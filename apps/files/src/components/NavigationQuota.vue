<!--
 - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
 -->

<script setup lang="ts">
import axios from '@nextcloud/axios'
import { showError } from '@nextcloud/dialogs'
import { subscribe, unsubscribe } from '@nextcloud/event-bus'
import { formatFileSize } from '@nextcloud/files'
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import { useThrottleFn } from '@vueuse/core'
import { computed, onBeforeMount, onMounted, onUnmounted, ref } from 'vue'
import NcAppNavigationItem from '@nextcloud/vue/components/NcAppNavigationItem'
import NcProgressBar from '@nextcloud/vue/components/NcProgressBar'
import ChartPie from 'vue-material-design-icons/ChartPieOutline.vue'
import { logger } from '../utils/logger.ts'

type StorageStats = {
	used: number
	free: number
	total: number
	quota: number
	relative: number
}

const loadingStorageStats = ref(false)
const storageStats = ref(loadState<StorageStats | null>('files', 'storageStats', null))

const storageStatsTitle = computed(() => {
	const usedQuotaByte = formatFileSize(storageStats.value?.used ?? 0, false, false)
	const quotaByte = formatFileSize(storageStats.value?.total ?? 0, false, false)

	// If no quota set
	if (storageStats.value === null || storageStats.value?.quota < 0) {
		return t('files', '{usedQuotaByte} used', { usedQuotaByte })
	}

	return t('files', '{used} of {quota} used', {
		used: usedQuotaByte,
		quota: quotaByte,
	})
})

const storageStatsTooltip = computed(() => {
	if (!storageStats.value?.relative) {
		return ''
	}

	return t('files', '{relative}% used', storageStats.value)
})

const throttleUpdateStorageStats = useThrottleFn(updateStorageStats, 2000)
onBeforeMount(() => {
	subscribe('files:node:created', throttleUpdateStorageStats)
	subscribe('files:node:deleted', throttleUpdateStorageStats)
	subscribe('files:node:moved', throttleUpdateStorageStats)
	subscribe('files:node:updated', throttleUpdateStorageStats)
})
onUnmounted(() => {
	unsubscribe('files:node:created', throttleUpdateStorageStats)
	unsubscribe('files:node:deleted', throttleUpdateStorageStats)
	unsubscribe('files:node:moved', throttleUpdateStorageStats)
	unsubscribe('files:node:updated', throttleUpdateStorageStats)
})

onMounted(() => {
	// If the user has a quota set, warn if the available account storage is <=0
	//
	// NOTE: This doesn't catch situations where actual *server*
	// disk (non-quota) space is low, but those should probably
	// be handled differently anyway since a regular user can't
	// can't do much about them (If we did want to indicate server disk
	// space matters to users, we'd probably want to use a warning
	// specific to that situation anyhow. So this covers warning covers
	// our primary day-to-day concern (individual account quota usage).
	//
	if (storageStats.value && storageStats.value?.quota > 0 && storageStats.value?.free === 0) {
		showStorageFullWarning()
	}
})

/**
 * Update the storage stats
 * Throttled at max 1 refresh per minute
 *
 * @param event - If user interaction
 */
async function updateStorageStats(event?: unknown) {
	if (loadingStorageStats.value) {
		return
	}

	loadingStorageStats.value = true
	try {
		const response = await axios.get(generateUrl('/apps/files/api/v1/stats'))
		if (!response?.data?.data) {
			throw new Error('Invalid storage stats')
		}

		// Warn the user if the available account storage changed from > 0 to 0
		// (unless only because quota was intentionally set to 0 by admin in the interim)
		if (storageStats.value && storageStats.value?.free > 0 && response.data.data?.free === 0 && response.data.data?.quota > 0) {
			showStorageFullWarning()
		}

		storageStats.value = response.data.data
	} catch (error) {
		logger.error('Could not refresh storage stats', { error })
		// Only show to the user if it was manually triggered
		if (event) {
			showError(t('files', 'Could not refresh storage stats'))
		}
	} finally {
		loadingStorageStats.value = false
	}
}

/**
 * Show a warning that the user's storage is full and files can not be updated or synced anymore
 */
function showStorageFullWarning() {
	showError(t('files', 'Your storage is full, files can not be updated or synced anymore!'))
}
</script>

<template>
	<NcAppNavigationItem
		v-if="storageStats"
		:aria-description="t('files', 'Storage information')"
		:class="{ 'app-navigation-entry__settings-quota--not-unlimited': storageStats.quota >= 0 }"
		:loading="loadingStorageStats"
		:name="storageStatsTitle"
		:title="storageStatsTooltip"
		class="app-navigation-entry__settings-quota"
		data-cy-files-navigation-settings-quota
		@click.stop.prevent="throttleUpdateStorageStats">
		<template #icon>
			<ChartPie :size="20" />
		</template>

		<!-- Progress bar -->
		<template #extra>
			<NcProgressBar
				v-if="storageStats.quota >= 0"

				:aria-label="t('files', 'Storage quota')"
				:error="storageStats.relative > 80"
				:value="Math.min(storageStats.relative, 100)" />
		</template>
	</NcAppNavigationItem>
</template>

<style lang="scss" scoped>
// User storage stats display
.app-navigation-entry__settings-quota {
	// Align title with progress and icon
	--app-navigation-quota-margin: calc((var(--default-clickable-area) - 24px) / 2); // 20px icon size and 4px progress bar

	&--not-unlimited :deep(.app-navigation-entry__name) {
		line-height: 1;
		margin-top: var(--app-navigation-quota-margin);
	}

	progress {
		position: absolute;
		bottom: var(--app-navigation-quota-margin);
		margin-inline-start: var(--default-clickable-area);
		width: calc(100% - (1.5 * var(--default-clickable-area)));
	}
}
</style>
