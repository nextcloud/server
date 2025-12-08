<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\TaskProcessing\TaskTypes;

use OCP\IL10N;
use OCP\L10N\IFactory;
use OCP\TaskProcessing\EShapeType;
use OCP\TaskProcessing\ITaskType;
use OCP\TaskProcessing\ShapeDescriptor;

/**
 * This is the task processing task type for OCR
 * @since 33.0.0
 */
class ImageToTextOpticalCharacterRecognition implements ITaskType {
	/**
	 * @since 33.0.0
	 */
	public const ID = 'core:image2text:ocr';

	private IL10N $l;

	/**
	 * @since 33.0.0
	 */
	public function __construct(
		IFactory $l10nFactory,
	) {
		$this->l = $l10nFactory->get('lib');
	}


	/**
	 * @since 33.0.0
	 */
	public function getName(): string {
		return $this->l->t('Optical character recognition');
	}

	/**
	 * @since 33.0.0
	 */
	public function getDescription(): string {
		return $this->l->t('Extract text from an image');
	}

	/**
	 * @since 33.0.0
	 */
	public function getId(): string {
		return self::ID;
	}

	/**
	 * @return ShapeDescriptor[]
	 * @since 33.0.0
	 */
	public function getInputShape(): array {
		return [
			'input' => new ShapeDescriptor(
				$this->l->t('Input Image'),
				$this->l->t('The image to extract text from'),
				EShapeType::Image
			),
		];
	}

	/**
	 * @return ShapeDescriptor[]
	 * @since 33.0.0
	 */
	public function getOutputShape(): array {
		return [
			'output' => new ShapeDescriptor(
				$this->l->t('Output text'),
				$this->l->t('The text that was extracted from the image'),
				EShapeType::Text
			),
		];
	}
}
