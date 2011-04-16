<?php

// Init owncloud
require_once('../../lib/base.php');

// We send json data
// header( "Content-Type: application/json" );
// Firefox and Konqueror tries to download application/json for me.  --Arthur
header( "Content-Type: text/plain" );

// Check if we are a user
if( !OC_USER::isLoggedIn()){
	echo json_encode( array( "status" => "error", "data" => "Authentication error" ));
	exit();
}

$fileName=$_FILES['file']['name'];
$source=$_FILES['file']['tmp_name'];
$dir = $_POST['dir'];
if(!empty($dir)) $dir .= '/';
$target='/' . stripslashes($dir) . $fileName;
if(isset($_SESSION['username'])
and $_SESSION['username'] and strpos($dir,'..') === false){
	if(OC_FILESYSTEM::fromTmpFile($source,$target)){
		echo json_encode(array( "status" => "success"));
		exit();
	}
}

$error = $_FILES['file']['error'];

echo json_encode(array( 'status' => 'error', 'data' => array('error' => $error)));

?>
