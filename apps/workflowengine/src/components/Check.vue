<!--
  - SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div v-click-outside="hideDelete" class="check" @click="showDelete">
		<NcSelect ref="checkSelector"
			v-model="currentOption"
			:options="options"
			label="name"
			:clearable="false"
			:placeholder="t('workflowengine', 'Select a filter')"
			@input="updateCheck" />
		<NcSelect v-model="currentOperator"
			:disabled="!currentOption"
			:options="operators"
			class="comparator"
			label="name"
			:clearable="false"
			:placeholder="t('workflowengine', 'Select a comparator')"
			@input="updateCheck" />
		<component :is="currentElement"
			v-if="currentElement"
			ref="checkComponent"
			:disabled="!currentOption"
			:operator="check.operator"
			:model-value="check.value"
			class="option"
			@update:model-value="updateCheck"
			@valid="(valid=true) && validate()"
			@invalid="!(valid=false) && validate()" />
		<component :is="currentOption.component"
			v-else-if="currentOperator && currentComponent"
			v-model="check.value"
			:disabled="!currentOption"
			:check="check"
			class="option"
			@input="updateCheck"
			@valid="(valid=true) && validate()"
			@invalid="!(valid=false) && validate()" />
		<input v-else
			v-model="check.value"
			type="text"
			:class="{ invalid: !valid }"
			:disabled="!currentOption"
			:placeholder="valuePlaceholder"
			class="option"
			@input="updateCheck">
		<NcActions v-if="deleteVisible || !currentOption">
			<NcActionButton :title="t('workflowengine', 'Remove filter')" @click="$emit('remove')">
				<template #icon>
					<CloseIcon :size="20" />
				</template>
			</NcActionButton>
		</NcActions>
	</div>
</template>

<script>
import NcActions from '@nextcloud/vue/components/NcActions'
import NcActionButton from '@nextcloud/vue/components/NcActionButton'
import NcSelect from '@nextcloud/vue/components/NcSelect'

import CloseIcon from 'vue-material-design-icons/Close.vue'
import ClickOutside from 'vue-click-outside'

export default {
	name: 'Check',
	components: {
		NcActionButton,
		NcActions,
		NcSelect,

		// Icons
		CloseIcon,
	},
	directives: {
		ClickOutside,
	},
	props: {
		check: {
			type: Object,
			required: true,
		},
		rule: {
			type: Object,
			required: true,
		},
	},
	data() {
		return {
			deleteVisible: false,
			currentOption: null,
			currentOperator: null,
			options: [],
			valid: false,
		}
	},
	computed: {
		checks() {
			return this.$store.getters.getChecksForEntity(this.rule.entity)
		},
		operators() {
			if (!this.currentOption) { return [] }
			const operators = this.checks[this.currentOption.class].operators
			if (typeof operators === 'function') {
				return operators(this.check)
			}
			return operators
		},
		currentElement() {
			if (!this.check.class) {
				return false
			}
			return this.checks[this.check.class].element
		},
		currentComponent() {
			if (!this.currentOption) { return [] }
			return this.checks[this.currentOption.class].component
		},
		valuePlaceholder() {
			if (this.currentOption && this.currentOption.placeholder) {
				return this.currentOption.placeholder(this.check)
			}
			return ''
		},
	},
	watch: {
		'check.operator'() {
			this.validate()
		},
	},
	mounted() {
		this.options = Object.values(this.checks)
		this.currentOption = this.checks[this.check.class]
		this.currentOperator = this.operators.find((operator) => operator.operator === this.check.operator)

		if (this.currentElement) {
			// If we do not set it, the check`s value would remain empty. Unsure why Vue behaves this way.
			this.$refs.checkComponent.modelValue = undefined
		} else if (this.currentOption?.component) {
			// keeping this in an else for apps that try to be backwards compatible and may ship both
			// to be removed in 03/2028
			console.warn('Developer warning: `CheckPlugin.options` is deprecated. Use `CheckPlugin.element` instead.')
		}

		if (this.check.class === null) {
			this.$nextTick(() => this.$refs.checkSelector.$el.focus())
		}
		this.validate()
	},
	methods: {
		showDelete() {
			this.deleteVisible = true
		},
		hideDelete() {
			this.deleteVisible = false
		},
		validate() {
			this.valid = true
			if (this.currentOption && this.currentOption.validate) {
				this.valid = !!this.currentOption.validate(this.check)
			}
			// eslint-disable-next-line vue/no-mutating-props
			this.check.invalid = !this.valid
			this.$emit('validate', this.valid)
		},
		updateCheck(event) {
			const selectedOperator = event?.operator || this.currentOperator?.operator || this.check.operator
			const matchingOperator = this.operators.findIndex((operator) => selectedOperator === operator.operator)
			if (this.check.class !== this.currentOption.class || matchingOperator === -1) {
				this.currentOperator = this.operators[0]
			}
			if (event?.detail) {
				this.check.value = event.detail[0]
			}
			// eslint-disable-next-line vue/no-mutating-props
			this.check.class = this.currentOption.class
			// eslint-disable-next-line vue/no-mutating-props
			this.check.operator = this.currentOperator.operator

			this.validate()

			this.$emit('update', this.check)
		},
	},
}
</script>

<style scoped lang="scss">
	.check {
		display: flex;
		flex-wrap: wrap;
		align-items: flex-start; // to not stretch components vertically
		width: 100%;
		padding-inline-end: 20px;

		& > *:not(.close) {
			width: 180px;
		}
		& > .comparator {
			min-width: 200px;
			width: 200px;
		}
		& > .option {
			min-width: 260px;
			width: 260px;
			min-height: 48px;

			& > input[type=text] {
				min-height: 48px;
			}
		}
		& > .v-select,
		& > .button-vue,
		& > input[type=text] {
			margin-inline-end: 5px;
			margin-bottom: 5px;
		}
	}

	input[type=text] {
		margin: 0;
	}

	.invalid {
		border-color: var(--color-error) !important;
	}
</style>
