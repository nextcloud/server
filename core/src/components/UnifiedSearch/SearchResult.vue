<!--
 - SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<script setup lang="ts">
import { computed, ref, watch } from 'vue'
import NcListItem from '@nextcloud/vue/components/NcListItem'
import AppIcon from '../AppIcon.vue'

const props = withDefaults(defineProps<{
	title: string
	thumbnailUrl?: string | null
	subline?: string | null
	resourceUrl?: string | null
	icon?: string
	rounded?: boolean
	query?: string
	/**
	 * DOM id set on the option element (the <li>). The combobox input points
	 * aria-activedescendant at this id to name the active row while focus stays
	 * in the input.
	 */
	elementId?: string
	/**
	 * Whether this row is the selected result. Highlights it (via NcListItem's
	 * active state) so the auto-selected first result and arrow navigation are
	 * visible while the search input keeps focus.
	 */
	active?: boolean
}>(), {
	thumbnailUrl: null,
	subline: null,
	resourceUrl: null,
	icon: '',
	rounded: false,
	query: '',
	elementId: undefined,
	active: false,
})

const thumbnailHasError = ref(false)

/** A usable thumbnail image (a preview/avatar), not errored. */
const hasThumbnail = computed(() => isValidIconOrPreviewUrl(props.thumbnailUrl) && !thumbnailHasError.value)

/** The icon is a real URL we can put in an <img>, not a legacy CSS class string. */
const iconIsUrl = computed(() => isValidIconOrPreviewUrl(props.icon))

/**
 * App-style icon (bright glyph on a primary circle, like the app menu). Providers
 * flag it by marking the entry rounded with an icon URL and no thumbnail.
 */
const isAppIcon = computed(() => props.rounded && iconIsUrl.value && !hasThumbnail.value)

watch(() => props.thumbnailUrl, () => {
	thumbnailHasError.value = false
})

/**
 * @param url - The url to check
 */
function isValidIconOrPreviewUrl(url: string | null): boolean {
	return !!url && (/^https?:\/\//.test(url) || url.startsWith('/'))
}

/**
 * Fall back to the icon if the thumbnail cannot be loaded.
 */
function thumbnailErrorHandler() {
	thumbnailHasError.value = true
}
</script>

<template>
	<NcListItem
		:id="elementId"
		class="result-item"
		:name="title"
		:bold="false"
		:active="active"
		:href="resourceUrl ?? undefined"
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
					:src="thumbnailUrl ?? undefined"
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

<style lang="scss" scoped>
.result-item {
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
		:deep(.list-item) {
			background-color: var(--color-background-hover);

			// Keyboard selection marker: the pill the left navigation paints on its active
			// entry. It has to hang off .list-item rather than the wrapper, because
			// .list-item is itself positioned and paints the opaque row background, so it
			// would cover a pseudo-element belonging to its parent.
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

			// Mime icons carry their own colours (a red PDF, a green spreadsheet), so the
			// dark-theme invert would recolour them: red comes out cyan. Sized to match the
			// 32px these icons had while they were painted as a background-image.
			&[src*='/filetypes/'] {
				width: 32px;
				height: 32px;
				filter: none;
			}
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
