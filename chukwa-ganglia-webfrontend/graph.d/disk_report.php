<?php

/* Pass in by reference! */
function graph_disk_report (&$metaInfo) {

    global $context,
           $hostname,
           $clustername,
           $mem_cached_color,
           $mem_used_color,
           $cpu_num_color,
           $range,
           $rrd_dir,
           $size,
           $strip_domainname,
           $thrift_ip,
           $thrift_port,
           $system_table,
           $cluster_table,
           $start,
           $end,
           $range_cluster_table_map,
           $range_host_table_map,
           $cluster_arr,
           $range_count,
           $time_ranges;

    $num = 0;
    if ($clustername == "unspecified") {
        foreach ($cluster_arr as $k) {
            $num += count($k);
        }
    } else {
        $num = count($cluster_arr[$clustername]);
    }

    if ($strip_domainname) {
       $hostname = strip_domainname($hostname);
    }

    $title = 'Disk';
    $metaInfo['lower-limit']    = '0';
    $metaInfo['vertical-label'] = 'MBytes/sec';

    $metaInfo['color']['bytes_read'] = $mem_cached_color;
    $metaInfo['color']['bytes_write'] = $mem_used_color;

    $metaInfo['style']['bytes_read'] = "line";
    $metaInfo['style']['bytes_write'] = "line";

    $metaInfo['order'][] = "bytes_read";
    $metaInfo['order'][] = "bytes_write";

    $chukwa = new chukwaBase($thrift_ip, $thrift_port);

    $metricData = array();
    $metricArray = array('bytes_read',
                         'bytes_write');

    if ($context != 'host') {
        $metaInfo['title'] = $title;
        $table = $range_cluster_table_map[$range];

        $data = $chukwa->getMetrics($table, $clustername, "bytes_read", $start, $end,
            "double", $time_ranges[$range], 1024 * 1024 * $num * $range_count[$range]);
        $metricData["bytes_read"] = $data["bytes_read"];
 
        $data = $chukwa->getMetrics($table, $clustername, "bytes_write", $start, $end,
            "double", $time_ranges[$range], 1024 * 1024 * $num * $range_count[$range]);
        $metricData["bytes_write"] = $data["bytes_write"];

    } else {
        $metaInfo['title'] = "$hostname disk last $range";
        $table = $range_host_table_map[$range];
 
       $data = $chukwa->getMetrics($table, $hostname, "bytes_read", $start, $end,
            "double", $time_ranges[$range], 1024 * 1024 * $range_count[$range]);
        $metricData["bytes_read"] = $data["bytes_read"];
 
        $data = $chukwa->getMetrics($table, $hostname, "bytes_write", $start, $end,
            "double", $time_ranges[$range], 1024 * 1024 * $range_count[$range]);
        $metricData["bytes_write"] = $data["bytes_write"];

    }

    return $metricData;
}

?>
