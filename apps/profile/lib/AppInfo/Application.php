<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Profile\AppInfo;

use OCA\Profile\Listener\ProfilePickerReferenceListener;
use OCA\Profile\Reference\ProfilePickerReferenceProvider;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;

use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\Collaboration\Reference\RenderReferenceEvent;
use OCP\INavigationManager;
use OCP\IURLGenerator;
use OCP\IUserSession;
use OCP\L10N\IFactory;
use OCP\Server;

class Application extends App implements IBootstrap {
	public const APP_ID = 'profile';

	public function __construct(array $urlParams = []) {
		parent::__construct(self::APP_ID, $urlParams);
	}

	#[\Override]
	public function register(IRegistrationContext $context): void {
		$context->registerReferenceProvider(ProfilePickerReferenceProvider::class);
		$context->registerEventListener(RenderReferenceEvent::class, ProfilePickerReferenceListener::class);
	}

	#[\Override]
	public function boot(IBootContext $context): void {
		$context->injectFn($this->registerNavigationEntry(...));
	}

	/**
	 * Registers the navigation entry for the profile app in the user settings.
	 * Needed as the href is dynamic and thus we cannot use the appinfo/info.xml
	 */
	public function registerNavigationEntry(
		INavigationManager $navigationManager,
		IUserSession $userSession,
		IURLGenerator $urlGenerator,
	): void {
		if (!$userSession->isLoggedIn()) {
			return;
		}

		$l = Server::get(IFactory::class)->get('profile');
		// Profile
		$navigationManager->add([
			'type' => 'settings',
			'id' => 'profile',
			'order' => 1,
			'href' => $urlGenerator->linkToRoute(
				'profile.ProfilePage.index',
				['targetUserId' => $userSession->getUser()->getUID()],
			),
			'name' => $l->t('View profile'),
		]);
	}
}
