<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Settings\Controller;

use OC\Authentication\TwoFactorAuth\EnforcementState;
use OC\Authentication\TwoFactorAuth\MandatoryTwoFactor;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\PasswordConfirmationRequired;
use OCP\AppFramework\Http\JSONResponse;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IRequest;
use OCP\Log\Audit\CriticalActionPerformedEvent;

class TwoFactorSettingsController extends Controller {

	public function __construct(
		string $appName,
		IRequest $request,
		private MandatoryTwoFactor $mandatoryTwoFactor,
		private IEventDispatcher $eventDispatcher,
	) {
		parent::__construct($appName, $request);
	}

	public function index(): JSONResponse {
		return new JSONResponse($this->mandatoryTwoFactor->getState());
	}

	#[PasswordConfirmationRequired(strict: true)]
	public function update(bool $enforced, array $enforcedGroups = [], array $excludedGroups = []): JSONResponse {
		$this->mandatoryTwoFactor->setState(
			new EnforcementState($enforced, $enforcedGroups, $excludedGroups)
		);
		$this->eventDispatcher->dispatchTyped(new CriticalActionPerformedEvent(
			'Two-factor authentication enforcement was set to "%s" with enforced groups "%s" and excluded groups "%s"',
			[
				'enforced' => $enforced ? 'enabled' : 'disabled',
				'enforcedGroups' => implode(', ', $enforcedGroups),
				'excludedGroups' => implode(', ', $excludedGroups),
			],
			false,
			'auth.twofactor_enforcement.updated',
		));

		return new JSONResponse($this->mandatoryTwoFactor->getState());
	}
}
