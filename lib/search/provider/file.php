<?php

class OC_Search_Provider_File extends OC_Search_Provider{
	function search($query) {
		$files=\OC\Files\Filesystem::search($query, true);
		$results=array();
		$l=OC_L10N::get('lib');
		foreach($files as $fileData) {
			$path = $fileData['path'];
			$mime = $fileData['mimetype'];

			$name = basename($path);
			$text = '';
			$skip = false;
			if($mime=='httpd/unix-directory') {
				$link = OC_Helper::linkTo( 'files', 'index.php', array('dir' => $path));
				$type = (string)$l->t('Files');
			}else{
				$link = OC_Helper::linkToRoute( 'download', array('file' => $path));
				$mimeBase = $fileData['mimepart'];
				switch($mimeBase) {
					case 'audio':
						$skip = true;
						break;
					case 'text':
						$type = (string)$l->t('Text');
						break;
					case 'image':
						$type = (string)$l->t('Images');
						break;
					default:
						if($mime=='application/xml') {
							$type = (string)$l->t('Text');
						}else{
							$type = (string)$l->t('Files');
						}
				}
			}
			if(!$skip) {
				$results[] = new OC_Search_Result($name, $text, $link, $type);
			}
		}
		return $results;
	}
}
