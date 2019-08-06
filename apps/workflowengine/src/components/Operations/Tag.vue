<template>
	<multiselect :options="options" v-model="value" track-by="id" label="title" :multiple="true" :tagging="true" @input="$emit('input', value.map(item => item.id))"></multiselect>
</template>

<script>
	// TODO: fetch tags from endpoint
	const tags = [{id: 3, title: 'foo'}, {id: 4, title: 'bar'}]

	import { Multiselect } from 'nextcloud-vue'
	export default {
		name: "Tag",
		components: {Multiselect},
		data() {
			return {
				options: tags,
				value: null
			}
		}
	}
</script>

<style scoped>
	.multiselect {
		width: 100%;
		max-width: 300px;
		margin: auto;
		text-align: center;
	}
</style>