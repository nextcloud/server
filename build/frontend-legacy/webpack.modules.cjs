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
		'vue-settings-admin-overview': path.join(__dirname, 'apps/settings/src', 'main-admin-overview.ts'),
		'vue-settings-admin-basic-settings': path.join(__dirname, 'apps/settings/src', 'main-admin-basic-settings.js'),
		'vue-settings-admin-ai': path.join(__dirname, 'apps/settings/src', 'main-admin-ai.js'),
		'vue-settings-admin-delegation': path.join(__dirname, 'apps/settings/src', 'main-admin-delegation.js'),
		'vue-settings-admin-mail': path.join(__dirname, 'apps/settings/src', 'admin-settings-mail.ts'),
		'vue-settings-admin-security': path.join(__dirname, 'apps/settings/src', 'main-admin-security.js'),
		'vue-settings-admin-settings-presets': path.join(__dirname, 'apps/settings/src', 'main-admin-settings-presets.js'),
		'vue-settings-admin-sharing': path.join(__dirname, 'apps/settings/src', 'admin-settings-sharing.ts'),
		'vue-settings-nextcloud-pdf': path.join(__dirname, 'apps/settings/src', 'main-nextcloud-pdf.js'),
		'vue-settings-profile-contact': path.join(__dirname, 'apps/settings/src', 'main-profile-contact.js'),
		'vue-settings-language-locale': path.join(__dirname, 'apps/settings/src', 'main-language-locale.js'),
		'vue-settings-personal-password': path.join(__dirname, 'apps/settings/src', 'main-personal-password.js'),
		'vue-settings-personal-security': path.join(__dirname, 'apps/settings/src', 'main-personal-security.js'),
		'vue-settings-personal-webauthn': path.join(__dirname, 'apps/settings/src', 'main-personal-webauth.js'),
		'vue-settings-users-management': path.join(__dirname, 'apps/settings/src', 'main-users-management.ts'),
		'declarative-settings-forms': path.join(__dirname, 'apps/settings/src', 'main-declarative-settings-forms.ts'),
	},
}
