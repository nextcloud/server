<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\DAV\Files;

use OC\AppFramework\Http\Request;
use OC_Template;
use OCP\IRequest;
use Sabre\DAV\Exception;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;

class BrowserErrorPagePlugin extends ServerPlugin {
	/** @var Server */
	private $server;

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
		$server->on('exception', array($this, 'logException'), 1000);
	}

	/**
	 * @param IRequest $request
	 * @return bool
	 */
	public static function isBrowserRequest(IRequest $request) {
		if ($request->getMethod() !== 'GET') {
			return false;
		}
		return $request->isUserAgent([
			Request::USER_AGENT_IE,
			Request::USER_AGENT_MS_EDGE,
			Request::USER_AGENT_CHROME,
			Request::USER_AGENT_FIREFOX,
			Request::USER_AGENT_SAFARI,
		]);
	}

	/**
	 * @param \Exception $ex
	 */
	public function logException(\Exception $ex) {
		if ($ex instanceof Exception) {
			$httpCode = $ex->getHTTPCode();
			$headers = $ex->getHTTPHeaders($this->server);
		} else {
			$httpCode = 500;
			$headers = [];
		}
		$this->server->httpResponse->addHeaders($headers);
		$this->server->httpResponse->setStatus($httpCode);
		$body = $this->generateBody($ex);
		$this->server->httpResponse->setBody($body);
		$this->sendResponse();
	}

	/**
	 * @codeCoverageIgnore
	 * @param \Exception $exception
	 * @return bool|string
	 */
	public function generateBody(\Exception $exception) {
		$request = \OC::$server->getRequest();
		$content = new OC_Template('dav', 'exception', 'guest');
		$content->assign('title', $this->server->httpResponse->getStatusText());
		$content->assign('message', $exception->getMessage());
		$content->assign('errorClass', get_class($exception));
		$content->assign('errorMsg', $exception->getMessage());
		$content->assign('errorCode', $exception->getCode());
		$content->assign('file', $exception->getFile());
		$content->assign('line', $exception->getLine());
		$content->assign('trace', $exception->getTraceAsString());
		$content->assign('debugMode', \OC::$server->getSystemConfig()->getValue('debug', false));
		$content->assign('remoteAddr', $request->getRemoteAddress());
		$content->assign('requestID', $request->getId());
		return $content->fetchPage();
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function sendResponse() {
		$this->server->sapi->sendResponse($this->server->httpResponse);
		exit();
	}
}
