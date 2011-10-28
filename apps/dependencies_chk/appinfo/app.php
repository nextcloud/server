<?php
$l=new OC_L10N('dependencies_chk');

OC_App::register( array( 
  'order' => 14,
  'id' => 'dependencies_chk',
  'name' => 'Owncloud Install Info' ));

OC_APP::registerAdmin('dependencies_chk','settings');
