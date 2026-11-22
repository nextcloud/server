<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { LanguageOption } from '../../../components/Users/userFormUtils.ts'

import { t } from '@nextcloud/l10n'
import { computed } from 'vue'
import NcFormBoxButton from '@nextcloud/vue/components/NcFormBoxButton'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import OpenInNew from 'vue-material-design-icons/OpenInNew.vue'
import { ACCOUNT_SETTING_PROPERTY_ENUM } from '../../../constants/AccountPropertyConstants.ts'
import { savePrimaryAccountProperty } from '../../../service/PersonalInfo/PersonalInfoService.js'
import { handleError } from '../../../utils/handlers.ts'
import { validateLanguage } from '../../../utils/validate.js'

/** The user's current language */
const language = defineModel<LanguageOption>('language', { required: true })

const props = defineProps<{
	/** Id of the select input, for the section's label */
	inputId?: string
	/** Most used languages, listed first */
	commonLanguages: LanguageOption[]
	/** All other languages */
	otherLanguages: LanguageOption[]
}>()

/**
 * All available languages, sorted like: current, common, other
 */
const allLanguages = computed(() => {
	const common = props.commonLanguages.filter((l) => l.code !== language.value.code)
	const other = props.otherLanguages.filter((l) => l.code !== language.value.code)
	return [language.value, ...common, ...other]
})

/**
 * @param newLanguage The language picked in the select, the page reloads in it
 */
async function onLanguageChange(newLanguage: LanguageOption) {
	language.value = newLanguage

	if (!validateLanguage(newLanguage)) {
		return
	}

	try {
		await savePrimaryAccountProperty(ACCOUNT_SETTING_PROPERTY_ENUM.LANGUAGE, newLanguage.code)
		window.location.reload()
	} catch (error) {
		handleError(error, t('settings', 'Unable to update language'))
	}
}
</script>

<template>
	<div class="language">
		<NcSelect
			:aria-label-listbox="t('settings', 'Languages')"
			class="language__select"
			:clearable="false"
			:inputId="inputId"
			label="name"
			labelOutside
			:options="allLanguages"
			:modelValue="language"
			@option:selected="onLanguageChange" />

		<NcFormBoxButton
			:label="t('settings', 'Help translate')"
			href="https://explore.transifex.com/nextcloud/"
			target="_blank">
			<template #icon>
				<OpenInNew :size="20" />
			</template>
		</NcFormBoxButton>
	</div>
</template>

<style lang="scss" scoped>
.language {
	display: flex;
	flex-direction: column;
	gap: 6px;
}
</style>
