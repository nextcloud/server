<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\AppFramework\Http;

use OCP\AppFramework\Http;

/**
 * A template response that does not emit the loadAdditionalScripts events.
 *
 * This is useful for pages that are authenticated but do not yet show the
 * full nextcloud UI. Like the 2FA page, or the grant page in the login flow.
 *
 * @since 16.0.0
 * @template S of Http::STATUS_*
 * @template H of array<string, mixed>
 * @template-extends TemplateResponse<Http::STATUS_*, array<string, mixed>>
 */
class StandaloneTemplateResponse extends TemplateResponse {
}
