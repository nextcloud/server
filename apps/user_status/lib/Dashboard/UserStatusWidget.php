<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2020, Georg Ehrke
 *
 * @author Georg Ehrke <oc.list@georgehrke.com>
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
namespace OCA\UserStatus\Dashboard;

use OCA\UserStatus\AppInfo\Application;
use OCA\UserStatus\Db\UserStatus;
use OCA\UserStatus\Service\StatusService;
use OCP\AppFramework\Services\IInitialState;
use OCP\Dashboard\IAPIWidget;
use OCP\Dashboard\IButtonWidget;
use OCP\Dashboard\IIconWidget;
use OCP\Dashboard\IOptionWidget;
use OCP\Dashboard\Model\WidgetItem;
use OCP\Dashboard\Model\WidgetOptions;
use OCP\IDateTimeFormatter;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\UserStatus\IUserStatus;
use OCP\Util;

/**
 * Class UserStatusWidget
 *
 * @package OCA\UserStatus
 */
class UserStatusWidget implements IAPIWidget, IIconWidget, IOptionWidget {
	private IL10N $l10n;
	private IDateTimeFormatter $dateTimeFormatter;
	private IURLGenerator $urlGenerator;
	private IInitialState $initialStateService;
	private IUserManager $userManager;
	private IUserSession $userSession;
	private StatusService $service;

	/**
	 * UserStatusWidget constructor
	 *
	 * @param IL10N $l10n
	 * @param IDateTimeFormatter $dateTimeFormatter
	 * @param IURLGenerator $urlGenerator
	 * @param IInitialState $initialStateService
	 * @param IUserManager $userManager
	 * @param IUserSession $userSession
	 * @param StatusService $service
	 */
	public function __construct(IL10N $l10n,
								IDateTimeFormatter $dateTimeFormatter,
								IURLGenerator $urlGenerator,
								IInitialState $initialStateService,
								IUserManager $userManager,
								IUserSession $userSession,
								StatusService $service) {
		$this->l10n = $l10n;
		$this->dateTimeFormatter = $dateTimeFormatter;
		$this->urlGenerator = $urlGenerator;
		$this->initialStateService = $initialStateService;
		$this->userManager = $userManager;
		$this->userSession = $userSession;
		$this->service = $service;
	}

	/**
	 * @inheritDoc
	 */
	public function getId(): string {
		return Application::APP_ID;
	}

	/**
	 * @inheritDoc
	 */
	public function getTitle(): string {
		return $this->l10n->t('Recent statuses');
	}

	/**
	 * @inheritDoc
	 */
	public function getOrder(): int {
		return 5;
	}

	/**
	 * @inheritDoc
	 */
	public function getIconClass(): string {
		return 'icon-user-status-dark';
	}

	/**
	 * @inheritDoc
	 */
	public function getIconUrl(): string {
		return $this->urlGenerator->getAbsoluteURL(
			$this->urlGenerator->imagePath(Application::APP_ID, 'app-dark.svg')
		);
	}

	/**
	 * @inheritDoc
	 */
	public function getUrl(): ?string {
		return null;
	}

	/**
	 * @inheritDoc
	 */
	public function load(): void {
		Util::addScript(Application::APP_ID, 'dashboard');

		$currentUser = $this->userSession->getUser();
		if ($currentUser === null) {
			$this->initialStateService->provideInitialState('dashboard_data', []);
			return;
		}
		$currentUserId = $currentUser->getUID();

		$widgetItemsData = $this->getWidgetData($currentUserId);
		$this->initialStateService->provideInitialState('dashboard_data', $widgetItemsData);
	}

	private function getWidgetData(string $userId, ?string $since = null, int $limit = 7): array {
		// Fetch status updates and filter current user
		$recentStatusUpdates = array_slice(
			array_filter(
				$this->service->findAllRecentStatusChanges($limit + 1, 0),
				static function (UserStatus $status) use ($userId, $since): bool {
					return $status->getUserId() !== $userId
						&& ($since === null || $status->getStatusTimestamp() > (int) $since);
				}
			),
			0,
			$limit
		);
		return array_map(function (UserStatus $status): array {
			$user = $this->userManager->get($status->getUserId());
			$displayName = $status->getUserId();
			if ($user !== null) {
				$displayName = $user->getDisplayName();
			}

			return [
				'userId' => $status->getUserId(),
				'displayName' => $displayName,
				'status' => $status->getStatus() === IUserStatus::INVISIBLE
					? IUserStatus::OFFLINE
					: $status->getStatus(),
				'icon' => $status->getCustomIcon(),
				'message' => $status->getCustomMessage(),
				'timestamp' => $status->getStatusTimestamp(),
			];
		}, $recentStatusUpdates);
	}

	/**
	 * @inheritDoc
	 */
	public function getItems(string $userId, ?string $since = null, int $limit = 7): array {
		$widgetItemsData = $this->getWidgetData($userId, $since, $limit);

		return array_map(function(array $widgetData) {
			$formattedDate = $this->dateTimeFormatter->formatTimeSpan($widgetData['timestamp']);
			return new WidgetItem(
				$widgetData['displayName'],
				$widgetData['icon'] . ($widgetData['icon'] ? ' ' : '') . $widgetData['message'] . ', ' . $formattedDate,
				// https://nextcloud.local/index.php/u/julien
				$this->urlGenerator->getAbsoluteURL(
					$this->urlGenerator->linkToRoute('core.ProfilePage.index', ['targetUserId' => $widgetData['userId']])
				),
				$this->urlGenerator->getAbsoluteURL(
					$this->urlGenerator->linkToRoute('core.avatar.getAvatar', ['userId' => $widgetData['userId'], 'size' => 44])
				),
				(string) $widgetData['timestamp']
			);
		}, $widgetItemsData);
	}

	public function getWidgetOptions(): WidgetOptions {
		return new WidgetOptions(true);
	}
}
