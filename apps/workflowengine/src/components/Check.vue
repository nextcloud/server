<template>
	<div v-click-outside="hideDelete" class="check" @click="showDelete">
		<Multiselect ref="checkSelector" v-model="currentOption" :options="options"
			label="name" track-by="class" :allow-empty="false"
			:placeholder="t('workflowengine', 'Select a filter')" @input="updateCheck" />
		<Multiselect v-if="currentOption" v-model="currentOperator" :options="operators"
			label="name" track-by="operator" :allow-empty="false"
			:placeholder="t('workflowengine', 'Select a comparator')" @input="updateCheck" />
		<component :is="currentOption.component()" v-if="currentOperator && currentComponent" v-model="check.value" />
		<input v-else-if="currentOperator" v-model="check.value" type="text"
			@input="updateCheck">
		<Actions>
			<ActionButton v-if="deleteVisible || !currentOption" icon="icon-delete" @click="$emit('remove')" />
		</Actions>
	</div>
</template>

<script>
import { Checks } from '../services/Operation'
import { Multiselect, Actions, ActionButton } from 'nextcloud-vue'
import ClickOutside from 'vue-click-outside'

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
		}
	},
	data() {
		return {
			deleteVisible: false,
			currentOption: null,
			currentOperator: null,
			options: []
		}
	},
	computed: {
		operators() {
			if (!this.currentOption) { return [] }
			return Checks[this.currentOption.class].operators
		},
		currentComponent() {
			if (!this.currentOption) { return [] }
			const currentComponent = Checks[this.currentOption.class].component
			return currentComponent && currentComponent()
		}
	},
	mounted() {
		this.options = Object.values(Checks)
		this.currentOption = Checks[this.check.class]
		this.currentOperator = this.operators.find((operator) => operator.operator === this.check.operator)
	},
	methods: {
		showDelete() {
			this.deleteVisible = true
		},
		hideDelete() {
			this.deleteVisible = false
		},
		updateCheck() {
			if (this.check.class !== this.currentOption.class) {
				this.currentOperator = this.operators[0]
			}
			this.check.class = this.currentOption.class
			this.check.operator = this.currentOperator.operator
			this.$emit('update', this.check)
		}
	}
}
</script>

<style scoped lang="scss">
	.check {
		display: flex;
		& > .multiselect,
		& > input[type=text] {
			margin-right: 5px;
		}
	}
	input[type=text] {
		margin: 0;
	}
	::placeholder {
		font-size: 10px;
	}
</style>
