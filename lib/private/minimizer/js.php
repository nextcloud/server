<?php

require_once 'mediawiki/JavaScriptMinifier.php';

class OC_Minimizer_JS extends OC_Minimizer
{
	protected $contentType = 'application/javascript';

	public function minimizeFiles($files) {
		$js_out = '';
		foreach($files as $file_info) {
			$file = $file_info[0] . '/' . $file_info[2];
			$js_out .= '/* ' . $file . ' */' . "\n";
			$js_out .= file_get_contents($file);
		}
		if (!defined('DEBUG') || !DEBUG) {
			$js_out = JavaScriptMinifier::minify($js_out);
		}
		return $js_out;
	}
}
