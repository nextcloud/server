const appName = "nextcloud-ui";
const appVersion = "1.0.0";
const _export_sfc = (sfc, props) => {
  const target = sfc.__vccOpts || sfc;
  for (const [key, val] of props) {
    target[key] = val;
  }
  return target;
};
function loadState(app, key, fallback) {
  const selector = `#initial-state-${app}-${key}`;
  if (window._nc_initial_state?.has(selector)) {
    return window._nc_initial_state.get(selector);
  } else if (!window._nc_initial_state) {
    window._nc_initial_state = /* @__PURE__ */ new Map();
  }
  const elem = document.querySelector(selector);
  if (elem === null) {
    if (fallback !== void 0) {
      return fallback;
    }
    throw new Error(`Could not find initial state ${key} of ${app}`);
  }
  try {
    const parsedValue = JSON.parse(atob(elem.value));
    window._nc_initial_state.set(selector, parsedValue);
    return parsedValue;
  } catch (error) {
    console.error("[@nextcloud/initial-state] Could not parse initial state", { key, app, error });
    if (fallback !== void 0) {
      return fallback;
    }
    throw new Error(`Could not parse initial state ${key} of ${app}`, { cause: error });
  }
}
function getCapabilities() {
  try {
    return loadState("core", "capabilities");
  } catch (error) {
    console.debug("Could not find capabilities initial state fall back to _oc_capabilities");
    if (!("_oc_capabilities" in window)) {
      return {};
    }
    return window["_oc_capabilities"];
  }
}
export {
  _export_sfc as _,
  getCapabilities as g,
  loadState as l
};
//# sourceMappingURL=index-o76qk6sn.chunk.mjs.map
