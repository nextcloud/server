<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Settings\Controller;

use OC\Config\PresetManager;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;

#[OpenAPI(scope: OpenAPI::SCOPE_IGNORE)]
class PresetController extends Controller {

	public function __construct(
		string $appName,
		IRequest $request,
		private readonly PresetManager $presetManager,
	) {
		parent::__construct($appName, $request);
	}

	public function getPreset(): DataResponse {
		return new DataResponse($this->presetManager->retrieveLexiconPreset());
	}

}
