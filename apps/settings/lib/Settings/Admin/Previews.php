<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Settings\Settings\Admin;

use OC\Preview\PreviewAdminConfig;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\AppFramework\Services\IInitialState;
use OCP\Settings\IDelegatedSettings;
use OCP\Util;

class Previews implements IDelegatedSettings {
	public function __construct(
		private PreviewAdminConfig $previewAdminConfig,
		private IInitialState $initialState,
		private string $appName,
	) {
	}

	#[\Override]
	public function getForm(): TemplateResponse {
		$this->initialState->provideInitialState('previewsSettings', $this->previewAdminConfig->getSettings());
		$this->initialState->provideInitialState('previewsDocumentation', 'https://docs.nextcloud.com/server/latest/admin_manual/configuration_files/previews_configuration.html');

		Util::addScript($this->appName, 'vue-settings-admin-previews');
		return new TemplateResponse($this->appName, 'settings/admin/previews', [], '');
	}

	#[\Override]
	public function getSection(): string {
		return 'previews';
	}

	#[\Override]
	public function getPriority(): int {
		return 0;
	}

	#[\Override]
	public function getAuthorizedAppConfig(): array {
		return [
			'preview' => ['/jpeg_quality/', '/webp_quality/'],
		];
	}

	#[\Override]
	public function getName(): ?string {
		return null;
	}
}
