<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Settings\WellKnown;

use OCP\AppFramework\Http\RedirectResponse;
use OCP\Http\WellKnown\GenericResponse;
use OCP\Http\WellKnown\IHandler;
use OCP\Http\WellKnown\IRequestContext;
use OCP\Http\WellKnown\IResponse;
use OCP\IURLGenerator;

class ChangePasswordHandler implements IHandler {

	public function __construct(
		private IURLGenerator $urlGenerator,
	) {
	}

	public function handle(string $service, IRequestContext $context, ?IResponse $previousResponse): ?IResponse {
		if ($service !== 'change-password') {
			return $previousResponse;
		}

		$response = new RedirectResponse($this->urlGenerator->linkToRouteAbsolute('settings.PersonalSettings.index', ['section' => 'security']));
		return new GenericResponse($response);
	}
}
