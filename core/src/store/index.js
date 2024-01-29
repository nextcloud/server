import Vue from 'vue';
import Vuex from 'vuex';
import search from './unified-search-external-filters';

Vue.use(Vuex);

export default new Vuex.Store({
    modules: {
        search,
    },
});
