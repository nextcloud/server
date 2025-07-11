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
			<ul v-if="isSharedWithMe">
				<SharingEntrySimple v-bind="sharedWithMe" class="sharing-entry__reshare">
					<template #avatar>
						<NcAvatar :user="sharedWithMe.user"
							:display-name="sharedWithMe.displayName"
							class="sharing-entry__avatar" />
					</template>
				</SharingEntrySimple>
			</ul>

			<section>
				<div class="section-header">
					<h4>{{ t('files_sharing', 'Internal shares') }}</h4>
					<NcPopover popup-role="dialog">
						<template #trigger>
							<NcButton class="hint-icon"
								type="tertiary-no-background"
								:aria-label="t('files_sharing', 'Internal shares explanation')">
								<template #icon>
									<InfoIcon :size="20" />
								</template>
							</NcButton>
						</template>
						<p class="hint-body">
							{{ internalSharesHelpText }}
						</p>
					</NcPopover>
				</div>
				<!-- add new share input -->
				<SharingInput v-if="!loading"
					:can-reshare="canReshare"
					:file-info="fileInfo"
					:link-shares="linkShares"
					:reshare="reshare"
					:shares="shares"
					:placeholder="internalShareInputPlaceholder"
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
			</section>

			<section>
				<div class="section-header">
					<h4>{{ t('files_sharing', 'External shares') }}</h4>
					<NcPopover popup-role="dialog">
						<template #trigger>
							<NcButton class="hint-icon"
								type="tertiary-no-background"
								:aria-label="t('files_sharing', 'External shares explanation')">
								<template #icon>
									<InfoIcon :size="20" />
								</template>
							</NcButton>
						</template>
						<p class="hint-body">
							{{ externalSharesHelpText }}
						</p>
					</NcPopover>
				</div>
				<SharingInput v-if="!loading"
					:can-reshare="canReshare"
					:file-info="fileInfo"
					:link-shares="linkShares"
					:is-external="true"
					:placeholder="externalShareInputPlaceholder"
					:reshare="reshare"
					:shares="shares"
					@open-sharing-details="toggleShareDetailsView" />
				<!-- Non link external shares list -->
				<SharingList v-if="!loading"
					:shares="externalShares"
					:file-info="fileInfo"
					@open-sharing-details="toggleShareDetailsView" />
				<!-- link shares list -->
				<SharingLinkList v-if="!loading && isLinkSharingAllowed"
					ref="linkShareList"
					:can-reshare="canReshare"
					:file-info="fileInfo"
					:shares="linkShares"
					@open-sharing-details="toggleShareDetailsView" />
			</section>

			<section v-if="sections.length > 0 && !showSharingDetailsView">
				<div class="section-header">
					<h4>{{ t('files_sharing', 'Additional shares') }}</h4>
					<NcPopover popup-role="dialog">
						<template #trigger>
							<NcButton class="hint-icon"
								type="tertiary-no-background"
								:aria-label="t('files_sharing', 'Additional shares explanation')">
								<template #icon>
									<InfoIcon :size="20" />
								</template>
							</NcButton>
						</template>
						<p class="hint-body">
							{{ additionalSharesHelpText }}
						</p>
					</NcPopover>
				</div>
				<!-- additional entries, use it with cautious -->
				<div v-for="(section, index) in sections"
					:ref="'section-' + index"
					:key="index"
					class="sharingTab__additionalContent">
					<component :is="section($refs['section-'+index], fileInfo)" :file-info="fileInfo" />
				</div>

				<!-- projects (deprecated as of NC25 (replaced by related_resources) - see instance config "projects.enabled" ; ignore this / remove it / move into own section) -->
				<div v-if="projectsEnabled"
					v-show="!showSharingDetailsView && fileInfo"
					class="sharingTab__additionalContent">
					<CollectionList :id="`${fileInfo.id}`"
						type="file"
						:name="fileInfo.name" />
				</div>
			</section>
		</div>

		<!-- share details -->
		<SharingDetailsTab v-if="showSharingDetailsView"
			:file-info="shareDetailsData.fileInfo"
			:share="shareDetailsData.share"
			@close-sharing-details="toggleShareDetailsView"
			@add:share="addShare"
			@remove:share="removeShare" />
	</div>
</template>

<script>
import { getCurrentUser } from '@nextcloud/auth'
import { getCapabilities } from '@nextcloud/capabilities'
import { orderBy } from '@nextcloud/files'
import { loadState } from '@nextcloud/initial-state'
import { generateOcsUrl } from '@nextcloud/router'
import { CollectionList } from 'nextcloud-vue-collections'
import { ShareType } from '@nextcloud/sharing'

import InfoIcon from 'vue-material-design-icons/Information.vue'
import NcPopover from '@nextcloud/vue/components/NcPopover'

import axios from '@nextcloud/axios'
import moment from '@nextcloud/moment'
import NcAvatar from '@nextcloud/vue/components/NcAvatar'
import NcButton from '@nextcloud/vue/components/NcButton'

import { shareWithTitle } from '../utils/SharedWithMe.js'

import Config from '../services/ConfigService.ts'
import Share from '../models/Share.ts'
import SharingEntryInternal from '../components/SharingEntryInternal.vue'
import SharingEntrySimple from '../components/SharingEntrySimple.vue'
import SharingInput from '../components/SharingInput.vue'

import SharingInherited from './SharingInherited.vue'
import SharingLinkList from './SharingLinkList.vue'
import SharingList from './SharingList.vue'
import SharingDetailsTab from './SharingDetailsTab.vue'

import ShareDetails from '../mixins/ShareDetails.js'
import logger from '../services/logger.ts'

export default {
	name: 'SharingTab',

	components: {
		CollectionList,
		InfoIcon,
		NcAvatar,
		NcButton,
		NcPopover,
		SharingEntryInternal,
		SharingEntrySimple,
		SharingInherited,
		SharingInput,
		SharingLinkList,
		SharingList,
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
			externalShares: [],

			sections: OCA.Sharing.ShareTabSections.getSections(),
			projectsEnabled: loadState('core', 'projects_enabled', false),
			showSharingDetailsView: false,
			shareDetailsData: {},
			returnFocusElement: null,

			internalSharesHelpText: t('files_sharing', 'Use this method to share files with individuals or teams within your organization. If the recipient already has access to the share but cannot locate it, you can send them the internal share link for easy access.'),
			externalSharesHelpText: t('files_sharing', 'Use this method to share files with individuals or organizations outside your organization. Files and folders can be shared via public share links and email addresses. You can also share to other Nextcloud accounts hosted on different instances using their federated cloud ID.'),
			additionalSharesHelpText: t('files_sharing', 'Shares that are not part of the internal or external shares. This can be shares from apps or other sources.'),
		}
	},

	computed: {
		/**
		 * Is this share shared with me?
		 *
		 * @return {boolean}
		 */
		isSharedWithMe() {
			return this.sharedWithMe !== null
				&& this.sharedWithMe !== undefined
		},

		/**
		 * Is link sharing allowed for the current user?
		 *
		 * @return {boolean}
		 */
		isLinkSharingAllowed() {
			const currentUser = getCurrentUser()
			if (!currentUser) {
				return false
			}

			const capabilities = getCapabilities()
			const publicSharing = capabilities.files_sharing?.public || {}
			return publicSharing.enabled === true
		},

		canReshare() {
			return !!(this.fileInfo.permissions & OC.PERMISSION_SHARE)
				|| !!(this.reshare && this.reshare.hasSharePermission && this.config.isResharingAllowed)
		},

		internalShareInputPlaceholder() {
			return this.config.showFederatedSharesAsInternal
				? t('files_sharing', 'Share with accounts, teams, federated cloud IDs')
				: t('files_sharing', 'Share with accounts and teams')
		},

		externalShareInputPlaceholder() {
			if (!this.isLinkSharingAllowed) {
				return t('files_sharing', 'Federated cloud ID')
			}
			return this.config.showFederatedSharesAsInternal
				? t('files_sharing', 'Email')
				: t('files_sharing', 'Email, federated cloud ID')
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

				for (const share of shares) {
					if ([ShareType.Link, ShareType.Email].includes(share.type)) {
						this.linkShares.push(share)
					} else if ([ShareType.Remote, ShareType.RemoteGroup].includes(share.type)) {
						if (this.config.showFederatedSharesAsInternal) {
							this.shares.push(share)
						} else {
							this.externalShares.push(share)
						}
					} else {
						this.shares.push(share)
					}
				}

				logger.debug(`Processed ${this.linkShares.length} link share(s)`)
				logger.debug(`Processed ${this.shares.length} share(s)`)
				logger.debug(`Processed ${this.externalShares.length} external share(s)`)
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
		addShare(share, resolve = () => { }) {
			// only catching share type MAIL as link shares are added differently
			// meaning: not from the ShareInput
			if (share.type === ShareType.Email) {
				this.linkShares.unshift(share)
			} else if ([ShareType.Remote, ShareType.RemoteGroup].includes(share.type)) {
				if (this.config.showFederatedSharesAsInternal) {
					this.shares.unshift(share)
				} else {
					this.externalShares.unshift(share)
				}
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
		awaitForShare(share, resolve) {
			this.$nextTick(() => {
				let listComponent = this.$refs.shareList
				// Only mail shares comes from the input, link shares
				// are managed internally in the SharingLinkList component
				if (share.type === ShareType.Email) {
					listComponent = this.$refs.linkShareList
				}
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

		section {
			padding-bottom: 16px;

			.section-header {
				margin-top: 2px;
				margin-bottom: 2px;
				display: flex;
				align-items: center;
				padding-bottom: 4px;

				h4 {
					margin: 0;
					font-size: 16px;
				}

				.visually-hidden {
					display: none;
				}

				.hint-icon {
					color: var(--color-primary-element);
				}

			}

		}

		& > section:not(:last-child) {
			border-bottom: 2px solid var(--color-border);
		}

	}

	&__additionalContent {
		margin: 44px 0;
	}
}

.hint-body {
	max-width: 300px;
	padding: var(--border-radius-element);
}
</style>
