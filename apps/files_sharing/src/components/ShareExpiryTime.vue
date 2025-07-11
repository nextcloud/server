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
					:aria-label="t('files_sharing', 'Share expiration: {date}', { date: new Date(expiryTime).toLocaleString() })">
					<template #icon>
						<ClockIcon :size="20" />
					</template>
				</NcButton>
			</template>
			<h3 class="hint-heading">
				{{ t('files_sharing', 'Share Expiration') }}
			</h3>
			<p v-if="expiryTime" class="hint-body">
				<NcDateTime :timestamp="expiryTime"
					:format="timeFormat"
					:relative-time="false" /> (<NcDateTime :timestamp="expiryTime" />)
			</p>
		</NcPopover>
	</div>
</template>

<script>
import NcButton from '@nextcloud/vue/components/NcButton'
import NcPopover from '@nextcloud/vue/components/NcPopover'
import NcDateTime from '@nextcloud/vue/components/NcDateTime'
import ClockIcon from 'vue-material-design-icons/Clock.vue'

export default {
	name: 'ShareExpiryTime',

	components: {
		NcButton,
		NcPopover,
		NcDateTime,
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
			return this.share?.expireDate ? new Date(this.share.expireDate).getTime() : null
		},
		timeFormat() {
			return { dateStyle: 'full', timeStyle: 'short' }
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

.hint-heading {
    text-align: center;
    font-size: 1rem;
    margin-top: 8px;
    padding-bottom: 8px;
    margin-bottom: 0;
    border-bottom: 1px solid var(--color-border);
}

.hint-body {
    padding: var(--border-radius-element);
    max-width: 300px;
}
</style>
