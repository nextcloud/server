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
		<component :is="currentOption.component"
			v-if="currentOperator && currentComponent"
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
import NcActions from '@nextcloud/vue/dist/Components/NcActions.js'
import NcActionButton from '@nextcloud/vue/dist/Components/NcActionButton.js'
import NcSelect from '@nextcloud/vue/dist/Components/NcSelect.js'

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
		updateCheck() {
			const matchingOperator = this.operators.findIndex((operator) => this.check.operator === operator.operator)
			if (this.check.class !== this.currentOption.class || matchingOperator === -1) {
				this.currentOperator = this.operators[0]
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
		padding-right: 20px;

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
			margin-right: 5px;
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
