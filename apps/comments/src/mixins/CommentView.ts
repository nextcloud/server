import axios from '@nextcloud/axios'
import { getCurrentUser } from '@nextcloud/auth'
import { loadState } from '@nextcloud/initial-state'
import { generateOcsUrl } from '@nextcloud/router'
import { defineComponent } from 'vue'

export default defineComponent({
	props: {
		ressourceId: {
			type: Number,
			required: true,
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
					itemId: this.ressourceId,
					sorter: 'commenters|share-recipients',
					limit: loadState('comments', 'maxAutoCompleteResults'),
				},
			})
			// Save user data so it can be used by the editor to replace mentions
			data.ocs.data.forEach(user => { this.userData[user.id] = user })
			return callback(Object.values(this.userData))
		},
	},
})
