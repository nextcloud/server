<!--
  - SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<NcModal size="normal"
		:name="$t('user_status', 'Set status')"
		aria-labelledby="user_status-set-dialog"
		:set-return-focus="setReturnFocus"
		@close="closeModal">
		<div class="set-status-modal">
			<!-- Status selector -->
			<h2 id="user_status-set-dialog" class="set-status-modal__header">
				{{ $t('user_status', 'Online status') }}
			</h2>
			<div class="set-status-modal__online-status"
				role="radiogroup"
				:aria-label="$t('user_status', 'Online status')">
				<OnlineStatusSelect v-for="status in statuses"
					:key="status.type"
					v-bind="status"
					:checked="status.type === statusType"
					@select="changeStatus" />
			</div>

			<!-- Status message form -->
			<form @submit.prevent="saveStatus" @reset="clearStatus">
				<h3 class="set-status-modal__header">
					{{ $t('user_status', 'Status message') }}
				</h3>
				<div class="set-status-modal__custom-input">
					<CustomMessageInput ref="customMessageInput"
						:icon="icon"
						:message="editedMessage"
						@change="setMessage"
						@select-icon="setIcon" />
					<NcButton v-if="messageId === 'vacationing'"
						:href="absencePageUrl"
						target="_blank"
						type="secondary"
						:aria-label="$t('user_status', 'Set absence period')">
						{{ $t('user_status', 'Set absence period and replacement') + ' ↗' }}
					</NcButton>
				</div>
				<div v-if="hasBackupStatus"
					class="set-status-modal__automation-hint">
					{{ $t('user_status', 'Your status was set automatically') }}
				</div>
				<PreviousStatus v-if="hasBackupStatus"
					:icon="backupIcon"
					:message="backupMessage"
					@select="revertBackupFromServer" />
				<PredefinedStatusesList @select-status="selectPredefinedMessage" />
				<ClearAtSelect :clear-at="clearAt"
					@select-clear-at="setClearAt" />
				<div class="status-buttons">
					<NcButton :wide="true"
						type="tertiary"
						native-type="reset"
						:aria-label="$t('user_status', 'Clear status message')"
						:disabled="isSavingStatus">
						{{ $t('user_status', 'Clear status message') }}
					</NcButton>
					<NcButton :wide="true"
						type="primary"
						native-type="submit"
						:aria-label="$t('user_status', 'Set status message')"
						:disabled="isSavingStatus">
						{{ $t('user_status', 'Set status message') }}
					</NcButton>
				</div>
			</form>
		</div>
	</NcModal>
</template>

<script>
import { showError } from '@nextcloud/dialogs'
import { generateUrl } from '@nextcloud/router'
import NcModal from '@nextcloud/vue/dist/Components/NcModal.js'
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import { getAllStatusOptions } from '../services/statusOptionsService.js'
import OnlineStatusMixin from '../mixins/OnlineStatusMixin.js'
import PredefinedStatusesList from './PredefinedStatusesList.vue'
import PreviousStatus from './PreviousStatus.vue'
import CustomMessageInput from './CustomMessageInput.vue'
import ClearAtSelect from './ClearAtSelect.vue'
import OnlineStatusSelect from './OnlineStatusSelect.vue'

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
		messageId() {
			return this.$store.state.userStatus.messageId
		},
		icon() {
			return this.$store.state.userStatus.icon
		},
		message() {
			return this.$store.state.userStatus.message || ''
		},
		hasBackupStatus() {
			return this.messageId && (this.backupIcon || this.backupMessage)
		},
		backupIcon() {
			return this.$store.state.userBackupStatus.icon || ''
		},
		backupMessage() {
			return this.$store.state.userBackupStatus.message || ''
		},

		absencePageUrl() {
			return generateUrl('settings/user/availability#absence')
		},

		resetButtonText() {
			if (this.backupIcon && this.backupMessage) {
				return this.$t('user_status', 'Reset status to "{icon} {message}"', {
					icon: this.backupIcon,
					message: this.backupMessage,
				})
			} else if (this.backupMessage) {
				return this.$t('user_status', 'Reset status to "{message}"', {
					message: this.backupMessage,
				})
			} else if (this.backupIcon) {
				return this.$t('user_status', 'Reset status to "{icon}"', {
					icon: this.backupIcon,
				})
			}

			return this.$t('user_status', 'Reset status')
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
		this.$store.dispatch('fetchBackupFromServer')

		this.predefinedMessageId = this.$store.state.userStatus.messageId
		if (this.$store.state.userStatus.clearAt !== null) {
			this.clearAt = {
				type: '_time',
				time: this.$store.state.userStatus.clearAt,
			}
		}
	},
	methods: {
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
			this.$store.dispatch('setCustomMessage', {
				message: this.message,
				icon,
				clearAt: this.clearAt,
			})
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
			this.$store.dispatch('setPredefinedMessage', {
				messageId: status.id,
				clearAt: status.clearAt,
			})
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
					await this.$store.dispatch('setCustomMessage', {
						message: this.editedMessage,
						icon: this.icon,
						clearAt: this.clearAt,
					})
				} else {
					this.$store.dispatch('setPredefinedMessage', {
						messageId: this.predefinedMessageId,
						clearAt: this.clearAt,
					})
				}
			} catch (err) {
				showError(this.$t('user_status', 'There was an error saving the status'))
				console.debug(err)
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

				await this.$store.dispatch('clearMessage')
			} catch (err) {
				showError(this.$t('user_status', 'There was an error clearing the status'))
				console.debug(err)
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

				await this.$store.dispatch('revertBackupFromServer', {
					messageId: this.messageId,
				})
			} catch (err) {
				showError(this.$t('user_status', 'There was an error reverting the status'))
				console.debug(err)
				this.isSavingStatus = false
				return
			}

			this.isSavingStatus = false
			this.predefinedMessageId = this.$store.state.userStatus?.messageId
		},
	},
}
</script>

<style lang="scss" scoped>

.set-status-modal {
	padding: 8px 20px 20px 20px;

	&__header {
		font-size: 21px;
		text-align: center;
		height: fit-content;
		min-height: var(--default-clickable-area);
		line-height: var(--default-clickable-area);
		overflow-wrap: break-word;
		margin-block: 0 12px;
	}

	&__online-status {
		display: grid;
		grid-template-columns: 1fr 1fr;
	}

	&__custom-input {
		display: flex;
		flex-direction: column;
		align-items: center;
		gap: var(--default-grid-baseline);
		width: 100%;
		margin-bottom: 10px;
	}

	&__automation-hint {
		display: flex;
		width: 100%;
		margin-bottom: 10px;
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
