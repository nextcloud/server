<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="settings-column">
		<slot />
	</div>
</template>

<style>
#content:has(#app-navigation-vue[aria-hidden="true"]) .settings-column {
	--settings-column-inset: var(--body-container-margin);
}
</style>

<style lang="scss" scoped>
.settings-column {
	// Wide enough for the per-row action icons to sit outside the field boxes.
	--settings-column-gutter: calc(var(--default-clickable-area) + 2 * var(--default-grid-baseline));
	--settings-column-width: calc(700px + 2 * var(--settings-column-gutter));
	--settings-column-inset: calc(var(--body-container-margin) + var(--navigation-width));
	--settings-column-margin: max(0px, calc((100vw - var(--settings-column-width)) / 2 - var(--settings-column-inset)));
	box-sizing: border-box;
	width: 100%;
	max-width: var(--settings-column-width);
	// Starts the fields where the header search field starts. Centring would
	// centre on the app content, which the navigation pushes off the viewport
	// centre the search sits on.
	margin-inline: var(--settings-column-margin) auto;
	padding-inline: var(--settings-column-gutter);

	// The section's own inline margin and width cap would inset the fields from
	// the edge this column aligns to.
	:deep(.settings-section) {
		width: 100%;
		margin-inline: 0;
		padding-inline: 0;
	}
}

// The navigation overlays the content here, so there is no offset to undo.
@media only screen and (width < 1024px) {
	.settings-column {
		margin-inline: auto;
	}
}
</style>
