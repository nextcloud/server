<!--
  - SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
<template>
	<div class="share-expiry-time">
		<NcPopover popup-role="dialog">
			<template #trigger>
				<NcButton v-if="expiryTime"
					class="hint-icon"
					type="tertiary"
					:aria-label="formattedExpiry">
					<template #icon>
						<ClockIcon :size="20" />
					</template>
				</NcButton>
			</template>
			<p v-if="expiryTime" class="hint-body">
				{{ formattedExpiry }}
			</p>
		</NcPopover>
	</div>
</template>

<script>
import NcButton from '@nextcloud/vue/components/NcButton'
import NcPopover from '@nextcloud/vue/components/NcPopover'
import ClockIcon from 'vue-material-design-icons/Clock.vue'

export default {
	name: 'ShareExpiryTime',

	components: {
		NcButton,
		NcPopover,
		ClockIcon,
	},

	props: {
		share: {
			type: Object,
			required: true,
		},
	},

	computed: {
		expiryTime() {
			return this.share?.expireDate || null
		},

		formattedExpiry() {
			return this.expiryTime
				? this.t('files_sharing', 'Share expires on {datetime}', { datetime: this.expiryTime })
				: ''
		},
	},
}
</script>

<style scoped lang="scss">
.share-expiry-time {
    display: inline-flex;
    align-items: center;
    justify-content: center;

    .hint-icon {
        padding: 0;
        margin: 0;
        width: 24px;
        height: 24px;
    }
}

.hint-body {
        padding: var(--border-radius-element);
        max-width: 300px;
    }
</style>
