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

use function Safe\substr;
use OCP\Accounts\IAccountManager;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\L10N\IFactory;
use OCP\Profile\ILinkAction;

class TwitterAction implements ILinkAction {
	/** @var string */
	private $value;

	/** @var IAccountManager */
	private $accountManager;

	/** @var IFactory */
	private $l10nFactory;

	/** @var IUrlGenerator */
	private $urlGenerator;

	public function __construct(
		IAccountManager $accountManager,
		IFactory $l10nFactory,
		IURLGenerator $urlGenerator
	) {
		$this->accountManager = $accountManager;
		$this->l10nFactory = $l10nFactory;
		$this->urlGenerator = $urlGenerator;
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
