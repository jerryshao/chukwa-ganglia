<TABLE BORDER="0" CELLSPACING=5 WIDTH="100%">
<TR>
  <TD CLASS=title COLSPAN="2">
  <FONT SIZE="+1">Overview of {cluster}</FONT>
  </TD>
</TR>

<TR>
<TD ALIGN=left VALIGN=top>
<table cellspacing=1 cellpadding=1 width="100%" border=0>
 <tr><td>Job Name:</td><td align=left><B>{job_name}</B></td></tr>
 <tr><td width="60%">Job submit time:</td><td align=left><B>{job_submit_time}</B></td></tr>
 <tr><td width="60%">Job launch time:</td><td align=left><B>{job_launch_time}</B></td></tr>
 <tr><td width="60%">Job finish time:</td><td align=left><B>{job_finish_time}</B></td></tr>
 <tr><td width="60%">Job Maps num:</td><td align=left><B>{job_map_num}</B></td></tr>
 <tr><td width="60%">Job Reduces num:</td><td align=left><B>{job_reduce_num}</B></td></tr>
 </table>
<!-- INCLUDE BLOCK : extra -->
 <hr>
</TD>

<TD ROWSPAN=2 ALIGN="CENTER" VALIGN=top>
<A HREF="./graph.php?g=load_report&amp;z=large&amp;{graph_args}">
<IMG BORDER=0 ALT="{cluster} LOAD"
   SRC="./graph.php?g=load_report&amp;z=medium&amp;{graph_args}">
</A>
<A HREF="./graph.php?g=cpu_report&amp;z=large&amp;{graph_args}">
<IMG BORDER=0 ALT="{cluster} CPU"
   SRC="./graph.php?g=cpu_report&amp;z=medium&amp;{graph_args}">
</A>
<A HREF="./graph.php?g=mem_report&amp;z=large&amp;{graph_args}">
<IMG BORDER=0 ALT="{cluster} MEM"
   SRC="./graph.php?g=mem_report&amp;z=medium&amp;{graph_args}">
</A>
<A HREF="./graph.php?g=network_report&amp;z=large&amp;{graph_args}">
<IMG BORDER=0 ALT="{cluster} NETWORK"
    SRC="./graph.php?g=network_report&amp;z=medium&amp;{graph_args}">
</A>
<A HREF="./graph.php?g=disk_report&amp;z=large&amp;{graph_args}">
<IMG BORDER=0 ALT="{cluster} DISK"
    SRC="./graph.php?g=disk_report&amp;z=medium&amp;{graph_args}">
</A>
<A HREF="./graph.php?g=mapred_report&amp;z=large&amp;{graph_args}">
<IMG BORDER=0 ALT="{cluster} MAPRED"
    SRC="./graph.php?g=mapred_report&amp;z=medium&amp;{graph_args}">
</A>

<!-- START BLOCK : optional_graphs -->
<A HREF="./graph.php?g={name}_report&amp;z=large&amp;{graph_args}">
<IMG BORDER=0 ALT="{cluster} {name}" SRC="./graph.php?g={name}_report&amp;z=medium&amp;{graph_args}">
</A>
<!-- END BLOCK : optional_graphs -->
</TD>
</TR>


<TABLE BORDER="0" WIDTH="100%">
</TABLE>

<CENTER>
<TABLE>
</TABLE>

<p>
<!-- START BLOCK : node_legend -->
(Nodes colored by 1-minute load) | <A HREF="./node_legend.html">Legend</A>
<!-- END BLOCK : node_legend -->

</CENTER>
