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
-->

<template>
	<form
		ref="form"
		class="section"
		@submit.stop.prevent="() => {}">
		<HeaderBar
			:account-property="accountProperty"
			label-for="language"
			:is-valid-form="isValidForm" />

		<template v-if="isEditable">
			<Language
				:common-languages="commonLanguages"
				:other-languages="otherLanguages"
				:language.sync="language"
				@update:language="onUpdateLanguage" />
		</template>

		<span v-else>
			{{ t('settings', 'No language set') }}
		</span>
	</form>
</template>

<script>
import { loadState } from '@nextcloud/initial-state'

import Language from './Language'
import HeaderBar from '../shared/HeaderBar'

import { SETTING_PROPERTY_READABLE_ENUM } from '../../../constants/AccountPropertyConstants'

const { languages: { activeLanguage, commonLanguages, otherLanguages } } = loadState('settings', 'personalInfoParameters', {})

export default {
	name: 'LanguageSection',

	components: {
		Language,
		HeaderBar,
	},

	data() {
		return {
			accountProperty: SETTING_PROPERTY_READABLE_ENUM.LANGUAGE,
			isValidForm: true,
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

	mounted() {
		this.$nextTick(() => this.updateFormValidity())
	},

	methods: {
		onUpdateLanguage() {
			this.$nextTick(() => this.updateFormValidity())
		},

		updateFormValidity() {
			this.isValidForm = this.$refs.form?.checkValidity()
		},
	},
}
</script>

<style lang="scss" scoped>
form::v-deep button {
	&:disabled {
		cursor: default;
	}
}
</style>
