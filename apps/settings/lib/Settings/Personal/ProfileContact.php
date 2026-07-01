<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Settings\Settings\Personal;

class ProfileContact extends APersonalInfoSettings {

	#[\Override]
	protected function getTemplate(): string {
		return 'settings/personal/profile.contact';
	}

	#[\Override]
	public function getSection(): string {
		return 'profile-contact';
	}
}
