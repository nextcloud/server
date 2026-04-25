/*!
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import type { App, ComponentPublicInstance } from 'vue'

import { n, t } from '@nextcloud/l10n'
import { createPinia } from 'pinia'
import { createApp } from 'vue'
import CommentsApp from '../views/CommentsApp.vue'
import logger from '../logger.ts'

export interface CommentsInstanceOptions {
	el?: HTMLElement

	props?: Record<string, unknown>

	/** @deprecated use `props` instead */
	propsData?: Record<string, unknown>
}

export default class CommentInstance {
	private app: App
	private instance: ComponentPublicInstance<typeof CommentsApp> | undefined

	/**
	 * Initialize a new Comments instance for the desired type
	 *
	 * @param resourceType - The comments endpoint type
	 * @param options - The vue options (props, parent, el...)
	 */
	constructor(resourceType = 'files', options: CommentsInstanceOptions = {}) {
		const pinia = createPinia()

		this.app = createApp(
			CommentsApp,
			{
				...(options.propsData ?? {}),
				...(options.props ?? {}),
				resourceType,
			},
		)

		// Add translates functions
		this.app.mixin({
			data() {
				return {
					logger,
				}
			},
			methods: {
				t,
				n,
			},
		})

		this.app.use(pinia)
		if (options.el) {
			this.instance = this.app.mount(options.el)
		}
	}

	/**
	 * Mount the Comments instance to a new element.
	 *
	 * @param el - The element to mount the instance on
	 */
	$mount(el: HTMLElement | string) {
		if (this.instance) {
			this.app.unmount()
		}
		this.instance = this.app.mount(el)
	}

	/**
	 * Unmount the Comments instance from the DOM and destroy it.
	 */
	$unmount() {
		this.app.unmount()
		this.instance = undefined
	}

	/**
	 * Update the current resource id.
	 *
	 * @param id - The new resource id to load the comments for
	 */
	update(id: string | number) {
		if (this.instance) {
			this.instance.update(id)
		}
	}
}
