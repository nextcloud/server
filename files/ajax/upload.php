<?php

// Init owncloud
require_once('../../lib/base.php');

// We send json data
// header( "Content-Type: application/json" );
// Firefox and Konqueror tries to download application/json for me.  --Arthur
header( "Content-Type: text/plain" );

// Check if we are a user
if( !OC_User::isLoggedIn()){
	echo json_encode( array( "status" => "error", "data" => array( "message" => "Authentication error" )));
	exit();
}

if (!isset($_FILES['files'])) {
	echo json_encode( array( "status" => "error", "data" => array( "message" => "No file was uploaded. Unknown error" )));
	exit();
}
foreach ($_FILES['files']['error'] as $error) {
	if ($error != 0) {
		$errors = array(
			0=>$l->t("There is no error, the file uploaded with success"),
			1=>$l->t("The uploaded file exceeds the upload_max_filesize directive in php.ini"),
			2=>$l->t("The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form"),
			3=>$l->t("The uploaded file was only partially uploaded"),
			4=>$l->t("No file was uploaded"),
			6=>$l->t("Missing a temporary folder")
		);
		echo json_encode( array( "status" => "error", "data" => array( "message" => $errors[$error] )));
		exit();
	}
}
$files=$_FILES['files'];

$dir = $_POST['dir'];
$dir .= '/';
$error='';

$totalSize=0;
foreach($files['size'] as $size){
	$totalSize+=$size;
}
if($totalSize>OC_Filesystem::free_space('/')){
	echo json_encode( array( "status" => "error", "data" => array( "message" => "Not enough space available" )));
	exit();
}

$result=array();
if(strpos($dir,'..') === false){
	$fileCount=count($files['name']);
	for($i=0;$i<$fileCount;$i++){
		$target=stripslashes($dir) . $files['name'][$i];
		if(OC_Filesystem::fromUploadedFile($files['tmp_name'][$i],$target)){
			$result[]=array( "status" => "success", 'mime'=>OC_Filesystem::getMimeType($target),'size'=>OC_Filesystem::filesize($target),'name'=>$files['name'][$i]);
		}
	}
	echo json_encode($result);
	exit();
}else{
	$error='invalid dir';
}

echo json_encode(array( 'status' => 'error', 'data' => array('error' => $error, "file" => $fileName)));

?>
