<?php
// Compression Configuration for GACS Dashboard

// Enable compression globally
define('ENABLE_COMPRESSION', true);

// Compression levels (1-9, where 6 is optimal balance)
define('COMPRESSION_LEVEL', 6);

// Minimum file size to compress (bytes)
define('MIN_COMPRESS_SIZE', 1024);

// Compression statistics
class CompressionStats {
    private static $stats = [
        'requests' => 0,
        'compressed' => 0,
        'bytes_saved' => 0,
        'total_original_size' => 0,
        'total_compressed_size' => 0
    ];
    
    public static function record($originalSize, $compressedSize) {
        self::$stats['requests']++;
        self::$stats['total_original_size'] += $originalSize;
        self::$stats['total_compressed_size'] += $compressedSize;
        
        if ($compressedSize < $originalSize) {
            self::$stats['compressed']++;
            self::$stats['bytes_saved'] += ($originalSize - $compressedSize);
        }
    }
    
    public static function getStats() {
        $stats = self::$stats;
        
        if ($stats['requests'] > 0) {
            $stats['compression_ratio'] = ($stats['total_original_size'] - $stats['total_compressed_size']) / $stats['total_original_size'];
            $stats['compression_rate'] = ($stats['compressed'] / $stats['requests']) * 100;
        } else {
            $stats['compression_ratio'] = 0;
            $stats['compression_rate'] = 0;
        }
        
        return $stats;
    }
    
    public static function reset() {
        self::$stats = [
            'requests' => 0,
            'compressed' => 0,
            'bytes_saved' => 0,
            'total_original_size' => 0,
            'total_compressed_size' => 0
        ];
    }
}

// Auto-enable compression if supported
if (ENABLE_COMPRESSION && function_exists('ob_gzhandler')) {
    // Check if client supports gzip
    $acceptEncoding = $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '';
    
    if (strpos($acceptEncoding, 'gzip') !== false) {
        ob_start('ob_gzhandler');
    } else {
        ob_start();
    }
} else {
    ob_start();
}
