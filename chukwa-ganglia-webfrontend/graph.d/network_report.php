<?php

/* Pass in by reference! */
function graph_network_report (&$metaInfo) {

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

    $title = 'Network';
    $metaInfo['lower-limit']    = '0';
    $metaInfo['vertical-label'] = 'MBytes/sec';

    $metaInfo['color']['bytes_in'] = $mem_cached_color;
    $metaInfo['color']['bytes_out'] = $mem_used_color;

    $metaInfo['style']['bytes_in'] = "line";
    $metaInfo['style']['bytes_out'] = "line";

    $metaInfo['order'][] = "bytes_in";
    $metaInfo['order'][] = "bytes_out";

    $chukwa = new chukwaBase($thrift_ip, $thrift_port);

    $metricData = array();
    $metricArray = array('bytes_in',
                         'bytes_out');

    if ($context != 'host') {
        $metaInfo['title'] = $title;
        $table = $range_cluster_table_map[$range];

        $data = $chukwa->getMetrics($table, $clustername, "bytes_in", $start, $end,
            "double", $time_ranges[$range], 1024 * 1024 * $num * $range_count[$range]);
        $metricData["bytes_in"] = $data["bytes_in"];

        $data = $chukwa->getMetrics($table, $clustername, "bytes_out", $start, $end,
            "double", $time_ranges[$range], 1024 * 1024 * $num * $range_count[$range]);
        $metricData["bytes_out"] = $data["bytes_out"];

    } else {
        $metaInfo['title'] = "$hostname Network last $range";
        $table = $range_host_table_map[$range];

	$data = $chukwa->getMetrics($table, $hostname, "bytes_in", $start, $end,
            "double", $time_ranges[$range], 1024 * 1024 * $range_count[$range]);
        $metricData["bytes_in"] = $data["bytes_in"];

        $data = $chukwa->getMetrics($table, $hostname, "bytes_out", $start, $end,
            "double", $time_ranges[$range], 1024 * 1024 * $range_count[$range]);
        $metricData["bytes_out"] = $data["bytes_out"];
    }

    return $metricData;
}

?>
