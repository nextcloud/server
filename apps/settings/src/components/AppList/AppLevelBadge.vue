<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<span v-if="isSupported || isFeatured"
		class="app-level-badge"
		:class="{ 'app-level-badge--supported': isSupported }"
		:title="badgeTitle">
		<NcIconSvgWrapper :path="badgeIcon" :size="20" inline />
		{{ badgeText }}
	</span>
</template>

<script setup lang="ts">
import NcIconSvgWrapper from '@nextcloud/vue/dist/Components/NcIconSvgWrapper.js'

import { mdiCheck, mdiStarShooting } from '@mdi/js'
import { translate as t } from '@nextcloud/l10n'
import { computed } from 'vue'

const props = defineProps<{
	/**
	 * The app level
	 */
	level?: number
}>()

const isSupported = computed(() => props.level === 300)
const isFeatured = computed(() => props.level === 200)
const badgeIcon = computed(() => isSupported.value ? mdiStarShooting : mdiCheck)
const badgeText = computed(() => isSupported.value ? t('settings', 'Supported') : t('settings', 'Featured'))
const badgeTitle = computed(() => isSupported.value
	? t('settings', 'This app is supported via your current Nextcloud subscription.')
	: t('settings', 'Featured apps are developed by and within the community. They offer central functionality and are ready for production use.'))
</script>

<style scoped lang="scss">
.app-level-badge {
	color: var(--color-text-maxcontrast);
	background-color: transparent;
	border: 1px solid var(--color-text-maxcontrast);
	border-radius: var(--border-radius);

	display: flex;
	flex-direction: row;
	gap: 6px;
	padding: 3px 6px;
	width: fit-content;

	&--supported {
		border-color: var(--color-success);
		color: var(--color-success);
	}
}
</style>
