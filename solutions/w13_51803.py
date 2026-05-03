// UnifiedSharingManager.js
class UnifiedSharingManager {
    constructor(apps) {
        this.apps = apps;
        this.sharingHandlers = new Map();
    }

    registerApp(appName, shareHandler) {
        this.sharingHandlers.set(appName, shareHandler);
    }

    async shareContent(content, targetApps = []) {
        const results = [];
        
        for (const app of targetApps) {
            if (this.sharingHandlers.has(app)) {
                try {
                    const handler = this.sharingHandlers.get(app);
                    const result = await handler(content);
                    results.push({ app, success: true, result });
                } catch (error) {
                    results.push({ app, success: false, error: error.message });
                }
            } else {
                results.push({ app, success: false, error: 'App not registered' });
            }
        }
        
        return results;
    }

    getSupportedApps() {
        return Array.from(this.sharingHandlers.keys());
    }
}

// Example usage with Nextcloud apps
const sharingManager = new UnifiedSharingManager();

// Register sharing handlers for different apps
sharingManager.registerApp('files', async (content) => {
    // Implementation for file sharing
    return { shared: true, path: `/shared/${content.name}` };
});

sharingManager.registerApp('contacts', async (content) => {
    // Implementation for contact sharing
    return { shared: true, contactId: content.id };
});

sharingManager.registerApp('calendar', async (content) => {
    // Implementation for calendar event sharing
    return { shared: true, eventId: content.eventId };
});

// Export for use in Nextcloud
export default UnifiedSharingManager;
