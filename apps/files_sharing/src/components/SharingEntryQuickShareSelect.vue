<template>
	<div :class="{ 'active': showDropdown, 'share-select': true }">
		<span class="trigger-text" @click="toggleDropdown">
			{{ selectedOption }}
			<DropdownIcon :size="15" />
		</span>
		<div v-if="showDropdown" class="share-select-dropdown-container">
			<div v-for="option in options"
				:key="option"
				:class="{ 'dropdown-item': true, 'selected': option === selectedOption }"
				@click="selectOption(option)">
				{{ option }}
			</div>
		</div>
	</div>
</template>

<script>
import DropdownIcon from 'vue-material-design-icons/TriangleSmallDown.vue'
import SharesMixin from '../mixins/SharesMixin.js'
import ShareDetails from '../mixins/ShareDetails.js'
import ShareTypes from '../mixins/ShareTypes.js'

import {
	BUNDLED_PERMISSIONS,
	ATOMIC_PERMISSIONS,
} from '../lib/SharePermissionsToolBox.js'

export default {
	components: {
		DropdownIcon,
	},
	mixins: [SharesMixin, ShareDetails, ShareTypes],
	props: {
		share: {
			type: Object,
			required: true,
		},
		toggle: {
			type: Boolean,
			default: false,
		},
	},
	data() {
		return {
			selectedOption: '',
			showDropdown: this.toggle,
		}
	},
	computed: {
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
			const options = [this.canViewText, this.canEditText]
			if (this.supportsFileDrop) {
				options.push(this.fileDropText)
			}
			options.push(this.customPermissionsText)

			return options
		},
		supportsFileDrop() {
			if (this.isFolder) {
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
	watch: {
		toggle(toggleValue) {
			this.showDropdown = toggleValue
		},
	},
	mounted() {
		this.initializeComponent()
	},
	methods: {
		toggleDropdown() {
			this.showDropdown = !this.showDropdown
		},
		selectOption(option) {
			this.selectedOption = option
			if (option === this.customPermissionsText) {
				this.$emit('open-sharing-details')
			} else {
				this.share.permissions = this.dropDownPermissionValue
				this.queueUpdate('permissions')
			}
			this.showDropdown = false
		},
		initializeComponent() {
			this.selectedOption = this.preSelectedOption
		},
	},

}
</script>

<style lang="scss" scoped>
.share-select {
	position: relative;
	cursor: pointer;

	.trigger-text {
		display: flex;
		flex-direction: row;
		align-items: center;
		font-size: 12.5px;
		gap: 2px;
		color: var(--color-primary-element);
	}

	.share-select-dropdown-container {
		position: absolute;
		top: 100%;
		left: 0;
		background-color: var(--color-main-background);
		border-radius: 8px;
		box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
		padding: 4px 0;
		z-index: 1;

		.dropdown-item {
			padding: 8px;
			font-size: 12px;

			&:hover {
				background-color: #f2f2f2;
			}

			&.selected {
				background-color: #f0f0f0;
			}
		}
	}

	/* Optional: Add a transition effect for smoother dropdown animation */
	.share-select-dropdown-container {
		max-height: 0;
		overflow: hidden;
		transition: max-height 0.3s ease;
	}

	&.active .share-select-dropdown-container {
		max-height: 200px;
		/* Adjust the value to your desired height */
	}
}
</style>
