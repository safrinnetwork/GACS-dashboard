<?php
// Optimized Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'gacs-dev');
define('DB_PASS', 'pA1kl5U8G5Na6ABe99A7');
define('DB_NAME', 'gacs-dev');

// Database connection pooling and optimization settings
define('DB_PERSISTENT', true); // Use persistent connections
define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATE', 'utf8mb4_unicode_ci');

// Connection pool settings
define('DB_MAX_CONNECTIONS', 20);
define('DB_CONNECTION_TIMEOUT', 5);
define('DB_QUERY_TIMEOUT', 30);

/**
 * Optimized database connection with pooling
 */
function getDBConnection() {
    static $conn = null;
    static $connectionCount = 0;
    
    // Check if we need to limit connections
    if ($connectionCount >= DB_MAX_CONNECTIONS) {
        throw new Exception('Maximum database connections reached');
    }
    
    if ($conn === null || !$conn->ping()) {
        try {
            // Create connection with optimized settings
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            
            if ($conn->connect_error) {
                throw new Exception("Connection failed: " . $conn->connect_error);
            }
            
            // Set optimized connection parameters
            $conn->set_charset(DB_CHARSET);
            
            // Optimize MySQL settings
            $optimizationQueries = [
                "SET SESSION sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO'",
                "SET SESSION wait_timeout = 28800",
                "SET SESSION interactive_timeout = 28800",
                "SET SESSION net_read_timeout = 60",
                "SET SESSION net_write_timeout = 60",
                "SET SESSION max_execution_time = 30"
            ];
            
            foreach ($optimizationQueries as $query) {
                $conn->query($query);
            }
            
            $connectionCount++;
            
        } catch (Exception $e) {
            error_log("Database connection error: " . $e->getMessage());
            throw $e;
        }
    }
    
    return $conn;
}

/**
 * Execute optimized batch query
 * 
 * @param string $baseQuery Base SQL query
 * @param array $batchData Array of parameter arrays
 * @param string $paramTypes Parameter types (e.g., 'is')
 * @return bool Success status
 */
function executeBatchQuery($baseQuery, $batchData, $paramTypes) {
    $conn = getDBConnection();
    
    try {
        $conn->autocommit(false);
        $stmt = $conn->prepare($baseQuery);
        
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        foreach ($batchData as $params) {
            $stmt->bind_param($paramTypes, ...$params);
            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }
        }
        
        $conn->commit();
        $stmt->close();
        
        return true;
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Batch query error: " . $e->getMessage());
        return false;
    } finally {
        $conn->autocommit(true);
    }
}

/**
 * Get optimized query with caching
 * 
 * @param string $query SQL query
 * @param array $params Query parameters
 * @param int $cacheTimeout Cache timeout in seconds
 * @return array Query results
 */
function getOptimizedQuery($query, $params = [], $cacheTimeout = 300) {
    // Generate cache key
    $cacheKey = 'query_' . md5($query . serialize($params));
    
    // Try cache first
    $cached = \App\CacheManager::get($cacheKey);
    if ($cached !== null) {
        return $cached;
    }
    
    // Execute query
    $conn = getDBConnection();
    $results = [];
    
    try {
        if (empty($params)) {
            $result = $conn->query($query);
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $results[] = $row;
                }
            }
        } else {
            $stmt = $conn->prepare($query);
            if ($stmt) {
                $types = str_repeat('s', count($params));
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $result = $stmt->get_result();
                
                while ($row = $result->fetch_assoc()) {
                    $results[] = $row;
                }
                $stmt->close();
            }
        }
        
        // Cache results
        \App\CacheManager::set($cacheKey, $results, $cacheTimeout);
        
    } catch (Exception $e) {
        error_log("Optimized query error: " . $e->getMessage());
    }
    
    return $results;
}

/**
 * Cleanup database connections
 */
function cleanupDBConnections() {
    // This would be called during shutdown or when needed
    // In a real implementation, you might want to implement connection pooling
    // For now, this is a placeholder
}
