<template>
	<router-view></router-view>
</template>

<script>
export default {
	name: 'App',
	beforeMount: function () {
		// importing server data into the store
		const serverDataElmt = document.getElementById('serverData');
		if (serverDataElmt !== null) {
			this.$store.commit('setServerData', JSON.parse(document.getElementById('serverData').dataset.server));
		}
	}
}
</script>
