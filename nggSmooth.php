<?php
/*
Plugin Name: NextGEN Smooth Gallery
Plugin URI: http://uninuni.com/wordpress-plugin-nextgen-smooth-gallery/
Description: The amazing galery viewer from <a href="http://smoothgallery.jondesign.net/">JonDesign's SmoothGallery</a> for NextGEN Gallery.
Author: Bruno Guimaraes
Author URI: http://uninuni.com/
Version: 0.10

#################################################################

Copyright 2008 by Uninuni

If you are using this plugin, I would appreciate if you write about it on your blog/site.
Thanks =)

#################################################################
#################  GNU General Public License  ##################
#################################################################

This script is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

#################################################################
########################  Instructions  #########################
#################################################################

Simply use [smooth=galleryId] on your posts and set your default values under "Initial Values" on nggSmooth.php

You may also use [smooth=galleryId,width,height,timed,showArrows,showCarousel,embedLinks] where:
     galleryId: The id you were already using on [gallery=galleryId]
         width: Width of your image container
        height: Height of your image container
         timed: true/false to slideshow your images
    showArrows: true/false to see the arrows for next/previous images
  showCarousel: true/false to see all thumbnails
    embedLinks: true/false to open the image on a new window
  
#################################################################
##################  Upgrading Smooth Gallery  ###################
#################################################################

The current version used is 2.0

Unless the new version has major changes, you should:
  1. Replace the content of /nextgen-smooth/SmoothGallery/
  2. Look for #myGallery and change to .myGallery on all .css inside /nextgen-smooth/SmoothGallery/css/
  3. Look for: 	 title: this.galleryData[num].linkTitle
     change to:  title: this.galleryData[num].linkTitle, target: this.galleryData[num].linkTarget
     on /nextgen-smooth/SmoothGallery/scripts/jd.gallery.js

*/ 

//#################################################################
// Initial Values
   define('SMOOTH_WIDTH'       , '400');
   define('SMOOTH_HEIGHT'      , '400');
   define('SMOOTH_TIMED'       , 'false');
   define('SMOOTH_SHOWARROWS'  , 'true');
   define('SMOOTH_SHOWCAROUSEL', 'true');
   define('SMOOTH_EMBEDLINKS'  , 'true');
   
   define('BASE_URL'  , get_option('siteurl'));
   define('SMOOTH_URL', get_option('siteurl').'/wp-content/plugins/' . dirname(plugin_basename(__FILE__))); // get_bloginfo('wpurl')

//#################################################################
// Restrictions
  if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { die('You are not allowed to call this page directly.'); }

  if (!class_exists('nggallery') ) {
    add_action('admin_notices', create_function('', 'echo \'<div id="message" class="error fade"><p><strong>' . __('Sorry, NextGEN Smooth Gallery works only in Combination with NextGEN Gallery',"nggallery") . '</strong></p></div>\';'));
    return;
  }

//#################################################################
// The Real Deal

function nggSmoothDefault() {
	/* Settings for Simple Viewer */
	
	$ngg_sv_options['maxImageWidth']			  = 640;  			// Width of your largest image in pixels. Used to determine the best layout for your gallery
	$ngg_sv_options['maxImageHeight']			  = 480;				// Height of your largest image in pixels. Used to determine the best layout for your gallery. 
	$ngg_sv_options['textColor']				    = '0xFFFFFF'; // Color of title and caption text (hexidecimal color value e.g 0xff00ff). 
	$ngg_sv_options['frameColor']				    = '0xFFFFFF';	// Color of image frame, navigation buttons and thumbnail frame (hexidecimal color value e.g 0xff00ff). 
	$ngg_sv_options['frameWidth']				    = 16;				  // Width of image frame in pixels. 
	$ngg_sv_options['stagePadding']			  	= 40;				  // Distance between image and thumbnails and around gallery edge in pixels. 
	$ngg_sv_options['thumbnailColumns']			= 5;				  // Number of thumbnail rows. (To disable thumbnails completely set this value to 0.) 
	$ngg_sv_options['thumbnailRows']			  = 2;				  // Number of thumbnail columns. (To disable thumbnails completely set this value to 0.) 
	$ngg_sv_options['navPosition']				  = 'bottom';		// Position of thumbnails relative to image. Can be "top", "bottom","left" or "right".
	$ngg_sv_options['enableRightClickOpen']	= 'true';		  // Whether to display a 'Open In new Window...' dialog when right-clicking on an image. Can be "true" or "false"
	$ngg_sv_options['backgroundImagePath']	= '';				  // Relative or absolute path to a JPG or SWF to load as the gallery background.

	update_option('ngg_sv_options', $ngg_sv_options);
}

function nggSmoothFindStringBetween($text, $begin, $end) {
  if ( ($posBegin = stripos($text, $begin         )) === false) return Array($text, "");
  if ( ($posEnd   = stripos($text, $end, $posBegin)) === false) return Array($text, "");
  
  $textBegin  = substr($text, 0, $posBegin);
  $textMiddle = substr($text, $posBegin, $posEnd - $posBegin + strlen($end) );
  $textEnd    = substr($text, $posEnd + strlen($end) , strlen($text));
  
  return Array($textBegin, $textMiddle, $textEnd);
}

function  nggSmoothReplace($content) {
	global $wpdb;

  list($begin, $middle, $end) = nggSmoothFindStringBetween($content, "[smooth", "]");  
  
  if ($begin == $content) return $content;	
  
  $middleValues = str_replace(Array(" ", "]"), "", $middle);
  $middleValues = explode("=", $middleValues);
  $middleValues = explode(",", $middleValues[1]);

                   $galleryID = $wpdb->get_var("SELECT gid FROM $wpdb->nggallery WHERE gid  = '$middleValues[0]' ");
  if(! $galleryID) $galleryID = $wpdb->get_var("SELECT gid FROM $wpdb->nggallery WHERE name = '$middleValues[0]' ");
  if(! $galleryID) return $begin . $middle . $end;
  if(  $galleryID) $middle    = nggSmoothShow($galleryID, $middleValues[1], $middleValues[2], $middleValues[3], $middleValues[4], $middleValues[5], $middleValues[6]);
  
	return nggSmoothReplace($begin . $middle . $end); // More than one gallery per post
}

function nggSmoothShow($galleryID, $width, $height, $timed, $showArrows, $showCarousel, $embedLinks) {	
	global $wpdb;

  if($width             == "") $width             = SMOOTH_WIDTH;
  if($height            == "") $height            = SMOOTH_HEIGHT;
  if($timed             == "") $timed             = SMOOTH_TIMED;
  if($showArrows        == "") $showArrows        = SMOOTH_SHOWARROWS;
  if($showCarousel      == "") $showCarousel      = SMOOTH_SHOWCAROUSEL;
  if($embedLinks        == "") $embedLinks        = SMOOTH_EMBEDLINKS;
	
  // Get the pictures
  $ngg_options = get_option ('ngg_options');  
  $pictures    = $wpdb->get_results("SELECT t.*, tt.* FROM $wpdb->nggallery AS t INNER JOIN $wpdb->nggpictures AS tt ON t.gid = tt.galleryid WHERE t.gid = '$galleryID' AND tt.exclude != 1 ORDER BY tt.$ngg_options[galSort] $ngg_options[galSortDir] ");

  if (empty($pictures)) return "";

  $out = '<script type="text/javascript">
            function startGallery_'.$galleryID.'() { 
              var myGallery = new gallery($("myGallery_'.$galleryID.'"), {timed: '.$timed.', showArrows: '.$showArrows.', showCarousel: '.$showCarousel.', embedLinks: '.$embedLinks.'});
              
              document.getElementById("myGallery_'.$galleryID.'").style.display = "block";
              
              if ("'.strtolower($showCarousel).'" == "true" || "'.$showCarousel.'" == "1")
                myGallery.toggleCarousel();
              }
            window.addEvent("domready", startGallery_'.$galleryID.');
          </script>
         ';
  
    $out .= '<div style="clear:both; text-align:center;">';
    $out .= '<div style="text-align:left; margin:0 auto; width: '.$width.'px; height: '.$height.'px; border:1px solid;">';
    $out .= '<div id="myGallery_'.$galleryID.'" class="myGallery" style="display:none; text-align:left; margin:0 auto; width: '.$width.'px !important; height: '.$height.'px !important;">';
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
  $out .= ' </div></div></div>';

	return $out;  
}

function nggSmoothHead() {
  echo '<!-- begin nextgen-smooth scripts -->
          <script type="text/javascript"  src="'.SMOOTH_URL.'/SmoothGallery/scripts/mootools.v1.11.js"></script>
          <script type="text/javascript"  src="'.SMOOTH_URL.'/SmoothGallery/scripts/jd.gallery.js"></script>
          <link   type="text/css"        href="'.SMOOTH_URL.'/SmoothGallery/css/jd.gallery.css" rel="stylesheet" media="screen" />
        <!-- end nextgen-smooth scripts -->
       ';
}

// activate the options
register_activation_hook(__FILE__ ,'nggSmoothDefault');

// Replace [smooth=id,width,height] with the real galery
add_filter('the_content', 'nggSmoothReplace');
add_filter('the_excerpt', 'nggSmoothReplace');

// Hook wp_head to add css
add_action('wp_head', 'nggSmoothHead');

?>