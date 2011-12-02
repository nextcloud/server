<?php
/**
 * ownCloud
 *
 * @author Frank Karlitschek
 * @author Jakob Sack
 * @copyright 2010 Frank Karlitschek karlitschek@kde.org
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
 * @brief make OC_Helper::linkTo available as a simple function
 * @param $app app
 * @param $file file
 * @returns link to the file
 *
 * For further information have a look at OC_Helper::linkTo
 */
function link_to( $app, $file ){
	return OC_Helper::linkTo( $app, $file );
}

/**
 * @brief make OC_Helper::imagePath available as a simple function
 * @param $app app
 * @param $image image
 * @returns link to the image
 *
 * For further information have a look at OC_Helper::imagePath
 */
function image_path( $app, $image ){
	return OC_Helper::imagePath( $app, $image );
}

/**
 * @brief make OC_Helper::mimetypeIcon available as a simple function
 * @param $mimetype mimetype
 * @returns link to the image
 *
 * For further information have a look at OC_Helper::mimetypeIcon
 */
function mimetype_icon( $mimetype ){
	return OC_Helper::mimetypeIcon( $mimetype );
}

/**
 * @brief make OC_Helper::humanFileSize available as a simple function
 * @param $bytes size in bytes
 * @returns size as string
 *
 * For further information have a look at OC_Helper::humanFileSize
 */
function human_file_size( $bytes ){
	return OC_Helper::humanFileSize( $bytes );
}

function simple_file_size($bytes) {
	$mbytes = round($bytes/(1024*1024),1);
	if($bytes == 0) { return '0'; }
	else if($mbytes < 0.1) { return '&lt; 0.1'; }
	else if($mbytes > 1000) { return '&gt; 1000'; }
	else { return number_format($mbytes, 1); }
}

function relative_modified_date($timestamp) {
    $l=new OC_L10N('template');
	$timediff = time() - $timestamp;
	$diffminutes = round($timediff/60);
	$diffhours = round($diffminutes/60);
	$diffdays = round($diffhours/24);
	$diffmonths = round($diffdays/31);
	$diffyears = round($diffdays/365);

	if($timediff < 60) { return $l->t('seconds ago'); }
	else if($timediff < 120) { return $l->t('1 minute ago'); }
	else if($timediff < 3600) { return $l->t('%d minutes ago',$diffminutes); }
	//else if($timediff < 7200) { return '1 hour ago'; }
	//else if($timediff < 86400) { return $diffhours.' hours ago'; }
	else if((date('G')-$diffhours) > 0) { return $l->t('today'); }
	else if((date('G')-$diffhours) > -24) { return $l->t('yesterday'); }
	else if($timediff < 2678400) { return $l->t('%d days ago',$diffdays); }
	else if($timediff < 5184000) { return $l->t('last month'); }
	else if((date('n')-$diffmonths) > 0) { return $l->t('months ago'); }
	else if($timediff < 63113852) { return $l->t('last year'); }
	else { return $l->t('years ago'); }
}

function html_select_options($options, $selected, $params=array()) {
	if (!is_array($selected)){
		$selected=array($selected);
	}
	if (isset($params['combine']) && $params['combine']){
		$options = array_combine($options, $options);
	}
	$value_name = $label_name = false;
	if (isset($params['value'])){
		$value_name = $params['value'];
	}
	if (isset($params['label'])){
		$label_name = $params['label'];
	}
	$html = '';
	foreach($options as $value => $label){
		if ($value_name && is_array($label)){
			$value = $label[$value_name];
		}
		if ($label_name && is_array($label)){
			$label = $label[$label_name];
		}
		$select = in_array($value, $selected) ? ' selected="selected"' : '';
		$html .= '<option value="' . $value . '"' . $select . '>' . $label . '</option>'."\n";
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
	 * @param $app app providing the template
	 * @param $file name of the tempalte file (without suffix)
	 * @param $renderas = ""; produce a full page
	 * @returns OC_Template object
	 *
	 * This function creates an OC_Template object.
	 *
	 * If $renderas is set, OC_Template will try to produce a full page in the
	 * according layout. For now, renderas can be set to "guest", "user" or
	 * "admin".
	 */
	public function __construct( $app, $name, $renderas = "" ){
		// Get the right template folder
		$template = OC::$SERVERROOT."/core/templates/";
		if( $app != "" ){
			// Check if the app is in the app folder
			if( file_exists( OC::$SERVERROOT."/apps/$app/templates/" )){
				$template = OC::$SERVERROOT."/apps/$app/templates/";
			}
			else{
				$template = OC::$SERVERROOT."/$app/templates/";
			}
		}

		// Templates have the ending .php
		$path = $template;
		$template .= "$name.php";

		// Set the private data
		$this->renderas = $renderas;
		$this->application = $app;
		$this->template = $template;
		$this->path = $path;
		$this->vars = array();
		$this->l10n = new OC_L10N($app);
	}

	/**
	 * @brief Assign variables
	 * @param $key key
	 * @param $value value
	 * @returns true
	 *
	 * This function assigns a variable. It can be accessed via $_[$key] in
	 * the template.
	 *
	 * If the key existed before, it will be overwritten
	 */
	public function assign( $key, $value ){
		$this->vars[$key] = $value;
		return true;
	}

	/**
	 * @brief Appends a variable
	 * @param $key key
	 * @param $value value
	 * @returns true
	 *
	 * This function assigns a variable in an array context. If the key already
	 * exists, the value will be appended. It can be accessed via
	 * $_[$key][$position] in the template.
	 */
	public function append( $key, $value ){
		if( array_key_exists( $key, $this->vars )){
			$this->vars[$key][] = $value;
		}
		else{
			$this->vars[$key] = array( $value );
		}
	}
	
	/**
	 * @brief Add a custom element to the header
	 * @param string tag tag name of the element
	 * @param array $attributes array of attrobutes for the element
	 * @param string $text the text content for the element
	 */
	public function addHeader( $tag, $attributes, $text=''){
		$this->headers[]=array('tag'=>$tag,'attributes'=>$attributes,'text'=>$text);
	}

	/**
	 * @brief Prints the proceeded template
	 * @returns true/false
	 *
	 * This function proceeds the template and prints its output.
	 */
	public function printPage(){
		$data = $this->fetchPage();
		if( $data === false ){
			return false;
		}
		else{
			print $data;
			return true;
		}
	}

	/**
	 * @brief Proceeds the template
	 * @returns content
	 *
	 * This function proceeds the template. If $this->renderas is set, it will
	 * will produce a full page.
	 */
	public function fetchPage(){
		$data = $this->_fetch();

		if( $this->renderas ){
			// Decide which page we show
			if( $this->renderas == "user" ){
				$page = new OC_Template( "core", "layout.user" );
				$page->assign('searchurl',OC_Helper::linkTo( 'search', 'index.php' ));
				if(array_search(OC_APP::getCurrentApp(),array('settings','admin','help'))!==false){
					$page->assign('bodyid','body-settings');
				}else{
					$page->assign('bodyid','body-user');
				}

				// Add navigation entry
				$page->assign( "navigation", OC_App::getNavigation());
				$page->assign( "settingsnavigation", OC_App::getSettingsNavigation());
			}else{
				$page = new OC_Template( "core", "layout.guest" );
			}

			// Add the css and js files
			foreach(OC_Util::$scripts as $script){
				if(is_file(OC::$SERVERROOT."/apps/$script.js" )){
					$page->append( "jsfiles", OC::$WEBROOT."/apps/$script.js" );
				}
				elseif(is_file(OC::$SERVERROOT."/$script.js" )){
					$page->append( "jsfiles", OC::$WEBROOT."/$script.js" );
				}
				else{
					$page->append( "jsfiles", OC::$WEBROOT."/core/$script.js" );
				}
			}
			foreach(OC_Util::$styles as $style){
				if(is_file(OC::$SERVERROOT."/apps/$style.css" )){
					$page->append( "cssfiles", OC::$WEBROOT."/apps/$style.css" );
				}
				elseif(is_file(OC::$SERVERROOT."/$style.css" )){
					$page->append( "cssfiles", OC::$WEBROOT."/$style.css" );
				}
				else{
					$page->append( "cssfiles", OC::$WEBROOT."/core/$style.css" );
				}
			}
			
			// Add custom headers
			$page->assign('headers',$this->headers);
			foreach(OC_Util::$headers as $header){
				$page->append('headers',$header);
			}
			
			// Add css files and js files
			$page->assign( "content", $data );
			return $page->fetchPage();
		}
		else{
			return $data;
		}
	}

	/**
	 * @brief doing the actual work
	 * @returns content
	 *
	 * Includes the template file, fetches its output
	 */
	private function _fetch(){
		// Register the variables
		$_ = $this->vars;
		$l = $this->l10n;

		// Execute the template
		ob_start();
		include( $this->template ); // <-- we have to use include because we pass $_!
		$data = ob_get_contents();
		@ob_end_clean();

		// return the data
		return $data;
	}

	/**
	 * @brief Include template
	 * @returns returns content of included template
	 *
	 * Includes another template. use <?php echo $this->inc('template'); ?> to
	 * do this.
	 */
	public function inc( $file, $additionalparams = null ){
		// $_ erstellen
		$_ = $this->vars;
		$l = $this->l10n;

		if( !is_null($additionalparams)){
			$_ = array_merge( $additionalparams, $this->vars );
		}

		// Einbinden
		ob_start();
		include( $this->path.$file.'.php' );
		$data = ob_get_contents();
		@ob_end_clean();

		// Daten zurückgeben
		return $data;
	}

	/**
	 * @brief Shortcut to print a simple page for users
	 * @param $application The application we render the template for
	 * @param $name Name of the template
	 * @param $parameters Parameters for the template
	 * @returns true/false
	 */
	public static function printUserPage( $application, $name, $parameters = array() ){
		$content = new OC_Template( $application, $name, "user" );
		foreach( $parameters as $key => $value ){
			$content->assign( $key, $value );
		}
		print $content->printPage();
	}

	/**
	 * @brief Shortcut to print a simple page for admins
	 * @param $application The application we render the template for
	 * @param $name Name of the template
	 * @param $parameters Parameters for the template
	 * @returns true/false
	 */
	public static function printAdminPage( $application, $name, $parameters = array() ){
		$content = new OC_Template( $application, $name, "admin" );
		foreach( $parameters as $key => $value ){
			$content->assign( $key, $value );
		}
		return $content->printPage();
	}

	/**
	 * @brief Shortcut to print a simple page for guests
	 * @param $application The application we render the template for
	 * @param $name Name of the template
	 * @param $parameters Parameters for the template
	 * @returns true/false
	 */
	public static function printGuestPage( $application, $name, $parameters = array() ){
		$content = new OC_Template( $application, $name, "guest" );
		foreach( $parameters as $key => $value ){
			$content->assign( $key, $value );
		}
		return $content->printPage();
	}
}
