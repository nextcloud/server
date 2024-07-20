<!--
 - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<NcDialog dialog-classes="legacy-prompt__dialog"
		:buttons="buttons"
		:name="name"
		@update:open="$emit('close', false, inputValue)">
		<p class="legacy-prompt__text" v-text="text" />
		<NcPasswordField v-if="isPassword"
			ref="input"
			autocomplete="new-password"
			class="legacy-prompt__input"
			:label="name"
			:name="inputName"
			:value.sync="inputValue" />
		<NcTextField v-else
			ref="input"
			class="legacy-prompt__input"
			:label="name"
			:name="inputName"
			:value.sync="inputValue" />
	</NcDialog>
</template>

<script lang="ts">
import { translate as t } from '@nextcloud/l10n'
import { defineComponent } from 'vue'

import NcDialog from '@nextcloud/vue/dist/Components/NcDialog.js'
import NcTextField from '@nextcloud/vue/dist/Components/NcTextField.js'
import NcPasswordField from '@nextcloud/vue/dist/Components/NcPasswordField.js'

export default defineComponent({
	name: 'LegacyDialogPrompt',

	components: {
		NcDialog,
		NcTextField,
		NcPasswordField,
	},

	props: {
		name: {
			type: String,
			required: true,
		},

		text: {
			type: String,
			required: true,
		},

		isPassword: {
			type: Boolean,
			required: true,
		},

		inputName: {
			type: String,
			default: 'prompt-input',
		},
	},

	emits: ['close'],

	data() {
		return {
			inputValue: '',
		}
	},

	computed: {
		buttons() {
			return [
				{
					label: t('core', 'No'),
					callback: () => this.$emit('close', false, this.inputValue),
				},
				{
					label: t('core', 'Yes'),
					type: 'primary',
					callback: () => this.$emit('close', true, this.inputValue),
				},
			]
		},
	},

	mounted() {
		this.$nextTick(() => this.$refs.input?.focus?.())
	},
})
</script>

<style scoped lang="scss">
.legacy-prompt {
	&__text {
		margin-block: 0 .75em;
	}

	&__input {
		margin-block: 0 1em;
	}
}

:deep(.legacy-prompt__dialog .dialog__actions) {
	min-width: calc(100% - 12px);
	justify-content: space-between;
}
</style>
