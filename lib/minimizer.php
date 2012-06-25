<?php

abstract class OC_Minimizer {
	public function getLastModified($files) {
		$last_modified = 0;
		foreach($files as $file_info) {
			$file = $file_info[0] . '/' . $file_info[2];
			$filemtime = filemtime($file);
			if ($filemtime > $last_modified) {
				$last_modified = $filemtime;
			}
		}
		return $last_modified;
	}

	abstract public function minimizeFiles($files);

	public function output($files, $cache_key) {
		header('Content-Type: '.$this->contentType);
		OC_Response::enableCaching();
		$last_modified = $this->getLastModified($files);
		OC_Response::setLastModifiedHeader($last_modified);

		$gzout = false;
		$cache = OC_Cache::getGlobalCache();
		if (!OC_Request::isNoCache() && (!defined('DEBUG') || !DEBUG)){
			$gzout = $cache->get($cache_key.'.gz');
			OC_Response::setETagHeader(md5($gzout));
		}

		if (!$gzout) {
			$out = $this->minimizeFiles($files);
			$gzout = gzencode($out);
			$cache->set($cache_key.'.gz', $gzout);
		}
		if ($encoding = OC_Request::acceptGZip()) {
			header('Content-Encoding: '.$encoding);
			$out = $gzout;
		} else {
			$out = gzdecode($gzout);
		}
		header('Content-Length: '.strlen($out));
		echo $out;
	}
}
