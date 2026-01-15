<!--
  - SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->

<template>
	<div class="profile-reference">
		<div class="profile-reference__wrapper">
			<div class="profile-reference__wrapper__header">
				<NcAvatar :user="richObject.user_id" :size="48" class="profile-card__avatar" />
				<div class="profile-card__title">
					<a :href="richObject.url" target="_blank">
						<AccountOutline :size="20" />
						<strong>
							AAA {{ richObject.email !== null ? richObject.title + ' - ' + richObject.email : richObject.title }}
						</strong>
					</a>
				</div>
			</div>
			<div class="profile-content">
				<p class="profile-content__subline">
					<span v-if="richObject.headline" class="headline">
						{{ richObject.headline }}
					</span>
					<span v-if="richObject.location" class="location">
						<MapMarkerOutline :size="20" />
						<template v-if="richObject.location_url">
							<a :href="richObject.location_url" class="external" target="_blank">{{ richObject.location }}</a>
						</template>
						<template v-else>
							{{ richObject.location }}
						</template>
					</span>
					<span v-if="richObject.website" class="website">
						<Web :size="20" />
						<a :href="richObject.website" class="external" target="_blank">{{ richObject.website }}</a>
					</span>
					<span v-if="richObject.organisation" class="organisation">
						<Domain :size="20" />
						{{ richObject.organisation }}
					</span>
					<span v-if="richObject.role" class="role">
						<HandshakeOutline :size="20" />
						{{ richObject.role }}
					</span>
					<span
						v-if="richObject.bio"
						class="bio"
						:title="richObject.full_bio">
						<TextAccount :size="20" />
						{{ richObject.bio }}
					</span>
				</p>
			</div>
		</div>
	</div>
</template>

<script>
import NcAvatar from '@nextcloud/vue/components/NcAvatar'
import AccountOutline from 'vue-material-design-icons/AccountOutline.vue'
import Domain from 'vue-material-design-icons/Domain.vue'
import HandshakeOutline from 'vue-material-design-icons/HandshakeOutline.vue'
import MapMarkerOutline from 'vue-material-design-icons/MapMarkerOutline.vue'
import TextAccount from 'vue-material-design-icons/TextAccount.vue'
import Web from 'vue-material-design-icons/Web.vue'
import { logger } from '../utils/logger.ts'

export default {
	name: 'ProfilePickerReferenceWidget',
	components: {
		NcAvatar,
		AccountOutline,
		MapMarkerOutline,
		Web,
		Domain,
		HandshakeOutline,
		TextAccount,
	},

	props: {
		richObjectType: {
			type: String,
			default: '',
		},

		richObject: {
			type: Object,
			default: null,
		},

		accessible: {
			type: Boolean,
			default: true,
		},
	},

	beforeMount() {
		logger.debug('ProfilePickerReferenceWidget', this.richObject)
	},
}
</script>

<style scoped lang="scss">
.profile-reference {
	width: 100%;
	white-space: normal;
	display: flex;

	&__wrapper {
		width: 100%;
		display: flex;
		align-items: center;
		flex-direction: column;

		&__header {
			width: 100%;
			min-height: 70px;
			padding: 0 12px;
			background-color: var(--color-primary);
			background-image: var(--gradient-primary-background);
			position: relative;
			display: flex;
			align-items: center;
			gap: 10px;
		}

		.profile-card__title a {
			display: flex;
			align-items: center;
			gap: 5px;
			color: var(--color-primary-text);
		}

		.profile-content {
			display: flex;
			flex-direction: column;
			justify-content: center;
			min-height: 46px;
			padding: 10px 10px 10px 60px;
			width: 100%;
		}

		.headline {
			font-style: italic;
			padding-left: 5px;
		}

		.profile-content__subline {
			padding: 0 0 0 10px;

			& span.material-design-icon {
				margin-right: 5px;
			}

			& > span {
				display: flex;
				align-items: center;
				margin-bottom: 5px;
			}
		}
	}
}
</style>
