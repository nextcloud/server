/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
import axios from '@nextcloud/axios'
import { getCurrentUser } from '@nextcloud/auth'
import { loadState } from '@nextcloud/initial-state'
import { generateOcsUrl } from '@nextcloud/router'
import { defineComponent } from 'vue'

export default defineComponent({
	props: {
		resourceId: {
			type: Number,
			required: true,
		},
		resourceType: {
			type: String,
			default: 'files',
		},
	},
	data() {
		return {
			editorData: {
				actorDisplayName: getCurrentUser()!.displayName as string,
				actorId: getCurrentUser()!.uid as string,
				key: 'editor',
			},
			userData: {},
		}
	},
	methods: {
		/**
		 * Autocomplete @mentions
		 *
		 * @param {string} search the query
		 * @param {Function} callback the callback to process the results with
		 */
		async autoComplete(search, callback) {
			const { data } = await axios.get(generateOcsUrl('core/autocomplete/get'), {
				params: {
					search,
					itemType: 'files',
					itemId: this.resourceId,
					sorter: 'commenters|share-recipients',
					limit: loadState('comments', 'maxAutoCompleteResults'),
				},
			})
			// Save user data so it can be used by the editor to replace mentions
			data.ocs.data.forEach(user => { this.userData[user.id] = user })
			return callback(Object.values(this.userData))
		},

		/**
		 * Make sure we have all mentions as Array of objects
		 *
		 * @param mentions the mentions list
		 */
		// eslint-disable-next-line @typescript-eslint/no-explicit-any
		genMentionsData(mentions: any[]): Record<string, object> {
			Object.values(mentions)
				.flat()
				.forEach(mention => {
					this.userData[mention.mentionId] = {
						// TODO: support groups
						icon: 'icon-user',
						id: mention.mentionId,
						label: mention.mentionDisplayName,
						source: 'users',
						primary: getCurrentUser()?.uid === mention.mentionId,
					}
				})
			return this.userData
		},
	},
})
