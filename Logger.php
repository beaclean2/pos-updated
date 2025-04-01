<?php
/**
 * Logger Class for POS System
 * Handles logging of orders, payments, and system events
 */

class Logger {
    // Log levels
    const DEBUG = 100;
    const INFO = 200;
    const NOTICE = 300;
    const WARNING = 400;
    const ERROR = 500;
    const CRITICAL = 600;
    const ALERT = 700;
    const EMERGENCY = 800;
    
    // Log level names
    private $levelNames = [
        self::DEBUG => 'DEBUG',
        self::INFO => 'INFO',
        self::NOTICE => 'NOTICE',
        self::WARNING => 'WARNING',
        self::ERROR => 'ERROR',
        self::CRITICAL => 'CRITICAL',
        self::ALERT => 'ALERT',
        self::EMERGENCY => 'EMERGENCY'
    ];
    
    // Log file path
    private $logPath = 'logs/';
    
    // Default log file
    private $defaultLogFile = 'pos_system.log';
    
    /**
     * Constructor - creates log directory if it doesn't exist
     */
    public function __construct() {
        if (!is_dir($this->logPath)) {
            if (!mkdir($this->logPath, 0755, true)) {
                // Fall back to system temp directory
                $this->logPath = sys_get_temp_dir() . '/pos_logs/';
                if (!is_dir($this->logPath)) {
                    mkdir($this->logPath, 0755, true);
                }
            }
        }
    }
    
    /**
     * Write log message
     * @param int $level Log level
     * @param string $message Log message
     * @param array $context Additional context data
     * @param string $file Optional specific log file
     */
    public function log($level, $message, array $context = [], $file = null) {
        // Get log level name
        $levelName = isset($this->levelNames[$level]) ? $this->levelNames[$level] : 'UNKNOWN';
        
        // Format timestamp
        $timestamp = date('Y-m-d H:i:s');
        
        // Format context data
        $contextJson = !empty($context) ? json_encode($context) : '{}';
        
        // Format log line
        $logLine = sprintf(
            "[%s] [%s] %s %s\n",
            $timestamp,
            $levelName,
            $message,
            $contextJson
        );
        
        // Determine log file
        $logFile = $file ?: $this->defaultLogFile;
        $logFilePath = $this->logPath . $logFile;
        
        // Write to log file
        file_put_contents($logFilePath, $logLine, FILE_APPEND);
        
        // If error or higher, also log to error log
        if ($level >= self::ERROR) {
            error_log($logLine);
        }
    }
    
    /**
     * Log debug message
     * @param string $message Log message
     * @param array $context Additional context data
     */
    public function debug($message, array $context = []) {
        $this->log(self::DEBUG, $message, $context);
    }
    
    /**
     * Log info message
     * @param string $message Log message
     * @param array $context Additional context data
     */
    public function info($message, array $context = []) {
        $this->log(self::INFO, $message, $context);
    }
    
    /**
     * Log notice message
     * @param string $message Log message
     * @param array $context Additional context data
     */
    public function notice($message, array $context = []) {
        $this->log(self::NOTICE, $message, $context);
    }
    
    /**
     * Log warning message
     * @param string $message Log message
     * @param array $context Additional context data
     */
    public function warning($message, array $context = []) {
        $this->log(self::WARNING, $message, $context);
    }
    
    /**
     * Log error message
     * @param string $message Log message
     * @param array $context Additional context data
     */
    public function error($message, array $context = []) {
        $this->log(self::ERROR, $message, $context);
    }
    
    /**
     * Log critical message
     * @param string $message Log message
     * @param array $context Additional context data
     */
    public function critical($message, array $context = []) {
        $this->log(self::CRITICAL, $message, $context);
    }
    
    /**
     * Log alert message
     * @param string $message Log message
     * @param array $context Additional context data
     */
    public function alert($message, array $context = []) {
        $this->log(self::ALERT, $message, $context);
    }
    
    /**
     * Log emergency message
     * @param string $message Log message
     * @param array $context Additional context data
     */
    public function emergency($message, array $context = []) {
        $this->log(self::EMERGENCY, $message, $context);
    }
    
    /**
     * Log order information
     * @param string $orderNumber Order number
     * @param array $orderData Order data
     */
    public function logOrder($orderNumber, array $orderData) {
        $this->log(
            self::INFO,
            "Order processed: {$orderNumber}",
            $orderData,
            'orders.log'
        );
    }
    
    /**
     * Log payment information
     * @param string $orderNumber Order number
     * @param array $paymentData Payment data
     */
    public function logPayment($orderNumber, array $paymentData) {
        $this->log(
            self::INFO,
            "Payment processed for order: {$orderNumber}",
            $paymentData,
            'payments.log'
        );
    }
    
    /**
     * Log user activity
     * @param int $userId User ID
     * @param string $action Action performed
     * @param array $actionData Action details
     */
    public function logUserActivity($userId, $action, array $actionData = []) {
        $context = array_merge(['user_id' => $userId, 'action' => $action], $actionData);
        $this->log(
            self::INFO,
            "User activity: {$action}",
            $context,
            'user_activity.log'
        );
    }
}
?>