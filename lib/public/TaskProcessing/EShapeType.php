<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2024 Marcel Klehr <mklehr@gmx.net>
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

namespace OCP\TaskProcessing;

use OCP\TaskProcessing\Exception\ValidationException;

/**
 * The input and output Shape types
 *
 * @since 30.0.0
 */
enum EShapeType: int {
	case Number = 0;
	case Text = 1;
	case Image = 2;
	case Audio = 3;
	case Video = 4;
	case File = 5;
	case ListOfNumbers = 10;
	case ListOfTexts = 11;
	case ListOfImages = 12;
	case ListOfAudio = 13;
	case ListOfVideo = 14;
	case ListOfFiles = 15;

	/**
	 * @param mixed $value
	 * @return void
	 * @throws ValidationException
	 */
	private function validateNonFileType(mixed $value): void {
		if ($this === EShapeType::Text && !is_string($value)) {
			throw new ValidationException('Non-text item provided for Text slot');
		}
		if ($this === EShapeType::ListOfTexts && (!is_array($value) || count(array_filter($value, fn($item) => !is_string($item))) > 0)) {
			throw new ValidationException('Non-text list item provided for ListOfTexts slot');
		}
		if ($this === EShapeType::Number && !is_numeric($value)) {
			throw new ValidationException('Non-numeric item provided for Number slot');
		}
		if ($this === EShapeType::ListOfNumbers && (!is_array($value) || count(array_filter($value, fn($item) => !is_numeric($item))) > 0)) {
			throw new ValidationException('Non-numeric list item provided for ListOfNumbers slot');
		}
	}

	/**
	 * @param mixed $value
	 * @return void
	 * @throws Exception\ValidationException
	 */
	public function validateInput(mixed $value): void {
		$this->validateNonFileType($value);
		if ($this === EShapeType::Image && !is_numeric($value)) {
			throw new ValidationException('Non-image item provided for Image slot');
		}
		if ($this === EShapeType::ListOfImages && (!is_array($value) || count(array_filter($value, fn ($item) => !is_numeric($item))) > 0)) {
			throw new ValidationException('Non-image list item provided for ListOfImages slot');
		}
		if ($this === EShapeType::Audio && !is_numeric($value)) {
			throw new ValidationException('Non-audio item provided for Audio slot');
		}
		if ($this === EShapeType::ListOfAudio && (!is_array($value) || count(array_filter($value, fn ($item) => !is_numeric($item))) > 0)) {
			throw new ValidationException('Non-audio list item provided for ListOfAudio slot');
		}
		if ($this === EShapeType::Video && !is_numeric($value)) {
			throw new ValidationException('Non-video item provided for Video slot');
		}
		if ($this === EShapeType::ListOfVideo && (!is_array($value) || count(array_filter($value, fn ($item) => !is_numeric($item))) > 0)) {
			throw new ValidationException('Non-video list item provided for ListOfTexts slot');
		}
		if ($this === EShapeType::File && !is_numeric($value)) {
			throw new ValidationException('Non-file item provided for File slot');
		}
		if ($this === EShapeType::ListOfFiles && (!is_array($value) || count(array_filter($value, fn ($item) => !is_numeric($item))) > 0)) {
			throw new ValidationException('Non-audio list item provided for ListOfFiles slot');
		}
	}

	/**
	 * @throws ValidationException
	 */
	public function validateOutput(mixed $value) {
		$this->validateNonFileType($value);
		if ($this === EShapeType::Image && !is_string($value)) {
			throw new ValidationException('Non-image item provided for Image slot');
		}
		if ($this === EShapeType::ListOfImages && (!is_array($value) || count(array_filter($value, fn ($item) => !is_string($item))) > 0)) {
			throw new ValidationException('Non-image list item provided for ListOfImages slot');
		}
		if ($this === EShapeType::Audio && !is_string($value)) {
			throw new ValidationException('Non-audio item provided for Audio slot');
		}
		if ($this === EShapeType::ListOfAudio && (!is_array($value) || count(array_filter($value, fn ($item) => !is_string($item))) > 0)) {
			throw new ValidationException('Non-audio list item provided for ListOfAudio slot');
		}
		if ($this === EShapeType::Video && !is_string($value)) {
			throw new ValidationException('Non-video item provided for Video slot');
		}
		if ($this === EShapeType::ListOfVideo && (!is_array($value) || count(array_filter($value, fn ($item) => !is_string($item))) > 0)) {
			throw new ValidationException('Non-video list item provided for ListOfTexts slot');
		}
		if ($this === EShapeType::File && !is_string($value)) {
			throw new ValidationException('Non-file item provided for File slot');
		}
		if ($this === EShapeType::ListOfFiles && (!is_array($value) || count(array_filter($value, fn ($item) => !is_string($item))) > 0)) {
			throw new ValidationException('Non-audio list item provided for ListOfFiles slot');
		}
	}

	public static function getScalarType(EShapeType $type): EShapeType {
		return EShapeType::from($type->value % 10);
	}
}
