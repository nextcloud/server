<?php
/**
 * @author Adam Williamson <awilliam@redhat.com>
 * @author Bart Visscher <bartv@thisnet.nl>
 * @author Bernhard Posselt <dev@bernhard-posselt.com>
 * @author Christopher Schäpers <kondou@ts.unde.re>
 * @author Clark Tomlinson <fallen013@gmail.com>
 * @author Joas Schilling <nickvergessen@owncloud.com>
 * @author Jörn Friedrich Dreyer <jfd@butonic.de>
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author Michael Gapczynski <GapczynskiM@gmail.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Remco Brenninkmeijer <requist1@starmail.nl>
 * @author Robin Appelman <icewind@owncloud.com>
 * @author Robin McCorkell <rmccorkell@karoshi.org.uk>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Victor Dubiniuk <dubiniuk@owncloud.com>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */
use Assetic\Asset\AssetCollection;
use Assetic\Asset\FileAsset;
use Assetic\AssetWriter;
use Assetic\Filter\CssImportFilter;
use Assetic\Filter\CssMinFilter;
use Assetic\Filter\CssRewriteFilter;
use Assetic\Filter\JSMinFilter;
use OC\Assetic\SeparatorFilter; // waiting on upstream

/**
 * Copyright (c) 2012 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

class OC_TemplateLayout extends OC_Template {

	private static $versionHash = '';

	/**
	 * @var \OCP\IConfig
	 */
	private $config;

	/**
	 * @param string $renderAs
	 * @param string $appId application id
	 */
	public function __construct( $renderAs, $appId = '' ) {

		// yes - should be injected ....
		$this->config = \OC::$server->getConfig();

		// Decide which page we show
		if($renderAs == 'user') {
			parent::__construct( 'core', 'layout.user' );
			if(in_array(OC_App::getCurrentApp(), ['settings','admin', 'help']) !== false) {
				$this->assign('bodyid', 'body-settings');
			}else{
				$this->assign('bodyid', 'body-user');
			}

			// Update notification
			if($this->config->getSystemValue('updatechecker', true) === true &&
				OC_User::isAdminUser(OC_User::getUser())) {
				$updater = new \OC\Updater(\OC::$server->getHTTPHelper(),
					\OC::$server->getConfig());
				$data = $updater->check();

				if(isset($data['version']) && $data['version'] != '' and $data['version'] !== Array()) {
					$this->assign('updateAvailable', true);
					$this->assign('updateVersion', $data['versionstring']);
					$this->assign('updateLink', $data['web']);
					\OCP\Util::addScript('core', 'update-notification');
				} else {
					$this->assign('updateAvailable', false); // No update available or not an admin user
				}
			} else {
				$this->assign('updateAvailable', false); // Update check is disabled
			}

			// Add navigation entry

			$this->assign( 'application', '');
			$this->assign( 'appid', $appId );
			$navigation = OC_App::getNavigation();
			$this->assign( 'navigation', $navigation);
			$settingsNavigation = OC_App::getSettingsNavigation();
			$this->assign( 'settingsnavigation', $settingsNavigation);
			foreach($navigation as $entry) {
				if ($entry['active']) {
					$this->assign( 'application', $entry['name'] );
					break;
				}
			}
			
			foreach($settingsNavigation as $entry) {
				if ($entry['active']) {
					$this->assign( 'application', $entry['name'] );
					break;
				}
			}
			$userDisplayName = OC_User::getDisplayName();
			$this->assign('user_displayname', $userDisplayName);
			$this->assign('user_uid', OC_User::getUser());
			$this->assign('appsmanagement_active', strpos(\OC::$server->getRequest()->getRequestUri(), \OC::$server->getURLGenerator()->linkToRoute('settings.AppSettings.viewApps')) === 0 );
			$this->assign('enableAvatars', $this->config->getSystemValue('enable_avatars', true));
			$this->assign('userAvatarSet', \OC_Helper::userAvatarSet(OC_User::getUser()));
		} else if ($renderAs == 'error') {
			parent::__construct('core', 'layout.guest', '', false);
			$this->assign('bodyid', 'body-login');
		} else if ($renderAs == 'guest') {
			parent::__construct('core', 'layout.guest');
			$this->assign('bodyid', 'body-login');
		} else {
			parent::__construct('core', 'layout.base');

		}
		// Send the language to our layouts
		$this->assign('language', OC_L10N::findLanguage());


		if(empty(self::$versionHash)) {
			$v = OC_App::getAppVersions();
			$v['core'] = implode('.', \OC_Util::getVersion());
			self::$versionHash = md5(implode(',', $v));
		}

		$useAssetPipeline = self::isAssetPipelineEnabled();
		if ($useAssetPipeline) {
			$this->append( 'jsfiles', OC_Helper::linkToRoute('js_config', array('v' => self::$versionHash)));
			$this->generateAssets();
		} else {
			// Add the js files
			$jsFiles = self::findJavascriptFiles(OC_Util::$scripts);
			$this->assign('jsfiles', array(), false);
			if ($this->config->getSystemValue('installed', false) && $renderAs != 'error') {
				$this->append( 'jsfiles', OC_Helper::linkToRoute('js_config', array('v' => self::$versionHash)));
			}
			foreach($jsFiles as $info) {
				$web = $info[1];
				$file = $info[2];
				$this->append( 'jsfiles', $web.'/'.$file . '?v=' . self::$versionHash);
			}

			// Add the css files
			$cssFiles = self::findStylesheetFiles(OC_Util::$styles);
			$this->assign('cssfiles', array());
			foreach($cssFiles as $info) {
				$web = $info[1];
				$file = $info[2];

				$this->append( 'cssfiles', $web.'/'.$file . '?v=' . self::$versionHash);
			}
		}
	}

	/**
	 * @param array $styles
	 * @return array
	 */
	static public function findStylesheetFiles($styles) {
		// Read the selected theme from the config file
		$theme = OC_Util::getTheme();

		$locator = new \OC\Template\CSSResourceLocator(
			OC::$server->getLogger(),
			$theme,
			array( OC::$SERVERROOT => OC::$WEBROOT ),
			array( OC::$THIRDPARTYROOT => OC::$THIRDPARTYWEBROOT ));
		$locator->find($styles);
		return $locator->getResources();
	}

	/**
	 * @param array $scripts
	 * @return array
	 */
	static public function findJavascriptFiles($scripts) {
		// Read the selected theme from the config file
		$theme = OC_Util::getTheme();

		$locator = new \OC\Template\JSResourceLocator(
			OC::$server->getLogger(),
			$theme,
			array( OC::$SERVERROOT => OC::$WEBROOT ),
			array( OC::$THIRDPARTYROOT => OC::$THIRDPARTYWEBROOT ));
		$locator->find($scripts);
		return $locator->getResources();
	}

	public function generateAssets() {
		$assetDir = \OC::$server->getConfig()->getSystemValue('assetdirectory', \OC::$SERVERROOT);
		$jsFiles = self::findJavascriptFiles(OC_Util::$scripts);
		$jsHash = self::hashFileNames($jsFiles);

		if (!file_exists("$assetDir/assets/$jsHash.js")) {
			$jsFiles = array_map(function ($item) {
				$root = $item[0];
				$file = $item[2];
				// no need to minifiy minified files
				if (substr($file, -strlen('.min.js')) === '.min.js') {
					return new FileAsset($root . '/' . $file, array(
						new SeparatorFilter(';')
					), $root, $file);
				}
				return new FileAsset($root . '/' . $file, array(
					new JSMinFilter(),
					new SeparatorFilter(';')
				), $root, $file);
			}, $jsFiles);
			$jsCollection = new AssetCollection($jsFiles);
			$jsCollection->setTargetPath("assets/$jsHash.js");

			$writer = new AssetWriter($assetDir);
			$writer->writeAsset($jsCollection);
		}

		$cssFiles = self::findStylesheetFiles(OC_Util::$styles);
		$cssHash = self::hashFileNames($cssFiles);

		if (!file_exists("$assetDir/assets/$cssHash.css")) {
			$cssFiles = array_map(function ($item) {
				$root = $item[0];
				$file = $item[2];
				$assetPath = $root . '/' . $file;
				$sourceRoot =  \OC::$SERVERROOT;
				$sourcePath = substr($assetPath, strlen(\OC::$SERVERROOT));
				return new FileAsset(
					$assetPath,
					array(
						new CssRewriteFilter(),
						new CssMinFilter(),
						new CssImportFilter()
					),
					$sourceRoot,
					$sourcePath
				);
			}, $cssFiles);
			$cssCollection = new AssetCollection($cssFiles);
			$cssCollection->setTargetPath("assets/$cssHash.css");

			$writer = new AssetWriter($assetDir);
			$writer->writeAsset($cssCollection);
		}

		$this->append('jsfiles', OC_Helper::linkTo('assets', "$jsHash.js"));
		$this->append('cssfiles', OC_Helper::linkTo('assets', "$cssHash.css"));
	}

	/**
	 * Converts the absolute file path to a relative path from \OC::$SERVERROOT
	 * @param string $filePath Absolute path
	 * @return string Relative path
	 * @throws Exception If $filePath is not under \OC::$SERVERROOT
	 */
	public static function convertToRelativePath($filePath) {
		$relativePath = explode(\OC::$SERVERROOT, $filePath);
		if(count($relativePath) !== 2) {
			throw new \Exception('$filePath is not under the \OC::$SERVERROOT');
		}

		return $relativePath[1];
	}

	/**
	 * @param array $files
	 * @return string
	 */

	private static function hashFileNames($files) {
		foreach($files as $i => $file) {
			try {
				$files[$i] = self::convertToRelativePath($file[0]).'/'.$file[2];
			} catch (\Exception $e) {
				$files[$i] = $file[0].'/'.$file[2];
			}
		}

		sort($files);
		// include the apps' versions hash to invalidate the cached assets
		$files[] = self::$versionHash;
		return hash('md5', implode('', $files));
	}
}
