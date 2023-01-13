import Vue from 'vue'
import Vuex, { Store } from 'vuex'

import files from './files'
import paths from './paths'
import selection from './selection'

Vue.use(Vuex)

export default new Store({
	modules: {
		files,
		paths,
		selection,
	},
})
