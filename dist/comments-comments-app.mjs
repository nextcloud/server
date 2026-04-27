const appName = "nextcloud-ui";
const appVersion = "1.0.0";
import { l as logger } from "./activity-C3wf9N73.chunk.mjs";
import { a as translatePlural, t as translate } from "./translation-DoG5ZELJ-2UfAUX2V.chunk.mjs";
import { c as createPinia } from "./pinia-0yhe0wHh.chunk.mjs";
import { e as createApp } from "./preload-helper-xAe3EUYB.chunk.mjs";
import { C as CommentsApp } from "./FilesSidebarTab-CfZ9Rey-.chunk.mjs";
import "./index-rAufP352.chunk.mjs";
import "./index-o76qk6sn.chunk.mjs";
import "./util-BSOXDoOW.chunk.mjs";
import "./index-C1xmmKTZ-kBgT3zMc.chunk.mjs";
import "./NcDialog-BG9t4Psg-IlLJVAz0.chunk.mjs";
import "./NcModal-DHryP_87-Da4dXMUU.chunk.mjs";
import "./ArrowRight-BC77f5L9.chunk.mjs";
import "./Web-BOM4en5n.chunk.mjs";
import "./TrashCanOutline-DgEtyFGH.chunk.mjs";
import "./mdi-BGU2G5q5.chunk.mjs";
import "./NcAvatar-C9d7Wrc8-C3hNgT1e.chunk.mjs";
import "./index-D5H5XMHa.chunk.mjs";
import "./colors-BHGKZFDI-C0-WujoK.chunk.mjs";
import "./NcUserStatusIcon-XiwrgeCm-DTG2zfIi.chunk.mjs";
import "./PencilOutline-BMYBdzdS.chunk.mjs";
import "./NcDateTime.vue_vue_type_script_setup_true_lang-BhB8yA4U-DXOBfuGJ.chunk.mjs";
import "./NcEmptyContent-B8-90BSI-BIPGjjpl.chunk.mjs";
import "./CommentView-DcG_o6xO.chunk.mjs";
/* empty css                                           */
import "./NcUserBubble-vOAXLHB5-C3lGURBU.chunk.mjs";
import "./GetComments-DAgltXhH.chunk.mjs";
import "./index-595Vk4Ec.chunk.mjs";
/*!
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
class CommentInstance {
  app;
  instance;
  /**
   * Initialize a new Comments instance for the desired type
   *
   * @param resourceType - The comments endpoint type
   * @param options - The vue options (props, parent, el...)
   */
  constructor(resourceType = "files", options = {}) {
    const pinia = createPinia();
    this.app = createApp(
      CommentsApp,
      {
        ...options.propsData ?? {},
        ...options.props ?? {},
        resourceType
      }
    );
    this.app.mixin({
      data() {
        return {
          logger
        };
      },
      methods: {
        t: translate,
        n: translatePlural
      }
    });
    this.app.use(pinia);
    if (options.el) {
      this.instance = this.app.mount(options.el);
    }
  }
  /**
   * Mount the Comments instance to a new element.
   *
   * @param el - The element to mount the instance on
   */
  $mount(el) {
    if (this.instance) {
      this.app.unmount();
    }
    this.instance = this.app.mount(el);
  }
  /**
   * Unmount the Comments instance from the DOM and destroy it.
   */
  $unmount() {
    this.app.unmount();
    this.instance = void 0;
  }
  /**
   * Update the current resource id.
   *
   * @param id - The new resource id to load the comments for
   */
  update(id) {
    if (this.instance) {
      this.instance.update(id);
    }
  }
}
if (window.OCA && !window.OCA.Comments) {
  Object.assign(window.OCA, { Comments: {} });
}
Object.assign(window.OCA.Comments, { View: CommentInstance });
logger.debug("OCA.Comments.View initialized");
//# sourceMappingURL=comments-comments-app.mjs.map
