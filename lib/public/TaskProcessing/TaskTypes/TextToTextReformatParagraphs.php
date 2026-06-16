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
 * This is the task processing task type for reformatting text into paragraphs
 * @since 34.0.0
 */
class TextToTextReformatParagraphs implements ITaskType {
	/**
	 * @since 34.0.0
	 */
	public const ID = 'core:text2text:reformatparagraphs';

	private IL10N $l;

	/**
	 * @param IFactory $l10nFactory
	 * @since 34.0.0
	 */
	public function __construct(
		IFactory $l10nFactory,
	) {
		$this->l = $l10nFactory->get('lib');
	}

	/**
	 * @inheritDoc
	 * @since 34.0.0
	 */
	#[\Override]
	public function getName(): string {
		return $this->l->t('Reformat paragraphs');
	}

	/**
	 * @inheritDoc
	 * @since 34.0.0
	 */
	#[\Override]
	public function getDescription(): string {
		return $this->l->t('Reformats a text into multiple paragraphs separated by topic');
	}

	/**
	 * @return string
	 * @since 34.0.0
	 */
	#[\Override]
	public function getId(): string {
		return self::ID;
	}

	/**
	 * @return ShapeDescriptor[]
	 * @since 34.0.0
	 */
	#[\Override]
	public function getInputShape(): array {
		return [
			'input' => new ShapeDescriptor(
				$this->l->t('Text'),
				$this->l->t('The text to reformat'),
				EShapeType::Text
			),
		];
	}

	/**
	 * @return ShapeDescriptor[]
	 * @since 34.0.0
	 */
	#[\Override]
	public function getOutputShape(): array {
		return [
			'output' => new ShapeDescriptor(
				$this->l->t('Reformatted text'),
				$this->l->t('The reformatted text with paragraphs separated by topic'),
				EShapeType::Text
			),
		];
	}
}
