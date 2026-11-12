/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
const path = require('path')

module.exports = {
	settings: {
		main: path.join(__dirname, 'apps/settings/src', 'main-settings.ts'),
		'vue-settings-users-management': path.join(__dirname, 'apps/settings/src', 'main-users-management.ts'),
		'declarative-settings-forms': path.join(__dirname, 'apps/settings/src', 'main-declarative-settings-forms.ts'),
	},
}
