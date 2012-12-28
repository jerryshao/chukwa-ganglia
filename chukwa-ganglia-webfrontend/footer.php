<?php
/* $Id: footer.php 1679 2008-08-14 04:15:17Z carenas $ */
$tpl = new TemplatePower( template("footer.tpl") );
$tpl->prepare();
$tpl->assign("webfrontend-version",$version["webfrontend"]);

$tpl->assign("templatepower-version", $tpl->version);
$tpl->printToScreen();
?>
