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
			$this->assign('enableAvatars', \OC_Config::getValue('enable_avatars', true));
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
		if (!empty(OC_Util::$coreScripts)) {
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
		if (!empty(OC_Util::$coreStyles)) {
			$this->append( 'cssfiles', OC_Helper::linkToRemoteBase('core.css', false) . $versionParameter);
		}
		foreach($cssfiles as $info) {
			$root = $info[0];
			$web = $info[1];
			$file = $info[2];

			$this->append( 'cssfiles', $web.'/'.$file . $versionParameter);
		}
	}

	static public function findStylesheetFiles($styles) {
		// Read the selected theme from the config file
		$theme = OC_Util::getTheme();

		// Read the detected formfactor and use the right file name.
		$fext = self::getFormFactorExtension();

		$locator = new \OC\Template\CSSResourceLocator( $theme, $fext,
			array( OC::$SERVERROOT => OC::$WEBROOT ),
			array( OC::$THIRDPARTYROOT => OC::$THIRDPARTYWEBROOT ));
		$locator->find($styles);
		return $locator->getResources();
	}

	static public function findJavascriptFiles($scripts) {
		// Read the selected theme from the config file
		$theme = OC_Util::getTheme();

		// Read the detected formfactor and use the right file name.
		$fext = self::getFormFactorExtension();

		$locator = new \OC\Template\JSResourceLocator( $theme, $fext,
			array( OC::$SERVERROOT => OC::$WEBROOT ),
			array( OC::$THIRDPARTYROOT => OC::$THIRDPARTYWEBROOT ));
		$locator->find($scripts);
		return $locator->getResources();
	}
}
