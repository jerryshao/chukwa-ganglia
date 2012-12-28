<?php
include_once (dirname(__FILE__) . "/pChart/class/pData.class.php");
include_once (dirname(__FILE__) . "/pChart/class/pDraw.class.php");
include_once (dirname(__FILE__) . "/pChart/class/pImage.class.php");
include_once ("./pChart_graph.php");

function stack_graph($metaInfo, $metricData) {
    $myData = new pData();

    foreach ($metricData as $name => $val) {
        $myData->addPoints($val, $name);
        if (isset($metaInfo['color'][$name])) {
            $myData->setPalette($name, hColor2RGB($metaInfo['color'][$name]));
        }
    }

    $myData->normalize(100, "%");

    $myPicture = new pImage($metaInfo["size"]["width"], $metaInfo["size"]["height"], $myData);


    $myPicture->Antialias = FALSE;
    $myPicture->drawRectangle(0, 0,
                              $metaInfo["size"]["width"] - 1,
                              $metaInfo["size"]["height"] - 1,
                              isset($metaInfo['color'])
                              ? hColor2RGB($metaInfo["bg_color"])
                              : Array("R" => 0, "G" => 0, "B" => 0));

    $myPicture->setFontProperties(
        array("FontName" => dirname(__FILE__) . "/pChart/fonts/pf_arma_five.ttf", "FontSize" => 6));
    $myPicture->drawText($metaInfo["size"]["width"] /2,
                         2,
                         $metaInfo["title"],
                         array("FontSize" => 8,"Align" => TEXT_ALIGN_TOPMIDDLE));

    $myPicture->setFontProperties(
        array("FontName" => dirname(__FILE__) . "/pChart/fonts/pf_arma_five.ttf", "FontSize" => 6));

    $myPicture->setGraphArea(20, 30, $metaInfo["size"]["width"] - 10, $metaInfo["size"]["height"] - 40);

    $scaleSettings = array("XMargin" => 10,
                           "YMargin" => 0,
                           "LabelSkip" => 6,
                           "Floating" => TRUE,
                           "GridR" => 200,
                           "GridG" => 200,
                           "GridB" => 200,
                           "DrawSubTicks" => TRUE,
                           "CycleBackground" => TRUE,
                           "Mode" => SCALE_MODE_ADDALL_START0,
                           "Pos" => SCALE_POS_TOPBOTTOM);
    $myPicture->drawScale($scaleSettings);

    $myPicture->Antialias = TRUE;

    $myPicture->drawStackedBarChart();

    $myPicture->drawLegend(20,
                           $metaInfo["size"]["height"] - 20,
                           array("Style" => LEGEND_NOBORDER, "Mode" => LEGEND_HORIZONTAL));

    $myPicture->autoOutput("simple.png");
}

?>
