<!--
	- @copyright 2021, Christopher Ng <chrng8@gmail.com>
	-
	- @author Christopher Ng <chrng8@gmail.com>
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
	- MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	- GNU Affero General Public License for more details.
	-
	- You should have received a copy of the GNU Affero General Public License
	- along with this program. If not, see <http://www.gnu.org/licenses/>.
	-
-->

<template>
	<a class="preview-card"
		:class="{ disabled }"
		:href="profilePageLink">
		<Avatar class="preview-card__avatar"
			:user="userId"
			:size="48"
			:show-user-status="true"
			:show-user-status-compact="false"
			:disable-menu="true"
			:disable-tooltip="true" />
		<div class="preview-card__header">
			<span>{{ displayName }}</span>
		</div>
		<div class="preview-card__footer">
			<span>{{ organisation }}</span>
		</div>
	</a>
</template>

<script>
import { getCurrentUser } from '@nextcloud/auth'
import { generateUrl } from '@nextcloud/router'

import Avatar from '@nextcloud/vue/dist/Components/Avatar'

export default {
	name: 'ProfilePreviewCard',

	components: {
		Avatar,
	},

	props: {
		displayName: {
			type: String,
			required: true,
		},
		organisation: {
			type: String,
			required: true,
		},
		profileEnabled: {
			type: Boolean,
			required: true,
		},
		userId: {
			type: String,
			required: true,
		},
	},

	computed: {
		disabled() {
			return !this.profileEnabled
		},

		profilePageLink() {
			if (this.profileEnabled) {
				return generateUrl('/u/{userId}', { userId: getCurrentUser().uid })
			}
			// Since an anchor element is used rather than a button for better UX,
			// this hack removes href if the profile is disabled so that disabling pointer-events is not needed to prevent a click from opening a page
			// and to allow the hover event (which disabling pointer-events wouldn't allow) for styling
			return null
		},
	},
}
</script>

<style lang="scss" scoped>
.preview-card {
	display: flex;
	flex-direction: column;
	position: relative;
	width: 290px;
	height: 116px;
	margin: 14px auto;
	border-radius: var(--border-radius-large);
	background-color: var(--color-main-background);
	font-weight: bold;
	box-shadow: 0 2px 9px var(--color-box-shadow);

	&:hover,
	&:focus,
	&:active {
		box-shadow: 0 2px 12px var(--color-box-shadow);
	}

	&:focus-visible {
		outline: var(--color-main-text) solid 1px;
		outline-offset: 3px;
	}

	&.disabled {
		filter: grayscale(1);
		opacity: 0.5;
		cursor: default;
		box-shadow: 0 0 3px var(--color-box-shadow);

		& *,
		&::v-deep * {
			cursor: default;
		}
	}

	&__avatar {
		// Override Avatar component position to fix positioning on rerender
		position: absolute !important;
		top: 40px;
		left: 18px;
		z-index: 1;

		&:not(.avatardiv--unknown) {
			box-shadow: 0 0 0 3px var(--color-main-background) !important;
		}
	}

	&__header,
	&__footer {
		position: relative;
		width: auto;

		span {
			position: absolute;
			left: 78px;
			overflow: hidden;
			text-overflow: ellipsis;
			word-break: break-all;

			@supports (-webkit-line-clamp: 2) {
				display: -webkit-box;
				-webkit-line-clamp: 2;
				-webkit-box-orient: vertical;
			}
		}
	}

	&__header {
		height: 70px;
		border-radius: var(--border-radius-large) var(--border-radius-large) 0 0;
		background-color: var(--color-primary);
		background-image: linear-gradient(40deg, var(--color-primary) 0%, var(--color-primary-element-light) 100%);

		span {
			bottom: 0;
			color: var(--color-primary-text);
			font-size: 18px;
			font-weight: bold;
			margin: 0 4px 8px 0;
		}
	}

	&__footer {
		height: 46px;

		span {
			top: 0;
			color: var(--color-text-maxcontrast);
			font-size: 14px;
			font-weight: normal;
			margin: 4px 4px 0 0;
			line-height: 1.3;
		}
	}
}
</style>
