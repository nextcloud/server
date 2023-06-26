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
	<div class="custom-input" role="group">
		<NcEmojiPicker container=".custom-input" @select="setIcon">
			<NcButton type="tertiary"
				class="custom-input__emoji-button"
				:aria-label="t('user_status', 'Emoji for your status message')">
				{{ visibleIcon }}
			</NcButton>
		</NcEmojiPicker>
		<div class="custom-input__container">
			<label class="hidden-visually" for="user_status_message">
				{{ t('user_status', 'What is your status?') }}
			</label>
			<input id="user_status_message"
				ref="input"
				maxlength="80"
				:disabled="disabled"
				:placeholder="$t('user_status', 'What is your status?')"
				type="text"
				:value="message"
				@change="onChange"
				@keyup="onKeyup"
				@paste="onKeyup">
		</div>
	</div>
</template>

<script>
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import NcEmojiPicker from '@nextcloud/vue/dist/Components/NcEmojiPicker.js'

export default {
	name: 'CustomMessageInput',

	components: {
		NcButton,
		NcEmojiPicker,
	},

	props: {
		icon: {
			type: String,
			default: '😀',
		},
		message: {
			type: String,
			required: true,
			default: () => '',
		},
		disabled: {
			type: Boolean,
			default: false,
		},
	},

	emits: [
		'change',
		'submit',
		'icon-selected',
	],

	computed: {
		/**
		 * Returns the user-set icon or a smiley in case no icon is set
		 *
		 * @return {string}
		 */
		visibleIcon() {
			return this.icon || '😀'
		},
	},

	methods: {
		focus() {
			this.$refs.input.focus()
		},

		/**
		 * Notifies the parent component about a changed input
		 *
		 * @param {Event} event The Change Event
		 */
		onKeyup(event) {
			this.$emit('change', event.target.value)
		},

		onChange(event) {
			this.$emit('submit', event.target.value)
		},

		setIcon(icon) {
			this.$emit('select-icon', icon)
		},
	},
}
</script>

<style lang="scss" scoped>
.custom-input {
	display: flex;
	width: 100%;

	&__emoji-button {
		min-height: 36px;
		padding: 0;
		border: 2px solid var(--color-border-maxcontrast);
		border-right: none;
		border-radius: var(--border-radius) 0 0 var(--border-radius);

		&:hover {
			border-color: var(--color-primary-element);
		}
	}

	&__container {
		width: 100%;

		input {
			width: 100%;
			margin: 0;
			border-radius: 0 var(--border-radius) var(--border-radius) 0;
		}
	}
}
</style>
