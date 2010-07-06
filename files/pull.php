<?php
$token=$_GET['token'];

$file=sys_get_temp_dir().'/'.'remoteCloudFile'.$token;
if(file_exists($file) and is_readable($file) and is_writable($file)){
	readfile($file);
	unlink($file);
}else{
	header("HTTP/1.0 404 Not Found");
}
?>