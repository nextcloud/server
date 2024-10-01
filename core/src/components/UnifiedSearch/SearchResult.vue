<!--
 - SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<NcListItem class="result-items__item"
		:name="title"
		:bold="false"
		:href="resourceUrl"
		target="_self">
		<template #icon>
			<div aria-hidden="true"
				class="result-items__item-icon"
				:class="{
					'result-items__item-icon--rounded': rounded,
					'result-items__item-icon--no-preview': !isValidIconOrPreviewUrl(thumbnailUrl),
					'result-items__item-icon--with-thumbnail': isValidIconOrPreviewUrl(thumbnailUrl),
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
@use "sass:math";
$clickable-area: 44px;
$margin: 10px;

.result-items {
    &__item {

    ::v-deep a {
            border-radius: 12px;
            border: 2px solid transparent;
            border-radius: var(--border-radius-large) !important;

            &--focused {
                background-color: var(--color-background-hover);
            }

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

        &-icon {
            overflow: hidden;
            width: $clickable-area;
            height: $clickable-area;
            border-radius: var(--border-radius);
            background-repeat: no-repeat;
            background-position: center center;
            background-size: 32px;

            &--rounded {
                border-radius: math.div($clickable-area, 2);
            }

            &--no-preview {
                background-size: 32px;
            }

            &--with-thumbnail {
                background-size: cover;
            }

            &--with-thumbnail:not(&--rounded) {
                // compensate for border
                max-width: $clickable-area - 2px;
                max-height: $clickable-area - 2px;
                border: 1px solid var(--color-border);
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
}
</style>
