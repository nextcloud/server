<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { subscribe, unsubscribe } from '@nextcloud/event-bus'
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcPopover from '@nextcloud/vue/components/NcPopover'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import FederationControl from './FederationControl.vue'
import { SCOPE_PROPERTY_ENUM } from '../../../../constants/AccountPropertyConstants.ts'
import { VISIBILITY_PROPERTY_ENUM } from '../../../../constants/ProfileConstants.js'
import { saveProfileParameterVisibility } from '../../../../service/ProfileService.js'
import { handleError } from '../../../../utils/handlers.ts'
import { useScopeChange } from '../../composables/useScopeChange.ts'

const props = withDefaults(defineProps<{
	/** Human readable name of the property */
	readable: string
	/** Key of the account property */
	name: string
	/** Federation scope of the property */
	scope: string
	/** Whether this controls an additional value, e.g. a secondary email */
	additional?: boolean
	/** The additional value this scope belongs to */
	additionalValue?: string
	/** Saves the scope of an additional value */
	handleAdditionalScopeChange?: (value: string, scope: string) => Promise<unknown>
	/** Disable changing the scope */
	disabled?: boolean
}>(), {
	additional: false,
	additionalValue: '',
	handleAdditionalScopeChange: undefined,
	disabled: false,
})

const emit = defineEmits<{
	/** The scope was changed, and reverted if saving it failed */
	'update:scope': [scope: string]
}>()

const { profileConfig } = loadState<{ profileConfig: Record<string, { visibility: string }> }>('settings', 'profileParameters', { profileConfig: {} })
const { profileEnabled: initialProfileEnabled, profileEnabledGlobally } = loadState<{ profileEnabled?: boolean, profileEnabledGlobally?: boolean }>('settings', 'personalInfoParameters', {})

const open = ref(false)
const profileEnabled = ref(initialProfileEnabled)
const localScope = ref(props.scope)
const visibility = ref(profileConfig[props.name]?.visibility ?? null)
let savedVisibility = visibility.value

const showCombined = computed(() => Boolean(profileEnabledGlobally)
	&& profileEnabled.value
	&& visibility.value !== null
	&& !props.additional)

const ariaLabel = computed(() => t('settings', 'Change visibility and scope of {property}', { property: props.readable.toLocaleLowerCase() }))

const scopeIcon = computed(() => SCOPE_PROPERTY_ENUM[localScope.value].icon)

const visibilityOptions = computed(() => Object.values(VISIBILITY_PROPERTY_ENUM))

const visibilityOption = computed(() => VISIBILITY_PROPERTY_ENUM[visibility.value])

const { supportedScopes, changeScope } = useScopeChange(
	props,
	(scope) => {
		localScope.value = scope
		emit('update:scope', scope)
	},
	() => t('settings', 'Unable to update scope of {property}', { property: props.readable.toLocaleLowerCase() }),
)

const scopeOptions = computed(() => supportedScopes.value.map((scope) => SCOPE_PROPERTY_ENUM[scope]))

const scopeOption = computed(() => SCOPE_PROPERTY_ENUM[localScope.value])

watch(() => props.scope, (value) => {
	localScope.value = value
})

/**
 * @param value Whether the profile is enabled now
 */
function handleProfileEnabledUpdate(value: boolean) {
	profileEnabled.value = value
}

onMounted(() => {
	subscribe('settings:profile-enabled:updated', handleProfileEnabledUpdate)
})

onBeforeUnmount(() => {
	unsubscribe('settings:profile-enabled:updated', handleProfileEnabledUpdate)
})

/**
 * @param option The picked visibility
 */
async function onVisibilityChange(option: { name: string } | null) {
	if (!option) {
		return
	}
	visibility.value = option.name
	try {
		await saveProfileParameterVisibility(props.name, option.name)
		savedVisibility = option.name
	} catch (error) {
		visibility.value = savedVisibility
		handleError(error, t('settings', 'Unable to update visibility of {property}', { property: props.readable.toLocaleLowerCase() }))
	}
}

/**
 * @param option The picked scope
 */
async function onScopeChange(option: { name: string } | null) {
	if (option) {
		await changeScope(option.name)
	}
}
</script>

<template>
	<FederationControl
		v-if="!showCombined"
		:readable="readable"
		:scope="scope"
		:additional="additional"
		:additionalValue="additionalValue"
		:disabled="disabled"
		:handleAdditionalScopeChange="handleAdditionalScopeChange"
		@update:scope="(value) => emit('update:scope', value)" />

	<NcPopover v-else v-model:shown="open">
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
				:ariaLabelCombobox="t('settings', 'Visibility')"
				:ariaLabelListbox="t('settings', 'Visibility')"
				:clearable="false"
				:options="visibilityOptions"
				:modelValue="visibilityOption"
				label="label"
				labelOutside
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
				:ariaLabelCombobox="t('settings', 'Scope')"
				:ariaLabelListbox="t('settings', 'Scope')"
				:clearable="false"
				:options="scopeOptions"
				:modelValue="scopeOption"
				label="displayName"
				labelOutside
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
