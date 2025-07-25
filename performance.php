<?php
/**
 * Performance Optimization Helper
 * Include this file to enable various performance enhancements
 */

// Enable output compression
if (!ob_get_level() && extension_loaded('zlib') && !headers_sent()) {
    ob_start('ob_gzhandler');
}

// Set performance headers
function set_performance_headers() {
    // Enable browser caching for static assets
    if (preg_match('/\.(css|js|png|jpg|jpeg|gif|ico|svg|woff|woff2)$/i', $_SERVER['REQUEST_URI'])) {
        header('Cache-Control: public, max-age=31536000'); // 1 year
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', filemtime($_SERVER['SCRIPT_FILENAME'])) . ' GMT');
        
        // Add ETag for better caching
        $etag = md5_file($_SERVER['SCRIPT_FILENAME']);
        header('ETag: "' . $etag . '"');
        
        // Check if file is not modified
        if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && $_SERVER['HTTP_IF_NONE_MATCH'] === '"' . $etag . '"') {
            header('HTTP/1.1 304 Not Modified');
            exit;
        }
    }
    
    // Security headers
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    
    // Remove server information
    header_remove('X-Powered-By');
}

// Simple query cache implementation
class SimpleCache {
    private static $cache = [];
    private static $cache_dir = 'cache/';
    
    public static function init() {
        if (!is_dir(self::$cache_dir)) {
            mkdir(self::$cache_dir, 0755, true);
        }
    }
    
    public static function get($key) {
        $file = self::$cache_dir . md5($key) . '.cache';
        
        if (file_exists($file)) {
            $data = unserialize(file_get_contents($file));
            
            // Check if cache is still valid (5 minutes)
            if ($data['expires'] > time()) {
                return $data['value'];
            }
            
            // Remove expired cache
            unlink($file);
        }
        
        return null;
    }
    
    public static function set($key, $value, $ttl = 300) {
        $file = self::$cache_dir . md5($key) . '.cache';
        $data = [
            'value' => $value,
            'expires' => time() + $ttl
        ];
        
        file_put_contents($file, serialize($data), LOCK_EX);
    }
    
    public static function delete($key) {
        $file = self::$cache_dir . md5($key) . '.cache';
        if (file_exists($file)) {
            unlink($file);
        }
    }
    
    public static function clear() {
        $files = glob(self::$cache_dir . '*.cache');
        foreach ($files as $file) {
            unlink($file);
        }
    }
}

// Database query optimization helper
function cached_query($conn, $sql, $params = [], $cache_key = null, $ttl = 300) {
    if ($cache_key === null) {
        $cache_key = md5($sql . serialize($params));
    }
    
    // Try to get from cache first
    $result = SimpleCache::get($cache_key);
    if ($result !== null) {
        return $result;
    }
    
    // Execute query
    $stmt = $conn->prepare($sql);
    if ($params) {
        $types = str_repeat('s', count($params));
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $res = $stmt->get_result();
    
    $data = [];
    while ($row = $res->fetch_assoc()) {
        $data[] = $row;
    }
    
    $stmt->close();
    
    // Cache the result
    SimpleCache::set($cache_key, $data, $ttl);
    
    return $data;
}

// Performance monitoring
class PerformanceMonitor {
    private static $start_time;
    private static $memory_start;
    private static $queries = 0;
    
    public static function start() {
        self::$start_time = microtime(true);
        self::$memory_start = memory_get_usage();
    }
    
    public static function end() {
        $execution_time = microtime(true) - self::$start_time;
        $memory_used = memory_get_usage() - self::$memory_start;
        $memory_peak = memory_get_peak_usage();
        
        // Log performance data (only in development)
        if (defined('DEVELOPMENT') && DEVELOPMENT) {
            error_log(sprintf(
                "Performance: Time: %.4fs | Memory: %s | Peak: %s | Queries: %d",
                $execution_time,
                self::formatBytes($memory_used),
                self::formatBytes($memory_peak),
                self::$queries
            ));
        }
        
        return [
            'time' => $execution_time,
            'memory' => $memory_used,
            'peak_memory' => $memory_peak,
            'queries' => self::$queries
        ];
    }
    
    public static function addQuery() {
        self::$queries++;
    }
    
    private static function formatBytes($size) {
        $units = ['B', 'KB', 'MB', 'GB'];
        $unit = 0;
        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }
        return round($size, 2) . ' ' . $units[$unit];
    }
}

// Initialize cache
SimpleCache::init();

// Start performance monitoring
PerformanceMonitor::start();

// Set headers
set_performance_headers();

// Register shutdown function for cleanup
register_shutdown_function(function() {
    PerformanceMonitor::end();
    
    // Clean expired cache files periodically (1% chance)
    if (rand(1, 100) === 1) {
        SimpleCache::clear();
    }
});

// Optimize MySQL connection for better performance
function optimize_mysql_connection($conn) {
    // Set connection encoding
    $conn->set_charset("utf8mb4");
    
    // Optimize MySQL settings for this session
    $optimizations = [
        "SET SESSION sql_mode = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'",
        "SET SESSION autocommit = 1",
        "SET SESSION query_cache_type = ON",
        "SET SESSION innodb_lock_wait_timeout = 5"
    ];
    
    foreach ($optimizations as $query) {
        $conn->query($query);
    }
}
?>