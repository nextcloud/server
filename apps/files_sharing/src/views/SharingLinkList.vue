<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<ul v-if="canLinkShare"
		:aria-label="t('files_sharing', 'Link shares')"
		class="sharing-link-list">
		<!-- If no link shares, show the add link default entry -->
		<SharingEntryLink v-if="!hasLinkShares && canReshare"
			:can-reshare="canReshare"
			:file-info="fileInfo"
			@add:share="addShare" />

		<!-- Else we display the list -->
		<template v-if="hasShares">
			<!-- using shares[index] to work with .sync -->
			<SharingEntryLink v-for="(share, index) in shares"
				:key="share.id"
				:index="shares.length > 1 ? index + 1 : null"
				:can-reshare="canReshare"
				:share.sync="shares[index]"
				:file-info="fileInfo"
				@add:share="addShare(...arguments)"
				@update:share="awaitForShare(...arguments)"
				@remove:share="removeShare"
				@open-sharing-details="openSharingDetails(share)" />
		</template>
	</ul>
</template>

<script>
import { getCapabilities } from '@nextcloud/capabilities'

import { t } from '@nextcloud/l10n'

import Share from '../models/Share.js'
import SharingEntryLink from '../components/SharingEntryLink.vue'
import ShareDetails from '../mixins/ShareDetails.js'
import { ShareType } from '@nextcloud/sharing'

export default {
	name: 'SharingLinkList',

	components: {
		SharingEntryLink,
	},

	mixins: [ShareDetails],

	props: {
		fileInfo: {
			type: Object,
			default: () => {},
			required: true,
		},
		shares: {
			type: Array,
			default: () => [],
			required: true,
		},
		canReshare: {
			type: Boolean,
			required: true,
		},
	},

	data() {
		return {
			canLinkShare: getCapabilities().files_sharing.public.enabled,
		}
	},

	computed: {
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
		t,

		/**
		 * Add a new share into the link shares list
		 * and return the newly created share component
		 *
		 * @param {Share} share the share to add to the array
		 * @param {Function} resolve a function to run after the share is added and its component initialized
		 */
		addShare(share, resolve) {
			// eslint-disable-next-line vue/no-mutating-props
			this.shares.push(share)
			this.awaitForShare(share, resolve)
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
				const newShare = this.$children.find(component => component.share === share)
				if (newShare) {
					resolve(newShare)
				}
			})
		},

		/**
		 * Remove a share from the shares list
		 *
		 * @param {Share} share the share to remove
		 */
		removeShare(share) {
			const index = this.shares.findIndex(item => item === share)
			// eslint-disable-next-line vue/no-mutating-props
			this.shares.splice(index, 1)
		},
	},
}
</script>
