<!--
 - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
 -->

<template>
	<span class="app-menu-icon"
		role="img"
		:aria-hidden="ariaHidden"
		:aria-label="ariaLabel">
		<img class="app-menu-icon__icon" :src="app.icon" alt="">
		<IconDot v-if="app.unread" class="app-menu-icon__unread" :size="10" />
	</span>
</template>

<script setup lang="ts">
import type { INavigationEntry } from '../types/navigation'
import { n } from '@nextcloud/l10n'
import { computed } from 'vue'

import IconDot from 'vue-material-design-icons/Circle.vue'

const props = defineProps<{
	app: INavigationEntry
}>()

const ariaHidden = computed(() => String(props.app.unread > 0))

const ariaLabel = computed(() => {
	if (ariaHidden.value === 'true') {
		return ''
	}
	return props.app.name
		+ (props.app.unread > 0 ? ` (${n('core', '{count} notification', '{count} notifications', props.app.unread, { count: props.app.unread })})` : '')
})
</script>

<style scoped lang="scss">
$icon-size: 20px;
$unread-indicator-size: 10px;

.app-menu-icon {
	box-sizing: border-box;
	position: relative;

	height: $icon-size;
	width: $icon-size;

	&__icon {
		transition: margin 0.1s ease-in-out;
		height: $icon-size;
		width: $icon-size;
		filter: var(--background-image-invert-if-bright);
	}

	&__unread {
		color: var(--color-error);
		position: absolute;
		// Align the dot to the top right corner of the icon
		inset-block-end: calc($icon-size + ($unread-indicator-size / -2));
		inset-inline-end: calc($unread-indicator-size / -2);
		transition: all 0.1s ease-in-out;
	}
}
</style>
