<?php

declare(strict_types=1);

/**
 * @copyright 2022 Maxence Lange <maxence@artificial-owl.com>
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
namespace OCP\Search;

/**
 * The query objected passed into \OCP\Search\IProvider::search
 *
 * This mainly wraps the search term, but will ensure that Nextcloud can add new
 * optional properties to a search request without having break the interface of
 * \OCP\Search\IProvider::search.
 *
 * @see \OCP\Search\IProvider::search
 *
 * @since 24.0.0
 */
interface ISearchOptions {

	public function getKeys(): array;

	public function hasKey(string $key): bool;

	public function getOptions(): array;

	public function getOption(string $key): string;

	public function getOptionInt(string $key): int;

	public function getOptionBool(string $key): bool;

	public function getOptionArray(string $key): array;
}
