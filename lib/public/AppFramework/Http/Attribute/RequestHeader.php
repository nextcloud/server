<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\AppFramework\Http\Attribute;

use Attribute;

/**
 * This attribute allows documenting request headers and is primarily intended for OpenAPI documentation.
 * It should be added whenever you use a request header in a controller method, in order to properly describe the header and its functionality.
 * There are no checks that ensure the header is set, so you will still need to do this yourself in the controller method.
 *
 * @since 32.0.0
 */
#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class RequestHeader {
	/**
	 * @param string $name The name of the request header
	 * @param string $description The description of the request header
	 */
	public function __construct(
		protected string $name,
		protected string $description,
	) {
	}

	/**
	 * @return string The name of the request header.
	 *
	 * @since 32.0.0
	 */
	public function getName(): string {
		return $this->name;
	}

	/**
	 * @return string The description of the request header.
	 *
	 * @since 32.0.0
	 */
	public function getDescription(): string {
		return $this->description;
	}
}
