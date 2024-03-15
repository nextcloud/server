<?php

declare(strict_types=1);

/**
 * @copyright 2021 Christopher Ng <chrng8@gmail.com>
 *
 * @author Christopher Ng <chrng8@gmail.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OC\Profile\Actions;

use OCP\Accounts\IAccountManager;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\L10N\IFactory;
use OCP\Profile\ILinkAction;

class EmailAction implements ILinkAction {
	private string $value = '';

	public function __construct(
		private IAccountManager $accountManager,
		private IFactory $l10nFactory,
		private IURLGenerator $urlGenerator,
	) {
	}

	public function preload(IUser $targetUser): void {
		$account = $this->accountManager->getAccount($targetUser);
		$this->value = $account->getProperty(IAccountManager::PROPERTY_EMAIL)->getValue();
	}

	public function getAppId(): string {
		return 'core';
	}

	public function getId(): string {
		return IAccountManager::PROPERTY_EMAIL;
	}

	public function getDisplayId(): string {
		return $this->l10nFactory->get('lib')->t('Email');
	}

	public function getTitle(): string {
		return $this->l10nFactory->get('lib')->t('Mail %s', [$this->value]);
	}

	public function getPriority(): int {
		return 20;
	}

	public function getIcon(): string {
		return $this->urlGenerator->getAbsoluteURL($this->urlGenerator->imagePath('core', 'actions/mail.svg'));
	}

	public function getTarget(): ?string {
		if (empty($this->value)) {
			return null;
		}
		return 'mailto:' . $this->value;
	}
}
