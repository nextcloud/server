<?php

require_once('mediawiki/JavaScriptMinifier.php');

class OC_Minimizer_JS extends OC_Minimizer
{
	protected $contentType = 'application/javascript';

	public function findFiles($scripts) {
		// Read the selected theme from the config file
		$theme=OC_Config::getValue( "theme" );

		// Read the detected formfactor and use the right file name.
		$fext = OC_Template::getFormFactorExtension();
		// Add the core js files or the js files provided by the selected theme
		foreach($scripts as $script){
			// Is it in 3rd party?
			if($this->appendIfExist(OC::$THIRDPARTYROOT, OC::$THIRDPARTYWEBROOT, $script.'.js')) {

			// Is it in apps and overwritten by the theme?
			}elseif($this->appendIfExist(OC::$SERVERROOT, OC::$WEBROOT, "themes/$theme/apps/$script$fext.js" )) {
			}elseif($this->appendIfExist(OC::$SERVERROOT, OC::$WEBROOT, "themes/$theme/apps/$script.js" )) {

			// Is it part of an app?
			}elseif($this->appendIfExist(OC::$APPSROOT, OC::$APPSWEBROOT, "apps/$script$fext.js" )) {
			}elseif($this->appendIfExist(OC::$APPSROOT, OC::$APPSWEBROOT, "apps/$script.js" )) {

			// Is it in the owncloud root but overwritten by the theme?
			}elseif($this->appendIfExist(OC::$SERVERROOT, OC::$WEBROOT, "themes/$theme/$script$fext.js" )) {
			}elseif($this->appendIfExist(OC::$SERVERROOT, OC::$WEBROOT, "themes/$theme/$script.js" )) {

			// Is it in the owncloud root ?
			}elseif($this->appendIfExist(OC::$SERVERROOT, OC::$WEBROOT, "$script$fext.js" )) {
			}elseif($this->appendIfExist(OC::$SERVERROOT, OC::$WEBROOT, "$script.js" )) {

			// Is in core but overwritten by a theme?
			}elseif($this->appendIfExist(OC::$SERVERROOT, OC::$WEBROOT, "themes/$theme/core/$script$fext.js" )) {
			}elseif($this->appendIfExist(OC::$SERVERROOT, OC::$WEBROOT, "themes/$theme/core/$script.js" )) {

			// Is it in core?
			}elseif($this->appendIfExist(OC::$SERVERROOT, OC::$WEBROOT, "core/$script$fext.js" )) {
			}elseif($this->appendIfExist(OC::$SERVERROOT, OC::$WEBROOT, "core/$script.js" )) {

			}else{
				echo('js file not found: script:'.$script.' formfactor:'.$fext.' webroot:'.OC::$WEBROOT.' serverroot:'.OC::$SERVERROOT);
				die();
			}
		}
		return $this->files;
	}

	public function minimizeFiles($files) {
		$js_out = '';
		foreach($files as $file_info) {
			$file = $file_info[0] . '/' . $file_info[2];
			$js_out .= '/* ' . $file . ' */' . "\n";
			$js_out .= file_get_contents($file);
		}
		$js_out = JavaScriptMinifier::minify($js_out);
		return $js_out;
	}
}
