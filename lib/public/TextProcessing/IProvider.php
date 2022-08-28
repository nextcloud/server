<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Marcel Klehr <mklehr@gmx.net>
 *
 * @author Marcel Klehr <mklehr@gmx.net>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */


namespace OCP\TextProcessing;

use RuntimeException;

/**
 * This is the interface that is implemented by apps that
 * implement a text processing provider
 * @psalm-template-covariant  T of ITaskType
 * @since 27.1.0
 */
interface IProvider {
	/**
	 * The localized name of this provider
	 * @since 27.1.0
	 */
	public function getName(): string;

	/**
	 * Processes a text
	 *
	 * @param string $prompt The input text
	 * @return string the output text
	 * @since 27.1.0
	 * @throws RuntimeException If the text could not be processed
	 */
	public function process(string $prompt): string;

	/**
	 * Returns the task type class string of the task type, that this
	 * provider handles
	 *
	 * @since 27.1.0
	 * @return class-string<T>
	 */
	public function getTaskType(): string;
}
