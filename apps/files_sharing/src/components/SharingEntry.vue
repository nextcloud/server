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
	<li class="sharing-entry">
		<NcAvatar class="sharing-entry__avatar"
			:is-no-user="share.type !== SHARE_TYPES.SHARE_TYPE_USER"
			:user="share.shareWith"
			:display-name="share.shareWithDisplayName"
			:menu-position="'left'"
			:url="share.shareWithAvatar" />

		<div class="sharing-entry__summary" @click.prevent="toggleQuickShareSelect">
			<component :is="share.shareWithLink ? 'a' : 'div'"
				:title="tooltip"
				:aria-label="tooltip"
				:href="share.shareWithLink"
				class="sharing-entry__desc">
				<span>{{ title }}<span v-if="!isUnique" class="sharing-entry__desc-unique"> ({{
					share.shareWithDisplayNameUnique }})</span></span>
				<p v-if="hasStatus">
					<span>{{ share.status.icon || '' }}</span>
					<span>{{ share.status.message || '' }}</span>
				</p>
			</component>
			<QuickShareSelect :share="share"
				:file-info="fileInfo"
				:toggle="showDropdown"
				@open-sharing-details="openShareDetailsForCustomSettings(share)" />
		</div>
		<NcButton class="sharing-entry__action"
			:aria-label="t('files_sharing', 'Open Sharing Details')"
			type="tertiary-no-background"
			@click="openSharingDetails(share)">
			<template #icon>
				<DotsHorizontalIcon :size="20" />
			</template>
		</NcButton>
	</li>
</template>

<script>
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcSelect from '@nextcloud/vue/dist/Components/NcSelect.js'
import NcAvatar from '@nextcloud/vue/dist/Components/NcAvatar.js'
import DotsHorizontalIcon from 'vue-material-design-icons/DotsHorizontal.vue'

import QuickShareSelect from './SharingEntryQuickShareSelect.vue'

import SharesMixin from '../mixins/SharesMixin.js'
import ShareDetails from '../mixins/ShareDetails.js'

export default {
	name: 'SharingEntry',

	components: {
		NcButton,
		NcAvatar,
		DotsHorizontalIcon,
		NcSelect,
		QuickShareSelect,
	},

	mixins: [SharesMixin, ShareDetails],

	data() {
		return {
			showDropdown: false,
		}
	},
	computed: {
		title() {
			let title = this.share.shareWithDisplayName
			if (this.share.type === this.SHARE_TYPES.SHARE_TYPE_GROUP) {
				title += ` (${t('files_sharing', 'group')})`
			} else if (this.share.type === this.SHARE_TYPES.SHARE_TYPE_ROOM) {
				title += ` (${t('files_sharing', 'conversation')})`
			} else if (this.share.type === this.SHARE_TYPES.SHARE_TYPE_REMOTE) {
				title += ` (${t('files_sharing', 'remote')})`
			} else if (this.share.type === this.SHARE_TYPES.SHARE_TYPE_REMOTE_GROUP) {
				title += ` (${t('files_sharing', 'remote group')})`
			} else if (this.share.type === this.SHARE_TYPES.SHARE_TYPE_GUEST) {
				title += ` (${t('files_sharing', 'guest')})`
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
				if (this.share.type === this.SHARE_TYPES.SHARE_TYPE_GROUP) {
					return t('files_sharing', 'Shared with the group {user} by {owner}', data)
				} else if (this.share.type === this.SHARE_TYPES.SHARE_TYPE_ROOM) {
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
			if (this.share.type !== this.SHARE_TYPES.SHARE_TYPE_USER) {
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
		toggleQuickShareSelect() {
			this.showDropdown = !this.showDropdown
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
		padding-bottom: 0;
		line-height: 1.2em;

		p {
			color: var(--color-text-maxcontrast);
		}

		&-unique {
			color: var(--color-text-maxcontrast);
		}
	}

	&__actions {
		margin-left: auto;
	}

	&__summary {
		padding: 8px;
		display: flex;
		flex-direction: column;
		justify-content: center;
		width: 100%;
	}

}
</style>
