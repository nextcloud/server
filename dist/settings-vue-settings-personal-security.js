/******/ (function() { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./apps/settings/src/main-personal-security.js":
/*!*****************************************************!*\
  !*** ./apps/settings/src/main-personal-security.js ***!
  \*****************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/initial-state */ "./node_modules/@nextcloud/initial-state/dist/index.esm.js");
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var vue_clipboard2__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! vue-clipboard2 */ "./node_modules/vue-clipboard2/vue-clipboard.js");
/* harmony import */ var vue_clipboard2__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(vue_clipboard2__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var v_tooltip__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! v-tooltip */ "./node_modules/v-tooltip/dist/v-tooltip.esm.js");
/* harmony import */ var _components_AuthTokenSection__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./components/AuthTokenSection */ "./apps/settings/src/components/AuthTokenSection.vue");
/**
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @license AGPL-3.0-or-later
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







// eslint-disable-next-line camelcase
__webpack_require__.nc = btoa(OC.requestToken);
vue__WEBPACK_IMPORTED_MODULE_4__["default"].use((vue_clipboard2__WEBPACK_IMPORTED_MODULE_1___default()));
vue__WEBPACK_IMPORTED_MODULE_4__["default"].use(v_tooltip__WEBPACK_IMPORTED_MODULE_2__["default"], {
  defaultHtml: false
});
vue__WEBPACK_IMPORTED_MODULE_4__["default"].prototype.t = t;
var View = vue__WEBPACK_IMPORTED_MODULE_4__["default"].extend(_components_AuthTokenSection__WEBPACK_IMPORTED_MODULE_3__["default"]);
new View({
  propsData: {
    tokens: (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__.loadState)('settings', 'app_tokens'),
    canCreateToken: (0,_nextcloud_initial_state__WEBPACK_IMPORTED_MODULE_0__.loadState)('settings', 'can_create_app_token')
  }
}).$mount('#security-authtokens');

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AuthToken.vue?vue&type=script&lang=js&":
/*!*********************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AuthToken.vue?vue&type=script&lang=js& ***!
  \*********************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _nextcloud_vue__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/vue */ "./node_modules/@nextcloud/vue/dist/index.module.js");


// When using capture groups the following parts are extracted the first is used as the version number, the second as the OS
var userAgentMap = {
  ie: /(?:MSIE|Trident|Trident\/7.0; rv)[ :](\d+)/,
  // Microsoft Edge User Agent from https://msdn.microsoft.com/en-us/library/hh869301(v=vs.85).aspx
  edge: /^Mozilla\/5\.0 \([^)]+\) AppleWebKit\/[0-9.]+ \(KHTML, like Gecko\) Chrome\/[0-9.]+ (?:Mobile Safari|Safari)\/[0-9.]+ Edge\/[0-9.]+$/,
  // Firefox User Agent from https://developer.mozilla.org/en-US/docs/Web/HTTP/Gecko_user_agent_string_reference
  firefox: /^Mozilla\/5\.0 \([^)]*(Windows|OS X|Linux)[^)]+\) Gecko\/[0-9.]+ Firefox\/(\d+)(?:\.\d)?$/,
  // Chrome User Agent from https://developer.chrome.com/multidevice/user-agent
  chrome: /^Mozilla\/5\.0 \([^)]*(Windows|OS X|Linux)[^)]+\) AppleWebKit\/[0-9.]+ \(KHTML, like Gecko\) Chrome\/(\d+)[0-9.]+ (?:Mobile Safari|Safari)\/[0-9.]+$/,
  // Safari User Agent from http://www.useragentstring.com/pages/Safari/
  safari: /^Mozilla\/5\.0 \([^)]*(Windows|OS X)[^)]+\) AppleWebKit\/[0-9.]+ \(KHTML, like Gecko\)(?: Version\/([0-9]+)[0-9.]+)? Safari\/[0-9.A-Z]+$/,
  // Android Chrome user agent: https://developers.google.com/chrome/mobile/docs/user-agent
  androidChrome: /Android.*(?:; (.*) Build\/).*Chrome\/(\d+)[0-9.]+/,
  iphone: / *CPU +iPhone +OS +([0-9]+)_(?:[0-9_])+ +like +Mac +OS +X */,
  ipad: /\(iPad; *CPU +OS +([0-9]+)_(?:[0-9_])+ +like +Mac +OS +X */,
  iosClient: /^Mozilla\/5\.0 \(iOS\) (?:ownCloud|Nextcloud)-iOS.*$/,
  androidClient: /^Mozilla\/5\.0 \(Android\) (?:ownCloud|Nextcloud)-android.*$/,
  iosTalkClient: /^Mozilla\/5\.0 \(iOS\) Nextcloud-Talk.*$/,
  androidTalkClient: /^Mozilla\/5\.0 \(Android\) Nextcloud-Talk.*$/,
  // DAVx5/3.3.8-beta2-gplay (2021/01/02; dav4jvm; okhttp/4.9.0) Android/10
  davx5: /DAV(?:droid|x5)\/([^ ]+)/,
  // Mozilla/5.0 (U; Linux; Maemo; Jolla; Sailfish; like Android 4.3) AppleWebKit/538.1 (KHTML, like Gecko) WebPirate/2.0 like Mobile Safari/538.1 (compatible)
  webPirate: /(Sailfish).*WebPirate\/(\d+)/,
  // Mozilla/5.0 (Maemo; Linux; U; Jolla; Sailfish; Mobile; rv:31.0) Gecko/31.0 Firefox/31.0 SailfishBrowser/1.0
  sailfishBrowser: /(Sailfish).*SailfishBrowser\/(\d+)/,
  // Neon 1.0.0+1
  neon: /Neon \d+\.\d+\.\d+\+\d+/
};
var nameMap = {
  ie: t('setting', 'Internet Explorer'),
  edge: t('setting', 'Edge'),
  firefox: t('setting', 'Firefox'),
  chrome: t('setting', 'Google Chrome'),
  safari: t('setting', 'Safari'),
  androidChrome: t('setting', 'Google Chrome for Android'),
  iphone: t('setting', 'iPhone'),
  ipad: t('setting', 'iPad'),
  iosClient: t('setting', '{productName} iOS app', {
    productName: window.oc_defaults.productName
  }),
  androidClient: t('setting', '{productName} Android app', {
    productName: window.oc_defaults.productName
  }),
  iosTalkClient: t('setting', '{productName} Talk for iOS', {
    productName: window.oc_defaults.productName
  }),
  androidTalkClient: t('setting', '{productName} Talk for Android', {
    productName: window.oc_defaults.productName
  }),
  davx5: 'DAVx5',
  webPirate: 'WebPirate',
  sailfishBrowser: 'SailfishBrowser',
  neon: 'Neon'
};
var iconMap = {
  ie: 'icon-desktop',
  edge: 'icon-desktop',
  firefox: 'icon-desktop',
  chrome: 'icon-desktop',
  safari: 'icon-desktop',
  androidChrome: 'icon-phone',
  iphone: 'icon-phone',
  ipad: 'icon-tablet',
  iosClient: 'icon-phone',
  androidClient: 'icon-phone',
  iosTalkClient: 'icon-phone',
  androidTalkClient: 'icon-phone',
  davx5: 'icon-phone',
  webPirate: 'icon-link',
  sailfishBrowser: 'icon-link'
};
/* harmony default export */ __webpack_exports__["default"] = ({
  name: 'AuthToken',
  components: {
    NcActions: _nextcloud_vue__WEBPACK_IMPORTED_MODULE_0__.NcActions,
    NcActionButton: _nextcloud_vue__WEBPACK_IMPORTED_MODULE_0__.NcActionButton,
    NcActionCheckbox: _nextcloud_vue__WEBPACK_IMPORTED_MODULE_0__.NcActionCheckbox
  },
  props: {
    token: {
      type: Object,
      required: true
    }
  },
  data: function data() {
    return {
      showMore: this.token.canScope || this.token.canDelete,
      renaming: false,
      newName: '',
      oldName: '',
      actionOpen: false
    };
  },
  computed: {
    lastActivityRelative: function lastActivityRelative() {
      return OC.Util.relativeModifiedDate(this.token.lastActivity * 1000);
    },
    lastActivity: function lastActivity() {
      return OC.Util.formatDate(this.token.lastActivity * 1000, 'LLL');
    },
    iconName: function iconName() {
      // pretty format sync client user agent
      var matches = this.token.name.match(/Mozilla\/5\.0 \((\w+)\) (?:mirall|csyncoC)\/(\d+\.\d+\.\d+)/);
      var icon = '';
      if (matches) {
        /* eslint-disable-next-line */
        this.token.name = t('settings', 'Sync client - {os}', {
          os: matches[1],
          version: matches[2]
        });
        icon = 'icon-desktop';
      }

      // preserve title for cases where we format it further
      var title = this.token.name;
      var name = this.token.name;
      for (var client in userAgentMap) {
        var _matches = title.match(userAgentMap[client]);
        if (_matches) {
          if (_matches[2] && _matches[1]) {
            // version number and os
            name = nameMap[client] + ' ' + _matches[2] + ' - ' + _matches[1];
          } else if (_matches[1]) {
            // only version number
            name = nameMap[client] + ' ' + _matches[1];
          } else {
            name = nameMap[client];
          }
          icon = iconMap[client];
        }
      }
      if (this.token.current) {
        name = t('settings', 'This session');
      }
      return {
        icon: icon,
        name: name
      };
    },
    wiping: function wiping() {
      return this.token.type === 2;
    }
  },
  methods: {
    startRename: function startRename() {
      var _this = this;
      // Close action (popover menu)
      this.actionOpen = false;
      this.oldName = this.token.name;
      this.newName = this.token.name;
      this.renaming = true;
      this.$nextTick(function () {
        _this.$refs.input.select();
      });
    },
    cancelRename: function cancelRename() {
      this.renaming = false;
      this.$emit('rename', this.token, this.oldName);
    },
    revoke: function revoke() {
      this.actionOpen = false;
      this.$emit('delete', this.token);
    },
    rename: function rename() {
      this.renaming = false;
      this.$emit('rename', this.token, this.newName);
    },
    wipe: function wipe() {
      this.actionOpen = false;
      this.$emit('wipe', this.token);
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AuthTokenList.vue?vue&type=script&lang=js&":
/*!*************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AuthTokenList.vue?vue&type=script&lang=js& ***!
  \*************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _AuthToken__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./AuthToken */ "./apps/settings/src/components/AuthToken.vue");

/* harmony default export */ __webpack_exports__["default"] = ({
  name: 'AuthTokenList',
  components: {
    AuthToken: _AuthToken__WEBPACK_IMPORTED_MODULE_0__["default"]
  },
  props: {
    tokens: {
      type: Array,
      required: true
    }
  },
  computed: {
    sortedTokens: function sortedTokens() {
      return this.tokens.slice().sort(function (t1, t2) {
        var ts1 = parseInt(t1.lastActivity, 10);
        var ts2 = parseInt(t2.lastActivity, 10);
        return ts2 - ts1;
      });
    }
  },
  methods: {
    toggleScope: function toggleScope(token, scope, value) {
      // Just pass it on
      this.$emit('toggle-scope', token, scope, value);
    },
    rename: function rename(token, newName) {
      // Just pass it on
      this.$emit('rename', token, newName);
    },
    onDelete: function onDelete(token) {
      // Just pass it on
      this.$emit('delete', token);
    },
    onWipe: function onWipe(token) {
      // Just pass it on
      this.$emit('wipe', token);
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AuthTokenSection.vue?vue&type=script&lang=js&":
/*!****************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AuthTokenSection.vue?vue&type=script&lang=js& ***!
  \****************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.js");
/* harmony import */ var _nextcloud_password_confirmation__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/password-confirmation */ "./node_modules/@nextcloud/password-confirmation/dist/main.js");
/* harmony import */ var _nextcloud_password_confirmation__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_password_confirmation__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _nextcloud_password_confirmation_dist_style_css__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/password-confirmation/dist/style.css */ "./node_modules/@nextcloud/password-confirmation/dist/style.css");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.js");
/* harmony import */ var _AuthTokenList__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ./AuthTokenList */ "./apps/settings/src/components/AuthTokenList.vue");
/* harmony import */ var _AuthTokenSetupDialogue__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./AuthTokenSetupDialogue */ "./apps/settings/src/components/AuthTokenSetupDialogue.vue");
function asyncGeneratorStep(gen, resolve, reject, _next, _throw, key, arg) { try { var info = gen[key](arg); var value = info.value; } catch (error) { reject(error); return; } if (info.done) { resolve(value); } else { Promise.resolve(value).then(_next, _throw); } }
function _asyncToGenerator(fn) { return function () { var self = this, args = arguments; return new Promise(function (resolve, reject) { var gen = fn.apply(self, args); function _next(value) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "next", value); } function _throw(err) { asyncGeneratorStep(gen, resolve, reject, _next, _throw, "throw", err); } _next(undefined); }); }; }






var confirm = function confirm() {
  return new Promise(function (resolve) {
    OC.dialogs.confirm(t('settings', 'Do you really want to wipe your data from this device?'), t('settings', 'Confirm wipe'), resolve, true);
  });
};

/**
 * Tap into a promise without losing the value
 *
 * @param {Function} cb the callback
 * @return {any} val the value
 */
var tap = function tap(cb) {
  return function (val) {
    cb(val);
    return val;
  };
};
/* harmony default export */ __webpack_exports__["default"] = ({
  name: 'AuthTokenSection',
  components: {
    AuthTokenSetupDialogue: _AuthTokenSetupDialogue__WEBPACK_IMPORTED_MODULE_5__["default"],
    AuthTokenList: _AuthTokenList__WEBPACK_IMPORTED_MODULE_4__["default"]
  },
  props: {
    tokens: {
      type: Array,
      required: true
    },
    canCreateToken: {
      type: Boolean,
      required: true
    }
  },
  data: function data() {
    return {
      baseUrl: (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_3__.generateUrl)('/settings/personal/authtokens')
    };
  },
  methods: {
    addNewToken: function addNewToken(name) {
      var _this = this;
      console.debug('creating a new app token', name);
      var data = {
        name: name
      };
      return _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].post(this.baseUrl, data).then(function (resp) {
        return resp.data;
      }).then(tap(function () {
        return console.debug('app token created');
      }))
      // eslint-disable-next-line vue/no-mutating-props
      .then(tap(function (data) {
        return _this.tokens.push(data.deviceToken);
      })).catch(function (err) {
        console.error.bind('could not create app password', err);
        OC.Notification.showTemporary(t('settings', 'Error while creating device token'));
        throw err;
      });
    },
    toggleTokenScope: function toggleTokenScope(token, scope, value) {
      console.debug('updating app token scope', token.id, scope, value);
      var oldVal = token.scope[scope];
      token.scope[scope] = value;
      return this.updateToken(token).then(tap(function () {
        return console.debug('app token scope updated');
      })).catch(function (err) {
        console.error.bind('could not update app token scope', err);
        OC.Notification.showTemporary(t('settings', 'Error while updating device token scope'));

        // Restore
        token.scope[scope] = oldVal;
        throw err;
      });
    },
    rename: function rename(token, newName) {
      console.debug('renaming app token', token.id, token.name, newName);
      var oldName = token.name;
      token.name = newName;
      return this.updateToken(token).then(tap(function () {
        return console.debug('app token name updated');
      })).catch(function (err) {
        console.error.bind('could not update app token name', err);
        OC.Notification.showTemporary(t('settings', 'Error while updating device token name'));

        // Restore
        token.name = oldName;
      });
    },
    updateToken: function updateToken(token) {
      return _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].put(this.baseUrl + '/' + token.id, token).then(function (resp) {
        return resp.data;
      });
    },
    deleteToken: function deleteToken(token) {
      var _this2 = this;
      console.debug('deleting app token', token);

      // eslint-disable-next-line vue/no-mutating-props
      this.tokens = this.tokens.filter(function (t) {
        return t !== token;
      });
      return _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"]["delete"](this.baseUrl + '/' + token.id).then(function (resp) {
        return resp.data;
      }).then(tap(function () {
        return console.debug('app token deleted');
      })).catch(function (err) {
        console.error.bind('could not delete app token', err);
        OC.Notification.showTemporary(t('settings', 'Error while deleting the token'));

        // Restore
        // eslint-disable-next-line vue/no-mutating-props
        _this2.tokens.push(token);
      });
    },
    wipeToken: function wipeToken(token) {
      var _this3 = this;
      return _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee() {
        return regeneratorRuntime.wrap(function _callee$(_context) {
          while (1) {
            switch (_context.prev = _context.next) {
              case 0:
                console.debug('wiping app token', token);
                _context.prev = 1;
                _context.next = 4;
                return (0,_nextcloud_password_confirmation__WEBPACK_IMPORTED_MODULE_1__.confirmPassword)();
              case 4:
                _context.next = 6;
                return confirm();
              case 6:
                if (_context.sent) {
                  _context.next = 9;
                  break;
                }
                console.debug('wipe aborted by user');
                return _context.abrupt("return");
              case 9:
                _context.next = 11;
                return _nextcloud_axios__WEBPACK_IMPORTED_MODULE_0__["default"].post(_this3.baseUrl + '/wipe/' + token.id);
              case 11:
                console.debug('app token marked for wipe');
                token.type = 2;
                _context.next = 19;
                break;
              case 15:
                _context.prev = 15;
                _context.t0 = _context["catch"](1);
                console.error('could not wipe app token', _context.t0);
                OC.Notification.showTemporary(t('settings', 'Error while wiping the device with the token'));
              case 19:
              case "end":
                return _context.stop();
            }
          }
        }, _callee, null, [[1, 15]]);
      }))();
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AuthTokenSetupDialogue.vue?vue&type=script&lang=js&":
/*!**********************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AuthTokenSetupDialogue.vue?vue&type=script&lang=js& ***!
  \**********************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _chenfengyuan_vue_qrcode__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @chenfengyuan/vue-qrcode */ "./node_modules/@chenfengyuan/vue-qrcode/dist/vue-qrcode.js");
/* harmony import */ var _chenfengyuan_vue_qrcode__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_chenfengyuan_vue_qrcode__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _nextcloud_password_confirmation__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/password-confirmation */ "./node_modules/@nextcloud/password-confirmation/dist/main.js");
/* harmony import */ var _nextcloud_password_confirmation__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_password_confirmation__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _nextcloud_password_confirmation_dist_style_css__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/password-confirmation/dist/style.css */ "./node_modules/@nextcloud/password-confirmation/dist/style.css");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcButton__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcButton */ "./node_modules/@nextcloud/vue/dist/Components/NcButton.js");
/* harmony import */ var _nextcloud_vue_dist_Components_NcButton__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_NcButton__WEBPACK_IMPORTED_MODULE_4__);





/* harmony default export */ __webpack_exports__["default"] = ({
  name: 'AuthTokenSetupDialogue',
  components: {
    QR: (_chenfengyuan_vue_qrcode__WEBPACK_IMPORTED_MODULE_0___default()),
    NcButton: (_nextcloud_vue_dist_Components_NcButton__WEBPACK_IMPORTED_MODULE_4___default())
  },
  props: {
    add: {
      type: Function,
      required: true
    }
  },
  data: function data() {
    return {
      adding: false,
      loading: false,
      deviceName: '',
      appPassword: '',
      loginName: '',
      passwordCopied: false,
      showQR: false,
      qrUrl: '',
      hoveringCopyButton: false
    };
  },
  computed: {
    copyTooltipOptions: function copyTooltipOptions() {
      if (this.passwordCopied) {
        return t('settings', 'Copied!');
      }
      return t('settings', 'Copy');
    }
  },
  methods: {
    selectInput: function selectInput(e) {
      e.currentTarget.select();
    },
    submit: function submit() {
      var _this = this;
      (0,_nextcloud_password_confirmation__WEBPACK_IMPORTED_MODULE_1__.confirmPassword)().then(function () {
        _this.loading = true;
        return _this.add(_this.deviceName);
      }).then(function (token) {
        _this.adding = true;
        _this.loginName = token.loginName;
        _this.appPassword = token.token;
        var server = window.location.protocol + '//' + window.location.host + (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_3__.getRootUrl)();
        _this.qrUrl = "nc://login/user:".concat(token.loginName, "&password:").concat(token.token, "&server:").concat(server);
        _this.$nextTick(function () {
          _this.$refs.appPassword.select();
        });
      }).catch(function (err) {
        console.error('could not create a new app password', err);
        OC.Notification.showTemporary(t('settings', 'Error while creating device token'));
        _this.reset();
      });
    },
    onCopyPassword: function onCopyPassword() {
      var _this2 = this;
      this.passwordCopied = true;
      this.$refs.clipboardButton.blur();
      setTimeout(function () {
        _this2.passwordCopied = false;
      }, 3000);
    },
    onCopyPasswordFailed: function onCopyPasswordFailed() {
      OC.Notification.showTemporary(t('settings', 'Could not copy app password. Please copy it manually.'));
    },
    reset: function reset() {
      this.adding = false;
      this.loading = false;
      this.showQR = false;
      this.qrUrl = '';
      this.deviceName = '';
      this.appPassword = '';
      this.loginName = '';
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AuthToken.vue?vue&type=template&id=1a411ac0&scoped=true&":
/*!********************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AuthToken.vue?vue&type=template&id=1a411ac0&scoped=true& ***!
  \********************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* binding */ render; },
/* harmony export */   "staticRenderFns": function() { return /* binding */ staticRenderFns; }
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c("tr", {
    class: _vm.wiping,
    attrs: {
      "data-id": _vm.token.id
    }
  }, [_c("td", {
    staticClass: "client"
  }, [_c("div", {
    class: _vm.iconName.icon
  })]), _vm._v(" "), _c("td", {
    staticClass: "token-name"
  }, [_vm.token.canRename && _vm.renaming ? _c("input", {
    directives: [{
      name: "model",
      rawName: "v-model",
      value: _vm.newName,
      expression: "newName"
    }],
    ref: "input",
    attrs: {
      type: "text"
    },
    domProps: {
      value: _vm.newName
    },
    on: {
      keyup: [function ($event) {
        if (!$event.type.indexOf("key") && _vm._k($event.keyCode, "enter", 13, $event.key, "Enter")) return null;
        return _vm.rename.apply(null, arguments);
      }, function ($event) {
        if (!$event.type.indexOf("key") && _vm._k($event.keyCode, "esc", 27, $event.key, ["Esc", "Escape"])) return null;
        return _vm.cancelRename.apply(null, arguments);
      }],
      change: _vm.rename,
      input: function input($event) {
        if ($event.target.composing) return;
        _vm.newName = $event.target.value;
      }
    }
  }) : _c("span", [_vm._v(_vm._s(_vm.iconName.name))]), _vm._v(" "), _vm.wiping ? _c("span", {
    staticClass: "wiping-warning"
  }, [_vm._v("(" + _vm._s(_vm.t("settings", "Marked for remote wipe")) + ")")]) : _vm._e()]), _vm._v(" "), _c("td", [_c("span", {
    staticClass: "last-activity",
    attrs: {
      title: _vm.lastActivity
    }
  }, [_vm._v(_vm._s(_vm.lastActivityRelative))])]), _vm._v(" "), _c("td", {
    staticClass: "more"
  }, [!_vm.token.current ? _c("NcActions", {
    attrs: {
      title: _vm.t("settings", "Device settings"),
      "aria-label": _vm.t("settings", "Device settings"),
      open: _vm.actionOpen
    },
    on: {
      "update:open": function updateOpen($event) {
        _vm.actionOpen = $event;
      }
    }
  }, [_vm.token.type === 1 ? _c("NcActionCheckbox", {
    attrs: {
      checked: _vm.token.scope.filesystem
    },
    on: {
      change: function change($event) {
        $event.stopPropagation();
        $event.preventDefault();
        return _vm.$emit("toggle-scope", _vm.token, "filesystem", !_vm.token.scope.filesystem);
      }
    }
  }, [_vm._v("\n\t\t\t\t" + _vm._s(_vm.t("settings", "Allow filesystem access")) + "\n\t\t\t")]) : _vm._e(), _vm._v(" "), _vm.token.canRename ? _c("NcActionButton", {
    attrs: {
      icon: "icon-rename"
    },
    on: {
      click: function click($event) {
        $event.stopPropagation();
        $event.preventDefault();
        return _vm.startRename.apply(null, arguments);
      }
    }
  }, [_vm._v("\n\t\t\t\t" + _vm._s(_vm.t("settings", "Rename")) + "\n\t\t\t")]) : _vm._e(), _vm._v(" "), _vm.token.canDelete ? [_vm.token.type !== 2 ? [_c("NcActionButton", {
    attrs: {
      icon: "icon-delete"
    },
    on: {
      click: function click($event) {
        $event.stopPropagation();
        $event.preventDefault();
        return _vm.revoke.apply(null, arguments);
      }
    }
  }, [_vm._v("\n\t\t\t\t\t\t" + _vm._s(_vm.t("settings", "Revoke")) + "\n\t\t\t\t\t")]), _vm._v(" "), _c("NcActionButton", {
    attrs: {
      icon: "icon-delete"
    },
    on: {
      click: function click($event) {
        $event.stopPropagation();
        $event.preventDefault();
        return _vm.wipe.apply(null, arguments);
      }
    }
  }, [_vm._v("\n\t\t\t\t\t\t" + _vm._s(_vm.t("settings", "Wipe device")) + "\n\t\t\t\t\t")])] : _vm.token.type === 2 ? _c("NcActionButton", {
    attrs: {
      icon: "icon-delete",
      title: _vm.t("settings", "Revoke")
    },
    on: {
      click: function click($event) {
        $event.stopPropagation();
        $event.preventDefault();
        return _vm.revoke.apply(null, arguments);
      }
    }
  }, [_vm._v("\n\t\t\t\t\t" + _vm._s(_vm.t("settings", "Revoking this token might prevent the wiping of your device if it has not started the wipe yet.")) + "\n\t\t\t\t")]) : _vm._e()] : _vm._e()], 2) : _vm._e()], 1)]);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AuthTokenList.vue?vue&type=template&id=04e85c7e&scoped=true&":
/*!************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AuthTokenList.vue?vue&type=template&id=04e85c7e&scoped=true& ***!
  \************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* binding */ render; },
/* harmony export */   "staticRenderFns": function() { return /* binding */ staticRenderFns; }
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c("table", {
    attrs: {
      id: "app-tokens-table"
    }
  }, [_vm.tokens.length ? _c("thead", [_c("tr", [_c("th"), _vm._v(" "), _c("th", [_vm._v(_vm._s(_vm.t("settings", "Device")))]), _vm._v(" "), _c("th", [_vm._v(_vm._s(_vm.t("settings", "Last activity")))]), _vm._v(" "), _c("th")])]) : _vm._e(), _vm._v(" "), _c("tbody", {
    staticClass: "token-list"
  }, _vm._l(_vm.sortedTokens, function (token) {
    return _c("AuthToken", {
      key: token.id,
      attrs: {
        token: token
      },
      on: {
        "toggle-scope": _vm.toggleScope,
        rename: _vm.rename,
        delete: _vm.onDelete,
        wipe: _vm.onWipe
      }
    });
  }), 1)]);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AuthTokenSection.vue?vue&type=template&id=3d9b79b5&scoped=true&":
/*!***************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AuthTokenSection.vue?vue&type=template&id=3d9b79b5&scoped=true& ***!
  \***************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* binding */ render; },
/* harmony export */   "staticRenderFns": function() { return /* binding */ staticRenderFns; }
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return _c("div", {
    staticClass: "section",
    attrs: {
      id: "security"
    }
  }, [_c("h2", [_vm._v(_vm._s(_vm.t("settings", "Devices & sessions", {}, undefined, {
    sanitize: false
  })))]), _vm._v(" "), _c("p", {
    staticClass: "settings-hint hidden-when-empty"
  }, [_vm._v("\n\t\t" + _vm._s(_vm.t("settings", "Web, desktop and mobile clients currently logged in to your account.")) + "\n\t")]), _vm._v(" "), _c("AuthTokenList", {
    attrs: {
      tokens: _vm.tokens
    },
    on: {
      "toggle-scope": _vm.toggleTokenScope,
      rename: _vm.rename,
      delete: _vm.deleteToken,
      wipe: _vm.wipeToken
    }
  }), _vm._v(" "), _vm.canCreateToken ? _c("AuthTokenSetupDialogue", {
    attrs: {
      add: _vm.addNewToken
    }
  }) : _vm._e()], 1);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AuthTokenSetupDialogue.vue?vue&type=template&id=69a2f445&scoped=true&":
/*!*********************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AuthTokenSetupDialogue.vue?vue&type=template&id=69a2f445&scoped=true& ***!
  \*********************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* binding */ render; },
/* harmony export */   "staticRenderFns": function() { return /* binding */ staticRenderFns; }
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c;
  return !_vm.adding ? _c("div", {
    staticClass: "row spacing"
  }, [_c("input", {
    directives: [{
      name: "model",
      rawName: "v-model",
      value: _vm.deviceName,
      expression: "deviceName"
    }],
    attrs: {
      type: "text",
      maxlength: 120,
      disabled: _vm.loading,
      placeholder: _vm.t("settings", "App name")
    },
    domProps: {
      value: _vm.deviceName
    },
    on: {
      keydown: function keydown($event) {
        if (!$event.type.indexOf("key") && _vm._k($event.keyCode, "enter", 13, $event.key, "Enter")) return null;
        return _vm.submit.apply(null, arguments);
      },
      input: function input($event) {
        if ($event.target.composing) return;
        _vm.deviceName = $event.target.value;
      }
    }
  }), _vm._v(" "), _c("NcButton", {
    attrs: {
      disabled: _vm.loading || _vm.deviceName.length === 0,
      type: "primary"
    },
    on: {
      click: _vm.submit
    }
  }, [_vm._v("\n\t\t" + _vm._s(_vm.t("settings", "Create new app password")) + "\n\t")])], 1) : _c("div", {
    staticClass: "spacing"
  }, [_vm._v("\n\t" + _vm._s(_vm.t("settings", "Use the credentials below to configure your app or device.")) + "\n\t" + _vm._s(_vm.t("settings", "For security reasons this password will only be shown once.")) + "\n\t"), _c("div", {
    staticClass: "app-password-row"
  }, [_c("label", {
    staticClass: "app-password-label",
    attrs: {
      for: "app-username"
    }
  }, [_vm._v(_vm._s(_vm.t("settings", "Username")))]), _vm._v(" "), _c("input", {
    staticClass: "monospaced",
    attrs: {
      id: "app-username",
      type: "text",
      readonly: "readonly"
    },
    domProps: {
      value: _vm.loginName
    },
    on: {
      focus: _vm.selectInput
    }
  })]), _vm._v(" "), _c("div", {
    staticClass: "app-password-row"
  }, [_c("label", {
    staticClass: "app-password-label",
    attrs: {
      for: "app-password"
    }
  }, [_vm._v(_vm._s(_vm.t("settings", "Password")))]), _vm._v(" "), _c("input", {
    ref: "appPassword",
    staticClass: "monospaced",
    attrs: {
      id: "app-password",
      type: "text",
      readonly: "readonly"
    },
    domProps: {
      value: _vm.appPassword
    },
    on: {
      focus: _vm.selectInput
    }
  }), _vm._v(" "), _c("a", {
    directives: [{
      name: "clipboard",
      rawName: "v-clipboard:copy",
      value: _vm.appPassword,
      expression: "appPassword",
      arg: "copy"
    }, {
      name: "clipboard",
      rawName: "v-clipboard:success",
      value: _vm.onCopyPassword,
      expression: "onCopyPassword",
      arg: "success"
    }, {
      name: "clipboard",
      rawName: "v-clipboard:error",
      value: _vm.onCopyPasswordFailed,
      expression: "onCopyPasswordFailed",
      arg: "error"
    }],
    ref: "clipboardButton",
    staticClass: "icon icon-clippy",
    attrs: {
      title: _vm.copyTooltipOptions,
      "aria-label": _vm.copyTooltipOptions
    },
    on: {
      mouseover: function mouseover($event) {
        _vm.hoveringCopyButton = true;
      },
      mouseleave: function mouseleave($event) {
        _vm.hoveringCopyButton = false;
      }
    }
  }), _vm._v(" "), _c("NcButton", {
    on: {
      click: _vm.reset
    }
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.t("settings", "Done")) + "\n\t\t")])], 1), _vm._v(" "), _c("div", {
    staticClass: "app-password-row"
  }, [_c("span", {
    staticClass: "app-password-label"
  }), _vm._v(" "), !_vm.showQR ? _c("a", {
    on: {
      click: function click($event) {
        _vm.showQR = true;
      }
    }
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.t("settings", "Show QR code for mobile apps")) + "\n\t\t")]) : _c("QR", {
    attrs: {
      value: _vm.qrUrl
    }
  })], 1)]);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AuthToken.vue?vue&type=style&index=0&id=1a411ac0&lang=scss&scoped=true&":
/*!***************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AuthToken.vue?vue&type=style&index=0&id=1a411ac0&lang=scss&scoped=true& ***!
  \***************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/noSourceMaps.js */ "./node_modules/css-loader/dist/runtime/noSourceMaps.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__);
// Imports


var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default()((_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default()));
// Module
___CSS_LOADER_EXPORT___.push([module.id, ".wiping[data-v-1a411ac0] {\n  background-color: var(--color-background-darker);\n}\ntd[data-v-1a411ac0] {\n  border-top: 1px solid var(--color-border);\n  max-width: 200px;\n  white-space: normal;\n  vertical-align: middle;\n  position: relative;\n}\ntd.client[data-v-1a411ac0], td.more[data-v-1a411ac0] {\n  overflow: visible;\n  position: relative;\n  width: 44px;\n  height: 44px;\n}\ntd.token-name[data-v-1a411ac0] {\n  padding: 10px 6px;\n}\ntd.token-name.token-rename[data-v-1a411ac0] {\n  padding: 0;\n}\ntd.token-name input[data-v-1a411ac0] {\n  width: 100%;\n  margin: 0;\n}\ntd.token-name .wiping-warning[data-v-1a411ac0] {\n  color: var(--color-text-lighter);\n}\ntd.more[data-v-1a411ac0] {\n  padding: 0 10px;\n}\ntd.client div[data-v-1a411ac0] {\n  opacity: 0.57;\n  width: 44px;\n  height: 44px;\n}", ""]);
// Exports
/* harmony default export */ __webpack_exports__["default"] = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AuthTokenList.vue?vue&type=style&index=0&id=04e85c7e&lang=scss&scoped=true&":
/*!*******************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AuthTokenList.vue?vue&type=style&index=0&id=04e85c7e&lang=scss&scoped=true& ***!
  \*******************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/noSourceMaps.js */ "./node_modules/css-loader/dist/runtime/noSourceMaps.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__);
// Imports


var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default()((_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default()));
// Module
___CSS_LOADER_EXPORT___.push([module.id, "table[data-v-04e85c7e] {\n  width: 100%;\n  min-height: 50px;\n  padding-top: 5px;\n  max-width: 580px;\n}\ntable th[data-v-04e85c7e] {\n  opacity: 0.5;\n  padding: 10px 0;\n}\n.token-list td > a.icon-more[data-v-04e85c7e] {\n  transition: opacity var(--animation-quick);\n}\n.token-list a.icon-more[data-v-04e85c7e] {\n  padding: 14px;\n  display: block;\n  width: 44px;\n  height: 44px;\n  opacity: 0.5;\n}\n.token-list tr:hover td > a.icon[data-v-04e85c7e],\n.token-list tr td > a.icon[data-v-04e85c7e]:focus, .token-list tr.active td > a.icon[data-v-04e85c7e] {\n  opacity: 1;\n}", ""]);
// Exports
/* harmony default export */ __webpack_exports__["default"] = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AuthTokenList.vue?vue&type=style&index=1&id=04e85c7e&lang=scss&":
/*!*******************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AuthTokenList.vue?vue&type=style&index=1&id=04e85c7e&lang=scss& ***!
  \*******************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/noSourceMaps.js */ "./node_modules/css-loader/dist/runtime/noSourceMaps.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__);
// Imports


var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default()((_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default()));
// Module
___CSS_LOADER_EXPORT___.push([module.id, "#app-tokens-table tr > *:nth-child(2) {\n  padding-left: 6px;\n}\n#app-tokens-table tr > *:nth-child(3) {\n  text-align: right;\n}", ""]);
// Exports
/* harmony default export */ __webpack_exports__["default"] = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AuthTokenSetupDialogue.vue?vue&type=style&index=0&id=69a2f445&lang=scss&scoped=true&":
/*!****************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AuthTokenSetupDialogue.vue?vue&type=style&index=0&id=69a2f445&lang=scss&scoped=true& ***!
  \****************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/noSourceMaps.js */ "./node_modules/css-loader/dist/runtime/noSourceMaps.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__);
// Imports


var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default()((_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default()));
// Module
___CSS_LOADER_EXPORT___.push([module.id, ".app-password-row[data-v-69a2f445] {\n  display: flex;\n  align-items: center;\n}\n.app-password-row .icon[data-v-69a2f445] {\n  background-size: 16px 16px;\n  display: inline-block;\n  position: relative;\n  top: 3px;\n  margin-left: 5px;\n  margin-right: 8px;\n}\n.app-password-label[data-v-69a2f445] {\n  display: table-cell;\n  padding-right: 1em;\n  text-align: right;\n  vertical-align: middle;\n  width: 100px;\n}\n.row input[data-v-69a2f445] {\n  height: 44px !important;\n  padding: 7px 12px;\n  margin-right: 12px;\n  width: 200px;\n}\n.monospaced[data-v-69a2f445] {\n  width: 245px;\n  font-family: monospace;\n}\n.button-vue[data-v-69a2f445] {\n  display: inline-block;\n  margin: 3px 3px 3px 3px;\n}\n.row[data-v-69a2f445] {\n  display: flex;\n  align-items: center;\n}\n.spacing[data-v-69a2f445] {\n  padding-top: 16px;\n}", ""]);
// Exports
/* harmony default export */ __webpack_exports__["default"] = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AuthToken.vue?vue&type=style&index=0&id=1a411ac0&lang=scss&scoped=true&":
/*!*******************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AuthToken.vue?vue&type=style&index=0&id=1a411ac0&lang=scss&scoped=true& ***!
  \*******************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js */ "./node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/styleDomAPI.js */ "./node_modules/style-loader/dist/runtime/styleDomAPI.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/insertBySelector.js */ "./node_modules/style-loader/dist/runtime/insertBySelector.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js */ "./node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/insertStyleElement.js */ "./node_modules/style-loader/dist/runtime/insertStyleElement.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/styleTagTransform.js */ "./node_modules/style-loader/dist/runtime/styleTagTransform.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AuthToken_vue_vue_type_style_index_0_id_1a411ac0_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AuthToken.vue?vue&type=style&index=0&id=1a411ac0&lang=scss&scoped=true& */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AuthToken.vue?vue&type=style&index=0&id=1a411ac0&lang=scss&scoped=true&");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AuthToken_vue_vue_type_style_index_0_id_1a411ac0_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ __webpack_exports__["default"] = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AuthToken_vue_vue_type_style_index_0_id_1a411ac0_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AuthToken_vue_vue_type_style_index_0_id_1a411ac0_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AuthToken_vue_vue_type_style_index_0_id_1a411ac0_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AuthTokenList.vue?vue&type=style&index=0&id=04e85c7e&lang=scss&scoped=true&":
/*!***********************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AuthTokenList.vue?vue&type=style&index=0&id=04e85c7e&lang=scss&scoped=true& ***!
  \***********************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js */ "./node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/styleDomAPI.js */ "./node_modules/style-loader/dist/runtime/styleDomAPI.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/insertBySelector.js */ "./node_modules/style-loader/dist/runtime/insertBySelector.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js */ "./node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/insertStyleElement.js */ "./node_modules/style-loader/dist/runtime/insertStyleElement.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/styleTagTransform.js */ "./node_modules/style-loader/dist/runtime/styleTagTransform.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AuthTokenList_vue_vue_type_style_index_0_id_04e85c7e_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AuthTokenList.vue?vue&type=style&index=0&id=04e85c7e&lang=scss&scoped=true& */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AuthTokenList.vue?vue&type=style&index=0&id=04e85c7e&lang=scss&scoped=true&");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AuthTokenList_vue_vue_type_style_index_0_id_04e85c7e_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ __webpack_exports__["default"] = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AuthTokenList_vue_vue_type_style_index_0_id_04e85c7e_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AuthTokenList_vue_vue_type_style_index_0_id_04e85c7e_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AuthTokenList_vue_vue_type_style_index_0_id_04e85c7e_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AuthTokenList.vue?vue&type=style&index=1&id=04e85c7e&lang=scss&":
/*!***********************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AuthTokenList.vue?vue&type=style&index=1&id=04e85c7e&lang=scss& ***!
  \***********************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js */ "./node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/styleDomAPI.js */ "./node_modules/style-loader/dist/runtime/styleDomAPI.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/insertBySelector.js */ "./node_modules/style-loader/dist/runtime/insertBySelector.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js */ "./node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/insertStyleElement.js */ "./node_modules/style-loader/dist/runtime/insertStyleElement.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/styleTagTransform.js */ "./node_modules/style-loader/dist/runtime/styleTagTransform.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AuthTokenList_vue_vue_type_style_index_1_id_04e85c7e_lang_scss___WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AuthTokenList.vue?vue&type=style&index=1&id=04e85c7e&lang=scss& */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AuthTokenList.vue?vue&type=style&index=1&id=04e85c7e&lang=scss&");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AuthTokenList_vue_vue_type_style_index_1_id_04e85c7e_lang_scss___WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ __webpack_exports__["default"] = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AuthTokenList_vue_vue_type_style_index_1_id_04e85c7e_lang_scss___WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AuthTokenList_vue_vue_type_style_index_1_id_04e85c7e_lang_scss___WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AuthTokenList_vue_vue_type_style_index_1_id_04e85c7e_lang_scss___WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AuthTokenSetupDialogue.vue?vue&type=style&index=0&id=69a2f445&lang=scss&scoped=true&":
/*!********************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AuthTokenSetupDialogue.vue?vue&type=style&index=0&id=69a2f445&lang=scss&scoped=true& ***!
  \********************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js */ "./node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/styleDomAPI.js */ "./node_modules/style-loader/dist/runtime/styleDomAPI.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/insertBySelector.js */ "./node_modules/style-loader/dist/runtime/insertBySelector.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js */ "./node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/insertStyleElement.js */ "./node_modules/style-loader/dist/runtime/insertStyleElement.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/styleTagTransform.js */ "./node_modules/style-loader/dist/runtime/styleTagTransform.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AuthTokenSetupDialogue_vue_vue_type_style_index_0_id_69a2f445_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AuthTokenSetupDialogue.vue?vue&type=style&index=0&id=69a2f445&lang=scss&scoped=true& */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AuthTokenSetupDialogue.vue?vue&type=style&index=0&id=69a2f445&lang=scss&scoped=true&");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AuthTokenSetupDialogue_vue_vue_type_style_index_0_id_69a2f445_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ __webpack_exports__["default"] = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AuthTokenSetupDialogue_vue_vue_type_style_index_0_id_69a2f445_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AuthTokenSetupDialogue_vue_vue_type_style_index_0_id_69a2f445_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AuthTokenSetupDialogue_vue_vue_type_style_index_0_id_69a2f445_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./apps/settings/src/components/AuthToken.vue":
/*!****************************************************!*\
  !*** ./apps/settings/src/components/AuthToken.vue ***!
  \****************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _AuthToken_vue_vue_type_template_id_1a411ac0_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./AuthToken.vue?vue&type=template&id=1a411ac0&scoped=true& */ "./apps/settings/src/components/AuthToken.vue?vue&type=template&id=1a411ac0&scoped=true&");
/* harmony import */ var _AuthToken_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./AuthToken.vue?vue&type=script&lang=js& */ "./apps/settings/src/components/AuthToken.vue?vue&type=script&lang=js&");
/* harmony import */ var _AuthToken_vue_vue_type_style_index_0_id_1a411ac0_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./AuthToken.vue?vue&type=style&index=0&id=1a411ac0&lang=scss&scoped=true& */ "./apps/settings/src/components/AuthToken.vue?vue&type=style&index=0&id=1a411ac0&lang=scss&scoped=true&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _AuthToken_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _AuthToken_vue_vue_type_template_id_1a411ac0_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render,
  _AuthToken_vue_vue_type_template_id_1a411ac0_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "1a411ac0",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/settings/src/components/AuthToken.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./apps/settings/src/components/AuthTokenList.vue":
/*!********************************************************!*\
  !*** ./apps/settings/src/components/AuthTokenList.vue ***!
  \********************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _AuthTokenList_vue_vue_type_template_id_04e85c7e_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./AuthTokenList.vue?vue&type=template&id=04e85c7e&scoped=true& */ "./apps/settings/src/components/AuthTokenList.vue?vue&type=template&id=04e85c7e&scoped=true&");
/* harmony import */ var _AuthTokenList_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./AuthTokenList.vue?vue&type=script&lang=js& */ "./apps/settings/src/components/AuthTokenList.vue?vue&type=script&lang=js&");
/* harmony import */ var _AuthTokenList_vue_vue_type_style_index_0_id_04e85c7e_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./AuthTokenList.vue?vue&type=style&index=0&id=04e85c7e&lang=scss&scoped=true& */ "./apps/settings/src/components/AuthTokenList.vue?vue&type=style&index=0&id=04e85c7e&lang=scss&scoped=true&");
/* harmony import */ var _AuthTokenList_vue_vue_type_style_index_1_id_04e85c7e_lang_scss___WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./AuthTokenList.vue?vue&type=style&index=1&id=04e85c7e&lang=scss& */ "./apps/settings/src/components/AuthTokenList.vue?vue&type=style&index=1&id=04e85c7e&lang=scss&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;



/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_4__["default"])(
  _AuthTokenList_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _AuthTokenList_vue_vue_type_template_id_04e85c7e_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render,
  _AuthTokenList_vue_vue_type_template_id_04e85c7e_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "04e85c7e",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/settings/src/components/AuthTokenList.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./apps/settings/src/components/AuthTokenSection.vue":
/*!***********************************************************!*\
  !*** ./apps/settings/src/components/AuthTokenSection.vue ***!
  \***********************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _AuthTokenSection_vue_vue_type_template_id_3d9b79b5_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./AuthTokenSection.vue?vue&type=template&id=3d9b79b5&scoped=true& */ "./apps/settings/src/components/AuthTokenSection.vue?vue&type=template&id=3d9b79b5&scoped=true&");
/* harmony import */ var _AuthTokenSection_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./AuthTokenSection.vue?vue&type=script&lang=js& */ "./apps/settings/src/components/AuthTokenSection.vue?vue&type=script&lang=js&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */
;
var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__["default"])(
  _AuthTokenSection_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _AuthTokenSection_vue_vue_type_template_id_3d9b79b5_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render,
  _AuthTokenSection_vue_vue_type_template_id_3d9b79b5_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "3d9b79b5",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/settings/src/components/AuthTokenSection.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./apps/settings/src/components/AuthTokenSetupDialogue.vue":
/*!*****************************************************************!*\
  !*** ./apps/settings/src/components/AuthTokenSetupDialogue.vue ***!
  \*****************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _AuthTokenSetupDialogue_vue_vue_type_template_id_69a2f445_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./AuthTokenSetupDialogue.vue?vue&type=template&id=69a2f445&scoped=true& */ "./apps/settings/src/components/AuthTokenSetupDialogue.vue?vue&type=template&id=69a2f445&scoped=true&");
/* harmony import */ var _AuthTokenSetupDialogue_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./AuthTokenSetupDialogue.vue?vue&type=script&lang=js& */ "./apps/settings/src/components/AuthTokenSetupDialogue.vue?vue&type=script&lang=js&");
/* harmony import */ var _AuthTokenSetupDialogue_vue_vue_type_style_index_0_id_69a2f445_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./AuthTokenSetupDialogue.vue?vue&type=style&index=0&id=69a2f445&lang=scss&scoped=true& */ "./apps/settings/src/components/AuthTokenSetupDialogue.vue?vue&type=style&index=0&id=69a2f445&lang=scss&scoped=true&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _AuthTokenSetupDialogue_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _AuthTokenSetupDialogue_vue_vue_type_template_id_69a2f445_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render,
  _AuthTokenSetupDialogue_vue_vue_type_template_id_69a2f445_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "69a2f445",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/settings/src/components/AuthTokenSetupDialogue.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./apps/settings/src/components/AuthToken.vue?vue&type=script&lang=js&":
/*!*****************************************************************************!*\
  !*** ./apps/settings/src/components/AuthToken.vue?vue&type=script&lang=js& ***!
  \*****************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AuthToken_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AuthToken.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AuthToken.vue?vue&type=script&lang=js&");
 /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AuthToken_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/settings/src/components/AuthTokenList.vue?vue&type=script&lang=js&":
/*!*********************************************************************************!*\
  !*** ./apps/settings/src/components/AuthTokenList.vue?vue&type=script&lang=js& ***!
  \*********************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AuthTokenList_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AuthTokenList.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AuthTokenList.vue?vue&type=script&lang=js&");
 /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AuthTokenList_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/settings/src/components/AuthTokenSection.vue?vue&type=script&lang=js&":
/*!************************************************************************************!*\
  !*** ./apps/settings/src/components/AuthTokenSection.vue?vue&type=script&lang=js& ***!
  \************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AuthTokenSection_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AuthTokenSection.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AuthTokenSection.vue?vue&type=script&lang=js&");
 /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AuthTokenSection_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/settings/src/components/AuthTokenSetupDialogue.vue?vue&type=script&lang=js&":
/*!******************************************************************************************!*\
  !*** ./apps/settings/src/components/AuthTokenSetupDialogue.vue?vue&type=script&lang=js& ***!
  \******************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AuthTokenSetupDialogue_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AuthTokenSetupDialogue.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AuthTokenSetupDialogue.vue?vue&type=script&lang=js&");
 /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AuthTokenSetupDialogue_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/settings/src/components/AuthToken.vue?vue&type=template&id=1a411ac0&scoped=true&":
/*!***********************************************************************************************!*\
  !*** ./apps/settings/src/components/AuthToken.vue?vue&type=template&id=1a411ac0&scoped=true& ***!
  \***********************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_AuthToken_vue_vue_type_template_id_1a411ac0_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render; },
/* harmony export */   "staticRenderFns": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_AuthToken_vue_vue_type_template_id_1a411ac0_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns; }
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_AuthToken_vue_vue_type_template_id_1a411ac0_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AuthToken.vue?vue&type=template&id=1a411ac0&scoped=true& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AuthToken.vue?vue&type=template&id=1a411ac0&scoped=true&");


/***/ }),

/***/ "./apps/settings/src/components/AuthTokenList.vue?vue&type=template&id=04e85c7e&scoped=true&":
/*!***************************************************************************************************!*\
  !*** ./apps/settings/src/components/AuthTokenList.vue?vue&type=template&id=04e85c7e&scoped=true& ***!
  \***************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_AuthTokenList_vue_vue_type_template_id_04e85c7e_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render; },
/* harmony export */   "staticRenderFns": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_AuthTokenList_vue_vue_type_template_id_04e85c7e_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns; }
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_AuthTokenList_vue_vue_type_template_id_04e85c7e_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AuthTokenList.vue?vue&type=template&id=04e85c7e&scoped=true& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AuthTokenList.vue?vue&type=template&id=04e85c7e&scoped=true&");


/***/ }),

/***/ "./apps/settings/src/components/AuthTokenSection.vue?vue&type=template&id=3d9b79b5&scoped=true&":
/*!******************************************************************************************************!*\
  !*** ./apps/settings/src/components/AuthTokenSection.vue?vue&type=template&id=3d9b79b5&scoped=true& ***!
  \******************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_AuthTokenSection_vue_vue_type_template_id_3d9b79b5_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render; },
/* harmony export */   "staticRenderFns": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_AuthTokenSection_vue_vue_type_template_id_3d9b79b5_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns; }
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_AuthTokenSection_vue_vue_type_template_id_3d9b79b5_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AuthTokenSection.vue?vue&type=template&id=3d9b79b5&scoped=true& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AuthTokenSection.vue?vue&type=template&id=3d9b79b5&scoped=true&");


/***/ }),

/***/ "./apps/settings/src/components/AuthTokenSetupDialogue.vue?vue&type=template&id=69a2f445&scoped=true&":
/*!************************************************************************************************************!*\
  !*** ./apps/settings/src/components/AuthTokenSetupDialogue.vue?vue&type=template&id=69a2f445&scoped=true& ***!
  \************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_AuthTokenSetupDialogue_vue_vue_type_template_id_69a2f445_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render; },
/* harmony export */   "staticRenderFns": function() { return /* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_AuthTokenSetupDialogue_vue_vue_type_template_id_69a2f445_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns; }
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_AuthTokenSetupDialogue_vue_vue_type_template_id_69a2f445_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AuthTokenSetupDialogue.vue?vue&type=template&id=69a2f445&scoped=true& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AuthTokenSetupDialogue.vue?vue&type=template&id=69a2f445&scoped=true&");


/***/ }),

/***/ "./apps/settings/src/components/AuthToken.vue?vue&type=style&index=0&id=1a411ac0&lang=scss&scoped=true&":
/*!**************************************************************************************************************!*\
  !*** ./apps/settings/src/components/AuthToken.vue?vue&type=style&index=0&id=1a411ac0&lang=scss&scoped=true& ***!
  \**************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AuthToken_vue_vue_type_style_index_0_id_1a411ac0_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader/dist/cjs.js!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AuthToken.vue?vue&type=style&index=0&id=1a411ac0&lang=scss&scoped=true& */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AuthToken.vue?vue&type=style&index=0&id=1a411ac0&lang=scss&scoped=true&");


/***/ }),

/***/ "./apps/settings/src/components/AuthTokenList.vue?vue&type=style&index=0&id=04e85c7e&lang=scss&scoped=true&":
/*!******************************************************************************************************************!*\
  !*** ./apps/settings/src/components/AuthTokenList.vue?vue&type=style&index=0&id=04e85c7e&lang=scss&scoped=true& ***!
  \******************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AuthTokenList_vue_vue_type_style_index_0_id_04e85c7e_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader/dist/cjs.js!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AuthTokenList.vue?vue&type=style&index=0&id=04e85c7e&lang=scss&scoped=true& */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AuthTokenList.vue?vue&type=style&index=0&id=04e85c7e&lang=scss&scoped=true&");


/***/ }),

/***/ "./apps/settings/src/components/AuthTokenList.vue?vue&type=style&index=1&id=04e85c7e&lang=scss&":
/*!******************************************************************************************************!*\
  !*** ./apps/settings/src/components/AuthTokenList.vue?vue&type=style&index=1&id=04e85c7e&lang=scss& ***!
  \******************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AuthTokenList_vue_vue_type_style_index_1_id_04e85c7e_lang_scss___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader/dist/cjs.js!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AuthTokenList.vue?vue&type=style&index=1&id=04e85c7e&lang=scss& */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AuthTokenList.vue?vue&type=style&index=1&id=04e85c7e&lang=scss&");


/***/ }),

/***/ "./apps/settings/src/components/AuthTokenSetupDialogue.vue?vue&type=style&index=0&id=69a2f445&lang=scss&scoped=true&":
/*!***************************************************************************************************************************!*\
  !*** ./apps/settings/src/components/AuthTokenSetupDialogue.vue?vue&type=style&index=0&id=69a2f445&lang=scss&scoped=true& ***!
  \***************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_AuthTokenSetupDialogue_vue_vue_type_style_index_0_id_69a2f445_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader/dist/cjs.js!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./AuthTokenSetupDialogue.vue?vue&type=style&index=0&id=69a2f445&lang=scss&scoped=true& */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/settings/src/components/AuthTokenSetupDialogue.vue?vue&type=style&index=0&id=69a2f445&lang=scss&scoped=true&");


/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			id: moduleId,
/******/ 			loaded: false,
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Flag the module as loaded
/******/ 		module.loaded = true;
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = __webpack_modules__;
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/chunk loaded */
/******/ 	!function() {
/******/ 		var deferred = [];
/******/ 		__webpack_require__.O = function(result, chunkIds, fn, priority) {
/******/ 			if(chunkIds) {
/******/ 				priority = priority || 0;
/******/ 				for(var i = deferred.length; i > 0 && deferred[i - 1][2] > priority; i--) deferred[i] = deferred[i - 1];
/******/ 				deferred[i] = [chunkIds, fn, priority];
/******/ 				return;
/******/ 			}
/******/ 			var notFulfilled = Infinity;
/******/ 			for (var i = 0; i < deferred.length; i++) {
/******/ 				var chunkIds = deferred[i][0];
/******/ 				var fn = deferred[i][1];
/******/ 				var priority = deferred[i][2];
/******/ 				var fulfilled = true;
/******/ 				for (var j = 0; j < chunkIds.length; j++) {
/******/ 					if ((priority & 1 === 0 || notFulfilled >= priority) && Object.keys(__webpack_require__.O).every(function(key) { return __webpack_require__.O[key](chunkIds[j]); })) {
/******/ 						chunkIds.splice(j--, 1);
/******/ 					} else {
/******/ 						fulfilled = false;
/******/ 						if(priority < notFulfilled) notFulfilled = priority;
/******/ 					}
/******/ 				}
/******/ 				if(fulfilled) {
/******/ 					deferred.splice(i--, 1)
/******/ 					var r = fn();
/******/ 					if (r !== undefined) result = r;
/******/ 				}
/******/ 			}
/******/ 			return result;
/******/ 		};
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/compat get default export */
/******/ 	!function() {
/******/ 		// getDefaultExport function for compatibility with non-harmony modules
/******/ 		__webpack_require__.n = function(module) {
/******/ 			var getter = module && module.__esModule ?
/******/ 				function() { return module['default']; } :
/******/ 				function() { return module; };
/******/ 			__webpack_require__.d(getter, { a: getter });
/******/ 			return getter;
/******/ 		};
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/define property getters */
/******/ 	!function() {
/******/ 		// define getter functions for harmony exports
/******/ 		__webpack_require__.d = function(exports, definition) {
/******/ 			for(var key in definition) {
/******/ 				if(__webpack_require__.o(definition, key) && !__webpack_require__.o(exports, key)) {
/******/ 					Object.defineProperty(exports, key, { enumerable: true, get: definition[key] });
/******/ 				}
/******/ 			}
/******/ 		};
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/global */
/******/ 	!function() {
/******/ 		__webpack_require__.g = (function() {
/******/ 			if (typeof globalThis === 'object') return globalThis;
/******/ 			try {
/******/ 				return this || new Function('return this')();
/******/ 			} catch (e) {
/******/ 				if (typeof window === 'object') return window;
/******/ 			}
/******/ 		})();
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/hasOwnProperty shorthand */
/******/ 	!function() {
/******/ 		__webpack_require__.o = function(obj, prop) { return Object.prototype.hasOwnProperty.call(obj, prop); }
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	!function() {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = function(exports) {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/node module decorator */
/******/ 	!function() {
/******/ 		__webpack_require__.nmd = function(module) {
/******/ 			module.paths = [];
/******/ 			if (!module.children) module.children = [];
/******/ 			return module;
/******/ 		};
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/jsonp chunk loading */
/******/ 	!function() {
/******/ 		__webpack_require__.b = document.baseURI || self.location.href;
/******/ 		
/******/ 		// object to store loaded and loading chunks
/******/ 		// undefined = chunk not loaded, null = chunk preloaded/prefetched
/******/ 		// [resolve, reject, Promise] = chunk loading, 0 = chunk loaded
/******/ 		var installedChunks = {
/******/ 			"settings-vue-settings-personal-security": 0
/******/ 		};
/******/ 		
/******/ 		// no chunk on demand loading
/******/ 		
/******/ 		// no prefetching
/******/ 		
/******/ 		// no preloaded
/******/ 		
/******/ 		// no HMR
/******/ 		
/******/ 		// no HMR manifest
/******/ 		
/******/ 		__webpack_require__.O.j = function(chunkId) { return installedChunks[chunkId] === 0; };
/******/ 		
/******/ 		// install a JSONP callback for chunk loading
/******/ 		var webpackJsonpCallback = function(parentChunkLoadingFunction, data) {
/******/ 			var chunkIds = data[0];
/******/ 			var moreModules = data[1];
/******/ 			var runtime = data[2];
/******/ 			// add "moreModules" to the modules object,
/******/ 			// then flag all "chunkIds" as loaded and fire callback
/******/ 			var moduleId, chunkId, i = 0;
/******/ 			if(chunkIds.some(function(id) { return installedChunks[id] !== 0; })) {
/******/ 				for(moduleId in moreModules) {
/******/ 					if(__webpack_require__.o(moreModules, moduleId)) {
/******/ 						__webpack_require__.m[moduleId] = moreModules[moduleId];
/******/ 					}
/******/ 				}
/******/ 				if(runtime) var result = runtime(__webpack_require__);
/******/ 			}
/******/ 			if(parentChunkLoadingFunction) parentChunkLoadingFunction(data);
/******/ 			for(;i < chunkIds.length; i++) {
/******/ 				chunkId = chunkIds[i];
/******/ 				if(__webpack_require__.o(installedChunks, chunkId) && installedChunks[chunkId]) {
/******/ 					installedChunks[chunkId][0]();
/******/ 				}
/******/ 				installedChunks[chunkId] = 0;
/******/ 			}
/******/ 			return __webpack_require__.O(result);
/******/ 		}
/******/ 		
/******/ 		var chunkLoadingGlobal = self["webpackChunknextcloud"] = self["webpackChunknextcloud"] || [];
/******/ 		chunkLoadingGlobal.forEach(webpackJsonpCallback.bind(null, 0));
/******/ 		chunkLoadingGlobal.push = webpackJsonpCallback.bind(null, chunkLoadingGlobal.push.bind(chunkLoadingGlobal));
/******/ 	}();
/******/ 	
/******/ 	/* webpack/runtime/nonce */
/******/ 	!function() {
/******/ 		__webpack_require__.nc = undefined;
/******/ 	}();
/******/ 	
/************************************************************************/
/******/ 	
/******/ 	// startup
/******/ 	// Load entry module and return exports
/******/ 	// This entry module depends on other loaded chunks and execution need to be delayed
/******/ 	var __webpack_exports__ = __webpack_require__.O(undefined, ["core-common"], function() { return __webpack_require__("./apps/settings/src/main-personal-security.js"); })
/******/ 	__webpack_exports__ = __webpack_require__.O(__webpack_exports__);
/******/ 	
/******/ })()
;
//# sourceMappingURL=settings-vue-settings-personal-security.js.map?v=3602c5878d0bf3fc04f3