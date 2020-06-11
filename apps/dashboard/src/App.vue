<template>
	<div id="app-dashboard">
		<h2>{{ greeting }}, {{ name }}</h2>

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

console.debug('Loading dashboard panels', panels)

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
				return t('dashboard', '🌙 Time to call it a day')
			}
			if (time > 12) {
				return t('dashboard', '☀ Good afternoon')
			}
			if (time === 12) {
				return t('dashboard', '🍽 Time for lunch')
			}
			if (time > 5) {
				return t('dashboard', '🌄 Good morning')
			}
			return t('dashboard', '🦉 Have a night owl')
		},
	},
	watch: {
		callbacks() {
			for (const app in this.callbacks) {
				const element = this.$refs[app]
				if (this.panels[app].mounted) {
					return
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
		padding-left: 50px;
		padding-right: 50px;
	}
	h2 {
		text-align: center;
		padding: 40px;
	}

	.panels {
		width: 100%;
		display: flex;
		justify-content: center;
	}

	.panel {
		width: 250px;
		margin: 0 30px 30px 0;

		&:first-child {
			margin-left: 30px;
		}

		h3 {
			position: sticky;
			top: 50px;
			margin-top: 0;
			background-position: 10px 32px;
			padding: 30px 12px 12px 35px;
			background-color: var(--color-main-background);
		}

	}

</style>
