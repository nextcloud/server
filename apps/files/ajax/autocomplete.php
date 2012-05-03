<?php
//provide auto completion of paths for use with jquer ui autocomplete


// Init owncloud


OCP\JSON::checkLoggedIn();

// Get data
$query = $_GET['term'];
$dirOnly=(isset($_GET['dironly']))?($_GET['dironly']=='true'):false;

if($query[0]!='/'){
	$query='/'.$query;
}

if(substr($query,-1,1)=='/'){
	$base=$query;
}else{
	$base=dirname($query);
}

$query=substr($query,strlen($base));

if($base!='/'){
	$query=substr($query,1);
}
$queryLen=strlen($query);
$query=strtolower($query);

// echo "$base - $query";

$files=array();

if(OC_Filesystem::file_exists($base) and OC_Filesystem::is_dir($base)){
	$dh = OC_Filesystem::opendir($base);
	if($dh){
		if(substr($base,-1,1)!='/'){
			$base=$base.'/';
		}
		while (($file = readdir($dh)) !== false) {
			if ($file != "." && $file != ".."){
				if(substr(strtolower($file),0,$queryLen)==$query){
					$item=$base.$file;
					if((!$dirOnly or OC_Filesystem::is_dir($item))){
						$files[]=(object)array('id'=>$item,'label'=>$item,'name'=>$item);
					}
				}
			}
		}
	}
}
OCP\JSON::encodedPrint($files);

?>
