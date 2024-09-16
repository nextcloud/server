<!--
SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
SPDX-FileCopyrightText: 2024 STRATO AG
SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcAppNavigationItem :aria-label="t('files', 'Storage informations')"
		:class="{ 'app-navigation-entry__settings-quota--not-unlimited': storageStats.quota >= 0}"
		:loading="loadingStorageStats"
		:name="storageStatsTitle"
		:title="storageStatsTitle + ': ' + storageStatsTextUsed + ' ' + storageStatsTextOf"
		class="app-navigation-entry__settings-quota"
		data-cy-files-navigation-settings-quota
		@click.stop.prevent="debounceUpdateStorageStats">
		<CloudIcon slot="icon" :size="16" />
		<NcProgressBar v-if="storageStats.quota >= 0"
			slot="extra"
			size="7"
			:error="storageStats.relative > 80"
			:value="Math.min(storageStats.relative, 100)"
			:color="barColor"
			:style="{'background-color': backgroundColor}" />
		<div slot="extra" class="quota-text">
			<span class="quota-text-bold">{{ storageStatsTextUsed }}</span>
			<span>{{ storageStatsTextOf }}</span>
		</div>
	</NcAppNavigationItem>
</template>

<script>
import { debounce, throttle } from 'throttle-debounce'
import { formatFileSize } from '@nextcloud/files'
import { generateUrl } from '@nextcloud/router'
import { loadState } from '@nextcloud/initial-state'
import { subscribe } from '@nextcloud/event-bus'
import { translate } from '@nextcloud/l10n'
import axios from '@nextcloud/axios'

import CloudIcon from 'vue-material-design-icons/Cloud.vue'
import NcAppNavigationItem from '@nextcloud/vue/dist/Components/NcAppNavigationItem.js'
import NcProgressBar from '@nextcloud/vue/dist/Components/NcProgressBar.js'

export default {
	name: 'SidebarQuota',

	components: {
		CloudIcon,
		NcAppNavigationItem,
		NcProgressBar,
	},

	data() {
		return {
			loadingStorageStats: false,
			storageStats: loadState('files', 'storageStats', null),
			barColor: null,
			backgroundColor: null,
		}
	},

	computed: {
		storageStatsTitle() {
			return this.t('files', 'Storage used')
		},
		storageStatsTextUsed() {
			return formatFileSize(this.storageStats?.used, false, false)
		},
		storageStatsTextOf() {
			return this.storageStats?.quota >= 0
				? this.t('files', 'of {quota}', { quota: formatFileSize(this.storageStats?.quota, false, false) })
				: ''
		},
	},

	beforeMount() {
		subscribe('files:node:created', this.throttleUpdateStorageStats)
		subscribe('files:node:deleted', this.throttleUpdateStorageStats)
		subscribe('files:node:moved', this.throttleUpdateStorageStats)
		subscribe('files:node:updated', this.throttleUpdateStorageStats)
	},

	mounted() {
		const styles = getComputedStyle(document.documentElement)
		this.barColor = styles.getPropertyValue('--ion-color-blue-b4')
		this.backgroundColor = styles.getPropertyValue('--color-primary-text')
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
		 */
		async updateStorageStats() {
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
			} finally {
				this.loadingStorageStats = false
			}
		},

		t: translate,
	},
}
</script>

<style lang="scss" scoped>
.app-navigation-entry__settings-quota {
	:deep(.app-navigation-entry:hover) {
		background-color: initial !important;
	}

	:deep(.app-navigation-entry__name) {
		font-weight: 500;
		padding-bottom: 32px;
	}

	&--not-unlimited::v-deep .app-navigation-entry__name {
		padding-bottom: 50px;
	}

	progress {
		position: absolute;
		bottom: 46px;
		margin-left: 14px;
		width: calc(100% - 44px);
		box-shadow: 0 2px 2px 0 rgba(0, 0, 0, 0.15) inset;
	}

	.quota-text {
		position: absolute;
		bottom: 16px;
		margin-left: 14px;

		.quota-text-bold {
			font-weight: 500;
		}
	}
}

</style>
