<?php
/**
 * @copyright Copyright (c) 2017 Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\DAV\CardDAV;

use Sabre\CardDAV\Card;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;
use Sabre\VObject\Reader;

class ContactExportPlugin extends ServerPlugin {

	/** @var  Server */
	protected $server;

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
		$this->server->on('method:GET', [$this, 'httpGet'], 90);
	}

	/**
	 * Injects a Content-Disposition header to GET requests on VCard with
	 * export parameter. The full name of the vcard will be used as filename
	 * instead of the original one, for usability reasons. If none is present
	 * a fallback to the original filename is done.
	 *
	 * @param RequestInterface $request
	 * @param ResponseInterface $response
	 * @return bool|void
	 */
	function httpGet(RequestInterface $request, ResponseInterface $response) {
		$path = $request->getPath();
		$node = $this->server->tree->getNodeForPath($path);
		$queryParams = $request->getQueryParameters();

		if (!$node instanceof Card || !array_key_exists('export', $queryParams)) {
			return;
		}

		$vCard = Reader::read($node->get());
		$fn = $vCard->FN;
		if($fn === null) {
			return;
		}

		$filenameUtf8 = trim($fn->getValue());
		$filename = trim(preg_replace('/[^a-zA-Z0-9-_ ]/um', '', $filenameUtf8));

		if($filename === '' && $filenameUtf8 === '') {
			return;
		}

		if($filename === '') {
			$filename = $node->getName();
		} else {
			$filename .= '.vcf';
		}

		$filenameUtf8 = urlencode($filenameUtf8) . '.vcf';

		$httpHeaders = $this->server->getHTTPHeaders($path);
		$httpHeaders['Content-Disposition'] =
			'attachment;' .
			'filename*=UTF8\'\''.$filenameUtf8.';' .
			'filename="'.$filename.'"'
		;
		$response->addHeaders($httpHeaders);
	}
}
