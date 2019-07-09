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
	<Tab :icon="icon" :name="name" :class="{'icon-loading': loading}">
		<!-- error message -->
		<div v-if="error" class="emptycontent">
			<div class="icon icon-error"></div>
			<h2>{{ error }}</h2>
		</div>

		<!-- shares content -->
		<template v-else>
			<SharingEntry v-if="isSharedWithMe" v-bind="sharedWithMe"></SharingEntry>
			<SharingInput :shares="shares" :file-info="fileInfo" :reshare="reshare" />
		</template>

		<collection-list v-if="fileInfo" type="file" :id="`${fileInfo.id}`" :name="fileInfo.name"></collection-list>
	</Tab>
</template>

<script>
import { generateOcsUrl } from 'nextcloud-router/dist/index'
import Tab from 'nextcloud-vue/dist/Components/AppSidebarTab'
import axios from 'nextcloud-axios'

import { shareWithTitle } from '../utils/SharedWithMe'
import Share from '../models/Share'
import SharingEntry from '../components/SharingEntry'
import SharingInput from '../components/SharingInput'
import { CollectionList } from 'nextcloud-vue-collections'

export default {
	name: 'SharingTab',

	components: {
		SharingEntry,
		SharingInput,
		Tab,
		CollectionList
	},

	props: {
		fileInfo: {
			type: Object,
			default: () => {},
			required: true
		}
	},

	data() {
		return {
			error: '',
			expirationInterval: null,
			icon: 'icon-share',
			loading: true,
			name: t('files_sharing', 'Sharing'),
			// reshare Share object
			reshare: null,
			sharedWithMe: {},
			shares: []
		}
	},

	computed: {
		/**
		 * Needed to differenciate the tabs
		 * pulled from the AppSidebarTab component
		 * 
		 * @returns {string}
		 */
		id() {
			return this.name.toLowerCase().replace(/ /g, '-')
		},

		/**
		 * Returns the current active tab
		 * needed because AppSidebarTab also uses $parent.activeTab
		 *
		 * @returns {string}
		 */
		activeTab() {
			return this.$parent.activeTab
		},

		/**
		 * Is this share shared with me?
		 *
		 * @returns {boolean}
		 */
		isSharedWithMe() {
			return Object.keys(this.sharedWithMe).length > 0
		}
	},

	mounted() {
		this.getShares()
	},

	watch: {
		fileInfo() {
			this.resetState()
			this.getShares()
		}
	},

	methods: {
		/**
		 * Get the existing shares infos
		 */
		async getShares() {
			try {
				this.loading = true

				// init params
				const shareUrl = generateOcsUrl('apps/files_sharing/api/v1', 2) + 'shares'
				const format = 'json'
				const path = this.fileInfo.path + this.fileInfo.name

				// fetch shares
				const fetchShares = axios.get(shareUrl, {
					params: {
						format, path,
						reshare: true
					}
				})
				const fetchSharedWithMe = axios.get(shareUrl, {
					params: {
						format, path,
						shared_with_me: true
					}
				})

				// wait for data
				const [shares, sharedWithMe] = await Promise.all([fetchShares, fetchSharedWithMe])
				this.loading = false

				// process results
				this.processSharedWithMe(sharedWithMe)
				this.processShares(shares)
			} catch (error) {
				this.error = t('files_sharing', 'Unable to load the shares list')
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
		},

		/**
		 * Update sharedWithMe.subtitle with the appropriate
		 * expiration time left
		 * 
		 * @param {Share} share the sharedWith Share object
		 */
		updateExpirationSubtitle(share) {
			const expiration = moment(share.expiration).unix()
			this.$set(this.sharedWithMe, 'subtitle', t('files_sharing', 'expire {relativetime}', {
				relativetime: OC.Util.relativeModifiedDate(expiration * 1000)
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
		 * @param {Object} share the share ocs api request data
		 * @param {Object} share.data the request data
		 */
		processShares({ data }) {
			if (data.ocs && data.ocs.data && data.ocs.data.length > 0) {
				this.shares = data.ocs.data.map(share => new Share(share))
			}
		},

		/**
		 * Process the sharedWithMe share data
		 * and init sharedWithMe
		 *
		 * @param {Object} share the share ocs api request data
		 * @param {Object} share.data the request data
		 */
		processSharedWithMe({ data }) {
			if (data.ocs && data.ocs.data && data.ocs.data[0]) {
				const share = new Share(data)
				const title = shareWithTitle(share)
				const displayName = share.ownerDisplayName
				const user = share.owner

				console.info(share);

				this.sharedWithMe = {
					displayName,
					title,
					user
				}
				this.reshare = share

				// If we have an expiration date, use it as subtitle
				// Refresh the status every 10s and clear if expired
				if (share.expiration && moment(share.expiration).unix() > moment().unix()) {
					// first update
					this.updateExpirationSubtitle(share)
					// interval update
					this.expirationInterval = setInterval(this.updateExpirationSubtitle, 10000, share)
				}
			}
		}
	}
}
</script>

<style lang="scss" scoped>

</style>

