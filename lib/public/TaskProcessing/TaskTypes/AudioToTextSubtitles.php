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
 * This is the task processing task type for subtitles transcription
 * @since 35.0.0
 */
class AudioToTextSubtitles implements ITaskType {
	/**
	 * @since 35.0.0
	 */
	public const ID = 'core:audio2text:subtitles';

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
		return $this->l->t('Generate subtitles');
	}

	/**
	 * @inheritDoc
	 * @since 35.0.0
	 */
	#[\Override]
	public function getDescription(): string {
		return $this->l->t('Subtitle the things said in an audio or video');
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
				$this->l->t('Input file'),
				$this->l->t('The file to subtitle'),
				EShapeType::File
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
				$this->l->t('Subtitles'),
				$this->l->t('The subtitles file'),
				EShapeType::File
			),
		];
	}
}
