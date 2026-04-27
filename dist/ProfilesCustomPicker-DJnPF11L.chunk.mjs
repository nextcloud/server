const appName = "nextcloud-ui";
const appVersion = "1.0.0";
import { c as cancelableClient } from "./index-D5H5XMHa.chunk.mjs";
import { b as generateUrl, c as generateOcsUrl, d as debounce } from "./index-rAufP352.chunk.mjs";
import { A as ArrowRightIcon, N as NcButton } from "./ArrowRight-BC77f5L9.chunk.mjs";
import { N as NcEmptyContent } from "./NcEmptyContent-B8-90BSI-BIPGjjpl.chunk.mjs";
import { N as NcSelect } from "./NcSelect-DLheQ2yp-DVxJhLyT.chunk.mjs";
import { A as AccountOutline, l as logger } from "./logger-BQwTrq8j.chunk.mjs";
import { r as resolveComponent, o as openBlock, f as createElementBlock, g as createBaseVNode, t as toDisplayString, x as createVNode, w as withCtx, j as createTextVNode, c as createBlock, h as createCommentVNode } from "./preload-helper-xAe3EUYB.chunk.mjs";
import { _ as _export_sfc } from "./index-o76qk6sn.chunk.mjs";
import "./util-BSOXDoOW.chunk.mjs";
import "./Web-BOM4en5n.chunk.mjs";
import "./translation-DoG5ZELJ-2UfAUX2V.chunk.mjs";
import "./NcModal-DHryP_87-Da4dXMUU.chunk.mjs";
import "./TrashCanOutline-DgEtyFGH.chunk.mjs";
const _sfc_main = {
  name: "ProfilesCustomPicker",
  components: {
    NcSelect,
    NcButton,
    NcEmptyContent,
    AccountOutline,
    ArrowRightIcon
  },
  props: {
    providerId: {
      type: String,
      required: true
    },
    accessible: {
      type: Boolean,
      default: false
    }
  },
  emits: ["submit"],
  data() {
    return {
      searchQuery: "",
      loading: false,
      resultUrl: null,
      reference: null,
      profiles: [],
      selectedProfile: null,
      abortController: null
    };
  },
  computed: {
    options() {
      if (this.searchQuery !== "") {
        return this.profiles;
      }
      return [];
    },
    noResultText() {
      return this.loading ? t("profile", "Searching …") : t("profile", "Not found");
    }
  },
  mounted() {
    this.focusOnInput();
  },
  methods: {
    focusOnInput() {
      this.$nextTick(() => {
        this.$refs["profiles-search-input"].$el.getElementsByTagName("input")[0]?.focus();
      });
    },
    async searchForProfile(query) {
      if (query.trim() === "" || query.trim().length < 3) {
        return;
      }
      this.searchQuery = query.trim();
      this.loading = true;
      await this.debounceFindProfiles(query);
    },
    debounceFindProfiles: debounce(function(...args) {
      this.findProfiles(...args);
    }, 300),
    async findProfiles(query) {
      const url = generateOcsUrl("core/autocomplete/get?search={searchQuery}&itemType=%20&itemId=%20&shareTypes[]=0&limit=20", { searchQuery: query });
      try {
        const res = await cancelableClient.get(url);
        this.profiles = res.data.ocs.data.map((userAutocomplete) => {
          return {
            user: userAutocomplete.id,
            displayName: userAutocomplete.label,
            icon: userAutocomplete.icon,
            subtitle: userAutocomplete.subline,
            isNoUser: userAutocomplete.source.startsWith("users")
          };
        });
      } catch (error) {
        logger.error("profile_picker: error while searching for users", { error });
      } finally {
        this.loading = false;
      }
    },
    submit() {
      this.resultUrl = window.location.origin + generateUrl(`/u/${this.selectedProfile.user.trim().toLowerCase()}`, null, { noRewrite: true });
      this.$emit("submit", this.resultUrl);
      this.$el.dispatchEvent(new CustomEvent("submit", { detail: this.resultUrl, bubbles: true }));
    },
    async resolveResult(selectedItem) {
      this.loading = true;
      this.abortController = new AbortController();
      this.selectedProfile = selectedItem;
      this.resultUrl = window.location.origin + generateUrl(`/u/${this.selectedProfile.user.trim().toLowerCase()}`, null, { noRewrite: true });
      try {
        const res = await cancelableClient.get(generateOcsUrl("references/resolve", 2) + "?reference=" + encodeURIComponent(this.resultUrl), {
          signal: this.abortController.signal
        });
        this.reference = res.data.ocs.data.references[this.resultUrl];
      } catch (error) {
        logger.error("profile_picker: error resolving the user profile link", { error });
      } finally {
        this.loading = false;
      }
    },
    clearSelection() {
      this.selectedProfile = null;
      this.resultUrl = null;
      this.reference = null;
    }
  }
};
const _hoisted_1 = { class: "profile-picker" };
const _hoisted_2 = { class: "profile-picker__heading" };
const _hoisted_3 = { class: "input-wrapper" };
const _hoisted_4 = { class: "profile-picker__footer" };
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_NcSelect = resolveComponent("NcSelect");
  const _component_AccountOutline = resolveComponent("AccountOutline");
  const _component_NcEmptyContent = resolveComponent("NcEmptyContent");
  const _component_ArrowRightIcon = resolveComponent("ArrowRightIcon");
  const _component_NcButton = resolveComponent("NcButton");
  return openBlock(), createElementBlock("div", _hoisted_1, [
    createBaseVNode("div", _hoisted_2, [
      createBaseVNode(
        "h2",
        null,
        toDisplayString(_ctx.t("profile", "Profile picker")),
        1
        /* TEXT */
      ),
      createBaseVNode("div", _hoisted_3, [
        createVNode(_component_NcSelect, {
          ref: "profiles-search-input",
          modelValue: $data.selectedProfile,
          "onUpdate:modelValue": _cache[0] || (_cache[0] = ($event) => $data.selectedProfile = $event),
          inputId: "profiles-search",
          loading: $data.loading,
          filterable: false,
          placeholder: _ctx.t("profile", "Search for a user profile"),
          clearSearchOnBlur: () => false,
          multiple: false,
          options: $options.options,
          label: "displayName",
          onSearch: $options.searchForProfile,
          "onOption:selecting": $options.resolveResult
        }, {
          "no-options": withCtx(({ search }) => [
            createTextVNode(
              toDisplayString(search ? $options.noResultText : _ctx.t("profile", "Search for a user profile. Start typing")),
              1
              /* TEXT */
            )
          ]),
          _: 1
          /* STABLE */
        }, 8, ["modelValue", "loading", "placeholder", "options", "onSearch", "onOption:selecting"])
      ]),
      createVNode(_component_NcEmptyContent, { class: "empty-content" }, {
        icon: withCtx(() => [
          createVNode(_component_AccountOutline, { size: 20 })
        ]),
        _: 1
        /* STABLE */
      })
    ]),
    createBaseVNode("div", _hoisted_4, [
      $data.selectedProfile !== null ? (openBlock(), createBlock(_component_NcButton, {
        key: 0,
        variant: "primary",
        "aria-label": _ctx.t("profile", "Insert selected user profile link"),
        disabled: $data.loading || $data.selectedProfile === null,
        onClick: $options.submit
      }, {
        icon: withCtx(() => [
          createVNode(_component_ArrowRightIcon)
        ]),
        default: withCtx(() => [
          createTextVNode(
            toDisplayString(_ctx.t("profile", "Insert")) + " ",
            1
            /* TEXT */
          )
        ]),
        _: 1
        /* STABLE */
      }, 8, ["aria-label", "disabled", "onClick"])) : createCommentVNode("v-if", true)
    ])
  ]);
}
const ProfilesCustomPicker = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render], ["__scopeId", "data-v-c89c6b27"], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/build/frontend/apps/profile/src/components/ProfilesCustomPicker.vue"]]);
export {
  ProfilesCustomPicker as default
};
//# sourceMappingURL=ProfilesCustomPicker-DJnPF11L.chunk.mjs.map
