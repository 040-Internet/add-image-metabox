<?php
/*
Plugin Name: Add Image Metabox
Plugin URI: http://040.se
Description: Adds a image upload metabox
Version: 0.1
Author: Martin Nilsson
Author URI: http://040.se

This is a revision of a plugin called "Multi Image Metabox" originally created by Willy Bahuaud (http://wordpress.org/plugins/multi-image-metabox/). Willy's plugin is awesome, but I felt it needed some improvements to fit my needs. Thats why I changed the code and added some functionality. I've also removed some functions that I felt was unnecessary.

What did I change/add?
- Added a functions to add unlimited new slides for each post type.
- Added a function to get the images in a nice array, to make it easier to use them.
- I've also made the code structure more readable (imo).

LICENSE:
This plugin is licensed under GPLv2, you can read about the license here: (http://wordpress.org/about/gpl/).
*/



/**
*
* Javascripts & CSS
*
* Loading all the css and js that i needed here
*
**/
function aim_add_css() {
  if(is_admin()) {
    wp_enqueue_style('add-img-mb-css', plugins_url('/add-img.css',__FILE__));
  }
}

function aim_add_js() {
  if(is_admin()) {
    wp_enqueue_script('add-img-mb-draggable-js', plugins_url('/add-img-draggable.js',__FILE__), array('jquery'));
    wp_enqueue_script('add-img-mb-js', plugins_url('/add-img.js',__FILE__), array('jquery'));
    wp_enqueue_script('jquery-ui-draggable');
  }
}

add_action('admin_init', 'aim_add_css');
add_action('admin_init', 'aim_add_js');



/**
*
* aim_list_my_images_slots()
*
* This function defines how many imageboxes there should be on
* each page. The function checks if there is any option for more
* than the standard 3 imageboxes. If there is, it will use this
* amount instead.
*
**/
function aim_list_my_images_slots() {
  global $post;
  $slideAmount = get_post_meta($post->ID, 'slide-amount', true);

  if($slideAmount) {
    $imageArray = array();
    for ($i=1; $i <= $slideAmount; $i++) { 
      $imageArray['image'.$i] = '_image' . $i;
    }
  } else {
    $imageArray = array(
      'image1' => '_image1',
      'image2' => '_image2',
      'image3' => '_image3',
    );
  }

  $list_images = apply_filters('list_images', $imageArray);

  return $list_images;
}


/**
*
* INITIALIZE
*
* This is where the plugin begin, it adds the meta box to every page
* and load all the necessary stuff.
*
**/
add_action('add_meta_boxes', 'aim_metabox');
function aim_metabox() {
  $cpts = apply_filters('images_cpt', array('page'));

  foreach($cpts as $cpt) {
    add_meta_box(
      'add_img_metabox',
      __('Add images'),
      'aim_markup',
      $cpt,
      'normal',
      'core'
    );
  }
}


/**
*
* SAVE METABOX 
*
**/
add_action('save_post', 'aim_save_details'); 
function aim_save_details($post_ID) { 
  if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
    return $post_ID;
  }
   
  update_post_meta($post_ID, 'slide-amount', $_POST['slide-amount']);

  $list_images = aim_list_my_images_slots();
  foreach($list_images as $k => $i) {
    if(isset($_POST[$k])) {
      check_admin_referer('image-slide-save_'.$_POST['post_ID'], 'image-slide-nonce');
      update_post_meta($post_ID, $i, esc_html($_POST[$k]));
    }
  }
}


/**
*
* MARKUP AND LOAD STUFF
*
* This functions handles all the markup and loading scripts/styles etc
*
**/
function aim_markup($post) {
  $list_images = aim_list_my_images_slots();
  $slideAmount = get_post_meta($post->ID, 'slide-amount', true);

  if($slideAmount) {
    $value = $slideAmount;
  } else {
    $value = '3';
  }

  wp_nonce_field( 'image-slide-save_'.$post->ID, 'image-slide-nonce');
  echo '<input type="hidden" name="slide-amount" class="slide-amount" value="'.$value.'" />';

  echo '<div id="droppable">';
  $z = 1;
  foreach($list_images as $k=>$i) {
    $meta = get_post_meta($post->ID,$i,true);
    $img = ($meta) ? '<img src="'.wp_get_attachment_thumb_url($meta).'" alt="">' : '';
    echo '<div class="image-entry">';
    echo '<input type="hidden" name="'.$k.'" id="'.$k.'" class="id_img" data-num="'.$z.'" value="'.$meta.'">';
    echo '<div class="img-preview" data-num="'.$z.'">'.$img.'</div>';
    echo '<a class="get-image button-primary" data-num="'.$z.'">'.__('Add image').'</a>';
    echo '<a class="remove-slide button-secondary" data-num="'.$z.'">'.__('Delete').'</a>';
    echo '</div>';
    $z++;
  }
  echo '<div class="add-more-slides" data-action="add">+</div>';
  echo '</div>';

}


/**
*
* aim_get_post_slide_images()
*
* This function push each image in an array with both the image and its ID.
* Then it use that array to create the final array that contains the attributes
* of the image and the thumbnail (src, width, height and resized).
*
**/
function aim_get_post_slide_images($large = null, $small = null) {
  global $post;
  $the_id = $post->ID;
  $list_images = aim_list_my_images_slots();

  $imgsWithIds = array();
  foreach ($list_images as $key => $img) {
    if($i = get_post_meta($the_id,$img,true))
      $imgsWithIds[$key] = $i;
  }

  $imgAndThumb = array();
  foreach($imgsWithIds as $k => $id)
    $imgAndThumb[$k] = array(wp_get_attachment_image_src($id, $small),wp_get_attachment_image_src($id, $large));
  return $imgAndThumb;
}


/**
*
* aim_get_the_images()
*
* This is the main function you should use in your loop. The function makes
* the imgAndThumb array look better and also makes it easier to use.
* 
* For example: To get the width of the image or thumbnail, you can now use
* $theImageArray['width'] instead of $theImageArray[1]
*
* You can also define what size you want for the full size img (param 3)
* and the thumbnails (param 4).
*
* Standard sizes are 'full' and 'thumbnail'
*
**/
function aim_get_the_images($showImg = false, $showThumbs = false, $imgSize = 'full', $thumbSize = 'thumbnail') {
  $images = array();
  $imgs = array();
  $thumbnails = array();
  $thumbs = array();

  $array = aim_get_post_slide_images($imgSize, $thumbSize);
  $args = array(
    0 => 'src',
    1 => 'width',
    2 => 'height',
    3 => 'resized'
  );

  $i = 0;
  foreach ($array as $name => $arr) {
    if($showImg) $images[$i] = $array[$name][1];
    if($showThumbs) $thumbnails[$i] = $array[$name][0];
    $i++;
  }
  
 /**
  * IMAGES
  **/
  if($showImg) {
    $i = 0;
    foreach ($images as $key => $value) {
      foreach ($images[$i] as $key => $value) {
        $type = $args[$key];
        $imgs[$i][$type] = $value;
      } $i++;
    }
  }

 /**
  * THUMBNAILS
  **/
  if($showThumbs) {
    $i = 0;
    foreach ($thumbnails as $key => $value) {
      foreach ($thumbnails[$i] as $key => $value) {
        $type = $args[$key];
        $thumbs[$i][$type] = $value;
      } $i++;
    }
  }

  return array(
    'imgs' => $imgs,
    'thumbs' => $thumbs
  );
}