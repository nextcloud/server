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

require_once __DIR__.'/template/functions.php';

/**
 * This class provides the templates for ownCloud.
 */
class OC_Template extends \OC\Template\Base {
	private $renderas; // Create a full page?
	private $path; // The path to the template
	private $headers = array(); //custom headers
	protected $app; // app id

	/**
	 * Constructor
	 * @param string $app app providing the template
	 * @param string $name of the template file (without suffix)
	 * @param string $renderas = ""; produce a full page
	 * @param bool $registerCall = true
	 * @return OC_Template object
	 *
	 * This function creates an OC_Template object.
	 *
	 * If $renderas is set, OC_Template will try to produce a full page in the
	 * according layout. For now, renderas can be set to "guest", "user" or
	 * "admin".
	 */
	public function __construct( $app, $name, $renderas = "", $registerCall = true ) {
		// Read the selected theme from the config file
		$theme = OC_Util::getTheme();

		$requesttoken = (OC::$server->getSession() and $registerCall) ? OC_Util::callRegister() : '';

		$parts = explode('/', $app); // fix translation when app is something like core/lostpassword
		$l10n = \OC::$server->getL10N($parts[0]);
		$themeDefaults = new OC_Defaults();

		list($path, $template) = $this->findTemplate($theme, $app, $name);

		// Set the private data
		$this->renderas = $renderas;
		$this->path = $path;
		$this->app = $app;

		parent::__construct($template, $requesttoken, $l10n, $themeDefaults);
	}

	/**
	 * find the template with the given name
	 * @param string $name of the template file (without suffix)
	 *
	 * Will select the template file for the selected theme.
	 * Checking all the possible locations.
	 * @param string $theme
	 * @param string $app
	 * @return array
	 */
	protected function findTemplate($theme, $app, $name) {
		// Check if it is a app template or not.
		if( $app !== '' ) {
			$dirs = $this->getAppTemplateDirs($theme, $app, OC::$SERVERROOT, OC_App::getAppPath($app));
		} else {
			$dirs = $this->getCoreTemplateDirs($theme, OC::$SERVERROOT);
		}
		$locator = new \OC\Template\TemplateFileLocator( $dirs );
		$template = $locator->find($name);
		$path = $locator->getPath();
		return array($path, $template);
	}

	/**
	 * Add a custom element to the header
	 * @param string $tag tag name of the element
	 * @param array $attributes array of attributes for the element
	 * @param string $text the text content for the element. If $text is null then the
	 * element will be written as empty element. So use "" to get a closing tag.
	 */
	public function addHeader($tag, $attributes, $text=null) {
		$this->headers[]= array(
			'tag' => $tag,
			'attributes' => $attributes,
			'text' => $text
		);
	}

	/**
	 * Process the template
	 * @return boolean|string
	 *
	 * This function process the template. If $this->renderas is set, it
	 * will produce a full page.
	 */
	public function fetchPage() {
		$data = parent::fetchPage();

		if( $this->renderas ) {
			$page = new OC_TemplateLayout($this->renderas, $this->app);

			// Add custom headers
			$headers = '';
			foreach(OC_Util::$headers as $header) {
				$headers .= '<'.OC_Util::sanitizeHTML($header['tag']);
				foreach($header['attributes'] as $name=>$value) {
					$headers .= ' '.OC_Util::sanitizeHTML($name).'="'.OC_Util::sanitizeHTML($value).'"';
				}
				if ($header['text'] !== null) {
					$headers .= '>'.OC_Util::sanitizeHTML($header['text']).'</'.OC_Util::sanitizeHTML($header['tag']).'>';
				} else {
					$headers .= '/>';
				}
			}

			$page->assign('headers', $headers, false);

			$page->assign('content', $data, false );
			return $page->fetchPage();
		}
		else{
			return $data;
		}
	}

	/**
	 * Include template
	 * @return string returns content of included template
	 *
	 * Includes another template. use <?php echo $this->inc('template'); ?> to
	 * do this.
	 */
	public function inc( $file, $additionalParams = null ) {
		return $this->load($this->path.$file.'.php', $additionalParams);
	}

	/**
	 * Shortcut to print a simple page for users
	 * @param string $application The application we render the template for
	 * @param string $name Name of the template
	 * @param array $parameters Parameters for the template
	 * @return boolean|null
	 */
	public static function printUserPage( $application, $name, $parameters = array() ) {
		$content = new OC_Template( $application, $name, "user" );
		foreach( $parameters as $key => $value ) {
			$content->assign( $key, $value );
		}
		print $content->printPage();
	}

	/**
	 * Shortcut to print a simple page for admins
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
	 * Shortcut to print a simple page for guests
	 * @param string $application The application we render the template for
	 * @param string $name Name of the template
	 * @param array|string $parameters Parameters for the template
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
		* Print a fatal error page and terminates the script
		* @param string $error_msg The error message to show
		* @param string $hint An optional hint message - needs to be properly escaped
		*/
	public static function printErrorPage( $error_msg, $hint = '' ) {
		$content = new \OC_Template( '', 'error', 'error', false );
		$errors = array(array('error' => $error_msg, 'hint' => $hint));
		$content->assign( 'errors', $errors );
		$content->printPage();
		die();
	}

	/**
	 * print error page using Exception details
	 * @param Exception $exception
	 */
	public static function printExceptionErrorPage(Exception $exception) {
		$content = new \OC_Template('', 'exception', 'error', false);
		$content->assign('errorMsg', $exception->getMessage());
		$content->assign('errorCode', $exception->getCode());
		$content->assign('file', $exception->getFile());
		$content->assign('line', $exception->getLine());
		$content->assign('trace', $exception->getTraceAsString());
		$content->assign('debugMode', defined('DEBUG') && DEBUG === true);
		$content->assign('remoteAddr', OC_Request::getRemoteAddress());
		$content->assign('requestID', OC_Request::getRequestID());
		$content->printPage();
		die();
	}

	/**
	 * @return bool
	 */
	public static function isAssetPipelineEnabled() {
		// asset management enabled?
		$config = \OC::$server->getConfig();
		$useAssetPipeline = $config->getSystemValue('asset-pipeline.enabled', false);
		if (!$useAssetPipeline) {
			return false;
		}

		// assets folder exists?
		$assetDir = $config->getSystemValue('assetdirectory', \OC::$SERVERROOT) . '/assets';
		if (!is_dir($assetDir)) {
			if (!mkdir($assetDir)) {
				\OCP\Util::writeLog('assets',
					"Folder <$assetDir> does not exist and/or could not be generated.", \OCP\Util::ERROR);
				return false;
			}
		}

		// assets folder can be accessed?
		if (!touch($assetDir."/.oc")) {
			\OCP\Util::writeLog('assets',
				"Folder <$assetDir> could not be accessed.", \OCP\Util::ERROR);
			return false;
		}
		return $useAssetPipeline;
	}

}
