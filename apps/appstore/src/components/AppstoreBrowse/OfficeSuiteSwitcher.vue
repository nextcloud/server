<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { showConfirmation } from '@nextcloud/dialogs'
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { ref, watch } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import OfficeSuiteSwitcherItem from './OfficeSuiteSwitcherItem.vue'
import { OFFICE_SUITES } from '../../service/OfficeSuites.ts'
import { useAppsStore } from '../../store/apps.ts'
import { canDisable, needForceEnable } from '../../utils/appStatus.ts'

const store = useAppsStore()
const isAllInOne = loadState('appstore', 'isAllInOne', false)

const isProcessing = ref(false)
const selectedSuiteId = ref<string | null>(getInitialSuite())
watch(selectedSuiteId, onSuiteChanged)

/**
 * Get the initially selected office suite based on the installed apps
 */
function getInitialSuite() {
	for (const suite of OFFICE_SUITES) {
		const app = store.apps.find((a) => a.id === suite.appId && a.installed)
		if (app && app.active) {
			return suite.id
		}
	}
	return null
}

/**
 * Disable all office suites
 */
function disableSuites() {
	selectedSuiteId.value = null
}

/**
 * Disable a specific office suite
 *
 * @param suite - The suite to disable
 */
async function disableSuite(suite: typeof OFFICE_SUITES[number]) {
	const app = store.getAppById(suite.appId)
	if (!app) {
		return
	}

	if (canDisable(app)) {
		await store.disableApp(suite.appId)
	}
}

/**
 * Callback to handle office suite changes. Enables the selected suite and disables others.
 *
 * @param newSuiteId - The new selected suite ID
 * @param oldSuiteId - The previously selected suite ID
 */
async function onSuiteChanged(newSuiteId: string | null, oldSuiteId: string | null) {
	if (isProcessing.value || newSuiteId === oldSuiteId) {
		return
	}

	try {
		isProcessing.value = true
		const suite = OFFICE_SUITES.find((s) => s.id === newSuiteId)
		if (!suite) {
			// No suite selected, disable all suites
			for (const s of OFFICE_SUITES) {
				await disableSuite(s)
			}
			return
		}

		const app = store.getAppById(suite.appId)!
		if (needForceEnable(app)) {
			const result = await showConfirmation({
				name: t('appstore', 'Force enable {suite}?', { suite: suite.name }),
				text: t('appstore', 'Enabling {suite} requires force enabling the app. This may cause issues with your Nextcloud instance. Are you sure you want to proceed?', { suite: suite.name }),
				labelConfirm: t('appstore', 'Force enable'),
				labelReject: t('appstore', 'Cancel'),
				severity: 'warning',
			})

			if (result) {
				await store.enableApp(suite.appId, true)
			} else {
				// Revert selection
				selectedSuiteId.value = oldSuiteId
				return
			}
		}

		// Enable the selected suite and disable others
		for (const s of OFFICE_SUITES) {
			if (s.id === newSuiteId) {
				await store.enableApp(s.appId)
			} else {
				await disableSuite(s)
			}
		}
	} finally {
		isProcessing.value = false
	}
}
</script>

<template>
	<NcNoteCard v-if="isAllInOne" type="info">
		<p>{{ t('appstore', 'Office suite switching is managed through the Nextcloud All-in-One interface.') }}</p>
		<p>{{ t('appstore', 'Please use the AIO interface to switch between office suites.') }}</p>
	</NcNoteCard>

	<section v-else :class="$style.officeSuiteSwitcher">
		<h3 :class="$style.officeSuiteSwitcher__title">
			{{ t('appstore', 'Select your preferred office suite.') }}
		</h3>
		<p>{{ t('appstore', 'Please note that installing requires manual server setup.') }}</p>
		<fieldset :class="$style.officeSuiteSwitcher__cards">
			<OfficeSuiteSwitcherItem
				v-for="suite in OFFICE_SUITES"
				:key="suite.id"
				v-model:selected="selectedSuiteId"
				:class="$style.officeSuiteSwitcher__cardsItem"
				:suite="suite"
				:loading="isProcessing" />
		</fieldset>
		<div :class="$style.officeSuiteSwitcher__actions">
			<NcButton :disabled="!selectedSuiteId" @click="disableSuites">
				{{ t('appstore', 'Disable office suites') }}
			</NcButton>
		</div>
	</section>
</template>

<style module>
.officeSuiteSwitcher {
	padding: 20px;
	margin-bottom: 30px;

	h3 {
		margin: 0px;
	}

	p {
		margin: 8px 0;

		&:first-child {
			font-weight: 600;
		}
	}
}

.officeSuiteSwitcher__cards {
	display: flex;
	gap: 20px;
	max-width: 1200px;
}

.officeSuiteSwitcher__cardsItem {
	flex: 1;
}

.officeSuiteSwitcher__actions {
	margin-top: 16px;
}

.officeSuiteSwitcher__disableButton {
	background: transparent;
	border: 1px solid var(--color-border);
	border-radius: var(--border-radius-small);
	padding: 8px 12px;
	font-weight: 600;
	color: var(--color-main-text);
	cursor: pointer;
	transition: background 0.15s ease, border-color 0.15s ease;
}

.officeSuiteSwitcher__disableButton:disabled {
	opacity: 0.5;
	cursor: default;
}

.officeSuiteSwitcher__disableButton:hover:not(:disabled) {
	border-color: var(--color-primary-element);
	background: var(--color-background-dark);
}

@media (max-width: 768px) {
	.officeSuiteSwitcher__cards {
		flex-direction: column;
	}
}
</style>
