<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files\Event;

use OCP\EventDispatcher\Event;

/**
 * This event is triggered when the files app is rendered.
 *
 * @since 17.0.0
 */
class LoadAdditionalScriptsEvent extends Event {
}
