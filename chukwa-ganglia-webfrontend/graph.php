<?php
/* $Id: graph.php 2183 2010-01-07 16:09:55Z d_pocock $ */
include_once "./eval_config.php";
include_once "./get_context.php";
include_once "./functions.php";
include_once "./chukwa/chukwa_base.php";
include_once "./conf.php";
include_once "./graph.d/pChart_graph.php";
include_once "./graph.d/stack_graph.php";
# RFM - Added all the isset() tests to eliminate "undefined index"
# messages in ssl_error_log.

# Graph specific variables
# ATD - No need for escapeshellcmd or rawurldecode on $size or $graph.  Not used directly in rrdtool calls.

$size = isset($_GET["z"]) && in_array( $_GET["z"], $graph_sizes_keys )
             ? $_GET["z"]
             : NULL;
# ATD - TODO, should encapsulate these custom graphs in some type of container, then this code could check list of defined containers for valid graph labels.
$graph      = isset($_GET["g"])  ?  sanitize ( $_GET["g"] )   : NULL;
$grid       = isset($_GET["G"])  ?  sanitize ( $_GET["G"] )   : NULL;
$self       = isset($_GET["me"]) ?  sanitize ( $_GET["me"] )  : NULL;
$vlabel     = isset($_GET["vl"]) ?  sanitize ( $_GET["vl"] )  : NULL;
$value      = isset($_GET["v"])  ?  sanitize ( $_GET["v"] )   : NULL;
if ($self) {
    $clustername = $self;
}


$max        = isset($_GET["x"])  ?  clean_number ( sanitize ($_GET["x"] ) ) : NULL;
$min        = isset($_GET["n"])  ?  clean_number ( sanitize ($_GET["n"] ) ) : NULL;
$sourcetime = isset($_GET["st"]) ?  clean_number ( sanitize( $_GET["st"] ) ) : NULL;

$load_color = isset($_GET["l"]) && is_valid_hex_color( rawurldecode( $_GET[ 'l' ] ) )
                                 ?  sanitize ( $_GET["l"] )   : NULL;

$summary    = isset( $_GET["su"] )    ? 1 : 0;
$debug      = isset( $_GET['debug'] ) ? clean_number ( sanitize( $_GET["debug"] ) ) : 0;

# Assumes we have a $start variable (set in get_context.php).
# $graph_sizes and $graph_sizes_keys defined in conf.php.  Add custom sizes there.

$size = in_array( $size, $graph_sizes_keys ) ? $size : 'default';

#
# Since the $command variable is explicitly set to an empty string, above, do we really need
# this check anymore?  --jb Jan 2008
#
# This security fix was brought to my attention by Peter Vreugdenhil <petervre@sci.kun.nl>
# Dont want users specifying their own malicious command via GET variables e.g.
# http://ganglia.mrcluster.org/graph.php?graph=blob&command=whoami;cat%20/etc/passwd
#

$metaInfo = array(
    'size' => $graph_sizes[$size],
    'start' => $start,
    'end' => $end,
    'title' => ' ');
$metricData = array();
/* If we have $graph, then a specific report was requested, such as "network_report" or
 * "cpu_report.  These graphs usually have some special logic and custom handling required,
 * instead of simply plotting a single metric.  If $graph is not set, then we are (hopefully),
 * plotting a single metric, and will use the commands in the metric.php file.
 *
 * With modular graphs, we look for a "${graph}.php" file, and if it exists, we
 * source it, and call a pre-defined function name.  The current scheme for the function
 * names is:   'graph_' + <name_of_report>.  So a 'cpu_report' would call graph_cpu_report(),
 * which would be found in the cpu_report.php file.
 *
 * These functions take the $rrdtool_graph array as an argument.  This variable is
 * PASSED BY REFERENCE, and will be modified by the various functions.  Each key/value
 * pair represents an option/argument, as passed to the rrdtool program.  Thus,
 * $rrdtool_graph['title'] will refer to the --title option for rrdtool, and pass the array
 * value accordingly.
 *
 * There are two exceptions to:  the 'extras' and 'series' keys in $rrdtool_graph.  These are
 * assigned to $extras and $series respectively, and are treated specially.  $series will contain
 * the various DEF, CDEF, RULE, LINE, AREA, etc statements that actually plot the charts.  The
 * rrdtool program requires that this come *last* in the argument string; we make sure that it
 * is put in it's proper place.  The $extras variable is used for other arguemnts that may not
 * fit nicely for other reasons.  Complicated requests for --color, or adding --ridgid, for example.
 * It is simply a way for the graph writer to add an arbitrary options when calling rrdtool, and to
 * forcibly override other settings, since rrdtool will use the last version of an option passed.
 * (For example, if you call 'rrdtool' with two --title statements, the second one will be used.)
 *
 * See $graphdir/sample.php for more documentation, and details on the
 * common variables passed and used.
 */

// No report requested, so use 'metric'
if (!$graph) {
    $graph = 'metric';
}

$graph_file = "$graphdir/$graph.php";

if (is_readable($graph_file)) {
    include_once($graph_file);
    $graph_function = "graph_${graph}";
    $metricData = $graph_function($metaInfo);  // Pass by reference call, $rrdtool_graph modified inplace
} else {
    /* Bad stuff happened. */
    error_log("Tried to load graph file [$graph_file], but failed.  Invalid graph, aborting.");
    exit();
}

# Tidy up the title a bit
switch ($context) {
    case 'meta':
        $title = "$self Grid";
        break;

    case 'cluster':
        $title  = "$clustername Cluster";
        break;

    case 'grid':
        $title  = "$gridname Grid";
        break;

    case 'host':
        if (!$summary)
            $title = null ;
        break;

    default:
        $title = $clustername;
        break;
}

if ($context != 'host' && $graph != 'mapred_report') {
    $metaInfo['title'] = $metaInfo['title'] . " last $range";
}

if (isset($title)) {
    $metaInfo['title'] = "$title " . $metaInfo['title'];
}


////--------------------------------------------------------------------------------------
/*Make sure the image is not cached*/
header ("Expires: Mon, 26 Jul 1997 05:00:00 GMT");   // Date in the past
header ("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); // always modified
header ("Cache-Control: no-cache, must-revalidate");   // HTTP/1.1
header ("Pragma: no-cache");                     // HTTP/1.0
if ($graph == "mapred_report") {
    header ("Content-type: image/gif");
  stack_graph($metaInfo, $metricData);
} else {
    header ("Content-type: image/gif");
    pChart_graph($metaInfo, $metricData);
}

?>
