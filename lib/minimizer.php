<?php

abstract class OC_Minimizer {
	public function generateETag($files) {
		$fullpath_files = array();
		foreach($files as $file_info) {
			$fullpath_files[] = $file_info[0] . '/' . $file_info[2];
		}
		return OC_Cache::generateCacheKeyFromFiles($fullpath_files);
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
		// on some systems (e.g. SLES 11, but not Ubuntu) mod_deflate and zlib compression will compress the output twice.
		// This results in broken core.css and  core.js. To avoid it, we switch off zlib compression.
		// Since mod_deflate is still active, Apache will compress what needs to be compressed, i.e. no disadvantage.
		if(function_exists('apache_get_modules') && ini_get('zlib.output_compression') && in_array('mod_deflate', apache_get_modules())) {
			ini_set('zlib.output_compression', 'Off');
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
	function gzdecode($data, $maxlength=null, &$filename='', &$error='')
	{
		if (strcmp(substr($data, 0, 9),"\x1f\x8b\x8\0\0\0\0\0\0")) {
			return null;  // Not the GZIP format we expect (See RFC 1952)
		}
		return gzinflate(substr($data, 10, -8));
	}
}
