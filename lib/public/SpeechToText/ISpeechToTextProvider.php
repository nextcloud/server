<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2022 Marcel Klehr <mklehr@gmx.net>
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


namespace OCP\SpeechToText;

use OCP\Files\File;
use RuntimeException;

/**
 * @since 27.0.0
 */
interface ISpeechToTextProvider {
	/**
	 * @since 27.0.0
	 */
	public function getName(): string;

	/**
	 * @since 27.0.0
	 * @throws RuntimeException If the text could not be transcribed
	 */
	public function transcribeFile(File $file): string;
}
