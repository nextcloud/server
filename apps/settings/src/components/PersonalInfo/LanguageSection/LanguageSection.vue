<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<section>
		<HeaderBar :input-id="inputId"
			:readable="propertyReadable" />

		<Language v-if="isEditable"
			data-test="language-select"
			:input-id="inputId"
			:common-languages="commonLanguages"
			:other-languages="otherLanguages"
			:language.sync="language" />

		<span v-else-if="forcedLanguage && forcedLanguage.name"
			data-test="forced-language-message">
			{{ t('settings', 'Language is forced to {language} by the administrator', { language: forcedLanguage.name }) }}
		</span>
		<span v-else
			data-test="no-language-message">
			{{ t('settings', 'No language set') }}
		</span>
	</section>
</template>

<script>
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'

import Language from './Language.vue'
import HeaderBar from '../shared/HeaderBar.vue'

import { ACCOUNT_SETTING_PROPERTY_ENUM, ACCOUNT_SETTING_PROPERTY_READABLE_ENUM } from '../../../constants/AccountPropertyConstants.js'

export default {
	name: 'LanguageSection',

	components: {
		Language,
		HeaderBar,
	},

	setup() {
		// Non reactive instance properties
		return {
			propertyReadable: ACCOUNT_SETTING_PROPERTY_READABLE_ENUM.LANGUAGE,
		}
	},

	data() {
		const state = loadState('settings', 'personalInfoParameters', {})
		const { activeLanguage, commonLanguages, otherLanguages, forcedLanguage } = state.languageMap || {}
		return {
			language: activeLanguage || null,
			forcedLanguage: forcedLanguage && forcedLanguage.name
				? {
					code: forcedLanguage.code,
					name: forcedLanguage.name,
				}
				: null,
			commonLanguages: forcedLanguage ? [] : (commonLanguages || []),
			otherLanguages: forcedLanguage ? [] : (otherLanguages || []),
		}
	},

	computed: {
		inputId() {
			return `account-setting-${ACCOUNT_SETTING_PROPERTY_ENUM.LANGUAGE}`
		},

		isEditable() {
			// Return false if language is forced or there's no active language
			return !this.forcedLanguage && this.language !== null
		},
	},

	methods: {
		t,
	},
}
</script>

<style lang="scss" scoped>
section {
	padding: 10px 10px;
}
</style>
