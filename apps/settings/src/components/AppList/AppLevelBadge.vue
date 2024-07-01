<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<span v-if="isSupported"
		class="app-level-badge"
		:class="{ 'app-level-badge--supported': isSupported }"
		:title="badgeTitle">
		<NcIconSvgWrapper :path="badgeIcon" :size="20" inline />
		{{ badgeText }}
	</span>
</template>

<script setup lang="ts">
import NcIconSvgWrapper from '@nextcloud/vue/dist/Components/NcIconSvgWrapper.js'

import { mdiStarShooting } from '@mdi/js'
import { translate as t } from '@nextcloud/l10n'
import { computed } from 'vue'

const props = defineProps<{
	/**
	 * The app level
	 */
	level?: number
}>()

const isSupported = computed(() => props.level === 300)
const badgeIcon = computed(() => isSupported.value ? mdiStarShooting : '')
const badgeText = computed(() => isSupported.value ? t('settings', 'Supported') : '')
const badgeTitle = computed(() => isSupported.value ? t('settings', 'This app is supported via your current Nextcloud subscription.') : '')
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
