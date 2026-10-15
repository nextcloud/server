<!--
 - SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<script setup lang="ts">
import { t } from '@nextcloud/l10n'
import { nextTick, onMounted, ref, useTemplateRef } from 'vue'
import NcDialog from '@nextcloud/vue/components/NcDialog'
import NcPasswordField from '@nextcloud/vue/components/NcPasswordField'
import NcTextField from '@nextcloud/vue/components/NcTextField'

withDefaults(defineProps<{
	name: string
	text: string
	isPassword: boolean
	inputName?: string
}>(), {
	inputName: 'prompt-input',
})

const emit = defineEmits<{
	close: [confirmed: boolean, value: string]
}>()

const input = useTemplateRef('input')
const inputValue = ref('')

const buttons = [
	{
		label: t('core', 'No'),
		callback: () => emit('close', false, inputValue.value),
	},
	{
		label: t('core', 'Yes'),
		variant: 'primary' as const,
		callback: () => emit('close', true, inputValue.value),
	},
]

onMounted(() => nextTick(() => input.value?.focus()))
</script>

<template>
	<NcDialog
		dialogClasses="legacy-prompt__dialog"
		:buttons="buttons"
		:name="name"
		@update:open="emit('close', false, inputValue)">
		<p class="legacy-prompt__text" v-text="text" />
		<NcPasswordField
			v-if="isPassword"
			ref="input"
			v-model="inputValue"
			autocomplete="new-password"
			class="legacy-prompt__input"
			:label="name"
			:name="inputName" />
		<NcTextField
			v-else
			ref="input"
			v-model="inputValue"
			class="legacy-prompt__input"
			:label="name"
			:name="inputName" />
	</NcDialog>
</template>

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
