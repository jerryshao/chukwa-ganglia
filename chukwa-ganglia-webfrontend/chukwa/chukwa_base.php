<?php
require_once( $GLOBALS['THRIFT_ROOT'].'/Thrift.php' );
require_once( $GLOBALS['THRIFT_ROOT'].'/transport/TSocket.php' );
require_once( $GLOBALS['THRIFT_ROOT'].'/transport/TBufferedTransport.php' );
require_once( $GLOBALS['THRIFT_ROOT'].'/protocol/TBinaryProtocol.php' );
require_once( $GLOBALS['THRIFT_ROOT'].'/packages/Hbase/Hbase.php' );
require_once( 'Bytes.php' );

class chukwaBase {
    protected $thriftClient = null;
    protected $thriftHost = null;
    protected $thriftPort = null;
    protected $socket = null;
    protected $transport = null;
    protected $protocol = null;

    public function __construct($host, $port) {
        $this->thriftHost = $host;
        $this->thriftPort = $port;

        $this->socket = new TSocket($this->thriftHost, $this->thriftPort);
        $this->socket->setSendTimeout(8000);
        $this->socket->setRecvTimeout(8000);

        $this->transport = new TBufferedTransport($this->socket);
        $this->protocol = new TBinaryProtocol($this->transport);
        $this->thriftClient = new HbaseClient($this->protocol);
        $this->transport->open();
    }

    public function __destruct() {
        $this->transport->close();
    }

    /**
     * retrieve all the cluster names and host nams in HBase
     * @return array of cluster names with hostname and lastupdate,
     * when no cluster found or internal error,
     * return empty array.
     */
    public function getClusterHostNames() {
        $tableName = 'HostClusterTable';
        $columns = array('property:LASTUPDATE');

        $recordArray = array();
        try {
            $result = $this->thriftClient->scannerOpen($tableName, "", $columns, null);
            if ($result == null) {
                return $recordArray;
            }

            while ($record = $this->thriftClient->scannerGet($result)) {
                foreach ($record as $TRowResult) {
                    $row = $TRowResult->row;
                    list($cluster, $host) = split(":", $row);
                    $column = $TRowResult->columns;
                    foreach ($column as $family_column) {
                        $ts = (int)$family_column->value;
                        if (time() - $ts > 3600) {
                            continue;
                        }
                        $recordArray[$cluster][] = $host;
                    }
                }
            }
            $this->thriftClient->scannerClose($result);
        } catch (Exception $e) {
            error_log($e->getTraceAsString());
            return $recordArray;
        }

        return $recordArray;
    }

    /**
     *
     *
     */
    public function getHostNamesByCluster($clusterName) {
        $tableName = 'HostClusterTable';
        $filter = "PrefixFilter ('" . $clusterName . "')";
        $scan = new TScan(array('filterString' => $filter));

        $recordArray = array();
        try {
            $result = $this->thriftClient->scannerOpenWithScan($tableName, $scan, null);
            if ($result == null) {
                return $recordArray;
            }

            while ($record = $this->thriftClient->scannerGet($result)) {
                foreach ($record as $TRowResult) {
                    $row = $TRowResult->row;
                    list($cluster, $host) = split(":", $row);
                    $column = $TRowResult->columns;
                    foreach ($column as $family_column) {
                        $recordArray[$host] = (int)$family_column->value;
                    }
                }
            }
            $this->thriftClient->scannerClose($result);
        } catch (Exception $e) {
            error_log($e->getTraceAsString());
            return $recordArray;
        }

        return $recordArray;
    }


    /**
     * retrieve all the metrics according to specific host name.
     * @param hostName the specific host name
     * @return array of metrics, when no metrics got or internal error, return
     * empty array.
     */
    public function getHostMetrics($hostName) {
        $tableName = 'MetricAttributeTable';

        $filter = "PrefixFilter ('" . $hostName . "')";
        $scan = new TScan(array('filterString' => $filter));

        $recordArray = array();
        try {
            $result = $this->thriftClient->scannerOpenWithScan($tableName, $scan, null);
            if ($result == null) {
                return $recordArray;
            }

            while ($record = $this->thriftClient->scannerGet($result)) {
                foreach ($record as $TRowResult) {
                    $column = $TRowResult->columns;
                    list($host, $metric) = split(":", $TRowResult->row);
                    $recordArray[$metric]['NAME'] = $metric;
                    foreach ($column as $key => $val) {
                        list($c, $q) = split(":", $key);
                        $recordArray[$metric][$q] = $val->value;
                    }
                }
            }
            $this->thriftClient->scannerClose($result);
        } catch (Exception $e) {
            error_log($e->getTraceAsString());
            return $recordArray;
        }

        return $recordArray;
    }

    /**
     * retrieve specific the metrics data in a time range.
     * @param tableName  input table name
     * @param sourceName name of source to retrieve data, like: host name or cluster name
     * @param metricName metric names
     * @param startTime  start time to retrieve data, startTime is unix timestamp second
     * @param stopTime   stop time to retrieve data, stopTime is unix timestamp second
     * @param type       type value to explain the retrieve value
     * @rangeSec
     * @divide
     * @return array of metrics data, first key is metric name, second key is timestamp.
     * if no data retrieved or internal error, return empty array.
     */
    public function getMetrics($tableName,
                               $sourceName,
                               $metricName,
                               $startTime,
                               $stopTime,
                               $type,
                               $rangeSec,
                               $divide) {
        $startInterval = $startTime - $startTime % $rangeSec;
        $stopInterval = $stopTime - $stopTime % $rangeSec + 1;

        $startRow = $metricName . ":" . $sourceName . ":" . $startInterval;
        $stopRow = $metricName . ":" . $sourceName . ":" . $stopInterval;

        $recordArray = array();
        try {
            $result = $this->thriftClient->scannerOpenWithStop($tableName,
                $startRow, $stopRow, $columns, null);

            while ($record = $this->thriftClient->scannerGet($result)) {
                foreach ($record as $TRowResult) {
                    $column = $TRowResult->columns;
                    list($metric, $host, $ts) = split(":", $TRowResult->row);
                    foreach ($column as $key => $val) {
                        list($c, $q) = split(":", $key);
                        $time = (int)$ts + (int)$q;
                        //var_dump($time);
                        if ($time > $stopTime || $time < $startTime) {
                            continue;
                        }

                        if ($type == "string") {
                            $recordArray[$metric][$time] = $val->value;
                        } else if ($type == "double" || $type == "long") {
                            $recordArray[$metric][$time] = Bytes::toLong($val->value) / 1000 / $divide;
                        } else {
                            continue;
                        }
                    }
                }
            }
            $this->thriftClient->scannerClose($result);

        } catch (Exception $e) {
            error_log($e->getTraceAsString());
            return $recordArray;
        }

        foreach ($recordArray as &$k) {
            ksort($k);
        }



        return $recordArray;
    }

    /**
     * retrieve all the hadoop job id according to specific cluster name.
     * @param hostName the specific cluster name
     * @return array of hadoop job id, when no metrics got or internal error, return
     * empty array.
     */
    public function getHadoopJobByCluster($clusterName) {
        $tableName = 'JobHistoryTable';

        $filter = "PrefixFilter ('" . $clusterName . "') AND FirstKeyOnlyFilter()";
        $scan = new TScan(array('filterString' => $filter));

        $recordArray = array();
        try {
            $result = $this->thriftClient->scannerOpenWithScan($tableName, $scan, null);
            if ($result == null) {
                return $recordArray;
            }

            while ($record = $this->thriftClient->scannerGet($result)) {
                foreach ($record as $TRowResult) {
                    list($c, $j) = split(":", $TRowResult->row);
                    $recordArray[] = $j;
                }
            }
            $this->thriftClient->scannerClose($result);
        } catch (Exception $e) {
            error_log($e->getTraceAsString());
            return $recordArray;
        }

        return $recordArray;
    }

    public function getHadoopJobHistory($clusterName, $jobName, $columns) {
        $tableName = 'JobHistoryTable';
        $row = $clusterName . ":" . $jobName;

        $recordArray = array();
        try {
            $result = $this->thriftClient->getRowWithColumns($tableName, $row, $columns, null);
            if ($result == null) {
                return $recordArray;
            }

        } catch (Exception $e) {
            error_log($e->getTraceAsString());
            return $recordArray;
        }

        foreach ($result as $TRowResult) {
            $columns = $TRowResult->columns;
            foreach ($columns as $key => $val) {
                $recordArray[$key] = $val->value;
            }
        }

        return $recordArray;
    }

}
?>
