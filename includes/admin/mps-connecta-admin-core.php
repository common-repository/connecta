<?php

defined('ABSPATH') or exit();

/**
 * This class adds all things the plugin does 
 * for just admin users
 */
class MPS_Ebay_Admin_Core
{
    public function __construct()
    {
        // add menu item to backend
        add_action('admin_menu', array($this, 'menu'));
        
        // add settings page
        add_action('admin_menu', array($this, 'settings'));

        // adds meta box to woocommerce edit order page
        require_once 'mps-connecta-admin-meta-box.php';
    }

    public function menu()
    {
        add_menu_page(
            'Connecta',
            'Connecta',
            'manage_options',
            'mps-connecta',
            function() { require_once plugin_dir_path(__FILE__) . 'mps-connecta-admin-main.php'; },
            plugin_dir_url(__FILE__) . '../../assets/img/light-16x16.png',
            6
        );
    }
    
    public function settings()
    {
        add_submenu_page(
            'mps-connecta',
            'Settings',
            'Settings',
            'manage_options',
            'mps-connecta-settings',
            function () {require_once plugin_dir_path(__FILE__) . 'mps-connecta-admin-settings.php';}
        );
    }
}


return new MPS_Ebay_Admin_Core();
