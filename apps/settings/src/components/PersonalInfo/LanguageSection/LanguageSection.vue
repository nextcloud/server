<!--
  - SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<section class="language-section">
		<h3 class="language-section__heading">{{ t('settings', 'Language') }}</h3>

		<LanguageSectionEntry
			v-if="isEditable"
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
import LanguageSectionEntry from './LanguageSectionEntry.vue'
import { ACCOUNT_SETTING_PROPERTY_ENUM } from '../../../constants/AccountPropertyConstants.js'

const { languageMap: { activeLanguage, commonLanguages, otherLanguages } } = loadState('settings', 'personalInfoParameters', {})

export default {
	name: 'LanguageSection',

	components: {
		LanguageSectionEntry,
	},

	setup() {
		// Non reactive instance properties
		return {
			commonLanguages,
			otherLanguages,
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
.language-section {
	padding: 6px 0;

	&__heading {
		margin: 0 0 6px;
		font-size: 16px;
		font-weight: bold;
	}
}
</style>
