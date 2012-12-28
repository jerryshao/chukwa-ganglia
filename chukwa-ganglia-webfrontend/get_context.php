<?php
/* $Id: get_context.php 2182 2010-01-07 16:00:54Z d_pocock $ */

include_once "./functions.php";


$meta_designator = "Grid";
$cluster_designator = "Cluster Overview";

# Blocking malicious CGI input.
$clustername = isset($_GET["c"]) ?
	escapeshellcmd(clean_string(rawurldecode($_GET["c"]))) : NULL;
$gridname = isset($_GET["G"]) ?
	escapeshellcmd(clean_string(rawurldecode($_GET["G"]))) : NULL;
if($case_sensitive_hostnames == 1) {
    $hostname = isset($_GET["h"]) ?
        escapeshellcmd(clean_string(rawurldecode($_GET["h"]))) : NULL;
} else {
    $hostname = isset($_GET["h"]) ?
        strtolower(escapeshellcmd(clean_string(rawurldecode($_GET["h"])))) : NULL;
}

$range = isset($_GET["r"]) && in_array($_GET["r"], array_keys($time_ranges)) ?
	escapeshellcmd( rawurldecode($_GET["r"])) : NULL;
$metricname = isset($_GET["m"]) ?
	escapeshellcmd(clean_string(rawurldecode($_GET["m"]))) : NULL;
$metrictitle = isset($_GET["ti"]) ?
	escapeshellcmd(clean_string(rawurldecode($_GET["ti"]))) : NULL;
$sort = isset($_GET["s"]) ?
	escapeshellcmd(clean_string(rawurldecode($_GET["s"]))) : NULL;
$controlroom = isset($_GET["cr"]) ?
    clean_number(rawurldecode($_GET["cr"])) : NULL;

#get cookies for the whole cluster
$cluster_str = isset($_COOKIE["ganglia_cluster"]) ? rawurldecode($_COOKIE["ganglia_cluster"]) : NULL;
$cluster_arr = null;
if ($cluster_str) {
    $cluster_arr = json_decode($cluster_str, TRUE);
}

# Default value set in conf.php, Allow URL to overrride
if (isset($_GET["hc"]))
    $hostcols = clean_number($_GET["hc"]);
if (isset($_GET["mc"]))
    $metriccols = clean_number($_GET["mc"]);
# Flag, whether or not to show a list of hosts
$showhosts = isset($_GET["sh"]) ?
    clean_number($_GET["sh"]) : NULL;
# A custom range value for job graphs, in -sec.
$jobrange = isset($_GET["jr"]) ?
    intval(clean_number($_GET["jr"])) : NULL;
# A red vertical line for various events. Value specifies the event time.
$jobstart = isset($_GET["js"]) ?
    intval(clean_number($_GET["js"])) : NULL;
# The direction we are travelling in the grid tree
$gridwalk = isset($_GET["gw"]) ?
    escapeshellcmd(clean_string( $_GET["gw"])) : NULL;

$cs = isset($_GET["cs"]) ?
    intval(clean_number($_GET["cs"])) : NULL;
$ce = isset($_GET["ce"]) ?
    intval(clean_number($_GET["ce"])) : NULL;
if ($cs != 0 && $ce != 0 && $cs < $ce) {
 
} else {
    $cs = null;
    $ce = null;
}
    

# Size of the host graphs in the cluster view
$clustergraphsize = isset($_GET["z"]) && in_array($_GET['z'], $graph_sizes_keys) ?
    escapeshellcmd($_GET["z"]) : NULL;

# A stack of grid parents. Prefer a GET variable, default to cookie.
if (isset($_GET["gs"]) and $_GET["gs"])
    $gridstack = explode(">", rawurldecode($_GET["gs"]));

else if (isset($_COOKIE['gs']) and $_COOKIE['gs'])
    $gridstack = explode(">", $_COOKIE["gs"]);

if (isset($gridstack) and $gridstack) {
   foreach($gridstack as $key=>$value)
       $gridstack[$key] = clean_string($value);
}

#parameter for hadoop job
$job_id = isset($_GET['jb']) ? rawurldecode($_GET['jb']) : NULL;

# Assume we are the first grid visited in the tree if there is no gridwalk
# or gridstack is not well formed. Gridstack always has at least one element.
if (!isset($gridstack) or !strstr($gridstack[0], "http://"))
    $initgrid = TRUE;

# Default values
if (!isset($hostcols) || !is_numeric($hostcols)) $hostcols = 4;
if (!isset($metriccols) || !is_numeric($metriccols)) $metriccols = 2;
if (!is_numeric($showhosts)) $showhosts = 1;

# Set context.
if(!$clustername && !$hostname && $controlroom) {
    $context = "control";
} else if(!$clustername and !$gridname and !$hostname) {
    $context = "meta";
} else if($gridname) {
    $context = "grid";
} else if ($clustername and !$hostname and !$showhosts) {
    $context = "cluster-summary";
} else if ($clustername and !$hostname and $job_id) {
    $context = "job";
} else if($clustername and !$hostname) {
    $context = "cluster";
} else if($clustername and $hostname) {
    $context = "host";
}

if (!$range)
    $range = "$default_time_range";

$end = "N";
$start = null;
# $time_ranges defined in conf.php
if($context == 'job' && isset($jobrange)) {
  $start = $jobstart;
  $end = $start + $jobrange;
} else if ($cs && $ce) {
  $start = $cs;
  $end = $ce;
  $range = "custom";
} else if (isset($time_ranges[ $range ])) {
  if ($range)
  $end = time() - 30;
  $start = $end + $time_ranges[$range] * -1;
} else {
  $end = time() - 30;
  $start = $end + $time_ranges[$default_time_range] * -1;
}

if (!$metricname)
    $metricname = "$default_metric";

if (!$sort)
    $sort = "descending";

# Since cluster context do not have the option to sort "by hosts down" or
# "by hosts up", therefore change sort order to "descending" if previous
# sort order is either "by hosts down" or "by hosts up"
if ($context == "cluster") {
    if ($sort == "by hosts up" || $sort == "by hosts down") {
        $sort = "descending";
    }
}

# A hack for pre-2.5.0 ganglia data sources.
$always_constant = array(
    "cpu_speed" => 1,
    "swap_total" => 1,
    "mem_total" => 1
);

$always_timestamp = array(
    //"boottime" => 1
);

$always_last = array(
    "boottime" => 1,
    "cpu_num" => 1,
    "cpu_speed" => 1
);

# List of report graphs
$reports = array(
    "load_report" => "load_one",
    "cpu_report" => 1,
    "mem_report" => 1,
    "network_report" => 1,
    "disk_report" => 1
);

?>
