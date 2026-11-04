<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { LanguageOption } from '../../../components/Users/userFormUtils.ts'

import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import LocaleSectionEntry from './LocaleSectionEntry.vue'
import { ACCOUNT_SETTING_PROPERTY_ENUM } from '../../../constants/AccountPropertyConstants.ts'

const { localeMap: { activeLocale, localesForLanguage, otherLocales } } = loadState<{
	localeMap: { activeLocale: LanguageOption, localesForLanguage: LanguageOption[], otherLocales: LanguageOption[] }
}>('settings', 'personalInfoParameters')

const inputId = `account-setting-${ACCOUNT_SETTING_PROPERTY_ENUM.LOCALE}`

const isEditable = Boolean(activeLocale)
</script>

<template>
	<section class="locale-section">
		<h3 class="locale-section__heading">
			<label :for="inputId">{{ t('settings', 'Locale') }}</label>
		</h3>

		<LocaleSectionEntry
			v-if="isEditable"
			:locale="activeLocale"
			:inputId="inputId"
			:localesForLanguage="localesForLanguage"
			:otherLocales="otherLocales" />

		<span v-else>
			{{ t('settings', 'No locale set') }}
		</span>
	</section>
</template>

<style lang="scss" scoped>
.locale-section {
	padding: 6px 0;

	&__heading {
		margin: 0 0 2px;
		font-size: 16px;
		font-weight: bold;
	}
}
</style>
