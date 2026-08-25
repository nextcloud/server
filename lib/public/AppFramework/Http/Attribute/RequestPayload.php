<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\AppFramework\Http\Attribute;

use Attribute;

/**
 * Marks a controller method parameter as populated from the JSON request body
 *
 * The parameter's type is deserialized from the request body with
 * {@see \OCP\Serializer\ISerializer} and validated with {@see \OCP\Validator\IValidator}
 * before the controller method is called. A malformed or unprocessable body never reaches
 * the controller: it short-circuits into a 400 or 422 response.
 *
 * ```
 * class PersonController extends Controller {
 *     public function create(#[RequestPayload] PersonDto $person): DataResponse {
 *         // $person is already deserialized and valid at this point
 *     }
 * }
 * ```
 *
 * @since 36.0.0
 */
#[Attribute(Attribute::TARGET_PARAMETER)]
final class RequestPayload {
	/**
	 * @param string|string[]|null $validationGroups only constraints tagged with one of these
	 *                                               groups are checked, `null` checks every
	 *                                               constraint regardless of its groups
	 * @since 36.0.0
	 */
	public function __construct(
		public readonly string|array|null $validationGroups = null,
	) {
	}
}
