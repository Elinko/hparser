<?php

$mainCats = [];
$cats = [];

// select categories with kociky
foreach ( $xml->children() as $child ) {
  $child = (array) $child;

  $tmp = (array) explode('|', $child['CATEGORYTEXT']);

  if (strpos($child['CATEGORYTEXT'], 'Kočíky') !== false) {

    if(strpos($tmp[1], 'Kočíky') || strpos($tmp[2], 'Kočíky') ) {

      $cats[] = trim($child['CATEGORYTEXT']);

      $thirdCat = trim($tmp[2]);

      if($thirdCat == "") {
        $mainCats[] = 'Kočíky';
      } else {
        $mainCats[] = $thirdCat;
      }
    }
  }
}


$cats = array_unique($cats);
$mainCats = array_unique($mainCats);



// var_dump($cats);

// create main categories
foreach ($mainCats as $key => $cat) {
  $id = (array) wp_insert_term( $cat, 'product_cat', []);

  $mainCats[$key] = array(
    'name' => $mainCats[$key],
    'id' => $id['term_id']
  );
}

// var_dump($mainCats[3]['id']);
//
// echo '<br>';
//
// var_dump($mainCats);

// create sub categories
foreach ($cats as $key => $category) {
    // var_dump($category);

    $category = (array) explode('|', $category);

    foreach ($mainCats as $key => $value) {

      if(trim($value['name']) == trim($category[2])) {
        $parentId = $value;
      }
    }

    wp_insert_term( $category[3], 'product_cat', [
      'alias_of'    => '',
      'description' => '',
      'parent' => $parentId['id'],
      ]
    );

    if ( is_wp_error( $term ) ) {
    	$term_id = $term->error_data['term_exists'] ?? null;
    } else {
    	$term_id = $term['term_id'];
    }
}


// echo "<pre>";
// print_r($mainCats);
// echo "</pre>";
