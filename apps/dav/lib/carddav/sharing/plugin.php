<?php

namespace OCA\DAV\CardDAV\Sharing;

use OCA\DAV\Connector\Sabre\Auth;
use OCP\IRequest;
use Sabre\DAV\Exception\BadRequest;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use Sabre\DAV\XMLUtil;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

class Plugin extends ServerPlugin {

	public function __construct(Auth $authBackEnd, IRequest $request) {
		$this->auth = $authBackEnd;
		$this->request = $request;
	}

	/**
	 * Reference to SabreDAV server object.
	 *
	 * @var \Sabre\DAV\Server
	 */
	protected $server;

	/**
	 * This method should return a list of server-features.
	 *
	 * This is for example 'versioning' and is added to the DAV: header
	 * in an OPTIONS response.
	 *
	 * @return array
	 */
	function getFeatures() {

		return ['oc-addressbook-sharing'];

	}

	/**
	 * Returns a plugin name.
	 *
	 * Using this name other plugins will be able to access other plugins
	 * using Sabre\DAV\Server::getPlugin
	 *
	 * @return string
	 */
	function getPluginName() {

		return 'carddav-sharing';

	}

	/**
	 * This initializes the plugin.
	 *
	 * This function is called by Sabre\DAV\Server, after
	 * addPlugin is called.
	 *
	 * This method should set up the required event subscriptions.
	 *
	 * @param Server $server
	 * @return void
	 */
	function initialize(Server $server) {
		$this->server = $server;
		$server->resourceTypeMapping['OCA\\DAV\CardDAV\\ISharedAddressbook'] = '{' . \Sabre\CardDAV\Plugin::NS_CARDDAV . '}shared';

		$this->server->on('method:POST', [$this, 'httpPost']);
	}

	/**
	 * We intercept this to handle POST requests on calendars.
	 *
	 * @param RequestInterface $request
	 * @param ResponseInterface $response
	 * @return null|bool
	 */
	function httpPost(RequestInterface $request, ResponseInterface $response) {

		$path = $request->getPath();

		// Only handling xml
		$contentType = $request->getHeader('Content-Type');
		if (strpos($contentType, 'application/xml') === false && strpos($contentType, 'text/xml') === false)
			return;

		// Making sure the node exists
		try {
			$node = $this->server->tree->getNodeForPath($path);
		} catch (NotFound $e) {
			return;
		}

		// CSRF protection
		$this->protectAgainstCSRF();

		$requestBody = $request->getBodyAsString();

		// If this request handler could not deal with this POST request, it
		// will return 'null' and other plugins get a chance to handle the
		// request.
		//
		// However, we already requested the full body. This is a problem,
		// because a body can only be read once. This is why we preemptively
		// re-populated the request body with the existing data.
		$request->setBody($requestBody);

		$dom = XMLUtil::loadDOMDocument($requestBody);

		$documentType = XMLUtil::toClarkNotation($dom->firstChild);

		switch ($documentType) {

			// Dealing with the 'share' document, which modified invitees on a
			// calendar.
			case '{' . \Sabre\CardDAV\Plugin::NS_CARDDAV . '}share' :

				// We can only deal with IShareableCalendar objects
				if (!$node instanceof IShareableAddressBook) {
					return;
				}

				$this->server->transactionType = 'post-calendar-share';

				// Getting ACL info
				$acl = $this->server->getPlugin('acl');

				// If there's no ACL support, we allow everything
				if ($acl) {
					$acl->checkPrivileges($path, '{DAV:}write');
				}

				$mutations = $this->parseShareRequest($dom);

				$node->updateShares($mutations[0], $mutations[1]);

				$response->setStatus(200);
				// Adding this because sending a response body may cause issues,
				// and I wanted some type of indicator the response was handled.
				$response->setHeader('X-Sabre-Status', 'everything-went-well');

				// Breaking the event chain
				return false;
		}
	}

	/**
	 * Parses the 'share' POST request.
	 *
	 * This method returns an array, containing two arrays.
	 * The first array is a list of new sharees. Every element is a struct
	 * containing a:
	 *   * href element. (usually a mailto: address)
	 *   * commonName element (often a first and lastname, but can also be
	 *     false)
	 *   * readOnly (true or false)
	 *   * summary (A description of the share, can also be false)
	 *
	 * The second array is a list of sharees that are to be removed. This is
	 * just a simple array with 'hrefs'.
	 *
	 * @param \DOMDocument $dom
	 * @return array
	 */
	function parseShareRequest(\DOMDocument $dom) {

		$xpath = new \DOMXPath($dom);
		$xpath->registerNamespace('cs', \Sabre\CardDAV\Plugin::NS_CARDDAV);
		$xpath->registerNamespace('d', 'urn:DAV');

		$set = [];
		$elems = $xpath->query('cs:set');

		for ($i = 0; $i < $elems->length; $i++) {

			$xset = $elems->item($i);
			$set[] = [
				'href' => $xpath->evaluate('string(d:href)', $xset),
				'commonName' => $xpath->evaluate('string(cs:common-name)', $xset),
				'summary' => $xpath->evaluate('string(cs:summary)', $xset),
				'readOnly' => $xpath->evaluate('boolean(cs:read)', $xset) !== false
			];

		}

		$remove = [];
		$elems = $xpath->query('cs:remove');

		for ($i = 0; $i < $elems->length; $i++) {

			$xremove = $elems->item($i);
			$remove[] = $xpath->evaluate('string(d:href)', $xremove);

		}

		return [$set, $remove];

	}

	private function protectAgainstCSRF() {
		$user = $this->auth->getCurrentUser();
		if ($this->auth->isDavAuthenticated($user)) {
			return true;
		}

		if ($this->request->passesCSRFCheck()) {
			return true;
		}

		throw new BadRequest();
	}


}
