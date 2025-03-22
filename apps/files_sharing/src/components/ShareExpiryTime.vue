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
					type="tertiary-no-background"
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
import { getLocale } from '@nextcloud/l10n'
import dayjs from 'dayjs'
import relativeTime from 'dayjs/plugin/relativeTime'
import utc from 'dayjs/plugin/utc'
import timezone from 'dayjs/plugin/timezone'
import localizedFormat from 'dayjs/plugin/localizedFormat'

dayjs.extend(relativeTime)
dayjs.extend(utc)
dayjs.extend(timezone)
dayjs.extend(localizedFormat)

/**
 *
 * @param locale
 */
async function loadLocale(locale) {
	try {
		await import(`dayjs/locale/${locale}.js`)
		dayjs.locale(locale)
	} catch (error) {
		console.warn(`Locale ${locale} not found, falling back to English`)
		dayjs.locale('en')
	}
}

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
			return this.share?.expireDate ? dayjs(this.share.expireDate) : null
		},

		formattedExpiry() {
			if (!this.expiryTime) {
				return ''
			}

			const userTimezone = Intl.DateTimeFormat().resolvedOptions().timeZone
			const formattedDate = this.expiryTime.tz(userTimezone).locale(getLocale()).format('LLLL')
			const relativeTime = this.expiryTime.tz(userTimezone).fromNow()

			return this.t('files_sharing', 'Share expires on {datetime} ({relative})', {
				datetime: formattedDate,
				relative: relativeTime,
			})
		},
	},

	created() {
		loadLocale(getLocale())
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
