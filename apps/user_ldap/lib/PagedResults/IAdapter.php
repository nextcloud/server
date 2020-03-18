<?php
declare(strict_types=1);
/**
 * @copyright Copyright (c) 2020 Arthur Schiwon <blizzz@arthur-schiwon.de>
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

namespace OCA\User_LDAP\PagedResults;

interface IAdapter {

	public function setRequestParameters($link, int $pageSize, bool $isCritical): void;

	public function getRequestCallFunc(): ?string;

	public function getRequestCallArgs($link): array;

	public function requestCall($link): bool;

	/**
	 * The adapter shall report which PHP function will be called to process
	 * the paged results call
	 */
	public function getResponseCallFunc(): string;

	/**
	 * The adapter shall report with arguments will be provided to the LDAP
	 * function it will call
	 */
	public function getResponseCallArgs(array $originalArgs): array;

	/**
	 * the adapter should do it's LDAP function call and return success state
	 *
	 * @param resource $link LDAP resource
	 * @return bool
	 */
	public function responseCall($link): bool;

	public function setSearchArgs(
		$link,
		string $baseDN,
		string $filter,
		array $attr,
		int $attrsOnly,
		int $limit
	): void;

	public function getSearchArgs($link): array;

	/**
	 * Returns the current paged results cookie
	 *
	 * @param resource $link LDAP resource
	 * @return string
	 */
	public function getCookie($link): string;

}
