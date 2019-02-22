<template>
	<div :class="{preview: true}">
		<div class="preview-image" :style="{backgroundImage: 'url(' + preview.img + ')'}"></div>
		<div class="preview-description">
			<h3>{{preview.title}}</h3>
			<p>{{preview.text}}</p>
			<input type="checkbox" class="checkbox" :id="'accessibility-' + preview.id" :checked="selected === preview.id" />
			<label :for="'accessibility-' + preview.id" @click="selectItem">{{t('accessibility', 'Enable')}} {{preview.title.toLowerCase()}}</label>
		</div>
	</div>
</template>

<script>
export default {
	name: 'itemPreview',
	props: ['preview', 'selected'],
	methods: {
		selectItem() {
			this.$emit(
				'select',
				// if we clicked the already selected one: disable it
				this.preview.id === this.selected ? false : this.preview.id
			);
		}
	}
};
</script>
