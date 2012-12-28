<?php

/* Pass in by reference! */
function graph_load_report ( &$metaInfo ) {

    global $context,
           $cpu_num_color,
           $cpu_user_color,
           $clustername,
           $hostname,
           $load_one_color,
           $num_nodes_color,
           $proc_run_color,
           $range,
           $size,
           $strip_domainname,
           $thrift_ip,
           $thrift_port,
           $system_table,
           $cluster_table,
           $load_color,
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

    $metaInfo['lower-limit']    = '0';
    $metaInfo['vertical-label'] = 'Load/Procs';
    $title = 'Load';

    $metaInfo['color']['load_one'] = $load_one_color;
    $metaInfo['color']['cpu_num'] = $cpu_num_color;
    $metaInfo['color']['proc_run'] = $proc_run_color;
    $metaInfo['style']['load_one'] = "area";
    $metaInfo['style']['cpu_num'] = "line";
    $metaInfo['style']['proc_run'] = "line";

    $metaInfo['order'][] = "cpu_num";
    $metaInfo['order'][] = "proc_run";
    $metaInfo['order'][] = "load_one";

    $metricData = array();

    $chukwa = new chukwaBase($thrift_ip, $thrift_port);

    if ($context != 'host') {
        $metaInfo['title'] = $title;
        $table = $range_cluster_table_map[$range];

        $data = $chukwa->getMetrics($table, $clustername, "cpu_num", $start, $end,
            "double", $time_ranges[$range], $range_count[$range]);
        $metricData["cpu_num"] = $data["cpu_num"];

        $data = $chukwa->getMetrics($table, $clustername, "proc_run", $start, $end,
            "double", $time_ranges[$range], $range_count[$range] * $num);
        $metricData["proc_run"] = $data["proc_run"];

        $data = $chukwa->getMetrics($table, $clustername, "load_one", $start, $end,
            "double", $time_ranges[$range], $range_count[$range] * $num);
        $metricData["load_one"] = $data["load_one"];

    } else {
        $metaInfo['title'] = "$hostname $title last $range";
        $table = $range_host_table_map[$range];

        $data = $chukwa->getMetrics($table, $hostname, "cpu_num", $start, $end,
            "double", $time_ranges[$range], $range_count[$range]);
        $metricData["cpu_num"] = $data["cpu_num"];

        $data = $chukwa->getMetrics($table, $hostname, "proc_run", $start, $end,
            "double", $time_ranges[$range], $range_count[$range]);
        $metricData["proc_run"] = $data["proc_run"];

        $data = $chukwa->getMetrics($table, $hostname, "load_one", $start, $end,
            "double", $time_ranges[$range], $range_count[$range]);
        $metricData["load_one"] = $data["load_one"];

    }

    return $metricData;
}
?>
