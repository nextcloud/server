const appName = "nextcloud-ui";
const appVersion = "1.0.0";
import { b as defineComponent, k as useModel, n as computed, z as watch, o as openBlock, f as createElementBlock, v as normalizeClass, h as createCommentVNode, q as mergeModels } from "./preload-helper-xAe3EUYB.chunk.mjs";
import { c as cancelableClient } from "./index-D5H5XMHa.chunk.mjs";
import { g as getCapabilities } from "./index-o76qk6sn.chunk.mjs";
import { c as generateOcsUrl } from "./index-rAufP352.chunk.mjs";
import { r as register, f as t51, g as t11, _ as _export_sfc, b as t } from "./Web-BOM4en5n.chunk.mjs";
import { l as logger } from "./ArrowRight-BC77f5L9.chunk.mjs";
const awaySvg = '<!--\n  - SPDX-FileCopyrightText: 2020 Google Inc.\n  - SPDX-License-Identifier: Apache-2.0\n-->\n<svg viewBox="0 -960 960 960" width="24px" height="24px" xmlns="http://www.w3.org/2000/svg">\n	<path\n		fill="var(--user-status-color-away, var(--color-warning, #C88800))"\n		d="m612-292 56-56-148-148v-184h-80v216l172 172ZM480-80q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Z"/>\n</svg>\n';
const busySvg = '<!--\n  - SPDX-FileCopyrightText: 2020 Google Inc.\n  - SPDX-License-Identifier: Apache-2.0\n-->\n<svg viewBox="0 -960 960 960" width="24px" height="24px" xmlns="http://www.w3.org/2000/svg">\n	<path\n		fill="var(--user-status-color-busy, var(--color-error, #DB0606))"\n		d="M480-80q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Z"/>\n</svg>\n';
const dndSvg = '<!--\n  - SPDX-FileCopyrightText: 2020 Google Inc.\n  - SPDX-License-Identifier: Apache-2.0\n-->\n<svg viewBox="0 -960 960 960" width="24px" height="24px" xmlns="http://www.w3.org/2000/svg">\n	<path\n		fill="var(--user-status-color-busy, var(--color-error, #DB0606))"\n		d="M280-440h400v-80H280v80ZM480-80q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Z"/>\n</svg>\n';
const invisibleSvg = '<!--\n  - SPDX-FileCopyrightText: 2020 Google Inc.\n  - SPDX-License-Identifier: Apache-2.0\n-->\n<svg viewBox="0 -960 960 960" width="24px" height="24px" xmlns="http://www.w3.org/2000/svg">\n	<path\n		fill="var(--user-status-color-offline, var(--color-text-maxcontrast, #6B6B6B))"\n		d="M480-80q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Zm0-80q134 0 227-93t93-227q0-134-93-227t-227-93q-134 0-227 93t-93 227q0 134 93 227t227 93Zm0-320Z"/>\n</svg>\n';
const onlineSvg = '<!--\n  - SPDX-FileCopyrightText: 2020 Google Inc.\n  - SPDX-License-Identifier: Apache-2.0\n-->\n<svg viewBox="0 -960 960 960" width="24px" height="24px" xmlns="http://www.w3.org/2000/svg">\n	<path\n		fill="var(--user-status-color-online, var(--color-success, #2D7B41))"\n		d="m424-296 282-282-56-56-226 226-114-114-56 56 170 170Zm56 216q-83 0-156-31.5T197-197q-54-54-85.5-127T80-480q0-83 31.5-156T197-763q54-54 127-85.5T480-880q83 0 156 31.5T763-763q54 54 85.5 127T880-480q0 83-31.5 156T763-197q-54 54-127 85.5T480-80Z"/>\n</svg>\n';
register(t51);
register(t11);
function getUserStatusText(status) {
  switch (status) {
    case "away":
      return t("away");
    // TRANSLATORS: User status if the user is currently away from keyboard
    case "busy":
      return t("busy");
    case "dnd":
      return t("do not disturb");
    case "online":
      return t("online");
    case "invisible":
      return t("invisible");
    case "offline":
      return t("offline");
    default:
      return status;
  }
}
const _hoisted_1 = ["aria-hidden", "aria-label", "innerHTML"];
const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "NcUserStatusIcon",
  props: /* @__PURE__ */ mergeModels({
    user: { default: void 0 },
    ariaHidden: { type: [Boolean, String], default: false }
  }, {
    "status": {},
    "statusModifiers": {}
  }),
  emits: ["update:status"],
  setup(__props) {
    const status = useModel(__props, "status");
    const props = __props;
    const isInvisible = computed(() => status.value && ["invisible", "offline"].includes(status.value));
    const ariaLabel = computed(() => status.value && (!props.ariaHidden || props.ariaHidden === "false") ? t("User status: {status}", { status: getUserStatusText(status.value) }) : void 0);
    watch(() => props.user, async (user) => {
      if (!status.value && user && getCapabilities()?.user_status?.enabled) {
        try {
          const { data } = await cancelableClient.get(generateOcsUrl("/apps/user_status/api/v1/statuses/{user}", { user }));
          status.value = data.ocs?.data?.status;
        } catch (error) {
          logger.debug("Error while fetching user status", { error });
        }
      }
    }, { immediate: true });
    const matchSvg = {
      online: onlineSvg,
      away: awaySvg,
      busy: busySvg,
      dnd: dndSvg,
      invisible: invisibleSvg,
      offline: invisibleSvg
    };
    const activeSvg = computed(() => status.value && matchSvg[status.value]);
    return (_ctx, _cache) => {
      return status.value ? (openBlock(), createElementBlock("span", {
        key: 0,
        class: normalizeClass(["user-status-icon", {
          "user-status-icon--invisible": isInvisible.value
        }]),
        "aria-hidden": !ariaLabel.value || void 0,
        "aria-label": ariaLabel.value,
        role: "img",
        innerHTML: activeSvg.value
      }, null, 10, _hoisted_1)) : createCommentVNode("", true);
    };
  }
});
const NcUserStatusIcon = /* @__PURE__ */ _export_sfc(_sfc_main, [["__scopeId", "data-v-881a79fb"]]);
export {
  NcUserStatusIcon as N,
  getUserStatusText as g
};
//# sourceMappingURL=NcUserStatusIcon-XiwrgeCm-DTG2zfIi.chunk.mjs.map
