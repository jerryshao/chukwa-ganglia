<?php
/* $Id: meta_view.php 1710 2008-08-21 16:44:54Z bernardli $ */
$tpl = new TemplatePower( template("meta_view.tpl") );
$tpl->prepare();

$source_names = array_keys($grid);
# Build a list of cluster names and randomly pick a smaller subset to
# display for control room mode.  This allows a dedicated host to
# eventually cycle through all the graphs w/o scrolling the mouse.  A bunch
# of these stations could monitor a large grid.
#
# For the standard meta view still display all the hosts.

if ( $context == "control" ) {
    srand((double)microtime()*1000000);
    shuffle($source_names);
    $subset = array_slice($source_names, 0, abs($controlroom));
    $source_names = $subset;
}


foreach( $source_names as $c) {
    $data = $chukwa->getMetrics($cluster_table, $c, "cpu_num", time() - 60, time(),
            "long", $time_ranges['hour'], 1);
    $cpucount = isset($data['cpu_num']) ? current($data['cpu_num']) : 1;

    $data = $chukwa->getMetrics($cluster_table, $c, "load_one", time() - 60, time(),
            "double", $time_ranges['hour'], $grid[$c]['HOSTS_UP']);
    $load_one = isset($data['load_one']) ? current($data['load_one']) : 0;
    $value = (double) ($load_one / $cpucount);
    $sorted_sources[$c] = $value;
    $values[$c] = $value;
    isset($total_load) or $total_load = 0;
    $total_load += $value;
}

if ($sort == "descending") {
    $sorted_sources[$self] = 999999999;
    arsort($sorted_sources);
} else if ($sort == "by name") { # SORT HACK to keep $self first; see below:
    $sorted_sources["AAAAA.$self"] = $sorted_sources[$self];
    unset($sorted_sources[$self]);
    ksort($sorted_sources);
} else if ($sort == "by hosts up") {
    foreach ($sorted_sources as $source => $val) {
        $sorted_sources[$source] = intval($grid[$source]['HOSTS_UP']);
    }
    $sorted_sources[$self] = 999999999;
    arsort($sorted_sources);
} else if ($sort == "by hosts down") {
    foreach ($sorted_sources as $source => $val) {
        $sorted_sources[$source] = intval($grid[$source]['HOSTS_DOWN']);
    }
    $sorted_sources[$self] = 999999999;
    arsort($sorted_sources);
} else {
    $sorted_sources[$self] = -1;
    asort($sorted_sources);
}

foreach ($sorted_sources as $source => $val) {
    # XXX: SORT HACK to keep $self first; see above
    if ($source == "AAAAA.$self") {
        $source = $self;
    }
    $m = $metrics[$source];
    $sourceurl = rawurlencode($source);
    if (isset($grid[$source]['GRID']) and $grid[$source]['GRID']) {
        $localtime = $grid[$source]['LOCALTIME'];
        # Is this the our own grid?
        if ($source==$self) {
            # Negative control room values means dont display grid summary.
            if ($controlroom < 0) continue;
            $num_sources = count($sorted_sources) - 1;
            $name = "$self $meta_designator ($num_sources sources)";
            $graph_url = "me=$sourceurl&amp;$get_metric_string&amp;cs=$cs&amp;ce=$ce";
            $url = "./?$get_metric_string&amp;cs=$cs&amp;ce=$ce";
        } else {
            # Set grid context.
            $name = "$source $meta_designator";
            $graph_url = "G=$sourceurl&amp;$get_metric_string&amp;st=$localtime&amp;cs=$cs&amp;ce=$ce";
            $authority = $grid[$source]['AUTHORITY'];
            $url = "$authority?gw=fwd&amp;gs=$gridstack_url&amp;cs=$cs&amp;ce=$ce";
        }
        $class = "grid";
    } else {
        # Set cluster context.
        $name = $source;
        $localtime = $grid[$source]['LOCALTIME'];
        $graph_url = "c=$sourceurl&amp;$get_metric_string&amp;st=$localtime&amp;cs=$cs&amp;ce=$ce";
        $url = "./?c=$sourceurl&amp;$get_metric_string&amp;cs=$cs&amp;ce=$ce";
        $class = "cluster";
    }

    $data = $chukwa->getMetrics($cluster_table, $source, "cpu_num", time() - 60, time(),
        "long", $time_ranges['hour'], 1);
    $cpu_num = isset($data['cpu_num']) ? current($data['cpu_num']) : 1;

    $data = $chukwa->getMetrics($cluster_table, $source, "load_one", time() - 60, time(),
            "double", $time_ranges['hour'], $grid[$source]['HOSTS_UP']);
    $cluster_load1 = isset($data['load_one']) ? current($data['load_one']) : 0;

    $data = $chukwa->getMetrics($cluster_table, $source, "load_five", time() - 60, time(),
            "double", $time_ranges['hour'], $grid[$source]['HOSTS_UP']);
    $cluster_load5 = isset($data['load_five']) ? current($data['load_five']) : 0;

    $data = $chukwa->getMetrics($cluster_table, $source, "load_fifteen", time() - 60, time(),
            "double", $time_ranges['hour'], $grid[$source]['HOSTS_UP']);
    $cluster_load15 = isset($data['load_fifteen']) ? current($data['load_fifteen']) : 0;
	
    $cluster_load15 = (intval($cluster_load15 * 100)) / 100;
    $cluster_load5 = (intval($cluster_load5 * 100)) / 100;
    $cluster_load1 = (intval($cluster_load1 * 100)) / 100;
    $cluster_load = "$cluster_load15%, $cluster_load5%, $cluster_load1%";

    $tpl->newBlock ("source_info");
    $tpl->assign("name", $name);
    $tpl->assign("cpu_num", $cpu_num);
    $tpl->assign("url", $url);
    $tpl->assign("class", $class);
    if (isset($num_sources))
        $tpl->assign("Sources: $num_sources");

    $tpl->assign("alt_view", "<FONT SIZE=\"-2\">$alt_url</FONT>");
    # Each block has a different namespace, so we need to redefine variables.
    $tpl->newBlock("public");
    if ($localtime)
        $tpl->assign("localtime",  "<font size=-1>Localtime:</font><br>&nbsp;&nbsp;"
          . date("Y-m-d H:i", $localtime) );
    if ($cluster_load)
        $tpl->assign("cluster_load", "<font size=-1>Avg Load (15, 5, 1m):</font>"
          ."<br>&nbsp;&nbsp;$cluster_load");
    $tpl->assign("cpu_num", $cpu_num);
    $tpl->assign("num_nodes", $grid[$source]["HOSTS_UP"] );
    $tpl->assign("num_dead_nodes", $grid[$source]["HOSTS_DOWN"] );
    $tpl->assign("range", $range);
    $tpl->assign("name", $name);
    $tpl->assign("url", $url);
    $tpl->assign("graph_url", $graph_url);
    if(isset($base64img)) {
        $tpl->assign("base64img", $base64img);
    }
}

# Show load images.
if ($show_meta_snapshot=="yes") {
    $tpl->newBlock("show_snapshot");
    $tpl->assign("self", "$self $meta_designator");

    foreach ($sorted_sources as $c=>$value) {
        if ($c==$self) continue;
        if ($c=="AAAAA.$self") continue;  # SORT HACK; see above
        $names[]=$c;

        if (isset($grid[$c]['GRID']) and $grid[$c]['GRID'])
            $image = load_image("grid", $values[$c]);
        else
            $image = load_image("cluster", $values[$c]);
        $Images[]=$image;
    }

    # All this fancyness is to get the Cluster names
    # above the image. Not easy with template blocks.
    $cols=5;
    $i = 0;
    $count=count($names);
    while ($i < $count) {
        $snapnames = "";
        $snapimgs = "";
        $tpl->newBlock("snap_row");
        foreach (range(0, $cols-1) as $j){
            $k = $i + $j;
            if ($k >= $count) break;
            $n = $names[$k];
            $snapnames .= "<td valign=bottom align=center><b>$n</b></td>\n";
            $snapimgs .= "<td valign=top align=center>";
            if (isset($grid[$n]['GRID']) and $grid[$n]['GRID'])
                $snapimgs .= "<a href=\"" . $grid[$n]['AUTHORITY'] ."?gw=fwd&amp;gs=$gridstack_url\">";
            else {
                $nameurl = rawurlencode($n);
                $snapimgs .= "<a href=\"./?c=$nameurl&amp;$get_metric_string\">";
            }
            $snapimgs .= "<img src=$Images[$k] border=0 align=top></a></td>\n";
        }
        $tpl->assign("names", $snapnames);
        $tpl->assign("images", $snapimgs);
        $i += $cols;
    }
}

$tpl->printToScreen();
?>
