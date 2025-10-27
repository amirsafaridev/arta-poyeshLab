<?php
/**
 * Logger Class for System Logs
 * 
 * @package ArtaPoyeshLab
 * @since 1.0.0
 */

namespace APL\Classes;

if (!defined('ABSPATH')) {
    exit;
}

class APL_Logger {
    
    private $log_key = 'apl_system_logs';
    private $max_logs = 50;
    
    /**
     * Add log entry
     * 
     * @param string $type
     * @param string $mobile_number
     * @param string $status
     * @param string $message
     * @param array $extra_data
     */
    public function add_log($type, $mobile_number, $status, $message, $extra_data = array()) {
        $logs = $this->get_logs();
        
        $log_entry = array(
            'id' => uniqid(),
            'timestamp' => current_time('timestamp'),
            'date' => current_time('Y-m-d H:i:s'),
            'type' => $type,
            'mobile_number' => $mobile_number,
            'status' => $status,
            'message' => $message,
            'extra_data' => $extra_data
        );
        
        // Add to beginning of array
        array_unshift($logs, $log_entry);
        
        // Keep only max_logs entries
        if (count($logs) > $this->max_logs) {
            $logs = array_slice($logs, 0, $this->max_logs);
        }
        
        update_option($this->log_key, $logs);
    }
    
    /**
     * Get all logs
     * 
     * @return array
     */
    public function get_logs() {
        $logs = get_option($this->log_key, array());
        return is_array($logs) ? $logs : array();
    }
    
    /**
     * Get logs by type
     * 
     * @param string $type
     * @return array
     */
    public function get_logs_by_type($type) {
        $all_logs = $this->get_logs();
        return array_filter($all_logs, function($log) use ($type) {
            return $log['type'] === $type;
        });
    }
    
    /**
     * Get logs by mobile number
     * 
     * @param string $mobile_number
     * @return array
     */
    public function get_logs_by_mobile($mobile_number) {
        $all_logs = $this->get_logs();
        return array_filter($all_logs, function($log) use ($mobile_number) {
            return $log['mobile_number'] === $mobile_number;
        });
    }
    
    /**
     * Clear all logs
     */
    public function clear_logs() {
        delete_option($this->log_key);
    }
    
    /**
     * Get log statistics
     * 
     * @return array
     */
    public function get_statistics() {
        $logs = $this->get_logs();
        $stats = array(
            'total' => count($logs),
            'by_type' => array(),
            'by_status' => array(),
            'today' => 0,
            'this_week' => 0,
            'this_month' => 0
        );
        
        $today = current_time('Y-m-d');
        $week_start = date('Y-m-d', strtotime('monday this week'));
        $month_start = current_time('Y-m-01');
        
        foreach ($logs as $log) {
            // Count by type
            if (!isset($stats['by_type'][$log['type']])) {
                $stats['by_type'][$log['type']] = 0;
            }
            $stats['by_type'][$log['type']]++;
            
            // Count by status
            if (!isset($stats['by_status'][$log['status']])) {
                $stats['by_status'][$log['status']] = 0;
            }
            $stats['by_status'][$log['status']]++;
            
            // Count by date
            $log_date = date('Y-m-d', $log['timestamp']);
            if ($log_date === $today) {
                $stats['today']++;
            }
            if ($log_date >= $week_start) {
                $stats['this_week']++;
            }
            if ($log_date >= $month_start) {
                $stats['this_month']++;
            }
        }
        
        return $stats;
    }
    
    /**
     * Log SMS sent
     * 
     * @param string $mobile_number
     * @param string $status
     * @param string $message
     * @param array $extra_data
     */
    public function log_sms_sent($mobile_number, $status, $message, $extra_data = array()) {
        $this->add_log('sms_sent', $mobile_number, $status, $message, $extra_data);
    }
    
    /**
     * Log login attempt
     * 
     * @param string $mobile_number
     * @param string $status
     * @param string $message
     * @param array $extra_data
     */
    public function log_login_attempt($mobile_number, $status, $message, $extra_data = array()) {
        $this->add_log('login_attempt', $mobile_number, $status, $message, $extra_data);
    }
    
    /**
     * Log registration attempt
     * 
     * @param string $mobile_number
     * @param string $status
     * @param string $message
     * @param array $extra_data
     */
    public function log_registration_attempt($mobile_number, $status, $message, $extra_data = array()) {
        $this->add_log('registration_attempt', $mobile_number, $status, $message, $extra_data);
    }
    
    /**
     * Log OTP verification
     * 
     * @param string $mobile_number
     * @param string $status
     * @param string $message
     * @param array $extra_data
     */
    public function log_otp_verification($mobile_number, $status, $message, $extra_data = array()) {
        $this->add_log('otp_verification', $mobile_number, $status, $message, $extra_data);
    }
    
    /**
     * Log profile update
     * 
     * @param int $user_id
     * @param string $status
     * @param string $message
     * @param array $extra_data
     */
    public function log_profile_update($user_id, $status, $message, $extra_data = array()) {
        $mobile_number = get_user_meta($user_id, 'apl_mobile_number', true);
        $this->add_log('profile_update', $mobile_number, $status, $message, $extra_data);
    }
}
