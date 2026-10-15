<!--
  - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { mdiStar, mdiStarShootingOutline } from '@mdi/js'
import { translate as t } from '@nextcloud/l10n'
import { computed } from 'vue'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'

const props = defineProps<{
	/**
	 * The app level
	 */
	level?: number
}>()

const isSupported = computed(() => props.level === 300)
const isFeatured = computed(() => props.level === 200)
const badgeIcon = computed(() => isSupported.value
	? mdiStarShootingOutline
	: mdiStar)
const badgeText = computed(() => isSupported.value ? t('appstore', 'Supported') : t('appstore', 'Featured'))
const badgeTitle = computed(() => isSupported.value
	? t('appstore', 'This app is supported via your current Nextcloud subscription.')
	: t('appstore', 'Featured apps are developed by and within the community. They offer central functionality and are ready for production use.'))
</script>

<template>
	<span
		v-if="isSupported || isFeatured"
		:class="[ $style.appLevelBadge, { [$style.appLevelBadge__supported]: isSupported } ]"
		:title="badgeTitle">
		<NcIconSvgWrapper :path="badgeIcon" :size="20" inline />
		{{ badgeText }}
	</span>
</template>

<style module>
.appLevelBadge {
	color: var(--color-text-maxcontrast);
	background-color: transparent;
	border: 1px solid var(--color-text-maxcontrast);
	border-radius: var(--border-radius);

	display: flex;
	flex-direction: row;
	gap: var(--default-grid-baseline);
	padding: 3px 6px;
	width: fit-content;
}

.appLevelBadge__supported {
	background-color: var(--color-success);
	border-color: var(--color-border-success);
	color: var(--color-success-text);
}
</style>
