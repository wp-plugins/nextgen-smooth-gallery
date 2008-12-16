<?php
$info = array();
$info["galleryID"]         = $_REQUEST["galleryID"];
$info["width"]             = $_REQUEST["width"];
$info["height"]            = $_REQUEST["height"];
$info["timed"]             = $_REQUEST["timed"];
$info["showArrows"]        = $_REQUEST["showArrows"];
$info["showCarousel"]      = $_REQUEST["showCarousel"];
$info["embedLinks"]        = $_REQUEST["embedLinks"];
$info["delay"]             = $_REQUEST["delay"];
$info["defaultTransition"] = $_REQUEST["defaultTransition"];
$info["showInfopane"]      = $_REQUEST["showInfopane"];
$info["textShowCarousel"]  = $_REQUEST["textShowCarousel"];
$info["showCarouselOpen"]  = $_REQUEST["showCarouselOpen"];
$info["margin"]            = $_REQUEST["margin"];
$info["align"]             = $_REQUEST["align"];

include "../../../wp-config.php";

?>
<html>
  <head>
    <?php nggSmoothHead(); ?>
  </head>
  
  <body>
    <?php echo nggSmoothShow($info); ?>
  </body>  
</html>