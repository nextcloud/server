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
 * This is the task processing task type for proofreading
 * @since 31.0.0
 */
class TextToTextProofread implements ITaskType {
	/**
	 * @since 31.0.0
	 */
	public const ID = 'core:text2text:proofread';
	private IL10N $l;

	/**
	 * @param IFactory $l10nFactory
	 * @since 31.0.0
	 */
	public function __construct(
		IFactory $l10nFactory,
	) {
		$this->l = $l10nFactory->get('lib');
	}


	/**
	 * @inheritDoc
	 * @since 31.0.0
	 */
	public function getName(): string {
		return $this->l->t('Proofread');
	}

	/**
	 * @inheritDoc
	 * @since 31.0.0
	 */
	public function getDescription(): string {
		return $this->l->t('Proofreads a text and lists corrections');
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
				$this->l->t('Text'),
				$this->l->t('The text to proofread'),
				EShapeType::Text
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
				$this->l->t('Corrections'),
				$this->l->t('The corrections that should be made in your text'),
				EShapeType::Text
			),
		];
	}
}
