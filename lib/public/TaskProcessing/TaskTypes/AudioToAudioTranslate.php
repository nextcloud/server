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
 * This is the task processing task type for audio to audio translation
 * @since 35.0.0
 */
class AudioToAudioTranslate implements ITaskType {
	/**
	 * @since 35.0.0
	 */
	public const ID = 'core:audio2audio:translate';

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
		return $this->l->t('Translate audio');
	}

	/**
	 * @inheritDoc
	 * @since 35.0.0
	 */
	#[\Override]
	public function getDescription(): string {
		return $this->l->t('Translates the speech of an audio file or recording and outputs an audio file in the desired language.');
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
				$this->l->t('Input audio'),
				$this->l->t('The audio file or recording to translate'),
				EShapeType::Audio,
			),
			'origin_language' => new ShapeDescriptor(
				$this->l->t('Origin language'),
				$this->l->t('The language of the origin audio'),
				EShapeType::Enum,
			),
			'target_language' => new ShapeDescriptor(
				$this->l->t('Target language'),
				$this->l->t('The desired language to translate the origin audio in'),
				EShapeType::Enum,
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
			'audio_output' => new ShapeDescriptor(
				$this->l->t('Audio output'),
				$this->l->t('The audio translation'),
				EShapeType::Audio,
			),
		];
	}
}
