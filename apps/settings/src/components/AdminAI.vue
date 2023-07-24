<template>
	<NcSettingsSection :title="t('settings', 'Artificial Intelligence')"
		:description="t('settings', 'Artificial Intelligence features can be implemented by different apps. Here you can set which app should be used for which features.')">
		<h3>{{ t('settings', 'Translations') }}</h3>
		<h3>{{ t('settings', 'Speech-To-Text') }}</h3>
		<template v-for="provider in sttProviders">
			<NcCheckboxRadioSwitch :key="provider.class"
				:checked.sync="settings['ai.stt_provider']"
				:value="provider.class"
				name="stt_provider"
				type="radio">{{ provider.name }}</NcCheckboxRadioSwitch>
		</template>
		<template v-if="sttProviders.length === 0">
	  	<NcCheckboxRadioSwitch disabled type="radio">{{ t('settings', 'No apps are currently installed that provide Speech-To-Text functionality') }}</NcCheckboxRadioSwitch>
		</template>
		<h3>{{ t('settings', 'Text processing') }}</h3>
		<template v-for="(type, provider) in settings['ai.textprocessing_provider_preferences']">
			<h4>{{ type }}</h4>
			<!--<p>{{ getTaskType(type).description }}</p>
			<NcSelect v-model="settings['ai.textprocessing_provider_preferences'][type]" :options="textProcessingProviders.filter(provider => provider.taskType === type)" />-->
		</template>
		<template v-if="Object.keys(settings['ai.textprocessing_provider_preferences']).length === 0 || !Array.isArray(this.textProcessingTaskTypes)">
			<p>{{ t('settings', 'No apps are currently installed that provide Text processing functionality') }}</p>
		</template>
	</NcSettingsSection>
</template>

<script>
import axios from '@nextcloud/axios'
import NcCheckboxRadioSwitch from '@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.js'
import NcSettingsSection from '@nextcloud/vue/dist/Components/NcSettingsSection.js'
import { loadState } from '@nextcloud/initial-state'

import { generateUrl } from '@nextcloud/router'

export default {
	name: 'AdminAI',
	components: {
		NcCheckboxRadioSwitch,
		NcSettingsSection,
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
		saveChanges() {
			this.loading = true

			const data = {
				enforced: this.enforced,
				enforcedGroups: this.enforcedGroups,
				excludedGroups: this.excludedGroups,
			}
			axios.put(generateUrl('/settings/api/admin/twofactorauth'), data)
				.then(resp => resp.data)
				.then(state => {
					this.state = state
					this.dirty = false
				})
				.catch(err => {
					console.error('could not save changes', err)
				})
				.then(() => { this.loading = false })
		},
		getTaskType(type) {
		  if (!Array.isArray(this.textProcessingTaskTypes)) {
				return null
			}
			return this.textProcessingTaskTypes.find(taskType => taskType === type)
		}
	},
}
</script>
