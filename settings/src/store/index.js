import Vue from 'vue';
import Vuex from 'vuex';
import users from './users';
import settings from './settings';
import oc from './oc';

Vue.use(Vuex)

const debug = process.env.NODE_ENV !== 'production';

const mutations = {
	API_FAILURE(state, error) {
		console.log(state, error);
		OC.Notification.showTemporary(t('settings','An error occured during the request. Unable to proceed.'));
	}
};

export default new Vuex.Store({
	modules: {
		users,
		settings,
		oc
	},
	strict: debug,

	mutations
});
