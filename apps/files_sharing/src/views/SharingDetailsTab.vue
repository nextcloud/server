<template>
	<div class="sharingTabDetailsView">
		<div class="sharingTabDetailsView__header">
			<span>
				<NcAvatar v-if="isUserShare"
					class="sharing-entry__avatar"
					:is-no-user="share.shareType !== SHARE_TYPES.SHARE_TYPE_USER"
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
		<div class="sharingTabDetailsView__quick-permissions">
			<div>
				<NcCheckboxRadioSwitch :button-variant="true"
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
					:checked.sync="sharingPermission"
					:value="bundledPermissions.ALL.toString()"
					name="sharing_permission_radio"
					type="radio"
					button-variant-grouped="vertical"
					@update:checked="toggleCustomPermissions">
					{{ t('files_sharing', 'Allow upload and editing') }}
					<template #icon>
						<EditIcon :size="20" />
					</template>
				</NcCheckboxRadioSwitch>
				<NcCheckboxRadioSwitch v-if="allowsFileDrop"
					:button-variant="true"
					:checked.sync="sharingPermission"
					:value="bundledPermissions.FILE_DROP.toString()"
					name="sharing_permission_radio"
					type="radio"
					button-variant-grouped="vertical"
					@update:checked="toggleCustomPermissions">
					{{ t('files_sharing', 'File drop') }}
					<small>{{ t('files_sharing', 'Upload only') }}</small>
					<template #icon>
						<UploadIcon :size="20" />
					</template>
				</NcCheckboxRadioSwitch>
				<NcCheckboxRadioSwitch :button-variant="true"
					:checked.sync="sharingPermission"
					:value="'custom'"
					name="sharing_permission_radio"
					type="radio"
					button-variant-grouped="vertical"
					@update:checked="toggleCustomPermissions">
					{{ t('files_sharing', 'Custom permissions') }}
					<small>{{ t('files_sharing', customPermissionsList) }}</small>
					<template #icon>
						<DotsHorizontalIcon :size="20" />
					</template>
				</NcCheckboxRadioSwitch>
			</div>
		</div>
		<div class="sharingTabDetailsView__advanced-control">
			<NcButton type="tertiary"
				alignment="end-reverse"
				@click="advancedSectionAccordionExpanded = !advancedSectionAccordionExpanded">
				{{ t('files_sharing', 'Advanced settings') }}
				<template #icon>
					<MenuDownIcon />
				</template>
			</NcButton>
		</div>
		<div v-if="advancedSectionAccordionExpanded" class="sharingTabDetailsView__advanced">
			<section>
				<NcInputField v-if="isPublicShare"
					:value.sync="share.label"
					type="text"
					:label="t('file_sharing', 'Share label')" />
				<template v-if="isPublicShare">
					<NcCheckboxRadioSwitch :checked.sync="isPasswordProtected" :disabled="isPasswordEnforced">
						{{ t('file_sharing', 'Set password') }}
					</NcCheckboxRadioSwitch>
					<NcInputField v-if="isPasswordProtected"
						:type="hasUnsavedPassword ? 'text' : 'password'"
						:value="hasUnsavedPassword ? share.newPassword : '***************'"
						:error="passwordError"
						:required="isPasswordEnforced"
						:label="t('file_sharing', 'Password')"
						@update:value="onPasswordChange" />

					<!-- Migrate icons and remote -> icon="icon-info"-->
					<span v-if="isEmailShareType && passwordExpirationTime" icon="icon-info">
						{{ t('files_sharing', 'Password expires {passwordExpirationTime}', { passwordExpirationTime }) }}
					</span>
					<span v-else-if="isEmailShareType && passwordExpirationTime !== null" icon="icon-error">
						{{ t('files_sharing', 'Password expired') }}
					</span>
				</template>
				<NcCheckboxRadioSwitch :checked.sync="hasExpirationDate" :disabled="isExpiryDateEnforced">
					{{ isExpiryDateEnforced
						? t('files_sharing', 'Expiration date (enforced)')
						: t('files_sharing', 'Set expiration date') }}
				</NcCheckboxRadioSwitch>
				<NcDateTimePickerNative v-if="hasExpirationDate"
					id="share-date-picker"
					:value="new Date(share.expireDate)"
					:min="dateTomorrow"
					:max="dateMaxEnforced"
					:hide-label="true"
					:disabled="isExpiryDateEnforced"
					:placeholder="t('file_sharing', 'Expiration date')"
					type="date"
					@input="onExpirationChange" />
				<NcCheckboxRadioSwitch v-if="isPublicShare"
					:disabled="canChangeHideDownload"
					:checked.sync="share.hideDownload"
					@update:checked="queueUpdate('hideDownload')">
					{{ t('file_sharing', 'Hide download') }}
				</NcCheckboxRadioSwitch>
				<NcCheckboxRadioSwitch v-if="canTogglePasswordProtectedByTalkAvailable"
					:checked.sync="isPasswordProtectedByTalk"
					@update:checked="onPasswordProtectedByTalkChange">
					{{ t('file_sharing', 'Video verification') }}
				</NcCheckboxRadioSwitch>
				<NcCheckboxRadioSwitch :checked.sync="writeNoteToRecipientIsChecked">
					{{ t('file_sharing', 'Note to recipient') }}
				</NcCheckboxRadioSwitch>
				<template v-if="writeNoteToRecipientIsChecked">
					<textarea :value="share.note" @input="share.note = $event.target.value" />
				</template>
				<NcCheckboxRadioSwitch :checked.sync="setCustomPermissions">
					{{ t('file_sharing', 'Custom permissions') }}
				</NcCheckboxRadioSwitch>
				<section v-if="setCustomPermissions" class="custom-permissions-group">
					<NcCheckboxRadioSwitch :disabled="!allowsFileDrop && share.type === SHARE_TYPES.SHARE_TYPE_LINK" :checked.sync="hasRead">
						{{ t('file_sharing', 'Read') }}
					</NcCheckboxRadioSwitch>
					<NcCheckboxRadioSwitch v-if="isFolder" :disabled="!canSetCreate" :checked.sync="canCreate">
						{{ t('file_sharing', 'Create') }}
					</NcCheckboxRadioSwitch>
					<NcCheckboxRadioSwitch :disabled="!canSetEdit" :checked.sync="canEdit">
						{{ t('file_sharing', 'Update') }}
					</NcCheckboxRadioSwitch>
					<NcCheckboxRadioSwitch v-if="config.isResharingAllowed && share.type !== SHARE_TYPES.SHARE_TYPE_LINK" :disabled="!canSetReshare" :checked.sync="canReshare">
						{{ t('file_sharing', 'Share') }}
					</NcCheckboxRadioSwitch>
					<NcCheckboxRadioSwitch v-if="!isPublicShare" :disabled="!canSetDownload" :checked.sync="canDownload">
						{{ t('file_sharing', 'Download') }}
					</NcCheckboxRadioSwitch>
					<NcCheckboxRadioSwitch :disabled="!canSetDelete" :checked.sync="canDelete">
						{{ t('file_sharing', 'Delete') }}
					</NcCheckboxRadioSwitch>
				</section>
			</section>
		</div>

		<div class="sharingTabDetailsView__footer">
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
			<div class="button-group">
				<NcButton @click="$emit('close-sharing-details')">
					{{ t('file_sharing', 'Cancel') }}
				</NcButton>
				<NcButton type="primary" @click="saveShare">
					{{ shareButtonText }}
				</NcButton>
			</div>
		</div>
	</div>
</template>

<script>
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcInputField from '@nextcloud/vue/dist/Components/NcInputField.js'
import NcAvatar from '@nextcloud/vue/dist/Components/NcAvatar.js'
import NcDatetimePicker from '@nextcloud/vue/dist/Components/NcDatetimePicker.js'
import NcDateTimePickerNative from '@nextcloud/vue/dist/Components/NcDateTimePickerNative.js'
import NcCheckboxRadioSwitch from '@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.js'
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
import DotsHorizontalIcon from 'vue-material-design-icons/DotsHorizontal.vue'

import GeneratePassword from '../utils/GeneratePassword.js'
import Share from '../models/Share.js'
import ShareRequests from '../mixins/ShareRequests.js'
import ShareTypes from '../mixins/ShareTypes.js'
import SharesMixin from '../mixins/SharesMixin.js'

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
		NcInputField,
		NcDatetimePicker,
		NcDateTimePickerNative,
		NcCheckboxRadioSwitch,
		CloseIcon,
		CircleIcon,
		EditIcon,
		LinkIcon,
		GroupIcon,
		ShareIcon,
		UserIcon,
		UploadIcon,
		ViewIcon,
		MenuDownIcon,
		DotsHorizontalIcon,
	},
	mixins: [ShareTypes, ShareRequests, SharesMixin],
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
			revertSharingPermission: null,
			setCustomPermissions: false,
			passwordError: false,
			advancedSectionAccordionExpanded: false,
			bundledPermissions: BUNDLED_PERMISSIONS,
			isFirstComponentLoad: true,
			test: false,
		}
	},

	computed: {
		title() {
			let title = t('files_sharing', 'Share with ')
			if (this.share.type === this.SHARE_TYPES.SHARE_TYPE_USER) {
				title = title + this.share.shareWithDisplayName
			} else if (this.share.type === this.SHARE_TYPES.SHARE_TYPE_LINK) {
				title = t('files_sharing', 'Share link')
			} else if (this.share.type === this.SHARE_TYPES.SHARE_TYPE_GROUP) {
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
		 * Can the sharee download files or only view them ?
		 */
		canDownload: {
			get() {
				return this.share.hasDownloadPermission
			},
			set(checked) {
				this.updateAtomicPermissions({ isDownloadChecked: checked })
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
				return !!this.share.expireDate || this.config.isDefaultInternalExpireDateEnforced
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
				// TODO: directly save after generation to make sure the share is always protected
				this.share.password = enabled ? await GeneratePassword() : ''
				this.$set(this.share, 'newPassword', this.share.password)
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
		dateMaxEnforced() {
			if (!this.isRemote && this.config.isDefaultInternalExpireDateEnforced) {
				return new Date(new Date().setDate(new Date().getDate() + 1 + this.config.defaultInternalExpireDate))
			} else if (this.config.isDefaultRemoteExpireDateEnforced) {
				return new Date(new Date().setDate(new Date().getDate() + 1 + this.config.defaultRemoteExpireDate))
			}
			return null
		},
		/**
		 * @return {boolean}
		 */
		isSetDownloadButtonVisible() {
			// TODO: Implement download permission for circle shares instead of hiding the option.
			//       https://github.com/nextcloud/server/issues/39161
			if (this.share && this.share.type === this.SHARE_TYPES.SHARE_TYPE_CIRCLE) {
				return false
			}

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
		isExpiryDateEnforced() {
			return this.config.isDefaultInternalExpireDateEnforced
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
			return this.share.type === this.SHARE_TYPES.SHARE_TYPE_USER
		},
		isGroupShare() {
			return this.share.type === this.SHARE_TYPES.SHARE_TYPE_GROUP
		},
		isRemoteShare() {
			return this.share.type === this.SHARE_TYPES.SHARE_TYPE_REMOTE_GROUP || this.share.type === this.SHARE_TYPES.SHARE_TYPE_REMOTE
		},
		isNewShare() {
			return this.share.id === null || this.share.id === undefined
		},
		allowsFileDrop() {
			if (this.isFolder) {
				if (this.share.type === this.SHARE_TYPES.SHARE_TYPE_LINK || this.share.type === this.SHARE_TYPES.SHARE_TYPE_EMAIL) {
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
				return t('file_sharing', 'Save share')
			}
			return t('file_sharing', 'Update share')

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
		// if newPassword exists, but is empty, it means
		// the user deleted the original password
		hasUnsavedPassword() {
			return this.share.newPassword !== undefined
		},
		passwordExpirationTime() {
			if (this.share.passwordExpirationTime === null) {
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
				? this.share.type === this.SHARE_TYPES.SHARE_TYPE_EMAIL
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

			// Anything else should be fine
			return true
		},
		canChangeHideDownload() {
			const hasDisabledDownload = (shareAttribute) => shareAttribute.key === 'download' && shareAttribute.scope === 'permissions' && shareAttribute.enabled === false
			return this.fileInfo.shareAttributes.some(hasDisabledDownload)
		},
		customPermissionsList() {
			const perms = []
			if (hasPermissions(this.share.permissions, ATOMIC_PERMISSIONS.READ)) {
				perms.push('read')
			}
			if (hasPermissions(this.share.permissions, ATOMIC_PERMISSIONS.CREATE)) {
				perms.push('create')
			}
			if (hasPermissions(this.share.permissions, ATOMIC_PERMISSIONS.UPDATE)) {
				perms.push('update')
			}
			if (hasPermissions(this.share.permissions, ATOMIC_PERMISSIONS.DELETE)) {
				perms.push('delete')
			}
			if (hasPermissions(this.share.permissions, ATOMIC_PERMISSIONS.SHARE)) {
				perms.push('share')
			}
			if (this.share.hasDownloadPermission) {
				perms.push('download')
			}
			const capitalizeFirstAndJoin = array => array.map((item, index) => index === 0 ? item[0].toUpperCase() + item.substring(1) : item).join(', ')

			return capitalizeFirstAndJoin(perms)

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
		console.debug('shareSentIn', this.share)
		console.debug('config', this.config)
	},

	methods: {
		updateAtomicPermissions({
			isReadChecked = this.hasRead,
			isEditChecked = this.canEdit,
			isCreateChecked = this.canCreate,
			isDeleteChecked = this.canDelete,
			isReshareChecked = this.canReshare,
			isDownloadChecked = this.canDownload,
		} = {}) {
			// calc permissions if checked
			const permissions = 0
				| (isReadChecked ? ATOMIC_PERMISSIONS.READ : 0)
				| (isCreateChecked ? ATOMIC_PERMISSIONS.CREATE : 0)
				| (isDeleteChecked ? ATOMIC_PERMISSIONS.DELETE : 0)
				| (isEditChecked ? ATOMIC_PERMISSIONS.UPDATE : 0)
				| (isReshareChecked ? ATOMIC_PERMISSIONS.SHARE : 0)
			this.share.permissions = permissions
			if (this.share.hasDownloadPermission !== isDownloadChecked) {
				this.$set(this.share, 'hasDownloadPermission', isDownloadChecked)
			}
		},

		toggleCustomPermissions(selectedPermission) {
			if (this.sharingPermission === 'custom') {
				this.advancedSectionAccordionExpanded = true
				this.setCustomPermissions = true
			} else {
				this.advancedSectionAccordionExpanded = false
				this.revertSharingPermission = selectedPermission
				this.setCustomPermissions = false
			}
		},
		initializeAttributes() {

			if (this.isNewShare) return

			let hasAdvancedAttributes = false
			if (this.isValidShareAttribute(this.share.note)) {
				this.writeNoteToRecipientIsChecked = true
				hasAdvancedAttributes = true
			}

			if (this.isValidShareAttribute(this.share.password)) {
				hasAdvancedAttributes = true
			}

			if (this.isValidShareAttribute(this.share.expireDate)) {
				hasAdvancedAttributes = true
			}

			if (this.isValidShareAttribute(this.share.label)) {
				hasAdvancedAttributes = true
			}

			if (hasAdvancedAttributes) {
				this.advancedSectionAccordionExpanded = true
			}

		},
		initializePermissions() {
			if (this.share.share_type) {
				this.share.type = this.share.share_type
			}
			// shareType 0 (USER_SHARE) would evaluate to zero
			// Hence the use of hasOwnProperty
			if ('shareType' in this.share) {
				this.share.type = this.share.shareType
			}
			if (this.isNewShare) {
				if (this.isPublicShare) {
					this.sharingPermission = BUNDLED_PERMISSIONS.READ_ONLY.toString()
				} else {
					this.sharingPermission = BUNDLED_PERMISSIONS.ALL.toString()
				}

			} else {
				if (this.hasCustomPermissions || this.share.setCustomPermissions) {
					this.sharingPermission = 'custom'
					this.advancedSectionAccordionExpanded = true
					this.setCustomPermissions = true
				} else {
					this.sharingPermission = this.share.permissions.toString()
				}
			}
		},
		async saveShare() {
			const permissionsAndAttributes = ['permissions', 'attributes', 'note', 'expireDate']
			const publicShareAttributes = ['label', 'password', 'hideDownload']
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
				if (this.isValidShareAttribute(this.share.newPassword)) {
					this.share.password = this.share.newPassword
					this.$delete(this.share, 'newPassword')
				} else {
					if (this.isPasswordEnforced) {
						this.passwordError = true
						return
					}
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
				}

				if (this.hasExpirationDate) {
					incomingShare.expireDate = this.share.expireDate
				}

				if (this.isPasswordProtected) {
					incomingShare.password = this.share.password
				}

				const share = await this.addShare(incomingShare, this.fileInfo, this.config)
				this.share = share
				this.$emit('add:share', this.share)
			} else {
				this.queueUpdate(...permissionsAndAttributes)
			}

			this.$emit('close-sharing-details')
		},
		/**
		 * Process the new share request
		 *
		 * @param {object} value the multiselect option
		 * @param {object} fileInfo file data
		 * @param {Config} config instance configs
		 */
		async addShare(value, fileInfo, config) {
			// Clear the displayed selection
			this.value = null

			// handle externalResults from OCA.Sharing.ShareSearch
			if (value.handler) {
				const share = await value.handler(this)
				this.$emit('add:share', new Share(share))
				return true
			}

			// this.loading = true // Are we adding loaders the new share flow?
			console.debug('Adding a new share from the input for', value)
			try {
				const path = (fileInfo.path + '/' + fileInfo.name).replace('//', '/')
				const share = await this.createShare({
					path,
					shareType: value.shareType,
					shareWith: value.shareWith,
					permissions: value.permissions,
					attributes: JSON.stringify(fileInfo.shareAttributes),
					...(value.note ? { note: value.note } : {}),
					...(value.password ? { password: value.password } : {}),
					...(value.expireDate ? { expireDate: value.expireDate } : {}),
				})
				return share
			} catch (error) {
				console.error('Error while adding new share', error)
			} finally {
				// this.loading = false // No loader here yet
			}
		},
		async removeShare() {
			await this.onDelete()
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
			case this.SHARE_TYPES.SHARE_TYPE_LINK:
				return LinkIcon
			case this.SHARE_TYPES.SHARE_TYPE_GUEST:
				return UserIcon
			case this.SHARE_TYPES.SHARE_TYPE_REMOTE_GROUP:
			case this.SHARE_TYPES.SHARE_TYPE_GROUP:
				return GroupIcon
			case this.SHARE_TYPES.SHARE_TYPE_EMAIL:
				return EmailIcon
			case this.SHARE_TYPES.SHARE_TYPE_CIRCLE:
				return CircleIcon
			case this.SHARE_TYPES.SHARE_TYPE_ROOM:
				return ShareIcon
			case this.SHARE_TYPES.SHARE_TYPE_DECK:
				return ShareIcon
			case this.SHARE_TYPES.SHARE_TYPE_SCIENCEMESH:
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
	align-items: flex-start;
	width: 96%;
	margin: 0 auto;

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
				padding-left: 0.3em;
			}

		}
	}

	&__quick-permissions {
		display: flex;
		justify-content: center;
		margin-bottom: 0.2em;
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
					color: var(--color-primary-element);
					padding: 0.1em;
				}

				::v-deep label {

					span {
						display: flex;
						flex-direction: column;
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
		text-align: left;
		padding-left: 0;

		section {

			textarea,
			div.mx-datepicker {
				width: 100%;
			}

			textarea {
				height: 80px;
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
			span {
				::v-deep label {
					padding-left: 0 !important;
					background-color: initial !important;
					border: none !important;
				}
			}

			section.custom-permissions-group {
				padding-left: 1.5em;
			}
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

		>button:first-child {
			font-size: 12px;
			color: rgb(223, 7, 7);
			background-color: #f5f5f5;
		}

		.button-group {
			display: flex;
			justify-content: space-between;
			width: 100%;
			margin-top: 16px;

			button {
				margin-left: 16px;

				&:first-child {
					margin-left: 0;
				}
			}
		}
	}
}
</style>
