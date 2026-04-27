const appName = "nextcloud-ui";
const appVersion = "1.0.0";
import { t as translate } from "./translation-DoG5ZELJ-2UfAUX2V.chunk.mjs";
import { C as CommentView, a as Comment } from "./CommentView-DcG_o6xO.chunk.mjs";
import { r as resolveComponent, o as openBlock, c as createBlock, m as mergeProps } from "./preload-helper-xAe3EUYB.chunk.mjs";
import { _ as _export_sfc } from "./index-o76qk6sn.chunk.mjs";
import "./index-rAufP352.chunk.mjs";
import "./pinia-0yhe0wHh.chunk.mjs";
import "./util-BSOXDoOW.chunk.mjs";
import "./PencilOutline-BMYBdzdS.chunk.mjs";
import "./ArrowRight-BC77f5L9.chunk.mjs";
import "./Web-BOM4en5n.chunk.mjs";
import "./NcModal-DHryP_87-Da4dXMUU.chunk.mjs";
/* empty css                                           */
import "./NcAvatar-C9d7Wrc8-C3hNgT1e.chunk.mjs";
import "./index-D5H5XMHa.chunk.mjs";
import "./colors-BHGKZFDI-C0-WujoK.chunk.mjs";
import "./NcUserStatusIcon-XiwrgeCm-DTG2zfIi.chunk.mjs";
import "./NcDateTime.vue_vue_type_script_setup_true_lang-BhB8yA4U-DXOBfuGJ.chunk.mjs";
import "./TrashCanOutline-DgEtyFGH.chunk.mjs";
import "./NcUserBubble-vOAXLHB5-C3lGURBU.chunk.mjs";
import "./index-C1xmmKTZ-kBgT3zMc.chunk.mjs";
import "./NcDialog-BG9t4Psg-IlLJVAz0.chunk.mjs";
import "./mdi-BGU2G5q5.chunk.mjs";
import "./activity-C3wf9N73.chunk.mjs";
import "./GetComments-DAgltXhH.chunk.mjs";
import "./index-595Vk4Ec.chunk.mjs";
const _sfc_main = {
  name: "ActivityCommentEntry",
  components: {
    Comment
  },
  mixins: [CommentView],
  props: {
    comment: {
      type: Object,
      required: true
    },
    reloadCallback: {
      type: Function,
      required: true
    }
  },
  data() {
    return {
      commentMessage: ""
    };
  },
  watch: {
    comment() {
      this.commentMessage = this.comment.props.message;
    }
  },
  mounted() {
    this.commentMessage = this.comment.props.message;
  },
  methods: {
    t: translate
  }
};
function _sfc_render(_ctx, _cache, $props, $setup, $data, $options) {
  const _component_Comment = resolveComponent("Comment");
  return openBlock(), createBlock(_component_Comment, mergeProps({
    ref: "comment",
    tag: "li"
  }, $props.comment.props, {
    autoComplete: _ctx.autoComplete,
    resourceType: _ctx.resourceType,
    message: $data.commentMessage,
    resourceId: _ctx.resourceId,
    userData: _ctx.genMentionsData($props.comment.props.mentions),
    class: "comments-activity",
    onDelete: _cache[0] || (_cache[0] = ($event) => $props.reloadCallback())
  }), null, 16, ["autoComplete", "resourceType", "message", "resourceId", "userData"]);
}
const ActivityCommentEntry = /* @__PURE__ */ _export_sfc(_sfc_main, [["render", _sfc_render], ["__scopeId", "data-v-38bad313"], ["__file", "/home/rodrigo/nextcloud-docker-dev/workspace/server/build/frontend/apps/comments/src/views/ActivityCommentEntry.vue"]]);
export {
  ActivityCommentEntry as default
};
//# sourceMappingURL=ActivityCommentEntry-BL02i_q3.chunk.mjs.map
