<!--
 - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
 -->
<template>
	<NcAppNavigationItem v-if="storageStats"
		:aria-description="t('files', 'Storage information')"
		:class="{ 'app-navigation-entry__settings-quota--not-unlimited': storageStats.quota >= 0}"
		:loading="loadingStorageStats"
		:name="storageStatsTitle"
		:title="storageStatsTooltip"
		class="app-navigation-entry__settings-quota"
		data-cy-files-navigation-settings-quota
		@click.stop.prevent="debounceUpdateStorageStats">
		<ChartPie slot="icon" :size="20" />

		<!-- Progress bar -->
		<NcProgressBar v-if="storageStats.quota >= 0"
			slot="extra"
			:aria-label="t('files', 'Storage quota')"
			:error="storageStats.relative > 80"
			:value="Math.min(storageStats.relative, 100)" />
	</NcAppNavigationItem>
</template>

<script>
import { debounce, throttle } from 'throttle-debounce'
import { formatFileSize } from '@nextcloud/files'
import { generateUrl } from '@nextcloud/router'
import { loadState } from '@nextcloud/initial-state'
import { showError } from '@nextcloud/dialogs'
import { subscribe } from '@nextcloud/event-bus'
import { translate } from '@nextcloud/l10n'
import axios from '@nextcloud/axios'

import ChartPie from 'vue-material-design-icons/ChartPie.vue'
import NcAppNavigationItem from '@nextcloud/vue/dist/Components/NcAppNavigationItem.js'
import NcProgressBar from '@nextcloud/vue/dist/Components/NcProgressBar.js'

import logger from '../logger.ts'

export default {
	name: 'NavigationQuota',

	components: {
		ChartPie,
		NcAppNavigationItem,
		NcProgressBar,
	},

	data() {
		return {
			loadingStorageStats: false,
			storageStats: loadState('files', 'storageStats', null),
		}
	},

	computed: {
		storageStatsTitle() {
			const usedQuotaByte = formatFileSize(this.storageStats?.used, false, false)
			const quotaByte = formatFileSize(this.storageStats?.quota, false, false)

			// If no quota set
			if (this.storageStats?.quota < 0) {
				return this.t('files', '{usedQuotaByte} used', { usedQuotaByte })
			}

			return this.t('files', '{used} of {quota} used', {
				used: usedQuotaByte,
				quota: quotaByte,
			})
		},
		storageStatsTooltip() {
			if (!this.storageStats.relative) {
				return ''
			}

			return this.t('files', '{relative}% used', this.storageStats)
		},
	},

	beforeMount() {
		/**
		 * Update storage stats every minute
		 * TODO: remove when all views are migrated to Vue
		 */
		setInterval(this.throttleUpdateStorageStats, 60 * 1000)

		subscribe('files:node:created', this.throttleUpdateStorageStats)
		subscribe('files:node:deleted', this.throttleUpdateStorageStats)
		subscribe('files:node:moved', this.throttleUpdateStorageStats)
		subscribe('files:node:updated', this.throttleUpdateStorageStats)
	},

	mounted() {
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
		if (this.storageStats?.quota > 0 && this.storageStats?.free === 0) {
			this.showStorageFullWarning()
		}
	},

	methods: {
		// From user input
		debounceUpdateStorageStats: debounce(200, function(event) {
			this.updateStorageStats(event)
		}),
		// From interval or event bus
		throttleUpdateStorageStats: throttle(1000, function(event) {
			this.updateStorageStats(event)
		}),

		/**
		 * Update the storage stats
		 * Throttled at max 1 refresh per minute
		 *
		 * @param {Event} [event] if user interaction
		 */
		async updateStorageStats(event = null) {
			if (this.loadingStorageStats) {
				return
			}

			this.loadingStorageStats = true
			try {
				const response = await axios.get(generateUrl('/apps/files/api/v1/stats'))
				if (!response?.data?.data) {
					throw new Error('Invalid storage stats')
				}

				// Warn the user if the available account storage changed from > 0 to 0
				// (unless only because quota was intentionally set to 0 by admin in the interim)
				if (this.storageStats?.free > 0 && response.data.data?.free === 0 && response.data.data?.quota > 0) {
					this.showStorageFullWarning()
				}

				this.storageStats = response.data.data
			} catch (error) {
				logger.error('Could not refresh storage stats', { error })
				// Only show to the user if it was manually triggered
				if (event) {
					showError(t('files', 'Could not refresh storage stats'))
				}
			} finally {
				this.loadingStorageStats = false
			}
		},

		showStorageFullWarning() {
			showError(this.t('files', 'Your storage is full, files can not be updated or synced anymore!'))
		},

		t: translate,
	},
}
</script>

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
