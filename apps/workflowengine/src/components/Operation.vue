<template>
	<div class="actions__item" :class="{'colored': colored}" :style="{ backgroundColor: colored ? operation.color : 'transparent' }">
		<div class="icon" :class="operation.iconClass" :style="{ backgroundImage: operation.iconClass ? '' : `url(${operation.icon})` }" />
		<div class="actions__item__description">
			<h3>{{ operation.name }}</h3>
			<small>{{ operation.description }}</small>
			<Button v-if="colored">
				{{ t('workflowengine', 'Add new flow') }}
			</Button>
		</div>
		<div class="actions__item_options">
			<slot />
		</div>
	</div>
</template>

<script>
import Button from '@nextcloud/vue/dist/Components/Button'

export default {
	name: 'Operation',
	components: {
		Button,
	},
	props: {
		operation: {
			type: Object,
			required: true,
		},
		colored: {
			type: Boolean,
			default: true,
		},
	},
}
</script>

<style scoped lang="scss">
	@import "./../styles/operation";
</style>
