<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { SCOPE_ENUM } from '../../../../constants/AccountPropertyConstants.ts'

import { t } from '@nextcloud/l10n'
import { computed, useTemplateRef } from 'vue'
import NcActionButton from '@nextcloud/vue/components/NcActionButton'
import NcActions from '@nextcloud/vue/components/NcActions'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import { SCOPE_PROPERTY_ENUM } from '../../../../constants/AccountPropertyConstants.ts'
import { useScopeChange } from '../../composables/useScopeChange.ts'

const props = withDefaults(defineProps<{
	/** Human readable name of the property */
	readable: string
	/** Federation scope of the property */
	scope: string
	/** Whether this controls an additional value, e.g. a secondary email */
	additional?: boolean
	/** The additional value this scope belongs to */
	additionalValue?: string
	/** Disable changing the scope */
	disabled?: boolean
	/** Saves the scope of an additional value */
	handleAdditionalScopeChange?: (value: string, scope: string) => Promise<unknown>
}>(), {
	additional: false,
	additionalValue: '',
	disabled: false,
	handleAdditionalScopeChange: undefined,
})

const emit = defineEmits<{
	/** The scope was changed, and reverted if saving it failed */
	'update:scope': [scope: string]
}>()

defineSlots<{
	/** Additional actions of the menu */
	default?: () => unknown
}>()

const federationActions = useTemplateRef('federationActions')

const readableLowerCase = props.readable.toLocaleLowerCase()
const federationScopes: { name: SCOPE_ENUM, displayName: string, tooltip: string, tooltipDisabled?: string, icon: string }[] = Object.values(SCOPE_PROPERTY_ENUM)

const ariaLabel = computed(() => t('settings', 'Change scope level of {property}, current scope is {scope}', {
	property: readableLowerCase,
	scope: SCOPE_PROPERTY_ENUM[props.scope].displayName.toLocaleLowerCase(),
}))

const { supportedScopes, changeScope: saveScope } = useScopeChange(
	props,
	(scope) => emit('update:scope', scope),
	() => props.additional
		? t('settings', 'Unable to update federation scope of additional {property}', { property: readableLowerCase })
		: t('settings', 'Unable to update federation scope of the primary {property}', { property: readableLowerCase }),
)

/**
 * @param scope The picked scope
 */
async function changeScope(scope: string) {
	await saveScope(scope)

	// TODO: provide focus method from NcActions
	const triggerButton = federationActions.value?.$refs?.triggerButton as { $el?: HTMLElement } | undefined
	triggerButton?.$el?.focus()
}
</script>

<template>
	<NcActions
		ref="federationActions"
		:aria-label="ariaLabel"
		:disabled="disabled">
		<template #icon>
			<NcIconSvgWrapper :path="SCOPE_PROPERTY_ENUM[scope].icon" />
		</template>

		<NcActionButton
			v-for="federationScope in federationScopes"
			:key="federationScope.name"
			closeAfterClick
			:disabled="!supportedScopes.includes(federationScope.name)"
			:name="federationScope.displayName"
			type="radio"
			:value="federationScope.name"
			:modelValue="scope"
			@update:modelValue="changeScope">
			<template #icon>
				<NcIconSvgWrapper :path="federationScope.icon" />
			</template>
			{{ supportedScopes.includes(federationScope.name) ? federationScope.tooltip : federationScope.tooltipDisabled }}
		</NcActionButton>

		<slot />
	</NcActions>
</template>
