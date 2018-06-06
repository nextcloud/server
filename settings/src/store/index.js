import Vue from 'vue';
import Vuex from 'vuex';
import users from './users';
import apps from './apps';
import settings from './settings';
import oc from './oc';

Vue.use(Vuex)

const debug = process.env.NODE_ENV !== 'production';

const mutations = {
	API_FAILURE(state, error) {
		try {
			let message = error.error.response.data.ocs.meta.message;
			OC.Notification.showHtml(t('settings','An error occured during the request. Unable to proceed.')+'<br>'+message, {timeout: 7});
		} catch(e) {
			OC.Notification.showTemporary(t('settings','An error occured during the request. Unable to proceed.'));
		}
		console.log(state, error);
	}
};

export default new Vuex.Store({
	modules: {
		users,
		apps,
		settings,
		oc
	},
	strict: debug,

	mutations
});
