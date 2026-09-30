<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { IAppstoreApp } from '../../../../apps/appstore/src/apps.d.ts'

import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { imagePath } from '@nextcloud/router'
import { computed, onMounted, ref } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import * as appstoreApi from '../../../../apps/appstore/src/service/api.ts'
import { canInstall } from '../../../../apps/appstore/src/utils/appStatus.ts'
import { logger } from '../../utils/logger.ts'

interface RecommendedApp extends IAppstoreApp {
	loading: boolean
	isSelected: boolean
	error?: string
}

interface Recommendation {
	name?: string
	description?: string
	icon?: string
	/** Installed along with the other apps, but not offered on its own */
	hidden?: boolean
	/** The apps it is installed along with */
	required?: string[]
}

const recommended: Record<string, Recommendation> = {
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

const defaultPageUrl = loadState<string>('core', 'defaultPageUrl')

const showInstallButton = ref(false)
const installingApps = ref(false)
const loadingApps = ref(true)
const loadingAppsError = ref(false)
const recommendedApps = ref<RecommendedApp[]>([])

const isAnyAppSelected = computed(() => recommendedApps.value.some((app) => app.isSelected && !app.active))

onMounted(async () => {
	try {
		const apps = await appstoreApi.getApps()
		logger.info(`${apps.length} apps fetched`)

		recommendedApps.value = apps
			.filter((app) => app.id in recommended)
			.map((app) => ({
				...app,
				loading: false,
				isSelected: app.isCompatible && !isHidden(app.id),
			}))
		logger.debug(`${recommendedApps.value.length} recommended apps found`, { apps: recommendedApps.value })

		showInstallButton.value = true
	} catch (error) {
		logger.error('could not fetch app list', { error })
		loadingAppsError.value = true
	} finally {
		loadingApps.value = false
	}
})

/**
 * Install the selected apps and the hidden apps they require.
 */
async function installApps() {
	const availableApps = recommendedApps.value.filter((app) => app.active || (app.isSelected && canInstall(app)))
	const appsToInstall = [
		...availableApps.filter((app) => !app.active && app.isSelected),
		...recommendedApps.value.filter((app) => isHidden(app.id)
			&& (recommended[app.id]!.required ?? []).every((requiredAppId) => availableApps.some((requiredApp) => requiredApp.id === requiredAppId))),
	]

	logger.debug(`Installing ${appsToInstall.length} recommended apps`, { appIds: appsToInstall.map((app) => app.id) })
	installingApps.value = true
	for (const app of appsToInstall) {
		app.loading = true
	}

	const results = await Promise.allSettled(appsToInstall.map((app) => appstoreApi.enableApp(app.id)))
	results.forEach((result, index) => {
		const app = appsToInstall[index]!
		app.loading = false
		if (result.status === 'fulfilled') {
			app.active = true
			return
		}

		if (result.reason instanceof Error && result.reason.message === 'Dialog closed') {
			logger.info(`User cancelled the password confirmation for recommended app ${app.id}`)
			app.error = t('core', 'Password confirmation was aborted')
		} else {
			logger.error(`could not install recommended app ${app.id}`, { error: result.reason })
			app.error = t('core', 'App download or installation failed')
		}
		app.isSelected = false
	})
	installingApps.value = false
}

/**
 * @param appId - The app id
 */
function customIcon(appId: string): string {
	const icon = recommended[appId]?.icon
	if (!icon) {
		logger.warn(`no app icon for recommended app ${appId}`)
		return imagePath('core', 'places/default-app-icon.svg')
	}
	return icon
}

/**
 * @param app - The app
 */
function customName(app: RecommendedApp): string {
	return recommended[app.id]?.name || app.name
}

/**
 * @param appId - The app id
 */
function customDescription(appId: string): string {
	const description = recommended[appId]?.description
	if (description === undefined) {
		logger.warn(`no app description for recommended app ${appId}`)
		return ''
	}
	return description
}

/**
 * @param appId - The app id
 */
function isHidden(appId: string): boolean {
	return !!recommended[appId]?.hidden
}

/**
 * @param app - The app to (de)select for installation
 */
function toggleSelect(app: RecommendedApp) {
	// nothing can be selected while the apps are loading or installing
	if (!showInstallButton.value) {
		return
	}
	app.isSelected = !app.isSelected
}
</script>

<template>
	<div class="guest-box" data-cy-setup-recommended-apps>
		<h2>{{ t('core', 'Recommended apps') }}</h2>
		<p v-if="loadingApps" class="loading" :class="[$style.status]">
			{{ t('core', 'Loading apps …') }}
		</p>
		<p v-else-if="loadingAppsError" :class="$style.status">
			{{ t('core', 'Could not fetch list of apps from the App Store.') }}
		</p>

		<template v-for="app in recommendedApps" :key="app.id">
			<div v-if="!isHidden(app.id)" :class="$style.app">
				<img :class="$style.app__icon" :src="customIcon(app.id)" alt="">
				<div :class="$style.app__info">
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
					:class="$style.app__switch"
					:modelValue="app.isSelected || app.active"
					:disabled="!app.isCompatible || app.active"
					:loading="app.loading"
					@update:modelValue="toggleSelect(app)" />
			</div>
		</template>

		<div :class="$style.actions">
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

<style module lang="scss">
.actions {
	display: flex;
	justify-content: end;
	margin-top: 8px;
}

.status {
	height: 100px;
	text-align: center;
}

.app {
	display: flex;
	flex-direction: row;

	&__icon {
		height: 50px;
		width: 50px;
		padding: 12px;
		filter: var(--background-invert-if-dark);
	}

	&__info {
		padding: 12px;

		h3, p {
			text-align: start;
		}

		h3 {
			margin-top: 0;
		}

		p:last-child {
			margin-top: 10px;
		}
	}

	&__switch {
		margin-inline-start: auto;
		padding: 0 2px;
	}
}
</style>
