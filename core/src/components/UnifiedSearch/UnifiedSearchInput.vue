<!--
 - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<search
		class="unified-search-input"
		:class="{ 'unified-search-input--mobile': isSmallMobile }">
		<NcHeaderButton
			v-if="isSmallMobile"
			id="unified-search-trigger"
			:ariaLabel="placeholderText"
			aria-haspopup="dialog"
			:aria-expanded="expanded ? 'true' : 'false'"
			@click="$emit('click', $event)">
			<template #icon>
				<IconMagnify :size="20" />
			</template>
		</NcHeaderButton>
		<div
			v-else
			class="unified-search-input__field"
			:class="{ 'unified-search-input__field--active': isActive }">
			<!-- Decorative overlay: an input can't group an icon with its own placeholder,
				so we paint the magnifier + placeholder on top and let clicks fall through.
				It slides to the leading edge on focus (see styles). -->
			<div
				class="unified-search-input__resting"
				:class="{ 'unified-search-input__resting--filled': query.length > 0 }"
				aria-hidden="true">
				<IconMagnify :size="20" />
				<span class="unified-search-input__label">{{ placeholderText }}</span>
			</div>
			<input
				ref="inputRef"
				type="text"
				role="combobox"
				class="unified-search-input__input"
				aria-autocomplete="list"
				:aria-expanded="expanded ? 'true' : 'false'"
				:aria-controls="expanded ? resultsContainerId : undefined"
				:aria-activedescendant="expanded ? (activeDescendantId || undefined) : undefined"
				:aria-label="placeholderText"
				:value="query"
				@focus="isFocused = true"
				@blur="isFocused = false"
				@input="onInput"
				@keydown="onKeyDown">
			<NcButton
				v-if="query.length > 0"
				variant="tertiary-no-background"
				class="unified-search-input__clear"
				:aria-label="t('core', 'Clear search')"
				@click="clearQuery">
				<template #icon>
					<IconClose :size="20" />
				</template>
			</NcButton>
			<!-- Decorative focus-shortcut hint, shown only while resting (unfocused +
				empty) so it never collides with the clear button. -->
			<span
				v-if="!isActive"
				class="unified-search-input__shortcut"
				aria-hidden="true">
				<NcKbd symbol="Control" />
				<NcKbd symbol="K" />
			</span>
		</div>
	</search>
</template>

<script setup lang="ts">
import { t } from '@nextcloud/l10n'
import { useIsSmallMobile } from '@nextcloud/vue/composables/useIsMobile'
import { computed, ref } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcHeaderButton from '@nextcloud/vue/components/NcHeaderButton'
import NcKbd from '@nextcloud/vue/components/NcKbd'
import IconClose from 'vue-material-design-icons/Close.vue'
import IconMagnify from 'vue-material-design-icons/Magnify.vue'

/**
 * The unified-search input that lives in the header.
 *
 * Implemented as a plain <input> rather than NcTextField/NcInputField: those
 * assume a light form background and a floating label, which clashes with the
 * themed header and the resting "button" look and would need heavy overrides of
 * their internals. A custom input also lets us own the combobox semantics the
 * results popover needs. On narrow viewports it collapses to an NcHeaderButton
 * to match the other header items.
 */

const props = defineProps<{
	/** Whether the popover the input controls is open. Bound to aria-expanded. */
	expanded?: boolean
	/** Id of the active result row, for aria-activedescendant. Empty when none. */
	activeDescendantId?: string
	query: string
}>()

const emit = defineEmits<{
	click: [mouseEvent: MouseEvent]
	'update:query': [query: string]
	/** Move the result selection while focus stays in the input. */
	navigate: [direction: 'next' | 'prev' | 'first' | 'last']
	/** Open the currently selected result (Enter). */
	activate: []
}>()

const isSmallMobile = useIsSmallMobile()
const placeholderText = t('core', 'Apps, files, messages, and more')

// Id of the results popover the input controls (aria-controls). Kept in sync with
// the id on UnifiedSearchModal's panel.
const resultsContainerId = 'unified-search-results'

// Maps the navigation keys to a selection direction. Keys not listed are left
// alone. Home/End are deliberately absent: in the combobox pattern they move the
// textbox caret, not the result selection.
const directionByKey: Record<string, 'next' | 'prev'> = {
	ArrowDown: 'next',
	ArrowUp: 'prev',
}

const inputRef = ref<HTMLInputElement>()
const isFocused = ref(false)

/** Active = focused or holding a query; drives the resting-vs-active styling. */
const isActive = computed(() => isFocused.value || props.query.length > 0)

/**
 * Relay the typed value upward.
 *
 * @param event The input event
 */
function onInput(event: Event) {
	emit('update:query', (event.target as HTMLInputElement).value)
}

/** Clear the query and keep focus in the input. */
function clearQuery() {
	emit('update:query', '')
	inputRef.value?.focus()
}

/**
 * Combobox key handling. While the popover is open arrows move the selection via
 * aria-activedescendant and Enter opens the active row, with focus staying in the
 * input; otherwise the input keeps normal typing and caret movement (Home/End),
 * except Escape, which drops focus like a native find bar.
 *
 * @param event the keydown event
 */
function onKeyDown(event: KeyboardEvent) {
	// Never intercept keys mid-IME-composition (CJK etc.): the popover is already
	// open, so Enter/Arrows must reach the input to commit or edit the composition.
	if (event.isComposing) {
		return
	}
	// Escape with the popover closed drops focus, like a native find bar. While
	// open, the modal owns Escape, so only the closed case is handled here.
	if (event.key === 'Escape' && !props.expanded) {
		inputRef.value?.blur()
		return
	}
	if (!props.expanded) {
		return
	}
	const direction = directionByKey[event.key]
	if (direction) {
		event.preventDefault()
		emit('navigate', direction)
	} else if (event.key === 'Enter') {
		event.preventDefault()
		emit('activate')
	}
}

/** Focus the text field. Used by the global shortcut; a no-op on the mobile button. */
function focus() {
	inputRef.value?.focus()
}

defineExpose({ focus })
</script>

<style lang="scss" scoped>
.unified-search-input {
	// Paints above the modal root (z-index: 50) so the header input stays clickable
	// over the scrim while the popover is open. Keep 51 one above that value.
	position: relative;
	z-index: 51;

	&:not(.unified-search-input--mobile) {
		display: flex;
		align-items: center;
		width: clamp(200px, 35vw, 600px);
		max-width: calc(100% - 32px);
	}

	&--mobile {
		display: contents;
	}

	&__field {
		--resting-background: rgba(0, 0, 0, 0.15);
		--resting-background-hover: rgba(0, 0, 0, 0.22);
		// Shared geometry: the resting group and the input's leading padding read the
		// same tokens so the placeholder and the typed value line up.
		--search-icon-pad: 12px;
		--search-icon-size: 20px;
		--search-icon-gap: 8px;
		// One shared timing for every focus transition (background, the icon/label
		// slide, the recolour) so they move together. easeOutQuart = soft landing.
		--search-anim-duration: 240ms;
		--search-anim-easing: cubic-bezier(0.22, 1, 0.36, 1);
		position: relative;
		// Query container so the resting group can centre itself with cqi units
		container-type: inline-size;
		display: flex;
		align-items: center;
		// Match the default clickable area so the inner <input> (which the global
		// input reset forces to that height) fills the field without an override.
		height: var(--default-clickable-area);
		width: 100%;
		border-radius: var(--border-radius-element, 8px);
		box-shadow: inset 0 2px 0 rgba(0, 0, 0, 0.12);
		// Resting: subdued "button" look that sits on the themed header
		background-color: var(--resting-background);
		-webkit-backdrop-filter: var(--filter-background-blur);
		backdrop-filter: var(--filter-background-blur);
		// Blue tint -> white surface on the shared timing, in step with the slide.
		transition:
			background-color var(--search-anim-duration) var(--search-anim-easing),
			box-shadow var(--search-anim-duration) var(--search-anim-easing);

		&:hover:not(.unified-search-input__field--active) {
			background-color: var(--resting-background-hover);
		}

		// Active: real input surface once focused or filled
		&--active {
			background-color: var(--color-main-background);
			box-shadow: none;
		}
	}

	// Anchored at the leading edge and translated to the centre while at rest; on
	// focus (--active) the translate goes to 0 and it slides into place. Centre offset
	// is pure CSS: half the field (50cqi) minus half the group (50%) minus the pad, so
	// it self-corrects for any placeholder length or field width.
	&__resting {
		--slide-sign: 1;
		position: absolute;
		inset-block: 0;
		inset-inline-start: var(--search-icon-pad);
		max-width: calc(100% - 2 * var(--search-icon-pad));
		display: flex;
		align-items: center;
		gap: var(--search-icon-gap);
		pointer-events: none;
		color: color-mix(in srgb, var(--color-background-plain-text) 70%, var(--color-background-plain));
		transform: translateX(calc(var(--slide-sign) * (50cqi - 50% - var(--search-icon-pad))));
		transition:
			transform var(--search-anim-duration) var(--search-anim-easing),
			color var(--search-anim-duration) var(--search-anim-easing);

		.unified-search-input__field--active & {
			transform: translateX(0);
			color: var(--color-text-maxcontrast);
		}
	}

	// Placeholder text inside the resting group. Ellipsised, and hidden once typing
	// starts so it doesn't overlap the value. Scoped to the label class so the sibling
	// magnifier (also rendered as a <span>) stays visible.
	&__label {
		overflow: hidden;
		white-space: nowrap;
		text-overflow: ellipsis;
		transition: opacity var(--search-anim-duration) var(--search-anim-easing);
	}

	&__resting--filled &__label {
		opacity: 0;
	}

	// The material-design icon <svg> is inline (baseline-aligned), which leaves a
	// descender gap and makes the glyph sit high even when its box is centred.
	// Render it as a block so it fills its box, then nudge 1px down to sit on the
	// text's optical centre (a geometrically centred glyph reads slightly high).
	&__resting :deep(.material-design-icon__svg) {
		display: block;
		transform: translateY(1px);
	}

	// Only visible once active (at rest it's empty and covered by the overlay),
	// so it's styled for the active/white surface throughout.
	&__input {
		flex: 1;
		min-width: 0;
		height: 100%;
		margin: 0;
		// Leading space so the placeholder/value starts one gap past the magnifier,
		// matching the resting group exactly. Trailing padding mirrors the leading pad.
		padding-inline: calc(var(--search-icon-pad) + var(--search-icon-size) + var(--search-icon-gap)) var(--search-icon-pad);
		// Opt out of NC's global input chrome (core/css/inputs.scss adds a border,
		// radius and focus box-shadow to any text input not in its exclusion list).
		// !important because that global focus rule outweighs a scoped class.
		border: none !important;
		border-radius: 0 !important;
		box-shadow: none !important;
		background-color: transparent;
		color: var(--color-main-text);
		font-size: var(--default-font-size);

		&::placeholder {
			opacity: 1;
			color: var(--color-text-maxcontrast);
		}

		&:focus-visible {
			outline: none;
		}
	}

	&__clear {
		flex-shrink: 0;
		margin-inline-end: 2px;
	}

	// Pinned to the trailing edge, overlaid on the input (pointer-events: none so a
	// click there still focuses the field).
	&__shortcut {
		position: absolute;
		inset-inline-end: var(--default-grid-baseline);
		top: 50%;
		transform: translateY(-50%);
		display: flex;
		pointer-events: none;

		// On a narrow field the centred placeholder runs under the hint, so drop it
		// below a usable width. Keyed to the field's own inline-size (its container),
		// not the viewport, so it holds however crowded the header gets.
		@container (max-width: 400px) {
			display: none;
		}

		:deep(kbd) {
			min-width: 12px;
			height: 12px;
			padding-inline: 5px;
			border: 1px solid color-mix(in srgb, var(--color-background-plain-text) 20%, transparent);
			border-block-end-width: 2px;
			border-radius: var(--border-radius-small, 4px);
			color: color-mix(in srgb, var(--color-background-plain-text) 70%, var(--color-background-plain));
			font-size: 13px;
		}
	}
}

// On dark themes the plain overlay is nearly invisible on the header, so tint
// the resting background with the primary colour instead.
[data-theme-dark] .unified-search-input__field,
[data-theme-dark-highcontrast] .unified-search-input__field {
	--resting-background: color-mix(in srgb, var(--color-primary-element) 16%, transparent);
	--resting-background-hover: color-mix(in srgb, var(--color-primary-element) 22%, transparent);
}

// translateX is physical, so flip the resting slide under RTL to keep it moving
// toward the leading (right) edge.
[dir=rtl] .unified-search-input__resting {
	--slide-sign: -1;
}

// Respect reduced-motion: keep the end states but drop the slide/fade so nothing
// animates on focus.
@media (prefers-reduced-motion: reduce) {
	.unified-search-input__resting,
	.unified-search-input__resting span {
		transition: none;
	}
}

// Mobile: NcHeaderButton styling to match the other header items
.unified-search-input--mobile :deep(.header-menu) {
	height: var(--default-clickable-area);
}

.unified-search-input--mobile :deep(.header-menu__trigger) {
	--button-size: var(--default-clickable-area) !important;
	height: var(--default-clickable-area) !important;
}

.unified-search-input--mobile :deep(.button-vue) {
	--color-main-text: var(--color-background-plain-text);
	color: var(--color-background-plain-text);
	border-radius: var(--border-radius-element) !important;

	&:hover:not(:disabled) {
		background-color: rgba(0, 0, 0, 0.1) !important;
	}

	&:active:not(:disabled) {
		background-color: rgba(0, 0, 0, 0.15) !important;
	}

	&:focus-visible {
		background-color: rgba(0, 0, 0, 0.1) !important;
		outline: none !important;
		box-shadow: inset 0 0 0 2px var(--color-background-plain-text) !important;
	}
}
</style>
