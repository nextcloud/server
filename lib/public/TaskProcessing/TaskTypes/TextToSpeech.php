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
 * This is the task processing task type for speech generation
 * @since 32.0.0
 */
class TextToSpeech implements ITaskType {
	/**
	 * @since 32.0.0
	 */
	public const ID = 'core:text2speech';

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
		return $this->l->t('Generate speech');
	}

	/**
	 * @inheritDoc
	 * @since 32.0.0
	 */
	public function getDescription(): string {
		return $this->l->t('Generate speech from a transcript');
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
			'input' => new ShapeDescriptor(
				$this->l->t('Prompt'),
				$this->l->t('Write transcript that you want the assistant to generate speech from'),
				EShapeType::Text
			),
		];
	}

	/**
	 * @return ShapeDescriptor[]
	 * @since 32.0.0
	 */
	public function getOutputShape(): array {
		return [
			'speech' => new ShapeDescriptor(
				$this->l->t('Output speech'),
				$this->l->t('The generated speech'),
				EShapeType::Audio
			),
		];
	}
}
