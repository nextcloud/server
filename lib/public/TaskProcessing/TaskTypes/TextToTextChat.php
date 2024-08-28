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
 * This is the task processing task type for text chat
 * @since 30.0.0
 */
class TextToTextChat implements ITaskType {
	/**
	 * @since 30.0.0
	 */
	public const ID = 'core:text2text:chat';

	private IL10N $l;

	/**
	 * @param IFactory $l10nFactory
	 * @since 30.0.0
	 */
	public function __construct(
		IFactory $l10nFactory,
	) {
		$this->l = $l10nFactory->get('core');
	}


	/**
	 * @inheritDoc
	 * @since 30.0.0
	 */
	public function getName(): string {
		return $this->l->t('Chat');
	}

	/**
	 * @inheritDoc
	 * @since 30.0.0
	 */
	public function getDescription(): string {
		return $this->l->t('Chat with the assistant');
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
			'system_prompt' => new ShapeDescriptor(
				$this->l->t('System prompt'),
				$this->l->t('Define rules and assumptions that the assistant should follow during the conversation.'),
				EShapeType::Text
			),
			'input' => new ShapeDescriptor(
				$this->l->t('Chat message'),
				$this->l->t('Describe a task that you want the assistant to do or ask a question'),
				EShapeType::Text
			),
			'history' => new ShapeDescriptor(
				$this->l->t('Chat history'),
				$this->l->t('The history of chat messages before the current message, starting with a message by the user'),
				EShapeType::ListOfTexts
			)
		];
	}

	/**
	 * @return ShapeDescriptor[]
	 * @since 30.0.0
	 */
	public function getOutputShape(): array {
		return [
			'output' => new ShapeDescriptor(
				$this->l->t('Response message'),
				$this->l->t('The generated response as part of the conversation'),
				EShapeType::Text
			),
		];
	}
}
