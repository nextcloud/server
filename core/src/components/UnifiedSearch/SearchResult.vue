<template>
	<a :href="resourceUrl || '#'"
		class="unified-search__result"
		:class="{
			'unified-search__result--focused': focused
		}"
		@click="reEmitEvent"
		@focus="reEmitEvent">
		<!-- Icon describing the result -->
		<div class="unified-search__result-icon"
			:class="{
				'unified-search__result-icon--rounded': rounded,
				'unified-search__result-icon--no-preview': !hasValidThumbnail && !loaded,
				'unified-search__result-icon--with-thumbnail': hasValidThumbnail && loaded,
				[iconClass]: true
			}"
			role="img">
			<img v-if="hasValidThumbnail"
				:src="thumbnailUrl"
				:alt="t('core', 'Thumbnail for {result}', {result: title})"
				@error="onError"
				@load="onLoad">
		</div>

		<!-- Title and sub-title -->
		<span class="unified-search__result-content">
			<h3 class="unified-search__result-line-one">
				<Highlight :text="title" :search="query" />
			</h3>
			<h4 v-if="subline" class="unified-search__result-line-two">{{ subline }}</h4>
		</span>
	</a>
</template>

<script>
import Highlight from '@nextcloud/vue/dist/Components/Highlight'

export default {
	name: 'SearchResult',

	components: {
		Highlight,
	},

	props: {
		thumbnailUrl: {
			type: String,
			default: null,
		},
		title: {
			type: String,
			required: true,
		},
		subline: {
			type: String,
			default: null,
		},
		resourceUrl: {
			type: String,
			default: null,
		},
		iconClass: {
			type: String,
			default: '',
		},
		rounded: {
			type: Boolean,
			default: false,
		},
		query: {
			type: String,
			default: '',
		},

		/**
		 * Only used for the first result as a visual feedback
		 * so we can keep the search input focused but pressing
		 * enter still opens the first result
		 */
		focused: {
			type: Boolean,
			default: false,
		},
	},

	data() {
		return {
			hasValidThumbnail: this.thumbnailUrl && this.thumbnailUrl.trim() !== '',
			loaded: false,
		}
	},

	watch: {
		// Make sure to reset state on change even when vue recycle the component
		thumbnailUrl() {
			this.hasValidThumbnail = this.thumbnailUrl && this.thumbnailUrl.trim() !== ''
			this.loaded = false
		},
	},

	methods: {
		reEmitEvent(e) {
			this.$emit(e.type, e)
		},

		/**
		 * If the image fails to load, fallback to iconClass
		 */
		onError() {
			this.hasValidThumbnail = false
		},

		onLoad() {
			this.loaded = true
		},
	},
}
</script>

<style lang="scss" scoped>
$clickable-area: 44px;
$margin: 10px;

.unified-search__result {
	display: flex;
	height: $clickable-area;
	padding: $margin;
	border-bottom: 1px solid var(--color-border);

	// Load more entry,
	&:last-child {
		border-bottom: none;
	}

	&--focused,
	&:active,
	&:hover,
	&:focus {
		background-color: var(--color-background-hover);
	}

	* {
		cursor: pointer;
	}

	&-icon {
		overflow: hidden;
		width: $clickable-area;
		height: $clickable-area;
		border-radius: var(--border-radius);
		background-position: center center;
		background-size: 32px;
		&--rounded {
			border-radius: $clickable-area / 2;
		}
		&--no-preview {
			background-size: 32px;
		}
		&--with-thumbnail {
			background-size: cover;
		}
		&--with-thumbnail:not(&--rounded) {
			// compensate for border
			max-width: $clickable-area - 2px;
			max-height: $clickable-area - 2px;
			border: 1px solid var(--color-border);
		}

		img {
			// Make sure to keep ratio
			width: 100%;
			height: 100%;

			object-fit: cover;
			object-position: center;
		}
	}

	&-icon,
	&-actions {
		flex: 0 0 $clickable-area;
	}

	&-content {
		display: flex;
		align-items: center;
		flex: 1 1 100%;
		flex-wrap: wrap;
		// Set to minimum and gro from it
		min-width: 0;
		padding-left: $margin;
	}

	&-line-one,
	&-line-two {
		overflow: hidden;
		flex: 1 1 100%;
		margin: 0;
		white-space: nowrap;
		text-overflow: ellipsis;
		// Use the same color as the `a`
		color: inherit;
		font-size: inherit;
	}
	&-line-two {
		opacity: .7;
		font-size: 14px;
	}
}

</style>
