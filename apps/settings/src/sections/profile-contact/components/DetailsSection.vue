<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { mdiOpenInNew } from '@mdi/js'
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import NcFormBox from '@nextcloud/vue/components/NcFormBox'
import NcFormBoxButton from '@nextcloud/vue/components/NcFormBoxButton'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcProgressBar from '@nextcloud/vue/components/NcProgressBar'

/** SYNC to be kept in sync with `lib/public/Files/FileInfo.php` */
const SPACE_UNLIMITED = -3

const { groups, teams, quota, totalSpace, usage, usageRelative } = loadState<{
	groups: string[]
	teams: { id: string, displayName: string, link?: string }[]
	quota: number
	totalSpace: string
	usage: string
	usageRelative: number
}>('settings', 'personalInfoParameters')

const quotaText = quota === SPACE_UNLIMITED
	? t('settings', 'You are using {s}{usage}{/s}', { usage, s: '<strong>', '/s': '</strong>' }, undefined, { escape: false })
	: t(
			'settings',
			'You are using {s}{usage}{/s} of {s}{totalSpace}{/s} ({s}{usageRelative}%{/s})',
			{ usage, totalSpace, usageRelative, s: '<strong>', '/s': '</strong>' },
			undefined,
			{ escape: false },
		)
</script>

<template>
	<section :class="$style.detailsSection">
		<div v-if="teams.length" :class="$style.block">
			<h3 :class="$style.heading">
				{{ t('settings', 'Your teams') }}
			</h3>
			<NcFormBox :class="$style.box">
				<NcFormBoxButton
					v-for="team in teams"
					:key="team.id"
					:label="team.displayName"
					:href="team.link || undefined"
					:target="team.link ? '_blank' : undefined">
					<template #icon>
						<NcIconSvgWrapper :path="mdiOpenInNew" :size="20" />
					</template>
				</NcFormBoxButton>
			</NcFormBox>
		</div>

		<div v-if="groups.length" :class="$style.block">
			<h3 :class="$style.heading">
				{{ t('settings', 'Your groups') }}
			</h3>
			<p :class="$style.secondaryText">
				{{ groups.join(', ') }}
			</p>
		</div>

		<div :class="$style.block">
			<!-- eslint-disable-next-line vue/no-v-html -->
			<p :class="$style.secondaryText" v-html="quotaText" />
			<NcProgressBar
				size="medium"
				:value="usageRelative"
				:error="usageRelative > 80" />
		</div>
	</section>
</template>

<style module>
.detailsSection {
	display: flex;
	flex-direction: column;
	gap: calc(var(--default-grid-baseline) * 4);
	padding: 6px 0;
}

.block {
	display: flex;
	flex-direction: column;
	gap: 6px;
}

.heading {
	margin: 0;
	font-size: 16px;
	font-weight: bold;
}

.box {
	margin-inline-end: 52px;
}

.secondaryText {
	margin: 0;
	color: var(--color-text-maxcontrast);
}
</style>
