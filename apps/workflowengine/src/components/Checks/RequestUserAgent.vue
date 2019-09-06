<!--
  - @copyright Copyright (c) 2019 Julius Härtl <jus@bitgrid.net>
  -
  - @author Julius Härtl <jus@bitgrid.net>
  -
  - @license GNU AGPL version 3 or any later version
  -
  - This program is free software: you can redistribute it and/or modify
  - it under the terms of the GNU Affero General Public License as
  - published by the Free Software Foundation, either version 3 of the
  - License, or (at your option) any later version.
  -
  - This program is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  - GNU Affero General Public License for more details.
  -
  - You should have received a copy of the GNU Affero General Public License
  - along with this program. If not, see <http://www.gnu.org/licenses/>.
  -
  -->

<template>
	<div>

		<multiselect
			:value="currentValue"
			placeholder="Select a file type"
			label="label"
			track-by="pattern"
			group-values="children",
			group-label="text",
			:options="options" :multiple="false" :tagging="false" @input="setValue">
			<template slot="singleLabel" slot-scope="props">
				<span class="option__icon" :class="props.option.icon"></span>
				<span class="option__title option__title_single">{{ props.option.label }}</span>
			</template>
			<template slot="option" slot-scope="props">
				<span class="option__icon" :class="props.option.icon"></span>
				<span class="option__title">{{ props.option.label }}</span>
			</template>
		</multiselect>
		<input type="text" :value="currentValue.pattern" @input="updateCustom"/>
	</div>
</template>

<script>
import { Multiselect } from 'nextcloud-vue/dist/Components/Multiselect'

export default {
	name: 'UserAgent',
	components: {
		Multiselect
	},
	data() {
		return {
			value: '',
			predefinedTypes: [
				{
					text: t('workflowengine', 'Sync clients'),
					children: [
						{ id: 'android', text: t('workflowengine', 'Android client') },
						{ id: 'ios', text: t('workflowengine', 'iOS client') },
						{ id: 'desktop', text: t('workflowengine', 'Desktop client') },
						{ id: 'mail', text: t('workflowengine', 'Thunderbird & Outlook addons') }
					]
				}
			]
		}
	},
	computed: {
		options() {
			return [...this.predefinedTypes, this.customValue]
		},
		customValue() {
			const matchingPredefined = this.predefinedTypes.find((type) => this.value.pattern === type.pattern)
			return {
				icon: 'icon-settings-dark',
				label: t('workflowengine', 'Custom pattern'),
				pattern: '',
			}
		},
		currentValue() {
			const matchingPredefined = this.predefinedTypes.find((type) => this.value === type.pattern)
			if (matchingPredefined) {
				return matchingPredefined
			}
			return {
				icon: 'icon-settings-dark',
				label: t('workflowengine', 'Custom pattern'),
				pattern: this.value,
			}
		}
	},
	methods: {
		validateRegex(string) {
			var regexRegex = /^\/(.*)\/([gui]{0,3})$/
			var result = regexRegex.exec(string)
			return result !== null
		},
		setValue (value) {
			// TODO: check if value requires a regex and set the check operator according to that
			if (value !== null) {
				this.value = value.pattern
			}
		},
		updateCustom (event) {
			console.log(event)
			this.value = event.target.value
		}
	}
}
</script>

<style scoped>
	.multiselect::v-deep .multiselect__single {
		display: flex;
	}
	input, .multiselect {
		width: 100%;
	}
</style>
