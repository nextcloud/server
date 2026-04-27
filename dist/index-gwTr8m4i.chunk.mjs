const appName = "nextcloud-ui";
const appVersion = "1.0.0";
import { d as debounce } from "./index-rAufP352.chunk.mjs";
import { g as getAvatarUrl, u as useIsDarkTheme, s as stripTags } from "./NcAvatar-C9d7Wrc8-C3hNgT1e.chunk.mjs";
import { N as NcUserStatusIcon } from "./NcUserStatusIcon-XiwrgeCm-DTG2zfIi.chunk.mjs";
import { r as resolveComponent, o as openBlock, f as createElementBlock, g as createBaseVNode, t as toDisplayString, c as createBlock, h as createCommentVNode, N as normalizeStyle, v as normalizeClass, m as mergeProps, M as withModifiers, V as withKeys, e as createApp } from "./preload-helper-xAe3EUYB.chunk.mjs";
import { r as register, q as t37, v as t34, b as t, _ as _export_sfc, n, c as createElementId } from "./Web-BOM4en5n.chunk.mjs";
import { e as emojiSearch, a as emojiAddRecent } from "./emoji-BY_D0V5K-BlCul1cD.chunk.mjs";
import { e as escapeHTML } from "./translation-DoG5ZELJ-2UfAUX2V.chunk.mjs";
import { l as logger } from "./ArrowRight-BC77f5L9.chunk.mjs";
import "./index-D5H5XMHa.chunk.mjs";
import { g as getLinkWithPicker, s as searchProvider } from "./index-D5BR15En.chunk.mjs";
import "./NcEmptyContent-B8-90BSI-BIPGjjpl.chunk.mjs";
import "./NcSelect-DLheQ2yp-DVxJhLyT.chunk.mjs";
import "./TrashCanOutline-DgEtyFGH.chunk.mjs";
import "./NcTextField.vue_vue_type_script_setup_true_lang-BxkYy7wv-Beu3Njvy.chunk.mjs";
import "./NcInputField-o5OFv3z6-D-7orWgm.chunk.mjs";
import "./NcModal-DHryP_87-Da4dXMUU.chunk.mjs";
import "./colors-BHGKZFDI-C0-WujoK.chunk.mjs";
import "./index-o76qk6sn.chunk.mjs";
import "./PencilOutline-BMYBdzdS.chunk.mjs";
import "./NcDateTime.vue_vue_type_script_setup_true_lang-BhB8yA4U-DXOBfuGJ.chunk.mjs";
import "./util-BSOXDoOW.chunk.mjs";
import "./NcCheckboxRadioSwitch-BMsPx74L-BYrlnEKO.chunk.mjs";
if (!Array.prototype.find) {
  Array.prototype.find = function(predicate) {
    if (this === null) {
      throw new TypeError("Array.prototype.find called on null or undefined");
    }
    if (typeof predicate !== "function") {
      throw new TypeError("predicate must be a function");
    }
    var list = Object(this);
    var length = list.length >>> 0;
    var thisArg = arguments[1];
    var value;
    for (var i = 0; i < length; i++) {
      value = list[i];
      if (predicate.call(thisArg, value, i, list)) {
        return value;
      }
    }
    return void 0;
  };
}
if (window && typeof window.CustomEvent !== "function") {
  let CustomEvent$1 = function(event, params) {
    params = params || {
      bubbles: false,
      cancelable: false,
      detail: void 0
    };
    var evt = document.createEvent("CustomEvent");
    evt.initCustomEvent(event, params.bubbles, params.cancelable, params.detail);
    return evt;
  };
  if (typeof window.Event !== "undefined") {
    CustomEvent$1.prototype = window.Event.prototype;
  }
  window.CustomEvent = CustomEvent$1;
}
class TributeEvents {
  constructor(tribute) {
    this.tribute = tribute;
    this.tribute.events = this;
  }
  static keys() {
    return [
      {
        key: 9,
        value: "TAB"
      },
      {
        key: 8,
        value: "DELETE"
      },
      {
        key: 13,
        value: "ENTER"
      },
      {
        key: 27,
        value: "ESCAPE"
      },
      {
        key: 32,
        value: "SPACE"
      },
      {
        key: 38,
        value: "UP"
      },
      {
        key: 40,
        value: "DOWN"
      }
    ];
  }
  bind(element) {
    element.boundKeydown = this.keydown.bind(element, this);
    element.boundKeyup = this.keyup.bind(element, this);
    element.boundInput = this.input.bind(element, this);
    element.addEventListener("keydown", element.boundKeydown, false);
    element.addEventListener("keyup", element.boundKeyup, false);
    element.addEventListener("input", element.boundInput, false);
  }
  unbind(element) {
    element.removeEventListener("keydown", element.boundKeydown, false);
    element.removeEventListener("keyup", element.boundKeyup, false);
    element.removeEventListener("input", element.boundInput, false);
    delete element.boundKeydown;
    delete element.boundKeyup;
    delete element.boundInput;
  }
  keydown(instance, event) {
    if (instance.shouldDeactivate(event)) {
      instance.tribute.isActive = false;
      instance.tribute.hideMenu();
    }
    let element = this;
    instance.commandEvent = false;
    TributeEvents.keys().forEach((o) => {
      if (o.key === event.keyCode) {
        instance.commandEvent = true;
        instance.callbacks()[o.value.toLowerCase()](event, element);
      }
    });
  }
  input(instance, event) {
    instance.inputEvent = true;
    instance.keyup.call(this, instance, event);
  }
  click(instance, event) {
    let tribute = instance.tribute;
    if (tribute.menu && tribute.menu.contains(event.target)) {
      let li = event.target;
      event.preventDefault();
      event.stopPropagation();
      while (li.nodeName.toLowerCase() !== "li") {
        li = li.parentNode;
        if (!li || li === tribute.menu) {
          throw new Error("cannot find the <li> container for the click");
        }
      }
      tribute.selectItemAtIndex(li.getAttribute("data-index"), event);
      tribute.hideMenu();
    } else if (tribute.current.element && !tribute.current.externalTrigger) {
      tribute.current.externalTrigger = false;
      setTimeout(() => tribute.hideMenu());
    }
  }
  keyup(instance, event) {
    if (instance.inputEvent) {
      instance.inputEvent = false;
    }
    instance.updateSelection(this);
    if (event.keyCode === 27) return;
    if (!instance.tribute.allowSpaces && instance.tribute.hasTrailingSpace) {
      instance.tribute.hasTrailingSpace = false;
      instance.commandEvent = true;
      instance.callbacks()["space"](event, this);
      return;
    }
    if (!instance.tribute.isActive) {
      if (instance.tribute.autocompleteMode) {
        instance.callbacks().triggerChar(event, this, "");
      } else {
        let keyCode = instance.getKeyCode(instance, this, event);
        if (isNaN(keyCode) || !keyCode) return;
        let trigger = instance.tribute.triggers().find((trigger2) => {
          return trigger2.charCodeAt(0) === keyCode;
        });
        if (typeof trigger !== "undefined") {
          instance.callbacks().triggerChar(event, this, trigger);
        }
      }
    }
    if (instance.tribute.current.mentionText.length < instance.tribute.current.collection.menuShowMinLength) {
      return;
    }
    if ((instance.tribute.current.trigger || instance.tribute.autocompleteMode) && instance.commandEvent === false || instance.tribute.isActive && event.keyCode === 8) {
      instance.tribute.showMenuFor(this, true);
    }
  }
  shouldDeactivate(event) {
    if (!this.tribute.isActive) return false;
    if (this.tribute.current.mentionText.length === 0) {
      let eventKeyPressed = false;
      TributeEvents.keys().forEach((o) => {
        if (event.keyCode === o.key) eventKeyPressed = true;
      });
      return !eventKeyPressed;
    }
    return false;
  }
  getKeyCode(instance, el, event) {
    let tribute = instance.tribute;
    let info = tribute.range.getTriggerInfo(
      false,
      tribute.hasTrailingSpace,
      true,
      tribute.allowSpaces,
      tribute.autocompleteMode
    );
    if (info) {
      return info.mentionTriggerChar.charCodeAt(0);
    } else {
      return false;
    }
  }
  updateSelection(el) {
    this.tribute.current.element = el;
    let info = this.tribute.range.getTriggerInfo(
      false,
      this.tribute.hasTrailingSpace,
      true,
      this.tribute.allowSpaces,
      this.tribute.autocompleteMode
    );
    if (info) {
      this.tribute.current.selectedPath = info.mentionSelectedPath;
      this.tribute.current.mentionText = info.mentionText;
      this.tribute.current.selectedOffset = info.mentionSelectedOffset;
    }
  }
  callbacks() {
    return {
      triggerChar: (e, el, trigger) => {
        let tribute = this.tribute;
        tribute.current.trigger = trigger;
        let collectionItem = tribute.collection.find((item) => {
          return item.trigger === trigger;
        });
        tribute.current.collection = collectionItem;
        if (tribute.current.mentionText.length >= tribute.current.collection.menuShowMinLength && tribute.inputEvent) {
          tribute.showMenuFor(el, true);
        }
      },
      enter: (e, el) => {
        if (this.tribute.isActive && this.tribute.current.filteredItems) {
          e.preventDefault();
          e.stopPropagation();
          setTimeout(() => {
            this.tribute.selectItemAtIndex(this.tribute.menuSelected, e);
            this.tribute.hideMenu();
          }, 0);
        }
      },
      escape: (e, el) => {
        if (this.tribute.isActive) {
          e.preventDefault();
          e.stopPropagation();
          this.tribute.isActive = false;
          this.tribute.hideMenu();
        }
      },
      tab: (e, el) => {
        this.callbacks().enter(e, el);
      },
      space: (e, el) => {
        if (this.tribute.isActive) {
          if (this.tribute.spaceSelectsMatch) {
            this.callbacks().enter(e, el);
          } else if (!this.tribute.allowSpaces) {
            e.stopPropagation();
            setTimeout(() => {
              this.tribute.hideMenu();
              this.tribute.isActive = false;
            }, 0);
          }
        }
      },
      up: (e, el) => {
        if (this.tribute.isActive && this.tribute.current.filteredItems) {
          e.preventDefault();
          e.stopPropagation();
          let count = this.tribute.current.filteredItems.length, selected = this.tribute.menuSelected;
          if (count > selected && selected > 0) {
            this.tribute.menuSelected--;
            this.setActiveLi();
          } else if (selected === 0) {
            this.tribute.menuSelected = count - 1;
            this.setActiveLi();
            this.tribute.menu.scrollTop = this.tribute.menu.scrollHeight;
          }
        }
      },
      down: (e, el) => {
        if (this.tribute.isActive && this.tribute.current.filteredItems) {
          e.preventDefault();
          e.stopPropagation();
          let count = this.tribute.current.filteredItems.length - 1, selected = this.tribute.menuSelected;
          if (count > selected) {
            this.tribute.menuSelected++;
            this.setActiveLi();
          } else if (count === selected) {
            this.tribute.menuSelected = 0;
            this.setActiveLi();
            this.tribute.menu.scrollTop = 0;
          }
        }
      },
      delete: (e, el) => {
        if (this.tribute.isActive && this.tribute.current.mentionText.length < 1) {
          this.tribute.hideMenu();
        } else if (this.tribute.isActive) {
          this.tribute.showMenuFor(el);
        }
      }
    };
  }
  setActiveLi(index) {
    let lis = this.tribute.menu.querySelectorAll("li"), length = lis.length >>> 0;
    if (index) this.tribute.menuSelected = parseInt(index);
    for (let i = 0; i < length; i++) {
      let li = lis[i];
      if (i === this.tribute.menuSelected) {
        li.classList.add(this.tribute.current.collection.selectClass);
        let liClientRect = li.getBoundingClientRect();
        let menuClientRect = this.tribute.menu.getBoundingClientRect();
        if (liClientRect.bottom > menuClientRect.bottom) {
          let scrollDistance = liClientRect.bottom - menuClientRect.bottom;
          this.tribute.menu.scrollTop += scrollDistance;
        } else if (liClientRect.top < menuClientRect.top) {
          let scrollDistance = menuClientRect.top - liClientRect.top;
          this.tribute.menu.scrollTop -= scrollDistance;
        }
      } else {
        li.classList.remove(this.tribute.current.collection.selectClass);
      }
    }
  }
  getFullHeight(elem, includeMargin) {
    let height = elem.getBoundingClientRect().height;
    if (includeMargin) {
      let style = elem.currentStyle || window.getComputedStyle(elem);
      return height + parseFloat(style.marginTop) + parseFloat(style.marginBottom);
    }
    return height;
  }
}
class TributeMenuEvents {
  constructor(tribute) {
    this.tribute = tribute;
    this.tribute.menuEvents = this;
    this.menu = this.tribute.menu;
  }
  bind(menu) {
    this.menuClickEvent = this.tribute.events.click.bind(null, this);
    this.menuContainerScrollEvent = this.debounce(
      () => {
        if (this.tribute.isActive) {
          this.tribute.showMenuFor(this.tribute.current.element, false);
        }
      },
      300,
      false
    );
    this.windowResizeEvent = this.debounce(
      () => {
        if (this.tribute.isActive) {
          this.tribute.range.positionMenuAtCaret(true);
        }
      },
      300,
      false
    );
    this.tribute.range.getDocument().addEventListener("MSPointerDown", this.menuClickEvent, false);
    this.tribute.range.getDocument().addEventListener("mousedown", this.menuClickEvent, false);
    window.addEventListener("resize", this.windowResizeEvent);
    if (this.menuContainer) {
      this.menuContainer.addEventListener(
        "scroll",
        this.menuContainerScrollEvent,
        false
      );
    } else {
      window.addEventListener("scroll", this.menuContainerScrollEvent);
    }
  }
  unbind(menu) {
    this.tribute.range.getDocument().removeEventListener("mousedown", this.menuClickEvent, false);
    this.tribute.range.getDocument().removeEventListener("MSPointerDown", this.menuClickEvent, false);
    window.removeEventListener("resize", this.windowResizeEvent);
    if (this.menuContainer) {
      this.menuContainer.removeEventListener(
        "scroll",
        this.menuContainerScrollEvent,
        false
      );
    } else {
      window.removeEventListener("scroll", this.menuContainerScrollEvent);
    }
  }
  debounce(func, wait, immediate) {
    var timeout;
    return () => {
      var context = this, args = arguments;
      var later = () => {
        timeout = null;
        if (!immediate) func.apply(context, args);
      };
      var callNow = immediate && !timeout;
      clearTimeout(timeout);
      timeout = setTimeout(later, wait);
      if (callNow) func.apply(context, args);
    };
  }
}
class TributeRange {
  constructor(tribute) {
    this.tribute = tribute;
    this.tribute.range = this;
  }
  getDocument() {
    let iframe;
    if (this.tribute.current.collection) {
      iframe = this.tribute.current.collection.iframe;
    }
    if (!iframe) {
      return document;
    }
    return iframe.contentWindow.document;
  }
  positionMenuAtCaret(scrollTo) {
    let context = this.tribute.current, coordinates;
    let info = this.getTriggerInfo(false, this.tribute.hasTrailingSpace, true, this.tribute.allowSpaces, this.tribute.autocompleteMode);
    if (typeof info !== "undefined") {
      if (!this.tribute.positionMenu) {
        this.tribute.menu.style.cssText = `display: block;`;
        return;
      }
      if (!this.isContentEditable(context.element)) {
        coordinates = this.getTextAreaOrInputUnderlinePosition(
          this.tribute.current.element,
          info.mentionPosition
        );
      } else {
        coordinates = this.getContentEditableCaretPosition(info.mentionPosition);
      }
      this.tribute.menu.style.cssText = `top: ${coordinates.top}px;
                                     left: ${coordinates.left}px;
                                     right: ${coordinates.right}px;
                                     bottom: ${coordinates.bottom}px;
                                     position: absolute;
                                     display: block;`;
      if (coordinates.left === "auto") {
        this.tribute.menu.style.left = "auto";
      }
      if (coordinates.top === "auto") {
        this.tribute.menu.style.top = "auto";
      }
      if (scrollTo) this.scrollIntoView();
      window.setTimeout(() => {
        let menuDimensions = {
          width: this.tribute.menu.offsetWidth,
          height: this.tribute.menu.offsetHeight
        };
        let menuIsOffScreen = this.isMenuOffScreen(coordinates, menuDimensions);
        let menuIsOffScreenHorizontally = window.innerWidth > menuDimensions.width && (menuIsOffScreen.left || menuIsOffScreen.right);
        let menuIsOffScreenVertically = window.innerHeight > menuDimensions.height && (menuIsOffScreen.top || menuIsOffScreen.bottom);
        if (menuIsOffScreenHorizontally || menuIsOffScreenVertically) {
          this.tribute.menu.style.cssText = "display: none";
          this.positionMenuAtCaret(scrollTo);
        }
      }, 0);
    } else {
      this.tribute.menu.style.cssText = "display: none";
    }
  }
  get menuContainerIsBody() {
    return this.tribute.menuContainer === document.body || !this.tribute.menuContainer;
  }
  selectElement(targetElement, path, offset) {
    let range;
    let elem = targetElement;
    if (path) {
      for (var i = 0; i < path.length; i++) {
        elem = elem.childNodes[path[i]];
        if (elem === void 0) {
          return;
        }
        while (elem.length < offset) {
          offset -= elem.length;
          elem = elem.nextSibling;
        }
        if (elem.childNodes.length === 0 && !elem.length) {
          elem = elem.previousSibling;
        }
      }
    }
    let sel = this.getWindowSelection();
    range = this.getDocument().createRange();
    range.setStart(elem, offset);
    range.setEnd(elem, offset);
    range.collapse(true);
    try {
      sel.removeAllRanges();
    } catch (error) {
    }
    sel.addRange(range);
    targetElement.focus();
  }
  replaceTriggerText(text, requireLeadingSpace, hasTrailingSpace, originalEvent, item) {
    let info = this.getTriggerInfo(true, hasTrailingSpace, requireLeadingSpace, this.tribute.allowSpaces, this.tribute.autocompleteMode);
    if (info !== void 0) {
      let context = this.tribute.current;
      let replaceEvent = new CustomEvent("tribute-replaced", {
        detail: {
          item,
          instance: context,
          context: info,
          event: originalEvent
        }
      });
      if (!this.isContentEditable(context.element)) {
        let myField = this.tribute.current.element;
        let textSuffix = typeof this.tribute.replaceTextSuffix == "string" ? this.tribute.replaceTextSuffix : " ";
        text += textSuffix;
        let startPos = info.mentionPosition;
        let endPos = info.mentionPosition + info.mentionText.length + textSuffix.length;
        if (!this.tribute.autocompleteMode) {
          endPos += info.mentionTriggerChar.length - 1;
        }
        myField.value = myField.value.substring(0, startPos) + text + myField.value.substring(endPos, myField.value.length);
        myField.selectionStart = startPos + text.length;
        myField.selectionEnd = startPos + text.length;
      } else {
        let textSuffix = typeof this.tribute.replaceTextSuffix == "string" ? this.tribute.replaceTextSuffix : " ";
        text += textSuffix;
        let endPos = info.mentionPosition + info.mentionText.length;
        if (!this.tribute.autocompleteMode) {
          endPos += info.mentionTriggerChar.length;
        }
        this.pasteHtml(text, info.mentionPosition, endPos);
      }
      context.element.dispatchEvent(new CustomEvent("input", { bubbles: true }));
      context.element.dispatchEvent(replaceEvent);
    }
  }
  pasteHtml(html, startPos, endPos) {
    let range, sel;
    sel = this.getWindowSelection();
    range = this.getDocument().createRange();
    range.setStart(sel.anchorNode, startPos);
    range.setEnd(sel.anchorNode, endPos);
    range.deleteContents();
    let el = this.getDocument().createElement("div");
    el.innerHTML = html;
    let frag = this.getDocument().createDocumentFragment(), node, lastNode;
    while (node = el.firstChild) {
      lastNode = frag.appendChild(node);
    }
    range.insertNode(frag);
    if (lastNode) {
      range = range.cloneRange();
      range.setStartAfter(lastNode);
      range.collapse(true);
      sel.removeAllRanges();
      sel.addRange(range);
    }
  }
  getWindowSelection() {
    if (this.tribute.collection.iframe) {
      return this.tribute.collection.iframe.contentWindow.getSelection();
    }
    return window.getSelection();
  }
  getNodePositionInParent(element) {
    if (element.parentNode === null) {
      return 0;
    }
    for (var i = 0; i < element.parentNode.childNodes.length; i++) {
      let node = element.parentNode.childNodes[i];
      if (node === element) {
        return i;
      }
    }
  }
  getContentEditableSelectedPath(ctx) {
    let sel = this.getWindowSelection();
    let selected = sel.anchorNode;
    let path = [];
    let offset;
    if (selected != null) {
      let i;
      let ce = selected.contentEditable;
      while (selected !== null && ce !== "true") {
        i = this.getNodePositionInParent(selected);
        path.push(i);
        selected = selected.parentNode;
        if (selected !== null) {
          ce = selected.contentEditable;
        }
      }
      path.reverse();
      offset = sel.getRangeAt(0).startOffset;
      return {
        selected,
        path,
        offset
      };
    }
  }
  getTextPrecedingCurrentSelection() {
    let context = this.tribute.current, text = "";
    if (!this.isContentEditable(context.element)) {
      let textComponent = this.tribute.current.element;
      if (textComponent) {
        let startPos = textComponent.selectionStart;
        if (textComponent.value && startPos >= 0) {
          text = textComponent.value.substring(0, startPos);
        }
      }
    } else {
      let selectedElem = this.getWindowSelection().anchorNode;
      if (selectedElem != null) {
        let workingNodeContent = selectedElem.textContent;
        let selectStartOffset = this.getWindowSelection().getRangeAt(0).startOffset;
        if (workingNodeContent && selectStartOffset >= 0) {
          text = workingNodeContent.substring(0, selectStartOffset);
        }
      }
    }
    return text;
  }
  getLastWordInText(text) {
    text = text.replace(/\u00A0/g, " ");
    let wordsArray = text.split(/\s+/);
    let worldsCount = wordsArray.length - 1;
    return wordsArray[worldsCount].trim();
  }
  getTriggerInfo(menuAlreadyActive, hasTrailingSpace, requireLeadingSpace, allowSpaces, isAutocomplete) {
    let ctx = this.tribute.current;
    let selected, path, offset;
    if (!this.isContentEditable(ctx.element)) {
      selected = this.tribute.current.element;
    } else {
      let selectionInfo = this.getContentEditableSelectedPath(ctx);
      if (selectionInfo) {
        selected = selectionInfo.selected;
        path = selectionInfo.path;
        offset = selectionInfo.offset;
      }
    }
    let effectiveRange = this.getTextPrecedingCurrentSelection();
    let lastWordOfEffectiveRange = this.getLastWordInText(effectiveRange);
    if (isAutocomplete) {
      return {
        mentionPosition: effectiveRange.length - lastWordOfEffectiveRange.length,
        mentionText: lastWordOfEffectiveRange,
        mentionSelectedElement: selected,
        mentionSelectedPath: path,
        mentionSelectedOffset: offset
      };
    }
    if (effectiveRange !== void 0 && effectiveRange !== null) {
      let mostRecentTriggerCharPos = -1;
      let triggerChar;
      this.tribute.collection.forEach((config) => {
        let c = config.trigger;
        let idx = config.requireLeadingSpace ? this.lastIndexWithLeadingSpace(effectiveRange, c) : effectiveRange.lastIndexOf(c);
        if (idx > mostRecentTriggerCharPos) {
          mostRecentTriggerCharPos = idx;
          triggerChar = c;
          requireLeadingSpace = config.requireLeadingSpace;
        }
      });
      if (mostRecentTriggerCharPos >= 0 && (mostRecentTriggerCharPos === 0 || !requireLeadingSpace || /[\xA0\s]/g.test(
        effectiveRange.substring(
          mostRecentTriggerCharPos - 1,
          mostRecentTriggerCharPos
        )
      ))) {
        let currentTriggerSnippet = effectiveRange.substring(
          mostRecentTriggerCharPos + triggerChar.length,
          effectiveRange.length
        );
        triggerChar = effectiveRange.substring(mostRecentTriggerCharPos, mostRecentTriggerCharPos + triggerChar.length);
        let firstSnippetChar = currentTriggerSnippet.substring(0, 1);
        let leadingSpace = currentTriggerSnippet.length > 0 && (firstSnippetChar === " " || firstSnippetChar === " ");
        if (hasTrailingSpace) {
          currentTriggerSnippet = currentTriggerSnippet.trim();
        }
        let regex = allowSpaces ? /[^\S ]/g : /[\xA0\s]/g;
        this.tribute.hasTrailingSpace = regex.test(currentTriggerSnippet);
        if (!leadingSpace && (menuAlreadyActive || !regex.test(currentTriggerSnippet))) {
          return {
            mentionPosition: mostRecentTriggerCharPos,
            mentionText: currentTriggerSnippet,
            mentionSelectedElement: selected,
            mentionSelectedPath: path,
            mentionSelectedOffset: offset,
            mentionTriggerChar: triggerChar
          };
        }
      }
    }
  }
  lastIndexWithLeadingSpace(str, trigger) {
    let reversedStr = str.split("").reverse().join("");
    let index = -1;
    for (let cidx = 0, len = str.length; cidx < len; cidx++) {
      let firstChar = cidx === str.length - 1;
      let leadingSpace = /\s/.test(reversedStr[cidx + 1]);
      let match = true;
      for (let triggerIdx = trigger.length - 1; triggerIdx >= 0; triggerIdx--) {
        if (trigger[triggerIdx] !== reversedStr[cidx - triggerIdx]) {
          match = false;
          break;
        }
      }
      if (match && (firstChar || leadingSpace)) {
        index = str.length - 1 - cidx;
        break;
      }
    }
    return index;
  }
  isContentEditable(element) {
    return element.nodeName !== "INPUT" && element.nodeName !== "TEXTAREA";
  }
  isMenuOffScreen(coordinates, menuDimensions) {
    let windowWidth = window.innerWidth;
    let windowHeight = window.innerHeight;
    let doc = document.documentElement;
    let windowLeft = (window.pageXOffset || doc.scrollLeft) - (doc.clientLeft || 0);
    let windowTop = (window.pageYOffset || doc.scrollTop) - (doc.clientTop || 0);
    let menuTop = typeof coordinates.top === "number" ? coordinates.top : windowTop + windowHeight - coordinates.bottom - menuDimensions.height;
    let menuRight = typeof coordinates.right === "number" ? coordinates.right : coordinates.left + menuDimensions.width;
    let menuBottom = typeof coordinates.bottom === "number" ? coordinates.bottom : coordinates.top + menuDimensions.height;
    let menuLeft = typeof coordinates.left === "number" ? coordinates.left : windowLeft + windowWidth - coordinates.right - menuDimensions.width;
    return {
      top: menuTop < Math.floor(windowTop),
      right: menuRight > Math.ceil(windowLeft + windowWidth),
      bottom: menuBottom > Math.ceil(windowTop + windowHeight),
      left: menuLeft < Math.floor(windowLeft)
    };
  }
  getMenuDimensions() {
    let dimensions = {
      width: null,
      height: null
    };
    this.tribute.menu.style.cssText = `top: 0px;
                                 left: 0px;
                                 position: fixed;
                                 display: block;
                                 visibility; hidden;`;
    dimensions.width = this.tribute.menu.offsetWidth;
    dimensions.height = this.tribute.menu.offsetHeight;
    this.tribute.menu.style.cssText = `display: none;`;
    return dimensions;
  }
  getTextAreaOrInputUnderlinePosition(element, position, flipped) {
    let properties = [
      "direction",
      "boxSizing",
      "width",
      "height",
      "overflowX",
      "overflowY",
      "borderTopWidth",
      "borderRightWidth",
      "borderBottomWidth",
      "borderLeftWidth",
      "paddingTop",
      "paddingRight",
      "paddingBottom",
      "paddingLeft",
      "fontStyle",
      "fontVariant",
      "fontWeight",
      "fontStretch",
      "fontSize",
      "fontSizeAdjust",
      "lineHeight",
      "fontFamily",
      "textAlign",
      "textTransform",
      "textIndent",
      "textDecoration",
      "letterSpacing",
      "wordSpacing"
    ];
    let isFirefox = window.mozInnerScreenX !== null;
    let div = this.getDocument().createElement("div");
    div.id = "input-textarea-caret-position-mirror-div";
    this.getDocument().body.appendChild(div);
    let style = div.style;
    let computed = window.getComputedStyle ? getComputedStyle(element) : element.currentStyle;
    style.whiteSpace = "pre-wrap";
    if (element.nodeName !== "INPUT") {
      style.wordWrap = "break-word";
    }
    style.position = "absolute";
    style.visibility = "hidden";
    properties.forEach((prop) => {
      style[prop] = computed[prop];
    });
    if (isFirefox) {
      style.width = `${parseInt(computed.width) - 2}px`;
      if (element.scrollHeight > parseInt(computed.height))
        style.overflowY = "scroll";
    } else {
      style.overflow = "hidden";
    }
    div.textContent = element.value.substring(0, position);
    if (element.nodeName === "INPUT") {
      div.textContent = div.textContent.replace(/\s/g, " ");
    }
    let span = this.getDocument().createElement("span");
    span.textContent = element.value.substring(position) || ".";
    div.appendChild(span);
    let rect = element.getBoundingClientRect();
    let doc = document.documentElement;
    let windowLeft = (window.pageXOffset || doc.scrollLeft) - (doc.clientLeft || 0);
    let windowTop = (window.pageYOffset || doc.scrollTop) - (doc.clientTop || 0);
    let top = 0;
    let left = 0;
    if (this.menuContainerIsBody) {
      top = rect.top;
      left = rect.left;
    }
    let coordinates = {
      top: top + windowTop + span.offsetTop + parseInt(computed.borderTopWidth) + parseInt(computed.fontSize) - element.scrollTop,
      left: left + windowLeft + span.offsetLeft + parseInt(computed.borderLeftWidth)
    };
    let windowWidth = window.innerWidth;
    let windowHeight = window.innerHeight;
    let menuDimensions = this.getMenuDimensions();
    let menuIsOffScreen = this.isMenuOffScreen(coordinates, menuDimensions);
    if (menuIsOffScreen.right) {
      coordinates.right = windowWidth - coordinates.left;
      coordinates.left = "auto";
    }
    let parentHeight = this.tribute.menuContainer ? this.tribute.menuContainer.offsetHeight : this.getDocument().body.offsetHeight;
    if (menuIsOffScreen.bottom) {
      let parentRect = this.tribute.menuContainer ? this.tribute.menuContainer.getBoundingClientRect() : this.getDocument().body.getBoundingClientRect();
      let scrollStillAvailable = parentHeight - (windowHeight - parentRect.top);
      coordinates.bottom = scrollStillAvailable + (windowHeight - rect.top - span.offsetTop);
      coordinates.top = "auto";
    }
    menuIsOffScreen = this.isMenuOffScreen(coordinates, menuDimensions);
    if (menuIsOffScreen.left) {
      coordinates.left = windowWidth > menuDimensions.width ? windowLeft + windowWidth - menuDimensions.width : windowLeft;
      delete coordinates.right;
    }
    if (menuIsOffScreen.top) {
      coordinates.top = windowHeight > menuDimensions.height ? windowTop + windowHeight - menuDimensions.height : windowTop;
      delete coordinates.bottom;
    }
    this.getDocument().body.removeChild(div);
    return coordinates;
  }
  getContentEditableCaretPosition(selectedNodePosition) {
    let range;
    let sel = this.getWindowSelection();
    range = this.getDocument().createRange();
    range.setStart(sel.anchorNode, selectedNodePosition);
    range.setEnd(sel.anchorNode, selectedNodePosition);
    range.collapse(false);
    let rect = range.getBoundingClientRect();
    let doc = document.documentElement;
    let windowLeft = (window.pageXOffset || doc.scrollLeft) - (doc.clientLeft || 0);
    let windowTop = (window.pageYOffset || doc.scrollTop) - (doc.clientTop || 0);
    let left = rect.left;
    let top = rect.top;
    let coordinates = {
      left: left + windowLeft,
      top: top + rect.height + windowTop
    };
    let windowWidth = window.innerWidth;
    let windowHeight = window.innerHeight;
    let menuDimensions = this.getMenuDimensions();
    let menuIsOffScreen = this.isMenuOffScreen(coordinates, menuDimensions);
    if (menuIsOffScreen.right) {
      coordinates.left = "auto";
      coordinates.right = windowWidth - rect.left - windowLeft;
    }
    let parentHeight = this.tribute.menuContainer ? this.tribute.menuContainer.offsetHeight : this.getDocument().body.offsetHeight;
    if (menuIsOffScreen.bottom) {
      let parentRect = this.tribute.menuContainer ? this.tribute.menuContainer.getBoundingClientRect() : this.getDocument().body.getBoundingClientRect();
      let scrollStillAvailable = parentHeight - (windowHeight - parentRect.top);
      coordinates.top = "auto";
      coordinates.bottom = scrollStillAvailable + (windowHeight - rect.top);
    }
    menuIsOffScreen = this.isMenuOffScreen(coordinates, menuDimensions);
    if (menuIsOffScreen.left) {
      coordinates.left = windowWidth > menuDimensions.width ? windowLeft + windowWidth - menuDimensions.width : windowLeft;
      delete coordinates.right;
    }
    if (menuIsOffScreen.top) {
      coordinates.top = windowHeight > menuDimensions.height ? windowTop + windowHeight - menuDimensions.height : windowTop;
      delete coordinates.bottom;
    }
    if (!this.menuContainerIsBody) {
      coordinates.left = coordinates.left ? coordinates.left - this.tribute.menuContainer.offsetLeft : coordinates.left;
      coordinates.top = coordinates.top ? coordinates.top - this.tribute.menuContainer.offsetTop : coordinates.top;
    }
    return coordinates;
  }
  scrollIntoView(elem) {
    let reasonableBuffer = 20, clientRect;
    let maxScrollDisplacement = 100;
    let e = this.menu;
    if (typeof e === "undefined") return;
    while (clientRect === void 0 || clientRect.height === 0) {
      clientRect = e.getBoundingClientRect();
      if (clientRect.height === 0) {
        e = e.childNodes[0];
        if (e === void 0 || !e.getBoundingClientRect) {
          return;
        }
      }
    }
    let elemTop = clientRect.top;
    let elemBottom = elemTop + clientRect.height;
    if (elemTop < 0) {
      window.scrollTo(0, window.pageYOffset + clientRect.top - reasonableBuffer);
    } else if (elemBottom > window.innerHeight) {
      let maxY = window.pageYOffset + clientRect.top - reasonableBuffer;
      if (maxY - window.pageYOffset > maxScrollDisplacement) {
        maxY = window.pageYOffset + maxScrollDisplacement;
      }
      let targetY = window.pageYOffset - (window.innerHeight - elemBottom);
      if (targetY > maxY) {
        targetY = maxY;
      }
      window.scrollTo(0, targetY);
    }
  }
}
class TributeSearch {
  constructor(tribute) {
    this.tribute = tribute;
    this.tribute.search = this;
  }
  simpleFilter(pattern, array) {
    return array.filter((string) => {
      return this.test(pattern, string);
    });
  }
  test(pattern, string) {
    return this.match(pattern, string) !== null;
  }
  match(pattern, string, opts) {
    opts = opts || {};
    string.length;
    let pre = opts.pre || "", post = opts.post || "", compareString = opts.caseSensitive && string || string.toLowerCase();
    if (opts.skip) {
      return { rendered: string, score: 0 };
    }
    pattern = opts.caseSensitive && pattern || pattern.toLowerCase();
    let patternCache = this.traverse(compareString, pattern, 0, 0, []);
    if (!patternCache) {
      return null;
    }
    return {
      rendered: this.render(string, patternCache.cache, pre, post),
      score: patternCache.score
    };
  }
  traverse(string, pattern, stringIndex, patternIndex, patternCache) {
    if (pattern.length === patternIndex) {
      return {
        score: this.calculateScore(patternCache),
        cache: patternCache.slice()
      };
    }
    if (string.length === stringIndex || pattern.length - patternIndex > string.length - stringIndex) {
      return void 0;
    }
    let c = pattern[patternIndex];
    let index = string.indexOf(c, stringIndex);
    let best, temp;
    while (index > -1) {
      patternCache.push(index);
      temp = this.traverse(string, pattern, index + 1, patternIndex + 1, patternCache);
      patternCache.pop();
      if (!temp) {
        return best;
      }
      if (!best || best.score < temp.score) {
        best = temp;
      }
      index = string.indexOf(c, index + 1);
    }
    return best;
  }
  calculateScore(patternCache) {
    let score = 0;
    let temp = 1;
    patternCache.forEach((index, i) => {
      if (i > 0) {
        if (patternCache[i - 1] + 1 === index) {
          temp += temp + 1;
        } else {
          temp = 1;
        }
      }
      score += temp;
    });
    return score;
  }
  render(string, indices, pre, post) {
    var rendered = string.substring(0, indices[0]);
    indices.forEach((index, i) => {
      rendered += pre + string[index] + post + string.substring(index + 1, indices[i + 1] ? indices[i + 1] : string.length);
    });
    return rendered;
  }
  filter(pattern, arr, opts) {
    opts = opts || {};
    return arr.reduce((prev, element, idx, arr2) => {
      let str = element;
      if (opts.extract) {
        str = opts.extract(element);
        if (!str) {
          str = "";
        }
      }
      let rendered = this.match(pattern, str, opts);
      if (rendered != null) {
        prev[prev.length] = {
          string: rendered.rendered,
          score: rendered.score,
          index: idx,
          original: element
        };
      }
      return prev;
    }, []).sort((a, b) => {
      let compare = b.score - a.score;
      if (compare) return compare;
      return a.index - b.index;
    });
  }
}
class Tribute {
  constructor({
    values = null,
    iframe = null,
    selectClass = "highlight",
    containerClass = "tribute-container",
    itemClass = "",
    trigger = "@",
    autocompleteMode = false,
    selectTemplate = null,
    menuItemTemplate = null,
    lookup = "key",
    fillAttr = "value",
    collection = null,
    menuContainer = null,
    noMatchTemplate = null,
    requireLeadingSpace = true,
    allowSpaces = false,
    replaceTextSuffix = null,
    positionMenu = true,
    spaceSelectsMatch = false,
    searchOpts = {},
    menuItemLimit = null,
    menuShowMinLength = 0
  }) {
    this.autocompleteMode = autocompleteMode;
    this.menuSelected = 0;
    this.current = {};
    this.inputEvent = false;
    this.isActive = false;
    this.menuContainer = menuContainer;
    this.allowSpaces = allowSpaces;
    this.replaceTextSuffix = replaceTextSuffix;
    this.positionMenu = positionMenu;
    this.hasTrailingSpace = false;
    this.spaceSelectsMatch = spaceSelectsMatch;
    if (this.autocompleteMode) {
      trigger = "";
      allowSpaces = false;
    }
    if (values) {
      this.collection = [
        {
          // symbol that starts the lookup
          trigger,
          // is it wrapped in an iframe
          iframe,
          // class applied to selected item
          selectClass,
          // class applied to the Container
          containerClass,
          // class applied to each item
          itemClass,
          // function called on select that retuns the content to insert
          selectTemplate: (selectTemplate || Tribute.defaultSelectTemplate).bind(this),
          // function called that returns content for an item
          menuItemTemplate: (menuItemTemplate || Tribute.defaultMenuItemTemplate).bind(this),
          // function called when menu is empty, disables hiding of menu.
          noMatchTemplate: ((t2) => {
            if (typeof t2 === "string") {
              if (t2.trim() === "") return null;
              return t2;
            }
            if (typeof t2 === "function") {
              return t2.bind(this);
            }
            return noMatchTemplate || function() {
              return "<li>No Match Found!</li>";
            }.bind(this);
          })(noMatchTemplate),
          // column to search against in the object
          lookup,
          // column that contains the content to insert by default
          fillAttr,
          // array of objects or a function returning an array of objects
          values,
          requireLeadingSpace,
          searchOpts,
          menuItemLimit,
          menuShowMinLength
        }
      ];
    } else if (collection) {
      if (this.autocompleteMode)
        console.warn(
          "Tribute in autocomplete mode does not work for collections"
        );
      this.collection = collection.map((item) => {
        return {
          trigger: item.trigger || trigger,
          iframe: item.iframe || iframe,
          selectClass: item.selectClass || selectClass,
          containerClass: item.containerClass || containerClass,
          itemClass: item.itemClass || itemClass,
          selectTemplate: (item.selectTemplate || Tribute.defaultSelectTemplate).bind(this),
          menuItemTemplate: (item.menuItemTemplate || Tribute.defaultMenuItemTemplate).bind(this),
          // function called when menu is empty, disables hiding of menu.
          noMatchTemplate: ((t2) => {
            if (typeof t2 === "string") {
              if (t2.trim() === "") return null;
              return t2;
            }
            if (typeof t2 === "function") {
              return t2.bind(this);
            }
            return noMatchTemplate || function() {
              return "<li>No Match Found!</li>";
            }.bind(this);
          })(noMatchTemplate),
          lookup: item.lookup || lookup,
          fillAttr: item.fillAttr || fillAttr,
          values: item.values,
          requireLeadingSpace: item.requireLeadingSpace,
          searchOpts: item.searchOpts || searchOpts,
          menuItemLimit: item.menuItemLimit || menuItemLimit,
          menuShowMinLength: item.menuShowMinLength || menuShowMinLength
        };
      });
    } else {
      throw new Error("[Tribute] No collection specified.");
    }
    new TributeRange(this);
    new TributeEvents(this);
    new TributeMenuEvents(this);
    new TributeSearch(this);
  }
  get isActive() {
    return this._isActive;
  }
  set isActive(val) {
    if (this._isActive != val) {
      this._isActive = val;
      if (this.current.element) {
        let noMatchEvent = new CustomEvent(`tribute-active-${val}`);
        this.current.element.dispatchEvent(noMatchEvent);
      }
    }
  }
  static defaultSelectTemplate(item) {
    if (typeof item === "undefined")
      return `${this.current.collection.trigger}${this.current.mentionText}`;
    if (this.range.isContentEditable(this.current.element)) {
      return '<span class="tribute-mention">' + (this.current.collection.trigger + item.original[this.current.collection.fillAttr]) + "</span>";
    }
    return this.current.collection.trigger + item.original[this.current.collection.fillAttr];
  }
  static defaultMenuItemTemplate(matchItem) {
    return matchItem.string;
  }
  static inputTypes() {
    return ["TEXTAREA", "INPUT"];
  }
  triggers() {
    return this.collection.map((config) => {
      return config.trigger;
    });
  }
  attach(el) {
    if (!el) {
      throw new Error("[Tribute] Must pass in a DOM node or NodeList.");
    }
    if (typeof jQuery !== "undefined" && el instanceof jQuery) {
      el = el.get();
    }
    if (el.constructor === NodeList || el.constructor === HTMLCollection || el.constructor === Array) {
      let length = el.length;
      for (var i = 0; i < length; ++i) {
        this._attach(el[i]);
      }
    } else {
      this._attach(el);
    }
  }
  _attach(el) {
    if (el.hasAttribute("data-tribute")) {
      console.warn("Tribute was already bound to " + el.nodeName);
    }
    this.ensureEditable(el);
    this.events.bind(el);
    el.setAttribute("data-tribute", true);
  }
  ensureEditable(element) {
    if (Tribute.inputTypes().indexOf(element.nodeName) === -1) {
      if (element.contentEditable) {
        element.contentEditable = true;
      } else {
        throw new Error("[Tribute] Cannot bind to " + element.nodeName);
      }
    }
  }
  createMenu(containerClass) {
    let wrapper = this.range.getDocument().createElement("div"), ul = this.range.getDocument().createElement("ul");
    wrapper.className = containerClass;
    wrapper.appendChild(ul);
    if (this.menuContainer) {
      return this.menuContainer.appendChild(wrapper);
    }
    return this.range.getDocument().body.appendChild(wrapper);
  }
  showMenuFor(element, scrollTo) {
    if (this.isActive && this.current.element === element && this.current.mentionText === this.currentMentionTextSnapshot) {
      return;
    }
    this.currentMentionTextSnapshot = this.current.mentionText;
    if (!this.menu) {
      this.menu = this.createMenu(this.current.collection.containerClass);
      element.tributeMenu = this.menu;
      this.menuEvents.bind(this.menu);
    }
    this.isActive = true;
    this.menuSelected = 0;
    if (!this.current.mentionText) {
      this.current.mentionText = "";
    }
    const processValues = (values) => {
      if (!this.isActive) {
        return;
      }
      let items = this.search.filter(this.current.mentionText, values, {
        pre: this.current.collection.searchOpts.pre || "<span>",
        post: this.current.collection.searchOpts.post || "</span>",
        skip: this.current.collection.searchOpts.skip,
        extract: (el) => {
          if (typeof this.current.collection.lookup === "string") {
            return el[this.current.collection.lookup];
          } else if (typeof this.current.collection.lookup === "function") {
            return this.current.collection.lookup(el, this.current.mentionText);
          } else {
            throw new Error(
              "Invalid lookup attribute, lookup must be string or function."
            );
          }
        }
      });
      if (this.current.collection.menuItemLimit) {
        items = items.slice(0, this.current.collection.menuItemLimit);
      }
      this.current.filteredItems = items;
      let ul = this.menu.querySelector("ul");
      this.range.positionMenuAtCaret(scrollTo);
      if (!items.length) {
        let noMatchEvent = new CustomEvent("tribute-no-match", {
          detail: this.menu
        });
        this.current.element.dispatchEvent(noMatchEvent);
        if (typeof this.current.collection.noMatchTemplate === "function" && !this.current.collection.noMatchTemplate() || !this.current.collection.noMatchTemplate) {
          this.hideMenu();
        } else {
          typeof this.current.collection.noMatchTemplate === "function" ? ul.innerHTML = this.current.collection.noMatchTemplate() : ul.innerHTML = this.current.collection.noMatchTemplate;
        }
        return;
      }
      ul.innerHTML = "";
      let fragment = this.range.getDocument().createDocumentFragment();
      items.forEach((item, index) => {
        let li = this.range.getDocument().createElement("li");
        li.setAttribute("data-index", index);
        li.className = this.current.collection.itemClass;
        li.addEventListener("mousemove", (e) => {
          let [li2, index2] = this._findLiTarget(e.target);
          if (e.movementY !== 0) {
            this.events.setActiveLi(index2);
          }
        });
        if (this.menuSelected === index) {
          li.classList.add(this.current.collection.selectClass);
        }
        li.innerHTML = this.current.collection.menuItemTemplate(item);
        fragment.appendChild(li);
      });
      ul.appendChild(fragment);
    };
    if (typeof this.current.collection.values === "function") {
      this.current.collection.values(this.current.mentionText, processValues);
    } else {
      processValues(this.current.collection.values);
    }
  }
  _findLiTarget(el) {
    if (!el) return [];
    const index = el.getAttribute("data-index");
    return !index ? this._findLiTarget(el.parentNode) : [el, index];
  }
  showMenuForCollection(element, collectionIndex) {
    if (element !== document.activeElement) {
      this.placeCaretAtEnd(element);
    }
    this.current.collection = this.collection[collectionIndex || 0];
    this.current.externalTrigger = true;
    this.current.element = element;
    if (element.isContentEditable)
      this.insertTextAtCursor(this.current.collection.trigger);
    else this.insertAtCaret(element, this.current.collection.trigger);
    this.showMenuFor(element);
  }
  // TODO: make sure this works for inputs/textareas
  placeCaretAtEnd(el) {
    el.focus();
    if (typeof window.getSelection != "undefined" && typeof document.createRange != "undefined") {
      var range = document.createRange();
      range.selectNodeContents(el);
      range.collapse(false);
      var sel = window.getSelection();
      sel.removeAllRanges();
      sel.addRange(range);
    } else if (typeof document.body.createTextRange != "undefined") {
      var textRange = document.body.createTextRange();
      textRange.moveToElementText(el);
      textRange.collapse(false);
      textRange.select();
    }
  }
  // for contenteditable
  insertTextAtCursor(text) {
    var sel, range;
    sel = window.getSelection();
    range = sel.getRangeAt(0);
    range.deleteContents();
    var textNode = document.createTextNode(text);
    range.insertNode(textNode);
    range.selectNodeContents(textNode);
    range.collapse(false);
    sel.removeAllRanges();
    sel.addRange(range);
  }
  // for regular inputs
  insertAtCaret(textarea, text) {
    var scrollPos = textarea.scrollTop;
    var caretPos = textarea.selectionStart;
    var front = textarea.value.substring(0, caretPos);
    var back = textarea.value.substring(
      textarea.selectionEnd,
      textarea.value.length
    );
    textarea.value = front + text + back;
    caretPos = caretPos + text.length;
    textarea.selectionStart = caretPos;
    textarea.selectionEnd = caretPos;
    textarea.focus();
    textarea.scrollTop = scrollPos;
  }
  hideMenu() {
    if (this.menu) {
      this.menu.style.cssText = "display: none;";
      this.isActive = false;
      this.menuSelected = 0;
      this.current = {};
    }
  }
  selectItemAtIndex(index, originalEvent) {
    index = parseInt(index);
    if (typeof index !== "number" || isNaN(index)) return;
    let item = this.current.filteredItems[index];
    let content = this.current.collection.selectTemplate(item);
    if (content !== null) this.replaceText(content, originalEvent, item);
  }
  replaceText(content, originalEvent, item) {
    this.range.replaceTriggerText(content, true, true, originalEvent, item);
  }
  _append(collection, newValues, replace) {
    if (typeof collection.values === "function") {
      throw new Error("Unable to append to values, as it is a function.");
    } else if (!replace) {
      collection.values = collection.values.concat(newValues);
    } else {
      collection.values = newValues;
    }
  }
  append(collectionIndex, newValues, replace) {
    let index = parseInt(collectionIndex);
    if (typeof index !== "number")
      throw new Error("please provide an index for the collection to update.");
    let collection = this.collection[index];
    this._append(collection, newValues, replace);
  }
  appendCurrent(newValues, replace) {
    if (this.isActive) {
      this._append(this.current.collection, newValues, replace);
    } else {
      throw new Error(
        "No active state. Please use append instead and pass an index."
      );
    }
  }
  detach(el) {
    if (!el) {
      throw new Error("[Tribute] Must pass in a DOM node or NodeList.");
    }
    if (typeof jQuery !== "undefined" && el instanceof jQuery) {
      el = el.get();
    }
    if (el.constructor === NodeList || el.constructor === HTMLCollection || el.constructor === Array) {
      let length = el.length;
      for (var i = 0; i < length; ++i) {
        this._detach(el[i]);
      }
    } else {
      this._detach(el);
    }
  }
  _detach(el) {
    this.events.unbind(el);
    if (el.tributeMenu) {
      this.menuEvents.unbind(el.tributeMenu);
    }
    setTimeout(() => {
      el.removeAttribute("data-tribute");
      this.isActive = false;
      if (el.tributeMenu) {
        el.tributeMenu.remove();
      }
    });
  }
}
const _sfc_main$2 = {
  name: "NcMentionBubble",
  /* eslint vue/require-prop-comment: warn -- TODO: Add a proper doc block about what this props do */
  props: {
    /**
     * Id of the bubble
     */
    id: {
      type: String,
      required: true
    },
    /**
     * The main text
     */
    label: {
      type: String,
      required: false,
      default: null
    },
    /**
     * Icon to be applied
     */
    icon: {
      type: String,
      required: true
    },
    /**
     * URL of the icon
     */
    iconUrl: {
      type: [String, null],
      default: null
    },
    source: {
      type: String,
      required: true
    },
    /**
     * Is the bubble shown as primary
     */
    primary: {
      type: Boolean,
      default: false
    }
  },
  setup() {
    const isDarkTheme = useIsDarkTheme();
    return {
      isDarkTheme
    };
  },
  computed: {
    avatarUrl() {
      if (this.iconUrl) {
        return this.iconUrl;
      }
      return this.id && this.source === "users" ? getAvatarUrl(this.id, { isDarkTheme: this.isDarkTheme }) : null;
    },
    mentionText() {
      return !this.id.includes(" ") && !this.id.includes("/") ? `@${this.id}` : `@"${this.id}"`;
    }
  }
};
const _hoisted_1$2 = { class: "mention-bubble__wrapper" };
const _hoisted_2$2 = { class: "mention-bubble__content" };
const _hoisted_3$1 = ["title"];
const _hoisted_4$1 = {
  role: "none",
  class: "mention-bubble__select"
};
function _sfc_render$2(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("span", {
    class: normalizeClass(["mention-bubble", { "mention-bubble--primary": $props.primary }]),
    contenteditable: "false"
  }, [
    createBaseVNode("span", _hoisted_1$2, [
      createBaseVNode("span", _hoisted_2$2, [
        createBaseVNode("span", {
          class: normalizeClass([[$props.icon, `mention-bubble__icon--${$options.avatarUrl ? "with-avatar" : ""}`], "mention-bubble__icon"]),
          style: normalizeStyle($options.avatarUrl ? { backgroundImage: `url(${$options.avatarUrl})` } : null)
        }, null, 6),
        createBaseVNode("span", {
          role: "heading",
          class: "mention-bubble__title",
          title: $props.label
        }, null, 8, _hoisted_3$1)
      ]),
      createBaseVNode("span", _hoisted_4$1, toDisplayString($options.mentionText), 1)
    ])
  ], 2);
}
const NcMentionBubble = /* @__PURE__ */ _export_sfc(_sfc_main$2, [["render", _sfc_render$2], ["__scopeId", "data-v-45238efd"]]);
const MENTION_START = /(?=[a-z0-9_\-@.'])\B/.source;
const MENTION_SIMPLE = /(@[a-z0-9_\-@.']+)/.source;
const MENTION_GUEST = /@&quot;(?:guest|email){1}\/[a-f0-9]+&quot;/.source;
const MENTION_PREFIXED = /@&quot;(?:federated_)?(?:group|team|user){1}\/[a-z0-9_\-@.' /:]+&quot;/.source;
const MENTION_WITH_SPACE = /@&quot;[a-z0-9_\-@.' ]+&quot;/.source;
const MENTION_COMPLEX = `(${MENTION_GUEST}|${MENTION_PREFIXED}|${MENTION_WITH_SPACE})`;
const USERID_REGEX = new RegExp(`${MENTION_START}${MENTION_SIMPLE}`, "gi");
const USERID_REGEX_WITH_SPACE = new RegExp(`${MENTION_START}${MENTION_COMPLEX}`, "gi");
const richEditor = {
  props: {
    userData: {
      type: Object,
      default: () => ({})
    }
  },
  methods: {
    /**
     * Convert the value string to html for the inner content
     *
     * @param {string} value the content without html
     * @return {string} rendered html
     */
    renderContent(value) {
      const sanitizedValue = escapeHTML(value);
      const splitValue = sanitizedValue.split(USERID_REGEX).map((part) => part.split(USERID_REGEX_WITH_SPACE)).flat();
      return splitValue.map((part) => {
        if (!part.startsWith("@")) {
          return part;
        }
        const id = part.slice(1).replace(/&quot;/gi, "");
        return this.genSelectTemplate(id);
      }).join("").replace(/\n/gmi, "<br>").replace(/&amp;/gmi, "&");
    },
    /**
     * Convert the innerHtml content to a string with mentions as text
     *
     * @param {string} content the content without html
     * @return {string}
     */
    parseContent(content) {
      let text = content;
      text = text.replace(/<br>/gmi, "\n");
      text = text.replace(/&nbsp;/gmi, " ");
      text = text.replace(/&amp;/gmi, "&");
      text = text.replace(/<\/div>/gmi, "\n");
      text = stripTags(text, "<div>");
      text = stripTags(text);
      return text;
    },
    /**
     * Generate an autocompletion popup entry template
     *
     * @param {string} value the value to match against the userData
     * @return {string}
     */
    genSelectTemplate(value) {
      if (typeof value === "undefined") {
        return `${this.autocompleteTribute.current.collection.trigger}${this.autocompleteTribute.current.mentionText}`;
      }
      const data = this.userData[value];
      if (!data) {
        return [" ", "/", ":"].every((char) => !value.includes(char)) ? `@${value}` : `@"${value}"`;
      }
      return this.renderComponentHtml(data, NcMentionBubble).replace(/[\n\t]/gmi, "").replace(/>\s+</g, "><");
    },
    /**
     * Render a component and return its html content
     *
     * @param {object} props the props to pass to the component
     * @param {object} component the component to render
     * @return {string} the rendered html
     */
    renderComponentHtml(props, component) {
      const Item = createApp(component, {
        ...props
      });
      const mount = document.createElement("div");
      mount.style.display = "none";
      document.body.appendChild(mount);
      Item.mount(mount);
      const renderedHtml = mount.innerHTML;
      Item.unmount();
      mount.remove();
      return renderedHtml;
    }
  }
};
const _sfc_main$1 = {
  name: "NcAutoCompleteResult",
  components: {
    NcUserStatusIcon
  },
  /* eslint vue/require-prop-comment: warn -- TODO: Add a proper doc block about what this props do */
  props: {
    /**
     * The label text
     */
    label: {
      type: String,
      required: false,
      default: null
    },
    /**
     * The secondary line of text if any
     */
    subline: {
      type: String,
      default: null
    },
    /**
     * Unique id
     */
    id: {
      type: String,
      default: null
    },
    /**
     * The icon class
     */
    icon: {
      type: String,
      required: true
    },
    /**
     * Icon as external URL
     */
    iconUrl: {
      type: String,
      default: null
    },
    source: {
      type: String,
      required: true
    },
    status: {
      type: [Object, Array],
      default: () => ({})
    }
  },
  setup() {
    const isDarkTheme = useIsDarkTheme();
    return {
      isDarkTheme
    };
  },
  computed: {
    avatarUrl() {
      if (this.iconUrl) {
        return this.iconUrl;
      }
      return this.id && this.source === "users" ? getAvatarUrl(this.id, { isDarkTheme: this.isDarkTheme }) : null;
    }
  }
};
const _hoisted_1$1 = { class: "autocomplete-result" };
const _hoisted_2$1 = {
  key: 0,
  class: "autocomplete-result__status autocomplete-result__status--icon"
};
const _hoisted_3 = { class: "autocomplete-result__content" };
const _hoisted_4 = ["title"];
const _hoisted_5 = {
  key: 0,
  class: "autocomplete-result__subline"
};
function _sfc_render$1(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_NcUserStatusIcon = resolveComponent("NcUserStatusIcon");
  return openBlock(), createElementBlock("div", _hoisted_1$1, [
    createBaseVNode("div", {
      class: normalizeClass([[$props.icon, `autocomplete-result__icon--${$options.avatarUrl ? "with-avatar" : ""}`], "autocomplete-result__icon"]),
      style: normalizeStyle($options.avatarUrl ? { backgroundImage: `url(${$options.avatarUrl})` } : null)
    }, [
      $props.status.icon ? (openBlock(), createElementBlock("span", _hoisted_2$1, toDisplayString($props.status && $props.status.icon || ""), 1)) : $props.status.status && $props.status.status !== "offline" ? (openBlock(), createBlock(_component_NcUserStatusIcon, {
        key: 1,
        class: "autocomplete-result__status",
        status: $props.status.status
      }, null, 8, ["status"])) : createCommentVNode("", true)
    ], 6),
    createBaseVNode("span", _hoisted_3, [
      createBaseVNode("span", {
        class: "autocomplete-result__title",
        title: $props.label
      }, toDisplayString($props.label), 9, _hoisted_4),
      $props.subline ? (openBlock(), createElementBlock("span", _hoisted_5, toDisplayString($props.subline), 1)) : createCommentVNode("", true)
    ])
  ]);
}
const NcAutoCompleteResult = /* @__PURE__ */ _export_sfc(_sfc_main$1, [["render", _sfc_render$1], ["__scopeId", "data-v-ca83b679"]]);
register(t34, t37);
const style1 = {
  "material-design-icon": "_material-design-icon_UrExO",
  "tribute-container": "_tribute-container_aTO5r",
  "tribute-container__item": "_tribute-container__item_EHZ07",
  "tribute-container--focus-visible": "_tribute-container--focus-visible_GHsDW",
  "tribute-container-autocomplete": "_tribute-container-autocomplete_YNk1h",
  "tribute-container-emoji": "_tribute-container-emoji_jWgZX",
  "tribute-container-link": "_tribute-container-link_1b7mc",
  "tribute-item": "_tribute-item_p5sRT",
  "tribute-item__title": "_tribute-item__title_VPcy9",
  "tribute-item__icon": "_tribute-item__icon_aTxCU"
};
const smilesCharacters = ["d", "D", "p", "P", "s", "S", "x", "X", ")", "(", "|", "/"];
const textSmiles = [];
smilesCharacters.forEach((char) => {
  textSmiles.push(":" + char);
  textSmiles.push(":-" + char);
});
const _sfc_main = {
  name: "NcRichContenteditable",
  mixins: [richEditor],
  inheritAttrs: false,
  props: {
    /**
     * The ID attribute of the content editable
     */
    id: {
      type: String,
      default: () => createElementId()
    },
    /**
     * Visual label of the contenteditable
     */
    label: {
      type: String,
      default: ""
    },
    /**
     * The text content
     */
    modelValue: {
      type: String,
      required: true
    },
    /**
     * Placeholder to be shown if empty
     */
    placeholder: {
      type: String,
      default: t("Write a message …")
    },
    /**
     * Auto complete function
     */
    autoComplete: {
      type: Function,
      default: () => []
    },
    /**
     * The containing element for the menu popover
     */
    menuContainer: {
      type: Element,
      default: () => document.body
    },
    /**
     * Make the contenteditable looks like a textarea or not.
     * Default looks like a single-line input.
     * This also handle the default enter/shift+enter behaviour.
     * if multiline, enter = newline; otherwise enter = submit
     * shift+enter always add a new line. ctrl+enter always submits
     */
    multiline: {
      type: Boolean,
      default: false
    },
    /**
     * Is the content editable ?
     */
    contenteditable: {
      type: Boolean,
      default: true
    },
    /**
     * Disable the editing and show specific disabled design
     */
    disabled: {
      type: Boolean,
      default: false
    },
    /**
     * Max allowed length
     */
    maxlength: {
      type: Number,
      default: null
    },
    /**
     * Enable or disable emoji autocompletion
     */
    emojiAutocomplete: {
      type: Boolean,
      default: true
    },
    /**
     * Enable or disable link autocompletion
     */
    linkAutocomplete: {
      type: Boolean,
      default: true
    },
    /**
     * CSS class to apply to the root element.
     */
    class: {
      type: [String, Array, Object],
      default: ""
    }
  },
  emits: [
    "paste",
    "update:modelValue",
    "smartPickerSubmit",
    "submit"
  ],
  setup() {
    const segmenter = new Intl.Segmenter();
    return {
      // Constants
      labelId: createElementId(),
      tributeId: createElementId(),
      segmenter,
      /**
       * Non-reactive property to store Tribute instance
       *
       * @type {import('tributejs').default | null}
       */
      tribute: null,
      tributeStyleMutationObserver: null
    };
  },
  data() {
    return {
      // Represent the raw untrimmed text of the contenteditable
      // serves no other purpose than to check whether the
      // content is empty or not
      localValue: this.modelValue,
      // Is in text composition session in IME
      isComposing: false,
      // Tribute autocomplete
      isAutocompleteOpen: false,
      autocompleteActiveId: void 0,
      isTributeIntegrationDone: false
    };
  },
  computed: {
    /**
     * Is the current trimmed value empty?
     *
     * @return {boolean}
     */
    isEmptyValue() {
      return !this.localValue || this.localValue.trim() === "";
    },
    /**
     * Is the current value over maxlength?
     *
     * @return {boolean}
     */
    isOverMaxlength() {
      if (this.isEmptyValue || !this.maxlength) {
        return false;
      }
      const length = [...this.segmenter.segment(this.localValue)].length;
      return length > this.maxlength;
    },
    /**
     * Tooltip to show if characters count is over limit
     *
     * @return {string}
     */
    tooltipString() {
      if (!this.isOverMaxlength) {
        return null;
      }
      return n("Message limit of %n character reached", "Message limit of %n characters reached", this.maxlength);
    },
    /**
     * Edit is only allowed when contenteditableis true and disabled is false
     *
     * @return {boolean}
     */
    canEdit() {
      return this.contenteditable && !this.disabled;
    },
    /**
     * Compute debounce function for the autocomplete function
     */
    debouncedAutoComplete() {
      return debounce(async (search, callback) => {
        this.autoComplete(search, callback);
      }, 100);
    }
  },
  watch: {
    /**
     * If the parent value change, we compare the plain text rendering
     * If it's different, we render everything and update the main content
     */
    modelValue() {
      const html = this.$refs.contenteditable.innerHTML;
      if (this.modelValue.trim() !== this.parseContent(html).trim()) {
        this.updateContent(this.modelValue);
      }
    }
  },
  mounted() {
    this.initializeTribute();
    this.updateContent(this.modelValue);
    this.$refs.contenteditable.contentEditable = this.canEdit;
  },
  beforeUnmount() {
    if (this.tribute) {
      this.tribute.detach(this.$refs.contenteditable);
    }
    if (this.tributeStyleMutationObserver) {
      this.tributeStyleMutationObserver.disconnect();
    }
  },
  methods: {
    /**
     * Focus the richContenteditable
     *
     * @public
     */
    focus() {
      this.$refs.contenteditable.focus();
    },
    initializeTribute() {
      const renderMenuItem = (content) => `<div id="${createElementId()}" class="${this.$style["tribute-item"]}" role="option">${content}</div>`;
      const tributesCollection = [];
      tributesCollection.push({
        fillAttr: "id",
        // Search against id and label (display name) (fallback to title for v8.0.0..8.6.1 compatibility)
        lookup: (result) => `${result.id} ${result.label ?? result.title}`,
        requireLeadingSpace: true,
        // Popup mention autocompletion templates
        menuItemTemplate: (item) => renderMenuItem(this.renderComponentHtml(item.original, NcAutoCompleteResult)),
        // Hide if no results
        noMatchTemplate: () => '<span class="hidden"></span>',
        // Inner display of mentions
        selectTemplate: (item) => this.genSelectTemplate(item?.original?.id),
        // Autocompletion results
        values: this.debouncedAutoComplete,
        // Class added to the menu container
        containerClass: `${this.$style["tribute-container"]} ${this.$style["tribute-container-autocomplete"]}`,
        // Class added to each list item
        itemClass: this.$style["tribute-container__item"]
      });
      if (this.emojiAutocomplete) {
        tributesCollection.push({
          trigger: ":",
          // Don't use the tribute search function at all
          // We pass search results as values (see below)
          lookup: (result, query) => query,
          requireLeadingSpace: true,
          // Popup mention autocompletion templates
          menuItemTemplate: (item) => {
            if (textSmiles.includes(item.original)) {
              return item.original;
            }
            return renderMenuItem(`<span class="${this.$style["tribute-item__emoji"]}">${item.original.native}</span> :${item.original.short_name}`);
          },
          // Hide if no results
          noMatchTemplate: () => t("No emoji found"),
          // Display raw emoji along with its name
          selectTemplate: (item) => {
            if (textSmiles.includes(item.original)) {
              return item.original;
            }
            emojiAddRecent(item.original);
            return item.original.native;
          },
          // Pass the search results as values
          values: (text, cb) => {
            const emojiResults = emojiSearch(text);
            if (textSmiles.includes(":" + text)) {
              emojiResults.unshift(":" + text);
            }
            cb(emojiResults);
          },
          // Class added to the menu container
          containerClass: `${this.$style["tribute-container"]} ${this.$style["tribute-container-emoji"]}`,
          // Class added to each list item
          itemClass: this.$style["tribute-container__item"]
        });
      }
      if (this.linkAutocomplete) {
        tributesCollection.push({
          trigger: "/",
          // Don't use the tribute search function at all
          // We pass search results as values (see below)
          lookup: (result, query) => query,
          requireLeadingSpace: true,
          // Popup mention autocompletion templates
          menuItemTemplate: (item) => renderMenuItem(`<img class="${this.$style["tribute-item__icon"]}" src="${item.original.icon_url}"> <span class="${this.$style["tribute-item__title"]}">${item.original.title}</span>`),
          // Hide if no results
          noMatchTemplate: () => t("No link provider found"),
          selectTemplate: this.getLink,
          // Pass the search results as values
          values: (text, cb) => cb(searchProvider(text)),
          // Class added to the menu container
          containerClass: `${this.$style["tribute-container"]} ${this.$style["tribute-container-link"]}`,
          // Class added to each list item
          itemClass: this.$style["tribute-container__item"]
        });
      }
      this.tribute = new Tribute({
        collection: tributesCollection,
        // FIXME: tributejs doesn't support allowSpaces as a collection option, only as a global one
        // Requires to fork a library to allow spaces only in the middle of mentions ('@' trigger)
        allowSpaces: false,
        // Where to inject the menu popup
        menuContainer: this.menuContainer
      });
      this.tribute.attach(this.$refs.contenteditable);
    },
    getLink(item) {
      getLinkWithPicker(item.original.id).then((result) => {
        const tmpElem = document.getElementById("tmp-smart-picker-result-node");
        const eventData = {
          result,
          insertText: true
        };
        this.$emit("smartPickerSubmit", eventData);
        if (eventData.insertText) {
          const newElem = document.createTextNode(result);
          tmpElem.replaceWith(newElem);
          this.setCursorAfter(newElem);
          this.updateValue(this.$refs.contenteditable.innerHTML);
        } else {
          tmpElem.remove();
        }
      }).catch((error) => {
        logger.debug("[NcRichContenteditable] Smart picker promise rejected:", { error });
        const tmpElem = document.getElementById("tmp-smart-picker-result-node");
        this.setCursorAfter(tmpElem);
        tmpElem.remove();
      });
      return '<span id="tmp-smart-picker-result-node"></span>';
    },
    setCursorAfter(element) {
      const range = document.createRange();
      range.setEndAfter(element);
      range.collapse();
      const selection = window.getSelection();
      selection.removeAllRanges();
      selection.addRange(range);
    },
    moveCursorToEnd() {
      if (!document.createRange) {
        return;
      }
      if (window.getSelection().rangeCount > 0 && this.$refs.contenteditable.contains(window.getSelection().getRangeAt(0).commonAncestorContainer)) {
        return;
      }
      const range = document.createRange();
      range.selectNodeContents(this.$refs.contenteditable);
      range.collapse(false);
      const selection = window.getSelection();
      selection.removeAllRanges();
      selection.addRange(range);
    },
    /**
     * Re-emit the input event to the parent
     *
     * @param {Event} event the input event
     */
    onInput(event) {
      this.updateValue(event.target.innerHTML);
    },
    /**
     * When pasting, sanitize the content, extract text
     * and render it again
     *
     * @param {Event} event the paste event
     * @fires Event paste the original paste event
     */
    onPaste(event) {
      if (!this.canEdit) {
        return;
      }
      event.preventDefault();
      const clipboardData = event.clipboardData;
      this.$emit("paste", event);
      if (clipboardData.files.length !== 0 || !Object.values(clipboardData.items).find((item) => item?.type.startsWith("text"))) {
        return;
      }
      const text = clipboardData.getData("text");
      const selection = window.getSelection();
      const range = selection.getRangeAt(0);
      range.deleteContents();
      range.insertNode(document.createTextNode(text));
      range.collapse(false);
      this.updateValue(this.$refs.contenteditable.innerHTML);
    },
    /**
     * Update the value text from the provided html
     *
     * @param {string} htmlOrText the html content (or raw text with @mentions)
     */
    updateValue(htmlOrText) {
      const text = this.parseContent(htmlOrText).replace(/^\n$/, "");
      this.localValue = text;
      this.$emit("update:modelValue", text);
    },
    /**
     * Update content and local value
     *
     * @param {string} value the message value
     */
    updateContent(value) {
      const renderedContent = this.renderContent(value);
      this.$refs.contenteditable.innerHTML = renderedContent;
      this.localValue = value;
    },
    /**
     * Enter key pressed. Submits if not multiline
     *
     * @param {Event} event the keydown event
     */
    onEnter(event) {
      if (this.multiline || this.isOverMaxlength || this.tribute.isActive || this.isComposing) {
        return;
      }
      event.preventDefault();
      event.stopPropagation();
      this.$emit("submit", event);
    },
    /**
     * Ctrl + Enter key pressed is used to submit
     *
     * @param {Event} event the keydown event
     */
    onCtrlEnter(event) {
      if (this.isOverMaxlength) {
        return;
      }
      this.$emit("submit", event);
    },
    onKeyUp(event) {
      event.stopImmediatePropagation();
    },
    onKeyEsc(event) {
      if (this.tribute && this.isAutocompleteOpen) {
        event.stopImmediatePropagation();
        this.tribute.hideMenu();
      }
    },
    /**
     * Get HTML element with Tribute.js container
     *
     * @return {HTMLElement}
     */
    getTributeContainer() {
      return this.tribute.menu;
    },
    /**
     * Get the currently selected item element id in Tribute.js container
     *
     * @return {HTMLElement}
     */
    getTributeSelectedItem() {
      return this.getTributeContainer().querySelector('.highlight [id^="nc-rich-contenteditable-tribute-item-"]');
    },
    /**
     * Handle Tribute activation
     *
     * @param {boolean} isActive - is active
     */
    onTributeActive(isActive) {
      this.isAutocompleteOpen = isActive;
      if (isActive) {
        this.getTributeContainer().setAttribute("class", this.tribute.current.collection.containerClass || this.$style["tribute-container"]);
        this.setupTributeIntegration();
        document.removeEventListener("click", this.hideTribute, true);
      } else {
        this.debouncedAutoComplete.clear();
        this.autocompleteActiveId = void 0;
        this.setTributeFocusVisible(false);
      }
    },
    onTributeArrowKeyDown() {
      if (!this.isAutocompleteOpen) {
        return;
      }
      this.setTributeFocusVisible(true);
      this.onTributeSelectedItemWillChange();
    },
    onTributeSelectedItemWillChange() {
      requestAnimationFrame(() => {
        this.autocompleteActiveId = this.getTributeSelectedItem()?.id;
      });
    },
    setupTributeIntegration() {
      if (this.isTributeIntegrationDone) {
        return;
      }
      this.isTributeIntegrationDone = true;
      const tributeContainer = this.getTributeContainer();
      tributeContainer.id = this.tributeId;
      tributeContainer.setAttribute("role", "listbox");
      const ul = tributeContainer.children[0];
      ul.setAttribute("role", "presentation");
      this.tributeStyleMutationObserver = new MutationObserver(([{ target }]) => {
        if (target.style.display !== "none") {
          this.onTributeSelectedItemWillChange();
        }
      }).observe(tributeContainer, {
        attributes: true,
        attributeFilter: ["style"]
      });
      tributeContainer.addEventListener("mousemove", () => {
        this.setTributeFocusVisible(false);
        this.onTributeSelectedItemWillChange();
      }, { passive: true });
    },
    /**
     * Set tribute-container--focus-visible class on the Tribute container when the user navigates the listbox via keyboard.
     *
     * Because the real focus is kept on the textbox, we cannot use the :focus-visible pseudo-class
     * to style selected options in the autocomplete listbox.
     *
     * @param {boolean} withFocusVisible - should the focus-visible class be added
     */
    setTributeFocusVisible(withFocusVisible) {
      if (withFocusVisible) {
        this.getTributeContainer().classList.add(this.$style["tribute-container--focus-visible"]);
      } else {
        this.getTributeContainer().classList.remove(this.$style["tribute-container--focus-visible"]);
      }
    },
    /**
     * Show tribute menu programmatically.
     *
     * @param {string} trigger - trigger character, can be '/', '@', or ':'
     *
     * @public
     */
    showTribute(trigger) {
      this.focus();
      const index = this.tribute.collection.findIndex((collection) => collection.trigger === trigger);
      this.tribute.showMenuForCollection(this.$refs.contenteditable, index);
      this.updateValue(this.$refs.contenteditable.innerHTML);
      document.addEventListener("click", this.hideTribute, true);
    },
    /**
     * Hide tribute menu programmatically
     *
     */
    hideTribute() {
      this.tribute.hideMenu();
      document.removeEventListener("click", this.hideTribute, true);
    }
  }
};
const _hoisted_1 = ["id", "contenteditable", "aria-labelledby", "aria-placeholder", "aria-controls", "aria-expanded", "aria-activedescendant", "title"];
const _hoisted_2 = ["id"];
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("div", {
    class: normalizeClass(["rich-contenteditable", _ctx.$props.class])
  }, [
    createBaseVNode("div", mergeProps({
      id: $props.id,
      ref: "contenteditable",
      class: [{
        "rich-contenteditable__input--empty": $options.isEmptyValue,
        "rich-contenteditable__input--multiline": $props.multiline,
        "rich-contenteditable__input--has-label": $props.label,
        "rich-contenteditable__input--overflow": $options.isOverMaxlength,
        "rich-contenteditable__input--disabled": $props.disabled
      }, "rich-contenteditable__input"],
      contenteditable: $options.canEdit,
      "aria-labelledby": $props.label ? $setup.labelId : void 0,
      "aria-placeholder": $props.placeholder,
      "aria-multiline": "true",
      role: "textbox",
      "aria-haspopup": "listbox",
      "aria-autocomplete": "inline",
      "aria-controls": $setup.tributeId,
      "aria-expanded": $data.isAutocompleteOpen ? "true" : "false",
      "aria-activedescendant": $data.autocompleteActiveId,
      title: $options.tooltipString
    }, _ctx.$attrs, {
      onFocus: _cache[0] || (_cache[0] = (...args) => $options.moveCursorToEnd && $options.moveCursorToEnd(...args)),
      onInput: _cache[1] || (_cache[1] = (...args) => $options.onInput && $options.onInput(...args)),
      onCompositionstart: _cache[2] || (_cache[2] = ($event) => $data.isComposing = true),
      onCompositionend: _cache[3] || (_cache[3] = ($event) => $data.isComposing = false),
      onKeydownCapture: _cache[4] || (_cache[4] = withKeys((...args) => $options.onKeyEsc && $options.onKeyEsc(...args), ["esc"])),
      onKeydown: [
        _cache[5] || (_cache[5] = withKeys(withModifiers((...args) => $options.onEnter && $options.onEnter(...args), ["exact"]), ["enter"])),
        _cache[6] || (_cache[6] = withKeys(withModifiers((...args) => $options.onCtrlEnter && $options.onCtrlEnter(...args), ["ctrl", "exact", "stop", "prevent"]), ["enter"])),
        _cache[9] || (_cache[9] = withKeys(withModifiers((...args) => $options.onTributeArrowKeyDown && $options.onTributeArrowKeyDown(...args), ["exact", "stop"]), ["up"])),
        _cache[10] || (_cache[10] = withKeys(withModifiers((...args) => $options.onTributeArrowKeyDown && $options.onTributeArrowKeyDown(...args), ["exact", "stop"]), ["down"]))
      ],
      onPaste: _cache[7] || (_cache[7] = (...args) => $options.onPaste && $options.onPaste(...args)),
      onKeyupCapture: _cache[8] || (_cache[8] = withModifiers((...args) => $options.onKeyUp && $options.onKeyUp(...args), ["stop", "prevent"])),
      onTributeActiveTrue: _cache[11] || (_cache[11] = ($event) => $options.onTributeActive(true)),
      onTributeActiveFalse: _cache[12] || (_cache[12] = ($event) => $options.onTributeActive(false))
    }), null, 16, _hoisted_1),
    $props.label ? (openBlock(), createElementBlock("div", {
      key: 0,
      id: $setup.labelId,
      class: "rich-contenteditable__label"
    }, toDisplayString($props.label), 9, _hoisted_2)) : createCommentVNode("", true)
  ], 2);
}
const cssModules = {
  "$style": style1
};
const NcRichContenteditable = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render], ["__cssModules", cssModules], ["__scopeId", "data-v-faef642b"]]);
export {
  NcAutoCompleteResult,
  NcMentionBubble,
  NcRichContenteditable as default
};
//# sourceMappingURL=index-gwTr8m4i.chunk.mjs.map
