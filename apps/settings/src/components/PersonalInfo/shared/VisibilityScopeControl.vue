<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<FederationControl
		v-if="!showCombined"
		:readable="readable"
		:scope="scope"
		:additional="additional"
		:additional-value="additionalValue"
		:disabled="disabled"
		:handle-additional-scope-change="handleAdditionalScopeChange"
		@update:scope="(value) => emit('update:scope', value)" />

	<NcPopover v-else :shown.sync="open">
		<template #trigger="{ attrs }">
			<NcButton
				v-bind="attrs"
				:aria-label="ariaLabel"
				:disabled="disabled"
				variant="tertiary"
				type="button">
				<template #icon>
					<NcIconSvgWrapper :path="scopeIcon" />
				</template>
			</NcButton>
		</template>

		<div class="visibility-scope">
			<h3 class="visibility-scope__heading">
				{{ t('settings', 'Visibility & Scope') }}
			</h3>
			<p class="visibility-scope__description">
				{{ t('settings', 'The more restrictive setting of either visibility or scope is respected on your Profile. For example, if visibility is set to "Show to everyone" and scope is set to "Private", "Private" is respected.') }}
			</p>

			<NcSelect
				class="visibility-scope__select"
				:aria-label-listbox="t('settings', 'Visibility')"
				:clearable="false"
				:options="visibilityOptions"
				:model-value="visibilityOption"
				label="label"
				label-outside
				@option:selected="onVisibilityChange">
				<template #option="{ label, icon }">
					<span class="visibility-scope__option">
						<NcIconSvgWrapper :path="icon" :size="20" />
						<span>{{ label }}</span>
					</span>
				</template>
				<template #selected-option="{ label, icon }">
					<span class="visibility-scope__option">
						<NcIconSvgWrapper :path="icon" :size="20" />
						<span>{{ label }}</span>
					</span>
				</template>
			</NcSelect>

			<NcSelect
				class="visibility-scope__select"
				:aria-label-listbox="t('settings', 'Scope')"
				:clearable="false"
				:options="scopeOptions"
				:model-value="scopeOption"
				label="displayName"
				label-outside
				@option:selected="onScopeChange">
				<template #option="{ displayName, tooltip, icon }">
					<span class="visibility-scope__option">
						<NcIconSvgWrapper :path="icon" :size="20" />
						<span class="visibility-scope__option-text">
							<span>{{ displayName }}</span>
							<span class="visibility-scope__option-description">{{ tooltip }}</span>
						</span>
					</span>
				</template>
				<template #selected-option="{ displayName, icon }">
					<span class="visibility-scope__option">
						<NcIconSvgWrapper :path="icon" :size="20" />
						<span>{{ displayName }}</span>
					</span>
				</template>
			</NcSelect>

			<NcButton
				class="visibility-scope__done"
				variant="primary"
				wide
				@click="open = false">
				{{ t('settings', 'Done') }}
			</NcButton>
		</div>
	</NcPopover>
</template>

<script setup lang="ts">
import { subscribe, unsubscribe } from '@nextcloud/event-bus'
import { loadState } from '@nextcloud/initial-state'
import { translate as t } from '@nextcloud/l10n'
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcPopover from '@nextcloud/vue/components/NcPopover'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import FederationControl from './FederationControl.vue'
import {
	PROPERTY_READABLE_KEYS_ENUM,
	PROPERTY_READABLE_SUPPORTED_SCOPES_ENUM,
	SCOPE_ENUM,
	SCOPE_PROPERTY_ENUM,
	UNPUBLISHED_READABLE_PROPERTIES,
} from '../../../constants/AccountPropertyConstants.ts'
import { VISIBILITY_PROPERTY_ENUM } from '../../../constants/ProfileConstants.js'
import { savePrimaryAccountPropertyScope } from '../../../service/PersonalInfo/PersonalInfoService.js'
import { saveProfileParameterVisibility } from '../../../service/ProfileService.js'
import { handleError } from '../../../utils/handlers.ts'

const props = defineProps({
	readable: {
		type: String,
		required: true,
	},

	name: {
		type: String,
		required: true,
	},

	scope: {
		type: String,
		required: true,
	},

	additional: {
		type: Boolean,
		default: false,
	},

	additionalValue: {
		type: String,
		default: '',
	},

	handleAdditionalScopeChange: {
		type: Function,
		default: null,
	},

	disabled: {
		type: Boolean,
		default: false,
	},
})

const emit = defineEmits<{
	(e: 'update:scope', scope: string): void
}>()

const { profileConfig } = loadState('settings', 'profileParameters', { profileConfig: {} })
const { profileEnabled: initialProfileEnabled, profileEnabledGlobally } = loadState('settings', 'personalInfoParameters', {})
const { federationEnabled, lookupServerUploadEnabled } = loadState('settings', 'accountParameters', {})

const open = ref(false)
const profileEnabled = ref(initialProfileEnabled)
const localScope = ref(props.scope)
const visibility = ref(profileConfig[props.name]?.visibility ?? null)

const showCombined = computed(() => Boolean(profileEnabledGlobally)
	&& profileEnabled.value
	&& visibility.value !== null
	&& !props.additional)

const ariaLabel = computed(() => t('settings', 'Change visibility and scope of {property}', { property: props.readable.toLocaleLowerCase() }))

const scopeIcon = computed(() => SCOPE_PROPERTY_ENUM[localScope.value].icon)

const visibilityOptions = computed(() => Object.values(VISIBILITY_PROPERTY_ENUM))

const visibilityOption = computed(() => VISIBILITY_PROPERTY_ENUM[visibility.value])

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

const scopeOptions = computed(() => supportedScopes.value.map((scope) => SCOPE_PROPERTY_ENUM[scope]))

const scopeOption = computed(() => SCOPE_PROPERTY_ENUM[localScope.value])

watch(() => props.scope, (value) => {
	localScope.value = value
})

function handleProfileEnabledUpdate(value) {
	profileEnabled.value = value
}

onMounted(() => {
	subscribe('settings:profile-enabled:updated', handleProfileEnabledUpdate)
})

onBeforeUnmount(() => {
	unsubscribe('settings:profile-enabled:updated', handleProfileEnabledUpdate)
})

async function onVisibilityChange(option) {
	if (!option) {
		return
	}
	const previous = visibility.value
	visibility.value = option.name
	try {
		const responseData = await saveProfileParameterVisibility(props.name, option.name)
		if (responseData.ocs?.meta?.status !== 'ok') {
			throw new Error('Unexpected response')
		}
	} catch (e) {
		visibility.value = previous
		handleError(e, t('settings', 'Unable to update visibility of {property}', { property: props.readable.toLocaleLowerCase() }))
	}
}

async function onScopeChange(option) {
	if (!option) {
		return
	}
	const previous = localScope.value
	localScope.value = option.name
	emit('update:scope', option.name)
	try {
		let responseData
		if (props.additional && typeof props.handleAdditionalScopeChange === 'function') {
			responseData = await props.handleAdditionalScopeChange(props.additionalValue, option.name)
		} else {
			responseData = await savePrimaryAccountPropertyScope(PROPERTY_READABLE_KEYS_ENUM[props.readable], option.name)
		}
		if (responseData.ocs?.meta?.status !== 'ok') {
			throw new Error('Unexpected response')
		}
	} catch (e) {
		localScope.value = previous
		emit('update:scope', previous)
		handleError(e, t('settings', 'Unable to update scope of {property}', { property: props.readable.toLocaleLowerCase() }))
	}
}
</script>

<style lang="scss" scoped>
.visibility-scope {
	display: flex;
	flex-direction: column;
	gap: 12px;
	padding: 16px;
	max-width: 360px;

	&__heading {
		margin: 0;
		font-size: 16px;
		font-weight: bold;
	}

	&__description {
		margin: 0;
		color: var(--color-text-maxcontrast);
	}

	&__select {
		width: 100%;
	}

	&__option {
		display: flex;
		align-items: center;
		gap: 8px;
		min-width: 0;
	}

	&__option-text {
		display: flex;
		flex-direction: column;
		min-width: 0;
	}

	&__option-description {
		color: var(--color-text-maxcontrast);
		white-space: normal;
	}

	&__done {
		margin-top: 4px;
	}
}
</style>
