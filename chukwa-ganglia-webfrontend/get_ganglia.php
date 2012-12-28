<?php
# $Id: get_ganglia.php 589 2005-10-10 07:42:26Z knobi1 $
# Retrieves and parses the XML output from gmond. Results stored
# in global variables: $clusters, $hosts, $hosts_down, $metrics.
# Assumes you have already called get_context.php.
#
if (!get_hbase_metainfo()) {
    print "<H4>There was an error collecting HBase data ".
       "($thrift_ip:$thrift_port)</H4>\n";
    exit;
}
?>
