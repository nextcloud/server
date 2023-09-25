<template>
	<ol ref="listElement" class="order-selector">
		<AppOrderSelectorElement v-for="app,index in appList"
			:key="app.id"
			:app="app"
			:is-first="index === 0"
			:is-last="index === value.length - 1"
			@move:up="moveUp(index)"
			@move:down="moveDown(index)" />
	</ol>
</template>

<script lang="ts">
import { useSortable } from '@vueuse/integrations/useSortable'
import { PropType, computed, defineComponent, ref } from 'vue'

import AppOrderSelectorElement from './AppOrderSelectorElement.vue'

interface IApp {
	id: string // app id
	icon: string // path to the icon svg
	label?: string // display name
}

export default defineComponent({
	name: 'AppOrderSelector',
	components: {
		AppOrderSelectorElement,
	},
	props: {
		value: {
			type: Array as PropType<IApp[]>,
			required: true,
		},
	},
	emits: {
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
			set: (list) => emit('update:value', list),
		})

		/**
		 * Handle drag & drop sorting
		 */
		useSortable(listElement, appList)

		/**
		 * Handle element is moved up
		 * @param index The index of the element that is moved
		 */
		const moveUp = (index: number) => {
			const before = index > 1 ? props.value.slice(0, index - 1) : []
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

		return {
			appList,
			listElement,

			moveDown,
			moveUp,
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
