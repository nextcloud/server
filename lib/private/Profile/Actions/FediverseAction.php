<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Profile\Actions;

use OCP\Accounts\IAccountManager;
use OCP\Accounts\PropertyDoesNotExistException;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\L10N\IFactory;
use OCP\Profile\ILinkAction;
use function substr;

class FediverseAction implements ILinkAction {
	private string $value = '';

	public function __construct(
		private IAccountManager $accountManager,
		private IFactory $l10nFactory,
		private IURLGenerator $urlGenerator,
	) {
	}

	#[\Override]
	public function preload(IUser $targetUser): void {
		try {
			$account = $this->accountManager->getAccount($targetUser);
			$this->value = $account->getProperty(IAccountManager::PROPERTY_FEDIVERSE)->getValue();
		} catch (PropertyDoesNotExistException) {
			// `getTarget` will return null to skip this action
			$this->value = '';
		}
	}

	#[\Override]
	public function getAppId(): string {
		return 'core';
	}

	#[\Override]
	public function getId(): string {
		return IAccountManager::PROPERTY_FEDIVERSE;
	}

	#[\Override]
	public function getDisplayId(): string {
		return $this->l10nFactory->get('lib')->t('Fediverse');
	}

	#[\Override]
	public function getTitle(): string {
		$displayUsername = $this->value[0] === '@' ? $this->value : '@' . $this->value;
		return $this->l10nFactory->get('lib')->t('View %s on the fediverse', [$displayUsername]);
	}

	#[\Override]
	public function getPriority(): int {
		return 50;
	}

	#[\Override]
	public function getIcon(): string {
		return $this->urlGenerator->getAbsoluteURL($this->urlGenerator->imagePath('core', 'actions/mastodon.svg'));
	}

	#[\Override]
	public function getTarget(): ?string {
		if ($this->value === '') {
			return null;
		}

		$handle = $this->value[0] === '@' ? substr($this->value, 1) : $this->value;
		[$username, $instance] = [...explode('@', $handle, 2), ''];

		if (($username === '') || ($instance === '')) {
			return null;
		} elseif (str_contains($username, '/') || str_contains($instance, '/')) {
			return null;
		}
		return 'https://' . $instance . '/@' . $username;
	}
}
