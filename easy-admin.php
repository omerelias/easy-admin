<?php
/**
 * Plugin Name: Easy Admin (WC Mobile Dashboard)
 * Description: ממשק מובייל נוח להזמנות ומלאים בווקומרס.
 * Version: 1.0.0
 * Author: Omer Elias
 */

if (!defined('ABSPATH')) exit;

require_once plugin_dir_path(__FILE__) . 'includes/class-wc-mobile-dashboard.php';

new WC_Mobile_Dashboard(); // הפעלת הקלאס