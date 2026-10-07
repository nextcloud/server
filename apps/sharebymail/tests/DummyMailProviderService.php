<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\ShareByMail\Tests;

use OCP\Mail\Provider\IMessageSend;
use OCP\Mail\Provider\IService;

interface DummyMailProviderService extends IService, IMessageSend {
}
