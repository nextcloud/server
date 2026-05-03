<?php

class Config {
    private $db;
    private $configData = [];
    private $configFile;
    private $writableFields = ['instanceid', 'maintenance', 'version'];

    public function __construct($configFile, $db = null) {
        $this->configFile = $configFile;
        $this->db = $db;
        $this->loadConfig();
    }

    private function loadConfig() {
        if (file_exists($this->configFile)) {
            $this->configData = require $this->configFile;
        }
    }

    public function getValue($key, $default = null) {
        if (in_array($key, $this->writableFields) && $this->db) {
            return $this->getFromDatabase($key) ?? $this->configData[$key] ?? $default;
        }
        return $this->configData[$key] ?? $default;
    }

    public function setValue($key, $value) {
        if (in_array($key, $this->writableFields) && $this->db) {
            $this->setInDatabase($key, $value);
        } else {
            $this->configData[$key] = $value;
            $this->saveToFile();
        }
    }

    private function getFromDatabase($key) {
        try {
            $stmt = $this->db->prepare("SELECT value FROM config WHERE `key` = ?");
            $stmt->execute([$key]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? $row['value'] : null;
        } catch (PDOException $e) {
            // If database is inaccessible, return default for maintenance
            if ($key === 'maintenance') {
                return true;
            }
            return null;
        }
    }

    private function setInDatabase($key, $value) {
        try {
            $stmt = $this->db->prepare("INSERT INTO config (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = ?");
            $stmt->execute([$key, $value, $value]);
        } catch (PDOException $e) {
            // Fallback to file if database fails
            $this->configData[$key] = $value;
            $this->saveToFile();
        }
    }

    private function saveToFile() {
        $content = "<?php\nreturn " . var_export($this->configData, true) . ";\n";
        file_put_contents($this->configFile, $content, LOCK_EX);
    }

    public function isMaintenanceMode() {
        return $this->getValue('maintenance', false);
    }

    public function getInstanceId() {
        $instanceId = $this->getValue('instanceid');
        if (!$instanceId) {
            $instanceId = $this->generateInstanceId();
            $this->setValue('instanceid', $instanceId);
        }
        return $instanceId;
    }

    public function getVersion() {
        return $this->getValue('version', '0.0.0');
    }

    public function setVersion($version) {
        $this->setValue('version', $version);
    }

    private function generateInstanceId() {
        return bin2hex(random_bytes(16));
    }

    public function setMaintenanceMode($enabled) {
        $this->setValue('maintenance', $enabled);
    }
}

// Database setup function
function setupConfigTable($db) {
    $db->exec("CREATE TABLE IF NOT EXISTS config (
        `key` VARCHAR(255) PRIMARY KEY,
        `value` TEXT NOT NULL
    )");
}

// Usage example:
// $db = new PDO('mysql:host=localhost;dbname=test', 'user', 'pass');
// setupConfigTable($db);
// $config = new Config('/path/to/config.php', $db);
// $config->getInstanceId();
// $config->setVersion('1.0.0');
// $config->setMaintenanceMode(true);