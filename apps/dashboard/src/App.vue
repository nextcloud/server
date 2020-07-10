<template>
	<div id="app-dashboard">
		<h2>{{ greeting.icon }} {{ greeting.text }}</h2>

		<div class="panels">
			<div v-for="panel in panels" :key="panel.id" class="panel">
				<a :href="panel.url">
					<h3 :class="panel.iconClass">
						{{ panel.title }}
					</h3>
				</a>
				<div :ref="panel.id" :data-id="panel.id" />
			</div>
		</div>
	</div>
</template>

<script>
import Vue from 'vue'
import { loadState } from '@nextcloud/initial-state'
import { getCurrentUser } from '@nextcloud/auth'

const panels = loadState('dashboard', 'panels')

export default {
	name: 'App',
	data() {
		return {
			timer: new Date(),
			callbacks: {},
			panels,
			name: getCurrentUser()?.displayName,
		}
	},
	computed: {
		greeting() {
			const time = this.timer.getHours()

			if (time > 18) {
				return { icon: '🌙', text: t('dashboard', 'Good evening, {name}', { name: this.name }) }
			}
			if (time > 12) {
				return { icon: '☀', text: t('dashboard', 'Good afternoon, {name}', { name: this.name }) }
			}
			if (time === 12) {
				return { icon: '🍽', text: t('dashboard', 'Time for lunch, {name}', { name: this.name }) }
			}
			if (time > 5) {
				return { icon: '🌄', text: t('dashboard', 'Good morning, {name}', { name: this.name }) }
			}
			return { icon: '🦉', text: t('dashboard', 'Have a night owl, {name}', { name: this.name }) }
		},
	},
	watch: {
		callbacks() {
			for (const app in this.callbacks) {
				const element = this.$refs[app]
				if (this.panels[app].mounted) {
					continue
				}

				if (element) {
					this.callbacks[app](element[0])
					Vue.set(this.panels[app], 'mounted', true)
				} else {
					console.error('Failed to register panel in the frontend as no backend data was provided for ' + app)
				}
			}
		},
	},
	mounted() {
		setInterval(() => {
			this.timer = new Date()
		}, 30000)
	},
	methods: {
		register(app, callback) {
			Vue.set(this.callbacks, app, callback)
		},
	},
}
</script>

<style lang="scss" scoped>
	#app-dashboard {
		width: 100%;
	}
	h2 {
		text-align: center;
		font-size: 32px;
		line-height: 130%;
		padding: 80px 16px 32px;
	}

	.panels {
		width: 100%;
		display: flex;
		justify-content: center;
		flex-direction: row;
		align-items: flex-start;
		flex-wrap: wrap;
	}

	.panel {
		width: 250px;
		margin: 16px;

		& > a {
			position: sticky;
			top: 50px;
			display: block;
			background: linear-gradient(var(--color-main-background-translucent), var(--color-main-background-translucent) 80%, rgba(255, 255, 255, 0));
			backdrop-filter: blur(4px);

			h3 {
				margin: 0;
				font-size: 20px;
				font-weight: bold;
				background-size: 32px;
				background-position: 10px 10px;
				padding: 16px 8px 16px 52px;
			}
		}
	}

</style>
