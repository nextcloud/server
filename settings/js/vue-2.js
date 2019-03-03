(window["webpackJsonp"] = window["webpackJsonp"] || []).push([[2],{

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js?!./settings/src/components/userList.vue?vue&type=script&lang=js&":
/*!*********************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib!./node_modules/vue-loader/lib??vue-loader-options!./settings/src/components/userList.vue?vue&type=script&lang=js& ***!
  \*********************************************************************************************************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _userList_userRow__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./userList/userRow */ "./settings/src/components/userList/userRow.vue");
/* harmony import */ var nextcloud_vue__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! nextcloud-vue */ "./node_modules/nextcloud-vue/dist/ncvuecomponents.js");
/* harmony import */ var nextcloud_vue__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(nextcloud_vue__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var vue_infinite_loading__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! vue-infinite-loading */ "./node_modules/vue-infinite-loading/dist/vue-infinite-loading.js");
/* harmony import */ var vue_infinite_loading__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(vue_infinite_loading__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
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
  name: 'userList',
  props: ['users', 'showConfig', 'selectedGroup', 'externalActions'],
  components: {
    userRow: _userList_userRow__WEBPACK_IMPORTED_MODULE_0__["default"],
    Multiselect: nextcloud_vue__WEBPACK_IMPORTED_MODULE_1__["Multiselect"],
    InfiniteLoading: vue_infinite_loading__WEBPACK_IMPORTED_MODULE_2___default.a
  },
  data: function data() {
    var unlimitedQuota = {
      id: 'none',
      label: t('settings', 'Unlimited')
    },
        defaultQuota = {
      id: 'default',
      label: t('settings', 'Default quota')
    };
    return {
      unlimitedQuota: unlimitedQuota,
      defaultQuota: defaultQuota,
      loading: {
        all: false,
        groups: false
      },
      scrolled: false,
      searchQuery: '',
      newUser: {
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
      }
    };
  },
  mounted: function mounted() {
    if (!this.settings.canChangePassword) {
      OC.Notification.showTemporary(t('settings', 'Password change is disabled because the master key is disabled'));
    }
    /** 
     * Init default language from server data. The use of this.settings
     * requires a computed variable, which break the v-model binding of the form,
     * this is a much easier solution than getter and setter on a computed var
     */


    vue__WEBPACK_IMPORTED_MODULE_3__["default"].set(this.newUser.language, 'code', this.settings.defaultLanguage);
    /**
     * In case the user directly loaded the user list within a group
     * the watch won't be triggered. We need to initialize it.
     */

    this.setNewUserDefaultGroup(this.selectedGroup);
    /** 
     * Register search
     */

    this.userSearch = new OCA.Search(this.search, this.resetSearch);
  },
  computed: {
    settings: function settings() {
      return this.$store.getters.getServerData;
    },
    filteredUsers: function filteredUsers() {
      if (this.selectedGroup === 'disabled') {
        var disabledUsers = this.users.filter(function (user) {
          return user.enabled === false;
        });

        if (disabledUsers.length === 0 && this.$refs.infiniteLoading && this.$refs.infiniteLoading.isComplete) {
          // disabled group is empty, redirection to all users
          this.$router.push({
            name: 'users'
          });
          this.$refs.infiniteLoading.stateChanger.reset();
        }

        return disabledUsers;
      }

      if (!this.settings.isAdmin) {
        // we don't want subadmins to edit themselves
        return this.users.filter(function (user) {
          return user.enabled !== false && user.id !== oc_current_user;
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

      quotaPreset.unshift(this.unlimitedQuota);
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
      return Array({
        label: t('settings', 'Common languages'),
        languages: this.settings.languages.commonlanguages
      }, {
        label: t('settings', 'All languages'),
        languages: this.settings.languages.languages
      });
    }
  },
  watch: {
    // watch url change and group select
    selectedGroup: function selectedGroup(val, old) {
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
  methods: {
    onScroll: function onScroll(event) {
      this.scrolled = event.target.scrollTo > 0;
    },

    /**
     * Validate quota string to make sure it's a valid human file size
     * 
     * @param {string} quota Quota in readable format '5 GB'
     * @returns {Object}
     */
    validateQuota: function validateQuota(quota) {
      // only used for new presets sent through @Tag
      var validQuota = OC.Util.computerFileSize(quota);

      if (validQuota !== null && validQuota >= 0) {
        // unify format output
        quota = OC.Util.humanFileSize(OC.Util.computerFileSize(quota));
        return this.newUser.quota = {
          id: quota,
          label: quota
        };
      } // Default is unlimited


      return this.newUser.quota = this.quotaOptions[0];
    },
    infiniteHandler: function infiniteHandler($state) {
      this.$store.dispatch('getUsers', {
        offset: this.usersOffset,
        limit: this.usersLimit,
        group: this.selectedGroup !== 'disabled' ? this.selectedGroup : '',
        search: this.searchQuery
      }).then(function (response) {
        response ? $state.loaded() : $state.complete();
      });
    },

    /* SEARCH */
    search: function search(query) {
      this.searchQuery = query;
      this.$store.commit('resetUsers');
      this.$refs.infiniteLoading.stateChanger.reset();
    },
    resetSearch: function resetSearch() {
      this.search('');
    },
    resetForm: function resetForm() {
      // revert form to original state
      Object.assign(this.newUser, this.$options.data.call(this).newUser); // reset group

      this.setNewUserDefaultGroup(this.selectedGroup);
      this.loading.all = false;
    },
    createUser: function createUser() {
      var _this = this;

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
        _this.resetForm();
      }).catch(function (error) {
        _this.loading.all = false;

        if (error.response && error.response.data && error.response.data.ocs && error.response.data.ocs.meta) {
          var statuscode = error.response.data.ocs.meta.statuscode;

          if (statuscode === 102) {
            // wrong username
            _this.$refs.newusername.focus();
          } else if (statuscode === 107) {
            // wrong password
            _this.$refs.newuserpassword.focus();
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
     * @param {string} groups Group id
     * @returns {Promise}
     */
    createGroup: function createGroup(gid) {
      var _this2 = this;

      this.loading.groups = true;
      this.$store.dispatch('addGroup', gid).then(function (group) {
        _this2.newUser.groups.push(_this2.groups.find(function (group) {
          return group.id === gid;
        }));

        _this2.loading.groups = false;
      }).catch(function () {
        _this2.loading.groups = false;
      });
      return this.$store.getters.getGroups[this.groups.length];
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js?!./settings/src/components/userList/userRow.vue?vue&type=script&lang=js&":
/*!*****************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib!./node_modules/vue-loader/lib??vue-loader-options!./settings/src/components/userList/userRow.vue?vue&type=script&lang=js& ***!
  \*****************************************************************************************************************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var vue_click_outside__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! vue-click-outside */ "./node_modules/vue-click-outside/index.js");
/* harmony import */ var vue_click_outside__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(vue_click_outside__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var v_tooltip__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! v-tooltip */ "./node_modules/v-tooltip/dist/v-tooltip.esm.js");
/* harmony import */ var nextcloud_vue__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! nextcloud-vue */ "./node_modules/nextcloud-vue/dist/ncvuecomponents.js");
/* harmony import */ var nextcloud_vue__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(nextcloud_vue__WEBPACK_IMPORTED_MODULE_3__);
function _typeof(obj) { if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//




vue__WEBPACK_IMPORTED_MODULE_1__["default"].use(v_tooltip__WEBPACK_IMPORTED_MODULE_2__["default"]);
/* harmony default export */ __webpack_exports__["default"] = ({
  name: 'userRow',
  props: ['user', 'settings', 'groups', 'subAdminsGroups', 'quotaOptions', 'showConfig', 'languages', 'externalActions'],
  components: {
    PopoverMenu: nextcloud_vue__WEBPACK_IMPORTED_MODULE_3__["PopoverMenu"],
    Multiselect: nextcloud_vue__WEBPACK_IMPORTED_MODULE_3__["Multiselect"]
  },
  directives: {
    ClickOutside: vue_click_outside__WEBPACK_IMPORTED_MODULE_0___default.a
  },
  mounted: function mounted() {// required if popup needs to stay opened after menu click
    // since we only have disable/delete actions, let's close it directly
    // this.popupItem = this.$el;
  },
  data: function data() {
    return {
      rand: parseInt(Math.random() * 1000),
      openedMenu: false,
      feedbackMessage: '',
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
        languages: false
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
    },

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
        var usedInGB = this.user.quota.used / (10 * Math.pow(2, 30)); //asymptotic curve approaching 50% at 10GB to visualize used stace with infinite quota

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
        return userQuota ? userQuota : {
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

    /**
     * Generate avatar url
     * 
     * @param {string} user The user name
     * @param {int} size Size integer, default 32
     * @returns {string}
     */
    generateAvatar: function generateAvatar(user) {
      var size = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 32;
      return OC.generateUrl('/avatar/{user}/{size}?v={version}', {
        user: user,
        size: size,
        version: oc_userconfig.avatar.version
      });
    },

    /**
     * Format array of groups objects to a string for the popup
     * 
     * @param {array} groups The groups
     * @returns {string}
     */
    formatGroupsTitle: function formatGroupsTitle(groups) {
      var names = groups.map(function (group) {
        return group.name;
      });
      return names.slice(2).join(', ');
    },
    deleteUser: function deleteUser() {
      var _this5 = this;

      this.loading.delete = true;
      this.loading.all = true;
      var userid = this.user.id;
      return this.$store.dispatch('deleteUser', userid).then(function () {
        _this5.loading.delete = false;
        _this5.loading.all = false;
      });
    },
    enableDisableUser: function enableDisableUser() {
      var _this6 = this;

      this.loading.delete = true;
      this.loading.all = true;
      var userid = this.user.id;
      var enabled = !this.user.enabled;
      return this.$store.dispatch('enableDisableUser', {
        userid: userid,
        enabled: enabled
      }).then(function () {
        _this6.loading.delete = false;
        _this6.loading.all = false;
      });
    },

    /**
     * Set user displayName
     * 
     * @param {string} displayName The display name
     * @returns {Promise}
     */
    updateDisplayName: function updateDisplayName() {
      var _this7 = this;

      var displayName = this.$refs.displayName.value;
      this.loading.displayName = true;
      this.$store.dispatch('setUserData', {
        userid: this.user.id,
        key: 'displayname',
        value: displayName
      }).then(function () {
        _this7.loading.displayName = false;
        _this7.$refs.displayName.value = displayName;
      });
    },

    /**
     * Set user password
     * 
     * @param {string} password The email adress
     * @returns {Promise}
     */
    updatePassword: function updatePassword() {
      var _this8 = this;

      var password = this.$refs.password.value;
      this.loading.password = true;
      this.$store.dispatch('setUserData', {
        userid: this.user.id,
        key: 'password',
        value: password
      }).then(function () {
        _this8.loading.password = false;
        _this8.$refs.password.value = ''; // empty & show placeholder 
      });
    },

    /**
     * Set user mailAddress
     * 
     * @param {string} mailAddress The email adress
     * @returns {Promise}
     */
    updateEmail: function updateEmail() {
      var _this9 = this;

      var mailAddress = this.$refs.mailAddress.value;
      this.loading.mailAddress = true;
      this.$store.dispatch('setUserData', {
        userid: this.user.id,
        key: 'email',
        value: mailAddress
      }).then(function () {
        _this9.loading.mailAddress = false;
        _this9.$refs.mailAddress.value = mailAddress;
      });
    },

    /**
     * Create a new group and add user to it
     * 
     * @param {string} groups Group id
     * @returns {Promise}
     */
    createGroup: function createGroup(gid) {
      var _this10 = this;

      this.loading = {
        groups: true,
        subadmins: true
      };
      this.$store.dispatch('addGroup', gid).then(function () {
        _this10.loading = {
          groups: false,
          subadmins: false
        };
        var userid = _this10.user.id;

        _this10.$store.dispatch('addUserGroup', {
          userid: userid,
          gid: gid
        });
      }).catch(function () {
        _this10.loading = {
          groups: false,
          subadmins: false
        };
      });
      return this.$store.getters.getGroups[this.groups.length];
    },

    /**
     * Add user to group
     * 
     * @param {object} group Group object
     * @returns {Promise}
     */
    addUserGroup: function addUserGroup(group) {
      var _this11 = this;

      if (group.canAdd === false) {
        return false;
      }

      this.loading.groups = true;
      var userid = this.user.id;
      var gid = group.id;
      return this.$store.dispatch('addUserGroup', {
        userid: userid,
        gid: gid
      }).then(function () {
        return _this11.loading.groups = false;
      });
    },

    /**
     * Remove user from group
     * 
     * @param {object} group Group object
     * @returns {Promise}
     */
    removeUserGroup: function removeUserGroup(group) {
      var _this12 = this;

      if (group.canRemove === false) {
        return false;
      }

      this.loading.groups = true;
      var userid = this.user.id;
      var gid = group.id;
      return this.$store.dispatch('removeUserGroup', {
        userid: userid,
        gid: gid
      }).then(function () {
        _this12.loading.groups = false; // remove user from current list if current list is the removed group

        if (_this12.$route.params.selectedGroup === gid) {
          _this12.$store.commit('deleteUser', userid);
        }
      }).catch(function () {
        _this12.loading.groups = false;
      });
    },

    /**
     * Add user to group
     * 
     * @param {object} group Group object
     * @returns {Promise}
     */
    addUserSubAdmin: function addUserSubAdmin(group) {
      var _this13 = this;

      this.loading.subadmins = true;
      var userid = this.user.id;
      var gid = group.id;
      return this.$store.dispatch('addUserSubAdmin', {
        userid: userid,
        gid: gid
      }).then(function () {
        return _this13.loading.subadmins = false;
      });
    },

    /**
     * Remove user from group
     * 
     * @param {object} group Group object
     * @returns {Promise}
     */
    removeUserSubAdmin: function removeUserSubAdmin(group) {
      var _this14 = this;

      this.loading.subadmins = true;
      var userid = this.user.id;
      var gid = group.id;
      return this.$store.dispatch('removeUserSubAdmin', {
        userid: userid,
        gid: gid
      }).then(function () {
        return _this14.loading.subadmins = false;
      });
    },

    /**
     * Dispatch quota set request
     * 
     * @param {string|Object} quota Quota in readable format '5 GB' or Object {id: '5 GB', label: '5GB'}
     * @returns {string}
     */
    setUserQuota: function setUserQuota() {
      var _this15 = this;

      var quota = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : 'none';
      this.loading.quota = true; // ensure we only send the preset id

      quota = quota.id ? quota.id : quota;
      this.$store.dispatch('setUserData', {
        userid: this.user.id,
        key: 'quota',
        value: quota
      }).then(function () {
        return _this15.loading.quota = false;
      });
      return quota;
    },

    /**
     * Validate quota string to make sure it's a valid human file size
     * 
     * @param {string} quota Quota in readable format '5 GB'
     * @returns {Promise|boolean}
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
     * @param {Object} lang language object {code:'en', name:'English'}
     * @returns {Object}
     */
    setUserLanguage: function setUserLanguage(lang) {
      var _this16 = this;

      this.loading.languages = true; // ensure we only send the preset id

      this.$store.dispatch('setUserData', {
        userid: this.user.id,
        key: 'language',
        value: lang.code
      }).then(function () {
        return _this16.loading.languages = false;
      });
      return lang;
    },

    /**
     * Dispatch new welcome mail request
     */
    sendWelcomeMail: function sendWelcomeMail() {
      var _this17 = this;

      this.loading.all = true;
      this.$store.dispatch('sendWelcomeMail', this.user.id).then(function (success) {
        if (success) {
          // Show feedback to indicate the success
          _this17.feedbackMessage = t('setting', 'Welcome mail sent!');
          setTimeout(function () {
            _this17.feedbackMessage = '';
          }, 2000);
        }

        _this17.loading.all = false;
      });
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js?!./settings/src/views/Users.vue?vue&type=script&lang=js&":
/*!*************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib!./node_modules/vue-loader/lib??vue-loader-options!./settings/src/views/Users.vue?vue&type=script&lang=js& ***!
  \*************************************************************************************************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var nextcloud_vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! nextcloud-vue */ "./node_modules/nextcloud-vue/dist/ncvuecomponents.js");
/* harmony import */ var nextcloud_vue__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(nextcloud_vue__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _components_userList__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../components/userList */ "./settings/src/components/userList.vue");
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var vue_localstorage__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! vue-localstorage */ "./node_modules/vue-localstorage/dist/vue-local-storage.js");
/* harmony import */ var vue_localstorage__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(vue_localstorage__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var vue_multiselect__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! vue-multiselect */ "./node_modules/vue-multiselect/dist/vue-multiselect.min.js");
/* harmony import */ var vue_multiselect__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(vue_multiselect__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _store_api__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../store/api */ "./settings/src/store/api.js");
function _typeof(obj) { if (typeof Symbol === "function" && typeof Symbol.iterator === "symbol") { _typeof = function _typeof(obj) { return typeof obj; }; } else { _typeof = function _typeof(obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol && obj !== Symbol.prototype ? "symbol" : typeof obj; }; } return _typeof(obj); }

//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//






vue__WEBPACK_IMPORTED_MODULE_2__["default"].use(vue_localstorage__WEBPACK_IMPORTED_MODULE_3___default.a);
/* harmony default export */ __webpack_exports__["default"] = ({
  name: 'Users',
  props: ['selectedGroup'],
  components: {
    AppContent: nextcloud_vue__WEBPACK_IMPORTED_MODULE_0__["AppContent"],
    AppNavigationItem: nextcloud_vue__WEBPACK_IMPORTED_MODULE_0__["AppNavigationItem"],
    AppNavigationNew: nextcloud_vue__WEBPACK_IMPORTED_MODULE_0__["AppNavigationNew"],
    AppNavigationSettings: nextcloud_vue__WEBPACK_IMPORTED_MODULE_0__["AppNavigationSettings"],
    userList: _components_userList__WEBPACK_IMPORTED_MODULE_1__["default"],
    Multiselect: vue_multiselect__WEBPACK_IMPORTED_MODULE_4___default.a
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
      showAddGroupEntry: false,
      loadingAddGroup: false,
      showConfig: {
        showStoragePath: false,
        showUserBackend: false,
        showLastLogin: false,
        showNewUserForm: false,
        showLanguages: false
      }
    };
  },
  methods: {
    toggleNewUserMenu: function toggleNewUserMenu() {
      this.showConfig.showNewUserForm = !this.showConfig.showNewUserForm;

      if (this.showConfig.showNewUserForm) {
        vue__WEBPACK_IMPORTED_MODULE_2__["default"].nextTick(function () {
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
     * @param {string|Object} quota Quota in readable format '5 GB' or Object {id: '5 GB', label: '5GB'}
     * @returns {string}
     */
    setDefaultQuota: function setDefaultQuota() {
      var _this = this;

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

        _this.defaultQuota = quota;
      });
    },

    /**
     * Validate quota string to make sure it's a valid human file size
     * 
     * @param {string} quota Quota in readable format '5 GB'
     * @returns {Promise|boolean}
     */
    validateQuota: function validateQuota(quota) {
      // only used for new presets sent through @Tag
      var validQuota = OC.Util.computerFileSize(quota);

      if (validQuota === 0) {
        return this.setDefaultQuota('none');
      } else if (validQuota !== null) {
        // unify format output
        return this.setDefaultQuota(OC.Util.humanFileSize(OC.Util.computerFileSize(quota)));
      } // if no valid do not change


      return false;
    },

    /**
     * Register a new action for the user menu
     * 
     * @param {string} icon the icon class
     * @param {string} text the text to display
     * @param {function} action the function to run
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
     * @param {Object} event The form submit event
     */
    createGroup: function createGroup(event) {
      var _this2 = this;

      var gid = event.target[0].value;
      this.loadingAddGroup = true;
      this.$store.dispatch('addGroup', gid).then(function () {
        _this2.showAddGroupEntry = false;
        _this2.loadingAddGroup = false;

        _this2.$router.push({
          name: 'group',
          params: {
            selectedGroup: gid
          }
        });
      }).catch(function () {
        _this2.loadingAddGroup = false;
      });
    }
  },
  computed: {
    users: function users() {
      return this.$store.getters.getUsers;
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

      quotaPreset.unshift(this.unlimitedQuota);
      return quotaPreset;
    },
    // mapping saved values to objects
    defaultQuota: {
      get: function get() {
        if (this.selectedQuota !== false) {
          return this.selectedQuota;
        }

        if (OC.Util.computerFileSize(this.settings.defaultQuota) > 0) {
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
    // BUILD APP NAVIGATION MENU OBJECT
    menu: function menu() {
      var _this3 = this;

      // Data provided php side
      var self = this;
      var groups = this.$store.getters.getGroups;
      groups = Array.isArray(groups) ? groups : []; // Map groups

      groups = groups.map(function (group) {
        var item = {};
        item.id = group.id.replace(' ', '_');
        item.key = item.id;
        item.utils = {}; // router link to

        item.router = {
          name: 'group',
          params: {
            selectedGroup: group.id
          }
        }; // group name

        item.text = group.name;
        item.title = group.name; // users count for all groups

        if (group.usercount - group.disabled > 0 || group.usercount === -1) {
          item.utils.counter = group.usercount - group.disabled;
        }

        if (item.id !== 'admin' && item.id !== 'disabled' && _this3.settings.isAdmin) {
          // add delete button on real groups
          item.utils.actions = [{
            icon: 'icon-delete',
            text: t('settings', 'Remove group'),
            action: function action() {
              self.removeGroup(group.id);
            }
          }];
        }

        ;
        return item;
      }); // Every item is added on top of the array, so we're going backward
      // Groups, separator, disabled, admin, everyone
      // Add separator

      var realGroups = groups.find(function (group) {
        return group.id !== 'disabled' && group.id !== 'admin';
      });
      realGroups = typeof realGroups === 'undefined' ? [] : realGroups;
      realGroups = Array.isArray(realGroups) ? realGroups : [realGroups];

      if (realGroups.length > 0) {
        var separator = {
          caption: true,
          text: t('settings', 'Groups')
        };
        groups.unshift(separator);
      } // Adjust admin and disabled groups


      var adminGroup = groups.find(function (group) {
        return group.id == 'admin';
      });
      var disabledGroup = groups.find(function (group) {
        return group.id == 'disabled';
      }); // filter out admin and disabled

      groups = groups.filter(function (group) {
        return ['admin', 'disabled'].indexOf(group.id) === -1;
      });

      if (adminGroup && adminGroup.text) {
        adminGroup.text = t('settings', 'Admins'); // rename admin group

        adminGroup.icon = 'icon-user-admin'; // set icon

        groups.unshift(adminGroup); // add admin group if present
      }

      if (disabledGroup && disabledGroup.text) {
        disabledGroup.text = t('settings', 'Disabled users'); // rename disabled group

        disabledGroup.icon = 'icon-disabled-users'; // set icon

        if (disabledGroup.utils && (disabledGroup.utils.counter > 0 // add disabled if not empty 
        || disabledGroup.utils.counter === -1) // add disabled if ldap enabled 
        ) {
            groups.unshift(disabledGroup);
          }
      } // Add everyone group


      var everyoneGroup = {
        id: 'everyone',
        key: 'everyone',
        icon: 'icon-contacts-dark',
        router: {
          name: 'users'
        },
        text: t('settings', 'Everyone')
      }; // users count

      if (this.userCount > 0) {
        vue__WEBPACK_IMPORTED_MODULE_2__["default"].set(everyoneGroup, 'utils', {
          counter: this.userCount
        });
      }

      groups.unshift(everyoneGroup);
      var addGroup = {
        id: 'addgroup',
        key: 'addgroup',
        icon: 'icon-add',
        text: t('settings', 'Add group'),
        classes: this.loadingAddGroup ? 'icon-loading-small' : ''
      };

      if (this.showAddGroupEntry) {
        vue__WEBPACK_IMPORTED_MODULE_2__["default"].set(addGroup, 'edit', {
          text: t('settings', 'Add group'),
          action: this.createGroup,
          reset: function reset() {
            self.showAddGroupEntry = false;
          }
        });
        addGroup.classes = 'editing';
      } else {
        vue__WEBPACK_IMPORTED_MODULE_2__["default"].set(addGroup, 'action', function () {
          self.showAddGroupEntry = true; // focus input

          vue__WEBPACK_IMPORTED_MODULE_2__["default"].nextTick(function () {
            window.addgroup.querySelector('form > input[type="text"]').focus();
          });
        });
      }

      groups.unshift(addGroup);
      return groups;
    }
  }
});

/***/ }),

/***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js?!./node_modules/vue-loader/lib/index.js?!./settings/src/components/userList.vue?vue&type=template&id=1347754e&":
/*!***********************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/vue-loader/lib??vue-loader-options!./settings/src/components/userList.vue?vue&type=template&id=1347754e& ***!
  \***********************************************************************************************************************************************************************************************************/
/*! exports provided: render, staticRenderFns */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "render", function() { return render; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "staticRenderFns", function() { return staticRenderFns; });
var render = function() {
  var _vm = this
  var _h = _vm.$createElement
  var _c = _vm._self._c || _h
  return _c(
    "div",
    {
      staticClass: "user-list-grid",
      attrs: { id: "app-content" },
      on: {
        "&scroll": function($event) {
          return _vm.onScroll($event)
        }
      }
    },
    [
      _c(
        "div",
        {
          staticClass: "row",
          class: { sticky: _vm.scrolled && !_vm.showConfig.showNewUserForm },
          attrs: { id: "grid-header" }
        },
        [
          _c("div", { staticClass: "avatar", attrs: { id: "headerAvatar" } }),
          _vm._v(" "),
          _c("div", { staticClass: "name", attrs: { id: "headerName" } }, [
            _vm._v(_vm._s(_vm.t("settings", "Username")))
          ]),
          _vm._v(" "),
          _c(
            "div",
            { staticClass: "displayName", attrs: { id: "headerDisplayName" } },
            [_vm._v(_vm._s(_vm.t("settings", "Display name")))]
          ),
          _vm._v(" "),
          _c(
            "div",
            { staticClass: "password", attrs: { id: "headerPassword" } },
            [_vm._v(_vm._s(_vm.t("settings", "Password")))]
          ),
          _vm._v(" "),
          _c(
            "div",
            { staticClass: "mailAddress", attrs: { id: "headerAddress" } },
            [_vm._v(_vm._s(_vm.t("settings", "Email")))]
          ),
          _vm._v(" "),
          _c("div", { staticClass: "groups", attrs: { id: "headerGroups" } }, [
            _vm._v(_vm._s(_vm.t("settings", "Groups")))
          ]),
          _vm._v(" "),
          _vm.subAdminsGroups.length > 0 && _vm.settings.isAdmin
            ? _c(
                "div",
                { staticClass: "subadmins", attrs: { id: "headerSubAdmins" } },
                [_vm._v(_vm._s(_vm.t("settings", "Group admin for")))]
              )
            : _vm._e(),
          _vm._v(" "),
          _c("div", { staticClass: "quota", attrs: { id: "headerQuota" } }, [
            _vm._v(_vm._s(_vm.t("settings", "Quota")))
          ]),
          _vm._v(" "),
          _vm.showConfig.showLanguages
            ? _c(
                "div",
                { staticClass: "languages", attrs: { id: "headerLanguages" } },
                [_vm._v(_vm._s(_vm.t("settings", "Language")))]
              )
            : _vm._e(),
          _vm._v(" "),
          _vm.showConfig.showStoragePath
            ? _c(
                "div",
                { staticClass: "headerStorageLocation storageLocation" },
                [_vm._v(_vm._s(_vm.t("settings", "Storage location")))]
              )
            : _vm._e(),
          _vm._v(" "),
          _vm.showConfig.showUserBackend
            ? _c("div", { staticClass: "headerUserBackend userBackend" }, [
                _vm._v(_vm._s(_vm.t("settings", "User backend")))
              ])
            : _vm._e(),
          _vm._v(" "),
          _vm.showConfig.showLastLogin
            ? _c("div", { staticClass: "headerLastLogin lastLogin" }, [
                _vm._v(_vm._s(_vm.t("settings", "Last login")))
              ])
            : _vm._e(),
          _vm._v(" "),
          _c("div", { staticClass: "userActions" })
        ]
      ),
      _vm._v(" "),
      _c(
        "form",
        {
          directives: [
            {
              name: "show",
              rawName: "v-show",
              value: _vm.showConfig.showNewUserForm,
              expression: "showConfig.showNewUserForm"
            }
          ],
          staticClass: "row",
          class: { sticky: _vm.scrolled && _vm.showConfig.showNewUserForm },
          attrs: { id: "new-user", disabled: _vm.loading.all },
          on: {
            submit: function($event) {
              $event.preventDefault()
              return _vm.createUser($event)
            }
          }
        },
        [
          _c("div", {
            class: _vm.loading.all ? "icon-loading-small" : "icon-add"
          }),
          _vm._v(" "),
          _c("div", { staticClass: "name" }, [
            _c("input", {
              directives: [
                {
                  name: "model",
                  rawName: "v-model",
                  value: _vm.newUser.id,
                  expression: "newUser.id"
                }
              ],
              ref: "newusername",
              attrs: {
                id: "newusername",
                type: "text",
                required: "",
                placeholder: _vm.t("settings", "Username"),
                name: "username",
                autocomplete: "off",
                autocapitalize: "none",
                autocorrect: "off",
                pattern: "[a-zA-Z0-9 _\\.@\\-']+"
              },
              domProps: { value: _vm.newUser.id },
              on: {
                input: function($event) {
                  if ($event.target.composing) {
                    return
                  }
                  _vm.$set(_vm.newUser, "id", $event.target.value)
                }
              }
            })
          ]),
          _vm._v(" "),
          _c("div", { staticClass: "displayName" }, [
            _c("input", {
              directives: [
                {
                  name: "model",
                  rawName: "v-model",
                  value: _vm.newUser.displayName,
                  expression: "newUser.displayName"
                }
              ],
              attrs: {
                id: "newdisplayname",
                type: "text",
                placeholder: _vm.t("settings", "Display name"),
                name: "displayname",
                autocomplete: "off",
                autocapitalize: "none",
                autocorrect: "off"
              },
              domProps: { value: _vm.newUser.displayName },
              on: {
                input: function($event) {
                  if ($event.target.composing) {
                    return
                  }
                  _vm.$set(_vm.newUser, "displayName", $event.target.value)
                }
              }
            })
          ]),
          _vm._v(" "),
          _c("div", { staticClass: "password" }, [
            _c("input", {
              directives: [
                {
                  name: "model",
                  rawName: "v-model",
                  value: _vm.newUser.password,
                  expression: "newUser.password"
                }
              ],
              ref: "newuserpassword",
              attrs: {
                id: "newuserpassword",
                type: "password",
                required: _vm.newUser.mailAddress === "",
                placeholder: _vm.t("settings", "Password"),
                name: "password",
                autocomplete: "new-password",
                autocapitalize: "none",
                autocorrect: "off",
                minlength: _vm.minPasswordLength
              },
              domProps: { value: _vm.newUser.password },
              on: {
                input: function($event) {
                  if ($event.target.composing) {
                    return
                  }
                  _vm.$set(_vm.newUser, "password", $event.target.value)
                }
              }
            })
          ]),
          _vm._v(" "),
          _c("div", { staticClass: "mailAddress" }, [
            _c("input", {
              directives: [
                {
                  name: "model",
                  rawName: "v-model",
                  value: _vm.newUser.mailAddress,
                  expression: "newUser.mailAddress"
                }
              ],
              attrs: {
                id: "newemail",
                type: "email",
                required: _vm.newUser.password === "",
                placeholder: _vm.t("settings", "Email"),
                name: "email",
                autocomplete: "off",
                autocapitalize: "none",
                autocorrect: "off"
              },
              domProps: { value: _vm.newUser.mailAddress },
              on: {
                input: function($event) {
                  if ($event.target.composing) {
                    return
                  }
                  _vm.$set(_vm.newUser, "mailAddress", $event.target.value)
                }
              }
            })
          ]),
          _vm._v(" "),
          _c(
            "div",
            { staticClass: "groups" },
            [
              !_vm.settings.isAdmin
                ? _c("input", {
                    class: { "icon-loading-small": _vm.loading.groups },
                    attrs: {
                      type: "text",
                      tabindex: "-1",
                      id: "newgroups",
                      required: !_vm.settings.isAdmin
                    },
                    domProps: { value: _vm.newUser.groups }
                  })
                : _vm._e(),
              _vm._v(" "),
              _c(
                "multiselect",
                {
                  staticClass: "multiselect-vue",
                  attrs: {
                    options: _vm.canAddGroups,
                    disabled: _vm.loading.groups || _vm.loading.all,
                    "tag-placeholder": "create",
                    placeholder: _vm.t("settings", "Add user in group"),
                    label: "name",
                    "track-by": "id",
                    multiple: true,
                    taggable: true,
                    "close-on-select": false,
                    "tag-width": 60
                  },
                  on: { tag: _vm.createGroup },
                  model: {
                    value: _vm.newUser.groups,
                    callback: function($$v) {
                      _vm.$set(_vm.newUser, "groups", $$v)
                    },
                    expression: "newUser.groups"
                  }
                },
                [
                  _c(
                    "span",
                    { attrs: { slot: "noResult" }, slot: "noResult" },
                    [_vm._v(_vm._s(_vm.t("settings", "No results")))]
                  )
                ]
              )
            ],
            1
          ),
          _vm._v(" "),
          _vm.subAdminsGroups.length > 0 && _vm.settings.isAdmin
            ? _c(
                "div",
                { staticClass: "subadmins" },
                [
                  _c(
                    "multiselect",
                    {
                      staticClass: "multiselect-vue",
                      attrs: {
                        options: _vm.subAdminsGroups,
                        placeholder: _vm.t("settings", "Set user as admin for"),
                        label: "name",
                        "track-by": "id",
                        multiple: true,
                        "close-on-select": false,
                        "tag-width": 60
                      },
                      model: {
                        value: _vm.newUser.subAdminsGroups,
                        callback: function($$v) {
                          _vm.$set(_vm.newUser, "subAdminsGroups", $$v)
                        },
                        expression: "newUser.subAdminsGroups"
                      }
                    },
                    [
                      _c(
                        "span",
                        { attrs: { slot: "noResult" }, slot: "noResult" },
                        [_vm._v(_vm._s(_vm.t("settings", "No results")))]
                      )
                    ]
                  )
                ],
                1
              )
            : _vm._e(),
          _vm._v(" "),
          _c(
            "div",
            { staticClass: "quota" },
            [
              _c("multiselect", {
                staticClass: "multiselect-vue",
                attrs: {
                  options: _vm.quotaOptions,
                  placeholder: _vm.t("settings", "Select user quota"),
                  label: "label",
                  "track-by": "id",
                  allowEmpty: false,
                  taggable: true
                },
                on: { tag: _vm.validateQuota },
                model: {
                  value: _vm.newUser.quota,
                  callback: function($$v) {
                    _vm.$set(_vm.newUser, "quota", $$v)
                  },
                  expression: "newUser.quota"
                }
              })
            ],
            1
          ),
          _vm._v(" "),
          _vm.showConfig.showLanguages
            ? _c(
                "div",
                { staticClass: "languages" },
                [
                  _c("multiselect", {
                    staticClass: "multiselect-vue",
                    attrs: {
                      options: _vm.languages,
                      placeholder: _vm.t("settings", "Default language"),
                      label: "name",
                      "track-by": "code",
                      allowEmpty: false,
                      "group-values": "languages",
                      "group-label": "label"
                    },
                    model: {
                      value: _vm.newUser.language,
                      callback: function($$v) {
                        _vm.$set(_vm.newUser, "language", $$v)
                      },
                      expression: "newUser.language"
                    }
                  })
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
          _c("div", { staticClass: "userActions" }, [
            _c("input", {
              staticClass: "button primary icon-checkmark-white has-tooltip",
              attrs: {
                type: "submit",
                id: "newsubmit",
                value: "",
                title: _vm.t("settings", "Add a new user")
              }
            })
          ])
        ]
      ),
      _vm._v(" "),
      _vm._l(_vm.filteredUsers, function(user, key) {
        return _c("user-row", {
          key: key,
          attrs: {
            user: user,
            settings: _vm.settings,
            showConfig: _vm.showConfig,
            groups: _vm.groups,
            subAdminsGroups: _vm.subAdminsGroups,
            quotaOptions: _vm.quotaOptions,
            languages: _vm.languages,
            externalActions: _vm.externalActions
          }
        })
      }),
      _vm._v(" "),
      _c(
        "infinite-loading",
        { ref: "infiniteLoading", on: { infinite: _vm.infiniteHandler } },
        [
          _c("div", { attrs: { slot: "spinner" }, slot: "spinner" }, [
            _c("div", { staticClass: "users-icon-loading icon-loading" })
          ]),
          _vm._v(" "),
          _c("div", { attrs: { slot: "no-more" }, slot: "no-more" }, [
            _c("div", { staticClass: "users-list-end" })
          ]),
          _vm._v(" "),
          _c("div", { attrs: { slot: "no-results" }, slot: "no-results" }, [
            _c("div", { attrs: { id: "emptycontent" } }, [
              _c("div", { staticClass: "icon-contacts-dark" }),
              _vm._v(" "),
              _c("h2", [_vm._v(_vm._s(_vm.t("settings", "No users in here")))])
            ])
          ])
        ]
      )
    ],
    2
  )
}
var staticRenderFns = []
render._withStripped = true



/***/ }),

/***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js?!./node_modules/vue-loader/lib/index.js?!./settings/src/components/userList/userRow.vue?vue&type=template&id=5a5e6f59&":
/*!*******************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/vue-loader/lib??vue-loader-options!./settings/src/components/userList/userRow.vue?vue&type=template&id=5a5e6f59& ***!
  \*******************************************************************************************************************************************************************************************************************/
/*! exports provided: render, staticRenderFns */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "render", function() { return render; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "staticRenderFns", function() { return staticRenderFns; });
var render = function() {
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
              "icon-loading-small": _vm.loading.delete || _vm.loading.disable
            }
          },
          [
            !_vm.loading.delete && !_vm.loading.disable
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
                      " 4x"
                  }
                })
              : _vm._e()
          ]
        ),
        _vm._v(" "),
        _c("div", { staticClass: "name" }, [_vm._v(_vm._s(_vm.user.id))]),
        _vm._v(" "),
        _c("div", { staticClass: "obfuscated" }, [
          _vm._v(
            _vm._s(
              _vm.t(
                "settings",
                "You do not have permissions to see the details of this user"
              )
            )
          )
        ])
      ])
    : _c(
        "div",
        {
          staticClass: "row",
          class: { disabled: _vm.loading.delete || _vm.loading.disable },
          attrs: { "data-id": _vm.user.id }
        },
        [
          _c(
            "div",
            {
              staticClass: "avatar",
              class: {
                "icon-loading-small": _vm.loading.delete || _vm.loading.disable
              }
            },
            [
              !_vm.loading.delete && !_vm.loading.disable
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
                        " 4x"
                    }
                  })
                : _vm._e()
            ]
          ),
          _vm._v(" "),
          _c("div", { staticClass: "name" }, [_vm._v(_vm._s(_vm.user.id))]),
          _vm._v(" "),
          _c(
            "form",
            {
              staticClass: "displayName",
              class: { "icon-loading-small": _vm.loading.displayName },
              on: {
                submit: function($event) {
                  $event.preventDefault()
                  return _vm.updateDisplayName($event)
                }
              }
            },
            [
              _vm.user.backendCapabilities.setDisplayName
                ? [
                    _vm.user.backendCapabilities.setDisplayName
                      ? _c("input", {
                          ref: "displayName",
                          attrs: {
                            id: "displayName" + _vm.user.id + _vm.rand,
                            type: "text",
                            disabled:
                              _vm.loading.displayName || _vm.loading.all,
                            autocomplete: "new-password",
                            autocorrect: "off",
                            autocapitalize: "off",
                            spellcheck: "false"
                          },
                          domProps: { value: _vm.user.displayname }
                        })
                      : _vm._e(),
                    _vm._v(" "),
                    _vm.user.backendCapabilities.setDisplayName
                      ? _c("input", {
                          staticClass: "icon-confirm",
                          attrs: { type: "submit", value: "" }
                        })
                      : _vm._e()
                  ]
                : _c(
                    "div",
                    {
                      directives: [
                        {
                          name: "tooltip",
                          rawName: "v-tooltip.auto",
                          value: _vm.t(
                            "settings",
                            "The backend does not support changing the display name"
                          ),
                          expression:
                            "t('settings', 'The backend does not support changing the display name')",
                          modifiers: { auto: true }
                        }
                      ],
                      staticClass: "name"
                    },
                    [_vm._v(_vm._s(_vm.user.displayname))]
                  )
            ],
            2
          ),
          _vm._v(" "),
          _vm.settings.canChangePassword &&
          _vm.user.backendCapabilities.setPassword
            ? _c(
                "form",
                {
                  staticClass: "password",
                  class: { "icon-loading-small": _vm.loading.password },
                  on: {
                    submit: function($event) {
                      $event.preventDefault()
                      return _vm.updatePassword($event)
                    }
                  }
                },
                [
                  _c("input", {
                    ref: "password",
                    attrs: {
                      id: "password" + _vm.user.id + _vm.rand,
                      type: "password",
                      required: "",
                      disabled: _vm.loading.password || _vm.loading.all,
                      minlength: _vm.minPasswordLength,
                      value: "",
                      placeholder: _vm.t("settings", "New password"),
                      autocomplete: "new-password",
                      autocorrect: "off",
                      autocapitalize: "off",
                      spellcheck: "false"
                    }
                  }),
                  _vm._v(" "),
                  _c("input", {
                    staticClass: "icon-confirm",
                    attrs: { type: "submit", value: "" }
                  })
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
                submit: function($event) {
                  $event.preventDefault()
                  return _vm.updateEmail($event)
                }
              }
            },
            [
              _c("input", {
                ref: "mailAddress",
                attrs: {
                  id: "mailAddress" + _vm.user.id + _vm.rand,
                  type: "email",
                  disabled: _vm.loading.mailAddress || _vm.loading.all,
                  autocomplete: "new-password",
                  autocorrect: "off",
                  autocapitalize: "off",
                  spellcheck: "false"
                },
                domProps: { value: _vm.user.email }
              }),
              _vm._v(" "),
              _c("input", {
                staticClass: "icon-confirm",
                attrs: { type: "submit", value: "" }
              })
            ]
          ),
          _vm._v(" "),
          _c(
            "div",
            {
              staticClass: "groups",
              class: { "icon-loading-small": _vm.loading.groups }
            },
            [
              _c(
                "multiselect",
                {
                  staticClass: "multiselect-vue",
                  attrs: {
                    value: _vm.userGroups,
                    options: _vm.availableGroups,
                    disabled: _vm.loading.groups || _vm.loading.all,
                    "tag-placeholder": "create",
                    placeholder: _vm.t("settings", "Add user in group"),
                    label: "name",
                    "track-by": "id",
                    limit: 2,
                    multiple: true,
                    taggable: _vm.settings.isAdmin,
                    closeOnSelect: false,
                    "tag-width": 60
                  },
                  on: {
                    tag: _vm.createGroup,
                    select: _vm.addUserGroup,
                    remove: _vm.removeUserGroup
                  }
                },
                [
                  _c(
                    "span",
                    {
                      directives: [
                        {
                          name: "tooltip",
                          rawName: "v-tooltip.auto",
                          value: _vm.formatGroupsTitle(_vm.userGroups),
                          expression: "formatGroupsTitle(userGroups)",
                          modifiers: { auto: true }
                        }
                      ],
                      staticClass: "multiselect__limit",
                      attrs: { slot: "limit" },
                      slot: "limit"
                    },
                    [_vm._v("+" + _vm._s(_vm.userGroups.length - 2))]
                  ),
                  _vm._v(" "),
                  _c(
                    "span",
                    { attrs: { slot: "noResult" }, slot: "noResult" },
                    [_vm._v(_vm._s(_vm.t("settings", "No results")))]
                  )
                ]
              )
            ],
            1
          ),
          _vm._v(" "),
          _vm.subAdminsGroups.length > 0 && _vm.settings.isAdmin
            ? _c(
                "div",
                {
                  staticClass: "subadmins",
                  class: { "icon-loading-small": _vm.loading.subadmins }
                },
                [
                  _c(
                    "multiselect",
                    {
                      staticClass: "multiselect-vue",
                      attrs: {
                        value: _vm.userSubAdminsGroups,
                        options: _vm.subAdminsGroups,
                        disabled: _vm.loading.subadmins || _vm.loading.all,
                        placeholder: _vm.t("settings", "Set user as admin for"),
                        label: "name",
                        "track-by": "id",
                        limit: 2,
                        multiple: true,
                        closeOnSelect: false,
                        "tag-width": 60
                      },
                      on: {
                        select: _vm.addUserSubAdmin,
                        remove: _vm.removeUserSubAdmin
                      }
                    },
                    [
                      _c(
                        "span",
                        {
                          directives: [
                            {
                              name: "tooltip",
                              rawName: "v-tooltip.auto",
                              value: _vm.formatGroupsTitle(
                                _vm.userSubAdminsGroups
                              ),
                              expression:
                                "formatGroupsTitle(userSubAdminsGroups)",
                              modifiers: { auto: true }
                            }
                          ],
                          staticClass: "multiselect__limit",
                          attrs: { slot: "limit" },
                          slot: "limit"
                        },
                        [
                          _vm._v(
                            "+" + _vm._s(_vm.userSubAdminsGroups.length - 2)
                          )
                        ]
                      ),
                      _vm._v(" "),
                      _c(
                        "span",
                        { attrs: { slot: "noResult" }, slot: "noResult" },
                        [_vm._v(_vm._s(_vm.t("settings", "No results")))]
                      )
                    ]
                  )
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
                  modifiers: { auto: true }
                }
              ],
              staticClass: "quota",
              class: { "icon-loading-small": _vm.loading.quota }
            },
            [
              _c("multiselect", {
                staticClass: "multiselect-vue",
                attrs: {
                  value: _vm.userQuota,
                  options: _vm.quotaOptions,
                  disabled: _vm.loading.quota || _vm.loading.all,
                  "tag-placeholder": "create",
                  placeholder: _vm.t("settings", "Select user quota"),
                  label: "label",
                  "track-by": "id",
                  allowEmpty: false,
                  taggable: true
                },
                on: { tag: _vm.validateQuota, input: _vm.setUserQuota }
              }),
              _vm._v(" "),
              _c("progress", {
                staticClass: "quota-user-progress",
                class: { warn: _vm.usedQuota > 80 },
                attrs: { max: "100" },
                domProps: { value: _vm.usedQuota }
              })
            ],
            1
          ),
          _vm._v(" "),
          _vm.showConfig.showLanguages
            ? _c(
                "div",
                {
                  staticClass: "languages",
                  class: { "icon-loading-small": _vm.loading.languages }
                },
                [
                  _c("multiselect", {
                    staticClass: "multiselect-vue",
                    attrs: {
                      value: _vm.userLanguage,
                      options: _vm.languages,
                      disabled: _vm.loading.languages || _vm.loading.all,
                      placeholder: _vm.t("settings", "No language set"),
                      label: "name",
                      "track-by": "code",
                      allowEmpty: false,
                      "group-values": "languages",
                      "group-label": "label"
                    },
                    on: { input: _vm.setUserLanguage }
                  })
                ],
                1
              )
            : _vm._e(),
          _vm._v(" "),
          _vm.showConfig.showStoragePath
            ? _c("div", { staticClass: "storageLocation" }, [
                _vm._v(_vm._s(_vm.user.storageLocation))
              ])
            : _vm._e(),
          _vm._v(" "),
          _vm.showConfig.showUserBackend
            ? _c("div", { staticClass: "userBackend" }, [
                _vm._v(_vm._s(_vm.user.backend))
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
                      value:
                        _vm.user.lastLogin > 0
                          ? _vm.OC.Util.formatDate(_vm.user.lastLogin)
                          : "",
                      expression:
                        "user.lastLogin>0 ? OC.Util.formatDate(user.lastLogin) : ''",
                      modifiers: { auto: true }
                    }
                  ],
                  staticClass: "lastLogin"
                },
                [
                  _vm._v(
                    "\n\t\t" +
                      _vm._s(
                        _vm.user.lastLogin > 0
                          ? _vm.OC.Util.relativeModifiedDate(_vm.user.lastLogin)
                          : _vm.t("settings", "Never")
                      ) +
                      "\n\t"
                  )
                ]
              )
            : _vm._e(),
          _vm._v(" "),
          _c("div", { staticClass: "userActions" }, [
            _vm.OC.currentUser !== _vm.user.id &&
            _vm.user.id !== "admin" &&
            !_vm.loading.all
              ? _c("div", { staticClass: "toggleUserActions" }, [
                  _c("div", {
                    directives: [
                      {
                        name: "click-outside",
                        rawName: "v-click-outside",
                        value: _vm.hideMenu,
                        expression: "hideMenu"
                      }
                    ],
                    staticClass: "icon-more",
                    on: { click: _vm.toggleMenu }
                  }),
                  _vm._v(" "),
                  _c(
                    "div",
                    {
                      staticClass: "popovermenu",
                      class: { open: _vm.openedMenu }
                    },
                    [_c("popover-menu", { attrs: { menu: _vm.userActions } })],
                    1
                  )
                ])
              : _vm._e(),
            _vm._v(" "),
            _c(
              "div",
              {
                staticClass: "feedback",
                style: { opacity: _vm.feedbackMessage !== "" ? 1 : 0 }
              },
              [
                _c("div", { staticClass: "icon-checkmark" }),
                _vm._v("\n\t\t\t" + _vm._s(_vm.feedbackMessage) + "\n\t\t")
              ]
            )
          ])
        ]
      )
}
var staticRenderFns = []
render._withStripped = true



/***/ }),

/***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js?!./node_modules/vue-loader/lib/index.js?!./settings/src/views/Users.vue?vue&type=template&id=1c24c2fe&":
/*!***************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/vue-loader/lib??vue-loader-options!./settings/src/views/Users.vue?vue&type=template&id=1c24c2fe& ***!
  \***************************************************************************************************************************************************************************************************/
/*! exports provided: render, staticRenderFns */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "render", function() { return render; });
/* harmony export (binding) */ __webpack_require__.d(__webpack_exports__, "staticRenderFns", function() { return staticRenderFns; });
var render = function() {
  var _vm = this
  var _h = _vm.$createElement
  var _c = _vm._self._c || _h
  return _c("AppContent", {
    attrs: {
      "app-name": "settings",
      "navigation-class": { "icon-loading": _vm.loadingAddGroup }
    },
    scopedSlots: _vm._u([
      {
        key: "navigation",
        fn: function() {
          return [
            _c("AppNavigationNew", {
              attrs: {
                "button-id": "new-user-button",
                text: _vm.t("settings", "New user"),
                "button-class": "icon-add"
              },
              on: { click: _vm.toggleNewUserMenu }
            }),
            _vm._v(" "),
            _c(
              "ul",
              { attrs: { id: "usergrouplist" } },
              _vm._l(_vm.menu, function(item) {
                return _c("AppNavigationItem", {
                  key: item.key,
                  attrs: { item: item }
                })
              }),
              1
            ),
            _vm._v(" "),
            _c("AppNavigationSettings", [
              _c(
                "div",
                [
                  _c("p", [
                    _vm._v(_vm._s(_vm.t("settings", "Default quota:")))
                  ]),
                  _vm._v(" "),
                  _c("multiselect", {
                    staticClass: "multiselect-vue",
                    attrs: {
                      value: _vm.defaultQuota,
                      options: _vm.quotaOptions,
                      "tag-placeholder": "create",
                      placeholder: _vm.t("settings", "Select default quota"),
                      label: "label",
                      "track-by": "id",
                      allowEmpty: false,
                      taggable: true
                    },
                    on: { tag: _vm.validateQuota, input: _vm.setDefaultQuota }
                  })
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
                      expression: "showLanguages"
                    }
                  ],
                  staticClass: "checkbox",
                  attrs: { type: "checkbox", id: "showLanguages" },
                  domProps: {
                    checked: Array.isArray(_vm.showLanguages)
                      ? _vm._i(_vm.showLanguages, null) > -1
                      : _vm.showLanguages
                  },
                  on: {
                    change: function($event) {
                      var $$a = _vm.showLanguages,
                        $$el = $event.target,
                        $$c = $$el.checked ? true : false
                      if (Array.isArray($$a)) {
                        var $$v = null,
                          $$i = _vm._i($$a, $$v)
                        if ($$el.checked) {
                          $$i < 0 && (_vm.showLanguages = $$a.concat([$$v]))
                        } else {
                          $$i > -1 &&
                            (_vm.showLanguages = $$a
                              .slice(0, $$i)
                              .concat($$a.slice($$i + 1)))
                        }
                      } else {
                        _vm.showLanguages = $$c
                      }
                    }
                  }
                }),
                _vm._v(" "),
                _c("label", { attrs: { for: "showLanguages" } }, [
                  _vm._v(_vm._s(_vm.t("settings", "Show Languages")))
                ])
              ]),
              _vm._v(" "),
              _c("div", [
                _c("input", {
                  directives: [
                    {
                      name: "model",
                      rawName: "v-model",
                      value: _vm.showLastLogin,
                      expression: "showLastLogin"
                    }
                  ],
                  staticClass: "checkbox",
                  attrs: { type: "checkbox", id: "showLastLogin" },
                  domProps: {
                    checked: Array.isArray(_vm.showLastLogin)
                      ? _vm._i(_vm.showLastLogin, null) > -1
                      : _vm.showLastLogin
                  },
                  on: {
                    change: function($event) {
                      var $$a = _vm.showLastLogin,
                        $$el = $event.target,
                        $$c = $$el.checked ? true : false
                      if (Array.isArray($$a)) {
                        var $$v = null,
                          $$i = _vm._i($$a, $$v)
                        if ($$el.checked) {
                          $$i < 0 && (_vm.showLastLogin = $$a.concat([$$v]))
                        } else {
                          $$i > -1 &&
                            (_vm.showLastLogin = $$a
                              .slice(0, $$i)
                              .concat($$a.slice($$i + 1)))
                        }
                      } else {
                        _vm.showLastLogin = $$c
                      }
                    }
                  }
                }),
                _vm._v(" "),
                _c("label", { attrs: { for: "showLastLogin" } }, [
                  _vm._v(_vm._s(_vm.t("settings", "Show last login")))
                ])
              ]),
              _vm._v(" "),
              _c("div", [
                _c("input", {
                  directives: [
                    {
                      name: "model",
                      rawName: "v-model",
                      value: _vm.showUserBackend,
                      expression: "showUserBackend"
                    }
                  ],
                  staticClass: "checkbox",
                  attrs: { type: "checkbox", id: "showUserBackend" },
                  domProps: {
                    checked: Array.isArray(_vm.showUserBackend)
                      ? _vm._i(_vm.showUserBackend, null) > -1
                      : _vm.showUserBackend
                  },
                  on: {
                    change: function($event) {
                      var $$a = _vm.showUserBackend,
                        $$el = $event.target,
                        $$c = $$el.checked ? true : false
                      if (Array.isArray($$a)) {
                        var $$v = null,
                          $$i = _vm._i($$a, $$v)
                        if ($$el.checked) {
                          $$i < 0 && (_vm.showUserBackend = $$a.concat([$$v]))
                        } else {
                          $$i > -1 &&
                            (_vm.showUserBackend = $$a
                              .slice(0, $$i)
                              .concat($$a.slice($$i + 1)))
                        }
                      } else {
                        _vm.showUserBackend = $$c
                      }
                    }
                  }
                }),
                _vm._v(" "),
                _c("label", { attrs: { for: "showUserBackend" } }, [
                  _vm._v(_vm._s(_vm.t("settings", "Show user backend")))
                ])
              ]),
              _vm._v(" "),
              _c("div", [
                _c("input", {
                  directives: [
                    {
                      name: "model",
                      rawName: "v-model",
                      value: _vm.showStoragePath,
                      expression: "showStoragePath"
                    }
                  ],
                  staticClass: "checkbox",
                  attrs: { type: "checkbox", id: "showStoragePath" },
                  domProps: {
                    checked: Array.isArray(_vm.showStoragePath)
                      ? _vm._i(_vm.showStoragePath, null) > -1
                      : _vm.showStoragePath
                  },
                  on: {
                    change: function($event) {
                      var $$a = _vm.showStoragePath,
                        $$el = $event.target,
                        $$c = $$el.checked ? true : false
                      if (Array.isArray($$a)) {
                        var $$v = null,
                          $$i = _vm._i($$a, $$v)
                        if ($$el.checked) {
                          $$i < 0 && (_vm.showStoragePath = $$a.concat([$$v]))
                        } else {
                          $$i > -1 &&
                            (_vm.showStoragePath = $$a
                              .slice(0, $$i)
                              .concat($$a.slice($$i + 1)))
                        }
                      } else {
                        _vm.showStoragePath = $$c
                      }
                    }
                  }
                }),
                _vm._v(" "),
                _c("label", { attrs: { for: "showStoragePath" } }, [
                  _vm._v(_vm._s(_vm.t("settings", "Show storage path")))
                ])
              ])
            ])
          ]
        },
        proxy: true
      },
      {
        key: "content",
        fn: function() {
          return [
            _c("user-list", {
              attrs: {
                users: _vm.users,
                showConfig: _vm.showConfig,
                selectedGroup: _vm.selectedGroup,
                externalActions: _vm.externalActions
              }
            })
          ]
        },
        proxy: true
      }
    ])
  })
}
var staticRenderFns = []
render._withStripped = true



/***/ }),

/***/ "./settings/src/components/userList.vue":
/*!**********************************************!*\
  !*** ./settings/src/components/userList.vue ***!
  \**********************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _userList_vue_vue_type_template_id_1347754e___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./userList.vue?vue&type=template&id=1347754e& */ "./settings/src/components/userList.vue?vue&type=template&id=1347754e&");
/* harmony import */ var _userList_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./userList.vue?vue&type=script&lang=js& */ "./settings/src/components/userList.vue?vue&type=script&lang=js&");
/* empty/unused harmony star reexport *//* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */

var component = Object(_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__["default"])(
  _userList_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _userList_vue_vue_type_template_id_1347754e___WEBPACK_IMPORTED_MODULE_0__["render"],
  _userList_vue_vue_type_template_id_1347754e___WEBPACK_IMPORTED_MODULE_0__["staticRenderFns"],
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "settings/src/components/userList.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./settings/src/components/userList.vue?vue&type=script&lang=js&":
/*!***********************************************************************!*\
  !*** ./settings/src/components/userList.vue?vue&type=script&lang=js& ***!
  \***********************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_userList_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../node_modules/babel-loader/lib!../../../node_modules/vue-loader/lib??vue-loader-options!./userList.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js?!./settings/src/components/userList.vue?vue&type=script&lang=js&");
/* empty/unused harmony star reexport */ /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_userList_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./settings/src/components/userList.vue?vue&type=template&id=1347754e&":
/*!*****************************************************************************!*\
  !*** ./settings/src/components/userList.vue?vue&type=template&id=1347754e& ***!
  \*****************************************************************************/
/*! exports provided: render, staticRenderFns */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_userList_vue_vue_type_template_id_1347754e___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!../../../node_modules/vue-loader/lib??vue-loader-options!./userList.vue?vue&type=template&id=1347754e& */ "./node_modules/vue-loader/lib/loaders/templateLoader.js?!./node_modules/vue-loader/lib/index.js?!./settings/src/components/userList.vue?vue&type=template&id=1347754e&");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "render", function() { return _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_userList_vue_vue_type_template_id_1347754e___WEBPACK_IMPORTED_MODULE_0__["render"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "staticRenderFns", function() { return _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_userList_vue_vue_type_template_id_1347754e___WEBPACK_IMPORTED_MODULE_0__["staticRenderFns"]; });



/***/ }),

/***/ "./settings/src/components/userList/userRow.vue":
/*!******************************************************!*\
  !*** ./settings/src/components/userList/userRow.vue ***!
  \******************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _userRow_vue_vue_type_template_id_5a5e6f59___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./userRow.vue?vue&type=template&id=5a5e6f59& */ "./settings/src/components/userList/userRow.vue?vue&type=template&id=5a5e6f59&");
/* harmony import */ var _userRow_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./userRow.vue?vue&type=script&lang=js& */ "./settings/src/components/userList/userRow.vue?vue&type=script&lang=js&");
/* empty/unused harmony star reexport *//* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */

var component = Object(_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__["default"])(
  _userRow_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _userRow_vue_vue_type_template_id_5a5e6f59___WEBPACK_IMPORTED_MODULE_0__["render"],
  _userRow_vue_vue_type_template_id_5a5e6f59___WEBPACK_IMPORTED_MODULE_0__["staticRenderFns"],
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "settings/src/components/userList/userRow.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./settings/src/components/userList/userRow.vue?vue&type=script&lang=js&":
/*!*******************************************************************************!*\
  !*** ./settings/src/components/userList/userRow.vue?vue&type=script&lang=js& ***!
  \*******************************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_userRow_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib!../../../../node_modules/vue-loader/lib??vue-loader-options!./userRow.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js?!./settings/src/components/userList/userRow.vue?vue&type=script&lang=js&");
/* empty/unused harmony star reexport */ /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_userRow_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./settings/src/components/userList/userRow.vue?vue&type=template&id=5a5e6f59&":
/*!*************************************************************************************!*\
  !*** ./settings/src/components/userList/userRow.vue?vue&type=template&id=5a5e6f59& ***!
  \*************************************************************************************/
/*! exports provided: render, staticRenderFns */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_userRow_vue_vue_type_template_id_5a5e6f59___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!../../../../node_modules/vue-loader/lib??vue-loader-options!./userRow.vue?vue&type=template&id=5a5e6f59& */ "./node_modules/vue-loader/lib/loaders/templateLoader.js?!./node_modules/vue-loader/lib/index.js?!./settings/src/components/userList/userRow.vue?vue&type=template&id=5a5e6f59&");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "render", function() { return _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_userRow_vue_vue_type_template_id_5a5e6f59___WEBPACK_IMPORTED_MODULE_0__["render"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "staticRenderFns", function() { return _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_userRow_vue_vue_type_template_id_5a5e6f59___WEBPACK_IMPORTED_MODULE_0__["staticRenderFns"]; });



/***/ }),

/***/ "./settings/src/views/Users.vue":
/*!**************************************!*\
  !*** ./settings/src/views/Users.vue ***!
  \**************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _Users_vue_vue_type_template_id_1c24c2fe___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./Users.vue?vue&type=template&id=1c24c2fe& */ "./settings/src/views/Users.vue?vue&type=template&id=1c24c2fe&");
/* harmony import */ var _Users_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./Users.vue?vue&type=script&lang=js& */ "./settings/src/views/Users.vue?vue&type=script&lang=js&");
/* empty/unused harmony star reexport *//* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */

var component = Object(_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__["default"])(
  _Users_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _Users_vue_vue_type_template_id_1c24c2fe___WEBPACK_IMPORTED_MODULE_0__["render"],
  _Users_vue_vue_type_template_id_1c24c2fe___WEBPACK_IMPORTED_MODULE_0__["staticRenderFns"],
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "settings/src/views/Users.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./settings/src/views/Users.vue?vue&type=script&lang=js&":
/*!***************************************************************!*\
  !*** ./settings/src/views/Users.vue?vue&type=script&lang=js& ***!
  \***************************************************************/
/*! exports provided: default */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Users_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../node_modules/babel-loader/lib!../../../node_modules/vue-loader/lib??vue-loader-options!./Users.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js?!./settings/src/views/Users.vue?vue&type=script&lang=js&");
/* empty/unused harmony star reexport */ /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_Users_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./settings/src/views/Users.vue?vue&type=template&id=1c24c2fe&":
/*!*********************************************************************!*\
  !*** ./settings/src/views/Users.vue?vue&type=template&id=1c24c2fe& ***!
  \*********************************************************************/
/*! exports provided: render, staticRenderFns */
/***/ (function(module, __webpack_exports__, __webpack_require__) {

"use strict";
__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_Users_vue_vue_type_template_id_1c24c2fe___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!../../../node_modules/vue-loader/lib??vue-loader-options!./Users.vue?vue&type=template&id=1c24c2fe& */ "./node_modules/vue-loader/lib/loaders/templateLoader.js?!./node_modules/vue-loader/lib/index.js?!./settings/src/views/Users.vue?vue&type=template&id=1c24c2fe&");
/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "render", function() { return _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_Users_vue_vue_type_template_id_1c24c2fe___WEBPACK_IMPORTED_MODULE_0__["render"]; });

/* harmony reexport (safe) */ __webpack_require__.d(__webpack_exports__, "staticRenderFns", function() { return _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_Users_vue_vue_type_template_id_1c24c2fe___WEBPACK_IMPORTED_MODULE_0__["staticRenderFns"]; });



/***/ })

}]);
//# sourceMappingURL=vue-2.js.map