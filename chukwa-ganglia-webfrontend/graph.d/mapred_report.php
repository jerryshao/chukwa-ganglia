<?php
function my_compare($a, $b) {
    if ($a['bootstrap'] < $b['bootstrap'])
        return -1;
    else if ($a['bootstrap'] == $b['bootstrap'])
        return 0;
    else
        return 1;
}

function graph_mapred_report ( &$metaInfo ) {

    global $context,$job_id, $start, $end, $thrift_ip, $thrift_port, $clustername;

    $chukwa = new chukwaBase($thrift_ip, $thrift_port);
    $data = $chukwa->getHadoopJobHistory($clustername, $job_id, array("mapAttempt"));
    if (count($data) == 0) {
        exit;
    }

    $mapData = array();
    foreach ($data as $job => $val) {
        $arr = json_decode($val, TRUE);
        $mapData[$job]['bootstrap'] = (int)$arr['START_TIME'] - $start * 1000;
        $mapData[$job]['active'] = (int)$arr['FINISH_TIME'] - (int)$arr['START_TIME'];
        $mapData[$job]['shuffle'] = 0;
        $mapData[$job]['sort'] = 0;
        $mapData[$job]['reduce'] = 0;
        $mapData[$job]['idle'] = $end * 1000 - (int)$arr['FINISH_TIME'];
    }

    uasort($mapData, 'my_compare');

    $data = $chukwa->getHadoopJobHistory($clustername, $job_id, array("reduceAttempt"));

    $reduceData = array();
    if (count($data) != 0) {
        foreach ($data as $job => $val) {
            $arr = json_decode($val, TRUE);
            $reduceData[$job]['bootstrap'] = (int)$arr['START_TIME'] - $start * 1000;
            $reduceData[$job]['active'] = 0;
            $reduceData[$job]['shuffle'] = (int)$arr['SHUFFLE_FINISHED'] - (int)$arr['START_TIME'];
            $reduceData[$job]['sort'] = (int)$arr['SORT_FINISHED'] - (int)$arr['SHUFFLE_FINISHED'];
            $reduceData[$job]['reduce'] = (int)$arr['FINISH_TIME'] - (int)$arr['SORT_FINISHED'];
            $reduceData[$job]['idle'] = $end * 1000 - (int)$arr['FINISH_TIME'];
        }
        uasort($reduceData, 'my_compare');
    }

    $metricData = array();
    foreach ($mapData as $key  => $val) {
        foreach ($val as $t => $v) {
            $metricData[$t][] = $v;
        }
    }
    if (count($reduceData) != 0) {
        foreach ($reduceData as $key => $val) {
            foreach ($val as $t => $v) {
                $metricData[$t][] = $v;
            }
        }
    }

    $metaInfo["title"] = $job_id;
    $metaInfo['upper-limit']    = '100';
    $metaInfo['lower-limit']    = '0';
    $metaInfo['vertical-label'] = 'Percent';

    $metaInfo['color']['bootstrap'] = "4572a7";
    $metaInfo['color']['active'] = "aa4643";
    $metaInfo['color']['shuffle'] = "89a54e";
    $metaInfo['color']['sort'] = "71588f";
    $metaInfo['color']['reduce'] = "4198af";
    $metaInfo['color']['idle'] = "db843d";

    return $metricData;
}



?>
