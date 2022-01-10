"use strict";
(self["webpackChunknextcloud"] = self["webpackChunknextcloud"] || []).push([["user-status-modal"],{

/***/ "./apps/user_status/src/filters/clearAtFilter.js":
/*!*******************************************************!*\
  !*** ./apps/user_status/src/filters/clearAtFilter.js ***!
  \*******************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "clearAtFilter": function() { return /* binding */ clearAtFilter; }
/* harmony export */ });
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.js");
/* harmony import */ var _nextcloud_moment__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/moment */ "./node_modules/@nextcloud/moment/dist/index.js");
/* harmony import */ var _nextcloud_moment__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_moment__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _services_dateService__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../services/dateService */ "./apps/user_status/src/services/dateService.js");
/**
 * @copyright Copyright (c) 2020 Georg Ehrke
 *
 * @author Georg Ehrke <oc.list@georgehrke.com>
 *
 * @license GNU AGPL version 3 or any later version
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



/**
 * Formats a clearAt object to be human readable
 *
 * @param {object} clearAt The clearAt object
 * @return {string|null}
 */

var clearAtFilter = function clearAtFilter(clearAt) {
  if (clearAt === null) {
    return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('user_status', 'Don\'t clear');
  }

  if (clearAt.type === 'end-of') {
    switch (clearAt.time) {
      case 'day':
        return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('user_status', 'Today');

      case 'week':
        return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('user_status', 'This week');

      default:
        return null;
    }
  }

  if (clearAt.type === 'period') {
    return _nextcloud_moment__WEBPACK_IMPORTED_MODULE_1___default().duration(clearAt.time * 1000).humanize();
  } // This is not an officially supported type
  // but only used internally to show the remaining time
  // in the Set Status Modal


  if (clearAt.type === '_time') {
    var momentNow = _nextcloud_moment__WEBPACK_IMPORTED_MODULE_1___default()((0,_services_dateService__WEBPACK_IMPORTED_MODULE_2__.dateFactory)());
    var momentClearAt = _nextcloud_moment__WEBPACK_IMPORTED_MODULE_1___default()(clearAt.time, 'X');
    return _nextcloud_moment__WEBPACK_IMPORTED_MODULE_1___default().duration(momentNow.diff(momentClearAt)).humanize();
  }

  return null;
};



/***/ }),

/***/ "./apps/user_status/src/services/clearAtOptionsService.js":
/*!****************************************************************!*\
  !*** ./apps/user_status/src/services/clearAtOptionsService.js ***!
  \****************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "getAllClearAtOptions": function() { return /* binding */ getAllClearAtOptions; }
/* harmony export */ });
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.js");
/**
 * @copyright Copyright (c) 2020 Georg Ehrke
 *
 * @author Georg Ehrke <oc.list@georgehrke.com>
 *
 * @license GNU AGPL version 3 or any later version
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

/**
 * Returns an array
 *
 * @return {object[]}
 */

var getAllClearAtOptions = function getAllClearAtOptions() {
  return [{
    label: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('user_status', 'Don\'t clear'),
    clearAt: null
  }, {
    label: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('user_status', '30 minutes'),
    clearAt: {
      type: 'period',
      time: 1800
    }
  }, {
    label: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('user_status', '1 hour'),
    clearAt: {
      type: 'period',
      time: 3600
    }
  }, {
    label: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('user_status', '4 hours'),
    clearAt: {
      type: 'period',
      time: 14400
    }
  }, {
    label: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('user_status', 'Today'),
    clearAt: {
      type: 'end-of',
      time: 'day'
    }
  }, {
    label: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('user_status', 'This week'),
    clearAt: {
      type: 'end-of',
      time: 'week'
    }
  }];
};



/***/ }),

/***/ "./apps/user_status/src/services/statusOptionsService.js":
/*!***************************************************************!*\
  !*** ./apps/user_status/src/services/statusOptionsService.js ***!
  \***************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "getAllStatusOptions": function() { return /* binding */ getAllStatusOptions; }
/* harmony export */ });
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.js");
/**
 * @copyright Copyright (c) 2020 Georg Ehrke
 *
 * @author Georg Ehrke <oc.list@georgehrke.com>
 * @author Jan C. Borchardt <hey@jancborchardt.net>
 *
 * @license GNU AGPL version 3 or any later version
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

/**
 * Returns a list of all user-definable statuses
 *
 * @return {object[]}
 */

var getAllStatusOptions = function getAllStatusOptions() {
  return [{
    type: 'online',
    label: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('user_status', 'Online'),
    icon: 'icon-user-status-online'
  }, {
    type: 'away',
    label: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('user_status', 'Away'),
    icon: 'icon-user-status-away'
  }, {
    type: 'dnd',
    label: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('user_status', 'Do not disturb'),
    subline: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('user_status', 'Mute all notifications'),
    icon: 'icon-user-status-dnd'
  }, {
    type: 'invisible',
    label: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('user_status', 'Invisible'),
    subline: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.translate)('user_status', 'Appear offline'),
    icon: 'icon-user-status-invisible'
  }];
};



/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/user_status/src/components/ClearAtSelect.vue?vue&type=script&lang=js&":
/*!****************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/user_status/src/components/ClearAtSelect.vue?vue&type=script&lang=js& ***!
  \****************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _nextcloud_vue_dist_Components_Multiselect__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/Multiselect */ "./node_modules/@nextcloud/vue/dist/Components/Multiselect.js");
/* harmony import */ var _nextcloud_vue_dist_Components_Multiselect__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_Multiselect__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _services_clearAtOptionsService__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../services/clearAtOptionsService */ "./apps/user_status/src/services/clearAtOptionsService.js");
/* harmony import */ var _filters_clearAtFilter__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../filters/clearAtFilter */ "./apps/user_status/src/filters/clearAtFilter.js");
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
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
  name: 'ClearAtSelect',
  components: {
    Multiselect: (_nextcloud_vue_dist_Components_Multiselect__WEBPACK_IMPORTED_MODULE_0___default())
  },
  props: {
    clearAt: {
      type: Object,
      default: null
    }
  },
  data: function data() {
    return {
      options: (0,_services_clearAtOptionsService__WEBPACK_IMPORTED_MODULE_1__.getAllClearAtOptions)()
    };
  },
  computed: {
    /**
     * Returns an object of the currently selected option
     *
     * @return {object}
     */
    option: function option() {
      return {
        clearAt: this.clearAt,
        label: (0,_filters_clearAtFilter__WEBPACK_IMPORTED_MODULE_2__.clearAtFilter)(this.clearAt)
      };
    }
  },
  methods: {
    /**
     * Triggered when the user selects a new option.
     *
     * @param {object=} option The new selected option
     */
    select: function select(option) {
      if (!option) {
        return;
      }

      this.$emit('select-clear-at', option.clearAt);
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/user_status/src/components/CustomMessageInput.vue?vue&type=script&lang=js&":
/*!*********************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/user_status/src/components/CustomMessageInput.vue?vue&type=script&lang=js& ***!
  \*********************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
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
  name: 'CustomMessageInput',
  props: {
    message: {
      type: String,
      required: true,
      default: function _default() {
        return '';
      }
    },
    disabled: {
      type: Boolean,
      default: false
    }
  },
  methods: {
    focus: function focus() {
      this.$refs.input.focus();
    },

    /**
     * Notifies the parent component about a changed input
     *
     * @param {Event} event The Change Event
     */
    change: function change(event) {
      this.$emit('change', event.target.value);
    },
    submit: function submit(event) {
      this.$emit('submit', event.target.value);
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/user_status/src/components/OnlineStatusSelect.vue?vue&type=script&lang=js&":
/*!*********************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/user_status/src/components/OnlineStatusSelect.vue?vue&type=script&lang=js& ***!
  \*********************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
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
  name: 'OnlineStatusSelect',
  props: {
    checked: {
      type: Boolean,
      default: false
    },
    icon: {
      type: String,
      required: true
    },
    type: {
      type: String,
      required: true
    },
    label: {
      type: String,
      required: true
    },
    subline: {
      type: String,
      default: null
    }
  },
  computed: {
    id: function id() {
      return "user-status-online-status-".concat(this.type);
    }
  },
  methods: {
    onChange: function onChange() {
      this.$emit('select', this.type);
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/user_status/src/components/PredefinedStatus.vue?vue&type=script&lang=js&":
/*!*******************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/user_status/src/components/PredefinedStatus.vue?vue&type=script&lang=js& ***!
  \*******************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _filters_clearAtFilter__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../filters/clearAtFilter */ "./apps/user_status/src/filters/clearAtFilter.js");
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
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
  name: 'PredefinedStatus',
  filters: {
    clearAtFilter: _filters_clearAtFilter__WEBPACK_IMPORTED_MODULE_0__.clearAtFilter
  },
  props: {
    messageId: {
      type: String,
      required: true
    },
    icon: {
      type: String,
      required: true
    },
    message: {
      type: String,
      required: true
    },
    clearAt: {
      type: Object,
      required: false,
      default: null
    }
  },
  methods: {
    /**
     * Emits an event when the user clicks the row
     */
    select: function select() {
      this.$emit('select');
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/user_status/src/components/PredefinedStatusesList.vue?vue&type=script&lang=js&":
/*!*************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/user_status/src/components/PredefinedStatusesList.vue?vue&type=script&lang=js& ***!
  \*************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _PredefinedStatus__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./PredefinedStatus */ "./apps/user_status/src/components/PredefinedStatus.vue");
/* harmony import */ var vuex__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! vuex */ "./node_modules/vuex/dist/vuex.esm.js");
function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); enumerableOnly && (symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; })), keys.push.apply(keys, symbols); } return keys; }

function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = null != arguments[i] ? arguments[i] : {}; i % 2 ? ownKeys(Object(source), !0).forEach(function (key) { _defineProperty(target, key, source[key]); }) : Object.getOwnPropertyDescriptors ? Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)) : ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } return target; }

function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
//
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
  name: 'PredefinedStatusesList',
  components: {
    PredefinedStatus: _PredefinedStatus__WEBPACK_IMPORTED_MODULE_0__["default"]
  },
  computed: _objectSpread(_objectSpread({}, (0,vuex__WEBPACK_IMPORTED_MODULE_1__.mapState)({
    predefinedStatuses: function predefinedStatuses(state) {
      return state.predefinedStatuses.predefinedStatuses;
    }
  })), {}, {
    /**
     * Indicator whether the predefined statuses have already been loaded
     *
     * @return {boolean}
     */
    hasLoaded: function hasLoaded() {
      return this.predefinedStatuses.length > 0;
    }
  }),

  /**
   * Loads all predefined statuses from the server
   * when this component is mounted
   */
  mounted: function mounted() {
    this.$store.dispatch('loadAllPredefinedStatuses');
  },
  methods: {
    /**
     * Emits an event when the user selects a status
     *
     * @param {object} status The selected status
     */
    selectStatus: function selectStatus(status) {
      this.$emit('select-status', status);
    }
  }
});

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/user_status/src/components/SetStatusModal.vue?vue&type=script&lang=js&":
/*!*****************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/user_status/src/components/SetStatusModal.vue?vue&type=script&lang=js& ***!
  \*****************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/dialogs */ "./node_modules/@nextcloud/dialogs/dist/index.es.js");
/* harmony import */ var _nextcloud_vue_dist_Components_EmojiPicker__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/EmojiPicker */ "./node_modules/@nextcloud/vue/dist/Components/EmojiPicker.js");
/* harmony import */ var _nextcloud_vue_dist_Components_EmojiPicker__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_EmojiPicker__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _nextcloud_vue_dist_Components_Modal__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/Modal */ "./node_modules/@nextcloud/vue/dist/Components/Modal.js");
/* harmony import */ var _nextcloud_vue_dist_Components_Modal__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_nextcloud_vue_dist_Components_Modal__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _services_statusOptionsService__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ../services/statusOptionsService */ "./apps/user_status/src/services/statusOptionsService.js");
/* harmony import */ var _mixins_OnlineStatusMixin__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../mixins/OnlineStatusMixin */ "./apps/user_status/src/mixins/OnlineStatusMixin.js");
/* harmony import */ var _PredefinedStatusesList__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ./PredefinedStatusesList */ "./apps/user_status/src/components/PredefinedStatusesList.vue");
/* harmony import */ var _CustomMessageInput__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! ./CustomMessageInput */ "./apps/user_status/src/components/CustomMessageInput.vue");
/* harmony import */ var _ClearAtSelect__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! ./ClearAtSelect */ "./apps/user_status/src/components/ClearAtSelect.vue");
/* harmony import */ var _OnlineStatusSelect__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ./OnlineStatusSelect */ "./apps/user_status/src/components/OnlineStatusSelect.vue");
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









/* harmony default export */ __webpack_exports__["default"] = ({
  name: 'SetStatusModal',
  components: {
    ClearAtSelect: _ClearAtSelect__WEBPACK_IMPORTED_MODULE_7__["default"],
    CustomMessageInput: _CustomMessageInput__WEBPACK_IMPORTED_MODULE_6__["default"],
    EmojiPicker: (_nextcloud_vue_dist_Components_EmojiPicker__WEBPACK_IMPORTED_MODULE_1___default()),
    Modal: (_nextcloud_vue_dist_Components_Modal__WEBPACK_IMPORTED_MODULE_2___default()),
    OnlineStatusSelect: _OnlineStatusSelect__WEBPACK_IMPORTED_MODULE_8__["default"],
    PredefinedStatusesList: _PredefinedStatusesList__WEBPACK_IMPORTED_MODULE_5__["default"]
  },
  mixins: [_mixins_OnlineStatusMixin__WEBPACK_IMPORTED_MODULE_4__["default"]],
  data: function data() {
    return {
      clearAt: null,
      icon: null,
      message: '',
      messageId: '',
      isSavingStatus: false,
      statuses: (0,_services_statusOptionsService__WEBPACK_IMPORTED_MODULE_3__.getAllStatusOptions)()
    };
  },
  computed: {
    /**
     * Returns the user-set icon or a smiley in case no icon is set
     *
     * @return {string}
     */
    visibleIcon: function visibleIcon() {
      return this.icon || '😀';
    }
  },

  /**
   * Loads the current status when a user opens dialog
   */
  mounted: function mounted() {
    this.messageId = this.$store.state.userStatus.messageId;
    this.icon = this.$store.state.userStatus.icon;
    this.message = this.$store.state.userStatus.message || '';

    if (this.$store.state.userStatus.clearAt !== null) {
      this.clearAt = {
        type: '_time',
        time: this.$store.state.userStatus.clearAt
      };
    }
  },
  methods: {
    /**
     * Closes the Set Status modal
     */
    closeModal: function closeModal() {
      this.$emit('close');
    },

    /**
     * Sets a new icon
     *
     * @param {string} icon The new icon
     */
    setIcon: function setIcon(icon) {
      var _this = this;

      this.messageId = null;
      this.icon = icon;
      this.$nextTick(function () {
        _this.$refs.customMessageInput.focus();
      });
    },

    /**
     * Sets a new message
     *
     * @param {string} message The new message
     */
    setMessage: function setMessage(message) {
      this.messageId = null;
      this.message = message;
    },

    /**
     * Sets a new clearAt value
     *
     * @param {object} clearAt The new clearAt object
     */
    setClearAt: function setClearAt(clearAt) {
      this.clearAt = clearAt;
    },

    /**
     * Sets new icon/message/clearAt based on a predefined message
     *
     * @param {object} status The predefined status object
     */
    selectPredefinedMessage: function selectPredefinedMessage(status) {
      this.messageId = status.id;
      this.clearAt = status.clearAt;
      this.icon = status.icon;
      this.message = status.message;
    },

    /**
     * Saves the status and closes the
     *
     * @return {Promise<void>}
     */
    saveStatus: function saveStatus() {
      var _this2 = this;

      return _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee() {
        return regeneratorRuntime.wrap(function _callee$(_context) {
          while (1) {
            switch (_context.prev = _context.next) {
              case 0:
                if (!_this2.isSavingStatus) {
                  _context.next = 2;
                  break;
                }

                return _context.abrupt("return");

              case 2:
                _context.prev = 2;
                _this2.isSavingStatus = true;

                if (!(_this2.messageId !== undefined && _this2.messageId !== null)) {
                  _context.next = 9;
                  break;
                }

                _context.next = 7;
                return _this2.$store.dispatch('setPredefinedMessage', {
                  messageId: _this2.messageId,
                  clearAt: _this2.clearAt
                });

              case 7:
                _context.next = 11;
                break;

              case 9:
                _context.next = 11;
                return _this2.$store.dispatch('setCustomMessage', {
                  message: _this2.message,
                  icon: _this2.icon,
                  clearAt: _this2.clearAt
                });

              case 11:
                _context.next = 19;
                break;

              case 13:
                _context.prev = 13;
                _context.t0 = _context["catch"](2);
                (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_0__.showError)(_this2.$t('user_status', 'There was an error saving the status'));
                console.debug(_context.t0);
                _this2.isSavingStatus = false;
                return _context.abrupt("return");

              case 19:
                _this2.isSavingStatus = false;

                _this2.closeModal();

              case 21:
              case "end":
                return _context.stop();
            }
          }
        }, _callee, null, [[2, 13]]);
      }))();
    },

    /**
     *
     * @return {Promise<void>}
     */
    clearStatus: function clearStatus() {
      var _this3 = this;

      return _asyncToGenerator( /*#__PURE__*/regeneratorRuntime.mark(function _callee2() {
        return regeneratorRuntime.wrap(function _callee2$(_context2) {
          while (1) {
            switch (_context2.prev = _context2.next) {
              case 0:
                _context2.prev = 0;
                _this3.isSavingStatus = true;
                _context2.next = 4;
                return _this3.$store.dispatch('clearMessage');

              case 4:
                _context2.next = 12;
                break;

              case 6:
                _context2.prev = 6;
                _context2.t0 = _context2["catch"](0);
                (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_0__.showError)(_this3.$t('user_status', 'There was an error clearing the status'));
                console.debug(_context2.t0);
                _this3.isSavingStatus = false;
                return _context2.abrupt("return");

              case 12:
                _this3.isSavingStatus = false;

                _this3.closeModal();

              case 14:
              case "end":
                return _context2.stop();
            }
          }
        }, _callee2, null, [[0, 6]]);
      }))();
    }
  }
});

/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/user_status/src/components/ClearAtSelect.vue?vue&type=style&index=0&id=16308054&lang=scss&scoped=true&":
/*!**********************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/user_status/src/components/ClearAtSelect.vue?vue&type=style&index=0&id=16308054&lang=scss&scoped=true& ***!
  \**********************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_0__);
// Imports

var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_0___default()(function(i){return i[1]});
// Module
___CSS_LOADER_EXPORT___.push([module.id, ".clear-at-select[data-v-16308054] {\n  display: flex;\n  margin-bottom: 10px;\n  align-items: center;\n}\n.clear-at-select__label[data-v-16308054] {\n  margin-right: 10px;\n}\n.clear-at-select .multiselect[data-v-16308054] {\n  flex-grow: 1;\n  min-width: 130px;\n}", ""]);
// Exports
/* harmony default export */ __webpack_exports__["default"] = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/user_status/src/components/CustomMessageInput.vue?vue&type=style&index=0&id=f8ff5dac&lang=scss&scoped=true&":
/*!***************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/user_status/src/components/CustomMessageInput.vue?vue&type=style&index=0&id=f8ff5dac&lang=scss&scoped=true& ***!
  \***************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_0__);
// Imports

var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_0___default()(function(i){return i[1]});
// Module
___CSS_LOADER_EXPORT___.push([module.id, ".custom-input__form[data-v-f8ff5dac] {\n  flex-grow: 1;\n}\n.custom-input__form input[data-v-f8ff5dac] {\n  width: 100%;\n  border-radius: 0 var(--border-radius) var(--border-radius) 0;\n}", ""]);
// Exports
/* harmony default export */ __webpack_exports__["default"] = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/user_status/src/components/OnlineStatusSelect.vue?vue&type=style&index=0&id=08b51a17&lang=scss&scoped=true&":
/*!***************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/user_status/src/components/OnlineStatusSelect.vue?vue&type=style&index=0&id=08b51a17&lang=scss&scoped=true& ***!
  \***************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_0__);
// Imports

var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_0___default()(function(i){return i[1]});
// Module
___CSS_LOADER_EXPORT___.push([module.id, ".user-status-online-select__input[data-v-08b51a17] {\n  position: absolute;\n  top: auto;\n  left: -10000px;\n  overflow: hidden;\n  width: 1px;\n  height: 1px;\n}\n.user-status-online-select__label[data-v-08b51a17] {\n  display: block;\n  margin: 8px;\n  padding: 8px;\n  padding-left: 40px;\n  border: 2px solid var(--color-main-background);\n  border-radius: var(--border-radius-large);\n  background-color: var(--color-background-hover);\n  background-position: 8px center;\n  background-size: 24px;\n}\n.user-status-online-select__label span[data-v-08b51a17], .user-status-online-select__label[data-v-08b51a17] {\n  cursor: pointer;\n}\n.user-status-online-select__input:checked + .user-status-online-select__label[data-v-08b51a17], .user-status-online-select__input:focus + .user-status-online-select__label[data-v-08b51a17], .user-status-online-select__label[data-v-08b51a17]:hover {\n  border-color: var(--color-primary);\n}\n.user-status-online-select__subline[data-v-08b51a17] {\n  display: block;\n  color: var(--color-text-lighter);\n}", ""]);
// Exports
/* harmony default export */ __webpack_exports__["default"] = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/user_status/src/components/PredefinedStatus.vue?vue&type=style&index=0&id=2b4a822e&lang=scss&scoped=true&":
/*!*************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/user_status/src/components/PredefinedStatus.vue?vue&type=style&index=0&id=2b4a822e&lang=scss&scoped=true& ***!
  \*************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_0__);
// Imports

var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_0___default()(function(i){return i[1]});
// Module
___CSS_LOADER_EXPORT___.push([module.id, ".predefined-status[data-v-2b4a822e] {\n  display: flex;\n  flex-wrap: nowrap;\n  justify-content: flex-start;\n  flex-basis: 100%;\n  border-radius: var(--border-radius);\n  align-items: center;\n  min-height: 44px;\n}\n.predefined-status[data-v-2b4a822e]:hover, .predefined-status[data-v-2b4a822e]:focus {\n  background-color: var(--color-background-hover);\n}\n.predefined-status__icon[data-v-2b4a822e] {\n  flex-basis: 40px;\n  text-align: center;\n}\n.predefined-status__message[data-v-2b4a822e] {\n  font-weight: bold;\n  padding: 0 6px;\n}\n.predefined-status__clear-at[data-v-2b4a822e] {\n  opacity: 0.7;\n}\n.predefined-status__clear-at[data-v-2b4a822e]::before {\n  content: \" - \";\n}", ""]);
// Exports
/* harmony default export */ __webpack_exports__["default"] = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/user_status/src/components/PredefinedStatusesList.vue?vue&type=style&index=0&id=31790e1a&lang=scss&scoped=true&":
/*!*******************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/user_status/src/components/PredefinedStatusesList.vue?vue&type=style&index=0&id=31790e1a&lang=scss&scoped=true& ***!
  \*******************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_0__);
// Imports

var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_0___default()(function(i){return i[1]});
// Module
___CSS_LOADER_EXPORT___.push([module.id, ".predefined-statuses-list[data-v-31790e1a] {\n  display: flex;\n  flex-direction: column;\n  margin-bottom: 10px;\n}", ""]);
// Exports
/* harmony default export */ __webpack_exports__["default"] = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/user_status/src/components/SetStatusModal.vue?vue&type=style&index=0&id=1a4980a2&lang=scss&scoped=true&":
/*!***********************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/user_status/src/components/SetStatusModal.vue?vue&type=style&index=0&id=1a4980a2&lang=scss&scoped=true& ***!
  \***********************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_0__);
// Imports

var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_0___default()(function(i){return i[1]});
// Module
___CSS_LOADER_EXPORT___.push([module.id, "[data-v-1a4980a2] .modal-wrapper .prev,[data-v-1a4980a2] .modal-wrapper .next {\n  display: none !important;\n}\n[data-v-1a4980a2] .modal-wrapper .modal-container {\n  max-height: 100% !important;\n}\n[data-v-1a4980a2] .modal-header .modal-title {\n  display: none;\n}\n.set-status-modal[data-v-1a4980a2] {\n  min-height: 200px;\n  padding: 8px 20px 20px 20px;\n  max-height: 95vh;\n  overflow: auto;\n}\n.set-status-modal__header[data-v-1a4980a2] {\n  text-align: center;\n  font-weight: bold;\n}\n.set-status-modal__online-status[data-v-1a4980a2] {\n  display: grid;\n  margin-bottom: 40px;\n  grid-template-columns: 1fr 1fr;\n}\n.set-status-modal__custom-input[data-v-1a4980a2] {\n  display: flex;\n  width: 100%;\n  margin-bottom: 10px;\n}\n.set-status-modal__custom-input .custom-input__emoji-button[data-v-1a4980a2] {\n  flex-basis: 40px;\n  flex-grow: 0;\n  width: 40px;\n  height: 34px;\n  margin-right: 0;\n  border-right: none;\n  border-radius: var(--border-radius) 0 0 var(--border-radius);\n}\n.set-status-modal .status-buttons[data-v-1a4980a2] {\n  display: flex;\n}\n.set-status-modal .status-buttons button[data-v-1a4980a2] {\n  flex-basis: 50%;\n}\n@media only screen and (max-width: 500px) {\n.set-status-modal__online-status[data-v-1a4980a2] {\n    grid-template-columns: none !important;\n}\n}", ""]);
// Exports
/* harmony default export */ __webpack_exports__["default"] = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/user_status/src/components/ClearAtSelect.vue?vue&type=style&index=0&id=16308054&lang=scss&scoped=true&":
/*!**************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/user_status/src/components/ClearAtSelect.vue?vue&type=style&index=0&id=16308054&lang=scss&scoped=true& ***!
  \**************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js */ "./node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_ClearAtSelect_vue_vue_type_style_index_0_id_16308054_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./ClearAtSelect.vue?vue&type=style&index=0&id=16308054&lang=scss&scoped=true& */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/user_status/src/components/ClearAtSelect.vue?vue&type=style&index=0&id=16308054&lang=scss&scoped=true&");

            

var options = {};

options.insert = "head";
options.singleton = false;

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_ClearAtSelect_vue_vue_type_style_index_0_id_16308054_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_1__["default"], options);



/* harmony default export */ __webpack_exports__["default"] = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_ClearAtSelect_vue_vue_type_style_index_0_id_16308054_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_1__["default"].locals || {});

/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/user_status/src/components/CustomMessageInput.vue?vue&type=style&index=0&id=f8ff5dac&lang=scss&scoped=true&":
/*!*******************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/user_status/src/components/CustomMessageInput.vue?vue&type=style&index=0&id=f8ff5dac&lang=scss&scoped=true& ***!
  \*******************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js */ "./node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_CustomMessageInput_vue_vue_type_style_index_0_id_f8ff5dac_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./CustomMessageInput.vue?vue&type=style&index=0&id=f8ff5dac&lang=scss&scoped=true& */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/user_status/src/components/CustomMessageInput.vue?vue&type=style&index=0&id=f8ff5dac&lang=scss&scoped=true&");

            

var options = {};

options.insert = "head";
options.singleton = false;

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_CustomMessageInput_vue_vue_type_style_index_0_id_f8ff5dac_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_1__["default"], options);



/* harmony default export */ __webpack_exports__["default"] = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_CustomMessageInput_vue_vue_type_style_index_0_id_f8ff5dac_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_1__["default"].locals || {});

/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/user_status/src/components/OnlineStatusSelect.vue?vue&type=style&index=0&id=08b51a17&lang=scss&scoped=true&":
/*!*******************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/user_status/src/components/OnlineStatusSelect.vue?vue&type=style&index=0&id=08b51a17&lang=scss&scoped=true& ***!
  \*******************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js */ "./node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_OnlineStatusSelect_vue_vue_type_style_index_0_id_08b51a17_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./OnlineStatusSelect.vue?vue&type=style&index=0&id=08b51a17&lang=scss&scoped=true& */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/user_status/src/components/OnlineStatusSelect.vue?vue&type=style&index=0&id=08b51a17&lang=scss&scoped=true&");

            

var options = {};

options.insert = "head";
options.singleton = false;

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_OnlineStatusSelect_vue_vue_type_style_index_0_id_08b51a17_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_1__["default"], options);



/* harmony default export */ __webpack_exports__["default"] = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_OnlineStatusSelect_vue_vue_type_style_index_0_id_08b51a17_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_1__["default"].locals || {});

/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/user_status/src/components/PredefinedStatus.vue?vue&type=style&index=0&id=2b4a822e&lang=scss&scoped=true&":
/*!*****************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/user_status/src/components/PredefinedStatus.vue?vue&type=style&index=0&id=2b4a822e&lang=scss&scoped=true& ***!
  \*****************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js */ "./node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_PredefinedStatus_vue_vue_type_style_index_0_id_2b4a822e_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./PredefinedStatus.vue?vue&type=style&index=0&id=2b4a822e&lang=scss&scoped=true& */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/user_status/src/components/PredefinedStatus.vue?vue&type=style&index=0&id=2b4a822e&lang=scss&scoped=true&");

            

var options = {};

options.insert = "head";
options.singleton = false;

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_PredefinedStatus_vue_vue_type_style_index_0_id_2b4a822e_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_1__["default"], options);



/* harmony default export */ __webpack_exports__["default"] = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_PredefinedStatus_vue_vue_type_style_index_0_id_2b4a822e_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_1__["default"].locals || {});

/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/user_status/src/components/PredefinedStatusesList.vue?vue&type=style&index=0&id=31790e1a&lang=scss&scoped=true&":
/*!***********************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/user_status/src/components/PredefinedStatusesList.vue?vue&type=style&index=0&id=31790e1a&lang=scss&scoped=true& ***!
  \***********************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js */ "./node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_PredefinedStatusesList_vue_vue_type_style_index_0_id_31790e1a_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./PredefinedStatusesList.vue?vue&type=style&index=0&id=31790e1a&lang=scss&scoped=true& */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/user_status/src/components/PredefinedStatusesList.vue?vue&type=style&index=0&id=31790e1a&lang=scss&scoped=true&");

            

var options = {};

options.insert = "head";
options.singleton = false;

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_PredefinedStatusesList_vue_vue_type_style_index_0_id_31790e1a_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_1__["default"], options);



/* harmony default export */ __webpack_exports__["default"] = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_PredefinedStatusesList_vue_vue_type_style_index_0_id_31790e1a_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_1__["default"].locals || {});

/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/user_status/src/components/SetStatusModal.vue?vue&type=style&index=0&id=1a4980a2&lang=scss&scoped=true&":
/*!***************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/user_status/src/components/SetStatusModal.vue?vue&type=style&index=0&id=1a4980a2&lang=scss&scoped=true& ***!
  \***************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! !../../../../node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js */ "./node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SetStatusModal_vue_vue_type_style_index_0_id_1a4980a2_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SetStatusModal.vue?vue&type=style&index=0&id=1a4980a2&lang=scss&scoped=true& */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/user_status/src/components/SetStatusModal.vue?vue&type=style&index=0&id=1a4980a2&lang=scss&scoped=true&");

            

var options = {};

options.insert = "head";
options.singleton = false;

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SetStatusModal_vue_vue_type_style_index_0_id_1a4980a2_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_1__["default"], options);



/* harmony default export */ __webpack_exports__["default"] = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SetStatusModal_vue_vue_type_style_index_0_id_1a4980a2_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_1__["default"].locals || {});

/***/ }),

/***/ "./apps/user_status/src/components/ClearAtSelect.vue":
/*!***********************************************************!*\
  !*** ./apps/user_status/src/components/ClearAtSelect.vue ***!
  \***********************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _ClearAtSelect_vue_vue_type_template_id_16308054_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./ClearAtSelect.vue?vue&type=template&id=16308054&scoped=true& */ "./apps/user_status/src/components/ClearAtSelect.vue?vue&type=template&id=16308054&scoped=true&");
/* harmony import */ var _ClearAtSelect_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./ClearAtSelect.vue?vue&type=script&lang=js& */ "./apps/user_status/src/components/ClearAtSelect.vue?vue&type=script&lang=js&");
/* harmony import */ var _ClearAtSelect_vue_vue_type_style_index_0_id_16308054_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./ClearAtSelect.vue?vue&type=style&index=0&id=16308054&lang=scss&scoped=true& */ "./apps/user_status/src/components/ClearAtSelect.vue?vue&type=style&index=0&id=16308054&lang=scss&scoped=true&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _ClearAtSelect_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _ClearAtSelect_vue_vue_type_template_id_16308054_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render,
  _ClearAtSelect_vue_vue_type_template_id_16308054_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "16308054",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/user_status/src/components/ClearAtSelect.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./apps/user_status/src/components/CustomMessageInput.vue":
/*!****************************************************************!*\
  !*** ./apps/user_status/src/components/CustomMessageInput.vue ***!
  \****************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _CustomMessageInput_vue_vue_type_template_id_f8ff5dac_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./CustomMessageInput.vue?vue&type=template&id=f8ff5dac&scoped=true& */ "./apps/user_status/src/components/CustomMessageInput.vue?vue&type=template&id=f8ff5dac&scoped=true&");
/* harmony import */ var _CustomMessageInput_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./CustomMessageInput.vue?vue&type=script&lang=js& */ "./apps/user_status/src/components/CustomMessageInput.vue?vue&type=script&lang=js&");
/* harmony import */ var _CustomMessageInput_vue_vue_type_style_index_0_id_f8ff5dac_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./CustomMessageInput.vue?vue&type=style&index=0&id=f8ff5dac&lang=scss&scoped=true& */ "./apps/user_status/src/components/CustomMessageInput.vue?vue&type=style&index=0&id=f8ff5dac&lang=scss&scoped=true&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _CustomMessageInput_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _CustomMessageInput_vue_vue_type_template_id_f8ff5dac_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render,
  _CustomMessageInput_vue_vue_type_template_id_f8ff5dac_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "f8ff5dac",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/user_status/src/components/CustomMessageInput.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./apps/user_status/src/components/OnlineStatusSelect.vue":
/*!****************************************************************!*\
  !*** ./apps/user_status/src/components/OnlineStatusSelect.vue ***!
  \****************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _OnlineStatusSelect_vue_vue_type_template_id_08b51a17_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./OnlineStatusSelect.vue?vue&type=template&id=08b51a17&scoped=true& */ "./apps/user_status/src/components/OnlineStatusSelect.vue?vue&type=template&id=08b51a17&scoped=true&");
/* harmony import */ var _OnlineStatusSelect_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./OnlineStatusSelect.vue?vue&type=script&lang=js& */ "./apps/user_status/src/components/OnlineStatusSelect.vue?vue&type=script&lang=js&");
/* harmony import */ var _OnlineStatusSelect_vue_vue_type_style_index_0_id_08b51a17_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./OnlineStatusSelect.vue?vue&type=style&index=0&id=08b51a17&lang=scss&scoped=true& */ "./apps/user_status/src/components/OnlineStatusSelect.vue?vue&type=style&index=0&id=08b51a17&lang=scss&scoped=true&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _OnlineStatusSelect_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _OnlineStatusSelect_vue_vue_type_template_id_08b51a17_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render,
  _OnlineStatusSelect_vue_vue_type_template_id_08b51a17_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "08b51a17",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/user_status/src/components/OnlineStatusSelect.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./apps/user_status/src/components/PredefinedStatus.vue":
/*!**************************************************************!*\
  !*** ./apps/user_status/src/components/PredefinedStatus.vue ***!
  \**************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _PredefinedStatus_vue_vue_type_template_id_2b4a822e_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./PredefinedStatus.vue?vue&type=template&id=2b4a822e&scoped=true& */ "./apps/user_status/src/components/PredefinedStatus.vue?vue&type=template&id=2b4a822e&scoped=true&");
/* harmony import */ var _PredefinedStatus_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./PredefinedStatus.vue?vue&type=script&lang=js& */ "./apps/user_status/src/components/PredefinedStatus.vue?vue&type=script&lang=js&");
/* harmony import */ var _PredefinedStatus_vue_vue_type_style_index_0_id_2b4a822e_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./PredefinedStatus.vue?vue&type=style&index=0&id=2b4a822e&lang=scss&scoped=true& */ "./apps/user_status/src/components/PredefinedStatus.vue?vue&type=style&index=0&id=2b4a822e&lang=scss&scoped=true&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _PredefinedStatus_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _PredefinedStatus_vue_vue_type_template_id_2b4a822e_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render,
  _PredefinedStatus_vue_vue_type_template_id_2b4a822e_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "2b4a822e",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/user_status/src/components/PredefinedStatus.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./apps/user_status/src/components/PredefinedStatusesList.vue":
/*!********************************************************************!*\
  !*** ./apps/user_status/src/components/PredefinedStatusesList.vue ***!
  \********************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _PredefinedStatusesList_vue_vue_type_template_id_31790e1a_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./PredefinedStatusesList.vue?vue&type=template&id=31790e1a&scoped=true& */ "./apps/user_status/src/components/PredefinedStatusesList.vue?vue&type=template&id=31790e1a&scoped=true&");
/* harmony import */ var _PredefinedStatusesList_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./PredefinedStatusesList.vue?vue&type=script&lang=js& */ "./apps/user_status/src/components/PredefinedStatusesList.vue?vue&type=script&lang=js&");
/* harmony import */ var _PredefinedStatusesList_vue_vue_type_style_index_0_id_31790e1a_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./PredefinedStatusesList.vue?vue&type=style&index=0&id=31790e1a&lang=scss&scoped=true& */ "./apps/user_status/src/components/PredefinedStatusesList.vue?vue&type=style&index=0&id=31790e1a&lang=scss&scoped=true&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _PredefinedStatusesList_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _PredefinedStatusesList_vue_vue_type_template_id_31790e1a_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render,
  _PredefinedStatusesList_vue_vue_type_template_id_31790e1a_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "31790e1a",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/user_status/src/components/PredefinedStatusesList.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./apps/user_status/src/components/SetStatusModal.vue":
/*!************************************************************!*\
  !*** ./apps/user_status/src/components/SetStatusModal.vue ***!
  \************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _SetStatusModal_vue_vue_type_template_id_1a4980a2_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./SetStatusModal.vue?vue&type=template&id=1a4980a2&scoped=true& */ "./apps/user_status/src/components/SetStatusModal.vue?vue&type=template&id=1a4980a2&scoped=true&");
/* harmony import */ var _SetStatusModal_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./SetStatusModal.vue?vue&type=script&lang=js& */ "./apps/user_status/src/components/SetStatusModal.vue?vue&type=script&lang=js&");
/* harmony import */ var _SetStatusModal_vue_vue_type_style_index_0_id_1a4980a2_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./SetStatusModal.vue?vue&type=style&index=0&id=1a4980a2&lang=scss&scoped=true& */ "./apps/user_status/src/components/SetStatusModal.vue?vue&type=style&index=0&id=1a4980a2&lang=scss&scoped=true&");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _SetStatusModal_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_1__["default"],
  _SetStatusModal_vue_vue_type_template_id_1a4980a2_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render,
  _SetStatusModal_vue_vue_type_template_id_1a4980a2_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "1a4980a2",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/user_status/src/components/SetStatusModal.vue"
/* harmony default export */ __webpack_exports__["default"] = (component.exports);

/***/ }),

/***/ "./apps/user_status/src/components/ClearAtSelect.vue?vue&type=script&lang=js&":
/*!************************************************************************************!*\
  !*** ./apps/user_status/src/components/ClearAtSelect.vue?vue&type=script&lang=js& ***!
  \************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_ClearAtSelect_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./ClearAtSelect.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/user_status/src/components/ClearAtSelect.vue?vue&type=script&lang=js&");
 /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_ClearAtSelect_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/user_status/src/components/CustomMessageInput.vue?vue&type=script&lang=js&":
/*!*****************************************************************************************!*\
  !*** ./apps/user_status/src/components/CustomMessageInput.vue?vue&type=script&lang=js& ***!
  \*****************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_CustomMessageInput_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./CustomMessageInput.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/user_status/src/components/CustomMessageInput.vue?vue&type=script&lang=js&");
 /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_CustomMessageInput_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/user_status/src/components/OnlineStatusSelect.vue?vue&type=script&lang=js&":
/*!*****************************************************************************************!*\
  !*** ./apps/user_status/src/components/OnlineStatusSelect.vue?vue&type=script&lang=js& ***!
  \*****************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_OnlineStatusSelect_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./OnlineStatusSelect.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/user_status/src/components/OnlineStatusSelect.vue?vue&type=script&lang=js&");
 /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_OnlineStatusSelect_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/user_status/src/components/PredefinedStatus.vue?vue&type=script&lang=js&":
/*!***************************************************************************************!*\
  !*** ./apps/user_status/src/components/PredefinedStatus.vue?vue&type=script&lang=js& ***!
  \***************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_PredefinedStatus_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./PredefinedStatus.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/user_status/src/components/PredefinedStatus.vue?vue&type=script&lang=js&");
 /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_PredefinedStatus_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/user_status/src/components/PredefinedStatusesList.vue?vue&type=script&lang=js&":
/*!*********************************************************************************************!*\
  !*** ./apps/user_status/src/components/PredefinedStatusesList.vue?vue&type=script&lang=js& ***!
  \*********************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_PredefinedStatusesList_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./PredefinedStatusesList.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/user_status/src/components/PredefinedStatusesList.vue?vue&type=script&lang=js&");
 /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_PredefinedStatusesList_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/user_status/src/components/SetStatusModal.vue?vue&type=script&lang=js&":
/*!*************************************************************************************!*\
  !*** ./apps/user_status/src/components/SetStatusModal.vue?vue&type=script&lang=js& ***!
  \*************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SetStatusModal_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SetStatusModal.vue?vue&type=script&lang=js& */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/user_status/src/components/SetStatusModal.vue?vue&type=script&lang=js&");
 /* harmony default export */ __webpack_exports__["default"] = (_node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SetStatusModal_vue_vue_type_script_lang_js___WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/user_status/src/components/ClearAtSelect.vue?vue&type=style&index=0&id=16308054&lang=scss&scoped=true&":
/*!*********************************************************************************************************************!*\
  !*** ./apps/user_status/src/components/ClearAtSelect.vue?vue&type=style&index=0&id=16308054&lang=scss&scoped=true& ***!
  \*********************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_ClearAtSelect_vue_vue_type_style_index_0_id_16308054_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader/dist/cjs.js!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./ClearAtSelect.vue?vue&type=style&index=0&id=16308054&lang=scss&scoped=true& */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/user_status/src/components/ClearAtSelect.vue?vue&type=style&index=0&id=16308054&lang=scss&scoped=true&");


/***/ }),

/***/ "./apps/user_status/src/components/CustomMessageInput.vue?vue&type=style&index=0&id=f8ff5dac&lang=scss&scoped=true&":
/*!**************************************************************************************************************************!*\
  !*** ./apps/user_status/src/components/CustomMessageInput.vue?vue&type=style&index=0&id=f8ff5dac&lang=scss&scoped=true& ***!
  \**************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_CustomMessageInput_vue_vue_type_style_index_0_id_f8ff5dac_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader/dist/cjs.js!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./CustomMessageInput.vue?vue&type=style&index=0&id=f8ff5dac&lang=scss&scoped=true& */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/user_status/src/components/CustomMessageInput.vue?vue&type=style&index=0&id=f8ff5dac&lang=scss&scoped=true&");


/***/ }),

/***/ "./apps/user_status/src/components/OnlineStatusSelect.vue?vue&type=style&index=0&id=08b51a17&lang=scss&scoped=true&":
/*!**************************************************************************************************************************!*\
  !*** ./apps/user_status/src/components/OnlineStatusSelect.vue?vue&type=style&index=0&id=08b51a17&lang=scss&scoped=true& ***!
  \**************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_OnlineStatusSelect_vue_vue_type_style_index_0_id_08b51a17_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader/dist/cjs.js!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./OnlineStatusSelect.vue?vue&type=style&index=0&id=08b51a17&lang=scss&scoped=true& */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/user_status/src/components/OnlineStatusSelect.vue?vue&type=style&index=0&id=08b51a17&lang=scss&scoped=true&");


/***/ }),

/***/ "./apps/user_status/src/components/PredefinedStatus.vue?vue&type=style&index=0&id=2b4a822e&lang=scss&scoped=true&":
/*!************************************************************************************************************************!*\
  !*** ./apps/user_status/src/components/PredefinedStatus.vue?vue&type=style&index=0&id=2b4a822e&lang=scss&scoped=true& ***!
  \************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_PredefinedStatus_vue_vue_type_style_index_0_id_2b4a822e_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader/dist/cjs.js!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./PredefinedStatus.vue?vue&type=style&index=0&id=2b4a822e&lang=scss&scoped=true& */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/user_status/src/components/PredefinedStatus.vue?vue&type=style&index=0&id=2b4a822e&lang=scss&scoped=true&");


/***/ }),

/***/ "./apps/user_status/src/components/PredefinedStatusesList.vue?vue&type=style&index=0&id=31790e1a&lang=scss&scoped=true&":
/*!******************************************************************************************************************************!*\
  !*** ./apps/user_status/src/components/PredefinedStatusesList.vue?vue&type=style&index=0&id=31790e1a&lang=scss&scoped=true& ***!
  \******************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_PredefinedStatusesList_vue_vue_type_style_index_0_id_31790e1a_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader/dist/cjs.js!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./PredefinedStatusesList.vue?vue&type=style&index=0&id=31790e1a&lang=scss&scoped=true& */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/user_status/src/components/PredefinedStatusesList.vue?vue&type=style&index=0&id=31790e1a&lang=scss&scoped=true&");


/***/ }),

/***/ "./apps/user_status/src/components/SetStatusModal.vue?vue&type=style&index=0&id=1a4980a2&lang=scss&scoped=true&":
/*!**********************************************************************************************************************!*\
  !*** ./apps/user_status/src/components/SetStatusModal.vue?vue&type=style&index=0&id=1a4980a2&lang=scss&scoped=true& ***!
  \**********************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_SetStatusModal_vue_vue_type_style_index_0_id_1a4980a2_lang_scss_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader/dist/cjs.js!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SetStatusModal.vue?vue&type=style&index=0&id=1a4980a2&lang=scss&scoped=true& */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/user_status/src/components/SetStatusModal.vue?vue&type=style&index=0&id=1a4980a2&lang=scss&scoped=true&");


/***/ }),

/***/ "./apps/user_status/src/components/ClearAtSelect.vue?vue&type=template&id=16308054&scoped=true&":
/*!******************************************************************************************************!*\
  !*** ./apps/user_status/src/components/ClearAtSelect.vue?vue&type=template&id=16308054&scoped=true& ***!
  \******************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* reexport safe */ _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_ClearAtSelect_vue_vue_type_template_id_16308054_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render; },
/* harmony export */   "staticRenderFns": function() { return /* reexport safe */ _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_ClearAtSelect_vue_vue_type_template_id_16308054_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns; }
/* harmony export */ });
/* harmony import */ var _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_ClearAtSelect_vue_vue_type_template_id_16308054_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./ClearAtSelect.vue?vue&type=template&id=16308054&scoped=true& */ "./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/user_status/src/components/ClearAtSelect.vue?vue&type=template&id=16308054&scoped=true&");


/***/ }),

/***/ "./apps/user_status/src/components/CustomMessageInput.vue?vue&type=template&id=f8ff5dac&scoped=true&":
/*!***********************************************************************************************************!*\
  !*** ./apps/user_status/src/components/CustomMessageInput.vue?vue&type=template&id=f8ff5dac&scoped=true& ***!
  \***********************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* reexport safe */ _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_CustomMessageInput_vue_vue_type_template_id_f8ff5dac_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render; },
/* harmony export */   "staticRenderFns": function() { return /* reexport safe */ _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_CustomMessageInput_vue_vue_type_template_id_f8ff5dac_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns; }
/* harmony export */ });
/* harmony import */ var _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_CustomMessageInput_vue_vue_type_template_id_f8ff5dac_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./CustomMessageInput.vue?vue&type=template&id=f8ff5dac&scoped=true& */ "./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/user_status/src/components/CustomMessageInput.vue?vue&type=template&id=f8ff5dac&scoped=true&");


/***/ }),

/***/ "./apps/user_status/src/components/OnlineStatusSelect.vue?vue&type=template&id=08b51a17&scoped=true&":
/*!***********************************************************************************************************!*\
  !*** ./apps/user_status/src/components/OnlineStatusSelect.vue?vue&type=template&id=08b51a17&scoped=true& ***!
  \***********************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* reexport safe */ _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_OnlineStatusSelect_vue_vue_type_template_id_08b51a17_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render; },
/* harmony export */   "staticRenderFns": function() { return /* reexport safe */ _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_OnlineStatusSelect_vue_vue_type_template_id_08b51a17_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns; }
/* harmony export */ });
/* harmony import */ var _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_OnlineStatusSelect_vue_vue_type_template_id_08b51a17_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./OnlineStatusSelect.vue?vue&type=template&id=08b51a17&scoped=true& */ "./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/user_status/src/components/OnlineStatusSelect.vue?vue&type=template&id=08b51a17&scoped=true&");


/***/ }),

/***/ "./apps/user_status/src/components/PredefinedStatus.vue?vue&type=template&id=2b4a822e&scoped=true&":
/*!*********************************************************************************************************!*\
  !*** ./apps/user_status/src/components/PredefinedStatus.vue?vue&type=template&id=2b4a822e&scoped=true& ***!
  \*********************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* reexport safe */ _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_PredefinedStatus_vue_vue_type_template_id_2b4a822e_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render; },
/* harmony export */   "staticRenderFns": function() { return /* reexport safe */ _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_PredefinedStatus_vue_vue_type_template_id_2b4a822e_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns; }
/* harmony export */ });
/* harmony import */ var _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_PredefinedStatus_vue_vue_type_template_id_2b4a822e_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./PredefinedStatus.vue?vue&type=template&id=2b4a822e&scoped=true& */ "./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/user_status/src/components/PredefinedStatus.vue?vue&type=template&id=2b4a822e&scoped=true&");


/***/ }),

/***/ "./apps/user_status/src/components/PredefinedStatusesList.vue?vue&type=template&id=31790e1a&scoped=true&":
/*!***************************************************************************************************************!*\
  !*** ./apps/user_status/src/components/PredefinedStatusesList.vue?vue&type=template&id=31790e1a&scoped=true& ***!
  \***************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* reexport safe */ _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_PredefinedStatusesList_vue_vue_type_template_id_31790e1a_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render; },
/* harmony export */   "staticRenderFns": function() { return /* reexport safe */ _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_PredefinedStatusesList_vue_vue_type_template_id_31790e1a_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns; }
/* harmony export */ });
/* harmony import */ var _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_PredefinedStatusesList_vue_vue_type_template_id_31790e1a_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./PredefinedStatusesList.vue?vue&type=template&id=31790e1a&scoped=true& */ "./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/user_status/src/components/PredefinedStatusesList.vue?vue&type=template&id=31790e1a&scoped=true&");


/***/ }),

/***/ "./apps/user_status/src/components/SetStatusModal.vue?vue&type=template&id=1a4980a2&scoped=true&":
/*!*******************************************************************************************************!*\
  !*** ./apps/user_status/src/components/SetStatusModal.vue?vue&type=template&id=1a4980a2&scoped=true& ***!
  \*******************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* reexport safe */ _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_SetStatusModal_vue_vue_type_template_id_1a4980a2_scoped_true___WEBPACK_IMPORTED_MODULE_0__.render; },
/* harmony export */   "staticRenderFns": function() { return /* reexport safe */ _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_SetStatusModal_vue_vue_type_template_id_1a4980a2_scoped_true___WEBPACK_IMPORTED_MODULE_0__.staticRenderFns; }
/* harmony export */ });
/* harmony import */ var _node_modules_vue_loader_lib_loaders_templateLoader_js_vue_loader_options_node_modules_vue_loader_lib_index_js_vue_loader_options_SetStatusModal_vue_vue_type_template_id_1a4980a2_scoped_true___WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./SetStatusModal.vue?vue&type=template&id=1a4980a2&scoped=true& */ "./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/user_status/src/components/SetStatusModal.vue?vue&type=template&id=1a4980a2&scoped=true&");


/***/ }),

/***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/user_status/src/components/ClearAtSelect.vue?vue&type=template&id=16308054&scoped=true&":
/*!*********************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/user_status/src/components/ClearAtSelect.vue?vue&type=template&id=16308054&scoped=true& ***!
  \*********************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

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
    { staticClass: "clear-at-select" },
    [
      _c("span", { staticClass: "clear-at-select__label" }, [
        _vm._v(
          "\n\t\t" +
            _vm._s(_vm.$t("user_status", "Clear status message after")) +
            "\n\t"
        ),
      ]),
      _vm._v(" "),
      _c("Multiselect", {
        attrs: {
          label: "label",
          value: _vm.option,
          options: _vm.options,
          "open-direction": "top",
        },
        on: { select: _vm.select },
      }),
    ],
    1
  )
}
var staticRenderFns = []
render._withStripped = true



/***/ }),

/***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/user_status/src/components/CustomMessageInput.vue?vue&type=template&id=f8ff5dac&scoped=true&":
/*!**************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/user_status/src/components/CustomMessageInput.vue?vue&type=template&id=f8ff5dac&scoped=true& ***!
  \**************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

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
    "form",
    {
      staticClass: "custom-input__form",
      on: {
        submit: function ($event) {
          $event.preventDefault()
        },
      },
    },
    [
      _c("input", {
        ref: "input",
        attrs: {
          maxlength: "80",
          disabled: _vm.disabled,
          placeholder: _vm.$t("user_status", "What is your status?"),
          type: "text",
        },
        domProps: { value: _vm.message },
        on: {
          change: _vm.change,
          keyup: [
            _vm.change,
            function ($event) {
              if (
                !$event.type.indexOf("key") &&
                _vm._k($event.keyCode, "enter", 13, $event.key, "Enter")
              ) {
                return null
              }
              return _vm.submit.apply(null, arguments)
            },
          ],
          paste: _vm.change,
        },
      }),
    ]
  )
}
var staticRenderFns = []
render._withStripped = true



/***/ }),

/***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/user_status/src/components/OnlineStatusSelect.vue?vue&type=template&id=08b51a17&scoped=true&":
/*!**************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/user_status/src/components/OnlineStatusSelect.vue?vue&type=template&id=08b51a17&scoped=true& ***!
  \**************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* binding */ render; },
/* harmony export */   "staticRenderFns": function() { return /* binding */ staticRenderFns; }
/* harmony export */ });
var render = function () {
  var _vm = this
  var _h = _vm.$createElement
  var _c = _vm._self._c || _h
  return _c("div", { staticClass: "user-status-online-select" }, [
    _c("input", {
      staticClass: "user-status-online-select__input",
      attrs: { id: _vm.id, type: "radio", name: "user-status-online" },
      domProps: { checked: _vm.checked },
      on: { change: _vm.onChange },
    }),
    _vm._v(" "),
    _c(
      "label",
      {
        staticClass: "user-status-online-select__label",
        class: _vm.icon,
        attrs: { for: _vm.id },
      },
      [
        _vm._v("\n\t\t" + _vm._s(_vm.label) + "\n\t\t"),
        _c("em", { staticClass: "user-status-online-select__subline" }, [
          _vm._v(_vm._s(_vm.subline)),
        ]),
      ]
    ),
  ])
}
var staticRenderFns = []
render._withStripped = true



/***/ }),

/***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/user_status/src/components/PredefinedStatus.vue?vue&type=template&id=2b4a822e&scoped=true&":
/*!************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/user_status/src/components/PredefinedStatus.vue?vue&type=template&id=2b4a822e&scoped=true& ***!
  \************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

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
      staticClass: "predefined-status",
      attrs: { tabindex: "0" },
      on: {
        keyup: [
          function ($event) {
            if (
              !$event.type.indexOf("key") &&
              _vm._k($event.keyCode, "enter", 13, $event.key, "Enter")
            ) {
              return null
            }
            return _vm.select.apply(null, arguments)
          },
          function ($event) {
            if (
              !$event.type.indexOf("key") &&
              _vm._k($event.keyCode, "space", 32, $event.key, [" ", "Spacebar"])
            ) {
              return null
            }
            return _vm.select.apply(null, arguments)
          },
        ],
        click: _vm.select,
      },
    },
    [
      _c("span", { staticClass: "predefined-status__icon" }, [
        _vm._v("\n\t\t" + _vm._s(_vm.icon) + "\n\t"),
      ]),
      _vm._v(" "),
      _c("span", { staticClass: "predefined-status__message" }, [
        _vm._v("\n\t\t" + _vm._s(_vm.message) + "\n\t"),
      ]),
      _vm._v(" "),
      _c("span", { staticClass: "predefined-status__clear-at" }, [
        _vm._v(
          "\n\t\t" + _vm._s(_vm._f("clearAtFilter")(_vm.clearAt)) + "\n\t"
        ),
      ]),
    ]
  )
}
var staticRenderFns = []
render._withStripped = true



/***/ }),

/***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/user_status/src/components/PredefinedStatusesList.vue?vue&type=template&id=31790e1a&scoped=true&":
/*!******************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/user_status/src/components/PredefinedStatusesList.vue?vue&type=template&id=31790e1a&scoped=true& ***!
  \******************************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "render": function() { return /* binding */ render; },
/* harmony export */   "staticRenderFns": function() { return /* binding */ staticRenderFns; }
/* harmony export */ });
var render = function () {
  var _vm = this
  var _h = _vm.$createElement
  var _c = _vm._self._c || _h
  return _vm.hasLoaded
    ? _c(
        "div",
        { staticClass: "predefined-statuses-list" },
        _vm._l(_vm.predefinedStatuses, function (status) {
          return _c("PredefinedStatus", {
            key: status.id,
            attrs: {
              "message-id": status.id,
              icon: status.icon,
              message: status.message,
              "clear-at": status.clearAt,
            },
            on: {
              select: function ($event) {
                return _vm.selectStatus(status)
              },
            },
          })
        }),
        1
      )
    : _c("div", { staticClass: "predefined-statuses-list" }, [
        _c("div", { staticClass: "icon icon-loading-small" }),
      ])
}
var staticRenderFns = []
render._withStripped = true



/***/ }),

/***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/user_status/src/components/SetStatusModal.vue?vue&type=template&id=1a4980a2&scoped=true&":
/*!**********************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??vue-loader-options!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/user_status/src/components/SetStatusModal.vue?vue&type=template&id=1a4980a2&scoped=true& ***!
  \**********************************************************************************************************************************************************************************************************************************************/
/***/ (function(__unused_webpack_module, __webpack_exports__, __webpack_require__) {

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
    "Modal",
    {
      attrs: { size: "normal", title: _vm.$t("user_status", "Set status") },
      on: { close: _vm.closeModal },
    },
    [
      _c(
        "div",
        { staticClass: "set-status-modal" },
        [
          _c("div", { staticClass: "set-status-modal__header" }, [
            _c("h3", [_vm._v(_vm._s(_vm.$t("user_status", "Online status")))]),
          ]),
          _vm._v(" "),
          _c(
            "div",
            { staticClass: "set-status-modal__online-status" },
            _vm._l(_vm.statuses, function (status) {
              return _c(
                "OnlineStatusSelect",
                _vm._b(
                  {
                    key: status.type,
                    attrs: { checked: status.type === _vm.statusType },
                    on: { select: _vm.changeStatus },
                  },
                  "OnlineStatusSelect",
                  status,
                  false
                )
              )
            }),
            1
          ),
          _vm._v(" "),
          _c("div", { staticClass: "set-status-modal__header" }, [
            _c("h3", [_vm._v(_vm._s(_vm.$t("user_status", "Status message")))]),
          ]),
          _vm._v(" "),
          _c(
            "div",
            { staticClass: "set-status-modal__custom-input" },
            [
              _c("EmojiPicker", { on: { select: _vm.setIcon } }, [
                _c("button", { staticClass: "custom-input__emoji-button" }, [
                  _vm._v(
                    "\n\t\t\t\t\t" + _vm._s(_vm.visibleIcon) + "\n\t\t\t\t"
                  ),
                ]),
              ]),
              _vm._v(" "),
              _c("CustomMessageInput", {
                ref: "customMessageInput",
                attrs: { message: _vm.message },
                on: { change: _vm.setMessage, submit: _vm.saveStatus },
              }),
            ],
            1
          ),
          _vm._v(" "),
          _c("PredefinedStatusesList", {
            on: { selectStatus: _vm.selectPredefinedMessage },
          }),
          _vm._v(" "),
          _c("ClearAtSelect", {
            attrs: { "clear-at": _vm.clearAt },
            on: { selectClearAt: _vm.setClearAt },
          }),
          _vm._v(" "),
          _c("div", { staticClass: "status-buttons" }, [
            _c(
              "button",
              {
                staticClass: "status-buttons__select",
                attrs: { disabled: _vm.isSavingStatus },
                on: { click: _vm.clearStatus },
              },
              [
                _vm._v(
                  "\n\t\t\t\t" +
                    _vm._s(_vm.$t("user_status", "Clear status message")) +
                    "\n\t\t\t"
                ),
              ]
            ),
            _vm._v(" "),
            _c(
              "button",
              {
                staticClass: "status-buttons__primary primary",
                attrs: { disabled: _vm.isSavingStatus },
                on: { click: _vm.saveStatus },
              },
              [
                _vm._v(
                  "\n\t\t\t\t" +
                    _vm._s(_vm.$t("user_status", "Set status message")) +
                    "\n\t\t\t"
                ),
              ]
            ),
          ]),
        ],
        1
      ),
    ]
  )
}
var staticRenderFns = []
render._withStripped = true



/***/ })

}]);
//# sourceMappingURL=user-status-modal-user-status-modal.js.map?v=c0bc9bb0757c8706cd9a