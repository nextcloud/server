<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div class="sharingTabDetailsView">
		<div class="sharingTabDetailsView__header">
			<span>
				<NcAvatar v-if="isUserShare"
					class="sharing-entry__avatar"
					:is-no-user="share.shareType !== ShareType.User"
					:user="share.shareWith"
					:display-name="share.shareWithDisplayName"
					:menu-position="'left'"
					:url="share.shareWithAvatar" />
				<component :is="getShareTypeIcon(share.type)" :size="32" />
			</span>
			<span>
				<h1>{{ title }}</h1>
			</span>
		</div>
		<div class="sharingTabDetailsView__wrapper">
			<div ref="quickPermissions" class="sharingTabDetailsView__quick-permissions">
				<div>
					<NcCheckboxRadioSwitch :button-variant="true"
						data-cy-files-sharing-share-permissions-bundle="read-only"
						:checked.sync="sharingPermission"
						:value="bundledPermissions.READ_ONLY.toString()"
						name="sharing_permission_radio"
						type="radio"
						button-variant-grouped="vertical"
						@update:checked="toggleCustomPermissions">
						{{ t('files_sharing', 'View only') }}
						<template #icon>
							<ViewIcon :size="20" />
						</template>
					</NcCheckboxRadioSwitch>
					<NcCheckboxRadioSwitch :button-variant="true"
						data-cy-files-sharing-share-permissions-bundle="upload-edit"
						:checked.sync="sharingPermission"
						:value="bundledPermissions.ALL.toString()"
						name="sharing_permission_radio"
						type="radio"
						button-variant-grouped="vertical"
						@update:checked="toggleCustomPermissions">
						<template v-if="allowsFileDrop">
							{{ t('files_sharing', 'Allow upload and editing') }}
						</template>
						<template v-else>
							{{ t('files_sharing', 'Allow editing') }}
						</template>
						<template #icon>
							<EditIcon :size="20" />
						</template>
					</NcCheckboxRadioSwitch>
					<NcCheckboxRadioSwitch v-if="allowsFileDrop"
						data-cy-files-sharing-share-permissions-bundle="file-drop"
						:button-variant="true"
						:checked.sync="sharingPermission"
						:value="bundledPermissions.FILE_DROP.toString()"
						name="sharing_permission_radio"
						type="radio"
						button-variant-grouped="vertical"
						@update:checked="toggleCustomPermissions">
						{{ t('files_sharing', 'File request') }}
						<small class="subline">{{ t('files_sharing', 'Upload only') }}</small>
						<template #icon>
							<UploadIcon :size="20" />
						</template>
					</NcCheckboxRadioSwitch>
					<NcCheckboxRadioSwitch :button-variant="true"
						data-cy-files-sharing-share-permissions-bundle="custom"
						:checked.sync="sharingPermission"
						:value="'custom'"
						name="sharing_permission_radio"
						type="radio"
						button-variant-grouped="vertical"
						@update:checked="expandCustomPermissions">
						{{ t('files_sharing', 'Custom permissions') }}
						<small class="subline">{{ customPermissionsList }}</small>
						<template #icon>
							<DotsHorizontalIcon :size="20" />
						</template>
					</NcCheckboxRadioSwitch>
				</div>
			</div>
			<div class="sharingTabDetailsView__advanced-control">
				<NcButton id="advancedSectionAccordionAdvancedControl"
					type="tertiary"
					alignment="end-reverse"
					aria-controls="advancedSectionAccordionAdvanced"
					:aria-expanded="advancedControlExpandedValue"
					@click="advancedSectionAccordionExpanded = !advancedSectionAccordionExpanded">
					{{ t('files_sharing', 'Advanced settings') }}
					<template #icon>
						<MenuDownIcon v-if="!advancedSectionAccordionExpanded" />
						<MenuUpIcon v-else />
					</template>
				</NcButton>
			</div>
			<div v-if="advancedSectionAccordionExpanded"
				id="advancedSectionAccordionAdvanced"
				class="sharingTabDetailsView__advanced"
				aria-labelledby="advancedSectionAccordionAdvancedControl"
				role="region">
				<section>
					<NcInputField v-if="isPublicShare"
						class="sharingTabDetailsView__label"
						autocomplete="off"
						:label="t('files_sharing', 'Share label')"
						:value.sync="share.label" />
					<NcInputField v-if="config.allowCustomTokens && isPublicShare && !isNewShare"
						autocomplete="off"
						:label="t('files_sharing', 'Share link token')"
						:helper-text="t('files_sharing', 'Set the public share link token to something easy to remember or generate a new token. It is not recommended to use a guessable token for shares which contain sensitive information.')"
						show-trailing-button
						:trailing-button-label="loadingToken ? t('files_sharing', 'Generating…') : t('files_sharing', 'Generate new token')"
						:value.sync="share.token"
						@trailing-button-click="generateNewToken">
						<template #trailing-button-icon>
							<NcLoadingIcon v-if="loadingToken" />
							<Refresh v-else :size="20" />
						</template>
					</NcInputField>
					<template v-if="isPublicShare">
						<NcCheckboxRadioSwitch :checked.sync="isPasswordProtected" :disabled="isPasswordEnforced">
							{{ t('files_sharing', 'Set password') }}
						</NcCheckboxRadioSwitch>
						<NcPasswordField v-if="isPasswordProtected"
							autocomplete="new-password"
							:value="hasUnsavedPassword ? share.newPassword : ''"
							:error="passwordError"
							:helper-text="errorPasswordLabel || passwordHint"
							:required="isPasswordEnforced && isNewShare"
							:label="t('files_sharing', 'Password')"
							@update:value="onPasswordChange" />

						<!-- Migrate icons and remote -> icon="icon-info"-->
						<span v-if="isEmailShareType && passwordExpirationTime" icon="icon-info">
							{{ t('files_sharing', 'Password expires {passwordExpirationTime}', { passwordExpirationTime }) }}
						</span>
						<span v-else-if="isEmailShareType && passwordExpirationTime !== null" icon="icon-error">
							{{ t('files_sharing', 'Password expired') }}
						</span>
					</template>
					<NcCheckboxRadioSwitch v-if="canTogglePasswordProtectedByTalkAvailable"
						:checked.sync="isPasswordProtectedByTalk"
						@update:checked="onPasswordProtectedByTalkChange">
						{{ t('files_sharing', 'Video verification') }}
					</NcCheckboxRadioSwitch>
					<NcCheckboxRadioSwitch :checked.sync="hasExpirationDate" :disabled="isExpiryDateEnforced">
						{{ isExpiryDateEnforced
							? t('files_sharing', 'Expiration date (enforced)')
							: t('files_sharing', 'Set expiration date') }}
					</NcCheckboxRadioSwitch>
					<NcDateTimePickerNative v-if="hasExpirationDate"
						id="share-date-picker"
						:value="new Date(share.expireDate ?? dateTomorrow)"
						:min="dateTomorrow"
						:max="maxExpirationDateEnforced"
						hide-label
						:label="t('files_sharing', 'Expiration date')"
						:placeholder="t('files_sharing', 'Expiration date')"
						type="date"
						@input="onExpirationChange" />
					<NcCheckboxRadioSwitch v-if="isPublicShare"
						:disabled="canChangeHideDownload"
						:checked.sync="share.hideDownload"
						@update:checked="queueUpdate('hideDownload')">
						{{ t('files_sharing', 'Hide download') }}
					</NcCheckboxRadioSwitch>
					<NcCheckboxRadioSwitch v-else
						:disabled="!canSetDownload"
						:checked.sync="canDownload"
						data-cy-files-sharing-share-permissions-checkbox="download">
						{{ t('files_sharing', 'Allow download and sync') }}
					</NcCheckboxRadioSwitch>
					<NcCheckboxRadioSwitch :checked.sync="writeNoteToRecipientIsChecked">
						{{ t('files_sharing', 'Note to recipient') }}
					</NcCheckboxRadioSwitch>
					<template v-if="writeNoteToRecipientIsChecked">
						<NcTextArea :label="t('files_sharing', 'Note to recipient')"
							:placeholder="t('files_sharing', 'Enter a note for the share recipient')"
							:value.sync="share.note" />
					</template>
					<NcCheckboxRadioSwitch v-if="isPublicShare && isFolder"
						:checked.sync="showInGridView">
						{{ t('files_sharing', 'Show files in grid view') }}
					</NcCheckboxRadioSwitch>
					<ExternalShareAction v-for="action in externalLinkActions"
						:id="action.id"
						ref="externalLinkActions"
						:key="action.id"
						:action="action"
						:file-info="fileInfo"
						:share="share" />
					<NcCheckboxRadioSwitch :checked.sync="setCustomPermissions">
						{{ t('files_sharing', 'Custom permissions') }}
					</NcCheckboxRadioSwitch>
					<section v-if="setCustomPermissions" class="custom-permissions-group">
						<NcCheckboxRadioSwitch :disabled="!canRemoveReadPermission"
							:checked.sync="hasRead"
							data-cy-files-sharing-share-permissions-checkbox="read">
							{{ t('files_sharing', 'Read') }}
						</NcCheckboxRadioSwitch>
						<NcCheckboxRadioSwitch v-if="isFolder"
							:disabled="!canSetCreate"
							:checked.sync="canCreate"
							data-cy-files-sharing-share-permissions-checkbox="create">
							{{ t('files_sharing', 'Create') }}
						</NcCheckboxRadioSwitch>
						<NcCheckboxRadioSwitch :disabled="!canSetEdit"
							:checked.sync="canEdit"
							data-cy-files-sharing-share-permissions-checkbox="update">
							{{ t('files_sharing', 'Edit') }}
						</NcCheckboxRadioSwitch>
						<NcCheckboxRadioSwitch v-if="resharingIsPossible"
							:disabled="!canSetReshare"
							:checked.sync="canReshare"
							data-cy-files-sharing-share-permissions-checkbox="share">
							{{ t('files_sharing', 'Share') }}
						</NcCheckboxRadioSwitch>
						<NcCheckboxRadioSwitch :disabled="!canSetDelete"
							:checked.sync="canDelete"
							data-cy-files-sharing-share-permissions-checkbox="delete">
							{{ t('files_sharing', 'Delete') }}
						</NcCheckboxRadioSwitch>
					</section>
					<div class="sharingTabDetailsView__delete">
						<NcButton v-if="!isNewShare"
							:aria-label="t('files_sharing', 'Delete share')"
							:disabled="false"
							:readonly="false"
							type="tertiary"
							@click.prevent="removeShare">
							<template #icon>
								<CloseIcon :size="16" />
							</template>
							{{ t('files_sharing', 'Delete share') }}
						</NcButton>
					</div>
				</section>
			</div>
		</div>

		<div class="sharingTabDetailsView__footer">
			<div class="button-group">
				<NcButton data-cy-files-sharing-share-editor-action="cancel"
					@click="cancel">
					{{ t('files_sharing', 'Cancel') }}
				</NcButton>
				<NcButton type="primary"
					data-cy-files-sharing-share-editor-action="save"
					:disabled="creating"
					@click="saveShare">
					{{ shareButtonText }}
					<template v-if="creating" #icon>
						<NcLoadingIcon />
					</template>
				</NcButton>
			</div>
		</div>
	</div>
</template>

<script>
import { emit } from '@nextcloud/event-bus'
import { getLanguage } from '@nextcloud/l10n'
import { ShareType } from '@nextcloud/sharing'
import { showError } from '@nextcloud/dialogs'
import moment from '@nextcloud/moment'

import NcAvatar from '@nextcloud/vue/components/NcAvatar'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import NcDateTimePickerNative from '@nextcloud/vue/components/NcDateTimePickerNative'
import NcInputField from '@nextcloud/vue/components/NcInputField'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import NcPasswordField from '@nextcloud/vue/components/NcPasswordField'
import NcTextArea from '@nextcloud/vue/components/NcTextArea'

import CircleIcon from 'vue-material-design-icons/CircleOutline.vue'
import CloseIcon from 'vue-material-design-icons/Close.vue'
import EditIcon from 'vue-material-design-icons/Pencil.vue'
import EmailIcon from 'vue-material-design-icons/Email.vue'
import LinkIcon from 'vue-material-design-icons/Link.vue'
import GroupIcon from 'vue-material-design-icons/AccountGroup.vue'
import ShareIcon from 'vue-material-design-icons/ShareCircle.vue'
import UserIcon from 'vue-material-design-icons/AccountCircleOutline.vue'
import ViewIcon from 'vue-material-design-icons/Eye.vue'
import UploadIcon from 'vue-material-design-icons/Upload.vue'
import MenuDownIcon from 'vue-material-design-icons/MenuDown.vue'
import MenuUpIcon from 'vue-material-design-icons/MenuUp.vue'
import DotsHorizontalIcon from 'vue-material-design-icons/DotsHorizontal.vue'
import Refresh from 'vue-material-design-icons/Refresh.vue'

import ExternalShareAction from '../components/ExternalShareAction.vue'

import GeneratePassword from '../utils/GeneratePassword.ts'
import Share from '../models/Share.ts'
import ShareRequests from '../mixins/ShareRequests.js'
import SharesMixin from '../mixins/SharesMixin.js'
import { generateToken } from '../services/TokenService.ts'
import logger from '../services/logger.ts'

import {
	ATOMIC_PERMISSIONS,
	BUNDLED_PERMISSIONS,
	hasPermissions,
} from '../lib/SharePermissionsToolBox.js'

export default {
	name: 'SharingDetailsTab',
	components: {
		NcAvatar,
		NcButton,
		NcCheckboxRadioSwitch,
		NcDateTimePickerNative,
		NcInputField,
		NcLoadingIcon,
		NcPasswordField,
		NcTextArea,
		CloseIcon,
		CircleIcon,
		EditIcon,
		ExternalShareAction,
		LinkIcon,
		GroupIcon,
		ShareIcon,
		UserIcon,
		UploadIcon,
		ViewIcon,
		MenuDownIcon,
		MenuUpIcon,
		DotsHorizontalIcon,
		Refresh,
	},
	mixins: [ShareRequests, SharesMixin],
	props: {
		shareRequestValue: {
			type: Object,
			required: false,
		},
		fileInfo: {
			type: Object,
			required: true,
		},
		share: {
			type: Object,
			required: true,
		},
	},
	data() {
		return {
			writeNoteToRecipientIsChecked: false,
			sharingPermission: BUNDLED_PERMISSIONS.ALL.toString(),
			revertSharingPermission: BUNDLED_PERMISSIONS.ALL.toString(),
			setCustomPermissions: false,
			passwordError: false,
			advancedSectionAccordionExpanded: false,
			bundledPermissions: BUNDLED_PERMISSIONS,
			isFirstComponentLoad: true,
			test: false,
			creating: false,
			initialToken: this.share.token,
			loadingToken: false,

			ExternalShareActions: OCA.Sharing.ExternalShareActions.state,
		}
	},

	computed: {
		title() {
			switch (this.share.type) {
			case ShareType.User:
				return t('files_sharing', 'Share with {userName}', { userName: this.share.shareWithDisplayName })
			case ShareType.Email:
			    return t('files_sharing', 'Share with email {email}', { email: this.share.shareWith })
			case ShareType.Link:
				return t('files_sharing', 'Share link')
			case ShareType.Group:
				return t('files_sharing', 'Share with group')
			case ShareType.Room:
				return t('files_sharing', 'Share in conversation')
			case ShareType.Remote: {
				const [user, server] = this.share.shareWith.split('@')
				return t('files_sharing', 'Share with {user} on remote server {server}', { user, server })
			}
			case ShareType.RemoteGroup:
				return t('files_sharing', 'Share with remote group')
			case ShareType.Guest:
				return t('files_sharing', 'Share with guest')
			default: {
				if (this.share.id) {
					// Share already exists
					return t('files_sharing', 'Update share')
				} else {
					return t('files_sharing', 'Create share')
				}
			}
			}
		},
		/**
		 * Can the sharee edit the shared file ?
		 */
		canEdit: {
			get() {
				return this.share.hasUpdatePermission
			},
			set(checked) {
				this.updateAtomicPermissions({ isEditChecked: checked })
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
				this.updateAtomicPermissions({ isCreateChecked: checked })
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
				this.updateAtomicPermissions({ isDeleteChecked: checked })
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
				this.updateAtomicPermissions({ isReshareChecked: checked })
			},
		},

		/**
		 * Change the default view for public shares from "list" to "grid"
		 */
		showInGridView: {
			get() {
				return this.getShareAttribute('config', 'grid_view', false)
			},
			/** @param {boolean} value If the default view should be changed to "grid" */
			set(value) {
				this.setShareAttribute('config', 'grid_view', value)
			},
		},

		/**
		 * Can the sharee download files or only view them ?
		 */
		canDownload: {
			get() {
				return this.getShareAttribute('permissions', 'download', true)
			},
			set(checked) {
				this.setShareAttribute('permissions', 'download', checked)
			},
		},
		/**
		 * Is this share readable
		 * Needed for some federated shares that might have been added from file requests links
		 */
		hasRead: {
			get() {
				return this.share.hasReadPermission
			},
			set(checked) {
				this.updateAtomicPermissions({ isReadChecked: checked })
			},
		},
		/**
		 * Does the current share have an expiration date
		 *
		 * @return {boolean}
		 */
		hasExpirationDate: {
			get() {
				return this.isValidShareAttribute(this.share.expireDate)
			},
			set(enabled) {
				this.share.expireDate = enabled
					? this.formatDateToString(this.defaultExpiryDate)
					: ''
			},
		},
		/**
		 * Is the current share password protected ?
		 *
		 * @return {boolean}
		 */
		isPasswordProtected: {
			get() {
				return this.config.enforcePasswordForPublicLink
					|| !!this.share.password
			},
			async set(enabled) {
				if (enabled) {
					this.share.password = await GeneratePassword(true)
					this.$set(this.share, 'newPassword', this.share.password)
				} else {
					this.share.password = ''
					this.$delete(this.share, 'newPassword')
				}
			},
		},
		/**
		 * Is the current share a folder ?
		 *
		 * @return {boolean}
		 */
		isFolder() {
			return this.fileInfo.type === 'dir'
		},
		/**
		 * @return {boolean}
		 */
		isSetDownloadButtonVisible() {
			const allowedMimetypes = [
				// Office documents
				'application/msword',
				'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
				'application/vnd.ms-powerpoint',
				'application/vnd.openxmlformats-officedocument.presentationml.presentation',
				'application/vnd.ms-excel',
				'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
				'application/vnd.oasis.opendocument.text',
				'application/vnd.oasis.opendocument.spreadsheet',
				'application/vnd.oasis.opendocument.presentation',
			]

			return this.isFolder || allowedMimetypes.includes(this.fileInfo.mimetype)
		},
		isPasswordEnforced() {
			return this.isPublicShare && this.config.enforcePasswordForPublicLink
		},
		defaultExpiryDate() {
			if ((this.isGroupShare || this.isUserShare) && this.config.isDefaultInternalExpireDateEnabled) {
				return new Date(this.config.defaultInternalExpirationDate)
			} else if (this.isRemoteShare && this.config.isDefaultRemoteExpireDateEnabled) {
				return new Date(this.config.defaultRemoteExpireDateEnabled)
			} else if (this.isPublicShare && this.config.isDefaultExpireDateEnabled) {
				return new Date(this.config.defaultExpirationDate)
			}
			return new Date(new Date().setDate(new Date().getDate() + 1))
		},
		isUserShare() {
			return this.share.type === ShareType.User
		},
		isGroupShare() {
			return this.share.type === ShareType.Group
		},
		allowsFileDrop() {
			if (this.isFolder && this.config.isPublicUploadEnabled) {
				if (this.share.type === ShareType.Link || this.share.type === ShareType.Email) {
					return true
				}
			}
			return false
		},
		hasFileDropPermissions() {
			return this.share.permissions === this.bundledPermissions.FILE_DROP
		},
		shareButtonText() {
			if (this.isNewShare) {
				return t('files_sharing', 'Save share')
			}
			return t('files_sharing', 'Update share')

		},
		resharingIsPossible() {
			return this.config.isResharingAllowed && this.share.type !== ShareType.Link && this.share.type !== ShareType.Email
		},
		/**
		 * Can the sharer set whether the sharee can edit the file ?
		 *
		 * @return {boolean}
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
		 * @return {boolean}
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
		 * @return {boolean}
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
		 * @return {boolean}
		 */
		canSetReshare() {
			// If the owner revoked the permission after the resharer granted it
			// the share still has the permission, and the resharer is still
			// allowed to revoke it too (but not to grant it again).
			return (this.fileInfo.sharePermissions & OC.PERMISSION_SHARE) || this.canReshare
		},
		/**
		 * Can the sharer set whether the sharee can download the file ?
		 *
		 * @return {boolean}
		 */
		canSetDownload() {
			// If the owner revoked the permission after the resharer granted it
			// the share still has the permission, and the resharer is still
			// allowed to revoke it too (but not to grant it again).
			return (this.fileInfo.canDownload() || this.canDownload)
		},
		canRemoveReadPermission() {
			return this.allowsFileDrop && (
				this.share.type === ShareType.Link
					|| this.share.type === ShareType.Email
			)
		},
		// if newPassword exists, but is empty, it means
		// the user deleted the original password
		hasUnsavedPassword() {
			return this.share.newPassword !== undefined
		},
		passwordExpirationTime() {
			if (!this.isValidShareAttribute(this.share.passwordExpirationTime)) {
				return null
			}

			const expirationTime = moment(this.share.passwordExpirationTime)

			if (expirationTime.diff(moment()) < 0) {
				return false
			}

			return expirationTime.fromNow()
		},

		/**
		 * Is Talk enabled?
		 *
		 * @return {boolean}
		 */
		isTalkEnabled() {
			return OC.appswebroots.spreed !== undefined
		},

		/**
		 * Is it possible to protect the password by Talk?
		 *
		 * @return {boolean}
		 */
		isPasswordProtectedByTalkAvailable() {
			return this.isPasswordProtected && this.isTalkEnabled
		},
		/**
		 * Is the current share password protected by Talk?
		 *
		 * @return {boolean}
		 */
		isPasswordProtectedByTalk: {
			get() {
				return this.share.sendPasswordByTalk
			},
			async set(enabled) {
				this.share.sendPasswordByTalk = enabled
			},
		},
		/**
		 * Is the current share an email share ?
		 *
		 * @return {boolean}
		 */
		isEmailShareType() {
			return this.share
				? this.share.type === ShareType.Email
				: false
		},
		canTogglePasswordProtectedByTalkAvailable() {
			if (!this.isPublicShare || !this.isPasswordProtected) {
				// Makes no sense
				return false
			} else if (this.isEmailShareType && !this.hasUnsavedPassword) {
				// For email shares we need a new password in order to enable or
				// disable
				return false
			}

			// Is Talk enabled?
			return OC.appswebroots.spreed !== undefined
		},
		canChangeHideDownload() {
			const hasDisabledDownload = (shareAttribute) => shareAttribute.key === 'download' && shareAttribute.scope === 'permissions' && shareAttribute.value === false
			return this.fileInfo.shareAttributes.some(hasDisabledDownload)
		},
		customPermissionsList() {
			// Key order will be different, because ATOMIC_PERMISSIONS are numbers
			const translatedPermissions = {
				[ATOMIC_PERMISSIONS.READ]: this.t('files_sharing', 'Read'),
				[ATOMIC_PERMISSIONS.CREATE]: this.t('files_sharing', 'Create'),
				[ATOMIC_PERMISSIONS.UPDATE]: this.t('files_sharing', 'Edit'),
				[ATOMIC_PERMISSIONS.SHARE]: this.t('files_sharing', 'Share'),
				[ATOMIC_PERMISSIONS.DELETE]: this.t('files_sharing', 'Delete'),
			}

			return [ATOMIC_PERMISSIONS.READ, ATOMIC_PERMISSIONS.CREATE, ATOMIC_PERMISSIONS.UPDATE, ...(this.resharingIsPossible ? [ATOMIC_PERMISSIONS.SHARE] : []), ATOMIC_PERMISSIONS.DELETE]
				.filter((permission) => hasPermissions(this.share.permissions, permission))
				.map((permission, index) => index === 0
					? translatedPermissions[permission]
					: translatedPermissions[permission].toLocaleLowerCase(getLanguage()))
				.join(', ')
		},
		advancedControlExpandedValue() {
			return this.advancedSectionAccordionExpanded ? 'true' : 'false'
		},
		errorPasswordLabel() {
			if (this.passwordError) {
				return t('files_sharing', 'Password field cannot be empty')
			}
			return undefined
		},

		passwordHint() {
			if (this.isNewShare || this.hasUnsavedPassword) {
				return undefined
			}
			return t('files_sharing', 'Replace current password')
		},

		/**
		 * Additional actions for the menu
		 *
		 * @return {Array}
		 */
		externalLinkActions() {
			const filterValidAction = (action) => (action.shareType.includes(ShareType.Link) || action.shareType.includes(ShareType.Email)) && action.advanced
			// filter only the advanced registered actions for said link
			return this.ExternalShareActions.actions
				.filter(filterValidAction)
		},
	},
	watch: {
		setCustomPermissions(isChecked) {
			if (isChecked) {
				this.sharingPermission = 'custom'
			} else {
				this.sharingPermission = this.revertSharingPermission
			}
		},
	},
	beforeMount() {
		this.initializePermissions()
		this.initializeAttributes()
		logger.debug('Share object received', { share: this.share })
		logger.debug('Configuration object received', { config: this.config })
	},

	mounted() {
		this.$refs.quickPermissions?.querySelector('input:checked')?.focus()
	},

	methods: {
		/**
		 * Set a share attribute on the current share
		 * @param {string} scope The attribute scope
		 * @param {string} key The attribute key
		 * @param {boolean} value The value
		 */
		setShareAttribute(scope, key, value) {
			if (!this.share.attributes) {
				this.$set(this.share, 'attributes', [])
			}

			const attribute = this.share.attributes
				.find((attr) => attr.scope === scope || attr.key === key)

			if (attribute) {
				attribute.value = value
			} else {
				this.share.attributes.push({
					scope,
					key,
					value,
				})
			}
		},

		/**
		 * Get the value of a share attribute
		 * @param {string} scope The attribute scope
		 * @param {string} key The attribute key
		 * @param {undefined|boolean} fallback The fallback to return if not found
		 */
		getShareAttribute(scope, key, fallback = undefined) {
			const attribute = this.share.attributes?.find((attr) => attr.scope === scope && attr.key === key)
			return attribute?.value ?? fallback
		},

		async generateNewToken() {
			if (this.loadingToken) {
				return
			}
			this.loadingToken = true
			try {
				this.share.token = await generateToken()
			} catch (error) {
				showError(t('files_sharing', 'Failed to generate a new token'))
			}
			this.loadingToken = false
		},

		cancel() {
			this.share.token = this.initialToken
			this.$emit('close-sharing-details')
		},

		updateAtomicPermissions({
			isReadChecked = this.hasRead,
			isEditChecked = this.canEdit,
			isCreateChecked = this.canCreate,
			isDeleteChecked = this.canDelete,
			isReshareChecked = this.canReshare,
		} = {}) {
			// calc permissions if checked
			const permissions = 0
				| (isReadChecked ? ATOMIC_PERMISSIONS.READ : 0)
				| (isCreateChecked ? ATOMIC_PERMISSIONS.CREATE : 0)
				| (isDeleteChecked ? ATOMIC_PERMISSIONS.DELETE : 0)
				| (isEditChecked ? ATOMIC_PERMISSIONS.UPDATE : 0)
				| (isReshareChecked ? ATOMIC_PERMISSIONS.SHARE : 0)
			this.share.permissions = permissions
		},
		expandCustomPermissions() {
			if (!this.advancedSectionAccordionExpanded) {
				this.advancedSectionAccordionExpanded = true
			}
			this.toggleCustomPermissions()
		},
		toggleCustomPermissions(selectedPermission) {
			const isCustomPermissions = this.sharingPermission === 'custom'
			this.revertSharingPermission = !isCustomPermissions ? selectedPermission : 'custom'
			this.setCustomPermissions = isCustomPermissions
		},
		async initializeAttributes() {

			if (this.isNewShare) {
				if (this.isPasswordEnforced && this.isPublicShare) {
					this.$set(this.share, 'newPassword', await GeneratePassword(true))
					this.advancedSectionAccordionExpanded = true
				}
				/* Set default expiration dates if configured */
				if (this.isPublicShare && this.config.isDefaultExpireDateEnabled) {
					this.share.expireDate = this.config.defaultExpirationDate.toDateString()
				} else if (this.isRemoteShare && this.config.isDefaultRemoteExpireDateEnabled) {
					this.share.expireDate = this.config.defaultRemoteExpirationDateString.toDateString()
				} else if (this.config.isDefaultInternalExpireDateEnabled) {
					this.share.expireDate = this.config.defaultInternalExpirationDate.toDateString()
				}

				if (this.isValidShareAttribute(this.share.expireDate)) {
					this.advancedSectionAccordionExpanded = true
				}

				return
			}

			// If there is an enforced expiry date, then existing shares created before enforcement
			// have no expiry date, hence we set it here.
			if (!this.isValidShareAttribute(this.share.expireDate) && this.isExpiryDateEnforced) {
				this.hasExpirationDate = true
			}

			if (
				this.isValidShareAttribute(this.share.password)
				|| this.isValidShareAttribute(this.share.expireDate)
				|| this.isValidShareAttribute(this.share.label)
			) {
				this.advancedSectionAccordionExpanded = true
			}

			if (this.share.note) {
				this.writeNoteToRecipientIsChecked = true
			}

		},
		handleShareType() {
			if ('shareType' in this.share) {
				this.share.type = this.share.shareType
			} else if (this.share.share_type) {
				this.share.type = this.share.share_type
			}
		},
		handleDefaultPermissions() {
			if (this.isNewShare) {
				const defaultPermissions = this.config.defaultPermissions
				if (defaultPermissions === BUNDLED_PERMISSIONS.READ_ONLY || defaultPermissions === BUNDLED_PERMISSIONS.ALL) {
					this.sharingPermission = defaultPermissions.toString()
				} else {
					this.sharingPermission = 'custom'
					this.share.permissions = defaultPermissions
					this.advancedSectionAccordionExpanded = true
					this.setCustomPermissions = true
				}
			}
			// Read permission required for share creation
			if (!this.canRemoveReadPermission) {
				this.hasRead = true
			}
		},
		handleCustomPermissions() {
			if (!this.isNewShare && (this.hasCustomPermissions || this.share.setCustomPermissions)) {
				this.sharingPermission = 'custom'
				this.advancedSectionAccordionExpanded = true
				this.setCustomPermissions = true
			} else if (this.share.permissions) {
				this.sharingPermission = this.share.permissions.toString()
			}
		},
		initializePermissions() {
			this.handleShareType()
			this.handleDefaultPermissions()
			this.handleCustomPermissions()
		},
		async saveShare() {
			const permissionsAndAttributes = ['permissions', 'attributes', 'note', 'expireDate']
			const publicShareAttributes = ['label', 'password', 'hideDownload']
			if (this.config.allowCustomTokens) {
				publicShareAttributes.push('token')
			}
			if (this.isPublicShare) {
				permissionsAndAttributes.push(...publicShareAttributes)
			}
			const sharePermissionsSet = parseInt(this.sharingPermission)
			if (this.setCustomPermissions) {
				this.updateAtomicPermissions()
			} else {
				this.share.permissions = sharePermissionsSet
			}

			if (!this.isFolder && this.share.permissions === BUNDLED_PERMISSIONS.ALL) {
				// It's not possible to create an existing file.
				this.share.permissions = BUNDLED_PERMISSIONS.ALL_FILE
			}
			if (!this.writeNoteToRecipientIsChecked) {
				this.share.note = ''
			}
			if (this.isPasswordProtected) {
				if (this.hasUnsavedPassword && this.isValidShareAttribute(this.share.newPassword)) {
					this.share.password = this.share.newPassword
					this.$delete(this.share, 'newPassword')
				} else if (this.isPasswordEnforced && this.isNewShare && !this.isValidShareAttribute(this.share.password)) {
					this.passwordError = true
				}
			} else {
				this.share.password = ''
			}

			if (!this.hasExpirationDate) {
				this.share.expireDate = ''
			}

			if (this.isNewShare) {
				const incomingShare = {
					permissions: this.share.permissions,
					shareType: this.share.type,
					shareWith: this.share.shareWith,
					attributes: this.share.attributes,
					note: this.share.note,
					fileInfo: this.fileInfo,
				}

				incomingShare.expireDate = this.hasExpirationDate ? this.share.expireDate : ''

				if (this.isPasswordProtected) {
					incomingShare.password = this.share.password
				}

				let share
				try {
					this.creating = true
					share = await this.addShare(incomingShare)
				} catch (error) {
					this.creating = false
					// Error is already handled by ShareRequests mixin
					return
				}

				// ugly hack to make code work - we need the id to be set but at the same time we need to keep values we want to update
				this.share._share.id = share.id
				await this.queueUpdate(...permissionsAndAttributes)
				// Also a ugly hack to update the updated permissions
				for (const prop of permissionsAndAttributes) {
					if (prop in share && prop in this.share) {
						try {
							share[prop] = this.share[prop]
						} catch {
							share._share[prop] = this.share[prop]
						}
					}
				}

				this.share = share
				this.creating = false
				this.$emit('add:share', this.share)
			} else {
				// Let's update after creation as some attrs are only available after creation
				this.$emit('update:share', this.share)
				emit('update:share', this.share)
				this.queueUpdate(...permissionsAndAttributes)
			}

			await this.getNode()
			emit('files:node:updated', this.node)

			if (this.$refs.externalLinkActions?.length > 0) {
				await Promise.allSettled(this.$refs.externalLinkActions.map((action) => {
					if (typeof action.$children.at(0)?.onSave !== 'function') {
						return Promise.resolve()
					}
					return action.$children.at(0)?.onSave?.()
				}))
			}

			this.$emit('close-sharing-details')
		},
		/**
		 * Process the new share request
		 *
		 * @param {Share} share incoming share object
		 */
		async addShare(share) {
			logger.debug('Adding a new share from the input for', { share })
			const path = this.path
			try {
				const resultingShare = await this.createShare({
					path,
					shareType: share.shareType,
					shareWith: share.shareWith,
					permissions: share.permissions,
					expireDate: share.expireDate,
					attributes: JSON.stringify(share.attributes),
					...(share.note ? { note: share.note } : {}),
					...(share.password ? { password: share.password } : {}),
				})
				return resultingShare
			} catch (error) {
				logger.error('Error while adding new share', { error })
			} finally {
				// this.loading = false // No loader here yet
			}
		},
		async removeShare() {
			await this.onDelete()
			await this.getNode()
			emit('files:node:updated', this.node)
			this.$emit('close-sharing-details')
		},
		/**
		 * Update newPassword values
		 * of share. If password is set but not newPassword
		 * then the user did not changed the password
		 * If both co-exists, the password have changed and
		 * we show it in plain text.
		 * Then on submit (or menu close), we sync it.
		 *
		 * @param {string} password the changed password
		 */
		onPasswordChange(password) {
			if (password === '') {
				this.$delete(this.share, 'newPassword')
				this.passwordError = this.isNewShare && this.isPasswordEnforced
				return
			}
			this.passwordError = !this.isValidShareAttribute(password)
			this.$set(this.share, 'newPassword', password)
		},
		/**
		 * Update the password along with "sendPasswordByTalk".
		 *
		 * If the password was modified the new password is sent; otherwise
		 * updating a mail share would fail, as in that case it is required that
		 * a new password is set when enabling or disabling
		 * "sendPasswordByTalk".
		 */
		onPasswordProtectedByTalkChange() {
			if (this.hasUnsavedPassword) {
				this.share.password = this.share.newPassword.trim()
			}

			this.queueUpdate('sendPasswordByTalk', 'password')
		},
		isValidShareAttribute(value) {
			if ([null, undefined].includes(value)) {
				return false
			}

			if (!(value.trim().length > 0)) {
				return false
			}

			return true
		},
		getShareTypeIcon(type) {
			switch (type) {
			case ShareType.Link:
				return LinkIcon
			case ShareType.Guest:
				return UserIcon
			case ShareType.RemoteGroup:
			case ShareType.Group:
				return GroupIcon
			case ShareType.Email:
				return EmailIcon
			case ShareType.Team:
				return CircleIcon
			case ShareType.Room:
				return ShareIcon
			case ShareType.Deck:
				return ShareIcon
			case ShareType.ScienceMesh:
				return ShareIcon
			default:
				return null // Or a default icon component if needed
			}
		},
	},
}
</script>

<style lang="scss" scoped>
.sharingTabDetailsView {
	display: flex;
	flex-direction: column;
	width: 100%;
	margin: 0 auto;
	position: relative;
	height: 100%;
	overflow: hidden;

	&__header {
		display: flex;
		align-items: center;
		box-sizing: border-box;
		margin: 0.2em;

		span {
			display: flex;
			align-items: center;

			h1 {
				font-size: 15px;
				padding-inline-start: 0.3em;
			}

		}
	}

	&__wrapper {
		position: relative;
		overflow: scroll;
		flex-shrink: 1;
		padding: 4px;
		padding-inline-end: 12px;
	}

	&__quick-permissions {
		display: flex;
		justify-content: center;
		width: 100%;
		margin: 0 auto;
		border-radius: 0;

		div {
			width: 100%;

			span {
				width: 100%;

				span:nth-child(1) {
					align-items: center;
					justify-content: center;
					padding: 0.1em;
				}

				:deep(label span) {
					display: flex;
					flex-direction: column;
				}

				/* Target component based style in NcCheckboxRadioSwitch slot content*/
				:deep(span.checkbox-content__text.checkbox-radio-switch__text) {
					flex-wrap: wrap;

					.subline {
						display: block;
						flex-basis: 100%;
					}
				}
			}

		}
	}

	&__advanced-control {
		width: 100%;

		button {
			margin-top: 0.5em;
		}

	}

	&__advanced {
		width: 100%;
		margin-bottom: 0.5em;
		text-align: start;
		padding-inline-start: 0;

		section {

			textarea,
			div.mx-datepicker {
				width: 100%;
			}

			textarea {
				height: 80px;
				margin: 0;
			}

			/*
			  The following style is applied out of the component's scope
			  to remove padding from the label.checkbox-radio-switch__label,
			  which is used to group radio checkbox items. The use of ::v-deep
			  ensures that the padding is modified without being affected by
			  the component's scoping.
			  Without this achieving left alignment for the checkboxes would not
			  be possible.
			*/
			span :deep(label) {
				padding-inline-start: 0 !important;
				background-color: initial !important;
				border: none !important;
			}

			section.custom-permissions-group {
				padding-inline-start: 1.5em;
			}
		}
	}

	&__label {
		padding-block-end: 6px;
	}

	&__delete {
		> button:first-child {
			color: rgb(223, 7, 7);
		}
	}

	&__footer {
		width: 100%;
		display: flex;
		position: sticky;
		bottom: 0;
		flex-direction: column;
		justify-content: space-between;
		align-items: flex-start;
		background: linear-gradient(to bottom, rgba(255, 255, 255, 0), var(--color-main-background));

		.button-group {
			display: flex;
			justify-content: space-between;
			width: 100%;
			margin-top: 16px;

			button {
				margin-inline-start: 16px;

				&:first-child {
					margin-inline-start: 0;
				}
			}
		}
	}
}
</style>
