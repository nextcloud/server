import Vue from 'vue';
import Router from 'vue-router';
import Users from './views/Users';

Vue.use(Router);

/*
 * This is the list of routes where the vuejs app will
 * take over php to provide data
 * You need to forward the php routing (routes.php) to
 * /settings/main.php, where the vue-router will ensure
 * the proper route.
 * ⚠️ Routes needs to match the php routes.
 */

export default new Router({
    mode: 'history',
    base: window.location.pathName,
    routes: [{
        path: '/settings/users',
        component: Users
    }]
});