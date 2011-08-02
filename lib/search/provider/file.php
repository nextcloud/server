<?php

class OC_Search_Provider_File extends OC_Search_Provider{
	function search($query){
		$files=OC_Filesystem::search($query);
		$results=array();
		foreach($files as $file){
			if(OC_Filesystem::is_dir($file)){
				$results[]=new OC_Search_Result(basename($file),'',OC_Helper::linkTo( 'files', 'index.php?dir='.$file ),'Files');
			}else{
				$mime=OC_Filesystem::getMimeType($file);
				$mimeBase=substr($mime,0,strpos($mime,'/'));
				switch($mimeBase){
					case 'audio':
						break;
					case 'text':
						$results[]=new OC_Search_Result(basename($file),'',OC_Helper::linkTo( 'files', 'download.php?file='.$file ),'Text');
						break;
					case 'image':
						$results[]=new OC_Search_Result(basename($file),'',OC_Helper::linkTo( 'files', 'download.php?file='.$file ),'Images');
						break;
					default:
						if($mime=='application/xml'){
							$results[]=new OC_Search_Result(basename($file),'',OC_Helper::linkTo( 'files', 'download.php?file='.$file ),'Text');
						}else{
							$results[]=new OC_Search_Result(basename($file),'',OC_Helper::linkTo( 'files', 'download.php?file='.$file ),'Files');
						}
				}
			}
		}
		return $results;
	}
}
