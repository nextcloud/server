<template>
	<NcAppNavigationItem v-if="storageStats"
		:aria-label="t('files', 'Storage informations')"
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

import logger from '../logger.js'

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
		 * @param {Event} [event = null] if user interaction
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

		t: translate,
	},
}
</script>

<style lang="scss" scoped>
// User storage stats display
.app-navigation-entry__settings-quota {
	// Align title with progress and icon
	&--not-unlimited::v-deep .app-navigation-entry__name {
		margin-top: -6px;
	}

	progress {
		position: absolute;
		bottom: 12px;
		margin-left: 44px;
		width: calc(100% - 44px - 22px);
	}
}
</style>
