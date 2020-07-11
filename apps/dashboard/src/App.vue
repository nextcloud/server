<template>
	<div id="app-dashboard">
		<h2>{{ greeting.icon }} {{ greeting.text }}</h2>

		<Container class="panels"
			orientation="horizontal"
			drag-handle-selector=".panel--header"
			@drop="onDrop">
			<Draggable v-for="panelId in layout" :key="panels[panelId].id" class="panel">
				<div class="panel--header">
					<a :href="panels[panelId].url">
						<h3 :class="panels[panelId].iconClass">
							{{ panels[panelId].title }}
						</h3>
					</a>
				</div>
				<div class="panel--content">
					<div :ref="panels[panelId].id" :data-id="panels[panelId].id" />
				</div>
			</Draggable>
		</Container>
		<a class="edit-panels icon-add" @click="showModal">{{ t('dashboard', 'Edit panels') }}</a>
		<Modal v-if="modal" @close="closeModal">
			<div class="modal__content">
				<h3>{{ t('dashboard', 'Edit panels') }}</h3>
				<transition-group name="flip-list" tag="ol">
					<li v-for="panel in sortedPanels" :key="panel.id">
						<input :id="'panel-checkbox-' + panel.id"
							type="checkbox"
							class="checkbox"
							:checked="isActive(panel)"
							@input="updateCheckbox(panel, $event.target.checked)">
						<label :for="'panel-checkbox-' + panel.id">
							{{ panel.title }}
						</label>
					</li>
					<li key="appstore">
						<a href="generateUrl('/apps/settings')" class="button">{{ t('dashboard', 'Get more panels from the app store') }}</a>
					</li>
				</transition-group>
			</div>
		</Modal>
	</div>
</template>

<script>
import Vue from 'vue'
import { loadState } from '@nextcloud/initial-state'
import { getCurrentUser } from '@nextcloud/auth'
import { Modal } from '@nextcloud/vue'
import { Container, Draggable } from 'vue-smooth-dnd'
import axios from '@nextcloud/axios'
import { generateUrl } from '@nextcloud/router'

const panels = loadState('dashboard', 'panels')

const applyDrag = (arr, dragResult) => {
	const { removedIndex, addedIndex, payload } = dragResult
	if (removedIndex === null && addedIndex === null) return arr

	const result = [...arr]
	let itemToAdd = payload

	if (removedIndex !== null) {
		itemToAdd = result.splice(removedIndex, 1)[0]
	}

	if (addedIndex !== null) {
		result.splice(addedIndex, 0, itemToAdd)
	}

	return result
}

export default {
	name: 'App',
	components: {
		Modal,
		Container,
		Draggable,
	},
	data() {
		return {
			timer: new Date(),
			callbacks: {},
			panels,
			name: getCurrentUser()?.displayName,
			layout: loadState('dashboard', 'layout').filter((panelId) => panels[panelId]),
			modal: false,
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
	},
	mounted() {
		setInterval(() => {
			this.timer = new Date()
		}, 30000)
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
		rerenderPanels() {
			for (const app in this.callbacks) {
				const element = this.$refs[app]
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
		onDrop(dropResult) {
			this.layout = applyDrag(this.layout, dropResult)
			this.saveLayout()
		},
		showModal() {
			this.modal = true
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

	.panel, .panels > div {
		width: 320px;
		max-width: 100%;
		margin: 16px;
		background-color: var(--color-main-background-translucent);
		border-radius: var(--border-radius-large);
		border: 2px solid var(--color-border);

		& > .panel--header {
			position: sticky;
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
				background-position: 12px 12px;
				padding: 16px 8px 16px 60px;
				cursor: grab;
			}
		}

		& > .panel--content {
			margin: 0 16px 16px 16px;
		}
	}

	.edit-panels {
		position: fixed;
		bottom: 20px;
		right: 20px;
		padding: 10px;
		padding-left: 35px;
		padding-right: 15px;
		background-position: 10px center;
		opacity: .7;
		background-color: var(--color-main-background);
		border-radius: var(--border-radius-pill);
		&:hover {
			opacity: 1;
			background-color: var(--color-background-hover);
		}
	}

	.modal__content {
		width: 30vw;
		margin: 20px;
		ol {
			list-style-type: none;
		}
		li label {
			padding: 10px;
			display: block;
			list-style-type: none;
		}
	}

	.flip-list-move {
		transition: transform 1s;
	}

</style>
