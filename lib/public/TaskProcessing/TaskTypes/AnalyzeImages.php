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
 * This is the task processing task type to ask a question about the images
 * @since 32.0.0
 */
class AnalyzeImages implements ITaskType {
	/**
	 * @since 32.0.0
	 */
	public const ID = 'core:analyze-images';

	private IL10N $l;

	/**
	 * @param IFactory $l10nFactory
	 * @since 32.0.0
	 */
	public function __construct(
		IFactory $l10nFactory,
	) {
		$this->l = $l10nFactory->get('lib');
	}

	/**
	 * @inheritDoc
	 * @since 32.0.0
	 */
	public function getName(): string {
		return $this->l->t('Analyze images');
	}

	/**
	 * @inheritDoc
	 * @since 32.0.0
	 */
	public function getDescription(): string {
		return $this->l->t('Ask a question about the given images.');
	}

	/**
	 * @return string
	 * @since 32.0.0
	 */
	public function getId(): string {
		return self::ID;
	}

	/**
	 * @return ShapeDescriptor[]
	 * @since 32.0.0
	 */
	public function getInputShape(): array {
		return [
			'images' => new ShapeDescriptor(
				$this->l->t('Images'),
				$this->l->t('Images to ask a question about'),
				EShapeType::ListOfImages,
			),
			'input' => new ShapeDescriptor(
				$this->l->t('Question'),
				$this->l->t('What to ask about the images.'),
				EShapeType::Text,
			),
		];
	}

	/**
	 * @return ShapeDescriptor[]
	 * @since 32.0.0
	 */
	public function getOutputShape(): array {
		return [
			'output' => new ShapeDescriptor(
				$this->l->t('Generated response'),
				$this->l->t('The answer to the question'),
				EShapeType::Text
			),
		];
	}
}
