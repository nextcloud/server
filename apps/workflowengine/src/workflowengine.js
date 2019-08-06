import './filemimetypeplugin'
import './filenameplugin'
import './filesizeplugin'
import './filesystemtagsplugin'
import './requestremoteaddressplugin'
import './requesttimeplugin'
import './requesturlplugin'
import './requestuseragentplugin'
import './usergroupmembershipplugin'

window.OCA.WorkflowEngine = OCA.WorkflowEngine

import Vue from 'vue';

Vue.prototype.t = t;

import Settings from './components/Workflow';
const View = Vue.extend(Settings)
new View({}).$mount('#workflowengine')