<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\CardDAV;

use OCP\AppFramework\Http;
use Sabre\DAV;
use Sabre\DAV\Server;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

class MultiGetExportPlugin extends DAV\ServerPlugin {

	/** @var Server */
	protected $server;

	/**
	 * Initializes the plugin and registers event handlers
	 *
	 * @param Server $server
	 * @return void
	 */
	public function initialize(Server $server) {
		$this->server = $server;
		$this->server->on('afterMethod:REPORT', [$this, 'httpReport'], 90);
	}

	/**
	 * Intercepts REPORT requests
	 *
	 * @param RequestInterface $request
	 * @param ResponseInterface $response
	 * @return bool
	 */
	public function httpReport(RequestInterface $request, ResponseInterface $response) {
		$queryParams = $request->getQueryParameters();
		if (!array_key_exists('export', $queryParams)) {
			return;
		}

		// Only handling xml
		$contentType = (string)$response->getHeader('Content-Type');
		if (!str_contains($contentType, 'application/xml') && !str_contains($contentType, 'text/xml')) {
			return;
		}

		$this->server->transactionType = 'vcf-multi-get-intercept-and-export';

		// Get the xml response
		$responseBody = $response->getBodyAsString();
		$responseXml = $this->server->xml->parse($responseBody);

		// Reduce the vcards into one string
		$output = array_reduce($responseXml->getResponses(), function ($vcf, $card) {
			$vcf .= $card->getResponseProperties()[200]['{urn:ietf:params:xml:ns:carddav}address-data'] . PHP_EOL;
			return $vcf;
		}, '');

		// Build and override the response
		$filename = 'vcfexport-' . date('Y-m-d') . '.vcf';
		$response->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
		$response->setHeader('Content-Type', 'text/vcard');

		$response->setStatus(Http::STATUS_OK);
		$response->setBody($output);

		return true;
	}

	/**
	 * Returns a plugin name.
	 *
	 * Using this name other plugins will be able to access other plugins
	 * using \Sabre\DAV\Server::getPlugin
	 *
	 * @return string
	 */
	public function getPluginName() {
		return 'vcf-multi-get-intercept-and-export';
	}

	/**
	 * Returns a bunch of meta-data about the plugin.
	 *
	 * Providing this information is optional, and is mainly displayed by the
	 * Browser plugin.
	 *
	 * The description key in the returned array may contain html and will not
	 * be sanitized.
	 *
	 * @return array
	 */
	public function getPluginInfo() {
		return [
			'name' => $this->getPluginName(),
			'description' => 'Intercept a multi-get request and return a single vcf file instead.'
		];
	}
}
