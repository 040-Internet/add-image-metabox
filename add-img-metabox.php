<?php
/*
Plugin Name: Add Image Metabox
Plugin URI: http://040.se
Description: Adds a image upload metabox
Version: 0.1
Author: Martin Nilsson
Author URI: http://040.se

This is a revision of a plugin called "Multi Image Metabox" originally created by Willy Bahuaud (http://wordpress.org/plugins/multi-image-metabox/).
This plugin is licensed under GPLv2, you can read about the license here: (http://wordpress.org/about/gpl/).
*/


 /**
 	*
	* list_my_images_slots()
	*
	* This function defines how many imageboxes there should be on
	* each page. The function checks if there is any option for more
	* than the standard 3 imageboxes. If there is, it will use this
	* amount instead.
	*
	**/
	function list_my_images_slots() {
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
	add_action('add_meta_boxes', 'add_img_meta_box');
	function add_img_meta_box() {
		$cpts = apply_filters('images_cpt', array('page'));

		foreach($cpts as $cpt) {
			add_meta_box(
			'add_img_metabox',						// ID attribute of metabox
			__('Add images'),							// Title of metabox visible to user
			'add_img_meta_box_callback',	// Function that prints box in wp-admin
			$cpt,													// Show box for posts, pages, custom, etc.
			'normal',											// Where on the page to show the box
			'core'												// Priority of box in display order
			);
		}
	}


 /**
 	*
	* SAVE METABOX 
	*
	**/
	add_action('save_post', 'save_image_metabox'); 
	function save_image_metabox($post_ID) { 
		if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE )
	        return $post_id;
	   
		update_post_meta($post_ID, 'slide-amount', $_POST['slide-amount']);

   	$list_images = list_my_images_slots();
    foreach($list_images as $k => $i) {
	    if (isset($_POST[$k])) {
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
	function add_img_meta_box_callback($post) {
		$list_images = list_my_images_slots();
		$slideAmount = get_post_meta($post->ID, 'slide-amount', true);

		if($slideAmount) {
			$value = $slideAmount;
		} else {
			$value = '3';
		}

		wp_enqueue_script( 'add-img-mb-draggable-js', plugins_url('/add-img-draggable.js',__FILE__), array( 'jquery' ) );
		wp_enqueue_script( 'add-img-mb-js', plugins_url('/add-img.js',__FILE__), array( 'jquery' ) );
		wp_enqueue_script( 'jquery-ui-draggable' );
		wp_enqueue_style( 'add-img-mb-css', plugins_url('/add-img.css',__FILE__) );

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
			echo '<a class="get-image button-primary" data-num="'.$z.'">'.__('Add New').'</a><a class="del-image button-secondary" data-num="'.$z.'">'.__('Delete').'</a>';
			echo '</div>';
			$z++;
		}
		echo '</div>';

		echo '<div class="right">';
		echo '<div class="add-more-slides" data-action="add">+ Add Slide</div>';
		echo '<div class="remove-slides" data-action="remove">- Remove Slide</div>';
		echo '</div>';
	}


 /**
 	*
	* get_post_slide_images()
	*
	* This function push each image in an array with both the image and its ID.
	* Then it use that array to create the final array that contains the attributes
	* of the image and the thumbnail (src, width, height and resized).
	*
 	**/


	function get_post_slide_images($large = null, $small = null) {
		global $post;
		$the_id = $post->ID;
		$list_images = list_my_images_slots();

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
  * get_the_images()
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
  function get_the_images($showImg = false, $showThumbs = false, $imgSize = 'full', $thumbSize = 'thumbnail') {
    $images = array();
    $imgs = array();
    
    $thumbnails = array();
    $thumbs = array();

    $array = get_post_slide_images($imgSize, $thumbSize);
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