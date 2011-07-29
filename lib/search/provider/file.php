<?php

class OC_Search_Provider_File extends OC_Search_Provider{
	function search($query){
		$files=OC_Filesystem::search($query);
		$results=array();
		foreach($files as $file){
			if(OC_Filesystem::is_dir($file)){
				$results[]=new OC_Search_Result(basename($file),$file,OC_Helper::linkTo( 'files', 'index.php?dir='.$file ),'Files');
			}else{
				$results[]=new OC_Search_Result(basename($file),$file,OC_Helper::linkTo( 'files', 'download.php?file='.$file ),'Files');
			}
		}
		return $results;
	}
}
