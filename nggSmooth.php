<?php
/*
Plugin Name: NextGEN Smooth Gallery
Plugin URI: http://uninuni.com/wordpress-plugin-nextgen-smooth-gallery/
Description: The amazing galery viewer from <a href="http://smoothgallery.jondesign.net/">JonDesign's SmoothGallery</a> for NextGEN Gallery.
Author: Bruno Guimaraes
Author URI: http://uninuni.com/
Version: 1.0

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

Set your default values on the admin page and use [smooth=id] on your posts.

If you want a specific gallery to display another configuration, you can use
  [smooth=id:; width:; height:; timed:; delay:; transition:; arrows:; info:; carousel:; text:; open:; links:;]

  Example: [smooth=id:4; timed:false; arrows:true; carousel:true; links:true; width:500; height:300;]

            id: The id you were already using on [gallery=galleryId]
         width: Width of your image container
        height: Height of your image container
         timed: true/false to slideshow your images
         delay: Time in miliseconds before moving to the next image
    transition: Animation when moving to the next image: fade, fadeslideleft, continuoushorizontal, continuousvertical, crossfade, fadebg 
        arrows: true/false to see the arrows for next/previous images
          info: Shows the description for each image
      carousel: true/false to see all thumbnails
          text: Text relative to the Carousel
          open: Carousel is opened/closed
         links: true/false to click on the image and open the original image alone
    
For compatibility with prior versions, you can also use (Not recomended / Deprecated):
  [smooth=id, width, height, timed, arrows, carousel, links]
  
  Example: [smooth=4, 500, 300, false, true, true, true]
  
#####################################################################
##############  Upgrading JonDesign's SmoothGallery  ################
#####################################################################

The current version used is 2.0

Unless the new version has major changes, you should:
  1. Replace the content of the folder /nextgen-smooth/SmoothGallery/ with the new one
  2. /nextgen-smooth/SmoothGallery/css/
     Look for #myGallery and change to .myGallery on all .css
  3. /nextgen-smooth/SmoothGallery/scripts/jd.gallery.js
       Replace: title: this.galleryData[num].linkTitle
          with: title: this.galleryData[num].linkTitle, target: this.galleryData[num].linkTarget
  4. Bug on 2.0: This combination shows the carousel on a blank frame:
      showArrows   = false
      showCarousel = true
      embedLinks   = true                            
*/ 

//#################################################################
// Restrictions
  if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { die('You are not allowed to call this page directly.'); }

  if (!class_exists('nggallery') ) {
    add_action('admin_notices', create_function('', 'echo \'<div id="message" class="error fade"><p><strong>' . __('Sorry, NextGEN Smooth Gallery works only in Combination with NextGEN Gallery',"nggallery") . '</strong></p></div>\';'));
    return;
  }

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
                            "gal_code"          => ""); // ngs - NextGen Smooth

  add_option('dataNextGenSmooth', $data_ngs_default, 'Data from NextGen Smooth Gallery');
  $data_ngs = get_option('dataNextGenSmooth');
   
  define('BASE_URL'  , get_option('siteurl'));
  define('SMOOTH_URL', get_option('siteurl').'/wp-content/plugins/' . dirname(plugin_basename(__FILE__))); // get_bloginfo('wpurl')
  
include "nggSmoothSharedFunctions.php";
  
class Smooth_Gallery {
  //#################################################################
  // The Real Deal 
  
  function is_using_frames() { 
    global $data_ngs;
    
    return $data_ngs["use_frames"];    
  }
    
  function nggSmoothFindStringBetween($text, $begin, $end) {
    if ( ($posBegin = stripos($text, $begin         )) === false) return Array($text, "");
    if ( ($posEnd   = stripos($text, $end, $posBegin)) === false) return Array($text, "");
    
    $textBegin  = substr($text, 0, $posBegin);
    $textMiddle = substr($text, $posBegin, $posEnd - $posBegin + strlen($end) );
    $textEnd    = substr($text, $posEnd + strlen($end) , strlen($text));
    
    return Array($textBegin, $textMiddle, $textEnd);
  }

  function nggSmoothReplace($content) {
  	global $wpdb, $data_ngs;

    list($begin, $middle, $end) = $this->nggSmoothFindStringBetween($content, "[smooth", "]");  
    
    if ($begin == $content) return $content;	

    if (strpos($middle, ";") === false) { // Old Way [smooth=galleryId, width, height, timed, showArrows, showCarousel, embedLinks]
      $middleValues = str_replace(Array(" ", "]"), "", $middle);
      $middleValues = explode("=", $middleValues);
      $middleValues = explode(",", $middleValues[1]);

      $gid    = (int)  $middleValues[ 0];
      $width  = (int) ($middleValues[ 1]? $middleValues[ 1]: $data_ngs["width"]);
      $height = (int) ($middleValues[ 2]? $middleValues[ 2]: $data_ngs["height"]);
      
      switch ($middleValues[ 3]) {  case "0": case "false": $timed        = 0; break;
                                    case "1": case "true" : $timed        = 1; break;
                                    default:                $timed        = $data_ngs["timed"];}
      switch ($middleValues[ 4]) {  case "0": case "false": $showArrows   = 0; break;
                                    case "1": case "true" : $showArrows   = 1; break;
                                    default:                $showArrows   = $data_ngs["showArrows"];}
      switch ($middleValues[ 5]) {  case "0": case "false": $showCarousel = 0; break;
                                    case "1": case "true" : $showCarousel = 1; break;
                                    default:                $showCarousel = $data_ngs["showCarousel"];}
      switch ($middleValues[ 6]) {  case "0": case "false": $embedLinks   = 0; break;
                                    case "1": case "true" : $embedLinks   = 1; break;
                                    default:                $embedLinks   = $data_ngs["embedLinks"];}
      
      $delay             = (int)   $data_ngs["delay"];
      $defaultTransition = (string)$data_ngs["defaultTransition"];
      $showInfopane      = (int)   $data_ngs["showInfopane"];
      $textShowCarousel  = (string)$data_ngs["textShowCarousel"];
      $showCarouselOpen  = (int)   $data_ngs["showCarouselOpen"];
      
    } else { // New Way [smooth=id:; width:; height:; timed:; delay:; transition:; arrows:; info:; carousel:; text:; open:; links:;]
      $middleValues = substr($middle, 0, -1); // Remove last comma  
      $middleValues = explode("=", $middleValues);
      $middleValues = explode(";", $middleValues[1]);

      $final = Array();
      foreach($middleValues as $value) {
        list($key, $value) = explode(":", $value);
        
        if (trim($key) != "")
          $final[trim(strtolower($key))] = trim($value);
      }
      
      // The key is lowercase
      $gid    = (int)  $final["id"];
      $width  = (int) ($final["width"] ? $final["width"] : $data_ngs["width"]);
      $height = (int) ($final["height"]? $final["height"]: $data_ngs["height"]);
      
      switch ($final["timed"])    { case "0": case "false": $timed        = 0; break;
                                    case "1": case "true" : $timed        = 1; break;
                                    default:                $timed        = $data_ngs["timed"];}
      switch ($final["arrows"])   { case "0": case "false": $showArrows   = 0; break;
                                    case "1": case "true" : $showArrows   = 1; break;
                                    default:                $showArrows   = $data_ngs["showArrows"];}
      switch ($final["carousel"]) { case "0": case "false": $showCarousel = 0; break;
                                    case "1": case "true" : $showCarousel = 1; break;
                                    default:                $showCarousel = $data_ngs["showCarousel"];}
      switch ($final["links"])    { case "0": case "false": $embedLinks   = 0; break;
                                    case "1": case "true" : $embedLinks   = 1; break;
                                    default:                $embedLinks   = $data_ngs["embedLinks"];}
      
      $delay             = (int)   ($final["delay"]     ? $final["delay"]     : $data_ngs["delay"]);
      $defaultTransition = (string)($final["transition"]? $final["transition"]: $data_ngs["defaultTransition"]);
      $showInfopane      = (int)   ($final["info"]      ? $final["info"]      : $data_ngs["showInfopane"]);
      $textShowCarousel  = (string)($final["text"]      ? $final["text"]      : $data_ngs["textShowCarousel"]);
      $showCarouselOpen  = (int)   ($final["open"]      ? $final["open"]      : $data_ngs["showCarouselOpen"]);      
    }

                      $galleryID = $wpdb->get_var("SELECT gid FROM $wpdb->nggallery WHERE gid  = '$gid' ");
    if (! $galleryID) $galleryID = $wpdb->get_var("SELECT gid FROM $wpdb->nggallery WHERE name = '$gid' ");
    if (! $galleryID) return $begin . $middle . $end;

    if (  $galleryID) {              
      if ($data_ngs["use_frames"])
        $middle = $this->nggSmoothFrame($galleryID, $width, $height, $timed, $showArrows, $showCarousel, $embedLinks, $delay, $defaultTransition, $showInfopane, $textShowCarousel, $showCarouselOpen);
      else
        $middle = nggSmoothShow ($galleryID, $width, $height, $timed, $showArrows, $showCarousel, $embedLinks, $delay, $defaultTransition, $showInfopane, $textShowCarousel, $showCarouselOpen);
    }
    
  	return $this->nggSmoothReplace($begin . $middle . $end); // More than one gallery per post
  }

  function nggSmoothFrame($galleryID, $width, $height, $timed, $showArrows, $showCarousel, $embedLinks, $delay, $defaultTransition, $showInfopane, $textShowCarousel, $showCarouselOpen) {	
  	global $data_ngs;
    
    if($width == "") $width  = $data_ngs["width"];
    if($height== "") $height = $data_ngs["height"];
    
    $frame_url = "/wp-content/plugins/nextgen-smooth-gallery/nggSmoothFrame.php?galleryID=$galleryID&width=$width&height=$height&timed=$timed&showArrows=$showArrows&showCarousel=$showCarousel&embedLinks=$embedLinks&delay=$delay&defaultTransition=$defaultTransition&showInfopane=$showInfopane&textShowCarousel=$textShowCarousel&showCarouselOpen=$showCarouselOpen";
    
    // Increases frame width and height by 3px in order to display the complete image on the inside.    
    return "<iframe width=\"". ($width+3) ."px\" height=\"". ($height+3) ."px\" marginwidth=\"0\" marginheight=\"0\" scrolling=\"no\" frameborder=\"0\" name=\"smooth_frame_".rand()."\" src=\"" . BASE_URL . $frame_url . "\"></iframe>";
  }  
  
  function admin_menu() {
    add_options_page('NextGen Smooth Gallery', 'NextGen Smooth Gallery', 8, 'nextgen_smooth', array($this, 'add_options_page'));
  } 
  
  function add_options_page() {
  	global $data_ngs, $data_ngs_default, $wpdb;

    $msg = "";
        
    if ($_REQUEST["enviar"] == "Back to Default") {
      $data_ngs = $data_ngs_default;
      update_option('dataNextGenSmooth', $data_ngs);
      $msg = "Data saved successfully.";
    }

    if ($_REQUEST["enviar"] == "Save") {
      $data_ngs['width']             =  $_REQUEST['width'];
      $data_ngs['height']            =  $_REQUEST['height'];
      $data_ngs['timed']             = ($_REQUEST['timed']           ?1:0);
      $data_ngs['showArrows']        = ($_REQUEST['showArrows']      ?1:0);
      $data_ngs['showCarousel']      = ($_REQUEST['showCarousel']    ?1:0);
      $data_ngs['embedLinks']        = ($_REQUEST['embedLinks']      ?1:0);
      $data_ngs['use_frames']        = ($_REQUEST['use_frames']      ?1:0);
      $data_ngs['delay']             =  $_REQUEST['delay'];
      $data_ngs['defaultTransition'] =  $_REQUEST['defaultTransition'];
      $data_ngs['showInfopane']      = ($_REQUEST['showInfopane']    ?1:0);
      $data_ngs['textShowCarousel']  =  $_REQUEST['textShowCarousel'];
      $data_ngs['showCarouselOpen']  = ($_REQUEST['showCarouselOpen']?1:0);
      
      update_option('dataNextGenSmooth', $data_ngs);
      $msg = "Data saved successfully.";
    }
  	
  	if ($msg != '') echo '<div id="message"class="updated fade"><p>' . $msg . '</p></div>';  	     
  	?>
    
  	<div class="wrap">
      <h2>NextGen Smooth Gallery</h2>
            
      <div>      
     		<fieldset class="options" style="padding:20px; margin-top:20px;">
          <legend> Options </legend>

          <div style="float:right;">
            <form action="https://www.paypal.com/cgi-bin/webscr" method="post">
              <input type="hidden" name="cmd" value="_donations">
              <input type="hidden" name="business" value="parisoto@gmail.com">
              <input type="hidden" name="item_name" value="Wordpress Plugin: NextGen Smooth Gallery">
              <input type="hidden" name="no_shipping" value="0">
              <input type="hidden" name="no_note" value="1">
              <input type="hidden" name="currency_code" value="USD">
              <input type="hidden" name="tax" value="0">
              <input type="hidden" name="bn" value="PP-DonationsBF">
              <input type="image" src="https://www.paypal.com/en_US/i/btn/btn_donate_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to pay online!">
              <img alt="" border="0" src="https://www.paypal.com/en_US/i/scr/pixel.gif" width="1" height="1">
            </form>
            Help support this plugin
          </div>
          
          <form method="post">
            <div style="">
              <div style="width:120px; float:left;"> Width </div>
              <div style="width:120px; float:left;"> <input type="text" name="width" value="<?=$data_ngs['width']?>" style="width:60px;">px </div>
            </div>

            <div style="clear:left; padding-top:10px;">
              <div style="width:120px; float:left;"> Height </div>
              <div style="width:120px; float:left;"> <input type="text" name="height" value="<?=$data_ngs['height']?>" style="width:60px;">px </div>
            </div>
            
            <div style="clear:both; padding-top:10px;">
              <div style="width:120px; float:left;"> Timed </div>
              <div style="width:120px; float:left;"> <input type="checkbox" id="timed" name="timed" <?= ($data_ngs['timed']? "checked=\"checked\"": "") ?> onClick="if(this.checked){document.getElementById('timed_options').style.display='';} else{document.getElementById('timed_options').style.display='none';};" > </div>
            </div>                    
            
            <fieldset id="timed_options" class="options" style="padding:20px; margin-top:0px; display:none;">
              <legend> Timed Options </legend>
            
              <div style="clear:both;">
                <div style="width:120px; float:left;"> Delay </div>
                <div style="width:120px; float:left;"> <input type="text" name="delay" value="<?=$data_ngs['delay']?>" style="width:60px;">ms </div>
              </div>

              <div style="clear:both; padding-top:10px;">
                <div style="width:120px; float:left;"> Transition </div>
                <div style="width:120px; float:left;"> 
                  <select name="defaultTransition">
                    <option value="fade"                 <?= ($data_ngs['defaultTransition'] == "fade"                ? "selected":"") ?>> Fade                  </option>
                    <option value="crossfade"            <?= ($data_ngs['defaultTransition'] == "crossfade"           ? "selected":"") ?>> Cross Fade            </option>
                    <option value="fadebg"               <?= ($data_ngs['defaultTransition'] == "fadebg"              ? "selected":"") ?>> Fade BackGround       </option>
                    <option value="fadeslideleft"        <?= ($data_ngs['defaultTransition'] == "fadeslideleft"       ? "selected":"") ?>> Fade Slide Left       </option>
                    <option value="continuousvertical"   <?= ($data_ngs['defaultTransition'] == "continuousvertical"  ? "selected":"") ?>> Continuous Vertical   </option>
                    <option value="continuoushorizontal" <?= ($data_ngs['defaultTransition'] == "continuoushorizontal"? "selected":"") ?>> Continuous Horizontal </option>
                  </select>
                </div>
              </div>
            </fieldset>

            <script>
              if ("<?=$data_ngs['timed'];?>" == "1") {
                document.getElementById('timed_options').style.display='';
              }
            </script>
            
            <div style="clear:both; padding-top:10px;">
              <div style="width:120px; float:left;"> Show Arrows </div>
              <div style="width:120px; float:left;"> <input type="checkbox" name="showArrows" <?= ($data_ngs['showArrows']? "checked=\"checked\"": "") ?>> </div>
            </div>

            <div style="clear:both; padding-top:10px;">
              <div style="width:120px; float:left;"> Show Info Pane </div>
              <div style="width:120px; float:left;"> <input type="checkbox" name="showInfopane" <?= ($data_ngs['showInfopane']? "checked=\"checked\"": "") ?>> </div>
            </div>
            
            <div style="clear:both; padding-top:10px;">
              <div style="width:120px; float:left;"> Show Carousel </div>
              <div style="width:120px; float:left;"> <input type="checkbox" name="showCarousel" <?= ($data_ngs['showCarousel']? "checked=\"checked\"": "") ?> onClick="if(this.checked){document.getElementById('carousel_options').style.display='';} else{document.getElementById('carousel_options').style.display='none';};"> </div>
            </div>

            <fieldset id="carousel_options" class="options" style="padding:20px; margin-top:0px; display:none;">
              <legend> Carousel Options </legend>
            
              <div style="clear:both;">
                <div style="width:120px; float:left;"> Text</div>
                <div style="width:120px; float:left;"> <input type="text" name="textShowCarousel" value="<?=$data_ngs['textShowCarousel']?>" style="width:120px;"> </div>
              </div>

              <div style="clear:both; padding-top:10px;">
                <div style="width:120px; float:left;"> Opened </div>
                <div style="width:120px; float:left;"> <input type="checkbox" name="showCarouselOpen" <?= ($data_ngs['showCarouselOpen']? "checked=\"checked\"": "") ?>> </div>              
              </div>
            </fieldset>
            
            <script>
              if ("<?=$data_ngs['showCarousel'];?>" == "1") {
                document.getElementById('carousel_options').style.display='';
              }
            </script>
            
            <div style="clear:both; padding-top:10px; padding-bottom:20px;">
              <div style="width:120px; float:left;"> Embed Links </div>
              <div style="width:120px; float:left;"> <input type="checkbox" name="embedLinks" <?= ($data_ngs['embedLinks']? "checked=\"checked\"": "") ?>> </div>
            </div>
           
            <div class="submit">           
              <div style="clear:both; padding-top:10px; padding-bottom:50px;">
                <div style="width:120px; float:left;"> Use IFrames </div>
                <div style="width:40px; float:left;"> <input type="checkbox" name="use_frames" <?= ($data_ngs['use_frames']? "checked=\"checked\"": "") ?>> </div>
                <div style=" float:left; width:700px;"> 
                  Jon Design's Smooth Gallery is known for not working properly along other JS libraries like prototype and jquery (some of your plugins might use them). <br/>
                  Checking 'Use IFrames' makes your gallery appear inside an IFrame, therefore overcoming this problem.
                </div>
              </div>
            </div>
            
            <div class="submit"> 
              <input type="submit" name="enviar" value="Save">
              <input type="submit" name="enviar" value="Back to Default">
            </div>
<!--            
            <br> <b>Default: </b> Width: 400px; Height 400px; Timed: false; Delay: 9000; DefaultTransition: fade; Show Arrows: true; Show Info Pane: false; Show Carousel: true; Carousel Text: Pictures; Carousel Opened: true; Embed Links: true; Use IFrames: false;
-->
          </form>
     		</fieldset>
      </div>
      
      <?
      $gal_id   = $_REQUEST['gal_id'];
      $gal_code = $_REQUEST['gal_code'];
      
      if ( ($_REQUEST["hide"] == "Hide Example") || ($_REQUEST["show"] == "Show" && $gal_id == "" && $gal_code == "") ) {
        $gal_id = "";
        $data_ngs['gal_id'] = "";
        update_option('dataNextGenSmooth', $data_ngs);
      } elseif ($_REQUEST["show"] == "Show") {
        if ($gal_id) 
          $gal_code = "[smooth=id:$gal_id;]";
      
        $gal_id_example = $this->nggSmoothReplace($gal_code);
        
        if ($gal_id_example == $gal_code) {
          $data_ngs['gal_code'] = "";
          
          if ($gal_id) $err_example      = "<font color='red'>Gallery not found</font>";
          else         $err_example_code = "<font color='red'>Gallery not found</font>";
          
          $gal_id_example = "";
        } else {
          $data_ngs['gal_code'] = $gal_code;
        }
      
        update_option('dataNextGenSmooth', $data_ngs);
      } else {      
        $gal_code = $data_ngs["gal_code"];
        
        if ($gal_code != "")
          $gal_id_example = $this->nggSmoothReplace($gal_code);
      }

      ?>      
      
      <div>
     		<fieldset class="options" style="padding:20px; margin-top:20px; margin-bottom:20px;">
          <legend> Gallery </legend>
          
          This is what your gallery will look like with the options above (after you <b>save</b> them). <br/><br/>
          You can also overwrite the default options in the Gallery Code field. <br/><br/>

          <div class="submit">           
            <form method="post">
              <div style="width:100px; vertical-align:bottom; line-height:28px; text-align:right; float:left;"> 
                Gallery Id:
              </div>
              <div> 
                <input type="text" name="gal_id" value="<?=$gal_id?>" style="width:60px;">
                <input type="submit" name="show" value="Show">
                <input type="submit" name="hide" value="Hide Example">
                <?= $err_example; ?>
              </div>
            </form>

            <form method="post">
              <div style="width:100px; vertical-align:bottom; line-height:28px;  text-align:right; float:left;"> 
                Gallery Code: 
              </div>
              <div>               
                <input type="text" name="gal_code" value="<?=$gal_code?>" style="width:300px;">
                <input type="submit" name="show" value="Show">
                <?= $err_example_code; ?>
              </div>
            </form>
          </div>
          <br/>
          
          <?= $gal_id_example; ?>
          
          <br/> <br/>
          If your gallery is taking forever to load, or you just see the frame around it, try to check the option 'Use IFrames' above.<br/><br/>
          There is a bug on JonDesign's SmoothGallery 2.0 that shows the carousel on a blank frame with the options: arrows: false; carousel: true; links: true;

        </fieldset>
      </div>
    </div>
  	<?
  }  
}

$smooth_gallery = new Smooth_Gallery();

add_action('admin_menu'  , array($smooth_gallery, 'admin_menu'));

// Replace [smooth=id,width,height] with the real galery
add_filter('the_content', array($smooth_gallery, 'nggSmoothReplace'));
add_filter('the_excerpt', array($smooth_gallery, 'nggSmoothReplace'));

// Hook wp_head to add css
if (! $smooth_gallery->is_using_frames)
  add_action('wp_head', 'nggSmoothHead');

if ($_REQUEST["page"] == "nextgen_smooth")
  add_action('admin_head', 'nggSmoothHead');
?>