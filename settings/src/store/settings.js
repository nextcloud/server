import api from './api';

const state = {
	serverData: {}
};
const mutations = {
	setServerData(state, data) {
		state.serverData = data;
	}
};
const getters = {
	getServerData(state) {
		return state.serverData;
	}
}
const actions = {}

export default {state, mutations, getters, actions};
