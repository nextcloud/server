<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
	 * APIs used by ExApps.
	 * Will be set automatically when an ExApp is required to access the route.
	 *
	 * @since 30.0.0
	 */
	public const SCOPE_EX_APP = 'ex_app';

	/**
	 * @param self::SCOPE_*|string $scope Scopes are used to define different clients.
	 *                                    It is recommended to go with the scopes available as self::SCOPE_* constants,
	 *                                    but in exotic cases other APIs might need documentation as well,
	 *                                    then a free string can be provided (but it should be `a-z` only).
	 * @param ?list<string> $tags Tags can be used to group routes inside a scope
	 *                            for easier implementation and reviewing of the API specification.
	 *                            It defaults to the controller name in snake_case (should be `a-z` and underscore only).
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
