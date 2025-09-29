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
 * This is the task processing task type for Context Agent interaction
 * @since 32.0.0
 */
class ContextAgentAudioInteraction implements ITaskType {
	public const ID = 'core:contextagent:audio-interaction';

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
		return 'ContextAgent audio'; // We do not translate this
	}

	/**
	 * @inheritDoc
	 * @since 32.0.0
	 */
	public function getDescription(): string {
		return $this->l->t('Chat by voice with an agent');
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
				$this->l->t('Chat voice message'),
				$this->l->t('Describe a task that you want the agent to do or ask a question.'),
				EShapeType::Audio
			),
			'confirmation' => new ShapeDescriptor(
				$this->l->t('Confirmation'),
				$this->l->t('Whether to confirm previously requested actions: 0 for denial and 1 for confirmation.'),
				EShapeType::Number
			),
			'conversation_token' => new ShapeDescriptor(
				$this->l->t('Conversation token'),
				$this->l->t('A token representing the conversation.'),
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
			'conversation_token' => new ShapeDescriptor(
				$this->l->t('The new conversation token'),
				$this->l->t('Send this along with the next interaction.'),
				EShapeType::Text
			),
			'actions' => new ShapeDescriptor(
				$this->l->t('Requested actions by the agent'),
				$this->l->t('Actions that the agent would like to carry out in JSON format.'),
				EShapeType::Text
			),
		];
	}
}
