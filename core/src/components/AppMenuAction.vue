<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { INavigationEntry } from '../types/navigation.d.ts'

import { emit as emitOnEventBus } from '@nextcloud/event-bus'
import { computed } from 'vue'
import AppActionIcon from './AppActionIcon.vue'
import AppMenuItem from './AppMenuItem.vue'

const props = withDefaults(defineProps<{
	/** The navigation action to render (INavigationManager::TYPE_ACTION entry). */
	action: INavigationEntry
	/** Render as a one-line list row instead of a tile (used in the overflow menu). */
	compact?: boolean
	/** Roving-tabindex value, see AppMenuItem. */
	tabindex?: number
}>(), {
	tabindex: -1,
})

const emit = defineEmits<{
	/** Emitted after the action was activated, so the app menu can close. */
	(event: 'click', mouseEvent: MouseEvent): void
}>()

// `href` is required by NavigationManager, so handler-only actions carry an
// empty string or "#" as placeholder.
const hasLink = computed(() => Boolean(props.action.href) && props.action.href !== '#')

// AppMenuItem picks the element by `href`, so the placeholder has to be
// stripped for handler-only actions to render as a <button>.
const entry = computed<INavigationEntry>(() => hasLink.value
	? props.action
	: { ...props.action, href: '' })

/**
 * Actions with a link simply navigate. Actions without one are implemented in
 * JavaScript by the registering app, which listens on the event bus.
 *
 * @param mouseEvent - The click event of the underlying item
 */
function onClick(mouseEvent: MouseEvent): void {
	if (!hasLink.value) {
		emitOnEventBus('core:navigation:action', props.action)
	}
	emit('click', mouseEvent)
}
</script>

<template>
	<AppMenuItem
		:app="entry"
		:compact="compact"
		:tabindex="tabindex"
		@click="onClick">
		<template #icon>
			<AppActionIcon :icon="action.icon" :color="action.color" />
		</template>
	</AppMenuItem>
</template>
