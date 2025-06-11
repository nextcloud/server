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
		systemtags: path.resolve(__dirname, 'core/src', 'systemtags/merged-systemtags.js'),
		'unified-search': path.join(__dirname, 'core/src', 'unified-search.ts'),
		'legacy-unified-search': path.join(__dirname, 'core/src', 'legacy-unified-search.js'),
		'unsupported-browser': path.join(__dirname, 'core/src', 'unsupported-browser.js'),
		'unsupported-browser-redirect': path.join(__dirname, 'core/src', 'unsupported-browser-redirect.js'),
		public: path.join(__dirname, 'core/src', 'public.ts'),
	},
	dashboard: {
		main: path.join(__dirname, 'apps/dashboard/src', 'main.js'),
	},
	dav: {
		'settings-admin-caldav': path.join(__dirname, 'apps/dav/src', 'settings.js'),
		'settings-personal-availability': path.join(__dirname, 'apps/dav/src', 'settings-personal-availability.js'),
		'settings-example-content': path.join(__dirname, 'apps/dav/src', 'settings-example-content.js'),
	},
	files: {
		sidebar: path.join(__dirname, 'apps/files/src', 'sidebar.ts'),
		main: path.join(__dirname, 'apps/files/src', 'main.ts'),
		init: path.join(__dirname, 'apps/files/src', 'init.ts'),
		search: path.join(__dirname, 'apps/files/src/plugins/search', 'folderSearch.ts'),
		'settings-personal': path.join(__dirname, 'apps/files/src', 'main-personal-settings.js'),
		'reference-files': path.join(__dirname, 'apps/files/src', 'reference-files.ts'),
	},
	files_external: {
		init: path.join(__dirname, 'apps/files_external/src', 'init.ts'),
		settings: path.join(__dirname, 'apps/files_external/src', 'settings.js'),
	},
	files_reminders: {
		init: path.join(__dirname, 'apps/files_reminders/src', 'init.ts'),
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
	files_trashbin: {
		init: path.join(__dirname, 'apps/files_trashbin/src', 'files-init.ts'),
	},
	files_versions: {
		files_versions: path.join(__dirname, 'apps/files_versions/src', 'files_versions_tab.js'),
	},
	oauth2: {
		oauth2: path.join(__dirname, 'apps/oauth2/src', 'main.js'),
	},
	federatedfilesharing: {
		external: path.join(__dirname, 'apps/federatedfilesharing/src', 'external.js'),
		'vue-settings-admin': path.join(__dirname, 'apps/federatedfilesharing/src', 'main-admin.js'),
		'vue-settings-personal': path.join(__dirname, 'apps/federatedfilesharing/src', 'main-personal.js'),
	},
	profile: {
		main: path.join(__dirname, 'apps/profile/src', 'main.ts'),
	},
	settings: {
		apps: path.join(__dirname, 'apps/settings/src', 'apps.js'),
		'legacy-admin': path.join(__dirname, 'apps/settings/src', 'admin.js'),
		'vue-settings-admin-basic-settings': path.join(__dirname, 'apps/settings/src', 'main-admin-basic-settings.js'),
		'vue-settings-admin-ai': path.join(__dirname, 'apps/settings/src', 'main-admin-ai.js'),
		'vue-settings-admin-delegation': path.join(__dirname, 'apps/settings/src', 'main-admin-delegation.js'),
		'vue-settings-admin-security': path.join(__dirname, 'apps/settings/src', 'main-admin-security.js'),
		'vue-settings-admin-sharing': path.join(__dirname, 'apps/settings/src', 'admin-settings-sharing.ts'),
		'vue-settings-apps-users-management': path.join(__dirname, 'apps/settings/src', 'main-apps-users-management.ts'),
		'vue-settings-nextcloud-pdf': path.join(__dirname, 'apps/settings/src', 'main-nextcloud-pdf.js'),
		'vue-settings-personal-info': path.join(__dirname, 'apps/settings/src', 'main-personal-info.js'),
		'vue-settings-personal-password': path.join(__dirname, 'apps/settings/src', 'main-personal-password.js'),
		'vue-settings-personal-security': path.join(__dirname, 'apps/settings/src', 'main-personal-security.js'),
		'vue-settings-personal-webauthn': path.join(__dirname, 'apps/settings/src', 'main-personal-webauth.js'),
		'declarative-settings-forms': path.join(__dirname, 'apps/settings/src', 'main-declarative-settings-forms.ts'),
	},
	sharebymail: {
		'vue-settings-admin-sharebymail': path.join(__dirname, 'apps/sharebymail/src', 'main-admin.js'),
	},
	systemtags: {
		init: path.join(__dirname, 'apps/systemtags/src', 'init.ts'),
		admin: path.join(__dirname, 'apps/systemtags/src', 'admin.ts'),
	},
	theming: {
		'personal-theming': path.join(__dirname, 'apps/theming/src', 'personal-settings.js'),
		'admin-theming': path.join(__dirname, 'apps/theming/src', 'admin-settings.js'),
	},
	twofactor_backupcodes: {
		settings: path.join(__dirname, 'apps/twofactor_backupcodes/src', 'settings.js'),
	},
	updatenotification: {
		init: path.join(__dirname, 'apps/updatenotification/src', 'init.ts'),
		'view-changelog-page': path.join(__dirname, 'apps/updatenotification/src', 'view-changelog-page.ts'),
		updatenotification: path.join(__dirname, 'apps/updatenotification/src', 'updatenotification.js'),
		'update-notification-legacy': path.join(__dirname, 'apps/updatenotification/src', 'update-notification-legacy.ts'),
	},
	user_status: {
		menu: path.join(__dirname, 'apps/user_status/src', 'menu.js'),
	},
	weather_status: {
		'weather-status': path.join(__dirname, 'apps/weather_status/src', 'weather-status.js'),
	},
	workflowengine: {
		workflowengine: path.join(__dirname, 'apps/workflowengine/src', 'workflowengine.js'),
	},
}
