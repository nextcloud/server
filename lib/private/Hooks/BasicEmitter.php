<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Hooks;

/**
 * @deprecated 18.0.0 use \OCP\EventDispatcher\IEventDispatcher
 */
abstract class BasicEmitter implements Emitter {
	use EmitterTrait;
}
