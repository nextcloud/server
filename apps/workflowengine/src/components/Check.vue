<template>
	<div v-click-outside="hideDelete" class="check" @click="showDelete">
		<Multiselect ref="checkSelector" v-model="currentOption" :options="options"
			label="name" track-by="class" :allow-empty="false"
			:placeholder="t('workflowengine', 'Select a filter')" @input="updateCheck" />
		<Multiselect :disabled="!currentOption" v-model="currentOperator" :options="operators"
			label="name" track-by="operator" :allow-empty="false"
			:placeholder="t('workflowengine', 'Select a comparator')" @input="updateCheck" />
		<component :is="currentOption.component" v-if="currentOperator && currentComponent" v-model="check.value" :disabled="!currentOption" :check="check" @valid="valid=true && validate()" @invalid="valid=false && validate()" />
		<input v-else v-model="check.value" type="text" :class="{ invalid: !valid }"
			@input="updateCheck" :disabled="!currentOption" :placeholder="valuePlaceholder">
		<Actions>
			<ActionButton v-if="deleteVisible || !currentOption" icon="icon-delete" @click="$emit('remove')" />
		</Actions>
	</div>
</template>

<script>
import { Multiselect, Actions, ActionButton } from 'nextcloud-vue'
import ClickOutside from 'vue-click-outside'
import { mapState } from 'vuex'

export default {
	name: 'Check',
	components: {
		ActionButton,
		Actions,
		Multiselect
	},
	directives: {
		ClickOutside
	},
	props: {
		check: {
			type: Object,
			required: true
		},
		rule: {
			type: Object,
			required: true
		}
	},
	data() {
		return {
			deleteVisible: false,
			currentOption: null,
			currentOperator: null,
			options: [],
			valid: true,
		}
	},
	computed: {
		...mapState({
			Checks: (state) => state.plugins.checks
		}),
		operators() {
			if (!this.currentOption) { return [] }
			return this.Checks[this.currentOption.class].operators
		},
		currentComponent() {
			if (!this.currentOption) { return [] }
			const currentComponent = this.Checks[this.currentOption.class].component
			return currentComponent
		},
		valuePlaceholder() {
			if (this.currentOption && this.currentOption.placeholder) {
				return this.currentOption.placeholder(this.check)
			}
		}
	},
	mounted() {
		this.options = Object.values(this.Checks)
		this.currentOption = this.Checks[this.check.class]
		this.currentOperator = this.operators.find((operator) => operator.operator === this.check.operator)
	},
	watch: {
		'check.operator': function () {
			this.validate()
		}
	},
	methods: {
		showDelete() {
			this.deleteVisible = true
		},
		hideDelete() {
			this.deleteVisible = false
		},
		validate() {
			if (this.currentOption && this.currentOption.validate) {
				if(this.currentOption.validate(this.check)) {
					this.valid = true
				} else {
					this.valid = false
				}
			}
			this.$store.dispatch('setValid', { rule: this.rule, valid: this.rule.valid && this.valid })
			return this.valid
		},
		updateCheck() {
			if (this.check.class !== this.currentOption.class) {
				this.currentOperator = this.operators[0]
			}
			this.check.class = this.currentOption.class
			this.check.operator = this.currentOperator.operator

			if (!this.validate()) {
				return
			}
			this.$emit('update', this.check)
		}
	}
}
</script>

<style scoped lang="scss">
	.check {
		display: flex;
		flex-wrap: wrap;
		width: 100%;
		padding-right: 20px;
		& > *:not(.icon-delete) {
			width: 180px;
		}
		& > .multiselect,
		& > input[type=text] {
			margin-right: 5px;
			margin-bottom: 5px;
		}
	}
	input[type=text] {
		margin: 0;
	}
	::placeholder {
		font-size: 10px;
	}
	.icon-delete {
		margin-top: -5px;
		margin-bottom: -5px;
	}
	.invalid {
		border: 1px solid var(--color-error) !important;
	}
</style>
