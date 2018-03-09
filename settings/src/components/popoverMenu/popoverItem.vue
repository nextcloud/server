<template>
	<li>
		<a @click="dispatchToStore" v-if="item.href" :href="(item.href) ? item.href : '#' ">
			<span :class="item.icon"></span>
			<span>{{item.text}}</span>
		</a>
		<button @click="dispatchToStore(item.action)" v-else>
			<span :class="item.icon"></span>
			<span>{{item.text}}</span>
		</button>
	</li>
</template>

<script>
export default {
	props: ['item'],
	methods: {
		dispatchToStore () {
			this.$store.dispatch(this.item.action, this.item.data);
		}
	}
}
</script>
