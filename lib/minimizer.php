<?php

abstract class OC_Minimizer {
	public function generateETag($files) {
		$etag = '';
		sort($files);
		foreach($files as $file_info) {
			$file = $file_info[0] . '/' . $file_info[2];
			$stat = stat($file);
			$etag .= $file.$stat['mtime'].$stat['size'];
		}
		return md5($etag);
	}

	abstract public function minimizeFiles($files);

	public function output($files, $cache_key) {
		header('Content-Type: '.$this->contentType);
		OC_Response::enableCaching();
		$etag = $this->generateETag($files);
		$cache_key .= '-'.$etag;

		$gzout = false;
		$cache = OC_Cache::getGlobalCache();
		if (!OC_Request::isNoCache() && (!defined('DEBUG') || !DEBUG)) {
			OC_Response::setETagHeader($etag);
			$gzout = $cache->get($cache_key.'.gz');
		}

		if (!$gzout) {
			$out = $this->minimizeFiles($files);
			$gzout = gzencode($out);
			$cache->set($cache_key.'.gz', $gzout);
			OC_Response::setETagHeader($etag);
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

	public function clearCache() {
		$cache = OC_Cache::getGlobalCache();
		$cache->clear('core.css');
		$cache->clear('core.js');
	}
}

if (!function_exists('gzdecode')) {
	function gzdecode($data,$maxlength=null,&$filename='',&$error='')
	{
		if (strcmp(substr($data,0,9),"\x1f\x8b\x8\0\0\0\0\0\0")) {
			return null;  // Not the GZIP format we expect (See RFC 1952)
		}
		return gzinflate(substr($data,10,-8));
	}
}
