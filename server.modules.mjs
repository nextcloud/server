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

import { dirname, join } from 'path'
import { fileURLToPath } from 'url'

const __dirname = globalThis?.__dirname ?? import.meta.dirname ?? dirname(fileURLToPath(import.meta.url))

export default {
	comments: {
		'comments-app': join(__dirname, 'apps/comments/src', 'comments-app.js'),
		'comments-tab': join(__dirname, 'apps/comments/src', 'comments-tab.js'),
		init: join(__dirname, 'apps/comments/src', 'init.ts'),
	},
	core: {
		'shared-vue': join(__dirname, 'core/src', 'modules/vue.ts'),
		'shared-nextcloud-vue': join(__dirname, 'core/src', 'modules/nextcloud-vue.ts'),
		backgroundjobs: join(__dirname, 'core/src', 'cron.ts'),
		files_client: join(__dirname, 'core/src', 'files/client.js'),
		files_fileinfo: join(__dirname, 'core/src', 'files/fileinfo.js'),
		install: join(__dirname, 'core/src', 'install.js'),
		login: join(__dirname, 'core/src', 'login.js'),
		main: join(__dirname, 'core/src', 'main.js'),
		maintenance: join(__dirname, 'core/src', 'maintenance.js'),
		profile: join(__dirname, 'core/src', 'profile.ts'),
		recommendedapps: join(__dirname, 'core/src', 'recommendedapps.js'),
		// systemtags: resolve(__dirname, 'core/src', 'systemtags/merged-systemtags.js'),
		'unified-search': join(__dirname, 'core/src', 'unified-search.ts'),
		'legacy-unified-search': join(__dirname, 'core/src', 'legacy-unified-search.js'),
		'unsupported-browser': join(__dirname, 'core/src', 'unsupported-browser.js'),
		'unsupported-browser-redirect': join(__dirname, 'core/src', 'unsupported-browser-redirect.js'),
	},
	dashboard: {
		main: join(__dirname, 'apps/dashboard/src', 'main.js'),
	},
	dav: {
		'settings-admin-caldav': join(__dirname, 'apps/dav/src', 'settings.js'),
		'settings-personal-availability': join(__dirname, 'apps/dav/src', 'settings-personal-availability.js'),
	},
	files: {
		sidebar: join(__dirname, 'apps/files/src', 'sidebar.js'),
		main: join(__dirname, 'apps/files/src', 'main.ts'),
		init: join(__dirname, 'apps/files/src', 'init.ts'),
		'personal-settings': join(__dirname, 'apps/files/src', 'main-personal-settings.js'),
		'reference-files': join(__dirname, 'apps/files/src', 'reference-files.ts'),
		search: join(__dirname, 'apps/files/src/plugins/search', 'folderSearch.ts'),
	},
	files_external: {
		init: join(__dirname, 'apps/files_external/src', 'init.ts'),
	},
	files_reminders: {
		init: join(__dirname, 'apps/files_reminders/src', 'init.ts'),
	},
	files_sharing: {
		additionalScripts: join(__dirname, 'apps/files_sharing/src', 'additionalScripts.js'),
		collaboration: join(__dirname, 'apps/files_sharing/src', 'collaborationresourceshandler.js'),
		files_sharing_tab: join(__dirname, 'apps/files_sharing/src', 'files_sharing_tab.js'),
		init: join(__dirname, 'apps/files_sharing/src', 'init.ts'),
		main: join(__dirname, 'apps/files_sharing/src', 'main.ts'),
		'personal-settings': join(__dirname, 'apps/files_sharing/src', 'personal-settings.js'),
	},
	files_trashbin: {
		main: join(__dirname, 'apps/files_trashbin/src', 'main.ts'),
	},
	files_versions: {
		files_versions: join(__dirname, 'apps/files_versions/src', 'files_versions_tab.js'),
	},
	oauth2: {
		oauth2: join(__dirname, 'apps/oauth2/src', 'main.js'),
	},
	federatedfilesharing: {
		external: join(__dirname, 'apps/federatedfilesharing/src', 'external.js'),
		'vue-settings-admin': join(__dirname, 'apps/federatedfilesharing/src', 'main-admin.js'),
		'vue-settings-personal': join(__dirname, 'apps/federatedfilesharing/src', 'main-personal.js'),
	},
	settings: {
		apps: join(__dirname, 'apps/settings/src', 'apps.js'),
		'legacy-admin': join(__dirname, 'apps/settings/src', 'admin.js'),
		'declarative-settings-forms': join(__dirname, 'apps/settings/src', 'main-declarative-settings-forms.ts'),
		'vue-settings-admin-basic-settings': join(__dirname, 'apps/settings/src', 'main-admin-basic-settings.js'),
		'vue-settings-admin-ai': join(__dirname, 'apps/settings/src', 'main-admin-ai.js'),
		'vue-settings-admin-delegation': join(__dirname, 'apps/settings/src', 'main-admin-delegation.js'),
		'vue-settings-admin-security': join(__dirname, 'apps/settings/src', 'main-admin-security.js'),
		'vue-settings-admin-sharing': join(__dirname, 'apps/settings/src', 'admin-settings-sharing.ts'),
		'vue-settings-apps-users-management': join(__dirname, 'apps/settings/src', 'main-apps-users-management.ts'),
		'vue-settings-nextcloud-pdf': join(__dirname, 'apps/settings/src', 'main-nextcloud-pdf.js'),
		'vue-settings-personal-info': join(__dirname, 'apps/settings/src', 'main-personal-info.js'),
		'vue-settings-personal-password': join(__dirname, 'apps/settings/src', 'main-personal-password.js'),
		'vue-settings-personal-security': join(__dirname, 'apps/settings/src', 'main-personal-security.js'),
		'vue-settings-personal-webauthn': join(__dirname, 'apps/settings/src', 'main-personal-webauth.js'),
	},
	sharebymail: {
		'vue-settings-admin-sharebymail': join(__dirname, 'apps/sharebymail/src', 'main-admin.js'),
	},
	systemtags: {
		init: join(__dirname, 'apps/systemtags/src', 'init.ts'),
		admin: join(__dirname, 'apps/systemtags/src', 'admin.ts'),
	},
	theming: {
		'personal-theming': join(__dirname, 'apps/theming/src', 'personal-settings.js'),
		'admin-theming': join(__dirname, 'apps/theming/src', 'admin-settings.js'),
	},
	twofactor_backupcodes: {
		settings: join(__dirname, 'apps/twofactor_backupcodes/src', 'settings.js'),
	},
	updatenotification: {
		init: join(__dirname, 'apps/updatenotification/src', 'init.ts'),
		updatenotification: join(__dirname, 'apps/updatenotification/src', 'updatenotification.js'),
		'view-changelog-page': join(__dirname, 'apps/updatenotification/src', 'view-changelog-page.ts'),
	},
	user_status: {
		menu: join(__dirname, 'apps/user_status/src', 'menu.js'),
	},
	weather_status: {
		'weather-status': join(__dirname, 'apps/weather_status/src', 'weather-status.js'),
	},
	workflowengine: {
		workflowengine: join(__dirname, 'apps/workflowengine/src', 'workflowengine.js'),
	},
}
