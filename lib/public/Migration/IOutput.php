<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Vincent Petry <vincent@nextcloud.com>
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
namespace OCP\Migration;

/**
 * Interface IOutput
 *
 * @since 9.1.0
 */
interface IOutput {
	/**
	 * @param string $message
	 * @return void
	 * @since 28.0.0
	 */
	public function debug(string $message): void;

	/**
	 * @param string $message
	 * @return void
	 * @since 9.1.0
	 */
	public function info($message);

	/**
	 * @param string $message
	 * @return void
	 * @since 9.1.0
	 */
	public function warning($message);

	/**
	 * @param int $max
	 * @return void
	 * @since 9.1.0
	 */
	public function startProgress($max = 0);

	/**
	 * @param int $step
	 * @param string $description
	 * @return void
	 * @since 9.1.0
	 */
	public function advance($step = 1, $description = '');

	/**
	 * @return void
	 * @since 9.1.0
	 */
	public function finishProgress();
}
