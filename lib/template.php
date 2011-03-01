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

oc_require_once( "Smarty/Smarty.class.php" );

/**
 *
 */
function oc_template_helper_link_to( $params, $smarty ){
	$app = "";
	if( isset( $params["app"] ))
	{
		$app = $params["app"];
	}
	$file = $params["file"];
	return OC_UTIL::linkTo( $app, $file );
}

/**
 *
 */
function oc_template_helper_image_path( $params, $smarty ){
	$app = "";
	if( isset( $params["app"] ))
	{
		$app = $params["app"];
	}
	$file = $params["file"];
	return OC_UTIL::imagePath( $app, $file );
}

class OC_TEMPLATE{
	private $renderas; // Create a full page?
	private $name; // name of the template
	private $application; // template Application
	private $smarty; // The smarty object

	public function __construct( $application, $name, $renderas = "" ){
		// Global vars we need
		global $SERVERROOT;

		$template_dir = "$SERVERROOT/templates/";
		// Get the right template folder
		if( $application != "core" ){
			$template_dir = "$SERVERROOT/$application/templates/";
		}

		// Set the OC-defaults for Smarty
		$smarty = new Smarty();
		$smarty->left_delimiter  = "[%";
		$smarty->right_delimiter = "%]";
		$smarty->template_dir    = $template_dir;
		$smarty->compile_dir     = "$SERVERROOT/templates/_c";
		$smarty->registerPlugin( "function", "linkto", "oc_template_helper_link_to");
		$smarty->registerPlugin( "function", "imagepath", "oc_template_helper_image_path");

		// Templates have the ending .tmpl
		$name = "$name.tmpl";
		// Set the private data
		$this->renderas = $renderas;
		$this->application = $application;
		$this->name = $name;
		$this->smarty = $smarty;
	}

	public function assign( $a, $b = null ){
		$this->smarty->assign( $a, $b );
	}

	public function append( $a, $b = null ){
		$this->smarty->append( $a, $b );
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
		$data = $this->smarty->fetch( $this->name );

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
