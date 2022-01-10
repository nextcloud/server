(self["webpackChunknextcloud"] = self["webpackChunknextcloud"] || []).push([["settings-users"],{

/***/ "./apps/settings/src/mixins/UserRowMixin.js":
/*!**************************************************!*\
  !*** ./apps/settings/src/mixins/UserRowMixin.js ***!
  \**************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.js");
function _typeof(obj) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (obj) { return typeof obj; } : function (obj) { return obj && "function" == typeof Symbol && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }, _typeof(obj); }

/**
 * @copyright Copyright (c) 2019 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author Greta Doci <gretadoci@gmail.com>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @license  GPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

/* harmony default export */ __webpack_exports__["default"] = ({
  props: {
    user: {
      type: Object,
      required: true
    },
    settings: {
      type: Object,
      default: function _default() {
        return {};
      }
    },
    groups: {
      type: Array,
      default: function _default() {
        return [];
      }
    },
    subAdminsGroups: {
      type: Array,
      default: function _default() {
        return [];
      }
    },
    quotaOptions: {
      type: Array,
      default: function _default() {
        return [];
      }
    },
    showConfig: {
      type: Object,
      default: function _default() {
        return {};
      }
    },
    languages: {
      type: Array,
      required: true
    },
    externalActions: {
      type: Array,
      default: function _default() {
        return [];
      }
    }
  },
  computed: {
    /* GROUPS MANAGEMENT */
    userGroups: function userGroups() {
      var _this = this;

      var userGroups = this.groups.filter(function (group) {
        return _this.user.groups.includes(group.id);
      });
      return userGroups;
    },
    userSubAdminsGroups: function userSubAdminsGroups() {
      var _this2 = this;

      var userSubAdminsGroups = this.subAdminsGroups.filter(function (group) {
        return _this2.user.subadmin.includes(group.id);
      });
      return userSubAdminsGroups;
    },
    availableGroups: function availableGroups() {
      var _this3 = this;

      return this.groups.map(function (group) {
        // clone object because we don't want
        // to edit the original groups
        var groupClone = Object.assign({}, group); // two settings here:
        // 1. user NOT in group but no permission to add
        // 2. user is in group but no permission to remove

        groupClone.$isDisabled = group.canAdd === false && !_this3.user.groups.includes(group.id) || group.canRemove === false && _this3.user.groups.includes(group.id);
        return groupClone;
      });
    },

    /* QUOTA MANAGEMENT */
    usedSpace: function usedSpace() {
      if (this.user.quota.used) {
        return t('settings', '{size} used', {
          size: OC.Util.humanFileSize(this.user.quota.used)
        });
      }

      return t('settings', '{size} used', {
        size: OC.Util.humanFileSize(0)
      });
    },
    usedQuota: function usedQuota() {
      var quota = this.user.quota.quota;

      if (quota > 0) {
        quota = Math.min(100, Math.round(this.user.quota.used / quota * 100));
      } else {
        var usedInGB = this.user.quota.used / (10 * Math.pow(2, 30)); // asymptotic curve approaching 50% at 10GB to visualize used stace with infinite quota

        quota = 95 * (1 - 1 / (usedInGB + 1));
      }

      return isNaN(quota) ? 0 : quota;
    },
    // Mapping saved values to objects
    userQuota: function userQuota() {
      if (this.user.quota.quota >= 0) {
        // if value is valid, let's map the quotaOptions or return custom quota
        var humanQuota = OC.Util.humanFileSize(this.user.quota.quota);
        var userQuota = this.quotaOptions.find(function (quota) {
          return quota.id === humanQuota;
        });
        return userQuota || {
          id: humanQuota,
          label: humanQuota
        };
      } else if (this.user.quota.quota === 'default') {
        // default quota is replaced by the proper value on load
        return this.quotaOptions[0];
      }

      return this.quotaOptions[1]; // unlimited
    },

    /* PASSWORD POLICY? */
    minPasswordLength: function minPasswordLength() {
      return this.$store.getters.getPasswordPolicyMinLength;
    },

    /* LANGUAGE */
    userLanguage: function userLanguage() {
      var _this4 = this;

      var availableLanguages = this.languages[0].languages.concat(this.languages[1].languages);
      var userLang = availableLanguages.find(function (lang) {
        return lang.code === _this4.user.language;
      });

      if (_typeof(userLang) !== 'object' && this.user.language !== '') {
        return {
          code: this.user.language,
          name: this.user.language
        };
      } else if (this.user.language === '') {
        return false;
      }

      return userLang;
    },

    /* LAST LOGIN */
    userLastLoginTooltip: function userLastLoginTooltip() {
      if (this.user.lastLogin > 0) {
        return OC.Util.formatDate(this.user.lastLogin);
      }

      return '';
    },
    userLastLogin: function userLastLogin() {
      if (this.user.lastLogin > 0) {
        return OC.Util.relativeModifiedDate(this.user.lastLogin);
      }

      return t('settings', 'Never');
    }
  },
  methods: {
    /**
     * Generate avatar url
     *
     * @param {string} user The user name
     * @param {number} size Size integer, default 32
     * @return {string}
     */
    generateAvatar: function generateAvatar(user) {
      var size = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 32;
      return (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_0__.generateUrl)('/avatar/{user}/{size}?v={version}', {
        user: user,
        size: size,
        version: oc_userconfig.avatar.version
      });
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/UserList.vue?vue&type=script&lang=js&":
/*!********************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/UserList.vue?vue&type=script&lang=js& ***!
  \********************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/event-bus */ "./node_modules/@nextcloud/event-bus/dist/index.js");
/* harmony import */ var vue_infinite_loading__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! vue-infinite-loading */ "./node_modules/vue-infinite-loading/dist/vue-infinite-loading.js");
/* harmony import */ var vue_infinite_loading__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(vue_infinite_loading__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _nextcloud_vue__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/vue */ "./node_modules/@nextcloud/vue/dist/ncvuecomponents.js");
/* harmony import */ var _nextcloud_vue__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _nextcloud_vue_dist_Components_Multiselect__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/Multiselect */ "./node_modules/@nextcloud/vue/dist/Components/Multiselect.js");
/* harmony import */ var _nextcloud_vue_dist_Components_Multiselect__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_Multiselect__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _UserList_UserRow__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./UserList/UserRow */ "./apps/settings/src/components/UserList/UserRow.vue");
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//






var unlimitedQuota = {
  id: 'none',
  label: t('settings', 'Unlimited')
};
var defaultQuota = {
  id: 'default',
  label: t('settings', 'Default quota')
};
var newUser = {
  id: '',
  displayName: '',
  password: '',
  mailAddress: '',
  groups: [],
  subAdminsGroups: [],
  quota: defaultQuota,
  language: {
    code: 'en',
    name: t('settings', 'Default language')
  }
};
/* harmony default export */ __webpack_exports__["default"] = ({
  name: 'UserList',
  components: {
    Modal: _nextcloud_vue__WEBPACK_IMPORTED_MODULE_2__.Modal,
    userRow: _UserList_UserRow__WEBPACK_IMPORTED_MODULE_4__.default,
    Multiselect: (_nextcloud_vue_dist_Components_Multiselect__WEBPACK_IMPORTED_MODULE_3___default()),
    InfiniteLoading: (vue_infinite_loading__WEBPACK_IMPORTED_MODULE_1___default())
  },
  props: {
    users: {
      type: Array,
      default: function _default() {
        return [];
      }
    },
    showConfig: {
      type: Object,
      required: true
    },
    selectedGroup: {
      type: String,
      default: null
    },
    externalActions: {
      type: Array,
      default: function _default() {
        return [];
      }
    }
  },
  data: function data() {
    return {
      unlimitedQuota: unlimitedQuota,
      defaultQuota: defaultQuota,
      loading: {
        all: false,
        groups: false
      },
      scrolled: false,
      searchQuery: '',
      newUser: Object.assign({}, newUser)
    };
  },
  computed: {
    settings: function settings() {
      return this.$store.getters.getServerData;
    },
    selectedGroupDecoded: function selectedGroupDecoded() {
      return decodeURIComponent(this.selectedGroup);
    },
    filteredUsers: function filteredUsers() {
      if (this.selectedGroup === 'disabled') {
        return this.users.filter(function (user) {
          return user.enabled === false;
        });
      }

      if (!this.settings.isAdmin) {
        // we don't want subadmins to edit themselves
        return this.users.filter(function (user) {
          return user.enabled !== false;
        });
      }

      return this.users.filter(function (user) {
        return user.enabled !== false;
      });
    },
    groups: function groups() {
      // data provided php side + remove the disabled group
      return this.$store.getters.getGroups.filter(function (group) {
        return group.id !== 'disabled';
      }).sort(function (a, b) {
        return a.name.localeCompare(b.name);
      });
    },
    canAddGroups: function canAddGroups() {
      // disabled if no permission to add new users to group
      return this.groups.map(function (group) {
        // clone object because we don't want
        // to edit the original groups
        group = Object.assign({}, group);
        group.$isDisabled = group.canAdd === false;
        return group;
      });
    },
    subAdminsGroups: function subAdminsGroups() {
      // data provided php side
      return this.$store.getters.getSubadminGroups;
    },
    quotaOptions: function quotaOptions() {
      // convert the preset array into objects
      var quotaPreset = this.settings.quotaPreset.reduce(function (acc, cur) {
        return acc.concat({
          id: cur,
          label: cur
        });
      }, []); // add default presets

      if (this.settings.allowUnlimitedQuota) {
        quotaPreset.unshift(this.unlimitedQuota);
      }

      quotaPreset.unshift(this.defaultQuota);
      return quotaPreset;
    },
    minPasswordLength: function minPasswordLength() {
      return this.$store.getters.getPasswordPolicyMinLength;
    },
    usersOffset: function usersOffset() {
      return this.$store.getters.getUsersOffset;
    },
    usersLimit: function usersLimit() {
      return this.$store.getters.getUsersLimit;
    },
    usersCount: function usersCount() {
      return this.users.length;
    },

    /* LANGUAGES */
    languages: function languages() {
      return [{
        label: t('settings', 'Common languages'),
        languages: this.settings.languages.commonLanguages
      }, {
        label: t('settings', 'Other languages'),
        languages: this.settings.languages.otherLanguages
      }];
    }
  },
  watch: {
    // watch url change and group select
    selectedGroup: function selectedGroup(val, old) {
      // if selected is the disabled group but it's empty
      this.redirectIfDisabled();
      this.$store.commit('resetUsers');
      this.$refs.infiniteLoading.stateChanger.reset();
      this.setNewUserDefaultGroup(val);
    },
    // make sure the infiniteLoading state is changed if we manually
    // add/remove data from the store
    usersCount: function usersCount(val, old) {
      // deleting the last user, reset the list
      if (val === 0 && old === 1) {
        this.$refs.infiniteLoading.stateChanger.reset(); // adding the first user, warn the infiniteLoader that
        // the list is not empty anymore (we don't fetch the newly
        // added user as we already have all the info we need)
      } else if (val === 1 && old === 0) {
        this.$refs.infiniteLoading.stateChanger.loaded();
      }
    }
  },
  mounted: function mounted() {
    if (!this.settings.canChangePassword) {
      OC.Notification.showTemporary(t('settings', 'Password change is disabled because the master key is disabled'));
    }
    /**
     * Reset and init new user form
     */


    this.resetForm();
    /**
     * Register search
     */

    (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__.subscribe)('nextcloud:unified-search.search', this.search);
    (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__.subscribe)('nextcloud:unified-search.reset', this.resetSearch);
    /**
     * If disabled group but empty, redirect
     */

    this.redirectIfDisabled();
  },
  beforeDestroy: function beforeDestroy() {
    (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__.unsubscribe)('nextcloud:unified-search.search', this.search);
    (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__.unsubscribe)('nextcloud:unified-search.reset', this.resetSearch);
  },
  methods: {
    onScroll: function onScroll(event) {
      this.scrolled = event.target.scrollTo > 0;
    },

    /**
     * Validate quota string to make sure it's a valid human file size
     *
     * @param {string} quota Quota in readable format '5 GB'
     * @return {object}
     */
    validateQuota: function validateQuota(quota) {
      // only used for new presets sent through @Tag
      var validQuota = OC.Util.computerFileSize(quota);

      if (validQuota !== null && validQuota >= 0) {
        // unify format output
        quota = OC.Util.humanFileSize(OC.Util.computerFileSize(quota));
        this.newUser.quota = {
          id: quota,
          label: quota
        };
        return this.newUser.quota;
      } // Default is unlimited


      this.newUser.quota = this.quotaOptions[0];
      return this.quotaOptions[0];
    },
    infiniteHandler: function infiniteHandler($state) {
      var _this = this;

      this.$store.dispatch('getUsers', {
        offset: this.usersOffset,
        limit: this.usersLimit,
        group: this.selectedGroup !== 'disabled' ? this.selectedGroup : '',
        search: this.searchQuery
      }).then(function (usersCount) {
        if (usersCount > 0) {
          $state.loaded();
        }

        if (usersCount < _this.usersLimit) {
          $state.complete();
        }
      });
    },

    /* SEARCH */
    search: function search(_ref) {
      var query = _ref.query;
      this.searchQuery = query;
      this.$store.commit('resetUsers');
      this.$refs.infiniteLoading.stateChanger.reset();
    },
    resetSearch: function resetSearch() {
      this.search({
        query: ''
      });
    },
    resetForm: function resetForm() {
      // revert form to original state
      this.newUser = Object.assign({}, newUser);
      /**
       * Init default language from server data. The use of this.settings
       * requires a computed variable, which break the v-model binding of the form,
       * this is a much easier solution than getter and setter on a computed var
       */

      if (this.settings.defaultLanguage) {
        vue__WEBPACK_IMPORTED_MODULE_5__.default.set(this.newUser.language, 'code', this.settings.defaultLanguage);
      }
      /**
       * In case the user directly loaded the user list within a group
       * the watch won't be triggered. We need to initialize it.
       */


      this.setNewUserDefaultGroup(this.selectedGroup);
      this.loading.all = false;
    },
    createUser: function createUser() {
      var _this2 = this;

      this.loading.all = true;
      this.$store.dispatch('addUser', {
        userid: this.newUser.id,
        password: this.newUser.password,
        displayName: this.newUser.displayName,
        email: this.newUser.mailAddress,
        groups: this.newUser.groups.map(function (group) {
          return group.id;
        }),
        subadmin: this.newUser.subAdminsGroups.map(function (group) {
          return group.id;
        }),
        quota: this.newUser.quota.id,
        language: this.newUser.language.code
      }).then(function () {
        _this2.resetForm();

        _this2.$refs.newusername.focus();

        _this2.closeModal();
      }).catch(function (error) {
        _this2.loading.all = false;

        if (error.response && error.response.data && error.response.data.ocs && error.response.data.ocs.meta) {
          var statuscode = error.response.data.ocs.meta.statuscode;

          if (statuscode === 102) {
            // wrong username
            _this2.$refs.newusername.focus();
          } else if (statuscode === 107) {
            // wrong password
            _this2.$refs.newuserpassword.focus();
          }
        }
      });
    },
    setNewUserDefaultGroup: function setNewUserDefaultGroup(value) {
      if (value && value.length > 0) {
        // setting new user default group to the current selected one
        var currentGroup = this.groups.find(function (group) {
          return group.id === value;
        });

        if (currentGroup) {
          this.newUser.groups = [currentGroup];
          return;
        }
      } // fallback, empty selected group


      this.newUser.groups = [];
    },

    /**
     * Create a new group
     *
     * @param {string} gid Group id
     * @return {Promise}
     */
    createGroup: function createGroup(gid) {
      var _this3 = this;

      this.loading.groups = true;
      this.$store.dispatch('addGroup', gid).then(function (group) {
        _this3.newUser.groups.push(_this3.groups.find(function (group) {
          return group.id === gid;
        }));

        _this3.loading.groups = false;
      }).catch(function () {
        _this3.loading.groups = false;
      });
      return this.$store.getters.getGroups[this.groups.length];
    },

    /**
     * If the selected group is the disabled group but the count is 0
     * redirect to the all users page.
     * we only check for 0 because we don't have the count on ldap
     * and we therefore set the usercount to -1 in this specific case
     */
    redirectIfDisabled: function redirectIfDisabled() {
      var allGroups = this.$store.getters.getGroups;

      if (this.selectedGroup === 'disabled' && allGroups.findIndex(function (group) {
        return group.id === 'disabled' && group.usercount === 0;
      }) > -1) {
        // disabled group is empty, redirection to all users
        this.$router.push({
          name: 'users'
        });
        this.$refs.infiniteLoading.stateChanger.reset();
      }
    },
    closeModal: function closeModal() {
      // eslint-disable-next-line vue/no-mutating-props
      this.showConfig.showNewUserForm = false;
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/UserList/UserRow.vue?vue&type=script&lang=js&":
/*!****************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/UserList/UserRow.vue?vue&type=script&lang=js& ***!
  \****************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var vue_click_outside__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue-click-outside */ "./node_modules/vue-click-outside/index.js");
/* harmony import */ var vue_click_outside__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(vue_click_outside__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var v_tooltip__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! v-tooltip */ "./node_modules/v-tooltip/dist/v-tooltip.esm.js");
/* harmony import */ var _nextcloud_vue__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/vue */ "./node_modules/@nextcloud/vue/dist/ncvuecomponents.js");
/* harmony import */ var _nextcloud_vue__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _UserRowSimple__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./UserRowSimple */ "./apps/settings/src/components/UserList/UserRowSimple.vue");
/* harmony import */ var _mixins_UserRowMixin__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../../mixins/UserRowMixin */ "./apps/settings/src/mixins/UserRowMixin.js");
function asyncGeneratorStep(gen, resolve, reject, _next, _throw, key, arg) { try { var info = gen[key](arg); var value = info.value; } catch (error) { reject(error); return; } if (info.done) { resolve(value); } else { Promise.resolve(value).then(_next, _throw); } }

function _asyncToGenerator(fn) { return function () { var self = this, args = arguments; return new Promise(function (resolve, reject) { var gen = fn.apply(self, args); function _next(value) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "next", value); } function _throw(err) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "throw", err); } _next(undefined); }); }; }

//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//






vue__WEBPACK_IMPORTED_MODULE_5__.default.use(v_tooltip__WEBPACK_IMPORTED_MODULE_1__.default);
/* harmony default export */ __webpack_exports__["default"] = ({
  name: 'UserRow',
  components: {
    UserRowSimple: _UserRowSimple__WEBPACK_IMPORTED_MODULE_3__.default,
    PopoverMenu: _nextcloud_vue__WEBPACK_IMPORTED_MODULE_2__.PopoverMenu,
    Actions: _nextcloud_vue__WEBPACK_IMPORTED_MODULE_2__.Actions,
    ActionButton: _nextcloud_vue__WEBPACK_IMPORTED_MODULE_2__.ActionButton,
    Multiselect: _nextcloud_vue__WEBPACK_IMPORTED_MODULE_2__.Multiselect
  },
  directives: {
    ClickOutside: (vue_click_outside__WEBPACK_IMPORTED_MODULE_0___default())
  },
  mixins: [_mixins_UserRowMixin__WEBPACK_IMPORTED_MODULE_4__.default],
  props: {
    user: {
      type: Object,
      required: true
    },
    settings: {
      type: Object,
      default: function _default() {
        return {};
      }
    },
    groups: {
      type: Array,
      default: function _default() {
        return [];
      }
    },
    subAdminsGroups: {
      type: Array,
      default: function _default() {
        return [];
      }
    },
    quotaOptions: {
      type: Array,
      default: function _default() {
        return [];
      }
    },
    showConfig: {
      type: Object,
      default: function _default() {
        return {};
      }
    },
    languages: {
      type: Array,
      required: true
    },
    externalActions: {
      type: Array,
      default: function _default() {
        return [];
      }
    }
  },
  data: function data() {
    return {
      rand: parseInt(Math.random() * 1000),
      openedMenu: false,
      feedbackMessage: '',
      editing: false,
      loading: {
        all: false,
        displayName: false,
        password: false,
        mailAddress: false,
        groups: false,
        subadmins: false,
        quota: false,
        delete: false,
        disable: false,
        languages: false,
        wipe: false
      }
    };
  },
  computed: {
    /* USER POPOVERMENU ACTIONS */
    userActions: function userActions() {
      var actions = [{
        icon: 'icon-delete',
        text: t('settings', 'Delete user'),
        action: this.deleteUser
      }, {
        icon: 'icon-delete',
        text: t('settings', 'Wipe all devices'),
        action: this.wipeUserDevices
      }, {
        icon: this.user.enabled ? 'icon-close' : 'icon-add',
        text: this.user.enabled ? t('settings', 'Disable user') : t('settings', 'Enable user'),
        action: this.enableDisableUser
      }];

      if (this.user.email !== null && this.user.email !== '') {
        actions.push({
          icon: 'icon-mail',
          text: t('settings', 'Resend welcome email'),
          action: this.sendWelcomeMail
        });
      }

      return actions.concat(this.externalActions);
    }
  },
  methods: {
    /* MENU HANDLING */
    toggleMenu: function toggleMenu() {
      this.openedMenu = !this.openedMenu;
    },
    hideMenu: function hideMenu() {
      this.openedMenu = false;
    },
    wipeUserDevices: function wipeUserDevices() {
      var _this = this;

      var userid = this.user.id;
      OC.dialogs.confirmDestructive(t('settings', 'In case of lost device or exiting the organization, this can remotely wipe the Nextcloud data from all devices associated with {userid}. Only works if the devices are connected to the internet.', {
        userid: userid
      }), t('settings', 'Remote wipe of devices'), {
        type: OC.dialogs.YES_NO_BUTTONS,
        confirm: t('settings', 'Wipe {userid}\'s devices', {
          userid: userid
        }),
        confirmClasses: 'error',
        cancel: t('settings', 'Cancel')
      }, function (result) {
        if (result) {
          _this.loading.wipe = true;
          _this.loading.all = true;

          _this.$store.dispatch('wipeUserDevices', userid).then(function () {
            _this.loading.wipe = false;
            _this.loading.all = false;
          });
        }
      }, true);
    },
    deleteUser: function deleteUser() {
      var _this2 = this;

      var userid = this.user.id;
      OC.dialogs.confirmDestructive(t('settings', 'Fully delete {userid}\'s account including all their personal files, app data, etc.', {
        userid: userid
      }), t('settings', 'Account deletion'), {
        type: OC.dialogs.YES_NO_BUTTONS,
        confirm: t('settings', 'Delete {userid}\'s account', {
          userid: userid
        }),
        confirmClasses: 'error',
        cancel: t('settings', 'Cancel')
      }, function (result) {
        if (result) {
          _this2.loading.delete = true;
          _this2.loading.all = true;
          return _this2.$store.dispatch('deleteUser', userid).then(function () {
            _this2.loading.delete = false;
            _this2.loading.all = false;
          });
        }
      }, true);
    },
    enableDisableUser: function enableDisableUser() {
      var _this3 = this;

      this.loading.delete = true;
      this.loading.all = true;
      var userid = this.user.id;
      var enabled = !this.user.enabled;
      return this.$store.dispatch('enableDisableUser', {
        userid: userid,
        enabled: enabled
      }).then(function () {
        _this3.loading.delete = false;
        _this3.loading.all = false;
      });
    },

    /**
     * Set user displayName
     *
     * @param {string} displayName The display name
     */
    updateDisplayName: function updateDisplayName() {
      var _this4 = this;

      var displayName = this.$refs.displayName.value;
      this.loading.displayName = true;
      this.$store.dispatch('setUserData', {
        userid: this.user.id,
        key: 'displayname',
        value: displayName
      }).then(function () {
        _this4.loading.displayName = false;
        _this4.$refs.displayName.value = displayName;
      });
    },

    /**
     * Set user password
     *
     * @param {string} password The email adress
     */
    updatePassword: function updatePassword() {
      var _this5 = this;

      var password = this.$refs.password.value;
      this.loading.password = true;
      this.$store.dispatch('setUserData', {
        userid: this.user.id,
        key: 'password',
        value: password
      }).then(function () {
        _this5.loading.password = false;
        _this5.$refs.password.value = ''; // empty & show placeholder
      });
    },

    /**
     * Set user mailAddress
     *
     * @param {string} mailAddress The email adress
     */
    updateEmail: function updateEmail() {
      var _this6 = this;

      var mailAddress = this.$refs.mailAddress.value;
      this.loading.mailAddress = true;
      this.$store.dispatch('setUserData', {
        userid: this.user.id,
        key: 'email',
        value: mailAddress
      }).then(function () {
        _this6.loading.mailAddress = false;
        _this6.$refs.mailAddress.value = mailAddress;
      });
    },

    /**
     * Create a new group and add user to it
     *
     * @param {string} gid Group id
     */
    createGroup: function createGroup(gid) {
      var _this7 = this;

      return _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee() {
        var userid;
        return regeneratorRuntime.wrap(function _callee$(_context) {
          while (1) {
            switch (_context.prev = _context.next) {
              case 0:
                _this7.loading = {
                  groups: true,
                  subadmins: true
                };
                _context.prev = 1;
                _context.next = 4;
                return _this7.$store.dispatch('addGroup', gid);

              case 4:
                userid = _this7.user.id;
                _context.next = 7;
                return _this7.$store.dispatch('addUserGroup', {
                  userid: userid,
                  gid: gid
                });

              case 7:
                _context.next = 12;
                break;

              case 9:
                _context.prev = 9;
                _context.t0 = _context["catch"](1);
                console.error(_context.t0);

              case 12:
                _context.prev = 12;
                _this7.loading = {
                  groups: false,
                  subadmins: false
                };
                return _context.finish(12);

              case 15:
                return _context.abrupt("return", _this7.$store.getters.getGroups[_this7.groups.length]);

              case 16:
              case "end":
                return _context.stop();
            }
          }
        }, _callee, null, [[1, 9, 12, 15]]);
      }))();
    },

    /**
     * Add user to group
     *
     * @param {object} group Group object
     */
    addUserGroup: function addUserGroup(group) {
      var _this8 = this;

      return _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee2() {
        var userid, gid;
        return regeneratorRuntime.wrap(function _callee2$(_context2) {
          while (1) {
            switch (_context2.prev = _context2.next) {
              case 0:
                if (!(group.canAdd === false)) {
                  _context2.next = 2;
                  break;
                }

                return _context2.abrupt("return", false);

              case 2:
                _this8.loading.groups = true;
                userid = _this8.user.id;
                gid = group.id;
                _context2.prev = 5;
                _context2.next = 8;
                return _this8.$store.dispatch('addUserGroup', {
                  userid: userid,
                  gid: gid
                });

              case 8:
                _context2.next = 13;
                break;

              case 10:
                _context2.prev = 10;
                _context2.t0 = _context2["catch"](5);
                console.error(_context2.t0);

              case 13:
                _context2.prev = 13;
                _this8.loading.groups = false;
                return _context2.finish(13);

              case 16:
              case "end":
                return _context2.stop();
            }
          }
        }, _callee2, null, [[5, 10, 13, 16]]);
      }))();
    },

    /**
     * Remove user from group
     *
     * @param {object} group Group object
     */
    removeUserGroup: function removeUserGroup(group) {
      var _this9 = this;

      return _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee3() {
        var userid, gid;
        return regeneratorRuntime.wrap(function _callee3$(_context3) {
          while (1) {
            switch (_context3.prev = _context3.next) {
              case 0:
                if (!(group.canRemove === false)) {
                  _context3.next = 2;
                  break;
                }

                return _context3.abrupt("return", false);

              case 2:
                _this9.loading.groups = true;
                userid = _this9.user.id;
                gid = group.id;
                _context3.prev = 5;
                _context3.next = 8;
                return _this9.$store.dispatch('removeUserGroup', {
                  userid: userid,
                  gid: gid
                });

              case 8:
                _this9.loading.groups = false; // remove user from current list if current list is the removed group

                if (_this9.$route.params.selectedGroup === gid) {
                  _this9.$store.commit('deleteUser', userid);
                }

                _context3.next = 15;
                break;

              case 12:
                _context3.prev = 12;
                _context3.t0 = _context3["catch"](5);
                _this9.loading.groups = false;

              case 15:
              case "end":
                return _context3.stop();
            }
          }
        }, _callee3, null, [[5, 12]]);
      }))();
    },

    /**
     * Add user to group
     *
     * @param {object} group Group object
     */
    addUserSubAdmin: function addUserSubAdmin(group) {
      var _this10 = this;

      return _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee4() {
        var userid, gid;
        return regeneratorRuntime.wrap(function _callee4$(_context4) {
          while (1) {
            switch (_context4.prev = _context4.next) {
              case 0:
                _this10.loading.subadmins = true;
                userid = _this10.user.id;
                gid = group.id;
                _context4.prev = 3;
                _context4.next = 6;
                return _this10.$store.dispatch('addUserSubAdmin', {
                  userid: userid,
                  gid: gid
                });

              case 6:
                _this10.loading.subadmins = false;
                _context4.next = 12;
                break;

              case 9:
                _context4.prev = 9;
                _context4.t0 = _context4["catch"](3);
                console.error(_context4.t0);

              case 12:
              case "end":
                return _context4.stop();
            }
          }
        }, _callee4, null, [[3, 9]]);
      }))();
    },

    /**
     * Remove user from group
     *
     * @param {object} group Group object
     */
    removeUserSubAdmin: function removeUserSubAdmin(group) {
      var _this11 = this;

      return _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee5() {
        var userid, gid;
        return regeneratorRuntime.wrap(function _callee5$(_context5) {
          while (1) {
            switch (_context5.prev = _context5.next) {
              case 0:
                _this11.loading.subadmins = true;
                userid = _this11.user.id;
                gid = group.id;
                _context5.prev = 3;
                _context5.next = 6;
                return _this11.$store.dispatch('removeUserSubAdmin', {
                  userid: userid,
                  gid: gid
                });

              case 6:
                _context5.next = 11;
                break;

              case 8:
                _context5.prev = 8;
                _context5.t0 = _context5["catch"](3);
                console.error(_context5.t0);

              case 11:
                _context5.prev = 11;
                _this11.loading.subadmins = false;
                return _context5.finish(11);

              case 14:
              case "end":
                return _context5.stop();
            }
          }
        }, _callee5, null, [[3, 8, 11, 14]]);
      }))();
    },

    /**
     * Dispatch quota set request
     *
     * @param {string | object} quota Quota in readable format '5 GB' or Object {id: '5 GB', label: '5GB'}
     * @return {string}
     */
    setUserQuota: function setUserQuota() {
      var _arguments = arguments,
          _this12 = this;

      return _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee6() {
        var quota;
        return regeneratorRuntime.wrap(function _callee6$(_context6) {
          while (1) {
            switch (_context6.prev = _context6.next) {
              case 0:
                quota = _arguments.length > 0 && _arguments[0] !== undefined ? _arguments[0] : 'none';
                _this12.loading.quota = true; // ensure we only send the preset id

                quota = quota.id ? quota.id : quota;
                _context6.prev = 3;
                _context6.next = 6;
                return _this12.$store.dispatch('setUserData', {
                  userid: _this12.user.id,
                  key: 'quota',
                  value: quota
                });

              case 6:
                _context6.next = 11;
                break;

              case 8:
                _context6.prev = 8;
                _context6.t0 = _context6["catch"](3);
                console.error(_context6.t0);

              case 11:
                _context6.prev = 11;
                _this12.loading.quota = false;
                return _context6.finish(11);

              case 14:
                return _context6.abrupt("return", quota);

              case 15:
              case "end":
                return _context6.stop();
            }
          }
        }, _callee6, null, [[3, 8, 11, 14]]);
      }))();
    },

    /**
     * Validate quota string to make sure it's a valid human file size
     *
     * @param {string} quota Quota in readable format '5 GB'
     * @return {Promise|boolean}
     */
    validateQuota: function validateQuota(quota) {
      // only used for new presets sent through @Tag
      var validQuota = OC.Util.computerFileSize(quota);

      if (validQuota !== null && validQuota >= 0) {
        // unify format output
        return this.setUserQuota(OC.Util.humanFileSize(OC.Util.computerFileSize(quota)));
      } // if no valid do not change


      return false;
    },

    /**
     * Dispatch language set request
     *
     * @param {object} lang language object {code:'en', name:'English'}
     * @return {object}
     */
    setUserLanguage: function setUserLanguage(lang) {
      var _this13 = this;

      return _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee7() {
        return regeneratorRuntime.wrap(function _callee7$(_context7) {
          while (1) {
            switch (_context7.prev = _context7.next) {
              case 0:
                _this13.loading.languages = true; // ensure we only send the preset id

                _context7.prev = 1;
                _context7.next = 4;
                return _this13.$store.dispatch('setUserData', {
                  userid: _this13.user.id,
                  key: 'language',
                  value: lang.code
                });

              case 4:
                _context7.next = 9;
                break;

              case 6:
                _context7.prev = 6;
                _context7.t0 = _context7["catch"](1);
                console.error(_context7.t0);

              case 9:
                _context7.prev = 9;
                _this13.loading.languages = false;
                return _context7.finish(9);

              case 12:
                return _context7.abrupt("return", lang);

              case 13:
              case "end":
                return _context7.stop();
            }
          }
        }, _callee7, null, [[1, 6, 9, 12]]);
      }))();
    },

    /**
     * Dispatch new welcome mail request
     */
    sendWelcomeMail: function sendWelcomeMail() {
      var _this14 = this;

      this.loading.all = true;
      this.$store.dispatch('sendWelcomeMail', this.user.id).then(function (success) {
        if (success) {
          // Show feedback to indicate the success
          _this14.feedbackMessage = t('setting', 'Welcome mail sent!');
          setTimeout(function () {
            _this14.feedbackMessage = '';
          }, 2000);
        }

        _this14.loading.all = false;
      });
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/UserList/UserRowSimple.vue?vue&type=script&lang=js&":
/*!**********************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/UserList/UserRowSimple.vue?vue&type=script&lang=js& ***!
  \**********************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _nextcloud_vue_dist_Components_PopoverMenu__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/PopoverMenu */ "./node_modules/@nextcloud/vue/dist/Components/PopoverMenu.js");
/* harmony import */ var _nextcloud_vue_dist_Components_PopoverMenu__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_PopoverMenu__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _nextcloud_vue_dist_Components_Actions__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/Actions */ "./node_modules/@nextcloud/vue/dist/Components/Actions.js");
/* harmony import */ var _nextcloud_vue_dist_Components_Actions__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_Actions__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _nextcloud_vue_dist_Components_ActionButton__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/ActionButton */ "./node_modules/@nextcloud/vue/dist/Components/ActionButton.js");
/* harmony import */ var _nextcloud_vue_dist_Components_ActionButton__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_ActionButton__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var vue_click_outside__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! vue-click-outside */ "./node_modules/vue-click-outside/index.js");
/* harmony import */ var vue_click_outside__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(vue_click_outside__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _nextcloud_auth__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/auth */ "./node_modules/@nextcloud/auth/dist/index.js");
/* harmony import */ var _mixins_UserRowMixin__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../../mixins/UserRowMixin */ "./apps/settings/src/mixins/UserRowMixin.js");
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//






/* harmony default export */ __webpack_exports__["default"] = ({
  name: 'UserRowSimple',
  components: {
    PopoverMenu: (_nextcloud_vue_dist_Components_PopoverMenu__WEBPACK_IMPORTED_MODULE_0___default()),
    ActionButton: (_nextcloud_vue_dist_Components_ActionButton__WEBPACK_IMPORTED_MODULE_2___default()),
    Actions: (_nextcloud_vue_dist_Components_Actions__WEBPACK_IMPORTED_MODULE_1___default())
  },
  directives: {
    ClickOutside: (vue_click_outside__WEBPACK_IMPORTED_MODULE_3___default())
  },
  mixins: [_mixins_UserRowMixin__WEBPACK_IMPORTED_MODULE_5__.default],
  props: {
    user: {
      type: Object,
      required: true
    },
    loading: {
      type: Object,
      required: true
    },
    showConfig: {
      type: Object,
      required: true
    },
    userActions: {
      type: Array,
      required: true
    },
    openedMenu: {
      type: Boolean,
      required: true
    },
    feedbackMessage: {
      type: String,
      required: true
    },
    subAdminsGroups: {
      type: Array,
      required: true
    },
    settings: {
      type: Object,
      required: true
    }
  },
  computed: {
    userGroupsLabels: function userGroupsLabels() {
      return this.userGroups.map(function (group) {
        return group.name;
      }).join(', ');
    },
    userSubAdminsGroupsLabels: function userSubAdminsGroupsLabels() {
      return this.userSubAdminsGroups.map(function (group) {
        return group.name;
      }).join(', ');
    },
    usedSpace: function usedSpace() {
      if (this.user.quota.used) {
        return t('settings', '{size} used', {
          size: OC.Util.humanFileSize(this.user.quota.used)
        });
      }

      return t('settings', '{size} used', {
        size: OC.Util.humanFileSize(0)
      });
    },
    canEdit: function canEdit() {
      return (0,_nextcloud_auth__WEBPACK_IMPORTED_MODULE_4__.getCurrentUser)().uid !== this.user.id || this.settings.isAdmin;
    },
    userQuota: function userQuota() {
      var quota = this.user.quota.quota;

      if (quota === 'default') {
        quota = this.settings.defaultQuota;

        if (quota !== 'none') {
          // convert to numeric value to match what the server would usually return
          quota = OC.Util.computerFileSize(quota);
        }
      } // when the default quota is unlimited, the server returns -3 here, map it to "none"


      if (quota === 'none' || quota === -3) {
        return t('settings', 'Unlimited');
      } else if (quota >= 0) {
        return OC.Util.humanFileSize(quota);
      }

      return OC.Util.humanFileSize(0);
    }
  },
  methods: {
    toggleMenu: function toggleMenu() {
      this.$emit('update:openedMenu', !this.openedMenu);
    },
    hideMenu: function hideMenu() {
      this.$emit('update:openedMenu', false);
    },
    toggleEdit: function toggleEdit() {
      this.$emit('update:editing', true);
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/Users.vue?vue&type=script&lang=js&":
/*!************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/Users.vue?vue&type=script&lang=js& ***!
  \************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _nextcloud_vue_dist_Components_ActionButton__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/ActionButton */ "./node_modules/@nextcloud/vue/dist/Components/ActionButton.js");
/* harmony import */ var _nextcloud_vue_dist_Components_ActionButton__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_ActionButton__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _nextcloud_vue_dist_Components_AppContent__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/AppContent */ "./node_modules/@nextcloud/vue/dist/Components/AppContent.js");
/* harmony import */ var _nextcloud_vue_dist_Components_AppContent__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_AppContent__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _nextcloud_vue_dist_Components_AppNavigation__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/AppNavigation */ "./node_modules/@nextcloud/vue/dist/Components/AppNavigation.js");
/* harmony import */ var _nextcloud_vue_dist_Components_AppNavigation__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_AppNavigation__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _nextcloud_vue_dist_Components_AppNavigationCaption__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/AppNavigationCaption */ "./node_modules/@nextcloud/vue/dist/Components/AppNavigationCaption.js");
/* harmony import */ var _nextcloud_vue_dist_Components_AppNavigationCaption__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_AppNavigationCaption__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _nextcloud_vue_dist_Components_AppNavigationCounter__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/AppNavigationCounter */ "./node_modules/@nextcloud/vue/dist/Components/AppNavigationCounter.js");
/* harmony import */ var _nextcloud_vue_dist_Components_AppNavigationCounter__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_AppNavigationCounter__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _nextcloud_vue_dist_Components_AppNavigationItem__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/AppNavigationItem */ "./node_modules/@nextcloud/vue/dist/Components/AppNavigationItem.js");
/* harmony import */ var _nextcloud_vue_dist_Components_AppNavigationItem__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_AppNavigationItem__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _nextcloud_vue_dist_Components_AppNavigationNew__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/AppNavigationNew */ "./node_modules/@nextcloud/vue/dist/Components/AppNavigationNew.js");
/* harmony import */ var _nextcloud_vue_dist_Components_AppNavigationNew__WEBPACK_IMPORTED_MODULE_6___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_AppNavigationNew__WEBPACK_IMPORTED_MODULE_6__);
/* harmony import */ var _nextcloud_vue_dist_Components_AppNavigationSettings__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/AppNavigationSettings */ "./node_modules/@nextcloud/vue/dist/Components/AppNavigationSettings.js");
/* harmony import */ var _nextcloud_vue_dist_Components_AppNavigationSettings__WEBPACK_IMPORTED_MODULE_7___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_AppNavigationSettings__WEBPACK_IMPORTED_MODULE_7__);
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.js");
/* harmony import */ var _nextcloud_vue_dist_Components_Content__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/Content */ "./node_modules/@nextcloud/vue/dist/Components/Content.js");
/* harmony import */ var _nextcloud_vue_dist_Components_Content__WEBPACK_IMPORTED_MODULE_9___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_Content__WEBPACK_IMPORTED_MODULE_9__);
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.js");
/* harmony import */ var _nextcloud_vue_dist_Components_Multiselect__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/Multiselect */ "./node_modules/@nextcloud/vue/dist/Components/Multiselect.js");
/* harmony import */ var _nextcloud_vue_dist_Components_Multiselect__WEBPACK_IMPORTED_MODULE_11___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_Multiselect__WEBPACK_IMPORTED_MODULE_11__);
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var vue_localstorage__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! vue-localstorage */ "./node_modules/vue-localstorage/dist/vue-local-storage.js");
/* harmony import */ var vue_localstorage__WEBPACK_IMPORTED_MODULE_12___default = /*#__PURE__*/__webpack_require__.n(vue_localstorage__WEBPACK_IMPORTED_MODULE_12__);
/* harmony import */ var _components_UserList__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! ../components/UserList */ "./apps/settings/src/components/UserList.vue");
function _typeof(obj) { "@babel/helpers - typeof"; return _typeof = "function" == typeof Symbol && "symbol" == typeof Symbol.iterator ? function (obj) { return typeof obj; } : function (obj) { return obj && "function" == typeof Symbol && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }, _typeof(obj); }

function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys(Object(source), !0).forEach(function (key) { _defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

function asyncGeneratorStep(gen, resolve, reject, _next, _throw, key, arg) { try { var info = gen[key](arg); var value = info.value; } catch (error) { reject(error); return; } if (info.done) { resolve(value); } else { Promise.resolve(value).then(_next, _throw); } }

function _asyncToGenerator(fn) { return function () { var self = this, args = arguments; return new Promise(function (resolve, reject) { var gen = fn.apply(self, args); function _next(value) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "next", value); } function _throw(err) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "throw", err); } _next(undefined); }); }; }

//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//















vue__WEBPACK_IMPORTED_MODULE_14__.default.use((vue_localstorage__WEBPACK_IMPORTED_MODULE_12___default()));
/* harmony default export */ __webpack_exports__["default"] = ({
  name: 'Users',
  components: {
    ActionButton: (_nextcloud_vue_dist_Components_ActionButton__WEBPACK_IMPORTED_MODULE_0___default()),
    AppContent: (_nextcloud_vue_dist_Components_AppContent__WEBPACK_IMPORTED_MODULE_1___default()),
    AppNavigation: (_nextcloud_vue_dist_Components_AppNavigation__WEBPACK_IMPORTED_MODULE_2___default()),
    AppNavigationCaption: (_nextcloud_vue_dist_Components_AppNavigationCaption__WEBPACK_IMPORTED_MODULE_3___default()),
    AppNavigationCounter: (_nextcloud_vue_dist_Components_AppNavigationCounter__WEBPACK_IMPORTED_MODULE_4___default()),
    AppNavigationItem: (_nextcloud_vue_dist_Components_AppNavigationItem__WEBPACK_IMPORTED_MODULE_5___default()),
    AppNavigationNew: (_nextcloud_vue_dist_Components_AppNavigationNew__WEBPACK_IMPORTED_MODULE_6___default()),
    AppNavigationSettings: (_nextcloud_vue_dist_Components_AppNavigationSettings__WEBPACK_IMPORTED_MODULE_7___default()),
    Content: (_nextcloud_vue_dist_Components_Content__WEBPACK_IMPORTED_MODULE_9___default()),
    Multiselect: (_nextcloud_vue_dist_Components_Multiselect__WEBPACK_IMPORTED_MODULE_11___default()),
    UserList: _components_UserList__WEBPACK_IMPORTED_MODULE_13__.default
  },
  props: {
    selectedGroup: {
      type: String,
      default: null
    }
  },
  data: function data() {
    return {
      // default quota is set to unlimited
      unlimitedQuota: {
        id: 'none',
        label: t('settings', 'Unlimited')
      },
      // temporary value used for multiselect change
      selectedQuota: false,
      externalActions: [],
      loadingAddGroup: false,
      loadingSendMail: false,
      showConfig: {
        showStoragePath: false,
        showUserBackend: false,
        showLastLogin: false,
        showNewUserForm: false,
        showLanguages: false
      }
    };
  },
  computed: {
    selectedGroupDecoded: function selectedGroupDecoded() {
      return this.selectedGroup ? decodeURIComponent(this.selectedGroup) : null;
    },
    users: function users() {
      return this.$store.getters.getUsers;
    },
    groups: function groups() {
      return this.$store.getters.getGroups;
    },
    usersOffset: function usersOffset() {
      return this.$store.getters.getUsersOffset;
    },
    usersLimit: function usersLimit() {
      return this.$store.getters.getUsersLimit;
    },
    // Local settings
    showLanguages: {
      get: function get() {
        return this.getLocalstorage('showLanguages');
      },
      set: function set(status) {
        this.setLocalStorage('showLanguages', status);
      }
    },
    showLastLogin: {
      get: function get() {
        return this.getLocalstorage('showLastLogin');
      },
      set: function set(status) {
        this.setLocalStorage('showLastLogin', status);
      }
    },
    showUserBackend: {
      get: function get() {
        return this.getLocalstorage('showUserBackend');
      },
      set: function set(status) {
        this.setLocalStorage('showUserBackend', status);
      }
    },
    showStoragePath: {
      get: function get() {
        return this.getLocalstorage('showStoragePath');
      },
      set: function set(status) {
        this.setLocalStorage('showStoragePath', status);
      }
    },
    userCount: function userCount() {
      return this.$store.getters.getUserCount;
    },
    settings: function settings() {
      return this.$store.getters.getServerData;
    },
    // default quota
    quotaOptions: function quotaOptions() {
      // convert the preset array into objects
      var quotaPreset = this.settings.quotaPreset.reduce(function (acc, cur) {
        return acc.concat({
          id: cur,
          label: cur
        });
      }, []); // add default presets

      if (this.settings.allowUnlimitedQuota) {
        quotaPreset.unshift(this.unlimitedQuota);
      }

      return quotaPreset;
    },
    // mapping saved values to objects
    defaultQuota: {
      get: function get() {
        if (this.selectedQuota !== false) {
          return this.selectedQuota;
        }

        if (this.settings.defaultQuota !== this.unlimitedQuota.id && OC.Util.computerFileSize(this.settings.defaultQuota) >= 0) {
          // if value is valid, let's map the quotaOptions or return custom quota
          return {
            id: this.settings.defaultQuota,
            label: this.settings.defaultQuota
          };
        }

        return this.unlimitedQuota; // unlimited
      },
      set: function set(quota) {
        this.selectedQuota = quota;
      }
    },
    sendWelcomeMail: {
      get: function get() {
        return this.settings.newUserSendEmail;
      },
      set: function set(value) {
        var _this = this;

        return _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee() {
          return regeneratorRuntime.wrap(function _callee$(_context) {
            while (1) {
              switch (_context.prev = _context.next) {
                case 0:
                  _context.prev = 0;
                  _this.loadingSendMail = true;

                  _this.$store.commit('setServerData', _objectSpread(_objectSpread({}, _this.settings), {}, {
                    newUserSendEmail: value
                  }));

                  _context.next = 5;
                  return _nextcloud_axios__WEBPACK_IMPORTED_MODULE_8__.default.post((0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_10__.generateUrl)('/settings/users/preferences/newUser.sendEmail'), {
                    value: value ? 'yes' : 'no'
                  });

                case 5:
                  _context.next = 10;
                  break;

                case 7:
                  _context.prev = 7;
                  _context.t0 = _context["catch"](0);
                  console.error('could not update newUser.sendEmail preference: ' + _context.t0.message, _context.t0);

                case 10:
                  _context.prev = 10;
                  _this.loadingSendMail = false;
                  return _context.finish(10);

                case 13:
                case "end":
                  return _context.stop();
              }
            }
          }, _callee, null, [[0, 7, 10, 13]]);
        }))();
      }
    },
    groupList: function groupList() {
      var _this2 = this;

      var groups = Array.isArray(this.groups) ? this.groups : [];
      return groups // filter out disabled and admin
      .filter(function (group) {
        return group.id !== 'disabled' && group.id !== 'admin';
      }).map(function (group) {
        return _this2.formatGroupMenu(group);
      });
    },
    adminGroupMenu: function adminGroupMenu() {
      return this.formatGroupMenu(this.groups.find(function (group) {
        return group.id === 'admin';
      }));
    },
    disabledGroupMenu: function disabledGroupMenu() {
      return this.formatGroupMenu(this.groups.find(function (group) {
        return group.id === 'disabled';
      }));
    }
  },
  beforeMount: function beforeMount() {
    this.$store.commit('initGroups', {
      groups: this.$store.getters.getServerData.groups,
      orderBy: this.$store.getters.getServerData.sortGroups,
      userCount: this.$store.getters.getServerData.userCount
    });
    this.$store.dispatch('getPasswordPolicyMinLength');
  },
  created: function created() {
    // init the OCA.Settings.UserList object
    // and add the registerAction method
    Object.assign(OCA, {
      Settings: {
        UserList: {
          registerAction: this.registerAction
        }
      }
    });
  },
  methods: {
    showNewUserMenu: function showNewUserMenu() {
      this.showConfig.showNewUserForm = true;

      if (this.showConfig.showNewUserForm) {
        vue__WEBPACK_IMPORTED_MODULE_14__.default.nextTick(function () {
          window.newusername.focus();
        });
      }
    },
    getLocalstorage: function getLocalstorage(key) {
      // force initialization
      var localConfig = this.$localStorage.get(key); // if localstorage is null, fallback to original values

      this.showConfig[key] = localConfig !== null ? localConfig === 'true' : this.showConfig[key];
      return this.showConfig[key];
    },
    setLocalStorage: function setLocalStorage(key, status) {
      this.showConfig[key] = status;
      this.$localStorage.set(key, status);
      return status;
    },
    removeGroup: function removeGroup(groupid) {
      var self = this; // TODO migrate to a vue js confirm dialog component

      OC.dialogs.confirm(t('settings', 'You are about to remove the group {group}. The users will NOT be deleted.', {
        group: groupid
      }), t('settings', 'Please confirm the group removal '), function (success) {
        if (success) {
          self.$store.dispatch('removeGroup', groupid);
        }
      });
    },

    /**
     * Dispatch default quota set request
     *
     * @param {string | object} quota Quota in readable format '5 GB' or Object {id: '5 GB', label: '5GB'}
     */
    setDefaultQuota: function setDefaultQuota() {
      var _this3 = this;

      var quota = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : 'none';
      this.$store.dispatch('setAppConfig', {
        app: 'files',
        key: 'default_quota',
        // ensure we only send the preset id
        value: quota.id ? quota.id : quota
      }).then(function () {
        if (_typeof(quota) !== 'object') {
          quota = {
            id: quota,
            label: quota
          };
        }

        _this3.defaultQuota = quota;
      });
    },

    /**
     * Validate quota string to make sure it's a valid human file size
     *
     * @param {string} quota Quota in readable format '5 GB'
     * @return {Promise|boolean}
     */
    validateQuota: function validateQuota(quota) {
      // only used for new presets sent through @Tag
      var validQuota = OC.Util.computerFileSize(quota);

      if (validQuota === null) {
        return this.setDefaultQuota('none');
      } else {
        // unify format output
        return this.setDefaultQuota(OC.Util.humanFileSize(OC.Util.computerFileSize(quota)));
      }
    },

    /**
     * Register a new action for the user menu
     *
     * @param {string} icon the icon class
     * @param {string} text the text to display
     * @param {Function} action the function to run
     * @return {Array}
     */
    registerAction: function registerAction(icon, text, action) {
      this.externalActions.push({
        icon: icon,
        text: text,
        action: action
      });
      return this.externalActions;
    },

    /**
     * Create a new group
     *
     * @param {string} gid The group id
     */
    createGroup: function createGroup(gid) {
      var _this4 = this;

      return _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee2() {
        return regeneratorRuntime.wrap(function _callee2$(_context2) {
          while (1) {
            switch (_context2.prev = _context2.next) {
              case 0:
                if (!(gid.trim() === '')) {
                  _context2.next = 2;
                  break;
                }

                return _context2.abrupt("return");

              case 2:
                _context2.prev = 2;
                _this4.loadingAddGroup = true;
                _context2.next = 6;
                return _this4.$store.dispatch('addGroup', gid.trim());

              case 6:
                _this4.hideAddGroupForm();

                _context2.next = 9;
                return _this4.$router.push({
                  name: 'group',
                  params: {
                    selectedGroup: encodeURIComponent(gid.trim())
                  }
                });

              case 9:
                _context2.next = 14;
                break;

              case 11:
                _context2.prev = 11;
                _context2.t0 = _context2["catch"](2);

                _this4.showAddGroupForm();

              case 14:
                _context2.prev = 14;
                _this4.loadingAddGroup = false;
                return _context2.finish(14);

              case 17:
              case "end":
                return _context2.stop();
            }
          }
        }, _callee2, null, [[2, 11, 14, 17]]);
      }))();
    },
    showAddGroupForm: function showAddGroupForm() {
      var _this5 = this;

      this.$refs.addGroup.editingActive = true;
      this.$refs.addGroup.onMenuToggle(false);
      this.$nextTick(function () {
        _this5.$refs.addGroup.$refs.editingInput.focusInput();
      });
    },
    hideAddGroupForm: function hideAddGroupForm() {
      this.$refs.addGroup.editingActive = false;
      this.$refs.addGroup.editingValue = '';
    },

    /**
     * Format a group to a menu entry
     *
     * @param {object} group the group
     * @return {object}
     */
    formatGroupMenu: function formatGroupMenu(group) {
      var item = {};

      if (typeof group === 'undefined') {
        return {};
      }

      item.id = group.id;
      item.title = group.name;
      item.usercount = group.usercount; // users count for all groups

      if (group.usercount - group.disabled > 0) {
        item.count = group.usercount - group.disabled;
      }

      return item;
    }
  }
});

/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/UserList/UserRow.vue?vue&type=style&index=0&id=77960baa&scoped=true&lang=scss&":
/*!**********************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/UserList/UserRow.vue?vue&type=style&index=0&id=77960baa&scoped=true&lang=scss& ***!
  \**********************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_0__);
// Imports

var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_0___default()(function(i){return i[1]});
// Module
___CSS_LOADER_EXPORT___.push([module.id, ".row--menu-opened[data-v-77960baa] {\n  z-index: 1 !important;\n}\n.row[data-v-77960baa] .multiselect__single {\n  z-index: auto !important;\n}", ""]);
// Exports
/* harmony default export */ __webpack_exports__["default"] = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/UserList/UserRowSimple.vue?vue&type=style&index=0&lang=scss&":
/*!****************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/UserList/UserRowSimple.vue?vue&type=style&index=0&lang=scss& ***!
  \****************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_0__);
// Imports

var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_0___default()(function(i){return i[1]});
// Module
___CSS_LOADER_EXPORT___.push([module.id, ".cellText {\n  overflow: hidden;\n  text-overflow: ellipsis;\n  white-space: nowrap;\n}\n.icon-more {\n  background-color: var(--color-main-background);\n  border: 0;\n}", ""]);
// Exports
/* harmony default export */ __webpack_exports__["default"] = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/Users.vue?vue&type=style&index=0&id=889b7562&lang=scss&scoped=true&":
/*!******************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/Users.vue?vue&type=style&index=0&id=889b7562&lang=scss&scoped=true& ***!
  \******************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_0__);
// Imports

var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_0___default()(function(i){return i[1]});
// Module
___CSS_LOADER_EXPORT___.push([module.id, ".app-navigation__list #addgroup[data-v-889b7562] .app-navigation-entry__utils {\n  display: none;\n}", ""]);
// Exports
/* harmony default export */ __webpack_exports__["default"] = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/UserList.vue?vue&type=style&index=0&id=6cba3aca&scoped=true&lang=css&":
/*!**********************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/UserList.vue?vue&type=style&index=0&id=6cba3aca&scoped=true&lang=css& ***!
  \**********************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_0__);
// Imports

var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_0___default()(function(i){return i[1]});
// Module
___CSS_LOADER_EXPORT___.push([module.id, "\n.modal-wrapper[data-v-6cba3aca] {\n\tmargin: 2vh 0;\n\talign-items: flex-start;\n}\n.modal__content[data-v-6cba3aca] {\n\tdisplay: flex;\n\tpadding: 20px;\n\tflex-direction: column;\n\talign-items: center;\n\ttext-align: center;\n\toverflow: auto;\n}\n.modal__item[data-v-6cba3aca] {\n\tmargin-bottom: 16px;\n\twidth: 100%;\n}\n.modal__item[data-v-6cba3aca]:not(:focus):not(:active) {\n\tborder-color: var(--color-border-dark);\n}\n.modal__item[data-v-6cba3aca] .multiselect {\n\twidth: 100%;\n}\n.user-actions[data-v-6cba3aca] {\n\tmargin-top: 20px;\n}\n.modal__content[data-v-6cba3aca] .multiselect__single {\n\ttext-align: left;\n\tbox-sizing: border-box;\n}\n.modal__content[data-v-6cba3aca] .multiselect__content-wrapper {\n\tbox-sizing: border-box;\n}\n.row[data-v-6cba3aca] .multiselect__single {\n\tz-index: auto !important;\n}\n\n/* fake input for groups validation */\ninput#newgroups[data-v-6cba3aca] {\n\tposition: absolute;\n\topacity: 0;\n\t/* The \"hidden\" input is behind the Multiselect, so in general it does\n\t * not receives clicks. However, with Firefox, after the validation\n\t * fails, it will receive the first click done on it, so its width needs\n\t * to be set to 0 to prevent that (\"pointer-events: none\" does not\n\t * prevent it). */\n\twidth: 0;\n}\n", ""]);
// Exports
/* harmony default export */ __webpack_exports__["default"] = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/UserList/UserRow.vue?vue&type=style&index=0&id=77960baa&scoped=true&lang=scss&":
/*!**************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/UserList/UserRow.vue?vue&type=style&index=0&id=77960baa&scoped=true&lang=scss& ***!
  \**************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! !../../../../../node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js */ "./node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserRow_vue_vue_type_style_index_0_id_77960baa_scoped_true_lang_scss___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! !!../../../../../node_modules/css-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../node_modules/sass-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UserRow.vue?vue&type=style&index=0&id=77960baa&scoped=true&lang=scss& */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/UserList/UserRow.vue?vue&type=style&index=0&id=77960baa&scoped=true&lang=scss&");

            

var options = {};

options.insert = "head";
options.singleton = false;

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserRow_vue_vue_type_style_index_0_id_77960baa_scoped_true_lang_scss___WEBPACK_IMPORTED_MODULE_1__.default, options);



/* harmony default export */ __webpack_exports__["default"] = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserRow_vue_vue_type_style_index_0_id_77960baa_scoped_true_lang_scss___WEBPACK_IMPORTED_MODULE_1__.default.locals || {});

/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/UserList/UserRowSimple.vue?vue&type=style&index=0&lang=scss&":
/*!********************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/UserList/UserRowSimple.vue?vue&type=style&index=0&lang=scss& ***!
  \********************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! !../../../../../node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js */ "./node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserRowSimple_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! !!../../../../../node_modules/css-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../node_modules/sass-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UserRowSimple.vue?vue&type=style&index=0&lang=scss& */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/UserList/UserRowSimple.vue?vue&type=style&index=0&lang=scss&");

            

var options = {};

options.insert = "head";
options.singleton = false;

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserRowSimple_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_1__.default, options);



/* harmony default export */ __webpack_exports__["default"] = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserRowSimple_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_1__.default.locals || {});

/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/Users.vue?vue&type=style&index=0&id=889b7562&lang=scss&scoped=true&":
/*!**********************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/Users.vue?vue&type=style&index=0&id=889b7562&lang=scss&scoped=true& ***!
  \**********************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js */ "./node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Users_vue_vue_type_style_index_0_id_889b7562_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./Users.vue?vue&type=style&index=0&id=889b7562&lang=scss&scoped=true& */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/Users.vue?vue&type=style&index=0&id=889b7562&lang=scss&scoped=true&");

            

var options = {};

options.insert = "head";
options.singleton = false;

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Users_vue_vue_type_style_index_0_id_889b7562_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_1__.default, options);



/* harmony default export */ __webpack_exports__["default"] = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Users_vue_vue_type_style_index_0_id_889b7562_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_1__.default.locals || {});

/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/UserList.vue?vue&type=style&index=0&id=6cba3aca&scoped=true&lang=css&":
/*!**************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/UserList.vue?vue&type=style&index=0&id=6cba3aca&scoped=true&lang=css& ***!
  \**************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js */ "./node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserList_vue_vue_type_style_index_0_id_6cba3aca_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UserList.vue?vue&type=style&index=0&id=6cba3aca&scoped=true&lang=css& */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/UserList.vue?vue&type=style&index=0&id=6cba3aca&scoped=true&lang=css&");

            

var options = {};

options.insert = "head";
options.singleton = false;

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserList_vue_vue_type_style_index_0_id_6cba3aca_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_1__.default, options);



/* harmony default export */ __webpack_exports__["default"] = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserList_vue_vue_type_style_index_0_id_6cba3aca_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_1__.default.locals || {});

/***/ }),

/***/ "./apps/settings/src/components/UserList.vue":
/*!***************************************************!*\
  !*** ./apps/settings/src/components/UserList.vue ***!
  \***************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _UserList_vue_vue_type_template_id_6cba3aca_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./UserList.vue?vue&type=template&id=6cba3aca&scoped=true& */ "./apps/settings/src/components/UserList.vue?vue&type=template&id=6cba3aca&scoped=true&");
/* harmony import */ var _UserList_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./UserList.vue?vue&type=script&lang=js& */ "./apps/settings/src/components/UserList.vue?vue&type=script&lang=js&");
/* harmony import */ var _UserList_vue_vue_type_style_index_0_id_6cba3aca_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./UserList.vue?vue&type=style&index=0&id=6cba3aca&scoped=true&lang=css& */ "./apps/settings/src/components/UserList.vue?vue&type=style&index=0&id=6cba3aca&scoped=true&lang=css&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__.default)(
  _UserList_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__.default,
  _UserList_vue_vue_type_template_id_6cba3aca_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render,
  _UserList_vue_vue_type_template_id_6cba3aca_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "6cba3aca",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/settings/src/components/UserList.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./apps/settings/src/components/UserList/UserRow.vue":
/*!***********************************************************!*\
  !*** ./apps/settings/src/components/UserList/UserRow.vue ***!
  \***********************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _UserRow_vue_vue_type_template_id_77960baa_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./UserRow.vue?vue&type=template&id=77960baa&scoped=true& */ "./apps/settings/src/components/UserList/UserRow.vue?vue&type=template&id=77960baa&scoped=true&");
/* harmony import */ var _UserRow_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./UserRow.vue?vue&type=script&lang=js& */ "./apps/settings/src/components/UserList/UserRow.vue?vue&type=script&lang=js&");
/* harmony import */ var _UserRow_vue_vue_type_style_index_0_id_77960baa_scoped_true_lang_scss___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./UserRow.vue?vue&type=style&index=0&id=77960baa&scoped=true&lang=scss& */ "./apps/settings/src/components/UserList/UserRow.vue?vue&type=style&index=0&id=77960baa&scoped=true&lang=scss&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__.default)(
  _UserRow_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__.default,
  _UserRow_vue_vue_type_template_id_77960baa_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render,
  _UserRow_vue_vue_type_template_id_77960baa_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "77960baa",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/settings/src/components/UserList/UserRow.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./apps/settings/src/components/UserList/UserRowSimple.vue":
/*!*****************************************************************!*\
  !*** ./apps/settings/src/components/UserList/UserRowSimple.vue ***!
  \*****************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _UserRowSimple_vue_vue_type_template_id_ff154a08___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./UserRowSimple.vue?vue&type=template&id=ff154a08& */ "./apps/settings/src/components/UserList/UserRowSimple.vue?vue&type=template&id=ff154a08&");
/* harmony import */ var _UserRowSimple_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./UserRowSimple.vue?vue&type=script&lang=js& */ "./apps/settings/src/components/UserList/UserRowSimple.vue?vue&type=script&lang=js&");
/* harmony import */ var _UserRowSimple_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./UserRowSimple.vue?vue&type=style&index=0&lang=scss& */ "./apps/settings/src/components/UserList/UserRowSimple.vue?vue&type=style&index=0&lang=scss&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__.default)(
  _UserRowSimple_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__.default,
  _UserRowSimple_vue_vue_type_template_id_ff154a08___WEBPACK_IMPORTED_MODULE_0__.render,
  _UserRowSimple_vue_vue_type_template_id_ff154a08___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/settings/src/components/UserList/UserRowSimple.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./apps/settings/src/views/Users.vue":
/*!*******************************************!*\
  !*** ./apps/settings/src/views/Users.vue ***!
  \*******************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _Users_vue_vue_type_template_id_889b7562_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./Users.vue?vue&type=template&id=889b7562&scoped=true& */ "./apps/settings/src/views/Users.vue?vue&type=template&id=889b7562&scoped=true&");
/* harmony import */ var _Users_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./Users.vue?vue&type=script&lang=js& */ "./apps/settings/src/views/Users.vue?vue&type=script&lang=js&");
/* harmony import */ var _Users_vue_vue_type_style_index_0_id_889b7562_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./Users.vue?vue&type=style&index=0&id=889b7562&lang=scss&scoped=true& */ "./apps/settings/src/views/Users.vue?vue&type=style&index=0&id=889b7562&lang=scss&scoped=true&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__.default)(
  _Users_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__.default,
  _Users_vue_vue_type_template_id_889b7562_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render,
  _Users_vue_vue_type_template_id_889b7562_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "889b7562",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/settings/src/views/Users.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./apps/settings/src/components/UserList.vue?vue&type=script&lang=js&":
/*!****************************************************************************!*\
  !*** ./apps/settings/src/components/UserList.vue?vue&type=script&lang=js& ***!
  \****************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserList_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UserList.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/UserList.vue?vue&type=script&lang=js&");
 /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserList_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__.default); 

/***/ }),

/***/ "./apps/settings/src/components/UserList/UserRow.vue?vue&type=script&lang=js&":
/*!************************************************************************************!*\
  !*** ./apps/settings/src/components/UserList/UserRow.vue?vue&type=script&lang=js& ***!
  \************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserRow_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UserRow.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/UserList/UserRow.vue?vue&type=script&lang=js&");
 /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserRow_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__.default); 

/***/ }),

/***/ "./apps/settings/src/components/UserList/UserRowSimple.vue?vue&type=script&lang=js&":
/*!******************************************************************************************!*\
  !*** ./apps/settings/src/components/UserList/UserRowSimple.vue?vue&type=script&lang=js& ***!
  \******************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserRowSimple_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UserRowSimple.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/UserList/UserRowSimple.vue?vue&type=script&lang=js&");
 /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserRowSimple_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__.default); 

/***/ }),

/***/ "./apps/settings/src/views/Users.vue?vue&type=script&lang=js&":
/*!********************************************************************!*\
  !*** ./apps/settings/src/views/Users.vue?vue&type=script&lang=js& ***!
  \********************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Users_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./Users.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/Users.vue?vue&type=script&lang=js&");
 /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Users_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__.default); 

/***/ }),

/***/ "./apps/settings/src/components/UserList/UserRow.vue?vue&type=style&index=0&id=77960baa&scoped=true&lang=scss&":
/*!*********************************************************************************************************************!*\
  !*** ./apps/settings/src/components/UserList/UserRow.vue?vue&type=style&index=0&id=77960baa&scoped=true&lang=scss& ***!
  \*********************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserRow_vue_vue_type_style_index_0_id_77960baa_scoped_true_lang_scss___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/style-loader/dist/cjs.js!../../../../../node_modules/css-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../node_modules/sass-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UserRow.vue?vue&type=style&index=0&id=77960baa&scoped=true&lang=scss& */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/UserList/UserRow.vue?vue&type=style&index=0&id=77960baa&scoped=true&lang=scss&");


/***/ }),

/***/ "./apps/settings/src/components/UserList/UserRowSimple.vue?vue&type=style&index=0&lang=scss&":
/*!***************************************************************************************************!*\
  !*** ./apps/settings/src/components/UserList/UserRowSimple.vue?vue&type=style&index=0&lang=scss& ***!
  \***************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserRowSimple_vue_vue_type_style_index_0_lang_scss___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/style-loader/dist/cjs.js!../../../../../node_modules/css-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../node_modules/sass-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UserRowSimple.vue?vue&type=style&index=0&lang=scss& */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/UserList/UserRowSimple.vue?vue&type=style&index=0&lang=scss&");


/***/ }),

/***/ "./apps/settings/src/views/Users.vue?vue&type=style&index=0&id=889b7562&lang=scss&scoped=true&":
/*!*****************************************************************************************************!*\
  !*** ./apps/settings/src/views/Users.vue?vue&type=style&index=0&id=889b7562&lang=scss&scoped=true& ***!
  \*****************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Users_vue_vue_type_style_index_0_id_889b7562_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader/dist/cjs.js!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./Users.vue?vue&type=style&index=0&id=889b7562&lang=scss&scoped=true& */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/Users.vue?vue&type=style&index=0&id=889b7562&lang=scss&scoped=true&");


/***/ }),

/***/ "./apps/settings/src/components/UserList.vue?vue&type=style&index=0&id=6cba3aca&scoped=true&lang=css&":
/*!************************************************************************************************************!*\
  !*** ./apps/settings/src/components/UserList.vue?vue&type=style&index=0&id=6cba3aca&scoped=true&lang=css& ***!
  \************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_UserList_vue_vue_type_style_index_0_id_6cba3aca_scoped_true_lang_css___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader/dist/cjs.js!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UserList.vue?vue&type=style&index=0&id=6cba3aca&scoped=true&lang=css& */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/UserList.vue?vue&type=style&index=0&id=6cba3aca&scoped=true&lang=css&");


/***/ }),

/***/ "./apps/settings/src/components/UserList.vue?vue&type=template&id=6cba3aca&scoped=true&":
/*!**********************************************************************************************!*\
  !*** ./apps/settings/src/components/UserList.vue?vue&type=template&id=6cba3aca&scoped=true& ***!
  \**********************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* reexport safe */ _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_UserList_vue_vue_type_template_id_6cba3aca_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render; },
/* harmony export */   "staticRenderFns": function() { return /* reexport safe */ _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_UserList_vue_vue_type_template_id_6cba3aca_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns; }
/* harmony export */ });
/* harmony import */ var _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_UserList_vue_vue_type_template_id_6cba3aca_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UserList.vue?vue&type=template&id=6cba3aca&scoped=true& */ "./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/UserList.vue?vue&type=template&id=6cba3aca&scoped=true&");


/***/ }),

/***/ "./apps/settings/src/components/UserList/UserRow.vue?vue&type=template&id=77960baa&scoped=true&":
/*!******************************************************************************************************!*\
  !*** ./apps/settings/src/components/UserList/UserRow.vue?vue&type=template&id=77960baa&scoped=true& ***!
  \******************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* reexport safe */ _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_UserRow_vue_vue_type_template_id_77960baa_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render; },
/* harmony export */   "staticRenderFns": function() { return /* reexport safe */ _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_UserRow_vue_vue_type_template_id_77960baa_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns; }
/* harmony export */ });
/* harmony import */ var _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_UserRow_vue_vue_type_template_id_77960baa_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UserRow.vue?vue&type=template&id=77960baa&scoped=true& */ "./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/UserList/UserRow.vue?vue&type=template&id=77960baa&scoped=true&");


/***/ }),

/***/ "./apps/settings/src/components/UserList/UserRowSimple.vue?vue&type=template&id=ff154a08&":
/*!************************************************************************************************!*\
  !*** ./apps/settings/src/components/UserList/UserRowSimple.vue?vue&type=template&id=ff154a08& ***!
  \************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* reexport safe */ _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_UserRowSimple_vue_vue_type_template_id_ff154a08___WEBPACK_IMPORTED_MODULE_0__.render; },
/* harmony export */   "staticRenderFns": function() { return /* reexport safe */ _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_UserRowSimple_vue_vue_type_template_id_ff154a08___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns; }
/* harmony export */ });
/* harmony import */ var _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_UserRowSimple_vue_vue_type_template_id_ff154a08___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./UserRowSimple.vue?vue&type=template&id=ff154a08& */ "./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/UserList/UserRowSimple.vue?vue&type=template&id=ff154a08&");


/***/ }),

/***/ "./apps/settings/src/views/Users.vue?vue&type=template&id=889b7562&scoped=true&":
/*!**************************************************************************************!*\
  !*** ./apps/settings/src/views/Users.vue?vue&type=template&id=889b7562&scoped=true& ***!
  \**************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* reexport safe */ _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_Users_vue_vue_type_template_id_889b7562_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render; },
/* harmony export */   "staticRenderFns": function() { return /* reexport safe */ _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_Users_vue_vue_type_template_id_889b7562_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns; }
/* harmony export */ });
/* harmony import */ var _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_Users_vue_vue_type_template_id_889b7562_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./Users.vue?vue&type=template&id=889b7562&scoped=true& */ "./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/Users.vue?vue&type=template&id=889b7562&scoped=true&");


/***/ }),

/***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/UserList.vue?vue&type=template&id=6cba3aca&scoped=true&":
/*!*************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/UserList.vue?vue&type=template&id=6cba3aca&scoped=true& ***!
  \*************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* binding */ render; },
/* harmony export */   "staticRenderFns": function() { return /* binding */ staticRenderFns; }
/* harmony export */ });
var render = function () {
  var _vm = this
  var _h = _vm.$createElement
  var _c = _vm._self._c || _h
  return _c(
    "div",
    {
      staticClass: "user-list-grid",
      attrs: { id: "app-content" },
      on: {
        "&scroll": function ($event) {
          return _vm.onScroll.apply(null, arguments)
        },
      },
    },
    [
      _vm.showConfig.showNewUserForm
        ? _c("Modal", { on: { close: _vm.closeModal } }, [
            _c(
              "form",
              {
                staticClass: "modal__content",
                attrs: { id: "new-user", disabled: _vm.loading.all },
                on: {
                  submit: function ($event) {
                    $event.preventDefault()
                    return _vm.createUser.apply(null, arguments)
                  },
                },
              },
              [
                _c("h2", [_vm._v(_vm._s(_vm.t("settings", "New user")))]),
                _vm._v(" "),
                _c("input", {
                  directives: [
                    {
                      name: "model",
                      rawName: "v-model",
                      value: _vm.newUser.id,
                      expression: "newUser.id",
                    },
                  ],
                  ref: "newusername",
                  staticClass: "modal__item",
                  attrs: {
                    id: "newusername",
                    disabled: _vm.settings.newUserGenerateUserID,
                    placeholder: _vm.settings.newUserGenerateUserID
                      ? _vm.t("settings", "Will be autogenerated")
                      : _vm.t("settings", "Username"),
                    autocapitalize: "none",
                    autocomplete: "off",
                    autocorrect: "off",
                    name: "username",
                    pattern: "[a-zA-Z0-9 _\\.@\\-']+",
                    required: "",
                    type: "text",
                  },
                  domProps: { value: _vm.newUser.id },
                  on: {
                    input: function ($event) {
                      if ($event.target.composing) {
                        return
                      }
                      _vm.$set(_vm.newUser, "id", $event.target.value)
                    },
                  },
                }),
                _vm._v(" "),
                _c("input", {
                  directives: [
                    {
                      name: "model",
                      rawName: "v-model",
                      value: _vm.newUser.displayName,
                      expression: "newUser.displayName",
                    },
                  ],
                  staticClass: "modal__item",
                  attrs: {
                    id: "newdisplayname",
                    placeholder: _vm.t("settings", "Display name"),
                    autocapitalize: "none",
                    autocomplete: "off",
                    autocorrect: "off",
                    name: "displayname",
                    type: "text",
                  },
                  domProps: { value: _vm.newUser.displayName },
                  on: {
                    input: function ($event) {
                      if ($event.target.composing) {
                        return
                      }
                      _vm.$set(_vm.newUser, "displayName", $event.target.value)
                    },
                  },
                }),
                _vm._v(" "),
                _c("input", {
                  directives: [
                    {
                      name: "model",
                      rawName: "v-model",
                      value: _vm.newUser.password,
                      expression: "newUser.password",
                    },
                  ],
                  ref: "newuserpassword",
                  staticClass: "modal__item",
                  attrs: {
                    id: "newuserpassword",
                    minlength: _vm.minPasswordLength,
                    placeholder: _vm.t("settings", "Password"),
                    required: _vm.newUser.mailAddress === "",
                    autocapitalize: "none",
                    autocomplete: "new-password",
                    autocorrect: "off",
                    name: "password",
                    type: "password",
                  },
                  domProps: { value: _vm.newUser.password },
                  on: {
                    input: function ($event) {
                      if ($event.target.composing) {
                        return
                      }
                      _vm.$set(_vm.newUser, "password", $event.target.value)
                    },
                  },
                }),
                _vm._v(" "),
                _c("input", {
                  directives: [
                    {
                      name: "model",
                      rawName: "v-model",
                      value: _vm.newUser.mailAddress,
                      expression: "newUser.mailAddress",
                    },
                  ],
                  staticClass: "modal__item",
                  attrs: {
                    id: "newemail",
                    placeholder: _vm.t("settings", "Email"),
                    required:
                      _vm.newUser.password === "" ||
                      _vm.settings.newUserRequireEmail,
                    autocapitalize: "none",
                    autocomplete: "off",
                    autocorrect: "off",
                    name: "email",
                    type: "email",
                  },
                  domProps: { value: _vm.newUser.mailAddress },
                  on: {
                    input: function ($event) {
                      if ($event.target.composing) {
                        return
                      }
                      _vm.$set(_vm.newUser, "mailAddress", $event.target.value)
                    },
                  },
                }),
                _vm._v(" "),
                _c(
                  "div",
                  { staticClass: "groups modal__item" },
                  [
                    !_vm.settings.isAdmin
                      ? _c("input", {
                          class: { "icon-loading-small": _vm.loading.groups },
                          attrs: {
                            id: "newgroups",
                            required: !_vm.settings.isAdmin,
                            tabindex: "-1",
                            type: "text",
                          },
                          domProps: { value: _vm.newUser.groups },
                        })
                      : _vm._e(),
                    _vm._v(" "),
                    _c(
                      "Multiselect",
                      {
                        staticClass: "multiselect-vue",
                        attrs: {
                          "close-on-select": false,
                          disabled: _vm.loading.groups || _vm.loading.all,
                          multiple: true,
                          options: _vm.canAddGroups,
                          placeholder: _vm.t("settings", "Add user to group"),
                          "tag-width": 60,
                          taggable: true,
                          label: "name",
                          "tag-placeholder": "create",
                          "track-by": "id",
                        },
                        on: { tag: _vm.createGroup },
                        model: {
                          value: _vm.newUser.groups,
                          callback: function ($$v) {
                            _vm.$set(_vm.newUser, "groups", $$v)
                          },
                          expression: "newUser.groups",
                        },
                      },
                      [
                        _c(
                          "span",
                          { attrs: { slot: "noResult" }, slot: "noResult" },
                          [_vm._v(_vm._s(_vm.t("settings", "No results")))]
                        ),
                      ]
                    ),
                  ],
                  1
                ),
                _vm._v(" "),
                _vm.subAdminsGroups.length > 0 && _vm.settings.isAdmin
                  ? _c(
                      "div",
                      { staticClass: "subadmins modal__item" },
                      [
                        _c(
                          "Multiselect",
                          {
                            staticClass: "multiselect-vue",
                            attrs: {
                              "close-on-select": false,
                              multiple: true,
                              options: _vm.subAdminsGroups,
                              placeholder: _vm.t(
                                "settings",
                                "Set user as admin for"
                              ),
                              "tag-width": 60,
                              label: "name",
                              "track-by": "id",
                            },
                            model: {
                              value: _vm.newUser.subAdminsGroups,
                              callback: function ($$v) {
                                _vm.$set(_vm.newUser, "subAdminsGroups", $$v)
                              },
                              expression: "newUser.subAdminsGroups",
                            },
                          },
                          [
                            _c(
                              "span",
                              { attrs: { slot: "noResult" }, slot: "noResult" },
                              [_vm._v(_vm._s(_vm.t("settings", "No results")))]
                            ),
                          ]
                        ),
                      ],
                      1
                    )
                  : _vm._e(),
                _vm._v(" "),
                _c(
                  "div",
                  { staticClass: "quota modal__item" },
                  [
                    _c("Multiselect", {
                      staticClass: "multiselect-vue",
                      attrs: {
                        "allow-empty": false,
                        options: _vm.quotaOptions,
                        placeholder: _vm.t("settings", "Select user quota"),
                        taggable: true,
                        label: "label",
                        "track-by": "id",
                      },
                      on: { tag: _vm.validateQuota },
                      model: {
                        value: _vm.newUser.quota,
                        callback: function ($$v) {
                          _vm.$set(_vm.newUser, "quota", $$v)
                        },
                        expression: "newUser.quota",
                      },
                    }),
                  ],
                  1
                ),
                _vm._v(" "),
                _vm.showConfig.showLanguages
                  ? _c(
                      "div",
                      { staticClass: "languages modal__item" },
                      [
                        _c("Multiselect", {
                          staticClass: "multiselect-vue",
                          attrs: {
                            "allow-empty": false,
                            options: _vm.languages,
                            placeholder: _vm.t("settings", "Default language"),
                            "group-label": "label",
                            "group-values": "languages",
                            label: "name",
                            "track-by": "code",
                          },
                          model: {
                            value: _vm.newUser.language,
                            callback: function ($$v) {
                              _vm.$set(_vm.newUser, "language", $$v)
                            },
                            expression: "newUser.language",
                          },
                        }),
                      ],
                      1
                    )
                  : _vm._e(),
                _vm._v(" "),
                _vm.showConfig.showStoragePath
                  ? _c("div", { staticClass: "storageLocation" })
                  : _vm._e(),
                _vm._v(" "),
                _vm.showConfig.showUserBackend
                  ? _c("div", { staticClass: "userBackend" })
                  : _vm._e(),
                _vm._v(" "),
                _vm.showConfig.showLastLogin
                  ? _c("div", { staticClass: "lastLogin" })
                  : _vm._e(),
                _vm._v(" "),
                _c("div", { staticClass: "user-actions" }, [
                  _c(
                    "button",
                    {
                      staticClass: "button primary",
                      attrs: { id: "newsubmit", type: "submit", value: "" },
                    },
                    [
                      _vm._v(
                        "\n\t\t\t\t\t" +
                          _vm._s(_vm.t("settings", "Add a new user")) +
                          "\n\t\t\t\t"
                      ),
                    ]
                  ),
                ]),
              ]
            ),
          ])
        : _vm._e(),
      _vm._v(" "),
      _c(
        "div",
        {
          staticClass: "row",
          class: { sticky: _vm.scrolled && !_vm.showConfig.showNewUserForm },
          attrs: { id: "grid-header" },
        },
        [
          _c("div", { staticClass: "avatar", attrs: { id: "headerAvatar" } }),
          _vm._v(" "),
          _c("div", { staticClass: "name", attrs: { id: "headerName" } }, [
            _vm._v(
              "\n\t\t\t" + _vm._s(_vm.t("settings", "Username")) + "\n\n\t\t\t"
            ),
            _c("div", { staticClass: "subtitle" }, [
              _vm._v(
                "\n\t\t\t\t" +
                  _vm._s(_vm.t("settings", "Display name")) +
                  "\n\t\t\t"
              ),
            ]),
          ]),
          _vm._v(" "),
          _c(
            "div",
            { staticClass: "password", attrs: { id: "headerPassword" } },
            [
              _vm._v(
                "\n\t\t\t" + _vm._s(_vm.t("settings", "Password")) + "\n\t\t"
              ),
            ]
          ),
          _vm._v(" "),
          _c(
            "div",
            { staticClass: "mailAddress", attrs: { id: "headerAddress" } },
            [_vm._v("\n\t\t\t" + _vm._s(_vm.t("settings", "Email")) + "\n\t\t")]
          ),
          _vm._v(" "),
          _c("div", { staticClass: "groups", attrs: { id: "headerGroups" } }, [
            _vm._v("\n\t\t\t" + _vm._s(_vm.t("settings", "Groups")) + "\n\t\t"),
          ]),
          _vm._v(" "),
          _vm.subAdminsGroups.length > 0 && _vm.settings.isAdmin
            ? _c(
                "div",
                { staticClass: "subadmins", attrs: { id: "headerSubAdmins" } },
                [
                  _vm._v(
                    "\n\t\t\t" +
                      _vm._s(_vm.t("settings", "Group admin for")) +
                      "\n\t\t"
                  ),
                ]
              )
            : _vm._e(),
          _vm._v(" "),
          _c("div", { staticClass: "quota", attrs: { id: "headerQuota" } }, [
            _vm._v("\n\t\t\t" + _vm._s(_vm.t("settings", "Quota")) + "\n\t\t"),
          ]),
          _vm._v(" "),
          _vm.showConfig.showLanguages
            ? _c(
                "div",
                { staticClass: "languages", attrs: { id: "headerLanguages" } },
                [
                  _vm._v(
                    "\n\t\t\t" +
                      _vm._s(_vm.t("settings", "Language")) +
                      "\n\t\t"
                  ),
                ]
              )
            : _vm._e(),
          _vm._v(" "),
          _vm.showConfig.showUserBackend || _vm.showConfig.showStoragePath
            ? _c("div", { staticClass: "headerUserBackend userBackend" }, [
                _vm.showConfig.showUserBackend
                  ? _c("div", { staticClass: "userBackend" }, [
                      _vm._v(
                        "\n\t\t\t\t" +
                          _vm._s(_vm.t("settings", "User backend")) +
                          "\n\t\t\t"
                      ),
                    ])
                  : _vm._e(),
                _vm._v(" "),
                _vm.showConfig.showStoragePath
                  ? _c("div", { staticClass: "subtitle storageLocation" }, [
                      _vm._v(
                        "\n\t\t\t\t" +
                          _vm._s(_vm.t("settings", "Storage location")) +
                          "\n\t\t\t"
                      ),
                    ])
                  : _vm._e(),
              ])
            : _vm._e(),
          _vm._v(" "),
          _vm.showConfig.showLastLogin
            ? _c("div", { staticClass: "headerLastLogin lastLogin" }, [
                _vm._v(
                  "\n\t\t\t" +
                    _vm._s(_vm.t("settings", "Last login")) +
                    "\n\t\t"
                ),
              ])
            : _vm._e(),
          _vm._v(" "),
          _c("div", { staticClass: "userActions" }),
        ]
      ),
      _vm._v(" "),
      _vm._l(_vm.filteredUsers, function (user) {
        return _c("user-row", {
          key: user.id,
          attrs: {
            "external-actions": _vm.externalActions,
            groups: _vm.groups,
            languages: _vm.languages,
            "quota-options": _vm.quotaOptions,
            settings: _vm.settings,
            "show-config": _vm.showConfig,
            "sub-admins-groups": _vm.subAdminsGroups,
            user: user,
          },
        })
      }),
      _vm._v(" "),
      _c(
        "InfiniteLoading",
        { ref: "infiniteLoading", on: { infinite: _vm.infiniteHandler } },
        [
          _c("div", { attrs: { slot: "spinner" }, slot: "spinner" }, [
            _c("div", { staticClass: "users-icon-loading icon-loading" }),
          ]),
          _vm._v(" "),
          _c("div", { attrs: { slot: "no-more" }, slot: "no-more" }, [
            _c("div", { staticClass: "users-list-end" }),
          ]),
          _vm._v(" "),
          _c("div", { attrs: { slot: "no-results" }, slot: "no-results" }, [
            _c("div", { attrs: { id: "emptycontent" } }, [
              _c("div", { staticClass: "icon-contacts-dark" }),
              _vm._v(" "),
              _c("h2", [_vm._v(_vm._s(_vm.t("settings", "No users in here")))]),
            ]),
          ]),
        ]
      ),
    ],
    2
  )
}
var staticRenderFns = []
render._withStripped = true



/***/ }),

/***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/UserList/UserRow.vue?vue&type=template&id=77960baa&scoped=true&":
/*!*********************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/UserList/UserRow.vue?vue&type=template&id=77960baa&scoped=true& ***!
  \*********************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* binding */ render; },
/* harmony export */   "staticRenderFns": function() { return /* binding */ staticRenderFns; }
/* harmony export */ });
var render = function () {
  var _vm = this
  var _h = _vm.$createElement
  var _c = _vm._self._c || _h
  return Object.keys(_vm.user).length === 1
    ? _c("div", { staticClass: "row", attrs: { "data-id": _vm.user.id } }, [
        _c(
          "div",
          {
            staticClass: "avatar",
            class: {
              "icon-loading-small":
                _vm.loading.delete || _vm.loading.disable || _vm.loading.wipe,
            },
          },
          [
            !_vm.loading.delete && !_vm.loading.disable && !_vm.loading.wipe
              ? _c("img", {
                  attrs: {
                    src: _vm.generateAvatar(_vm.user.id, 32),
                    srcset:
                      _vm.generateAvatar(_vm.user.id, 64) +
                      " 2x, " +
                      _vm.generateAvatar(_vm.user.id, 128) +
                      " 4x",
                    alt: "",
                    height: "32",
                    width: "32",
                  },
                })
              : _vm._e(),
          ]
        ),
        _vm._v(" "),
        _c("div", { staticClass: "name" }, [
          _vm._v("\n\t\t" + _vm._s(_vm.user.id) + "\n\t"),
        ]),
        _vm._v(" "),
        _c("div", { staticClass: "obfuscated" }, [
          _vm._v(
            "\n\t\t" +
              _vm._s(
                _vm.t(
                  "settings",
                  "You do not have permissions to see the details of this user"
                )
              ) +
              "\n\t"
          ),
        ]),
      ])
    : !_vm.editing
    ? _c("UserRowSimple", {
        class: { "row--menu-opened": _vm.openedMenu },
        attrs: {
          editing: _vm.editing,
          "feedback-message": _vm.feedbackMessage,
          groups: _vm.groups,
          languages: _vm.languages,
          loading: _vm.loading,
          "opened-menu": _vm.openedMenu,
          settings: _vm.settings,
          "show-config": _vm.showConfig,
          "sub-admins-groups": _vm.subAdminsGroups,
          "user-actions": _vm.userActions,
          user: _vm.user,
        },
        on: {
          "update:editing": function ($event) {
            _vm.editing = $event
          },
          "update:openedMenu": function ($event) {
            _vm.openedMenu = $event
          },
          "update:opened-menu": function ($event) {
            _vm.openedMenu = $event
          },
        },
      })
    : _c(
        "div",
        {
          staticClass: "row row--editable",
          class: {
            disabled: _vm.loading.delete || _vm.loading.disable,
            "row--menu-opened": _vm.openedMenu,
          },
          attrs: { "data-id": _vm.user.id },
        },
        [
          _c(
            "div",
            {
              staticClass: "avatar",
              class: {
                "icon-loading-small":
                  _vm.loading.delete || _vm.loading.disable || _vm.loading.wipe,
              },
            },
            [
              !_vm.loading.delete && !_vm.loading.disable && !_vm.loading.wipe
                ? _c("img", {
                    attrs: {
                      src: _vm.generateAvatar(_vm.user.id, 32),
                      srcset:
                        _vm.generateAvatar(_vm.user.id, 64) +
                        " 2x, " +
                        _vm.generateAvatar(_vm.user.id, 128) +
                        " 4x",
                      alt: "",
                      height: "32",
                      width: "32",
                    },
                  })
                : _vm._e(),
            ]
          ),
          _vm._v(" "),
          _vm.user.backendCapabilities.setDisplayName
            ? _c("div", { staticClass: "displayName" }, [
                _c(
                  "form",
                  {
                    staticClass: "displayName",
                    class: { "icon-loading-small": _vm.loading.displayName },
                    on: {
                      submit: function ($event) {
                        $event.preventDefault()
                        return _vm.updateDisplayName.apply(null, arguments)
                      },
                    },
                  },
                  [
                    _c("input", {
                      ref: "displayName",
                      attrs: {
                        id: "displayName" + _vm.user.id + _vm.rand,
                        disabled: _vm.loading.displayName || _vm.loading.all,
                        autocapitalize: "off",
                        autocomplete: "off",
                        autocorrect: "off",
                        spellcheck: "false",
                        type: "text",
                      },
                      domProps: { value: _vm.user.displayname },
                    }),
                    _vm._v(" "),
                    _c("input", {
                      staticClass: "icon-confirm",
                      attrs: { type: "submit", value: "" },
                    }),
                  ]
                ),
              ])
            : _c("div", { staticClass: "name" }, [
                _vm._v("\n\t\t" + _vm._s(_vm.user.id) + "\n\t\t"),
                _c("div", { staticClass: "displayName subtitle" }, [
                  _c(
                    "div",
                    {
                      directives: [
                        {
                          name: "tooltip",
                          rawName: "v-tooltip",
                          value:
                            _vm.user.displayname.length > 20
                              ? _vm.user.displayname
                              : "",
                          expression:
                            "user.displayname.length > 20 ? user.displayname : ''",
                        },
                      ],
                      staticClass: "cellText",
                    },
                    [
                      _vm._v(
                        "\n\t\t\t\t" + _vm._s(_vm.user.displayname) + "\n\t\t\t"
                      ),
                    ]
                  ),
                ]),
              ]),
          _vm._v(" "),
          _vm.settings.canChangePassword &&
          _vm.user.backendCapabilities.setPassword
            ? _c(
                "form",
                {
                  staticClass: "password",
                  class: { "icon-loading-small": _vm.loading.password },
                  on: {
                    submit: function ($event) {
                      $event.preventDefault()
                      return _vm.updatePassword.apply(null, arguments)
                    },
                  },
                },
                [
                  _c("input", {
                    ref: "password",
                    attrs: {
                      id: "password" + _vm.user.id + _vm.rand,
                      disabled: _vm.loading.password || _vm.loading.all,
                      minlength: _vm.minPasswordLength,
                      placeholder: _vm.t("settings", "Add new password"),
                      autocapitalize: "off",
                      autocomplete: "new-password",
                      autocorrect: "off",
                      required: "",
                      spellcheck: "false",
                      type: "password",
                      value: "",
                    },
                  }),
                  _vm._v(" "),
                  _c("input", {
                    staticClass: "icon-confirm",
                    attrs: { type: "submit", value: "" },
                  }),
                ]
              )
            : _c("div"),
          _vm._v(" "),
          _c(
            "form",
            {
              staticClass: "mailAddress",
              class: { "icon-loading-small": _vm.loading.mailAddress },
              on: {
                submit: function ($event) {
                  $event.preventDefault()
                  return _vm.updateEmail.apply(null, arguments)
                },
              },
            },
            [
              _c("input", {
                ref: "mailAddress",
                attrs: {
                  id: "mailAddress" + _vm.user.id + _vm.rand,
                  disabled: _vm.loading.mailAddress || _vm.loading.all,
                  placeholder: _vm.t("settings", "Add new email address"),
                  autocapitalize: "off",
                  autocomplete: "new-password",
                  autocorrect: "off",
                  spellcheck: "false",
                  type: "email",
                },
                domProps: { value: _vm.user.email },
              }),
              _vm._v(" "),
              _c("input", {
                staticClass: "icon-confirm",
                attrs: { type: "submit", value: "" },
              }),
            ]
          ),
          _vm._v(" "),
          _c(
            "div",
            {
              staticClass: "groups",
              class: { "icon-loading-small": _vm.loading.groups },
            },
            [
              _c(
                "Multiselect",
                {
                  staticClass: "multiselect-vue",
                  attrs: {
                    "close-on-select": false,
                    disabled: _vm.loading.groups || _vm.loading.all,
                    limit: 2,
                    multiple: true,
                    options: _vm.availableGroups,
                    placeholder: _vm.t("settings", "Add user to group"),
                    "tag-width": 60,
                    taggable: _vm.settings.isAdmin,
                    value: _vm.userGroups,
                    label: "name",
                    "tag-placeholder": "create",
                    "track-by": "id",
                  },
                  on: {
                    remove: _vm.removeUserGroup,
                    select: _vm.addUserGroup,
                    tag: _vm.createGroup,
                  },
                },
                [
                  _c(
                    "span",
                    { attrs: { slot: "noResult" }, slot: "noResult" },
                    [_vm._v(_vm._s(_vm.t("settings", "No results")))]
                  ),
                ]
              ),
            ],
            1
          ),
          _vm._v(" "),
          _vm.subAdminsGroups.length > 0 && _vm.settings.isAdmin
            ? _c(
                "div",
                {
                  staticClass: "subadmins",
                  class: { "icon-loading-small": _vm.loading.subadmins },
                },
                [
                  _c(
                    "Multiselect",
                    {
                      staticClass: "multiselect-vue",
                      attrs: {
                        "close-on-select": false,
                        disabled: _vm.loading.subadmins || _vm.loading.all,
                        limit: 2,
                        multiple: true,
                        options: _vm.subAdminsGroups,
                        placeholder: _vm.t("settings", "Set user as admin for"),
                        "tag-width": 60,
                        value: _vm.userSubAdminsGroups,
                        label: "name",
                        "track-by": "id",
                      },
                      on: {
                        remove: _vm.removeUserSubAdmin,
                        select: _vm.addUserSubAdmin,
                      },
                    },
                    [
                      _c(
                        "span",
                        { attrs: { slot: "noResult" }, slot: "noResult" },
                        [_vm._v(_vm._s(_vm.t("settings", "No results")))]
                      ),
                    ]
                  ),
                ],
                1
              )
            : _vm._e(),
          _vm._v(" "),
          _c(
            "div",
            {
              directives: [
                {
                  name: "tooltip",
                  rawName: "v-tooltip.auto",
                  value: _vm.usedSpace,
                  expression: "usedSpace",
                  modifiers: { auto: true },
                },
              ],
              staticClass: "quota",
              class: { "icon-loading-small": _vm.loading.quota },
            },
            [
              _c("Multiselect", {
                staticClass: "multiselect-vue",
                attrs: {
                  "allow-empty": false,
                  disabled: _vm.loading.quota || _vm.loading.all,
                  options: _vm.quotaOptions,
                  placeholder: _vm.t("settings", "Select user quota"),
                  taggable: true,
                  value: _vm.userQuota,
                  label: "label",
                  "tag-placeholder": "create",
                  "track-by": "id",
                },
                on: { input: _vm.setUserQuota, tag: _vm.validateQuota },
              }),
            ],
            1
          ),
          _vm._v(" "),
          _vm.showConfig.showLanguages
            ? _c(
                "div",
                {
                  staticClass: "languages",
                  class: { "icon-loading-small": _vm.loading.languages },
                },
                [
                  _c("Multiselect", {
                    staticClass: "multiselect-vue",
                    attrs: {
                      "allow-empty": false,
                      disabled: _vm.loading.languages || _vm.loading.all,
                      options: _vm.languages,
                      placeholder: _vm.t("settings", "No language set"),
                      value: _vm.userLanguage,
                      "group-label": "label",
                      "group-values": "languages",
                      label: "name",
                      "track-by": "code",
                    },
                    on: { input: _vm.setUserLanguage },
                  }),
                ],
                1
              )
            : _vm._e(),
          _vm._v(" "),
          _vm.showConfig.showStoragePath || _vm.showConfig.showUserBackend
            ? _c("div", { staticClass: "storageLocation" })
            : _vm._e(),
          _vm._v(" "),
          _vm.showConfig.showLastLogin ? _c("div") : _vm._e(),
          _vm._v(" "),
          _c("div", { staticClass: "userActions" }, [
            !_vm.loading.all
              ? _c(
                  "div",
                  { staticClass: "toggleUserActions" },
                  [
                    _c(
                      "Actions",
                      [
                        _c(
                          "ActionButton",
                          {
                            attrs: { icon: "icon-checkmark" },
                            on: {
                              click: function ($event) {
                                _vm.editing = false
                              },
                            },
                          },
                          [
                            _vm._v(
                              "\n\t\t\t\t\t" +
                                _vm._s(_vm.t("settings", "Done")) +
                                "\n\t\t\t\t"
                            ),
                          ]
                        ),
                      ],
                      1
                    ),
                    _vm._v(" "),
                    _c(
                      "div",
                      {
                        directives: [
                          {
                            name: "click-outside",
                            rawName: "v-click-outside",
                            value: _vm.hideMenu,
                            expression: "hideMenu",
                          },
                        ],
                        staticClass: "userPopoverMenuWrapper",
                      },
                      [
                        _c("div", {
                          staticClass: "icon-more",
                          on: { click: _vm.toggleMenu },
                        }),
                        _vm._v(" "),
                        _c(
                          "div",
                          {
                            staticClass: "popovermenu",
                            class: { open: _vm.openedMenu },
                          },
                          [
                            _c("PopoverMenu", {
                              attrs: { menu: _vm.userActions },
                            }),
                          ],
                          1
                        ),
                      ]
                    ),
                  ],
                  1
                )
              : _vm._e(),
            _vm._v(" "),
            _c(
              "div",
              {
                staticClass: "feedback",
                style: { opacity: _vm.feedbackMessage !== "" ? 1 : 0 },
              },
              [
                _c("div", { staticClass: "icon-checkmark" }),
                _vm._v("\n\t\t\t" + _vm._s(_vm.feedbackMessage) + "\n\t\t"),
              ]
            ),
          ]),
        ]
      )
}
var staticRenderFns = []
render._withStripped = true



/***/ }),

/***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/UserList/UserRowSimple.vue?vue&type=template&id=ff154a08&":
/*!***************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/UserList/UserRowSimple.vue?vue&type=template&id=ff154a08& ***!
  \***************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* binding */ render; },
/* harmony export */   "staticRenderFns": function() { return /* binding */ staticRenderFns; }
/* harmony export */ });
var render = function () {
  var _vm = this
  var _h = _vm.$createElement
  var _c = _vm._self._c || _h
  return _c(
    "div",
    {
      staticClass: "row",
      class: { disabled: _vm.loading.delete || _vm.loading.disable },
      attrs: { "data-id": _vm.user.id },
    },
    [
      _c(
        "div",
        {
          staticClass: "avatar",
          class: {
            "icon-loading-small":
              _vm.loading.delete || _vm.loading.disable || _vm.loading.wipe,
          },
        },
        [
          !_vm.loading.delete && !_vm.loading.disable && !_vm.loading.wipe
            ? _c("img", {
                attrs: {
                  alt: "",
                  width: "32",
                  height: "32",
                  src: _vm.generateAvatar(_vm.user.id, 32),
                  srcset:
                    _vm.generateAvatar(_vm.user.id, 64) +
                    " 2x, " +
                    _vm.generateAvatar(_vm.user.id, 128) +
                    " 4x",
                },
              })
            : _vm._e(),
        ]
      ),
      _vm._v(" "),
      _c("div", { staticClass: "name" }, [
        _vm._v("\n\t\t" + _vm._s(_vm.user.id) + "\n\t\t"),
        _c("div", { staticClass: "displayName subtitle" }, [
          _c(
            "div",
            {
              directives: [
                {
                  name: "tooltip",
                  rawName: "v-tooltip",
                  value:
                    _vm.user.displayname.length > 20
                      ? _vm.user.displayname
                      : "",
                  expression:
                    "user.displayname.length > 20 ? user.displayname : ''",
                },
              ],
              staticClass: "cellText",
            },
            [_vm._v("\n\t\t\t\t" + _vm._s(_vm.user.displayname) + "\n\t\t\t")]
          ),
        ]),
      ]),
      _vm._v(" "),
      _c("div"),
      _vm._v(" "),
      _c("div", { staticClass: "mailAddress" }, [
        _c(
          "div",
          {
            directives: [
              {
                name: "tooltip",
                rawName: "v-tooltip",
                value:
                  _vm.user.email !== null && _vm.user.email.length > 20
                    ? _vm.user.email
                    : "",
                expression:
                  "user.email !== null && user.email.length > 20 ? user.email : ''",
              },
            ],
            staticClass: "cellText",
          },
          [_vm._v("\n\t\t\t" + _vm._s(_vm.user.email) + "\n\t\t")]
        ),
      ]),
      _vm._v(" "),
      _c("div", { staticClass: "groups" }, [
        _vm._v("\n\t\t" + _vm._s(_vm.userGroupsLabels) + "\n\t"),
      ]),
      _vm._v(" "),
      _vm.subAdminsGroups.length > 0 && _vm.settings.isAdmin
        ? _c("div", { staticClass: "subAdminsGroups" }, [
            _vm._v("\n\t\t" + _vm._s(_vm.userSubAdminsGroupsLabels) + "\n\t"),
          ])
        : _vm._e(),
      _vm._v(" "),
      _c("div", { staticClass: "userQuota" }, [
        _c("div", { staticClass: "quota" }, [
          _vm._v(
            "\n\t\t\t" +
              _vm._s(_vm.userQuota) +
              " (" +
              _vm._s(_vm.usedSpace) +
              ")\n\t\t\t"
          ),
          _c("progress", {
            staticClass: "quota-user-progress",
            class: { warn: _vm.usedQuota > 80 },
            attrs: { max: "100" },
            domProps: { value: _vm.usedQuota },
          }),
        ]),
      ]),
      _vm._v(" "),
      _vm.showConfig.showLanguages
        ? _c("div", { staticClass: "languages" }, [
            _vm._v("\n\t\t" + _vm._s(_vm.userLanguage.name) + "\n\t"),
          ])
        : _vm._e(),
      _vm._v(" "),
      _vm.showConfig.showUserBackend || _vm.showConfig.showStoragePath
        ? _c("div", { staticClass: "userBackend" }, [
            _vm.showConfig.showUserBackend
              ? _c("div", { staticClass: "userBackend" }, [
                  _vm._v("\n\t\t\t" + _vm._s(_vm.user.backend) + "\n\t\t"),
                ])
              : _vm._e(),
            _vm._v(" "),
            _vm.showConfig.showStoragePath
              ? _c(
                  "div",
                  {
                    directives: [
                      {
                        name: "tooltip",
                        rawName: "v-tooltip",
                        value: _vm.user.storageLocation,
                        expression: "user.storageLocation",
                      },
                    ],
                    staticClass: "storageLocation subtitle",
                  },
                  [
                    _vm._v(
                      "\n\t\t\t" + _vm._s(_vm.user.storageLocation) + "\n\t\t"
                    ),
                  ]
                )
              : _vm._e(),
          ])
        : _vm._e(),
      _vm._v(" "),
      _vm.showConfig.showLastLogin
        ? _c(
            "div",
            {
              directives: [
                {
                  name: "tooltip",
                  rawName: "v-tooltip.auto",
                  value: _vm.userLastLoginTooltip,
                  expression: "userLastLoginTooltip",
                  modifiers: { auto: true },
                },
              ],
              staticClass: "lastLogin",
            },
            [_vm._v("\n\t\t" + _vm._s(_vm.userLastLogin) + "\n\t")]
          )
        : _vm._e(),
      _vm._v(" "),
      _c("div", { staticClass: "userActions" }, [
        _vm.canEdit && !_vm.loading.all
          ? _c(
              "div",
              { staticClass: "toggleUserActions" },
              [
                _c(
                  "Actions",
                  [
                    _c(
                      "ActionButton",
                      {
                        attrs: { icon: "icon-rename" },
                        on: { click: _vm.toggleEdit },
                      },
                      [
                        _vm._v(
                          "\n\t\t\t\t\t" +
                            _vm._s(_vm.t("settings", "Edit User")) +
                            "\n\t\t\t\t"
                        ),
                      ]
                    ),
                  ],
                  1
                ),
                _vm._v(" "),
                _c("div", { staticClass: "userPopoverMenuWrapper" }, [
                  _c("button", {
                    directives: [
                      {
                        name: "click-outside",
                        rawName: "v-click-outside",
                        value: _vm.hideMenu,
                        expression: "hideMenu",
                      },
                    ],
                    staticClass: "icon-more",
                    attrs: {
                      "aria-label": _vm.t(
                        "settings",
                        "Toggle user actions menu"
                      ),
                    },
                    on: {
                      click: function ($event) {
                        $event.preventDefault()
                        return _vm.toggleMenu.apply(null, arguments)
                      },
                    },
                  }),
                  _vm._v(" "),
                  _c(
                    "div",
                    {
                      staticClass: "popovermenu",
                      class: { open: _vm.openedMenu },
                      attrs: { "aria-expanded": _vm.openedMenu },
                    },
                    [_c("PopoverMenu", { attrs: { menu: _vm.userActions } })],
                    1
                  ),
                ]),
              ],
              1
            )
          : _vm._e(),
        _vm._v(" "),
        _c(
          "div",
          {
            staticClass: "feedback",
            style: { opacity: _vm.feedbackMessage !== "" ? 1 : 0 },
          },
          [
            _c("div", { staticClass: "icon-checkmark" }),
            _vm._v("\n\t\t\t" + _vm._s(_vm.feedbackMessage) + "\n\t\t"),
          ]
        ),
      ]),
    ]
  )
}
var staticRenderFns = []
render._withStripped = true



/***/ }),

/***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/Users.vue?vue&type=template&id=889b7562&scoped=true&":
/*!*****************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/views/Users.vue?vue&type=template&id=889b7562&scoped=true& ***!
  \*****************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* binding */ render; },
/* harmony export */   "staticRenderFns": function() { return /* binding */ staticRenderFns; }
/* harmony export */ });
var render = function () {
  var _vm = this
  var _h = _vm.$createElement
  var _c = _vm._self._c || _h
  return _c(
    "Content",
    {
      attrs: {
        "app-name": "settings",
        "navigation-class": { "icon-loading": _vm.loadingAddGroup },
      },
    },
    [
      _c(
        "AppNavigation",
        {
          scopedSlots: _vm._u([
            {
              key: "list",
              fn: function () {
                return [
                  _c("AppNavigationItem", {
                    ref: "addGroup",
                    attrs: {
                      id: "addgroup",
                      "edit-placeholder": _vm.t("settings", "Enter group name"),
                      editable: true,
                      loading: _vm.loadingAddGroup,
                      title: _vm.t("settings", "Add group"),
                      icon: "icon-add",
                    },
                    on: {
                      click: _vm.showAddGroupForm,
                      "update:title": _vm.createGroup,
                    },
                  }),
                  _vm._v(" "),
                  _c(
                    "AppNavigationItem",
                    {
                      attrs: {
                        id: "everyone",
                        exact: true,
                        title: _vm.t("settings", "Active users"),
                        to: { name: "users" },
                        icon: "icon-contacts-dark",
                      },
                    },
                    [
                      _vm.userCount > 0
                        ? _c(
                            "AppNavigationCounter",
                            { attrs: { slot: "counter" }, slot: "counter" },
                            [
                              _vm._v(
                                "\n\t\t\t\t\t" +
                                  _vm._s(_vm.userCount) +
                                  "\n\t\t\t\t"
                              ),
                            ]
                          )
                        : _vm._e(),
                    ],
                    1
                  ),
                  _vm._v(" "),
                  _vm.settings.isAdmin
                    ? _c(
                        "AppNavigationItem",
                        {
                          attrs: {
                            id: "admin",
                            exact: true,
                            title: _vm.t("settings", "Admins"),
                            to: {
                              name: "group",
                              params: { selectedGroup: "admin" },
                            },
                            icon: "icon-user-admin",
                          },
                        },
                        [
                          _vm.adminGroupMenu.count
                            ? _c(
                                "AppNavigationCounter",
                                { attrs: { slot: "counter" }, slot: "counter" },
                                [
                                  _vm._v(
                                    "\n\t\t\t\t\t" +
                                      _vm._s(_vm.adminGroupMenu.count) +
                                      "\n\t\t\t\t"
                                  ),
                                ]
                              )
                            : _vm._e(),
                        ],
                        1
                      )
                    : _vm._e(),
                  _vm._v(" "),
                  _vm.disabledGroupMenu.usercount > 0 ||
                  _vm.disabledGroupMenu.usercount === -1
                    ? _c(
                        "AppNavigationItem",
                        {
                          attrs: {
                            id: "disabled",
                            exact: true,
                            title: _vm.t("settings", "Disabled users"),
                            to: {
                              name: "group",
                              params: { selectedGroup: "disabled" },
                            },
                            icon: "icon-disabled-users",
                          },
                        },
                        [
                          _vm.disabledGroupMenu.usercount > 0
                            ? _c(
                                "AppNavigationCounter",
                                { attrs: { slot: "counter" }, slot: "counter" },
                                [
                                  _vm._v(
                                    "\n\t\t\t\t\t" +
                                      _vm._s(_vm.disabledGroupMenu.usercount) +
                                      "\n\t\t\t\t"
                                  ),
                                ]
                              )
                            : _vm._e(),
                        ],
                        1
                      )
                    : _vm._e(),
                  _vm._v(" "),
                  _vm.groupList.length > 0
                    ? _c("AppNavigationCaption", {
                        attrs: { title: _vm.t("settings", "Groups") },
                      })
                    : _vm._e(),
                  _vm._v(" "),
                  _vm._l(_vm.groupList, function (group) {
                    return _c(
                      "AppNavigationItem",
                      {
                        key: group.id,
                        attrs: {
                          exact: true,
                          title: group.title,
                          to: {
                            name: "group",
                            params: {
                              selectedGroup: encodeURIComponent(group.id),
                            },
                          },
                          icon: "icon-group",
                        },
                      },
                      [
                        group.count
                          ? _c(
                              "AppNavigationCounter",
                              { attrs: { slot: "counter" }, slot: "counter" },
                              [
                                _vm._v(
                                  "\n\t\t\t\t\t" +
                                    _vm._s(group.count) +
                                    "\n\t\t\t\t"
                                ),
                              ]
                            )
                          : _vm._e(),
                        _vm._v(" "),
                        _c(
                          "template",
                          { slot: "actions" },
                          [
                            group.id !== "admin" &&
                            group.id !== "disabled" &&
                            _vm.settings.isAdmin
                              ? _c(
                                  "ActionButton",
                                  {
                                    attrs: { icon: "icon-delete" },
                                    on: {
                                      click: function ($event) {
                                        return _vm.removeGroup(group.id)
                                      },
                                    },
                                  },
                                  [
                                    _vm._v(
                                      "\n\t\t\t\t\t\t" +
                                        _vm._s(
                                          _vm.t("settings", "Remove group")
                                        ) +
                                        "\n\t\t\t\t\t"
                                    ),
                                  ]
                                )
                              : _vm._e(),
                          ],
                          1
                        ),
                      ],
                      2
                    )
                  }),
                ]
              },
              proxy: true,
            },
            {
              key: "footer",
              fn: function () {
                return [
                  _c("AppNavigationSettings", [
                    _c(
                      "div",
                      [
                        _c("p", [
                          _vm._v(_vm._s(_vm.t("settings", "Default quota:"))),
                        ]),
                        _vm._v(" "),
                        _c("Multiselect", {
                          attrs: {
                            value: _vm.defaultQuota,
                            options: _vm.quotaOptions,
                            "tag-placeholder": "create",
                            placeholder: _vm.t(
                              "settings",
                              "Select default quota"
                            ),
                            label: "label",
                            "track-by": "id",
                            "allow-empty": false,
                            taggable: true,
                          },
                          on: {
                            tag: _vm.validateQuota,
                            input: _vm.setDefaultQuota,
                          },
                        }),
                      ],
                      1
                    ),
                    _vm._v(" "),
                    _c("div", [
                      _c("input", {
                        directives: [
                          {
                            name: "model",
                            rawName: "v-model",
                            value: _vm.showLanguages,
                            expression: "showLanguages",
                          },
                        ],
                        staticClass: "checkbox",
                        attrs: { id: "showLanguages", type: "checkbox" },
                        domProps: {
                          checked: Array.isArray(_vm.showLanguages)
                            ? _vm._i(_vm.showLanguages, null) > -1
                            : _vm.showLanguages,
                        },
                        on: {
                          change: function ($event) {
                            var $$a = _vm.showLanguages,
                              $$el = $event.target,
                              $$c = $$el.checked ? true : false
                            if (Array.isArray($$a)) {
                              var $$v = null,
                                $$i = _vm._i($$a, $$v)
                              if ($$el.checked) {
                                $$i < 0 &&
                                  (_vm.showLanguages = $$a.concat([$$v]))
                              } else {
                                $$i > -1 &&
                                  (_vm.showLanguages = $$a
                                    .slice(0, $$i)
                                    .concat($$a.slice($$i + 1)))
                              }
                            } else {
                              _vm.showLanguages = $$c
                            }
                          },
                        },
                      }),
                      _vm._v(" "),
                      _c("label", { attrs: { for: "showLanguages" } }, [
                        _vm._v(_vm._s(_vm.t("settings", "Show Languages"))),
                      ]),
                    ]),
                    _vm._v(" "),
                    _c("div", [
                      _c("input", {
                        directives: [
                          {
                            name: "model",
                            rawName: "v-model",
                            value: _vm.showLastLogin,
                            expression: "showLastLogin",
                          },
                        ],
                        staticClass: "checkbox",
                        attrs: { id: "showLastLogin", type: "checkbox" },
                        domProps: {
                          checked: Array.isArray(_vm.showLastLogin)
                            ? _vm._i(_vm.showLastLogin, null) > -1
                            : _vm.showLastLogin,
                        },
                        on: {
                          change: function ($event) {
                            var $$a = _vm.showLastLogin,
                              $$el = $event.target,
                              $$c = $$el.checked ? true : false
                            if (Array.isArray($$a)) {
                              var $$v = null,
                                $$i = _vm._i($$a, $$v)
                              if ($$el.checked) {
                                $$i < 0 &&
                                  (_vm.showLastLogin = $$a.concat([$$v]))
                              } else {
                                $$i > -1 &&
                                  (_vm.showLastLogin = $$a
                                    .slice(0, $$i)
                                    .concat($$a.slice($$i + 1)))
                              }
                            } else {
                              _vm.showLastLogin = $$c
                            }
                          },
                        },
                      }),
                      _vm._v(" "),
                      _c("label", { attrs: { for: "showLastLogin" } }, [
                        _vm._v(_vm._s(_vm.t("settings", "Show last login"))),
                      ]),
                    ]),
                    _vm._v(" "),
                    _c("div", [
                      _c("input", {
                        directives: [
                          {
                            name: "model",
                            rawName: "v-model",
                            value: _vm.showUserBackend,
                            expression: "showUserBackend",
                          },
                        ],
                        staticClass: "checkbox",
                        attrs: { id: "showUserBackend", type: "checkbox" },
                        domProps: {
                          checked: Array.isArray(_vm.showUserBackend)
                            ? _vm._i(_vm.showUserBackend, null) > -1
                            : _vm.showUserBackend,
                        },
                        on: {
                          change: function ($event) {
                            var $$a = _vm.showUserBackend,
                              $$el = $event.target,
                              $$c = $$el.checked ? true : false
                            if (Array.isArray($$a)) {
                              var $$v = null,
                                $$i = _vm._i($$a, $$v)
                              if ($$el.checked) {
                                $$i < 0 &&
                                  (_vm.showUserBackend = $$a.concat([$$v]))
                              } else {
                                $$i > -1 &&
                                  (_vm.showUserBackend = $$a
                                    .slice(0, $$i)
                                    .concat($$a.slice($$i + 1)))
                              }
                            } else {
                              _vm.showUserBackend = $$c
                            }
                          },
                        },
                      }),
                      _vm._v(" "),
                      _c("label", { attrs: { for: "showUserBackend" } }, [
                        _vm._v(_vm._s(_vm.t("settings", "Show user backend"))),
                      ]),
                    ]),
                    _vm._v(" "),
                    _c("div", [
                      _c("input", {
                        directives: [
                          {
                            name: "model",
                            rawName: "v-model",
                            value: _vm.showStoragePath,
                            expression: "showStoragePath",
                          },
                        ],
                        staticClass: "checkbox",
                        attrs: { id: "showStoragePath", type: "checkbox" },
                        domProps: {
                          checked: Array.isArray(_vm.showStoragePath)
                            ? _vm._i(_vm.showStoragePath, null) > -1
                            : _vm.showStoragePath,
                        },
                        on: {
                          change: function ($event) {
                            var $$a = _vm.showStoragePath,
                              $$el = $event.target,
                              $$c = $$el.checked ? true : false
                            if (Array.isArray($$a)) {
                              var $$v = null,
                                $$i = _vm._i($$a, $$v)
                              if ($$el.checked) {
                                $$i < 0 &&
                                  (_vm.showStoragePath = $$a.concat([$$v]))
                              } else {
                                $$i > -1 &&
                                  (_vm.showStoragePath = $$a
                                    .slice(0, $$i)
                                    .concat($$a.slice($$i + 1)))
                              }
                            } else {
                              _vm.showStoragePath = $$c
                            }
                          },
                        },
                      }),
                      _vm._v(" "),
                      _c("label", { attrs: { for: "showStoragePath" } }, [
                        _vm._v(_vm._s(_vm.t("settings", "Show storage path"))),
                      ]),
                    ]),
                    _vm._v(" "),
                    _c("div", [
                      _c("input", {
                        directives: [
                          {
                            name: "model",
                            rawName: "v-model",
                            value: _vm.sendWelcomeMail,
                            expression: "sendWelcomeMail",
                          },
                        ],
                        staticClass: "checkbox",
                        attrs: {
                          id: "sendWelcomeMail",
                          disabled: _vm.loadingSendMail,
                          type: "checkbox",
                        },
                        domProps: {
                          checked: Array.isArray(_vm.sendWelcomeMail)
                            ? _vm._i(_vm.sendWelcomeMail, null) > -1
                            : _vm.sendWelcomeMail,
                        },
                        on: {
                          change: function ($event) {
                            var $$a = _vm.sendWelcomeMail,
                              $$el = $event.target,
                              $$c = $$el.checked ? true : false
                            if (Array.isArray($$a)) {
                              var $$v = null,
                                $$i = _vm._i($$a, $$v)
                              if ($$el.checked) {
                                $$i < 0 &&
                                  (_vm.sendWelcomeMail = $$a.concat([$$v]))
                              } else {
                                $$i > -1 &&
                                  (_vm.sendWelcomeMail = $$a
                                    .slice(0, $$i)
                                    .concat($$a.slice($$i + 1)))
                              }
                            } else {
                              _vm.sendWelcomeMail = $$c
                            }
                          },
                        },
                      }),
                      _vm._v(" "),
                      _c("label", { attrs: { for: "sendWelcomeMail" } }, [
                        _vm._v(
                          _vm._s(_vm.t("settings", "Send email to new user"))
                        ),
                      ]),
                    ]),
                  ]),
                ]
              },
              proxy: true,
            },
          ]),
        },
        [
          _c("AppNavigationNew", {
            attrs: {
              "button-id": "new-user-button",
              text: _vm.t("settings", "New user"),
              "button-class": "icon-add",
            },
            on: {
              click: _vm.showNewUserMenu,
              keyup: [
                function ($event) {
                  if (
                    !$event.type.indexOf("key") &&
                    _vm._k($event.keyCode, "enter", 13, $event.key, "Enter")
                  ) {
                    return null
                  }
                  return _vm.showNewUserMenu.apply(null, arguments)
                },
                function ($event) {
                  if (
                    !$event.type.indexOf("key") &&
                    _vm._k($event.keyCode, "space", 32, $event.key, [
                      " ",
                      "Spacebar",
                    ])
                  ) {
                    return null
                  }
                  return _vm.showNewUserMenu.apply(null, arguments)
                },
              ],
            },
          }),
        ],
        1
      ),
      _vm._v(" "),
      _c(
        "AppContent",
        [
          _c("UserList", {
            attrs: {
              users: _vm.users,
              "show-config": _vm.showConfig,
              "selected-group": _vm.selectedGroupDecoded,
              "external-actions": _vm.externalActions,
            },
          }),
        ],
        1
      ),
    ],
    1
  )
}
var staticRenderFns = []
render._withStripped = true



/***/ })

}]);
<<<<<<< HEAD
//# sourceMappingURL=settings-users-settings-users.js.map?v=3a4fbcc884b49145f7f1
=======
//# sourceMappingURL=settings-users-settings-users.js.map?v=c111d46c81ea40916677
>>>>>>> 5f2027f1e1 (Typing corrections)
