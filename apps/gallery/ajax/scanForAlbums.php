<?php

require_once('../../../lib/base.php');
require_once('../lib_scanner.php');

if (!OC_User::IsLoggedIn()) {
  echo json_encode(array('status' => 'error', 'message' => 'You need to log in'));
  exit();
}

echo json_encode(array( 'status' => 'success', 'albums' => OC_GALLERY_SCANNER::scan('')));
//echo json_encode(array('status' => 'success', 'albums' => array(array('name' => 'test', 'imagesCount' => 1, 'images' => array('dupa')))));

?>
