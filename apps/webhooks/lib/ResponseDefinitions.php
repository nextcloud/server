<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Webhooks;

/**
 * @psalm-type WebhooksListenerInfo = array{
 *     id: string,
 *     userId: string,
 *     httpMethod: string,
 *     uri: string,
 *     event?: string,
 *     eventFilter?: array<mixed>,
 *     headers?: array<string,string>,
 *     authMethod: string,
 *     authData?: array<string,mixed>,
 * }
 */
class ResponseDefinitions {
}
