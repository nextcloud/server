/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
const path = require('path')

module.exports = {
	files_sharing: {
		additionalScripts: path.join(__dirname, 'apps/files_sharing/src', 'additionalScripts.js'),
		collaboration: path.join(__dirname, 'apps/files_sharing/src', 'collaborationresourceshandler.js'),
		files_sharing_tab: path.join(__dirname, 'apps/files_sharing/src', 'files-sidebar.ts'),
		init: path.join(__dirname, 'apps/files_sharing/src', 'init.ts'),
		main: path.join(__dirname, 'apps/files_sharing/src', 'main.ts'),
		'personal-settings': path.join(__dirname, 'apps/files_sharing/src', 'personal-settings.js'),
		'public-nickname-handler': path.join(__dirname, 'apps/files_sharing/src', 'public-nickname-handler.ts'),
	},
	settings: {
		main: path.join(__dirname, 'apps/settings/src', 'main-settings.ts'),
		'vue-settings-admin-ai': path.join(__dirname, 'apps/settings/src/sections/ai', 'main.js'),
		'vue-settings-admin-security': path.join(__dirname, 'apps/settings/src/sections/security', 'main.js'),
		'vue-settings-profile-contact': path.join(__dirname, 'apps/settings/src', 'main-profile-contact.js'),
		'vue-settings-users-management': path.join(__dirname, 'apps/settings/src', 'main-users-management.ts'),
		'declarative-settings-forms': path.join(__dirname, 'apps/settings/src', 'main-declarative-settings-forms.ts'),
	},
}
