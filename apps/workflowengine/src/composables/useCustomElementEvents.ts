/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { Ref } from 'vue'

import { onScopeDispose, watch } from 'vue'

type CustomElementHandlers = Record<string, (event: CustomEvent<unknown[]>) => void>

/**
 * Listen to the events a custom element dispatches.
 *
 * A template listener would not do: Vue takes every `onUpdate:*` listener for
 * the v-model listener of a component and never attaches it to a DOM element,
 * so `update:model-value` of a custom element would never arrive.
 *
 * @param element - The custom element, as long as it is rendered
 * @param handlers - The handler to run per event name
 */
export function useCustomElementEvents(
	element: Ref<Element | null | undefined>,
	handlers: CustomElementHandlers,
): void {
	const entries = Object.entries(handlers) as [string, EventListener][]

	function subscribe(target: Element | null | undefined): void {
		entries.forEach(([name, handler]) => target?.addEventListener(name, handler))
	}

	function unsubscribe(target: Element | null | undefined): void {
		entries.forEach(([name, handler]) => target?.removeEventListener(name, handler))
	}

	watch(element, (current, previous) => {
		unsubscribe(previous)
		subscribe(current)
	}, { immediate: true, flush: 'post' })

	onScopeDispose(() => unsubscribe(element.value))
}
