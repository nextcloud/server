<?php

require_once('mediawiki/CSSMin.php');

class OC_Minimizer_CSS extends OC_Minimizer
{
	protected $contentType = 'text/css';

	public function findFiles($styles) {
		// Read the selected theme from the config file
		$theme=OC_Config::getValue( "theme" );

		// Read the detected formfactor and use the right file name.
		$fext = OC_Template::getFormFactorExtension();
		foreach($styles as $style){
			// is it in 3rdparty?
			if($this->appendIfExist(OC::$THIRDPARTYROOT, OC::$THIRDPARTYWEBROOT, $style.'.css')) {

			// or in apps?
			}elseif($this->appendIfExist(OC::$APPSROOT, OC::$APPSWEBROOT, "apps/$style$fext.css" )) {
			}elseif($this->appendIfExist(OC::$APPSROOT, OC::$APPSWEBROOT, "apps/$style.css" )) {

			// or in the owncloud root?
			}elseif($this->appendIfExist(OC::$SERVERROOT, OC::$WEBROOT, "$style$fext.css" )) {
			}elseif($this->appendIfExist(OC::$SERVERROOT, OC::$WEBROOT, "$style.css" )) {

			// or in core ?
			}elseif($this->appendIfExist(OC::$SERVERROOT, OC::$WEBROOT, "core/$style$fext.css" )) {
			}elseif($this->appendIfExist(OC::$SERVERROOT, OC::$WEBROOT, "core/$style.css" )) {

			}else{
				echo('css file not found: style:'.$style.' formfactor:'.$fext.' webroot:'.OC::$WEBROOT.' serverroot:'.OC::$SERVERROOT);
				die();
			}
		}
		// Add the theme css files. you can override the default values here
		if(!empty($theme)) {
			foreach($styles as $style){
				     if($this->appendIfExist(OC::$SERVERROOT, OC::$WEBROOT, "themes/$theme/apps/$style$fext.css" )) {
				}elseif($this->appendIfExist(OC::$SERVERROOT, OC::$WEBROOT, "themes/$theme/apps/$style.css" )) {

				}elseif($this->appendIfExist(OC::$SERVERROOT, OC::$WEBROOT, "themes/$theme/$style$fext.css" )) {
				}elseif($this->appendIfExist(OC::$SERVERROOT, OC::$WEBROOT, "themes/$theme/$style.css" )) {

				}elseif($this->appendIfExist(OC::$SERVERROOT, OC::$WEBROOT, "themes/$theme/core/$style$fext.css" )) {
				}elseif($this->appendIfExist(OC::$SERVERROOT, OC::$WEBROOT, "themes/$theme/core/$style.css" )) {
				}
			}
		}
		return $this->files;
	}

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
		$css_out = CSSMin::minify($css_out);
		return $css_out;
	}
}
