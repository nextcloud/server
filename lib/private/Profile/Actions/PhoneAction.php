<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Profile\Actions;

use OCP\Accounts\IAccountManager;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\L10N\IFactory;
use OCP\Profile\ILinkAction;

class PhoneAction implements ILinkAction {
	private string $value = '';

	public function __construct(
		private IAccountManager $accountManager,
		private IFactory $l10nFactory,
		private IURLGenerator $urlGenerator,
	) {
	}

	#[\Override]
	public function preload(IUser $targetUser): void {
		$account = $this->accountManager->getAccount($targetUser);
		$this->value = $account->getProperty(IAccountManager::PROPERTY_PHONE)->getValue();
	}

	#[\Override]
	public function getAppId(): string {
		return 'core';
	}

	#[\Override]
	public function getId(): string {
		return IAccountManager::PROPERTY_PHONE;
	}

	#[\Override]
	public function getDisplayId(): string {
		return $this->l10nFactory->get('lib')->t('Phone');
	}

	#[\Override]
	public function getTitle(): string {
		return $this->l10nFactory->get('lib')->t('Call %s', [$this->value]);
	}

	#[\Override]
	public function getPriority(): int {
		return 30;
	}

	#[\Override]
	public function getIcon(): string {
		return $this->urlGenerator->getAbsoluteURL($this->urlGenerator->imagePath('core', 'actions/phone.svg'));
	}

	#[\Override]
	public function getTarget(): ?string {
		if (empty($this->value)) {
			return null;
		}
		return 'tel:' . $this->value;
	}
}
