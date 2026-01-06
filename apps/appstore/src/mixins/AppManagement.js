/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { showError } from '@nextcloud/dialogs'
import { rebuildNavigation } from '../service/rebuild-navigation.ts'

const productName = window.OC.theme.productName

export default {
	computed: {
		appGroups() {
			return this.app.groups.map((group) => {
				return { id: group, name: group }
			})
		},
		installing() {
			if (this.app?.app_api) {
				return this.app && this?.appApiStore.getLoading('install') === true
			}
			return this.$store.getters.loading('install')
		},
		isLoading() {
			if (this.app?.app_api) {
				return this.app && this?.appApiStore.getLoading(this.app.id) === true
			}
			return this.app && this.$store.getters.loading(this.app.id)
		},
		isInitializing() {
			if (this.app?.app_api) {
				return this.app && (this.app?.status?.action === 'init' || this.app?.status?.action === 'healthcheck')
			}
			return false
		},
		isDeploying() {
			if (this.app?.app_api) {
				return this.app && this.app?.status?.action === 'deploy'
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
			if (this.app?.app_api && this.app?.daemon?.accepts_deploy_id === 'manual-install') {
				return t('settings', 'Manually installed apps cannot be updated')
			}
			return t('settings', 'Update to {version}', { version: this.app?.update })
		},
		enableButtonText() {
			if (this.app?.app_api) {
				if (this.app && this.app?.status?.action && this.app?.status?.action === 'deploy') {
					return t('settings', '{progress}% Deploying …', { progress: this.app?.status?.deploy ?? 0 })
				}
				if (this.app && this.app?.status?.action && this.app?.status?.action === 'init') {
					return t('settings', '{progress}% Initializing …', { progress: this.app?.status?.init ?? 0 })
				}
				if (this.app && this.app?.status?.action && this.app?.status?.action === 'healthcheck') {
					return t('settings', 'Health checking')
				}
				if (this.app.needsDownload) {
					return t('settings', 'Deploy and Enable')
				}
				return t('settings', 'Enable')
			} else {
				if (this.app.needsDownload) {
					return t('settings', 'Download and enable')
				}
				return t('settings', 'Enable')
			}
		},
		disableButtonText() {
			if (this.app?.app_api) {
				if (this.app && this.app?.status?.action && this.app?.status?.action === 'deploy') {
					return t('settings', '{progress}% Deploying …', { progress: this.app?.status?.deploy })
				}
				if (this.app && this.app?.status?.action && this.app?.status?.action === 'init') {
					return t('settings', '{progress}% Initializing …', { progress: this.app?.status?.init })
				}
				if (this.app && this.app?.status?.action && this.app?.status?.action === 'healthcheck') {
					return t('settings', 'Health checking')
				}
			}
			return t('settings', 'Disable')
		},
		forceEnableButtonText() {
			if (this.app.needsDownload) {
				return t('settings', 'Allow untested app')
			}
			return t('settings', 'Allow untested app')
		},
		enableButtonTooltip() {
			if (!this.app?.app_api && this.app.needsDownload) {
				return t('settings', 'The app will be downloaded from the App Store')
			}
			return null
		},
		forceEnableButtonTooltip() {
			const base = t('settings', 'This app is not marked as compatible with your {productName} version.', { productName })
				+ ' '
				+ t('settings', 'If you continue you will still be able to install the app. Note that the app might not work as expected.')
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
				if (this.app?.daemon?.accepts_deploy_id === 'docker-install'
					&& this.appApiStore.getDefaultDaemon?.name === this.app?.daemon?.name) {
					return this?.appApiStore.getDaemonAccessible === true
				}
				return this?.appApiStore.getDaemonAccessible
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
		isLimitedToGroups() {
			if (this.app?.app_api) {
				return false
			}
			return this.app.groups.length || this.groupCheckedAppsData
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
				return
			}
			const group = groupArray.pop()
			const groups = this.app.groups.concat([]).concat([group.id])

			if (this.store && this.store.updateAppGroups) {
				this.store.updateAppGroups(this.app.id, groups)
			}

			this.$store.dispatch('enableApp', { appId: this.app.id, groups })
		},
		removeGroupLimitation(group) {
			if (this.app?.app_api) {
				return
			}
			const currentGroups = this.app.groups.concat([])
			const index = currentGroups.indexOf(group.id)
			if (index > -1) {
				currentGroups.splice(index, 1)
			}

			if (this.store && this.store.updateAppGroups) {
				this.store.updateAppGroups(this.app.id, currentGroups)
			}

			if (currentGroups.length === 0) {
				this.groupCheckedAppsData = false
			}

			this.$store.dispatch('enableApp', { appId: this.app.id, groups: currentGroups })
		},
		forceEnable(appId) {
			if (this.app?.app_api) {
				this.appApiStore.forceEnableApp(appId)
					.then(() => { rebuildNavigation() })
					.catch((error) => { showError(error) })
			} else {
				this.$store.dispatch('forceEnableApp', { appId, groups: [] })
					.then(() => { rebuildNavigation() })
					.catch((error) => { showError(error) })
			}
		},
		enable(appId, daemon = null, deployOptions = {}) {
			if (this.app?.app_api) {
				this.appApiStore.enableApp(appId, daemon, deployOptions)
					.then(() => { rebuildNavigation() })
					.catch((error) => { showError(error) })
			} else {
				this.$store.dispatch('enableApp', { appId, groups: [] })
					.then(() => { rebuildNavigation() })
					.catch((error) => { showError(error) })
			}
		},
		disable(appId) {
			if (this.app?.app_api) {
				this.appApiStore.disableApp(appId)
					.then(() => { rebuildNavigation() })
					.catch((error) => { showError(error) })
			} else {
				this.$store.dispatch('disableApp', { appId })
					.then(() => { rebuildNavigation() })
					.catch((error) => { showError(error) })
			}
		},
		async remove(appId, removeData = false) {
			try {
				if (this.app?.app_api) {
					await this.appApiStore.uninstallApp(appId, removeData)
				} else {
					await this.$store.dispatch('uninstallApp', { appId, removeData })
				}
				await rebuildNavigation()
			} catch (error) {
				showError(error)
			}
		},
		install(appId) {
			if (this.app?.app_api) {
				this.appApiStore.enableApp(appId)
					.then(() => { rebuildNavigation() })
					.catch((error) => { showError(error) })
			} else {
				this.$store.dispatch('enableApp', { appId })
					.then(() => { rebuildNavigation() })
					.catch((error) => { showError(error) })
			}
		},
	},
}
