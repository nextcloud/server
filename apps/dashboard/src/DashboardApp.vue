<!--
 - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
 -->
<template>
	<main id="app-dashboard">
		<h2>{{ greeting.text }}</h2>
		<ul class="statuses">
			<li v-for="status in sortedRegisteredStatus"
				:id="'status-' + status"
				:key="status">
				<div :ref="'status-' + status" />
			</li>
		</ul>

		<Draggable v-model="layout"
			class="panels"
			v-bind="{swapThreshold: 0.30, delay: 500, delayOnTouchOnly: true, touchStartThreshold: 3}"
			handle=".panel--header"
			@end="saveLayout">
			<template v-for="panelId in layout">
				<div v-if="isApiWidgetV2(panels[panelId].id)"
					:key="`${panels[panelId].id}-v2`"
					class="panel">
					<div class="panel--header">
						<h2>
							<img v-if="apiWidgets[panels[panelId].id].icon_url" :src="apiWidgets[panels[panelId].id].icon_url" alt="">
							<span v-else :class="apiWidgets[panels[panelId].id].icon_class" aria-hidden="true" />
							{{ apiWidgets[panels[panelId].id].title }}
						</h2>
					</div>
					<div class="panel--content">
						<ApiDashboardWidget :widget="apiWidgets[panels[panelId].id]"
							:data="apiWidgetItems[panels[panelId].id]"
							:loading="loadingItems" />
					</div>
				</div>
				<div v-else :key="panels[panelId].id" class="panel">
					<div class="panel--header">
						<h2>
							<span :class="panels[panelId].iconClass" aria-hidden="true" />
							{{ panels[panelId].title }}
						</h2>
					</div>
					<div class="panel--content" :class="{ loading: !panels[panelId].mounted }">
						<div :ref="panels[panelId].id" :data-id="panels[panelId].id" />
					</div>
				</div>
			</template>
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
				<h2>{{ t('dashboard', 'Edit widgets') }}</h2>
				<ol class="panels">
					<li v-for="status in sortedAllStatuses" :key="status" :class="'panel-' + status">
						<input :id="'status-checkbox-' + status"
							type="checkbox"
							class="checkbox"
							:checked="isStatusActive(status)"
							@input="updateStatusCheckbox(status, $event.target.checked)">
						<label :for="'status-checkbox-' + status">
							<NcUserStatusIcon v-if="status === 'status'" status="online" aria-hidden="true" />
							<span v-else :class="statusInfo[status].icon" aria-hidden="true" />
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
							<img v-if="panel.iconUrl" alt="" :src="panel.iconUrl">
							<span v-else :class="panel.iconClass" aria-hidden="true" />
							{{ panel.title }}
						</label>
					</li>
				</Draggable>

				<a v-if="isAdmin && appStoreEnabled" :href="appStoreUrl" class="button">{{ t('dashboard', 'Get more widgets from the App Store') }}</a>

				<div v-if="statuses.weather && isStatusActive('weather')">
					<h2>{{ t('dashboard', 'Weather service') }}</h2>
					<p>
						{{ t('dashboard', 'For your privacy, the weather data is requested by your Nextcloud server on your behalf so the weather service receives no personal information.') }}
					</p>
					<p class="credits--end">
						<a href="https://api.met.no/doc/TermsOfService" target="_blank" rel="noopener">{{ t('dashboard', 'Weather data from Met.no') }}</a>,
						<a href="https://wiki.osmfoundation.org/wiki/Privacy_Policy" target="_blank" rel="noopener">{{ t('dashboard', 'geocoding with Nominatim') }}</a>,
						<a href="https://www.opentopodata.org/#public-api" target="_blank" rel="noopener">{{ t('dashboard', 'elevation data from OpenTopoData') }}</a>.
					</p>
				</div>
			</div>
		</NcModal>
	</main>
</template>

<script>
import { generateUrl, generateOcsUrl } from '@nextcloud/router'
import { getCurrentUser } from '@nextcloud/auth'
import { loadState } from '@nextcloud/initial-state'
import axios from '@nextcloud/axios'
import NcButton from '@nextcloud/vue/components/NcButton'
import Draggable from 'vuedraggable'
import NcModal from '@nextcloud/vue/components/NcModal'
import NcUserStatusIcon from '@nextcloud/vue/components/NcUserStatusIcon'
import Pencil from 'vue-material-design-icons/Pencil.vue'
import Vue from 'vue'

import isMobile from './mixins/isMobile.js'
import ApiDashboardWidget from './components/ApiDashboardWidget.vue'

const panels = loadState('dashboard', 'panels')
const firstRun = loadState('dashboard', 'firstRun')
const birthdate = new Date(loadState('dashboard', 'birthdate'))

const statusInfo = {
	weather: {
		text: t('dashboard', 'Weather'),
		icon: 'icon-weather-status',
	},
	status: {
		text: t('dashboard', 'Status'),
	},
}

export default {
	name: 'DashboardApp',
	components: {
		ApiDashboardWidget,
		NcButton,
		Draggable,
		NcModal,
		Pencil,
		NcUserStatusIcon,
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
			appStoreEnabled: loadState('dashboard', 'appStoreEnabled', true),
			statuses: {},
			apiWidgets: [],
			apiWidgetItems: {},
			loadingItems: true,
			birthdate,
		}
	},
	computed: {
		greeting() {
			const time = this.timer.getHours()
			const isBirthday = this.birthdate instanceof Date
				&& this.birthdate.getMonth() === this.timer.getMonth()
				&& this.birthdate.getDate() === this.timer.getDate()

			// Determine part of the day
			let partOfDay
			if (isBirthday) {
				partOfDay = 'birthday'
			} else if (time >= 22 || time < 5) {
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
				birthday: {
					generic: t('dashboard', 'Happy birthday 🥳🤩🎂🎉'),
					withName: t('dashboard', 'Happy birthday, {name} 🥳🤩🎂🎉', { name: this.displayName }, undefined, { escape: false }),
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
			return (status) => this.enabledStatuses.findIndex((s) => s === status) !== -1
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

	async created() {
		await this.fetchApiWidgets()

		const apiWidgetIdsToFetch = Object
			.values(this.apiWidgets)
			.filter(widget => this.isApiWidgetV2(widget.id) && this.layout.includes(widget.id))
			.map(widget => widget.id)
		await Promise.all(apiWidgetIdsToFetch.map(id => this.fetchApiWidgetItems([id], true)))

		for (const widget of Object.values(this.apiWidgets)) {
			if (widget.reload_interval > 0) {
				setInterval(async () => {
					if (!this.layout.includes(widget.id)) {
						return
					}

					await this.fetchApiWidgetItems([widget.id], true)
				}, widget.reload_interval * 1000)
			}
		}
	},
	mounted() {
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
				// TODO: Properly rerender v2 widgets
				if (this.isApiWidgetV2(this.panels[app].id)) {
					continue
				}

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
			axios.post(generateOcsUrl('/apps/dashboard/api/v3/layout'), {
				layout: this.layout,
			})
		},
		saveStatuses() {
			axios.post(generateOcsUrl('/apps/dashboard/api/v3/statuses'), {
				statuses: this.enabledStatuses,
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
				if (this.isApiWidgetV2(panel.id)) {
					this.fetchApiWidgetItems([panel.id], true)
				}
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
			this.enabledStatuses.push(app)
			this.registerStatus(app, this.allCallbacksStatus[app])
			this.saveStatuses()
		},
		disableStatus(app) {
			const i = this.enabledStatuses.findIndex((s) => s === app)
			if (i !== -1) {
				this.enabledStatuses.splice(i, 1)
			}
			const j = this.registeredStatus.findIndex((s) => s === app)
			if (j !== -1) {
				this.registeredStatus.splice(j, 1)
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
		async fetchApiWidgets() {
			const { data } = await axios.get(generateOcsUrl('/apps/dashboard/api/v1/widgets'))
			this.apiWidgets = data.ocs.data
		},
		async fetchApiWidgetItems(widgetIds, merge = false) {
			try {
				const url = generateOcsUrl('/apps/dashboard/api/v2/widget-items')
				const params = new URLSearchParams(widgetIds.map(id => ['widgets[]', id]))
				const response = await axios.get(`${url}?${params.toString()}`)
				const widgetItems = response.data.ocs.data
				if (merge) {
					this.apiWidgetItems = Object.assign({}, this.apiWidgetItems, widgetItems)
				} else {
					this.apiWidgetItems = widgetItems
				}
			} finally {
				this.loadingItems = false
			}
		},
		isApiWidgetV2(id) {
			for (const widget of Object.values(this.apiWidgets)) {
				if (widget.id === id && widget.item_api_versions.includes(2)) {
					return true
				}
			}
			return false
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
		// this is shown directly on the background image / color
		color: var(--color-background-plain-text);
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
	// Ensure the maxcontrast color is set for the background
	--color-text-maxcontrast: var(--color-text-maxcontrast-background-blur, var(--color-main-text));
	width: 320px;
	max-width: 100%;
	margin: 16px;
	align-self: stretch;
	background-color: var(--color-main-background-blur);
	-webkit-backdrop-filter: var(--filter-background-blur);
	backdrop-filter: var(--filter-background-blur);
	border-radius: var(--border-radius-container-large);

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

		&,
		:deep(*) {
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

			img,
			span {
				background-size: 32px;
				width: 32px;
				height: 32px;
				background-position: center;
				float: left;
				margin-top: -6px;
				margin-inline: 6px 16px;
			}

			img {
				filter: var(--background-invert-if-dark);
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
	padding-inline-start: 36px;
	border-radius: var(--border-radius-pill);
	max-width: 200px;
	opacity: 1;
	text-align: center;
}

.button,
.button-vue,
.edit-panels,
.statuses :deep(.action-item .action-item__menutoggle),
.statuses :deep(.action-item.action-item--open .action-item__menutoggle) {
	// Ensure the maxcontrast color is set for the background
	--color-text-maxcontrast: var(--color-text-maxcontrast-background-blur, var(--color-main-text));
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
		box-shadow: 0 0 0 4px var(--color-main-background) !important;
		outline: 2px solid var(--color-main-text) !important;
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
			text-align: start;
			overflow: hidden;
			text-overflow: ellipsis;
			white-space: nowrap;

			img,
			span {
				position: absolute;
				top: 16px;
				width: 24px;
				height: 24px;
				background-size: 24px;
			}

			img {
				filter: var(--background-invert-if-dark);
			}

			&:hover {
				border-color: var(--color-primary-element);
			}
		}

		// Do not invert status icons
		&:not(.panel-status) label span {
			filter: var(--background-invert-if-dark);
		}

		input[type='checkbox'].checkbox + label:before {
			position: absolute;
			inset-inline-end: 12px;
			top: 16px;
		}

		input:focus + label {
			border-color: var(--color-primary-element);
		}
	}

	h2 {
		font-weight: bold;
		margin-top: 12px;
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

	& > li {
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
}
</style>
