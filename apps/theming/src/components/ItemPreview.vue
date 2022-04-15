<template>
	<div class="theming__preview">
		<div class="theming__preview-image" :style="{ backgroundImage: 'url(' + img + ')' }" />
		<div class="theming__preview-description">
			<h3>{{ theme.title }}</h3>
			<p>{{ theme.description }}</p>
			<CheckboxRadioSwitch class="theming__preview-toggle"
				:checked.sync="checked"
				:name="name"
				:type="switchType">
				{{ theme.enableLabel }}
			</CheckboxRadioSwitch>
		</div>
	</div>
</template>

<script>
import { generateFilePath} from '@nextcloud/router'
import CheckboxRadioSwitch from '@nextcloud/vue/dist/Components/CheckboxRadioSwitch'

export default {
	name: 'ItemPreview',
	components: {
		CheckboxRadioSwitch,
	},
	props: {
		theme: {
			type: Object,
			required: true,
		},
		selected: {
			type: Boolean,
			default: false,
		},
		type: {
			type: String,
			default: '',
		},
		themes: {
			type: Array,
			default: () => [],
		},
	},
	computed: {
		switchType() {
			return this.themes.length === 1 ? 'switch' : 'radio'
		},

		name() {
			return this.switchType === 'radio' ? this.type : null
		},

		img() {
			return generateFilePath('theming', 'img', this.theme.id + '.jpg')
		},

		checked: {
			get() {
				return this.selected
			},
			set(checked) {
				console.debug('Selecting theme', this.theme, checked)

				// If this is a radio, we can only enable
				if (this.switchType === 'radio') {
					this.$emit('change', { enabled: true, id: this.theme.id })
					return
				}

				// If this is a switch, we can disable the theme
				this.$emit('change', { enabled: checked === true, id: this.theme.id })
			},
		},
	},
}
</script>
<style lang="scss" scoped>

.theming__preview {
	position: relative;
	display: flex;
	justify-content: flex-start;
	height: 140px;
	margin-top: 3em;

	&,
	* {
		user-select: none;
	}

	&-image {
		flex-basis: 200px;
		flex-shrink: 0;
		margin-right: 30px;
		border-radius: var(--border-radius);
		background-repeat: no-repeat;
		background-position: top left;
		background-size: cover;
	}

	&-description {
		display: flex;
		flex-direction: column;

		label {
			padding: 12px 0;
		}
	}
}

@media (max-width: (1024 / 2)) {
	.theming__preview {
		display: unset;

		&-image {
			height: 150px;
		}
	}
}

</style>
