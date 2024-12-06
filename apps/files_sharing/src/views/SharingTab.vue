<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="sharingTab" :class="{ 'icon-loading': loading }">
		<!-- error message -->
		<div v-if="error" class="emptycontent" :class="{ emptyContentWithSections: sections.length > 0 }">
			<div class="icon icon-error" />
			<h2>{{ error }}</h2>
		</div>

		<!-- shares content -->
		<div v-show="!showSharingDetailsView"
			class="sharingTab__content">
			<!-- shared with me information -->
			<ul>
				<SharingEntrySimple v-if="isSharedWithMe" v-bind="sharedWithMe" class="sharing-entry__reshare">
					<template #avatar>
						<NcAvatar :user="sharedWithMe.user"
							:display-name="sharedWithMe.displayName"
							class="sharing-entry__avatar" />
					</template>
				</SharingEntrySimple>
			</ul>

			<h3>Internal shares</h3>

			<!-- TODO: component must either be configurable or diffentiated into two -->
			<!-- add new share input -->
			<SharingInput v-if="!loading"
				:can-reshare="canReshare"
				:file-info="fileInfo"
				:link-shares="linkShares"
				:reshare="reshare"
				:shares="shares"
				@open-sharing-details="toggleShareDetailsView" />

			<!-- will move _into_ the dropdown component -->
			<div style="border-top: 1px dotted grey;"></div>

			<!-- internal link copy -->
			<SharingEntryInternal :file-info="fileInfo" />

			<div style="border-top: 1px dotted grey;"></div>
			<!-- will move _into_ the dropdown component -->

			<!-- other shares list -->
			<SharingList v-if="!loading"
				ref="internalShareList"
				:shares="shares"
				:file-info="fileInfo"
				@open-sharing-details="toggleShareDetailsView" />

			<!-- inherited shares -->
			<SharingInherited v-if="canReshare && !loading" :file-info="fileInfo" />

		<template v-if="config.isRemoteShareAllowed">
			<hr>
			<h3>External shares</h3>

			<!-- ***** DISSOLVED OUT FROM ShareLinkList ***** -->
			<SharingEntryLink v-if="!hasLinkShares && canReshare"
				:can-reshare="canReshare"
				:file-info="fileInfo"
				@add:share="addExternalShare" />
			<!-- ***** DISSOLVED OUT FROM ShareLinkList ***** -->

			<!-- TODO: component must either be configurable or diffentiated into two -->
			<!-- add new email/federated share input -->
			<SharingInput v-if="!loading"
				:can-reshare="canReshare"
				:file-info="fileInfo"
				:link-shares="linkShares"
				:reshare="reshare"
				:shares="shares"
				@open-sharing-details="toggleShareDetailsView" />

			<!-- ***** DISSOLVED OUT FROM ShareLinkList ***** -->
			<SharingListExternal v-if="hasShares"
				ref="externalShareList"
				:can-reshare="canReshare"
				:shares="shares"
				:file-info="fileInfo"
				@add:share="addShare(...arguments)"
				@update:share="awaitForShare(...arguments)"
				@remove:share="removeShare"
				@open-sharing-details="openSharingDetails(share)" />
			<!-- ***** DISSOLVED OUT FROM ShareLinkList ***** -->
		</template>

			<CollectionList v-if="projectsEnabled && fileInfo"
				:id="`${fileInfo.id}`"
				type="file"
				:name="fileInfo.name" />
		</div>

		<!-- additional entries, use it with cautious -->
		<div v-for="(section, index) in sections"
			v-show="!showSharingDetailsView"
			:ref="'section-' + index"
			:key="index"
			class="sharingTab__additionalContent">
			<component :is="section($refs['section-'+index], fileInfo)" :file-info="fileInfo" />
		</div>

		<!-- projects (deprecated as of NC25 (replaced by related_resources) - see instance config "projects.enabled" ; ignore this / remove it / move into own section) -->
		<div v-show="!showSharingDetailsView && projectsEnabled && fileInfo"
			class="sharingTab__additionalContent">
			<CollectionList :id="`${fileInfo.id}`"
				type="file"
				:name="fileInfo.name" />
		</div>

		<!-- share details -->
		<SharingDetailsTab v-if="showSharingDetailsView"
			:file-info="shareDetailsData.fileInfo"
			:share="shareDetailsData.share"
			@close-sharing-details="toggleShareDetailsView"
			@add:share="addInternalShare"
			@remove:share="removeShare" />
	</div>
</template>

<script>
import { getCurrentUser } from '@nextcloud/auth'
import { orderBy } from '@nextcloud/files'
import { loadState } from '@nextcloud/initial-state'
import { generateOcsUrl } from '@nextcloud/router'
import { CollectionList } from 'nextcloud-vue-collections'
import { ShareType } from '@nextcloud/sharing'

import axios from '@nextcloud/axios'
import moment from '@nextcloud/moment'
import NcAvatar from '@nextcloud/vue/dist/Components/NcAvatar.js'

import { shareWithTitle } from '../utils/SharedWithMe.js'

import Config from '../services/ConfigService.ts'
import Share from '../models/Share.ts'
import SharingEntryInternal from '../components/SharingEntryInternal.vue'
import SharingEntrySimple from '../components/SharingEntrySimple.vue'
import SharingInput from '../components/SharingInput.vue'

import SharingInherited from './SharingInherited.vue'
import SharingList from './SharingList.vue'
import SharingListExternal from './SharingListExternal.vue'
import SharingDetailsTab from './SharingDetailsTab.vue'
import { getCapabilities } from '@nextcloud/capabilities'
import SharingEntryLink from '../components/SharingEntryLink.vue'
import ShareDetails from '../mixins/ShareDetails.js'

export default {
	name: 'SharingTab',

	components: {
		SharingEntryLink,
		NcAvatar,
		CollectionList,
		SharingEntryInternal,
		SharingEntrySimple,
		SharingInherited,
		SharingInput,
		SharingList,
		SharingListExternal,
		SharingDetailsTab,
	},

	mixins: [ShareDetails],

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
			returnFocusElement: null,
			canLinkShare: getCapabilities().files_sharing.public.enabled,
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

		/**
		 * Do we have link shares?
		 * Using this to still show the `new link share`
		 * button regardless of mail shares
		 *
		 * @return {Array}
		 */
		hasLinkShares() {
			return this.shares.filter(share => share.type === ShareType.Link).length > 0
		},

		/**
		 * Do we have any link or email shares?
		 *
		 * @return {boolean}
		 */
		hasShares() {
			return this.shares.length > 0
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
				if (error?.response?.data?.ocs?.meta?.message) {
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
				relativetime: moment(expiration * 1000).fromNow(),
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
				const shares = orderBy(
					data.ocs.data.map(share => new Share(share)),
					[
						// First order by the "share with" label
						(share) => share.shareWithDisplayName,
						// Then by the label
						(share) => share.label,
						// And last resort order by createdTime
						(share) => share.createdTime,
					],
				)

				this.linkShares = shares.filter(share => share.type === ShareType.Link || share.type === ShareType.Email)
				this.shares = shares.filter(share => share.type !== ShareType.Link && share.type !== ShareType.Email)

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
			} else if (this.fileInfo && this.fileInfo.shareOwnerId !== undefined ? this.fileInfo.shareOwnerId !== getCurrentUser().uid : false) {
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
		addInternalShare(share, resolve = () => { }) {
			this.shares.unshift(share)
			this.awaitForInternalShare(share, resolve)
		},
		/**
		 * Add a new share into the shares list
		 * and return the newly created share component
		 *
		 * @param {Share} share the share to add to the array
		 * @param {Function} [resolve] a function to run after the share is added and its component initialized
		 */
		addExternalShare(share, resolve = () => { }) {
			// only catching share type MAIL as link shares are added differently
			// meaning: not from the ShareInput
			if (share.type === ShareType.Email) {
				this.linkShares.unshift(share)
			} else {
				this.shares.unshift(share)
			}
			this.awaitForExternalShare(share, resolve)
		},
		/**
		 * Remove a share from the shares list
		 *
		 * @param {Share} share the share to remove
		 */
		removeShare(share) {
			// Get reference for this.linkShares or this.shares
			const shareList
				= share.type === ShareType.Email
					|| share.type === ShareType.Link
					? this.linkShares
					: this.shares
			const index = shareList.findIndex(item => item.id === share.id)
			if (index !== -1) {
				shareList.splice(index, 1)
			}
		},
		/**
		 * Await for next tick and render after the list updated
		 * Then resolve with the matched vue component of the
		 * provided share object
		 *
		 * @param {Share} share newly created share
		 * @param {Function} resolve a function to execute after
		 */
		awaitForInternalShare(share, resolve) {
			this.$nextTick(() => {
				let listComponent = this.$refs.internalShareList
				const newShare = listComponent.$children.find(component => component.share === share)
				if (newShare) {
					resolve(newShare)
				}
			})
		},
		/**
		 * Await for next tick and render after the list updated
		 * Then resolve with the matched vue component of the
		 * provided share object
		 *
		 * @param {Share} share newly created share
		 * @param {Function} resolve a function to execute after
		 */
		awaitForExternalShare(share, resolve) {
			this.$nextTick(() => {
				let listComponent = this.$refs.externalShareList
				const newShare = listComponent.$children.find(component => component.share === share)
				if (newShare) {
					resolve(newShare)
				}
			})
		},
		toggleShareDetailsView(eventData) {
			if (!this.showSharingDetailsView) {
				const isAction = Array.from(document.activeElement.classList)
					.some(className => className.startsWith('action-'))
				if (isAction) {
					const menuId = document.activeElement.closest('[role="menu"]')?.id
					this.returnFocusElement = document.querySelector(`[aria-controls="${menuId}"]`)
				} else {
					this.returnFocusElement = document.activeElement
				}
			}

			if (eventData) {
				this.shareDetailsData = eventData
			}

			this.showSharingDetailsView = !this.showSharingDetailsView

			if (!this.showSharingDetailsView) {
				this.$nextTick(() => { // Wait for next tick as the element must be visible to be focused
					this.returnFocusElement?.focus()
					this.returnFocusElement = null
				})
			}
		},
	},
}
</script>

<style scoped lang="scss">
.emptyContentWithSections {
	margin: 1rem auto;
}

.sharingTab {
	position: relative;
	height: 100%;

	&__content {
		padding: 0 6px;
	}

	&__additionalContent {
		margin: 44px 0;
	}
}

h3 {
	font-weight: bold;
}
</style>
