<!--
  - SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcModal
		id="user_status-dialog"
		size="normal"
		labelId="user_status-set-dialog"
		dark
		:setReturnFocus="setReturnFocus"
		@close="closeModal">
		<div class="set-status-modal">
			<!-- Status selector -->
			<h2 id="user_status-set-dialog" class="set-status-modal__header">
				{{ t('user_status', 'Online status') }}
			</h2>
			<div
				class="set-status-modal__online-status"
				role="radiogroup"
				:aria-label="t('user_status', 'Online status')">
				<OnlineStatusSelect
					v-for="status in statuses"
					:key="status.type"
					v-bind="status"
					:checked="status.type === statusType"
					@select="changeStatus" />
			</div>

			<!-- Status message form -->
			<form @submit.prevent="saveStatus" @reset="clearStatus">
				<h3 class="set-status-modal__header">
					{{ t('user_status', 'Status message') }}
				</h3>
				<div class="set-status-modal__custom-input">
					<CustomMessageInput
						ref="customMessageInput"
						:icon="icon"
						:message="editedMessage"
						@change="setMessage"
						@selectIcon="setIcon" />
					<NcButton
						v-if="messageId === 'vacationing'"
						:href="absencePageUrl"
						target="_blank"
						variant="secondary"
						:aria-label="t('user_status', 'Set absence period')">
						{{ t('user_status', 'Set absence period and replacement') + ' ↗' }}
					</NcButton>
				</div>
				<div
					v-if="hasBackupStatus"
					class="set-status-modal__automation-hint">
					{{ t('user_status', 'Your status was set automatically') }}
				</div>
				<PreviousStatus
					v-if="hasBackupStatus"
					:icon="backupIcon"
					:message="backupMessage"
					@select="revertBackupFromServer" />
				<PredefinedStatusesList @selectStatus="selectPredefinedMessage" />
				<ClearAtSelect
					:clearAt="clearAt"
					@selectClearAt="setClearAt" />
				<div class="status-buttons">
					<NcButton
						:wide="true"
						variant="tertiary"
						type="reset"
						:aria-label="t('user_status', 'Clear status message')"
						:disabled="isSavingStatus">
						{{ t('user_status', 'Clear status message') }}
					</NcButton>
					<NcButton
						:wide="true"
						variant="primary"
						type="submit"
						:aria-label="t('user_status', 'Set status message')"
						:disabled="isSavingStatus">
						{{ t('user_status', 'Set status message') }}
					</NcButton>
				</div>
			</form>
		</div>
	</NcModal>
</template>

<script>
import { showError } from '@nextcloud/dialogs'
import { t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import { mapState, mapStores } from 'pinia'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcModal from '@nextcloud/vue/components/NcModal'
import ClearAtSelect from './ClearAtSelect.vue'
import CustomMessageInput from './CustomMessageInput.vue'
import OnlineStatusSelect from './OnlineStatusSelect.vue'
import PredefinedStatusesList from './PredefinedStatusesList.vue'
import PreviousStatus from './PreviousStatus.vue'
import { logger } from '../logger.ts'
import OnlineStatusMixin from '../mixins/OnlineStatusMixin.js'
import { getAllStatusOptions } from '../services/statusOptionsService.js'
import { useUserBackupStatusStore } from '../store/userBackupStatus.ts'
import { useUserStatusStore } from '../store/userStatus.ts'

export default {
	name: 'SetStatusModal',

	components: {
		ClearAtSelect,
		CustomMessageInput,
		NcModal,
		OnlineStatusSelect,
		PredefinedStatusesList,
		PreviousStatus,
		NcButton,
	},

	mixins: [OnlineStatusMixin],

	props: {
		/**
		 * Whether the component should be rendered as a Dashboard Status or a User Menu Entries
		 * true = Dashboard Status
		 * false = User Menu Entries
		 */
		inline: {
			type: Boolean,
			default: false,
		},
	},

	emits: ['close'],

	data() {
		return {
			clearAt: null,
			editedMessage: '',
			predefinedMessageId: null,
			isSavingStatus: false,
			statuses: getAllStatusOptions(),
		}
	},

	computed: {
		...mapStores(useUserBackupStatusStore),
		...mapState(useUserStatusStore, ['messageId', 'icon']),

		message() {
			return this.userStatusStore.message || ''
		},

		hasBackupStatus() {
			return this.messageId && (this.backupIcon || this.backupMessage)
		},

		backupIcon() {
			return this.userBackupStatusStore.icon || ''
		},

		backupMessage() {
			return this.userBackupStatusStore.message || ''
		},

		absencePageUrl() {
			return generateUrl('settings/user/availability#absence')
		},

		resetButtonText() {
			if (this.backupIcon && this.backupMessage) {
				return t('user_status', 'Reset status to "{icon} {message}"', {
					icon: this.backupIcon,
					message: this.backupMessage,
				})
			} else if (this.backupMessage) {
				return t('user_status', 'Reset status to "{message}"', {
					message: this.backupMessage,
				})
			} else if (this.backupIcon) {
				return t('user_status', 'Reset status to "{icon}"', {
					icon: this.backupIcon,
				})
			}

			return t('user_status', 'Reset status')
		},

		setReturnFocus() {
			if (this.inline) {
				return undefined
			}
			return document.querySelector('[aria-controls="header-menu-user-menu"]') ?? undefined
		},
	},

	watch: {
		message: {
			immediate: true,
			handler(newValue) {
				this.editedMessage = newValue
			},
		},
	},

	/**
	 * Loads the current status when a user opens dialog
	 */
	mounted() {
		this.userBackupStatusStore.fetchBackupFromServer()

		this.predefinedMessageId = this.userStatusStore.messageId
		if (this.userStatusStore.clearAt !== null) {
			this.clearAt = {
				type: '_time',
				time: this.userStatusStore.clearAt,
			}
		}
	},

	methods: {
		t,

		/**
		 * Closes the Set Status modal
		 */
		closeModal() {
			this.$emit('close')
		},

		/**
		 * Sets a new icon
		 *
		 * @param {string} icon The new icon
		 */
		setIcon(icon) {
			this.predefinedMessageId = null
			this.userStatusStore.setCustomMessage(this.message, icon, this.clearAt)
			this.$nextTick(() => {
				this.$refs.customMessageInput.focus()
			})
		},

		/**
		 * Sets a new message
		 *
		 * @param {string} message The new message
		 */
		setMessage(message) {
			this.predefinedMessageId = null
			this.editedMessage = message
		},

		/**
		 * Sets a new clearAt value
		 *
		 * @param {object} clearAt The new clearAt object
		 */
		setClearAt(clearAt) {
			this.clearAt = clearAt
		},

		/**
		 * Sets new icon/message/clearAt based on a predefined message
		 *
		 * @param {object} status The predefined status object
		 */
		selectPredefinedMessage(status) {
			this.predefinedMessageId = status.id
			this.clearAt = status.clearAt
			this.userStatusStore.setPredefinedMessage(status.id, status.clearAt)
		},

		/**
		 * Saves the status and closes the
		 *
		 * @return {Promise<void>}
		 */
		async saveStatus() {
			if (this.isSavingStatus) {
				return
			}

			try {
				this.isSavingStatus = true

				if (this.predefinedMessageId === null) {
					await this.userStatusStore.setCustomMessage(this.editedMessage, this.icon, this.clearAt)
				} else {
					await this.userStatusStore.setPredefinedMessage(this.predefinedMessageId, this.clearAt)
				}
			} catch (err) {
				showError(t('user_status', 'There was an error saving the status'))
				logger.debug(err)
				this.isSavingStatus = false
				return
			}

			this.isSavingStatus = false
			this.closeModal()
		},

		/**
		 *
		 * @return {Promise<void>}
		 */
		async clearStatus() {
			try {
				this.isSavingStatus = true

				await this.userStatusStore.clearMessage()
			} catch (err) {
				showError(t('user_status', 'There was an error clearing the status'))
				logger.debug(err)
				this.isSavingStatus = false
				return
			}

			this.isSavingStatus = false
			this.predefinedMessageId = null
			this.closeModal()
		},

		/**
		 *
		 * @return {Promise<void>}
		 */
		async revertBackupFromServer() {
			try {
				this.isSavingStatus = true

				await this.userBackupStatusStore.revertBackupFromServer(this.messageId)
			} catch (err) {
				showError(t('user_status', 'There was an error reverting the status'))
				logger.debug(err)
				this.isSavingStatus = false
				return
			}

			this.isSavingStatus = false
			this.predefinedMessageId = this.userStatusStore.messageId
		},
	},
}
</script>

<style lang="scss" scoped>

.set-status-modal {
	padding: 8px 20px 20px 20px;

	&, & * {
		box-sizing: border-box;
	}

	&__header {
		font-size: 21px;
		text-align: center;
		height: fit-content;
		min-height: var(--default-clickable-area);
		line-height: var(--default-clickable-area);
		overflow-wrap: break-word;
		margin-block: 0 calc(2 * var(--default-grid-baseline));
	}

	&__online-status {
		display: flex;
		flex-direction: column;
		gap: calc(2 * var(--default-grid-baseline));
		margin-block: 0 calc(2 * var(--default-grid-baseline));
	}

	&__custom-input {
		display: flex;
		flex-direction: column;
		align-items: center;
		gap: var(--default-grid-baseline);
		width: 100%;
		padding-inline-start: var(--default-grid-baseline);
		margin-block: 0 calc(2 * var(--default-grid-baseline));
	}

	&__automation-hint {
		display: flex;
		width: 100%;
		margin-block: 0 calc(2 * var(--default-grid-baseline));
		color: var(--color-text-maxcontrast);
	}

	.status-buttons {
		display: flex;
		padding: 3px;
		padding-inline-start:0;
		gap: 3px;
	}
}

@media only screen and (max-width: 500px) {
	.set-status-modal__online-status {
		grid-template-columns: none !important;
	}
}

</style>
