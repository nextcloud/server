<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\AppFramework\Http\Attribute;

use Attribute;

/**
 * This attribute can be used to define API routes on controller methods.
 *
 * It works in addition to the traditional routes.php method and has the same parameters
 * (except for the `name` parameter which is not needed).
 *
 * @since 29.0.0
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class ApiRoute extends Route {
	/**
	 * @inheritDoc
	 *
	 * @since 29.0.0
	 */
	public function __construct(
		protected string $verb,
		protected string $url,
		protected ?array $requirements = null,
		protected ?array $defaults = null,
		protected ?string $root = null,
		protected ?string $postfix = null,
	) {
		parent::__construct(
			Route::TYPE_API,
			$verb,
			$url,
			$requirements,
			$defaults,
			$root,
			$postfix,
		);
	}
}
