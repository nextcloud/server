<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\TextProcessing;

use OCP\IL10N;
use OCP\L10N\IFactory;

/**
 * This is the text processing task type for free prompting
 * @since 27.1.0
 * @deprecated 30.0.0
 */
class FreePromptTaskType implements ITaskType {
	private IL10N $l;

	/**
	 * Constructor for FreePromptTaskType
	 *
	 * @param IFactory $l10nFactory
	 * @since 27.1.0
	 */
	public function __construct(
		IFactory $l10nFactory,
	) {
		$this->l = $l10nFactory->get('core');
	}


	/**
	 * @inheritDoc
	 * @since 27.1.0
	 */
	public function getName(): string {
		return $this->l->t('Free prompt');
	}

	/**
	 * @inheritDoc
	 * @since 27.1.0
	 */
	public function getDescription(): string {
		return $this->l->t('Runs an arbitrary prompt through the language model.');
	}
}
