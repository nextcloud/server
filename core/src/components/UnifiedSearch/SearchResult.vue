<!--
 - SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<NcListItem
		:id="elementId"
		class="result-item"
		:name="title"
		:bold="false"
		:active="active"
		:href="resourceUrl"
		target="_self">
		<template #icon>
			<AppIcon
				v-if="isAppIcon"
				class="result-item__app-icon"
				:icon="icon" />
			<div
				v-else
				aria-hidden="true"
				class="result-item__icon"
				:class="{
					'result-item__icon--rounded': rounded,
					'result-item__icon--with-thumbnail': hasThumbnail,
					[icon]: !iconIsUrl && !hasThumbnail,
				}">
				<img
					v-if="hasThumbnail"
					:src="thumbnailUrl"
					@error="thumbnailErrorHandler">
				<img
					v-else-if="iconIsUrl"
					class="result-item__icon-img"
					:src="icon"
					alt=""
					aria-hidden="true">
			</div>
		</template>
		<template #subname>
			{{ subline }}
		</template>
	</NcListItem>
</template>

<script>
import NcListItem from '@nextcloud/vue/components/NcListItem'
import AppIcon from '../AppIcon.vue'

export default {
	name: 'SearchResult',
	components: {
		AppIcon,
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
		 * DOM id set on the option element (the <li>). The combobox input points
		 * aria-activedescendant at this id to name the active row while focus stays
		 * in the input.
		 */
		elementId: {
			type: String,
			default: undefined,
		},

		/**
		 * Whether this row is the selected result. Highlights it (via NcListItem's
		 * active state) so the auto-selected first result and arrow navigation are
		 * visible while the search input keeps focus.
		 */
		active: {
			type: Boolean,
			default: false,
		},
	},

	data() {
		return {
			thumbnailHasError: false,
		}
	},

	computed: {
		/** A usable thumbnail image (a preview/avatar), not errored. */
		hasThumbnail() {
			return this.isValidIconOrPreviewUrl(this.thumbnailUrl) && !this.thumbnailHasError
		},

		/** The icon is a real URL we can put in an <img>, not a legacy CSS class string. */
		iconIsUrl() {
			return this.isValidIconOrPreviewUrl(this.icon)
		},

		/**
		 * App-style icon (bright glyph on a primary circle, like the app menu). Providers
		 * flag it by marking the entry rounded with an icon URL and no thumbnail.
		 */
		isAppIcon() {
			return this.rounded && this.iconIsUrl && !this.hasThumbnail
		},
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
	// Positioning context for the selection pill. NcListItem's wrapper already sets this;
	// repeated so the pseudo-element below does not depend on that.
	position: relative;
	padding-inline: 0;

	:deep(a) {
		border: 2px solid transparent;
		border-radius: var(--border-radius-large) !important;

		// Hover/press: neutral gray fill only, no border.
		&:active,
		&:hover {
			background-color: var(--color-background-hover);
		}

		// Plain Tab into a result keeps a visible focus ring (a11y). Normally the combobox
		// keeps focus in the input and drives selection via `active` below.
		&:focus-visible {
			background-color: var(--color-background-hover);
			border-color: var(--color-border-maxcontrast);
		}

		* {
			cursor: pointer;
		}
	}

	// NcListItem's `active` state paints a primary fill, white text and a blue stripe.
	// We want a neutral look: the gray hover fill plus a maxcontrast border, readable text.
	&.list-item__wrapper--active {
		// Keyboard selection marker: the pill the left navigation paints on its active entry.
		&::before {
			content: '';
			position: absolute;
			inset-block: calc(var(--default-grid-baseline) * 2);
			inset-inline-start: 0;
			width: 3px;
			border-radius: var(--border-radius-rounded);
			background-color: var(--color-primary-element);
			// Zeroed by the reduced-motion theme, so no separate media query is needed.
			animation: result-pill-in var(--animation-quick) ease-out;
		}

		:deep(.list-item) {
			background-color: var(--color-background-hover);

			&:hover {
				background-color: var(--color-background-hover);
			}
		}

		// Undo the forced active text colour. Chain through the anchor to outrank
		// NcListItem's own !important rule.
		:deep(.list-item__anchor .list-item-content__name),
		:deep(.list-item__anchor .list-item-content__subname),
		:deep(.list-item__anchor .list-item-content__details),
		:deep(.list-item__anchor .list-item-details__details) {
			color: var(--color-main-text) !important;
		}
	}

	&__icon {
		display: flex;
		align-items: center;
		justify-content: center;
		overflow: hidden;
		width: var(--default-clickable-area);
		height: var(--default-clickable-area);
		border-radius: var(--border-radius);
		margin-inline-start: var(--default-grid-baseline);

		&--rounded {
			border-radius: calc(var(--default-clickable-area) / 2);
		}

		&--with-thumbnail:not(#{&}--rounded) {
			border: 1px solid var(--color-border);
			// compensate for border
			max-height: calc(var(--default-clickable-area) - 2px);
			max-width: calc(var(--default-clickable-area) - 2px);
		}

		// A full-bleed thumbnail (preview or avatar) fills the box.
		&--with-thumbnail img {
			// Make sure to keep ratio
			width: 100%;
			height: 100%;

			object-fit: cover;
			object-position: center;
		}

		// A small monochrome glyph (e.g. a settings section), not a thumbnail.
		&-img {
			width: 20px;
			height: 20px;
			object-fit: contain;
			// Dark monochrome icons invert to light in dark themes.
			filter: var(--background-invert-if-dark);
		}
	}

	// App results reuse the app-menu tile (AppIcon); size its circle to the icon column.
	&__app-icon {
		--app-icon-circle-size: var(--default-clickable-area);
		margin-inline-start: var(--default-grid-baseline);
	}
}

// Grow the pill out of the row's centre line, matching the navigation entry.
@keyframes result-pill-in {
	from {
		transform: scaleY(0);
		opacity: 0;
	}

	to {
		transform: scaleY(1);
		opacity: 1;
	}
}
</style>
