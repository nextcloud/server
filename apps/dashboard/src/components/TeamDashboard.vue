<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import type { TeamDashboardOptions } from '../dashboardApi.ts'

import { mdiCogOutline, mdiRefresh } from '@mdi/js'
import { t } from '@nextcloud/l10n'
import { useEventListener } from '@vueuse/core'
import { computed, onMounted, ref, watch } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcDialog from '@nextcloud/vue/components/NcDialog'
import NcEmptyContent from '@nextcloud/vue/components/NcEmptyContent'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcLoadingIcon from '@nextcloud/vue/components/NcLoadingIcon'
import ApiDashboardWidget from './ApiDashboardWidget.vue'
import DashboardWidgetChoice from './DashboardWidgetChoice.vue'
import DashboardWidgetHeading from './DashboardWidgetHeading.vue'
import { logger } from '../logger.ts'
import { useDashboardWidgets } from '../useDashboardWidgets.ts'

const { options } = defineProps<{ options: TeamDashboardOptions }>()
const { widgets, items, visible, loading, error, teamState, reloadWidgets } = useDashboardWidgets(options)
const layout = computed(() => teamState.value?.status === 'ready' ? teamState.value.layout : undefined)
const customizing = ref(false)
const saving = ref(false)
const saveError = ref(false)
const draft = ref<string[]>([])

watch(teamState, (state) => {
	if (!layout.value?.canEdit) {
		customizing.value = false
	}
	if (state?.status === 'unavailable') {
		options.onUnavailable()
	}
})

const choices = computed(() => [
	...Object.values(widgets.value).map((widget) => ({ id: widget.id, title: widget.title, iconUrl: widget.icon_url, iconClass: widget.icon_class, missing: false })),
	...(layout.value?.widgetIds ?? []).filter((id) => !Object.hasOwn(widgets.value, id))
		.map((id) => ({ id, title: id, iconUrl: '', iconClass: '', missing: true })),
])

/** Start from the saved layout each time so Cancel discards the draft. */
function openWidgetPicker() {
	draft.value = [...(layout.value?.widgetIds ?? Object.keys(widgets.value))]
	saveError.value = false
	customizing.value = true
}

/**
 * Update the draft without changing the saved layout.
 *
 * @param id - Widget identifier
 * @param checked - Whether to include the widget
 */
function toggleWidgetSelection(id: string, checked: boolean) {
	draft.value = checked ? [...new Set([...draft.value, id])] : draft.value.filter((entry) => entry !== id)
}

/**
 * Save the explicit selection, or restore automatic defaults.
 *
 * @param widgetIds - Selected widget IDs, or null for automatic defaults
 */
async function saveWidgetSelection(widgetIds: string[] | null) {
	if (!layout.value?.canEdit || saving.value) {
		return
	}
	saving.value = true
	saveError.value = false
	try {
		await options.saveLayout(widgetIds)
		customizing.value = false
		await reloadWidgets()
	} catch (reason) {
		logger.error('Could not save team dashboard layout', { reason })
		saveError.value = true
	} finally {
		saving.value = false
	}
}

onMounted(() => reloadWidgets())
useEventListener(document, 'visibilitychange', () => {
	if (!document.hidden) {
		reloadWidgets({ background: true })
	}
})
</script>

<template>
	<div class="team-overview">
		<header class="team-overview__header">
			<h2>{{ t('dashboard', 'Overview') }}</h2>
			<NcButton v-if="layout?.canEdit" :disabled="loading || error || saving" @click="openWidgetPicker">
				<template #icon>
					<NcIconSvgWrapper :path="mdiCogOutline" />
				</template>
				{{ t('dashboard', 'Customize widgets') }}
			</NcButton>
		</header>
		<NcLoadingIcon v-if="loading" :size="44" />
		<NcEmptyContent v-else-if="error" :name="t('dashboard', 'Could not load widgets')">
			<template #action>
				<NcButton @click="reloadWidgets()">
					<template #icon>
						<NcIconSvgWrapper :path="mdiRefresh" />
					</template>
					{{ t('dashboard', 'Try again') }}
				</NcButton>
			</template>
		</NcEmptyContent>
		<NcEmptyContent v-else-if="!visible.length" :name="layout?.widgetIds?.length === 0 ? t('dashboard', 'No widgets selected') : t('dashboard', 'No widgets available')" />
		<div v-else class="team-overview__widgets">
			<section
				v-for="widget in visible"
				:key="widget.id"
				class="team-overview__widget"
				:aria-label="widget.title">
				<DashboardWidgetHeading
					class="team-overview__heading"
					tag="h3"
					:title="widget.title"
					:iconUrl="widget.icon_url"
					:iconClass="widget.icon_class"
					:href="widget.widget_url" />
				<ApiDashboardWidget :widget="widget" :data="items[widget.id]" :loading="false" />
			</section>
		</div>
		<NcDialog
			v-if="customizing"
			:name="t('dashboard', 'Customize widgets')"
			:closeOnClickOutside="!saving"
			:noClose="saving"
			@update:open="!$event && (customizing = false)">
			<ul v-if="choices.length" class="team-overview__choices">
				<DashboardWidgetChoice
					v-for="choice in choices"
					:id="'team-widget-choice-' + choice.id"
					:key="choice.id"
					:selected="draft.includes(choice.id)"
					:iconUrl="choice.iconUrl"
					:iconClass="choice.iconClass"
					:label="choice.missing ? `${choice.title} (${t('dashboard', 'Unavailable')})` : choice.title"
					:disabled="saving"
					@change="toggleWidgetSelection(choice.id, $event)" />
			</ul>
			<p v-else>
				{{ t('dashboard', 'No widgets available') }}
			</p>
			<p v-if="saveError" role="alert">
				{{ t('dashboard', 'Could not save widget selection') }}
			</p>
			<template #actions>
				<NcButton :disabled="saving || layout?.widgetIds === null" @click="saveWidgetSelection(null)">
					{{ t('dashboard', 'Restore defaults') }}
				</NcButton>
				<NcButton :disabled="saving" @click="customizing = false">
					{{ t('dashboard', 'Cancel') }}
				</NcButton>
				<NcButton variant="primary" :disabled="saving" @click="saveWidgetSelection(draft)">
					{{ t('dashboard', 'Save') }}
				</NcButton>
			</template>
		</NcDialog>
	</div>
</template>

<style scoped>
.team-overview__header {
	display: flex;
	flex-wrap: wrap;
	align-items: center;
	justify-content: space-between;
	gap: 12px;
	margin-bottom: 24px;
}

.team-overview__widgets {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(min(100%, 300px), 1fr));
	gap: 24px;
}

.team-overview__widget {
	min-width: 0;
}

.team-overview__heading {
	display: flex;
	align-items: center;
	gap: 8px;
	margin-bottom: 12px;
	font-size: 20px;
	overflow-wrap: anywhere;
}

.team-overview__heading :deep(img),
.team-overview__heading :deep(span) {
	width: 24px;
	height: 24px;
	flex-shrink: 0;
}

.team-overview__heading :deep(img) {
	filter: var(--background-invert-if-dark);
}

.team-overview__heading :deep(a) {
	color: inherit;
}

.team-overview__choices {
	display: flex;
	flex-wrap: wrap;
	list-style: none;
}
</style>
