<?php
/**
 * Configuration manager that moves writable fields to database
 * and makes config.php read-only after initial setup
 */

class ConfigManager {
    private $configFile;
    private $db;
    private $configData;
    
    public function __construct($configFile, $dbConnection = null) {
        $this->configFile = $configFile;
        $this->db = $dbConnection;
        $this->loadConfig();
    }
    
    private function loadConfig() {
        if (file_exists($this->configFile)) {
            $this->configData = require $this->configFile;
        } else {
            $this->configData = [];
        }
    }
    
    public function getValue($key, $default = null) {
        // Check database first for dynamic values
        $dbKeys = ['instanceid', 'version', 'maintenance'];
        
        if (in_array($key, $dbKeys) && $this->db) {
            $dbValue = $this->getFromDatabase($key);
            if ($dbValue !== null) {
                return $dbValue;
            }
        }
        
        // Fall back to config file
        return isset($this->configData[$key]) ? $this->configData[$key] : $default;
    }
    
    public function setValue($key, $value) {
        $dbKeys = ['instanceid', 'version', 'maintenance'];
        
        if (in_array($key, $dbKeys) && $this->db) {
            $this->saveToDatabase($key, $value);
        } else {
            $this->configData[$key] = $value;
            $this->saveConfigFile();
        }
    }
    
    private function getFromDatabase($key) {
        try {
            $stmt = $this->db->prepare("SELECT config_value FROM app_config WHERE config_key = ?");
            $stmt->execute([$key]);
            $result = $stmt->fetchColumn();
            
            if ($result !== false) {
                // Handle maintenance mode - if DB is accessible, return actual value
                if ($key === 'maintenance') {
                    return (bool)$result;
                }
                return $result;
            }
        } catch (Exception $e) {
            // If database is inaccessible, assume maintenance mode
            if ($key === 'maintenance') {
                return true;
            }
        }
        
        return null;
    }
    
    private function saveToDatabase($key, $value) {
        try {
            $stmt = $this->db->prepare(
                "INSERT INTO app_config (config_key, config_value) VALUES (?, ?) 
                 ON DUPLICATE KEY UPDATE config_value = ?"
            );
            $stmt->execute([$key, $value, $value]);
        } catch (Exception $e) {
            // Fall back to config file if database fails
            $this->configData[$key] = $value;
            $this->saveConfigFile();
        }
    }
    
    private function saveConfigFile() {
        $content = "<?php\nreturn " . var_export($this->configData, true) . ";\n";
        
        // Only write if file is writable (initial setup)
        if (!file_exists($this->configFile) || is_writable($this->configFile)) {
            file_put_contents($this->configFile, $content, LOCK_EX);
        }
    }
    
    public function isInstalled() {
        $instanceId = $this->getValue('instanceid');
        return !empty($instanceId);
    }
    
    public function getMaintenanceMode() {
        return $this->getValue('maintenance', false);
    }
    
    public function setMaintenanceMode($enabled) {
        $this->setValue('maintenance', $enabled);
    }
}

// Database setup for config table
function setupConfigTable($db) {
    $sql = "CREATE TABLE IF NOT EXISTS app_config (
        config_key VARCHAR(64) PRIMARY KEY,
        config_value TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $db->exec($sql);
    
    // Migrate existing values from config.php if needed
    $configFile = __DIR__ . '/config.php';
    if (file_exists($configFile)) {
        $config = require $configFile;
        $migrateKeys = ['instanceid', 'version', 'maintenance'];
        
        foreach ($migrateKeys as $key) {
            if (isset($config[$key])) {
                $stmt = $db->prepare(
                    "INSERT IGNORE INTO app_config (config_key, config_value) VALUES (?, ?)"
                );
                $stmt->execute([$key, $config[$key]]);
            }
        }
    }
}

// Usage example:
// $db = new PDO('mysql:host=localhost;dbname=myapp', 'user', 'pass');
// setupConfigTable($db);
// $config = new ConfigManager(__DIR__ . '/config.php', $db);
// 
// // Get values
// $instanceId = $config->getValue('instanceid');
// $maintenance = $config->getMaintenanceMode();
// 
// // Set values
// $config->setValue('maintenance', true);
// $config->setValue('version', '1.2.3');
