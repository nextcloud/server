/**
 * @copyright Copyright (c) 2021 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
const path = require('path')

module.exports = {
	comments: {
		comments: path.join(__dirname, 'apps/comments/src', 'comments.js'),
		'comments-app': path.join(__dirname, 'apps/comments/src', 'comments-app.js'),
		'comments-tab': path.join(__dirname, 'apps/comments/src', 'comments-tab.js'),
	},
	core: {
		files_client: path.join(__dirname, 'core/src', 'files/client.js'),
		files_fileinfo: path.join(__dirname, 'core/src', 'files/fileinfo.js'),
		install: path.join(__dirname, 'core/src', 'install.js'),
		login: path.join(__dirname, 'core/src', 'login.js'),
		main: path.join(__dirname, 'core/src', 'main.js'),
		maintenance: path.join(__dirname, 'core/src', 'maintenance.js'),
		profile: path.join(__dirname, 'core/src', 'profile.js'),
		recommendedapps: path.join(__dirname, 'core/src', 'recommendedapps.js'),
		systemtags: path.resolve(__dirname, 'core/src', 'systemtags/merged-systemtags.js'),
		'unified-search': path.join(__dirname, 'core/src', 'unified-search.js'),
		'unsupported-browser': path.join(__dirname, 'core/src', 'unsupported-browser.js'),
		'unsupported-browser-redirect': path.join(__dirname, 'core/src', 'unsupported-browser-redirect.js'),
	},
	dashboard: {
		main: path.join(__dirname, 'apps/dashboard/src', 'main.js'),
	},
	dav: {
		'settings-admin-caldav': path.join(__dirname, 'apps/dav/src', 'settings.js'),
		'settings-personal-availability': path.join(__dirname, 'apps/dav/src', 'settings-personal-availability.js'),
	},
	files: {
		sidebar: path.join(__dirname, 'apps/files/src', 'sidebar.js'),
		main: path.join(__dirname, 'apps/files/src', 'main.ts'),
		'personal-settings': path.join(__dirname, 'apps/files/src', 'main-personal-settings.js'),
		'reference-files': path.join(__dirname, 'apps/files/src', 'reference-files.js'),
	},
	files_sharing: {
		additionalScripts: path.join(__dirname, 'apps/files_sharing/src', 'additionalScripts.js'),
		collaboration: path.join(__dirname, 'apps/files_sharing/src', 'collaborationresourceshandler.js'),
		files_sharing_tab: path.join(__dirname, 'apps/files_sharing/src', 'files_sharing_tab.js'),
		files_sharing: path.join(__dirname, 'apps/files_sharing/src', 'files_sharing.ts'),
		main: path.join(__dirname, 'apps/files_sharing/src', 'main.ts'),
		'personal-settings': path.join(__dirname, 'apps/files_sharing/src', 'personal-settings.js'),
	},
	files_trashbin: {
		main: path.join(__dirname, 'apps/files_trashbin/src', 'main.ts'),
	},
	files_versions: {
		files_versions: path.join(__dirname, 'apps/files_versions/src', 'files_versions_tab.js'),
	},
	oauth2: {
		oauth2: path.join(__dirname, 'apps/oauth2/src', 'main.js'),
	},
	federatedfilesharing: {
		'vue-settings-admin': path.join(__dirname, 'apps/federatedfilesharing/src', 'main-admin.js'),
		'vue-settings-personal': path.join(__dirname, 'apps/federatedfilesharing/src', 'main-personal.js'),
	},
	settings: {
		apps: path.join(__dirname, 'apps/settings/src', 'apps.js'),
		'legacy-admin': path.join(__dirname, 'apps/settings/src', 'admin.js'),
		'vue-settings-admin-basic-settings': path.join(__dirname, 'apps/settings/src', 'main-admin-basic-settings.js'),
		'vue-settings-admin-delegation': path.join(__dirname, 'apps/settings/src', 'main-admin-delegation.js'),
		'vue-settings-admin-security': path.join(__dirname, 'apps/settings/src', 'main-admin-security.js'),
		'vue-settings-apps-users-management': path.join(__dirname, 'apps/settings/src', 'main-apps-users-management.js'),
		'vue-settings-nextcloud-pdf': path.join(__dirname, 'apps/settings/src', 'main-nextcloud-pdf.js'),
		'vue-settings-personal-info': path.join(__dirname, 'apps/settings/src', 'main-personal-info.js'),
		'vue-settings-personal-password': path.join(__dirname, 'apps/settings/src', 'main-personal-password.js'),
		'vue-settings-personal-security': path.join(__dirname, 'apps/settings/src', 'main-personal-security.js'),
		'vue-settings-personal-webauthn': path.join(__dirname, 'apps/settings/src', 'main-personal-webauth.js'),
	},
	sharebymail: {
		'vue-settings-admin-sharebymail': path.join(__dirname, 'apps/sharebymail/src', 'main-admin.js'),
	},
	systemtags: {
		systemtags: path.join(__dirname, 'apps/systemtags/src', 'systemtags.js'),
	},
	theming: {
		'personal-theming': path.join(__dirname, 'apps/theming/src', 'personal-settings.js'),
		'admin-theming': path.join(__dirname, 'apps/theming/src', 'admin-settings.js'),
	},
	twofactor_backupcodes: {
		settings: path.join(__dirname, 'apps/twofactor_backupcodes/src', 'settings.js'),
	},
	updatenotification: {
		updatenotification: path.join(__dirname, 'apps/updatenotification/src', 'init.js'),
	},
	user_status: {
		dashboard: path.join(__dirname, 'apps/user_status/src', 'dashboard.js'),
		menu: path.join(__dirname, 'apps/user_status/src', 'menu.js'),
	},
	weather_status: {
		'weather-status': path.join(__dirname, 'apps/weather_status/src', 'weather-status.js'),
	},
	workflowengine: {
		workflowengine: path.join(__dirname, 'apps/workflowengine/src', 'workflowengine.js'),
	},
}
