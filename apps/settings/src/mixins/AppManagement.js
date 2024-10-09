/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { showError } from '@nextcloud/dialogs'
import rebuildNavigation from '../service/rebuild-navigation.js'

export default {
	computed: {
		appGroups() {
			return this.app.groups.map(group => { return { id: group, name: group } })
		},
		installing() {
			if (this.app?.app_api) {
				return this.app && this.$store.getters['app_api_apps/loading']('install')
			}
			return this.$store.getters.loading('install')
		},
		isLoading() {
			if (this.app?.app_api) {
				return this.app && this.$store.getters['app_api_apps/loading'](this.app.id)
			}
			return this.app && this.$store.getters.loading(this.app.id)
		},
		isInitializing() {
			if (this.app?.app_api) {
				return this.app && Object.hasOwn(this.app?.status, 'action') && (this.app.status.action === 'init' || this.app.status.action === 'healthcheck')
			}
			return false
		},
		isDeploying() {
			if (this.app?.app_api) {
				return this.app && Object.hasOwn(this.app?.status, 'action') && this.app.status.action === 'deploy'
			}
			return false
		},
		isManualInstall() {
			if (this.app?.app_api) {
				return this.app?.daemon?.accepts_deploy_id === 'manual-install'
			}
			return false
		},
		updateButtonText() {
			if (this.app?.daemon?.accepts_deploy_id === 'manual-install') {
				return t('app_api', 'manual-install apps cannot be updated')
			}
			return ''
		},
		enableButtonText() {
			if (this.app?.app_api) {
				if (this.app && Object.hasOwn(this.app?.status, 'action') && this.app.status.action === 'deploy') {
					return t('app_api', '{progress}% Deploying', { progress: this.app.status?.deploy })
				}
				if (this.app && Object.hasOwn(this.app?.status, 'action') && this.app.status.action === 'init') {
					return t('app_api', '{progress}% Initializing', { progress: this.app.status?.init })
				}
				if (this.app && Object.hasOwn(this.app?.status, 'action') && this.app.status.action === 'healthcheck') {
					return t('app_api', 'Healthchecking')
				}
				if (this.app.needsDownload) {
					return t('app_api', 'Deploy and Enable')
				}
				return t('app_api', 'Enable')
			} else {
				if (this.app.needsDownload) {
					return t('settings', 'Download and enable')
				}
				return t('settings', 'Enable')
			}
		},
		disableButtonText() {
			if (this.app?.app_api) {
				if (this.app && Object.hasOwn(this.app?.status, 'action') && this.app.status.action === 'deploy') {
					return t('app_api', '{progress}% Deploying', { progress: this.app.status?.deploy })
				}
				if (this.app && Object.hasOwn(this.app?.status, 'action') && this.app.status.action === 'init') {
					return t('app_api', '{progress}% Initializing', { progress: this.app.status?.init })
				}
				if (this.app && Object.hasOwn(this.app?.status, 'action') && this.app.status.action === 'healthcheck') {
					return t('app_api', 'Healthchecking')
				}
			}
			return t('app_api', 'Disable')
		},
		forceEnableButtonText() {
			if (this.app.needsDownload) {
				return t('settings', 'Allow untested app')
			}
			return t('settings', 'Allow untested app')
		},
		enableButtonTooltip() {
			if (this.app.needsDownload) {
				return t('settings', 'The app will be downloaded from the App Store')
			}
			return null
		},
		forceEnableButtonTooltip() {
			const base = t('settings', 'This app is not marked as compatible with your Nextcloud version. If you continue you will still be able to install the app. Note that the app might not work as expected.')
			if (this.app.needsDownload) {
				return base + ' ' + t('settings', 'The app will be downloaded from the App Store')
			}
			return base
		},
		defaultDeployDaemonAccessible() {
			if (this.app?.app_api) {
				if (this.app?.daemon && this.app?.daemon?.accepts_deploy_id === 'manual-install') {
					return true
				}
				if (this.app?.daemon?.accepts_deploy_id === 'docker-install') {
					return this.$store.getters['app_api_apps/getDaemonAccessible'] === true
				}
				return this.$store.getters['app_api_apps/getDaemonAccessible']
			}
			return true
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
			if (this.app?.app_api) {
				return false
			}
			return this.app.groups.length || this.groupCheckedAppsData;
		},
		setGroupLimit() {
			if (this.app?.app_api) {
				return // not supported for app_api apps
			}
			if (!this.groupCheckedAppsData) {
				this.$store.dispatch('enableApp', { appId: this.app.id, groups: [] })
			}
		},
		canLimitToGroups(app) {
			if ((app.types && app.types.includes('filesystem'))
					|| app.types.includes('prelogin')
					|| app.types.includes('authentication')
					|| app.types.includes('logging')
					|| app.types.includes('prevent_group_restriction')
					|| app?.app_api) {
				return false
			}
			return true
		},
		addGroupLimitation(groupArray) {
			if (this.app?.app_api) {
				return // not supported for app_api apps
			}
			const group = groupArray.pop()
			const groups = this.app.groups.concat([]).concat([group.id])
			this.$store.dispatch('enableApp', { appId: this.app.id, groups })
		},
		removeGroupLimitation(group) {
			if (this.app?.app_api) {
				return // not supported for app_api apps
			}
			const currentGroups = this.app.groups.concat([])
			const index = currentGroups.indexOf(group.id)
			if (index > -1) {
				currentGroups.splice(index, 1)
			}
			this.$store.dispatch('enableApp', { appId: this.app.id, groups: currentGroups })
		},
		forceEnable(appId) {
			let type = 'forceEnableApp'
			if (this.app?.app_api) {
				type = 'app_api_apps/forceEnableApp'
			}
			this.$store.dispatch(type, { appId, groups: [] })
				.then((response) => { rebuildNavigation() })
				.catch((error) => { showError(error) })
		},
		enable(appId) {
			let type = 'enableApp'
			if (this.app?.app_api) {
				type = 'app_api_apps/enableApp'
			}
			this.$store.dispatch(type, { appId, groups: [] })
				.then((response) => { rebuildNavigation() })
				.catch((error) => { showError(error) })
		},
		disable(appId) {
			let type = 'disableApp'
			if (this.app?.app_api) {
				type = 'app_api_apps/disableApp'
			}
			this.$store.dispatch(type, { appId })
				.then((response) => { rebuildNavigation() })
				.catch((error) => { showError(error) })
		},
		remove(appId, removeData = false) {
			let type = 'uninstallApp'
			let payload = { appId }
			if (this.app?.app_api) {
				type = 'app_api_apps/uninstallApp'
				payload = { appId, removeData }
			}
			this.$store.dispatch(type, payload)
				.then((response) => { rebuildNavigation() })
				.catch((error) => { showError(error) })
		},
		install(appId) {
			let type = 'enableApp'
			if (this.app?.app_api) {
				type = 'app_api_apps/enableApp'
			}
			this.$store.dispatch(type, { appId })
				.then((response) => { rebuildNavigation() })
				.catch((error) => { showError(error) })
		},
		update(appId) {
			let type = 'updateApp'
			if (this.app?.app_api) {
				type = 'app_api_apps/updateApp'
			}
			this.$store.dispatch(type, { appId })
				.catch((error) => { showError(error) })
				.then(() => {
					rebuildNavigation()
					this.store.updateCount = Math.max(this.store.updateCount - 1, 0)
				})
		},
	},
}
