<?php

// This report is used for specific metric graphs at the bottom of the
// cluster_view page.

/* Pass in by reference! */
function graph_metric ( &$metaInfo) {

    global $context,
           $clustername,
           $hostname,
           $meta_designator,
           $metricname,
           $range,
           $summary,
           $metrictitle,
           $vlabel,
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

    switch ($context) {
        case 'host':
            $metaInfo['title'] = $metricname;
            if ($summary) {
                $metaInfo['title'] = $hostname;
            } else {
                if ($metrictitle) {
                   $metaInfo['title'] = $metrictitle;
                } else {
                   $metaInfo['title'] = $metricname;
                }
            }
            break;

        case 'meta':
            $metaInfo['title'] = "$meta_designator ". $metaInfo['title'] ."last $range";
            break;

        case 'grid':
            $metaInfo['title'] = "$meta_designator ". $metaInfo['title'] ."last $range";
            break;

        case 'cluster':
            $metaInfo['title'] = "$clustername " . $metaInfo['title'] ."last $range";
            break;

        default:
            $metaInfo['title'] = $metricname;
            break;
    }

    $metaInfo['color'][$metricname] = "303030";
    $metaInfo['style'][$metricname] = "area";
    $metaInfo['order'][] = $metricname;

    if (isset($max) && is_numeric($max))
        $metaInfo['upper-limit'] = $max;

    if (isset($min) && is_numeric($min))
        $metaInfo['lower-limit'] = $min;

    if ($vlabel) {
        // We should set $vlabel, even if it isn't used for spacing
        // and alignment reasons.  This is mostly for aesthetics
        $temp_vlabel = trim($vlabel);
        $metaInfo['vertical-label'] = strlen($temp_vlabel)
                   ?  $temp_vlabel
                   :  ' ';
    } else {
        $metaInfo['vertical-label'] = ' ';
    }

    $chukwa = new chukwaBase($thrift_ip, $thrift_port);
    $metricData = array();

    $divide = 1;
    if (isset($always_last[$m]) && $always_last[$m]) {
        $divide *= 1;
    } else {
        $divide *= $range_count[$range];
    }

    switch ($context) {
        case 'host':
            $table = $range_host_table_map[$range];
            $metricData = $chukwa->getMetrics($table, $hostname,
                $metricname, $start, $end, "double", $time_ranges[$range], $divide);
            break;

        case 'meta':
            $table = $range_cluster_table_map[$range];
            $metricData = $chukwa->getMetrics($table, $clustername,
                $metricname, $start, $end, "double", $time_ranges[$range], $num * $divide);
            break;

        case 'cluster':
            $table = $range_cluster_table_map[$range];
            $metricData = $chukwa->getMetrics($table, $clustername,
                $metricname, $start, $end, "double", $time_ranges[$range], $num * $divide);
            break;

        default:
            break;
    }

    return $metricData;
}

?>
