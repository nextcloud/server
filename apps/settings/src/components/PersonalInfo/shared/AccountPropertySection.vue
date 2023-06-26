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
		<HeaderBar :scope.sync="scope"
			:readable.sync="readable"
			:input-id="inputId"
			:is-editable="isEditable" />

		<div v-if="isEditable" class="property">
			<textarea v-if="multiLine"
				:id="inputId"
				:placeholder="placeholder"
				:value="value"
				rows="8"
				autocapitalize="none"
				autocomplete="off"
				autocorrect="off"
				@input="onPropertyChange" />
			<input v-else
				ref="input"
				:id="inputId"
				:placeholder="placeholder"
				:type="type"
				:value="value"
				:aria-describedby="helperText ? `${name}-helper-text` : ''"
				autocapitalize="none"
				autocorrect="off"
				:autocomplete="autocomplete"
				@input="onPropertyChange">

			<div class="property__actions-container">
				<transition name="fade">
					<Check v-if="showCheckmarkIcon" :size="20" />
					<AlertOctagon v-else-if="showErrorIcon" :size="20" />
				</transition>
			</div>
		</div>
		<span v-else>
			{{ value || t('settings', 'No {property} set', { property: readable.toLocaleLowerCase() }) }}
		</span>

		<p v-if="helperText"
			:id="`${name}-helper-text`"
			class="property__helper-text-message property__helper-text-message--error">
			<AlertCircle class="property__helper-text-message__icon" :size="18" />
			{{ helperText }}
		</p>
	</section>
</template>

<script>
import debounce from 'debounce'

import AlertCircle from 'vue-material-design-icons/AlertCircleOutline.vue'
import AlertOctagon from 'vue-material-design-icons/AlertOctagon.vue'
import Check from 'vue-material-design-icons/Check.vue'

import HeaderBar from '../shared/HeaderBar.vue'

import { savePrimaryAccountProperty } from '../../../service/PersonalInfo/PersonalInfoService.js'
import { handleError } from '../../../utils/handlers.js'

export default {
	name: 'AccountPropertySection',

	components: {
		AlertCircle,
		AlertOctagon,
		Check,
		HeaderBar,
	},

	props: {
		name: {
			type: String,
			required: true,
		},
		value: {
			type: String,
			required: true,
		},
		scope: {
			type: String,
			required: true,
		},
		readable: {
			type: String,
			required: true,
		},
		placeholder: {
			type: String,
			required: true,
		},
		type: {
			type: String,
			default: 'text',
		},
		isEditable: {
			type: Boolean,
			default: true,
		},
		multiLine: {
			type: Boolean,
			default: false,
		},
		onValidate: {
			type: Function,
			default: null,
		},
		onSave: {
			type: Function,
			default: null,
		},
		autocomplete: {
			type: String,
			default: null,
		},
	},

	data() {
		return {
			initialValue: this.value,
			helperText: null,
			showCheckmarkIcon: false,
			showErrorIcon: false,
		}
	},

	computed: {
		inputId() {
			return `account-property-${this.name}`
		},
	},

	methods: {
		onPropertyChange(e) {
			this.$emit('update:value', e.target.value)
			this.debouncePropertyChange(e.target.value.trim())
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

	.property {
		display: grid;
		align-items: center;

		textarea {
			resize: vertical;
			grid-area: 1 / 1;
			width: 100%;
		}

		input {
			grid-area: 1 / 1;
			width: 100%;
		}

		.property__actions-container {
			grid-area: 1 / 1;
			justify-self: flex-end;
			align-self: flex-end;
			height: 30px;

			display: flex;
			gap: 0 2px;
			margin-right: 5px;
			margin-bottom: 5px;
		}
	}

	.property__helper-text-message {
		padding: 4px 0;
		display: flex;
		align-items: center;

		&__icon {
			margin-right: 8px;
			align-self: start;
			margin-top: 4px;
		}

		&--error {
			color: var(--color-error);
		}
	}

	.fade-enter,
	.fade-leave-to {
		opacity: 0;
	}

	.fade-enter-active {
		transition: opacity 200ms ease-out;
	}

	.fade-leave-active {
		transition: opacity 300ms ease-out;
	}
}
</style>
