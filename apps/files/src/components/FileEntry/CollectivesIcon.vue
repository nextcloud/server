<!--
 - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
 -->
<template>
	<span :aria-hidden="!title"
		:aria-label="title"
		class="material-design-icon collectives-icon"
		role="img"
		v-bind="$attrs"
		@click="$emit('click', $event)">
		<svg :fill="fillColor"
			class="material-design-icon__svg"
			:width="size"
			:height="size"
			viewBox="0 0 16 16">
			<path d="M2.9,8.8c0-1.2,0.4-2.4,1.2-3.3L0.3,6c-0.2,0-0.3,0.3-0.1,0.4l2.7,2.6C2.9,9,2.9,8.9,2.9,8.8z" />
			<path d="M8,3.7c0.7,0,1.3,0.1,1.9,0.4L8.2,0.6c-0.1-0.2-0.3-0.2-0.4,0L6.1,4C6.7,3.8,7.3,3.7,8,3.7z" />
			<path d="M3.7,11.5L3,15.2c0,0.2,0.2,0.4,0.4,0.3l3.3-1.7C5.4,13.4,4.4,12.6,3.7,11.5z" />
			<path d="M15.7,6l-3.7-0.5c0.7,0.9,1.2,2,1.2,3.3c0,0.1,0,0.2,0,0.3l2.7-2.6C15.9,6.3,15.9,6.1,15.7,6z" />
			<path d="M12.3,11.5c-0.7,1.1-1.8,1.9-3,2.2l3.3,1.7c0.2,0.1,0.4-0.1,0.4-0.3L12.3,11.5z" />
			<path d="M9.6,10.1c-0.4,0.5-1,0.8-1.6,0.8c-1.1,0-2-0.9-2.1-2C5.9,7.7,6.8,6.7,8,6.7c0.6,0,1.1,0.3,1.5,0.7 c0.1,0.1,0.1,0.1,0.2,0.1h1.4c0.2,0,0.4-0.2,0.3-0.5c-0.7-1.3-2.1-2.2-3.8-2.1C5.8,5,4.3,6.6,4.1,8.5C4,10.8,5.8,12.7,8,12.7 c1.6,0,2.9-0.9,3.5-2.3c0.1-0.2-0.1-0.4-0.3-0.4H9.9C9.8,10,9.7,10,9.6,10.1z" />
		</svg>
	</span>
</template>

<script>
export default {
	name: 'CollectivesIcon',
	props: {
		title: {
			type: String,
			default: '',
		},
		fillColor: {
			type: String,
			default: 'currentColor',
		},
		size: {
			type: Number,
			default: 24,
		},
	},
}
</script>
