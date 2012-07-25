<?php
/**
 * kate: replace-tabs off; indent-mode Normal; keep-extra-spaces: off; tab-indents: on; 
 * Copyright (c) 2012 Thomas Tanghus <thomas@tanghus.net>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

function phptal_tales_remote($exp, $nothrow) {
	//$exp = trim($exp, ' \t\r\n/');
	error_log(phptal_tales($exp, $nothrow));
	return "OCP\Util::linkToRemote(".phptal_tales($exp, $nothrow).")";
}

function phptal_tales_url($src, $nothrow) {
	//$exp = trim($exp, ' \t\r\n/');
	error_log(phptal_tales($src, $nothrow));
	return "OC_TALTemplate::linkToAbsolute(".phptal_tales($src, $nothrow).")";
}

function phptal_tales_linkto($src, $nothrow) {
	return "OC_TALTemplate::linkTo(".phptal_tales($src, $nothrow).")";
}

function phptal_tales_image($src, $nothrow) {
	return "OC_TALTemplate::imagePath(".phptal_tales($src, $nothrow).")";
}

function phptal_tales_config($src, $nothrow) {
	return "OC_TALTemplate::config(".phptal_tales($src, $nothrow).")";
}

/**
 * This class provides TAL templates for owncloud.
 */
class OC_TALTemplate extends OC_Template {
	/**
	*/
	protected $_engine = null;
	protected $scripts = Array();
	protected $styles = Array();
	protected $_headers = Array();
	protected $renderas;
	protected static $app = '';

	public function __construct($app, $name, $renderas = "") {
		//if(defined('DEBUG') && DEBUG) {
			ini_set('display_errors', true);
		//}
		$this->renderas = $renderas;
		$this->i18n = new OC_TALL10N($app);
		$this->setEngine(new PHPTAL());
		parent::__construct($app, $name, $renderas);
		//$this->fetchHeadVars();
		self::$app = $app;
		//$this->assign('application', $this->app);
		$this->assign('i18n', $this->i18n);
		$this->assign('user', OCP\User::getUser());
		$this->assign('appinfo', OCP\App::getAppInfo($app));
		$this->assign('appajaxpath', OC::$SERVERROOT.OC_App::getAppPath($app).'/ajax');
		$this->assign('appjspath', OC::$SERVERROOT.OC_App::getAppPath($app).'/js');
		$this->assign('apptemplatepath', OC::$SERVERROOT.OC_App::getAppPath($app).'/templates');
		if($renderas) {
			$this->assign('maintemplate', OC_App::getAppPath('tal').'/templates/layout.'.$renderas.'.pt');
			if($renderas == 'user') {
				$this->assign('requesttoken', OC_Util::callRegister());
			}
		}
		//$this->assign('styles', $this->styles);
		$this->assign('core_styles', !empty(OC_Util::$core_styles)?'core.css':null);
		$this->assign('core_scripts', !empty(OC_Util::$core_scripts)?'core.js':null);
		$request = isset($_REQUEST)?$_REQUEST:array();
		$request['post'] = isset($_POST)?$_POST:array();
		$request['get'] = isset($_GET)?$_GET:array();
		$this->assign('request', $request);
		$this->assign('server', $_SERVER);
		$this->assign('webroot', OC::$WEBROOT);
		$this->assign('theme', OC_Config::getValue('theme'));

		$apps_paths = array();
		foreach(OC_App::getEnabledApps() as $app){
			$apps_paths[$app] = OC_App::getAppWebPath($app);
		}
		$this->assign( 'apps_paths', str_replace('\\/', '/',json_encode($apps_paths)),false ); // Ugly unescape slashes waiting for better solution

		// Add the js files
		$jsfiles = OC_TemplateLayout::findJavascriptFiles(OC_Util::$scripts);
		
		foreach($jsfiles as $info) {
			$root = $info[0];
			$web = $info[1];
			$file = $info[2];
			$this->scripts[] = $web.'/'.$file;
		}
		$this->assign('scripts',$this->scripts);

		// Add the css files
		$cssfiles = OC_TemplateLayout::findStylesheetFiles(OC_Util::$styles);

		foreach($cssfiles as $info) {
			$root = $info[0];
			$web = $info[1];
			$file = $info[2];
			$paths = explode('/', $file);

			$in_root = false;
			foreach(OC::$APPSROOTS as $app_root) {
				if($root == $app_root['path']) {
					$in_root = true;
					break;
				}
			}

			if($in_root ) {
				$app = $paths[0];
				unset($paths[0]);
				$path = implode('/', $paths);
				$this->styles[] = OC_Helper::linkTo($app, $path);
			}
			else {
				$this->styles[] = $web.'/'.$file;
			}
		}
		$this->assign('styles', $this->styles);
		
	}

	/**
	* Plug in PHPTAL object into View
	*
	* @name setEngine
	* @access public
	* @param object PHPTAL $engine
	*/
	public function setEngine(PHPTAL $engine) {
		$view = new OC_FilesystemView('/'.OC_User::getUser());
		if(!$view->file_exists('phptal')) {
			$view->mkdir('phptal');
		}
		$this->_engine = $engine;
		$this->_engine->setPhpCodeDestination($view->getLocalFile('/phptal/'));
		$this->_engine->setTemplateRepository($_SERVER['DOCUMENT_ROOT'].OCP\Util::linkTo(self::$app, 'templates'));
		$this->_engine->set('this', $this);
		$this->_engine->setOutputMode(PHPTAL::HTML5);
		$this->_engine->setTranslator($this->i18n);
		return $this;
	}

	/**
	 * Forces reparsing of all templates all the time. It should be used only for testing and debugging. 
	 * It's useful if you're testing pre filters or changing code of PHPTAL itself.
	 * WARNING: This slows down PHPTAL very much. Never enable this on production servers!
	 */
	public function setForceReparse() {
		OCP\Util::writeLog('tal','ForceReparse is enabled!', OCP\Util::WARN);
		$this->_engine->setForceReparse();
	}
	
	/**
	* Get PHPTAL object from View
	*
	* @name getEngine
	* @access public
	*/
	public function getEngine() {
		return $this->_engine;
	}

	/**
	* Clone PHPTAL object
	*
	* @access public
	*/
	public function __clone() {
		$this->_engine = clone $this->_engine;
	}

	/**
	* Display template
	*
	* @access protected
	*/
	protected function _run() {
		$this->_engine->setTemplate(func_get_arg(0));
		try {
			echo $this->_engine->execute();
		} catch (Exception $e) {
			throw new Exception($e);
		}
	}

	/**
	 * @brief check Path For Template with and without $fext
	 * @param $path to check
	 * @param $name of the template file (without suffix)
	 * @param $fext formfactor extension
	 * @return bool true when found
	 *
	 * Will set $this->template and $this->path if there is a template at
	 * the specified $path
	 */
	protected function checkPathForTemplate($path, $name, $fext) {
		if ($name =='') return false;
		$template = null;
		if( is_file( $path.$name.$fext.'.pt' )){
			$template = $path.$name.$fext.'.pt';
		}elseif( is_file( $path.$name.'.pt' )){
			$template = $path.$name.'.pt';
		}
		//error_log('Template: '.$template);
		if ($template) {
			$this->template = $template;
			$this->path = $path;
			$this->_engine->template = $this->template;
			$this->_engine->setTemplate($this->template);
			return true;
		}
		return false;
	}

	/**
	 * @brief Assign variables
	 * @param $key key
	 * @param $value value
	 * @param $sanitizeHTML Ignored, as values are always sanitized unless explicitly specified not to.
	 * @returns true
	 *
	 * This function assigns a variable. It can be accessed via TALES expressions or ${$key} in
	 * the template.
	 *
	 * If the key existed before, it will be overwritten
	 */
	public function assign( $key, $value, $sanitizeHTML=false ){
		$this->_engine->set($key, $value);
		return true;
	}

	/**
	 * @brief Add a custom element to the header
	 * @param string tag tag name of the element
	 * @param array $attributes array of attributes for the element
	 * @param string $text the text content for the element
	 */
	public function addHeader( $tag, $attributes, $text=''){
		$this->_headers[]=array('tag'=>$tag,'attributes'=>$attributes,'text'=>$text);
	}

	/**
	 * @brief Prints the proceeded template
	 * @returns true/false
	 *
	 * This function proceeds the template and prints its output.
	 */
	public function printPage(){
		echo $this->fetchPage();
	}

	/**
	 * @brief Proceeds the template
	 * @returns content
	 *
	 * This function proceeds the template. If $this->renderas is set, it
	 * will produce a full page.
	 */
	public function fetchPage(){
		error_log('renderas: '.$this->renderas);
		if($this->renderas) {
			// Add custom headers
			$this->assign('headers',array_merge($this->_headers, OC_Util::$headers));
			// Add navigation entry
			$navigation = OC_App::getNavigation();
			$this->assign( "navigation", $navigation);
			$this->assign( "settingsnavigation", OC_App::getSettingsNavigation());
			if(array_search(OC_APP::getCurrentApp(),array('settings','admin','help'))!==false){
				$this->assign('bodyid','body-settings');
			}else{
				$this->assign('bodyid','body-user');
			}
			foreach($navigation as $entry) {
				if ($entry['active']) {
					$this->assign( 'application', $entry['name'] );
					break;
				}
			}
		}
		//error_log('utilheaders'.print_r(array_merge($this->_headers, OC_Util::$headers), true));
		return $this->_engine->execute();
	}

	static function linkTo($src) {
		//error_log('linkTo '.$src);
		$parts = is_array($src)?$src:explode('/', rtrim($src, ' \t\r\n/'));
		if($parts[0] == '') {
			array_shift($parts);
			return OCP\Util::linkTo('', implode('/', $parts));
		} elseif(count($parts) == 1) {
			return OCP\Util::linkTo('', implode('/', $parts));
		} elseif(trim($parts[0] == 'core')) {
			array_shift($parts);
			return OCP\Util::linkTo('', implode('/', $parts));
		} else { // This should be an app.
			return OCP\Util::linkTo(array_shift($parts), implode('/', $parts));
		}
	}

	static function linkToAbsolute($src) {
		//error_log('linkTo '.$src);
		$parts = is_array($src)?$src:explode('/', rtrim($src, ' \t\r\n/'));
		if($parts[0] == '') {
			array_shift($parts);
			return OCP\Util::linkToAbsolute('', implode('/', $parts));
		} elseif(count($parts) == 1) {
			return OCP\Util::linkToAbsolute('', implode('/', $parts));
		} elseif(trim($parts[0] == 'core')) {
			array_shift($parts);
			return OCP\Util::linkToAbsolute('', implode('/', $parts));
		} else { // This should be an app.
			return OCP\Util::linkToAbsolute(array_shift($parts), implode('/', $parts));
		}
	}

	static function imagePath($src) {
		//error_log('imagePath '.$src);
		$parts = is_array($src)?$src:explode('/', rtrim($src, ' \t\r\n/'));
		if($parts[0] == '') {
			array_shift($parts);
			return OCP\Util::imagePath('', implode('/', $parts));
		} elseif(count($parts) == 1) {
			return OCP\Util::imagePath('', implode('/', $parts));
		} elseif(trim($parts[0] == 'core')) {
			array_shift($parts);
			return OCP\Util::imagePath('', implode('/', $parts));
		} else { // This should be an app.
			return OCP\Util::imagePath(array_shift($parts), implode('/', $parts));
		}
	}

	static function config($src) {
		error_log('pref '.$src);
		$parts = is_array($src)?$src:explode('/', rtrim($src, ' \t\r\n/'));
		if(count($parts) < 2) {
			throw new PHPTAL_Exception('Wrong argument count: config: takes no less than 2 arguments.');
		} else {
			switch ($parts[0]) {
			    case 'sys':
					return OCP\Config::getSystemValue($parts[1]);
			        break;
			    case 'app':
					if(count($parts) == 2) {
						return OCP\Config::getAppValue(self::app, $parts[1]);
					} elseif(count($parts) == 3) {
						return OCP\Config::getAppValue($parts[1], $parts[2]);
					} else {
						throw new PHPTAL_Exception('Wrong argument count: config:$app takes no more than 3 arguments.');
					}
			        break;
			    case 'user':
					if(count($parts) == 2) {
						return OCP\Config::getUserValue(OCP\User::getUser(), self::app, $parts[1]);
					} elseif(count($parts) == 3) {
						return OCP\Config::getUserValue(OCP\User::getUser(), $parts[1], $parts[2]);
					} elseif(count($parts) == 4) {
						return OCP\Config::getUserValue($parts[1], $parts[2], $parts[3]);
					} else {
						throw new PHPTAL_Exception('Wrong argument count: config: takes no more than 4 arguments.');
					}
			        break;
			}
		}
	}
}