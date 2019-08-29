import './legacy/filemimetypeplugin'
import './legacy/filenameplugin'
import './legacy/filesizeplugin'
import './legacy/filesystemtagsplugin'
import './legacy/requestremoteaddressplugin'
import './legacy/requesttimeplugin'
import './legacy/requesturlplugin'
import './legacy/requestuseragentplugin'
import './legacy/usergroupmembershipplugin'

import Vue from 'vue'
import Vuex from 'vuex'

import store from './store'

import Settings from './components/Workflow'

window.OCA.WorkflowEngine = OCA.WorkflowEngine
Vue.use(Vuex)

Vue.prototype.t = t
const View = Vue.extend(Settings)
new View({
	store
}).$mount('#workflowengine')
