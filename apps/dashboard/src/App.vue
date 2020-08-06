<template>
	<div id="app-dashboard">
		<h2>{{ greeting.icon }} {{ greeting.text }}</h2>
		<div class="statuses">
			<div v-for="status in registeredStatus"
				:id="'status-' + status"
				:key="status"
				:ref="'status-' + status" />
		</div>

		<Draggable v-model="layout"
			class="panels"
			handle=".panel--header"
			@end="saveLayout">
			<div v-for="panelId in layout" :key="panels[panelId].id" class="panel">
				<div class="panel--header">
					<h3 :class="panels[panelId].iconClass">
						{{ panels[panelId].title }}
					</h3>
				</div>
				<div class="panel--content">
					<div :ref="panels[panelId].id" :data-id="panels[panelId].id" />
				</div>
			</div>
		</Draggable>

		<a v-tooltip="tooltip"
			class="edit-panels icon-add"
			:class="{ firstrun: firstRun }"
			@click="showModal">{{ t('dashboard', 'Edit widgets') }}</a>

		<Modal v-if="modal" @close="closeModal">
			<div class="modal__content">
				<h3>{{ t('dashboard', 'Edit widgets') }}</h3>
				<Draggable v-model="layout"
					class="panels"
					tag="ol"
					handle=".draggable"
					@end="saveLayout">
					<li v-for="panel in sortedPanels" :key="panel.id">
						<input :id="'panel-checkbox-' + panel.id"
							type="checkbox"
							class="checkbox"
							:checked="isActive(panel)"
							@input="updateCheckbox(panel, $event.target.checked)">
						<label :for="'panel-checkbox-' + panel.id" :class="isActive(panel) ? 'draggable ' + panel.iconClass : panel.iconClass">
							{{ panel.title }}
						</label>
					</li>
				</Draggable>

				<a :href="appStoreUrl" class="button">{{ t('dashboard', 'Get more widgets from the app store') }}</a>
			</div>
		</Modal>
	</div>
</template>

<script>
import Vue from 'vue'
import { loadState } from '@nextcloud/initial-state'
import { getCurrentUser } from '@nextcloud/auth'
import { Modal } from '@nextcloud/vue'
import Draggable from 'vuedraggable'
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

const panels = loadState('dashboard', 'panels')
const firstRun = loadState('dashboard', 'firstRun')

export default {
	name: 'App',
	components: {
		Modal,
		Draggable,
	},
	data() {
		return {
			timer: new Date(),
			registeredStatus: [],
			callbacks: {},
			callbacksStatus: {},
			panels,
			firstRun,
			displayName: getCurrentUser()?.displayName,
			uid: getCurrentUser()?.uid,
			layout: loadState('dashboard', 'layout').filter((panelId) => panels[panelId]),
			modal: false,
			appStoreUrl: generateUrl('/settings/apps/dashboard'),
			statuses: {},
		}
	},
	computed: {
		tooltip() {
			if (!this.firstRun) {
				return null
			}
			return {
				content: t('dashboard', 'Adjust the dashboard to your needs'),
				placement: 'top',
				show: true,
				trigger: 'manual',
			}
		},
		greeting() {
			const time = this.timer.getHours()
			const shouldShowName = this.displayName && this.uid !== this.displayName

			if (time > 18) {
				return { icon: '🌙', text: shouldShowName ? t('dashboard', 'Good evening, {name}', { name: this.displayName }) : t('dashboard', 'Good evening') }
			}
			if (time > 12) {
				return { icon: '☀', text: shouldShowName ? t('dashboard', 'Good afternoon, {name}', { name: this.displayName }) : t('dashboard', 'Good afternoon') }
			}
			if (time === 12) {
				return { icon: '🍽', text: shouldShowName ? t('dashboard', 'Time for lunch, {name}', { name: this.displayName }) : t('dashboard', 'Time for lunch') }
			}
			if (time > 5) {
				return { icon: '🌄', text: shouldShowName ? t('dashboard', 'Good morning, {name}', { name: this.displayName }) : t('dashboard', 'Good morning') }
			}
			return { icon: '🦉', text: shouldShowName ? t('dashboard', 'Have a night owl, {name}', { name: this.displayName }) : t('dashboard', 'Have a night owl') }
		},
		isActive() {
			return (panel) => this.layout.indexOf(panel.id) > -1
		},
		sortedPanels() {
			return Object.values(this.panels).sort((a, b) => {
				const indexA = this.layout.indexOf(a.id)
				const indexB = this.layout.indexOf(b.id)
				if (indexA === -1 || indexB === -1) {
					return indexB - indexA || a.id - b.id
				}
				return indexA - indexB || a.id - b.id
			})
		},
	},
	watch: {
		callbacks() {
			this.rerenderPanels()
		},
		callbacksStatus() {
			for (const app in this.callbacksStatus) {
				const element = this.$refs['status-' + app]
				if (this.statuses[app] && this.statuses[app].mounted) {
					continue
				}
				if (element) {
					this.callbacksStatus[app](element[0])
					Vue.set(this.statuses, app, { mounted: true })
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

		if (this.firstRun) {
			window.addEventListener('scroll', this.disableFirstrunHint)
		}
	},
	methods: {
		/**
		 * Method to register panels that will be called by the integrating apps
		 *
		 * @param {string} app The unique app id for the widget
		 * @param {function} callback The callback function to register a panel which gets the DOM element passed as parameter
		 */
		register(app, callback) {
			Vue.set(this.callbacks, app, callback)
		},
		registerStatus(app, callback) {
			this.registeredStatus.push(app)
			this.$nextTick(() => {
				Vue.set(this.callbacksStatus, app, callback)
			})
		},
		rerenderPanels() {
			for (const app in this.callbacks) {
				const element = this.$refs[app]
				if (this.layout.indexOf(app) === -1) {
					continue
				}
				if (this.panels[app] && this.panels[app].mounted) {
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
		saveLayout() {
			axios.post(generateUrl('/apps/dashboard/layout'), {
				layout: this.layout.join(','),
			})
		},
		showModal() {
			this.modal = true
			this.firstRun = false
		},
		closeModal() {
			this.modal = false
		},
		updateCheckbox(panel, currentValue) {
			const index = this.layout.indexOf(panel.id)
			if (!currentValue && index > -1) {
				this.layout.splice(index, 1)

			} else {
				this.layout.push(panel.id)
			}
			Vue.set(this.panels[panel.id], 'mounted', false)
			this.saveLayout()
			this.$nextTick(() => this.rerenderPanels())
		},
		disableFirstrunHint() {
			window.removeEventListener('scroll', this.disableFirstrunHint)
			setTimeout(() => {
				this.firstRun = false
			}, 1000)
		},
	},
}
</script>

<style lang="scss" scoped>
	#app-dashboard {
		width: 100%;
		margin-bottom: 100px;
	}

	h2 {
		text-align: center;
		font-size: 32px;
		line-height: 130%;
		padding: 80px 16px 0px;
	}

	.panels {
		width: auto;
		margin: auto;
		max-width: 1500px;
		display: flex;
		justify-content: center;
		flex-direction: row;
		align-items: flex-start;
		flex-wrap: wrap;
	}

	.panel, .panels > div {
		width: 320px;
		max-width: 100%;
		margin: 16px;
		background-color: var(--color-main-background-translucent);
		border-radius: var(--border-radius-large);
		border: 2px solid var(--color-border);

		&.sortable-ghost {
			 opacity: 0.1;
		}

		& > .panel--header {
			display: flex;
			z-index: 1;
			top: 50px;
			padding: 16px;
			// TO DO: use variables here
			background: linear-gradient(170deg, rgba(0, 130,201, 0.2) 0%, rgba(255,255,255,.1) 50%, rgba(255,255,255,0) 100%);
			border-top-left-radius: calc(var(--border-radius-large) - 2px);
			border-top-right-radius: calc(var(--border-radius-large) - 2px);
			backdrop-filter: blur(4px);
			cursor: grab;

			&, ::v-deep * {
				-webkit-touch-callout: none;
				-webkit-user-select: none;
				-khtml-user-select: none;
				-moz-user-select: none;
				-ms-user-select: none;
				user-select: none;
			}

			&:active {
				cursor: grabbing;
			}

			a {
				flex-grow: 1;
			}

			h3 {
				display: block;
				flex-grow: 1;
				margin: 0;
				font-size: 20px;
				font-weight: bold;
				background-size: 32px;
				background-position: 14px 12px;
				padding: 16px 8px 16px 60px;
				cursor: grab;
			}
		}

		& > .panel--content {
			margin: 0 16px 16px 16px;
			height: 420px;
			overflow: auto;
		}
	}

	.edit-panels {
		z-index: 99;
		position: fixed;
		bottom: 20px;
		right: 20px;
		padding: 10px 15px 10px 35px;
		background-position: 10px center;
		opacity: .7;
		background-color: var(--color-main-background);
		border-radius: var(--border-radius-pill);
		transition: right var(--animation-slow) ease-in-out;

		&:hover {
			opacity: 1;
			background-color: var(--color-background-hover);
		}

		&.firstrun {
			right: 50%;
			transform: translateX(50%);
			max-width: 200px;
			box-shadow: 0px 0px 3px var(--color-box-shadow);
			opacity: 1;
			text-align: center;
		}
	}

	.modal__content {
		width: 30vw;
		margin: 20px;
		ol {
			display: flex;
			flex-direction: column;
			list-style-type: none;
		}
		li label {
			padding: 10px;
			display: block;
			list-style-type: none;
			background-size: 16px;
			background-position: left center;
			padding-left: 26px;
		}
	}

	.flip-list-move {
		transition: transform var(--animation-slow);
	}

	.statuses {
		display: flex;
		flex-direction: row;
		justify-content: center;
		margin-bottom: 40px;

		& > div {
			max-width: 200px;
		}
	}

</style>
