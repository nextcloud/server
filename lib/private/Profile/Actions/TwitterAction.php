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
use function substr;

class TwitterAction implements ILinkAction {
	private string $value = '';

	public function __construct(
		private IAccountManager $accountManager,
		private IFactory $l10nFactory,
		private IURLGenerator $urlGenerator,
	) {
	}

	public function preload(IUser $targetUser): void {
		$account = $this->accountManager->getAccount($targetUser);
		$this->value = $account->getProperty(IAccountManager::PROPERTY_TWITTER)->getValue();
	}

	public function getAppId(): string {
		return 'core';
	}

	public function getId(): string {
		return IAccountManager::PROPERTY_TWITTER;
	}

	public function getDisplayId(): string {
		return $this->l10nFactory->get('lib')->t('Twitter');
	}

	public function getTitle(): string {
		$displayUsername = $this->value[0] === '@' ? $this->value : '@' . $this->value;
		return $this->l10nFactory->get('lib')->t('View %s on Twitter', [$displayUsername]);
	}

	public function getPriority(): int {
		return 50;
	}

	public function getIcon(): string {
		return $this->urlGenerator->getAbsoluteURL($this->urlGenerator->imagePath('core', 'actions/twitter.svg'));
	}

	public function getTarget(): ?string {
		if (empty($this->value)) {
			return null;
		}
		$username = $this->value[0] === '@' ? substr($this->value, 1) : $this->value;
		return 'https://twitter.com/' . $username;
	}
}
