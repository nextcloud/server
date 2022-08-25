<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2020 Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\User_LDAP\PagedResults;

/**
 * Class Php73
 *
 * implements paged results support with PHP APIs available from PHP 7.3
 *
 * @package OCA\User_LDAP\PagedResults
 */
class Php73 implements IAdapter {
	use TLinkId;

	/** @var array */
	protected $linkData = [];

	public function getResponseCallFunc(): string {
		return 'ldap_parse_result';
	}

	public function responseCall($link): bool {
		$linkId = $this->getLinkId($link);
		return ldap_parse_result(...$this->linkData[$linkId]['responseArgs']);
	}

	public function getResponseCallArgs(array $originalArgs): array {
		$link = array_shift($originalArgs);
		$linkId = $this->getLinkId($link);

		if (!isset($this->linkData[$linkId])) {
			$this->linkData[$linkId] = [];
		}

		$this->linkData[$linkId]['responseErrorCode'] = 0;
		$this->linkData[$linkId]['responseErrorMessage'] = '';
		$this->linkData[$linkId]['serverControls'] = [];
		$matchedDn = null;
		$referrals = [];

		$this->linkData[$linkId]['responseArgs'] = [
			$link,
			array_shift($originalArgs),
			&$this->linkData[$linkId]['responseErrorCode'],
			$matchedDn,
			&$this->linkData[$linkId]['responseErrorMessage'],
			$referrals,
			&$this->linkData[$linkId]['serverControls']
		];


		return $this->linkData[$linkId]['responseArgs'];
	}

	public function getCookie($link): string {
		$linkId = $this->getLinkId($link);
		return $this->linkData[$linkId]['serverControls'][LDAP_CONTROL_PAGEDRESULTS]['value']['cookie'] ?? '';
	}

	private function resetCookie(int $linkId): void {
		if (isset($this->linkData[$linkId]['serverControls'][LDAP_CONTROL_PAGEDRESULTS]['value']['cookie'])) {
			$this->linkData[$linkId]['serverControls'][LDAP_CONTROL_PAGEDRESULTS]['value']['cookie'] = '';
		}
	}

	public function setRequestParameters($link, int $pageSize, bool $isCritical): void {
		$linkId = $this->getLinkId($link);
		if (!isset($this->linkData[$linkId])) {
			$this->linkData[$linkId] = [];
		}
		$this->linkData[$linkId]['requestArgs'] = [];
		$this->linkData[$linkId]['requestArgs']['pageSize'] = $pageSize;
		$this->linkData[$linkId]['requestArgs']['isCritical'] = $isCritical;

		if ($pageSize === 0) {
			$this->resetCookie($linkId);
		}
	}

	public function setSearchArgs(
		$link,
		string $baseDN,
		string $filter,
		array $attr,
		int $attrsOnly,
		int $limit
	): void {
		$linkId = $this->getLinkId($link);
		if (!isset($this->linkData[$linkId])) {
			$this->linkData[$linkId] = [];
		}

		$this->linkData[$linkId]['searchArgs'] = func_get_args();
		$this->preparePagesResultsArgs($linkId, 'searchArgs');
	}

	public function getSearchArgs($link): array {
		$linkId = $this->getLinkId($link);
		return $this->linkData[$linkId]['searchArgs'];
	}

	public function setReadArgs($link, string $baseDN, string $filter, array $attr): void {
		$linkId = $this->getLinkId($link);
		if (!isset($this->linkData[$linkId])) {
			$this->linkData[$linkId] = [];
		}

		$this->linkData[$linkId]['readArgs'] = func_get_args();
		$this->linkData[$linkId]['readArgs'][] = 0; // $attrsonly default
		$this->linkData[$linkId]['readArgs'][] = -1; // $sizelimit default
	}

	public function getReadArgs($link): array {
		$linkId = $this->getLinkId($link);
		return $this->linkData[$linkId]['readArgs'];
	}

	protected function preparePagesResultsArgs(int $linkId, string $methodKey): void {
		if (!isset($this->linkData[$linkId]['requestArgs'])) {
			return;
		}

		$serverControls = [[
			'oid' => LDAP_CONTROL_PAGEDRESULTS,
			'value' => [
				'size' => $this->linkData[$linkId]['requestArgs']['pageSize'],
				'cookie' => $this->linkData[$linkId]['serverControls'][LDAP_CONTROL_PAGEDRESULTS]['value']['cookie'] ?? '',
			]
		]];

		$this->linkData[$linkId][$methodKey][] = -1; // timelimit
		$this->linkData[$linkId][$methodKey][] = LDAP_DEREF_NEVER;
		$this->linkData[$linkId][$methodKey][] = $serverControls;
	}
}
