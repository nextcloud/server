<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\TaskProcessing\TaskTypes;

use OCP\IL10N;
use OCP\L10N\IFactory;
use OCP\TaskProcessing\EShapeType;
use OCP\TaskProcessing\ITaskType;
use OCP\TaskProcessing\ShapeDescriptor;

/**
 * This is the task processing task type for editing images
 * @since 35.0.0
 */
class ImageToImage implements ITaskType {
	/**
	 * @since 35.0.0
	 */
	public const ID = 'core:image2image';

	private IL10N $l;

	/**
	 * @param IFactory $l10nFactory
	 * @since 35.0.0
	 */
	public function __construct(
		IFactory $l10nFactory,
	) {
		$this->l = $l10nFactory->get('lib');
	}

	/**
	 * @inheritDoc
	 * @since 35.0.0
	 */
	#[\Override]
	public function getName(): string {
		return $this->l->t('Edit image');
	}

	/**
	 * @inheritDoc
	 * @since 35.0.0
	 */
	#[\Override]
	public function getDescription(): string {
		return $this->l->t('Edit an image based on a text description of the changes');
	}

	/**
	 * @return string
	 * @since 35.0.0
	 */
	#[\Override]
	public function getId(): string {
		return self::ID;
	}

	/**
	 * @return ShapeDescriptor[]
	 * @since 35.0.0
	 */
	#[\Override]
	public function getInputShape(): array {
		return [
			'input' => new ShapeDescriptor(
				$this->l->t('Input images'),
				$this->l->t('The images to edit'),
				EShapeType::ListOfImages
			),
			'prompt' => new ShapeDescriptor(
				$this->l->t('Prompt'),
				$this->l->t('Describe the changes you want to make to the image'),
				EShapeType::Text
			),
		];
	}

	/**
	 * @return ShapeDescriptor[]
	 * @since 35.0.0
	 */
	#[\Override]
	public function getOutputShape(): array {
		return [
			'output' => new ShapeDescriptor(
				$this->l->t('Output image'),
				$this->l->t('The edited image'),
				EShapeType::Image
			),
		];
	}
}
