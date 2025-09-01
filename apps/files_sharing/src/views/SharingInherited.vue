<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<ul id="sharing-inherited-shares">
		<!-- Main collapsible entry -->
		<SharingEntrySimple class="sharing-entry__inherited__header"
			:title="subTitle">
			<template #avatar>
				<DotsHorizontal class="sharing-entry__inherited__icon" />
			</template>
			<NcActionButton :disabled="true">
				<template #icon>
					<NcLoadingIcon v-if="loading" />
				</template>
			</NcActionButton>
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
import NcActionButton from '@nextcloud/vue/components/NcActionButton'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import axios from '@nextcloud/axios'
import DotsHorizontal from 'vue-material-design-icons/DotsHorizontal.vue'

import Share from '../models/Share.ts'
import SharingEntryInherited from '../components/SharingEntryInherited.vue'
import SharingEntrySimple from '../components/SharingEntrySimple.vue'

export default {
	name: 'SharingInherited',

	components: {
		NcActionButton,
		NcLoadingIcon,
		DotsHorizontal,
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
			loading: true,
			shares: [],
		}
	},
	computed: {
		subTitle() {
			if (this.loading || this.shares.length > 0) {
				return t('files_sharing', 'Others with access')
			} else {
				return t('files_sharing', 'No other accounts with access found')
			}
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

	mounted() {
		this.fetchInheritedShares()
	},

	methods: {
		/**
		 * Fetch the Inherited Shares array
		 */
		async fetchInheritedShares() {
			try {
				const url = generateOcsUrl('apps/files_sharing/api/v1/shares/inherited?format=json&path={path}', { path: this.fullPath })
				const shares = await axios.get(url)
				this.shares = shares.data.ocs.data
					.map(share => new Share(share))
					.sort((a, b) => b.createdTime - a.createdTime)
				console.info(this.shares)
			} catch (error) {
				OC.Notification.showTemporary(t('files_sharing', 'Unable to fetch inherited shares'), { type: 'error' })
			} finally {
				this.loading = false
			}
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
	&__header :deep(.sharing-entry__title) {
		color: var(--color-text-maxcontrast);
	}

	&__icon {
		color: var(--color-text-maxcontrast);
	}
}
</style>
