<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\Files;

use OC_Template;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\IConfig;
use OCP\IRequest;
use Sabre\DAV\Exception;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;

class ErrorPagePlugin extends ServerPlugin {
	private ?Server $server = null;

	public function __construct(
		private IRequest $request,
		private IConfig $config,
	) {
	}

	/**
	 * This initializes the plugin.
	 *
	 * This function is called by Sabre\DAV\Server, after
	 * addPlugin is called.
	 *
	 * This method should set up the required event subscriptions.
	 */
	public function initialize(Server $server): void {
		$this->server = $server;
		$server->on('exception', [$this, 'logException'], 1000);
	}

	public function logException(\Throwable $ex): void {
		if ($ex instanceof Exception) {
			$httpCode = $ex->getHTTPCode();
			$headers = $ex->getHTTPHeaders($this->server);
		} else {
			$httpCode = 500;
			$headers = [];
		}
		$this->server->httpResponse->addHeaders($headers);
		$this->server->httpResponse->setStatus($httpCode);
		$body = $this->generateBody($ex, $httpCode);
		$this->server->httpResponse->setBody($body);
		$csp = new ContentSecurityPolicy();
		$this->server->httpResponse->addHeader('Content-Security-Policy', $csp->buildPolicy());
		$this->sendResponse();
	}

	/**
	 * @codeCoverageIgnore
	 * @return bool|string
	 */
	public function generateBody(\Throwable $ex, int $httpCode): mixed {
		if ($this->acceptHtml()) {
			$templateName = 'exception';
			$renderAs = 'guest';
			if ($httpCode === 403 || $httpCode === 404) {
				$templateName = (string)$httpCode;
			}
		} else {
			$templateName = 'xml_exception';
			$renderAs = null;
			$this->server->httpResponse->setHeader('Content-Type', 'application/xml; charset=utf-8');
		}

		$debug = $this->config->getSystemValueBool('debug', false);

		$content = new OC_Template('core', $templateName, $renderAs);
		$content->assign('title', $this->server->httpResponse->getStatusText());
		$content->assign('remoteAddr', $this->request->getRemoteAddress());
		$content->assign('requestID', $this->request->getId());
		$content->assign('debugMode', $debug);
		$content->assign('errorClass', get_class($ex));
		$content->assign('errorMsg', $ex->getMessage());
		$content->assign('errorCode', $ex->getCode());
		$content->assign('file', $ex->getFile());
		$content->assign('line', $ex->getLine());
		$content->assign('exception', $ex);
		return $content->fetchPage();
	}

	/**
	 * @codeCoverageIgnore
	 */
	public function sendResponse() {
		$this->server->sapi->sendResponse($this->server->httpResponse);
		exit();
	}

	private function acceptHtml(): bool {
		foreach (explode(',', $this->request->getHeader('Accept')) as $part) {
			$subparts = explode(';', $part);
			if (str_ends_with($subparts[0], '/html')) {
				return true;
			}
		}
		return false;
	}
}
