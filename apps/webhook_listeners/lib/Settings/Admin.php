<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\WebhookListeners\Settings;

use OCP\AppFramework\Http;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IL10N;
use OCP\Settings\IDelegatedSettings;

/**
 * Empty settings class, used only for admin delegation for now as there is no UI
 */
class Admin implements IDelegatedSettings {

	public function __construct(
		protected string $appName,
		private IL10N $l10n,
	) {
	}

	/**
	 * Empty template response
	 */
	public function getForm(): TemplateResponse {

		return new /** @template-extends TemplateResponse<Http::STATUS_OK, array{}> */ class($this->appName, '') extends TemplateResponse {
			public function render(): string {
				return '';
			}
		};
	}

	public function getSection(): ?string {
		return 'admindelegation';
	}

	/**
	 * @return int whether the form should be rather on the top or bottom of
	 *             the admin section. The forms are arranged in ascending order of the
	 *             priority values. It is required to return a value between 0 and 100.
	 *
	 * E.g.: 70
	 */
	public function getPriority(): int {
		return 0;
	}

	public function getName(): string {
		return $this->l10n->t('Webhooks');
	}

	public function getAuthorizedAppConfig(): array {
		return [];
	}
}
