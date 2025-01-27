<!--
 - SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<NcListItem class="result-item"
		:name="title"
		:bold="false"
		:href="resourceUrl"
		target="_self">
		<template #icon>
			<div aria-hidden="true"
				class="result-item__icon"
				:class="{
					'result-item__icon--rounded': rounded,
					'result-item__icon--no-preview': !isValidIconOrPreviewUrl(thumbnailUrl),
					'result-item__icon--with-thumbnail': isValidIconOrPreviewUrl(thumbnailUrl),
					[icon]: !isValidIconOrPreviewUrl(icon),
				}"
				:style="{
					backgroundImage: isValidIconOrPreviewUrl(icon) ? `url(${icon})` : '',
				}">
				<img v-if="isValidIconOrPreviewUrl(thumbnailUrl) && !thumbnailHasError"
					:src="thumbnailUrl"
					@error="thumbnailErrorHandler">
			</div>
		</template>
		<template #subname>
			{{ subline }}
		</template>
	</NcListItem>
</template>

<script>
import NcListItem from '@nextcloud/vue/dist/Components/NcListItem.js'

export default {
	name: 'SearchResult',
	components: {
		NcListItem,
	},
	props: {
		thumbnailUrl: {
			type: String,
			default: null,
		},
		title: {
			type: String,
			required: true,
		},
		subline: {
			type: String,
			default: null,
		},
		resourceUrl: {
			type: String,
			default: null,
		},
		icon: {
			type: String,
			default: '',
		},
		rounded: {
			type: Boolean,
			default: false,
		},
		query: {
			type: String,
			default: '',
		},

		/**
		 * Only used for the first result as a visual feedback
		 * so we can keep the search input focused but pressing
		 * enter still opens the first result
		 */
		focused: {
			type: Boolean,
			default: false,
		},
	},
	data() {
		return {
			thumbnailHasError: false,
		}
	},
	watch: {
		thumbnailUrl() {
			this.thumbnailHasError = false
		},
	},
	methods: {
		isValidIconOrPreviewUrl(url) {
			return /^https?:\/\//.test(url) || url.startsWith('/')
		},
		thumbnailErrorHandler() {
			this.thumbnailHasError = true
		},
	},
}
</script>

<style lang="scss" scoped>
.result-item {
	:deep(a) {
		border: 2px solid transparent;
		border-radius: var(--border-radius-large) !important;

		&:active,
		&:hover,
		&:focus {
			background-color: var(--color-background-hover);
			border: 2px solid var(--color-border-maxcontrast);
		}

		* {
			cursor: pointer;
		}
	}

	&__icon {
		overflow: hidden;
		width: var(--default-clickable-area);
		height: var(--default-clickable-area);
		border-radius: var(--border-radius);
		background-repeat: no-repeat;
		background-position: center center;
		background-size: 32px;

		&--rounded {
			border-radius: calc(var(--default-clickable-area) / 2);
		}

		&--no-preview {
			background-size: 32px;
		}

		&--with-thumbnail {
			background-size: cover;
		}

		&--with-thumbnail:not(#{&}--rounded) {
			border: 1px solid var(--color-border);
			// compensate for border
			max-height: calc(var(--default-clickable-area) - 2px);
			max-width: calc(var(--default-clickable-area) - 2px);
		}

		img {
			// Make sure to keep ratio
			width: 100%;
			height: 100%;

			object-fit: cover;
			object-position: center;
		}
	}
}
</style>
