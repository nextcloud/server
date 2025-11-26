<!--
  - SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div class="custom-input" role="group">
		<NcEmojiPicker container=".custom-input" @select="setIcon">
			<NcButton
				variant="tertiary"
				:aria-label="t('user_status', 'Emoji for your status message')">
				<template #icon>
					{{ visibleIcon }}
				</template>
			</NcButton>
		</NcEmojiPicker>
		<div class="custom-input__container">
			<NcTextField
				ref="input"
				maxlength="80"
				:disabled="disabled"
				:placeholder="t('user_status', 'What is your status?')"
				:model-value="message"
				type="text"
				:label="t('user_status', 'What is your status?')"
				@update:model-value="onChange" />
		</div>
	</div>
</template>

<script>
import { t } from '@nextcloud/l10n'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcEmojiPicker from '@nextcloud/vue/components/NcEmojiPicker'
import NcTextField from '@nextcloud/vue/components/NcTextField'

export default {
	name: 'CustomMessageInput',

	components: {
		NcTextField,
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
			default: '',
		},

		disabled: {
			type: Boolean,
			default: false,
		},
	},

	emits: [
		'change',
		'selectIcon',
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
		t,

		focus() {
			this.$refs.input.focus()
		},

		/**
		 * Notifies the parent component about a changed input
		 *
		 * @param {string} value The new input value
		 */
		onChange(value) {
			this.$emit('change', value)
		},

		setIcon(icon) {
			this.$emit('selectIcon', icon)
		},
	},
}
</script>

<style lang="scss" scoped>
.custom-input {
	display: flex;
	align-items: flex-end;
	gap: var(--default-grid-baseline);
	width: 100%;

	&__container {
		width: 100%;
	}
}
</style>
