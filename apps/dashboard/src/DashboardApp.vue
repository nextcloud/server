<!--
 - SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 - SPDX-License-Identifier: AGPL-3.0-or-later
 -->
<template>
	<main id="app-dashboard">
		<h2>{{ greeting.text }}</h2>
		<ul v-if="sortedRegisteredStatus.length > 0" class="statuses">
			<li
				v-for="status in sortedRegisteredStatus"
				:id="'status-' + status"
				:key="status">
				<div :ref="(element) => setStatusElement(status, element)" />
			</li>
		</ul>

		<div ref="panelsElement" class="panels">
			<div v-for="panelId in layout" :key="panelId" class="panel">
				<template v-if="isApiWidgetV2(panels[panelId].id)">
					<div class="panel--header">
						<DashboardWidgetHeading
							:title="apiWidgets[panels[panelId].id].title"
							:iconUrl="apiWidgets[panels[panelId].id].icon_url"
							:iconClass="apiWidgets[panels[panelId].id].icon_class" />
					</div>
					<div class="panel--content">
						<ApiDashboardWidget
							:widget="apiWidgets[panels[panelId].id]"
							:data="apiWidgetItems[panels[panelId].id]"
							:loading="loadingItems" />
					</div>
				</template>
				<template v-else>
					<div class="panel--header">
						<DashboardWidgetHeading :title="panels[panelId].title" :iconClass="panels[panelId].iconClass" />
					</div>
					<div class="panel--content" :class="{ loading: !panels[panelId].mounted }">
						<div :ref="panels[panelId].id" :data-id="panels[panelId].id" />
					</div>
				</template>
			</div>
		</div>

		<div class="footer">
			<NcButton @click="showModal">
				<template #icon>
					<Pencil :size="20" />
				</template>
				{{ t('dashboard', 'Customize') }}
			</NcButton>
		</div>

		<NcModal
			v-if="modal"
			size="large"
			:closeOnClickOutside="true"
			@close="closeModal">
			<div class="modal__content">
				<h2>{{ t('dashboard', 'Edit widgets') }}</h2>
				<ol class="panels">
					<DashboardWidgetChoice
						v-for="status in sortedAllStatuses"
						:id="'status-checkbox-' + status"
						:key="status"
						:class="'panel-' + status"
						:selected="isStatusActive(status)"
						:iconClass="statusInfo[status].icon"
						:label="statusInfo[status].text"
						@change="updateStatusCheckbox(status, $event)">
						<template v-if="status === 'status'" #icon>
							<NcUserStatusIcon status="online" />
						</template>
					</DashboardWidgetChoice>
				</ol>
				<ol ref="modalPanelsElement" class="panels">
					<DashboardWidgetChoice
						v-for="panel in modalPanelList"
						:id="'panel-checkbox-' + panel.id"
						:key="panel.id"
						:class="'panel-' + panel.id"
						:selected="isActive(panel)"
						:iconUrl="panel.iconUrl"
						:iconClass="panel.iconClass"
						:label="panel.title"
						sortable
						@change="updateCheckbox(panel, $event)" />
				</ol>
				<a v-if="isAdmin && appStoreEnabled" :href="appStoreUrl" class="button">{{ t('dashboard', 'Get more widgets from the App Store') }}</a>

				<div v-if="statuses.weather && isStatusActive('weather')">
					<h2>{{ t('dashboard', 'Weather service') }}</h2>
					<p>
						{{ t('dashboard', 'For your privacy, the weather data is requested by your {productName} server on your behalf so the weather service receives no personal information.', { productName }) }}
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
import { getCurrentUser } from '@nextcloud/auth'
import axios from '@nextcloud/axios'
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { generateOcsUrl, generateUrl } from '@nextcloud/router'
import { useSortable } from '@vueuse/integrations/useSortable'
import { nextTick, ref } from 'vue'
import { useTemplateRef } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcModal from '@nextcloud/vue/components/NcModal'
import NcUserStatusIcon from '@nextcloud/vue/components/NcUserStatusIcon'
import Pencil from 'vue-material-design-icons/Pencil.vue'
import ApiDashboardWidget from './components/ApiDashboardWidget.vue'
import DashboardWidgetChoice from './components/DashboardWidgetChoice.vue'
import DashboardWidgetHeading from './components/DashboardWidgetHeading.vue'
import { logger } from './logger.ts'
import isMobile from './mixins/isMobile.js'
import { useDashboardWidgets } from './useDashboardWidgets.ts'

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
		DashboardWidgetChoice,
		DashboardWidgetHeading,
		NcButton,
		NcModal,
		Pencil,
		NcUserStatusIcon,
	},

	mixins: [
		isMobile,
	],

	setup() {
		const layout = ref(loadState('dashboard', 'layout').filter((panelId) => panels[panelId]))
		const { widgets, items, loading, reload, refresh } = useDashboardWidgets({ widgetIds: () => layout.value })
		const modalPanelList = ref([])
		const isModalDragging = ref(false)

		const panelsElement = useTemplateRef('panelsElement')
		const modalPanelsElement = useTemplateRef('modalPanelsElement')

		const sortableOptions = {
			swapThreshold: 0.30,
			delay: 500,
			delayOnTouchOnly: true,
			touchStartThreshold: 3,
		}

		/**
		 * Save the current panel layout of the user
		 */
		function saveLayout() {
			axios.post(generateOcsUrl('/apps/dashboard/api/v3/layout'), {
				layout: layout.value,
			})
		}

		// useSortable applies the new order on the next tick, so the end handlers have to wait for it
		useSortable(panelsElement, layout, {
			...sortableOptions,
			handle: '.panel--header',
			onEnd: async () => {
				await nextTick()
				saveLayout()
			},
		})

		useSortable(modalPanelsElement, modalPanelList, {
			...sortableOptions,
			handle: '.draggable',
			watchElement: true,
			onStart: () => {
				isModalDragging.value = true
			},
			onEnd: async () => {
				await nextTick()
				isModalDragging.value = false
				layout.value = modalPanelList.value
					.filter((panel) => layout.value.includes(panel.id))
					.map((panel) => panel.id)
				saveLayout()
			},
		})

		return {
			t,
			productName: window.OC.theme.productName,
			layout,
			modalPanelList,
			isModalDragging,
			panelsElement,
			modalPanelsElement,
			saveLayout,
			apiWidgets: widgets,
			apiWidgetItems: items,
			loadingItems: loading,
			loadApiWidgets: reload,
			refreshApiItems: refresh,
		}
	},

	data() {
		return {
			isAdmin: getCurrentUser().isAdmin,
			timer: new Date(),
			registeredStatus: [],
			callbacks: {},
			allCallbacksStatus: {},
			statusElements: {},
			statusInfo,
			enabledStatuses: loadState('dashboard', 'statuses'),
			panels,
			firstRun,
			displayName: getCurrentUser()?.displayName,
			uid: getCurrentUser()?.uid,
			modal: false,
			appStoreUrl: generateUrl('/settings/apps/dashboard'),
			appStoreEnabled: loadState('dashboard', 'appStoreEnabled', true),
			statuses: {},
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
					withName: t('dashboard', 'Good morning, {name}', { name: this.displayName }, { escape: false }),
				},

				afternoon: {
					generic: t('dashboard', 'Good afternoon'),
					withName: t('dashboard', 'Good afternoon, {name}', { name: this.displayName }, { escape: false }),
				},

				evening: {
					generic: t('dashboard', 'Good evening'),
					withName: t('dashboard', 'Good evening, {name}', { name: this.displayName }, { escape: false }),
				},

				night: {
					// Don't use "Good night" as it's not a greeting
					generic: t('dashboard', 'Hello') /* TRANSLATORS: Greeting to be used at night (in English there is no specific greeting, think of it like "good morning") */,
					withName: t('dashboard', 'Hello, {name}', { name: this.displayName }, { escape: false }) /* TRANSLATORS: Greeting to be used at night (in English there is no specific greeting, think of it like "Good morning, J. Doe" but at night) */,
				},

				birthday: {
					generic: t('dashboard', 'Happy birthday 🥳🤩🎂🎉'),
					withName: t('dashboard', 'Happy birthday, {name} 🥳🤩🎂🎉', { name: this.displayName }, { escape: false }),
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

		sortedRegisteredStatus() {
			return this.registeredStatus.slice().sort(this.sortStatuses)
		},
	},

	watch: {
		callbacks() {
			this.$nextTick(() => this.rerenderPanels())
		},

		layout() {
			if (this.modal && !this.isModalDragging) {
				this.modalPanelList = this.getSortedPanelObjects()
			}
		},
	},

	async created() {
		await this.loadApiWidgets()
		this.$nextTick(() => this.rerenderPanels())
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

	unmounted() {
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
			this.callbacks[app] = callback
		},

		registerStatus(app, callback) {
			// always save callbacks in case user enables the status later
			this.allCallbacksStatus[app] = callback
			// register only if status is enabled or missing from config
			if (this.isStatusActive(app)) {
				if (!this.registeredStatus.includes(app)) {
					this.registeredStatus.push(app)
				}
				this.$nextTick(() => this.mountStatus(app))
			}
		},

		setStatusElement(app, element) {
			if (element) {
				this.statusElements[app] = element
				this.mountStatus(app)
			} else {
				delete this.statusElements[app]
			}
		},

		mountStatus(app) {
			if (this.statuses[app]?.mounted) {
				return
			}

			const element = this.statusElements[app]
			const callback = this.allCallbacksStatus[app]
			if (element && callback) {
				callback(element)
				this.statuses[app] = { mounted: true }
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
					// In Vue 3, refs in v-for are arrays
					const el = Array.isArray(element) ? element[0] : element
					this.callbacks[app](el, {
						widget: this.panels[app],
					})
					this.panels[app].mounted = true
				} else {
					logger.error('Failed to register panel in the frontend as no backend data was provided for ' + app)
				}
			}
		},

		saveStatuses() {
			axios.post(generateOcsUrl('/apps/dashboard/api/v3/statuses'), {
				statuses: this.enabledStatuses,
			})
		},

		showModal() {
			this.modal = true
			this.firstRun = false
			this.modalPanelList = this.getSortedPanelObjects()
		},

		closeModal() {
			this.modal = false
		},

		getSortedPanelObjects() {
			return Object.values(this.panels).sort((a, b) => {
				const indexA = this.layout.indexOf(a.id)
				const indexB = this.layout.indexOf(b.id)
				if (indexA === -1 || indexB === -1) {
					return indexB - indexA || a.id - b.id
				}
				return indexA - indexB || a.id - b.id
			})
		},

		updateCheckbox(panel, currentValue) {
			const index = this.layout.indexOf(panel.id)
			if (!currentValue && index > -1) {
				this.layout.splice(index, 1)
			} else {
				this.layout.push(panel.id)
				if (this.isApiWidgetV2(panel.id)) {
					void this.refreshApiItems([panel.id])
				}
			}
			this.panels[panel.id].mounted = false
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
				this.statuses[app] = { mounted: false }
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

		isApiWidgetV2(id) {
			return this.apiWidgets[id]?.item_api_versions?.includes(2) ?? false
		},
	},
}
</script>

<style lang="scss" scoped>
:global(#content.app-dashboard) {
	margin-inline: 0;
	width: 100%;
}

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

			:deep(img),
			:deep(span) {
				background-size: 32px;
				width: 32px;
				height: 32px;
				background-position: center;
				float: left;
				margin-top: -6px;
				margin-inline: 6px 16px;
			}

			:deep(img) {
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
	/* Scrollbar sits on the background image — use plain-text color for contrast */
	scrollbar-color: var(--color-background-plain-text) transparent;
}

#app-content-vue {
	width: 100%;
}
</style>
