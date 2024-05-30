<template>
	<NcActions ref="quickShareActions"
		class="share-select"
		:menu-name="selectedOption"
		:aria-label="ariaLabel"
		type="tertiary-no-background"
		force-name>
		<template #icon>
			<DropdownIcon :size="15" />
		</template>
		<NcActionButton v-for="option in options"
			:key="option.label"
			type="radio"
			:model-value="option.label === selectedOption"
			close-after-click
			@click="selectOption(option.label)">
			<template #icon>
				<component :is="option.icon" />
			</template>
			{{ option.label }}
		</NcActionButton>
	</NcActions>
</template>

<script>
import DropdownIcon from 'vue-material-design-icons/TriangleSmallDown.vue'
import SharesMixin from '../mixins/SharesMixin.js'
import ShareDetails from '../mixins/ShareDetails.js'
import ShareTypes from '../mixins/ShareTypes.js'
import NcActions from '@nextcloud/vue/dist/Components/NcActions.js'
import NcActionButton from '@nextcloud/vue/dist/Components/NcActionButton.js'
import IconEyeOutline from 'vue-material-design-icons/EyeOutline.vue'
import IconPencil from 'vue-material-design-icons/Pencil.vue'
import IconFileUpload from 'vue-material-design-icons/FileUpload.vue'
import IconTune from 'vue-material-design-icons/Tune.vue'

import {
	BUNDLED_PERMISSIONS,
	ATOMIC_PERMISSIONS,
} from '../lib/SharePermissionsToolBox.js'

export default {
	name: 'SharingEntryQuickShareSelect',

	components: {
		DropdownIcon,
		NcActions,
		NcActionButton,
	},

	mixins: [SharesMixin, ShareDetails, ShareTypes],

	props: {
		share: {
			type: Object,
			required: true,
		},
	},

	emits: ['open-sharing-details'],

	data() {
		return {
			selectedOption: '',
		}
	},

	computed: {
		ariaLabel() {
			return t('files_sharing', 'Quick share options, the current selected is "{selectedOption}"', { selectedOption: this.selectedOption })
		},
		canViewText() {
			return t('files_sharing', 'View only')
		},
		canEditText() {
			return t('files_sharing', 'Can edit')
		},
		fileDropText() {
			return t('files_sharing', 'File drop')
		},
		customPermissionsText() {
			return t('files_sharing', 'Custom permissions')
		},
		preSelectedOption() {
			// We remove the share permission for the comparison as it is not relevant for bundled permissions.
			if ((this.share.permissions & ~ATOMIC_PERMISSIONS.SHARE) === BUNDLED_PERMISSIONS.READ_ONLY) {
				return this.canViewText
			} else if (this.share.permissions === BUNDLED_PERMISSIONS.ALL || this.share.permissions === BUNDLED_PERMISSIONS.ALL_FILE) {
				return this.canEditText
			} else if ((this.share.permissions & ~ATOMIC_PERMISSIONS.SHARE) === BUNDLED_PERMISSIONS.FILE_DROP) {
				return this.fileDropText
			}

			return this.customPermissionsText

		},
		options() {
			const options = [{
				label: this.canViewText,
				icon: IconEyeOutline,
			}, {
				label: this.canEditText,
				icon: IconPencil,
			}]
			if (this.supportsFileDrop) {
				options.push({
					label: this.fileDropText,
					icon: IconFileUpload,
				})
			}
			options.push({
				label: this.customPermissionsText,
				icon: IconTune,
			})

			return options
		},
		supportsFileDrop() {
			if (this.isFolder && this.config.isPublicUploadEnabled) {
				const shareType = this.share.type ?? this.share.shareType
				return [this.SHARE_TYPES.SHARE_TYPE_LINK, this.SHARE_TYPES.SHARE_TYPE_EMAIL].includes(shareType)
			}
			return false
		},
		dropDownPermissionValue() {
			switch (this.selectedOption) {
			case this.canEditText:
				return this.isFolder ? BUNDLED_PERMISSIONS.ALL : BUNDLED_PERMISSIONS.ALL_FILE
			case this.fileDropText:
				return BUNDLED_PERMISSIONS.FILE_DROP
			case this.customPermissionsText:
				return 'custom'
			case this.canViewText:
			default:
				return BUNDLED_PERMISSIONS.READ_ONLY
			}
		},
	},

	created() {
		this.selectedOption = this.preSelectedOption
	},

	methods: {
		selectOption(optionLabel) {
			this.selectedOption = optionLabel
			if (optionLabel === this.customPermissionsText) {
				this.$emit('open-sharing-details')
			} else {
				this.share.permissions = this.dropDownPermissionValue
				this.queueUpdate('permissions')
				// TODO: Add a focus method to NcActions or configurable returnFocus enabling to NcActionButton with closeAfterClick
				this.$refs.quickShareActions.$refs.menuButton.$el.focus()
			}
		},
	},

}
</script>

<style lang="scss" scoped>
.share-select {
	display: block;

	// TODO: NcActions should have a slot for custom trigger button like NcPopover
	// Overrider NcActionms button to make it small
	:deep(.action-item__menutoggle) {
		color: var(--color-primary-element) !important;
		font-size: 12.5px !important;
		height: auto !important;
		min-height: auto !important;

		.button-vue__text {
			font-weight: normal !important;
		}

		.button-vue__icon {
			height: 24px !important;
			min-height: 24px !important;
			width: 24px !important;
			min-width: 24px !important;
		}

		.button-vue__wrapper {
			// Emulate NcButton's alignment=center-reverse
			flex-direction: row-reverse !important;
		}
	}
}
</style>
