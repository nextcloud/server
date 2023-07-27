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

		<template>
			<NcDateTimePickerNative :id="inputId"
				type="date"
				:label="t('settings', 'Your birthday')"
				:value="birthdayValue"
				@input="onInput" />
		</template>
	</section>
</template>

<script>
import { loadState } from '@nextcloud/initial-state'

import HeaderBar from './shared/HeaderBar.vue'
import AccountPropertySection from './shared/AccountPropertySection.vue'

import {
	NAME_READABLE_ENUM,
	ACCOUNT_SETTING_PROPERTY_READABLE_ENUM,
	ACCOUNT_SETTING_PROPERTY_ENUM,
} from '../../constants/AccountPropertyConstants.js'

import { NcDateTimePickerNative } from '@nextcloud/vue'
import debounce from 'debounce'
import { savePrimaryAccountProperty } from '../../service/PersonalInfo/PersonalInfoService'
import { handleError } from '../../utils/handlers'

const { birthday } = loadState('settings', 'personalInfoParameters', {})

export default {
	name: 'BirthdaySection',

	components: {
		AccountPropertySection,
		NcDateTimePickerNative,
		HeaderBar,
	},

	data() {
		return {
			propertyReadable: 'Birthday', // ACCOUNT_SETTING_PROPERTY_READABLE_ENUM.BIRTHDAY,
			readable: 'Birthday',
			birthday: { ...birthday, readable: NAME_READABLE_ENUM[birthday.name] },
			birthdayValue: new Date(),
			name: 'birthday',
		}
	},

	computed: {
		inputId() {
			return `account-setting-${ACCOUNT_SETTING_PROPERTY_ENUM.BIRTHDAY}`
		},
	},

	mounted() {
		console.log('birthday', this, ACCOUNT_SETTING_PROPERTY_READABLE_ENUM)
	},

	methods: {
		onInput(e) {
			console.log('onInput', e)
			this.birtdayValue = e
			this.debouncePropertyChange(this.birthdayValue.toString())
		},

		debouncePropertyChange: debounce(async function(value) {
			this.helperText = null
			if (this.$refs.input && this.$refs.input.validationMessage) {
				this.helperText = this.$refs.input.validationMessage
				return
			}
			if (this.onValidate && !this.onValidate(value)) {
				return
			}
			await this.updateProperty(value)
		}, 500),

		async updateProperty(value) {
			try {
				const responseData = await savePrimaryAccountProperty(
					this.name,
					value,
				)
				this.handleResponse({
					value,
					status: responseData.ocs?.meta?.status,
				})
			} catch (e) {
				this.handleResponse({
					errorMessage: t('settings', 'Unable to update {property}', { property: this.readable.toLocaleLowerCase() }),
					error: e,
				})
			}
		},

		handleResponse({ value, status, errorMessage, error }) {
			if (status === 'ok') {
				this.initialValue = value
				if (this.onSave) {
					this.onSave(value)
				}
				this.showCheckmarkIcon = true
				setTimeout(() => { this.showCheckmarkIcon = false }, 2000)
			} else {
				this.$emit('update:value', this.initialValue)
				handleError(error, errorMessage)
				this.showErrorIcon = true
				setTimeout(() => { this.showErrorIcon = false }, 2000)
			}
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
