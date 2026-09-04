/*
 * SPDX-FileCopyrightText: Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { MaybeRefOrGetter } from '@vueuse/core'
import type { Component } from 'vue'
import type { IRichObjectParameter, IRichObjectParameters } from '../settings-types.ts'

import { toValue } from '@vueuse/core'
import { computed } from 'vue'
import NcUserBubble from '@nextcloud/vue/components/NcUserBubble'
import UnknownArgument from '../components/RichArguments/UnknownArgument.vue'

export interface IRichArgument {
	component: Component
	props: Record<string, unknown>
}

/**
 * Map an collection of rich text objects to rich arguments for the RichText component
 *
 * @param richObjects The rich text object
 */
export function mapRichObjectsToRichArguments(richObjects: IRichObjectParameters) {
	const args: Record<string, IRichArgument | string> = {}

	for (const richObjectName in richObjects) {
		args[richObjectName] = mapRichObjectToRichArgument(richObjects[richObjectName])
	}

	return args
}

/**
 * Map rich text object to rich argument for the RichText component
 *
 * @param richObject - The rich text object
 */
export function mapRichObjectToRichArgument(richObject: IRichObjectParameter): IRichArgument | string {
	switch (richObject.type) {
		case 'user':
			return {
				component: NcUserBubble as Component,
				props: {
					displayName: richObject.name,
					user: richObject.id,
					url: richObject.link,
				},
			}
		case 'group':
			return {
				component: NcUserBubble as Component,
				props: {
					avatarImage: 'icon-group',
					displayName: richObject.name,
					url: richObject.link,
					primary: true,
				},
			}
		default:
			return {
				component: UnknownArgument,
				props: richObject,
			}
	}
}

/**
 * Reactively map rich objects to rich arguments for use with NcRichText
 *
 * @param objects Map of RichObjects
 */
export function useRichArguments(objects: MaybeRefOrGetter<IRichObjectParameters>) {
	return computed(() => mapRichObjectsToRichArguments(toValue(objects)))
}
