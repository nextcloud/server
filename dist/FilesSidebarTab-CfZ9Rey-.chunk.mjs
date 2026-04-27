const appName = "nextcloud-ui";
const appVersion = "1.0.0";
import { f as createElementBlock, g as createBaseVNode, t as toDisplayString, h as createCommentVNode, m as mergeProps, o as openBlock, r as resolveComponent, a9 as resolveDirective, E as withDirectives, x as createVNode, F as Fragment, c as createBlock, w as withCtx, C as renderList, v as normalizeClass, j as createTextVNode, b as defineComponent, n as computed } from "./preload-helper-xAe3EUYB.chunk.mjs";
import { a as showError } from "./index-C1xmmKTZ-kBgT3zMc.chunk.mjs";
import { t as translate } from "./translation-DoG5ZELJ-2UfAUX2V.chunk.mjs";
import { v as vElementVisibility } from "./NcAvatar-C9d7Wrc8-C3hNgT1e.chunk.mjs";
import { N as NcButton } from "./ArrowRight-BC77f5L9.chunk.mjs";
import { N as NcEmptyContent } from "./NcEmptyContent-B8-90BSI-BIPGjjpl.chunk.mjs";
import { _ as _export_sfc } from "./index-o76qk6sn.chunk.mjs";
import { C as CommentView, a as Comment } from "./CommentView-DcG_o6xO.chunk.mjs";
import { l as logger } from "./activity-C3wf9N73.chunk.mjs";
import { c as client, g as getComments, D as DEFAULT_LIMIT } from "./GetComments-DAgltXhH.chunk.mjs";
const _sfc_main$4 = {
  name: "AlertCircleOutlineIcon",
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
const _hoisted_2$3 = ["fill", "width", "height"];
const _hoisted_3$3 = { d: "M11,15H13V17H11V15M11,7H13V13H11V7M12,2C6.47,2 2,6.5 2,12A10,10 0 0,0 12,22A10,10 0 0,0 22,12A10,10 0 0,0 12,2M12,20A8,8 0 0,1 4,12A8,8 0 0,1 12,4A8,8 0 0,1 20,12A8,8 0 0,1 12,20Z" };
const _hoisted_4$2 = { key: 0 };
function _sfc_render$4(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("span", mergeProps(_ctx.$attrs, {
    "aria-hidden": $props.title ? null : "true",
    "aria-label": $props.title,
    class: "material-design-icon alert-circle-outline-icon",
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
        $props.title ? (openBlock(), createElementBlock(
          "title",
          _hoisted_4$2,
          toDisplayString($props.title),
          1
          /* TEXT */
        )) : createCommentVNode("v-if", true)
      ])
    ], 8, _hoisted_2$3))
  ], 16, _hoisted_1$3);
}
const IconAlertCircleOutline = /* @__PURE__ */ _export_sfc(_sfc_main$4, [["render", _sfc_render$4], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/node_modules/vue-material-design-icons/AlertCircleOutline.vue"]]);
const _sfc_main$3 = {
  name: "MessageReplyTextOutlineIcon",
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
const _hoisted_1$2 = ["aria-hidden", "aria-label"];
const _hoisted_2$2 = ["fill", "width", "height"];
const _hoisted_3$2 = { d: "M9 11H18V13H9V11M18 7H6V9H18V7M22 4V22L18 18H4C2.9 18 2 17.11 2 16V4C2 2.9 2.9 2 4 2H20C21.1 2 22 2.89 22 4M20 4H4V16H18.83L20 17.17V4Z" };
const _hoisted_4$1 = { key: 0 };
function _sfc_render$3(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("span", mergeProps(_ctx.$attrs, {
    "aria-hidden": $props.title ? null : "true",
    "aria-label": $props.title,
    class: "material-design-icon message-reply-text-outline-icon",
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
      createBaseVNode("path", _hoisted_3$2, [
        $props.title ? (openBlock(), createElementBlock(
          "title",
          _hoisted_4$1,
          toDisplayString($props.title),
          1
          /* TEXT */
        )) : createCommentVNode("v-if", true)
      ])
    ], 8, _hoisted_2$2))
  ], 16, _hoisted_1$2);
}
const IconMessageReplyTextOutline = /* @__PURE__ */ _export_sfc(_sfc_main$3, [["render", _sfc_render$3], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/node_modules/vue-material-design-icons/MessageReplyTextOutline.vue"]]);
const _sfc_main$2 = {
  name: "RefreshIcon",
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
const _hoisted_3$1 = { d: "M17.65,6.35C16.2,4.9 14.21,4 12,4A8,8 0 0,0 4,12A8,8 0 0,0 12,20C15.73,20 18.84,17.45 19.73,14H17.65C16.83,16.33 14.61,18 12,18A6,6 0 0,1 6,12A6,6 0 0,1 12,6C13.66,6 15.14,6.69 16.22,7.78L13,11H20V4L17.65,6.35Z" };
const _hoisted_4 = { key: 0 };
function _sfc_render$2(_ctx, _cache, $props, $setup, $data, $options) {
  return openBlock(), createElementBlock("span", mergeProps(_ctx.$attrs, {
    "aria-hidden": $props.title ? null : "true",
    "aria-label": $props.title,
    class: "material-design-icon refresh-icon",
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
        $props.title ? (openBlock(), createElementBlock(
          "title",
          _hoisted_4,
          toDisplayString($props.title),
          1
          /* TEXT */
        )) : createCommentVNode("v-if", true)
      ])
    ], 8, _hoisted_2$1))
  ], 16, _hoisted_1$1);
}
const IconRefresh = /* @__PURE__ */ _export_sfc(_sfc_main$2, [["render", _sfc_render$2], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/node_modules/vue-material-design-icons/Refresh.vue"]]);
function markCommentsAsRead(resourceType, resourceId, date) {
  const resourcePath = ["", resourceType, resourceId].join("/");
  const readMarker = date.toUTCString();
  return client.customRequest(resourcePath, {
    method: "PROPPATCH",
    data: `<?xml version="1.0"?>
			<d:propertyupdate
				xmlns:d="DAV:"
				xmlns:oc="http://owncloud.org/ns">
			<d:set>
				<d:prop>
					<oc:readMarker>${readMarker}</oc:readMarker>
				</d:prop>
			</d:set>
			</d:propertyupdate>`
  });
}
function cancelableRequest(request) {
  const controller = new AbortController();
  const signal = controller.signal;
  const fetch = async function(url, options) {
    const response = await request(
      url,
      { signal, ...options }
    );
    return response;
  };
  return {
    request: fetch,
    abort: () => controller.abort()
  };
}
const _sfc_main$1 = {
  name: "CommentsApp",
  components: {
    Comment,
    NcEmptyContent,
    NcButton,
    IconRefresh,
    IconMessageReplyTextOutline,
    IconAlertCircleOutline
  },
  directives: {
    elementVisibility: vElementVisibility
  },
  mixins: [CommentView],
  expose: ["update"],
  data() {
    return {
      error: "",
      loading: false,
      done: false,
      offset: 0,
      comments: [],
      cancelRequest: () => {
      },
      Comment,
      userData: {}
    };
  },
  computed: {
    hasComments() {
      return this.comments.length > 0;
    },
    isFirstLoading() {
      return this.loading && this.offset === 0;
    }
  },
  watch: {
    resourceId() {
      this.currentResourceId = this.resourceId;
    }
  },
  methods: {
    t: translate,
    async onVisibilityChange(isVisible) {
      if (isVisible) {
        try {
          await markCommentsAsRead(this.resourceType, this.currentResourceId, /* @__PURE__ */ new Date());
        } catch (e) {
          showError(e.message || translate("comments", "Failed to mark comments as read"));
        }
      }
    },
    /**
     * Update current resourceId and fetch new data
     *
     * @param {number} resourceId the current resourceId (fileId...)
     */
    async update(resourceId) {
      this.currentResourceId = resourceId;
      this.resetState();
      await this.getComments();
    },
    /**
     * Ran when the bottom of the tab is reached
     */
    onScrollBottomReached() {
      if (this.error || this.done || this.loading) {
        return;
      }
      this.getComments();
    },
    /**
     * Get the existing shares infos
     */
    async getComments() {
      this.cancelRequest("cancel");
      try {
        this.loading = true;
        this.error = "";
        const { request, abort } = cancelableRequest(getComments);
        this.cancelRequest = abort;
        const { data: comments } = await request({
          resourceType: this.resourceType,
          resourceId: this.currentResourceId
        }, { offset: this.offset }) || { data: [] };
        this.logger.debug(`Processed ${comments.length} comments`, { comments });
        if (comments.length < DEFAULT_LIMIT) {
          this.done = true;
        }
        for (const comment of comments) {
          comment.props.actorId = comment.props.actorId.toString();
        }
        this.comments = [...this.comments, ...comments];
        this.offset += DEFAULT_LIMIT;
      } catch (error) {
        if (error.message === "cancel") {
          return;
        }
        this.error = translate("comments", "Unable to load the comments list");
        logger.error("Error loading the comments list", { error });
      } finally {
        this.loading = false;
      }
    },
    /**
     * Add newly created comment to the list
     *
     * @param {object} comment the new comment
     */
    onNewComment(comment) {
      this.comments.unshift(comment);
    },
    /**
     * Remove deleted comment from the list
     *
     * @param {number} id the deleted comment
     */
    onDelete(id) {
      const index = this.comments.findIndex((comment) => comment.props.id === id);
      if (index > -1) {
        this.comments.splice(index, 1);
      } else {
        logger.error("Could not find the deleted comment in the list", { id });
      }
    },
    /**
     * Reset the current view to its default state
     */
    resetState() {
      this.error = "";
      this.loading = false;
      this.done = false;
      this.offset = 0;
      this.comments = [];
    }
  }
};
const _hoisted_1 = { key: 1 };
const _hoisted_2 = {
  key: 2,
  class: "comments__info icon-loading"
};
const _hoisted_3 = {
  key: 3,
  class: "comments__info"
};
function _sfc_render$1(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_Comment = resolveComponent("Comment");
  const _component_IconMessageReplyTextOutline = resolveComponent("IconMessageReplyTextOutline");
  const _component_NcEmptyContent = resolveComponent("NcEmptyContent");
  const _component_IconAlertCircleOutline = resolveComponent("IconAlertCircleOutline");
  const _component_IconRefresh = resolveComponent("IconRefresh");
  const _component_NcButton = resolveComponent("NcButton");
  const _directive_element_visibility = resolveDirective("element-visibility");
  return withDirectives((openBlock(), createElementBlock(
    "div",
    {
      class: normalizeClass(["comments", { "icon-loading": $options.isFirstLoading }])
    },
    [
      createCommentVNode(" Editor "),
      createVNode(_component_Comment, mergeProps(_ctx.editorData, {
        editor: "",
        autoComplete: _ctx.autoComplete,
        resourceType: _ctx.resourceType,
        userData: $data.userData,
        resourceId: _ctx.currentResourceId,
        class: "comments__writer",
        onNew: $options.onNewComment
      }), null, 16, ["autoComplete", "resourceType", "userData", "resourceId", "onNew"]),
      !$options.isFirstLoading ? (openBlock(), createElementBlock(
        Fragment,
        { key: 0 },
        [
          !$options.hasComments && $data.done ? (openBlock(), createBlock(_component_NcEmptyContent, {
            key: 0,
            class: "comments__empty",
            name: $options.t("comments", "No comments yet, start the conversation!")
          }, {
            icon: withCtx(() => [
              createVNode(_component_IconMessageReplyTextOutline)
            ]),
            _: 1
            /* STABLE */
          }, 8, ["name"])) : (openBlock(), createElementBlock("ul", _hoisted_1, [
            createCommentVNode(" Comments "),
            (openBlock(true), createElementBlock(
              Fragment,
              null,
              renderList($data.comments, (comment) => {
                return openBlock(), createBlock(_component_Comment, mergeProps({
                  key: comment.props.id,
                  modelValue: comment.props.message,
                  "onUpdate:modelValue": ($event) => comment.props.message = $event,
                  tag: "li"
                }, { ref_for: true }, comment.props, {
                  autoComplete: _ctx.autoComplete,
                  resourceType: _ctx.resourceType,
                  resourceId: _ctx.currentResourceId,
                  userData: _ctx.genMentionsData(comment.props.mentions),
                  class: "comments__list",
                  onDelete: $options.onDelete
                }), null, 16, ["modelValue", "onUpdate:modelValue", "autoComplete", "resourceType", "resourceId", "userData", "onDelete"]);
              }),
              128
              /* KEYED_FRAGMENT */
            ))
          ])),
          createCommentVNode(" Loading more message "),
          $data.loading && !$options.isFirstLoading ? (openBlock(), createElementBlock("div", _hoisted_2)) : $options.hasComments && $data.done ? (openBlock(), createElementBlock(
            "div",
            _hoisted_3,
            toDisplayString($options.t("comments", "No more messages")),
            1
            /* TEXT */
          )) : $data.error ? (openBlock(), createElementBlock(
            Fragment,
            { key: 4 },
            [
              createCommentVNode(" Error message "),
              createVNode(_component_NcEmptyContent, {
                class: "comments__error",
                name: $data.error
              }, {
                icon: withCtx(() => [
                  createVNode(_component_IconAlertCircleOutline)
                ]),
                _: 1
                /* STABLE */
              }, 8, ["name"]),
              createVNode(_component_NcButton, {
                class: "comments__retry",
                onClick: $options.getComments
              }, {
                icon: withCtx(() => [
                  createVNode(_component_IconRefresh)
                ]),
                default: withCtx(() => [
                  createTextVNode(
                    " " + toDisplayString($options.t("comments", "Retry")),
                    1
                    /* TEXT */
                  )
                ]),
                _: 1
                /* STABLE */
              }, 8, ["onClick"])
            ],
            64
            /* STABLE_FRAGMENT */
          )) : createCommentVNode("v-if", true)
        ],
        64
        /* STABLE_FRAGMENT */
      )) : createCommentVNode("v-if", true)
    ],
    2
    /* CLASS */
  )), [
    [_directive_element_visibility, $options.onVisibilityChange]
  ]);
}
const CommentsApp = /* @__PURE__ */ _export_sfc(_sfc_main$1, [["render", _sfc_render$1], ["__scopeId", "data-v-19de1880"], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/build/frontend/apps/comments/src/views/CommentsApp.vue"]]);
const _sfc_main = /* @__PURE__ */ defineComponent({
  __name: "FilesSidebarTab",
  props: {
    node: { type: null, required: false },
    active: { type: Boolean, required: false },
    folder: { type: null, required: false },
    view: { type: null, required: false }
  },
  setup(__props, { expose: __expose }) {
    __expose();
    const props = __props;
    const resourceId = computed(() => props.node?.fileid);
    const __returned__ = { props, resourceId, CommentsApp };
    Object.defineProperty(__returned__, "__isScriptSetup", { enumerable: false, value: true });
    return __returned__;
  }
});
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  return $setup.resourceId !== void 0 ? (openBlock(), createBlock($setup["CommentsApp"], {
    key: $setup.resourceId,
    resourceId: $setup.resourceId,
    resourceType: "files"
  }, null, 8, ["resourceId"])) : createCommentVNode("v-if", true);
}
const FilesSidebarTab = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/build/frontend/apps/comments/src/views/FilesSidebarTab.vue"]]);
const FilesSidebarTab$1 = /* @__PURE__ */ Object.freeze(/* @__PURE__ */ Object.defineProperty({
  __proto__: null,
  default: FilesSidebarTab
}, Symbol.toStringTag, { value: "Module" }));
export {
  CommentsApp as C,
  FilesSidebarTab$1 as F
};
//# sourceMappingURL=FilesSidebarTab-CfZ9Rey-.chunk.mjs.map
