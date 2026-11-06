<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<section class="details-section">
		<div v-if="teams.length" class="details-section__block">
			<h3 class="details-section__heading">{{ t('settings', 'Your teams') }}</h3>
			<NcFormBox class="details-section__box">
				<NcFormBoxButton
					v-for="team in teams"
					:key="team.id"
					:label="team.displayName"
					:href="team.link || undefined"
					:target="team.link ? '_blank' : undefined">
					<template #icon>
						<OpenInNew :size="20" />
					</template>
				</NcFormBoxButton>
			</NcFormBox>
		</div>

		<div v-if="groups.length" class="details-section__block">
			<h3 class="details-section__heading">{{ t('settings', 'Your groups') }}</h3>
			<p class="details-section__list">{{ groups.join(', ') }}</p>
		</div>

		<div class="details-section__block">
			<!-- eslint-disable-next-line vue/no-v-html -->
			<p class="details-section__quota" v-html="quotaText" />
			<NcProgressBar
				size="medium"
				:value="usageRelative"
				:error="usageRelative > 80" />
		</div>
	</section>
</template>

<script>
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import NcFormBox from '@nextcloud/vue/components/NcFormBox'
import NcFormBoxButton from '@nextcloud/vue/components/NcFormBoxButton'
import NcProgressBar from '@nextcloud/vue/components/NcProgressBar'
import OpenInNew from 'vue-material-design-icons/OpenInNew.vue'

/** SYNC to be kept in sync with `lib/public/Files/FileInfo.php` */
const SPACE_UNLIMITED = -3

const { groups, teams, quota, totalSpace, usage, usageRelative } = loadState('settings', 'personalInfoParameters', {})

export default {
	name: 'DetailsSection',

	components: {
		NcFormBox,
		NcFormBoxButton,
		NcProgressBar,
		OpenInNew,
	},

	data() {
		return {
			groups,
			teams,
			usageRelative,
		}
	},

	computed: {
		quotaText() {
			if (quota === SPACE_UNLIMITED) {
				return t('settings', 'You are using {s}{usage}{/s}', { usage, s: '<strong>', '/s': '</strong>' }, undefined, { escape: false })
			}
			return t(
				'settings',
				'You are using {s}{usage}{/s} of {s}{totalSpace}{/s} ({s}{usageRelative}%{/s})',
				{ usage, totalSpace, usageRelative, s: '<strong>', '/s': '</strong>' },
				undefined,
				{ escape: false },
			)
		},
	},
}
</script>

<style lang="scss" scoped>
.details-section {
	display: flex;
	flex-direction: column;
	gap: 16px;
	padding: 6px 0;

	&__block {
		display: flex;
		flex-direction: column;
		gap: 6px;
	}

	&__heading {
		margin: 0;
		font-size: 16px;
		font-weight: bold;
	}

	&__box {
		margin-inline-end: 52px;
	}

	&__list,
	&__quota {
		margin: 0;
		color: var(--color-text-maxcontrast);
	}
}
</style>
