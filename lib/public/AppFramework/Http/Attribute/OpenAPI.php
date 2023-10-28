<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Joas Schilling <coding@schilljs.com>
 *
 * @author Joas Schilling <coding@schilljs.com>
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
 * With this attribute a controller or a method can be moved into a different
 * scope or tag. Scopes should be seen as API consumers, tags can be used to group
 * different routes inside the same scope.
 *
 * @since 28.0.0
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
class OpenAPI {
	/**
	 * APIs used for normal user facing interaction with your app,
	 * e.g. when you would implement a mobile client or standalone frontend.
	 *
	 * @since 28.0.0
	 */
	public const SCOPE_DEFAULT = 'default';

	/**
	 * APIs used to administrate your app's configuration on an administrative level.
	 * Will be set automatically when admin permissions are required to access the route.
	 *
	 * @since 28.0.0
	 */
	public const SCOPE_ADMINISTRATION = 'administration';

	/**
	 * APIs used by servers to federate with each other.
	 *
	 * @since 28.0.0
	 */
	public const SCOPE_FEDERATION = 'federation';

	/**
	 * Ignore this controller or method in all generated OpenAPI specifications.
	 *
	 * @since 28.0.0
	 */
	public const SCOPE_IGNORE = 'ignore';

	/**
	 * @param self::SCOPE_*|string $scope Scopes are used to define different clients.
	 *   It is recommended to go with the scopes available as self::SCOPE_* constants,
	 *   but in exotic cases other APIs might need documentation as well,
	 *   then a free string can be provided (but it should be `a-z` only).
	 * @param ?list<string> $tags Tags can be used to group routes inside a scope
	 *   for easier implementation and reviewing of the API specification.
	 *   It defaults to the controller name in snake_case (should be `a-z` and underscore only).
	 * @since 28.0.0
	 */
	public function __construct(
		protected string $scope = self::SCOPE_DEFAULT,
		protected ?array $tags = null,
	) {
	}

	/**
	 * @since 28.0.0
	 */
	public function getScope(): string {
		return $this->scope;
	}

	/**
	 * @return ?list<string>
	 * @since 28.0.0
	 */
	public function getTags(): ?array {
		return $this->tags;
	}
}
