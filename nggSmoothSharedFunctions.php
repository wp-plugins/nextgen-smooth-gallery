<?php

function nggSmoothHead() {
  echo '<!-- begin nextgen-smooth scripts -->
          <script type="text/javascript"  src="'.SMOOTH_URL.'/SmoothGallery/scripts/mootools.v1.11.js"></script>
          <script type="text/javascript"  src="'.SMOOTH_URL.'/SmoothGallery/scripts/jd.gallery.js"></script>
          <script type="text/javascript"  src="'.SMOOTH_URL.'/SmoothGallery/scripts/jd.gallery.transitions.js"></script>          
          <link   type="text/css"        href="'.SMOOTH_URL.'/SmoothGallery/css/jd.gallery.css" rel="stylesheet" media="screen" />
        <!-- end nextgen-smooth scripts -->
       ';
}

function nggSmoothAlign($align, $margin, $who="") {
  if ($who == "iframe") {
    switch ($align) {
      case "left"       : $align = "text-align:left;";                   break;
      case "right"      : $align = "text-align:right;";                  break;
      case "center"     : $align = "text-align:center;";                 break;
      case "float_left" : $align = "float:left;  margin:".$margin."px;"; break;
      case "float_right": $align = "float:right; margin:".$margin."px;"; break;
    }
  } else {
    switch ($align) {
      case "left"       : $align = "margin:0px auto 0px 0px;";           break;
      case "right"      : $align = "margin:0px 0px 0px auto;";           break;
      case "center"     : $align = "margin:0px auto;";                   break;
      case "float_left" : $align = "float:left;  margin:".$margin."px;"; break;
      case "float_right": $align = "float:right; margin:".$margin."px;"; break;
    }
  }
  
  return $align;
}

function nggSmoothShow($galleryID, $width, $height, $timed, $showArrows, $showCarousel, $embedLinks, $delay, $defaultTransition, $showInfopane, $textShowCarousel, $showCarouselOpen, $margin, $align) {	
  global $wpdb;
  
  $galleryID         =  (int)    $galleryID;
  $width             =  (int)    $width;
  $height            =  (int)    $height;
  $timed             = ((int)    $timed           ?'true':'false');    
  $showArrows        = ((int)    $showArrows      ?'true':'false');    
  $showCarousel      = ((int)    $showCarousel    ?'true':'false');    
  $embedLinks        = ((int)    $embedLinks      ?'true':'false');    
  $delay             =  (int)    $delay;
  $defaultTransition =  (string) $defaultTransition;
  $showInfopane      = ((int)    $showInfopane    ?'true':'false');  
  $textShowCarousel  =  (string) $textShowCarousel; 
  $showCarouselOpen  = ((int)    $showCarouselOpen?'true':'false');
  $margin            =  (int)    $margin; 
  $align             =  (string) $align;

  // print_r("$galleryID, $width, $height, $timed, $showArrows, $showCarousel, $embedLinks, $delay, $defaultTransition, $showInfopane, $textShowCarousel, $showCarouselOpen");    
  
  // Get the pictures
  $ngg_options = get_option ('ngg_options');  
  $pictures    = $wpdb->get_results("SELECT t.*, tt.* FROM $wpdb->nggallery AS t INNER JOIN $wpdb->nggpictures AS tt ON t.gid = tt.galleryid WHERE t.gid = '$galleryID' AND tt.exclude != 1 ORDER BY tt.$ngg_options[galSort] $ngg_options[galSortDir] ");

  if (empty($pictures)) return "";
  
  // Gather pictures and Smooth Gallery
  $out = '<script type="text/javascript">
            function startGallery_'.$galleryID.'() { 
              var myGallery = new gallery($("myGallery_'.$galleryID.'"), {  '; // Leave a blank space in case there is no last comma to be removed above
              
  $out .= " timed: $timed,";
  if ($timed == 'true') { 
    if ($delay             != 0 ) $out .= " delay: $delay,";
    if ($defaultTransition != "") $out .= " defaultTransition: \"$defaultTransition\",";
  }
  
  $out .= " showCarousel: $showCarousel,";
  if ($showCarousel == 'true') {                                   
    if ($textShowCarousel  != "") $out .= " textShowCarousel: \"$textShowCarousel\",";
  }
  $out .= " showInfopane: $showInfopane,";
  $out .= "   showArrows: $showArrows,";
  $out .= "   embedLinks: $embedLinks,";

  $out = substr($out, 0, -1); // Remove last comma
  $out .= '   });
              
              document.getElementById("myGallery_'.$galleryID.'").style.display = "block";
              
              if ("'.$showCarouselOpen.'" == "true")
                myGallery.toggleCarousel();
              }
            window.addEvent("domready", startGallery_'.$galleryID.');
          </script>
         ';

  $out .= '<div style="width: '.$width.'px; height: '.$height.'px; border:1px solid; '.nggSmoothAlign($align, $margin).' clear:both;">'; // margin centers div and clear makes it work like a container 
  $out .= '<div id="myGallery_'.$galleryID.'" class="myGallery" style="display:none; width: '.$width.'px !important; height: '.$height.'px !important;">';

  foreach ($pictures as $picture) {
    $out .= ' <div class="imageElement">
                <h3>'.$picture->title.'</h3>
                <p>'.$picture->description.'</p>
                <a target="_blank" href="'.BASE_URL."/".$picture->path."/".$picture->filename.'" title="open image" class="open"></a>
                <img src="'.BASE_URL."/".$picture->path."/".$picture->filename.'" class="full" />
                <img src="'.BASE_URL."/".$picture->path."/thumbs/thumbs_".$picture->filename.'" class="thumbnail" />
              </div>
            ';
  }
  $out .= ' </div></div>'; //</div>';

  return $out;  
}

?>