/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { n, t } from '@nextcloud/l10n'
import { createPinia } from 'pinia'
import { createApp } from 'vue'
import CommentsApp from '../views/Comments.vue'
import logger from '../logger.ts'

export default class CommentInstance {
	/**
	 * Initialize a new Comments instance for the desired type
	 *
	 * @param {string} resourceType the comments endpoint type
	 * @param {object} options the vue options (propsData, parent, el...)
	 */
	constructor(resourceType = 'files', options = {}) {
		const pinia = createPinia()

		const app = createApp(
			CommentsApp,
			{
				...(options.propsData ?? {}),
				resourceType,
			},
		)

		// Add translates functions
		app.mixin({
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

		app.use(pinia)
		// app.mount(options.el)
		return app
	}
}
