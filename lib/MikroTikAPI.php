<?php
namespace App;

use RouterOS\Client;
use RouterOS\Query;

/**
 * MikroTik API Client
 */
class MikroTikAPI {
    private $host;
    private $port;
    private $username;
    private $password;
    private $client;

    public function __construct($host = null, $username = null, $password = null, $port = 8728) {
        $this->host = $host;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
    }

    /**
     * Connect to MikroTik
     */
    public function connect() {
        try {
            $this->client = new Client([
                'host' => $this->host,
                'user' => $this->username,
                'pass' => $this->password,
                'port' => (int)$this->port,
            ]);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Test connection
     */
    public function testConnection() {
        try {
            if ($this->connect()) {
                // Try to get system resource
                $query = new Query('/system/resource/print');
                $response = $this->client->query($query)->read();
                return !empty($response);
            }
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get Netwatch list
     */
    public function getNetwatchList() {
        try {
            if (!$this->connect()) {
                return ['success' => false, 'error' => 'Connection failed'];
            }

            $query = new Query('/tool/netwatch/print');
            $response = $this->client->query($query)->read();

            return ['success' => true, 'data' => $response];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get system resources
     */
    public function getSystemResources() {
        try {
            if (!$this->connect()) {
                return ['success' => false, 'error' => 'Connection failed'];
            }

            $query = new Query('/system/resource/print');
            $response = $this->client->query($query)->read();

            return ['success' => true, 'data' => $response];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get interfaces
     */
    public function getInterfaces() {
        try {
            if (!$this->connect()) {
                return ['success' => false, 'error' => 'Connection failed'];
            }

            $query = new Query('/interface/print');
            $response = $this->client->query($query)->read();

            return ['success' => true, 'data' => $response];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Get netwatch (wrapper for compatibility)
     */
    public function getNetwatch() {
        $result = $this->getNetwatchList();
        return $result['success'] ? $result['data'] : [];
    }
}
