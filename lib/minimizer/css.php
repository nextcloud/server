<?php

require_once('mediawiki/CSSMin.php');

class OC_Minimizer_CSS extends OC_Minimizer
{
	protected $contentType = 'text/css';

	public function minimizeFiles($files) {
		$css_out = '';
		$appswebroot = (string) OC::$APPSWEBROOT;
		$webroot = (string) OC::$WEBROOT;
		foreach($files as $file_info) {
			$file = $file_info[0] . '/' . $file_info[2];
			$css_out .= '/* ' . $file . ' */' . "\n";
			$css = file_get_contents($file);
			if (strpos($file, OC::$APPSROOT) == 0) {
				$css = str_replace('%appswebroot%', $appswebroot, $css);
				$css = str_replace('%webroot%', $webroot, $css);
			}
			$remote = $file_info[1];
			$remote .= '/';
			$remote .= dirname($file_info[2]);
			$css_out .= CSSMin::remap($css, dirname($file), $remote, true);
		}
		if (!defined('DEBUG') || !DEBUG){
			$css_out = CSSMin::minify($css_out);
		}
		return $css_out;
	}
}
