<!--
	- @copyright 2021, Christopher Ng <chrng8@gmail.com>
	-
	- @author Christopher Ng <chrng8@gmail.com>
	-
	- @license GNU AGPL version 3 or any later version
	-
	- This program is free software: you can redistribute it and/or modify
	- it under the terms of the GNU Affero General Public License as
	- published by the Free Software Foundation, either version 3 of the
	- License, or (at your option) any later version.
	-
	- This program is distributed in the hope that it will be useful,
	- but WITHOUT ANY WARRANTY; without even the implied warranty of
	- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	- GNU Affero General Public License for more details.
	-
	- You should have received a copy of the GNU Affero General Public License
	- along with this program. If not, see <http://www.gnu.org/licenses/>.
	-
-->

<template>
	<section>
		<HeaderBar :account-property="accountProperty"
			label-for="language" />

		<template v-if="isEditable">
			<Language :common-languages="commonLanguages"
				:other-languages="otherLanguages"
				:language.sync="language" />
		</template>

		<span v-else>
			{{ t('settings', 'No language set') }}
		</span>
	</section>
</template>

<script>
import { loadState } from '@nextcloud/initial-state'

import Language from './Language'
import HeaderBar from '../shared/HeaderBar'

import { ACCOUNT_SETTING_PROPERTY_READABLE_ENUM } from '../../../constants/AccountPropertyConstants'

const { languageMap: { activeLanguage, commonLanguages, otherLanguages } } = loadState('settings', 'personalInfoParameters', {})

export default {
	name: 'LanguageSection',

	components: {
		Language,
		HeaderBar,
	},

	data() {
		return {
			accountProperty: ACCOUNT_SETTING_PROPERTY_READABLE_ENUM.LANGUAGE,
			commonLanguages,
			otherLanguages,
			language: activeLanguage,
		}
	},

	computed: {
		isEditable() {
			return Boolean(this.language)
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
