<!--
  - @copyright Copyright (c) 2019 John Molakvoæ <skjnldsv@protonmail.com>
  -
  - @author John Molakvoæ <skjnldsv@protonmail.com>
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
		<!-- error message -->
		<div v-if="error" class="emptycontent" :class="{ emptyContentWithSections: sections.length > 0 }">
			<div class="icon icon-error" />
			<h2>{{ error }}</h2>
		</div>

		<!-- shares content -->
		<div v-if="!showSharingDetailsView" class="sharingTab__content">
			<!-- shared with me information -->
			<SharingEntrySimple v-if="isSharedWithMe" v-bind="sharedWithMe" class="sharing-entry__reshare">
				<template #avatar>
					<NcAvatar :user="sharedWithMe.user"
						:display-name="sharedWithMe.displayName"
						class="sharing-entry__avatar" />
				</template>
			</SharingEntrySimple>

			<!-- add new share input -->
			<SharingInput v-if="!loading"
				:can-reshare="canReshare"
				:file-info="fileInfo"
				:link-shares="linkShares"
				:reshare="reshare"
				:shares="shares"
				@open-sharing-details="toggleShareDetailsView" />

			<!-- link shares list -->
			<SharingLinkList v-if="!loading"
				ref="linkShareList"
				:can-reshare="canReshare"
				:file-info="fileInfo"
				:shares="linkShares"
				@open-sharing-details="toggleShareDetailsView" />

			<!-- other shares list -->
			<SharingList v-if="!loading"
				ref="shareList"
				:shares="shares"
				:file-info="fileInfo"
				@open-sharing-details="toggleShareDetailsView" />

			<!-- inherited shares -->
			<SharingInherited v-if="canReshare && !loading" :file-info="fileInfo" />

			<!-- internal link copy -->
			<SharingEntryInternal :file-info="fileInfo" />

			<!-- projects -->
			<CollectionList v-if="projectsEnabled && fileInfo"
				:id="`${fileInfo.id}`"
				type="file"
				:name="fileInfo.name" />
		</div>

		<!-- share details -->
		<div v-else>
			<SharingDetailsTab :file-info="shareDetailsData.fileInfo"
				:share="shareDetailsData.share"
				@close-sharing-details="toggleShareDetailsView"
				@add:share="addShare"
				@remove:share="removeShare" />
		</div>

		<!-- additional entries, use it with cautious -->
		<div v-for="(section, index) in sections"
			:ref="'section-' + index"
			:key="index"
			class="sharingTab__additionalContent">
			<component :is="section($refs['section-'+index], fileInfo)" :file-info="fileInfo" />
		</div>
	</div>
</template>

<script>
import { CollectionList } from 'nextcloud-vue-collections'
import { generateOcsUrl } from '@nextcloud/router'
import NcAvatar from '@nextcloud/vue/dist/Components/NcAvatar.js'
import axios from '@nextcloud/axios'
import { loadState } from '@nextcloud/initial-state'

import Config from '../services/ConfigService.js'
import { shareWithTitle } from '../utils/SharedWithMe.js'
import Share from '../models/Share.js'
import ShareTypes from '../mixins/ShareTypes.js'
import SharingEntryInternal from '../components/SharingEntryInternal.vue'
import SharingEntrySimple from '../components/SharingEntrySimple.vue'
import SharingInput from '../components/SharingInput.vue'

import SharingInherited from './SharingInherited.vue'
import SharingLinkList from './SharingLinkList.vue'
import SharingList from './SharingList.vue'
import SharingDetailsTab from './SharingDetailsTab.vue'

export default {
	name: 'SharingTab',

	components: {
		NcAvatar,
		CollectionList,
		SharingEntryInternal,
		SharingEntrySimple,
		SharingInherited,
		SharingInput,
		SharingLinkList,
		SharingList,
		SharingDetailsTab,
	},

	mixins: [ShareTypes],

	data() {
		return {
			config: new Config(),
			deleteEvent: null,
			error: '',
			expirationInterval: null,
			loading: true,

			fileInfo: null,

			// reshare Share object
			reshare: null,
			sharedWithMe: {},
			shares: [],
			linkShares: [],

			sections: OCA.Sharing.ShareTabSections.getSections(),
			projectsEnabled: loadState('core', 'projects_enabled', false),
			showSharingDetailsView: false,
			shareDetailsData: {},
		}
	},

	computed: {
		/**
		 * Is this share shared with me?
		 *
		 * @return {boolean}
		 */
		isSharedWithMe() {
			return Object.keys(this.sharedWithMe).length > 0
		},

		canReshare() {
			return !!(this.fileInfo.permissions & OC.PERMISSION_SHARE)
				|| !!(this.reshare && this.reshare.hasSharePermission && this.config.isResharingAllowed)
		},
	},

	methods: {
		/**
		 * Update current fileInfo and fetch new data
		 *
		 * @param {object} fileInfo the current file FileInfo
		 */
		async update(fileInfo) {
			this.fileInfo = fileInfo
			this.resetState()
			this.getShares()
		},

		/**
		 * Get the existing shares infos
		 */
		async getShares() {
			try {
				this.loading = true

				// init params
				const shareUrl = generateOcsUrl('apps/files_sharing/api/v1/shares')
				const format = 'json'
				// TODO: replace with proper getFUllpath implementation of our own FileInfo model
				const path = (this.fileInfo.path + '/' + this.fileInfo.name).replace('//', '/')

				// fetch shares
				const fetchShares = axios.get(shareUrl, {
					params: {
						format,
						path,
						reshares: true,
					},
				})
				const fetchSharedWithMe = axios.get(shareUrl, {
					params: {
						format,
						path,
						shared_with_me: true,
					},
				})

				// wait for data
				const [shares, sharedWithMe] = await Promise.all([fetchShares, fetchSharedWithMe])
				this.loading = false

				// process results
				this.processSharedWithMe(sharedWithMe)
				this.processShares(shares)
			} catch (error) {
				if (error.response.data?.ocs?.meta?.message) {
					this.error = error.response.data.ocs.meta.message
				} else {
					this.error = t('files_sharing', 'Unable to load the shares list')
				}
				this.loading = false
				console.error('Error loading the shares list', error)
			}
		},

		/**
		 * Reset the current view to its default state
		 */
		resetState() {
			clearInterval(this.expirationInterval)
			this.loading = true
			this.error = ''
			this.sharedWithMe = {}
			this.shares = []
			this.linkShares = []
			this.showSharingDetailsView = false
			this.shareDetailsData = {}
		},

		/**
		 * Update sharedWithMe.subtitle with the appropriate
		 * expiration time left
		 *
		 * @param {Share} share the sharedWith Share object
		 */
		updateExpirationSubtitle(share) {
			const expiration = moment(share.expireDate).unix()
			this.$set(this.sharedWithMe, 'subtitle', t('files_sharing', 'Expires {relativetime}', {
				relativetime: OC.Util.relativeModifiedDate(expiration * 1000),
			}))

			// share have expired
			if (moment().unix() > expiration) {
				clearInterval(this.expirationInterval)
				// TODO: clear ui if share is expired
				this.$set(this.sharedWithMe, 'subtitle', t('files_sharing', 'this share just expired.'))
			}
		},

		/**
		 * Process the current shares data
		 * and init shares[]
		 *
		 * @param {object} share the share ocs api request data
		 * @param {object} share.data the request data
		 */
		processShares({ data }) {
			if (data.ocs && data.ocs.data && data.ocs.data.length > 0) {
				// create Share objects and sort by newest
				const shares = data.ocs.data
					.map(share => new Share(share))
					.sort((a, b) => b.createdTime - a.createdTime)

				this.linkShares = shares.filter(share => share.type === this.SHARE_TYPES.SHARE_TYPE_LINK || share.type === this.SHARE_TYPES.SHARE_TYPE_EMAIL)
				this.shares = shares.filter(share => share.type !== this.SHARE_TYPES.SHARE_TYPE_LINK && share.type !== this.SHARE_TYPES.SHARE_TYPE_EMAIL)

				console.debug('Processed', this.linkShares.length, 'link share(s)')
				console.debug('Processed', this.shares.length, 'share(s)')
			}
		},

		/**
		 * Process the sharedWithMe share data
		 * and init sharedWithMe
		 *
		 * @param {object} share the share ocs api request data
		 * @param {object} share.data the request data
		 */
		processSharedWithMe({ data }) {
			if (data.ocs && data.ocs.data && data.ocs.data[0]) {
				const share = new Share(data)
				const title = shareWithTitle(share)
				const displayName = share.ownerDisplayName
				const user = share.owner

				this.sharedWithMe = {
					displayName,
					title,
					user,
				}
				this.reshare = share

				// If we have an expiration date, use it as subtitle
				// Refresh the status every 10s and clear if expired
				if (share.expireDate && moment(share.expireDate).unix() > moment().unix()) {
					// first update
					this.updateExpirationSubtitle(share)
					// interval update
					this.expirationInterval = setInterval(this.updateExpirationSubtitle, 10000, share)
				}
			} else if (this.fileInfo && this.fileInfo.shareOwnerId !== undefined ? this.fileInfo.shareOwnerId !== OC.currentUser : false) {
				// Fallback to compare owner and current user.
				this.sharedWithMe = {
					displayName: this.fileInfo.shareOwner,
					title: t(
						'files_sharing',
						'Shared with you by {owner}',
						{ owner: this.fileInfo.shareOwner },
						undefined,
						{ escape: false },
					),
					user: this.fileInfo.shareOwnerId,
				}
			}
		},

		/**
		 * Add a new share into the shares list
		 * and return the newly created share component
		 *
		 * @param {Share} share the share to add to the array
		 * @param {Function} [resolve] a function to run after the share is added and its component initialized
		 */
		addShare(share, resolve = () => { }) {
			// only catching share type MAIL as link shares are added differently
			// meaning: not from the ShareInput
			if (share.type === this.SHARE_TYPES.SHARE_TYPE_EMAIL) {
				this.linkShares.unshift(share)
			} else {
				this.shares.unshift(share)
			}
			this.awaitForShare(share, resolve)
		},
		/**
		 * Remove a share from the shares list
		 *
		 * @param {Share} share the share to remove
		 */
		removeShare(share) {
			const index = this.shares.findIndex(item => item.id === share.id)
			// eslint-disable-next-line vue/no-mutating-props
			this.shares.splice(index, 1)
		},
		/**
		 * Await for next tick and render after the list updated
		 * Then resolve with the matched vue component of the
		 * provided share object
		 *
		 * @param {Share} share newly created share
		 * @param {Function} resolve a function to execute after
		 */
		awaitForShare(share, resolve) {
			let listComponent = this.$refs.shareList
			// Only mail shares comes from the input, link shares
			// are managed internally in the SharingLinkList component
			if (share.type === this.SHARE_TYPES.SHARE_TYPE_EMAIL) {
				listComponent = this.$refs.linkShareList
			}

			this.$nextTick(() => {
				const newShare = listComponent.$children.find(component => component.share === share)
				if (newShare) {
					resolve(newShare)
				}
			})
		},
		toggleShareDetailsView(eventData) {
			if (eventData) {
				this.shareDetailsData = eventData
			}
			this.showSharingDetailsView = !this.showSharingDetailsView
		},
	},
}
</script>

<style scoped lang="scss">
.emptyContentWithSections {
	margin: 1rem auto;
}

.sharingTab {
	&__content {
		padding: 0 6px;
	}

	&__additionalContent {
		margin: 44px 0;
	}
}
</style>
