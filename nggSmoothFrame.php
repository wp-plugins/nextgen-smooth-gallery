<?php
$galleryID         = $_REQUEST["galleryID"];
$width             = $_REQUEST["width"];
$height            = $_REQUEST["height"];
$timed             = $_REQUEST["timed"];
$showArrows        = $_REQUEST["showArrows"];
$showCarousel      = $_REQUEST["showCarousel"];
$embedLinks        = $_REQUEST["embedLinks"];
$delay             = $_REQUEST["delay"];
$defaultTransition = $_REQUEST["defaultTransition"];
$showInfopane      = $_REQUEST["showInfopane"];
$textShowCarousel  = $_REQUEST["textShowCarousel"];
$showCarouselOpen  = $_REQUEST["showCarouselOpen"];

include "../../../wp-config.php";

?>
<html>
  <head>
    <?nggSmoothHead();?>
  </head>
  
  <body>
    <?= nggSmoothShow($galleryID, $width, $height, $timed, $showArrows, $showCarousel, $embedLinks, $delay, $defaultTransition, $showInfopane, $textShowCarousel, $showCarouselOpen);?>
  </body>  
</html>