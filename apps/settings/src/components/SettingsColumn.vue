<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="settings-column">
		<slot />
	</div>
</template>

<style lang="scss" scoped>
.settings-column {
	// Room on each side for the per-row action icons (e.g. the privacy control)
	// so they sit outside the field boxes instead of shrinking them. Mirroring
	// the gutter keeps the content box itself centred on the page, in line with
	// the page footer below it. Every content box fills the same width; the
	// icons live in the trailing gutter.
	--settings-column-gutter: calc(var(--default-clickable-area) + 8px);
	box-sizing: border-box;
	width: 100%;
	max-width: calc(var(--settings-column-max-width, 720px) + 2 * var(--settings-column-gutter));
	margin-inline: auto;
	padding-inline: var(--settings-column-gutter);
}
</style>
