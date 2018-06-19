<template>
	<ul>
		<popover-item v-for="(item, key) in menu" :item="item" :key="key" />
	</ul>
</template>


<script>
import popoverItem from './popoverMenu/popoverItem';

export default {
	name: 'popoverMenu',
	props: ['menu'],
	components: {
		popoverItem
	}
}
</script>
