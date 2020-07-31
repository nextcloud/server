<!--
  - @copyright Copyright (c) 2020 Georg Ehrke <oc.list@georgehrke.com>
  - @author Georg Ehrke <oc.list@georgehrke.com>
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
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program. If not, see <http://www.gnu.org/licenses/>.
  -
  -->

<template>
	<Modal
		size="normal"
		:title="$t('user_status', 'Set a custom status')"
		@close="closeModal">
		<div class="set-status-modal">
			<div class="set-status-modal__header">
				<h3>{{ $t('user_status', 'Set a custom status') }}</h3>
			</div>
			<div class="set-status-modal__custom-input">
				<EmojiPicker @select="setIcon">
					<button
						class="custom-input__emoji-button">
						{{ visibleIcon }}
					</button>
				</EmojiPicker>
				<CustomMessageInput
					:message="message"
					@change="setMessage" />
			</div>
			<PredefinedStatusesList
				@selectStatus="selectPredefinedMessage" />
			<ClearAtSelect
				:clear-at="clearAt"
				@selectClearAt="setClearAt" />
			<div class="status-buttons">
				<button class="status-buttons__select" @click="clearStatus">
					{{ $t('user_status', 'Clear custom status') }}
				</button>
				<button class="status-buttons__primary primary" @click="saveStatus">
					{{ $t('user_status', 'Set status') }}
				</button>
			</div>
		</div>
	</Modal>
</template>

<script>
import EmojiPicker from '@nextcloud/vue/dist/Components/EmojiPicker'
import Modal from '@nextcloud/vue/dist/Components/Modal'
import PredefinedStatusesList from './PredefinedStatusesList'
import CustomMessageInput from './CustomMessageInput'
import ClearAtSelect from './ClearAtSelect'
import { showError } from '@nextcloud/dialogs'

export default {
	name: 'SetStatusModal',
	components: {
		EmojiPicker,
		Modal,
		CustomMessageInput,
		PredefinedStatusesList,
		ClearAtSelect,
	},
	data() {
		return {
			icon: null,
			message: null,
			clearAt: null,
		}
	},
	computed: {
		/**
		 * Returns the user-set icon or a smiley in case no icon is set
		 *
		 * @returns {String}
		 */
		visibleIcon() {
			return this.icon || '😀'
		},
	},
	/**
	 * Loads the current status when a user opens dialog
	 */
	mounted() {
		this.messageId = this.$store.state.userStatus.messageId
		this.icon = this.$store.state.userStatus.icon
		this.message = this.$store.state.userStatus.message

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
		 * @param {String} icon The new icon
		 */
		setIcon(icon) {
			this.messageId = null
			this.icon = icon
		},
		/**
		 * Sets a new message
		 *
		 * @param {String} message The new message
		 */
		setMessage(message) {
			this.messageId = null
			this.message = message
		},
		/**
		 * Sets a new clearAt value
		 *
		 * @param {Object} clearAt The new clearAt object
		 */
		setClearAt(clearAt) {
			this.clearAt = clearAt
		},
		/**
		 * Sets new icon/message/clearAt based on a predefined message
		 *
		 * @param {Object} status The predefined status object
		 */
		selectPredefinedMessage(status) {
			this.messageId = status.id
			this.clearAt = status.clearAt
			this.icon = status.icon
			this.message = status.message
		},
		/**
		 * Saves the status and closes the
		 *
		 * @returns {Promise<void>}
		 */
		async saveStatus() {
			try {
				this.isSavingStatus = true

				if (this.messageId !== null) {
					await this.$store.dispatch('setPredefinedMessage', {
						messageId: this.messageId,
						clearAt: this.clearAt,
					})
				} else {
					await this.$store.dispatch('setCustomMessage', {
						message: this.message,
						icon: this.icon,
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
		 * @returns {Promise<void>}
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
			this.closeModal()
		},
	},
}
</script>

<style lang="scss" scoped>
.set-status-modal {
	min-width: 500px;
	min-height: 200px;
	padding: 8px 20px 20px 20px;

	&__custom-input {
		display: flex;
		width: 100%;
		margin-bottom: 10px;

		.custom-input__emoji-button {
			flex-basis: 40px;
			width: 40px;
			flex-grow: 0;
			border-radius: var(--border-radius) 0 0 var(--border-radius);
			height: 34px;
			margin-right: 0;
			border-right: none;
		}
	}

	.status-buttons {
		display: flex;

		button {
			flex-basis: 50%;
		}
	}
}
</style>
