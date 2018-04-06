<template>
	<li>
		<a @click="item.action" v-if="item.href" :href="(item.href) ? item.href : '#' ">
			<span :class="item.icon"></span>
			<span>{{item.text}}</span>
		</a>
		<button @click="item.action" v-else>
			<span :class="item.icon"></span>
			<span>{{item.text}}</span>
		</button>
	</li>
</template>

<script>
export default {
	props: ['item']
}
</script>
