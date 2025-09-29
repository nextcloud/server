<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\CalDAV\Search;

use OCA\DAV\CalDAV\CalendarHome;
use OCA\DAV\CalDAV\Search\Xml\Request\CalendarSearchReport;
use OCP\AppFramework\Http;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;

class SearchPlugin extends ServerPlugin {
	public const NS_Nextcloud = 'http://nextcloud.com/ns';

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
	 * @return string[]
	 */
	public function getFeatures() {
		// May have to be changed to be detected
		return ['nc-calendar-search'];
	}

	/**
	 * Returns a plugin name.
	 *
	 * Using this name other plugins will be able to access other plugins
	 * using Sabre\DAV\Server::getPlugin
	 *
	 * @return string
	 */
	public function getPluginName() {
		return 'nc-calendar-search';
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
	 */
	public function initialize(Server $server) {
		$this->server = $server;

		$server->on('report', [$this, 'report']);

		$server->xml->elementMap['{' . self::NS_Nextcloud . '}calendar-search']
			= CalendarSearchReport::class;
	}

	/**
	 * This functions handles REPORT requests specific to CalDAV
	 *
	 * @param string $reportName
	 * @param mixed $report
	 * @param mixed $path
	 * @return bool
	 */
	public function report($reportName, $report, $path) {
		switch ($reportName) {
			case '{' . self::NS_Nextcloud . '}calendar-search':
				$this->server->transactionType = 'report-nc-calendar-search';
				$this->calendarSearch($report);
				return false;
		}
	}

	/**
	 * Returns a list of reports this plugin supports.
	 *
	 * This will be used in the {DAV:}supported-report-set property.
	 * Note that you still need to subscribe to the 'report' event to actually
	 * implement them
	 *
	 * @param string $uri
	 * @return array
	 */
	public function getSupportedReportSet($uri) {
		$node = $this->server->tree->getNodeForPath($uri);

		$reports = [];
		if ($node instanceof CalendarHome) {
			$reports[] = '{' . self::NS_Nextcloud . '}calendar-search';
		}

		return $reports;
	}

	/**
	 * This function handles the calendar-query REPORT
	 *
	 * This report is used by clients to request calendar objects based on
	 * complex conditions.
	 *
	 * @param CalendarSearchReport $report
	 * @return void
	 */
	private function calendarSearch($report) {
		$node = $this->server->tree->getNodeForPath($this->server->getRequestUri());
		$depth = $this->server->getHTTPDepth(2);

		// The default result is an empty array
		$result = [];

		// If we're dealing with the calendar home, the calendar home itself is
		// responsible for the calendar-query
		if ($node instanceof CalendarHome && $depth === 2) {
			$nodePaths = $node->calendarSearch($report->filters, $report->limit, $report->offset);

			foreach ($nodePaths as $path) {
				[$properties] = $this->server->getPropertiesForPath(
					$this->server->getRequestUri() . '/' . $path,
					$report->properties);
				$result[] = $properties;
			}
		}

		$prefer = $this->server->getHTTPPrefer();

		$this->server->httpResponse->setStatus(Http::STATUS_MULTI_STATUS);
		$this->server->httpResponse->setHeader('Content-Type',
			'application/xml; charset=utf-8');
		$this->server->httpResponse->setHeader('Vary', 'Brief,Prefer');
		$this->server->httpResponse->setBody(
			$this->server->generateMultiStatus($result,
				$prefer['return'] === 'minimal'));
	}
}
