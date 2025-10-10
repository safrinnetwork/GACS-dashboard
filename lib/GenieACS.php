<?php
namespace App;

/**
 * GenieACS API Client
 */
class GenieACS {
    private $host;
    private $port;
    private $username;
    private $password;
    private $baseUrl;

    public function __construct($host = null, $port = 7557, $username = null, $password = null) {
        $this->host = $host;
        $this->port = $port;
        $this->username = $username;
        $this->password = $password;
        $this->baseUrl = "http://{$this->host}:{$this->port}";
    }

    /**
     * Make HTTP request to GenieACS API
     */
    private function request($endpoint, $method = 'GET', $data = null) {
        $url = $this->baseUrl . $endpoint;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        // Add authentication if provided
        if ($this->username && $this->password) {
            curl_setopt($ch, CURLOPT_USERPWD, "{$this->username}:{$this->password}");
        }

        // Set method and data
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            }
        } elseif ($method === 'PUT') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            }
        } elseif ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['success' => false, 'error' => $error];
        }

        return [
            'success' => $httpCode >= 200 && $httpCode < 300,
            'data' => json_decode($response, true),
            'http_code' => $httpCode
        ];
    }

    /**
     * Test connection to GenieACS
     */
    public function testConnection() {
        $result = $this->request('/devices?limit=1');
        return $result['success'];
    }

    /**
     * Get all devices
     */
    public function getDevices($query = []) {
        $queryString = empty($query) ? '' : '?query=' . urlencode(json_encode($query));
        return $this->request('/devices/' . $queryString);
    }

    /**
     * Get device by ID
     */
    public function getDevice($deviceId) {
        $query = ['_id' => $deviceId];
        $result = $this->request('/devices/?query=' . urlencode(json_encode($query)));

        if ($result['success'] && !empty($result['data'])) {
            return ['success' => true, 'data' => $result['data'][0]];
        }

        return ['success' => false, 'error' => 'Device not found'];
    }

    /**
     * Get device parameters
     */
    public function getDeviceParameters($deviceId) {
        return $this->getDevice($deviceId);
    }

    /**
     * Execute task on device
     */
    public function executeTask($deviceId, $taskName, $params = []) {
        $endpoint = "/devices/{$deviceId}/tasks";
        $data = [
            'name' => $taskName
        ];

        if (!empty($params)) {
            $data['parameterValues'] = $params;
        }

        return $this->request($endpoint, 'POST', $data);
    }

    /**
     * Summon device (connection request)
     */
    public function summonDevice($deviceId) {
        // URL encode device ID to handle special characters
        $encodedId = rawurlencode($deviceId);
        $endpoint = "/devices/{$encodedId}/tasks?connection_request";
        return $this->request($endpoint, 'POST');
    }

    /**
     * Reboot device
     */
    public function rebootDevice($deviceId) {
        return $this->executeTask($deviceId, 'reboot');
    }

    /**
     * Get device statistics
     */
    public function getDeviceStats() {
        $devices = $this->getDevices();

        if (!$devices['success']) {
            return ['success' => false, 'error' => 'Failed to fetch devices'];
        }

        $total = count($devices['data']);
        $online = 0;
        $offline = 0;

        foreach ($devices['data'] as $device) {
            // Check last inform time (within last 5 minutes = online)
            $lastInform = isset($device['_lastInform']) ? $device['_lastInform'] : null;

            $isOnline = false;

            if ($lastInform) {
                // Convert ISO 8601 to Unix timestamp
                $lastInformTimestamp = strtotime($lastInform);
                if ($lastInformTimestamp !== false) {
                    // Online if last inform within 5 minutes
                    $isOnline = (time() - $lastInformTimestamp) < 300;
                }
            }

            if ($isOnline) {
                $online++;
            } else {
                $offline++;
            }
        }

        return [
            'success' => true,
            'data' => [
                'total' => $total,
                'online' => $online,
                'offline' => $offline
            ]
        ];
    }

    /**
     * Parse device data for display
     */
    public function parseDeviceData($device) {
        $data = [];

        // Helper function to get nested parameter value
        $getParam = function($path) use ($device) {
            $keys = explode('.', $path);
            $value = $device;

            foreach ($keys as $key) {
                if (isset($value[$key])) {
                    $value = $value[$key];
                } else {
                    return null;
                }
            }

            // GenieACS uses object format with _value field
            if (is_array($value) && isset($value['_value'])) {
                return $value['_value'];
            }

            // Fallback for direct values
            return is_array($value) ? null : $value;
        };

        // Basic info
        $data['device_id'] = $device['_id'] ?? 'N/A';
        $data['serial_number'] = $getParam('_deviceId._SerialNumber') ?? $getParam('InternetGatewayDevice.DeviceInfo.SerialNumber') ?? 'N/A';
        $data['mac_address'] = $getParam('InternetGatewayDevice.LANDevice.1.LANEthernetInterfaceConfig.1.MACAddress') ??
                              $getParam('InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.BSSID') ??
                              $getParam('_deviceId._MACAddress') ?? 'N/A';
        $data['manufacturer'] = $getParam('_deviceId._Manufacturer') ?? $getParam('InternetGatewayDevice.DeviceInfo.Manufacturer') ?? 'N/A';
        $data['oui'] = $getParam('_deviceId._OUI') ?? $getParam('InternetGatewayDevice.DeviceInfo.ManufacturerOUI') ?? 'N/A';
        $data['product_class'] = $getParam('_deviceId._ProductClass') ?? $getParam('InternetGatewayDevice.DeviceInfo.ProductClass') ?? 'N/A';
        $data['hardware_version'] = $getParam('InternetGatewayDevice.DeviceInfo.HardwareVersion') ?? 'N/A';
        $data['software_version'] = $getParam('InternetGatewayDevice.DeviceInfo.SoftwareVersion') ?? 'N/A';

        // Status
        $lastInform = isset($device['_lastInform']) ? $device['_lastInform'] : null;

        if ($lastInform) {
            $lastInformTimestamp = strtotime($lastInform);
            if ($lastInformTimestamp !== false) {
                $data['last_inform'] = date('Y-m-d H:i:s', $lastInformTimestamp);
                $data['status'] = (time() - $lastInformTimestamp) < 300 ? 'Online' : 'Offline';
            } else {
                $data['last_inform'] = 'N/A';
                $data['status'] = 'Offline';
            }
        } else {
            $data['last_inform'] = 'N/A';
            $data['status'] = 'Offline';
        }

        // Network info
        $data['ip_tr069'] = $getParam('InternetGatewayDevice.ManagementServer.ConnectionRequestURL') ??
                           $getParam('Device.ManagementServer.ConnectionRequestURL') ?? 'N/A';
        $data['uptime'] = $getParam('InternetGatewayDevice.DeviceInfo.UpTime') ??
                         $getParam('Device.DeviceInfo.UpTime') ?? 'N/A';

        // WiFi info
        $data['wifi_ssid'] = $getParam('InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.SSID') ??
                            $getParam('Device.WiFi.SSID.1.SSID') ?? 'N/A';
        $data['wifi_password'] = $getParam('InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.KeyPassphrase') ??
                                $getParam('InternetGatewayDevice.LANDevice.1.WLANConfiguration.1.PreSharedKey.1.KeyPassphrase') ??
                                $getParam('Device.WiFi.AccessPoint.1.Security.KeyPassphrase') ?? 'N/A';

        // Optical info
        $rxPower = $getParam('VirtualParameters.RXPower') ??
                   $getParam('InternetGatewayDevice.WANDevice.1.X_CT-COM_EponInterfaceConfig.RXPower') ??
                   $getParam('Device.Optical.Interface.1.RxPower');

        // Convert raw value to dBm if needed
        if ($rxPower !== null && is_numeric($rxPower)) {
            $rxPower = floatval($rxPower);
            if ($rxPower > 100) {
                $rxPower = ($rxPower / 100) - 40;
            }
            $data['rx_power'] = number_format($rxPower, 2);
        } else {
            $data['rx_power'] = $rxPower ?? 'N/A';
        }

        // Temperature
        $temperature = $getParam('VirtualParameters.gettemp') ??
                      $getParam('InternetGatewayDevice.WANDevice.1.X_CT-COM_EponInterfaceConfig.TransceiverTemperature') ??
                      $getParam('VirtualParameters.Temperature') ??
                      $getParam('InternetGatewayDevice.DeviceInfo.Temperature');

        // Convert raw value if needed (> 1000 indicates raw format)
        if ($temperature !== null && is_numeric($temperature)) {
            $temperature = floatval($temperature);
            if ($temperature > 1000) {
                $temperature = $temperature / 256; // Convert from raw to Celsius
            }
            $data['temperature'] = number_format($temperature, 1);
        } else {
            $data['temperature'] = $temperature ?? 'N/A';
        }

        return $data;
    }
}
