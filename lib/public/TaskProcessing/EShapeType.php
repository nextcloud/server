<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
	case Enum = 6;
	case ListOfNumbers = 10;
	case ListOfTexts = 11;
	case ListOfImages = 12;
	case ListOfAudios = 13;
	case ListOfVideos = 14;
	case ListOfFiles = 15;

	/**
	 * @param mixed $value
	 * @param ShapeEnumValue[] $enumValues
	 * @return void
	 * @throws ValidationException
	 * @since 30.0.0
	 */
	public function validateEnum(mixed $value, array $enumValues): void {
		if ($this !== EShapeType::Enum) {
			throw new ValidationException('Provider provided enum values for non-enum slot');
		}
		foreach ($enumValues as $enumValue) {
			if ($value === $enumValue->getValue()) {
				return;
			}
		}
		throw new ValidationException('Wrong value given for Enum slot. Got "' . $value . '", but expected one of the provided enum values: "' . implode('", "', array_map(fn ($enumValue) => $enumValue->getValue(), $enumValues)) . '"');
	}

	/**
	 * @param mixed $value
	 * @return void
	 * @throws ValidationException
	 * @since 30.0.0
	 */
	private function validateNonFileType(mixed $value): void {
		if ($this === EShapeType::Enum && !is_string($value)) {
			throw new ValidationException('Non-text item provided for Enum slot');
		}
		if ($this === EShapeType::Text && !is_string($value)) {
			throw new ValidationException('Non-text item provided for Text slot');
		}
		if ($this === EShapeType::ListOfTexts && (!is_array($value) || count(array_filter($value, fn ($item) => !is_string($item))) > 0)) {
			throw new ValidationException('Non-text list item provided for ListOfTexts slot');
		}
		if ($this === EShapeType::Number && !is_numeric($value)) {
			throw new ValidationException('Non-numeric item provided for Number slot');
		}
		if ($this === EShapeType::ListOfNumbers && (!is_array($value) || count(array_filter($value, fn ($item) => !is_numeric($item))) > 0)) {
			throw new ValidationException('Non-numeric list item provided for ListOfNumbers slot');
		}
	}

	/**
	 * @param mixed $value
	 * @return void
	 * @throws Exception\ValidationException
	 * @since 30.0.0
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
		if ($this === EShapeType::ListOfAudios && (!is_array($value) || count(array_filter($value, fn ($item) => !is_numeric($item))) > 0)) {
			throw new ValidationException('Non-audio list item provided for ListOfAudio slot');
		}
		if ($this === EShapeType::Video && !is_numeric($value)) {
			throw new ValidationException('Non-video item provided for Video slot');
		}
		if ($this === EShapeType::ListOfVideos && (!is_array($value) || count(array_filter($value, fn ($item) => !is_numeric($item))) > 0)) {
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
	 * @since 30.0.0
	 */
	public function validateOutputWithFileData(mixed $value): void {
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
		if ($this === EShapeType::ListOfAudios && (!is_array($value) || count(array_filter($value, fn ($item) => !is_string($item))) > 0)) {
			throw new ValidationException('Non-audio list item provided for ListOfAudio slot');
		}
		if ($this === EShapeType::Video && !is_string($value)) {
			throw new ValidationException('Non-video item provided for Video slot');
		}
		if ($this === EShapeType::ListOfVideos && (!is_array($value) || count(array_filter($value, fn ($item) => !is_string($item))) > 0)) {
			throw new ValidationException('Non-video list item provided for ListOfTexts slot');
		}
		if ($this === EShapeType::File && !is_string($value)) {
			throw new ValidationException('Non-file item provided for File slot');
		}
		if ($this === EShapeType::ListOfFiles && (!is_array($value) || count(array_filter($value, fn ($item) => !is_string($item))) > 0)) {
			throw new ValidationException('Non-audio list item provided for ListOfFiles slot');
		}
	}

	/**
	 * @param mixed $value
	 * @return void
	 * @throws ValidationException
	 * @since 30.0.0
	 */
	public function validateOutputWithFileIds(mixed $value): void {
		$this->validateNonFileType($value);
		if ($this === EShapeType::Image && !is_numeric($value)) {
			throw new ValidationException('Non-image item provided for Image slot');
		}
		if ($this === EShapeType::ListOfImages && (!is_array($value) || count(array_filter($value, fn ($item) => !is_numeric($item))) > 0)) {
			throw new ValidationException('Non-image list item provided for ListOfImages slot');
		}
		if ($this === EShapeType::Audio && !is_string($value)) {
			throw new ValidationException('Non-audio item provided for Audio slot');
		}
		if ($this === EShapeType::ListOfAudios && (!is_array($value) || count(array_filter($value, fn ($item) => !is_numeric($item))) > 0)) {
			throw new ValidationException('Non-audio list item provided for ListOfAudio slot');
		}
		if ($this === EShapeType::Video && !is_string($value)) {
			throw new ValidationException('Non-video item provided for Video slot');
		}
		if ($this === EShapeType::ListOfVideos && (!is_array($value) || count(array_filter($value, fn ($item) => !is_numeric($item))) > 0)) {
			throw new ValidationException('Non-video list item provided for ListOfTexts slot');
		}
		if ($this === EShapeType::File && !is_string($value)) {
			throw new ValidationException('Non-file item provided for File slot');
		}
		if ($this === EShapeType::ListOfFiles && (!is_array($value) || count(array_filter($value, fn ($item) => !is_numeric($item))) > 0)) {
			throw new ValidationException('Non-audio list item provided for ListOfFiles slot');
		}
	}

	/**
	 * @param EShapeType $type
	 * @return EShapeType
	 * @since 30.0.0
	 */
	public static function getScalarType(EShapeType $type): EShapeType {
		return EShapeType::from($type->value % 10);
	}

	/**
	 * @param EShapeType $type
	 * @return bool
	 * @since 30.0.0
	 */
	public static function isFileType(EShapeType $type): bool {
		return in_array(EShapeType::getScalarType($type), [EShapeType::File, EShapeType::Image, EShapeType::Audio, EShapeType::Video], true);
	}
}
