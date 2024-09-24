<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\CloudFederationAPI;

/**
 * @psalm-type CloudFederationAPIAddShare = array{
 *     recipientDisplayName: string,
 *     recipientUserId?: string,
 * }
 *
 * @psalm-type CloudFederationAPIError = array{
 *     message: string,
 * }
 *
 * @psalm-type CloudFederationAPIValidationError = CloudFederationAPIError&array{
 *     validationErrors: list<array{
 *          name: string,
 *          message: string|null,
 *     }>,
 * }
 */
class ResponseDefinitions {
}
