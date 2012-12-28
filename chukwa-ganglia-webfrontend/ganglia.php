<?php
/* $Id: ganglia.php 1817 2008-09-17 10:03:15Z carenas $ */
#
# Parses ganglia XML tree.
#
# The arrays defined in the first part of this file to hold XML info.
#
# sacerdoti: These are now context-sensitive, and hold only as much
# information as we need to make the page.
#

$error="";

# 2key = "Source Name" / "NAME | AUTHORITY | HOSTS_UP ..." = Value.
$grid = array();

# 1Key = "NAME | LOCALTIME | HOSTS_UP | HOSTS_DOWN" = Value.
$cluster = array();

# 2Key = "Cluster Name / Host Name" ... Value = Array of Host Attributes
$hosts_up = array();
# 2Key = "Cluster Name / Host Name" ... Value = Array of Host Attributes
$hosts_down = array();

# Context dependant structure.
$metrics = array();

# 1Key = "Component" (gmetad | gmond) = Version string
$version = array();

# The web frontend version, from conf.php.
#$version["webfrontend"] = "$majorversion.$minorversion.$microversion";
$version["webfrontend"] = "$ganglia_version";

# The name of our local grid.
$self = "unspecified";

$hadoop_job = array();

# create chukwa connection
$chukwa = new chukwaBase($thrift_ip, $thrift_port);

//changed by our use
function start_meta() {
    global $grid, $chukwa, $authority, $cluster_arr;

    $allHostCount = 0;
    $clusterHostMap = array();

    if ($cluster_arr) {
        $clusterHostMap = $cluster_arr;
    } else {
        $clusterHostMap = $chukwa->getClusterHostNames();
        if (count($clusterHostMap) == 0) {
            print "<H4>There was no cluster names finding in HBase, check HBase</H4>\n";
            exit;
        }
        setcookie("ganglia_cluster", json_encode($clusterHostMap), 0);
    }

    foreach ($clusterHostMap as $cluster => $hostList) {
        $hostsCount = count($hostList);
        $allHostCount += $hostsCount;

        $grid[$cluster] = Array("NAME" => $cluster,
                             "LOCALTIME" => sprintf("%d", time()),
                             "OWNER" => "unspecified",
                             "LATLONG" => "unspecified",
                             "URL" => "unspecified",
                             "CLUSTER" => 1,
                             "HOSTS_UP" => "$hostsCount",
                             "HOSTS_DOWN" => "0");
    }

    $grid["unspecified"] = Array("NAME" => "unspecified",
                                 "AUTHORITY" => $authority,
                                 "LOCALTIME" => sprintf("%d", time()),
                                 "GRID" => 1,
                                 "HOSTS_UP" => "$allHostCount",
                                 "HOSTS_DOWN" => "0");
}

function start_cluster() {
    global $clustername, $chukwa;
    global $metrics, $cluster, $grid, $hosts_up, $authority;

    $grid["NAME"] = "unspecified";
    $grid["LOCATIME"] = sprintf("%d", time());
    $grid["AUTHORITY"] = $authority;

    $hostNames = $chukwa->getHostNamesByCluster($clustername);
    if (count($hostNames) == 0) {
        print "<H4>There was no host names finding in HBase, check HBase</H4>\n";
        exit;
    }

    $hadoopJobs = $chukwa->getHadoopJobByCluster($clustername);

    $cluster["NAME"] = $clustername;
    $cluster["LOCALTIME"] = sprintf("%d", time());
    $cluster["OWNER"] = "unspecified";
    $cluster["LATLONG"] = "unspecified";
    $cluster["URL"] = "unspecified";
    $cluster["HOSTS_UP"] = count($hostNames);
    $cluster["JOBS"] = $hadoopJobs;

    foreach ($hostNames as $name => $ts) {
        $hosts_up[$name] = Array("NAME" => $name,
                                 "IP" => gethostbyname($name),
                                 "REPORTED" => sprintf("%d", ts),
                                 "LOCATION" => "unspecified");

        $metrics[$name] = $chukwa->getHostMetrics($name);
    }
}

function start_host() {
    global $chukwa, $hostname;
    global $metrics, $cluster, $hosts_up, $self, $grid, $authority;

    $grid["NAME"] = "unspecified";
    $grid["AUTHORITY"] = $authority;
    $grid["LOCALTIME"] = sprintf("%d", time());

    $cluster["NAME"] = $clustername;
    $cluster["LOCALTIME"] = sprintf("%d", time());
    $cluster["OWNER"] = "unspecified";
    $cluster["LATLONG"] = "unspecified";
    $cluster["URL"] = "unspecified";


    $hosts_up["NAME"] = $hostname;
    $hosts_up["IP"] = gethostbyname($hostname);
    $hosts_up["REPORTED"] = sprintf("%d", time());
    $hosts_up["LOCATION"] = "unspecified";
    $hosts_up["GMOND_STARTED"] = sprintf("%d", time());

    $metrics = $chukwa->getHostMetrics($hostname);
}

function start_hadoop_job() {
    global $job_key, $job_id, $start, $end;
    global $hadoop_job, $chukwa, $clustername;

    $hadoop_job = $chukwa->getHadoopJobHistory($clustername, $job_id, array('summary'));

    if (isset($hadoop_job['summary:LAUNCH_TIME'])) {
        $start = (int)($hadoop_job['summary:LAUNCH_TIME'] / 1000);
    } else if (isset($hadoop_job['summary:SUBMIT_TIME'])) {
        $start = (int)($hadoop_job['summary:SUBMIT_TIME'] / 1000);
    }

    $end = (int)($hadoop_job['summary:FINISH_TIME'] / 1000);
}

function get_hbase_metainfo() {
    global $context;

    switch ($context) {
        case "meta":
        case "control":
        case "tree":
        default:
           start_meta();
           break;
        case "physical":
        case "cluster":
            start_cluster();
            break;
        case "cluster-summary":
           break;
        case "node":
        case "host":
            start_host();
            break;
        case "job":
            start_hadoop_job();
            break;
    }

    return TRUE;
}
?>
