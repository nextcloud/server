<template>
	<div>
		<NcSettingsSection :title="t('settings', 'Machine translation')"
			:description="t('settings', 'Machine translation can be implemented by different apps. Here you can define the precedence of the machine translation apps you have installed at the moment.')">
			<draggable v-model="settings['ai.translation_provider_preferences']" @change="saveChanges">
				<div v-for="(providerClass, i) in settings['ai.translation_provider_preferences']" :key="providerClass" class="draggable__item">
					<DragVerticalIcon /> <span class="draggable__number">{{ i+1 }}</span> {{ translationProviders.find(p => p.class === providerClass)?.name }}
				</div>
			</draggable>
		</NcSettingsSection>
		<NcSettingsSection :title="t('settings', 'Speech-To-Text')"
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
			<template v-if="sttProviders.length === 0">
				<NcCheckboxRadioSwitch disabled type="radio">
					{{ t('settings', 'No apps are currently installed that provide Speech-To-Text functionality') }}
				</NcCheckboxRadioSwitch>
			</template>
		</NcSettingsSection>
		<NcSettingsSection :title="t('settings', 'Text processing')"
			:description="t('settings', 'Text processing tasks can be implemented by different apps. Here you can set which app should be used for which task.')">
			<template v-for="type in Object.keys(settings['ai.textprocessing_provider_preferences'])">
				<div :key="type">
					<h3>{{ t('settings', 'Task:') }} {{ getTaskType(type).name }}</h3>
					<p>{{ getTaskType(type).description }}</p>
					<p>&nbsp;</p>
					<NcSelect v-model="settings['ai.textprocessing_provider_preferences'][type]"
						:clearable="false"
						:options="textProcessingProviders.filter(p => p.taskType === type).map(p => p.class)"
						@change="saveChanges">
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
			<template v-if="Object.keys(settings['ai.textprocessing_provider_preferences']).length === 0 || !Array.isArray(textProcessingTaskTypes)">
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
import draggable from 'vuedraggable'
import DragVerticalIcon from 'vue-material-design-icons/DragVertical.vue'
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
			settings: loadState('settings', 'ai-settings'),
		}
	},
	methods: {
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
}

.draggable__item,
.draggable__item * {
  cursor: grab;
}

.draggable__number {
	border-radius: 20px;
	border: 2px solid var(--color-primary-default);
	color: var(--color-primary-default);
  padding: 2px 7px;
}

.drag-vertical-icon {
  float: left;
}
</style>
