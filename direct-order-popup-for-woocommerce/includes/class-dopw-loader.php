<?php

/**
 * The core plugin loader class.
 *
 * @since      1.0.0
 * @package    DirectOrderPopupCheckout
 * @subpackage DirectOrderPopupCheckout/includes
 */

if (!defined('WPINC')) {
    die;
}

/**
 * The core plugin loader class.
 */
class DOPW_Loader
{

    /**
     * The single instance of the class.
     *
     * @var DOPW_Loader
     */
    private static $instance = null;

    /**
     * The admin instance.
     *
     * @var DOPW_Admin
     */
    private $admin;

    /**
     * The frontend instance.
     *
     * @var DOPW_Frontend
     */
    private $frontend;

    /**
     * The ajax handler instance.
     *
     * @var DOPW_Ajax
     */
    private $ajax;

    /**
     * Protected constructor to prevent creating a new instance of the
     * class via the `new` operator from outside of this class.
     */
    protected function __construct()
    {
        $this->load_dependencies();
        $this->init_components();
        $this->setup_hooks();
    }

    /**
     * Main DOPW_Loader Instance.
     *
     * Ensures only one instance of DOPW_Loader is loaded or can be loaded.
     *
     * @return DOPW_Loader Main instance
     */
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Load the required dependencies for this plugin.
     *
     * @return void
     */
    private function load_dependencies()
    {
        require_once DOPW_PLUGIN_DIR . 'includes/class-DOPW-admin.php';
        require_once DOPW_PLUGIN_DIR . 'includes/class-DOPW-frontend.php';
        require_once DOPW_PLUGIN_DIR . 'includes/class-DOPW-ajax.php';
        require_once DOPW_PLUGIN_DIR . 'includes/class-DOPW-helpers.php';
    }

    /**
     * Initialize plugin components.
     *
     * @return void
     */
    private function init_components()
    {
        $this->admin = new DOPW_Admin();
        $this->frontend = new DOPW_Frontend();
        $this->ajax = new DOPW_Ajax();
    }

    /**
     * Register all of the hooks related to the plugin functionality.
     *
     * @return void
     */
    private function setup_hooks()
    {
        // All plugin hooks (enqueue scripts, ajax etc) can be added here
        // load_plugin_textdomain() is no longer needed
    }

    /**
     * Run the loader to execute all the hooks with WordPress.
     *
     * @return void
     */
    public function run()
    {
        $this->admin->init();
        $this->frontend->init();
        $this->ajax->init();
    }

    /**
     * Activation hook callback.
     *
     * @return void
     */
    public static function activate()
    {
        // Activation code here
    }

    /**
     * Deactivation hook callback.
     *
     * @return void
     */
    public static function deactivate()
    {
        // Deactivation code here
    }
}
