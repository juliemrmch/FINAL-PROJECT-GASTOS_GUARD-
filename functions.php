<?php
/**
 * General utility functions for Gastos Guard
 */

/**
 * Sanitize input to prevent XSS attacks
 * 
 * @param string $data Input data
 * @return string Sanitized data
 */
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Format currency with peso symbol
 * 
 * @param float $amount Amount to format
 * @param bool $show_peso Whether to show peso symbol
 * @return string Formatted amount
 */
function format_currency($amount, $show_peso = true) {
    $formatted = number_format($amount, 2);
    return $show_peso ? '₱' . $formatted : $formatted;
}

/**
 * Format date to readable format
 * 
 * @param string $date Date in Y-m-d format
 * @param string $format Output format
 * @return string Formatted date
 */
function format_date($date, $format = 'Y-m-d') {
    $timestamp = strtotime($date);
    return date($format, $timestamp);
}

/**
 * Get first letter of each word in a string
 * Useful for user avatar initials
 * 
 * @param string $name Full name
 * @return string Initials
 */
function get_initials($name) {
    $words = explode(' ', $name);
    $initials = '';
    
    foreach ($words as $word) {
        $initials .= strtoupper(substr($word, 0, 1));
    }
    
    return substr($initials, 0, 2); // Return at most 2 initials
}

/**
 * Get current month name and year
 * 
 * @return string Month and year (e.g., June 2023)
 */
function get_current_month() {
    return date('F Y');
}

/**
 * Calculate percentage of a value from total
 * 
 * @param float $value The value
 * @param float $total The total
 * @return float Percentage
 */
function calculate_percentage($value, $total) {
    if ($total == 0) {
        return 0;
    }
    
    return round(($value / $total) * 100, 2);
}

/**
 * Get custom date range based on predefined periods
 * 
 * @param string $period Period (today, week, month, year, custom)
 * @param string $start_date Custom start date (for custom period)
 * @param string $end_date Custom end date (for custom period)
 * @return array Start and end dates
 */
function get_date_range($period = 'month', $start_date = null, $end_date = null) {
    $today = date('Y-m-d');
    
    switch ($period) {
        case 'today':
            return ['start_date' => $today, 'end_date' => $today];
        
        case 'week':
            $week_start = date('Y-m-d', strtotime('monday this week'));
            return ['start_date' => $week_start, 'end_date' => $today];
        
        case 'month':
            $month_start = date('Y-m-01');
            $month_end = date('Y-m-t');
            return ['start_date' => $month_start, 'end_date' => $month_end];
        
        case 'year':
            $year_start = date('Y-01-01');
            $year_end = date('Y-12-31');
            return ['start_date' => $year_start, 'end_date' => $year_end];
        
        case 'custom':
            if (!$start_date || !$end_date) {
                return get_date_range();
            }
            return ['start_date' => $start_date, 'end_date' => $end_date];
        
        default:
            $month_start = date('Y-m-01');
            $month_end = date('Y-m-t');
            return ['start_date' => $month_start, 'end_date' => $month_end];
    }
}

/**
 * Debug function to print variables in a readable format
 * 
 * @param mixed $data Data to debug
 * @param bool $die Whether to stop execution after printing
 * @return void
 */
function debug($data, $die = true) {
    echo '<pre>';
    print_r($data);
    echo '</pre>';
    
    if ($die) {
        die();
    }
}

/**
 * Get user avatar URL or generate initial-based avatar
 * 
 * @param array $user User data
 * @return string Avatar HTML
 */
function get_user_avatar($user) {
    if (!empty($user['profile_image'])) {
        return '<img src="' . $user['profile_image'] . '" alt="' . $user['full_name'] . '" class="avatar-img">';
    } else {
        $initials = get_initials($user['full_name']);
        return '<span>' . $initials . '</span>';
    }
}