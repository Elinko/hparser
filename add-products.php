<?php

$cats = [];
$tmp = [];
$count = 0;

$tmp2 = [];

foreach ( $xml->children() as $child ) {
    $child = (array) $child;
    $tmp2[] = $child['CATEGORYTEXT'];

    if (strpos($child['CATEGORYTEXT'], 'Kočíky') !== false) {
      // echo 'true';
      $child['CATEGORYTEXT'] = (array) explode('|', $child['CATEGORYTEXT']);
      // echo $child['CATEGORYTEXT'] .'<br>';

      // echo "<pre>";
      // print_r($child);
      // echo "</pre>";
      if((trim($child['CATEGORYTEXT'][0]) == "Baby a deti") || (trim($child['CATEGORYTEXT'][0]) == 'Detský tovar')) {

        $products[] = $child;
      }

      // $cats[] = $child['CATEGORYTEXT'];
    }

    // $count = $count +1;
    //
    // if ($count == 4750) {
    //   // code...
    //   break;
    // }

}

foreach ($products as $key => $product) {

  // echo "<pre>";
  // print_r($product);
  // echo "</pre>";

  if((! post_exists($product['PRODUCT'], 0 , 0, 0)) && (file_get_contents($product['IMGURL']) != '')  ) {

    $product_cat = count($product['CATEGORYTEXT']);
    $product_cat = $product['CATEGORYTEXT'][($product_cat -1)];

    $post_id = wp_insert_post(array(
      'post_title' => $product['PRODUCT'],
      'post_type' => 'product',
      'post_price' => $product['PRICE_VAT'],
      'post_status' => 'publish',
      // 'post_status' => 'publish',
      'post_content' => $product['DESCRIPTION'],
      'post_excerpt' => 'Lorem Ipsum is simply.'
    ));

    update_field( 'url', $product['URL'], $post_id );
    update_field( 'brand', $product['MANUFACTURER'], $post_id );

    update_post_meta( $post_id, '_regular_price', $product['PRICE_VAT'] );
    // update_post_meta( $post_id, '_sale_price', '' );
    update_post_meta( $post_id, '_price', $product['PRICE_VAT'] );
    wp_set_object_terms($post_id, trim($product_cat), 'product_cat');
    wp_set_object_terms($post_id, $product['MANUFACTURER'], 'product_tag');

    attach_product_thumbnail($post_id, $product['IMGURL'], 0);
  }




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


// echo "<pre>";
// print_r($product);
// echo "</pre>";

// echo "<pre>";
// print_r($tmp);
// echo "</pre>";
