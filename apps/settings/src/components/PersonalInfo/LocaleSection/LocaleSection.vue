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

		<span v-else-if="forcedLocale && forcedLocale.name">
			{{ t('settings', 'Locale is forced to {locale} by the administrator', { locale: forcedLocale.name }) }}
		</span>
		<span v-else>
			{{ t('settings', 'No locale set') }}
		</span>
	</section>
</template>

<script>
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'

import Locale from './Locale.vue'
import HeaderBar from '../shared/HeaderBar.vue'

import { ACCOUNT_SETTING_PROPERTY_ENUM, ACCOUNT_SETTING_PROPERTY_READABLE_ENUM } from '../../../constants/AccountPropertyConstants.js'

export default {
	name: 'LocaleSection',

	components: {
		Locale,
		HeaderBar,
	},

	setup() {
		// Non reactive instance properties
		return {
			propertyReadable: ACCOUNT_SETTING_PROPERTY_READABLE_ENUM.LOCALE,
		}
	},

	data() {
		const state = loadState('settings', 'personalInfoParameters', {})
		const { activeLocale, localesForLanguage, otherLocales, forcedLocale } = state.localeMap || {}
		return {
			locale: activeLocale || null,
			forcedLocale: forcedLocale && forcedLocale.name
				? {
					code: forcedLocale.code,
					name: forcedLocale.name,
				}
				: null,
			localesForLanguage: forcedLocale ? [] : (localesForLanguage || []),
			otherLocales: forcedLocale ? [] : (otherLocales || []),
		}
	},

	computed: {
		inputId() {
			return `account-setting-${ACCOUNT_SETTING_PROPERTY_ENUM.LOCALE}`
		},

		isEditable() {
			// Return false if there's no locale data or if locale is forced
			return !this.forcedLocale && (this.locale !== null || this.localesForLanguage.length > 0)
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
