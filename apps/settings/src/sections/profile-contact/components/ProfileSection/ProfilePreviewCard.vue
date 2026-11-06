<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import NcAvatar from '@nextcloud/vue/components/NcAvatar'

withDefaults(defineProps<{
	/** Display name of the user */
	displayName: string
	/** Status message shown below the name */
	statusMessage?: string
	/** User id for the avatar */
	userId: string
}>(), {
	statusMessage: '',
})
</script>

<template>
	<div :class="$style.previewCard">
		<div :class="$style.banner" />
		<div :class="$style.body" />
		<NcAvatar
			:class="$style.avatar"
			:user="userId"
			:size="106"
			disableMenu
			disableTooltip />
		<div :class="$style.text">
			<span :class="$style.name">{{ displayName }}</span>
			<span :class="$style.secondary">{{ statusMessage }}</span>
		</div>
	</div>
</template>

<style module lang="scss">
.previewCard {
	position: relative;
	height: 180px;
	border: 2px solid var(--color-border);
	border-radius: var(--border-radius-large);
	overflow: hidden;
	background-color: var(--color-main-background);
}

.banner {
	height: 60%;
	background-color: var(--color-primary-element-light);
}

.body {
	height: 40%;
}

.avatar {
	position: absolute !important;
	top: 23px;
	inset-inline-start: 20px;
	border-radius: 50%;
	// !important is required to override NcAvatar's own box-shadow
	box-shadow: 0 0 0 5px var(--color-main-background) !important;
}

.text {
	position: absolute;
	top: 52px;
	inset-inline-start: 146px;
	inset-inline-end: 16px;
	display: flex;
	flex-direction: column;
	min-width: 0;
}

.name {
	font-weight: bold;
	font-size: 20px;
	line-height: 1.3;
	color: var(--color-main-text);
	overflow: hidden;
	text-overflow: ellipsis;
}

.secondary {
	color: var(--color-text-maxcontrast);
	font-size: 14px;
	overflow: hidden;
	text-overflow: ellipsis;
}
</style>
