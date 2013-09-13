<?php

namespace OC;

use OC\AppFramework\Http\Request;
use OC\AppFramework\Utility\SimpleContainer;
use OCP\IServerContainer;

/**
 * Class Server
 * @package OC
 *
 * TODO: hookup all manager classes
 */
class Server extends SimpleContainer implements IServerContainer {

	function __construct() {
		$this->registerService('ContactsManager', function($c){
			return new ContactsManager();
		});
		$this->registerService('Request', function($c){
			$params = array();

			// we json decode the body only in case of content type json
			if (isset($_SERVER['CONTENT_TYPE']) && stripos($_SERVER['CONTENT_TYPE'],'json') === true ) {
				$params = json_decode(file_get_contents('php://input'), true);
				$params = is_array($params) ? $params: array();
			}

			return new Request(
				array(
					'get' => $_GET,
					'post' => $_POST,
					'files' => $_FILES,
					'server' => $_SERVER,
					'env' => $_ENV,
					'session' => $_SESSION,
					'cookies' => $_COOKIE,
					'method' => (isset($_SERVER) && isset($_SERVER['REQUEST_METHOD']))
						? $_SERVER['REQUEST_METHOD']
						: null,
					'params' => $params,
					'urlParams' => $c['urlParams']
				)
			);
		});
		$this->registerService('PreviewManager', function($c){
			return new PreviewManager();
		});
	}

	/**
	 * @return \OCP\Contacts\IManager
	 */
	function getContactsManager() {
		return $this->query('ContactsManager');
	}

	/**
	 * The current request object holding all information about the request currently being processed
	 * is returned from this method.
	 * In case the current execution was not initiated by a web request null is returned
	 *
	 * @return \OCP\IRequest|null
	 */
	function getRequest()
	{
		return $this->query('Request');
	}

	/**
	 * Returns the preview manager which can create preview images for a given file
	 *
	 * @return \OCP\IPreview
	 */
	function getPreviewManager()
	{
		return $this->query('PreviewManager');
	}
}
