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
		<Avatar class="sharing-entry__avatar"
			:is-no-user="share.type !== SHARE_TYPES.SHARE_TYPE_USER"
			:user="share.shareWith"
			:display-name="share.shareWithDisplayName"
			:tooltip-message="share.type === SHARE_TYPES.SHARE_TYPE_USER ? share.shareWith : ''"
			:menu-position="'left'"
			:url="share.shareWithAvatar" />
		<component :is="share.shareWithLink ? 'a' : 'div'"
			v-tooltip.auto="tooltip"
			:href="share.shareWithLink"
			class="sharing-entry__desc">
			<h5>{{ title }}<span v-if="!isUnique" class="sharing-entry__desc-unique"> ({{ share.shareWithDisplayNameUnique }})</span></h5>
			<p v-if="hasStatus">
				<span>{{ share.status.icon || '' }}</span>
				<span>{{ share.status.message || '' }}</span>
			</p>
		</component>
		<Actions v-if="open === false"
			menu-align="right"
			class="sharing-entry__actions"
			@close="onMenuClose">
			<ActionButton v-if="share.canEdit"
				icon="icon-settings"
				:disabled="saving"
				:share="share"
				@click.prevent="editPermissions">
				{{ t('files_sharing', 'Advanced permission') }}
			</ActionButton>
			<ActionButton v-if="share.canEdit"
				icon="icon-mail"
				:disabled="saving"
				:share="share"
				@click.prevent="editNotes">
				{{ t('files_sharing', 'Send new mail') }}
			</ActionButton>
			<ActionButton v-if="share.canDelete"
				icon="icon-close"
				:disabled="saving"
				@click.prevent="onDelete">
				{{ t('files_sharing', 'Unshare') }}
			</ActionButton>
		</Actions>
	</li>
</template>

<script>
import Avatar from '@nextcloud/vue/dist/Components/Avatar'
import Actions from '@nextcloud/vue/dist/Components/Actions'
import ActionButton from '@nextcloud/vue/dist/Components/ActionButton'
import ActionCheckbox from '@nextcloud/vue/dist/Components/ActionCheckbox'
import ActionInput from '@nextcloud/vue/dist/Components/ActionInput'
import ActionTextEditable from '@nextcloud/vue/dist/Components/ActionTextEditable'
import Tooltip from '@nextcloud/vue/dist/Directives/Tooltip'

import SharesMixin from '../mixins/SharesMixin'

export default {
	name: 'SharingEntry',

	components: {
		Actions,
		ActionButton,
		ActionCheckbox,
		ActionInput,
		ActionTextEditable,
		Avatar,
	},

	directives: {
		Tooltip,
	},

	mixins: [SharesMixin],

	data() {
		return {
			permissionsEdit: OC.PERMISSION_UPDATE,
			permissionsCreate: OC.PERMISSION_CREATE,
			permissionsDelete: OC.PERMISSION_DELETE,
			permissionsRead: OC.PERMISSION_READ,
			permissionsShare: OC.PERMISSION_SHARE,

			publicUploadRWValue: OC.PERMISSION_UPDATE | OC.PERMISSION_CREATE | OC.PERMISSION_READ | OC.PERMISSION_DELETE,
			publicUploadRValue: OC.PERMISSION_READ,
			publicUploadWValue: OC.PERMISSION_CREATE,
			publicUploadEValue: OC.PERMISSION_UPDATE | OC.PERMISSION_READ,
		}
	},

	computed: {
		/**
		 * Return the current share permissions
		 * We always ignore the SHARE permission as this is used for the
		 * federated sharing.
		 * @returns {number}
		 */
		sharePermissions() {
			return this.share.permissions & ~OC.PERMISSION_SHARE
		},

		/**
		 * Generate a unique random id for this SharingEntry only
		 * @returns {string}
		 */
		randomId() {
			return Math.random().toString(27).substr(2)
		},

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

		canHaveNote() {
			return !this.isRemote
		},

		isRemote() {
			return this.share.type === this.SHARE_TYPES.SHARE_TYPE_REMOTE
				|| this.share.type === this.SHARE_TYPES.SHARE_TYPE_REMOTE_GROUP
		},

		/**
		 * Can the sharer set whether the sharee can edit the file ?
		 *
		 * @returns {boolean}
		 */
		canSetEdit() {
			// If the owner revoked the permission after the resharer granted it
			// the share still has the permission, and the resharer is still
			// allowed to revoke it too (but not to grant it again).
			return (this.fileInfo.sharePermissions & OC.PERMISSION_UPDATE) || this.canEdit
		},

		/**
		 * Can the sharer set whether the sharee can create the file ?
		 *
		 * @returns {boolean}
		 */
		canSetCreate() {
			// If the owner revoked the permission after the resharer granted it
			// the share still has the permission, and the resharer is still
			// allowed to revoke it too (but not to grant it again).
			return (this.fileInfo.sharePermissions & OC.PERMISSION_CREATE) || this.canCreate
		},

		/**
		 * Can the sharer set whether the sharee can delete the file ?
		 *
		 * @returns {boolean}
		 */
		canSetDelete() {
			// If the owner revoked the permission after the resharer granted it
			// the share still has the permission, and the resharer is still
			// allowed to revoke it too (but not to grant it again).
			return (this.fileInfo.sharePermissions & OC.PERMISSION_DELETE) || this.canDelete
		},

		/**
		 * Can the sharer set whether the sharee can reshare the file ?
		 *
		 * @returns {boolean}
		 */
		canSetReshare() {
			// If the owner revoked the permission after the resharer granted it
			// the share still has the permission, and the resharer is still
			// allowed to revoke it too (but not to grant it again).
			return (this.fileInfo.sharePermissions & OC.PERMISSION_SHARE) || this.canReshare
		},

		/**
		 * Can the sharee edit the shared file ?
		 */
		canEdit: {
			get() {
				return this.share.hasUpdatePermission
			},
			set(checked) {
				this.updatePermissions({ isEditChecked: checked })
			},
		},

		/**
		 * Can the sharee create the shared file ?
		 */
		canCreate: {
			get() {
				return this.share.hasCreatePermission
			},
			set(checked) {
				this.updatePermissions({ isCreateChecked: checked })
			},
		},

		/**
		 * Can the sharee delete the shared file ?
		 */
		canDelete: {
			get() {
				return this.share.hasDeletePermission
			},
			set(checked) {
				this.updatePermissions({ isDeleteChecked: checked })
			},
		},

		/**
		 * Can the sharee reshare the file ?
		 */
		canReshare: {
			get() {
				return this.share.hasSharePermission
			},
			set(checked) {
				this.updatePermissions({ isReshareChecked: checked })
			},
		},

		/**
		 * Is this share readable
		 * Needed for some federated shares that might have been added from file drop links
		 */
		hasRead: {
			get() {
				return this.share.hasReadPermission
			},
		},

		/**
		 * Is the current share a folder ?
		 * @returns {boolean}
		 */
		isFolder() {
			return this.fileInfo.type === 'dir'
		},

		/**
		 * Does the current share have an expiration date
		 * @returns {boolean}
		 */
		// hasExpirationDate: {
		// 	get() {
		// 		return this.config.isDefaultInternalExpireDateEnforced || !!this.share.expireDate
		// 	},
		// 	set(enabled) {
		// 		this.share.expireDate = enabled
		// 			? this.config.defaultInternalExpirationDateString !== ''
		// 				? this.config.defaultInternalExpirationDateString
		// 				: moment().format('YYYY-MM-DD')
		// 			: ''
		// 	},
		// },

		// dateMaxEnforced() {
		// 	if (!this.isRemote) {
		// 		return this.config.isDefaultInternalExpireDateEnforced
		// 			&& moment().add(1 + this.config.defaultInternalExpireDate, 'days')
		// 	} else {
		// 		return this.config.isDefaultRemoteExpireDateEnforced
		// 			&& moment().add(1 + this.config.defaultRemoteExpireDate, 'days')
		// 	}
		// },

		/**
		 * @returns {bool}
		 */
		hasStatus() {
			if (this.share.type !== this.SHARE_TYPES.SHARE_TYPE_USER) {
				return false
			}

			return (typeof this.share.status === 'object' && !Array.isArray(this.share.status))
		},

	},

	methods: {
		updatePermissions({ isEditChecked = this.canEdit, isCreateChecked = this.canCreate, isDeleteChecked = this.canDelete, isReshareChecked = this.canReshare } = {}) {
			// calc permissions if checked
			const permissions = 0
				| (this.hasRead ? this.permissionsRead : 0)
				| (isCreateChecked ? this.permissionsCreate : 0)
				| (isDeleteChecked ? this.permissionsDelete : 0)
				| (isEditChecked ? this.permissionsEdit : 0)
				| (isReshareChecked ? this.permissionsShare : 0)

			this.share.permissions = permissions
			this.queueUpdate('permissions')
		},

		/**
		 * Save potential changed data on menu close
		 */
		onMenuClose() {
			this.onNoteSubmit()
		},

		editPermissions() {
			this.$store.commit('addFromInput', false)
			this.$store.commit('addShare', this.share)
			this.$store.commit('addCurrentTab', 'permissions')
		},

		editNotes() {
			this.$store.commit('addFromInput', false)
			this.$store.commit('addShare', this.share)
			this.$store.commit('addCurrentTab', 'notes')
		},
	},
}
</script>

<style lang="scss" scoped>
.sharing-entry {
	display: flex;
	align-items: center;
	min-height: 44px;
	&__desc {
		display: flex;
		flex-direction: column;
		justify-content: space-between;
		padding: 8px;
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
}
</style>
