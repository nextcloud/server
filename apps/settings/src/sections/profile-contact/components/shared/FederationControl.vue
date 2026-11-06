<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { computed, useTemplateRef } from 'vue'
import NcActionButton from '@nextcloud/vue/components/NcActionButton'
import NcActions from '@nextcloud/vue/components/NcActions'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import {
	PROPERTY_READABLE_KEYS_ENUM,
	PROPERTY_READABLE_SUPPORTED_SCOPES_ENUM,
	SCOPE_ENUM,
	SCOPE_PROPERTY_ENUM,
	UNPUBLISHED_READABLE_PROPERTIES,
} from '../../../../constants/AccountPropertyConstants.ts'
import { savePrimaryAccountPropertyScope } from '../../../../service/PersonalInfo/PersonalInfoService.js'
import { handleError } from '../../../../utils/handlers.ts'

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
	handleAdditionalScopeChange?: (value: string, scope: string) => Promise<{ ocs?: { meta?: { status?: string } } }>
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

const {
	federationEnabled,
	lookupServerUploadEnabled,
} = loadState<{ federationEnabled?: boolean, lookupServerUploadEnabled?: boolean }>('settings', 'accountParameters', {})

const federationActions = useTemplateRef('federationActions')

let savedScope = props.scope

const readableLowerCase = props.readable.toLocaleLowerCase()
const federationScopes: { name: SCOPE_ENUM, displayName: string, tooltip: string, tooltipDisabled?: string, icon: string }[] = Object.values(SCOPE_PROPERTY_ENUM)

const ariaLabel = computed(() => t('settings', 'Change scope level of {property}, current scope is {scope}', {
	property: readableLowerCase,
	scope: SCOPE_PROPERTY_ENUM[props.scope].displayName.toLocaleLowerCase(),
}))

const supportedScopes = computed(() => {
	// copy to avoid mutating the shared constant
	const scopes = [...PROPERTY_READABLE_SUPPORTED_SCOPES_ENUM[props.readable]]
	if (UNPUBLISHED_READABLE_PROPERTIES.includes(props.readable)) {
		return scopes
	}
	if (federationEnabled) {
		scopes.push(SCOPE_ENUM.FEDERATED)
	}
	if (lookupServerUploadEnabled) {
		scopes.push(SCOPE_ENUM.PUBLISHED)
	}
	return scopes
})

/**
 * @param scope The picked scope
 */
async function changeScope(scope: string) {
	emit('update:scope', scope)

	try {
		const responseData = props.additional
			? await props.handleAdditionalScopeChange!(props.additionalValue, scope)
			: await savePrimaryAccountPropertyScope(PROPERTY_READABLE_KEYS_ENUM[props.readable], scope)
		if (responseData.ocs?.meta?.status !== 'ok') {
			throw new Error('Unexpected response')
		}
		savedScope = scope
	} catch (error) {
		emit('update:scope', savedScope)
		handleError(error, props.additional
			? t('settings', 'Unable to update federation scope of additional {property}', { property: readableLowerCase })
			: t('settings', 'Unable to update federation scope of the primary {property}', { property: readableLowerCase }))
	}

	// TODO: provide focus method from NcActions
	(federationActions.value?.$refs?.triggerButton as { $el?: HTMLElement } | undefined)?.$el?.focus()
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
