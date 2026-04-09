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

class BlueskyAction implements ILinkAction {
	private string $value = '';

	public function __construct(
		private IAccountManager $accountManager,
		private IFactory $l10nFactory,
		private IURLGenerator $urlGenerator,
	) {
	}

	public function preload(IUser $targetUser): void {
		$account = $this->accountManager->getAccount($targetUser);
		$this->value = $account->getProperty(IAccountManager::PROPERTY_BLUESKY)->getValue();
	}

	public function getAppId(): string {
		return 'core';
	}

	public function getId(): string {
		return IAccountManager::PROPERTY_BLUESKY;
	}

	public function getDisplayId(): string {
		return $this->l10nFactory->get('lib')->t('Bluesky');
	}

	public function getTitle(): string {
		$displayUsername = $this->value;
		return $this->l10nFactory->get('lib')->t('View %s on Bluesky', [$displayUsername]);
	}

	public function getPriority(): int {
		return 60;
	}

	public function getIcon(): string {
		return $this->urlGenerator->getAbsoluteURL($this->urlGenerator->imagePath('core', 'actions/bluesky.svg'));
	}

	public function getTarget(): ?string {
		if (empty($this->value)) {
			return null;
		}
		$username = $this->value;
		return 'https://bsky.app/profile/' . $username;
	}
}
