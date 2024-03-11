<template>
	<article class="app-discover-post"
		:class="{ 'app-discover-post--reverse': media && media.alignment === 'start' }">
		<div v-if="headline || text" class="app-discover-post__text">
			<h3>{{ translatedHeadline }}</h3>
			<p>{{ translatedText }}</p>
		</div>
		<div v-if="media">
			<img class="app-discover-post__media" :alt="mediaAlt" :src="mediaSource">
		</div>
	</article>
</template>

<script setup lang="ts">
import { getLanguage } from '@nextcloud/l10n'
import { computed } from 'vue'

type ILocalizedValue<T> = Record<string, T | undefined> & { en: T }

const props = defineProps<{
	type: string

	headline: ILocalizedValue<string>
	text: ILocalizedValue<string>
	link?: string
	media: {
		alignment: 'start'|'end'
		content: ILocalizedValue<{ src: string, alt: string}>
	}
}>()

const language = getLanguage()

const getLocalizedValue = <T, >(dict: ILocalizedValue<T>) => dict[language] ?? dict[language.split('_')[0]] ?? dict.en

const translatedText = computed(() => getLocalizedValue(props.text))
const translatedHeadline = computed(() => getLocalizedValue(props.headline))

const localizedMedia = computed(() => getLocalizedValue(props.media.content))

const mediaSource = computed(() => localizedMedia.value?.src)
const mediaAlt = ''
</script>

<style scoped lang="scss">
.app-discover-post {
	width: 100%;
	background-color: var(--color-primary-element-light);
	border-radius: var(--border-radius-rounded);

	display: flex;
	flex-direction: row;
	&--reverse {
		flex-direction: row-reverse;
	}

	h3 {
		font-size: 24px;
		font-weight: 600;
		margin-block: 0 1em;
	}

	&__text {
		padding: var(--border-radius-rounded);
	}

	&__media {
		max-height: 300px;
		max-width: 450px;
		border-radius: var(--border-radius-rounded);
		border-end-start-radius: 0;
		border-start-start-radius: 0;
	}

	&--reverse &__media {
		border-radius: var(--border-radius-rounded);
		border-end-end-radius: 0;
		border-start-end-radius: 0;
	}
}
</style>
