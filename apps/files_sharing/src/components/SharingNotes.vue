<!--
  - @copyright Copyright (c) 2021 Yogesh Shejwadkar <yogesh.shejwadkar@t-systems.com>
  -
  - @author Yogesh Shejwadkar <yogesh.shejwadkar@t-systems.com>
  -
  - @license GNU AGPL version 3 or any later version
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program. If not, see <http://www.gnu.org/licenses/>.
  -
  -->

<template>
	<div :class="{ 'icon-loading': loading }">
		<div class="sharing-notes">
			<!-- note -->
			<template>
				<label>
					{{ t('files_sharing', 'Note to recipient') }}
				</label>
				<div>
					<textarea
						ref="note"
						v-model="shareNote"
						class="sharing-note"
						:disabled="saving" />
				</div>
				<div>
					<button class="status-buttons__select"
						:name="randomId"
						:disabled="saving"
						@click="cancelSharing">
						{{ t('files_sharing', 'Cancel') }}
					</button>
					<template v-if="share.id > 0">
						<button class="status-buttons__primary primary"
							:name="randomId"
							:disabled="saving"
							@click="sendEmail">
							{{ t('files_sharing', 'Send email') }}
						</button>
					</template>
					<template v-else>
						<button class="status-buttons__primary primary"
							:name="randomId"
							:disabled="saving"
							@click="addShare">
							{{ t('files_sharing', 'Send share') }}
						</button>
					</template>
				</div>
			</template>
		</div>
	</div>
</template>

<script>
import Config from '../services/ConfigService'
import SharesMixin from '../mixins/SharesMixin'
import Share from '../models/Share'
import ShareRequests from '../mixins/ShareRequests'
import ShareTypes from '../mixins/ShareTypes'
import { mapGetters } from 'vuex'

export default {
	name: 'SharingNotes',

	components: {
	},

	directives: {
	},

	mixins: [SharesMixin, ShareRequests, ShareTypes],

	data() {
		return {
			config: new Config(),
			loading: false,
			query: '',
			recommendations: [],
			ShareSearch: OCA.Sharing.ShareSearch.state,
			suggestions: [],
			shareNote: this.share.newNote || this.share.note || '',
			sendPasswordByTalk: null,
			hideDownload: null,
		}
	},

	computed: {
		...mapGetters({
			optionValues: 'getOption',
			// share: 'getShare',
		}),

		/**
		 * Generate a unique random id for this SharingPermissions only
		 * This allows ActionRadios to have the same name prop
		 * but not to impact others SharingPermissions
		 * @returns {string}
		 */
		randomId() {
			return Math.random().toString(27).substr(2)
		},
	},

	methods: {
		cancelSharing() {
			this.$store.commit('addCurrentTab', 'default')
		},

		sendEmail() {
			this.loading = true
			this.share.newNote = this.shareNote
			this.onNoteSubmit()
			this.loading = false
			this.$store.commit('addCurrentTab', 'default')
		},

		/**
		 * Process the new share request
		 * @param {Object} optionValues the multiselect option
		 */
		async addShare() {
			// handle externalResults from OCA.Sharing.ShareSearch
			if (this.share.handler) {
				console.debug('addShare this.share handler ', this.share)
				const share = await this.share.handler(this)
				this.$emit('add:share', new Share(share))
				return true
			}

			this.loading = true
			console.debug('Adding a new share from the input for', this.share)
			try {
				const path = (this.fileInfo.path + '/' + this.fileInfo.name).replace('//', '/')

				if (this.share.sendPasswordByTalk) {
					this.sendPasswordByTalk = this.share.sendPasswordByTalk.toString()
				}

				if (this.share.hideDownload) {
					this.hideDownload = this.share.hideDownload.toString()
				}

				const share = await this.createShare({
					path,
					shareType: this.optionValues.shareType,
					shareWith: this.optionValues.shareWith,
					password: this.share.password,
					sendPasswordByTalk: this.sendPasswordByTalk,
					expireDate: this.share.expireDate,
					hideDownload: this.hideDownload,
					permissions: this.fileInfo.sharePermissions & OC.getCapabilities().files_sharing.default_permissions & this.share.permissions,
				})

				// add notes to share if any
				this.share = share
				this.share.note = this.shareNote
				this.queueUpdate('note')

				// reset the search string when done
				// FIXME: https://github.com/shentao/vue-multiselect/issues/633
				if (this.$refs.multiselect?.$refs?.VueMultiselect?.search) {
					this.$refs.multiselect.$refs.VueMultiselect.search = ''
				}

				this.$root.$emit('update', this.fileInfo)
				await this.$root.$emit('getRecommendations')
				this.cancelSharing()
			} catch (error) {
				this.query = this.share.shareWith
				console.error('Error while adding new share', error)
			} finally {
				this.loading = false
			}
		},
	},
}
</script>

<style lang="scss" scoped>
.sharing-note {
	width: 100%;
	height: 200px;
}
</style>
