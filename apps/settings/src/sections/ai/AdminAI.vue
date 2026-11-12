<!--
  - SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<script setup lang="ts">
import { mdiArrowDown, mdiArrowUp, mdiChevronDown, mdiChevronUp, mdiDragVertical } from '@mdi/js'
import axios from '@nextcloud/axios'
import { showError } from '@nextcloud/dialogs'
import { loadState } from '@nextcloud/initial-state'
import { t } from '@nextcloud/l10n'
import { generateUrl } from '@nextcloud/router'
import { useSortable } from '@vueuse/integrations/useSortable'
import { computed, nextTick, reactive, ref, useTemplateRef } from 'vue'
import NcButton from '@nextcloud/vue/components/NcButton'
import NcCheckboxRadioSwitch from '@nextcloud/vue/components/NcCheckboxRadioSwitch'
import NcIconSvgWrapper from '@nextcloud/vue/components/NcIconSvgWrapper'
import NcNoteCard from '@nextcloud/vue/components/NcNoteCard'
import NcSelect from '@nextcloud/vue/components/NcSelect'
import NcSettingsSection from '@nextcloud/vue/components/NcSettingsSection'
import logger from '../../logger.ts'

interface TaskType {
	id: string
	name: string
	description: string
}

interface AISettings {
	'ai.translation_provider_preferences': string[]
	'ai.textprocessing_provider_preferences': Record<string, string>
	'ai.text2image_provider': string | null
	'ai.taskprocessing_provider_preferences': Record<string, string>
	'ai.taskprocessing_type_preferences': Record<string, boolean>
	'ai.taskprocessing_guests': boolean
}

const BUILTIN_TEXT_PROCESSING_TYPES = [
	'OCP\\TextProcessing\\FreePromptTaskType',
	'OCP\\TextProcessing\\HeadlineTaskType',
	'OCP\\TextProcessing\\SummaryTaskType',
	'OCP\\TextProcessing\\TopicsTaskType',
]

const translationProviders = loadState<{ class: string, name: string }[]>('settings', 'ai-translation-providers')
const textProcessingProviders = loadState<{ class: string, name: string, taskType: string }[]>('settings', 'ai-text-processing-providers')
const textProcessingTaskTypes = loadState<{ class: string, name: string, description: string }[]>('settings', 'ai-text-processing-task-types')
const text2imageProviders = loadState<{ id: string, name: string }[]>('settings', 'ai-text2image-providers')
const taskProcessingProviders = loadState<{ id: string, name: string, taskType: string }[]>('settings', 'ai-task-processing-providers')
const taskProcessingTaskTypes = loadState<TaskType[]>('settings', 'ai-task-processing-task-types')

const settings = reactive(loadState<AISettings>('settings', 'ai-settings'))
let savedSettings = JSON.stringify(settings)

const showLegacySections = ref(false)

// TRANSLATORS Switch that turns an AI task type on or off
const enableLabel = t('settings', 'Enable')
// TRANSLATORS Prefix of a heading, followed by the name of a text processing task
const taskPrefix = t('settings', 'Task:')

const hasTaskProcessing = Object.keys(settings['ai.taskprocessing_provider_preferences']).length > 0 && Array.isArray(taskProcessingTaskTypes)
const tpTaskTypes = computed(() => Object.keys(settings['ai.textprocessing_provider_preferences'])
	.filter((type) => !BUILTIN_TEXT_PROCESSING_TYPES.includes(type))
	.map((type) => getTextProcessingTaskType(type))
	.filter((taskType) => taskType !== undefined))

// useSortable applies the new order on the next tick, saveChanges waits for it
useSortable(useTemplateRef('translationList'), () => settings['ai.translation_provider_preferences'], {
	onEnd: saveChanges,
})

/**
 * @param i Index of the translation provider to move
 * @param delta -1 to move it up, 1 to move it down
 */
function move(i: number, delta: number) {
	const preferences = settings['ai.translation_provider_preferences']
	preferences.splice(Math.max(i + delta, 0), 0, ...preferences.splice(i, 1))
	saveChanges()
}

/**
 * @param providerClass Class or ID of the translation provider
 */
function translationProviderName(providerClass: string) {
	return translationProviders.find((p) => p.class === providerClass)?.name ?? ''
}

/**
 * Save all AI settings, restore the last saved state if that fails
 */
async function saveChanges() {
	await nextTick()
	try {
		await axios.put(generateUrl('/settings/api/admin/ai'), { settings })
		savedSettings = JSON.stringify(settings)
	} catch (error) {
		logger.error('Could not save the AI settings', { error })
		showError(t('settings', 'Could not save the AI settings'))
		Object.assign(settings, JSON.parse(savedSettings))
	}
}

/**
 * @param type The task processing task type
 */
function getTaskTypeName(type: TaskType) {
	if (type.id === 'core:text2text') {
		return t('settings', 'Generate text')
	}
	return type.name
}

/**
 * @param type Class name of the text processing task type
 */
function getTextProcessingTaskType(type: string) {
	if (!Array.isArray(textProcessingTaskTypes)) {
		return undefined
	}
	return textProcessingTaskTypes.find((taskType) => taskType.class === type)
}
</script>

<template>
	<div :class="$style.aiSettings">
		<NcSettingsSection
			:name="t('settings', 'Unified task processing')"
			:description="t('settings', 'AI tasks can be implemented by different apps. Here you can set which app should be used for which task.')">
			<NcCheckboxRadioSwitch
				v-model="settings['ai.taskprocessing_guests']"
				type="switch"
				@update:modelValue="saveChanges">
				{{ t('settings', 'Allow AI usage for guest users') }}
			</NcCheckboxRadioSwitch>
			<h3>{{ t('settings', 'Provider for Task types') }}</h3>
			<div v-for="type in taskProcessingTaskTypes" :key="type.id" :class="$style.taskTypeItem">
				<p :class="$style.taskTypeName">
					{{ getTaskTypeName(type) }}
				</p>
				<NcCheckboxRadioSwitch
					v-model="settings['ai.taskprocessing_type_preferences'][type.id]"
					type="switch"
					@update:modelValue="saveChanges">
					{{ enableLabel }}
				</NcCheckboxRadioSwitch>
				<NcSelect
					v-model="settings['ai.taskprocessing_provider_preferences'][type.id]"
					:class="$style.providerSelect"
					:clearable="false"
					:disabled="!settings['ai.taskprocessing_type_preferences'][type.id]"
					:options="taskProcessingProviders.filter(p => p.taskType === type.id).map(p => p.id)"
					@update:modelValue="saveChanges">
					<template #option="{ label }">
						{{ taskProcessingProviders.find(p => p.id === label)?.name }}
					</template>
					<template #selected-option="{ label }">
						{{ taskProcessingProviders.find(p => p.id === label)?.name }}
					</template>
				</NcSelect>
			</div>
			<NcNoteCard v-if="!hasTaskProcessing" type="info">
				{{ t('settings', 'None of your currently installed apps provide Task processing functionality') }}
			</NcNoteCard>
		</NcSettingsSection>
		<NcButton :class="$style.legacyToggle" variant="tertiary" @click="showLegacySections = !showLegacySections">
			<template #icon>
				<NcIconSvgWrapper :path="showLegacySections ? mdiChevronUp : mdiChevronDown" />
			</template>
			{{ showLegacySections ? t('settings', 'Hide legacy provider settings') : t('settings', 'Show legacy provider settings') }}
		</NcButton>
		<div v-show="showLegacySections">
			<NcSettingsSection
				:name="t('settings', 'Machine translation (Legacy)')"
				:description="t('settings', 'Machine translation can be implemented by different apps. Here you can define the precedence of the machine translation apps you have installed at the moment.')">
				<NcNoteCard type="warning">
					{{ t('settings', 'This API is deprecated. Install apps that provide Unified task processing to get the best experience.') }}
					<a href="https://docs.nextcloud.com/server/latest/admin_manual/ai/overview.html" target="_blank" rel="noopener noreferrer">{{ t('settings', 'Learn more about AI providers') }}</a>
				</NcNoteCard>
				<div ref="translationList">
					<div v-for="(providerClass, i) in settings['ai.translation_provider_preferences']" :key="providerClass" :class="$style.draggableItem">
						<NcIconSvgWrapper :path="mdiDragVertical" />
						<span :class="$style.draggableNumber">{{ i + 1 }}</span>
						{{ translationProviderName(providerClass) }}
						<NcButton :aria-label="t('settings', 'Move {provider} up', { provider: translationProviderName(providerClass) })" variant="tertiary" @click="move(i, -1)">
							<template #icon>
								<NcIconSvgWrapper :path="mdiArrowUp" />
							</template>
						</NcButton>
						<NcButton :aria-label="t('settings', 'Move {provider} down', { provider: translationProviderName(providerClass) })" variant="tertiary" @click="move(i, 1)">
							<template #icon>
								<NcIconSvgWrapper :path="mdiArrowDown" />
							</template>
						</NcButton>
					</div>
				</div>
			</NcSettingsSection>
			<NcSettingsSection
				:name="t('settings', 'Image generation (Legacy)')"
				:description="t('settings', 'Image generation can be implemented by different apps. Here you can set which app should be used.')">
				<NcNoteCard type="warning">
					{{ t('settings', 'This API is deprecated. Install apps that provide Unified task processing to get the best experience.') }}
					<a href="https://docs.nextcloud.com/server/latest/admin_manual/ai/overview.html" target="_blank" rel="noopener noreferrer">{{ t('settings', 'Learn more about AI providers') }}</a>
				</NcNoteCard>
				<NcCheckboxRadioSwitch
					v-for="provider in text2imageProviders"
					:key="provider.id"
					v-model="settings['ai.text2image_provider']"
					:value="provider.id"
					name="text2image_provider"
					type="radio"
					@update:modelValue="saveChanges">
					{{ provider.name }}
				</NcCheckboxRadioSwitch>
				<NcNoteCard v-if="text2imageProviders.length === 0" type="info">
					{{ t('settings', 'None of your currently installed apps provide image generation functionality') }}
				</NcNoteCard>
			</NcSettingsSection>
			<NcSettingsSection
				:name="t('settings', 'Text processing (Legacy)')"
				:description="t('settings', 'Text processing tasks can be implemented by different apps. Here you can set which app should be used for which task.')">
				<NcNoteCard type="warning">
					{{ t('settings', 'This API is deprecated. Install apps that provide Unified task processing to get the best experience.') }}
					<a href="https://docs.nextcloud.com/server/latest/admin_manual/ai/overview.html" target="_blank" rel="noopener noreferrer">{{ t('settings', 'Learn more about AI providers') }}</a>
				</NcNoteCard>
				<div v-for="type in tpTaskTypes" :key="type.class">
					<h3>{{ taskPrefix }} {{ type.name }}</h3>
					<p>{{ type.description }}</p>
					<p>&nbsp;</p>
					<NcSelect
						v-model="settings['ai.textprocessing_provider_preferences'][type.class]"
						:class="$style.providerSelect"
						:clearable="false"
						:options="textProcessingProviders.filter(p => p.taskType === type.class).map(p => p.class)"
						@update:modelValue="saveChanges">
						<template #option="{ label }">
							{{ textProcessingProviders.find(p => p.class === label)?.name }}
						</template>
						<template #selected-option="{ label }">
							{{ textProcessingProviders.find(p => p.class === label)?.name }}
						</template>
					</NcSelect>
					<p>&nbsp;</p>
				</div>
				<NcNoteCard v-if="tpTaskTypes.length === 0" type="info">
					<!-- TRANSLATORS Text processing is the name of a Nextcloud-internal API -->
					{{ t('settings', 'None of your currently installed apps provide text processing functionality using the Text Processing API.') }}
				</NcNoteCard>
			</NcSettingsSection>
		</div>
	</div>
</template>

<style module>
.aiSettings h3 {
	font-size: 16px; /* to offset against the 20px section heading */
}

.draggableItem {
	display: flex;
	align-items: center;
	margin-block-end: 5px;
}

.draggableItem,
.draggableItem * {
	cursor: grab;
}

.draggableNumber {
	border-radius: 20px;
	border: 2px solid var(--color-primary-element);
	color: var(--color-primary-element);
	padding: 0px 7px;
	margin-inline-end: 3px;
}

.providerSelect {
	min-width: 350px !important;
}

.legacyToggle {
	margin-block: calc(var(--default-grid-baseline) * 3);
}

.taskTypeItem {
	display: flex;
	align-items: center;
	gap: calc(var(--default-grid-baseline) * 2);
}

.taskTypeName {
	flex: 1;
	margin: 0;
}
</style>
