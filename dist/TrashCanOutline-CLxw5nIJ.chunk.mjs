const appName = "nextcloud-ui";
const appVersion = "1.0.0";
const global = globalThis || void 0 || self;
/**
* @vue/shared v3.5.25
* (c) 2018-present Yuxi (Evan) You and Vue contributors
* @license MIT
**/
// @__NO_SIDE_EFFECTS__
function makeMap(str) {
  const map2 = /* @__PURE__ */ Object.create(null);
  for (const key of str.split(",")) map2[key] = 1;
  return (val) => val in map2;
}
const EMPTY_OBJ = Object.freeze({});
const EMPTY_ARR = Object.freeze([]);
const NOOP = () => {
};
const NO = () => false;
const isOn = (key) => key.charCodeAt(0) === 111 && key.charCodeAt(1) === 110 && // uppercase letter
(key.charCodeAt(2) > 122 || key.charCodeAt(2) < 97);
const isModelListener = (key) => key.startsWith("onUpdate:");
const extend$1 = Object.assign;
const remove = (arr, el) => {
  const i = arr.indexOf(el);
  if (i > -1) {
    arr.splice(i, 1);
  }
};
const hasOwnProperty$2 = Object.prototype.hasOwnProperty;
const hasOwn = (val, key) => hasOwnProperty$2.call(val, key);
const isArray$1 = Array.isArray;
const isMap = (val) => toTypeString(val) === "[object Map]";
const isSet = (val) => toTypeString(val) === "[object Set]";
const isFunction$2 = (val) => typeof val === "function";
const isString$1 = (val) => typeof val === "string";
const isSymbol = (val) => typeof val === "symbol";
const isObject$1 = (val) => val !== null && typeof val === "object";
const isPromise = (val) => {
  return (isObject$1(val) || isFunction$2(val)) && isFunction$2(val.then) && isFunction$2(val.catch);
};
const objectToString = Object.prototype.toString;
const toTypeString = (value) => objectToString.call(value);
const toRawType = (value) => {
  return toTypeString(value).slice(8, -1);
};
const isPlainObject$1 = (val) => toTypeString(val) === "[object Object]";
const isIntegerKey = (key) => isString$1(key) && key !== "NaN" && key[0] !== "-" && "" + parseInt(key, 10) === key;
const isReservedProp = /* @__PURE__ */ makeMap(
  // the leading comma is intentional so empty string "" is also included
  ",key,ref,ref_for,ref_key,onVnodeBeforeMount,onVnodeMounted,onVnodeBeforeUpdate,onVnodeUpdated,onVnodeBeforeUnmount,onVnodeUnmounted"
);
const isBuiltInDirective = /* @__PURE__ */ makeMap(
  "bind,cloak,else-if,else,for,html,if,model,on,once,pre,show,slot,text,memo"
);
const cacheStringFunction = (fn) => {
  const cache = /* @__PURE__ */ Object.create(null);
  return ((str) => {
    const hit = cache[str];
    return hit || (cache[str] = fn(str));
  });
};
const camelizeRE = /-\w/g;
const camelize = cacheStringFunction(
  (str) => {
    return str.replace(camelizeRE, (c) => c.slice(1).toUpperCase());
  }
);
const hyphenateRE = /\B([A-Z])/g;
const hyphenate = cacheStringFunction(
  (str) => str.replace(hyphenateRE, "-$1").toLowerCase()
);
const capitalize = cacheStringFunction((str) => {
  return str.charAt(0).toUpperCase() + str.slice(1);
});
const toHandlerKey = cacheStringFunction(
  (str) => {
    const s = str ? `on${capitalize(str)}` : ``;
    return s;
  }
);
const hasChanged = (value, oldValue) => !Object.is(value, oldValue);
const invokeArrayFns = (fns, ...arg) => {
  for (let i = 0; i < fns.length; i++) {
    fns[i](...arg);
  }
};
const def = (obj, key, value, writable = false) => {
  Object.defineProperty(obj, key, {
    configurable: true,
    enumerable: false,
    writable,
    value
  });
};
const looseToNumber = (val) => {
  const n2 = parseFloat(val);
  return isNaN(n2) ? val : n2;
};
const toNumber = (val) => {
  const n2 = isString$1(val) ? Number(val) : NaN;
  return isNaN(n2) ? val : n2;
};
let _globalThis;
const getGlobalThis = () => {
  return _globalThis || (_globalThis = typeof globalThis !== "undefined" ? globalThis : typeof self !== "undefined" ? self : typeof window !== "undefined" ? window : typeof global !== "undefined" ? global : {});
};
function normalizeStyle(value) {
  if (isArray$1(value)) {
    const res = {};
    for (let i = 0; i < value.length; i++) {
      const item = value[i];
      const normalized = isString$1(item) ? parseStringStyle(item) : normalizeStyle(item);
      if (normalized) {
        for (const key in normalized) {
          res[key] = normalized[key];
        }
      }
    }
    return res;
  } else if (isString$1(value) || isObject$1(value)) {
    return value;
  }
}
const listDelimiterRE = /;(?![^(]*\))/g;
const propertyDelimiterRE = /:([^]+)/;
const styleCommentRE = /\/\*[^]*?\*\//g;
function parseStringStyle(cssText) {
  const ret = {};
  cssText.replace(styleCommentRE, "").split(listDelimiterRE).forEach((item) => {
    if (item) {
      const tmp = item.split(propertyDelimiterRE);
      tmp.length > 1 && (ret[tmp[0].trim()] = tmp[1].trim());
    }
  });
  return ret;
}
function normalizeClass(value) {
  let res = "";
  if (isString$1(value)) {
    res = value;
  } else if (isArray$1(value)) {
    for (let i = 0; i < value.length; i++) {
      const normalized = normalizeClass(value[i]);
      if (normalized) {
        res += normalized + " ";
      }
    }
  } else if (isObject$1(value)) {
    for (const name in value) {
      if (value[name]) {
        res += name + " ";
      }
    }
  }
  return res.trim();
}
function normalizeProps(props) {
  if (!props) return null;
  let { class: klass, style } = props;
  if (klass && !isString$1(klass)) {
    props.class = normalizeClass(klass);
  }
  if (style) {
    props.style = normalizeStyle(style);
  }
  return props;
}
const HTML_TAGS = "html,body,base,head,link,meta,style,title,address,article,aside,footer,header,hgroup,h1,h2,h3,h4,h5,h6,nav,section,div,dd,dl,dt,figcaption,figure,picture,hr,img,li,main,ol,p,pre,ul,a,b,abbr,bdi,bdo,br,cite,code,data,dfn,em,i,kbd,mark,q,rp,rt,ruby,s,samp,small,span,strong,sub,sup,time,u,var,wbr,area,audio,map,track,video,embed,object,param,source,canvas,script,noscript,del,ins,caption,col,colgroup,table,thead,tbody,td,th,tr,button,datalist,fieldset,form,input,label,legend,meter,optgroup,option,output,progress,select,textarea,details,dialog,menu,summary,template,blockquote,iframe,tfoot";
const SVG_TAGS = "svg,animate,animateMotion,animateTransform,circle,clipPath,color-profile,defs,desc,discard,ellipse,feBlend,feColorMatrix,feComponentTransfer,feComposite,feConvolveMatrix,feDiffuseLighting,feDisplacementMap,feDistantLight,feDropShadow,feFlood,feFuncA,feFuncB,feFuncG,feFuncR,feGaussianBlur,feImage,feMerge,feMergeNode,feMorphology,feOffset,fePointLight,feSpecularLighting,feSpotLight,feTile,feTurbulence,filter,foreignObject,g,hatch,hatchpath,image,line,linearGradient,marker,mask,mesh,meshgradient,meshpatch,meshrow,metadata,mpath,path,pattern,polygon,polyline,radialGradient,rect,set,solidcolor,stop,switch,symbol,text,textPath,title,tspan,unknown,use,view";
const MATH_TAGS = "annotation,annotation-xml,maction,maligngroup,malignmark,math,menclose,merror,mfenced,mfrac,mfraction,mglyph,mi,mlabeledtr,mlongdiv,mmultiscripts,mn,mo,mover,mpadded,mphantom,mprescripts,mroot,mrow,ms,mscarries,mscarry,msgroup,msline,mspace,msqrt,msrow,mstack,mstyle,msub,msubsup,msup,mtable,mtd,mtext,mtr,munder,munderover,none,semantics";
const isHTMLTag = /* @__PURE__ */ makeMap(HTML_TAGS);
const isSVGTag = /* @__PURE__ */ makeMap(SVG_TAGS);
const isMathMLTag = /* @__PURE__ */ makeMap(MATH_TAGS);
const specialBooleanAttrs = `itemscope,allowfullscreen,formnovalidate,ismap,nomodule,novalidate,readonly`;
const isSpecialBooleanAttr = /* @__PURE__ */ makeMap(specialBooleanAttrs);
function includeBooleanAttr(value) {
  return !!value || value === "";
}
const isRef$1 = (val) => {
  return !!(val && val["__v_isRef"] === true);
};
const toDisplayString = (val) => {
  return isString$1(val) ? val : val == null ? "" : isArray$1(val) || isObject$1(val) && (val.toString === objectToString || !isFunction$2(val.toString)) ? isRef$1(val) ? toDisplayString(val.value) : JSON.stringify(val, replacer, 2) : String(val);
};
const replacer = (_key, val) => {
  if (isRef$1(val)) {
    return replacer(_key, val.value);
  } else if (isMap(val)) {
    return {
      [`Map(${val.size})`]: [...val.entries()].reduce(
        (entries2, [key, val2], i) => {
          entries2[stringifySymbol(key, i) + " =>"] = val2;
          return entries2;
        },
        {}
      )
    };
  } else if (isSet(val)) {
    return {
      [`Set(${val.size})`]: [...val.values()].map((v) => stringifySymbol(v))
    };
  } else if (isSymbol(val)) {
    return stringifySymbol(val);
  } else if (isObject$1(val) && !isArray$1(val) && !isPlainObject$1(val)) {
    return String(val);
  }
  return val;
};
const stringifySymbol = (v, i = "") => {
  var _a;
  return (
    // Symbol.description in es2019+ so we need to cast here to pass
    // the lib: es2016 check
    isSymbol(v) ? `Symbol(${(_a = v.description) != null ? _a : i})` : v
  );
};
function normalizeCssVarValue(value) {
  if (value == null) {
    return "initial";
  }
  if (typeof value === "string") {
    return value === "" ? " " : value;
  }
  if (typeof value !== "number" || !Number.isFinite(value)) {
    {
      console.warn(
        "[Vue warn] Invalid value used for CSS binding. Expected a string or a finite number but received:",
        value
      );
    }
  }
  return String(value);
}
/**
* @vue/reactivity v3.5.25
* (c) 2018-present Yuxi (Evan) You and Vue contributors
* @license MIT
**/
function warn$2(msg, ...args) {
  console.warn(`[Vue warn] ${msg}`, ...args);
}
let activeEffectScope;
class EffectScope {
  constructor(detached = false) {
    this.detached = detached;
    this._active = true;
    this._on = 0;
    this.effects = [];
    this.cleanups = [];
    this._isPaused = false;
    this.parent = activeEffectScope;
    if (!detached && activeEffectScope) {
      this.index = (activeEffectScope.scopes || (activeEffectScope.scopes = [])).push(
        this
      ) - 1;
    }
  }
  get active() {
    return this._active;
  }
  pause() {
    if (this._active) {
      this._isPaused = true;
      let i, l;
      if (this.scopes) {
        for (i = 0, l = this.scopes.length; i < l; i++) {
          this.scopes[i].pause();
        }
      }
      for (i = 0, l = this.effects.length; i < l; i++) {
        this.effects[i].pause();
      }
    }
  }
  /**
   * Resumes the effect scope, including all child scopes and effects.
   */
  resume() {
    if (this._active) {
      if (this._isPaused) {
        this._isPaused = false;
        let i, l;
        if (this.scopes) {
          for (i = 0, l = this.scopes.length; i < l; i++) {
            this.scopes[i].resume();
          }
        }
        for (i = 0, l = this.effects.length; i < l; i++) {
          this.effects[i].resume();
        }
      }
    }
  }
  run(fn) {
    if (this._active) {
      const currentEffectScope = activeEffectScope;
      try {
        activeEffectScope = this;
        return fn();
      } finally {
        activeEffectScope = currentEffectScope;
      }
    } else {
      warn$2(`cannot run an inactive effect scope.`);
    }
  }
  /**
   * This should only be called on non-detached scopes
   * @internal
   */
  on() {
    if (++this._on === 1) {
      this.prevScope = activeEffectScope;
      activeEffectScope = this;
    }
  }
  /**
   * This should only be called on non-detached scopes
   * @internal
   */
  off() {
    if (this._on > 0 && --this._on === 0) {
      activeEffectScope = this.prevScope;
      this.prevScope = void 0;
    }
  }
  stop(fromParent) {
    if (this._active) {
      this._active = false;
      let i, l;
      for (i = 0, l = this.effects.length; i < l; i++) {
        this.effects[i].stop();
      }
      this.effects.length = 0;
      for (i = 0, l = this.cleanups.length; i < l; i++) {
        this.cleanups[i]();
      }
      this.cleanups.length = 0;
      if (this.scopes) {
        for (i = 0, l = this.scopes.length; i < l; i++) {
          this.scopes[i].stop(true);
        }
        this.scopes.length = 0;
      }
      if (!this.detached && this.parent && !fromParent) {
        const last = this.parent.scopes.pop();
        if (last && last !== this) {
          this.parent.scopes[this.index] = last;
          last.index = this.index;
        }
      }
      this.parent = void 0;
    }
  }
}
function effectScope(detached) {
  return new EffectScope(detached);
}
function getCurrentScope() {
  return activeEffectScope;
}
function onScopeDispose(fn, failSilently = false) {
  if (activeEffectScope) {
    activeEffectScope.cleanups.push(fn);
  } else if (!failSilently) {
    warn$2(
      `onScopeDispose() is called when there is no active effect scope to be associated with.`
    );
  }
}
let activeSub;
const pausedQueueEffects = /* @__PURE__ */ new WeakSet();
class ReactiveEffect {
  constructor(fn) {
    this.fn = fn;
    this.deps = void 0;
    this.depsTail = void 0;
    this.flags = 1 | 4;
    this.next = void 0;
    this.cleanup = void 0;
    this.scheduler = void 0;
    if (activeEffectScope && activeEffectScope.active) {
      activeEffectScope.effects.push(this);
    }
  }
  pause() {
    this.flags |= 64;
  }
  resume() {
    if (this.flags & 64) {
      this.flags &= -65;
      if (pausedQueueEffects.has(this)) {
        pausedQueueEffects.delete(this);
        this.trigger();
      }
    }
  }
  /**
   * @internal
   */
  notify() {
    if (this.flags & 2 && !(this.flags & 32)) {
      return;
    }
    if (!(this.flags & 8)) {
      batch(this);
    }
  }
  run() {
    if (!(this.flags & 1)) {
      return this.fn();
    }
    this.flags |= 2;
    cleanupEffect(this);
    prepareDeps(this);
    const prevEffect = activeSub;
    const prevShouldTrack = shouldTrack;
    activeSub = this;
    shouldTrack = true;
    try {
      return this.fn();
    } finally {
      if (activeSub !== this) {
        warn$2(
          "Active effect was not restored correctly - this is likely a Vue internal bug."
        );
      }
      cleanupDeps(this);
      activeSub = prevEffect;
      shouldTrack = prevShouldTrack;
      this.flags &= -3;
    }
  }
  stop() {
    if (this.flags & 1) {
      for (let link = this.deps; link; link = link.nextDep) {
        removeSub(link);
      }
      this.deps = this.depsTail = void 0;
      cleanupEffect(this);
      this.onStop && this.onStop();
      this.flags &= -2;
    }
  }
  trigger() {
    if (this.flags & 64) {
      pausedQueueEffects.add(this);
    } else if (this.scheduler) {
      this.scheduler();
    } else {
      this.runIfDirty();
    }
  }
  /**
   * @internal
   */
  runIfDirty() {
    if (isDirty(this)) {
      this.run();
    }
  }
  get dirty() {
    return isDirty(this);
  }
}
let batchDepth = 0;
let batchedSub;
let batchedComputed;
function batch(sub, isComputed = false) {
  sub.flags |= 8;
  if (isComputed) {
    sub.next = batchedComputed;
    batchedComputed = sub;
    return;
  }
  sub.next = batchedSub;
  batchedSub = sub;
}
function startBatch() {
  batchDepth++;
}
function endBatch() {
  if (--batchDepth > 0) {
    return;
  }
  if (batchedComputed) {
    let e = batchedComputed;
    batchedComputed = void 0;
    while (e) {
      const next = e.next;
      e.next = void 0;
      e.flags &= -9;
      e = next;
    }
  }
  let error;
  while (batchedSub) {
    let e = batchedSub;
    batchedSub = void 0;
    while (e) {
      const next = e.next;
      e.next = void 0;
      e.flags &= -9;
      if (e.flags & 1) {
        try {
          ;
          e.trigger();
        } catch (err) {
          if (!error) error = err;
        }
      }
      e = next;
    }
  }
  if (error) throw error;
}
function prepareDeps(sub) {
  for (let link = sub.deps; link; link = link.nextDep) {
    link.version = -1;
    link.prevActiveLink = link.dep.activeLink;
    link.dep.activeLink = link;
  }
}
function cleanupDeps(sub) {
  let head;
  let tail = sub.depsTail;
  let link = tail;
  while (link) {
    const prev = link.prevDep;
    if (link.version === -1) {
      if (link === tail) tail = prev;
      removeSub(link);
      removeDep(link);
    } else {
      head = link;
    }
    link.dep.activeLink = link.prevActiveLink;
    link.prevActiveLink = void 0;
    link = prev;
  }
  sub.deps = head;
  sub.depsTail = tail;
}
function isDirty(sub) {
  for (let link = sub.deps; link; link = link.nextDep) {
    if (link.dep.version !== link.version || link.dep.computed && (refreshComputed(link.dep.computed) || link.dep.version !== link.version)) {
      return true;
    }
  }
  if (sub._dirty) {
    return true;
  }
  return false;
}
function refreshComputed(computed2) {
  if (computed2.flags & 4 && !(computed2.flags & 16)) {
    return;
  }
  computed2.flags &= -17;
  if (computed2.globalVersion === globalVersion) {
    return;
  }
  computed2.globalVersion = globalVersion;
  if (!computed2.isSSR && computed2.flags & 128 && (!computed2.deps && !computed2._dirty || !isDirty(computed2))) {
    return;
  }
  computed2.flags |= 2;
  const dep = computed2.dep;
  const prevSub = activeSub;
  const prevShouldTrack = shouldTrack;
  activeSub = computed2;
  shouldTrack = true;
  try {
    prepareDeps(computed2);
    const value = computed2.fn(computed2._value);
    if (dep.version === 0 || hasChanged(value, computed2._value)) {
      computed2.flags |= 128;
      computed2._value = value;
      dep.version++;
    }
  } catch (err) {
    dep.version++;
    throw err;
  } finally {
    activeSub = prevSub;
    shouldTrack = prevShouldTrack;
    cleanupDeps(computed2);
    computed2.flags &= -3;
  }
}
function removeSub(link, soft = false) {
  const { dep, prevSub, nextSub } = link;
  if (prevSub) {
    prevSub.nextSub = nextSub;
    link.prevSub = void 0;
  }
  if (nextSub) {
    nextSub.prevSub = prevSub;
    link.nextSub = void 0;
  }
  if (dep.subsHead === link) {
    dep.subsHead = nextSub;
  }
  if (dep.subs === link) {
    dep.subs = prevSub;
    if (!prevSub && dep.computed) {
      dep.computed.flags &= -5;
      for (let l = dep.computed.deps; l; l = l.nextDep) {
        removeSub(l, true);
      }
    }
  }
  if (!soft && !--dep.sc && dep.map) {
    dep.map.delete(dep.key);
  }
}
function removeDep(link) {
  const { prevDep, nextDep } = link;
  if (prevDep) {
    prevDep.nextDep = nextDep;
    link.prevDep = void 0;
  }
  if (nextDep) {
    nextDep.prevDep = prevDep;
    link.nextDep = void 0;
  }
}
let shouldTrack = true;
const trackStack = [];
function pauseTracking() {
  trackStack.push(shouldTrack);
  shouldTrack = false;
}
function resetTracking() {
  const last = trackStack.pop();
  shouldTrack = last === void 0 ? true : last;
}
function cleanupEffect(e) {
  const { cleanup } = e;
  e.cleanup = void 0;
  if (cleanup) {
    const prevSub = activeSub;
    activeSub = void 0;
    try {
      cleanup();
    } finally {
      activeSub = prevSub;
    }
  }
}
let globalVersion = 0;
class Link {
  constructor(sub, dep) {
    this.sub = sub;
    this.dep = dep;
    this.version = dep.version;
    this.nextDep = this.prevDep = this.nextSub = this.prevSub = this.prevActiveLink = void 0;
  }
}
class Dep {
  // TODO isolatedDeclarations "__v_skip"
  constructor(computed2) {
    this.computed = computed2;
    this.version = 0;
    this.activeLink = void 0;
    this.subs = void 0;
    this.map = void 0;
    this.key = void 0;
    this.sc = 0;
    this.__v_skip = true;
    {
      this.subsHead = void 0;
    }
  }
  track(debugInfo) {
    if (!activeSub || !shouldTrack || activeSub === this.computed) {
      return;
    }
    let link = this.activeLink;
    if (link === void 0 || link.sub !== activeSub) {
      link = this.activeLink = new Link(activeSub, this);
      if (!activeSub.deps) {
        activeSub.deps = activeSub.depsTail = link;
      } else {
        link.prevDep = activeSub.depsTail;
        activeSub.depsTail.nextDep = link;
        activeSub.depsTail = link;
      }
      addSub(link);
    } else if (link.version === -1) {
      link.version = this.version;
      if (link.nextDep) {
        const next = link.nextDep;
        next.prevDep = link.prevDep;
        if (link.prevDep) {
          link.prevDep.nextDep = next;
        }
        link.prevDep = activeSub.depsTail;
        link.nextDep = void 0;
        activeSub.depsTail.nextDep = link;
        activeSub.depsTail = link;
        if (activeSub.deps === link) {
          activeSub.deps = next;
        }
      }
    }
    if (activeSub.onTrack) {
      activeSub.onTrack(
        extend$1(
          {
            effect: activeSub
          },
          debugInfo
        )
      );
    }
    return link;
  }
  trigger(debugInfo) {
    this.version++;
    globalVersion++;
    this.notify(debugInfo);
  }
  notify(debugInfo) {
    startBatch();
    try {
      if (true) {
        for (let head = this.subsHead; head; head = head.nextSub) {
          if (head.sub.onTrigger && !(head.sub.flags & 8)) {
            head.sub.onTrigger(
              extend$1(
                {
                  effect: head.sub
                },
                debugInfo
              )
            );
          }
        }
      }
      for (let link = this.subs; link; link = link.prevSub) {
        if (link.sub.notify()) {
          ;
          link.sub.dep.notify();
        }
      }
    } finally {
      endBatch();
    }
  }
}
function addSub(link) {
  link.dep.sc++;
  if (link.sub.flags & 4) {
    const computed2 = link.dep.computed;
    if (computed2 && !link.dep.subs) {
      computed2.flags |= 4 | 16;
      for (let l = computed2.deps; l; l = l.nextDep) {
        addSub(l);
      }
    }
    const currentTail = link.dep.subs;
    if (currentTail !== link) {
      link.prevSub = currentTail;
      if (currentTail) currentTail.nextSub = link;
    }
    if (link.dep.subsHead === void 0) {
      link.dep.subsHead = link;
    }
    link.dep.subs = link;
  }
}
const targetMap = /* @__PURE__ */ new WeakMap();
const ITERATE_KEY = Symbol(
  "Object iterate"
);
const MAP_KEY_ITERATE_KEY = Symbol(
  "Map keys iterate"
);
const ARRAY_ITERATE_KEY = Symbol(
  "Array iterate"
);
function track(target, type, key) {
  if (shouldTrack && activeSub) {
    let depsMap = targetMap.get(target);
    if (!depsMap) {
      targetMap.set(target, depsMap = /* @__PURE__ */ new Map());
    }
    let dep = depsMap.get(key);
    if (!dep) {
      depsMap.set(key, dep = new Dep());
      dep.map = depsMap;
      dep.key = key;
    }
    {
      dep.track({
        target,
        type,
        key
      });
    }
  }
}
function trigger(target, type, key, newValue, oldValue, oldTarget) {
  const depsMap = targetMap.get(target);
  if (!depsMap) {
    globalVersion++;
    return;
  }
  const run = (dep) => {
    if (dep) {
      {
        dep.trigger({
          target,
          type,
          key,
          newValue,
          oldValue,
          oldTarget
        });
      }
    }
  };
  startBatch();
  if (type === "clear") {
    depsMap.forEach(run);
  } else {
    const targetIsArray = isArray$1(target);
    const isArrayIndex = targetIsArray && isIntegerKey(key);
    if (targetIsArray && key === "length") {
      const newLength = Number(newValue);
      depsMap.forEach((dep, key2) => {
        if (key2 === "length" || key2 === ARRAY_ITERATE_KEY || !isSymbol(key2) && key2 >= newLength) {
          run(dep);
        }
      });
    } else {
      if (key !== void 0 || depsMap.has(void 0)) {
        run(depsMap.get(key));
      }
      if (isArrayIndex) {
        run(depsMap.get(ARRAY_ITERATE_KEY));
      }
      switch (type) {
        case "add":
          if (!targetIsArray) {
            run(depsMap.get(ITERATE_KEY));
            if (isMap(target)) {
              run(depsMap.get(MAP_KEY_ITERATE_KEY));
            }
          } else if (isArrayIndex) {
            run(depsMap.get("length"));
          }
          break;
        case "delete":
          if (!targetIsArray) {
            run(depsMap.get(ITERATE_KEY));
            if (isMap(target)) {
              run(depsMap.get(MAP_KEY_ITERATE_KEY));
            }
          }
          break;
        case "set":
          if (isMap(target)) {
            run(depsMap.get(ITERATE_KEY));
          }
          break;
      }
    }
  }
  endBatch();
}
function getDepFromReactive(object, key) {
  const depMap = targetMap.get(object);
  return depMap && depMap.get(key);
}
function reactiveReadArray(array) {
  const raw = toRaw(array);
  if (raw === array) return raw;
  track(raw, "iterate", ARRAY_ITERATE_KEY);
  return isShallow(array) ? raw : raw.map(toReactive);
}
function shallowReadArray(arr) {
  track(arr = toRaw(arr), "iterate", ARRAY_ITERATE_KEY);
  return arr;
}
function toWrapped(target, item) {
  if (isReadonly(target)) {
    return isReactive(target) ? toReadonly(toReactive(item)) : toReadonly(item);
  }
  return toReactive(item);
}
const arrayInstrumentations = {
  __proto__: null,
  [Symbol.iterator]() {
    return iterator$1(this, Symbol.iterator, (item) => toWrapped(this, item));
  },
  concat(...args) {
    return reactiveReadArray(this).concat(
      ...args.map((x) => isArray$1(x) ? reactiveReadArray(x) : x)
    );
  },
  entries() {
    return iterator$1(this, "entries", (value) => {
      value[1] = toWrapped(this, value[1]);
      return value;
    });
  },
  every(fn, thisArg) {
    return apply$1(this, "every", fn, thisArg, void 0, arguments);
  },
  filter(fn, thisArg) {
    return apply$1(
      this,
      "filter",
      fn,
      thisArg,
      (v) => v.map((item) => toWrapped(this, item)),
      arguments
    );
  },
  find(fn, thisArg) {
    return apply$1(
      this,
      "find",
      fn,
      thisArg,
      (item) => toWrapped(this, item),
      arguments
    );
  },
  findIndex(fn, thisArg) {
    return apply$1(this, "findIndex", fn, thisArg, void 0, arguments);
  },
  findLast(fn, thisArg) {
    return apply$1(
      this,
      "findLast",
      fn,
      thisArg,
      (item) => toWrapped(this, item),
      arguments
    );
  },
  findLastIndex(fn, thisArg) {
    return apply$1(this, "findLastIndex", fn, thisArg, void 0, arguments);
  },
  // flat, flatMap could benefit from ARRAY_ITERATE but are not straight-forward to implement
  forEach(fn, thisArg) {
    return apply$1(this, "forEach", fn, thisArg, void 0, arguments);
  },
  includes(...args) {
    return searchProxy(this, "includes", args);
  },
  indexOf(...args) {
    return searchProxy(this, "indexOf", args);
  },
  join(separator) {
    return reactiveReadArray(this).join(separator);
  },
  // keys() iterator only reads `length`, no optimization required
  lastIndexOf(...args) {
    return searchProxy(this, "lastIndexOf", args);
  },
  map(fn, thisArg) {
    return apply$1(this, "map", fn, thisArg, void 0, arguments);
  },
  pop() {
    return noTracking(this, "pop");
  },
  push(...args) {
    return noTracking(this, "push", args);
  },
  reduce(fn, ...args) {
    return reduce(this, "reduce", fn, args);
  },
  reduceRight(fn, ...args) {
    return reduce(this, "reduceRight", fn, args);
  },
  shift() {
    return noTracking(this, "shift");
  },
  // slice could use ARRAY_ITERATE but also seems to beg for range tracking
  some(fn, thisArg) {
    return apply$1(this, "some", fn, thisArg, void 0, arguments);
  },
  splice(...args) {
    return noTracking(this, "splice", args);
  },
  toReversed() {
    return reactiveReadArray(this).toReversed();
  },
  toSorted(comparer) {
    return reactiveReadArray(this).toSorted(comparer);
  },
  toSpliced(...args) {
    return reactiveReadArray(this).toSpliced(...args);
  },
  unshift(...args) {
    return noTracking(this, "unshift", args);
  },
  values() {
    return iterator$1(this, "values", (item) => toWrapped(this, item));
  }
};
function iterator$1(self2, method, wrapValue) {
  const arr = shallowReadArray(self2);
  const iter = arr[method]();
  if (arr !== self2 && !isShallow(self2)) {
    iter._next = iter.next;
    iter.next = () => {
      const result = iter._next();
      if (!result.done) {
        result.value = wrapValue(result.value);
      }
      return result;
    };
  }
  return iter;
}
const arrayProto = Array.prototype;
function apply$1(self2, method, fn, thisArg, wrappedRetFn, args) {
  const arr = shallowReadArray(self2);
  const needsWrap = arr !== self2 && !isShallow(self2);
  const methodFn = arr[method];
  if (methodFn !== arrayProto[method]) {
    const result2 = methodFn.apply(self2, args);
    return needsWrap ? toReactive(result2) : result2;
  }
  let wrappedFn = fn;
  if (arr !== self2) {
    if (needsWrap) {
      wrappedFn = function(item, index) {
        return fn.call(this, toWrapped(self2, item), index, self2);
      };
    } else if (fn.length > 2) {
      wrappedFn = function(item, index) {
        return fn.call(this, item, index, self2);
      };
    }
  }
  const result = methodFn.call(arr, wrappedFn, thisArg);
  return needsWrap && wrappedRetFn ? wrappedRetFn(result) : result;
}
function reduce(self2, method, fn, args) {
  const arr = shallowReadArray(self2);
  let wrappedFn = fn;
  if (arr !== self2) {
    if (!isShallow(self2)) {
      wrappedFn = function(acc, item, index) {
        return fn.call(this, acc, toWrapped(self2, item), index, self2);
      };
    } else if (fn.length > 3) {
      wrappedFn = function(acc, item, index) {
        return fn.call(this, acc, item, index, self2);
      };
    }
  }
  return arr[method](wrappedFn, ...args);
}
function searchProxy(self2, method, args) {
  const arr = toRaw(self2);
  track(arr, "iterate", ARRAY_ITERATE_KEY);
  const res = arr[method](...args);
  if ((res === -1 || res === false) && isProxy(args[0])) {
    args[0] = toRaw(args[0]);
    return arr[method](...args);
  }
  return res;
}
function noTracking(self2, method, args = []) {
  pauseTracking();
  startBatch();
  const res = toRaw(self2)[method].apply(self2, args);
  endBatch();
  resetTracking();
  return res;
}
const isNonTrackableKeys = /* @__PURE__ */ makeMap(`__proto__,__v_isRef,__isVue`);
const builtInSymbols = new Set(
  /* @__PURE__ */ Object.getOwnPropertyNames(Symbol).filter((key) => key !== "arguments" && key !== "caller").map((key) => Symbol[key]).filter(isSymbol)
);
function hasOwnProperty$1(key) {
  if (!isSymbol(key)) key = String(key);
  const obj = toRaw(this);
  track(obj, "has", key);
  return obj.hasOwnProperty(key);
}
class BaseReactiveHandler {
  constructor(_isReadonly = false, _isShallow = false) {
    this._isReadonly = _isReadonly;
    this._isShallow = _isShallow;
  }
  get(target, key, receiver) {
    if (key === "__v_skip") return target["__v_skip"];
    const isReadonly2 = this._isReadonly, isShallow2 = this._isShallow;
    if (key === "__v_isReactive") {
      return !isReadonly2;
    } else if (key === "__v_isReadonly") {
      return isReadonly2;
    } else if (key === "__v_isShallow") {
      return isShallow2;
    } else if (key === "__v_raw") {
      if (receiver === (isReadonly2 ? isShallow2 ? shallowReadonlyMap : readonlyMap : isShallow2 ? shallowReactiveMap : reactiveMap).get(target) || // receiver is not the reactive proxy, but has the same prototype
      // this means the receiver is a user proxy of the reactive proxy
      Object.getPrototypeOf(target) === Object.getPrototypeOf(receiver)) {
        return target;
      }
      return;
    }
    const targetIsArray = isArray$1(target);
    if (!isReadonly2) {
      let fn;
      if (targetIsArray && (fn = arrayInstrumentations[key])) {
        return fn;
      }
      if (key === "hasOwnProperty") {
        return hasOwnProperty$1;
      }
    }
    const res = Reflect.get(
      target,
      key,
      // if this is a proxy wrapping a ref, return methods using the raw ref
      // as receiver so that we don't have to call `toRaw` on the ref in all
      // its class methods
      isRef(target) ? target : receiver
    );
    if (isSymbol(key) ? builtInSymbols.has(key) : isNonTrackableKeys(key)) {
      return res;
    }
    if (!isReadonly2) {
      track(target, "get", key);
    }
    if (isShallow2) {
      return res;
    }
    if (isRef(res)) {
      const value = targetIsArray && isIntegerKey(key) ? res : res.value;
      return isReadonly2 && isObject$1(value) ? readonly(value) : value;
    }
    if (isObject$1(res)) {
      return isReadonly2 ? readonly(res) : reactive(res);
    }
    return res;
  }
}
class MutableReactiveHandler extends BaseReactiveHandler {
  constructor(isShallow2 = false) {
    super(false, isShallow2);
  }
  set(target, key, value, receiver) {
    let oldValue = target[key];
    const isArrayWithIntegerKey = isArray$1(target) && isIntegerKey(key);
    if (!this._isShallow) {
      const isOldValueReadonly = isReadonly(oldValue);
      if (!isShallow(value) && !isReadonly(value)) {
        oldValue = toRaw(oldValue);
        value = toRaw(value);
      }
      if (!isArrayWithIntegerKey && isRef(oldValue) && !isRef(value)) {
        if (isOldValueReadonly) {
          {
            warn$2(
              `Set operation on key "${String(key)}" failed: target is readonly.`,
              target[key]
            );
          }
          return true;
        } else {
          oldValue.value = value;
          return true;
        }
      }
    }
    const hadKey = isArrayWithIntegerKey ? Number(key) < target.length : hasOwn(target, key);
    const result = Reflect.set(
      target,
      key,
      value,
      isRef(target) ? target : receiver
    );
    if (target === toRaw(receiver)) {
      if (!hadKey) {
        trigger(target, "add", key, value);
      } else if (hasChanged(value, oldValue)) {
        trigger(target, "set", key, value, oldValue);
      }
    }
    return result;
  }
  deleteProperty(target, key) {
    const hadKey = hasOwn(target, key);
    const oldValue = target[key];
    const result = Reflect.deleteProperty(target, key);
    if (result && hadKey) {
      trigger(target, "delete", key, void 0, oldValue);
    }
    return result;
  }
  has(target, key) {
    const result = Reflect.has(target, key);
    if (!isSymbol(key) || !builtInSymbols.has(key)) {
      track(target, "has", key);
    }
    return result;
  }
  ownKeys(target) {
    track(
      target,
      "iterate",
      isArray$1(target) ? "length" : ITERATE_KEY
    );
    return Reflect.ownKeys(target);
  }
}
class ReadonlyReactiveHandler extends BaseReactiveHandler {
  constructor(isShallow2 = false) {
    super(true, isShallow2);
  }
  set(target, key) {
    {
      warn$2(
        `Set operation on key "${String(key)}" failed: target is readonly.`,
        target
      );
    }
    return true;
  }
  deleteProperty(target, key) {
    {
      warn$2(
        `Delete operation on key "${String(key)}" failed: target is readonly.`,
        target
      );
    }
    return true;
  }
}
const mutableHandlers = /* @__PURE__ */ new MutableReactiveHandler();
const readonlyHandlers = /* @__PURE__ */ new ReadonlyReactiveHandler();
const shallowReactiveHandlers = /* @__PURE__ */ new MutableReactiveHandler(true);
const shallowReadonlyHandlers = /* @__PURE__ */ new ReadonlyReactiveHandler(true);
const toShallow = (value) => value;
const getProto = (v) => Reflect.getPrototypeOf(v);
function createIterableMethod(method, isReadonly2, isShallow2) {
  return function(...args) {
    const target = this["__v_raw"];
    const rawTarget = toRaw(target);
    const targetIsMap = isMap(rawTarget);
    const isPair = method === "entries" || method === Symbol.iterator && targetIsMap;
    const isKeyOnly = method === "keys" && targetIsMap;
    const innerIterator = target[method](...args);
    const wrap = isShallow2 ? toShallow : isReadonly2 ? toReadonly : toReactive;
    !isReadonly2 && track(
      rawTarget,
      "iterate",
      isKeyOnly ? MAP_KEY_ITERATE_KEY : ITERATE_KEY
    );
    return {
      // iterator protocol
      next() {
        const { value, done } = innerIterator.next();
        return done ? { value, done } : {
          value: isPair ? [wrap(value[0]), wrap(value[1])] : wrap(value),
          done
        };
      },
      // iterable protocol
      [Symbol.iterator]() {
        return this;
      }
    };
  };
}
function createReadonlyMethod(type) {
  return function(...args) {
    {
      const key = args[0] ? `on key "${args[0]}" ` : ``;
      warn$2(
        `${capitalize(type)} operation ${key}failed: target is readonly.`,
        toRaw(this)
      );
    }
    return type === "delete" ? false : type === "clear" ? void 0 : this;
  };
}
function createInstrumentations(readonly2, shallow) {
  const instrumentations = {
    get(key) {
      const target = this["__v_raw"];
      const rawTarget = toRaw(target);
      const rawKey = toRaw(key);
      if (!readonly2) {
        if (hasChanged(key, rawKey)) {
          track(rawTarget, "get", key);
        }
        track(rawTarget, "get", rawKey);
      }
      const { has } = getProto(rawTarget);
      const wrap = shallow ? toShallow : readonly2 ? toReadonly : toReactive;
      if (has.call(rawTarget, key)) {
        return wrap(target.get(key));
      } else if (has.call(rawTarget, rawKey)) {
        return wrap(target.get(rawKey));
      } else if (target !== rawTarget) {
        target.get(key);
      }
    },
    get size() {
      const target = this["__v_raw"];
      !readonly2 && track(toRaw(target), "iterate", ITERATE_KEY);
      return target.size;
    },
    has(key) {
      const target = this["__v_raw"];
      const rawTarget = toRaw(target);
      const rawKey = toRaw(key);
      if (!readonly2) {
        if (hasChanged(key, rawKey)) {
          track(rawTarget, "has", key);
        }
        track(rawTarget, "has", rawKey);
      }
      return key === rawKey ? target.has(key) : target.has(key) || target.has(rawKey);
    },
    forEach(callback, thisArg) {
      const observed = this;
      const target = observed["__v_raw"];
      const rawTarget = toRaw(target);
      const wrap = shallow ? toShallow : readonly2 ? toReadonly : toReactive;
      !readonly2 && track(rawTarget, "iterate", ITERATE_KEY);
      return target.forEach((value, key) => {
        return callback.call(thisArg, wrap(value), wrap(key), observed);
      });
    }
  };
  extend$1(
    instrumentations,
    readonly2 ? {
      add: createReadonlyMethod("add"),
      set: createReadonlyMethod("set"),
      delete: createReadonlyMethod("delete"),
      clear: createReadonlyMethod("clear")
    } : {
      add(value) {
        if (!shallow && !isShallow(value) && !isReadonly(value)) {
          value = toRaw(value);
        }
        const target = toRaw(this);
        const proto = getProto(target);
        const hadKey = proto.has.call(target, value);
        if (!hadKey) {
          target.add(value);
          trigger(target, "add", value, value);
        }
        return this;
      },
      set(key, value) {
        if (!shallow && !isShallow(value) && !isReadonly(value)) {
          value = toRaw(value);
        }
        const target = toRaw(this);
        const { has, get } = getProto(target);
        let hadKey = has.call(target, key);
        if (!hadKey) {
          key = toRaw(key);
          hadKey = has.call(target, key);
        } else {
          checkIdentityKeys(target, has, key);
        }
        const oldValue = get.call(target, key);
        target.set(key, value);
        if (!hadKey) {
          trigger(target, "add", key, value);
        } else if (hasChanged(value, oldValue)) {
          trigger(target, "set", key, value, oldValue);
        }
        return this;
      },
      delete(key) {
        const target = toRaw(this);
        const { has, get } = getProto(target);
        let hadKey = has.call(target, key);
        if (!hadKey) {
          key = toRaw(key);
          hadKey = has.call(target, key);
        } else {
          checkIdentityKeys(target, has, key);
        }
        const oldValue = get ? get.call(target, key) : void 0;
        const result = target.delete(key);
        if (hadKey) {
          trigger(target, "delete", key, void 0, oldValue);
        }
        return result;
      },
      clear() {
        const target = toRaw(this);
        const hadItems = target.size !== 0;
        const oldTarget = isMap(target) ? new Map(target) : new Set(target);
        const result = target.clear();
        if (hadItems) {
          trigger(
            target,
            "clear",
            void 0,
            void 0,
            oldTarget
          );
        }
        return result;
      }
    }
  );
  const iteratorMethods = [
    "keys",
    "values",
    "entries",
    Symbol.iterator
  ];
  iteratorMethods.forEach((method) => {
    instrumentations[method] = createIterableMethod(method, readonly2, shallow);
  });
  return instrumentations;
}
function createInstrumentationGetter(isReadonly2, shallow) {
  const instrumentations = createInstrumentations(isReadonly2, shallow);
  return (target, key, receiver) => {
    if (key === "__v_isReactive") {
      return !isReadonly2;
    } else if (key === "__v_isReadonly") {
      return isReadonly2;
    } else if (key === "__v_raw") {
      return target;
    }
    return Reflect.get(
      hasOwn(instrumentations, key) && key in target ? instrumentations : target,
      key,
      receiver
    );
  };
}
const mutableCollectionHandlers = {
  get: /* @__PURE__ */ createInstrumentationGetter(false, false)
};
const shallowCollectionHandlers = {
  get: /* @__PURE__ */ createInstrumentationGetter(false, true)
};
const readonlyCollectionHandlers = {
  get: /* @__PURE__ */ createInstrumentationGetter(true, false)
};
const shallowReadonlyCollectionHandlers = {
  get: /* @__PURE__ */ createInstrumentationGetter(true, true)
};
function checkIdentityKeys(target, has, key) {
  const rawKey = toRaw(key);
  if (rawKey !== key && has.call(target, rawKey)) {
    const type = toRawType(target);
    warn$2(
      `Reactive ${type} contains both the raw and reactive versions of the same object${type === `Map` ? ` as keys` : ``}, which can lead to inconsistencies. Avoid differentiating between the raw and reactive versions of an object and only use the reactive version if possible.`
    );
  }
}
const reactiveMap = /* @__PURE__ */ new WeakMap();
const shallowReactiveMap = /* @__PURE__ */ new WeakMap();
const readonlyMap = /* @__PURE__ */ new WeakMap();
const shallowReadonlyMap = /* @__PURE__ */ new WeakMap();
function targetTypeMap(rawType) {
  switch (rawType) {
    case "Object":
    case "Array":
      return 1;
    case "Map":
    case "Set":
    case "WeakMap":
    case "WeakSet":
      return 2;
    default:
      return 0;
  }
}
function getTargetType(value) {
  return value["__v_skip"] || !Object.isExtensible(value) ? 0 : targetTypeMap(toRawType(value));
}
function reactive(target) {
  if (isReadonly(target)) {
    return target;
  }
  return createReactiveObject(
    target,
    false,
    mutableHandlers,
    mutableCollectionHandlers,
    reactiveMap
  );
}
function shallowReactive(target) {
  return createReactiveObject(
    target,
    false,
    shallowReactiveHandlers,
    shallowCollectionHandlers,
    shallowReactiveMap
  );
}
function readonly(target) {
  return createReactiveObject(
    target,
    true,
    readonlyHandlers,
    readonlyCollectionHandlers,
    readonlyMap
  );
}
function shallowReadonly(target) {
  return createReactiveObject(
    target,
    true,
    shallowReadonlyHandlers,
    shallowReadonlyCollectionHandlers,
    shallowReadonlyMap
  );
}
function createReactiveObject(target, isReadonly2, baseHandlers, collectionHandlers, proxyMap) {
  if (!isObject$1(target)) {
    {
      warn$2(
        `value cannot be made ${isReadonly2 ? "readonly" : "reactive"}: ${String(
          target
        )}`
      );
    }
    return target;
  }
  if (target["__v_raw"] && !(isReadonly2 && target["__v_isReactive"])) {
    return target;
  }
  const targetType = getTargetType(target);
  if (targetType === 0) {
    return target;
  }
  const existingProxy = proxyMap.get(target);
  if (existingProxy) {
    return existingProxy;
  }
  const proxy = new Proxy(
    target,
    targetType === 2 ? collectionHandlers : baseHandlers
  );
  proxyMap.set(target, proxy);
  return proxy;
}
function isReactive(value) {
  if (isReadonly(value)) {
    return isReactive(value["__v_raw"]);
  }
  return !!(value && value["__v_isReactive"]);
}
function isReadonly(value) {
  return !!(value && value["__v_isReadonly"]);
}
function isShallow(value) {
  return !!(value && value["__v_isShallow"]);
}
function isProxy(value) {
  return value ? !!value["__v_raw"] : false;
}
function toRaw(observed) {
  const raw = observed && observed["__v_raw"];
  return raw ? toRaw(raw) : observed;
}
function markRaw(value) {
  if (!hasOwn(value, "__v_skip") && Object.isExtensible(value)) {
    def(value, "__v_skip", true);
  }
  return value;
}
const toReactive = (value) => isObject$1(value) ? reactive(value) : value;
const toReadonly = (value) => isObject$1(value) ? readonly(value) : value;
function isRef(r) {
  return r ? r["__v_isRef"] === true : false;
}
function ref(value) {
  return createRef(value, false);
}
function shallowRef(value) {
  return createRef(value, true);
}
function createRef(rawValue, shallow) {
  if (isRef(rawValue)) {
    return rawValue;
  }
  return new RefImpl(rawValue, shallow);
}
class RefImpl {
  constructor(value, isShallow2) {
    this.dep = new Dep();
    this["__v_isRef"] = true;
    this["__v_isShallow"] = false;
    this._rawValue = isShallow2 ? value : toRaw(value);
    this._value = isShallow2 ? value : toReactive(value);
    this["__v_isShallow"] = isShallow2;
  }
  get value() {
    {
      this.dep.track({
        target: this,
        type: "get",
        key: "value"
      });
    }
    return this._value;
  }
  set value(newValue) {
    const oldValue = this._rawValue;
    const useDirectValue = this["__v_isShallow"] || isShallow(newValue) || isReadonly(newValue);
    newValue = useDirectValue ? newValue : toRaw(newValue);
    if (hasChanged(newValue, oldValue)) {
      this._rawValue = newValue;
      this._value = useDirectValue ? newValue : toReactive(newValue);
      {
        this.dep.trigger({
          target: this,
          type: "set",
          key: "value",
          newValue,
          oldValue
        });
      }
    }
  }
}
function unref(ref2) {
  return isRef(ref2) ? ref2.value : ref2;
}
function toValue(source) {
  return isFunction$2(source) ? source() : unref(source);
}
const shallowUnwrapHandlers = {
  get: (target, key, receiver) => key === "__v_raw" ? target : unref(Reflect.get(target, key, receiver)),
  set: (target, key, value, receiver) => {
    const oldValue = target[key];
    if (isRef(oldValue) && !isRef(value)) {
      oldValue.value = value;
      return true;
    } else {
      return Reflect.set(target, key, value, receiver);
    }
  }
};
function proxyRefs(objectWithRefs) {
  return isReactive(objectWithRefs) ? objectWithRefs : new Proxy(objectWithRefs, shallowUnwrapHandlers);
}
class CustomRefImpl {
  constructor(factory2) {
    this["__v_isRef"] = true;
    this._value = void 0;
    const dep = this.dep = new Dep();
    const { get, set } = factory2(dep.track.bind(dep), dep.trigger.bind(dep));
    this._get = get;
    this._set = set;
  }
  get value() {
    return this._value = this._get();
  }
  set value(newVal) {
    this._set(newVal);
  }
}
function customRef(factory2) {
  return new CustomRefImpl(factory2);
}
function toRefs(object) {
  if (!isProxy(object)) {
    warn$2(`toRefs() expects a reactive object but received a plain one.`);
  }
  const ret = isArray$1(object) ? new Array(object.length) : {};
  for (const key in object) {
    ret[key] = propertyToRef(object, key);
  }
  return ret;
}
class ObjectRefImpl {
  constructor(_object, _key, _defaultValue) {
    this._object = _object;
    this._key = _key;
    this._defaultValue = _defaultValue;
    this["__v_isRef"] = true;
    this._value = void 0;
    this._raw = toRaw(_object);
    let shallow = true;
    let obj = _object;
    if (!isArray$1(_object) || !isIntegerKey(String(_key))) {
      do {
        shallow = !isProxy(obj) || isShallow(obj);
      } while (shallow && (obj = obj["__v_raw"]));
    }
    this._shallow = shallow;
  }
  get value() {
    let val = this._object[this._key];
    if (this._shallow) {
      val = unref(val);
    }
    return this._value = val === void 0 ? this._defaultValue : val;
  }
  set value(newVal) {
    if (this._shallow && isRef(this._raw[this._key])) {
      const nestedRef = this._object[this._key];
      if (isRef(nestedRef)) {
        nestedRef.value = newVal;
        return;
      }
    }
    this._object[this._key] = newVal;
  }
  get dep() {
    return getDepFromReactive(this._raw, this._key);
  }
}
class GetterRefImpl {
  constructor(_getter) {
    this._getter = _getter;
    this["__v_isRef"] = true;
    this["__v_isReadonly"] = true;
    this._value = void 0;
  }
  get value() {
    return this._value = this._getter();
  }
}
function toRef(source, key, defaultValue) {
  if (isRef(source)) {
    return source;
  } else if (isFunction$2(source)) {
    return new GetterRefImpl(source);
  } else if (isObject$1(source) && arguments.length > 1) {
    return propertyToRef(source, key, defaultValue);
  } else {
    return ref(source);
  }
}
function propertyToRef(source, key, defaultValue) {
  return new ObjectRefImpl(source, key, defaultValue);
}
class ComputedRefImpl {
  constructor(fn, setter, isSSR) {
    this.fn = fn;
    this.setter = setter;
    this._value = void 0;
    this.dep = new Dep(this);
    this.__v_isRef = true;
    this.deps = void 0;
    this.depsTail = void 0;
    this.flags = 16;
    this.globalVersion = globalVersion - 1;
    this.next = void 0;
    this.effect = this;
    this["__v_isReadonly"] = !setter;
    this.isSSR = isSSR;
  }
  /**
   * @internal
   */
  notify() {
    this.flags |= 16;
    if (!(this.flags & 8) && // avoid infinite self recursion
    activeSub !== this) {
      batch(this, true);
      return true;
    }
  }
  get value() {
    const link = this.dep.track({
      target: this,
      type: "get",
      key: "value"
    });
    refreshComputed(this);
    if (link) {
      link.version = this.dep.version;
    }
    return this._value;
  }
  set value(newValue) {
    if (this.setter) {
      this.setter(newValue);
    } else {
      warn$2("Write operation failed: computed value is readonly");
    }
  }
}
function computed$1(getterOrOptions, debugOptions, isSSR = false) {
  let getter;
  let setter;
  if (isFunction$2(getterOrOptions)) {
    getter = getterOrOptions;
  } else {
    getter = getterOrOptions.get;
    setter = getterOrOptions.set;
  }
  const cRef = new ComputedRefImpl(getter, setter, isSSR);
  return cRef;
}
const INITIAL_WATCHER_VALUE = {};
const cleanupMap = /* @__PURE__ */ new WeakMap();
let activeWatcher = void 0;
function onWatcherCleanup(cleanupFn, failSilently = false, owner = activeWatcher) {
  if (owner) {
    let cleanups = cleanupMap.get(owner);
    if (!cleanups) cleanupMap.set(owner, cleanups = []);
    cleanups.push(cleanupFn);
  } else if (!failSilently) {
    warn$2(
      `onWatcherCleanup() was called when there was no active watcher to associate with.`
    );
  }
}
function watch$1(source, cb, options = EMPTY_OBJ) {
  const { immediate, deep, once, scheduler, augmentJob, call } = options;
  const warnInvalidSource = (s) => {
    (options.onWarn || warn$2)(
      `Invalid watch source: `,
      s,
      `A watch source can only be a getter/effect function, a ref, a reactive object, or an array of these types.`
    );
  };
  const reactiveGetter = (source2) => {
    if (deep) return source2;
    if (isShallow(source2) || deep === false || deep === 0)
      return traverse(source2, 1);
    return traverse(source2);
  };
  let effect2;
  let getter;
  let cleanup;
  let boundCleanup;
  let forceTrigger = false;
  let isMultiSource = false;
  if (isRef(source)) {
    getter = () => source.value;
    forceTrigger = isShallow(source);
  } else if (isReactive(source)) {
    getter = () => reactiveGetter(source);
    forceTrigger = true;
  } else if (isArray$1(source)) {
    isMultiSource = true;
    forceTrigger = source.some((s) => isReactive(s) || isShallow(s));
    getter = () => source.map((s) => {
      if (isRef(s)) {
        return s.value;
      } else if (isReactive(s)) {
        return reactiveGetter(s);
      } else if (isFunction$2(s)) {
        return call ? call(s, 2) : s();
      } else {
        warnInvalidSource(s);
      }
    });
  } else if (isFunction$2(source)) {
    if (cb) {
      getter = call ? () => call(source, 2) : source;
    } else {
      getter = () => {
        if (cleanup) {
          pauseTracking();
          try {
            cleanup();
          } finally {
            resetTracking();
          }
        }
        const currentEffect = activeWatcher;
        activeWatcher = effect2;
        try {
          return call ? call(source, 3, [boundCleanup]) : source(boundCleanup);
        } finally {
          activeWatcher = currentEffect;
        }
      };
    }
  } else {
    getter = NOOP;
    warnInvalidSource(source);
  }
  if (cb && deep) {
    const baseGetter = getter;
    const depth = deep === true ? Infinity : deep;
    getter = () => traverse(baseGetter(), depth);
  }
  const scope = getCurrentScope();
  const watchHandle = () => {
    effect2.stop();
    if (scope && scope.active) {
      remove(scope.effects, effect2);
    }
  };
  if (once && cb) {
    const _cb = cb;
    cb = (...args) => {
      _cb(...args);
      watchHandle();
    };
  }
  let oldValue = isMultiSource ? new Array(source.length).fill(INITIAL_WATCHER_VALUE) : INITIAL_WATCHER_VALUE;
  const job = (immediateFirstRun) => {
    if (!(effect2.flags & 1) || !effect2.dirty && !immediateFirstRun) {
      return;
    }
    if (cb) {
      const newValue = effect2.run();
      if (deep || forceTrigger || (isMultiSource ? newValue.some((v, i) => hasChanged(v, oldValue[i])) : hasChanged(newValue, oldValue))) {
        if (cleanup) {
          cleanup();
        }
        const currentWatcher = activeWatcher;
        activeWatcher = effect2;
        try {
          const args = [
            newValue,
            // pass undefined as the old value when it's changed for the first time
            oldValue === INITIAL_WATCHER_VALUE ? void 0 : isMultiSource && oldValue[0] === INITIAL_WATCHER_VALUE ? [] : oldValue,
            boundCleanup
          ];
          oldValue = newValue;
          call ? call(cb, 3, args) : (
            // @ts-expect-error
            cb(...args)
          );
        } finally {
          activeWatcher = currentWatcher;
        }
      }
    } else {
      effect2.run();
    }
  };
  if (augmentJob) {
    augmentJob(job);
  }
  effect2 = new ReactiveEffect(getter);
  effect2.scheduler = scheduler ? () => scheduler(job, false) : job;
  boundCleanup = (fn) => onWatcherCleanup(fn, false, effect2);
  cleanup = effect2.onStop = () => {
    const cleanups = cleanupMap.get(effect2);
    if (cleanups) {
      if (call) {
        call(cleanups, 4);
      } else {
        for (const cleanup2 of cleanups) cleanup2();
      }
      cleanupMap.delete(effect2);
    }
  };
  {
    effect2.onTrack = options.onTrack;
    effect2.onTrigger = options.onTrigger;
  }
  if (cb) {
    if (immediate) {
      job(true);
    } else {
      oldValue = effect2.run();
    }
  } else if (scheduler) {
    scheduler(job.bind(null, true), true);
  } else {
    effect2.run();
  }
  watchHandle.pause = effect2.pause.bind(effect2);
  watchHandle.resume = effect2.resume.bind(effect2);
  watchHandle.stop = watchHandle;
  return watchHandle;
}
function traverse(value, depth = Infinity, seen) {
  if (depth <= 0 || !isObject$1(value) || value["__v_skip"]) {
    return value;
  }
  seen = seen || /* @__PURE__ */ new Map();
  if ((seen.get(value) || 0) >= depth) {
    return value;
  }
  seen.set(value, depth);
  depth--;
  if (isRef(value)) {
    traverse(value.value, depth, seen);
  } else if (isArray$1(value)) {
    for (let i = 0; i < value.length; i++) {
      traverse(value[i], depth, seen);
    }
  } else if (isSet(value) || isMap(value)) {
    value.forEach((v) => {
      traverse(v, depth, seen);
    });
  } else if (isPlainObject$1(value)) {
    for (const key in value) {
      traverse(value[key], depth, seen);
    }
    for (const key of Object.getOwnPropertySymbols(value)) {
      if (Object.prototype.propertyIsEnumerable.call(value, key)) {
        traverse(value[key], depth, seen);
      }
    }
  }
  return value;
}
/**
* @vue/runtime-core v3.5.25
* (c) 2018-present Yuxi (Evan) You and Vue contributors
* @license MIT
**/
const stack = [];
function pushWarningContext(vnode) {
  stack.push(vnode);
}
function popWarningContext() {
  stack.pop();
}
let isWarning = false;
function warn$1(msg, ...args) {
  if (isWarning) return;
  isWarning = true;
  pauseTracking();
  const instance = stack.length ? stack[stack.length - 1].component : null;
  const appWarnHandler = instance && instance.appContext.config.warnHandler;
  const trace = getComponentTrace();
  if (appWarnHandler) {
    callWithErrorHandling(
      appWarnHandler,
      instance,
      11,
      [
        // eslint-disable-next-line no-restricted-syntax
        msg + args.map((a) => {
          var _a, _b;
          return (_b = (_a = a.toString) == null ? void 0 : _a.call(a)) != null ? _b : JSON.stringify(a);
        }).join(""),
        instance && instance.proxy,
        trace.map(
          ({ vnode }) => `at <${formatComponentName(instance, vnode.type)}>`
        ).join("\n"),
        trace
      ]
    );
  } else {
    const warnArgs = [`[Vue warn]: ${msg}`, ...args];
    if (trace.length && // avoid spamming console during tests
    true) {
      warnArgs.push(`
`, ...formatTrace(trace));
    }
    console.warn(...warnArgs);
  }
  resetTracking();
  isWarning = false;
}
function getComponentTrace() {
  let currentVNode = stack[stack.length - 1];
  if (!currentVNode) {
    return [];
  }
  const normalizedStack = [];
  while (currentVNode) {
    const last = normalizedStack[0];
    if (last && last.vnode === currentVNode) {
      last.recurseCount++;
    } else {
      normalizedStack.push({
        vnode: currentVNode,
        recurseCount: 0
      });
    }
    const parentInstance = currentVNode.component && currentVNode.component.parent;
    currentVNode = parentInstance && parentInstance.vnode;
  }
  return normalizedStack;
}
function formatTrace(trace) {
  const logs = [];
  trace.forEach((entry, i) => {
    logs.push(...i === 0 ? [] : [`
`], ...formatTraceEntry(entry));
  });
  return logs;
}
function formatTraceEntry({ vnode, recurseCount }) {
  const postfix = recurseCount > 0 ? `... (${recurseCount} recursive calls)` : ``;
  const isRoot = vnode.component ? vnode.component.parent == null : false;
  const open = ` at <${formatComponentName(
    vnode.component,
    vnode.type,
    isRoot
  )}`;
  const close = `>` + postfix;
  return vnode.props ? [open, ...formatProps(vnode.props), close] : [open + close];
}
function formatProps(props) {
  const res = [];
  const keys = Object.keys(props);
  keys.slice(0, 3).forEach((key) => {
    res.push(...formatProp(key, props[key]));
  });
  if (keys.length > 3) {
    res.push(` ...`);
  }
  return res;
}
function formatProp(key, value, raw) {
  if (isString$1(value)) {
    value = JSON.stringify(value);
    return raw ? value : [`${key}=${value}`];
  } else if (typeof value === "number" || typeof value === "boolean" || value == null) {
    return raw ? value : [`${key}=${value}`];
  } else if (isRef(value)) {
    value = formatProp(key, toRaw(value.value), true);
    return raw ? value : [`${key}=Ref<`, value, `>`];
  } else if (isFunction$2(value)) {
    return [`${key}=fn${value.name ? `<${value.name}>` : ``}`];
  } else {
    value = toRaw(value);
    return raw ? value : [`${key}=`, value];
  }
}
function assertNumber(val, type) {
  if (val === void 0) {
    return;
  } else if (typeof val !== "number") {
    warn$1(`${type} is not a valid number - got ${JSON.stringify(val)}.`);
  } else if (isNaN(val)) {
    warn$1(`${type} is NaN - the duration expression might be incorrect.`);
  }
}
const ErrorTypeStrings$1 = {
  ["sp"]: "serverPrefetch hook",
  ["bc"]: "beforeCreate hook",
  ["c"]: "created hook",
  ["bm"]: "beforeMount hook",
  ["m"]: "mounted hook",
  ["bu"]: "beforeUpdate hook",
  ["u"]: "updated",
  ["bum"]: "beforeUnmount hook",
  ["um"]: "unmounted hook",
  ["a"]: "activated hook",
  ["da"]: "deactivated hook",
  ["ec"]: "errorCaptured hook",
  ["rtc"]: "renderTracked hook",
  ["rtg"]: "renderTriggered hook",
  [0]: "setup function",
  [1]: "render function",
  [2]: "watcher getter",
  [3]: "watcher callback",
  [4]: "watcher cleanup function",
  [5]: "native event handler",
  [6]: "component event handler",
  [7]: "vnode hook",
  [8]: "directive hook",
  [9]: "transition hook",
  [10]: "app errorHandler",
  [11]: "app warnHandler",
  [12]: "ref function",
  [13]: "async component loader",
  [14]: "scheduler flush",
  [15]: "component update",
  [16]: "app unmount cleanup function"
};
function callWithErrorHandling(fn, instance, type, args) {
  try {
    return args ? fn(...args) : fn();
  } catch (err) {
    handleError(err, instance, type);
  }
}
function callWithAsyncErrorHandling(fn, instance, type, args) {
  if (isFunction$2(fn)) {
    const res = callWithErrorHandling(fn, instance, type, args);
    if (res && isPromise(res)) {
      res.catch((err) => {
        handleError(err, instance, type);
      });
    }
    return res;
  }
  if (isArray$1(fn)) {
    const values = [];
    for (let i = 0; i < fn.length; i++) {
      values.push(callWithAsyncErrorHandling(fn[i], instance, type, args));
    }
    return values;
  } else {
    warn$1(
      `Invalid value type passed to callWithAsyncErrorHandling(): ${typeof fn}`
    );
  }
}
function handleError(err, instance, type, throwInDev = true) {
  const contextVNode = instance ? instance.vnode : null;
  const { errorHandler, throwUnhandledErrorInProduction } = instance && instance.appContext.config || EMPTY_OBJ;
  if (instance) {
    let cur = instance.parent;
    const exposedInstance = instance.proxy;
    const errorInfo = ErrorTypeStrings$1[type];
    while (cur) {
      const errorCapturedHooks = cur.ec;
      if (errorCapturedHooks) {
        for (let i = 0; i < errorCapturedHooks.length; i++) {
          if (errorCapturedHooks[i](err, exposedInstance, errorInfo) === false) {
            return;
          }
        }
      }
      cur = cur.parent;
    }
    if (errorHandler) {
      pauseTracking();
      callWithErrorHandling(errorHandler, null, 10, [
        err,
        exposedInstance,
        errorInfo
      ]);
      resetTracking();
      return;
    }
  }
  logError(err, type, contextVNode, throwInDev, throwUnhandledErrorInProduction);
}
function logError(err, type, contextVNode, throwInDev = true, throwInProd = false) {
  {
    const info = ErrorTypeStrings$1[type];
    if (contextVNode) {
      pushWarningContext(contextVNode);
    }
    warn$1(`Unhandled error${info ? ` during execution of ${info}` : ``}`);
    if (contextVNode) {
      popWarningContext();
    }
    if (throwInDev) {
      throw err;
    } else {
      console.error(err);
    }
  }
}
const queue$1 = [];
let flushIndex = -1;
const pendingPostFlushCbs = [];
let activePostFlushCbs = null;
let postFlushIndex = 0;
const resolvedPromise = /* @__PURE__ */ Promise.resolve();
let currentFlushPromise = null;
const RECURSION_LIMIT = 100;
function nextTick(fn) {
  const p2 = currentFlushPromise || resolvedPromise;
  return fn ? p2.then(this ? fn.bind(this) : fn) : p2;
}
function findInsertionIndex(id) {
  let start = flushIndex + 1;
  let end = queue$1.length;
  while (start < end) {
    const middle = start + end >>> 1;
    const middleJob = queue$1[middle];
    const middleJobId = getId(middleJob);
    if (middleJobId < id || middleJobId === id && middleJob.flags & 2) {
      start = middle + 1;
    } else {
      end = middle;
    }
  }
  return start;
}
function queueJob(job) {
  if (!(job.flags & 1)) {
    const jobId = getId(job);
    const lastJob = queue$1[queue$1.length - 1];
    if (!lastJob || // fast path when the job id is larger than the tail
    !(job.flags & 2) && jobId >= getId(lastJob)) {
      queue$1.push(job);
    } else {
      queue$1.splice(findInsertionIndex(jobId), 0, job);
    }
    job.flags |= 1;
    queueFlush();
  }
}
function queueFlush() {
  if (!currentFlushPromise) {
    currentFlushPromise = resolvedPromise.then(flushJobs);
  }
}
function queuePostFlushCb(cb) {
  if (!isArray$1(cb)) {
    if (activePostFlushCbs && cb.id === -1) {
      activePostFlushCbs.splice(postFlushIndex + 1, 0, cb);
    } else if (!(cb.flags & 1)) {
      pendingPostFlushCbs.push(cb);
      cb.flags |= 1;
    }
  } else {
    pendingPostFlushCbs.push(...cb);
  }
  queueFlush();
}
function flushPreFlushCbs(instance, seen, i = flushIndex + 1) {
  {
    seen = seen || /* @__PURE__ */ new Map();
  }
  for (; i < queue$1.length; i++) {
    const cb = queue$1[i];
    if (cb && cb.flags & 2) {
      if (instance && cb.id !== instance.uid) {
        continue;
      }
      if (checkRecursiveUpdates(seen, cb)) {
        continue;
      }
      queue$1.splice(i, 1);
      i--;
      if (cb.flags & 4) {
        cb.flags &= -2;
      }
      cb();
      if (!(cb.flags & 4)) {
        cb.flags &= -2;
      }
    }
  }
}
function flushPostFlushCbs(seen) {
  if (pendingPostFlushCbs.length) {
    const deduped = [...new Set(pendingPostFlushCbs)].sort(
      (a, b) => getId(a) - getId(b)
    );
    pendingPostFlushCbs.length = 0;
    if (activePostFlushCbs) {
      activePostFlushCbs.push(...deduped);
      return;
    }
    activePostFlushCbs = deduped;
    {
      seen = seen || /* @__PURE__ */ new Map();
    }
    for (postFlushIndex = 0; postFlushIndex < activePostFlushCbs.length; postFlushIndex++) {
      const cb = activePostFlushCbs[postFlushIndex];
      if (checkRecursiveUpdates(seen, cb)) {
        continue;
      }
      if (cb.flags & 4) {
        cb.flags &= -2;
      }
      if (!(cb.flags & 8)) cb();
      cb.flags &= -2;
    }
    activePostFlushCbs = null;
    postFlushIndex = 0;
  }
}
const getId = (job) => job.id == null ? job.flags & 2 ? -1 : Infinity : job.id;
function flushJobs(seen) {
  {
    seen = seen || /* @__PURE__ */ new Map();
  }
  const check = (job) => checkRecursiveUpdates(seen, job);
  try {
    for (flushIndex = 0; flushIndex < queue$1.length; flushIndex++) {
      const job = queue$1[flushIndex];
      if (job && !(job.flags & 8)) {
        if (check(job)) {
          continue;
        }
        if (job.flags & 4) {
          job.flags &= ~1;
        }
        callWithErrorHandling(
          job,
          job.i,
          job.i ? 15 : 14
        );
        if (!(job.flags & 4)) {
          job.flags &= ~1;
        }
      }
    }
  } finally {
    for (; flushIndex < queue$1.length; flushIndex++) {
      const job = queue$1[flushIndex];
      if (job) {
        job.flags &= -2;
      }
    }
    flushIndex = -1;
    queue$1.length = 0;
    flushPostFlushCbs(seen);
    currentFlushPromise = null;
    if (queue$1.length || pendingPostFlushCbs.length) {
      flushJobs(seen);
    }
  }
}
function checkRecursiveUpdates(seen, fn) {
  const count = seen.get(fn) || 0;
  if (count > RECURSION_LIMIT) {
    const instance = fn.i;
    const componentName = instance && getComponentName(instance.type);
    handleError(
      `Maximum recursive updates exceeded${componentName ? ` in component <${componentName}>` : ``}. This means you have a reactive effect that is mutating its own dependencies and thus recursively triggering itself. Possible sources include component template, render function, updated hook or watcher source function.`,
      null,
      10
    );
    return true;
  }
  seen.set(fn, count + 1);
  return false;
}
let isHmrUpdating = false;
const hmrDirtyComponents = /* @__PURE__ */ new Map();
{
  getGlobalThis().__VUE_HMR_RUNTIME__ = {
    createRecord: tryWrap(createRecord),
    rerender: tryWrap(rerender),
    reload: tryWrap(reload)
  };
}
const map = /* @__PURE__ */ new Map();
function registerHMR(instance) {
  const id = instance.type.__hmrId;
  let record = map.get(id);
  if (!record) {
    createRecord(id, instance.type);
    record = map.get(id);
  }
  record.instances.add(instance);
}
function unregisterHMR(instance) {
  map.get(instance.type.__hmrId).instances.delete(instance);
}
function createRecord(id, initialDef) {
  if (map.has(id)) {
    return false;
  }
  map.set(id, {
    initialDef: normalizeClassComponent(initialDef),
    instances: /* @__PURE__ */ new Set()
  });
  return true;
}
function normalizeClassComponent(component) {
  return isClassComponent(component) ? component.__vccOpts : component;
}
function rerender(id, newRender) {
  const record = map.get(id);
  if (!record) {
    return;
  }
  record.initialDef.render = newRender;
  [...record.instances].forEach((instance) => {
    if (newRender) {
      instance.render = newRender;
      normalizeClassComponent(instance.type).render = newRender;
    }
    instance.renderCache = [];
    isHmrUpdating = true;
    if (!(instance.job.flags & 8)) {
      instance.update();
    }
    isHmrUpdating = false;
  });
}
function reload(id, newComp) {
  const record = map.get(id);
  if (!record) return;
  newComp = normalizeClassComponent(newComp);
  updateComponentDef(record.initialDef, newComp);
  const instances = [...record.instances];
  for (let i = 0; i < instances.length; i++) {
    const instance = instances[i];
    const oldComp = normalizeClassComponent(instance.type);
    let dirtyInstances = hmrDirtyComponents.get(oldComp);
    if (!dirtyInstances) {
      if (oldComp !== record.initialDef) {
        updateComponentDef(oldComp, newComp);
      }
      hmrDirtyComponents.set(oldComp, dirtyInstances = /* @__PURE__ */ new Set());
    }
    dirtyInstances.add(instance);
    instance.appContext.propsCache.delete(instance.type);
    instance.appContext.emitsCache.delete(instance.type);
    instance.appContext.optionsCache.delete(instance.type);
    if (instance.ceReload) {
      dirtyInstances.add(instance);
      instance.ceReload(newComp.styles);
      dirtyInstances.delete(instance);
    } else if (instance.parent) {
      queueJob(() => {
        if (!(instance.job.flags & 8)) {
          isHmrUpdating = true;
          instance.parent.update();
          isHmrUpdating = false;
          dirtyInstances.delete(instance);
        }
      });
    } else if (instance.appContext.reload) {
      instance.appContext.reload();
    } else if (typeof window !== "undefined") {
      window.location.reload();
    } else {
      console.warn(
        "[HMR] Root or manually mounted instance modified. Full reload required."
      );
    }
    if (instance.root.ce && instance !== instance.root) {
      instance.root.ce._removeChildStyle(oldComp);
    }
  }
  queuePostFlushCb(() => {
    hmrDirtyComponents.clear();
  });
}
function updateComponentDef(oldComp, newComp) {
  extend$1(oldComp, newComp);
  for (const key in oldComp) {
    if (key !== "__file" && !(key in newComp)) {
      delete oldComp[key];
    }
  }
}
function tryWrap(fn) {
  return (id, arg) => {
    try {
      return fn(id, arg);
    } catch (e) {
      console.error(e);
      console.warn(
        `[HMR] Something went wrong during Vue component hot-reload. Full reload required.`
      );
    }
  };
}
let devtools$1;
let buffer$1 = [];
let devtoolsNotInstalled = false;
function emit$1(event, ...args) {
  if (devtools$1) {
    devtools$1.emit(event, ...args);
  } else if (!devtoolsNotInstalled) {
    buffer$1.push({ event, args });
  }
}
function setDevtoolsHook$1(hook, target) {
  var _a, _b;
  devtools$1 = hook;
  if (devtools$1) {
    devtools$1.enabled = true;
    buffer$1.forEach(({ event, args }) => devtools$1.emit(event, ...args));
    buffer$1 = [];
  } else if (
    // handle late devtools injection - only do this if we are in an actual
    // browser environment to avoid the timer handle stalling test runner exit
    // (#4815)
    typeof window !== "undefined" && // some envs mock window but not fully
    window.HTMLElement && // also exclude jsdom
    // eslint-disable-next-line no-restricted-syntax
    !((_b = (_a = window.navigator) == null ? void 0 : _a.userAgent) == null ? void 0 : _b.includes("jsdom"))
  ) {
    const replay = target.__VUE_DEVTOOLS_HOOK_REPLAY__ = target.__VUE_DEVTOOLS_HOOK_REPLAY__ || [];
    replay.push((newHook) => {
      setDevtoolsHook$1(newHook, target);
    });
    setTimeout(() => {
      if (!devtools$1) {
        target.__VUE_DEVTOOLS_HOOK_REPLAY__ = null;
        devtoolsNotInstalled = true;
        buffer$1 = [];
      }
    }, 3e3);
  } else {
    devtoolsNotInstalled = true;
    buffer$1 = [];
  }
}
function devtoolsInitApp(app, version2) {
  emit$1("app:init", app, version2, {
    Fragment,
    Text,
    Comment,
    Static
  });
}
function devtoolsUnmountApp(app) {
  emit$1("app:unmount", app);
}
const devtoolsComponentAdded = /* @__PURE__ */ createDevtoolsComponentHook(
  "component:added"
  /* COMPONENT_ADDED */
);
const devtoolsComponentUpdated = /* @__PURE__ */ createDevtoolsComponentHook(
  "component:updated"
  /* COMPONENT_UPDATED */
);
const _devtoolsComponentRemoved = /* @__PURE__ */ createDevtoolsComponentHook(
  "component:removed"
  /* COMPONENT_REMOVED */
);
const devtoolsComponentRemoved = (component) => {
  if (devtools$1 && typeof devtools$1.cleanupBuffer === "function" && // remove the component if it wasn't buffered
  !devtools$1.cleanupBuffer(component)) {
    _devtoolsComponentRemoved(component);
  }
};
// @__NO_SIDE_EFFECTS__
function createDevtoolsComponentHook(hook) {
  return (component) => {
    emit$1(
      hook,
      component.appContext.app,
      component.uid,
      component.parent ? component.parent.uid : void 0,
      component
    );
  };
}
const devtoolsPerfStart = /* @__PURE__ */ createDevtoolsPerformanceHook(
  "perf:start"
  /* PERFORMANCE_START */
);
const devtoolsPerfEnd = /* @__PURE__ */ createDevtoolsPerformanceHook(
  "perf:end"
  /* PERFORMANCE_END */
);
function createDevtoolsPerformanceHook(hook) {
  return (component, type, time) => {
    emit$1(hook, component.appContext.app, component.uid, component, type, time);
  };
}
function devtoolsComponentEmit(component, event, params) {
  emit$1(
    "component:emit",
    component.appContext.app,
    component,
    event,
    params
  );
}
let currentRenderingInstance = null;
let currentScopeId = null;
function setCurrentRenderingInstance(instance) {
  const prev = currentRenderingInstance;
  currentRenderingInstance = instance;
  currentScopeId = instance && instance.type.__scopeId || null;
  return prev;
}
function pushScopeId(id) {
  currentScopeId = id;
}
function popScopeId() {
  currentScopeId = null;
}
const withScopeId = (_id) => withCtx;
function withCtx(fn, ctx = currentRenderingInstance, isNonScopedSlot) {
  if (!ctx) return fn;
  if (fn._n) {
    return fn;
  }
  const renderFnWithContext = (...args) => {
    if (renderFnWithContext._d) {
      setBlockTracking(-1);
    }
    const prevInstance = setCurrentRenderingInstance(ctx);
    let res;
    try {
      res = fn(...args);
    } finally {
      setCurrentRenderingInstance(prevInstance);
      if (renderFnWithContext._d) {
        setBlockTracking(1);
      }
    }
    {
      devtoolsComponentUpdated(ctx);
    }
    return res;
  };
  renderFnWithContext._n = true;
  renderFnWithContext._c = true;
  renderFnWithContext._d = true;
  return renderFnWithContext;
}
function validateDirectiveName(name) {
  if (isBuiltInDirective(name)) {
    warn$1("Do not use built-in directive ids as custom directive id: " + name);
  }
}
function withDirectives(vnode, directives) {
  if (currentRenderingInstance === null) {
    warn$1(`withDirectives can only be used inside render functions.`);
    return vnode;
  }
  const instance = getComponentPublicInstance(currentRenderingInstance);
  const bindings = vnode.dirs || (vnode.dirs = []);
  for (let i = 0; i < directives.length; i++) {
    let [dir, value, arg, modifiers = EMPTY_OBJ] = directives[i];
    if (dir) {
      if (isFunction$2(dir)) {
        dir = {
          mounted: dir,
          updated: dir
        };
      }
      if (dir.deep) {
        traverse(value);
      }
      bindings.push({
        dir,
        instance,
        value,
        oldValue: void 0,
        arg,
        modifiers
      });
    }
  }
  return vnode;
}
function invokeDirectiveHook(vnode, prevVNode, instance, name) {
  const bindings = vnode.dirs;
  const oldBindings = prevVNode && prevVNode.dirs;
  for (let i = 0; i < bindings.length; i++) {
    const binding = bindings[i];
    if (oldBindings) {
      binding.oldValue = oldBindings[i].value;
    }
    let hook = binding.dir[name];
    if (hook) {
      pauseTracking();
      callWithAsyncErrorHandling(hook, instance, 8, [
        vnode.el,
        binding,
        vnode,
        prevVNode
      ]);
      resetTracking();
    }
  }
}
const TeleportEndKey = Symbol("_vte");
const isTeleport = (type) => type.__isTeleport;
const isTeleportDisabled = (props) => props && (props.disabled || props.disabled === "");
const isTeleportDeferred = (props) => props && (props.defer || props.defer === "");
const isTargetSVG = (target) => typeof SVGElement !== "undefined" && target instanceof SVGElement;
const isTargetMathML = (target) => typeof MathMLElement === "function" && target instanceof MathMLElement;
const resolveTarget = (props, select) => {
  const targetSelector = props && props.to;
  if (isString$1(targetSelector)) {
    if (!select) {
      warn$1(
        `Current renderer does not support string target for Teleports. (missing querySelector renderer option)`
      );
      return null;
    } else {
      const target = select(targetSelector);
      if (!target && !isTeleportDisabled(props)) {
        warn$1(
          `Failed to locate Teleport target with selector "${targetSelector}". Note the target element must exist before the component is mounted - i.e. the target cannot be rendered by the component itself, and ideally should be outside of the entire Vue component tree.`
        );
      }
      return target;
    }
  } else {
    if (!targetSelector && !isTeleportDisabled(props)) {
      warn$1(`Invalid Teleport target: ${targetSelector}`);
    }
    return targetSelector;
  }
};
const TeleportImpl = {
  name: "Teleport",
  __isTeleport: true,
  process(n1, n2, container, anchor, parentComponent, parentSuspense, namespace, slotScopeIds, optimized, internals) {
    const {
      mc: mountChildren,
      pc: patchChildren,
      pbc: patchBlockChildren,
      o: { insert, querySelector, createText, createComment }
    } = internals;
    const disabled = isTeleportDisabled(n2.props);
    let { shapeFlag, children, dynamicChildren } = n2;
    if (isHmrUpdating) {
      optimized = false;
      dynamicChildren = null;
    }
    if (n1 == null) {
      const placeholder = n2.el = createComment("teleport start");
      const mainAnchor = n2.anchor = createComment("teleport end");
      insert(placeholder, container, anchor);
      insert(mainAnchor, container, anchor);
      const mount = (container2, anchor2) => {
        if (shapeFlag & 16) {
          mountChildren(
            children,
            container2,
            anchor2,
            parentComponent,
            parentSuspense,
            namespace,
            slotScopeIds,
            optimized
          );
        }
      };
      const mountToTarget = () => {
        const target = n2.target = resolveTarget(n2.props, querySelector);
        const targetAnchor = prepareAnchor(target, n2, createText, insert);
        if (target) {
          if (namespace !== "svg" && isTargetSVG(target)) {
            namespace = "svg";
          } else if (namespace !== "mathml" && isTargetMathML(target)) {
            namespace = "mathml";
          }
          if (parentComponent && parentComponent.isCE) {
            (parentComponent.ce._teleportTargets || (parentComponent.ce._teleportTargets = /* @__PURE__ */ new Set())).add(target);
          }
          if (!disabled) {
            mount(target, targetAnchor);
            updateCssVars(n2, false);
          }
        } else if (!disabled) {
          warn$1(
            "Invalid Teleport target on mount:",
            target,
            `(${typeof target})`
          );
        }
      };
      if (disabled) {
        mount(container, mainAnchor);
        updateCssVars(n2, true);
      }
      if (isTeleportDeferred(n2.props)) {
        n2.el.__isMounted = false;
        queuePostRenderEffect(() => {
          mountToTarget();
          delete n2.el.__isMounted;
        }, parentSuspense);
      } else {
        mountToTarget();
      }
    } else {
      if (isTeleportDeferred(n2.props) && n1.el.__isMounted === false) {
        queuePostRenderEffect(() => {
          TeleportImpl.process(
            n1,
            n2,
            container,
            anchor,
            parentComponent,
            parentSuspense,
            namespace,
            slotScopeIds,
            optimized,
            internals
          );
        }, parentSuspense);
        return;
      }
      n2.el = n1.el;
      n2.targetStart = n1.targetStart;
      const mainAnchor = n2.anchor = n1.anchor;
      const target = n2.target = n1.target;
      const targetAnchor = n2.targetAnchor = n1.targetAnchor;
      const wasDisabled = isTeleportDisabled(n1.props);
      const currentContainer = wasDisabled ? container : target;
      const currentAnchor = wasDisabled ? mainAnchor : targetAnchor;
      if (namespace === "svg" || isTargetSVG(target)) {
        namespace = "svg";
      } else if (namespace === "mathml" || isTargetMathML(target)) {
        namespace = "mathml";
      }
      if (dynamicChildren) {
        patchBlockChildren(
          n1.dynamicChildren,
          dynamicChildren,
          currentContainer,
          parentComponent,
          parentSuspense,
          namespace,
          slotScopeIds
        );
        traverseStaticChildren(n1, n2, false);
      } else if (!optimized) {
        patchChildren(
          n1,
          n2,
          currentContainer,
          currentAnchor,
          parentComponent,
          parentSuspense,
          namespace,
          slotScopeIds,
          false
        );
      }
      if (disabled) {
        if (!wasDisabled) {
          moveTeleport(
            n2,
            container,
            mainAnchor,
            internals,
            1
          );
        } else {
          if (n2.props && n1.props && n2.props.to !== n1.props.to) {
            n2.props.to = n1.props.to;
          }
        }
      } else {
        if ((n2.props && n2.props.to) !== (n1.props && n1.props.to)) {
          const nextTarget = n2.target = resolveTarget(
            n2.props,
            querySelector
          );
          if (nextTarget) {
            moveTeleport(
              n2,
              nextTarget,
              null,
              internals,
              0
            );
          } else {
            warn$1(
              "Invalid Teleport target on update:",
              target,
              `(${typeof target})`
            );
          }
        } else if (wasDisabled) {
          moveTeleport(
            n2,
            target,
            targetAnchor,
            internals,
            1
          );
        }
      }
      updateCssVars(n2, disabled);
    }
  },
  remove(vnode, parentComponent, parentSuspense, { um: unmount, o: { remove: hostRemove } }, doRemove) {
    const {
      shapeFlag,
      children,
      anchor,
      targetStart,
      targetAnchor,
      target,
      props
    } = vnode;
    if (target) {
      hostRemove(targetStart);
      hostRemove(targetAnchor);
    }
    doRemove && hostRemove(anchor);
    if (shapeFlag & 16) {
      const shouldRemove = doRemove || !isTeleportDisabled(props);
      for (let i = 0; i < children.length; i++) {
        const child = children[i];
        unmount(
          child,
          parentComponent,
          parentSuspense,
          shouldRemove,
          !!child.dynamicChildren
        );
      }
    }
  },
  move: moveTeleport,
  hydrate: hydrateTeleport
};
function moveTeleport(vnode, container, parentAnchor, { o: { insert }, m: move }, moveType = 2) {
  if (moveType === 0) {
    insert(vnode.targetAnchor, container, parentAnchor);
  }
  const { el, anchor, shapeFlag, children, props } = vnode;
  const isReorder = moveType === 2;
  if (isReorder) {
    insert(el, container, parentAnchor);
  }
  if (!isReorder || isTeleportDisabled(props)) {
    if (shapeFlag & 16) {
      for (let i = 0; i < children.length; i++) {
        move(
          children[i],
          container,
          parentAnchor,
          2
        );
      }
    }
  }
  if (isReorder) {
    insert(anchor, container, parentAnchor);
  }
}
function hydrateTeleport(node, vnode, parentComponent, parentSuspense, slotScopeIds, optimized, {
  o: { nextSibling, parentNode, querySelector, insert, createText }
}, hydrateChildren) {
  function hydrateDisabledTeleport(node2, vnode2, targetStart, targetAnchor) {
    vnode2.anchor = hydrateChildren(
      nextSibling(node2),
      vnode2,
      parentNode(node2),
      parentComponent,
      parentSuspense,
      slotScopeIds,
      optimized
    );
    vnode2.targetStart = targetStart;
    vnode2.targetAnchor = targetAnchor;
  }
  const target = vnode.target = resolveTarget(
    vnode.props,
    querySelector
  );
  const disabled = isTeleportDisabled(vnode.props);
  if (target) {
    const targetNode = target._lpa || target.firstChild;
    if (vnode.shapeFlag & 16) {
      if (disabled) {
        hydrateDisabledTeleport(
          node,
          vnode,
          targetNode,
          targetNode && nextSibling(targetNode)
        );
      } else {
        vnode.anchor = nextSibling(node);
        let targetAnchor = targetNode;
        while (targetAnchor) {
          if (targetAnchor && targetAnchor.nodeType === 8) {
            if (targetAnchor.data === "teleport start anchor") {
              vnode.targetStart = targetAnchor;
            } else if (targetAnchor.data === "teleport anchor") {
              vnode.targetAnchor = targetAnchor;
              target._lpa = vnode.targetAnchor && nextSibling(vnode.targetAnchor);
              break;
            }
          }
          targetAnchor = nextSibling(targetAnchor);
        }
        if (!vnode.targetAnchor) {
          prepareAnchor(target, vnode, createText, insert);
        }
        hydrateChildren(
          targetNode && nextSibling(targetNode),
          vnode,
          target,
          parentComponent,
          parentSuspense,
          slotScopeIds,
          optimized
        );
      }
    }
    updateCssVars(vnode, disabled);
  } else if (disabled) {
    if (vnode.shapeFlag & 16) {
      hydrateDisabledTeleport(node, vnode, node, nextSibling(node));
    }
  }
  return vnode.anchor && nextSibling(vnode.anchor);
}
const Teleport = TeleportImpl;
function updateCssVars(vnode, isDisabled) {
  const ctx = vnode.ctx;
  if (ctx && ctx.ut) {
    let node, anchor;
    if (isDisabled) {
      node = vnode.el;
      anchor = vnode.anchor;
    } else {
      node = vnode.targetStart;
      anchor = vnode.targetAnchor;
    }
    while (node && node !== anchor) {
      if (node.nodeType === 1) node.setAttribute("data-v-owner", ctx.uid);
      node = node.nextSibling;
    }
    ctx.ut();
  }
}
function prepareAnchor(target, vnode, createText, insert) {
  const targetStart = vnode.targetStart = createText("");
  const targetAnchor = vnode.targetAnchor = createText("");
  targetStart[TeleportEndKey] = targetAnchor;
  if (target) {
    insert(targetStart, target);
    insert(targetAnchor, target);
  }
  return targetAnchor;
}
const leaveCbKey = Symbol("_leaveCb");
const enterCbKey = Symbol("_enterCb");
function useTransitionState() {
  const state = {
    isMounted: false,
    isLeaving: false,
    isUnmounting: false,
    leavingVNodes: /* @__PURE__ */ new Map()
  };
  onMounted(() => {
    state.isMounted = true;
  });
  onBeforeUnmount(() => {
    state.isUnmounting = true;
  });
  return state;
}
const TransitionHookValidator = [Function, Array];
const BaseTransitionPropsValidators = {
  mode: String,
  appear: Boolean,
  persisted: Boolean,
  // enter
  onBeforeEnter: TransitionHookValidator,
  onEnter: TransitionHookValidator,
  onAfterEnter: TransitionHookValidator,
  onEnterCancelled: TransitionHookValidator,
  // leave
  onBeforeLeave: TransitionHookValidator,
  onLeave: TransitionHookValidator,
  onAfterLeave: TransitionHookValidator,
  onLeaveCancelled: TransitionHookValidator,
  // appear
  onBeforeAppear: TransitionHookValidator,
  onAppear: TransitionHookValidator,
  onAfterAppear: TransitionHookValidator,
  onAppearCancelled: TransitionHookValidator
};
const recursiveGetSubtree = (instance) => {
  const subTree = instance.subTree;
  return subTree.component ? recursiveGetSubtree(subTree.component) : subTree;
};
const BaseTransitionImpl = {
  name: `BaseTransition`,
  props: BaseTransitionPropsValidators,
  setup(props, { slots }) {
    const instance = getCurrentInstance();
    const state = useTransitionState();
    return () => {
      const children = slots.default && getTransitionRawChildren(slots.default(), true);
      if (!children || !children.length) {
        return;
      }
      const child = findNonCommentChild(children);
      const rawProps = toRaw(props);
      const { mode } = rawProps;
      if (mode && mode !== "in-out" && mode !== "out-in" && mode !== "default") {
        warn$1(`invalid <transition> mode: ${mode}`);
      }
      if (state.isLeaving) {
        return emptyPlaceholder(child);
      }
      const innerChild = getInnerChild$1(child);
      if (!innerChild) {
        return emptyPlaceholder(child);
      }
      let enterHooks = resolveTransitionHooks(
        innerChild,
        rawProps,
        state,
        instance,
        // #11061, ensure enterHooks is fresh after clone
        (hooks) => enterHooks = hooks
      );
      if (innerChild.type !== Comment) {
        setTransitionHooks(innerChild, enterHooks);
      }
      let oldInnerChild = instance.subTree && getInnerChild$1(instance.subTree);
      if (oldInnerChild && oldInnerChild.type !== Comment && !isSameVNodeType(oldInnerChild, innerChild) && recursiveGetSubtree(instance).type !== Comment) {
        let leavingHooks = resolveTransitionHooks(
          oldInnerChild,
          rawProps,
          state,
          instance
        );
        setTransitionHooks(oldInnerChild, leavingHooks);
        if (mode === "out-in" && innerChild.type !== Comment) {
          state.isLeaving = true;
          leavingHooks.afterLeave = () => {
            state.isLeaving = false;
            if (!(instance.job.flags & 8)) {
              instance.update();
            }
            delete leavingHooks.afterLeave;
            oldInnerChild = void 0;
          };
          return emptyPlaceholder(child);
        } else if (mode === "in-out" && innerChild.type !== Comment) {
          leavingHooks.delayLeave = (el, earlyRemove, delayedLeave) => {
            const leavingVNodesCache = getLeavingNodesForType(
              state,
              oldInnerChild
            );
            leavingVNodesCache[String(oldInnerChild.key)] = oldInnerChild;
            el[leaveCbKey] = () => {
              earlyRemove();
              el[leaveCbKey] = void 0;
              delete enterHooks.delayedLeave;
              oldInnerChild = void 0;
            };
            enterHooks.delayedLeave = () => {
              delayedLeave();
              delete enterHooks.delayedLeave;
              oldInnerChild = void 0;
            };
          };
        } else {
          oldInnerChild = void 0;
        }
      } else if (oldInnerChild) {
        oldInnerChild = void 0;
      }
      return child;
    };
  }
};
function findNonCommentChild(children) {
  let child = children[0];
  if (children.length > 1) {
    let hasFound = false;
    for (const c of children) {
      if (c.type !== Comment) {
        if (hasFound) {
          warn$1(
            "<transition> can only be used on a single element or component. Use <transition-group> for lists."
          );
          break;
        }
        child = c;
        hasFound = true;
      }
    }
  }
  return child;
}
const BaseTransition = BaseTransitionImpl;
function getLeavingNodesForType(state, vnode) {
  const { leavingVNodes } = state;
  let leavingVNodesCache = leavingVNodes.get(vnode.type);
  if (!leavingVNodesCache) {
    leavingVNodesCache = /* @__PURE__ */ Object.create(null);
    leavingVNodes.set(vnode.type, leavingVNodesCache);
  }
  return leavingVNodesCache;
}
function resolveTransitionHooks(vnode, props, state, instance, postClone) {
  const {
    appear,
    mode,
    persisted = false,
    onBeforeEnter,
    onEnter,
    onAfterEnter,
    onEnterCancelled,
    onBeforeLeave,
    onLeave,
    onAfterLeave,
    onLeaveCancelled,
    onBeforeAppear,
    onAppear,
    onAfterAppear,
    onAppearCancelled
  } = props;
  const key = String(vnode.key);
  const leavingVNodesCache = getLeavingNodesForType(state, vnode);
  const callHook2 = (hook, args) => {
    hook && callWithAsyncErrorHandling(
      hook,
      instance,
      9,
      args
    );
  };
  const callAsyncHook = (hook, args) => {
    const done = args[1];
    callHook2(hook, args);
    if (isArray$1(hook)) {
      if (hook.every((hook2) => hook2.length <= 1)) done();
    } else if (hook.length <= 1) {
      done();
    }
  };
  const hooks = {
    mode,
    persisted,
    beforeEnter(el) {
      let hook = onBeforeEnter;
      if (!state.isMounted) {
        if (appear) {
          hook = onBeforeAppear || onBeforeEnter;
        } else {
          return;
        }
      }
      if (el[leaveCbKey]) {
        el[leaveCbKey](
          true
          /* cancelled */
        );
      }
      const leavingVNode = leavingVNodesCache[key];
      if (leavingVNode && isSameVNodeType(vnode, leavingVNode) && leavingVNode.el[leaveCbKey]) {
        leavingVNode.el[leaveCbKey]();
      }
      callHook2(hook, [el]);
    },
    enter(el) {
      let hook = onEnter;
      let afterHook = onAfterEnter;
      let cancelHook = onEnterCancelled;
      if (!state.isMounted) {
        if (appear) {
          hook = onAppear || onEnter;
          afterHook = onAfterAppear || onAfterEnter;
          cancelHook = onAppearCancelled || onEnterCancelled;
        } else {
          return;
        }
      }
      let called = false;
      const done = el[enterCbKey] = (cancelled) => {
        if (called) return;
        called = true;
        if (cancelled) {
          callHook2(cancelHook, [el]);
        } else {
          callHook2(afterHook, [el]);
        }
        if (hooks.delayedLeave) {
          hooks.delayedLeave();
        }
        el[enterCbKey] = void 0;
      };
      if (hook) {
        callAsyncHook(hook, [el, done]);
      } else {
        done();
      }
    },
    leave(el, remove2) {
      const key2 = String(vnode.key);
      if (el[enterCbKey]) {
        el[enterCbKey](
          true
          /* cancelled */
        );
      }
      if (state.isUnmounting) {
        return remove2();
      }
      callHook2(onBeforeLeave, [el]);
      let called = false;
      const done = el[leaveCbKey] = (cancelled) => {
        if (called) return;
        called = true;
        remove2();
        if (cancelled) {
          callHook2(onLeaveCancelled, [el]);
        } else {
          callHook2(onAfterLeave, [el]);
        }
        el[leaveCbKey] = void 0;
        if (leavingVNodesCache[key2] === vnode) {
          delete leavingVNodesCache[key2];
        }
      };
      leavingVNodesCache[key2] = vnode;
      if (onLeave) {
        callAsyncHook(onLeave, [el, done]);
      } else {
        done();
      }
    },
    clone(vnode2) {
      const hooks2 = resolveTransitionHooks(
        vnode2,
        props,
        state,
        instance,
        postClone
      );
      if (postClone) postClone(hooks2);
      return hooks2;
    }
  };
  return hooks;
}
function emptyPlaceholder(vnode) {
  if (isKeepAlive(vnode)) {
    vnode = cloneVNode(vnode);
    vnode.children = null;
    return vnode;
  }
}
function getInnerChild$1(vnode) {
  if (!isKeepAlive(vnode)) {
    if (isTeleport(vnode.type) && vnode.children) {
      return findNonCommentChild(vnode.children);
    }
    return vnode;
  }
  if (vnode.component) {
    return vnode.component.subTree;
  }
  const { shapeFlag, children } = vnode;
  if (children) {
    if (shapeFlag & 16) {
      return children[0];
    }
    if (shapeFlag & 32 && isFunction$2(children.default)) {
      return children.default();
    }
  }
}
function setTransitionHooks(vnode, hooks) {
  if (vnode.shapeFlag & 6 && vnode.component) {
    vnode.transition = hooks;
    setTransitionHooks(vnode.component.subTree, hooks);
  } else if (vnode.shapeFlag & 128) {
    vnode.ssContent.transition = hooks.clone(vnode.ssContent);
    vnode.ssFallback.transition = hooks.clone(vnode.ssFallback);
  } else {
    vnode.transition = hooks;
  }
}
function getTransitionRawChildren(children, keepComment = false, parentKey) {
  let ret = [];
  let keyedFragmentCount = 0;
  for (let i = 0; i < children.length; i++) {
    let child = children[i];
    const key = parentKey == null ? child.key : String(parentKey) + String(child.key != null ? child.key : i);
    if (child.type === Fragment) {
      if (child.patchFlag & 128) keyedFragmentCount++;
      ret = ret.concat(
        getTransitionRawChildren(child.children, keepComment, key)
      );
    } else if (keepComment || child.type !== Comment) {
      ret.push(key != null ? cloneVNode(child, { key }) : child);
    }
  }
  if (keyedFragmentCount > 1) {
    for (let i = 0; i < ret.length; i++) {
      ret[i].patchFlag = -2;
    }
  }
  return ret;
}
// @__NO_SIDE_EFFECTS__
function defineComponent(options, extraOptions) {
  return isFunction$2(options) ? (
    // #8236: extend call and options.name access are considered side-effects
    // by Rollup, so we have to wrap it in a pure-annotated IIFE.
    /* @__PURE__ */ (() => extend$1({ name: options.name }, extraOptions, { setup: options }))()
  ) : options;
}
function markAsyncBoundary(instance) {
  instance.ids = [instance.ids[0] + instance.ids[2]++ + "-", 0, 0];
}
const knownTemplateRefs = /* @__PURE__ */ new WeakSet();
function useTemplateRef(key) {
  const i = getCurrentInstance();
  const r = shallowRef(null);
  if (i) {
    const refs = i.refs === EMPTY_OBJ ? i.refs = {} : i.refs;
    let desc;
    if ((desc = Object.getOwnPropertyDescriptor(refs, key)) && !desc.configurable) {
      warn$1(`useTemplateRef('${key}') already exists.`);
    } else {
      Object.defineProperty(refs, key, {
        enumerable: true,
        get: () => r.value,
        set: (val) => r.value = val
      });
    }
  } else {
    warn$1(
      `useTemplateRef() is called when there is no active component instance to be associated with.`
    );
  }
  const ret = readonly(r);
  {
    knownTemplateRefs.add(ret);
  }
  return ret;
}
const pendingSetRefMap = /* @__PURE__ */ new WeakMap();
function setRef(rawRef, oldRawRef, parentSuspense, vnode, isUnmount = false) {
  if (isArray$1(rawRef)) {
    rawRef.forEach(
      (r, i) => setRef(
        r,
        oldRawRef && (isArray$1(oldRawRef) ? oldRawRef[i] : oldRawRef),
        parentSuspense,
        vnode,
        isUnmount
      )
    );
    return;
  }
  if (isAsyncWrapper(vnode) && !isUnmount) {
    if (vnode.shapeFlag & 512 && vnode.type.__asyncResolved && vnode.component.subTree.component) {
      setRef(rawRef, oldRawRef, parentSuspense, vnode.component.subTree);
    }
    return;
  }
  const refValue = vnode.shapeFlag & 4 ? getComponentPublicInstance(vnode.component) : vnode.el;
  const value = isUnmount ? null : refValue;
  const { i: owner, r: ref3 } = rawRef;
  if (!owner) {
    warn$1(
      `Missing ref owner context. ref cannot be used on hoisted vnodes. A vnode with ref must be created inside the render function.`
    );
    return;
  }
  const oldRef = oldRawRef && oldRawRef.r;
  const refs = owner.refs === EMPTY_OBJ ? owner.refs = {} : owner.refs;
  const setupState = owner.setupState;
  const rawSetupState = toRaw(setupState);
  const canSetSetupRef = setupState === EMPTY_OBJ ? NO : (key) => {
    {
      if (hasOwn(rawSetupState, key) && !isRef(rawSetupState[key])) {
        warn$1(
          `Template ref "${key}" used on a non-ref value. It will not work in the production build.`
        );
      }
      if (knownTemplateRefs.has(rawSetupState[key])) {
        return false;
      }
    }
    return hasOwn(rawSetupState, key);
  };
  const canSetRef = (ref22) => {
    return !knownTemplateRefs.has(ref22);
  };
  if (oldRef != null && oldRef !== ref3) {
    invalidatePendingSetRef(oldRawRef);
    if (isString$1(oldRef)) {
      refs[oldRef] = null;
      if (canSetSetupRef(oldRef)) {
        setupState[oldRef] = null;
      }
    } else if (isRef(oldRef)) {
      if (canSetRef(oldRef)) {
        oldRef.value = null;
      }
      const oldRawRefAtom = oldRawRef;
      if (oldRawRefAtom.k) refs[oldRawRefAtom.k] = null;
    }
  }
  if (isFunction$2(ref3)) {
    callWithErrorHandling(ref3, owner, 12, [value, refs]);
  } else {
    const _isString = isString$1(ref3);
    const _isRef = isRef(ref3);
    if (_isString || _isRef) {
      const doSet = () => {
        if (rawRef.f) {
          const existing = _isString ? canSetSetupRef(ref3) ? setupState[ref3] : refs[ref3] : canSetRef(ref3) || !rawRef.k ? ref3.value : refs[rawRef.k];
          if (isUnmount) {
            isArray$1(existing) && remove(existing, refValue);
          } else {
            if (!isArray$1(existing)) {
              if (_isString) {
                refs[ref3] = [refValue];
                if (canSetSetupRef(ref3)) {
                  setupState[ref3] = refs[ref3];
                }
              } else {
                const newVal = [refValue];
                if (canSetRef(ref3)) {
                  ref3.value = newVal;
                }
                if (rawRef.k) refs[rawRef.k] = newVal;
              }
            } else if (!existing.includes(refValue)) {
              existing.push(refValue);
            }
          }
        } else if (_isString) {
          refs[ref3] = value;
          if (canSetSetupRef(ref3)) {
            setupState[ref3] = value;
          }
        } else if (_isRef) {
          if (canSetRef(ref3)) {
            ref3.value = value;
          }
          if (rawRef.k) refs[rawRef.k] = value;
        } else {
          warn$1("Invalid template ref type:", ref3, `(${typeof ref3})`);
        }
      };
      if (value) {
        const job = () => {
          doSet();
          pendingSetRefMap.delete(rawRef);
        };
        job.id = -1;
        pendingSetRefMap.set(rawRef, job);
        queuePostRenderEffect(job, parentSuspense);
      } else {
        invalidatePendingSetRef(rawRef);
        doSet();
      }
    } else {
      warn$1("Invalid template ref type:", ref3, `(${typeof ref3})`);
    }
  }
}
function invalidatePendingSetRef(rawRef) {
  const pendingSetRef = pendingSetRefMap.get(rawRef);
  if (pendingSetRef) {
    pendingSetRef.flags |= 8;
    pendingSetRefMap.delete(rawRef);
  }
}
const isComment = (node) => node.nodeType === 8;
getGlobalThis().requestIdleCallback || ((cb) => setTimeout(cb, 1));
getGlobalThis().cancelIdleCallback || ((id) => clearTimeout(id));
function forEachElement(node, cb) {
  if (isComment(node) && node.data === "[") {
    let depth = 1;
    let next = node.nextSibling;
    while (next) {
      if (next.nodeType === 1) {
        const result = cb(next);
        if (result === false) {
          break;
        }
      } else if (isComment(next)) {
        if (next.data === "]") {
          if (--depth === 0) break;
        } else if (next.data === "[") {
          depth++;
        }
      }
      next = next.nextSibling;
    }
  } else {
    cb(node);
  }
}
const isAsyncWrapper = (i) => !!i.type.__asyncLoader;
// @__NO_SIDE_EFFECTS__
function defineAsyncComponent(source) {
  if (isFunction$2(source)) {
    source = { loader: source };
  }
  const {
    loader,
    loadingComponent,
    errorComponent,
    delay = 200,
    hydrate: hydrateStrategy,
    timeout,
    // undefined = never times out
    suspensible = true,
    onError: userOnError
  } = source;
  let pendingRequest = null;
  let resolvedComp;
  let retries = 0;
  const retry = () => {
    retries++;
    pendingRequest = null;
    return load();
  };
  const load = () => {
    let thisRequest;
    return pendingRequest || (thisRequest = pendingRequest = loader().catch((err) => {
      err = err instanceof Error ? err : new Error(String(err));
      if (userOnError) {
        return new Promise((resolve2, reject) => {
          const userRetry = () => resolve2(retry());
          const userFail = () => reject(err);
          userOnError(err, userRetry, userFail, retries + 1);
        });
      } else {
        throw err;
      }
    }).then((comp) => {
      if (thisRequest !== pendingRequest && pendingRequest) {
        return pendingRequest;
      }
      if (!comp) {
        warn$1(
          `Async component loader resolved to undefined. If you are using retry(), make sure to return its return value.`
        );
      }
      if (comp && (comp.__esModule || comp[Symbol.toStringTag] === "Module")) {
        comp = comp.default;
      }
      if (comp && !isObject$1(comp) && !isFunction$2(comp)) {
        throw new Error(`Invalid async component load result: ${comp}`);
      }
      resolvedComp = comp;
      return comp;
    }));
  };
  return /* @__PURE__ */ defineComponent({
    name: "AsyncComponentWrapper",
    __asyncLoader: load,
    __asyncHydrate(el, instance, hydrate) {
      let patched = false;
      (instance.bu || (instance.bu = [])).push(() => patched = true);
      const performHydrate = () => {
        if (patched) {
          {
            warn$1(
              `Skipping lazy hydration for component '${getComponentName(resolvedComp) || resolvedComp.__file}': it was updated before lazy hydration performed.`
            );
          }
          return;
        }
        hydrate();
      };
      const doHydrate = hydrateStrategy ? () => {
        const teardown = hydrateStrategy(
          performHydrate,
          (cb) => forEachElement(el, cb)
        );
        if (teardown) {
          (instance.bum || (instance.bum = [])).push(teardown);
        }
      } : performHydrate;
      if (resolvedComp) {
        doHydrate();
      } else {
        load().then(() => !instance.isUnmounted && doHydrate());
      }
    },
    get __asyncResolved() {
      return resolvedComp;
    },
    setup() {
      const instance = currentInstance;
      markAsyncBoundary(instance);
      if (resolvedComp) {
        return () => createInnerComp(resolvedComp, instance);
      }
      const onError = (err) => {
        pendingRequest = null;
        handleError(
          err,
          instance,
          13,
          !errorComponent
        );
      };
      if (suspensible && instance.suspense || isInSSRComponentSetup) {
        return load().then((comp) => {
          return () => createInnerComp(comp, instance);
        }).catch((err) => {
          onError(err);
          return () => errorComponent ? createVNode(errorComponent, {
            error: err
          }) : null;
        });
      }
      const loaded = ref(false);
      const error = ref();
      const delayed = ref(!!delay);
      if (delay) {
        setTimeout(() => {
          delayed.value = false;
        }, delay);
      }
      if (timeout != null) {
        setTimeout(() => {
          if (!loaded.value && !error.value) {
            const err = new Error(
              `Async component timed out after ${timeout}ms.`
            );
            onError(err);
            error.value = err;
          }
        }, timeout);
      }
      load().then(() => {
        loaded.value = true;
        if (instance.parent && isKeepAlive(instance.parent.vnode)) {
          instance.parent.update();
        }
      }).catch((err) => {
        onError(err);
        error.value = err;
      });
      return () => {
        if (loaded.value && resolvedComp) {
          return createInnerComp(resolvedComp, instance);
        } else if (error.value && errorComponent) {
          return createVNode(errorComponent, {
            error: error.value
          });
        } else if (loadingComponent && !delayed.value) {
          return createInnerComp(
            loadingComponent,
            instance
          );
        }
      };
    }
  });
}
function createInnerComp(comp, parent) {
  const { ref: ref22, props, children, ce } = parent.vnode;
  const vnode = createVNode(comp, props, children);
  vnode.ref = ref22;
  vnode.ce = ce;
  delete parent.vnode.ce;
  return vnode;
}
const isKeepAlive = (vnode) => vnode.type.__isKeepAlive;
function onActivated(hook, target) {
  registerKeepAliveHook(hook, "a", target);
}
function onDeactivated(hook, target) {
  registerKeepAliveHook(hook, "da", target);
}
function registerKeepAliveHook(hook, type, target = currentInstance) {
  const wrappedHook = hook.__wdc || (hook.__wdc = () => {
    let current = target;
    while (current) {
      if (current.isDeactivated) {
        return;
      }
      current = current.parent;
    }
    return hook();
  });
  injectHook(type, wrappedHook, target);
  if (target) {
    let current = target.parent;
    while (current && current.parent) {
      if (isKeepAlive(current.parent.vnode)) {
        injectToKeepAliveRoot(wrappedHook, type, target, current);
      }
      current = current.parent;
    }
  }
}
function injectToKeepAliveRoot(hook, type, target, keepAliveRoot) {
  const injected = injectHook(
    type,
    hook,
    keepAliveRoot,
    true
    /* prepend */
  );
  onUnmounted(() => {
    remove(keepAliveRoot[type], injected);
  }, target);
}
function injectHook(type, hook, target = currentInstance, prepend = false) {
  if (target) {
    const hooks = target[type] || (target[type] = []);
    const wrappedHook = hook.__weh || (hook.__weh = (...args) => {
      pauseTracking();
      const reset = setCurrentInstance(target);
      const res = callWithAsyncErrorHandling(hook, target, type, args);
      reset();
      resetTracking();
      return res;
    });
    if (prepend) {
      hooks.unshift(wrappedHook);
    } else {
      hooks.push(wrappedHook);
    }
    return wrappedHook;
  } else {
    const apiName = toHandlerKey(ErrorTypeStrings$1[type].replace(/ hook$/, ""));
    warn$1(
      `${apiName} is called when there is no active component instance to be associated with. Lifecycle injection APIs can only be used during execution of setup(). If you are using async setup(), make sure to register lifecycle hooks before the first await statement.`
    );
  }
}
const createHook = (lifecycle) => (hook, target = currentInstance) => {
  if (!isInSSRComponentSetup || lifecycle === "sp") {
    injectHook(lifecycle, (...args) => hook(...args), target);
  }
};
const onBeforeMount = createHook("bm");
const onMounted = createHook("m");
const onBeforeUpdate = createHook(
  "bu"
);
const onUpdated = createHook("u");
const onBeforeUnmount = createHook(
  "bum"
);
const onUnmounted = createHook("um");
const onServerPrefetch = createHook(
  "sp"
);
const onRenderTriggered = createHook("rtg");
const onRenderTracked = createHook("rtc");
function onErrorCaptured(hook, target = currentInstance) {
  injectHook("ec", hook, target);
}
const COMPONENTS = "components";
const DIRECTIVES = "directives";
function resolveComponent(name, maybeSelfReference) {
  return resolveAsset(COMPONENTS, name, true, maybeSelfReference) || name;
}
const NULL_DYNAMIC_COMPONENT = Symbol.for("v-ndc");
function resolveDynamicComponent(component) {
  if (isString$1(component)) {
    return resolveAsset(COMPONENTS, component, false) || component;
  } else {
    return component || NULL_DYNAMIC_COMPONENT;
  }
}
function resolveDirective(name) {
  return resolveAsset(DIRECTIVES, name);
}
function resolveAsset(type, name, warnMissing = true, maybeSelfReference = false) {
  const instance = currentRenderingInstance || currentInstance;
  if (instance) {
    const Component = instance.type;
    if (type === COMPONENTS) {
      const selfName = getComponentName(
        Component,
        false
      );
      if (selfName && (selfName === name || selfName === camelize(name) || selfName === capitalize(camelize(name)))) {
        return Component;
      }
    }
    const res = (
      // local registration
      // check instance[type] first which is resolved for options API
      resolve(instance[type] || Component[type], name) || // global registration
      resolve(instance.appContext[type], name)
    );
    if (!res && maybeSelfReference) {
      return Component;
    }
    if (warnMissing && !res) {
      const extra = type === COMPONENTS ? `
If this is a native custom element, make sure to exclude it from component resolution via compilerOptions.isCustomElement.` : ``;
      warn$1(`Failed to resolve ${type.slice(0, -1)}: ${name}${extra}`);
    }
    return res;
  } else {
    warn$1(
      `resolve${capitalize(type.slice(0, -1))} can only be used in render() or setup().`
    );
  }
}
function resolve(registry, name) {
  return registry && (registry[name] || registry[camelize(name)] || registry[capitalize(camelize(name))]);
}
function renderList(source, renderItem, cache, index) {
  let ret;
  const cached = cache;
  const sourceIsArray = isArray$1(source);
  if (sourceIsArray || isString$1(source)) {
    const sourceIsReactiveArray = sourceIsArray && isReactive(source);
    let needsWrap = false;
    let isReadonlySource = false;
    if (sourceIsReactiveArray) {
      needsWrap = !isShallow(source);
      isReadonlySource = isReadonly(source);
      source = shallowReadArray(source);
    }
    ret = new Array(source.length);
    for (let i = 0, l = source.length; i < l; i++) {
      ret[i] = renderItem(
        needsWrap ? isReadonlySource ? toReadonly(toReactive(source[i])) : toReactive(source[i]) : source[i],
        i,
        void 0,
        cached
      );
    }
  } else if (typeof source === "number") {
    if (!Number.isInteger(source)) {
      warn$1(`The v-for range expect an integer value but got ${source}.`);
    }
    ret = new Array(source);
    for (let i = 0; i < source; i++) {
      ret[i] = renderItem(i + 1, i, void 0, cached);
    }
  } else if (isObject$1(source)) {
    if (source[Symbol.iterator]) {
      ret = Array.from(
        source,
        (item, i) => renderItem(item, i, void 0, cached)
      );
    } else {
      const keys = Object.keys(source);
      ret = new Array(keys.length);
      for (let i = 0, l = keys.length; i < l; i++) {
        const key = keys[i];
        ret[i] = renderItem(source[key], key, i, cached);
      }
    }
  } else {
    ret = [];
  }
  return ret;
}
function createSlots(slots, dynamicSlots) {
  for (let i = 0; i < dynamicSlots.length; i++) {
    const slot = dynamicSlots[i];
    if (isArray$1(slot)) {
      for (let j = 0; j < slot.length; j++) {
        slots[slot[j].name] = slot[j].fn;
      }
    } else if (slot) {
      slots[slot.name] = slot.key ? (...args) => {
        const res = slot.fn(...args);
        if (res) res.key = slot.key;
        return res;
      } : slot.fn;
    }
  }
  return slots;
}
function renderSlot(slots, name, props = {}, fallback, noSlotted) {
  if (currentRenderingInstance.ce || currentRenderingInstance.parent && isAsyncWrapper(currentRenderingInstance.parent) && currentRenderingInstance.parent.ce) {
    const hasProps = Object.keys(props).length > 0;
    if (name !== "default") props.name = name;
    return openBlock(), createBlock(
      Fragment,
      null,
      [createVNode("slot", props, fallback && fallback())],
      hasProps ? -2 : 64
    );
  }
  let slot = slots[name];
  if (slot && slot.length > 1) {
    warn$1(
      `SSR-optimized slot function detected in a non-SSR-optimized render function. You need to mark this component with $dynamic-slots in the parent template.`
    );
    slot = () => [];
  }
  if (slot && slot._c) {
    slot._d = false;
  }
  openBlock();
  const validSlotContent = slot && ensureValidVNode(slot(props));
  const slotKey = props.key || // slot content array of a dynamic conditional slot may have a branch
  // key attached in the `createSlots` helper, respect that
  validSlotContent && validSlotContent.key;
  const rendered = createBlock(
    Fragment,
    {
      key: (slotKey && !isSymbol(slotKey) ? slotKey : `_${name}`) + // #7256 force differentiate fallback content from actual content
      (!validSlotContent && fallback ? "_fb" : "")
    },
    validSlotContent || (fallback ? fallback() : []),
    validSlotContent && slots._ === 1 ? 64 : -2
  );
  if (!noSlotted && rendered.scopeId) {
    rendered.slotScopeIds = [rendered.scopeId + "-s"];
  }
  if (slot && slot._c) {
    slot._d = true;
  }
  return rendered;
}
function ensureValidVNode(vnodes) {
  return vnodes.some((child) => {
    if (!isVNode(child)) return true;
    if (child.type === Comment) return false;
    if (child.type === Fragment && !ensureValidVNode(child.children))
      return false;
    return true;
  }) ? vnodes : null;
}
function toHandlers(obj, preserveCaseIfNecessary) {
  const ret = {};
  if (!isObject$1(obj)) {
    warn$1(`v-on with no argument expects an object value.`);
    return ret;
  }
  for (const key in obj) {
    ret[preserveCaseIfNecessary && /[A-Z]/.test(key) ? `on:${key}` : toHandlerKey(key)] = obj[key];
  }
  return ret;
}
const getPublicInstance = (i) => {
  if (!i) return null;
  if (isStatefulComponent(i)) return getComponentPublicInstance(i);
  return getPublicInstance(i.parent);
};
const publicPropertiesMap = (
  // Move PURE marker to new line to workaround compiler discarding it
  // due to type annotation
  /* @__PURE__ */ extend$1(/* @__PURE__ */ Object.create(null), {
    $: (i) => i,
    $el: (i) => i.vnode.el,
    $data: (i) => i.data,
    $props: (i) => shallowReadonly(i.props),
    $attrs: (i) => shallowReadonly(i.attrs),
    $slots: (i) => shallowReadonly(i.slots),
    $refs: (i) => shallowReadonly(i.refs),
    $parent: (i) => getPublicInstance(i.parent),
    $root: (i) => getPublicInstance(i.root),
    $host: (i) => i.ce,
    $emit: (i) => i.emit,
    $options: (i) => resolveMergedOptions(i),
    $forceUpdate: (i) => i.f || (i.f = () => {
      queueJob(i.update);
    }),
    $nextTick: (i) => i.n || (i.n = nextTick.bind(i.proxy)),
    $watch: (i) => instanceWatch.bind(i)
  })
);
const isReservedPrefix = (key) => key === "_" || key === "$";
const hasSetupBinding = (state, key) => state !== EMPTY_OBJ && !state.__isScriptSetup && hasOwn(state, key);
const PublicInstanceProxyHandlers = {
  get({ _: instance }, key) {
    if (key === "__v_skip") {
      return true;
    }
    const { ctx, setupState, data, props, accessCache, type, appContext } = instance;
    if (key === "__isVue") {
      return true;
    }
    if (key[0] !== "$") {
      const n2 = accessCache[key];
      if (n2 !== void 0) {
        switch (n2) {
          case 1:
            return setupState[key];
          case 2:
            return data[key];
          case 4:
            return ctx[key];
          case 3:
            return props[key];
        }
      } else if (hasSetupBinding(setupState, key)) {
        accessCache[key] = 1;
        return setupState[key];
      } else if (data !== EMPTY_OBJ && hasOwn(data, key)) {
        accessCache[key] = 2;
        return data[key];
      } else if (hasOwn(props, key)) {
        accessCache[key] = 3;
        return props[key];
      } else if (ctx !== EMPTY_OBJ && hasOwn(ctx, key)) {
        accessCache[key] = 4;
        return ctx[key];
      } else if (shouldCacheAccess) {
        accessCache[key] = 0;
      }
    }
    const publicGetter = publicPropertiesMap[key];
    let cssModule, globalProperties;
    if (publicGetter) {
      if (key === "$attrs") {
        track(instance.attrs, "get", "");
        markAttrsAccessed();
      } else if (key === "$slots") {
        track(instance, "get", key);
      }
      return publicGetter(instance);
    } else if (
      // css module (injected by vue-loader)
      (cssModule = type.__cssModules) && (cssModule = cssModule[key])
    ) {
      return cssModule;
    } else if (ctx !== EMPTY_OBJ && hasOwn(ctx, key)) {
      accessCache[key] = 4;
      return ctx[key];
    } else if (
      // global properties
      globalProperties = appContext.config.globalProperties, hasOwn(globalProperties, key)
    ) {
      {
        return globalProperties[key];
      }
    } else if (currentRenderingInstance && (!isString$1(key) || // #1091 avoid internal isRef/isVNode checks on component instance leading
    // to infinite warning loop
    key.indexOf("__v") !== 0)) {
      if (data !== EMPTY_OBJ && isReservedPrefix(key[0]) && hasOwn(data, key)) {
        warn$1(
          `Property ${JSON.stringify(
            key
          )} must be accessed via $data because it starts with a reserved character ("$" or "_") and is not proxied on the render context.`
        );
      } else if (instance === currentRenderingInstance) {
        warn$1(
          `Property ${JSON.stringify(key)} was accessed during render but is not defined on instance.`
        );
      }
    }
  },
  set({ _: instance }, key, value) {
    const { data, setupState, ctx } = instance;
    if (hasSetupBinding(setupState, key)) {
      setupState[key] = value;
      return true;
    } else if (setupState.__isScriptSetup && hasOwn(setupState, key)) {
      warn$1(`Cannot mutate <script setup> binding "${key}" from Options API.`);
      return false;
    } else if (data !== EMPTY_OBJ && hasOwn(data, key)) {
      data[key] = value;
      return true;
    } else if (hasOwn(instance.props, key)) {
      warn$1(`Attempting to mutate prop "${key}". Props are readonly.`);
      return false;
    }
    if (key[0] === "$" && key.slice(1) in instance) {
      warn$1(
        `Attempting to mutate public property "${key}". Properties starting with $ are reserved and readonly.`
      );
      return false;
    } else {
      if (key in instance.appContext.config.globalProperties) {
        Object.defineProperty(ctx, key, {
          enumerable: true,
          configurable: true,
          value
        });
      } else {
        ctx[key] = value;
      }
    }
    return true;
  },
  has({
    _: { data, setupState, accessCache, ctx, appContext, props, type }
  }, key) {
    let cssModules;
    return !!(accessCache[key] || data !== EMPTY_OBJ && key[0] !== "$" && hasOwn(data, key) || hasSetupBinding(setupState, key) || hasOwn(props, key) || hasOwn(ctx, key) || hasOwn(publicPropertiesMap, key) || hasOwn(appContext.config.globalProperties, key) || (cssModules = type.__cssModules) && cssModules[key]);
  },
  defineProperty(target, key, descriptor) {
    if (descriptor.get != null) {
      target._.accessCache[key] = 0;
    } else if (hasOwn(descriptor, "value")) {
      this.set(target, key, descriptor.value, null);
    }
    return Reflect.defineProperty(target, key, descriptor);
  }
};
{
  PublicInstanceProxyHandlers.ownKeys = (target) => {
    warn$1(
      `Avoid app logic that relies on enumerating keys on a component instance. The keys will be empty in production mode to avoid performance overhead.`
    );
    return Reflect.ownKeys(target);
  };
}
function createDevRenderContext(instance) {
  const target = {};
  Object.defineProperty(target, `_`, {
    configurable: true,
    enumerable: false,
    get: () => instance
  });
  Object.keys(publicPropertiesMap).forEach((key) => {
    Object.defineProperty(target, key, {
      configurable: true,
      enumerable: false,
      get: () => publicPropertiesMap[key](instance),
      // intercepted by the proxy so no need for implementation,
      // but needed to prevent set errors
      set: NOOP
    });
  });
  return target;
}
function exposePropsOnRenderContext(instance) {
  const {
    ctx,
    propsOptions: [propsOptions]
  } = instance;
  if (propsOptions) {
    Object.keys(propsOptions).forEach((key) => {
      Object.defineProperty(ctx, key, {
        enumerable: true,
        configurable: true,
        get: () => instance.props[key],
        set: NOOP
      });
    });
  }
}
function exposeSetupStateOnRenderContext(instance) {
  const { ctx, setupState } = instance;
  Object.keys(toRaw(setupState)).forEach((key) => {
    if (!setupState.__isScriptSetup) {
      if (isReservedPrefix(key[0])) {
        warn$1(
          `setup() return property ${JSON.stringify(
            key
          )} should not start with "$" or "_" which are reserved prefixes for Vue internals.`
        );
        return;
      }
      Object.defineProperty(ctx, key, {
        enumerable: true,
        configurable: true,
        get: () => setupState[key],
        set: NOOP
      });
    }
  });
}
function useSlots() {
  return getContext("useSlots").slots;
}
function useAttrs() {
  return getContext("useAttrs").attrs;
}
function getContext(calledFunctionName) {
  const i = getCurrentInstance();
  if (!i) {
    warn$1(`${calledFunctionName}() called without active instance.`);
  }
  return i.setupContext || (i.setupContext = createSetupContext(i));
}
function normalizePropsOrEmits(props) {
  return isArray$1(props) ? props.reduce(
    (normalized, p2) => (normalized[p2] = null, normalized),
    {}
  ) : props;
}
function mergeModels(a, b) {
  if (!a || !b) return a || b;
  if (isArray$1(a) && isArray$1(b)) return a.concat(b);
  return extend$1({}, normalizePropsOrEmits(a), normalizePropsOrEmits(b));
}
function createDuplicateChecker() {
  const cache = /* @__PURE__ */ Object.create(null);
  return (type, key) => {
    if (cache[key]) {
      warn$1(`${type} property "${key}" is already defined in ${cache[key]}.`);
    } else {
      cache[key] = type;
    }
  };
}
let shouldCacheAccess = true;
function applyOptions(instance) {
  const options = resolveMergedOptions(instance);
  const publicThis = instance.proxy;
  const ctx = instance.ctx;
  shouldCacheAccess = false;
  if (options.beforeCreate) {
    callHook$1(options.beforeCreate, instance, "bc");
  }
  const {
    // state
    data: dataOptions,
    computed: computedOptions,
    methods,
    watch: watchOptions,
    provide: provideOptions,
    inject: injectOptions,
    // lifecycle
    created,
    beforeMount,
    mounted,
    beforeUpdate,
    updated,
    activated,
    deactivated,
    beforeDestroy,
    beforeUnmount,
    destroyed,
    unmounted,
    render: render2,
    renderTracked,
    renderTriggered,
    errorCaptured,
    serverPrefetch,
    // public API
    expose,
    inheritAttrs,
    // assets
    components,
    directives,
    filters
  } = options;
  const checkDuplicateProperties = createDuplicateChecker();
  {
    const [propsOptions] = instance.propsOptions;
    if (propsOptions) {
      for (const key in propsOptions) {
        checkDuplicateProperties("Props", key);
      }
    }
  }
  if (injectOptions) {
    resolveInjections(injectOptions, ctx, checkDuplicateProperties);
  }
  if (methods) {
    for (const key in methods) {
      const methodHandler = methods[key];
      if (isFunction$2(methodHandler)) {
        {
          Object.defineProperty(ctx, key, {
            value: methodHandler.bind(publicThis),
            configurable: true,
            enumerable: true,
            writable: true
          });
        }
        {
          checkDuplicateProperties("Methods", key);
        }
      } else {
        warn$1(
          `Method "${key}" has type "${typeof methodHandler}" in the component definition. Did you reference the function correctly?`
        );
      }
    }
  }
  if (dataOptions) {
    if (!isFunction$2(dataOptions)) {
      warn$1(
        `The data option must be a function. Plain object usage is no longer supported.`
      );
    }
    const data = dataOptions.call(publicThis, publicThis);
    if (isPromise(data)) {
      warn$1(
        `data() returned a Promise - note data() cannot be async; If you intend to perform data fetching before component renders, use async setup() + <Suspense>.`
      );
    }
    if (!isObject$1(data)) {
      warn$1(`data() should return an object.`);
    } else {
      instance.data = reactive(data);
      {
        for (const key in data) {
          checkDuplicateProperties("Data", key);
          if (!isReservedPrefix(key[0])) {
            Object.defineProperty(ctx, key, {
              configurable: true,
              enumerable: true,
              get: () => data[key],
              set: NOOP
            });
          }
        }
      }
    }
  }
  shouldCacheAccess = true;
  if (computedOptions) {
    for (const key in computedOptions) {
      const opt = computedOptions[key];
      const get = isFunction$2(opt) ? opt.bind(publicThis, publicThis) : isFunction$2(opt.get) ? opt.get.bind(publicThis, publicThis) : NOOP;
      if (get === NOOP) {
        warn$1(`Computed property "${key}" has no getter.`);
      }
      const set = !isFunction$2(opt) && isFunction$2(opt.set) ? opt.set.bind(publicThis) : () => {
        warn$1(
          `Write operation failed: computed property "${key}" is readonly.`
        );
      };
      const c = computed({
        get,
        set
      });
      Object.defineProperty(ctx, key, {
        enumerable: true,
        configurable: true,
        get: () => c.value,
        set: (v) => c.value = v
      });
      {
        checkDuplicateProperties("Computed", key);
      }
    }
  }
  if (watchOptions) {
    for (const key in watchOptions) {
      createWatcher(watchOptions[key], ctx, publicThis, key);
    }
  }
  if (provideOptions) {
    const provides = isFunction$2(provideOptions) ? provideOptions.call(publicThis) : provideOptions;
    Reflect.ownKeys(provides).forEach((key) => {
      provide(key, provides[key]);
    });
  }
  if (created) {
    callHook$1(created, instance, "c");
  }
  function registerLifecycleHook(register2, hook) {
    if (isArray$1(hook)) {
      hook.forEach((_hook) => register2(_hook.bind(publicThis)));
    } else if (hook) {
      register2(hook.bind(publicThis));
    }
  }
  registerLifecycleHook(onBeforeMount, beforeMount);
  registerLifecycleHook(onMounted, mounted);
  registerLifecycleHook(onBeforeUpdate, beforeUpdate);
  registerLifecycleHook(onUpdated, updated);
  registerLifecycleHook(onActivated, activated);
  registerLifecycleHook(onDeactivated, deactivated);
  registerLifecycleHook(onErrorCaptured, errorCaptured);
  registerLifecycleHook(onRenderTracked, renderTracked);
  registerLifecycleHook(onRenderTriggered, renderTriggered);
  registerLifecycleHook(onBeforeUnmount, beforeUnmount);
  registerLifecycleHook(onUnmounted, unmounted);
  registerLifecycleHook(onServerPrefetch, serverPrefetch);
  if (isArray$1(expose)) {
    if (expose.length) {
      const exposed = instance.exposed || (instance.exposed = {});
      expose.forEach((key) => {
        Object.defineProperty(exposed, key, {
          get: () => publicThis[key],
          set: (val) => publicThis[key] = val,
          enumerable: true
        });
      });
    } else if (!instance.exposed) {
      instance.exposed = {};
    }
  }
  if (render2 && instance.render === NOOP) {
    instance.render = render2;
  }
  if (inheritAttrs != null) {
    instance.inheritAttrs = inheritAttrs;
  }
  if (components) instance.components = components;
  if (directives) instance.directives = directives;
  if (serverPrefetch) {
    markAsyncBoundary(instance);
  }
}
function resolveInjections(injectOptions, ctx, checkDuplicateProperties = NOOP) {
  if (isArray$1(injectOptions)) {
    injectOptions = normalizeInject(injectOptions);
  }
  for (const key in injectOptions) {
    const opt = injectOptions[key];
    let injected;
    if (isObject$1(opt)) {
      if ("default" in opt) {
        injected = inject(
          opt.from || key,
          opt.default,
          true
        );
      } else {
        injected = inject(opt.from || key);
      }
    } else {
      injected = inject(opt);
    }
    if (isRef(injected)) {
      Object.defineProperty(ctx, key, {
        enumerable: true,
        configurable: true,
        get: () => injected.value,
        set: (v) => injected.value = v
      });
    } else {
      ctx[key] = injected;
    }
    {
      checkDuplicateProperties("Inject", key);
    }
  }
}
function callHook$1(hook, instance, type) {
  callWithAsyncErrorHandling(
    isArray$1(hook) ? hook.map((h2) => h2.bind(instance.proxy)) : hook.bind(instance.proxy),
    instance,
    type
  );
}
function createWatcher(raw, ctx, publicThis, key) {
  let getter = key.includes(".") ? createPathGetter(publicThis, key) : () => publicThis[key];
  if (isString$1(raw)) {
    const handler = ctx[raw];
    if (isFunction$2(handler)) {
      {
        watch(getter, handler);
      }
    } else {
      warn$1(`Invalid watch handler specified by key "${raw}"`, handler);
    }
  } else if (isFunction$2(raw)) {
    {
      watch(getter, raw.bind(publicThis));
    }
  } else if (isObject$1(raw)) {
    if (isArray$1(raw)) {
      raw.forEach((r) => createWatcher(r, ctx, publicThis, key));
    } else {
      const handler = isFunction$2(raw.handler) ? raw.handler.bind(publicThis) : ctx[raw.handler];
      if (isFunction$2(handler)) {
        watch(getter, handler, raw);
      } else {
        warn$1(`Invalid watch handler specified by key "${raw.handler}"`, handler);
      }
    }
  } else {
    warn$1(`Invalid watch option: "${key}"`, raw);
  }
}
function resolveMergedOptions(instance) {
  const base = instance.type;
  const { mixins, extends: extendsOptions } = base;
  const {
    mixins: globalMixins,
    optionsCache: cache,
    config: { optionMergeStrategies }
  } = instance.appContext;
  const cached = cache.get(base);
  let resolved;
  if (cached) {
    resolved = cached;
  } else if (!globalMixins.length && !mixins && !extendsOptions) {
    {
      resolved = base;
    }
  } else {
    resolved = {};
    if (globalMixins.length) {
      globalMixins.forEach(
        (m) => mergeOptions(resolved, m, optionMergeStrategies, true)
      );
    }
    mergeOptions(resolved, base, optionMergeStrategies);
  }
  if (isObject$1(base)) {
    cache.set(base, resolved);
  }
  return resolved;
}
function mergeOptions(to, from, strats, asMixin = false) {
  const { mixins, extends: extendsOptions } = from;
  if (extendsOptions) {
    mergeOptions(to, extendsOptions, strats, true);
  }
  if (mixins) {
    mixins.forEach(
      (m) => mergeOptions(to, m, strats, true)
    );
  }
  for (const key in from) {
    if (asMixin && key === "expose") {
      warn$1(
        `"expose" option is ignored when declared in mixins or extends. It should only be declared in the base component itself.`
      );
    } else {
      const strat = internalOptionMergeStrats[key] || strats && strats[key];
      to[key] = strat ? strat(to[key], from[key]) : from[key];
    }
  }
  return to;
}
const internalOptionMergeStrats = {
  data: mergeDataFn,
  props: mergeEmitsOrPropsOptions,
  emits: mergeEmitsOrPropsOptions,
  // objects
  methods: mergeObjectOptions,
  computed: mergeObjectOptions,
  // lifecycle
  beforeCreate: mergeAsArray,
  created: mergeAsArray,
  beforeMount: mergeAsArray,
  mounted: mergeAsArray,
  beforeUpdate: mergeAsArray,
  updated: mergeAsArray,
  beforeDestroy: mergeAsArray,
  beforeUnmount: mergeAsArray,
  destroyed: mergeAsArray,
  unmounted: mergeAsArray,
  activated: mergeAsArray,
  deactivated: mergeAsArray,
  errorCaptured: mergeAsArray,
  serverPrefetch: mergeAsArray,
  // assets
  components: mergeObjectOptions,
  directives: mergeObjectOptions,
  // watch
  watch: mergeWatchOptions,
  // provide / inject
  provide: mergeDataFn,
  inject: mergeInject
};
function mergeDataFn(to, from) {
  if (!from) {
    return to;
  }
  if (!to) {
    return from;
  }
  return function mergedDataFn() {
    return extend$1(
      isFunction$2(to) ? to.call(this, this) : to,
      isFunction$2(from) ? from.call(this, this) : from
    );
  };
}
function mergeInject(to, from) {
  return mergeObjectOptions(normalizeInject(to), normalizeInject(from));
}
function normalizeInject(raw) {
  if (isArray$1(raw)) {
    const res = {};
    for (let i = 0; i < raw.length; i++) {
      res[raw[i]] = raw[i];
    }
    return res;
  }
  return raw;
}
function mergeAsArray(to, from) {
  return to ? [...new Set([].concat(to, from))] : from;
}
function mergeObjectOptions(to, from) {
  return to ? extend$1(/* @__PURE__ */ Object.create(null), to, from) : from;
}
function mergeEmitsOrPropsOptions(to, from) {
  if (to) {
    if (isArray$1(to) && isArray$1(from)) {
      return [.../* @__PURE__ */ new Set([...to, ...from])];
    }
    return extend$1(
      /* @__PURE__ */ Object.create(null),
      normalizePropsOrEmits(to),
      normalizePropsOrEmits(from != null ? from : {})
    );
  } else {
    return from;
  }
}
function mergeWatchOptions(to, from) {
  if (!to) return from;
  if (!from) return to;
  const merged = extend$1(/* @__PURE__ */ Object.create(null), to);
  for (const key in from) {
    merged[key] = mergeAsArray(to[key], from[key]);
  }
  return merged;
}
function createAppContext() {
  return {
    app: null,
    config: {
      isNativeTag: NO,
      performance: false,
      globalProperties: {},
      optionMergeStrategies: {},
      errorHandler: void 0,
      warnHandler: void 0,
      compilerOptions: {}
    },
    mixins: [],
    components: {},
    directives: {},
    provides: /* @__PURE__ */ Object.create(null),
    optionsCache: /* @__PURE__ */ new WeakMap(),
    propsCache: /* @__PURE__ */ new WeakMap(),
    emitsCache: /* @__PURE__ */ new WeakMap()
  };
}
let uid$1 = 0;
function createAppAPI(render2, hydrate) {
  return function createApp2(rootComponent, rootProps = null) {
    if (!isFunction$2(rootComponent)) {
      rootComponent = extend$1({}, rootComponent);
    }
    if (rootProps != null && !isObject$1(rootProps)) {
      warn$1(`root props passed to app.mount() must be an object.`);
      rootProps = null;
    }
    const context = createAppContext();
    const installedPlugins = /* @__PURE__ */ new WeakSet();
    const pluginCleanupFns = [];
    let isMounted = false;
    const app = context.app = {
      _uid: uid$1++,
      _component: rootComponent,
      _props: rootProps,
      _container: null,
      _context: context,
      _instance: null,
      version,
      get config() {
        return context.config;
      },
      set config(v) {
        {
          warn$1(
            `app.config cannot be replaced. Modify individual options instead.`
          );
        }
      },
      use(plugin, ...options) {
        if (installedPlugins.has(plugin)) {
          warn$1(`Plugin has already been applied to target app.`);
        } else if (plugin && isFunction$2(plugin.install)) {
          installedPlugins.add(plugin);
          plugin.install(app, ...options);
        } else if (isFunction$2(plugin)) {
          installedPlugins.add(plugin);
          plugin(app, ...options);
        } else {
          warn$1(
            `A plugin must either be a function or an object with an "install" function.`
          );
        }
        return app;
      },
      mixin(mixin) {
        {
          if (!context.mixins.includes(mixin)) {
            context.mixins.push(mixin);
          } else {
            warn$1(
              "Mixin has already been applied to target app" + (mixin.name ? `: ${mixin.name}` : "")
            );
          }
        }
        return app;
      },
      component(name, component) {
        {
          validateComponentName(name, context.config);
        }
        if (!component) {
          return context.components[name];
        }
        if (context.components[name]) {
          warn$1(`Component "${name}" has already been registered in target app.`);
        }
        context.components[name] = component;
        return app;
      },
      directive(name, directive) {
        {
          validateDirectiveName(name);
        }
        if (!directive) {
          return context.directives[name];
        }
        if (context.directives[name]) {
          warn$1(`Directive "${name}" has already been registered in target app.`);
        }
        context.directives[name] = directive;
        return app;
      },
      mount(rootContainer, isHydrate, namespace) {
        if (!isMounted) {
          if (rootContainer.__vue_app__) {
            warn$1(
              `There is already an app instance mounted on the host container.
 If you want to mount another app on the same host container, you need to unmount the previous app by calling \`app.unmount()\` first.`
            );
          }
          const vnode = app._ceVNode || createVNode(rootComponent, rootProps);
          vnode.appContext = context;
          if (namespace === true) {
            namespace = "svg";
          } else if (namespace === false) {
            namespace = void 0;
          }
          {
            context.reload = () => {
              const cloned = cloneVNode(vnode);
              cloned.el = null;
              render2(cloned, rootContainer, namespace);
            };
          }
          {
            render2(vnode, rootContainer, namespace);
          }
          isMounted = true;
          app._container = rootContainer;
          rootContainer.__vue_app__ = app;
          {
            app._instance = vnode.component;
            devtoolsInitApp(app, version);
          }
          return getComponentPublicInstance(vnode.component);
        } else {
          warn$1(
            `App has already been mounted.
If you want to remount the same app, move your app creation logic into a factory function and create fresh app instances for each mount - e.g. \`const createMyApp = () => createApp(App)\``
          );
        }
      },
      onUnmount(cleanupFn) {
        if (typeof cleanupFn !== "function") {
          warn$1(
            `Expected function as first argument to app.onUnmount(), but got ${typeof cleanupFn}`
          );
        }
        pluginCleanupFns.push(cleanupFn);
      },
      unmount() {
        if (isMounted) {
          callWithAsyncErrorHandling(
            pluginCleanupFns,
            app._instance,
            16
          );
          render2(null, app._container);
          {
            app._instance = null;
            devtoolsUnmountApp(app);
          }
          delete app._container.__vue_app__;
        } else {
          warn$1(`Cannot unmount an app that is not mounted.`);
        }
      },
      provide(key, value) {
        if (key in context.provides) {
          if (hasOwn(context.provides, key)) {
            warn$1(
              `App already provides property with key "${String(key)}". It will be overwritten with the new value.`
            );
          } else {
            warn$1(
              `App already provides property with key "${String(key)}" inherited from its parent element. It will be overwritten with the new value.`
            );
          }
        }
        context.provides[key] = value;
        return app;
      },
      runWithContext(fn) {
        const lastApp = currentApp;
        currentApp = app;
        try {
          return fn();
        } finally {
          currentApp = lastApp;
        }
      }
    };
    return app;
  };
}
let currentApp = null;
function provide(key, value) {
  {
    if (!currentInstance || currentInstance.isMounted) {
      warn$1(`provide() can only be used inside setup().`);
    }
  }
  if (currentInstance) {
    let provides = currentInstance.provides;
    const parentProvides = currentInstance.parent && currentInstance.parent.provides;
    if (parentProvides === provides) {
      provides = currentInstance.provides = Object.create(parentProvides);
    }
    provides[key] = value;
  }
}
function inject(key, defaultValue, treatDefaultAsFactory = false) {
  const instance = getCurrentInstance();
  if (instance || currentApp) {
    let provides = currentApp ? currentApp._context.provides : instance ? instance.parent == null || instance.ce ? instance.vnode.appContext && instance.vnode.appContext.provides : instance.parent.provides : void 0;
    if (provides && key in provides) {
      return provides[key];
    } else if (arguments.length > 1) {
      return treatDefaultAsFactory && isFunction$2(defaultValue) ? defaultValue.call(instance && instance.proxy) : defaultValue;
    } else {
      warn$1(`injection "${String(key)}" not found.`);
    }
  } else {
    warn$1(`inject() can only be used inside setup() or functional components.`);
  }
}
function hasInjectionContext() {
  return !!(getCurrentInstance() || currentApp);
}
const ssrContextKey = Symbol.for("v-scx");
const useSSRContext = () => {
  {
    const ctx = inject(ssrContextKey);
    if (!ctx) {
      warn$1(
        `Server rendering context not provided. Make sure to only call useSSRContext() conditionally in the server build.`
      );
    }
    return ctx;
  }
};
function watchEffect(effect2, options) {
  return doWatch(effect2, null, options);
}
function watchSyncEffect(effect2, options) {
  return doWatch(
    effect2,
    null,
    extend$1({}, options, { flush: "sync" })
  );
}
function watch(source, cb, options) {
  if (!isFunction$2(cb)) {
    warn$1(
      `\`watch(fn, options?)\` signature has been moved to a separate API. Use \`watchEffect(fn, options?)\` instead. \`watch\` now only supports \`watch(source, cb, options?) signature.`
    );
  }
  return doWatch(source, cb, options);
}
function doWatch(source, cb, options = EMPTY_OBJ) {
  const { immediate, deep, flush, once } = options;
  if (!cb) {
    if (immediate !== void 0) {
      warn$1(
        `watch() "immediate" option is only respected when using the watch(source, callback, options?) signature.`
      );
    }
    if (deep !== void 0) {
      warn$1(
        `watch() "deep" option is only respected when using the watch(source, callback, options?) signature.`
      );
    }
    if (once !== void 0) {
      warn$1(
        `watch() "once" option is only respected when using the watch(source, callback, options?) signature.`
      );
    }
  }
  const baseWatchOptions = extend$1({}, options);
  baseWatchOptions.onWarn = warn$1;
  const runsImmediately = cb && immediate || !cb && flush !== "post";
  let ssrCleanup;
  if (isInSSRComponentSetup) {
    if (flush === "sync") {
      const ctx = useSSRContext();
      ssrCleanup = ctx.__watcherHandles || (ctx.__watcherHandles = []);
    } else if (!runsImmediately) {
      const watchStopHandle = () => {
      };
      watchStopHandle.stop = NOOP;
      watchStopHandle.resume = NOOP;
      watchStopHandle.pause = NOOP;
      return watchStopHandle;
    }
  }
  const instance = currentInstance;
  baseWatchOptions.call = (fn, type, args) => callWithAsyncErrorHandling(fn, instance, type, args);
  let isPre = false;
  if (flush === "post") {
    baseWatchOptions.scheduler = (job) => {
      queuePostRenderEffect(job, instance && instance.suspense);
    };
  } else if (flush !== "sync") {
    isPre = true;
    baseWatchOptions.scheduler = (job, isFirstRun) => {
      if (isFirstRun) {
        job();
      } else {
        queueJob(job);
      }
    };
  }
  baseWatchOptions.augmentJob = (job) => {
    if (cb) {
      job.flags |= 4;
    }
    if (isPre) {
      job.flags |= 2;
      if (instance) {
        job.id = instance.uid;
        job.i = instance;
      }
    }
  };
  const watchHandle = watch$1(source, cb, baseWatchOptions);
  if (isInSSRComponentSetup) {
    if (ssrCleanup) {
      ssrCleanup.push(watchHandle);
    } else if (runsImmediately) {
      watchHandle();
    }
  }
  return watchHandle;
}
function instanceWatch(source, value, options) {
  const publicThis = this.proxy;
  const getter = isString$1(source) ? source.includes(".") ? createPathGetter(publicThis, source) : () => publicThis[source] : source.bind(publicThis, publicThis);
  let cb;
  if (isFunction$2(value)) {
    cb = value;
  } else {
    cb = value.handler;
    options = value;
  }
  const reset = setCurrentInstance(this);
  const res = doWatch(getter, cb.bind(publicThis), options);
  reset();
  return res;
}
function createPathGetter(ctx, path) {
  const segments = path.split(".");
  return () => {
    let cur = ctx;
    for (let i = 0; i < segments.length && cur; i++) {
      cur = cur[segments[i]];
    }
    return cur;
  };
}
function useModel(props, name, options = EMPTY_OBJ) {
  const i = getCurrentInstance();
  if (!i) {
    warn$1(`useModel() called without active instance.`);
    return ref();
  }
  const camelizedName = camelize(name);
  if (!i.propsOptions[0][camelizedName]) {
    warn$1(`useModel() called with prop "${name}" which is not declared.`);
    return ref();
  }
  const hyphenatedName = hyphenate(name);
  const modifiers = getModelModifiers(props, camelizedName);
  const res = customRef((track2, trigger2) => {
    let localValue;
    let prevSetValue = EMPTY_OBJ;
    let prevEmittedValue;
    watchSyncEffect(() => {
      const propValue = props[camelizedName];
      if (hasChanged(localValue, propValue)) {
        localValue = propValue;
        trigger2();
      }
    });
    return {
      get() {
        track2();
        return options.get ? options.get(localValue) : localValue;
      },
      set(value) {
        const emittedValue = options.set ? options.set(value) : value;
        if (!hasChanged(emittedValue, localValue) && !(prevSetValue !== EMPTY_OBJ && hasChanged(value, prevSetValue))) {
          return;
        }
        const rawProps = i.vnode.props;
        if (!(rawProps && // check if parent has passed v-model
        (name in rawProps || camelizedName in rawProps || hyphenatedName in rawProps) && (`onUpdate:${name}` in rawProps || `onUpdate:${camelizedName}` in rawProps || `onUpdate:${hyphenatedName}` in rawProps))) {
          localValue = value;
          trigger2();
        }
        i.emit(`update:${name}`, emittedValue);
        if (hasChanged(value, emittedValue) && hasChanged(value, prevSetValue) && !hasChanged(emittedValue, prevEmittedValue)) {
          trigger2();
        }
        prevSetValue = value;
        prevEmittedValue = emittedValue;
      }
    };
  });
  res[Symbol.iterator] = () => {
    let i2 = 0;
    return {
      next() {
        if (i2 < 2) {
          return { value: i2++ ? modifiers || EMPTY_OBJ : res, done: false };
        } else {
          return { done: true };
        }
      }
    };
  };
  return res;
}
const getModelModifiers = (props, modelName) => {
  return modelName === "modelValue" || modelName === "model-value" ? props.modelModifiers : props[`${modelName}Modifiers`] || props[`${camelize(modelName)}Modifiers`] || props[`${hyphenate(modelName)}Modifiers`];
};
function emit$2(instance, event, ...rawArgs) {
  if (instance.isUnmounted) return;
  const props = instance.vnode.props || EMPTY_OBJ;
  {
    const {
      emitsOptions,
      propsOptions: [propsOptions]
    } = instance;
    if (emitsOptions) {
      if (!(event in emitsOptions) && true) {
        if (!propsOptions || !(toHandlerKey(camelize(event)) in propsOptions)) {
          warn$1(
            `Component emitted event "${event}" but it is neither declared in the emits option nor as an "${toHandlerKey(camelize(event))}" prop.`
          );
        }
      } else {
        const validator2 = emitsOptions[event];
        if (isFunction$2(validator2)) {
          const isValid = validator2(...rawArgs);
          if (!isValid) {
            warn$1(
              `Invalid event arguments: event validation failed for event "${event}".`
            );
          }
        }
      }
    }
  }
  let args = rawArgs;
  const isModelListener2 = event.startsWith("update:");
  const modifiers = isModelListener2 && getModelModifiers(props, event.slice(7));
  if (modifiers) {
    if (modifiers.trim) {
      args = rawArgs.map((a) => isString$1(a) ? a.trim() : a);
    }
    if (modifiers.number) {
      args = rawArgs.map(looseToNumber);
    }
  }
  {
    devtoolsComponentEmit(instance, event, args);
  }
  {
    const lowerCaseEvent = event.toLowerCase();
    if (lowerCaseEvent !== event && props[toHandlerKey(lowerCaseEvent)]) {
      warn$1(
        `Event "${lowerCaseEvent}" is emitted in component ${formatComponentName(
          instance,
          instance.type
        )} but the handler is registered for "${event}". Note that HTML attributes are case-insensitive and you cannot use v-on to listen to camelCase events when using in-DOM templates. You should probably use "${hyphenate(
          event
        )}" instead of "${event}".`
      );
    }
  }
  let handlerName;
  let handler = props[handlerName = toHandlerKey(event)] || // also try camelCase event handler (#2249)
  props[handlerName = toHandlerKey(camelize(event))];
  if (!handler && isModelListener2) {
    handler = props[handlerName = toHandlerKey(hyphenate(event))];
  }
  if (handler) {
    callWithAsyncErrorHandling(
      handler,
      instance,
      6,
      args
    );
  }
  const onceHandler = props[handlerName + `Once`];
  if (onceHandler) {
    if (!instance.emitted) {
      instance.emitted = {};
    } else if (instance.emitted[handlerName]) {
      return;
    }
    instance.emitted[handlerName] = true;
    callWithAsyncErrorHandling(
      onceHandler,
      instance,
      6,
      args
    );
  }
}
const mixinEmitsCache = /* @__PURE__ */ new WeakMap();
function normalizeEmitsOptions(comp, appContext, asMixin = false) {
  const cache = asMixin ? mixinEmitsCache : appContext.emitsCache;
  const cached = cache.get(comp);
  if (cached !== void 0) {
    return cached;
  }
  const raw = comp.emits;
  let normalized = {};
  let hasExtends = false;
  if (!isFunction$2(comp)) {
    const extendEmits = (raw2) => {
      const normalizedFromExtend = normalizeEmitsOptions(raw2, appContext, true);
      if (normalizedFromExtend) {
        hasExtends = true;
        extend$1(normalized, normalizedFromExtend);
      }
    };
    if (!asMixin && appContext.mixins.length) {
      appContext.mixins.forEach(extendEmits);
    }
    if (comp.extends) {
      extendEmits(comp.extends);
    }
    if (comp.mixins) {
      comp.mixins.forEach(extendEmits);
    }
  }
  if (!raw && !hasExtends) {
    if (isObject$1(comp)) {
      cache.set(comp, null);
    }
    return null;
  }
  if (isArray$1(raw)) {
    raw.forEach((key) => normalized[key] = null);
  } else {
    extend$1(normalized, raw);
  }
  if (isObject$1(comp)) {
    cache.set(comp, normalized);
  }
  return normalized;
}
function isEmitListener(options, key) {
  if (!options || !isOn(key)) {
    return false;
  }
  key = key.slice(2).replace(/Once$/, "");
  return hasOwn(options, key[0].toLowerCase() + key.slice(1)) || hasOwn(options, hyphenate(key)) || hasOwn(options, key);
}
let accessedAttrs = false;
function markAttrsAccessed() {
  accessedAttrs = true;
}
function renderComponentRoot(instance) {
  const {
    type: Component,
    vnode,
    proxy,
    withProxy,
    propsOptions: [propsOptions],
    slots,
    attrs,
    emit: emit2,
    render: render2,
    renderCache,
    props,
    data,
    setupState,
    ctx,
    inheritAttrs
  } = instance;
  const prev = setCurrentRenderingInstance(instance);
  let result;
  let fallthroughAttrs;
  {
    accessedAttrs = false;
  }
  try {
    if (vnode.shapeFlag & 4) {
      const proxyToUse = withProxy || proxy;
      const thisProxy = setupState.__isScriptSetup ? new Proxy(proxyToUse, {
        get(target, key, receiver) {
          warn$1(
            `Property '${String(
              key
            )}' was accessed via 'this'. Avoid using 'this' in templates.`
          );
          return Reflect.get(target, key, receiver);
        }
      }) : proxyToUse;
      result = normalizeVNode(
        render2.call(
          thisProxy,
          proxyToUse,
          renderCache,
          true ? shallowReadonly(props) : props,
          setupState,
          data,
          ctx
        )
      );
      fallthroughAttrs = attrs;
    } else {
      const render22 = Component;
      if (attrs === props) {
        markAttrsAccessed();
      }
      result = normalizeVNode(
        render22.length > 1 ? render22(
          true ? shallowReadonly(props) : props,
          true ? {
            get attrs() {
              markAttrsAccessed();
              return shallowReadonly(attrs);
            },
            slots,
            emit: emit2
          } : { attrs, slots, emit: emit2 }
        ) : render22(
          true ? shallowReadonly(props) : props,
          null
        )
      );
      fallthroughAttrs = Component.props ? attrs : getFunctionalFallthrough(attrs);
    }
  } catch (err) {
    blockStack.length = 0;
    handleError(err, instance, 1);
    result = createVNode(Comment);
  }
  let root = result;
  let setRoot = void 0;
  if (result.patchFlag > 0 && result.patchFlag & 2048) {
    [root, setRoot] = getChildRoot(result);
  }
  if (fallthroughAttrs && inheritAttrs !== false) {
    const keys = Object.keys(fallthroughAttrs);
    const { shapeFlag } = root;
    if (keys.length) {
      if (shapeFlag & (1 | 6)) {
        if (propsOptions && keys.some(isModelListener)) {
          fallthroughAttrs = filterModelListeners(
            fallthroughAttrs,
            propsOptions
          );
        }
        root = cloneVNode(root, fallthroughAttrs, false, true);
      } else if (!accessedAttrs && root.type !== Comment) {
        const allAttrs = Object.keys(attrs);
        const eventAttrs = [];
        const extraAttrs = [];
        for (let i = 0, l = allAttrs.length; i < l; i++) {
          const key = allAttrs[i];
          if (isOn(key)) {
            if (!isModelListener(key)) {
              eventAttrs.push(key[2].toLowerCase() + key.slice(3));
            }
          } else {
            extraAttrs.push(key);
          }
        }
        if (extraAttrs.length) {
          warn$1(
            `Extraneous non-props attributes (${extraAttrs.join(", ")}) were passed to component but could not be automatically inherited because component renders fragment or text or teleport root nodes.`
          );
        }
        if (eventAttrs.length) {
          warn$1(
            `Extraneous non-emits event listeners (${eventAttrs.join(", ")}) were passed to component but could not be automatically inherited because component renders fragment or text root nodes. If the listener is intended to be a component custom event listener only, declare it using the "emits" option.`
          );
        }
      }
    }
  }
  if (vnode.dirs) {
    if (!isElementRoot(root)) {
      warn$1(
        `Runtime directive used on component with non-element root node. The directives will not function as intended.`
      );
    }
    root = cloneVNode(root, null, false, true);
    root.dirs = root.dirs ? root.dirs.concat(vnode.dirs) : vnode.dirs;
  }
  if (vnode.transition) {
    if (!isElementRoot(root)) {
      warn$1(
        `Component inside <Transition> renders non-element root node that cannot be animated.`
      );
    }
    setTransitionHooks(root, vnode.transition);
  }
  if (setRoot) {
    setRoot(root);
  } else {
    result = root;
  }
  setCurrentRenderingInstance(prev);
  return result;
}
const getChildRoot = (vnode) => {
  const rawChildren = vnode.children;
  const dynamicChildren = vnode.dynamicChildren;
  const childRoot = filterSingleRoot(rawChildren, false);
  if (!childRoot) {
    return [vnode, void 0];
  } else if (childRoot.patchFlag > 0 && childRoot.patchFlag & 2048) {
    return getChildRoot(childRoot);
  }
  const index = rawChildren.indexOf(childRoot);
  const dynamicIndex = dynamicChildren ? dynamicChildren.indexOf(childRoot) : -1;
  const setRoot = (updatedRoot) => {
    rawChildren[index] = updatedRoot;
    if (dynamicChildren) {
      if (dynamicIndex > -1) {
        dynamicChildren[dynamicIndex] = updatedRoot;
      } else if (updatedRoot.patchFlag > 0) {
        vnode.dynamicChildren = [...dynamicChildren, updatedRoot];
      }
    }
  };
  return [normalizeVNode(childRoot), setRoot];
};
function filterSingleRoot(children, recurse = true) {
  let singleRoot;
  for (let i = 0; i < children.length; i++) {
    const child = children[i];
    if (isVNode(child)) {
      if (child.type !== Comment || child.children === "v-if") {
        if (singleRoot) {
          return;
        } else {
          singleRoot = child;
          if (recurse && singleRoot.patchFlag > 0 && singleRoot.patchFlag & 2048) {
            return filterSingleRoot(singleRoot.children);
          }
        }
      }
    } else {
      return;
    }
  }
  return singleRoot;
}
const getFunctionalFallthrough = (attrs) => {
  let res;
  for (const key in attrs) {
    if (key === "class" || key === "style" || isOn(key)) {
      (res || (res = {}))[key] = attrs[key];
    }
  }
  return res;
};
const filterModelListeners = (attrs, props) => {
  const res = {};
  for (const key in attrs) {
    if (!isModelListener(key) || !(key.slice(9) in props)) {
      res[key] = attrs[key];
    }
  }
  return res;
};
const isElementRoot = (vnode) => {
  return vnode.shapeFlag & (6 | 1) || vnode.type === Comment;
};
function shouldUpdateComponent(prevVNode, nextVNode, optimized) {
  const { props: prevProps, children: prevChildren, component } = prevVNode;
  const { props: nextProps, children: nextChildren, patchFlag } = nextVNode;
  const emits = component.emitsOptions;
  if ((prevChildren || nextChildren) && isHmrUpdating) {
    return true;
  }
  if (nextVNode.dirs || nextVNode.transition) {
    return true;
  }
  if (optimized && patchFlag >= 0) {
    if (patchFlag & 1024) {
      return true;
    }
    if (patchFlag & 16) {
      if (!prevProps) {
        return !!nextProps;
      }
      return hasPropsChanged(prevProps, nextProps, emits);
    } else if (patchFlag & 8) {
      const dynamicProps = nextVNode.dynamicProps;
      for (let i = 0; i < dynamicProps.length; i++) {
        const key = dynamicProps[i];
        if (nextProps[key] !== prevProps[key] && !isEmitListener(emits, key)) {
          return true;
        }
      }
    }
  } else {
    if (prevChildren || nextChildren) {
      if (!nextChildren || !nextChildren.$stable) {
        return true;
      }
    }
    if (prevProps === nextProps) {
      return false;
    }
    if (!prevProps) {
      return !!nextProps;
    }
    if (!nextProps) {
      return true;
    }
    return hasPropsChanged(prevProps, nextProps, emits);
  }
  return false;
}
function hasPropsChanged(prevProps, nextProps, emitsOptions) {
  const nextKeys = Object.keys(nextProps);
  if (nextKeys.length !== Object.keys(prevProps).length) {
    return true;
  }
  for (let i = 0; i < nextKeys.length; i++) {
    const key = nextKeys[i];
    if (nextProps[key] !== prevProps[key] && !isEmitListener(emitsOptions, key)) {
      return true;
    }
  }
  return false;
}
function updateHOCHostEl({ vnode, parent }, el) {
  while (parent) {
    const root = parent.subTree;
    if (root.suspense && root.suspense.activeBranch === vnode) {
      root.el = vnode.el;
    }
    if (root === vnode) {
      (vnode = parent.vnode).el = el;
      parent = parent.parent;
    } else {
      break;
    }
  }
}
const internalObjectProto = {};
const createInternalObject = () => Object.create(internalObjectProto);
const isInternalObject = (obj) => Object.getPrototypeOf(obj) === internalObjectProto;
function initProps(instance, rawProps, isStateful, isSSR = false) {
  const props = {};
  const attrs = createInternalObject();
  instance.propsDefaults = /* @__PURE__ */ Object.create(null);
  setFullProps(instance, rawProps, props, attrs);
  for (const key in instance.propsOptions[0]) {
    if (!(key in props)) {
      props[key] = void 0;
    }
  }
  {
    validateProps(rawProps || {}, props, instance);
  }
  if (isStateful) {
    instance.props = isSSR ? props : shallowReactive(props);
  } else {
    if (!instance.type.props) {
      instance.props = attrs;
    } else {
      instance.props = props;
    }
  }
  instance.attrs = attrs;
}
function isInHmrContext(instance) {
  while (instance) {
    if (instance.type.__hmrId) return true;
    instance = instance.parent;
  }
}
function updateProps(instance, rawProps, rawPrevProps, optimized) {
  const {
    props,
    attrs,
    vnode: { patchFlag }
  } = instance;
  const rawCurrentProps = toRaw(props);
  const [options] = instance.propsOptions;
  let hasAttrsChanged = false;
  if (
    // always force full diff in dev
    // - #1942 if hmr is enabled with sfc component
    // - vite#872 non-sfc component used by sfc component
    !isInHmrContext(instance) && (optimized || patchFlag > 0) && !(patchFlag & 16)
  ) {
    if (patchFlag & 8) {
      const propsToUpdate = instance.vnode.dynamicProps;
      for (let i = 0; i < propsToUpdate.length; i++) {
        let key = propsToUpdate[i];
        if (isEmitListener(instance.emitsOptions, key)) {
          continue;
        }
        const value = rawProps[key];
        if (options) {
          if (hasOwn(attrs, key)) {
            if (value !== attrs[key]) {
              attrs[key] = value;
              hasAttrsChanged = true;
            }
          } else {
            const camelizedKey = camelize(key);
            props[camelizedKey] = resolvePropValue(
              options,
              rawCurrentProps,
              camelizedKey,
              value,
              instance,
              false
            );
          }
        } else {
          if (value !== attrs[key]) {
            attrs[key] = value;
            hasAttrsChanged = true;
          }
        }
      }
    }
  } else {
    if (setFullProps(instance, rawProps, props, attrs)) {
      hasAttrsChanged = true;
    }
    let kebabKey;
    for (const key in rawCurrentProps) {
      if (!rawProps || // for camelCase
      !hasOwn(rawProps, key) && // it's possible the original props was passed in as kebab-case
      // and converted to camelCase (#955)
      ((kebabKey = hyphenate(key)) === key || !hasOwn(rawProps, kebabKey))) {
        if (options) {
          if (rawPrevProps && // for camelCase
          (rawPrevProps[key] !== void 0 || // for kebab-case
          rawPrevProps[kebabKey] !== void 0)) {
            props[key] = resolvePropValue(
              options,
              rawCurrentProps,
              key,
              void 0,
              instance,
              true
            );
          }
        } else {
          delete props[key];
        }
      }
    }
    if (attrs !== rawCurrentProps) {
      for (const key in attrs) {
        if (!rawProps || !hasOwn(rawProps, key) && true) {
          delete attrs[key];
          hasAttrsChanged = true;
        }
      }
    }
  }
  if (hasAttrsChanged) {
    trigger(instance.attrs, "set", "");
  }
  {
    validateProps(rawProps || {}, props, instance);
  }
}
function setFullProps(instance, rawProps, props, attrs) {
  const [options, needCastKeys] = instance.propsOptions;
  let hasAttrsChanged = false;
  let rawCastValues;
  if (rawProps) {
    for (let key in rawProps) {
      if (isReservedProp(key)) {
        continue;
      }
      const value = rawProps[key];
      let camelKey;
      if (options && hasOwn(options, camelKey = camelize(key))) {
        if (!needCastKeys || !needCastKeys.includes(camelKey)) {
          props[camelKey] = value;
        } else {
          (rawCastValues || (rawCastValues = {}))[camelKey] = value;
        }
      } else if (!isEmitListener(instance.emitsOptions, key)) {
        if (!(key in attrs) || value !== attrs[key]) {
          attrs[key] = value;
          hasAttrsChanged = true;
        }
      }
    }
  }
  if (needCastKeys) {
    const rawCurrentProps = toRaw(props);
    const castValues = rawCastValues || EMPTY_OBJ;
    for (let i = 0; i < needCastKeys.length; i++) {
      const key = needCastKeys[i];
      props[key] = resolvePropValue(
        options,
        rawCurrentProps,
        key,
        castValues[key],
        instance,
        !hasOwn(castValues, key)
      );
    }
  }
  return hasAttrsChanged;
}
function resolvePropValue(options, props, key, value, instance, isAbsent) {
  const opt = options[key];
  if (opt != null) {
    const hasDefault = hasOwn(opt, "default");
    if (hasDefault && value === void 0) {
      const defaultValue = opt.default;
      if (opt.type !== Function && !opt.skipFactory && isFunction$2(defaultValue)) {
        const { propsDefaults } = instance;
        if (key in propsDefaults) {
          value = propsDefaults[key];
        } else {
          const reset = setCurrentInstance(instance);
          value = propsDefaults[key] = defaultValue.call(
            null,
            props
          );
          reset();
        }
      } else {
        value = defaultValue;
      }
      if (instance.ce) {
        instance.ce._setProp(key, value);
      }
    }
    if (opt[
      0
      /* shouldCast */
    ]) {
      if (isAbsent && !hasDefault) {
        value = false;
      } else if (opt[
        1
        /* shouldCastTrue */
      ] && (value === "" || value === hyphenate(key))) {
        value = true;
      }
    }
  }
  return value;
}
const mixinPropsCache = /* @__PURE__ */ new WeakMap();
function normalizePropsOptions(comp, appContext, asMixin = false) {
  const cache = asMixin ? mixinPropsCache : appContext.propsCache;
  const cached = cache.get(comp);
  if (cached) {
    return cached;
  }
  const raw = comp.props;
  const normalized = {};
  const needCastKeys = [];
  let hasExtends = false;
  if (!isFunction$2(comp)) {
    const extendProps = (raw2) => {
      hasExtends = true;
      const [props, keys] = normalizePropsOptions(raw2, appContext, true);
      extend$1(normalized, props);
      if (keys) needCastKeys.push(...keys);
    };
    if (!asMixin && appContext.mixins.length) {
      appContext.mixins.forEach(extendProps);
    }
    if (comp.extends) {
      extendProps(comp.extends);
    }
    if (comp.mixins) {
      comp.mixins.forEach(extendProps);
    }
  }
  if (!raw && !hasExtends) {
    if (isObject$1(comp)) {
      cache.set(comp, EMPTY_ARR);
    }
    return EMPTY_ARR;
  }
  if (isArray$1(raw)) {
    for (let i = 0; i < raw.length; i++) {
      if (!isString$1(raw[i])) {
        warn$1(`props must be strings when using array syntax.`, raw[i]);
      }
      const normalizedKey = camelize(raw[i]);
      if (validatePropName(normalizedKey)) {
        normalized[normalizedKey] = EMPTY_OBJ;
      }
    }
  } else if (raw) {
    if (!isObject$1(raw)) {
      warn$1(`invalid props options`, raw);
    }
    for (const key in raw) {
      const normalizedKey = camelize(key);
      if (validatePropName(normalizedKey)) {
        const opt = raw[key];
        const prop = normalized[normalizedKey] = isArray$1(opt) || isFunction$2(opt) ? { type: opt } : extend$1({}, opt);
        const propType = prop.type;
        let shouldCast = false;
        let shouldCastTrue = true;
        if (isArray$1(propType)) {
          for (let index = 0; index < propType.length; ++index) {
            const type = propType[index];
            const typeName = isFunction$2(type) && type.name;
            if (typeName === "Boolean") {
              shouldCast = true;
              break;
            } else if (typeName === "String") {
              shouldCastTrue = false;
            }
          }
        } else {
          shouldCast = isFunction$2(propType) && propType.name === "Boolean";
        }
        prop[
          0
          /* shouldCast */
        ] = shouldCast;
        prop[
          1
          /* shouldCastTrue */
        ] = shouldCastTrue;
        if (shouldCast || hasOwn(prop, "default")) {
          needCastKeys.push(normalizedKey);
        }
      }
    }
  }
  const res = [normalized, needCastKeys];
  if (isObject$1(comp)) {
    cache.set(comp, res);
  }
  return res;
}
function validatePropName(key) {
  if (key[0] !== "$" && !isReservedProp(key)) {
    return true;
  } else {
    warn$1(`Invalid prop name: "${key}" is a reserved property.`);
  }
  return false;
}
function getType(ctor) {
  if (ctor === null) {
    return "null";
  }
  if (typeof ctor === "function") {
    return ctor.name || "";
  } else if (typeof ctor === "object") {
    const name = ctor.constructor && ctor.constructor.name;
    return name || "";
  }
  return "";
}
function validateProps(rawProps, props, instance) {
  const resolvedValues = toRaw(props);
  const options = instance.propsOptions[0];
  const camelizePropsKey = Object.keys(rawProps).map((key) => camelize(key));
  for (const key in options) {
    let opt = options[key];
    if (opt == null) continue;
    validateProp(
      key,
      resolvedValues[key],
      opt,
      shallowReadonly(resolvedValues),
      !camelizePropsKey.includes(key)
    );
  }
}
function validateProp(name, value, prop, props, isAbsent) {
  const { type, required, validator: validator2, skipCheck } = prop;
  if (required && isAbsent) {
    warn$1('Missing required prop: "' + name + '"');
    return;
  }
  if (value == null && !required) {
    return;
  }
  if (type != null && type !== true && !skipCheck) {
    let isValid = false;
    const types = isArray$1(type) ? type : [type];
    const expectedTypes = [];
    for (let i = 0; i < types.length && !isValid; i++) {
      const { valid: valid2, expectedType } = assertType(value, types[i]);
      expectedTypes.push(expectedType || "");
      isValid = valid2;
    }
    if (!isValid) {
      warn$1(getInvalidTypeMessage(name, value, expectedTypes));
      return;
    }
  }
  if (validator2 && !validator2(value, props)) {
    warn$1('Invalid prop: custom validator check failed for prop "' + name + '".');
  }
}
const isSimpleType = /* @__PURE__ */ makeMap(
  "String,Number,Boolean,Function,Symbol,BigInt"
);
function assertType(value, type) {
  let valid2;
  const expectedType = getType(type);
  if (expectedType === "null") {
    valid2 = value === null;
  } else if (isSimpleType(expectedType)) {
    const t7 = typeof value;
    valid2 = t7 === expectedType.toLowerCase();
    if (!valid2 && t7 === "object") {
      valid2 = value instanceof type;
    }
  } else if (expectedType === "Object") {
    valid2 = isObject$1(value);
  } else if (expectedType === "Array") {
    valid2 = isArray$1(value);
  } else {
    valid2 = value instanceof type;
  }
  return {
    valid: valid2,
    expectedType
  };
}
function getInvalidTypeMessage(name, value, expectedTypes) {
  if (expectedTypes.length === 0) {
    return `Prop type [] for prop "${name}" won't match anything. Did you mean to use type Array instead?`;
  }
  let message = `Invalid prop: type check failed for prop "${name}". Expected ${expectedTypes.map(capitalize).join(" | ")}`;
  const expectedType = expectedTypes[0];
  const receivedType = toRawType(value);
  const expectedValue = styleValue(value, expectedType);
  const receivedValue = styleValue(value, receivedType);
  if (expectedTypes.length === 1 && isExplicable(expectedType) && !isBoolean$1(expectedType, receivedType)) {
    message += ` with value ${expectedValue}`;
  }
  message += `, got ${receivedType} `;
  if (isExplicable(receivedType)) {
    message += `with value ${receivedValue}.`;
  }
  return message;
}
function styleValue(value, type) {
  if (type === "String") {
    return `"${value}"`;
  } else if (type === "Number") {
    return `${Number(value)}`;
  } else {
    return `${value}`;
  }
}
function isExplicable(type) {
  const explicitTypes = ["string", "number", "boolean"];
  return explicitTypes.some((elem) => type.toLowerCase() === elem);
}
function isBoolean$1(...args) {
  return args.some((elem) => elem.toLowerCase() === "boolean");
}
const isInternalKey = (key) => key === "_" || key === "_ctx" || key === "$stable";
const normalizeSlotValue = (value) => isArray$1(value) ? value.map(normalizeVNode) : [normalizeVNode(value)];
const normalizeSlot = (key, rawSlot, ctx) => {
  if (rawSlot._n) {
    return rawSlot;
  }
  const normalized = withCtx((...args) => {
    if (currentInstance && !(ctx === null && currentRenderingInstance) && !(ctx && ctx.root !== currentInstance.root)) {
      warn$1(
        `Slot "${key}" invoked outside of the render function: this will not track dependencies used in the slot. Invoke the slot function inside the render function instead.`
      );
    }
    return normalizeSlotValue(rawSlot(...args));
  }, ctx);
  normalized._c = false;
  return normalized;
};
const normalizeObjectSlots = (rawSlots, slots, instance) => {
  const ctx = rawSlots._ctx;
  for (const key in rawSlots) {
    if (isInternalKey(key)) continue;
    const value = rawSlots[key];
    if (isFunction$2(value)) {
      slots[key] = normalizeSlot(key, value, ctx);
    } else if (value != null) {
      {
        warn$1(
          `Non-function value encountered for slot "${key}". Prefer function slots for better performance.`
        );
      }
      const normalized = normalizeSlotValue(value);
      slots[key] = () => normalized;
    }
  }
};
const normalizeVNodeSlots = (instance, children) => {
  if (!isKeepAlive(instance.vnode) && true) {
    warn$1(
      `Non-function value encountered for default slot. Prefer function slots for better performance.`
    );
  }
  const normalized = normalizeSlotValue(children);
  instance.slots.default = () => normalized;
};
const assignSlots = (slots, children, optimized) => {
  for (const key in children) {
    if (optimized || !isInternalKey(key)) {
      slots[key] = children[key];
    }
  }
};
const initSlots = (instance, children, optimized) => {
  const slots = instance.slots = createInternalObject();
  if (instance.vnode.shapeFlag & 32) {
    const type = children._;
    if (type) {
      assignSlots(slots, children, optimized);
      if (optimized) {
        def(slots, "_", type, true);
      }
    } else {
      normalizeObjectSlots(children, slots);
    }
  } else if (children) {
    normalizeVNodeSlots(instance, children);
  }
};
const updateSlots = (instance, children, optimized) => {
  const { vnode, slots } = instance;
  let needDeletionCheck = true;
  let deletionComparisonTarget = EMPTY_OBJ;
  if (vnode.shapeFlag & 32) {
    const type = children._;
    if (type) {
      if (isHmrUpdating) {
        assignSlots(slots, children, optimized);
        trigger(instance, "set", "$slots");
      } else if (optimized && type === 1) {
        needDeletionCheck = false;
      } else {
        assignSlots(slots, children, optimized);
      }
    } else {
      needDeletionCheck = !children.$stable;
      normalizeObjectSlots(children, slots);
    }
    deletionComparisonTarget = children;
  } else if (children) {
    normalizeVNodeSlots(instance, children);
    deletionComparisonTarget = { default: 1 };
  }
  if (needDeletionCheck) {
    for (const key in slots) {
      if (!isInternalKey(key) && deletionComparisonTarget[key] == null) {
        delete slots[key];
      }
    }
  }
};
let supported;
let perf;
function startMeasure(instance, type) {
  if (instance.appContext.config.performance && isSupported()) {
    perf.mark(`vue-${type}-${instance.uid}`);
  }
  {
    devtoolsPerfStart(instance, type, isSupported() ? perf.now() : Date.now());
  }
}
function endMeasure(instance, type) {
  if (instance.appContext.config.performance && isSupported()) {
    const startTag = `vue-${type}-${instance.uid}`;
    const endTag = startTag + `:end`;
    const measureName = `<${formatComponentName(instance, instance.type)}> ${type}`;
    perf.mark(endTag);
    perf.measure(measureName, startTag, endTag);
    perf.clearMeasures(measureName);
    perf.clearMarks(startTag);
    perf.clearMarks(endTag);
  }
  {
    devtoolsPerfEnd(instance, type, isSupported() ? perf.now() : Date.now());
  }
}
function isSupported() {
  if (supported !== void 0) {
    return supported;
  }
  if (typeof window !== "undefined" && window.performance) {
    supported = true;
    perf = window.performance;
  } else {
    supported = false;
  }
  return supported;
}
function initFeatureFlags() {
  const needWarn = [];
  if (needWarn.length) {
    const multi = needWarn.length > 1;
    console.warn(
      `Feature flag${multi ? `s` : ``} ${needWarn.join(", ")} ${multi ? `are` : `is`} not explicitly defined. You are running the esm-bundler build of Vue, which expects these compile-time feature flags to be globally injected via the bundler config in order to get better tree-shaking in the production bundle.

For more details, see https://link.vuejs.org/feature-flags.`
    );
  }
}
const queuePostRenderEffect = queueEffectWithSuspense;
function createRenderer(options) {
  return baseCreateRenderer(options);
}
function baseCreateRenderer(options, createHydrationFns) {
  {
    initFeatureFlags();
  }
  const target = getGlobalThis();
  target.__VUE__ = true;
  {
    setDevtoolsHook$1(target.__VUE_DEVTOOLS_GLOBAL_HOOK__, target);
  }
  const {
    insert: hostInsert,
    remove: hostRemove,
    patchProp: hostPatchProp,
    createElement: hostCreateElement,
    createText: hostCreateText,
    createComment: hostCreateComment,
    setText: hostSetText,
    setElementText: hostSetElementText,
    parentNode: hostParentNode,
    nextSibling: hostNextSibling,
    setScopeId: hostSetScopeId = NOOP,
    insertStaticContent: hostInsertStaticContent
  } = options;
  const patch = (n1, n2, container, anchor = null, parentComponent = null, parentSuspense = null, namespace = void 0, slotScopeIds = null, optimized = isHmrUpdating ? false : !!n2.dynamicChildren) => {
    if (n1 === n2) {
      return;
    }
    if (n1 && !isSameVNodeType(n1, n2)) {
      anchor = getNextHostNode(n1);
      unmount(n1, parentComponent, parentSuspense, true);
      n1 = null;
    }
    if (n2.patchFlag === -2) {
      optimized = false;
      n2.dynamicChildren = null;
    }
    const { type, ref: ref3, shapeFlag } = n2;
    switch (type) {
      case Text:
        processText(n1, n2, container, anchor);
        break;
      case Comment:
        processCommentNode(n1, n2, container, anchor);
        break;
      case Static:
        if (n1 == null) {
          mountStaticNode(n2, container, anchor, namespace);
        } else {
          patchStaticNode(n1, n2, container, namespace);
        }
        break;
      case Fragment:
        processFragment(
          n1,
          n2,
          container,
          anchor,
          parentComponent,
          parentSuspense,
          namespace,
          slotScopeIds,
          optimized
        );
        break;
      default:
        if (shapeFlag & 1) {
          processElement(
            n1,
            n2,
            container,
            anchor,
            parentComponent,
            parentSuspense,
            namespace,
            slotScopeIds,
            optimized
          );
        } else if (shapeFlag & 6) {
          processComponent(
            n1,
            n2,
            container,
            anchor,
            parentComponent,
            parentSuspense,
            namespace,
            slotScopeIds,
            optimized
          );
        } else if (shapeFlag & 64) {
          type.process(
            n1,
            n2,
            container,
            anchor,
            parentComponent,
            parentSuspense,
            namespace,
            slotScopeIds,
            optimized,
            internals
          );
        } else if (shapeFlag & 128) {
          type.process(
            n1,
            n2,
            container,
            anchor,
            parentComponent,
            parentSuspense,
            namespace,
            slotScopeIds,
            optimized,
            internals
          );
        } else {
          warn$1("Invalid VNode type:", type, `(${typeof type})`);
        }
    }
    if (ref3 != null && parentComponent) {
      setRef(ref3, n1 && n1.ref, parentSuspense, n2 || n1, !n2);
    } else if (ref3 == null && n1 && n1.ref != null) {
      setRef(n1.ref, null, parentSuspense, n1, true);
    }
  };
  const processText = (n1, n2, container, anchor) => {
    if (n1 == null) {
      hostInsert(
        n2.el = hostCreateText(n2.children),
        container,
        anchor
      );
    } else {
      const el = n2.el = n1.el;
      if (n2.children !== n1.children) {
        hostSetText(el, n2.children);
      }
    }
  };
  const processCommentNode = (n1, n2, container, anchor) => {
    if (n1 == null) {
      hostInsert(
        n2.el = hostCreateComment(n2.children || ""),
        container,
        anchor
      );
    } else {
      n2.el = n1.el;
    }
  };
  const mountStaticNode = (n2, container, anchor, namespace) => {
    [n2.el, n2.anchor] = hostInsertStaticContent(
      n2.children,
      container,
      anchor,
      namespace,
      n2.el,
      n2.anchor
    );
  };
  const patchStaticNode = (n1, n2, container, namespace) => {
    if (n2.children !== n1.children) {
      const anchor = hostNextSibling(n1.anchor);
      removeStaticNode(n1);
      [n2.el, n2.anchor] = hostInsertStaticContent(
        n2.children,
        container,
        anchor,
        namespace
      );
    } else {
      n2.el = n1.el;
      n2.anchor = n1.anchor;
    }
  };
  const moveStaticNode = ({ el, anchor }, container, nextSibling) => {
    let next;
    while (el && el !== anchor) {
      next = hostNextSibling(el);
      hostInsert(el, container, nextSibling);
      el = next;
    }
    hostInsert(anchor, container, nextSibling);
  };
  const removeStaticNode = ({ el, anchor }) => {
    let next;
    while (el && el !== anchor) {
      next = hostNextSibling(el);
      hostRemove(el);
      el = next;
    }
    hostRemove(anchor);
  };
  const processElement = (n1, n2, container, anchor, parentComponent, parentSuspense, namespace, slotScopeIds, optimized) => {
    if (n2.type === "svg") {
      namespace = "svg";
    } else if (n2.type === "math") {
      namespace = "mathml";
    }
    if (n1 == null) {
      mountElement(
        n2,
        container,
        anchor,
        parentComponent,
        parentSuspense,
        namespace,
        slotScopeIds,
        optimized
      );
    } else {
      const customElement = !!(n1.el && n1.el._isVueCE) ? n1.el : null;
      try {
        if (customElement) {
          customElement._beginPatch();
        }
        patchElement(
          n1,
          n2,
          parentComponent,
          parentSuspense,
          namespace,
          slotScopeIds,
          optimized
        );
      } finally {
        if (customElement) {
          customElement._endPatch();
        }
      }
    }
  };
  const mountElement = (vnode, container, anchor, parentComponent, parentSuspense, namespace, slotScopeIds, optimized) => {
    let el;
    let vnodeHook;
    const { props, shapeFlag, transition, dirs } = vnode;
    el = vnode.el = hostCreateElement(
      vnode.type,
      namespace,
      props && props.is,
      props
    );
    if (shapeFlag & 8) {
      hostSetElementText(el, vnode.children);
    } else if (shapeFlag & 16) {
      mountChildren(
        vnode.children,
        el,
        null,
        parentComponent,
        parentSuspense,
        resolveChildrenNamespace(vnode, namespace),
        slotScopeIds,
        optimized
      );
    }
    if (dirs) {
      invokeDirectiveHook(vnode, null, parentComponent, "created");
    }
    setScopeId(el, vnode, vnode.scopeId, slotScopeIds, parentComponent);
    if (props) {
      for (const key in props) {
        if (key !== "value" && !isReservedProp(key)) {
          hostPatchProp(el, key, null, props[key], namespace, parentComponent);
        }
      }
      if ("value" in props) {
        hostPatchProp(el, "value", null, props.value, namespace);
      }
      if (vnodeHook = props.onVnodeBeforeMount) {
        invokeVNodeHook(vnodeHook, parentComponent, vnode);
      }
    }
    {
      def(el, "__vnode", vnode, true);
      def(el, "__vueParentComponent", parentComponent, true);
    }
    if (dirs) {
      invokeDirectiveHook(vnode, null, parentComponent, "beforeMount");
    }
    const needCallTransitionHooks = needTransition(parentSuspense, transition);
    if (needCallTransitionHooks) {
      transition.beforeEnter(el);
    }
    hostInsert(el, container, anchor);
    if ((vnodeHook = props && props.onVnodeMounted) || needCallTransitionHooks || dirs) {
      queuePostRenderEffect(() => {
        vnodeHook && invokeVNodeHook(vnodeHook, parentComponent, vnode);
        needCallTransitionHooks && transition.enter(el);
        dirs && invokeDirectiveHook(vnode, null, parentComponent, "mounted");
      }, parentSuspense);
    }
  };
  const setScopeId = (el, vnode, scopeId, slotScopeIds, parentComponent) => {
    if (scopeId) {
      hostSetScopeId(el, scopeId);
    }
    if (slotScopeIds) {
      for (let i = 0; i < slotScopeIds.length; i++) {
        hostSetScopeId(el, slotScopeIds[i]);
      }
    }
    if (parentComponent) {
      let subTree = parentComponent.subTree;
      if (subTree.patchFlag > 0 && subTree.patchFlag & 2048) {
        subTree = filterSingleRoot(subTree.children) || subTree;
      }
      if (vnode === subTree || isSuspense(subTree.type) && (subTree.ssContent === vnode || subTree.ssFallback === vnode)) {
        const parentVNode = parentComponent.vnode;
        setScopeId(
          el,
          parentVNode,
          parentVNode.scopeId,
          parentVNode.slotScopeIds,
          parentComponent.parent
        );
      }
    }
  };
  const mountChildren = (children, container, anchor, parentComponent, parentSuspense, namespace, slotScopeIds, optimized, start = 0) => {
    for (let i = start; i < children.length; i++) {
      const child = children[i] = optimized ? cloneIfMounted(children[i]) : normalizeVNode(children[i]);
      patch(
        null,
        child,
        container,
        anchor,
        parentComponent,
        parentSuspense,
        namespace,
        slotScopeIds,
        optimized
      );
    }
  };
  const patchElement = (n1, n2, parentComponent, parentSuspense, namespace, slotScopeIds, optimized) => {
    const el = n2.el = n1.el;
    {
      el.__vnode = n2;
    }
    let { patchFlag, dynamicChildren, dirs } = n2;
    patchFlag |= n1.patchFlag & 16;
    const oldProps = n1.props || EMPTY_OBJ;
    const newProps = n2.props || EMPTY_OBJ;
    let vnodeHook;
    parentComponent && toggleRecurse(parentComponent, false);
    if (vnodeHook = newProps.onVnodeBeforeUpdate) {
      invokeVNodeHook(vnodeHook, parentComponent, n2, n1);
    }
    if (dirs) {
      invokeDirectiveHook(n2, n1, parentComponent, "beforeUpdate");
    }
    parentComponent && toggleRecurse(parentComponent, true);
    if (isHmrUpdating) {
      patchFlag = 0;
      optimized = false;
      dynamicChildren = null;
    }
    if (oldProps.innerHTML && newProps.innerHTML == null || oldProps.textContent && newProps.textContent == null) {
      hostSetElementText(el, "");
    }
    if (dynamicChildren) {
      patchBlockChildren(
        n1.dynamicChildren,
        dynamicChildren,
        el,
        parentComponent,
        parentSuspense,
        resolveChildrenNamespace(n2, namespace),
        slotScopeIds
      );
      {
        traverseStaticChildren(n1, n2);
      }
    } else if (!optimized) {
      patchChildren(
        n1,
        n2,
        el,
        null,
        parentComponent,
        parentSuspense,
        resolveChildrenNamespace(n2, namespace),
        slotScopeIds,
        false
      );
    }
    if (patchFlag > 0) {
      if (patchFlag & 16) {
        patchProps(el, oldProps, newProps, parentComponent, namespace);
      } else {
        if (patchFlag & 2) {
          if (oldProps.class !== newProps.class) {
            hostPatchProp(el, "class", null, newProps.class, namespace);
          }
        }
        if (patchFlag & 4) {
          hostPatchProp(el, "style", oldProps.style, newProps.style, namespace);
        }
        if (patchFlag & 8) {
          const propsToUpdate = n2.dynamicProps;
          for (let i = 0; i < propsToUpdate.length; i++) {
            const key = propsToUpdate[i];
            const prev = oldProps[key];
            const next = newProps[key];
            if (next !== prev || key === "value") {
              hostPatchProp(el, key, prev, next, namespace, parentComponent);
            }
          }
        }
      }
      if (patchFlag & 1) {
        if (n1.children !== n2.children) {
          hostSetElementText(el, n2.children);
        }
      }
    } else if (!optimized && dynamicChildren == null) {
      patchProps(el, oldProps, newProps, parentComponent, namespace);
    }
    if ((vnodeHook = newProps.onVnodeUpdated) || dirs) {
      queuePostRenderEffect(() => {
        vnodeHook && invokeVNodeHook(vnodeHook, parentComponent, n2, n1);
        dirs && invokeDirectiveHook(n2, n1, parentComponent, "updated");
      }, parentSuspense);
    }
  };
  const patchBlockChildren = (oldChildren, newChildren, fallbackContainer, parentComponent, parentSuspense, namespace, slotScopeIds) => {
    for (let i = 0; i < newChildren.length; i++) {
      const oldVNode = oldChildren[i];
      const newVNode = newChildren[i];
      const container = (
        // oldVNode may be an errored async setup() component inside Suspense
        // which will not have a mounted element
        oldVNode.el && // - In the case of a Fragment, we need to provide the actual parent
        // of the Fragment itself so it can move its children.
        (oldVNode.type === Fragment || // - In the case of different nodes, there is going to be a replacement
        // which also requires the correct parent container
        !isSameVNodeType(oldVNode, newVNode) || // - In the case of a component, it could contain anything.
        oldVNode.shapeFlag & (6 | 64 | 128)) ? hostParentNode(oldVNode.el) : (
          // In other cases, the parent container is not actually used so we
          // just pass the block element here to avoid a DOM parentNode call.
          fallbackContainer
        )
      );
      patch(
        oldVNode,
        newVNode,
        container,
        null,
        parentComponent,
        parentSuspense,
        namespace,
        slotScopeIds,
        true
      );
    }
  };
  const patchProps = (el, oldProps, newProps, parentComponent, namespace) => {
    if (oldProps !== newProps) {
      if (oldProps !== EMPTY_OBJ) {
        for (const key in oldProps) {
          if (!isReservedProp(key) && !(key in newProps)) {
            hostPatchProp(
              el,
              key,
              oldProps[key],
              null,
              namespace,
              parentComponent
            );
          }
        }
      }
      for (const key in newProps) {
        if (isReservedProp(key)) continue;
        const next = newProps[key];
        const prev = oldProps[key];
        if (next !== prev && key !== "value") {
          hostPatchProp(el, key, prev, next, namespace, parentComponent);
        }
      }
      if ("value" in newProps) {
        hostPatchProp(el, "value", oldProps.value, newProps.value, namespace);
      }
    }
  };
  const processFragment = (n1, n2, container, anchor, parentComponent, parentSuspense, namespace, slotScopeIds, optimized) => {
    const fragmentStartAnchor = n2.el = n1 ? n1.el : hostCreateText("");
    const fragmentEndAnchor = n2.anchor = n1 ? n1.anchor : hostCreateText("");
    let { patchFlag, dynamicChildren, slotScopeIds: fragmentSlotScopeIds } = n2;
    if (
      // #5523 dev root fragment may inherit directives
      isHmrUpdating || patchFlag & 2048
    ) {
      patchFlag = 0;
      optimized = false;
      dynamicChildren = null;
    }
    if (fragmentSlotScopeIds) {
      slotScopeIds = slotScopeIds ? slotScopeIds.concat(fragmentSlotScopeIds) : fragmentSlotScopeIds;
    }
    if (n1 == null) {
      hostInsert(fragmentStartAnchor, container, anchor);
      hostInsert(fragmentEndAnchor, container, anchor);
      mountChildren(
        // #10007
        // such fragment like `<></>` will be compiled into
        // a fragment which doesn't have a children.
        // In this case fallback to an empty array
        n2.children || [],
        container,
        fragmentEndAnchor,
        parentComponent,
        parentSuspense,
        namespace,
        slotScopeIds,
        optimized
      );
    } else {
      if (patchFlag > 0 && patchFlag & 64 && dynamicChildren && // #2715 the previous fragment could've been a BAILed one as a result
      // of renderSlot() with no valid children
      n1.dynamicChildren) {
        patchBlockChildren(
          n1.dynamicChildren,
          dynamicChildren,
          container,
          parentComponent,
          parentSuspense,
          namespace,
          slotScopeIds
        );
        {
          traverseStaticChildren(n1, n2);
        }
      } else {
        patchChildren(
          n1,
          n2,
          container,
          fragmentEndAnchor,
          parentComponent,
          parentSuspense,
          namespace,
          slotScopeIds,
          optimized
        );
      }
    }
  };
  const processComponent = (n1, n2, container, anchor, parentComponent, parentSuspense, namespace, slotScopeIds, optimized) => {
    n2.slotScopeIds = slotScopeIds;
    if (n1 == null) {
      if (n2.shapeFlag & 512) {
        parentComponent.ctx.activate(
          n2,
          container,
          anchor,
          namespace,
          optimized
        );
      } else {
        mountComponent(
          n2,
          container,
          anchor,
          parentComponent,
          parentSuspense,
          namespace,
          optimized
        );
      }
    } else {
      updateComponent(n1, n2, optimized);
    }
  };
  const mountComponent = (initialVNode, container, anchor, parentComponent, parentSuspense, namespace, optimized) => {
    const instance = initialVNode.component = createComponentInstance(
      initialVNode,
      parentComponent,
      parentSuspense
    );
    if (instance.type.__hmrId) {
      registerHMR(instance);
    }
    {
      pushWarningContext(initialVNode);
      startMeasure(instance, `mount`);
    }
    if (isKeepAlive(initialVNode)) {
      instance.ctx.renderer = internals;
    }
    {
      {
        startMeasure(instance, `init`);
      }
      setupComponent(instance, false, optimized);
      {
        endMeasure(instance, `init`);
      }
    }
    if (isHmrUpdating) initialVNode.el = null;
    if (instance.asyncDep) {
      parentSuspense && parentSuspense.registerDep(instance, setupRenderEffect, optimized);
      if (!initialVNode.el) {
        const placeholder = instance.subTree = createVNode(Comment);
        processCommentNode(null, placeholder, container, anchor);
        initialVNode.placeholder = placeholder.el;
      }
    } else {
      setupRenderEffect(
        instance,
        initialVNode,
        container,
        anchor,
        parentSuspense,
        namespace,
        optimized
      );
    }
    {
      popWarningContext();
      endMeasure(instance, `mount`);
    }
  };
  const updateComponent = (n1, n2, optimized) => {
    const instance = n2.component = n1.component;
    if (shouldUpdateComponent(n1, n2, optimized)) {
      if (instance.asyncDep && !instance.asyncResolved) {
        {
          pushWarningContext(n2);
        }
        updateComponentPreRender(instance, n2, optimized);
        {
          popWarningContext();
        }
        return;
      } else {
        instance.next = n2;
        instance.update();
      }
    } else {
      n2.el = n1.el;
      instance.vnode = n2;
    }
  };
  const setupRenderEffect = (instance, initialVNode, container, anchor, parentSuspense, namespace, optimized) => {
    const componentUpdateFn = () => {
      if (!instance.isMounted) {
        let vnodeHook;
        const { el, props } = initialVNode;
        const { bm, m, parent, root, type } = instance;
        const isAsyncWrapperVNode = isAsyncWrapper(initialVNode);
        toggleRecurse(instance, false);
        if (bm) {
          invokeArrayFns(bm);
        }
        if (!isAsyncWrapperVNode && (vnodeHook = props && props.onVnodeBeforeMount)) {
          invokeVNodeHook(vnodeHook, parent, initialVNode);
        }
        toggleRecurse(instance, true);
        {
          if (root.ce && // @ts-expect-error _def is private
          root.ce._def.shadowRoot !== false) {
            root.ce._injectChildStyle(type);
          }
          {
            startMeasure(instance, `render`);
          }
          const subTree = instance.subTree = renderComponentRoot(instance);
          {
            endMeasure(instance, `render`);
          }
          {
            startMeasure(instance, `patch`);
          }
          patch(
            null,
            subTree,
            container,
            anchor,
            instance,
            parentSuspense,
            namespace
          );
          {
            endMeasure(instance, `patch`);
          }
          initialVNode.el = subTree.el;
        }
        if (m) {
          queuePostRenderEffect(m, parentSuspense);
        }
        if (!isAsyncWrapperVNode && (vnodeHook = props && props.onVnodeMounted)) {
          const scopedInitialVNode = initialVNode;
          queuePostRenderEffect(
            () => invokeVNodeHook(vnodeHook, parent, scopedInitialVNode),
            parentSuspense
          );
        }
        if (initialVNode.shapeFlag & 256 || parent && isAsyncWrapper(parent.vnode) && parent.vnode.shapeFlag & 256) {
          instance.a && queuePostRenderEffect(instance.a, parentSuspense);
        }
        instance.isMounted = true;
        {
          devtoolsComponentAdded(instance);
        }
        initialVNode = container = anchor = null;
      } else {
        let { next, bu, u, parent, vnode } = instance;
        {
          const nonHydratedAsyncRoot = locateNonHydratedAsyncRoot(instance);
          if (nonHydratedAsyncRoot) {
            if (next) {
              next.el = vnode.el;
              updateComponentPreRender(instance, next, optimized);
            }
            nonHydratedAsyncRoot.asyncDep.then(() => {
              if (!instance.isUnmounted) {
                componentUpdateFn();
              }
            });
            return;
          }
        }
        let originNext = next;
        let vnodeHook;
        {
          pushWarningContext(next || instance.vnode);
        }
        toggleRecurse(instance, false);
        if (next) {
          next.el = vnode.el;
          updateComponentPreRender(instance, next, optimized);
        } else {
          next = vnode;
        }
        if (bu) {
          invokeArrayFns(bu);
        }
        if (vnodeHook = next.props && next.props.onVnodeBeforeUpdate) {
          invokeVNodeHook(vnodeHook, parent, next, vnode);
        }
        toggleRecurse(instance, true);
        {
          startMeasure(instance, `render`);
        }
        const nextTree = renderComponentRoot(instance);
        {
          endMeasure(instance, `render`);
        }
        const prevTree = instance.subTree;
        instance.subTree = nextTree;
        {
          startMeasure(instance, `patch`);
        }
        patch(
          prevTree,
          nextTree,
          // parent may have changed if it's in a teleport
          hostParentNode(prevTree.el),
          // anchor may have changed if it's in a fragment
          getNextHostNode(prevTree),
          instance,
          parentSuspense,
          namespace
        );
        {
          endMeasure(instance, `patch`);
        }
        next.el = nextTree.el;
        if (originNext === null) {
          updateHOCHostEl(instance, nextTree.el);
        }
        if (u) {
          queuePostRenderEffect(u, parentSuspense);
        }
        if (vnodeHook = next.props && next.props.onVnodeUpdated) {
          queuePostRenderEffect(
            () => invokeVNodeHook(vnodeHook, parent, next, vnode),
            parentSuspense
          );
        }
        {
          devtoolsComponentUpdated(instance);
        }
        {
          popWarningContext();
        }
      }
    };
    instance.scope.on();
    const effect2 = instance.effect = new ReactiveEffect(componentUpdateFn);
    instance.scope.off();
    const update = instance.update = effect2.run.bind(effect2);
    const job = instance.job = effect2.runIfDirty.bind(effect2);
    job.i = instance;
    job.id = instance.uid;
    effect2.scheduler = () => queueJob(job);
    toggleRecurse(instance, true);
    {
      effect2.onTrack = instance.rtc ? (e) => invokeArrayFns(instance.rtc, e) : void 0;
      effect2.onTrigger = instance.rtg ? (e) => invokeArrayFns(instance.rtg, e) : void 0;
    }
    update();
  };
  const updateComponentPreRender = (instance, nextVNode, optimized) => {
    nextVNode.component = instance;
    const prevProps = instance.vnode.props;
    instance.vnode = nextVNode;
    instance.next = null;
    updateProps(instance, nextVNode.props, prevProps, optimized);
    updateSlots(instance, nextVNode.children, optimized);
    pauseTracking();
    flushPreFlushCbs(instance);
    resetTracking();
  };
  const patchChildren = (n1, n2, container, anchor, parentComponent, parentSuspense, namespace, slotScopeIds, optimized = false) => {
    const c1 = n1 && n1.children;
    const prevShapeFlag = n1 ? n1.shapeFlag : 0;
    const c2 = n2.children;
    const { patchFlag, shapeFlag } = n2;
    if (patchFlag > 0) {
      if (patchFlag & 128) {
        patchKeyedChildren(
          c1,
          c2,
          container,
          anchor,
          parentComponent,
          parentSuspense,
          namespace,
          slotScopeIds,
          optimized
        );
        return;
      } else if (patchFlag & 256) {
        patchUnkeyedChildren(
          c1,
          c2,
          container,
          anchor,
          parentComponent,
          parentSuspense,
          namespace,
          slotScopeIds,
          optimized
        );
        return;
      }
    }
    if (shapeFlag & 8) {
      if (prevShapeFlag & 16) {
        unmountChildren(c1, parentComponent, parentSuspense);
      }
      if (c2 !== c1) {
        hostSetElementText(container, c2);
      }
    } else {
      if (prevShapeFlag & 16) {
        if (shapeFlag & 16) {
          patchKeyedChildren(
            c1,
            c2,
            container,
            anchor,
            parentComponent,
            parentSuspense,
            namespace,
            slotScopeIds,
            optimized
          );
        } else {
          unmountChildren(c1, parentComponent, parentSuspense, true);
        }
      } else {
        if (prevShapeFlag & 8) {
          hostSetElementText(container, "");
        }
        if (shapeFlag & 16) {
          mountChildren(
            c2,
            container,
            anchor,
            parentComponent,
            parentSuspense,
            namespace,
            slotScopeIds,
            optimized
          );
        }
      }
    }
  };
  const patchUnkeyedChildren = (c1, c2, container, anchor, parentComponent, parentSuspense, namespace, slotScopeIds, optimized) => {
    c1 = c1 || EMPTY_ARR;
    c2 = c2 || EMPTY_ARR;
    const oldLength = c1.length;
    const newLength = c2.length;
    const commonLength = Math.min(oldLength, newLength);
    let i;
    for (i = 0; i < commonLength; i++) {
      const nextChild = c2[i] = optimized ? cloneIfMounted(c2[i]) : normalizeVNode(c2[i]);
      patch(
        c1[i],
        nextChild,
        container,
        null,
        parentComponent,
        parentSuspense,
        namespace,
        slotScopeIds,
        optimized
      );
    }
    if (oldLength > newLength) {
      unmountChildren(
        c1,
        parentComponent,
        parentSuspense,
        true,
        false,
        commonLength
      );
    } else {
      mountChildren(
        c2,
        container,
        anchor,
        parentComponent,
        parentSuspense,
        namespace,
        slotScopeIds,
        optimized,
        commonLength
      );
    }
  };
  const patchKeyedChildren = (c1, c2, container, parentAnchor, parentComponent, parentSuspense, namespace, slotScopeIds, optimized) => {
    let i = 0;
    const l2 = c2.length;
    let e1 = c1.length - 1;
    let e2 = l2 - 1;
    while (i <= e1 && i <= e2) {
      const n1 = c1[i];
      const n2 = c2[i] = optimized ? cloneIfMounted(c2[i]) : normalizeVNode(c2[i]);
      if (isSameVNodeType(n1, n2)) {
        patch(
          n1,
          n2,
          container,
          null,
          parentComponent,
          parentSuspense,
          namespace,
          slotScopeIds,
          optimized
        );
      } else {
        break;
      }
      i++;
    }
    while (i <= e1 && i <= e2) {
      const n1 = c1[e1];
      const n2 = c2[e2] = optimized ? cloneIfMounted(c2[e2]) : normalizeVNode(c2[e2]);
      if (isSameVNodeType(n1, n2)) {
        patch(
          n1,
          n2,
          container,
          null,
          parentComponent,
          parentSuspense,
          namespace,
          slotScopeIds,
          optimized
        );
      } else {
        break;
      }
      e1--;
      e2--;
    }
    if (i > e1) {
      if (i <= e2) {
        const nextPos = e2 + 1;
        const anchor = nextPos < l2 ? c2[nextPos].el : parentAnchor;
        while (i <= e2) {
          patch(
            null,
            c2[i] = optimized ? cloneIfMounted(c2[i]) : normalizeVNode(c2[i]),
            container,
            anchor,
            parentComponent,
            parentSuspense,
            namespace,
            slotScopeIds,
            optimized
          );
          i++;
        }
      }
    } else if (i > e2) {
      while (i <= e1) {
        unmount(c1[i], parentComponent, parentSuspense, true);
        i++;
      }
    } else {
      const s1 = i;
      const s2 = i;
      const keyToNewIndexMap = /* @__PURE__ */ new Map();
      for (i = s2; i <= e2; i++) {
        const nextChild = c2[i] = optimized ? cloneIfMounted(c2[i]) : normalizeVNode(c2[i]);
        if (nextChild.key != null) {
          if (keyToNewIndexMap.has(nextChild.key)) {
            warn$1(
              `Duplicate keys found during update:`,
              JSON.stringify(nextChild.key),
              `Make sure keys are unique.`
            );
          }
          keyToNewIndexMap.set(nextChild.key, i);
        }
      }
      let j;
      let patched = 0;
      const toBePatched = e2 - s2 + 1;
      let moved = false;
      let maxNewIndexSoFar = 0;
      const newIndexToOldIndexMap = new Array(toBePatched);
      for (i = 0; i < toBePatched; i++) newIndexToOldIndexMap[i] = 0;
      for (i = s1; i <= e1; i++) {
        const prevChild = c1[i];
        if (patched >= toBePatched) {
          unmount(prevChild, parentComponent, parentSuspense, true);
          continue;
        }
        let newIndex;
        if (prevChild.key != null) {
          newIndex = keyToNewIndexMap.get(prevChild.key);
        } else {
          for (j = s2; j <= e2; j++) {
            if (newIndexToOldIndexMap[j - s2] === 0 && isSameVNodeType(prevChild, c2[j])) {
              newIndex = j;
              break;
            }
          }
        }
        if (newIndex === void 0) {
          unmount(prevChild, parentComponent, parentSuspense, true);
        } else {
          newIndexToOldIndexMap[newIndex - s2] = i + 1;
          if (newIndex >= maxNewIndexSoFar) {
            maxNewIndexSoFar = newIndex;
          } else {
            moved = true;
          }
          patch(
            prevChild,
            c2[newIndex],
            container,
            null,
            parentComponent,
            parentSuspense,
            namespace,
            slotScopeIds,
            optimized
          );
          patched++;
        }
      }
      const increasingNewIndexSequence = moved ? getSequence(newIndexToOldIndexMap) : EMPTY_ARR;
      j = increasingNewIndexSequence.length - 1;
      for (i = toBePatched - 1; i >= 0; i--) {
        const nextIndex = s2 + i;
        const nextChild = c2[nextIndex];
        const anchorVNode = c2[nextIndex + 1];
        const anchor = nextIndex + 1 < l2 ? (
          // #13559, fallback to el placeholder for unresolved async component
          anchorVNode.el || anchorVNode.placeholder
        ) : parentAnchor;
        if (newIndexToOldIndexMap[i] === 0) {
          patch(
            null,
            nextChild,
            container,
            anchor,
            parentComponent,
            parentSuspense,
            namespace,
            slotScopeIds,
            optimized
          );
        } else if (moved) {
          if (j < 0 || i !== increasingNewIndexSequence[j]) {
            move(nextChild, container, anchor, 2);
          } else {
            j--;
          }
        }
      }
    }
  };
  const move = (vnode, container, anchor, moveType, parentSuspense = null) => {
    const { el, type, transition, children, shapeFlag } = vnode;
    if (shapeFlag & 6) {
      move(vnode.component.subTree, container, anchor, moveType);
      return;
    }
    if (shapeFlag & 128) {
      vnode.suspense.move(container, anchor, moveType);
      return;
    }
    if (shapeFlag & 64) {
      type.move(vnode, container, anchor, internals);
      return;
    }
    if (type === Fragment) {
      hostInsert(el, container, anchor);
      for (let i = 0; i < children.length; i++) {
        move(children[i], container, anchor, moveType);
      }
      hostInsert(vnode.anchor, container, anchor);
      return;
    }
    if (type === Static) {
      moveStaticNode(vnode, container, anchor);
      return;
    }
    const needTransition2 = moveType !== 2 && shapeFlag & 1 && transition;
    if (needTransition2) {
      if (moveType === 0) {
        transition.beforeEnter(el);
        hostInsert(el, container, anchor);
        queuePostRenderEffect(() => transition.enter(el), parentSuspense);
      } else {
        const { leave, delayLeave, afterLeave } = transition;
        const remove22 = () => {
          if (vnode.ctx.isUnmounted) {
            hostRemove(el);
          } else {
            hostInsert(el, container, anchor);
          }
        };
        const performLeave = () => {
          if (el._isLeaving) {
            el[leaveCbKey](
              true
              /* cancelled */
            );
          }
          leave(el, () => {
            remove22();
            afterLeave && afterLeave();
          });
        };
        if (delayLeave) {
          delayLeave(el, remove22, performLeave);
        } else {
          performLeave();
        }
      }
    } else {
      hostInsert(el, container, anchor);
    }
  };
  const unmount = (vnode, parentComponent, parentSuspense, doRemove = false, optimized = false) => {
    const {
      type,
      props,
      ref: ref3,
      children,
      dynamicChildren,
      shapeFlag,
      patchFlag,
      dirs,
      cacheIndex
    } = vnode;
    if (patchFlag === -2) {
      optimized = false;
    }
    if (ref3 != null) {
      pauseTracking();
      setRef(ref3, null, parentSuspense, vnode, true);
      resetTracking();
    }
    if (cacheIndex != null) {
      parentComponent.renderCache[cacheIndex] = void 0;
    }
    if (shapeFlag & 256) {
      parentComponent.ctx.deactivate(vnode);
      return;
    }
    const shouldInvokeDirs = shapeFlag & 1 && dirs;
    const shouldInvokeVnodeHook = !isAsyncWrapper(vnode);
    let vnodeHook;
    if (shouldInvokeVnodeHook && (vnodeHook = props && props.onVnodeBeforeUnmount)) {
      invokeVNodeHook(vnodeHook, parentComponent, vnode);
    }
    if (shapeFlag & 6) {
      unmountComponent(vnode.component, parentSuspense, doRemove);
    } else {
      if (shapeFlag & 128) {
        vnode.suspense.unmount(parentSuspense, doRemove);
        return;
      }
      if (shouldInvokeDirs) {
        invokeDirectiveHook(vnode, null, parentComponent, "beforeUnmount");
      }
      if (shapeFlag & 64) {
        vnode.type.remove(
          vnode,
          parentComponent,
          parentSuspense,
          internals,
          doRemove
        );
      } else if (dynamicChildren && // #5154
      // when v-once is used inside a block, setBlockTracking(-1) marks the
      // parent block with hasOnce: true
      // so that it doesn't take the fast path during unmount - otherwise
      // components nested in v-once are never unmounted.
      !dynamicChildren.hasOnce && // #1153: fast path should not be taken for non-stable (v-for) fragments
      (type !== Fragment || patchFlag > 0 && patchFlag & 64)) {
        unmountChildren(
          dynamicChildren,
          parentComponent,
          parentSuspense,
          false,
          true
        );
      } else if (type === Fragment && patchFlag & (128 | 256) || !optimized && shapeFlag & 16) {
        unmountChildren(children, parentComponent, parentSuspense);
      }
      if (doRemove) {
        remove2(vnode);
      }
    }
    if (shouldInvokeVnodeHook && (vnodeHook = props && props.onVnodeUnmounted) || shouldInvokeDirs) {
      queuePostRenderEffect(() => {
        vnodeHook && invokeVNodeHook(vnodeHook, parentComponent, vnode);
        shouldInvokeDirs && invokeDirectiveHook(vnode, null, parentComponent, "unmounted");
      }, parentSuspense);
    }
  };
  const remove2 = (vnode) => {
    const { type, el, anchor, transition } = vnode;
    if (type === Fragment) {
      if (vnode.patchFlag > 0 && vnode.patchFlag & 2048 && transition && !transition.persisted) {
        vnode.children.forEach((child) => {
          if (child.type === Comment) {
            hostRemove(child.el);
          } else {
            remove2(child);
          }
        });
      } else {
        removeFragment(el, anchor);
      }
      return;
    }
    if (type === Static) {
      removeStaticNode(vnode);
      return;
    }
    const performRemove = () => {
      hostRemove(el);
      if (transition && !transition.persisted && transition.afterLeave) {
        transition.afterLeave();
      }
    };
    if (vnode.shapeFlag & 1 && transition && !transition.persisted) {
      const { leave, delayLeave } = transition;
      const performLeave = () => leave(el, performRemove);
      if (delayLeave) {
        delayLeave(vnode.el, performRemove, performLeave);
      } else {
        performLeave();
      }
    } else {
      performRemove();
    }
  };
  const removeFragment = (cur, end) => {
    let next;
    while (cur !== end) {
      next = hostNextSibling(cur);
      hostRemove(cur);
      cur = next;
    }
    hostRemove(end);
  };
  const unmountComponent = (instance, parentSuspense, doRemove) => {
    if (instance.type.__hmrId) {
      unregisterHMR(instance);
    }
    const { bum, scope, job, subTree, um, m, a } = instance;
    invalidateMount(m);
    invalidateMount(a);
    if (bum) {
      invokeArrayFns(bum);
    }
    scope.stop();
    if (job) {
      job.flags |= 8;
      unmount(subTree, instance, parentSuspense, doRemove);
    }
    if (um) {
      queuePostRenderEffect(um, parentSuspense);
    }
    queuePostRenderEffect(() => {
      instance.isUnmounted = true;
    }, parentSuspense);
    {
      devtoolsComponentRemoved(instance);
    }
  };
  const unmountChildren = (children, parentComponent, parentSuspense, doRemove = false, optimized = false, start = 0) => {
    for (let i = start; i < children.length; i++) {
      unmount(children[i], parentComponent, parentSuspense, doRemove, optimized);
    }
  };
  const getNextHostNode = (vnode) => {
    if (vnode.shapeFlag & 6) {
      return getNextHostNode(vnode.component.subTree);
    }
    if (vnode.shapeFlag & 128) {
      return vnode.suspense.next();
    }
    const el = hostNextSibling(vnode.anchor || vnode.el);
    const teleportEnd = el && el[TeleportEndKey];
    return teleportEnd ? hostNextSibling(teleportEnd) : el;
  };
  let isFlushing = false;
  const render2 = (vnode, container, namespace) => {
    if (vnode == null) {
      if (container._vnode) {
        unmount(container._vnode, null, null, true);
      }
    } else {
      patch(
        container._vnode || null,
        vnode,
        container,
        null,
        null,
        null,
        namespace
      );
    }
    container._vnode = vnode;
    if (!isFlushing) {
      isFlushing = true;
      flushPreFlushCbs();
      flushPostFlushCbs();
      isFlushing = false;
    }
  };
  const internals = {
    p: patch,
    um: unmount,
    m: move,
    r: remove2,
    mt: mountComponent,
    mc: mountChildren,
    pc: patchChildren,
    pbc: patchBlockChildren,
    n: getNextHostNode,
    o: options
  };
  let hydrate;
  return {
    render: render2,
    hydrate,
    createApp: createAppAPI(render2)
  };
}
function resolveChildrenNamespace({ type, props }, currentNamespace) {
  return currentNamespace === "svg" && type === "foreignObject" || currentNamespace === "mathml" && type === "annotation-xml" && props && props.encoding && props.encoding.includes("html") ? void 0 : currentNamespace;
}
function toggleRecurse({ effect: effect2, job }, allowed) {
  if (allowed) {
    effect2.flags |= 32;
    job.flags |= 4;
  } else {
    effect2.flags &= -33;
    job.flags &= -5;
  }
}
function needTransition(parentSuspense, transition) {
  return (!parentSuspense || parentSuspense && !parentSuspense.pendingBranch) && transition && !transition.persisted;
}
function traverseStaticChildren(n1, n2, shallow = false) {
  const ch1 = n1.children;
  const ch2 = n2.children;
  if (isArray$1(ch1) && isArray$1(ch2)) {
    for (let i = 0; i < ch1.length; i++) {
      const c1 = ch1[i];
      let c2 = ch2[i];
      if (c2.shapeFlag & 1 && !c2.dynamicChildren) {
        if (c2.patchFlag <= 0 || c2.patchFlag === 32) {
          c2 = ch2[i] = cloneIfMounted(ch2[i]);
          c2.el = c1.el;
        }
        if (!shallow && c2.patchFlag !== -2)
          traverseStaticChildren(c1, c2);
      }
      if (c2.type === Text && // avoid cached text nodes retaining detached dom nodes
      c2.patchFlag !== -1) {
        c2.el = c1.el;
      }
      if (c2.type === Comment && !c2.el) {
        c2.el = c1.el;
      }
      {
        c2.el && (c2.el.__vnode = c2);
      }
    }
  }
}
function getSequence(arr) {
  const p2 = arr.slice();
  const result = [0];
  let i, j, u, v, c;
  const len = arr.length;
  for (i = 0; i < len; i++) {
    const arrI = arr[i];
    if (arrI !== 0) {
      j = result[result.length - 1];
      if (arr[j] < arrI) {
        p2[i] = j;
        result.push(i);
        continue;
      }
      u = 0;
      v = result.length - 1;
      while (u < v) {
        c = u + v >> 1;
        if (arr[result[c]] < arrI) {
          u = c + 1;
        } else {
          v = c;
        }
      }
      if (arrI < arr[result[u]]) {
        if (u > 0) {
          p2[i] = result[u - 1];
        }
        result[u] = i;
      }
    }
  }
  u = result.length;
  v = result[u - 1];
  while (u-- > 0) {
    result[u] = v;
    v = p2[v];
  }
  return result;
}
function locateNonHydratedAsyncRoot(instance) {
  const subComponent = instance.subTree.component;
  if (subComponent) {
    if (subComponent.asyncDep && !subComponent.asyncResolved) {
      return subComponent;
    } else {
      return locateNonHydratedAsyncRoot(subComponent);
    }
  }
}
function invalidateMount(hooks) {
  if (hooks) {
    for (let i = 0; i < hooks.length; i++)
      hooks[i].flags |= 8;
  }
}
const isSuspense = (type) => type.__isSuspense;
function queueEffectWithSuspense(fn, suspense) {
  if (suspense && suspense.pendingBranch) {
    if (isArray$1(fn)) {
      suspense.effects.push(...fn);
    } else {
      suspense.effects.push(fn);
    }
  } else {
    queuePostFlushCb(fn);
  }
}
const Fragment = Symbol.for("v-fgt");
const Text = Symbol.for("v-txt");
const Comment = Symbol.for("v-cmt");
const Static = Symbol.for("v-stc");
const blockStack = [];
let currentBlock = null;
function openBlock(disableTracking = false) {
  blockStack.push(currentBlock = disableTracking ? null : []);
}
function closeBlock() {
  blockStack.pop();
  currentBlock = blockStack[blockStack.length - 1] || null;
}
let isBlockTreeEnabled = 1;
function setBlockTracking(value, inVOnce = false) {
  isBlockTreeEnabled += value;
  if (value < 0 && currentBlock && inVOnce) {
    currentBlock.hasOnce = true;
  }
}
function setupBlock(vnode) {
  vnode.dynamicChildren = isBlockTreeEnabled > 0 ? currentBlock || EMPTY_ARR : null;
  closeBlock();
  if (isBlockTreeEnabled > 0 && currentBlock) {
    currentBlock.push(vnode);
  }
  return vnode;
}
function createElementBlock(type, props, children, patchFlag, dynamicProps, shapeFlag) {
  return setupBlock(
    createBaseVNode(
      type,
      props,
      children,
      patchFlag,
      dynamicProps,
      shapeFlag,
      true
    )
  );
}
function createBlock(type, props, children, patchFlag, dynamicProps) {
  return setupBlock(
    createVNode(
      type,
      props,
      children,
      patchFlag,
      dynamicProps,
      true
    )
  );
}
function isVNode(value) {
  return value ? value.__v_isVNode === true : false;
}
function isSameVNodeType(n1, n2) {
  if (n2.shapeFlag & 6 && n1.component) {
    const dirtyInstances = hmrDirtyComponents.get(n2.type);
    if (dirtyInstances && dirtyInstances.has(n1.component)) {
      n1.shapeFlag &= -257;
      n2.shapeFlag &= -513;
      return false;
    }
  }
  return n1.type === n2.type && n1.key === n2.key;
}
const createVNodeWithArgsTransform = (...args) => {
  return _createVNode(
    ...args
  );
};
const normalizeKey = ({ key }) => key != null ? key : null;
const normalizeRef = ({
  ref: ref3,
  ref_key,
  ref_for
}) => {
  if (typeof ref3 === "number") {
    ref3 = "" + ref3;
  }
  return ref3 != null ? isString$1(ref3) || isRef(ref3) || isFunction$2(ref3) ? { i: currentRenderingInstance, r: ref3, k: ref_key, f: !!ref_for } : ref3 : null;
};
function createBaseVNode(type, props = null, children = null, patchFlag = 0, dynamicProps = null, shapeFlag = type === Fragment ? 0 : 1, isBlockNode = false, needFullChildrenNormalization = false) {
  const vnode = {
    __v_isVNode: true,
    __v_skip: true,
    type,
    props,
    key: props && normalizeKey(props),
    ref: props && normalizeRef(props),
    scopeId: currentScopeId,
    slotScopeIds: null,
    children,
    component: null,
    suspense: null,
    ssContent: null,
    ssFallback: null,
    dirs: null,
    transition: null,
    el: null,
    anchor: null,
    target: null,
    targetStart: null,
    targetAnchor: null,
    staticCount: 0,
    shapeFlag,
    patchFlag,
    dynamicProps,
    dynamicChildren: null,
    appContext: null,
    ctx: currentRenderingInstance
  };
  if (needFullChildrenNormalization) {
    normalizeChildren(vnode, children);
    if (shapeFlag & 128) {
      type.normalize(vnode);
    }
  } else if (children) {
    vnode.shapeFlag |= isString$1(children) ? 8 : 16;
  }
  if (vnode.key !== vnode.key) {
    warn$1(`VNode created with invalid key (NaN). VNode type:`, vnode.type);
  }
  if (isBlockTreeEnabled > 0 && // avoid a block node from tracking itself
  !isBlockNode && // has current parent block
  currentBlock && // presence of a patch flag indicates this node needs patching on updates.
  // component nodes also should always be patched, because even if the
  // component doesn't need to update, it needs to persist the instance on to
  // the next vnode so that it can be properly unmounted later.
  (vnode.patchFlag > 0 || shapeFlag & 6) && // the EVENTS flag is only for hydration and if it is the only flag, the
  // vnode should not be considered dynamic due to handler caching.
  vnode.patchFlag !== 32) {
    currentBlock.push(vnode);
  }
  return vnode;
}
const createVNode = createVNodeWithArgsTransform;
function _createVNode(type, props = null, children = null, patchFlag = 0, dynamicProps = null, isBlockNode = false) {
  if (!type || type === NULL_DYNAMIC_COMPONENT) {
    if (!type) {
      warn$1(`Invalid vnode type when creating vnode: ${type}.`);
    }
    type = Comment;
  }
  if (isVNode(type)) {
    const cloned = cloneVNode(
      type,
      props,
      true
      /* mergeRef: true */
    );
    if (children) {
      normalizeChildren(cloned, children);
    }
    if (isBlockTreeEnabled > 0 && !isBlockNode && currentBlock) {
      if (cloned.shapeFlag & 6) {
        currentBlock[currentBlock.indexOf(type)] = cloned;
      } else {
        currentBlock.push(cloned);
      }
    }
    cloned.patchFlag = -2;
    return cloned;
  }
  if (isClassComponent(type)) {
    type = type.__vccOpts;
  }
  if (props) {
    props = guardReactiveProps(props);
    let { class: klass, style } = props;
    if (klass && !isString$1(klass)) {
      props.class = normalizeClass(klass);
    }
    if (isObject$1(style)) {
      if (isProxy(style) && !isArray$1(style)) {
        style = extend$1({}, style);
      }
      props.style = normalizeStyle(style);
    }
  }
  const shapeFlag = isString$1(type) ? 1 : isSuspense(type) ? 128 : isTeleport(type) ? 64 : isObject$1(type) ? 4 : isFunction$2(type) ? 2 : 0;
  if (shapeFlag & 4 && isProxy(type)) {
    type = toRaw(type);
    warn$1(
      `Vue received a Component that was made a reactive object. This can lead to unnecessary performance overhead and should be avoided by marking the component with \`markRaw\` or using \`shallowRef\` instead of \`ref\`.`,
      `
Component that was made reactive: `,
      type
    );
  }
  return createBaseVNode(
    type,
    props,
    children,
    patchFlag,
    dynamicProps,
    shapeFlag,
    isBlockNode,
    true
  );
}
function guardReactiveProps(props) {
  if (!props) return null;
  return isProxy(props) || isInternalObject(props) ? extend$1({}, props) : props;
}
function cloneVNode(vnode, extraProps, mergeRef = false, cloneTransition = false) {
  const { props, ref: ref3, patchFlag, children, transition } = vnode;
  const mergedProps = extraProps ? mergeProps(props || {}, extraProps) : props;
  const cloned = {
    __v_isVNode: true,
    __v_skip: true,
    type: vnode.type,
    props: mergedProps,
    key: mergedProps && normalizeKey(mergedProps),
    ref: extraProps && extraProps.ref ? (
      // #2078 in the case of <component :is="vnode" ref="extra"/>
      // if the vnode itself already has a ref, cloneVNode will need to merge
      // the refs so the single vnode can be set on multiple refs
      mergeRef && ref3 ? isArray$1(ref3) ? ref3.concat(normalizeRef(extraProps)) : [ref3, normalizeRef(extraProps)] : normalizeRef(extraProps)
    ) : ref3,
    scopeId: vnode.scopeId,
    slotScopeIds: vnode.slotScopeIds,
    children: patchFlag === -1 && isArray$1(children) ? children.map(deepCloneVNode) : children,
    target: vnode.target,
    targetStart: vnode.targetStart,
    targetAnchor: vnode.targetAnchor,
    staticCount: vnode.staticCount,
    shapeFlag: vnode.shapeFlag,
    // if the vnode is cloned with extra props, we can no longer assume its
    // existing patch flag to be reliable and need to add the FULL_PROPS flag.
    // note: preserve flag for fragments since they use the flag for children
    // fast paths only.
    patchFlag: extraProps && vnode.type !== Fragment ? patchFlag === -1 ? 16 : patchFlag | 16 : patchFlag,
    dynamicProps: vnode.dynamicProps,
    dynamicChildren: vnode.dynamicChildren,
    appContext: vnode.appContext,
    dirs: vnode.dirs,
    transition,
    // These should technically only be non-null on mounted VNodes. However,
    // they *should* be copied for kept-alive vnodes. So we just always copy
    // them since them being non-null during a mount doesn't affect the logic as
    // they will simply be overwritten.
    component: vnode.component,
    suspense: vnode.suspense,
    ssContent: vnode.ssContent && cloneVNode(vnode.ssContent),
    ssFallback: vnode.ssFallback && cloneVNode(vnode.ssFallback),
    placeholder: vnode.placeholder,
    el: vnode.el,
    anchor: vnode.anchor,
    ctx: vnode.ctx,
    ce: vnode.ce
  };
  if (transition && cloneTransition) {
    setTransitionHooks(
      cloned,
      transition.clone(cloned)
    );
  }
  return cloned;
}
function deepCloneVNode(vnode) {
  const cloned = cloneVNode(vnode);
  if (isArray$1(vnode.children)) {
    cloned.children = vnode.children.map(deepCloneVNode);
  }
  return cloned;
}
function createTextVNode(text2 = " ", flag = 0) {
  return createVNode(Text, null, text2, flag);
}
function createCommentVNode(text2 = "", asBlock = false) {
  return asBlock ? (openBlock(), createBlock(Comment, null, text2)) : createVNode(Comment, null, text2);
}
function normalizeVNode(child) {
  if (child == null || typeof child === "boolean") {
    return createVNode(Comment);
  } else if (isArray$1(child)) {
    return createVNode(
      Fragment,
      null,
      // #3666, avoid reference pollution when reusing vnode
      child.slice()
    );
  } else if (isVNode(child)) {
    return cloneIfMounted(child);
  } else {
    return createVNode(Text, null, String(child));
  }
}
function cloneIfMounted(child) {
  return child.el === null && child.patchFlag !== -1 || child.memo ? child : cloneVNode(child);
}
function normalizeChildren(vnode, children) {
  let type = 0;
  const { shapeFlag } = vnode;
  if (children == null) {
    children = null;
  } else if (isArray$1(children)) {
    type = 16;
  } else if (typeof children === "object") {
    if (shapeFlag & (1 | 64)) {
      const slot = children.default;
      if (slot) {
        slot._c && (slot._d = false);
        normalizeChildren(vnode, slot());
        slot._c && (slot._d = true);
      }
      return;
    } else {
      type = 32;
      const slotFlag = children._;
      if (!slotFlag && !isInternalObject(children)) {
        children._ctx = currentRenderingInstance;
      } else if (slotFlag === 3 && currentRenderingInstance) {
        if (currentRenderingInstance.slots._ === 1) {
          children._ = 1;
        } else {
          children._ = 2;
          vnode.patchFlag |= 1024;
        }
      }
    }
  } else if (isFunction$2(children)) {
    children = { default: children, _ctx: currentRenderingInstance };
    type = 32;
  } else {
    children = String(children);
    if (shapeFlag & 64) {
      type = 16;
      children = [createTextVNode(children)];
    } else {
      type = 8;
    }
  }
  vnode.children = children;
  vnode.shapeFlag |= type;
}
function mergeProps(...args) {
  const ret = {};
  for (let i = 0; i < args.length; i++) {
    const toMerge = args[i];
    for (const key in toMerge) {
      if (key === "class") {
        if (ret.class !== toMerge.class) {
          ret.class = normalizeClass([ret.class, toMerge.class]);
        }
      } else if (key === "style") {
        ret.style = normalizeStyle([ret.style, toMerge.style]);
      } else if (isOn(key)) {
        const existing = ret[key];
        const incoming = toMerge[key];
        if (incoming && existing !== incoming && !(isArray$1(existing) && existing.includes(incoming))) {
          ret[key] = existing ? [].concat(existing, incoming) : incoming;
        }
      } else if (key !== "") {
        ret[key] = toMerge[key];
      }
    }
  }
  return ret;
}
function invokeVNodeHook(hook, instance, vnode, prevVNode = null) {
  callWithAsyncErrorHandling(hook, instance, 7, [
    vnode,
    prevVNode
  ]);
}
const emptyAppContext = createAppContext();
let uid = 0;
function createComponentInstance(vnode, parent, suspense) {
  const type = vnode.type;
  const appContext = (parent ? parent.appContext : vnode.appContext) || emptyAppContext;
  const instance = {
    uid: uid++,
    vnode,
    type,
    parent,
    appContext,
    root: null,
    // to be immediately set
    next: null,
    subTree: null,
    // will be set synchronously right after creation
    effect: null,
    update: null,
    // will be set synchronously right after creation
    job: null,
    scope: new EffectScope(
      true
      /* detached */
    ),
    render: null,
    proxy: null,
    exposed: null,
    exposeProxy: null,
    withProxy: null,
    provides: parent ? parent.provides : Object.create(appContext.provides),
    ids: parent ? parent.ids : ["", 0, 0],
    accessCache: null,
    renderCache: [],
    // local resolved assets
    components: null,
    directives: null,
    // resolved props and emits options
    propsOptions: normalizePropsOptions(type, appContext),
    emitsOptions: normalizeEmitsOptions(type, appContext),
    // emit
    emit: null,
    // to be set immediately
    emitted: null,
    // props default value
    propsDefaults: EMPTY_OBJ,
    // inheritAttrs
    inheritAttrs: type.inheritAttrs,
    // state
    ctx: EMPTY_OBJ,
    data: EMPTY_OBJ,
    props: EMPTY_OBJ,
    attrs: EMPTY_OBJ,
    slots: EMPTY_OBJ,
    refs: EMPTY_OBJ,
    setupState: EMPTY_OBJ,
    setupContext: null,
    // suspense related
    suspense,
    suspenseId: suspense ? suspense.pendingId : 0,
    asyncDep: null,
    asyncResolved: false,
    // lifecycle hooks
    // not using enums here because it results in computed properties
    isMounted: false,
    isUnmounted: false,
    isDeactivated: false,
    bc: null,
    c: null,
    bm: null,
    m: null,
    bu: null,
    u: null,
    um: null,
    bum: null,
    da: null,
    a: null,
    rtg: null,
    rtc: null,
    ec: null,
    sp: null
  };
  {
    instance.ctx = createDevRenderContext(instance);
  }
  instance.root = parent ? parent.root : instance;
  instance.emit = emit$2.bind(null, instance);
  if (vnode.ce) {
    vnode.ce(instance);
  }
  return instance;
}
let currentInstance = null;
const getCurrentInstance = () => currentInstance || currentRenderingInstance;
let internalSetCurrentInstance;
let setInSSRSetupState;
{
  const g = getGlobalThis();
  const registerGlobalSetter = (key, setter) => {
    let setters;
    if (!(setters = g[key])) setters = g[key] = [];
    setters.push(setter);
    return (v) => {
      if (setters.length > 1) setters.forEach((set) => set(v));
      else setters[0](v);
    };
  };
  internalSetCurrentInstance = registerGlobalSetter(
    `__VUE_INSTANCE_SETTERS__`,
    (v) => currentInstance = v
  );
  setInSSRSetupState = registerGlobalSetter(
    `__VUE_SSR_SETTERS__`,
    (v) => isInSSRComponentSetup = v
  );
}
const setCurrentInstance = (instance) => {
  const prev = currentInstance;
  internalSetCurrentInstance(instance);
  instance.scope.on();
  return () => {
    instance.scope.off();
    internalSetCurrentInstance(prev);
  };
};
const unsetCurrentInstance = () => {
  currentInstance && currentInstance.scope.off();
  internalSetCurrentInstance(null);
};
const isBuiltInTag = /* @__PURE__ */ makeMap("slot,component");
function validateComponentName(name, { isNativeTag }) {
  if (isBuiltInTag(name) || isNativeTag(name)) {
    warn$1(
      "Do not use built-in or reserved HTML elements as component id: " + name
    );
  }
}
function isStatefulComponent(instance) {
  return instance.vnode.shapeFlag & 4;
}
let isInSSRComponentSetup = false;
function setupComponent(instance, isSSR = false, optimized = false) {
  isSSR && setInSSRSetupState(isSSR);
  const { props, children } = instance.vnode;
  const isStateful = isStatefulComponent(instance);
  initProps(instance, props, isStateful, isSSR);
  initSlots(instance, children, optimized || isSSR);
  const setupResult = isStateful ? setupStatefulComponent(instance, isSSR) : void 0;
  isSSR && setInSSRSetupState(false);
  return setupResult;
}
function setupStatefulComponent(instance, isSSR) {
  const Component = instance.type;
  {
    if (Component.name) {
      validateComponentName(Component.name, instance.appContext.config);
    }
    if (Component.components) {
      const names = Object.keys(Component.components);
      for (let i = 0; i < names.length; i++) {
        validateComponentName(names[i], instance.appContext.config);
      }
    }
    if (Component.directives) {
      const names = Object.keys(Component.directives);
      for (let i = 0; i < names.length; i++) {
        validateDirectiveName(names[i]);
      }
    }
    if (Component.compilerOptions && isRuntimeOnly()) {
      warn$1(
        `"compilerOptions" is only supported when using a build of Vue that includes the runtime compiler. Since you are using a runtime-only build, the options should be passed via your build tool config instead.`
      );
    }
  }
  instance.accessCache = /* @__PURE__ */ Object.create(null);
  instance.proxy = new Proxy(instance.ctx, PublicInstanceProxyHandlers);
  {
    exposePropsOnRenderContext(instance);
  }
  const { setup } = Component;
  if (setup) {
    pauseTracking();
    const setupContext = instance.setupContext = setup.length > 1 ? createSetupContext(instance) : null;
    const reset = setCurrentInstance(instance);
    const setupResult = callWithErrorHandling(
      setup,
      instance,
      0,
      [
        shallowReadonly(instance.props),
        setupContext
      ]
    );
    const isAsyncSetup = isPromise(setupResult);
    resetTracking();
    reset();
    if ((isAsyncSetup || instance.sp) && !isAsyncWrapper(instance)) {
      markAsyncBoundary(instance);
    }
    if (isAsyncSetup) {
      setupResult.then(unsetCurrentInstance, unsetCurrentInstance);
      if (isSSR) {
        return setupResult.then((resolvedResult) => {
          handleSetupResult(instance, resolvedResult, isSSR);
        }).catch((e) => {
          handleError(e, instance, 0);
        });
      } else {
        instance.asyncDep = setupResult;
        if (!instance.suspense) {
          const name = formatComponentName(instance, Component);
          warn$1(
            `Component <${name}>: setup function returned a promise, but no <Suspense> boundary was found in the parent component tree. A component with async setup() must be nested in a <Suspense> in order to be rendered.`
          );
        }
      }
    } else {
      handleSetupResult(instance, setupResult, isSSR);
    }
  } else {
    finishComponentSetup(instance, isSSR);
  }
}
function handleSetupResult(instance, setupResult, isSSR) {
  if (isFunction$2(setupResult)) {
    if (instance.type.__ssrInlineRender) {
      instance.ssrRender = setupResult;
    } else {
      instance.render = setupResult;
    }
  } else if (isObject$1(setupResult)) {
    if (isVNode(setupResult)) {
      warn$1(
        `setup() should not return VNodes directly - return a render function instead.`
      );
    }
    {
      instance.devtoolsRawSetupState = setupResult;
    }
    instance.setupState = proxyRefs(setupResult);
    {
      exposeSetupStateOnRenderContext(instance);
    }
  } else if (setupResult !== void 0) {
    warn$1(
      `setup() should return an object. Received: ${setupResult === null ? "null" : typeof setupResult}`
    );
  }
  finishComponentSetup(instance, isSSR);
}
const isRuntimeOnly = () => true;
function finishComponentSetup(instance, isSSR, skipOptions) {
  const Component = instance.type;
  if (!instance.render) {
    instance.render = Component.render || NOOP;
  }
  {
    const reset = setCurrentInstance(instance);
    pauseTracking();
    try {
      applyOptions(instance);
    } finally {
      resetTracking();
      reset();
    }
  }
  if (!Component.render && instance.render === NOOP && !isSSR) {
    if (Component.template) {
      warn$1(
        `Component provided template option but runtime compilation is not supported in this build of Vue. Configure your bundler to alias "vue" to "vue/dist/vue.esm-bundler.js".`
      );
    } else {
      warn$1(`Component is missing template or render function: `, Component);
    }
  }
}
const attrsProxyHandlers = {
  get(target, key) {
    markAttrsAccessed();
    track(target, "get", "");
    return target[key];
  },
  set() {
    warn$1(`setupContext.attrs is readonly.`);
    return false;
  },
  deleteProperty() {
    warn$1(`setupContext.attrs is readonly.`);
    return false;
  }
};
function getSlotsProxy(instance) {
  return new Proxy(instance.slots, {
    get(target, key) {
      track(instance, "get", "$slots");
      return target[key];
    }
  });
}
function createSetupContext(instance) {
  const expose = (exposed) => {
    {
      if (instance.exposed) {
        warn$1(`expose() should be called only once per setup().`);
      }
      if (exposed != null) {
        let exposedType = typeof exposed;
        if (exposedType === "object") {
          if (isArray$1(exposed)) {
            exposedType = "array";
          } else if (isRef(exposed)) {
            exposedType = "ref";
          }
        }
        if (exposedType !== "object") {
          warn$1(
            `expose() should be passed a plain object, received ${exposedType}.`
          );
        }
      }
    }
    instance.exposed = exposed || {};
  };
  {
    let attrsProxy;
    let slotsProxy;
    return Object.freeze({
      get attrs() {
        return attrsProxy || (attrsProxy = new Proxy(instance.attrs, attrsProxyHandlers));
      },
      get slots() {
        return slotsProxy || (slotsProxy = getSlotsProxy(instance));
      },
      get emit() {
        return (event, ...args) => instance.emit(event, ...args);
      },
      expose
    });
  }
}
function getComponentPublicInstance(instance) {
  if (instance.exposed) {
    return instance.exposeProxy || (instance.exposeProxy = new Proxy(proxyRefs(markRaw(instance.exposed)), {
      get(target, key) {
        if (key in target) {
          return target[key];
        } else if (key in publicPropertiesMap) {
          return publicPropertiesMap[key](instance);
        }
      },
      has(target, key) {
        return key in target || key in publicPropertiesMap;
      }
    }));
  } else {
    return instance.proxy;
  }
}
const classifyRE = /(?:^|[-_])\w/g;
const classify = (str) => str.replace(classifyRE, (c) => c.toUpperCase()).replace(/[-_]/g, "");
function getComponentName(Component, includeInferred = true) {
  return isFunction$2(Component) ? Component.displayName || Component.name : Component.name || includeInferred && Component.__name;
}
function formatComponentName(instance, Component, isRoot = false) {
  let name = getComponentName(Component);
  if (!name && Component.__file) {
    const match = Component.__file.match(/([^/\\]+)\.\w+$/);
    if (match) {
      name = match[1];
    }
  }
  if (!name && instance) {
    const inferFromRegistry = (registry) => {
      for (const key in registry) {
        if (registry[key] === Component) {
          return key;
        }
      }
    };
    name = inferFromRegistry(instance.components) || instance.parent && inferFromRegistry(
      instance.parent.type.components
    ) || inferFromRegistry(instance.appContext.components);
  }
  return name ? classify(name) : isRoot ? `App` : `Anonymous`;
}
function isClassComponent(value) {
  return isFunction$2(value) && "__vccOpts" in value;
}
const computed = (getterOrOptions, debugOptions) => {
  const c = computed$1(getterOrOptions, debugOptions, isInSSRComponentSetup);
  {
    const i = getCurrentInstance();
    if (i && i.appContext.config.warnRecursiveComputed) {
      c._warnRecursive = true;
    }
  }
  return c;
};
function h(type, propsOrChildren, children) {
  try {
    setBlockTracking(-1);
    const l = arguments.length;
    if (l === 2) {
      if (isObject$1(propsOrChildren) && !isArray$1(propsOrChildren)) {
        if (isVNode(propsOrChildren)) {
          return createVNode(type, null, [propsOrChildren]);
        }
        return createVNode(type, propsOrChildren);
      } else {
        return createVNode(type, null, propsOrChildren);
      }
    } else {
      if (l > 3) {
        children = Array.prototype.slice.call(arguments, 2);
      } else if (l === 3 && isVNode(children)) {
        children = [children];
      }
      return createVNode(type, propsOrChildren, children);
    }
  } finally {
    setBlockTracking(1);
  }
}
function initCustomFormatter() {
  if (typeof window === "undefined") {
    return;
  }
  const vueStyle = { style: "color:#3ba776" };
  const numberStyle = { style: "color:#1677ff" };
  const stringStyle = { style: "color:#f5222d" };
  const keywordStyle = { style: "color:#eb2f96" };
  const formatter = {
    __vue_custom_formatter: true,
    header(obj) {
      if (!isObject$1(obj)) {
        return null;
      }
      if (obj.__isVue) {
        return ["div", vueStyle, `VueInstance`];
      } else if (isRef(obj)) {
        pauseTracking();
        const value = obj.value;
        resetTracking();
        return [
          "div",
          {},
          ["span", vueStyle, genRefFlag(obj)],
          "<",
          formatValue(value),
          `>`
        ];
      } else if (isReactive(obj)) {
        return [
          "div",
          {},
          ["span", vueStyle, isShallow(obj) ? "ShallowReactive" : "Reactive"],
          "<",
          formatValue(obj),
          `>${isReadonly(obj) ? ` (readonly)` : ``}`
        ];
      } else if (isReadonly(obj)) {
        return [
          "div",
          {},
          ["span", vueStyle, isShallow(obj) ? "ShallowReadonly" : "Readonly"],
          "<",
          formatValue(obj),
          ">"
        ];
      }
      return null;
    },
    hasBody(obj) {
      return obj && obj.__isVue;
    },
    body(obj) {
      if (obj && obj.__isVue) {
        return [
          "div",
          {},
          ...formatInstance(obj.$)
        ];
      }
    }
  };
  function formatInstance(instance) {
    const blocks = [];
    if (instance.type.props && instance.props) {
      blocks.push(createInstanceBlock("props", toRaw(instance.props)));
    }
    if (instance.setupState !== EMPTY_OBJ) {
      blocks.push(createInstanceBlock("setup", instance.setupState));
    }
    if (instance.data !== EMPTY_OBJ) {
      blocks.push(createInstanceBlock("data", toRaw(instance.data)));
    }
    const computed2 = extractKeys(instance, "computed");
    if (computed2) {
      blocks.push(createInstanceBlock("computed", computed2));
    }
    const injected = extractKeys(instance, "inject");
    if (injected) {
      blocks.push(createInstanceBlock("injected", injected));
    }
    blocks.push([
      "div",
      {},
      [
        "span",
        {
          style: keywordStyle.style + ";opacity:0.66"
        },
        "$ (internal): "
      ],
      ["object", { object: instance }]
    ]);
    return blocks;
  }
  function createInstanceBlock(type, target) {
    target = extend$1({}, target);
    if (!Object.keys(target).length) {
      return ["span", {}];
    }
    return [
      "div",
      { style: "line-height:1.25em;margin-bottom:0.6em" },
      [
        "div",
        {
          style: "color:#476582"
        },
        type
      ],
      [
        "div",
        {
          style: "padding-left:1.25em"
        },
        ...Object.keys(target).map((key) => {
          return [
            "div",
            {},
            ["span", keywordStyle, key + ": "],
            formatValue(target[key], false)
          ];
        })
      ]
    ];
  }
  function formatValue(v, asRaw = true) {
    if (typeof v === "number") {
      return ["span", numberStyle, v];
    } else if (typeof v === "string") {
      return ["span", stringStyle, JSON.stringify(v)];
    } else if (typeof v === "boolean") {
      return ["span", keywordStyle, v];
    } else if (isObject$1(v)) {
      return ["object", { object: asRaw ? toRaw(v) : v }];
    } else {
      return ["span", stringStyle, String(v)];
    }
  }
  function extractKeys(instance, type) {
    const Comp = instance.type;
    if (isFunction$2(Comp)) {
      return;
    }
    const extracted = {};
    for (const key in instance.ctx) {
      if (isKeyOfType(Comp, key, type)) {
        extracted[key] = instance.ctx[key];
      }
    }
    return extracted;
  }
  function isKeyOfType(Comp, key, type) {
    const opts = Comp[type];
    if (isArray$1(opts) && opts.includes(key) || isObject$1(opts) && key in opts) {
      return true;
    }
    if (Comp.extends && isKeyOfType(Comp.extends, key, type)) {
      return true;
    }
    if (Comp.mixins && Comp.mixins.some((m) => isKeyOfType(m, key, type))) {
      return true;
    }
  }
  function genRefFlag(v) {
    if (isShallow(v)) {
      return `ShallowRef`;
    }
    if (v.effect) {
      return `ComputedRef`;
    }
    return `Ref`;
  }
  if (window.devtoolsFormatters) {
    window.devtoolsFormatters.push(formatter);
  } else {
    window.devtoolsFormatters = [formatter];
  }
}
const version = "3.5.25";
const warn = warn$1;
/**
* @vue/runtime-dom v3.5.25
* (c) 2018-present Yuxi (Evan) You and Vue contributors
* @license MIT
**/
let policy = void 0;
const tt = typeof window !== "undefined" && window.trustedTypes;
if (tt) {
  try {
    policy = /* @__PURE__ */ tt.createPolicy("vue", {
      createHTML: (val) => val
    });
  } catch (e) {
    warn(`Error creating trusted types policy: ${e}`);
  }
}
const unsafeToTrustedHTML = policy ? (val) => policy.createHTML(val) : (val) => val;
const svgNS = "http://www.w3.org/2000/svg";
const mathmlNS = "http://www.w3.org/1998/Math/MathML";
const doc = typeof document !== "undefined" ? document : null;
const templateContainer = doc && /* @__PURE__ */ doc.createElement("template");
const nodeOps = {
  insert: (child, parent, anchor) => {
    parent.insertBefore(child, anchor || null);
  },
  remove: (child) => {
    const parent = child.parentNode;
    if (parent) {
      parent.removeChild(child);
    }
  },
  createElement: (tag, namespace, is, props) => {
    const el = namespace === "svg" ? doc.createElementNS(svgNS, tag) : namespace === "mathml" ? doc.createElementNS(mathmlNS, tag) : is ? doc.createElement(tag, { is }) : doc.createElement(tag);
    if (tag === "select" && props && props.multiple != null) {
      el.setAttribute("multiple", props.multiple);
    }
    return el;
  },
  createText: (text2) => doc.createTextNode(text2),
  createComment: (text2) => doc.createComment(text2),
  setText: (node, text2) => {
    node.nodeValue = text2;
  },
  setElementText: (el, text2) => {
    el.textContent = text2;
  },
  parentNode: (node) => node.parentNode,
  nextSibling: (node) => node.nextSibling,
  querySelector: (selector) => doc.querySelector(selector),
  setScopeId(el, id) {
    el.setAttribute(id, "");
  },
  // __UNSAFE__
  // Reason: innerHTML.
  // Static content here can only come from compiled templates.
  // As long as the user only uses trusted templates, this is safe.
  insertStaticContent(content, parent, anchor, namespace, start, end) {
    const before = anchor ? anchor.previousSibling : parent.lastChild;
    if (start && (start === end || start.nextSibling)) {
      while (true) {
        parent.insertBefore(start.cloneNode(true), anchor);
        if (start === end || !(start = start.nextSibling)) break;
      }
    } else {
      templateContainer.innerHTML = unsafeToTrustedHTML(
        namespace === "svg" ? `<svg>${content}</svg>` : namespace === "mathml" ? `<math>${content}</math>` : content
      );
      const template = templateContainer.content;
      if (namespace === "svg" || namespace === "mathml") {
        const wrapper = template.firstChild;
        while (wrapper.firstChild) {
          template.appendChild(wrapper.firstChild);
        }
        template.removeChild(wrapper);
      }
      parent.insertBefore(template, anchor);
    }
    return [
      // first
      before ? before.nextSibling : parent.firstChild,
      // last
      anchor ? anchor.previousSibling : parent.lastChild
    ];
  }
};
const TRANSITION = "transition";
const ANIMATION = "animation";
const vtcKey = Symbol("_vtc");
const DOMTransitionPropsValidators = {
  name: String,
  type: String,
  css: {
    type: Boolean,
    default: true
  },
  duration: [String, Number, Object],
  enterFromClass: String,
  enterActiveClass: String,
  enterToClass: String,
  appearFromClass: String,
  appearActiveClass: String,
  appearToClass: String,
  leaveFromClass: String,
  leaveActiveClass: String,
  leaveToClass: String
};
const TransitionPropsValidators = /* @__PURE__ */ extend$1(
  {},
  BaseTransitionPropsValidators,
  DOMTransitionPropsValidators
);
const decorate$1 = (t7) => {
  t7.displayName = "Transition";
  t7.props = TransitionPropsValidators;
  return t7;
};
const Transition = /* @__PURE__ */ decorate$1(
  (props, { slots }) => h(BaseTransition, resolveTransitionProps(props), slots)
);
const callHook = (hook, args = []) => {
  if (isArray$1(hook)) {
    hook.forEach((h2) => h2(...args));
  } else if (hook) {
    hook(...args);
  }
};
const hasExplicitCallback = (hook) => {
  return hook ? isArray$1(hook) ? hook.some((h2) => h2.length > 1) : hook.length > 1 : false;
};
function resolveTransitionProps(rawProps) {
  const baseProps = {};
  for (const key in rawProps) {
    if (!(key in DOMTransitionPropsValidators)) {
      baseProps[key] = rawProps[key];
    }
  }
  if (rawProps.css === false) {
    return baseProps;
  }
  const {
    name = "v",
    type,
    duration,
    enterFromClass = `${name}-enter-from`,
    enterActiveClass = `${name}-enter-active`,
    enterToClass = `${name}-enter-to`,
    appearFromClass = enterFromClass,
    appearActiveClass = enterActiveClass,
    appearToClass = enterToClass,
    leaveFromClass = `${name}-leave-from`,
    leaveActiveClass = `${name}-leave-active`,
    leaveToClass = `${name}-leave-to`
  } = rawProps;
  const durations = normalizeDuration(duration);
  const enterDuration = durations && durations[0];
  const leaveDuration = durations && durations[1];
  const {
    onBeforeEnter,
    onEnter,
    onEnterCancelled,
    onLeave,
    onLeaveCancelled,
    onBeforeAppear = onBeforeEnter,
    onAppear = onEnter,
    onAppearCancelled = onEnterCancelled
  } = baseProps;
  const finishEnter = (el, isAppear, done, isCancelled) => {
    el._enterCancelled = isCancelled;
    removeTransitionClass(el, isAppear ? appearToClass : enterToClass);
    removeTransitionClass(el, isAppear ? appearActiveClass : enterActiveClass);
    done && done();
  };
  const finishLeave = (el, done) => {
    el._isLeaving = false;
    removeTransitionClass(el, leaveFromClass);
    removeTransitionClass(el, leaveToClass);
    removeTransitionClass(el, leaveActiveClass);
    done && done();
  };
  const makeEnterHook = (isAppear) => {
    return (el, done) => {
      const hook = isAppear ? onAppear : onEnter;
      const resolve2 = () => finishEnter(el, isAppear, done);
      callHook(hook, [el, resolve2]);
      nextFrame(() => {
        removeTransitionClass(el, isAppear ? appearFromClass : enterFromClass);
        addTransitionClass(el, isAppear ? appearToClass : enterToClass);
        if (!hasExplicitCallback(hook)) {
          whenTransitionEnds(el, type, enterDuration, resolve2);
        }
      });
    };
  };
  return extend$1(baseProps, {
    onBeforeEnter(el) {
      callHook(onBeforeEnter, [el]);
      addTransitionClass(el, enterFromClass);
      addTransitionClass(el, enterActiveClass);
    },
    onBeforeAppear(el) {
      callHook(onBeforeAppear, [el]);
      addTransitionClass(el, appearFromClass);
      addTransitionClass(el, appearActiveClass);
    },
    onEnter: makeEnterHook(false),
    onAppear: makeEnterHook(true),
    onLeave(el, done) {
      el._isLeaving = true;
      const resolve2 = () => finishLeave(el, done);
      addTransitionClass(el, leaveFromClass);
      if (!el._enterCancelled) {
        forceReflow(el);
        addTransitionClass(el, leaveActiveClass);
      } else {
        addTransitionClass(el, leaveActiveClass);
        forceReflow(el);
      }
      nextFrame(() => {
        if (!el._isLeaving) {
          return;
        }
        removeTransitionClass(el, leaveFromClass);
        addTransitionClass(el, leaveToClass);
        if (!hasExplicitCallback(onLeave)) {
          whenTransitionEnds(el, type, leaveDuration, resolve2);
        }
      });
      callHook(onLeave, [el, resolve2]);
    },
    onEnterCancelled(el) {
      finishEnter(el, false, void 0, true);
      callHook(onEnterCancelled, [el]);
    },
    onAppearCancelled(el) {
      finishEnter(el, true, void 0, true);
      callHook(onAppearCancelled, [el]);
    },
    onLeaveCancelled(el) {
      finishLeave(el);
      callHook(onLeaveCancelled, [el]);
    }
  });
}
function normalizeDuration(duration) {
  if (duration == null) {
    return null;
  } else if (isObject$1(duration)) {
    return [NumberOf(duration.enter), NumberOf(duration.leave)];
  } else {
    const n2 = NumberOf(duration);
    return [n2, n2];
  }
}
function NumberOf(val) {
  const res = toNumber(val);
  {
    assertNumber(res, "<transition> explicit duration");
  }
  return res;
}
function addTransitionClass(el, cls) {
  cls.split(/\s+/).forEach((c) => c && el.classList.add(c));
  (el[vtcKey] || (el[vtcKey] = /* @__PURE__ */ new Set())).add(cls);
}
function removeTransitionClass(el, cls) {
  cls.split(/\s+/).forEach((c) => c && el.classList.remove(c));
  const _vtc = el[vtcKey];
  if (_vtc) {
    _vtc.delete(cls);
    if (!_vtc.size) {
      el[vtcKey] = void 0;
    }
  }
}
function nextFrame(cb) {
  requestAnimationFrame(() => {
    requestAnimationFrame(cb);
  });
}
let endId = 0;
function whenTransitionEnds(el, expectedType, explicitTimeout, resolve2) {
  const id = el._endId = ++endId;
  const resolveIfNotStale = () => {
    if (id === el._endId) {
      resolve2();
    }
  };
  if (explicitTimeout != null) {
    return setTimeout(resolveIfNotStale, explicitTimeout);
  }
  const { type, timeout, propCount } = getTransitionInfo(el, expectedType);
  if (!type) {
    return resolve2();
  }
  const endEvent = type + "end";
  let ended = 0;
  const end = () => {
    el.removeEventListener(endEvent, onEnd);
    resolveIfNotStale();
  };
  const onEnd = (e) => {
    if (e.target === el && ++ended >= propCount) {
      end();
    }
  };
  setTimeout(() => {
    if (ended < propCount) {
      end();
    }
  }, timeout + 1);
  el.addEventListener(endEvent, onEnd);
}
function getTransitionInfo(el, expectedType) {
  const styles = window.getComputedStyle(el);
  const getStyleProperties = (key) => (styles[key] || "").split(", ");
  const transitionDelays = getStyleProperties(`${TRANSITION}Delay`);
  const transitionDurations = getStyleProperties(`${TRANSITION}Duration`);
  const transitionTimeout = getTimeout(transitionDelays, transitionDurations);
  const animationDelays = getStyleProperties(`${ANIMATION}Delay`);
  const animationDurations = getStyleProperties(`${ANIMATION}Duration`);
  const animationTimeout = getTimeout(animationDelays, animationDurations);
  let type = null;
  let timeout = 0;
  let propCount = 0;
  if (expectedType === TRANSITION) {
    if (transitionTimeout > 0) {
      type = TRANSITION;
      timeout = transitionTimeout;
      propCount = transitionDurations.length;
    }
  } else if (expectedType === ANIMATION) {
    if (animationTimeout > 0) {
      type = ANIMATION;
      timeout = animationTimeout;
      propCount = animationDurations.length;
    }
  } else {
    timeout = Math.max(transitionTimeout, animationTimeout);
    type = timeout > 0 ? transitionTimeout > animationTimeout ? TRANSITION : ANIMATION : null;
    propCount = type ? type === TRANSITION ? transitionDurations.length : animationDurations.length : 0;
  }
  const hasTransform = type === TRANSITION && /\b(?:transform|all)(?:,|$)/.test(
    getStyleProperties(`${TRANSITION}Property`).toString()
  );
  return {
    type,
    timeout,
    propCount,
    hasTransform
  };
}
function getTimeout(delays, durations) {
  while (delays.length < durations.length) {
    delays = delays.concat(delays);
  }
  return Math.max(...durations.map((d, i) => toMs(d) + toMs(delays[i])));
}
function toMs(s) {
  if (s === "auto") return 0;
  return Number(s.slice(0, -1).replace(",", ".")) * 1e3;
}
function forceReflow(el) {
  const targetDocument = el ? el.ownerDocument : document;
  return targetDocument.body.offsetHeight;
}
function patchClass(el, value, isSVG) {
  const transitionClasses = el[vtcKey];
  if (transitionClasses) {
    value = (value ? [value, ...transitionClasses] : [...transitionClasses]).join(" ");
  }
  if (value == null) {
    el.removeAttribute("class");
  } else if (isSVG) {
    el.setAttribute("class", value);
  } else {
    el.className = value;
  }
}
const vShowOriginalDisplay = Symbol("_vod");
const vShowHidden = Symbol("_vsh");
const vShow = {
  // used for prop mismatch check during hydration
  name: "show",
  beforeMount(el, { value }, { transition }) {
    el[vShowOriginalDisplay] = el.style.display === "none" ? "" : el.style.display;
    if (transition && value) {
      transition.beforeEnter(el);
    } else {
      setDisplay(el, value);
    }
  },
  mounted(el, { value }, { transition }) {
    if (transition && value) {
      transition.enter(el);
    }
  },
  updated(el, { value, oldValue }, { transition }) {
    if (!value === !oldValue) return;
    if (transition) {
      if (value) {
        transition.beforeEnter(el);
        setDisplay(el, true);
        transition.enter(el);
      } else {
        transition.leave(el, () => {
          setDisplay(el, false);
        });
      }
    } else {
      setDisplay(el, value);
    }
  },
  beforeUnmount(el, { value }) {
    setDisplay(el, value);
  }
};
function setDisplay(el, value) {
  el.style.display = value ? el[vShowOriginalDisplay] : "none";
  el[vShowHidden] = !value;
}
const CSS_VAR_TEXT = Symbol("CSS_VAR_TEXT");
function useCssVars(getter) {
  const instance = getCurrentInstance();
  if (!instance) {
    warn(`useCssVars is called without current active component instance.`);
    return;
  }
  const updateTeleports = instance.ut = (vars = getter(instance.proxy)) => {
    Array.from(
      document.querySelectorAll(`[data-v-owner="${instance.uid}"]`)
    ).forEach((node) => setVarsOnNode(node, vars));
  };
  {
    instance.getCssVars = () => getter(instance.proxy);
  }
  const setVars = () => {
    const vars = getter(instance.proxy);
    if (instance.ce) {
      setVarsOnNode(instance.ce, vars);
    } else {
      setVarsOnVNode(instance.subTree, vars);
    }
    updateTeleports(vars);
  };
  onBeforeUpdate(() => {
    queuePostFlushCb(setVars);
  });
  onMounted(() => {
    watch(setVars, NOOP, { flush: "post" });
    const ob = new MutationObserver(setVars);
    ob.observe(instance.subTree.el.parentNode, { childList: true });
    onUnmounted(() => ob.disconnect());
  });
}
function setVarsOnVNode(vnode, vars) {
  if (vnode.shapeFlag & 128) {
    const suspense = vnode.suspense;
    vnode = suspense.activeBranch;
    if (suspense.pendingBranch && !suspense.isHydrating) {
      suspense.effects.push(() => {
        setVarsOnVNode(suspense.activeBranch, vars);
      });
    }
  }
  while (vnode.component) {
    vnode = vnode.component.subTree;
  }
  if (vnode.shapeFlag & 1 && vnode.el) {
    setVarsOnNode(vnode.el, vars);
  } else if (vnode.type === Fragment) {
    vnode.children.forEach((c) => setVarsOnVNode(c, vars));
  } else if (vnode.type === Static) {
    let { el, anchor } = vnode;
    while (el) {
      setVarsOnNode(el, vars);
      if (el === anchor) break;
      el = el.nextSibling;
    }
  }
}
function setVarsOnNode(el, vars) {
  if (el.nodeType === 1) {
    const style = el.style;
    let cssText = "";
    for (const key in vars) {
      const value = normalizeCssVarValue(vars[key]);
      style.setProperty(`--${key}`, value);
      cssText += `--${key}: ${value};`;
    }
    style[CSS_VAR_TEXT] = cssText;
  }
}
const displayRE = /(?:^|;)\s*display\s*:/;
function patchStyle(el, prev, next) {
  const style = el.style;
  const isCssString = isString$1(next);
  let hasControlledDisplay = false;
  if (next && !isCssString) {
    if (prev) {
      if (!isString$1(prev)) {
        for (const key in prev) {
          if (next[key] == null) {
            setStyle(style, key, "");
          }
        }
      } else {
        for (const prevStyle of prev.split(";")) {
          const key = prevStyle.slice(0, prevStyle.indexOf(":")).trim();
          if (next[key] == null) {
            setStyle(style, key, "");
          }
        }
      }
    }
    for (const key in next) {
      if (key === "display") {
        hasControlledDisplay = true;
      }
      setStyle(style, key, next[key]);
    }
  } else {
    if (isCssString) {
      if (prev !== next) {
        const cssVarText = style[CSS_VAR_TEXT];
        if (cssVarText) {
          next += ";" + cssVarText;
        }
        style.cssText = next;
        hasControlledDisplay = displayRE.test(next);
      }
    } else if (prev) {
      el.removeAttribute("style");
    }
  }
  if (vShowOriginalDisplay in el) {
    el[vShowOriginalDisplay] = hasControlledDisplay ? style.display : "";
    if (el[vShowHidden]) {
      style.display = "none";
    }
  }
}
const semicolonRE = /[^\\];\s*$/;
const importantRE = /\s*!important$/;
function setStyle(style, name, val) {
  if (isArray$1(val)) {
    val.forEach((v) => setStyle(style, name, v));
  } else {
    if (val == null) val = "";
    {
      if (semicolonRE.test(val)) {
        warn(
          `Unexpected semicolon at the end of '${name}' style value: '${val}'`
        );
      }
    }
    if (name.startsWith("--")) {
      style.setProperty(name, val);
    } else {
      const prefixed = autoPrefix(style, name);
      if (importantRE.test(val)) {
        style.setProperty(
          hyphenate(prefixed),
          val.replace(importantRE, ""),
          "important"
        );
      } else {
        style[prefixed] = val;
      }
    }
  }
}
const prefixes = ["Webkit", "Moz", "ms"];
const prefixCache = {};
function autoPrefix(style, rawName) {
  const cached = prefixCache[rawName];
  if (cached) {
    return cached;
  }
  let name = camelize(rawName);
  if (name !== "filter" && name in style) {
    return prefixCache[rawName] = name;
  }
  name = capitalize(name);
  for (let i = 0; i < prefixes.length; i++) {
    const prefixed = prefixes[i] + name;
    if (prefixed in style) {
      return prefixCache[rawName] = prefixed;
    }
  }
  return rawName;
}
const xlinkNS = "http://www.w3.org/1999/xlink";
function patchAttr(el, key, value, isSVG, instance, isBoolean2 = isSpecialBooleanAttr(key)) {
  if (isSVG && key.startsWith("xlink:")) {
    if (value == null) {
      el.removeAttributeNS(xlinkNS, key.slice(6, key.length));
    } else {
      el.setAttributeNS(xlinkNS, key, value);
    }
  } else {
    if (value == null || isBoolean2 && !includeBooleanAttr(value)) {
      el.removeAttribute(key);
    } else {
      el.setAttribute(
        key,
        isBoolean2 ? "" : isSymbol(value) ? String(value) : value
      );
    }
  }
}
function patchDOMProp(el, key, value, parentComponent, attrName) {
  if (key === "innerHTML" || key === "textContent") {
    if (value != null) {
      el[key] = key === "innerHTML" ? unsafeToTrustedHTML(value) : value;
    }
    return;
  }
  const tag = el.tagName;
  if (key === "value" && tag !== "PROGRESS" && // custom elements may use _value internally
  !tag.includes("-")) {
    const oldValue = tag === "OPTION" ? el.getAttribute("value") || "" : el.value;
    const newValue = value == null ? (
      // #11647: value should be set as empty string for null and undefined,
      // but <input type="checkbox"> should be set as 'on'.
      el.type === "checkbox" ? "on" : ""
    ) : String(value);
    if (oldValue !== newValue || !("_value" in el)) {
      el.value = newValue;
    }
    if (value == null) {
      el.removeAttribute(key);
    }
    el._value = value;
    return;
  }
  let needRemove = false;
  if (value === "" || value == null) {
    const type = typeof el[key];
    if (type === "boolean") {
      value = includeBooleanAttr(value);
    } else if (value == null && type === "string") {
      value = "";
      needRemove = true;
    } else if (type === "number") {
      value = 0;
      needRemove = true;
    }
  }
  try {
    el[key] = value;
  } catch (e) {
    if (!needRemove) {
      warn(
        `Failed setting prop "${key}" on <${tag.toLowerCase()}>: value ${value} is invalid.`,
        e
      );
    }
  }
  needRemove && el.removeAttribute(attrName || key);
}
function addEventListener(el, event, handler, options) {
  el.addEventListener(event, handler, options);
}
function removeEventListener(el, event, handler, options) {
  el.removeEventListener(event, handler, options);
}
const veiKey = Symbol("_vei");
function patchEvent(el, rawName, prevValue, nextValue, instance = null) {
  const invokers = el[veiKey] || (el[veiKey] = {});
  const existingInvoker = invokers[rawName];
  if (nextValue && existingInvoker) {
    existingInvoker.value = sanitizeEventValue(nextValue, rawName);
  } else {
    const [name, options] = parseName(rawName);
    if (nextValue) {
      const invoker = invokers[rawName] = createInvoker(
        sanitizeEventValue(nextValue, rawName),
        instance
      );
      addEventListener(el, name, invoker, options);
    } else if (existingInvoker) {
      removeEventListener(el, name, existingInvoker, options);
      invokers[rawName] = void 0;
    }
  }
}
const optionsModifierRE = /(?:Once|Passive|Capture)$/;
function parseName(name) {
  let options;
  if (optionsModifierRE.test(name)) {
    options = {};
    let m;
    while (m = name.match(optionsModifierRE)) {
      name = name.slice(0, name.length - m[0].length);
      options[m[0].toLowerCase()] = true;
    }
  }
  const event = name[2] === ":" ? name.slice(3) : hyphenate(name.slice(2));
  return [event, options];
}
let cachedNow = 0;
const p = /* @__PURE__ */ Promise.resolve();
const getNow = () => cachedNow || (p.then(() => cachedNow = 0), cachedNow = Date.now());
function createInvoker(initialValue, instance) {
  const invoker = (e) => {
    if (!e._vts) {
      e._vts = Date.now();
    } else if (e._vts <= invoker.attached) {
      return;
    }
    callWithAsyncErrorHandling(
      patchStopImmediatePropagation(e, invoker.value),
      instance,
      5,
      [e]
    );
  };
  invoker.value = initialValue;
  invoker.attached = getNow();
  return invoker;
}
function sanitizeEventValue(value, propName) {
  if (isFunction$2(value) || isArray$1(value)) {
    return value;
  }
  warn(
    `Wrong type passed as event handler to ${propName} - did you forget @ or : in front of your prop?
Expected function or array of functions, received type ${typeof value}.`
  );
  return NOOP;
}
function patchStopImmediatePropagation(e, value) {
  if (isArray$1(value)) {
    const originalStop = e.stopImmediatePropagation;
    e.stopImmediatePropagation = () => {
      originalStop.call(e);
      e._stopped = true;
    };
    return value.map(
      (fn) => (e2) => !e2._stopped && fn && fn(e2)
    );
  } else {
    return value;
  }
}
const isNativeOn = (key) => key.charCodeAt(0) === 111 && key.charCodeAt(1) === 110 && // lowercase letter
key.charCodeAt(2) > 96 && key.charCodeAt(2) < 123;
const patchProp = (el, key, prevValue, nextValue, namespace, parentComponent) => {
  const isSVG = namespace === "svg";
  if (key === "class") {
    patchClass(el, nextValue, isSVG);
  } else if (key === "style") {
    patchStyle(el, prevValue, nextValue);
  } else if (isOn(key)) {
    if (!isModelListener(key)) {
      patchEvent(el, key, prevValue, nextValue, parentComponent);
    }
  } else if (key[0] === "." ? (key = key.slice(1), true) : key[0] === "^" ? (key = key.slice(1), false) : shouldSetAsProp(el, key, nextValue, isSVG)) {
    patchDOMProp(el, key, nextValue);
    if (!el.tagName.includes("-") && (key === "value" || key === "checked" || key === "selected")) {
      patchAttr(el, key, nextValue, isSVG, parentComponent, key !== "value");
    }
  } else if (
    // #11081 force set props for possible async custom element
    el._isVueCE && (/[A-Z]/.test(key) || !isString$1(nextValue))
  ) {
    patchDOMProp(el, camelize(key), nextValue, parentComponent, key);
  } else {
    if (key === "true-value") {
      el._trueValue = nextValue;
    } else if (key === "false-value") {
      el._falseValue = nextValue;
    }
    patchAttr(el, key, nextValue, isSVG);
  }
};
function shouldSetAsProp(el, key, value, isSVG) {
  if (isSVG) {
    if (key === "innerHTML" || key === "textContent") {
      return true;
    }
    if (key in el && isNativeOn(key) && isFunction$2(value)) {
      return true;
    }
    return false;
  }
  if (key === "spellcheck" || key === "draggable" || key === "translate" || key === "autocorrect") {
    return false;
  }
  if (key === "sandbox" && el.tagName === "IFRAME") {
    return false;
  }
  if (key === "form") {
    return false;
  }
  if (key === "list" && el.tagName === "INPUT") {
    return false;
  }
  if (key === "type" && el.tagName === "TEXTAREA") {
    return false;
  }
  if (key === "width" || key === "height") {
    const tag = el.tagName;
    if (tag === "IMG" || tag === "VIDEO" || tag === "CANVAS" || tag === "SOURCE") {
      return false;
    }
  }
  if (isNativeOn(key) && isString$1(value)) {
    return false;
  }
  return key in el;
}
const getModelAssigner = (vnode) => {
  const fn = vnode.props["onUpdate:modelValue"] || false;
  return isArray$1(fn) ? (value) => invokeArrayFns(fn, value) : fn;
};
function onCompositionStart(e) {
  e.target.composing = true;
}
function onCompositionEnd(e) {
  const target = e.target;
  if (target.composing) {
    target.composing = false;
    target.dispatchEvent(new Event("input"));
  }
}
const assignKey = Symbol("_assign");
function castValue(value, trim2, number) {
  if (trim2) value = value.trim();
  if (number) value = looseToNumber(value);
  return value;
}
const vModelText = {
  created(el, { modifiers: { lazy, trim: trim2, number } }, vnode) {
    el[assignKey] = getModelAssigner(vnode);
    const castToNumber = number || vnode.props && vnode.props.type === "number";
    addEventListener(el, lazy ? "change" : "input", (e) => {
      if (e.target.composing) return;
      el[assignKey](castValue(el.value, trim2, castToNumber));
    });
    if (trim2 || castToNumber) {
      addEventListener(el, "change", () => {
        el.value = castValue(el.value, trim2, castToNumber);
      });
    }
    if (!lazy) {
      addEventListener(el, "compositionstart", onCompositionStart);
      addEventListener(el, "compositionend", onCompositionEnd);
      addEventListener(el, "change", onCompositionEnd);
    }
  },
  // set value on mounted so it's after min/max for type="range"
  mounted(el, { value }) {
    el.value = value == null ? "" : value;
  },
  beforeUpdate(el, { value, oldValue, modifiers: { lazy, trim: trim2, number } }, vnode) {
    el[assignKey] = getModelAssigner(vnode);
    if (el.composing) return;
    const elValue = (number || el.type === "number") && !/^0\d/.test(el.value) ? looseToNumber(el.value) : el.value;
    const newValue = value == null ? "" : value;
    if (elValue === newValue) {
      return;
    }
    if (document.activeElement === el && el.type !== "range") {
      if (lazy && value === oldValue) {
        return;
      }
      if (trim2 && el.value.trim() === newValue) {
        return;
      }
    }
    el.value = newValue;
  }
};
const systemModifiers = ["ctrl", "shift", "alt", "meta"];
const modifierGuards = {
  stop: (e) => e.stopPropagation(),
  prevent: (e) => e.preventDefault(),
  self: (e) => e.target !== e.currentTarget,
  ctrl: (e) => !e.ctrlKey,
  shift: (e) => !e.shiftKey,
  alt: (e) => !e.altKey,
  meta: (e) => !e.metaKey,
  left: (e) => "button" in e && e.button !== 0,
  middle: (e) => "button" in e && e.button !== 1,
  right: (e) => "button" in e && e.button !== 2,
  exact: (e, modifiers) => systemModifiers.some((m) => e[`${m}Key`] && !modifiers.includes(m))
};
const withModifiers = (fn, modifiers) => {
  const cache = fn._withMods || (fn._withMods = {});
  const cacheKey = modifiers.join(".");
  return cache[cacheKey] || (cache[cacheKey] = ((event, ...args) => {
    for (let i = 0; i < modifiers.length; i++) {
      const guard = modifierGuards[modifiers[i]];
      if (guard && guard(event, modifiers)) return;
    }
    return fn(event, ...args);
  }));
};
const keyNames = {
  esc: "escape",
  space: " ",
  up: "arrow-up",
  left: "arrow-left",
  right: "arrow-right",
  down: "arrow-down",
  delete: "backspace"
};
const withKeys = (fn, modifiers) => {
  const cache = fn._withKeys || (fn._withKeys = {});
  const cacheKey = modifiers.join(".");
  return cache[cacheKey] || (cache[cacheKey] = ((event) => {
    if (!("key" in event)) {
      return;
    }
    const eventKey = hyphenate(event.key);
    if (modifiers.some(
      (k) => k === eventKey || keyNames[k] === eventKey
    )) {
      return fn(event);
    }
  }));
};
const rendererOptions = /* @__PURE__ */ extend$1({ patchProp }, nodeOps);
let renderer;
function ensureRenderer() {
  return renderer || (renderer = createRenderer(rendererOptions));
}
const render = ((...args) => {
  ensureRenderer().render(...args);
});
const createApp = ((...args) => {
  const app = ensureRenderer().createApp(...args);
  {
    injectNativeTagCheck(app);
    injectCompilerOptionsCheck(app);
  }
  const { mount } = app;
  app.mount = (containerOrSelector) => {
    const container = normalizeContainer(containerOrSelector);
    if (!container) return;
    const component = app._component;
    if (!isFunction$2(component) && !component.render && !component.template) {
      component.template = container.innerHTML;
    }
    if (container.nodeType === 1) {
      container.textContent = "";
    }
    const proxy = mount(container, false, resolveRootNamespace(container));
    if (container instanceof Element) {
      container.removeAttribute("v-cloak");
      container.setAttribute("data-v-app", "");
    }
    return proxy;
  };
  return app;
});
function resolveRootNamespace(container) {
  if (container instanceof SVGElement) {
    return "svg";
  }
  if (typeof MathMLElement === "function" && container instanceof MathMLElement) {
    return "mathml";
  }
}
function injectNativeTagCheck(app) {
  Object.defineProperty(app.config, "isNativeTag", {
    value: (tag) => isHTMLTag(tag) || isSVGTag(tag) || isMathMLTag(tag),
    writable: false
  });
}
function injectCompilerOptionsCheck(app) {
  {
    const isCustomElement = app.config.isCustomElement;
    Object.defineProperty(app.config, "isCustomElement", {
      get() {
        return isCustomElement;
      },
      set() {
        warn(
          `The \`isCustomElement\` config option is deprecated. Use \`compilerOptions.isCustomElement\` instead.`
        );
      }
    });
    const compilerOptions = app.config.compilerOptions;
    const msg = `The \`compilerOptions\` config option is only respected when using a build of Vue.js that includes the runtime compiler (aka "full build"). Since you are using the runtime-only build, \`compilerOptions\` must be passed to \`@vue/compiler-dom\` in the build setup instead.
- For vue-loader: pass it via vue-loader's \`compilerOptions\` loader option.
- For vue-cli: see https://cli.vuejs.org/guide/webpack.html#modifying-options-of-a-loader
- For vite: pass it via @vitejs/plugin-vue options. See https://github.com/vitejs/vite-plugin-vue/tree/main/packages/plugin-vue#example-for-passing-options-to-vuecompiler-sfc`;
    Object.defineProperty(app.config, "compilerOptions", {
      get() {
        warn(msg);
        return compilerOptions;
      },
      set() {
        warn(msg);
      }
    });
  }
}
function normalizeContainer(container) {
  if (isString$1(container)) {
    const res = document.querySelector(container);
    if (!res) {
      warn(
        `Failed to mount app: mount target selector "${container}" returned null.`
      );
    }
    return res;
  }
  if (window.ShadowRoot && container instanceof window.ShadowRoot && container.mode === "closed") {
    warn(
      `mounting on a ShadowRoot with \`{mode: "closed"}\` may lead to unpredictable bugs`
    );
  }
  return container;
}
/**
* vue v3.5.25
* (c) 2018-present Yuxi (Evan) You and Vue contributors
* @license MIT
**/
function initDev() {
  {
    initCustomFormatter();
  }
}
{
  initDev();
}
function getDefaultExportFromCjs$1(x) {
  return x && x.__esModule && Object.prototype.hasOwnProperty.call(x, "default") ? x["default"] : x;
}
function getDefaultExportFromCjs(x) {
  return x && x.__esModule && Object.prototype.hasOwnProperty.call(x, "default") ? x["default"] : x;
}
var browser = { exports: {} };
var process = browser.exports = {};
var cachedSetTimeout;
var cachedClearTimeout;
function defaultSetTimout() {
  throw new Error("setTimeout has not been defined");
}
function defaultClearTimeout() {
  throw new Error("clearTimeout has not been defined");
}
(function() {
  try {
    if (typeof setTimeout === "function") {
      cachedSetTimeout = setTimeout;
    } else {
      cachedSetTimeout = defaultSetTimout;
    }
  } catch (e) {
    cachedSetTimeout = defaultSetTimout;
  }
  try {
    if (typeof clearTimeout === "function") {
      cachedClearTimeout = clearTimeout;
    } else {
      cachedClearTimeout = defaultClearTimeout;
    }
  } catch (e) {
    cachedClearTimeout = defaultClearTimeout;
  }
})();
function runTimeout(fun) {
  if (cachedSetTimeout === setTimeout) {
    return setTimeout(fun, 0);
  }
  if ((cachedSetTimeout === defaultSetTimout || !cachedSetTimeout) && setTimeout) {
    cachedSetTimeout = setTimeout;
    return setTimeout(fun, 0);
  }
  try {
    return cachedSetTimeout(fun, 0);
  } catch (e) {
    try {
      return cachedSetTimeout.call(null, fun, 0);
    } catch (e2) {
      return cachedSetTimeout.call(this, fun, 0);
    }
  }
}
function runClearTimeout(marker) {
  if (cachedClearTimeout === clearTimeout) {
    return clearTimeout(marker);
  }
  if ((cachedClearTimeout === defaultClearTimeout || !cachedClearTimeout) && clearTimeout) {
    cachedClearTimeout = clearTimeout;
    return clearTimeout(marker);
  }
  try {
    return cachedClearTimeout(marker);
  } catch (e) {
    try {
      return cachedClearTimeout.call(null, marker);
    } catch (e2) {
      return cachedClearTimeout.call(this, marker);
    }
  }
}
var queue = [];
var draining = false;
var currentQueue;
var queueIndex = -1;
function cleanUpNextTick() {
  if (!draining || !currentQueue) {
    return;
  }
  draining = false;
  if (currentQueue.length) {
    queue = currentQueue.concat(queue);
  } else {
    queueIndex = -1;
  }
  if (queue.length) {
    drainQueue();
  }
}
function drainQueue() {
  if (draining) {
    return;
  }
  var timeout = runTimeout(cleanUpNextTick);
  draining = true;
  var len = queue.length;
  while (len) {
    currentQueue = queue;
    queue = [];
    while (++queueIndex < len) {
      if (currentQueue) {
        currentQueue[queueIndex].run();
      }
    }
    queueIndex = -1;
    len = queue.length;
  }
  currentQueue = null;
  draining = false;
  runClearTimeout(timeout);
}
process.nextTick = function(fun) {
  var args = new Array(arguments.length - 1);
  if (arguments.length > 1) {
    for (var i = 1; i < arguments.length; i++) {
      args[i - 1] = arguments[i];
    }
  }
  queue.push(new Item(fun, args));
  if (queue.length === 1 && !draining) {
    runTimeout(drainQueue);
  }
};
function Item(fun, array) {
  this.fun = fun;
  this.array = array;
}
Item.prototype.run = function() {
  this.fun.apply(null, this.array);
};
process.title = "browser";
process.browser = true;
process.env = {};
process.argv = [];
process.version = "";
process.versions = {};
function noop$1() {
}
process.on = noop$1;
process.addListener = noop$1;
process.once = noop$1;
process.off = noop$1;
process.removeListener = noop$1;
process.removeAllListeners = noop$1;
process.emit = noop$1;
process.prependListener = noop$1;
process.prependOnceListener = noop$1;
process.listeners = function(name) {
  return [];
};
process.binding = function(name) {
  throw new Error("process.binding is not supported");
};
process.cwd = function() {
  return "/";
};
process.chdir = function(dir) {
  throw new Error("process.chdir is not supported");
};
process.umask = function() {
  return 0;
};
var browserExports = browser.exports;
const process$1 = /* @__PURE__ */ getDefaultExportFromCjs(browserExports);
var debug_1;
var hasRequiredDebug;
function requireDebug() {
  if (hasRequiredDebug) return debug_1;
  hasRequiredDebug = 1;
  var define_process_env_default = {};
  const debug = typeof process$1 === "object" && define_process_env_default && define_process_env_default.NODE_DEBUG && /\bsemver\b/i.test(define_process_env_default.NODE_DEBUG) ? (...args) => console.error("SEMVER", ...args) : () => {
  };
  debug_1 = debug;
  return debug_1;
}
var constants;
var hasRequiredConstants;
function requireConstants() {
  if (hasRequiredConstants) return constants;
  hasRequiredConstants = 1;
  const SEMVER_SPEC_VERSION = "2.0.0";
  const MAX_LENGTH = 256;
  const MAX_SAFE_INTEGER = Number.MAX_SAFE_INTEGER || /* istanbul ignore next */
  9007199254740991;
  const MAX_SAFE_COMPONENT_LENGTH = 16;
  const MAX_SAFE_BUILD_LENGTH = MAX_LENGTH - 6;
  const RELEASE_TYPES = [
    "major",
    "premajor",
    "minor",
    "preminor",
    "patch",
    "prepatch",
    "prerelease"
  ];
  constants = {
    MAX_LENGTH,
    MAX_SAFE_COMPONENT_LENGTH,
    MAX_SAFE_BUILD_LENGTH,
    MAX_SAFE_INTEGER,
    RELEASE_TYPES,
    SEMVER_SPEC_VERSION,
    FLAG_INCLUDE_PRERELEASE: 1,
    FLAG_LOOSE: 2
  };
  return constants;
}
var re = { exports: {} };
var hasRequiredRe;
function requireRe() {
  if (hasRequiredRe) return re.exports;
  hasRequiredRe = 1;
  (function(module, exports) {
    const {
      MAX_SAFE_COMPONENT_LENGTH,
      MAX_SAFE_BUILD_LENGTH,
      MAX_LENGTH
    } = requireConstants();
    const debug = requireDebug();
    exports = module.exports = {};
    const re2 = exports.re = [];
    const safeRe = exports.safeRe = [];
    const src = exports.src = [];
    const safeSrc = exports.safeSrc = [];
    const t7 = exports.t = {};
    let R = 0;
    const LETTERDASHNUMBER = "[a-zA-Z0-9-]";
    const safeRegexReplacements = [
      ["\\s", 1],
      ["\\d", MAX_LENGTH],
      [LETTERDASHNUMBER, MAX_SAFE_BUILD_LENGTH]
    ];
    const makeSafeRegex = (value) => {
      for (const [token2, max] of safeRegexReplacements) {
        value = value.split(`${token2}*`).join(`${token2}{0,${max}}`).split(`${token2}+`).join(`${token2}{1,${max}}`);
      }
      return value;
    };
    const createToken = (name, value, isGlobal) => {
      const safe = makeSafeRegex(value);
      const index = R++;
      debug(name, index, value);
      t7[name] = index;
      src[index] = value;
      safeSrc[index] = safe;
      re2[index] = new RegExp(value, isGlobal ? "g" : void 0);
      safeRe[index] = new RegExp(safe, isGlobal ? "g" : void 0);
    };
    createToken("NUMERICIDENTIFIER", "0|[1-9]\\d*");
    createToken("NUMERICIDENTIFIERLOOSE", "\\d+");
    createToken("NONNUMERICIDENTIFIER", `\\d*[a-zA-Z-]${LETTERDASHNUMBER}*`);
    createToken("MAINVERSION", `(${src[t7.NUMERICIDENTIFIER]})\\.(${src[t7.NUMERICIDENTIFIER]})\\.(${src[t7.NUMERICIDENTIFIER]})`);
    createToken("MAINVERSIONLOOSE", `(${src[t7.NUMERICIDENTIFIERLOOSE]})\\.(${src[t7.NUMERICIDENTIFIERLOOSE]})\\.(${src[t7.NUMERICIDENTIFIERLOOSE]})`);
    createToken("PRERELEASEIDENTIFIER", `(?:${src[t7.NONNUMERICIDENTIFIER]}|${src[t7.NUMERICIDENTIFIER]})`);
    createToken("PRERELEASEIDENTIFIERLOOSE", `(?:${src[t7.NONNUMERICIDENTIFIER]}|${src[t7.NUMERICIDENTIFIERLOOSE]})`);
    createToken("PRERELEASE", `(?:-(${src[t7.PRERELEASEIDENTIFIER]}(?:\\.${src[t7.PRERELEASEIDENTIFIER]})*))`);
    createToken("PRERELEASELOOSE", `(?:-?(${src[t7.PRERELEASEIDENTIFIERLOOSE]}(?:\\.${src[t7.PRERELEASEIDENTIFIERLOOSE]})*))`);
    createToken("BUILDIDENTIFIER", `${LETTERDASHNUMBER}+`);
    createToken("BUILD", `(?:\\+(${src[t7.BUILDIDENTIFIER]}(?:\\.${src[t7.BUILDIDENTIFIER]})*))`);
    createToken("FULLPLAIN", `v?${src[t7.MAINVERSION]}${src[t7.PRERELEASE]}?${src[t7.BUILD]}?`);
    createToken("FULL", `^${src[t7.FULLPLAIN]}$`);
    createToken("LOOSEPLAIN", `[v=\\s]*${src[t7.MAINVERSIONLOOSE]}${src[t7.PRERELEASELOOSE]}?${src[t7.BUILD]}?`);
    createToken("LOOSE", `^${src[t7.LOOSEPLAIN]}$`);
    createToken("GTLT", "((?:<|>)?=?)");
    createToken("XRANGEIDENTIFIERLOOSE", `${src[t7.NUMERICIDENTIFIERLOOSE]}|x|X|\\*`);
    createToken("XRANGEIDENTIFIER", `${src[t7.NUMERICIDENTIFIER]}|x|X|\\*`);
    createToken("XRANGEPLAIN", `[v=\\s]*(${src[t7.XRANGEIDENTIFIER]})(?:\\.(${src[t7.XRANGEIDENTIFIER]})(?:\\.(${src[t7.XRANGEIDENTIFIER]})(?:${src[t7.PRERELEASE]})?${src[t7.BUILD]}?)?)?`);
    createToken("XRANGEPLAINLOOSE", `[v=\\s]*(${src[t7.XRANGEIDENTIFIERLOOSE]})(?:\\.(${src[t7.XRANGEIDENTIFIERLOOSE]})(?:\\.(${src[t7.XRANGEIDENTIFIERLOOSE]})(?:${src[t7.PRERELEASELOOSE]})?${src[t7.BUILD]}?)?)?`);
    createToken("XRANGE", `^${src[t7.GTLT]}\\s*${src[t7.XRANGEPLAIN]}$`);
    createToken("XRANGELOOSE", `^${src[t7.GTLT]}\\s*${src[t7.XRANGEPLAINLOOSE]}$`);
    createToken("COERCEPLAIN", `${"(^|[^\\d])(\\d{1,"}${MAX_SAFE_COMPONENT_LENGTH}})(?:\\.(\\d{1,${MAX_SAFE_COMPONENT_LENGTH}}))?(?:\\.(\\d{1,${MAX_SAFE_COMPONENT_LENGTH}}))?`);
    createToken("COERCE", `${src[t7.COERCEPLAIN]}(?:$|[^\\d])`);
    createToken("COERCEFULL", src[t7.COERCEPLAIN] + `(?:${src[t7.PRERELEASE]})?(?:${src[t7.BUILD]})?(?:$|[^\\d])`);
    createToken("COERCERTL", src[t7.COERCE], true);
    createToken("COERCERTLFULL", src[t7.COERCEFULL], true);
    createToken("LONETILDE", "(?:~>?)");
    createToken("TILDETRIM", `(\\s*)${src[t7.LONETILDE]}\\s+`, true);
    exports.tildeTrimReplace = "$1~";
    createToken("TILDE", `^${src[t7.LONETILDE]}${src[t7.XRANGEPLAIN]}$`);
    createToken("TILDELOOSE", `^${src[t7.LONETILDE]}${src[t7.XRANGEPLAINLOOSE]}$`);
    createToken("LONECARET", "(?:\\^)");
    createToken("CARETTRIM", `(\\s*)${src[t7.LONECARET]}\\s+`, true);
    exports.caretTrimReplace = "$1^";
    createToken("CARET", `^${src[t7.LONECARET]}${src[t7.XRANGEPLAIN]}$`);
    createToken("CARETLOOSE", `^${src[t7.LONECARET]}${src[t7.XRANGEPLAINLOOSE]}$`);
    createToken("COMPARATORLOOSE", `^${src[t7.GTLT]}\\s*(${src[t7.LOOSEPLAIN]})$|^$`);
    createToken("COMPARATOR", `^${src[t7.GTLT]}\\s*(${src[t7.FULLPLAIN]})$|^$`);
    createToken("COMPARATORTRIM", `(\\s*)${src[t7.GTLT]}\\s*(${src[t7.LOOSEPLAIN]}|${src[t7.XRANGEPLAIN]})`, true);
    exports.comparatorTrimReplace = "$1$2$3";
    createToken("HYPHENRANGE", `^\\s*(${src[t7.XRANGEPLAIN]})\\s+-\\s+(${src[t7.XRANGEPLAIN]})\\s*$`);
    createToken("HYPHENRANGELOOSE", `^\\s*(${src[t7.XRANGEPLAINLOOSE]})\\s+-\\s+(${src[t7.XRANGEPLAINLOOSE]})\\s*$`);
    createToken("STAR", "(<|>)?=?\\s*\\*");
    createToken("GTE0", "^\\s*>=\\s*0\\.0\\.0\\s*$");
    createToken("GTE0PRE", "^\\s*>=\\s*0\\.0\\.0-0\\s*$");
  })(re, re.exports);
  return re.exports;
}
var parseOptions_1;
var hasRequiredParseOptions;
function requireParseOptions() {
  if (hasRequiredParseOptions) return parseOptions_1;
  hasRequiredParseOptions = 1;
  const looseOption = Object.freeze({ loose: true });
  const emptyOpts = Object.freeze({});
  const parseOptions = (options) => {
    if (!options) {
      return emptyOpts;
    }
    if (typeof options !== "object") {
      return looseOption;
    }
    return options;
  };
  parseOptions_1 = parseOptions;
  return parseOptions_1;
}
var identifiers;
var hasRequiredIdentifiers;
function requireIdentifiers() {
  if (hasRequiredIdentifiers) return identifiers;
  hasRequiredIdentifiers = 1;
  const numeric = /^[0-9]+$/;
  const compareIdentifiers = (a, b) => {
    if (typeof a === "number" && typeof b === "number") {
      return a === b ? 0 : a < b ? -1 : 1;
    }
    const anum = numeric.test(a);
    const bnum = numeric.test(b);
    if (anum && bnum) {
      a = +a;
      b = +b;
    }
    return a === b ? 0 : anum && !bnum ? -1 : bnum && !anum ? 1 : a < b ? -1 : 1;
  };
  const rcompareIdentifiers = (a, b) => compareIdentifiers(b, a);
  identifiers = {
    compareIdentifiers,
    rcompareIdentifiers
  };
  return identifiers;
}
var semver;
var hasRequiredSemver;
function requireSemver() {
  if (hasRequiredSemver) return semver;
  hasRequiredSemver = 1;
  const debug = requireDebug();
  const { MAX_LENGTH, MAX_SAFE_INTEGER } = requireConstants();
  const { safeRe: re2, t: t7 } = requireRe();
  const parseOptions = requireParseOptions();
  const { compareIdentifiers } = requireIdentifiers();
  class SemVer {
    constructor(version2, options) {
      options = parseOptions(options);
      if (version2 instanceof SemVer) {
        if (version2.loose === !!options.loose && version2.includePrerelease === !!options.includePrerelease) {
          return version2;
        } else {
          version2 = version2.version;
        }
      } else if (typeof version2 !== "string") {
        throw new TypeError(`Invalid version. Must be a string. Got type "${typeof version2}".`);
      }
      if (version2.length > MAX_LENGTH) {
        throw new TypeError(
          `version is longer than ${MAX_LENGTH} characters`
        );
      }
      debug("SemVer", version2, options);
      this.options = options;
      this.loose = !!options.loose;
      this.includePrerelease = !!options.includePrerelease;
      const m = version2.trim().match(options.loose ? re2[t7.LOOSE] : re2[t7.FULL]);
      if (!m) {
        throw new TypeError(`Invalid Version: ${version2}`);
      }
      this.raw = version2;
      this.major = +m[1];
      this.minor = +m[2];
      this.patch = +m[3];
      if (this.major > MAX_SAFE_INTEGER || this.major < 0) {
        throw new TypeError("Invalid major version");
      }
      if (this.minor > MAX_SAFE_INTEGER || this.minor < 0) {
        throw new TypeError("Invalid minor version");
      }
      if (this.patch > MAX_SAFE_INTEGER || this.patch < 0) {
        throw new TypeError("Invalid patch version");
      }
      if (!m[4]) {
        this.prerelease = [];
      } else {
        this.prerelease = m[4].split(".").map((id) => {
          if (/^[0-9]+$/.test(id)) {
            const num = +id;
            if (num >= 0 && num < MAX_SAFE_INTEGER) {
              return num;
            }
          }
          return id;
        });
      }
      this.build = m[5] ? m[5].split(".") : [];
      this.format();
    }
    format() {
      this.version = `${this.major}.${this.minor}.${this.patch}`;
      if (this.prerelease.length) {
        this.version += `-${this.prerelease.join(".")}`;
      }
      return this.version;
    }
    toString() {
      return this.version;
    }
    compare(other) {
      debug("SemVer.compare", this.version, this.options, other);
      if (!(other instanceof SemVer)) {
        if (typeof other === "string" && other === this.version) {
          return 0;
        }
        other = new SemVer(other, this.options);
      }
      if (other.version === this.version) {
        return 0;
      }
      return this.compareMain(other) || this.comparePre(other);
    }
    compareMain(other) {
      if (!(other instanceof SemVer)) {
        other = new SemVer(other, this.options);
      }
      if (this.major < other.major) {
        return -1;
      }
      if (this.major > other.major) {
        return 1;
      }
      if (this.minor < other.minor) {
        return -1;
      }
      if (this.minor > other.minor) {
        return 1;
      }
      if (this.patch < other.patch) {
        return -1;
      }
      if (this.patch > other.patch) {
        return 1;
      }
      return 0;
    }
    comparePre(other) {
      if (!(other instanceof SemVer)) {
        other = new SemVer(other, this.options);
      }
      if (this.prerelease.length && !other.prerelease.length) {
        return -1;
      } else if (!this.prerelease.length && other.prerelease.length) {
        return 1;
      } else if (!this.prerelease.length && !other.prerelease.length) {
        return 0;
      }
      let i = 0;
      do {
        const a = this.prerelease[i];
        const b = other.prerelease[i];
        debug("prerelease compare", i, a, b);
        if (a === void 0 && b === void 0) {
          return 0;
        } else if (b === void 0) {
          return 1;
        } else if (a === void 0) {
          return -1;
        } else if (a === b) {
          continue;
        } else {
          return compareIdentifiers(a, b);
        }
      } while (++i);
    }
    compareBuild(other) {
      if (!(other instanceof SemVer)) {
        other = new SemVer(other, this.options);
      }
      let i = 0;
      do {
        const a = this.build[i];
        const b = other.build[i];
        debug("build compare", i, a, b);
        if (a === void 0 && b === void 0) {
          return 0;
        } else if (b === void 0) {
          return 1;
        } else if (a === void 0) {
          return -1;
        } else if (a === b) {
          continue;
        } else {
          return compareIdentifiers(a, b);
        }
      } while (++i);
    }
    // preminor will bump the version up to the next minor release, and immediately
    // down to pre-release. premajor and prepatch work the same way.
    inc(release, identifier, identifierBase) {
      if (release.startsWith("pre")) {
        if (!identifier && identifierBase === false) {
          throw new Error("invalid increment argument: identifier is empty");
        }
        if (identifier) {
          const match = `-${identifier}`.match(this.options.loose ? re2[t7.PRERELEASELOOSE] : re2[t7.PRERELEASE]);
          if (!match || match[1] !== identifier) {
            throw new Error(`invalid identifier: ${identifier}`);
          }
        }
      }
      switch (release) {
        case "premajor":
          this.prerelease.length = 0;
          this.patch = 0;
          this.minor = 0;
          this.major++;
          this.inc("pre", identifier, identifierBase);
          break;
        case "preminor":
          this.prerelease.length = 0;
          this.patch = 0;
          this.minor++;
          this.inc("pre", identifier, identifierBase);
          break;
        case "prepatch":
          this.prerelease.length = 0;
          this.inc("patch", identifier, identifierBase);
          this.inc("pre", identifier, identifierBase);
          break;
        // If the input is a non-prerelease version, this acts the same as
        // prepatch.
        case "prerelease":
          if (this.prerelease.length === 0) {
            this.inc("patch", identifier, identifierBase);
          }
          this.inc("pre", identifier, identifierBase);
          break;
        case "release":
          if (this.prerelease.length === 0) {
            throw new Error(`version ${this.raw} is not a prerelease`);
          }
          this.prerelease.length = 0;
          break;
        case "major":
          if (this.minor !== 0 || this.patch !== 0 || this.prerelease.length === 0) {
            this.major++;
          }
          this.minor = 0;
          this.patch = 0;
          this.prerelease = [];
          break;
        case "minor":
          if (this.patch !== 0 || this.prerelease.length === 0) {
            this.minor++;
          }
          this.patch = 0;
          this.prerelease = [];
          break;
        case "patch":
          if (this.prerelease.length === 0) {
            this.patch++;
          }
          this.prerelease = [];
          break;
        // This probably shouldn't be used publicly.
        // 1.0.0 'pre' would become 1.0.0-0 which is the wrong direction.
        case "pre": {
          const base = Number(identifierBase) ? 1 : 0;
          if (this.prerelease.length === 0) {
            this.prerelease = [base];
          } else {
            let i = this.prerelease.length;
            while (--i >= 0) {
              if (typeof this.prerelease[i] === "number") {
                this.prerelease[i]++;
                i = -2;
              }
            }
            if (i === -1) {
              if (identifier === this.prerelease.join(".") && identifierBase === false) {
                throw new Error("invalid increment argument: identifier already exists");
              }
              this.prerelease.push(base);
            }
          }
          if (identifier) {
            let prerelease = [identifier, base];
            if (identifierBase === false) {
              prerelease = [identifier];
            }
            if (compareIdentifiers(this.prerelease[0], identifier) === 0) {
              if (isNaN(this.prerelease[1])) {
                this.prerelease = prerelease;
              }
            } else {
              this.prerelease = prerelease;
            }
          }
          break;
        }
        default:
          throw new Error(`invalid increment argument: ${release}`);
      }
      this.raw = this.format();
      if (this.build.length) {
        this.raw += `+${this.build.join(".")}`;
      }
      return this;
    }
  }
  semver = SemVer;
  return semver;
}
var major_1;
var hasRequiredMajor;
function requireMajor() {
  if (hasRequiredMajor) return major_1;
  hasRequiredMajor = 1;
  const SemVer = requireSemver();
  const major2 = (a, loose) => new SemVer(a, loose).major;
  major_1 = major2;
  return major_1;
}
var majorExports = requireMajor();
const major = /* @__PURE__ */ getDefaultExportFromCjs$1(majorExports);
var parse_1;
var hasRequiredParse;
function requireParse() {
  if (hasRequiredParse) return parse_1;
  hasRequiredParse = 1;
  const SemVer = requireSemver();
  const parse = (version2, options, throwErrors = false) => {
    if (version2 instanceof SemVer) {
      return version2;
    }
    try {
      return new SemVer(version2, options);
    } catch (er) {
      if (!throwErrors) {
        return null;
      }
      throw er;
    }
  };
  parse_1 = parse;
  return parse_1;
}
var valid_1;
var hasRequiredValid;
function requireValid() {
  if (hasRequiredValid) return valid_1;
  hasRequiredValid = 1;
  const parse = requireParse();
  const valid2 = (version2, options) => {
    const v = parse(version2, options);
    return v ? v.version : null;
  };
  valid_1 = valid2;
  return valid_1;
}
var validExports = requireValid();
const valid = /* @__PURE__ */ getDefaultExportFromCjs$1(validExports);
/*!
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: GPL-3.0-or-later
 */
class ProxyBus {
  bus;
  constructor(bus2) {
    if (typeof bus2.getVersion !== "function" || !valid(bus2.getVersion())) {
      console.warn("Proxying an event bus with an unknown or invalid version");
    } else if (major(bus2.getVersion()) !== major(this.getVersion())) {
      console.warn(
        "Proxying an event bus of version " + bus2.getVersion() + " with " + this.getVersion()
      );
    }
    this.bus = bus2;
  }
  getVersion() {
    return "3.3.3";
  }
  subscribe(name, handler) {
    this.bus.subscribe(name, handler);
  }
  unsubscribe(name, handler) {
    this.bus.unsubscribe(name, handler);
  }
  emit(name, ...event) {
    this.bus.emit(name, ...event);
  }
}
/*!
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: GPL-3.0-or-later
 */
class SimpleBus {
  handlers = /* @__PURE__ */ new Map();
  getVersion() {
    return "3.3.3";
  }
  subscribe(name, handler) {
    this.handlers.set(
      name,
      (this.handlers.get(name) || []).concat(
        handler
      )
    );
  }
  unsubscribe(name, handler) {
    this.handlers.set(
      name,
      (this.handlers.get(name) || []).filter((h2) => h2 !== handler)
    );
  }
  emit(name, ...event) {
    const handlers = this.handlers.get(name) || [];
    handlers.forEach((h2) => {
      try {
        ;
        h2(event[0]);
      } catch (e) {
        console.error("could not invoke event listener", e);
      }
    });
  }
}
/*!
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: GPL-3.0-or-later
 */
let bus = null;
function getBus() {
  if (bus !== null) {
    return bus;
  }
  if (typeof window === "undefined") {
    return new Proxy({}, {
      get: () => {
        return () => console.error(
          "Window not available, EventBus can not be established!"
        );
      }
    });
  }
  if (window.OC?._eventBus && typeof window._nc_event_bus === "undefined") {
    console.warn(
      "found old event bus instance at OC._eventBus. Update your version!"
    );
    window._nc_event_bus = window.OC._eventBus;
  }
  if (typeof window?._nc_event_bus !== "undefined") {
    bus = new ProxyBus(window._nc_event_bus);
  } else {
    bus = window._nc_event_bus = new SimpleBus();
  }
  return bus;
}
function subscribe(name, handler) {
  getBus().subscribe(name, handler);
}
function unsubscribe(name, handler) {
  getBus().unsubscribe(name, handler);
}
function emit(name, ...event) {
  getBus().emit(name, ...event);
}
class ScopedStorage {
  static GLOBAL_SCOPE_VOLATILE = "nextcloud_vol";
  static GLOBAL_SCOPE_PERSISTENT = "nextcloud_per";
  scope;
  wrapped;
  constructor(scope, wrapped, persistent) {
    this.scope = `${persistent ? ScopedStorage.GLOBAL_SCOPE_PERSISTENT : ScopedStorage.GLOBAL_SCOPE_VOLATILE}_${btoa(scope)}_`;
    this.wrapped = wrapped;
  }
  scopeKey(key) {
    return `${this.scope}${key}`;
  }
  setItem(key, value) {
    this.wrapped.setItem(this.scopeKey(key), value);
  }
  getItem(key) {
    return this.wrapped.getItem(this.scopeKey(key));
  }
  removeItem(key) {
    this.wrapped.removeItem(this.scopeKey(key));
  }
  clear() {
    Object.keys(this.wrapped).filter((key) => key.startsWith(this.scope)).map(this.wrapped.removeItem.bind(this.wrapped));
  }
}
class StorageBuilder {
  appId;
  persisted = false;
  clearedOnLogout = false;
  constructor(appId) {
    this.appId = appId;
  }
  persist(persist = true) {
    this.persisted = persist;
    return this;
  }
  clearOnLogout(clear = true) {
    this.clearedOnLogout = clear;
    return this;
  }
  build() {
    return new ScopedStorage(this.appId, this.persisted ? window.localStorage : window.sessionStorage, !this.clearedOnLogout);
  }
}
function getBuilder(appId) {
  return new StorageBuilder(appId);
}
let token;
const observers = [];
function getRequestToken() {
  if (token === void 0) {
    token = document.head.dataset.requesttoken ?? null;
  }
  return token;
}
function onRequestTokenUpdate(observer) {
  observers.push(observer);
}
subscribe("csrf-token-update", (e) => {
  token = e.token;
  observers.forEach((observer) => {
    try {
      observer(token);
    } catch (error) {
      console.error("Error updating CSRF token observer", error);
    }
  });
});
/*!
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: GPL-3.0-or-later
 */
getBuilder("public").persist().build();
let currentUser;
function getAttribute(el, attribute) {
  if (el) {
    return el.getAttribute(attribute);
  }
  return null;
}
function getCurrentUser() {
  if (currentUser !== void 0) {
    return currentUser;
  }
  const head = document?.getElementsByTagName("head")[0];
  if (!head) {
    return null;
  }
  const uid2 = getAttribute(head, "data-user");
  if (uid2 === null) {
    currentUser = null;
    return currentUser;
  }
  currentUser = {
    uid: uid2,
    displayName: getAttribute(head, "data-user-displayname"),
    isAdmin: !!window._oc_isadmin
  };
  return currentUser;
}
function bind(fn, thisArg) {
  return function wrap() {
    return fn.apply(thisArg, arguments);
  };
}
const { toString } = Object.prototype;
const { getPrototypeOf: getPrototypeOf$1 } = Object;
const { iterator, toStringTag } = Symbol;
const kindOf = /* @__PURE__ */ ((cache) => (thing) => {
  const str = toString.call(thing);
  return cache[str] || (cache[str] = str.slice(8, -1).toLowerCase());
})(/* @__PURE__ */ Object.create(null));
const kindOfTest = (type) => {
  type = type.toLowerCase();
  return (thing) => kindOf(thing) === type;
};
const typeOfTest = (type) => (thing) => typeof thing === type;
const { isArray } = Array;
const isUndefined = typeOfTest("undefined");
function isBuffer(val) {
  return val !== null && !isUndefined(val) && val.constructor !== null && !isUndefined(val.constructor) && isFunction$1(val.constructor.isBuffer) && val.constructor.isBuffer(val);
}
const isArrayBuffer = kindOfTest("ArrayBuffer");
function isArrayBufferView(val) {
  let result;
  if (typeof ArrayBuffer !== "undefined" && ArrayBuffer.isView) {
    result = ArrayBuffer.isView(val);
  } else {
    result = val && val.buffer && isArrayBuffer(val.buffer);
  }
  return result;
}
const isString = typeOfTest("string");
const isFunction$1 = typeOfTest("function");
const isNumber = typeOfTest("number");
const isObject = (thing) => thing !== null && typeof thing === "object";
const isBoolean = (thing) => thing === true || thing === false;
const isPlainObject = (val) => {
  if (kindOf(val) !== "object") {
    return false;
  }
  const prototype2 = getPrototypeOf$1(val);
  return (prototype2 === null || prototype2 === Object.prototype || Object.getPrototypeOf(prototype2) === null) && !(toStringTag in val) && !(iterator in val);
};
const isEmptyObject = (val) => {
  if (!isObject(val) || isBuffer(val)) {
    return false;
  }
  try {
    return Object.keys(val).length === 0 && Object.getPrototypeOf(val) === Object.prototype;
  } catch (e) {
    return false;
  }
};
const isDate = kindOfTest("Date");
const isFile = kindOfTest("File");
const isBlob = kindOfTest("Blob");
const isFileList = kindOfTest("FileList");
const isStream = (val) => isObject(val) && isFunction$1(val.pipe);
const isFormData = (thing) => {
  let kind;
  return thing && (typeof FormData === "function" && thing instanceof FormData || isFunction$1(thing.append) && ((kind = kindOf(thing)) === "formdata" || // detect form-data instance
  kind === "object" && isFunction$1(thing.toString) && thing.toString() === "[object FormData]"));
};
const isURLSearchParams = kindOfTest("URLSearchParams");
const [isReadableStream, isRequest, isResponse, isHeaders] = ["ReadableStream", "Request", "Response", "Headers"].map(kindOfTest);
const trim = (str) => str.trim ? str.trim() : str.replace(/^[\s\uFEFF\xA0]+|[\s\uFEFF\xA0]+$/g, "");
function forEach(obj, fn, { allOwnKeys = false } = {}) {
  if (obj === null || typeof obj === "undefined") {
    return;
  }
  let i;
  let l;
  if (typeof obj !== "object") {
    obj = [obj];
  }
  if (isArray(obj)) {
    for (i = 0, l = obj.length; i < l; i++) {
      fn.call(null, obj[i], i, obj);
    }
  } else {
    if (isBuffer(obj)) {
      return;
    }
    const keys = allOwnKeys ? Object.getOwnPropertyNames(obj) : Object.keys(obj);
    const len = keys.length;
    let key;
    for (i = 0; i < len; i++) {
      key = keys[i];
      fn.call(null, obj[key], key, obj);
    }
  }
}
function findKey(obj, key) {
  if (isBuffer(obj)) {
    return null;
  }
  key = key.toLowerCase();
  const keys = Object.keys(obj);
  let i = keys.length;
  let _key;
  while (i-- > 0) {
    _key = keys[i];
    if (key === _key.toLowerCase()) {
      return _key;
    }
  }
  return null;
}
const _global = (() => {
  if (typeof globalThis !== "undefined") return globalThis;
  return typeof self !== "undefined" ? self : typeof window !== "undefined" ? window : global;
})();
const isContextDefined = (context) => !isUndefined(context) && context !== _global;
function merge() {
  const { caseless, skipUndefined } = isContextDefined(this) && this || {};
  const result = {};
  const assignValue = (val, key) => {
    const targetKey = caseless && findKey(result, key) || key;
    if (isPlainObject(result[targetKey]) && isPlainObject(val)) {
      result[targetKey] = merge(result[targetKey], val);
    } else if (isPlainObject(val)) {
      result[targetKey] = merge({}, val);
    } else if (isArray(val)) {
      result[targetKey] = val.slice();
    } else if (!skipUndefined || !isUndefined(val)) {
      result[targetKey] = val;
    }
  };
  for (let i = 0, l = arguments.length; i < l; i++) {
    arguments[i] && forEach(arguments[i], assignValue);
  }
  return result;
}
const extend = (a, b, thisArg, { allOwnKeys } = {}) => {
  forEach(b, (val, key) => {
    if (thisArg && isFunction$1(val)) {
      a[key] = bind(val, thisArg);
    } else {
      a[key] = val;
    }
  }, { allOwnKeys });
  return a;
};
const stripBOM = (content) => {
  if (content.charCodeAt(0) === 65279) {
    content = content.slice(1);
  }
  return content;
};
const inherits = (constructor, superConstructor, props, descriptors2) => {
  constructor.prototype = Object.create(superConstructor.prototype, descriptors2);
  constructor.prototype.constructor = constructor;
  Object.defineProperty(constructor, "super", {
    value: superConstructor.prototype
  });
  props && Object.assign(constructor.prototype, props);
};
const toFlatObject = (sourceObj, destObj, filter2, propFilter) => {
  let props;
  let i;
  let prop;
  const merged = {};
  destObj = destObj || {};
  if (sourceObj == null) return destObj;
  do {
    props = Object.getOwnPropertyNames(sourceObj);
    i = props.length;
    while (i-- > 0) {
      prop = props[i];
      if ((!propFilter || propFilter(prop, sourceObj, destObj)) && !merged[prop]) {
        destObj[prop] = sourceObj[prop];
        merged[prop] = true;
      }
    }
    sourceObj = filter2 !== false && getPrototypeOf$1(sourceObj);
  } while (sourceObj && (!filter2 || filter2(sourceObj, destObj)) && sourceObj !== Object.prototype);
  return destObj;
};
const endsWith = (str, searchString, position) => {
  str = String(str);
  if (position === void 0 || position > str.length) {
    position = str.length;
  }
  position -= searchString.length;
  const lastIndex = str.indexOf(searchString, position);
  return lastIndex !== -1 && lastIndex === position;
};
const toArray = (thing) => {
  if (!thing) return null;
  if (isArray(thing)) return thing;
  let i = thing.length;
  if (!isNumber(i)) return null;
  const arr = new Array(i);
  while (i-- > 0) {
    arr[i] = thing[i];
  }
  return arr;
};
const isTypedArray = /* @__PURE__ */ ((TypedArray) => {
  return (thing) => {
    return TypedArray && thing instanceof TypedArray;
  };
})(typeof Uint8Array !== "undefined" && getPrototypeOf$1(Uint8Array));
const forEachEntry = (obj, fn) => {
  const generator = obj && obj[iterator];
  const _iterator = generator.call(obj);
  let result;
  while ((result = _iterator.next()) && !result.done) {
    const pair = result.value;
    fn.call(obj, pair[0], pair[1]);
  }
};
const matchAll = (regExp, str) => {
  let matches;
  const arr = [];
  while ((matches = regExp.exec(str)) !== null) {
    arr.push(matches);
  }
  return arr;
};
const isHTMLForm = kindOfTest("HTMLFormElement");
const toCamelCase = (str) => {
  return str.toLowerCase().replace(
    /[-_\s]([a-z\d])(\w*)/g,
    function replacer2(m, p1, p2) {
      return p1.toUpperCase() + p2;
    }
  );
};
const hasOwnProperty = (({ hasOwnProperty: hasOwnProperty2 }) => (obj, prop) => hasOwnProperty2.call(obj, prop))(Object.prototype);
const isRegExp = kindOfTest("RegExp");
const reduceDescriptors = (obj, reducer) => {
  const descriptors2 = Object.getOwnPropertyDescriptors(obj);
  const reducedDescriptors = {};
  forEach(descriptors2, (descriptor, name) => {
    let ret;
    if ((ret = reducer(descriptor, name, obj)) !== false) {
      reducedDescriptors[name] = ret || descriptor;
    }
  });
  Object.defineProperties(obj, reducedDescriptors);
};
const freezeMethods = (obj) => {
  reduceDescriptors(obj, (descriptor, name) => {
    if (isFunction$1(obj) && ["arguments", "caller", "callee"].indexOf(name) !== -1) {
      return false;
    }
    const value = obj[name];
    if (!isFunction$1(value)) return;
    descriptor.enumerable = false;
    if ("writable" in descriptor) {
      descriptor.writable = false;
      return;
    }
    if (!descriptor.set) {
      descriptor.set = () => {
        throw Error("Can not rewrite read-only method '" + name + "'");
      };
    }
  });
};
const toObjectSet = (arrayOrString, delimiter) => {
  const obj = {};
  const define = (arr) => {
    arr.forEach((value) => {
      obj[value] = true;
    });
  };
  isArray(arrayOrString) ? define(arrayOrString) : define(String(arrayOrString).split(delimiter));
  return obj;
};
const noop = () => {
};
const toFiniteNumber = (value, defaultValue) => {
  return value != null && Number.isFinite(value = +value) ? value : defaultValue;
};
function isSpecCompliantForm(thing) {
  return !!(thing && isFunction$1(thing.append) && thing[toStringTag] === "FormData" && thing[iterator]);
}
const toJSONObject = (obj) => {
  const stack2 = new Array(10);
  const visit = (source, i) => {
    if (isObject(source)) {
      if (stack2.indexOf(source) >= 0) {
        return;
      }
      if (isBuffer(source)) {
        return source;
      }
      if (!("toJSON" in source)) {
        stack2[i] = source;
        const target = isArray(source) ? [] : {};
        forEach(source, (value, key) => {
          const reducedValue = visit(value, i + 1);
          !isUndefined(reducedValue) && (target[key] = reducedValue);
        });
        stack2[i] = void 0;
        return target;
      }
    }
    return source;
  };
  return visit(obj, 0);
};
const isAsyncFn = kindOfTest("AsyncFunction");
const isThenable = (thing) => thing && (isObject(thing) || isFunction$1(thing)) && isFunction$1(thing.then) && isFunction$1(thing.catch);
const _setImmediate = ((setImmediateSupported, postMessageSupported) => {
  if (setImmediateSupported) {
    return setImmediate;
  }
  return postMessageSupported ? ((token2, callbacks) => {
    _global.addEventListener("message", ({ source, data }) => {
      if (source === _global && data === token2) {
        callbacks.length && callbacks.shift()();
      }
    }, false);
    return (cb) => {
      callbacks.push(cb);
      _global.postMessage(token2, "*");
    };
  })(`axios@${Math.random()}`, []) : (cb) => setTimeout(cb);
})(
  typeof setImmediate === "function",
  isFunction$1(_global.postMessage)
);
const asap = typeof queueMicrotask !== "undefined" ? queueMicrotask.bind(_global) : typeof process$1 !== "undefined" && process$1.nextTick || _setImmediate;
const isIterable = (thing) => thing != null && isFunction$1(thing[iterator]);
const utils$1 = {
  isArray,
  isArrayBuffer,
  isBuffer,
  isFormData,
  isArrayBufferView,
  isString,
  isNumber,
  isBoolean,
  isObject,
  isPlainObject,
  isEmptyObject,
  isReadableStream,
  isRequest,
  isResponse,
  isHeaders,
  isUndefined,
  isDate,
  isFile,
  isBlob,
  isRegExp,
  isFunction: isFunction$1,
  isStream,
  isURLSearchParams,
  isTypedArray,
  isFileList,
  forEach,
  merge,
  extend,
  trim,
  stripBOM,
  inherits,
  toFlatObject,
  kindOf,
  kindOfTest,
  endsWith,
  toArray,
  forEachEntry,
  matchAll,
  isHTMLForm,
  hasOwnProperty,
  hasOwnProp: hasOwnProperty,
  // an alias to avoid ESLint no-prototype-builtins detection
  reduceDescriptors,
  freezeMethods,
  toObjectSet,
  toCamelCase,
  noop,
  toFiniteNumber,
  findKey,
  global: _global,
  isContextDefined,
  isSpecCompliantForm,
  toJSONObject,
  isAsyncFn,
  isThenable,
  setImmediate: _setImmediate,
  asap,
  isIterable
};
var buffer = {};
var base64Js = {};
base64Js.byteLength = byteLength;
base64Js.toByteArray = toByteArray;
base64Js.fromByteArray = fromByteArray;
var lookup = [];
var revLookup = [];
var Arr = typeof Uint8Array !== "undefined" ? Uint8Array : Array;
var code = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/";
for (var i = 0, len = code.length; i < len; ++i) {
  lookup[i] = code[i];
  revLookup[code.charCodeAt(i)] = i;
}
revLookup["-".charCodeAt(0)] = 62;
revLookup["_".charCodeAt(0)] = 63;
function getLens(b64) {
  var len = b64.length;
  if (len % 4 > 0) {
    throw new Error("Invalid string. Length must be a multiple of 4");
  }
  var validLen = b64.indexOf("=");
  if (validLen === -1) validLen = len;
  var placeHoldersLen = validLen === len ? 0 : 4 - validLen % 4;
  return [validLen, placeHoldersLen];
}
function byteLength(b64) {
  var lens = getLens(b64);
  var validLen = lens[0];
  var placeHoldersLen = lens[1];
  return (validLen + placeHoldersLen) * 3 / 4 - placeHoldersLen;
}
function _byteLength(b64, validLen, placeHoldersLen) {
  return (validLen + placeHoldersLen) * 3 / 4 - placeHoldersLen;
}
function toByteArray(b64) {
  var tmp;
  var lens = getLens(b64);
  var validLen = lens[0];
  var placeHoldersLen = lens[1];
  var arr = new Arr(_byteLength(b64, validLen, placeHoldersLen));
  var curByte = 0;
  var len = placeHoldersLen > 0 ? validLen - 4 : validLen;
  var i;
  for (i = 0; i < len; i += 4) {
    tmp = revLookup[b64.charCodeAt(i)] << 18 | revLookup[b64.charCodeAt(i + 1)] << 12 | revLookup[b64.charCodeAt(i + 2)] << 6 | revLookup[b64.charCodeAt(i + 3)];
    arr[curByte++] = tmp >> 16 & 255;
    arr[curByte++] = tmp >> 8 & 255;
    arr[curByte++] = tmp & 255;
  }
  if (placeHoldersLen === 2) {
    tmp = revLookup[b64.charCodeAt(i)] << 2 | revLookup[b64.charCodeAt(i + 1)] >> 4;
    arr[curByte++] = tmp & 255;
  }
  if (placeHoldersLen === 1) {
    tmp = revLookup[b64.charCodeAt(i)] << 10 | revLookup[b64.charCodeAt(i + 1)] << 4 | revLookup[b64.charCodeAt(i + 2)] >> 2;
    arr[curByte++] = tmp >> 8 & 255;
    arr[curByte++] = tmp & 255;
  }
  return arr;
}
function tripletToBase64(num) {
  return lookup[num >> 18 & 63] + lookup[num >> 12 & 63] + lookup[num >> 6 & 63] + lookup[num & 63];
}
function encodeChunk(uint8, start, end) {
  var tmp;
  var output = [];
  for (var i = start; i < end; i += 3) {
    tmp = (uint8[i] << 16 & 16711680) + (uint8[i + 1] << 8 & 65280) + (uint8[i + 2] & 255);
    output.push(tripletToBase64(tmp));
  }
  return output.join("");
}
function fromByteArray(uint8) {
  var tmp;
  var len = uint8.length;
  var extraBytes = len % 3;
  var parts = [];
  var maxChunkLength = 16383;
  for (var i = 0, len2 = len - extraBytes; i < len2; i += maxChunkLength) {
    parts.push(encodeChunk(uint8, i, i + maxChunkLength > len2 ? len2 : i + maxChunkLength));
  }
  if (extraBytes === 1) {
    tmp = uint8[len - 1];
    parts.push(
      lookup[tmp >> 2] + lookup[tmp << 4 & 63] + "=="
    );
  } else if (extraBytes === 2) {
    tmp = (uint8[len - 2] << 8) + uint8[len - 1];
    parts.push(
      lookup[tmp >> 10] + lookup[tmp >> 4 & 63] + lookup[tmp << 2 & 63] + "="
    );
  }
  return parts.join("");
}
var ieee754 = {};
/*! ieee754. BSD-3-Clause License. Feross Aboukhadijeh <https://feross.org/opensource> */
ieee754.read = function(buffer2, offset, isLE, mLen, nBytes) {
  var e, m;
  var eLen = nBytes * 8 - mLen - 1;
  var eMax = (1 << eLen) - 1;
  var eBias = eMax >> 1;
  var nBits = -7;
  var i = isLE ? nBytes - 1 : 0;
  var d = isLE ? -1 : 1;
  var s = buffer2[offset + i];
  i += d;
  e = s & (1 << -nBits) - 1;
  s >>= -nBits;
  nBits += eLen;
  for (; nBits > 0; e = e * 256 + buffer2[offset + i], i += d, nBits -= 8) {
  }
  m = e & (1 << -nBits) - 1;
  e >>= -nBits;
  nBits += mLen;
  for (; nBits > 0; m = m * 256 + buffer2[offset + i], i += d, nBits -= 8) {
  }
  if (e === 0) {
    e = 1 - eBias;
  } else if (e === eMax) {
    return m ? NaN : (s ? -1 : 1) * Infinity;
  } else {
    m = m + Math.pow(2, mLen);
    e = e - eBias;
  }
  return (s ? -1 : 1) * m * Math.pow(2, e - mLen);
};
ieee754.write = function(buffer2, value, offset, isLE, mLen, nBytes) {
  var e, m, c;
  var eLen = nBytes * 8 - mLen - 1;
  var eMax = (1 << eLen) - 1;
  var eBias = eMax >> 1;
  var rt = mLen === 23 ? Math.pow(2, -24) - Math.pow(2, -77) : 0;
  var i = isLE ? 0 : nBytes - 1;
  var d = isLE ? 1 : -1;
  var s = value < 0 || value === 0 && 1 / value < 0 ? 1 : 0;
  value = Math.abs(value);
  if (isNaN(value) || value === Infinity) {
    m = isNaN(value) ? 1 : 0;
    e = eMax;
  } else {
    e = Math.floor(Math.log(value) / Math.LN2);
    if (value * (c = Math.pow(2, -e)) < 1) {
      e--;
      c *= 2;
    }
    if (e + eBias >= 1) {
      value += rt / c;
    } else {
      value += rt * Math.pow(2, 1 - eBias);
    }
    if (value * c >= 2) {
      e++;
      c /= 2;
    }
    if (e + eBias >= eMax) {
      m = 0;
      e = eMax;
    } else if (e + eBias >= 1) {
      m = (value * c - 1) * Math.pow(2, mLen);
      e = e + eBias;
    } else {
      m = value * Math.pow(2, eBias - 1) * Math.pow(2, mLen);
      e = 0;
    }
  }
  for (; mLen >= 8; buffer2[offset + i] = m & 255, i += d, m /= 256, mLen -= 8) {
  }
  e = e << mLen | m;
  eLen += mLen;
  for (; eLen > 0; buffer2[offset + i] = e & 255, i += d, e /= 256, eLen -= 8) {
  }
  buffer2[offset + i - d] |= s * 128;
};
/*!
 * The buffer module from node.js, for the browser.
 *
 * @author   Feross Aboukhadijeh <https://feross.org>
 * @license  MIT
 */
(function(exports) {
  const base64 = base64Js;
  const ieee754$1 = ieee754;
  const customInspectSymbol = typeof Symbol === "function" && typeof Symbol["for"] === "function" ? Symbol["for"]("nodejs.util.inspect.custom") : null;
  exports.Buffer = Buffer2;
  exports.SlowBuffer = SlowBuffer;
  exports.INSPECT_MAX_BYTES = 50;
  const K_MAX_LENGTH = 2147483647;
  exports.kMaxLength = K_MAX_LENGTH;
  const { Uint8Array: GlobalUint8Array, ArrayBuffer: GlobalArrayBuffer, SharedArrayBuffer: GlobalSharedArrayBuffer } = globalThis;
  Buffer2.TYPED_ARRAY_SUPPORT = typedArraySupport();
  if (!Buffer2.TYPED_ARRAY_SUPPORT && typeof console !== "undefined" && typeof console.error === "function") {
    console.error(
      "This browser lacks typed array (Uint8Array) support which is required by `buffer` v5.x. Use `buffer` v4.x if you require old browser support."
    );
  }
  function typedArraySupport() {
    try {
      const arr = new GlobalUint8Array(1);
      const proto = { foo: function() {
        return 42;
      } };
      Object.setPrototypeOf(proto, GlobalUint8Array.prototype);
      Object.setPrototypeOf(arr, proto);
      return arr.foo() === 42;
    } catch (e) {
      return false;
    }
  }
  Object.defineProperty(Buffer2.prototype, "parent", {
    enumerable: true,
    get: function() {
      if (!Buffer2.isBuffer(this)) return void 0;
      return this.buffer;
    }
  });
  Object.defineProperty(Buffer2.prototype, "offset", {
    enumerable: true,
    get: function() {
      if (!Buffer2.isBuffer(this)) return void 0;
      return this.byteOffset;
    }
  });
  function createBuffer(length) {
    if (length > K_MAX_LENGTH) {
      throw new RangeError('The value "' + length + '" is invalid for option "size"');
    }
    const buf = new GlobalUint8Array(length);
    Object.setPrototypeOf(buf, Buffer2.prototype);
    return buf;
  }
  function Buffer2(arg, encodingOrOffset, length) {
    if (typeof arg === "number") {
      if (typeof encodingOrOffset === "string") {
        throw new TypeError(
          'The "string" argument must be of type string. Received type number'
        );
      }
      return allocUnsafe(arg);
    }
    return from(arg, encodingOrOffset, length);
  }
  Buffer2.poolSize = 8192;
  function from(value, encodingOrOffset, length) {
    if (typeof value === "string") {
      return fromString(value, encodingOrOffset);
    }
    if (GlobalArrayBuffer.isView(value)) {
      return fromArrayView(value);
    }
    if (value == null) {
      throw new TypeError(
        "The first argument must be one of type string, Buffer, ArrayBuffer, Array, or Array-like Object. Received type " + typeof value
      );
    }
    if (isInstance(value, GlobalArrayBuffer) || value && isInstance(value.buffer, GlobalArrayBuffer)) {
      return fromArrayBuffer(value, encodingOrOffset, length);
    }
    if (typeof GlobalSharedArrayBuffer !== "undefined" && (isInstance(value, GlobalSharedArrayBuffer) || value && isInstance(value.buffer, GlobalSharedArrayBuffer))) {
      return fromArrayBuffer(value, encodingOrOffset, length);
    }
    if (typeof value === "number") {
      throw new TypeError(
        'The "value" argument must not be of type number. Received type number'
      );
    }
    const valueOf = value.valueOf && value.valueOf();
    if (valueOf != null && valueOf !== value) {
      return Buffer2.from(valueOf, encodingOrOffset, length);
    }
    const b = fromObject(value);
    if (b) return b;
    if (typeof Symbol !== "undefined" && Symbol.toPrimitive != null && typeof value[Symbol.toPrimitive] === "function") {
      return Buffer2.from(value[Symbol.toPrimitive]("string"), encodingOrOffset, length);
    }
    throw new TypeError(
      "The first argument must be one of type string, Buffer, ArrayBuffer, Array, or Array-like Object. Received type " + typeof value
    );
  }
  Buffer2.from = function(value, encodingOrOffset, length) {
    return from(value, encodingOrOffset, length);
  };
  Object.setPrototypeOf(Buffer2.prototype, GlobalUint8Array.prototype);
  Object.setPrototypeOf(Buffer2, GlobalUint8Array);
  function assertSize(size) {
    if (typeof size !== "number") {
      throw new TypeError('"size" argument must be of type number');
    } else if (size < 0) {
      throw new RangeError('The value "' + size + '" is invalid for option "size"');
    }
  }
  function alloc(size, fill, encoding) {
    assertSize(size);
    if (size <= 0) {
      return createBuffer(size);
    }
    if (fill !== void 0) {
      return typeof encoding === "string" ? createBuffer(size).fill(fill, encoding) : createBuffer(size).fill(fill);
    }
    return createBuffer(size);
  }
  Buffer2.alloc = function(size, fill, encoding) {
    return alloc(size, fill, encoding);
  };
  function allocUnsafe(size) {
    assertSize(size);
    return createBuffer(size < 0 ? 0 : checked(size) | 0);
  }
  Buffer2.allocUnsafe = function(size) {
    return allocUnsafe(size);
  };
  Buffer2.allocUnsafeSlow = function(size) {
    return allocUnsafe(size);
  };
  function fromString(string, encoding) {
    if (typeof encoding !== "string" || encoding === "") {
      encoding = "utf8";
    }
    if (!Buffer2.isEncoding(encoding)) {
      throw new TypeError("Unknown encoding: " + encoding);
    }
    const length = byteLength2(string, encoding) | 0;
    let buf = createBuffer(length);
    const actual = buf.write(string, encoding);
    if (actual !== length) {
      buf = buf.slice(0, actual);
    }
    return buf;
  }
  function fromArrayLike(array) {
    const length = array.length < 0 ? 0 : checked(array.length) | 0;
    const buf = createBuffer(length);
    for (let i = 0; i < length; i += 1) {
      buf[i] = array[i] & 255;
    }
    return buf;
  }
  function fromArrayView(arrayView) {
    if (isInstance(arrayView, GlobalUint8Array)) {
      const copy = new GlobalUint8Array(arrayView);
      return fromArrayBuffer(copy.buffer, copy.byteOffset, copy.byteLength);
    }
    return fromArrayLike(arrayView);
  }
  function fromArrayBuffer(array, byteOffset, length) {
    if (byteOffset < 0 || array.byteLength < byteOffset) {
      throw new RangeError('"offset" is outside of buffer bounds');
    }
    if (array.byteLength < byteOffset + (length || 0)) {
      throw new RangeError('"length" is outside of buffer bounds');
    }
    let buf;
    if (byteOffset === void 0 && length === void 0) {
      buf = new GlobalUint8Array(array);
    } else if (length === void 0) {
      buf = new GlobalUint8Array(array, byteOffset);
    } else {
      buf = new GlobalUint8Array(array, byteOffset, length);
    }
    Object.setPrototypeOf(buf, Buffer2.prototype);
    return buf;
  }
  function fromObject(obj) {
    if (Buffer2.isBuffer(obj)) {
      const len = checked(obj.length) | 0;
      const buf = createBuffer(len);
      if (buf.length === 0) {
        return buf;
      }
      obj.copy(buf, 0, 0, len);
      return buf;
    }
    if (obj.length !== void 0) {
      if (typeof obj.length !== "number" || numberIsNaN(obj.length)) {
        return createBuffer(0);
      }
      return fromArrayLike(obj);
    }
    if (obj.type === "Buffer" && Array.isArray(obj.data)) {
      return fromArrayLike(obj.data);
    }
  }
  function checked(length) {
    if (length >= K_MAX_LENGTH) {
      throw new RangeError("Attempt to allocate Buffer larger than maximum size: 0x" + K_MAX_LENGTH.toString(16) + " bytes");
    }
    return length | 0;
  }
  function SlowBuffer(length) {
    if (+length != length) {
      length = 0;
    }
    return Buffer2.alloc(+length);
  }
  Buffer2.isBuffer = function isBuffer2(b) {
    return b != null && b._isBuffer === true && b !== Buffer2.prototype;
  };
  Buffer2.compare = function compare(a, b) {
    if (isInstance(a, GlobalUint8Array)) a = Buffer2.from(a, a.offset, a.byteLength);
    if (isInstance(b, GlobalUint8Array)) b = Buffer2.from(b, b.offset, b.byteLength);
    if (!Buffer2.isBuffer(a) || !Buffer2.isBuffer(b)) {
      throw new TypeError(
        'The "buf1", "buf2" arguments must be one of type Buffer or Uint8Array'
      );
    }
    if (a === b) return 0;
    let x = a.length;
    let y = b.length;
    for (let i = 0, len = Math.min(x, y); i < len; ++i) {
      if (a[i] !== b[i]) {
        x = a[i];
        y = b[i];
        break;
      }
    }
    if (x < y) return -1;
    if (y < x) return 1;
    return 0;
  };
  Buffer2.isEncoding = function isEncoding(encoding) {
    switch (String(encoding).toLowerCase()) {
      case "hex":
      case "utf8":
      case "utf-8":
      case "ascii":
      case "latin1":
      case "binary":
      case "base64":
      case "ucs2":
      case "ucs-2":
      case "utf16le":
      case "utf-16le":
        return true;
      default:
        return false;
    }
  };
  Buffer2.concat = function concat(list, length) {
    if (!Array.isArray(list)) {
      throw new TypeError('"list" argument must be an Array of Buffers');
    }
    if (list.length === 0) {
      return Buffer2.alloc(0);
    }
    let i;
    if (length === void 0) {
      length = 0;
      for (i = 0; i < list.length; ++i) {
        length += list[i].length;
      }
    }
    const buffer2 = Buffer2.allocUnsafe(length);
    let pos = 0;
    for (i = 0; i < list.length; ++i) {
      let buf = list[i];
      if (isInstance(buf, GlobalUint8Array)) {
        if (pos + buf.length > buffer2.length) {
          if (!Buffer2.isBuffer(buf)) buf = Buffer2.from(buf);
          buf.copy(buffer2, pos);
        } else {
          GlobalUint8Array.prototype.set.call(
            buffer2,
            buf,
            pos
          );
        }
      } else if (!Buffer2.isBuffer(buf)) {
        throw new TypeError('"list" argument must be an Array of Buffers');
      } else {
        buf.copy(buffer2, pos);
      }
      pos += buf.length;
    }
    return buffer2;
  };
  function byteLength2(string, encoding) {
    if (Buffer2.isBuffer(string)) {
      return string.length;
    }
    if (GlobalArrayBuffer.isView(string) || isInstance(string, GlobalArrayBuffer)) {
      return string.byteLength;
    }
    if (typeof string !== "string") {
      throw new TypeError(
        'The "string" argument must be one of type string, Buffer, or ArrayBuffer. Received type ' + typeof string
      );
    }
    const len = string.length;
    const mustMatch = arguments.length > 2 && arguments[2] === true;
    if (!mustMatch && len === 0) return 0;
    let loweredCase = false;
    for (; ; ) {
      switch (encoding) {
        case "ascii":
        case "latin1":
        case "binary":
          return len;
        case "utf8":
        case "utf-8":
          return utf8ToBytes(string).length;
        case "ucs2":
        case "ucs-2":
        case "utf16le":
        case "utf-16le":
          return len * 2;
        case "hex":
          return len >>> 1;
        case "base64":
          return base64ToBytes(string).length;
        default:
          if (loweredCase) {
            return mustMatch ? -1 : utf8ToBytes(string).length;
          }
          encoding = ("" + encoding).toLowerCase();
          loweredCase = true;
      }
    }
  }
  Buffer2.byteLength = byteLength2;
  function slowToString(encoding, start, end) {
    let loweredCase = false;
    if (start === void 0 || start < 0) {
      start = 0;
    }
    if (start > this.length) {
      return "";
    }
    if (end === void 0 || end > this.length) {
      end = this.length;
    }
    if (end <= 0) {
      return "";
    }
    end >>>= 0;
    start >>>= 0;
    if (end <= start) {
      return "";
    }
    if (!encoding) encoding = "utf8";
    while (true) {
      switch (encoding) {
        case "hex":
          return hexSlice(this, start, end);
        case "utf8":
        case "utf-8":
          return utf8Slice(this, start, end);
        case "ascii":
          return asciiSlice(this, start, end);
        case "latin1":
        case "binary":
          return latin1Slice(this, start, end);
        case "base64":
          return base64Slice(this, start, end);
        case "ucs2":
        case "ucs-2":
        case "utf16le":
        case "utf-16le":
          return utf16leSlice(this, start, end);
        default:
          if (loweredCase) throw new TypeError("Unknown encoding: " + encoding);
          encoding = (encoding + "").toLowerCase();
          loweredCase = true;
      }
    }
  }
  Buffer2.prototype._isBuffer = true;
  function swap(b, n2, m) {
    const i = b[n2];
    b[n2] = b[m];
    b[m] = i;
  }
  Buffer2.prototype.swap16 = function swap16() {
    const len = this.length;
    if (len % 2 !== 0) {
      throw new RangeError("Buffer size must be a multiple of 16-bits");
    }
    for (let i = 0; i < len; i += 2) {
      swap(this, i, i + 1);
    }
    return this;
  };
  Buffer2.prototype.swap32 = function swap32() {
    const len = this.length;
    if (len % 4 !== 0) {
      throw new RangeError("Buffer size must be a multiple of 32-bits");
    }
    for (let i = 0; i < len; i += 4) {
      swap(this, i, i + 3);
      swap(this, i + 1, i + 2);
    }
    return this;
  };
  Buffer2.prototype.swap64 = function swap64() {
    const len = this.length;
    if (len % 8 !== 0) {
      throw new RangeError("Buffer size must be a multiple of 64-bits");
    }
    for (let i = 0; i < len; i += 8) {
      swap(this, i, i + 7);
      swap(this, i + 1, i + 6);
      swap(this, i + 2, i + 5);
      swap(this, i + 3, i + 4);
    }
    return this;
  };
  Buffer2.prototype.toString = function toString3() {
    const length = this.length;
    if (length === 0) return "";
    if (arguments.length === 0) return utf8Slice(this, 0, length);
    return slowToString.apply(this, arguments);
  };
  Buffer2.prototype.toLocaleString = Buffer2.prototype.toString;
  Buffer2.prototype.equals = function equals(b) {
    if (!Buffer2.isBuffer(b)) throw new TypeError("Argument must be a Buffer");
    if (this === b) return true;
    return Buffer2.compare(this, b) === 0;
  };
  Buffer2.prototype.inspect = function inspect() {
    let str = "";
    const max = exports.INSPECT_MAX_BYTES;
    str = this.toString("hex", 0, max).replace(/(.{2})/g, "$1 ").trim();
    if (this.length > max) str += " ... ";
    return "<Buffer " + str + ">";
  };
  if (customInspectSymbol) {
    Buffer2.prototype[customInspectSymbol] = Buffer2.prototype.inspect;
  }
  Buffer2.prototype.compare = function compare(target, start, end, thisStart, thisEnd) {
    if (isInstance(target, GlobalUint8Array)) {
      target = Buffer2.from(target, target.offset, target.byteLength);
    }
    if (!Buffer2.isBuffer(target)) {
      throw new TypeError(
        'The "target" argument must be one of type Buffer or Uint8Array. Received type ' + typeof target
      );
    }
    if (start === void 0) {
      start = 0;
    }
    if (end === void 0) {
      end = target ? target.length : 0;
    }
    if (thisStart === void 0) {
      thisStart = 0;
    }
    if (thisEnd === void 0) {
      thisEnd = this.length;
    }
    if (start < 0 || end > target.length || thisStart < 0 || thisEnd > this.length) {
      throw new RangeError("out of range index");
    }
    if (thisStart >= thisEnd && start >= end) {
      return 0;
    }
    if (thisStart >= thisEnd) {
      return -1;
    }
    if (start >= end) {
      return 1;
    }
    start >>>= 0;
    end >>>= 0;
    thisStart >>>= 0;
    thisEnd >>>= 0;
    if (this === target) return 0;
    let x = thisEnd - thisStart;
    let y = end - start;
    const len = Math.min(x, y);
    const thisCopy = this.slice(thisStart, thisEnd);
    const targetCopy = target.slice(start, end);
    for (let i = 0; i < len; ++i) {
      if (thisCopy[i] !== targetCopy[i]) {
        x = thisCopy[i];
        y = targetCopy[i];
        break;
      }
    }
    if (x < y) return -1;
    if (y < x) return 1;
    return 0;
  };
  function bidirectionalIndexOf(buffer2, val, byteOffset, encoding, dir) {
    if (buffer2.length === 0) return -1;
    if (typeof byteOffset === "string") {
      encoding = byteOffset;
      byteOffset = 0;
    } else if (byteOffset > 2147483647) {
      byteOffset = 2147483647;
    } else if (byteOffset < -2147483648) {
      byteOffset = -2147483648;
    }
    byteOffset = +byteOffset;
    if (numberIsNaN(byteOffset)) {
      byteOffset = dir ? 0 : buffer2.length - 1;
    }
    if (byteOffset < 0) byteOffset = buffer2.length + byteOffset;
    if (byteOffset >= buffer2.length) {
      if (dir) return -1;
      else byteOffset = buffer2.length - 1;
    } else if (byteOffset < 0) {
      if (dir) byteOffset = 0;
      else return -1;
    }
    if (typeof val === "string") {
      val = Buffer2.from(val, encoding);
    }
    if (Buffer2.isBuffer(val)) {
      if (val.length === 0) {
        return -1;
      }
      return arrayIndexOf(buffer2, val, byteOffset, encoding, dir);
    } else if (typeof val === "number") {
      val = val & 255;
      if (typeof GlobalUint8Array.prototype.indexOf === "function") {
        if (dir) {
          return GlobalUint8Array.prototype.indexOf.call(buffer2, val, byteOffset);
        } else {
          return GlobalUint8Array.prototype.lastIndexOf.call(buffer2, val, byteOffset);
        }
      }
      return arrayIndexOf(buffer2, [val], byteOffset, encoding, dir);
    }
    throw new TypeError("val must be string, number or Buffer");
  }
  function arrayIndexOf(arr, val, byteOffset, encoding, dir) {
    let indexSize = 1;
    let arrLength = arr.length;
    let valLength = val.length;
    if (encoding !== void 0) {
      encoding = String(encoding).toLowerCase();
      if (encoding === "ucs2" || encoding === "ucs-2" || encoding === "utf16le" || encoding === "utf-16le") {
        if (arr.length < 2 || val.length < 2) {
          return -1;
        }
        indexSize = 2;
        arrLength /= 2;
        valLength /= 2;
        byteOffset /= 2;
      }
    }
    function read(buf, i2) {
      if (indexSize === 1) {
        return buf[i2];
      } else {
        return buf.readUInt16BE(i2 * indexSize);
      }
    }
    let i;
    if (dir) {
      let foundIndex = -1;
      for (i = byteOffset; i < arrLength; i++) {
        if (read(arr, i) === read(val, foundIndex === -1 ? 0 : i - foundIndex)) {
          if (foundIndex === -1) foundIndex = i;
          if (i - foundIndex + 1 === valLength) return foundIndex * indexSize;
        } else {
          if (foundIndex !== -1) i -= i - foundIndex;
          foundIndex = -1;
        }
      }
    } else {
      if (byteOffset + valLength > arrLength) byteOffset = arrLength - valLength;
      for (i = byteOffset; i >= 0; i--) {
        let found = true;
        for (let j = 0; j < valLength; j++) {
          if (read(arr, i + j) !== read(val, j)) {
            found = false;
            break;
          }
        }
        if (found) return i;
      }
    }
    return -1;
  }
  Buffer2.prototype.includes = function includes(val, byteOffset, encoding) {
    return this.indexOf(val, byteOffset, encoding) !== -1;
  };
  Buffer2.prototype.indexOf = function indexOf(val, byteOffset, encoding) {
    return bidirectionalIndexOf(this, val, byteOffset, encoding, true);
  };
  Buffer2.prototype.lastIndexOf = function lastIndexOf(val, byteOffset, encoding) {
    return bidirectionalIndexOf(this, val, byteOffset, encoding, false);
  };
  function hexWrite(buf, string, offset, length) {
    offset = Number(offset) || 0;
    const remaining = buf.length - offset;
    if (!length) {
      length = remaining;
    } else {
      length = Number(length);
      if (length > remaining) {
        length = remaining;
      }
    }
    const strLen = string.length;
    if (length > strLen / 2) {
      length = strLen / 2;
    }
    let i;
    for (i = 0; i < length; ++i) {
      const parsed = parseInt(string.substr(i * 2, 2), 16);
      if (numberIsNaN(parsed)) return i;
      buf[offset + i] = parsed;
    }
    return i;
  }
  function utf8Write(buf, string, offset, length) {
    return blitBuffer(utf8ToBytes(string, buf.length - offset), buf, offset, length);
  }
  function asciiWrite(buf, string, offset, length) {
    return blitBuffer(asciiToBytes(string), buf, offset, length);
  }
  function base64Write(buf, string, offset, length) {
    return blitBuffer(base64ToBytes(string), buf, offset, length);
  }
  function ucs2Write(buf, string, offset, length) {
    return blitBuffer(utf16leToBytes(string, buf.length - offset), buf, offset, length);
  }
  Buffer2.prototype.write = function write(string, offset, length, encoding) {
    if (offset === void 0) {
      encoding = "utf8";
      length = this.length;
      offset = 0;
    } else if (length === void 0 && typeof offset === "string") {
      encoding = offset;
      length = this.length;
      offset = 0;
    } else if (isFinite(offset)) {
      offset = offset >>> 0;
      if (isFinite(length)) {
        length = length >>> 0;
        if (encoding === void 0) encoding = "utf8";
      } else {
        encoding = length;
        length = void 0;
      }
    } else {
      throw new Error(
        "Buffer.write(string, encoding, offset[, length]) is no longer supported"
      );
    }
    const remaining = this.length - offset;
    if (length === void 0 || length > remaining) length = remaining;
    if (string.length > 0 && (length < 0 || offset < 0) || offset > this.length) {
      throw new RangeError("Attempt to write outside buffer bounds");
    }
    if (!encoding) encoding = "utf8";
    let loweredCase = false;
    for (; ; ) {
      switch (encoding) {
        case "hex":
          return hexWrite(this, string, offset, length);
        case "utf8":
        case "utf-8":
          return utf8Write(this, string, offset, length);
        case "ascii":
        case "latin1":
        case "binary":
          return asciiWrite(this, string, offset, length);
        case "base64":
          return base64Write(this, string, offset, length);
        case "ucs2":
        case "ucs-2":
        case "utf16le":
        case "utf-16le":
          return ucs2Write(this, string, offset, length);
        default:
          if (loweredCase) throw new TypeError("Unknown encoding: " + encoding);
          encoding = ("" + encoding).toLowerCase();
          loweredCase = true;
      }
    }
  };
  Buffer2.prototype.toJSON = function toJSON2() {
    return {
      type: "Buffer",
      data: Array.prototype.slice.call(this._arr || this, 0)
    };
  };
  function base64Slice(buf, start, end) {
    if (start === 0 && end === buf.length) {
      return base64.fromByteArray(buf);
    } else {
      return base64.fromByteArray(buf.slice(start, end));
    }
  }
  function utf8Slice(buf, start, end) {
    end = Math.min(buf.length, end);
    const res = [];
    let i = start;
    while (i < end) {
      const firstByte = buf[i];
      let codePoint = null;
      let bytesPerSequence = firstByte > 239 ? 4 : firstByte > 223 ? 3 : firstByte > 191 ? 2 : 1;
      if (i + bytesPerSequence <= end) {
        let secondByte, thirdByte, fourthByte, tempCodePoint;
        switch (bytesPerSequence) {
          case 1:
            if (firstByte < 128) {
              codePoint = firstByte;
            }
            break;
          case 2:
            secondByte = buf[i + 1];
            if ((secondByte & 192) === 128) {
              tempCodePoint = (firstByte & 31) << 6 | secondByte & 63;
              if (tempCodePoint > 127) {
                codePoint = tempCodePoint;
              }
            }
            break;
          case 3:
            secondByte = buf[i + 1];
            thirdByte = buf[i + 2];
            if ((secondByte & 192) === 128 && (thirdByte & 192) === 128) {
              tempCodePoint = (firstByte & 15) << 12 | (secondByte & 63) << 6 | thirdByte & 63;
              if (tempCodePoint > 2047 && (tempCodePoint < 55296 || tempCodePoint > 57343)) {
                codePoint = tempCodePoint;
              }
            }
            break;
          case 4:
            secondByte = buf[i + 1];
            thirdByte = buf[i + 2];
            fourthByte = buf[i + 3];
            if ((secondByte & 192) === 128 && (thirdByte & 192) === 128 && (fourthByte & 192) === 128) {
              tempCodePoint = (firstByte & 15) << 18 | (secondByte & 63) << 12 | (thirdByte & 63) << 6 | fourthByte & 63;
              if (tempCodePoint > 65535 && tempCodePoint < 1114112) {
                codePoint = tempCodePoint;
              }
            }
        }
      }
      if (codePoint === null) {
        codePoint = 65533;
        bytesPerSequence = 1;
      } else if (codePoint > 65535) {
        codePoint -= 65536;
        res.push(codePoint >>> 10 & 1023 | 55296);
        codePoint = 56320 | codePoint & 1023;
      }
      res.push(codePoint);
      i += bytesPerSequence;
    }
    return decodeCodePointsArray(res);
  }
  const MAX_ARGUMENTS_LENGTH = 4096;
  function decodeCodePointsArray(codePoints) {
    const len = codePoints.length;
    if (len <= MAX_ARGUMENTS_LENGTH) {
      return String.fromCharCode.apply(String, codePoints);
    }
    let res = "";
    let i = 0;
    while (i < len) {
      res += String.fromCharCode.apply(
        String,
        codePoints.slice(i, i += MAX_ARGUMENTS_LENGTH)
      );
    }
    return res;
  }
  function asciiSlice(buf, start, end) {
    let ret = "";
    end = Math.min(buf.length, end);
    for (let i = start; i < end; ++i) {
      ret += String.fromCharCode(buf[i] & 127);
    }
    return ret;
  }
  function latin1Slice(buf, start, end) {
    let ret = "";
    end = Math.min(buf.length, end);
    for (let i = start; i < end; ++i) {
      ret += String.fromCharCode(buf[i]);
    }
    return ret;
  }
  function hexSlice(buf, start, end) {
    const len = buf.length;
    if (!start || start < 0) start = 0;
    if (!end || end < 0 || end > len) end = len;
    let out = "";
    for (let i = start; i < end; ++i) {
      out += hexSliceLookupTable[buf[i]];
    }
    return out;
  }
  function utf16leSlice(buf, start, end) {
    const bytes = buf.slice(start, end);
    let res = "";
    for (let i = 0; i < bytes.length - 1; i += 2) {
      res += String.fromCharCode(bytes[i] + bytes[i + 1] * 256);
    }
    return res;
  }
  Buffer2.prototype.slice = function slice(start, end) {
    const len = this.length;
    start = ~~start;
    end = end === void 0 ? len : ~~end;
    if (start < 0) {
      start += len;
      if (start < 0) start = 0;
    } else if (start > len) {
      start = len;
    }
    if (end < 0) {
      end += len;
      if (end < 0) end = 0;
    } else if (end > len) {
      end = len;
    }
    if (end < start) end = start;
    const newBuf = this.subarray(start, end);
    Object.setPrototypeOf(newBuf, Buffer2.prototype);
    return newBuf;
  };
  function checkOffset(offset, ext, length) {
    if (offset % 1 !== 0 || offset < 0) throw new RangeError("offset is not uint");
    if (offset + ext > length) throw new RangeError("Trying to access beyond buffer length");
  }
  Buffer2.prototype.readUintLE = Buffer2.prototype.readUIntLE = function readUIntLE(offset, byteLength3, noAssert) {
    offset = offset >>> 0;
    byteLength3 = byteLength3 >>> 0;
    if (!noAssert) checkOffset(offset, byteLength3, this.length);
    let val = this[offset];
    let mul = 1;
    let i = 0;
    while (++i < byteLength3 && (mul *= 256)) {
      val += this[offset + i] * mul;
    }
    return val;
  };
  Buffer2.prototype.readUintBE = Buffer2.prototype.readUIntBE = function readUIntBE(offset, byteLength3, noAssert) {
    offset = offset >>> 0;
    byteLength3 = byteLength3 >>> 0;
    if (!noAssert) {
      checkOffset(offset, byteLength3, this.length);
    }
    let val = this[offset + --byteLength3];
    let mul = 1;
    while (byteLength3 > 0 && (mul *= 256)) {
      val += this[offset + --byteLength3] * mul;
    }
    return val;
  };
  Buffer2.prototype.readUint8 = Buffer2.prototype.readUInt8 = function readUInt8(offset, noAssert) {
    offset = offset >>> 0;
    if (!noAssert) checkOffset(offset, 1, this.length);
    return this[offset];
  };
  Buffer2.prototype.readUint16LE = Buffer2.prototype.readUInt16LE = function readUInt16LE(offset, noAssert) {
    offset = offset >>> 0;
    if (!noAssert) checkOffset(offset, 2, this.length);
    return this[offset] | this[offset + 1] << 8;
  };
  Buffer2.prototype.readUint16BE = Buffer2.prototype.readUInt16BE = function readUInt16BE(offset, noAssert) {
    offset = offset >>> 0;
    if (!noAssert) checkOffset(offset, 2, this.length);
    return this[offset] << 8 | this[offset + 1];
  };
  Buffer2.prototype.readUint32LE = Buffer2.prototype.readUInt32LE = function readUInt32LE(offset, noAssert) {
    offset = offset >>> 0;
    if (!noAssert) checkOffset(offset, 4, this.length);
    return (this[offset] | this[offset + 1] << 8 | this[offset + 2] << 16) + this[offset + 3] * 16777216;
  };
  Buffer2.prototype.readUint32BE = Buffer2.prototype.readUInt32BE = function readUInt32BE(offset, noAssert) {
    offset = offset >>> 0;
    if (!noAssert) checkOffset(offset, 4, this.length);
    return this[offset] * 16777216 + (this[offset + 1] << 16 | this[offset + 2] << 8 | this[offset + 3]);
  };
  Buffer2.prototype.readBigUInt64LE = defineBigIntMethod(function readBigUInt64LE(offset) {
    offset = offset >>> 0;
    validateNumber(offset, "offset");
    const first = this[offset];
    const last = this[offset + 7];
    if (first === void 0 || last === void 0) {
      boundsError(offset, this.length - 8);
    }
    const lo = first + this[++offset] * 2 ** 8 + this[++offset] * 2 ** 16 + this[++offset] * 2 ** 24;
    const hi = this[++offset] + this[++offset] * 2 ** 8 + this[++offset] * 2 ** 16 + last * 2 ** 24;
    return BigInt(lo) + (BigInt(hi) << BigInt(32));
  });
  Buffer2.prototype.readBigUInt64BE = defineBigIntMethod(function readBigUInt64BE(offset) {
    offset = offset >>> 0;
    validateNumber(offset, "offset");
    const first = this[offset];
    const last = this[offset + 7];
    if (first === void 0 || last === void 0) {
      boundsError(offset, this.length - 8);
    }
    const hi = first * 2 ** 24 + this[++offset] * 2 ** 16 + this[++offset] * 2 ** 8 + this[++offset];
    const lo = this[++offset] * 2 ** 24 + this[++offset] * 2 ** 16 + this[++offset] * 2 ** 8 + last;
    return (BigInt(hi) << BigInt(32)) + BigInt(lo);
  });
  Buffer2.prototype.readIntLE = function readIntLE(offset, byteLength3, noAssert) {
    offset = offset >>> 0;
    byteLength3 = byteLength3 >>> 0;
    if (!noAssert) checkOffset(offset, byteLength3, this.length);
    let val = this[offset];
    let mul = 1;
    let i = 0;
    while (++i < byteLength3 && (mul *= 256)) {
      val += this[offset + i] * mul;
    }
    mul *= 128;
    if (val >= mul) val -= Math.pow(2, 8 * byteLength3);
    return val;
  };
  Buffer2.prototype.readIntBE = function readIntBE(offset, byteLength3, noAssert) {
    offset = offset >>> 0;
    byteLength3 = byteLength3 >>> 0;
    if (!noAssert) checkOffset(offset, byteLength3, this.length);
    let i = byteLength3;
    let mul = 1;
    let val = this[offset + --i];
    while (i > 0 && (mul *= 256)) {
      val += this[offset + --i] * mul;
    }
    mul *= 128;
    if (val >= mul) val -= Math.pow(2, 8 * byteLength3);
    return val;
  };
  Buffer2.prototype.readInt8 = function readInt8(offset, noAssert) {
    offset = offset >>> 0;
    if (!noAssert) checkOffset(offset, 1, this.length);
    if (!(this[offset] & 128)) return this[offset];
    return (255 - this[offset] + 1) * -1;
  };
  Buffer2.prototype.readInt16LE = function readInt16LE(offset, noAssert) {
    offset = offset >>> 0;
    if (!noAssert) checkOffset(offset, 2, this.length);
    const val = this[offset] | this[offset + 1] << 8;
    return val & 32768 ? val | 4294901760 : val;
  };
  Buffer2.prototype.readInt16BE = function readInt16BE(offset, noAssert) {
    offset = offset >>> 0;
    if (!noAssert) checkOffset(offset, 2, this.length);
    const val = this[offset + 1] | this[offset] << 8;
    return val & 32768 ? val | 4294901760 : val;
  };
  Buffer2.prototype.readInt32LE = function readInt32LE(offset, noAssert) {
    offset = offset >>> 0;
    if (!noAssert) checkOffset(offset, 4, this.length);
    return this[offset] | this[offset + 1] << 8 | this[offset + 2] << 16 | this[offset + 3] << 24;
  };
  Buffer2.prototype.readInt32BE = function readInt32BE(offset, noAssert) {
    offset = offset >>> 0;
    if (!noAssert) checkOffset(offset, 4, this.length);
    return this[offset] << 24 | this[offset + 1] << 16 | this[offset + 2] << 8 | this[offset + 3];
  };
  Buffer2.prototype.readBigInt64LE = defineBigIntMethod(function readBigInt64LE(offset) {
    offset = offset >>> 0;
    validateNumber(offset, "offset");
    const first = this[offset];
    const last = this[offset + 7];
    if (first === void 0 || last === void 0) {
      boundsError(offset, this.length - 8);
    }
    const val = this[offset + 4] + this[offset + 5] * 2 ** 8 + this[offset + 6] * 2 ** 16 + (last << 24);
    return (BigInt(val) << BigInt(32)) + BigInt(first + this[++offset] * 2 ** 8 + this[++offset] * 2 ** 16 + this[++offset] * 2 ** 24);
  });
  Buffer2.prototype.readBigInt64BE = defineBigIntMethod(function readBigInt64BE(offset) {
    offset = offset >>> 0;
    validateNumber(offset, "offset");
    const first = this[offset];
    const last = this[offset + 7];
    if (first === void 0 || last === void 0) {
      boundsError(offset, this.length - 8);
    }
    const val = (first << 24) + // Overflow
    this[++offset] * 2 ** 16 + this[++offset] * 2 ** 8 + this[++offset];
    return (BigInt(val) << BigInt(32)) + BigInt(this[++offset] * 2 ** 24 + this[++offset] * 2 ** 16 + this[++offset] * 2 ** 8 + last);
  });
  Buffer2.prototype.readFloatLE = function readFloatLE(offset, noAssert) {
    offset = offset >>> 0;
    if (!noAssert) checkOffset(offset, 4, this.length);
    return ieee754$1.read(this, offset, true, 23, 4);
  };
  Buffer2.prototype.readFloatBE = function readFloatBE(offset, noAssert) {
    offset = offset >>> 0;
    if (!noAssert) checkOffset(offset, 4, this.length);
    return ieee754$1.read(this, offset, false, 23, 4);
  };
  Buffer2.prototype.readDoubleLE = function readDoubleLE(offset, noAssert) {
    offset = offset >>> 0;
    if (!noAssert) checkOffset(offset, 8, this.length);
    return ieee754$1.read(this, offset, true, 52, 8);
  };
  Buffer2.prototype.readDoubleBE = function readDoubleBE(offset, noAssert) {
    offset = offset >>> 0;
    if (!noAssert) checkOffset(offset, 8, this.length);
    return ieee754$1.read(this, offset, false, 52, 8);
  };
  function checkInt(buf, value, offset, ext, max, min) {
    if (!Buffer2.isBuffer(buf)) throw new TypeError('"buffer" argument must be a Buffer instance');
    if (value > max || value < min) throw new RangeError('"value" argument is out of bounds');
    if (offset + ext > buf.length) throw new RangeError("Index out of range");
  }
  Buffer2.prototype.writeUintLE = Buffer2.prototype.writeUIntLE = function writeUIntLE(value, offset, byteLength3, noAssert) {
    value = +value;
    offset = offset >>> 0;
    byteLength3 = byteLength3 >>> 0;
    if (!noAssert) {
      const maxBytes = Math.pow(2, 8 * byteLength3) - 1;
      checkInt(this, value, offset, byteLength3, maxBytes, 0);
    }
    let mul = 1;
    let i = 0;
    this[offset] = value & 255;
    while (++i < byteLength3 && (mul *= 256)) {
      this[offset + i] = value / mul & 255;
    }
    return offset + byteLength3;
  };
  Buffer2.prototype.writeUintBE = Buffer2.prototype.writeUIntBE = function writeUIntBE(value, offset, byteLength3, noAssert) {
    value = +value;
    offset = offset >>> 0;
    byteLength3 = byteLength3 >>> 0;
    if (!noAssert) {
      const maxBytes = Math.pow(2, 8 * byteLength3) - 1;
      checkInt(this, value, offset, byteLength3, maxBytes, 0);
    }
    let i = byteLength3 - 1;
    let mul = 1;
    this[offset + i] = value & 255;
    while (--i >= 0 && (mul *= 256)) {
      this[offset + i] = value / mul & 255;
    }
    return offset + byteLength3;
  };
  Buffer2.prototype.writeUint8 = Buffer2.prototype.writeUInt8 = function writeUInt8(value, offset, noAssert) {
    value = +value;
    offset = offset >>> 0;
    if (!noAssert) checkInt(this, value, offset, 1, 255, 0);
    this[offset] = value & 255;
    return offset + 1;
  };
  Buffer2.prototype.writeUint16LE = Buffer2.prototype.writeUInt16LE = function writeUInt16LE(value, offset, noAssert) {
    value = +value;
    offset = offset >>> 0;
    if (!noAssert) checkInt(this, value, offset, 2, 65535, 0);
    this[offset] = value & 255;
    this[offset + 1] = value >>> 8;
    return offset + 2;
  };
  Buffer2.prototype.writeUint16BE = Buffer2.prototype.writeUInt16BE = function writeUInt16BE(value, offset, noAssert) {
    value = +value;
    offset = offset >>> 0;
    if (!noAssert) checkInt(this, value, offset, 2, 65535, 0);
    this[offset] = value >>> 8;
    this[offset + 1] = value & 255;
    return offset + 2;
  };
  Buffer2.prototype.writeUint32LE = Buffer2.prototype.writeUInt32LE = function writeUInt32LE(value, offset, noAssert) {
    value = +value;
    offset = offset >>> 0;
    if (!noAssert) checkInt(this, value, offset, 4, 4294967295, 0);
    this[offset + 3] = value >>> 24;
    this[offset + 2] = value >>> 16;
    this[offset + 1] = value >>> 8;
    this[offset] = value & 255;
    return offset + 4;
  };
  Buffer2.prototype.writeUint32BE = Buffer2.prototype.writeUInt32BE = function writeUInt32BE(value, offset, noAssert) {
    value = +value;
    offset = offset >>> 0;
    if (!noAssert) checkInt(this, value, offset, 4, 4294967295, 0);
    this[offset] = value >>> 24;
    this[offset + 1] = value >>> 16;
    this[offset + 2] = value >>> 8;
    this[offset + 3] = value & 255;
    return offset + 4;
  };
  function wrtBigUInt64LE(buf, value, offset, min, max) {
    checkIntBI(value, min, max, buf, offset, 7);
    let lo = Number(value & BigInt(4294967295));
    buf[offset++] = lo;
    lo = lo >> 8;
    buf[offset++] = lo;
    lo = lo >> 8;
    buf[offset++] = lo;
    lo = lo >> 8;
    buf[offset++] = lo;
    let hi = Number(value >> BigInt(32) & BigInt(4294967295));
    buf[offset++] = hi;
    hi = hi >> 8;
    buf[offset++] = hi;
    hi = hi >> 8;
    buf[offset++] = hi;
    hi = hi >> 8;
    buf[offset++] = hi;
    return offset;
  }
  function wrtBigUInt64BE(buf, value, offset, min, max) {
    checkIntBI(value, min, max, buf, offset, 7);
    let lo = Number(value & BigInt(4294967295));
    buf[offset + 7] = lo;
    lo = lo >> 8;
    buf[offset + 6] = lo;
    lo = lo >> 8;
    buf[offset + 5] = lo;
    lo = lo >> 8;
    buf[offset + 4] = lo;
    let hi = Number(value >> BigInt(32) & BigInt(4294967295));
    buf[offset + 3] = hi;
    hi = hi >> 8;
    buf[offset + 2] = hi;
    hi = hi >> 8;
    buf[offset + 1] = hi;
    hi = hi >> 8;
    buf[offset] = hi;
    return offset + 8;
  }
  Buffer2.prototype.writeBigUInt64LE = defineBigIntMethod(function writeBigUInt64LE(value, offset = 0) {
    return wrtBigUInt64LE(this, value, offset, BigInt(0), BigInt("0xffffffffffffffff"));
  });
  Buffer2.prototype.writeBigUInt64BE = defineBigIntMethod(function writeBigUInt64BE(value, offset = 0) {
    return wrtBigUInt64BE(this, value, offset, BigInt(0), BigInt("0xffffffffffffffff"));
  });
  Buffer2.prototype.writeIntLE = function writeIntLE(value, offset, byteLength3, noAssert) {
    value = +value;
    offset = offset >>> 0;
    if (!noAssert) {
      const limit = Math.pow(2, 8 * byteLength3 - 1);
      checkInt(this, value, offset, byteLength3, limit - 1, -limit);
    }
    let i = 0;
    let mul = 1;
    let sub = 0;
    this[offset] = value & 255;
    while (++i < byteLength3 && (mul *= 256)) {
      if (value < 0 && sub === 0 && this[offset + i - 1] !== 0) {
        sub = 1;
      }
      this[offset + i] = (value / mul >> 0) - sub & 255;
    }
    return offset + byteLength3;
  };
  Buffer2.prototype.writeIntBE = function writeIntBE(value, offset, byteLength3, noAssert) {
    value = +value;
    offset = offset >>> 0;
    if (!noAssert) {
      const limit = Math.pow(2, 8 * byteLength3 - 1);
      checkInt(this, value, offset, byteLength3, limit - 1, -limit);
    }
    let i = byteLength3 - 1;
    let mul = 1;
    let sub = 0;
    this[offset + i] = value & 255;
    while (--i >= 0 && (mul *= 256)) {
      if (value < 0 && sub === 0 && this[offset + i + 1] !== 0) {
        sub = 1;
      }
      this[offset + i] = (value / mul >> 0) - sub & 255;
    }
    return offset + byteLength3;
  };
  Buffer2.prototype.writeInt8 = function writeInt8(value, offset, noAssert) {
    value = +value;
    offset = offset >>> 0;
    if (!noAssert) checkInt(this, value, offset, 1, 127, -128);
    if (value < 0) value = 255 + value + 1;
    this[offset] = value & 255;
    return offset + 1;
  };
  Buffer2.prototype.writeInt16LE = function writeInt16LE(value, offset, noAssert) {
    value = +value;
    offset = offset >>> 0;
    if (!noAssert) checkInt(this, value, offset, 2, 32767, -32768);
    this[offset] = value & 255;
    this[offset + 1] = value >>> 8;
    return offset + 2;
  };
  Buffer2.prototype.writeInt16BE = function writeInt16BE(value, offset, noAssert) {
    value = +value;
    offset = offset >>> 0;
    if (!noAssert) checkInt(this, value, offset, 2, 32767, -32768);
    this[offset] = value >>> 8;
    this[offset + 1] = value & 255;
    return offset + 2;
  };
  Buffer2.prototype.writeInt32LE = function writeInt32LE(value, offset, noAssert) {
    value = +value;
    offset = offset >>> 0;
    if (!noAssert) checkInt(this, value, offset, 4, 2147483647, -2147483648);
    this[offset] = value & 255;
    this[offset + 1] = value >>> 8;
    this[offset + 2] = value >>> 16;
    this[offset + 3] = value >>> 24;
    return offset + 4;
  };
  Buffer2.prototype.writeInt32BE = function writeInt32BE(value, offset, noAssert) {
    value = +value;
    offset = offset >>> 0;
    if (!noAssert) checkInt(this, value, offset, 4, 2147483647, -2147483648);
    if (value < 0) value = 4294967295 + value + 1;
    this[offset] = value >>> 24;
    this[offset + 1] = value >>> 16;
    this[offset + 2] = value >>> 8;
    this[offset + 3] = value & 255;
    return offset + 4;
  };
  Buffer2.prototype.writeBigInt64LE = defineBigIntMethod(function writeBigInt64LE(value, offset = 0) {
    return wrtBigUInt64LE(this, value, offset, -BigInt("0x8000000000000000"), BigInt("0x7fffffffffffffff"));
  });
  Buffer2.prototype.writeBigInt64BE = defineBigIntMethod(function writeBigInt64BE(value, offset = 0) {
    return wrtBigUInt64BE(this, value, offset, -BigInt("0x8000000000000000"), BigInt("0x7fffffffffffffff"));
  });
  function checkIEEE754(buf, value, offset, ext, max, min) {
    if (offset + ext > buf.length) throw new RangeError("Index out of range");
    if (offset < 0) throw new RangeError("Index out of range");
  }
  function writeFloat(buf, value, offset, littleEndian, noAssert) {
    value = +value;
    offset = offset >>> 0;
    if (!noAssert) {
      checkIEEE754(buf, value, offset, 4);
    }
    ieee754$1.write(buf, value, offset, littleEndian, 23, 4);
    return offset + 4;
  }
  Buffer2.prototype.writeFloatLE = function writeFloatLE(value, offset, noAssert) {
    return writeFloat(this, value, offset, true, noAssert);
  };
  Buffer2.prototype.writeFloatBE = function writeFloatBE(value, offset, noAssert) {
    return writeFloat(this, value, offset, false, noAssert);
  };
  function writeDouble(buf, value, offset, littleEndian, noAssert) {
    value = +value;
    offset = offset >>> 0;
    if (!noAssert) {
      checkIEEE754(buf, value, offset, 8);
    }
    ieee754$1.write(buf, value, offset, littleEndian, 52, 8);
    return offset + 8;
  }
  Buffer2.prototype.writeDoubleLE = function writeDoubleLE(value, offset, noAssert) {
    return writeDouble(this, value, offset, true, noAssert);
  };
  Buffer2.prototype.writeDoubleBE = function writeDoubleBE(value, offset, noAssert) {
    return writeDouble(this, value, offset, false, noAssert);
  };
  Buffer2.prototype.copy = function copy(target, targetStart, start, end) {
    if (!Buffer2.isBuffer(target)) throw new TypeError("argument should be a Buffer");
    if (!start) start = 0;
    if (!end && end !== 0) end = this.length;
    if (targetStart >= target.length) targetStart = target.length;
    if (!targetStart) targetStart = 0;
    if (end > 0 && end < start) end = start;
    if (end === start) return 0;
    if (target.length === 0 || this.length === 0) return 0;
    if (targetStart < 0) {
      throw new RangeError("targetStart out of bounds");
    }
    if (start < 0 || start >= this.length) throw new RangeError("Index out of range");
    if (end < 0) throw new RangeError("sourceEnd out of bounds");
    if (end > this.length) end = this.length;
    if (target.length - targetStart < end - start) {
      end = target.length - targetStart + start;
    }
    const len = end - start;
    if (this === target && typeof GlobalUint8Array.prototype.copyWithin === "function") {
      this.copyWithin(targetStart, start, end);
    } else {
      GlobalUint8Array.prototype.set.call(
        target,
        this.subarray(start, end),
        targetStart
      );
    }
    return len;
  };
  Buffer2.prototype.fill = function fill(val, start, end, encoding) {
    if (typeof val === "string") {
      if (typeof start === "string") {
        encoding = start;
        start = 0;
        end = this.length;
      } else if (typeof end === "string") {
        encoding = end;
        end = this.length;
      }
      if (encoding !== void 0 && typeof encoding !== "string") {
        throw new TypeError("encoding must be a string");
      }
      if (typeof encoding === "string" && !Buffer2.isEncoding(encoding)) {
        throw new TypeError("Unknown encoding: " + encoding);
      }
      if (val.length === 1) {
        const code2 = val.charCodeAt(0);
        if (encoding === "utf8" && code2 < 128 || encoding === "latin1") {
          val = code2;
        }
      }
    } else if (typeof val === "number") {
      val = val & 255;
    } else if (typeof val === "boolean") {
      val = Number(val);
    }
    if (start < 0 || this.length < start || this.length < end) {
      throw new RangeError("Out of range index");
    }
    if (end <= start) {
      return this;
    }
    start = start >>> 0;
    end = end === void 0 ? this.length : end >>> 0;
    if (!val) val = 0;
    let i;
    if (typeof val === "number") {
      for (i = start; i < end; ++i) {
        this[i] = val;
      }
    } else {
      const bytes = Buffer2.isBuffer(val) ? val : Buffer2.from(val, encoding);
      const len = bytes.length;
      if (len === 0) {
        throw new TypeError('The value "' + val + '" is invalid for argument "value"');
      }
      for (i = 0; i < end - start; ++i) {
        this[i + start] = bytes[i % len];
      }
    }
    return this;
  };
  const errors = {};
  function E(sym, getMessage, Base) {
    errors[sym] = class NodeError extends Base {
      constructor() {
        super();
        Object.defineProperty(this, "message", {
          value: getMessage.apply(this, arguments),
          writable: true,
          configurable: true
        });
        this.name = `${this.name} [${sym}]`;
        this.stack;
        delete this.name;
      }
      get code() {
        return sym;
      }
      set code(value) {
        Object.defineProperty(this, "code", {
          configurable: true,
          enumerable: true,
          value,
          writable: true
        });
      }
      toString() {
        return `${this.name} [${sym}]: ${this.message}`;
      }
    };
  }
  E(
    "ERR_BUFFER_OUT_OF_BOUNDS",
    function(name) {
      if (name) {
        return `${name} is outside of buffer bounds`;
      }
      return "Attempt to access memory outside buffer bounds";
    },
    RangeError
  );
  E(
    "ERR_INVALID_ARG_TYPE",
    function(name, actual) {
      return `The "${name}" argument must be of type number. Received type ${typeof actual}`;
    },
    TypeError
  );
  E(
    "ERR_OUT_OF_RANGE",
    function(str, range, input) {
      let msg = `The value of "${str}" is out of range.`;
      let received = input;
      if (Number.isInteger(input) && Math.abs(input) > 2 ** 32) {
        received = addNumericalSeparator(String(input));
      } else if (typeof input === "bigint") {
        received = String(input);
        if (input > BigInt(2) ** BigInt(32) || input < -(BigInt(2) ** BigInt(32))) {
          received = addNumericalSeparator(received);
        }
        received += "n";
      }
      msg += ` It must be ${range}. Received ${received}`;
      return msg;
    },
    RangeError
  );
  function addNumericalSeparator(val) {
    let res = "";
    let i = val.length;
    const start = val[0] === "-" ? 1 : 0;
    for (; i >= start + 4; i -= 3) {
      res = `_${val.slice(i - 3, i)}${res}`;
    }
    return `${val.slice(0, i)}${res}`;
  }
  function checkBounds(buf, offset, byteLength3) {
    validateNumber(offset, "offset");
    if (buf[offset] === void 0 || buf[offset + byteLength3] === void 0) {
      boundsError(offset, buf.length - (byteLength3 + 1));
    }
  }
  function checkIntBI(value, min, max, buf, offset, byteLength3) {
    if (value > max || value < min) {
      const n2 = typeof min === "bigint" ? "n" : "";
      let range;
      {
        if (min === 0 || min === BigInt(0)) {
          range = `>= 0${n2} and < 2${n2} ** ${(byteLength3 + 1) * 8}${n2}`;
        } else {
          range = `>= -(2${n2} ** ${(byteLength3 + 1) * 8 - 1}${n2}) and < 2 ** ${(byteLength3 + 1) * 8 - 1}${n2}`;
        }
      }
      throw new errors.ERR_OUT_OF_RANGE("value", range, value);
    }
    checkBounds(buf, offset, byteLength3);
  }
  function validateNumber(value, name) {
    if (typeof value !== "number") {
      throw new errors.ERR_INVALID_ARG_TYPE(name, "number", value);
    }
  }
  function boundsError(value, length, type) {
    if (Math.floor(value) !== value) {
      validateNumber(value, type);
      throw new errors.ERR_OUT_OF_RANGE("offset", "an integer", value);
    }
    if (length < 0) {
      throw new errors.ERR_BUFFER_OUT_OF_BOUNDS();
    }
    throw new errors.ERR_OUT_OF_RANGE(
      "offset",
      `>= ${0} and <= ${length}`,
      value
    );
  }
  const INVALID_BASE64_RE = /[^+/0-9A-Za-z-_]/g;
  function base64clean(str) {
    str = str.split("=")[0];
    str = str.trim().replace(INVALID_BASE64_RE, "");
    if (str.length < 2) return "";
    while (str.length % 4 !== 0) {
      str = str + "=";
    }
    return str;
  }
  function utf8ToBytes(string, units) {
    units = units || Infinity;
    let codePoint;
    const length = string.length;
    let leadSurrogate = null;
    const bytes = [];
    for (let i = 0; i < length; ++i) {
      codePoint = string.charCodeAt(i);
      if (codePoint > 55295 && codePoint < 57344) {
        if (!leadSurrogate) {
          if (codePoint > 56319) {
            if ((units -= 3) > -1) bytes.push(239, 191, 189);
            continue;
          } else if (i + 1 === length) {
            if ((units -= 3) > -1) bytes.push(239, 191, 189);
            continue;
          }
          leadSurrogate = codePoint;
          continue;
        }
        if (codePoint < 56320) {
          if ((units -= 3) > -1) bytes.push(239, 191, 189);
          leadSurrogate = codePoint;
          continue;
        }
        codePoint = (leadSurrogate - 55296 << 10 | codePoint - 56320) + 65536;
      } else if (leadSurrogate) {
        if ((units -= 3) > -1) bytes.push(239, 191, 189);
      }
      leadSurrogate = null;
      if (codePoint < 128) {
        if ((units -= 1) < 0) break;
        bytes.push(codePoint);
      } else if (codePoint < 2048) {
        if ((units -= 2) < 0) break;
        bytes.push(
          codePoint >> 6 | 192,
          codePoint & 63 | 128
        );
      } else if (codePoint < 65536) {
        if ((units -= 3) < 0) break;
        bytes.push(
          codePoint >> 12 | 224,
          codePoint >> 6 & 63 | 128,
          codePoint & 63 | 128
        );
      } else if (codePoint < 1114112) {
        if ((units -= 4) < 0) break;
        bytes.push(
          codePoint >> 18 | 240,
          codePoint >> 12 & 63 | 128,
          codePoint >> 6 & 63 | 128,
          codePoint & 63 | 128
        );
      } else {
        throw new Error("Invalid code point");
      }
    }
    return bytes;
  }
  function asciiToBytes(str) {
    const byteArray = [];
    for (let i = 0; i < str.length; ++i) {
      byteArray.push(str.charCodeAt(i) & 255);
    }
    return byteArray;
  }
  function utf16leToBytes(str, units) {
    let c, hi, lo;
    const byteArray = [];
    for (let i = 0; i < str.length; ++i) {
      if ((units -= 2) < 0) break;
      c = str.charCodeAt(i);
      hi = c >> 8;
      lo = c % 256;
      byteArray.push(lo);
      byteArray.push(hi);
    }
    return byteArray;
  }
  function base64ToBytes(str) {
    return base64.toByteArray(base64clean(str));
  }
  function blitBuffer(src, dst, offset, length) {
    let i;
    for (i = 0; i < length; ++i) {
      if (i + offset >= dst.length || i >= src.length) break;
      dst[i + offset] = src[i];
    }
    return i;
  }
  function isInstance(obj, type) {
    return obj instanceof type || obj != null && obj.constructor != null && obj.constructor.name != null && obj.constructor.name === type.name;
  }
  function numberIsNaN(obj) {
    return obj !== obj;
  }
  const hexSliceLookupTable = (function() {
    const alphabet = "0123456789abcdef";
    const table = new Array(256);
    for (let i = 0; i < 16; ++i) {
      const i16 = i * 16;
      for (let j = 0; j < 16; ++j) {
        table[i16 + j] = alphabet[i] + alphabet[j];
      }
    }
    return table;
  })();
  function defineBigIntMethod(fn) {
    return typeof BigInt === "undefined" ? BufferBigIntNotDefined : fn;
  }
  function BufferBigIntNotDefined() {
    throw new Error("BigInt not supported");
  }
})(buffer);
const Buffer = buffer.Buffer;
function AxiosError$1(message, code2, config, request, response) {
  Error.call(this);
  if (Error.captureStackTrace) {
    Error.captureStackTrace(this, this.constructor);
  } else {
    this.stack = new Error().stack;
  }
  this.message = message;
  this.name = "AxiosError";
  code2 && (this.code = code2);
  config && (this.config = config);
  request && (this.request = request);
  if (response) {
    this.response = response;
    this.status = response.status ? response.status : null;
  }
}
utils$1.inherits(AxiosError$1, Error, {
  toJSON: function toJSON() {
    return {
      // Standard
      message: this.message,
      name: this.name,
      // Microsoft
      description: this.description,
      number: this.number,
      // Mozilla
      fileName: this.fileName,
      lineNumber: this.lineNumber,
      columnNumber: this.columnNumber,
      stack: this.stack,
      // Axios
      config: utils$1.toJSONObject(this.config),
      code: this.code,
      status: this.status
    };
  }
});
const prototype$1 = AxiosError$1.prototype;
const descriptors = {};
[
  "ERR_BAD_OPTION_VALUE",
  "ERR_BAD_OPTION",
  "ECONNABORTED",
  "ETIMEDOUT",
  "ERR_NETWORK",
  "ERR_FR_TOO_MANY_REDIRECTS",
  "ERR_DEPRECATED",
  "ERR_BAD_RESPONSE",
  "ERR_BAD_REQUEST",
  "ERR_CANCELED",
  "ERR_NOT_SUPPORT",
  "ERR_INVALID_URL"
  // eslint-disable-next-line func-names
].forEach((code2) => {
  descriptors[code2] = { value: code2 };
});
Object.defineProperties(AxiosError$1, descriptors);
Object.defineProperty(prototype$1, "isAxiosError", { value: true });
AxiosError$1.from = (error, code2, config, request, response, customProps) => {
  const axiosError = Object.create(prototype$1);
  utils$1.toFlatObject(error, axiosError, function filter2(obj) {
    return obj !== Error.prototype;
  }, (prop) => {
    return prop !== "isAxiosError";
  });
  const msg = error && error.message ? error.message : "Error";
  const errCode = code2 == null && error ? error.code : code2;
  AxiosError$1.call(axiosError, msg, errCode, config, request, response);
  if (error && axiosError.cause == null) {
    Object.defineProperty(axiosError, "cause", { value: error, configurable: true });
  }
  axiosError.name = error && error.name || "Error";
  customProps && Object.assign(axiosError, customProps);
  return axiosError;
};
const httpAdapter = null;
function isVisitable(thing) {
  return utils$1.isPlainObject(thing) || utils$1.isArray(thing);
}
function removeBrackets(key) {
  return utils$1.endsWith(key, "[]") ? key.slice(0, -2) : key;
}
function renderKey(path, key, dots) {
  if (!path) return key;
  return path.concat(key).map(function each(token2, i) {
    token2 = removeBrackets(token2);
    return !dots && i ? "[" + token2 + "]" : token2;
  }).join(dots ? "." : "");
}
function isFlatArray(arr) {
  return utils$1.isArray(arr) && !arr.some(isVisitable);
}
const predicates = utils$1.toFlatObject(utils$1, {}, null, function filter(prop) {
  return /^is[A-Z]/.test(prop);
});
function toFormData$1(obj, formData, options) {
  if (!utils$1.isObject(obj)) {
    throw new TypeError("target must be an object");
  }
  formData = formData || new FormData();
  options = utils$1.toFlatObject(options, {
    metaTokens: true,
    dots: false,
    indexes: false
  }, false, function defined(option, source) {
    return !utils$1.isUndefined(source[option]);
  });
  const metaTokens = options.metaTokens;
  const visitor = options.visitor || defaultVisitor;
  const dots = options.dots;
  const indexes = options.indexes;
  const _Blob = options.Blob || typeof Blob !== "undefined" && Blob;
  const useBlob = _Blob && utils$1.isSpecCompliantForm(formData);
  if (!utils$1.isFunction(visitor)) {
    throw new TypeError("visitor must be a function");
  }
  function convertValue(value) {
    if (value === null) return "";
    if (utils$1.isDate(value)) {
      return value.toISOString();
    }
    if (utils$1.isBoolean(value)) {
      return value.toString();
    }
    if (!useBlob && utils$1.isBlob(value)) {
      throw new AxiosError$1("Blob is not supported. Use a Buffer instead.");
    }
    if (utils$1.isArrayBuffer(value) || utils$1.isTypedArray(value)) {
      return useBlob && typeof Blob === "function" ? new Blob([value]) : Buffer.from(value);
    }
    return value;
  }
  function defaultVisitor(value, key, path) {
    let arr = value;
    if (value && !path && typeof value === "object") {
      if (utils$1.endsWith(key, "{}")) {
        key = metaTokens ? key : key.slice(0, -2);
        value = JSON.stringify(value);
      } else if (utils$1.isArray(value) && isFlatArray(value) || (utils$1.isFileList(value) || utils$1.endsWith(key, "[]")) && (arr = utils$1.toArray(value))) {
        key = removeBrackets(key);
        arr.forEach(function each(el, index) {
          !(utils$1.isUndefined(el) || el === null) && formData.append(
            // eslint-disable-next-line no-nested-ternary
            indexes === true ? renderKey([key], index, dots) : indexes === null ? key : key + "[]",
            convertValue(el)
          );
        });
        return false;
      }
    }
    if (isVisitable(value)) {
      return true;
    }
    formData.append(renderKey(path, key, dots), convertValue(value));
    return false;
  }
  const stack2 = [];
  const exposedHelpers = Object.assign(predicates, {
    defaultVisitor,
    convertValue,
    isVisitable
  });
  function build(value, path) {
    if (utils$1.isUndefined(value)) return;
    if (stack2.indexOf(value) !== -1) {
      throw Error("Circular reference detected in " + path.join("."));
    }
    stack2.push(value);
    utils$1.forEach(value, function each(el, key) {
      const result = !(utils$1.isUndefined(el) || el === null) && visitor.call(
        formData,
        el,
        utils$1.isString(key) ? key.trim() : key,
        path,
        exposedHelpers
      );
      if (result === true) {
        build(el, path ? path.concat(key) : [key]);
      }
    });
    stack2.pop();
  }
  if (!utils$1.isObject(obj)) {
    throw new TypeError("data must be an object");
  }
  build(obj);
  return formData;
}
function encode$1(str) {
  const charMap = {
    "!": "%21",
    "'": "%27",
    "(": "%28",
    ")": "%29",
    "~": "%7E",
    "%20": "+",
    "%00": "\0"
  };
  return encodeURIComponent(str).replace(/[!'()~]|%20|%00/g, function replacer2(match) {
    return charMap[match];
  });
}
function AxiosURLSearchParams(params, options) {
  this._pairs = [];
  params && toFormData$1(params, this, options);
}
const prototype = AxiosURLSearchParams.prototype;
prototype.append = function append(name, value) {
  this._pairs.push([name, value]);
};
prototype.toString = function toString2(encoder) {
  const _encode = encoder ? function(value) {
    return encoder.call(this, value, encode$1);
  } : encode$1;
  return this._pairs.map(function each(pair) {
    return _encode(pair[0]) + "=" + _encode(pair[1]);
  }, "").join("&");
};
function encode(val) {
  return encodeURIComponent(val).replace(/%3A/gi, ":").replace(/%24/g, "$").replace(/%2C/gi, ",").replace(/%20/g, "+");
}
function buildURL(url, params, options) {
  if (!params) {
    return url;
  }
  const _encode = options && options.encode || encode;
  if (utils$1.isFunction(options)) {
    options = {
      serialize: options
    };
  }
  const serializeFn = options && options.serialize;
  let serializedParams;
  if (serializeFn) {
    serializedParams = serializeFn(params, options);
  } else {
    serializedParams = utils$1.isURLSearchParams(params) ? params.toString() : new AxiosURLSearchParams(params, options).toString(_encode);
  }
  if (serializedParams) {
    const hashmarkIndex = url.indexOf("#");
    if (hashmarkIndex !== -1) {
      url = url.slice(0, hashmarkIndex);
    }
    url += (url.indexOf("?") === -1 ? "?" : "&") + serializedParams;
  }
  return url;
}
class InterceptorManager {
  constructor() {
    this.handlers = [];
  }
  /**
   * Add a new interceptor to the stack
   *
   * @param {Function} fulfilled The function to handle `then` for a `Promise`
   * @param {Function} rejected The function to handle `reject` for a `Promise`
   *
   * @return {Number} An ID used to remove interceptor later
   */
  use(fulfilled, rejected, options) {
    this.handlers.push({
      fulfilled,
      rejected,
      synchronous: options ? options.synchronous : false,
      runWhen: options ? options.runWhen : null
    });
    return this.handlers.length - 1;
  }
  /**
   * Remove an interceptor from the stack
   *
   * @param {Number} id The ID that was returned by `use`
   *
   * @returns {void}
   */
  eject(id) {
    if (this.handlers[id]) {
      this.handlers[id] = null;
    }
  }
  /**
   * Clear all interceptors from the stack
   *
   * @returns {void}
   */
  clear() {
    if (this.handlers) {
      this.handlers = [];
    }
  }
  /**
   * Iterate over all the registered interceptors
   *
   * This method is particularly useful for skipping over any
   * interceptors that may have become `null` calling `eject`.
   *
   * @param {Function} fn The function to call for each interceptor
   *
   * @returns {void}
   */
  forEach(fn) {
    utils$1.forEach(this.handlers, function forEachHandler(h2) {
      if (h2 !== null) {
        fn(h2);
      }
    });
  }
}
const transitionalDefaults = {
  silentJSONParsing: true,
  forcedJSONParsing: true,
  clarifyTimeoutError: false
};
const URLSearchParams$1 = typeof URLSearchParams !== "undefined" ? URLSearchParams : AxiosURLSearchParams;
const FormData$1 = typeof FormData !== "undefined" ? FormData : null;
const Blob$1 = typeof Blob !== "undefined" ? Blob : null;
const platform$1 = {
  isBrowser: true,
  classes: {
    URLSearchParams: URLSearchParams$1,
    FormData: FormData$1,
    Blob: Blob$1
  },
  protocols: ["http", "https", "file", "blob", "url", "data"]
};
const hasBrowserEnv = typeof window !== "undefined" && typeof document !== "undefined";
const _navigator = typeof navigator === "object" && navigator || void 0;
const hasStandardBrowserEnv = hasBrowserEnv && (!_navigator || ["ReactNative", "NativeScript", "NS"].indexOf(_navigator.product) < 0);
const hasStandardBrowserWebWorkerEnv = (() => {
  return typeof WorkerGlobalScope !== "undefined" && // eslint-disable-next-line no-undef
  self instanceof WorkerGlobalScope && typeof self.importScripts === "function";
})();
const origin = hasBrowserEnv && window.location.href || "http://localhost";
const utils = /* @__PURE__ */ Object.freeze(/* @__PURE__ */ Object.defineProperty({
  __proto__: null,
  hasBrowserEnv,
  hasStandardBrowserEnv,
  hasStandardBrowserWebWorkerEnv,
  navigator: _navigator,
  origin
}, Symbol.toStringTag, { value: "Module" }));
const platform = {
  ...utils,
  ...platform$1
};
function toURLEncodedForm(data, options) {
  return toFormData$1(data, new platform.classes.URLSearchParams(), {
    visitor: function(value, key, path, helpers) {
      if (platform.isNode && utils$1.isBuffer(value)) {
        this.append(key, value.toString("base64"));
        return false;
      }
      return helpers.defaultVisitor.apply(this, arguments);
    },
    ...options
  });
}
function parsePropPath(name) {
  return utils$1.matchAll(/\w+|\[(\w*)]/g, name).map((match) => {
    return match[0] === "[]" ? "" : match[1] || match[0];
  });
}
function arrayToObject(arr) {
  const obj = {};
  const keys = Object.keys(arr);
  let i;
  const len = keys.length;
  let key;
  for (i = 0; i < len; i++) {
    key = keys[i];
    obj[key] = arr[key];
  }
  return obj;
}
function formDataToJSON(formData) {
  function buildPath(path, value, target, index) {
    let name = path[index++];
    if (name === "__proto__") return true;
    const isNumericKey = Number.isFinite(+name);
    const isLast = index >= path.length;
    name = !name && utils$1.isArray(target) ? target.length : name;
    if (isLast) {
      if (utils$1.hasOwnProp(target, name)) {
        target[name] = [target[name], value];
      } else {
        target[name] = value;
      }
      return !isNumericKey;
    }
    if (!target[name] || !utils$1.isObject(target[name])) {
      target[name] = [];
    }
    const result = buildPath(path, value, target[name], index);
    if (result && utils$1.isArray(target[name])) {
      target[name] = arrayToObject(target[name]);
    }
    return !isNumericKey;
  }
  if (utils$1.isFormData(formData) && utils$1.isFunction(formData.entries)) {
    const obj = {};
    utils$1.forEachEntry(formData, (name, value) => {
      buildPath(parsePropPath(name), value, obj, 0);
    });
    return obj;
  }
  return null;
}
function stringifySafely(rawValue, parser, encoder) {
  if (utils$1.isString(rawValue)) {
    try {
      (parser || JSON.parse)(rawValue);
      return utils$1.trim(rawValue);
    } catch (e) {
      if (e.name !== "SyntaxError") {
        throw e;
      }
    }
  }
  return (encoder || JSON.stringify)(rawValue);
}
const defaults = {
  transitional: transitionalDefaults,
  adapter: ["xhr", "http", "fetch"],
  transformRequest: [function transformRequest(data, headers) {
    const contentType = headers.getContentType() || "";
    const hasJSONContentType = contentType.indexOf("application/json") > -1;
    const isObjectPayload = utils$1.isObject(data);
    if (isObjectPayload && utils$1.isHTMLForm(data)) {
      data = new FormData(data);
    }
    const isFormData2 = utils$1.isFormData(data);
    if (isFormData2) {
      return hasJSONContentType ? JSON.stringify(formDataToJSON(data)) : data;
    }
    if (utils$1.isArrayBuffer(data) || utils$1.isBuffer(data) || utils$1.isStream(data) || utils$1.isFile(data) || utils$1.isBlob(data) || utils$1.isReadableStream(data)) {
      return data;
    }
    if (utils$1.isArrayBufferView(data)) {
      return data.buffer;
    }
    if (utils$1.isURLSearchParams(data)) {
      headers.setContentType("application/x-www-form-urlencoded;charset=utf-8", false);
      return data.toString();
    }
    let isFileList2;
    if (isObjectPayload) {
      if (contentType.indexOf("application/x-www-form-urlencoded") > -1) {
        return toURLEncodedForm(data, this.formSerializer).toString();
      }
      if ((isFileList2 = utils$1.isFileList(data)) || contentType.indexOf("multipart/form-data") > -1) {
        const _FormData = this.env && this.env.FormData;
        return toFormData$1(
          isFileList2 ? { "files[]": data } : data,
          _FormData && new _FormData(),
          this.formSerializer
        );
      }
    }
    if (isObjectPayload || hasJSONContentType) {
      headers.setContentType("application/json", false);
      return stringifySafely(data);
    }
    return data;
  }],
  transformResponse: [function transformResponse(data) {
    const transitional2 = this.transitional || defaults.transitional;
    const forcedJSONParsing = transitional2 && transitional2.forcedJSONParsing;
    const JSONRequested = this.responseType === "json";
    if (utils$1.isResponse(data) || utils$1.isReadableStream(data)) {
      return data;
    }
    if (data && utils$1.isString(data) && (forcedJSONParsing && !this.responseType || JSONRequested)) {
      const silentJSONParsing = transitional2 && transitional2.silentJSONParsing;
      const strictJSONParsing = !silentJSONParsing && JSONRequested;
      try {
        return JSON.parse(data, this.parseReviver);
      } catch (e) {
        if (strictJSONParsing) {
          if (e.name === "SyntaxError") {
            throw AxiosError$1.from(e, AxiosError$1.ERR_BAD_RESPONSE, this, null, this.response);
          }
          throw e;
        }
      }
    }
    return data;
  }],
  /**
   * A timeout in milliseconds to abort a request. If set to 0 (default) a
   * timeout is not created.
   */
  timeout: 0,
  xsrfCookieName: "XSRF-TOKEN",
  xsrfHeaderName: "X-XSRF-TOKEN",
  maxContentLength: -1,
  maxBodyLength: -1,
  env: {
    FormData: platform.classes.FormData,
    Blob: platform.classes.Blob
  },
  validateStatus: function validateStatus(status) {
    return status >= 200 && status < 300;
  },
  headers: {
    common: {
      "Accept": "application/json, text/plain, */*",
      "Content-Type": void 0
    }
  }
};
utils$1.forEach(["delete", "get", "head", "post", "put", "patch"], (method) => {
  defaults.headers[method] = {};
});
const ignoreDuplicateOf = utils$1.toObjectSet([
  "age",
  "authorization",
  "content-length",
  "content-type",
  "etag",
  "expires",
  "from",
  "host",
  "if-modified-since",
  "if-unmodified-since",
  "last-modified",
  "location",
  "max-forwards",
  "proxy-authorization",
  "referer",
  "retry-after",
  "user-agent"
]);
const parseHeaders = (rawHeaders) => {
  const parsed = {};
  let key;
  let val;
  let i;
  rawHeaders && rawHeaders.split("\n").forEach(function parser(line) {
    i = line.indexOf(":");
    key = line.substring(0, i).trim().toLowerCase();
    val = line.substring(i + 1).trim();
    if (!key || parsed[key] && ignoreDuplicateOf[key]) {
      return;
    }
    if (key === "set-cookie") {
      if (parsed[key]) {
        parsed[key].push(val);
      } else {
        parsed[key] = [val];
      }
    } else {
      parsed[key] = parsed[key] ? parsed[key] + ", " + val : val;
    }
  });
  return parsed;
};
const $internals = Symbol("internals");
function normalizeHeader(header) {
  return header && String(header).trim().toLowerCase();
}
function normalizeValue(value) {
  if (value === false || value == null) {
    return value;
  }
  return utils$1.isArray(value) ? value.map(normalizeValue) : String(value);
}
function parseTokens(str) {
  const tokens = /* @__PURE__ */ Object.create(null);
  const tokensRE = /([^\s,;=]+)\s*(?:=\s*([^,;]+))?/g;
  let match;
  while (match = tokensRE.exec(str)) {
    tokens[match[1]] = match[2];
  }
  return tokens;
}
const isValidHeaderName = (str) => /^[-_a-zA-Z0-9^`|~,!#$%&'*+.]+$/.test(str.trim());
function matchHeaderValue(context, value, header, filter2, isHeaderNameFilter) {
  if (utils$1.isFunction(filter2)) {
    return filter2.call(this, value, header);
  }
  if (isHeaderNameFilter) {
    value = header;
  }
  if (!utils$1.isString(value)) return;
  if (utils$1.isString(filter2)) {
    return value.indexOf(filter2) !== -1;
  }
  if (utils$1.isRegExp(filter2)) {
    return filter2.test(value);
  }
}
function formatHeader(header) {
  return header.trim().toLowerCase().replace(/([a-z\d])(\w*)/g, (w, char, str) => {
    return char.toUpperCase() + str;
  });
}
function buildAccessors(obj, header) {
  const accessorName = utils$1.toCamelCase(" " + header);
  ["get", "set", "has"].forEach((methodName) => {
    Object.defineProperty(obj, methodName + accessorName, {
      value: function(arg1, arg2, arg3) {
        return this[methodName].call(this, header, arg1, arg2, arg3);
      },
      configurable: true
    });
  });
}
let AxiosHeaders$1 = class AxiosHeaders {
  constructor(headers) {
    headers && this.set(headers);
  }
  set(header, valueOrRewrite, rewrite) {
    const self2 = this;
    function setHeader(_value, _header, _rewrite) {
      const lHeader = normalizeHeader(_header);
      if (!lHeader) {
        throw new Error("header name must be a non-empty string");
      }
      const key = utils$1.findKey(self2, lHeader);
      if (!key || self2[key] === void 0 || _rewrite === true || _rewrite === void 0 && self2[key] !== false) {
        self2[key || _header] = normalizeValue(_value);
      }
    }
    const setHeaders = (headers, _rewrite) => utils$1.forEach(headers, (_value, _header) => setHeader(_value, _header, _rewrite));
    if (utils$1.isPlainObject(header) || header instanceof this.constructor) {
      setHeaders(header, valueOrRewrite);
    } else if (utils$1.isString(header) && (header = header.trim()) && !isValidHeaderName(header)) {
      setHeaders(parseHeaders(header), valueOrRewrite);
    } else if (utils$1.isObject(header) && utils$1.isIterable(header)) {
      let obj = {}, dest, key;
      for (const entry of header) {
        if (!utils$1.isArray(entry)) {
          throw TypeError("Object iterator must return a key-value pair");
        }
        obj[key = entry[0]] = (dest = obj[key]) ? utils$1.isArray(dest) ? [...dest, entry[1]] : [dest, entry[1]] : entry[1];
      }
      setHeaders(obj, valueOrRewrite);
    } else {
      header != null && setHeader(valueOrRewrite, header, rewrite);
    }
    return this;
  }
  get(header, parser) {
    header = normalizeHeader(header);
    if (header) {
      const key = utils$1.findKey(this, header);
      if (key) {
        const value = this[key];
        if (!parser) {
          return value;
        }
        if (parser === true) {
          return parseTokens(value);
        }
        if (utils$1.isFunction(parser)) {
          return parser.call(this, value, key);
        }
        if (utils$1.isRegExp(parser)) {
          return parser.exec(value);
        }
        throw new TypeError("parser must be boolean|regexp|function");
      }
    }
  }
  has(header, matcher) {
    header = normalizeHeader(header);
    if (header) {
      const key = utils$1.findKey(this, header);
      return !!(key && this[key] !== void 0 && (!matcher || matchHeaderValue(this, this[key], key, matcher)));
    }
    return false;
  }
  delete(header, matcher) {
    const self2 = this;
    let deleted = false;
    function deleteHeader(_header) {
      _header = normalizeHeader(_header);
      if (_header) {
        const key = utils$1.findKey(self2, _header);
        if (key && (!matcher || matchHeaderValue(self2, self2[key], key, matcher))) {
          delete self2[key];
          deleted = true;
        }
      }
    }
    if (utils$1.isArray(header)) {
      header.forEach(deleteHeader);
    } else {
      deleteHeader(header);
    }
    return deleted;
  }
  clear(matcher) {
    const keys = Object.keys(this);
    let i = keys.length;
    let deleted = false;
    while (i--) {
      const key = keys[i];
      if (!matcher || matchHeaderValue(this, this[key], key, matcher, true)) {
        delete this[key];
        deleted = true;
      }
    }
    return deleted;
  }
  normalize(format) {
    const self2 = this;
    const headers = {};
    utils$1.forEach(this, (value, header) => {
      const key = utils$1.findKey(headers, header);
      if (key) {
        self2[key] = normalizeValue(value);
        delete self2[header];
        return;
      }
      const normalized = format ? formatHeader(header) : String(header).trim();
      if (normalized !== header) {
        delete self2[header];
      }
      self2[normalized] = normalizeValue(value);
      headers[normalized] = true;
    });
    return this;
  }
  concat(...targets) {
    return this.constructor.concat(this, ...targets);
  }
  toJSON(asStrings) {
    const obj = /* @__PURE__ */ Object.create(null);
    utils$1.forEach(this, (value, header) => {
      value != null && value !== false && (obj[header] = asStrings && utils$1.isArray(value) ? value.join(", ") : value);
    });
    return obj;
  }
  [Symbol.iterator]() {
    return Object.entries(this.toJSON())[Symbol.iterator]();
  }
  toString() {
    return Object.entries(this.toJSON()).map(([header, value]) => header + ": " + value).join("\n");
  }
  getSetCookie() {
    return this.get("set-cookie") || [];
  }
  get [Symbol.toStringTag]() {
    return "AxiosHeaders";
  }
  static from(thing) {
    return thing instanceof this ? thing : new this(thing);
  }
  static concat(first, ...targets) {
    const computed2 = new this(first);
    targets.forEach((target) => computed2.set(target));
    return computed2;
  }
  static accessor(header) {
    const internals = this[$internals] = this[$internals] = {
      accessors: {}
    };
    const accessors = internals.accessors;
    const prototype2 = this.prototype;
    function defineAccessor(_header) {
      const lHeader = normalizeHeader(_header);
      if (!accessors[lHeader]) {
        buildAccessors(prototype2, _header);
        accessors[lHeader] = true;
      }
    }
    utils$1.isArray(header) ? header.forEach(defineAccessor) : defineAccessor(header);
    return this;
  }
};
AxiosHeaders$1.accessor(["Content-Type", "Content-Length", "Accept", "Accept-Encoding", "User-Agent", "Authorization"]);
utils$1.reduceDescriptors(AxiosHeaders$1.prototype, ({ value }, key) => {
  let mapped = key[0].toUpperCase() + key.slice(1);
  return {
    get: () => value,
    set(headerValue) {
      this[mapped] = headerValue;
    }
  };
});
utils$1.freezeMethods(AxiosHeaders$1);
function transformData(fns, response) {
  const config = this || defaults;
  const context = response || config;
  const headers = AxiosHeaders$1.from(context.headers);
  let data = context.data;
  utils$1.forEach(fns, function transform(fn) {
    data = fn.call(config, data, headers.normalize(), response ? response.status : void 0);
  });
  headers.normalize();
  return data;
}
function isCancel$1(value) {
  return !!(value && value.__CANCEL__);
}
function CanceledError$1(message, config, request) {
  AxiosError$1.call(this, message == null ? "canceled" : message, AxiosError$1.ERR_CANCELED, config, request);
  this.name = "CanceledError";
}
utils$1.inherits(CanceledError$1, AxiosError$1, {
  __CANCEL__: true
});
function settle(resolve2, reject, response) {
  const validateStatus2 = response.config.validateStatus;
  if (!response.status || !validateStatus2 || validateStatus2(response.status)) {
    resolve2(response);
  } else {
    reject(new AxiosError$1(
      "Request failed with status code " + response.status,
      [AxiosError$1.ERR_BAD_REQUEST, AxiosError$1.ERR_BAD_RESPONSE][Math.floor(response.status / 100) - 4],
      response.config,
      response.request,
      response
    ));
  }
}
function parseProtocol(url) {
  const match = /^([-+\w]{1,25})(:?\/\/|:)/.exec(url);
  return match && match[1] || "";
}
function speedometer(samplesCount, min) {
  samplesCount = samplesCount || 10;
  const bytes = new Array(samplesCount);
  const timestamps = new Array(samplesCount);
  let head = 0;
  let tail = 0;
  let firstSampleTS;
  min = min !== void 0 ? min : 1e3;
  return function push(chunkLength) {
    const now = Date.now();
    const startedAt = timestamps[tail];
    if (!firstSampleTS) {
      firstSampleTS = now;
    }
    bytes[head] = chunkLength;
    timestamps[head] = now;
    let i = tail;
    let bytesCount = 0;
    while (i !== head) {
      bytesCount += bytes[i++];
      i = i % samplesCount;
    }
    head = (head + 1) % samplesCount;
    if (head === tail) {
      tail = (tail + 1) % samplesCount;
    }
    if (now - firstSampleTS < min) {
      return;
    }
    const passed = startedAt && now - startedAt;
    return passed ? Math.round(bytesCount * 1e3 / passed) : void 0;
  };
}
function throttle(fn, freq) {
  let timestamp = 0;
  let threshold = 1e3 / freq;
  let lastArgs;
  let timer;
  const invoke = (args, now = Date.now()) => {
    timestamp = now;
    lastArgs = null;
    if (timer) {
      clearTimeout(timer);
      timer = null;
    }
    fn(...args);
  };
  const throttled = (...args) => {
    const now = Date.now();
    const passed = now - timestamp;
    if (passed >= threshold) {
      invoke(args, now);
    } else {
      lastArgs = args;
      if (!timer) {
        timer = setTimeout(() => {
          timer = null;
          invoke(lastArgs);
        }, threshold - passed);
      }
    }
  };
  const flush = () => lastArgs && invoke(lastArgs);
  return [throttled, flush];
}
const progressEventReducer = (listener, isDownloadStream, freq = 3) => {
  let bytesNotified = 0;
  const _speedometer = speedometer(50, 250);
  return throttle((e) => {
    const loaded = e.loaded;
    const total = e.lengthComputable ? e.total : void 0;
    const progressBytes = loaded - bytesNotified;
    const rate = _speedometer(progressBytes);
    const inRange = loaded <= total;
    bytesNotified = loaded;
    const data = {
      loaded,
      total,
      progress: total ? loaded / total : void 0,
      bytes: progressBytes,
      rate: rate ? rate : void 0,
      estimated: rate && total && inRange ? (total - loaded) / rate : void 0,
      event: e,
      lengthComputable: total != null,
      [isDownloadStream ? "download" : "upload"]: true
    };
    listener(data);
  }, freq);
};
const progressEventDecorator = (total, throttled) => {
  const lengthComputable = total != null;
  return [(loaded) => throttled[0]({
    lengthComputable,
    total,
    loaded
  }), throttled[1]];
};
const asyncDecorator = (fn) => (...args) => utils$1.asap(() => fn(...args));
const isURLSameOrigin = platform.hasStandardBrowserEnv ? /* @__PURE__ */ ((origin2, isMSIE) => (url) => {
  url = new URL(url, platform.origin);
  return origin2.protocol === url.protocol && origin2.host === url.host && (isMSIE || origin2.port === url.port);
})(
  new URL(platform.origin),
  platform.navigator && /(msie|trident)/i.test(platform.navigator.userAgent)
) : () => true;
const cookies = platform.hasStandardBrowserEnv ? (
  // Standard browser envs support document.cookie
  {
    write(name, value, expires, path, domain, secure, sameSite) {
      if (typeof document === "undefined") return;
      const cookie = [`${name}=${encodeURIComponent(value)}`];
      if (utils$1.isNumber(expires)) {
        cookie.push(`expires=${new Date(expires).toUTCString()}`);
      }
      if (utils$1.isString(path)) {
        cookie.push(`path=${path}`);
      }
      if (utils$1.isString(domain)) {
        cookie.push(`domain=${domain}`);
      }
      if (secure === true) {
        cookie.push("secure");
      }
      if (utils$1.isString(sameSite)) {
        cookie.push(`SameSite=${sameSite}`);
      }
      document.cookie = cookie.join("; ");
    },
    read(name) {
      if (typeof document === "undefined") return null;
      const match = document.cookie.match(new RegExp("(?:^|; )" + name + "=([^;]*)"));
      return match ? decodeURIComponent(match[1]) : null;
    },
    remove(name) {
      this.write(name, "", Date.now() - 864e5, "/");
    }
  }
) : (
  // Non-standard browser env (web workers, react-native) lack needed support.
  {
    write() {
    },
    read() {
      return null;
    },
    remove() {
    }
  }
);
function isAbsoluteURL(url) {
  return /^([a-z][a-z\d+\-.]*:)?\/\//i.test(url);
}
function combineURLs(baseURL, relativeURL) {
  return relativeURL ? baseURL.replace(/\/?\/$/, "") + "/" + relativeURL.replace(/^\/+/, "") : baseURL;
}
function buildFullPath(baseURL, requestedURL, allowAbsoluteUrls) {
  let isRelativeUrl = !isAbsoluteURL(requestedURL);
  if (baseURL && (isRelativeUrl || allowAbsoluteUrls == false)) {
    return combineURLs(baseURL, requestedURL);
  }
  return requestedURL;
}
const headersToObject = (thing) => thing instanceof AxiosHeaders$1 ? { ...thing } : thing;
function mergeConfig$1(config1, config2) {
  config2 = config2 || {};
  const config = {};
  function getMergedValue(target, source, prop, caseless) {
    if (utils$1.isPlainObject(target) && utils$1.isPlainObject(source)) {
      return utils$1.merge.call({ caseless }, target, source);
    } else if (utils$1.isPlainObject(source)) {
      return utils$1.merge({}, source);
    } else if (utils$1.isArray(source)) {
      return source.slice();
    }
    return source;
  }
  function mergeDeepProperties(a, b, prop, caseless) {
    if (!utils$1.isUndefined(b)) {
      return getMergedValue(a, b, prop, caseless);
    } else if (!utils$1.isUndefined(a)) {
      return getMergedValue(void 0, a, prop, caseless);
    }
  }
  function valueFromConfig2(a, b) {
    if (!utils$1.isUndefined(b)) {
      return getMergedValue(void 0, b);
    }
  }
  function defaultToConfig2(a, b) {
    if (!utils$1.isUndefined(b)) {
      return getMergedValue(void 0, b);
    } else if (!utils$1.isUndefined(a)) {
      return getMergedValue(void 0, a);
    }
  }
  function mergeDirectKeys(a, b, prop) {
    if (prop in config2) {
      return getMergedValue(a, b);
    } else if (prop in config1) {
      return getMergedValue(void 0, a);
    }
  }
  const mergeMap = {
    url: valueFromConfig2,
    method: valueFromConfig2,
    data: valueFromConfig2,
    baseURL: defaultToConfig2,
    transformRequest: defaultToConfig2,
    transformResponse: defaultToConfig2,
    paramsSerializer: defaultToConfig2,
    timeout: defaultToConfig2,
    timeoutMessage: defaultToConfig2,
    withCredentials: defaultToConfig2,
    withXSRFToken: defaultToConfig2,
    adapter: defaultToConfig2,
    responseType: defaultToConfig2,
    xsrfCookieName: defaultToConfig2,
    xsrfHeaderName: defaultToConfig2,
    onUploadProgress: defaultToConfig2,
    onDownloadProgress: defaultToConfig2,
    decompress: defaultToConfig2,
    maxContentLength: defaultToConfig2,
    maxBodyLength: defaultToConfig2,
    beforeRedirect: defaultToConfig2,
    transport: defaultToConfig2,
    httpAgent: defaultToConfig2,
    httpsAgent: defaultToConfig2,
    cancelToken: defaultToConfig2,
    socketPath: defaultToConfig2,
    responseEncoding: defaultToConfig2,
    validateStatus: mergeDirectKeys,
    headers: (a, b, prop) => mergeDeepProperties(headersToObject(a), headersToObject(b), prop, true)
  };
  utils$1.forEach(Object.keys({ ...config1, ...config2 }), function computeConfigValue(prop) {
    const merge2 = mergeMap[prop] || mergeDeepProperties;
    const configValue = merge2(config1[prop], config2[prop], prop);
    utils$1.isUndefined(configValue) && merge2 !== mergeDirectKeys || (config[prop] = configValue);
  });
  return config;
}
const resolveConfig = (config) => {
  const newConfig = mergeConfig$1({}, config);
  let { data, withXSRFToken, xsrfHeaderName, xsrfCookieName, headers, auth } = newConfig;
  newConfig.headers = headers = AxiosHeaders$1.from(headers);
  newConfig.url = buildURL(buildFullPath(newConfig.baseURL, newConfig.url, newConfig.allowAbsoluteUrls), config.params, config.paramsSerializer);
  if (auth) {
    headers.set(
      "Authorization",
      "Basic " + btoa((auth.username || "") + ":" + (auth.password ? unescape(encodeURIComponent(auth.password)) : ""))
    );
  }
  if (utils$1.isFormData(data)) {
    if (platform.hasStandardBrowserEnv || platform.hasStandardBrowserWebWorkerEnv) {
      headers.setContentType(void 0);
    } else if (utils$1.isFunction(data.getHeaders)) {
      const formHeaders = data.getHeaders();
      const allowedHeaders = ["content-type", "content-length"];
      Object.entries(formHeaders).forEach(([key, val]) => {
        if (allowedHeaders.includes(key.toLowerCase())) {
          headers.set(key, val);
        }
      });
    }
  }
  if (platform.hasStandardBrowserEnv) {
    withXSRFToken && utils$1.isFunction(withXSRFToken) && (withXSRFToken = withXSRFToken(newConfig));
    if (withXSRFToken || withXSRFToken !== false && isURLSameOrigin(newConfig.url)) {
      const xsrfValue = xsrfHeaderName && xsrfCookieName && cookies.read(xsrfCookieName);
      if (xsrfValue) {
        headers.set(xsrfHeaderName, xsrfValue);
      }
    }
  }
  return newConfig;
};
const isXHRAdapterSupported = typeof XMLHttpRequest !== "undefined";
const xhrAdapter = isXHRAdapterSupported && function(config) {
  return new Promise(function dispatchXhrRequest(resolve2, reject) {
    const _config = resolveConfig(config);
    let requestData = _config.data;
    const requestHeaders = AxiosHeaders$1.from(_config.headers).normalize();
    let { responseType, onUploadProgress, onDownloadProgress } = _config;
    let onCanceled;
    let uploadThrottled, downloadThrottled;
    let flushUpload, flushDownload;
    function done() {
      flushUpload && flushUpload();
      flushDownload && flushDownload();
      _config.cancelToken && _config.cancelToken.unsubscribe(onCanceled);
      _config.signal && _config.signal.removeEventListener("abort", onCanceled);
    }
    let request = new XMLHttpRequest();
    request.open(_config.method.toUpperCase(), _config.url, true);
    request.timeout = _config.timeout;
    function onloadend() {
      if (!request) {
        return;
      }
      const responseHeaders = AxiosHeaders$1.from(
        "getAllResponseHeaders" in request && request.getAllResponseHeaders()
      );
      const responseData = !responseType || responseType === "text" || responseType === "json" ? request.responseText : request.response;
      const response = {
        data: responseData,
        status: request.status,
        statusText: request.statusText,
        headers: responseHeaders,
        config,
        request
      };
      settle(function _resolve(value) {
        resolve2(value);
        done();
      }, function _reject(err) {
        reject(err);
        done();
      }, response);
      request = null;
    }
    if ("onloadend" in request) {
      request.onloadend = onloadend;
    } else {
      request.onreadystatechange = function handleLoad() {
        if (!request || request.readyState !== 4) {
          return;
        }
        if (request.status === 0 && !(request.responseURL && request.responseURL.indexOf("file:") === 0)) {
          return;
        }
        setTimeout(onloadend);
      };
    }
    request.onabort = function handleAbort() {
      if (!request) {
        return;
      }
      reject(new AxiosError$1("Request aborted", AxiosError$1.ECONNABORTED, config, request));
      request = null;
    };
    request.onerror = function handleError2(event) {
      const msg = event && event.message ? event.message : "Network Error";
      const err = new AxiosError$1(msg, AxiosError$1.ERR_NETWORK, config, request);
      err.event = event || null;
      reject(err);
      request = null;
    };
    request.ontimeout = function handleTimeout() {
      let timeoutErrorMessage = _config.timeout ? "timeout of " + _config.timeout + "ms exceeded" : "timeout exceeded";
      const transitional2 = _config.transitional || transitionalDefaults;
      if (_config.timeoutErrorMessage) {
        timeoutErrorMessage = _config.timeoutErrorMessage;
      }
      reject(new AxiosError$1(
        timeoutErrorMessage,
        transitional2.clarifyTimeoutError ? AxiosError$1.ETIMEDOUT : AxiosError$1.ECONNABORTED,
        config,
        request
      ));
      request = null;
    };
    requestData === void 0 && requestHeaders.setContentType(null);
    if ("setRequestHeader" in request) {
      utils$1.forEach(requestHeaders.toJSON(), function setRequestHeader(val, key) {
        request.setRequestHeader(key, val);
      });
    }
    if (!utils$1.isUndefined(_config.withCredentials)) {
      request.withCredentials = !!_config.withCredentials;
    }
    if (responseType && responseType !== "json") {
      request.responseType = _config.responseType;
    }
    if (onDownloadProgress) {
      [downloadThrottled, flushDownload] = progressEventReducer(onDownloadProgress, true);
      request.addEventListener("progress", downloadThrottled);
    }
    if (onUploadProgress && request.upload) {
      [uploadThrottled, flushUpload] = progressEventReducer(onUploadProgress);
      request.upload.addEventListener("progress", uploadThrottled);
      request.upload.addEventListener("loadend", flushUpload);
    }
    if (_config.cancelToken || _config.signal) {
      onCanceled = (cancel) => {
        if (!request) {
          return;
        }
        reject(!cancel || cancel.type ? new CanceledError$1(null, config, request) : cancel);
        request.abort();
        request = null;
      };
      _config.cancelToken && _config.cancelToken.subscribe(onCanceled);
      if (_config.signal) {
        _config.signal.aborted ? onCanceled() : _config.signal.addEventListener("abort", onCanceled);
      }
    }
    const protocol = parseProtocol(_config.url);
    if (protocol && platform.protocols.indexOf(protocol) === -1) {
      reject(new AxiosError$1("Unsupported protocol " + protocol + ":", AxiosError$1.ERR_BAD_REQUEST, config));
      return;
    }
    request.send(requestData || null);
  });
};
const composeSignals = (signals, timeout) => {
  const { length } = signals = signals ? signals.filter(Boolean) : [];
  if (timeout || length) {
    let controller = new AbortController();
    let aborted;
    const onabort = function(reason) {
      if (!aborted) {
        aborted = true;
        unsubscribe2();
        const err = reason instanceof Error ? reason : this.reason;
        controller.abort(err instanceof AxiosError$1 ? err : new CanceledError$1(err instanceof Error ? err.message : err));
      }
    };
    let timer = timeout && setTimeout(() => {
      timer = null;
      onabort(new AxiosError$1(`timeout ${timeout} of ms exceeded`, AxiosError$1.ETIMEDOUT));
    }, timeout);
    const unsubscribe2 = () => {
      if (signals) {
        timer && clearTimeout(timer);
        timer = null;
        signals.forEach((signal2) => {
          signal2.unsubscribe ? signal2.unsubscribe(onabort) : signal2.removeEventListener("abort", onabort);
        });
        signals = null;
      }
    };
    signals.forEach((signal2) => signal2.addEventListener("abort", onabort));
    const { signal } = controller;
    signal.unsubscribe = () => utils$1.asap(unsubscribe2);
    return signal;
  }
};
const streamChunk = function* (chunk, chunkSize) {
  let len = chunk.byteLength;
  if (len < chunkSize) {
    yield chunk;
    return;
  }
  let pos = 0;
  let end;
  while (pos < len) {
    end = pos + chunkSize;
    yield chunk.slice(pos, end);
    pos = end;
  }
};
const readBytes = async function* (iterable, chunkSize) {
  for await (const chunk of readStream(iterable)) {
    yield* streamChunk(chunk, chunkSize);
  }
};
const readStream = async function* (stream) {
  if (stream[Symbol.asyncIterator]) {
    yield* stream;
    return;
  }
  const reader = stream.getReader();
  try {
    for (; ; ) {
      const { done, value } = await reader.read();
      if (done) {
        break;
      }
      yield value;
    }
  } finally {
    await reader.cancel();
  }
};
const trackStream = (stream, chunkSize, onProgress, onFinish) => {
  const iterator2 = readBytes(stream, chunkSize);
  let bytes = 0;
  let done;
  let _onFinish = (e) => {
    if (!done) {
      done = true;
      onFinish && onFinish(e);
    }
  };
  return new ReadableStream({
    async pull(controller) {
      try {
        const { done: done2, value } = await iterator2.next();
        if (done2) {
          _onFinish();
          controller.close();
          return;
        }
        let len = value.byteLength;
        if (onProgress) {
          let loadedBytes = bytes += len;
          onProgress(loadedBytes);
        }
        controller.enqueue(new Uint8Array(value));
      } catch (err) {
        _onFinish(err);
        throw err;
      }
    },
    cancel(reason) {
      _onFinish(reason);
      return iterator2.return();
    }
  }, {
    highWaterMark: 2
  });
};
const DEFAULT_CHUNK_SIZE = 64 * 1024;
const { isFunction } = utils$1;
const globalFetchAPI = (({ Request, Response }) => ({
  Request,
  Response
}))(utils$1.global);
const {
  ReadableStream: ReadableStream$1,
  TextEncoder
} = utils$1.global;
const test = (fn, ...args) => {
  try {
    return !!fn(...args);
  } catch (e) {
    return false;
  }
};
const factory = (env) => {
  env = utils$1.merge.call({
    skipUndefined: true
  }, globalFetchAPI, env);
  const { fetch: envFetch, Request, Response } = env;
  const isFetchSupported = envFetch ? isFunction(envFetch) : typeof fetch === "function";
  const isRequestSupported = isFunction(Request);
  const isResponseSupported = isFunction(Response);
  if (!isFetchSupported) {
    return false;
  }
  const isReadableStreamSupported = isFetchSupported && isFunction(ReadableStream$1);
  const encodeText = isFetchSupported && (typeof TextEncoder === "function" ? /* @__PURE__ */ ((encoder) => (str) => encoder.encode(str))(new TextEncoder()) : async (str) => new Uint8Array(await new Request(str).arrayBuffer()));
  const supportsRequestStream = isRequestSupported && isReadableStreamSupported && test(() => {
    let duplexAccessed = false;
    const hasContentType = new Request(platform.origin, {
      body: new ReadableStream$1(),
      method: "POST",
      get duplex() {
        duplexAccessed = true;
        return "half";
      }
    }).headers.has("Content-Type");
    return duplexAccessed && !hasContentType;
  });
  const supportsResponseStream = isResponseSupported && isReadableStreamSupported && test(() => utils$1.isReadableStream(new Response("").body));
  const resolvers = {
    stream: supportsResponseStream && ((res) => res.body)
  };
  isFetchSupported && (() => {
    ["text", "arrayBuffer", "blob", "formData", "stream"].forEach((type) => {
      !resolvers[type] && (resolvers[type] = (res, config) => {
        let method = res && res[type];
        if (method) {
          return method.call(res);
        }
        throw new AxiosError$1(`Response type '${type}' is not supported`, AxiosError$1.ERR_NOT_SUPPORT, config);
      });
    });
  })();
  const getBodyLength = async (body) => {
    if (body == null) {
      return 0;
    }
    if (utils$1.isBlob(body)) {
      return body.size;
    }
    if (utils$1.isSpecCompliantForm(body)) {
      const _request = new Request(platform.origin, {
        method: "POST",
        body
      });
      return (await _request.arrayBuffer()).byteLength;
    }
    if (utils$1.isArrayBufferView(body) || utils$1.isArrayBuffer(body)) {
      return body.byteLength;
    }
    if (utils$1.isURLSearchParams(body)) {
      body = body + "";
    }
    if (utils$1.isString(body)) {
      return (await encodeText(body)).byteLength;
    }
  };
  const resolveBodyLength = async (headers, body) => {
    const length = utils$1.toFiniteNumber(headers.getContentLength());
    return length == null ? getBodyLength(body) : length;
  };
  return async (config) => {
    let {
      url,
      method,
      data,
      signal,
      cancelToken,
      timeout,
      onDownloadProgress,
      onUploadProgress,
      responseType,
      headers,
      withCredentials = "same-origin",
      fetchOptions
    } = resolveConfig(config);
    let _fetch = envFetch || fetch;
    responseType = responseType ? (responseType + "").toLowerCase() : "text";
    let composedSignal = composeSignals([signal, cancelToken && cancelToken.toAbortSignal()], timeout);
    let request = null;
    const unsubscribe2 = composedSignal && composedSignal.unsubscribe && (() => {
      composedSignal.unsubscribe();
    });
    let requestContentLength;
    try {
      if (onUploadProgress && supportsRequestStream && method !== "get" && method !== "head" && (requestContentLength = await resolveBodyLength(headers, data)) !== 0) {
        let _request = new Request(url, {
          method: "POST",
          body: data,
          duplex: "half"
        });
        let contentTypeHeader;
        if (utils$1.isFormData(data) && (contentTypeHeader = _request.headers.get("content-type"))) {
          headers.setContentType(contentTypeHeader);
        }
        if (_request.body) {
          const [onProgress, flush] = progressEventDecorator(
            requestContentLength,
            progressEventReducer(asyncDecorator(onUploadProgress))
          );
          data = trackStream(_request.body, DEFAULT_CHUNK_SIZE, onProgress, flush);
        }
      }
      if (!utils$1.isString(withCredentials)) {
        withCredentials = withCredentials ? "include" : "omit";
      }
      const isCredentialsSupported = isRequestSupported && "credentials" in Request.prototype;
      const resolvedOptions = {
        ...fetchOptions,
        signal: composedSignal,
        method: method.toUpperCase(),
        headers: headers.normalize().toJSON(),
        body: data,
        duplex: "half",
        credentials: isCredentialsSupported ? withCredentials : void 0
      };
      request = isRequestSupported && new Request(url, resolvedOptions);
      let response = await (isRequestSupported ? _fetch(request, fetchOptions) : _fetch(url, resolvedOptions));
      const isStreamResponse = supportsResponseStream && (responseType === "stream" || responseType === "response");
      if (supportsResponseStream && (onDownloadProgress || isStreamResponse && unsubscribe2)) {
        const options = {};
        ["status", "statusText", "headers"].forEach((prop) => {
          options[prop] = response[prop];
        });
        const responseContentLength = utils$1.toFiniteNumber(response.headers.get("content-length"));
        const [onProgress, flush] = onDownloadProgress && progressEventDecorator(
          responseContentLength,
          progressEventReducer(asyncDecorator(onDownloadProgress), true)
        ) || [];
        response = new Response(
          trackStream(response.body, DEFAULT_CHUNK_SIZE, onProgress, () => {
            flush && flush();
            unsubscribe2 && unsubscribe2();
          }),
          options
        );
      }
      responseType = responseType || "text";
      let responseData = await resolvers[utils$1.findKey(resolvers, responseType) || "text"](response, config);
      !isStreamResponse && unsubscribe2 && unsubscribe2();
      return await new Promise((resolve2, reject) => {
        settle(resolve2, reject, {
          data: responseData,
          headers: AxiosHeaders$1.from(response.headers),
          status: response.status,
          statusText: response.statusText,
          config,
          request
        });
      });
    } catch (err) {
      unsubscribe2 && unsubscribe2();
      if (err && err.name === "TypeError" && /Load failed|fetch/i.test(err.message)) {
        throw Object.assign(
          new AxiosError$1("Network Error", AxiosError$1.ERR_NETWORK, config, request),
          {
            cause: err.cause || err
          }
        );
      }
      throw AxiosError$1.from(err, err && err.code, config, request);
    }
  };
};
const seedCache = /* @__PURE__ */ new Map();
const getFetch = (config) => {
  let env = config && config.env || {};
  const { fetch: fetch2, Request, Response } = env;
  const seeds = [
    Request,
    Response,
    fetch2
  ];
  let len = seeds.length, i = len, seed, target, map2 = seedCache;
  while (i--) {
    seed = seeds[i];
    target = map2.get(seed);
    target === void 0 && map2.set(seed, target = i ? /* @__PURE__ */ new Map() : factory(env));
    map2 = target;
  }
  return target;
};
getFetch();
const knownAdapters = {
  http: httpAdapter,
  xhr: xhrAdapter,
  fetch: {
    get: getFetch
  }
};
utils$1.forEach(knownAdapters, (fn, value) => {
  if (fn) {
    try {
      Object.defineProperty(fn, "name", { value });
    } catch (e) {
    }
    Object.defineProperty(fn, "adapterName", { value });
  }
});
const renderReason = (reason) => `- ${reason}`;
const isResolvedHandle = (adapter) => utils$1.isFunction(adapter) || adapter === null || adapter === false;
function getAdapter$1(adapters2, config) {
  adapters2 = utils$1.isArray(adapters2) ? adapters2 : [adapters2];
  const { length } = adapters2;
  let nameOrAdapter;
  let adapter;
  const rejectedReasons = {};
  for (let i = 0; i < length; i++) {
    nameOrAdapter = adapters2[i];
    let id;
    adapter = nameOrAdapter;
    if (!isResolvedHandle(nameOrAdapter)) {
      adapter = knownAdapters[(id = String(nameOrAdapter)).toLowerCase()];
      if (adapter === void 0) {
        throw new AxiosError$1(`Unknown adapter '${id}'`);
      }
    }
    if (adapter && (utils$1.isFunction(adapter) || (adapter = adapter.get(config)))) {
      break;
    }
    rejectedReasons[id || "#" + i] = adapter;
  }
  if (!adapter) {
    const reasons = Object.entries(rejectedReasons).map(
      ([id, state]) => `adapter ${id} ` + (state === false ? "is not supported by the environment" : "is not available in the build")
    );
    let s = length ? reasons.length > 1 ? "since :\n" + reasons.map(renderReason).join("\n") : " " + renderReason(reasons[0]) : "as no adapter specified";
    throw new AxiosError$1(
      `There is no suitable adapter to dispatch the request ` + s,
      "ERR_NOT_SUPPORT"
    );
  }
  return adapter;
}
const adapters = {
  /**
   * Resolve an adapter from a list of adapter names or functions.
   * @type {Function}
   */
  getAdapter: getAdapter$1,
  /**
   * Exposes all known adapters
   * @type {Object<string, Function|Object>}
   */
  adapters: knownAdapters
};
function throwIfCancellationRequested(config) {
  if (config.cancelToken) {
    config.cancelToken.throwIfRequested();
  }
  if (config.signal && config.signal.aborted) {
    throw new CanceledError$1(null, config);
  }
}
function dispatchRequest(config) {
  throwIfCancellationRequested(config);
  config.headers = AxiosHeaders$1.from(config.headers);
  config.data = transformData.call(
    config,
    config.transformRequest
  );
  if (["post", "put", "patch"].indexOf(config.method) !== -1) {
    config.headers.setContentType("application/x-www-form-urlencoded", false);
  }
  const adapter = adapters.getAdapter(config.adapter || defaults.adapter, config);
  return adapter(config).then(function onAdapterResolution(response) {
    throwIfCancellationRequested(config);
    response.data = transformData.call(
      config,
      config.transformResponse,
      response
    );
    response.headers = AxiosHeaders$1.from(response.headers);
    return response;
  }, function onAdapterRejection(reason) {
    if (!isCancel$1(reason)) {
      throwIfCancellationRequested(config);
      if (reason && reason.response) {
        reason.response.data = transformData.call(
          config,
          config.transformResponse,
          reason.response
        );
        reason.response.headers = AxiosHeaders$1.from(reason.response.headers);
      }
    }
    return Promise.reject(reason);
  });
}
const VERSION$1 = "1.13.2";
const validators$1 = {};
["object", "boolean", "number", "function", "string", "symbol"].forEach((type, i) => {
  validators$1[type] = function validator2(thing) {
    return typeof thing === type || "a" + (i < 1 ? "n " : " ") + type;
  };
});
const deprecatedWarnings = {};
validators$1.transitional = function transitional(validator2, version2, message) {
  function formatMessage(opt, desc) {
    return "[Axios v" + VERSION$1 + "] Transitional option '" + opt + "'" + desc + (message ? ". " + message : "");
  }
  return (value, opt, opts) => {
    if (validator2 === false) {
      throw new AxiosError$1(
        formatMessage(opt, " has been removed" + (version2 ? " in " + version2 : "")),
        AxiosError$1.ERR_DEPRECATED
      );
    }
    if (version2 && !deprecatedWarnings[opt]) {
      deprecatedWarnings[opt] = true;
      console.warn(
        formatMessage(
          opt,
          " has been deprecated since v" + version2 + " and will be removed in the near future"
        )
      );
    }
    return validator2 ? validator2(value, opt, opts) : true;
  };
};
validators$1.spelling = function spelling(correctSpelling) {
  return (value, opt) => {
    console.warn(`${opt} is likely a misspelling of ${correctSpelling}`);
    return true;
  };
};
function assertOptions(options, schema, allowUnknown) {
  if (typeof options !== "object") {
    throw new AxiosError$1("options must be an object", AxiosError$1.ERR_BAD_OPTION_VALUE);
  }
  const keys = Object.keys(options);
  let i = keys.length;
  while (i-- > 0) {
    const opt = keys[i];
    const validator2 = schema[opt];
    if (validator2) {
      const value = options[opt];
      const result = value === void 0 || validator2(value, opt, options);
      if (result !== true) {
        throw new AxiosError$1("option " + opt + " must be " + result, AxiosError$1.ERR_BAD_OPTION_VALUE);
      }
      continue;
    }
    if (allowUnknown !== true) {
      throw new AxiosError$1("Unknown option " + opt, AxiosError$1.ERR_BAD_OPTION);
    }
  }
}
const validator = {
  assertOptions,
  validators: validators$1
};
const validators = validator.validators;
let Axios$1 = class Axios {
  constructor(instanceConfig) {
    this.defaults = instanceConfig || {};
    this.interceptors = {
      request: new InterceptorManager(),
      response: new InterceptorManager()
    };
  }
  /**
   * Dispatch a request
   *
   * @param {String|Object} configOrUrl The config specific for this request (merged with this.defaults)
   * @param {?Object} config
   *
   * @returns {Promise} The Promise to be fulfilled
   */
  async request(configOrUrl, config) {
    try {
      return await this._request(configOrUrl, config);
    } catch (err) {
      if (err instanceof Error) {
        let dummy = {};
        Error.captureStackTrace ? Error.captureStackTrace(dummy) : dummy = new Error();
        const stack2 = dummy.stack ? dummy.stack.replace(/^.+\n/, "") : "";
        try {
          if (!err.stack) {
            err.stack = stack2;
          } else if (stack2 && !String(err.stack).endsWith(stack2.replace(/^.+\n.+\n/, ""))) {
            err.stack += "\n" + stack2;
          }
        } catch (e) {
        }
      }
      throw err;
    }
  }
  _request(configOrUrl, config) {
    if (typeof configOrUrl === "string") {
      config = config || {};
      config.url = configOrUrl;
    } else {
      config = configOrUrl || {};
    }
    config = mergeConfig$1(this.defaults, config);
    const { transitional: transitional2, paramsSerializer, headers } = config;
    if (transitional2 !== void 0) {
      validator.assertOptions(transitional2, {
        silentJSONParsing: validators.transitional(validators.boolean),
        forcedJSONParsing: validators.transitional(validators.boolean),
        clarifyTimeoutError: validators.transitional(validators.boolean)
      }, false);
    }
    if (paramsSerializer != null) {
      if (utils$1.isFunction(paramsSerializer)) {
        config.paramsSerializer = {
          serialize: paramsSerializer
        };
      } else {
        validator.assertOptions(paramsSerializer, {
          encode: validators.function,
          serialize: validators.function
        }, true);
      }
    }
    if (config.allowAbsoluteUrls !== void 0) ;
    else if (this.defaults.allowAbsoluteUrls !== void 0) {
      config.allowAbsoluteUrls = this.defaults.allowAbsoluteUrls;
    } else {
      config.allowAbsoluteUrls = true;
    }
    validator.assertOptions(config, {
      baseUrl: validators.spelling("baseURL"),
      withXsrfToken: validators.spelling("withXSRFToken")
    }, true);
    config.method = (config.method || this.defaults.method || "get").toLowerCase();
    let contextHeaders = headers && utils$1.merge(
      headers.common,
      headers[config.method]
    );
    headers && utils$1.forEach(
      ["delete", "get", "head", "post", "put", "patch", "common"],
      (method) => {
        delete headers[method];
      }
    );
    config.headers = AxiosHeaders$1.concat(contextHeaders, headers);
    const requestInterceptorChain = [];
    let synchronousRequestInterceptors = true;
    this.interceptors.request.forEach(function unshiftRequestInterceptors(interceptor) {
      if (typeof interceptor.runWhen === "function" && interceptor.runWhen(config) === false) {
        return;
      }
      synchronousRequestInterceptors = synchronousRequestInterceptors && interceptor.synchronous;
      requestInterceptorChain.unshift(interceptor.fulfilled, interceptor.rejected);
    });
    const responseInterceptorChain = [];
    this.interceptors.response.forEach(function pushResponseInterceptors(interceptor) {
      responseInterceptorChain.push(interceptor.fulfilled, interceptor.rejected);
    });
    let promise;
    let i = 0;
    let len;
    if (!synchronousRequestInterceptors) {
      const chain = [dispatchRequest.bind(this), void 0];
      chain.unshift(...requestInterceptorChain);
      chain.push(...responseInterceptorChain);
      len = chain.length;
      promise = Promise.resolve(config);
      while (i < len) {
        promise = promise.then(chain[i++], chain[i++]);
      }
      return promise;
    }
    len = requestInterceptorChain.length;
    let newConfig = config;
    while (i < len) {
      const onFulfilled = requestInterceptorChain[i++];
      const onRejected = requestInterceptorChain[i++];
      try {
        newConfig = onFulfilled(newConfig);
      } catch (error) {
        onRejected.call(this, error);
        break;
      }
    }
    try {
      promise = dispatchRequest.call(this, newConfig);
    } catch (error) {
      return Promise.reject(error);
    }
    i = 0;
    len = responseInterceptorChain.length;
    while (i < len) {
      promise = promise.then(responseInterceptorChain[i++], responseInterceptorChain[i++]);
    }
    return promise;
  }
  getUri(config) {
    config = mergeConfig$1(this.defaults, config);
    const fullPath = buildFullPath(config.baseURL, config.url, config.allowAbsoluteUrls);
    return buildURL(fullPath, config.params, config.paramsSerializer);
  }
};
utils$1.forEach(["delete", "get", "head", "options"], function forEachMethodNoData(method) {
  Axios$1.prototype[method] = function(url, config) {
    return this.request(mergeConfig$1(config || {}, {
      method,
      url,
      data: (config || {}).data
    }));
  };
});
utils$1.forEach(["post", "put", "patch"], function forEachMethodWithData(method) {
  function generateHTTPMethod(isForm) {
    return function httpMethod(url, data, config) {
      return this.request(mergeConfig$1(config || {}, {
        method,
        headers: isForm ? {
          "Content-Type": "multipart/form-data"
        } : {},
        url,
        data
      }));
    };
  }
  Axios$1.prototype[method] = generateHTTPMethod();
  Axios$1.prototype[method + "Form"] = generateHTTPMethod(true);
});
let CancelToken$1 = class CancelToken {
  constructor(executor) {
    if (typeof executor !== "function") {
      throw new TypeError("executor must be a function.");
    }
    let resolvePromise;
    this.promise = new Promise(function promiseExecutor(resolve2) {
      resolvePromise = resolve2;
    });
    const token2 = this;
    this.promise.then((cancel) => {
      if (!token2._listeners) return;
      let i = token2._listeners.length;
      while (i-- > 0) {
        token2._listeners[i](cancel);
      }
      token2._listeners = null;
    });
    this.promise.then = (onfulfilled) => {
      let _resolve;
      const promise = new Promise((resolve2) => {
        token2.subscribe(resolve2);
        _resolve = resolve2;
      }).then(onfulfilled);
      promise.cancel = function reject() {
        token2.unsubscribe(_resolve);
      };
      return promise;
    };
    executor(function cancel(message, config, request) {
      if (token2.reason) {
        return;
      }
      token2.reason = new CanceledError$1(message, config, request);
      resolvePromise(token2.reason);
    });
  }
  /**
   * Throws a `CanceledError` if cancellation has been requested.
   */
  throwIfRequested() {
    if (this.reason) {
      throw this.reason;
    }
  }
  /**
   * Subscribe to the cancel signal
   */
  subscribe(listener) {
    if (this.reason) {
      listener(this.reason);
      return;
    }
    if (this._listeners) {
      this._listeners.push(listener);
    } else {
      this._listeners = [listener];
    }
  }
  /**
   * Unsubscribe from the cancel signal
   */
  unsubscribe(listener) {
    if (!this._listeners) {
      return;
    }
    const index = this._listeners.indexOf(listener);
    if (index !== -1) {
      this._listeners.splice(index, 1);
    }
  }
  toAbortSignal() {
    const controller = new AbortController();
    const abort = (err) => {
      controller.abort(err);
    };
    this.subscribe(abort);
    controller.signal.unsubscribe = () => this.unsubscribe(abort);
    return controller.signal;
  }
  /**
   * Returns an object that contains a new `CancelToken` and a function that, when called,
   * cancels the `CancelToken`.
   */
  static source() {
    let cancel;
    const token2 = new CancelToken(function executor(c) {
      cancel = c;
    });
    return {
      token: token2,
      cancel
    };
  }
};
function spread$1(callback) {
  return function wrap(arr) {
    return callback.apply(null, arr);
  };
}
function isAxiosError$1(payload) {
  return utils$1.isObject(payload) && payload.isAxiosError === true;
}
const HttpStatusCode$1 = {
  Continue: 100,
  SwitchingProtocols: 101,
  Processing: 102,
  EarlyHints: 103,
  Ok: 200,
  Created: 201,
  Accepted: 202,
  NonAuthoritativeInformation: 203,
  NoContent: 204,
  ResetContent: 205,
  PartialContent: 206,
  MultiStatus: 207,
  AlreadyReported: 208,
  ImUsed: 226,
  MultipleChoices: 300,
  MovedPermanently: 301,
  Found: 302,
  SeeOther: 303,
  NotModified: 304,
  UseProxy: 305,
  Unused: 306,
  TemporaryRedirect: 307,
  PermanentRedirect: 308,
  BadRequest: 400,
  Unauthorized: 401,
  PaymentRequired: 402,
  Forbidden: 403,
  NotFound: 404,
  MethodNotAllowed: 405,
  NotAcceptable: 406,
  ProxyAuthenticationRequired: 407,
  RequestTimeout: 408,
  Conflict: 409,
  Gone: 410,
  LengthRequired: 411,
  PreconditionFailed: 412,
  PayloadTooLarge: 413,
  UriTooLong: 414,
  UnsupportedMediaType: 415,
  RangeNotSatisfiable: 416,
  ExpectationFailed: 417,
  ImATeapot: 418,
  MisdirectedRequest: 421,
  UnprocessableEntity: 422,
  Locked: 423,
  FailedDependency: 424,
  TooEarly: 425,
  UpgradeRequired: 426,
  PreconditionRequired: 428,
  TooManyRequests: 429,
  RequestHeaderFieldsTooLarge: 431,
  UnavailableForLegalReasons: 451,
  InternalServerError: 500,
  NotImplemented: 501,
  BadGateway: 502,
  ServiceUnavailable: 503,
  GatewayTimeout: 504,
  HttpVersionNotSupported: 505,
  VariantAlsoNegotiates: 506,
  InsufficientStorage: 507,
  LoopDetected: 508,
  NotExtended: 510,
  NetworkAuthenticationRequired: 511,
  WebServerIsDown: 521,
  ConnectionTimedOut: 522,
  OriginIsUnreachable: 523,
  TimeoutOccurred: 524,
  SslHandshakeFailed: 525,
  InvalidSslCertificate: 526
};
Object.entries(HttpStatusCode$1).forEach(([key, value]) => {
  HttpStatusCode$1[value] = key;
});
function createInstance(defaultConfig) {
  const context = new Axios$1(defaultConfig);
  const instance = bind(Axios$1.prototype.request, context);
  utils$1.extend(instance, Axios$1.prototype, context, { allOwnKeys: true });
  utils$1.extend(instance, context, null, { allOwnKeys: true });
  instance.create = function create2(instanceConfig) {
    return createInstance(mergeConfig$1(defaultConfig, instanceConfig));
  };
  return instance;
}
const axios = createInstance(defaults);
axios.Axios = Axios$1;
axios.CanceledError = CanceledError$1;
axios.CancelToken = CancelToken$1;
axios.isCancel = isCancel$1;
axios.VERSION = VERSION$1;
axios.toFormData = toFormData$1;
axios.AxiosError = AxiosError$1;
axios.Cancel = axios.CanceledError;
axios.all = function all(promises) {
  return Promise.all(promises);
};
axios.spread = spread$1;
axios.isAxiosError = isAxiosError$1;
axios.mergeConfig = mergeConfig$1;
axios.AxiosHeaders = AxiosHeaders$1;
axios.formToJSON = (thing) => formDataToJSON(utils$1.isHTMLForm(thing) ? new FormData(thing) : thing);
axios.getAdapter = adapters.getAdapter;
axios.HttpStatusCode = HttpStatusCode$1;
axios.default = axios;
const {
  Axios: Axios2,
  AxiosError,
  CanceledError,
  isCancel,
  CancelToken: CancelToken2,
  VERSION,
  all: all2,
  Cancel,
  isAxiosError,
  spread,
  toFormData,
  AxiosHeaders: AxiosHeaders2,
  HttpStatusCode,
  formToJSON,
  getAdapter,
  mergeConfig
} = axios;
const linkToRemoteBase = (service) => "/remote.php/" + service;
const generateRemoteUrl = (service, options) => {
  const baseURL = getBaseUrl();
  return baseURL + linkToRemoteBase(service);
};
const generateOcsUrl = (url, params, options) => {
  const allOptions = Object.assign({
    ocsVersion: 2
  }, {});
  const version2 = allOptions.ocsVersion === 1 ? 1 : 2;
  const baseURL = getBaseUrl();
  return baseURL + "/ocs/v" + version2 + ".php" + _generateUrlPath(url, params);
};
const _generateUrlPath = (url, params, options) => {
  const allOptions = Object.assign({
    escape: true
  }, {});
  const _build = function(text2, vars) {
    vars = vars || {};
    return text2.replace(
      /{([^{}]*)}/g,
      function(a, b) {
        const r = vars[b];
        if (allOptions.escape) {
          return typeof r === "string" || typeof r === "number" ? encodeURIComponent(r.toString()) : encodeURIComponent(a);
        } else {
          return typeof r === "string" || typeof r === "number" ? r.toString() : a;
        }
      }
    );
  };
  if (url.charAt(0) !== "/") {
    url = "/" + url;
  }
  return _build(url, params || {});
};
const generateUrl = (url, params, options) => {
  const allOptions = Object.assign({
    noRewrite: false
  }, {});
  const baseOrRootURL = getRootUrl();
  if (window?.OC?.config?.modRewriteWorking === true && !allOptions.noRewrite) {
    return baseOrRootURL + _generateUrlPath(url, params);
  }
  return baseOrRootURL + "/index.php" + _generateUrlPath(url, params);
};
const imagePath = (app, file) => {
  if (!file.includes(".")) {
    return generateFilePath(app, "img", `${file}.svg`);
  }
  return generateFilePath(app, "img", file);
};
const generateFilePath = (app, type, file) => {
  const isCore = window?.OC?.coreApps?.includes(app) ?? false;
  const isPHP = file.slice(-3) === "php";
  let link = getRootUrl();
  if (isPHP && !isCore) {
    link += `/index.php/apps/${app}`;
    {
      link += `/${encodeURI(type)}`;
    }
    if (file !== "index.php") {
      link += `/${file}`;
    }
  } else if (!isPHP && !isCore) {
    link = getAppRootUrl(app);
    {
      link += `/${type}/`;
    }
    if (link.at(-1) !== "/") {
      link += "/";
    }
    link += file;
  } else {
    {
      link += `/${app}`;
    }
    {
      link += `/${type}`;
    }
    link += `/${file}`;
  }
  return link;
};
const getBaseUrl = () => window.location.protocol + "//" + window.location.host + getRootUrl();
function getRootUrl() {
  let webroot = window._oc_webroot;
  if (typeof webroot === "undefined") {
    webroot = location.pathname;
    const pos = webroot.indexOf("/index.php/");
    if (pos !== -1) {
      webroot = webroot.slice(0, pos);
    } else {
      const index = webroot.indexOf("/", 1);
      webroot = webroot.slice(0, index > 0 ? index : void 0);
    }
  }
  return webroot;
}
function getAppRootUrl(app) {
  const webroots = window._oc_appswebroots ?? {};
  return webroots[app] ?? "";
}
/*!
 * SPDX-License-Identifier: GPL-3.0-or-later
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 */
const client = axios.create({
  headers: {
    requesttoken: getRequestToken() ?? "",
    "X-Requested-With": "XMLHttpRequest"
  }
});
onRequestTokenUpdate((token2) => {
  client.defaults.headers.requesttoken = token2;
});
const cancelableClient = Object.assign(client, {
  CancelToken: axios.CancelToken,
  isCancel: axios.isCancel
});
const RETRY_KEY = Symbol("csrf-retry");
function onCsrfTokenError(axios2) {
  return async (error) => {
    if (!isAxiosError(error)) {
      throw error;
    }
    const { config, response, request } = error;
    const responseURL = request?.responseURL;
    if (config && !config[RETRY_KEY] && response?.status === 412 && response?.data?.message === "CSRF check failed") {
      console.warn(`Request to ${responseURL} failed because of a CSRF mismatch. Fetching a new token`);
      const { data: { token: token2 } } = await axios2.get(generateUrl("/csrftoken"));
      console.debug(`New request token ${token2} fetched`);
      axios2.defaults.headers.requesttoken = token2;
      return axios2({
        ...config,
        headers: {
          ...config.headers,
          requesttoken: token2
        },
        [RETRY_KEY]: true
      });
    }
    throw error;
  };
}
const RETRY_DELAY_KEY = Symbol("retryDelay");
function onMaintenanceModeError(axios2) {
  return async (error) => {
    if (!isAxiosError(error)) {
      throw error;
    }
    const { config, response, request } = error;
    const responseURL = request?.responseURL;
    const status = response?.status;
    const headers = response?.headers;
    let retryDelay = typeof config?.[RETRY_DELAY_KEY] === "number" ? config?.[RETRY_DELAY_KEY] : 1;
    if (status === 503 && headers?.["x-nextcloud-maintenance-mode"] === "1" && config?.retryIfMaintenanceMode) {
      retryDelay *= 2;
      if (retryDelay > 32) {
        console.error("Retry delay exceeded one minute, giving up.", { responseURL });
        throw error;
      }
      console.warn(`Request to ${responseURL} failed because of maintenance mode. Retrying in ${retryDelay}s`);
      await new Promise((resolve2) => {
        setTimeout(resolve2, retryDelay * 1e3);
      });
      return axios2({
        ...config,
        [RETRY_DELAY_KEY]: retryDelay
      });
    }
    throw error;
  };
}
async function onNotLoggedInError(error) {
  if (isAxiosError(error)) {
    const { config, response, request } = error;
    const responseURL = request?.responseURL;
    const status = response?.status;
    if (status === 401 && response?.data?.message === "Current user is not logged in" && config?.reloadExpiredSession && window?.location) {
      console.error(`Request to ${responseURL} failed because the user session expired. Reloading the page …`);
      window.location.reload();
    }
  }
  throw error;
}
cancelableClient.interceptors.response.use((r) => r, onCsrfTokenError(cancelableClient));
cancelableClient.interceptors.response.use((r) => r, onMaintenanceModeError(cancelableClient));
cancelableClient.interceptors.response.use((r) => r, onNotLoggedInError);
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
/*! @license DOMPurify 3.3.1 | (c) Cure53 and other contributors | Released under the Apache license 2.0 and Mozilla Public License 2.0 | github.com/cure53/DOMPurify/blob/3.3.1/LICENSE */
const {
  entries,
  setPrototypeOf,
  isFrozen,
  getPrototypeOf,
  getOwnPropertyDescriptor
} = Object;
let {
  freeze,
  seal,
  create
} = Object;
let {
  apply,
  construct
} = typeof Reflect !== "undefined" && Reflect;
if (!freeze) {
  freeze = function freeze2(x) {
    return x;
  };
}
if (!seal) {
  seal = function seal2(x) {
    return x;
  };
}
if (!apply) {
  apply = function apply2(func, thisArg) {
    for (var _len = arguments.length, args = new Array(_len > 2 ? _len - 2 : 0), _key = 2; _key < _len; _key++) {
      args[_key - 2] = arguments[_key];
    }
    return func.apply(thisArg, args);
  };
}
if (!construct) {
  construct = function construct2(Func) {
    for (var _len2 = arguments.length, args = new Array(_len2 > 1 ? _len2 - 1 : 0), _key2 = 1; _key2 < _len2; _key2++) {
      args[_key2 - 1] = arguments[_key2];
    }
    return new Func(...args);
  };
}
const arrayForEach = unapply(Array.prototype.forEach);
const arrayLastIndexOf = unapply(Array.prototype.lastIndexOf);
const arrayPop = unapply(Array.prototype.pop);
const arrayPush = unapply(Array.prototype.push);
const arraySplice = unapply(Array.prototype.splice);
const stringToLowerCase = unapply(String.prototype.toLowerCase);
const stringToString = unapply(String.prototype.toString);
const stringMatch = unapply(String.prototype.match);
const stringReplace = unapply(String.prototype.replace);
const stringIndexOf = unapply(String.prototype.indexOf);
const stringTrim = unapply(String.prototype.trim);
const objectHasOwnProperty = unapply(Object.prototype.hasOwnProperty);
const regExpTest = unapply(RegExp.prototype.test);
const typeErrorCreate = unconstruct(TypeError);
function unapply(func) {
  return function(thisArg) {
    if (thisArg instanceof RegExp) {
      thisArg.lastIndex = 0;
    }
    for (var _len3 = arguments.length, args = new Array(_len3 > 1 ? _len3 - 1 : 0), _key3 = 1; _key3 < _len3; _key3++) {
      args[_key3 - 1] = arguments[_key3];
    }
    return apply(func, thisArg, args);
  };
}
function unconstruct(Func) {
  return function() {
    for (var _len4 = arguments.length, args = new Array(_len4), _key4 = 0; _key4 < _len4; _key4++) {
      args[_key4] = arguments[_key4];
    }
    return construct(Func, args);
  };
}
function addToSet(set, array) {
  let transformCaseFunc = arguments.length > 2 && arguments[2] !== void 0 ? arguments[2] : stringToLowerCase;
  if (setPrototypeOf) {
    setPrototypeOf(set, null);
  }
  let l = array.length;
  while (l--) {
    let element = array[l];
    if (typeof element === "string") {
      const lcElement = transformCaseFunc(element);
      if (lcElement !== element) {
        if (!isFrozen(array)) {
          array[l] = lcElement;
        }
        element = lcElement;
      }
    }
    set[element] = true;
  }
  return set;
}
function cleanArray(array) {
  for (let index = 0; index < array.length; index++) {
    const isPropertyExist = objectHasOwnProperty(array, index);
    if (!isPropertyExist) {
      array[index] = null;
    }
  }
  return array;
}
function clone(object) {
  const newObject = create(null);
  for (const [property, value] of entries(object)) {
    const isPropertyExist = objectHasOwnProperty(object, property);
    if (isPropertyExist) {
      if (Array.isArray(value)) {
        newObject[property] = cleanArray(value);
      } else if (value && typeof value === "object" && value.constructor === Object) {
        newObject[property] = clone(value);
      } else {
        newObject[property] = value;
      }
    }
  }
  return newObject;
}
function lookupGetter(object, prop) {
  while (object !== null) {
    const desc = getOwnPropertyDescriptor(object, prop);
    if (desc) {
      if (desc.get) {
        return unapply(desc.get);
      }
      if (typeof desc.value === "function") {
        return unapply(desc.value);
      }
    }
    object = getPrototypeOf(object);
  }
  function fallbackValue() {
    return null;
  }
  return fallbackValue;
}
const html$1 = freeze(["a", "abbr", "acronym", "address", "area", "article", "aside", "audio", "b", "bdi", "bdo", "big", "blink", "blockquote", "body", "br", "button", "canvas", "caption", "center", "cite", "code", "col", "colgroup", "content", "data", "datalist", "dd", "decorator", "del", "details", "dfn", "dialog", "dir", "div", "dl", "dt", "element", "em", "fieldset", "figcaption", "figure", "font", "footer", "form", "h1", "h2", "h3", "h4", "h5", "h6", "head", "header", "hgroup", "hr", "html", "i", "img", "input", "ins", "kbd", "label", "legend", "li", "main", "map", "mark", "marquee", "menu", "menuitem", "meter", "nav", "nobr", "ol", "optgroup", "option", "output", "p", "picture", "pre", "progress", "q", "rp", "rt", "ruby", "s", "samp", "search", "section", "select", "shadow", "slot", "small", "source", "spacer", "span", "strike", "strong", "style", "sub", "summary", "sup", "table", "tbody", "td", "template", "textarea", "tfoot", "th", "thead", "time", "tr", "track", "tt", "u", "ul", "var", "video", "wbr"]);
const svg$1 = freeze(["svg", "a", "altglyph", "altglyphdef", "altglyphitem", "animatecolor", "animatemotion", "animatetransform", "circle", "clippath", "defs", "desc", "ellipse", "enterkeyhint", "exportparts", "filter", "font", "g", "glyph", "glyphref", "hkern", "image", "inputmode", "line", "lineargradient", "marker", "mask", "metadata", "mpath", "part", "path", "pattern", "polygon", "polyline", "radialgradient", "rect", "stop", "style", "switch", "symbol", "text", "textpath", "title", "tref", "tspan", "view", "vkern"]);
const svgFilters = freeze(["feBlend", "feColorMatrix", "feComponentTransfer", "feComposite", "feConvolveMatrix", "feDiffuseLighting", "feDisplacementMap", "feDistantLight", "feDropShadow", "feFlood", "feFuncA", "feFuncB", "feFuncG", "feFuncR", "feGaussianBlur", "feImage", "feMerge", "feMergeNode", "feMorphology", "feOffset", "fePointLight", "feSpecularLighting", "feSpotLight", "feTile", "feTurbulence"]);
const svgDisallowed = freeze(["animate", "color-profile", "cursor", "discard", "font-face", "font-face-format", "font-face-name", "font-face-src", "font-face-uri", "foreignobject", "hatch", "hatchpath", "mesh", "meshgradient", "meshpatch", "meshrow", "missing-glyph", "script", "set", "solidcolor", "unknown", "use"]);
const mathMl$1 = freeze(["math", "menclose", "merror", "mfenced", "mfrac", "mglyph", "mi", "mlabeledtr", "mmultiscripts", "mn", "mo", "mover", "mpadded", "mphantom", "mroot", "mrow", "ms", "mspace", "msqrt", "mstyle", "msub", "msup", "msubsup", "mtable", "mtd", "mtext", "mtr", "munder", "munderover", "mprescripts"]);
const mathMlDisallowed = freeze(["maction", "maligngroup", "malignmark", "mlongdiv", "mscarries", "mscarry", "msgroup", "mstack", "msline", "msrow", "semantics", "annotation", "annotation-xml", "mprescripts", "none"]);
const text = freeze(["#text"]);
const html = freeze(["accept", "action", "align", "alt", "autocapitalize", "autocomplete", "autopictureinpicture", "autoplay", "background", "bgcolor", "border", "capture", "cellpadding", "cellspacing", "checked", "cite", "class", "clear", "color", "cols", "colspan", "controls", "controlslist", "coords", "crossorigin", "datetime", "decoding", "default", "dir", "disabled", "disablepictureinpicture", "disableremoteplayback", "download", "draggable", "enctype", "enterkeyhint", "exportparts", "face", "for", "headers", "height", "hidden", "high", "href", "hreflang", "id", "inert", "inputmode", "integrity", "ismap", "kind", "label", "lang", "list", "loading", "loop", "low", "max", "maxlength", "media", "method", "min", "minlength", "multiple", "muted", "name", "nonce", "noshade", "novalidate", "nowrap", "open", "optimum", "part", "pattern", "placeholder", "playsinline", "popover", "popovertarget", "popovertargetaction", "poster", "preload", "pubdate", "radiogroup", "readonly", "rel", "required", "rev", "reversed", "role", "rows", "rowspan", "spellcheck", "scope", "selected", "shape", "size", "sizes", "slot", "span", "srclang", "start", "src", "srcset", "step", "style", "summary", "tabindex", "title", "translate", "type", "usemap", "valign", "value", "width", "wrap", "xmlns", "slot"]);
const svg = freeze(["accent-height", "accumulate", "additive", "alignment-baseline", "amplitude", "ascent", "attributename", "attributetype", "azimuth", "basefrequency", "baseline-shift", "begin", "bias", "by", "class", "clip", "clippathunits", "clip-path", "clip-rule", "color", "color-interpolation", "color-interpolation-filters", "color-profile", "color-rendering", "cx", "cy", "d", "dx", "dy", "diffuseconstant", "direction", "display", "divisor", "dur", "edgemode", "elevation", "end", "exponent", "fill", "fill-opacity", "fill-rule", "filter", "filterunits", "flood-color", "flood-opacity", "font-family", "font-size", "font-size-adjust", "font-stretch", "font-style", "font-variant", "font-weight", "fx", "fy", "g1", "g2", "glyph-name", "glyphref", "gradientunits", "gradienttransform", "height", "href", "id", "image-rendering", "in", "in2", "intercept", "k", "k1", "k2", "k3", "k4", "kerning", "keypoints", "keysplines", "keytimes", "lang", "lengthadjust", "letter-spacing", "kernelmatrix", "kernelunitlength", "lighting-color", "local", "marker-end", "marker-mid", "marker-start", "markerheight", "markerunits", "markerwidth", "maskcontentunits", "maskunits", "max", "mask", "mask-type", "media", "method", "mode", "min", "name", "numoctaves", "offset", "operator", "opacity", "order", "orient", "orientation", "origin", "overflow", "paint-order", "path", "pathlength", "patterncontentunits", "patterntransform", "patternunits", "points", "preservealpha", "preserveaspectratio", "primitiveunits", "r", "rx", "ry", "radius", "refx", "refy", "repeatcount", "repeatdur", "restart", "result", "rotate", "scale", "seed", "shape-rendering", "slope", "specularconstant", "specularexponent", "spreadmethod", "startoffset", "stddeviation", "stitchtiles", "stop-color", "stop-opacity", "stroke-dasharray", "stroke-dashoffset", "stroke-linecap", "stroke-linejoin", "stroke-miterlimit", "stroke-opacity", "stroke", "stroke-width", "style", "surfacescale", "systemlanguage", "tabindex", "tablevalues", "targetx", "targety", "transform", "transform-origin", "text-anchor", "text-decoration", "text-rendering", "textlength", "type", "u1", "u2", "unicode", "values", "viewbox", "visibility", "version", "vert-adv-y", "vert-origin-x", "vert-origin-y", "width", "word-spacing", "wrap", "writing-mode", "xchannelselector", "ychannelselector", "x", "x1", "x2", "xmlns", "y", "y1", "y2", "z", "zoomandpan"]);
const mathMl = freeze(["accent", "accentunder", "align", "bevelled", "close", "columnsalign", "columnlines", "columnspan", "denomalign", "depth", "dir", "display", "displaystyle", "encoding", "fence", "frame", "height", "href", "id", "largeop", "length", "linethickness", "lspace", "lquote", "mathbackground", "mathcolor", "mathsize", "mathvariant", "maxsize", "minsize", "movablelimits", "notation", "numalign", "open", "rowalign", "rowlines", "rowspacing", "rowspan", "rspace", "rquote", "scriptlevel", "scriptminsize", "scriptsizemultiplier", "selection", "separator", "separators", "stretchy", "subscriptshift", "supscriptshift", "symmetric", "voffset", "width", "xmlns"]);
const xml = freeze(["xlink:href", "xml:id", "xlink:title", "xml:space", "xmlns:xlink"]);
const MUSTACHE_EXPR = seal(/\{\{[\w\W]*|[\w\W]*\}\}/gm);
const ERB_EXPR = seal(/<%[\w\W]*|[\w\W]*%>/gm);
const TMPLIT_EXPR = seal(/\$\{[\w\W]*/gm);
const DATA_ATTR = seal(/^data-[\-\w.\u00B7-\uFFFF]+$/);
const ARIA_ATTR = seal(/^aria-[\-\w]+$/);
const IS_ALLOWED_URI = seal(
  /^(?:(?:(?:f|ht)tps?|mailto|tel|callto|sms|cid|xmpp|matrix):|[^a-z]|[a-z+.\-]+(?:[^a-z+.\-:]|$))/i
  // eslint-disable-line no-useless-escape
);
const IS_SCRIPT_OR_DATA = seal(/^(?:\w+script|data):/i);
const ATTR_WHITESPACE = seal(
  /[\u0000-\u0020\u00A0\u1680\u180E\u2000-\u2029\u205F\u3000]/g
  // eslint-disable-line no-control-regex
);
const DOCTYPE_NAME = seal(/^html$/i);
const CUSTOM_ELEMENT = seal(/^[a-z][.\w]*(-[.\w]+)+$/i);
var EXPRESSIONS = /* @__PURE__ */ Object.freeze({
  __proto__: null,
  ARIA_ATTR,
  ATTR_WHITESPACE,
  CUSTOM_ELEMENT,
  DATA_ATTR,
  DOCTYPE_NAME,
  ERB_EXPR,
  IS_ALLOWED_URI,
  IS_SCRIPT_OR_DATA,
  MUSTACHE_EXPR,
  TMPLIT_EXPR
});
const NODE_TYPE = {
  element: 1,
  text: 3,
  // Deprecated
  progressingInstruction: 7,
  comment: 8,
  document: 9
};
const getGlobal = function getGlobal2() {
  return typeof window === "undefined" ? null : window;
};
const _createTrustedTypesPolicy = function _createTrustedTypesPolicy2(trustedTypes, purifyHostElement) {
  if (typeof trustedTypes !== "object" || typeof trustedTypes.createPolicy !== "function") {
    return null;
  }
  let suffix = null;
  const ATTR_NAME = "data-tt-policy-suffix";
  if (purifyHostElement && purifyHostElement.hasAttribute(ATTR_NAME)) {
    suffix = purifyHostElement.getAttribute(ATTR_NAME);
  }
  const policyName = "dompurify" + (suffix ? "#" + suffix : "");
  try {
    return trustedTypes.createPolicy(policyName, {
      createHTML(html2) {
        return html2;
      },
      createScriptURL(scriptUrl) {
        return scriptUrl;
      }
    });
  } catch (_) {
    console.warn("TrustedTypes policy " + policyName + " could not be created.");
    return null;
  }
};
const _createHooksMap = function _createHooksMap2() {
  return {
    afterSanitizeAttributes: [],
    afterSanitizeElements: [],
    afterSanitizeShadowDOM: [],
    beforeSanitizeAttributes: [],
    beforeSanitizeElements: [],
    beforeSanitizeShadowDOM: [],
    uponSanitizeAttribute: [],
    uponSanitizeElement: [],
    uponSanitizeShadowNode: []
  };
};
function createDOMPurify() {
  let window2 = arguments.length > 0 && arguments[0] !== void 0 ? arguments[0] : getGlobal();
  const DOMPurify = (root) => createDOMPurify(root);
  DOMPurify.version = "3.3.1";
  DOMPurify.removed = [];
  if (!window2 || !window2.document || window2.document.nodeType !== NODE_TYPE.document || !window2.Element) {
    DOMPurify.isSupported = false;
    return DOMPurify;
  }
  let {
    document: document2
  } = window2;
  const originalDocument = document2;
  const currentScript = originalDocument.currentScript;
  const {
    DocumentFragment,
    HTMLTemplateElement,
    Node,
    Element: Element2,
    NodeFilter,
    NamedNodeMap = window2.NamedNodeMap || window2.MozNamedAttrMap,
    HTMLFormElement,
    DOMParser,
    trustedTypes
  } = window2;
  const ElementPrototype = Element2.prototype;
  const cloneNode = lookupGetter(ElementPrototype, "cloneNode");
  const remove2 = lookupGetter(ElementPrototype, "remove");
  const getNextSibling = lookupGetter(ElementPrototype, "nextSibling");
  const getChildNodes = lookupGetter(ElementPrototype, "childNodes");
  const getParentNode = lookupGetter(ElementPrototype, "parentNode");
  if (typeof HTMLTemplateElement === "function") {
    const template = document2.createElement("template");
    if (template.content && template.content.ownerDocument) {
      document2 = template.content.ownerDocument;
    }
  }
  let trustedTypesPolicy;
  let emptyHTML = "";
  const {
    implementation,
    createNodeIterator,
    createDocumentFragment,
    getElementsByTagName
  } = document2;
  const {
    importNode
  } = originalDocument;
  let hooks = _createHooksMap();
  DOMPurify.isSupported = typeof entries === "function" && typeof getParentNode === "function" && implementation && implementation.createHTMLDocument !== void 0;
  const {
    MUSTACHE_EXPR: MUSTACHE_EXPR2,
    ERB_EXPR: ERB_EXPR2,
    TMPLIT_EXPR: TMPLIT_EXPR2,
    DATA_ATTR: DATA_ATTR2,
    ARIA_ATTR: ARIA_ATTR2,
    IS_SCRIPT_OR_DATA: IS_SCRIPT_OR_DATA2,
    ATTR_WHITESPACE: ATTR_WHITESPACE2,
    CUSTOM_ELEMENT: CUSTOM_ELEMENT2
  } = EXPRESSIONS;
  let {
    IS_ALLOWED_URI: IS_ALLOWED_URI$1
  } = EXPRESSIONS;
  let ALLOWED_TAGS = null;
  const DEFAULT_ALLOWED_TAGS = addToSet({}, [...html$1, ...svg$1, ...svgFilters, ...mathMl$1, ...text]);
  let ALLOWED_ATTR = null;
  const DEFAULT_ALLOWED_ATTR = addToSet({}, [...html, ...svg, ...mathMl, ...xml]);
  let CUSTOM_ELEMENT_HANDLING = Object.seal(create(null, {
    tagNameCheck: {
      writable: true,
      configurable: false,
      enumerable: true,
      value: null
    },
    attributeNameCheck: {
      writable: true,
      configurable: false,
      enumerable: true,
      value: null
    },
    allowCustomizedBuiltInElements: {
      writable: true,
      configurable: false,
      enumerable: true,
      value: false
    }
  }));
  let FORBID_TAGS = null;
  let FORBID_ATTR = null;
  const EXTRA_ELEMENT_HANDLING = Object.seal(create(null, {
    tagCheck: {
      writable: true,
      configurable: false,
      enumerable: true,
      value: null
    },
    attributeCheck: {
      writable: true,
      configurable: false,
      enumerable: true,
      value: null
    }
  }));
  let ALLOW_ARIA_ATTR = true;
  let ALLOW_DATA_ATTR = true;
  let ALLOW_UNKNOWN_PROTOCOLS = false;
  let ALLOW_SELF_CLOSE_IN_ATTR = true;
  let SAFE_FOR_TEMPLATES = false;
  let SAFE_FOR_XML = true;
  let WHOLE_DOCUMENT = false;
  let SET_CONFIG = false;
  let FORCE_BODY = false;
  let RETURN_DOM = false;
  let RETURN_DOM_FRAGMENT = false;
  let RETURN_TRUSTED_TYPE = false;
  let SANITIZE_DOM = true;
  let SANITIZE_NAMED_PROPS = false;
  const SANITIZE_NAMED_PROPS_PREFIX = "user-content-";
  let KEEP_CONTENT = true;
  let IN_PLACE = false;
  let USE_PROFILES = {};
  let FORBID_CONTENTS = null;
  const DEFAULT_FORBID_CONTENTS = addToSet({}, ["annotation-xml", "audio", "colgroup", "desc", "foreignobject", "head", "iframe", "math", "mi", "mn", "mo", "ms", "mtext", "noembed", "noframes", "noscript", "plaintext", "script", "style", "svg", "template", "thead", "title", "video", "xmp"]);
  let DATA_URI_TAGS = null;
  const DEFAULT_DATA_URI_TAGS = addToSet({}, ["audio", "video", "img", "source", "image", "track"]);
  let URI_SAFE_ATTRIBUTES = null;
  const DEFAULT_URI_SAFE_ATTRIBUTES = addToSet({}, ["alt", "class", "for", "id", "label", "name", "pattern", "placeholder", "role", "summary", "title", "value", "style", "xmlns"]);
  const MATHML_NAMESPACE = "http://www.w3.org/1998/Math/MathML";
  const SVG_NAMESPACE = "http://www.w3.org/2000/svg";
  const HTML_NAMESPACE = "http://www.w3.org/1999/xhtml";
  let NAMESPACE = HTML_NAMESPACE;
  let IS_EMPTY_INPUT = false;
  let ALLOWED_NAMESPACES = null;
  const DEFAULT_ALLOWED_NAMESPACES = addToSet({}, [MATHML_NAMESPACE, SVG_NAMESPACE, HTML_NAMESPACE], stringToString);
  let MATHML_TEXT_INTEGRATION_POINTS = addToSet({}, ["mi", "mo", "mn", "ms", "mtext"]);
  let HTML_INTEGRATION_POINTS = addToSet({}, ["annotation-xml"]);
  const COMMON_SVG_AND_HTML_ELEMENTS = addToSet({}, ["title", "style", "font", "a", "script"]);
  let PARSER_MEDIA_TYPE = null;
  const SUPPORTED_PARSER_MEDIA_TYPES = ["application/xhtml+xml", "text/html"];
  const DEFAULT_PARSER_MEDIA_TYPE = "text/html";
  let transformCaseFunc = null;
  let CONFIG = null;
  const formElement = document2.createElement("form");
  const isRegexOrFunction = function isRegexOrFunction2(testValue) {
    return testValue instanceof RegExp || testValue instanceof Function;
  };
  const _parseConfig = function _parseConfig2() {
    let cfg = arguments.length > 0 && arguments[0] !== void 0 ? arguments[0] : {};
    if (CONFIG && CONFIG === cfg) {
      return;
    }
    if (!cfg || typeof cfg !== "object") {
      cfg = {};
    }
    cfg = clone(cfg);
    PARSER_MEDIA_TYPE = // eslint-disable-next-line unicorn/prefer-includes
    SUPPORTED_PARSER_MEDIA_TYPES.indexOf(cfg.PARSER_MEDIA_TYPE) === -1 ? DEFAULT_PARSER_MEDIA_TYPE : cfg.PARSER_MEDIA_TYPE;
    transformCaseFunc = PARSER_MEDIA_TYPE === "application/xhtml+xml" ? stringToString : stringToLowerCase;
    ALLOWED_TAGS = objectHasOwnProperty(cfg, "ALLOWED_TAGS") ? addToSet({}, cfg.ALLOWED_TAGS, transformCaseFunc) : DEFAULT_ALLOWED_TAGS;
    ALLOWED_ATTR = objectHasOwnProperty(cfg, "ALLOWED_ATTR") ? addToSet({}, cfg.ALLOWED_ATTR, transformCaseFunc) : DEFAULT_ALLOWED_ATTR;
    ALLOWED_NAMESPACES = objectHasOwnProperty(cfg, "ALLOWED_NAMESPACES") ? addToSet({}, cfg.ALLOWED_NAMESPACES, stringToString) : DEFAULT_ALLOWED_NAMESPACES;
    URI_SAFE_ATTRIBUTES = objectHasOwnProperty(cfg, "ADD_URI_SAFE_ATTR") ? addToSet(clone(DEFAULT_URI_SAFE_ATTRIBUTES), cfg.ADD_URI_SAFE_ATTR, transformCaseFunc) : DEFAULT_URI_SAFE_ATTRIBUTES;
    DATA_URI_TAGS = objectHasOwnProperty(cfg, "ADD_DATA_URI_TAGS") ? addToSet(clone(DEFAULT_DATA_URI_TAGS), cfg.ADD_DATA_URI_TAGS, transformCaseFunc) : DEFAULT_DATA_URI_TAGS;
    FORBID_CONTENTS = objectHasOwnProperty(cfg, "FORBID_CONTENTS") ? addToSet({}, cfg.FORBID_CONTENTS, transformCaseFunc) : DEFAULT_FORBID_CONTENTS;
    FORBID_TAGS = objectHasOwnProperty(cfg, "FORBID_TAGS") ? addToSet({}, cfg.FORBID_TAGS, transformCaseFunc) : clone({});
    FORBID_ATTR = objectHasOwnProperty(cfg, "FORBID_ATTR") ? addToSet({}, cfg.FORBID_ATTR, transformCaseFunc) : clone({});
    USE_PROFILES = objectHasOwnProperty(cfg, "USE_PROFILES") ? cfg.USE_PROFILES : false;
    ALLOW_ARIA_ATTR = cfg.ALLOW_ARIA_ATTR !== false;
    ALLOW_DATA_ATTR = cfg.ALLOW_DATA_ATTR !== false;
    ALLOW_UNKNOWN_PROTOCOLS = cfg.ALLOW_UNKNOWN_PROTOCOLS || false;
    ALLOW_SELF_CLOSE_IN_ATTR = cfg.ALLOW_SELF_CLOSE_IN_ATTR !== false;
    SAFE_FOR_TEMPLATES = cfg.SAFE_FOR_TEMPLATES || false;
    SAFE_FOR_XML = cfg.SAFE_FOR_XML !== false;
    WHOLE_DOCUMENT = cfg.WHOLE_DOCUMENT || false;
    RETURN_DOM = cfg.RETURN_DOM || false;
    RETURN_DOM_FRAGMENT = cfg.RETURN_DOM_FRAGMENT || false;
    RETURN_TRUSTED_TYPE = cfg.RETURN_TRUSTED_TYPE || false;
    FORCE_BODY = cfg.FORCE_BODY || false;
    SANITIZE_DOM = cfg.SANITIZE_DOM !== false;
    SANITIZE_NAMED_PROPS = cfg.SANITIZE_NAMED_PROPS || false;
    KEEP_CONTENT = cfg.KEEP_CONTENT !== false;
    IN_PLACE = cfg.IN_PLACE || false;
    IS_ALLOWED_URI$1 = cfg.ALLOWED_URI_REGEXP || IS_ALLOWED_URI;
    NAMESPACE = cfg.NAMESPACE || HTML_NAMESPACE;
    MATHML_TEXT_INTEGRATION_POINTS = cfg.MATHML_TEXT_INTEGRATION_POINTS || MATHML_TEXT_INTEGRATION_POINTS;
    HTML_INTEGRATION_POINTS = cfg.HTML_INTEGRATION_POINTS || HTML_INTEGRATION_POINTS;
    CUSTOM_ELEMENT_HANDLING = cfg.CUSTOM_ELEMENT_HANDLING || {};
    if (cfg.CUSTOM_ELEMENT_HANDLING && isRegexOrFunction(cfg.CUSTOM_ELEMENT_HANDLING.tagNameCheck)) {
      CUSTOM_ELEMENT_HANDLING.tagNameCheck = cfg.CUSTOM_ELEMENT_HANDLING.tagNameCheck;
    }
    if (cfg.CUSTOM_ELEMENT_HANDLING && isRegexOrFunction(cfg.CUSTOM_ELEMENT_HANDLING.attributeNameCheck)) {
      CUSTOM_ELEMENT_HANDLING.attributeNameCheck = cfg.CUSTOM_ELEMENT_HANDLING.attributeNameCheck;
    }
    if (cfg.CUSTOM_ELEMENT_HANDLING && typeof cfg.CUSTOM_ELEMENT_HANDLING.allowCustomizedBuiltInElements === "boolean") {
      CUSTOM_ELEMENT_HANDLING.allowCustomizedBuiltInElements = cfg.CUSTOM_ELEMENT_HANDLING.allowCustomizedBuiltInElements;
    }
    if (SAFE_FOR_TEMPLATES) {
      ALLOW_DATA_ATTR = false;
    }
    if (RETURN_DOM_FRAGMENT) {
      RETURN_DOM = true;
    }
    if (USE_PROFILES) {
      ALLOWED_TAGS = addToSet({}, text);
      ALLOWED_ATTR = [];
      if (USE_PROFILES.html === true) {
        addToSet(ALLOWED_TAGS, html$1);
        addToSet(ALLOWED_ATTR, html);
      }
      if (USE_PROFILES.svg === true) {
        addToSet(ALLOWED_TAGS, svg$1);
        addToSet(ALLOWED_ATTR, svg);
        addToSet(ALLOWED_ATTR, xml);
      }
      if (USE_PROFILES.svgFilters === true) {
        addToSet(ALLOWED_TAGS, svgFilters);
        addToSet(ALLOWED_ATTR, svg);
        addToSet(ALLOWED_ATTR, xml);
      }
      if (USE_PROFILES.mathMl === true) {
        addToSet(ALLOWED_TAGS, mathMl$1);
        addToSet(ALLOWED_ATTR, mathMl);
        addToSet(ALLOWED_ATTR, xml);
      }
    }
    if (cfg.ADD_TAGS) {
      if (typeof cfg.ADD_TAGS === "function") {
        EXTRA_ELEMENT_HANDLING.tagCheck = cfg.ADD_TAGS;
      } else {
        if (ALLOWED_TAGS === DEFAULT_ALLOWED_TAGS) {
          ALLOWED_TAGS = clone(ALLOWED_TAGS);
        }
        addToSet(ALLOWED_TAGS, cfg.ADD_TAGS, transformCaseFunc);
      }
    }
    if (cfg.ADD_ATTR) {
      if (typeof cfg.ADD_ATTR === "function") {
        EXTRA_ELEMENT_HANDLING.attributeCheck = cfg.ADD_ATTR;
      } else {
        if (ALLOWED_ATTR === DEFAULT_ALLOWED_ATTR) {
          ALLOWED_ATTR = clone(ALLOWED_ATTR);
        }
        addToSet(ALLOWED_ATTR, cfg.ADD_ATTR, transformCaseFunc);
      }
    }
    if (cfg.ADD_URI_SAFE_ATTR) {
      addToSet(URI_SAFE_ATTRIBUTES, cfg.ADD_URI_SAFE_ATTR, transformCaseFunc);
    }
    if (cfg.FORBID_CONTENTS) {
      if (FORBID_CONTENTS === DEFAULT_FORBID_CONTENTS) {
        FORBID_CONTENTS = clone(FORBID_CONTENTS);
      }
      addToSet(FORBID_CONTENTS, cfg.FORBID_CONTENTS, transformCaseFunc);
    }
    if (cfg.ADD_FORBID_CONTENTS) {
      if (FORBID_CONTENTS === DEFAULT_FORBID_CONTENTS) {
        FORBID_CONTENTS = clone(FORBID_CONTENTS);
      }
      addToSet(FORBID_CONTENTS, cfg.ADD_FORBID_CONTENTS, transformCaseFunc);
    }
    if (KEEP_CONTENT) {
      ALLOWED_TAGS["#text"] = true;
    }
    if (WHOLE_DOCUMENT) {
      addToSet(ALLOWED_TAGS, ["html", "head", "body"]);
    }
    if (ALLOWED_TAGS.table) {
      addToSet(ALLOWED_TAGS, ["tbody"]);
      delete FORBID_TAGS.tbody;
    }
    if (cfg.TRUSTED_TYPES_POLICY) {
      if (typeof cfg.TRUSTED_TYPES_POLICY.createHTML !== "function") {
        throw typeErrorCreate('TRUSTED_TYPES_POLICY configuration option must provide a "createHTML" hook.');
      }
      if (typeof cfg.TRUSTED_TYPES_POLICY.createScriptURL !== "function") {
        throw typeErrorCreate('TRUSTED_TYPES_POLICY configuration option must provide a "createScriptURL" hook.');
      }
      trustedTypesPolicy = cfg.TRUSTED_TYPES_POLICY;
      emptyHTML = trustedTypesPolicy.createHTML("");
    } else {
      if (trustedTypesPolicy === void 0) {
        trustedTypesPolicy = _createTrustedTypesPolicy(trustedTypes, currentScript);
      }
      if (trustedTypesPolicy !== null && typeof emptyHTML === "string") {
        emptyHTML = trustedTypesPolicy.createHTML("");
      }
    }
    if (freeze) {
      freeze(cfg);
    }
    CONFIG = cfg;
  };
  const ALL_SVG_TAGS = addToSet({}, [...svg$1, ...svgFilters, ...svgDisallowed]);
  const ALL_MATHML_TAGS = addToSet({}, [...mathMl$1, ...mathMlDisallowed]);
  const _checkValidNamespace = function _checkValidNamespace2(element) {
    let parent = getParentNode(element);
    if (!parent || !parent.tagName) {
      parent = {
        namespaceURI: NAMESPACE,
        tagName: "template"
      };
    }
    const tagName = stringToLowerCase(element.tagName);
    const parentTagName = stringToLowerCase(parent.tagName);
    if (!ALLOWED_NAMESPACES[element.namespaceURI]) {
      return false;
    }
    if (element.namespaceURI === SVG_NAMESPACE) {
      if (parent.namespaceURI === HTML_NAMESPACE) {
        return tagName === "svg";
      }
      if (parent.namespaceURI === MATHML_NAMESPACE) {
        return tagName === "svg" && (parentTagName === "annotation-xml" || MATHML_TEXT_INTEGRATION_POINTS[parentTagName]);
      }
      return Boolean(ALL_SVG_TAGS[tagName]);
    }
    if (element.namespaceURI === MATHML_NAMESPACE) {
      if (parent.namespaceURI === HTML_NAMESPACE) {
        return tagName === "math";
      }
      if (parent.namespaceURI === SVG_NAMESPACE) {
        return tagName === "math" && HTML_INTEGRATION_POINTS[parentTagName];
      }
      return Boolean(ALL_MATHML_TAGS[tagName]);
    }
    if (element.namespaceURI === HTML_NAMESPACE) {
      if (parent.namespaceURI === SVG_NAMESPACE && !HTML_INTEGRATION_POINTS[parentTagName]) {
        return false;
      }
      if (parent.namespaceURI === MATHML_NAMESPACE && !MATHML_TEXT_INTEGRATION_POINTS[parentTagName]) {
        return false;
      }
      return !ALL_MATHML_TAGS[tagName] && (COMMON_SVG_AND_HTML_ELEMENTS[tagName] || !ALL_SVG_TAGS[tagName]);
    }
    if (PARSER_MEDIA_TYPE === "application/xhtml+xml" && ALLOWED_NAMESPACES[element.namespaceURI]) {
      return true;
    }
    return false;
  };
  const _forceRemove = function _forceRemove2(node) {
    arrayPush(DOMPurify.removed, {
      element: node
    });
    try {
      getParentNode(node).removeChild(node);
    } catch (_) {
      remove2(node);
    }
  };
  const _removeAttribute = function _removeAttribute2(name, element) {
    try {
      arrayPush(DOMPurify.removed, {
        attribute: element.getAttributeNode(name),
        from: element
      });
    } catch (_) {
      arrayPush(DOMPurify.removed, {
        attribute: null,
        from: element
      });
    }
    element.removeAttribute(name);
    if (name === "is") {
      if (RETURN_DOM || RETURN_DOM_FRAGMENT) {
        try {
          _forceRemove(element);
        } catch (_) {
        }
      } else {
        try {
          element.setAttribute(name, "");
        } catch (_) {
        }
      }
    }
  };
  const _initDocument = function _initDocument2(dirty) {
    let doc2 = null;
    let leadingWhitespace = null;
    if (FORCE_BODY) {
      dirty = "<remove></remove>" + dirty;
    } else {
      const matches = stringMatch(dirty, /^[\r\n\t ]+/);
      leadingWhitespace = matches && matches[0];
    }
    if (PARSER_MEDIA_TYPE === "application/xhtml+xml" && NAMESPACE === HTML_NAMESPACE) {
      dirty = '<html xmlns="http://www.w3.org/1999/xhtml"><head></head><body>' + dirty + "</body></html>";
    }
    const dirtyPayload = trustedTypesPolicy ? trustedTypesPolicy.createHTML(dirty) : dirty;
    if (NAMESPACE === HTML_NAMESPACE) {
      try {
        doc2 = new DOMParser().parseFromString(dirtyPayload, PARSER_MEDIA_TYPE);
      } catch (_) {
      }
    }
    if (!doc2 || !doc2.documentElement) {
      doc2 = implementation.createDocument(NAMESPACE, "template", null);
      try {
        doc2.documentElement.innerHTML = IS_EMPTY_INPUT ? emptyHTML : dirtyPayload;
      } catch (_) {
      }
    }
    const body = doc2.body || doc2.documentElement;
    if (dirty && leadingWhitespace) {
      body.insertBefore(document2.createTextNode(leadingWhitespace), body.childNodes[0] || null);
    }
    if (NAMESPACE === HTML_NAMESPACE) {
      return getElementsByTagName.call(doc2, WHOLE_DOCUMENT ? "html" : "body")[0];
    }
    return WHOLE_DOCUMENT ? doc2.documentElement : body;
  };
  const _createNodeIterator = function _createNodeIterator2(root) {
    return createNodeIterator.call(
      root.ownerDocument || root,
      root,
      // eslint-disable-next-line no-bitwise
      NodeFilter.SHOW_ELEMENT | NodeFilter.SHOW_COMMENT | NodeFilter.SHOW_TEXT | NodeFilter.SHOW_PROCESSING_INSTRUCTION | NodeFilter.SHOW_CDATA_SECTION,
      null
    );
  };
  const _isClobbered = function _isClobbered2(element) {
    return element instanceof HTMLFormElement && (typeof element.nodeName !== "string" || typeof element.textContent !== "string" || typeof element.removeChild !== "function" || !(element.attributes instanceof NamedNodeMap) || typeof element.removeAttribute !== "function" || typeof element.setAttribute !== "function" || typeof element.namespaceURI !== "string" || typeof element.insertBefore !== "function" || typeof element.hasChildNodes !== "function");
  };
  const _isNode = function _isNode2(value) {
    return typeof Node === "function" && value instanceof Node;
  };
  function _executeHooks(hooks2, currentNode, data) {
    arrayForEach(hooks2, (hook) => {
      hook.call(DOMPurify, currentNode, data, CONFIG);
    });
  }
  const _sanitizeElements = function _sanitizeElements2(currentNode) {
    let content = null;
    _executeHooks(hooks.beforeSanitizeElements, currentNode, null);
    if (_isClobbered(currentNode)) {
      _forceRemove(currentNode);
      return true;
    }
    const tagName = transformCaseFunc(currentNode.nodeName);
    _executeHooks(hooks.uponSanitizeElement, currentNode, {
      tagName,
      allowedTags: ALLOWED_TAGS
    });
    if (SAFE_FOR_XML && currentNode.hasChildNodes() && !_isNode(currentNode.firstElementChild) && regExpTest(/<[/\w!]/g, currentNode.innerHTML) && regExpTest(/<[/\w!]/g, currentNode.textContent)) {
      _forceRemove(currentNode);
      return true;
    }
    if (currentNode.nodeType === NODE_TYPE.progressingInstruction) {
      _forceRemove(currentNode);
      return true;
    }
    if (SAFE_FOR_XML && currentNode.nodeType === NODE_TYPE.comment && regExpTest(/<[/\w]/g, currentNode.data)) {
      _forceRemove(currentNode);
      return true;
    }
    if (!(EXTRA_ELEMENT_HANDLING.tagCheck instanceof Function && EXTRA_ELEMENT_HANDLING.tagCheck(tagName)) && (!ALLOWED_TAGS[tagName] || FORBID_TAGS[tagName])) {
      if (!FORBID_TAGS[tagName] && _isBasicCustomElement(tagName)) {
        if (CUSTOM_ELEMENT_HANDLING.tagNameCheck instanceof RegExp && regExpTest(CUSTOM_ELEMENT_HANDLING.tagNameCheck, tagName)) {
          return false;
        }
        if (CUSTOM_ELEMENT_HANDLING.tagNameCheck instanceof Function && CUSTOM_ELEMENT_HANDLING.tagNameCheck(tagName)) {
          return false;
        }
      }
      if (KEEP_CONTENT && !FORBID_CONTENTS[tagName]) {
        const parentNode = getParentNode(currentNode) || currentNode.parentNode;
        const childNodes = getChildNodes(currentNode) || currentNode.childNodes;
        if (childNodes && parentNode) {
          const childCount = childNodes.length;
          for (let i = childCount - 1; i >= 0; --i) {
            const childClone = cloneNode(childNodes[i], true);
            childClone.__removalCount = (currentNode.__removalCount || 0) + 1;
            parentNode.insertBefore(childClone, getNextSibling(currentNode));
          }
        }
      }
      _forceRemove(currentNode);
      return true;
    }
    if (currentNode instanceof Element2 && !_checkValidNamespace(currentNode)) {
      _forceRemove(currentNode);
      return true;
    }
    if ((tagName === "noscript" || tagName === "noembed" || tagName === "noframes") && regExpTest(/<\/no(script|embed|frames)/i, currentNode.innerHTML)) {
      _forceRemove(currentNode);
      return true;
    }
    if (SAFE_FOR_TEMPLATES && currentNode.nodeType === NODE_TYPE.text) {
      content = currentNode.textContent;
      arrayForEach([MUSTACHE_EXPR2, ERB_EXPR2, TMPLIT_EXPR2], (expr) => {
        content = stringReplace(content, expr, " ");
      });
      if (currentNode.textContent !== content) {
        arrayPush(DOMPurify.removed, {
          element: currentNode.cloneNode()
        });
        currentNode.textContent = content;
      }
    }
    _executeHooks(hooks.afterSanitizeElements, currentNode, null);
    return false;
  };
  const _isValidAttribute = function _isValidAttribute2(lcTag, lcName, value) {
    if (SANITIZE_DOM && (lcName === "id" || lcName === "name") && (value in document2 || value in formElement)) {
      return false;
    }
    if (ALLOW_DATA_ATTR && !FORBID_ATTR[lcName] && regExpTest(DATA_ATTR2, lcName)) ;
    else if (ALLOW_ARIA_ATTR && regExpTest(ARIA_ATTR2, lcName)) ;
    else if (EXTRA_ELEMENT_HANDLING.attributeCheck instanceof Function && EXTRA_ELEMENT_HANDLING.attributeCheck(lcName, lcTag)) ;
    else if (!ALLOWED_ATTR[lcName] || FORBID_ATTR[lcName]) {
      if (
        // First condition does a very basic check if a) it's basically a valid custom element tagname AND
        // b) if the tagName passes whatever the user has configured for CUSTOM_ELEMENT_HANDLING.tagNameCheck
        // and c) if the attribute name passes whatever the user has configured for CUSTOM_ELEMENT_HANDLING.attributeNameCheck
        _isBasicCustomElement(lcTag) && (CUSTOM_ELEMENT_HANDLING.tagNameCheck instanceof RegExp && regExpTest(CUSTOM_ELEMENT_HANDLING.tagNameCheck, lcTag) || CUSTOM_ELEMENT_HANDLING.tagNameCheck instanceof Function && CUSTOM_ELEMENT_HANDLING.tagNameCheck(lcTag)) && (CUSTOM_ELEMENT_HANDLING.attributeNameCheck instanceof RegExp && regExpTest(CUSTOM_ELEMENT_HANDLING.attributeNameCheck, lcName) || CUSTOM_ELEMENT_HANDLING.attributeNameCheck instanceof Function && CUSTOM_ELEMENT_HANDLING.attributeNameCheck(lcName, lcTag)) || // Alternative, second condition checks if it's an `is`-attribute, AND
        // the value passes whatever the user has configured for CUSTOM_ELEMENT_HANDLING.tagNameCheck
        lcName === "is" && CUSTOM_ELEMENT_HANDLING.allowCustomizedBuiltInElements && (CUSTOM_ELEMENT_HANDLING.tagNameCheck instanceof RegExp && regExpTest(CUSTOM_ELEMENT_HANDLING.tagNameCheck, value) || CUSTOM_ELEMENT_HANDLING.tagNameCheck instanceof Function && CUSTOM_ELEMENT_HANDLING.tagNameCheck(value))
      ) ;
      else {
        return false;
      }
    } else if (URI_SAFE_ATTRIBUTES[lcName]) ;
    else if (regExpTest(IS_ALLOWED_URI$1, stringReplace(value, ATTR_WHITESPACE2, ""))) ;
    else if ((lcName === "src" || lcName === "xlink:href" || lcName === "href") && lcTag !== "script" && stringIndexOf(value, "data:") === 0 && DATA_URI_TAGS[lcTag]) ;
    else if (ALLOW_UNKNOWN_PROTOCOLS && !regExpTest(IS_SCRIPT_OR_DATA2, stringReplace(value, ATTR_WHITESPACE2, ""))) ;
    else if (value) {
      return false;
    } else ;
    return true;
  };
  const _isBasicCustomElement = function _isBasicCustomElement2(tagName) {
    return tagName !== "annotation-xml" && stringMatch(tagName, CUSTOM_ELEMENT2);
  };
  const _sanitizeAttributes = function _sanitizeAttributes2(currentNode) {
    _executeHooks(hooks.beforeSanitizeAttributes, currentNode, null);
    const {
      attributes
    } = currentNode;
    if (!attributes || _isClobbered(currentNode)) {
      return;
    }
    const hookEvent = {
      attrName: "",
      attrValue: "",
      keepAttr: true,
      allowedAttributes: ALLOWED_ATTR,
      forceKeepAttr: void 0
    };
    let l = attributes.length;
    while (l--) {
      const attr = attributes[l];
      const {
        name,
        namespaceURI,
        value: attrValue
      } = attr;
      const lcName = transformCaseFunc(name);
      const initValue = attrValue;
      let value = name === "value" ? initValue : stringTrim(initValue);
      hookEvent.attrName = lcName;
      hookEvent.attrValue = value;
      hookEvent.keepAttr = true;
      hookEvent.forceKeepAttr = void 0;
      _executeHooks(hooks.uponSanitizeAttribute, currentNode, hookEvent);
      value = hookEvent.attrValue;
      if (SANITIZE_NAMED_PROPS && (lcName === "id" || lcName === "name")) {
        _removeAttribute(name, currentNode);
        value = SANITIZE_NAMED_PROPS_PREFIX + value;
      }
      if (SAFE_FOR_XML && regExpTest(/((--!?|])>)|<\/(style|title|textarea)/i, value)) {
        _removeAttribute(name, currentNode);
        continue;
      }
      if (lcName === "attributename" && stringMatch(value, "href")) {
        _removeAttribute(name, currentNode);
        continue;
      }
      if (hookEvent.forceKeepAttr) {
        continue;
      }
      if (!hookEvent.keepAttr) {
        _removeAttribute(name, currentNode);
        continue;
      }
      if (!ALLOW_SELF_CLOSE_IN_ATTR && regExpTest(/\/>/i, value)) {
        _removeAttribute(name, currentNode);
        continue;
      }
      if (SAFE_FOR_TEMPLATES) {
        arrayForEach([MUSTACHE_EXPR2, ERB_EXPR2, TMPLIT_EXPR2], (expr) => {
          value = stringReplace(value, expr, " ");
        });
      }
      const lcTag = transformCaseFunc(currentNode.nodeName);
      if (!_isValidAttribute(lcTag, lcName, value)) {
        _removeAttribute(name, currentNode);
        continue;
      }
      if (trustedTypesPolicy && typeof trustedTypes === "object" && typeof trustedTypes.getAttributeType === "function") {
        if (namespaceURI) ;
        else {
          switch (trustedTypes.getAttributeType(lcTag, lcName)) {
            case "TrustedHTML": {
              value = trustedTypesPolicy.createHTML(value);
              break;
            }
            case "TrustedScriptURL": {
              value = trustedTypesPolicy.createScriptURL(value);
              break;
            }
          }
        }
      }
      if (value !== initValue) {
        try {
          if (namespaceURI) {
            currentNode.setAttributeNS(namespaceURI, name, value);
          } else {
            currentNode.setAttribute(name, value);
          }
          if (_isClobbered(currentNode)) {
            _forceRemove(currentNode);
          } else {
            arrayPop(DOMPurify.removed);
          }
        } catch (_) {
          _removeAttribute(name, currentNode);
        }
      }
    }
    _executeHooks(hooks.afterSanitizeAttributes, currentNode, null);
  };
  const _sanitizeShadowDOM = function _sanitizeShadowDOM2(fragment) {
    let shadowNode = null;
    const shadowIterator = _createNodeIterator(fragment);
    _executeHooks(hooks.beforeSanitizeShadowDOM, fragment, null);
    while (shadowNode = shadowIterator.nextNode()) {
      _executeHooks(hooks.uponSanitizeShadowNode, shadowNode, null);
      _sanitizeElements(shadowNode);
      _sanitizeAttributes(shadowNode);
      if (shadowNode.content instanceof DocumentFragment) {
        _sanitizeShadowDOM2(shadowNode.content);
      }
    }
    _executeHooks(hooks.afterSanitizeShadowDOM, fragment, null);
  };
  DOMPurify.sanitize = function(dirty) {
    let cfg = arguments.length > 1 && arguments[1] !== void 0 ? arguments[1] : {};
    let body = null;
    let importedNode = null;
    let currentNode = null;
    let returnNode = null;
    IS_EMPTY_INPUT = !dirty;
    if (IS_EMPTY_INPUT) {
      dirty = "<!-->";
    }
    if (typeof dirty !== "string" && !_isNode(dirty)) {
      if (typeof dirty.toString === "function") {
        dirty = dirty.toString();
        if (typeof dirty !== "string") {
          throw typeErrorCreate("dirty is not a string, aborting");
        }
      } else {
        throw typeErrorCreate("toString is not a function");
      }
    }
    if (!DOMPurify.isSupported) {
      return dirty;
    }
    if (!SET_CONFIG) {
      _parseConfig(cfg);
    }
    DOMPurify.removed = [];
    if (typeof dirty === "string") {
      IN_PLACE = false;
    }
    if (IN_PLACE) {
      if (dirty.nodeName) {
        const tagName = transformCaseFunc(dirty.nodeName);
        if (!ALLOWED_TAGS[tagName] || FORBID_TAGS[tagName]) {
          throw typeErrorCreate("root node is forbidden and cannot be sanitized in-place");
        }
      }
    } else if (dirty instanceof Node) {
      body = _initDocument("<!---->");
      importedNode = body.ownerDocument.importNode(dirty, true);
      if (importedNode.nodeType === NODE_TYPE.element && importedNode.nodeName === "BODY") {
        body = importedNode;
      } else if (importedNode.nodeName === "HTML") {
        body = importedNode;
      } else {
        body.appendChild(importedNode);
      }
    } else {
      if (!RETURN_DOM && !SAFE_FOR_TEMPLATES && !WHOLE_DOCUMENT && // eslint-disable-next-line unicorn/prefer-includes
      dirty.indexOf("<") === -1) {
        return trustedTypesPolicy && RETURN_TRUSTED_TYPE ? trustedTypesPolicy.createHTML(dirty) : dirty;
      }
      body = _initDocument(dirty);
      if (!body) {
        return RETURN_DOM ? null : RETURN_TRUSTED_TYPE ? emptyHTML : "";
      }
    }
    if (body && FORCE_BODY) {
      _forceRemove(body.firstChild);
    }
    const nodeIterator = _createNodeIterator(IN_PLACE ? dirty : body);
    while (currentNode = nodeIterator.nextNode()) {
      _sanitizeElements(currentNode);
      _sanitizeAttributes(currentNode);
      if (currentNode.content instanceof DocumentFragment) {
        _sanitizeShadowDOM(currentNode.content);
      }
    }
    if (IN_PLACE) {
      return dirty;
    }
    if (RETURN_DOM) {
      if (RETURN_DOM_FRAGMENT) {
        returnNode = createDocumentFragment.call(body.ownerDocument);
        while (body.firstChild) {
          returnNode.appendChild(body.firstChild);
        }
      } else {
        returnNode = body;
      }
      if (ALLOWED_ATTR.shadowroot || ALLOWED_ATTR.shadowrootmode) {
        returnNode = importNode.call(originalDocument, returnNode, true);
      }
      return returnNode;
    }
    let serializedHTML = WHOLE_DOCUMENT ? body.outerHTML : body.innerHTML;
    if (WHOLE_DOCUMENT && ALLOWED_TAGS["!doctype"] && body.ownerDocument && body.ownerDocument.doctype && body.ownerDocument.doctype.name && regExpTest(DOCTYPE_NAME, body.ownerDocument.doctype.name)) {
      serializedHTML = "<!DOCTYPE " + body.ownerDocument.doctype.name + ">\n" + serializedHTML;
    }
    if (SAFE_FOR_TEMPLATES) {
      arrayForEach([MUSTACHE_EXPR2, ERB_EXPR2, TMPLIT_EXPR2], (expr) => {
        serializedHTML = stringReplace(serializedHTML, expr, " ");
      });
    }
    return trustedTypesPolicy && RETURN_TRUSTED_TYPE ? trustedTypesPolicy.createHTML(serializedHTML) : serializedHTML;
  };
  DOMPurify.setConfig = function() {
    let cfg = arguments.length > 0 && arguments[0] !== void 0 ? arguments[0] : {};
    _parseConfig(cfg);
    SET_CONFIG = true;
  };
  DOMPurify.clearConfig = function() {
    CONFIG = null;
    SET_CONFIG = false;
  };
  DOMPurify.isValidAttribute = function(tag, attr, value) {
    if (!CONFIG) {
      _parseConfig({});
    }
    const lcTag = transformCaseFunc(tag);
    const lcName = transformCaseFunc(attr);
    return _isValidAttribute(lcTag, lcName, value);
  };
  DOMPurify.addHook = function(entryPoint, hookFunction) {
    if (typeof hookFunction !== "function") {
      return;
    }
    arrayPush(hooks[entryPoint], hookFunction);
  };
  DOMPurify.removeHook = function(entryPoint, hookFunction) {
    if (hookFunction !== void 0) {
      const index = arrayLastIndexOf(hooks[entryPoint], hookFunction);
      return index === -1 ? void 0 : arraySplice(hooks[entryPoint], index, 1)[0];
    }
    return arrayPop(hooks[entryPoint]);
  };
  DOMPurify.removeHooks = function(entryPoint) {
    hooks[entryPoint] = [];
  };
  DOMPurify.removeAllHooks = function() {
    hooks = _createHooksMap();
  };
  return DOMPurify;
}
var purify = createDOMPurify();
/*!
 * escape-html
 * Copyright(c) 2012-2013 TJ Holowaychuk
 * Copyright(c) 2015 Andreas Lubbe
 * Copyright(c) 2015 Tiancheng "Timothy" Gu
 * MIT Licensed
 */
var escapeHtml_1;
var hasRequiredEscapeHtml;
function requireEscapeHtml() {
  if (hasRequiredEscapeHtml) return escapeHtml_1;
  hasRequiredEscapeHtml = 1;
  var matchHtmlRegExp = /["'&<>]/;
  escapeHtml_1 = escapeHtml;
  function escapeHtml(string) {
    var str = "" + string;
    var match = matchHtmlRegExp.exec(str);
    if (!match) {
      return str;
    }
    var escape;
    var html2 = "";
    var index = 0;
    var lastIndex = 0;
    for (index = match.index; index < str.length; index++) {
      switch (str.charCodeAt(index)) {
        case 34:
          escape = "&quot;";
          break;
        case 38:
          escape = "&amp;";
          break;
        case 39:
          escape = "&#39;";
          break;
        case 60:
          escape = "&lt;";
          break;
        case 62:
          escape = "&gt;";
          break;
        default:
          continue;
      }
      if (lastIndex !== index) {
        html2 += str.substring(lastIndex, index);
      }
      lastIndex = index + 1;
      html2 += escape;
    }
    return lastIndex !== index ? html2 + str.substring(lastIndex, index) : html2;
  }
  return escapeHtml_1;
}
var escapeHtmlExports = requireEscapeHtml();
const escapeHTML = /* @__PURE__ */ getDefaultExportFromCjs$1(escapeHtmlExports);
/*!
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: GPL-3.0-or-later
 */
function getLocale() {
  return globalThis._nc_l10n_locale;
}
function getCanonicalLocale() {
  return getLocale().replaceAll(/_/g, "-");
}
function getLanguage() {
  return globalThis._nc_l10n_language;
}
function isRTL(language) {
  const languageCode = getLanguage();
  const rtlLanguages = [
    "ae",
    // Avestan
    "ar",
    // 'العربية', Arabic
    "arc",
    // Aramaic
    "arz",
    // 'مصرى', Egyptian
    "bcc",
    // 'بلوچی مکرانی', Southern Balochi
    "bqi",
    // 'بختياري', Bakthiari
    "ckb",
    // 'Soranî / کوردی', Sorani
    "dv",
    // Dhivehi
    "fa",
    // 'فارسی', Persian
    "glk",
    // 'گیلکی', Gilaki
    "ha",
    // 'هَوُسَ', Hausa
    "he",
    // 'עברית', Hebrew
    "khw",
    // 'کھوار', Khowar
    "ks",
    // 'कॉशुर / کٲشُر', Kashmiri
    "ku",
    // 'Kurdî / كوردی', Kurdish
    "mzn",
    // 'مازِرونی', Mazanderani
    "nqo",
    // 'ߒߞߏ', N’Ko
    "pnb",
    // 'پنجابی', Western Punjabi
    "ps",
    // 'پښتو', Pashto,
    "sd",
    // 'سنڌي', Sindhi
    "ug",
    // 'Uyghurche / ئۇيغۇرچە', Uyghur
    "ur",
    // 'اردو', Urdu
    "ur-PK",
    // 'اردو', Urdu (nextcloud BCP47 variant)
    "uz-AF",
    // 'اوزبیکی', Uzbek Afghan
    "yi"
    // 'ייִדיש', Yiddish
  ];
  return rtlLanguages.includes(languageCode);
}
globalThis._nc_l10n_locale ??= typeof document !== "undefined" && document.documentElement.dataset.locale || Intl.DateTimeFormat().resolvedOptions().locale.replaceAll(/-/g, "_");
globalThis._nc_l10n_language ??= typeof document !== "undefined" && document.documentElement.lang || (globalThis.navigator?.language ?? "en");
function getAppTranslations(appId) {
  return {
    translations: globalThis._oc_l10n_registry_translations[appId] ?? {},
    pluralFunction: globalThis._oc_l10n_registry_plural_functions[appId] ?? ((number) => number)
  };
}
globalThis._oc_l10n_registry_translations ??= {};
globalThis._oc_l10n_registry_plural_functions ??= {};
/*!
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: GPL-3.0-or-later
 */
function translate(app, text2, placeholdersOrNumber, optionsOrNumber, options) {
  const vars = typeof placeholdersOrNumber === "object" ? placeholdersOrNumber : void 0;
  const number = typeof optionsOrNumber === "number" ? optionsOrNumber : typeof placeholdersOrNumber === "number" ? placeholdersOrNumber : void 0;
  const allOptions = {
    // defaults
    escape: true,
    sanitize: true,
    // overwrite with user config
    ...typeof options === "object" ? options : typeof optionsOrNumber === "object" ? optionsOrNumber : {}
  };
  const identity = (value) => value;
  const optSanitize = (allOptions.sanitize ? purify.sanitize : identity) || identity;
  const optEscape = allOptions.escape ? escapeHTML : identity;
  const isValidReplacement = (value) => typeof value === "string" || typeof value === "number";
  const _build = (text22, vars2, number2) => {
    return text22.replace(/%n/g, "" + number2).replace(/{([^{}]*)}/g, (match, key) => {
      if (vars2 === void 0 || !(key in vars2)) {
        return optEscape(match);
      }
      const replacement = vars2[key];
      if (isValidReplacement(replacement)) {
        return optEscape(`${replacement}`);
      } else if (typeof replacement === "object" && isValidReplacement(replacement.value)) {
        const escape = replacement.escape !== false ? escapeHTML : identity;
        return escape(`${replacement.value}`);
      } else {
        return optEscape(match);
      }
    });
  };
  const bundle = options?.bundle ?? getAppTranslations(app);
  let translation = bundle.translations[text2] || text2;
  translation = Array.isArray(translation) ? translation[0] : translation;
  if (typeof vars === "object" || number !== void 0) {
    return optSanitize(_build(
      translation,
      vars,
      number
    ));
  } else {
    return optSanitize(translation);
  }
}
function translatePlural(app, textSingular, textPlural, number, vars, options) {
  const identifier = "_" + textSingular + "_::_" + textPlural + "_";
  const bundle = options?.bundle ?? getAppTranslations(app);
  const value = bundle.translations[identifier];
  if (typeof value !== "undefined") {
    const translation = value;
    if (Array.isArray(translation)) {
      const plural = bundle.pluralFunction(number);
      return translate(app, translation[plural], vars, number, options);
    }
  }
  if (number === 1) {
    return translate(app, textSingular, vars, number, options);
  } else {
    return translate(app, textPlural, vars, number, options);
  }
}
function getPlural(number, language = getLanguage()) {
  if (language === "pt-BR") {
    language = "xbr";
  }
  if (language.length > 3) {
    language = language.substring(0, language.lastIndexOf("-"));
  }
  switch (language) {
    case "az":
    case "bo":
    case "dz":
    case "id":
    case "ja":
    case "jv":
    case "ka":
    case "km":
    case "kn":
    case "ko":
    case "ms":
    case "th":
    case "tr":
    case "vi":
    case "zh":
      return 0;
    case "af":
    case "bn":
    case "bg":
    case "ca":
    case "da":
    case "de":
    case "el":
    case "en":
    case "eo":
    case "es":
    case "et":
    case "eu":
    case "fa":
    case "fi":
    case "fo":
    case "fur":
    case "fy":
    case "gl":
    case "gu":
    case "ha":
    case "he":
    case "hu":
    case "is":
    case "it":
    case "ku":
    case "lb":
    case "ml":
    case "mn":
    case "mr":
    case "nah":
    case "nb":
    case "ne":
    case "nl":
    case "nn":
    case "no":
    case "oc":
    case "om":
    case "or":
    case "pa":
    case "pap":
    case "ps":
    case "pt":
    case "so":
    case "sq":
    case "sv":
    case "sw":
    case "ta":
    case "te":
    case "tk":
    case "ur":
    case "zu":
      return number === 1 ? 0 : 1;
    case "am":
    case "bh":
    case "fil":
    case "fr":
    case "gun":
    case "hi":
    case "hy":
    case "ln":
    case "mg":
    case "nso":
    case "xbr":
    case "ti":
    case "wa":
      return number === 0 || number === 1 ? 0 : 1;
    case "be":
    case "bs":
    case "hr":
    case "ru":
    case "sh":
    case "sr":
    case "uk":
      return number % 10 === 1 && number % 100 !== 11 ? 0 : number % 10 >= 2 && number % 10 <= 4 && (number % 100 < 10 || number % 100 >= 20) ? 1 : 2;
    case "cs":
    case "sk":
      return number === 1 ? 0 : number >= 2 && number <= 4 ? 1 : 2;
    case "ga":
      return number === 1 ? 0 : number === 2 ? 1 : 2;
    case "lt":
      return number % 10 === 1 && number % 100 !== 11 ? 0 : number % 10 >= 2 && (number % 100 < 10 || number % 100 >= 20) ? 1 : 2;
    case "sl":
      return number % 100 === 1 ? 0 : number % 100 === 2 ? 1 : number % 100 === 3 || number % 100 === 4 ? 2 : 3;
    case "mk":
      return number % 10 === 1 ? 0 : 1;
    case "mt":
      return number === 1 ? 0 : number === 0 || number % 100 > 1 && number % 100 < 11 ? 1 : number % 100 > 10 && number % 100 < 20 ? 2 : 3;
    case "lv":
      return number === 0 ? 0 : number % 10 === 1 && number % 100 !== 11 ? 1 : 2;
    case "pl":
      return number === 1 ? 0 : number % 10 >= 2 && number % 10 <= 4 && (number % 100 < 12 || number % 100 > 14) ? 1 : 2;
    case "cy":
      return number === 1 ? 0 : number === 2 ? 1 : number === 8 || number === 11 ? 2 : 3;
    case "ro":
      return number === 1 ? 0 : number === 0 || number % 100 > 0 && number % 100 < 20 ? 1 : 2;
    case "ar":
      return number === 0 ? 0 : number === 1 ? 1 : number === 2 ? 2 : number % 100 >= 3 && number % 100 <= 10 ? 3 : number % 100 >= 11 && number % 100 <= 99 ? 4 : 5;
    default:
      return 0;
  }
}
const _export_sfc$1 = (sfc, props) => {
  const target = sfc.__vccOpts || sfc;
  for (const [key, val] of props) {
    target[key] = val;
  }
  return target;
};
const _hoisted_1$a = ["aria-label"];
const _hoisted_2$9 = ["width", "height"];
const _hoisted_3$9 = ["fill"];
const _hoisted_4$8 = ["fill"];
const _hoisted_5 = { key: 0 };
const _sfc_main$a = /* @__PURE__ */ defineComponent({
  __name: "NcLoadingIcon",
  props: {
    appearance: { default: "auto" },
    name: { default: "" },
    size: { default: 20 }
  },
  setup(__props) {
    const props = __props;
    const colors = computed(() => {
      const colors2 = ["#777", "#CCC"];
      if (props.appearance === "light") {
        return colors2;
      } else if (props.appearance === "dark") {
        return colors2.reverse();
      }
      return ["var(--color-loading-light)", "var(--color-loading-dark)"];
    });
    return (_ctx, _cache) => {
      return openBlock(), createElementBlock("span", {
        "aria-label": _ctx.name,
        role: "img",
        class: "material-design-icon loading-icon"
      }, [
        (openBlock(), createElementBlock("svg", {
          width: _ctx.size,
          height: _ctx.size,
          viewBox: "0 0 24 24"
        }, [
          createBaseVNode("path", {
            fill: colors.value[0],
            d: "M12,4V2A10,10 0 1,0 22,12H20A8,8 0 1,1 12,4Z"
          }, null, 8, _hoisted_3$9),
          createBaseVNode("path", {
            fill: colors.value[1],
            d: "M12,4V2A10,10 0 0,1 22,12H20A8,8 0 0,0 12,4Z"
          }, [
            _ctx.name ? (openBlock(), createElementBlock("title", _hoisted_5, toDisplayString(_ctx.name), 1)) : createCommentVNode("", true)
          ], 8, _hoisted_4$8)
        ], 8, _hoisted_2$9))
      ], 8, _hoisted_1$a);
    };
  }
});
const NcLoadingIcon = /* @__PURE__ */ _export_sfc$1(_sfc_main$a, [["__scopeId", "data-v-cf399190"]]);
/*!
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: GPL-3.0-or-later
 */
class GettextWrapper {
  bundle;
  constructor(pluralFunction) {
    this.bundle = {
      pluralFunction,
      translations: {}
    };
  }
  /**
   * Append new translations to the wrapper.
   *
   * This is useful if translations should be added on demand,
   * e.g. depending on component usage.
   *
   * @param bundle - The new translation bundle to append
   */
  addTranslations(bundle) {
    const dict = Object.values(bundle.translations[""] ?? {}).map(({ msgid, msgid_plural: msgidPlural, msgstr }) => {
      if (msgidPlural !== void 0) {
        return [`_${msgid}_::_${msgidPlural}_`, msgstr];
      }
      return [msgid, msgstr[0]];
    });
    this.bundle.translations = {
      ...this.bundle.translations,
      ...Object.fromEntries(dict)
    };
  }
  /**
   * Get translated string (singular form), optionally with placeholders
   *
   * @param original original string to translate
   * @param placeholders map of placeholder key to value
   */
  gettext(original, placeholders = {}) {
    return translate("", original, placeholders, void 0, { bundle: this.bundle });
  }
  /**
   * Get translated string with plural forms
   *
   * @param singular Singular text form
   * @param plural Plural text form to be used if `count` requires it
   * @param count The number to insert into the text
   * @param placeholders optional map of placeholder key to value
   */
  ngettext(singular, plural, count, placeholders = {}) {
    return translatePlural("", singular, plural, count, placeholders, { bundle: this.bundle });
  }
}
class GettextBuilder {
  debug = false;
  language = "en";
  translations = {};
  setLanguage(language) {
    this.language = language;
    return this;
  }
  /**
   * Try to detect locale from context with `en` as fallback value
   * This only works within a Nextcloud page context.
   *
   * @deprecated use `detectLanguage` instead.
   */
  detectLocale() {
    return this.detectLanguage();
  }
  /**
   * Try to detect locale from context with `en` as fallback value.
   * This only works within a Nextcloud page context.
   */
  detectLanguage() {
    return this.setLanguage(getLanguage().replace("-", "_"));
  }
  /**
   * Register a new translation bundle for a specified language.
   *
   * Please note that existing translations for that language will be overwritten.
   *
   * @param language - Language this is the translation for
   * @param data - The translation bundle
   */
  addTranslation(language, data) {
    this.translations[language] = data;
    return this;
  }
  enableDebugMode() {
    this.debug = true;
    return this;
  }
  build() {
    if (this.debug) {
      console.debug(`Creating gettext instance for language ${this.language}`);
    }
    const wrapper = new GettextWrapper((n2) => getPlural(n2, this.language));
    if (this.language in this.translations) {
      wrapper.addTranslations(this.translations[this.language]);
    }
    return wrapper;
  }
}
function getGettextBuilder() {
  return new GettextBuilder();
}
/*!
 * SPDX-FileCopyrightText: Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
const gettext = getGettextBuilder().detectLanguage().build();
const n = (...args) => gettext.ngettext(...args);
const t = (...args) => gettext.gettext(...args);
function register(...chunks) {
  for (const chunk of chunks) {
    if (chunk.registered) {
      continue;
    }
    for (const { l: language, t: translations } of chunk) {
      if (language !== getLanguage() || !translations) {
        continue;
      }
      const decompressed = Object.fromEntries(Object.entries(translations).map(([id, value]) => [
        id,
        {
          msgid: id,
          msgid_plural: value.p,
          msgstr: value.v
        }
      ]));
      gettext.addTranslations({
        translations: {
          "": decompressed
        }
      });
    }
    chunk.registered = true;
  }
}
const t0 = [{ "l": "ar", "t": { "{tag} (restricted)": { "v": ["{tag} (مقيد)"] }, "Select a tag": { "v": ["اختر وسم"] } } }, { "l": "ast", "t": { "{tag} (restricted)": { "v": ["{tag} (restrinxóse)"] }, "Select a tag": { "v": ["Seleicionar una etiqueta"] } } }, { "l": "br", "t": { "{tag} (restricted)": { "v": ["{tag} (bevennet)"] }, "Select a tag": { "v": ["Choaz ur c'hlav"] } } }, { "l": "ca", "t": { "{tag} (restricted)": { "v": ["{tag} (restringit)"] }, "Select a tag": { "v": ["Seleccioneu una etiqueta"] } } }, { "l": "cs", "t": { "{tag} (restricted)": { "v": ["{tag} (omezené)"] }, "Select a tag": { "v": ["Vybrat štítek"] } } }, { "l": "cs-CZ", "t": { "{tag} (restricted)": { "v": ["{tag} (omezené)"] }, "Select a tag": { "v": ["Vybrat štítek"] } } }, { "l": "da", "t": { "{tag} (restricted)": { "v": ["{tag} (begrænset)"] }, "Select a tag": { "v": ["Vælg et mærke"] } } }, { "l": "de", "t": { "{tag} (restricted)": { "v": ["{tag} (eingeschränkt)"] }, "Select a tag": { "v": ["Schlagwort auswählen"] } } }, { "l": "de-DE", "t": { "{tag} (restricted)": { "v": ["{tag} (eingeschränkt)"] }, "Select a tag": { "v": ["Schlagwort auswählen"] } } }, { "l": "el", "t": { "{tag} (restricted)": { "v": ["{tag} (περιορισμένο)"] }, "Select a tag": { "v": ["Επιλογή ετικέτας"] } } }, { "l": "en-GB", "t": { "{tag} (restricted)": { "v": ["{tag} (restricted)"] }, "Select a tag": { "v": ["Select a tag"] } } }, { "l": "eo", "t": { "{tag} (restricted)": { "v": ["{tag} (limigita)"] }, "Select a tag": { "v": ["Elektu etikedon"] } } }, { "l": "es", "t": { "{tag} (restricted)": { "v": ["{tag} (restringido)"] }, "Select a tag": { "v": ["Seleccione una etiqueta"] } } }, { "l": "es-AR", "t": { "{tag} (restricted)": { "v": ["{tag} (restringido)"] }, "Select a tag": { "v": ["Elija una etiqueta"] } } }, { "l": "es-EC", "t": { "{tag} (restricted)": { "v": ["{tag} (restricted)"] }, "Select a tag": { "v": ["Seleccionar una etiqueta"] } } }, { "l": "es-MX", "t": { "{tag} (restricted)": { "v": ["{tag} (restringido)"] }, "Select a tag": { "v": ["Seleccionar una etiqueta"] } } }, { "l": "et-EE", "t": { "{tag} (restricted)": { "v": ["{tag} (piiratud)"] }, "Select a tag": { "v": ["Vali silt"] } } }, { "l": "eu", "t": { "{tag} (restricted)": { "v": ["{tag} (mugatua)"] }, "Select a tag": { "v": ["Hautatu etiketa bat"] } } }, { "l": "fa", "t": { "{tag} (restricted)": { "v": ["{tag} محدود شده"] }, "Select a tag": { "v": ["انتخاب یک برچسب"] } } }, { "l": "fi", "t": { "{tag} (restricted)": { "v": ["{tag} (rajoitettu)"] }, "Select a tag": { "v": ["Valitse tunniste"] } } }, { "l": "fr", "t": { "{tag} (restricted)": { "v": ["{tag} (restreint)"] }, "Select a tag": { "v": ["Sélectionnez une balise"] } } }, { "l": "ga", "t": { "{tag} (restricted)": { "v": ["{tag} (srianta)"] }, "Select a tag": { "v": ["Roghnaigh clib"] } } }, { "l": "gl", "t": { "{tag} (restricted)": { "v": ["{tag} (restrinxido)"] }, "Select a tag": { "v": ["Seleccione unha etiqueta"] } } }, { "l": "he", "t": { "{tag} (restricted)": { "v": ["{tag} (מוגבל)"] }, "Select a tag": { "v": ["בחירת תגית"] } } }, { "l": "hu", "t": { "{tag} (restricted)": { "v": ["{tag} (korlátozott)"] }, "Select a tag": { "v": ["Válasszon címkét"] } } }, { "l": "id", "t": { "{tag} (restricted)": { "v": ["{tag} (dibatasi)"] }, "Select a tag": { "v": ["Pilih tag"] } } }, { "l": "is", "t": { "{tag} (restricted)": { "v": ["{tag} (takmarkað)"] }, "Select a tag": { "v": ["Veldu merki"] } } }, { "l": "it", "t": { "{tag} (restricted)": { "v": ["{tag} (limitato)"] }, "Select a tag": { "v": ["Seleziona un'etichetta"] } } }, { "l": "ja", "t": { "{tag} (restricted)": { "v": ["{tag} (制限付)"] }, "Select a tag": { "v": ["タグを選択"] } } }, { "l": "ja-JP", "t": { "{tag} (restricted)": { "v": ["{tag} (制限付)"] }, "Select a tag": { "v": ["タグを選択"] } } }, { "l": "ko", "t": { "{tag} (restricted)": { "v": ["{tag}(제한)"] }, "Select a tag": { "v": ["태그 선택"] } } }, { "l": "lo", "t": { "{tag} (restricted)": { "v": ["{tag} (ຈຳກັດ)"] }, "Select a tag": { "v": ["ເລືອກແທັກ"] } } }, { "l": "lt-LT", "t": { "{tag} (restricted)": { "v": ["{tag} (apribota)"] }, "Select a tag": { "v": ["Pasirinkti žymę"] } } }, { "l": "lv", "t": { "{tag} (restricted)": { "v": ["{tag} (ierobežots)"] }, "Select a tag": { "v": ["Izvēlēties birku"] } } }, { "l": "mk", "t": { "{tag} (restricted)": { "v": ["{tag} (ограничено)"] }, "Select a tag": { "v": ["Избери ознака"] } } }, { "l": "my", "t": { "{tag} (restricted)": { "v": ["{tag} (ကန့်သတ်)"] }, "Select a tag": { "v": ["tag ရွေးချယ်ရန်"] } } }, { "l": "nb", "t": { "{tag} (restricted)": { "v": ["{tag} (beskyttet)"] }, "Select a tag": { "v": ["Velg en merkelapp"] } } }, { "l": "nl", "t": { "{tag} (restricted)": { "v": ["{tag} (beperkt)"] }, "Select a tag": { "v": ["Selecteer een label"] } } }, { "l": "oc", "t": { "{tag} (restricted)": { "v": ["{tag} (limit)"] }, "Select a tag": { "v": ["Seleccionar una etiqueta"] } } }, { "l": "pl", "t": { "{tag} (restricted)": { "v": ["{tag} (ograniczona)"] }, "Select a tag": { "v": ["Wybierz etykietę"] } } }, { "l": "pt-BR", "t": { "{tag} (restricted)": { "v": ["{tag} (restrito)"] }, "Select a tag": { "v": ["Selecione uma etiqueta"] } } }, { "l": "pt-PT", "t": { "{tag} (restricted)": { "v": ["{tag} (restrito)"] }, "Select a tag": { "v": ["Selecionar uma etiqueta"] } } }, { "l": "ro", "t": { "{tag} (restricted)": { "v": ["{tag} (restricționat)"] }, "Select a tag": { "v": ["Selectați o etichetă"] } } }, { "l": "ru", "t": { "{tag} (restricted)": { "v": ["{tag} (ограниченное)"] }, "Select a tag": { "v": ["Выберите метку"] } } }, { "l": "sk", "t": { "{tag} (restricted)": { "v": ["{tag} (obmedzený)"] }, "Select a tag": { "v": ["Vybrať štítok"] } } }, { "l": "sl", "t": { "{tag} (restricted)": { "v": ["{tag} (omejeno)"] }, "Select a tag": { "v": ["Izbor oznake"] } } }, { "l": "sr", "t": { "{tag} (restricted)": { "v": ["{tag} (ограничено)"] }, "Select a tag": { "v": ["Изаберите ознаку"] } } }, { "l": "sv", "t": { "{tag} (restricted)": { "v": ["{tag} (begränsad)"] }, "Select a tag": { "v": ["Välj en tag"] } } }, { "l": "tr", "t": { "{tag} (restricted)": { "v": ["{tag} (kısıtlanmış)"] }, "Select a tag": { "v": ["Bir etiket seçin"] } } }, { "l": "uk", "t": { "{tag} (restricted)": { "v": ["{tag} (обмежений)"] }, "Select a tag": { "v": ["Виберіть позначку"] } } }, { "l": "uz", "t": { "{tag} (restricted)": { "v": ["{tag} (cheklangan)"] }, "Select a tag": { "v": ["Teg tanlang"] } } }, { "l": "zh-CN", "t": { "{tag} (restricted)": { "v": ["{tag} （受限）"] }, "Select a tag": { "v": ["选择一个标签"] } } }, { "l": "zh-HK", "t": { "{tag} (restricted)": { "v": ["{tag} (受限)"] }, "Select a tag": { "v": ["選擇標籤"] } } }, { "l": "zh-TW", "t": { "{tag} (restricted)": { "v": ["{tag}（受限）"] }, "Select a tag": { "v": ["選擇標籤"] } } }];
const t2 = [{ "l": "ar", "t": { "a few seconds ago": { "v": ["منذ عدة ثوانٍ"] }, "sec. ago": { "v": ["ثانية مضت"] }, "seconds ago": { "v": ["ثوانٍ مضت"] } } }, { "l": "ast", "t": { "a few seconds ago": { "v": ["hai unos segundos"] }, "sec. ago": { "v": ["hai segs"] }, "seconds ago": { "v": ["hai segundos"] } } }, { "l": "br", "t": {} }, { "l": "ca", "t": {} }, { "l": "cs", "t": { "a few seconds ago": { "v": ["před několika sekundami"] }, "sec. ago": { "v": ["sek. před"] }, "seconds ago": { "v": ["sekund předtím"] } } }, { "l": "cs-CZ", "t": { "a few seconds ago": { "v": ["před několika sekundami"] }, "sec. ago": { "v": ["sek. před"] }, "seconds ago": { "v": ["sekund předtím"] } } }, { "l": "da", "t": { "a few seconds ago": { "v": ["et par sekunder siden"] }, "sec. ago": { "v": ["sek. siden"] }, "seconds ago": { "v": ["sekunder siden"] } } }, { "l": "de", "t": { "a few seconds ago": { "v": ["vor ein paar Sekunden"] }, "sec. ago": { "v": ["Sek. zuvor"] }, "seconds ago": { "v": ["Sekunden zuvor"] } } }, { "l": "de-DE", "t": { "a few seconds ago": { "v": ["vor ein paar Sekunden"] }, "sec. ago": { "v": ["Sek. zuvor"] }, "seconds ago": { "v": ["Sekunden zuvor"] } } }, { "l": "el", "t": { "a few seconds ago": { "v": ["πριν λίγα δευτερόλεπτα"] }, "sec. ago": { "v": ["δευτ. πριν"] }, "seconds ago": { "v": ["δευτερόλεπτα πριν"] } } }, { "l": "en-GB", "t": { "a few seconds ago": { "v": ["a few seconds ago"] }, "sec. ago": { "v": ["sec. ago"] }, "seconds ago": { "v": ["seconds ago"] } } }, { "l": "eo", "t": {} }, { "l": "es", "t": { "a few seconds ago": { "v": ["hace unos pocos segundos"] }, "sec. ago": { "v": ["hace segundos"] }, "seconds ago": { "v": ["segundos atrás"] } } }, { "l": "es-AR", "t": { "a few seconds ago": { "v": ["hace unos segundos"] }, "sec. ago": { "v": ["seg. atrás"] }, "seconds ago": { "v": ["segundos atrás"] } } }, { "l": "es-EC", "t": { "a few seconds ago": { "v": ["hace unos segundos"] }, "sec. ago": { "v": ["hace segundos"] }, "seconds ago": { "v": ["Segundos atrás"] } } }, { "l": "es-MX", "t": { "a few seconds ago": { "v": ["hace unos segundos"] }, "sec. ago": { "v": ["seg. atrás"] }, "seconds ago": { "v": ["segundos atrás"] } } }, { "l": "et-EE", "t": { "a few seconds ago": { "v": ["mõni sekund tagasi"] }, "sec. ago": { "v": ["sek. tagasi"] }, "seconds ago": { "v": ["sekundit tagasi"] } } }, { "l": "eu", "t": { "a few seconds ago": { "v": ["duela segundo batzuk"] }, "sec. ago": { "v": ["duela seg."] }, "seconds ago": { "v": ["duela segundo"] } } }, { "l": "fa", "t": { "a few seconds ago": { "v": ["چند ثانیه پیش"] }, "sec. ago": { "v": ["چند ثانیه پیش"] }, "seconds ago": { "v": ["چند ثانیه پیش"] } } }, { "l": "fi", "t": { "a few seconds ago": { "v": ["muutamia sekunteja sitten"] }, "sec. ago": { "v": ["sek. sitten"] }, "seconds ago": { "v": ["sekunteja sitten"] } } }, { "l": "fr", "t": { "a few seconds ago": { "v": ["il y a quelques instants"] }, "sec. ago": { "v": ["il y a qq. sec."] }, "seconds ago": { "v": ["il y a quelques secondes"] } } }, { "l": "ga", "t": { "a few seconds ago": { "v": ["cúpla soicind ó shin"] }, "sec. ago": { "v": ["soic. ó shin"] }, "seconds ago": { "v": ["soicind ó shin"] } } }, { "l": "gl", "t": { "a few seconds ago": { "v": ["hai uns segundos"] }, "sec. ago": { "v": ["segs. atrás"] }, "seconds ago": { "v": ["segundos atrás"] } } }, { "l": "he", "t": { "a few seconds ago": { "v": ["לפני מספר שניות"] }, "sec. ago": { "v": ["לפני מספר שניות"] }, "seconds ago": { "v": ["לפני מס׳ שניות"] } } }, { "l": "hu", "t": {} }, { "l": "id", "t": { "a few seconds ago": { "v": ["beberapa detik yang lalu"] }, "sec. ago": { "v": ["dtk. yang lalu"] }, "seconds ago": { "v": ["beberapa detik lalu"] } } }, { "l": "is", "t": { "a few seconds ago": { "v": ["fyrir örfáum sekúndum síðan"] }, "sec. ago": { "v": ["sek. síðan"] }, "seconds ago": { "v": ["sekúndum síðan"] } } }, { "l": "it", "t": { "a few seconds ago": { "v": ["pochi secondi fa"] }, "sec. ago": { "v": ["sec. fa"] }, "seconds ago": { "v": ["secondi fa"] } } }, { "l": "ja", "t": { "a few seconds ago": { "v": ["数秒前"] }, "sec. ago": { "v": ["秒前"] }, "seconds ago": { "v": ["数秒前"] } } }, { "l": "ja-JP", "t": { "a few seconds ago": { "v": ["数秒前"] }, "sec. ago": { "v": ["秒前"] }, "seconds ago": { "v": ["数秒前"] } } }, { "l": "ko", "t": { "a few seconds ago": { "v": ["방금 전"] }, "sec. ago": { "v": ["몇 초 전"] }, "seconds ago": { "v": ["초 전"] } } }, { "l": "lo", "t": { "a few seconds ago": { "v": ["ສອງສາມວິນາທີກ່ອນ"] }, "sec. ago": { "v": ["ວິ. ກ່ອນ"] }, "seconds ago": { "v": ["ວິນາທີກ່ອນ"] } } }, { "l": "lt-LT", "t": {} }, { "l": "lv", "t": {} }, { "l": "mk", "t": { "a few seconds ago": { "v": ["пред неколку секунди"] }, "sec. ago": { "v": ["секунда"] }, "seconds ago": { "v": ["секунди"] } } }, { "l": "my", "t": {} }, { "l": "nb", "t": { "a few seconds ago": { "v": ["noen få sekunder siden"] }, "sec. ago": { "v": ["sek. siden"] }, "seconds ago": { "v": ["sekunder siden"] } } }, { "l": "nl", "t": { "a few seconds ago": { "v": ["enkele seconden geleden"] }, "sec. ago": { "v": ["sec. geleden"] }, "seconds ago": { "v": ["seconden geleden"] } } }, { "l": "oc", "t": {} }, { "l": "pl", "t": { "a few seconds ago": { "v": ["kilka sekund temu"] }, "sec. ago": { "v": ["sek. temu"] }, "seconds ago": { "v": ["sekund temu"] } } }, { "l": "pt-BR", "t": { "a few seconds ago": { "v": ["há alguns segundos"] }, "sec. ago": { "v": ["seg. atrás"] }, "seconds ago": { "v": ["segundos atrás"] } } }, { "l": "pt-PT", "t": { "a few seconds ago": { "v": ["há alguns segundos"] }, "sec. ago": { "v": ["seg. atrás"] }, "seconds ago": { "v": ["segundos atrás"] } } }, { "l": "ro", "t": { "a few seconds ago": { "v": ["acum câteva secunde"] }, "sec. ago": { "v": ["sec. în urmă"] }, "seconds ago": { "v": ["secunde în urmă"] } } }, { "l": "ru", "t": { "a few seconds ago": { "v": ["несколько секунд назад"] }, "sec. ago": { "v": ["сек. назад"] }, "seconds ago": { "v": ["секунд назад"] } } }, { "l": "sk", "t": { "a few seconds ago": { "v": ["pred chvíľou"] }, "sec. ago": { "v": ["pred pár sekundami"] }, "seconds ago": { "v": ["pred sekundami"] } } }, { "l": "sl", "t": {} }, { "l": "sr", "t": { "a few seconds ago": { "v": ["пре неколико секунди"] }, "sec. ago": { "v": ["сек. раније"] }, "seconds ago": { "v": ["секунди раније"] } } }, { "l": "sv", "t": { "a few seconds ago": { "v": ["några sekunder sedan"] }, "sec. ago": { "v": ["sek. sedan"] }, "seconds ago": { "v": ["sekunder sedan"] } } }, { "l": "tr", "t": { "a few seconds ago": { "v": ["birkaç saniye önce"] }, "sec. ago": { "v": ["sn. önce"] }, "seconds ago": { "v": ["saniye önce"] } } }, { "l": "uk", "t": { "a few seconds ago": { "v": ["декілька секунд тому"] }, "sec. ago": { "v": ["с тому"] }, "seconds ago": { "v": ["с тому"] } } }, { "l": "uz", "t": { "a few seconds ago": { "v": ["bir necha soniya oldin"] }, "sec. ago": { "v": ["sek. oldin"] }, "seconds ago": { "v": ["soniyalar oldin"] } } }, { "l": "zh-CN", "t": { "a few seconds ago": { "v": ["几秒前"] }, "sec. ago": { "v": ["几秒前"] }, "seconds ago": { "v": ["几秒前"] } } }, { "l": "zh-HK", "t": { "a few seconds ago": { "v": ["幾秒前"] }, "sec. ago": { "v": ["秒前"] }, "seconds ago": { "v": ["秒前"] } } }, { "l": "zh-TW", "t": { "a few seconds ago": { "v": ["幾秒前"] }, "sec. ago": { "v": ["秒前"] }, "seconds ago": { "v": ["秒前"] } } }];
const t3 = [{ "l": "ar", "t": { "Acapulco": { "v": ["بازلائي مطفي"] }, "Blue Violet": { "v": ["بنفسجي مشعشع"] }, "Boston Blue": { "v": ["سماوي مطفي"] }, "Deluge": { "v": ["بنفسجي مطفي"] }, "Feldspar": { "v": ["وردي صخري"] }, "Gold": { "v": ["ذهبي"] }, "Mariner": { "v": ["أزرق بحري"] }, "Nextcloud blue": { "v": ["أزرق نكست كلاود"] }, "Olivine": { "v": ["زيتي"] }, "Purple": { "v": ["بنفسجي"] }, "Rosy brown": { "v": ["بُنِّي زهري"] }, "Whiskey": { "v": ["نبيذي"] } } }, { "l": "ast", "t": { "Acapulco": { "v": ["Acapulcu"] }, "Blue Violet": { "v": ["Viola azulao"] }, "Boston Blue": { "v": ["Azul Boston"] }, "Deluge": { "v": ["Deluge"] }, "Feldspar": { "v": ["Feldspar"] }, "Gold": { "v": ["Oru"] }, "Mariner": { "v": ["Marineru"] }, "Nextcloud blue": { "v": ["Nextcloud azul"] }, "Olivine": { "v": ["Olivina"] }, "Purple": { "v": ["Moráu"] }, "Rosy brown": { "v": ["Marrón arrosao"] }, "Whiskey": { "v": ["Whiskey"] } } }, { "l": "br", "t": {} }, { "l": "ca", "t": {} }, { "l": "cs", "t": { "Acapulco": { "v": ["Akapulko"] }, "Black": { "v": ["Černá"] }, "Blue Violet": { "v": ["Modrofialová"] }, "Boston Blue": { "v": ["Bostonská modrá"] }, "Deluge": { "v": ["Deluge"] }, "Feldspar": { "v": ["Živicová"] }, "Gold": { "v": ["Zlatá"] }, "Mariner": { "v": ["Námořnická"] }, "Nextcloud blue": { "v": ["Nextcloud modrá"] }, "Olivine": { "v": ["Olivínová"] }, "Purple": { "v": ["Fialová"] }, "Rosy brown": { "v": ["Růžovohnědá"] }, "Whiskey": { "v": ["Whisky"] }, "White": { "v": ["Bílá"] } } }, { "l": "cs-CZ", "t": { "Acapulco": { "v": ["Akapulko"] }, "Blue Violet": { "v": ["Modrofialová"] }, "Boston Blue": { "v": ["Bostonská modrá"] }, "Deluge": { "v": ["Deluge"] }, "Feldspar": { "v": ["Živicová"] }, "Gold": { "v": ["Zlatá"] }, "Mariner": { "v": ["Námořnická"] }, "Nextcloud blue": { "v": ["Nextcloud modrá"] }, "Olivine": { "v": ["Olivínová"] }, "Purple": { "v": ["Fialová"] }, "Rosy brown": { "v": ["Růžovohnědá"] }, "Whiskey": { "v": ["Whisky"] } } }, { "l": "da", "t": { "Acapulco": { "v": ["Acapulco"] }, "Black": { "v": ["Sort"] }, "Blue Violet": { "v": ["Blue Violet"] }, "Boston Blue": { "v": ["Boston Blue"] }, "Deluge": { "v": ["Deluge"] }, "Feldspar": { "v": ["Feldspar"] }, "Gold": { "v": ["Guld"] }, "Mariner": { "v": ["Mariner"] }, "Nextcloud blue": { "v": ["Nextcloud blue"] }, "Olivine": { "v": ["Olivine"] }, "Purple": { "v": ["Lilla"] }, "Rosy brown": { "v": ["Rosy brown"] }, "Whiskey": { "v": ["Whiskey"] }, "White": { "v": ["Hvid"] } } }, { "l": "de", "t": { "Acapulco": { "v": ["Acapulco"] }, "Black": { "v": ["Schwarz"] }, "Blue Violet": { "v": ["Blau Violett"] }, "Boston Blue": { "v": ["Boston-Blau"] }, "Deluge": { "v": ["Sintflut"] }, "Feldspar": { "v": ["Feldspat"] }, "Gold": { "v": ["Gold"] }, "Mariner": { "v": ["Seemann"] }, "Nextcloud blue": { "v": ["Nextcloud Blau"] }, "Olivine": { "v": ["Olivin"] }, "Purple": { "v": ["Lila"] }, "Rosy brown": { "v": ["Rosiges Braun"] }, "Whiskey": { "v": ["Whiskey"] }, "White": { "v": ["Weiß"] } } }, { "l": "de-DE", "t": { "Acapulco": { "v": ["Acapulco"] }, "Black": { "v": ["Schwarz"] }, "Blue Violet": { "v": ["Blau Violett"] }, "Boston Blue": { "v": ["Boston-Blau"] }, "Deluge": { "v": ["Sintflut"] }, "Feldspar": { "v": ["Feldspat"] }, "Gold": { "v": ["Gold"] }, "Mariner": { "v": ["Seemann"] }, "Nextcloud blue": { "v": ["Nextcloud Blau"] }, "Olivine": { "v": ["Olivin"] }, "Purple": { "v": ["Lila"] }, "Rosy brown": { "v": ["Rosiges Braun"] }, "Whiskey": { "v": ["Whiskey"] }, "White": { "v": ["Weiß"] } } }, { "l": "el", "t": { "Acapulco": { "v": ["Ακαπούλκο"] }, "Black": { "v": ["Μαύρο"] }, "Blue Violet": { "v": ["Μπλε Βιολέτ"] }, "Boston Blue": { "v": ["Μπλε Βοστώνης"] }, "Deluge": { "v": ["Deluge"] }, "Feldspar": { "v": ["Feldspar"] }, "Gold": { "v": ["Χρυσό"] }, "Mariner": { "v": ["Mariner"] }, "Nextcloud blue": { "v": ["Μπλε Nextcloud"] }, "Olivine": { "v": ["Olivine"] }, "Purple": { "v": ["Μωβ"] }, "Rosy brown": { "v": ["Ροζ καφέ"] }, "Whiskey": { "v": ["Ουίσκι"] }, "White": { "v": ["Λευκό"] } } }, { "l": "en-GB", "t": { "Acapulco": { "v": ["Acapulco"] }, "Black": { "v": ["Black"] }, "Blue Violet": { "v": ["Blue Violet"] }, "Boston Blue": { "v": ["Boston Blue"] }, "Deluge": { "v": ["Deluge"] }, "Feldspar": { "v": ["Feldspar"] }, "Gold": { "v": ["Gold"] }, "Mariner": { "v": ["Mariner"] }, "Nextcloud blue": { "v": ["Nextcloud blue"] }, "Olivine": { "v": ["Olivine"] }, "Purple": { "v": ["Purple"] }, "Rosy brown": { "v": ["Rosy brown"] }, "Whiskey": { "v": ["Whiskey"] }, "White": { "v": ["White"] } } }, { "l": "eo", "t": {} }, { "l": "es", "t": { "Acapulco": { "v": ["Acapulco"] }, "Blue Violet": { "v": ["Violeta Azul"] }, "Boston Blue": { "v": ["Azul Boston"] }, "Deluge": { "v": ["Diluvio"] }, "Feldspar": { "v": ["Feldespato"] }, "Gold": { "v": ["Oro"] }, "Mariner": { "v": ["Marinero"] }, "Nextcloud blue": { "v": ["Azul Nextcloud"] }, "Olivine": { "v": ["Olivino"] }, "Purple": { "v": ["Púrpura"] }, "Rosy brown": { "v": ["Marrón rosáceo"] }, "Whiskey": { "v": ["Whiskey"] } } }, { "l": "es-AR", "t": { "Acapulco": { "v": ["Acapulco"] }, "Blue Violet": { "v": ["Violeta Azul"] }, "Boston Blue": { "v": ["Azul Boston"] }, "Deluge": { "v": ["Diluvio"] }, "Feldspar": { "v": ["Feldespato"] }, "Gold": { "v": ["Oro"] }, "Mariner": { "v": ["Marinero"] }, "Nextcloud blue": { "v": ["Azul Nextcloud"] }, "Olivine": { "v": ["Olivino"] }, "Purple": { "v": ["Púrpura"] }, "Rosy brown": { "v": ["Marrón rosáceo"] }, "Whiskey": { "v": ["Whiskey"] } } }, { "l": "es-EC", "t": {} }, { "l": "es-MX", "t": { "Acapulco": { "v": ["Acapulco"] }, "Blue Violet": { "v": ["Violeta Azul"] }, "Boston Blue": { "v": ["Azul Boston"] }, "Deluge": { "v": ["Diluvio"] }, "Feldspar": { "v": ["Feldespato"] }, "Gold": { "v": ["Oro"] }, "Mariner": { "v": ["Marinero"] }, "Nextcloud blue": { "v": ["Azul Nextcloud"] }, "Olivine": { "v": ["Olivino"] }, "Purple": { "v": ["Púrpura"] }, "Rosy brown": { "v": ["Marrón rosáceo"] }, "Whiskey": { "v": ["Whiskey"] } } }, { "l": "et-EE", "t": { "Acapulco": { "v": ["Acapulco meresinine"] }, "Black": { "v": ["Must"] }, "Blue Violet": { "v": ["Sinakasvioletne"] }, "Boston Blue": { "v": ["Bostoni rohekassinine"] }, "Deluge": { "v": ["Tulvavee lilla"] }, "Feldspar": { "v": ["Põlevkivipruun"] }, "Gold": { "v": ["Kuldne"] }, "Mariner": { "v": ["Meresinine"] }, "Nextcloud blue": { "v": ["Nextcloudi sinine"] }, "Olivine": { "v": ["Oliiviroheline"] }, "Purple": { "v": ["Purpurpunane"] }, "Rosy brown": { "v": ["Roosikarva pruun"] }, "Whiskey": { "v": ["Viskikarva kollakaspruun"] }, "White": { "v": ["Valge"] } } }, { "l": "eu", "t": {} }, { "l": "fa", "t": { "Acapulco": { "v": ["آکاپولکو"] }, "Blue Violet": { "v": ["بنفش آبی"] }, "Boston Blue": { "v": ["آبی بوستونی"] }, "Deluge": { "v": ["سیل"] }, "Feldspar": { "v": ["فلدسپات"] }, "Gold": { "v": ["طلا"] }, "Mariner": { "v": ["مارینر"] }, "Nextcloud blue": { "v": ["نکس کلود آبی"] }, "Olivine": { "v": ["الیوین"] }, "Purple": { "v": ["بنفش"] }, "Rosy brown": { "v": ["قهوه‌ای رز"] }, "Whiskey": { "v": ["ویسکی"] } } }, { "l": "fi", "t": { "Acapulco": { "v": ["Acapulco"] }, "Blue Violet": { "v": ["Sinivioletti"] }, "Boston Blue": { "v": ["Bostoninsininen"] }, "Deluge": { "v": ["Tulva"] }, "Feldspar": { "v": ["Feldspar"] }, "Gold": { "v": ["Kulta"] }, "Mariner": { "v": ["Merenkulkija"] }, "Nextcloud blue": { "v": ["Nextcloudin sininen"] }, "Olivine": { "v": ["Oliviini"] }, "Purple": { "v": ["Purppura"] }, "Rosy brown": { "v": ["Ruusunruskea"] }, "Whiskey": { "v": ["Viski"] } } }, { "l": "fr", "t": { "Acapulco": { "v": ["Acapulco"] }, "Blue Violet": { "v": ["Bleu violet"] }, "Boston Blue": { "v": ["Bleu de Boston"] }, "Deluge": { "v": ["Deluge"] }, "Feldspar": { "v": ["Feldspar"] }, "Gold": { "v": ["Doré"] }, "Mariner": { "v": ["Marin"] }, "Nextcloud blue": { "v": ["Bleu Nextcloud"] }, "Olivine": { "v": ["Olivine"] }, "Purple": { "v": ["Violet"] }, "Rosy brown": { "v": ["Brun rosé"] }, "Whiskey": { "v": ["Whiskey"] } } }, { "l": "ga", "t": { "Acapulco": { "v": ["Acapulco"] }, "Black": { "v": ["Dubh"] }, "Blue Violet": { "v": ["Gorm Violet"] }, "Boston Blue": { "v": ["Bostún Gorm"] }, "Deluge": { "v": ["Díle"] }, "Feldspar": { "v": ["Feldspar"] }, "Gold": { "v": ["Óir"] }, "Mariner": { "v": ["Mairnéalach"] }, "Nextcloud blue": { "v": ["Nextcloud gorm"] }, "Olivine": { "v": ["Olaivín"] }, "Purple": { "v": ["Corcra"] }, "Rosy brown": { "v": ["Rosach donn"] }, "Whiskey": { "v": ["Fuisce"] }, "White": { "v": ["Bán"] } } }, { "l": "gl", "t": { "Acapulco": { "v": ["Acapulco"] }, "Black": { "v": ["Negro"] }, "Blue Violet": { "v": ["Azul violeta"] }, "Boston Blue": { "v": ["Azul Boston"] }, "Deluge": { "v": ["Dioivo"] }, "Feldspar": { "v": ["Feldespato"] }, "Gold": { "v": ["Ouro"] }, "Mariner": { "v": ["Marino"] }, "Nextcloud blue": { "v": ["Azul Nextcloud"] }, "Olivine": { "v": ["Olivina"] }, "Purple": { "v": ["Púrpura"] }, "Rosy brown": { "v": ["Pardo rosado"] }, "Whiskey": { "v": ["Whisky"] }, "White": { "v": ["Branco"] } } }, { "l": "he", "t": {} }, { "l": "hu", "t": {} }, { "l": "id", "t": { "Gold": { "v": ["Emas"] }, "Nextcloud blue": { "v": ["Biru Nextcloud"] }, "Purple": { "v": ["Ungu"] } } }, { "l": "is", "t": { "Acapulco": { "v": ["Acapulco"] }, "Blue Violet": { "v": ["Bláklukka"] }, "Boston Blue": { "v": ["Bostonblátt"] }, "Deluge": { "v": ["Fjólublátt"] }, "Feldspar": { "v": ["Feldspat"] }, "Gold": { "v": ["Gull"] }, "Mariner": { "v": ["Sjóarablátt"] }, "Nextcloud blue": { "v": ["Nextcloud blátt"] }, "Olivine": { "v": ["Ólivín"] }, "Purple": { "v": ["Purpurablátt"] }, "Rosy brown": { "v": ["Rósabrúnt"] }, "Whiskey": { "v": ["Viský"] } } }, { "l": "it", "t": { "Gold": { "v": ["Oro"] }, "Nextcloud blue": { "v": ["Nextcloud blue"] }, "Purple": { "v": ["Viola"] } } }, { "l": "ja", "t": { "Acapulco": { "v": ["アカプルコ"] }, "Black": { "v": ["黒"] }, "Blue Violet": { "v": ["ブルーバイオレット"] }, "Boston Blue": { "v": ["ボストンブルー"] }, "Deluge": { "v": ["豪雨"] }, "Feldspar": { "v": ["長石"] }, "Gold": { "v": ["黄金"] }, "Mariner": { "v": ["船乗り"] }, "Nextcloud blue": { "v": ["ネクストクラウド・ブルー"] }, "Olivine": { "v": ["カンラン石"] }, "Purple": { "v": ["紫色"] }, "Rosy brown": { "v": ["バラ色"] }, "Whiskey": { "v": ["ウイスキー"] }, "White": { "v": ["白"] } } }, { "l": "ja-JP", "t": { "Acapulco": { "v": ["アカプルコ"] }, "Blue Violet": { "v": ["ブルーバイオレット"] }, "Boston Blue": { "v": ["ボストンブルー"] }, "Deluge": { "v": ["豪雨"] }, "Feldspar": { "v": ["長石"] }, "Gold": { "v": ["黄金"] }, "Mariner": { "v": ["船乗り"] }, "Nextcloud blue": { "v": ["ネクストクラウド・ブルー"] }, "Olivine": { "v": ["カンラン石"] }, "Purple": { "v": ["紫色"] }, "Rosy brown": { "v": ["バラ色"] }, "Whiskey": { "v": ["ウイスキー"] } } }, { "l": "ko", "t": { "Acapulco": { "v": ["아카풀코"] }, "Blue Violet": { "v": ["푸른 보라"] }, "Boston Blue": { "v": ["보스턴 블루"] }, "Deluge": { "v": ["폭우"] }, "Feldspar": { "v": ["장석"] }, "Gold": { "v": ["금"] }, "Mariner": { "v": ["뱃사람"] }, "Nextcloud blue": { "v": ["Nextcloud 파랑"] }, "Olivine": { "v": ["감람석"] }, "Purple": { "v": ["보라"] }, "Rosy brown": { "v": ["로지 브라운"] }, "Whiskey": { "v": ["위스키"] } } }, { "l": "lo", "t": { "Acapulco": { "v": ["Acapulco"] }, "Black": { "v": ["ສີດຳ"] }, "Blue Violet": { "v": ["Blue Violet"] }, "Boston Blue": { "v": ["Boston Blue"] }, "Deluge": { "v": ["Deluge"] }, "Feldspar": { "v": ["Feldspar"] }, "Gold": { "v": ["ສີຄຳ"] }, "Mariner": { "v": ["Mariner"] }, "Nextcloud blue": { "v": ["ສີຟ້າ Nextcloud"] }, "Olivine": { "v": ["Olivine"] }, "Purple": { "v": ["ສີມ່ວງ"] }, "Rosy brown": { "v": ["Rosy brown"] }, "Whiskey": { "v": ["Whiskey"] }, "White": { "v": ["ສີຂາວ"] } } }, { "l": "lt-LT", "t": {} }, { "l": "lv", "t": {} }, { "l": "mk", "t": { "Acapulco": { "v": ["Акапулко"] }, "Black": { "v": ["Црно"] }, "Blue Violet": { "v": ["Сино Виолетова"] }, "Boston Blue": { "v": ["Бостон Сина"] }, "Deluge": { "v": ["Делуџ"] }, "Feldspar": { "v": ["Фелдспар"] }, "Gold": { "v": ["Златна"] }, "Mariner": { "v": ["Маринер"] }, "Nextcloud blue": { "v": ["Nextcloud сина"] }, "Olivine": { "v": ["Оливин"] }, "Purple": { "v": ["Виолетова"] }, "Rosy brown": { "v": ["Розево-кафеава"] }, "Whiskey": { "v": ["Виски"] }, "White": { "v": ["Бела"] } } }, { "l": "my", "t": {} }, { "l": "nb", "t": { "Acapulco": { "v": ["Acapulco"] }, "Blue Violet": { "v": ["Blå fiolett"] }, "Boston Blue": { "v": ["Boston blå"] }, "Deluge": { "v": ["Syndflod"] }, "Feldspar": { "v": ["Feltspat"] }, "Gold": { "v": ["Gull"] }, "Mariner": { "v": ["Mariner"] }, "Nextcloud blue": { "v": ["Nextcloud-blå"] }, "Olivine": { "v": ["Olivin"] }, "Purple": { "v": ["Lilla"] }, "Rosy brown": { "v": ["Rosenrød brun"] }, "Whiskey": { "v": ["Whiskey"] } } }, { "l": "nl", "t": { "Acapulco": { "v": ["Acapulco"] }, "Black": { "v": ["Zwart"] }, "Blue Violet": { "v": ["Blauw Paars"] }, "Boston Blue": { "v": ["Boston Blauw"] }, "Deluge": { "v": ["Overlopen"] }, "Feldspar": { "v": ["Veldspaat"] }, "Gold": { "v": ["Goud"] }, "Mariner": { "v": ["Marineblauw"] }, "Nextcloud blue": { "v": ["Nextcloud blauw"] }, "Olivine": { "v": ["Olivijn"] }, "Purple": { "v": ["Paars"] }, "Rosy brown": { "v": ["Rozig bruin"] }, "Whiskey": { "v": ["Whiskey"] }, "White": { "v": ["Wit"] } } }, { "l": "oc", "t": {} }, { "l": "pl", "t": { "Acapulco": { "v": ["Acapulco"] }, "Blue Violet": { "v": ["Niebieski fiolet"] }, "Boston Blue": { "v": ["Błękit Bostonu"] }, "Deluge": { "v": ["Potop"] }, "Feldspar": { "v": ["Skaleń"] }, "Gold": { "v": ["Złote"] }, "Mariner": { "v": ["Marynarz"] }, "Nextcloud blue": { "v": ["Niebieskie Nextcloud"] }, "Olivine": { "v": ["Oliwin"] }, "Purple": { "v": ["Fioletowy"] }, "Rosy brown": { "v": ["Różowy brąz"] }, "Whiskey": { "v": ["Whisky"] } } }, { "l": "pt-BR", "t": { "Acapulco": { "v": ["Acapulco"] }, "Black": { "v": ["Preto"] }, "Blue Violet": { "v": ["Violeta Azul"] }, "Boston Blue": { "v": ["Azul Boston"] }, "Deluge": { "v": ["Deluge"] }, "Feldspar": { "v": ["Feldspato"] }, "Gold": { "v": ["Ouro"] }, "Mariner": { "v": ["Marinheiro"] }, "Nextcloud blue": { "v": ["Azul Nextcloud"] }, "Olivine": { "v": ["Olivina"] }, "Purple": { "v": ["Roxo"] }, "Rosy brown": { "v": ["Castanho rosado"] }, "Whiskey": { "v": ["Uísque"] }, "White": { "v": ["Branco"] } } }, { "l": "pt-PT", "t": { "Acapulco": { "v": ["Acapulco"] }, "Blue Violet": { "v": ["Azul violeta"] }, "Boston Blue": { "v": ["Azul Boston"] }, "Deluge": { "v": ["Deluge"] }, "Feldspar": { "v": ["Feldspar"] }, "Gold": { "v": ["Ouro"] }, "Mariner": { "v": ["Mariner"] }, "Nextcloud blue": { "v": ["Nextcloud azul"] }, "Olivine": { "v": ["Olivine"] }, "Purple": { "v": ["Púrpura"] }, "Rosy brown": { "v": ["Castanho rosado"] }, "Whiskey": { "v": ["Whiskey"] } } }, { "l": "ro", "t": { "Gold": { "v": ["Aur"] }, "Nextcloud blue": { "v": ["Nextcloud albastru"] }, "Purple": { "v": ["Purpuriu"] } } }, { "l": "ru", "t": { "Acapulco": { "v": ["Акапулько"] }, "Blue Violet": { "v": ["Синий фиолет"] }, "Boston Blue": { "v": ["Синий Бостон"] }, "Deluge": { "v": ["Перламутрово-фиолетовый"] }, "Feldspar": { "v": ["Античная латунь"] }, "Gold": { "v": ["Золотой"] }, "Mariner": { "v": ["Морской"] }, "Nextcloud blue": { "v": ["Nextcloud голубой"] }, "Olivine": { "v": [" Оливковый"] }, "Purple": { "v": ["Фиолетовый"] }, "Rosy brown": { "v": ["Розово-коричневый"] }, "Whiskey": { "v": ["Виски"] } } }, { "l": "sk", "t": { "Acapulco": { "v": ["Acapulco"] }, "Blue Violet": { "v": ["Modro fialová"] }, "Boston Blue": { "v": ["Bostonská modrá"] }, "Deluge": { "v": ["Deluge"] }, "Feldspar": { "v": ["Živec"] }, "Gold": { "v": ["Zlatá"] }, "Mariner": { "v": ["Námorník"] }, "Nextcloud blue": { "v": ["Nextcloud modrá"] }, "Olivine": { "v": ["Olivová"] }, "Purple": { "v": ["Fialová"] }, "Rosy brown": { "v": ["Ružovo hnedá"] }, "Whiskey": { "v": ["Whisky"] } } }, { "l": "sl", "t": {} }, { "l": "sr", "t": { "Acapulco": { "v": ["Акапулко"] }, "Black": { "v": ["Црно"] }, "Blue Violet": { "v": ["Плаво љубичаста"] }, "Boston Blue": { "v": ["Бостон плава"] }, "Deluge": { "v": ["Поплава"] }, "Feldspar": { "v": ["Фелдспар"] }, "Gold": { "v": ["Злато"] }, "Mariner": { "v": ["Морнар"] }, "Nextcloud blue": { "v": ["Nextcloud плава"] }, "Olivine": { "v": ["Маслинаста"] }, "Purple": { "v": ["Пурпурна"] }, "Rosy brown": { "v": ["Роси браон"] }, "Whiskey": { "v": ["Виски"] }, "White": { "v": ["Бело"] } } }, { "l": "sv", "t": { "Acapulco": { "v": ["Acapulco"] }, "Blue Violet": { "v": ["Blåviolett"] }, "Boston Blue": { "v": ["Bostonblå"] }, "Deluge": { "v": ["Skyfallsblå"] }, "Feldspar": { "v": ["Feldspat"] }, "Gold": { "v": ["Guld"] }, "Mariner": { "v": ["Marinblå"] }, "Nextcloud blue": { "v": ["Nextcloud-blå"] }, "Olivine": { "v": ["Olivin"] }, "Purple": { "v": ["Lila"] }, "Rosy brown": { "v": ["Rosabrun"] }, "Whiskey": { "v": ["Whisky"] } } }, { "l": "tr", "t": { "Acapulco": { "v": ["Akapulko"] }, "Black": { "v": ["Siyah"] }, "Blue Violet": { "v": ["Mavi mor"] }, "Boston Blue": { "v": ["Boston mavisi"] }, "Deluge": { "v": ["Sel"] }, "Feldspar": { "v": ["Feldispat"] }, "Gold": { "v": ["Altın"] }, "Mariner": { "v": ["Denizci"] }, "Nextcloud blue": { "v": ["Nextcloud mavi"] }, "Olivine": { "v": ["Zeytinlik"] }, "Purple": { "v": ["Mor"] }, "Rosy brown": { "v": ["Kırmızımsı kahverengi"] }, "Whiskey": { "v": ["Viski"] }, "White": { "v": ["Beyaz"] } } }, { "l": "uk", "t": { "Acapulco": { "v": ["Акапулько"] }, "Blue Violet": { "v": ["Блакитна фіалка"] }, "Boston Blue": { "v": ["Бостонський синій"] }, "Deluge": { "v": ["Злива"] }, "Feldspar": { "v": ["Польові шпати"] }, "Gold": { "v": ["Золотий"] }, "Mariner": { "v": ["Морський"] }, "Nextcloud blue": { "v": ["Блакитний Nextcloud"] }, "Olivine": { "v": ["Олива"] }, "Purple": { "v": ["Фіолетовий"] }, "Rosy brown": { "v": ["Темно-рожевий"] }, "Whiskey": { "v": ["Кола"] } } }, { "l": "uz", "t": { "Acapulco": { "v": ["Akapulko"] }, "Black": { "v": ["Qora"] }, "Blue Violet": { "v": ["Moviy binafsha"] }, "Boston Blue": { "v": ["Boston ko'k"] }, "Deluge": { "v": ["To'fon"] }, "Feldspar": { "v": ["Feldspar"] }, "Gold": { "v": ["Oltin"] }, "Mariner": { "v": ["Dengizchi"] }, "Nextcloud blue": { "v": ["Ko'k Nextcloud "] }, "Olivine": { "v": ["Olivine"] }, "Purple": { "v": ["Binafsha"] }, "Rosy brown": { "v": ["Qizil jigarrang"] }, "Whiskey": { "v": ["Whiskey"] }, "White": { "v": ["Oq"] } } }, { "l": "zh-CN", "t": { "Acapulco": { "v": ["Acapulco"] }, "Blue Violet": { "v": ["瓦罗兰特蓝"] }, "Boston Blue": { "v": ["波士顿蓝"] }, "Deluge": { "v": ["洪水色"] }, "Feldspar": { "v": ["长石"] }, "Gold": { "v": ["金色"] }, "Mariner": { "v": ["水手"] }, "Nextcloud blue": { "v": ["Nextcloud 蓝"] }, "Olivine": { "v": ["橄榄石色"] }, "Purple": { "v": ["紫色"] }, "Rosy brown": { "v": ["玫瑰棕色"] }, "Whiskey": { "v": ["威士忌"] } } }, { "l": "zh-HK", "t": { "Acapulco": { "v": ["阿卡普爾科"] }, "Black": { "v": ["黑色"] }, "Blue Violet": { "v": ["藍紫色"] }, "Boston Blue": { "v": ["波士頓藍"] }, "Deluge": { "v": ["大洪水"] }, "Feldspar": { "v": ["長石"] }, "Gold": { "v": ["Gold"] }, "Mariner": { "v": ["海軍藍"] }, "Nextcloud blue": { "v": ["Nextcloud 藍色"] }, "Olivine": { "v": ["橄欖石色"] }, "Purple": { "v": ["紫色"] }, "Rosy brown": { "v": ["玫瑰棕色"] }, "Whiskey": { "v": ["威士忌"] }, "White": { "v": ["白色"] } } }, { "l": "zh-TW", "t": { "Acapulco": { "v": ["Acapulco"] }, "Blue Violet": { "v": ["藍紫色"] }, "Boston Blue": { "v": ["波士頓藍"] }, "Deluge": { "v": ["Deluge"] }, "Feldspar": { "v": ["長石"] }, "Gold": { "v": ["金色"] }, "Mariner": { "v": ["海軍藍"] }, "Nextcloud blue": { "v": ["Nextcloud 藍色"] }, "Olivine": { "v": ["橄欖石色"] }, "Purple": { "v": ["紫色"] }, "Rosy brown": { "v": ["玫瑰棕色"] }, "Whiskey": { "v": ["威士忌"] } } }];
const t4 = [{ "l": "ar", "t": { "Actions": { "v": ["إجراءات"] } } }, { "l": "ast", "t": { "Actions": { "v": ["Aiciones"] } } }, { "l": "br", "t": { "Actions": { "v": ["Oberioù"] } } }, { "l": "ca", "t": { "Actions": { "v": ["Accions"] } } }, { "l": "cs", "t": { "Actions": { "v": ["Akce"] } } }, { "l": "cs-CZ", "t": { "Actions": { "v": ["Akce"] } } }, { "l": "da", "t": { "Actions": { "v": ["Handlinger"] } } }, { "l": "de", "t": { "Actions": { "v": ["Aktionen"] } } }, { "l": "de-DE", "t": { "Actions": { "v": ["Aktionen"] } } }, { "l": "el", "t": { "Actions": { "v": ["Ενέργειες"] } } }, { "l": "en-GB", "t": { "Actions": { "v": ["Actions"] } } }, { "l": "eo", "t": { "Actions": { "v": ["Agoj"] } } }, { "l": "es", "t": { "Actions": { "v": ["Acciones"] } } }, { "l": "es-AR", "t": { "Actions": { "v": ["Acciones"] } } }, { "l": "es-EC", "t": { "Actions": { "v": ["Acciones"] } } }, { "l": "es-MX", "t": { "Actions": { "v": ["Acciones"] } } }, { "l": "et-EE", "t": { "Actions": { "v": ["Tegevus"] } } }, { "l": "eu", "t": { "Actions": { "v": ["Ekintzak"] } } }, { "l": "fa", "t": { "Actions": { "v": ["کنش‌ها"] } } }, { "l": "fi", "t": { "Actions": { "v": ["Toiminnot"] } } }, { "l": "fr", "t": { "Actions": { "v": ["Actions"] } } }, { "l": "ga", "t": { "Actions": { "v": ["Gníomhartha"] } } }, { "l": "gl", "t": { "Actions": { "v": ["Accións"] } } }, { "l": "he", "t": { "Actions": { "v": ["פעולות"] } } }, { "l": "hu", "t": { "Actions": { "v": ["Műveletek"] } } }, { "l": "id", "t": { "Actions": { "v": ["Tindakan"] } } }, { "l": "is", "t": { "Actions": { "v": ["Aðgerðir"] } } }, { "l": "it", "t": { "Actions": { "v": ["Azioni"] } } }, { "l": "ja", "t": { "Actions": { "v": ["操作"] } } }, { "l": "ja-JP", "t": { "Actions": { "v": ["操作"] } } }, { "l": "ko", "t": { "Actions": { "v": ["동작"] } } }, { "l": "lo", "t": { "Actions": { "v": ["ການກະທຳ"] } } }, { "l": "lt-LT", "t": { "Actions": { "v": ["Veiksmai"] } } }, { "l": "lv", "t": {} }, { "l": "mk", "t": { "Actions": { "v": ["Акции"] } } }, { "l": "my", "t": { "Actions": { "v": ["လုပ်ဆောင်ချက်များ"] } } }, { "l": "nb", "t": { "Actions": { "v": ["Handlinger"] } } }, { "l": "nl", "t": { "Actions": { "v": ["Acties"] } } }, { "l": "oc", "t": { "Actions": { "v": ["Accions"] } } }, { "l": "pl", "t": { "Actions": { "v": ["Działania"] } } }, { "l": "pt-BR", "t": { "Actions": { "v": ["Ações"] } } }, { "l": "pt-PT", "t": { "Actions": { "v": ["Ações"] } } }, { "l": "ro", "t": { "Actions": { "v": ["Acțiuni"] } } }, { "l": "ru", "t": { "Actions": { "v": ["Действия "] } } }, { "l": "sk", "t": { "Actions": { "v": ["Akcie"] } } }, { "l": "sl", "t": { "Actions": { "v": ["Dejanja"] } } }, { "l": "sr", "t": { "Actions": { "v": ["Радње"] } } }, { "l": "sv", "t": { "Actions": { "v": ["Åtgärder"] } } }, { "l": "tr", "t": { "Actions": { "v": ["İşlemler"] } } }, { "l": "uk", "t": { "Actions": { "v": ["Дії"] } } }, { "l": "uz", "t": { "Actions": { "v": ["Harakatlar"] } } }, { "l": "zh-CN", "t": { "Actions": { "v": ["行为"] } } }, { "l": "zh-HK", "t": { "Actions": { "v": ["動作"] } } }, { "l": "zh-TW", "t": { "Actions": { "v": ["動作"] } } }];
const t5 = [{ "l": "ar", "t": { "Activities": { "v": ["سجل الأنشطة"] }, "Animals & Nature": { "v": ["الحيوانات والطبيعة"] }, "Custom": { "v": ["مُخصَّص"] }, "Dark skin tone": { "v": ["أسمر البُشرة"] }, "Emoji picker": { "v": ["لاقط الإيموجي"] }, "Flags": { "v": ["الأعلام"] }, "Food & Drink": { "v": ["الطعام والشراب"] }, "Frequently used": { "v": ["شائعة الاستعمال"] }, "Light skin tone": { "v": ["فاتح البُشرة"] }, "Medium dark skin tone": { "v": ["بشرة متوسطة الاسمرار"] }, "Medium light skin tone": { "v": ["بشرة متوسطة البياض"] }, "Medium skin tone": { "v": ["بشرة وسطية اللون"] }, "Neutral skin color": { "v": ["لون بُشرة طبيعي"] }, "Objects": { "v": ["أشياء"] }, "People & Body": { "v": ["أشخاص و أجسام"] }, "Pick an emoji": { "v": ["إختَر رمز إيموجي emoji"] }, "Search emoji": { "v": ["البحث عن إيموجي emoji"] }, "Search results": { "v": ["نتائج البحث"] }, "Selected": { "v": ["محدّدة"] }, "Skin tone": { "v": ["لون البُشرة"] }, "Smileys & Emotion": { "v": ["وجوهٌ ضاحكة و مشاعر"] }, "Symbols": { "v": ["رموز"] }, "Travel & Places": { "v": ["سفر و أماكن"] } } }, { "l": "ast", "t": { "Activities": { "v": ["Actividaes"] }, "Animals & Nature": { "v": ["Animales y natura"] }, "Custom": { "v": ["Personalizar"] }, "Dark skin tone": { "v": ["Tonu d'aspeutu escuru"] }, "Emoji picker": { "v": ["Selector de fustaxes"] }, "Flags": { "v": ["Banderes"] }, "Food & Drink": { "v": ["Cómida y bébora"] }, "Frequently used": { "v": ["D'usu frecuente"] }, "Light skin tone": { "v": ["Tonu d'aspeutu claru"] }, "Medium dark skin tone": { "v": ["Tonu d'aspeutu medio escuru"] }, "Medium light skin tone": { "v": ["Tonu d'aspeutu medio claru"] }, "Medium skin tone": { "v": ["Tonu d'aspeutu mediu"] }, "Neutral skin color": { "v": ["Color d'aspeutu neutral"] }, "Objects": { "v": ["Oxetos"] }, "People & Body": { "v": ["Persones y cuerpu"] }, "Pick an emoji": { "v": ["Escueyi un fustaxe"] }, "Search emoji": { "v": ["Buscar nos fustaxes"] }, "Search results": { "v": ["Resultaos de la busca"] }, "Selected": { "v": ["Na seleición"] }, "Skin tone": { "v": ["Tonu d'aspeutu"] }, "Smileys & Emotion": { "v": ["Sorrises y emociones"] }, "Symbols": { "v": ["Símbolos"] }, "Travel & Places": { "v": ["Viaxes y llugares"] } } }, { "l": "br", "t": { "Activities": { "v": ["Oberiantizoù"] }, "Animals & Nature": { "v": ["Loened & Natur"] }, "Custom": { "v": ["Personelañ"] }, "Flags": { "v": ["Bannieloù"] }, "Food & Drink": { "v": ["Boued & Evajoù"] }, "Frequently used": { "v": ["Implijet alies"] }, "Objects": { "v": ["Traoù"] }, "People & Body": { "v": ["Tud & Korf"] }, "Pick an emoji": { "v": ["Choaz un emoji"] }, "Search results": { "v": ["Disoc'hoù an enklask"] }, "Smileys & Emotion": { "v": ["Smileyioù & Fromoù"] }, "Symbols": { "v": ["Arouezioù"] }, "Travel & Places": { "v": ["Beaj & Lec'hioù"] } } }, { "l": "ca", "t": { "Activities": { "v": ["Activitats"] }, "Animals & Nature": { "v": ["Animals i natura"] }, "Custom": { "v": ["Personalitzat"] }, "Flags": { "v": ["Marques"] }, "Food & Drink": { "v": ["Menjar i begudes"] }, "Frequently used": { "v": ["Utilitzats recentment"] }, "Objects": { "v": ["Objectes"] }, "People & Body": { "v": ["Persones i cos"] }, "Pick an emoji": { "v": ["Trieu un emoji"] }, "Search results": { "v": ["Resultats de cerca"] }, "Smileys & Emotion": { "v": ["Cares i emocions"] }, "Symbols": { "v": ["Símbols"] }, "Travel & Places": { "v": ["Viatges i llocs"] } } }, { "l": "cs", "t": { "Activities": { "v": ["Aktivity"] }, "Animals & Nature": { "v": ["Zvířata a příroda"] }, "Custom": { "v": ["Uživatelsky určené"] }, "Dark skin tone": { "v": ["Tmavý tělový tón"] }, "Emoji picker": { "v": ["Výběr emotikon"] }, "Flags": { "v": ["Příznaky"] }, "Food & Drink": { "v": ["Jídlo a pití"] }, "Frequently used": { "v": ["Často používané"] }, "Light skin tone": { "v": ["Světlý tělový tón"] }, "Medium dark skin tone": { "v": ["Středně tmavý tělový tón"] }, "Medium light skin tone": { "v": ["Středně světlý tělový tón"] }, "Medium skin tone": { "v": ["Střední tělový tón"] }, "Neutral skin color": { "v": ["Neutrální tělová barva"] }, "Objects": { "v": ["Objekty"] }, "People & Body": { "v": ["Lidé a tělo"] }, "Pick an emoji": { "v": ["Vybrat emoji"] }, "Search emoji": { "v": ["Hledat emoji"] }, "Search results": { "v": ["Výsledky hledání"] }, "Selected": { "v": ["Vybráno"] }, "Skin tone": { "v": ["Tělový tón"] }, "Smileys & Emotion": { "v": ["Úsměvy a emoce"] }, "Symbols": { "v": ["Symboly"] }, "Travel & Places": { "v": ["Cestování a místa"] } } }, { "l": "cs-CZ", "t": { "Activities": { "v": ["Aktivity"] }, "Animals & Nature": { "v": ["Zvířata a příroda"] }, "Custom": { "v": ["Uživatelsky určené"] }, "Dark skin tone": { "v": ["Tmavý tělový tón"] }, "Emoji picker": { "v": ["Výběr emotikon"] }, "Flags": { "v": ["Příznaky"] }, "Food & Drink": { "v": ["Jídlo a pití"] }, "Frequently used": { "v": ["Často používané"] }, "Light skin tone": { "v": ["Světlý tělový tón"] }, "Medium dark skin tone": { "v": ["Středně tmavý tělový tón"] }, "Medium light skin tone": { "v": ["Středně světlý tělový tón"] }, "Medium skin tone": { "v": ["Střední tělový tón"] }, "Neutral skin color": { "v": ["Neutřální tělová barva"] }, "Objects": { "v": ["Objekty"] }, "People & Body": { "v": ["Lidé a tělo"] }, "Pick an emoji": { "v": ["Vybrat emoji"] }, "Search emoji": { "v": ["Hledat emoji"] }, "Search results": { "v": ["Výsledky hledání"] }, "Selected": { "v": ["Vybráno"] }, "Skin tone": { "v": ["Tělový tón"] }, "Smileys & Emotion": { "v": ["Úsměvy a emoce"] }, "Symbols": { "v": ["Symboly"] }, "Travel & Places": { "v": ["Cestování a místa"] } } }, { "l": "da", "t": { "Activities": { "v": ["Aktiviteter"] }, "Animals & Nature": { "v": ["Dyr & Natur"] }, "Custom": { "v": ["Brugerdefineret"] }, "Dark skin tone": { "v": ["Mørk skin tone"] }, "Emoji picker": { "v": ["Emoji vælger"] }, "Flags": { "v": ["Flag"] }, "Food & Drink": { "v": ["Mad & Drikke"] }, "Frequently used": { "v": ["Ofte brugt"] }, "Light skin tone": { "v": ["Lys skin tone"] }, "Medium dark skin tone": { "v": ["Medium mørk skin tone"] }, "Medium light skin tone": { "v": ["Medium lys skin tone"] }, "Medium skin tone": { "v": ["Medium skin tone"] }, "Neutral skin color": { "v": ["Neutral skin color"] }, "Objects": { "v": ["Objekter"] }, "People & Body": { "v": ["Mennesker & Menneskekroppen"] }, "Pick an emoji": { "v": ["Vælg en emoji"] }, "Search emoji": { "v": ["Søg emoji"] }, "Search results": { "v": ["Søgeresultater"] }, "Selected": { "v": ["Valgt"] }, "Skin tone": { "v": ["Skin tone"] }, "Smileys & Emotion": { "v": ["Smileys & Emotion"] }, "Symbols": { "v": ["Symboler"] }, "Travel & Places": { "v": ["Rejser & Rejsemål"] } } }, { "l": "de", "t": { "Activities": { "v": ["Aktivitäten"] }, "Animals & Nature": { "v": ["Tiere & Natur"] }, "Custom": { "v": ["Benutzerdefiniert"] }, "Dark skin tone": { "v": ["Dunkler Hautfarbton"] }, "Emoji picker": { "v": ["Emoji-Auswahl"] }, "Flags": { "v": ["Flaggen"] }, "Food & Drink": { "v": ["Essen & Trinken"] }, "Frequently used": { "v": ["Häufig verwendet"] }, "Light skin tone": { "v": ["Heller Hautfarbton"] }, "Medium dark skin tone": { "v": ["Mitteldunkler Hautfarbton"] }, "Medium light skin tone": { "v": ["Mittelheller Hautfarbton"] }, "Medium skin tone": { "v": ["Mittlerer Hautfarbton"] }, "Neutral skin color": { "v": ["Neutraler Hautfarbton"] }, "Objects": { "v": ["Objekte"] }, "People & Body": { "v": ["Menschen & Körper"] }, "Pick an emoji": { "v": ["Ein Emoji auswählen"] }, "Search emoji": { "v": ["Emoji suchen"] }, "Search results": { "v": ["Suchergebnisse"] }, "Selected": { "v": ["Ausgewählt"] }, "Skin tone": { "v": ["Hautfarbton"] }, "Smileys & Emotion": { "v": ["Smileys & Emotionen"] }, "Symbols": { "v": ["Symbole"] }, "Travel & Places": { "v": ["Reisen & Orte"] } } }, { "l": "de-DE", "t": { "Activities": { "v": ["Aktivitäten"] }, "Animals & Nature": { "v": ["Tiere & Natur"] }, "Custom": { "v": ["Benutzerdefiniert"] }, "Dark skin tone": { "v": ["Dunkler Hautfarbton"] }, "Emoji picker": { "v": ["Emoji-Auswahl"] }, "Flags": { "v": ["Flaggen"] }, "Food & Drink": { "v": ["Essen & Trinken"] }, "Frequently used": { "v": ["Häufig verwendet"] }, "Light skin tone": { "v": ["Heller Hautfarbton"] }, "Medium dark skin tone": { "v": ["Mitteldunkler Hautfarbton"] }, "Medium light skin tone": { "v": ["Mittelheller Hautfarbton"] }, "Medium skin tone": { "v": ["Mittlerer Hautfarbton"] }, "Neutral skin color": { "v": ["Neutraler Hautfarbton"] }, "Objects": { "v": ["Objekte"] }, "People & Body": { "v": ["Menschen & Körper"] }, "Pick an emoji": { "v": ["Ein Emoji auswählen"] }, "Search emoji": { "v": ["Emoji suchen"] }, "Search results": { "v": ["Suchergebnisse"] }, "Selected": { "v": ["Ausgewählt"] }, "Skin tone": { "v": ["Hautfarbton"] }, "Smileys & Emotion": { "v": ["Smileys & Emotionen"] }, "Symbols": { "v": ["Symbole"] }, "Travel & Places": { "v": ["Reisen & Orte"] } } }, { "l": "el", "t": { "Activities": { "v": ["Δραστηριότητες"] }, "Animals & Nature": { "v": ["Ζώα & Φύση"] }, "Custom": { "v": ["Προσαρμογή"] }, "Dark skin tone": { "v": ["Σκούρο θέμα"] }, "Emoji picker": { "v": ["Επιλογέας emoji"] }, "Flags": { "v": ["Σημαίες"] }, "Food & Drink": { "v": ["Φαγητό & Ποτό"] }, "Frequently used": { "v": ["Συχνά χρησιμοποιούμενο"] }, "Light skin tone": { "v": ["Ανοιχτό θέμα"] }, "Medium dark skin tone": { "v": ["Μέτριο σκούρο θέμα"] }, "Medium light skin tone": { "v": ["Μέτριο ανοιχτό θέμα"] }, "Medium skin tone": { "v": ["Μέτριος τόνος θέματος"] }, "Neutral skin color": { "v": ["Ουδέτερο χρώμα θέματος"] }, "Objects": { "v": ["Αντικείμενα"] }, "People & Body": { "v": ["Άνθρωποι & Σώμα"] }, "Pick an emoji": { "v": ["Επιλέξτε ένα emoji"] }, "Search emoji": { "v": ["Αναζήτηση emoji"] }, "Search results": { "v": ["Αποτελέσματα αναζήτησης"] }, "Selected": { "v": ["Επιλεγμένο"] }, "Skin tone": { "v": ["Τόνος δέρματος"] }, "Smileys & Emotion": { "v": ["Φατσούλες & Συναίσθημα"] }, "Symbols": { "v": ["Σύμβολα"] }, "Travel & Places": { "v": ["Ταξίδια & Τοποθεσίες"] } } }, { "l": "en-GB", "t": { "Activities": { "v": ["Activities"] }, "Animals & Nature": { "v": ["Animals & Nature"] }, "Custom": { "v": ["Custom"] }, "Dark skin tone": { "v": ["Dark skin tone"] }, "Emoji picker": { "v": ["Emoji picker"] }, "Flags": { "v": ["Flags"] }, "Food & Drink": { "v": ["Food & Drink"] }, "Frequently used": { "v": ["Frequently used"] }, "Light skin tone": { "v": ["Light skin tone"] }, "Medium dark skin tone": { "v": ["Medium dark skin tone"] }, "Medium light skin tone": { "v": ["Medium light skin tone"] }, "Medium skin tone": { "v": ["Medium skin tone"] }, "Neutral skin color": { "v": ["Neutral skin colour"] }, "Objects": { "v": ["Objects"] }, "People & Body": { "v": ["People & Body"] }, "Pick an emoji": { "v": ["Pick an emoji"] }, "Search emoji": { "v": ["Search emoji"] }, "Search results": { "v": ["Search results"] }, "Selected": { "v": ["Selected"] }, "Skin tone": { "v": ["Skin tone"] }, "Smileys & Emotion": { "v": ["Smileys & Emotion"] }, "Symbols": { "v": ["Symbols"] }, "Travel & Places": { "v": ["Travel & Places"] } } }, { "l": "eo", "t": { "Activities": { "v": ["Aktiveco"] }, "Animals & Nature": { "v": ["Bestoj & Naturo"] }, "Custom": { "v": ["Propra"] }, "Flags": { "v": ["Flagoj"] }, "Food & Drink": { "v": ["Manĝaĵo & Trinkaĵo"] }, "Frequently used": { "v": ["Ofte uzataj"] }, "Objects": { "v": ["Objektoj"] }, "People & Body": { "v": ["Homoj & Korpo"] }, "Pick an emoji": { "v": ["Elekti emoĝion "] }, "Search results": { "v": ["Serĉrezultoj"] }, "Smileys & Emotion": { "v": ["Ridoj kaj Emocioj"] }, "Symbols": { "v": ["Signoj"] }, "Travel & Places": { "v": ["Vojaĵoj & Lokoj"] } } }, { "l": "es", "t": { "Activities": { "v": ["Actividades"] }, "Animals & Nature": { "v": ["Animales y naturaleza"] }, "Custom": { "v": ["Personalizado"] }, "Dark skin tone": { "v": ["Tono de piel obscuro"] }, "Emoji picker": { "v": ["Selector de emojis"] }, "Flags": { "v": ["Banderas"] }, "Food & Drink": { "v": ["Comida y bebida"] }, "Frequently used": { "v": ["Usado con frecuencia"] }, "Light skin tone": { "v": ["Tono de piel claro"] }, "Medium dark skin tone": { "v": ["Tono de piel medio oscuro"] }, "Medium light skin tone": { "v": ["Tono de piel medio claro"] }, "Medium skin tone": { "v": ["Tono de piel medio"] }, "Neutral skin color": { "v": ["Color de piel neutral"] }, "Objects": { "v": ["Objetos"] }, "People & Body": { "v": ["Personas y Cuerpo"] }, "Pick an emoji": { "v": ["Elegir un emoji"] }, "Search emoji": { "v": ["Buscar emoji"] }, "Search results": { "v": ["Resultados de la búsqueda"] }, "Selected": { "v": ["Seleccionado"] }, "Skin tone": { "v": ["Tono de piel"] }, "Smileys & Emotion": { "v": ["Smileys y emoticonos"] }, "Symbols": { "v": ["Símbolos"] }, "Travel & Places": { "v": ["Viajes y lugares"] } } }, { "l": "es-AR", "t": { "Activities": { "v": ["Actividades"] }, "Animals & Nature": { "v": ["Animales y Naturaleza"] }, "Custom": { "v": ["Personalizado"] }, "Dark skin tone": { "v": ["Tono de piel oscuro"] }, "Emoji picker": { "v": ["Selector de emojis"] }, "Flags": { "v": ["Marcas"] }, "Food & Drink": { "v": ["Comida y Bebida"] }, "Frequently used": { "v": ["Usados frecuentemente"] }, "Light skin tone": { "v": ["Tono de piel claro"] }, "Medium dark skin tone": { "v": ["Tono de piel medio oscuro"] }, "Medium light skin tone": { "v": ["Tono de piel medio claro"] }, "Medium skin tone": { "v": ["Tono de piel medio"] }, "Neutral skin color": { "v": ["Color de piel neutral"] }, "Objects": { "v": ["Objetos"] }, "People & Body": { "v": ["Personas y Cuerpo"] }, "Pick an emoji": { "v": ["Elija un emoji"] }, "Search emoji": { "v": ["Buscar emoji"] }, "Search results": { "v": ["Resultados de la búsqueda"] }, "Selected": { "v": ["Seleccionado"] }, "Skin tone": { "v": ["Tono de piel"] }, "Smileys & Emotion": { "v": ["Caritas y Emociones"] }, "Symbols": { "v": ["Símbolos"] }, "Travel & Places": { "v": ["Viajes y Lugares"] } } }, { "l": "es-EC", "t": { "Activities": { "v": ["Actividades"] }, "Animals & Nature": { "v": ["Animales y Naturaleza"] }, "Custom": { "v": ["Personalizado"] }, "Flags": { "v": ["Marcas"] }, "Food & Drink": { "v": ["Comida y Bebida"] }, "Frequently used": { "v": ["Frecuentemente utilizado"] }, "Objects": { "v": ["Objetos"] }, "People & Body": { "v": ["Personas y Cuerpo"] }, "Pick an emoji": { "v": ["Seleccionar un emoji"] }, "Search emoji": { "v": ["Buscar emoji"] }, "Search results": { "v": ["Resultados de búsqueda"] }, "Smileys & Emotion": { "v": ["Caritas y Emociones"] }, "Symbols": { "v": ["Símbolos"] }, "Travel & Places": { "v": ["Viajes y Lugares"] } } }, { "l": "es-MX", "t": { "Activities": { "v": ["Actividades"] }, "Animals & Nature": { "v": ["Animales y naturaleza"] }, "Custom": { "v": ["Personalizado"] }, "Dark skin tone": { "v": ["Tono de piel oscuro"] }, "Emoji picker": { "v": ["Selector de emojis"] }, "Flags": { "v": ["Banderas"] }, "Food & Drink": { "v": ["Comida y Bebida"] }, "Frequently used": { "v": ["Usado frecuentemente"] }, "Light skin tone": { "v": ["Tono de piel claro"] }, "Medium dark skin tone": { "v": ["Tono de piel medio oscuro"] }, "Medium light skin tone": { "v": ["Tono de piel medio claro"] }, "Medium skin tone": { "v": ["Tono de piel medio"] }, "Neutral skin color": { "v": ["Color de piel neutral"] }, "Objects": { "v": ["Objetos"] }, "People & Body": { "v": ["Personas y cuerpos"] }, "Pick an emoji": { "v": ["Seleccionar un emoji"] }, "Search emoji": { "v": ["Buscar emoji"] }, "Search results": { "v": ["Resultados de la búsqueda"] }, "Selected": { "v": ["Seleccionado"] }, "Skin tone": { "v": ["Tono de piel"] }, "Smileys & Emotion": { "v": ["Caritas y Emociones"] }, "Symbols": { "v": ["Símbolos"] }, "Travel & Places": { "v": ["Viajes y lugares"] } } }, { "l": "et-EE", "t": { "Activities": { "v": ["Tegevused"] }, "Animals & Nature": { "v": ["Loomad ja loodus"] }, "Custom": { "v": ["Kohanda"] }, "Dark skin tone": { "v": ["Kesta tume toon"] }, "Emoji picker": { "v": ["Emojide valija"] }, "Flags": { "v": ["Lipud"] }, "Food & Drink": { "v": ["Söök ja jook"] }, "Frequently used": { "v": ["Sageli kasutatud"] }, "Light skin tone": { "v": ["Kesta hele toon"] }, "Medium dark skin tone": { "v": ["Kesta keskmiselt tume toon"] }, "Medium light skin tone": { "v": ["Kesta keskmiselt hele toon"] }, "Medium skin tone": { "v": ["Kesta keskmine toon"] }, "Neutral skin color": { "v": ["Kesta neutraalne toon"] }, "Objects": { "v": ["Objektid"] }, "People & Body": { "v": ["Inimesed ja keha"] }, "Pick an emoji": { "v": ["Vali emoji"] }, "Search emoji": { "v": ["Otsi emojit"] }, "Search results": { "v": ["Otsi tulemustest"] }, "Selected": { "v": ["Valitud"] }, "Skin tone": { "v": ["Kesta toon"] }, "Smileys & Emotion": { "v": ["Smailid ja emotsioonid"] }, "Symbols": { "v": ["Sümbolid"] }, "Travel & Places": { "v": ["Reisimine ja kohad"] } } }, { "l": "eu", "t": { "Activities": { "v": ["Jarduerak"] }, "Animals & Nature": { "v": ["Animaliak eta Natura"] }, "Custom": { "v": ["Pertsonalizatua"] }, "Flags": { "v": ["Banderak"] }, "Food & Drink": { "v": ["Janaria eta edariak"] }, "Frequently used": { "v": ["Askotan erabilia"] }, "Objects": { "v": ["Objektuak"] }, "People & Body": { "v": ["Jendea eta gorputza"] }, "Pick an emoji": { "v": ["Hautatu emoji bat"] }, "Search emoji": { "v": ["Bilatu emojiak"] }, "Search results": { "v": ["Bilaketa emaitzak"] }, "Selected": { "v": ["Hautatuta"] }, "Smileys & Emotion": { "v": ["Smileyak eta emozioa"] }, "Symbols": { "v": ["Sinboloak"] }, "Travel & Places": { "v": ["Bidaiak eta lekuak"] } } }, { "l": "fa", "t": { "Activities": { "v": ["فعالیت‌ها"] }, "Animals & Nature": { "v": ["حیوانات و طبیعت"] }, "Custom": { "v": ["سفارشی"] }, "Dark skin tone": { "v": ["رنگ پوسته تیره"] }, "Emoji picker": { "v": ["انتخاب‌گر شکلک"] }, "Flags": { "v": ["پرچم‌ها"] }, "Food & Drink": { "v": ["غذا و نوشیدنی"] }, "Frequently used": { "v": ["پرکاربرد"] }, "Light skin tone": { "v": ["رنگ پوسته روشن"] }, "Medium dark skin tone": { "v": ["رنگ پوسته تیره متوسط"] }, "Medium light skin tone": { "v": ["رنگ پوسته روشن متوسط"] }, "Medium skin tone": { "v": ["رنگ پوسته متوسط"] }, "Neutral skin color": { "v": ["رنگ پوسته خنثی"] }, "Objects": { "v": ["اشیاء"] }, "People & Body": { "v": ["مردم و بدن"] }, "Pick an emoji": { "v": ["انتخاب شکلک"] }, "Search emoji": { "v": ["جستجوی شکلک"] }, "Search results": { "v": ["نتایج جستجو"] }, "Selected": { "v": ["انتخاب شده"] }, "Skin tone": { "v": ["رنگ پوسته"] }, "Smileys & Emotion": { "v": ["شکلک‌ها و احساسات"] }, "Symbols": { "v": ["نمادها"] }, "Travel & Places": { "v": ["سفر و مکان‌ها"] } } }, { "l": "fi", "t": { "Activities": { "v": ["Aktiviteetit"] }, "Animals & Nature": { "v": ["Eläimet & luonto"] }, "Custom": { "v": ["Mukautettu"] }, "Dark skin tone": { "v": ["Tumma ihonväri"] }, "Emoji picker": { "v": ["Emojivalitsin"] }, "Flags": { "v": ["Liput"] }, "Food & Drink": { "v": ["Ruoka & juoma"] }, "Frequently used": { "v": ["Usein käytetyt"] }, "Light skin tone": { "v": ["Vaalea ihonväri"] }, "Medium dark skin tone": { "v": ["Keskitumma ihonväri"] }, "Medium light skin tone": { "v": ["Keskivaalea ihonväri"] }, "Medium skin tone": { "v": ["Keskimääräinen ihonväri"] }, "Neutral skin color": { "v": ["Neutraali ihonväri"] }, "Objects": { "v": ["Esineet & asiat"] }, "People & Body": { "v": ["Ihmiset & keho"] }, "Pick an emoji": { "v": ["Valitse emoji"] }, "Search emoji": { "v": ["Etsi emojia"] }, "Search results": { "v": ["Hakutulokset"] }, "Selected": { "v": ["Valittu"] }, "Skin tone": { "v": ["Ihonväri"] }, "Smileys & Emotion": { "v": ["Hymiöt & tunteet"] }, "Symbols": { "v": ["Symbolit"] }, "Travel & Places": { "v": ["Matkustus & kohteet"] } } }, { "l": "fr", "t": { "Activities": { "v": ["Activités"] }, "Animals & Nature": { "v": ["Animaux & Nature"] }, "Custom": { "v": ["Personnalisé"] }, "Dark skin tone": { "v": ["Teint de peau foncé"] }, "Emoji picker": { "v": ["Sélecteur d'émojis"] }, "Flags": { "v": ["Drapeaux"] }, "Food & Drink": { "v": ["Nourriture & Boissons"] }, "Frequently used": { "v": ["Utilisés fréquemment"] }, "Light skin tone": { "v": ["Teint de peau clair"] }, "Medium dark skin tone": { "v": ["Teint de peau moyennement foncé"] }, "Medium light skin tone": { "v": ["Teint de peau moyennement clair"] }, "Medium skin tone": { "v": ["Teint de peau moyen"] }, "Neutral skin color": { "v": ["Teint de peau neutre"] }, "Objects": { "v": ["Objets"] }, "People & Body": { "v": ["Personnes & Corps"] }, "Pick an emoji": { "v": ["Choisissez un émoji"] }, "Search emoji": { "v": ["Rechercher un emoji"] }, "Search results": { "v": ["Résultats de recherche"] }, "Selected": { "v": ["sélectionné"] }, "Skin tone": { "v": ["Teint de peau"] }, "Smileys & Emotion": { "v": ["Smileys & Émotions"] }, "Symbols": { "v": ["Symboles"] }, "Travel & Places": { "v": ["Voyage & Lieux"] } } }, { "l": "ga", "t": { "Activities": { "v": ["Gníomhaíochtaí"] }, "Animals & Nature": { "v": ["Ainmhithe & Dúlra"] }, "Custom": { "v": ["Saincheaptha"] }, "Dark skin tone": { "v": ["Ton craiceann dorcha"] }, "Emoji picker": { "v": ["Roghnóir Emoji"] }, "Flags": { "v": ["Bratacha"] }, "Food & Drink": { "v": ["Bia & Deoch"] }, "Frequently used": { "v": ["Úsáidtear go minic"] }, "Light skin tone": { "v": ["Ton craiceann éadrom"] }, "Medium dark skin tone": { "v": ["Ton craiceann meánach dorcha"] }, "Medium light skin tone": { "v": ["Ton craiceann meánach éadrom"] }, "Medium skin tone": { "v": ["Ton craiceann meánach"] }, "Neutral skin color": { "v": ["Dath craiceann neodrach"] }, "Objects": { "v": ["Réada"] }, "People & Body": { "v": ["Daoine & Corp"] }, "Pick an emoji": { "v": ["Roghnaigh emoji"] }, "Search emoji": { "v": ["Cuardaigh emoji"] }, "Search results": { "v": ["Torthaí cuardaigh"] }, "Selected": { "v": ["Roghnaithe"] }, "Skin tone": { "v": ["Ton craicinn"] }, "Smileys & Emotion": { "v": ["Smileys & Mothúchán"] }, "Symbols": { "v": ["Siombailí"] }, "Travel & Places": { "v": ["Taisteal & Áiteanna"] } } }, { "l": "gl", "t": { "Activities": { "v": ["Actividades"] }, "Animals & Nature": { "v": ["Animais e natureza"] }, "Custom": { "v": ["Personalizado"] }, "Dark skin tone": { "v": ["Ton de pel escuro"] }, "Emoji picker": { "v": ["Selector de «emojis»"] }, "Flags": { "v": ["Bandeiras"] }, "Food & Drink": { "v": ["Comida e bebida"] }, "Frequently used": { "v": ["Usado con frecuencia"] }, "Light skin tone": { "v": ["Ton de pel claro"] }, "Medium dark skin tone": { "v": ["Ton de pel medio escuro"] }, "Medium light skin tone": { "v": ["Ton de pel medio claro"] }, "Medium skin tone": { "v": ["Ton de pel medio"] }, "Neutral skin color": { "v": ["Cor de pel neutra"] }, "Objects": { "v": ["Obxectos"] }, "People & Body": { "v": ["Persoas e corpo"] }, "Pick an emoji": { "v": ["Escolla un «emoji»"] }, "Search emoji": { "v": ["Buscar «emoji»"] }, "Search results": { "v": ["Resultados da busca"] }, "Selected": { "v": ["Seleccionado"] }, "Skin tone": { "v": ["Ton de pel"] }, "Smileys & Emotion": { "v": ["Sorrisos e emocións"] }, "Symbols": { "v": ["Símbolos"] }, "Travel & Places": { "v": ["Viaxes e lugares"] } } }, { "l": "he", "t": { "Activities": { "v": ["פעילויות"] }, "Animals & Nature": { "v": ["חיות וטבע"] }, "Custom": { "v": ["בהתאמה אישית"] }, "Flags": { "v": ["דגלים"] }, "Food & Drink": { "v": ["מזון ומשקאות"] }, "Frequently used": { "v": ["בשימוש תדיר"] }, "Objects": { "v": ["חפצים"] }, "People & Body": { "v": ["אנשים וגוף"] }, "Pick an emoji": { "v": ["נא לבחור אמוג׳י"] }, "Search emoji": { "v": ["חיפוש אמוג׳י"] }, "Search results": { "v": ["תוצאות חיפוש"] }, "Smileys & Emotion": { "v": ["חייכנים ורגשונים"] }, "Symbols": { "v": ["סמלים"] }, "Travel & Places": { "v": ["טיולים ומקומות"] } } }, { "l": "hu", "t": { "Activities": { "v": ["Tevékenységek"] }, "Animals & Nature": { "v": ["Állatok és természet"] }, "Custom": { "v": ["Egyéni"] }, "Flags": { "v": ["Zászlók"] }, "Food & Drink": { "v": ["Étel és ital"] }, "Frequently used": { "v": ["Gyakran használt"] }, "Objects": { "v": ["Tárgyak"] }, "People & Body": { "v": ["Emberek és test"] }, "Pick an emoji": { "v": ["Válasszon egy emodzsit"] }, "Search results": { "v": ["Találatok"] }, "Smileys & Emotion": { "v": ["Mosolyok és érzelmek"] }, "Symbols": { "v": ["Szimbólumok"] }, "Travel & Places": { "v": ["Utazás és helyek"] } } }, { "l": "id", "t": { "Activities": { "v": ["Aktivitas"] }, "Animals & Nature": { "v": ["Satwa dan Alam"] }, "Custom": { "v": ["Khusus"] }, "Flags": { "v": ["Tanda"] }, "Food & Drink": { "v": ["Makanan dan Minuman"] }, "Frequently used": { "v": ["Sering digunakan"] }, "Objects": { "v": ["Objek"] }, "People & Body": { "v": ["Orang & Badan"] }, "Pick an emoji": { "v": ["Pilih emoji"] }, "Search emoji": { "v": ["Cari emoji"] }, "Search results": { "v": ["Hasil pencarian"] }, "Selected": { "v": ["Dipilih"] }, "Smileys & Emotion": { "v": ["Senyuman & Perasaan"] }, "Symbols": { "v": ["Simbol"] }, "Travel & Places": { "v": ["Perjalanan & Tempat"] } } }, { "l": "is", "t": { "Activities": { "v": ["Aðgerðir"] }, "Animals & Nature": { "v": ["Dýr og náttúra"] }, "Custom": { "v": ["Sérsniðið"] }, "Dark skin tone": { "v": ["Dökkur húðlitur"] }, "Emoji picker": { "v": ["Emoji-táknmyndaval"] }, "Flags": { "v": ["Flögg"] }, "Food & Drink": { "v": ["Matur og drykkur"] }, "Frequently used": { "v": ["Oftast notað"] }, "Light skin tone": { "v": ["Ljós húðlitur"] }, "Medium dark skin tone": { "v": ["Meðaldökkur húðlitur"] }, "Medium light skin tone": { "v": ["Meðalljós húðlitur"] }, "Medium skin tone": { "v": ["Meðaltónn húðar"] }, "Neutral skin color": { "v": ["Hlutlaus húðlitur"] }, "Objects": { "v": ["Hlutir"] }, "People & Body": { "v": ["Fólk og líkami"] }, "Pick an emoji": { "v": ["Veldu tjáningartákn"] }, "Search emoji": { "v": ["Leita að tjáningartákni"] }, "Search results": { "v": ["Leitarniðurstöður"] }, "Selected": { "v": ["Valið"] }, "Skin tone": { "v": ["Húðlitur"] }, "Smileys & Emotion": { "v": ["Broskallar og tilfinningar"] }, "Symbols": { "v": ["Tákn"] }, "Travel & Places": { "v": ["Staðir og ferðalög"] } } }, { "l": "it", "t": { "Activities": { "v": ["Attività"] }, "Animals & Nature": { "v": ["Animali e natura"] }, "Custom": { "v": ["Personalizzato"] }, "Flags": { "v": ["Bandiere"] }, "Food & Drink": { "v": ["Cibo e bevande"] }, "Frequently used": { "v": ["Usati di frequente"] }, "Objects": { "v": ["Oggetti"] }, "People & Body": { "v": ["Persone e corpo"] }, "Pick an emoji": { "v": ["Scegli un emoji"] }, "Search emoji": { "v": ["Ricerca emoji"] }, "Search results": { "v": ["Risultati di ricerca"] }, "Selected": { "v": ["Selezionato"] }, "Smileys & Emotion": { "v": ["Faccine ed emozioni"] }, "Symbols": { "v": ["Simboli"] }, "Travel & Places": { "v": ["Viaggi e luoghi"] } } }, { "l": "ja", "t": { "Activities": { "v": ["アクティビティ"] }, "Animals & Nature": { "v": ["動物と自然"] }, "Custom": { "v": ["カスタム"] }, "Dark skin tone": { "v": ["暗い肌のトーン"] }, "Emoji picker": { "v": ["絵文字ピッカー"] }, "Flags": { "v": ["国旗"] }, "Food & Drink": { "v": ["食べ物と飲み物"] }, "Frequently used": { "v": ["よく使うもの"] }, "Light skin tone": { "v": ["明るい肌のトーン"] }, "Medium dark skin tone": { "v": ["やや暗い肌のトーン"] }, "Medium light skin tone": { "v": ["やや明るい肌のトーン"] }, "Medium skin tone": { "v": ["中間の肌のトーン"] }, "Neutral skin color": { "v": ["ニュートラルな肌の色"] }, "Objects": { "v": ["物"] }, "People & Body": { "v": ["様々な人と体の部位"] }, "Pick an emoji": { "v": ["絵文字を選択"] }, "Search emoji": { "v": ["絵文字を検索"] }, "Search results": { "v": ["検索結果"] }, "Selected": { "v": ["選択済み"] }, "Skin tone": { "v": ["肌のトーン"] }, "Smileys & Emotion": { "v": ["感情表現"] }, "Symbols": { "v": ["記号"] }, "Travel & Places": { "v": ["旅行と場所"] } } }, { "l": "ja-JP", "t": { "Activities": { "v": ["アクティビティ"] }, "Animals & Nature": { "v": ["動物と自然"] }, "Custom": { "v": ["カスタム"] }, "Dark skin tone": { "v": ["暗い肌のトーン"] }, "Emoji picker": { "v": ["絵文字ピッカー"] }, "Flags": { "v": ["国旗"] }, "Food & Drink": { "v": ["食べ物と飲み物"] }, "Frequently used": { "v": ["よく使うもの"] }, "Light skin tone": { "v": ["明るい肌のトーン"] }, "Medium dark skin tone": { "v": ["やや暗い肌のトーン"] }, "Medium light skin tone": { "v": ["やや明るい肌のトーン"] }, "Medium skin tone": { "v": ["中間の肌のトーン"] }, "Neutral skin color": { "v": ["ニュートラルな肌の色"] }, "Objects": { "v": ["物"] }, "People & Body": { "v": ["様々な人と体の部位"] }, "Pick an emoji": { "v": ["絵文字を選択"] }, "Search emoji": { "v": ["絵文字を検索"] }, "Search results": { "v": ["検索結果"] }, "Selected": { "v": ["選択済み"] }, "Skin tone": { "v": ["肌のトーン"] }, "Smileys & Emotion": { "v": ["感情表現"] }, "Symbols": { "v": ["記号"] }, "Travel & Places": { "v": ["旅行と場所"] } } }, { "l": "ko", "t": { "Activities": { "v": ["활동"] }, "Animals & Nature": { "v": ["동물 & 자연"] }, "Custom": { "v": ["맞춤 설정"] }, "Dark skin tone": { "v": ["어두운 피부 톤"] }, "Emoji picker": { "v": ["이모지 선택기"] }, "Flags": { "v": ["깃발"] }, "Food & Drink": { "v": ["음식 & 음료"] }, "Frequently used": { "v": ["자주 쓰임"] }, "Light skin tone": { "v": ["밝은 피부 톤"] }, "Medium dark skin tone": { "v": ["약간 어두운 피부 톤"] }, "Medium light skin tone": { "v": ["약간 밝은 피부 톤"] }, "Medium skin tone": { "v": ["중간 피부 톤"] }, "Neutral skin color": { "v": ["중성적 피부 톤"] }, "Objects": { "v": ["물체"] }, "People & Body": { "v": ["사람 & 신체"] }, "Pick an emoji": { "v": ["이모지 선택"] }, "Search emoji": { "v": ["이모지 검색"] }, "Search results": { "v": ["검색 결과"] }, "Selected": { "v": ["선택됨"] }, "Skin tone": { "v": ["피부 톤"] }, "Smileys & Emotion": { "v": ["스마일리 & 이모티콘"] }, "Symbols": { "v": ["기호"] }, "Travel & Places": { "v": ["여행 & 장소"] } } }, { "l": "lo", "t": { "Activities": { "v": ["ກິດຈະກຳ"] }, "Animals & Nature": { "v": ["ສັດ ແລະ ທຳມະຊາດ"] }, "Custom": { "v": ["ກຳນົດເອງ"] }, "Dark skin tone": { "v": ["ໂຕນສີຜິວເຂັ້ມ"] }, "Emoji picker": { "v": ["ໂຕເລືອກອີໂມຈິ"] }, "Flags": { "v": ["ທຸງ"] }, "Food & Drink": { "v": ["ອາຫານ ແລະ ເຄື່ອງດື່ມ"] }, "Frequently used": { "v": ["ໃຊ້ງານເລື້ອຍໆ"] }, "Light skin tone": { "v": ["ໂຕນສີຜິວອ່ອນ"] }, "Medium dark skin tone": { "v": ["ໂຕນສີຜິວເຂັ້ມປານກາງ"] }, "Medium light skin tone": { "v": ["ໂຕນສີຜິວອ່ອນປານກາງ"] }, "Medium skin tone": { "v": ["ໂຕນສີຜິວປານກາງ"] }, "Neutral skin color": { "v": ["ສີຜິວເປັນກາງ"] }, "Objects": { "v": ["ວັດຖຸ"] }, "People & Body": { "v": ["ຄົນ ແລະ ຮ່າງກາຍ"] }, "Pick an emoji": { "v": ["ເລືອກອີໂມຈິ"] }, "Search emoji": { "v": ["ຄົ້ນຫາອີໂມຈິ"] }, "Search results": { "v": ["ຜົນການຄົ້ນຫາ"] }, "Selected": { "v": ["ເລືອກແລ້ວ"] }, "Skin tone": { "v": ["ໂຕນສີຜິວ"] }, "Smileys & Emotion": { "v": ["ໜ້າຍິ້ມ ແລະ ອາລົມ"] }, "Symbols": { "v": ["ສັນຍາລັກ"] }, "Travel & Places": { "v": ["ການເດີນທາງ ແລະ ສະຖານທີ່"] } } }, { "l": "lt-LT", "t": { "Activities": { "v": ["Veiklos"] }, "Animals & Nature": { "v": ["Gyvūnai ir gamta"] }, "Custom": { "v": ["Tinkinti"] }, "Flags": { "v": ["Vėliavos"] }, "Food & Drink": { "v": ["Maistas ir gėrimai"] }, "Frequently used": { "v": ["Dažniausiai naudoti"] }, "Objects": { "v": ["Objektai"] }, "People & Body": { "v": ["Žmonės ir kūnas"] }, "Pick an emoji": { "v": ["Pasirinkti jaustuką"] }, "Search results": { "v": ["Paieškos rezultatai"] }, "Smileys & Emotion": { "v": ["Šypsenos ir emocijos"] }, "Symbols": { "v": ["Simboliai"] }, "Travel & Places": { "v": ["Kelionės ir vietos"] } } }, { "l": "lv", "t": {} }, { "l": "mk", "t": { "Activities": { "v": ["Активности"] }, "Animals & Nature": { "v": ["Животни & Природа"] }, "Custom": { "v": ["Прилагодени"] }, "Dark skin tone": { "v": ["Темна боја на кожа"] }, "Emoji picker": { "v": ["Избор на emoji"] }, "Flags": { "v": ["Знамиња"] }, "Food & Drink": { "v": ["Храна & Пијалоци"] }, "Frequently used": { "v": ["Најчесто користени"] }, "Light skin tone": { "v": ["Светла кожа"] }, "Medium dark skin tone": { "v": ["Средно темна кожа"] }, "Medium light skin tone": { "v": ["Средно светла кожа"] }, "Medium skin tone": { "v": ["Средна кожа"] }, "Neutral skin color": { "v": ["Неутрална боја на кожа"] }, "Objects": { "v": ["Објекти"] }, "People & Body": { "v": ["Луѓе & Тело"] }, "Pick an emoji": { "v": ["Избери емотикон"] }, "Search emoji": { "v": ["Барај emoji"] }, "Search results": { "v": ["Резултати од барувањето"] }, "Selected": { "v": ["Избрано"] }, "Skin tone": { "v": ["Боја на кожа"] }, "Smileys & Emotion": { "v": ["Смешковци & Емотикони"] }, "Symbols": { "v": ["Симболи"] }, "Travel & Places": { "v": ["Патувања & Места"] } } }, { "l": "my", "t": { "Activities": { "v": ["ပြုလုပ်ဆောင်တာများ"] }, "Animals & Nature": { "v": ["တိရစ္ဆာန်များနှင့် သဘာဝ"] }, "Custom": { "v": ["အလိုကျချိန်ညှိမှု"] }, "Flags": { "v": ["အလံများ"] }, "Food & Drink": { "v": ["အစားအသောက်"] }, "Frequently used": { "v": ["မကြာခဏအသုံးပြုသော"] }, "Objects": { "v": ["အရာဝတ္ထုများ"] }, "People & Body": { "v": ["လူပုဂ္ဂိုလ်များနှင့် ခန္ဓာကိုယ်"] }, "Pick an emoji": { "v": ["အီမိုဂျီရွေးရန်"] }, "Search results": { "v": ["ရှာဖွေမှု ရလဒ်များ"] }, "Smileys & Emotion": { "v": ["စမိုင်လီများနှင့် အီမိုရှင်း"] }, "Symbols": { "v": ["သင်္ကေတများ"] }, "Travel & Places": { "v": ["ခရီးသွားလာခြင်းနှင့် နေရာများ"] } } }, { "l": "nb", "t": { "Activities": { "v": ["Aktiviteter"] }, "Animals & Nature": { "v": ["Dyr og natur"] }, "Custom": { "v": ["Tilpasset"] }, "Dark skin tone": { "v": ["Mørk hudtone"] }, "Emoji picker": { "v": ["Emoji-velger"] }, "Flags": { "v": ["Flagg"] }, "Food & Drink": { "v": ["Mat og drikke"] }, "Frequently used": { "v": ["Ofte brukt"] }, "Light skin tone": { "v": ["Lys hudtone"] }, "Medium dark skin tone": { "v": ["Middels mørk hudtone"] }, "Medium light skin tone": { "v": ["Middels lys hudtone"] }, "Medium skin tone": { "v": ["Middels hudtone"] }, "Neutral skin color": { "v": ["Nøytral hudfarge"] }, "Objects": { "v": ["Objekter"] }, "People & Body": { "v": ["Mennesker og kropp"] }, "Pick an emoji": { "v": ["Velg en emoji"] }, "Search emoji": { "v": ["Søk emoji"] }, "Search results": { "v": ["Søkeresultater"] }, "Selected": { "v": ["Valgt"] }, "Skin tone": { "v": ["Hudtone"] }, "Smileys & Emotion": { "v": ["Smilefjes og følelser"] }, "Symbols": { "v": ["Symboler"] }, "Travel & Places": { "v": ["Reise og steder"] } } }, { "l": "nl", "t": { "Activities": { "v": ["Activiteiten"] }, "Animals & Nature": { "v": ["Dieren & Natuur"] }, "Custom": { "v": ["Aangepast"] }, "Dark skin tone": { "v": ["Donkere huidskleur"] }, "Emoji picker": { "v": ["Emoji-kiezer"] }, "Flags": { "v": ["Vlaggen"] }, "Food & Drink": { "v": ["Eten & Drinken"] }, "Frequently used": { "v": ["Vaak gebruikt"] }, "Light skin tone": { "v": ["Lichte huidskleur"] }, "Medium dark skin tone": { "v": ["Gemiddeld donkere huidskleur"] }, "Medium light skin tone": { "v": ["Gemiddeld lichte huidskleur"] }, "Medium skin tone": { "v": ["Gemiddelde huidskleur"] }, "Neutral skin color": { "v": ["Neutrale huidskleur"] }, "Objects": { "v": ["Objecten"] }, "People & Body": { "v": ["Mensen & Lichaam"] }, "Pick an emoji": { "v": ["Kies een emoji"] }, "Search emoji": { "v": ["Emoji zoeken"] }, "Search results": { "v": ["Zoekresultaten"] }, "Selected": { "v": ["Geselecteerd"] }, "Skin tone": { "v": ["Huidskleur"] }, "Smileys & Emotion": { "v": ["Smileys & Emotie"] }, "Symbols": { "v": ["Symbolen"] }, "Travel & Places": { "v": ["Reizen & Plaatsen"] } } }, { "l": "oc", "t": {} }, { "l": "pl", "t": { "Activities": { "v": ["Aktywność"] }, "Animals & Nature": { "v": ["Zwierzęta i natura"] }, "Custom": { "v": ["Zwyczajne"] }, "Dark skin tone": { "v": ["Ciemna tonacja"] }, "Emoji picker": { "v": ["Wybierz Emoji"] }, "Flags": { "v": ["Flagi"] }, "Food & Drink": { "v": ["Jedzenie i picie"] }, "Frequently used": { "v": ["Często używane"] }, "Light skin tone": { "v": ["Jasny odcień skóry"] }, "Medium dark skin tone": { "v": ["Średnio ciemny odcień skóry"] }, "Medium light skin tone": { "v": ["Średnio jasny odcień skóry"] }, "Medium skin tone": { "v": ["Średni odcień skóry"] }, "Neutral skin color": { "v": ["Neutralny kolor skróry"] }, "Objects": { "v": ["Obiekty"] }, "People & Body": { "v": ["Ludzie i ciało"] }, "Pick an emoji": { "v": ["Wybierz emoji"] }, "Search emoji": { "v": ["Szukaj emoji"] }, "Search results": { "v": ["Wyniki wyszukiwania"] }, "Selected": { "v": ["Wybrane"] }, "Skin tone": { "v": ["Kolor skóry"] }, "Smileys & Emotion": { "v": ["Buźki i emotikony"] }, "Symbols": { "v": ["Symbole"] }, "Travel & Places": { "v": ["Podróże i miejsca"] } } }, { "l": "pt-BR", "t": { "Activities": { "v": ["Atividades"] }, "Animals & Nature": { "v": ["Animais & Natureza"] }, "Custom": { "v": ["Personalizados"] }, "Dark skin tone": { "v": ["Tom de pele escuro"] }, "Emoji picker": { "v": ["Seletor de emoji"] }, "Flags": { "v": ["Bandeiras"] }, "Food & Drink": { "v": ["Comida & Bebida"] }, "Frequently used": { "v": ["Mais usados"] }, "Light skin tone": { "v": ["Tom de pele claro"] }, "Medium dark skin tone": { "v": ["Tom de pele meio escuro"] }, "Medium light skin tone": { "v": ["Tom de pele meio claro"] }, "Medium skin tone": { "v": ["Tom de pele médio"] }, "Neutral skin color": { "v": ["Tom de pele neutro"] }, "Objects": { "v": ["Objetos"] }, "People & Body": { "v": ["Pessoas & Corpo"] }, "Pick an emoji": { "v": ["Escolha um emoji"] }, "Search emoji": { "v": ["Pesquisar emoji"] }, "Search results": { "v": ["Resultados da pesquisa"] }, "Selected": { "v": ["Selecionado"] }, "Skin tone": { "v": ["Tom de pele"] }, "Smileys & Emotion": { "v": ["Smileys & Emoções"] }, "Symbols": { "v": ["Símbolos"] }, "Travel & Places": { "v": ["Viagem & Lugares"] } } }, { "l": "pt-PT", "t": { "Activities": { "v": ["Atividades"] }, "Animals & Nature": { "v": ["Animais e Natureza"] }, "Custom": { "v": ["Personalizado"] }, "Dark skin tone": { "v": ["Tom de pele escuro"] }, "Emoji picker": { "v": ["seletor de emoji"] }, "Flags": { "v": ["Bandeiras"] }, "Food & Drink": { "v": ["Comida e Bebida"] }, "Frequently used": { "v": ["Mais utilizados"] }, "Light skin tone": { "v": ["Tom de pele claro"] }, "Medium dark skin tone": { "v": ["Tom de pele escuro médio"] }, "Medium light skin tone": { "v": ["Tom de pele claro médio"] }, "Medium skin tone": { "v": ["Tom de pele médio"] }, "Neutral skin color": { "v": ["Cor de pele neutra"] }, "Objects": { "v": ["Objetos"] }, "People & Body": { "v": ["Pessoas e Corpo"] }, "Pick an emoji": { "v": ["Escolha um emoji"] }, "Search emoji": { "v": ["Pesquisar emoji"] }, "Search results": { "v": ["Resultados da pesquisa"] }, "Selected": { "v": ["Selecionado"] }, "Skin tone": { "v": ["Tom de pele"] }, "Smileys & Emotion": { "v": ["Sorrisos e Emoções"] }, "Symbols": { "v": ["Símbolos"] }, "Travel & Places": { "v": ["Viagens e Lugares"] } } }, { "l": "ro", "t": { "Activities": { "v": ["Activități"] }, "Animals & Nature": { "v": ["Animale și natură"] }, "Custom": { "v": ["Personalizat"] }, "Flags": { "v": ["Marcaje"] }, "Food & Drink": { "v": ["Alimente și băuturi"] }, "Frequently used": { "v": ["Utilizate frecvent"] }, "Objects": { "v": ["Obiecte"] }, "People & Body": { "v": ["Oameni și corp"] }, "Pick an emoji": { "v": ["Alege un emoji"] }, "Search emoji": { "v": ["Căutare emoji"] }, "Search results": { "v": ["Rezultatele căutării"] }, "Selected": { "v": ["Selectat"] }, "Smileys & Emotion": { "v": ["Zâmbete și emoții"] }, "Symbols": { "v": ["Simboluri"] }, "Travel & Places": { "v": ["Călătorii și locuri"] } } }, { "l": "ru", "t": { "Activities": { "v": ["События"] }, "Animals & Nature": { "v": ["Животные и природа "] }, "Custom": { "v": ["Пользовательское"] }, "Dark skin tone": { "v": ["Темный оттенок"] }, "Emoji picker": { "v": ["Подборщик эмодзи"] }, "Flags": { "v": ["Флаги"] }, "Food & Drink": { "v": ["Еда, напиток"] }, "Frequently used": { "v": ["Часто используемый"] }, "Light skin tone": { "v": ["Светлый оттенок"] }, "Medium dark skin tone": { "v": ["Средний темный оттенок"] }, "Medium light skin tone": { "v": ["Средний светлый оттенок"] }, "Medium skin tone": { "v": ["Средний оттенок"] }, "Neutral skin color": { "v": ["Нейтральный оттенок"] }, "Objects": { "v": ["Объекты"] }, "People & Body": { "v": ["Люди и тело"] }, "Pick an emoji": { "v": ["Выберите эмодзи"] }, "Search emoji": { "v": ["Поиск эмодзи"] }, "Search results": { "v": ["Результаты поиска"] }, "Selected": { "v": ["Выбрано"] }, "Skin tone": { "v": ["Оттенок скина"] }, "Smileys & Emotion": { "v": ["Смайлики и эмоции"] }, "Symbols": { "v": ["Символы"] }, "Travel & Places": { "v": ["Путешествия и места"] } } }, { "l": "sk", "t": { "Activities": { "v": ["Aktivity"] }, "Animals & Nature": { "v": ["Zvieratá a príroda"] }, "Custom": { "v": ["Vlastné"] }, "Dark skin tone": { "v": ["Tmavý vzhľad"] }, "Emoji picker": { "v": ["Výber emodži"] }, "Flags": { "v": ["Vlajky"] }, "Food & Drink": { "v": ["Jedlo a nápoje"] }, "Frequently used": { "v": ["Často používané"] }, "Light skin tone": { "v": ["Svetlý vzhľad"] }, "Medium dark skin tone": { "v": ["Stredne tmavý vzhľad"] }, "Medium light skin tone": { "v": ["Stredne svetlý vzhľad"] }, "Medium skin tone": { "v": ["Stredný vzhľad"] }, "Neutral skin color": { "v": ["Neutrálny vzhľad"] }, "Objects": { "v": ["Objekty"] }, "People & Body": { "v": ["Ľudia a telo"] }, "Pick an emoji": { "v": ["Vybrať emodži"] }, "Search emoji": { "v": ["Vyhľadať emoji"] }, "Search results": { "v": ["Výsledky vyhľadávania"] }, "Selected": { "v": ["Vybraný"] }, "Skin tone": { "v": ["Vzhľad"] }, "Smileys & Emotion": { "v": ["Smajlíky a emócie"] }, "Symbols": { "v": ["Symboly"] }, "Travel & Places": { "v": ["Cestovanie a miesta"] } } }, { "l": "sl", "t": { "Activities": { "v": ["Dejavnosti"] }, "Animals & Nature": { "v": ["Živali in Narava"] }, "Custom": { "v": ["Po meri"] }, "Flags": { "v": ["Zastavice"] }, "Food & Drink": { "v": ["Hrana in Pijača"] }, "Frequently used": { "v": ["Pogostost uporabe"] }, "Objects": { "v": ["Predmeti"] }, "People & Body": { "v": ["Ljudje in Telo"] }, "Pick an emoji": { "v": ["Izbor izrazne ikone"] }, "Search results": { "v": ["Zadetki iskanja"] }, "Smileys & Emotion": { "v": ["Izrazne ikone"] }, "Symbols": { "v": ["Simboli"] }, "Travel & Places": { "v": ["Potovanja in Kraji"] } } }, { "l": "sr", "t": { "Activities": { "v": ["Активности"] }, "Animals & Nature": { "v": ["Животиње и природа"] }, "Custom": { "v": ["Произвољно"] }, "Dark skin tone": { "v": ["Тамни тен коже"] }, "Emoji picker": { "v": ["Бирач емођија"] }, "Flags": { "v": ["Заставе"] }, "Food & Drink": { "v": ["Храна и пиће"] }, "Frequently used": { "v": ["Често коришћено"] }, "Light skin tone": { "v": ["Светли тен коже"] }, "Medium dark skin tone": { "v": ["Средње тамни тен коже"] }, "Medium light skin tone": { "v": ["Средње светли тен коже"] }, "Medium skin tone": { "v": ["Средњи тен коже"] }, "Neutral skin color": { "v": ["Неутрална боја коже"] }, "Objects": { "v": ["Предмети"] }, "People & Body": { "v": ["Људи и тело"] }, "Pick an emoji": { "v": ["Изаберите емођи"] }, "Search emoji": { "v": ["Претражи емођи"] }, "Search results": { "v": ["Резултати претраге"] }, "Selected": { "v": ["Изабрано"] }, "Skin tone": { "v": ["Тен коже"] }, "Smileys & Emotion": { "v": ["Смајлији и емоције"] }, "Symbols": { "v": ["Симболи"] }, "Travel & Places": { "v": ["Путовање и места"] } } }, { "l": "sv", "t": { "Activities": { "v": ["Aktiviteter"] }, "Animals & Nature": { "v": ["Djur & Natur"] }, "Custom": { "v": ["Anpassad"] }, "Dark skin tone": { "v": ["Mörk hudton"] }, "Emoji picker": { "v": ["Emoji-väljare"] }, "Flags": { "v": ["Flaggor"] }, "Food & Drink": { "v": ["Mat & Dryck"] }, "Frequently used": { "v": ["Används ofta"] }, "Light skin tone": { "v": ["Ljus hudton"] }, "Medium dark skin tone": { "v": ["Medium mörk hudton"] }, "Medium light skin tone": { "v": ["Medium ljus hudton"] }, "Medium skin tone": { "v": ["Medium hudton"] }, "Neutral skin color": { "v": ["Neutral hudfärg"] }, "Objects": { "v": ["Objekt"] }, "People & Body": { "v": ["Kropp & Själ"] }, "Pick an emoji": { "v": ["Välj en emoji"] }, "Search emoji": { "v": ["Sök emoji"] }, "Search results": { "v": ["Sökresultat"] }, "Selected": { "v": ["Vald"] }, "Skin tone": { "v": ["Hudton"] }, "Smileys & Emotion": { "v": ["Selfies & Känslor"] }, "Symbols": { "v": ["Symboler"] }, "Travel & Places": { "v": ["Resor & Sevärdigheter"] } } }, { "l": "tr", "t": { "Activities": { "v": ["Etkinlikler"] }, "Animals & Nature": { "v": ["Hayvanlar ve doğa"] }, "Custom": { "v": ["Özel"] }, "Dark skin tone": { "v": ["Koyu deri rengi"] }, "Emoji picker": { "v": ["Emoji seçici"] }, "Flags": { "v": ["Bayraklar"] }, "Food & Drink": { "v": ["Yeme ve içme"] }, "Frequently used": { "v": ["Sık kullanılanlar"] }, "Light skin tone": { "v": ["Açık deri rengi"] }, "Medium dark skin tone": { "v": ["Orta koyu deri rengi"] }, "Medium light skin tone": { "v": ["Orta açık deri rengi"] }, "Medium skin tone": { "v": ["Orta deri rengi"] }, "Neutral skin color": { "v": ["Nötr deri rengi"] }, "Objects": { "v": ["Nesneler"] }, "People & Body": { "v": ["İnsanlar ve beden"] }, "Pick an emoji": { "v": ["Bir emoji seçin"] }, "Search emoji": { "v": ["Emoji ara"] }, "Search results": { "v": ["Arama sonuçları"] }, "Selected": { "v": ["Seçilmiş"] }, "Skin tone": { "v": ["Deri rengi"] }, "Smileys & Emotion": { "v": ["İfadeler ve duygular"] }, "Symbols": { "v": ["Simgeler"] }, "Travel & Places": { "v": ["Gezi ve yerler"] } } }, { "l": "uk", "t": { "Activities": { "v": ["Діяльність"] }, "Animals & Nature": { "v": ["Тварини та природа"] }, "Custom": { "v": ["Власне"] }, "Dark skin tone": { "v": ["Смаглявий"] }, "Emoji picker": { "v": ["Вибір емоційки"] }, "Flags": { "v": ["Прапори"] }, "Food & Drink": { "v": ["Їжа та напої"] }, "Frequently used": { "v": ["Найчастіші"] }, "Light skin tone": { "v": ["Світла шкіра"] }, "Medium dark skin tone": { "v": ["Какао"] }, "Medium light skin tone": { "v": ["Лате"] }, "Medium skin tone": { "v": ["Середній колір шкіри"] }, "Neutral skin color": { "v": ["Нейтральний колір шкіри"] }, "Objects": { "v": ["Об'єкти"] }, "People & Body": { "v": ["Люди та жести"] }, "Pick an emoji": { "v": ["Виберіть емоційку"] }, "Search emoji": { "v": ["Шукати емоційки"] }, "Search results": { "v": ["Результати пошуку"] }, "Selected": { "v": ["Вибрано"] }, "Skin tone": { "v": ["Колір шкіри"] }, "Smileys & Emotion": { "v": ["Смайли та емоції"] }, "Symbols": { "v": ["Символи"] }, "Travel & Places": { "v": ["Поїздки та місця"] } } }, { "l": "uz", "t": { "Activities": { "v": ["Faolliklar"] }, "Animals & Nature": { "v": ["Hayvonlar va Tabiat"] }, "Custom": { "v": ["Moslashtirilgan"] }, "Dark skin tone": { "v": ["Qora rangdagi qoplama"] }, "Emoji picker": { "v": ["Emoji tanlagich"] }, "Flags": { "v": ["Bayroqlar"] }, "Food & Drink": { "v": ["Oziq-ovqat va ichimliklar"] }, "Frequently used": { "v": ["Tez-tez ishlatiladi"] }, "Light skin tone": { "v": ["Yorug` rangdagi qoplama"] }, "Medium dark skin tone": { "v": ["O`rtacha qorong`u rangdagi qoplama"] }, "Medium light skin tone": { "v": ["O`rtacha yorug`lik rangdagi qoplama"] }, "Medium skin tone": { "v": ["O`rtacha rangdagi qoplama"] }, "Neutral skin color": { "v": ["Neytral rang"] }, "Objects": { "v": ["Obyekt"] }, "People & Body": { "v": ["Odamlar va Tana"] }, "Pick an emoji": { "v": ["Emojini tanlang"] }, "Search emoji": { "v": ["Emoji qidirish"] }, "Search results": { "v": ["Qidiruv natijalari"] }, "Selected": { "v": ["Tanlangan"] }, "Skin tone": { "v": ["Odatiy rangdagi qoplama"] }, "Smileys & Emotion": { "v": ["Smayllar va Hissiyotlar"] }, "Symbols": { "v": ["Belgilar"] }, "Travel & Places": { "v": ["Sayohat va Joylar"] } } }, { "l": "zh-CN", "t": { "Activities": { "v": ["活动"] }, "Animals & Nature": { "v": ["动物 & 自然"] }, "Custom": { "v": ["自定义"] }, "Dark skin tone": { "v": ["深色皮肤"] }, "Emoji picker": { "v": ["表情拾取器"] }, "Flags": { "v": ["旗帜"] }, "Food & Drink": { "v": ["食物 & 饮品"] }, "Frequently used": { "v": ["经常使用"] }, "Light skin tone": { "v": ["浅色皮肤"] }, "Medium dark skin tone": { "v": ["中等深色皮肤"] }, "Medium light skin tone": { "v": ["中等浅色皮肤"] }, "Medium skin tone": { "v": ["中等皮肤"] }, "Neutral skin color": { "v": ["中性皮肤颜色"] }, "Objects": { "v": ["物体"] }, "People & Body": { "v": ["人 & 身体"] }, "Pick an emoji": { "v": ["选择一个表情"] }, "Search emoji": { "v": ["搜索表情"] }, "Search results": { "v": ["搜索结果"] }, "Selected": { "v": ["选择"] }, "Skin tone": { "v": ["皮肤"] }, "Smileys & Emotion": { "v": ["笑脸 & 情感"] }, "Symbols": { "v": ["符号"] }, "Travel & Places": { "v": ["旅游 & 地点"] } } }, { "l": "zh-HK", "t": { "Activities": { "v": ["活動"] }, "Animals & Nature": { "v": ["動物與自然"] }, "Custom": { "v": ["自定義"] }, "Dark skin tone": { "v": ["深膚色"] }, "Emoji picker": { "v": ["表情符號選擇器"] }, "Flags": { "v": ["旗幟"] }, "Food & Drink": { "v": ["食物與飲料"] }, "Frequently used": { "v": ["經常使用"] }, "Light skin tone": { "v": ["淺膚色"] }, "Medium dark skin tone": { "v": ["中等深膚色"] }, "Medium light skin tone": { "v": ["中等淺膚色"] }, "Medium skin tone": { "v": ["中等膚色"] }, "Neutral skin color": { "v": ["中性色膚色"] }, "Objects": { "v": ["物件"] }, "People & Body": { "v": ["人物"] }, "Pick an emoji": { "v": ["選擇表情符號"] }, "Search emoji": { "v": ["搜尋表情符號"] }, "Search results": { "v": ["搜尋結果"] }, "Selected": { "v": ["已選"] }, "Skin tone": { "v": ["膚色"] }, "Smileys & Emotion": { "v": ["表情"] }, "Symbols": { "v": ["標誌"] }, "Travel & Places": { "v": ["旅遊與景點"] } } }, { "l": "zh-TW", "t": { "Activities": { "v": ["活動"] }, "Animals & Nature": { "v": ["動物與自然"] }, "Custom": { "v": ["自定義"] }, "Dark skin tone": { "v": ["深膚色"] }, "Emoji picker": { "v": ["表情符號挑選器"] }, "Flags": { "v": ["旗幟"] }, "Food & Drink": { "v": ["食物與飲料"] }, "Frequently used": { "v": ["最近使用"] }, "Light skin tone": { "v": ["淺膚色"] }, "Medium dark skin tone": { "v": ["中等深膚色"] }, "Medium light skin tone": { "v": ["中等淺膚色"] }, "Medium skin tone": { "v": ["中等膚色"] }, "Neutral skin color": { "v": ["中性膚色"] }, "Objects": { "v": ["物件"] }, "People & Body": { "v": ["人物"] }, "Pick an emoji": { "v": ["選擇表情符號"] }, "Search emoji": { "v": ["搜尋表情符號"] }, "Search results": { "v": ["搜尋結果"] }, "Selected": { "v": ["已選取"] }, "Skin tone": { "v": ["膚色"] }, "Smileys & Emotion": { "v": ["表情"] }, "Symbols": { "v": ["標誌"] }, "Travel & Places": { "v": ["旅遊與景點"] } } }];
const t6 = [{ "l": "ar", "t": { "Add to a project": { "v": ["أضف إلى مشروع"] }, "Connect items to a project to make them easier to find": { "v": ["ربط عناصر بمشروع لتسهيل العثور عليها"] }, "Failed to add the item to the project": { "v": ["تعذر ربط عنصر بمشروع"] }, "Failed to create a project": { "v": ["تعذر إنشاء مشروع"] }, "Failed to rename the project": { "v": ["تعذّر تغيير اسم المشروع"] }, "Type to search for existing projects": { "v": ["أكتُب للبحث في المشاريع الموجودة"] } } }, { "l": "ast", "t": {} }, { "l": "br", "t": {} }, { "l": "ca", "t": {} }, { "l": "cs", "t": { "Add to a project": { "v": ["Přidat do projektu"] }, "Connect items to a project to make them easier to find": { "v": ["Připojte položky k projektu, čímž budou snáze dohledatelné"] }, "Failed to add the item to the project": { "v": ["Položku se nepodařilo přidat do projektu"] }, "Failed to create a project": { "v": ["Projekt se nepodařilo vytvořit"] }, "Failed to rename the project": { "v": ["Projekt se nepodařilo přejmenovat"] }, "Type to search for existing projects": { "v": ["Psaním vyhledávejte existující projekty"] } } }, { "l": "cs-CZ", "t": {} }, { "l": "da", "t": { "Add to a project": { "v": ["Tilføj til et projekt"] }, "Connect items to a project to make them easier to find": { "v": ["Forbind elementer til et projekt for at gøre dem nemmere at finde"] }, "Failed to add the item to the project": { "v": ["Kunne ikke føje elementet til projektet"] }, "Failed to create a project": { "v": ["Kunne ikke oprette et projekt"] }, "Failed to rename the project": { "v": ["Projektet kunne ikke omdøbes"] }, "Type to search for existing projects": { "v": ["Skriv for at søge efter eksisterende projekter"] } } }, { "l": "de", "t": { "Add to a project": { "v": ["Einem Projekt hinzufügen"] }, "Connect items to a project to make them easier to find": { "v": ["Verbinde Elemente mit einem Projekt, um sie leichter zu finden"] }, "Failed to add the item to the project": { "v": ["Das Element konnte nicht zum Projekt hinzugefügt werden"] }, "Failed to create a project": { "v": ["Projekt konnte nicht erstellt werden"] }, "Failed to rename the project": { "v": ["Das Projekt konnte nicht umbenannt werden"] }, "Type to search for existing projects": { "v": ["Tippen, um nach vorhandenen Projekten zu suchen"] } } }, { "l": "de-DE", "t": { "Add to a project": { "v": ["Einem Projekt hinzufügen"] }, "Connect items to a project to make them easier to find": { "v": ["Verbinden Sie Elemente mit einem Projekt, um sie leichter zu finden"] }, "Failed to add the item to the project": { "v": ["Das Element konnte nicht zum Projekt hinzugefügt werden"] }, "Failed to create a project": { "v": ["Projekt konnte nicht erstellt werden"] }, "Failed to rename the project": { "v": ["Das Projekt konnte nicht umbenannt werden"] }, "Type to search for existing projects": { "v": ["Tippen, um nach vorhandenen Projekten zu suchen"] } } }, { "l": "el", "t": { "Add to a project": { "v": ["Προσθήκη σε ένα έργο"] }, "Connect items to a project to make them easier to find": { "v": ["Συνδέστε αντικείμενα σε ένα έργο για να τα βρίσκετε πιο εύκολα"] }, "Failed to add the item to the project": { "v": ["Αποτυχία προσθήκης του αντικειμένου στο έργο"] }, "Failed to create a project": { "v": ["Αποτυχία δημιουργίας έργου"] }, "Failed to rename the project": { "v": ["Αποτυχία μετονομασίας του έργου"] }, "Type to search for existing projects": { "v": ["Πληκτρολογήστε για αναζήτηση υπαρχόντων έργων"] } } }, { "l": "en-GB", "t": { "Add to a project": { "v": ["Add to a project"] }, "Connect items to a project to make them easier to find": { "v": ["Connect items to a project to make them easier to find"] }, "Failed to add the item to the project": { "v": ["Failed to add the item to the project"] }, "Failed to create a project": { "v": ["Failed to create a project"] }, "Failed to rename the project": { "v": ["Failed to rename the project"] }, "Type to search for existing projects": { "v": ["Type to search for existing projects"] } } }, { "l": "eo", "t": {} }, { "l": "es", "t": {} }, { "l": "es-AR", "t": { "Add to a project": { "v": ["Agregar a un proyecto"] }, "Connect items to a project to make them easier to find": { "v": ["Conecte items a un proyecto para hacerlos más fáciles de encontrar"] }, "Failed to add the item to the project": { "v": ["No se pudo agregar el elemento al proyecto"] }, "Failed to create a project": { "v": ["No se pudo crear un proyecto"] }, "Failed to rename the project": { "v": ["No se pudo renombrar el proyecto"] }, "Type to search for existing projects": { "v": ["Escriba para buscar proyectos existentes"] } } }, { "l": "es-EC", "t": {} }, { "l": "es-MX", "t": { "Add to a project": { "v": ["Agregar a un proyecto"] }, "Connect items to a project to make them easier to find": { "v": ["Conecte elementos a un proyecto para hacerlos más fáciles de encontrar"] }, "Failed to add the item to the project": { "v": ["No se pudo agregar el elemento al proyecto"] }, "Failed to create a project": { "v": ["No se pudo crear el proyecto"] }, "Failed to rename the project": { "v": ["No se pudo renombrar el proyecto"] }, "Type to search for existing projects": { "v": ["Escriba para buscar proyectos existentes"] } } }, { "l": "et-EE", "t": { "Add to a project": { "v": ["Lisa projekti"] }, "Connect items to a project to make them easier to find": { "v": ["Selleks, et objekte oleks lihtsam leida, seo nad projektiga"] }, "Failed to add the item to the project": { "v": ["Objekti lisamine projekti ei õnnestunud"] }, "Failed to create a project": { "v": ["Projekti loomine ei õnnestunud"] }, "Failed to rename the project": { "v": ["Projekti nime muutmine ei õnnestunud"] }, "Type to search for existing projects": { "v": ["Olemasolevate projektide otsimiseks kirjuta"] } } }, { "l": "eu", "t": {} }, { "l": "fa", "t": { "Add to a project": { "v": ["افزودن به پروژه"] }, "Connect items to a project to make them easier to find": { "v": ["برای پیدا کردن راحت‌تر، مواردی را به پروژه متصل کنید"] }, "Failed to add the item to the project": { "v": ["موارد به پروژه اضافه نشد"] }, "Failed to create a project": { "v": ["ایجاد پروژه نامؤفق بود"] }, "Failed to rename the project": { "v": ["تغییر نام پروژه انجام نشد"] }, "Type to search for existing projects": { "v": ["برای جستجوی پروژه‌های موجود تایپ کنید"] } } }, { "l": "fi", "t": { "Add to a project": { "v": ["Lisää projektiin"] }, "Connect items to a project to make them easier to find": { "v": ["Yhdistä kohteet projektiin, jotta ne olisivat helpompia löytää"] }, "Failed to add the item to the project": { "v": ["Kohteiden lisääminen projektiin epäonnistui"] }, "Failed to create a project": { "v": ["Projektin luominen epäonnistui"] }, "Failed to rename the project": { "v": ["Projektin nimeäminen epäonnistui"] }, "Type to search for existing projects": { "v": ["Kirjoita etsiäksesi olemassaolevia projekteja"] } } }, { "l": "fr", "t": { "Add to a project": { "v": ["Ajouter à un projet"] }, "Connect items to a project to make them easier to find": { "v": ["Connectez des éléments à un projet pour les retrouver plus facilement"] }, "Failed to add the item to the project": { "v": ["Impossible d'ajouter l'élément au projet"] }, "Failed to create a project": { "v": ["Impossible de créer un projet"] }, "Failed to rename the project": { "v": ["Impossible de renommer le projet"] }, "Type to search for existing projects": { "v": ["Tapez pour rechercher des projets existants"] } } }, { "l": "ga", "t": { "Add to a project": { "v": ["Cuir le tionscadal"] }, "Connect items to a project to make them easier to find": { "v": ["Ceangail míreanna le tionscadal chun iad a dhéanamh níos éasca iad a aimsiú"] }, "Failed to add the item to the project": { "v": ["Theip ar an mír a chur leis an tionscadal"] }, "Failed to create a project": { "v": ["Theip ar thionscadal a chruthú"] }, "Failed to rename the project": { "v": ["Theip ar an tionscadal a athainmniú"] }, "Type to search for existing projects": { "v": ["Clóscríobh chun tionscadail atá ann cheana a chuardach"] } } }, { "l": "gl", "t": { "Add to a project": { "v": ["Engadir a un proxecto"] }, "Connect items to a project to make them easier to find": { "v": ["Conectar elementos a un proxecto para facelos máis doados de atopar"] }, "Failed to add the item to the project": { "v": ["Produciuse un fallo ao engadir o elemento ao proxecto"] }, "Failed to create a project": { "v": ["Produciuse un fallo ao crear un proxecto"] }, "Failed to rename the project": { "v": ["Produciuse un fallo ao cambiarlle o nome ao proxecto"] }, "Type to search for existing projects": { "v": ["Escriba para buscar proxectos existentes"] } } }, { "l": "he", "t": {} }, { "l": "hu", "t": {} }, { "l": "id", "t": {} }, { "l": "is", "t": { "Add to a project": { "v": ["Bæta við verkefni"] }, "Connect items to a project to make them easier to find": { "v": ["Tengdu atriði við verkefni til að gera einfaldara að finna þau"] }, "Failed to add the item to the project": { "v": ["Mistókst að bæta atriðinu í verkefnið"] }, "Failed to create a project": { "v": ["Mistókst að útbúa verkefni"] }, "Failed to rename the project": { "v": ["Mistókst að endurnefna verkefnið"] }, "Type to search for existing projects": { "v": ["Skrifaðu hér til að leita að fyrirliggjandi verkefnum"] } } }, { "l": "it", "t": {} }, { "l": "ja", "t": { "Add to a project": { "v": ["プロジェクトに追加する"] }, "Connect items to a project to make them easier to find": { "v": ["項目をプロジェクトに接続して検索しやすくする"] }, "Failed to add the item to the project": { "v": ["プロジェクトへのアイテムの追加に失敗しました"] }, "Failed to create a project": { "v": ["プロジェクトの作成に失敗しました"] }, "Failed to rename the project": { "v": ["プロジェクトの名前変更に失敗しました"] }, "Type to search for existing projects": { "v": ["既存のプロジェクトを検索するために入力します"] } } }, { "l": "ja-JP", "t": {} }, { "l": "ko", "t": { "Add to a project": { "v": ["프로젝트에 추가"] }, "Connect items to a project to make them easier to find": { "v": ["항목을 더 쉽게 찾을 수 있도록 프로젝트에 연결하세요."] }, "Failed to add the item to the project": { "v": ["항목을 프로젝트에 추가하는 데 실패함"] }, "Failed to create a project": { "v": ["프로젝트를 만드는 데 실패함"] }, "Failed to rename the project": { "v": ["프로젝트의 이름을 바꾸는 데 실패함"] }, "Type to search for existing projects": { "v": ["입력하여 프로젝트를 검색"] } } }, { "l": "lo", "t": { "Add to a project": { "v": ["ເພີ່ມໃສ່ໂຄງການ"] }, "Connect items to a project to make them easier to find": { "v": ["ເຊື່ອມຕໍ່ລາຍການຕ່າງໆໃສ່ໂຄງການ ເພື່ອເຮັດໃຫ້ຊອກຫາງ່າຍຂຶ້ນ"] }, "Failed to add the item to the project": { "v": ["ບໍ່ສາມາດເພີ່ມລາຍການໃສ່ໂຄງການໄດ້"] }, "Failed to create a project": { "v": ["ບໍ່ສາມາດສ້າງໂຄງການໄດ້"] }, "Failed to rename the project": { "v": ["ບໍ່ສາມາດປ່ຽນຊື່ໂຄງການໄດ້"] }, "Type to search for existing projects": { "v": ["ພິມເພື່ອຄົ້ນຫາໂຄງການທີ່ມີຢູ່"] } } }, { "l": "lt-LT", "t": {} }, { "l": "lv", "t": {} }, { "l": "mk", "t": { "Add to a project": { "v": ["Додај во проект"] }, "Connect items to a project to make them easier to find": { "v": ["Поврзете ги елементите со проект за да биде полесно да се најдат"] }, "Failed to add the item to the project": { "v": ["Неуспешно додавање на елементот во проектот"] }, "Failed to create a project": { "v": ["Неуспешно креирање на проект"] }, "Failed to rename the project": { "v": ["Неуспешно преименување на проектот"] }, "Type to search for existing projects": { "v": ["Пишувај за пребарување постоечки проекти"] } } }, { "l": "my", "t": {} }, { "l": "nb", "t": { "Add to a project": { "v": ["Legge til i et prosjekt"] }, "Connect items to a project to make them easier to find": { "v": ["Koble elementer til et prosjekt for å gjøre det enklere å finne dem"] }, "Failed to add the item to the project": { "v": ["Kan ikke legge til elementet i prosjektet"] }, "Failed to create a project": { "v": ["Kan ikke opprette et prosjekt"] }, "Failed to rename the project": { "v": ["Kunne ikke gi prosjektet nytt navn"] }, "Type to search for existing projects": { "v": ["Skriv for å søke for eksisterende prosjekter"] } } }, { "l": "nl", "t": { "Add to a project": { "v": ["Toevoegen aan een project"] }, "Connect items to a project to make them easier to find": { "v": ["Items aan een project koppelen om ze eenvoudiger te vinden"] }, "Failed to add the item to the project": { "v": ["Toevoegen van item aan project mislukt"] }, "Failed to create a project": { "v": ["Project aanmaken mislukt"] }, "Failed to rename the project": { "v": ["Project hernoemen mislukt"] }, "Type to search for existing projects": { "v": ["Typ om te zoeken naar bestaande projecten"] } } }, { "l": "oc", "t": {} }, { "l": "pl", "t": { "Add to a project": { "v": ["Dodaj do projektu"] }, "Connect items to a project to make them easier to find": { "v": ["Połącz elementy z projektem, aby ułatwić ich znalezienie"] }, "Failed to add the item to the project": { "v": ["Nie udało się dodać elementu do projektu"] }, "Failed to create a project": { "v": ["Nie udało się utworzyć projektu"] }, "Failed to rename the project": { "v": ["Nie udało się zmienić nazwy projektu"] }, "Type to search for existing projects": { "v": ["Wpisz, aby wyszukać istniejące projekty"] } } }, { "l": "pt-BR", "t": { "Add to a project": { "v": ["Adicionar a um projeto"] }, "Connect items to a project to make them easier to find": { "v": ["Conectar itens a um projeto para encontrá-los mais facilmente"] }, "Failed to add the item to the project": { "v": ["Falha ao adicionar itens ao projeto"] }, "Failed to create a project": { "v": ["Falha ao criar um projeto"] }, "Failed to rename the project": { "v": ["Falha ao renomear o projeto"] }, "Type to search for existing projects": { "v": ["Digite para pesquisar projetos existentes"] } } }, { "l": "pt-PT", "t": { "Add to a project": { "v": ["Adicionar a um projeto"] }, "Connect items to a project to make them easier to find": { "v": ["Ligar itens a um projeto para serem mais facilmente encontrados"] }, "Failed to add the item to the project": { "v": ["Não foi possível adicionar item ao projeto"] }, "Failed to create a project": { "v": ["Não foi possível criar um projeto"] }, "Failed to rename the project": { "v": ["Não foi possível alterar o nome do projeto"] }, "Type to search for existing projects": { "v": ["Digite para procurar projetos existentes"] } } }, { "l": "ro", "t": {} }, { "l": "ru", "t": { "Add to a project": { "v": ["Добавить в проект"] }, "Connect items to a project to make them easier to find": { "v": ["Подключайте элементы к проекту, чтобы их было легче найти"] }, "Failed to add the item to the project": { "v": ["Не удалось добавить элемент в проект"] }, "Failed to create a project": { "v": ["Не удалось создать проект"] }, "Failed to rename the project": { "v": ["Не удалось переименовать проект"] }, "Type to search for existing projects": { "v": ["Введите для поиска существующих проектов"] } } }, { "l": "sk", "t": { "Add to a project": { "v": ["Pridať do projektu"] }, "Connect items to a project to make them easier to find": { "v": ["Pridať položky do projektu pre jednoduchšie vyhľadávanie"] }, "Failed to add the item to the project": { "v": ["Nepodarilo sa pridať položku do projektu"] }, "Failed to create a project": { "v": ["Nepodarilo sa vytvoriť projekt"] }, "Failed to rename the project": { "v": ["Nepodarilo sa premenovať projekt"] }, "Type to search for existing projects": { "v": ["Začnite písať pre vyhľadávanie v existujúcich projektoch"] } } }, { "l": "sl", "t": {} }, { "l": "sr", "t": { "Add to a project": { "v": ["Додај у пројекат"] }, "Connect items to a project to make them easier to find": { "v": ["Повезује ставке у пројекат како би се лакше пронашле"] }, "Failed to add the item to the project": { "v": ["Није успело додавање ставке у пројекат"] }, "Failed to create a project": { "v": ["Није успело креирање пројекта"] }, "Failed to rename the project": { "v": ["Није успела промена имена пројекта"] }, "Type to search for existing projects": { "v": ["Куцајте да претражите постојеће пројекте"] } } }, { "l": "sv", "t": { "Add to a project": { "v": ["Lägg till i ett projekt"] }, "Connect items to a project to make them easier to find": { "v": ["Anslut objekt till ett projekt för att göra dem lättare att hitta"] }, "Failed to add the item to the project": { "v": ["Det gick inte att lägga till objektet i projektet"] }, "Failed to create a project": { "v": ["Det gick inte att skapa ett projekt"] }, "Failed to rename the project": { "v": ["Kunde inte byta namn på projektet"] }, "Type to search for existing projects": { "v": ["Skriv för att söka efter befintliga projekt"] } } }, { "l": "tr", "t": { "Add to a project": { "v": ["Bir projeye ekle"] }, "Connect items to a project to make them easier to find": { "v": ["Ögeleri daha kolay bulmak için bir proje ile ilişkilendirin"] }, "Failed to add the item to the project": { "v": ["Öge projeye eklenemedi"] }, "Failed to create a project": { "v": ["Bir proje oluşturulamadı"] }, "Failed to rename the project": { "v": ["Proje yeniden adlandırılamadı"] }, "Type to search for existing projects": { "v": ["Var olan projeleri aramak için yazmaya başlayın"] } } }, { "l": "uk", "t": { "Add to a project": { "v": ["Додати до проєкту"] }, "Connect items to a project to make them easier to find": { "v": ["Приєднайте ресурси до проєкту для швидшого  пошуку"] }, "Failed to add the item to the project": { "v": ["Не вдалося приєднати ресурс до проєкту"] }, "Failed to create a project": { "v": ["Не вдалося створити проєкт"] }, "Failed to rename the project": { "v": ["Не вдалося перейменувати проєкт"] }, "Type to search for existing projects": { "v": ["Почніть вводити, щоб знайти проєкт"] } } }, { "l": "uz", "t": { "Add to a project": { "v": ["Loyihaga qo'shish"] }, "Connect items to a project to make them easier to find": { "v": ["Elementlarni topishni osonlashtirish uchun ularni loyihaga ulang"] }, "Failed to add the item to the project": { "v": ["Ob'ektni loyihaga qo'shib bo'lmadi"] }, "Failed to create a project": { "v": ["Loyiha yaratib bo‘lmadi"] }, "Failed to rename the project": { "v": ["Loyiha nomini o‘zgartirib bo‘lmadi"] }, "Type to search for existing projects": { "v": ["Mavjud loyihalarni qidirish uchun kiriting"] } } }, { "l": "zh-CN", "t": { "Add to a project": { "v": ["添加至一个项目"] }, "Connect items to a project to make them easier to find": { "v": ["将条目连接至一个项目以易于查找"] }, "Failed to add the item to the project": { "v": ["添加条目至项目失败"] }, "Failed to create a project": { "v": ["创建项目失败"] }, "Failed to rename the project": { "v": ["重命名项目失败"] }, "Type to search for existing projects": { "v": ["输入以搜索现存项目"] } } }, { "l": "zh-HK", "t": { "Add to a project": { "v": ["添加到方案中"] }, "Connect items to a project to make them easier to find": { "v": ["將項目連接到方案中，以便更容易找到。"] }, "Failed to add the item to the project": { "v": ["無法將項目添加到方案中"] }, "Failed to create a project": { "v": ["無法創建方案"] }, "Failed to rename the project": { "v": ["無法重命名方案"] }, "Type to search for existing projects": { "v": ["輸入以搜索現有方案"] } } }, { "l": "zh-TW", "t": { "Add to a project": { "v": ["新增至專案中"] }, "Connect items to a project to make them easier to find": { "v": ["將項目連結至專案中以方便尋找"] }, "Failed to add the item to the project": { "v": ["新增項目至專案失敗"] }, "Failed to create a project": { "v": ["建立專案失敗"] }, "Failed to rename the project": { "v": ["重新命名專案失敗"] }, "Type to search for existing projects": { "v": ["輸入以搜尋既有專案"] } } }];
const t8 = [{ "l": "ar", "t": { "Any link": { "v": ["أيَّ رابط"] } } }, { "l": "ast", "t": { "Any link": { "v": ["Cualesquier enllaz"] } } }, { "l": "br", "t": {} }, { "l": "ca", "t": {} }, { "l": "cs", "t": { "Any link": { "v": ["Jakýkoli odkaz"] } } }, { "l": "cs-CZ", "t": { "Any link": { "v": ["Jakýkoli odkaz"] } } }, { "l": "da", "t": { "Any link": { "v": ["Ethvert link"] } } }, { "l": "de", "t": { "Any link": { "v": ["Irgendein Link"] } } }, { "l": "de-DE", "t": { "Any link": { "v": ["Irgendein Link"] } } }, { "l": "el", "t": { "Any link": { "v": ["Οποιοσδήποτε σύνδεσμος"] } } }, { "l": "en-GB", "t": { "Any link": { "v": ["Any link"] } } }, { "l": "eo", "t": {} }, { "l": "es", "t": { "Any link": { "v": ["Cualquier enlace"] } } }, { "l": "es-AR", "t": { "Any link": { "v": ["Cualquier enlace"] } } }, { "l": "es-EC", "t": { "Any link": { "v": ["Cualquier enlace"] } } }, { "l": "es-MX", "t": { "Any link": { "v": ["Cualquier enlace"] } } }, { "l": "et-EE", "t": { "Any link": { "v": ["Mistahes link"] } } }, { "l": "eu", "t": { "Any link": { "v": ["Edozein esteka"] } } }, { "l": "fa", "t": { "Any link": { "v": ["هر پیوندی"] } } }, { "l": "fi", "t": { "Any link": { "v": ["Mikä tahansa linkki"] } } }, { "l": "fr", "t": { "Any link": { "v": ["N'importe quel lien"] } } }, { "l": "ga", "t": { "Any link": { "v": ["Aon nasc"] } } }, { "l": "gl", "t": { "Any link": { "v": ["Calquera ligazón"] } } }, { "l": "he", "t": { "Any link": { "v": ["קישור כלשהו"] } } }, { "l": "hu", "t": {} }, { "l": "id", "t": { "Any link": { "v": ["Semua tautan"] } } }, { "l": "is", "t": { "Any link": { "v": ["Einhver tengill"] } } }, { "l": "it", "t": { "Any link": { "v": ["Qualsiasi link"] } } }, { "l": "ja", "t": { "Any link": { "v": ["任意のリンク"] } } }, { "l": "ja-JP", "t": { "Any link": { "v": ["任意のリンク"] } } }, { "l": "ko", "t": { "Any link": { "v": ["아무 링크"] } } }, { "l": "lo", "t": { "Any link": { "v": ["ລິງໃດກໍໄດ້"] } } }, { "l": "lt-LT", "t": {} }, { "l": "lv", "t": {} }, { "l": "mk", "t": { "Any link": { "v": ["Секој линк"] } } }, { "l": "my", "t": {} }, { "l": "nb", "t": { "Any link": { "v": ["Enhver lenke"] } } }, { "l": "nl", "t": { "Any link": { "v": ["Elke link"] } } }, { "l": "oc", "t": {} }, { "l": "pl", "t": { "Any link": { "v": ["Dowolny link"] } } }, { "l": "pt-BR", "t": { "Any link": { "v": ["Qualquer link"] } } }, { "l": "pt-PT", "t": { "Any link": { "v": ["Qualquer hiperligação"] } } }, { "l": "ro", "t": { "Any link": { "v": ["Orice link"] } } }, { "l": "ru", "t": { "Any link": { "v": ["Любая ссылка"] } } }, { "l": "sk", "t": { "Any link": { "v": ["Akýkoľvek odkaz"] } } }, { "l": "sl", "t": {} }, { "l": "sr", "t": { "Any link": { "v": ["Било који линк"] } } }, { "l": "sv", "t": { "Any link": { "v": ["Vilken länk som helst"] } } }, { "l": "tr", "t": { "Any link": { "v": ["Herhangi bir bağlantı"] } } }, { "l": "uk", "t": { "Any link": { "v": ["Будь-яке посилання"] } } }, { "l": "uz", "t": { "Any link": { "v": ["Har qanday havola"] } } }, { "l": "zh-CN", "t": { "Any link": { "v": ["任何链接"] } } }, { "l": "zh-HK", "t": { "Any link": { "v": ["任何連結"] } } }, { "l": "zh-TW", "t": { "Any link": { "v": ["任何連結"] } } }];
const t9 = [{ "l": "ar", "t": { "Anything shared with the same group of people will show up here": { "v": ["أيّ مادة تمت مشاركتها مع نفس المجموعة من الأشخاص سيتم عرضها هنا"] }, "Error getting related resources. Please contact your system administrator if you have any questions.": { "v": ["خطأ في الحصول على الموارد ذات الصلة. يرجى الاتصال بمشرف النظام عندك إذا كان لديك أيّ أسئلة."] }, "Related resources": { "v": ["مصادر ذات صلة"] } } }, { "l": "ast", "t": { "Anything shared with the same group of people will show up here": { "v": ["Equí va apaecer tolo que compartas col mesmu grupu de persones"] }, "Error getting related resources. Please contact your system administrator if you have any questions.": { "v": ["Hebo un error al consiguir los recursos rellacionaos. Ponte en contautu col alministrador del sistema si tienes dalguna entruga."] }, "Related resources": { "v": ["Recursos rellacionao"] } } }, { "l": "br", "t": {} }, { "l": "ca", "t": { "Anything shared with the same group of people will show up here": { "v": ["Qualsevol cosa compartida amb el mateix grup de persones es mostrarà aquí"] }, "Related resources": { "v": ["Recursos relacionats"] } } }, { "l": "cs", "t": { "Anything shared with the same group of people will show up here": { "v": ["Cokoli nasdíleného stejné skupině lidí se zobrazí zde"] }, "Error getting related resources. Please contact your system administrator if you have any questions.": { "v": ["Chyba při získávání souvisejících prostředků. Pokud máte jakékoli dotazy, obraťte se na správce vámi využívaného systému."] }, "Related resources": { "v": ["Související prostředky"] } } }, { "l": "cs-CZ", "t": { "Anything shared with the same group of people will show up here": { "v": ["Cokoli nasdíleného stejné skupině lidí se zobrazí zde"] }, "Error getting related resources. Please contact your system administrator if you have any questions.": { "v": ["Chyba při získávání souvisejících prostředků. Pokud máte jakékoli dotazy, obraťte se na správce vámi využívaného systému."] }, "Related resources": { "v": ["Související prostředky"] } } }, { "l": "da", "t": { "Anything shared with the same group of people will show up here": { "v": ["Alt der deles med samme gruppe af personer vil vises her"] }, "Error getting related resources. Please contact your system administrator if you have any questions.": { "v": ["Fejl ved hentning af relaterede ressourcer. Kontakt venligst din systemadministrator, hvis du har spørgsmål."] }, "Related resources": { "v": ["Relaterede emner"] } } }, { "l": "de", "t": { "Anything shared with the same group of people will show up here": { "v": ["Alles, das mit derselben Gruppe von Personen geteilt wird, wird hier angezeigt"] }, "Error getting related resources. Please contact your system administrator if you have any questions.": { "v": ["Fehler beim Abrufen verwandter Ressourcen. Bei Fragen wende dich bitte an deinen Systemadministrator."] }, "Related resources": { "v": ["Verwandte Ressourcen"] } } }, { "l": "de-DE", "t": { "Anything shared with the same group of people will show up here": { "v": ["Alles, das mit derselben Gruppe von Personen geteilt wird, wird hier angezeigt"] }, "Error getting related resources. Please contact your system administrator if you have any questions.": { "v": ["Fehler beim Abrufen verwandter Ressourcen. Bei Fragen wenden Sie sich bitte an Ihre Systemadministration."] }, "Related resources": { "v": ["Verwandte Ressourcen"] } } }, { "l": "el", "t": { "Anything shared with the same group of people will show up here": { "v": ["Οτιδήποτε μοιράζεται με την ίδια ομάδα ατόμων θα εμφανίζεται εδώ"] }, "Error getting related resources. Please contact your system administrator if you have any questions.": { "v": ["Σφάλμα λήψης σχετικών πόρων. Παρακαλούμε επικοινωνήστε με τον διαχειριστή του συστήματός σας εάν έχετε οποιεσδήποτε ερωτήσεις."] }, "Related resources": { "v": ["Σχετικοί πόροι"] } } }, { "l": "en-GB", "t": { "Anything shared with the same group of people will show up here": { "v": ["Anything shared with the same group of people will show up here"] }, "Error getting related resources. Please contact your system administrator if you have any questions.": { "v": ["Error getting related resources. Please contact your system administrator if you have any questions."] }, "Related resources": { "v": ["Related resources"] } } }, { "l": "eo", "t": {} }, { "l": "es", "t": { "Anything shared with the same group of people will show up here": { "v": ["Cualquier cosa que esté compartida con el mismo grupo de personas se mostrará aquí"] }, "Error getting related resources. Please contact your system administrator if you have any questions.": { "v": ["Error al obtener recursos relacionados. Por favor, contacte a su administrador del sistema si tiene alguna pregunta."] }, "Related resources": { "v": ["Recursos relacionados"] } } }, { "l": "es-AR", "t": { "Anything shared with the same group of people will show up here": { "v": ["Cualquier cosa compartida con el mismo grupo de personas aparecerá aquí."] }, "Error getting related resources. Please contact your system administrator if you have any questions.": { "v": ["Error al obtener recursos relacionados. Por favor, contacte a su administrador del sistema si tiene alguna pregunta."] }, "Related resources": { "v": ["Recursos relacionados"] } } }, { "l": "es-EC", "t": { "Anything shared with the same group of people will show up here": { "v": ["Cualquier cosa compartida con el mismo grupo de personas aparecerá aquí."] }, "Error getting related resources. Please contact your system administrator if you have any questions.": { "v": ["Error al obtener recursos relacionados. Por favor, contacta a tu administrador del sistema si tienes alguna pregunta."] }, "Related resources": { "v": ["Recursos relacionados"] } } }, { "l": "es-MX", "t": { "Anything shared with the same group of people will show up here": { "v": ["Todo lo que se comparta con el mismo grupo de personas se mostrará aquí"] }, "Error getting related resources. Please contact your system administrator if you have any questions.": { "v": ["Error al obtener recursos relacionados. Por favor contacte al administrador si tiene alguna pregunta."] }, "Related resources": { "v": ["Recursos relacionados"] } } }, { "l": "et-EE", "t": { "Anything shared with the same group of people will show up here": { "v": ["Siin kuvatakse kõik, mida jagatakse sama kasutajagrupiga"] }, "Error getting related resources. Please contact your system administrator if you have any questions.": { "v": ["Viga seotud ressursside saamisel. Küsimuste korral võtke ühendust oma süsteemiadministraatoriga."] }, "Related resources": { "v": ["Seotud ressursid"] } } }, { "l": "eu", "t": { "Anything shared with the same group of people will show up here": { "v": ["Pertsona-talde berarekin partekatutako edozer agertuko da hemen"] }, "Error getting related resources. Please contact your system administrator if you have any questions.": { "v": ["Errore bat gertatu da erlazionatutako baliabideak eskuratzean. Jarri harremanetan zure sistemaren administratzailearekin galderarik baduzu."] }, "Related resources": { "v": ["Erlazionatutako baliabideak"] } } }, { "l": "fa", "t": { "Anything shared with the same group of people will show up here": { "v": ["هر چیزی که با گروه مشابهی هم‌رسانی شود در این قسمت نمایش می‌یابد"] }, "Error getting related resources. Please contact your system administrator if you have any questions.": { "v": ["خطا در دریافت منابع مرتبط. لطفاً در صورت داشتن هر گونه سؤال با مدیر سیستم خود تماس بگیرید."] }, "Related resources": { "v": ["منابع مرتبط"] } } }, { "l": "fi", "t": { "Anything shared with the same group of people will show up here": { "v": ["Kaikki saman ryhmän kesken jaettu näkyy tässä"] }, "Error getting related resources. Please contact your system administrator if you have any questions.": { "v": ["Virhe resurssien haussa. Ota yhteyttä järjestelmän ylläpitäjään, mikäli sinulla on kysyttävää."] }, "Related resources": { "v": ["Liittyvät resurssit"] } } }, { "l": "fr", "t": { "Anything shared with the same group of people will show up here": { "v": ["Tout ce qui est partagé avec le même groupe de personnes apparaîtra ici"] }, "Error getting related resources. Please contact your system administrator if you have any questions.": { "v": ["Erreur lors de la récupération des ressources liées. Contactez votre administrateur système pour répondre à vos éventuelles questions."] }, "Related resources": { "v": ["Ressources liées"] } } }, { "l": "ga", "t": { "Anything shared with the same group of people will show up here": { "v": ["Taispeánfar aon rud a roinntear leis an ngrúpa céanna daoine anseo"] }, "Error getting related resources. Please contact your system administrator if you have any questions.": { "v": ["Earráid agus acmhainní gaolmhara á bhfáil. Déan teagmháil le riarthóir do chórais má tá aon cheist agat."] }, "Related resources": { "v": ["Acmhainní gaolmhara"] } } }, { "l": "gl", "t": { "Anything shared with the same group of people will show up here": { "v": ["Todo o que se comparta co mesmo grupo de persoas aparecerá aquí"] }, "Error getting related resources. Please contact your system administrator if you have any questions.": { "v": ["Produciuse un erro ao obter os recursos relacionados. Póñase en contacto coa administración do seu sistema se ten algunha dúbida."] }, "Related resources": { "v": ["Recursos relacionados"] } } }, { "l": "he", "t": { "Anything shared with the same group of people will show up here": { "v": ["כל מה שמשותף עם אותה קבוצת האנשים יופיע כאן"] }, "Error getting related resources. Please contact your system administrator if you have any questions.": { "v": ["שגיאה בקבלת המשאבים הקשורים. נא ליצור קשר עם הנהלת המערכת אם יש לך שאלות."] }, "Related resources": { "v": ["משאבים קשורים"] } } }, { "l": "hu", "t": { "Anything shared with the same group of people will show up here": { "v": ["Minden, amit ugyanazzal a csoporttal oszt meg, itt fog megjelenni"] }, "Related resources": { "v": ["Kapcsolódó erőforrások"] } } }, { "l": "id", "t": { "Anything shared with the same group of people will show up here": { "v": ["Apa pun yang dibagikan dengan grup orang yang sama akan muncul di sini"] }, "Error getting related resources. Please contact your system administrator if you have any questions.": { "v": ["Kesalahan saat mengambil sumber daya terkait. Hubungi administrator sistem Anda jika ada pertanyaan."] }, "Related resources": { "v": ["Sumber daya terkait"] } } }, { "l": "is", "t": { "Anything shared with the same group of people will show up here": { "v": ["Allt sem deilt er með sama hópi fólks mun birtast hér"] }, "Error getting related resources. Please contact your system administrator if you have any questions.": { "v": ["Villa við að sækja tengd tilföng. Hafðu samband við kerfisstjórann þinn ef þú ert með einhverjar spurningar."] }, "Related resources": { "v": ["Tengd tilföng"] } } }, { "l": "it", "t": { "Anything shared with the same group of people will show up here": { "v": ["Tutto ciò che è stato condiviso con lo stesso gruppo di persone viene visualizzato qui"] }, "Error getting related resources. Please contact your system administrator if you have any questions.": { "v": ["Errore nell'ottenere le risorse correlate. Per qualsiasi domanda, contattare l'amministratore di sistema."] }, "Related resources": { "v": ["Risorse correlate"] } } }, { "l": "ja", "t": { "Anything shared with the same group of people will show up here": { "v": ["同じグループで共有しているものは、全てここに表示されます"] }, "Error getting related resources. Please contact your system administrator if you have any questions.": { "v": ["関連リソースの取得エラー。ご不明な点がございましたら、システム管理者にお問い合わせください。"] }, "Related resources": { "v": ["関連リソース"] } } }, { "l": "ja-JP", "t": { "Anything shared with the same group of people will show up here": { "v": ["同じグループで共有しているものは、全てここに表示されます"] }, "Error getting related resources. Please contact your system administrator if you have any questions.": { "v": ["関連リソースの取得エラー。ご不明な点がございましたら、システム管理者にお問い合わせください。"] }, "Related resources": { "v": ["関連リソース"] } } }, { "l": "ko", "t": { "Anything shared with the same group of people will show up here": { "v": ["같은 그룹의 사용자와 공유된 모든 것들이 이곳에 나타납니다."] }, "Error getting related resources. Please contact your system administrator if you have any questions.": { "v": ["관련 리소스를 가져오는 중 오류가 발생했습니다. 궁금한 것이 있는 경우 시스템 관리자에게 연락해 주세요."] }, "Related resources": { "v": ["관련 리소스"] } } }, { "l": "lo", "t": { "Anything shared with the same group of people will show up here": { "v": ["ທຸກຢ່າງທີ່ແບ່ງປັນກັບກຸ່ມຄົນດຽວກັນຈະສະແດງຢູ່ບ່ອນນີ້"] }, "Error getting related resources. Please contact your system administrator if you have any questions.": { "v": ["ເກີດຂໍ້ຜິດພາດໃນການເອີ້ນຂໍ້ມູນຊັບພະຍາກອນທີ່ກ່ຽວຂ້ອງ. ກະລຸນາຕິດຕໍ່ຜູ້ເບິ່ງແຍງລະບົບຂອງທ່ານ ຖ້າທ່ານມີຄຳຖາມ."] }, "Related resources": { "v": ["ຊັບພະຍາກອນທີ່ກ່ຽວຂ້ອງ"] } } }, { "l": "lt-LT", "t": {} }, { "l": "lv", "t": {} }, { "l": "mk", "t": { "Anything shared with the same group of people will show up here": { "v": ["Сè што е споделено со истата група луѓе ќе се појави овде"] }, "Error getting related resources. Please contact your system administrator if you have any questions.": { "v": ["Грешка при добивање поврзани ресурси. Ве молиме контактирајте го вашиот систем администратор ако имате прашања."] }, "Related resources": { "v": ["Поврзани ресурси"] } } }, { "l": "my", "t": {} }, { "l": "nb", "t": { "Anything shared with the same group of people will show up here": { "v": ["Alt som er delt med den samme gruppen vil vises her"] }, "Error getting related resources. Please contact your system administrator if you have any questions.": { "v": ["Feil ved henting av relaterte ressurser. Kontakt systemansvarlig hvis du har spørsmål."] }, "Related resources": { "v": ["Relaterte ressurser"] } } }, { "l": "nl", "t": { "Anything shared with the same group of people will show up here": { "v": ["Alles dat gedeeld is met dezelfde groep mensen zal hier getoond worden"] }, "Error getting related resources. Please contact your system administrator if you have any questions.": { "v": ["Fout bij het ophalen van gerelateerde bronnen. Neem contact op met uw systeembeheerder als u vragen heeft."] }, "Related resources": { "v": ["Gerelateerde bronnen"] } } }, { "l": "oc", "t": {} }, { "l": "pl", "t": { "Anything shared with the same group of people will show up here": { "v": ["Tutaj pojawi się wszystko, co zostało udostępnione tej samej grupie osób"] }, "Error getting related resources. Please contact your system administrator if you have any questions.": { "v": ["Błąd podczas pobierania powiązanych zasobów. Jeśli masz jakiekolwiek pytania, skontaktuj się z administratorem systemu."] }, "Related resources": { "v": ["Powiązane zasoby"] } } }, { "l": "pt-BR", "t": { "Anything shared with the same group of people will show up here": { "v": ["Qualquer coisa compartilhada com o mesmo grupo de pessoas aparecerá aqui"] }, "Error getting related resources. Please contact your system administrator if you have any questions.": { "v": ["Erro ao obter recursos relacionados. Por favor, entre em contato com o administrador do sistema se tiver alguma dúvida."] }, "Related resources": { "v": ["Recursos relacionados"] } } }, { "l": "pt-PT", "t": { "Anything shared with the same group of people will show up here": { "v": ["Qualquer coisa partilhada com o mesmo grupo de pessoas irá aparecer aqui"] }, "Error getting related resources. Please contact your system administrator if you have any questions.": { "v": ["Erro ao obter os recursos relacionados. Por favor, contacte o administrador do sistema se tiver quaisquer  perguntas."] }, "Related resources": { "v": ["Recursos relacionados"] } } }, { "l": "ro", "t": { "Anything shared with the same group of people will show up here": { "v": ["Tot ceea ce este partajat cu același grup de persoane va fi afișat aici"] }, "Error getting related resources. Please contact your system administrator if you have any questions.": { "v": ["Eroare la preluarea resurselor adiționale. Vă rugăm să contactați administratorul pentru întrebări."] }, "Related resources": { "v": ["Resurse legate"] } } }, { "l": "ru", "t": { "Anything shared with the same group of people will show up here": { "v": ["Всё, чем поделились с той же группой людей, будет отображаться здесь"] }, "Error getting related resources. Please contact your system administrator if you have any questions.": { "v": ["Ошибка при получении связанных ресурсов. Если у вас есть какие-либо вопросы, обратитесь к системному администратору."] }, "Related resources": { "v": ["Связанные ресурсы"] } } }, { "l": "sk", "t": { "Anything shared with the same group of people will show up here": { "v": ["Tu sa zobrazí čokoľvek zdieľané s rovnakou skupinou ľudí"] }, "Error getting related resources. Please contact your system administrator if you have any questions.": { "v": ["Chyba pri získavaní súvisiacich zdrojov. V prípade otázok kontaktujte prosím svojho systemového administrátora."] }, "Related resources": { "v": ["Súvisiace zdroje"] } } }, { "l": "sl", "t": { "Related resources": { "v": ["Povezani viri"] } } }, { "l": "sr", "t": { "Anything shared with the same group of people will show up here": { "v": ["Све што се дели са истом групом људи ће се појавити овде"] }, "Error getting related resources. Please contact your system administrator if you have any questions.": { "v": ["Грешка код прибављања везаних ресурса. Молимо вас да се обратите администратору ако имате питања."] }, "Related resources": { "v": ["Повезани ресурси"] } } }, { "l": "sv", "t": { "Anything shared with the same group of people will show up here": { "v": ["Något som delats med samma grupp av personer kommer att visas här"] }, "Error getting related resources. Please contact your system administrator if you have any questions.": { "v": ["Det gick inte att hämta relaterade resurser. Kontakta din systemadministratör om du har några frågor."] }, "Related resources": { "v": ["Relaterade resurser"] } } }, { "l": "tr", "t": { "Anything shared with the same group of people will show up here": { "v": ["Aynı kişi grubu ile paylaşılan herşey burada görüntülenir"] }, "Error getting related resources. Please contact your system administrator if you have any questions.": { "v": ["İlgili kaynaklara ulaşılırken sorun çıktı. Herhangi bir sorunuz varsa lütfen sistem yöneticiniz ile görüşün "] }, "Related resources": { "v": ["İlgili kaynaklar"] } } }, { "l": "uk", "t": { "Anything shared with the same group of people will show up here": { "v": ["Будь-що доступне для цієї же групи людей буде показано тут"] }, "Error getting related resources. Please contact your system administrator if you have any questions.": { "v": ["Помилка під час отримання пов'язаних ресурсів. Будь ласка, сконтактуйте з системним адміністратором, якщо у вас виникли запитання."] }, "Related resources": { "v": ["Пов'язані ресурси"] } } }, { "l": "uz", "t": { "Anything shared with the same group of people will show up here": { "v": ["Xuddi shu guruhdagi odamlarga ulashilgan hamma narsa shu yerda chiqadi"] }, "Error getting related resources. Please contact your system administrator if you have any questions.": { "v": ["Tegishli manbalarni olishda xatolik yuz berdi. Savollaringiz bo'lsa, tizim administratoriga murojaat qiling."] }, "Related resources": { "v": ["Tegishli manbalar"] } } }, { "l": "zh-CN", "t": { "Anything shared with the same group of people will show up here": { "v": ["与同组用户分享的所有内容都会显示于此"] }, "Error getting related resources. Please contact your system administrator if you have any questions.": { "v": ["获取相关资源出现错误。如果你有任何问题，请联系系统管理员。"] }, "Related resources": { "v": ["相关资源"] } } }, { "l": "zh-HK", "t": { "Anything shared with the same group of people will show up here": { "v": ["與同一組人共享的任何內容都會顯示在此處"] }, "Error getting related resources. Please contact your system administrator if you have any questions.": { "v": ["取得相關資源時發生錯誤。如果有任何問題，請聯絡系統管理員。"] }, "Related resources": { "v": ["相關資源"] } } }, { "l": "zh-TW", "t": { "Anything shared with the same group of people will show up here": { "v": ["與相同群組分享的所有內容都會顯示於此"] }, "Error getting related resources. Please contact your system administrator if you have any questions.": { "v": ["取得相關資源時發生錯誤。如果有任何問題，請聯絡系統管理員。"] }, "Related resources": { "v": ["相關資源"] } } }];
const t10 = [{ "l": "ar", "t": { "Avatar of {displayName}": { "v": ["صورة الملف الشخصي الرمزية لــ {displayName}  "] }, "Avatar of {displayName}, {status}": { "v": ["صورة الملف الشخصي الرمزية لــ {displayName}، {status}"] } } }, { "l": "ast", "t": { "Avatar of {displayName}": { "v": ["Avatar de: {displayName}"] }, "Avatar of {displayName}, {status}": { "v": ["Avatar de: {displayName}, {status}"] } } }, { "l": "br", "t": {} }, { "l": "ca", "t": { "Avatar of {displayName}": { "v": ["Avatar de {displayName}"] }, "Avatar of {displayName}, {status}": { "v": ["Avatar de {displayName}, {status}"] } } }, { "l": "cs", "t": { "Avatar of {displayName}": { "v": ["Zástupný obrázek uživatele {displayName}"] }, "Avatar of {displayName}, {status}": { "v": ["Zástupný obrázek uživatele {displayName}, {status}"] } } }, { "l": "cs-CZ", "t": { "Avatar of {displayName}": { "v": ["Zástupný obrázek uživatele {displayName}"] }, "Avatar of {displayName}, {status}": { "v": ["Zástupný obrázek uživatele {displayName}, {status}"] } } }, { "l": "da", "t": { "Avatar of {displayName}": { "v": ["Avatar af {displayName}"] }, "Avatar of {displayName}, {status}": { "v": ["Avatar af {displayName}, {status}"] } } }, { "l": "de", "t": { "Avatar of {displayName}": { "v": ["Avatar von {displayName}"] }, "Avatar of {displayName}, {status}": { "v": ["Avatar von {displayName}, {status}"] } } }, { "l": "de-DE", "t": { "Avatar of {displayName}": { "v": ["Avatar von {displayName}"] }, "Avatar of {displayName}, {status}": { "v": ["Avatar von {displayName}, {status}"] } } }, { "l": "el", "t": { "Avatar of {displayName}": { "v": ["Άβαταρ του {displayName}"] }, "Avatar of {displayName}, {status}": { "v": ["Άβαταρ του {displayName}, {status}"] } } }, { "l": "en-GB", "t": { "Avatar of {displayName}": { "v": ["Avatar of {displayName}"] }, "Avatar of {displayName}, {status}": { "v": ["Avatar of {displayName}, {status}"] } } }, { "l": "eo", "t": {} }, { "l": "es", "t": { "Avatar of {displayName}": { "v": ["Avatar de {displayName}"] }, "Avatar of {displayName}, {status}": { "v": ["Avatar de {displayName}, {status}"] } } }, { "l": "es-AR", "t": { "Avatar of {displayName}": { "v": ["Avatar de {displayName}"] }, "Avatar of {displayName}, {status}": { "v": ["Avatar de {displayName}, {status}"] } } }, { "l": "es-EC", "t": { "Avatar of {displayName}": { "v": ["Avatar de {displayName}"] }, "Avatar of {displayName}, {status}": { "v": ["Avatar de {displayName}, {status}"] } } }, { "l": "es-MX", "t": { "Avatar of {displayName}": { "v": ["Avatar de {displayName}"] }, "Avatar of {displayName}, {status}": { "v": ["Avatar de {displayName}, {status}"] } } }, { "l": "et-EE", "t": { "Avatar of {displayName}": { "v": ["Avatar {displayName}"] }, "Avatar of {displayName}, {status}": { "v": ["Avatar {displayName}, {status}"] } } }, { "l": "eu", "t": { "Avatar of {displayName}": { "v": ["{displayName}-(e)n irudia"] }, "Avatar of {displayName}, {status}": { "v": ["{displayName} -(e)n irudia, {status}"] } } }, { "l": "fa", "t": { "Avatar of {displayName}": { "v": ["آواتار {displayName}"] }, "Avatar of {displayName}, {status}": { "v": ["آواتار {displayName} ، {status}"] } } }, { "l": "fi", "t": { "Avatar of {displayName}": { "v": ["{displayName}n avatar"] }, "Avatar of {displayName}, {status}": { "v": ["{displayName}n avatar, {status}"] } } }, { "l": "fr", "t": { "Avatar of {displayName}": { "v": ["Avatar de {displayName}"] }, "Avatar of {displayName}, {status}": { "v": ["Avatar de {displayName}, {status}"] } } }, { "l": "ga", "t": { "Avatar of {displayName}": { "v": ["Avatar de {displayName}"] }, "Avatar of {displayName}, {status}": { "v": ["Avatar de {displayName}, {status}"] } } }, { "l": "gl", "t": { "Avatar of {displayName}": { "v": ["Avatar de {displayName}"] }, "Avatar of {displayName}, {status}": { "v": ["Avatar de {displayName}, {status}"] } } }, { "l": "he", "t": { "Avatar of {displayName}": { "v": ["תמונה ייצוגית של {displayName}"] }, "Avatar of {displayName}, {status}": { "v": ["תמונה ייצוגית של {displayName}, {status}"] } } }, { "l": "hu", "t": { "Avatar of {displayName}": { "v": ["{displayName} profilképe"] }, "Avatar of {displayName}, {status}": { "v": ["{displayName} profilképe, {status}"] } } }, { "l": "id", "t": { "Avatar of {displayName}": { "v": ["Avatar {displayName}"] }, "Avatar of {displayName}, {status}": { "v": ["Avatar {displayName}, {status}"] } } }, { "l": "is", "t": { "Avatar of {displayName}": { "v": ["Auðkennismynd fyrir {displayName}"] }, "Avatar of {displayName}, {status}": { "v": ["Auðkennismynd fyrir {displayName}, {status}"] } } }, { "l": "it", "t": { "Avatar of {displayName}": { "v": ["Avatar di {displayName}"] }, "Avatar of {displayName}, {status}": { "v": ["Avatar di {displayName}, {status}"] } } }, { "l": "ja", "t": { "Avatar of {displayName}": { "v": ["{displayName} のアバター"] }, "Avatar of {displayName}, {status}": { "v": ["{displayName}, {status} のアバター"] } } }, { "l": "ja-JP", "t": { "Avatar of {displayName}": { "v": ["{displayName} のアバター"] }, "Avatar of {displayName}, {status}": { "v": ["{displayName}, {status} のアバター"] } } }, { "l": "ko", "t": { "Avatar of {displayName}": { "v": ["{displayName}님의 아바타"] }, "Avatar of {displayName}, {status}": { "v": ["{displayName}, {status}님의 아바타"] } } }, { "l": "lo", "t": { "Avatar of {displayName}": { "v": ["ຮູບແທນຕົວຂອງ {displayName}"] }, "Avatar of {displayName}, {status}": { "v": ["ຮູບແທນຕົວຂອງ {displayName}, {status}"] } } }, { "l": "lt-LT", "t": {} }, { "l": "lv", "t": {} }, { "l": "mk", "t": { "Avatar of {displayName}": { "v": ["Аватар на {displayName}"] }, "Avatar of {displayName}, {status}": { "v": ["Аватар на {displayName}, {status}"] } } }, { "l": "my", "t": { "Avatar of {displayName}": { "v": ["{displayName} ၏ ကိုယ်ပွား"] } } }, { "l": "nb", "t": { "Avatar of {displayName}": { "v": ["Avataren til {displayName}"] }, "Avatar of {displayName}, {status}": { "v": ["{displayName}'s avatar, {status}"] } } }, { "l": "nl", "t": { "Avatar of {displayName}": { "v": ["Avatar van {displayName}"] }, "Avatar of {displayName}, {status}": { "v": ["Avatar van {displayName}, {status}"] } } }, { "l": "oc", "t": {} }, { "l": "pl", "t": { "Avatar of {displayName}": { "v": ["Awatar {displayName}"] }, "Avatar of {displayName}, {status}": { "v": ["Awatar {displayName}, {status}"] } } }, { "l": "pt-BR", "t": { "Avatar of {displayName}": { "v": ["Avatar de {displayName}"] }, "Avatar of {displayName}, {status}": { "v": ["Avatar de {displayName}, {status}"] } } }, { "l": "pt-PT", "t": { "Avatar of {displayName}": { "v": ["Avatar de {displayName}"] }, "Avatar of {displayName}, {status}": { "v": ["Avatar de {displayName}, {status}"] } } }, { "l": "ro", "t": { "Avatar of {displayName}": { "v": ["Avatarul lui {displayName}"] }, "Avatar of {displayName}, {status}": { "v": ["Avatarul lui {displayName}, {status}"] } } }, { "l": "ru", "t": { "Avatar of {displayName}": { "v": ["Аватар {displayName}"] }, "Avatar of {displayName}, {status}": { "v": ["Фотография {displayName}, {status}"] } } }, { "l": "sk", "t": { "Avatar of {displayName}": { "v": ["Avatar {displayName}"] }, "Avatar of {displayName}, {status}": { "v": ["Avatar {displayName}, {status}"] } } }, { "l": "sl", "t": { "Avatar of {displayName}": { "v": ["Podoba {displayName}"] }, "Avatar of {displayName}, {status}": { "v": ["Prikazna slika {displayName}, {status}"] } } }, { "l": "sr", "t": { "Avatar of {displayName}": { "v": ["Аватар за {displayName}"] }, "Avatar of {displayName}, {status}": { "v": ["Avatar za {displayName}, {status}"] } } }, { "l": "sv", "t": { "Avatar of {displayName}": { "v": ["{displayName}s avatar"] }, "Avatar of {displayName}, {status}": { "v": ["{displayName}s avatar, {status}"] } } }, { "l": "tr", "t": { "Avatar of {displayName}": { "v": ["{displayName} avatarı"] }, "Avatar of {displayName}, {status}": { "v": ["{displayName}, {status} avatarı"] } } }, { "l": "uk", "t": { "Avatar of {displayName}": { "v": ["Аватар {displayName}"] }, "Avatar of {displayName}, {status}": { "v": ["Аватар {displayName}, {status}"] } } }, { "l": "uz", "t": { "Avatar of {displayName}": { "v": [" {displayName}Avatari"] }, "Avatar of {displayName}, {status}": { "v": ["{displayName}, {status} Avatari"] } } }, { "l": "zh-CN", "t": { "Avatar of {displayName}": { "v": ["{displayName}的头像"] }, "Avatar of {displayName}, {status}": { "v": ["{displayName}的头像，{status}"] } } }, { "l": "zh-HK", "t": { "Avatar of {displayName}": { "v": ["{displayName} 的頭像"] }, "Avatar of {displayName}, {status}": { "v": ["{displayName} 的頭像，{status}"] } } }, { "l": "zh-TW", "t": { "Avatar of {displayName}": { "v": ["{displayName} 的大頭照"] }, "Avatar of {displayName}, {status}": { "v": ["{displayName}, {status} 的大頭照"] } } }];
const t11 = [{ "l": "ar", "t": { "away": { "v": ["غير موجود"] }, "busy": { "v": ["مشغول"] }, "do not disturb": { "v": ["يُرجى عدم الإزعاج"] }, "invisible": { "v": ["غير مرئي"] }, "offline": { "v": ["غير متصل"] }, "online": { "v": ["متصل"] } } }, { "l": "ast", "t": { "away": { "v": ["ausente"] }, "busy": { "v": ["ocupáu"] }, "do not disturb": { "v": ["nun molestar"] }, "invisible": { "v": ["invisible"] }, "offline": { "v": ["desconectáu"] }, "online": { "v": ["en llinia"] } } }, { "l": "br", "t": {} }, { "l": "ca", "t": {} }, { "l": "cs", "t": { "away": { "v": ["pryč"] }, "busy": { "v": ["zaneprádněn(a)"] }, "do not disturb": { "v": ["nerušit"] }, "invisible": { "v": ["neviditelné"] }, "offline": { "v": ["offline"] }, "online": { "v": ["online"] } } }, { "l": "cs-CZ", "t": { "away": { "v": ["pryč"] }, "busy": { "v": ["zaneprádněn(a)"] }, "do not disturb": { "v": ["nerušit"] }, "invisible": { "v": ["neviditelné"] }, "offline": { "v": ["offline"] }, "online": { "v": ["online"] } } }, { "l": "da", "t": { "away": { "v": ["væk"] }, "busy": { "v": ["optaget"] }, "do not disturb": { "v": ["forstyr ikke"] }, "invisible": { "v": ["usynlig"] }, "offline": { "v": ["offline"] }, "online": { "v": ["online"] } } }, { "l": "de", "t": { "away": { "v": ["Abwesend"] }, "busy": { "v": ["Beschäftigt"] }, "do not disturb": { "v": ["Bitte nicht stören"] }, "invisible": { "v": ["Unsichtbar"] }, "offline": { "v": ["Offline"] }, "online": { "v": ["Online"] } } }, { "l": "de-DE", "t": { "away": { "v": ["Abwesend"] }, "busy": { "v": ["Beschäftigt"] }, "do not disturb": { "v": ["Bitte nicht stören"] }, "invisible": { "v": ["Unsichtbar"] }, "offline": { "v": ["Offline"] }, "online": { "v": ["Online"] } } }, { "l": "el", "t": { "away": { "v": ["μακριά"] }, "busy": { "v": ["απασχολημένος"] }, "do not disturb": { "v": ["μην ενοχλείτε"] }, "invisible": { "v": ["αόρατο"] }, "offline": { "v": ["εκτός σύνδεσης"] }, "online": { "v": ["συνδεδεμένος"] } } }, { "l": "en-GB", "t": { "away": { "v": ["away"] }, "busy": { "v": ["busy"] }, "do not disturb": { "v": ["do not disturb"] }, "invisible": { "v": ["invisible"] }, "offline": { "v": ["offline"] }, "online": { "v": ["online"] } } }, { "l": "eo", "t": {} }, { "l": "es", "t": { "away": { "v": ["ausente"] }, "busy": { "v": ["ocupado"] }, "do not disturb": { "v": ["no molestar"] }, "invisible": { "v": ["invisible"] }, "offline": { "v": ["fuera de línea"] }, "online": { "v": ["en línea"] } } }, { "l": "es-AR", "t": { "away": { "v": ["ausente"] }, "busy": { "v": ["ocupado"] }, "do not disturb": { "v": ["no molestar"] }, "invisible": { "v": ["invisible"] }, "offline": { "v": ["desconectado"] }, "online": { "v": ["en línea"] } } }, { "l": "es-EC", "t": {} }, { "l": "es-MX", "t": { "away": { "v": ["ausente"] }, "busy": { "v": ["ocupado"] }, "do not disturb": { "v": ["no molestar"] }, "invisible": { "v": ["invisible"] }, "offline": { "v": ["fuera de línea"] }, "online": { "v": ["en línea"] } } }, { "l": "et-EE", "t": { "away": { "v": ["eemal"] }, "busy": { "v": ["hõivatud"] }, "do not disturb": { "v": ["ära sega"] }, "invisible": { "v": ["nähtamatu"] }, "offline": { "v": ["pole võrgus"] }, "online": { "v": ["võrgus"] } } }, { "l": "eu", "t": {} }, { "l": "fa", "t": { "away": { "v": ["دور از دستگاه"] }, "busy": { "v": ["مشغول"] }, "do not disturb": { "v": ["مزاحم نشوید"] }, "invisible": { "v": ["مخفی"] }, "offline": { "v": ["برون‌خط"] }, "online": { "v": ["برخط"] } } }, { "l": "fi", "t": { "away": { "v": ["poissa"] }, "busy": { "v": ["varattu"] }, "do not disturb": { "v": ["älä häiritse"] }, "invisible": { "v": ["näkymätön"] }, "offline": { "v": ["ei linjalla"] }, "online": { "v": ["linjalla"] } } }, { "l": "fr", "t": { "away": { "v": ["absent"] }, "busy": { "v": ["occupé"] }, "do not disturb": { "v": ["ne pas déranger"] }, "invisible": { "v": ["invisible"] }, "offline": { "v": ["hors ligne"] }, "online": { "v": ["en ligne"] } } }, { "l": "ga", "t": { "away": { "v": ["ar shiúl"] }, "busy": { "v": ["gnóthach"] }, "do not disturb": { "v": ["ná cur as"] }, "invisible": { "v": ["dofheicthe"] }, "offline": { "v": ["as líne"] }, "online": { "v": ["ar líne"] } } }, { "l": "gl", "t": { "away": { "v": ["ausente"] }, "busy": { "v": ["ocupado"] }, "do not disturb": { "v": ["non molestar"] }, "invisible": { "v": ["invisíbel"] }, "offline": { "v": ["desconectado"] }, "online": { "v": ["conectado"] } } }, { "l": "he", "t": {} }, { "l": "hu", "t": {} }, { "l": "id", "t": { "away": { "v": ["tidak tersedia"] }, "do not disturb": { "v": ["jangan ganggu"] }, "offline": { "v": ["luring"] }, "online": { "v": ["daring"] } } }, { "l": "is", "t": { "away": { "v": ["í burtu"] }, "busy": { "v": ["upptekin/n"] }, "do not disturb": { "v": ["ekki ónáða"] }, "invisible": { "v": ["ósýnilegt"] }, "offline": { "v": ["ónettengt"] }, "online": { "v": ["nettengt"] } } }, { "l": "it", "t": { "away": { "v": ["via"] }, "do not disturb": { "v": ["non disturbare"] }, "offline": { "v": ["offline"] }, "online": { "v": ["online"] } } }, { "l": "ja", "t": { "away": { "v": ["離れる"] }, "busy": { "v": ["ビジー"] }, "do not disturb": { "v": ["邪魔をしないでください"] }, "invisible": { "v": ["不可視"] }, "offline": { "v": ["オフライン"] }, "online": { "v": ["オンライン"] } } }, { "l": "ja-JP", "t": { "away": { "v": ["離れる"] }, "busy": { "v": ["ビジー"] }, "do not disturb": { "v": ["邪魔をしないでください"] }, "invisible": { "v": ["不可視"] }, "offline": { "v": ["オフライン"] }, "online": { "v": ["オンライン"] } } }, { "l": "ko", "t": { "away": { "v": ["자리 비움"] }, "busy": { "v": ["바쁨"] }, "do not disturb": { "v": ["방해 금지"] }, "invisible": { "v": ["보이지 않음"] }, "offline": { "v": ["오프라인"] }, "online": { "v": ["온라인"] } } }, { "l": "lo", "t": { "away": { "v": ["ບໍ່ຢູ່"] }, "busy": { "v": ["ບໍ່ວ່າງ"] }, "do not disturb": { "v": ["ຫ້າມລົບກວນ"] }, "invisible": { "v": ["ບໍ່ສະແດງ"] }, "offline": { "v": ["ອອບໄລນ໌"] }, "online": { "v": ["ອອນໄລນ໌"] } } }, { "l": "lt-LT", "t": {} }, { "l": "lv", "t": {} }, { "l": "mk", "t": { "away": { "v": ["оддалечен"] }, "busy": { "v": ["зафатен"] }, "do not disturb": { "v": ["не вознемирувај"] }, "invisible": { "v": ["невидливо"] }, "offline": { "v": ["офлајн"] }, "online": { "v": ["онлајн"] } } }, { "l": "my", "t": {} }, { "l": "nb", "t": { "away": { "v": ["borte"] }, "busy": { "v": ["opptatt"] }, "do not disturb": { "v": ["ikke forstyrr"] }, "invisible": { "v": ["usynlig"] }, "offline": { "v": ["frakoblet"] }, "online": { "v": ["tilkoblet"] } } }, { "l": "nl", "t": { "away": { "v": ["weg"] }, "busy": { "v": ["bezig"] }, "do not disturb": { "v": ["niet storen"] }, "invisible": { "v": ["Onzichtbaar"] }, "offline": { "v": ["offline"] }, "online": { "v": ["online"] } } }, { "l": "oc", "t": {} }, { "l": "pl", "t": { "away": { "v": ["stąd"] }, "busy": { "v": ["zajęty"] }, "do not disturb": { "v": ["nie przeszkadzać"] }, "invisible": { "v": ["niewidzialny"] }, "offline": { "v": ["offline"] }, "online": { "v": ["online"] } } }, { "l": "pt-BR", "t": { "away": { "v": ["ausente"] }, "busy": { "v": ["ocupado"] }, "do not disturb": { "v": ["não perturbe"] }, "invisible": { "v": ["invisível"] }, "offline": { "v": ["off-line"] }, "online": { "v": ["on-line"] } } }, { "l": "pt-PT", "t": { "away": { "v": ["longe"] }, "busy": { "v": ["ocupado"] }, "do not disturb": { "v": ["não incomodar"] }, "invisible": { "v": ["invisível"] }, "offline": { "v": ["offline"] }, "online": { "v": ["online"] } } }, { "l": "ro", "t": { "away": { "v": ["plecat"] }, "do not disturb": { "v": ["nu deranjați"] }, "offline": { "v": ["deconectat"] }, "online": { "v": ["online"] } } }, { "l": "ru", "t": { "away": { "v": ["отсутствие"] }, "busy": { "v": ["занятый"] }, "do not disturb": { "v": ["не беспокоить"] }, "invisible": { "v": ["невидимый"] }, "offline": { "v": ["офлайн"] }, "online": { "v": ["онлайн"] } } }, { "l": "sk", "t": { "away": { "v": ["neprítomný"] }, "busy": { "v": ["zaneprázdnený"] }, "do not disturb": { "v": ["nerušiť"] }, "invisible": { "v": ["neviditeľný"] }, "offline": { "v": ["Odpojený - offline"] }, "online": { "v": ["Pripojený - online"] } } }, { "l": "sl", "t": {} }, { "l": "sr", "t": { "away": { "v": ["одсутан"] }, "busy": { "v": ["заузет"] }, "do not disturb": { "v": ["не узнемиравај"] }, "invisible": { "v": ["невидљиво"] }, "offline": { "v": ["ван мреже"] }, "online": { "v": ["на мрежи"] } } }, { "l": "sv", "t": { "away": { "v": ["borta"] }, "busy": { "v": ["upptagen"] }, "do not disturb": { "v": ["stör ej"] }, "invisible": { "v": ["osynlig"] }, "offline": { "v": ["offline"] }, "online": { "v": ["online"] } } }, { "l": "tr", "t": { "away": { "v": ["Uzakta"] }, "busy": { "v": ["Meşgul"] }, "do not disturb": { "v": ["Rahatsız etmeyin"] }, "invisible": { "v": ["görünmez"] }, "offline": { "v": ["Çevrim dışı"] }, "online": { "v": ["Çevrim içi"] } } }, { "l": "uk", "t": { "away": { "v": ["відсутній"] }, "busy": { "v": ["зайнято"] }, "do not disturb": { "v": ["не турбувати"] }, "invisible": { "v": ["Невидимий"] }, "offline": { "v": ["не в мережі"] }, "online": { "v": ["в мережі"] } } }, { "l": "uz", "t": { "away": { "v": ["uzoqda"] }, "busy": { "v": ["band"] }, "do not disturb": { "v": ["bezovta qilmang"] }, "invisible": { "v": ["ko'rinmas"] }, "offline": { "v": ["offline"] }, "online": { "v": ["online"] } } }, { "l": "zh-CN", "t": { "away": { "v": ["离开"] }, "busy": { "v": ["繁忙"] }, "do not disturb": { "v": ["请勿打扰"] }, "invisible": { "v": ["隐藏的"] }, "offline": { "v": ["离线"] }, "online": { "v": ["在线"] } } }, { "l": "zh-HK", "t": { "away": { "v": ["離開"] }, "busy": { "v": ["忙碌"] }, "do not disturb": { "v": ["請勿打擾"] }, "invisible": { "v": ["隐藏的"] }, "offline": { "v": ["離線"] }, "online": { "v": ["在線"] } } }, { "l": "zh-TW", "t": { "away": { "v": ["離開"] }, "busy": { "v": ["忙碌"] }, "do not disturb": { "v": ["請勿打擾"] }, "invisible": { "v": ["不可見"] }, "offline": { "v": ["離線"] }, "online": { "v": ["線上"] } } }];
const t12 = [{ "l": "ar", "t": { "Back to provider selection": { "v": ["عودة إلى اختيار المزوّد"] }, "Close Smart Picker": { "v": ["إغلاق المحدد الذكي"] }, "Smart Picker": { "v": ["اللاقط الذكي smart picker"] } } }, { "l": "ast", "t": { "Back to provider selection": { "v": ["Volver a la seleición de fornidores"] }, "Close Smart Picker": { "v": ["Zarrar la seleición intelixente"] }, "Smart Picker": { "v": ["Selector intelixente"] } } }, { "l": "br", "t": {} }, { "l": "ca", "t": {} }, { "l": "cs", "t": { "Back to provider selection": { "v": ["Zpět na výběr poskytovatele"] }, "Close Smart Picker": { "v": ["Zavřít inteligentní výběr"] }, "Smart Picker": { "v": ["Inteligentní výběr"] } } }, { "l": "cs-CZ", "t": { "Back to provider selection": { "v": ["Zpět na výběr poskytovatele"] }, "Close Smart Picker": { "v": ["Zavřít inteligentní výběr"] }, "Smart Picker": { "v": ["Inteligentní výběr"] } } }, { "l": "da", "t": { "Back to provider selection": { "v": ["Tilbage til udbydervalg"] }, "Close Smart Picker": { "v": ["Luk Smart Vælger"] }, "Smart Picker": { "v": ["Smart Vælger"] } } }, { "l": "de", "t": { "Back to provider selection": { "v": ["Zurück zur Anbieterauswahl"] }, "Close Smart Picker": { "v": ["Smart Picker schließen"] }, "Smart Picker": { "v": ["Smart Picker"] } } }, { "l": "de-DE", "t": { "Back to provider selection": { "v": ["Zurück zur Anbieterauswahl"] }, "Close Smart Picker": { "v": ["Smart Picker schließen"] }, "Smart Picker": { "v": ["Smart Picker"] } } }, { "l": "el", "t": { "Back to provider selection": { "v": ["Επιστροφή στην επιλογή παρόχου"] }, "Close Smart Picker": { "v": ["Κλείσιμο Έξυπνης Επιλογής"] }, "Smart Picker": { "v": ["Έξυπνη Επιλογή"] } } }, { "l": "en-GB", "t": { "Back to provider selection": { "v": ["Back to provider selection"] }, "Close Smart Picker": { "v": ["Close Smart Picker"] }, "Smart Picker": { "v": ["Smart Picker"] } } }, { "l": "eo", "t": {} }, { "l": "es", "t": { "Back to provider selection": { "v": ["Volver a la selección de proveedor"] }, "Close Smart Picker": { "v": ["Cerrar selector inteligente"] }, "Smart Picker": { "v": ["Selector inteligente"] } } }, { "l": "es-AR", "t": { "Back to provider selection": { "v": ["Volver a la selección de proveedor"] }, "Close Smart Picker": { "v": ["Cerrar selector inteligente"] }, "Smart Picker": { "v": ["Selector inteligente"] } } }, { "l": "es-EC", "t": { "Back to provider selection": { "v": ["Volver a la selección de proveedor"] }, "Close Smart Picker": { "v": ["Cerrar selector inteligente"] }, "Smart Picker": { "v": ["Selector inteligente"] } } }, { "l": "es-MX", "t": { "Back to provider selection": { "v": ["Volver a la selección de proveedor"] }, "Close Smart Picker": { "v": ["Cerrar selector inteligente"] }, "Smart Picker": { "v": ["Selector inteligente"] } } }, { "l": "et-EE", "t": { "Back to provider selection": { "v": ["Tagasi teenusepakkuja valiku juurde"] }, "Close Smart Picker": { "v": ["Sulge nutikas valija"] }, "Smart Picker": { "v": ["Nutikas valija"] } } }, { "l": "eu", "t": { "Back to provider selection": { "v": ["Itzuli hornitzaileen hautapenera"] }, "Close Smart Picker": { "v": ["Itxi hautatzaile adimenduna"] }, "Smart Picker": { "v": ["Hautatzaile adimenduna"] } } }, { "l": "fa", "t": { "Back to provider selection": { "v": ["بازگشت به انتخاب ارائه دهنده"] }, "Close Smart Picker": { "v": ["بستن انتخاب‌گر هوشمند"] }, "Smart Picker": { "v": ["انتخابگر هوشمند"] } } }, { "l": "fi", "t": { "Back to provider selection": { "v": ["Takaisin toimittajavalintaan"] }, "Close Smart Picker": { "v": ["Sulje älykas valitsin"] }, "Smart Picker": { "v": ["Älykäs valitsin"] } } }, { "l": "fr", "t": { "Back to provider selection": { "v": ["Revenir à la sélection du fournisseur"] }, "Close Smart Picker": { "v": ["Fermer le sélecteur intelligent"] }, "Smart Picker": { "v": ["Sélecteur intelligent"] } } }, { "l": "ga", "t": { "Back to provider selection": { "v": ["Ar ais go roghnú soláthróra"] }, "Close Smart Picker": { "v": ["Dún Piocálaí Cliste"] }, "Smart Picker": { "v": ["Roghnóir Cliste"] } } }, { "l": "gl", "t": { "Back to provider selection": { "v": ["Volver á selección do provedor"] }, "Close Smart Picker": { "v": ["Pechar o Selector intelixente"] }, "Smart Picker": { "v": ["Selector intelixente"] } } }, { "l": "he", "t": { "Back to provider selection": { "v": ["חזרה לבחירת ספק"] }, "Close Smart Picker": { "v": ["סגירת הבורר החכם"] }, "Smart Picker": { "v": ["בורר חכם"] } } }, { "l": "hu", "t": {} }, { "l": "id", "t": { "Back to provider selection": { "v": ["Kembali ke pemilihan penyedia"] }, "Close Smart Picker": { "v": ["Tutup Pemilih Cerdas"] }, "Smart Picker": { "v": ["Pemilih Cerdas"] } } }, { "l": "is", "t": { "Back to provider selection": { "v": ["Til baka í val á þjónustuveitu"] }, "Close Smart Picker": { "v": ["Loka snjall-veljara"] }, "Smart Picker": { "v": ["Snjall-veljari"] } } }, { "l": "it", "t": { "Back to provider selection": { "v": ["Torna alla selezione del provider"] }, "Close Smart Picker": { "v": ["Chiudere lo Smart Picker"] }, "Smart Picker": { "v": ["Picker intelligente"] } } }, { "l": "ja", "t": { "Back to provider selection": { "v": ["プロバイダーの選択に戻る"] }, "Close Smart Picker": { "v": ["スマートピッカーを閉じる"] }, "Smart Picker": { "v": ["スマートピッカー"] } } }, { "l": "ja-JP", "t": { "Back to provider selection": { "v": ["プロバイダーの選択に戻る"] }, "Close Smart Picker": { "v": ["スマートピッカーを閉じる"] }, "Smart Picker": { "v": ["スマートピッカー"] } } }, { "l": "ko", "t": { "Back to provider selection": { "v": ["제공자 선택으로 돌아가기"] }, "Close Smart Picker": { "v": ["스마트 선택기 닫기"] }, "Smart Picker": { "v": ["스마트 선택기"] } } }, { "l": "lo", "t": { "Back to provider selection": { "v": ["ກັບໄປທີ່ການເລືອກຜູ້ໃຫ້ບໍລິການ"] }, "Close Smart Picker": { "v": ["ປິດໂຕເລືອກອັດສະລິຍະ"] }, "Smart Picker": { "v": ["ໂຕເລືອກອັດສະລິຍະ"] } } }, { "l": "lt-LT", "t": {} }, { "l": "lv", "t": {} }, { "l": "mk", "t": { "Back to provider selection": { "v": ["Назад до избор на провајдер"] }, "Close Smart Picker": { "v": ["Затвори паметен избирач"] }, "Smart Picker": { "v": ["Паметен избирач"] } } }, { "l": "my", "t": {} }, { "l": "nb", "t": { "Back to provider selection": { "v": ["Tilbake til leverandørvalg"] }, "Close Smart Picker": { "v": ["Lukk Smart Velger"] }, "Smart Picker": { "v": ["Smart Velger"] } } }, { "l": "nl", "t": { "Back to provider selection": { "v": ["Terug naar provider selectie"] }, "Close Smart Picker": { "v": ["Slimme Kiezer sluiten"] }, "Smart Picker": { "v": ["Slimme Kiezer"] } } }, { "l": "oc", "t": {} }, { "l": "pl", "t": { "Back to provider selection": { "v": ["Powrót do wyboru dostawcy"] }, "Close Smart Picker": { "v": ["Zamknij inteligentny selektor"] }, "Smart Picker": { "v": ["Inteligentne wybieranie"] } } }, { "l": "pt-BR", "t": { "Back to provider selection": { "v": ["Voltar para seleção de provedor"] }, "Close Smart Picker": { "v": ["Fechar Seletor Inteligente"] }, "Smart Picker": { "v": ["Seletor Inteligente"] } } }, { "l": "pt-PT", "t": { "Back to provider selection": { "v": ["Voltar à seleção de fornecedor"] }, "Close Smart Picker": { "v": ['Fechar "Smart Picker"'] }, "Smart Picker": { "v": ["Smart Picker"] } } }, { "l": "ro", "t": { "Back to provider selection": { "v": ["Înapoi la selecția providerului"] }, "Close Smart Picker": { "v": ["Închide Smart Picker"] }, "Smart Picker": { "v": ["Smart Picker"] } } }, { "l": "ru", "t": { "Back to provider selection": { "v": ["Вернуться к выбору провайдера"] }, "Close Smart Picker": { "v": ["Закрыть интеллектуальный выбор"] }, "Smart Picker": { "v": ["Умный выбор"] } } }, { "l": "sk", "t": { "Back to provider selection": { "v": ["Späť na výber poskytovateľa"] }, "Close Smart Picker": { "v": ["Zavrieť inteligentný výber"] }, "Smart Picker": { "v": ["Inteligentný výber"] } } }, { "l": "sl", "t": {} }, { "l": "sr", "t": { "Back to provider selection": { "v": ["Назад на избор пружаоца"] }, "Close Smart Picker": { "v": ["Затвори паметни бирач"] }, "Smart Picker": { "v": ["Паметни бирач"] } } }, { "l": "sv", "t": { "Back to provider selection": { "v": ["Tillbaka till leverantörsval"] }, "Close Smart Picker": { "v": ["Stäng Smart Picker"] }, "Smart Picker": { "v": ["Smart Picker"] } } }, { "l": "tr", "t": { "Back to provider selection": { "v": ["Hizmet sağlayıcı seçimine dön"] }, "Close Smart Picker": { "v": ["Akıllı seçimi kapat"] }, "Smart Picker": { "v": ["Akıllı seçim"] } } }, { "l": "uk", "t": { "Back to provider selection": { "v": ["Назад до вибору постачальника"] }, "Close Smart Picker": { "v": ["Закрити асистент вибору"] }, "Smart Picker": { "v": ["Асистент вибору"] } } }, { "l": "uz", "t": { "Back to provider selection": { "v": ["Provayder tanloviga qaytish"] }, "Close Smart Picker": { "v": ["Smart Picker-ni yoping"] }, "Smart Picker": { "v": ["Aqlli tanlovchi"] } } }, { "l": "zh-CN", "t": { "Back to provider selection": { "v": ["返回至提供者选择列表"] }, "Close Smart Picker": { "v": ["关闭智能拾取器"] }, "Smart Picker": { "v": ["智能拾取器"] } } }, { "l": "zh-HK", "t": { "Back to provider selection": { "v": ["回到提供者選擇"] }, "Close Smart Picker": { "v": ["關閉 Smart Picker"] }, "Smart Picker": { "v": ["Smart Picker"] } } }, { "l": "zh-TW", "t": { "Back to provider selection": { "v": ["回到提供者選擇"] }, "Close Smart Picker": { "v": ["關閉智慧型挑選器"] }, "Smart Picker": { "v": ["智慧型挑選器"] } } }];
const t14 = [{ "l": "ar", "t": { "Cancel changes": { "v": ["إلغاء التغييرات"] }, "Confirm changes": { "v": ["تأكيد التغييرات"] } } }, { "l": "ast", "t": { "Cancel changes": { "v": ["Encaboxar los cambeos"] }, "Confirm changes": { "v": ["Confirmar los cambeos"] } } }, { "l": "br", "t": {} }, { "l": "ca", "t": { "Cancel changes": { "v": ["Cancel·la els canvis"] }, "Confirm changes": { "v": ["Confirmeu els canvis"] } } }, { "l": "cs", "t": { "Cancel changes": { "v": ["Zrušit změny"] }, "Confirm changes": { "v": ["Potvrdit změny"] } } }, { "l": "cs-CZ", "t": { "Cancel changes": { "v": ["Zrušit změny"] }, "Confirm changes": { "v": ["Potvrdit změny"] } } }, { "l": "da", "t": { "Cancel changes": { "v": ["Annuller ændringer"] }, "Confirm changes": { "v": ["Bekræft ændringer"] } } }, { "l": "de", "t": { "Cancel changes": { "v": ["Änderungen verwerfen"] }, "Confirm changes": { "v": ["Änderungen bestätigen"] } } }, { "l": "de-DE", "t": { "Cancel changes": { "v": ["Änderungen verwerfen"] }, "Confirm changes": { "v": ["Änderungen bestätigen"] } } }, { "l": "el", "t": { "Cancel changes": { "v": ["Ακύρωση αλλαγών"] }, "Confirm changes": { "v": ["Επιβεβαίωση αλλαγών"] } } }, { "l": "en-GB", "t": { "Cancel changes": { "v": ["Cancel changes"] }, "Confirm changes": { "v": ["Confirm changes"] } } }, { "l": "eo", "t": {} }, { "l": "es", "t": { "Cancel changes": { "v": ["Cancelar cambios"] }, "Confirm changes": { "v": ["Confirmar cambios"] } } }, { "l": "es-AR", "t": { "Cancel changes": { "v": ["Cancelar cambios"] }, "Confirm changes": { "v": ["Confirmar cambios"] } } }, { "l": "es-EC", "t": { "Cancel changes": { "v": ["Cancelar cambios"] }, "Confirm changes": { "v": ["Confirmar cambios"] } } }, { "l": "es-MX", "t": { "Cancel changes": { "v": ["Cancelar cambios"] }, "Confirm changes": { "v": ["Confirmar cambios"] } } }, { "l": "et-EE", "t": { "Cancel changes": { "v": ["Tühista muudatused"] }, "Confirm changes": { "v": ["Kinnitage muudatused"] } } }, { "l": "eu", "t": { "Cancel changes": { "v": ["Ezeztatu aldaketak"] }, "Confirm changes": { "v": ["Baieztatu aldaketak"] } } }, { "l": "fa", "t": { "Cancel changes": { "v": ["لغو تغییرات"] }, "Confirm changes": { "v": ["تایید تغییرات"] } } }, { "l": "fi", "t": { "Cancel changes": { "v": ["Peruuta muutokset"] }, "Confirm changes": { "v": ["Vahvista muutokset"] } } }, { "l": "fr", "t": { "Cancel changes": { "v": ["Annuler les modifications"] }, "Confirm changes": { "v": ["Confirmer les modifications"] } } }, { "l": "ga", "t": { "Cancel changes": { "v": ["Cealaigh athruithe"] }, "Confirm changes": { "v": ["Deimhnigh na hathruithe"] } } }, { "l": "gl", "t": { "Cancel changes": { "v": ["Cancelar os cambios"] }, "Confirm changes": { "v": ["Confirma os cambios"] } } }, { "l": "he", "t": { "Cancel changes": { "v": ["ביטול שינויים"] }, "Confirm changes": { "v": ["אישור השינויים"] } } }, { "l": "hu", "t": { "Cancel changes": { "v": ["Változtatások elvetése"] }, "Confirm changes": { "v": ["Változtatások megerősítése"] } } }, { "l": "id", "t": { "Cancel changes": { "v": ["Batalkan perubahan"] }, "Confirm changes": { "v": ["Konfirmasikan perubahan"] } } }, { "l": "is", "t": { "Cancel changes": { "v": ["Hætta við breytingar"] }, "Confirm changes": { "v": ["Staðfesta breytingar"] } } }, { "l": "it", "t": { "Cancel changes": { "v": ["Annulla modifiche"] }, "Confirm changes": { "v": ["Conferma modifiche"] } } }, { "l": "ja", "t": { "Cancel changes": { "v": ["変更をキャンセル"] }, "Confirm changes": { "v": ["変更を承認"] } } }, { "l": "ja-JP", "t": { "Cancel changes": { "v": ["変更をキャンセル"] }, "Confirm changes": { "v": ["変更を承認"] } } }, { "l": "ko", "t": { "Cancel changes": { "v": ["변경 취소"] }, "Confirm changes": { "v": ["변경 사항 확인"] } } }, { "l": "lo", "t": { "Cancel changes": { "v": ["ຍົກເລີກການປ່ຽນແປງ"] }, "Confirm changes": { "v": ["ຢືນຢັນການປ່ຽນແປງ"] } } }, { "l": "lt-LT", "t": {} }, { "l": "lv", "t": {} }, { "l": "mk", "t": { "Cancel changes": { "v": ["Откажи ги промените"] }, "Confirm changes": { "v": ["Потврди ги промените"] } } }, { "l": "my", "t": { "Cancel changes": { "v": ["ပြောင်းလဲမှုများ ပယ်ဖျက်ရန်"] }, "Confirm changes": { "v": ["ပြောင်းလဲမှုများ အတည်ပြုရန်"] } } }, { "l": "nb", "t": { "Cancel changes": { "v": ["Avbryt endringer"] }, "Confirm changes": { "v": ["Bekreft endringer"] } } }, { "l": "nl", "t": { "Cancel changes": { "v": ["Wijzigingen annuleren"] }, "Confirm changes": { "v": ["Wijzigingen bevestigen"] } } }, { "l": "oc", "t": {} }, { "l": "pl", "t": { "Cancel changes": { "v": ["Anuluj zmiany"] }, "Confirm changes": { "v": ["Potwierdź zmiany"] } } }, { "l": "pt-BR", "t": { "Cancel changes": { "v": ["Cancelar alterações"] }, "Confirm changes": { "v": ["Confirmar alterações"] } } }, { "l": "pt-PT", "t": { "Cancel changes": { "v": ["Cancelar alterações"] }, "Confirm changes": { "v": ["Confirmar alterações"] } } }, { "l": "ro", "t": { "Cancel changes": { "v": ["Anulează modificările"] }, "Confirm changes": { "v": ["Confirmați modificările"] } } }, { "l": "ru", "t": { "Cancel changes": { "v": ["Отменить изменения"] }, "Confirm changes": { "v": ["Подтвердить изменения"] } } }, { "l": "sk", "t": { "Cancel changes": { "v": ["Zrušiť zmeny"] }, "Confirm changes": { "v": ["Potvrdiť zmeny"] } } }, { "l": "sl", "t": { "Cancel changes": { "v": ["Prekliči spremembe"] }, "Confirm changes": { "v": ["Potrdi spremembe"] } } }, { "l": "sr", "t": { "Cancel changes": { "v": ["Откажи измене"] }, "Confirm changes": { "v": ["Потврдите измене"] } } }, { "l": "sv", "t": { "Cancel changes": { "v": ["Avbryt ändringar"] }, "Confirm changes": { "v": ["Bekräfta ändringar"] } } }, { "l": "tr", "t": { "Cancel changes": { "v": ["Değişiklikleri iptal et"] }, "Confirm changes": { "v": ["Değişiklikleri onayla"] } } }, { "l": "uk", "t": { "Cancel changes": { "v": ["Скасувати зміни"] }, "Confirm changes": { "v": ["Підтвердити зміни"] } } }, { "l": "uz", "t": { "Cancel changes": { "v": ["O'zgarishlarni bekor qilish"] }, "Confirm changes": { "v": ["O'zgarishlarni tasdiqlang"] } } }, { "l": "zh-CN", "t": { "Cancel changes": { "v": ["取消更改"] }, "Confirm changes": { "v": ["确认更改"] } } }, { "l": "zh-HK", "t": { "Cancel changes": { "v": ["取消更改"] }, "Confirm changes": { "v": ["確認更改"] } } }, { "l": "zh-TW", "t": { "Cancel changes": { "v": ["取消變更"] }, "Confirm changes": { "v": ["確認變更"] } } }];
const t15 = [{ "l": "ar", "t": { "Change name": { "v": ["تغيير الاسم"] }, "Close sidebar": { "v": ["قفل الشريط الجانبي"] }, "Favorite": { "v": ["المفضلة"] }, "Open sidebar": { "v": ["إفتَح الشريط الجانبي"] } } }, { "l": "ast", "t": { "Change name": { "v": ["Camudar el nome"] }, "Close sidebar": { "v": ["Zarrar la barra llateral"] }, "Favorite": { "v": ["Favoritu"] }, "Open sidebar": { "v": ["Abrir la barra llateral"] } } }, { "l": "br", "t": {} }, { "l": "ca", "t": { "Close sidebar": { "v": ["Tancar la barra lateral"] }, "Favorite": { "v": ["Preferit"] } } }, { "l": "cs", "t": { "Change name": { "v": ["Změnit název"] }, "Close sidebar": { "v": ["Zavřít postranní panel"] }, "Favorite": { "v": ["Oblíbené"] }, "Open sidebar": { "v": ["Otevřít postranní panel"] } } }, { "l": "cs-CZ", "t": { "Change name": { "v": ["Změnit název"] }, "Close sidebar": { "v": ["Zavřít postranní panel"] }, "Favorite": { "v": ["Oblíbené"] } } }, { "l": "da", "t": { "Change name": { "v": ["Ændre navn"] }, "Close sidebar": { "v": ["Luk sidepanel"] }, "Favorite": { "v": ["Favorit"] }, "Open sidebar": { "v": ["Åbn sidepanel"] } } }, { "l": "de", "t": { "Change name": { "v": ["Namen ändern"] }, "Close sidebar": { "v": ["Seitenleiste schließen"] }, "Favorite": { "v": ["Favorit"] }, "Open sidebar": { "v": ["Seitenleiste öffnen"] } } }, { "l": "de-DE", "t": { "Change name": { "v": ["Namen ändern"] }, "Close sidebar": { "v": ["Seitenleiste schließen"] }, "Favorite": { "v": ["Favorit"] }, "Open sidebar": { "v": ["Seitenleiste öffnen"] } } }, { "l": "el", "t": { "Change name": { "v": ["Αλλαγή ονόματος"] }, "Close sidebar": { "v": ["Κλείσιμο πλευρικής μπάρας"] }, "Favorite": { "v": ["Αγαπημένα"] }, "Open sidebar": { "v": ["Άνοιγμα πλευρικής μπάρας"] } } }, { "l": "en-GB", "t": { "Change name": { "v": ["Change name"] }, "Close sidebar": { "v": ["Close sidebar"] }, "Favorite": { "v": ["Favourite"] }, "Open sidebar": { "v": ["Open sidebar"] } } }, { "l": "eo", "t": {} }, { "l": "es", "t": { "Change name": { "v": ["Cambiar nombre"] }, "Close sidebar": { "v": ["Cerrar barra lateral"] }, "Favorite": { "v": ["Favorito"] }, "Open sidebar": { "v": ["Abrir barra lateral"] } } }, { "l": "es-AR", "t": { "Change name": { "v": ["Cambiar nombre"] }, "Close sidebar": { "v": ["Cerrar barra lateral"] }, "Favorite": { "v": ["Favorito"] }, "Open sidebar": { "v": ["Abrir barra lateral"] } } }, { "l": "es-EC", "t": { "Change name": { "v": ["Cambiar nombre"] }, "Close sidebar": { "v": ["Cerrar barra lateral"] }, "Favorite": { "v": ["Favorito"] } } }, { "l": "es-MX", "t": { "Change name": { "v": ["Cambiar nombre"] }, "Close sidebar": { "v": ["Cerrar barra lateral"] }, "Favorite": { "v": ["Favorito"] }, "Open sidebar": { "v": ["Abrir barra lateral"] } } }, { "l": "et-EE", "t": { "Change name": { "v": ["Muuda nime"] }, "Close sidebar": { "v": ["Sulge külgriba"] }, "Favorite": { "v": ["Lemmik"] }, "Open sidebar": { "v": ["Ava külgriba"] } } }, { "l": "eu", "t": { "Change name": { "v": ["Aldatu izena"] }, "Close sidebar": { "v": ["Itxi albo-barra"] }, "Favorite": { "v": ["Gogokoa"] } } }, { "l": "fa", "t": { "Change name": { "v": ["تغییر نام"] }, "Close sidebar": { "v": ["بستن نوار کناری"] }, "Favorite": { "v": ["مورد علاقه"] }, "Open sidebar": { "v": ["باز کردن نوار کنار"] } } }, { "l": "fi", "t": { "Change name": { "v": ["Vaihda nimi"] }, "Close sidebar": { "v": ["Sulje sivupalkki"] }, "Favorite": { "v": ["Suosikki"] }, "Open sidebar": { "v": ["Avaa sivupalkki"] } } }, { "l": "fr", "t": { "Change name": { "v": ["Modifier le nom"] }, "Close sidebar": { "v": ["Fermer la barre latérale"] }, "Favorite": { "v": ["Favori"] }, "Open sidebar": { "v": ["Ouvrir la barre latérale"] } } }, { "l": "ga", "t": { "Change name": { "v": ["Athrú ainm"] }, "Close sidebar": { "v": ["Dún barra taoibh"] }, "Favorite": { "v": ["is fearr leat"] }, "Open sidebar": { "v": ["Oscail barra taoibh"] } } }, { "l": "gl", "t": { "Change name": { "v": ["Cambiar o nome"] }, "Close sidebar": { "v": ["Pechar a barra lateral"] }, "Favorite": { "v": ["Favorito"] }, "Open sidebar": { "v": ["Abrir a barra lateral"] } } }, { "l": "he", "t": { "Change name": { "v": ["החלפת שם"] }, "Close sidebar": { "v": ["סגירת סרגל הצד"] }, "Favorite": { "v": ["למועדפים"] } } }, { "l": "hu", "t": { "Close sidebar": { "v": ["Oldalsáv bezárása"] }, "Favorite": { "v": ["Kedvenc"] } } }, { "l": "id", "t": { "Change name": { "v": ["Ubah nama"] }, "Close sidebar": { "v": ["Tutup bilah sisi"] }, "Favorite": { "v": ["Favorit"] } } }, { "l": "is", "t": { "Change name": { "v": ["Breyta nafni"] }, "Close sidebar": { "v": ["Loka hliðarstiku"] }, "Favorite": { "v": ["Eftirlæti"] }, "Open sidebar": { "v": ["Opna hliðarspjald"] } } }, { "l": "it", "t": { "Change name": { "v": ["Cambia nome"] }, "Close sidebar": { "v": ["Chiudi la barra laterale"] }, "Favorite": { "v": ["Preferito"] } } }, { "l": "ja", "t": { "Change name": { "v": ["名前の変更"] }, "Close sidebar": { "v": ["サイドバーを閉じる"] }, "Favorite": { "v": ["お気に入り"] }, "Open sidebar": { "v": ["サイドバーを開く"] } } }, { "l": "ja-JP", "t": { "Change name": { "v": ["名前の変更"] }, "Close sidebar": { "v": ["サイドバーを閉じる"] }, "Favorite": { "v": ["お気に入り"] }, "Open sidebar": { "v": ["サイドバーを開く"] } } }, { "l": "ko", "t": { "Change name": { "v": ["이름 변경"] }, "Close sidebar": { "v": ["사이드바 닫기"] }, "Favorite": { "v": ["즐겨찾기"] }, "Open sidebar": { "v": ["사이드바 열기"] } } }, { "l": "lo", "t": { "Change name": { "v": ["ປ່ຽນຊື່"] }, "Close sidebar": { "v": ["ປິດແຖບດ້ານຂ້າງ"] }, "Favorite": { "v": ["ລາຍການທີ່ມັກ"] }, "Open sidebar": { "v": ["ເປີດແຖບດ້ານຂ້າງ"] } } }, { "l": "lt-LT", "t": {} }, { "l": "lv", "t": {} }, { "l": "mk", "t": { "Change name": { "v": ["Промени име"] }, "Close sidebar": { "v": ["Затвори странична лента"] }, "Favorite": { "v": ["Фаворити"] }, "Open sidebar": { "v": ["Отвори странична лента"] } } }, { "l": "my", "t": {} }, { "l": "nb", "t": { "Change name": { "v": ["Endre navn"] }, "Close sidebar": { "v": ["Lukk sidepanel"] }, "Favorite": { "v": ["Favoritt"] }, "Open sidebar": { "v": ["Åpne sidefelt"] } } }, { "l": "nl", "t": { "Change name": { "v": ["Naam wijzigen"] }, "Close sidebar": { "v": ["Zijbalk sluiten"] }, "Favorite": { "v": ["Favoriet"] }, "Open sidebar": { "v": ["Zijbalk openen"] } } }, { "l": "oc", "t": {} }, { "l": "pl", "t": { "Change name": { "v": ["Zmień nazwę"] }, "Close sidebar": { "v": ["Zamknij pasek boczny"] }, "Favorite": { "v": ["Ulubiony"] }, "Open sidebar": { "v": ["Otwórz pasek boczny"] } } }, { "l": "pt-BR", "t": { "Change name": { "v": ["Mudar nome"] }, "Close sidebar": { "v": ["Fechar barra lateral"] }, "Favorite": { "v": ["Favorito"] }, "Open sidebar": { "v": ["Abrir barra lateral"] } } }, { "l": "pt-PT", "t": { "Change name": { "v": ["Alterar nome"] }, "Close sidebar": { "v": ["Fechar barra lateral"] }, "Favorite": { "v": ["Favorito"] }, "Open sidebar": { "v": ["Abrir barra lateral"] } } }, { "l": "ro", "t": { "Change name": { "v": ["Modifică numele"] }, "Close sidebar": { "v": ["Închide bara laterală"] }, "Favorite": { "v": ["Favorit"] } } }, { "l": "ru", "t": { "Change name": { "v": ["Изменить имя"] }, "Close sidebar": { "v": ["Закрыть сайдбар"] }, "Favorite": { "v": ["Избранное"] }, "Open sidebar": { "v": ["Открыть боковую панель"] } } }, { "l": "sk", "t": { "Change name": { "v": ["Zmeniť názov"] }, "Close sidebar": { "v": ["Zavrieť bočný panel"] }, "Favorite": { "v": ["Obľúbené"] }, "Open sidebar": { "v": ["Otvoriť bočný panel"] } } }, { "l": "sl", "t": { "Close sidebar": { "v": ["Zapri stransko vrstico"] }, "Favorite": { "v": ["Priljubljeno"] } } }, { "l": "sr", "t": { "Change name": { "v": ["Измени назив"] }, "Close sidebar": { "v": ["Затвори бочну траку"] }, "Favorite": { "v": ["Омиљени"] }, "Open sidebar": { "v": ["Отвори бочну траку"] } } }, { "l": "sv", "t": { "Change name": { "v": ["Ändra namn"] }, "Close sidebar": { "v": ["Stäng sidofältet"] }, "Favorite": { "v": ["Favorit"] }, "Open sidebar": { "v": ["Öppna sidofältet"] } } }, { "l": "tr", "t": { "Change name": { "v": ["Adı değiştir"] }, "Close sidebar": { "v": ["Yan çubuğu kapat"] }, "Favorite": { "v": ["Sık kullanılanlara ekle"] }, "Open sidebar": { "v": ["Yan çubuğu aç"] } } }, { "l": "uk", "t": { "Change name": { "v": ["Змінити назву"] }, "Close sidebar": { "v": ["Закрити бічну панель"] }, "Favorite": { "v": ["Із зірочкою"] }, "Open sidebar": { "v": ["Бокове меню"] } } }, { "l": "uz", "t": { "Change name": { "v": ["Ismni o'zgartirish"] }, "Close sidebar": { "v": ["Yon panelni yoping"] }, "Favorite": { "v": ["Tanlangan"] }, "Open sidebar": { "v": ["Yon panelni oching"] } } }, { "l": "zh-CN", "t": { "Change name": { "v": ["修改名称"] }, "Close sidebar": { "v": ["关闭侧边栏"] }, "Favorite": { "v": ["喜爱"] }, "Open sidebar": { "v": ["打开侧边栏"] } } }, { "l": "zh-HK", "t": { "Change name": { "v": ["更改名稱"] }, "Close sidebar": { "v": ["關閉側邊欄"] }, "Favorite": { "v": ["喜愛"] }, "Open sidebar": { "v": ["打開側邊欄"] } } }, { "l": "zh-TW", "t": { "Change name": { "v": ["變更名稱"] }, "Close sidebar": { "v": ["關閉側邊欄"] }, "Favorite": { "v": ["最愛"] }, "Open sidebar": { "v": ["開啟側邊欄"] } } }];
const t16 = [{ "l": "ar", "t": { "Clear search": { "v": ["محو البحث"] } } }, { "l": "ast", "t": { "Clear search": { "v": ["Borrar la busca"] } } }, { "l": "br", "t": {} }, { "l": "ca", "t": {} }, { "l": "cs", "t": { "Clear search": { "v": ["Vyčistit vyhledávání"] } } }, { "l": "cs-CZ", "t": { "Clear search": { "v": ["Vyčistit vyhledávání"] } } }, { "l": "da", "t": { "Clear search": { "v": ["Ryd søgning"] } } }, { "l": "de", "t": { "Clear search": { "v": ["Suche leeren"] } } }, { "l": "de-DE", "t": { "Clear search": { "v": ["Suche leeren"] } } }, { "l": "el", "t": { "Clear search": { "v": ["Εκκαθάριση αναζήτησης"] } } }, { "l": "en-GB", "t": { "Clear search": { "v": ["Clear search"] } } }, { "l": "eo", "t": {} }, { "l": "es", "t": { "Clear search": { "v": ["Limpiar búsqueda"] } } }, { "l": "es-AR", "t": { "Clear search": { "v": ["Limpiar búsqueda"] } } }, { "l": "es-EC", "t": { "Clear search": { "v": ["Limpiar búsqueda"] } } }, { "l": "es-MX", "t": { "Clear search": { "v": ["Limpiar búsqueda"] } } }, { "l": "et-EE", "t": { "Clear search": { "v": ["Tühjenda otsing"] } } }, { "l": "eu", "t": { "Clear search": { "v": ["Garbitu bilaketa"] } } }, { "l": "fa", "t": { "Clear search": { "v": ["پاک کردن جستجو"] } } }, { "l": "fi", "t": { "Clear search": { "v": ["Tyhjennä haku"] } } }, { "l": "fr", "t": { "Clear search": { "v": ["Effacer la recherche"] } } }, { "l": "ga", "t": { "Clear search": { "v": ["Glan cuardach"] } } }, { "l": "gl", "t": { "Clear search": { "v": ["Limpar a busca"] } } }, { "l": "he", "t": { "Clear search": { "v": ["פינוי חיפוש"] } } }, { "l": "hu", "t": {} }, { "l": "id", "t": { "Clear search": { "v": ["Bersihkan pencarian"] } } }, { "l": "is", "t": { "Clear search": { "v": ["Hreinsa leit"] } } }, { "l": "it", "t": { "Clear search": { "v": ["online"] } } }, { "l": "ja", "t": { "Clear search": { "v": ["検索をクリア"] } } }, { "l": "ja-JP", "t": { "Clear search": { "v": ["検索をクリア"] } } }, { "l": "ko", "t": { "Clear search": { "v": ["검색 지우기"] } } }, { "l": "lo", "t": { "Clear search": { "v": ["ລຶບການຄົ້ນຫາ"] } } }, { "l": "lt-LT", "t": {} }, { "l": "lv", "t": {} }, { "l": "mk", "t": { "Clear search": { "v": ["Исчисти пребарување"] } } }, { "l": "my", "t": {} }, { "l": "nb", "t": { "Clear search": { "v": ["Tøm søk"] } } }, { "l": "nl", "t": { "Clear search": { "v": ["Zoekopdracht wissen"] } } }, { "l": "oc", "t": {} }, { "l": "pl", "t": { "Clear search": { "v": ["Wyczyść wyszukiwanie"] } } }, { "l": "pt-BR", "t": { "Clear search": { "v": ["Limpar pesquisa"] } } }, { "l": "pt-PT", "t": { "Clear search": { "v": ["Limpar pesquisa"] } } }, { "l": "ro", "t": { "Clear search": { "v": ["Șterge căutarea"] } } }, { "l": "ru", "t": { "Clear search": { "v": ["Очистить поиск"] } } }, { "l": "sk", "t": { "Clear search": { "v": ["Vymazať vyhľadávanie"] } } }, { "l": "sl", "t": {} }, { "l": "sr", "t": { "Clear search": { "v": ["Обриши претрагу"] } } }, { "l": "sv", "t": { "Clear search": { "v": ["Rensa sökning"] } } }, { "l": "tr", "t": { "Clear search": { "v": ["Aramayı temizle"] } } }, { "l": "uk", "t": { "Clear search": { "v": ["Очистити пошук"] } } }, { "l": "uz", "t": { "Clear search": { "v": ["Qidiruvni tozalash"] } } }, { "l": "zh-CN", "t": { "Clear search": { "v": ["清除搜索"] } } }, { "l": "zh-HK", "t": { "Clear search": { "v": ["清除搜索"] } } }, { "l": "zh-TW", "t": { "Clear search": { "v": ["清除搜尋"] } } }];
const t17 = [{ "l": "ar", "t": { "Clear selected": { "v": ["محو المحدّد"] }, "Deselect {option}": { "v": ["إلغاء تحديد {option}"] }, "No results": { "v": ["ليس هناك أية نتيجة"] }, "Options": { "v": ["خيارات"] } } }, { "l": "ast", "t": { "Clear selected": { "v": ["Borrar lo seleicionao"] }, "Deselect {option}": { "v": ["Deseleicionar «{option}»"] }, "No results": { "v": ["Nun hai nengún resultáu"] }, "Options": { "v": ["Opciones"] } } }, { "l": "br", "t": { "No results": { "v": ["Disoc'h ebet"] } } }, { "l": "ca", "t": { "No results": { "v": ["Sense resultats"] } } }, { "l": "cs", "t": { "Clear selected": { "v": ["Vyčistit vybrané"] }, "Deselect {option}": { "v": ["Zrušit výběr {option}"] }, "No results": { "v": ["Nic nenalezeno"] }, "Options": { "v": ["Možnosti"] } } }, { "l": "cs-CZ", "t": { "Clear selected": { "v": ["Vyčistit vybrané"] }, "Deselect {option}": { "v": ["Zrušit výběr {option}"] }, "No results": { "v": ["Nic nenalezeno"] }, "Options": { "v": ["Možnosti"] } } }, { "l": "da", "t": { "Clear selected": { "v": ["Ryd valgt"] }, "Deselect {option}": { "v": ["Fravælg {option}"] }, "No results": { "v": ["Ingen resultater"] }, "Options": { "v": ["Indstillinger"] } } }, { "l": "de", "t": { "Clear selected": { "v": ["Auswahl leeren"] }, "Deselect {option}": { "v": ["{option} abwählen"] }, "No results": { "v": ["Keine Ergebnisse"] }, "Options": { "v": ["Optionen"] } } }, { "l": "de-DE", "t": { "Clear selected": { "v": ["Auswahl leeren"] }, "Deselect {option}": { "v": ["{option} abwählen"] }, "No results": { "v": ["Keine Ergebnisse"] }, "Options": { "v": ["Optionen"] } } }, { "l": "el", "t": { "Clear selected": { "v": ["Εκκαθάριση επιλογής"] }, "Deselect {option}": { "v": ["Αποεπιλογή {option}"] }, "No results": { "v": ["Κανένα αποτέλεσμα"] }, "Options": { "v": ["Επιλογές"] } } }, { "l": "en-GB", "t": { "Clear selected": { "v": ["Clear selected"] }, "Deselect {option}": { "v": ["Deselect {option}"] }, "No results": { "v": ["No results"] }, "Options": { "v": ["Options"] } } }, { "l": "eo", "t": { "No results": { "v": ["La rezulto forestas"] } } }, { "l": "es", "t": { "Clear selected": { "v": ["Limpiar selección"] }, "Deselect {option}": { "v": ["Deseleccionar {option}"] }, "No results": { "v": [" Ningún resultado"] }, "Options": { "v": ["Opciones"] } } }, { "l": "es-AR", "t": { "Clear selected": { "v": ["Limpiar selección"] }, "Deselect {option}": { "v": ["Deseleccionar {option}"] }, "No results": { "v": ["Sin resultados"] }, "Options": { "v": ["Opciones"] } } }, { "l": "es-EC", "t": { "No results": { "v": ["Sin resultados"] } } }, { "l": "es-MX", "t": { "Clear selected": { "v": ["Limpiar selección"] }, "Deselect {option}": { "v": ["Deseleccionar {option}"] }, "No results": { "v": ["Sin resultados"] }, "Options": { "v": ["Opciones"] } } }, { "l": "et-EE", "t": { "Clear selected": { "v": ["Tühjenad valik"] }, "Deselect {option}": { "v": ["Eemalda {option} valik"] }, "No results": { "v": ["Tulemusi pole"] }, "Options": { "v": ["Valikud"] } } }, { "l": "eu", "t": { "No results": { "v": ["Emaitzarik ez"] } } }, { "l": "fa", "t": { "Clear selected": { "v": ["پاک کردن مورد انتخاب شده"] }, "Deselect {option}": { "v": ["لغو انتخاب {option}"] }, "No results": { "v": ["بدون هیچ نتیجه‌ای"] }, "Options": { "v": ["گزینه‌ها"] } } }, { "l": "fi", "t": { "Clear selected": { "v": ["Tyhjennä valitut"] }, "Deselect {option}": { "v": ["Poista valinta {option}"] }, "No results": { "v": ["Ei tuloksia"] }, "Options": { "v": ["Valinnat"] } } }, { "l": "fr", "t": { "Clear selected": { "v": ["Vider la sélection"] }, "Deselect {option}": { "v": ["Désélectionner {option}"] }, "No results": { "v": ["Aucun résultat"] }, "Options": { "v": ["Options"] } } }, { "l": "ga", "t": { "Clear selected": { "v": ["Glan roghnaithe"] }, "Deselect {option}": { "v": ["Díroghnaigh {option}"] }, "No results": { "v": ["Gan torthaí"] }, "Options": { "v": ["Roghanna"] } } }, { "l": "gl", "t": { "Clear selected": { "v": ["Limpar o seleccionado"] }, "Deselect {option}": { "v": ["Desmarcar {option}"] }, "No results": { "v": ["Sen resultados"] }, "Options": { "v": ["Opcións"] } } }, { "l": "he", "t": { "No results": { "v": ["אין תוצאות"] } } }, { "l": "hu", "t": { "No results": { "v": ["Nincs találat"] } } }, { "l": "id", "t": { "Clear selected": { "v": ["Hapus terpilih"] }, "Deselect {option}": { "v": ["Batalkan pemilihan {option}"] }, "No results": { "v": ["Tidak ada hasil"] } } }, { "l": "is", "t": { "Clear selected": { "v": ["Hreinsa valið"] }, "Deselect {option}": { "v": ["Afvelja {option}"] }, "No results": { "v": ["Engar niðurstöður"] }, "Options": { "v": ["Valkostir"] } } }, { "l": "it", "t": { "Clear selected": { "v": ["Cancella selezionati"] }, "Deselect {option}": { "v": ["Deselezionare {option}"] }, "No results": { "v": ["Nessun risultato"] } } }, { "l": "ja", "t": { "Clear selected": { "v": ["選択を解除"] }, "Deselect {option}": { "v": ["{option} の選択を解除"] }, "No results": { "v": ["結果無し"] }, "Options": { "v": ["オプション"] } } }, { "l": "ja-JP", "t": { "Clear selected": { "v": ["選択を解除"] }, "Deselect {option}": { "v": ["{option} の選択を解除"] }, "No results": { "v": ["結果無し"] }, "Options": { "v": ["オプション"] } } }, { "l": "ko", "t": { "Clear selected": { "v": ["선택 항목 지우기"] }, "Deselect {option}": { "v": ["{option} 선택 해제"] }, "No results": { "v": ["결과 없음"] }, "Options": { "v": ["옵션"] } } }, { "l": "lo", "t": { "Clear selected": { "v": ["ລຶບສິ່ງທີ່ເລືອກ"] }, "Deselect {option}": { "v": ["ຍົກເລີກການເລືອກ {option}"] }, "No results": { "v": ["ບໍ່ມີຜົນລັບ"] }, "Options": { "v": ["ຕົວເລືອກ"] } } }, { "l": "lt-LT", "t": { "No results": { "v": ["Nėra rezultatų"] } } }, { "l": "lv", "t": { "No results": { "v": ["Nav rezultātu"] } } }, { "l": "mk", "t": { "Clear selected": { "v": ["Исчисти означени"] }, "Deselect {option}": { "v": ["Откажи избор на {option}"] }, "No results": { "v": ["Нема резултати"] }, "Options": { "v": ["Опции"] } } }, { "l": "my", "t": { "No results": { "v": ["ရလဒ်မရှိပါ"] } } }, { "l": "nb", "t": { "Clear selected": { "v": ["Tøm merket"] }, "Deselect {option}": { "v": ["Opphev valg {option}"] }, "No results": { "v": ["Ingen resultater"] }, "Options": { "v": ["Alternativer"] } } }, { "l": "nl", "t": { "Clear selected": { "v": ["Selectie wissen"] }, "Deselect {option}": { "v": ["Selectie {option} opheffen"] }, "No results": { "v": ["Geen resultaten"] }, "Options": { "v": ["Opties"] } } }, { "l": "oc", "t": { "No results": { "v": ["Cap de resultat"] } } }, { "l": "pl", "t": { "Clear selected": { "v": ["Wyczyść wybrane"] }, "Deselect {option}": { "v": ["Odznacz {option}"] }, "No results": { "v": ["Brak wyników"] }, "Options": { "v": ["Opcje"] } } }, { "l": "pt-BR", "t": { "Clear selected": { "v": ["Limpar selecionado"] }, "Deselect {option}": { "v": ["Desselecionar {option}"] }, "No results": { "v": ["Sem resultados"] }, "Options": { "v": ["Opções"] } } }, { "l": "pt-PT", "t": { "Clear selected": { "v": ["Limpeza selecionada"] }, "Deselect {option}": { "v": ["Desmarcar {option}"] }, "No results": { "v": ["Sem resultados"] }, "Options": { "v": ["Opções"] } } }, { "l": "ro", "t": { "Clear selected": { "v": ["Șterge selecția"] }, "Deselect {option}": { "v": ["Deselctează {option}"] }, "No results": { "v": ["Nu există rezultate"] } } }, { "l": "ru", "t": { "Clear selected": { "v": ["Очистить выбранный"] }, "Deselect {option}": { "v": ["Отменить выбор {option}"] }, "No results": { "v": ["Результаты отсуствуют"] }, "Options": { "v": ["Варианты"] } } }, { "l": "sk", "t": { "Clear selected": { "v": ["Vymazať vybraté"] }, "Deselect {option}": { "v": ["Zrušiť výber {option}"] }, "No results": { "v": ["Žiadne výsledky"] }, "Options": { "v": ["možnosti"] } } }, { "l": "sl", "t": { "No results": { "v": ["Ni zadetkov"] } } }, { "l": "sr", "t": { "Clear selected": { "v": ["Обриши изабрано"] }, "Deselect {option}": { "v": ["Уклони избор {option}"] }, "No results": { "v": ["Нема резултата"] }, "Options": { "v": ["Опције"] } } }, { "l": "sv", "t": { "Clear selected": { "v": ["Rensa val"] }, "Deselect {option}": { "v": ["Avmarkera {option}"] }, "No results": { "v": ["Inga resultat"] }, "Options": { "v": ["Alternativ"] } } }, { "l": "tr", "t": { "Clear selected": { "v": ["Seçilmişleri temizle"] }, "Deselect {option}": { "v": ["{option} bırak"] }, "No results": { "v": ["Herhangi bir sonuç bulunamadı"] }, "Options": { "v": ["Seçenekler"] } } }, { "l": "uk", "t": { "Clear selected": { "v": ["Очистити вибране"] }, "Deselect {option}": { "v": ["Зняти вибір {option}"] }, "No results": { "v": ["Відсутні результати"] }, "Options": { "v": ["Параметри"] } } }, { "l": "uz", "t": { "Clear selected": { "v": ["Tanlanganni tozalash"] }, "Deselect {option}": { "v": ["{option}tanlovni bekor qiling"] }, "No results": { "v": ["Natija yoʻq"] }, "Options": { "v": ["Variantlar"] } } }, { "l": "zh-CN", "t": { "Clear selected": { "v": ["清除所选"] }, "Deselect {option}": { "v": ["取消选择 {option}"] }, "No results": { "v": ["无结果"] }, "Options": { "v": ["选项"] } } }, { "l": "zh-HK", "t": { "Clear selected": { "v": ["清除所選項目"] }, "Deselect {option}": { "v": ["取消選擇 {option}"] }, "No results": { "v": ["無結果"] }, "Options": { "v": ["選項"] } } }, { "l": "zh-TW", "t": { "Clear selected": { "v": ["清除選定項目"] }, "Deselect {option}": { "v": ["取消選取 {option}"] }, "No results": { "v": ["無結果"] }, "Options": { "v": ["選項"] } } }];
const t19 = [{ "l": "ar", "t": { "Close": { "v": ["إغلاق"] } } }, { "l": "ast", "t": { "Close": { "v": ["Zarrar"] } } }, { "l": "br", "t": { "Close": { "v": ["Serriñ"] } } }, { "l": "ca", "t": { "Close": { "v": ["Tanca"] } } }, { "l": "cs", "t": { "Close": { "v": ["Zavřít"] } } }, { "l": "cs-CZ", "t": { "Close": { "v": ["Zavřít"] } } }, { "l": "da", "t": { "Close": { "v": ["Luk"] } } }, { "l": "de", "t": { "Close": { "v": ["Schließen"] } } }, { "l": "de-DE", "t": { "Close": { "v": ["Schließen"] } } }, { "l": "el", "t": { "Close": { "v": ["Κλείσιμο"] } } }, { "l": "en-GB", "t": { "Close": { "v": ["Close"] } } }, { "l": "eo", "t": { "Close": { "v": ["Fermu"] } } }, { "l": "es", "t": { "Close": { "v": ["Cerrar"] } } }, { "l": "es-AR", "t": { "Close": { "v": ["Cerrar"] } } }, { "l": "es-EC", "t": { "Close": { "v": ["Cerrar"] } } }, { "l": "es-MX", "t": { "Close": { "v": ["Cerrar"] } } }, { "l": "et-EE", "t": { "Close": { "v": ["Sulge"] } } }, { "l": "eu", "t": { "Close": { "v": ["Itxi"] } } }, { "l": "fa", "t": { "Close": { "v": ["بستن"] } } }, { "l": "fi", "t": { "Close": { "v": ["Sulje"] } } }, { "l": "fr", "t": { "Close": { "v": ["Fermer"] } } }, { "l": "ga", "t": { "Close": { "v": ["Dún"] } } }, { "l": "gl", "t": { "Close": { "v": ["Pechar"] } } }, { "l": "he", "t": { "Close": { "v": ["סגירה"] } } }, { "l": "hu", "t": { "Close": { "v": ["Bezárás"] } } }, { "l": "id", "t": { "Close": { "v": ["Tutup"] } } }, { "l": "is", "t": { "Close": { "v": ["Loka"] } } }, { "l": "it", "t": { "Close": { "v": ["Chiudi"] } } }, { "l": "ja", "t": { "Close": { "v": ["閉じる"] } } }, { "l": "ja-JP", "t": { "Close": { "v": ["閉じる"] } } }, { "l": "ko", "t": { "Close": { "v": ["닫기"] } } }, { "l": "lo", "t": { "Close": { "v": ["ປິດ"] } } }, { "l": "lt-LT", "t": { "Close": { "v": ["Užverti"] } } }, { "l": "lv", "t": { "Close": { "v": ["Aizvērt"] } } }, { "l": "mk", "t": { "Close": { "v": ["Затвори"] } } }, { "l": "my", "t": { "Close": { "v": ["ပိတ်ရန်"] } } }, { "l": "nb", "t": { "Close": { "v": ["Lukk"] } } }, { "l": "nl", "t": { "Close": { "v": ["Sluiten"] } } }, { "l": "oc", "t": { "Close": { "v": ["Tampar"] } } }, { "l": "pl", "t": { "Close": { "v": ["Zamknij"] } } }, { "l": "pt-BR", "t": { "Close": { "v": ["Fechar"] } } }, { "l": "pt-PT", "t": { "Close": { "v": ["Fechar"] } } }, { "l": "ro", "t": { "Close": { "v": ["Închideți"] } } }, { "l": "ru", "t": { "Close": { "v": ["Закрыть"] } } }, { "l": "sk", "t": { "Close": { "v": ["Zavrieť"] } } }, { "l": "sl", "t": { "Close": { "v": ["Zapri"] } } }, { "l": "sr", "t": { "Close": { "v": ["Затвори"] } } }, { "l": "sv", "t": { "Close": { "v": ["Stäng"] } } }, { "l": "tr", "t": { "Close": { "v": ["Kapat"] } } }, { "l": "uk", "t": { "Close": { "v": ["Закрити"] } } }, { "l": "uz", "t": { "Close": { "v": ["Yopish"] } } }, { "l": "zh-CN", "t": { "Close": { "v": ["关闭"] } } }, { "l": "zh-HK", "t": { "Close": { "v": ["關閉"] } } }, { "l": "zh-TW", "t": { "Close": { "v": ["關閉"] } } }];
const t21 = [{ "l": "ar", "t": { "Collapse menu": { "v": ["طي القائمة"] }, "Open menu": { "v": ["إفتَح القائمة"] } } }, { "l": "ast", "t": { "Collapse menu": { "v": ["Recoyer el menú"] }, "Open menu": { "v": ["Abrir le menú"] } } }, { "l": "br", "t": {} }, { "l": "ca", "t": {} }, { "l": "cs", "t": { "Collapse menu": { "v": ["Sbalit nabídku"] }, "Open menu": { "v": ["Otevřít nabídku"] } } }, { "l": "cs-CZ", "t": { "Collapse menu": { "v": ["Sbalit nabídku"] }, "Open menu": { "v": ["Otevřít nabídku"] } } }, { "l": "da", "t": { "Collapse menu": { "v": ["Skjul menuen"] }, "Open menu": { "v": ["Åben menu"] } } }, { "l": "de", "t": { "Collapse menu": { "v": ["Menü einklappen"] }, "Open menu": { "v": ["Menü öffnen"] } } }, { "l": "de-DE", "t": { "Collapse menu": { "v": ["Menü einklappen"] }, "Open menu": { "v": ["Menü öffnen"] } } }, { "l": "el", "t": { "Collapse menu": { "v": ["Σύμπτυξη μενού"] }, "Open menu": { "v": ["Άνοιγμα μενού"] } } }, { "l": "en-GB", "t": { "Collapse menu": { "v": ["Collapse menu"] }, "Open menu": { "v": ["Open menu"] } } }, { "l": "eo", "t": {} }, { "l": "es", "t": { "Collapse menu": { "v": ["Ocultar menú"] }, "Open menu": { "v": ["Abrir menú"] } } }, { "l": "es-AR", "t": { "Collapse menu": { "v": ["Ocultar menú"] }, "Open menu": { "v": ["Abrir menú"] } } }, { "l": "es-EC", "t": { "Collapse menu": { "v": ["Ocultar menú"] }, "Open menu": { "v": ["Abrir menú"] } } }, { "l": "es-MX", "t": { "Collapse menu": { "v": ["Ocultar menú"] }, "Open menu": { "v": ["Abrir menú"] } } }, { "l": "et-EE", "t": { "Collapse menu": { "v": ["Menüü kokkuklappimine"] }, "Open menu": { "v": ["Ava menüü"] } } }, { "l": "eu", "t": { "Collapse menu": { "v": ["Tolestu menua"] }, "Open menu": { "v": ["Ireki menua"] } } }, { "l": "fa", "t": { "Collapse menu": { "v": ["بستن فهرست"] }, "Open menu": { "v": ["باز کردن فهرست"] } } }, { "l": "fi", "t": { "Collapse menu": { "v": ["Supista valikko"] }, "Open menu": { "v": ["Avaa valikko"] } } }, { "l": "fr", "t": { "Collapse menu": { "v": ["Réduire le menu"] }, "Open menu": { "v": ["Ouvrir le menu"] } } }, { "l": "ga", "t": { "Collapse menu": { "v": ["Roghchlár Laghdaigh"] }, "Open menu": { "v": ["Roghchlár a oscailt"] } } }, { "l": "gl", "t": { "Collapse menu": { "v": ["Contraer o menú"] }, "Open menu": { "v": ["Abrir o menú"] } } }, { "l": "he", "t": { "Collapse menu": { "v": ["צמצום התפריט"] }, "Open menu": { "v": ["פתיחת תפריט"] } } }, { "l": "hu", "t": {} }, { "l": "id", "t": { "Collapse menu": { "v": ["Ciutkan menu"] }, "Open menu": { "v": ["Buka menu"] } } }, { "l": "is", "t": { "Collapse menu": { "v": ["Fella valmynd saman"] }, "Open menu": { "v": ["Opna valmynd"] } } }, { "l": "it", "t": { "Collapse menu": { "v": ["Chiudi Menu"] }, "Open menu": { "v": ["Apri il menu"] } } }, { "l": "ja", "t": { "Collapse menu": { "v": ["メニューの折りたたみ"] }, "Open menu": { "v": ["メニューを開く"] } } }, { "l": "ja-JP", "t": { "Collapse menu": { "v": ["メニューの折りたたみ"] }, "Open menu": { "v": ["メニューを開く"] } } }, { "l": "ko", "t": { "Collapse menu": { "v": ["메뉴 접기"] }, "Open menu": { "v": ["메뉴 열기"] } } }, { "l": "lo", "t": { "Collapse menu": { "v": ["ຫຍໍ້ເມນູ"] }, "Open menu": { "v": ["ເປີດເມນູ"] } } }, { "l": "lt-LT", "t": {} }, { "l": "lv", "t": {} }, { "l": "mk", "t": { "Collapse menu": { "v": ["Скриј мени"] }, "Open menu": { "v": ["Отвори мени"] } } }, { "l": "my", "t": {} }, { "l": "nb", "t": { "Collapse menu": { "v": ["Skjul meny"] }, "Open menu": { "v": ["Åpne meny"] } } }, { "l": "nl", "t": { "Collapse menu": { "v": ["Menu inklappen"] }, "Open menu": { "v": ["Menu openen"] } } }, { "l": "oc", "t": {} }, { "l": "pl", "t": { "Collapse menu": { "v": ["Zwiń menu"] }, "Open menu": { "v": ["Otwórz menu"] } } }, { "l": "pt-BR", "t": { "Collapse menu": { "v": ["Recolher menu"] }, "Open menu": { "v": ["Abrir menu"] } } }, { "l": "pt-PT", "t": { "Collapse menu": { "v": ["Ocultar menu"] }, "Open menu": { "v": ["Abrir menu"] } } }, { "l": "ro", "t": { "Collapse menu": { "v": ["Restrânge meniul"] }, "Open menu": { "v": ["Deschide meniul"] } } }, { "l": "ru", "t": { "Collapse menu": { "v": ["Свернуть меню"] }, "Open menu": { "v": ["Открыть меню"] } } }, { "l": "sk", "t": { "Collapse menu": { "v": ["Zbaliť menu"] }, "Open menu": { "v": ["Otvoriť menu"] } } }, { "l": "sl", "t": {} }, { "l": "sr", "t": { "Collapse menu": { "v": ["Сажми мени"] }, "Open menu": { "v": ["Отвори мени"] } } }, { "l": "sv", "t": { "Collapse menu": { "v": ["Dölj menyn"] }, "Open menu": { "v": ["Öppna menyn"] } } }, { "l": "tr", "t": { "Collapse menu": { "v": ["Menüyü daralt"] }, "Open menu": { "v": ["Menüyü aç"] } } }, { "l": "uk", "t": { "Collapse menu": { "v": ["Згорнути меню"] }, "Open menu": { "v": ["Відкрити меню"] } } }, { "l": "uz", "t": { "Collapse menu": { "v": ["Menyuni yig‘ish"] }, "Open menu": { "v": ["Menyuni oching"] } } }, { "l": "zh-CN", "t": { "Collapse menu": { "v": ["收起菜单"] }, "Open menu": { "v": ["打开菜单"] } } }, { "l": "zh-HK", "t": { "Collapse menu": { "v": ["折疊選單"] }, "Open menu": { "v": ["開啟選單"] } } }, { "l": "zh-TW", "t": { "Collapse menu": { "v": ["折疊選單"] }, "Open menu": { "v": ["開啟選單"] } } }];
const t22 = [{ "l": "ar", "t": {} }, { "l": "ast", "t": {} }, { "l": "br", "t": {} }, { "l": "ca", "t": {} }, { "l": "cs", "t": { "Copied": { "v": ["Zkopírováno"] }, "Copy to clipboard": { "v": ["Zkopírovat do schránky"] } } }, { "l": "cs-CZ", "t": {} }, { "l": "da", "t": {} }, { "l": "de", "t": { "Copied": { "v": ["Kopiert"] }, "Copy to clipboard": { "v": ["In die Zwischenablage kopieren"] } } }, { "l": "de-DE", "t": { "Copied": { "v": ["Kopiert"] }, "Copy to clipboard": { "v": ["In die Zwischenablage kopieren"] } } }, { "l": "el", "t": {} }, { "l": "en-GB", "t": { "Copied": { "v": ["Copied"] }, "Copy to clipboard": { "v": ["Copy to clipboard"] } } }, { "l": "eo", "t": {} }, { "l": "es", "t": {} }, { "l": "es-AR", "t": {} }, { "l": "es-EC", "t": {} }, { "l": "es-MX", "t": {} }, { "l": "et-EE", "t": { "Copied": { "v": ["Kopeeritud"] }, "Copy to clipboard": { "v": ["Kopeeri lõikelauale"] } } }, { "l": "eu", "t": {} }, { "l": "fa", "t": {} }, { "l": "fi", "t": {} }, { "l": "fr", "t": {} }, { "l": "ga", "t": { "Copied": { "v": ["Cóipeáilte"] }, "Copy to clipboard": { "v": ["Cóipeáil chuig an ghearrthaisce"] } } }, { "l": "gl", "t": { "Copied": { "v": ["Copiado"] }, "Copy to clipboard": { "v": ["Copiar ao portapapeis"] } } }, { "l": "he", "t": {} }, { "l": "hu", "t": {} }, { "l": "id", "t": {} }, { "l": "is", "t": {} }, { "l": "it", "t": {} }, { "l": "ja", "t": { "Copied": { "v": ["コピーされました"] }, "Copy to clipboard": { "v": ["クリップボードにコピー"] } } }, { "l": "ja-JP", "t": {} }, { "l": "ko", "t": {} }, { "l": "lo", "t": { "Copied": { "v": ["ສຳເນົາແລ້ວ"] }, "Copy to clipboard": { "v": ["ສຳເນົາໃສ່ຄລິບບອດ"] } } }, { "l": "lt-LT", "t": {} }, { "l": "lv", "t": {} }, { "l": "mk", "t": {} }, { "l": "my", "t": {} }, { "l": "nb", "t": {} }, { "l": "nl", "t": {} }, { "l": "oc", "t": {} }, { "l": "pl", "t": {} }, { "l": "pt-BR", "t": { "Copied": { "v": ["Copiado"] }, "Copy to clipboard": { "v": ["Copiar para a área de transferência"] } } }, { "l": "pt-PT", "t": {} }, { "l": "ro", "t": {} }, { "l": "ru", "t": {} }, { "l": "sk", "t": {} }, { "l": "sl", "t": {} }, { "l": "sr", "t": {} }, { "l": "sv", "t": {} }, { "l": "tr", "t": { "Copied": { "v": ["Kopyalandı"] }, "Copy to clipboard": { "v": ["Panoya kopyalandı"] } } }, { "l": "uk", "t": {} }, { "l": "uz", "t": {} }, { "l": "zh-CN", "t": {} }, { "l": "zh-HK", "t": { "Copied": { "v": ["已被複製"] }, "Copy to clipboard": { "v": ["複製到剪貼簿"] } } }, { "l": "zh-TW", "t": {} }];
const t23 = [{ "l": "ar", "t": { "Edit item": { "v": ["تعديل عنصر"] } } }, { "l": "ast", "t": { "Edit item": { "v": ["Editar l'elementu"] } } }, { "l": "br", "t": {} }, { "l": "ca", "t": { "Edit item": { "v": ["Edita l'element"] } } }, { "l": "cs", "t": { "Edit item": { "v": ["Upravit položku"] } } }, { "l": "cs-CZ", "t": { "Edit item": { "v": ["Upravit položku"] } } }, { "l": "da", "t": { "Edit item": { "v": ["Rediger emne"] } } }, { "l": "de", "t": { "Edit item": { "v": ["Element bearbeiten"] } } }, { "l": "de-DE", "t": { "Edit item": { "v": ["Element bearbeiten"] } } }, { "l": "el", "t": { "Edit item": { "v": ["Επεξεργασία αντικειμένου"] } } }, { "l": "en-GB", "t": { "Edit item": { "v": ["Edit item"] } } }, { "l": "eo", "t": {} }, { "l": "es", "t": { "Edit item": { "v": ["Editar elemento"] } } }, { "l": "es-AR", "t": { "Edit item": { "v": ["Editar elemento"] } } }, { "l": "es-EC", "t": { "Edit item": { "v": ["Editar elemento"] } } }, { "l": "es-MX", "t": { "Edit item": { "v": ["Editar elemento"] } } }, { "l": "et-EE", "t": { "Edit item": { "v": ["Muuda objekti"] } } }, { "l": "eu", "t": { "Edit item": { "v": ["Editatu elementua"] } } }, { "l": "fa", "t": { "Edit item": { "v": ["ویرایش مورد"] } } }, { "l": "fi", "t": { "Edit item": { "v": ["Muokkaa kohdetta"] } } }, { "l": "fr", "t": { "Edit item": { "v": ["Éditer l'élément"] } } }, { "l": "ga", "t": { "Edit item": { "v": ["Cuir mír in eagar"] } } }, { "l": "gl", "t": { "Edit item": { "v": ["Editar o elemento"] } } }, { "l": "he", "t": { "Edit item": { "v": ["עריכת פריט"] } } }, { "l": "hu", "t": { "Edit item": { "v": ["Elem szerkesztése"] } } }, { "l": "id", "t": { "Edit item": { "v": ["Edit item"] } } }, { "l": "is", "t": { "Edit item": { "v": ["Breyta atriði"] } } }, { "l": "it", "t": { "Edit item": { "v": ["Modifica l'elemento"] } } }, { "l": "ja", "t": { "Edit item": { "v": ["編集"] } } }, { "l": "ja-JP", "t": { "Edit item": { "v": ["編集"] } } }, { "l": "ko", "t": { "Edit item": { "v": ["항목 수정"] } } }, { "l": "lo", "t": { "Edit item": { "v": ["ແກ້ໄຂລາຍການ"] } } }, { "l": "lt-LT", "t": {} }, { "l": "lv", "t": {} }, { "l": "mk", "t": { "Edit item": { "v": ["Уреди"] } } }, { "l": "my", "t": {} }, { "l": "nb", "t": { "Edit item": { "v": ["Rediger"] } } }, { "l": "nl", "t": { "Edit item": { "v": ["Item bewerken"] } } }, { "l": "oc", "t": {} }, { "l": "pl", "t": { "Edit item": { "v": ["Edytuj element"] } } }, { "l": "pt-BR", "t": { "Edit item": { "v": ["Editar item"] } } }, { "l": "pt-PT", "t": { "Edit item": { "v": ["Editar item"] } } }, { "l": "ro", "t": { "Edit item": { "v": ["Editați elementul"] } } }, { "l": "ru", "t": { "Edit item": { "v": ["Изменить элемент"] } } }, { "l": "sk", "t": { "Edit item": { "v": ["Upraviť položku"] } } }, { "l": "sl", "t": { "Edit item": { "v": ["Uredi predmet"] } } }, { "l": "sr", "t": { "Edit item": { "v": ["Уреди ставку"] } } }, { "l": "sv", "t": { "Edit item": { "v": ["Redigera objekt"] } } }, { "l": "tr", "t": { "Edit item": { "v": ["Ögeyi düzenle"] } } }, { "l": "uk", "t": { "Edit item": { "v": ["Редагувати елемент"] } } }, { "l": "uz", "t": { "Edit item": { "v": ["Elementni tahrirlash"] } } }, { "l": "zh-CN", "t": { "Edit item": { "v": ["编辑项目"] } } }, { "l": "zh-HK", "t": { "Edit item": { "v": ["編輯項目"] } } }, { "l": "zh-TW", "t": { "Edit item": { "v": ["編輯項目"] } } }];
const t25 = [{ "l": "ar", "t": { "Enter link": { "v": ["أدخِل الرابط"] } } }, { "l": "ast", "t": { "Enter link": { "v": ["Introducir l'enllaz"] } } }, { "l": "br", "t": {} }, { "l": "ca", "t": {} }, { "l": "cs", "t": { "Enter link": { "v": ["Zadat odkaz"] } } }, { "l": "cs-CZ", "t": { "Enter link": { "v": ["Zadat odkaz"] } } }, { "l": "da", "t": { "Enter link": { "v": ["Indtast link"] } } }, { "l": "de", "t": { "Enter link": { "v": ["Link eingeben"] } } }, { "l": "de-DE", "t": { "Enter link": { "v": ["Link eingeben"] } } }, { "l": "el", "t": { "Enter link": { "v": ["Εισάγετε σύνδεσμο"] } } }, { "l": "en-GB", "t": { "Enter link": { "v": ["Enter link"] } } }, { "l": "eo", "t": {} }, { "l": "es", "t": { "Enter link": { "v": ["Ingrese enlace"] } } }, { "l": "es-AR", "t": { "Enter link": { "v": ["Ingresar enlace"] } } }, { "l": "es-EC", "t": { "Enter link": { "v": ["Ingresar enlace"] } } }, { "l": "es-MX", "t": { "Enter link": { "v": ["Ingresar enlace"] } } }, { "l": "et-EE", "t": { "Enter link": { "v": ["Sisesta link"] } } }, { "l": "eu", "t": { "Enter link": { "v": ["Sartu esteka"] } } }, { "l": "fa", "t": { "Enter link": { "v": ["لینک را وارد کنید"] } } }, { "l": "fi", "t": { "Enter link": { "v": ["Kirjoita linkki"] } } }, { "l": "fr", "t": { "Enter link": { "v": ["Saisissez le lien"] } } }, { "l": "ga", "t": { "Enter link": { "v": ["Cuir isteach nasc"] } } }, { "l": "gl", "t": { "Enter link": { "v": ["Introducir a ligazón"] } } }, { "l": "he", "t": { "Enter link": { "v": ["מילוי קישור"] } } }, { "l": "hu", "t": {} }, { "l": "id", "t": { "Enter link": { "v": ["Masukkan tautan"] } } }, { "l": "is", "t": { "Enter link": { "v": ["Settu inn tengil"] } } }, { "l": "it", "t": { "Enter link": { "v": ["Inserire il link"] } } }, { "l": "ja", "t": { "Enter link": { "v": ["リンクを入力する"] } } }, { "l": "ja-JP", "t": { "Enter link": { "v": ["リンクを入力する"] } } }, { "l": "ko", "t": { "Enter link": { "v": ["링크 입력"] } } }, { "l": "lo", "t": { "Enter link": { "v": ["ປ້ອນລິງ"] } } }, { "l": "lt-LT", "t": {} }, { "l": "lv", "t": {} }, { "l": "mk", "t": { "Enter link": { "v": ["Внеси линк"] } } }, { "l": "my", "t": {} }, { "l": "nb", "t": { "Enter link": { "v": ["Skriv inn lenken"] } } }, { "l": "nl", "t": { "Enter link": { "v": ["Link invoeren"] } } }, { "l": "oc", "t": {} }, { "l": "pl", "t": { "Enter link": { "v": ["Wprowadź link"] } } }, { "l": "pt-BR", "t": { "Enter link": { "v": ["Insira o link"] } } }, { "l": "pt-PT", "t": { "Enter link": { "v": ["Inserir hiperligação"] } } }, { "l": "ro", "t": { "Enter link": { "v": ["Introduceți link-ul"] } } }, { "l": "ru", "t": { "Enter link": { "v": ["Введите ссылку"] } } }, { "l": "sk", "t": { "Enter link": { "v": ["Vložiť link"] } } }, { "l": "sl", "t": {} }, { "l": "sr", "t": { "Enter link": { "v": ["Унесите линк"] } } }, { "l": "sv", "t": { "Enter link": { "v": ["Ange länk"] } } }, { "l": "tr", "t": { "Enter link": { "v": ["Bağlantıyı yazın"] } } }, { "l": "uk", "t": { "Enter link": { "v": ["Зазначте посилання"] } } }, { "l": "uz", "t": { "Enter link": { "v": ["Havolani kiriting"] } } }, { "l": "zh-CN", "t": { "Enter link": { "v": ["输入链接"] } } }, { "l": "zh-HK", "t": { "Enter link": { "v": ["輸入連結"] } } }, { "l": "zh-TW", "t": { "Enter link": { "v": ["輸入連結"] } } }];
const t28 = [{ "l": "ar", "t": { "Hide details": { "v": ["أخفِ التفاصيل"] }, "Rename project": { "v": ["تغيير اسم المشروع"] }, "Show details": { "v": ["أظهِر التفاصيل"] } } }, { "l": "ast", "t": {} }, { "l": "br", "t": {} }, { "l": "ca", "t": {} }, { "l": "cs", "t": { "Hide details": { "v": ["Skrýt podrobnosti"] }, "Rename project": { "v": ["Přejmenovat projekt"] }, "Show details": { "v": ["Zobrazit podrobnosti"] } } }, { "l": "cs-CZ", "t": {} }, { "l": "da", "t": { "Hide details": { "v": ["Skjul detaljer"] }, "Rename project": { "v": ["Omdøb projekt"] }, "Show details": { "v": ["Vis detaljer"] } } }, { "l": "de", "t": { "Hide details": { "v": ["Details ausblenden"] }, "Rename project": { "v": ["Projekt umbenennen"] }, "Show details": { "v": ["Details anzeigen"] } } }, { "l": "de-DE", "t": { "Hide details": { "v": ["Details ausblenden"] }, "Rename project": { "v": ["Projekt umbenennen"] }, "Show details": { "v": ["Details anzeigen"] } } }, { "l": "el", "t": { "Hide details": { "v": ["Απόκρυψη λεπτομερειών"] }, "Rename project": { "v": ["Μετονομασία έργου"] }, "Show details": { "v": ["Εμφάνιση λεπτομερειών"] } } }, { "l": "en-GB", "t": { "Hide details": { "v": ["Hide details"] }, "Rename project": { "v": ["Rename project"] }, "Show details": { "v": ["Show details"] } } }, { "l": "eo", "t": {} }, { "l": "es", "t": {} }, { "l": "es-AR", "t": { "Hide details": { "v": ["Ocultar detalles"] }, "Rename project": { "v": ["Renombrar proyecto"] }, "Show details": { "v": ["Mostrar detalles"] } } }, { "l": "es-EC", "t": {} }, { "l": "es-MX", "t": { "Hide details": { "v": ["Ocultar detalles"] }, "Rename project": { "v": ["Renombrar proyecto"] }, "Show details": { "v": ["Mostrar detalles"] } } }, { "l": "et-EE", "t": { "Hide details": { "v": ["Peida üksikasjad"] }, "Rename project": { "v": ["Muuda projekti nime"] }, "Show details": { "v": ["Näita üksikasju"] } } }, { "l": "eu", "t": {} }, { "l": "fa", "t": { "Hide details": { "v": ["پنهان کردن جزئیات"] }, "Rename project": { "v": ["تغییر نام پروژه"] }, "Show details": { "v": ["نمایش جزئیات"] } } }, { "l": "fi", "t": { "Hide details": { "v": ["Piilota yksityiskohdat"] }, "Rename project": { "v": ["Nimeä projekti"] }, "Show details": { "v": ["Näytä yksityiskohdat"] } } }, { "l": "fr", "t": { "Hide details": { "v": ["Masquer les détails"] }, "Rename project": { "v": ["Renommer le projet"] }, "Show details": { "v": ["Afficher les détails"] } } }, { "l": "ga", "t": { "Hide details": { "v": ["Folaigh sonraí"] }, "Rename project": { "v": ["Athainmnigh an tionscadal"] }, "Show details": { "v": ["Taispeáin sonraí"] } } }, { "l": "gl", "t": { "Hide details": { "v": ["Agochar os detalles"] }, "Rename project": { "v": ["Cambiar o nome do proxecto"] }, "Show details": { "v": ["Amosar os detalles"] } } }, { "l": "he", "t": {} }, { "l": "hu", "t": {} }, { "l": "id", "t": {} }, { "l": "is", "t": { "Hide details": { "v": ["Fela nánari upplýsingar"] }, "Rename project": { "v": ["Endurnefna verkefni"] }, "Show details": { "v": ["Birta nánari upplýsingar"] } } }, { "l": "it", "t": {} }, { "l": "ja", "t": { "Hide details": { "v": ["詳細を非表示"] }, "Rename project": { "v": ["プロジェクト名を変更"] }, "Show details": { "v": ["詳細の表示"] } } }, { "l": "ja-JP", "t": {} }, { "l": "ko", "t": { "Hide details": { "v": ["세부 사항 숨기기"] }, "Rename project": { "v": ["프로젝트 이름 변경"] }, "Show details": { "v": ["세부 사항 보기"] } } }, { "l": "lo", "t": { "Hide details": { "v": ["ເຊື່ອງລາຍລະອຽດ"] }, "Rename project": { "v": ["ປ່ຽນຊື່ໂຄງການ"] }, "Show details": { "v": ["ສະແດງລາຍລະອຽດ"] } } }, { "l": "lt-LT", "t": {} }, { "l": "lv", "t": {} }, { "l": "mk", "t": { "Hide details": { "v": ["Сокриј детали"] }, "Rename project": { "v": ["Преименувај проект"] }, "Show details": { "v": ["Прикажи детали"] } } }, { "l": "my", "t": {} }, { "l": "nb", "t": { "Hide details": { "v": ["Skjul detaljer"] }, "Rename project": { "v": ["Gi prosjekt nytt navn"] }, "Show details": { "v": ["Vis detaljer"] } } }, { "l": "nl", "t": { "Hide details": { "v": ["Details verbergen"] }, "Rename project": { "v": ["Project hernoemen"] }, "Show details": { "v": ["Details weergeven"] } } }, { "l": "oc", "t": {} }, { "l": "pl", "t": { "Hide details": { "v": ["Ukryj szczegóły"] }, "Rename project": { "v": ["Zmień nazwę projektu"] }, "Show details": { "v": ["Pokaż szczegóły"] } } }, { "l": "pt-BR", "t": { "Hide details": { "v": ["Ocultar detalhes"] }, "Rename project": { "v": ["Renomear projeto"] }, "Show details": { "v": ["Mostrar detalhes"] } } }, { "l": "pt-PT", "t": { "Hide details": { "v": ["Ocultar detalhes"] }, "Rename project": { "v": ["Alterar nome do projeto"] }, "Show details": { "v": ["Ver detalhes"] } } }, { "l": "ro", "t": {} }, { "l": "ru", "t": { "Hide details": { "v": ["Скрыть подробности"] }, "Rename project": { "v": ["Переименовать проект"] }, "Show details": { "v": ["Показать детали"] } } }, { "l": "sk", "t": { "Hide details": { "v": ["Skryť detaily"] }, "Rename project": { "v": ["Premenovať projekt"] }, "Show details": { "v": ["Zobraziť detaily"] } } }, { "l": "sl", "t": {} }, { "l": "sr", "t": { "Hide details": { "v": ["Сакриј детаље"] }, "Rename project": { "v": ["Промени име пројекта"] }, "Show details": { "v": ["Прикажи детаље"] } } }, { "l": "sv", "t": { "Hide details": { "v": ["Göm detaljer"] }, "Rename project": { "v": ["Byt namn på projektet"] }, "Show details": { "v": ["Visa detaljer"] } } }, { "l": "tr", "t": { "Hide details": { "v": ["Ayrıntıları gizle"] }, "Rename project": { "v": ["Projeyi yeniden adlandır"] }, "Show details": { "v": ["Ayrıntıları görüntüle"] } } }, { "l": "uk", "t": { "Hide details": { "v": ["Сховати деталі"] }, "Rename project": { "v": ["Перейменувати проєкт"] }, "Show details": { "v": ["Показати деталі"] } } }, { "l": "uz", "t": { "Hide details": { "v": ["Tafsilotlarni yashirish"] }, "Rename project": { "v": ["Loyiha nomini o'zgartirish"] }, "Show details": { "v": ["Tafsilotlarni ko'rsatish"] } } }, { "l": "zh-CN", "t": { "Hide details": { "v": ["隐藏细节"] }, "Rename project": { "v": ["重命名项目"] }, "Show details": { "v": ["显示细节"] } } }, { "l": "zh-HK", "t": { "Hide details": { "v": ["隱藏詳情"] }, "Rename project": { "v": ["重命名方案"] }, "Show details": { "v": ["顯示詳情"] } } }, { "l": "zh-TW", "t": { "Hide details": { "v": ["隱藏詳細資料"] }, "Rename project": { "v": ["重新命名專案"] }, "Show details": { "v": ["顯示詳細資訊"] } } }];
const t32 = [{ "l": "ar", "t": { 'Load more "{options}"': { "v": ['تحميل المزيد من "{options}" '] }, "Raw link {options}": { "v": [" الرابط الخام raw link ـ {options}"] }, "Start typing to search": { "v": ["إبدإ كتابة مفردات البحث"] } } }, { "l": "ast", "t": { 'Load more "{options}"': { "v": ["Cargar más «{options}»"] }, "Raw link {options}": { "v": ["Enllaz en bruto {optiones}"] }, "Start typing to search": { "v": ["Comienza a escribir pa buscar"] } } }, { "l": "br", "t": {} }, { "l": "ca", "t": {} }, { "l": "cs", "t": { 'Load more "{options}"': { "v": ["Načíst další „{options}“"] }, "Raw link {options}": { "v": ["Holý odkaz {options}"] }, "Start typing to search": { "v": ["Vyhledávejte psaním"] } } }, { "l": "cs-CZ", "t": { 'Load more "{options}"': { "v": ["Načíst další „{options}“"] }, "Raw link {options}": { "v": ["Holý odkaz {options}"] }, "Start typing to search": { "v": ["Vyhledávejte psaním"] } } }, { "l": "da", "t": { 'Load more "{options}"': { "v": ['Indlæs flere "{options}"'] }, "Raw link {options}": { "v": ["Rå link {options}"] }, "Start typing to search": { "v": ["Begynd at skrive for at søge"] } } }, { "l": "de", "t": { 'Load more "{options}"': { "v": ['Weitere "{options}" laden'] }, "Raw link {options}": { "v": ["Unverarbeiteter Link {options}"] }, "Start typing to search": { "v": ["Mit der Eingabe beginnen, um zu suchen"] } } }, { "l": "de-DE", "t": { 'Load more "{options}"': { "v": ['Weitere "{options}" laden'] }, "Raw link {options}": { "v": ["Unverarbeiteter Link {options}"] }, "Start typing to search": { "v": ["Mit der Eingabe beginnen, um zu suchen"] } } }, { "l": "el", "t": { 'Load more "{options}"': { "v": ['Φόρτωση περισσότερων "{options}"'] }, "Raw link {options}": { "v": ["Ακατέργαστος σύνδεσμος {options}"] }, "Start typing to search": { "v": ["Ξεκινήστε να πληκτρολογείτε για αναζήτηση"] } } }, { "l": "en-GB", "t": { 'Load more "{options}"': { "v": ['Load more "{options}"'] }, "Raw link {options}": { "v": ["Raw link {options}"] }, "Start typing to search": { "v": ["Start typing to search"] } } }, { "l": "eo", "t": {} }, { "l": "es", "t": { 'Load more "{options}"': { "v": ['Cargar más "{options}"'] }, "Raw link {options}": { "v": ["Enlace directo {options}"] }, "Start typing to search": { "v": ["Comience a escribir para buscar"] } } }, { "l": "es-AR", "t": { 'Load more "{options}"': { "v": ['Cargar más "{options}"'] }, "Raw link {options}": { "v": ["Enlace directo {options}"] }, "Start typing to search": { "v": ["Comience a escribir para buscar"] } } }, { "l": "es-EC", "t": { "Raw link {options}": { "v": ["Enlace directo {options}"] }, "Start typing to search": { "v": ["Comienza a escribir para buscar"] } } }, { "l": "es-MX", "t": { 'Load more "{options}"': { "v": ['Cargar más "{options}"'] }, "Raw link {options}": { "v": ["Enlace directo {options}"] }, "Start typing to search": { "v": ["Comience a escribir para buscar"] } } }, { "l": "et-EE", "t": { 'Load more "{options}"': { "v": ["Laadi veel „{options}“"] }, "Raw link {options}": { "v": ["Töötlemata link: {options}"] }, "Start typing to search": { "v": ["Alusta otsinguks sisestamist"] } } }, { "l": "eu", "t": { 'Load more "{options}"': { "v": ['Kargatu "{options}" gehiago'] }, "Raw link {options}": { "v": ["Formaturik gabeko esteka {aukerak}"] }, "Start typing to search": { "v": ["Hasi idazten bilatzeko"] } } }, { "l": "fa", "t": { 'Load more "{options}"': { "v": ['بارگذاری بیشتر "{options}"'] }, "Raw link {options}": { "v": ["پیوند خام {options}"] }, "Start typing to search": { "v": ["برای جستجو تایپ کنید"] } } }, { "l": "fi", "t": { 'Load more "{options}"': { "v": ['Lataa lisää "{options}"'] }, "Raw link {options}": { "v": ["Raaka linkki {options}"] }, "Start typing to search": { "v": ["Aloita kirjoittaminen hakeaksesi"] } } }, { "l": "fr", "t": { 'Load more "{options}"': { "v": [`Charger d'avantage "{options}"`] }, "Raw link {options}": { "v": ["Lien brut {options}"] }, "Start typing to search": { "v": ["Commencez à écrire pour rechercher"] } } }, { "l": "ga", "t": { 'Load more "{options}"': { "v": ['Luchtaigh tuilleadh "{options}"'] }, "Raw link {options}": { "v": ["Nasc amh {roghanna}"] }, "Start typing to search": { "v": ["Tosaigh ag clóscríobh chun cuardach a dhéanamh"] } } }, { "l": "gl", "t": { 'Load more "{options}"': { "v": ["Cargar máis «{options}»"] }, "Raw link {options}": { "v": ["Ligazón sen procesar {options}"] }, "Start typing to search": { "v": ["Comece a escribir para buscar"] } } }, { "l": "he", "t": { "Raw link {options}": { "v": ["קישור גולמי {options}"] }, "Start typing to search": { "v": ["התחלת הקלדה מחפשת"] } } }, { "l": "hu", "t": {} }, { "l": "id", "t": { 'Load more "{options}"': { "v": ['Muat "{options}" lainnya'] }, "Raw link {options}": { "v": ["Tautan mentah {options}"] }, "Start typing to search": { "v": ["Ketik untuk mulai mencari"] } } }, { "l": "is", "t": { 'Load more "{options}"': { "v": ['Hlaða inn fleiri "{options}"'] }, "Raw link {options}": { "v": ["Hrár tengill {options}"] }, "Start typing to search": { "v": ["Byrjaðu að skrifa til að leita"] } } }, { "l": "it", "t": { 'Load more "{options}"': { "v": ['Carica più "{options}"'] }, "Raw link {options}": { "v": ["Raw link {options}"] }, "Start typing to search": { "v": ["Iniziare a digitare per effettuare la ricerca"] } } }, { "l": "ja", "t": { 'Load more "{options}"': { "v": ['"{options}" をもっと読み込む'] }, "Raw link {options}": { "v": ["未加工のリンク {options}"] }, "Start typing to search": { "v": ["入力を開始して検索します"] } } }, { "l": "ja-JP", "t": { 'Load more "{options}"': { "v": ['"{options}" をもっと読み込む'] }, "Raw link {options}": { "v": ["未加工のリンク {options}"] }, "Start typing to search": { "v": ["入力を開始して検索します"] } } }, { "l": "ko", "t": { 'Load more "{options}"': { "v": ['"{options}" 더 불러오기'] }, "Raw link {options}": { "v": ["{options} 원본 링크"] }, "Start typing to search": { "v": ["입력하여 검색"] } } }, { "l": "lo", "t": { 'Load more "{options}"': { "v": ["ໂຫຼດ “{options}” ເພີ່ມເຕີມ"] }, "Raw link {options}": { "v": ["ລິງດິບ {options}"] }, "Start typing to search": { "v": ["ເລີ່ມພິມເພື່ອຄົ້ນຫາ"] } } }, { "l": "lt-LT", "t": {} }, { "l": "lv", "t": {} }, { "l": "mk", "t": { 'Load more "{options}"': { "v": ['Вчитај повеќе "{options}"'] }, "Raw link {options}": { "v": ["Суров линк {options}"] }, "Start typing to search": { "v": ["Почни да пишуваш за пребарување"] } } }, { "l": "my", "t": {} }, { "l": "nb", "t": { 'Load more "{options}"': { "v": ['Last inn flere "{options}"'] }, "Raw link {options}": { "v": ["Rå lenke {options}"] }, "Start typing to search": { "v": ["Start å skrive for å søke"] } } }, { "l": "nl", "t": { 'Load more "{options}"': { "v": ['Meer "{options}" laden'] }, "Raw link {options}": { "v": ["Ruwe link {options}"] }, "Start typing to search": { "v": ["Start met typen om te zoeken"] } } }, { "l": "oc", "t": {} }, { "l": "pl", "t": { 'Load more "{options}"': { "v": ['Załaduj więcej "{options}"'] }, "Raw link {options}": { "v": ["Surowy odnośnik {options}"] }, "Start typing to search": { "v": ["Zacznij pisać, aby wyszukać"] } } }, { "l": "pt-BR", "t": { 'Load more "{options}"': { "v": ['Carregar mais "{options}"'] }, "Raw link {options}": { "v": ["Link bruto {options}"] }, "Start typing to search": { "v": ["Comece a digitar para pesquisar"] } } }, { "l": "pt-PT", "t": { 'Load more "{options}"': { "v": ['Carregar mais "{options}"'] }, "Raw link {options}": { "v": ["Link inicial {options}"] }, "Start typing to search": { "v": ["Comece a digitar para pesquisar"] } } }, { "l": "ro", "t": { 'Load more "{options}"': { "v": ['Încarcă mai multe "{options}"'] }, "Raw link {options}": { "v": ["Link brut {options}"] }, "Start typing to search": { "v": ["Tastați pentru căutare"] } } }, { "l": "ru", "t": { 'Load more "{options}"': { "v": ['Загрузить больше "{options}""'] }, "Raw link {options}": { "v": ["Необработанная ссылка {options}"] }, "Start typing to search": { "v": ["Начните вводить текст для поиска"] } } }, { "l": "sk", "t": { 'Load more "{options}"': { "v": ['Načítať viac "{options}"'] }, "Raw link {options}": { "v": ["Raw odkaz {options}"] }, "Start typing to search": { "v": ["Začnite písať pre vyhľadávanie"] } } }, { "l": "sl", "t": {} }, { "l": "sr", "t": { 'Load more "{options}"': { "v": ["Учитај још „{options}”"] }, "Raw link {options}": { "v": ["Сирови линк {options}"] }, "Start typing to search": { "v": ["Покрените претрагу куцањем"] } } }, { "l": "sv", "t": { 'Load more "{options}"': { "v": ['Ladda fler "{options}"'] }, "Raw link {options}": { "v": ["Oformaterad länk {options}"] }, "Start typing to search": { "v": ["Börja skriva för att söka"] } } }, { "l": "tr", "t": { 'Load more "{options}"': { "v": ['Diğer "{options}"'] }, "Raw link {options}": { "v": ["Ham bağlantı {options}"] }, "Start typing to search": { "v": ["Aramak için yazmaya başlayın"] } } }, { "l": "uk", "t": { 'Load more "{options}"': { "v": ['Завантажити більше "{options}"'] }, "Raw link {options}": { "v": ["Пряме посилання {options}"] }, "Start typing to search": { "v": ["Почніть вводити для пошуку"] } } }, { "l": "uz", "t": { 'Load more "{options}"': { "v": [`Ko'proq yuklash "{options}"`] }, "Raw link {options}": { "v": [" {options}satr havolasi"] }, "Start typing to search": { "v": ["Qidirish uchun yozishni boshlang"] } } }, { "l": "zh-CN", "t": { 'Load more "{options}"': { "v": ["加载更多 “{options}”"] }, "Raw link {options}": { "v": ["原始链接 {options}"] }, "Start typing to search": { "v": ["开始输入以进行搜索"] } } }, { "l": "zh-HK", "t": { 'Load more "{options}"': { "v": ['載入更多 "{options}"'] }, "Raw link {options}": { "v": ["原始連結 {options}"] }, "Start typing to search": { "v": ["開始輸入以進行搜尋"] } } }, { "l": "zh-TW", "t": { 'Load more "{options}"': { "v": ["載入更多「{options}」"] }, "Raw link {options}": { "v": ["原始連結 {options}"] }, "Start typing to search": { "v": ["開始輸入以進行搜尋"] } } }];
const t34 = [{ "l": "ar", "t": { "No link provider found": { "v": ["لا يوجد أيّ مزود روابط link provider"] }, "Write a message …": { "v": ["أكتب رسالة ..."] } } }, { "l": "ast", "t": { "No link provider found": { "v": ["Nun s'atopó nengún fornidor d'enllaces"] }, "Write a message …": { "v": ["Escribi un mensaxe…"] } } }, { "l": "br", "t": {} }, { "l": "ca", "t": {} }, { "l": "cs", "t": { "Message limit of %n character reached": { "p": "Message limit of %n characters reached", "v": ["Dosaženo limitu počtu %n znaku zprávy", "Dosaženo limitu počtu %n znaků zprávy", "Dosaženo limitu počtu %n znaků zprávy", "Dosaženo limitu počtu %n znaků zprávy"] }, "No link provider found": { "v": ["Nenalezen žádný poskytovatel odkazů"] }, "Write a message …": { "v": ["Napište zprávu …"] } } }, { "l": "cs-CZ", "t": { "No link provider found": { "v": ["Nenalezen žádný poskytovatel odkazů"] }, "Write a message …": { "v": ["Napsat zprávu…"] } } }, { "l": "da", "t": { "Message limit of %n character reached": { "p": "Message limit of %n characters reached", "v": ["Beskedgrænsen på %n tegn er nået", "Beskedgrænsen på %n tegn er nået"] }, "No link provider found": { "v": ["Ingen linkudbyder fundet"] }, "Write a message …": { "v": ["Skriv en besked ..."] } } }, { "l": "de", "t": { "Message limit of %n character reached": { "p": "Message limit of %n characters reached", "v": ["Nachrichtenlimit von %n Zeichen erreicht", "Nachrichtenlimit von %n Zeichen erreicht"] }, "No link provider found": { "v": ["Kein Linkanbieter gefunden"] }, "Write a message …": { "v": ["Nachricht schreiben …"] } } }, { "l": "de-DE", "t": { "Message limit of %n character reached": { "p": "Message limit of %n characters reached", "v": ["Nachrichtenlimit von %n Zeichen erreicht", "Nachrichtenlimit von %n Zeichen erreicht"] }, "No link provider found": { "v": ["Kein Linkanbieter gefunden"] }, "Write a message …": { "v": ["Nachricht schreiben …"] } } }, { "l": "el", "t": { "Message limit of %n character reached": { "p": "Message limit of %n characters reached", "v": ["Έφτασε το όριο μηνύματος των %n χαρακτήρων", "Έφτασε το όριο μηνύματος των %n χαρακτήρων"] }, "No link provider found": { "v": ["Δεν βρέθηκε πάροχος συνδέσμου"] }, "Write a message …": { "v": ["Γράψτε ένα μήνυμα …"] } } }, { "l": "en-GB", "t": { "Message limit of %n character reached": { "p": "Message limit of %n characters reached", "v": ["Message limit of %n character reached", "Message limit of %n characters reached"] }, "No link provider found": { "v": ["No link provider found"] }, "Write a message …": { "v": ["Write a message …"] } } }, { "l": "eo", "t": {} }, { "l": "es", "t": { "No link provider found": { "v": ["No se encontró ningún proveedor de enlaces"] }, "Write a message …": { "v": ["Escriba un mensaje ..."] } } }, { "l": "es-AR", "t": { "No link provider found": { "v": ["No se encontró ningún proveedor de enlaces"] }, "Write a message …": { "v": ["Escriba un mensaje ..."] } } }, { "l": "es-EC", "t": { "No link provider found": { "v": ["No se encontró ningún proveedor de enlaces"] } } }, { "l": "es-MX", "t": { "No link provider found": { "v": ["No se encontró ningún proveedor de enlaces"] }, "Write a message …": { "v": ["Escriba un mensaje ..."] } } }, { "l": "et-EE", "t": { "Message limit of %n character reached": { "p": "Message limit of %n characters reached", "v": ["Sõnumi piirarv %n tähemärk on käes", "Sõnumi piirarv %n tähemärki on käes"] }, "No link provider found": { "v": ["Lingi pakkujat ei leitud"] }, "Write a message …": { "v": ["Koosta sõnum …"] } } }, { "l": "eu", "t": { "No link provider found": { "v": ["Ez da aurkitu esteka-hornitzailerik"] }, "Write a message …": { "v": ["Idatzi mezu bat…"] } } }, { "l": "fa", "t": { "No link provider found": { "v": ["هیچ ارائه‌دهنده پیوندی یافت نشد"] }, "Write a message …": { "v": ["یک پیام بنویسید ..."] } } }, { "l": "fi", "t": { "No link provider found": { "v": ["Linkin tarjoajia ei löydetty"] }, "Write a message …": { "v": ["Kirjoita viesti…"] } } }, { "l": "fr", "t": { "Message limit of %n character reached": { "p": "Message limit of %n characters reached", "v": ["Limite de messages de %n caractère atteinte", "Limite de messages de %n caractères atteinte", "Limite de messages de %n caractères atteinte"] }, "No link provider found": { "v": ["Aucun fournisseur de lien trouvé"] }, "Write a message …": { "v": ["Ecrire un message..."] } } }, { "l": "ga", "t": { "Message limit of %n character reached": { "p": "Message limit of %n characters reached", "v": ["Sroicheadh ​​teorainn teachtaireachta de %n carachtar", "Sroicheadh ​​teorainn teachtaireachta de %n carachtar", "Sroicheadh ​​teorainn teachtaireachta de %n carachtar", "Sroicheadh ​​teorainn teachtaireachta de %n carachtar", "Sroicheadh ​​teorainn teachtaireachta de %n carachtar"] }, "No link provider found": { "v": ["Níor aimsíodh aon soláthraí naisc"] }, "Write a message …": { "v": ["Scríobh teachtaireacht …"] } } }, { "l": "gl", "t": { "Message limit of %n character reached": { "p": "Message limit of %n characters reached", "v": ["Acadouse o límite de %n caracter por mensaxe", "Acadouse o límite de %n caracteres por mensaxe"] }, "No link provider found": { "v": ["Non se atopou ningún provedor de ligazóns"] }, "Write a message …": { "v": ["Escribir unha mensaxe…"] } } }, { "l": "he", "t": { "No link provider found": { "v": ["לא נמצא ספק קישורים"] } } }, { "l": "hu", "t": {} }, { "l": "id", "t": { "No link provider found": { "v": ["Tidak ada penyedia tautan yang ditemukan"] }, "Write a message …": { "v": ["Tulis pesan ..."] } } }, { "l": "is", "t": { "No link provider found": { "v": ["Engin tenglaveita fannst"] }, "Write a message …": { "v": ["Skrifaðu skilaboð …"] } } }, { "l": "it", "t": { "No link provider found": { "v": ["Nessun fornitore di link trovato"] }, "Write a message …": { "v": ["Scrivi un messaggio ..."] } } }, { "l": "ja", "t": { "Message limit of %n character reached": { "p": "Message limit of %n characters reached", "v": ["メッセージの%n文字の制限に達しました"] }, "No link provider found": { "v": ["リンクプロバイダーが見つかりません"] }, "Write a message …": { "v": ["メッセージを書く ..."] } } }, { "l": "ja-JP", "t": { "No link provider found": { "v": ["リンクプロバイダーが見つかりません"] }, "Write a message …": { "v": ["メッセージを書く ..."] } } }, { "l": "ko", "t": { "Message limit of %n character reached": { "p": "Message limit of %n characters reached", "v": ["메시지 제한 %n자에 도달"] }, "No link provider found": { "v": ["링크 제공자 없음"] }, "Write a message …": { "v": ["메시지 작성..."] } } }, { "l": "lo", "t": { "Message limit of %n character reached": { "p": "Message limit of %n characters reached", "v": ["ຮອດຂີດຈຳກັດຂໍ້ຄວາມ %n ໂຕອັກສອນແລ້ວ"] }, "No link provider found": { "v": ["ບໍ່ພົບຜູ້ໃຫ້ບໍລິການລິງ"] }, "Write a message …": { "v": ["ຂຽນຂໍ້ຄວາມ…"] } } }, { "l": "lt-LT", "t": {} }, { "l": "lv", "t": {} }, { "l": "mk", "t": { "Message limit of %n character reached": { "p": "Message limit of %n characters reached", "v": ["Достигнат е лимит од %n карактер", "Достигнат е лимит од %n карактери за порака"] }, "No link provider found": { "v": ["Не е пронајден давател на линк"] }, "Write a message …": { "v": ["Напиши порака …"] } } }, { "l": "my", "t": {} }, { "l": "nb", "t": { "No link provider found": { "v": ["Finner ingen lenkeleverandør"] }, "Write a message …": { "v": ["Skriv en melding..."] } } }, { "l": "nl", "t": { "Message limit of %n character reached": { "p": "Message limit of %n characters reached", "v": ["Berichtlimiet van %n teken bereikt", "Berichtlimiet van %n tekens bereikt"] }, "No link provider found": { "v": ["Geen link provider gevonden"] }, "Write a message …": { "v": ["Schrijf een bericht …"] } } }, { "l": "oc", "t": {} }, { "l": "pl", "t": { "No link provider found": { "v": ["Nie znaleziono dostawcy linków"] }, "Write a message …": { "v": ["Napisz wiadomość…"] } } }, { "l": "pt-BR", "t": { "Message limit of %n character reached": { "p": "Message limit of %n characters reached", "v": ["Limite de mensagem de %n caractere atingido", "Limite de mensagem de %n de caracteres atingido", "Limite de mensagem de %n caracteres atingido"] }, "No link provider found": { "v": ["Nenhum provedor de link encontrado"] }, "Write a message …": { "v": ["Escreva uma mensagem …"] } } }, { "l": "pt-PT", "t": { "Message limit of %n character reached": { "p": "Message limit of %n characters reached", "v": ["Limite de mensagem de %n carácter atingido", "Limite de mensagem de %n caracteres atingido", "Limite de mensagem de %n caracteres atingido"] }, "No link provider found": { "v": ["Nenhum fornecedor de link encontrado"] }, "Write a message …": { "v": ["Escreva uma mensagem..."] } } }, { "l": "ro", "t": { "No link provider found": { "v": ["Nu s-a găsit un provider pentru linkuri"] }, "Write a message …": { "v": ["Scrieți un mesaj ..."] } } }, { "l": "ru", "t": { "Message limit of %n character reached": { "p": "Message limit of %n characters reached", "v": ["Достигнут лимит в %n символ", "Достигнут лимит в %n символа", "Достигнут лимит в %n символов", "Достигнут лимит в %n символов"] }, "No link provider found": { "v": ["Поставщик ссылок не найден"] }, "Write a message …": { "v": ["Напишите сообщение …"] } } }, { "l": "sk", "t": { "No link provider found": { "v": ["Žiaden odkaz poskytovateľa nebol nájdený"] }, "Write a message …": { "v": ["Napíšte správu…"] } } }, { "l": "sl", "t": {} }, { "l": "sr", "t": { "Message limit of %n character reached": { "p": "Message limit of %n characters reached", "v": ["Достигнуто је ограничење величине поруке од %n карактер", "Достигнуто је ограничење величине поруке од %n карактера", "Достигнуто је ограничење величине поруке од %n карактера"] }, "No link provider found": { "v": ["Није пронађен ниједан пружалац линка"] }, "Write a message …": { "v": ["Напишите поруку…"] } } }, { "l": "sv", "t": { "No link provider found": { "v": ["Ingen länkleverantör hittades"] }, "Write a message …": { "v": ["Skriv ett meddelande …"] } } }, { "l": "tr", "t": { "Message limit of %n character reached": { "p": "Message limit of %n characters reached", "v": ["İleti için %n karakter sayısı sınırına ulaşıldı", "İleti için %n karakter sayısı sınırına ulaşıldı"] }, "No link provider found": { "v": ["Bağlantı hizmeti sağlayıcısı bulunamadı"] }, "Write a message …": { "v": ["Bir ileti yazın…"] } } }, { "l": "uk", "t": { "Message limit of %n character reached": { "p": "Message limit of %n characters reached", "v": ["Досягнуто обмеження на довжину повідомлення у %n символ.", "Досягнуто обмеження на довжину повідомлення у %n символи.", "Досягнуто обмеження на довжину повідомлення у%n символів.", "Досягнуто обмеження на довжину повідомлення у %n символів."] }, "No link provider found": { "v": ["Не наведено посилання"] }, "Write a message …": { "v": ["Створити повідомлення …"] } } }, { "l": "uz", "t": { "Message limit of %n character reached": { "p": "Message limit of %n characters reached", "v": [" %n  ta belgidan iborat xabar chegarasiga yetdi"] }, "No link provider found": { "v": ["Hech qanday havola provayderi topilmadi"] }, "Write a message …": { "v": ["Xabar yozish..."] } } }, { "l": "zh-CN", "t": { "No link provider found": { "v": ["未找到任何链接提供者"] }, "Write a message …": { "v": ["编写信息 ..."] } } }, { "l": "zh-HK", "t": { "Message limit of %n character reached": { "p": "Message limit of %n characters reached", "v": ["已達到訊息最多 %n 字元限制"] }, "No link provider found": { "v": ["找不到連結提供者"] }, "Write a message …": { "v": ["編寫訊息 …"] } } }, { "l": "zh-TW", "t": { "No link provider found": { "v": ["找不到連結提供者"] }, "Write a message …": { "v": ["編寫訊息……"] } } }];
const t35 = [{ "l": "ar", "t": { "More items …": { "v": ["عناصر أخرى ..."] } } }, { "l": "ast", "t": { "More items …": { "v": ["Más elementos…"] } } }, { "l": "br", "t": {} }, { "l": "ca", "t": { "More items …": { "v": ["Més artícles..."] } } }, { "l": "cs", "t": { "More items …": { "v": ["Další položky …"] } } }, { "l": "cs-CZ", "t": { "More items …": { "v": ["Další položky…"] } } }, { "l": "da", "t": { "More items …": { "v": ["Flere elementer ..."] } } }, { "l": "de", "t": { "More items …": { "v": ["Weitere Elemente …"] } } }, { "l": "de-DE", "t": { "More items …": { "v": ["Weitere Elemente …"] } } }, { "l": "el", "t": { "More items …": { "v": ["Περισσότερα στοιχεία …"] } } }, { "l": "en-GB", "t": { "More items …": { "v": ["More items …"] } } }, { "l": "eo", "t": {} }, { "l": "es", "t": { "More items …": { "v": ["Más ítems ..."] } } }, { "l": "es-AR", "t": { "More items …": { "v": ["Más elementos..."] } } }, { "l": "es-EC", "t": { "More items …": { "v": ["Más elementos..."] } } }, { "l": "es-MX", "t": { "More items …": { "v": ["Más Elementos ..."] } } }, { "l": "et-EE", "t": { "More items …": { "v": ["Vaata veel …"] } } }, { "l": "eu", "t": { "More items …": { "v": ["Elementu gehiago …"] } } }, { "l": "fa", "t": { "More items …": { "v": ["موارد بیشتر ..."] } } }, { "l": "fi", "t": { "More items …": { "v": ["Lisää kohteita…"] } } }, { "l": "fr", "t": { "More items …": { "v": ["Plus d'éléments..."] } } }, { "l": "ga", "t": { "More items …": { "v": ["Tuilleadh míreanna …"] } } }, { "l": "gl", "t": { "More items …": { "v": ["Máis elementos…"] } } }, { "l": "he", "t": { "More items …": { "v": ["פריטים נוספים…"] } } }, { "l": "hu", "t": { "More items …": { "v": ["További elemek..."] } } }, { "l": "id", "t": { "More items …": { "v": ["Item lainnya…"] } } }, { "l": "is", "t": { "More items …": { "v": ["Fleiri atriði …"] } } }, { "l": "it", "t": { "More items …": { "v": ["Più elementi ..."] } } }, { "l": "ja", "t": { "More items …": { "v": ["他のアイテム　…"] } } }, { "l": "ja-JP", "t": { "More items …": { "v": ["他のアイテム"] } } }, { "l": "ko", "t": { "More items …": { "v": ["항목 더 보기..."] } } }, { "l": "lo", "t": { "More items …": { "v": ["ລາຍການເພີ່ມເຕີມ…"] } } }, { "l": "lt-LT", "t": {} }, { "l": "lv", "t": {} }, { "l": "mk", "t": { "More items …": { "v": ["Повеќе елементи …"] } } }, { "l": "my", "t": {} }, { "l": "nb", "t": { "More items …": { "v": ["Flere gjenstander..."] } } }, { "l": "nl", "t": { "More items …": { "v": ["Meer items …"] } } }, { "l": "oc", "t": {} }, { "l": "pl", "t": { "More items …": { "v": ["Więcej pozycji…"] } } }, { "l": "pt-BR", "t": { "More items …": { "v": ["Mais itens …"] } } }, { "l": "pt-PT", "t": { "More items …": { "v": ["Mais itens…"] } } }, { "l": "ro", "t": { "More items …": { "v": ["Mai multe articole ..."] } } }, { "l": "ru", "t": { "More items …": { "v": ["Больше элементов …"] } } }, { "l": "sk", "t": { "More items …": { "v": ["Viac položiek..."] } } }, { "l": "sl", "t": { "More items …": { "v": ["Več predmetov ..."] } } }, { "l": "sr", "t": { "More items …": { "v": ["Још ставки..."] } } }, { "l": "sv", "t": { "More items …": { "v": ["Fler objekt …"] } } }, { "l": "tr", "t": { "More items …": { "v": ["Diğer ögeler…"] } } }, { "l": "uk", "t": { "More items …": { "v": ["Більше …"] } } }, { "l": "uz", "t": { "More items …": { "v": ["Yana elementlar..."] } } }, { "l": "zh-CN", "t": { "More items …": { "v": ["更多项目…"] } } }, { "l": "zh-HK", "t": { "More items …": { "v": ["更多項目 …"] } } }, { "l": "zh-TW", "t": { "More items …": { "v": ["更多項目……"] } } }];
const t37 = [{ "l": "ar", "t": { "No emoji found": { "v": ["لم يتم العثور على أي إيموجي emoji"] } } }, { "l": "ast", "t": { "No emoji found": { "v": ["Nun s'atopó nengún fustaxe"] } } }, { "l": "br", "t": { "No emoji found": { "v": ["Emoji ebet kavet"] } } }, { "l": "ca", "t": { "No emoji found": { "v": ["No s'ha trobat cap emoji"] } } }, { "l": "cs", "t": { "No emoji found": { "v": ["Nenalezeno žádné emoji"] } } }, { "l": "cs-CZ", "t": { "No emoji found": { "v": ["Nenalezeno žádné emoji"] } } }, { "l": "da", "t": { "No emoji found": { "v": ["Ingen emoji fundet"] } } }, { "l": "de", "t": { "No emoji found": { "v": ["Kein Emoji gefunden"] } } }, { "l": "de-DE", "t": { "No emoji found": { "v": ["Kein Emoji gefunden"] } } }, { "l": "el", "t": { "No emoji found": { "v": ["Δεν βρέθηκε emoji"] } } }, { "l": "en-GB", "t": { "No emoji found": { "v": ["No emoji found"] } } }, { "l": "eo", "t": { "No emoji found": { "v": ["La emoĝio forestas"] } } }, { "l": "es", "t": { "No emoji found": { "v": ["No se encontró ningún emoji"] } } }, { "l": "es-AR", "t": { "No emoji found": { "v": ["No se encontró ningún emoji"] } } }, { "l": "es-EC", "t": { "No emoji found": { "v": ["No se encontró ningún emoji"] } } }, { "l": "es-MX", "t": { "No emoji found": { "v": ["No se encontró ningún emoji"] } } }, { "l": "et-EE", "t": { "No emoji found": { "v": ["Emojit ei leitud"] } } }, { "l": "eu", "t": { "No emoji found": { "v": ["Ez da emojirik aurkitu"] } } }, { "l": "fa", "t": { "No emoji found": { "v": ["هیچ شکلکی یافت نشد"] } } }, { "l": "fi", "t": { "No emoji found": { "v": ["Emojia ei löytynyt"] } } }, { "l": "fr", "t": { "No emoji found": { "v": ["Pas d’émoji trouvé"] } } }, { "l": "ga", "t": { "No emoji found": { "v": ["Níor aimsíodh emoji"] } } }, { "l": "gl", "t": { "No emoji found": { "v": ["Non se atopou ningún «emoji»"] } } }, { "l": "he", "t": { "No emoji found": { "v": ["לא נמצא אמוג׳י"] } } }, { "l": "hu", "t": { "No emoji found": { "v": ["Nem található emodzsi"] } } }, { "l": "id", "t": { "No emoji found": { "v": ["Tidak ada emoji yang ditemukan"] } } }, { "l": "is", "t": { "No emoji found": { "v": ["Ekkert tjáningartákn fannst"] } } }, { "l": "it", "t": { "No emoji found": { "v": ["Nessun emoji trovato"] } } }, { "l": "ja", "t": { "No emoji found": { "v": ["絵文字が見つかりません"] } } }, { "l": "ja-JP", "t": { "No emoji found": { "v": ["絵文字が見つかりません"] } } }, { "l": "ko", "t": { "No emoji found": { "v": ["이모지 없음"] } } }, { "l": "lo", "t": { "No emoji found": { "v": ["ບໍ່ພົບອີໂມຈິ"] } } }, { "l": "lt-LT", "t": { "No emoji found": { "v": ["Nerasta jaustukų"] } } }, { "l": "lv", "t": {} }, { "l": "mk", "t": { "No emoji found": { "v": ["Не се пронајдени емотикони"] } } }, { "l": "my", "t": { "No emoji found": { "v": ["အီမိုဂျီ ရှာဖွေမတွေ့နိုင်ပါ"] } } }, { "l": "nb", "t": { "No emoji found": { "v": ["Fant ingen emoji"] } } }, { "l": "nl", "t": { "No emoji found": { "v": ["Geen emoji gevonden"] } } }, { "l": "oc", "t": {} }, { "l": "pl", "t": { "No emoji found": { "v": ["Nie znaleziono emoji"] } } }, { "l": "pt-BR", "t": { "No emoji found": { "v": ["Nenhum emoji encontrado"] } } }, { "l": "pt-PT", "t": { "No emoji found": { "v": ["Nenhum emoji encontrado"] } } }, { "l": "ro", "t": { "No emoji found": { "v": ["Nu s-a găsit niciun emoji"] } } }, { "l": "ru", "t": { "No emoji found": { "v": ["Эмодзи не найдено"] } } }, { "l": "sk", "t": { "No emoji found": { "v": ["Nenašli sa žiadne emodži"] } } }, { "l": "sl", "t": { "No emoji found": { "v": ["Ni najdenih izraznih ikon"] } } }, { "l": "sr", "t": { "No emoji found": { "v": ["Није пронађен ниједан емођи"] } } }, { "l": "sv", "t": { "No emoji found": { "v": ["Hittade inga emojis"] } } }, { "l": "tr", "t": { "No emoji found": { "v": ["Herhangi bir emoji bulunamadı"] } } }, { "l": "uk", "t": { "No emoji found": { "v": ["Емоційки відсутні"] } } }, { "l": "uz", "t": { "No emoji found": { "v": ["Hech qanday emoji topilmadi"] } } }, { "l": "zh-CN", "t": { "No emoji found": { "v": ["表情未找到"] } } }, { "l": "zh-HK", "t": { "No emoji found": { "v": ["未找到表情符號"] } } }, { "l": "zh-TW", "t": { "No emoji found": { "v": ["未找到表情符號"] } } }];
const t38 = [{ "l": "ar", "t": { 'Open link to "{resourceName}"': { "v": ['إفتَح الرابط إلى "{resourceName}"'] } } }, { "l": "ast", "t": { 'Open link to "{resourceName}"': { "v": ["Abrir l'enllaz a «{resourceName}»"] } } }, { "l": "br", "t": {} }, { "l": "ca", "t": {} }, { "l": "cs", "t": { 'Open link to "{resourceName}"': { "v": ["Otevřít odkaz na „{resourceName}“"] } } }, { "l": "cs-CZ", "t": { 'Open link to "{resourceName}"': { "v": ["Otevřít odkaz na „{resourceName}“"] } } }, { "l": "da", "t": { 'Open link to "{resourceName}"': { "v": ['Åbn link til "{resourceName}"'] } } }, { "l": "de", "t": { 'Open link to "{resourceName}"': { "v": ['Link zu "{resourceName}" öffnen'] } } }, { "l": "de-DE", "t": { 'Open link to "{resourceName}"': { "v": ['Link zu "{resourceName}" öffnen'] } } }, { "l": "el", "t": { 'Open link to "{resourceName}"': { "v": ['Άνοιγμα συνδέσμου για "{resourceName}"'] } } }, { "l": "en-GB", "t": { 'Open link to "{resourceName}"': { "v": ['Open link to "{resourceName}"'] } } }, { "l": "eo", "t": {} }, { "l": "es", "t": { 'Open link to "{resourceName}"': { "v": ['Abrir enlace a "{resourceName}"'] } } }, { "l": "es-AR", "t": { 'Open link to "{resourceName}"': { "v": ['Abrir enlace a "{resourceName}"'] } } }, { "l": "es-EC", "t": { 'Open link to "{resourceName}"': { "v": ['Abrir enlace a "{resourceName}"'] } } }, { "l": "es-MX", "t": { 'Open link to "{resourceName}"': { "v": ['Abrir enlace a "{resourceName}"'] } } }, { "l": "et-EE", "t": { 'Open link to "{resourceName}"': { "v": ["Ava link „{resourceName}“"] } } }, { "l": "eu", "t": { 'Open link to "{resourceName}"': { "v": ['Ireki "{resourceName}" esteka'] } } }, { "l": "fa", "t": { 'Open link to "{resourceName}"': { "v": ["باز کردن پیوند به «{resourceName}»"] } } }, { "l": "fi", "t": { 'Open link to "{resourceName}"': { "v": ['Avaa linkki "{resourceName}"'] } } }, { "l": "fr", "t": { 'Open link to "{resourceName}"': { "v": ['Ouvrir le lien vers "{resourceName}"'] } } }, { "l": "ga", "t": { 'Open link to "{resourceName}"': { "v": ['Oscail nasc chuig "{resourceName}"'] } } }, { "l": "gl", "t": { 'Open link to "{resourceName}"': { "v": ["Abrir a ligazón a «{resourceName}»"] } } }, { "l": "he", "t": { 'Open link to "{resourceName}"': { "v": ["פתיחת קישור אל „{resourceName}”"] } } }, { "l": "hu", "t": {} }, { "l": "id", "t": { 'Open link to "{resourceName}"': { "v": ['Buka tautan ke "{resourceName}"'] } } }, { "l": "is", "t": { 'Open link to "{resourceName}"': { "v": ['Opna tengil í "{resourceName}"'] } } }, { "l": "it", "t": { 'Open link to "{resourceName}"': { "v": ['Apri il link a "{resourceName}"'] } } }, { "l": "ja", "t": { 'Open link to "{resourceName}"': { "v": ['"{resourceName}" へのリンクを開く'] } } }, { "l": "ja-JP", "t": { 'Open link to "{resourceName}"': { "v": ['"{resourceName}" へのリンクを開く'] } } }, { "l": "ko", "t": { 'Open link to "{resourceName}"': { "v": ['"{resourceName}"의 링크 열기'] } } }, { "l": "lo", "t": { 'Open link to "{resourceName}"': { "v": ["ເປີດລິງໄປທີ່ “{resourceName}”"] } } }, { "l": "lt-LT", "t": {} }, { "l": "lv", "t": {} }, { "l": "mk", "t": { 'Open link to "{resourceName}"': { "v": ['Отвори линк до "{resourceName}"'] } } }, { "l": "my", "t": {} }, { "l": "nb", "t": { 'Open link to "{resourceName}"': { "v": ['Åpne lenken til "{resourceName}"'] } } }, { "l": "nl", "t": { 'Open link to "{resourceName}"': { "v": ['Link naar "{resourceName}" openen'] } } }, { "l": "oc", "t": {} }, { "l": "pl", "t": { 'Open link to "{resourceName}"': { "v": ['Otwórz link do "{resourceName}"'] } } }, { "l": "pt-BR", "t": { 'Open link to "{resourceName}"': { "v": ['Abrir o link para "{resourceName}"'] } } }, { "l": "pt-PT", "t": { 'Open link to "{resourceName}"': { "v": ['Abrir link para "{resourceName}"'] } } }, { "l": "ro", "t": { 'Open link to "{resourceName}"': { "v": ['Deschide linkul la  "{resourceName}"'] } } }, { "l": "ru", "t": { 'Open link to "{resourceName}"': { "v": ['Открыть ссылку на "{resourceName}"'] } } }, { "l": "sk", "t": { 'Open link to "{resourceName}"': { "v": ['Otvoriť link v "{resourceName}"'] } } }, { "l": "sl", "t": {} }, { "l": "sr", "t": { 'Open link to "{resourceName}"': { "v": ["Отвори линк на „{resourceName}”"] } } }, { "l": "sv", "t": { 'Open link to "{resourceName}"': { "v": ['Öppna länken till "{resourceName}"'] } } }, { "l": "tr", "t": { 'Open link to "{resourceName}"': { "v": ['Bağlantıyı "{resourceName}" üzerine aç'] } } }, { "l": "uk", "t": { 'Open link to "{resourceName}"': { "v": ['Відкрити посилання на "{resourceName}"'] } } }, { "l": "uz", "t": { 'Open link to "{resourceName}"': { "v": [' "{resourceName}" ga havolani ochish'] } } }, { "l": "zh-CN", "t": { 'Open link to "{resourceName}"': { "v": ["打开 “{resourceName}” 的链接"] } } }, { "l": "zh-HK", "t": { 'Open link to "{resourceName}"': { "v": ["開啟到「{resourceName}」的連結"] } } }, { "l": "zh-TW", "t": { 'Open link to "{resourceName}"': { "v": ["開啟到「{resourceName}」的連結"] } } }];
const t40 = [{ "l": "ar", "t": { "Provider icon": { "v": ["أيقونة المزوّد"] } } }, { "l": "ast", "t": { "Provider icon": { "v": ["Iconu del fornidor"] } } }, { "l": "br", "t": {} }, { "l": "ca", "t": {} }, { "l": "cs", "t": { "Provider icon": { "v": ["Ikona poskytovatele"] } } }, { "l": "cs-CZ", "t": { "Provider icon": { "v": ["Ikona poskytovatele"] } } }, { "l": "da", "t": { "Provider icon": { "v": ["Udbyder ikon"] } } }, { "l": "de", "t": { "Provider icon": { "v": ["Anbietersymbol"] } } }, { "l": "de-DE", "t": { "Provider icon": { "v": ["Anbietersymbol"] } } }, { "l": "el", "t": { "Provider icon": { "v": ["Εικονίδιο παρόχου"] } } }, { "l": "en-GB", "t": { "Provider icon": { "v": ["Provider icon"] } } }, { "l": "eo", "t": {} }, { "l": "es", "t": { "Provider icon": { "v": ["Ícono del proveedor"] } } }, { "l": "es-AR", "t": { "Provider icon": { "v": ["Ícono del proveedor"] } } }, { "l": "es-EC", "t": { "Provider icon": { "v": ["Ícono del proveedor"] } } }, { "l": "es-MX", "t": { "Provider icon": { "v": ["Ícono del proveedor"] } } }, { "l": "et-EE", "t": { "Provider icon": { "v": ["Teenusepakkuja ikoon"] } } }, { "l": "eu", "t": { "Provider icon": { "v": ["Hornitzailearen ikonoa"] } } }, { "l": "fa", "t": { "Provider icon": { "v": ["آیکون ارائه دهنده"] } } }, { "l": "fi", "t": { "Provider icon": { "v": ["Palveluntarjoajan kuvake"] } } }, { "l": "fr", "t": { "Provider icon": { "v": ["Icône du fournisseur"] } } }, { "l": "ga", "t": { "Provider icon": { "v": ["Deilbhín soláthraí"] } } }, { "l": "gl", "t": { "Provider icon": { "v": ["Icona do provedor"] } } }, { "l": "he", "t": { "Provider icon": { "v": ["סמל ספק"] } } }, { "l": "hu", "t": {} }, { "l": "id", "t": { "Provider icon": { "v": ["Ikon penyedia"] } } }, { "l": "is", "t": { "Provider icon": { "v": ["Táknmynd þjónustuveitu"] } } }, { "l": "it", "t": { "Provider icon": { "v": ["Icona del provider"] } } }, { "l": "ja", "t": { "Provider icon": { "v": ["プロバイダーのアイコン"] } } }, { "l": "ja-JP", "t": { "Provider icon": { "v": ["プロバイダーのアイコン"] } } }, { "l": "ko", "t": { "Provider icon": { "v": ["제공자 아이콘"] } } }, { "l": "lo", "t": { "Provider icon": { "v": ["ໄອຄອນຜູ້ໃຫ້ບໍລິການ"] } } }, { "l": "lt-LT", "t": {} }, { "l": "lv", "t": {} }, { "l": "mk", "t": { "Provider icon": { "v": ["Икона на давател"] } } }, { "l": "my", "t": {} }, { "l": "nb", "t": { "Provider icon": { "v": ["Leverandørikon"] } } }, { "l": "nl", "t": { "Provider icon": { "v": ["Provider-pictogram"] } } }, { "l": "oc", "t": {} }, { "l": "pl", "t": { "Provider icon": { "v": ["Dostawca ikony"] } } }, { "l": "pt-BR", "t": { "Provider icon": { "v": ["Ícone do provedor"] } } }, { "l": "pt-PT", "t": { "Provider icon": { "v": ["Ícone do fornecedor"] } } }, { "l": "ro", "t": { "Provider icon": { "v": ["Provider pentru icon"] } } }, { "l": "ru", "t": { "Provider icon": { "v": ["Значок поставщика"] } } }, { "l": "sk", "t": { "Provider icon": { "v": ["Ikonka poskytovateľa"] } } }, { "l": "sl", "t": {} }, { "l": "sr", "t": { "Provider icon": { "v": ["Икона пружаоца"] } } }, { "l": "sv", "t": { "Provider icon": { "v": ["Leverantörsikon"] } } }, { "l": "tr", "t": { "Provider icon": { "v": ["Hizmet sağlayıcı simgesi"] } } }, { "l": "uk", "t": { "Provider icon": { "v": ["Піктограма постачальника"] } } }, { "l": "uz", "t": { "Provider icon": { "v": ["Provayder belgisi"] } } }, { "l": "zh-CN", "t": { "Provider icon": { "v": ["提供者图标"] } } }, { "l": "zh-HK", "t": { "Provider icon": { "v": ["提供者圖示"] } } }, { "l": "zh-TW", "t": { "Provider icon": { "v": ["提供者圖示"] } } }];
const t41 = [{ "l": "ar", "t": { "Related team resources": { "v": ["موارد للفريق ذات صلة"] }, "View team": { "v": ["عرض الفريق"] } } }, { "l": "ast", "t": { "Related team resources": { "v": ["Recursos rellacionaos colos equipos"] }, "View team": { "v": ["Ver l'equipu"] } } }, { "l": "br", "t": {} }, { "l": "ca", "t": {} }, { "l": "cs", "t": { "Related team resources": { "v": ["Související prostředky kolektivu"] }, "View team": { "v": ["Zobrazit kolektiv"] } } }, { "l": "cs-CZ", "t": {} }, { "l": "da", "t": { "Related team resources": { "v": ["Relaterede teamressourcer"] }, "View team": { "v": ["Se teamet"] } } }, { "l": "de", "t": { "Related team resources": { "v": ["Verwandte Team-Ressourcen"] }, "View team": { "v": ["Team anzeigen"] } } }, { "l": "de-DE", "t": { "Related team resources": { "v": ["Verwandte Team-Ressourcen"] }, "View team": { "v": ["Team anzeigen"] } } }, { "l": "el", "t": { "Related team resources": { "v": ["Σχετικοί πόροι ομάδας"] }, "View team": { "v": ["Προβολή ομάδας"] } } }, { "l": "en-GB", "t": { "Related team resources": { "v": ["Related team resources"] }, "View team": { "v": ["View team"] } } }, { "l": "eo", "t": {} }, { "l": "es", "t": { "Related team resources": { "v": ["Recursos de equipo relacionados"] }, "View team": { "v": ["Ver equipo"] } } }, { "l": "es-AR", "t": { "Related team resources": { "v": ["Recursos de equipo relacionados"] }, "View team": { "v": ["Ver equipo"] } } }, { "l": "es-EC", "t": {} }, { "l": "es-MX", "t": { "Related team resources": { "v": ["Recursos de equipo relacionados"] }, "View team": { "v": ["Ver equipo"] } } }, { "l": "et-EE", "t": { "Related team resources": { "v": ["Tiimi seotud ressursid"] }, "View team": { "v": ["Vaata tiimi"] } } }, { "l": "eu", "t": {} }, { "l": "fa", "t": { "Related team resources": { "v": ["منابع تیمی مرتبط"] }, "View team": { "v": ["مشاهده گروه"] } } }, { "l": "fi", "t": { "Related team resources": { "v": ["Liittyvät tiimiresurssit"] }, "View team": { "v": ["Näytä tiimi"] } } }, { "l": "fr", "t": { "Related team resources": { "v": ["Ressources d'équipe associées"] }, "View team": { "v": ["Voir l'équipe"] } } }, { "l": "ga", "t": { "Related team resources": { "v": ["Acmhainní foirne gaolmhara"] }, "View team": { "v": ["Féach ar an bhfoireann"] } } }, { "l": "gl", "t": { "Related team resources": { "v": ["Recursos de equipo relacionados"] }, "View team": { "v": ["Ver o equipo"] } } }, { "l": "he", "t": {} }, { "l": "hu", "t": {} }, { "l": "id", "t": {} }, { "l": "is", "t": { "Related team resources": { "v": ["Tengd tilföng teymis"] }, "View team": { "v": ["Skoða teymi"] } } }, { "l": "it", "t": {} }, { "l": "ja", "t": { "Related team resources": { "v": ["チームの関連リソース"] }, "View team": { "v": ["チームを表示"] } } }, { "l": "ja-JP", "t": { "Related team resources": { "v": ["チームの関連リソース"] }, "View team": { "v": ["チームを表示"] } } }, { "l": "ko", "t": { "Related team resources": { "v": ["관련 팀 리소스"] }, "View team": { "v": ["팀 보기"] } } }, { "l": "lo", "t": { "Related team resources": { "v": ["ຊັບພະຍາກອນຂອງທີມທີ່ກ່ຽວຂ້ອງ"] }, "View team": { "v": ["ເບິ່ງທີມ"] } } }, { "l": "lt-LT", "t": {} }, { "l": "lv", "t": {} }, { "l": "mk", "t": { "Related team resources": { "v": ["Поврзани ресурси на тимот"] }, "View team": { "v": ["Прикажи тим"] } } }, { "l": "my", "t": {} }, { "l": "nb", "t": { "Related team resources": { "v": ["Relaterte lagressurser"] }, "View team": { "v": ["Se lag"] } } }, { "l": "nl", "t": { "Related team resources": { "v": ["Verwante teambronnen"] }, "View team": { "v": ["Team bekijken"] } } }, { "l": "oc", "t": {} }, { "l": "pl", "t": { "Related team resources": { "v": ["Powiązane zasoby grupowe"] }, "View team": { "v": ["Zobacz grupę"] } } }, { "l": "pt-BR", "t": { "Related team resources": { "v": ["Recursos de equipe relacionados"] }, "View team": { "v": ["Ver equipe"] } } }, { "l": "pt-PT", "t": { "Related team resources": { "v": ["Recursos relacionados com a equipa"] }, "View team": { "v": ["Ver equipa"] } } }, { "l": "ro", "t": {} }, { "l": "ru", "t": { "Related team resources": { "v": ["Связанные командные ресурсы"] }, "View team": { "v": ["Просмотр команды"] } } }, { "l": "sk", "t": { "Related team resources": { "v": ["Súvisiace tímové zdroje"] }, "View team": { "v": ["Zobraziť tím"] } } }, { "l": "sl", "t": {} }, { "l": "sr", "t": { "Related team resources": { "v": ["Повезани тимски ресурси"] }, "View team": { "v": ["Прикажи тим"] } } }, { "l": "sv", "t": { "Related team resources": { "v": ["Relaterade teamresurser"] }, "View team": { "v": ["Visa team"] } } }, { "l": "tr", "t": { "Related team resources": { "v": ["İlgili takım kaynakları"] }, "View team": { "v": ["Takımı görüntüle"] } } }, { "l": "uk", "t": { "Related team resources": { "v": ["Пов'язані ресурси команди"] }, "View team": { "v": ["Переглянути команду"] } } }, { "l": "uz", "t": { "Related team resources": { "v": ["Tegishli jamoa resurslari"] }, "View team": { "v": ["Jamoani ko'rish"] } } }, { "l": "zh-CN", "t": { "Related team resources": { "v": ["相关团队资源"] }, "View team": { "v": ["查看团队"] } } }, { "l": "zh-HK", "t": { "Related team resources": { "v": ["相關團隊資源"] }, "View team": { "v": ["查看團隊"] } } }, { "l": "zh-TW", "t": { "Related team resources": { "v": ["相關團隊資源"] }, "View team": { "v": ["檢視團隊"] } } }];
const t42 = [{ "l": "ar", "t": { "Search": { "v": ["بحث"] } } }, { "l": "ast", "t": { "Search": { "v": ["Buscar"] } } }, { "l": "br", "t": { "Search": { "v": ["Klask"] } } }, { "l": "ca", "t": { "Search": { "v": ["Cerca"] } } }, { "l": "cs", "t": { "Search": { "v": ["Hledat"] } } }, { "l": "cs-CZ", "t": { "Search": { "v": ["Hledat"] } } }, { "l": "da", "t": { "Search": { "v": ["Søg"] } } }, { "l": "de", "t": { "Search": { "v": ["Suche"] } } }, { "l": "de-DE", "t": { "Search": { "v": ["Suche"] } } }, { "l": "el", "t": { "Search": { "v": ["Αναζήτηση"] } } }, { "l": "en-GB", "t": { "Search": { "v": ["Search"] } } }, { "l": "eo", "t": { "Search": { "v": ["Serĉi"] } } }, { "l": "es", "t": { "Search": { "v": ["Buscar"] } } }, { "l": "es-AR", "t": { "Search": { "v": ["Buscar"] } } }, { "l": "es-EC", "t": { "Search": { "v": ["Buscar"] } } }, { "l": "es-MX", "t": { "Search": { "v": ["Buscar"] } } }, { "l": "et-EE", "t": { "Search": { "v": ["Otsing"] } } }, { "l": "eu", "t": { "Search": { "v": ["Bilatu"] } } }, { "l": "fa", "t": { "Search": { "v": ["جستجو"] } } }, { "l": "fi", "t": { "Search": { "v": ["Etsi"] } } }, { "l": "fr", "t": { "Search": { "v": ["Rechercher"] } } }, { "l": "ga", "t": { "Search": { "v": ["Cuardach"] } } }, { "l": "gl", "t": { "Search": { "v": ["Buscar"] } } }, { "l": "he", "t": { "Search": { "v": ["חיפוש"] } } }, { "l": "hu", "t": { "Search": { "v": ["Keresés"] } } }, { "l": "id", "t": { "Search": { "v": ["Cari"] } } }, { "l": "is", "t": { "Search": { "v": ["Leita"] } } }, { "l": "it", "t": { "Search": { "v": ["Cerca"] } } }, { "l": "ja", "t": { "Search": { "v": ["検索"] } } }, { "l": "ja-JP", "t": { "Search": { "v": ["検索"] } } }, { "l": "ko", "t": { "Search": { "v": ["검색"] } } }, { "l": "lo", "t": { "Search": { "v": ["ຄົ້ນຫາ"] } } }, { "l": "lt-LT", "t": { "Search": { "v": ["Ieškoti"] } } }, { "l": "lv", "t": {} }, { "l": "mk", "t": { "Search": { "v": ["Барај"] } } }, { "l": "my", "t": { "Search": { "v": ["ရှာဖွေရန်"] } } }, { "l": "nb", "t": { "Search": { "v": ["Søk"] } } }, { "l": "nl", "t": { "Search": { "v": ["Zoeken"] } } }, { "l": "oc", "t": {} }, { "l": "pl", "t": { "Search": { "v": ["Szukaj"] } } }, { "l": "pt-BR", "t": { "Search": { "v": ["Pesquisar"] } } }, { "l": "pt-PT", "t": { "Search": { "v": ["Pesquisar"] } } }, { "l": "ro", "t": { "Search": { "v": ["Căutare"] } } }, { "l": "ru", "t": { "Search": { "v": ["Поиск"] } } }, { "l": "sk", "t": { "Search": { "v": ["Hľadať"] } } }, { "l": "sl", "t": { "Search": { "v": ["Iskanje"] } } }, { "l": "sr", "t": { "Search": { "v": ["Претражи"] } } }, { "l": "sv", "t": { "Search": { "v": ["Sök"] } } }, { "l": "tr", "t": { "Search": { "v": ["Ara"] } } }, { "l": "uk", "t": { "Search": { "v": ["Пошук"] } } }, { "l": "uz", "t": { "Search": { "v": ["Qidiruv"] } } }, { "l": "zh-CN", "t": { "Search": { "v": ["搜索"] } } }, { "l": "zh-HK", "t": { "Search": { "v": ["搜尋"] } } }, { "l": "zh-TW", "t": { "Search": { "v": ["搜尋"] } } }];
const t45 = [{ "l": "ar", "t": { "Select provider": { "v": ["اختر مزود"] } } }, { "l": "ast", "t": { "Select provider": { "v": ["Seleicionar el fornidor"] } } }, { "l": "br", "t": {} }, { "l": "ca", "t": {} }, { "l": "cs", "t": { "Select provider": { "v": ["Vybrat poskytovatele"] } } }, { "l": "cs-CZ", "t": { "Select provider": { "v": ["Vybrat poskytovatele"] } } }, { "l": "da", "t": { "Select provider": { "v": ["Vælg udbyder"] } } }, { "l": "de", "t": { "Select provider": { "v": ["Anbieter auswählen"] } } }, { "l": "de-DE", "t": { "Select provider": { "v": ["Anbieter auswählen"] } } }, { "l": "el", "t": { "Select provider": { "v": ["Επιλογή παρόχου"] } } }, { "l": "en-GB", "t": { "Select provider": { "v": ["Select provider"] } } }, { "l": "eo", "t": {} }, { "l": "es", "t": { "Select provider": { "v": ["Seleccione proveedor"] } } }, { "l": "es-AR", "t": { "Select provider": { "v": ["Elija proveedor"] } } }, { "l": "es-EC", "t": { "Select provider": { "v": ["Seleccionar proveedor"] } } }, { "l": "es-MX", "t": { "Select provider": { "v": ["Seleccionar proveedor"] } } }, { "l": "et-EE", "t": { "Select provider": { "v": ["Vali teenuspakkuja"] } } }, { "l": "eu", "t": { "Select provider": { "v": ["Hautatu hornitzailea"] } } }, { "l": "fa", "t": { "Select provider": { "v": ["ارائه دهنده را انتخاب کنید"] } } }, { "l": "fi", "t": { "Select provider": { "v": ["Valitse tarjoaja"] } } }, { "l": "fr", "t": { "Select provider": { "v": ["Sélectionner un fournisseur"] } } }, { "l": "ga", "t": { "Select provider": { "v": ["Roghnaigh soláthraí"] } } }, { "l": "gl", "t": { "Select provider": { "v": ["Seleccione o provedor"] } } }, { "l": "he", "t": { "Select provider": { "v": ["בחירת ספק"] } } }, { "l": "hu", "t": {} }, { "l": "id", "t": { "Select provider": { "v": ["Pilih penyedia"] } } }, { "l": "is", "t": { "Select provider": { "v": ["Veldu þjónustuveitu"] } } }, { "l": "it", "t": { "Select provider": { "v": ["Selezionare il provider"] } } }, { "l": "ja", "t": { "Select provider": { "v": ["プロバイダーを選択"] } } }, { "l": "ja-JP", "t": { "Select provider": { "v": ["プロバイダーを選択"] } } }, { "l": "ko", "t": { "Select provider": { "v": ["제공자 선택"] } } }, { "l": "lo", "t": { "Select provider": { "v": ["ເລືອກຜູ້ໃຫ້ບໍລິການ"] } } }, { "l": "lt-LT", "t": {} }, { "l": "lv", "t": {} }, { "l": "mk", "t": { "Select provider": { "v": ["Избери провајдер"] } } }, { "l": "my", "t": {} }, { "l": "nb", "t": { "Select provider": { "v": ["Velg leverandør"] } } }, { "l": "nl", "t": { "Select provider": { "v": ["Selecteer provider"] } } }, { "l": "oc", "t": {} }, { "l": "pl", "t": { "Select provider": { "v": ["Wybierz dostawcę"] } } }, { "l": "pt-BR", "t": { "Select provider": { "v": ["Selecione provedor"] } } }, { "l": "pt-PT", "t": { "Select provider": { "v": ["Selecionar fornecedor"] } } }, { "l": "ro", "t": { "Select provider": { "v": ["Selectați providerul"] } } }, { "l": "ru", "t": { "Select provider": { "v": ["Выбрать поставщика"] } } }, { "l": "sk", "t": { "Select provider": { "v": ["Vybrať poskytovateľa"] } } }, { "l": "sl", "t": {} }, { "l": "sr", "t": { "Select provider": { "v": ["Изаберите пружаоца"] } } }, { "l": "sv", "t": { "Select provider": { "v": ["Välj leverantör"] } } }, { "l": "tr", "t": { "Select provider": { "v": ["Hizmet sağlayıcı seçin"] } } }, { "l": "uk", "t": { "Select provider": { "v": ["Виберіть постачальника"] } } }, { "l": "uz", "t": { "Select provider": { "v": ["Provayderni tanlang"] } } }, { "l": "zh-CN", "t": { "Select provider": { "v": ["选择提供者"] } } }, { "l": "zh-HK", "t": { "Select provider": { "v": ["選擇提供者"] } } }, { "l": "zh-TW", "t": { "Select provider": { "v": ["選取提供者"] } } }];
const t48 = [{ "l": "ar", "t": { "Submit": { "v": ["إرسال"] } } }, { "l": "ast", "t": { "Submit": { "v": ["Unviar"] } } }, { "l": "br", "t": {} }, { "l": "ca", "t": { "Submit": { "v": ["Envia"] } } }, { "l": "cs", "t": { "Submit": { "v": ["Odeslat"] } } }, { "l": "cs-CZ", "t": { "Submit": { "v": ["Odeslat"] } } }, { "l": "da", "t": { "Submit": { "v": ["Send"] } } }, { "l": "de", "t": { "Submit": { "v": ["Einreichen"] } } }, { "l": "de-DE", "t": { "Submit": { "v": ["Einreichen"] } } }, { "l": "el", "t": { "Submit": { "v": ["Υποβολή"] } } }, { "l": "en-GB", "t": { "Submit": { "v": ["Submit"] } } }, { "l": "eo", "t": {} }, { "l": "es", "t": { "Submit": { "v": ["Enviar"] } } }, { "l": "es-AR", "t": { "Submit": { "v": ["Enviar"] } } }, { "l": "es-EC", "t": { "Submit": { "v": ["Enviar"] } } }, { "l": "es-MX", "t": { "Submit": { "v": ["Enviar"] } } }, { "l": "et-EE", "t": { "Submit": { "v": ["Saada"] } } }, { "l": "eu", "t": { "Submit": { "v": ["Bidali"] } } }, { "l": "fa", "t": { "Submit": { "v": ["ارسال"] } } }, { "l": "fi", "t": { "Submit": { "v": ["Lähetä"] } } }, { "l": "fr", "t": { "Submit": { "v": ["Valider"] } } }, { "l": "ga", "t": { "Submit": { "v": ["Cuir isteach"] } } }, { "l": "gl", "t": { "Submit": { "v": ["Enviar"] } } }, { "l": "he", "t": { "Submit": { "v": ["הגשה"] } } }, { "l": "hu", "t": { "Submit": { "v": ["Beküldés"] } } }, { "l": "id", "t": { "Submit": { "v": ["Kirimkan"] } } }, { "l": "is", "t": { "Submit": { "v": ["Senda inn"] } } }, { "l": "it", "t": { "Submit": { "v": ["Invia"] } } }, { "l": "ja", "t": { "Submit": { "v": ["提出"] } } }, { "l": "ja-JP", "t": { "Submit": { "v": ["提出"] } } }, { "l": "ko", "t": { "Submit": { "v": ["제출"] } } }, { "l": "lo", "t": { "Submit": { "v": ["ສົ່ງ"] } } }, { "l": "lt-LT", "t": { "Submit": { "v": ["Pateikti"] } } }, { "l": "lv", "t": {} }, { "l": "mk", "t": { "Submit": { "v": ["Испрати"] } } }, { "l": "my", "t": { "Submit": { "v": ["တင်သွင်းရန်"] } } }, { "l": "nb", "t": { "Submit": { "v": ["Send"] } } }, { "l": "nl", "t": { "Submit": { "v": ["Indienen"] } } }, { "l": "oc", "t": {} }, { "l": "pl", "t": { "Submit": { "v": ["Wyślij"] } } }, { "l": "pt-BR", "t": { "Submit": { "v": ["Enviar"] } } }, { "l": "pt-PT", "t": { "Submit": { "v": ["Submeter"] } } }, { "l": "ro", "t": { "Submit": { "v": ["Trimiteți"] } } }, { "l": "ru", "t": { "Submit": { "v": ["Утвердить"] } } }, { "l": "sk", "t": { "Submit": { "v": ["Odoslať"] } } }, { "l": "sl", "t": { "Submit": { "v": ["Pošlji"] } } }, { "l": "sr", "t": { "Submit": { "v": ["Поднеси"] } } }, { "l": "sv", "t": { "Submit": { "v": ["Skicka"] } } }, { "l": "tr", "t": { "Submit": { "v": ["Gönder"] } } }, { "l": "uk", "t": { "Submit": { "v": ["Надіслати"] } } }, { "l": "uz", "t": { "Submit": { "v": ["Yuborish"] } } }, { "l": "zh-CN", "t": { "Submit": { "v": ["提交"] } } }, { "l": "zh-HK", "t": { "Submit": { "v": ["提交"] } } }, { "l": "zh-TW", "t": { "Submit": { "v": ["遞交"] } } }];
const t49 = [{ "l": "ar", "t": { "Unable to search the group": { "v": ["تعذّر البحث في المجموعة"] } } }, { "l": "ast", "t": { "Unable to search the group": { "v": ["Nun ye posible buscar el grupu"] } } }, { "l": "br", "t": { "Unable to search the group": { "v": ["Dibosupl eo klask ar strollad"] } } }, { "l": "ca", "t": { "Unable to search the group": { "v": ["No es pot cercar el grup"] } } }, { "l": "cs", "t": { "Unable to search the group": { "v": ["Nedaří se hledat skupinu"] } } }, { "l": "cs-CZ", "t": { "Unable to search the group": { "v": ["Nedaří se hledat skupinu"] } } }, { "l": "da", "t": { "Unable to search the group": { "v": ["Kan ikke søge på denne gruppe"] } } }, { "l": "de", "t": { "Unable to search the group": { "v": ["Die Gruppe kann nicht durchsucht werden"] } } }, { "l": "de-DE", "t": { "Unable to search the group": { "v": ["Die Gruppe kann nicht durchsucht werden"] } } }, { "l": "el", "t": { "Unable to search the group": { "v": ["Δεν είναι δυνατή η αναζήτηση της ομάδας"] } } }, { "l": "en-GB", "t": { "Unable to search the group": { "v": ["Unable to search the group"] } } }, { "l": "eo", "t": { "Unable to search the group": { "v": ["Ne eblas serĉi en la grupo"] } } }, { "l": "es", "t": { "Unable to search the group": { "v": ["No es posible buscar en el grupo"] } } }, { "l": "es-AR", "t": { "Unable to search the group": { "v": ["No se puede buscar el grupo"] } } }, { "l": "es-EC", "t": { "Unable to search the group": { "v": ["No se puede buscar en el grupo"] } } }, { "l": "es-MX", "t": { "Unable to search the group": { "v": ["No fue posible buscar en el grupo"] } } }, { "l": "et-EE", "t": { "Unable to search the group": { "v": ["Gruppi ei ole võimalik otsida"] } } }, { "l": "eu", "t": { "Unable to search the group": { "v": ["Ezin izan da taldea bilatu"] } } }, { "l": "fa", "t": { "Unable to search the group": { "v": ["امکان جستجوی گروه وجود ندارد"] } } }, { "l": "fi", "t": { "Unable to search the group": { "v": ["Ryhmää ei voi hakea"] } } }, { "l": "fr", "t": { "Unable to search the group": { "v": ["Impossible de chercher le groupe"] } } }, { "l": "ga", "t": { "Unable to search the group": { "v": ["Ní féidir an grúpa a chuardach"] } } }, { "l": "gl", "t": { "Unable to search the group": { "v": ["Non foi posíbel buscar o grupo"] } } }, { "l": "he", "t": { "Unable to search the group": { "v": ["לא ניתן לחפש בקבוצה"] } } }, { "l": "hu", "t": { "Unable to search the group": { "v": ["A csoport nem kereshető"] } } }, { "l": "id", "t": { "Unable to search the group": { "v": ["Tidak dapat mencari dalam grup"] } } }, { "l": "is", "t": { "Unable to search the group": { "v": ["Get ekki leitað í hópnum"] } } }, { "l": "it", "t": { "Unable to search the group": { "v": ["Impossibile cercare il gruppo"] } } }, { "l": "ja", "t": { "Unable to search the group": { "v": ["グループを検索できません"] } } }, { "l": "ja-JP", "t": { "Unable to search the group": { "v": ["グループを検索できません"] } } }, { "l": "ko", "t": { "Unable to search the group": { "v": ["그룹을 검색할 수 없음"] } } }, { "l": "lo", "t": { "Unable to search the group": { "v": ["ບໍ່ສາມາດຄົ້ນຫາກຸ່ມໄດ້"] } } }, { "l": "lt-LT", "t": { "Unable to search the group": { "v": ["Nepavyko atlikti paiešką grupėje"] } } }, { "l": "lv", "t": {} }, { "l": "mk", "t": { "Unable to search the group": { "v": ["Неможе да се пронајде групата"] } } }, { "l": "my", "t": { "Unable to search the group": { "v": ["အဖွဲ့အား ရှာဖွေ၍ မရနိုင်ပါ"] } } }, { "l": "nb", "t": { "Unable to search the group": { "v": ["Kunne ikke søke i gruppen"] } } }, { "l": "nl", "t": { "Unable to search the group": { "v": ["Kan niet zoeken in de groep"] } } }, { "l": "oc", "t": {} }, { "l": "pl", "t": { "Unable to search the group": { "v": ["Nie można przeszukać grupy"] } } }, { "l": "pt-BR", "t": { "Unable to search the group": { "v": ["Não foi possível pesquisar o grupo"] } } }, { "l": "pt-PT", "t": { "Unable to search the group": { "v": ["Não é possível pesquisar o grupo"] } } }, { "l": "ro", "t": { "Unable to search the group": { "v": ["Imposibilitatea de a căuta în grup"] } } }, { "l": "ru", "t": { "Unable to search the group": { "v": ["Невозможно найти группу"] } } }, { "l": "sk", "t": { "Unable to search the group": { "v": ["Skupinu sa nepodarilo nájsť"] } } }, { "l": "sl", "t": { "Unable to search the group": { "v": ["Ni mogoče iskati po skupini"] } } }, { "l": "sr", "t": { "Unable to search the group": { "v": ["Група не може да се претражи"] } } }, { "l": "sv", "t": { "Unable to search the group": { "v": ["Kunde inte söka i gruppen"] } } }, { "l": "tr", "t": { "Unable to search the group": { "v": ["Grupta arama yapılamadı"] } } }, { "l": "uk", "t": { "Unable to search the group": { "v": ["Неможливо шукати в групі"] } } }, { "l": "uz", "t": { "Unable to search the group": { "v": ["Guruhni qidirish imkonsiz"] } } }, { "l": "zh-CN", "t": { "Unable to search the group": { "v": ["无法搜索分组"] } } }, { "l": "zh-HK", "t": { "Unable to search the group": { "v": ["無法搜尋群組"] } } }, { "l": "zh-TW", "t": { "Unable to search the group": { "v": ["無法搜尋群組"] } } }];
const t50 = [{ "l": "ar", "t": { "Undo changes": { "v": ["تراجَع عن التغييرات"] } } }, { "l": "ast", "t": { "Undo changes": { "v": ["Desfacer los cambeos"] } } }, { "l": "br", "t": {} }, { "l": "ca", "t": { "Undo changes": { "v": ["Desfés els canvis"] } } }, { "l": "cs", "t": { "Undo changes": { "v": ["Vzít změny zpět"] } } }, { "l": "cs-CZ", "t": { "Undo changes": { "v": ["Vzít změny zpět"] } } }, { "l": "da", "t": { "Undo changes": { "v": ["Fortryd ændringer"] } } }, { "l": "de", "t": { "Undo changes": { "v": ["Änderungen rückgängig machen"] } } }, { "l": "de-DE", "t": { "Undo changes": { "v": ["Änderungen rückgängig machen"] } } }, { "l": "el", "t": { "Undo changes": { "v": ["Αναίρεση Αλλαγών"] } } }, { "l": "en-GB", "t": { "Undo changes": { "v": ["Undo changes"] } } }, { "l": "eo", "t": {} }, { "l": "es", "t": { "Undo changes": { "v": ["Deshacer cambios"] } } }, { "l": "es-AR", "t": { "Undo changes": { "v": ["Deshacer cambios"] } } }, { "l": "es-EC", "t": { "Undo changes": { "v": ["Deshacer cambios"] } } }, { "l": "es-MX", "t": { "Undo changes": { "v": ["Deshacer cambios"] } } }, { "l": "et-EE", "t": { "Undo changes": { "v": ["Pööra muudatused tagasi"] } } }, { "l": "eu", "t": { "Undo changes": { "v": ["Aldaketak desegin"] } } }, { "l": "fa", "t": { "Undo changes": { "v": ["لغو تغییرات"] } } }, { "l": "fi", "t": { "Undo changes": { "v": ["Kumoa muutokset"] } } }, { "l": "fr", "t": { "Undo changes": { "v": ["Annuler les changements"] } } }, { "l": "ga", "t": { "Undo changes": { "v": ["Cealaigh athruithe"] } } }, { "l": "gl", "t": { "Undo changes": { "v": ["Desfacer os cambios"] } } }, { "l": "he", "t": { "Undo changes": { "v": ["ביטול שינויים"] } } }, { "l": "hu", "t": { "Undo changes": { "v": ["Változtatások visszavonása"] } } }, { "l": "id", "t": { "Undo changes": { "v": ["Urungkan perubahan"] } } }, { "l": "is", "t": { "Undo changes": { "v": ["Afturkalla breytingar"] } } }, { "l": "it", "t": { "Undo changes": { "v": ["Cancella i cambiamenti"] } } }, { "l": "ja", "t": { "Undo changes": { "v": ["変更を取り消し"] } } }, { "l": "ja-JP", "t": { "Undo changes": { "v": ["変更を取り消し"] } } }, { "l": "ko", "t": { "Undo changes": { "v": ["변경 되돌리기"] } } }, { "l": "lo", "t": { "Undo changes": { "v": ["ຍ້ອນຄືນການປ່ຽນແປງ"] } } }, { "l": "lt-LT", "t": {} }, { "l": "lv", "t": {} }, { "l": "mk", "t": { "Undo changes": { "v": ["Врати ги промените"] } } }, { "l": "my", "t": {} }, { "l": "nb", "t": { "Undo changes": { "v": ["Tilbakestill endringer"] } } }, { "l": "nl", "t": { "Undo changes": { "v": ["Wijzigingen ongedaan maken"] } } }, { "l": "oc", "t": {} }, { "l": "pl", "t": { "Undo changes": { "v": ["Cofnij zmiany"] } } }, { "l": "pt-BR", "t": { "Undo changes": { "v": ["Desfazer modificações"] } } }, { "l": "pt-PT", "t": { "Undo changes": { "v": ["Anular alterações"] } } }, { "l": "ro", "t": { "Undo changes": { "v": ["Anularea modificărilor"] } } }, { "l": "ru", "t": { "Undo changes": { "v": ["Отменить изменения"] } } }, { "l": "sk", "t": { "Undo changes": { "v": ["Vrátiť zmeny"] } } }, { "l": "sl", "t": { "Undo changes": { "v": ["Razveljavi spremembe"] } } }, { "l": "sr", "t": { "Undo changes": { "v": ["Поништи измене"] } } }, { "l": "sv", "t": { "Undo changes": { "v": ["Ångra ändringar"] } } }, { "l": "tr", "t": { "Undo changes": { "v": ["Değişiklikleri geri al"] } } }, { "l": "uk", "t": { "Undo changes": { "v": ["Скасувати зміни"] } } }, { "l": "uz", "t": { "Undo changes": { "v": ["O'zgarishlarni bekor qilish"] } } }, { "l": "zh-CN", "t": { "Undo changes": { "v": ["撤销更改"] } } }, { "l": "zh-HK", "t": { "Undo changes": { "v": ["取消更改"] } } }, { "l": "zh-TW", "t": { "Undo changes": { "v": ["還原變更"] } } }];
window._nc_vue_element_id = window._nc_vue_element_id ?? 0;
function createElementId() {
  return `nc-vue-${window._nc_vue_element_id++}`;
}
/*!
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
const INSIDE_RADIO_GROUP_KEY = Symbol.for("insideRadioGroup");
function useInsideRadioGroup() {
  return inject(INSIDE_RADIO_GROUP_KEY, void 0);
}
const _sfc_main$8 = {
  name: "CheckboxBlankOutlineIcon",
  emits: ["click"],
  props: {
    title: {
      type: String
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
};
const _hoisted_1$8 = ["aria-hidden", "aria-label"];
const _hoisted_2$7 = ["fill", "width", "height"];
const _hoisted_3$7 = { d: "M19,3H5C3.89,3 3,3.89 3,5V19A2,2 0 0,0 5,21H19A2,2 0 0,0 21,19V5C21,3.89 20.1,3 19,3M19,5V19H5V5H19Z" };
const _hoisted_4$6 = { key: 0 };
function _sfc_render$8(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("span", mergeProps(_ctx.$attrs, {
    "aria-hidden": $props.title ? null : "true",
    "aria-label": $props.title,
    class: "material-design-icon checkbox-blank-outline-icon",
    role: "img",
    onClick: _cache[0] || (_cache[0] = ($event) => _ctx.$emit("click", $event))
  }), [
    (openBlock(), createElementBlock("svg", {
      fill: $props.fillColor,
      class: "material-design-icon__svg",
      width: $props.size,
      height: $props.size,
      viewBox: "0 0 24 24"
    }, [
      createBaseVNode("path", _hoisted_3$7, [
        $props.title ? (openBlock(), createElementBlock("title", _hoisted_4$6, toDisplayString($props.title), 1)) : createCommentVNode("", true)
      ])
    ], 8, _hoisted_2$7))
  ], 16, _hoisted_1$8);
}
const CheckboxBlankOutline = /* @__PURE__ */ _export_sfc$1(_sfc_main$8, [["render", _sfc_render$8]]);
const _sfc_main$7 = {
  name: "CheckboxMarkedIcon",
  emits: ["click"],
  props: {
    title: {
      type: String
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
};
const _hoisted_1$7 = ["aria-hidden", "aria-label"];
const _hoisted_2$6 = ["fill", "width", "height"];
const _hoisted_3$6 = { d: "M10,17L5,12L6.41,10.58L10,14.17L17.59,6.58L19,8M19,3H5C3.89,3 3,3.89 3,5V19A2,2 0 0,0 5,21H19A2,2 0 0,0 21,19V5C21,3.89 20.1,3 19,3Z" };
const _hoisted_4$5 = { key: 0 };
function _sfc_render$7(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("span", mergeProps(_ctx.$attrs, {
    "aria-hidden": $props.title ? null : "true",
    "aria-label": $props.title,
    class: "material-design-icon checkbox-marked-icon",
    role: "img",
    onClick: _cache[0] || (_cache[0] = ($event) => _ctx.$emit("click", $event))
  }), [
    (openBlock(), createElementBlock("svg", {
      fill: $props.fillColor,
      class: "material-design-icon__svg",
      width: $props.size,
      height: $props.size,
      viewBox: "0 0 24 24"
    }, [
      createBaseVNode("path", _hoisted_3$6, [
        $props.title ? (openBlock(), createElementBlock("title", _hoisted_4$5, toDisplayString($props.title), 1)) : createCommentVNode("", true)
      ])
    ], 8, _hoisted_2$6))
  ], 16, _hoisted_1$7);
}
const CheckboxMarked = /* @__PURE__ */ _export_sfc$1(_sfc_main$7, [["render", _sfc_render$7]]);
const _sfc_main$6 = {
  name: "MinusBoxIcon",
  emits: ["click"],
  props: {
    title: {
      type: String
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
};
const _hoisted_1$6 = ["aria-hidden", "aria-label"];
const _hoisted_2$5 = ["fill", "width", "height"];
const _hoisted_3$5 = { d: "M17,13H7V11H17M19,3H5C3.89,3 3,3.89 3,5V19A2,2 0 0,0 5,21H19A2,2 0 0,0 21,19V5C21,3.89 20.1,3 19,3Z" };
const _hoisted_4$4 = { key: 0 };
function _sfc_render$6(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("span", mergeProps(_ctx.$attrs, {
    "aria-hidden": $props.title ? null : "true",
    "aria-label": $props.title,
    class: "material-design-icon minus-box-icon",
    role: "img",
    onClick: _cache[0] || (_cache[0] = ($event) => _ctx.$emit("click", $event))
  }), [
    (openBlock(), createElementBlock("svg", {
      fill: $props.fillColor,
      class: "material-design-icon__svg",
      width: $props.size,
      height: $props.size,
      viewBox: "0 0 24 24"
    }, [
      createBaseVNode("path", _hoisted_3$5, [
        $props.title ? (openBlock(), createElementBlock("title", _hoisted_4$4, toDisplayString($props.title), 1)) : createCommentVNode("", true)
      ])
    ], 8, _hoisted_2$5))
  ], 16, _hoisted_1$6);
}
const MinusBox = /* @__PURE__ */ _export_sfc$1(_sfc_main$6, [["render", _sfc_render$6]]);
const _sfc_main$5 = {
  name: "RadioboxBlankIcon",
  emits: ["click"],
  props: {
    title: {
      type: String
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
};
const _hoisted_1$5 = ["aria-hidden", "aria-label"];
const _hoisted_2$4 = ["fill", "width", "height"];
const _hoisted_3$4 = { d: "M12,20A8,8 0 0,1 4,12A8,8 0 0,1 12,4A8,8 0 0,1 20,12A8,8 0 0,1 12,20M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2Z" };
const _hoisted_4$3 = { key: 0 };
function _sfc_render$5(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("span", mergeProps(_ctx.$attrs, {
    "aria-hidden": $props.title ? null : "true",
    "aria-label": $props.title,
    class: "material-design-icon radiobox-blank-icon",
    role: "img",
    onClick: _cache[0] || (_cache[0] = ($event) => _ctx.$emit("click", $event))
  }), [
    (openBlock(), createElementBlock("svg", {
      fill: $props.fillColor,
      class: "material-design-icon__svg",
      width: $props.size,
      height: $props.size,
      viewBox: "0 0 24 24"
    }, [
      createBaseVNode("path", _hoisted_3$4, [
        $props.title ? (openBlock(), createElementBlock("title", _hoisted_4$3, toDisplayString($props.title), 1)) : createCommentVNode("", true)
      ])
    ], 8, _hoisted_2$4))
  ], 16, _hoisted_1$5);
}
const RadioboxBlank = /* @__PURE__ */ _export_sfc$1(_sfc_main$5, [["render", _sfc_render$5]]);
const _sfc_main$4 = {
  name: "RadioboxMarkedIcon",
  emits: ["click"],
  props: {
    title: {
      type: String
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
};
const _hoisted_1$4 = ["aria-hidden", "aria-label"];
const _hoisted_2$3 = ["fill", "width", "height"];
const _hoisted_3$3 = { d: "M12,20A8,8 0 0,1 4,12A8,8 0 0,1 12,4A8,8 0 0,1 20,12A8,8 0 0,1 12,20M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2M12,7A5,5 0 0,0 7,12A5,5 0 0,0 12,17A5,5 0 0,0 17,12A5,5 0 0,0 12,7Z" };
const _hoisted_4$2$1 = { key: 0 };
function _sfc_render$4(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("span", mergeProps(_ctx.$attrs, {
    "aria-hidden": $props.title ? null : "true",
    "aria-label": $props.title,
    class: "material-design-icon radiobox-marked-icon",
    role: "img",
    onClick: _cache[0] || (_cache[0] = ($event) => _ctx.$emit("click", $event))
  }), [
    (openBlock(), createElementBlock("svg", {
      fill: $props.fillColor,
      class: "material-design-icon__svg",
      width: $props.size,
      height: $props.size,
      viewBox: "0 0 24 24"
    }, [
      createBaseVNode("path", _hoisted_3$3, [
        $props.title ? (openBlock(), createElementBlock("title", _hoisted_4$2$1, toDisplayString($props.title), 1)) : createCommentVNode("", true)
      ])
    ], 8, _hoisted_2$3))
  ], 16, _hoisted_1$4);
}
const RadioboxMarked = /* @__PURE__ */ _export_sfc$1(_sfc_main$4, [["render", _sfc_render$4]]);
const _sfc_main$3 = {
  name: "ToggleSwitchIcon",
  emits: ["click"],
  props: {
    title: {
      type: String
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
};
const _hoisted_1$3 = ["aria-hidden", "aria-label"];
const _hoisted_2$2$1 = ["fill", "width", "height"];
const _hoisted_3$2$1 = { d: "M17,7H7A5,5 0 0,0 2,12A5,5 0 0,0 7,17H17A5,5 0 0,0 22,12A5,5 0 0,0 17,7M17,15A3,3 0 0,1 14,12A3,3 0 0,1 17,9A3,3 0 0,1 20,12A3,3 0 0,1 17,15Z" };
const _hoisted_4$1$1 = { key: 0 };
function _sfc_render$3(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("span", mergeProps(_ctx.$attrs, {
    "aria-hidden": $props.title ? null : "true",
    "aria-label": $props.title,
    class: "material-design-icon toggle-switch-icon",
    role: "img",
    onClick: _cache[0] || (_cache[0] = ($event) => _ctx.$emit("click", $event))
  }), [
    (openBlock(), createElementBlock("svg", {
      fill: $props.fillColor,
      class: "material-design-icon__svg",
      width: $props.size,
      height: $props.size,
      viewBox: "0 0 24 24"
    }, [
      createBaseVNode("path", _hoisted_3$2$1, [
        $props.title ? (openBlock(), createElementBlock("title", _hoisted_4$1$1, toDisplayString($props.title), 1)) : createCommentVNode("", true)
      ])
    ], 8, _hoisted_2$2$1))
  ], 16, _hoisted_1$3);
}
const ToggleSwitch = /* @__PURE__ */ _export_sfc$1(_sfc_main$3, [["render", _sfc_render$3]]);
const _sfc_main$2$1 = {
  name: "ToggleSwitchOffIcon",
  emits: ["click"],
  props: {
    title: {
      type: String
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
};
const _hoisted_1$2$1 = ["aria-hidden", "aria-label"];
const _hoisted_2$1$1 = ["fill", "width", "height"];
const _hoisted_3$1$1 = { d: "M17,7H7A5,5 0 0,0 2,12A5,5 0 0,0 7,17H17A5,5 0 0,0 22,12A5,5 0 0,0 17,7M7,15A3,3 0 0,1 4,12A3,3 0 0,1 7,9A3,3 0 0,1 10,12A3,3 0 0,1 7,15Z" };
const _hoisted_4$7 = { key: 0 };
function _sfc_render$2(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("span", mergeProps(_ctx.$attrs, {
    "aria-hidden": $props.title ? null : "true",
    "aria-label": $props.title,
    class: "material-design-icon toggle-switch-off-icon",
    role: "img",
    onClick: _cache[0] || (_cache[0] = ($event) => _ctx.$emit("click", $event))
  }), [
    (openBlock(), createElementBlock("svg", {
      fill: $props.fillColor,
      class: "material-design-icon__svg",
      width: $props.size,
      height: $props.size,
      viewBox: "0 0 24 24"
    }, [
      createBaseVNode("path", _hoisted_3$1$1, [
        $props.title ? (openBlock(), createElementBlock("title", _hoisted_4$7, toDisplayString($props.title), 1)) : createCommentVNode("", true)
      ])
    ], 8, _hoisted_2$1$1))
  ], 16, _hoisted_1$2$1);
}
const ToggleSwitchOff = /* @__PURE__ */ _export_sfc$1(_sfc_main$2$1, [["render", _sfc_render$2]]);
const TYPE_CHECKBOX = "checkbox";
const TYPE_RADIO = "radio";
const TYPE_SWITCH = "switch";
const TYPE_BUTTON = "button";
const _sfc_main$1$1 = {
  name: "NcCheckboxContent",
  components: {
    NcLoadingIcon
  },
  props: {
    /**
     * Class for the icon element
     */
    iconClass: {
      type: [String, Object],
      default: null
    },
    /**
     * Class for the text element
     */
    textClass: {
      type: [String, Object],
      default: null
    },
    /**
     * Type of the input. checkbox, radio, switch, or button.
     *
     * Only use button when used in a `tablist` container and the
     * `tab` role is set.
     *
     * @type {'checkbox'|'radio'|'switch'|'button'}
     */
    type: {
      type: String,
      default: "checkbox",
      validator: (type) => [
        TYPE_CHECKBOX,
        TYPE_RADIO,
        TYPE_SWITCH,
        TYPE_BUTTON
      ].includes(type)
    },
    /**
     * Toggle the alternative button style
     */
    buttonVariant: {
      type: Boolean,
      default: false
    },
    /**
     * True if the entry is checked
     */
    isChecked: {
      type: Boolean,
      default: false
    },
    /**
     * Indeterminate state
     */
    indeterminate: {
      type: Boolean,
      default: false
    },
    /**
     * Loading state
     */
    loading: {
      type: Boolean,
      default: false
    },
    /**
     * Icon size
     */
    iconSize: {
      type: Number,
      default: 24
    },
    /**
     * Label id attribute
     */
    labelId: {
      type: String,
      required: true
    },
    /**
     * Description id attribute
     */
    descriptionId: {
      type: String,
      required: true
    }
  },
  computed: {
    isButtonType() {
      return this.type === TYPE_BUTTON;
    },
    /**
     * Returns the proper Material icon depending on the select case
     *
     * @return {object}
     */
    checkboxRadioIconElement() {
      if (this.type === TYPE_RADIO) {
        if (this.isChecked) {
          return RadioboxMarked;
        }
        return RadioboxBlank;
      }
      if (this.type === TYPE_SWITCH) {
        if (this.isChecked) {
          return ToggleSwitch;
        }
        return ToggleSwitchOff;
      }
      if (this.indeterminate) {
        return MinusBox;
      }
      if (this.isChecked) {
        return CheckboxMarked;
      }
      return CheckboxBlankOutline;
    }
  }
};
const _hoisted_1$1$1 = {
  key: 0,
  class: "checkbox-content__wrapper"
};
const _hoisted_2$8 = ["id"];
const _hoisted_3$8 = ["id"];
function _sfc_render$1$1(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_NcLoadingIcon = resolveComponent("NcLoadingIcon");
  return openBlock(), createElementBlock("span", {
    class: normalizeClass(["checkbox-content", {
      ["checkbox-content-" + $props.type]: true,
      "checkbox-content--button-variant": $props.buttonVariant,
      "checkbox-content--has-text": !!_ctx.$slots.default
    }])
  }, [
    createBaseVNode("span", {
      class: normalizeClass(["checkbox-content__icon", {
        "checkbox-content__icon--checked": $props.isChecked,
        "checkbox-content__icon--has-description": !$options.isButtonType && _ctx.$slots.description,
        [$props.iconClass]: true
      }]),
      "aria-hidden": true,
      inert: ""
    }, [
      renderSlot(_ctx.$slots, "icon", {
        checked: $props.isChecked,
        loading: $props.loading
      }, () => [
        $props.loading ? (openBlock(), createBlock(_component_NcLoadingIcon, { key: 0 })) : !$props.buttonVariant ? (openBlock(), createBlock(resolveDynamicComponent($options.checkboxRadioIconElement), {
          key: 1,
          size: $props.iconSize
        }, null, 8, ["size"])) : createCommentVNode("", true)
      ], true)
    ], 2),
    _ctx.$slots.default || _ctx.$slots.description ? (openBlock(), createElementBlock("span", _hoisted_1$1$1, [
      _ctx.$slots.default ? (openBlock(), createElementBlock("span", {
        key: 0,
        id: $props.labelId,
        class: normalizeClass(["checkbox-content__text", $props.textClass])
      }, [
        renderSlot(_ctx.$slots, "default", {}, void 0, true)
      ], 10, _hoisted_2$8)) : createCommentVNode("", true),
      !$options.isButtonType && _ctx.$slots.description ? (openBlock(), createElementBlock("span", {
        key: 1,
        id: $props.descriptionId,
        class: "checkbox-content__description"
      }, [
        renderSlot(_ctx.$slots, "description", {}, void 0, true)
      ], 8, _hoisted_3$8)) : createCommentVNode("", true)
    ])) : createCommentVNode("", true)
  ], 2);
}
const NcCheckboxContent = /* @__PURE__ */ _export_sfc$1(_sfc_main$1$1, [["render", _sfc_render$1$1], ["__scopeId", "data-v-a1ac280f"]]);
register();
const _sfc_main$9 = {
  name: "NcCheckboxRadioSwitch",
  components: {
    NcCheckboxContent
  },
  // We need to pass attributes to the input element
  inheritAttrs: false,
  props: {
    /**
     * Unique id attribute of the input
     */
    id: {
      type: String,
      default: () => "checkbox-radio-switch-" + createElementId(),
      validator: (id) => id.trim() !== ""
    },
    /**
     * Unique id attribute of the wrapper element
     */
    wrapperId: {
      type: String,
      default: null
    },
    /**
     * Input name. Required for radio, optional for checkbox, and ignored
     * for button.
     */
    name: {
      type: String,
      default: null
    },
    /**
     * Required if no text is set.
     * The aria-label is forwarded to the input or button.
     */
    ariaLabel: {
      type: String,
      default: ""
    },
    /**
     * Type of the input. checkbox, radio, switch, or button.
     *
     * Only use button when used in a `tablist` container and the
     * `tab` role is set.
     *
     * @type {'checkbox'|'radio'|'switch'|'button'}
     */
    type: {
      type: String,
      default: "checkbox",
      validator: (type) => [
        TYPE_CHECKBOX,
        TYPE_RADIO,
        TYPE_SWITCH,
        TYPE_BUTTON
      ].includes(type)
    },
    /**
     * Toggle the alternative button style
     *
     * @deprecated - Use `NcRadioGroup` instead
     */
    buttonVariant: {
      type: Boolean,
      default: false
    },
    /**
     * Are the elements are all direct siblings?
     * If so they will be grouped horizontally or vertically
     *
     * @type {'no'|'horizontal'|'vertical'}
     * @deprecated - Use `NcRadioGroup` instead
     */
    buttonVariantGrouped: {
      type: String,
      default: "no",
      validator: (v) => ["no", "vertical", "horizontal"].includes(v)
    },
    /**
     * Checked state. To be used with `v-model:value`
     */
    modelValue: {
      type: [Boolean, Array, String],
      default: false
    },
    /**
     * Value to be synced on check
     */
    value: {
      type: String,
      default: null
    },
    /**
     * Disabled state
     */
    disabled: {
      type: Boolean,
      default: false
    },
    /**
     * Indeterminate state
     */
    indeterminate: {
      type: Boolean,
      default: false
    },
    /**
     * Required state
     */
    required: {
      type: Boolean,
      default: false
    },
    /**
     * Loading state
     */
    loading: {
      type: Boolean,
      default: false
    },
    /**
     * Wrapping element tag
     *
     * When `type` is set to `button` this will be ignored
     *
     * Defaults to `span`
     */
    wrapperElement: {
      type: String,
      default: null
    },
    /**
     * The class(es) to pass to the wrapper / root element of the component
     */
    class: {
      type: [String, Array, Object],
      default: ""
    },
    /**
     * The style to pass to the wrapper / root element of the component
     */
    style: {
      type: [String, Array, Object],
      default: ""
    },
    /**
     * Description
     *
     * This is unsupported when using button has type.
     */
    description: {
      type: String,
      default: null
    }
  },
  emits: ["update:modelValue"],
  setup(props, { emit: emit2 }) {
    const radioGroup = useInsideRadioGroup();
    onMounted(() => radioGroup?.value.register(false));
    const internalType = computed(() => radioGroup?.value ? TYPE_RADIO : props.type);
    const internalModelValue = computed({
      get() {
        if (radioGroup?.value) {
          return radioGroup.value.modelValue;
        }
        return props.modelValue;
      },
      set(value) {
        if (radioGroup?.value) {
          radioGroup.value.onUpdate(value);
        } else {
          emit2("update:modelValue", value);
        }
      }
    });
    return {
      internalType,
      internalModelValue,
      labelId: createElementId(),
      descriptionId: createElementId()
    };
  },
  computed: {
    isButtonType() {
      return this.internalType === TYPE_BUTTON;
    },
    computedWrapperElement() {
      if (this.isButtonType) {
        return "button";
      }
      if (this.wrapperElement !== null) {
        return this.wrapperElement;
      }
      return "span";
    },
    listeners() {
      if (this.isButtonType) {
        return {
          click: this.onToggle
        };
      }
      return {
        change: this.onToggle
      };
    },
    iconSize() {
      return this.internalType === TYPE_SWITCH ? 36 : 20;
    },
    cssIconSize() {
      return this.iconSize + "px";
    },
    cssIconHeight() {
      return this.internalType === TYPE_SWITCH ? "16px" : this.cssIconSize;
    },
    /**
     * Return the input type.
     * Switch is not an official type
     *
     * @return {string}
     */
    inputType() {
      const nativeTypes = [
        TYPE_CHECKBOX,
        TYPE_RADIO,
        TYPE_BUTTON
      ];
      if (nativeTypes.includes(this.internalType)) {
        return this.internalType;
      }
      return TYPE_CHECKBOX;
    },
    /**
     * Check if that entry is checked
     * If value is defined, we use that as the checked value
     * If not, we expect true/false in this.checked
     *
     * @return {boolean}
     */
    isChecked() {
      if (this.value !== null) {
        if (Array.isArray(this.internalModelValue)) {
          return [...this.internalModelValue].indexOf(this.value) > -1;
        }
        return this.internalModelValue === this.value;
      }
      return this.internalModelValue === true;
    },
    hasIndeterminate() {
      return [
        TYPE_CHECKBOX,
        TYPE_RADIO
      ].includes(this.inputType);
    }
  },
  mounted() {
    if (this.name && this.internalType === TYPE_CHECKBOX) {
      if (!Array.isArray(this.internalModelValue)) {
        throw new Error("When using groups of checkboxes, the updated value will be an array.");
      }
    }
    if (this.name && this.internalType === TYPE_SWITCH) {
      throw new Error("Switches are not made to be used for data sets. Please use checkboxes instead.");
    }
    if (typeof this.internalModelValue !== "boolean" && this.internalType === TYPE_SWITCH) {
      throw new Error("Switches can only be used with boolean as modelValue prop.");
    }
  },
  methods: {
    t,
    n,
    onToggle(event) {
      if (this.disabled || event.target.tagName.toLowerCase() === "a") {
        return;
      }
      if (this.internalType === TYPE_RADIO) {
        this.internalModelValue = this.value;
        return;
      }
      if (this.internalType === TYPE_SWITCH) {
        this.internalModelValue = !this.isChecked;
        return;
      }
      if (typeof this.internalModelValue === "boolean") {
        this.internalModelValue = !this.internalModelValue;
        return;
      }
      const values = this.getInputsSet().filter((input) => input.checked).map((input) => input.value);
      if (values.includes(this.value)) {
        this.internalModelValue = values.filter((v) => v !== this.value);
      } else {
        this.internalModelValue = [...values, this.value];
      }
    },
    /**
     * Get the input set based on this name
     *
     * @return {Node[]}
     */
    getInputsSet() {
      return [...document.getElementsByName(this.name)];
    }
  }
};
const __injectCSSVars__ = () => {
  useCssVars((_ctx) => ({
    "65a7082e": _ctx.cssIconSize,
    "20f7d30f": _ctx.cssIconHeight
  }));
};
const __setup__ = _sfc_main$9.setup;
_sfc_main$9.setup = __setup__ ? (props, ctx) => {
  __injectCSSVars__();
  return __setup__(props, ctx);
} : __injectCSSVars__;
const _hoisted_1$9 = ["id", "aria-labelledby", "aria-describedby", "aria-label", "disabled", "type", "value", "checked", ".indeterminate", "required", "name"];
function _sfc_render$9(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_NcCheckboxContent = resolveComponent("NcCheckboxContent");
  return openBlock(), createBlock(resolveDynamicComponent($options.computedWrapperElement), mergeProps({
    id: $props.wrapperId ?? ($options.isButtonType ? $props.id : null),
    "aria-label": $options.isButtonType && $props.ariaLabel ? $props.ariaLabel : void 0,
    class: ["checkbox-radio-switch", [
      _ctx.$props.class,
      {
        ["checkbox-radio-switch-" + $setup.internalType]: $setup.internalType,
        "checkbox-radio-switch--checked": $options.isChecked,
        "checkbox-radio-switch--disabled": $props.disabled,
        "checkbox-radio-switch--indeterminate": $options.hasIndeterminate ? $props.indeterminate : false,
        "checkbox-radio-switch--button-variant": $props.buttonVariant,
        "checkbox-radio-switch--button-variant-v-grouped": $props.buttonVariant && $props.buttonVariantGrouped === "vertical",
        "checkbox-radio-switch--button-variant-h-grouped": $props.buttonVariant && $props.buttonVariantGrouped === "horizontal",
        "button-vue": $options.isButtonType
      }
    ]],
    style: $props.style,
    type: $options.isButtonType ? "button" : null
  }, $options.isButtonType ? _ctx.$attrs : {}, toHandlers($options.isButtonType ? $options.listeners : {})), {
    default: withCtx(() => [
      !$options.isButtonType ? (openBlock(), createElementBlock("input", mergeProps({
        key: 0,
        id: $props.id,
        "aria-labelledby": !$options.isButtonType && !$props.ariaLabel ? $setup.labelId : null,
        "aria-describedby": !$options.isButtonType && ($props.description || _ctx.$slots.description) ? $setup.descriptionId : null,
        "aria-label": $props.ariaLabel || void 0,
        class: "checkbox-radio-switch__input",
        disabled: $props.disabled,
        type: $options.inputType,
        value: $props.value,
        checked: $options.isChecked,
        ".indeterminate": $options.hasIndeterminate ? $props.indeterminate : null,
        required: $props.required,
        name: $props.name
      }, _ctx.$attrs, toHandlers($options.listeners, true)), null, 48, _hoisted_1$9)) : createCommentVNode("", true),
      createVNode(_component_NcCheckboxContent, {
        id: !$options.isButtonType ? `${$props.id}-label` : void 0,
        class: "checkbox-radio-switch__content",
        "icon-class": "checkbox-radio-switch__icon",
        "text-class": "checkbox-radio-switch__text",
        type: $setup.internalType,
        indeterminate: $options.hasIndeterminate ? $props.indeterminate : false,
        "button-variant": $props.buttonVariant,
        "is-checked": $options.isChecked,
        loading: $props.loading,
        "label-id": $setup.labelId,
        "description-id": $setup.descriptionId,
        "icon-size": $options.iconSize,
        onClick: $options.onToggle
      }, createSlots({
        icon: withCtx(() => [
          renderSlot(_ctx.$slots, "icon", {}, void 0, true)
        ]),
        _: 2
      }, [
        _ctx.$slots.description || $props.description ? {
          name: "description",
          fn: withCtx(() => [
            renderSlot(_ctx.$slots, "description", {}, () => [
              createTextVNode(toDisplayString($props.description), 1)
            ], true)
          ]),
          key: "0"
        } : void 0,
        !!_ctx.$slots.default ? {
          name: "default",
          fn: withCtx(() => [
            renderSlot(_ctx.$slots, "default", {}, void 0, true)
          ]),
          key: "1"
        } : void 0
      ]), 1032, ["id", "type", "indeterminate", "button-variant", "is-checked", "loading", "label-id", "description-id", "icon-size", "onClick"])
    ]),
    _: 3
  }, 16, ["id", "aria-label", "class", "style", "type"]);
}
const NcCheckboxRadioSwitch = /* @__PURE__ */ _export_sfc$1(_sfc_main$9, [["render", _sfc_render$9], ["__scopeId", "data-v-0dcb138a"]]);
const _sfc_main$1 = {
  name: "HelpCircleIcon",
  emits: ["click"],
  props: {
    title: {
      type: String
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
};
const _hoisted_1$1 = ["aria-hidden", "aria-label"];
const _hoisted_2$1 = ["fill", "width", "height"];
const _hoisted_3$1 = { d: "M15.07,11.25L14.17,12.17C13.45,12.89 13,13.5 13,15H11V14.5C11,13.39 11.45,12.39 12.17,11.67L13.41,10.41C13.78,10.05 14,9.55 14,9C14,7.89 13.1,7 12,7A2,2 0 0,0 10,9H8A4,4 0 0,1 12,5A4,4 0 0,1 16,9C16,9.88 15.64,10.67 15.07,11.25M13,19H11V17H13M12,2A10,10 0 0,0 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12C22,6.47 17.5,2 12,2Z" };
const _hoisted_4$1 = { key: 0 };
function _sfc_render$1(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("span", mergeProps(_ctx.$attrs, {
    "aria-hidden": $props.title ? null : "true",
    "aria-label": $props.title,
    class: "material-design-icon help-circle-icon",
    role: "img",
    onClick: _cache[0] || (_cache[0] = ($event) => _ctx.$emit("click", $event))
  }), [
    (openBlock(), createElementBlock("svg", {
      fill: $props.fillColor,
      class: "material-design-icon__svg",
      width: $props.size,
      height: $props.size,
      viewBox: "0 0 24 24"
    }, [
      createBaseVNode("path", _hoisted_3$1, [
        $props.title ? (openBlock(), createElementBlock("title", _hoisted_4$1, toDisplayString($props.title), 1)) : createCommentVNode("", true)
      ])
    ], 8, _hoisted_2$1))
  ], 16, _hoisted_1$1);
}
const HelpCircle = /* @__PURE__ */ _export_sfc$1(_sfc_main$1, [["render", _sfc_render$1]]);
register();
const _hoisted_1$2 = { class: "settings-section" };
const _hoisted_2$2 = { class: "settings-section__name" };
const _hoisted_3$2 = ["aria-label", "href", "title"];
const _hoisted_4$2 = {
  key: 0,
  class: "settings-section__desc"
};
const _sfc_main$2 = /* @__PURE__ */ defineComponent({
  __name: "NcSettingsSection",
  props: {
    name: {},
    description: { default: "" },
    docUrl: { default: "" }
  },
  setup(__props) {
    const ariaLabel = t("External documentation");
    return (_ctx, _cache) => {
      return openBlock(), createElementBlock("div", _hoisted_1$2, [
        createBaseVNode("h2", _hoisted_2$2, [
          createTextVNode(toDisplayString(_ctx.name) + " ", 1),
          _ctx.docUrl ? (openBlock(), createElementBlock("a", {
            key: 0,
            "aria-label": unref(ariaLabel),
            class: "settings-section__info",
            href: _ctx.docUrl,
            rel: "noreferrer nofollow",
            target: "_blank",
            title: unref(ariaLabel)
          }, [
            createVNode(HelpCircle, { size: 20 })
          ], 8, _hoisted_3$2)) : createCommentVNode("", true)
        ]),
        _ctx.description ? (openBlock(), createElementBlock("p", _hoisted_4$2, toDisplayString(_ctx.description), 1)) : createCommentVNode("", true),
        renderSlot(_ctx.$slots, "default", {}, void 0, true)
      ]);
    };
  }
});
const NcSettingsSection = /* @__PURE__ */ _export_sfc$1(_sfc_main$2, [["__scopeId", "data-v-9cedb949"]]);
const _export_sfc = (sfc, props) => {
  const target = sfc.__vccOpts || sfc;
  for (const [key, val] of props) {
    target[key] = val;
  }
  return target;
};
const _sfc_main = {
  name: "TrashCanOutlineIcon",
  emits: ["click"],
  props: {
    title: {
      type: String
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
};
const _hoisted_1 = ["aria-hidden", "aria-label"];
const _hoisted_2 = ["fill", "width", "height"];
const _hoisted_3 = { d: "M9,3V4H4V6H5V19A2,2 0 0,0 7,21H17A2,2 0 0,0 19,19V6H20V4H15V3H9M7,6H17V19H7V6M9,8V17H11V8H9M13,8V17H15V8H13Z" };
const _hoisted_4 = { key: 0 };
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("span", mergeProps(_ctx.$attrs, {
    "aria-hidden": $props.title ? null : "true",
    "aria-label": $props.title,
    class: "material-design-icon trash-can-outline-icon",
    role: "img",
    onClick: _cache[0] || (_cache[0] = ($event) => _ctx.$emit("click", $event))
  }), [
    (openBlock(), createElementBlock("svg", {
      fill: $props.fillColor,
      class: "material-design-icon__svg",
      width: $props.size,
      height: $props.size,
      viewBox: "0 0 24 24"
    }, [
      createBaseVNode("path", _hoisted_3, [
        $props.title ? (openBlock(), createElementBlock(
          "title",
          _hoisted_4,
          toDisplayString($props.title),
          1
          /* TEXT */
        )) : createCommentVNode("v-if", true)
      ])
    ], 8, _hoisted_2))
  ], 16, _hoisted_1);
}
const Delete = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render], ["__file", "/home/admin/Docker/workspace/server/node_modules/vue-material-design-icons/TrashCanOutline.vue"]]);
export {
  getGettextBuilder as $,
  onRequestTokenUpdate as A,
  reactive as B,
  inject as C,
  h as D,
  unref as E,
  Fragment as F,
  getCurrentInstance as G,
  watchEffect as H,
  _export_sfc$1 as I,
  mergeModels as J,
  useModel as K,
  warn as L,
  watch as M,
  NcSettingsSection as N,
  renderSlot as O,
  resolveDynamicComponent as P,
  mergeProps as Q,
  normalizeStyle as R,
  getLanguage as S,
  getCanonicalLocale as T,
  emit as U,
  isAxiosError as V,
  onBeforeMount as W,
  onMounted as X,
  withModifiers as Y,
  withKeys as Z,
  _export_sfc as _,
  NcCheckboxRadioSwitch as a,
  t10 as a$,
  useTemplateRef as a0,
  nextTick as a1,
  getRootUrl as a2,
  Delete as a3,
  subscribe as a4,
  onBeforeUnmount as a5,
  unsubscribe as a6,
  effectScope as a7,
  defineAsyncComponent as a8,
  translatePlural as a9,
  toValue as aA,
  onUnmounted as aB,
  pushScopeId as aC,
  popScopeId as aD,
  withScopeId as aE,
  isRTL as aF,
  t4 as aG,
  Comment as aH,
  Text as aI,
  shallowRef as aJ,
  shallowReadonly as aK,
  readonly as aL,
  customRef as aM,
  getBuilder as aN,
  t14 as aO,
  t21 as aP,
  t50 as aQ,
  t23 as aR,
  useSlots as aS,
  t2 as aT,
  Transition as aU,
  Teleport as aV,
  toHandlers as aW,
  t15 as aX,
  getBaseUrl as aY,
  t3 as aZ,
  t11 as a_,
  global as aa,
  Buffer as ab,
  markRaw as ac,
  toRaw as ad,
  hasInjectionContext as ae,
  isRef as af,
  isReactive as ag,
  toRef as ah,
  getCurrentScope as ai,
  onScopeDispose as aj,
  toRefs as ak,
  process$1 as al,
  getDefaultExportFromCjs$1 as am,
  withDirectives as an,
  vShow as ao,
  createSlots as ap,
  normalizeProps as aq,
  guardReactiveProps as ar,
  getLocale as as,
  useCssVars as at,
  purify as au,
  register as av,
  t as aw,
  createElementId as ax,
  useAttrs as ay,
  t48 as az,
  createVNode as b,
  resolveDirective as b0,
  t17 as b1,
  t28 as b2,
  t6 as b3,
  vModelText as b4,
  t35 as b5,
  render as b6,
  onBeforeUpdate as b7,
  t42 as b8,
  t37 as b9,
  t16 as ba,
  t5 as bb,
  t22 as bc,
  t38 as bd,
  t41 as be,
  t9 as bf,
  t8 as bg,
  imagePath as bh,
  t45 as bi,
  t40 as bj,
  t25 as bk,
  t32 as bl,
  t19 as bm,
  t12 as bn,
  t34 as bo,
  t0 as bp,
  t49 as bq,
  createBlock as c,
  createTextVNode as d,
  cancelableClient as e,
  translate as f,
  generateOcsUrl as g,
  createApp as h,
  generateUrl as i,
  createCommentVNode as j,
  createBaseVNode as k,
  loadState as l,
  ref as m,
  defineComponent as n,
  openBlock as o,
  computed as p,
  NcLoadingIcon as q,
  resolveComponent as r,
  createElementBlock as s,
  toDisplayString as t,
  renderList as u,
  normalizeClass as v,
  withCtx as w,
  getCurrentUser as x,
  generateRemoteUrl as y,
  getRequestToken as z
};
//# sourceMappingURL=TrashCanOutline-CLxw5nIJ.chunk.mjs.map
