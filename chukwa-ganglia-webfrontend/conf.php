<?php
# $Id: conf.php.in 2194 2010-01-08 16:58:25Z d_pocock $
#
# Gmetad-webfrontend version. Used to check for updates.
#
include_once "./version.php";

#
# The name of the directory in "./templates" which contains the
# templates that you want to use. Templates are like a skin for the
# site that can alter its look and feel.
#
$template_name = "default";

#
# The maximum number of dynamic graphs to display.  If you set this
# to 0 (the default) all graphs will be shown.  This option is
# helpful if you are viewing the web pages from a browser with a
# small pipe.
#
$max_graphs = 0;

#
# In the Cluster View this sets the default number of columns used to
# display the host grid below the summary graphs.
#
$hostcols = 4;

#
# In the Host View this sets the default number of columns used to
# display the metric grid below the summary graphs.
#
$metriccols = 2;

#
# Turn on and off the Grid Snapshot. Now that we have a
# hierarchical snapshot (per-cluster instead of per-node) on
# the meta page this makes more sense. Most people will want this
# on.
#
$show_meta_snapshot = "yes";

#
# The default refresh frequency on pages.
#
$default_refresh = 300;

#
# Colors for the CPU report graph
#
$cpu_user_color = "3333bb";
$cpu_nice_color = "ffea00";
$cpu_system_color = "dd0000";
$cpu_wio_color = "ff8a60";
$cpu_idle_color = "e2e2f2";

#
# Colors for the MEMORY report graph
#
$mem_used_color = "5555cc";
$mem_shared_color = "0000aa";
$mem_cached_color = "33cc33";
$mem_buffered_color = "99ff33";
$mem_free_color = "00ff00";
$mem_swapped_color = "9900CC";

#
# Colors for the LOAD report graph
#
$load_one_color = "CCCCCC";
$proc_run_color = "0000FF";
$cpu_num_color  = "FF0000";
$num_nodes_color = "00FF00";

# Other colors
$jobstart_color = "ff3300";

#
# Colors for the load ranks.
#
$load_colors = array(
    "100+" => "ff634f",
    "75-100" =>"ffa15e",
    "50-75" => "ffde5e",
    "25-50" => "caff98",
    "0-25" => "e2ecff",
    "down" => "515151"
);

$graphdir='./graph.d';

#
# Load scaling
#
$load_scale = 1.0;

#
# Default color for single metric graphs
#
$default_metric_color = "555555";

#
# Default metric
#
$default_metric = "load_one";

#
# remove the domainname from the FQDN hostnames in graphs
# (to help with long hostnames in small charts)
#
$strip_domainname = false;

#
# Optional summary graphs
#
#$optional_graphs = array('packet');

#
# Time ranges
# Each value is the # of seconds in that range.
#
$time_ranges = array(
    'hour'=>3600,
    'day'=>86400,
    'week'=>604800,
    'month'=>2592000,
    'year'=>31536000,
    'custom' => 3600
);

# this key must exist in $time_ranges
$default_time_range = 'hour';

#
# Graph sizes
#
$graph_sizes = array(
    'small'=>array(
      'height'=>135,
      'width'=>240,
      'fudge_0'=>0,
      'fudge_1'=>0,
      'fudge_2'=>0
    ),
    'medium'=>array(
      'height'=>180,
      'width'=>320,
      'fudge_0'=>0,
      'fudge_1'=>14,
      'fudge_2'=>28
    ),
    'large'=>array(
      'height'=>450,
      'width'=>800,
      'fudge_0'=>0,
      'fudge_1'=>0,
      'fudge_2'=>0
    ),
    # this was the default value when no other size was provided.
    'default'=>array(
      'height'=>180,
      'width'=>320,
      'fudge_0'=>0,
      'fudge_1'=>0,
      'fudge_2'=>0
    )
);
$default_graph_size = 'default';
$graph_sizes_keys = array_keys( $graph_sizes );

# In earlier versions of gmetad, hostnames were handled in a case
# sensitive manner
# If your hostname directories have been renamed to lower case,
# set this option to 0 to disable backward compatibility.
# From version 3.2, backwards compatibility will be disabled by default.
# default: true  (for gmetad < 3.2)
# default: false (for gmetad >= 3.2)
$case_sensitive_hostnames = true;


# HBase thrift related configure
$GLOBALS['THRIFT_ROOT'] = dirname(__FILE__) . '/hbasethrift/libs';
$thrift_ip = 'localhost';
$thrift_port = 10080;

$cluster_table = "ClusterMetricData";
$range_cluster_table_map = array (
    "hour"  =>    "ClusterMetricData",
    "day"   =>    "ClusterMetricDataDay",
    "week"  =>    "ClusterMetricDataWeek",
    "month" =>    "ClusterMetricDataMonth",
    "year"  =>    "ClusterMetricDataYear",
    "custom"  =>    "ClusterMetricData"
);

$host_table = "MetricData";
$range_host_table_map = array (
    "hour"  =>    "MetricData",
    "day"   =>    "MetricDataDay",
    "week"  =>    "MetricDataWeek",
    "month" =>    "MetricDataMonth",
    "year"  =>    "MetricDataYear",
    "custom"  =>    "MetricData"
);

$range_count = array(
    'hour' => 1,
    'day' => 24,
    'week' => 168,
    'month' => 720,
    'year' => 8760,
    'custom' => 1
);


$authority = "http://10.0.0.15/ganglia-test";

# setting error log
error_reporting(E_ALL);
ini_set("display_errors", "off");
ini_set("log_errors", "on");
ini_set("error_log", "./error.log");
?>
