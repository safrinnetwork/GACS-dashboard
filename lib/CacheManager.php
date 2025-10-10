<?php
namespace App;

/**
 * Simple Cache Manager for GACS Dashboard
 * Provides basic caching functionality to reduce database and API calls
 */
class CacheManager {
    
    private static $cache = [];
    private static $cacheTimeouts = [];
    private static $defaultTimeout = 300; // 5 minutes
    
    /**
     * Get cached data
     * 
     * @param string $key Cache key
     * @return mixed Cached data or null if not found/expired
     */
    public static function get($key) {
        // Check if cache exists and hasn't expired
        if (isset(self::$cache[$key]) && 
            isset(self::$cacheTimeouts[$key]) && 
            time() < self::$cacheTimeouts[$key]) {
            return self::$cache[$key];
        }
        
        // Remove expired cache
        if (isset(self::$cache[$key])) {
            unset(self::$cache[$key]);
            unset(self::$cacheTimeouts[$key]);
        }
        
        return null;
    }
    
    /**
     * Set cached data
     * 
     * @param string $key Cache key
     * @param mixed $data Data to cache
     * @param int $timeout Timeout in seconds (optional)
     */
    public static function set($key, $data, $timeout = null) {
        $timeout = $timeout ?? self::$defaultTimeout;
        
        self::$cache[$key] = $data;
        self::$cacheTimeouts[$key] = time() + $timeout;
    }
    
    /**
     * Delete cached data
     * 
     * @param string $key Cache key
     */
    public static function delete($key) {
        unset(self::$cache[$key]);
        unset(self::$cacheTimeouts[$key]);
    }
    
    /**
     * Clear all cache
     */
    public static function clear() {
        self::$cache = [];
        self::$cacheTimeouts = [];
    }
    
    /**
     * Get cache with callback (cache-aside pattern)
     * 
     * @param string $key Cache key
     * @param callable $callback Function to generate data if not cached
     * @param int $timeout Timeout in seconds (optional)
     * @return mixed Cached or generated data
     */
    public static function remember($key, $callback, $timeout = null) {
        $data = self::get($key);
        
        if ($data === null) {
            $data = $callback();
            self::set($key, $data, $timeout);
        }
        
        return $data;
    }
    
    /**
     * Get or set cache with database query
     * 
     * @param string $key Cache key
     * @param string $query SQL query
     * @param array $params Query parameters
     * @param int $timeout Timeout in seconds (optional)
     * @return array Query results
     */
    public static function rememberQuery($key, $query, $params = [], $timeout = null) {
        return self::remember($key, function() use ($query, $params) {
            $conn = \getDBConnection();
            
            if (empty($params)) {
                $result = $conn->query($query);
                $data = [];
                while ($row = $result->fetch_assoc()) {
                    $data[] = $row;
                }
                return $data;
            } else {
                $stmt = $conn->prepare($query);
                if (count($params) > 0) {
                    $types = str_repeat('s', count($params));
                    $stmt->bind_param($types, ...$params);
                }
                $stmt->execute();
                $result = $stmt->get_result();
                $data = [];
                while ($row = $result->fetch_assoc()) {
                    $data[] = $row;
                }
                return $data;
            }
        }, $timeout);
    }
    
    /**
     * Invalidate cache by pattern
     * 
     * @param string $pattern Cache key pattern (supports wildcards)
     */
    public static function invalidatePattern($pattern) {
        $pattern = str_replace('*', '.*', $pattern);
        $pattern = '/^' . $pattern . '$/';
        
        foreach (array_keys(self::$cache) as $key) {
            if (preg_match($pattern, $key)) {
                self::delete($key);
            }
        }
    }
    
    /**
     * Get cache statistics
     * 
     * @return array Cache statistics
     */
    public static function getStats() {
        $total = count(self::$cache);
        $expired = 0;
        
        foreach (self::$cacheTimeouts as $timeout) {
            if (time() >= $timeout) {
                $expired++;
            }
        }
        
        return [
            'total_entries' => $total,
            'expired_entries' => $expired,
            'active_entries' => $total - $expired,
            'memory_usage' => memory_get_usage(true)
        ];
    }
}
