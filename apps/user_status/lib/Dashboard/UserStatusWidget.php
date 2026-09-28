<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\UserStatus\Dashboard;

use OCA\UserStatus\AppInfo\Application;
use OCA\UserStatus\Db\UserStatus;
use OCA\UserStatus\Service\StatusService;
use OCP\AppFramework\Services\IInitialState;
use OCP\Dashboard\IAPIWidget;
use OCP\Dashboard\IAPIWidgetV2;
use OCP\Dashboard\IIconWidget;
use OCP\Dashboard\IOptionWidget;
use OCP\Dashboard\Model\WidgetItem;
use OCP\Dashboard\Model\WidgetItems;
use OCP\Dashboard\Model\WidgetOptions;
use OCP\IDateTimeFormatter;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\IUserSession;
use OCP\UserStatus\IUserStatus;

/**
 * Class UserStatusWidget
 *
 * @package OCA\UserStatus
 */
class UserStatusWidget implements IAPIWidget, IAPIWidgetV2, IIconWidget, IOptionWidget {
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
	public function __construct(
		private IL10N $l10n,
		private IDateTimeFormatter $dateTimeFormatter,
		private IURLGenerator $urlGenerator,
		private IInitialState $initialStateService,
		private IUserManager $userManager,
		private IUserSession $userSession,
		private StatusService $service,
	) {
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
	}

	private function getWidgetData(string $userId, ?string $since = null, int $limit = 7): array {
		// Fetch status updates and filter current user
		$recentStatusUpdates = array_slice(
			array_filter(
				$this->service->findAllRecentStatusChanges($limit + 1, 0),
				static function (UserStatus $status) use ($userId, $since): bool {
					return $status->getUserId() !== $userId
						&& ($since === null || $status->getStatusTimestamp() > (int)$since);
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
				'timestamp' => $status->getStatusMessageTimestamp(),
			];
		}, $recentStatusUpdates);
	}

	/**
	 * @inheritDoc
	 */
	public function getItems(string $userId, ?string $since = null, int $limit = 7): array {
		$widgetItemsData = $this->getWidgetData($userId, $since, $limit);

		return array_values(array_map(function (array $widgetData) {
			$formattedDate = $this->dateTimeFormatter->formatTimeSpan($widgetData['timestamp']);
			return new WidgetItem(
				$widgetData['displayName'],
				$widgetData['icon'] . ($widgetData['icon'] ? ' ' : '') . $widgetData['message'] . ', ' . $formattedDate,
				// https://nextcloud.local/index.php/u/julien
				$this->urlGenerator->getAbsoluteURL(
					$this->urlGenerator->linkToRoute('profile.ProfilePage.index', ['targetUserId' => $widgetData['userId']])
				),
				$this->urlGenerator->getAbsoluteURL(
					$this->urlGenerator->linkToRoute('core.avatar.getAvatar', ['userId' => $widgetData['userId'], 'size' => 44])
				),
				(string)$widgetData['timestamp']
			);
		}, $widgetItemsData));
	}

	/**
	 * @inheritDoc
	 */
	public function getItemsV2(string $userId, ?string $since = null, int $limit = 7): WidgetItems {
		$items = $this->getItems($userId, $since, $limit);
		return new WidgetItems(
			$items,
			count($items) === 0 ? $this->l10n->t('No recent status changes') : '',
		);
	}

	public function getWidgetOptions(): WidgetOptions {
		return new WidgetOptions(true);
	}
}
