<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<section class="locale-section">
		<h3 class="locale-section__heading">{{ t('settings', 'Locale') }}</h3>

		<LocaleSectionEntry
			v-if="isEditable"
			:input-id="inputId"
			:locales-for-language="localesForLanguage"
			:other-locales="otherLocales"
			:locale.sync="locale" />

		<span v-else>
			{{ t('settings', 'No locale set') }}
		</span>
	</section>
</template>

<script>
import { loadState } from '@nextcloud/initial-state'
import LocaleSectionEntry from './LocaleSectionEntry.vue'
import { ACCOUNT_SETTING_PROPERTY_ENUM } from '../../../constants/AccountPropertyConstants.js'

const { localeMap: { activeLocale, localesForLanguage, otherLocales } } = loadState('settings', 'personalInfoParameters', {})

export default {
	name: 'LocaleSection',

	components: {
		LocaleSectionEntry,
	},

	data() {
		return {
			localesForLanguage,
			otherLocales,
			locale: activeLocale,
		}
	},

	computed: {
		inputId() {
			return `account-setting-${ACCOUNT_SETTING_PROPERTY_ENUM.LOCALE}`
		},

		isEditable() {
			return Boolean(this.locale)
		},
	},
}
</script>

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
