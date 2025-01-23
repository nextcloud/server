"use strict";
(self["webpackChunknextcloud"] = self["webpackChunknextcloud"] || []).push([["apps_files_sharing_src_components_NewFileRequestDialog_vue"],{

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/NewFileRequestDialog.vue?vue&type=script&lang=ts":
/*!**********************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/NewFileRequestDialog.vue?vue&type=script&lang=ts ***!
  \**********************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_19__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/event-bus */ "./node_modules/@nextcloud/event-bus/dist/index.mjs");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");
/* harmony import */ var _nextcloud_files__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/files */ "./node_modules/@nextcloud/files/dist/index.mjs");
/* harmony import */ var _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/sharing */ "./node_modules/@nextcloud/sharing/dist/index.mjs");
/* harmony import */ var _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/dialogs */ "./node_modules/@nextcloud/dialogs/dist/index.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _nextcloud_axios__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @nextcloud/axios */ "./node_modules/@nextcloud/axios/dist/index.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcButton_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcButton.js */ "./node_modules/@nextcloud/vue/dist/Components/NcButton.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcDialog_js__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcDialog.js */ "./node_modules/@nextcloud/vue/dist/Components/NcDialog.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcLoadingIcon_js__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcLoadingIcon.js */ "./node_modules/@nextcloud/vue/dist/Components/NcLoadingIcon.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcNoteCard_js__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcNoteCard.js */ "./node_modules/@nextcloud/vue/dist/Components/NcNoteCard.mjs");
/* harmony import */ var vue_material_design_icons_Check_vue__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! vue-material-design-icons/Check.vue */ "./node_modules/vue-material-design-icons/Check.vue");
/* harmony import */ var vue_material_design_icons_ArrowRight_vue__WEBPACK_IMPORTED_MODULE_12__ = __webpack_require__(/*! vue-material-design-icons/ArrowRight.vue */ "./node_modules/vue-material-design-icons/ArrowRight.vue");
/* harmony import */ var _services_ConfigService__WEBPACK_IMPORTED_MODULE_13__ = __webpack_require__(/*! ../services/ConfigService */ "./apps/files_sharing/src/services/ConfigService.ts");
/* harmony import */ var _NewFileRequestDialog_NewFileRequestDialogDatePassword_vue__WEBPACK_IMPORTED_MODULE_14__ = __webpack_require__(/*! ./NewFileRequestDialog/NewFileRequestDialogDatePassword.vue */ "./apps/files_sharing/src/components/NewFileRequestDialog/NewFileRequestDialogDatePassword.vue");
/* harmony import */ var _NewFileRequestDialog_NewFileRequestDialogFinish_vue__WEBPACK_IMPORTED_MODULE_15__ = __webpack_require__(/*! ./NewFileRequestDialog/NewFileRequestDialogFinish.vue */ "./apps/files_sharing/src/components/NewFileRequestDialog/NewFileRequestDialogFinish.vue");
/* harmony import */ var _NewFileRequestDialog_NewFileRequestDialogIntro_vue__WEBPACK_IMPORTED_MODULE_16__ = __webpack_require__(/*! ./NewFileRequestDialog/NewFileRequestDialogIntro.vue */ "./apps/files_sharing/src/components/NewFileRequestDialog/NewFileRequestDialogIntro.vue");
/* harmony import */ var _services_logger__WEBPACK_IMPORTED_MODULE_17__ = __webpack_require__(/*! ../services/logger */ "./apps/files_sharing/src/services/logger.ts");
/* harmony import */ var _models_Share_ts__WEBPACK_IMPORTED_MODULE_18__ = __webpack_require__(/*! ../models/Share.ts */ "./apps/files_sharing/src/models/Share.ts");




















var STEP;
(function (STEP) {
  STEP[STEP["FIRST"] = 0] = "FIRST";
  STEP[STEP["SECOND"] = 1] = "SECOND";
  STEP[STEP["LAST"] = 2] = "LAST";
})(STEP || (STEP = {}));
const sharingConfig = new _services_ConfigService__WEBPACK_IMPORTED_MODULE_13__["default"]();
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ((0,vue__WEBPACK_IMPORTED_MODULE_19__.defineComponent)({
  name: 'NewFileRequestDialog',
  components: {
    FileRequestDatePassword: _NewFileRequestDialog_NewFileRequestDialogDatePassword_vue__WEBPACK_IMPORTED_MODULE_14__["default"],
    FileRequestFinish: _NewFileRequestDialog_NewFileRequestDialogFinish_vue__WEBPACK_IMPORTED_MODULE_15__["default"],
    FileRequestIntro: _NewFileRequestDialog_NewFileRequestDialogIntro_vue__WEBPACK_IMPORTED_MODULE_16__["default"],
    IconCheck: vue_material_design_icons_Check_vue__WEBPACK_IMPORTED_MODULE_11__["default"],
    IconNext: vue_material_design_icons_ArrowRight_vue__WEBPACK_IMPORTED_MODULE_12__["default"],
    NcButton: _nextcloud_vue_dist_Components_NcButton_js__WEBPACK_IMPORTED_MODULE_7__["default"],
    NcDialog: _nextcloud_vue_dist_Components_NcDialog_js__WEBPACK_IMPORTED_MODULE_8__["default"],
    NcLoadingIcon: _nextcloud_vue_dist_Components_NcLoadingIcon_js__WEBPACK_IMPORTED_MODULE_9__["default"],
    NcNoteCard: _nextcloud_vue_dist_Components_NcNoteCard_js__WEBPACK_IMPORTED_MODULE_10__["default"]
  },
  props: {
    context: {
      type: Object,
      required: true
    },
    content: {
      type: Array,
      required: true
    }
  },
  setup() {
    return {
      STEP,
      n: _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_5__.n,
      t: _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_5__.t,
      isShareByMailEnabled: sharingConfig.isMailShareAllowed
    };
  },
  data() {
    return {
      currentStep: STEP.FIRST,
      loading: false,
      destination: this.context.path || '/',
      label: '',
      note: '',
      expirationDate: null,
      password: null,
      share: null,
      emails: []
    };
  },
  computed: {
    finishButtonLabel() {
      if (this.emails.length === 0) {
        return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_5__.t)('files_sharing', 'Close');
      }
      return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_5__.n)('files_sharing', 'Send email and close', 'Send {count} emails and close', this.emails.length, {
        count: this.emails.length
      });
    }
  },
  methods: {
    onPageNext() {
      const form = this.$refs.form;
      if (!form.checkValidity()) {
        form.reportValidity();
        return;
      }
      // custom destination validation
      // cannot share root
      if (this.destination === '/' || this.destination === '') {
        const destinationInput = form.querySelector('input[name="destination"]');
        destinationInput?.setCustomValidity((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_5__.t)('files_sharing', 'Please select a folder, you cannot share the root directory.'));
        form.reportValidity();
        return;
      }
      if (this.currentStep === STEP.FIRST) {
        this.currentStep = STEP.SECOND;
        return;
      }
      this.createShare();
    },
    onRemoveEmail(email) {
      const index = this.emails.indexOf(email);
      this.emails.splice(index, 1);
    },
    onCancel() {
      this.$emit('close');
    },
    async onFinish() {
      if (this.emails.length === 0 || this.isShareByMailEnabled === false) {
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_4__.showSuccess)((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_5__.t)('files_sharing', 'File request created'));
        this.$emit('close');
        return;
      }
      if (sharingConfig.isMailShareAllowed && this.emails.length > 0) {
        await this.setShareEmails();
        await this.sendEmails();
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_4__.showSuccess)((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_5__.n)('files_sharing', 'File request created and email sent', 'File request created and {count} emails sent', this.emails.length, {
          count: this.emails.length
        }));
      } else {
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_4__.showSuccess)((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_5__.t)('files_sharing', 'File request created'));
      }
      this.$emit('close');
    },
    async createShare() {
      this.loading = true;
      let expireDate = '';
      if (this.expirationDate) {
        const year = this.expirationDate.getFullYear();
        const month = (this.expirationDate.getMonth() + 1).toString().padStart(2, '0');
        const day = this.expirationDate.getDate().toString().padStart(2, '0');
        // Format must be YYYY-MM-DD
        expireDate = `${year}-${month}-${day}`;
      }
      const shareUrl = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateOcsUrl)('apps/files_sharing/api/v1/shares');
      try {
        const request = await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_6__["default"].post(shareUrl, {
          // Always create a file request, but without mail share
          // permissions, only a share link will be created.
          shareType: sharingConfig.isMailShareAllowed ? _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_3__.ShareType.Email : _nextcloud_sharing__WEBPACK_IMPORTED_MODULE_3__.ShareType.Link,
          permissions: _nextcloud_files__WEBPACK_IMPORTED_MODULE_2__.Permission.CREATE,
          label: this.label,
          path: this.destination,
          note: this.note,
          password: this.password || undefined,
          expireDate: expireDate || undefined,
          // Empty string
          shareWith: '',
          attributes: JSON.stringify([{
            value: true,
            key: 'enabled',
            scope: 'fileRequest'
          }])
        });
        // If not an ocs request
        if (!request?.data?.ocs) {
          throw request;
        }
        const share = new _models_Share_ts__WEBPACK_IMPORTED_MODULE_18__["default"](request.data.ocs.data);
        this.share = share;
        _services_logger__WEBPACK_IMPORTED_MODULE_17__["default"].info('New file request created', {
          share
        });
        (0,_nextcloud_event_bus__WEBPACK_IMPORTED_MODULE_0__.emit)('files_sharing:share:created', {
          share
        });
        // Move to the last page
        this.currentStep = STEP.LAST;
      } catch (error) {
        const errorMessage = error?.response?.data?.ocs?.meta?.message;
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_4__.showError)(errorMessage ? (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_5__.t)('files_sharing', 'Error creating the share: {errorMessage}', {
          errorMessage
        }) : (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_5__.t)('files_sharing', 'Error creating the share'));
        _services_logger__WEBPACK_IMPORTED_MODULE_17__["default"].error('Error while creating share', {
          error,
          errorMessage
        });
        throw error;
      } finally {
        this.loading = false;
      }
    },
    async setShareEmails() {
      this.loading = true;
      // This should never happen™
      if (!this.share || !this.share?.id) {
        throw new Error('Share ID is missing');
      }
      const shareUrl = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateOcsUrl)('apps/files_sharing/api/v1/shares/{id}', {
        id: this.share.id
      });
      try {
        // Convert link share to email share
        const request = await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_6__["default"].put(shareUrl, {
          attributes: JSON.stringify([{
            value: this.emails,
            key: 'emails',
            scope: 'shareWith'
          }, {
            value: true,
            key: 'enabled',
            scope: 'fileRequest'
          }])
        });
        // If not an ocs request
        if (!request?.data?.ocs) {
          throw request;
        }
      } catch (error) {
        this.onEmailSendError(error);
        throw error;
      } finally {
        this.loading = false;
      }
    },
    async sendEmails() {
      this.loading = true;
      // This should never happen™
      if (!this.share || !this.share?.id) {
        throw new Error('Share ID is missing');
      }
      const shareUrl = (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateOcsUrl)('apps/files_sharing/api/v1/shares/{id}/send-email', {
        id: this.share.id
      });
      try {
        // Convert link share to email share
        const request = await _nextcloud_axios__WEBPACK_IMPORTED_MODULE_6__["default"].post(shareUrl, {
          password: this.password || undefined
        });
        // If not an ocs request
        if (!request?.data?.ocs) {
          throw request;
        }
      } catch (error) {
        this.onEmailSendError(error);
        throw error;
      } finally {
        this.loading = false;
      }
    },
    onEmailSendError(error) {
      const errorMessage = error.response?.data?.ocs?.meta?.message;
      (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_4__.showError)(errorMessage ? (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_5__.t)('files_sharing', 'Error sending emails: {errorMessage}', {
        errorMessage
      }) : (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_5__.t)('files_sharing', 'Error sending emails'));
      _services_logger__WEBPACK_IMPORTED_MODULE_17__["default"].error('Error while sending emails', {
        error,
        errorMessage
      });
    }
  }
}));

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/NewFileRequestDialog/NewFileRequestDialogDatePassword.vue?vue&type=script&lang=ts":
/*!*******************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/NewFileRequestDialog/NewFileRequestDialogDatePassword.vue?vue&type=script&lang=ts ***!
  \*******************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcButton_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcButton.js */ "./node_modules/@nextcloud/vue/dist/Components/NcButton.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcCheckboxRadioSwitch_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.js */ "./node_modules/@nextcloud/vue/dist/Components/NcCheckboxRadioSwitch.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcDateTimePickerNative_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcDateTimePickerNative.js */ "./node_modules/@nextcloud/vue/dist/Components/NcDateTimePickerNative.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcNoteCard_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcNoteCard.js */ "./node_modules/@nextcloud/vue/dist/Components/NcNoteCard.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcPasswordField_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcPasswordField.js */ "./node_modules/@nextcloud/vue/dist/Components/NcPasswordField.mjs");
/* harmony import */ var vue_material_design_icons_Information_vue__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! vue-material-design-icons/Information.vue */ "./node_modules/vue-material-design-icons/Information.vue");
/* harmony import */ var vue_material_design_icons_AutoFix_vue__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! vue-material-design-icons/AutoFix.vue */ "./node_modules/vue-material-design-icons/AutoFix.vue");
/* harmony import */ var _services_ConfigService__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! ../../services/ConfigService */ "./apps/files_sharing/src/services/ConfigService.ts");
/* harmony import */ var _utils_GeneratePassword__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! ../../utils/GeneratePassword */ "./apps/files_sharing/src/utils/GeneratePassword.ts");











const sharingConfig = new _services_ConfigService__WEBPACK_IMPORTED_MODULE_8__["default"]();
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ((0,vue__WEBPACK_IMPORTED_MODULE_10__.defineComponent)({
  name: 'NewFileRequestDialogDatePassword',
  components: {
    IconInfo: vue_material_design_icons_Information_vue__WEBPACK_IMPORTED_MODULE_6__["default"],
    IconPasswordGen: vue_material_design_icons_AutoFix_vue__WEBPACK_IMPORTED_MODULE_7__["default"],
    NcButton: _nextcloud_vue_dist_Components_NcButton_js__WEBPACK_IMPORTED_MODULE_1__["default"],
    NcCheckboxRadioSwitch: _nextcloud_vue_dist_Components_NcCheckboxRadioSwitch_js__WEBPACK_IMPORTED_MODULE_2__["default"],
    NcDateTimePickerNative: _nextcloud_vue_dist_Components_NcDateTimePickerNative_js__WEBPACK_IMPORTED_MODULE_3__["default"],
    NcNoteCard: _nextcloud_vue_dist_Components_NcNoteCard_js__WEBPACK_IMPORTED_MODULE_4__["default"],
    NcPasswordField: _nextcloud_vue_dist_Components_NcPasswordField_js__WEBPACK_IMPORTED_MODULE_5__["default"]
  },
  props: {
    disabled: {
      type: Boolean,
      required: false,
      default: false
    },
    expirationDate: {
      type: Date,
      required: false,
      default: null
    },
    password: {
      type: String,
      required: false,
      default: null
    }
  },
  emits: ['update:expirationDate', 'update:password'],
  setup() {
    return {
      t: _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.t,
      // Default expiration date if defaultExpireDateEnabled is true
      defaultExpireDate: sharingConfig.defaultExpireDate,
      // Default expiration date is enabled for public links (can be disabled)
      defaultExpireDateEnabled: sharingConfig.isDefaultExpireDateEnabled,
      // Default expiration date is enforced for public links (can't be disabled)
      defaultExpireDateEnforced: sharingConfig.isDefaultExpireDateEnforced,
      // Default password protection is enabled for public links (can be disabled)
      enableLinkPasswordByDefault: sharingConfig.enableLinkPasswordByDefault,
      // Password protection is enforced for public links (can't be disabled)
      enforcePasswordForPublicLink: sharingConfig.enforcePasswordForPublicLink
    };
  },
  data() {
    return {
      maxDate: null,
      minDate: new Date(new Date().setDate(new Date().getDate() + 1))
    };
  },
  computed: {
    passwordAndExpirationSummary() {
      if (this.expirationDate && this.password) {
        return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.t)('files_sharing', 'The request will expire on {date} at midnight and will be password protected.', {
          date: this.expirationDate.toLocaleDateString()
        });
      }
      if (this.expirationDate) {
        return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.t)('files_sharing', 'The request will expire on {date} at midnight.', {
          date: this.expirationDate.toLocaleDateString()
        });
      }
      if (this.password) {
        return (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_0__.t)('files_sharing', 'The request will be password protected.');
      }
      return '';
    }
  },
  mounted() {
    // If defined, we set the default expiration date
    if (this.defaultExpireDate) {
      this.$emit('update:expirationDate', sharingConfig.defaultExpirationDate);
    }
    // If enforced, we cannot set a date before the default expiration days (see admin settings)
    if (this.defaultExpireDateEnforced) {
      this.maxDate = sharingConfig.defaultExpirationDate;
    }
    // If enabled by default, we generate a valid password
    if (this.enableLinkPasswordByDefault) {
      this.generatePassword();
    }
  },
  methods: {
    onToggleDeadline(checked) {
      this.$emit('update:expirationDate', checked ? this.maxDate || this.minDate : null);
    },
    async onTogglePassword(checked) {
      if (checked) {
        this.generatePassword();
        return;
      }
      this.$emit('update:password', null);
    },
    async onGeneratePassword() {
      await this.generatePassword();
      this.showPassword();
    },
    async generatePassword() {
      await (0,_utils_GeneratePassword__WEBPACK_IMPORTED_MODULE_9__["default"])().then(password => {
        this.$emit('update:password', password);
      });
    },
    showPassword() {
      // @ts-expect-error isPasswordHidden is private
      this.$refs.passwordField.isPasswordHidden = false;
    }
  }
}));

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/NewFileRequestDialog/NewFileRequestDialogFinish.vue?vue&type=script&lang=ts":
/*!*************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/NewFileRequestDialog/NewFileRequestDialogFinish.vue?vue&type=script&lang=ts ***!
  \*************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _models_Share_ts__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../models/Share.ts */ "./apps/files_sharing/src/models/Share.ts");
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_11__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _nextcloud_router__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/router */ "./node_modules/@nextcloud/router/dist/index.mjs");
/* harmony import */ var _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! @nextcloud/dialogs */ "./node_modules/@nextcloud/dialogs/dist/index.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcAvatar_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcAvatar.js */ "./node_modules/@nextcloud/vue/dist/Components/NcAvatar.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcInputField_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcInputField.js */ "./node_modules/@nextcloud/vue/dist/Components/NcInputField.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcNoteCard_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcNoteCard.js */ "./node_modules/@nextcloud/vue/dist/Components/NcNoteCard.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcTextField_js__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcTextField.js */ "./node_modules/@nextcloud/vue/dist/Components/NcTextField.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcChip_js__WEBPACK_IMPORTED_MODULE_8__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcChip.js */ "./node_modules/@nextcloud/vue/dist/Components/NcChip.mjs");
/* harmony import */ var vue_material_design_icons_Check_vue__WEBPACK_IMPORTED_MODULE_9__ = __webpack_require__(/*! vue-material-design-icons/Check.vue */ "./node_modules/vue-material-design-icons/Check.vue");
/* harmony import */ var vue_material_design_icons_ClipboardText_vue__WEBPACK_IMPORTED_MODULE_10__ = __webpack_require__(/*! vue-material-design-icons/ClipboardText.vue */ "./node_modules/vue-material-design-icons/ClipboardText.vue");












/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ((0,vue__WEBPACK_IMPORTED_MODULE_11__.defineComponent)({
  name: 'NewFileRequestDialogFinish',
  components: {
    IconCheck: vue_material_design_icons_Check_vue__WEBPACK_IMPORTED_MODULE_9__["default"],
    IconClipboard: vue_material_design_icons_ClipboardText_vue__WEBPACK_IMPORTED_MODULE_10__["default"],
    NcAvatar: _nextcloud_vue_dist_Components_NcAvatar_js__WEBPACK_IMPORTED_MODULE_4__["default"],
    NcInputField: _nextcloud_vue_dist_Components_NcInputField_js__WEBPACK_IMPORTED_MODULE_5__["default"],
    NcNoteCard: _nextcloud_vue_dist_Components_NcNoteCard_js__WEBPACK_IMPORTED_MODULE_6__["default"],
    NcTextField: _nextcloud_vue_dist_Components_NcTextField_js__WEBPACK_IMPORTED_MODULE_7__["default"],
    NcChip: _nextcloud_vue_dist_Components_NcChip_js__WEBPACK_IMPORTED_MODULE_8__["default"]
  },
  props: {
    share: {
      type: Object,
      required: true
    },
    emails: {
      type: Array,
      required: true
    },
    isShareByMailEnabled: {
      type: Boolean,
      required: true
    }
  },
  emits: ['add-email', 'remove-email'],
  setup() {
    return {
      n: _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.n,
      t: _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.t
    };
  },
  data() {
    return {
      isCopied: false,
      email: ''
    };
  },
  computed: {
    shareLink() {
      return (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.generateUrl)('/s/{token}', {
        token: this.share.token
      }, {
        baseURL: (0,_nextcloud_router__WEBPACK_IMPORTED_MODULE_1__.getBaseUrl)()
      });
    }
  },
  methods: {
    async copyShareLink(event) {
      if (this.isCopied) {
        this.isCopied = false;
        return;
      }
      if (!navigator.clipboard) {
        // Clipboard API not available
        window.prompt((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.t)('files_sharing', 'Automatically copying failed, please copy the share link manually'), this.shareLink);
        return;
      }
      await navigator.clipboard.writeText(this.shareLink);
      (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_2__.showSuccess)((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.t)('files_sharing', 'Link copied to clipboard'));
      this.isCopied = true;
      event.target?.select?.();
      setTimeout(() => {
        this.isCopied = false;
      }, 3000);
    },
    addNewEmail(e) {
      if (this.email.trim() === '') {
        return;
      }
      if (e.target instanceof HTMLInputElement) {
        // Reset the custom validity
        e.target.setCustomValidity('');
        // Check if the field is valid
        if (e.target.checkValidity() === false) {
          e.target.reportValidity();
          return;
        }
        // The email is already in the list
        if (this.emails.includes(this.email.trim())) {
          e.target.setCustomValidity((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.t)('files_sharing', 'Email already added'));
          e.target.reportValidity();
          return;
        }
        // Check if the email is valid
        if (!this.isValidEmail(this.email.trim())) {
          e.target.setCustomValidity((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.t)('files_sharing', 'Invalid email address'));
          e.target.reportValidity();
          return;
        }
        this.$emit('add-email', this.email.trim());
        this.email = '';
      }
    },
    // Handle dumping a list of emails
    onPasteEmails(e) {
      const clipboardData = e.clipboardData;
      if (!clipboardData) {
        return;
      }
      const pastedText = clipboardData.getData('text');
      const emails = pastedText.split(/[\s,;]+/).filter(Boolean).map(email => email.trim());
      const duplicateEmails = emails.filter(email => this.emails.includes(email));
      const validEmails = emails.filter(email => this.isValidEmail(email) && !duplicateEmails.includes(email));
      const invalidEmails = emails.filter(email => !this.isValidEmail(email));
      validEmails.forEach(email => this.$emit('add-email', email));
      // Warn about invalid emails
      if (invalidEmails.length > 0) {
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_2__.showError)((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.n)('files_sharing', 'The following email address is not valid: {emails}', 'The following email addresses are not valid: {emails}', invalidEmails.length, {
          emails: invalidEmails.join(', ')
        }));
      }
      // Warn about duplicate emails
      if (duplicateEmails.length > 0) {
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_2__.showError)((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.n)('files_sharing', '1 email address already added', '{count} email addresses already added', duplicateEmails.length, {
          count: duplicateEmails.length
        }));
      }
      if (validEmails.length > 0) {
        (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_2__.showSuccess)((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_3__.n)('files_sharing', '1 email address added', '{count} email addresses added', validEmails.length, {
          count: validEmails.length
        }));
      }
      this.email = '';
    },
    // No need to have a fancy regex, just check for an @
    isValidEmail(email) {
      return email.includes('@');
    }
  }
}));

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/NewFileRequestDialog/NewFileRequestDialogIntro.vue?vue&type=script&lang=ts":
/*!************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/NewFileRequestDialog/NewFileRequestDialogIntro.vue?vue&type=script&lang=ts ***!
  \************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_7__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! @nextcloud/dialogs */ "./node_modules/@nextcloud/dialogs/dist/index.mjs");
/* harmony import */ var _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! @nextcloud/l10n */ "./node_modules/@nextcloud/l10n/dist/index.mjs");
/* harmony import */ var vue_material_design_icons_Folder_vue__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! vue-material-design-icons/Folder.vue */ "./node_modules/vue-material-design-icons/Folder.vue");
/* harmony import */ var vue_material_design_icons_Information_vue__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! vue-material-design-icons/Information.vue */ "./node_modules/vue-material-design-icons/Information.vue");
/* harmony import */ var vue_material_design_icons_Lock_vue__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! vue-material-design-icons/Lock.vue */ "./node_modules/vue-material-design-icons/Lock.vue");
/* harmony import */ var _nextcloud_vue_dist_Components_NcTextArea_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcTextArea.js */ "./node_modules/@nextcloud/vue/dist/Components/NcTextArea.mjs");
/* harmony import */ var _nextcloud_vue_dist_Components_NcTextField_js__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! @nextcloud/vue/dist/Components/NcTextField.js */ "./node_modules/@nextcloud/vue/dist/Components/NcTextField.mjs");








/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ((0,vue__WEBPACK_IMPORTED_MODULE_7__.defineComponent)({
  name: 'NewFileRequestDialogIntro',
  components: {
    IconFolder: vue_material_design_icons_Folder_vue__WEBPACK_IMPORTED_MODULE_2__["default"],
    IconInfo: vue_material_design_icons_Information_vue__WEBPACK_IMPORTED_MODULE_3__["default"],
    IconLock: vue_material_design_icons_Lock_vue__WEBPACK_IMPORTED_MODULE_4__["default"],
    NcTextArea: _nextcloud_vue_dist_Components_NcTextArea_js__WEBPACK_IMPORTED_MODULE_5__["default"],
    NcTextField: _nextcloud_vue_dist_Components_NcTextField_js__WEBPACK_IMPORTED_MODULE_6__["default"]
  },
  props: {
    disabled: {
      type: Boolean,
      required: false,
      default: false
    },
    context: {
      type: Object,
      required: true
    },
    label: {
      type: String,
      required: true
    },
    destination: {
      type: String,
      required: true
    },
    note: {
      type: String,
      required: true
    }
  },
  emits: ['update:destination', 'update:label', 'update:note'],
  setup() {
    return {
      t: _nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.t
    };
  },
  methods: {
    onPickDestination() {
      const filepicker = (0,_nextcloud_dialogs__WEBPACK_IMPORTED_MODULE_0__.getFilePickerBuilder)((0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.t)('files_sharing', 'Select a destination')).addMimeTypeFilter('httpd/unix-directory').allowDirectories(true).addButton({
        label: (0,_nextcloud_l10n__WEBPACK_IMPORTED_MODULE_1__.t)('files_sharing', 'Select'),
        callback: this.onPickedDestination
      }).setFilter(node => node.path !== '/').startAt(this.destination).build();
      try {
        filepicker.pick();
      } catch (e) {
        // ignore cancel
      }
    },
    onPickedDestination(nodes) {
      const node = nodes[0];
      if (node) {
        this.$emit('update:destination', node.path);
      }
    }
  }
}));

/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/NewFileRequestDialog.vue?vue&type=template&id=27651adf":
/*!***********************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/NewFileRequestDialog.vue?vue&type=template&id=27651adf ***!
  \***********************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c,
    _setup = _vm._self._setupProxy;
  return _c("NcDialog", {
    staticClass: "file-request-dialog",
    attrs: {
      "can-close": "",
      "data-cy-file-request-dialog": "",
      "close-on-click-outside": false,
      name: _vm.currentStep !== _vm.STEP.LAST ? _vm.t("files_sharing", "Create a file request") : _vm.t("files_sharing", "File request created"),
      size: "normal"
    },
    on: {
      closing: _vm.onCancel
    },
    scopedSlots: _vm._u([{
      key: "actions",
      fn: function () {
        return [_c("NcButton", {
          directives: [{
            name: "show",
            rawName: "v-show",
            value: _vm.currentStep === _vm.STEP.SECOND,
            expression: "currentStep === STEP.SECOND"
          }],
          attrs: {
            "aria-label": _vm.t("files_sharing", "Previous step"),
            disabled: _vm.loading,
            "data-cy-file-request-dialog-controls": "back",
            type: "tertiary"
          },
          on: {
            click: function ($event) {
              _vm.currentStep = _vm.STEP.FIRST;
            }
          }
        }, [_vm._v("\n\t\t\t" + _vm._s(_vm.t("files_sharing", "Previous step")) + "\n\t\t")]), _vm._v(" "), _c("span", {
          staticClass: "dialog__actions-separator"
        }), _vm._v(" "), _vm.currentStep !== _vm.STEP.LAST ? _c("NcButton", {
          attrs: {
            "aria-label": _vm.t("files_sharing", "Cancel"),
            disabled: _vm.loading,
            title: _vm.t("files_sharing", "Cancel the file request creation"),
            "data-cy-file-request-dialog-controls": "cancel",
            type: "tertiary"
          },
          on: {
            click: _vm.onCancel
          }
        }, [_vm._v("\n\t\t\t" + _vm._s(_vm.t("files_sharing", "Cancel")) + "\n\t\t")]) : _vm.emails.length !== 0 ? _c("NcButton", {
          attrs: {
            "aria-label": _vm.t("files_sharing", "Close without sending emails"),
            disabled: _vm.loading,
            title: _vm.t("files_sharing", "Close without sending emails"),
            "data-cy-file-request-dialog-controls": "cancel",
            type: "tertiary"
          },
          on: {
            click: _vm.onCancel
          }
        }, [_vm._v("\n\t\t\t" + _vm._s(_vm.t("files_sharing", "Close")) + "\n\t\t")]) : _vm._e(), _vm._v(" "), _vm.currentStep !== _vm.STEP.LAST ? _c("NcButton", {
          attrs: {
            "aria-label": _vm.t("files_sharing", "Continue"),
            disabled: _vm.loading,
            "data-cy-file-request-dialog-controls": "next"
          },
          on: {
            click: _vm.onPageNext
          },
          scopedSlots: _vm._u([{
            key: "icon",
            fn: function () {
              return [_vm.loading ? _c("NcLoadingIcon") : _c("IconNext", {
                attrs: {
                  size: 20
                }
              })];
            },
            proxy: true
          }], null, false, 3563923451)
        }, [_vm._v("\n\t\t\t" + _vm._s(_vm.t("files_sharing", "Continue")) + "\n\t\t")]) : _c("NcButton", {
          attrs: {
            "aria-label": _vm.finishButtonLabel,
            disabled: _vm.loading,
            "data-cy-file-request-dialog-controls": "finish",
            type: "primary"
          },
          on: {
            click: _vm.onFinish
          },
          scopedSlots: _vm._u([{
            key: "icon",
            fn: function () {
              return [_vm.loading ? _c("NcLoadingIcon") : _c("IconCheck", {
                attrs: {
                  size: 20
                }
              })];
            },
            proxy: true
          }])
        }, [_vm._v("\n\t\t\t" + _vm._s(_vm.finishButtonLabel) + "\n\t\t")])];
      },
      proxy: true
    }])
  }, [_c("NcNoteCard", {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: _vm.currentStep === _vm.STEP.FIRST,
      expression: "currentStep === STEP.FIRST"
    }],
    staticClass: "file-request-dialog__header",
    attrs: {
      type: "info"
    }
  }, [_c("p", {
    staticClass: "file-request-dialog__description",
    attrs: {
      id: "file-request-dialog-description"
    }
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.t("files_sharing", "Collect files from others even if they do not have an account.")) + "\n\t\t\t" + _vm._s(_vm.t("files_sharing", "To ensure you can receive files, verify you have enough storage available.")) + "\n\t\t")])]), _vm._v(" "), _c("form", {
    ref: "form",
    staticClass: "file-request-dialog__form",
    attrs: {
      "aria-describedby": "file-request-dialog-description",
      "aria-label": _vm.t("files_sharing", "File request"),
      "aria-live": "polite",
      "data-cy-file-request-dialog-form": ""
    },
    on: {
      submit: function ($event) {
        $event.preventDefault();
        $event.stopPropagation();
      }
    }
  }, [_c("FileRequestIntro", {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: _vm.currentStep === _vm.STEP.FIRST,
      expression: "currentStep === STEP.FIRST"
    }],
    attrs: {
      context: _vm.context,
      destination: _vm.destination,
      disabled: _vm.loading,
      label: _vm.label,
      note: _vm.note
    },
    on: {
      "update:destination": function ($event) {
        _vm.destination = $event;
      },
      "update:label": function ($event) {
        _vm.label = $event;
      },
      "update:note": function ($event) {
        _vm.note = $event;
      }
    }
  }), _vm._v(" "), _c("FileRequestDatePassword", {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: _vm.currentStep === _vm.STEP.SECOND,
      expression: "currentStep === STEP.SECOND"
    }],
    attrs: {
      disabled: _vm.loading,
      "expiration-date": _vm.expirationDate,
      password: _vm.password
    },
    on: {
      "update:expirationDate": function ($event) {
        _vm.expirationDate = $event;
      },
      "update:expiration-date": function ($event) {
        _vm.expirationDate = $event;
      },
      "update:password": function ($event) {
        _vm.password = $event;
      }
    }
  }), _vm._v(" "), _vm.share ? _c("FileRequestFinish", {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: _vm.currentStep === _vm.STEP.LAST,
      expression: "currentStep === STEP.LAST"
    }],
    attrs: {
      emails: _vm.emails,
      "is-share-by-mail-enabled": _vm.isShareByMailEnabled,
      share: _vm.share
    },
    on: {
      "add-email": email => _vm.emails.push(email),
      "remove-email": _vm.onRemoveEmail
    }
  }) : _vm._e()], 1)], 1);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/NewFileRequestDialog/NewFileRequestDialogDatePassword.vue?vue&type=template&id=75196638&scoped=true":
/*!********************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/NewFileRequestDialog/NewFileRequestDialogDatePassword.vue?vue&type=template&id=75196638&scoped=true ***!
  \********************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c,
    _setup = _vm._self._setupProxy;
  return _c("div", [_vm.passwordAndExpirationSummary ? _c("NcNoteCard", {
    attrs: {
      type: "success"
    }
  }, [_vm._v("\n\t\t" + _vm._s(_vm.passwordAndExpirationSummary) + "\n\t")]) : _vm._e(), _vm._v(" "), _c("fieldset", {
    staticClass: "file-request-dialog__expiration",
    attrs: {
      "data-cy-file-request-dialog-fieldset": "expiration"
    }
  }, [_c("legend", [_vm._v(_vm._s(_vm.t("files_sharing", "When should the request expire?")))]), _vm._v(" "), _c("NcCheckboxRadioSwitch", {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: !_vm.defaultExpireDateEnforced,
      expression: "!defaultExpireDateEnforced"
    }],
    attrs: {
      checked: _vm.defaultExpireDateEnforced || _vm.expirationDate !== null,
      disabled: _vm.disabled || _vm.defaultExpireDateEnforced
    },
    on: {
      "update:checked": _vm.onToggleDeadline
    }
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.t("files_sharing", "Set a submission expiration date")) + "\n\t\t")]), _vm._v(" "), _vm.expirationDate !== null ? _c("NcDateTimePickerNative", {
    attrs: {
      id: "file-request-dialog-expirationDate",
      disabled: _vm.disabled,
      "hide-label": true,
      label: _vm.t("files_sharing", "Expiration date"),
      max: _vm.maxDate,
      min: _vm.minDate,
      placeholder: _vm.t("files_sharing", "Select a date"),
      required: _vm.defaultExpireDateEnforced,
      value: _vm.expirationDate,
      name: "expirationDate",
      type: "date"
    },
    on: {
      input: function ($event) {
        return _vm.$emit("update:expirationDate", $event);
      }
    }
  }) : _vm._e(), _vm._v(" "), _vm.defaultExpireDateEnforced ? _c("p", {
    staticClass: "file-request-dialog__info"
  }, [_c("IconInfo", {
    staticClass: "file-request-dialog__info-icon",
    attrs: {
      size: 18
    }
  }), _vm._v("\n\t\t\t" + _vm._s(_vm.t("files_sharing", "Your administrator has enforced a {count} days expiration policy.", {
    count: _vm.defaultExpireDate
  })) + "\n\t\t")], 1) : _vm._e()], 1), _vm._v(" "), _c("fieldset", {
    staticClass: "file-request-dialog__password",
    attrs: {
      "data-cy-file-request-dialog-fieldset": "password"
    }
  }, [_c("legend", [_vm._v(_vm._s(_vm.t("files_sharing", "What password should be used for the request?")))]), _vm._v(" "), _c("NcCheckboxRadioSwitch", {
    directives: [{
      name: "show",
      rawName: "v-show",
      value: !_vm.enforcePasswordForPublicLink,
      expression: "!enforcePasswordForPublicLink"
    }],
    attrs: {
      checked: _vm.enforcePasswordForPublicLink || _vm.password !== null,
      disabled: _vm.disabled || _vm.enforcePasswordForPublicLink
    },
    on: {
      "update:checked": _vm.onTogglePassword
    }
  }, [_vm._v("\n\t\t\t" + _vm._s(_vm.t("files_sharing", "Set a password")) + "\n\t\t")]), _vm._v(" "), _vm.password !== null ? _c("div", {
    staticClass: "file-request-dialog__password-field"
  }, [_c("NcPasswordField", {
    ref: "passwordField",
    attrs: {
      "check-password-strength": true,
      disabled: _vm.disabled,
      label: _vm.t("files_sharing", "Password"),
      placeholder: _vm.t("files_sharing", "Enter a valid password"),
      required: false,
      value: _vm.password,
      name: "password"
    },
    on: {
      "update:value": function ($event) {
        return _vm.$emit("update:password", $event);
      }
    }
  }), _vm._v(" "), _c("NcButton", {
    attrs: {
      "aria-label": _vm.t("files_sharing", "Generate a new password"),
      title: _vm.t("files_sharing", "Generate a new password"),
      type: "tertiary-no-background"
    },
    on: {
      click: _vm.onGeneratePassword
    },
    scopedSlots: _vm._u([{
      key: "icon",
      fn: function () {
        return [_c("IconPasswordGen", {
          attrs: {
            size: 20
          }
        })];
      },
      proxy: true
    }], null, false, 1334968784)
  })], 1) : _vm._e(), _vm._v(" "), _vm.enforcePasswordForPublicLink ? _c("p", {
    staticClass: "file-request-dialog__info"
  }, [_c("IconInfo", {
    staticClass: "file-request-dialog__info-icon",
    attrs: {
      size: 18
    }
  }), _vm._v("\n\t\t\t" + _vm._s(_vm.t("files_sharing", "Your administrator has enforced a password protection.")) + "\n\t\t")], 1) : _vm._e()], 1)], 1);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/NewFileRequestDialog/NewFileRequestDialogFinish.vue?vue&type=template&id=ead70b64&scoped=true":
/*!**************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/NewFileRequestDialog/NewFileRequestDialogFinish.vue?vue&type=template&id=ead70b64&scoped=true ***!
  \**************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c,
    _setup = _vm._self._setupProxy;
  return _c("div", [_c("NcNoteCard", {
    attrs: {
      type: "success"
    }
  }, [_vm._v("\n\t\t" + _vm._s(_vm.t("files_sharing", "You can now share the link below to allow people to upload files to your directory.")) + "\n\t")]), _vm._v(" "), _c("NcInputField", {
    ref: "clipboard",
    attrs: {
      value: _vm.shareLink,
      label: _vm.t("files_sharing", "Share link"),
      readonly: true,
      "show-trailing-button": true,
      "trailing-button-label": _vm.t("files_sharing", "Copy to clipboard"),
      "data-cy-file-request-dialog-fieldset": "link"
    },
    on: {
      click: _vm.copyShareLink,
      "trailing-button-click": _vm.copyShareLink
    },
    scopedSlots: _vm._u([{
      key: "trailing-button-icon",
      fn: function () {
        return [_vm.isCopied ? _c("IconCheck", {
          attrs: {
            size: 20
          }
        }) : _c("IconClipboard", {
          attrs: {
            size: 20
          }
        })];
      },
      proxy: true
    }])
  }), _vm._v(" "), _vm.isShareByMailEnabled ? [_c("NcTextField", {
    attrs: {
      value: _vm.email,
      label: _vm.t("files_sharing", "Send link via email"),
      placeholder: _vm.t("files_sharing", "Enter an email address or paste a list"),
      "data-cy-file-request-dialog-fieldset": "email",
      type: "email"
    },
    on: {
      "update:value": function ($event) {
        _vm.email = $event;
      },
      keypress: function ($event) {
        if (!$event.type.indexOf("key") && _vm._k($event.keyCode, "enter", 13, $event.key, "Enter")) return null;
        $event.stopPropagation();
        return _vm.addNewEmail.apply(null, arguments);
      },
      paste: function ($event) {
        $event.stopPropagation();
        $event.preventDefault();
        return _vm.onPasteEmails.apply(null, arguments);
      }
    },
    nativeOn: {
      focusout: function ($event) {
        return _vm.addNewEmail.apply(null, arguments);
      }
    }
  }), _vm._v(" "), _vm.emails.length > 0 ? _c("div", {
    staticClass: "file-request-dialog__emails"
  }, _vm._l(_vm.emails, function (mail) {
    return _c("NcChip", {
      key: mail,
      attrs: {
        "aria-label-close": _vm.t("files_sharing", "Remove email"),
        text: mail
      },
      on: {
        close: function ($event) {
          return _vm.$emit("remove-email", mail);
        }
      },
      scopedSlots: _vm._u([{
        key: "icon",
        fn: function () {
          return [_c("NcAvatar", {
            attrs: {
              "disable-menu": true,
              "disable-tooltip": true,
              "display-name": mail,
              "is-no-user": true,
              "show-user-status": false,
              size: 24
            }
          })];
        },
        proxy: true
      }], null, true)
    });
  }), 1) : _vm._e()] : _vm._e()], 2);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/NewFileRequestDialog/NewFileRequestDialogIntro.vue?vue&type=template&id=17dab3fe&scoped=true":
/*!*************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/NewFileRequestDialog/NewFileRequestDialogIntro.vue?vue&type=template&id=17dab3fe&scoped=true ***!
  \*************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c,
    _setup = _vm._self._setupProxy;
  return _c("div", [_c("fieldset", {
    staticClass: "file-request-dialog__label",
    attrs: {
      "data-cy-file-request-dialog-fieldset": "label"
    }
  }, [_c("legend", [_vm._v("\n\t\t\t" + _vm._s(_vm.t("files_sharing", "What are you requesting?")) + "\n\t\t")]), _vm._v(" "), _c("NcTextField", {
    attrs: {
      value: _vm.label,
      disabled: _vm.disabled,
      label: _vm.t("files_sharing", "Request subject"),
      placeholder: _vm.t("files_sharing", "Birthday party photos, History assignment…"),
      required: false,
      name: "label"
    },
    on: {
      "update:value": function ($event) {
        return _vm.$emit("update:label", $event);
      }
    }
  })], 1), _vm._v(" "), _c("fieldset", {
    staticClass: "file-request-dialog__destination",
    attrs: {
      "data-cy-file-request-dialog-fieldset": "destination"
    }
  }, [_c("legend", [_vm._v("\n\t\t\t" + _vm._s(_vm.t("files_sharing", "Where should these files go?")) + "\n\t\t")]), _vm._v(" "), _c("NcTextField", {
    attrs: {
      value: _vm.destination,
      disabled: _vm.disabled,
      label: _vm.t("files_sharing", "Upload destination"),
      minlength: 2 /* cannot share root */,
      placeholder: _vm.t("files_sharing", "Select a destination"),
      readonly: false /* cannot validate a readonly input */,
      required: true /* cannot be empty */,
      "show-trailing-button": _vm.destination !== _vm.context.path,
      "trailing-button-icon": "undo",
      "trailing-button-label": _vm.t("files_sharing", "Revert to default"),
      name: "destination"
    },
    on: {
      click: _vm.onPickDestination,
      keypress: function ($event) {
        $event.preventDefault();
        $event.stopPropagation(); /* prevent typing in the input, we use the picker */
      },
      paste: function ($event) {
        $event.preventDefault();
        $event.stopPropagation(); /* prevent pasting in the input, we use the picker */
      },
      "trailing-button-click": function ($event) {
        return _vm.$emit("update:destination", "");
      }
    }
  }, [_c("IconFolder", {
    attrs: {
      size: 18
    }
  })], 1), _vm._v(" "), _c("p", {
    staticClass: "file-request-dialog__info"
  }, [_c("IconLock", {
    staticClass: "file-request-dialog__info-icon",
    attrs: {
      size: 18
    }
  }), _vm._v("\n\t\t\t" + _vm._s(_vm.t("files_sharing", "The uploaded files are visible only to you unless you choose to share them.")) + "\n\t\t")], 1)], 1), _vm._v(" "), _c("fieldset", {
    staticClass: "file-request-dialog__note",
    attrs: {
      "data-cy-file-request-dialog-fieldset": "note"
    }
  }, [_c("legend", [_vm._v("\n\t\t\t" + _vm._s(_vm.t("files_sharing", "Add a note")) + "\n\t\t")]), _vm._v(" "), _c("NcTextArea", {
    attrs: {
      value: _vm.note,
      disabled: _vm.disabled,
      label: _vm.t("files_sharing", "Note for recipient"),
      placeholder: _vm.t("files_sharing", "Add a note to help people understand what you are requesting."),
      required: false,
      name: "note"
    },
    on: {
      "update:value": function ($event) {
        return _vm.$emit("update:note", $event);
      }
    }
  }), _vm._v(" "), _c("p", {
    staticClass: "file-request-dialog__info"
  }, [_c("IconInfo", {
    staticClass: "file-request-dialog__info-icon",
    attrs: {
      size: 18
    }
  }), _vm._v("\n\t\t\t" + _vm._s(_vm.t("files_sharing", "You can add links, date or any other information that will help the recipient understand what you are requesting.")) + "\n\t\t")], 1)], 1)]);
};
var staticRenderFns = [];
render._withStripped = true;


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/@nextcloud/vue/dist/assets/NcChip-CEKw1zaK.css":
/*!***********************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/@nextcloud/vue/dist/assets/NcChip-CEKw1zaK.css ***!
  \***********************************************************************************************************/
/***/ ((module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../../css-loader/dist/runtime/noSourceMaps.js */ "./node_modules/css-loader/dist/runtime/noSourceMaps.js");
/* harmony import */ var _css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../../css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__);
// Imports


var ___CSS_LOADER_EXPORT___ = _css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default()((_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default()));
// Module
___CSS_LOADER_EXPORT___.push([module.id, `/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
/*
* Ensure proper alignment of the vue material icons
*/
.material-design-icon[data-v-470fc02d] {
  display: flex;
  align-self: center;
  justify-self: center;
  align-items: center;
  justify-content: center;
}
.nc-chip[data-v-470fc02d] {
  --chip-size: 24px;
  --chip-radius: calc(var(--chip-size) / 2);
  height: var(--chip-size);
  max-width: fit-content;
  display: flex;
  flex-direction: row;
  align-items: center;
  border-radius: var(--chip-radius);
  background-color: var(--color-background-hover);
}
.nc-chip--primary[data-v-470fc02d] {
  background-color: var(--color-primary-element);
  color: var(--color-primary-text);
}
.nc-chip--secondary[data-v-470fc02d] {
  background-color: var(--color-primary-element-light);
  color: var(--color-primary-element-light-text);
}
.nc-chip--no-actions .nc-chip__text[data-v-470fc02d] {
  padding-inline-end: calc(1.5 * var(--default-grid-baseline));
}
.nc-chip--no-icon .nc-chip__text[data-v-470fc02d] {
  padding-inline-start: calc(1.5 * var(--default-grid-baseline));
}
.nc-chip__text[data-v-470fc02d] {
  flex: 1 auto;
  overflow: hidden;
  text-overflow: ellipsis;
  text-wrap: nowrap;
}
.nc-chip__icon[data-v-470fc02d] {
  flex: 0 0 var(--chip-size);
  margin-inline-end: var(--default-grid-baseline);
  line-height: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  height: var(--chip-size);
  width: var(--chip-size);
}
.nc-chip__actions[data-v-470fc02d] {
  flex: 0 0 var(--chip-size);
  --default-clickable-area: var(--chip-size);
  --border-radius-element: var(--chip-radius);
}`, ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/NewFileRequestDialog.vue?vue&type=style&index=0&id=27651adf&lang=scss":
/*!******************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/NewFileRequestDialog.vue?vue&type=style&index=0&id=27651adf&lang=scss ***!
  \******************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/noSourceMaps.js */ "./node_modules/css-loader/dist/runtime/noSourceMaps.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__);
// Imports


var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default()((_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default()));
// Module
___CSS_LOADER_EXPORT___.push([module.id, `.file-request-dialog {
  --margin: 18px;
}
.file-request-dialog__header {
  margin: 0 var(--margin);
}
.file-request-dialog__form {
  position: relative;
  overflow: auto;
  padding: var(--margin) var(--margin);
  margin-top: calc(-1 * var(--margin));
}
.file-request-dialog fieldset {
  display: flex;
  flex-direction: column;
  width: 100%;
  margin-top: var(--margin);
}
.file-request-dialog fieldset legend {
  display: flex;
  align-items: center;
  width: 100%;
}
.file-request-dialog__info {
  color: var(--color-text-maxcontrast);
  padding-block: 4px;
  display: flex;
  align-items: center;
}
.file-request-dialog__info .file-request-dialog__info-icon {
  margin-inline-end: 8px;
}
.file-request-dialog .dialog__actions {
  width: auto;
  margin-inline: 12px;
}
.file-request-dialog .dialog__actions span.dialog__actions-separator {
  margin-inline-start: auto;
}
.file-request-dialog .input-field__helper-text-message {
  color: var(--color-text-maxcontrast);
}`, ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/NewFileRequestDialog/NewFileRequestDialogDatePassword.vue?vue&type=style&index=0&id=75196638&scoped=true&lang=scss":
/*!***************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/NewFileRequestDialog/NewFileRequestDialogDatePassword.vue?vue&type=style&index=0&id=75196638&scoped=true&lang=scss ***!
  \***************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../../../node_modules/css-loader/dist/runtime/noSourceMaps.js */ "./node_modules/css-loader/dist/runtime/noSourceMaps.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__);
// Imports


var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default()((_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default()));
// Module
___CSS_LOADER_EXPORT___.push([module.id, `.file-request-dialog__password-field[data-v-75196638] {
  display: flex;
  align-items: flex-start;
  gap: 8px;
  margin-top: 12px;
}
.file-request-dialog__password-field > div[data-v-75196638] {
  margin: 0;
}`, ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/NewFileRequestDialog/NewFileRequestDialogFinish.vue?vue&type=style&index=0&id=ead70b64&scoped=true&lang=css":
/*!*****************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/NewFileRequestDialog/NewFileRequestDialogFinish.vue?vue&type=style&index=0&id=ead70b64&scoped=true&lang=css ***!
  \*****************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../../../node_modules/css-loader/dist/runtime/noSourceMaps.js */ "./node_modules/css-loader/dist/runtime/noSourceMaps.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__);
// Imports


var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default()((_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default()));
// Module
___CSS_LOADER_EXPORT___.push([module.id, `
.input-field[data-v-ead70b64],
.file-request-dialog__emails[data-v-ead70b64] {
	margin-top: var(--margin);
}
.file-request-dialog__emails[data-v-ead70b64] {
	display: flex;
	gap: var(--default-grid-baseline);
	flex-wrap: wrap;
}
`, ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/NewFileRequestDialog/NewFileRequestDialogIntro.vue?vue&type=style&index=0&id=17dab3fe&scoped=true&lang=css":
/*!****************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/NewFileRequestDialog/NewFileRequestDialogIntro.vue?vue&type=style&index=0&id=17dab3fe&scoped=true&lang=css ***!
  \****************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../../../../../node_modules/css-loader/dist/runtime/noSourceMaps.js */ "./node_modules/css-loader/dist/runtime/noSourceMaps.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../../../../../node_modules/css-loader/dist/runtime/api.js */ "./node_modules/css-loader/dist/runtime/api.js");
/* harmony import */ var _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1__);
// Imports


var ___CSS_LOADER_EXPORT___ = _node_modules_css_loader_dist_runtime_api_js__WEBPACK_IMPORTED_MODULE_1___default()((_node_modules_css_loader_dist_runtime_noSourceMaps_js__WEBPACK_IMPORTED_MODULE_0___default()));
// Module
___CSS_LOADER_EXPORT___.push([module.id, `
.file-request-dialog__note[data-v-17dab3fe] textarea {
	width: 100% !important;
	min-height: 80px;
}
`, ""]);
// Exports
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (___CSS_LOADER_EXPORT___);


/***/ }),

/***/ "./node_modules/@nextcloud/vue/dist/assets/NcChip-CEKw1zaK.css":
/*!*********************************************************************!*\
  !*** ./node_modules/@nextcloud/vue/dist/assets/NcChip-CEKw1zaK.css ***!
  \*********************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! !../../../../style-loader/dist/runtime/injectStylesIntoStyleTag.js */ "./node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js");
/* harmony import */ var _style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! !../../../../style-loader/dist/runtime/styleDomAPI.js */ "./node_modules/style-loader/dist/runtime/styleDomAPI.js");
/* harmony import */ var _style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../style-loader/dist/runtime/insertBySelector.js */ "./node_modules/style-loader/dist/runtime/insertBySelector.js");
/* harmony import */ var _style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../style-loader/dist/runtime/setAttributesWithoutAttributes.js */ "./node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js");
/* harmony import */ var _style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! !../../../../style-loader/dist/runtime/insertStyleElement.js */ "./node_modules/style-loader/dist/runtime/insertStyleElement.js");
/* harmony import */ var _style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! !../../../../style-loader/dist/runtime/styleTagTransform.js */ "./node_modules/style-loader/dist/runtime/styleTagTransform.js");
/* harmony import */ var _style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _css_loader_dist_cjs_js_NcChip_CEKw1zaK_css__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../css-loader/dist/cjs.js!./NcChip-CEKw1zaK.css */ "./node_modules/css-loader/dist/cjs.js!./node_modules/@nextcloud/vue/dist/assets/NcChip-CEKw1zaK.css");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_css_loader_dist_cjs_js_NcChip_CEKw1zaK_css__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_css_loader_dist_cjs_js_NcChip_CEKw1zaK_css__WEBPACK_IMPORTED_MODULE_6__["default"] && _css_loader_dist_cjs_js_NcChip_CEKw1zaK_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _css_loader_dist_cjs_js_NcChip_CEKw1zaK_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/NewFileRequestDialog.vue?vue&type=style&index=0&id=27651adf&lang=scss":
/*!**********************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/NewFileRequestDialog.vue?vue&type=style&index=0&id=27651adf&lang=scss ***!
  \**********************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
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
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_NewFileRequestDialog_vue_vue_type_style_index_0_id_27651adf_lang_scss__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./NewFileRequestDialog.vue?vue&type=style&index=0&id=27651adf&lang=scss */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/NewFileRequestDialog.vue?vue&type=style&index=0&id=27651adf&lang=scss");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_NewFileRequestDialog_vue_vue_type_style_index_0_id_27651adf_lang_scss__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_NewFileRequestDialog_vue_vue_type_style_index_0_id_27651adf_lang_scss__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_NewFileRequestDialog_vue_vue_type_style_index_0_id_27651adf_lang_scss__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_NewFileRequestDialog_vue_vue_type_style_index_0_id_27651adf_lang_scss__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/NewFileRequestDialog/NewFileRequestDialogDatePassword.vue?vue&type=style&index=0&id=75196638&scoped=true&lang=scss":
/*!*******************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/NewFileRequestDialog/NewFileRequestDialogDatePassword.vue?vue&type=style&index=0&id=75196638&scoped=true&lang=scss ***!
  \*******************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! !../../../../../node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js */ "./node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! !../../../../../node_modules/style-loader/dist/runtime/styleDomAPI.js */ "./node_modules/style-loader/dist/runtime/styleDomAPI.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../../node_modules/style-loader/dist/runtime/insertBySelector.js */ "./node_modules/style-loader/dist/runtime/insertBySelector.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../../node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js */ "./node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! !../../../../../node_modules/style-loader/dist/runtime/insertStyleElement.js */ "./node_modules/style-loader/dist/runtime/insertStyleElement.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! !../../../../../node_modules/style-loader/dist/runtime/styleTagTransform.js */ "./node_modules/style-loader/dist/runtime/styleTagTransform.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_NewFileRequestDialogDatePassword_vue_vue_type_style_index_0_id_75196638_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../../node_modules/css-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../node_modules/sass-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./NewFileRequestDialogDatePassword.vue?vue&type=style&index=0&id=75196638&scoped=true&lang=scss */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/NewFileRequestDialog/NewFileRequestDialogDatePassword.vue?vue&type=style&index=0&id=75196638&scoped=true&lang=scss");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_NewFileRequestDialogDatePassword_vue_vue_type_style_index_0_id_75196638_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_NewFileRequestDialogDatePassword_vue_vue_type_style_index_0_id_75196638_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_NewFileRequestDialogDatePassword_vue_vue_type_style_index_0_id_75196638_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_NewFileRequestDialogDatePassword_vue_vue_type_style_index_0_id_75196638_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/NewFileRequestDialog/NewFileRequestDialogFinish.vue?vue&type=style&index=0&id=ead70b64&scoped=true&lang=css":
/*!*********************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/NewFileRequestDialog/NewFileRequestDialogFinish.vue?vue&type=style&index=0&id=ead70b64&scoped=true&lang=css ***!
  \*********************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! !../../../../../node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js */ "./node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! !../../../../../node_modules/style-loader/dist/runtime/styleDomAPI.js */ "./node_modules/style-loader/dist/runtime/styleDomAPI.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../../node_modules/style-loader/dist/runtime/insertBySelector.js */ "./node_modules/style-loader/dist/runtime/insertBySelector.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../../node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js */ "./node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! !../../../../../node_modules/style-loader/dist/runtime/insertStyleElement.js */ "./node_modules/style-loader/dist/runtime/insertStyleElement.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! !../../../../../node_modules/style-loader/dist/runtime/styleTagTransform.js */ "./node_modules/style-loader/dist/runtime/styleTagTransform.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_NewFileRequestDialogFinish_vue_vue_type_style_index_0_id_ead70b64_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../../node_modules/css-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./NewFileRequestDialogFinish.vue?vue&type=style&index=0&id=ead70b64&scoped=true&lang=css */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/NewFileRequestDialog/NewFileRequestDialogFinish.vue?vue&type=style&index=0&id=ead70b64&scoped=true&lang=css");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_NewFileRequestDialogFinish_vue_vue_type_style_index_0_id_ead70b64_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_NewFileRequestDialogFinish_vue_vue_type_style_index_0_id_ead70b64_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_NewFileRequestDialogFinish_vue_vue_type_style_index_0_id_ead70b64_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_NewFileRequestDialogFinish_vue_vue_type_style_index_0_id_ead70b64_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/NewFileRequestDialog/NewFileRequestDialogIntro.vue?vue&type=style&index=0&id=17dab3fe&scoped=true&lang=css":
/*!********************************************************************************************************************************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/NewFileRequestDialog/NewFileRequestDialogIntro.vue?vue&type=style&index=0&id=17dab3fe&scoped=true&lang=css ***!
  \********************************************************************************************************************************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! !../../../../../node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js */ "./node_modules/style-loader/dist/runtime/injectStylesIntoStyleTag.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! !../../../../../node_modules/style-loader/dist/runtime/styleDomAPI.js */ "./node_modules/style-loader/dist/runtime/styleDomAPI.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../../../../../node_modules/style-loader/dist/runtime/insertBySelector.js */ "./node_modules/style-loader/dist/runtime/insertBySelector.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../../node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js */ "./node_modules/style-loader/dist/runtime/setAttributesWithoutAttributes.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! !../../../../../node_modules/style-loader/dist/runtime/insertStyleElement.js */ "./node_modules/style-loader/dist/runtime/insertStyleElement.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4__);
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! !../../../../../node_modules/style-loader/dist/runtime/styleTagTransform.js */ "./node_modules/style-loader/dist/runtime/styleTagTransform.js");
/* harmony import */ var _node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default = /*#__PURE__*/__webpack_require__.n(_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5__);
/* harmony import */ var _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_NewFileRequestDialogIntro_vue_vue_type_style_index_0_id_17dab3fe_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! !!../../../../../node_modules/css-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./NewFileRequestDialogIntro.vue?vue&type=style&index=0&id=17dab3fe&scoped=true&lang=css */ "./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/NewFileRequestDialog/NewFileRequestDialogIntro.vue?vue&type=style&index=0&id=17dab3fe&scoped=true&lang=css");

      
      
      
      
      
      
      
      
      

var options = {};

options.styleTagTransform = (_node_modules_style_loader_dist_runtime_styleTagTransform_js__WEBPACK_IMPORTED_MODULE_5___default());
options.setAttributes = (_node_modules_style_loader_dist_runtime_setAttributesWithoutAttributes_js__WEBPACK_IMPORTED_MODULE_3___default());

      options.insert = _node_modules_style_loader_dist_runtime_insertBySelector_js__WEBPACK_IMPORTED_MODULE_2___default().bind(null, "head");
    
options.domAPI = (_node_modules_style_loader_dist_runtime_styleDomAPI_js__WEBPACK_IMPORTED_MODULE_1___default());
options.insertStyleElement = (_node_modules_style_loader_dist_runtime_insertStyleElement_js__WEBPACK_IMPORTED_MODULE_4___default());

var update = _node_modules_style_loader_dist_runtime_injectStylesIntoStyleTag_js__WEBPACK_IMPORTED_MODULE_0___default()(_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_NewFileRequestDialogIntro_vue_vue_type_style_index_0_id_17dab3fe_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"], options);




       /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_NewFileRequestDialogIntro_vue_vue_type_style_index_0_id_17dab3fe_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"] && _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_NewFileRequestDialogIntro_vue_vue_type_style_index_0_id_17dab3fe_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals ? _node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_NewFileRequestDialogIntro_vue_vue_type_style_index_0_id_17dab3fe_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_6__["default"].locals : undefined);


/***/ }),

/***/ "./apps/files_sharing/src/components/NewFileRequestDialog.vue":
/*!********************************************************************!*\
  !*** ./apps/files_sharing/src/components/NewFileRequestDialog.vue ***!
  \********************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _NewFileRequestDialog_vue_vue_type_template_id_27651adf__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./NewFileRequestDialog.vue?vue&type=template&id=27651adf */ "./apps/files_sharing/src/components/NewFileRequestDialog.vue?vue&type=template&id=27651adf");
/* harmony import */ var _NewFileRequestDialog_vue_vue_type_script_lang_ts__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./NewFileRequestDialog.vue?vue&type=script&lang=ts */ "./apps/files_sharing/src/components/NewFileRequestDialog.vue?vue&type=script&lang=ts");
/* harmony import */ var _NewFileRequestDialog_vue_vue_type_style_index_0_id_27651adf_lang_scss__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./NewFileRequestDialog.vue?vue&type=style&index=0&id=27651adf&lang=scss */ "./apps/files_sharing/src/components/NewFileRequestDialog.vue?vue&type=style&index=0&id=27651adf&lang=scss");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _NewFileRequestDialog_vue_vue_type_script_lang_ts__WEBPACK_IMPORTED_MODULE_1__["default"],
  _NewFileRequestDialog_vue_vue_type_template_id_27651adf__WEBPACK_IMPORTED_MODULE_0__.render,
  _NewFileRequestDialog_vue_vue_type_template_id_27651adf__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/files_sharing/src/components/NewFileRequestDialog.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./apps/files_sharing/src/components/NewFileRequestDialog/NewFileRequestDialogDatePassword.vue":
/*!*****************************************************************************************************!*\
  !*** ./apps/files_sharing/src/components/NewFileRequestDialog/NewFileRequestDialogDatePassword.vue ***!
  \*****************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _NewFileRequestDialogDatePassword_vue_vue_type_template_id_75196638_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./NewFileRequestDialogDatePassword.vue?vue&type=template&id=75196638&scoped=true */ "./apps/files_sharing/src/components/NewFileRequestDialog/NewFileRequestDialogDatePassword.vue?vue&type=template&id=75196638&scoped=true");
/* harmony import */ var _NewFileRequestDialogDatePassword_vue_vue_type_script_lang_ts__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./NewFileRequestDialogDatePassword.vue?vue&type=script&lang=ts */ "./apps/files_sharing/src/components/NewFileRequestDialog/NewFileRequestDialogDatePassword.vue?vue&type=script&lang=ts");
/* harmony import */ var _NewFileRequestDialogDatePassword_vue_vue_type_style_index_0_id_75196638_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./NewFileRequestDialogDatePassword.vue?vue&type=style&index=0&id=75196638&scoped=true&lang=scss */ "./apps/files_sharing/src/components/NewFileRequestDialog/NewFileRequestDialogDatePassword.vue?vue&type=style&index=0&id=75196638&scoped=true&lang=scss");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _NewFileRequestDialogDatePassword_vue_vue_type_script_lang_ts__WEBPACK_IMPORTED_MODULE_1__["default"],
  _NewFileRequestDialogDatePassword_vue_vue_type_template_id_75196638_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render,
  _NewFileRequestDialogDatePassword_vue_vue_type_template_id_75196638_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "75196638",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/files_sharing/src/components/NewFileRequestDialog/NewFileRequestDialogDatePassword.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./apps/files_sharing/src/components/NewFileRequestDialog/NewFileRequestDialogFinish.vue":
/*!***********************************************************************************************!*\
  !*** ./apps/files_sharing/src/components/NewFileRequestDialog/NewFileRequestDialogFinish.vue ***!
  \***********************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _NewFileRequestDialogFinish_vue_vue_type_template_id_ead70b64_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./NewFileRequestDialogFinish.vue?vue&type=template&id=ead70b64&scoped=true */ "./apps/files_sharing/src/components/NewFileRequestDialog/NewFileRequestDialogFinish.vue?vue&type=template&id=ead70b64&scoped=true");
/* harmony import */ var _NewFileRequestDialogFinish_vue_vue_type_script_lang_ts__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./NewFileRequestDialogFinish.vue?vue&type=script&lang=ts */ "./apps/files_sharing/src/components/NewFileRequestDialog/NewFileRequestDialogFinish.vue?vue&type=script&lang=ts");
/* harmony import */ var _NewFileRequestDialogFinish_vue_vue_type_style_index_0_id_ead70b64_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./NewFileRequestDialogFinish.vue?vue&type=style&index=0&id=ead70b64&scoped=true&lang=css */ "./apps/files_sharing/src/components/NewFileRequestDialog/NewFileRequestDialogFinish.vue?vue&type=style&index=0&id=ead70b64&scoped=true&lang=css");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _NewFileRequestDialogFinish_vue_vue_type_script_lang_ts__WEBPACK_IMPORTED_MODULE_1__["default"],
  _NewFileRequestDialogFinish_vue_vue_type_template_id_ead70b64_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render,
  _NewFileRequestDialogFinish_vue_vue_type_template_id_ead70b64_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "ead70b64",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/files_sharing/src/components/NewFileRequestDialog/NewFileRequestDialogFinish.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./apps/files_sharing/src/components/NewFileRequestDialog/NewFileRequestDialogIntro.vue":
/*!**********************************************************************************************!*\
  !*** ./apps/files_sharing/src/components/NewFileRequestDialog/NewFileRequestDialogIntro.vue ***!
  \**********************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _NewFileRequestDialogIntro_vue_vue_type_template_id_17dab3fe_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./NewFileRequestDialogIntro.vue?vue&type=template&id=17dab3fe&scoped=true */ "./apps/files_sharing/src/components/NewFileRequestDialog/NewFileRequestDialogIntro.vue?vue&type=template&id=17dab3fe&scoped=true");
/* harmony import */ var _NewFileRequestDialogIntro_vue_vue_type_script_lang_ts__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./NewFileRequestDialogIntro.vue?vue&type=script&lang=ts */ "./apps/files_sharing/src/components/NewFileRequestDialog/NewFileRequestDialogIntro.vue?vue&type=script&lang=ts");
/* harmony import */ var _NewFileRequestDialogIntro_vue_vue_type_style_index_0_id_17dab3fe_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ./NewFileRequestDialogIntro.vue?vue&type=style&index=0&id=17dab3fe&scoped=true&lang=css */ "./apps/files_sharing/src/components/NewFileRequestDialog/NewFileRequestDialogIntro.vue?vue&type=style&index=0&id=17dab3fe&scoped=true&lang=css");
/* harmony import */ var _node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! !../../../../../node_modules/vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");



;


/* normalize component */

var component = (0,_node_modules_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_3__["default"])(
  _NewFileRequestDialogIntro_vue_vue_type_script_lang_ts__WEBPACK_IMPORTED_MODULE_1__["default"],
  _NewFileRequestDialogIntro_vue_vue_type_template_id_17dab3fe_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render,
  _NewFileRequestDialogIntro_vue_vue_type_template_id_17dab3fe_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  "17dab3fe",
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "apps/files_sharing/src/components/NewFileRequestDialog/NewFileRequestDialogIntro.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./node_modules/vue-material-design-icons/AutoFix.vue":
/*!************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/AutoFix.vue ***!
  \************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _AutoFix_vue_vue_type_template_id_754cf12c__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./AutoFix.vue?vue&type=template&id=754cf12c */ "./node_modules/vue-material-design-icons/AutoFix.vue?vue&type=template&id=754cf12c");
/* harmony import */ var _AutoFix_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./AutoFix.vue?vue&type=script&lang=js */ "./node_modules/vue-material-design-icons/AutoFix.vue?vue&type=script&lang=js");
/* harmony import */ var _vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */
;
var component = (0,_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__["default"])(
  _AutoFix_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"],
  _AutoFix_vue_vue_type_template_id_754cf12c__WEBPACK_IMPORTED_MODULE_0__.render,
  _AutoFix_vue_vue_type_template_id_754cf12c__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "node_modules/vue-material-design-icons/AutoFix.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/AutoFix.vue?vue&type=script&lang=js":
/*!***********************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/AutoFix.vue?vue&type=script&lang=js ***!
  \***********************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: "AutoFixIcon",
  emits: ['click'],
  props: {
    title: {
      type: String,
    },
    fillColor: {
      type: String,
      default: "currentColor"
    },
    size: {
      type: Number,
      default: 24
    }
  }
});


/***/ }),

/***/ "./node_modules/vue-material-design-icons/ClipboardText.vue":
/*!******************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/ClipboardText.vue ***!
  \******************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _ClipboardText_vue_vue_type_template_id_71acd7e7__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./ClipboardText.vue?vue&type=template&id=71acd7e7 */ "./node_modules/vue-material-design-icons/ClipboardText.vue?vue&type=template&id=71acd7e7");
/* harmony import */ var _ClipboardText_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./ClipboardText.vue?vue&type=script&lang=js */ "./node_modules/vue-material-design-icons/ClipboardText.vue?vue&type=script&lang=js");
/* harmony import */ var _vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */
;
var component = (0,_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__["default"])(
  _ClipboardText_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"],
  _ClipboardText_vue_vue_type_template_id_71acd7e7__WEBPACK_IMPORTED_MODULE_0__.render,
  _ClipboardText_vue_vue_type_template_id_71acd7e7__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "node_modules/vue-material-design-icons/ClipboardText.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/ClipboardText.vue?vue&type=script&lang=js":
/*!*****************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/ClipboardText.vue?vue&type=script&lang=js ***!
  \*****************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: "ClipboardTextIcon",
  emits: ['click'],
  props: {
    title: {
      type: String,
    },
    fillColor: {
      type: String,
      default: "currentColor"
    },
    size: {
      type: Number,
      default: 24
    }
  }
});


/***/ }),

/***/ "./node_modules/vue-material-design-icons/Information.vue":
/*!****************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/Information.vue ***!
  \****************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _Information_vue_vue_type_template_id_403c6fb0__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ./Information.vue?vue&type=template&id=403c6fb0 */ "./node_modules/vue-material-design-icons/Information.vue?vue&type=template&id=403c6fb0");
/* harmony import */ var _Information_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ./Information.vue?vue&type=script&lang=js */ "./node_modules/vue-material-design-icons/Information.vue?vue&type=script&lang=js");
/* harmony import */ var _vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! !../vue-loader/lib/runtime/componentNormalizer.js */ "./node_modules/vue-loader/lib/runtime/componentNormalizer.js");





/* normalize component */
;
var component = (0,_vue_loader_lib_runtime_componentNormalizer_js__WEBPACK_IMPORTED_MODULE_2__["default"])(
  _Information_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_1__["default"],
  _Information_vue_vue_type_template_id_403c6fb0__WEBPACK_IMPORTED_MODULE_0__.render,
  _Information_vue_vue_type_template_id_403c6fb0__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns,
  false,
  null,
  null,
  null
  
)

/* hot reload */
if (false) { var api; }
component.options.__file = "node_modules/vue-material-design-icons/Information.vue"
/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (component.exports);

/***/ }),

/***/ "./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/Information.vue?vue&type=script&lang=js":
/*!***************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/Information.vue?vue&type=script&lang=js ***!
  \***************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });

/* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = ({
  name: "InformationIcon",
  emits: ['click'],
  props: {
    title: {
      type: String,
    },
    fillColor: {
      type: String,
      default: "currentColor"
    },
    size: {
      type: Number,
      default: 24
    }
  }
});


/***/ }),

/***/ "./apps/files_sharing/src/components/NewFileRequestDialog.vue?vue&type=script&lang=ts":
/*!********************************************************************************************!*\
  !*** ./apps/files_sharing/src/components/NewFileRequestDialog.vue?vue&type=script&lang=ts ***!
  \********************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_4_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_NewFileRequestDialog_vue_vue_type_script_lang_ts__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./NewFileRequestDialog.vue?vue&type=script&lang=ts */ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/NewFileRequestDialog.vue?vue&type=script&lang=ts");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_4_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_NewFileRequestDialog_vue_vue_type_script_lang_ts__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/files_sharing/src/components/NewFileRequestDialog/NewFileRequestDialogDatePassword.vue?vue&type=script&lang=ts":
/*!*****************************************************************************************************************************!*\
  !*** ./apps/files_sharing/src/components/NewFileRequestDialog/NewFileRequestDialogDatePassword.vue?vue&type=script&lang=ts ***!
  \*****************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_4_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_NewFileRequestDialogDatePassword_vue_vue_type_script_lang_ts__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./NewFileRequestDialogDatePassword.vue?vue&type=script&lang=ts */ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/NewFileRequestDialog/NewFileRequestDialogDatePassword.vue?vue&type=script&lang=ts");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_4_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_NewFileRequestDialogDatePassword_vue_vue_type_script_lang_ts__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/files_sharing/src/components/NewFileRequestDialog/NewFileRequestDialogFinish.vue?vue&type=script&lang=ts":
/*!***********************************************************************************************************************!*\
  !*** ./apps/files_sharing/src/components/NewFileRequestDialog/NewFileRequestDialogFinish.vue?vue&type=script&lang=ts ***!
  \***********************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_4_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_NewFileRequestDialogFinish_vue_vue_type_script_lang_ts__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./NewFileRequestDialogFinish.vue?vue&type=script&lang=ts */ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/NewFileRequestDialog/NewFileRequestDialogFinish.vue?vue&type=script&lang=ts");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_4_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_NewFileRequestDialogFinish_vue_vue_type_script_lang_ts__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/files_sharing/src/components/NewFileRequestDialog/NewFileRequestDialogIntro.vue?vue&type=script&lang=ts":
/*!**********************************************************************************************************************!*\
  !*** ./apps/files_sharing/src/components/NewFileRequestDialog/NewFileRequestDialogIntro.vue?vue&type=script&lang=ts ***!
  \**********************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_4_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_NewFileRequestDialogIntro_vue_vue_type_script_lang_ts__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./NewFileRequestDialogIntro.vue?vue&type=script&lang=ts */ "./node_modules/babel-loader/lib/index.js!./node_modules/ts-loader/index.js??clonedRuleSet-4.use[1]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/NewFileRequestDialog/NewFileRequestDialogIntro.vue?vue&type=script&lang=ts");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_node_modules_babel_loader_lib_index_js_node_modules_ts_loader_index_js_clonedRuleSet_4_use_1_node_modules_vue_loader_lib_index_js_vue_loader_options_NewFileRequestDialogIntro_vue_vue_type_script_lang_ts__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./apps/files_sharing/src/components/NewFileRequestDialog.vue?vue&type=template&id=27651adf":
/*!**************************************************************************************************!*\
  !*** ./apps/files_sharing/src/components/NewFileRequestDialog.vue?vue&type=template&id=27651adf ***!
  \**************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_NewFileRequestDialog_vue_vue_type_template_id_27651adf__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_NewFileRequestDialog_vue_vue_type_template_id_27651adf__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_NewFileRequestDialog_vue_vue_type_template_id_27651adf__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/babel-loader/lib/index.js!../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./NewFileRequestDialog.vue?vue&type=template&id=27651adf */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/NewFileRequestDialog.vue?vue&type=template&id=27651adf");


/***/ }),

/***/ "./apps/files_sharing/src/components/NewFileRequestDialog/NewFileRequestDialogDatePassword.vue?vue&type=template&id=75196638&scoped=true":
/*!***********************************************************************************************************************************************!*\
  !*** ./apps/files_sharing/src/components/NewFileRequestDialog/NewFileRequestDialogDatePassword.vue?vue&type=template&id=75196638&scoped=true ***!
  \***********************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_NewFileRequestDialogDatePassword_vue_vue_type_template_id_75196638_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_NewFileRequestDialogDatePassword_vue_vue_type_template_id_75196638_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_NewFileRequestDialogDatePassword_vue_vue_type_template_id_75196638_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./NewFileRequestDialogDatePassword.vue?vue&type=template&id=75196638&scoped=true */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/NewFileRequestDialog/NewFileRequestDialogDatePassword.vue?vue&type=template&id=75196638&scoped=true");


/***/ }),

/***/ "./apps/files_sharing/src/components/NewFileRequestDialog/NewFileRequestDialogFinish.vue?vue&type=template&id=ead70b64&scoped=true":
/*!*****************************************************************************************************************************************!*\
  !*** ./apps/files_sharing/src/components/NewFileRequestDialog/NewFileRequestDialogFinish.vue?vue&type=template&id=ead70b64&scoped=true ***!
  \*****************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_NewFileRequestDialogFinish_vue_vue_type_template_id_ead70b64_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_NewFileRequestDialogFinish_vue_vue_type_template_id_ead70b64_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_NewFileRequestDialogFinish_vue_vue_type_template_id_ead70b64_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./NewFileRequestDialogFinish.vue?vue&type=template&id=ead70b64&scoped=true */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/NewFileRequestDialog/NewFileRequestDialogFinish.vue?vue&type=template&id=ead70b64&scoped=true");


/***/ }),

/***/ "./apps/files_sharing/src/components/NewFileRequestDialog/NewFileRequestDialogIntro.vue?vue&type=template&id=17dab3fe&scoped=true":
/*!****************************************************************************************************************************************!*\
  !*** ./apps/files_sharing/src/components/NewFileRequestDialog/NewFileRequestDialogIntro.vue?vue&type=template&id=17dab3fe&scoped=true ***!
  \****************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_NewFileRequestDialogIntro_vue_vue_type_template_id_17dab3fe_scoped_true__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_NewFileRequestDialogIntro_vue_vue_type_template_id_17dab3fe_scoped_true__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _node_modules_babel_loader_lib_index_js_node_modules_vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_node_modules_vue_loader_lib_index_js_vue_loader_options_NewFileRequestDialogIntro_vue_vue_type_template_id_17dab3fe_scoped_true__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/babel-loader/lib/index.js!../../../../../node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./NewFileRequestDialogIntro.vue?vue&type=template&id=17dab3fe&scoped=true */ "./node_modules/babel-loader/lib/index.js!./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/NewFileRequestDialog/NewFileRequestDialogIntro.vue?vue&type=template&id=17dab3fe&scoped=true");


/***/ }),

/***/ "./apps/files_sharing/src/components/NewFileRequestDialog.vue?vue&type=style&index=0&id=27651adf&lang=scss":
/*!*****************************************************************************************************************!*\
  !*** ./apps/files_sharing/src/components/NewFileRequestDialog.vue?vue&type=style&index=0&id=27651adf&lang=scss ***!
  \*****************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_NewFileRequestDialog_vue_vue_type_style_index_0_id_27651adf_lang_scss__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../node_modules/style-loader/dist/cjs.js!../../../../node_modules/css-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../node_modules/sass-loader/dist/cjs.js!../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./NewFileRequestDialog.vue?vue&type=style&index=0&id=27651adf&lang=scss */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/NewFileRequestDialog.vue?vue&type=style&index=0&id=27651adf&lang=scss");


/***/ }),

/***/ "./apps/files_sharing/src/components/NewFileRequestDialog/NewFileRequestDialogDatePassword.vue?vue&type=style&index=0&id=75196638&scoped=true&lang=scss":
/*!**************************************************************************************************************************************************************!*\
  !*** ./apps/files_sharing/src/components/NewFileRequestDialog/NewFileRequestDialogDatePassword.vue?vue&type=style&index=0&id=75196638&scoped=true&lang=scss ***!
  \**************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_sass_loader_dist_cjs_js_node_modules_vue_loader_lib_index_js_vue_loader_options_NewFileRequestDialogDatePassword_vue_vue_type_style_index_0_id_75196638_scoped_true_lang_scss__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/style-loader/dist/cjs.js!../../../../../node_modules/css-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../node_modules/sass-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./NewFileRequestDialogDatePassword.vue?vue&type=style&index=0&id=75196638&scoped=true&lang=scss */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/sass-loader/dist/cjs.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/NewFileRequestDialog/NewFileRequestDialogDatePassword.vue?vue&type=style&index=0&id=75196638&scoped=true&lang=scss");


/***/ }),

/***/ "./apps/files_sharing/src/components/NewFileRequestDialog/NewFileRequestDialogFinish.vue?vue&type=style&index=0&id=ead70b64&scoped=true&lang=css":
/*!*******************************************************************************************************************************************************!*\
  !*** ./apps/files_sharing/src/components/NewFileRequestDialog/NewFileRequestDialogFinish.vue?vue&type=style&index=0&id=ead70b64&scoped=true&lang=css ***!
  \*******************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_NewFileRequestDialogFinish_vue_vue_type_style_index_0_id_ead70b64_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/style-loader/dist/cjs.js!../../../../../node_modules/css-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./NewFileRequestDialogFinish.vue?vue&type=style&index=0&id=ead70b64&scoped=true&lang=css */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/NewFileRequestDialog/NewFileRequestDialogFinish.vue?vue&type=style&index=0&id=ead70b64&scoped=true&lang=css");


/***/ }),

/***/ "./apps/files_sharing/src/components/NewFileRequestDialog/NewFileRequestDialogIntro.vue?vue&type=style&index=0&id=17dab3fe&scoped=true&lang=css":
/*!******************************************************************************************************************************************************!*\
  !*** ./apps/files_sharing/src/components/NewFileRequestDialog/NewFileRequestDialogIntro.vue?vue&type=style&index=0&id=17dab3fe&scoped=true&lang=css ***!
  \******************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony import */ var _node_modules_style_loader_dist_cjs_js_node_modules_css_loader_dist_cjs_js_node_modules_vue_loader_lib_loaders_stylePostLoader_js_node_modules_vue_loader_lib_index_js_vue_loader_options_NewFileRequestDialogIntro_vue_vue_type_style_index_0_id_17dab3fe_scoped_true_lang_css__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../../../../../node_modules/style-loader/dist/cjs.js!../../../../../node_modules/css-loader/dist/cjs.js!../../../../../node_modules/vue-loader/lib/loaders/stylePostLoader.js!../../../../../node_modules/vue-loader/lib/index.js??vue-loader-options!./NewFileRequestDialogIntro.vue?vue&type=style&index=0&id=17dab3fe&scoped=true&lang=css */ "./node_modules/style-loader/dist/cjs.js!./node_modules/css-loader/dist/cjs.js!./node_modules/vue-loader/lib/loaders/stylePostLoader.js!./node_modules/vue-loader/lib/index.js??vue-loader-options!./apps/files_sharing/src/components/NewFileRequestDialog/NewFileRequestDialogIntro.vue?vue&type=style&index=0&id=17dab3fe&scoped=true&lang=css");


/***/ }),

/***/ "./node_modules/vue-material-design-icons/AutoFix.vue?vue&type=script&lang=js":
/*!************************************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/AutoFix.vue?vue&type=script&lang=js ***!
  \************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _vue_loader_lib_index_js_vue_loader_options_AutoFix_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../vue-loader/lib/index.js??vue-loader-options!./AutoFix.vue?vue&type=script&lang=js */ "./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/AutoFix.vue?vue&type=script&lang=js");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_vue_loader_lib_index_js_vue_loader_options_AutoFix_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./node_modules/vue-material-design-icons/ClipboardText.vue?vue&type=script&lang=js":
/*!******************************************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/ClipboardText.vue?vue&type=script&lang=js ***!
  \******************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _vue_loader_lib_index_js_vue_loader_options_ClipboardText_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../vue-loader/lib/index.js??vue-loader-options!./ClipboardText.vue?vue&type=script&lang=js */ "./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/ClipboardText.vue?vue&type=script&lang=js");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_vue_loader_lib_index_js_vue_loader_options_ClipboardText_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./node_modules/vue-material-design-icons/Information.vue?vue&type=script&lang=js":
/*!****************************************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/Information.vue?vue&type=script&lang=js ***!
  \****************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (__WEBPACK_DEFAULT_EXPORT__)
/* harmony export */ });
/* harmony import */ var _vue_loader_lib_index_js_vue_loader_options_Information_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../vue-loader/lib/index.js??vue-loader-options!./Information.vue?vue&type=script&lang=js */ "./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/Information.vue?vue&type=script&lang=js");
 /* harmony default export */ const __WEBPACK_DEFAULT_EXPORT__ = (_vue_loader_lib_index_js_vue_loader_options_Information_vue_vue_type_script_lang_js__WEBPACK_IMPORTED_MODULE_0__["default"]); 

/***/ }),

/***/ "./node_modules/vue-material-design-icons/AutoFix.vue?vue&type=template&id=754cf12c":
/*!******************************************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/AutoFix.vue?vue&type=template&id=754cf12c ***!
  \******************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_AutoFix_vue_vue_type_template_id_754cf12c__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_AutoFix_vue_vue_type_template_id_754cf12c__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_AutoFix_vue_vue_type_template_id_754cf12c__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../vue-loader/lib/index.js??vue-loader-options!./AutoFix.vue?vue&type=template&id=754cf12c */ "./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/AutoFix.vue?vue&type=template&id=754cf12c");


/***/ }),

/***/ "./node_modules/vue-material-design-icons/ClipboardText.vue?vue&type=template&id=71acd7e7":
/*!************************************************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/ClipboardText.vue?vue&type=template&id=71acd7e7 ***!
  \************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_ClipboardText_vue_vue_type_template_id_71acd7e7__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_ClipboardText_vue_vue_type_template_id_71acd7e7__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_ClipboardText_vue_vue_type_template_id_71acd7e7__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../vue-loader/lib/index.js??vue-loader-options!./ClipboardText.vue?vue&type=template&id=71acd7e7 */ "./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/ClipboardText.vue?vue&type=template&id=71acd7e7");


/***/ }),

/***/ "./node_modules/vue-material-design-icons/Information.vue?vue&type=template&id=403c6fb0":
/*!**********************************************************************************************!*\
  !*** ./node_modules/vue-material-design-icons/Information.vue?vue&type=template&id=403c6fb0 ***!
  \**********************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* reexport safe */ _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_Information_vue_vue_type_template_id_403c6fb0__WEBPACK_IMPORTED_MODULE_0__.render),
/* harmony export */   staticRenderFns: () => (/* reexport safe */ _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_Information_vue_vue_type_template_id_403c6fb0__WEBPACK_IMPORTED_MODULE_0__.staticRenderFns)
/* harmony export */ });
/* harmony import */ var _vue_loader_lib_loaders_templateLoader_js_ruleSet_1_rules_3_vue_loader_lib_index_js_vue_loader_options_Information_vue_vue_type_template_id_403c6fb0__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! -!../vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!../vue-loader/lib/index.js??vue-loader-options!./Information.vue?vue&type=template&id=403c6fb0 */ "./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/Information.vue?vue&type=template&id=403c6fb0");


/***/ }),

/***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/AutoFix.vue?vue&type=template&id=754cf12c":
/*!**********************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/AutoFix.vue?vue&type=template&id=754cf12c ***!
  \**********************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c
  return _c(
    "span",
    _vm._b(
      {
        staticClass: "material-design-icon auto-fix-icon",
        attrs: {
          "aria-hidden": _vm.title ? null : true,
          "aria-label": _vm.title,
          role: "img",
        },
        on: {
          click: function ($event) {
            return _vm.$emit("click", $event)
          },
        },
      },
      "span",
      _vm.$attrs,
      false
    ),
    [
      _c(
        "svg",
        {
          staticClass: "material-design-icon__svg",
          attrs: {
            fill: _vm.fillColor,
            width: _vm.size,
            height: _vm.size,
            viewBox: "0 0 24 24",
          },
        },
        [
          _c(
            "path",
            {
              attrs: {
                d: "M7.5,5.6L5,7L6.4,4.5L5,2L7.5,3.4L10,2L8.6,4.5L10,7L7.5,5.6M19.5,15.4L22,14L20.6,16.5L22,19L19.5,17.6L17,19L18.4,16.5L17,14L19.5,15.4M22,2L20.6,4.5L22,7L19.5,5.6L17,7L18.4,4.5L17,2L19.5,3.4L22,2M13.34,12.78L15.78,10.34L13.66,8.22L11.22,10.66L13.34,12.78M14.37,7.29L16.71,9.63C17.1,10 17.1,10.65 16.71,11.04L5.04,22.71C4.65,23.1 4,23.1 3.63,22.71L1.29,20.37C0.9,20 0.9,19.35 1.29,18.96L12.96,7.29C13.35,6.9 14,6.9 14.37,7.29Z",
              },
            },
            [_vm.title ? _c("title", [_vm._v(_vm._s(_vm.title))]) : _vm._e()]
          ),
        ]
      ),
    ]
  )
}
var staticRenderFns = []
render._withStripped = true



/***/ }),

/***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/ClipboardText.vue?vue&type=template&id=71acd7e7":
/*!****************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/ClipboardText.vue?vue&type=template&id=71acd7e7 ***!
  \****************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c
  return _c(
    "span",
    _vm._b(
      {
        staticClass: "material-design-icon clipboard-text-icon",
        attrs: {
          "aria-hidden": _vm.title ? null : true,
          "aria-label": _vm.title,
          role: "img",
        },
        on: {
          click: function ($event) {
            return _vm.$emit("click", $event)
          },
        },
      },
      "span",
      _vm.$attrs,
      false
    ),
    [
      _c(
        "svg",
        {
          staticClass: "material-design-icon__svg",
          attrs: {
            fill: _vm.fillColor,
            width: _vm.size,
            height: _vm.size,
            viewBox: "0 0 24 24",
          },
        },
        [
          _c(
            "path",
            {
              attrs: {
                d: "M17,9H7V7H17M17,13H7V11H17M14,17H7V15H14M12,3A1,1 0 0,1 13,4A1,1 0 0,1 12,5A1,1 0 0,1 11,4A1,1 0 0,1 12,3M19,3H14.82C14.4,1.84 13.3,1 12,1C10.7,1 9.6,1.84 9.18,3H5A2,2 0 0,0 3,5V19A2,2 0 0,0 5,21H19A2,2 0 0,0 21,19V5A2,2 0 0,0 19,3Z",
              },
            },
            [_vm.title ? _c("title", [_vm._v(_vm._s(_vm.title))]) : _vm._e()]
          ),
        ]
      ),
    ]
  )
}
var staticRenderFns = []
render._withStripped = true



/***/ }),

/***/ "./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/Information.vue?vue&type=template&id=403c6fb0":
/*!**************************************************************************************************************************************************************************************************************************************!*\
  !*** ./node_modules/vue-loader/lib/loaders/templateLoader.js??ruleSet[1].rules[3]!./node_modules/vue-loader/lib/index.js??vue-loader-options!./node_modules/vue-material-design-icons/Information.vue?vue&type=template&id=403c6fb0 ***!
  \**************************************************************************************************************************************************************************************************************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   render: () => (/* binding */ render),
/* harmony export */   staticRenderFns: () => (/* binding */ staticRenderFns)
/* harmony export */ });
var render = function render() {
  var _vm = this,
    _c = _vm._self._c
  return _c(
    "span",
    _vm._b(
      {
        staticClass: "material-design-icon information-icon",
        attrs: {
          "aria-hidden": _vm.title ? null : true,
          "aria-label": _vm.title,
          role: "img",
        },
        on: {
          click: function ($event) {
            return _vm.$emit("click", $event)
          },
        },
      },
      "span",
      _vm.$attrs,
      false
    ),
    [
      _c(
        "svg",
        {
          staticClass: "material-design-icon__svg",
          attrs: {
            fill: _vm.fillColor,
            width: _vm.size,
            height: _vm.size,
            viewBox: "0 0 24 24",
          },
        },
        [
          _c(
            "path",
            {
              attrs: {
                d: "M13,9H11V7H13M13,17H11V11H13M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2Z",
              },
            },
            [_vm.title ? _c("title", [_vm._v(_vm._s(_vm.title))]) : _vm._e()]
          ),
        ]
      ),
    ]
  )
}
var staticRenderFns = []
render._withStripped = true



/***/ }),

/***/ "./node_modules/@nextcloud/vue/dist/Components/NcChip.mjs":
/*!****************************************************************!*\
  !*** ./node_modules/@nextcloud/vue/dist/Components/NcChip.mjs ***!
  \****************************************************************/
/***/ ((__unused_webpack___webpack_module__, __webpack_exports__, __webpack_require__) => {

__webpack_require__.r(__webpack_exports__);
/* harmony export */ __webpack_require__.d(__webpack_exports__, {
/* harmony export */   "default": () => (/* binding */ NcChip)
/* harmony export */ });
/* harmony import */ var _assets_NcChip_CEKw1zaK_css__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../assets/NcChip-CEKw1zaK.css */ "./node_modules/@nextcloud/vue/dist/assets/NcChip-CEKw1zaK.css");
/* harmony import */ var vue__WEBPACK_IMPORTED_MODULE_6__ = __webpack_require__(/*! vue */ "./node_modules/vue/dist/vue.runtime.esm.js");
/* harmony import */ var _chunks_l10n_B4dEPXsr_mjs__WEBPACK_IMPORTED_MODULE_1__ = __webpack_require__(/*! ../chunks/_l10n-B4dEPXsr.mjs */ "./node_modules/@nextcloud/vue/dist/chunks/_l10n-B4dEPXsr.mjs");
/* harmony import */ var _chunks_NcActions_D77YAhAy_mjs__WEBPACK_IMPORTED_MODULE_2__ = __webpack_require__(/*! ../chunks/NcActions-D77YAhAy.mjs */ "./node_modules/@nextcloud/vue/dist/chunks/NcActions-D77YAhAy.mjs");
/* harmony import */ var _NcActionButton_mjs__WEBPACK_IMPORTED_MODULE_3__ = __webpack_require__(/*! ./NcActionButton.mjs */ "./node_modules/@nextcloud/vue/dist/Components/NcActionButton.mjs");
/* harmony import */ var _chunks_NcIconSvgWrapper_DjrkBUkC_mjs__WEBPACK_IMPORTED_MODULE_4__ = __webpack_require__(/*! ../chunks/NcIconSvgWrapper-DjrkBUkC.mjs */ "./node_modules/@nextcloud/vue/dist/chunks/NcIconSvgWrapper-DjrkBUkC.mjs");
/* harmony import */ var _chunks_plugin_vue2_normalizer_DU4iP6Vu_mjs__WEBPACK_IMPORTED_MODULE_5__ = __webpack_require__(/*! ../chunks/_plugin-vue2_normalizer-DU4iP6Vu.mjs */ "./node_modules/@nextcloud/vue/dist/chunks/_plugin-vue2_normalizer-DU4iP6Vu.mjs");







var mdiClose = "M19,6.41L17.59,5L12,10.59L6.41,5L5,6.41L10.59,12L5,17.59L6.41,19L12,13.41L17.59,19L19,17.59L13.41,12L19,6.41Z";
(0,_chunks_l10n_B4dEPXsr_mjs__WEBPACK_IMPORTED_MODULE_1__.r)(_chunks_l10n_B4dEPXsr_mjs__WEBPACK_IMPORTED_MODULE_1__.u);
const _sfc_main = {
  __name: "NcChip",
  props: {
    /**
     * aria label to set on the close button
     * @default 'Close'
     */
    ariaLabelClose: {
      type: String,
      default: (0,_chunks_l10n_B4dEPXsr_mjs__WEBPACK_IMPORTED_MODULE_1__.a)("Close")
    },
    /**
     * Main text of the chip
     */
    text: {
      type: String,
      default: ""
    },
    /**
     * Chip style
     * This sets the background style of the chip, similar to NcButton's `type`
     */
    type: {
      type: String,
      default: "secondary",
      validator: (value) => ["primary", "secondary", "tertiary"].includes(value)
    },
    /**
     * SVG path of the icon to use, this takes precedence over `iconSVG`.
     * For example icon paths from `@mdi/js` can be used.
     */
    iconPath: {
      type: String,
      default: null
    },
    /**
     * Inline SVG to use as the icon
     */
    iconSvg: {
      type: String,
      default: null
    },
    /**
     * Set to true to prevent the close button to be shown
     */
    noClose: {
      type: Boolean,
      default: false
    }
  },
  emits: ["close"],
  setup(__props, { emit }) {
    const props = __props;
    const slots = (0,vue__WEBPACK_IMPORTED_MODULE_6__.useSlots)();
    const canClose = (0,vue__WEBPACK_IMPORTED_MODULE_6__.computed)(() => !props.noClose);
    const hasActions = () => {
      var _a;
      return Boolean((_a = slots.actions) == null ? void 0 : _a.call(slots));
    };
    const hasIcon = () => {
      var _a;
      return Boolean(props.iconPath || props.iconSvg || !!((_a = slots.icon) == null ? void 0 : _a.call(slots)));
    };
    const onClose = () => {
      emit("close");
    };
    return { __sfc: true, props, emit, slots, canClose, hasActions, hasIcon, onClose, mdiClose, NcActions: _chunks_NcActions_D77YAhAy_mjs__WEBPACK_IMPORTED_MODULE_2__.N, NcActionButton: _NcActionButton_mjs__WEBPACK_IMPORTED_MODULE_3__["default"], NcIconSvgWrapper: _chunks_NcIconSvgWrapper_DjrkBUkC_mjs__WEBPACK_IMPORTED_MODULE_4__.N };
  }
};
var _sfc_render = function render() {
  var _vm = this, _c = _vm._self._c, _setup = _vm._self._setupProxy;
  return _c("div", { staticClass: "nc-chip", class: {
    ["nc-chip--".concat(_vm.type)]: true,
    "nc-chip--no-actions": _vm.noClose && !_setup.hasActions(),
    "nc-chip--no-icon": !_setup.hasIcon()
  } }, [_setup.hasIcon() ? _c("span", { staticClass: "nc-chip__icon" }, [_vm._t("icon", function() {
    return [_vm.iconPath || _vm.iconSvg ? _c(_setup.NcIconSvgWrapper, { attrs: { "inline": "", "path": _vm.iconPath, "svg": _vm.iconPath ? void 0 : _vm.iconSvg, "size": 18 } }) : _vm._e()];
  })], 2) : _vm._e(), _c("span", { staticClass: "nc-chip__text" }, [_vm._t("default", function() {
    return [_vm._v(_vm._s(_vm.text))];
  })], 2), _setup.canClose || _setup.hasActions() ? _c(_setup.NcActions, { staticClass: "nc-chip__actions", attrs: { "force-menu": !_setup.canClose, "type": "tertiary-no-background" } }, [_setup.canClose ? _c(_setup.NcActionButton, { attrs: { "close-after-click": "" }, on: { "click": _setup.onClose }, scopedSlots: _vm._u([{ key: "icon", fn: function() {
    return [_c(_setup.NcIconSvgWrapper, { attrs: { "path": _setup.mdiClose, "size": 20 } })];
  }, proxy: true }], null, false, 2547223506) }, [_vm._v(" " + _vm._s(_vm.ariaLabelClose) + " ")]) : _vm._e(), _vm._t("actions")], 2) : _vm._e()], 1);
};
var _sfc_staticRenderFns = [];
var __component__ = /* @__PURE__ */ (0,_chunks_plugin_vue2_normalizer_DU4iP6Vu_mjs__WEBPACK_IMPORTED_MODULE_5__.n)(
  _sfc_main,
  _sfc_render,
  _sfc_staticRenderFns,
  false,
  null,
  "470fc02d"
);
const NcChip = __component__.exports;



/***/ })

}]);
//# sourceMappingURL=apps_files_sharing_src_components_NewFileRequestDialog_vue-apps_files_sharing_src_components_NewFileRequestDialog_vue.js.map?v=3a437f6c9e797d2877b8