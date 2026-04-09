<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<li class="sharing-entry">
		<NcAvatar
			class="sharing-entry__avatar"
			:is-no-user="share.type !== ShareType.User"
			:user="share.shareWith"
			:display-name="share.shareWithDisplayName"
			menu-position="left"
			:url="share.shareWithAvatar" />

		<div class="sharing-entry__summary">
			<component
				:is="share.shareWithLink ? 'a' : 'div'"
				:title="tooltip"
				:aria-label="tooltip"
				:href="share.shareWithLink"
				class="sharing-entry__summary__desc">
				<span>{{ title }}
					<span v-if="!isUnique" class="sharing-entry__summary__desc-unique">
						({{ share.shareWithDisplayNameUnique }})
					</span>
					<small v-if="hasStatus && share.status.message">({{ share.status.message }})</small>
				</span>
			</component>
			<SharingEntryQuickShareSelect
				:share="share"
				:file-info="fileInfo"
				@open-sharing-details="openShareDetailsForCustomSettings(share)" />
		</div>
		<ShareExpiryTime v-if="share && share.expireDate" :share="share" />
		<NcButton
			v-if="share.canEdit"
			class="sharing-entry__action"
			data-cy-files-sharing-share-actions
			:aria-label="t('files_sharing', 'Open Sharing Details')"
			variant="tertiary"
			@click="openSharingDetails(share)">
			<template #icon>
				<DotsHorizontalIcon :size="20" />
			</template>
		</NcButton>
	</li>
</template>

<script>
import { ShareType } from '@nextcloud/sharing'
import NcAvatar from '@nextcloud/vue/components/NcAvatar'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import DotsHorizontalIcon from 'vue-material-design-icons/DotsHorizontal.vue'
import ShareExpiryTime from './ShareExpiryTime.vue'
import SharingEntryQuickShareSelect from './SharingEntryQuickShareSelect.vue'
import ShareDetails from '../mixins/ShareDetails.js'
import SharesMixin from '../mixins/SharesMixin.js'

export default {
	name: 'SharingEntry',

	components: {
		NcButton,
		NcAvatar,
		DotsHorizontalIcon,
		NcSelect,
		ShareExpiryTime,
		SharingEntryQuickShareSelect,
	},

	mixins: [SharesMixin, ShareDetails],

	computed: {
		title() {
			let title = this.share.shareWithDisplayName

			const showAsInternal = this.config.showFederatedSharesAsInternal
				|| (this.share.isTrustedServer && this.config.showFederatedSharesToTrustedServersAsInternal)

			if (this.share.type === ShareType.Group || (this.share.type === ShareType.RemoteGroup && showAsInternal)) {
				title += ` (${t('files_sharing', 'group')})`
			} else if (this.share.type === ShareType.Room) {
				title += ` (${t('files_sharing', 'conversation')})`
			} else if (this.share.type === ShareType.Remote && !showAsInternal) {
				title += ` (${t('files_sharing', 'remote')})`
			} else if (this.share.type === ShareType.RemoteGroup) {
				title += ` (${t('files_sharing', 'remote group')})`
			} else if (this.share.type === ShareType.Guest) {
				title += ` (${t('files_sharing', 'guest')})`
			}
			if (!this.isShareOwner && this.share.ownerDisplayName) {
				title += ' ' + t('files_sharing', 'by {initiator}', {
					initiator: this.share.ownerDisplayName,
				})
			}
			return title
		},

		tooltip() {
			if (this.share.owner !== this.share.uidFileOwner) {
				const data = {
					// todo: strong or italic?
					// but the t function escape any html from the data :/
					user: this.share.shareWithDisplayName,
					owner: this.share.ownerDisplayName,
				}
				if (this.share.type === ShareType.Group) {
					return t('files_sharing', 'Shared with the group {user} by {owner}', data)
				} else if (this.share.type === ShareType.Room) {
					return t('files_sharing', 'Shared with the conversation {user} by {owner}', data)
				}

				return t('files_sharing', 'Shared with {user} by {owner}', data)
			}
			return null
		},

		/**
		 * @return {boolean}
		 */
		hasStatus() {
			if (this.share.type !== ShareType.User) {
				return false
			}

			return (typeof this.share.status === 'object' && !Array.isArray(this.share.status))
		},
	},

	methods: {
		/**
		 * Save potential changed data on menu close
		 */
		onMenuClose() {
			this.onNoteSubmit()
		},
	},
}
</script>

<style lang="scss" scoped>
.sharing-entry {
	display: flex;
	align-items: center;
	height: 44px;
	&__summary {
		padding: 8px;
		padding-inline-start: 10px;
		display: flex;
		flex-direction: column;
		justify-content: center;
		align-items: flex-start;
		flex: 1 0;
		min-width: 0;

		&__desc {
			display: inline-block;
			padding-bottom: 0;
			line-height: 1.2em;
			white-space: nowrap;
			overflow: hidden;
			text-overflow: ellipsis;

			p,
			small {
				color: var(--color-text-maxcontrast);
			}

			&-unique {
				color: var(--color-text-maxcontrast);
			}
		}
	}

}
</style>
