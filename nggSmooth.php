<?php
/*
Plugin Name: NextGEN Smooth Gallery
Plugin URI: http://uninuni.com/wordpress-plugin-nextgen-smooth-gallery/
Description: The amazing galery viewer from <a href="http://smoothgallery.jondesign.net/">JonDesign's SmoothGallery</a> for NextGEN Gallery.
Author: Bruno Guimaraes
Author URI: http://uninuni.com/
Version: 1.2

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

    // New Way [smooth=id:; width:; height:; timed:; delay:; transition:; arrows:; info:; carousel:; text:; open:; links:;]
    $middleValues = substr($middle, 0, -1); // Remove last brackets
    $middleValues = explode("=", $middleValues);
    $middleValues = explode(";", $middleValues[1]);

    $final = Array();
    foreach($middleValues as $value) {
      list($key, $value) = explode(":", $value);
      
      if (trim($key) != "")
        $final[trim(strtolower($key))] = trim($value);
    }
    
    $info = $this->get_values($final);
    
                              $info["galleryID"] = $wpdb->get_var("SELECT gid FROM $wpdb->nggallery WHERE gid  = '".$info["galleryID"]."' ");
    if (! $info["galleryID"]) $info["galleryID"] = $wpdb->get_var("SELECT gid FROM $wpdb->nggallery WHERE name = '".$info["galleryID"]."' ");
    if (! $info["galleryID"]) return $begin . $middle . $end;

    if (  $info["galleryID"]) {
      if ($info["use_frames"])
        $middle = $this->nggSmoothFrame($info);
      else
        $middle = nggSmoothShow($info);
    }
    
  	return $this->nggSmoothReplace($begin . $middle . $end); // More than one gallery per post
  }

  function nggSmoothFrame($info) {	
  	global $data_ngs;

    if($info["width"] == "") $info["width"]  = $data_ngs["width"];
    if($info["height"]== "") $info["height"] = $data_ngs["height"];

    $frame_url = "/wp-content/plugins/". plugin_basename( dirname(__FILE__)) ."/nggSmoothFrame.php?galleryID=".$info["galleryID"]."&width=".$info["width"]."&height=".$info["height"]."&timed=".$info["timed"]."&showArrows=".$info["showArrows"]."&showCarousel=".$info["showCarousel"]."&embedLinks=".$info["embedLinks"]."&delay=".$info["delay"]."&defaultTransition=".$info["defaultTransition"]."&showInfopane=".$info["showInfopane"]."&textShowCarousel=".$info["textShowCarousel"]."&showCarouselOpen=".$info["showCarouselOpen"]."&margin=&align="; // margin and align goes to the IFrame

    // Increases frame width and height by 3px in order to display the complete image on the inside.    
    return "<p style=\"".nggSmoothAlign($info['align'], $info['margin'], "iframe")."\">
              <iframe width=\"". ($info["width"]+3) ."px\" height=\"". ($info["height"]+3) ."px\" marginwidth=\"0\" marginheight=\"0\" scrolling=\"no\" frameborder=\"0\" name=\"smooth_frame_".rand()."\" src=\"" . BASE_URL . $frame_url . "\"></iframe>
            </p>";
  }  
  
  function admin_menu() {  
    add_menu_page('Gallery (Smooth)', 'Gallery (Smooth)', 8, plugin_basename( dirname(__FILE__)), array($this, 'general_page')); // add_options_page
    add_submenu_page( plugin_basename( dirname(__FILE__)), 'Specific Options', 'Specific Options', 8, 'specific_smooth', array($this, 'specific_page'));
    add_submenu_page( plugin_basename( dirname(__FILE__)), 'Soon...', 'Soon...', 8, 'soon_smooth', array($this, 'soon_page'));
  } 
  
  function soon_page() { ?>
    <fieldset class="options" style="padding:20px; margin-top:20px;">
      <legend> Soon... </legend>        
      <ol style="list-style-position:inside; padding-left:12px; list-style-type:decimal;">
        <li>Smooth with NextGen Albuns </li>
        <li>Smooth with Wordpress Gallery </li>
        <li>Smooth with PHP array </li>
        <li>Thumbnail Generator </li>
        <li>Open with lightbox </li>
        <li>Info Zone </li>
          <ul style="list-style-position:inside; padding-left:20px; list-style-type:disc;">
            <li>Set Opacity </li>
            <li>Choose Title from gallery name or alttext (NextGen) </li>
            <li>Change font, color, size from title and description </li>
          </ul>
        <li>Choose to open images on same/new window </li>        
      </ol>
    </fieldset>
  <?php }
  
  function save_request() {
    global $data_ngs, $_REQUEST;

    $data_ngs['width']             = (int)    $_REQUEST['width'];
    $data_ngs['height']            = (int)    $_REQUEST['height'];
    $data_ngs['timed']             = (bool)   $_REQUEST['timed'];
    $data_ngs['showArrows']        = (bool)   $_REQUEST['showArrows'];
    $data_ngs['showCarousel']      = (bool)   $_REQUEST['showCarousel'];
    $data_ngs['embedLinks']        = (bool)   $_REQUEST['embedLinks'];
    $data_ngs['use_frames']        = (bool)   $_REQUEST['use_frames'];
    $data_ngs['delay']             = (int)    $_REQUEST['delay'];
    $data_ngs['defaultTransition'] = (string) $_REQUEST['defaultTransition'];
    $data_ngs['showInfopane']      = (bool)   $_REQUEST['showInfopane'];
    $data_ngs['textShowCarousel']  = (string) $_REQUEST['textShowCarousel'];
    $data_ngs['showCarouselOpen']  = (bool)   $_REQUEST['showCarouselOpen'];
    $data_ngs['margin']            = (int)    $_REQUEST['margin'];
    $data_ngs['align']             = (string) $_REQUEST['align'];
  }

  function get_values($final) {
    global $data_ngs;

    $info = array();      
    $info["galleryID"]         = (int)    ( (array_key_exists("id"        , $final))? $final["id"]                    :$data_ngs["id"] );
    $info["width"]             = (int)    ( (array_key_exists("width"     , $final))? $final["width"]                 :$data_ngs["width"] );
    $info["height"]            = (int)    ( (array_key_exists("height"    , $final))? $final["height"]                :$data_ngs["height"] );
    $info["timed"]             = (bool)   ( (array_key_exists("timed"     , $final))?($final["timed"]   =='false'?0:1):$data_ngs["timed"] );
    $info["showArrows"]        = (bool)   ( (array_key_exists("arrows"    , $final))?($final["arrows"]  =='false'?0:1):$data_ngs["showArrows"] );
    $info["showCarousel"]      = (bool)   ( (array_key_exists("carousel"  , $final))?($final["carousel"]=='false'?0:1):$data_ngs["showCarousel"] );
    $info["embedLinks"]        = (bool)   ( (array_key_exists("links"     , $final))?($final["links"]   =='false'?0:1):$data_ngs["embedLinks"] );
    $info["use_frames"]        = (bool)   ( (array_key_exists("frames"    , $final))?($final["frames"]  =='false'?0:1):$data_ngs["use_frames"] );
    $info["delay"]             = (int)    ( (array_key_exists("delay"     , $final))? $final["delay"]                 :$data_ngs["delay"] );
    $info["defaultTransition"] = (string) ( (array_key_exists("transition", $final))? $final["transition"]            :$data_ngs["defaultTransition"] );
    $info["showInfopane"]      = (bool)   ( (array_key_exists("info"      , $final))?($final["info"]    =='false'?0:1):$data_ngs["showInfopane"] );
    $info["textShowCarousel"]  = (string) ( (array_key_exists("text"      , $final))? $final["text"]                  :$data_ngs["textShowCarousel"] );
    $info["showCarouselOpen"]  = (bool)   ( (array_key_exists("open"      , $final))?($final["open"]    =='false'?0:1):$data_ngs["showCarouselOpen"] );
    $info["margin"]            = (int)    ( (array_key_exists("margin"    , $final))? $final["margin"]                :$data_ngs["margin"] );
    $info["align"]             = (string) ( (array_key_exists("align"     , $final))? $final["align"]                 :$data_ngs["align"] );     
    
    return $info;
  }  
  
  function specific_page() {
  	global $data_ngs, $wpdb;

    if ($_REQUEST["enviar"])
      $this->save_request();
  
    $code  = "[smooth=id: yyy;";
    $code .= " width:"    .  $data_ngs['width']                        . ";";
    $code .= " height:"   .  $data_ngs['height']                       . ";";
    $code .= " timed:"    . ($data_ngs['timed']       ?'true':'false') . ";";
    $code .= " arrows:"   . ($data_ngs['showArrows']  ?'true':'false') . ";";
    $code .= " carousel:" . ($data_ngs['showCarousel']?'true':'false') . ";";
    $code .= " links:"    . ($data_ngs['embedLinks']  ?'true':'false') . ";";
    $code .= " info:"     . ($data_ngs['showInfopane']?'true':'false') . ";";
    $code .= " align:"    .  $data_ngs['align']                        . ";";
    $code .= " frames:"   . ($data_ngs['use_frames']  ?'true':'false') . ";";

    if ($data_ngs['timed']) { 
      $code .= " delay:"      . $data_ngs['delay']             . ";";
      $code .= " transition:" . $data_ngs['defaultTransition'] . ";";
    }
    
    if ($data_ngs['showCarousel']) {
      $code .= " open:" . ($data_ngs['showCarouselOpen']?'true':'false') . ";";
      $code .= " text:" . $data_ngs['textShowCarousel'] . ";";
    }
    
    if ($data_ngs['align'] == "float_right" || $data_ngs['align'] == "float_left")
      $code .= " margin:" . $data_ngs['margin'] . ";";
            
    $code .= "]";
      
    $code_2 = "<?php \n  \$content = \"" . $code . "\"; \n  smooth_show(\$content); \n?>";
      
    ?>
  	<div class="wrap">
      <h2>NextGen Smooth Gallery</h2>
      <?php $this->donation(); ?>      
      <form method="post">      
        <div>   
          <fieldset class="options" style="padding:20px; margin-top:20px;">
            <legend> Specific Options </legend>
              
              Allows a gallery to have a behavior other that the General one. 
              <br/><br/>

              <?php $this->opcoes_tela_antes(); ?>

              <div class="submit"> 
                <input type="submit" name="enviar" value="Generate Code">
              </div>

            <hr style="width:90%; border:1px solid #DFDFDF;">
            <br/><b>Warning: </b>There is a bug on JonDesign's SmoothGallery 2.0 that shows the carousel on a blank frame with the options: arrows: false; carousel: true; links: true;<br/><br/>
            
            <hr style="width:90%; border:1px solid #DFDFDF;">
            
            <br/>You have two options:
            
            <br><br><b>1. Write on your post</b> (You must replace 'yyy' with your Gallery Id)<br>
            
            <textarea style="width:700px; height:130px;"><?php echo $code; ?></textarea>

            <br><br><b>2. Write on any php page</b> (You must replace 'yyy' with your Gallery Id)<br>
            
            <textarea style="width:700px; height:130px;"><?php echo $code_2; ?></textarea>
            
            <hr style="width:90%; border:1px solid #DFDFDF;">
            
            <br/>If you remove, for example, "width:300, " the General option will be used on that item.
          </fieldset>
        </div>  
        
        <?php $this->example_show($code); ?>
      </form>
    </div>
  <?php }
  
  function general_page() {
  	global $data_ngs, $data_ngs_default, $wpdb;

    $msg = "";
        
    if ($_REQUEST["enviar"] == "Back to Default") {
      $data_ngs = $data_ngs_default;
      update_option('dataNextGenSmooth', $data_ngs);
      $msg = "Data saved successfully.";
    } elseif ($_REQUEST["enviar"]) {
      $this->save_request();
      
      update_option('dataNextGenSmooth', $data_ngs);
      $msg = "Data saved successfully.";
    }
  	
  	if ($msg != '') echo '<div id="message"class="updated fade"><p>' . $msg . '</p></div>';
    
    $code = "[smooth=id:yyy;]";    
    ?>    
  	<div class="wrap">
      <h2>NextGen Smooth Gallery</h2>    
      <?php $this->donation(); ?>
      <form method="post">      
        <div>   
          <fieldset class="options" style="padding:20px; margin-top:20px;">
            <legend> General Options </legend>      
              <?php $this->opcoes_tela_antes(); ?>      

              <div class="submit" style="clear:both;"> 
                <input type="submit" name="enviar" value="Save">
                <input type="submit" name="enviar" value="Back to Default">
              </div>
              
            <hr style="width:90%; border:1px solid #DFDFDF;">
            <br/><b>Warning: </b>There is a bug on JonDesign's SmoothGallery 2.0 that shows the carousel on a blank frame with the options: arrows: false; carousel: true; links: true;<br/><br/>

            <hr style="width:90%; border:1px solid #DFDFDF;">
            <br><br><b>Write on your post</b> (You must replace 'yyy' with your Gallery Id)<br>

            <textarea style="width:700px; height:60px;"><?php echo $code; ?></textarea>            
          </fieldset>
        </div>  
        
        <?php $this->example_show($code); ?>
      </form>
    </div>
  	<?php
  }  
  
  function example_show($code) {
    global $_REQUEST, $data_ngs, $wpdb; 
    
    $gal_id = $_REQUEST['gal_id']; 

    $gallerylist = $wpdb->get_results("SELECT * FROM $wpdb->nggallery ORDER BY gid ASC");

    $select = "";
    if(is_array($gallerylist))
      foreach($gallerylist as $gallery) {
        $selected = ($gallery->gid == $gal_id )?	' selected="selected"' : "";
        $select .= '<option value="'.$gallery->gid.'"'.$selected.' >('.$gallery->gid.') '.$gallery->title.'</option>'."\n";
      }
      
    if ($gal_id)
      $real_deal = $this->nggSmoothReplace( str_replace("yyy", $gal_id, $code) );
    ?>     
    <div>
      <fieldset class="options" style="padding:20px; margin-top:20px; margin-bottom:20px;">
        <legend> Example </legend>
        
        This is how your gallery will look like with the options above (after you <b>save</b> them). <br/><br/>

        <div class="submit">           
          <div class="alignleft actions">
            <select id="gal_id" name="gal_id" style="width:250px;">;
              <option value="0"> Choose a gallery </option>
              <?php echo $select; ?>
            </select>
            <input type="submit" id="enviar" name="enviar" value="Select" class="button-secondary" />
          </div>            
        </div>
        <br/>
        
        <?php echo $real_deal; ?>
      </fieldset>
    </div>
  <?php }
   
   
  function donation() { ?>
    <div style="width:100%; text-align:right;">
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
    </div>  
  <?php }  
  
  function opcoes_tela_antes() { 
    global $data_ngs; ?>    
          <div style="">
            <div style="width:120px; float:left;"> Width </div>
            <div style="width:120px; float:left;"> <input type="text" name="width" value="<?php echo $data_ngs['width']?>" style="width:60px;">px </div>
          </div>

          <div style="clear:left; padding-top:10px;">
            <div style="width:120px; float:left;"> Height </div>
            <div style="width:120px; float:left;"> <input type="text" name="height" value="<?php echo $data_ngs['height']?>" style="width:60px;">px </div>
          </div>
          
          <div style="clear:both; padding-top:10px;">
            <div style="width:120px; float:left;"> Timed </div>
            <div style="width:120px; float:left;"> <input type="checkbox" id="timed" name="timed" <?php echo ($data_ngs['timed']? "checked=\"checked\"": "") ?> onClick="if(this.checked){document.getElementById('timed_options').style.display='';} else{document.getElementById('timed_options').style.display='none';};" > </div>
          </div>                    
          
          <fieldset id="timed_options" class="options" style="padding:20px; margin-top:0px; display:<?php echo ($data_ngs['timed']?'':'none')?>;">
            <legend> Timed Options </legend>
          
            <div style="clear:both;">
              <div style="width:120px; float:left;"> Delay </div>
              <div style="width:120px; float:left;"> <input type="text" name="delay" value="<?php echo $data_ngs['delay']?>" style="width:60px;">ms </div>
            </div>

            <div style="clear:both; padding-top:10px;">
              <div style="width:120px; float:left;"> Transition </div>
              <div style="width:120px; float:left;"> 
                <select name="defaultTransition">
                  <option value="fade"                 <?php echo ($data_ngs['defaultTransition'] == "fade"                ? "selected":"") ?>> Fade                  </option>
                  <option value="crossfade"            <?php echo ($data_ngs['defaultTransition'] == "crossfade"           ? "selected":"") ?>> Cross Fade            </option>
                  <option value="fadebg"               <?php echo ($data_ngs['defaultTransition'] == "fadebg"              ? "selected":"") ?>> Fade BackGround       </option>
                  <option value="fadeslideleft"        <?php echo ($data_ngs['defaultTransition'] == "fadeslideleft"       ? "selected":"") ?>> Fade Slide Left       </option>
                  <option value="continuousvertical"   <?php echo ($data_ngs['defaultTransition'] == "continuousvertical"  ? "selected":"") ?>> Continuous Vertical   </option>
                  <option value="continuoushorizontal" <?php echo ($data_ngs['defaultTransition'] == "continuoushorizontal"? "selected":"") ?>> Continuous Horizontal </option>
                </select>
              </div>
            </div>
          </fieldset>           

          <div style="clear:both; padding-top:10px;">
            <div style="width:120px; float:left;"> Show Arrows </div>
            <div style="width:120px; float:left;"> <input type="checkbox" name="showArrows" <?php echo ($data_ngs['showArrows']? "checked=\"checked\"": "") ?>> </div>
          </div>

          <div style="clear:both; padding-top:10px;">
            <div style="width:120px; float:left;"> Show Info Pane </div>
            <div style="width:120px; float:left;"> <input type="checkbox" name="showInfopane" <?php echo ($data_ngs['showInfopane']? "checked=\"checked\"": "") ?>> </div>
          </div>
          
          <div style="clear:both; padding-top:10px;">
            <div style="width:120px; float:left;"> Show Carousel </div>
            <div style="width:120px; float:left;"> <input type="checkbox" name="showCarousel" <?php echo ($data_ngs['showCarousel']? "checked=\"checked\"": "") ?> onClick="if(this.checked){document.getElementById('carousel_options').style.display='';} else{document.getElementById('carousel_options').style.display='none';};"> </div>
          </div>

          <fieldset id="carousel_options" class="options" style="padding:20px; margin-top:0px; display:<?php echo ($data_ngs['showCarousel']?'':'none') ?>;">
            <legend> Carousel Options </legend>
          
            <div style="clear:both;">
              <div style="width:120px; float:left;"> Text</div>
              <div style="width:120px; float:left;"> <input type="text" name="textShowCarousel" value="<?php echo$data_ngs['textShowCarousel']?>" style="width:120px;"> </div>
            </div>

            <div style="clear:both; padding-top:10px;">
              <div style="width:120px; float:left;"> Opened </div>
              <div style="width:120px; float:left;"> <input type="checkbox" name="showCarouselOpen" <?php echo ($data_ngs['showCarouselOpen']? "checked=\"checked\"": "") ?>> </div>              
            </div>
          </fieldset>
          
          <div style="clear:both; padding-top:10px;">
            <div style="width:120px; float:left;"> Embed Links </div>
            <div style="width:120px; float:left;"> <input type="checkbox" name="embedLinks" <?php echo ($data_ngs['embedLinks']? "checked=\"checked\"": "") ?>> </div>
          </div>
                    
          <div style="clear:both; padding-top:10px;">
            <div style="width:120px; float:left;"> Align </div>
              <div style="width:120px; float:left;"> 
                <select name="align">
                  <option value="left"        <?php echo ($data_ngs['align'] == "left"        ? "selected":"") ?> onClick="document.getElementById('align_options').style.display='none';"> Left        </option>
                  <option value="right"       <?php echo ($data_ngs['align'] == "right"       ? "selected":"") ?> onClick="document.getElementById('align_options').style.display='none';"> Right       </option>
                  <option value="center"      <?php echo ($data_ngs['align'] == "center"      ? "selected":"") ?> onClick="document.getElementById('align_options').style.display='none';"> Center      </option>
                  <option value="float_left"  <?php echo ($data_ngs['align'] == "float_left"  ? "selected":"") ?> onClick="document.getElementById('align_options').style.display='';"    > Float Left  </option>
                  <option value="float_right" <?php echo ($data_ngs['align'] == "float_right" ? "selected":"") ?> onClick="document.getElementById('align_options').style.display='';"    > Float Right </option>
                </select>
              </div>
          </div>

          <fieldset id="align_options" class="options" style="padding:20px; margin-top:0px; display:<?php if ( ($data_ngs['align']=='float_left') || ($data_ngs['align']=='float_right') ) { echo '';} else {echo 'none';}?>;">
            <legend> Align Options </legend>
            <div style="clear:both; padding-top:10px;">
              <div style="width:120px; float:left;"> Margin </div>
              <div style="width:120px; float:left;"> <input type="text" name="margin" value="<?php echo $data_ngs['margin']?>" style="width:60px;">px </div>
            </div>
          </fieldset>
          
          <div style="clear:both; padding-bottom:8px;"></div>  
          
            <div class="submit">
              <div style="clear:both; padding-bottom:50px;">
                <div style="width:120px; float:left;"> IFrames </div>
                <div style="width:40px; float:left;"> <input type="checkbox" name="use_frames" <?php echo ($data_ngs['use_frames']? "checked=\"checked\"": "") ?>> </div>
                <div style=" float:left; width:700px;"> 
                  Jon Design's Smooth Gallery is known for not working properly along other JS libraries like prototype and jquery (some of your plugins might use them). 
                  <br/><br/>If your gallery is taking forever to load or not showing up, try to check this option.
                  <br/>Checking 'IFrames' makes your gallery appear inside an IFrame, therefore overcoming this problem.
                </div>
              </div>
            </div>          
  <?php }  
}

function smooth_show($content) {
  global $smooth_gallery;
  
  echo $smooth_gallery->nggSmoothReplace($content);
}

$smooth_gallery = new Smooth_Gallery();

add_action('admin_menu' , array($smooth_gallery, 'admin_menu'));
add_filter('the_content', array($smooth_gallery, 'nggSmoothReplace'));
add_filter('the_excerpt', array($smooth_gallery, 'nggSmoothReplace'));

// Hook wp_head to add css
if (! $smooth_gallery->is_using_frames)                       add_action('wp_head'   , 'nggSmoothHead');
if ($_REQUEST["page"] == "specific_smooth"                  ) add_action('admin_head', 'nggSmoothHeadAdmin');
if ($_REQUEST["page"] == "soon_smooth"                      ) add_action('admin_head', 'nggSmoothHeadAdmin');
if ($_REQUEST["page"] == plugin_basename( dirname(__FILE__))) add_action('admin_head', 'nggSmoothHeadAdmin');
?>