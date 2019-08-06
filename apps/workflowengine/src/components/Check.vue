<template>
	<div class="check" @click="showDelete" v-click-outside="hideDelete">
		<Multiselect v-model="currentOption" :options="options" label="name"
					 track-by="class" :allow-empty="false" :placeholder="t('workflowengine', 'Select a filter')"
					 @input="updateCheck" ref="checkSelector"></Multiselect>
		<Multiselect v-if="currentOption" v-model="currentOperator" @input="updateCheck"
					 :options="operators" label="name" track-by="operator"
					 :allow-empty="false" :placeholder="t('workflowengine', 'Select a comparator')"></Multiselect>
		<component v-if="currentOperator && currentComponent" :is="currentOption.component()" v-model="check.value" />
		<input v-else-if="currentOperator" type="text" v-model="check.value" @input="updateCheck" />
		<Actions>
			<ActionButton icon="icon-delete" v-if="deleteVisible || !currentOption" @click="$emit('remove')" />
		</Actions>
	</div>
</template>

<script>
	import { Multiselect, Actions, ActionButton } from 'nextcloud-vue'
	import ClickOutside from 'vue-click-outside';

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
				options: [],
			}
		},
		mounted() {
			this.options = Object.values(OCA.WorkflowEngine.Plugins).map((plugin) => {
				if (plugin.component) {
					return {...plugin.getCheck(), component: plugin.component}
				}
				return plugin.getCheck()
			})
			this.currentOption = this.options.find((option) => option.class === this.check.class)
			this.currentOperator = this.operators.find((operator) => operator.operator === this.check.operator)
			this.$nextTick(() => {
				this.$refs.checkSelector.$el.focus()
			})
		},
		computed: {
			operators() {
				if (!this.currentOption)
					return []
				return this.options.find((item) => item.class === this.currentOption.class).operators
			},
			currentComponent() {
				if (!this.currentOption)
					return []
				let currentComponent = this.options.find((item) => item.class === this.currentOption.class).component
				return currentComponent && currentComponent()
			}
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