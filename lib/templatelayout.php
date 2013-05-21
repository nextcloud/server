<?php
/**
 * Copyright (c) 2012 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

class OC_TemplateLayout extends OC_Template {
	public function __construct( $renderas ) {
		// Decide which page we show

		if( $renderas == 'user' ) {
			parent::__construct( 'core', 'layout.user' );
			if(in_array(OC_APP::getCurrentApp(), array('settings','admin', 'help'))!==false) {
				$this->assign('bodyid', 'body-settings');
			}else{
				$this->assign('bodyid', 'body-user');
			}

			// Update notification
			if(OC_Config::getValue('updatechecker', true) === true) {
				$data=OC_Updater::check();
				if(isset($data['version']) && $data['version'] != '' and $data['version'] !== Array() && OC_User::isAdminUser(OC_User::getUser())) {
					$this->assign('updateAvailable', true);
					$this->assign('updateVersion', $data['versionstring']);
					$this->assign('updateLink', $data['web']);
				} else {
					$this->assign('updateAvailable', false); // No update available or not an admin user
				}
			} else {
				$this->assign('updateAvailable', false); // Update check is disabled
			}

			// Add navigation entry
			$this->assign( 'application', '', false );
			$navigation = OC_App::getNavigation();
			$this->assign( 'navigation', $navigation);
			$this->assign( 'settingsnavigation', OC_App::getSettingsNavigation());
			foreach($navigation as $entry) {
				if ($entry['active']) {
					$this->assign( 'application', $entry['name'] );
					break;
				}
			}
			$user_displayname = OC_User::getDisplayName();
			$this->assign( 'user_displayname', $user_displayname );
			$this->assign( 'user_uid', OC_User::getUser() );
		} else if ($renderas == 'guest' || $renderas == 'error') {
			parent::__construct('core', 'layout.guest');
		} else {
			parent::__construct('core', 'layout.base');
		}
		$versionParameter = '?v=' . md5(implode(OC_Util::getVersion()));
		// Add the js files
		$jsfiles = self::findJavascriptFiles(OC_Util::$scripts);
		$this->assign('jsfiles', array(), false);
		if (OC_Config::getValue('installed', false) && $renderas!='error') {
			$this->append( 'jsfiles', OC_Helper::linkToRoute('js_config') . $versionParameter);
		}
		if (!empty(OC_Util::$core_scripts)) {
			$this->append( 'jsfiles', OC_Helper::linkToRemoteBase('core.js', false) . $versionParameter);
		}
		foreach($jsfiles as $info) {
			$root = $info[0];
			$web = $info[1];
			$file = $info[2];
			$this->append( 'jsfiles', $web.'/'.$file . $versionParameter);
		}

		// Add the css files
		$cssfiles = self::findStylesheetFiles(OC_Util::$styles);
		$this->assign('cssfiles', array());
		if (!empty(OC_Util::$core_styles)) {
			$this->append( 'cssfiles', OC_Helper::linkToRemoteBase('core.css', false) . $versionParameter);
		}
		foreach($cssfiles as $info) {
			$root = $info[0];
			$web = $info[1];
			$file = $info[2];

			$this->append( 'cssfiles', $web.'/'.$file . $versionParameter);
		}
	}

	/*
	 * @brief append the $file-url if exist at $root
	 * @param $files array to append file info to
	 * @param $root path to check
	 * @param $web base for path
	 * @param $file the filename
	 */
	static public function appendIfExist(&$files, $root, $webroot, $file) {
		if (is_file($root.'/'.$file)) {
			$files[] = array($root, $webroot, $file);
			return true;
		}
		return false;
	}

	static public function findStylesheetFiles($styles) {
		// Read the selected theme from the config file
		$theme = OC_Util::getTheme();

		// Read the detected formfactor and use the right file name.
		$fext = self::getFormFactorExtension();

		$files = array();
		foreach($styles as $style) {
			// is it in 3rdparty?
			if(strpos($style, '3rdparty') === 0 &&
				self::appendIfExist($files, OC::$THIRDPARTYROOT, OC::$THIRDPARTYWEBROOT, $style.'.css')) {

			// or in the owncloud root?
			}elseif(self::appendIfExist($files, OC::$SERVERROOT, OC::$WEBROOT, "$style$fext.css" )) {
			}elseif(self::appendIfExist($files, OC::$SERVERROOT, OC::$WEBROOT, "$style.css" )) {

			// or in core ?
			}elseif(self::appendIfExist($files, OC::$SERVERROOT, OC::$WEBROOT, "core/$style$fext.css" )) {
			}elseif(self::appendIfExist($files, OC::$SERVERROOT, OC::$WEBROOT, "core/$style.css" )) {

			}else{
				$app = substr($style, 0, strpos($style, '/'));
				$style = substr($style, strpos($style, '/')+1);
				$app_path = OC_App::getAppPath($app);
				$app_url = OC::$WEBROOT . '/index.php/apps/' . $app;
				if(self::appendIfExist($files, $app_path, $app_url, "$style$fext.css")) {
				}
				elseif(self::appendIfExist($files, $app_path, $app_url, "$style.css")) {
				}
				else {
					echo('css file not found: style:'.$style.' formfactor:'.$fext
						.' webroot:'.OC::$WEBROOT.' serverroot:'.OC::$SERVERROOT);
					die();
				}
			}
		}
		// Add the theme css files. you can override the default values here
		if(!empty($theme)) {
			foreach($styles as $style) {
				     if(self::appendIfExist($files, OC::$SERVERROOT, OC::$WEBROOT, "themes/$theme/apps/$style$fext.css" )) {
				}elseif(self::appendIfExist($files, OC::$SERVERROOT, OC::$WEBROOT, "themes/$theme/apps/$style.css" )) {

				}elseif(self::appendIfExist($files, OC::$SERVERROOT, OC::$WEBROOT, "themes/$theme/$style$fext.css" )) {
				}elseif(self::appendIfExist($files, OC::$SERVERROOT, OC::$WEBROOT, "themes/$theme/$style.css" )) {

				}elseif(self::appendIfExist($files, OC::$SERVERROOT, OC::$WEBROOT, "themes/$theme/core/$style$fext.css" )) {
				}elseif(self::appendIfExist($files, OC::$SERVERROOT, OC::$WEBROOT, "themes/$theme/core/$style.css" )) {
				}
			}
		}
		return $files;
	}

	static public function findJavascriptFiles($scripts) {
		// Read the selected theme from the config file
		$theme = OC_Util::getTheme();

		// Read the detected formfactor and use the right file name.
		$fext = self::getFormFactorExtension();

		$files = array();
		foreach($scripts as $script) {
			// Is it in 3rd party?
			if(strpos($script, '3rdparty') === 0 &&
				self::appendIfExist($files, OC::$THIRDPARTYROOT, OC::$THIRDPARTYWEBROOT, $script.'.js')) {

			// Is it in apps and overwritten by the theme?
			}elseif(self::appendIfExist($files, OC::$SERVERROOT, OC::$WEBROOT, "themes/$theme/apps/$script$fext.js" )) {
			}elseif(self::appendIfExist($files, OC::$SERVERROOT, OC::$WEBROOT, "themes/$theme/apps/$script.js" )) {

			// Is it in the owncloud root but overwritten by the theme?
			}elseif(self::appendIfExist($files, OC::$SERVERROOT, OC::$WEBROOT, "themes/$theme/$script$fext.js" )) {
			}elseif(self::appendIfExist($files, OC::$SERVERROOT, OC::$WEBROOT, "themes/$theme/$script.js" )) {

			// Is it in the owncloud root ?
			}elseif(self::appendIfExist($files, OC::$SERVERROOT, OC::$WEBROOT, "$script$fext.js" )) {
			}elseif(self::appendIfExist($files, OC::$SERVERROOT, OC::$WEBROOT, "$script.js" )) {

			// Is in core but overwritten by a theme?
			}elseif(self::appendIfExist($files, OC::$SERVERROOT, OC::$WEBROOT, "themes/$theme/core/$script$fext.js" )) {
			}elseif(self::appendIfExist($files, OC::$SERVERROOT, OC::$WEBROOT, "themes/$theme/core/$script.js" )) {

			// Is it in core?
			}elseif(self::appendIfExist($files, OC::$SERVERROOT, OC::$WEBROOT, "core/$script$fext.js" )) {
			}elseif(self::appendIfExist($files, OC::$SERVERROOT, OC::$WEBROOT, "core/$script.js" )) {

			}else{
				// Is it part of an app?
				$app = substr($script, 0, strpos($script, '/'));
				$script = substr($script, strpos($script, '/')+1);
				$app_path = OC_App::getAppPath($app);
				$app_url = OC_App::getAppWebPath($app);
				if(self::appendIfExist($files, $app_path, $app_url, "$script$fext.js")) {
				}
				elseif(self::appendIfExist($files, $app_path, $app_url, "$script.js")) {
				}
				else {
					echo('js file not found: script:'.$script.' formfactor:'.$fext
						.' webroot:'.OC::$WEBROOT.' serverroot:'.OC::$SERVERROOT);
					die();
				}
			}
		}
		return $files;
	}
}
