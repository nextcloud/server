<template>
	<Multiselect v-model="value" :options="options" track-by="id"
		label="title" :multiple="true" :tagging="true"
		@input="$emit('input', value.map(item => item.id))" />
</template>

<script>
// TODO: fetch tags from endpoint
import { Multiselect } from 'nextcloud-vue'

const tags = [{ id: 3, title: 'foo' }, { id: 4, title: 'bar' }]
export default {
	name: 'Tag',
	components: { Multiselect },
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
