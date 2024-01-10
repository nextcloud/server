<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2024 Kate Döen <kate.doeen@nextcloud.com>
 *
 * @author Kate Döen <kate.doeen@nextcloud.com>
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
 */

namespace OCP\AppFramework\Http\Attribute;

use Attribute;

/**
 * This attribute can be used to define routes on controller methods.
 *
 * It works in addition to the traditional routes.php method and has the same parameters
 * (except for the `name` parameter which is not needed).
 *
 * @since 29.0.0
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class Route {

	/**
	 * Corresponds to the `ocs` key in routes.php
	 *
	 * @see ApiRoute
	 * @since 29.0.0
	 */
	public const TYPE_API = 'ocs';

	/**
	 * Corresponds to the `routes` key in routes.php
	 *
	 * @see FrontpageRoute
	 * @since 29.0.0
	 */
	public const TYPE_FRONTPAGE = 'routes';

	/**
	 * @param string $type Either Route::TYPE_API or Route::TYPE_FRONTPAGE.
	 * @psalm-param Route::TYPE_* $type
	 * @param string $verb HTTP method of the route.
	 * @psalm-param 'GET'|'HEAD'|'POST'|'PUT'|'DELETE'|'OPTIONS'|'PATCH' $verb
	 * @param string $url The path of the route.
	 * @param ?array<string, string> $requirements Array of regexes mapped to the path parameters.
	 * @param ?array<string, mixed> $defaults Array of default values mapped to the path parameters.
	 * @param ?string $root Custom root. For OCS all apps are allowed, but for index.php only some can use it.
	 * @param ?string $postfix Postfix for the route name.
	 * @since 29.0.0
	 */
	public function __construct(
		protected string $type,
		protected string $verb,
		protected string $url,
		protected ?array $requirements = null,
		protected ?array $defaults = null,
		protected ?string $root = null,
		protected ?string $postfix = null,
	) {
	}

	/**
	 * @return array{
	 *     verb: string,
	 *     url: string,
	 *     requirements?: array<string, string>,
	 *     defaults?: array<string, mixed>,
	 *     root?: string,
	 *     postfix?: string,
	 * }
	 * @since 29.0.0
	 */
	public function toArray() {
		$route = [
			'verb' => $this->verb,
			'url' => $this->url,
		];

		if ($this->requirements !== null) {
			$route['requirements'] = $this->requirements;
		}
		if ($this->defaults !== null) {
			$route['defaults'] = $this->defaults;
		}
		if ($this->root !== null) {
			$route['root'] = $this->root;
		}
		if ($this->postfix !== null) {
			$route['postfix'] = $this->postfix;
		}

		return $route;
	}

	/**
	 * @since 29.0.0
	 */
	public function getType(): string {
		return $this->type;
	}

	/**
	 * @since 29.0.0
	 */
	public function getVerb(): string {
		return $this->verb;
	}

	/**
	 * @since 29.0.0
	 */
	public function getUrl(): string {
		return $this->url;
	}

	/**
	 * @since 29.0.0
	 */
	public function getRequirements(): ?array {
		return $this->requirements;
	}

	/**
	 * @since 29.0.0
	 */
	public function getDefaults(): ?array {
		return $this->defaults;
	}

	/**
	 * @since 29.0.0
	 */
	public function getRoot(): ?string {
		return $this->root;
	}

	/**
	 * @since 29.0.0
	 */
	public function getPostfix(): ?string {
		return $this->postfix;
	}
}
