<!--
  - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<section>
		<HeaderBar :input-id="inputId"
			:readable="propertyReadable" />

		<Locale v-if="isEditable"
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

import Locale from './Locale.vue'
import HeaderBar from '../shared/HeaderBar.vue'

import { ACCOUNT_SETTING_PROPERTY_ENUM, ACCOUNT_SETTING_PROPERTY_READABLE_ENUM } from '../../../constants/AccountPropertyConstants.js'

const { localeMap: { activeLocale, localesForLanguage, otherLocales } } = loadState('settings', 'personalInfoParameters', {})

export default {
	name: 'LocaleSection',

	components: {
		Locale,
		HeaderBar,
	},

	data() {
		return {
			propertyReadable: ACCOUNT_SETTING_PROPERTY_READABLE_ENUM.LOCALE,
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
section {
	padding: 10px 10px;
}
</style>
