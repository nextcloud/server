<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<SharingEntrySimple
		:key="share.id"
		class="sharing-entry__inherited"
		:title="share.shareWithDisplayName">
		<template #avatar>
			<NcAvatar
				:user="share.shareWith"
				:display-name="share.shareWithDisplayName"
				class="sharing-entry__avatar" />
		</template>
		<NcActionText icon="icon-user">
			{{ t('files_sharing', 'Added by {initiator}', { initiator: share.ownerDisplayName }) }}
		</NcActionText>
		<NcActionLink
			v-if="share.viaPath && share.viaFileid"
			icon="icon-folder"
			:href="viaFileTargetUrl">
			{{ t('files_sharing', 'Via “{folder}”', { folder: viaFolderName }) }}
		</NcActionLink>
		<NcActionButton
			v-if="share.canDelete"
			icon="icon-close"
			@click.prevent="onDelete">
			{{ t('files_sharing', 'Unshare') }}
		</NcActionButton>
	</SharingEntrySimple>
</template>

<script>
import { basename } from '@nextcloud/paths'
import NcActionButton from '@nextcloud/vue/components/NcActionButton'
import NcActionLink from '@nextcloud/vue/components/NcActionLink'
import NcActionText from '@nextcloud/vue/components/NcActionText'
import NcAvatar from '@nextcloud/vue/components/NcAvatar'
import SharingEntrySimple from '../components/SharingEntrySimple.vue'
import SharesMixin from '../mixins/SharesMixin.js'
import Share from '../models/Share.js'
import { generateFileUrl } from '../utils/generateUrl.js'

export default {
	name: 'SharingEntryInherited',

	components: {
		NcActionButton,
		NcActionLink,
		NcActionText,
		NcAvatar,
		SharingEntrySimple,
	},

	mixins: [SharesMixin],

	props: {
		share: {
			type: Share,
			required: true,
		},
	},

	computed: {
		viaFileTargetUrl() {
			return generateFileUrl(this.share.viaFileid)
		},

		viaFolderName() {
			return basename(this.share.viaPath)
		},
	},
}
</script>

<style lang="scss" scoped>
.sharing-entry {
	display: flex;
	align-items: center;
	height: 44px;
	&__desc {
		display: flex;
		flex-direction: column;
		justify-content: space-between;
		padding: 8px;
		padding-inline-start: 10px;
		line-height: 1.2em;
		p {
			color: var(--color-text-maxcontrast);
		}
	}
	&__actions {
		margin-inline-start: auto;
	}
}
</style>
