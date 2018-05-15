import Vue from 'vue';
import Vuex from 'vuex';
import users from './users';
import settings from './settings';
import oc from './oc';

Vue.use(Vuex)

const debug = process.env.NODE_ENV !== 'production';

const mutations = {
	API_FAILURE(state, error) {
		let message = error.error.response.data.ocs.meta.message;
		OC.Notification.showHtml(t('settings','An error occured during the request. Unable to proceed.')+'<br>'+message, {timeout: 7});
		// throw to raise exception of the promise and allow a `.then` in the Vue methods
		console.log(state, error);
		throw new Error(error);
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
