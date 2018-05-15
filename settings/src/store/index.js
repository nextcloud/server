import Vue from 'vue';
import Vuex from 'vuex';
import users from './users';
import settings from './settings';
import oc from './oc';

Vue.use(Vuex)

const debug = process.env.NODE_ENV !== 'production';

const mutations = {
	API_FAILURE(state, error) {
		OC.Notification.showTemporary(t('settings','An error occured during the request. Unable to proceed.'));
		// throw to raise exception of the promise and allow a `.then` in the Vue methods
		throw error;
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
