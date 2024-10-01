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
 * This is the task processing task type for generic text processing
 * @since 30.0.0
 */
class TextToTextTranslate implements ITaskType {
	/**
	 * @since 30.0.0
	 */
	public const ID = 'core:text2text:translate';

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
		return $this->l->t('Translate');
	}

	/**
	 * @inheritDoc
	 * @since 30.0.0
	 */
	public function getDescription(): string {
		return $this->l->t('Translate text from one language to another');
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
			'input' => new ShapeDescriptor(
				$this->l->t('Origin text'),
				$this->l->t('The text to translate'),
				EShapeType::Text
			),
			'origin_language' => new ShapeDescriptor(
				$this->l->t('Origin language'),
				$this->l->t('The language of the origin text'),
				EShapeType::Enum
			),
			'target_language' => new ShapeDescriptor(
				$this->l->t('Target language'),
				$this->l->t('The desired language to translate the origin text in'),
				EShapeType::Enum
			),
		];
	}

	/**
	 * @return ShapeDescriptor[]
	 * @since 30.0.0
	 */
	public function getOutputShape(): array {
		return [
			'output' => new ShapeDescriptor(
				$this->l->t('Result'),
				$this->l->t('The translated text'),
				EShapeType::Text
			),
		];
	}
}
