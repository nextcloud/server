const appName = "nextcloud-ui";
const appVersion = "1.0.0";
import { l as logger } from "./logger-CE4VDfGL.chunk.mjs";
import "./index-rAufP352.chunk.mjs";
/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
class AuthMechanism {
  #registeredAuthMechanisms = /* @__PURE__ */ new Map();
  /**
   * Register a custom auth mechanism handler
   *
   * @param authMechanism - The auth mechanism to register
   */
  registerHandler(authMechanism) {
    if (this.#registeredAuthMechanisms.has(authMechanism.id)) {
      logger.warn(`Auth mechanism handler with id '${authMechanism.id}' is already registered`);
    }
    this.#registeredAuthMechanisms.set(authMechanism.id, authMechanism);
  }
  /**
   * Get the handler for a given auth mechanism
   *
   * @param authMechanism - The auth mechanism to get the handler for
   */
  getHandler(authMechanism) {
    return this.#registeredAuthMechanisms.values().find((handler) => handler.enabled(authMechanism));
  }
}
/*!
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
window.OCA.FilesExternal ??= {};
window.OCA.FilesExternal.AuthMechanism = new AuthMechanism();
//# sourceMappingURL=files_external-init_settings.mjs.map
