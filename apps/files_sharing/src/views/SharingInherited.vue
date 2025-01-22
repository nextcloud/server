<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<ul v-if="shares.length" id="sharing-inherited-shares">
		<!-- Main collapsible entry -->
		<SharingEntrySimple class="sharing-entry__inherited"
			:title="mainTitle"
			:subtitle="subTitle"
			:aria-expanded="showInheritedShares">
			<template #avatar>
				<div class="avatar-shared icon-more-white" />
			</template>
			<NcActionButton :icon="showInheritedSharesIcon"
				:aria-label="toggleTooltip"
				:title="toggleTooltip"
				@click.prevent.stop="toggleInheritedShares" />
		</SharingEntrySimple>

		<!-- Inherited shares list -->
		<SharingEntryInherited v-for="share in shares"
			:key="share.id"
			:file-info="fileInfo"
			:share="share"
			@remove:share="removeShare" />
	</ul>
</template>

<script>
import { generateOcsUrl } from '@nextcloud/router'
import NcActionButton from '@nextcloud/vue/dist/Components/NcActionButton.js'
import axios from '@nextcloud/axios'

import Share from '../models/Share.ts'
import SharingEntryInherited from '../components/SharingEntryInherited.vue'
import SharingEntrySimple from '../components/SharingEntrySimple.vue'

export default {
	name: 'SharingInherited',

	components: {
		NcActionButton,
		SharingEntryInherited,
		SharingEntrySimple,
	},

	props: {
		fileInfo: {
			type: Object,
			default: () => {},
			required: true,
		},
	},

	data() {
		return {
			loaded: false,
			loading: false,
			showInheritedShares: false,
			shares: [],
		}
	},
	computed: {
		showInheritedSharesIcon() {
			if (this.loading) {
				return 'icon-loading-small'
			}
			if (this.showInheritedShares) {
				return 'icon-triangle-n'
			}
			return 'icon-triangle-s'
		},
		mainTitle() {
			return t('files_sharing', 'Others with access')
		},
		subTitle() {
			return (this.showInheritedShares && this.shares.length === 0)
				? t('files_sharing', 'No other accounts with access found')
				: ''
		},
		toggleTooltip() {
			return this.fileInfo.type === 'dir'
				? t('files_sharing', 'Toggle list of others with access to this directory')
				: t('files_sharing', 'Toggle list of others with access to this file')
		},
		fullPath() {
			const path = `${this.fileInfo.path}/${this.fileInfo.name}`
			return path.replace('//', '/')
		},
	},
	watch: {
		fileInfo() {
			this.resetState()
		},
	},
	methods: {
		/**
		 * Toggle the list view and fetch/reset the state
		 */
		toggleInheritedShares() {
			this.showInheritedShares = !this.showInheritedShares
			if (this.showInheritedShares) {
				this.fetchInheritedShares()
			} else {
				this.resetState()
			}
		},
		/**
		 * Fetch the Inherited Shares array
		 */
		async fetchInheritedShares() {
			this.loading = true
			try {
				const url = generateOcsUrl('apps/files_sharing/api/v1/shares/inherited?format=json&path={path}', { path: this.fullPath })
				const shares = await axios.get(url)
				this.shares = shares.data.ocs.data
					.map(share => new Share(share))
					.sort((a, b) => b.createdTime - a.createdTime)
				console.info(this.shares)
				this.loaded = true
			} catch (error) {
				OC.Notification.showTemporary(t('files_sharing', 'Unable to fetch inherited shares'), { type: 'error' })
			} finally {
				this.loading = false
			}
		},
		/**
		 * Reset current component state
		 */
		resetState() {
			this.loaded = false
			this.loading = false
			this.showInheritedShares = false
			this.shares = []
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

<style lang="scss" scoped>
.sharing-entry__inherited {
	.avatar-shared {
		width: 32px;
		height: 32px;
		line-height: 32px;
		font-size: 18px;
		background-color: var(--color-text-maxcontrast);
		border-radius: 50%;
		flex-shrink: 0;
	}
}
</style>
