const appName = "nextcloud-ui";
const appVersion = "1.0.0";
import { a as getCurrentUser, b as generateUrl } from "./index-rAufP352.chunk.mjs";
import { c as cancelableClient } from "./index-D5H5XMHa.chunk.mjs";
import { s as showWarning } from "./index-C1xmmKTZ-kBgT3zMc.chunk.mjs";
import "./preload-helper-xAe3EUYB.chunk.mjs";
import "./util-BSOXDoOW.chunk.mjs";
import "./NcDialog-BG9t4Psg-IlLJVAz0.chunk.mjs";
import "./NcModal-DHryP_87-Da4dXMUU.chunk.mjs";
import "./ArrowRight-BC77f5L9.chunk.mjs";
import "./Web-BOM4en5n.chunk.mjs";
import "./translation-DoG5ZELJ-2UfAUX2V.chunk.mjs";
import "./index-o76qk6sn.chunk.mjs";
import "./TrashCanOutline-DgEtyFGH.chunk.mjs";
import "./mdi-BGU2G5q5.chunk.mjs";
/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
window.addEventListener("DOMContentLoaded", async function() {
  if (getCurrentUser() === null) {
    return;
  }
  const { data } = await cancelableClient.get(generateUrl("/apps/encryption/ajax/getStatus"));
  if (data.status === "interactionNeeded") {
    showWarning(data.data.message);
  }
});
//# sourceMappingURL=encryption-encryption.mjs.map
