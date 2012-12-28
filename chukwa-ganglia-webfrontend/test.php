<?php
echo "here";
include_once "./conf.php";
include_once "./chukwa/chukwa_base.php";
include_once "./graph.d/mapred_report.php";

echo "here1";
//$chukwa = new chukwaBase($thrift_ip, $thrift_port);

//var_dump($chukwa->getHadoopJobByCluster("chukwa"));
//var_dump($chukwa->getHadoopJobHistory("chukwa", "job_201211291507_0001", array('task')));


$clustername = "chukwa";
$job_id = "job_201211291507_0001";
$start = 1354178706;
$end = 1354178706 + 82;
$metaInfo = array();

echo "here2";
graph_mapred_report($metaInfo);

//var_dump($chukwa->getHostNamesByCluster("chukwa"));

//var_dump($chukwa->getMetrics("ClusterMetricData", "chukwa", "cpu_num", time() - 60, time(), "double", 3600, 1));
//var_dump($chukwa->getMetrics("ClusterMetricData", "chukwa", "cpu_num", time() - 3600, time(), "double", 3600));
//var_dump($chukwa->getMetrics("ClusterMetricData", "chukwa", "proc_run", time() - 3600, time(), "double", 3600));
//var_dump($chukwa->getMetrics("MetricData", "jerryshao-desktop", "cpu_num", 1353024000, 1353027600, "double", 3600));
//var_dump($chukwa->getMetrics("MetricData", "jerryshao-desktop", "proc_run", 1353027600, 1353027601, "double"));

?>
