/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

import { defineStore } from 'pinia'

export const useDeletedCommentLimbo = defineStore('deletedCommentLimbo', {
	state: () => ({
		idsInLimbo: [],
	}),
	actions: {
		addId(id) {
			this.idsInLimbo.push(id)
		},

		removeId(id) {
			const index = this.idsInLimbo.indexOf(id)
			if (index > -1) {
				this.idsInLimbo.splice(index, 1)
			}
		},

		checkForId(id) {
			this.idsInLimbo.includes(id)
		},
	},
})
