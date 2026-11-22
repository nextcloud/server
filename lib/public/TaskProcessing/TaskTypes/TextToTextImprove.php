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
 * This is the task processing task type for improving text
 * @since 35.0.0
 */
class TextToTextImprove implements ITaskType {
	/**
	 * @since 35.0.0
	 */
	public const ID = 'core:text2text:improve';

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
		return $this->l->t('Improve text');
	}

	/**
	 * @inheritDoc
	 * @since 35.0.0
	 */
	#[\Override]
	public function getDescription(): string {
		return $this->l->t('Takes a text and improves it based on instructions');
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
				$this->l->t('Input text'),
				$this->l->t('Write a text that you want the assistant to improve'),
				EShapeType::Text
			),
			'instructions' => new ShapeDescriptor(
				$this->l->t('Instructions'),
				$this->l->t('Describe how the assistant should improve the text'),
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
				$this->l->t('Improved text'),
				$this->l->t('The improved text'),
				EShapeType::Text
			),
		];
	}
}
