<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\DAV\CardDAV;

use OCA\DAV\CardDAV\Xml\Groups;
use Sabre\DAV\Exception\ReportNotSupported;
use Sabre\DAV\INode;
use Sabre\DAV\PropFind;
use Sabre\DAV\Server;

class Plugin extends \Sabre\CardDAV\Plugin {
	public function initialize(Server $server) {
		$server->on('propFind', [$this, 'propFind']);
		parent::initialize($server);
	}

	/**
	 * Returns the addressbook home for a given principal
	 *
	 * @param string $principal
	 * @return string|null
	 */
	protected function getAddressbookHomeForPrincipal($principal) {
		if (strrpos($principal, 'principals/users', -strlen($principal)) !== false) {
			[, $principalId] = \Sabre\Uri\split($principal);
			return self::ADDRESSBOOK_ROOT . '/users/' . $principalId;
		}
		if (strrpos($principal, 'principals/groups', -strlen($principal)) !== false) {
			[, $principalId] = \Sabre\Uri\split($principal);
			return self::ADDRESSBOOK_ROOT . '/groups/' . $principalId;
		}
		if (strrpos($principal, 'principals/system', -strlen($principal)) !== false) {
			[, $principalId] = \Sabre\Uri\split($principal);
			return self::ADDRESSBOOK_ROOT . '/system/' . $principalId;
		}
	}

	/**
	 * Adds all CardDAV-specific properties
	 *
	 * @param PropFind $propFind
	 * @param INode $node
	 * @return void
	 */
	public function propFind(PropFind $propFind, INode $node) {
		$ns = '{http://owncloud.org/ns}';

		if ($node instanceof AddressBook) {
			$propFind->handle($ns . 'groups', function () use ($node) {
				return new Groups($node->getContactsGroups());
			});
		}
	}

	/**
	 * This function handles the addressbook-query REPORT.
	 *
	 * This report is used by the client to filter an addressbook based on a
	 * complex query.
	 *
	 * @param \Sabre\CardDAV\Xml\Request\AddressBookQueryReport $report
	 */
	protected function addressbookQueryReport($report) {
		$depth = $this->server->getHTTPDepth(0);

		if ($depth == 0) {
			$candidateNodes = [
				$this->server->tree->getNodeForPath($this->server->getRequestUri()),
			];
			if (!$candidateNodes[0] instanceof Card) {
				throw new ReportNotSupported('The addressbook-query report is not supported on this url with Depth: 0');
			}
		} else {
			$candidateNodes = $this->server->tree->getChildren($this->server->getRequestUri());
		}

		$validNodes = [];
		foreach ($candidateNodes as $node) {
			if (!$node instanceof Card) {
				continue;
			}

			$blob = $node->get();
			if (is_resource($blob)) {
				$blob = stream_get_contents($blob);
			}

			if (!$this->validateFilters($blob, $report->filters, $report->test)) {
				continue;
			}

			$validNodes[] = $node;

			if ($report->limit && $report->limit <= count($validNodes)) {
				// We hit the maximum number of items, we can stop now.
				break;
			}
		}

		$result = [];
		foreach ($validNodes as $validNode) {
			$contentType = $report->contentType;
			// we theoretically support versions  3.0 and 4.0 so $vcardType should be dyncamic depending on the node
			if ($validNode->getVersion()) {
				$contentType .= '; version=' . $validNode->getVersion();
			} elseif ($report->version) {
				$contentType .= '; version=' . $report->version;
			}
			$vcardType = $this->negotiateVCard(
				$contentType
			);
			if ($depth == 0) {
				$href = $this->server->getRequestUri();
			} else {
				$href = $this->server->getRequestUri() . '/' . $validNode->getName();
			}

			/** @psalm-suppress DeprecatedMethod */
			[$props] = $this->server->getPropertiesForPath($href, $report->properties, 0);

			if (isset($props[200]['{' . self::NS_CARDDAV . '}address-data'])) {
				$props[200]['{' . self::NS_CARDDAV . '}address-data'] = $this->convertVCard(
					$props[200]['{' . self::NS_CARDDAV . '}address-data'],
					$vcardType,
					$report->addressDataProperties
				);
			}
			$result[] = $props;
		}

		$prefer = $this->server->getHTTPPrefer();

		$this->server->httpResponse->setStatus(207);
		$this->server->httpResponse->setHeader('Content-Type', 'application/xml; charset=utf-8');
		$this->server->httpResponse->setHeader('Vary', 'Brief,Prefer');
		$this->server->httpResponse->setBody($this->server->generateMultiStatus($result, $prefer['return'] === 'minimal'));
	}
}
