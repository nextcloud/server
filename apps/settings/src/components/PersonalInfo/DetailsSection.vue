<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<section>
		<HeaderBar :is-heading="true" :readable="t('settings', 'Details')" />

		<div class="details">
			<div class="details__groups">
				<Account :size="20" />
				<div class="details__groups-info">
					<p>{{ t('settings', 'You are a member of the following groups:') }}</p>
					<p class="details__groups-list">
						{{ groups.join(', ') }}
					</p>
				</div>
			</div>
			<div class="details__quota">
				<CircleSlice :size="20" />
				<div class="details__quota-info">
					<p class="details__quota-text" v-html="quotaText" />
					<NcProgressBar size="medium"
						:value="usageRelative"
						:error="usageRelative > 80" />
				</div>
			</div>
		</div>
	</section>
</template>

<script>
import { loadState } from '@nextcloud/initial-state'
import NcProgressBar from '@nextcloud/vue/dist/Components/NcProgressBar.js'

import Account from 'vue-material-design-icons/Account.vue'
import CircleSlice from 'vue-material-design-icons/CircleSlice3.vue'

import HeaderBar from './shared/HeaderBar.vue'

/** SYNC to be kept in sync with `lib/public/Files/FileInfo.php` */
const SPACE_UNLIMITED = -3

const { groups, quota, totalSpace, usage, usageRelative } = loadState('settings', 'personalInfoParameters', {})

export default {
	name: 'DetailsSection',

	components: {
		Account,
		CircleSlice,
		HeaderBar,
		NcProgressBar,
	},

	data() {
		return {
			groups,
			usageRelative,
		}
	},

	computed: {
		quotaText() {
			if (quota === SPACE_UNLIMITED) {
				return t('settings', 'You are using <strong>{usage}</strong>', { usage })
			}
			return t(
				'settings',
				'You are using <strong>{usage}</strong> of <strong>{totalSpace}</strong> (<strong>{usageRelative}%</strong>)',
				{ usage, totalSpace, usageRelative },
			)
		},
	},
}
</script>

<style lang="scss" scoped>
.details {
	display: flex;
	flex-direction: column;
	margin: 10px 32px 10px 0;
	gap: 16px 0;
	color: var(--color-text-maxcontrast);

	&__groups,
	&__quota {
		display: flex;
		gap: 0 10px;

		&-info {
			display: flex;
			flex-direction: column;
			width: 100%;
			gap: 4px 0;
		}

		&-list {
			font-weight: bold;
		}

		&:deep(.material-design-icon) {
			align-self: flex-start;
			margin-top: 2px;
		}
	}
}
</style>
