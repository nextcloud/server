<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<li :class="{ 'sharing-entry--share': share }"
		class="sharing-entry sharing-entry__link">
		<NcAvatar :is-no-user="true"
			:icon-class="isEmailShareType ? 'avatar-link-share icon-mail-white' : 'avatar-link-share icon-public-white'"
			class="sharing-entry__avatar" />

		<div class="sharing-entry__summary">
			<div class="sharing-entry__desc">
				<span class="sharing-entry__title" :title="title">
					{{ title }}
				</span>
				<p v-if="subtitle">
					{{ subtitle }}
				</p>
				<SharingEntryQuickShareSelect v-if="share && share.permissions !== undefined"
					:share="share"
					:file-info="fileInfo"
					@open-sharing-details="openShareDetailsForCustomSettings(share)" />
			</div>

			<div class="sharing-entry__actions">
				<ShareExpiryTime v-if="share && share.expireDate" :share="share" />

				<!-- clipboard -->
				<div>
					<NcActions v-if="share && (!isEmailShareType || isFileRequest) && share.token" ref="copyButton" class="sharing-entry__copy">
						<NcActionButton :aria-label="copyLinkTooltip"
							:title="copyLinkTooltip"
							:href="shareLink"
							@click.prevent="copyLink">
							<template #icon>
								<CheckIcon v-if="copied && copySuccess"
									:size="20"
									class="icon-checkmark-color" />
								<ClipboardIcon v-else :size="20" />
							</template>
						</NcActionButton>
					</NcActions>
				</div>
			</div>
		</div>

		<!-- pending actions -->
		<NcActions v-if="!pending && pendingDataIsMissing"
			class="sharing-entry__actions"
			:aria-label="actionsTooltip"
			menu-align="right"
			:open.sync="open"
			@close="onCancel">
			<!-- pending data menu -->
			<NcActionText v-if="errors.pending"
				class="error">
				<template #icon>
					<ErrorIcon :size="20" />
				</template>
				{{ errors.pending }}
			</NcActionText>
			<NcActionText v-else icon="icon-info">
				{{ t('files_sharing', 'Please enter the following required information before creating the share') }}
			</NcActionText>

			<!-- password -->
			<NcActionCheckbox v-if="pendingPassword"
				:checked.sync="isPasswordProtected"
				:disabled="config.enforcePasswordForPublicLink || saving"
				class="share-link-password-checkbox"
				@uncheck="onPasswordDisable">
				{{ config.enforcePasswordForPublicLink ? t('files_sharing', 'Password protection (enforced)') : t('files_sharing', 'Password protection') }}
			</NcActionCheckbox>

			<NcActionInput v-if="pendingEnforcedPassword || share.password"
				class="share-link-password"
				:label="t('files_sharing', 'Enter a password')"
				:value.sync="share.password"
				:disabled="saving"
				:required="config.enableLinkPasswordByDefault || config.enforcePasswordForPublicLink"
				:minlength="isPasswordPolicyEnabled && config.passwordPolicy.minLength"
				autocomplete="new-password"
				@submit="onNewLinkShare(true)">
				<template #icon>
					<LockIcon :size="20" />
				</template>
			</NcActionInput>

			<NcActionCheckbox v-if="pendingDefaultExpirationDate"
				:checked.sync="defaultExpirationDateEnabled"
				:disabled="pendingEnforcedExpirationDate || saving"
				class="share-link-expiration-date-checkbox"
				@update:model-value="onExpirationDateToggleUpdate">
				{{ config.isDefaultExpireDateEnforced ? t('files_sharing', 'Enable link expiration (enforced)') : t('files_sharing', 'Enable link expiration') }}
			</NcActionCheckbox>

			<!-- expiration date -->
			<NcActionInput v-if="(pendingDefaultExpirationDate || pendingEnforcedExpirationDate) && defaultExpirationDateEnabled"
				data-cy-files-sharing-expiration-date-input
				class="share-link-expire-date"
				:label="pendingEnforcedExpirationDate ? t('files_sharing', 'Enter expiration date (enforced)') : t('files_sharing', 'Enter expiration date')"
				:disabled="saving"
				:is-native-picker="true"
				:hide-label="true"
				:value="new Date(share.expireDate)"
				type="date"
				:min="dateTomorrow"
				:max="maxExpirationDateEnforced"
				@update:model-value="onExpirationChange"
				@change="expirationDateChanged">
				<template #icon>
					<IconCalendarBlank :size="20" />
				</template>
			</NcActionInput>

			<NcActionButton @click.prevent.stop="onNewLinkShare(true)">
				<template #icon>
					<CheckIcon :size="20" />
				</template>
				{{ t('files_sharing', 'Create share') }}
			</NcActionButton>
			<NcActionButton @click.prevent.stop="onCancel">
				<template #icon>
					<CloseIcon :size="20" />
				</template>
				{{ t('files_sharing', 'Cancel') }}
			</NcActionButton>
		</NcActions>

		<!-- actions -->
		<NcActions v-else-if="!loading"
			class="sharing-entry__actions"
			:aria-label="actionsTooltip"
			menu-align="right"
			:open.sync="open"
			@close="onMenuClose">
			<template v-if="share">
				<template v-if="share.canEdit && canReshare">
					<NcActionButton :disabled="saving"
						:close-after-click="true"
						@click.prevent="openSharingDetails">
						<template #icon>
							<Tune :size="20" />
						</template>
						{{ t('files_sharing', 'Customize link') }}
					</NcActionButton>
				</template>

				<NcActionButton :close-after-click="true"
					@click.prevent="showQRCode = true">
					<template #icon>
						<IconQr :size="20" />
					</template>
					{{ t('files_sharing', 'Generate QR code') }}
				</NcActionButton>

				<NcActionSeparator />

				<!-- external actions -->
				<ExternalShareAction v-for="action in externalLinkActions"
					:id="action.id"
					:key="action.id"
					:action="action"
					:file-info="fileInfo"
					:share="share" />

				<!-- external legacy sharing via url (social...) -->
				<NcActionLink v-for="({ icon, url, name }, actionIndex) in externalLegacyLinkActions"
					:key="actionIndex"
					:href="url(shareLink)"
					:icon="icon"
					target="_blank">
					{{ name }}
				</NcActionLink>

				<NcActionButton v-if="!isEmailShareType && canReshare"
					class="new-share-link"
					@click.prevent.stop="onNewLinkShare">
					<template #icon>
						<PlusIcon :size="20" />
					</template>
					{{ t('files_sharing', 'Add another link') }}
				</NcActionButton>

				<NcActionButton v-if="share.canDelete"
					:disabled="saving"
					@click.prevent="onDelete">
					<template #icon>
						<CloseIcon :size="20" />
					</template>
					{{ t('files_sharing', 'Unshare') }}
				</NcActionButton>
			</template>

			<!-- Create new share -->
			<NcActionButton v-else-if="canReshare"
				class="new-share-link"
				:title="t('files_sharing', 'Create a new share link')"
				:aria-label="t('files_sharing', 'Create a new share link')"
				:icon="loading ? 'icon-loading-small' : 'icon-add'"
				@click.prevent.stop="onNewLinkShare" />
		</NcActions>

		<!-- loading indicator to replace the menu -->
		<div v-else class="icon-loading-small sharing-entry__loading" />

		<!-- Modal to open whenever we have a QR code -->
		<NcDialog v-if="showQRCode"
			size="normal"
			:open.sync="showQRCode"
			:name="title"
			:close-on-click-outside="true"
			@close="showQRCode = false">
			<div class="qr-code-dialog">
				<VueQrcode tag="img"
					:value="shareLink"
					class="qr-code-dialog__img" />
			</div>
		</NcDialog>
	</li>
</template>

<script>
import { showError, showSuccess } from '@nextcloud/dialogs'
import { emit } from '@nextcloud/event-bus'
import { t } from '@nextcloud/l10n'
import moment from '@nextcloud/moment'
import { generateUrl, getBaseUrl } from '@nextcloud/router'
import { ShareType } from '@nextcloud/sharing'

import VueQrcode from '@chenfengyuan/vue-qrcode'
import NcActionButton from '@nextcloud/vue/components/NcActionButton'
import NcActionCheckbox from '@nextcloud/vue/components/NcActionCheckbox'
import NcActionInput from '@nextcloud/vue/components/NcActionInput'
import NcActionLink from '@nextcloud/vue/components/NcActionLink'
import NcActionText from '@nextcloud/vue/components/NcActionText'
import NcActionSeparator from '@nextcloud/vue/components/NcActionSeparator'
import NcActions from '@nextcloud/vue/components/NcActions'
import NcAvatar from '@nextcloud/vue/components/NcAvatar'
import NcDialog from '@nextcloud/vue/components/NcDialog'

import Tune from 'vue-material-design-icons/Tune.vue'
import IconCalendarBlank from 'vue-material-design-icons/CalendarBlank.vue'
import IconQr from 'vue-material-design-icons/Qrcode.vue'
import ErrorIcon from 'vue-material-design-icons/Exclamation.vue'
import LockIcon from 'vue-material-design-icons/Lock.vue'
import CheckIcon from 'vue-material-design-icons/CheckBold.vue'
import ClipboardIcon from 'vue-material-design-icons/ContentCopy.vue'
import CloseIcon from 'vue-material-design-icons/Close.vue'
import PlusIcon from 'vue-material-design-icons/Plus.vue'

import SharingEntryQuickShareSelect from './SharingEntryQuickShareSelect.vue'
import ShareExpiryTime from './ShareExpiryTime.vue'

import ExternalShareAction from './ExternalShareAction.vue'
import GeneratePassword from '../utils/GeneratePassword.ts'
import Share from '../models/Share.ts'
import SharesMixin from '../mixins/SharesMixin.js'
import ShareDetails from '../mixins/ShareDetails.js'
import logger from '../services/logger.ts'

export default {
	name: 'SharingEntryLink',

	components: {
		ExternalShareAction,
		NcActions,
		NcActionButton,
		NcActionCheckbox,
		NcActionInput,
		NcActionLink,
		NcActionText,
		NcActionSeparator,
		NcAvatar,
		NcDialog,
		VueQrcode,
		Tune,
		IconCalendarBlank,
		IconQr,
		ErrorIcon,
		LockIcon,
		CheckIcon,
		ClipboardIcon,
		CloseIcon,
		PlusIcon,
		SharingEntryQuickShareSelect,
		ShareExpiryTime,
	},

	mixins: [SharesMixin, ShareDetails],

	props: {
		canReshare: {
			type: Boolean,
			default: true,
		},
		index: {
			type: Number,
			default: null,
		},
	},

	data() {
		return {
			shareCreationComplete: false,
			copySuccess: true,
			copied: false,
			defaultExpirationDateEnabled: false,

			// Are we waiting for password/expiration date
			pending: false,

			ExternalLegacyLinkActions: OCA.Sharing.ExternalLinkActions.state,
			ExternalShareActions: OCA.Sharing.ExternalShareActions.state,

			// tracks whether modal should be opened or not
			showQRCode: false,
		}
	},

	computed: {
		/**
		 * Link share label
		 *
		 * @return {string}
		 */
		title() {
			const l10nOptions = { escape: false /* no escape as this string is already escaped by Vue */ }

			// if we have a valid existing share (not pending)
			if (this.share && this.share.id) {
				if (!this.isShareOwner && this.share.ownerDisplayName) {
					if (this.isEmailShareType) {
						return t('files_sharing', '{shareWith} by {initiator}', {
							shareWith: this.share.shareWith,
							initiator: this.share.ownerDisplayName,
						}, l10nOptions)
					}
					return t('files_sharing', 'Shared via link by {initiator}', {
						initiator: this.share.ownerDisplayName,
					}, l10nOptions)
				}
				if (this.share.label && this.share.label.trim() !== '') {
					if (this.isEmailShareType) {
						if (this.isFileRequest) {
							return t('files_sharing', 'File request ({label})', {
								label: this.share.label.trim(),
							}, l10nOptions)
						}
						return t('files_sharing', 'Mail share ({label})', {
							label: this.share.label.trim(),
						}, l10nOptions)
					}
					return t('files_sharing', 'Share link ({label})', {
						label: this.share.label.trim(),
					}, l10nOptions)
				}
				if (this.isEmailShareType) {
					if (!this.share.shareWith || this.share.shareWith.trim() === '') {
						return this.isFileRequest
							? t('files_sharing', 'File request')
							: t('files_sharing', 'Mail share')
					}
					return this.share.shareWith
				}

				if (this.index === null) {
					return t('files_sharing', 'Share link')
				}
			}

			if (this.index >= 1) {
				return t('files_sharing', 'Share link ({index})', { index: this.index })
			}

			return t('files_sharing', 'Create public link')
		},

		/**
		 * Show the email on a second line if a label is set for mail shares
		 *
		 * @return {string}
		 */
		subtitle() {
			if (this.isEmailShareType
				&& this.title !== this.share.shareWith) {
				return this.share.shareWith
			}
			return null
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
				? this.share.type === ShareType.Email
				: false
		},

		canTogglePasswordProtectedByTalkAvailable() {
			if (!this.isPasswordProtected) {
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

		/**
		 * Pending data.
		 * If the share still doesn't have an id, it is not synced
		 * Therefore this is still not valid and requires user input
		 *
		 * @return {boolean}
		 */
		pendingDataIsMissing() {
			return this.pendingPassword || this.pendingEnforcedPassword || this.pendingDefaultExpirationDate || this.pendingEnforcedExpirationDate
		},
		pendingPassword() {
			return this.config.enableLinkPasswordByDefault && this.isPendingShare
		},
		pendingEnforcedPassword() {
			return this.config.enforcePasswordForPublicLink && this.isPendingShare
		},
		pendingEnforcedExpirationDate() {
			return this.config.isDefaultExpireDateEnforced && this.isPendingShare
		},
		pendingDefaultExpirationDate() {
			return (this.config.defaultExpirationDate instanceof Date || !isNaN(new Date(this.config.defaultExpirationDate).getTime())) && this.isPendingShare
		},
		isPendingShare() {
			return !!(this.share && !this.share.id)
		},
		sharePolicyHasEnforcedProperties() {
			return this.config.enforcePasswordForPublicLink || this.config.isDefaultExpireDateEnforced
		},

		enforcedPropertiesMissing() {
			// Ensure share exist and the share policy has required properties
			if (!this.sharePolicyHasEnforcedProperties) {
				return false
			}

			if (!this.share) {
				// if no share, we can't tell if properties are missing or not so we assume properties are missing
				return true
			}

			// If share has ID, then this is an incoming link share created from the existing link share
			// Hence assume required properties
			if (this.share.id) {
				return true
			}
			// Check if either password or expiration date is missing and enforced
			const isPasswordMissing = this.config.enforcePasswordForPublicLink && !this.share.password
			const isExpireDateMissing = this.config.isDefaultExpireDateEnforced && !this.share.expireDate

			return isPasswordMissing || isExpireDateMissing
		},
		// if newPassword exists, but is empty, it means
		// the user deleted the original password
		hasUnsavedPassword() {
			return this.share.newPassword !== undefined
		},

		/**
		 * Return the public share link
		 *
		 * @return {string}
		 */
		shareLink() {
			return generateUrl('/s/{token}', { token: this.share.token }, { baseURL: getBaseUrl() })
		},

		/**
		 * Tooltip message for actions button
		 *
		 * @return {string}
		 */
		actionsTooltip() {
			return t('files_sharing', 'Actions for "{title}"', { title: this.title })
		},

		/**
		 * Tooltip message for copy button
		 *
		 * @return {string}
		 */
		copyLinkTooltip() {
			if (this.copied) {
				if (this.copySuccess) {
					return ''
				}
				return t('files_sharing', 'Cannot copy, please copy the link manually')
			}
			return t('files_sharing', 'Copy public link of "{title}" to clipboard', { title: this.title })
		},

		/**
		 * External additionnai actions for the menu
		 *
		 * @deprecated use OCA.Sharing.ExternalShareActions
		 * @return {Array}
		 */
		externalLegacyLinkActions() {
			return this.ExternalLegacyLinkActions.actions
		},

		/**
		 * Additional actions for the menu
		 *
		 * @return {Array}
		 */
		externalLinkActions() {
			const filterValidAction = (action) => (action.shareType.includes(ShareType.Link) || action.shareType.includes(ShareType.Email)) && !action.advanced
			// filter only the registered actions for said link
			return this.ExternalShareActions.actions
				.filter(filterValidAction)
		},

		isPasswordPolicyEnabled() {
			return typeof this.config.passwordPolicy === 'object'
		},

		canChangeHideDownload() {
			const hasDisabledDownload = (shareAttribute) => shareAttribute.scope === 'permissions' && shareAttribute.key === 'download' && shareAttribute.value === false
			return this.fileInfo.shareAttributes.some(hasDisabledDownload)
		},

		isFileRequest() {
			return this.share.isFileRequest
		},
	},
	mounted() {
		this.defaultExpirationDateEnabled = this.config.defaultExpirationDate instanceof Date
		if (this.share && this.isNewShare) {
			this.share.expireDate = this.defaultExpirationDateEnabled ? this.formatDateToString(this.config.defaultExpirationDate) : ''
		}
	},

	methods: {
		/**
		 * Check if the share requires review
		 *
		 * @param {boolean} shareReviewComplete if the share was reviewed
		 * @return {boolean}
		 */
		shareRequiresReview(shareReviewComplete) {
			// If a user clicks 'Create share' it means they have reviewed the share
			if (shareReviewComplete) {
				return false
			}
			return this.defaultExpirationDateEnabled || this.config.enableLinkPasswordByDefault
		},
		/**
		 * Create a new share link and append it to the list
		 * @param {boolean} shareReviewComplete if the share was reviewed
		 */
		async onNewLinkShare(shareReviewComplete = false) {
			logger.debug('onNewLinkShare called (with this.share)', this.share)
			// do not run again if already loading
			if (this.loading) {
				return
			}

			const shareDefaults = {
				share_type: ShareType.Link,
			}
			if (this.config.isDefaultExpireDateEnforced) {
				// default is empty string if not set
				// expiration is the share object key, not expireDate
				shareDefaults.expiration = this.formatDateToString(this.config.defaultExpirationDate)
			}

			logger.debug('Missing required properties?', this.enforcedPropertiesMissing)
			// Do not push yet if we need a password or an expiration date: show pending menu
			// A share would require a review for example is default expiration date is set but not enforced, this allows
			// the user to review the share and remove the expiration date if they don't want it
			if ((this.sharePolicyHasEnforcedProperties && this.enforcedPropertiesMissing) || this.shareRequiresReview(shareReviewComplete === true)) {
				this.pending = true
				this.shareCreationComplete = false

				logger.info('Share policy requires a review or has mandated properties (password, expirationDate)...')

				// ELSE, show the pending popovermenu
				// if password default or enforced, pre-fill with random one
				if (this.config.enableLinkPasswordByDefault || this.config.enforcePasswordForPublicLink) {
					shareDefaults.password = await GeneratePassword(true)
				}

				// create share & close menu
				const share = new Share(shareDefaults)
				const component = await new Promise(resolve => {
					this.$emit('add:share', share, resolve)
				})

				// open the menu on the
				// freshly created share component
				this.open = false
				this.pending = false
				component.open = true

				// Nothing is enforced, creating share directly
			} else {

				// if a share already exists, pushing it
				if (this.share && !this.share.id) {
					// if the share is valid, create it on the server
					if (this.checkShare(this.share)) {
						try {
							logger.info('Sending existing share to server', this.share)
							await this.pushNewLinkShare(this.share, true)
							this.shareCreationComplete = true
							logger.info('Share created on server', this.share)
						} catch (e) {
							this.pending = false
							logger.error('Error creating share', e)
							return false
						}
						return true
					} else {
						this.open = true
						showError(t('files_sharing', 'Error, please enter proper password and/or expiration date'))
						return false
					}
				}

				const share = new Share(shareDefaults)
				await this.pushNewLinkShare(share)
				this.shareCreationComplete = true
			}
		},

		/**
		 * Push a new link share to the server
		 * And update or append to the list
		 * accordingly
		 *
		 * @param {Share} share the new share
		 * @param {boolean} [update] do we update the current share ?
		 */
		async pushNewLinkShare(share, update) {
			try {
				// do nothing if we're already pending creation
				if (this.loading) {
					return true
				}

				this.loading = true
				this.errors = {}

				const path = (this.fileInfo.path + '/' + this.fileInfo.name).replace('//', '/')
				const options = {
					path,
					shareType: ShareType.Link,
					password: share.password,
					expireDate: share.expireDate ?? '',
					attributes: JSON.stringify(this.fileInfo.shareAttributes),
					// we do not allow setting the publicUpload
					// before the share creation.
					// Todo: We also need to fix the createShare method in
					// lib/Controller/ShareAPIController.php to allow file requests
					// (currently not supported on create, only update)
				}

				console.debug('Creating link share with options', options)
				const newShare = await this.createShare(options)

				this.open = false
				this.shareCreationComplete = true
				console.debug('Link share created', newShare)
				// if share already exists, copy link directly on next tick
				let component
				if (update) {
					component = await new Promise(resolve => {
						this.$emit('update:share', newShare, resolve)
					})
				} else {
					// adding new share to the array and copying link to clipboard
					// using promise so that we can copy link in the same click function
					// and avoid firefox copy permissions issue
					component = await new Promise(resolve => {
						this.$emit('add:share', newShare, resolve)
					})
				}

				await this.getNode()
				emit('files:node:updated', this.node)

				// Execute the copy link method
				// freshly created share component
				// ! somehow does not works on firefox !
				if (!this.config.enforcePasswordForPublicLink) {
					// Only copy the link when the password was not forced,
					// otherwise the user needs to copy/paste the password before finishing the share.
					component.copyLink()
				}
				showSuccess(t('files_sharing', 'Link share created'))

			} catch (data) {
				const message = data?.response?.data?.ocs?.meta?.message
				if (!message) {
					showError(t('files_sharing', 'Error while creating the share'))
					console.error(data)
					return
				}

				if (message.match(/password/i)) {
					this.onSyncError('password', message)
				} else if (message.match(/date/i)) {
					this.onSyncError('expireDate', message)
				} else {
					this.onSyncError('pending', message)
				}
				throw data

			} finally {
				this.loading = false
				this.shareCreationComplete = true
			}
		},
		async copyLink() {
			try {
				await navigator.clipboard.writeText(this.shareLink)
				showSuccess(t('files_sharing', 'Link copied'))
				// focus and show the tooltip
				this.$refs.copyButton.$el.focus()
				this.copySuccess = true
				this.copied = true
			} catch (error) {
				this.copySuccess = false
				this.copied = true
				console.error(error)
			} finally {
				setTimeout(() => {
					this.copySuccess = false
					this.copied = false
				}, 4000)
			}
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
			this.$set(this.share, 'newPassword', password)
		},

		/**
		 * Uncheck password protection
		 * We need this method because @update:checked
		 * is ran simultaneously as @uncheck, so we
		 * cannot ensure data is up-to-date
		 */
		onPasswordDisable() {
			this.share.password = ''

			// reset password state after sync
			this.$delete(this.share, 'newPassword')

			// only update if valid share.
			if (this.share.id) {
				this.queueUpdate('password')
			}
		},

		/**
		 * Menu have been closed or password has been submitted.
		 * The only property that does not get
		 * synced automatically is the password
		 * So let's check if we have an unsaved
		 * password.
		 * expireDate is saved on datepicker pick
		 * or close.
		 */
		onPasswordSubmit() {
			if (this.hasUnsavedPassword) {
				this.share.password = this.share.newPassword.trim()
				this.queueUpdate('password')
			}
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

		/**
		 * Save potential changed data on menu close
		 */
		onMenuClose() {
			this.onPasswordSubmit()
			this.onNoteSubmit()
		},

		/**
		 * @param enabled True if expiration is enabled
		 */
		onExpirationDateToggleUpdate(enabled) {
			this.share.expireDate = enabled ? this.formatDateToString(this.config.defaultExpirationDate) : ''
		},

		expirationDateChanged(event) {
			const value = event?.target?.value
			const isValid = !!value && !isNaN(new Date(value).getTime())
			this.defaultExpirationDateEnabled = isValid
		},

		/**
		 * Cancel the share creation
		 * Used in the pending popover
		 */
		onCancel() {
			// this.share already exists at this point,
			// but is incomplete as not pushed to server
			// YET. We can safely delete the share :)
			if (!this.shareCreationComplete) {
				this.$emit('remove:share', this.share)
			}
		},
	},
}
</script>

<style lang="scss" scoped>
.sharing-entry {
	display: flex;
	align-items: center;
	min-height: 44px;

	&__summary {
		padding: 8px;
		padding-inline-start: 10px;
		display: flex;
		justify-content: space-between;
		flex: 1 0;
		min-width: 0;
	}

		&__desc {
			display: flex;
			flex-direction: column;
			line-height: 1.2em;

			p {
				color: var(--color-text-maxcontrast);
			}

			&__title {
				text-overflow: ellipsis;
				overflow: hidden;
				white-space: nowrap;
			}
		}

		&__actions {
			display: flex;
			align-items: center;
			margin-inline-start: auto;
		}

	&:not(.sharing-entry--share) &__actions {
		.new-share-link {
			border-top: 1px solid var(--color-border);
		}
	}

	:deep(.avatar-link-share) {
		background-color: var(--color-primary-element);
	}

	.sharing-entry__action--public-upload {
		border-bottom: 1px solid var(--color-border);
	}

	&__loading {
		width: 44px;
		height: 44px;
		margin: 0;
		padding: 14px;
		margin-inline-start: auto;
	}

	// put menus to the left
	// but only the first one
	.action-item {

		~.action-item,
		~.sharing-entry__loading {
			margin-inline-start: 0;
		}
	}

	.icon-checkmark-color {
		opacity: 1;
		color: var(--color-success);
	}
}

// styling for the qr-code container
.qr-code-dialog {
	display: flex;
	width: 100%;
	justify-content: center;

	&__img {
		width: 100%;
		height: auto;
	}
}
</style>
