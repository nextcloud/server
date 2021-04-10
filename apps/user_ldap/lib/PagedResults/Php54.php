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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\User_LDAP\PagedResults;

/**
 * Class Php54
 *
 * implements paged results support with PHP APIs available from PHP 5.4
 *
 * @package OCA\User_LDAP\PagedResults
 */
class Php54 implements IAdapter {
	use TLinkId;

	/** @var array */
	protected $linkData = [];

	public function getResponseCallFunc(): string {
		return 'ldap_control_paged_result_response';
	}

	public function responseCall($link): bool {
		$linkId = $this->getLinkId($link);
		return ldap_control_paged_result_response(...$this->linkData[$linkId]['responseArgs']);
	}

	public function getResponseCallArgs(array $originalArgs): array {
		$linkId = $this->getLinkId($originalArgs[0]);
		if (!isset($this->linkData[$linkId])) {
			throw new \LogicException('There should be a request before the response');
		}
		$this->linkData[$linkId]['responseArgs'] = &$originalArgs;
		$this->linkData[$linkId]['cookie'] = &$originalArgs[2];
		return $originalArgs;
	}

	public function getCookie($link): string {
		$linkId = $this->getLinkId($link);
		return $this->linkData[$linkId]['cookie'];
	}

	public function getRequestCallFunc(): ?string {
		return 'ldap_control_paged_result';
	}

	public function setRequestParameters($link, int $pageSize, bool $isCritical): void {
		$linkId = $this->getLinkId($link);

		if ($pageSize === 0 || !isset($this->linkData[$linkId]['cookie'])) {
			// abandons a previous paged search
			$this->linkData[$linkId]['cookie'] = '';
		}

		$this->linkData[$linkId]['requestArgs'] = [
			$link,
			$pageSize,
			$isCritical,
			&$this->linkData[$linkId]['cookie']
		];
	}

	public function getRequestCallArgs($link): array {
		$linkId = $this->getLinkId($link);
		return $this->linkData[$linkId]['requestArgs'];
	}

	public function requestCall($link): bool {
		$linkId = $this->getLinkId($link);
		return ldap_control_paged_result(...$this->linkData[$linkId]['requestArgs']);
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
	}

	public function getReadArgs($link): array {
		$linkId = $this->getLinkId($link);
		return $this->linkData[$linkId]['readArgs'];
	}
}
