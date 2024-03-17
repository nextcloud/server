import Vue from 'vue'
import FilesExternalApp from './views/FilesExternalSettings.vue'

const View = Vue.extend(FilesExternalApp)
const instance = new View()

instance.$mount('#files-external')
