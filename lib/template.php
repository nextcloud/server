<?php

/**
* ownCloud
*
* @author Frank Karlitschek
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
 *
 */
function link_to( $app, $file ){
	return OC_UTIL::linkTo( $app, $file );
}

/**
 *
 */
function image_path( $app, $file ){
	return OC_UTIL::imagePath( $app, $file );
}

class OC_TEMPLATE{
	private $renderas; // Create a full page?
	private $application; // template Application
	private $vars; // The smarty object
	private $template; // The smarty object

	public function __construct( $application, $name, $renderas = "" ){
		// Global vars we need
		global $SERVERROOT;

		$template = "$SERVERROOT/templates/";
		// Get the right template folder
		if( $application != "core" && $application != "" ){
			$template = "$SERVERROOT/$application/templates/";
		}

		// Templates have the ending .tmpl
		$template .= "$name.php";

		// Set the private data
		$this->renderas = $renderas;
		$this->application = $application;
		$this->template = $template;
		$this->vars = array();
	}

	public function assign( $a, $b ){
		$this->vars[$a] = $b;
	}

	public function append( $a, $b ){
		if( array_key_exists( $this->vars[$a] )){
			if( is_a( $this->vars[$a], "array" )){
				$this->vars[$a][] = $b;
			}
			else
			{
				$array = array( $this->vars[$a], $b );
				$this->vars[$a] = $array;
			}
		}
		else{
			$this->vars[$a] = $b;
		}
	}

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

	public function fetchPage()
	{
		// global Data we need
		global $WEBROOT;
		$data = $this->_fetch();

		if( $this->renderas )
		{
			// Decide which page we show
			if( $this->renderas == "user" )
			{
				$page = new OC_TEMPLATE( "core", "layout.user" );
				// Add menu data
			}
			elseif( $this->renderas == "admin" )
			{
				$page = new OC_TEMPLATE( "core", "layout.admin" );
				// Add menu data
			}
			else
			{
				$page = new OC_TEMPLATE( "core", "layout.guest" );
				// Add data if required
			}

			// Add the css and js files
			foreach(OC_UTIL::$scripts as $script){
				$page->append( "jsfiles", "$WEBROOT/$script.js" );
			}
			foreach(OC_UTIL::$styles as $style){
				$page->append( "cssfiles", "$WEBROOT/$style.css" );
			}

			// Add navigation entry and personal menu
			$page->assign( "navigation", OC_UTIL::$navigation );
			$page->assign( "personalmenu", OC_UTIL::$personalmenu );

			// Add css files and js files
			$page->assign( "content", $data );
			return $page->fetchPage();
		}
		else
		{
			return $data;
		}
	}
	public function __destruct(){
	}

	private function _fetch(){
		// Register the variables
		$_ = $this->vars;

		// Execute the template
		ob_start();
		oc_include( $this->template );
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
		$content->assign( $parameters );
		return $content->printPage();
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
		$content->assign( $parameters );
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
		$content->assign( $parameters );
		return $content->printPage();
	}
}

?>
