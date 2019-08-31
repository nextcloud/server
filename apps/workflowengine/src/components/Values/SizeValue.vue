<template>
	<input v-model="newValue" type="text" placeholder="1 MB"
		@input="update">
</template>

<script>
export default {
	name: 'SizeValue',
	props: {
		value: {
			type: String,
			default: '1 MB'
		}
	},
	data() {
		return {
			valid: false,
			newValue: this.value
		}
	},
	watch: {
		value() {
			this.newValue = this.value
		}
	},
	methods: {
		validate() {
			return this.newValue.match(/^[0-9]+[ ]?[kmgt]?b$/i) !== null
		},
		update() {
			if (this.validate()) {
				this.$emit('input', this.newValue)
				this.valid = false
			} else {
				this.valid = false
			}
		}
	}
}
</script>

<style scoped>

</style>
