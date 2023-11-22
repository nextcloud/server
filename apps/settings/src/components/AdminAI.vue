<template>
	<div>
		<NcSettingsSection :name="t('settings', 'Machine translation')"
			:description="t('settings', 'Machine translation can be implemented by different apps. Here you can define the precedence of the machine translation apps you have installed at the moment.')">
			<draggable v-model="settings['ai.translation_provider_preferences']" @change="saveChanges">
				<div v-for="(providerClass, i) in settings['ai.translation_provider_preferences']" :key="providerClass" class="draggable__item">
					<DragVerticalIcon /> <span class="draggable__number">{{ i + 1 }}</span> {{ translationProviders.find(p => p.class === providerClass)?.name }}
					<NcButton aria-label="Move up" type="tertiary" @click="moveUp(i)">
						<template #icon>
							<ArrowUpIcon />
						</template>
					</NcButton>
					<NcButton aria-label="Move down" type="tertiary" @click="moveDown(i)">
						<template #icon>
							<ArrowDownIcon />
						</template>
					</NcButton>
				</div>
			</draggable>
		</NcSettingsSection>
		<NcSettingsSection :name="t('settings', 'Speech-To-Text')"
			:description="t('settings', 'Speech-To-Text can be implemented by different apps. Here you can set which app should be used.')">
			<template v-for="provider in sttProviders">
				<NcCheckboxRadioSwitch :key="provider.class"
					:checked.sync="settings['ai.stt_provider']"
					:value="provider.class"
					name="stt_provider"
					type="radio"
					@update:checked="saveChanges">
					{{ provider.name }}
				</NcCheckboxRadioSwitch>
			</template>
			<template v-if="!hasStt">
				<NcCheckboxRadioSwitch disabled type="radio">
					{{ t('settings', 'None of your currently installed apps provide Speech-To-Text functionality') }}
				</NcCheckboxRadioSwitch>
			</template>
		</NcSettingsSection>
		<NcSettingsSection :name="t('settings', 'Image generation')"
			:description="t('settings', 'Image generation can be implemented by different apps. Here you can set which app should be used.')">
			<template v-for="provider in text2imageProviders">
				<NcCheckboxRadioSwitch :key="provider.id"
					:checked.sync="settings['ai.text2image_provider']"
					:value="provider.id"
					name="text2image_provider"
					type="radio"
					@update:checked="saveChanges">
					{{ provider.name }}
				</NcCheckboxRadioSwitch>
			</template>
			<template v-if="!hasText2ImageProviders">
				<NcCheckboxRadioSwitch disabled type="radio">
					{{ t('settings', 'None of your currently installed apps provide image generation functionality') }}
				</NcCheckboxRadioSwitch>
			</template>
		</NcSettingsSection>
		<NcSettingsSection :name="t('settings', 'Text processing')"
			:description="t('settings', 'Text processing tasks can be implemented by different apps. Here you can set which app should be used for which task.')">
			<template v-for="type in tpTaskTypes">
				<div :key="type">
					<h3>{{ t('settings', 'Task:') }} {{ getTaskType(type).name }}</h3>
					<p>{{ getTaskType(type).description }}</p>
					<p>&nbsp;</p>
					<NcSelect v-model="settings['ai.textprocessing_provider_preferences'][type]"
						:clearable="false"
						:options="textProcessingProviders.filter(p => p.taskType === type).map(p => p.class)"
						@input="saveChanges">
						<template #option="{label}">
							{{ textProcessingProviders.find(p => p.class === label)?.name }}
						</template>
						<template #selected-option="{label}">
							{{ textProcessingProviders.find(p => p.class === label)?.name }}
						</template>
					</NcSelect>
					<p>&nbsp;</p>
				</div>
			</template>
			<template v-if="!hasTextProcessing">
				<p>{{ t('settings', 'None of your currently installed apps provide Text processing functionality') }}</p>
			</template>
		</NcSettingsSection>
	</div>
</template>

<script>
import axios from '@nextcloud/axios'
import NcCheckboxRadioSwitch from '@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.js'
import NcSettingsSection from '@nextcloud/vue/dist/Components/NcSettingsSection.js'
import NcSelect from '@nextcloud/vue/dist/Components/NcSelect.js'
import NcButton from '@nextcloud/vue/dist/Components/NcButton.js'
import draggable from 'vuedraggable'
import DragVerticalIcon from 'vue-material-design-icons/DragVertical.vue'
import ArrowDownIcon from 'vue-material-design-icons/ArrowDown.vue'
import ArrowUpIcon from 'vue-material-design-icons/ArrowUp.vue'
import { loadState } from '@nextcloud/initial-state'

import { generateUrl } from '@nextcloud/router'

export default {
	name: 'AdminAI',
	components: {
		NcCheckboxRadioSwitch,
		NcSettingsSection,
		NcSelect,
		draggable,
		DragVerticalIcon,
		ArrowDownIcon,
		ArrowUpIcon,
		NcButton,
	},
	data() {
		return {
			loading: false,
			dirty: false,
			groups: [],
			loadingGroups: false,
			sttProviders: loadState('settings', 'ai-stt-providers'),
			translationProviders: loadState('settings', 'ai-translation-providers'),
			textProcessingProviders: loadState('settings', 'ai-text-processing-providers'),
			textProcessingTaskTypes: loadState('settings', 'ai-text-processing-task-types'),
			text2imageProviders: loadState('settings', 'ai-text2image-providers'),
			settings: loadState('settings', 'ai-settings'),
		}
	},
	computed: {
		hasStt() {
			return this.sttProviders.length > 0
		},
		hasTextProcessing() {
			return Object.keys(this.settings['ai.textprocessing_provider_preferences']).length > 0 && Array.isArray(this.textProcessingTaskTypes)
		},
		tpTaskTypes() {
			return Object.keys(this.settings['ai.textprocessing_provider_preferences']).filter(type => !!this.getTaskType(type))
		},
		hasText2ImageProviders() {
		  return this.text2imageProviders.length > 0
		},
	},
	methods: {
	  moveUp(i) {
			this.settings['ai.translation_provider_preferences'].splice(
			  Math.min(i - 1, 0),
				0,
				...this.settings['ai.translation_provider_preferences'].splice(i, 1),
			)
			this.saveChanges()
		},
		moveDown(i) {
			this.settings['ai.translation_provider_preferences'].splice(
				i + 1,
				0,
				...this.settings['ai.translation_provider_preferences'].splice(i, 1),
			)
			this.saveChanges()
		},
		async saveChanges() {
			this.loading = true
			const data = { settings: this.settings }
			try {
				await axios.put(generateUrl('/settings/api/admin/ai'), data)
			} catch (err) {
				console.error('could not save changes', err)
			}
			this.loading = false
		},
		getTaskType(type) {
		  if (!Array.isArray(this.textProcessingTaskTypes)) {
				return null
			}
			return this.textProcessingTaskTypes.find(taskType => taskType.class === type)
		},
	},
}
</script>
<style scoped>
.draggable__item {
	margin-bottom: 5px;
  display: flex;
  align-items: center;
}

.draggable__item,
.draggable__item * {
  cursor: grab;
}

.draggable__number {
	border-radius: 20px;
	border: 2px solid var(--color-primary-default);
	color: var(--color-primary-default);
  padding: 0px 7px;
	margin-right: 3px;
}

.drag-vertical-icon {
  float: left;
}
</style>
