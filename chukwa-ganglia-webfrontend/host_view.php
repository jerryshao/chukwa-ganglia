<?php
/* $Id: host_view.php 2203 2010-01-08 17:25:32Z d_pocock $ */
$tpl = new TemplatePower( template("host_view.tpl") );
$tpl->assignInclude("extra", template("host_extra.tpl"));
$tpl->prepare();

$tpl->assign("cluster", $clustername);
$tpl->assign("host", $hostname);
$tpl->assign("node_image", node_image($metrics));
$tpl->assign("sort",$sort);
$tpl->assign("range",$range);
if($hosts_up)
    $tpl->assign("node_msg", "This host is up and running.");
else
    $tpl->assign("node_msg", "This host is down.");

$cluster_url=rawurlencode($clustername);
$tpl->assign("cluster_url", $cluster_url);
$tpl->assign("graphargs", "h=$hostname&amp;$get_metric_string&amp;st=$cluster[LOCALTIME]&amp;cs=$cs&amp;ce=$ce");

# For the node view link.
$tpl->assign("node_view","./?p=2&amp;c=$cluster_url&amp;h=$hostname");

# No reason to go on if this node is down.
if ($hosts_down) {
      $tpl->printToScreen();
      return;
}

$tpl->assign("ip", $hosts_up['IP']);
$tpl->newBlock('columns_dropdown');
$tpl->assign("metric_cols_menu", $metric_cols_menu);
$g_metrics_group = array();
foreach ($metrics as $name => $v) {
    if ($v['TYPE'] == "string" or $v['TYPE']=="timestamp" or
       (isset($always_timestamp[$name]) and $always_timestamp[$name])) {
        $s_metrics[$name] = $v;
    } elseif (isset($always_constant[$name]) and $always_constant[$name]) {
        $c_metrics[$name] = $v;
    } else if (isset($reports[$name]) and $reports[$metric]) {
        continue;
    } else {
        $graphargs = "c=$cluster_url&amp;h=$hostname&amp;"
            ."&amp;m=$name&amp;r=$range&amp;z=medium&amp;jr=$jobrange"
            ."&amp;js=$jobstart&amp;st=$cluster[LOCALTIME]&amp;cs=$cs&amp;ce=$ce";

        if ($v['UNITS']) {
            $encodeUnits = rawurlencode($v['UNITS']);
            $graphargs .= "&vl=$encodeUnits";
        }

        if (isset($v['TITLE'])) {
            $title = $v['TITLE'];
            $graphargs .= "&ti=$title";
        }

        $g_metrics[$name]['graph'] = $graphargs;
        $g_metrics[$name]['description'] = '';
    }
}


# Add the uptime metric for this host. Cannot be done in ganglia.php,
# since it requires a fully-parsed XML tree. The classic contructor problem.
$s_metrics['uptime']['TYPE'] = "string";
$s_metrics['uptime']['VAL'] = uptime($metrics['boottime']['VAL'] / 1000);
$s_metrics['uptime']['TITLE'] = "Uptime";

$s_metrics['last_reported']['TYPE'] = "string";
$s_metrics['last_reported']['VAL'] = uptime($cluster['LOCALTIME'] -
        $metrics['boottime']['LASTUPDATE']);
$s_metrics['last_reported']['TITLE'] = "Last Reported";

# Show string metrics
if (is_array($s_metrics)) {
    ksort($s_metrics);
    foreach ($s_metrics as $name => $v ) {
        # RFM - If units aren't defined for metric, make it be the empty string
	    ! array_key_exists('UNITS', $v) and $v['UNITS'] = "";
        $tpl->newBlock("string_metric_info");
		if (isset($v['TITLE'])) {
			$tpl->assign("name", $v['TITLE']);
		} else {
            $tpl->assign("name", $name);
        }

        if( $v['TYPE']=="timestamp" or (isset($always_timestamp[$name]) and $always_timestamp[$name])) {
             $tpl->assign("value", date("r", $v['VAL'] / 1000));
         } else {
              $tpl->assign("value", $v['VAL'] . " " . $v['UNITS']);
         }
    }
}

# Show constant metrics.
if (is_array($c_metrics)) {
    ksort($c_metrics);
    foreach ($c_metrics as $name => $v ) {
        $tpl->newBlock("const_metric_info");
        if (isset($v['TITLE'])) {
   	        $tpl->assign("name", $v['TITLE']);
        } else {
   	        $tpl->assign("name", $name);
        }
        $d = $v['VAL'] / 1000;
        $tpl->assign("value", "$d $v[UNITS]");
    }
}

# Show graphs.
if (is_array($g_metrics)) {
    if ($group == "") {
        $group = "default_group";
    }

    $tpl->newBlock("vol_group_info");
    $tpl->assign("group", $group);
    $tpl->assign("group_metric_count", count($g_metrics));
    $i = 0;
    ksort($g_metrics);
    foreach ( $g_metrics as $name => $v ) {
        $tpl->newBlock("vol_metric_info");
        $tpl->assign("graphargs", $v['graph']);
        $tpl->assign("alt", "$hostname $name");
        if (isset($v['description']))
          $tpl->assign("desc", $v['description']);
        if ( !(++$i % $metriccols) && ($i != $c) )
           $tpl->assign("new_row", "</TR><TR>");
   }
}

$tpl->printToScreen();
?>
