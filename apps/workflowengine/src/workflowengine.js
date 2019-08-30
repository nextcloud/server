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
import FileMimeType from './components/Values/FileMimeType';

window.OCA.WorkflowEngine = Object.assign({}, OCA.WorkflowEngine, {
	registerCheck: function (Plugin) {
		store.commit('addPluginCheck', Plugin)
	},
	registerOperator: function (Plugin) {
		store.commit('addPluginOperator', Plugin)
	}
})

// Load legacy plugins for now and register them in the new plugin system
Object.values(OCA.WorkflowEngine.Plugins).map((plugin) => {
	if (plugin.component) {
		return { ...plugin.getCheck(), component: plugin.component() }
	}
	return plugin.getCheck()
}).forEach((legacyCheckPlugin) => window.OCA.WorkflowEngine.registerCheck(legacyCheckPlugin))

// new way of registering checks
window.OCA.WorkflowEngine.registerCheck({
	class: 'OCA\\WorkflowEngine\\Check\\FileMimeType',
	name: t('workflowengine', 'File MIME type'),
	operators: [
		{ operator: 'is', name: t('workflowengine', 'is') },
		{ operator: '!is', name: t('workflowengine', 'is not') },
		{ operator: 'matches', name: t('workflowengine', 'matches') },
		{ operator: '!matches', name: t('workflowengine', 'does not match') }
	],
	component: FileMimeType
})

Vue.use(Vuex)
Vue.prototype.t = t

const View = Vue.extend(Settings)
new View({
	store
}).$mount('#workflowengine')
