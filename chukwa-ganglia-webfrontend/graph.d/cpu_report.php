<?php

/* Pass in by reference! */
function graph_cpu_report ( &$metaInfo ) {

    global $context,
           $cpu_idle_color,
           $cpu_nice_color,
           $cpu_system_color,
           $cpu_user_color,
           $cpu_wio_color,
           $hostname,
           $clustername,
           $range,
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
           $time_ranges,
           $always_last;

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

    $title = 'CPU';
    if ($context != 'host') {
       $metaInfo['title'] = $title;
    } else {
       $metaInfo['title'] = "$hostname $title last $range";
    }
    $metaInfo['upper-limit']    = '100';
    $metaInfo['lower-limit']    = '0';
    $metaInfo['vertical-label'] = 'Percent';

    $metaInfo['color']['cpu_user'] = $cpu_user_color;
    $metaInfo['color']['cpu_nice'] = $cpu_nice_color;
    $metaInfo['color']['cpu_system'] = $cpu_system_color;
    $metaInfo['color']['cpu_wio'] = $cpu_wio_color;
    $metaInfo['color']['cpu_idle'] = $cpu_idle_color;

    $metaInfo['style']['cpu_user'] = "area";
    $metaInfo['style']['cpu_nice'] = "area";
    $metaInfo['style']['cpu_system'] = "area";
    $metaInfo['style']['cpu_wio'] = "area";
    $metaInfo['style']['cpu_idle'] = "area";

    $metaInfo['order'][] = "cpu_user";
    $metaInfo['order'][] = "cpu_nice";
    $metaInfo['order'][] = "cpu_system";
    $metaInfo['order'][] = "cpu_wio";
    $metaInfo['order'][] = "cpu_idle";


    $chukwa = new chukwaBase($thrift_ip, $thrift_port);

    $metricData = array();
    $metricArray = array('cpu_user',
                         'cpu_nice',
                         'cpu_system',
                         'cpu_wio',
                         'cpu_idle');

    if($context != "host" ) {
        $table = $range_cluster_table_map[$range];

        $data = $chukwa->getMetrics($table, $clustername, "cpu_user", $start, $end,
            "double", $time_ranges[$range], $range_count[$range] * $num);
        $metricData["cpu_user"] = $data["cpu_user"];

        $data = $chukwa->getMetrics($table, $clustername, "cpu_nice", $start, $end,
            "double", $time_ranges[$range], $range_count[$range] * $num);
        $metricData["cpu_nice"] = $data["cpu_nice"];

        $data = $chukwa->getMetrics($table, $clustername, "cpu_system", $start, $end,
            "double", $time_ranges[$range], $range_count[$range] * $num);
        $metricData["cpu_system"] = $data["cpu_system"];

        $data = $chukwa->getMetrics($table, $clustername, "cpu_wio", $start, $end,
            "double", $time_ranges[$range], $range_count[$range] * $num);
        $metricData["cpu_wio"] = $data["cpu_wio"];

        $data = $chukwa->getMetrics($table, $clustername, "cpu_idle", $start, $end,
            "double", $time_ranges[$range], $range_count[$range] * $num);
        $metricData["cpu_idle"] = $data["cpu_idle"];

    } else {
        $metaInfo['title'] = "$hostname $title last $range";
        $table = $range_host_table_map[$range];

        $data = $chukwa->getMetrics($table, $hostname, "cpu_user", $start, $end,
            "double", $time_ranges[$range], $range_count[$range]);
        $metricData["cpu_user"] = $data["cpu_user"];

        $data = $chukwa->getMetrics($table, $hostname, "cpu_nice", $start, $end,
            "double", $time_ranges[$range], $range_count[$range]);
        $metricData["cpu_nice"] = $data["cpu_nice"];

        $data = $chukwa->getMetrics($table, $hostname, "cpu_system", $start, $end,
            "double", $time_ranges[$range], $range_count[$range]);
        $metricData["cpu_system"] = $data["cpu_system"];

        $data = $chukwa->getMetrics($table, $hostname, "cpu_wio", $start, $end,
            "double", $time_ranges[$range], $range_count[$range]);
        $metricData["cpu_wio"] = $data["cpu_wio"];

        $data = $chukwa->getMetrics($table, $hostname, "cpu_idle", $start, $end,
            "double", $time_ranges[$range], $range_count[$range]);
        $metricData["cpu_idle"] = $data["cpu_idle"];

    }

    return $metricData;
}

?>
