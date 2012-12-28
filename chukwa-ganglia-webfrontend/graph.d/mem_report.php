<?php

/* Pass in by reference! */
function graph_mem_report (&$metaInfo) {

    global $context,
           $hostname,
           $clustername,
           $mem_shared_color,
           $mem_cached_color,
           $mem_buffered_color,
           $mem_swapped_color,
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

    $metaInfo['color']['mem_total'] = $cpu_num_color;
    $metaInfo['color']['mem_used'] = $mem_used_color;


    $metaInfo['style']['mem_total'] = "line";
    $metaInfo['style']['mem_used'] = "area";

    $metaInfo['order'][] = "mem_used";
    $metaInfo['order'][] = "mem_total";

    $title = 'Memory';
    $metricArray = array('mem_total',
                         'mem_used');
    $metricData = array();

    $chukwa = new chukwaBase($thrift_ip, $thrift_port);

    if ($context != 'host') {
        $metaInfo['title'] = $title;
        $table = $range_cluster_table_map[$range];

        $data = $chukwa->getMetrics($table, $clustername, "mem_total", $start, $end,
            "long", $time_ranges[$range], $range_count[$range] * 1024);
        $metricData["mem_total"] = $data["mem_total"];

        $data = $chukwa->getMetrics($table, $clustername, "mem_used", $start, $end,
            "long", $time_ranges[$range], $range_count[$range] * 1024);
        $metricData["mem_used"] = $data["mem_used"];
    	
	$metaInfo['vertical-label'] = 'GBytes';
        
    } else {
        $metaInfo['title'] = "$hostname $title last $range";
        $table = $range_host_table_map[$range];

        $data = $chukwa->getMetrics($table, $hostname, "mem_total", $start, $end,
            "long", $time_ranges[$range], $range_count[$range]);
        $metricData["mem_total"] = $data["mem_total"];

        $data = $chukwa->getMetrics($table, $hostname, "mem_used", $start, $end,
            "long", $time_ranges[$range], $range_count[$range]);
        $metricData["mem_used"] = $data["mem_used"];

    	$metaInfo['vertical-label'] = 'MBytes';
    }

    $metaInfo['lower-limit']    = '0';

    return $metricData;

}

?>
