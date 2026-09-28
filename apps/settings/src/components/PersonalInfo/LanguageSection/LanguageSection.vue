<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<section>
		<HeaderBar :input-id="inputId"
			:readable="propertyReadable" />

		<Language v-if="isEditable"
			:input-id="inputId"
			:common-languages="commonLanguages"
			:other-languages="otherLanguages"
			:language.sync="language" />

		<span v-else>
			{{ t('settings', 'No language set') }}
		</span>
	</section>
</template>

<script>
import { loadState } from '@nextcloud/initial-state'

import Language from './Language.vue'
import HeaderBar from '../shared/HeaderBar.vue'

import { ACCOUNT_SETTING_PROPERTY_ENUM, ACCOUNT_SETTING_PROPERTY_READABLE_ENUM } from '../../../constants/AccountPropertyConstants.js'

const { languageMap: { activeLanguage, commonLanguages, otherLanguages } } = loadState('settings', 'personalInfoParameters', {})

export default {
	name: 'LanguageSection',

	components: {
		Language,
		HeaderBar,
	},

	setup() {
		// Non reactive instance properties
		return {
			commonLanguages,
			otherLanguages,
			propertyReadable: ACCOUNT_SETTING_PROPERTY_READABLE_ENUM.LANGUAGE,
		}
	},

	data() {
		return {
			language: activeLanguage,
		}
	},

	computed: {
		inputId() {
			return `account-setting-${ACCOUNT_SETTING_PROPERTY_ENUM.LANGUAGE}`
		},

		isEditable() {
			return Boolean(this.language)
		},
	},
}
</script>

<style lang="scss" scoped>
section {
	padding: 10px 10px;
}
</style>
