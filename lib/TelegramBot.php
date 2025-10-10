<?php
namespace App;

/**
 * Telegram Bot Client
 */
class TelegramBot {
    private $botToken;
    private $chatId;
    private $apiUrl;

    public function __construct($botToken = null, $chatId = null) {
        $this->botToken = $botToken;
        $this->chatId = $chatId;
        $this->apiUrl = "https://api.telegram.org/bot{$this->botToken}";
    }

    /**
     * Make API request
     */
    private function request($method, $params = []) {
        $url = $this->apiUrl . '/' . $method;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            return ['ok' => false, 'error' => $error];
        }

        return json_decode($response, true);
    }

    /**
     * Test connection
     */
    public function testConnection() {
        $result = $this->request('getMe');
        return isset($result['ok']) && $result['ok'] === true;
    }

    /**
     * Send message
     */
    public function sendMessage($message, $chatId = null) {
        $targetChatId = $chatId ?? $this->chatId;

        $params = [
            'chat_id' => $targetChatId,
            'text' => $message,
            'parse_mode' => 'HTML'
        ];

        return $this->request('sendMessage', $params);
    }

    /**
     * Send device status notification
     */
    public function sendDeviceStatus($deviceId, $status, $deviceInfo = []) {
        $statusEmoji = $status === 'online' ? '🟢' : '🔴';
        $statusText = $status === 'online' ? 'ONLINE' : 'OFFLINE';

        $message = "{$statusEmoji} <b>Device Status: {$statusText}</b>\n\n";
        $message .= "Device ID: <code>{$deviceId}</code>\n";

        if (!empty($deviceInfo)) {
            if (isset($deviceInfo['serial_number'])) {
                $message .= "Serial Number: <code>{$deviceInfo['serial_number']}</code>\n";
            }
            if (isset($deviceInfo['ip_tr069'])) {
                $message .= "IP TR069: <code>{$deviceInfo['ip_tr069']}</code>\n";
            }
            if (isset($deviceInfo['customer_name'])) {
                $message .= "Customer: <b>{$deviceInfo['customer_name']}</b>\n";
            }
        }

        $message .= "\nTime: " . date('Y-m-d H:i:s');

        return $this->sendMessage($message);
    }

    /**
     * Get updates (for interactive bot)
     */
    public function getUpdates($offset = 0) {
        $params = ['offset' => $offset];
        return $this->request('getUpdates', $params);
    }

    /**
     * Process command
     */
    public function processCommand($command, $chatId) {
        $parts = explode(' ', $command);
        $cmd = strtolower($parts[0]);

        switch ($cmd) {
            case '/start':
                return $this->sendMessage("🤖 <b>GACS Dashboard Bot</b>\n\nAvailable commands:\n/status <device_id> - Check device status\n/list - List all devices\n/summon <device_id> - Summon device", $chatId);

            case '/status':
                if (isset($parts[1])) {
                    $deviceId = $parts[1];
                    // This will be implemented in the bot handler
                    return ['command' => 'status', 'device_id' => $deviceId, 'chat_id' => $chatId];
                }
                return $this->sendMessage("Usage: /status <device_id>", $chatId);

            case '/list':
                return ['command' => 'list', 'chat_id' => $chatId];

            case '/summon':
                if (isset($parts[1])) {
                    $deviceId = $parts[1];
                    return ['command' => 'summon', 'device_id' => $deviceId, 'chat_id' => $chatId];
                }
                return $this->sendMessage("Usage: /summon <device_id>", $chatId);

            default:
                return $this->sendMessage("Unknown command. Type /start for help.", $chatId);
        }
    }
}
