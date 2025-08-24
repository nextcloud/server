<?php

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCP\TaskProcessing;

/**
 * Data object for input output shape entries
 * @since 30.0.0
 */
class ShapeDescriptor implements \JsonSerializable {
	/**
	 * @param string $name
	 * @param string $description
	 * @param EShapeType $shapeType
	 * @since 30.0.0
	 */
	public function __construct(
		private string $name,
		private string $description,
		private EShapeType $shapeType,
	) {
	}

	/**
	 * @return string
	 * @since 30.0.0
	 */
	public function getName(): string {
		return $this->name;
	}

	/**
	 * @return string
	 * @since 30.0.0
	 */
	public function getDescription(): string {
		return $this->description;
	}

	/**
	 * @return EShapeType
	 * @since 30.0.0
	 */
	public function getShapeType(): EShapeType {
		return $this->shapeType;
	}

	/**
	 * @return array{name: string, description: string, type: "Number"|"Text"|"Audio"|"Image"|"Video"|"File"|"Enum"|"ListOfNumbers"|"ListOfTexts"|"ListOfImages"|"ListOfAudios"|"ListOfVideos"|"ListOfFiles"}
	 * @since 30.0.0
	 */
	public function jsonSerialize(): array {
		/** @var "Number"|"Text"|"Audio"|"Image"|"Video"|"File"|"Enum"|"ListOfNumbers"|"ListOfTexts"|"ListOfImages"|"ListOfAudios"|"ListOfVideos"|"ListOfFiles" $type */
		$type = $this->getShapeType()->name;
		return [
			'name' => $this->getName(),
			'description' => $this->getDescription(),
			'type' => $type,
		];
	}
}
