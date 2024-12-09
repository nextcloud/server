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
 * This is the task processing task type for text reformulation
 * @since 31.0.0
 */
class TextToTextChangeTone implements ITaskType {
	public const ID = 'core:text2text:changetone';

	private IL10N $l;

	/**
	 * @param IFactory $l10nFactory
	 * @since 31.0.0
	 */
	public function __construct(
		IFactory $l10nFactory,
	) {
		$this->l = $l10nFactory->get('core');
	}

	/**
	 * @inheritDoc
	 * @since 31.0.0
	 */
	public function getName(): string {
		return $this->l->t('Change Tone');
	}

	/**
	 * @inheritDoc
	 * @since 31.0.0
	 */
	public function getDescription(): string {
		return $this->l->t('Change the tone of a piece of text.');
	}

	/**
	 * @return string
	 * @since 31.0.0
	 */
	public function getId(): string {
		return self::ID;
	}

	/**
	 * @return ShapeDescriptor[]
	 * @since 31.0.0
	 */
	public function getInputShape(): array {
		return [
			'input' => new ShapeDescriptor(
				$this->l->t('Input text'),
				$this->l->t('Write a text that you want the assistant to rewrite in another tone.'),
				EShapeType::Text,
			),
			'tone' => new ShapeDescriptor(
				$this->l->t('Desired tone'),
				$this->l->t('In which tone should your text be rewritten?'),
				EShapeType::Enum,
			),
		];
	}

	/**
	 * @return ShapeDescriptor[]
	 * @since 31.0.0
	 */
	public function getOutputShape(): array {
		return [
			'output' => new ShapeDescriptor(
				$this->l->t('Generated response'),
				$this->l->t('The rewritten text in the desired tone, written by the assistant:'),
				EShapeType::Text
			),
		];
	}
}
