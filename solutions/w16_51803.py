// Unified sharing module for Nextcloud apps
const SharingManager = {
  // Supported sharing backends
  backends: {
    internal: 'internal',
    federated: 'federated', 
    public: 'public',
    circle: 'circle',
    group: 'group'
  },

  // Initialize sharing capabilities
  init() {
    this.supportedBackends = this.detectSupportedBackends();
    this.shareCache = new Map();
    this.setupEventListeners();
  },

  // Detect which sharing backends are supported by the current app
  detectSupportedBackends() {
    const backends = [];
    
    // Check for internal sharing (users within same instance)
    if (OC.appConfig.core.enableInternalSharing !== false) {
      backends.push(this.backends.internal);
    }

    // Check for federated sharing
    if (OC.appConfig.federation.enabled && OC.appConfig.federation.allowOutgoing) {
      backends.push(this.backends.federated);
    }

    // Check for public link sharing
    if (OC.appConfig.core.enablePublicSharing) {
      backends.push(this.backends.public);
    }

    // Check for circle sharing
    if (OC.appConfig.circles && OC.appConfig.circles.enabled) {
      backends.push(this.backends.circle);
    }

    // Check for group sharing
    if (OC.appConfig.core.enableGroupSharing) {
      backends.push(this.backends.group);
    }

    return backends;
  },

  // Main share method - unified interface
  async share(item, recipients, options = {}) {
    const results = [];
    
    for (const recipient of recipients) {
      try {
        const result = await this.shareToRecipient(item, recipient, options);
        results.push(result);
      } catch (error) {
        console.error(`Failed to share with ${recipient.id}:`, error);
        results.push({ success: false, error, recipient });
      }
    }

    return results;
  },

  // Share to a single recipient using appropriate backend
  async shareToRecipient(item, recipient, options) {
    const backend = this.determineBackend(recipient);
    
    if (!backend) {
      throw new Error(`No suitable sharing backend for recipient type: ${recipient.type}`);
    }

    switch (backend) {
      case this.backends.internal:
        return this.shareInternal(item, recipient, options);
      case this.backends.federated:
        return this.shareFederated(item, recipient, options);
      case this.backends.public:
        return this.sharePublic(item, recipient, options);
      case this.backends.circle:
        return this.shareToCircle(item, recipient, options);
      case this.backends.group:
        return this.shareToGroup(item, recipient, options);
      default:
        throw new Error(`Unknown backend: ${backend}`);
    }
  },

  // Determine which backend to use for a recipient
  determineBackend(recipient) {
    if (recipient.type === 'user') {
      if (recipient.federatedId) {
        return this.backends.federated;
      }
      return this.backends.internal;
    }
    
    if (recipient.type === 'group') {
      return this.backends.group;
    }
    
    if (recipient.type === 'circle') {
      return this.backends.circle;
    }
    
    if (recipient.type === 'public') {
      return this.backends.public;
    }

    return null;
  },

  // Internal sharing (users on same instance)
  async shareInternal(item, recipient, options) {
    const shareData = {
      path: item.path,
      shareWith: recipient.id,
      shareType: OC.Share.SHARE_TYPE_USER,
      permissions: options.permissions || OC.PERMISSION_READ,
      expireDate: options.expireDate || null,
      note: options.note || ''
    };

    const response = await OC.Share.shareItemWithUser(shareData);
    this.cacheShare(response);
    return response;
  },

  // Federated sharing (users on different instances)
  async shareFederated(item, recipient, options) {
    const shareData = {
      path: item.path,
      shareWith: recipient.federatedId,
      shareType: OC.Share.SHARE_TYPE_REMOTE,
      permissions: options.permissions || OC.PERMISSION_READ,
      expireDate: options.expireDate || null,
      remoteId: recipient.remoteId || null
    };

    const response = await OC.Share.shareItemWithRemote(shareData);
    this.cacheShare(response);
    return response;
  },

  // Public link sharing
  async sharePublic(item, recipient, options) {
    const shareData = {
      path: item.path,
      shareType: OC.Share.SHARE_TYPE_LINK,
      permissions: options.permissions || OC.PERMISSION_READ,
      expireDate: options.expireDate || null,
      password: options.password || null,
      label: options.label || ''
    };

    const response = await OC.Share.shareItemAsLink(shareData);
    this.cacheShare(response);
    return response;
  },

  // Circle sharing
  async shareToCircle(item, recipient, options) {
    const shareData = {
      path: item.path,
      shareWith: recipient.circleId,
      shareType: OC.Share.SHARE_TYPE_CIRCLE,
      permissions: options.permissions || OC.PERMISSION_READ,
      expireDate: options.expireDate || null
    };

    const response = await OC.Share.shareItemWithCircle(shareData);
    this.cacheShare(response);
    return response;
  },

  // Group sharing
  async shareToGroup(item, recipient, options) {
    const shareData = {
      path: item.path,
      shareWith: recipient.groupId,
      shareType: OC.Share.SHARE_TYPE_GROUP,
      permissions: options.permissions || OC.PERMISSION_READ,
      expireDate: options.expireDate || null
    };

    const response = await OC.Share.shareItemWithGroup(shareData);
    this.cacheShare(response);
    return response;
  },

  // Cache share for quick access
  cacheShare(shareData) {
    const cacheKey = `${shareData.shareType}:${shareData.shareWith}:${shareData.path}`;
    this.shareCache.set(cacheKey, {
      ...shareData,
      timestamp: Date.now()
    });
  },

  // Get cached share
  getCachedShare(shareType, shareWith, path) {
    const cacheKey = `${shareType}:${shareWith}:${path}`;
    return this.shareCache.get(cacheKey);
  },

  // Remove share
  async unshare(item, recipient) {
    const backend = this.determineBackend(recipient);
    
    if (!backend) {
      throw new Error(`Cannot determine backend for unshare`);
    }

    const shareData = this.getCachedShare(
      this.getShareType(backend),
      recipient.id,
      item.path
    );

    if (!shareData) {
      throw new Error('Share not found in cache');
    }

    await OC.Share.unshare(shareData.id);
    this.shareCache.delete(`${shareData.shareType}:${shareData.shareWith}:${shareData.path}`);
  },

  // Get share type for backend
  getShareType(backend) {
    const typeMap = {
      [this.backends.internal]: OC.Share.SHARE_TYPE_USER,
      [this.backends.federated]: OC.Share.SHARE_TYPE_REMOTE,
      [this.backends.public]: OC.Share.SHARE_TYPE_LINK,
      [this.backends.circle]: OC.Share.SHARE_TYPE_CIRCLE,
      [this.backends.group]: OC.Share.SHARE_TYPE_GROUP
    };
    return typeMap[backend];
  },

  // Setup event listeners for sharing
  setupEventListeners() {
    document.addEventListener('OCA.Sharing:shareCreated', (event) => {
      this.cacheShare(event.detail);
    });

    document.addEventListener('OCA.Sharing:shareRemoved', (event) => {
      const { shareType, shareWith, path } = event.detail;
      this.shareCache.delete(`${shareType}:${shareWith}:${path}`);
    });
  },

  // Get available sharing backends for UI
  getAvailableBackends() {
    return this.supportedBackends.map(backend => ({
      type: backend,
      label: this.getBackendLabel(backend),
      enabled: true
    }));
  },

  // Get human-readable label for backend
  getBackendLabel(backend) {
    const labels = {
      [this.backends.internal]: 'Internal Users',
      [this.backends.federated]: 'Federated Users',
      [this.backends.public]: 'Public Links',
      [this.backends.circle]: 'Circles',
      [this.backends.group]: 'Groups'
    };
    return labels[backend] || backend;
  }
};

// Export for use in other modules
if (typeof module !== 'undefined' && module.exports) {
  module.exports = SharingManager;
} else {
  window.SharingManager = SharingManager;
}
