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
use function substr;

class FediverseAction implements ILinkAction {
	private string $value = '';

	public function __construct(
		private IAccountManager $accountManager,
		private IFactory $l10nFactory,
		private IURLGenerator $urlGenerator,
	) {
	}

	public function preload(IUser $targetUser): void {
		$account = $this->accountManager->getAccount($targetUser);
		$this->value = $account->getProperty(IAccountManager::PROPERTY_FEDIVERSE)->getValue();
	}

	public function getAppId(): string {
		return 'core';
	}

	public function getId(): string {
		return IAccountManager::PROPERTY_FEDIVERSE;
	}

	public function getDisplayId(): string {
		return $this->l10nFactory->get('lib')->t('Fediverse');
	}

	public function getTitle(): string {
		$displayUsername = $this->value[0] === '@' ? $this->value : '@' . $this->value;
		return $this->l10nFactory->get('lib')->t('View %s on the fediverse', [$displayUsername]);
	}

	public function getPriority(): int {
		return 50;
	}

	public function getIcon(): string {
		return $this->urlGenerator->getAbsoluteURL($this->urlGenerator->imagePath('core', 'actions/mastodon.svg'));
	}

	public function getTarget(): ?string {
		if (empty($this->value)) {
			return null;
		}
		$username = $this->value[0] === '@' ? substr($this->value, 1) : $this->value;
		[$username, $instance] = explode('@', $username);
		return 'https://' . $instance . '/@' . $username;
	}
}
