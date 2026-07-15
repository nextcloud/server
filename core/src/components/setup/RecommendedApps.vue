<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="guest-box" data-cy-setup-recommended-apps>
		<h2>{{ t('core', 'Recommended apps') }}</h2>
		<p v-if="loadingApps" class="loading text-center">
			{{ t('core', 'Loading apps …') }}
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
					<p v-if="app.error">
						<strong>{{ app.error }}</strong>
					</p>
					<p v-else-if="app.active">
						<strong>{{ t('core', 'App already installed') }}</strong>
					</p>
					<p v-else-if="!app.isCompatible">
						<strong>{{ t('core', 'Cannot install this app because it is not compatible') }}</strong>
					</p>
					<p v-else-if="!canInstall(app)">
						<strong>{{ t('core', 'Cannot install this app') }}</strong>
					</p>
				</div>
				<NcCheckboxRadioSwitch
					:model-value="app.isSelected || app.active"
					:disabled="!app.isCompatible || app.active"
					:loading="app.loading"
					@update:modelValue="toggleSelect(app.id)" />
			</template>
		</div>

		<div class="dialog-row">
			<NcButton
				v-if="showInstallButton && !installingApps"
				data-cy-setup-recommended-apps-skip
				:href="defaultPageUrl"
				variant="tertiary">
				{{ t('core', 'Skip') }}
			</NcButton>

			<NcButton
				v-if="showInstallButton"
				data-cy-setup-recommended-apps-install
				:disabled="installingApps || !isAnyAppSelected"
				variant="primary"
				@click="installApps">
				{{ installingApps ? t('core', 'Installing apps …') : t('core', 'Install recommended apps') }}
			</NcButton>
		</div>
	</div>
</template>

<script>
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { imagePath } from '@nextcloud/router'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import logger from '../../logger.js'
import * as appstoreApi from '~/apps/appstore/src/service/api.ts'
import { canInstall } from '~/apps/appstore/src/utils/appStatus.js'

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
		required: ['richdocuments'],
	},
}
const recommendedIds = Object.keys(recommended)

export default {
	name: 'RecommendedApps',
	components: {
		NcCheckboxRadioSwitch,
		NcButton,
	},

	setup() {
		return {
			t,
			canInstall,
		}
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
			return this.apps.filter((app) => recommendedIds.includes(app.id))
		},

		isAnyAppSelected() {
			return this.recommendedApps.some((app) => app.isSelected && !app.active)
		},
	},

	async mounted() {
		try {
			const apps = await appstoreApi.getApps()
			logger.info(`${apps.length} apps fetched`)

			this.apps = apps.map((app) => Object.assign(app, {
				loading: false,
				installationError: false,
				isSelected: app.isCompatible && !this.isHidden(app.id),
			}))
			this.$nextTick(() => logger.debug(`${this.recommendedApps.length} recommended apps found`, { apps: this.recommendedApps }))

			this.showInstallButton = true
		} catch (error) {
			logger.error('could not fetch app list', { error })

			this.loadingAppsError = true
		} finally {
			this.loadingApps = false
		}
	},

	methods: {
		async installApps() {
			const availableApps = this.recommendedApps.filter((app) => app.active || (app.isSelected && canInstall(app)))
			const appsToInstall = [
				// all possible selected apps that are not active yet
				...availableApps.filter((app) => !app.active && app.isSelected),
				// all hidden apps that are required by the selected apps
				...this.recommendedApps.filter((app) => this.isHidden(app.id)
					&& recommended[app.id].required.every((requiredAppId) => availableApps.some((requiredApp) => requiredApp.id === requiredAppId))),
			]

			logger.debug(`Installing ${appsToInstall.length} recommended apps`, { appIds: appsToInstall.map((app) => app.id) })
			this.installingApps = true
			/** @type {Promise<void>[]} */
			const promises = []
			for (const app of appsToInstall) {
				app.loading = true
				promises.push(appstoreApi.enableApp(app.id))
			}

			const results = await Promise.allSettled(promises)
			for (let i = 0; i < results.length; i++) {
				const result = results[i]
				const app = appsToInstall[i]
				app.loading = false
				if (result.status === 'rejected') {
					if (result.reason instanceof Error && result.reason.message === 'Dialog closed') {
						logger.info(`User cancelled the password confirmation for recommended app ${app.id}`)
						app.error = t('core', 'Password confirmation was aborted')
					} else {
						logger.error(`could not install recommended app ${app.id}`, { error: result.reason })
						app.error = t('core', 'App download or installation failed')
					}
					app.isSelected = false
				} else {
					app.active = true
				}
			}
			this.installingApps = false
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
			const index = this.apps.findIndex((app) => app.id === appId)
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
