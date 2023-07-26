/**
 * @copyright Copyright (c) 2019 Julius Härtl <jus@bitgrid.net>
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

import { showError } from '@nextcloud/dialogs'
import rebuildNavigation from '../service/rebuild-navigation.js'

export default {
	computed: {
		appGroups() {
			return this.app.groups.map(group => { return { id: group, name: group } })
		},
		installing() {
			return this.$store.getters.loading('install')
		},
		isLoading() {
			return this.app && this.$store.getters.loading(this.app.id)
		},
		enableButtonText() {
			if (this.app.needsDownload) {
				return t('settings', 'Download and enable')
			}
			return t('settings', 'Enable')
		},
		forceEnableButtonText() {
			if (this.app.needsDownload) {
				return t('settings', 'Allow untested app')
			}
			return t('settings', 'Authorize this untested app')
		},
		enableButtonTooltip() {
			if (this.app.needsDownload) {
				return t('settings', 'The app will be downloaded from the App Store')
			}
			return false
		},
		forceEnableButtonTooltip() {
			const base = t('settings', 'This app is not marked as compatible with your Nextcloud version. If you continue you will still be able to install the app. Note that the app might not work as expected.')
			if (this.app.needsDownload) {
				return base + ' ' + t('settings', 'The app will be downloaded from the App Store')
			}
			return base
		},
	},

	data() {
		return {
			groupCheckedAppsData: false,
		}
	},

	mounted() {
		if (this.app && this.app.groups && this.app.groups.length > 0) {
			this.groupCheckedAppsData = true
		}
	},

	methods: {
		asyncFindGroup(query) {
			return this.$store.dispatch('getGroups', { search: query, limit: 5, offset: 0 })
		},
		isLimitedToGroups(app) {
			if (this.app.groups.length || this.groupCheckedAppsData) {
				return true
			}
			return false
		},
		setGroupLimit() {
			if (!this.groupCheckedAppsData) {
				this.$store.dispatch('enableApp', { appId: this.app.id, groups: [] })
			}
		},
		canLimitToGroups(app) {
			if ((app.types && app.types.includes('filesystem'))
					|| app.types.includes('prelogin')
					|| app.types.includes('authentication')
					|| app.types.includes('logging')
					|| app.types.includes('prevent_group_restriction')) {
				return false
			}
			return true
		},
		addGroupLimitation(groupArray) {
			const group = groupArray.pop()
			const groups = this.app.groups.concat([]).concat([group.id])
			this.$store.dispatch('enableApp', { appId: this.app.id, groups })
		},
		removeGroupLimitation(group) {
			const currentGroups = this.app.groups.concat([])
			const index = currentGroups.indexOf(group.id)
			if (index > -1) {
				currentGroups.splice(index, 1)
			}
			this.$store.dispatch('enableApp', { appId: this.app.id, groups: currentGroups })
		},
		forceEnable(appId) {
			this.$store.dispatch('forceEnableApp', { appId, groups: [] })
				.then((response) => { rebuildNavigation() })
				.catch((error) => { showError(error) })
		},
		enable(appId) {
			this.$store.dispatch('enableApp', { appId, groups: [] })
				.then((response) => { rebuildNavigation() })
				.catch((error) => { showError(error) })
		},
		disable(appId) {
			this.$store.dispatch('disableApp', { appId })
				.then((response) => { rebuildNavigation() })
				.catch((error) => { showError(error) })
		},
		remove(appId) {
			this.$store.dispatch('uninstallApp', { appId })
				.then((response) => { rebuildNavigation() })
				.catch((error) => { showError(error) })
		},
		install(appId) {
			this.$store.dispatch('enableApp', { appId })
				.then((response) => { rebuildNavigation() })
				.catch((error) => { showError(error) })
		},
		update(appId) {
			this.$store.dispatch('updateApp', { appId })
				.then((response) => { rebuildNavigation() })
				.catch((error) => { showError(error) })
		},
	},
}
