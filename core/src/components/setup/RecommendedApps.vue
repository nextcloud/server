<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="guest-box" data-cy-setup-recommended-apps>
		<h2>{{ t('core', 'Recommended apps') }}</h2>
		<p v-if="loadingApps" class="loading text-center">
			{{ t('core', 'Loading apps …') }}
		</p>
		<p v-else-if="loadingAppsError" class="loading-error text-center">
			{{ t('core', 'Could not fetch list of apps from the App Store.') }}
		</p>

		<div v-for="app in recommendedApps" :key="app.id" class="app">
			<template v-if="!isHidden(app.id)">
				<img :src="customIcon(app.id)" alt="">
				<div class="info">
					<h3>{{ customName(app) }}</h3>
					<p v-text="customDescription(app.id)" />
					<p v-if="app.installationError">
						<strong>{{ t('core', 'App download or installation failed') }}</strong>
					</p>
					<p v-else-if="!app.isCompatible">
						<strong>{{ t('core', 'Cannot install this app because it is not compatible') }}</strong>
					</p>
					<p v-else-if="!app.canInstall">
						<strong>{{ t('core', 'Cannot install this app') }}</strong>
					</p>
				</div>
				<NcCheckboxRadioSwitch :checked="app.isSelected || app.active"
					:disabled="!app.isCompatible || app.active"
					:loading="app.loading"
					@update:checked="toggleSelect(app.id)" />
			</template>
		</div>

		<div class="dialog-row">
			<NcButton v-if="showInstallButton && !installingApps"
				data-cy-setup-recommended-apps-skip
				:href="defaultPageUrl"
				variant="tertiary">
				{{ t('core', 'Skip') }}
			</NcButton>

			<NcButton v-if="showInstallButton"
				data-cy-setup-recommended-apps-install
				:disabled="installingApps || !isAnyAppSelected"
				variant="primary"
				@click.stop.prevent="installApps">
				{{ installingApps ? t('core', 'Installing apps …') : t('core', 'Install recommended apps') }}
			</NcButton>
		</div>
	</div>
</template>

<script>
import { t } from '@nextcloud/l10n'
import { loadState } from '@nextcloud/initial-state'
import { generateUrl, imagePath } from '@nextcloud/router'
import axios from '@nextcloud/axios'
import pLimit from 'p-limit'
import logger from '../../logger.js'

import NcButton from '@nextcloud/vue/components/NcButton'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'

const recommended = {
	calendar: {
		description: t('core', 'Schedule work & meetings, synced with all your devices.'),
		icon: imagePath('core', 'places/calendar.svg'),
	},
	contacts: {
		description: t('core', 'Keep your colleagues and friends in one place without leaking their private info.'),
		icon: imagePath('core', 'places/contacts.svg'),
	},
	mail: {
		description: t('core', 'Simple email app nicely integrated with Files, Contacts and Calendar.'),
		icon: imagePath('core', 'actions/mail.svg'),
	},
	spreed: {
		description: t('core', 'Chatting, video calls, screen sharing, online meetings and web conferencing – in your browser and with mobile apps.'),
		icon: imagePath('core', 'apps/spreed.svg'),
	},
	richdocuments: {
		name: 'Nextcloud Office',
		description: t('core', 'Collaborative documents, spreadsheets and presentations, built on Collabora Online.'),
		icon: imagePath('core', 'apps/richdocuments.svg'),
	},
	notes: {
		description: t('core', 'Distraction free note taking app.'),
		icon: imagePath('core', 'apps/notes.svg'),
	},
	richdocumentscode: {
		hidden: true,
	},
}
const recommendedIds = Object.keys(recommended)

export default {
	name: 'RecommendedApps',
	components: {
		NcCheckboxRadioSwitch,
		NcButton,
	},
	data() {
		return {
			showInstallButton: false,
			installingApps: false,
			loadingApps: true,
			loadingAppsError: false,
			apps: [],
			defaultPageUrl: loadState('core', 'defaultPageUrl'),
		}
	},
	computed: {
		recommendedApps() {
			return this.apps.filter(app => recommendedIds.includes(app.id))
		},
		isAnyAppSelected() {
			return this.recommendedApps.some(app => app.isSelected)
		},
	},
	async mounted() {
		try {
			const { data } = await axios.get(generateUrl('settings/apps/list'))
			logger.info(`${data.apps.length} apps fetched`)

			this.apps = data.apps.map(app => Object.assign(app, { loading: false, installationError: false, isSelected: app.isCompatible }))
			logger.debug(`${this.recommendedApps.length} recommended apps found`, { apps: this.recommendedApps })

			this.showInstallButton = true
		} catch (error) {
			logger.error('could not fetch app list', { error })

			this.loadingAppsError = true
		} finally {
			this.loadingApps = false
		}
	},
	methods: {
		installApps() {
			this.installingApps = true

			const limit = pLimit(1)
			const installing = this.recommendedApps
				.filter(app => !app.active && app.isCompatible && app.canInstall && app.isSelected)
				.map(app => limit(async () => {
					logger.info(`installing ${app.id}`)
					app.loading = true
					return axios.post(generateUrl('settings/apps/enable'), { appIds: [app.id], groups: [] })
						.catch(error => {
							logger.error(`could not install ${app.id}`, { error })
							app.isSelected = false
							app.installationError = true
						})
						.then(() => {
							logger.info(`installed ${app.id}`)
							app.loading = false
							app.active = true
						})
				}))
			logger.debug(`installing ${installing.length} recommended apps`)
			Promise.all(installing)
				.then(() => {
					logger.info('all recommended apps installed, redirecting …')

					window.location = this.defaultPageUrl
				})
				.catch(error => logger.error('could not install recommended apps', { error }))
		},
		customIcon(appId) {
			if (!(appId in recommended) || !recommended[appId].icon) {
				logger.warn(`no app icon for recommended app ${appId}`)
				return imagePath('core', 'places/default-app-icon.svg')
			}
			return recommended[appId].icon
		},
		customName(app) {
			if (!(app.id in recommended)) {
				return app.name
			}
			return recommended[app.id].name || app.name
		},
		customDescription(appId) {
			if (!(appId in recommended)) {
				logger.warn(`no app description for recommended app ${appId}`)
				return ''
			}
			return recommended[appId].description
		},
		isHidden(appId) {
			if (!(appId in recommended)) {
				return false
			}
			return !!recommended[appId].hidden
		},
		toggleSelect(appId) {
			// disable toggle when installButton is disabled
			if (!(appId in recommended) || !this.showInstallButton) {
				return
			}
			const index = this.apps.findIndex(app => app.id === appId)
			this.$set(this.apps[index], 'isSelected', !this.apps[index].isSelected)
		},
	},
}
</script>

<style lang="scss" scoped>
.dialog-row {
	display: flex;
	justify-content: end;
	margin-top: 8px;
}

p {
	&.loading,
	&.loading-error {
		height: 100px;
	}

	&:last-child {
		margin-top: 10px;
	}
}

.text-center {
	text-align: center;
}

.app {
	display: flex;
	flex-direction: row;

	img {
		height: 50px;
		width: 50px;
		filter: var(--background-invert-if-dark);
	}

	img, .info {
		padding: 12px;
	}

	.info {
		h3, p {
			text-align: start;
		}

		h3 {
			margin-top: 0;
		}
	}

	.checkbox-radio-switch {
		margin-inline-start: auto;
		padding: 0 2px;
	}
}
</style>
