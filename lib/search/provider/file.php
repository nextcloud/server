<?php

class OC_Search_Provider_File extends OC_Search_Provider{
	function search($query) {
		$files=OC_FileCache::search($query,true);
		$results=array();
		foreach($files as $fileData) {
			$file=$fileData['path'];
			$mime=$fileData['mimetype'];
			if($mime=='httpd/unix-directory') {
				$results[]=new OC_Search_Result(basename($file),'',OC_Helper::linkTo( 'files', 'index.php', array('dir' => $file)),'Files');
			}else{
				$mimeBase=$fileData['mimepart'];
				switch($mimeBase) {
					case 'audio':
						break;
					case 'text':
						$results[]=new OC_Search_Result(basename($file),'',OC_Helper::linkTo( 'files', 'download.php', array('dir' => $file) ),'Text');
						break;
					case 'image':
						$results[]=new OC_Search_Result(basename($file),'',OC_Helper::linkTo( 'files', 'download.php', array('dir' => $file) ),'Images');
						break;
					default:
						if($mime=='application/xml') {
							$results[]=new OC_Search_Result(basename($file),'',OC_Helper::linkTo( 'files', 'download.php', array('dir' => $file) ),'Text');
						}else{
							$results[]=new OC_Search_Result(basename($file),'',OC_Helper::linkTo( 'files', 'download.php', array('dir' => $file) ),'Files');
						}
				}
			}
		}
		return $results;
	}
}
