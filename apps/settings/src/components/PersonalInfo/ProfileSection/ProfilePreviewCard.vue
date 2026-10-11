<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="preview-card">
		<div class="preview-card__banner" />
		<div class="preview-card__body" />
		<NcAvatar
			class="preview-card__avatar"
			:user="userId"
			:size="106"
			:disable-menu="true"
			:disable-tooltip="true" />
		<div class="preview-card__text">
			<span class="preview-card__name">{{ displayName }}</span>
			<span class="preview-card__secondary">{{ secondary }}</span>
		</div>
	</div>
</template>

<script>
import NcAvatar from '@nextcloud/vue/components/NcAvatar'

export default {
	name: 'ProfilePreviewCard',

	components: {
		NcAvatar,
	},

	props: {
		displayName: {
			type: String,
			required: true,
		},

		statusMessage: {
			type: String,
			default: '',
		},

		userId: {
			type: String,
			required: true,
		},
	},

	computed: {
		secondary() {
			return this.statusMessage
		},
	},
}
</script>

<style lang="scss" scoped>
.preview-card {
	position: relative;
	height: 180px;
	border: 2px solid var(--color-border);
	border-radius: var(--border-radius-large);
	overflow: hidden;
	background-color: var(--color-main-background);

	&__banner {
		height: 60%;
		background-color: var(--color-primary-element-light);
	}

	&__body {
		height: 40%;
	}

	&__avatar {
		position: absolute !important;
		top: 23px;
		inset-inline-start: 20px;
		border-radius: 50%;
		// !important is required to override NcAvatar's own box-shadow
		box-shadow: 0 0 0 5px var(--color-main-background) !important;
	}

	&__text {
		position: absolute;
		top: 52px;
		inset-inline-start: 146px;
		inset-inline-end: 16px;
		display: flex;
		flex-direction: column;
		min-width: 0;
	}

	&__name {
		font-weight: bold;
		font-size: 20px;
		line-height: 1.3;
		color: var(--color-main-text);
		overflow: hidden;
		text-overflow: ellipsis;
	}

	&__secondary {
		color: var(--color-text-maxcontrast);
		font-size: 14px;
		overflow: hidden;
		text-overflow: ellipsis;
	}
}
</style>
