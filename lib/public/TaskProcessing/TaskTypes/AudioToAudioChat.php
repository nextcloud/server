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
 * This is the task processing task type for audio chat
 * @since 32.0.0
 */
class AudioToAudioChat implements ITaskType {
	/**
	 * @since 32.0.0
	 */
	public const ID = 'core:audio2audio:chat';

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
		return $this->l->t('Audio chat');
	}

	/**
	 * @inheritDoc
	 * @since 32.0.0
	 */
	public function getDescription(): string {
		return $this->l->t('Voice chat with the assistant');
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
			'system_prompt' => new ShapeDescriptor(
				$this->l->t('System prompt'),
				$this->l->t('Define rules and assumptions that the assistant should follow during the conversation.'),
				EShapeType::Text
			),
			'input' => new ShapeDescriptor(
				$this->l->t('Chat voice message'),
				$this->l->t('Describe a task that you want the assistant to do or ask a question.'),
				EShapeType::Audio
			),
			'history' => new ShapeDescriptor(
				$this->l->t('Chat history'),
				$this->l->t('The history of chat messages before the current message, starting with a message by the user.'),
				EShapeType::ListOfTexts
			)
		];
	}

	/**
	 * @return ShapeDescriptor[]
	 * @since 32.0.0
	 */
	public function getOutputShape(): array {
		return [
			'input_transcript' => new ShapeDescriptor(
				$this->l->t('Input transcript'),
				$this->l->t('Transcription of the audio input'),
				EShapeType::Text,
			),
			'output' => new ShapeDescriptor(
				$this->l->t('Response voice message'),
				$this->l->t('The generated voice response as part of the conversation'),
				EShapeType::Audio
			),
			'output_transcript' => new ShapeDescriptor(
				$this->l->t('Output transcript'),
				$this->l->t('Transcription of the audio output'),
				EShapeType::Text,
			),
		];
	}
}
