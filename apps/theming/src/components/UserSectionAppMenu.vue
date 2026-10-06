<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { INavigationEntry } from '../../../../core/src/types/navigation.d.ts'
import type { IApp } from './AppOrderSelector.vue'

import axios from '@nextcloud/axios'
import { showError } from '@nextcloud/dialogs'
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { generateOcsUrl } from '@nextcloud/router'
import { computed, ref } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'
import IconUndo from 'vue-material-design-icons/Undo.vue'
import AppOrderSelector from './AppOrderSelector.vue'
import { logger } from '../utils/logger.ts'

/** The app order user setting */
type IAppOrder = Record<string, { order: number, app?: string }>

/** OCS responses */
interface IOCSResponse<T> {
	ocs: {
		meta: unknown
		data: T
	}
}

const {
	/** The app order currently defined by the user */
	userAppOrder,
	/** The apps the user pinned to the navigation bar (if any) */
	userPinnedApps,
	/** The enforced default app set by the administrator (if any) */
	enforcedDefaultApp,
} = loadState<{ userAppOrder: IAppOrder, userPinnedApps: string[], enforcedDefaultApp: string }>('theming', 'navigationBar')

/**
 * Array of all available apps, it is set by a core controller for the app menu, so it is always available
 */
const initialAppOrder = loadState<INavigationEntry[]>('core', 'apps')
	.filter(({ type }) => type === 'link')
	.map((app) => ({ ...app, label: app.name, default: app.default && app.id === enforcedDefaultApp, pinned: userPinnedApps.includes(app.id) }))

/**
 * The current apporder (sorted by user)
 */
const appOrder = ref([...initialAppOrder])

/**
 * Check if a custom app order is used or the default is shown
 */
const hasCustomAppOrder = ref(!Array.isArray(userAppOrder) || Object.values(userAppOrder).length > 0)

/**
 * Track if the app order has changed, so the user can be informed to reload
 */
const hasAppOrderChanged = computed(() => initialAppOrder.some(({ id }, index) => id !== appOrder.value[index]?.id))

/**
 * Track if the pinned apps have changed, so the user can be informed to reload
 */
const hasPinnedAppsChanged = computed(() => initialAppOrder.some(({ id, pinned }) => pinned !== appOrder.value.find((app) => app.id === id)?.pinned))

/** ID of the "app order has changed" NcNodeCard, used for the aria-details of the apporder */
const elementIdAppOrderChanged = 'theming-apporder-changed-infocard'

/** ID of the "pinned apps have changed" NcNodeCard, used for the aria-details of the apporder */
const elementIdPinnedAppsChanged = 'theming-pinnedapps-changed-infocard'

/** ID of the "you can not change the default app" NcNodeCard, used for the aria-details of the apporder */
const elementIdEnforcedDefaultApp = 'theming-apporder-changed-infocard'

/**
 * The aria-details value of the app order selector
 * contains the space separated list of element ids of NcNoteCards
 */
const ariaDetailsAppOrder = computed(() => (hasAppOrderChanged.value ? `${elementIdAppOrderChanged} ` : '') + (hasPinnedAppsChanged.value ? `${elementIdPinnedAppsChanged} ` : '') + (enforcedDefaultApp ? elementIdEnforcedDefaultApp : ''))

/**
 * Update the app order, called when the user sorts entries
 *
 * @param value The new app order value
 */
async function updateAppOrder(value: IApp[]) {
	const order: IAppOrder = {}
	value.forEach(({ app, id }, index) => {
		order[id] = { order: index, app }
	})

	try {
		await saveSetting('apporder', order)
		appOrder.value = value as never
		hasCustomAppOrder.value = true
	} catch (error) {
		logger.error('Could not set the app order', { error })
		showError(t('theming', 'Could not set the app order'))
	}
}

/**
 * Update the pinned apps, called when the user toggles the pin of an entry
 *
 * @param app The app to toggle the pinned state of
 */
async function togglePinned(app: IApp) {
	const pinned = appOrder.value
		.filter(({ id, pinned }) => (id === app.id ? !pinned : pinned))
		.map(({ id }) => id)

	try {
		await saveSetting('apps_pinned', pinned)
		appOrder.value = appOrder.value.map((entry) => (entry.id === app.id ? { ...entry, pinned: !entry.pinned } : entry))
	} catch (error) {
		logger.error('Could not update the pinned apps', { error })
		showError(t('theming', 'Could not update the pinned apps'))
	}
}

/**
 * Reset the app order to the default
 */
async function resetAppOrder() {
	try {
		await saveSetting('apporder', [])
		hasCustomAppOrder.value = false

		// Reset our app order list, keeping the pinned state of the entries
		const pinnedIds = new Set(appOrder.value.filter(({ pinned }) => pinned).map(({ id }) => id))
		const { data } = await axios.get<IOCSResponse<INavigationEntry[]>>(generateOcsUrl('/core/navigation/apps'), {
			headers: {
				'OCS-APIRequest': 'true',
			},
		})
		appOrder.value = data.ocs.data.map((app) => ({ ...app, label: app.name, default: app.default && app.app === enforcedDefaultApp, pinned: pinnedIds.has(app.id) }))
	} catch (error) {
		logger.error('Could not reset the app order', { error })
		showError(t('theming', 'Could not reset the app order'))
	}
}

/**
 * @param key - The config key
 * @param value - The config value
 */
async function saveSetting(key: string, value: unknown) {
	const url = generateOcsUrl('apps/provisioning_api/api/v1/config/users/{appId}/{configKey}', {
		appId: 'core',
		configKey: key,
	})
	return await axios.post(url, {
		configValue: JSON.stringify(value),
	})
}
</script>

<template>
	<NcSettingsSection :name="t('theming', 'Navigation bar settings')">
		<p>
			{{ t('theming', 'You can configure the app order used for the navigation bar. The first entry will be the default app, opened after login or when clicking on the logo.') }}
		</p>
		<p>
			{{ t('theming', 'Pinned apps are shown directly in the navigation bar, while all other apps stay available in the apps menu.') }}
		</p>
		<NcNoteCard v-if="enforcedDefaultApp" :id="elementIdEnforcedDefaultApp" type="info">
			{{ t('theming', 'The default app can not be changed because it was configured by the administrator.') }}
		</NcNoteCard>
		<NcNoteCard v-if="hasAppOrderChanged" :id="elementIdAppOrderChanged" type="info">
			{{ t('theming', 'The app order was changed, to see it in action you have to reload the page.') }}
		</NcNoteCard>
		<NcNoteCard v-if="hasPinnedAppsChanged" :id="elementIdPinnedAppsChanged" type="info">
			{{ t('theming', 'The pinned apps were changed, to see them in the navigation bar you have to reload the page.') }}
		</NcNoteCard>

		<AppOrderSelector
			:class="$style.userSectionAppMenu__selector"
			:aria-details="ariaDetailsAppOrder"
			:modelValue="appOrder"
			showPin
			@update:modelValue="updateAppOrder"
			@toggle:pinned="togglePinned" />

		<NcButton
			data-test-id="btn-apporder-reset"
			:disabled="!hasCustomAppOrder"
			variant="tertiary"
			@click="resetAppOrder">
			<template #icon>
				<IconUndo :size="20" />
			</template>
			{{ t('theming', 'Reset default app order') }}
		</NcButton>
	</NcSettingsSection>
</template>

<style module>
.userSectionAppMenu__selector {
	margin-block: 12px;
}
</style>
