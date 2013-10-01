<?php

require_once 'mediawiki/CSSMin.php';

class OC_Minimizer_CSS extends OC_Minimizer
{
	protected $contentType = 'text/css';

	public function minimizeFiles($files) {
		$css_out = '';
		$webroot = (string) OC::$WEBROOT;
		foreach($files as $file_info) {
			$file = $file_info[0] . '/' . $file_info[2];
			$css_out .= '/* ' . $file . ' */' . "\n";
			$css = file_get_contents($file);

			$in_root = false;
			foreach(OC::$APPSROOTS as $app_root) {
				if(strpos($file, $app_root['path'].'/') === 0) {
					$in_root = rtrim($webroot.$app_root['url'], '/');
					break;
				}
			}
			if ($in_root !== false) {
				$css = str_replace('%appswebroot%', $in_root, $css);
				$css = str_replace('%webroot%', $webroot, $css);
			}
			$remote = $file_info[1];
			$remote .= '/';
			$remote .= dirname($file_info[2]);
			$css_out .= CSSMin::remap($css, dirname($file), $remote, true);
		}
		if (!defined('DEBUG') || !DEBUG) {
			$css_out = CSSMin::minify($css_out);
		}
		return $css_out;
	}
}
