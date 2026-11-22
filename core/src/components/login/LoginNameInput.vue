<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { t } from '@nextcloud/l10n'
import { computed, useTemplateRef } from 'vue'
import NcTextField from '@nextcloud/vue/components/NcTextField'

defineOptions({
	// Attributes are forwarded to the input element instead of the wrapper,
	// otherwise the `id` would be duplicated and break the label association.
	inheritAttrs: false,
})

const userName = defineModel<string>('user', { required: true })

const props = defineProps<{
	allowEmail?: boolean
	autoCompleteAllowed?: boolean
	error?: boolean
}>()

defineExpose({
	focus,
})

const inputElement = useTemplateRef('inputElement')

const isTooLong = computed(() => userName.value.length >= 255)
const hasError = computed(() => props.error || isTooLong.value)
const helperText = computed(() => {
	if (isTooLong.value) {
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
		:helperText="helperText" />
</template>
