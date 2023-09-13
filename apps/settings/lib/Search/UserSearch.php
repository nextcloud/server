<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2020 Joas Schilling <coding@schilljs.com>
 *
 * @author Stephan Orbaugh <stephan.orbaugh@nextcloud.com>
 *
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
namespace OCA\Settings\Search;

use OCP\Accounts\IAccountManager;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCS\OCSNotFoundException;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Search\IProvider;
use OCP\Search\ISearchQuery;
use OCP\Search\SearchResult;
use OCP\Search\SearchResultEntry;
use OCP\Settings\IIconSection;
use OCP\Settings\IManager;

class UserSearch implements IProvider {

	/** @var IManager */
	protected $settingsManager;

	/** @var IGroupManager */
	protected $groupManager;

	/** @var IURLGenerator */
	protected $urlGenerator;

	/** @var IL10N */
	protected $l;

	/** @var IUserManager */
	protected $userManager;

	/** @var IAccountManager */
	protected $accountManager;

	public function __construct(IManager $settingsManager,
								IGroupManager $groupManager,
								IURLGenerator $urlGenerator,
								IUserManager $userManager,
								IAccountManager $accountManager,
								IL10N $l) {
		$this->settingsManager = $settingsManager;
		$this->groupManager = $groupManager;
		$this->urlGenerator = $urlGenerator;
		$this->userManager = $userManager;
		$this->accountManager = $accountManager;
		$this->l = $l;
	}

	/**
	 * @inheritDoc
	 */
	public function getId(): string {
		return 'users';
	}

	/**
	 * @inheritDoc
	 */
	public function getName(): string {
		return $this->l->t('Users');
	}

	/**
	 * @inheritDoc
	 */
	public function getOrder(string $route, array $routeParameters): int {
		return 300;
	}

	/**
	 * @inheritDoc
	 */
	public function search(IUser $user, ISearchQuery $query): SearchResult {

		$users = $this->userManager->search($query->getTerm(), $query->getLimit(), 0);

		if (!$this->groupManager->isAdmin($user->getUID())) {
			return SearchResult::complete(
				$this->l->t('Users'),
				[]
			);
		}

		foreach ($users as $user) {
			$targetUserObject = $this->userManager->get($user->getUid());

			if ($targetUserObject === null) {
				throw new OCSNotFoundException('User does not exist');
			}

			$userAccount = $this->accountManager->getAccount($targetUserObject);
			$avatar = $userAccount->getProperty(IAccountManager::PROPERTY_AVATAR)->getScope();

			$result[] = new SearchResultEntry(
				'',
				$targetUserObject->getDisplayName(),
				$user->getUid(),
				$this->urlGenerator->linkToRouteAbsolute('settings.Users.usersList'),
				'icon-user-dark'
			);
		}

		return SearchResult::complete(
			$this->l->t('Users'),
			$result
		);
	}
}
