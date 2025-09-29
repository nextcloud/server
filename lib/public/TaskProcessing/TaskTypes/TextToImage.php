<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\TaskProcessing\TaskTypes;

use OCP\IL10N;
use OCP\L10N\IFactory;
use OCP\TaskProcessing\EShapeType;
use OCP\TaskProcessing\ITaskType;
use OCP\TaskProcessing\ShapeDescriptor;

/**
 * This is the task processing task type for image generation
 * @since 30.0.0
 */
class TextToImage implements ITaskType {
	/**
	 * @since 30.0.0
	 */
	public const ID = 'core:text2image';

	private IL10N $l;

	/**
	 * @param IFactory $l10nFactory
	 * @since 30.0.0
	 */
	public function __construct(
		IFactory $l10nFactory,
	) {
		$this->l = $l10nFactory->get('lib');
	}


	/**
	 * @inheritDoc
	 * @since 30.0.0
	 */
	public function getName(): string {
		return $this->l->t('Generate image');
	}

	/**
	 * @inheritDoc
	 * @since 30.0.0
	 */
	public function getDescription(): string {
		return $this->l->t('Generate an image from a text prompt');
	}

	/**
	 * @return string
	 * @since 30.0.0
	 */
	public function getId(): string {
		return self::ID;
	}

	/**
	 * @return ShapeDescriptor[]
	 * @since 30.0.0
	 */
	public function getInputShape(): array {
		return [
			'input' => new ShapeDescriptor(
				$this->l->t('Prompt'),
				$this->l->t('Describe the image you want to generate'),
				EShapeType::Text
			),
			'numberOfImages' => new ShapeDescriptor(
				$this->l->t('Number of images'),
				$this->l->t('How many images to generate'),
				EShapeType::Number
			),
		];
	}

	/**
	 * @return ShapeDescriptor[]
	 * @since 30.0.0
	 */
	public function getOutputShape(): array {
		return [
			'images' => new ShapeDescriptor(
				$this->l->t('Output images'),
				$this->l->t('The generated images'),
				EShapeType::ListOfImages
			),
		];
	}
}
