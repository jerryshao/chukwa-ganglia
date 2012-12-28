<?php
/* $Id: host_view.php 2203 2010-01-08 17:25:32Z d_pocock $ */
$tpl = new TemplatePower( template("job_view.tpl") );
$tpl->prepare();

$tpl->assign("job_name", $hadoop_job['summary:JOBNAME']);
$tpl->assign("job_submit_time", date("Y-m-d H:i:s", $hadoop_job['summary:SUBMIT_TIME'] / 1000));
$tpl->assign("job_launch_time", date("Y-m-d H:i:s", $hadoop_job['summary:LAUNCH_TIME'] / 1000));
$tpl->assign("job_finish_time", date("Y-m-d H:i:s", $hadoop_job['summary:FINISH_TIME'] / 1000));
$tpl->assign("job_map_num", $hadoop_job['summary:TOTAL_MAPS']);
$tpl->assign("job_reduce_num", $hadoop_job['summary:TOTAL_REDUCES']);

$jobstart = $start;
$jobrange = $end - $start;

$cluster_url=rawurlencode($clustername);
$graphargs = "c=$cluster_url&amp;r=hour&amp;jr=$jobrange&amp;js=$jobstart&amp;jb=$job_id";
$tpl->assign("cluster_url", $cluster_url);
$tpl->assign("graph_args", $graphargs);

$tpl->printToScreen();
?>
