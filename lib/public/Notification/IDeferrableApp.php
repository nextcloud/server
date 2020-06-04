<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2020, Joas Schilling <coding@schilljs.com>
 *
 * @author Joas Schilling <coding@schilljs.com>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCP\Notification;

/**
 * Interface IDeferrableApp
 *
 * @package OCP\Notification
 * @since 20.0.0
 */
interface IDeferrableApp extends IApp {
	/**
	 * Start deferring notifications until `flush()` is called
	 *
	 * @since 20.0.0
	 */
	public function defer(): void;

	/**
	 * Send all deferred notifications that have been stored since `defer()` was called
	 *
	 * @since 20.0.0
	 */
	public function flush(): void;
}
