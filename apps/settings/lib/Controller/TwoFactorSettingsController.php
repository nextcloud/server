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
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

class TwoFactorSettingsController extends Controller {

	/** @var MandatoryTwoFactor */
	private $mandatoryTwoFactor;

	public function __construct(string $appName,
		IRequest $request,
		MandatoryTwoFactor $mandatoryTwoFactor) {
		parent::__construct($appName, $request);

		$this->mandatoryTwoFactor = $mandatoryTwoFactor;
	}

	public function index(): JSONResponse {
		return new JSONResponse($this->mandatoryTwoFactor->getState());
	}

	public function update(bool $enforced, array $enforcedGroups = [], array $excludedGroups = []): JSONResponse {
		$this->mandatoryTwoFactor->setState(
			new EnforcementState($enforced, $enforcedGroups, $excludedGroups)
		);

		return new JSONResponse($this->mandatoryTwoFactor->getState());
	}
}
