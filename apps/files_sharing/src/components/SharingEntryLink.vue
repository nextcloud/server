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
	<li :class="{ 'sharing-entry--share': share }" class="sharing-entry sharing-entry__link">
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

			<!-- clipboard -->
			<NcActions v-if="share && !isEmailShareType && share.token" ref="copyButton" class="sharing-entry__copy">
				<NcActionButton	:title="copyLinkTooltip"
					:aria-label="copyLinkTooltip"
					:icon="copied && copySuccess ? 'icon-checkmark-color' : 'icon-clippy'"
					@click.prevent="copyLink" />
			</NcActions>
		</div>

		<!-- pending actions -->
		<NcActions v-if="!pending && (pendingPassword || pendingEnforcedPassword || pendingExpirationDate)"
			class="sharing-entry__actions"
			:aria-label="actionsTooltip"
			menu-align="right"
			:open.sync="open"
			@close="onCancel">
			<!-- pending data menu -->
			<NcActionText v-if="errors.pending" icon="icon-error" :class="{ error: errors.pending }">
				{{ errors.pending }}
			</NcActionText>
			<NcActionText v-else icon="icon-info">
				{{ t('files_sharing', 'Please enter the following required information before creating the share') }}
			</NcActionText>

			<!-- password -->
			<NcActionText v-if="pendingEnforcedPassword" icon="icon-password">
				{{ t('files_sharing', 'Password protection (enforced)') }}
			</NcActionText>
			<NcActionCheckbox v-else-if="pendingPassword"
				:checked.sync="isPasswordProtected"
				:disabled="config.enforcePasswordForPublicLink || saving"
				class="share-link-password-checkbox"
				@uncheck="onPasswordDisable">
				{{ t('files_sharing', 'Password protection') }}
			</NcActionCheckbox>

			<NcActionInput v-if="pendingEnforcedPassword || share.password"
				class="share-link-password"
				:value.sync="share.password"
				:disabled="saving"
				:required="config.enableLinkPasswordByDefault || config.enforcePasswordForPublicLink"
				:minlength="isPasswordPolicyEnabled && config.passwordPolicy.minLength"
				icon=""
				autocomplete="new-password"
				@submit="onNewLinkShare">
				{{ t('files_sharing', 'Enter a password') }}
			</NcActionInput>

			<!-- expiration date -->
			<NcActionText v-if="pendingExpirationDate" icon="icon-calendar-dark">
				{{ t('files_sharing', 'Expiration date (enforced)') }}
			</NcActionText>
			<NcActionInput v-if="pendingExpirationDate"
				class="share-link-expire-date"
				:disabled="saving"
				:is-native-picker="true"
				:hide-label="true"
				:value="new Date(share.expireDate)"
				type="date"
				:min="dateTomorrow"
				:max="maxExpirationDateEnforced"
				@input="onExpirationChange">
				<!-- let's not submit when picked, the user
					might want to still edit or copy the password -->
				{{ t('files_sharing', 'Enter a date') }}
			</NcActionInput>

			<NcActionButton icon="icon-checkmark" @click.prevent.stop="onNewLinkShare">
				{{ t('files_sharing', 'Create share') }}
			</NcActionButton>
			<NcActionButton icon="icon-close" @click.prevent.stop="onCancel">
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
							<Tune />
						</template>
						{{ t('files_sharing', 'Customize link') }}
					</NcActionButton>
				</template>

				<NcActionSeparator />

				<!-- external actions -->
				<ExternalShareAction v-for="action in externalLinkActions"
					:id="action.id"
					:key="action.id"
					:action="action"
					:file-info="fileInfo"
					:share="share" />

				<!-- external legacy sharing via url (social...) -->
				<NcActionLink v-for="({ icon, url, name }, index) in externalLegacyLinkActions"
					:key="index"
					:href="url(shareLink)"
					:icon="icon"
					target="_blank">
					{{ name }}
				</NcActionLink>

				<NcActionButton v-if="!isEmailShareType && canReshare"
					class="new-share-link"
					icon="icon-add"
					@click.prevent.stop="onNewLinkShare">
					{{ t('files_sharing', 'Add another link') }}
				</NcActionButton>

				<NcActionButton v-if="share.canDelete"
					icon="icon-close"
					:disabled="saving"
					@click.prevent="onDelete">
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
	</li>
</template>

<script>
import { generateUrl } from '@nextcloud/router'
import { showError, showSuccess } from '@nextcloud/dialogs'
import { Type as ShareTypes } from '@nextcloud/sharing'
import Vue from 'vue'

import NcActionButton from '@nextcloud/vue/dist/Components/NcActionButton.js'
import NcActionInput from '@nextcloud/vue/dist/Components/NcActionInput.js'
import NcActionLink from '@nextcloud/vue/dist/Components/NcActionLink.js'
import NcActionText from '@nextcloud/vue/dist/Components/NcActionText.js'
import NcActionSeparator from '@nextcloud/vue/dist/Components/NcActionSeparator.js'
import NcActions from '@nextcloud/vue/dist/Components/NcActions.js'
import NcAvatar from '@nextcloud/vue/dist/Components/NcAvatar.js'

import Tune from 'vue-material-design-icons/Tune.vue'

import SharingEntryQuickShareSelect from './SharingEntryQuickShareSelect.vue'

import ExternalShareAction from './ExternalShareAction.vue'
import GeneratePassword from '../utils/GeneratePassword.js'
import Share from '../models/Share.js'
import SharesMixin from '../mixins/SharesMixin.js'
import ShareDetails from '../mixins/ShareDetails.js'

export default {
	name: 'SharingEntryLink',

	components: {
		ExternalShareAction,
		NcActions,
		NcActionButton,
		NcActionInput,
		NcActionLink,
		NcActionText,
		NcActionSeparator,
		NcAvatar,
		Tune,
		SharingEntryQuickShareSelect,
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
			copySuccess: true,
			copied: false,

			// Are we waiting for password/expiration date
			pending: false,

			ExternalLegacyLinkActions: OCA.Sharing.ExternalLinkActions.state,
			ExternalShareActions: OCA.Sharing.ExternalShareActions.state,
		}
	},

	computed: {
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
			if (this.index > 1) {
				return t('files_sharing', 'Share link ({index})', { index: this.index })
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
			return this.config.enableLinkPasswordByDefault && this.share && !this.share.id
		},
		pendingEnforcedPassword() {
			return this.config.enforcePasswordForPublicLink && this.share && !this.share.id
		},
		pendingExpirationDate() {
			return this.config.isDefaultExpireDateEnforced && this.share && !this.share.id
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
			return window.location.protocol + '//' + window.location.host + generateUrl('/s/') + this.share.token
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
			// filter only the registered actions for said link
			return this.ExternalShareActions.actions
				.filter(action => action.shareType.includes(ShareTypes.SHARE_TYPE_LINK)
					|| action.shareType.includes(ShareTypes.SHARE_TYPE_EMAIL))
		},

		isPasswordPolicyEnabled() {
			return typeof this.config.passwordPolicy === 'object'
		},

		canChangeHideDownload() {
			const hasDisabledDownload = (shareAttribute) => shareAttribute.key === 'download' && shareAttribute.scope === 'permissions' && shareAttribute.enabled === false
			return this.fileInfo.shareAttributes.some(hasDisabledDownload)
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
				share_type: ShareTypes.SHARE_TYPE_LINK,
			}
			if (this.config.isDefaultExpireDateEnforced) {
				// default is empty string if not set
				// expiration is the share object key, not expireDate
				shareDefaults.expiration = this.formatDateToString(this.config.defaultExpirationDate)
			}

			// do not push yet if we need a password or an expiration date: show pending menu
			if (this.config.enableLinkPasswordByDefault || this.config.enforcePasswordForPublicLink || this.config.isDefaultExpireDateEnforced) {
				this.pending = true

				// if a share already exists, pushing it
				if (this.share && !this.share.id) {
					// if the share is valid, create it on the server
					if (this.checkShare(this.share)) {
						try {
							await this.pushNewLinkShare(this.share, true)
						} catch (e) {
							this.pending = false
							console.error(e)
							return false
						}
						return true
					} else {
						this.open = true
						OC.Notification.showTemporary(t('files_sharing', 'Error, please enter proper password and/or expiration date'))
						return false
					}
				}

				// ELSE, show the pending popovermenu
				// if password default or enforced, pre-fill with random one
				if (this.config.enableLinkPasswordByDefault || this.config.enforcePasswordForPublicLink) {
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
					shareType: ShareTypes.SHARE_TYPE_LINK,
					password: share.password,
					expireDate: share.expireDate,
					attributes: JSON.stringify(this.fileInfo.shareAttributes),
					// we do not allow setting the publicUpload
					// before the share creation.
					// Todo: We also need to fix the createShare method in
					// lib/Controller/ShareAPIController.php to allow file drop
					// (currently not supported on create, only update)
				}

				console.debug('Creating link share with options', options)
				const newShare = await this.createShare(options)

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

	&__summary {
		padding: 8px;
		padding-left: 10px;
		display: flex;
		justify-content: space-between;
		flex: 1 0;
		min-width: 0;

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

	  &__copy {

	  }
	}

	&:not(.sharing-entry--share) &__actions {
		.new-share-link {
			border-top: 1px solid var(--color-border);
		}
	}

	::v-deep .avatar-link-share {
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
		margin-left: auto;
	}

	// put menus to the left
	// but only the first one
	.action-item {

		~.action-item,
		~.sharing-entry__loading {
			margin-left: 0;
		}
	}

	.icon-checkmark-color {
		opacity: 1;
	}
}
</style>
