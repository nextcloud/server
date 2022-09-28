<template>
	<div id="app-dashboard">
		<h2>{{ greeting.text }}</h2>
		<ul class="statuses">
			<div v-for="status in sortedRegisteredStatus"
				:id="'status-' + status"
				:key="status">
				<div :ref="'status-' + status" />
			</div>
		</ul>

		<Draggable v-model="layout"
			class="panels"
			v-bind="{swapThreshold: 0.30, delay: 500, delayOnTouchOnly: true, touchStartThreshold: 3}"
			handle=".panel--header"
			@end="saveLayout">
			<div v-for="panelId in layout" :key="panels[panelId].id" class="panel">
				<div class="panel--header">
					<h2>
						<div :class="panels[panelId].iconClass" role="img" />
						{{ panels[panelId].title }}
					</h2>
				</div>
				<div class="panel--content" :class="{ loading: !panels[panelId].mounted }">
					<div :ref="panels[panelId].id" :data-id="panels[panelId].id" />
				</div>
			</div>
		</Draggable>

		<div class="footer">
			<NcButton @click="showModal">
				<template #icon>
					<Pencil :size="20" />
				</template>
				{{ t('dashboard', 'Customize') }}
			</NcButton>
		</div>

		<NcModal v-if="modal" size="large" @close="closeModal">
			<div class="modal__content">
				<h3>{{ t('dashboard', 'Edit widgets') }}</h3>
				<ol class="panels">
					<li v-for="status in sortedAllStatuses" :key="status" :class="'panel-' + status">
						<input :id="'status-checkbox-' + status"
							type="checkbox"
							class="checkbox"
							:checked="isStatusActive(status)"
							@input="updateStatusCheckbox(status, $event.target.checked)">
						<label :for="'status-checkbox-' + status">
							<div :class="statusInfo[status].icon" role="img" />
							{{ statusInfo[status].text }}
						</label>
					</li>
				</ol>
				<Draggable v-model="layout"
					class="panels"
					tag="ol"
					v-bind="{swapThreshold: 0.30, delay: 500, delayOnTouchOnly: true, touchStartThreshold: 3}"
					handle=".draggable"
					@end="saveLayout">
					<li v-for="panel in sortedPanels" :key="panel.id" :class="'panel-' + panel.id">
						<input :id="'panel-checkbox-' + panel.id"
							type="checkbox"
							class="checkbox"
							:checked="isActive(panel)"
							@input="updateCheckbox(panel, $event.target.checked)">
						<label :for="'panel-checkbox-' + panel.id" :class="{ draggable: isActive(panel) }">
							<div :class="panel.iconClass" role="img" />
							{{ panel.title }}
						</label>
					</li>
				</Draggable>

				<a v-if="isAdmin" :href="appStoreUrl" class="button">{{ t('dashboard', 'Get more widgets from the App Store') }}</a>

				<h3>{{ t('dashboard', 'Weather service') }}</h3>
				<p>
					{{ t('dashboard', 'For your privacy, the weather data is requested by your Nextcloud server on your behalf so the weather service receives no personal information.') }}
				</p>
				<p class="credits--end">
					<a href="https://api.met.no/doc/TermsOfService" target="_blank" rel="noopener">{{ t('dashboard', 'Weather data from Met.no') }}</a>,
					<a href="https://wiki.osmfoundation.org/wiki/Privacy_Policy" target="_blank" rel="noopener">{{ t('dashboard', 'geocoding with Nominatim') }}</a>,
					<a href="https://www.opentopodata.org/#public-api" target="_blank" rel="noopener">{{ t('dashboard', 'elevation data from OpenTopoData') }}</a>.
				</p>
			</div>
		</NcModal>
	</div>
</template>

<script>
import { generateUrl } from '@nextcloud/router'
import { getCurrentUser } from '@nextcloud/auth'
import { loadState } from '@nextcloud/initial-state'
import axios from '@nextcloud/axios'
import NcButton from '@nextcloud/vue/dist/Components/NcButton'
import Draggable from 'vuedraggable'
import NcModal from '@nextcloud/vue/dist/Components/NcModal'
import Pencil from 'vue-material-design-icons/Pencil.vue'
import Vue from 'vue'

import isMobile from './mixins/isMobile.js'

const panels = loadState('dashboard', 'panels')
const firstRun = loadState('dashboard', 'firstRun')

const background = loadState('theming', 'background')
const themingDefaultBackground = loadState('theming', 'themingDefaultBackground')
const shippedBackgroundList = loadState('theming', 'shippedBackgrounds')

const statusInfo = {
	weather: {
		text: t('dashboard', 'Weather'),
		icon: 'icon-weather-status',
	},
	status: {
		text: t('dashboard', 'Status'),
		icon: 'icon-user-status-online',
	},
}

export default {
	name: 'DashboardApp',
	components: {
		NcButton,
		Draggable,
		NcModal,
		Pencil,
	},
	mixins: [
		isMobile,
	],

	data() {
		return {
			isAdmin: getCurrentUser().isAdmin,
			timer: new Date(),
			registeredStatus: [],
			callbacks: {},
			callbacksStatus: {},
			allCallbacksStatus: {},
			statusInfo,
			enabledStatuses: loadState('dashboard', 'statuses'),
			panels,
			firstRun,
			displayName: getCurrentUser()?.displayName,
			uid: getCurrentUser()?.uid,
			layout: loadState('dashboard', 'layout').filter((panelId) => panels[panelId]),
			modal: false,
			appStoreUrl: generateUrl('/settings/apps/dashboard'),
			statuses: {},
			background,
			themingDefaultBackground,
		}
	},
	computed: {
		greeting() {
			const time = this.timer.getHours()

			// Determine part of the day
			let partOfDay
			if (time >= 22 || time < 5) {
				partOfDay = 'night'
			} else if (time >= 18) {
				partOfDay = 'evening'
			} else if (time >= 12) {
				partOfDay = 'afternoon'
			} else {
				partOfDay = 'morning'
			}

			// Define the greetings
			const good = {
				morning: {
					generic: t('dashboard', 'Good morning'),
					withName: t('dashboard', 'Good morning, {name}', { name: this.displayName }, undefined, { escape: false }),
				},
				afternoon: {
					generic: t('dashboard', 'Good afternoon'),
					withName: t('dashboard', 'Good afternoon, {name}', { name: this.displayName }, undefined, { escape: false }),
				},
				evening: {
					generic: t('dashboard', 'Good evening'),
					withName: t('dashboard', 'Good evening, {name}', { name: this.displayName }, undefined, { escape: false }),
				},
				night: {
					// Don't use "Good night" as it's not a greeting
					generic: t('dashboard', 'Hello'),
					withName: t('dashboard', 'Hello, {name}', { name: this.displayName }, undefined, { escape: false }),
				},
			}

			// Figure out which greeting to show
			const shouldShowName = this.displayName && this.uid !== this.displayName
			return { text: shouldShowName ? good[partOfDay].withName : good[partOfDay].generic }
		},

		isActive() {
			return (panel) => this.layout.indexOf(panel.id) > -1
		},
		isStatusActive() {
			return (status) => !(status in this.enabledStatuses) || this.enabledStatuses[status]
		},

		sortedAllStatuses() {
			return Object.keys(this.allCallbacksStatus).slice().sort(this.sortStatuses)
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
		sortedRegisteredStatus() {
			return this.registeredStatus.slice().sort(this.sortStatuses)
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
		this.updateGlobalStyles()
		this.updateSkipLink()
		window.addEventListener('scroll', this.handleScroll)

		setInterval(() => {
			this.timer = new Date()
		}, 30000)

		if (this.firstRun) {
			window.addEventListener('scroll', this.disableFirstrunHint)
		}
	},
	destroyed() {
		window.removeEventListener('scroll', this.handleScroll)
	},

	methods: {
		updateGlobalStyles() {
			// Override primary-invert-if-bright and color-primary-text if background is set
			const isBackgroundBright = shippedBackgroundList[this.background]?.theming === 'dark'
			if (isBackgroundBright) {
				document.querySelector('#header').style.setProperty('--primary-invert-if-bright', 'invert(100%)')
				document.querySelector('#header').style.setProperty('--color-primary-text', '#000000')
				// document.body.removeAttribute('data-theme-dark')
				// document.body.setAttribute('data-theme-light', 'true')
			} else {
				document.querySelector('#header').style.setProperty('--primary-invert-if-bright', 'no')
				document.querySelector('#header').style.setProperty('--color-primary-text', '#ffffff')
				// document.body.removeAttribute('data-theme-light')
				// document.body.setAttribute('data-theme-dark', 'true')
			}
		},
		/**
		 * Method to register panels that will be called by the integrating apps
		 *
		 * @param {string} app The unique app id for the widget
		 * @param {Function} callback The callback function to register a panel which gets the DOM element passed as parameter
		 */
		register(app, callback) {
			Vue.set(this.callbacks, app, callback)
		},
		registerStatus(app, callback) {
			// always save callbacks in case user enables the status later
			Vue.set(this.allCallbacksStatus, app, callback)
			// register only if status is enabled or missing from config
			if (this.isStatusActive(app)) {
				this.registeredStatus.push(app)
				this.$nextTick(() => {
					Vue.set(this.callbacksStatus, app, callback)
				})
			}
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
					this.callbacks[app](element[0], {
						widget: this.panels[app],
					})
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
		saveStatuses() {
			axios.post(generateUrl('/apps/dashboard/statuses'), {
				statuses: JSON.stringify(this.enabledStatuses),
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
		updateSkipLink() {
			// Make sure "Skip to main content" link points to the app content
			document.getElementsByClassName('skip-navigation')[0].setAttribute('href', '#app-dashboard')
		},
		updateStatusCheckbox(app, checked) {
			if (checked) {
				this.enableStatus(app)
			} else {
				this.disableStatus(app)
			}
		},
		enableStatus(app) {
			this.enabledStatuses[app] = true
			this.registerStatus(app, this.allCallbacksStatus[app])
			this.saveStatuses()
		},
		disableStatus(app) {
			this.enabledStatuses[app] = false
			const i = this.registeredStatus.findIndex((s) => s === app)
			if (i !== -1) {
				this.registeredStatus.splice(i, 1)
				Vue.set(this.statuses, app, { mounted: false })
				this.$nextTick(() => {
					Vue.delete(this.callbacksStatus, app)
				})
			}
			this.saveStatuses()
		},
		sortStatuses(a, b) {
			const al = a.toLowerCase()
			const bl = b.toLowerCase()
			return al > bl
				? 1
				: al < bl
					? -1
					: 0
		},
		handleScroll() {
			if (window.scrollY > 70) {
				document.body.classList.add('dashboard--scrolled')
			} else {
				document.body.classList.remove('dashboard--scrolled')
			}
		},
	},
}
</script>

<style lang="scss" scoped>
#app-dashboard {
	width: 100%;
	min-height: 100%;
	background-size: cover;
	background-position: center center;
	background-repeat: no-repeat;
	background-attachment: fixed;

	> h2 {
		color: var(--color-primary-text);
		text-align: center;
		font-size: 32px;
		line-height: 130%;
		padding: 1rem 0;
	}
}

.panels {
	width: auto;
	margin: auto;
	max-width: 1800px;
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
	background-color: var(--color-main-background-blur);
	-webkit-backdrop-filter: var(--filter-background-blur);
	backdrop-filter: var(--filter-background-blur);
	border-radius: var(--border-radius-large);

	#body-user.theme--highcontrast & {
		border: 2px solid var(--color-border);
	}

	&.sortable-ghost {
		 opacity: 0.1;
	}

	& > .panel--header {
		display: flex;
		z-index: 1;
		top: 50px;
		padding: 16px;
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

		> h2 {
			display: block;
			align-items: center;
			flex-grow: 1;
			margin: 0;
			font-size: 20px;
			line-height: 24px;
			font-weight: bold;
			padding: 16px 8px;
			height: 56px;
			white-space: nowrap;
			overflow: hidden;
			text-overflow: ellipsis;
			cursor: grab;
			div {
				background-size: 32px;
				width: 32px;
				height: 32px;
				margin-right: 16px;
				background-position: center;
				float: left;
			}
		}
	}

	& > .panel--content {
		margin: 0 16px 16px 16px;
		height: 424px;
		// We specifically do not want scrollbars inside widgets
		overflow: visible;
	}

	// No need to extend height of widgets if only one column is shown
	@media only screen and (max-width: 709px) {
		& > .panel--content {
			height: auto;
		}
	}
}

.footer {
	display: flex;
	justify-content: center;
	transition: bottom var(--animation-slow) ease-in-out;
	padding: 1rem 0;
}

.edit-panels {
	display: inline-block;
	margin:auto;
	background-position: 16px center;
	padding: 12px 16px;
	padding-left: 36px;
	border-radius: var(--border-radius-pill);
	max-width: 200px;
	opacity: 1;
	text-align: center;
}

.button,
.button-vue,
.edit-panels,
.statuses ::v-deep .action-item .action-item__menutoggle,
.statuses ::v-deep .action-item.action-item--open .action-item__menutoggle {
	background-color: var(--color-main-background-blur);
	-webkit-backdrop-filter: var(--filter-background-blur);
	backdrop-filter: var(--filter-background-blur);
	opacity: 1 !important;

	&:hover,
	&:focus,
	&:active {
		background-color: var(--color-background-hover)!important;
	}
	&:focus-visible {
		box-shadow: 0 0 0 2px var(--color-main-text) !important;
	}
}

.modal__content {
	padding: 32px 16px;
	text-align: center;

	ol {
		display: flex;
		flex-direction: row;
		justify-content: center;
		list-style-type: none;
		padding-bottom: 16px;
	}
	li {
		label {
			position: relative;
			display: block;
			padding: 48px 16px 14px 16px;
			margin: 8px;
			width: 140px;
			background-color: var(--color-background-hover);
			border: 2px solid var(--color-main-background);
			border-radius: var(--border-radius-large);
			text-align: left;
			overflow: hidden;
			text-overflow: ellipsis;
			white-space: nowrap;

			div {
				position: absolute;
				top: 16px;
				width: 24px;
				height: 24px;
				background-size: 24px;
			}

			&:hover {
				border-color: var(--color-primary);
			}
		}

		// Do not invert status icons
		&:not(.panel-status) label div {
			filter: var(--background-invert-if-dark);
		}

		input[type='checkbox'].checkbox + label:before {
			position: absolute;
			right: 12px;
			top: 16px;
		}

		input:focus + label {
			border-color: var(--color-primary);
		}
	}

	h3 {
		font-weight: bold;

		&:not(:first-of-type) {
			margin-top: 64px;
		}
	}

	// Adjust design of 'Get more widgets' button
	.button {
		display: inline-block;
		padding: 10px 16px;
		margin: 0;
	}

	p {
		max-width: 650px;
		margin: 0 auto;

		a:hover,
		a:focus {
			border-bottom: 2px solid var(--color-border);
		}
	}

	.credits--end {
		padding-bottom: 32px;
		color: var(--color-text-maxcontrast);

		a {
			color: var(--color-text-maxcontrast);
		}
	}
}

.flip-list-move {
	transition: transform var(--animation-slow);
}

.statuses {
	display: flex;
	flex-direction: row;
	justify-content: center;
	flex-wrap: wrap;
	margin-bottom: 36px;

	& > div {
		margin: 8px;
	}
}
</style>
<style>
html, body {
	background-attachment: fixed;
}

#body-user #header {
	position: fixed;
}

#content {
	overflow: auto;
	position: static !important;;
}
</style>
