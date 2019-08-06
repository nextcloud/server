<template>
	<input type="text" placeholder="1 MB" @input="$emit('input', newValue)" v-model="newValue">
</template>

<script>
	export default {
		name: "SizeValue",
		props: {
			value: {
				type: String
			}
		},
		data() {
			return {
				newValue: this.value
			}
		},
		watch: {
			value() {
				this.newValue = this.value
			}
		}
	}
</script>

<style scoped>

</style>