<?php

define( 'WP_USE_THEMES', false );
require('../wp-blog-header.php' );
require( '../wp-admin/includes/post.php' );
//
// $title = $desc = $img = $url = $brand = $priceFrom = $priceTo = $reviews = $ml = $type = '';

for ($i=1; $i < 4; $i++) {

  $url = 'https://parfemy.heureka.cz/?f=' . $i;
  $content = file_get_contents($url);
  $content = explode('<div id="product-container">', $content);
  $content = explode('<div class="p', $content[1]);

  foreach ($content as $key => $value) {

    // if ($key ==10) {
    //    break;
    // }

    $products_param = get_products_param($value);
    $title = $products_param[0];
    $desc = $products_param[1];
    $img = $products_param[2];
    $url = $products_param[3];
    $brand = $products_param[4];
    $priceFrom = $products_param[5];
    $priceTo = $products_param[6];
    $reviews = $products_param[7];
    $ml = $products_param[8];
    $type =$products_param[9];
    $sex =$products_param[10];

    echo "<pre>";
    print_r($title);
    echo "</pre>";


    // Insert post if everything exist
    if(($title!='') && ($desc!='') && ($img!='') && ($url!='') && ($brand!='') && ($priceFrom!='') ) {

      if((! $existID = post_exists($title, 0 , 0, 0))) {
        $post_id = wp_insert_post(array(
          'post_title' => $title,
          'post_type' => 'parfemy',
          'post_status' => 'publish',
          'post_content' => $desc,
        ));
        attach_product_thumbnail($post_id, $img, 0);

      } else { //update post

        wp_update_post(array(
          'ID' => $existID,
          'post_title' => $title,
          'post_type' => 'parfemy',
          'post_status' => 'publish',
          'post_content' => $desc,
        ));
        $post_id = $existID;
      }

      update_field( 'url', $url, $post_id );
      update_field( 'cena', $priceFrom, $post_id );
      update_field( 'recenzie', $reviews, $post_id );
      update_field( 'ml', $ml, $post_id );
      update_field( 'typ', $type, $post_id );

      $args = array(
          'post_type' => 'parfemy',
          'hide_empty' => 0,
      );
      $categories = get_categories($args);
      $parentID = '';
      $subCat = '';

      foreach ($categories as $key => $category) {
        if((trim($category->name)) == trim($sex)) {
          // echo 'zhoda parenta ';
          $parentID = trim($category->term_id);
        }
        if((trim($category->name)) == trim($brand)) {
          $subCat = trim($brand);
        }
      }

      // if parrent doesnt exist
      if($parentID == '') {
        $parentID = wp_insert_term( trim($sex), 'category', [ ] );
        $parentID = $parentID['term_id'];
      }

      // if subcat doesnt exist
      if($subCat == '') {
        wp_insert_term( trim($brand), 'category', [
          // 'post_type' => 'parfemy',
          'alias_of'    => '',
          'description' => '',
          'parent' => $parentID,
          ]
        );
      }

      wp_set_object_terms($post_id, $brand, 'category');

    } else {
      // echo '<br> chybny ';
    }
  } //end foreach
} // end for

function set_product_params() {

}

function get_products_param($value) {
  $img = explode('<img src="', $value);
  $img = explode('" alt=', $img[1]);
  $img = $img[0];

  $rating = explode('<strong class="textual">', $value);
  $rating = explode('(Perfektní)', $rating[1]);
  $rating = $rating[0];

  $title = explode('<h2>', $value);
  $title = explode('>', $title[1]);
  $title = explode('<', $title[1]);
  $title = $title[0];

  $params = explode('<p class="params">', $value);
  $params = explode('</p>', $params[1]);
  $params = explode(',', $params[0]);

  // var_dump($params);

  if(count($params) < 4) {
    $type = $params[0];
    $sex = $params[1];
    // $brand = 'Mix';
  } else {
    $type = $params[0];
    $sex = $params[1];
    // $brand = trim($params[2]);
  }

  //new Branding
  $brand = preg_split('/\s+/', $title);
  if(trim($brand[0]) == 'Dolce') {
    $brand = trim($brand[0]) . ' ' . trim($brand[1]). ' ' . trim($brand[2]);

  } elseif (trim($brand[0]) == 'Guess' || trim($brand[0]) == 'Moschino' || trim($brand[0]) == 'Chanel' || trim($brand[0]) == 'Chloé' || trim($brand[0]) == 'Bvlgari' || trim($brand[0]) == 'DKNY' || trim($brand[0]) == 'Lancôme' || trim($brand[0]) == 'Lacoste' || trim($brand[0]) =='Lanvin' || trim($brand[0]) =='Versace') {
    $brand = trim($brand[0]);

  } else {
    $brand = trim($brand[0]) . ' ' . trim($brand[1]);
  }


  $ml = $params[ (count($params) -1) ];
  $ml =  explode('<abbr title="Objem">', $ml);
  $ml =  explode('</abbr>', $ml[1]);
  $ml = $ml[0];

  $reviews = explode('<span class="review-count">', $value);
  $reviews = explode('>', $reviews[1]);
  $reviews = explode('<', $reviews[1]);
  $reviews = $reviews[0];

  $priceFrom = explode('<span class="priceFrom">', $value);
  $priceFrom = explode('</span>', $priceFrom[1]);
  $priceFrom = $priceFrom[0];

  $priceTo = explode('<span class="priceTo">', $value);
  $priceTo = explode('</span>', $priceTo[1]);
  $priceTo = $priceTo[0];

  $desc = explode('<p class="desc">', $value);
  $desc = explode('</p>', $desc[1]);
  $desc = $desc[0];

  $url = explode('<a href="//', $value);
  $url = explode('">', $url[1]);
  $url = $url[0];
  return array($title, $desc, $img, $url, $brand, $priceFrom, $priceTo, $reviews, $ml, $type, $sex);
}

function attach_product_thumbnail($post_id, $url, $flag){
    $image_url = $url;
    $url_array = explode('/',$url);
    $image_name = $url_array[count($url_array)-1];
    $image_data = file_get_contents($image_url); // Get image data
    $upload_dir = wp_upload_dir(); // Set upload folder
    $unique_file_name = wp_unique_filename( $upload_dir['path'], $image_name ); //    Generate unique name
    $filename = basename( $unique_file_name ); // Create image file name
    $filename = explode('ft',$filename);
    $filename = $filename[0];
    // Check folder permission and define file location
    // var_dump($filename);
    if( wp_mkdir_p( $upload_dir['path'] ) ) {
        $file = $upload_dir['path'] . '/' . $filename;
    } else {
        $file = $upload_dir['basedir'] . '/' . $filename;
    }
    // Create the image file on the server
    file_put_contents( $file, $image_data );
    // Check image file type
    $wp_filetype = wp_check_filetype( $filename, null );
    // Set attachment data
    $attachment = array(
        'post_mime_type' => $wp_filetype['type'],
        'post_title' => sanitize_file_name( $filename ),
        'post_content' => '',
        'post_status' => 'inherit'
    );
    // Create the attachment
    $attach_id = wp_insert_attachment( $attachment, $file, $post_id );


    // Include image.php
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    // Define attachment metadata
    $attach_data = wp_generate_attachment_metadata( $attach_id, $file );
    // Assign metadata to attachment
    wp_update_attachment_metadata( $attach_id, $attach_data );
    // asign to feature image
    if( $flag == 0){
        // And finally assign featured image to post
        set_post_thumbnail( $post_id, $attach_id );
    }
    // assign to the product gallery
    if( $flag == 1 ){
        // Add gallery image to product
        $attach_id_array = get_post_meta($post_id,'_product_image_gallery', true);
        $attach_id_array .= ','.$attach_id;
        update_post_meta($post_id,'_product_image_gallery',$attach_id_array);
    }
}


// include 'create-categories.php';

// include 'add-products.php';
