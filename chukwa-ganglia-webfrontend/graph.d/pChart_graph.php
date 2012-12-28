<?php

 /* pChart library inclusions */
 include_once (dirname(__FILE__) . "/pChart/class/pData.class.php");
 include_once (dirname(__FILE__) . "/pChart/class/pDraw.class.php");
 include_once (dirname(__FILE__) . "/pChart/class/pImage.class.php");

function hColor2RGB($hexColor) {
    $color = str_replace('#', '', $hexColor);
    if (strlen($color) > 3) {
        $rgb = array(
            'R' => hexdec(substr($color, 0, 2)),
            'G' => hexdec(substr($color, 2, 2)),
            'B' => hexdec(substr($color, 4, 2)),
            'Alpha' => 200
        );
     } else {
        $color = str_replace('#', '', $hexColor);
        $r = substr($color, 0, 1) . substr($color, 0, 1);
        $g = substr($color, 1, 1) . substr($color, 1, 1);
        $b = substr($color, 2, 1) . substr($color, 2, 1);
        $rgb = array(
            'R' => hexdec($r),
            'G' => hexdec($g),
            'B' => hexdec($b),
            'Alpha' => 80
        );
    }
     return $rgb;
}

function pChart_graph($metaInfo, $metricData) {

    global $range, $time_ranges;
    $myData = new pData();

    $key = null;
    foreach ($metaInfo['order'] as $name) {
        $myData->addPoints(array_values($metricData[$name]), $name);
        if (isset($metaInfo['color'][$name])) {
            $myData->setPalette($name, hColor2RGB($metaInfo['color'][$name]));
        }
        $myData->setSerieWeight($name, 0);
        $key = $name;
    }

    $myData->setAxisName(0, $metaInfo["vertical-label"]);

    $timeArray = Array();
    $i = 30;
    $count = 0;
    switch ($range) {
        case 'hour':
            foreach (array_keys($metricData[$key]) as $t) {
                $timeArray[] = date("H:i", $t);
            }
            break;
        case 'day':
            foreach (array_keys($metricData[$key]) as $t) {
                $timeArray[] = date("D H:i", $t);
            }
            break;
        case 'week':
        case 'month':
            foreach (array_keys($metricData[$key]) as $t) {
                $timeArray[] = date("D d", $t);
            }
            break;
        case 'year':
            foreach (array_keys($metricData[$key]) as $t) {
                $timeArray[] = date("M", $t);
            }
            break;
        default:
            foreach (array_keys($metricData[$key]) as $t) {
                $timeArray[] = date("H:i", $t);
            }
            break;
    }

    $myData->addPoints($timeArray, "Labels");
    $myData->setSerieDescription("Labels", "time");
    $myData->setAbscissa("Labels");


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

    $myPicture->setGraphArea(45, 20, $metaInfo["size"]["width"] - 10, $metaInfo["size"]["height"] - 40);


    $scaleSettings = array("XMargin" => 0,
                           "YMargin" => 0,
                           "LabelSkip" => $i,
                           "Floating" => TRUE,
                           "GridR" => 200,
                           "GridG" => 200,
                           "GridB" => 200,
                           "DrawSubTicks" => TRUE,
                           "CycleBackground" => TRUE,
                           "Mode" => SCALE_MODE_ADDALL_START0);
    $myPicture->drawScale($scaleSettings);

    $myPicture->Antialias = TRUE;

    //draw only line using line chart
    foreach (array_keys($metricData) as $k) {
        if ($metaInfo['style'][$k] == "line") {
            $myData->setSerieDrawable($k, TRUE);
        } else {
            $myData->setSerieDrawable($k, FALSE);
        }
    }
    $myPicture->drawLineChart();

    //draw the left using area chart
    foreach (array_keys($metricData) as $k) {
        if ($metaInfo['style'][$k] == "line") {
            $myData->setSerieDrawable($k, FALSE);
        } else {
            $myData->setSerieDrawable($k, TRUE);
        }
    }
    $myPicture->drawStackedAreaChart();


    //set all the serie to true to draw legend
    foreach (array_keys($metricData) as $k) {
        $myData->setSerieDrawable($k, TRUE);
    }

    $myPicture->drawLegend(20,
                           $metaInfo["size"]["height"] - 20,
                           array("Style" => LEGEND_NOBORDER, "Mode" => LEGEND_HORIZONTAL));

    $myPicture->autoOutput("simple.png");
}
?>
