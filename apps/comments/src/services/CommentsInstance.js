/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { translate as t, translatePlural as n } from '@nextcloud/l10n'
import { getRequestToken } from '@nextcloud/auth'
import Vue from 'vue'
import CommentsApp from '../views/Comments.vue'
import logger from '../logger.js'

// eslint-disable-next-line camelcase
__webpack_nonce__ = btoa(getRequestToken())

// Add translates functions
Vue.mixin({
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

export default class CommentInstance {

	/**
	 * Initialize a new Comments instance for the desired type
	 *
	 * @param {string} resourceType the comments endpoint type
	 * @param  {object} options the vue options (propsData, parent, el...)
	 */
	constructor(resourceType = 'files', options = {}) {
		// Merge options and set `resourceType` property
		options = {
			...options,
			propsData: {
				...(options.propsData ?? {}),
				resourceType,
			},
		}
		// Init Comments component
		const View = Vue.extend(CommentsApp)
		return new View(options)
	}

}
