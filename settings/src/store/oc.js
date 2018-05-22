import api from './api';

const state = {};
const mutations = {};
const getters = {};
const actions = {
	/**
     * Set application config in database
     * 
	 * @param {Object} context
     * @param {Object} options
	 * @param {string} options.app Application name
	 * @param {boolean} options.key Config key
	 * @param {boolean} options.value Value to set
	 * @returns{Promise}
	 */
	setAppConfig(context, {app, key, value}) {
		return api.requireAdmin().then((response) => {
			return api.post(OC.linkToOCS(`apps/provisioning_api/api/v1/config/apps/${app}/${key}`, 2), {value: value})
				.catch((error) => {throw error;});
		}).catch((error) => context.commit('API_FAILURE', { app, key, value, error }));;
    }
};

export default {state, mutations, getters, actions};
