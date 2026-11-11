<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { LanguageOption } from '../../../components/Users/userFormUtils.ts'

import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { computed, ref } from 'vue'
import LanguageSectionEntry from './LanguageSectionEntry.vue'
import { ACCOUNT_SETTING_PROPERTY_ENUM } from '../../../constants/AccountPropertyConstants.ts'

const { languageMap: { activeLanguage, commonLanguages, otherLanguages } } = loadState<{
	languageMap: { activeLanguage: LanguageOption, commonLanguages: LanguageOption[], otherLanguages: LanguageOption[] }
}>('settings', 'personalInfoParameters')

const inputId = `account-setting-${ACCOUNT_SETTING_PROPERTY_ENUM.LANGUAGE}`

const language = ref(activeLanguage)
const isEditable = computed(() => Boolean(language.value))
</script>

<template>
	<section class="language-section">
		<h3 class="language-section__heading">
			<label :for="inputId">{{ t('settings', 'Language') }}</label>
		</h3>

		<LanguageSectionEntry
			v-if="isEditable"
			v-model:language="language"
			:inputId="inputId"
			:commonLanguages="commonLanguages"
			:otherLanguages="otherLanguages" />

		<span v-else>
			{{ t('settings', 'No language set') }}
		</span>
	</section>
</template>

<style lang="scss" scoped>
.language-section {
	padding: 6px 0;

	&__heading {
		margin: 0 0 6px;
		font-size: 16px;
		font-weight: bold;
	}
}
</style>
