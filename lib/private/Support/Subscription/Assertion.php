<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Support\Subscription;

use OCP\HintException;
use OCP\L10N\IFactory;
use OCP\Notification\IManager;
use OCP\Support\Subscription\IAssertion;
use OCP\Support\Subscription\IRegistry;

class Assertion implements IAssertion {
	private IRegistry $registry;
	private IFactory $l10nFactory;
	private IManager $notificationManager;

	public function __construct(IRegistry $registry, IFactory $l10nFactory, IManager $notificationManager) {
		$this->registry = $registry;
		$this->l10nFactory = $l10nFactory;
		$this->notificationManager = $notificationManager;
	}

	/**
	 * @inheritDoc
	 */
	public function createUserIsLegit(): void {
		if ($this->registry->delegateIsHardUserLimitReached($this->notificationManager)) {
			$l = $this->l10nFactory->get('lib');
			throw new HintException($l->t('The user was not created because the user limit has been reached. Check your notifications to learn more.'));
		}
	}
}
