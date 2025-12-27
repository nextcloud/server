/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
const path = require('path')

module.exports = {
	comments: {
		'comments-app': path.join(__dirname, 'apps/comments/src', 'comments-app.js'),
		'comments-tab': path.join(__dirname, 'apps/comments/src', 'comments-tab.js'),
		init: path.join(__dirname, 'apps/comments/src', 'init.ts'),
	},
	core: {
		'ajax-cron': path.join(__dirname, 'core/src', 'ajax-cron.ts'),
		files_client: path.join(__dirname, 'core/src', 'files/client.js'),
		files_fileinfo: path.join(__dirname, 'core/src', 'files/fileinfo.js'),
		install: path.join(__dirname, 'core/src', 'install.ts'),
		login: path.join(__dirname, 'core/src', 'login.js'),
		main: path.join(__dirname, 'core/src', 'main.js'),
		maintenance: path.join(__dirname, 'core/src', 'maintenance.js'),
		'public-page-menu': path.resolve(__dirname, 'core/src', 'public-page-menu.ts'),
		'public-page-user-menu': path.resolve(__dirname, 'core/src', 'public-page-user-menu.ts'),
		recommendedapps: path.join(__dirname, 'core/src', 'recommendedapps.js'),
		'unified-search': path.join(__dirname, 'core/src', 'unified-search.ts'),
		'legacy-unified-search': path.join(__dirname, 'core/src', 'legacy-unified-search.js'),
		'unsupported-browser': path.join(__dirname, 'core/src', 'unsupported-browser.js'),
		'unsupported-browser-redirect': path.join(__dirname, 'core/src', 'unsupported-browser-redirect.js'),
		public: path.join(__dirname, 'core/src', 'public.ts'),
		'twofactor-request-token': path.join(__dirname, 'core/src', 'twofactor-request-token.ts'),
	},
	dashboard: {
		main: path.join(__dirname, 'apps/dashboard/src', 'main.js'),
	},
	files: {
		sidebar: path.join(__dirname, 'apps/files/src', 'sidebar.ts'),
		main: path.join(__dirname, 'apps/files/src', 'main.ts'),
		init: path.join(__dirname, 'apps/files/src', 'init.ts'),
		search: path.join(__dirname, 'apps/files/src/plugins/search', 'folderSearch.ts'),
		'settings-admin': path.join(__dirname, 'apps/files/src', 'main-settings-admin.ts'),
		'settings-personal': path.join(__dirname, 'apps/files/src', 'main-settings-personal.ts'),
		'reference-files': path.join(__dirname, 'apps/files/src', 'reference-files.ts'),
	},
	files_sharing: {
		additionalScripts: path.join(__dirname, 'apps/files_sharing/src', 'additionalScripts.js'),
		collaboration: path.join(__dirname, 'apps/files_sharing/src', 'collaborationresourceshandler.js'),
		files_sharing_tab: path.join(__dirname, 'apps/files_sharing/src', 'files_sharing_tab.js'),
		init: path.join(__dirname, 'apps/files_sharing/src', 'init.ts'),
		'init-public': path.join(__dirname, 'apps/files_sharing/src', 'init-public.ts'),
		main: path.join(__dirname, 'apps/files_sharing/src', 'main.ts'),
		'personal-settings': path.join(__dirname, 'apps/files_sharing/src', 'personal-settings.js'),
		'public-nickname-handler': path.join(__dirname, 'apps/files_sharing/src', 'public-nickname-handler.ts'),
	},
	profile: {
		main: path.join(__dirname, 'apps/profile/src', 'main.ts'),
	},
	settings: {
		apps: path.join(__dirname, 'apps/settings/src', 'apps.js'),
		'legacy-admin': path.join(__dirname, 'apps/settings/src', 'admin.js'),
		'vue-settings-admin-overview': path.join(__dirname, 'apps/settings/src', 'main-admin-overview.ts'),
		'vue-settings-admin-basic-settings': path.join(__dirname, 'apps/settings/src', 'main-admin-basic-settings.js'),
		'vue-settings-admin-ai': path.join(__dirname, 'apps/settings/src', 'main-admin-ai.js'),
		'vue-settings-admin-delegation': path.join(__dirname, 'apps/settings/src', 'main-admin-delegation.js'),
		'vue-settings-admin-security': path.join(__dirname, 'apps/settings/src', 'main-admin-security.js'),
		'vue-settings-admin-settings-presets': path.join(__dirname, 'apps/settings/src', 'main-admin-settings-presets.js'),
		'vue-settings-admin-sharing': path.join(__dirname, 'apps/settings/src', 'admin-settings-sharing.ts'),
		'vue-settings-apps-users-management': path.join(__dirname, 'apps/settings/src', 'main-apps-users-management.ts'),
		'vue-settings-nextcloud-pdf': path.join(__dirname, 'apps/settings/src', 'main-nextcloud-pdf.js'),
		'vue-settings-personal-info': path.join(__dirname, 'apps/settings/src', 'main-personal-info.js'),
		'vue-settings-personal-password': path.join(__dirname, 'apps/settings/src', 'main-personal-password.js'),
		'vue-settings-personal-security': path.join(__dirname, 'apps/settings/src', 'main-personal-security.js'),
		'vue-settings-personal-webauthn': path.join(__dirname, 'apps/settings/src', 'main-personal-webauth.js'),
		'declarative-settings-forms': path.join(__dirname, 'apps/settings/src', 'main-declarative-settings-forms.ts'),
	},
	systemtags: {
		init: path.join(__dirname, 'apps/systemtags/src', 'init.ts'),
		admin: path.join(__dirname, 'apps/systemtags/src', 'admin.ts'),
	},
	updatenotification: {
		init: path.join(__dirname, 'apps/updatenotification/src', 'init.ts'),
		'view-changelog-page': path.join(__dirname, 'apps/updatenotification/src', 'view-changelog-page.ts'),
		updatenotification: path.join(__dirname, 'apps/updatenotification/src', 'updatenotification.js'),
		'update-notification-legacy': path.join(__dirname, 'apps/updatenotification/src', 'update-notification-legacy.ts'),
	},
	weather_status: {
		'weather-status': path.join(__dirname, 'apps/weather_status/src', 'weather-status.js'),
	},
	workflowengine: {
		workflowengine: path.join(__dirname, 'apps/workflowengine/src', 'workflowengine.js'),
	},
}
