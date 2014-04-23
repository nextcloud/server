<?php
/**
 * ownCloud
 *
 * @author Frank Karlitschek
 * @author Jakob Sack
 * @copyright 2012 Frank Karlitschek frank@owncloud.org
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

/**
 * Prints an XSS escaped string
 * @param string $string the string which will be escaped and printed
 */
function p($string) {
	print(OC_Util::sanitizeHTML($string));
}

/**
 * Prints an unescaped string
 * @param string $string the string which will be printed as it is
 */
function print_unescaped($string) {
	print($string);
}

/**
 * @brief make OC_Helper::linkTo available as a simple function
 * @param string $app app
 * @param string $file file
 * @param array $args array with param=>value, will be appended to the returned url
 * @return string link to the file
 *
 * For further information have a look at OC_Helper::linkTo
 */
function link_to( $app, $file, $args = array() ) {
	return OC_Helper::linkTo( $app, $file, $args );
}

/**
 * @brief make OC_Helper::imagePath available as a simple function
 * @param string $app app
 * @param string $image image
 * @return string link to the image
 *
 * For further information have a look at OC_Helper::imagePath
 */
function image_path( $app, $image ) {
	return OC_Helper::imagePath( $app, $image );
}

/**
 * @brief make OC_Helper::mimetypeIcon available as a simple function
 * @param string $mimetype mimetype
 * @return string link to the image
 *
 * For further information have a look at OC_Helper::mimetypeIcon
 */
function mimetype_icon( $mimetype ) {
	return OC_Helper::mimetypeIcon( $mimetype );
}

/**
 * @brief make OC_Helper::humanFileSize available as a simple function
 * @param int $bytes size in bytes
 * @return string size as string
 *
 * For further information have a look at OC_Helper::humanFileSize
 */
function human_file_size( $bytes ) {
	return OC_Helper::humanFileSize( $bytes );
}

function simple_file_size($bytes) {
	if ($bytes < 0) {
		return '?';
	}
	$mbytes = round($bytes / (1024 * 1024), 1);
	if ($bytes == 0) {
		return '0';
	}
	if ($mbytes < 0.1) {
		return '&lt; 0.1';
	}
	if ($mbytes > 1000) {
		return '&gt; 1000';
	} else {
		return number_format($mbytes, 1);
	}
}

function relative_modified_date($timestamp) {
	$l=OC_L10N::get('lib');
	$timediff = time() - $timestamp;
	$diffminutes = round($timediff/60);
	$diffhours = round($diffminutes/60);
	$diffdays = round($diffhours/24);
	$diffmonths = round($diffdays/31);

	if($timediff < 60) { return $l->t('seconds ago'); }
	else if($timediff < 120) { return $l->t('1 minute ago'); }
	else if($timediff < 3600) { return $l->t('%d minutes ago', $diffminutes); }
	else if($timediff < 7200) { return $l->t('1 hour ago'); }
	else if($timediff < 86400) { return $l->t('%d hours ago', $diffhours); }
	else if((date('G')-$diffhours) > 0) { return $l->t('today'); }
	else if((date('G')-$diffhours) > -24) { return $l->t('yesterday'); }
	else if($timediff < 2678400) { return $l->t('%d days ago', $diffdays); }
	else if($timediff < 5184000) { return $l->t('last month'); }
	else if((date('n')-$diffmonths) > 0) { return $l->t('%d months ago', $diffmonths); }
	else if($timediff < 63113852) { return $l->t('last year'); }
	else { return $l->t('years ago'); }
}

function html_select_options($options, $selected, $params=array()) {
	if (!is_array($selected)) {
		$selected=array($selected);
	}
	if (isset($params['combine']) && $params['combine']) {
		$options = array_combine($options, $options);
	}
	$value_name = $label_name = false;
	if (isset($params['value'])) {
		$value_name = $params['value'];
	}
	if (isset($params['label'])) {
		$label_name = $params['label'];
	}
	$html = '';
	foreach($options as $value => $label) {
		if ($value_name && is_array($label)) {
			$value = $label[$value_name];
		}
		if ($label_name && is_array($label)) {
			$label = $label[$label_name];
		}
		$select = in_array($value, $selected) ? ' selected="selected"' : '';
		$html .= '<option value="' . OC_Util::sanitizeHTML($value) . '"' . $select . '>' . OC_Util::sanitizeHTML($label) . '</option>'."\n";
	}
	return $html;
}

/**
 * This class provides the templates for owncloud.
 */
class OC_Template{
	private $renderas; // Create a full page?
	private $application; // template Application
	private $vars; // Vars
	private $template; // The path to the template
	private $l10n; // The l10n-Object
	private $headers=array(); //custom headers

	/**
	 * @brief Constructor
	 * @param string $app app providing the template
	 * @param string $file name of the template file (without suffix)
	 * @param string $renderas = ""; produce a full page
	 * @return OC_Template object
	 *
	 * This function creates an OC_Template object.
	 *
	 * If $renderas is set, OC_Template will try to produce a full page in the
	 * according layout. For now, renderas can be set to "guest", "user" or
	 * "admin".
	 */
	public function __construct( $app, $name, $renderas = "" ) {
		// Set the private data
		$this->renderas = $renderas;
		$this->application = $app;
		$this->vars = array();
		$this->vars['requesttoken'] = OC_Util::callRegister();
		$parts = explode('/', $app); // fix translation when app is something like core/lostpassword
		$this->l10n = OC_L10N::get($parts[0]);

		$this->findTemplate($name);
	}

	/**
	 * autodetect the formfactor of the used device
	 * default -> the normal desktop browser interface
	 * mobile -> interface for smartphones
	 * tablet -> interface for tablets
	 * standalone -> the default interface but without header, footer and
	 *	sidebar, just the application. Useful to use just a specific
	 *	app on the desktop in a standalone window.
	 */
	public static function detectFormfactor() {
		// please add more useragent strings for other devices
		if(isset($_SERVER['HTTP_USER_AGENT'])) {
			if(stripos($_SERVER['HTTP_USER_AGENT'], 'ipad')>0) {
				$mode='tablet';
			}elseif(stripos($_SERVER['HTTP_USER_AGENT'], 'iphone')>0) {
				$mode='mobile';
			}elseif((stripos($_SERVER['HTTP_USER_AGENT'], 'N9')>0)
				and (stripos($_SERVER['HTTP_USER_AGENT'], 'nokia')>0)) {
				$mode='mobile';
			}else{
				$mode='default';
			}
		}else{
			$mode='default';
		}
		return($mode);
	}

	/**
	 * @brief Returns the formfactor extension for current formfactor
	 */
	static public function getFormFactorExtension()
	{
		// if the formfactor is not yet autodetected do the
		// autodetection now. For possible formfactors check the
		// detectFormfactor documentation
		if(!isset($_SESSION['formfactor'])) {
			$_SESSION['formfactor'] = self::detectFormfactor();
		}
		// allow manual override via GET parameter
		if(isset($_GET['formfactor'])) {
			$_SESSION['formfactor']=$_GET['formfactor'];
		}
		$formfactor=$_SESSION['formfactor'];
		if($formfactor=='default') {
			$fext='';
		}elseif($formfactor=='mobile') {
			$fext='.mobile';
		}elseif($formfactor=='tablet') {
			$fext='.tablet';
		}elseif($formfactor=='standalone') {
			$fext='.standalone';
		}else{
			$fext='';
		}
		return $fext;
	}

	/**
	 * @brief find the template with the given name
	 * @param string $name of the template file (without suffix)
	 *
	 * Will select the template file for the selected theme and formfactor.
	 * Checking all the possible locations.
	 */
	protected function findTemplate($name)
	{
		// Read the selected theme from the config file
		$theme = OC_Util::getTheme();

		// Read the detected formfactor and use the right file name.
		$fext = self::getFormFactorExtension();

		$app = $this->application;
		// Check if it is a app template or not.
		if( $app != "" ) {
			// Check if the app is in the app folder or in the root
			if( file_exists(OC_App::getAppPath($app)."/templates/" )) {
				// Check if the template is overwritten by the selected theme
				if ($this->checkPathForTemplate(OC::$SERVERROOT."/themes/$theme/apps/$app/templates/", $name, $fext)) {
				}elseif ($this->checkPathForTemplate(OC_App::getAppPath($app)."/templates/", $name, $fext)) {
				}
			}else{
				// Check if the template is overwritten by the selected theme
				if ($this->checkPathForTemplate(OC::$SERVERROOT."/themes/$theme/$app/templates/", $name, $fext)) {
				}elseif ($this->checkPathForTemplate(OC::$SERVERROOT."/$app/templates/", $name, $fext)) {
				}else{
					echo('template not found: template:'.$name.' formfactor:'.$fext
						.' webroot:'.OC::$WEBROOT.' serverroot:'.OC::$SERVERROOT);
					die();
				}

			}
		}else{
			// Check if the template is overwritten by the selected theme
			if ($this->checkPathForTemplate(OC::$SERVERROOT."/themes/$theme/core/templates/", $name, $fext)) {
			} elseif ($this->checkPathForTemplate(OC::$SERVERROOT."/core/templates/", $name, $fext)) {
			}else{
				echo('template not found: template:'.$name.' formfactor:'.$fext
					.' webroot:'.OC::$WEBROOT.' serverroot:'.OC::$SERVERROOT);
				die();
			}
		}
	}

	/**
	 * @brief check Path For Template with and without $fext
	 * @param string $path to check
	 * @param string $name of the template file (without suffix)
	 * @param string $fext formfactor extension
	 * @return bool true when found
	 *
	 * Will set $this->template and $this->path if there is a template at
	 * the specific $path
	 */
	protected function checkPathForTemplate($path, $name, $fext)
	{
		if ($name =='') return false;
		$template = null;
		if( is_file( $path.$name.$fext.'.php' )) {
			$template = $path.$name.$fext.'.php';
		}elseif( is_file( $path.$name.'.php' )) {
			$template = $path.$name.'.php';
		}
		if ($template) {
			$this->template = $template;
			$this->path = $path;
			return true;
		}
		return false;
	}

	/**
	 * @brief Assign variables
	 * @param string $key key
	 * @param string $value value
	 * @return bool
	 *
	 * This function assigns a variable. It can be accessed via $_[$key] in
	 * the template.
	 *
	 * If the key existed before, it will be overwritten
	 */
	public function assign( $key, $value) {
		$this->vars[$key] = $value;
		return true;
	}

	/**
	 * @brief Appends a variable
	 * @param string $key key
	 * @param string $value value
	 * @return bool
	 *
	 * This function assigns a variable in an array context. If the key already
	 * exists, the value will be appended. It can be accessed via
	 * $_[$key][$position] in the template.
	 */
	public function append( $key, $value ) {
		if( array_key_exists( $key, $this->vars )) {
			$this->vars[$key][] = $value;
		}
		else{
			$this->vars[$key] = array( $value );
		}
	}

	/**
	 * @brief Add a custom element to the header
	 * @param string $tag tag name of the element
	 * @param array $attributes array of attrobutes for the element
	 * @param string $text the text content for the element
	 */
	public function addHeader( $tag, $attributes, $text='') {
		$this->headers[]=array('tag'=>$tag,'attributes'=>$attributes, 'text'=>$text);
	}

	/**
	 * @brief Prints the proceeded template
	 * @return bool
	 *
	 * This function proceeds the template and prints its output.
	 */
	public function printPage() {
		$data = $this->fetchPage();
		if( $data === false ) {
			return false;
		}
		else{
			print $data;
			return true;
		}
	}

	/**
	 * @brief Proceeds the template
	 * @return bool
	 *
	 * This function proceeds the template. If $this->renderas is set, it
	 * will produce a full page.
	 */
	public function fetchPage() {
		$data = $this->_fetch();

		if( $this->renderas ) {
			$page = new OC_TemplateLayout($this->renderas);

			// Add custom headers
			$page->assign('headers', $this->headers, false);
			foreach(OC_Util::$headers as $header) {
				$page->append('headers', $header);
			}

			$page->assign( "content", $data, false );
			return $page->fetchPage();
		}
		else{
			return $data;
		}
	}

	/**
	 * @brief doing the actual work
	 * @return string content
	 *
	 * Includes the template file, fetches its output
	 */
	private function _fetch() {
		// Register the variables
		$_ = $this->vars;
		$l = $this->l10n;

		// Execute the template
		ob_start();
		include $this->template; // <-- we have to use include because we pass $_!
		$data = ob_get_contents();
		@ob_end_clean();

		// return the data
		return $data;
	}

	/**
	 * @brief Include template
	 * @return string returns content of included template
	 *
	 * Includes another template. use <?php echo $this->inc('template'); ?> to
	 * do this.
	 */
	public function inc( $file, $additionalparams = null ) {
		$_ = $this->vars;
		$l = $this->l10n;

		if( !is_null($additionalparams)) {
			$_ = array_merge( $additionalparams, $this->vars );
		}

		// Include
		ob_start();
		include $this->path.$file.'.php';
		$data = ob_get_contents();
		@ob_end_clean();

		// Return data
		return $data;
	}

	/**
	 * @brief Shortcut to print a simple page for users
	 * @param string $application The application we render the template for
	 * @param string $name Name of the template
	 * @param array $parameters Parameters for the template
	 * @return bool
	 */
	public static function printUserPage( $application, $name, $parameters = array() ) {
		$content = new OC_Template( $application, $name, "user" );
		foreach( $parameters as $key => $value ) {
			$content->assign( $key, $value );
		}
		print $content->printPage();
	}

	/**
	 * @brief Shortcut to print a simple page for admins
	 * @param string $application The application we render the template for
	 * @param string $name Name of the template
	 * @param array $parameters Parameters for the template
	 * @return bool
	 */
	public static function printAdminPage( $application, $name, $parameters = array() ) {
		$content = new OC_Template( $application, $name, "admin" );
		foreach( $parameters as $key => $value ) {
			$content->assign( $key, $value );
		}
		return $content->printPage();
	}

	/**
	 * @brief Shortcut to print a simple page for guests
	 * @param string $application The application we render the template for
	 * @param string $name Name of the template
	 * @param string $parameters Parameters for the template
	 * @return bool
	 */
	public static function printGuestPage( $application, $name, $parameters = array() ) {
		$content = new OC_Template( $application, $name, "guest" );
		foreach( $parameters as $key => $value ) {
			$content->assign( $key, $value );
		}
		return $content->printPage();
	}

	/**
		* @brief Print a fatal error page and terminates the script
		* @param string $error The error message to show
		* @param string $hint An optional hint message 
		* Warning: All data passed to $hint needs to get sanitized using OC_Util::sanitizeHTML
		*/
	public static function printErrorPage( $error_msg, $hint = '' ) {
		$content = new OC_Template( '', 'error', 'error' );
		$errors = array(array('error' => $error_msg, 'hint' => $hint));
		$content->assign( 'errors', $errors );
		$content->printPage();
		die();
	}
}
