/*! third party licenses: dist/vendor.LICENSE.txt */
import{b1 as o,b0 as n,bw as e}from"../core-common.mjs";function c(){return o().proxy.$root.$router}function s(){var r=o().proxy.$root;if(!r._$route){var t=n(!0).run(function(){return e(Object.assign({},r.$router.currentRoute))});r._$route=t,r.$router.afterEach(function(u){Object.assign(t,u)})}return r._$route}export{c as a,s as u};
