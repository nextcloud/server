const __vite__mapDeps=(i,m=__vite__mapDeps,d=(m.f||(m.f=[window.OC.filePath('', '', 'dist/SetStatusModal-z4vuOpeT.chunk.mjs'),window.OC.filePath('', '', 'dist/index-C1xmmKTZ-kBgT3zMc.chunk.mjs'),window.OC.filePath('', '', 'dist/preload-helper-xAe3EUYB.chunk.mjs'),window.OC.filePath('', '', 'dist/NcDialog-BG9t4Psg-IlLJVAz0.chunk.mjs'),window.OC.filePath('', '', 'dist/NcModal-DHryP_87-Da4dXMUU.chunk.mjs'),window.OC.filePath('', '', 'dist/ArrowRight-BC77f5L9.chunk.mjs'),window.OC.filePath('', '', 'dist/Web-BOM4en5n.chunk.mjs'),window.OC.filePath('', '', 'dist/translation-DoG5ZELJ-2UfAUX2V.chunk.mjs'),window.OC.filePath('', '', 'dist/index-rAufP352.chunk.mjs'),window.OC.filePath('', '', 'dist/index-o76qk6sn.chunk.mjs'),window.OC.filePath('', '', 'dist/Web-N3OwSN9O.chunk.css'),window.OC.filePath('', '', 'dist/ArrowRight-Ch8zyY_U.chunk.css'),window.OC.filePath('', '', 'dist/NcModal-DHryP_87-B2iEdqc2.chunk.css'),window.OC.filePath('', '', 'dist/TrashCanOutline-DgEtyFGH.chunk.mjs'),window.OC.filePath('', '', 'dist/TrashCanOutline-CWUlo4XY.chunk.css'),window.OC.filePath('', '', 'dist/NcDialog-BG9t4Psg-BSV74Bru.chunk.css'),window.OC.filePath('', '', 'dist/mdi-BGU2G5q5.chunk.mjs'),window.OC.filePath('', '', 'dist/mdi-DZSuYX4-.chunk.css'),window.OC.filePath('', '', 'dist/NcSelect-DLheQ2yp-DVxJhLyT.chunk.mjs'),window.OC.filePath('', '', 'dist/NcSelect-DLheQ2yp-DEY3FLux.chunk.css'),window.OC.filePath('', '', 'dist/NcEmojiPicker-Djc9a0gw-F1kmncT2.chunk.mjs'),window.OC.filePath('', '', 'dist/emoji-BY_D0V5K-BlCul1cD.chunk.mjs'),window.OC.filePath('', '', 'dist/index-DD39fp6M.chunk.mjs'),window.OC.filePath('', '', 'dist/colors-BHGKZFDI-C0-WujoK.chunk.mjs'),window.OC.filePath('', '', 'dist/index-CCanY5eB.chunk.css'),window.OC.filePath('', '', 'dist/NcTextField.vue_vue_type_script_setup_true_lang-BxkYy7wv-Beu3Njvy.chunk.mjs'),window.OC.filePath('', '', 'dist/NcInputField-o5OFv3z6-D-7orWgm.chunk.mjs'),window.OC.filePath('', '', 'dist/NcInputField-o5OFv3z6-B0lNBgr9.chunk.css'),window.OC.filePath('', '', 'dist/NcEmojiPicker-Djc9a0gw-vNKR9S87.chunk.css'),window.OC.filePath('', '', 'dist/NcUserStatusIcon-XiwrgeCm-DTG2zfIi.chunk.mjs'),window.OC.filePath('', '', 'dist/index-D5H5XMHa.chunk.mjs'),window.OC.filePath('', '', 'dist/util-BSOXDoOW.chunk.mjs'),window.OC.filePath('', '', 'dist/NcUserStatusIcon-XiwrgeCm-B3aHoBAd.chunk.css'),window.OC.filePath('', '', 'dist/TrayArrowDown-DVjUGg6-.chunk.mjs'),window.OC.filePath('', '', 'dist/TrayArrowDown-DzNPKSuT.chunk.css'),window.OC.filePath('', '', 'dist/user_status-SetStatusModal-C0b-8Ddi.chunk.css')])))=>i.map(i=>d[i]);
const appName = "nextcloud-ui";
const appVersion = "1.0.0";
import { g as getLoggerBuilder, c as generateOcsUrl, a as getCurrentUser, u as unsubscribe, d as debounce, s as subscribe, f as emit } from "./index-rAufP352.chunk.mjs";
import { aa as effectScope, O as reactive, z as watch, n as computed, r as resolveComponent, o as openBlock, f as createElementBlock, c as createBlock, w as withCtx, x as createVNode, v as normalizeClass, M as withModifiers, h as createCommentVNode, j as createTextVNode, t as toDisplayString, F as Fragment, a as defineAsyncComponent, _ as __vitePreload, e as createApp } from "./preload-helper-xAe3EUYB.chunk.mjs";
import { N as NcButton } from "./ArrowRight-BC77f5L9.chunk.mjs";
import { N as NcListItem } from "./TrayArrowDown-DVjUGg6-.chunk.mjs";
import { N as NcUserStatusIcon } from "./NcUserStatusIcon-XiwrgeCm-DTG2zfIi.chunk.mjs";
import { a as showError } from "./index-C1xmmKTZ-kBgT3zMc.chunk.mjs";
import { t as translate } from "./translation-DoG5ZELJ-2UfAUX2V.chunk.mjs";
import { c as cancelableClient } from "./index-D5H5XMHa.chunk.mjs";
import { _ as _export_sfc, l as loadState } from "./index-o76qk6sn.chunk.mjs";
import { f as formatRelativeTime, k as getFirstDay } from "./NcModal-DHryP_87-Da4dXMUU.chunk.mjs";
import "./Web-BOM4en5n.chunk.mjs";
import "./NcDialog-BG9t4Psg-IlLJVAz0.chunk.mjs";
import "./TrashCanOutline-DgEtyFGH.chunk.mjs";
import "./mdi-BGU2G5q5.chunk.mjs";
import "./util-BSOXDoOW.chunk.mjs";
/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
const logger = getLoggerBuilder().detectLogLevel().setApp("user_status").build();
function getDevtoolsGlobalHook() {
  return getTarget().__VUE_DEVTOOLS_GLOBAL_HOOK__;
}
function getTarget() {
  return typeof navigator !== "undefined" && typeof window !== "undefined" ? window : typeof globalThis !== "undefined" ? globalThis : {};
}
const isProxyAvailable = typeof Proxy === "function";
const HOOK_SETUP = "devtools-plugin:setup";
const HOOK_PLUGIN_SETTINGS_SET = "plugin:settings:set";
let supported;
let perf;
function isPerformanceSupported() {
  var _a;
  if (supported !== void 0) {
    return supported;
  }
  if (typeof window !== "undefined" && window.performance) {
    supported = true;
    perf = window.performance;
  } else if (typeof globalThis !== "undefined" && ((_a = globalThis.perf_hooks) === null || _a === void 0 ? void 0 : _a.performance)) {
    supported = true;
    perf = globalThis.perf_hooks.performance;
  } else {
    supported = false;
  }
  return supported;
}
function now() {
  return isPerformanceSupported() ? perf.now() : Date.now();
}
class ApiProxy {
  constructor(plugin, hook) {
    this.target = null;
    this.targetQueue = [];
    this.onQueue = [];
    this.plugin = plugin;
    this.hook = hook;
    const defaultSettings = {};
    if (plugin.settings) {
      for (const id in plugin.settings) {
        const item = plugin.settings[id];
        defaultSettings[id] = item.defaultValue;
      }
    }
    const localSettingsSaveId = `__vue-devtools-plugin-settings__${plugin.id}`;
    let currentSettings = Object.assign({}, defaultSettings);
    try {
      const raw = localStorage.getItem(localSettingsSaveId);
      const data = JSON.parse(raw);
      Object.assign(currentSettings, data);
    } catch (e) {
    }
    this.fallbacks = {
      getSettings() {
        return currentSettings;
      },
      setSettings(value) {
        try {
          localStorage.setItem(localSettingsSaveId, JSON.stringify(value));
        } catch (e) {
        }
        currentSettings = value;
      },
      now() {
        return now();
      }
    };
    if (hook) {
      hook.on(HOOK_PLUGIN_SETTINGS_SET, (pluginId, value) => {
        if (pluginId === this.plugin.id) {
          this.fallbacks.setSettings(value);
        }
      });
    }
    this.proxiedOn = new Proxy({}, {
      get: (_target, prop) => {
        if (this.target) {
          return this.target.on[prop];
        } else {
          return (...args) => {
            this.onQueue.push({
              method: prop,
              args
            });
          };
        }
      }
    });
    this.proxiedTarget = new Proxy({}, {
      get: (_target, prop) => {
        if (this.target) {
          return this.target[prop];
        } else if (prop === "on") {
          return this.proxiedOn;
        } else if (Object.keys(this.fallbacks).includes(prop)) {
          return (...args) => {
            this.targetQueue.push({
              method: prop,
              args,
              resolve: () => {
              }
            });
            return this.fallbacks[prop](...args);
          };
        } else {
          return (...args) => {
            return new Promise((resolve) => {
              this.targetQueue.push({
                method: prop,
                args,
                resolve
              });
            });
          };
        }
      }
    });
  }
  async setRealTarget(target) {
    this.target = target;
    for (const item of this.onQueue) {
      this.target.on[item.method](...item.args);
    }
    for (const item of this.targetQueue) {
      item.resolve(await this.target[item.method](...item.args));
    }
  }
}
function setupDevtoolsPlugin(pluginDescriptor, setupFn) {
  const descriptor = pluginDescriptor;
  const target = getTarget();
  const hook = getDevtoolsGlobalHook();
  const enableProxy = isProxyAvailable && descriptor.enableEarlyProxy;
  if (hook && (target.__VUE_DEVTOOLS_PLUGIN_API_AVAILABLE__ || !enableProxy)) {
    hook.emit(HOOK_SETUP, pluginDescriptor, setupFn);
  } else {
    const proxy = enableProxy ? new ApiProxy(descriptor, hook) : null;
    const list = target.__VUE_DEVTOOLS_PLUGINS__ = target.__VUE_DEVTOOLS_PLUGINS__ || [];
    list.push({
      pluginDescriptor: descriptor,
      setupFn,
      proxy
    });
    if (proxy) {
      setupFn(proxy.proxiedTarget);
    }
  }
}
/*!
 * vuex v4.1.0
 * (c) 2022 Evan You
 * @license MIT
 */
var storeKey = "store";
function forEachValue(obj, fn) {
  Object.keys(obj).forEach(function(key) {
    return fn(obj[key], key);
  });
}
function isObject(obj) {
  return obj !== null && typeof obj === "object";
}
function isPromise(val) {
  return val && typeof val.then === "function";
}
function assert(condition, msg) {
  if (!condition) {
    throw new Error("[vuex] " + msg);
  }
}
function partial(fn, arg) {
  return function() {
    return fn(arg);
  };
}
function genericSubscribe(fn, subs, options) {
  if (subs.indexOf(fn) < 0) {
    options && options.prepend ? subs.unshift(fn) : subs.push(fn);
  }
  return function() {
    var i = subs.indexOf(fn);
    if (i > -1) {
      subs.splice(i, 1);
    }
  };
}
function resetStore(store2, hot) {
  store2._actions = /* @__PURE__ */ Object.create(null);
  store2._mutations = /* @__PURE__ */ Object.create(null);
  store2._wrappedGetters = /* @__PURE__ */ Object.create(null);
  store2._modulesNamespaceMap = /* @__PURE__ */ Object.create(null);
  var state2 = store2.state;
  installModule(store2, state2, [], store2._modules.root, true);
  resetStoreState(store2, state2, hot);
}
function resetStoreState(store2, state2, hot) {
  var oldState = store2._state;
  var oldScope = store2._scope;
  store2.getters = {};
  store2._makeLocalGettersCache = /* @__PURE__ */ Object.create(null);
  var wrappedGetters = store2._wrappedGetters;
  var computedObj = {};
  var computedCache = {};
  var scope = effectScope(true);
  scope.run(function() {
    forEachValue(wrappedGetters, function(fn, key) {
      computedObj[key] = partial(fn, store2);
      computedCache[key] = computed(function() {
        return computedObj[key]();
      });
      Object.defineProperty(store2.getters, key, {
        get: function() {
          return computedCache[key].value;
        },
        enumerable: true
        // for local getters
      });
    });
  });
  store2._state = reactive({
    data: state2
  });
  store2._scope = scope;
  if (store2.strict) {
    enableStrictMode(store2);
  }
  if (oldState) {
    if (hot) {
      store2._withCommit(function() {
        oldState.data = null;
      });
    }
  }
  if (oldScope) {
    oldScope.stop();
  }
}
function installModule(store2, rootState, path, module, hot) {
  var isRoot = !path.length;
  var namespace = store2._modules.getNamespace(path);
  if (module.namespaced) {
    if (store2._modulesNamespaceMap[namespace] && true) {
      console.error("[vuex] duplicate namespace " + namespace + " for the namespaced module " + path.join("/"));
    }
    store2._modulesNamespaceMap[namespace] = module;
  }
  if (!isRoot && !hot) {
    var parentState = getNestedState(rootState, path.slice(0, -1));
    var moduleName = path[path.length - 1];
    store2._withCommit(function() {
      {
        if (moduleName in parentState) {
          console.warn(
            '[vuex] state field "' + moduleName + '" was overridden by a module with the same name at "' + path.join(".") + '"'
          );
        }
      }
      parentState[moduleName] = module.state;
    });
  }
  var local = module.context = makeLocalContext(store2, namespace, path);
  module.forEachMutation(function(mutation, key) {
    var namespacedType = namespace + key;
    registerMutation(store2, namespacedType, mutation, local);
  });
  module.forEachAction(function(action, key) {
    var type = action.root ? key : namespace + key;
    var handler = action.handler || action;
    registerAction(store2, type, handler, local);
  });
  module.forEachGetter(function(getter, key) {
    var namespacedType = namespace + key;
    registerGetter(store2, namespacedType, getter, local);
  });
  module.forEachChild(function(child, key) {
    installModule(store2, rootState, path.concat(key), child, hot);
  });
}
function makeLocalContext(store2, namespace, path) {
  var noNamespace = namespace === "";
  var local = {
    dispatch: noNamespace ? store2.dispatch : function(_type, _payload, _options) {
      var args = unifyObjectStyle(_type, _payload, _options);
      var payload = args.payload;
      var options = args.options;
      var type = args.type;
      if (!options || !options.root) {
        type = namespace + type;
        if (!store2._actions[type]) {
          console.error("[vuex] unknown local action type: " + args.type + ", global type: " + type);
          return;
        }
      }
      return store2.dispatch(type, payload);
    },
    commit: noNamespace ? store2.commit : function(_type, _payload, _options) {
      var args = unifyObjectStyle(_type, _payload, _options);
      var payload = args.payload;
      var options = args.options;
      var type = args.type;
      if (!options || !options.root) {
        type = namespace + type;
        if (!store2._mutations[type]) {
          console.error("[vuex] unknown local mutation type: " + args.type + ", global type: " + type);
          return;
        }
      }
      store2.commit(type, payload, options);
    }
  };
  Object.defineProperties(local, {
    getters: {
      get: noNamespace ? function() {
        return store2.getters;
      } : function() {
        return makeLocalGetters(store2, namespace);
      }
    },
    state: {
      get: function() {
        return getNestedState(store2.state, path);
      }
    }
  });
  return local;
}
function makeLocalGetters(store2, namespace) {
  if (!store2._makeLocalGettersCache[namespace]) {
    var gettersProxy = {};
    var splitPos = namespace.length;
    Object.keys(store2.getters).forEach(function(type) {
      if (type.slice(0, splitPos) !== namespace) {
        return;
      }
      var localType = type.slice(splitPos);
      Object.defineProperty(gettersProxy, localType, {
        get: function() {
          return store2.getters[type];
        },
        enumerable: true
      });
    });
    store2._makeLocalGettersCache[namespace] = gettersProxy;
  }
  return store2._makeLocalGettersCache[namespace];
}
function registerMutation(store2, type, handler, local) {
  var entry = store2._mutations[type] || (store2._mutations[type] = []);
  entry.push(function wrappedMutationHandler(payload) {
    handler.call(store2, local.state, payload);
  });
}
function registerAction(store2, type, handler, local) {
  var entry = store2._actions[type] || (store2._actions[type] = []);
  entry.push(function wrappedActionHandler(payload) {
    var res = handler.call(store2, {
      dispatch: local.dispatch,
      commit: local.commit,
      getters: local.getters,
      state: local.state,
      rootGetters: store2.getters,
      rootState: store2.state
    }, payload);
    if (!isPromise(res)) {
      res = Promise.resolve(res);
    }
    if (store2._devtoolHook) {
      return res.catch(function(err) {
        store2._devtoolHook.emit("vuex:error", err);
        throw err;
      });
    } else {
      return res;
    }
  });
}
function registerGetter(store2, type, rawGetter, local) {
  if (store2._wrappedGetters[type]) {
    {
      console.error("[vuex] duplicate getter key: " + type);
    }
    return;
  }
  store2._wrappedGetters[type] = function wrappedGetter(store22) {
    return rawGetter(
      local.state,
      // local state
      local.getters,
      // local getters
      store22.state,
      // root state
      store22.getters
      // root getters
    );
  };
}
function enableStrictMode(store2) {
  watch(function() {
    return store2._state.data;
  }, function() {
    {
      assert(store2._committing, "do not mutate vuex store state outside mutation handlers.");
    }
  }, { deep: true, flush: "sync" });
}
function getNestedState(state2, path) {
  return path.reduce(function(state22, key) {
    return state22[key];
  }, state2);
}
function unifyObjectStyle(type, payload, options) {
  if (isObject(type) && type.type) {
    options = payload;
    payload = type;
    type = type.type;
  }
  {
    assert(typeof type === "string", "expects string as the type, but found " + typeof type + ".");
  }
  return { type, payload, options };
}
var LABEL_VUEX_BINDINGS = "vuex bindings";
var MUTATIONS_LAYER_ID = "vuex:mutations";
var ACTIONS_LAYER_ID = "vuex:actions";
var INSPECTOR_ID = "vuex";
var actionId = 0;
function addDevtools(app, store2) {
  setupDevtoolsPlugin(
    {
      id: "org.vuejs.vuex",
      app,
      label: "Vuex",
      homepage: "https://next.vuex.vuejs.org/",
      logo: "https://vuejs.org/images/icons/favicon-96x96.png",
      packageName: "vuex",
      componentStateTypes: [LABEL_VUEX_BINDINGS]
    },
    function(api) {
      api.addTimelineLayer({
        id: MUTATIONS_LAYER_ID,
        label: "Vuex Mutations",
        color: COLOR_LIME_500
      });
      api.addTimelineLayer({
        id: ACTIONS_LAYER_ID,
        label: "Vuex Actions",
        color: COLOR_LIME_500
      });
      api.addInspector({
        id: INSPECTOR_ID,
        label: "Vuex",
        icon: "storage",
        treeFilterPlaceholder: "Filter stores..."
      });
      api.on.getInspectorTree(function(payload) {
        if (payload.app === app && payload.inspectorId === INSPECTOR_ID) {
          if (payload.filter) {
            var nodes = [];
            flattenStoreForInspectorTree(nodes, store2._modules.root, payload.filter, "");
            payload.rootNodes = nodes;
          } else {
            payload.rootNodes = [
              formatStoreForInspectorTree(store2._modules.root, "")
            ];
          }
        }
      });
      api.on.getInspectorState(function(payload) {
        if (payload.app === app && payload.inspectorId === INSPECTOR_ID) {
          var modulePath = payload.nodeId;
          makeLocalGetters(store2, modulePath);
          payload.state = formatStoreForInspectorState(
            getStoreModule(store2._modules, modulePath),
            modulePath === "root" ? store2.getters : store2._makeLocalGettersCache,
            modulePath
          );
        }
      });
      api.on.editInspectorState(function(payload) {
        if (payload.app === app && payload.inspectorId === INSPECTOR_ID) {
          var modulePath = payload.nodeId;
          var path = payload.path;
          if (modulePath !== "root") {
            path = modulePath.split("/").filter(Boolean).concat(path);
          }
          store2._withCommit(function() {
            payload.set(store2._state.data, path, payload.state.value);
          });
        }
      });
      store2.subscribe(function(mutation, state2) {
        var data = {};
        if (mutation.payload) {
          data.payload = mutation.payload;
        }
        data.state = state2;
        api.notifyComponentUpdate();
        api.sendInspectorTree(INSPECTOR_ID);
        api.sendInspectorState(INSPECTOR_ID);
        api.addTimelineEvent({
          layerId: MUTATIONS_LAYER_ID,
          event: {
            time: Date.now(),
            title: mutation.type,
            data
          }
        });
      });
      store2.subscribeAction({
        before: function(action, state2) {
          var data = {};
          if (action.payload) {
            data.payload = action.payload;
          }
          action._id = actionId++;
          action._time = Date.now();
          data.state = state2;
          api.addTimelineEvent({
            layerId: ACTIONS_LAYER_ID,
            event: {
              time: action._time,
              title: action.type,
              groupId: action._id,
              subtitle: "start",
              data
            }
          });
        },
        after: function(action, state2) {
          var data = {};
          var duration = Date.now() - action._time;
          data.duration = {
            _custom: {
              type: "duration",
              display: duration + "ms",
              tooltip: "Action duration",
              value: duration
            }
          };
          if (action.payload) {
            data.payload = action.payload;
          }
          data.state = state2;
          api.addTimelineEvent({
            layerId: ACTIONS_LAYER_ID,
            event: {
              time: Date.now(),
              title: action.type,
              groupId: action._id,
              subtitle: "end",
              data
            }
          });
        }
      });
    }
  );
}
var COLOR_LIME_500 = 8702998;
var COLOR_DARK = 6710886;
var COLOR_WHITE = 16777215;
var TAG_NAMESPACED = {
  label: "namespaced",
  textColor: COLOR_WHITE,
  backgroundColor: COLOR_DARK
};
function extractNameFromPath(path) {
  return path && path !== "root" ? path.split("/").slice(-2, -1)[0] : "Root";
}
function formatStoreForInspectorTree(module, path) {
  return {
    id: path || "root",
    // all modules end with a `/`, we want the last segment only
    // cart/ -> cart
    // nested/cart/ -> cart
    label: extractNameFromPath(path),
    tags: module.namespaced ? [TAG_NAMESPACED] : [],
    children: Object.keys(module._children).map(
      function(moduleName) {
        return formatStoreForInspectorTree(
          module._children[moduleName],
          path + moduleName + "/"
        );
      }
    )
  };
}
function flattenStoreForInspectorTree(result, module, filter, path) {
  if (path.includes(filter)) {
    result.push({
      id: path || "root",
      label: path.endsWith("/") ? path.slice(0, path.length - 1) : path || "Root",
      tags: module.namespaced ? [TAG_NAMESPACED] : []
    });
  }
  Object.keys(module._children).forEach(function(moduleName) {
    flattenStoreForInspectorTree(result, module._children[moduleName], filter, path + moduleName + "/");
  });
}
function formatStoreForInspectorState(module, getters2, path) {
  getters2 = path === "root" ? getters2 : getters2[path];
  var gettersKeys = Object.keys(getters2);
  var storeState = {
    state: Object.keys(module.state).map(function(key) {
      return {
        key,
        editable: true,
        value: module.state[key]
      };
    })
  };
  if (gettersKeys.length) {
    var tree = transformPathsToObjectTree(getters2);
    storeState.getters = Object.keys(tree).map(function(key) {
      return {
        key: key.endsWith("/") ? extractNameFromPath(key) : key,
        editable: false,
        value: canThrow(function() {
          return tree[key];
        })
      };
    });
  }
  return storeState;
}
function transformPathsToObjectTree(getters2) {
  var result = {};
  Object.keys(getters2).forEach(function(key) {
    var path = key.split("/");
    if (path.length > 1) {
      var target = result;
      var leafKey = path.pop();
      path.forEach(function(p) {
        if (!target[p]) {
          target[p] = {
            _custom: {
              value: {},
              display: p,
              tooltip: "Module",
              abstract: true
            }
          };
        }
        target = target[p]._custom.value;
      });
      target[leafKey] = canThrow(function() {
        return getters2[key];
      });
    } else {
      result[key] = canThrow(function() {
        return getters2[key];
      });
    }
  });
  return result;
}
function getStoreModule(moduleMap, path) {
  var names = path.split("/").filter(function(n) {
    return n;
  });
  return names.reduce(
    function(module, moduleName, i) {
      var child = module[moduleName];
      if (!child) {
        throw new Error('Missing module "' + moduleName + '" for path "' + path + '".');
      }
      return i === names.length - 1 ? child : child._children;
    },
    path === "root" ? moduleMap : moduleMap.root._children
  );
}
function canThrow(cb) {
  try {
    return cb();
  } catch (e) {
    return e;
  }
}
var Module = function Module2(rawModule, runtime) {
  this.runtime = runtime;
  this._children = /* @__PURE__ */ Object.create(null);
  this._rawModule = rawModule;
  var rawState = rawModule.state;
  this.state = (typeof rawState === "function" ? rawState() : rawState) || {};
};
var prototypeAccessors$1 = { namespaced: { configurable: true } };
prototypeAccessors$1.namespaced.get = function() {
  return !!this._rawModule.namespaced;
};
Module.prototype.addChild = function addChild(key, module) {
  this._children[key] = module;
};
Module.prototype.removeChild = function removeChild(key) {
  delete this._children[key];
};
Module.prototype.getChild = function getChild(key) {
  return this._children[key];
};
Module.prototype.hasChild = function hasChild(key) {
  return key in this._children;
};
Module.prototype.update = function update(rawModule) {
  this._rawModule.namespaced = rawModule.namespaced;
  if (rawModule.actions) {
    this._rawModule.actions = rawModule.actions;
  }
  if (rawModule.mutations) {
    this._rawModule.mutations = rawModule.mutations;
  }
  if (rawModule.getters) {
    this._rawModule.getters = rawModule.getters;
  }
};
Module.prototype.forEachChild = function forEachChild(fn) {
  forEachValue(this._children, fn);
};
Module.prototype.forEachGetter = function forEachGetter(fn) {
  if (this._rawModule.getters) {
    forEachValue(this._rawModule.getters, fn);
  }
};
Module.prototype.forEachAction = function forEachAction(fn) {
  if (this._rawModule.actions) {
    forEachValue(this._rawModule.actions, fn);
  }
};
Module.prototype.forEachMutation = function forEachMutation(fn) {
  if (this._rawModule.mutations) {
    forEachValue(this._rawModule.mutations, fn);
  }
};
Object.defineProperties(Module.prototype, prototypeAccessors$1);
var ModuleCollection = function ModuleCollection2(rawRootModule) {
  this.register([], rawRootModule, false);
};
ModuleCollection.prototype.get = function get(path) {
  return path.reduce(function(module, key) {
    return module.getChild(key);
  }, this.root);
};
ModuleCollection.prototype.getNamespace = function getNamespace(path) {
  var module = this.root;
  return path.reduce(function(namespace, key) {
    module = module.getChild(key);
    return namespace + (module.namespaced ? key + "/" : "");
  }, "");
};
ModuleCollection.prototype.update = function update$1(rawRootModule) {
  update2([], this.root, rawRootModule);
};
ModuleCollection.prototype.register = function register(path, rawModule, runtime) {
  var this$1$1 = this;
  if (runtime === void 0) runtime = true;
  {
    assertRawModule(path, rawModule);
  }
  var newModule = new Module(rawModule, runtime);
  if (path.length === 0) {
    this.root = newModule;
  } else {
    var parent = this.get(path.slice(0, -1));
    parent.addChild(path[path.length - 1], newModule);
  }
  if (rawModule.modules) {
    forEachValue(rawModule.modules, function(rawChildModule, key) {
      this$1$1.register(path.concat(key), rawChildModule, runtime);
    });
  }
};
ModuleCollection.prototype.unregister = function unregister(path) {
  var parent = this.get(path.slice(0, -1));
  var key = path[path.length - 1];
  var child = parent.getChild(key);
  if (!child) {
    {
      console.warn(
        "[vuex] trying to unregister module '" + key + "', which is not registered"
      );
    }
    return;
  }
  if (!child.runtime) {
    return;
  }
  parent.removeChild(key);
};
ModuleCollection.prototype.isRegistered = function isRegistered(path) {
  var parent = this.get(path.slice(0, -1));
  var key = path[path.length - 1];
  if (parent) {
    return parent.hasChild(key);
  }
  return false;
};
function update2(path, targetModule, newModule) {
  {
    assertRawModule(path, newModule);
  }
  targetModule.update(newModule);
  if (newModule.modules) {
    for (var key in newModule.modules) {
      if (!targetModule.getChild(key)) {
        {
          console.warn(
            "[vuex] trying to add a new module '" + key + "' on hot reloading, manual reload is needed"
          );
        }
        return;
      }
      update2(
        path.concat(key),
        targetModule.getChild(key),
        newModule.modules[key]
      );
    }
  }
}
var functionAssert = {
  assert: function(value) {
    return typeof value === "function";
  },
  expected: "function"
};
var objectAssert = {
  assert: function(value) {
    return typeof value === "function" || typeof value === "object" && typeof value.handler === "function";
  },
  expected: 'function or object with "handler" function'
};
var assertTypes = {
  getters: functionAssert,
  mutations: functionAssert,
  actions: objectAssert
};
function assertRawModule(path, rawModule) {
  Object.keys(assertTypes).forEach(function(key) {
    if (!rawModule[key]) {
      return;
    }
    var assertOptions = assertTypes[key];
    forEachValue(rawModule[key], function(value, type) {
      assert(
        assertOptions.assert(value),
        makeAssertionMessage(path, key, type, value, assertOptions.expected)
      );
    });
  });
}
function makeAssertionMessage(path, key, type, value, expected) {
  var buf = key + " should be " + expected + ' but "' + key + "." + type + '"';
  if (path.length > 0) {
    buf += ' in module "' + path.join(".") + '"';
  }
  buf += " is " + JSON.stringify(value) + ".";
  return buf;
}
function createStore(options) {
  return new Store(options);
}
var Store = function Store2(options) {
  var this$1$1 = this;
  if (options === void 0) options = {};
  {
    assert(typeof Promise !== "undefined", "vuex requires a Promise polyfill in this browser.");
    assert(this instanceof Store2, "store must be called with the new operator.");
  }
  var plugins = options.plugins;
  if (plugins === void 0) plugins = [];
  var strict = options.strict;
  if (strict === void 0) strict = false;
  var devtools = options.devtools;
  this._committing = false;
  this._actions = /* @__PURE__ */ Object.create(null);
  this._actionSubscribers = [];
  this._mutations = /* @__PURE__ */ Object.create(null);
  this._wrappedGetters = /* @__PURE__ */ Object.create(null);
  this._modules = new ModuleCollection(options);
  this._modulesNamespaceMap = /* @__PURE__ */ Object.create(null);
  this._subscribers = [];
  this._makeLocalGettersCache = /* @__PURE__ */ Object.create(null);
  this._scope = null;
  this._devtools = devtools;
  var store2 = this;
  var ref = this;
  var dispatch2 = ref.dispatch;
  var commit2 = ref.commit;
  this.dispatch = function boundDispatch(type, payload) {
    return dispatch2.call(store2, type, payload);
  };
  this.commit = function boundCommit(type, payload, options2) {
    return commit2.call(store2, type, payload, options2);
  };
  this.strict = strict;
  var state2 = this._modules.root.state;
  installModule(this, state2, [], this._modules.root);
  resetStoreState(this, state2);
  plugins.forEach(function(plugin) {
    return plugin(this$1$1);
  });
};
var prototypeAccessors = { state: { configurable: true } };
Store.prototype.install = function install(app, injectKey) {
  app.provide(injectKey || storeKey, this);
  app.config.globalProperties.$store = this;
  var useDevtools = this._devtools !== void 0 ? this._devtools : true;
  if (useDevtools) {
    addDevtools(app, this);
  }
};
prototypeAccessors.state.get = function() {
  return this._state.data;
};
prototypeAccessors.state.set = function(v) {
  {
    assert(false, "use store.replaceState() to explicit replace store state.");
  }
};
Store.prototype.commit = function commit(_type, _payload, _options) {
  var this$1$1 = this;
  var ref = unifyObjectStyle(_type, _payload, _options);
  var type = ref.type;
  var payload = ref.payload;
  var options = ref.options;
  var mutation = { type, payload };
  var entry = this._mutations[type];
  if (!entry) {
    {
      console.error("[vuex] unknown mutation type: " + type);
    }
    return;
  }
  this._withCommit(function() {
    entry.forEach(function commitIterator(handler) {
      handler(payload);
    });
  });
  this._subscribers.slice().forEach(function(sub) {
    return sub(mutation, this$1$1.state);
  });
  if (options && options.silent) {
    console.warn(
      "[vuex] mutation type: " + type + ". Silent option has been removed. Use the filter functionality in the vue-devtools"
    );
  }
};
Store.prototype.dispatch = function dispatch(_type, _payload) {
  var this$1$1 = this;
  var ref = unifyObjectStyle(_type, _payload);
  var type = ref.type;
  var payload = ref.payload;
  var action = { type, payload };
  var entry = this._actions[type];
  if (!entry) {
    {
      console.error("[vuex] unknown action type: " + type);
    }
    return;
  }
  try {
    this._actionSubscribers.slice().filter(function(sub) {
      return sub.before;
    }).forEach(function(sub) {
      return sub.before(action, this$1$1.state);
    });
  } catch (e) {
    {
      console.warn("[vuex] error in before action subscribers: ");
      console.error(e);
    }
  }
  var result = entry.length > 1 ? Promise.all(entry.map(function(handler) {
    return handler(payload);
  })) : entry[0](payload);
  return new Promise(function(resolve, reject) {
    result.then(function(res) {
      try {
        this$1$1._actionSubscribers.filter(function(sub) {
          return sub.after;
        }).forEach(function(sub) {
          return sub.after(action, this$1$1.state);
        });
      } catch (e) {
        {
          console.warn("[vuex] error in after action subscribers: ");
          console.error(e);
        }
      }
      resolve(res);
    }, function(error) {
      try {
        this$1$1._actionSubscribers.filter(function(sub) {
          return sub.error;
        }).forEach(function(sub) {
          return sub.error(action, this$1$1.state, error);
        });
      } catch (e) {
        {
          console.warn("[vuex] error in error action subscribers: ");
          console.error(e);
        }
      }
      reject(error);
    });
  });
};
Store.prototype.subscribe = function subscribe2(fn, options) {
  return genericSubscribe(fn, this._subscribers, options);
};
Store.prototype.subscribeAction = function subscribeAction(fn, options) {
  var subs = typeof fn === "function" ? { before: fn } : fn;
  return genericSubscribe(subs, this._actionSubscribers, options);
};
Store.prototype.watch = function watch$1(getter, cb, options) {
  var this$1$1 = this;
  {
    assert(typeof getter === "function", "store.watch only accepts a function.");
  }
  return watch(function() {
    return getter(this$1$1.state, this$1$1.getters);
  }, cb, Object.assign({}, options));
};
Store.prototype.replaceState = function replaceState(state2) {
  var this$1$1 = this;
  this._withCommit(function() {
    this$1$1._state.data = state2;
  });
};
Store.prototype.registerModule = function registerModule(path, rawModule, options) {
  if (options === void 0) options = {};
  if (typeof path === "string") {
    path = [path];
  }
  {
    assert(Array.isArray(path), "module path must be a string or an Array.");
    assert(path.length > 0, "cannot register the root module by using registerModule.");
  }
  this._modules.register(path, rawModule);
  installModule(this, this.state, path, this._modules.get(path), options.preserveState);
  resetStoreState(this, this.state);
};
Store.prototype.unregisterModule = function unregisterModule(path) {
  var this$1$1 = this;
  if (typeof path === "string") {
    path = [path];
  }
  {
    assert(Array.isArray(path), "module path must be a string or an Array.");
  }
  this._modules.unregister(path);
  this._withCommit(function() {
    var parentState = getNestedState(this$1$1.state, path.slice(0, -1));
    delete parentState[path[path.length - 1]];
  });
  resetStore(this);
};
Store.prototype.hasModule = function hasModule(path) {
  if (typeof path === "string") {
    path = [path];
  }
  {
    assert(Array.isArray(path), "module path must be a string or an Array.");
  }
  return this._modules.isRegistered(path);
};
Store.prototype.hotUpdate = function hotUpdate(newOptions) {
  this._modules.update(newOptions);
  resetStore(this, true);
};
Store.prototype._withCommit = function _withCommit(fn) {
  var committing = this._committing;
  this._committing = true;
  fn();
  this._committing = committing;
};
Object.defineProperties(Store.prototype, prototypeAccessors);
var mapState = normalizeNamespace(function(namespace, states) {
  var res = {};
  if (!isValidMap(states)) {
    console.error("[vuex] mapState: mapper parameter must be either an Array or an Object");
  }
  normalizeMap(states).forEach(function(ref) {
    var key = ref.key;
    var val = ref.val;
    res[key] = function mappedState() {
      var state2 = this.$store.state;
      var getters2 = this.$store.getters;
      if (namespace) {
        var module = getModuleByNamespace(this.$store, "mapState", namespace);
        if (!module) {
          return;
        }
        state2 = module.context.state;
        getters2 = module.context.getters;
      }
      return typeof val === "function" ? val.call(this, state2, getters2) : state2[val];
    };
    res[key].vuex = true;
  });
  return res;
});
var mapGetters = normalizeNamespace(function(namespace, getters2) {
  var res = {};
  if (!isValidMap(getters2)) {
    console.error("[vuex] mapGetters: mapper parameter must be either an Array or an Object");
  }
  normalizeMap(getters2).forEach(function(ref) {
    var key = ref.key;
    var val = ref.val;
    val = namespace + val;
    res[key] = function mappedGetter() {
      if (namespace && !getModuleByNamespace(this.$store, "mapGetters", namespace)) {
        return;
      }
      if (!(val in this.$store.getters)) {
        console.error("[vuex] unknown getter: " + val);
        return;
      }
      return this.$store.getters[val];
    };
    res[key].vuex = true;
  });
  return res;
});
function normalizeMap(map) {
  if (!isValidMap(map)) {
    return [];
  }
  return Array.isArray(map) ? map.map(function(key) {
    return { key, val: key };
  }) : Object.keys(map).map(function(key) {
    return { key, val: map[key] };
  });
}
function isValidMap(map) {
  return Array.isArray(map) || isObject(map);
}
function normalizeNamespace(fn) {
  return function(namespace, map) {
    if (typeof namespace !== "string") {
      map = namespace;
      namespace = "";
    } else if (namespace.charAt(namespace.length - 1) !== "/") {
      namespace += "/";
    }
    return fn(namespace, map);
  };
}
function getModuleByNamespace(store2, helper, namespace) {
  var module = store2._modulesNamespaceMap[namespace];
  if (!module) {
    console.error("[vuex] module namespace not found in " + helper + "(): " + namespace);
  }
  return module;
}
const OnlineStatusMixin = {
  computed: {
    ...mapState({
      statusType: (state2) => state2.userStatus.status,
      statusIsUserDefined: (state2) => state2.userStatus.statusIsUserDefined,
      customIcon: (state2) => state2.userStatus.icon,
      customMessage: (state2) => state2.userStatus.message
    }),
    /**
     * The message displayed in the top right corner
     *
     * @return {string}
     */
    visibleMessage() {
      if (this.customIcon && this.customMessage) {
        return `${this.customIcon} ${this.customMessage}`;
      }
      if (this.customMessage) {
        return this.customMessage;
      }
      if (this.statusIsUserDefined) {
        switch (this.statusType) {
          case "online":
            return translate("user_status", "Online");
          case "away":
            return translate("user_status", "Away");
          case "busy":
            return translate("user_status", "Busy");
          case "dnd":
            return translate("user_status", "Do not disturb");
          case "invisible":
            return translate("user_status", "Invisible");
          case "offline":
            return translate("user_status", "Offline");
        }
      }
      return translate("user_status", "Set status");
    }
  },
  methods: {
    /**
     * Changes the user-status
     *
     * @param {string} statusType (online / away / dnd / invisible)
     */
    async changeStatus(statusType) {
      try {
        await this.$store.dispatch("setStatus", { statusType });
      } catch (err) {
        showError(translate("user_status", "There was an error saving the new status"));
        logger.debug(err);
      }
    }
  }
};
async function sendHeartbeat(isAway) {
  const url = generateOcsUrl("apps/user_status/api/v1/heartbeat?format=json");
  const response = await cancelableClient.put(url, {
    status: isAway ? "away" : "online"
  });
  return response.data.ocs.data;
}
const userStatusMenuItem = "_userStatusMenuItem_1rva6_1";
const userStatusIcon = "_userStatusIcon_1rva6_6";
const style0 = {
  userStatusMenuItem,
  userStatusIcon
};
const _sfc_main = {
  name: "UserStatus",
  components: {
    NcButton,
    NcListItem,
    NcUserStatusIcon,
    SetStatusModal: defineAsyncComponent(() => __vitePreload(() => import("./SetStatusModal-z4vuOpeT.chunk.mjs"), true ? __vite__mapDeps([0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,35]) : void 0, import.meta.url))
  },
  mixins: [OnlineStatusMixin],
  props: {
    /**
     * Whether the component should be rendered as a Dashboard Status or a User Menu Entries
     * true = Dashboard Status
     * false = User Menu Entries
     */
    inline: {
      type: Boolean,
      default: false
    }
  },
  data() {
    return {
      heartbeatInterval: null,
      isAway: false,
      isModalOpen: false,
      mouseMoveListener: null,
      setAwayTimeout: null
    };
  },
  /**
   * Loads the current user's status from initial state
   * and stores it in Vuex
   */
  mounted() {
    this.$store.dispatch("loadStatusFromInitialState");
    if (OC.config.session_keepalive) {
      this.heartbeatInterval = setInterval(this._backgroundHeartbeat.bind(this), 1e3 * 60 * 5);
      this.setAwayTimeout = () => {
        this.isAway = true;
      };
      this.mouseMoveListener = debounce(() => {
        const wasAway = this.isAway;
        this.isAway = false;
        clearTimeout(this.setAwayTimeout);
        setTimeout(this.setAwayTimeout, 1e3 * 60 * 2);
        if (wasAway) {
          this._backgroundHeartbeat();
        }
      }, 1e3 * 2, { immediate: true });
      window.addEventListener("mousemove", this.mouseMoveListener, {
        capture: true,
        passive: true
      });
      this._backgroundHeartbeat();
    }
    subscribe("user_status:status.updated", this.handleUserStatusUpdated);
  },
  /**
   * Some housekeeping before destroying the component
   */
  beforeUnmount() {
    window.removeEventListener("mouseMove", this.mouseMoveListener);
    clearInterval(this.heartbeatInterval);
    unsubscribe("user_status:status.updated", this.handleUserStatusUpdated);
  },
  methods: {
    /**
     * Opens the modal to set a custom status
     */
    openModal() {
      this.isModalOpen = true;
    },
    /**
     * Closes the modal
     */
    closeModal() {
      this.isModalOpen = false;
    },
    /**
     * Sends the status heartbeat to the server
     *
     * @return {Promise<void>}
     * @private
     */
    async _backgroundHeartbeat() {
      try {
        const status = await sendHeartbeat(this.isAway);
        if (status?.userId) {
          this.$store.dispatch("setStatusFromHeartbeat", status);
        } else {
          await this.$store.dispatch("reFetchStatusFromServer");
        }
      } catch (error) {
        logger.debug("Failed sending heartbeat, got: " + error.response?.status);
      }
    },
    handleUserStatusUpdated(state2) {
      if (getCurrentUser()?.uid === state2.userId) {
        this.$store.dispatch("setStatusFromObject", {
          status: state2.status,
          icon: state2.icon,
          message: state2.message
        });
      }
    }
  }
};
const _hoisted_1 = { key: 1 };
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_NcUserStatusIcon = resolveComponent("NcUserStatusIcon");
  const _component_NcListItem = resolveComponent("NcListItem");
  const _component_NcButton = resolveComponent("NcButton");
  const _component_SetStatusModal = resolveComponent("SetStatusModal");
  return openBlock(), createElementBlock(
    Fragment,
    null,
    [
      !$props.inline ? (openBlock(), createBlock(_component_NcListItem, {
        key: 0,
        class: normalizeClass(_ctx.$style.userStatusMenuItem),
        compact: "",
        name: _ctx.visibleMessage,
        onClick: withModifiers($options.openModal, ["stop"])
      }, {
        icon: withCtx(() => [
          createVNode(_component_NcUserStatusIcon, {
            class: normalizeClass(_ctx.$style.userStatusIcon),
            status: _ctx.statusType,
            "aria-hidden": "true"
          }, null, 8, ["class", "status"])
        ]),
        _: 1
        /* STABLE */
      }, 8, ["class", "name", "onClick"])) : (openBlock(), createElementBlock("div", _hoisted_1, [
        createCommentVNode(" Dashboard Status "),
        createVNode(_component_NcButton, {
          onClick: withModifiers($options.openModal, ["stop"])
        }, {
          icon: withCtx(() => [
            createVNode(_component_NcUserStatusIcon, {
              class: normalizeClass(_ctx.$style.userStatusIcon),
              status: _ctx.statusType,
              "aria-hidden": "true"
            }, null, 8, ["class", "status"])
          ]),
          default: withCtx(() => [
            createTextVNode(
              " " + toDisplayString(_ctx.visibleMessage),
              1
              /* TEXT */
            )
          ]),
          _: 1
          /* STABLE */
        }, 8, ["onClick"])
      ])),
      createCommentVNode(" Status management modal "),
      $data.isModalOpen ? (openBlock(), createBlock(_component_SetStatusModal, {
        key: 2,
        inline: $props.inline,
        onClose: $options.closeModal
      }, null, 8, ["inline", "onClose"])) : createCommentVNode("v-if", true)
    ],
    64
    /* STABLE_FRAGMENT */
  );
}
const cssModules = {
  "$style": style0
};
const UserStatus = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render], ["__cssModules", cssModules], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/build/frontend/apps/user_status/src/UserStatus.vue"]]);
async function fetchAllPredefinedStatuses() {
  const url = generateOcsUrl("apps/user_status/api/v1/predefined_statuses?format=json");
  const response = await cancelableClient.get(url);
  return response.data.ocs.data;
}
const state$2 = () => ({
  predefinedStatuses: []
});
const mutations$2 = {
  /**
   * Adds a predefined status to the state
   *
   * @param {object} state The Vuex state
   * @param {object} status The status to add
   */
  addPredefinedStatus(state2, status) {
    state2.predefinedStatuses = [...state2.predefinedStatuses, status];
  }
};
const getters$2 = {
  statusesHaveLoaded(state2) {
    return state2.predefinedStatuses.length > 0;
  }
};
const actions$2 = {
  /**
   * Loads all predefined statuses from the server
   *
   * @param {object} vuex The Vuex components
   * @param {Function} vuex.commit The Vuex commit function
   * @param {object} vuex.state -
   */
  async loadAllPredefinedStatuses({ state: state2, commit: commit2 }) {
    if (state2.predefinedStatuses.length > 0) {
      return;
    }
    const statuses = await fetchAllPredefinedStatuses();
    for (const status of statuses) {
      commit2("addPredefinedStatus", status);
    }
  }
};
const predefinedStatuses = { state: state$2, mutations: mutations$2, getters: getters$2, actions: actions$2 };
async function fetchCurrentStatus() {
  const url = generateOcsUrl("apps/user_status/api/v1/user_status");
  const response = await cancelableClient.get(url);
  return response.data.ocs.data;
}
async function fetchBackupStatus(userId) {
  const url = generateOcsUrl("apps/user_status/api/v1/statuses/{userId}", { userId: "_" + userId });
  const response = await cancelableClient.get(url);
  return response.data.ocs.data;
}
async function setStatus(statusType) {
  const url = generateOcsUrl("apps/user_status/api/v1/user_status/status");
  await cancelableClient.put(url, {
    statusType
  });
}
async function setPredefinedMessage(messageId, clearAt = null) {
  const url = generateOcsUrl("apps/user_status/api/v1/user_status/message/predefined?format=json");
  await cancelableClient.put(url, {
    messageId,
    clearAt
  });
}
async function setCustomMessage(message, statusIcon = null, clearAt = null) {
  const url = generateOcsUrl("apps/user_status/api/v1/user_status/message/custom?format=json");
  await cancelableClient.put(url, {
    message,
    statusIcon,
    clearAt
  });
}
async function clearMessage() {
  const url = generateOcsUrl("apps/user_status/api/v1/user_status/message?format=json");
  await cancelableClient.delete(url);
}
async function revertToBackupStatus(messageId) {
  const url = generateOcsUrl("apps/user_status/api/v1/user_status/revert/{messageId}", { messageId });
  const response = await cancelableClient.delete(url);
  return response.data.ocs.data;
}
const state$1 = () => ({
  // Status (online / away / dnd / invisible / offline)
  status: null,
  // Whether the status is user-defined
  statusIsUserDefined: null,
  // A custom message set by the user
  message: null,
  // The icon selected by the user
  icon: null,
  // When to automatically clean the status
  clearAt: null,
  // Whether the message is predefined
  // (and can automatically be translated by Nextcloud)
  messageIsPredefined: null,
  // The id of the message in case it's predefined
  messageId: null
});
const mutations$1 = {
  /**
   * Loads the status from initial state
   *
   * @param {object} state The Vuex state
   * @param {object} data The destructuring object
   * @param {string} data.status The status type
   * @param {boolean} data.statusIsUserDefined Whether or not this status is user-defined
   * @param {string} data.message The message
   * @param {string} data.icon The icon
   * @param {number} data.clearAt When to automatically clear the status
   * @param {boolean} data.messageIsPredefined Whether or not the message is predefined
   * @param {string} data.messageId The id of the predefined message
   */
  loadBackupStatusFromServer(state2, { status, statusIsUserDefined, message, icon, clearAt, messageIsPredefined, messageId }) {
    state2.status = status;
    state2.message = message;
    state2.icon = icon;
    if (typeof statusIsUserDefined !== "undefined") {
      state2.statusIsUserDefined = statusIsUserDefined;
    }
    if (typeof clearAt !== "undefined") {
      state2.clearAt = clearAt;
    }
    if (typeof messageIsPredefined !== "undefined") {
      state2.messageIsPredefined = messageIsPredefined;
    }
    if (typeof messageId !== "undefined") {
      state2.messageId = messageId;
    }
  }
};
const getters$1 = {};
const actions$1 = {
  /**
   * Re-fetches the status from the server
   *
   * @param {object} vuex The Vuex destructuring object
   * @param {Function} vuex.commit The Vuex commit function
   * @return {Promise<void>}
   */
  async fetchBackupFromServer({ commit: commit2 }) {
    try {
      const status = await fetchBackupStatus(getCurrentUser()?.uid);
      commit2("loadBackupStatusFromServer", status);
    } catch {
    }
  },
  async revertBackupFromServer({ commit: commit2 }, { messageId }) {
    const status = await revertToBackupStatus(messageId);
    if (status) {
      commit2("loadBackupStatusFromServer", {});
      commit2("loadStatusFromServer", status);
      emit("user_status:status.updated", {
        status: status.status,
        message: status.message,
        icon: status.icon,
        clearAt: status.clearAt,
        userId: getCurrentUser()?.uid
      });
    }
  }
};
const userBackupStatus = { state: state$1, mutations: mutations$1, getters: getters$1, actions: actions$1 };
function dateFactory() {
  return /* @__PURE__ */ new Date();
}
function getTimestampForClearAt(clearAt) {
  if (clearAt === null) {
    return null;
  }
  const date = dateFactory();
  if (clearAt.type === "period") {
    date.setSeconds(date.getSeconds() + clearAt.time);
    return Math.floor(date.getTime() / 1e3);
  }
  if (clearAt.type === "end-of") {
    switch (clearAt.time) {
      case "day":
        return Math.floor(getEndOfDay(date).getTime() / 1e3);
      case "week":
        return Math.floor(getEndOfWeek(date).getTime() / 1e3);
    }
  }
  if (clearAt.type === "_time") {
    return clearAt.time;
  }
  return null;
}
function clearAtFormat(clearAt) {
  if (clearAt === null) {
    return translate("user_status", "Don't clear");
  }
  if (clearAt.type === "end-of") {
    switch (clearAt.time) {
      case "day":
        return translate("user_status", "Today");
      case "week":
        return translate("user_status", "This week");
      default:
        return null;
    }
  }
  if (clearAt.type === "period") {
    return formatRelativeTime(Date.now() + clearAt.time * 1e3);
  }
  if (clearAt.type === "_time") {
    return formatRelativeTime(clearAt.time * 1e3);
  }
  return null;
}
function getEndOfDay(date) {
  const endOfDay = new Date(date);
  endOfDay.setHours(23, 59, 59, 999);
  return endOfDay;
}
function getEndOfWeek(date) {
  const endOfWeek = getEndOfDay(date);
  endOfWeek.setDate(date.getDate() + (getFirstDay() - 1 - endOfWeek.getDay() + 7) % 7);
  return endOfWeek;
}
const state = () => ({
  // Status (online / away / dnd / invisible / offline)
  status: null,
  // Whether the status is user-defined
  statusIsUserDefined: null,
  // A custom message set by the user
  message: null,
  // The icon selected by the user
  icon: null,
  // When to automatically clean the status
  clearAt: null,
  // Whether the message is predefined
  // (and can automatically be translated by Nextcloud)
  messageIsPredefined: null,
  // The id of the message in case it's predefined
  messageId: null
});
const mutations = {
  /**
   * Sets a new status
   *
   * @param {object} state The Vuex state
   * @param {object} data The destructuring object
   * @param {string} data.statusType The new status type
   */
  setStatus(state2, { statusType }) {
    state2.status = statusType;
    state2.statusIsUserDefined = true;
  },
  /**
   * Sets a message using a predefined message
   *
   * @param {object} state The Vuex state
   * @param {object} data The destructuring object
   * @param {string} data.messageId The messageId
   * @param {number | null} data.clearAt When to automatically clear the status
   * @param {string} data.message The message
   * @param {string} data.icon The icon
   */
  setPredefinedMessage(state2, { messageId, clearAt, message, icon }) {
    state2.messageId = messageId;
    state2.messageIsPredefined = true;
    state2.message = message;
    state2.icon = icon;
    state2.clearAt = clearAt;
  },
  /**
   * Sets a custom message
   *
   * @param {object} state The Vuex state
   * @param {object} data The destructuring object
   * @param {string} data.message The message
   * @param {string} data.icon The icon
   * @param {number} data.clearAt When to automatically clear the status
   */
  setCustomMessage(state2, { message, icon, clearAt }) {
    state2.messageId = null;
    state2.messageIsPredefined = false;
    state2.message = message;
    state2.icon = icon;
    state2.clearAt = clearAt;
  },
  /**
   * Clears the status
   *
   * @param {object} state The Vuex state
   */
  clearMessage(state2) {
    state2.messageId = null;
    state2.messageIsPredefined = false;
    state2.message = null;
    state2.icon = null;
    state2.clearAt = null;
  },
  /**
   * Loads the status from initial state
   *
   * @param {object} state The Vuex state
   * @param {object} data The destructuring object
   * @param {string} data.status The status type
   * @param {boolean} data.statusIsUserDefined Whether or not this status is user-defined
   * @param {string} data.message The message
   * @param {string} data.icon The icon
   * @param {number} data.clearAt When to automatically clear the status
   * @param {boolean} data.messageIsPredefined Whether or not the message is predefined
   * @param {string} data.messageId The id of the predefined message
   */
  loadStatusFromServer(state2, { status, statusIsUserDefined, message, icon, clearAt, messageIsPredefined, messageId }) {
    state2.status = status;
    state2.message = message;
    state2.icon = icon;
    if (typeof statusIsUserDefined !== "undefined") {
      state2.statusIsUserDefined = statusIsUserDefined;
    }
    if (typeof clearAt !== "undefined") {
      state2.clearAt = clearAt;
    }
    if (typeof messageIsPredefined !== "undefined") {
      state2.messageIsPredefined = messageIsPredefined;
    }
    if (typeof messageId !== "undefined") {
      state2.messageId = messageId;
    }
  }
};
const getters = {};
const actions = {
  /**
   * Sets a new status
   *
   * @param {object} vuex The Vuex destructuring object
   * @param {Function} vuex.commit The Vuex commit function
   * @param {object} vuex.state The Vuex state object
   * @param {object} data The data destructuring object
   * @param {string} data.statusType The new status type
   * @return {Promise<void>}
   */
  async setStatus({ commit: commit2, state: state2 }, { statusType }) {
    await setStatus(statusType);
    commit2("setStatus", { statusType });
    emit("user_status:status.updated", {
      status: state2.status,
      message: state2.message,
      icon: state2.icon,
      clearAt: state2.clearAt,
      userId: getCurrentUser()?.uid
    });
  },
  /**
   * Update status from 'user_status:status.updated' update.
   * This doesn't trigger another 'user_status:status.updated'
   * event.
   *
   * @param {object} vuex The Vuex destructuring object
   * @param {Function} vuex.commit The Vuex commit function
   * @param {object} vuex.state The Vuex state object
   * @param {string} status The new status
   * @return {Promise<void>}
   */
  async setStatusFromObject({ commit: commit2 }, status) {
    commit2("loadStatusFromServer", status);
  },
  /**
   * Sets a message using a predefined message
   *
   * @param {object} vuex The Vuex destructuring object
   * @param {Function} vuex.commit The Vuex commit function
   * @param {object} vuex.state The Vuex state object
   * @param {object} vuex.rootState The Vuex root state
   * @param {object} data The data destructuring object
   * @param {string} data.messageId The messageId
   * @param {object | null} data.clearAt When to automatically clear the status
   * @return {Promise<void>}
   */
  async setPredefinedMessage({ commit: commit2, rootState, state: state2 }, { messageId, clearAt }) {
    const resolvedClearAt = getTimestampForClearAt(clearAt);
    await setPredefinedMessage(messageId, resolvedClearAt);
    const status = rootState.predefinedStatuses.predefinedStatuses.find((status2) => status2.id === messageId);
    const { message, icon } = status;
    commit2("setPredefinedMessage", { messageId, clearAt: resolvedClearAt, message, icon });
    emit("user_status:status.updated", {
      status: state2.status,
      message: state2.message,
      icon: state2.icon,
      clearAt: state2.clearAt,
      userId: getCurrentUser()?.uid
    });
  },
  /**
   * Sets a custom message
   *
   * @param {object} vuex The Vuex destructuring object
   * @param {Function} vuex.commit The Vuex commit function
   * @param {object} vuex.state The Vuex state object
   * @param {object} data The data destructuring object
   * @param {string} data.message The message
   * @param {string} data.icon The icon
   * @param {object | null} data.clearAt When to automatically clear the status
   * @return {Promise<void>}
   */
  async setCustomMessage({ commit: commit2, state: state2 }, { message, icon, clearAt }) {
    const resolvedClearAt = getTimestampForClearAt(clearAt);
    await setCustomMessage(message, icon, resolvedClearAt);
    commit2("setCustomMessage", { message, icon, clearAt: resolvedClearAt });
    emit("user_status:status.updated", {
      status: state2.status,
      message: state2.message,
      icon: state2.icon,
      clearAt: state2.clearAt,
      userId: getCurrentUser()?.uid
    });
  },
  /**
   * Clears the status
   *
   * @param {object} vuex The Vuex destructuring object
   * @param {Function} vuex.commit The Vuex commit function
   * @param {object} vuex.state The Vuex state object
   * @return {Promise<void>}
   */
  async clearMessage({ commit: commit2, state: state2 }) {
    await clearMessage();
    commit2("clearMessage");
    emit("user_status:status.updated", {
      status: state2.status,
      message: state2.message,
      icon: state2.icon,
      clearAt: state2.clearAt,
      userId: getCurrentUser()?.uid
    });
  },
  /**
   * Re-fetches the status from the server
   *
   * @param {object} vuex The Vuex destructuring object
   * @param {Function} vuex.commit The Vuex commit function
   * @return {Promise<void>}
   */
  async reFetchStatusFromServer({ commit: commit2 }) {
    const status = await fetchCurrentStatus();
    commit2("loadStatusFromServer", status);
  },
  /**
   * Stores the status we got in the reply of the heartbeat
   *
   * @param {object} vuex The Vuex destructuring object
   * @param {Function} vuex.commit The Vuex commit function
   * @param {object} status The data destructuring object
   * @param {string} status.status The status type
   * @param {boolean} status.statusIsUserDefined Whether or not this status is user-defined
   * @param {string} status.message The message
   * @param {string} status.icon The icon
   * @param {number} status.clearAt When to automatically clear the status
   * @param {boolean} status.messageIsPredefined Whether or not the message is predefined
   * @param {string} status.messageId The id of the predefined message
   * @return {Promise<void>}
   */
  async setStatusFromHeartbeat({ commit: commit2 }, status) {
    commit2("loadStatusFromServer", status);
  },
  /**
   * Loads the server from the initial state
   *
   * @param {object} vuex The Vuex destructuring object
   * @param {Function} vuex.commit The Vuex commit function
   */
  loadStatusFromInitialState({ commit: commit2 }) {
    const status = loadState("user_status", "status");
    commit2("loadStatusFromServer", status);
  }
};
const userStatus = { state, mutations, getters, actions };
const store = createStore({
  modules: {
    predefinedStatuses,
    userStatus,
    userBackupStatus
  },
  strict: true
});
const mountPoint = document.getElementById("user_status-menu-entry");
function mountMenuEntry() {
  const mountPoint2 = document.getElementById("user_status-menu-entry");
  const transparentMountPoint = document.createElement("div");
  transparentMountPoint.style.display = "contents";
  mountPoint2.replaceWith(transparentMountPoint);
  createApp(UserStatus).use(store).mount(transparentMountPoint);
}
if (mountPoint) {
  mountMenuEntry();
} else {
  subscribe("core:user-menu:mounted", mountMenuEntry);
}
document.addEventListener("DOMContentLoaded", function() {
  if (!OCA.Dashboard) {
    return;
  }
  OCA.Dashboard.registerStatus("status", (el) => {
    createApp(UserStatus, {
      inline: true
    }).use(store).mount(el);
  });
});
export {
  OnlineStatusMixin as O,
  mapState as a,
  clearAtFormat as c,
  logger as l,
  mapGetters as m
};
//# sourceMappingURL=user_status-menu.mjs.map
