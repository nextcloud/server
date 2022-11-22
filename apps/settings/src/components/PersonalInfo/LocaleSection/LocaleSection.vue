<!--
	- @copyright 2022 Christopher Ng <chrng8@gmail.com>
	-
	- @author Christopher Ng <chrng8@gmail.com>
	-
	- @license AGPL-3.0-or-later
	-
	- This program is free software: you can redistribute it and/or modify
	- it under the terms of the GNU Affero General Public License as
	- published by the Free Software Foundation, either version 3 of the
	- License, or (at your option) any later version.
	-
	- This program is distributed in the hope that it will be useful,
	- but WITHOUT ANY WARRANTY; without even the implied warranty of
	- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
	- GNU Affero General Public License for more details.
	-
	- You should have received a copy of the GNU Affero General Public License
	- along with this program. If not, see <http://www.gnu.org/licenses/>.
	-
-->

<template>
	<section>
		<HeaderBar :input-id="inputId"
			:readable="propertyReadable" />

		<template v-if="isEditable">
			<Locale :input-id="inputId"
				:locales-for-language="localesForLanguage"
				:other-locales="otherLocales"
				:locale.sync="locale" />
		</template>

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

	&::v-deep button:disabled {
		cursor: default;
	}
}
</style>
