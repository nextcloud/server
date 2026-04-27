const appName = "nextcloud-ui";
const appVersion = "1.0.0";
import { r as resolveComponent, o as openBlock, f as createElementBlock, g as createBaseVNode, x as createVNode, t as toDisplayString, w as withCtx, j as createTextVNode, M as withModifiers, F as Fragment, C as renderList, c as createBlock, h as createCommentVNode, b as defineComponent, y as ref, A as onMounted, e as createApp } from "./preload-helper-xAe3EUYB.chunk.mjs";
import { _ as _export_sfc$1, l as loadState, g as getCapabilities } from "./index-o76qk6sn.chunk.mjs";
import { t as translate } from "./translation-DoG5ZELJ-2UfAUX2V.chunk.mjs";
import { N as NcSettingsSection } from "./ContentCopy-C2t0ZTwU.chunk.mjs";
import { d as debounce, c as generateOcsUrl, a as getCurrentUser, g as getLoggerBuilder, h as generateRemoteUrl, e as getRequestToken, o as onRequestTokenUpdate } from "./index-rAufP352.chunk.mjs";
import { c as cancelableClient } from "./index-D5H5XMHa.chunk.mjs";
import { c as showSuccess, a as showError } from "./index-C1xmmKTZ-kBgT3zMc.chunk.mjs";
import { S as ShareType } from "./ShareType-Cy_lTCmc.chunk.mjs";
import { N as NcButton } from "./ArrowRight-BC77f5L9.chunk.mjs";
import { N as NcDateTimePickerNative } from "./index-CZV8rpGu.chunk.mjs";
import { N as NcSelectUsers, a as NcTextArea } from "./NcTextArea-CWA3KOiC-Cpgesyiv.chunk.mjs";
import { _ as _sfc_main$4 } from "./NcTextField.vue_vue_type_script_setup_true_lang-BxkYy7wv-Beu3Njvy.chunk.mjs";
import { l as logger$1 } from "./logger-DbM6EgbB.chunk.mjs";
import "./PencilOutline-BMYBdzdS.chunk.mjs";
import "./Web-BOM4en5n.chunk.mjs";
import "./NcDateTime.vue_vue_type_script_setup_true_lang-BhB8yA4U-DXOBfuGJ.chunk.mjs";
import { k as getFirstDay } from "./NcModal-DHryP_87-Da4dXMUU.chunk.mjs";
/* empty css                                           */
import "./NcAvatar-C9d7Wrc8-C3hNgT1e.chunk.mjs";
import "./NcContent-O-bMKi-3-CUJgW_Xf.chunk.mjs";
import { D as Delete } from "./TrashCanOutline-DgEtyFGH.chunk.mjs";
import { N as NcCheckboxRadioSwitch } from "./NcCheckboxRadioSwitch-BMsPx74L-BYrlnEKO.chunk.mjs";
import { P as PlusIcon } from "./Plus-DuSPdibD.chunk.mjs";
import "./index-DD39fp6M.chunk.mjs";
import "./TrayArrowDown-DVjUGg6-.chunk.mjs";
import "./index-BcMnKoRR.chunk.mjs";
import "./NcDialog-BG9t4Psg-IlLJVAz0.chunk.mjs";
import "./NcSelect-DLheQ2yp-DVxJhLyT.chunk.mjs";
import "./NcEmojiPicker-Djc9a0gw-F1kmncT2.chunk.mjs";
import "./NcEmptyContent-B8-90BSI-BIPGjjpl.chunk.mjs";
import "./index-D5BR15En.chunk.mjs";
/* empty css                                        */
import "./NcInputField-o5OFv3z6-D-7orWgm.chunk.mjs";
import "./mdi-BGU2G5q5.chunk.mjs";
import "./NcPasswordField-uaMO2pdt-DjVmarEi.chunk.mjs";
import "./index-gwTr8m4i.chunk.mjs";
import "./NcSelectTags-CTHyuMcq-2HejGZhj.chunk.mjs";
import "./NcUserBubble-vOAXLHB5-C3lGURBU.chunk.mjs";
import "./NcUserStatusIcon-XiwrgeCm-DTG2zfIi.chunk.mjs";
import "./emoji-BY_D0V5K-BlCul1cD.chunk.mjs";
import "./colors-BHGKZFDI-C0-WujoK.chunk.mjs";
import { l as lr, K as Ke } from "./index-595Vk4Ec.chunk.mjs";
import "./util-BSOXDoOW.chunk.mjs";
function formatDateAsYMD(date) {
  const year = date.getFullYear();
  const month = (date.getMonth() + 1).toString().padStart(2, "0");
  const day = date.getDate().toString().padStart(2, "0");
  return `${year}-${month}-${day}`;
}
const _sfc_main$3 = {
  name: "AbsenceForm",
  components: {
    NcButton,
    NcTextField: _sfc_main$4,
    NcTextArea,
    NcDateTimePickerNative,
    NcSelectUsers
  },
  setup() {
    return { t: translate };
  },
  data() {
    const { firstDay, lastDay, status, message, replacementUserId, replacementUserDisplayName } = loadState("dav", "absence", {});
    return {
      loading: false,
      status: status ?? "",
      message: message ?? "",
      firstDay: firstDay ? new Date(firstDay) : /* @__PURE__ */ new Date(),
      lastDay: lastDay ? new Date(lastDay) : null,
      replacementUserId,
      replacementUser: replacementUserId ? { user: replacementUserId, displayName: replacementUserDisplayName } : null,
      searchLoading: false,
      options: []
    };
  },
  computed: {
    /**
     * @return {boolean}
     */
    valid() {
      const firstDay = new Date(this.firstDay?.getTime());
      const lastDay = new Date(this.lastDay?.getTime());
      firstDay?.setHours(0, 0, 0, 0);
      lastDay?.setHours(0, 0, 0, 0);
      return !!this.firstDay && !!this.lastDay && !!this.status && !!this.message && lastDay >= firstDay;
    }
  },
  methods: {
    resetForm() {
      this.status = "";
      this.message = "";
      this.firstDay = /* @__PURE__ */ new Date();
      this.lastDay = null;
    },
    /**
     * Format shares for the multiselect options
     *
     * @param {object} result select entry item
     * @return {object}
     */
    formatForMultiselect(result) {
      return {
        user: result.uuid || result.value.shareWith,
        displayName: result.name || result.label,
        subtitle: result.dsc | ""
      };
    },
    async asyncFind(query) {
      this.searchLoading = true;
      await this.debounceGetSuggestions(query.trim());
    },
    /**
     * Get suggestions
     *
     * @param {string} search the search query
     */
    async getSuggestions(search) {
      const shareType = [
        ShareType.User
      ];
      let request;
      try {
        request = await cancelableClient.get(generateOcsUrl("apps/files_sharing/api/v1/sharees"), {
          params: {
            format: "json",
            itemType: "file",
            search,
            shareType
          }
        });
      } catch (error) {
        logger$1.error("Error fetching suggestions", { error });
        return;
      }
      const data = request.data.ocs.data;
      const exact = request.data.ocs.data.exact;
      data.exact = [];
      const rawExactSuggestions = exact.users;
      const rawSuggestions = data.users;
      logger$1.info("AbsenceForm raw suggestions", { rawExactSuggestions, rawSuggestions });
      const exactSuggestions = rawExactSuggestions.map((share) => this.formatForMultiselect(share));
      const suggestions = rawSuggestions.map((share) => this.formatForMultiselect(share));
      const allSuggestions = exactSuggestions.concat(suggestions);
      const nameCounts = allSuggestions.reduce((nameCounts2, result) => {
        if (!result.displayName) {
          return nameCounts2;
        }
        if (!nameCounts2[result.displayName]) {
          nameCounts2[result.displayName] = 0;
        }
        nameCounts2[result.displayName]++;
        return nameCounts2;
      }, {});
      this.options = allSuggestions.map((item) => {
        if (nameCounts[item.displayName] > 1 && !item.desc) {
          return { ...item, desc: item.shareWithDisplayNameUnique };
        }
        return item;
      });
      this.searchLoading = false;
      logger$1.info("AbsenseForm suggestions", { options: this.options });
    },
    /**
     * Debounce getSuggestions
     *
     * @param {[string]} args - The arguments
     */
    debounceGetSuggestions: debounce(function(...args) {
      this.getSuggestions(...args);
    }, 300),
    async saveForm() {
      if (!this.valid) {
        return;
      }
      this.loading = true;
      try {
        await cancelableClient.post(generateOcsUrl("/apps/dav/api/v1/outOfOffice/{userId}", { userId: getCurrentUser().uid }), {
          firstDay: formatDateAsYMD(this.firstDay),
          lastDay: formatDateAsYMD(this.lastDay),
          status: this.status,
          message: this.message,
          replacementUserId: this.replacementUser?.user ?? null
        });
        showSuccess(translate("dav", "Absence saved"));
      } catch (error) {
        showError(translate("dav", "Failed to save your absence settings"));
        logger$1.error("Could not save absence", { error });
      } finally {
        this.loading = false;
      }
    },
    async clearAbsence() {
      this.loading = true;
      try {
        await cancelableClient.delete(generateOcsUrl("/apps/dav/api/v1/outOfOffice/{userId}", { userId: getCurrentUser().uid }));
        this.resetForm();
        showSuccess(translate("dav", "Absence cleared"));
      } catch (error) {
        showError(translate("dav", "Failed to clear your absence settings"));
        logger$1.error("Could not clear absence", { error });
      } finally {
        this.loading = false;
      }
    }
  }
};
const _hoisted_1$1 = { class: "absence__dates" };
const _hoisted_2$1 = { for: "replacement-search-input" };
const _hoisted_3$1 = { class: "absence__buttons" };
function _sfc_render$3(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_NcDateTimePickerNative = resolveComponent("NcDateTimePickerNative");
  const _component_NcSelectUsers = resolveComponent("NcSelectUsers");
  const _component_NcTextField = resolveComponent("NcTextField");
  const _component_NcTextArea = resolveComponent("NcTextArea");
  const _component_NcButton = resolveComponent("NcButton");
  return openBlock(), createElementBlock(
    "form",
    {
      class: "absence",
      onSubmit: _cache[5] || (_cache[5] = withModifiers((...args) => $options.saveForm && $options.saveForm(...args), ["prevent"]))
    },
    [
      createBaseVNode("div", _hoisted_1$1, [
        createVNode(_component_NcDateTimePickerNative, {
          id: "absence-first-day",
          modelValue: $data.firstDay,
          "onUpdate:modelValue": _cache[0] || (_cache[0] = ($event) => $data.firstDay = $event),
          label: $setup.t("dav", "First day"),
          class: "absence__dates__picker",
          required: true
        }, null, 8, ["modelValue", "label"]),
        createVNode(_component_NcDateTimePickerNative, {
          id: "absence-last-day",
          modelValue: $data.lastDay,
          "onUpdate:modelValue": _cache[1] || (_cache[1] = ($event) => $data.lastDay = $event),
          label: $setup.t("dav", "Last day (inclusive)"),
          class: "absence__dates__picker",
          required: true
        }, null, 8, ["modelValue", "label"])
      ]),
      createBaseVNode(
        "label",
        _hoisted_2$1,
        toDisplayString($setup.t("dav", "Out of office replacement (optional)")),
        1
        /* TEXT */
      ),
      createVNode(_component_NcSelectUsers, {
        modelValue: $data.replacementUser,
        "onUpdate:modelValue": _cache[2] || (_cache[2] = ($event) => $data.replacementUser = $event),
        inputId: "replacement-search-input",
        loading: $data.searchLoading,
        placeholder: $setup.t("dav", "Name of the replacement"),
        options: $data.options,
        onSearch: $options.asyncFind
      }, null, 8, ["modelValue", "loading", "placeholder", "options", "onSearch"]),
      createVNode(_component_NcTextField, {
        modelValue: $data.status,
        "onUpdate:modelValue": _cache[3] || (_cache[3] = ($event) => $data.status = $event),
        label: $setup.t("dav", "Short absence status"),
        required: true
      }, null, 8, ["modelValue", "label"]),
      createVNode(_component_NcTextArea, {
        modelValue: $data.message,
        "onUpdate:modelValue": _cache[4] || (_cache[4] = ($event) => $data.message = $event),
        label: $setup.t("dav", "Long absence Message"),
        required: true
      }, null, 8, ["modelValue", "label"]),
      createBaseVNode("div", _hoisted_3$1, [
        createVNode(_component_NcButton, {
          disabled: $data.loading || !$options.valid,
          variant: "primary",
          type: "submit"
        }, {
          default: withCtx(() => [
            createTextVNode(
              toDisplayString($setup.t("dav", "Save")),
              1
              /* TEXT */
            )
          ]),
          _: 1
          /* STABLE */
        }, 8, ["disabled"]),
        createVNode(_component_NcButton, {
          disabled: $data.loading || !$options.valid,
          variant: "error",
          onClick: $options.clearAbsence
        }, {
          default: withCtx(() => [
            createTextVNode(
              toDisplayString($setup.t("dav", "Disable absence")),
              1
              /* TEXT */
            )
          ]),
          _: 1
          /* STABLE */
        }, 8, ["disabled", "onClick"])
      ])
    ],
    32
    /* NEED_HYDRATION */
  );
}
const AbsenceForm = /* @__PURE__ */ _export_sfc$1(_sfc_main$3, [["render", _sfc_render$3], ["__scopeId", "data-v-b606652e"], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/build/frontend/apps/dav/src/components/AbsenceForm.vue"]]);
var dist = {};
var zones$1 = {};
var hasRequiredZones;
function requireZones() {
  if (hasRequiredZones) return zones$1;
  hasRequiredZones = 1;
  Object.defineProperty(zones$1, "__esModule", { value: true });
  zones$1.zonesMap = zones$1.defaultStart = void 0;
  zones$1.defaultStart = "19700101T000000";
  zones$1.zonesMap = /* @__PURE__ */ new Map([
    [
      "Africa/Abidjan",
      {
        "s": {
          "f": "+0000",
          "n": "GMT"
        }
      }
    ],
    [
      "Africa/Accra",
      {
        "s": {
          "f": "+0000",
          "n": "GMT"
        }
      }
    ],
    [
      "Africa/Addis_Ababa",
      {
        "s": {
          "f": "+0300",
          "n": "EAT"
        }
      }
    ],
    [
      "Africa/Algiers",
      {
        "s": {
          "f": "+0100",
          "n": "CET"
        }
      }
    ],
    [
      "Africa/Asmara",
      {
        "s": {
          "f": "+0300",
          "n": "EAT"
        }
      }
    ],
    [
      "Africa/Bamako",
      {
        "s": {
          "f": "+0000",
          "n": "GMT"
        }
      }
    ],
    [
      "Africa/Bangui",
      {
        "s": {
          "f": "+0100",
          "n": "WAT"
        }
      }
    ],
    [
      "Africa/Banjul",
      {
        "s": {
          "f": "+0000",
          "n": "GMT"
        }
      }
    ],
    [
      "Africa/Bissau",
      {
        "s": {
          "f": "+0000",
          "n": "GMT"
        }
      }
    ],
    [
      "Africa/Blantyre",
      {
        "s": {
          "f": "+0200",
          "n": "CAT"
        }
      }
    ],
    [
      "Africa/Brazzaville",
      {
        "s": {
          "f": "+0100",
          "n": "WAT"
        }
      }
    ],
    [
      "Africa/Bujumbura",
      {
        "s": {
          "f": "+0200",
          "n": "CAT"
        }
      }
    ],
    [
      "Africa/Cairo",
      {
        "s": {
          "f": "+0200",
          "n": "EET"
        }
      }
    ],
    [
      "Africa/Casablanca",
      {
        "s": {
          "f": "+0100",
          "n": "+01"
        }
      }
    ],
    [
      "Africa/Ceuta",
      {
        "s": {
          "f": "+0200",
          "t": "+0100",
          "n": "CET",
          "s": "19701025T030000",
          "r": {
            "m": 10,
            "d": "-1SU"
          }
        },
        "d": {
          "f": "+0100",
          "t": "+0200",
          "n": "CEST",
          "s": "19700329T020000",
          "r": {
            "m": 3,
            "d": "-1SU"
          }
        }
      }
    ],
    [
      "Africa/Conakry",
      {
        "s": {
          "f": "+0000",
          "n": "GMT"
        }
      }
    ],
    [
      "Africa/Dakar",
      {
        "s": {
          "f": "+0000",
          "n": "GMT"
        }
      }
    ],
    [
      "Africa/Dar_es_Salaam",
      {
        "s": {
          "f": "+0300",
          "n": "EAT"
        }
      }
    ],
    [
      "Africa/Djibouti",
      {
        "s": {
          "f": "+0300",
          "n": "EAT"
        }
      }
    ],
    [
      "Africa/Douala",
      {
        "s": {
          "f": "+0100",
          "n": "WAT"
        }
      }
    ],
    [
      "Africa/El_Aaiun",
      {
        "s": {
          "f": "+0100",
          "n": "+01"
        }
      }
    ],
    [
      "Africa/Freetown",
      {
        "s": {
          "f": "+0000",
          "n": "GMT"
        }
      }
    ],
    [
      "Africa/Gaborone",
      {
        "s": {
          "f": "+0200",
          "n": "CAT"
        }
      }
    ],
    [
      "Africa/Harare",
      {
        "s": {
          "f": "+0200",
          "n": "CAT"
        }
      }
    ],
    [
      "Africa/Johannesburg",
      {
        "s": {
          "f": "+0200",
          "n": "SAST"
        }
      }
    ],
    [
      "Africa/Juba",
      {
        "s": {
          "f": "+0300",
          "n": "EAT"
        }
      }
    ],
    [
      "Africa/Kampala",
      {
        "s": {
          "f": "+0300",
          "n": "EAT"
        }
      }
    ],
    [
      "Africa/Khartoum",
      {
        "s": {
          "f": "+0200",
          "n": "CAT"
        }
      }
    ],
    [
      "Africa/Kigali",
      {
        "s": {
          "f": "+0200",
          "n": "CAT"
        }
      }
    ],
    [
      "Africa/Kinshasa",
      {
        "s": {
          "f": "+0100",
          "n": "WAT"
        }
      }
    ],
    [
      "Africa/Lagos",
      {
        "s": {
          "f": "+0100",
          "n": "WAT"
        }
      }
    ],
    [
      "Africa/Libreville",
      {
        "s": {
          "f": "+0100",
          "n": "WAT"
        }
      }
    ],
    [
      "Africa/Lome",
      {
        "s": {
          "f": "+0000",
          "n": "GMT"
        }
      }
    ],
    [
      "Africa/Luanda",
      {
        "s": {
          "f": "+0100",
          "n": "WAT"
        }
      }
    ],
    [
      "Africa/Lubumbashi",
      {
        "s": {
          "f": "+0200",
          "n": "CAT"
        }
      }
    ],
    [
      "Africa/Lusaka",
      {
        "s": {
          "f": "+0200",
          "n": "CAT"
        }
      }
    ],
    [
      "Africa/Malabo",
      {
        "s": {
          "f": "+0100",
          "n": "WAT"
        }
      }
    ],
    [
      "Africa/Maputo",
      {
        "s": {
          "f": "+0200",
          "n": "CAT"
        }
      }
    ],
    [
      "Africa/Maseru",
      {
        "s": {
          "f": "+0200",
          "n": "SAST"
        }
      }
    ],
    [
      "Africa/Mbabane",
      {
        "s": {
          "f": "+0200",
          "n": "SAST"
        }
      }
    ],
    [
      "Africa/Mogadishu",
      {
        "s": {
          "f": "+0300",
          "n": "EAT"
        }
      }
    ],
    [
      "Africa/Monrovia",
      {
        "s": {
          "f": "+0000",
          "n": "GMT"
        }
      }
    ],
    [
      "Africa/Nairobi",
      {
        "s": {
          "f": "+0300",
          "n": "EAT"
        }
      }
    ],
    [
      "Africa/Ndjamena",
      {
        "s": {
          "f": "+0100",
          "n": "WAT"
        }
      }
    ],
    [
      "Africa/Niamey",
      {
        "s": {
          "f": "+0100",
          "n": "WAT"
        }
      }
    ],
    [
      "Africa/Nouakchott",
      {
        "s": {
          "f": "+0000",
          "n": "GMT"
        }
      }
    ],
    [
      "Africa/Ouagadougou",
      {
        "s": {
          "f": "+0000",
          "n": "GMT"
        }
      }
    ],
    [
      "Africa/Porto-Novo",
      {
        "s": {
          "f": "+0100",
          "n": "WAT"
        }
      }
    ],
    [
      "Africa/Sao_Tome",
      {
        "s": {
          "f": "+0100",
          "n": "WAT"
        }
      }
    ],
    [
      "Africa/Tripoli",
      {
        "s": {
          "f": "+0200",
          "n": "EET"
        }
      }
    ],
    [
      "Africa/Tunis",
      {
        "s": {
          "f": "+0100",
          "n": "CET"
        }
      }
    ],
    [
      "Africa/Windhoek",
      {
        "s": {
          "f": "+0200",
          "n": "CAT"
        }
      }
    ],
    [
      "America/Adak",
      {
        "s": {
          "f": "-0900",
          "t": "-1000",
          "n": "HST",
          "s": "19701101T020000",
          "r": {
            "m": 11,
            "d": "1SU"
          }
        },
        "d": {
          "f": "-1000",
          "t": "-0900",
          "n": "HDT",
          "s": "19700308T020000",
          "r": {
            "m": 3,
            "d": "2SU"
          }
        }
      }
    ],
    [
      "America/Anchorage",
      {
        "s": {
          "f": "-0800",
          "t": "-0900",
          "n": "AKST",
          "s": "19701101T020000",
          "r": {
            "m": 11,
            "d": "1SU"
          }
        },
        "d": {
          "f": "-0900",
          "t": "-0800",
          "n": "AKDT",
          "s": "19700308T020000",
          "r": {
            "m": 3,
            "d": "2SU"
          }
        }
      }
    ],
    [
      "America/Anguilla",
      {
        "s": {
          "f": "-0400",
          "n": "AST"
        }
      }
    ],
    [
      "America/Antigua",
      {
        "s": {
          "f": "-0400",
          "n": "AST"
        }
      }
    ],
    [
      "America/Araguaina",
      {
        "s": {
          "f": "-0300",
          "n": "-03"
        }
      }
    ],
    [
      "America/Argentina/Buenos_Aires",
      {
        "s": {
          "f": "-0300",
          "n": "-03"
        }
      }
    ],
    [
      "America/Argentina/Catamarca",
      {
        "s": {
          "f": "-0300",
          "n": "-03"
        }
      }
    ],
    [
      "America/Argentina/Cordoba",
      {
        "s": {
          "f": "-0300",
          "n": "-03"
        }
      }
    ],
    [
      "America/Argentina/Jujuy",
      {
        "s": {
          "f": "-0300",
          "n": "-03"
        }
      }
    ],
    [
      "America/Argentina/La_Rioja",
      {
        "s": {
          "f": "-0300",
          "n": "-03"
        }
      }
    ],
    [
      "America/Argentina/Mendoza",
      {
        "s": {
          "f": "-0300",
          "n": "-03"
        }
      }
    ],
    [
      "America/Argentina/Rio_Gallegos",
      {
        "s": {
          "f": "-0300",
          "n": "-03"
        }
      }
    ],
    [
      "America/Argentina/Salta",
      {
        "s": {
          "f": "-0300",
          "n": "-03"
        }
      }
    ],
    [
      "America/Argentina/San_Juan",
      {
        "s": {
          "f": "-0300",
          "n": "-03"
        }
      }
    ],
    [
      "America/Argentina/San_Luis",
      {
        "s": {
          "f": "-0300",
          "n": "-03"
        }
      }
    ],
    [
      "America/Argentina/Tucuman",
      {
        "s": {
          "f": "-0300",
          "n": "-03"
        }
      }
    ],
    [
      "America/Argentina/Ushuaia",
      {
        "s": {
          "f": "-0300",
          "n": "-03"
        }
      }
    ],
    [
      "America/Aruba",
      {
        "s": {
          "f": "-0400",
          "n": "AST"
        }
      }
    ],
    [
      "America/Asuncion",
      {
        "s": {
          "f": "-0300",
          "t": "-0400",
          "n": "-04",
          "s": "19700322T000000",
          "r": {
            "m": 3,
            "d": "4SU"
          }
        },
        "d": {
          "f": "-0400",
          "t": "-0300",
          "n": "-03",
          "s": "19701004T000000",
          "r": {
            "m": 10,
            "d": "1SU"
          }
        }
      }
    ],
    [
      "America/Atikokan",
      {
        "s": {
          "f": "-0500",
          "n": "EST"
        }
      }
    ],
    [
      "America/Bahia_Banderas",
      {
        "s": {
          "f": "-0500",
          "t": "-0600",
          "n": "CST",
          "s": "19701025T020000",
          "r": {
            "m": 10,
            "d": "-1SU"
          }
        },
        "d": {
          "f": "-0600",
          "t": "-0500",
          "n": "CDT",
          "s": "19700405T020000",
          "r": {
            "m": 4,
            "d": "1SU"
          }
        }
      }
    ],
    [
      "America/Bahia",
      {
        "s": {
          "f": "-0300",
          "n": "-03"
        }
      }
    ],
    [
      "America/Barbados",
      {
        "s": {
          "f": "-0400",
          "n": "AST"
        }
      }
    ],
    [
      "America/Belem",
      {
        "s": {
          "f": "-0300",
          "n": "-03"
        }
      }
    ],
    [
      "America/Belize",
      {
        "s": {
          "f": "-0600",
          "n": "CST"
        }
      }
    ],
    [
      "America/Blanc-Sablon",
      {
        "s": {
          "f": "-0400",
          "n": "AST"
        }
      }
    ],
    [
      "America/Boa_Vista",
      {
        "s": {
          "f": "-0400",
          "n": "-04"
        }
      }
    ],
    [
      "America/Bogota",
      {
        "s": {
          "f": "-0500",
          "n": "-05"
        }
      }
    ],
    [
      "America/Boise",
      {
        "s": {
          "f": "-0600",
          "t": "-0700",
          "n": "MST",
          "s": "19701101T020000",
          "r": {
            "m": 11,
            "d": "1SU"
          }
        },
        "d": {
          "f": "-0700",
          "t": "-0600",
          "n": "MDT",
          "s": "19700308T020000",
          "r": {
            "m": 3,
            "d": "2SU"
          }
        }
      }
    ],
    [
      "America/Cambridge_Bay",
      {
        "s": {
          "f": "-0600",
          "t": "-0700",
          "n": "MST",
          "s": "19701101T020000",
          "r": {
            "m": 11,
            "d": "1SU"
          }
        },
        "d": {
          "f": "-0700",
          "t": "-0600",
          "n": "MDT",
          "s": "19700308T020000",
          "r": {
            "m": 3,
            "d": "2SU"
          }
        }
      }
    ],
    [
      "America/Campo_Grande",
      {
        "s": {
          "f": "-0400",
          "n": "-04",
          "s": "19700215T000000",
          "r": {
            "m": 2,
            "d": "3SU"
          }
        },
        "d": {
          "f": "-0400",
          "t": "-0300",
          "n": "-03",
          "s": "19701101T000000",
          "r": {
            "m": 11,
            "d": "1SU"
          }
        }
      }
    ],
    [
      "America/Cancun",
      {
        "s": {
          "f": "-0500",
          "n": "EST"
        }
      }
    ],
    [
      "America/Caracas",
      {
        "s": {
          "f": "-0400",
          "n": "-04"
        }
      }
    ],
    [
      "America/Cayenne",
      {
        "s": {
          "f": "-0300",
          "n": "-03"
        }
      }
    ],
    [
      "America/Cayman",
      {
        "s": {
          "f": "-0500",
          "n": "EST"
        }
      }
    ],
    [
      "America/Chicago",
      {
        "s": {
          "f": "-0500",
          "t": "-0600",
          "n": "CST",
          "s": "19701101T020000",
          "r": {
            "m": 11,
            "d": "1SU"
          }
        },
        "d": {
          "f": "-0600",
          "t": "-0500",
          "n": "CDT",
          "s": "19700308T020000",
          "r": {
            "m": 3,
            "d": "2SU"
          }
        }
      }
    ],
    [
      "America/Chihuahua",
      {
        "s": {
          "f": "-0600",
          "t": "-0700",
          "n": "MST",
          "s": "19701025T020000",
          "r": {
            "m": 10,
            "d": "-1SU"
          }
        },
        "d": {
          "f": "-0700",
          "t": "-0600",
          "n": "MDT",
          "s": "19700405T020000",
          "r": {
            "m": 4,
            "d": "1SU"
          }
        }
      }
    ],
    [
      "America/Costa_Rica",
      {
        "s": {
          "f": "-0600",
          "n": "CST"
        }
      }
    ],
    [
      "America/Creston",
      {
        "s": {
          "f": "-0700",
          "n": "MST"
        }
      }
    ],
    [
      "America/Cuiaba",
      {
        "s": {
          "f": "-0400",
          "n": "-04",
          "s": "19700215T000000",
          "r": {
            "m": 2,
            "d": "3SU"
          }
        },
        "d": {
          "f": "-0400",
          "t": "-0300",
          "n": "-03",
          "s": "19701101T000000",
          "r": {
            "m": 11,
            "d": "1SU"
          }
        }
      }
    ],
    [
      "America/Curacao",
      {
        "s": {
          "f": "-0400",
          "n": "AST"
        }
      }
    ],
    [
      "America/Danmarkshavn",
      {
        "s": {
          "f": "+0000",
          "n": "GMT"
        }
      }
    ],
    [
      "America/Dawson_Creek",
      {
        "s": {
          "f": "-0700",
          "n": "MST"
        }
      }
    ],
    [
      "America/Dawson",
      {
        "s": {
          "f": "-0700",
          "t": "-0800",
          "n": "PST",
          "s": "19701101T020000",
          "r": {
            "m": 11,
            "d": "1SU"
          }
        },
        "d": {
          "f": "-0800",
          "t": "-0700",
          "n": "PDT",
          "s": "19700308T020000",
          "r": {
            "m": 3,
            "d": "2SU"
          }
        }
      }
    ],
    [
      "America/Denver",
      {
        "s": {
          "f": "-0600",
          "t": "-0700",
          "n": "MST",
          "s": "19701101T020000",
          "r": {
            "m": 11,
            "d": "1SU"
          }
        },
        "d": {
          "f": "-0700",
          "t": "-0600",
          "n": "MDT",
          "s": "19700308T020000",
          "r": {
            "m": 3,
            "d": "2SU"
          }
        }
      }
    ],
    [
      "America/Detroit",
      {
        "s": {
          "f": "-0400",
          "t": "-0500",
          "n": "EST",
          "s": "19701101T020000",
          "r": {
            "m": 11,
            "d": "1SU"
          }
        },
        "d": {
          "f": "-0500",
          "t": "-0400",
          "n": "EDT",
          "s": "19700308T020000",
          "r": {
            "m": 3,
            "d": "2SU"
          }
        }
      }
    ],
    [
      "America/Dominica",
      {
        "s": {
          "f": "-0400",
          "n": "AST"
        }
      }
    ],
    [
      "America/Edmonton",
      {
        "s": {
          "f": "-0600",
          "t": "-0700",
          "n": "MST",
          "s": "19701101T020000",
          "r": {
            "m": 11,
            "d": "1SU"
          }
        },
        "d": {
          "f": "-0700",
          "t": "-0600",
          "n": "MDT",
          "s": "19700308T020000",
          "r": {
            "m": 3,
            "d": "2SU"
          }
        }
      }
    ],
    [
      "America/Eirunepe",
      {
        "s": {
          "f": "-0500",
          "n": "-05"
        }
      }
    ],
    [
      "America/El_Salvador",
      {
        "s": {
          "f": "-0600",
          "n": "CST"
        }
      }
    ],
    [
      "America/Fort_Nelson",
      {
        "s": {
          "f": "-0700",
          "n": "MST"
        }
      }
    ],
    [
      "America/Fortaleza",
      {
        "s": {
          "f": "-0300",
          "n": "-03"
        }
      }
    ],
    [
      "America/Glace_Bay",
      {
        "s": {
          "f": "-0300",
          "t": "-0400",
          "n": "AST",
          "s": "19701101T020000",
          "r": {
            "m": 11,
            "d": "1SU"
          }
        },
        "d": {
          "f": "-0400",
          "t": "-0300",
          "n": "ADT",
          "s": "19700308T020000",
          "r": {
            "m": 3,
            "d": "2SU"
          }
        }
      }
    ],
    [
      "America/Godthab",
      {
        "s": {
          "f": "-0200",
          "t": "-0300",
          "n": "-03",
          "s": "19701024T230000",
          "r": {
            "m": 10,
            "d": "-1SA"
          }
        },
        "d": {
          "f": "-0300",
          "t": "-0200",
          "n": "-02",
          "s": "19700328T220000",
          "r": {
            "m": 3,
            "d": "-1SA"
          }
        }
      }
    ],
    [
      "America/Goose_Bay",
      {
        "s": {
          "f": "-0300",
          "t": "-0400",
          "n": "AST",
          "s": "19701101T020000",
          "r": {
            "m": 11,
            "d": "1SU"
          }
        },
        "d": {
          "f": "-0400",
          "t": "-0300",
          "n": "ADT",
          "s": "19700308T020000",
          "r": {
            "m": 3,
            "d": "2SU"
          }
        }
      }
    ],
    [
      "America/Grand_Turk",
      {
        "s": {
          "f": "-0400",
          "t": "-0500",
          "n": "EST",
          "s": "19701101T020000",
          "r": {
            "m": 11,
            "d": "1SU"
          }
        },
        "d": {
          "f": "-0500",
          "t": "-0400",
          "n": "EDT",
          "s": "19700308T020000",
          "r": {
            "m": 3,
            "d": "2SU"
          }
        }
      }
    ],
    [
      "America/Grenada",
      {
        "s": {
          "f": "-0400",
          "n": "AST"
        }
      }
    ],
    [
      "America/Guadeloupe",
      {
        "s": {
          "f": "-0400",
          "n": "AST"
        }
      }
    ],
    [
      "America/Guatemala",
      {
        "s": {
          "f": "-0600",
          "n": "CST"
        }
      }
    ],
    [
      "America/Guayaquil",
      {
        "s": {
          "f": "-0500",
          "n": "-05"
        }
      }
    ],
    [
      "America/Guyana",
      {
        "s": {
          "f": "-0400",
          "n": "-04"
        }
      }
    ],
    [
      "America/Halifax",
      {
        "s": {
          "f": "-0300",
          "t": "-0400",
          "n": "AST",
          "s": "19701101T020000",
          "r": {
            "m": 11,
            "d": "1SU"
          }
        },
        "d": {
          "f": "-0400",
          "t": "-0300",
          "n": "ADT",
          "s": "19700308T020000",
          "r": {
            "m": 3,
            "d": "2SU"
          }
        }
      }
    ],
    [
      "America/Havana",
      {
        "s": {
          "f": "-0400",
          "t": "-0500",
          "n": "CST",
          "s": "19701101T010000",
          "r": {
            "m": 11,
            "d": "1SU"
          }
        },
        "d": {
          "f": "-0500",
          "t": "-0400",
          "n": "CDT",
          "s": "19700308T000000",
          "r": {
            "m": 3,
            "d": "2SU"
          }
        }
      }
    ],
    [
      "America/Hermosillo",
      {
        "s": {
          "f": "-0700",
          "n": "MST"
        }
      }
    ],
    [
      "America/Indiana/Indianapolis",
      {
        "s": {
          "f": "-0400",
          "t": "-0500",
          "n": "EST",
          "s": "19701101T020000",
          "r": {
            "m": 11,
            "d": "1SU"
          }
        },
        "d": {
          "f": "-0500",
          "t": "-0400",
          "n": "EDT",
          "s": "19700308T020000",
          "r": {
            "m": 3,
            "d": "2SU"
          }
        }
      }
    ],
    [
      "America/Indiana/Knox",
      {
        "s": {
          "f": "-0500",
          "t": "-0600",
          "n": "CST",
          "s": "19701101T020000",
          "r": {
            "m": 11,
            "d": "1SU"
          }
        },
        "d": {
          "f": "-0600",
          "t": "-0500",
          "n": "CDT",
          "s": "19700308T020000",
          "r": {
            "m": 3,
            "d": "2SU"
          }
        }
      }
    ],
    [
      "America/Indiana/Marengo",
      {
        "s": {
          "f": "-0400",
          "t": "-0500",
          "n": "EST",
          "s": "19701101T020000",
          "r": {
            "m": 11,
            "d": "1SU"
          }
        },
        "d": {
          "f": "-0500",
          "t": "-0400",
          "n": "EDT",
          "s": "19700308T020000",
          "r": {
            "m": 3,
            "d": "2SU"
          }
        }
      }
    ],
    [
      "America/Indiana/Petersburg",
      {
        "s": {
          "f": "-0400",
          "t": "-0500",
          "n": "EST",
          "s": "19701101T020000",
          "r": {
            "m": 11,
            "d": "1SU"
          }
        },
        "d": {
          "f": "-0500",
          "t": "-0400",
          "n": "EDT",
          "s": "19700308T020000",
          "r": {
            "m": 3,
            "d": "2SU"
          }
        }
      }
    ],
    [
      "America/Indiana/Tell_City",
      {
        "s": {
          "f": "-0500",
          "t": "-0600",
          "n": "CST",
          "s": "19701101T020000",
          "r": {
            "m": 11,
            "d": "1SU"
          }
        },
        "d": {
          "f": "-0600",
          "t": "-0500",
          "n": "CDT",
          "s": "19700308T020000",
          "r": {
            "m": 3,
            "d": "2SU"
          }
        }
      }
    ],
    [
      "America/Indiana/Vevay",
      {
        "s": {
          "f": "-0400",
          "t": "-0500",
          "n": "EST",
          "s": "19701101T020000",
          "r": {
            "m": 11,
            "d": "1SU"
          }
        },
        "d": {
          "f": "-0500",
          "t": "-0400",
          "n": "EDT",
          "s": "19700308T020000",
          "r": {
            "m": 3,
            "d": "2SU"
          }
        }
      }
    ],
    [
      "America/Indiana/Vincennes",
      {
        "s": {
          "f": "-0400",
          "t": "-0500",
          "n": "EST",
          "s": "19701101T020000",
          "r": {
            "m": 11,
            "d": "1SU"
          }
        },
        "d": {
          "f": "-0500",
          "t": "-0400",
          "n": "EDT",
          "s": "19700308T020000",
          "r": {
            "m": 3,
            "d": "2SU"
          }
        }
      }
    ],
    [
      "America/Indiana/Winamac",
      {
        "s": {
          "f": "-0400",
          "t": "-0500",
          "n": "EST",
          "s": "19701101T020000",
          "r": {
            "m": 11,
            "d": "1SU"
          }
        },
        "d": {
          "f": "-0500",
          "t": "-0400",
          "n": "EDT",
          "s": "19700308T020000",
          "r": {
            "m": 3,
            "d": "2SU"
          }
        }
      }
    ],
    [
      "America/Inuvik",
      {
        "s": {
          "f": "-0600",
          "t": "-0700",
          "n": "MST",
          "s": "19701101T020000",
          "r": {
            "m": 11,
            "d": "1SU"
          }
        },
        "d": {
          "f": "-0700",
          "t": "-0600",
          "n": "MDT",
          "s": "19700308T020000",
          "r": {
            "m": 3,
            "d": "2SU"
          }
        }
      }
    ],
    [
      "America/Iqaluit",
      {
        "s": {
          "f": "-0400",
          "t": "-0500",
          "n": "EST",
          "s": "19701101T020000",
          "r": {
            "m": 11,
            "d": "1SU"
          }
        },
        "d": {
          "f": "-0500",
          "t": "-0400",
          "n": "EDT",
          "s": "19700308T020000",
          "r": {
            "m": 3,
            "d": "2SU"
          }
        }
      }
    ],
    [
      "America/Jamaica",
      {
        "s": {
          "f": "-0500",
          "n": "EST"
        }
      }
    ],
    [
      "America/Juneau",
      {
        "s": {
          "f": "-0800",
          "t": "-0900",
          "n": "AKST",
          "s": "19701101T020000",
          "r": {
            "m": 11,
            "d": "1SU"
          }
        },
        "d": {
          "f": "-0900",
          "t": "-0800",
          "n": "AKDT",
          "s": "19700308T020000",
          "r": {
            "m": 3,
            "d": "2SU"
          }
        }
      }
    ],
    [
      "America/Kentucky/Louisville",
      {
        "s": {
          "f": "-0400",
          "t": "-0500",
          "n": "EST",
          "s": "19701101T020000",
          "r": {
            "m": 11,
            "d": "1SU"
          }
        },
        "d": {
          "f": "-0500",
          "t": "-0400",
          "n": "EDT",
          "s": "19700308T020000",
          "r": {
            "m": 3,
            "d": "2SU"
          }
        }
      }
    ],
    [
      "America/Kentucky/Monticello",
      {
        "s": {
          "f": "-0400",
          "t": "-0500",
          "n": "EST",
          "s": "19701101T020000",
          "r": {
            "m": 11,
            "d": "1SU"
          }
        },
        "d": {
          "f": "-0500",
          "t": "-0400",
          "n": "EDT",
          "s": "19700308T020000",
          "r": {
            "m": 3,
            "d": "2SU"
          }
        }
      }
    ],
    [
      "America/Kralendijk",
      {
        "s": {
          "f": "-0400",
          "n": "AST"
        }
      }
    ],
    [
      "America/La_Paz",
      {
        "s": {
          "f": "-0400",
          "n": "-04"
        }
      }
    ],
    [
      "America/Lima",
      {
        "s": {
          "f": "-0500",
          "n": "-05"
        }
      }
    ],
    [
      "America/Los_Angeles",
      {
        "s": {
          "f": "-0700",
          "t": "-0800",
          "n": "PST",
          "s": "19701101T020000",
          "r": {
            "m": 11,
            "d": "1SU"
          }
        },
        "d": {
          "f": "-0800",
          "t": "-0700",
          "n": "PDT",
          "s": "19700308T020000",
          "r": {
            "m": 3,
            "d": "2SU"
          }
        }
      }
    ],
    [
      "America/Lower_Princes",
      {
        "s": {
          "f": "-0400",
          "n": "AST"
        }
      }
    ],
    [
      "America/Maceio",
      {
        "s": {
          "f": "-0300",
          "n": "-03"
        }
      }
    ],
    [
      "America/Managua",
      {
        "s": {
          "f": "-0600",
          "n": "CST"
        }
      }
    ],
    [
      "America/Manaus",
      {
        "s": {
          "f": "-0400",
          "n": "-04"
        }
      }
    ],
    [
      "America/Marigot",
      {
        "s": {
          "f": "-0400",
          "n": "AST"
        }
      }
    ],
    [
      "America/Martinique",
      {
        "s": {
          "f": "-0400",
          "n": "AST"
        }
      }
    ],
    [
      "America/Matamoros",
      {
        "s": {
          "f": "-0500",
          "t": "-0600",
          "n": "CST",
          "s": "19701101T020000",
          "r": {
            "m": 11,
            "d": "1SU"
          }
        },
        "d": {
          "f": "-0600",
          "t": "-0500",
          "n": "CDT",
          "s": "19700308T020000",
          "r": {
            "m": 3,
            "d": "2SU"
          }
        }
      }
    ],
    [
      "America/Mazatlan",
      {
        "s": {
          "f": "-0600",
          "t": "-0700",
          "n": "MST",
          "s": "19701025T020000",
          "r": {
            "m": 10,
            "d": "-1SU"
          }
        },
        "d": {
          "f": "-0700",
          "t": "-0600",
          "n": "MDT",
          "s": "19700405T020000",
          "r": {
            "m": 4,
            "d": "1SU"
          }
        }
      }
    ],
    [
      "America/Menominee",
      {
        "s": {
          "f": "-0500",
          "t": "-0600",
          "n": "CST",
          "s": "19701101T020000",
          "r": {
            "m": 11,
            "d": "1SU"
          }
        },
        "d": {
          "f": "-0600",
          "t": "-0500",
          "n": "CDT",
          "s": "19700308T020000",
          "r": {
            "m": 3,
            "d": "2SU"
          }
        }
      }
    ],
    [
      "America/Merida",
      {
        "s": {
          "f": "-0500",
          "t": "-0600",
          "n": "CST",
          "s": "19701025T020000",
          "r": {
            "m": 10,
            "d": "-1SU"
          }
        },
        "d": {
          "f": "-0600",
          "t": "-0500",
          "n": "CDT",
          "s": "19700405T020000",
          "r": {
            "m": 4,
            "d": "1SU"
          }
        }
      }
    ],
    [
      "America/Metlakatla",
      {
        "s": {
          "f": "-0800",
          "t": "-0900",
          "n": "AKST",
          "s": "19701101T020000",
          "r": {
            "m": 11,
            "d": "1SU"
          }
        },
        "d": {
          "f": "-0900",
          "t": "-0800",
          "n": "AKDT",
          "s": "19700308T020000",
          "r": {
            "m": 3,
            "d": "2SU"
          }
        }
      }
    ],
    [
      "America/Mexico_City",
      {
        "s": {
          "f": "-0500",
          "t": "-0600",
          "n": "CST",
          "s": "19701025T020000",
          "r": {
            "m": 10,
            "d": "-1SU"
          }
        },
        "d": {
          "f": "-0600",
          "t": "-0500",
          "n": "CDT",
          "s": "19700405T020000",
          "r": {
            "m": 4,
            "d": "1SU"
          }
        }
      }
    ],
    [
      "America/Miquelon",
      {
        "s": {
          "f": "-0200",
          "t": "-0300",
          "n": "-03",
          "s": "19701101T020000",
          "r": {
            "m": 11,
            "d": "1SU"
          }
        },
        "d": {
          "f": "-0300",
          "t": "-0200",
          "n": "-02",
          "s": "19700308T020000",
          "r": {
            "m": 3,
            "d": "2SU"
          }
        }
      }
    ],
    [
      "America/Moncton",
      {
        "s": {
          "f": "-0300",
          "t": "-0400",
          "n": "AST",
          "s": "19701101T020000",
          "r": {
            "m": 11,
            "d": "1SU"
          }
        },
        "d": {
          "f": "-0400",
          "t": "-0300",
          "n": "ADT",
          "s": "19700308T020000",
          "r": {
            "m": 3,
            "d": "2SU"
          }
        }
      }
    ],
    [
      "America/Monterrey",
      {
        "s": {
          "f": "-0500",
          "t": "-0600",
          "n": "CST",
          "s": "19701025T020000",
          "r": {
            "m": 10,
            "d": "-1SU"
          }
        },
        "d": {
          "f": "-0600",
          "t": "-0500",
          "n": "CDT",
          "s": "19700405T020000",
          "r": {
            "m": 4,
            "d": "1SU"
          }
        }
      }
    ],
    [
      "America/Montevideo",
      {
        "s": {
          "f": "-0300",
          "n": "-03"
        }
      }
    ],
    [
      "America/Montserrat",
      {
        "s": {
          "f": "-0400",
          "n": "AST"
        }
      }
    ],
    [
      "America/Nassau",
      {
        "s": {
          "f": "-0400",
          "t": "-0500",
          "n": "EST",
          "s": "19701101T020000",
          "r": {
            "m": 11,
            "d": "1SU"
          }
        },
        "d": {
          "f": "-0500",
          "t": "-0400",
          "n": "EDT",
          "s": "19700308T020000",
          "r": {
            "m": 3,
            "d": "2SU"
          }
        }
      }
    ],
    [
      "America/New_York",
      {
        "s": {
          "f": "-0400",
          "t": "-0500",
          "n": "EST",
          "s": "19701101T020000",
          "r": {
            "m": 11,
            "d": "1SU"
          }
        },
        "d": {
          "f": "-0500",
          "t": "-0400",
          "n": "EDT",
          "s": "19700308T020000",
          "r": {
            "m": 3,
            "d": "2SU"
          }
        }
      }
    ],
    [
      "America/Nipigon",
      {
        "s": {
          "f": "-0400",
          "t": "-0500",
          "n": "EST",
          "s": "19701101T020000",
          "r": {
            "m": 11,
            "d": "1SU"
          }
        },
        "d": {
          "f": "-0500",
          "t": "-0400",
          "n": "EDT",
          "s": "19700308T020000",
          "r": {
            "m": 3,
            "d": "2SU"
          }
        }
      }
    ],
    [
      "America/Nome",
      {
        "s": {
          "f": "-0800",
          "t": "-0900",
          "n": "AKST",
          "s": "19701101T020000",
          "r": {
            "m": 11,
            "d": "1SU"
          }
        },
        "d": {
          "f": "-0900",
          "t": "-0800",
          "n": "AKDT",
          "s": "19700308T020000",
          "r": {
            "m": 3,
            "d": "2SU"
          }
        }
      }
    ],
    [
      "America/Noronha",
      {
        "s": {
          "f": "-0200",
          "n": "-02"
        }
      }
    ],
    [
      "America/North_Dakota/Beulah",
      {
        "s": {
          "f": "-0500",
          "t": "-0600",
          "n": "CST",
          "s": "19701101T020000",
          "r": {
            "m": 11,
            "d": "1SU"
          }
        },
        "d": {
          "f": "-0600",
          "t": "-0500",
          "n": "CDT",
          "s": "19700308T020000",
          "r": {
            "m": 3,
            "d": "2SU"
          }
        }
      }
    ],
    [
      "America/North_Dakota/Center",
      {
        "s": {
          "f": "-0500",
          "t": "-0600",
          "n": "CST",
          "s": "19701101T020000",
          "r": {
            "m": 11,
            "d": "1SU"
          }
        },
        "d": {
          "f": "-0600",
          "t": "-0500",
          "n": "CDT",
          "s": "19700308T020000",
          "r": {
            "m": 3,
            "d": "2SU"
          }
        }
      }
    ],
    [
      "America/North_Dakota/New_Salem",
      {
        "s": {
          "f": "-0500",
          "t": "-0600",
          "n": "CST",
          "s": "19701101T020000",
          "r": {
            "m": 11,
            "d": "1SU"
          }
        },
        "d": {
          "f": "-0600",
          "t": "-0500",
          "n": "CDT",
          "s": "19700308T020000",
          "r": {
            "m": 3,
            "d": "2SU"
          }
        }
      }
    ],
    [
      "America/Ojinaga",
      {
        "s": {
          "f": "-0600",
          "t": "-0700",
          "n": "MST",
          "s": "19701101T020000",
          "r": {
            "m": 11,
            "d": "1SU"
          }
        },
        "d": {
          "f": "-0700",
          "t": "-0600",
          "n": "MDT",
          "s": "19700308T020000",
          "r": {
            "m": 3,
            "d": "2SU"
          }
        }
      }
    ],
    [
      "America/Panama",
      {
        "s": {
          "f": "-0500",
          "n": "EST"
        }
      }
    ],
    [
      "America/Pangnirtung",
      {
        "s": {
          "f": "-0400",
          "t": "-0500",
          "n": "EST",
          "s": "19701101T020000",
          "r": {
            "m": 11,
            "d": "1SU"
          }
        },
        "d": {
          "f": "-0500",
          "t": "-0400",
          "n": "EDT",
          "s": "19700308T020000",
          "r": {
            "m": 3,
            "d": "2SU"
          }
        }
      }
    ],
    [
      "America/Paramaribo",
      {
        "s": {
          "f": "-0300",
          "n": "-03"
        }
      }
    ],
    [
      "America/Phoenix",
      {
        "s": {
          "f": "-0700",
          "n": "MST"
        }
      }
    ],
    [
      "America/Port_of_Spain",
      {
        "s": {
          "f": "-0400",
          "n": "AST"
        }
      }
    ],
    [
      "America/Port-au-Prince",
      {
        "s": {
          "f": "-0400",
          "t": "-0500",
          "n": "EST",
          "s": "19701101T020000",
          "r": {
            "m": 11,
            "d": "1SU"
          }
        },
        "d": {
          "f": "-0500",
          "t": "-0400",
          "n": "EDT",
          "s": "19700308T020000",
          "r": {
            "m": 3,
            "d": "2SU"
          }
        }
      }
    ],
    [
      "America/Porto_Velho",
      {
        "s": {
          "f": "-0400",
          "n": "-04"
        }
      }
    ],
    [
      "America/Puerto_Rico",
      {
        "s": {
          "f": "-0400",
          "n": "AST"
        }
      }
    ],
    [
      "America/Punta_Arenas",
      {
        "s": {
          "f": "-0300",
          "n": "-03"
        }
      }
    ],
    [
      "America/Rainy_River",
      {
        "s": {
          "f": "-0500",
          "t": "-0600",
          "n": "CST",
          "s": "19701101T020000",
          "r": {
            "m": 11,
            "d": "1SU"
          }
        },
        "d": {
          "f": "-0600",
          "t": "-0500",
          "n": "CDT",
          "s": "19700308T020000",
          "r": {
            "m": 3,
            "d": "2SU"
          }
        }
      }
    ],
    [
      "America/Rankin_Inlet",
      {
        "s": {
          "f": "-0500",
          "t": "-0600",
          "n": "CST",
          "s": "19701101T020000",
          "r": {
            "m": 11,
            "d": "1SU"
          }
        },
        "d": {
          "f": "-0600",
          "t": "-0500",
          "n": "CDT",
          "s": "19700308T020000",
          "r": {
            "m": 3,
            "d": "2SU"
          }
        }
      }
    ],
    [
      "America/Recife",
      {
        "s": {
          "f": "-0300",
          "n": "-03"
        }
      }
    ],
    [
      "America/Regina",
      {
        "s": {
          "f": "-0600",
          "n": "CST"
        }
      }
    ],
    [
      "America/Resolute",
      {
        "s": {
          "f": "-0500",
          "t": "-0600",
          "n": "CST",
          "s": "19701101T020000",
          "r": {
            "m": 11,
            "d": "1SU"
          }
        },
        "d": {
          "f": "-0600",
          "t": "-0500",
          "n": "CDT",
          "s": "19700308T020000",
          "r": {
            "m": 3,
            "d": "2SU"
          }
        }
      }
    ],
    [
      "America/Rio_Branco",
      {
        "s": {
          "f": "-0500",
          "n": "-05"
        }
      }
    ],
    [
      "America/Santarem",
      {
        "s": {
          "f": "-0300",
          "n": "-03"
        }
      }
    ],
    [
      "America/Santiago",
      {
        "s": {
          "f": "-0300",
          "t": "-0400",
          "n": "-04",
          "s": "19700405T000000",
          "r": {
            "m": 4,
            "d": "1SU"
          }
        },
        "d": {
          "f": "-0400",
          "t": "-0300",
          "n": "-03",
          "s": "19700906T000000",
          "r": {
            "m": 9,
            "d": "1SU"
          }
        }
      }
    ],
    [
      "America/Santo_Domingo",
      {
        "s": {
          "f": "-0400",
          "n": "AST"
        }
      }
    ],
    [
      "America/Sao_Paulo",
      {
        "s": {
          "f": "-0300",
          "n": "-03",
          "s": "19700215T000000",
          "r": {
            "m": 2,
            "d": "3SU"
          }
        },
        "d": {
          "f": "-0300",
          "t": "-0200",
          "n": "-02",
          "s": "19701101T000000",
          "r": {
            "m": 11,
            "d": "1SU"
          }
        }
      }
    ],
    [
      "America/Scoresbysund",
      {
        "s": {
          "f": "+0000",
          "t": "-0100",
          "n": "-01",
          "s": "19701025T010000",
          "r": {
            "m": 10,
            "d": "-1SU"
          }
        },
        "d": {
          "f": "-0100",
          "t": "+0000",
          "n": "+00",
          "s": "19700329T000000",
          "r": {
            "m": 3,
            "d": "-1SU"
          }
        }
      }
    ],
    [
      "America/Sitka",
      {
        "s": {
          "f": "-0800",
          "t": "-0900",
          "n": "AKST",
          "s": "19701101T020000",
          "r": {
            "m": 11,
            "d": "1SU"
          }
        },
        "d": {
          "f": "-0900",
          "t": "-0800",
          "n": "AKDT",
          "s": "19700308T020000",
          "r": {
            "m": 3,
            "d": "2SU"
          }
        }
      }
    ],
    [
      "America/St_Barthelemy",
      {
        "s": {
          "f": "-0400",
          "n": "AST"
        }
      }
    ],
    [
      "America/St_Johns",
      {
        "s": {
          "f": "-0230",
          "t": "-0330",
          "n": "NST",
          "s": "19701101T020000",
          "r": {
            "m": 11,
            "d": "1SU"
          }
        },
        "d": {
          "f": "-0330",
          "t": "-0230",
          "n": "NDT",
          "s": "19700308T020000",
          "r": {
            "m": 3,
            "d": "2SU"
          }
        }
      }
    ],
    [
      "America/St_Kitts",
      {
        "s": {
          "f": "-0400",
          "n": "AST"
        }
      }
    ],
    [
      "America/St_Lucia",
      {
        "s": {
          "f": "-0400",
          "n": "AST"
        }
      }
    ],
    [
      "America/St_Thomas",
      {
        "s": {
          "f": "-0400",
          "n": "AST"
        }
      }
    ],
    [
      "America/St_Vincent",
      {
        "s": {
          "f": "-0400",
          "n": "AST"
        }
      }
    ],
    [
      "America/Swift_Current",
      {
        "s": {
          "f": "-0600",
          "n": "CST"
        }
      }
    ],
    [
      "America/Tegucigalpa",
      {
        "s": {
          "f": "-0600",
          "n": "CST"
        }
      }
    ],
    [
      "America/Thule",
      {
        "s": {
          "f": "-0300",
          "t": "-0400",
          "n": "AST",
          "s": "19701101T020000",
          "r": {
            "m": 11,
            "d": "1SU"
          }
        },
        "d": {
          "f": "-0400",
          "t": "-0300",
          "n": "ADT",
          "s": "19700308T020000",
          "r": {
            "m": 3,
            "d": "2SU"
          }
        }
      }
    ],
    [
      "America/Thunder_Bay",
      {
        "s": {
          "f": "-0400",
          "t": "-0500",
          "n": "EST",
          "s": "19701101T020000",
          "r": {
            "m": 11,
            "d": "1SU"
          }
        },
        "d": {
          "f": "-0500",
          "t": "-0400",
          "n": "EDT",
          "s": "19700308T020000",
          "r": {
            "m": 3,
            "d": "2SU"
          }
        }
      }
    ],
    [
      "America/Tijuana",
      {
        "s": {
          "f": "-0700",
          "t": "-0800",
          "n": "PST",
          "s": "19701101T020000",
          "r": {
            "m": 11,
            "d": "1SU"
          }
        },
        "d": {
          "f": "-0800",
          "t": "-0700",
          "n": "PDT",
          "s": "19700308T020000",
          "r": {
            "m": 3,
            "d": "2SU"
          }
        }
      }
    ],
    [
      "America/Toronto",
      {
        "s": {
          "f": "-0400",
          "t": "-0500",
          "n": "EST",
          "s": "19701101T020000",
          "r": {
            "m": 11,
            "d": "1SU"
          }
        },
        "d": {
          "f": "-0500",
          "t": "-0400",
          "n": "EDT",
          "s": "19700308T020000",
          "r": {
            "m": 3,
            "d": "2SU"
          }
        }
      }
    ],
    [
      "America/Tortola",
      {
        "s": {
          "f": "-0400",
          "n": "AST"
        }
      }
    ],
    [
      "America/Vancouver",
      {
        "s": {
          "f": "-0700",
          "t": "-0800",
          "n": "PST",
          "s": "19701101T020000",
          "r": {
            "m": 11,
            "d": "1SU"
          }
        },
        "d": {
          "f": "-0800",
          "t": "-0700",
          "n": "PDT",
          "s": "19700308T020000",
          "r": {
            "m": 3,
            "d": "2SU"
          }
        }
      }
    ],
    [
      "America/Whitehorse",
      {
        "s": {
          "f": "-0700",
          "t": "-0800",
          "n": "PST",
          "s": "19701101T020000",
          "r": {
            "m": 11,
            "d": "1SU"
          }
        },
        "d": {
          "f": "-0800",
          "t": "-0700",
          "n": "PDT",
          "s": "19700308T020000",
          "r": {
            "m": 3,
            "d": "2SU"
          }
        }
      }
    ],
    [
      "America/Winnipeg",
      {
        "s": {
          "f": "-0500",
          "t": "-0600",
          "n": "CST",
          "s": "19701101T020000",
          "r": {
            "m": 11,
            "d": "1SU"
          }
        },
        "d": {
          "f": "-0600",
          "t": "-0500",
          "n": "CDT",
          "s": "19700308T020000",
          "r": {
            "m": 3,
            "d": "2SU"
          }
        }
      }
    ],
    [
      "America/Yakutat",
      {
        "s": {
          "f": "-0800",
          "t": "-0900",
          "n": "AKST",
          "s": "19701101T020000",
          "r": {
            "m": 11,
            "d": "1SU"
          }
        },
        "d": {
          "f": "-0900",
          "t": "-0800",
          "n": "AKDT",
          "s": "19700308T020000",
          "r": {
            "m": 3,
            "d": "2SU"
          }
        }
      }
    ],
    [
      "America/Yellowknife",
      {
        "s": {
          "f": "-0600",
          "t": "-0700",
          "n": "MST",
          "s": "19701101T020000",
          "r": {
            "m": 11,
            "d": "1SU"
          }
        },
        "d": {
          "f": "-0700",
          "t": "-0600",
          "n": "MDT",
          "s": "19700308T020000",
          "r": {
            "m": 3,
            "d": "2SU"
          }
        }
      }
    ],
    [
      "Antarctica/Casey",
      {
        "s": {
          "f": "+0800",
          "n": "+08"
        }
      }
    ],
    [
      "Antarctica/Davis",
      {
        "s": {
          "f": "+0700",
          "n": "+07"
        }
      }
    ],
    [
      "Antarctica/DumontDUrville",
      {
        "s": {
          "f": "+1000",
          "n": "+10"
        }
      }
    ],
    [
      "Antarctica/Macquarie",
      {
        "s": {
          "f": "+1100",
          "n": "+11"
        }
      }
    ],
    [
      "Antarctica/Mawson",
      {
        "s": {
          "f": "+0500",
          "n": "+05"
        }
      }
    ],
    [
      "Antarctica/McMurdo",
      {
        "s": {
          "f": "+1300",
          "t": "+1200",
          "n": "NZST",
          "s": "19700405T030000",
          "r": {
            "m": 4,
            "d": "1SU"
          }
        },
        "d": {
          "f": "+1200",
          "t": "+1300",
          "n": "NZDT",
          "s": "19700927T020000",
          "r": {
            "m": 9,
            "d": "-1SU"
          }
        }
      }
    ],
    [
      "Antarctica/Palmer",
      {
        "s": {
          "f": "-0300",
          "n": "-03"
        }
      }
    ],
    [
      "Antarctica/Rothera",
      {
        "s": {
          "f": "-0300",
          "n": "-03"
        }
      }
    ],
    [
      "Antarctica/Syowa",
      {
        "s": {
          "f": "+0300",
          "n": "+03"
        }
      }
    ],
    [
      "Antarctica/Troll",
      {
        "s": {
          "f": "+0200",
          "t": "+0000",
          "n": "+00",
          "s": "19701025T030000",
          "r": {
            "m": 10,
            "d": "-1SU"
          }
        },
        "d": {
          "f": "+0000",
          "t": "+0200",
          "n": "+02",
          "s": "19700329T010000",
          "r": {
            "m": 3,
            "d": "-1SU"
          }
        }
      }
    ],
    [
      "Antarctica/Vostok",
      {
        "s": {
          "f": "+0600",
          "n": "+06"
        }
      }
    ],
    [
      "Arctic/Longyearbyen",
      {
        "s": {
          "f": "+0200",
          "t": "+0100",
          "n": "CET",
          "s": "19701025T030000",
          "r": {
            "m": 10,
            "d": "-1SU"
          }
        },
        "d": {
          "f": "+0100",
          "t": "+0200",
          "n": "CEST",
          "s": "19700329T020000",
          "r": {
            "m": 3,
            "d": "-1SU"
          }
        }
      }
    ],
    [
      "Asia/Aden",
      {
        "s": {
          "f": "+0300",
          "n": "+03"
        }
      }
    ],
    [
      "Asia/Almaty",
      {
        "s": {
          "f": "+0600",
          "n": "+06"
        }
      }
    ],
    [
      "Asia/Amman",
      {
        "s": {
          "f": "+0300",
          "t": "+0200",
          "n": "EET",
          "s": "19701030T010000",
          "r": {
            "m": 10,
            "d": "-1FR"
          }
        },
        "d": {
          "f": "+0200",
          "t": "+0300",
          "n": "EEST",
          "s": "19700326T235959",
          "r": {
            "m": 3,
            "d": "-1TH"
          }
        }
      }
    ],
    [
      "Asia/Anadyr",
      {
        "s": {
          "f": "+1200",
          "n": "+12"
        }
      }
    ],
    [
      "Asia/Aqtau",
      {
        "s": {
          "f": "+0500",
          "n": "+05"
        }
      }
    ],
    [
      "Asia/Aqtobe",
      {
        "s": {
          "f": "+0500",
          "n": "+05"
        }
      }
    ],
    [
      "Asia/Ashgabat",
      {
        "s": {
          "f": "+0500",
          "n": "+05"
        }
      }
    ],
    [
      "Asia/Atyrau",
      {
        "s": {
          "f": "+0500",
          "n": "+05"
        }
      }
    ],
    [
      "Asia/Baghdad",
      {
        "s": {
          "f": "+0300",
          "n": "+03"
        }
      }
    ],
    [
      "Asia/Bahrain",
      {
        "s": {
          "f": "+0300",
          "n": "+03"
        }
      }
    ],
    [
      "Asia/Baku",
      {
        "s": {
          "f": "+0400",
          "n": "+04"
        }
      }
    ],
    [
      "Asia/Bangkok",
      {
        "s": {
          "f": "+0700",
          "n": "+07"
        }
      }
    ],
    [
      "Asia/Barnaul",
      {
        "s": {
          "f": "+0700",
          "n": "+07"
        }
      }
    ],
    [
      "Asia/Beirut",
      {
        "s": {
          "f": "+0300",
          "t": "+0200",
          "n": "EET",
          "s": "19701025T000000",
          "r": {
            "m": 10,
            "d": "-1SU"
          }
        },
        "d": {
          "f": "+0200",
          "t": "+0300",
          "n": "EEST",
          "s": "19700329T000000",
          "r": {
            "m": 3,
            "d": "-1SU"
          }
        }
      }
    ],
    [
      "Asia/Bishkek",
      {
        "s": {
          "f": "+0600",
          "n": "+06"
        }
      }
    ],
    [
      "Asia/Brunei",
      {
        "s": {
          "f": "+0800",
          "n": "+08"
        }
      }
    ],
    [
      "Asia/Chita",
      {
        "s": {
          "f": "+0900",
          "n": "+09"
        }
      }
    ],
    [
      "Asia/Choibalsan",
      {
        "s": {
          "f": "+0800",
          "n": "+08"
        }
      }
    ],
    [
      "Asia/Colombo",
      {
        "s": {
          "f": "+0530",
          "n": "+0530"
        }
      }
    ],
    [
      "Asia/Damascus",
      {
        "s": {
          "f": "+0300",
          "t": "+0200",
          "n": "EET",
          "s": "19701030T000000",
          "r": {
            "m": 10,
            "d": "-1FR"
          }
        },
        "d": {
          "f": "+0200",
          "t": "+0300",
          "n": "EEST",
          "s": "19700327T000000",
          "r": {
            "m": 3,
            "d": "-1FR"
          }
        }
      }
    ],
    [
      "Asia/Dhaka",
      {
        "s": {
          "f": "+0600",
          "n": "+06"
        }
      }
    ],
    [
      "Asia/Dili",
      {
        "s": {
          "f": "+0900",
          "n": "+09"
        }
      }
    ],
    [
      "Asia/Dubai",
      {
        "s": {
          "f": "+0400",
          "n": "+04"
        }
      }
    ],
    [
      "Asia/Dushanbe",
      {
        "s": {
          "f": "+0500",
          "n": "+05"
        }
      }
    ],
    [
      "Asia/Famagusta",
      {
        "s": {
          "f": "+0300",
          "t": "+0200",
          "n": "EET",
          "s": "19701025T040000",
          "r": {
            "m": 10,
            "d": "-1SU"
          }
        },
        "d": {
          "f": "+0200",
          "t": "+0300",
          "n": "EEST",
          "s": "19700329T030000",
          "r": {
            "m": 3,
            "d": "-1SU"
          }
        }
      }
    ],
    [
      "Asia/Gaza",
      {
        "s": {
          "f": "+0300",
          "t": "+0200",
          "n": "EET",
          "s": "19701031T010000",
          "r": {
            "m": 10,
            "d": "-1SA"
          }
        },
        "d": {
          "f": "+0200",
          "t": "+0300",
          "n": "EEST",
          "s": "19700328T010000",
          "r": {
            "m": 3,
            "d": "4SA"
          }
        }
      }
    ],
    [
      "Asia/Hebron",
      {
        "s": {
          "f": "+0300",
          "t": "+0200",
          "n": "EET",
          "s": "19701031T010000",
          "r": {
            "m": 10,
            "d": "-1SA"
          }
        },
        "d": {
          "f": "+0200",
          "t": "+0300",
          "n": "EEST",
          "s": "19700328T010000",
          "r": {
            "m": 3,
            "d": "4SA"
          }
        }
      }
    ],
    [
      "Asia/Ho_Chi_Minh",
      {
        "s": {
          "f": "+0700",
          "n": "+07"
        }
      }
    ],
    [
      "Asia/Hong_Kong",
      {
        "s": {
          "f": "+0800",
          "n": "HKT"
        }
      }
    ],
    [
      "Asia/Hovd",
      {
        "s": {
          "f": "+0700",
          "n": "+07"
        }
      }
    ],
    [
      "Asia/Irkutsk",
      {
        "s": {
          "f": "+0800",
          "n": "+08"
        }
      }
    ],
    [
      "Asia/Istanbul",
      {
        "s": {
          "f": "+0300",
          "n": "+03"
        }
      }
    ],
    [
      "Asia/Jakarta",
      {
        "s": {
          "f": "+0700",
          "n": "WIB"
        }
      }
    ],
    [
      "Asia/Jayapura",
      {
        "s": {
          "f": "+0900",
          "n": "WIT"
        }
      }
    ],
    [
      "Asia/Jerusalem",
      {
        "s": {
          "f": "+0300",
          "t": "+0200",
          "n": "IST",
          "s": "19701025T020000",
          "r": {
            "m": 10,
            "d": "-1SU"
          }
        },
        "d": {
          "f": "+0200",
          "t": "+0300",
          "n": "IDT",
          "s": "19700327T020000",
          "r": {
            "m": 3,
            "d": "-1FR"
          }
        }
      }
    ],
    [
      "Asia/Kabul",
      {
        "s": {
          "f": "+0430",
          "n": "+0430"
        }
      }
    ],
    [
      "Asia/Kamchatka",
      {
        "s": {
          "f": "+1200",
          "n": "+12"
        }
      }
    ],
    [
      "Asia/Karachi",
      {
        "s": {
          "f": "+0500",
          "n": "PKT"
        }
      }
    ],
    [
      "Asia/Kathmandu",
      {
        "s": {
          "f": "+0545",
          "n": "+0545"
        }
      }
    ],
    [
      "Asia/Khandyga",
      {
        "s": {
          "f": "+0900",
          "n": "+09"
        }
      }
    ],
    [
      "Asia/Kolkata",
      {
        "s": {
          "f": "+0530",
          "n": "IST"
        }
      }
    ],
    [
      "Asia/Krasnoyarsk",
      {
        "s": {
          "f": "+0700",
          "n": "+07"
        }
      }
    ],
    [
      "Asia/Kuala_Lumpur",
      {
        "s": {
          "f": "+0800",
          "n": "+08"
        }
      }
    ],
    [
      "Asia/Kuching",
      {
        "s": {
          "f": "+0800",
          "n": "+08"
        }
      }
    ],
    [
      "Asia/Kuwait",
      {
        "s": {
          "f": "+0300",
          "n": "+03"
        }
      }
    ],
    [
      "Asia/Macau",
      {
        "s": {
          "f": "+0800",
          "n": "CST"
        }
      }
    ],
    [
      "Asia/Magadan",
      {
        "s": {
          "f": "+1100",
          "n": "+11"
        }
      }
    ],
    [
      "Asia/Makassar",
      {
        "s": {
          "f": "+0800",
          "n": "WITA"
        }
      }
    ],
    [
      "Asia/Manila",
      {
        "s": {
          "f": "+0800",
          "n": "PST"
        }
      }
    ],
    [
      "Asia/Muscat",
      {
        "s": {
          "f": "+0400",
          "n": "+04"
        }
      }
    ],
    [
      "Asia/Nicosia",
      {
        "s": {
          "f": "+0300",
          "t": "+0200",
          "n": "EET",
          "s": "19701025T040000",
          "r": {
            "m": 10,
            "d": "-1SU"
          }
        },
        "d": {
          "f": "+0200",
          "t": "+0300",
          "n": "EEST",
          "s": "19700329T030000",
          "r": {
            "m": 3,
            "d": "-1SU"
          }
        }
      }
    ],
    [
      "Asia/Novokuznetsk",
      {
        "s": {
          "f": "+0700",
          "n": "+07"
        }
      }
    ],
    [
      "Asia/Novosibirsk",
      {
        "s": {
          "f": "+0700",
          "n": "+07"
        }
      }
    ],
    [
      "Asia/Omsk",
      {
        "s": {
          "f": "+0600",
          "n": "+06"
        }
      }
    ],
    [
      "Asia/Oral",
      {
        "s": {
          "f": "+0500",
          "n": "+05"
        }
      }
    ],
    [
      "Asia/Phnom_Penh",
      {
        "s": {
          "f": "+0700",
          "n": "+07"
        }
      }
    ],
    [
      "Asia/Pontianak",
      {
        "s": {
          "f": "+0700",
          "n": "WIB"
        }
      }
    ],
    [
      "Asia/Pyongyang",
      {
        "s": {
          "f": "+0900",
          "n": "KST"
        }
      }
    ],
    [
      "Asia/Qatar",
      {
        "s": {
          "f": "+0300",
          "n": "+03"
        }
      }
    ],
    [
      "Asia/Qyzylorda",
      {
        "s": {
          "f": "+0600",
          "n": "+06"
        }
      }
    ],
    [
      "Asia/Riyadh",
      {
        "s": {
          "f": "+0300",
          "n": "+03"
        }
      }
    ],
    [
      "Asia/Sakhalin",
      {
        "s": {
          "f": "+1100",
          "n": "+11"
        }
      }
    ],
    [
      "Asia/Samarkand",
      {
        "s": {
          "f": "+0500",
          "n": "+05"
        }
      }
    ],
    [
      "Asia/Seoul",
      {
        "s": {
          "f": "+0900",
          "n": "KST"
        }
      }
    ],
    [
      "Asia/Shanghai",
      {
        "s": {
          "f": "+0800",
          "n": "CST"
        }
      }
    ],
    [
      "Asia/Singapore",
      {
        "s": {
          "f": "+0800",
          "n": "+08"
        }
      }
    ],
    [
      "Asia/Srednekolymsk",
      {
        "s": {
          "f": "+1100",
          "n": "+11"
        }
      }
    ],
    [
      "Asia/Taipei",
      {
        "s": {
          "f": "+0800",
          "n": "CST"
        }
      }
    ],
    [
      "Asia/Tashkent",
      {
        "s": {
          "f": "+0500",
          "n": "+05"
        }
      }
    ],
    [
      "Asia/Tbilisi",
      {
        "s": {
          "f": "+0400",
          "n": "+04"
        }
      }
    ],
    [
      "Asia/Tehran",
      {
        "s": {
          "f": "+0430",
          "t": "+0330",
          "n": "+0330",
          "s": "19700921T000000",
          "r": {
            "m": 9,
            "d": "3SU"
          }
        },
        "d": {
          "f": "+0330",
          "t": "+0430",
          "n": "+0430",
          "s": "19700321T000000",
          "r": {
            "m": 3,
            "d": "3SU"
          }
        }
      }
    ],
    [
      "Asia/Thimphu",
      {
        "s": {
          "f": "+0600",
          "n": "+06"
        }
      }
    ],
    [
      "Asia/Tokyo",
      {
        "s": {
          "f": "+0900",
          "n": "JST"
        }
      }
    ],
    [
      "Asia/Tomsk",
      {
        "s": {
          "f": "+0700",
          "n": "+07"
        }
      }
    ],
    [
      "Asia/Ulaanbaatar",
      {
        "s": {
          "f": "+0800",
          "n": "+08"
        }
      }
    ],
    [
      "Asia/Urumqi",
      {
        "s": {
          "f": "+0600",
          "n": "+06"
        }
      }
    ],
    [
      "Asia/Ust-Nera",
      {
        "s": {
          "f": "+1000",
          "n": "+10"
        }
      }
    ],
    [
      "Asia/Vientiane",
      {
        "s": {
          "f": "+0700",
          "n": "+07"
        }
      }
    ],
    [
      "Asia/Vladivostok",
      {
        "s": {
          "f": "+1000",
          "n": "+10"
        }
      }
    ],
    [
      "Asia/Yakutsk",
      {
        "s": {
          "f": "+0900",
          "n": "+09"
        }
      }
    ],
    [
      "Asia/Yangon",
      {
        "s": {
          "f": "+0630",
          "n": "+0630"
        }
      }
    ],
    [
      "Asia/Yekaterinburg",
      {
        "s": {
          "f": "+0500",
          "n": "+05"
        }
      }
    ],
    [
      "Asia/Yerevan",
      {
        "s": {
          "f": "+0400",
          "n": "+04"
        }
      }
    ],
    [
      "Atlantic/Azores",
      {
        "s": {
          "f": "+0000",
          "t": "-0100",
          "n": "-01",
          "s": "19701025T010000",
          "r": {
            "m": 10,
            "d": "-1SU"
          }
        },
        "d": {
          "f": "-0100",
          "t": "+0000",
          "n": "+00",
          "s": "19700329T000000",
          "r": {
            "m": 3,
            "d": "-1SU"
          }
        }
      }
    ],
    [
      "Atlantic/Bermuda",
      {
        "s": {
          "f": "-0300",
          "t": "-0400",
          "n": "AST",
          "s": "19701101T020000",
          "r": {
            "m": 11,
            "d": "1SU"
          }
        },
        "d": {
          "f": "-0400",
          "t": "-0300",
          "n": "ADT",
          "s": "19700308T020000",
          "r": {
            "m": 3,
            "d": "2SU"
          }
        }
      }
    ],
    [
      "Atlantic/Canary",
      {
        "s": {
          "f": "+0100",
          "t": "+0000",
          "n": "WET",
          "s": "19701025T020000",
          "r": {
            "m": 10,
            "d": "-1SU"
          }
        },
        "d": {
          "f": "+0000",
          "t": "+0100",
          "n": "WEST",
          "s": "19700329T010000",
          "r": {
            "m": 3,
            "d": "-1SU"
          }
        }
      }
    ],
    [
      "Atlantic/Cape_Verde",
      {
        "s": {
          "f": "-0100",
          "n": "-01"
        }
      }
    ],
    [
      "Atlantic/Faroe",
      {
        "s": {
          "f": "+0100",
          "t": "+0000",
          "n": "WET",
          "s": "19701025T020000",
          "r": {
            "m": 10,
            "d": "-1SU"
          }
        },
        "d": {
          "f": "+0000",
          "t": "+0100",
          "n": "WEST",
          "s": "19700329T010000",
          "r": {
            "m": 3,
            "d": "-1SU"
          }
        }
      }
    ],
    [
      "Atlantic/Madeira",
      {
        "s": {
          "f": "+0100",
          "t": "+0000",
          "n": "WET",
          "s": "19701025T020000",
          "r": {
            "m": 10,
            "d": "-1SU"
          }
        },
        "d": {
          "f": "+0000",
          "t": "+0100",
          "n": "WEST",
          "s": "19700329T010000",
          "r": {
            "m": 3,
            "d": "-1SU"
          }
        }
      }
    ],
    [
      "Atlantic/Reykjavik",
      {
        "s": {
          "f": "+0000",
          "n": "GMT"
        }
      }
    ],
    [
      "Atlantic/South_Georgia",
      {
        "s": {
          "f": "-0200",
          "n": "-02"
        }
      }
    ],
    [
      "Atlantic/St_Helena",
      {
        "s": {
          "f": "+0000",
          "n": "GMT"
        }
      }
    ],
    [
      "Atlantic/Stanley",
      {
        "s": {
          "f": "-0300",
          "n": "-03"
        }
      }
    ],
    [
      "Australia/Adelaide",
      {
        "s": {
          "f": "+1030",
          "t": "+0930",
          "n": "ACST",
          "s": "19700405T030000",
          "r": {
            "m": 4,
            "d": "1SU"
          }
        },
        "d": {
          "f": "+0930",
          "t": "+1030",
          "n": "ACDT",
          "s": "19701004T020000",
          "r": {
            "m": 10,
            "d": "1SU"
          }
        }
      }
    ],
    [
      "Australia/Brisbane",
      {
        "s": {
          "f": "+1000",
          "n": "AEST"
        }
      }
    ],
    [
      "Australia/Broken_Hill",
      {
        "s": {
          "f": "+1030",
          "t": "+0930",
          "n": "ACST",
          "s": "19700405T030000",
          "r": {
            "m": 4,
            "d": "1SU"
          }
        },
        "d": {
          "f": "+0930",
          "t": "+1030",
          "n": "ACDT",
          "s": "19701004T020000",
          "r": {
            "m": 10,
            "d": "1SU"
          }
        }
      }
    ],
    [
      "Australia/Currie",
      {
        "s": {
          "f": "+1100",
          "t": "+1000",
          "n": "AEST",
          "s": "19700405T030000",
          "r": {
            "m": 4,
            "d": "1SU"
          }
        },
        "d": {
          "f": "+1000",
          "t": "+1100",
          "n": "AEDT",
          "s": "19701004T020000",
          "r": {
            "m": 10,
            "d": "1SU"
          }
        }
      }
    ],
    [
      "Australia/Darwin",
      {
        "s": {
          "f": "+0930",
          "n": "ACST"
        }
      }
    ],
    [
      "Australia/Eucla",
      {
        "s": {
          "f": "+0845",
          "n": "+0845"
        }
      }
    ],
    [
      "Australia/Hobart",
      {
        "s": {
          "f": "+1100",
          "t": "+1000",
          "n": "AEST",
          "s": "19700405T030000",
          "r": {
            "m": 4,
            "d": "1SU"
          }
        },
        "d": {
          "f": "+1000",
          "t": "+1100",
          "n": "AEDT",
          "s": "19701004T020000",
          "r": {
            "m": 10,
            "d": "1SU"
          }
        }
      }
    ],
    [
      "Australia/Lindeman",
      {
        "s": {
          "f": "+1000",
          "n": "AEST"
        }
      }
    ],
    [
      "Australia/Lord_Howe",
      {
        "s": {
          "f": "+1100",
          "t": "+1030",
          "n": "+1030",
          "s": "19700405T020000",
          "r": {
            "m": 4,
            "d": "1SU"
          }
        },
        "d": {
          "f": "+1030",
          "t": "+1100",
          "n": "+11",
          "s": "19701004T020000",
          "r": {
            "m": 10,
            "d": "1SU"
          }
        }
      }
    ],
    [
      "Australia/Melbourne",
      {
        "s": {
          "f": "+1100",
          "t": "+1000",
          "n": "AEST",
          "s": "19700405T030000",
          "r": {
            "m": 4,
            "d": "1SU"
          }
        },
        "d": {
          "f": "+1000",
          "t": "+1100",
          "n": "AEDT",
          "s": "19701004T020000",
          "r": {
            "m": 10,
            "d": "1SU"
          }
        }
      }
    ],
    [
      "Australia/Perth",
      {
        "s": {
          "f": "+0800",
          "n": "AWST"
        }
      }
    ],
    [
      "Australia/Sydney",
      {
        "s": {
          "f": "+1100",
          "t": "+1000",
          "n": "AEST",
          "s": "19700405T030000",
          "r": {
            "m": 4,
            "d": "1SU"
          }
        },
        "d": {
          "f": "+1000",
          "t": "+1100",
          "n": "AEDT",
          "s": "19701004T020000",
          "r": {
            "m": 10,
            "d": "1SU"
          }
        }
      }
    ],
    [
      "Etc/GMT-0",
      {
        "s": {
          "f": "+0000",
          "n": "GMT"
        }
      }
    ],
    [
      "Etc/GMT-1",
      {
        "s": {
          "f": "-0100",
          "n": "-01"
        }
      }
    ],
    [
      "Etc/GMT-10",
      {
        "s": {
          "f": "-1000",
          "n": "-10"
        }
      }
    ],
    [
      "Etc/GMT-11",
      {
        "s": {
          "f": "-1100",
          "n": "-11"
        }
      }
    ],
    [
      "Etc/GMT-12",
      {
        "s": {
          "f": "-1200",
          "n": "-12"
        }
      }
    ],
    [
      "Etc/GMT-2",
      {
        "s": {
          "f": "-0200",
          "n": "-02"
        }
      }
    ],
    [
      "Etc/GMT-3",
      {
        "s": {
          "f": "-0300",
          "n": "-03"
        }
      }
    ],
    [
      "Etc/GMT-4",
      {
        "s": {
          "f": "-0400",
          "n": "-04"
        }
      }
    ],
    [
      "Etc/GMT-5",
      {
        "s": {
          "f": "-0500",
          "n": "-05"
        }
      }
    ],
    [
      "Etc/GMT-6",
      {
        "s": {
          "f": "-0600",
          "n": "-06"
        }
      }
    ],
    [
      "Etc/GMT-7",
      {
        "s": {
          "f": "-0700",
          "n": "-07"
        }
      }
    ],
    [
      "Etc/GMT-8",
      {
        "s": {
          "f": "-0800",
          "n": "-08"
        }
      }
    ],
    [
      "Etc/GMT-9",
      {
        "s": {
          "f": "-0900",
          "n": "-09"
        }
      }
    ],
    [
      "Etc/GMT",
      {
        "s": {
          "f": "+0000",
          "n": "GMT"
        }
      }
    ],
    [
      "Etc/GMT+0",
      {
        "s": {
          "f": "+0000",
          "n": "GMT"
        }
      }
    ],
    [
      "Etc/GMT+1",
      {
        "s": {
          "f": "+0100",
          "n": "+01"
        }
      }
    ],
    [
      "Etc/GMT+10",
      {
        "s": {
          "f": "+1000",
          "n": "+10"
        }
      }
    ],
    [
      "Etc/GMT+11",
      {
        "s": {
          "f": "+1100",
          "n": "+11"
        }
      }
    ],
    [
      "Etc/GMT+12",
      {
        "s": {
          "f": "+1200",
          "n": "+12"
        }
      }
    ],
    [
      "Etc/GMT+13",
      {
        "s": {
          "f": "+1300",
          "n": "+13"
        }
      }
    ],
    [
      "Etc/GMT+14",
      {
        "s": {
          "f": "+1400",
          "n": "+14"
        }
      }
    ],
    [
      "Etc/GMT+2",
      {
        "s": {
          "f": "+0200",
          "n": "+02"
        }
      }
    ],
    [
      "Etc/GMT+3",
      {
        "s": {
          "f": "+0300",
          "n": "+03"
        }
      }
    ],
    [
      "Etc/GMT+4",
      {
        "s": {
          "f": "+0400",
          "n": "+04"
        }
      }
    ],
    [
      "Etc/GMT+5",
      {
        "s": {
          "f": "+0500",
          "n": "+05"
        }
      }
    ],
    [
      "Etc/GMT+6",
      {
        "s": {
          "f": "+0600",
          "n": "+06"
        }
      }
    ],
    [
      "Etc/GMT+7",
      {
        "s": {
          "f": "+0700",
          "n": "+07"
        }
      }
    ],
    [
      "Etc/GMT+8",
      {
        "s": {
          "f": "+0800",
          "n": "+08"
        }
      }
    ],
    [
      "Etc/GMT+9",
      {
        "s": {
          "f": "+0900",
          "n": "+09"
        }
      }
    ],
    [
      "Etc/GMT0",
      {
        "s": {
          "f": "+0000",
          "n": "GMT"
        }
      }
    ],
    [
      "Etc/Greenwich",
      {
        "s": {
          "f": "+0000",
          "n": "GMT"
        }
      }
    ],
    [
      "Etc/UCT",
      {
        "s": {
          "f": "+0000",
          "n": "UCT"
        }
      }
    ],
    [
      "Etc/Universal",
      {
        "s": {
          "f": "+0000",
          "n": "UTC"
        }
      }
    ],
    [
      "Etc/UTC",
      {
        "s": {
          "f": "+0000",
          "n": "UTC"
        }
      }
    ],
    [
      "Etc/Zulu",
      {
        "s": {
          "f": "+0000",
          "n": "UTC"
        }
      }
    ],
    [
      "Europe/Amsterdam",
      {
        "s": {
          "f": "+0200",
          "t": "+0100",
          "n": "CET",
          "s": "19701025T030000",
          "r": {
            "m": 10,
            "d": "-1SU"
          }
        },
        "d": {
          "f": "+0100",
          "t": "+0200",
          "n": "CEST",
          "s": "19700329T020000",
          "r": {
            "m": 3,
            "d": "-1SU"
          }
        }
      }
    ],
    [
      "Europe/Andorra",
      {
        "s": {
          "f": "+0200",
          "t": "+0100",
          "n": "CET",
          "s": "19701025T030000",
          "r": {
            "m": 10,
            "d": "-1SU"
          }
        },
        "d": {
          "f": "+0100",
          "t": "+0200",
          "n": "CEST",
          "s": "19700329T020000",
          "r": {
            "m": 3,
            "d": "-1SU"
          }
        }
      }
    ],
    [
      "Europe/Astrakhan",
      {
        "s": {
          "f": "+0400",
          "n": "+04"
        }
      }
    ],
    [
      "Europe/Athens",
      {
        "s": {
          "f": "+0300",
          "t": "+0200",
          "n": "EET",
          "s": "19701025T040000",
          "r": {
            "m": 10,
            "d": "-1SU"
          }
        },
        "d": {
          "f": "+0200",
          "t": "+0300",
          "n": "EEST",
          "s": "19700329T030000",
          "r": {
            "m": 3,
            "d": "-1SU"
          }
        }
      }
    ],
    [
      "Europe/Belgrade",
      {
        "s": {
          "f": "+0200",
          "t": "+0100",
          "n": "CET",
          "s": "19701025T030000",
          "r": {
            "m": 10,
            "d": "-1SU"
          }
        },
        "d": {
          "f": "+0100",
          "t": "+0200",
          "n": "CEST",
          "s": "19700329T020000",
          "r": {
            "m": 3,
            "d": "-1SU"
          }
        }
      }
    ],
    [
      "Europe/Berlin",
      {
        "s": {
          "f": "+0200",
          "t": "+0100",
          "n": "CET",
          "s": "19701025T030000",
          "r": {
            "m": 10,
            "d": "-1SU"
          }
        },
        "d": {
          "f": "+0100",
          "t": "+0200",
          "n": "CEST",
          "s": "19700329T020000",
          "r": {
            "m": 3,
            "d": "-1SU"
          }
        }
      }
    ],
    [
      "Europe/Bratislava",
      {
        "s": {
          "f": "+0200",
          "t": "+0100",
          "n": "CET",
          "s": "19701025T030000",
          "r": {
            "m": 10,
            "d": "-1SU"
          }
        },
        "d": {
          "f": "+0100",
          "t": "+0200",
          "n": "CEST",
          "s": "19700329T020000",
          "r": {
            "m": 3,
            "d": "-1SU"
          }
        }
      }
    ],
    [
      "Europe/Brussels",
      {
        "s": {
          "f": "+0200",
          "t": "+0100",
          "n": "CET",
          "s": "19701025T030000",
          "r": {
            "m": 10,
            "d": "-1SU"
          }
        },
        "d": {
          "f": "+0100",
          "t": "+0200",
          "n": "CEST",
          "s": "19700329T020000",
          "r": {
            "m": 3,
            "d": "-1SU"
          }
        }
      }
    ],
    [
      "Europe/Bucharest",
      {
        "s": {
          "f": "+0300",
          "t": "+0200",
          "n": "EET",
          "s": "19701025T040000",
          "r": {
            "m": 10,
            "d": "-1SU"
          }
        },
        "d": {
          "f": "+0200",
          "t": "+0300",
          "n": "EEST",
          "s": "19700329T030000",
          "r": {
            "m": 3,
            "d": "-1SU"
          }
        }
      }
    ],
    [
      "Europe/Budapest",
      {
        "s": {
          "f": "+0200",
          "t": "+0100",
          "n": "CET",
          "s": "19701025T030000",
          "r": {
            "m": 10,
            "d": "-1SU"
          }
        },
        "d": {
          "f": "+0100",
          "t": "+0200",
          "n": "CEST",
          "s": "19700329T020000",
          "r": {
            "m": 3,
            "d": "-1SU"
          }
        }
      }
    ],
    [
      "Europe/Busingen",
      {
        "s": {
          "f": "+0200",
          "t": "+0100",
          "n": "CET",
          "s": "19701025T030000",
          "r": {
            "m": 10,
            "d": "-1SU"
          }
        },
        "d": {
          "f": "+0100",
          "t": "+0200",
          "n": "CEST",
          "s": "19700329T020000",
          "r": {
            "m": 3,
            "d": "-1SU"
          }
        }
      }
    ],
    [
      "Europe/Chisinau",
      {
        "s": {
          "f": "+0300",
          "t": "+0200",
          "n": "EET",
          "s": "19701025T030000",
          "r": {
            "m": 10,
            "d": "-1SU"
          }
        },
        "d": {
          "f": "+0200",
          "t": "+0300",
          "n": "EEST",
          "s": "19700329T020000",
          "r": {
            "m": 3,
            "d": "-1SU"
          }
        }
      }
    ],
    [
      "Europe/Copenhagen",
      {
        "s": {
          "f": "+0200",
          "t": "+0100",
          "n": "CET",
          "s": "19701025T030000",
          "r": {
            "m": 10,
            "d": "-1SU"
          }
        },
        "d": {
          "f": "+0100",
          "t": "+0200",
          "n": "CEST",
          "s": "19700329T020000",
          "r": {
            "m": 3,
            "d": "-1SU"
          }
        }
      }
    ],
    [
      "Europe/Dublin",
      {
        "s": {
          "f": "+0100",
          "t": "+0000",
          "n": "GMT",
          "s": "19701025T020000",
          "r": {
            "m": 10,
            "d": "-1SU"
          }
        },
        "d": {
          "f": "+0000",
          "t": "+0100",
          "n": "IST",
          "s": "19700329T010000",
          "r": {
            "m": 3,
            "d": "-1SU"
          }
        }
      }
    ],
    [
      "Europe/Gibraltar",
      {
        "s": {
          "f": "+0200",
          "t": "+0100",
          "n": "CET",
          "s": "19701025T030000",
          "r": {
            "m": 10,
            "d": "-1SU"
          }
        },
        "d": {
          "f": "+0100",
          "t": "+0200",
          "n": "CEST",
          "s": "19700329T020000",
          "r": {
            "m": 3,
            "d": "-1SU"
          }
        }
      }
    ],
    [
      "Europe/Guernsey",
      {
        "s": {
          "f": "+0100",
          "t": "+0000",
          "n": "GMT",
          "s": "19701025T020000",
          "r": {
            "m": 10,
            "d": "-1SU"
          }
        },
        "d": {
          "f": "+0000",
          "t": "+0100",
          "n": "BST",
          "s": "19700329T010000",
          "r": {
            "m": 3,
            "d": "-1SU"
          }
        }
      }
    ],
    [
      "Europe/Helsinki",
      {
        "s": {
          "f": "+0300",
          "t": "+0200",
          "n": "EET",
          "s": "19701025T040000",
          "r": {
            "m": 10,
            "d": "-1SU"
          }
        },
        "d": {
          "f": "+0200",
          "t": "+0300",
          "n": "EEST",
          "s": "19700329T030000",
          "r": {
            "m": 3,
            "d": "-1SU"
          }
        }
      }
    ],
    [
      "Europe/Isle_of_Man",
      {
        "s": {
          "f": "+0100",
          "t": "+0000",
          "n": "GMT",
          "s": "19701025T020000",
          "r": {
            "m": 10,
            "d": "-1SU"
          }
        },
        "d": {
          "f": "+0000",
          "t": "+0100",
          "n": "BST",
          "s": "19700329T010000",
          "r": {
            "m": 3,
            "d": "-1SU"
          }
        }
      }
    ],
    [
      "Europe/Istanbul",
      {
        "s": {
          "f": "+0300",
          "n": "+03"
        }
      }
    ],
    [
      "Europe/Jersey",
      {
        "s": {
          "f": "+0100",
          "t": "+0000",
          "n": "GMT",
          "s": "19701025T020000",
          "r": {
            "m": 10,
            "d": "-1SU"
          }
        },
        "d": {
          "f": "+0000",
          "t": "+0100",
          "n": "BST",
          "s": "19700329T010000",
          "r": {
            "m": 3,
            "d": "-1SU"
          }
        }
      }
    ],
    [
      "Europe/Kaliningrad",
      {
        "s": {
          "f": "+0200",
          "n": "EET"
        }
      }
    ],
    [
      "Europe/Kiev",
      {
        "s": {
          "f": "+0300",
          "t": "+0200",
          "n": "EET",
          "s": "19701025T040000",
          "r": {
            "m": 10,
            "d": "-1SU"
          }
        },
        "d": {
          "f": "+0200",
          "t": "+0300",
          "n": "EEST",
          "s": "19700329T030000",
          "r": {
            "m": 3,
            "d": "-1SU"
          }
        }
      }
    ],
    [
      "Europe/Kirov",
      {
        "s": {
          "f": "+0300",
          "n": "+03"
        }
      }
    ],
    [
      "Europe/Lisbon",
      {
        "s": {
          "f": "+0100",
          "t": "+0000",
          "n": "WET",
          "s": "19701025T020000",
          "r": {
            "m": 10,
            "d": "-1SU"
          }
        },
        "d": {
          "f": "+0000",
          "t": "+0100",
          "n": "WEST",
          "s": "19700329T010000",
          "r": {
            "m": 3,
            "d": "-1SU"
          }
        }
      }
    ],
    [
      "Europe/Ljubljana",
      {
        "s": {
          "f": "+0200",
          "t": "+0100",
          "n": "CET",
          "s": "19701025T030000",
          "r": {
            "m": 10,
            "d": "-1SU"
          }
        },
        "d": {
          "f": "+0100",
          "t": "+0200",
          "n": "CEST",
          "s": "19700329T020000",
          "r": {
            "m": 3,
            "d": "-1SU"
          }
        }
      }
    ],
    [
      "Europe/London",
      {
        "s": {
          "f": "+0100",
          "t": "+0000",
          "n": "GMT",
          "s": "19701025T020000",
          "r": {
            "m": 10,
            "d": "-1SU"
          }
        },
        "d": {
          "f": "+0000",
          "t": "+0100",
          "n": "BST",
          "s": "19700329T010000",
          "r": {
            "m": 3,
            "d": "-1SU"
          }
        }
      }
    ],
    [
      "Europe/Luxembourg",
      {
        "s": {
          "f": "+0200",
          "t": "+0100",
          "n": "CET",
          "s": "19701025T030000",
          "r": {
            "m": 10,
            "d": "-1SU"
          }
        },
        "d": {
          "f": "+0100",
          "t": "+0200",
          "n": "CEST",
          "s": "19700329T020000",
          "r": {
            "m": 3,
            "d": "-1SU"
          }
        }
      }
    ],
    [
      "Europe/Madrid",
      {
        "s": {
          "f": "+0200",
          "t": "+0100",
          "n": "CET",
          "s": "19701025T030000",
          "r": {
            "m": 10,
            "d": "-1SU"
          }
        },
        "d": {
          "f": "+0100",
          "t": "+0200",
          "n": "CEST",
          "s": "19700329T020000",
          "r": {
            "m": 3,
            "d": "-1SU"
          }
        }
      }
    ],
    [
      "Europe/Malta",
      {
        "s": {
          "f": "+0200",
          "t": "+0100",
          "n": "CET",
          "s": "19701025T030000",
          "r": {
            "m": 10,
            "d": "-1SU"
          }
        },
        "d": {
          "f": "+0100",
          "t": "+0200",
          "n": "CEST",
          "s": "19700329T020000",
          "r": {
            "m": 3,
            "d": "-1SU"
          }
        }
      }
    ],
    [
      "Europe/Mariehamn",
      {
        "s": {
          "f": "+0300",
          "t": "+0200",
          "n": "EET",
          "s": "19701025T040000",
          "r": {
            "m": 10,
            "d": "-1SU"
          }
        },
        "d": {
          "f": "+0200",
          "t": "+0300",
          "n": "EEST",
          "s": "19700329T030000",
          "r": {
            "m": 3,
            "d": "-1SU"
          }
        }
      }
    ],
    [
      "Europe/Minsk",
      {
        "s": {
          "f": "+0300",
          "n": "+03"
        }
      }
    ],
    [
      "Europe/Monaco",
      {
        "s": {
          "f": "+0200",
          "t": "+0100",
          "n": "CET",
          "s": "19701025T030000",
          "r": {
            "m": 10,
            "d": "-1SU"
          }
        },
        "d": {
          "f": "+0100",
          "t": "+0200",
          "n": "CEST",
          "s": "19700329T020000",
          "r": {
            "m": 3,
            "d": "-1SU"
          }
        }
      }
    ],
    [
      "Europe/Moscow",
      {
        "s": {
          "f": "+0300",
          "n": "MSK"
        }
      }
    ],
    [
      "Europe/Nicosia",
      {
        "s": {
          "f": "+0300",
          "t": "+0200",
          "n": "EET",
          "s": "19701025T040000",
          "r": {
            "m": 10,
            "d": "-1SU"
          }
        },
        "d": {
          "f": "+0200",
          "t": "+0300",
          "n": "EEST",
          "s": "19700329T030000",
          "r": {
            "m": 3,
            "d": "-1SU"
          }
        }
      }
    ],
    [
      "Europe/Oslo",
      {
        "s": {
          "f": "+0200",
          "t": "+0100",
          "n": "CET",
          "s": "19701025T030000",
          "r": {
            "m": 10,
            "d": "-1SU"
          }
        },
        "d": {
          "f": "+0100",
          "t": "+0200",
          "n": "CEST",
          "s": "19700329T020000",
          "r": {
            "m": 3,
            "d": "-1SU"
          }
        }
      }
    ],
    [
      "Europe/Paris",
      {
        "s": {
          "f": "+0200",
          "t": "+0100",
          "n": "CET",
          "s": "19701025T030000",
          "r": {
            "m": 10,
            "d": "-1SU"
          }
        },
        "d": {
          "f": "+0100",
          "t": "+0200",
          "n": "CEST",
          "s": "19700329T020000",
          "r": {
            "m": 3,
            "d": "-1SU"
          }
        }
      }
    ],
    [
      "Europe/Podgorica",
      {
        "s": {
          "f": "+0200",
          "t": "+0100",
          "n": "CET",
          "s": "19701025T030000",
          "r": {
            "m": 10,
            "d": "-1SU"
          }
        },
        "d": {
          "f": "+0100",
          "t": "+0200",
          "n": "CEST",
          "s": "19700329T020000",
          "r": {
            "m": 3,
            "d": "-1SU"
          }
        }
      }
    ],
    [
      "Europe/Prague",
      {
        "s": {
          "f": "+0200",
          "t": "+0100",
          "n": "CET",
          "s": "19701025T030000",
          "r": {
            "m": 10,
            "d": "-1SU"
          }
        },
        "d": {
          "f": "+0100",
          "t": "+0200",
          "n": "CEST",
          "s": "19700329T020000",
          "r": {
            "m": 3,
            "d": "-1SU"
          }
        }
      }
    ],
    [
      "Europe/Riga",
      {
        "s": {
          "f": "+0300",
          "t": "+0200",
          "n": "EET",
          "s": "19701025T040000",
          "r": {
            "m": 10,
            "d": "-1SU"
          }
        },
        "d": {
          "f": "+0200",
          "t": "+0300",
          "n": "EEST",
          "s": "19700329T030000",
          "r": {
            "m": 3,
            "d": "-1SU"
          }
        }
      }
    ],
    [
      "Europe/Rome",
      {
        "s": {
          "f": "+0200",
          "t": "+0100",
          "n": "CET",
          "s": "19701025T030000",
          "r": {
            "m": 10,
            "d": "-1SU"
          }
        },
        "d": {
          "f": "+0100",
          "t": "+0200",
          "n": "CEST",
          "s": "19700329T020000",
          "r": {
            "m": 3,
            "d": "-1SU"
          }
        }
      }
    ],
    [
      "Europe/Samara",
      {
        "s": {
          "f": "+0400",
          "n": "+04"
        }
      }
    ],
    [
      "Europe/San_Marino",
      {
        "s": {
          "f": "+0200",
          "t": "+0100",
          "n": "CET",
          "s": "19701025T030000",
          "r": {
            "m": 10,
            "d": "-1SU"
          }
        },
        "d": {
          "f": "+0100",
          "t": "+0200",
          "n": "CEST",
          "s": "19700329T020000",
          "r": {
            "m": 3,
            "d": "-1SU"
          }
        }
      }
    ],
    [
      "Europe/Sarajevo",
      {
        "s": {
          "f": "+0200",
          "t": "+0100",
          "n": "CET",
          "s": "19701025T030000",
          "r": {
            "m": 10,
            "d": "-1SU"
          }
        },
        "d": {
          "f": "+0100",
          "t": "+0200",
          "n": "CEST",
          "s": "19700329T020000",
          "r": {
            "m": 3,
            "d": "-1SU"
          }
        }
      }
    ],
    [
      "Europe/Saratov",
      {
        "s": {
          "f": "+0400",
          "n": "+04"
        }
      }
    ],
    [
      "Europe/Simferopol",
      {
        "s": {
          "f": "+0300",
          "n": "MSK"
        }
      }
    ],
    [
      "Europe/Skopje",
      {
        "s": {
          "f": "+0200",
          "t": "+0100",
          "n": "CET",
          "s": "19701025T030000",
          "r": {
            "m": 10,
            "d": "-1SU"
          }
        },
        "d": {
          "f": "+0100",
          "t": "+0200",
          "n": "CEST",
          "s": "19700329T020000",
          "r": {
            "m": 3,
            "d": "-1SU"
          }
        }
      }
    ],
    [
      "Europe/Sofia",
      {
        "s": {
          "f": "+0300",
          "t": "+0200",
          "n": "EET",
          "s": "19701025T040000",
          "r": {
            "m": 10,
            "d": "-1SU"
          }
        },
        "d": {
          "f": "+0200",
          "t": "+0300",
          "n": "EEST",
          "s": "19700329T030000",
          "r": {
            "m": 3,
            "d": "-1SU"
          }
        }
      }
    ],
    [
      "Europe/Stockholm",
      {
        "s": {
          "f": "+0200",
          "t": "+0100",
          "n": "CET",
          "s": "19701025T030000",
          "r": {
            "m": 10,
            "d": "-1SU"
          }
        },
        "d": {
          "f": "+0100",
          "t": "+0200",
          "n": "CEST",
          "s": "19700329T020000",
          "r": {
            "m": 3,
            "d": "-1SU"
          }
        }
      }
    ],
    [
      "Europe/Tallinn",
      {
        "s": {
          "f": "+0300",
          "t": "+0200",
          "n": "EET",
          "s": "19701025T040000",
          "r": {
            "m": 10,
            "d": "-1SU"
          }
        },
        "d": {
          "f": "+0200",
          "t": "+0300",
          "n": "EEST",
          "s": "19700329T030000",
          "r": {
            "m": 3,
            "d": "-1SU"
          }
        }
      }
    ],
    [
      "Europe/Tirane",
      {
        "s": {
          "f": "+0200",
          "t": "+0100",
          "n": "CET",
          "s": "19701025T030000",
          "r": {
            "m": 10,
            "d": "-1SU"
          }
        },
        "d": {
          "f": "+0100",
          "t": "+0200",
          "n": "CEST",
          "s": "19700329T020000",
          "r": {
            "m": 3,
            "d": "-1SU"
          }
        }
      }
    ],
    [
      "Europe/Ulyanovsk",
      {
        "s": {
          "f": "+0400",
          "n": "+04"
        }
      }
    ],
    [
      "Europe/Uzhgorod",
      {
        "s": {
          "f": "+0300",
          "t": "+0200",
          "n": "EET",
          "s": "19701025T040000",
          "r": {
            "m": 10,
            "d": "-1SU"
          }
        },
        "d": {
          "f": "+0200",
          "t": "+0300",
          "n": "EEST",
          "s": "19700329T030000",
          "r": {
            "m": 3,
            "d": "-1SU"
          }
        }
      }
    ],
    [
      "Europe/Vaduz",
      {
        "s": {
          "f": "+0200",
          "t": "+0100",
          "n": "CET",
          "s": "19701025T030000",
          "r": {
            "m": 10,
            "d": "-1SU"
          }
        },
        "d": {
          "f": "+0100",
          "t": "+0200",
          "n": "CEST",
          "s": "19700329T020000",
          "r": {
            "m": 3,
            "d": "-1SU"
          }
        }
      }
    ],
    [
      "Europe/Vatican",
      {
        "s": {
          "f": "+0200",
          "t": "+0100",
          "n": "CET",
          "s": "19701025T030000",
          "r": {
            "m": 10,
            "d": "-1SU"
          }
        },
        "d": {
          "f": "+0100",
          "t": "+0200",
          "n": "CEST",
          "s": "19700329T020000",
          "r": {
            "m": 3,
            "d": "-1SU"
          }
        }
      }
    ],
    [
      "Europe/Vienna",
      {
        "s": {
          "f": "+0200",
          "t": "+0100",
          "n": "CET",
          "s": "19701025T030000",
          "r": {
            "m": 10,
            "d": "-1SU"
          }
        },
        "d": {
          "f": "+0100",
          "t": "+0200",
          "n": "CEST",
          "s": "19700329T020000",
          "r": {
            "m": 3,
            "d": "-1SU"
          }
        }
      }
    ],
    [
      "Europe/Vilnius",
      {
        "s": {
          "f": "+0300",
          "t": "+0200",
          "n": "EET",
          "s": "19701025T040000",
          "r": {
            "m": 10,
            "d": "-1SU"
          }
        },
        "d": {
          "f": "+0200",
          "t": "+0300",
          "n": "EEST",
          "s": "19700329T030000",
          "r": {
            "m": 3,
            "d": "-1SU"
          }
        }
      }
    ],
    [
      "Europe/Volgograd",
      {
        "s": {
          "f": "+0400",
          "n": "+04"
        }
      }
    ],
    [
      "Europe/Warsaw",
      {
        "s": {
          "f": "+0200",
          "t": "+0100",
          "n": "CET",
          "s": "19701025T030000",
          "r": {
            "m": 10,
            "d": "-1SU"
          }
        },
        "d": {
          "f": "+0100",
          "t": "+0200",
          "n": "CEST",
          "s": "19700329T020000",
          "r": {
            "m": 3,
            "d": "-1SU"
          }
        }
      }
    ],
    [
      "Europe/Zagreb",
      {
        "s": {
          "f": "+0200",
          "t": "+0100",
          "n": "CET",
          "s": "19701025T030000",
          "r": {
            "m": 10,
            "d": "-1SU"
          }
        },
        "d": {
          "f": "+0100",
          "t": "+0200",
          "n": "CEST",
          "s": "19700329T020000",
          "r": {
            "m": 3,
            "d": "-1SU"
          }
        }
      }
    ],
    [
      "Europe/Zaporozhye",
      {
        "s": {
          "f": "+0300",
          "t": "+0200",
          "n": "EET",
          "s": "19701025T040000",
          "r": {
            "m": 10,
            "d": "-1SU"
          }
        },
        "d": {
          "f": "+0200",
          "t": "+0300",
          "n": "EEST",
          "s": "19700329T030000",
          "r": {
            "m": 3,
            "d": "-1SU"
          }
        }
      }
    ],
    [
      "Europe/Zurich",
      {
        "s": {
          "f": "+0200",
          "t": "+0100",
          "n": "CET",
          "s": "19701025T030000",
          "r": {
            "m": 10,
            "d": "-1SU"
          }
        },
        "d": {
          "f": "+0100",
          "t": "+0200",
          "n": "CEST",
          "s": "19700329T020000",
          "r": {
            "m": 3,
            "d": "-1SU"
          }
        }
      }
    ],
    [
      "Indian/Antananarivo",
      {
        "s": {
          "f": "+0300",
          "n": "EAT"
        }
      }
    ],
    [
      "Indian/Chagos",
      {
        "s": {
          "f": "+0600",
          "n": "+06"
        }
      }
    ],
    [
      "Indian/Christmas",
      {
        "s": {
          "f": "+0700",
          "n": "+07"
        }
      }
    ],
    [
      "Indian/Cocos",
      {
        "s": {
          "f": "+0630",
          "n": "+0630"
        }
      }
    ],
    [
      "Indian/Comoro",
      {
        "s": {
          "f": "+0300",
          "n": "EAT"
        }
      }
    ],
    [
      "Indian/Kerguelen",
      {
        "s": {
          "f": "+0500",
          "n": "+05"
        }
      }
    ],
    [
      "Indian/Mahe",
      {
        "s": {
          "f": "+0400",
          "n": "+04"
        }
      }
    ],
    [
      "Indian/Maldives",
      {
        "s": {
          "f": "+0500",
          "n": "+05"
        }
      }
    ],
    [
      "Indian/Mauritius",
      {
        "s": {
          "f": "+0400",
          "n": "+04"
        }
      }
    ],
    [
      "Indian/Mayotte",
      {
        "s": {
          "f": "+0300",
          "n": "EAT"
        }
      }
    ],
    [
      "Indian/Reunion",
      {
        "s": {
          "f": "+0400",
          "n": "+04"
        }
      }
    ],
    [
      "Pacific/Apia",
      {
        "s": {
          "f": "+1400",
          "t": "+1300",
          "n": "+13",
          "s": "19700405T040000",
          "r": {
            "m": 4,
            "d": "1SU"
          }
        },
        "d": {
          "f": "+1300",
          "t": "+1400",
          "n": "+14",
          "s": "19700927T030000",
          "r": {
            "m": 9,
            "d": "-1SU"
          }
        }
      }
    ],
    [
      "Pacific/Auckland",
      {
        "s": {
          "f": "+1300",
          "t": "+1200",
          "n": "NZST",
          "s": "19700405T030000",
          "r": {
            "m": 4,
            "d": "1SU"
          }
        },
        "d": {
          "f": "+1200",
          "t": "+1300",
          "n": "NZDT",
          "s": "19700927T020000",
          "r": {
            "m": 9,
            "d": "-1SU"
          }
        }
      }
    ],
    [
      "Pacific/Bougainville",
      {
        "s": {
          "f": "+1100",
          "n": "+11"
        }
      }
    ],
    [
      "Pacific/Chatham",
      {
        "s": {
          "f": "+1345",
          "t": "+1245",
          "n": "+1245",
          "s": "19700405T034500",
          "r": {
            "m": 4,
            "d": "1SU"
          }
        },
        "d": {
          "f": "+1245",
          "t": "+1345",
          "n": "+1345",
          "s": "19700927T024500",
          "r": {
            "m": 9,
            "d": "-1SU"
          }
        }
      }
    ],
    [
      "Pacific/Chuuk",
      {
        "s": {
          "f": "+1000",
          "n": "+10"
        }
      }
    ],
    [
      "Pacific/Easter",
      {
        "s": {
          "f": "-0500",
          "t": "-0600",
          "n": "-06",
          "s": "19700404T220000",
          "r": {
            "m": 4,
            "d": "1SA"
          }
        },
        "d": {
          "f": "-0600",
          "t": "-0500",
          "n": "-05",
          "s": "19700905T220000",
          "r": {
            "m": 9,
            "d": "1SA"
          }
        }
      }
    ],
    [
      "Pacific/Efate",
      {
        "s": {
          "f": "+1100",
          "n": "+11"
        }
      }
    ],
    [
      "Pacific/Enderbury",
      {
        "s": {
          "f": "+1300",
          "n": "+13"
        }
      }
    ],
    [
      "Pacific/Fakaofo",
      {
        "s": {
          "f": "+1300",
          "n": "+13"
        }
      }
    ],
    [
      "Pacific/Fiji",
      {
        "s": {
          "f": "+1300",
          "t": "+1200",
          "n": "+12",
          "s": "19700118T030000",
          "r": {
            "m": 1,
            "d": "-2SU"
          }
        },
        "d": {
          "f": "+1200",
          "t": "+1300",
          "n": "+13",
          "s": "19701101T020000",
          "r": {
            "m": 11,
            "d": "1SU"
          }
        }
      }
    ],
    [
      "Pacific/Funafuti",
      {
        "s": {
          "f": "+1200",
          "n": "+12"
        }
      }
    ],
    [
      "Pacific/Galapagos",
      {
        "s": {
          "f": "-0600",
          "n": "-06"
        }
      }
    ],
    [
      "Pacific/Gambier",
      {
        "s": {
          "f": "-0900",
          "n": "-09"
        }
      }
    ],
    [
      "Pacific/Guadalcanal",
      {
        "s": {
          "f": "+1100",
          "n": "+11"
        }
      }
    ],
    [
      "Pacific/Guam",
      {
        "s": {
          "f": "+1000",
          "n": "ChST"
        }
      }
    ],
    [
      "Pacific/Honolulu",
      {
        "s": {
          "f": "-1000",
          "n": "HST"
        }
      }
    ],
    [
      "Pacific/Kiritimati",
      {
        "s": {
          "f": "+1400",
          "n": "+14"
        }
      }
    ],
    [
      "Pacific/Kosrae",
      {
        "s": {
          "f": "+1100",
          "n": "+11"
        }
      }
    ],
    [
      "Pacific/Kwajalein",
      {
        "s": {
          "f": "+1200",
          "n": "+12"
        }
      }
    ],
    [
      "Pacific/Majuro",
      {
        "s": {
          "f": "+1200",
          "n": "+12"
        }
      }
    ],
    [
      "Pacific/Marquesas",
      {
        "s": {
          "f": "-0930",
          "n": "-0930"
        }
      }
    ],
    [
      "Pacific/Midway",
      {
        "s": {
          "f": "-1100",
          "n": "SST"
        }
      }
    ],
    [
      "Pacific/Nauru",
      {
        "s": {
          "f": "+1200",
          "n": "+12"
        }
      }
    ],
    [
      "Pacific/Niue",
      {
        "s": {
          "f": "-1100",
          "n": "-11"
        }
      }
    ],
    [
      "Pacific/Norfolk",
      {
        "s": {
          "f": "+1100",
          "n": "+11"
        }
      }
    ],
    [
      "Pacific/Noumea",
      {
        "s": {
          "f": "+1100",
          "n": "+11"
        }
      }
    ],
    [
      "Pacific/Pago_Pago",
      {
        "s": {
          "f": "-1100",
          "n": "SST"
        }
      }
    ],
    [
      "Pacific/Palau",
      {
        "s": {
          "f": "+0900",
          "n": "+09"
        }
      }
    ],
    [
      "Pacific/Pitcairn",
      {
        "s": {
          "f": "-0800",
          "n": "-08"
        }
      }
    ],
    [
      "Pacific/Pohnpei",
      {
        "s": {
          "f": "+1100",
          "n": "+11"
        }
      }
    ],
    [
      "Pacific/Port_Moresby",
      {
        "s": {
          "f": "+1000",
          "n": "+10"
        }
      }
    ],
    [
      "Pacific/Rarotonga",
      {
        "s": {
          "f": "-1000",
          "n": "-10"
        }
      }
    ],
    [
      "Pacific/Saipan",
      {
        "s": {
          "f": "+1000",
          "n": "ChST"
        }
      }
    ],
    [
      "Pacific/Tahiti",
      {
        "s": {
          "f": "-1000",
          "n": "-10"
        }
      }
    ],
    [
      "Pacific/Tarawa",
      {
        "s": {
          "f": "+1200",
          "n": "+12"
        }
      }
    ],
    [
      "Pacific/Tongatapu",
      {
        "s": {
          "f": "+1300",
          "n": "+13"
        }
      }
    ],
    [
      "Pacific/Wake",
      {
        "s": {
          "f": "+1200",
          "n": "+12"
        }
      }
    ],
    [
      "Pacific/Wallis",
      {
        "s": {
          "f": "+1200",
          "n": "+12"
        }
      }
    ]
  ]);
  return zones$1;
}
var hasRequiredDist;
function requireDist() {
  if (hasRequiredDist) return dist;
  hasRequiredDist = 1;
  Object.defineProperty(dist, "__esModule", { value: true });
  dist.getZoneString = dist.getZoneLines = void 0;
  const zones_1 = requireZones();
  function renderZoneSub(data) {
    const { n, f, t, r, s } = data;
    return [
      `TZNAME:${n}`,
      `TZOFFSETFROM:${f}`,
      `TZOFFSETTO:${t || f}`,
      `DTSTART:${s || zones_1.defaultStart}`,
      ...r ? [`RRULE:FREQ=${r.f || "YEARLY"};BYMONTH=${r.m};BYDAY=${r.d}`] : []
    ];
  }
  function getZoneLines(zoneName, includeWrapper = true) {
    const zoneData = zones_1.zonesMap.get(zoneName);
    if (zoneData) {
      const { s, d } = zoneData;
      const lines = [
        ...includeWrapper ? ["BEGIN:VTIMEZONE"] : [],
        `TZID:${zoneName}`,
        // `X-LIC-LOCATION:${zoneName}`, // Who uses this?
        "BEGIN:STANDARD",
        ...renderZoneSub(s),
        "END:STANDARD",
        ...d ? [
          "BEGIN:DAYLIGHT",
          ...renderZoneSub(d),
          "END:DAYLIGHT"
        ] : [],
        ...includeWrapper ? ["END:VTIMEZONE"] : []
      ];
      return lines;
    }
  }
  dist.getZoneLines = getZoneLines;
  function getZoneString(zoneName, includeWrapper = true) {
    const lines = getZoneLines(zoneName, includeWrapper);
    return lines === null || lines === void 0 ? void 0 : lines.join("\r\n");
  }
  dist.getZoneString = getZoneString;
  return dist;
}
var distExports = requireDist();
class Binary {
  /**
   * Creates a binary value from the given string.
   *
   * @param {String} aString        The binary value string
   * @return {Binary}               The binary value instance
   */
  static fromString(aString) {
    return new Binary(aString);
  }
  /**
   * Creates a new ICAL.Binary instance
   *
   * @param {String} aValue     The binary data for this value
   */
  constructor(aValue) {
    this.value = aValue;
  }
  /**
   * The type name, to be used in the jCal object.
   * @default "binary"
   * @constant
   */
  icaltype = "binary";
  /**
   * Base64 decode the current value
   *
   * @return {String}         The base64-decoded value
   */
  decodeValue() {
    return this._b64_decode(this.value);
  }
  /**
   * Encodes the passed parameter with base64 and sets the internal
   * value to the result.
   *
   * @param {String} aValue      The raw binary value to encode
   */
  setEncodedValue(aValue) {
    this.value = this._b64_encode(aValue);
  }
  _b64_encode(data) {
    let b64 = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";
    let o1, o2, o3, h1, h2, h3, h4, bits, i = 0, ac = 0, enc = "", tmp_arr = [];
    if (!data) {
      return data;
    }
    do {
      o1 = data.charCodeAt(i++);
      o2 = data.charCodeAt(i++);
      o3 = data.charCodeAt(i++);
      bits = o1 << 16 | o2 << 8 | o3;
      h1 = bits >> 18 & 63;
      h2 = bits >> 12 & 63;
      h3 = bits >> 6 & 63;
      h4 = bits & 63;
      tmp_arr[ac++] = b64.charAt(h1) + b64.charAt(h2) + b64.charAt(h3) + b64.charAt(h4);
    } while (i < data.length);
    enc = tmp_arr.join("");
    let r = data.length % 3;
    return (r ? enc.slice(0, r - 3) : enc) + "===".slice(r || 3);
  }
  _b64_decode(data) {
    let b64 = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";
    let o1, o2, o3, h1, h2, h3, h4, bits, i = 0, ac = 0, dec = "", tmp_arr = [];
    if (!data) {
      return data;
    }
    data += "";
    do {
      h1 = b64.indexOf(data.charAt(i++));
      h2 = b64.indexOf(data.charAt(i++));
      h3 = b64.indexOf(data.charAt(i++));
      h4 = b64.indexOf(data.charAt(i++));
      bits = h1 << 18 | h2 << 12 | h3 << 6 | h4;
      o1 = bits >> 16 & 255;
      o2 = bits >> 8 & 255;
      o3 = bits & 255;
      if (h3 == 64) {
        tmp_arr[ac++] = String.fromCharCode(o1);
      } else if (h4 == 64) {
        tmp_arr[ac++] = String.fromCharCode(o1, o2);
      } else {
        tmp_arr[ac++] = String.fromCharCode(o1, o2, o3);
      }
    } while (i < data.length);
    dec = tmp_arr.join("");
    return dec;
  }
  /**
   * The string representation of this value
   * @return {String}
   */
  toString() {
    return this.value;
  }
}
const DURATION_LETTERS = /([PDWHMTS]{1,1})/;
const DATA_PROPS_TO_COPY = ["weeks", "days", "hours", "minutes", "seconds", "isNegative"];
class Duration {
  /**
   * Returns a new ICAL.Duration instance from the passed seconds value.
   *
   * @param {Number} aSeconds       The seconds to create the instance from
   * @return {Duration}             The newly created duration instance
   */
  static fromSeconds(aSeconds) {
    return new Duration().fromSeconds(aSeconds);
  }
  /**
   * Checks if the given string is an iCalendar duration value.
   *
   * @param {String} value      The raw ical value
   * @return {Boolean}          True, if the given value is of the
   *                              duration ical type
   */
  static isValueString(string) {
    return string[0] === "P" || string[1] === "P";
  }
  /**
   * Creates a new {@link ICAL.Duration} instance from the passed string.
   *
   * @param {String} aStr       The string to parse
   * @return {Duration}         The created duration instance
   */
  static fromString(aStr) {
    let pos = 0;
    let dict = /* @__PURE__ */ Object.create(null);
    let chunks = 0;
    while ((pos = aStr.search(DURATION_LETTERS)) !== -1) {
      let type = aStr[pos];
      let numeric = aStr.slice(0, Math.max(0, pos));
      aStr = aStr.slice(pos + 1);
      chunks += parseDurationChunk(type, numeric, dict);
    }
    if (chunks < 2) {
      throw new Error(
        'invalid duration value: Not enough duration components in "' + aStr + '"'
      );
    }
    return new Duration(dict);
  }
  /**
   * Creates a new ICAL.Duration instance from the given data object.
   *
   * @param {Object} aData                An object with members of the duration
   * @param {Number=} aData.weeks         Duration in weeks
   * @param {Number=} aData.days          Duration in days
   * @param {Number=} aData.hours         Duration in hours
   * @param {Number=} aData.minutes       Duration in minutes
   * @param {Number=} aData.seconds       Duration in seconds
   * @param {Boolean=} aData.isNegative   If true, the duration is negative
   * @return {Duration}                   The createad duration instance
   */
  static fromData(aData) {
    return new Duration(aData);
  }
  /**
   * Creates a new ICAL.Duration instance.
   *
   * @param {Object} data                 An object with members of the duration
   * @param {Number=} data.weeks          Duration in weeks
   * @param {Number=} data.days           Duration in days
   * @param {Number=} data.hours          Duration in hours
   * @param {Number=} data.minutes        Duration in minutes
   * @param {Number=} data.seconds        Duration in seconds
   * @param {Boolean=} data.isNegative    If true, the duration is negative
   */
  constructor(data) {
    this.wrappedJSObject = this;
    this.fromData(data);
  }
  /**
   * The weeks in this duration
   * @type {Number}
   * @default 0
   */
  weeks = 0;
  /**
   * The days in this duration
   * @type {Number}
   * @default 0
   */
  days = 0;
  /**
   * The days in this duration
   * @type {Number}
   * @default 0
   */
  hours = 0;
  /**
   * The minutes in this duration
   * @type {Number}
   * @default 0
   */
  minutes = 0;
  /**
   * The seconds in this duration
   * @type {Number}
   * @default 0
   */
  seconds = 0;
  /**
   * The seconds in this duration
   * @type {Boolean}
   * @default false
   */
  isNegative = false;
  /**
   * The class identifier.
   * @constant
   * @type {String}
   * @default "icalduration"
   */
  icalclass = "icalduration";
  /**
   * The type name, to be used in the jCal object.
   * @constant
   * @type {String}
   * @default "duration"
   */
  icaltype = "duration";
  /**
   * Returns a clone of the duration object.
   *
   * @return {Duration}      The cloned object
   */
  clone() {
    return Duration.fromData(this);
  }
  /**
   * The duration value expressed as a number of seconds.
   *
   * @return {Number}             The duration value in seconds
   */
  toSeconds() {
    let seconds = this.seconds + 60 * this.minutes + 3600 * this.hours + 86400 * this.days + 7 * 86400 * this.weeks;
    return this.isNegative ? -seconds : seconds;
  }
  /**
   * Reads the passed seconds value into this duration object. Afterwards,
   * members like {@link ICAL.Duration#days days} and {@link ICAL.Duration#weeks weeks} will be set up
   * accordingly.
   *
   * @param {Number} aSeconds     The duration value in seconds
   * @return {Duration}           Returns this instance
   */
  fromSeconds(aSeconds) {
    let secs = Math.abs(aSeconds);
    this.isNegative = aSeconds < 0;
    this.days = trunc(secs / 86400);
    if (this.days % 7 == 0) {
      this.weeks = this.days / 7;
      this.days = 0;
    } else {
      this.weeks = 0;
    }
    secs -= (this.days + 7 * this.weeks) * 86400;
    this.hours = trunc(secs / 3600);
    secs -= this.hours * 3600;
    this.minutes = trunc(secs / 60);
    secs -= this.minutes * 60;
    this.seconds = secs;
    return this;
  }
  /**
   * Sets up the current instance using members from the passed data object.
   *
   * @param {Object} aData                An object with members of the duration
   * @param {Number=} aData.weeks         Duration in weeks
   * @param {Number=} aData.days          Duration in days
   * @param {Number=} aData.hours         Duration in hours
   * @param {Number=} aData.minutes       Duration in minutes
   * @param {Number=} aData.seconds       Duration in seconds
   * @param {Boolean=} aData.isNegative   If true, the duration is negative
   */
  fromData(aData) {
    for (let prop of DATA_PROPS_TO_COPY) {
      if (aData && prop in aData) {
        this[prop] = aData[prop];
      } else {
        this[prop] = 0;
      }
    }
  }
  /**
   * Resets the duration instance to the default values, i.e. PT0S
   */
  reset() {
    this.isNegative = false;
    this.weeks = 0;
    this.days = 0;
    this.hours = 0;
    this.minutes = 0;
    this.seconds = 0;
  }
  /**
   * Compares the duration instance with another one.
   *
   * @param {Duration} aOther             The instance to compare with
   * @return {Number}                     -1, 0 or 1 for less/equal/greater
   */
  compare(aOther) {
    let thisSeconds = this.toSeconds();
    let otherSeconds = aOther.toSeconds();
    return (thisSeconds > otherSeconds) - (thisSeconds < otherSeconds);
  }
  /**
   * Normalizes the duration instance. For example, a duration with a value
   * of 61 seconds will be normalized to 1 minute and 1 second.
   */
  normalize() {
    this.fromSeconds(this.toSeconds());
  }
  /**
   * The string representation of this duration.
   * @return {String}
   */
  toString() {
    if (this.toSeconds() == 0) {
      return "PT0S";
    } else {
      let str = "";
      if (this.isNegative) str += "-";
      str += "P";
      let hasWeeks = false;
      if (this.weeks) {
        if (this.days || this.hours || this.minutes || this.seconds) {
          str += this.weeks * 7 + this.days + "D";
        } else {
          str += this.weeks + "W";
          hasWeeks = true;
        }
      } else if (this.days) {
        str += this.days + "D";
      }
      if (!hasWeeks) {
        if (this.hours || this.minutes || this.seconds) {
          str += "T";
          if (this.hours) {
            str += this.hours + "H";
          }
          if (this.minutes) {
            str += this.minutes + "M";
          }
          if (this.seconds) {
            str += this.seconds + "S";
          }
        }
      }
      return str;
    }
  }
  /**
   * The iCalendar string representation of this duration.
   * @return {String}
   */
  toICALString() {
    return this.toString();
  }
}
function parseDurationChunk(letter, number, object) {
  let type;
  switch (letter) {
    case "P":
      if (number && number === "-") {
        object.isNegative = true;
      } else {
        object.isNegative = false;
      }
      break;
    case "D":
      type = "days";
      break;
    case "W":
      type = "weeks";
      break;
    case "H":
      type = "hours";
      break;
    case "M":
      type = "minutes";
      break;
    case "S":
      type = "seconds";
      break;
    default:
      return 0;
  }
  if (type) {
    if (!number && number !== 0) {
      throw new Error(
        'invalid duration value: Missing number before "' + letter + '"'
      );
    }
    let num = parseInt(number, 10);
    if (isStrictlyNaN(num)) {
      throw new Error(
        'invalid duration value: Invalid number "' + number + '" before "' + letter + '"'
      );
    }
    object[type] = num;
  }
  return 1;
}
class Period {
  /**
   * Creates a new {@link ICAL.Period} instance from the passed string.
   *
   * @param {String} str            The string to parse
   * @param {Property} prop         The property this period will be on
   * @return {Period}               The created period instance
   */
  static fromString(str, prop) {
    let parts = str.split("/");
    if (parts.length !== 2) {
      throw new Error(
        'Invalid string value: "' + str + '" must contain a "/" char.'
      );
    }
    let options = {
      start: Time.fromDateTimeString(parts[0], prop)
    };
    let end = parts[1];
    if (Duration.isValueString(end)) {
      options.duration = Duration.fromString(end);
    } else {
      options.end = Time.fromDateTimeString(end, prop);
    }
    return new Period(options);
  }
  /**
   * Creates a new {@link ICAL.Period} instance from the given data object.
   * The passed data object cannot contain both and end date and a duration.
   *
   * @param {Object} aData                  An object with members of the period
   * @param {Time=} aData.start             The start of the period
   * @param {Time=} aData.end               The end of the period
   * @param {Duration=} aData.duration      The duration of the period
   * @return {Period}                       The period instance
   */
  static fromData(aData) {
    return new Period(aData);
  }
  /**
   * Returns a new period instance from the given jCal data array. The first
   * member is always the start date string, the second member is either a
   * duration or end date string.
   *
   * @param {jCalComponent} aData           The jCal data array
   * @param {Property} aProp                The property this jCal data is on
   * @param {Boolean} aLenient              If true, data value can be both date and date-time
   * @return {Period}                       The period instance
   */
  static fromJSON(aData, aProp, aLenient) {
    function fromDateOrDateTimeString(aValue, dateProp) {
      if (aLenient) {
        return Time.fromString(aValue, dateProp);
      } else {
        return Time.fromDateTimeString(aValue, dateProp);
      }
    }
    if (Duration.isValueString(aData[1])) {
      return Period.fromData({
        start: fromDateOrDateTimeString(aData[0], aProp),
        duration: Duration.fromString(aData[1])
      });
    } else {
      return Period.fromData({
        start: fromDateOrDateTimeString(aData[0], aProp),
        end: fromDateOrDateTimeString(aData[1], aProp)
      });
    }
  }
  /**
   * Creates a new ICAL.Period instance. The passed data object cannot contain both and end date and
   * a duration.
   *
   * @param {Object} aData                  An object with members of the period
   * @param {Time=} aData.start             The start of the period
   * @param {Time=} aData.end               The end of the period
   * @param {Duration=} aData.duration      The duration of the period
   */
  constructor(aData) {
    this.wrappedJSObject = this;
    if (aData && "start" in aData) {
      if (aData.start && !(aData.start instanceof Time)) {
        throw new TypeError(".start must be an instance of ICAL.Time");
      }
      this.start = aData.start;
    }
    if (aData && aData.end && aData.duration) {
      throw new Error("cannot accept both end and duration");
    }
    if (aData && "end" in aData) {
      if (aData.end && !(aData.end instanceof Time)) {
        throw new TypeError(".end must be an instance of ICAL.Time");
      }
      this.end = aData.end;
    }
    if (aData && "duration" in aData) {
      if (aData.duration && !(aData.duration instanceof Duration)) {
        throw new TypeError(".duration must be an instance of ICAL.Duration");
      }
      this.duration = aData.duration;
    }
  }
  /**
   * The start of the period
   * @type {Time}
   */
  start = null;
  /**
   * The end of the period
   * @type {Time}
   */
  end = null;
  /**
   * The duration of the period
   * @type {Duration}
   */
  duration = null;
  /**
   * The class identifier.
   * @constant
   * @type {String}
   * @default "icalperiod"
   */
  icalclass = "icalperiod";
  /**
   * The type name, to be used in the jCal object.
   * @constant
   * @type {String}
   * @default "period"
   */
  icaltype = "period";
  /**
   * Returns a clone of the duration object.
   *
   * @return {Period}      The cloned object
   */
  clone() {
    return Period.fromData({
      start: this.start ? this.start.clone() : null,
      end: this.end ? this.end.clone() : null,
      duration: this.duration ? this.duration.clone() : null
    });
  }
  /**
   * Calculates the duration of the period, either directly or by subtracting
   * start from end date.
   *
   * @return {Duration}      The calculated duration
   */
  getDuration() {
    if (this.duration) {
      return this.duration;
    } else {
      return this.end.subtractDate(this.start);
    }
  }
  /**
   * Calculates the end date of the period, either directly or by adding
   * duration to start date.
   *
   * @return {Time}          The calculated end date
   */
  getEnd() {
    if (this.end) {
      return this.end;
    } else {
      let end = this.start.clone();
      end.addDuration(this.duration);
      return end;
    }
  }
  /**
   * Compare this period with a date or other period. To maintain the logic where a.compare(b)
   * returns 1 when a > b, this function will return 1 when the period is after the date, 0 when the
   * date is within the period, and -1 when the period is before the date. When comparing two
   * periods, as soon as they overlap in any way this will return 0.
   *
   * @param {Time|Period} dt    The date or other period to compare with
   */
  compare(dt) {
    if (dt.compare(this.start) < 0) {
      return 1;
    } else if (dt.compare(this.getEnd()) > 0) {
      return -1;
    } else {
      return 0;
    }
  }
  /**
   * The string representation of this period.
   * @return {String}
   */
  toString() {
    return this.start + "/" + (this.end || this.duration);
  }
  /**
   * The jCal representation of this period type.
   * @return {Object}
   */
  toJSON() {
    return [this.start.toString(), (this.end || this.duration).toString()];
  }
  /**
   * The iCalendar string representation of this period.
   * @return {String}
   */
  toICALString() {
    return this.start.toICALString() + "/" + (this.end || this.duration).toICALString();
  }
}
class Time {
  static _dowCache = {};
  static _wnCache = {};
  /**
   * Returns the days in the given month
   *
   * @param {Number} month      The month to check
   * @param {Number} year       The year to check
   * @return {Number}           The number of days in the month
   */
  static daysInMonth(month, year) {
    let _daysInMonth = [0, 31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
    let days = 30;
    if (month < 1 || month > 12) return days;
    days = _daysInMonth[month];
    if (month == 2) {
      days += Time.isLeapYear(year);
    }
    return days;
  }
  /**
   * Checks if the year is a leap year
   *
   * @param {Number} year       The year to check
   * @return {Boolean}          True, if the year is a leap year
   */
  static isLeapYear(year) {
    if (year <= 1752) {
      return year % 4 == 0;
    } else {
      return year % 4 == 0 && year % 100 != 0 || year % 400 == 0;
    }
  }
  /**
   * Create a new ICAL.Time from the day of year and year. The date is returned
   * in floating timezone.
   *
   * @param {Number} aDayOfYear     The day of year
   * @param {Number} aYear          The year to create the instance in
   * @return {Time}                 The created instance with the calculated date
   */
  static fromDayOfYear(aDayOfYear, aYear) {
    let year = aYear;
    let doy = aDayOfYear;
    let tt = new Time();
    tt.auto_normalize = false;
    let is_leap = Time.isLeapYear(year) ? 1 : 0;
    if (doy < 1) {
      year--;
      is_leap = Time.isLeapYear(year) ? 1 : 0;
      doy += Time.daysInYearPassedMonth[is_leap][12];
      return Time.fromDayOfYear(doy, year);
    } else if (doy > Time.daysInYearPassedMonth[is_leap][12]) {
      is_leap = Time.isLeapYear(year) ? 1 : 0;
      doy -= Time.daysInYearPassedMonth[is_leap][12];
      year++;
      return Time.fromDayOfYear(doy, year);
    }
    tt.year = year;
    tt.isDate = true;
    for (let month = 11; month >= 0; month--) {
      if (doy > Time.daysInYearPassedMonth[is_leap][month]) {
        tt.month = month + 1;
        tt.day = doy - Time.daysInYearPassedMonth[is_leap][month];
        break;
      }
    }
    tt.auto_normalize = true;
    return tt;
  }
  /**
   * Returns a new ICAL.Time instance from a date string, e.g 2015-01-02.
   *
   * @deprecated                Use {@link ICAL.Time.fromDateString} instead
   * @param {String} str        The string to create from
   * @return {Time}             The date/time instance
   */
  static fromStringv2(str) {
    return new Time({
      year: parseInt(str.slice(0, 4), 10),
      month: parseInt(str.slice(5, 7), 10),
      day: parseInt(str.slice(8, 10), 10),
      isDate: true
    });
  }
  /**
   * Returns a new ICAL.Time instance from a date string, e.g 2015-01-02.
   *
   * @param {String} aValue     The string to create from
   * @return {Time}             The date/time instance
   */
  static fromDateString(aValue) {
    return new Time({
      year: strictParseInt(aValue.slice(0, 4)),
      month: strictParseInt(aValue.slice(5, 7)),
      day: strictParseInt(aValue.slice(8, 10)),
      isDate: true
    });
  }
  /**
   * Returns a new ICAL.Time instance from a date-time string, e.g
   * 2015-01-02T03:04:05. If a property is specified, the timezone is set up
   * from the property's TZID parameter.
   *
   * @param {String} aValue         The string to create from
   * @param {Property=} prop        The property the date belongs to
   * @return {Time}                 The date/time instance
   */
  static fromDateTimeString(aValue, prop) {
    if (aValue.length < 19) {
      throw new Error(
        'invalid date-time value: "' + aValue + '"'
      );
    }
    let zone;
    let zoneId;
    if (aValue.slice(-1) === "Z") {
      zone = Timezone.utcTimezone;
    } else if (prop) {
      zoneId = prop.getParameter("tzid");
      if (prop.parent) {
        if (prop.parent.name === "standard" || prop.parent.name === "daylight") {
          zone = Timezone.localTimezone;
        } else if (zoneId) {
          zone = prop.parent.getTimeZoneByID(zoneId);
        }
      }
    }
    const timeData = {
      year: strictParseInt(aValue.slice(0, 4)),
      month: strictParseInt(aValue.slice(5, 7)),
      day: strictParseInt(aValue.slice(8, 10)),
      hour: strictParseInt(aValue.slice(11, 13)),
      minute: strictParseInt(aValue.slice(14, 16)),
      second: strictParseInt(aValue.slice(17, 19))
    };
    if (zoneId && !zone) {
      timeData.timezone = zoneId;
    }
    return new Time(timeData, zone);
  }
  /**
   * Returns a new ICAL.Time instance from a date or date-time string,
   *
   * @param {String} aValue         The string to create from
   * @param {Property=} prop        The property the date belongs to
   * @return {Time}                 The date/time instance
   */
  static fromString(aValue, aProperty) {
    if (aValue.length > 10) {
      return Time.fromDateTimeString(aValue, aProperty);
    } else {
      return Time.fromDateString(aValue);
    }
  }
  /**
   * Creates a new ICAL.Time instance from the given Javascript Date.
   *
   * @param {?Date} aDate             The Javascript Date to read, or null to reset
   * @param {Boolean} [useUTC=false]  If true, the UTC values of the date will be used
   */
  static fromJSDate(aDate, useUTC) {
    let tt = new Time();
    return tt.fromJSDate(aDate, useUTC);
  }
  /**
   * Creates a new ICAL.Time instance from the the passed data object.
   *
   * @param {timeInit} aData          Time initialization
   * @param {Timezone=} aZone         Timezone this position occurs in
   */
  static fromData = function fromData(aData, aZone) {
    let t = new Time();
    return t.fromData(aData, aZone);
  };
  /**
   * Creates a new ICAL.Time instance from the current moment.
   * The instance is “floating” - has no timezone relation.
   * To create an instance considering the time zone, call
   * ICAL.Time.fromJSDate(new Date(), true)
   * @return {Time}
   */
  static now() {
    return Time.fromJSDate(/* @__PURE__ */ new Date(), false);
  }
  /**
   * Returns the date on which ISO week number 1 starts.
   *
   * @see Time#weekNumber
   * @param {Number} aYear                  The year to search in
   * @param {weekDay=} aWeekStart           The week start weekday, used for calculation.
   * @return {Time}                         The date on which week number 1 starts
   */
  static weekOneStarts(aYear, aWeekStart) {
    let t = Time.fromData({
      year: aYear,
      month: 1,
      day: 1,
      isDate: true
    });
    let dow = t.dayOfWeek();
    let wkst = aWeekStart || Time.DEFAULT_WEEK_START;
    if (dow > Time.THURSDAY) {
      t.day += 7;
    }
    if (wkst > Time.THURSDAY) {
      t.day -= 7;
    }
    t.day -= dow - wkst;
    return t;
  }
  /**
   * Get the dominical letter for the given year. Letters range from A - G for
   * common years, and AG to GF for leap years.
   *
   * @param {Number} yr           The year to retrieve the letter for
   * @return {String}             The dominical letter.
   */
  static getDominicalLetter(yr) {
    let LTRS = "GFEDCBA";
    let dom = (yr + (yr / 4 | 0) + (yr / 400 | 0) - (yr / 100 | 0) - 1) % 7;
    let isLeap = Time.isLeapYear(yr);
    if (isLeap) {
      return LTRS[(dom + 6) % 7] + LTRS[dom];
    } else {
      return LTRS[dom];
    }
  }
  static #epochTime = null;
  /**
   * January 1st, 1970 as an ICAL.Time.
   * @type {Time}
   * @constant
   * @instance
   */
  static get epochTime() {
    if (!this.#epochTime) {
      this.#epochTime = Time.fromData({
        year: 1970,
        month: 1,
        day: 1,
        hour: 0,
        minute: 0,
        second: 0,
        isDate: false,
        timezone: "Z"
      });
    }
    return this.#epochTime;
  }
  static _cmp_attr(a, b, attr) {
    if (a[attr] > b[attr]) return 1;
    if (a[attr] < b[attr]) return -1;
    return 0;
  }
  /**
   * The days that have passed in the year after a given month. The array has
   * two members, one being an array of passed days for non-leap years, the
   * other analog for leap years.
   * @example
   * var isLeapYear = ICAL.Time.isLeapYear(year);
   * var passedDays = ICAL.Time.daysInYearPassedMonth[isLeapYear][month];
   * @type {Array.<Array.<Number>>}
   */
  static daysInYearPassedMonth = [
    [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334, 365],
    [0, 31, 60, 91, 121, 152, 182, 213, 244, 274, 305, 335, 366]
  ];
  static SUNDAY = 1;
  static MONDAY = 2;
  static TUESDAY = 3;
  static WEDNESDAY = 4;
  static THURSDAY = 5;
  static FRIDAY = 6;
  static SATURDAY = 7;
  /**
   * The default weekday for the WKST part.
   * @constant
   * @default ICAL.Time.MONDAY
   */
  static DEFAULT_WEEK_START = 2;
  // MONDAY
  /**
   * Creates a new ICAL.Time instance.
   *
   * @param {timeInit} data           Time initialization
   * @param {Timezone} zone           timezone this position occurs in
   */
  constructor(data, zone) {
    this.wrappedJSObject = this;
    this._time = /* @__PURE__ */ Object.create(null);
    this._time.year = 0;
    this._time.month = 1;
    this._time.day = 1;
    this._time.hour = 0;
    this._time.minute = 0;
    this._time.second = 0;
    this._time.isDate = false;
    this.fromData(data, zone);
  }
  /**
   * The class identifier.
   * @constant
   * @type {String}
   * @default "icaltime"
   */
  icalclass = "icaltime";
  _cachedUnixTime = null;
  /**
   * The type name, to be used in the jCal object. This value may change and
   * is strictly defined by the {@link ICAL.Time#isDate isDate} member.
   * @type {String}
   * @default "date-time"
   */
  get icaltype() {
    return this.isDate ? "date" : "date-time";
  }
  /**
   * The timezone for this time.
   * @type {Timezone}
   */
  zone = null;
  /**
   * Internal uses to indicate that a change has been made and the next read
   * operation must attempt to normalize the value (for example changing the
   * day to 33).
   *
   * @type {Boolean}
   * @private
   */
  _pendingNormalization = false;
  /**
   * The year of this date.
   * @type {Number}
   */
  get year() {
    return this._getTimeAttr("year");
  }
  set year(val) {
    this._setTimeAttr("year", val);
  }
  /**
   * The month of this date.
   * @type {Number}
   */
  get month() {
    return this._getTimeAttr("month");
  }
  set month(val) {
    this._setTimeAttr("month", val);
  }
  /**
   * The day of this date.
   * @type {Number}
   */
  get day() {
    return this._getTimeAttr("day");
  }
  set day(val) {
    this._setTimeAttr("day", val);
  }
  /**
   * The hour of this date-time.
   * @type {Number}
   */
  get hour() {
    return this._getTimeAttr("hour");
  }
  set hour(val) {
    this._setTimeAttr("hour", val);
  }
  /**
   * The minute of this date-time.
   * @type {Number}
   */
  get minute() {
    return this._getTimeAttr("minute");
  }
  set minute(val) {
    this._setTimeAttr("minute", val);
  }
  /**
   * The second of this date-time.
   * @type {Number}
   */
  get second() {
    return this._getTimeAttr("second");
  }
  set second(val) {
    this._setTimeAttr("second", val);
  }
  /**
   * If true, the instance represents a date (as opposed to a date-time)
   * @type {Boolean}
   */
  get isDate() {
    return this._getTimeAttr("isDate");
  }
  set isDate(val) {
    this._setTimeAttr("isDate", val);
  }
  /**
   * @private
   * @param {String} attr             Attribute to get (one of: year, month,
   *                                  day, hour, minute, second, isDate)
   * @return {Number|Boolean}         Current value for the attribute
   */
  _getTimeAttr(attr) {
    if (this._pendingNormalization) {
      this._normalize();
      this._pendingNormalization = false;
    }
    return this._time[attr];
  }
  /**
   * @private
   * @param {String} attr             Attribute to set (one of: year, month,
   *                                  day, hour, minute, second, isDate)
   * @param {Number|Boolean} val      New value for the attribute
   */
  _setTimeAttr(attr, val) {
    if (attr === "isDate" && val && !this._time.isDate) {
      this.adjust(0, 0, 0, 0);
    }
    this._cachedUnixTime = null;
    this._pendingNormalization = true;
    this._time[attr] = val;
  }
  /**
   * Returns a clone of the time object.
   *
   * @return {Time}              The cloned object
   */
  clone() {
    return new Time(this._time, this.zone);
  }
  /**
   * Reset the time instance to epoch time
   */
  reset() {
    this.fromData(Time.epochTime);
    this.zone = Timezone.utcTimezone;
  }
  /**
   * Reset the time instance to the given date/time values.
   *
   * @param {Number} year             The year to set
   * @param {Number} month            The month to set
   * @param {Number} day              The day to set
   * @param {Number} hour             The hour to set
   * @param {Number} minute           The minute to set
   * @param {Number} second           The second to set
   * @param {Timezone} timezone       The timezone to set
   */
  resetTo(year, month, day, hour, minute, second, timezone) {
    this.fromData({
      year,
      month,
      day,
      hour,
      minute,
      second,
      zone: timezone
    });
  }
  /**
   * Set up the current instance from the Javascript date value.
   *
   * @param {?Date} aDate             The Javascript Date to read, or null to reset
   * @param {Boolean} [useUTC=false]  If true, the UTC values of the date will be used
   */
  fromJSDate(aDate, useUTC) {
    if (!aDate) {
      this.reset();
    } else {
      if (useUTC) {
        this.zone = Timezone.utcTimezone;
        this.year = aDate.getUTCFullYear();
        this.month = aDate.getUTCMonth() + 1;
        this.day = aDate.getUTCDate();
        this.hour = aDate.getUTCHours();
        this.minute = aDate.getUTCMinutes();
        this.second = aDate.getUTCSeconds();
      } else {
        this.zone = Timezone.localTimezone;
        this.year = aDate.getFullYear();
        this.month = aDate.getMonth() + 1;
        this.day = aDate.getDate();
        this.hour = aDate.getHours();
        this.minute = aDate.getMinutes();
        this.second = aDate.getSeconds();
      }
    }
    this._cachedUnixTime = null;
    return this;
  }
  /**
   * Sets up the current instance using members from the passed data object.
   *
   * @param {timeInit} aData          Time initialization
   * @param {Timezone=} aZone         Timezone this position occurs in
   */
  fromData(aData, aZone) {
    if (aData) {
      for (let [key, value] of Object.entries(aData)) {
        if (key === "icaltype") continue;
        this[key] = value;
      }
    }
    if (aZone) {
      this.zone = aZone;
    }
    if (aData && !("isDate" in aData)) {
      this.isDate = !("hour" in aData);
    } else if (aData && "isDate" in aData) {
      this.isDate = aData.isDate;
    }
    if (aData && "timezone" in aData) {
      let zone = TimezoneService.get(
        aData.timezone
      );
      this.zone = zone || Timezone.localTimezone;
    }
    if (aData && "zone" in aData) {
      this.zone = aData.zone;
    }
    if (!this.zone) {
      this.zone = Timezone.localTimezone;
    }
    this._cachedUnixTime = null;
    return this;
  }
  /**
   * Calculate the day of week.
   * @param {weekDay=} aWeekStart
   *        The week start weekday, defaults to SUNDAY
   * @return {weekDay}
   */
  dayOfWeek(aWeekStart) {
    let firstDow = aWeekStart || Time.SUNDAY;
    let dowCacheKey = (this.year << 12) + (this.month << 8) + (this.day << 3) + firstDow;
    if (dowCacheKey in Time._dowCache) {
      return Time._dowCache[dowCacheKey];
    }
    let q = this.day;
    let m = this.month + (this.month < 3 ? 12 : 0);
    let Y = this.year - (this.month < 3 ? 1 : 0);
    let h = q + Y + trunc((m + 1) * 26 / 10) + trunc(Y / 4);
    {
      h += trunc(Y / 100) * 6 + trunc(Y / 400);
    }
    h = (h + 7 - firstDow) % 7 + 1;
    Time._dowCache[dowCacheKey] = h;
    return h;
  }
  /**
   * Calculate the day of year.
   * @return {Number}
   */
  dayOfYear() {
    let is_leap = Time.isLeapYear(this.year) ? 1 : 0;
    let diypm = Time.daysInYearPassedMonth;
    return diypm[is_leap][this.month - 1] + this.day;
  }
  /**
   * Returns a copy of the current date/time, rewound to the start of the
   * week. The resulting ICAL.Time instance is of icaltype date, even if this
   * is a date-time.
   *
   * @param {weekDay=} aWeekStart
   *        The week start weekday, defaults to SUNDAY
   * @return {Time}      The start of the week (cloned)
   */
  startOfWeek(aWeekStart) {
    let firstDow = aWeekStart || Time.SUNDAY;
    let result = this.clone();
    result.day -= (this.dayOfWeek() + 7 - firstDow) % 7;
    result.isDate = true;
    result.hour = 0;
    result.minute = 0;
    result.second = 0;
    return result;
  }
  /**
   * Returns a copy of the current date/time, shifted to the end of the week.
   * The resulting ICAL.Time instance is of icaltype date, even if this is a
   * date-time.
   *
   * @param {weekDay=} aWeekStart
   *        The week start weekday, defaults to SUNDAY
   * @return {Time}      The end of the week (cloned)
   */
  endOfWeek(aWeekStart) {
    let firstDow = aWeekStart || Time.SUNDAY;
    let result = this.clone();
    result.day += (7 - this.dayOfWeek() + firstDow - Time.SUNDAY) % 7;
    result.isDate = true;
    result.hour = 0;
    result.minute = 0;
    result.second = 0;
    return result;
  }
  /**
   * Returns a copy of the current date/time, rewound to the start of the
   * month. The resulting ICAL.Time instance is of icaltype date, even if
   * this is a date-time.
   *
   * @return {Time}      The start of the month (cloned)
   */
  startOfMonth() {
    let result = this.clone();
    result.day = 1;
    result.isDate = true;
    result.hour = 0;
    result.minute = 0;
    result.second = 0;
    return result;
  }
  /**
   * Returns a copy of the current date/time, shifted to the end of the
   * month.  The resulting ICAL.Time instance is of icaltype date, even if
   * this is a date-time.
   *
   * @return {Time}      The end of the month (cloned)
   */
  endOfMonth() {
    let result = this.clone();
    result.day = Time.daysInMonth(result.month, result.year);
    result.isDate = true;
    result.hour = 0;
    result.minute = 0;
    result.second = 0;
    return result;
  }
  /**
   * Returns a copy of the current date/time, rewound to the start of the
   * year. The resulting ICAL.Time instance is of icaltype date, even if
   * this is a date-time.
   *
   * @return {Time}      The start of the year (cloned)
   */
  startOfYear() {
    let result = this.clone();
    result.day = 1;
    result.month = 1;
    result.isDate = true;
    result.hour = 0;
    result.minute = 0;
    result.second = 0;
    return result;
  }
  /**
   * Returns a copy of the current date/time, shifted to the end of the
   * year.  The resulting ICAL.Time instance is of icaltype date, even if
   * this is a date-time.
   *
   * @return {Time}      The end of the year (cloned)
   */
  endOfYear() {
    let result = this.clone();
    result.day = 31;
    result.month = 12;
    result.isDate = true;
    result.hour = 0;
    result.minute = 0;
    result.second = 0;
    return result;
  }
  /**
   * First calculates the start of the week, then returns the day of year for
   * this date. If the day falls into the previous year, the day is zero or negative.
   *
   * @param {weekDay=} aFirstDayOfWeek
   *        The week start weekday, defaults to SUNDAY
   * @return {Number}     The calculated day of year
   */
  startDoyWeek(aFirstDayOfWeek) {
    let firstDow = aFirstDayOfWeek || Time.SUNDAY;
    let delta = this.dayOfWeek() - firstDow;
    if (delta < 0) delta += 7;
    return this.dayOfYear() - delta;
  }
  /**
   * Get the dominical letter for the current year. Letters range from A - G
   * for common years, and AG to GF for leap years.
   *
   * @param {Number} yr           The year to retrieve the letter for
   * @return {String}             The dominical letter.
   */
  getDominicalLetter() {
    return Time.getDominicalLetter(this.year);
  }
  /**
   * Finds the nthWeekDay relative to the current month (not day).  The
   * returned value is a day relative the month that this month belongs to so
   * 1 would indicate the first of the month and 40 would indicate a day in
   * the following month.
   *
   * @param {Number} aDayOfWeek   Day of the week see the day name constants
   * @param {Number} aPos         Nth occurrence of a given week day values
   *        of 1 and 0 both indicate the first weekday of that type. aPos may
   *        be either positive or negative
   *
   * @return {Number} numeric value indicating a day relative
   *                   to the current month of this time object
   */
  nthWeekDay(aDayOfWeek, aPos) {
    let daysInMonth = Time.daysInMonth(this.month, this.year);
    let weekday;
    let pos = aPos;
    let start = 0;
    let otherDay = this.clone();
    if (pos >= 0) {
      otherDay.day = 1;
      if (pos != 0) {
        pos--;
      }
      start = otherDay.day;
      let startDow = otherDay.dayOfWeek();
      let offset = aDayOfWeek - startDow;
      if (offset < 0)
        offset += 7;
      start += offset;
      start -= aDayOfWeek;
      weekday = aDayOfWeek;
    } else {
      otherDay.day = daysInMonth;
      let endDow = otherDay.dayOfWeek();
      pos++;
      weekday = endDow - aDayOfWeek;
      if (weekday < 0) {
        weekday += 7;
      }
      weekday = daysInMonth - weekday;
    }
    weekday += pos * 7;
    return start + weekday;
  }
  /**
   * Checks if current time is the nth weekday, relative to the current
   * month.  Will always return false when rule resolves outside of current
   * month.
   *
   * @param {weekDay} aDayOfWeek                 Day of week to check
   * @param {Number} aPos                        Relative position
   * @return {Boolean}                           True, if it is the nth weekday
   */
  isNthWeekDay(aDayOfWeek, aPos) {
    let dow = this.dayOfWeek();
    if (aPos === 0 && dow === aDayOfWeek) {
      return true;
    }
    let day = this.nthWeekDay(aDayOfWeek, aPos);
    if (day === this.day) {
      return true;
    }
    return false;
  }
  /**
   * Calculates the ISO 8601 week number. The first week of a year is the
   * week that contains the first Thursday. The year can have 53 weeks, if
   * January 1st is a Friday.
   *
   * Note there are regions where the first week of the year is the one that
   * starts on January 1st, which may offset the week number. Also, if a
   * different week start is specified, this will also affect the week
   * number.
   *
   * @see Time.weekOneStarts
   * @param {weekDay} aWeekStart                  The weekday the week starts with
   * @return {Number}                             The ISO week number
   */
  weekNumber(aWeekStart) {
    let wnCacheKey = (this.year << 12) + (this.month << 8) + (this.day << 3) + aWeekStart;
    if (wnCacheKey in Time._wnCache) {
      return Time._wnCache[wnCacheKey];
    }
    let week1;
    let dt = this.clone();
    dt.isDate = true;
    let isoyear = this.year;
    if (dt.month == 12 && dt.day > 25) {
      week1 = Time.weekOneStarts(isoyear + 1, aWeekStart);
      if (dt.compare(week1) < 0) {
        week1 = Time.weekOneStarts(isoyear, aWeekStart);
      } else {
        isoyear++;
      }
    } else {
      week1 = Time.weekOneStarts(isoyear, aWeekStart);
      if (dt.compare(week1) < 0) {
        week1 = Time.weekOneStarts(--isoyear, aWeekStart);
      }
    }
    let daysBetween = dt.subtractDate(week1).toSeconds() / 86400;
    let answer = trunc(daysBetween / 7) + 1;
    Time._wnCache[wnCacheKey] = answer;
    return answer;
  }
  /**
   * Adds the duration to the current time. The instance is modified in
   * place.
   *
   * @param {Duration} aDuration         The duration to add
   */
  addDuration(aDuration) {
    let mult = aDuration.isNegative ? -1 : 1;
    let second = this.second;
    let minute = this.minute;
    let hour = this.hour;
    let day = this.day;
    second += mult * aDuration.seconds;
    minute += mult * aDuration.minutes;
    hour += mult * aDuration.hours;
    day += mult * aDuration.days;
    day += mult * 7 * aDuration.weeks;
    this.second = second;
    this.minute = minute;
    this.hour = hour;
    this.day = day;
    this._cachedUnixTime = null;
  }
  /**
   * Subtract the date details (_excluding_ timezone).  Useful for finding
   * the relative difference between two time objects excluding their
   * timezone differences.
   *
   * @param {Time} aDate     The date to subtract
   * @return {Duration}      The difference as a duration
   */
  subtractDate(aDate) {
    let unixTime = this.toUnixTime() + this.utcOffset();
    let other = aDate.toUnixTime() + aDate.utcOffset();
    return Duration.fromSeconds(unixTime - other);
  }
  /**
   * Subtract the date details, taking timezones into account.
   *
   * @param {Time} aDate  The date to subtract
   * @return {Duration}   The difference in duration
   */
  subtractDateTz(aDate) {
    let unixTime = this.toUnixTime();
    let other = aDate.toUnixTime();
    return Duration.fromSeconds(unixTime - other);
  }
  /**
   * Compares the ICAL.Time instance with another one, or a period.
   *
   * @param {Time|Period} aOther                  The instance to compare with
   * @return {Number}                             -1, 0 or 1 for less/equal/greater
   */
  compare(other) {
    if (other instanceof Period) {
      return -1 * other.compare(this);
    } else {
      let a = this.toUnixTime();
      let b = other.toUnixTime();
      if (a > b) return 1;
      if (b > a) return -1;
      return 0;
    }
  }
  /**
   * Compares only the date part of this instance with another one.
   *
   * @param {Time} other                  The instance to compare with
   * @param {Timezone} tz                 The timezone to compare in
   * @return {Number}                     -1, 0 or 1 for less/equal/greater
   */
  compareDateOnlyTz(other, tz) {
    let a = this.convertToZone(tz);
    let b = other.convertToZone(tz);
    let rc = 0;
    if ((rc = Time._cmp_attr(a, b, "year")) != 0) return rc;
    if ((rc = Time._cmp_attr(a, b, "month")) != 0) return rc;
    if ((rc = Time._cmp_attr(a, b, "day")) != 0) return rc;
    return rc;
  }
  /**
   * Convert the instance into another timezone. The returned ICAL.Time
   * instance is always a copy.
   *
   * @param {Timezone} zone      The zone to convert to
   * @return {Time}              The copy, converted to the zone
   */
  convertToZone(zone) {
    let copy = this.clone();
    let zone_equals = this.zone.tzid == zone.tzid;
    if (!this.isDate && !zone_equals) {
      Timezone.convert_time(copy, this.zone, zone);
    }
    copy.zone = zone;
    return copy;
  }
  /**
   * Calculates the UTC offset of the current date/time in the timezone it is
   * in.
   *
   * @return {Number}     UTC offset in seconds
   */
  utcOffset() {
    if (this.zone == Timezone.localTimezone || this.zone == Timezone.utcTimezone) {
      return 0;
    } else {
      return this.zone.utcOffset(this);
    }
  }
  /**
   * Returns an RFC 5545 compliant ical representation of this object.
   *
   * @return {String} ical date/date-time
   */
  toICALString() {
    let string = this.toString();
    if (string.length > 10) {
      return design.icalendar.value["date-time"].toICAL(string);
    } else {
      return design.icalendar.value.date.toICAL(string);
    }
  }
  /**
   * The string representation of this date/time, in jCal form
   * (including : and - separators).
   * @return {String}
   */
  toString() {
    let result = this.year + "-" + pad2(this.month) + "-" + pad2(this.day);
    if (!this.isDate) {
      result += "T" + pad2(this.hour) + ":" + pad2(this.minute) + ":" + pad2(this.second);
      if (this.zone === Timezone.utcTimezone) {
        result += "Z";
      }
    }
    return result;
  }
  /**
   * Converts the current instance to a Javascript date
   * @return {Date}
   */
  toJSDate() {
    if (this.zone == Timezone.localTimezone) {
      if (this.isDate) {
        return new Date(this.year, this.month - 1, this.day);
      } else {
        return new Date(
          this.year,
          this.month - 1,
          this.day,
          this.hour,
          this.minute,
          this.second,
          0
        );
      }
    } else {
      return new Date(this.toUnixTime() * 1e3);
    }
  }
  _normalize() {
    if (this._time.isDate) {
      this._time.hour = 0;
      this._time.minute = 0;
      this._time.second = 0;
    }
    this.adjust(0, 0, 0, 0);
    return this;
  }
  /**
   * Adjust the date/time by the given offset
   *
   * @param {Number} aExtraDays       The extra amount of days
   * @param {Number} aExtraHours      The extra amount of hours
   * @param {Number} aExtraMinutes    The extra amount of minutes
   * @param {Number} aExtraSeconds    The extra amount of seconds
   * @param {Number=} aTime           The time to adjust, defaults to the
   *                                    current instance.
   */
  adjust(aExtraDays, aExtraHours, aExtraMinutes, aExtraSeconds, aTime) {
    let minutesOverflow, hoursOverflow, daysOverflow = 0, yearsOverflow = 0;
    let second, minute, hour, day;
    let daysInMonth;
    let time = aTime || this._time;
    if (!time.isDate) {
      second = time.second + aExtraSeconds;
      time.second = second % 60;
      minutesOverflow = trunc(second / 60);
      if (time.second < 0) {
        time.second += 60;
        minutesOverflow--;
      }
      minute = time.minute + aExtraMinutes + minutesOverflow;
      time.minute = minute % 60;
      hoursOverflow = trunc(minute / 60);
      if (time.minute < 0) {
        time.minute += 60;
        hoursOverflow--;
      }
      hour = time.hour + aExtraHours + hoursOverflow;
      time.hour = hour % 24;
      daysOverflow = trunc(hour / 24);
      if (time.hour < 0) {
        time.hour += 24;
        daysOverflow--;
      }
    }
    if (time.month > 12) {
      yearsOverflow = trunc((time.month - 1) / 12);
    } else if (time.month < 1) {
      yearsOverflow = trunc(time.month / 12) - 1;
    }
    time.year += yearsOverflow;
    time.month -= 12 * yearsOverflow;
    day = time.day + aExtraDays + daysOverflow;
    if (day > 0) {
      for (; ; ) {
        daysInMonth = Time.daysInMonth(time.month, time.year);
        if (day <= daysInMonth) {
          break;
        }
        time.month++;
        if (time.month > 12) {
          time.year++;
          time.month = 1;
        }
        day -= daysInMonth;
      }
    } else {
      while (day <= 0) {
        if (time.month == 1) {
          time.year--;
          time.month = 12;
        } else {
          time.month--;
        }
        day += Time.daysInMonth(time.month, time.year);
      }
    }
    time.day = day;
    this._cachedUnixTime = null;
    return this;
  }
  /**
   * Sets up the current instance from unix time, the number of seconds since
   * January 1st, 1970.
   *
   * @param {Number} seconds      The seconds to set up with
   */
  fromUnixTime(seconds) {
    this.zone = Timezone.utcTimezone;
    let date = new Date(seconds * 1e3);
    this.year = date.getUTCFullYear();
    this.month = date.getUTCMonth() + 1;
    this.day = date.getUTCDate();
    if (this._time.isDate) {
      this.hour = 0;
      this.minute = 0;
      this.second = 0;
    } else {
      this.hour = date.getUTCHours();
      this.minute = date.getUTCMinutes();
      this.second = date.getUTCSeconds();
    }
    this._cachedUnixTime = null;
  }
  /**
   * Converts the current instance to seconds since January 1st 1970.
   *
   * @return {Number}         Seconds since 1970
   */
  toUnixTime() {
    if (this._cachedUnixTime !== null) {
      return this._cachedUnixTime;
    }
    let offset = this.utcOffset();
    let ms = Date.UTC(
      this.year,
      this.month - 1,
      this.day,
      this.hour,
      this.minute,
      this.second - offset
    );
    this._cachedUnixTime = ms / 1e3;
    return this._cachedUnixTime;
  }
  /**
   * Converts time to into Object which can be serialized then re-created
   * using the constructor.
   *
   * @example
   * // toJSON will automatically be called
   * var json = JSON.stringify(mytime);
   *
   * var deserialized = JSON.parse(json);
   *
   * var time = new ICAL.Time(deserialized);
   *
   * @return {Object}
   */
  toJSON() {
    let copy = [
      "year",
      "month",
      "day",
      "hour",
      "minute",
      "second",
      "isDate"
    ];
    let result = /* @__PURE__ */ Object.create(null);
    let i = 0;
    let len = copy.length;
    let prop;
    for (; i < len; i++) {
      prop = copy[i];
      result[prop] = this[prop];
    }
    if (this.zone) {
      result.timezone = this.zone.tzid;
    }
    return result;
  }
}
const CHAR = /[^ \t]/;
const VALUE_DELIMITER = ":";
const PARAM_DELIMITER = ";";
const PARAM_NAME_DELIMITER = "=";
const DEFAULT_VALUE_TYPE$1 = "unknown";
const DEFAULT_PARAM_TYPE = "text";
const RFC6868_REPLACE_MAP$1 = { "^'": '"', "^n": "\n", "^^": "^" };
function parse(input) {
  let state = {};
  let root = state.component = [];
  state.stack = [root];
  parse._eachLine(input, function(err, line) {
    parse._handleContentLine(line, state);
  });
  if (state.stack.length > 1) {
    throw new ParserError(
      "invalid ical body. component began but did not end"
    );
  }
  state = null;
  return root.length == 1 ? root[0] : root;
}
parse.property = function(str, designSet) {
  let state = {
    component: [[], []],
    designSet: designSet || design.defaultSet
  };
  parse._handleContentLine(str, state);
  return state.component[1][0];
};
parse.component = function(str) {
  return parse(str);
};
class ParserError extends Error {
  name = this.constructor.name;
}
parse.ParserError = ParserError;
parse._handleContentLine = function(line, state) {
  let valuePos = line.indexOf(VALUE_DELIMITER);
  let paramPos = line.indexOf(PARAM_DELIMITER);
  let lastParamIndex;
  let lastValuePos;
  let name;
  let value;
  let params = {};
  if (paramPos !== -1 && valuePos !== -1) {
    if (paramPos > valuePos) {
      paramPos = -1;
    }
  }
  let parsedParams;
  if (paramPos !== -1) {
    name = line.slice(0, Math.max(0, paramPos)).toLowerCase();
    parsedParams = parse._parseParameters(line.slice(Math.max(0, paramPos)), 0, state.designSet);
    if (parsedParams[2] == -1) {
      throw new ParserError("Invalid parameters in '" + line + "'");
    }
    params = parsedParams[0];
    let parsedParamLength;
    if (typeof parsedParams[1] === "string") {
      parsedParamLength = parsedParams[1].length;
    } else {
      parsedParamLength = parsedParams[1].reduce((accumulator, currentValue) => {
        return accumulator + currentValue.length;
      }, 0);
    }
    lastParamIndex = parsedParamLength + parsedParams[2] + paramPos;
    if ((lastValuePos = line.slice(Math.max(0, lastParamIndex)).indexOf(VALUE_DELIMITER)) !== -1) {
      value = line.slice(Math.max(0, lastParamIndex + lastValuePos + 1));
    } else {
      throw new ParserError("Missing parameter value in '" + line + "'");
    }
  } else if (valuePos !== -1) {
    name = line.slice(0, Math.max(0, valuePos)).toLowerCase();
    value = line.slice(Math.max(0, valuePos + 1));
    if (name === "begin") {
      let newComponent = [value.toLowerCase(), [], []];
      if (state.stack.length === 1) {
        state.component.push(newComponent);
      } else {
        state.component[2].push(newComponent);
      }
      state.stack.push(state.component);
      state.component = newComponent;
      if (!state.designSet) {
        state.designSet = design.getDesignSet(state.component[0]);
      }
      return;
    } else if (name === "end") {
      state.component = state.stack.pop();
      return;
    }
  } else {
    throw new ParserError(
      'invalid line (no token ";" or ":") "' + line + '"'
    );
  }
  let valueType;
  let multiValue = false;
  let structuredValue = false;
  let propertyDetails;
  let splitName;
  let ungroupedName;
  if (state.designSet.propertyGroups && name.indexOf(".") !== -1) {
    splitName = name.split(".");
    params.group = splitName[0];
    ungroupedName = splitName[1];
  } else {
    ungroupedName = name;
  }
  if (ungroupedName in state.designSet.property) {
    propertyDetails = state.designSet.property[ungroupedName];
    if ("multiValue" in propertyDetails) {
      multiValue = propertyDetails.multiValue;
    }
    if ("structuredValue" in propertyDetails) {
      structuredValue = propertyDetails.structuredValue;
    }
    if (value && "detectType" in propertyDetails) {
      valueType = propertyDetails.detectType(value);
    }
  }
  if (!valueType) {
    if (!("value" in params)) {
      if (propertyDetails) {
        valueType = propertyDetails.defaultType;
      } else {
        valueType = DEFAULT_VALUE_TYPE$1;
      }
    } else {
      valueType = params.value.toLowerCase();
    }
  }
  delete params.value;
  let result;
  if (multiValue && structuredValue) {
    value = parse._parseMultiValue(value, structuredValue, valueType, [], multiValue, state.designSet, structuredValue);
    result = [ungroupedName, params, valueType, value];
  } else if (multiValue) {
    result = [ungroupedName, params, valueType];
    parse._parseMultiValue(value, multiValue, valueType, result, null, state.designSet, false);
  } else if (structuredValue) {
    value = parse._parseMultiValue(value, structuredValue, valueType, [], null, state.designSet, structuredValue);
    result = [ungroupedName, params, valueType, value];
  } else {
    value = parse._parseValue(value, valueType, state.designSet, false);
    result = [ungroupedName, params, valueType, value];
  }
  if (state.component[0] === "vcard" && state.component[1].length === 0 && !(name === "version" && value === "4.0")) {
    state.designSet = design.getDesignSet("vcard3");
  }
  state.component[1].push(result);
};
parse._parseValue = function(value, type, designSet, structuredValue) {
  if (type in designSet.value && "fromICAL" in designSet.value[type]) {
    return designSet.value[type].fromICAL(value, structuredValue);
  }
  return value;
};
parse._parseParameters = function(line, start, designSet) {
  let lastParam = start;
  let pos = 0;
  let delim = PARAM_NAME_DELIMITER;
  let result = {};
  let name, lcname;
  let value, valuePos = -1;
  let type, multiValue, mvdelim;
  while (pos !== false && (pos = line.indexOf(delim, pos + 1)) !== -1) {
    name = line.slice(lastParam + 1, pos);
    if (name.length == 0) {
      throw new ParserError("Empty parameter name in '" + line + "'");
    }
    lcname = name.toLowerCase();
    mvdelim = false;
    multiValue = false;
    if (lcname in designSet.param && designSet.param[lcname].valueType) {
      type = designSet.param[lcname].valueType;
    } else {
      type = DEFAULT_PARAM_TYPE;
    }
    if (lcname in designSet.param) {
      multiValue = designSet.param[lcname].multiValue;
      if (designSet.param[lcname].multiValueSeparateDQuote) {
        mvdelim = parse._rfc6868Escape('"' + multiValue + '"');
      }
    }
    let nextChar = line[pos + 1];
    if (nextChar === '"') {
      valuePos = pos + 2;
      pos = line.indexOf('"', valuePos);
      if (multiValue && pos != -1) {
        let extendedValue = true;
        while (extendedValue) {
          if (line[pos + 1] == multiValue && line[pos + 2] == '"') {
            pos = line.indexOf('"', pos + 3);
          } else {
            extendedValue = false;
          }
        }
      }
      if (pos === -1) {
        throw new ParserError(
          'invalid line (no matching double quote) "' + line + '"'
        );
      }
      value = line.slice(valuePos, pos);
      lastParam = line.indexOf(PARAM_DELIMITER, pos);
      let propValuePos = line.indexOf(VALUE_DELIMITER, pos);
      if (lastParam === -1 || propValuePos !== -1 && lastParam > propValuePos) {
        pos = false;
      }
    } else {
      valuePos = pos + 1;
      let nextPos = line.indexOf(PARAM_DELIMITER, valuePos);
      let propValuePos = line.indexOf(VALUE_DELIMITER, valuePos);
      if (propValuePos !== -1 && nextPos > propValuePos) {
        nextPos = propValuePos;
        pos = false;
      } else if (nextPos === -1) {
        if (propValuePos === -1) {
          nextPos = line.length;
        } else {
          nextPos = propValuePos;
        }
        pos = false;
      } else {
        lastParam = nextPos;
        pos = nextPos;
      }
      value = line.slice(valuePos, nextPos);
    }
    const length_before = value.length;
    value = parse._rfc6868Escape(value);
    valuePos += length_before - value.length;
    if (multiValue) {
      let delimiter = mvdelim || multiValue;
      value = parse._parseMultiValue(value, delimiter, type, [], null, designSet);
    } else {
      value = parse._parseValue(value, type, designSet);
    }
    if (multiValue && lcname in result) {
      if (Array.isArray(result[lcname])) {
        result[lcname].push(value);
      } else {
        result[lcname] = [
          result[lcname],
          value
        ];
      }
    } else {
      result[lcname] = value;
    }
  }
  return [result, value, valuePos];
};
parse._rfc6868Escape = function(val) {
  return val.replace(/\^['n^]/g, function(x) {
    return RFC6868_REPLACE_MAP$1[x];
  });
};
parse._parseMultiValue = function(buffer, delim, type, result, innerMulti, designSet, structuredValue) {
  let pos = 0;
  let lastPos = 0;
  let value;
  if (delim.length === 0) {
    return buffer;
  }
  while ((pos = unescapedIndexOf(buffer, delim, lastPos)) !== -1) {
    value = buffer.slice(lastPos, pos);
    if (innerMulti) {
      value = parse._parseMultiValue(value, innerMulti, type, [], null, designSet, structuredValue);
    } else {
      value = parse._parseValue(value, type, designSet, structuredValue);
    }
    result.push(value);
    lastPos = pos + delim.length;
  }
  value = buffer.slice(lastPos);
  if (innerMulti) {
    value = parse._parseMultiValue(value, innerMulti, type, [], null, designSet, structuredValue);
  } else {
    value = parse._parseValue(value, type, designSet, structuredValue);
  }
  result.push(value);
  return result.length == 1 ? result[0] : result;
};
parse._eachLine = function(buffer, callback) {
  let len = buffer.length;
  let lastPos = buffer.search(CHAR);
  let pos = lastPos;
  let line;
  let firstChar;
  let newlineOffset;
  do {
    pos = buffer.indexOf("\n", lastPos) + 1;
    if (pos > 1 && buffer[pos - 2] === "\r") {
      newlineOffset = 2;
    } else {
      newlineOffset = 1;
    }
    if (pos === 0) {
      pos = len;
      newlineOffset = 0;
    }
    firstChar = buffer[lastPos];
    if (firstChar === " " || firstChar === "	") {
      line += buffer.slice(lastPos + 1, pos - newlineOffset);
    } else {
      if (line)
        callback(null, line);
      line = buffer.slice(lastPos, pos - newlineOffset);
    }
    lastPos = pos;
  } while (pos !== len);
  line = line.trim();
  if (line.length)
    callback(null, line);
};
const OPTIONS = ["tzid", "location", "tznames", "latitude", "longitude"];
class Timezone {
  static _compare_change_fn(a, b) {
    if (a.year < b.year) return -1;
    else if (a.year > b.year) return 1;
    if (a.month < b.month) return -1;
    else if (a.month > b.month) return 1;
    if (a.day < b.day) return -1;
    else if (a.day > b.day) return 1;
    if (a.hour < b.hour) return -1;
    else if (a.hour > b.hour) return 1;
    if (a.minute < b.minute) return -1;
    else if (a.minute > b.minute) return 1;
    if (a.second < b.second) return -1;
    else if (a.second > b.second) return 1;
    return 0;
  }
  /**
   * Convert the date/time from one zone to the next.
   *
   * @param {Time} tt                  The time to convert
   * @param {Timezone} from_zone       The source zone to convert from
   * @param {Timezone} to_zone         The target zone to convert to
   * @return {Time}                    The converted date/time object
   */
  static convert_time(tt, from_zone, to_zone) {
    if (tt.isDate || from_zone.tzid == to_zone.tzid || from_zone == Timezone.localTimezone || to_zone == Timezone.localTimezone) {
      tt.zone = to_zone;
      return tt;
    }
    let utcOffset = from_zone.utcOffset(tt);
    tt.adjust(0, 0, 0, -utcOffset);
    utcOffset = to_zone.utcOffset(tt);
    tt.adjust(0, 0, 0, utcOffset);
    return null;
  }
  /**
   * Creates a new ICAL.Timezone instance from the passed data object.
   *
   * @param {Component|Object} aData options for class
   * @param {String|Component} aData.component
   *        If aData is a simple object, then this member can be set to either a
   *        string containing the component data, or an already parsed
   *        ICAL.Component
   * @param {String} aData.tzid      The timezone identifier
   * @param {String} aData.location  The timezone locationw
   * @param {String} aData.tznames   An alternative string representation of the
   *                                  timezone
   * @param {Number} aData.latitude  The latitude of the timezone
   * @param {Number} aData.longitude The longitude of the timezone
   */
  static fromData(aData) {
    let tt = new Timezone();
    return tt.fromData(aData);
  }
  /**
   * The instance describing the UTC timezone
   * @type {Timezone}
   * @constant
   * @instance
   */
  static #utcTimezone = null;
  static get utcTimezone() {
    if (!this.#utcTimezone) {
      this.#utcTimezone = Timezone.fromData({
        tzid: "UTC"
      });
    }
    return this.#utcTimezone;
  }
  /**
   * The instance describing the local timezone
   * @type {Timezone}
   * @constant
   * @instance
   */
  static #localTimezone = null;
  static get localTimezone() {
    if (!this.#localTimezone) {
      this.#localTimezone = Timezone.fromData({
        tzid: "floating"
      });
    }
    return this.#localTimezone;
  }
  /**
   * Adjust a timezone change object.
   * @private
   * @param {Object} change     The timezone change object
   * @param {Number} days       The extra amount of days
   * @param {Number} hours      The extra amount of hours
   * @param {Number} minutes    The extra amount of minutes
   * @param {Number} seconds    The extra amount of seconds
   */
  static adjust_change(change, days, hours, minutes, seconds) {
    return Time.prototype.adjust.call(
      change,
      days,
      hours,
      minutes,
      seconds,
      change
    );
  }
  static _minimumExpansionYear = -1;
  static EXTRA_COVERAGE = 5;
  /**
   * Creates a new ICAL.Timezone instance, by passing in a tzid and component.
   *
   * @param {Component|Object} data options for class
   * @param {String|Component} data.component
   *        If data is a simple object, then this member can be set to either a
   *        string containing the component data, or an already parsed
   *        ICAL.Component
   * @param {String} data.tzid      The timezone identifier
   * @param {String} data.location  The timezone locationw
   * @param {String} data.tznames   An alternative string representation of the
   *                                  timezone
   * @param {Number} data.latitude  The latitude of the timezone
   * @param {Number} data.longitude The longitude of the timezone
   */
  constructor(data) {
    this.wrappedJSObject = this;
    this.fromData(data);
  }
  /**
   * Timezone identifier
   * @type {String}
   */
  tzid = "";
  /**
   * Timezone location
   * @type {String}
   */
  location = "";
  /**
   * Alternative timezone name, for the string representation
   * @type {String}
   */
  tznames = "";
  /**
   * The primary latitude for the timezone.
   * @type {Number}
   */
  latitude = 0;
  /**
   * The primary longitude for the timezone.
   * @type {Number}
   */
  longitude = 0;
  /**
   * The vtimezone component for this timezone.
   * @type {Component}
   */
  component = null;
  /**
   * The year this timezone has been expanded to. All timezone transition
   * dates until this year are known and can be used for calculation
   *
   * @private
   * @type {Number}
   */
  expandedUntilYear = 0;
  /**
   * The class identifier.
   * @constant
   * @type {String}
   * @default "icaltimezone"
   */
  icalclass = "icaltimezone";
  /**
   * Sets up the current instance using members from the passed data object.
   *
   * @param {Component|Object} aData options for class
   * @param {String|Component} aData.component
   *        If aData is a simple object, then this member can be set to either a
   *        string containing the component data, or an already parsed
   *        ICAL.Component
   * @param {String} aData.tzid      The timezone identifier
   * @param {String} aData.location  The timezone locationw
   * @param {String} aData.tznames   An alternative string representation of the
   *                                  timezone
   * @param {Number} aData.latitude  The latitude of the timezone
   * @param {Number} aData.longitude The longitude of the timezone
   */
  fromData(aData) {
    this.expandedUntilYear = 0;
    this.changes = [];
    if (aData instanceof Component) {
      this.component = aData;
    } else {
      if (aData && "component" in aData) {
        if (typeof aData.component == "string") {
          let jCal = parse(aData.component);
          this.component = new Component(jCal);
        } else if (aData.component instanceof Component) {
          this.component = aData.component;
        } else {
          this.component = null;
        }
      }
      for (let prop of OPTIONS) {
        if (aData && prop in aData) {
          this[prop] = aData[prop];
        }
      }
    }
    if (this.component instanceof Component && !this.tzid) {
      this.tzid = this.component.getFirstPropertyValue("tzid");
    }
    return this;
  }
  /**
   * Finds the utcOffset the given time would occur in this timezone.
   *
   * @param {Time} tt         The time to check for
   * @return {Number}         utc offset in seconds
   */
  utcOffset(tt) {
    if (this == Timezone.utcTimezone || this == Timezone.localTimezone) {
      return 0;
    }
    this._ensureCoverage(tt.year);
    if (!this.changes.length) {
      return 0;
    }
    let tt_change = {
      year: tt.year,
      month: tt.month,
      day: tt.day,
      hour: tt.hour,
      minute: tt.minute,
      second: tt.second
    };
    let change_num = this._findNearbyChange(tt_change);
    let change_num_to_use = -1;
    let step = 1;
    for (; ; ) {
      let change = clone(this.changes[change_num], true);
      if (change.utcOffset < change.prevUtcOffset) {
        Timezone.adjust_change(change, 0, 0, 0, change.utcOffset);
      } else {
        Timezone.adjust_change(
          change,
          0,
          0,
          0,
          change.prevUtcOffset
        );
      }
      let cmp = Timezone._compare_change_fn(tt_change, change);
      if (cmp >= 0) {
        change_num_to_use = change_num;
      } else {
        step = -1;
      }
      if (step == -1 && change_num_to_use != -1) {
        break;
      }
      change_num += step;
      if (change_num < 0) {
        return 0;
      }
      if (change_num >= this.changes.length) {
        break;
      }
    }
    let zone_change = this.changes[change_num_to_use];
    let utcOffset_change = zone_change.utcOffset - zone_change.prevUtcOffset;
    if (utcOffset_change < 0 && change_num_to_use > 0) {
      let tmp_change = clone(zone_change, true);
      Timezone.adjust_change(tmp_change, 0, 0, 0, tmp_change.prevUtcOffset);
      if (Timezone._compare_change_fn(tt_change, tmp_change) < 0) {
        let prev_zone_change = this.changes[change_num_to_use - 1];
        let want_daylight = false;
        if (zone_change.is_daylight != want_daylight && prev_zone_change.is_daylight == want_daylight) {
          zone_change = prev_zone_change;
        }
      }
    }
    return zone_change.utcOffset;
  }
  _findNearbyChange(change) {
    let idx = binsearchInsert(
      this.changes,
      change,
      Timezone._compare_change_fn
    );
    if (idx >= this.changes.length) {
      return this.changes.length - 1;
    }
    return idx;
  }
  _ensureCoverage(aYear) {
    if (Timezone._minimumExpansionYear == -1) {
      let today = Time.now();
      Timezone._minimumExpansionYear = today.year;
    }
    let changesEndYear = aYear;
    if (changesEndYear < Timezone._minimumExpansionYear) {
      changesEndYear = Timezone._minimumExpansionYear;
    }
    changesEndYear += Timezone.EXTRA_COVERAGE;
    if (!this.changes.length || this.expandedUntilYear < aYear) {
      let subcomps = this.component.getAllSubcomponents();
      let compLen = subcomps.length;
      let compIdx = 0;
      for (; compIdx < compLen; compIdx++) {
        this._expandComponent(
          subcomps[compIdx],
          changesEndYear,
          this.changes
        );
      }
      this.changes.sort(Timezone._compare_change_fn);
      this.expandedUntilYear = changesEndYear;
    }
  }
  _expandComponent(aComponent, aYear, changes) {
    if (!aComponent.hasProperty("dtstart") || !aComponent.hasProperty("tzoffsetto") || !aComponent.hasProperty("tzoffsetfrom")) {
      return null;
    }
    let dtstart = aComponent.getFirstProperty("dtstart").getFirstValue();
    let change;
    function convert_tzoffset(offset) {
      return offset.factor * (offset.hours * 3600 + offset.minutes * 60);
    }
    function init_changes() {
      let changebase = {};
      changebase.is_daylight = aComponent.name == "daylight";
      changebase.utcOffset = convert_tzoffset(
        aComponent.getFirstProperty("tzoffsetto").getFirstValue()
      );
      changebase.prevUtcOffset = convert_tzoffset(
        aComponent.getFirstProperty("tzoffsetfrom").getFirstValue()
      );
      return changebase;
    }
    if (!aComponent.hasProperty("rrule") && !aComponent.hasProperty("rdate")) {
      change = init_changes();
      change.year = dtstart.year;
      change.month = dtstart.month;
      change.day = dtstart.day;
      change.hour = dtstart.hour;
      change.minute = dtstart.minute;
      change.second = dtstart.second;
      Timezone.adjust_change(change, 0, 0, 0, -change.prevUtcOffset);
      changes.push(change);
    } else {
      let props = aComponent.getAllProperties("rdate");
      for (let rdate of props) {
        let time = rdate.getFirstValue();
        change = init_changes();
        change.year = time.year;
        change.month = time.month;
        change.day = time.day;
        if (time.isDate) {
          change.hour = dtstart.hour;
          change.minute = dtstart.minute;
          change.second = dtstart.second;
          if (dtstart.zone != Timezone.utcTimezone) {
            Timezone.adjust_change(change, 0, 0, 0, -change.prevUtcOffset);
          }
        } else {
          change.hour = time.hour;
          change.minute = time.minute;
          change.second = time.second;
          if (time.zone != Timezone.utcTimezone) {
            Timezone.adjust_change(change, 0, 0, 0, -change.prevUtcOffset);
          }
        }
        changes.push(change);
      }
      let rrule = aComponent.getFirstProperty("rrule");
      if (rrule) {
        rrule = rrule.getFirstValue();
        change = init_changes();
        if (rrule.until && rrule.until.zone == Timezone.utcTimezone) {
          rrule.until.adjust(0, 0, 0, change.prevUtcOffset);
          rrule.until.zone = Timezone.localTimezone;
        }
        let iterator = rrule.iterator(dtstart);
        let occ;
        while (occ = iterator.next()) {
          change = init_changes();
          if (occ.year > aYear || !occ) {
            break;
          }
          change.year = occ.year;
          change.month = occ.month;
          change.day = occ.day;
          change.hour = occ.hour;
          change.minute = occ.minute;
          change.second = occ.second;
          change.isDate = occ.isDate;
          Timezone.adjust_change(change, 0, 0, 0, -change.prevUtcOffset);
          changes.push(change);
        }
      }
    }
    return changes;
  }
  /**
   * The string representation of this timezone.
   * @return {String}
   */
  toString() {
    return this.tznames ? this.tznames : this.tzid;
  }
}
let zones = null;
const TimezoneService = {
  get count() {
    if (zones === null) {
      return 0;
    }
    return Object.keys(zones).length;
  },
  reset: function() {
    zones = /* @__PURE__ */ Object.create(null);
    let utc = Timezone.utcTimezone;
    zones.Z = utc;
    zones.UTC = utc;
    zones.GMT = utc;
  },
  _hard_reset: function() {
    zones = null;
  },
  /**
   * Checks if timezone id has been registered.
   *
   * @param {String} tzid     Timezone identifier (e.g. America/Los_Angeles)
   * @return {Boolean}        False, when not present
   */
  has: function(tzid) {
    if (zones === null) {
      return false;
    }
    return !!zones[tzid];
  },
  /**
   * Returns a timezone by its tzid if present.
   *
   * @param {String} tzid               Timezone identifier (e.g. America/Los_Angeles)
   * @return {Timezone | undefined}     The timezone, or undefined if not found
   */
  get: function(tzid) {
    if (zones === null) {
      this.reset();
    }
    return zones[tzid];
  },
  /**
   * Registers a timezone object or component.
   *
   * @param {Component|Timezone} timezone
   *        The initialized zone or vtimezone.
   *
   * @param {String=} name
   *        The name of the timezone. Defaults to the component's TZID if not
   *        passed.
   */
  register: function(timezone, name) {
    if (zones === null) {
      this.reset();
    }
    if (typeof timezone === "string" && name instanceof Timezone) {
      [timezone, name] = [name, timezone];
    }
    if (!name) {
      if (timezone instanceof Timezone) {
        name = timezone.tzid;
      } else {
        if (timezone.name === "vtimezone") {
          timezone = new Timezone(timezone);
          name = timezone.tzid;
        }
      }
    }
    if (!name) {
      throw new TypeError("Neither a timezone nor a name was passed");
    }
    if (timezone instanceof Timezone) {
      zones[name] = timezone;
    } else {
      throw new TypeError("timezone must be ICAL.Timezone or ICAL.Component");
    }
  },
  /**
   * Removes a timezone by its tzid from the list.
   *
   * @param {String} tzid     Timezone identifier (e.g. America/Los_Angeles)
   * @return {?Timezone}      The removed timezone, or null if not registered
   */
  remove: function(tzid) {
    if (zones === null) {
      return null;
    }
    return delete zones[tzid];
  }
};
function updateTimezones(vcal) {
  let allsubs, properties, vtimezones, reqTzid, i;
  if (!vcal || vcal.name !== "vcalendar") {
    return vcal;
  }
  allsubs = vcal.getAllSubcomponents();
  properties = [];
  vtimezones = {};
  for (i = 0; i < allsubs.length; i++) {
    if (allsubs[i].name === "vtimezone") {
      let tzid = allsubs[i].getFirstProperty("tzid").getFirstValue();
      vtimezones[tzid] = allsubs[i];
    } else {
      properties = properties.concat(allsubs[i].getAllProperties());
    }
  }
  reqTzid = {};
  for (i = 0; i < properties.length; i++) {
    let tzid = properties[i].getParameter("tzid");
    if (tzid) {
      reqTzid[tzid] = true;
    }
  }
  for (let [tzid, comp] of Object.entries(vtimezones)) {
    if (!reqTzid[tzid]) {
      vcal.removeSubcomponent(comp);
    }
  }
  for (let tzid of Object.keys(reqTzid)) {
    if (!vtimezones[tzid] && TimezoneService.has(tzid)) {
      vcal.addSubcomponent(TimezoneService.get(tzid).component);
    }
  }
  return vcal;
}
function isStrictlyNaN(number) {
  return typeof number === "number" && isNaN(number);
}
function strictParseInt(string) {
  let result = parseInt(string, 10);
  if (isStrictlyNaN(result)) {
    throw new Error(
      'Could not extract integer from "' + string + '"'
    );
  }
  return result;
}
function formatClassType(data, type) {
  if (typeof data === "undefined") {
    return void 0;
  }
  if (data instanceof type) {
    return data;
  }
  return new type(data);
}
function unescapedIndexOf(buffer, search, pos) {
  while ((pos = buffer.indexOf(search, pos)) !== -1) {
    if (pos > 0 && buffer[pos - 1] === "\\") {
      pos += 1;
    } else {
      return pos;
    }
  }
  return -1;
}
function binsearchInsert(list, seekVal, cmpfunc) {
  if (!list.length)
    return 0;
  let low = 0, high = list.length - 1, mid, cmpval;
  while (low <= high) {
    mid = low + Math.floor((high - low) / 2);
    cmpval = cmpfunc(seekVal, list[mid]);
    if (cmpval < 0)
      high = mid - 1;
    else if (cmpval > 0)
      low = mid + 1;
    else
      break;
  }
  if (cmpval < 0)
    return mid;
  else if (cmpval > 0)
    return mid + 1;
  else
    return mid;
}
function clone(aSrc, aDeep) {
  if (!aSrc || typeof aSrc != "object") {
    return aSrc;
  } else if (aSrc instanceof Date) {
    return new Date(aSrc.getTime());
  } else if ("clone" in aSrc) {
    return aSrc.clone();
  } else if (Array.isArray(aSrc)) {
    let arr = [];
    for (let i = 0; i < aSrc.length; i++) {
      arr.push(aDeep ? clone(aSrc[i], true) : aSrc[i]);
    }
    return arr;
  } else {
    let obj = {};
    for (let [name, value] of Object.entries(aSrc)) {
      if (aDeep) {
        obj[name] = clone(value, true);
      } else {
        obj[name] = value;
      }
    }
    return obj;
  }
}
function foldline(aLine) {
  let result = "";
  let line = aLine || "", pos = 0, line_length = 0;
  while (line.length) {
    let cp = line.codePointAt(pos);
    if (cp < 128) ++line_length;
    else if (cp < 2048) line_length += 2;
    else if (cp < 65536) line_length += 3;
    else line_length += 4;
    if (line_length < ICALmodule.foldLength + 1)
      pos += cp > 65535 ? 2 : 1;
    else {
      result += ICALmodule.newLineChar + " " + line.slice(0, Math.max(0, pos));
      line = line.slice(Math.max(0, pos));
      pos = line_length = 0;
    }
  }
  return result.slice(ICALmodule.newLineChar.length + 1);
}
function pad2(data) {
  if (typeof data !== "string") {
    if (typeof data === "number") {
      data = parseInt(data);
    }
    data = String(data);
  }
  let len = data.length;
  switch (len) {
    case 0:
      return "00";
    case 1:
      return "0" + data;
    default:
      return data;
  }
}
function trunc(number) {
  return number < 0 ? Math.ceil(number) : Math.floor(number);
}
function extend(source, target) {
  for (let key in source) {
    let descr = Object.getOwnPropertyDescriptor(source, key);
    if (descr && !Object.getOwnPropertyDescriptor(target, key)) {
      Object.defineProperty(target, key, descr);
    }
  }
  return target;
}
var helpers = /* @__PURE__ */ Object.freeze({
  __proto__: null,
  binsearchInsert,
  clone,
  extend,
  foldline,
  formatClassType,
  isStrictlyNaN,
  pad2,
  strictParseInt,
  trunc,
  unescapedIndexOf,
  updateTimezones
});
class UtcOffset {
  /**
   * Creates a new {@link ICAL.UtcOffset} instance from the passed string.
   *
   * @param {String} aString    The string to parse
   * @return {Duration}         The created utc-offset instance
   */
  static fromString(aString) {
    let options = {};
    options.factor = aString[0] === "+" ? 1 : -1;
    options.hours = strictParseInt(aString.slice(1, 3));
    options.minutes = strictParseInt(aString.slice(4, 6));
    return new UtcOffset(options);
  }
  /**
   * Creates a new {@link ICAL.UtcOffset} instance from the passed seconds
   * value.
   *
   * @param {Number} aSeconds       The number of seconds to convert
   */
  static fromSeconds(aSeconds) {
    let instance = new UtcOffset();
    instance.fromSeconds(aSeconds);
    return instance;
  }
  /**
   * Creates a new ICAL.UtcOffset instance.
   *
   * @param {Object} aData          An object with members of the utc offset
   * @param {Number=} aData.hours   The hours for the utc offset
   * @param {Number=} aData.minutes The minutes in the utc offset
   * @param {Number=} aData.factor  The factor for the utc-offset, either -1 or 1
   */
  constructor(aData) {
    this.fromData(aData);
  }
  /**
   * The hours in the utc-offset
   * @type {Number}
   */
  hours = 0;
  /**
   * The minutes in the utc-offset
   * @type {Number}
   */
  minutes = 0;
  /**
   * The sign of the utc offset, 1 for positive offset, -1 for negative
   * offsets.
   * @type {Number}
   */
  factor = 1;
  /**
   * The type name, to be used in the jCal object.
   * @constant
   * @type {String}
   * @default "utc-offset"
   */
  icaltype = "utc-offset";
  /**
   * Returns a clone of the utc offset object.
   *
   * @return {UtcOffset}     The cloned object
   */
  clone() {
    return UtcOffset.fromSeconds(this.toSeconds());
  }
  /**
   * Sets up the current instance using members from the passed data object.
   *
   * @param {Object} aData          An object with members of the utc offset
   * @param {Number=} aData.hours   The hours for the utc offset
   * @param {Number=} aData.minutes The minutes in the utc offset
   * @param {Number=} aData.factor  The factor for the utc-offset, either -1 or 1
   */
  fromData(aData) {
    if (aData) {
      for (let [key, value] of Object.entries(aData)) {
        this[key] = value;
      }
    }
    this._normalize();
  }
  /**
   * Sets up the current instance from the given seconds value. The seconds
   * value is truncated to the minute. Offsets are wrapped when the world
   * ends, the hour after UTC+14:00 is UTC-12:00.
   *
   * @param {Number} aSeconds         The seconds to convert into an offset
   */
  fromSeconds(aSeconds) {
    let secs = Math.abs(aSeconds);
    this.factor = aSeconds < 0 ? -1 : 1;
    this.hours = trunc(secs / 3600);
    secs -= this.hours * 3600;
    this.minutes = trunc(secs / 60);
    return this;
  }
  /**
   * Convert the current offset to a value in seconds
   *
   * @return {Number}                 The offset in seconds
   */
  toSeconds() {
    return this.factor * (60 * this.minutes + 3600 * this.hours);
  }
  /**
   * Compare this utc offset with another one.
   *
   * @param {UtcOffset} other             The other offset to compare with
   * @return {Number}                     -1, 0 or 1 for less/equal/greater
   */
  compare(other) {
    let a = this.toSeconds();
    let b = other.toSeconds();
    return (a > b) - (b > a);
  }
  _normalize() {
    let secs = this.toSeconds();
    let factor = this.factor;
    while (secs < -43200) {
      secs += 97200;
    }
    while (secs > 50400) {
      secs -= 97200;
    }
    this.fromSeconds(secs);
    if (secs == 0) {
      this.factor = factor;
    }
  }
  /**
   * The iCalendar string representation of this utc-offset.
   * @return {String}
   */
  toICALString() {
    return design.icalendar.value["utc-offset"].toICAL(this.toString());
  }
  /**
   * The string representation of this utc-offset.
   * @return {String}
   */
  toString() {
    return (this.factor == 1 ? "+" : "-") + pad2(this.hours) + ":" + pad2(this.minutes);
  }
}
class VCardTime extends Time {
  /**
   * Returns a new ICAL.VCardTime instance from a date and/or time string.
   *
   * @param {String} aValue     The string to create from
   * @param {String} aIcalType  The type for this instance, e.g. date-and-or-time
   * @return {VCardTime}        The date/time instance
   */
  static fromDateAndOrTimeString(aValue, aIcalType) {
    function part(v, s, e) {
      return v ? strictParseInt(v.slice(s, s + e)) : null;
    }
    let parts = aValue.split("T");
    let dt = parts[0], tmz = parts[1];
    let splitzone = tmz ? design.vcard.value.time._splitZone(tmz) : [];
    let zone = splitzone[0], tm = splitzone[1];
    let dtlen = dt ? dt.length : 0;
    let tmlen = tm ? tm.length : 0;
    let hasDashDate = dt && dt[0] == "-" && dt[1] == "-";
    let hasDashTime = tm && tm[0] == "-";
    let o = {
      year: hasDashDate ? null : part(dt, 0, 4),
      month: hasDashDate && (dtlen == 4 || dtlen == 7) ? part(dt, 2, 2) : dtlen == 7 ? part(dt, 5, 2) : dtlen == 10 ? part(dt, 5, 2) : null,
      day: dtlen == 5 ? part(dt, 3, 2) : dtlen == 7 && hasDashDate ? part(dt, 5, 2) : dtlen == 10 ? part(dt, 8, 2) : null,
      hour: hasDashTime ? null : part(tm, 0, 2),
      minute: hasDashTime && tmlen == 3 ? part(tm, 1, 2) : tmlen > 4 ? hasDashTime ? part(tm, 1, 2) : part(tm, 3, 2) : null,
      second: tmlen == 4 ? part(tm, 2, 2) : tmlen == 6 ? part(tm, 4, 2) : tmlen == 8 ? part(tm, 6, 2) : null
    };
    if (zone == "Z") {
      zone = Timezone.utcTimezone;
    } else if (zone && zone[3] == ":") {
      zone = UtcOffset.fromString(zone);
    } else {
      zone = null;
    }
    return new VCardTime(o, zone, aIcalType);
  }
  /**
   * Creates a new ICAL.VCardTime instance.
   *
   * @param {Object} data                           The data for the time instance
   * @param {Number=} data.year                     The year for this date
   * @param {Number=} data.month                    The month for this date
   * @param {Number=} data.day                      The day for this date
   * @param {Number=} data.hour                     The hour for this date
   * @param {Number=} data.minute                   The minute for this date
   * @param {Number=} data.second                   The second for this date
   * @param {Timezone|UtcOffset} zone               The timezone to use
   * @param {String} icaltype                       The type for this date/time object
   */
  constructor(data, zone, icaltype) {
    super(data, zone);
    this.icaltype = icaltype || "date-and-or-time";
  }
  /**
   * The class identifier.
   * @constant
   * @type {String}
   * @default "vcardtime"
   */
  icalclass = "vcardtime";
  /**
   * The type name, to be used in the jCal object.
   * @type {String}
   * @default "date-and-or-time"
   */
  icaltype = "date-and-or-time";
  /**
   * Returns a clone of the vcard date/time object.
   *
   * @return {VCardTime}     The cloned object
   */
  clone() {
    return new VCardTime(this._time, this.zone, this.icaltype);
  }
  _normalize() {
    return this;
  }
  /**
   * @inheritdoc
   */
  utcOffset() {
    if (this.zone instanceof UtcOffset) {
      return this.zone.toSeconds();
    } else {
      return Time.prototype.utcOffset.apply(this, arguments);
    }
  }
  /**
   * Returns an RFC 6350 compliant representation of this object.
   *
   * @return {String}         vcard date/time string
   */
  toICALString() {
    return design.vcard.value[this.icaltype].toICAL(this.toString());
  }
  /**
   * The string representation of this date/time, in jCard form
   * (including : and - separators).
   * @return {String}
   */
  toString() {
    let y = this.year, m = this.month, d = this.day;
    let h = this.hour, mm = this.minute, s = this.second;
    let hasYear = y !== null, hasMonth = m !== null, hasDay = d !== null;
    let hasHour = h !== null, hasMinute = mm !== null, hasSecond = s !== null;
    let datepart = (hasYear ? pad2(y) + (hasMonth || hasDay ? "-" : "") : hasMonth || hasDay ? "--" : "") + (hasMonth ? pad2(m) : "") + (hasDay ? "-" + pad2(d) : "");
    let timepart = (hasHour ? pad2(h) : "-") + (hasHour && hasMinute ? ":" : "") + (hasMinute ? pad2(mm) : "") + (!hasHour && !hasMinute ? "-" : "") + (hasMinute && hasSecond ? ":" : "") + (hasSecond ? pad2(s) : "");
    let zone;
    if (this.zone === Timezone.utcTimezone) {
      zone = "Z";
    } else if (this.zone instanceof UtcOffset) {
      zone = this.zone.toString();
    } else if (this.zone === Timezone.localTimezone) {
      zone = "";
    } else if (this.zone instanceof Timezone) {
      let offset = UtcOffset.fromSeconds(this.zone.utcOffset(this));
      zone = offset.toString();
    } else {
      zone = "";
    }
    switch (this.icaltype) {
      case "time":
        return timepart + zone;
      case "date-and-or-time":
      case "date-time":
        return datepart + (timepart == "--" ? "" : "T" + timepart + zone);
      case "date":
        return datepart;
    }
    return null;
  }
}
class RecurIterator {
  static _indexMap = {
    "BYSECOND": 0,
    "BYMINUTE": 1,
    "BYHOUR": 2,
    "BYDAY": 3,
    "BYMONTHDAY": 4,
    "BYYEARDAY": 5,
    "BYWEEKNO": 6,
    "BYMONTH": 7,
    "BYSETPOS": 8
  };
  static _expandMap = {
    "SECONDLY": [1, 1, 1, 1, 1, 1, 1, 1],
    "MINUTELY": [2, 1, 1, 1, 1, 1, 1, 1],
    "HOURLY": [2, 2, 1, 1, 1, 1, 1, 1],
    "DAILY": [2, 2, 2, 1, 1, 1, 1, 1],
    "WEEKLY": [2, 2, 2, 2, 3, 3, 1, 1],
    "MONTHLY": [2, 2, 2, 2, 2, 3, 3, 1],
    "YEARLY": [2, 2, 2, 2, 2, 2, 2, 2]
  };
  static UNKNOWN = 0;
  static CONTRACT = 1;
  static EXPAND = 2;
  static ILLEGAL = 3;
  /**
   * Creates a new ICAL.RecurIterator instance. The options object may contain additional members
   * when resuming iteration from a previous run.
   *
   * @param {Object} options                The iterator options
   * @param {Recur} options.rule            The rule to iterate.
   * @param {Time} options.dtstart          The start date of the event.
   * @param {Boolean=} options.initialized  When true, assume that options are
   *        from a previously constructed iterator. Initialization will not be
   *        repeated.
   */
  constructor(options) {
    this.fromData(options);
  }
  /**
   * True when iteration is finished.
   * @type {Boolean}
   */
  completed = false;
  /**
   * The rule that is being iterated
   * @type {Recur}
   */
  rule = null;
  /**
   * The start date of the event being iterated.
   * @type {Time}
   */
  dtstart = null;
  /**
   * The last occurrence that was returned from the
   * {@link RecurIterator#next} method.
   * @type {Time}
   */
  last = null;
  /**
   * The sequence number from the occurrence
   * @type {Number}
   */
  occurrence_number = 0;
  /**
   * The indices used for the {@link ICAL.RecurIterator#by_data} object.
   * @type {Object}
   * @private
   */
  by_indices = null;
  /**
   * If true, the iterator has already been initialized
   * @type {Boolean}
   * @private
   */
  initialized = false;
  /**
   * The initializd by-data.
   * @type {Object}
   * @private
   */
  by_data = null;
  /**
   * The expanded yeardays
   * @type {Array}
   * @private
   */
  days = null;
  /**
   * The index in the {@link ICAL.RecurIterator#days} array.
   * @type {Number}
   * @private
   */
  days_index = 0;
  /**
   * Initialize the recurrence iterator from the passed data object. This
   * method is usually not called directly, you can initialize the iterator
   * through the constructor.
   *
   * @param {Object} options                The iterator options
   * @param {Recur} options.rule            The rule to iterate.
   * @param {Time} options.dtstart          The start date of the event.
   * @param {Boolean=} options.initialized  When true, assume that options are
   *        from a previously constructed iterator. Initialization will not be
   *        repeated.
   */
  fromData(options) {
    this.rule = formatClassType(options.rule, Recur);
    if (!this.rule) {
      throw new Error("iterator requires a (ICAL.Recur) rule");
    }
    this.dtstart = formatClassType(options.dtstart, Time);
    if (!this.dtstart) {
      throw new Error("iterator requires a (ICAL.Time) dtstart");
    }
    if (options.by_data) {
      this.by_data = options.by_data;
    } else {
      this.by_data = clone(this.rule.parts, true);
    }
    if (options.occurrence_number)
      this.occurrence_number = options.occurrence_number;
    this.days = options.days || [];
    if (options.last) {
      this.last = formatClassType(options.last, Time);
    }
    this.by_indices = options.by_indices;
    if (!this.by_indices) {
      this.by_indices = {
        "BYSECOND": 0,
        "BYMINUTE": 0,
        "BYHOUR": 0,
        "BYDAY": 0,
        "BYMONTH": 0,
        "BYWEEKNO": 0,
        "BYMONTHDAY": 0
      };
    }
    this.initialized = options.initialized || false;
    if (!this.initialized) {
      try {
        this.init();
      } catch (e) {
        if (e instanceof InvalidRecurrenceRuleError) {
          this.completed = true;
        } else {
          throw e;
        }
      }
    }
  }
  /**
   * Initialize the iterator
   * @private
   */
  init() {
    this.initialized = true;
    this.last = this.dtstart.clone();
    let parts = this.by_data;
    if ("BYDAY" in parts) {
      this.sort_byday_rules(parts.BYDAY);
    }
    if ("BYYEARDAY" in parts) {
      if ("BYMONTH" in parts || "BYWEEKNO" in parts || "BYMONTHDAY" in parts) {
        throw new Error("Invalid BYYEARDAY rule");
      }
    }
    if ("BYWEEKNO" in parts && "BYMONTHDAY" in parts) {
      throw new Error("BYWEEKNO does not fit to BYMONTHDAY");
    }
    if (this.rule.freq == "MONTHLY" && ("BYYEARDAY" in parts || "BYWEEKNO" in parts)) {
      throw new Error("For MONTHLY recurrences neither BYYEARDAY nor BYWEEKNO may appear");
    }
    if (this.rule.freq == "WEEKLY" && ("BYYEARDAY" in parts || "BYMONTHDAY" in parts)) {
      throw new Error("For WEEKLY recurrences neither BYMONTHDAY nor BYYEARDAY may appear");
    }
    if (this.rule.freq != "YEARLY" && "BYYEARDAY" in parts) {
      throw new Error("BYYEARDAY may only appear in YEARLY rules");
    }
    this.last.second = this.setup_defaults("BYSECOND", "SECONDLY", this.dtstart.second);
    this.last.minute = this.setup_defaults("BYMINUTE", "MINUTELY", this.dtstart.minute);
    this.last.hour = this.setup_defaults("BYHOUR", "HOURLY", this.dtstart.hour);
    this.last.day = this.setup_defaults("BYMONTHDAY", "DAILY", this.dtstart.day);
    this.last.month = this.setup_defaults("BYMONTH", "MONTHLY", this.dtstart.month);
    if (this.rule.freq == "WEEKLY") {
      if ("BYDAY" in parts) {
        let [, dow] = this.ruleDayOfWeek(parts.BYDAY[0], this.rule.wkst);
        let wkdy = dow - this.last.dayOfWeek(this.rule.wkst);
        if (this.last.dayOfWeek(this.rule.wkst) < dow && wkdy >= 0 || wkdy < 0) {
          this.last.day += wkdy;
        }
      } else {
        let dayName = Recur.numericDayToIcalDay(this.dtstart.dayOfWeek());
        parts.BYDAY = [dayName];
      }
    }
    if (this.rule.freq == "YEARLY") {
      const untilYear = this.rule.until ? this.rule.until.year : 2e4;
      while (this.last.year <= untilYear) {
        this.expand_year_days(this.last.year);
        if (this.days.length > 0) {
          break;
        }
        this.increment_year(this.rule.interval);
      }
      if (this.days.length == 0) {
        throw new InvalidRecurrenceRuleError();
      }
      if (!this._nextByYearDay() && !this.next_year() && !this.next_year() && !this.next_year()) {
        throw new InvalidRecurrenceRuleError();
      }
    }
    if (this.rule.freq == "MONTHLY") {
      if (this.has_by_data("BYDAY")) {
        let tempLast = null;
        let initLast = this.last.clone();
        let daysInMonth = Time.daysInMonth(this.last.month, this.last.year);
        for (let bydow of this.by_data.BYDAY) {
          this.last = initLast.clone();
          let [pos, dow] = this.ruleDayOfWeek(bydow);
          let dayOfMonth = this.last.nthWeekDay(dow, pos);
          if (pos >= 6 || pos <= -6) {
            throw new Error("Malformed values in BYDAY part");
          }
          if (dayOfMonth > daysInMonth || dayOfMonth <= 0) {
            if (tempLast && tempLast.month == initLast.month) {
              continue;
            }
            while (dayOfMonth > daysInMonth || dayOfMonth <= 0) {
              this.increment_month();
              daysInMonth = Time.daysInMonth(this.last.month, this.last.year);
              dayOfMonth = this.last.nthWeekDay(dow, pos);
            }
          }
          this.last.day = dayOfMonth;
          if (!tempLast || this.last.compare(tempLast) < 0) {
            tempLast = this.last.clone();
          }
        }
        this.last = tempLast.clone();
        if (this.has_by_data("BYMONTHDAY")) {
          this._byDayAndMonthDay(true);
        }
        if (this.last.day > daysInMonth || this.last.day == 0) {
          throw new Error("Malformed values in BYDAY part");
        }
      } else if (this.has_by_data("BYMONTHDAY")) {
        this.last.day = 1;
        let normalized = this.normalizeByMonthDayRules(
          this.last.year,
          this.last.month,
          this.rule.parts.BYMONTHDAY
        ).filter((d) => d >= this.last.day);
        if (normalized.length) {
          this.last.day = normalized[0];
          this.by_data.BYMONTHDAY = normalized;
        } else {
          if (!this.next_month() && !this.next_month() && !this.next_month()) {
            throw new InvalidRecurrenceRuleError();
          }
        }
      }
    }
  }
  /**
   * Retrieve the next occurrence from the iterator.
   * @return {Time}
   */
  next(again = false) {
    let before = this.last ? this.last.clone() : null;
    if (this.rule.count && this.occurrence_number >= this.rule.count || this.rule.until && this.last.compare(this.rule.until) > 0) {
      this.completed = true;
    }
    if (this.completed) {
      return null;
    }
    if (this.occurrence_number == 0 && this.last.compare(this.dtstart) >= 0) {
      this.occurrence_number++;
      return this.last;
    }
    let valid;
    let invalid_count = 0;
    do {
      valid = 1;
      switch (this.rule.freq) {
        case "SECONDLY":
          this.next_second();
          break;
        case "MINUTELY":
          this.next_minute();
          break;
        case "HOURLY":
          this.next_hour();
          break;
        case "DAILY":
          this.next_day();
          break;
        case "WEEKLY":
          this.next_week();
          break;
        case "MONTHLY":
          valid = this.next_month();
          if (valid) {
            invalid_count = 0;
          } else if (++invalid_count == 336) {
            this.completed = true;
            return null;
          }
          break;
        case "YEARLY":
          valid = this.next_year();
          if (valid) {
            invalid_count = 0;
          } else if (++invalid_count == 28) {
            this.completed = true;
            return null;
          }
          break;
        default:
          return null;
      }
    } while (!this.check_contracting_rules() || this.last.compare(this.dtstart) < 0 || !valid);
    if (this.last.compare(before) == 0) {
      if (again) {
        throw new Error("Same occurrence found twice, protecting you from death by recursion");
      }
      this.next(true);
    }
    if (this.rule.until && this.last.compare(this.rule.until) > 0) {
      this.completed = true;
      return null;
    } else {
      this.occurrence_number++;
      return this.last;
    }
  }
  next_second() {
    return this.next_generic("BYSECOND", "SECONDLY", "second", "minute");
  }
  increment_second(inc) {
    return this.increment_generic(inc, "second", 60, "minute");
  }
  next_minute() {
    return this.next_generic(
      "BYMINUTE",
      "MINUTELY",
      "minute",
      "hour",
      "next_second"
    );
  }
  increment_minute(inc) {
    return this.increment_generic(inc, "minute", 60, "hour");
  }
  next_hour() {
    return this.next_generic(
      "BYHOUR",
      "HOURLY",
      "hour",
      "monthday",
      "next_minute"
    );
  }
  increment_hour(inc) {
    this.increment_generic(inc, "hour", 24, "monthday");
  }
  next_day() {
    let this_freq = this.rule.freq == "DAILY";
    if (this.next_hour() == 0) {
      return 0;
    }
    if (this_freq) {
      this.increment_monthday(this.rule.interval);
    } else {
      this.increment_monthday(1);
    }
    return 0;
  }
  next_week() {
    let end_of_data = 0;
    if (this.next_weekday_by_week() == 0) {
      return end_of_data;
    }
    if (this.has_by_data("BYWEEKNO")) {
      this.by_indices.BYWEEKNO++;
      if (this.by_indices.BYWEEKNO == this.by_data.BYWEEKNO.length) {
        this.by_indices.BYWEEKNO = 0;
        end_of_data = 1;
      }
      this.last.month = 1;
      this.last.day = 1;
      let week_no = this.by_data.BYWEEKNO[this.by_indices.BYWEEKNO];
      this.last.day += 7 * week_no;
      if (end_of_data) {
        this.increment_year(1);
      }
    } else {
      this.increment_monthday(7 * this.rule.interval);
    }
    return end_of_data;
  }
  /**
   * Normalize each by day rule for a given year/month.
   * Takes into account ordering and negative rules
   *
   * @private
   * @param {Number} year         Current year.
   * @param {Number} month        Current month.
   * @param {Array}  rules        Array of rules.
   *
   * @return {Array} sorted and normalized rules.
   *                 Negative rules will be expanded to their
   *                 correct positive values for easier processing.
   */
  normalizeByMonthDayRules(year, month, rules) {
    let daysInMonth = Time.daysInMonth(month, year);
    let newRules = [];
    let ruleIdx = 0;
    let len = rules.length;
    let rule;
    for (; ruleIdx < len; ruleIdx++) {
      rule = parseInt(rules[ruleIdx], 10);
      if (isNaN(rule)) {
        throw new Error("Invalid BYMONTHDAY value");
      }
      if (Math.abs(rule) > daysInMonth) {
        continue;
      }
      if (rule < 0) {
        rule = daysInMonth + (rule + 1);
      } else if (rule === 0) {
        continue;
      }
      if (newRules.indexOf(rule) === -1) {
        newRules.push(rule);
      }
    }
    return newRules.sort(function(a, b) {
      return a - b;
    });
  }
  /**
   * NOTES:
   * We are given a list of dates in the month (BYMONTHDAY) (23, etc..)
   * Also we are given a list of days (BYDAY) (MO, 2SU, etc..) when
   * both conditions match a given date (this.last.day) iteration stops.
   *
   * @private
   * @param {Boolean=} isInit     When given true will not increment the
   *                                current day (this.last).
   */
  _byDayAndMonthDay(isInit) {
    let byMonthDay;
    let byDay = this.by_data.BYDAY;
    let date;
    let dateIdx = 0;
    let dateLen;
    let dayLen = byDay.length;
    let dataIsValid = 0;
    let daysInMonth;
    let self = this;
    let lastDay = this.last.day;
    function initMonth() {
      daysInMonth = Time.daysInMonth(
        self.last.month,
        self.last.year
      );
      byMonthDay = self.normalizeByMonthDayRules(
        self.last.year,
        self.last.month,
        self.by_data.BYMONTHDAY
      );
      dateLen = byMonthDay.length;
      while (byMonthDay[dateIdx] <= lastDay && !(isInit && byMonthDay[dateIdx] == lastDay) && dateIdx < dateLen - 1) {
        dateIdx++;
      }
    }
    function nextMonth() {
      lastDay = 0;
      self.increment_month();
      dateIdx = 0;
      initMonth();
    }
    initMonth();
    if (isInit) {
      lastDay -= 1;
    }
    let monthsCounter = 48;
    while (!dataIsValid && monthsCounter) {
      monthsCounter--;
      date = lastDay + 1;
      if (date > daysInMonth) {
        nextMonth();
        continue;
      }
      let next = byMonthDay[dateIdx++];
      if (next >= date) {
        lastDay = next;
      } else {
        nextMonth();
        continue;
      }
      for (let dayIdx = 0; dayIdx < dayLen; dayIdx++) {
        let parts = this.ruleDayOfWeek(byDay[dayIdx]);
        let pos = parts[0];
        let dow = parts[1];
        this.last.day = lastDay;
        if (this.last.isNthWeekDay(dow, pos)) {
          dataIsValid = 1;
          break;
        }
      }
      if (!dataIsValid && dateIdx === dateLen) {
        nextMonth();
        continue;
      }
    }
    if (monthsCounter <= 0) {
      throw new Error("Malformed values in BYDAY combined with BYMONTHDAY parts");
    }
    return dataIsValid;
  }
  next_month() {
    let data_valid = 1;
    if (this.next_hour() == 0) {
      return data_valid;
    }
    if (this.has_by_data("BYDAY") && this.has_by_data("BYMONTHDAY")) {
      data_valid = this._byDayAndMonthDay();
    } else if (this.has_by_data("BYDAY")) {
      let daysInMonth = Time.daysInMonth(this.last.month, this.last.year);
      let setpos = 0;
      let setpos_total = 0;
      if (this.has_by_data("BYSETPOS")) {
        let last_day = this.last.day;
        for (let day2 = 1; day2 <= daysInMonth; day2++) {
          this.last.day = day2;
          if (this.is_day_in_byday(this.last)) {
            setpos_total++;
            if (day2 <= last_day) {
              setpos++;
            }
          }
        }
        this.last.day = last_day;
      }
      data_valid = 0;
      let day;
      for (day = this.last.day + 1; day <= daysInMonth; day++) {
        this.last.day = day;
        if (this.is_day_in_byday(this.last)) {
          if (!this.has_by_data("BYSETPOS") || this.check_set_position(++setpos) || this.check_set_position(setpos - setpos_total - 1)) {
            data_valid = 1;
            break;
          }
        }
      }
      if (day > daysInMonth) {
        this.last.day = 1;
        this.increment_month();
        if (this.is_day_in_byday(this.last)) {
          if (!this.has_by_data("BYSETPOS") || this.check_set_position(1)) {
            data_valid = 1;
          }
        } else {
          data_valid = 0;
        }
      }
    } else if (this.has_by_data("BYMONTHDAY")) {
      this.by_indices.BYMONTHDAY++;
      if (this.by_indices.BYMONTHDAY >= this.by_data.BYMONTHDAY.length) {
        this.by_indices.BYMONTHDAY = 0;
        this.increment_month();
        if (this.by_indices.BYMONTHDAY >= this.by_data.BYMONTHDAY.length) {
          return 0;
        }
      }
      let daysInMonth = Time.daysInMonth(this.last.month, this.last.year);
      let day = this.by_data.BYMONTHDAY[this.by_indices.BYMONTHDAY];
      if (day < 0) {
        day = daysInMonth + day + 1;
      }
      if (day > daysInMonth) {
        this.last.day = 1;
        data_valid = this.is_day_in_byday(this.last);
      } else {
        this.last.day = day;
      }
    } else {
      this.increment_month();
      let daysInMonth = Time.daysInMonth(this.last.month, this.last.year);
      if (this.by_data.BYMONTHDAY[0] > daysInMonth) {
        data_valid = 0;
      } else {
        this.last.day = this.by_data.BYMONTHDAY[0];
      }
    }
    return data_valid;
  }
  next_weekday_by_week() {
    let end_of_data = 0;
    if (this.next_hour() == 0) {
      return end_of_data;
    }
    if (!this.has_by_data("BYDAY")) {
      return 1;
    }
    for (; ; ) {
      let tt = new Time();
      this.by_indices.BYDAY++;
      if (this.by_indices.BYDAY == Object.keys(this.by_data.BYDAY).length) {
        this.by_indices.BYDAY = 0;
        end_of_data = 1;
      }
      let coded_day = this.by_data.BYDAY[this.by_indices.BYDAY];
      let parts = this.ruleDayOfWeek(coded_day);
      let dow = parts[1];
      dow -= this.rule.wkst;
      if (dow < 0) {
        dow += 7;
      }
      tt.year = this.last.year;
      tt.month = this.last.month;
      tt.day = this.last.day;
      let startOfWeek = tt.startDoyWeek(this.rule.wkst);
      if (dow + startOfWeek < 1) {
        if (!end_of_data) {
          continue;
        }
      }
      let next = Time.fromDayOfYear(startOfWeek + dow, this.last.year);
      this.last.year = next.year;
      this.last.month = next.month;
      this.last.day = next.day;
      return end_of_data;
    }
  }
  next_year() {
    if (this.next_hour() == 0) {
      return 0;
    }
    if (this.days.length == 0 || ++this.days_index == this.days.length) {
      this.days_index = 0;
      this.increment_year(this.rule.interval);
      if (this.has_by_data("BYMONTHDAY")) {
        this.by_data.BYMONTHDAY = this.normalizeByMonthDayRules(
          this.last.year,
          this.last.month,
          this.rule.parts.BYMONTHDAY
        );
      }
      this.expand_year_days(this.last.year);
      if (this.days.length == 0) {
        return 0;
      }
    }
    return this._nextByYearDay();
  }
  _nextByYearDay() {
    let doy = this.days[this.days_index];
    let year = this.last.year;
    if (Math.abs(doy) == 366 && !Time.isLeapYear(this.last.year)) {
      return 0;
    }
    if (doy < 1) {
      doy += 1;
      year += 1;
    }
    let next = Time.fromDayOfYear(doy, year);
    this.last.day = next.day;
    this.last.month = next.month;
    return 1;
  }
  /**
   * @param dow (eg: '1TU', '-1MO')
   * @param {weekDay=} aWeekStart The week start weekday
   * @return [pos, numericDow] (eg: [1, 3]) numericDow is relative to aWeekStart
   */
  ruleDayOfWeek(dow, aWeekStart) {
    let matches = dow.match(/([+-]?[0-9])?(MO|TU|WE|TH|FR|SA|SU)/);
    if (matches) {
      let pos = parseInt(matches[1] || 0, 10);
      dow = Recur.icalDayToNumericDay(matches[2], aWeekStart);
      return [pos, dow];
    } else {
      return [0, 0];
    }
  }
  next_generic(aRuleType, aInterval, aDateAttr, aFollowingAttr, aPreviousIncr) {
    let has_by_rule = aRuleType in this.by_data;
    let this_freq = this.rule.freq == aInterval;
    let end_of_data = 0;
    if (aPreviousIncr && this[aPreviousIncr]() == 0) {
      return end_of_data;
    }
    if (has_by_rule) {
      this.by_indices[aRuleType]++;
      let dta = this.by_data[aRuleType];
      if (this.by_indices[aRuleType] == dta.length) {
        this.by_indices[aRuleType] = 0;
        end_of_data = 1;
      }
      this.last[aDateAttr] = dta[this.by_indices[aRuleType]];
    } else if (this_freq) {
      this["increment_" + aDateAttr](this.rule.interval);
    }
    if (has_by_rule && end_of_data && this_freq) {
      this["increment_" + aFollowingAttr](1);
    }
    return end_of_data;
  }
  increment_monthday(inc) {
    for (let i = 0; i < inc; i++) {
      let daysInMonth = Time.daysInMonth(this.last.month, this.last.year);
      this.last.day++;
      if (this.last.day > daysInMonth) {
        this.last.day -= daysInMonth;
        this.increment_month();
      }
    }
  }
  increment_month() {
    this.last.day = 1;
    if (this.has_by_data("BYMONTH")) {
      this.by_indices.BYMONTH++;
      if (this.by_indices.BYMONTH == this.by_data.BYMONTH.length) {
        this.by_indices.BYMONTH = 0;
        this.increment_year(1);
      }
      this.last.month = this.by_data.BYMONTH[this.by_indices.BYMONTH];
    } else {
      if (this.rule.freq == "MONTHLY") {
        this.last.month += this.rule.interval;
      } else {
        this.last.month++;
      }
      this.last.month--;
      let years = trunc(this.last.month / 12);
      this.last.month %= 12;
      this.last.month++;
      if (years != 0) {
        this.increment_year(years);
      }
    }
    if (this.has_by_data("BYMONTHDAY")) {
      this.by_data.BYMONTHDAY = this.normalizeByMonthDayRules(
        this.last.year,
        this.last.month,
        this.rule.parts.BYMONTHDAY
      );
    }
  }
  increment_year(inc) {
    this.last.day = 1;
    this.last.year += inc;
  }
  increment_generic(inc, aDateAttr, aFactor, aNextIncrement) {
    this.last[aDateAttr] += inc;
    let nextunit = trunc(this.last[aDateAttr] / aFactor);
    this.last[aDateAttr] %= aFactor;
    if (nextunit != 0) {
      this["increment_" + aNextIncrement](nextunit);
    }
  }
  has_by_data(aRuleType) {
    return aRuleType in this.rule.parts;
  }
  expand_year_days(aYear) {
    let t = new Time();
    this.days = [];
    let parts = {};
    let rules = ["BYDAY", "BYWEEKNO", "BYMONTHDAY", "BYMONTH", "BYYEARDAY"];
    for (let part of rules) {
      if (part in this.rule.parts) {
        parts[part] = this.rule.parts[part];
      }
    }
    if ("BYMONTH" in parts && "BYWEEKNO" in parts) {
      let valid = 1;
      let validWeeks = {};
      t.year = aYear;
      t.isDate = true;
      for (let monthIdx = 0; monthIdx < this.by_data.BYMONTH.length; monthIdx++) {
        let month = this.by_data.BYMONTH[monthIdx];
        t.month = month;
        t.day = 1;
        let first_week = t.weekNumber(this.rule.wkst);
        t.day = Time.daysInMonth(month, aYear);
        let last_week = t.weekNumber(this.rule.wkst);
        for (monthIdx = first_week; monthIdx < last_week; monthIdx++) {
          validWeeks[monthIdx] = 1;
        }
      }
      for (let weekIdx = 0; weekIdx < this.by_data.BYWEEKNO.length && valid; weekIdx++) {
        let weekno = this.by_data.BYWEEKNO[weekIdx];
        if (weekno < 52) {
          valid &= validWeeks[weekIdx];
        } else {
          valid = 0;
        }
      }
      if (valid) {
        delete parts.BYMONTH;
      } else {
        delete parts.BYWEEKNO;
      }
    }
    let partCount = Object.keys(parts).length;
    if (partCount == 0) {
      let t1 = this.dtstart.clone();
      t1.year = this.last.year;
      this.days.push(t1.dayOfYear());
    } else if (partCount == 1 && "BYMONTH" in parts) {
      for (let month of this.by_data.BYMONTH) {
        let t2 = this.dtstart.clone();
        t2.year = aYear;
        t2.month = month;
        t2.isDate = true;
        this.days.push(t2.dayOfYear());
      }
    } else if (partCount == 1 && "BYMONTHDAY" in parts) {
      for (let monthday of this.by_data.BYMONTHDAY) {
        let t3 = this.dtstart.clone();
        if (monthday < 0) {
          let daysInMonth = Time.daysInMonth(t3.month, aYear);
          monthday = monthday + daysInMonth + 1;
        }
        t3.day = monthday;
        t3.year = aYear;
        t3.isDate = true;
        this.days.push(t3.dayOfYear());
      }
    } else if (partCount == 2 && "BYMONTHDAY" in parts && "BYMONTH" in parts) {
      for (let month of this.by_data.BYMONTH) {
        let daysInMonth = Time.daysInMonth(month, aYear);
        for (let monthday of this.by_data.BYMONTHDAY) {
          if (monthday < 0) {
            monthday = monthday + daysInMonth + 1;
          }
          t.day = monthday;
          t.month = month;
          t.year = aYear;
          t.isDate = true;
          this.days.push(t.dayOfYear());
        }
      }
    } else if (partCount == 1 && "BYWEEKNO" in parts) ;
    else if (partCount == 2 && "BYWEEKNO" in parts && "BYMONTHDAY" in parts) ;
    else if (partCount == 1 && "BYDAY" in parts) {
      this.days = this.days.concat(this.expand_by_day(aYear));
    } else if (partCount == 2 && "BYDAY" in parts && "BYMONTH" in parts) {
      for (let month of this.by_data.BYMONTH) {
        let daysInMonth = Time.daysInMonth(month, aYear);
        t.year = aYear;
        t.month = month;
        t.day = 1;
        t.isDate = true;
        let first_dow = t.dayOfWeek();
        let doy_offset = t.dayOfYear() - 1;
        t.day = daysInMonth;
        let last_dow = t.dayOfWeek();
        if (this.has_by_data("BYSETPOS")) {
          let by_month_day = [];
          for (let day = 1; day <= daysInMonth; day++) {
            t.day = day;
            if (this.is_day_in_byday(t)) {
              by_month_day.push(day);
            }
          }
          for (let spIndex = 0; spIndex < by_month_day.length; spIndex++) {
            if (this.check_set_position(spIndex + 1) || this.check_set_position(spIndex - by_month_day.length)) {
              this.days.push(doy_offset + by_month_day[spIndex]);
            }
          }
        } else {
          for (let coded_day of this.by_data.BYDAY) {
            let bydayParts = this.ruleDayOfWeek(coded_day);
            let pos = bydayParts[0];
            let dow = bydayParts[1];
            let month_day;
            let first_matching_day = (dow + 7 - first_dow) % 7 + 1;
            let last_matching_day = daysInMonth - (last_dow + 7 - dow) % 7;
            if (pos == 0) {
              for (let day = first_matching_day; day <= daysInMonth; day += 7) {
                this.days.push(doy_offset + day);
              }
            } else if (pos > 0) {
              month_day = first_matching_day + (pos - 1) * 7;
              if (month_day <= daysInMonth) {
                this.days.push(doy_offset + month_day);
              }
            } else {
              month_day = last_matching_day + (pos + 1) * 7;
              if (month_day > 0) {
                this.days.push(doy_offset + month_day);
              }
            }
          }
        }
      }
      this.days.sort(function(a, b) {
        return a - b;
      });
    } else if (partCount == 2 && "BYDAY" in parts && "BYMONTHDAY" in parts) {
      let expandedDays = this.expand_by_day(aYear);
      for (let day of expandedDays) {
        let tt = Time.fromDayOfYear(day, aYear);
        if (this.by_data.BYMONTHDAY.indexOf(tt.day) >= 0) {
          this.days.push(day);
        }
      }
    } else if (partCount == 3 && "BYDAY" in parts && "BYMONTHDAY" in parts && "BYMONTH" in parts) {
      let expandedDays = this.expand_by_day(aYear);
      for (let day of expandedDays) {
        let tt = Time.fromDayOfYear(day, aYear);
        if (this.by_data.BYMONTH.indexOf(tt.month) >= 0 && this.by_data.BYMONTHDAY.indexOf(tt.day) >= 0) {
          this.days.push(day);
        }
      }
    } else if (partCount == 2 && "BYDAY" in parts && "BYWEEKNO" in parts) {
      let expandedDays = this.expand_by_day(aYear);
      for (let day of expandedDays) {
        let tt = Time.fromDayOfYear(day, aYear);
        let weekno = tt.weekNumber(this.rule.wkst);
        if (this.by_data.BYWEEKNO.indexOf(weekno)) {
          this.days.push(day);
        }
      }
    } else if (partCount == 3 && "BYDAY" in parts && "BYWEEKNO" in parts && "BYMONTHDAY" in parts) ;
    else if (partCount == 1 && "BYYEARDAY" in parts) {
      this.days = this.days.concat(this.by_data.BYYEARDAY);
    } else if (partCount == 2 && "BYYEARDAY" in parts && "BYDAY" in parts) {
      let daysInYear2 = Time.isLeapYear(aYear) ? 366 : 365;
      let expandedDays = new Set(this.expand_by_day(aYear));
      for (let doy of this.by_data.BYYEARDAY) {
        if (doy < 0) {
          doy += daysInYear2 + 1;
        }
        if (expandedDays.has(doy)) {
          this.days.push(doy);
        }
      }
    } else {
      this.days = [];
    }
    let daysInYear = Time.isLeapYear(aYear) ? 366 : 365;
    this.days.sort((a, b) => {
      if (a < 0) a += daysInYear + 1;
      if (b < 0) b += daysInYear + 1;
      return a - b;
    });
    return 0;
  }
  expand_by_day(aYear) {
    let days_list = [];
    let tmp = this.last.clone();
    tmp.year = aYear;
    tmp.month = 1;
    tmp.day = 1;
    tmp.isDate = true;
    let start_dow = tmp.dayOfWeek();
    tmp.month = 12;
    tmp.day = 31;
    tmp.isDate = true;
    let end_dow = tmp.dayOfWeek();
    let end_year_day = tmp.dayOfYear();
    for (let day of this.by_data.BYDAY) {
      let parts = this.ruleDayOfWeek(day);
      let pos = parts[0];
      let dow = parts[1];
      if (pos == 0) {
        let tmp_start_doy = (dow + 7 - start_dow) % 7 + 1;
        for (let doy = tmp_start_doy; doy <= end_year_day; doy += 7) {
          days_list.push(doy);
        }
      } else if (pos > 0) {
        let first;
        if (dow >= start_dow) {
          first = dow - start_dow + 1;
        } else {
          first = dow - start_dow + 8;
        }
        days_list.push(first + (pos - 1) * 7);
      } else {
        let last;
        pos = -pos;
        if (dow <= end_dow) {
          last = end_year_day - end_dow + dow;
        } else {
          last = end_year_day - end_dow + dow - 7;
        }
        days_list.push(last - (pos - 1) * 7);
      }
    }
    return days_list;
  }
  is_day_in_byday(tt) {
    if (this.by_data.BYDAY) {
      for (let day of this.by_data.BYDAY) {
        let parts = this.ruleDayOfWeek(day);
        let pos = parts[0];
        let dow = parts[1];
        let this_dow = tt.dayOfWeek();
        if (pos == 0 && dow == this_dow || tt.nthWeekDay(dow, pos) == tt.day) {
          return 1;
        }
      }
    }
    return 0;
  }
  /**
   * Checks if given value is in BYSETPOS.
   *
   * @private
   * @param {Numeric} aPos position to check for.
   * @return {Boolean} false unless BYSETPOS rules exist
   *                   and the given value is present in rules.
   */
  check_set_position(aPos) {
    if (this.has_by_data("BYSETPOS")) {
      let idx = this.by_data.BYSETPOS.indexOf(aPos);
      return idx !== -1;
    }
    return false;
  }
  sort_byday_rules(aRules) {
    for (let i = 0; i < aRules.length; i++) {
      for (let j = 0; j < i; j++) {
        let one = this.ruleDayOfWeek(aRules[j], this.rule.wkst)[1];
        let two = this.ruleDayOfWeek(aRules[i], this.rule.wkst)[1];
        if (one > two) {
          let tmp = aRules[i];
          aRules[i] = aRules[j];
          aRules[j] = tmp;
        }
      }
    }
  }
  check_contract_restriction(aRuleType, v) {
    let indexMapValue = RecurIterator._indexMap[aRuleType];
    let ruleMapValue = RecurIterator._expandMap[this.rule.freq][indexMapValue];
    let pass = false;
    if (aRuleType in this.by_data && ruleMapValue == RecurIterator.CONTRACT) {
      let ruleType = this.by_data[aRuleType];
      for (let bydata of ruleType) {
        if (bydata == v) {
          pass = true;
          break;
        }
      }
    } else {
      pass = true;
    }
    return pass;
  }
  check_contracting_rules() {
    let dow = this.last.dayOfWeek();
    let weekNo = this.last.weekNumber(this.rule.wkst);
    let doy = this.last.dayOfYear();
    return this.check_contract_restriction("BYSECOND", this.last.second) && this.check_contract_restriction("BYMINUTE", this.last.minute) && this.check_contract_restriction("BYHOUR", this.last.hour) && this.check_contract_restriction("BYDAY", Recur.numericDayToIcalDay(dow)) && this.check_contract_restriction("BYWEEKNO", weekNo) && this.check_contract_restriction("BYMONTHDAY", this.last.day) && this.check_contract_restriction("BYMONTH", this.last.month) && this.check_contract_restriction("BYYEARDAY", doy);
  }
  setup_defaults(aRuleType, req, deftime) {
    let indexMapValue = RecurIterator._indexMap[aRuleType];
    let ruleMapValue = RecurIterator._expandMap[this.rule.freq][indexMapValue];
    if (ruleMapValue != RecurIterator.CONTRACT) {
      if (!(aRuleType in this.by_data)) {
        this.by_data[aRuleType] = [deftime];
      }
      if (this.rule.freq != req) {
        return this.by_data[aRuleType][0];
      }
    }
    return deftime;
  }
  /**
   * Convert iterator into a serialize-able object.  Will preserve current
   * iteration sequence to ensure the seamless continuation of the recurrence
   * rule.
   * @return {Object}
   */
  toJSON() {
    let result = /* @__PURE__ */ Object.create(null);
    result.initialized = this.initialized;
    result.rule = this.rule.toJSON();
    result.dtstart = this.dtstart.toJSON();
    result.by_data = this.by_data;
    result.days = this.days;
    result.last = this.last.toJSON();
    result.by_indices = this.by_indices;
    result.occurrence_number = this.occurrence_number;
    return result;
  }
}
class InvalidRecurrenceRuleError extends Error {
  constructor() {
    super("Recurrence rule has no valid occurrences");
  }
}
const VALID_DAY_NAMES = /^(SU|MO|TU|WE|TH|FR|SA)$/;
const VALID_BYDAY_PART = /^([+-])?(5[0-3]|[1-4][0-9]|[1-9])?(SU|MO|TU|WE|TH|FR|SA)$/;
const DOW_MAP = {
  SU: Time.SUNDAY,
  MO: Time.MONDAY,
  TU: Time.TUESDAY,
  WE: Time.WEDNESDAY,
  TH: Time.THURSDAY,
  FR: Time.FRIDAY,
  SA: Time.SATURDAY
};
const REVERSE_DOW_MAP = Object.fromEntries(Object.entries(DOW_MAP).map((entry) => entry.reverse()));
const ALLOWED_FREQ = [
  "SECONDLY",
  "MINUTELY",
  "HOURLY",
  "DAILY",
  "WEEKLY",
  "MONTHLY",
  "YEARLY"
];
class Recur {
  /**
   * Creates a new {@link ICAL.Recur} instance from the passed string.
   *
   * @param {String} string         The string to parse
   * @return {Recur}                The created recurrence instance
   */
  static fromString(string) {
    let data = this._stringToData(string, false);
    return new Recur(data);
  }
  /**
   * Creates a new {@link ICAL.Recur} instance using members from the passed
   * data object.
   *
   * @param {Object} aData                              An object with members of the recurrence
   * @param {frequencyValues=} aData.freq               The frequency value
   * @param {Number=} aData.interval                    The INTERVAL value
   * @param {weekDay=} aData.wkst                       The week start value
   * @param {Time=} aData.until                         The end of the recurrence set
   * @param {Number=} aData.count                       The number of occurrences
   * @param {Array.<Number>=} aData.bysecond            The seconds for the BYSECOND part
   * @param {Array.<Number>=} aData.byminute            The minutes for the BYMINUTE part
   * @param {Array.<Number>=} aData.byhour              The hours for the BYHOUR part
   * @param {Array.<String>=} aData.byday               The BYDAY values
   * @param {Array.<Number>=} aData.bymonthday          The days for the BYMONTHDAY part
   * @param {Array.<Number>=} aData.byyearday           The days for the BYYEARDAY part
   * @param {Array.<Number>=} aData.byweekno            The weeks for the BYWEEKNO part
   * @param {Array.<Number>=} aData.bymonth             The month for the BYMONTH part
   * @param {Array.<Number>=} aData.bysetpos            The positionals for the BYSETPOS part
   */
  static fromData(aData) {
    return new Recur(aData);
  }
  /**
   * Converts a recurrence string to a data object, suitable for the fromData
   * method.
   *
   * @private
   * @param {String} string     The string to parse
   * @param {Boolean} fmtIcal   If true, the string is considered to be an
   *                              iCalendar string
   * @return {Recur}            The recurrence instance
   */
  static _stringToData(string, fmtIcal) {
    let dict = /* @__PURE__ */ Object.create(null);
    let values = string.split(";");
    let len = values.length;
    for (let i = 0; i < len; i++) {
      let parts = values[i].split("=");
      let ucname = parts[0].toUpperCase();
      let lcname = parts[0].toLowerCase();
      let name = fmtIcal ? lcname : ucname;
      let value = parts[1];
      if (ucname in partDesign) {
        let partArr = value.split(",");
        let partSet = /* @__PURE__ */ new Set();
        for (let part of partArr) {
          partSet.add(partDesign[ucname](part));
        }
        partArr = [...partSet];
        dict[name] = partArr.length == 1 ? partArr[0] : partArr;
      } else if (ucname in optionDesign) {
        optionDesign[ucname](value, dict, fmtIcal);
      } else {
        dict[lcname] = value;
      }
    }
    return dict;
  }
  /**
   * Convert an ical representation of a day (SU, MO, etc..)
   * into a numeric value of that day.
   *
   * @param {String} string     The iCalendar day name
   * @param {weekDay=} aWeekStart
   *        The week start weekday, defaults to SUNDAY
   * @return {Number}           Numeric value of given day
   */
  static icalDayToNumericDay(string, aWeekStart) {
    let firstDow = aWeekStart || Time.SUNDAY;
    return (DOW_MAP[string] - firstDow + 7) % 7 + 1;
  }
  /**
   * Convert a numeric day value into its ical representation (SU, MO, etc..)
   *
   * @param {Number} num        Numeric value of given day
   * @param {weekDay=} aWeekStart
   *        The week start weekday, defaults to SUNDAY
   * @return {String}           The ICAL day value, e.g SU,MO,...
   */
  static numericDayToIcalDay(num, aWeekStart) {
    let firstDow = aWeekStart || Time.SUNDAY;
    let dow = num + firstDow - Time.SUNDAY;
    if (dow > 7) {
      dow -= 7;
    }
    return REVERSE_DOW_MAP[dow];
  }
  /**
   * Create a new instance of the Recur class.
   *
   * @param {Object} data                               An object with members of the recurrence
   * @param {frequencyValues=} data.freq                The frequency value
   * @param {Number=} data.interval                     The INTERVAL value
   * @param {weekDay=} data.wkst                        The week start value
   * @param {Time=} data.until                          The end of the recurrence set
   * @param {Number=} data.count                        The number of occurrences
   * @param {Array.<Number>=} data.bysecond             The seconds for the BYSECOND part
   * @param {Array.<Number>=} data.byminute             The minutes for the BYMINUTE part
   * @param {Array.<Number>=} data.byhour               The hours for the BYHOUR part
   * @param {Array.<String>=} data.byday                The BYDAY values
   * @param {Array.<Number>=} data.bymonthday           The days for the BYMONTHDAY part
   * @param {Array.<Number>=} data.byyearday            The days for the BYYEARDAY part
   * @param {Array.<Number>=} data.byweekno             The weeks for the BYWEEKNO part
   * @param {Array.<Number>=} data.bymonth              The month for the BYMONTH part
   * @param {Array.<Number>=} data.bysetpos             The positionals for the BYSETPOS part
   */
  constructor(data) {
    this.wrappedJSObject = this;
    this.parts = {};
    if (data && typeof data === "object") {
      this.fromData(data);
    }
  }
  /**
   * An object holding the BY-parts of the recurrence rule
   * @memberof ICAL.Recur
   * @typedef {Object} byParts
   * @property {Array.<Number>=} BYSECOND            The seconds for the BYSECOND part
   * @property {Array.<Number>=} BYMINUTE            The minutes for the BYMINUTE part
   * @property {Array.<Number>=} BYHOUR              The hours for the BYHOUR part
   * @property {Array.<String>=} BYDAY               The BYDAY values
   * @property {Array.<Number>=} BYMONTHDAY          The days for the BYMONTHDAY part
   * @property {Array.<Number>=} BYYEARDAY           The days for the BYYEARDAY part
   * @property {Array.<Number>=} BYWEEKNO            The weeks for the BYWEEKNO part
   * @property {Array.<Number>=} BYMONTH             The month for the BYMONTH part
   * @property {Array.<Number>=} BYSETPOS            The positionals for the BYSETPOS part
   */
  /**
   * An object holding the BY-parts of the recurrence rule
   * @type {byParts}
   */
  parts = null;
  /**
   * The interval value for the recurrence rule.
   * @type {Number}
   */
  interval = 1;
  /**
   * The week start day
   *
   * @type {weekDay}
   * @default ICAL.Time.MONDAY
   */
  wkst = Time.MONDAY;
  /**
   * The end of the recurrence
   * @type {?Time}
   */
  until = null;
  /**
   * The maximum number of occurrences
   * @type {?Number}
   */
  count = null;
  /**
   * The frequency value.
   * @type {frequencyValues}
   */
  freq = null;
  /**
   * The class identifier.
   * @constant
   * @type {String}
   * @default "icalrecur"
   */
  icalclass = "icalrecur";
  /**
   * The type name, to be used in the jCal object.
   * @constant
   * @type {String}
   * @default "recur"
   */
  icaltype = "recur";
  /**
   * Create a new iterator for this recurrence rule. The passed start date
   * must be the start date of the event, not the start of the range to
   * search in.
   *
   * @example
   * let recur = comp.getFirstPropertyValue('rrule');
   * let dtstart = comp.getFirstPropertyValue('dtstart');
   * let iter = recur.iterator(dtstart);
   * for (let next = iter.next(); next; next = iter.next()) {
   *   if (next.compare(rangeStart) < 0) {
   *     continue;
   *   }
   *   console.log(next.toString());
   * }
   *
   * @param {Time} aStart        The item's start date
   * @return {RecurIterator}     The recurrence iterator
   */
  iterator(aStart) {
    return new RecurIterator({
      rule: this,
      dtstart: aStart
    });
  }
  /**
   * Returns a clone of the recurrence object.
   *
   * @return {Recur}      The cloned object
   */
  clone() {
    return new Recur(this.toJSON());
  }
  /**
   * Checks if the current rule is finite, i.e. has a count or until part.
   *
   * @return {Boolean}        True, if the rule is finite
   */
  isFinite() {
    return !!(this.count || this.until);
  }
  /**
   * Checks if the current rule has a count part, and not limited by an until
   * part.
   *
   * @return {Boolean}        True, if the rule is by count
   */
  isByCount() {
    return !!(this.count && !this.until);
  }
  /**
   * Adds a component (part) to the recurrence rule. This is not a component
   * in the sense of {@link ICAL.Component}, but a part of the recurrence
   * rule, i.e. BYMONTH.
   *
   * @param {String} aType            The name of the component part
   * @param {Array|String} aValue     The component value
   */
  addComponent(aType, aValue) {
    let ucname = aType.toUpperCase();
    if (ucname in this.parts) {
      this.parts[ucname].push(aValue);
    } else {
      this.parts[ucname] = [aValue];
    }
  }
  /**
   * Sets the component value for the given by-part.
   *
   * @param {String} aType        The component part name
   * @param {Array} aValues       The component values
   */
  setComponent(aType, aValues) {
    this.parts[aType.toUpperCase()] = aValues.slice();
  }
  /**
   * Gets (a copy) of the requested component value.
   *
   * @param {String} aType        The component part name
   * @return {Array}              The component part value
   */
  getComponent(aType) {
    let ucname = aType.toUpperCase();
    return ucname in this.parts ? this.parts[ucname].slice() : [];
  }
  /**
   * Retrieves the next occurrence after the given recurrence id. See the
   * guide on {@tutorial terminology} for more details.
   *
   * NOTE: Currently, this method iterates all occurrences from the start
   * date. It should not be called in a loop for performance reasons. If you
   * would like to get more than one occurrence, you can iterate the
   * occurrences manually, see the example on the
   * {@link ICAL.Recur#iterator iterator} method.
   *
   * @param {Time} aStartTime        The start of the event series
   * @param {Time} aRecurrenceId     The date of the last occurrence
   * @return {Time}                  The next occurrence after
   */
  getNextOccurrence(aStartTime, aRecurrenceId) {
    let iter = this.iterator(aStartTime);
    let next;
    do {
      next = iter.next();
    } while (next && next.compare(aRecurrenceId) <= 0);
    if (next && aRecurrenceId.zone) {
      next.zone = aRecurrenceId.zone;
    }
    return next;
  }
  /**
   * Sets up the current instance using members from the passed data object.
   *
   * @param {Object} data                               An object with members of the recurrence
   * @param {frequencyValues=} data.freq                The frequency value
   * @param {Number=} data.interval                     The INTERVAL value
   * @param {weekDay=} data.wkst                        The week start value
   * @param {Time=} data.until                          The end of the recurrence set
   * @param {Number=} data.count                        The number of occurrences
   * @param {Array.<Number>=} data.bysecond             The seconds for the BYSECOND part
   * @param {Array.<Number>=} data.byminute             The minutes for the BYMINUTE part
   * @param {Array.<Number>=} data.byhour               The hours for the BYHOUR part
   * @param {Array.<String>=} data.byday                The BYDAY values
   * @param {Array.<Number>=} data.bymonthday           The days for the BYMONTHDAY part
   * @param {Array.<Number>=} data.byyearday            The days for the BYYEARDAY part
   * @param {Array.<Number>=} data.byweekno             The weeks for the BYWEEKNO part
   * @param {Array.<Number>=} data.bymonth              The month for the BYMONTH part
   * @param {Array.<Number>=} data.bysetpos             The positionals for the BYSETPOS part
   */
  fromData(data) {
    for (let key in data) {
      let uckey = key.toUpperCase();
      if (uckey in partDesign) {
        if (Array.isArray(data[key])) {
          this.parts[uckey] = data[key];
        } else {
          this.parts[uckey] = [data[key]];
        }
      } else {
        this[key] = data[key];
      }
    }
    if (this.interval && typeof this.interval != "number") {
      optionDesign.INTERVAL(this.interval, this);
    }
    if (this.wkst && typeof this.wkst != "number") {
      this.wkst = Recur.icalDayToNumericDay(this.wkst);
    }
    if (this.until && !(this.until instanceof Time)) {
      this.until = Time.fromString(this.until);
    }
  }
  /**
   * The jCal representation of this recurrence type.
   * @return {Object}
   */
  toJSON() {
    let res = /* @__PURE__ */ Object.create(null);
    res.freq = this.freq;
    if (this.count) {
      res.count = this.count;
    }
    if (this.interval > 1) {
      res.interval = this.interval;
    }
    for (let [k, kparts] of Object.entries(this.parts)) {
      if (Array.isArray(kparts) && kparts.length == 1) {
        res[k.toLowerCase()] = kparts[0];
      } else {
        res[k.toLowerCase()] = clone(kparts);
      }
    }
    if (this.until) {
      res.until = this.until.toString();
    }
    if ("wkst" in this && this.wkst !== Time.DEFAULT_WEEK_START) {
      res.wkst = Recur.numericDayToIcalDay(this.wkst);
    }
    return res;
  }
  /**
   * The string representation of this recurrence rule.
   * @return {String}
   */
  toString() {
    let str = "FREQ=" + this.freq;
    if (this.count) {
      str += ";COUNT=" + this.count;
    }
    if (this.interval > 1) {
      str += ";INTERVAL=" + this.interval;
    }
    for (let [k, v] of Object.entries(this.parts)) {
      str += ";" + k + "=" + v;
    }
    if (this.until) {
      str += ";UNTIL=" + this.until.toICALString();
    }
    if ("wkst" in this && this.wkst !== Time.DEFAULT_WEEK_START) {
      str += ";WKST=" + Recur.numericDayToIcalDay(this.wkst);
    }
    return str;
  }
}
function parseNumericValue(type, min, max, value) {
  let result = value;
  if (value[0] === "+") {
    result = value.slice(1);
  }
  result = strictParseInt(result);
  if (min !== void 0 && value < min) {
    throw new Error(
      type + ': invalid value "' + value + '" must be > ' + min
    );
  }
  if (max !== void 0 && value > max) {
    throw new Error(
      type + ': invalid value "' + value + '" must be < ' + min
    );
  }
  return result;
}
const optionDesign = {
  FREQ: function(value, dict, fmtIcal) {
    if (ALLOWED_FREQ.indexOf(value) !== -1) {
      dict.freq = value;
    } else {
      throw new Error(
        'invalid frequency "' + value + '" expected: "' + ALLOWED_FREQ.join(", ") + '"'
      );
    }
  },
  COUNT: function(value, dict, fmtIcal) {
    dict.count = strictParseInt(value);
  },
  INTERVAL: function(value, dict, fmtIcal) {
    dict.interval = strictParseInt(value);
    if (dict.interval < 1) {
      dict.interval = 1;
    }
  },
  UNTIL: function(value, dict, fmtIcal) {
    if (value.length > 10) {
      dict.until = design.icalendar.value["date-time"].fromICAL(value);
    } else {
      dict.until = design.icalendar.value.date.fromICAL(value);
    }
    if (!fmtIcal) {
      dict.until = Time.fromString(dict.until);
    }
  },
  WKST: function(value, dict, fmtIcal) {
    if (VALID_DAY_NAMES.test(value)) {
      dict.wkst = Recur.icalDayToNumericDay(value);
    } else {
      throw new Error('invalid WKST value "' + value + '"');
    }
  }
};
const partDesign = {
  BYSECOND: parseNumericValue.bind(void 0, "BYSECOND", 0, 60),
  BYMINUTE: parseNumericValue.bind(void 0, "BYMINUTE", 0, 59),
  BYHOUR: parseNumericValue.bind(void 0, "BYHOUR", 0, 23),
  BYDAY: function(value) {
    if (VALID_BYDAY_PART.test(value)) {
      return value;
    } else {
      throw new Error('invalid BYDAY value "' + value + '"');
    }
  },
  BYMONTHDAY: parseNumericValue.bind(void 0, "BYMONTHDAY", -31, 31),
  BYYEARDAY: parseNumericValue.bind(void 0, "BYYEARDAY", -366, 366),
  BYWEEKNO: parseNumericValue.bind(void 0, "BYWEEKNO", -53, 53),
  BYMONTH: parseNumericValue.bind(void 0, "BYMONTH", 1, 12),
  BYSETPOS: parseNumericValue.bind(void 0, "BYSETPOS", -366, 366)
};
const FROM_ICAL_NEWLINE = /\\\\|\\;|\\,|\\[Nn]/g;
const TO_ICAL_NEWLINE = /\\|;|,|\n/g;
const FROM_VCARD_NEWLINE = /\\\\|\\,|\\[Nn]/g;
const TO_VCARD_NEWLINE = /\\|,|\n/g;
function createTextType(fromNewline, toNewline) {
  let result = {
    matches: /.*/,
    fromICAL: function(aValue, structuredEscape) {
      return replaceNewline(aValue, fromNewline, structuredEscape);
    },
    toICAL: function(aValue, structuredEscape) {
      let regEx = toNewline;
      if (structuredEscape)
        regEx = new RegExp(regEx.source + "|" + structuredEscape, regEx.flags);
      return aValue.replace(regEx, function(str) {
        switch (str) {
          case "\\":
            return "\\\\";
          case ";":
            return "\\;";
          case ",":
            return "\\,";
          case "\n":
            return "\\n";
          /* c8 ignore next 2 */
          default:
            return str;
        }
      });
    }
  };
  return result;
}
const DEFAULT_TYPE_TEXT = { defaultType: "text" };
const DEFAULT_TYPE_TEXT_MULTI = { defaultType: "text", multiValue: "," };
const DEFAULT_TYPE_TEXT_STRUCTURED = { defaultType: "text", structuredValue: ";" };
const DEFAULT_TYPE_INTEGER = { defaultType: "integer" };
const DEFAULT_TYPE_DATETIME_DATE = { defaultType: "date-time", allowedTypes: ["date-time", "date"] };
const DEFAULT_TYPE_DATETIME = { defaultType: "date-time" };
const DEFAULT_TYPE_URI = { defaultType: "uri" };
const DEFAULT_TYPE_UTCOFFSET = { defaultType: "utc-offset" };
const DEFAULT_TYPE_RECUR = { defaultType: "recur" };
const DEFAULT_TYPE_DATE_ANDOR_TIME = { defaultType: "date-and-or-time", allowedTypes: ["date-time", "date", "text"] };
function replaceNewlineReplace(string) {
  switch (string) {
    case "\\\\":
      return "\\";
    case "\\;":
      return ";";
    case "\\,":
      return ",";
    case "\\n":
    case "\\N":
      return "\n";
    /* c8 ignore next 2 */
    default:
      return string;
  }
}
function replaceNewline(value, newline, structuredEscape) {
  if (value.indexOf("\\") === -1) {
    return value;
  }
  if (structuredEscape)
    newline = new RegExp(newline.source + "|\\\\" + structuredEscape, newline.flags);
  return value.replace(newline, replaceNewlineReplace);
}
let commonProperties = {
  "categories": DEFAULT_TYPE_TEXT_MULTI,
  "url": DEFAULT_TYPE_URI,
  "version": DEFAULT_TYPE_TEXT,
  "uid": DEFAULT_TYPE_TEXT
};
let commonValues = {
  "boolean": {
    values: ["TRUE", "FALSE"],
    fromICAL: function(aValue) {
      switch (aValue) {
        case "TRUE":
          return true;
        case "FALSE":
          return false;
        default:
          return false;
      }
    },
    toICAL: function(aValue) {
      if (aValue) {
        return "TRUE";
      }
      return "FALSE";
    }
  },
  float: {
    matches: /^[+-]?\d+\.\d+$/,
    fromICAL: function(aValue) {
      let parsed = parseFloat(aValue);
      if (isStrictlyNaN(parsed)) {
        return 0;
      }
      return parsed;
    },
    toICAL: function(aValue) {
      return String(aValue);
    }
  },
  integer: {
    fromICAL: function(aValue) {
      let parsed = parseInt(aValue);
      if (isStrictlyNaN(parsed)) {
        return 0;
      }
      return parsed;
    },
    toICAL: function(aValue) {
      return String(aValue);
    }
  },
  "utc-offset": {
    toICAL: function(aValue) {
      if (aValue.length < 7) {
        return aValue.slice(0, 3) + aValue.slice(4, 6);
      } else {
        return aValue.slice(0, 3) + aValue.slice(4, 6) + aValue.slice(7, 9);
      }
    },
    fromICAL: function(aValue) {
      if (aValue.length < 6) {
        return aValue.slice(0, 3) + ":" + aValue.slice(3, 5);
      } else {
        return aValue.slice(0, 3) + ":" + aValue.slice(3, 5) + ":" + aValue.slice(5, 7);
      }
    },
    decorate: function(aValue) {
      return UtcOffset.fromString(aValue);
    },
    undecorate: function(aValue) {
      return aValue.toString();
    }
  }
};
let icalParams = {
  // Although the syntax is DQUOTE uri DQUOTE, I don't think we should
  // enforce anything aside from it being a valid content line.
  //
  // At least some params require - if multi values are used - DQUOTEs
  // for each of its values - e.g. delegated-from="uri1","uri2"
  // To indicate this, I introduced the new k/v pair
  // multiValueSeparateDQuote: true
  //
  // "ALTREP": { ... },
  // CN just wants a param-value
  // "CN": { ... }
  "cutype": {
    values: ["INDIVIDUAL", "GROUP", "RESOURCE", "ROOM", "UNKNOWN"],
    allowXName: true,
    allowIanaToken: true
  },
  "delegated-from": {
    valueType: "cal-address",
    multiValue: ",",
    multiValueSeparateDQuote: true
  },
  "delegated-to": {
    valueType: "cal-address",
    multiValue: ",",
    multiValueSeparateDQuote: true
  },
  // "DIR": { ... }, // See ALTREP
  "encoding": {
    values: ["8BIT", "BASE64"]
  },
  // "FMTTYPE": { ... }, // See ALTREP
  "fbtype": {
    values: ["FREE", "BUSY", "BUSY-UNAVAILABLE", "BUSY-TENTATIVE"],
    allowXName: true,
    allowIanaToken: true
  },
  // "LANGUAGE": { ... }, // See ALTREP
  "member": {
    valueType: "cal-address",
    multiValue: ",",
    multiValueSeparateDQuote: true
  },
  "partstat": {
    // TODO These values are actually different per-component
    values: [
      "NEEDS-ACTION",
      "ACCEPTED",
      "DECLINED",
      "TENTATIVE",
      "DELEGATED",
      "COMPLETED",
      "IN-PROCESS"
    ],
    allowXName: true,
    allowIanaToken: true
  },
  "range": {
    values: ["THISANDFUTURE"]
  },
  "related": {
    values: ["START", "END"]
  },
  "reltype": {
    values: ["PARENT", "CHILD", "SIBLING"],
    allowXName: true,
    allowIanaToken: true
  },
  "role": {
    values: [
      "REQ-PARTICIPANT",
      "CHAIR",
      "OPT-PARTICIPANT",
      "NON-PARTICIPANT"
    ],
    allowXName: true,
    allowIanaToken: true
  },
  "rsvp": {
    values: ["TRUE", "FALSE"]
  },
  "sent-by": {
    valueType: "cal-address"
  },
  "tzid": {
    matches: /^\//
  },
  "value": {
    // since the value here is a 'type' lowercase is used.
    values: [
      "binary",
      "boolean",
      "cal-address",
      "date",
      "date-time",
      "duration",
      "float",
      "integer",
      "period",
      "recur",
      "text",
      "time",
      "uri",
      "utc-offset"
    ],
    allowXName: true,
    allowIanaToken: true
  }
};
const icalValues = extend(commonValues, {
  text: createTextType(FROM_ICAL_NEWLINE, TO_ICAL_NEWLINE),
  uri: {
    // TODO
    /* ... */
  },
  "binary": {
    decorate: function(aString) {
      return Binary.fromString(aString);
    },
    undecorate: function(aBinary) {
      return aBinary.toString();
    }
  },
  "cal-address": {
    // needs to be an uri
  },
  "date": {
    decorate: function(aValue, aProp) {
      {
        return Time.fromDateString(aValue, aProp);
      }
    },
    /**
     * undecorates a time object.
     */
    undecorate: function(aValue) {
      return aValue.toString();
    },
    fromICAL: function(aValue) {
      {
        return aValue.slice(0, 4) + "-" + aValue.slice(4, 6) + "-" + aValue.slice(6, 8);
      }
    },
    toICAL: function(aValue) {
      let len = aValue.length;
      if (len == 10) {
        return aValue.slice(0, 4) + aValue.slice(5, 7) + aValue.slice(8, 10);
      } else if (len >= 19) {
        return icalValues["date-time"].toICAL(aValue);
      } else {
        return aValue;
      }
    }
  },
  "date-time": {
    fromICAL: function(aValue) {
      {
        let result = aValue.slice(0, 4) + "-" + aValue.slice(4, 6) + "-" + aValue.slice(6, 8) + "T" + aValue.slice(9, 11) + ":" + aValue.slice(11, 13) + ":" + aValue.slice(13, 15);
        if (aValue[15] && aValue[15] === "Z") {
          result += "Z";
        }
        return result;
      }
    },
    toICAL: function(aValue) {
      let len = aValue.length;
      if (len >= 19) {
        let result = aValue.slice(0, 4) + aValue.slice(5, 7) + // grab the (DDTHH) segment
        aValue.slice(8, 13) + // MM
        aValue.slice(14, 16) + // SS
        aValue.slice(17, 19);
        if (aValue[19] && aValue[19] === "Z") {
          result += "Z";
        }
        return result;
      } else {
        return aValue;
      }
    },
    decorate: function(aValue, aProp) {
      {
        return Time.fromDateTimeString(aValue, aProp);
      }
    },
    undecorate: function(aValue) {
      return aValue.toString();
    }
  },
  duration: {
    decorate: function(aValue) {
      return Duration.fromString(aValue);
    },
    undecorate: function(aValue) {
      return aValue.toString();
    }
  },
  period: {
    fromICAL: function(string) {
      let parts = string.split("/");
      parts[0] = icalValues["date-time"].fromICAL(parts[0]);
      if (!Duration.isValueString(parts[1])) {
        parts[1] = icalValues["date-time"].fromICAL(parts[1]);
      }
      return parts;
    },
    toICAL: function(parts) {
      parts = parts.slice();
      {
        parts[0] = icalValues["date-time"].toICAL(parts[0]);
      }
      if (!Duration.isValueString(parts[1])) {
        {
          parts[1] = icalValues["date-time"].toICAL(parts[1]);
        }
      }
      return parts.join("/");
    },
    decorate: function(aValue, aProp) {
      return Period.fromJSON(aValue, aProp, false);
    },
    undecorate: function(aValue) {
      return aValue.toJSON();
    }
  },
  recur: {
    fromICAL: function(string) {
      return Recur._stringToData(string, true);
    },
    toICAL: function(data) {
      let str = "";
      for (let [k, val] of Object.entries(data)) {
        if (k == "until") {
          if (val.length > 10) {
            val = icalValues["date-time"].toICAL(val);
          } else {
            val = icalValues.date.toICAL(val);
          }
        } else if (k == "wkst") {
          if (typeof val === "number") {
            val = Recur.numericDayToIcalDay(val);
          }
        } else if (Array.isArray(val)) {
          val = val.join(",");
        }
        str += k.toUpperCase() + "=" + val + ";";
      }
      return str.slice(0, Math.max(0, str.length - 1));
    },
    decorate: function decorate(aValue) {
      return Recur.fromData(aValue);
    },
    undecorate: function(aRecur) {
      return aRecur.toJSON();
    }
  },
  time: {
    fromICAL: function(aValue) {
      if (aValue.length < 6) {
        return aValue;
      }
      let result = aValue.slice(0, 2) + ":" + aValue.slice(2, 4) + ":" + aValue.slice(4, 6);
      if (aValue[6] === "Z") {
        result += "Z";
      }
      return result;
    },
    toICAL: function(aValue) {
      if (aValue.length < 8) {
        return aValue;
      }
      let result = aValue.slice(0, 2) + aValue.slice(3, 5) + aValue.slice(6, 8);
      if (aValue[8] === "Z") {
        result += "Z";
      }
      return result;
    }
  }
});
let icalProperties = extend(commonProperties, {
  "action": DEFAULT_TYPE_TEXT,
  "attach": { defaultType: "uri" },
  "attendee": { defaultType: "cal-address" },
  "calscale": DEFAULT_TYPE_TEXT,
  "class": DEFAULT_TYPE_TEXT,
  "comment": DEFAULT_TYPE_TEXT,
  "completed": DEFAULT_TYPE_DATETIME,
  "contact": DEFAULT_TYPE_TEXT,
  "created": DEFAULT_TYPE_DATETIME,
  "description": DEFAULT_TYPE_TEXT,
  "dtend": DEFAULT_TYPE_DATETIME_DATE,
  "dtstamp": DEFAULT_TYPE_DATETIME,
  "dtstart": DEFAULT_TYPE_DATETIME_DATE,
  "due": DEFAULT_TYPE_DATETIME_DATE,
  "duration": { defaultType: "duration" },
  "exdate": {
    defaultType: "date-time",
    allowedTypes: ["date-time", "date"],
    multiValue: ","
  },
  "exrule": DEFAULT_TYPE_RECUR,
  "freebusy": { defaultType: "period", multiValue: "," },
  "geo": { defaultType: "float", structuredValue: ";" },
  "last-modified": DEFAULT_TYPE_DATETIME,
  "location": DEFAULT_TYPE_TEXT,
  "method": DEFAULT_TYPE_TEXT,
  "organizer": { defaultType: "cal-address" },
  "percent-complete": DEFAULT_TYPE_INTEGER,
  "priority": DEFAULT_TYPE_INTEGER,
  "prodid": DEFAULT_TYPE_TEXT,
  "related-to": DEFAULT_TYPE_TEXT,
  "repeat": DEFAULT_TYPE_INTEGER,
  "rdate": {
    defaultType: "date-time",
    allowedTypes: ["date-time", "date", "period"],
    multiValue: ",",
    detectType: function(string) {
      if (string.indexOf("/") !== -1) {
        return "period";
      }
      return string.indexOf("T") === -1 ? "date" : "date-time";
    }
  },
  "recurrence-id": DEFAULT_TYPE_DATETIME_DATE,
  "resources": DEFAULT_TYPE_TEXT_MULTI,
  "request-status": DEFAULT_TYPE_TEXT_STRUCTURED,
  "rrule": DEFAULT_TYPE_RECUR,
  "sequence": DEFAULT_TYPE_INTEGER,
  "status": DEFAULT_TYPE_TEXT,
  "summary": DEFAULT_TYPE_TEXT,
  "transp": DEFAULT_TYPE_TEXT,
  "trigger": { defaultType: "duration", allowedTypes: ["duration", "date-time"] },
  "tzoffsetfrom": DEFAULT_TYPE_UTCOFFSET,
  "tzoffsetto": DEFAULT_TYPE_UTCOFFSET,
  "tzurl": DEFAULT_TYPE_URI,
  "tzid": DEFAULT_TYPE_TEXT,
  "tzname": DEFAULT_TYPE_TEXT
});
const vcardValues = extend(commonValues, {
  text: createTextType(FROM_VCARD_NEWLINE, TO_VCARD_NEWLINE),
  uri: createTextType(FROM_VCARD_NEWLINE, TO_VCARD_NEWLINE),
  date: {
    decorate: function(aValue) {
      return VCardTime.fromDateAndOrTimeString(aValue, "date");
    },
    undecorate: function(aValue) {
      return aValue.toString();
    },
    fromICAL: function(aValue) {
      if (aValue.length == 8) {
        return icalValues.date.fromICAL(aValue);
      } else if (aValue[0] == "-" && aValue.length == 6) {
        return aValue.slice(0, 4) + "-" + aValue.slice(4);
      } else {
        return aValue;
      }
    },
    toICAL: function(aValue) {
      if (aValue.length == 10) {
        return icalValues.date.toICAL(aValue);
      } else if (aValue[0] == "-" && aValue.length == 7) {
        return aValue.slice(0, 4) + aValue.slice(5);
      } else {
        return aValue;
      }
    }
  },
  time: {
    decorate: function(aValue) {
      return VCardTime.fromDateAndOrTimeString("T" + aValue, "time");
    },
    undecorate: function(aValue) {
      return aValue.toString();
    },
    fromICAL: function(aValue) {
      let splitzone = vcardValues.time._splitZone(aValue, true);
      let zone = splitzone[0], value = splitzone[1];
      if (value.length == 6) {
        value = value.slice(0, 2) + ":" + value.slice(2, 4) + ":" + value.slice(4, 6);
      } else if (value.length == 4 && value[0] != "-") {
        value = value.slice(0, 2) + ":" + value.slice(2, 4);
      } else if (value.length == 5) {
        value = value.slice(0, 3) + ":" + value.slice(3, 5);
      }
      if (zone.length == 5 && (zone[0] == "-" || zone[0] == "+")) {
        zone = zone.slice(0, 3) + ":" + zone.slice(3);
      }
      return value + zone;
    },
    toICAL: function(aValue) {
      let splitzone = vcardValues.time._splitZone(aValue);
      let zone = splitzone[0], value = splitzone[1];
      if (value.length == 8) {
        value = value.slice(0, 2) + value.slice(3, 5) + value.slice(6, 8);
      } else if (value.length == 5 && value[0] != "-") {
        value = value.slice(0, 2) + value.slice(3, 5);
      } else if (value.length == 6) {
        value = value.slice(0, 3) + value.slice(4, 6);
      }
      if (zone.length == 6 && (zone[0] == "-" || zone[0] == "+")) {
        zone = zone.slice(0, 3) + zone.slice(4);
      }
      return value + zone;
    },
    _splitZone: function(aValue, isFromIcal) {
      let lastChar = aValue.length - 1;
      let signChar = aValue.length - (isFromIcal ? 5 : 6);
      let sign = aValue[signChar];
      let zone, value;
      if (aValue[lastChar] == "Z") {
        zone = aValue[lastChar];
        value = aValue.slice(0, Math.max(0, lastChar));
      } else if (aValue.length > 6 && (sign == "-" || sign == "+")) {
        zone = aValue.slice(signChar);
        value = aValue.slice(0, Math.max(0, signChar));
      } else {
        zone = "";
        value = aValue;
      }
      return [zone, value];
    }
  },
  "date-time": {
    decorate: function(aValue) {
      return VCardTime.fromDateAndOrTimeString(aValue, "date-time");
    },
    undecorate: function(aValue) {
      return aValue.toString();
    },
    fromICAL: function(aValue) {
      return vcardValues["date-and-or-time"].fromICAL(aValue);
    },
    toICAL: function(aValue) {
      return vcardValues["date-and-or-time"].toICAL(aValue);
    }
  },
  "date-and-or-time": {
    decorate: function(aValue) {
      return VCardTime.fromDateAndOrTimeString(aValue, "date-and-or-time");
    },
    undecorate: function(aValue) {
      return aValue.toString();
    },
    fromICAL: function(aValue) {
      let parts = aValue.split("T");
      return (parts[0] ? vcardValues.date.fromICAL(parts[0]) : "") + (parts[1] ? "T" + vcardValues.time.fromICAL(parts[1]) : "");
    },
    toICAL: function(aValue) {
      let parts = aValue.split("T");
      return vcardValues.date.toICAL(parts[0]) + (parts[1] ? "T" + vcardValues.time.toICAL(parts[1]) : "");
    }
  },
  timestamp: icalValues["date-time"],
  "language-tag": {
    matches: /^[a-zA-Z0-9-]+$/
    // Could go with a more strict regex here
  },
  "phone-number": {
    fromICAL: function(aValue) {
      return Array.from(aValue).filter(function(c) {
        return c === "\\" ? void 0 : c;
      }).join("");
    },
    toICAL: function(aValue) {
      return Array.from(aValue).map(function(c) {
        return c === "," || c === ";" ? "\\" + c : c;
      }).join("");
    }
  }
});
let vcardParams = {
  "type": {
    valueType: "text",
    multiValue: ","
  },
  "value": {
    // since the value here is a 'type' lowercase is used.
    values: [
      "text",
      "uri",
      "date",
      "time",
      "date-time",
      "date-and-or-time",
      "timestamp",
      "boolean",
      "integer",
      "float",
      "utc-offset",
      "language-tag"
    ],
    allowXName: true,
    allowIanaToken: true
  }
};
let vcardProperties = extend(commonProperties, {
  "adr": { defaultType: "text", structuredValue: ";", multiValue: "," },
  "anniversary": DEFAULT_TYPE_DATE_ANDOR_TIME,
  "bday": DEFAULT_TYPE_DATE_ANDOR_TIME,
  "caladruri": DEFAULT_TYPE_URI,
  "caluri": DEFAULT_TYPE_URI,
  "clientpidmap": DEFAULT_TYPE_TEXT_STRUCTURED,
  "email": DEFAULT_TYPE_TEXT,
  "fburl": DEFAULT_TYPE_URI,
  "fn": DEFAULT_TYPE_TEXT,
  "gender": DEFAULT_TYPE_TEXT_STRUCTURED,
  "geo": DEFAULT_TYPE_URI,
  "impp": DEFAULT_TYPE_URI,
  "key": DEFAULT_TYPE_URI,
  "kind": DEFAULT_TYPE_TEXT,
  "lang": { defaultType: "language-tag" },
  "logo": DEFAULT_TYPE_URI,
  "member": DEFAULT_TYPE_URI,
  "n": { defaultType: "text", structuredValue: ";", multiValue: "," },
  "nickname": DEFAULT_TYPE_TEXT_MULTI,
  "note": DEFAULT_TYPE_TEXT,
  "org": { defaultType: "text", structuredValue: ";" },
  "photo": DEFAULT_TYPE_URI,
  "related": DEFAULT_TYPE_URI,
  "rev": { defaultType: "timestamp" },
  "role": DEFAULT_TYPE_TEXT,
  "sound": DEFAULT_TYPE_URI,
  "source": DEFAULT_TYPE_URI,
  "tel": { defaultType: "uri", allowedTypes: ["uri", "text"] },
  "title": DEFAULT_TYPE_TEXT,
  "tz": { defaultType: "text", allowedTypes: ["text", "utc-offset", "uri"] },
  "xml": DEFAULT_TYPE_TEXT
});
let vcard3Values = extend(commonValues, {
  binary: icalValues.binary,
  date: vcardValues.date,
  "date-time": vcardValues["date-time"],
  "phone-number": vcardValues["phone-number"],
  uri: icalValues.uri,
  text: vcardValues.text,
  time: icalValues.time,
  vcard: icalValues.text,
  "utc-offset": {
    toICAL: function(aValue) {
      return aValue.slice(0, 7);
    },
    fromICAL: function(aValue) {
      return aValue.slice(0, 7);
    },
    decorate: function(aValue) {
      return UtcOffset.fromString(aValue);
    },
    undecorate: function(aValue) {
      return aValue.toString();
    }
  }
});
let vcard3Params = {
  "type": {
    valueType: "text",
    multiValue: ","
  },
  "value": {
    // since the value here is a 'type' lowercase is used.
    values: [
      "text",
      "uri",
      "date",
      "date-time",
      "phone-number",
      "time",
      "boolean",
      "integer",
      "float",
      "utc-offset",
      "vcard",
      "binary"
    ],
    allowXName: true,
    allowIanaToken: true
  }
};
let vcard3Properties = extend(commonProperties, {
  fn: DEFAULT_TYPE_TEXT,
  n: { defaultType: "text", structuredValue: ";", multiValue: "," },
  nickname: DEFAULT_TYPE_TEXT_MULTI,
  photo: { defaultType: "binary", allowedTypes: ["binary", "uri"] },
  bday: {
    defaultType: "date-time",
    allowedTypes: ["date-time", "date"],
    detectType: function(string) {
      return string.indexOf("T") === -1 ? "date" : "date-time";
    }
  },
  adr: { defaultType: "text", structuredValue: ";", multiValue: "," },
  label: DEFAULT_TYPE_TEXT,
  tel: { defaultType: "phone-number" },
  email: DEFAULT_TYPE_TEXT,
  mailer: DEFAULT_TYPE_TEXT,
  tz: { defaultType: "utc-offset", allowedTypes: ["utc-offset", "text"] },
  geo: { defaultType: "float", structuredValue: ";" },
  title: DEFAULT_TYPE_TEXT,
  role: DEFAULT_TYPE_TEXT,
  logo: { defaultType: "binary", allowedTypes: ["binary", "uri"] },
  agent: { defaultType: "vcard", allowedTypes: ["vcard", "text", "uri"] },
  org: DEFAULT_TYPE_TEXT_STRUCTURED,
  note: DEFAULT_TYPE_TEXT_MULTI,
  prodid: DEFAULT_TYPE_TEXT,
  rev: {
    defaultType: "date-time",
    allowedTypes: ["date-time", "date"],
    detectType: function(string) {
      return string.indexOf("T") === -1 ? "date" : "date-time";
    }
  },
  "sort-string": DEFAULT_TYPE_TEXT,
  sound: { defaultType: "binary", allowedTypes: ["binary", "uri"] },
  class: DEFAULT_TYPE_TEXT,
  key: { defaultType: "binary", allowedTypes: ["binary", "text"] }
});
let icalSet = {
  name: "ical",
  value: icalValues,
  param: icalParams,
  property: icalProperties,
  propertyGroups: false
};
let vcardSet = {
  name: "vcard4",
  value: vcardValues,
  param: vcardParams,
  property: vcardProperties,
  propertyGroups: true
};
let vcard3Set = {
  name: "vcard3",
  value: vcard3Values,
  param: vcard3Params,
  property: vcard3Properties,
  propertyGroups: true
};
const design = {
  /**
   * Can be set to false to make the parser more lenient.
   */
  strict: true,
  /**
   * The default set for new properties and components if none is specified.
   * @type {designSet}
   */
  defaultSet: icalSet,
  /**
   * The default type for unknown properties
   * @type {String}
   */
  defaultType: "unknown",
  /**
   * Holds the design set for known top-level components
   *
   * @type {Object}
   * @property {designSet} vcard       vCard VCARD
   * @property {designSet} vevent      iCalendar VEVENT
   * @property {designSet} vtodo       iCalendar VTODO
   * @property {designSet} vjournal    iCalendar VJOURNAL
   * @property {designSet} valarm      iCalendar VALARM
   * @property {designSet} vtimezone   iCalendar VTIMEZONE
   * @property {designSet} daylight    iCalendar DAYLIGHT
   * @property {designSet} standard    iCalendar STANDARD
   *
   * @example
   * let propertyName = 'fn';
   * let componentDesign = ICAL.design.components.vcard;
   * let propertyDetails = componentDesign.property[propertyName];
   * if (propertyDetails.defaultType == 'text') {
   *   // Yep, sure is...
   * }
   */
  components: {
    vcard: vcardSet,
    vcard3: vcard3Set,
    vevent: icalSet,
    vtodo: icalSet,
    vjournal: icalSet,
    valarm: icalSet,
    vtimezone: icalSet,
    daylight: icalSet,
    standard: icalSet
  },
  /**
   * The design set for iCalendar (rfc5545/rfc7265) components.
   * @type {designSet}
   */
  icalendar: icalSet,
  /**
   * The design set for vCard (rfc6350/rfc7095) components.
   * @type {designSet}
   */
  vcard: vcardSet,
  /**
   * The design set for vCard (rfc2425/rfc2426/rfc7095) components.
   * @type {designSet}
   */
  vcard3: vcard3Set,
  /**
   * Gets the design set for the given component name.
   *
   * @param {String} componentName        The name of the component
   * @return {designSet}      The design set for the component
   */
  getDesignSet: function(componentName) {
    let isInDesign = componentName && componentName in design.components;
    return isInDesign ? design.components[componentName] : design.defaultSet;
  }
};
const LINE_ENDING = "\r\n";
const DEFAULT_VALUE_TYPE = "unknown";
const RFC6868_REPLACE_MAP = { '"': "^'", "\n": "^n", "^": "^^" };
function stringify(jCal) {
  if (typeof jCal[0] == "string") {
    jCal = [jCal];
  }
  let i = 0;
  let len = jCal.length;
  let result = "";
  for (; i < len; i++) {
    result += stringify.component(jCal[i]) + LINE_ENDING;
  }
  return result;
}
stringify.component = function(component, designSet) {
  let name = component[0].toUpperCase();
  let result = "BEGIN:" + name + LINE_ENDING;
  let props = component[1];
  let propIdx = 0;
  let propLen = props.length;
  let designSetName = component[0];
  if (designSetName === "vcard" && component[1].length > 0 && !(component[1][0][0] === "version" && component[1][0][3] === "4.0")) {
    designSetName = "vcard3";
  }
  designSet = designSet || design.getDesignSet(designSetName);
  for (; propIdx < propLen; propIdx++) {
    result += stringify.property(props[propIdx], designSet) + LINE_ENDING;
  }
  let comps = component[2] || [];
  let compIdx = 0;
  let compLen = comps.length;
  for (; compIdx < compLen; compIdx++) {
    result += stringify.component(comps[compIdx], designSet) + LINE_ENDING;
  }
  result += "END:" + name;
  return result;
};
stringify.property = function(property, designSet, noFold) {
  let name = property[0].toUpperCase();
  let jsName = property[0];
  let params = property[1];
  if (!designSet) {
    designSet = design.defaultSet;
  }
  let groupName = params.group;
  let line;
  if (designSet.propertyGroups && groupName) {
    line = groupName.toUpperCase() + "." + name;
  } else {
    line = name;
  }
  for (let [paramName, value] of Object.entries(params)) {
    if (designSet.propertyGroups && paramName == "group") {
      continue;
    }
    let paramDesign = designSet.param[paramName];
    let multiValue2 = paramDesign && paramDesign.multiValue;
    if (multiValue2 && Array.isArray(value)) {
      value = value.map(function(val) {
        val = stringify._rfc6868Unescape(val);
        val = stringify.paramPropertyValue(val, paramDesign.multiValueSeparateDQuote);
        return val;
      });
      value = stringify.multiValue(value, multiValue2, "unknown", null, designSet);
    } else {
      value = stringify._rfc6868Unescape(value);
      value = stringify.paramPropertyValue(value);
    }
    line += ";" + paramName.toUpperCase() + "=" + value;
  }
  if (property.length === 3) {
    return line + ":";
  }
  let valueType = property[2];
  let propDetails;
  let multiValue = false;
  let structuredValue = false;
  let isDefault = false;
  if (jsName in designSet.property) {
    propDetails = designSet.property[jsName];
    if ("multiValue" in propDetails) {
      multiValue = propDetails.multiValue;
    }
    if ("structuredValue" in propDetails && Array.isArray(property[3])) {
      structuredValue = propDetails.structuredValue;
    }
    if ("defaultType" in propDetails) {
      if (valueType === propDetails.defaultType) {
        isDefault = true;
      }
    } else {
      if (valueType === DEFAULT_VALUE_TYPE) {
        isDefault = true;
      }
    }
  } else {
    if (valueType === DEFAULT_VALUE_TYPE) {
      isDefault = true;
    }
  }
  if (!isDefault) {
    line += ";VALUE=" + valueType.toUpperCase();
  }
  line += ":";
  if (multiValue && structuredValue) {
    line += stringify.multiValue(
      property[3],
      structuredValue,
      valueType,
      multiValue,
      designSet,
      structuredValue
    );
  } else if (multiValue) {
    line += stringify.multiValue(
      property.slice(3),
      multiValue,
      valueType,
      null,
      designSet,
      false
    );
  } else if (structuredValue) {
    line += stringify.multiValue(
      property[3],
      structuredValue,
      valueType,
      null,
      designSet,
      structuredValue
    );
  } else {
    line += stringify.value(property[3], valueType, designSet, false);
  }
  return noFold ? line : foldline(line);
};
stringify.paramPropertyValue = function(value, force) {
  if (!force && value.indexOf(",") === -1 && value.indexOf(":") === -1 && value.indexOf(";") === -1) {
    return value;
  }
  return '"' + value + '"';
};
stringify.multiValue = function(values, delim, type, innerMulti, designSet, structuredValue) {
  let result = "";
  let len = values.length;
  let i = 0;
  for (; i < len; i++) {
    if (innerMulti && Array.isArray(values[i])) {
      result += stringify.multiValue(values[i], innerMulti, type, null, designSet, structuredValue);
    } else {
      result += stringify.value(values[i], type, designSet, structuredValue);
    }
    if (i !== len - 1) {
      result += delim;
    }
  }
  return result;
};
stringify.value = function(value, type, designSet, structuredValue) {
  if (type in designSet.value && "toICAL" in designSet.value[type]) {
    return designSet.value[type].toICAL(value, structuredValue);
  }
  return value;
};
stringify._rfc6868Unescape = function(val) {
  return val.replace(/[\n^"]/g, function(x) {
    return RFC6868_REPLACE_MAP[x];
  });
};
const NAME_INDEX$1 = 0;
const PROP_INDEX = 1;
const TYPE_INDEX = 2;
const VALUE_INDEX = 3;
class Property {
  /**
   * Create an {@link ICAL.Property} by parsing the passed iCalendar string.
   *
   * @param {String} str            The iCalendar string to parse
   * @param {designSet=} designSet  The design data to use for this property
   * @return {Property}             The created iCalendar property
   */
  static fromString(str, designSet) {
    return new Property(parse.property(str, designSet));
  }
  /**
   * Creates a new ICAL.Property instance.
   *
   * It is important to note that mutations done in the wrapper directly mutate the jCal object used
   * to initialize.
   *
   * Can also be used to create new properties by passing the name of the property (as a String).
   *
   * @param {Array|String} jCal         Raw jCal representation OR the new name of the property
   * @param {Component=} parent         Parent component
   */
  constructor(jCal, parent) {
    this._parent = parent || null;
    if (typeof jCal === "string") {
      this.jCal = [jCal, {}, design.defaultType];
      this.jCal[TYPE_INDEX] = this.getDefaultType();
    } else {
      this.jCal = jCal;
    }
    this._updateType();
  }
  /**
   * The value type for this property
   * @type {String}
   */
  get type() {
    return this.jCal[TYPE_INDEX];
  }
  /**
   * The name of this property, in lowercase.
   * @type {String}
   */
  get name() {
    return this.jCal[NAME_INDEX$1];
  }
  /**
   * The parent component for this property.
   * @type {Component}
   */
  get parent() {
    return this._parent;
  }
  set parent(p) {
    let designSetChanged = !this._parent || p && p._designSet != this._parent._designSet;
    this._parent = p;
    if (this.type == design.defaultType && designSetChanged) {
      this.jCal[TYPE_INDEX] = this.getDefaultType();
      this._updateType();
    }
  }
  /**
   * The design set for this property, e.g. icalendar vs vcard
   *
   * @type {designSet}
   * @private
   */
  get _designSet() {
    return this.parent ? this.parent._designSet : design.defaultSet;
  }
  /**
   * Updates the type metadata from the current jCal type and design set.
   *
   * @private
   */
  _updateType() {
    let designSet = this._designSet;
    if (this.type in designSet.value) {
      if ("decorate" in designSet.value[this.type]) {
        this.isDecorated = true;
      } else {
        this.isDecorated = false;
      }
      if (this.name in designSet.property) {
        this.isMultiValue = "multiValue" in designSet.property[this.name];
        this.isStructuredValue = "structuredValue" in designSet.property[this.name];
      }
    }
  }
  /**
   * Hydrate a single value. The act of hydrating means turning the raw jCal
   * value into a potentially wrapped object, for example {@link ICAL.Time}.
   *
   * @private
   * @param {Number} index        The index of the value to hydrate
   * @return {?Object}             The decorated value.
   */
  _hydrateValue(index) {
    if (this._values && this._values[index]) {
      return this._values[index];
    }
    if (this.jCal.length <= VALUE_INDEX + index) {
      return null;
    }
    if (this.isDecorated) {
      if (!this._values) {
        this._values = [];
      }
      return this._values[index] = this._decorate(
        this.jCal[VALUE_INDEX + index]
      );
    } else {
      return this.jCal[VALUE_INDEX + index];
    }
  }
  /**
   * Decorate a single value, returning its wrapped object. This is used by
   * the hydrate function to actually wrap the value.
   *
   * @private
   * @param {?} value         The value to decorate
   * @return {Object}         The decorated value
   */
  _decorate(value) {
    return this._designSet.value[this.type].decorate(value, this);
  }
  /**
   * Undecorate a single value, returning its raw jCal data.
   *
   * @private
   * @param {Object} value         The value to undecorate
   * @return {?}                   The undecorated value
   */
  _undecorate(value) {
    return this._designSet.value[this.type].undecorate(value, this);
  }
  /**
   * Sets the value at the given index while also hydrating it. The passed
   * value can either be a decorated or undecorated value.
   *
   * @private
   * @param {?} value             The value to set
   * @param {Number} index        The index to set it at
   */
  _setDecoratedValue(value, index) {
    if (!this._values) {
      this._values = [];
    }
    if (typeof value === "object" && "icaltype" in value) {
      this.jCal[VALUE_INDEX + index] = this._undecorate(value);
      this._values[index] = value;
    } else {
      this.jCal[VALUE_INDEX + index] = value;
      this._values[index] = this._decorate(value);
    }
  }
  /**
   * Gets a parameter on the property.
   *
   * @param {String}        name   Parameter name (lowercase)
   * @return {Array|String}        Parameter value
   */
  getParameter(name) {
    if (name in this.jCal[PROP_INDEX]) {
      return this.jCal[PROP_INDEX][name];
    } else {
      return void 0;
    }
  }
  /**
   * Gets first parameter on the property.
   *
   * @param {String}        name   Parameter name (lowercase)
   * @return {String}        Parameter value
   */
  getFirstParameter(name) {
    let parameters = this.getParameter(name);
    if (Array.isArray(parameters)) {
      return parameters[0];
    }
    return parameters;
  }
  /**
   * Sets a parameter on the property.
   *
   * @param {String}       name     The parameter name
   * @param {Array|String} value    The parameter value
   */
  setParameter(name, value) {
    let lcname = name.toLowerCase();
    if (typeof value === "string" && lcname in this._designSet.param && "multiValue" in this._designSet.param[lcname]) {
      value = [value];
    }
    this.jCal[PROP_INDEX][name] = value;
  }
  /**
   * Removes a parameter
   *
   * @param {String} name     The parameter name
   */
  removeParameter(name) {
    delete this.jCal[PROP_INDEX][name];
  }
  /**
   * Get the default type based on this property's name.
   *
   * @return {String}     The default type for this property
   */
  getDefaultType() {
    let name = this.jCal[NAME_INDEX$1];
    let designSet = this._designSet;
    if (name in designSet.property) {
      let details = designSet.property[name];
      if ("defaultType" in details) {
        return details.defaultType;
      }
    }
    return design.defaultType;
  }
  /**
   * Sets type of property and clears out any existing values of the current
   * type.
   *
   * @param {String} type     New iCAL type (see design.*.values)
   */
  resetType(type) {
    this.removeAllValues();
    this.jCal[TYPE_INDEX] = type;
    this._updateType();
  }
  /**
   * Finds the first property value.
   *
   * @return {Binary | Duration | Period |
   * Recur | Time | UtcOffset | Geo | string | null}         First property value
   */
  getFirstValue() {
    return this._hydrateValue(0);
  }
  /**
   * Gets all values on the property.
   *
   * NOTE: this creates an array during each call.
   *
   * @return {Array}          List of values
   */
  getValues() {
    let len = this.jCal.length - VALUE_INDEX;
    if (len < 1) {
      return [];
    }
    let i = 0;
    let result = [];
    for (; i < len; i++) {
      result[i] = this._hydrateValue(i);
    }
    return result;
  }
  /**
   * Removes all values from this property
   */
  removeAllValues() {
    if (this._values) {
      this._values.length = 0;
    }
    this.jCal.length = 3;
  }
  /**
   * Sets the values of the property.  Will overwrite the existing values.
   * This can only be used for multi-value properties.
   *
   * @param {Array} values    An array of values
   */
  setValues(values) {
    if (!this.isMultiValue) {
      throw new Error(
        this.name + ": does not not support mulitValue.\noverride isMultiValue"
      );
    }
    let len = values.length;
    let i = 0;
    this.removeAllValues();
    if (len > 0 && typeof values[0] === "object" && "icaltype" in values[0]) {
      this.resetType(values[0].icaltype);
    }
    if (this.isDecorated) {
      for (; i < len; i++) {
        this._setDecoratedValue(values[i], i);
      }
    } else {
      for (; i < len; i++) {
        this.jCal[VALUE_INDEX + i] = values[i];
      }
    }
  }
  /**
   * Sets the current value of the property. If this is a multi-value
   * property, all other values will be removed.
   *
   * @param {String|Object} value     New property value.
   */
  setValue(value) {
    this.removeAllValues();
    if (typeof value === "object" && "icaltype" in value) {
      this.resetType(value.icaltype);
    }
    if (this.isDecorated) {
      this._setDecoratedValue(value, 0);
    } else {
      this.jCal[VALUE_INDEX] = value;
    }
  }
  /**
   * Returns the Object representation of this component. The returned object
   * is a live jCal object and should be cloned if modified.
   * @return {Object}
   */
  toJSON() {
    return this.jCal;
  }
  /**
   * The string representation of this component.
   * @return {String}
   */
  toICALString() {
    return stringify.property(
      this.jCal,
      this._designSet,
      true
    );
  }
}
const NAME_INDEX = 0;
const PROPERTY_INDEX = 1;
const COMPONENT_INDEX = 2;
const PROPERTY_NAME_INDEX = 0;
const PROPERTY_VALUE_INDEX = 3;
class Component {
  /**
   * Create an {@link ICAL.Component} by parsing the passed iCalendar string.
   *
   * @param {String} str        The iCalendar string to parse
   */
  static fromString(str) {
    return new Component(parse.component(str));
  }
  /**
   * Creates a new Component instance.
   *
   * @param {Array|String} jCal         Raw jCal component data OR name of new
   *                                      component
   * @param {Component=} parent     Parent component to associate
   */
  constructor(jCal, parent) {
    if (typeof jCal === "string") {
      jCal = [jCal, [], []];
    }
    this.jCal = jCal;
    this.parent = parent || null;
    if (!this.parent && this.name === "vcalendar") {
      this._timezoneCache = /* @__PURE__ */ new Map();
    }
  }
  /**
   * Hydrated properties are inserted into the _properties array at the same
   * position as in the jCal array, so it is possible that the array contains
   * undefined values for unhydrdated properties. To avoid iterating the
   * array when checking if all properties have been hydrated, we save the
   * count here.
   *
   * @type {Number}
   * @private
   */
  _hydratedPropertyCount = 0;
  /**
   * The same count as for _hydratedPropertyCount, but for subcomponents
   *
   * @type {Number}
   * @private
   */
  _hydratedComponentCount = 0;
  /**
   * A cache of hydrated time zone objects which may be used by consumers, keyed
   * by time zone ID.
   *
   * @type {Map}
   * @private
   */
  _timezoneCache = null;
  /**
   * @private
   */
  _components = null;
  /**
   * @private
   */
  _properties = null;
  /**
   * The name of this component
   *
   * @type {String}
   */
  get name() {
    return this.jCal[NAME_INDEX];
  }
  /**
   * The design set for this component, e.g. icalendar vs vcard
   *
   * @type {designSet}
   * @private
   */
  get _designSet() {
    let parentDesign = this.parent && this.parent._designSet;
    if (!parentDesign && this.name == "vcard") {
      let versionProp = this.jCal[PROPERTY_INDEX]?.[0];
      if (versionProp && versionProp[PROPERTY_NAME_INDEX] == "version" && versionProp[PROPERTY_VALUE_INDEX] == "3.0") {
        return design.getDesignSet("vcard3");
      }
    }
    return parentDesign || design.getDesignSet(this.name);
  }
  /**
   * @private
   */
  _hydrateComponent(index) {
    if (!this._components) {
      this._components = [];
      this._hydratedComponentCount = 0;
    }
    if (this._components[index]) {
      return this._components[index];
    }
    let comp = new Component(
      this.jCal[COMPONENT_INDEX][index],
      this
    );
    this._hydratedComponentCount++;
    return this._components[index] = comp;
  }
  /**
   * @private
   */
  _hydrateProperty(index) {
    if (!this._properties) {
      this._properties = [];
      this._hydratedPropertyCount = 0;
    }
    if (this._properties[index]) {
      return this._properties[index];
    }
    let prop = new Property(
      this.jCal[PROPERTY_INDEX][index],
      this
    );
    this._hydratedPropertyCount++;
    return this._properties[index] = prop;
  }
  /**
   * Finds first sub component, optionally filtered by name.
   *
   * @param {String=} name        Optional name to filter by
   * @return {?Component}     The found subcomponent
   */
  getFirstSubcomponent(name) {
    if (name) {
      let i = 0;
      let comps = this.jCal[COMPONENT_INDEX];
      let len = comps.length;
      for (; i < len; i++) {
        if (comps[i][NAME_INDEX] === name) {
          let result = this._hydrateComponent(i);
          return result;
        }
      }
    } else {
      if (this.jCal[COMPONENT_INDEX].length) {
        return this._hydrateComponent(0);
      }
    }
    return null;
  }
  /**
   * Finds all sub components, optionally filtering by name.
   *
   * @param {String=} name            Optional name to filter by
   * @return {Component[]}       The found sub components
   */
  getAllSubcomponents(name) {
    let jCalLen = this.jCal[COMPONENT_INDEX].length;
    let i = 0;
    if (name) {
      let comps = this.jCal[COMPONENT_INDEX];
      let result = [];
      for (; i < jCalLen; i++) {
        if (name === comps[i][NAME_INDEX]) {
          result.push(
            this._hydrateComponent(i)
          );
        }
      }
      return result;
    } else {
      if (!this._components || this._hydratedComponentCount !== jCalLen) {
        for (; i < jCalLen; i++) {
          this._hydrateComponent(i);
        }
      }
      return this._components || [];
    }
  }
  /**
   * Returns true when a named property exists.
   *
   * @param {String} name     The property name
   * @return {Boolean}        True, when property is found
   */
  hasProperty(name) {
    let props = this.jCal[PROPERTY_INDEX];
    let len = props.length;
    let i = 0;
    for (; i < len; i++) {
      if (props[i][NAME_INDEX] === name) {
        return true;
      }
    }
    return false;
  }
  /**
   * Finds the first property, optionally with the given name.
   *
   * @param {String=} name        Lowercase property name
   * @return {?Property}     The found property
   */
  getFirstProperty(name) {
    if (name) {
      let i = 0;
      let props = this.jCal[PROPERTY_INDEX];
      let len = props.length;
      for (; i < len; i++) {
        if (props[i][NAME_INDEX] === name) {
          let result = this._hydrateProperty(i);
          return result;
        }
      }
    } else {
      if (this.jCal[PROPERTY_INDEX].length) {
        return this._hydrateProperty(0);
      }
    }
    return null;
  }
  /**
   * Returns first property's value, if available.
   *
   * @param {String=} name                    Lowercase property name
   * @return {Binary | Duration | Period |
   * Recur | Time | UtcOffset | Geo | string | null}         The found property value.
   */
  getFirstPropertyValue(name) {
    let prop = this.getFirstProperty(name);
    if (prop) {
      return prop.getFirstValue();
    }
    return null;
  }
  /**
   * Get all properties in the component, optionally filtered by name.
   *
   * @param {String=} name        Lowercase property name
   * @return {Property[]}    List of properties
   */
  getAllProperties(name) {
    let jCalLen = this.jCal[PROPERTY_INDEX].length;
    let i = 0;
    if (name) {
      let props = this.jCal[PROPERTY_INDEX];
      let result = [];
      for (; i < jCalLen; i++) {
        if (name === props[i][NAME_INDEX]) {
          result.push(
            this._hydrateProperty(i)
          );
        }
      }
      return result;
    } else {
      if (!this._properties || this._hydratedPropertyCount !== jCalLen) {
        for (; i < jCalLen; i++) {
          this._hydrateProperty(i);
        }
      }
      return this._properties || [];
    }
  }
  /**
   * @private
   */
  _removeObjectByIndex(jCalIndex, cache, index) {
    cache = cache || [];
    if (cache[index]) {
      let obj = cache[index];
      if ("parent" in obj) {
        obj.parent = null;
      }
    }
    cache.splice(index, 1);
    this.jCal[jCalIndex].splice(index, 1);
  }
  /**
   * @private
   */
  _removeObject(jCalIndex, cache, nameOrObject) {
    let i = 0;
    let objects = this.jCal[jCalIndex];
    let len = objects.length;
    let cached = this[cache];
    if (typeof nameOrObject === "string") {
      for (; i < len; i++) {
        if (objects[i][NAME_INDEX] === nameOrObject) {
          this._removeObjectByIndex(jCalIndex, cached, i);
          return true;
        }
      }
    } else if (cached) {
      for (; i < len; i++) {
        if (cached[i] && cached[i] === nameOrObject) {
          this._removeObjectByIndex(jCalIndex, cached, i);
          return true;
        }
      }
    }
    return false;
  }
  /**
   * @private
   */
  _removeAllObjects(jCalIndex, cache, name) {
    let cached = this[cache];
    let objects = this.jCal[jCalIndex];
    let i = objects.length - 1;
    for (; i >= 0; i--) {
      if (!name || objects[i][NAME_INDEX] === name) {
        this._removeObjectByIndex(jCalIndex, cached, i);
      }
    }
  }
  /**
   * Adds a single sub component.
   *
   * @param {Component} component        The component to add
   * @return {Component}                 The passed in component
   */
  addSubcomponent(component) {
    if (!this._components) {
      this._components = [];
      this._hydratedComponentCount = 0;
    }
    if (component.parent) {
      component.parent.removeSubcomponent(component);
    }
    let idx = this.jCal[COMPONENT_INDEX].push(component.jCal);
    this._components[idx - 1] = component;
    this._hydratedComponentCount++;
    component.parent = this;
    return component;
  }
  /**
   * Removes a single component by name or the instance of a specific
   * component.
   *
   * @param {Component|String} nameOrComp    Name of component, or component
   * @return {Boolean}                            True when comp is removed
   */
  removeSubcomponent(nameOrComp) {
    let removed = this._removeObject(COMPONENT_INDEX, "_components", nameOrComp);
    if (removed) {
      this._hydratedComponentCount--;
    }
    return removed;
  }
  /**
   * Removes all components or (if given) all components by a particular
   * name.
   *
   * @param {String=} name            Lowercase component name
   */
  removeAllSubcomponents(name) {
    let removed = this._removeAllObjects(COMPONENT_INDEX, "_components", name);
    this._hydratedComponentCount = 0;
    return removed;
  }
  /**
   * Adds an {@link ICAL.Property} to the component.
   *
   * @param {Property} property      The property to add
   * @return {Property}              The passed in property
   */
  addProperty(property) {
    if (!(property instanceof Property)) {
      throw new TypeError("must be instance of ICAL.Property");
    }
    if (!this._properties) {
      this._properties = [];
      this._hydratedPropertyCount = 0;
    }
    if (property.parent) {
      property.parent.removeProperty(property);
    }
    let idx = this.jCal[PROPERTY_INDEX].push(property.jCal);
    this._properties[idx - 1] = property;
    this._hydratedPropertyCount++;
    property.parent = this;
    return property;
  }
  /**
   * Helper method to add a property with a value to the component.
   *
   * @param {String}               name         Property name to add
   * @param {String|Number|Object} value        Property value
   * @return {Property}                    The created property
   */
  addPropertyWithValue(name, value) {
    let prop = new Property(name);
    prop.setValue(value);
    this.addProperty(prop);
    return prop;
  }
  /**
   * Helper method that will update or create a property of the given name
   * and sets its value. If multiple properties with the given name exist,
   * only the first is updated.
   *
   * @param {String}               name         Property name to update
   * @param {String|Number|Object} value        Property value
   * @return {Property}                    The created property
   */
  updatePropertyWithValue(name, value) {
    let prop = this.getFirstProperty(name);
    if (prop) {
      prop.setValue(value);
    } else {
      prop = this.addPropertyWithValue(name, value);
    }
    return prop;
  }
  /**
   * Removes a single property by name or the instance of the specific
   * property.
   *
   * @param {String|Property} nameOrProp     Property name or instance to remove
   * @return {Boolean}                            True, when deleted
   */
  removeProperty(nameOrProp) {
    let removed = this._removeObject(PROPERTY_INDEX, "_properties", nameOrProp);
    if (removed) {
      this._hydratedPropertyCount--;
    }
    return removed;
  }
  /**
   * Removes all properties associated with this component, optionally
   * filtered by name.
   *
   * @param {String=} name        Lowercase property name
   * @return {Boolean}            True, when deleted
   */
  removeAllProperties(name) {
    let removed = this._removeAllObjects(PROPERTY_INDEX, "_properties", name);
    this._hydratedPropertyCount = 0;
    return removed;
  }
  /**
   * Returns the Object representation of this component. The returned object
   * is a live jCal object and should be cloned if modified.
   * @return {Object}
   */
  toJSON() {
    return this.jCal;
  }
  /**
   * The string representation of this component.
   * @return {String}
   */
  toString() {
    return stringify.component(
      this.jCal,
      this._designSet
    );
  }
  /**
   * Retrieve a time zone definition from the component tree, if any is present.
   * If the tree contains no time zone definitions or the TZID cannot be
   * matched, returns null.
   *
   * @param {String} tzid     The ID of the time zone to retrieve
   * @return {Timezone}  The time zone corresponding to the ID, or null
   */
  getTimeZoneByID(tzid) {
    if (this.parent) {
      return this.parent.getTimeZoneByID(tzid);
    }
    if (!this._timezoneCache) {
      return null;
    }
    if (this._timezoneCache.has(tzid)) {
      return this._timezoneCache.get(tzid);
    }
    const zones2 = this.getAllSubcomponents("vtimezone");
    for (const zone of zones2) {
      if (zone.getFirstProperty("tzid").getFirstValue() === tzid) {
        const hydratedZone = new Timezone({
          component: zone,
          tzid
        });
        this._timezoneCache.set(tzid, hydratedZone);
        return hydratedZone;
      }
    }
    return null;
  }
}
class RecurExpansion {
  /**
   * Creates a new ICAL.RecurExpansion instance.
   *
   * The options object can be filled with the specified initial values. It can also contain
   * additional members, as a result of serializing a previous expansion state, as shown in the
   * example.
   *
   * @param {Object} options
   *        Recurrence expansion options
   * @param {Time} options.dtstart
   *        Start time of the event
   * @param {Component=} options.component
   *        Component for expansion, required if not resuming.
   */
  constructor(options) {
    this.ruleDates = [];
    this.exDates = [];
    this.fromData(options);
  }
  /**
   * True when iteration is fully completed.
   * @type {Boolean}
   */
  complete = false;
  /**
   * Array of rrule iterators.
   *
   * @type {RecurIterator[]}
   * @private
   */
  ruleIterators = null;
  /**
   * Array of rdate instances.
   *
   * @type {Time[]}
   * @private
   */
  ruleDates = null;
  /**
   * Array of exdate instances.
   *
   * @type {Time[]}
   * @private
   */
  exDates = null;
  /**
   * Current position in ruleDates array.
   * @type {Number}
   * @private
   */
  ruleDateInc = 0;
  /**
   * Current position in exDates array
   * @type {Number}
   * @private
   */
  exDateInc = 0;
  /**
   * Current negative date.
   *
   * @type {Time}
   * @private
   */
  exDate = null;
  /**
   * Current additional date.
   *
   * @type {Time}
   * @private
   */
  ruleDate = null;
  /**
   * Start date of recurring rules.
   *
   * @type {Time}
   */
  dtstart = null;
  /**
   * Last expanded time
   *
   * @type {Time}
   */
  last = null;
  /**
   * Initialize the recurrence expansion from the data object. The options
   * object may also contain additional members, see the
   * {@link ICAL.RecurExpansion constructor} for more details.
   *
   * @param {Object} options
   *        Recurrence expansion options
   * @param {Time} options.dtstart
   *        Start time of the event
   * @param {Component=} options.component
   *        Component for expansion, required if not resuming.
   */
  fromData(options) {
    let start = formatClassType(options.dtstart, Time);
    if (!start) {
      throw new Error(".dtstart (ICAL.Time) must be given");
    } else {
      this.dtstart = start;
    }
    if (options.component) {
      this._init(options.component);
    } else {
      this.last = formatClassType(options.last, Time) || start.clone();
      if (!options.ruleIterators) {
        throw new Error(".ruleIterators or .component must be given");
      }
      this.ruleIterators = options.ruleIterators.map(function(item) {
        return formatClassType(item, RecurIterator);
      });
      this.ruleDateInc = options.ruleDateInc;
      this.exDateInc = options.exDateInc;
      if (options.ruleDates) {
        this.ruleDates = options.ruleDates.map((item) => formatClassType(item, Time));
        this.ruleDate = this.ruleDates[this.ruleDateInc];
      }
      if (options.exDates) {
        this.exDates = options.exDates.map((item) => formatClassType(item, Time));
        this.exDate = this.exDates[this.exDateInc];
      }
      if (typeof options.complete !== "undefined") {
        this.complete = options.complete;
      }
    }
  }
  /**
   * Compare two ICAL.Time objects.  When the second parameter is a DATE and the first parameter is
   * DATE-TIME, strip the time and compare only the days.
   *
   * @private
   * @param {Time} a   The one object to compare
   * @param {Time} b   The other object to compare
   */
  _compare_special(a, b) {
    if (!a.isDate && b.isDate)
      return new Time({ year: a.year, month: a.month, day: a.day }).compare(b);
    return a.compare(b);
  }
  /**
   * Retrieve the next occurrence in the series.
   * @return {Time}
   */
  next() {
    let iter;
    let next;
    let compare;
    let maxTries = 500;
    let currentTry = 0;
    while (true) {
      if (currentTry++ > maxTries) {
        throw new Error(
          "max tries have occurred, rule may be impossible to fulfill."
        );
      }
      next = this.ruleDate;
      iter = this._nextRecurrenceIter(this.last);
      if (!next && !iter) {
        this.complete = true;
        break;
      }
      if (!next || iter && next.compare(iter.last) > 0) {
        next = iter.last.clone();
        iter.next();
      }
      if (this.ruleDate === next) {
        this._nextRuleDay();
      }
      this.last = next;
      if (this.exDate) {
        compare = this._compare_special(this.last, this.exDate);
        if (compare > 0) {
          this._nextExDay();
        }
        if (compare === 0) {
          this._nextExDay();
          continue;
        }
      }
      return this.last;
    }
  }
  /**
   * Converts object into a serialize-able format. This format can be passed
   * back into the expansion to resume iteration.
   * @return {Object}
   */
  toJSON() {
    function toJSON(item) {
      return item.toJSON();
    }
    let result = /* @__PURE__ */ Object.create(null);
    result.ruleIterators = this.ruleIterators.map(toJSON);
    if (this.ruleDates) {
      result.ruleDates = this.ruleDates.map(toJSON);
    }
    if (this.exDates) {
      result.exDates = this.exDates.map(toJSON);
    }
    result.ruleDateInc = this.ruleDateInc;
    result.exDateInc = this.exDateInc;
    result.last = this.last.toJSON();
    result.dtstart = this.dtstart.toJSON();
    result.complete = this.complete;
    return result;
  }
  /**
   * Extract all dates from the properties in the given component. The
   * properties will be filtered by the property name.
   *
   * @private
   * @param {Component} component             The component to search in
   * @param {String} propertyName             The property name to search for
   * @return {Time[]}                         The extracted dates.
   */
  _extractDates(component, propertyName) {
    let result = [];
    let props = component.getAllProperties(propertyName);
    for (let i = 0, len = props.length; i < len; i++) {
      for (let prop of props[i].getValues()) {
        let idx = binsearchInsert(
          result,
          prop,
          (a, b) => a.compare(b)
        );
        result.splice(idx, 0, prop);
      }
    }
    return result;
  }
  /**
   * Initialize the recurrence expansion.
   *
   * @private
   * @param {Component} component    The component to initialize from.
   */
  _init(component) {
    this.ruleIterators = [];
    this.last = this.dtstart.clone();
    if (!component.hasProperty("rdate") && !component.hasProperty("rrule") && !component.hasProperty("recurrence-id")) {
      this.ruleDate = this.last.clone();
      this.complete = true;
      return;
    }
    if (component.hasProperty("rdate")) {
      this.ruleDates = this._extractDates(component, "rdate");
      if (this.ruleDates[0] && this.ruleDates[0].compare(this.dtstart) < 0) {
        this.ruleDateInc = 0;
        this.last = this.ruleDates[0].clone();
      } else {
        this.ruleDateInc = binsearchInsert(
          this.ruleDates,
          this.last,
          (a, b) => a.compare(b)
        );
      }
      this.ruleDate = this.ruleDates[this.ruleDateInc];
    }
    if (component.hasProperty("rrule")) {
      let rules = component.getAllProperties("rrule");
      let i = 0;
      let len = rules.length;
      let rule;
      let iter;
      for (; i < len; i++) {
        rule = rules[i].getFirstValue();
        iter = rule.iterator(this.dtstart);
        this.ruleIterators.push(iter);
        iter.next();
      }
    }
    if (component.hasProperty("exdate")) {
      this.exDates = this._extractDates(component, "exdate");
      this.exDateInc = binsearchInsert(
        this.exDates,
        this.last,
        this._compare_special
      );
      this.exDate = this.exDates[this.exDateInc];
    }
  }
  /**
   * Advance to the next exdate
   * @private
   */
  _nextExDay() {
    this.exDate = this.exDates[++this.exDateInc];
  }
  /**
   * Advance to the next rule date
   * @private
   */
  _nextRuleDay() {
    this.ruleDate = this.ruleDates[++this.ruleDateInc];
  }
  /**
   * Find and return the recurrence rule with the most recent event and
   * return it.
   *
   * @private
   * @return {?RecurIterator}    Found iterator.
   */
  _nextRecurrenceIter() {
    let iters = this.ruleIterators;
    if (iters.length === 0) {
      return null;
    }
    let len = iters.length;
    let iter;
    let iterTime;
    let iterIdx = 0;
    let chosenIter;
    for (; iterIdx < len; iterIdx++) {
      iter = iters[iterIdx];
      iterTime = iter.last;
      if (iter.completed) {
        len--;
        if (iterIdx !== 0) {
          iterIdx--;
        }
        iters.splice(iterIdx, 1);
        continue;
      }
      if (!chosenIter || chosenIter.last.compare(iterTime) > 0) {
        chosenIter = iter;
      }
    }
    return chosenIter;
  }
}
class Event {
  /**
   * Creates a new ICAL.Event instance.
   *
   * @param {Component=} component              The ICAL.Component to base this event on
   * @param {Object} [options]                  Options for this event
   * @param {Boolean=} options.strictExceptions  When true, will verify exceptions are related by
   *                                              their UUID
   * @param {Array<Component|Event>=} options.exceptions
   *          Exceptions to this event, either as components or events. If not
   *            specified exceptions will automatically be set in relation of
   *            component's parent
   */
  constructor(component, options) {
    if (!(component instanceof Component)) {
      options = component;
      component = null;
    }
    if (component) {
      this.component = component;
    } else {
      this.component = new Component("vevent");
    }
    this._rangeExceptionCache = /* @__PURE__ */ Object.create(null);
    this.exceptions = /* @__PURE__ */ Object.create(null);
    this.rangeExceptions = [];
    if (options && options.strictExceptions) {
      this.strictExceptions = options.strictExceptions;
    }
    if (options && options.exceptions) {
      options.exceptions.forEach(this.relateException, this);
    } else if (this.component.parent && !this.isRecurrenceException()) {
      this.component.parent.getAllSubcomponents("vevent").forEach(function(event) {
        if (event.hasProperty("recurrence-id")) {
          this.relateException(event);
        }
      }, this);
    }
  }
  static THISANDFUTURE = "THISANDFUTURE";
  /**
   * List of related event exceptions.
   *
   * @type {Event[]}
   */
  exceptions = null;
  /**
   * When true, will verify exceptions are related by their UUID.
   *
   * @type {Boolean}
   */
  strictExceptions = false;
  /**
   * Relates a given event exception to this object.  If the given component
   * does not share the UID of this event it cannot be related and will throw
   * an exception.
   *
   * If this component is an exception it cannot have other exceptions
   * related to it.
   *
   * @param {Component|Event} obj       Component or event
   */
  relateException(obj) {
    if (this.isRecurrenceException()) {
      throw new Error("cannot relate exception to exceptions");
    }
    if (obj instanceof Component) {
      obj = new Event(obj);
    }
    if (this.strictExceptions && obj.uid !== this.uid) {
      throw new Error("attempted to relate unrelated exception");
    }
    let id = obj.recurrenceId.toString();
    this.exceptions[id] = obj;
    if (obj.modifiesFuture()) {
      let item = [
        obj.recurrenceId.toUnixTime(),
        id
      ];
      let idx = binsearchInsert(
        this.rangeExceptions,
        item,
        compareRangeException
      );
      this.rangeExceptions.splice(idx, 0, item);
    }
  }
  /**
   * Checks if this record is an exception and has the RANGE=THISANDFUTURE
   * value.
   *
   * @return {Boolean}        True, when exception is within range
   */
  modifiesFuture() {
    if (!this.component.hasProperty("recurrence-id")) {
      return false;
    }
    let range = this.component.getFirstProperty("recurrence-id").getParameter("range");
    return range === Event.THISANDFUTURE;
  }
  /**
   * Finds the range exception nearest to the given date.
   *
   * @param {Time} time   usually an occurrence time of an event
   * @return {?Event}     the related event/exception or null
   */
  findRangeException(time) {
    if (!this.rangeExceptions.length) {
      return null;
    }
    let utc = time.toUnixTime();
    let idx = binsearchInsert(
      this.rangeExceptions,
      [utc],
      compareRangeException
    );
    idx -= 1;
    if (idx < 0) {
      return null;
    }
    let rangeItem = this.rangeExceptions[idx];
    if (utc < rangeItem[0]) {
      return null;
    }
    return rangeItem[1];
  }
  /**
   * Returns the occurrence details based on its start time.  If the
   * occurrence has an exception will return the details for that exception.
   *
   * NOTE: this method is intend to be used in conjunction
   *       with the {@link ICAL.Event#iterator iterator} method.
   *
   * @param {Time} occurrence               time occurrence
   * @return {occurrenceDetails}            Information about the occurrence
   */
  getOccurrenceDetails(occurrence) {
    let id = occurrence.toString();
    let utcId = occurrence.convertToZone(Timezone.utcTimezone).toString();
    let item;
    let result = {
      //XXX: Clone?
      recurrenceId: occurrence
    };
    if (id in this.exceptions) {
      item = result.item = this.exceptions[id];
      result.startDate = item.startDate;
      result.endDate = item.endDate;
      result.item = item;
    } else if (utcId in this.exceptions) {
      item = this.exceptions[utcId];
      result.startDate = item.startDate;
      result.endDate = item.endDate;
      result.item = item;
    } else {
      let rangeExceptionId = this.findRangeException(
        occurrence
      );
      let end;
      if (rangeExceptionId) {
        let exception = this.exceptions[rangeExceptionId];
        result.item = exception;
        let startDiff = this._rangeExceptionCache[rangeExceptionId];
        if (!startDiff) {
          let original = exception.recurrenceId.clone();
          let newStart = exception.startDate.clone();
          original.zone = newStart.zone;
          startDiff = newStart.subtractDate(original);
          this._rangeExceptionCache[rangeExceptionId] = startDiff;
        }
        let start = occurrence.clone();
        start.zone = exception.startDate.zone;
        start.addDuration(startDiff);
        end = start.clone();
        end.addDuration(exception.duration);
        result.startDate = start;
        result.endDate = end;
      } else {
        end = occurrence.clone();
        end.addDuration(this.duration);
        result.endDate = end;
        result.startDate = occurrence;
        result.item = this;
      }
    }
    return result;
  }
  /**
   * Builds a recur expansion instance for a specific point in time (defaults
   * to startDate).
   *
   * @param {Time=} startTime     Starting point for expansion
   * @return {RecurExpansion}    Expansion object
   */
  iterator(startTime) {
    return new RecurExpansion({
      component: this.component,
      dtstart: startTime || this.startDate
    });
  }
  /**
   * Checks if the event is recurring
   *
   * @return {Boolean}        True, if event is recurring
   */
  isRecurring() {
    let comp = this.component;
    return comp.hasProperty("rrule") || comp.hasProperty("rdate");
  }
  /**
   * Checks if the event describes a recurrence exception. See
   * {@tutorial terminology} for details.
   *
   * @return {Boolean}    True, if the event describes a recurrence exception
   */
  isRecurrenceException() {
    return this.component.hasProperty("recurrence-id");
  }
  /**
   * Returns the types of recurrences this event may have.
   *
   * Returned as an object with the following possible keys:
   *
   *    - YEARLY
   *    - MONTHLY
   *    - WEEKLY
   *    - DAILY
   *    - MINUTELY
   *    - SECONDLY
   *
   * @return {Object.<frequencyValues, Boolean>}
   *          Object of recurrence flags
   */
  getRecurrenceTypes() {
    let rules = this.component.getAllProperties("rrule");
    let i = 0;
    let len = rules.length;
    let result = /* @__PURE__ */ Object.create(null);
    for (; i < len; i++) {
      let value = rules[i].getFirstValue();
      result[value.freq] = true;
    }
    return result;
  }
  /**
   * The uid of this event
   * @type {String}
   */
  get uid() {
    return this._firstProp("uid");
  }
  set uid(value) {
    this._setProp("uid", value);
  }
  /**
   * The start date
   * @type {Time}
   */
  get startDate() {
    return this._firstProp("dtstart");
  }
  set startDate(value) {
    this._setTime("dtstart", value);
  }
  /**
   * The end date. This can be the result directly from the property, or the
   * end date calculated from start date and duration. Setting the property
   * will remove any duration properties.
   * @type {Time}
   */
  get endDate() {
    let endDate = this._firstProp("dtend");
    if (!endDate) {
      let duration = this._firstProp("duration");
      endDate = this.startDate.clone();
      if (duration) {
        endDate.addDuration(duration);
      } else if (endDate.isDate) {
        endDate.day += 1;
      }
    }
    return endDate;
  }
  set endDate(value) {
    if (this.component.hasProperty("duration")) {
      this.component.removeProperty("duration");
    }
    this._setTime("dtend", value);
  }
  /**
   * The duration. This can be the result directly from the property, or the
   * duration calculated from start date and end date. Setting the property
   * will remove any `dtend` properties.
   * @type {Duration}
   */
  get duration() {
    let duration = this._firstProp("duration");
    if (!duration) {
      return this.endDate.subtractDateTz(this.startDate);
    }
    return duration;
  }
  set duration(value) {
    if (this.component.hasProperty("dtend")) {
      this.component.removeProperty("dtend");
    }
    this._setProp("duration", value);
  }
  /**
   * The location of the event.
   * @type {String}
   */
  get location() {
    return this._firstProp("location");
  }
  set location(value) {
    this._setProp("location", value);
  }
  /**
   * The attendees in the event
   * @type {Property[]}
   */
  get attendees() {
    return this.component.getAllProperties("attendee");
  }
  /**
   * The event summary
   * @type {String}
   */
  get summary() {
    return this._firstProp("summary");
  }
  set summary(value) {
    this._setProp("summary", value);
  }
  /**
   * The event description.
   * @type {String}
   */
  get description() {
    return this._firstProp("description");
  }
  set description(value) {
    this._setProp("description", value);
  }
  /**
   * The event color from [rfc7986](https://datatracker.ietf.org/doc/html/rfc7986)
   * @type {String}
   */
  get color() {
    return this._firstProp("color");
  }
  set color(value) {
    this._setProp("color", value);
  }
  /**
   * The organizer value as an uri. In most cases this is a mailto: uri, but
   * it can also be something else, like urn:uuid:...
   * @type {String}
   */
  get organizer() {
    return this._firstProp("organizer");
  }
  set organizer(value) {
    this._setProp("organizer", value);
  }
  /**
   * The sequence value for this event. Used for scheduling
   * see {@tutorial terminology}.
   * @type {Number}
   */
  get sequence() {
    return this._firstProp("sequence");
  }
  set sequence(value) {
    this._setProp("sequence", value);
  }
  /**
   * The recurrence id for this event. See {@tutorial terminology} for details.
   * @type {Time}
   */
  get recurrenceId() {
    return this._firstProp("recurrence-id");
  }
  set recurrenceId(value) {
    this._setTime("recurrence-id", value);
  }
  /**
   * Set/update a time property's value.
   * This will also update the TZID of the property.
   *
   * TODO: this method handles the case where we are switching
   * from a known timezone to an implied timezone (one without TZID).
   * This does _not_ handle the case of moving between a known
   *  (by TimezoneService) timezone to an unknown timezone...
   *
   * We will not add/remove/update the VTIMEZONE subcomponents
   *  leading to invalid ICAL data...
   * @private
   * @param {String} propName     The property name
   * @param {Time} time           The time to set
   */
  _setTime(propName, time) {
    let prop = this.component.getFirstProperty(propName);
    if (!prop) {
      prop = new Property(propName);
      this.component.addProperty(prop);
    }
    if (time.zone === Timezone.localTimezone || time.zone === Timezone.utcTimezone) {
      prop.removeParameter("tzid");
    } else {
      prop.setParameter("tzid", time.zone.tzid);
    }
    prop.setValue(time);
  }
  _setProp(name, value) {
    this.component.updatePropertyWithValue(name, value);
  }
  _firstProp(name) {
    return this.component.getFirstPropertyValue(name);
  }
  /**
   * The string representation of this event.
   * @return {String}
   */
  toString() {
    return this.component.toString();
  }
}
function compareRangeException(a, b) {
  if (a[0] > b[0]) return 1;
  if (b[0] > a[0]) return -1;
  return 0;
}
class ComponentParser {
  /**
   * Creates a new ICAL.ComponentParser instance.
   *
   * @param {Object=} options                   Component parser options
   * @param {Boolean} options.parseEvent        Whether events should be parsed
   * @param {Boolean} options.parseTimezeone    Whether timezones should be parsed
   */
  constructor(options) {
    if (typeof options === "undefined") {
      options = {};
    }
    for (let [key, value] of Object.entries(options)) {
      this[key] = value;
    }
  }
  /**
   * When true, parse events
   *
   * @type {Boolean}
   */
  parseEvent = true;
  /**
   * When true, parse timezones
   *
   * @type {Boolean}
   */
  parseTimezone = true;
  /* SAX like events here for reference */
  /**
   * Fired when parsing is complete
   * @callback
   */
  oncomplete = (
    /* c8 ignore next */
    function() {
    }
  );
  /**
   * Fired if an error occurs during parsing.
   *
   * @callback
   * @param {Error} err details of error
   */
  onerror = (
    /* c8 ignore next */
    function(err) {
    }
  );
  /**
   * Fired when a top level component (VTIMEZONE) is found
   *
   * @callback
   * @param {Timezone} component     Timezone object
   */
  ontimezone = (
    /* c8 ignore next */
    function(component) {
    }
  );
  /**
   * Fired when a top level component (VEVENT) is found.
   *
   * @callback
   * @param {Event} component    Top level component
   */
  onevent = (
    /* c8 ignore next */
    function(component) {
    }
  );
  /**
   * Process a string or parse ical object.  This function itself will return
   * nothing but will start the parsing process.
   *
   * Events must be registered prior to calling this method.
   *
   * @param {Component|String|Object} ical      The component to process,
   *        either in its final form, as a jCal Object, or string representation
   */
  process(ical) {
    if (typeof ical === "string") {
      ical = parse(ical);
    }
    if (!(ical instanceof Component)) {
      ical = new Component(ical);
    }
    let components = ical.getAllSubcomponents();
    let i = 0;
    let len = components.length;
    let component;
    for (; i < len; i++) {
      component = components[i];
      switch (component.name) {
        case "vtimezone":
          if (this.parseTimezone) {
            let tzid = component.getFirstPropertyValue("tzid");
            if (tzid) {
              this.ontimezone(new Timezone({
                tzid,
                component
              }));
            }
          }
          break;
        case "vevent":
          if (this.parseEvent) {
            this.onevent(new Event(component));
          }
          break;
        default:
          continue;
      }
    }
    this.oncomplete();
  }
}
var ICALmodule = {
  /**
   * The number of characters before iCalendar line folding should occur
   * @type {Number}
   * @default 75
   */
  foldLength: 75,
  debug: false,
  /**
   * The character(s) to be used for a newline. The default value is provided by
   * rfc5545.
   * @type {String}
   * @default "\r\n"
   */
  newLineChar: "\r\n",
  Binary,
  Component,
  ComponentParser,
  Duration,
  Event,
  Period,
  Property,
  Recur,
  RecurExpansion,
  RecurIterator,
  Time,
  Timezone,
  TimezoneService,
  UtcOffset,
  VCardTime,
  parse,
  stringify,
  design,
  helpers
};
const byteToHex = [];
for (let i = 0; i < 256; ++i) {
  byteToHex.push((i + 256).toString(16).slice(1));
}
function unsafeStringify(arr, offset = 0) {
  return (byteToHex[arr[offset + 0]] + byteToHex[arr[offset + 1]] + byteToHex[arr[offset + 2]] + byteToHex[arr[offset + 3]] + "-" + byteToHex[arr[offset + 4]] + byteToHex[arr[offset + 5]] + "-" + byteToHex[arr[offset + 6]] + byteToHex[arr[offset + 7]] + "-" + byteToHex[arr[offset + 8]] + byteToHex[arr[offset + 9]] + "-" + byteToHex[arr[offset + 10]] + byteToHex[arr[offset + 11]] + byteToHex[arr[offset + 12]] + byteToHex[arr[offset + 13]] + byteToHex[arr[offset + 14]] + byteToHex[arr[offset + 15]]).toLowerCase();
}
let getRandomValues;
const rnds8 = new Uint8Array(16);
function rng() {
  if (!getRandomValues) {
    if (typeof crypto === "undefined" || !crypto.getRandomValues) {
      throw new Error("crypto.getRandomValues() not supported. See https://github.com/uuidjs/uuid#getrandomvalues-not-supported");
    }
    getRandomValues = crypto.getRandomValues.bind(crypto);
  }
  return getRandomValues(rnds8);
}
const randomUUID = typeof crypto !== "undefined" && crypto.randomUUID && crypto.randomUUID.bind(crypto);
const native = { randomUUID };
function _v4(options, buf, offset) {
  options = options || {};
  const rnds = options.random ?? options.rng?.() ?? rng();
  if (rnds.length < 16) {
    throw new Error("Random bytes length must be >= 16");
  }
  rnds[6] = rnds[6] & 15 | 64;
  rnds[8] = rnds[8] & 63 | 128;
  return unsafeStringify(rnds);
}
function v4(options, buf, offset) {
  if (native.randomUUID && true && !options) {
    return native.randomUUID();
  }
  return _v4(options);
}
const _export_sfc = (sfc, props) => {
  const target = sfc.__vccOpts || sfc;
  for (const [key, val] of props) {
    target[key] = val;
  }
  return target;
};
const _sfc_main$2 = {
  name: "CalendarAvailability",
  components: {
    NcDateTimePickerNative,
    NcButton,
    IconAdd: PlusIcon,
    IconDelete: Delete
  },
  props: {
    slots: {
      type: Object,
      required: true
    },
    loading: {
      type: Boolean,
      default: false
    },
    l10nTo: {
      type: String,
      required: true
    },
    l10nDeleteSlot: {
      type: String,
      required: true
    },
    l10nEmptyDay: {
      type: String,
      required: true
    },
    l10nAddSlot: {
      type: String,
      required: true
    },
    l10nWeekDayListLabel: {
      type: String,
      default: "Weekdays"
    },
    l10nMonday: {
      type: String,
      required: true
    },
    l10nTuesday: {
      type: String,
      required: true
    },
    l10nWednesday: {
      type: String,
      required: true
    },
    l10nThursday: {
      type: String,
      required: true
    },
    l10nFriday: {
      type: String,
      required: true
    },
    l10nSaturday: {
      type: String,
      required: true
    },
    l10nSunday: {
      type: String,
      required: true
    },
    l10nStartPickerLabel: {
      type: Function,
      default: (dayName) => `Pick a start time for ${dayName}`
    },
    l10nEndPickerLabel: {
      type: Function,
      default: (dayName) => `Pick a end time for ${dayName}`
    }
  },
  data() {
    return {
      internalSlots: this.slotsToInternalData(this.slots)
    };
  },
  watch: {
    slots() {
      this.internalSlots = this.slotsToInternalData(this.slots);
    }
  },
  methods: {
    timeStampSlotsToDateObjectSlots(slots) {
      return slots.map((slot) => ({
        start: new Date(slot.start * 1e3),
        end: new Date(slot.end * 1e3)
      }));
    },
    slotsToInternalData() {
      const moToSa = [
        {
          id: "MO",
          displayName: this.l10nMonday,
          slots: this.timeStampSlotsToDateObjectSlots(this.slots.MO)
        },
        {
          id: "TU",
          displayName: this.l10nTuesday,
          slots: this.timeStampSlotsToDateObjectSlots(this.slots.TU)
        },
        {
          id: "WE",
          displayName: this.l10nWednesday,
          slots: this.timeStampSlotsToDateObjectSlots(this.slots.WE)
        },
        {
          id: "TH",
          displayName: this.l10nThursday,
          slots: this.timeStampSlotsToDateObjectSlots(this.slots.TH)
        },
        {
          id: "FR",
          displayName: this.l10nFriday,
          slots: this.timeStampSlotsToDateObjectSlots(this.slots.FR)
        },
        {
          id: "SA",
          displayName: this.l10nSaturday,
          slots: this.timeStampSlotsToDateObjectSlots(this.slots.SA)
        }
      ];
      const sunday = {
        id: "SU",
        displayName: this.l10nSunday,
        slots: this.timeStampSlotsToDateObjectSlots(this.slots.SU)
      };
      return getFirstDay() === 1 ? [...moToSa, sunday] : [sunday, ...moToSa];
    },
    internalDataToSlots() {
      const converted = {};
      this.internalSlots.forEach(({ id, slots }) => {
        converted[id] = slots.map((slot) => ({
          start: Math.round(slot.start.getTime() / 1e3),
          end: Math.round(slot.end.getTime() / 1e3)
        }));
      });
      return converted;
    },
    addSlot(day) {
      const start = /* @__PURE__ */ new Date();
      start.setHours(9, 0, 0, 0);
      const end = /* @__PURE__ */ new Date();
      end.setHours(17, 0, 0, 0);
      day.slots.push({
        start,
        end
      });
      this.onChangeSlots();
    },
    removeSlot(day, idx) {
      day.slots.splice(idx, 1);
      this.onChangeSlots();
    },
    onChangeSlots() {
      this.$emit("update:slots", this.internalDataToSlots());
    }
  }
};
const _hoisted_1 = ["aria-label"];
const _hoisted_2 = { class: "label-weekday" };
const _hoisted_3 = ["id"];
const _hoisted_4 = { class: "availability-slot-group" };
const _hoisted_5 = { class: "to-text" };
const _hoisted_6 = {
  key: 0,
  class: "empty-content"
};
function _sfc_render$2(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_NcDateTimePickerNative = resolveComponent("NcDateTimePickerNative");
  const _component_IconDelete = resolveComponent("IconDelete");
  const _component_NcButton = resolveComponent("NcButton");
  const _component_IconAdd = resolveComponent("IconAdd");
  return openBlock(), createElementBlock("ul", {
    class: "week-day-container",
    "aria-label": $props.l10nWeekDayListLabel
  }, [
    (openBlock(true), createElementBlock(Fragment, null, renderList($data.internalSlots, (day) => {
      return openBlock(), createElementBlock("li", {
        key: `day-label-${day.id}`,
        class: "day-container"
      }, [
        createBaseVNode("div", _hoisted_2, [
          createBaseVNode("span", {
            id: day.displayName + "-label"
          }, toDisplayString(day.displayName), 9, _hoisted_3)
        ]),
        (openBlock(), createElementBlock("div", {
          key: `day-slots-${day.id}`,
          class: "availability-slots"
        }, [
          createBaseVNode("div", _hoisted_4, [
            (openBlock(true), createElementBlock(Fragment, null, renderList(day.slots, (slot, idx) => {
              return openBlock(), createElementBlock("div", {
                key: `slot-${day.id}-${idx}`,
                class: "availability-slot"
              }, [
                createVNode(_component_NcDateTimePickerNative, {
                  id: `start-${day.id}-${idx}`,
                  modelValue: slot.start,
                  "onUpdate:modelValue": ($event) => slot.start = $event,
                  type: "time",
                  label: $props.l10nStartPickerLabel?.(day.displayName),
                  "hide-label": true,
                  class: "start-date",
                  onChange: $options.onChangeSlots
                }, null, 8, ["id", "modelValue", "onUpdate:modelValue", "label", "onChange"]),
                createBaseVNode("span", _hoisted_5, toDisplayString($props.l10nTo), 1),
                createVNode(_component_NcDateTimePickerNative, {
                  id: `end-${day.id}-${idx}`,
                  modelValue: slot.end,
                  "onUpdate:modelValue": ($event) => slot.end = $event,
                  type: "time",
                  label: $props.l10nEndPickerLabel?.(day.displayName),
                  "hide-label": true,
                  class: "end-date",
                  onChange: $options.onChangeSlots
                }, null, 8, ["id", "modelValue", "onUpdate:modelValue", "label", "onChange"]),
                (openBlock(), createBlock(_component_NcButton, {
                  key: `slot-${day.id}-${idx}-btn`,
                  type: "tertiary",
                  class: "button",
                  "aria-label": $props.l10nDeleteSlot,
                  title: $props.l10nDeleteSlot,
                  onClick: ($event) => $options.removeSlot(day, idx)
                }, {
                  icon: withCtx(() => [
                    createVNode(_component_IconDelete, { size: 20 })
                  ]),
                  _: 2
                }, 1032, ["aria-label", "title", "onClick"]))
              ]);
            }), 128))
          ]),
          day.slots.length === 0 ? (openBlock(), createElementBlock("span", _hoisted_6, toDisplayString($props.l10nEmptyDay), 1)) : createCommentVNode("", true)
        ])),
        (openBlock(), createBlock(_component_NcButton, {
          key: `add-slot-${day.id}`,
          disabled: $props.loading,
          class: "add-another button",
          title: $props.l10nAddSlot,
          "aria-label": $props.l10nAddSlot,
          onClick: ($event) => $options.addSlot(day)
        }, {
          icon: withCtx(() => [
            createVNode(_component_IconAdd, { size: 20 })
          ]),
          _: 2
        }, 1032, ["disabled", "title", "aria-label", "onClick"]))
      ]);
    }), 128))
  ], 8, _hoisted_1);
}
const CalendarAvailability = /* @__PURE__ */ _export_sfc(_sfc_main$2, [["render", _sfc_render$2], ["__scopeId", "data-v-3ef03e87"]]);
const logger = getLoggerBuilder().detectUser().setApp("@nextcloud/calendar-availability-vue").build();
function getEmptySlots$1() {
  return {
    MO: [],
    TU: [],
    WE: [],
    TH: [],
    FR: [],
    SA: [],
    SU: []
  };
}
function vavailabilityToSlots(vavailability) {
  const parsedIcal = ICALmodule.parse(vavailability);
  const vcalendarComp = new ICALmodule.Component(parsedIcal);
  const vavailabilityComp = vcalendarComp.getFirstSubcomponent("vavailability");
  let timezoneId;
  const timezoneComp = vcalendarComp.getFirstSubcomponent("vtimezone");
  if (timezoneComp) {
    timezoneId = timezoneComp.getFirstProperty("tzid").getFirstValue();
  }
  const availableComps = vavailabilityComp.getAllSubcomponents("available");
  const slots = getEmptySlots$1();
  availableComps.forEach((availableComp) => {
    const startIcalDate = availableComp.getFirstProperty("dtstart").getFirstValue();
    const endIcalDate = availableComp.getFirstProperty("dtend").getFirstValue();
    const rrule = availableComp.getFirstProperty("rrule");
    const start = /* @__PURE__ */ new Date();
    start.setHours(startIcalDate.hour, startIcalDate.minute, 0, 0);
    const end = /* @__PURE__ */ new Date();
    end.setHours(endIcalDate.hour, endIcalDate.minute, 0, 0);
    if (rrule.getFirstValue().freq !== "WEEKLY") {
      logger.warn("rrule not supported", {
        rrule: rrule.toICALString()
      });
      return;
    }
    rrule.getFirstValue().getComponent("BYDAY").forEach((day) => {
      slots[day].push({
        start: start.getTime() / 1e3,
        end: end.getTime() / 1e3
      });
    });
  });
  return {
    slots,
    timezoneId
  };
}
function slotsToVavailability(slots, timezoneId) {
  const vcalendarComp = new ICALmodule.Component("vcalendar");
  vcalendarComp.addPropertyWithValue("prodid", "Nextcloud DAV app");
  const predefinedTimezoneIcal = distExports.getZoneString(timezoneId);
  if (predefinedTimezoneIcal) {
    const timezoneComp = new ICALmodule.Component(ICALmodule.parse(predefinedTimezoneIcal));
    vcalendarComp.addSubcomponent(timezoneComp);
  } else {
    const timezoneComp = new ICALmodule.Component("vtimezone");
    timezoneComp.addPropertyWithValue("tzid", timezoneId);
    vcalendarComp.addSubcomponent(timezoneComp);
  }
  const vavailabilityComp = new ICALmodule.Component("vavailability");
  const deduplicated = slots.reduce((acc, slot) => {
    const start = new Date(slot.start * 1e3);
    const end = new Date(slot.end * 1e3);
    const key = [
      start.getHours(),
      start.getMinutes(),
      end.getHours(),
      end.getMinutes()
    ].join("-");
    return {
      ...acc,
      [key]: [...acc[key] ?? [], slot]
    };
  }, {});
  Object.keys(deduplicated).map((key) => {
    const slots2 = deduplicated[key];
    const start = slots2[0].start;
    const end = slots2[0].end;
    const days = slots2.map((slot) => slot.day).filter((day, index, self) => self.indexOf(day) === index);
    const availableComp = new ICALmodule.Component("available");
    const startTimeProp = availableComp.addPropertyWithValue("dtstart", ICALmodule.Time.fromJSDate(new Date(start * 1e3), false));
    startTimeProp.setParameter("tzid", timezoneId);
    const endTimeProp = availableComp.addPropertyWithValue("dtend", ICALmodule.Time.fromJSDate(new Date(end * 1e3), false));
    endTimeProp.setParameter("tzid", timezoneId);
    availableComp.addPropertyWithValue("uid", v4());
    availableComp.addPropertyWithValue("rrule", {
      freq: "WEEKLY",
      byday: days
    });
    return availableComp;
  }).map(vavailabilityComp.addSubcomponent.bind(vavailabilityComp));
  vcalendarComp.addSubcomponent(vavailabilityComp);
  return vcalendarComp.toString();
}
let client = void 0;
function getClient() {
  if (!client) {
    const remote = generateRemoteUrl(`dav/calendars/${getCurrentUser().uid}`);
    client = lr(remote);
    const setHeaders = (token) => {
      client.setHeaders({
        // Add this so the server knows it is an request from the browser
        "X-Requested-With": "XMLHttpRequest",
        // Inject user auth
        requesttoken: token ?? ""
      });
    };
    onRequestTokenUpdate(setHeaders);
    setHeaders(getRequestToken());
  }
  return client;
}
function getEmptySlots() {
  return {
    MO: [],
    TU: [],
    WE: [],
    TH: [],
    FR: [],
    SA: [],
    SU: []
  };
}
async function findScheduleInboxAvailability() {
  const response = await getClient().customRequest("inbox", {
    method: "PROPFIND",
    data: `<?xml version="1.0"?>
			<x0:propfind xmlns:x0="DAV:">
			  <x0:prop>
				<x1:calendar-availability xmlns:x1="urn:ietf:params:xml:ns:caldav"/>
			  </x0:prop>
			</x0:propfind>`
  });
  const xml = await Ke(await response.text());
  if (!xml) {
    return void 0;
  }
  const availability = xml?.multistatus?.response[0]?.propstat?.prop["calendar-availability"];
  if (!availability) {
    return void 0;
  }
  return vavailabilityToSlots(availability);
}
async function saveScheduleInboxAvailability(slots, timezoneId) {
  const all = [...Object.keys(slots).flatMap((dayId) => slots[dayId].map((slot) => ({
    ...slot,
    day: dayId
  })))];
  const vavailability = slotsToVavailability(all, timezoneId);
  logger$1.debug("New availability ical created", {
    vavailability
  });
  await getClient().customRequest("inbox", {
    method: "PROPPATCH",
    data: `<?xml version="1.0"?>
			<x0:propertyupdate xmlns:x0="DAV:">
			  <x0:set>
				<x0:prop>
				  <x1:calendar-availability xmlns:x1="urn:ietf:params:xml:ns:caldav">${vavailability}</x1:calendar-availability>
				</x0:prop>
			  </x0:set>
			</x0:propertyupdate>`
  });
}
/*!
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
async function enableUserStatusAutomation() {
  await cancelableClient.post(
    generateOcsUrl("/apps/provisioning_api/api/v1/config/users/{appId}/{configKey}", {
      appId: "dav",
      configKey: "user_status_automation"
    }),
    {
      configValue: "yes"
    }
  );
}
async function disableUserStatusAutomation() {
  await cancelableClient.delete(generateOcsUrl("/apps/provisioning_api/api/v1/config/users/{appId}/{configKey}", {
    appId: "dav",
    configKey: "user_status_automation"
  }));
}
const _sfc_main$1 = /* @__PURE__ */ defineComponent({
  __name: "AvailabilityForm",
  setup(__props, { expose: __expose }) {
    __expose();
    const timezone = getCapabilities().core.user?.timezone ?? Intl.DateTimeFormat().resolvedOptions().timeZone;
    const loading = ref(true);
    const saving = ref(false);
    const slots = ref(getEmptySlots());
    const automated = ref(loadState("dav", "user_status_automation") === "yes");
    onMounted(async () => {
      try {
        const slotData = await findScheduleInboxAvailability();
        if (!slotData) {
          logger$1.debug("no availability is set");
        } else {
          slots.value = slotData.slots;
          logger$1.debug("availability loaded", { slots: slots.value });
        }
      } catch (error) {
        logger$1.error("could not load existing availability", { error });
        showError(translate("dav", "Failed to load availability"));
      } finally {
        loading.value = false;
      }
    });
    async function save() {
      saving.value = true;
      try {
        await saveScheduleInboxAvailability(slots.value, timezone);
        if (automated.value) {
          await enableUserStatusAutomation();
        } else {
          await disableUserStatusAutomation();
        }
        showSuccess(translate("dav", "Saved availability"));
      } catch (error) {
        logger$1.error("could not save availability", { error });
        showError(translate("dav", "Failed to save availability"));
      } finally {
        saving.value = false;
      }
    }
    const __returned__ = { timezone, loading, saving, slots, automated, save, get CalendarAvailability() {
      return CalendarAvailability;
    }, get t() {
      return translate;
    }, get NcButton() {
      return NcButton;
    }, get NcCheckboxRadioSwitch() {
      return NcCheckboxRadioSwitch;
    } };
    Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true });
    return __returned__;
  }
});
function _sfc_render$1(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("div", null, [
    createVNode($setup["CalendarAvailability"], {
      slots: $setup.slots,
      "onUpdate:slots": _cache[0] || (_cache[0] = ($event) => $setup.slots = $event),
      loading: $setup.loading,
      l10nTo: $setup.t("dav", "to"),
      l10nDeleteSlot: $setup.t("dav", "Delete slot"),
      l10nEmptyDay: $setup.t("dav", "No working hours set"),
      l10nAddSlot: $setup.t("dav", "Add slot"),
      l10nWeekDayListLabel: $setup.t("dav", "Weekdays"),
      l10nMonday: $setup.t("dav", "Monday"),
      l10nTuesday: $setup.t("dav", "Tuesday"),
      l10nWednesday: $setup.t("dav", "Wednesday"),
      l10nThursday: $setup.t("dav", "Thursday"),
      l10nFriday: $setup.t("dav", "Friday"),
      l10nSaturday: $setup.t("dav", "Saturday"),
      l10nSunday: $setup.t("dav", "Sunday"),
      l10nStartPickerLabel: (dayName) => $setup.t("dav", "Pick a start time for {dayName}", { dayName }),
      l10nEndPickerLabel: (dayName) => $setup.t("dav", "Pick a end time for {dayName}", { dayName })
    }, null, 8, ["slots", "loading", "l10nTo", "l10nDeleteSlot", "l10nEmptyDay", "l10nAddSlot", "l10nWeekDayListLabel", "l10nMonday", "l10nTuesday", "l10nWednesday", "l10nThursday", "l10nFriday", "l10nSaturday", "l10nSunday", "l10nStartPickerLabel", "l10nEndPickerLabel"]),
    createVNode($setup["NcCheckboxRadioSwitch"], {
      modelValue: $setup.automated,
      "onUpdate:modelValue": _cache[1] || (_cache[1] = ($event) => $setup.automated = $event)
    }, {
      default: withCtx(() => [
        createTextVNode(
          toDisplayString($setup.t("dav", 'Automatically set user status to "Do not disturb" outside of availability to mute all notifications.')),
          1
          /* TEXT */
        )
      ]),
      _: 1
      /* STABLE */
    }, 8, ["modelValue"]),
    createVNode($setup["NcButton"], {
      disabled: $setup.loading || $setup.saving,
      variant: "primary",
      onClick: $setup.save
    }, {
      default: withCtx(() => [
        createTextVNode(
          toDisplayString($setup.t("dav", "Save")),
          1
          /* TEXT */
        )
      ]),
      _: 1
      /* STABLE */
    }, 8, ["disabled"])
  ]);
}
const AvailabilityForm = /* @__PURE__ */ _export_sfc$1(_sfc_main$1, [["render", _sfc_render$1], ["__scopeId", "data-v-9ddf5091"], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/build/frontend/apps/dav/src/components/AvailabilityForm.vue"]]);
const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "UserAvailability",
  setup(__props, { expose: __expose }) {
    __expose();
    const hideAbsenceSettings = loadState("dav", "hide_absence_settings", true);
    const __returned__ = { hideAbsenceSettings, get t() {
      return translate;
    }, get NcSettingsSection() {
      return NcSettingsSection;
    }, AbsenceForm, AvailabilityForm };
    Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true });
    return __returned__;
  }
});
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("div", null, [
    createVNode($setup["NcSettingsSection"], {
      id: "availability",
      name: $setup.t("dav", "Availability"),
      description: $setup.t("dav", "If you configure your working hours, other people will see when you are out of office when they book a meeting.")
    }, {
      default: withCtx(() => [
        createVNode($setup["AvailabilityForm"])
      ]),
      _: 1
      /* STABLE */
    }, 8, ["name", "description"]),
    !$setup.hideAbsenceSettings ? (openBlock(), createBlock($setup["NcSettingsSection"], {
      key: 0,
      id: "absence",
      name: $setup.t("dav", "Absence"),
      description: $setup.t("dav", "Configure your next absence period.")
    }, {
      default: withCtx(() => [
        createVNode($setup["AbsenceForm"])
      ]),
      _: 1
      /* STABLE */
    }, 8, ["name", "description"])) : createCommentVNode("v-if", true)
  ]);
}
const UserAvailability = /* @__PURE__ */ _export_sfc$1(_sfc_main, [["render", _sfc_render], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/build/frontend/apps/dav/src/views/UserAvailability.vue"]]);
const app = createApp(UserAvailability);
app.mount("#settings-personal-availability");
//# sourceMappingURL=dav-settings-personal-availability.mjs.map
