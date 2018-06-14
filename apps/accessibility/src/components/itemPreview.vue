<template>
	<div :class="{preview: true, selected: preview.id === selected}"
		 @click="selectItem">
		<div class="icon-checkmark"></div>
		<div class="preview-image" :style="{backgroundImage: 'url(' + preview.img + ')'}"></div>
		<h3>{{preview.title}}</h3>
		<p>{{preview.text}}</p>
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
