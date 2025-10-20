<?php

namespace OCA\UserIprotek;

use OCP\IUserBackend;
use OCP\UserInterface;
use OCP\ILogger;
use OCP\AppFramework\App;
use GuzzleHttp\Client;

class iProtekBackend extends App implements IUserBackend {
    private $apiUrl = '';// 'https://laravel-app.local/api/nextcloud-auth';
    private $logger;
    private $config;
    
    public function __construct() {
        $this->logger = \OC::$server->getLogger();
        parent::__construct('user_iprotek');
        $this->config = include __DIR__ . '/../config/config.php';
        $this->apiUrl = $this->config['iprotek_api_url'] . '/nextcloud-auth';
        /*
        $this->http = new Client([
            'base_uri' => $this->config['laravel_api_url'],
            'timeout'  => 5.0,
        ]);
        */

    }

    public function login($uid, $password) {
        $api = $this->config['laravel_api_url'];
        // ... use Laravel API for login
    }

    /*
    public function checkPassword($uid, $password) {
        $response = $this->http->post('/api/login', [
            'form_params' => [
                'email' => $uid,
                'password' => $password,
            ],
        ]);

        $data = json_decode($response->getBody(), true);
        return isset($data['token']); // success if Laravel returns a token
    }
    */

    public function checkPassword($uid, $password) {
        $payload = json_encode([
            'email' => $uid,
            'password' => $password,
        ]);

        $ch = curl_init($this->apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($status == 200) {
            $result = json_decode($response, true);
            if (!empty($result['success']) && $result['success'] === true) {
                return $uid; // Authenticated
            }
        }

        $this->logger->warning("Laravel auth failed for $uid, response: $response");
        return false;
    }

    public function userExists($uid) {
        // Optional: check via Laravel API
        return true;
    }

    public function getDisplayName($uid) {
        return ucfirst(explode('@', $uid)[0]);
    }

    public function getUsers($search = '', $limit = 10, $offset = 0) {
        // Optional: call Laravel /api/users endpoint
        return [];
    }

    public function deleteUser($uid) { return false; }
    public function setPassword($uid, $password) { return false; }
}