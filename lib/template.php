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
 * @brief make OC_HELPER::linkTo available as a simple function
 * @param $app app
 * @param $file file
 * @returns link to the file
 *
 * For further information have a look at OC_HELPER::linkTo
 */
function link_to( $app, $file ){
	return OC_HELPER::linkTo( $app, $file );
}

/**
 * @brief make OC_HELPER::imagePath available as a simple function
 * @param $app app
 * @param $image image
 * @returns link to the image
 *
 * For further information have a look at OC_HELPER::imagePath
 */
function image_path( $app, $image ){
	return OC_HELPER::imagePath( $app, $image );
}

/**
 * @brief make OC_HELPER::mimetypeIcon available as a simple function
 * @param $mimetype mimetype
 * @returns link to the image
 *
 * For further information have a look at OC_HELPER::mimetypeIcon
 */
function mimetype_icon( $mimetype ){
	return OC_HELPER::mimetypeIcon( $mimetype );
}

/**
 * @brief make OC_HELPER::humanFileSize available as a simple function
 * @param $bytes size in bytes
 * @returns size as string
 *
 * For further information have a look at OC_HELPER::humanFileSize
 */
function human_file_size( $bytes ){
	return OC_HELPER::humanFileSize( $bytes );
}

/**
 * This class provides the templates for owncloud.
 */
class OC_TEMPLATE{
	private $renderas; // Create a full page?
	private $application; // template Application
	private $vars; // The smarty object
	private $template; // The smarty object

	/**
	 * @brief Constructor
	 * @param $app app providing the template
	 * @param $file name of the tempalte file (without suffix)
	 * @param $renderas = ""; produce a full page
	 * @returns OC_TEMPLATE object
	 *
	 * This function creates an OC_TEMPLATE object.
	 *
	 * If $renderas is set, OC_TEMPLATE will try to produce a full page in the
	 * according layout. For now, renderas can be set to "guest", "user" or
	 * "admin".
	 */
	public function __construct( $app, $name, $renderas = "" ){
		// Global vars we need
		global $SERVERROOT;

		// Get the right template folder
		$template = "$SERVERROOT/templates/";
		if( $app != "core" && $app != "" ){
			// Check if the app is in the app folder
			if( file_exists( "$SERVERROOT/apps/$app/templates/" )){
				$template = "$SERVERROOT/apps/$app/templates/";
			}
			else{
				$template = "$SERVERROOT/$app/templates/";
			}
		}

		// Templates have the ending .php
		$template .= "$name.php";

		// Set the private data
		$this->renderas = $renderas;
		$this->application = $app;
		$this->template = $template;
		$this->vars = array();
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
	 * @brief Prints the proceeded template
	 * @returns true/false
	 *
	 * This function proceeds the template and prints its output.
	 */
	public function printPage()
	{
		$data = $this->fetchPage();
		if( $data === false )
		{
			return false;
		}
		else
		{
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
	public function fetchPage()
	{
		// global Data we need
		global $WEBROOT;
		global $SERVERROOT;
		$data = $this->_fetch();

		if( $this->renderas )
		{
			// Decide which page we show
			if( $this->renderas == "user" )
			{
				$page = new OC_TEMPLATE( "core", "layout.user" );
				$search=new OC_TEMPLATE( 'core', 'part.searchbox');
				$search->assign('searchurl',OC_HELPER::linkTo( 'search', 'index.php' ));
				$page->assign('searchbox', $search->fetchPage());
				// Add menu data

				// Add navigation entry
				$page->assign( "navigation", OC_APP::getNavigation());
			}
			elseif( $this->renderas == "admin" )
			{
				$page = new OC_TEMPLATE( "core", "layout.admin" );
				$search=new OC_TEMPLATE( 'core', 'part.searchbox');
				$search->assign('searchurl',OC_HELPER::linkTo( 'search', 'index.php' ));
				$page->assign('searchbox', $search->fetchPage());
				// Add menu data
				if( OC_GROUP::inGroup( $_SESSION["user_id"], "admin" )){
					$page->assign( "settingsnavigation", OC_APP::getSettingsNavigation());
				}
				$page->assign( "adminnavigation", OC_APP::getAdminNavigation());
			}
			else
			{
				$page = new OC_TEMPLATE( "core", "layout.guest" );
				// Add data if required
			}

			// Add the css and js files
			foreach(OC_UTIL::$scripts as $script){
				if(is_file("$SERVERROOT/apps/$script.js" )){
					$page->append( "jsfiles", "$WEBROOT/apps/$script.js" );
				}else{
					$page->append( "jsfiles", "$WEBROOT/$script.js" );
				}
			}
			foreach(OC_UTIL::$styles as $style){
				if(is_file("$SERVERROOT/apps/$style.css" )){
					$page->append( "cssfiles", "$WEBROOT/apps/$style.css" );
				}else{
					$page->append( "cssfiles", "$WEBROOT/$style.css" );
				}
			}

			// Add css files and js files
			$page->assign( "content", $data );
			return $page->fetchPage();
		}
		else
		{
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

		// Execute the template
		ob_start();
		include( $this->template ); // <-- we have to use include because we pass $_!
		$data = ob_get_contents();
		ob_end_clean();

		// return the data
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
		$content = new OC_TEMPLATE( $application, $name, "user" );
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
		$content = new OC_TEMPLATE( $application, $name, "admin" );
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
		$content = new OC_TEMPLATE( $application, $name, "guest" );
		foreach( $parameters as $key => $value ){
			$content->assign( $key, $value );
		}
		return $content->printPage();
	}
}

?>
