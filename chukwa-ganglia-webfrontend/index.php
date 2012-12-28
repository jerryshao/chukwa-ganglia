<?php
/* $Id: index.php 2183 2010-01-07 16:09:55Z d_pocock $ */
include_once "./eval_config.php";
include_once "./chukwa/chukwa_base.php";
# ATD - function.php must be included before get_context.php.  It defines some needed functions.
include_once "./functions.php";
include_once "./get_context.php";
include_once "./ganglia.php";
include_once "./get_ganglia.php";
include_once "./class.TemplatePower.inc.php";

# Usefull for addons.
$GHOME = ".";
if ($context == "meta" or $context == "control") {
    $title = "$self $meta_designator Report";
    include_once "./header.php";
    include_once "./meta_view.php";
} else if ($context == "cluster" or $context == "cluster-summary") {
    $title = "$clustername Cluster Report";
    include_once "./header.php";
    include_once "./cluster_view.php";
} else if ($context == "host") {
    $title = "$hostname Host Report";
    include_once "./header.php";
    include_once "./host_view.php";
} else if ($context == "job") {
    $title = "Cluster $clustername Hadoop $job_id Report";
    include_once "./header.php";
    include_once "./job_view.php";
} else {
    $title = "Unknown Context";
    print "Unknown Context Error: Have you specified a host but not a cluster?.";
}

include_once "./footer.php";

?>
