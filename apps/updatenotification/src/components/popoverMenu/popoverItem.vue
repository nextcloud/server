<template>
	<li>
		<!-- If item.href is set, a link will be directly used -->
		<a @click="item.action" v-if="item.href" :href="(item.href) ? item.href : '#' " :target="(item.target) ? item.target : '' " rel="noreferrer noopener">
			<span :class="item.icon"></span>
			<span v-if="item.text">{{item.text}}</span>
			<p v-else-if="item.longtext">{{item.longtext}}</p>
		</a>
		<!-- If item.action is set instead, a button will be used -->
		<button @click="item.action" v-else-if="item.action">
			<span :class="item.icon"></span>
			<span v-if="item.text">{{item.text}}</span>
			<p v-else-if="item.longtext">{{item.longtext}}</p>
		</button>
		<!-- If item.longtext is set AND the item does not have an action -->
		<span class="menuitem" v-else>
			<span :class="item.icon"></span>
			<span v-if="item.text">{{item.text}}</span>
			<p v-else-if="item.longtext">{{item.longtext}}</p>
		</span>
	</li>
</template>

<script>
export default {
	props: ['item']
}
</script>
