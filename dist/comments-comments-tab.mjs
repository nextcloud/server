const __vite__mapDeps=(i,m=__vite__mapDeps,d=(m.f||(m.f=[window.OC.filePath('', '', 'dist/ActivityCommentAction-6WwJFjO5.chunk.mjs'),window.OC.filePath('', '', 'dist/index-C1xmmKTZ-kBgT3zMc.chunk.mjs'),window.OC.filePath('', '', 'dist/preload-helper-xAe3EUYB.chunk.mjs'),window.OC.filePath('', '', 'dist/NcDialog-BG9t4Psg-IlLJVAz0.chunk.mjs'),window.OC.filePath('', '', 'dist/NcModal-DHryP_87-Da4dXMUU.chunk.mjs'),window.OC.filePath('', '', 'dist/ArrowRight-BC77f5L9.chunk.mjs'),window.OC.filePath('', '', 'dist/Web-BOM4en5n.chunk.mjs'),window.OC.filePath('', '', 'dist/translation-DoG5ZELJ-2UfAUX2V.chunk.mjs'),window.OC.filePath('', '', 'dist/index-rAufP352.chunk.mjs'),window.OC.filePath('', '', 'dist/index-o76qk6sn.chunk.mjs'),window.OC.filePath('', '', 'dist/Web-N3OwSN9O.chunk.css'),window.OC.filePath('', '', 'dist/ArrowRight-Ch8zyY_U.chunk.css'),window.OC.filePath('', '', 'dist/NcModal-DHryP_87-B2iEdqc2.chunk.css'),window.OC.filePath('', '', 'dist/TrashCanOutline-DgEtyFGH.chunk.mjs'),window.OC.filePath('', '', 'dist/TrashCanOutline-CWUlo4XY.chunk.css'),window.OC.filePath('', '', 'dist/NcDialog-BG9t4Psg-BSV74Bru.chunk.css'),window.OC.filePath('', '', 'dist/mdi-BGU2G5q5.chunk.mjs'),window.OC.filePath('', '', 'dist/mdi-DZSuYX4-.chunk.css'),window.OC.filePath('', '', 'dist/CommentView-DcG_o6xO.chunk.mjs'),window.OC.filePath('', '', 'dist/pinia-0yhe0wHh.chunk.mjs'),window.OC.filePath('', '', 'dist/util-BSOXDoOW.chunk.mjs'),window.OC.filePath('', '', 'dist/PencilOutline-BMYBdzdS.chunk.mjs'),window.OC.filePath('', '', 'dist/PencilOutline-Bb0ihLdt.chunk.css'),window.OC.filePath('', '', 'dist/NcAvatar-C9d7Wrc8-C3hNgT1e.chunk.mjs'),window.OC.filePath('', '', 'dist/index-D5H5XMHa.chunk.mjs'),window.OC.filePath('', '', 'dist/colors-BHGKZFDI-C0-WujoK.chunk.mjs'),window.OC.filePath('', '', 'dist/NcUserStatusIcon-XiwrgeCm-DTG2zfIi.chunk.mjs'),window.OC.filePath('', '', 'dist/NcUserStatusIcon-XiwrgeCm-B3aHoBAd.chunk.css'),window.OC.filePath('', '', 'dist/NcDateTime.vue_vue_type_script_setup_true_lang-BhB8yA4U-DXOBfuGJ.chunk.mjs'),window.OC.filePath('', '', 'dist/NcDateTime-DRcCH7xq.chunk.css'),window.OC.filePath('', '', 'dist/NcAvatar-C9d7Wrc8-CeBxkemU.chunk.css'),window.OC.filePath('', '', 'dist/NcUserBubble-vOAXLHB5-C3lGURBU.chunk.mjs'),window.OC.filePath('', '', 'dist/NcUserBubble-vOAXLHB5-DFUmBxeb.chunk.css'),window.OC.filePath('', '', 'dist/activity-C3wf9N73.chunk.mjs'),window.OC.filePath('', '', 'dist/GetComments-DAgltXhH.chunk.mjs'),window.OC.filePath('', '', 'dist/index-595Vk4Ec.chunk.mjs'),window.OC.filePath('', '', 'dist/CommentView-DvpacKXo.chunk.css'),window.OC.filePath('', '', 'dist/NcActionSeparator-Ct2RnclR-Ct2RnclR.chunk.css'),window.OC.filePath('', '', 'dist/comments-ActivityCommentAction-D9gKOujr.chunk.css'),window.OC.filePath('', '', 'dist/ActivityCommentEntry-BL02i_q3.chunk.mjs'),window.OC.filePath('', '', 'dist/comments-ActivityCommentEntry-CVIS8q0P.chunk.css'),window.OC.filePath('', '', 'dist/FilesSidebarTab-CfZ9Rey-.chunk.mjs'),window.OC.filePath('', '', 'dist/NcEmptyContent-B8-90BSI-BIPGjjpl.chunk.mjs'),window.OC.filePath('', '', 'dist/NcEmptyContent-B8-90BSI-CLjlZ-UT.chunk.css'),window.OC.filePath('', '', 'dist/FilesSidebarTab-Czu8KEIy.chunk.css')])))=>i.map(i=>d[i]);
const appName = "nextcloud-ui";
const appVersion = "1.0.0";
import { _ as __vitePreload, e as createApp, d as defineCustomElement } from "./preload-helper-xAe3EUYB.chunk.mjs";
import { r as registerSidebarTab } from "./index-DCPyCjGS.chunk.mjs";
import { t as translate } from "./translation-DoG5ZELJ-2UfAUX2V.chunk.mjs";
import { c as createPinia } from "./pinia-0yhe0wHh.chunk.mjs";
import { l as logger, i as isUsingActivityIntegration } from "./activity-C3wf9N73.chunk.mjs";
import { g as getComments } from "./GetComments-DAgltXhH.chunk.mjs";
import "./public-CKeAb98h.chunk.mjs";
import "./index-rAufP352.chunk.mjs";
import "./index-o76qk6sn.chunk.mjs";
import "./util-BSOXDoOW.chunk.mjs";
import "./index-595Vk4Ec.chunk.mjs";
const MessageReplyText = '<svg xmlns="http://www.w3.org/2000/svg" id="mdi-message-reply-text" viewBox="0 0 24 24"><path d="M18,8H6V6H18V8M18,11H6V9H18V11M18,14H6V12H18V14M22,4A2,2 0 0,0 20,2H4A2,2 0 0,0 2,4V16A2,2 0 0,0 4,18H18L22,22V4Z" /></svg>';
function registerCommentsPlugins() {
  let app;
  window.OCA.Activity.registerSidebarAction({
    mount: async (el, { node, reload }) => {
      const pinia = createPinia();
      if (!app) {
        const { default: ActivityCommentAction } = await __vitePreload(async () => {
          const { default: ActivityCommentAction2 } = await import("./ActivityCommentAction-6WwJFjO5.chunk.mjs");
          return { default: ActivityCommentAction2 };
        }, true ? __vite__mapDeps([0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32,33,34,35,36,37,38]) : void 0, import.meta.url);
        app = createApp(
          ActivityCommentAction,
          {
            reloadCallback: reload,
            resourceId: node.fileid
          }
        );
      }
      app.use(pinia);
      app.mount(el);
      logger.info("Comments plugin mounted in Activity sidebar action", { node });
    },
    unmount: () => {
      app?.unmount();
    }
  });
  window.OCA.Activity.registerSidebarEntries(async ({ node, limit, offset }) => {
    const { data: comments } = await getComments(
      { resourceType: "files", resourceId: node.fileid },
      {
        limit,
        offset: offset ?? 0
      }
    );
    logger.debug("Loaded comments", { node, comments });
    const { default: CommentView } = await __vitePreload(async () => {
      const { default: CommentView2 } = await import("./ActivityCommentEntry-BL02i_q3.chunk.mjs");
      return { default: CommentView2 };
    }, true ? __vite__mapDeps([39,7,8,18,2,19,20,21,5,6,9,10,11,4,12,22,23,24,25,26,27,28,29,13,14,30,31,32,1,3,15,16,17,33,34,35,36,37,40]) : void 0, import.meta.url);
    return comments.map((comment) => ({
      _CommentsViewInstance: void 0,
      timestamp: Date.parse(comment.props?.creationDateTime ?? ""),
      mount(element, { reload }) {
        const app2 = createApp(
          CommentView,
          {
            comment,
            resourceId: node.fileid,
            reloadCallback: reload
          }
        );
        app2.mount(element);
        this._CommentsViewInstance = app2;
      },
      unmount() {
        this._CommentsViewInstance?.unmount();
      }
    }));
  });
  window.OCA.Activity.registerSidebarFilter((activity) => activity.type !== "comments");
  logger.info("Comments plugin registered for Activity sidebar action");
}
const tagName = "comments_files-sidebar-tab";
if (isUsingActivityIntegration()) {
  window.addEventListener("DOMContentLoaded", function() {
    registerCommentsPlugins();
  });
} else {
  registerSidebarTab({
    id: "comments",
    displayName: translate("comments", "Comments"),
    iconSvgInline: MessageReplyText,
    order: 50,
    tagName,
    async onInit() {
      const { default: FilesSidebarTab } = await __vitePreload(async () => {
        const { default: FilesSidebarTab2 } = await import("./FilesSidebarTab-CfZ9Rey-.chunk.mjs").then((n) => n.F);
        return { default: FilesSidebarTab2 };
      }, true ? __vite__mapDeps([41,2,1,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,23,24,20,25,26,27,21,22,28,29,30,42,43,18,19,31,32,33,34,35,36,37,44]) : void 0, import.meta.url);
      const FilesSidebarTabElement = defineCustomElement(FilesSidebarTab, {
        configureApp(app) {
          const pinia = createPinia();
          app.use(pinia);
        },
        shadowRoot: false
      });
      window.customElements.define(tagName, FilesSidebarTabElement);
    }
  });
}
//# sourceMappingURL=comments-comments-tab.mjs.map
