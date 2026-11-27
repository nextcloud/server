<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Authentication\TwoFactorAuth;

use OCP\Authentication\TwoFactorAuth\IChallengeRequester;
use OCP\ISession;
use OCP\IURLGenerator;
use OCP\IUser;

final readonly class ChallengeRequester implements IChallengeRequester {
	public function __construct(
		private Manager $manager,
		private ISession $session,
		private IURLGenerator $urlGenerator,
	) {
	}

	#[\Override]
	public function isAvailable(IUser $user): bool {
		return !$this->session->exists('app_password')
			&& $this->session->get('app_api') !== true
			&& $this->manager->isTwoFactorAuthenticated($user);
	}

	#[\Override]
	public function requestChallenge(IUser $user, string $redirectUrl): string {
		if (!$this->isAvailable($user)) {
			throw new \LogicException('The second factor cannot be requested for this session');
		}

		$this->session->remove(Manager::SESSION_UID_DONE);
		$this->manager->prepareTwoFactorLogin($user, false);

		return $this->urlGenerator->linkToRoute('core.TwoFactorChallenge.selectChallenge', [
			'redirect_url' => $redirectUrl,
		]);
	}
}
