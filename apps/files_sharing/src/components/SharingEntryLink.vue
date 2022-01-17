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
	<li :class="{'sharing-entry--share': share}" class="sharing-entry sharing-entry__link">
		<Avatar :is-no-user="true"
			:icon-class="isEmailShareType ? 'avatar-link-share icon-mail-white' : 'avatar-link-share icon-public-white'"
			class="sharing-entry__avatar" />
		<div class="sharing-entry__desc">
			<h5 :title="title">
				{{ title }}
			</h5>
			<p v-if="subtitle">
				{{ subtitle }}
			</p>
		</div>

		<!-- clipboard -->
		<Actions v-if="share && !isEmailShareType && share.token"
			ref="copyButton"
			class="sharing-entry__copy">
			<ActionLink :href="shareLink"
				target="_blank"
				:icon="copied && copySuccess ? 'icon-checkmark-color' : 'icon-clippy'"
				@click.stop.prevent="copyLink">
				{{ clipboardTooltip }}
			</ActionLink>
		</Actions>

		<!-- pending actions -->
		<Actions v-if="!pending && (pendingPassword || pendingExpirationDate)"
			class="sharing-entry__actions"
			menu-align="right"
			:open.sync="open"
			@close="onNewLinkShare">
			<!-- pending data menu -->
			<ActionText v-if="errors.pending"
				icon="icon-error"
				:class="{ error: errors.pending}">
				{{ errors.pending }}
			</ActionText>
			<ActionText v-else icon="icon-info">
				{{ t('files_sharing', 'Please enter the following required information before creating the share') }}
			</ActionText>

			<!-- password -->
			<ActionText v-if="pendingPassword" icon="icon-password">
				{{ t('files_sharing', 'Password protection (enforced)') }}
			</ActionText>
			<ActionCheckbox v-else-if="config.enableLinkPasswordByDefault"
				:checked.sync="isPasswordProtected"
				:disabled="config.enforcePasswordForPublicLink || saving"
				class="share-link-password-checkbox"
				@uncheck="onPasswordDisable">
				{{ t('files_sharing', 'Password protection') }}
			</ActionCheckbox>
			<ActionInput v-if="pendingPassword || share.password"
				v-tooltip.auto="{
					content: errors.password,
					show: errors.password,
					trigger: 'manual',
					defaultContainer: '#app-sidebar'
				}"
				class="share-link-password"
				:value.sync="share.password"
				:disabled="saving"
				:required="config.enableLinkPasswordByDefault || config.enforcePasswordForPublicLink"
				:minlength="isPasswordPolicyEnabled && config.passwordPolicy.minLength"
				icon=""
				autocomplete="new-password"
				@submit="onNewLinkShare">
				{{ t('files_sharing', 'Enter a password') }}
			</ActionInput>

			<!-- expiration date -->
			<ActionText v-if="pendingExpirationDate" icon="icon-calendar-dark">
				{{ t('files_sharing', 'Expiration date (enforced)') }}
			</ActionText>
			<ActionInput v-if="pendingExpirationDate"
				v-model="share.expireDate"
				v-tooltip.auto="{
					content: errors.expireDate,
					show: errors.expireDate,
					trigger: 'manual',
					defaultContainer: '#app-sidebar'
				}"
				class="share-link-expire-date"
				:disabled="saving"

				:lang="lang"
				icon=""
				type="date"
				value-type="format"
				:disabled-date="disabledDate">
				<!-- let's not submit when picked, the user
					might want to still edit or copy the password -->
				{{ t('files_sharing', 'Enter a date') }}
			</ActionInput>

			<ActionButton icon="icon-checkmark" @click.prevent.stop="onNewLinkShare">
				{{ t('files_sharing', 'Create share') }}
			</ActionButton>
			<ActionButton icon="icon-close" @click.prevent.stop="onCancel">
				{{ t('files_sharing', 'Cancel') }}
			</ActionButton>
		</Actions>

		<!-- actions -->
		<Actions v-else-if="!loading"
			class="sharing-entry__actions"
			menu-align="right"
			:open.sync="open"
			@close="onMenuClose">
			<template v-if="share">
				<template v-if="share.canEdit && canReshare">
					<!-- Custom Label -->
					<ActionInput
						ref="label"
						v-tooltip.auto="{
							content: errors.label,
							show: errors.label,
							trigger: 'manual',
							defaultContainer: '.app-sidebar'
						}"
						:class="{ error: errors.label }"
						:disabled="saving"
						:aria-label="t('files_sharing', 'Share label')"
						:value="share.newLabel !== undefined ? share.newLabel : share.label"
						icon="icon-edit"
						maxlength="255"
						@update:value="onLabelChange"
						@submit="onLabelSubmit">
						{{ t('files_sharing', 'Share label') }}
					</ActionInput>
					<!-- folder -->
					<template v-if="isFolder && fileHasCreatePermission && config.isPublicUploadEnabled">
						<ActionRadio :checked="sharePermissions === publicUploadRValue"
							:value="publicUploadRValue"
							:name="randomId"
							:disabled="saving"
							@change="togglePermissions">
							{{ t('files_sharing', 'Read only') }}
						</ActionRadio>
						<ActionRadio :checked="sharePermissions === publicUploadRWValue"
							:value="publicUploadRWValue"
							:disabled="saving"
							:name="randomId"
							@change="togglePermissions">
							{{ t('files_sharing', 'Allow upload and editing') }}
						</ActionRadio>
						<ActionRadio :checked="sharePermissions === publicUploadWValue"
							:value="publicUploadWValue"
							:disabled="saving"
							:name="randomId"
							class="sharing-entry__action--public-upload"
							@change="togglePermissions">
							{{ t('files_sharing', 'File drop (upload only)') }}
						</ActionRadio>
					</template>

					<!-- file -->
					<ActionCheckbox v-if="!isFolder"
						:checked.sync="canUpdate"
						:disabled="saving"
						@change="queueUpdate('permissions')">
						{{ t('files_sharing', 'Allow editing') }}
					</ActionCheckbox>

					<ActionCheckbox
						:checked.sync="share.hideDownload"
						:disabled="saving"
						@change="queueUpdate('hideDownload')">
						{{ t('files_sharing', 'Hide download') }}
					</ActionCheckbox>

					<!-- password -->
					<ActionCheckbox :checked.sync="isPasswordProtected"
						:disabled="config.enforcePasswordForPublicLink || saving"
						class="share-link-password-checkbox"
						@uncheck="onPasswordDisable">
						{{ config.enforcePasswordForPublicLink
							? t('files_sharing', 'Password protection (enforced)')
							: t('files_sharing', 'Password protect') }}
					</ActionCheckbox>
					<ActionInput v-if="isPasswordProtected"
						ref="password"
						v-tooltip.auto="{
							content: errors.password,
							show: errors.password,
							trigger: 'manual',
							defaultContainer: '#app-sidebar'
						}"
						class="share-link-password"
						:class="{ error: errors.password}"
						:disabled="saving"
						:required="config.enforcePasswordForPublicLink"
						:value="hasUnsavedPassword ? share.newPassword : '***************'"
						icon="icon-password"
						autocomplete="new-password"
						:type="hasUnsavedPassword ? 'text': 'password'"
						@update:value="onPasswordChange"
						@submit="onPasswordSubmit">
						{{ t('files_sharing', 'Enter a password') }}
					</ActionInput>

					<!-- password protected by Talk -->
					<ActionCheckbox v-if="isPasswordProtectedByTalkAvailable"
						:checked.sync="isPasswordProtectedByTalk"
						:disabled="!canTogglePasswordProtectedByTalkAvailable || saving"
						class="share-link-password-talk-checkbox"
						@change="onPasswordProtectedByTalkChange">
						{{ t('files_sharing', 'Video verification') }}
					</ActionCheckbox>

					<!-- expiration date -->
					<ActionCheckbox :checked.sync="hasExpirationDate"
						:disabled="config.isDefaultExpireDateEnforced || saving"
						class="share-link-expire-date-checkbox"
						@uncheck="onExpirationDisable">
						{{ config.isDefaultExpireDateEnforced
							? t('files_sharing', 'Expiration date (enforced)')
							: t('files_sharing', 'Set expiration date') }}
					</ActionCheckbox>
					<ActionInput v-if="hasExpirationDate"
						ref="expireDate"
						v-tooltip.auto="{
							content: errors.expireDate,
							show: errors.expireDate,
							trigger: 'manual',
							defaultContainer: '#app-sidebar'
						}"
						class="share-link-expire-date"
						:class="{ error: errors.expireDate}"
						:disabled="saving"
						:lang="lang"
						:value="share.expireDate"
						value-type="format"
						icon="icon-calendar-dark"
						type="date"
						:disabled-date="disabledDate"
						@update:value="onExpirationChange">
						{{ t('files_sharing', 'Enter a date') }}
					</ActionInput>

					<!-- note -->
					<ActionCheckbox :checked.sync="hasNote"
						:disabled="saving"
						@uncheck="queueUpdate('note')">
						{{ t('files_sharing', 'Note to recipient') }}
					</ActionCheckbox>
					<ActionTextEditable v-if="hasNote"
						ref="note"
						v-tooltip.auto="{
							content: errors.note,
							show: errors.note,
							trigger: 'manual',
							defaultContainer: '#app-sidebar'
						}"
						:class="{ error: errors.note}"
						:disabled="saving"
						:placeholder="t('files_sharing', 'Enter a note for the share recipient')"
						:value="share.newNote || share.note"
						icon="icon-edit"
						@update:value="onNoteChange"
						@submit="onNoteSubmit" />
				</template>

				<!-- external actions -->
				<ExternalShareAction v-for="action in externalLinkActions"
					:id="action.id"
					:key="action.id"
					:action="action"
					:file-info="fileInfo"
					:share="share" />

				<!-- external legacy sharing via url (social...) -->
				<ActionLink v-for="({icon, url, name}, index) in externalLegacyLinkActions"
					:key="index"
					:href="url(shareLink)"
					:icon="icon"
					target="_blank">
					{{ name }}
				</ActionLink>

				<ActionButton v-if="share.canDelete"
					icon="icon-close"
					:disabled="saving"
					@click.prevent="onDelete">
					{{ t('files_sharing', 'Unshare') }}
				</ActionButton>
				<ActionButton v-if="!isEmailShareType && canReshare"
					class="new-share-link"
					icon="icon-add"
					@click.prevent.stop="onNewLinkShare">
					{{ t('files_sharing', 'Add another link') }}
				</ActionButton>
			</template>

			<!-- Create new share -->
			<ActionButton v-else-if="canReshare"
				class="new-share-link"
				:icon="loading ? 'icon-loading-small' : 'icon-add'"
				@click.prevent.stop="onNewLinkShare">
				{{ t('files_sharing', 'Create a new share link') }}
			</ActionButton>
		</Actions>

		<!-- loading indicator to replace the menu -->
		<div v-else class="icon-loading-small sharing-entry__loading" />
	</li>
</template>

<script>
import { generateUrl } from '@nextcloud/router'
import Vue from 'vue'

import ActionButton from '@nextcloud/vue/dist/Components/ActionButton'
import ActionCheckbox from '@nextcloud/vue/dist/Components/ActionCheckbox'
import ActionInput from '@nextcloud/vue/dist/Components/ActionInput'
import ActionLink from '@nextcloud/vue/dist/Components/ActionLink'
import ActionRadio from '@nextcloud/vue/dist/Components/ActionRadio'
import ActionText from '@nextcloud/vue/dist/Components/ActionText'
import ActionTextEditable from '@nextcloud/vue/dist/Components/ActionTextEditable'
import Actions from '@nextcloud/vue/dist/Components/Actions'
import Avatar from '@nextcloud/vue/dist/Components/Avatar'
import Tooltip from '@nextcloud/vue/dist/Directives/Tooltip'

import ExternalShareAction from './ExternalShareAction'
import GeneratePassword from '../utils/GeneratePassword'
import Share from '../models/Share'
import SharesMixin from '../mixins/SharesMixin'

export default {
	name: 'SharingEntryLink',

	components: {
		Actions,
		ActionButton,
		ActionCheckbox,
		ActionRadio,
		ActionInput,
		ActionLink,
		ActionText,
		ActionTextEditable,
		Avatar,
		ExternalShareAction,
	},

	directives: {
		Tooltip,
	},

	mixins: [SharesMixin],

	props: {
		canReshare: {
			type: Boolean,
			default: true,
		},
	},

	data() {
		return {
			copySuccess: true,
			copied: false,

			// Are we waiting for password/expiration date
			pending: false,

			publicUploadRWValue: OC.PERMISSION_UPDATE | OC.PERMISSION_CREATE | OC.PERMISSION_READ | OC.PERMISSION_DELETE,
			publicUploadRValue: OC.PERMISSION_READ,
			publicUploadWValue: OC.PERMISSION_CREATE,

			ExternalLegacyLinkActions: OCA.Sharing.ExternalLinkActions.state,
			ExternalShareActions: OCA.Sharing.ExternalShareActions.state,
		}
	},

	computed: {
		/**
		 * Return the current share permissions
		 * We always ignore the SHARE permission as this is used for the
		 * federated sharing.
		 *
		 * @return {number}
		 */
		sharePermissions() {
			return this.share.permissions & ~OC.PERMISSION_SHARE
		},
		/**
		 * Generate a unique random id for this SharingEntryLink only
		 * This allows ActionRadios to have the same name prop
		 * but not to impact others SharingEntryLink
		 *
		 * @return {string}
		 */
		randomId() {
			return Math.random().toString(27).substr(2)
		},

		/**
		 * Link share label
		 *
		 * @return {string}
		 */
		title() {
			// if we have a valid existing share (not pending)
			if (this.share && this.share.id) {
				if (!this.isShareOwner && this.share.ownerDisplayName) {
					if (this.isEmailShareType) {
						return t('files_sharing', '{shareWith} by {initiator}', {
							shareWith: this.share.shareWith,
							initiator: this.share.ownerDisplayName,
						})
					}
					return t('files_sharing', 'Shared via link by {initiator}', {
						initiator: this.share.ownerDisplayName,
					})
				}
				if (this.share.label && this.share.label.trim() !== '') {
					if (this.isEmailShareType) {
						return t('files_sharing', 'Mail share ({label})', {
							label: this.share.label.trim(),
						})
					}
					return t('files_sharing', 'Share link ({label})', {
						label: this.share.label.trim(),
					})
				}
				if (this.isEmailShareType) {
					return this.share.shareWith
				}
			}
			return t('files_sharing', 'Share link')
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

		/**
		 * Does the current share have an expiration date
		 *
		 * @return {boolean}
		 */
		hasExpirationDate: {
			get() {
				return this.config.isDefaultExpireDateEnforced
					|| !!this.share.expireDate
			},
			set(enabled) {
				let dateString = moment(this.config.defaultExpirationDateString)
				if (!dateString.isValid()) {
					dateString = moment()
				}
				this.share.state.expiration = enabled
					? dateString.format('YYYY-MM-DD')
					: ''
				console.debug('Expiration date status', enabled, this.share.expireDate)
			},
		},

		dateMaxEnforced() {
			return this.config.isDefaultExpireDateEnforced
				&& moment().add(1 + this.config.defaultExpireDate, 'days')
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
				Vue.set(this.share, 'password', enabled ? await GeneratePassword() : '')
				Vue.set(this.share, 'newPassword', this.share.password)
			},
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
		pendingPassword() {
			return this.config.enforcePasswordForPublicLink && this.share && !this.share.id
		},
		pendingExpirationDate() {
			return this.config.isDefaultExpireDateEnforced && this.share && !this.share.id
		},

		/**
		 * Can the recipient edit the file ?
		 *
		 * @return {boolean}
		 */
		canUpdate: {
			get() {
				return this.share.hasUpdatePermission
			},
			set(enabled) {
				this.share.permissions = enabled
					? OC.PERMISSION_READ | OC.PERMISSION_UPDATE
					: OC.PERMISSION_READ
			},
		},

		// if newPassword exists, but is empty, it means
		// the user deleted the original password
		hasUnsavedPassword() {
			return this.share.newPassword !== undefined
		},

		/**
		 * Is the current share a folder ?
		 * TODO: move to a proper FileInfo model?
		 *
		 * @return {boolean}
		 */
		isFolder() {
			return this.fileInfo.type === 'dir'
		},

		/**
		 * Does the current file/folder have create permissions
		 * TODO: move to a proper FileInfo model?
		 *
		 * @return {boolean}
		 */
		fileHasCreatePermission() {
			return !!(this.fileInfo.permissions & OC.PERMISSION_CREATE)
		},

		/**
		 * Return the public share link
		 *
		 * @return {string}
		 */
		shareLink() {
			return window.location.protocol + '//' + window.location.host + generateUrl('/s/') + this.share.token
		},

		/**
		 * Clipboard v-tooltip message
		 *
		 * @return {string}
		 */
		clipboardTooltip() {
			if (this.copied) {
				return this.copySuccess
					? t('files_sharing', 'Link copied')
					: t('files_sharing', 'Cannot copy, please copy the link manually')
			}
			return t('files_sharing', 'Copy to clipboard')
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
			// filter only the registered actions for said link
			return this.ExternalShareActions.actions
				.filter(action => action.shareType.includes(OC.Share.SHARE_TYPE_LINK)
					|| action.shareType.includes(OC.Share.SHARE_TYPE_EMAIL))
		},

		isPasswordPolicyEnabled() {
			return typeof this.config.passwordPolicy === 'object'
		},
	},

	methods: {
		/**
		 * Create a new share link and append it to the list
		 */
		async onNewLinkShare() {
			// do not run again if already loading
			if (this.loading) {
				return
			}

			const shareDefaults = {
				share_type: OC.Share.SHARE_TYPE_LINK,
			}
			if (this.config.isDefaultExpireDateEnforced) {
				// default is empty string if not set
				// expiration is the share object key, not expireDate
				shareDefaults.expiration = this.config.defaultExpirationDateString
			}
			if (this.config.enableLinkPasswordByDefault) {
				shareDefaults.password = await GeneratePassword()
			}

			// do not push yet if we need a password or an expiration date: show pending menu
			if (this.config.enforcePasswordForPublicLink || this.config.isDefaultExpireDateEnforced) {
				this.pending = true

				// if a share already exists, pushing it
				if (this.share && !this.share.id) {
					// if the share is valid, create it on the server
					if (this.checkShare(this.share)) {
						await this.pushNewLinkShare(this.share, true)
						return true
					} else {
						this.open = true
						OC.Notification.showTemporary(t('files_sharing', 'Error, please enter proper password and/or expiration date'))
						return false
					}
				}

				// ELSE, show the pending popovermenu
				// if password enforced, pre-fill with random one
				if (this.config.enforcePasswordForPublicLink) {
					shareDefaults.password = await GeneratePassword()
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
				const share = new Share(shareDefaults)
				await this.pushNewLinkShare(share)
			}
		},

		/**
		 * Push a new link share to the server
		 * And update or append to the list
		 * accordingly
		 *
		 * @param {Share} share the new share
		 * @param {boolean} [update=false] do we update the current share ?
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
				const newShare = await this.createShare({
					path,
					shareType: OC.Share.SHARE_TYPE_LINK,
					password: share.password,
					expireDate: share.expireDate,
					// we do not allow setting the publicUpload
					// before the share creation.
					// Todo: We also need to fix the createShare method in
					// lib/Controller/ShareAPIController.php to allow file drop
					// (currently not supported on create, only update)
				})

				this.open = false

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

				// Execute the copy link method
				// freshly created share component
				// ! somehow does not works on firefox !
				if (!this.config.enforcePasswordForPublicLink) {
					// Only copy the link when the password was not forced,
					// otherwise the user needs to copy/paste the password before finishing the share.
					component.copyLink()
				}

			} catch ({ response }) {
				const message = response.data.ocs.meta.message
				if (message.match(/password/i)) {
					this.onSyncError('password', message)
				} else if (message.match(/date/i)) {
					this.onSyncError('expireDate', message)
				} else {
					this.onSyncError('pending', message)
				}
			} finally {
				this.loading = false
			}
		},

		/**
		 * On permissions change
		 *
		 * @param {Event} event js event
		 */
		togglePermissions(event) {
			const permissions = parseInt(event.target.value, 10)
			this.share.permissions = permissions
			this.queueUpdate('permissions')
		},

		/**
		 * Label changed, let's save it to a different key
		 *
		 * @param {string} label the share label
		 */
		onLabelChange(label) {
			this.$set(this.share, 'newLabel', label.trim())
		},

		/**
		 * When the note change, we trim, save and dispatch
		 */
		onLabelSubmit() {
			if (typeof this.share.newLabel === 'string') {
				this.share.label = this.share.newLabel
				this.$delete(this.share, 'newLabel')
				this.queueUpdate('label')
			}
		},
		async copyLink() {
			try {
				await this.$copyText(this.shareLink)
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
		 * is ran simultaneously as @uncheck, so
		 * so we cannot ensure data is up-to-date
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
		 * Menu have been closed or password has been submited.
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
		 * Cancel the share creation
		 * Used in the pending popover
		 */
		onCancel() {
			// this.share already exists at this point,
			// but is incomplete as not pushed to server
			// YET. We can safely delete the share :)
			this.$emit('remove:share', this.share)
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
		overflow: hidden;

		h5 {
			text-overflow: ellipsis;
			overflow: hidden;
			white-space: nowrap;
		}
		p {
			color: var(--color-text-maxcontrast);
		}
	}

	&:not(.sharing-entry--share) &__actions {
		.new-share-link {
			border-top: 1px solid var(--color-border);
		}
	}

	::v-deep .avatar-link-share {
		background-color: var(--color-primary);
	}

	.sharing-entry__action--public-upload {
		border-bottom: 1px solid var(--color-border);
	}

	&__loading {
		width: 44px;
		height: 44px;
		margin: 0;
		padding: 14px;
		margin-left: auto;
	}

	// put menus to the left
	// but only the first one
	.action-item {
		margin-left: auto;
		~ .action-item,
		~ .sharing-entry__loading {
			margin-left: 0;
		}
	}

	.icon-checkmark-color {
		opacity: 1;
	}
}
</style>
