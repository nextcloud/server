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
 * This is the task processing task type for creating headline
 * @since 30.0.0
 */
class TextToTextHeadline implements ITaskType {
	/**
	 * @since 30.0.0
	 */
	public const ID = 'core:text2text:headline';

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
		return $this->l->t('Generate a headline');
	}

	/**
	 * @inheritDoc
	 * @since 30.0.0
	 */
	public function getDescription(): string {
		return $this->l->t('Generates a possible headline for a text.');
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
				$this->l->t('Original text'),
				$this->l->t('The original text to generate a headline for'),
				EShapeType::Text
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
				$this->l->t('Headline'),
				$this->l->t('The generated headline'),
				EShapeType::Text
			),
		];
	}
}
