const appName = "nextcloud-ui";
const appVersion = "1.0.0";
import { r as register, h as t0, b as t, _ as _export_sfc } from "./Web-BOM4en5n.chunk.mjs";
import { l as logger } from "./ArrowRight-BC77f5L9.chunk.mjs";
import { N as NcSelect, a as NcEllipsisedOption } from "./NcSelect-DLheQ2yp-DVxJhLyT.chunk.mjs";
import { c as cancelableClient } from "./index-D5H5XMHa.chunk.mjs";
import { h as generateRemoteUrl } from "./index-rAufP352.chunk.mjs";
import { r as resolveComponent, o as openBlock, c as createBlock, p as createSlots, C as renderList, w as withCtx, i as renderSlot, I as normalizeProps, J as guardReactiveProps, x as createVNode, m as mergeProps } from "./preload-helper-xAe3EUYB.chunk.mjs";
register(t0);
function xmlToJson(xml) {
  let obj = {};
  if (xml.nodeType === 1) {
    if (xml.attributes.length > 0) {
      obj["@attributes"] = {};
      for (let j = 0; j < xml.attributes.length; j++) {
        const attribute = xml.attributes.item(j);
        obj["@attributes"][attribute.nodeName] = attribute.nodeValue;
      }
    }
  } else if (xml.nodeType === 3) {
    obj = xml.nodeValue;
  }
  if (xml.hasChildNodes()) {
    for (let i = 0; i < xml.childNodes.length; i++) {
      const item = xml.childNodes.item(i);
      const nodeName = item.nodeName;
      if (typeof obj[nodeName] === "undefined") {
        obj[nodeName] = xmlToJson(item);
      } else {
        if (typeof obj[nodeName].push === "undefined") {
          const old = obj[nodeName];
          obj[nodeName] = [];
          obj[nodeName].push(old);
        }
        obj[nodeName].push(xmlToJson(item));
      }
    }
  }
  return obj;
}
function parseXml(xml) {
  let dom = null;
  try {
    dom = new DOMParser().parseFromString(xml, "text/xml");
  } catch (error) {
    logger.error("[NcSelectTags] Failed to parse xml document", { error });
  }
  return dom;
}
function xmlToTagList(xml) {
  const json = xmlToJson(parseXml(xml));
  const list = json["d:multistatus"]["d:response"];
  const result = [];
  for (const index in list) {
    const tag = list[index]["d:propstat"];
    if (tag["d:status"]["#text"] !== "HTTP/1.1 200 OK") {
      continue;
    }
    result.push({
      id: parseInt(tag["d:prop"]["oc:id"]["#text"]),
      displayName: tag["d:prop"]["oc:display-name"]["#text"],
      canAssign: tag["d:prop"]["oc:can-assign"]["#text"] === "true",
      userAssignable: tag["d:prop"]["oc:user-assignable"]["#text"] === "true",
      userVisible: tag["d:prop"]["oc:user-visible"]["#text"] === "true"
    });
  }
  return result;
}
async function searchTags() {
  if (window.NextcloudVueDocs) {
    return Promise.resolve(xmlToTagList(window.NextcloudVueDocs.tags));
  }
  const result = await cancelableClient({
    method: "PROPFIND",
    url: generateRemoteUrl("dav") + "/systemtags/",
    data: `<?xml version="1.0"?>
					<d:propfind  xmlns:d="DAV:" xmlns:oc="http://owncloud.org/ns">
					  <d:prop>
						<oc:id />
						<oc:display-name />
						<oc:user-visible />
						<oc:user-assignable />
						<oc:can-assign />
					  </d:prop>
					</d:propfind>`
  });
  return xmlToTagList(result.data);
}
const _sfc_main = {
  name: "NcSelectTags",
  components: {
    NcEllipsisedOption,
    NcSelect
  },
  props: {
    // Add NcSelect prop defaults and populate $props
    ...NcSelect.props,
    /**
     * Enable automatic fetching of tags
     *
     * If `false`, available tags must be passed using the `options` prop
     */
    fetchTags: {
      type: Boolean,
      default: true
    },
    /**
     * Callback to generate the label text
     *
     * @see https://vue-select.org/api/props.html#getoptionlabel
     */
    getOptionLabel: {
      type: Function,
      default: (option) => {
        const { displayName, userVisible, userAssignable } = option;
        if (userVisible === false) {
          return t("{tag} (invisible)", { tag: displayName });
        }
        if (userAssignable === false) {
          return t("{tag} (restricted)", { tag: displayName });
        }
        return displayName;
      }
    },
    /**
     * Sets the maximum number of tags to display in the dropdown list
     *
     * Because of compatibility reasons only 5 tag entries are shown by
     * default
     */
    limit: {
      type: Number,
      default: 5
    },
    /**
     * Allow selection of multiple options
     *
     * This prop automatically sets the internal `closeOnSelect` prop to
     * its boolean opposite
     *
     * @see https://vue-select.org/api/props.html#multiple
     */
    multiple: {
      type: Boolean,
      default: true
    },
    /**
     * Callback to filter available options
     */
    optionsFilter: {
      type: Function,
      default: null
    },
    /**
     * Enable passing of `value` prop and emitted `input` events as-is
     * i.e. for usage with `v-model`
     *
     * If `true`, custom internal `value` and `input` handling is disabled
     */
    passthru: {
      type: Boolean,
      default: false
    },
    /**
     * Placeholder text
     *
     * @see https://vue-select.org/api/props.html#placeholder
     */
    placeholder: {
      type: String,
      default: t("Select a tag")
    },
    /**
     * Currently selected value
     */
    modelValue: {
      type: [Number, Array, Object],
      default: null
    },
    /**
     * Any available prop
     *
     * @see https://vue-select.org/api/props.html
     */
    // Not an actual prop but needed to show in vue-styleguidist docs
    // eslint-disable-next-line
    " ": {}
  },
  emits: [
    "update:modelValue",
    /**
     * All events from https://vue-select.org/api/events.html
     */
    // Not an actual event but needed to show in vue-styleguidist docs
    " "
  ],
  data() {
    return {
      search: "",
      availableTags: []
    };
  },
  computed: {
    availableOptions() {
      if (this.optionsFilter) {
        return this.tags.filter(this.optionsFilter);
      }
      return this.tags;
    },
    localValue() {
      if (this.passthru) {
        return this.modelValue;
      }
      if (this.tags.length === 0) {
        return [];
      }
      if (this.multiple) {
        return this.modelValue.filter((tag) => tag !== "").map((id) => this.tags.find((tag2) => tag2.id === id));
      } else {
        return this.tags.find((tag) => tag.id === this.modelValue);
      }
    },
    propsToForward() {
      const propsToForward = { ...this.$props };
      delete propsToForward.fetchTags;
      delete propsToForward.optionsFilter;
      delete propsToForward.passthru;
      return propsToForward;
    },
    tags() {
      if (!this.fetchTags) {
        return this.options;
      }
      return this.availableTags;
    }
  },
  async created() {
    if (!this.fetchTags) {
      return;
    }
    try {
      const result = await searchTags();
      this.availableTags = result;
    } catch (error) {
      logger.error("[NcSelectTags] Loading systemtags failed", error);
    }
  },
  methods: {
    handleInput(value) {
      if (this.passthru) {
        this.$emit("update:modelValue", value);
        return;
      }
      if (this.multiple) {
        this.$emit("update:modelValue", value.map((element) => element.id));
      } else {
        if (value === null) {
          this.$emit("update:modelValue", null);
        } else {
          this.$emit("update:modelValue", value.id);
        }
      }
    }
  }
};
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_NcEllipsisedOption = resolveComponent("NcEllipsisedOption");
  const _component_NcSelect = resolveComponent("NcSelect");
  return openBlock(), createBlock(_component_NcSelect, mergeProps($options.propsToForward, {
    options: $options.availableOptions,
    closeOnSelect: !$props.multiple,
    modelValue: $options.localValue,
    onSearch: _cache[0] || (_cache[0] = ($event) => $data.search = $event),
    "onUpdate:modelValue": $options.handleInput
  }), createSlots({
    option: withCtx((option) => [
      createVNode(_component_NcEllipsisedOption, {
        name: $props.getOptionLabel(option),
        search: $data.search
      }, null, 8, ["name", "search"])
    ]),
    "selected-option": withCtx((selectedOption) => [
      createVNode(_component_NcEllipsisedOption, {
        name: $props.getOptionLabel(selectedOption),
        search: $data.search
      }, null, 8, ["name", "search"])
    ]),
    _: 2
  }, [
    renderList(_ctx.$slots, (_, name) => {
      return {
        name,
        fn: withCtx((data) => [
          renderSlot(_ctx.$slots, name, normalizeProps(guardReactiveProps(data)))
        ])
      };
    })
  ]), 1040, ["options", "closeOnSelect", "modelValue", "onUpdate:modelValue"]);
}
const NcSelectTags = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render]]);
export {
  NcSelectTags as N
};
//# sourceMappingURL=NcSelectTags-CTHyuMcq-2HejGZhj.chunk.mjs.map
