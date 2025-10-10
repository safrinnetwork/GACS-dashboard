<?php
namespace App;

/**
 * Response Compression Helper
 * Provides PHP-level compression for API responses
 */
class ResponseCompression {
    
    /**
     * Enable compression if supported
     */
    public static function enable() {
        if (!ob_start('ob_gzhandler')) {
            ob_start();
        }
    }
    
    /**
     * Compress JSON response
     * 
     * @param array $data Response data
     * @param int $options JSON options
     * @return string Compressed JSON
     */
    public static function json($data, $options = JSON_UNESCAPED_UNICODE) {
        $json = json_encode($data, $options);
        
        // Compress if possible
        if (function_exists('gzencode') && strlen($json) > 1024) {
            $compressed = gzencode($json, 6); // Compression level 6 (good balance)
            
            if ($compressed !== false && strlen($compressed) < strlen($json)) {
                header('Content-Encoding: gzip');
                header('Content-Length: ' . strlen($compressed));
                return $compressed;
            }
        }
        
        return $json;
    }
    
    /**
     * Compress HTML response
     * 
     * @param string $html HTML content
     * @return string Compressed HTML
     */
    public static function html($html) {
        // Remove unnecessary whitespace
        $html = preg_replace('/\s+/', ' ', $html);
        $html = preg_replace('/>\s+</', '><', $html);
        
        // Compress if possible
        if (function_exists('gzencode') && strlen($html) > 1024) {
            $compressed = gzencode($html, 6);
            
            if ($compressed !== false && strlen($compressed) < strlen($html)) {
                header('Content-Encoding: gzip');
                header('Content-Length: ' . strlen($compressed));
                return $compressed;
            }
        }
        
        return $html;
    }
    
    /**
     * Check if client supports compression
     * 
     * @return bool True if gzip supported
     */
    public static function isSupported() {
        if (!isset($_SERVER['HTTP_ACCEPT_ENCODING'])) {
            return false;
        }
        
        return strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false;
    }
    
    /**
     * Get compression ratio
     * 
     * @param string $original Original content
     * @param string $compressed Compressed content
     * @return float Compression ratio (0-1)
     */
    public static function getRatio($original, $compressed) {
        if (strlen($original) == 0) {
            return 0;
        }
        
        return 1 - (strlen($compressed) / strlen($original));
    }
    
    /**
     * Add compression headers
     */
    public static function addHeaders() {
        if (self::isSupported()) {
            header('Vary: Accept-Encoding');
            
            // Add compression info header (for debugging)
            if (defined('DEBUG') && DEBUG) {
                header('X-Compression-Info: gzip-enabled');
            }
        }
    }
}
