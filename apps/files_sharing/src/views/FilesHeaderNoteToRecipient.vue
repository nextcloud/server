<template>
	<NcNoteCard class="note-to-recipient" type="info">
		<p class="note-to-recipient__text" v-text="noteToRecipient" />
	</NcNoteCard>
</template>

<script setup lang="ts">
import NcNoteCard from '@nextcloud/vue/dist/Components/NcNoteCard.js'

defineProps<{
	noteToRecipient: string
}>()
</script>

<style scoped>
.note-to-recipient {
	margin-inline: calc(3 * var(--default-grid-baseline));
}

.note-to-recipient__text {
	/* respect new lines */
	white-space: pre-line;
}
</style>
