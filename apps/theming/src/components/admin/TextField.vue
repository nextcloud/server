<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { AdminThemingParameters } from '../../types.d.ts'

import { loadState } from '@nextcloud/initial-state'
import { watchDebounced } from '@vueuse/core'
import { ref, toRef } from 'vue'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import NcTextField from '@nextcloud/vue/components/NcTextField'
import { useAdminThemingValue } from '../../composables/useAdminThemingValue.ts'

const props = withDefaults(defineProps<{
	name: keyof AdminThemingParameters
	label: string
	defaultValue: string
	type?: 'text' | 'url'
}>(), {
	type: 'text',
})

const modelValue = ref(loadState<AdminThemingParameters>('theming', 'adminThemingParameters')[props.name].toString())

const {
	isSaving,
	isSaved,
	reset,
} = useAdminThemingValue(toRef(() => props.name), modelValue, toRef(() => props.defaultValue))

watchDebounced(modelValue, (value) => {
	if (props.type === 'url' && value.includes('"')) {
		try {
			const url = new URL(value)
			url.pathname = url.pathname.replaceAll(/"/g, '%22')
			modelValue.value = url.href
		} catch {
			// invalid URL, do nothing
			return
		}
	}
}, { debounce: 600 })
</script>

<template>
	<NcTextField
		v-model="modelValue"
		:label
		:readonly="isSaving"
		:success="isSaved"
		:type
		:show-trailing-button="modelValue !== defaultValue"
		:trailing-button-icon="defaultValue ? 'undo' : 'close'"
		@trailing-button-click="reset">
		<template v-if="isSaving" #icon>
			<NcLoadingIcon />
		</template>
	</NcTextField>
</template>
