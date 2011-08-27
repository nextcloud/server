<?php

OC_App::register( array( 
  'order' => 11,
  'id' => 'test_db',
  'name' => 'Test' ));
  
OC_App::addNavigationEntry( array( 
  'id' => 'test_db_index',
  'order' => 11,
  'href' => OC_Helper::linkTo( 'test_db', 'index.php' ),
/*
  'icon' => OC_Helper::imagePath( 'openstreetgame', 'icon.svg' ),
*/
  'name' => 'Test DB' ));
  
?>
