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
			ref="defaultShareEntryRef"
			:can-reshare="canReshare"
			:file-info="fileInfo"
			@add:share="(share, resolve) => $emit('add:share', share, resolve)"
			@update:share="(share, resolve) => $emit('update:share', share, resolve)"
			@open-sharing-details="openSharingDetails(share)" />

		<!-- Else we display the list -->
		<template v-if="hasShares">
			<!-- using shares[index] to work with .sync -->
			<SharingEntryLink v-for="(share, index) in shares"
				:ref="(el) => { if (el && share && share.id) shareEntryRefs[share.id] = el }"
				:key="share.id"
				:index="shares.length > 1 ? index + 1 : null"
				:can-reshare="canReshare"
				:share="share"
				:file-info="fileInfo"
				@add:share="(share, resolve) => $emit('add:share', share, resolve)"
				@update:share="(share, resolve) => $emit('update:share', share, resolve)"
				@remove:share="(share) => $emit('remove:share', share)"
				@open-sharing-details="openSharingDetails(share)" />
		</template>
	</ul>
</template>

<script>
import { getCapabilities } from '@nextcloud/capabilities'

import { t } from '@nextcloud/l10n'

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
			shareEntryRefs: {},
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

	beforeUpdate() {
		// Clear refs before each update to ensure they are current
		this.shareEntryRefs = {}
	},

	methods: {
		t,
		getShareEntryComponent(shareId) {
			if (shareId) {
				return this.shareEntryRefs[shareId]
			}
			// For the case when a new share is added and it's the first one (default entry was shown)
			return this.$refs.defaultShareEntryRef
		},
	},
}
</script>
