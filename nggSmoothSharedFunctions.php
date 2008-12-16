<?php
//#################################################################
// Initial Values
  $data_ngs_default = array("width"             => 400,
                            "height"            => 400,
                            "timed"             => 0,
                            "showArrows"        => 1,
                            "showCarousel"      => 1,
                            "embedLinks"        => 1,
                            "use_frames"        => 0,
                            "delay"             => 9000,
                            "defaultTransition" => "fade", // fadeslideleft, continuoushorizontal, continuousvertical, crossfade, fadebg 
                            "showInfopane"      => 0,
                            "textShowCarousel"  => "Pictures",
                            "showCarouselOpen"  => 1,
                            "gal_code"          => "",
                            "margin"            => 8,
                            "align"             => "center"); // ngs - NextGen Smooth

  add_option('dataNextGenSmooth', $data_ngs_default, 'Data from NextGen Smooth Gallery');
  $data_ngs = get_option('dataNextGenSmooth');
  
  define('BASE_URL'  , get_option('siteurl'));
  define('SMOOTH_URL', get_option('siteurl').'/wp-content/plugins/' . dirname(plugin_basename(__FILE__))); // get_bloginfo('wpurl')

//#################################################################

function nggSmoothHeadAdmin() { ?>
  <!-- begin nextgen-smooth admin scripts -->
    <style>    
      fieldset {
        border:1px solid #DFDFDF;
        background:#fff;
        -moz-border-radius-bottomleft:6px;
        -moz-border-radius-bottomright:6px;
        -moz-border-radius-topleft:6px;
        -moz-border-radius-topright:6px;      
      }
      
      legend {
        font-weight:bold;
        padding:0px 6px;
      }    
    </style>
  <!-- end nextgen-smooth admin scripts -->    
  <?php  
  nggSmoothHead();
}

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

function nggSmoothShow($info, $pictures = null) {	
  global $wpdb, $data_ngs_default;  
  
  $info = array_merge( $data_ngs_default, $info );
  
  extract($info);

  // Get the pictures
  if ($galleryID) {
    $ngg_options = get_option ('ngg_options');  
    $pictures    = $wpdb->get_results("SELECT t.*, tt.* FROM $wpdb->nggallery AS t INNER JOIN $wpdb->nggpictures AS tt ON t.gid = tt.galleryid WHERE t.gid = '$galleryID' AND tt.exclude != 1 ORDER BY tt.$ngg_options[galSort] $ngg_options[galSortDir] ");
               
    $final = array();    
    foreach($pictures as $picture) {
      $aux = array();
      $aux["title"] = $picture->alttext; // $picture->alttext;
      $aux["desc"]  = $picture->description;
      $aux["link"]  = BASE_URL . "/" . $picture->path ."/" . $picture->filename;
      $aux["img"]   = BASE_URL . "/" . $picture->path ."/" . $picture->filename;
      $aux["thumb"] = BASE_URL . "/" . $picture->path ."/thumbs/thumbs_" . $picture->filename;
      
      $final[] = $aux;
    }
    
    $pictures = $final;
    
  } else {
    $galleryID = rand();
  }
  
  if (empty($pictures)) return "";
  
  // Gather pictures and Smooth Gallery
  $out = '<script type="text/javascript">
            function startGallery_'.$galleryID.'() { 
              var myGallery = new gallery($("myGallery_'.$galleryID.'"), {  '; // Leave a blank space in case there is no last comma to be removed later
              
  $out .= "                timed: " . ($timed       ?'true':'false') . ",";
  $out .= "         showCarousel: " . ($showCarousel?'true':'false') . ",";
  $out .= "         showInfopane: " . ($showInfopane?'true':'false') . ",";
  $out .= "           showArrows: " . ($showArrows  ?'true':'false') . ",";
  $out .= "           embedLinks: " . ($embedLinks  ?'true':'false') . ",";
  $out .= " slideInfoZoneOpacity: 0.80,";

  if ($timed) { 
    if ($delay)             $out .= " delay: $delay,";
    if ($defaultTransition) $out .= " defaultTransition: \"$defaultTransition\",";
  }
  
  if ($showCarousel)
    if ($textShowCarousel)
      $out .= " textShowCarousel: \"$textShowCarousel\",";
    
  $out = substr($out, 0, -1); // Remove last comma
  $out .= '   });
              
              document.getElementById("myGallery_'.$galleryID.'").style.display = "block";
          ';

  if ($showCarousel && $showCarouselOpen)
    $out .= ' myGallery.toggleCarousel(); ';

  $out .= ' }
            window.addEvent("domready", startGallery_'.$galleryID.');
          </script>
         ';
/*
  $out .= " <style>
              .jdGallery .slideInfoZone {
                height: 78px;
              }

              .jdGallery .slideInfoZone h2 {
                  font-size: 1.5em;
                font-family: Arial;
                font-weight: bold;
                     margin: 2px 4px;
                      color: #FFF000;
              }

              .jdGallery .slideInfoZone p {
                  font-size: 2.0em;
                font-family: Arial;
                font-weight: bold;  
                     margin: 0px 8px;
                      color: #FFFFFF;
                line-height: 1.0;
              }
            </style>";
*/

  $out .= '<div style="width: '.$width.'px; height: '.$height.'px; border:0px solid; '.nggSmoothAlign($align, $margin).' clear:both;">'; // margin centers div and clear makes it work like a container 
  $out .= '<div id="myGallery_'.$galleryID.'" class="myGallery" style="display:none; width: '.$width.'px !important; height: '.$height.'px !important;">';
    
  // Error with only one element
  foreach ($pictures as $picture)
    if ($picture["img"]) {
      $out .= "<div class=\"imageElement\">";
      $out .= "  <h3> " . $picture["title"] . "</h3>";
      $out .= "  <p style=\"color: #FFF000;\"> "  . $picture["desc"]  . "</p>";
      $out .= "  <a target=\"_blank\" href=\"" . $picture["link"] . "\" title=\"open image\" class=\"open\"></a>";
      $out .= "  <img src=\"" . $picture["img"]   . "\" class=\"full\" />";
      $out .= "  <img src=\"" . $picture["thumb"] . "\" class=\"thumbnail\" />";      
      $out .= "</div>";    
    }

  $out .= ' </div></div>';

  return $out;  
}

?>