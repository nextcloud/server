<template>
	<div :class="{preview: true}">
		<div class="preview-image" :style="{backgroundImage: 'url(' + preview.img + ')'}"></div>
		<div class="preview-description">
			<h3>{{preview.title}}</h3>
			<p>{{preview.text}}</p>
			<input type="checkbox" class="checkbox" :id="'accessibility-' + preview.id" v-model="checked" />
			<label :for="'accessibility-' + preview.id">{{t('accessibility', 'Enable')}} {{preview.title.toLowerCase()}}</label>
		</div>
	</div>
</template>

<script>
export default {
	name: 'itemPreview',
	props: ['preview', 'selected'],
	computed: {
		checked: {
			get() {
				return this.selected === this.preview.id;
			},
			set(checked) {
				this.$emit('select', checked ? this.preview.id : false,	this.selected);
			}
		}
    },
};
</script>
