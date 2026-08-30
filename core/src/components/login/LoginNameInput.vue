<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script lang="ts">
export default {
	// Attributes are forwarded to the input element instead of the wrapper,
	// otherwise the `id` would be duplicated and break the label association.
	inheritAttrs: false,
}
</script>

<script setup lang="ts">
import { t } from '@nextcloud/l10n'
import { useVModel } from '@vueuse/core'
import { computed, ref } from 'vue'
import NcTextField from '@nextcloud/vue/components/NcTextField'

const props = defineProps<{
	// eslint-disable-next-line vue/no-unused-properties
	user: string
	allowEmail?: boolean
	autoCompleteAllowed?: boolean
}>()

defineEmits(['update:user'])

defineExpose({
	focus,
})

const userName = useVModel(props, 'user')

const inputElement = ref<InstanceType<typeof NcTextField>>()

const hasError = computed(() => userName.value.length >= 255)
const helperText = computed(() => {
	if (hasError.value) {
		return t('core', 'Email length is at max (255)')
	}
	return ''
})

/**
 * Focus the input element.
 */
function focus() {
	inputElement.value?.focus()
}
</script>

<template>
	<NcTextField
		id="user"
		ref="inputElement"
		v-bind="$attrs"
		v-model="userName"
		:label="allowEmail ? t('core', 'Account name or email') : t('core', 'Account name')"
		name="user"
		:maxlength="255"
		autocapitalize="none"
		:autocomplete="autoCompleteAllowed ? 'username' : 'off'"
		:error="hasError"
		:helper-text="helperText" />
</template>
