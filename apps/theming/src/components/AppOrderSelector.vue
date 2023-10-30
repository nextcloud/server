<template>
	<Fragment>
		<div :id="statusInfoId"
			aria-live="polite"
			class="hidden-visually"
			role="status">
			{{ statusInfo }}
		</div>
		<ol ref="listElement" data-cy-app-order class="order-selector">
			<AppOrderSelectorElement v-for="app,index in appList"
				:key="`${app.id}${renderCount}`"
				ref="selectorElements"
				:app="app"
				:aria-details="ariaDetails"
				:aria-describedby="statusInfoId"
				:is-first="index === 0 || !!appList[index - 1].default"
				:is-last="index === value.length - 1"
				v-on="app.default ? {} : {
					'move:up': () => moveUp(index),
					'move:down': () => moveDown(index),
					'update:focus': () => updateStatusInfo(index),
				}" />
		</ol>
	</Fragment>
</template>

<script lang="ts">
import { translate as t } from '@nextcloud/l10n'
import { useSortable } from '@vueuse/integrations/useSortable'
import { PropType, computed, defineComponent, onUpdated, ref } from 'vue'
import { Fragment } from 'vue-frag'

import AppOrderSelectorElement from './AppOrderSelectorElement.vue'

export interface IApp {
	id: string // app id
	icon: string // path to the icon svg
	label: string // display name
	default?: boolean // force app as default app
	app: string
	key: number
}

export default defineComponent({
	name: 'AppOrderSelector',
	components: {
		AppOrderSelectorElement,
		Fragment,
	},
	props: {
		/**
		 * Details like status information that need to be forwarded to the interactive elements
		 */
		ariaDetails: {
			type: String,
			default: null,
		},
		/**
		 * List of apps to reorder
		 */
		value: {
			type: Array as PropType<IApp[]>,
			required: true,
		},
	},
	emits: {
		/**
		 * Update the apps list on reorder
		 * @param value The new value of the app list
		 */
		'update:value': (value: IApp[]) => Array.isArray(value),
	},
	setup(props, { emit }) {
		/**
		 * The Element that contains the app list
		 */
		const listElement = ref<HTMLElement | null>(null)

		/**
		 * The app list with setter that will ement the `update:value` event
		 */
		const appList = computed({
			get: () => props.value,
			// Ensure the sortable.js does not mess with the default attribute
			set: (list) => {
				const newValue = [...list].sort((a, b) => ((b.default ? 1 : 0) - (a.default ? 1 : 0)) || list.indexOf(a) - list.indexOf(b))
				if (newValue.some(({ id }, index) => id !== props.value[index].id)) {
					emit('update:value', newValue)
				} else {
					// forceUpdate as the DOM has changed because of a drag event, but the reactive state has not -> wrong state
					renderCount.value += 1
				}
			},
		})

		/**
		 * Helper to force rerender the list in case of a invalid drag event
		 */
		const renderCount = ref(0)

		/**
		 * Handle drag & drop sorting
		 */
		useSortable(listElement, appList, { filter: '.order-selector-element--disabled' })

		/**
		 * Array of all AppOrderSelectorElement components used to for keeping the focus after button click
		 */
		const selectorElements = ref<InstanceType<typeof AppOrderSelectorElement>[]>([])

		/**
		 * We use the updated hook here to verify all selector elements keep the focus on the last pressed button
		 * This is needed to be done in this component to make sure Sortable.JS has finished sorting the elements before focussing an element
		 */
		onUpdated(() => {
			selectorElements.value.forEach(element => element.keepFocus())
		})

		/**
		 * Handle element is moved up
		 * @param index The index of the element that is moved
		 */
		const moveUp = (index: number) => {
			const before = index > 1 ? props.value.slice(0, index - 1) : []
			// skip if not possible, because of default default app
			if (props.value[index - 1]?.default) {
				return
			}

			const after = [props.value[index - 1]]
			if (index < props.value.length - 1) {
				after.push(...props.value.slice(index + 1))
			}
			emit('update:value', [...before, props.value[index], ...after])
		}

		/**
		 * Handle element is moved down
		 * @param index The index of the element that is moved
		 */
		const moveDown = (index: number) => {
			const before = index > 0 ? props.value.slice(0, index) : []
			before.push(props.value[index + 1])

			const after = index < (props.value.length - 2) ? props.value.slice(index + 2) : []
			emit('update:value', [...before, props.value[index], ...after])
		}

		/**
		 * Additional status information to show to screen reader users for accessibility
		 */
		const statusInfo = ref('')

		/**
		 * ID to be used on the status info element
		 */
		const statusInfoId = `sorting-status-info-${(Math.random() + 1).toString(36).substring(7)}`

		/**
		 * Update the status information for the currently selected app
		 * @param index Index of the app that is currently selected
		 */
		const updateStatusInfo = (index: number) => {
			statusInfo.value = t('theming', 'Current selected app: {app}, position {position} of {total}', {
				app: props.value[index].label,
				position: index + 1,
				total: props.value.length,
			})
		}

		return {
			appList,
			listElement,

			moveDown,
			moveUp,

			statusInfoId,
			statusInfo,
			updateStatusInfo,

			renderCount,
			selectorElements,
		}
	},
})
</script>

<style scoped lang="scss">
.order-selector {
	width: max-content;
	min-width: 260px; // align with NcSelect
}
</style>
