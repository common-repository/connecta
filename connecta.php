<?php
/**
 * Plugin Name: Connecta
 * Plugin URI:  http://mipromotionalsourcing.com
 * Description: Link your WooCommerce store with your ebay account to automatically synchronize your orders and inventory 
 * Version: 1.0.0
 * Author: Mi Promotional Sourcing
 * Author URI: https://mipromotionalsourcing.com
 * License: GPLv3
 * License URI: https://www.gnu.org/licenses/gpl-3.0.en.html
 */


defined('ABSPATH') or exit();

require_once 'vendor/autoload.php';

/**
 * Main Connecta Class
 */
final class Connecta
{
    protected static $_instance = null;

    /**
     * Main Connecta Instance
     *
     * Ensures only one instance of Connecta is loaded or can be loaded.
     *
     * @return  Connecta - Main instance
     */
    public static function instance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * MPS_Ebay Constructor.
     */
    public function __construct()
    {
        // Include required files
        $this->includes();

        // AJAX requests
        $this->handle_ajax();

        // Core
        require_once 'includes/core/mps-connecta-core.php';

        // Admin stuff
        if (is_admin()) {
            require_once 'includes/admin/mps-connecta-admin-core.php';
        }
    }

    /**
     * Include required core files used in admin and on the frontend.
     */
    public function includes()
    {
        // Exceptions
        require_once 'includes/exceptions/ConnectaFailedToCreateOrder.php';
        require_once 'includes/exceptions/ConnectaFailedToCreateUser.php';
        require_once 'includes/exceptions/ConnectaMissingSku.php';
        require_once 'includes/exceptions/ConnectaNotAnEbayOrder.php';
        require_once 'includes/exceptions/ConnectaOrderAlreadyExists.php';
        require_once 'includes/exceptions/ConnectaUserEmailMissing.php';
        
        // Classes
        require_once 'includes/classes/mps-connecta-ebay-order.php';
        require_once 'includes/classes/mps-connecta-ebay-order-stub.php';
        require_once 'includes/classes/mps-connecta-ebay-user.php';
        
        // Webhooks
        require_once 'includes/classes/mps-connecta-rest.php';

        // Functions
        require_once 'includes/mps-connecta-functions.php';
        
        // Ajax handlers
        require_once 'includes/mps-connecta-ajax.php';

        // Load assets
        add_action('admin_enqueue_scripts', array($this, 'load_assets'));
    }

    /**
     * Handles loading assets
     */
    public function load_assets($hook)
    {
        if (!strstr($hook, 'mps-connecta')) {
            return;
        }
        // styles
        wp_enqueue_style('mps-connecta_core_style',  plugin_dir_url(__FILE__) . 'assets/css/mps-connecta-core.css');
        wp_enqueue_style('mps-connecta_admin_style', plugin_dir_url(__FILE__) . 'assets/css/mps-connecta-admin-page.css');
        wp_enqueue_style('mps-connecta_sweetalert',  plugin_dir_url(__FILE__) . 'assets/css/sweetalert.css');
        // scripts
        wp_enqueue_script('mps-connecta_bootstrap_script', plugin_dir_url(__FILE__) . 'assets/js/bootstrap.min.js');
        wp_enqueue_script('mps-connecta_sweetalert',       plugin_dir_url(__FILE__) . 'assets/js/sweetalert.min.js');
        wp_enqueue_script('mps-connecta_script',           plugin_dir_url(__FILE__) . 'assets/js/mps-connecta-admin-page.js');
    }

    /**
     * Handles AJAX requests
     */
    public function handle_ajax()
    {
        $ajax_handler = new MPS_Ebay_AJAX();

        add_action('wp_ajax_mps_connecta_update_frontend', array($ajax_handler, 'mps_connecta_update_frontend'));
        add_action('wp_ajax_mps_connecta_save_verif_code', array($ajax_handler, 'mps_connecta_save_verif_code'));
        add_action('wp_ajax_mps_connecta_save_key', array($ajax_handler, 'mps_connecta_save_key'));
    }
}

if (!function_exists('connecta')) {
    /**
     * Returns the main instance of Connecta to prevent the need to use globals.
     *
     * @return  MPS_Ebay
     */
    function connecta()
    {
        return Connecta::instance();
    }
}

/**
 * This plugin requries WooCommerce to be active
 */
add_action('plugins_loaded', function () {
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', function () {
            $class = 'notice notice-error';
            $message = __("Warning! Connecta requires WooCommerce to be active. Please download and activate WooCommerce before using this plugin.");
            printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), esc_html($message));
        });
        return;
    }
    connecta();
});

/**
 * Upon activation
 */
function connecta_activate(){
	// if previously installed, then just resume from 
	// where the user left off
	if( get_option('mps_connecta_installed') ){
			return;
	}
	// otherwise, we need to begin the tutorial
	update_option('mps_connecta_installed', true);
	update_option('mps_connecta_tutorial_stage', 0);
} 
register_activation_hook(__FILE__, 'connecta_activate');

/**
* Upon uninstallation
*/
function connecta_clear_options(){
	delete_option("mps_connecta_installed");
	delete_option("mps_connecta_tutorial_stage");
	delete_option("mps_ebay_verification_code");
	delete_option("mps_ebay_api_key");
	delete_option("mps_ebay_created");
	delete_option("mps_ebay_last_sync");
	delete_option("mps_ebay_last_sync_status");
	delete_option("mps_ebay_is_syncing");
} 
register_uninstall_hook(__FILE__, 'connecta_clear_options');
register_deactivation_hook(__FILE__, 'connecta_clear_options');