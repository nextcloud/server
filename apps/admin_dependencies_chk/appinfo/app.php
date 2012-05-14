<?php
$l=OC_L10N::get('admin_dependencies_chk');

OCP\App::register( array( 
  'order' => 14,
  'id' => 'admin_dependencies_chk',
  'name' => 'Owncloud Install Info' ));

OCP\App::registerAdmin('admin_dependencies_chk','settings');
