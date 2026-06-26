<!--
 SPDX-License-Identifier: AGPL-3.0-or-later
 SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
-->

<script setup lang="ts">
import type { IAppstoreApp, IAppstoreExApp } from '../apps.d.ts'

import { mdiCheck, mdiInformationOutline, mdiUpdate, mdiWeb } from '@mdi/js'
import { showError } from '@nextcloud/dialogs'
import { getLanguage, t } from '@nextcloud/l10n'
import { NcButton, NcDialog, NcIconSvgWrapper, NcLoadingIcon, NcNoteCard } from '@nextcloud/vue'
import { computed, ref } from 'vue'
import MarkdownPreview from './MarkdownPreview.vue'
import { useUpdatesStore } from '../store/updates.ts'
import logger from '../utils/logger.ts'

const props = defineProps<{
	apps: (IAppstoreApp | IAppstoreExApp)[]
}>()

const emit = defineEmits<{
	close: []
}>()

const store = useUpdatesStore()
const showDetails = ref('')
const isUpdating = ref(false)

const changelogText = computed(() => {
	if (!showDetails.value) {
		return ''
	}

	const app = props.apps.find((app) => app.id === showDetails.value)
	if (!app || !app.releases || app.releases.length === 0) {
		return ''
	}

	const [release] = app.releases
	const localizedEntry = release.translations[getLanguage()]
	return localizedEntry?.changelog ?? release.translations.en?.changelog ?? ''
})

/**
 * Handle update all apps
 */
async function onUpdate() {
	isUpdating.value = true
	for (const app of props.apps) {
		try {
			await store.updateApp(app.id)
		} catch (error) {
			logger.error(`Failed to update app ${app.id}`, { error })
			showError(t('appstore', 'Failed to update app {appName}', { appName: app.name }))
		}
	}
	isUpdating.value = false
	emit('close')
}
</script>

<template>
	<NcDialog :contentClasses="$style.updateAllDialog" size="normal" :name="t('appstore', 'Update all apps')">
		<p>{{ t('appstore', 'Are you sure you want to update all apps?') }}</p>
		<ul>
			<li v-for="app in apps" :key="app.id" :class="$style.updateAllDialog__listEntry">
				<div :class="$style.updateAllDialog__listEntryContent">
					<div :class="$style.updateAllDialog__listEntryHeading">
						<NcIconSvgWrapper
							:path="app.update ? mdiUpdate : mdiCheck"
							:name="app.update ? undefined : t('appstore', 'Update done')" />
						<span :class="$style.updateAllDialog__listEntryName">{{ app.name }} ({{ app.version }} → {{ app.update }})</span>
					</div>
					<div :class="$style.updateAllDialog__listEntryActions">
						<NcButton
							v-if="app.website"
							:aria-label="t('appstore', 'View website')"
							:title="t('appstore', 'View website')"
							:href="app.website"
							target="_blank"
							variant="tertiary">
							<template #icon>
								<NcIconSvgWrapper :path="mdiWeb" />
							</template>
						</NcButton>
						<NcButton
							v-if="app.releases"
							:aria-label="t('appstore', 'Show details')"
							:title="t('appstore', 'Show details')"
							:pressed="showDetails === app.id"
							@update:pressed="showDetails = $event ? app.id : ''">
							<template #icon>
								<NcIconSvgWrapper :path="mdiInformationOutline" />
							</template>
						</NcButton>
					</div>
				</div>
			</li>
		</ul>

		<NcNoteCard
			:class="$style.updateAllDialog__listEntryDetails"
			:heading="t('appstore', 'Details')"
			type="info">
			<MarkdownPreview
				:minHeadingLevel="3"
				:text="changelogText" />
		</NcNoteCard>

		<template #actions>
			<NcButton variant="tertiary" @click="emit('close')">
				{{ t('appstore', 'Cancel') }}
			</NcButton>
			<NcButton variant="primary" @click="onUpdate">
				<template v-if="isUpdating" #icon>
					<NcLoadingIcon />
				</template>
				{{ t('appstore', 'Update all') }}
			</NcButton>
		</template>
	</NcDialog>
</template>

<style module>
.updateAllDialog {
	min-height: 50vh !important;
}

.updateAllDialog__list {
	display: flex;
	flex-direction: row;
	gap: calc(3 * var(--default-grid-baseline));
}

.updateAllDialog__listEntry {
	display: flex;
	flex-direction: column;
	gap: calc(2 * var(--default-grid-baseline));
	padding: calc(2 * var(--default-grid-baseline));
}

.updateAllDialog__listEntryHeading {
	display: flex;
}

.updateAllDialog__listEntryName {
	font-weight: 500;
	line-height: var(--default-clickable-area);
}

.updateAllDialog__listEntryActions {
	display: flex;
	flex-direction: row;
	gap: var(--default-grid-baseline);
}

.updateAllDialog__listEntryContent {
	display: flex;
	justify-content: space-between;
}

.updateAllDialog__listEntryDetails {
	margin: 0;
}
</style>
