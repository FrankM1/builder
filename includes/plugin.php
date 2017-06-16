<?php
namespace Builder;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Main class plugin
 */
class Plugin {

    /**
     * Current version of the plugin.
     *
     * @since 1.0.0
     *
     * @var string
     */
    public $version;

    /** Magic *****************************************************************/

    /**
     * Builder uses many variables, several of which can be filtered to
     * customize the way it operates. Most of these variables are stored in a
     * private array that gets updated with the help of PHP magic methods.
     *
     * This is a precautionary measure, to avoid potential errors produced by
     * unanticipated direct manipulation of Builder's run-time data.
     *
     * @see builder::setup_globals()
     *
     * @var array
     */
    private $data;

    /** Not Magic *************************************************************/

    /**
     * @var mixed False when not logged in; WP_User object when logged in
     */
    public $current_user = false;

    /**
     * @var obj Add-ons append to this (Akismet, BuddyPress, etc...)
     */
    public $extend;

    /**
     * @var obj Add-ons append to this (Akismet, BuddyPress, etc...)
     */
    public $file_stack;

    /**
     * @var array Video views
     */
    public $views = array();

    /**
     * @var array Overloads get_option()
     */
    public $options = array();

    /*
    public $db;
    public $icons_manager;
    public $controls_manager;
    public $schemes_manager;
    public $elements_manager;

    public $widget_loader;
    public $widgets_manager;

    public $skins_manager;
    public $posts_css_manager;
    public $customcss;

    /*
	 * @var Revisions_Manager
	public $revisions_manager;

    public $editor;
    public $preview;
    public $widgets;

    public $frontend;
    public $heartbeat;

    public $templates_manager;

    public $extensions_loader;
    public $extensions;

    /**
     * Getter method for retrieving the object instance.
     *
     * @since 1.0.0
     */
    public static function instance() {

        // Store the instance locally to avoid private static replication
        static $instance = null;

        // Only run these methods if they haven't been ran previously
        if ( null === $instance ) {
            $instance = new self();
            $instance->setup_globals();
            $instance->includes();
            $instance->setup_actions();
        }

        // Always return the instance
        return $instance;
    }

    /** Magic Methods *********************************************************/

    /**
     * A dummy constructor to prevent Builder from being loaded more than once.
     *
     * @since 1.0.0
     * @see builder::instance()
     * @see builder();
     */
    private function __construct() {}

    /**
     * A dummy magic method to prevent Builder from being cloned.
     *
     * @since 1.0.0
     */
    public function __clone() {
        _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'builder' ), '1.0.0' );
    }

    /**
     * A dummy magic method to prevent Builder from being unserialized.
     *
     * @since 1.0.0
     */
    public function __wakeup() {
        _doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'builder' ), '1.0.0' );
    }

    /**
     * Magic method for checking the existence of a certain custom field.
     *
     * @since 1.0.0
     */
    public function __isset( $key ) {
        return isset( $this->data[$key] );
    }

    /**
     * Magic method for getting Builder variables.
     *
     * @since 1.0.0
     */
    public function __get( $key ) {
        return isset( $this->data[$key] ) ? $this->data[$key] : null;
    }

    /**
     * Magic method for setting Builder variables.
     *
     * @since 1.0.0
     */
    public function __set( $key, $value ) {
        $this->data[$key] = $value;
    }

    /**
     * Magic method for unsetting Builder variables.
     *
     * @since 1.0.0
     */
    public function __unset( $key ) {
        if ( isset( $this->data[$key] ) ) {
            unset( $this->data[$key] );
        }
    }

    /**
     * Magic method to prevent notices and errors from invalid method calls.
     *
     * @since 1.0.0
     */
    public function __call( $name = '', $args = array() ) {
        unset( $name, $args );

        return;
    }

    /** Private Methods *******************************************************/

    /**
     * Set some smart defaults to class variables. Allow some of them to be
     * filtered to allow for early overriding.
     *
     * @since 1.0.0
     *
     * @uses plugin_dir_path() To generate Builder plugin path
     * @uses plugin_dir_url() To generate Builder plugin url
     * @uses apply_filters() Calls various filters
     */
    private function setup_globals() {

        /* Versions **********************************************************/

        $this->version = '1.0.2';
        $this->db_version = '102';

        /* Paths *************************************************************/

        // Setup some base path and URL information
        $this->file = BUILDER__FILE__;
        $this->slug = 'builder';

        $this->basename = apply_filters( 'builder_plugin_basename', plugin_basename( BUILDER__FILE__ ) );
        $this->plugin_dir = apply_filters( 'builder_plugin_dir_path',  plugin_dir_path( BUILDER__FILE__ ) );
        $this->plugin_url = apply_filters( 'builder_plugin_dir_url', plugins_url( '/', BUILDER__FILE__ ) );

        // core assets
        $this->core_assets_dir = apply_filters( 'builder_core_assets_dir', trailingslashit( $this->plugin_dir . 'assets' ) );
        $this->core_assets_url = apply_filters( 'builder_core_assets_url', trailingslashit( $this->plugin_url . 'assets' ) );

        // Includes
        $this->includes_dir = apply_filters( 'builder_includes_dir', trailingslashit( $this->plugin_dir . 'includes' ) );
        $this->includes_url = apply_filters( 'builder_includes_url', trailingslashit( $this->plugin_url . 'includes' ) );

        // Languages
        $this->lang_dir = apply_filters( 'builder_lang_dir',     trailingslashit( $this->plugin_dir . 'languages' ) );

        /* Identifiers *******************************************************/
        $this->admin = new \StdClass(); // Used by admin

        /* Misc **************************************************************/
        $this->domain = 'builder'; // Unique identifier for retrieving translated strings
        $this->extend = new \StdClass(); // Plugins add data here
        $this->errors = new \WP_Error(); // Feedback

        $this->widget_locations = array(
            'includes/widgets',
        );

        $this->extensions_locations = array(
            'includes/extensions/builder/extensions',
            'includes/extensions',
        );

    }

    /**
     * Constructor. Hooks all interactions into correct areas to start
     * the class.
     *
     * @since 1.0.0
     */
    public function setup_actions() {

        // Add actions to plugin activation and deactivation hooks
        add_action( 'activate_' . $this->basename, 'builder_activation' );
        add_action( 'deactivate_' . $this->basename, 'builder_deactivation' );

        // If Builder is being deactivated, do not add any actions
        if ( builder_is_deactivation( $this->basename ) ) {
            return;
        }

        // Array of Builder core actions
        $actions = array(
            'init',     // check for supported cpts
            'init_classes',     // load plugin classes
            'load_textdomain', // Load textdomain (builder)
        );

        // Add the actions
        foreach ( $actions as $class_action ) {
            add_action( 'builder_' . $class_action, array( $this, $class_action ), 5 );
        }

        // All Builder actions are setup (includes video-central-core-hooks.php)
        do_action_ref_array( 'builder_after_setup_actions', array( &$this ) );

        // Add Page Templates
        add_action( 'after_setup_theme', array( 'Builder\Page_Template_Manager', 'get_instance' ) ); // load late for filters to work

    }

    /**
     * Register the CPTs with our Editor support.
     */
    public function init() {

        $cpt_support = get_option( 'builder_cpt_support', [ 'page', 'post' ] );

        foreach ( $cpt_support as $cpt_slug ) {
            add_post_type_support( $cpt_slug, 'builder', 'revisions' );
        }
    }

    private function includes() {

        /** Core **************************************************************/
        require_once( $this->includes_dir . 'core/sub-actions.php' );
        require_once( $this->includes_dir . 'core/functions.php' );
        require_once( $this->includes_dir . 'core/update.php' );
        require_once( $this->includes_dir . 'core/loader.php' );

        require_once( $this->includes_dir . 'common/functions.php' );
        require_once( $this->includes_dir . 'common/utils.php' );
        require_once( $this->includes_dir . 'common/db.php' );
        require_once( $this->includes_dir . 'common/heartbeat.php' );
        require_once( $this->includes_dir . 'common/cron.php' );

        require_once( $this->includes_dir . 'editor/fonts.php' );
        require_once( $this->includes_dir . 'editor/editor.php' );
        require_once( $this->includes_dir . 'editor/preview.php' );
        require_once( $this->includes_dir . 'editor/frontend.php' );
        require_once( $this->includes_dir . 'editor/responsive.php' );
        require_once( $this->includes_dir . 'editor/stylesheet.php' );
        require_once( $this->includes_dir . 'editor/user.php' );
        require_once( $this->includes_dir . 'editor/conditions.php' );
        require_once( $this->includes_dir . 'editor/post-css.php' );
        require_once( $this->includes_dir . 'editor/icons.php' );

        require_once( $this->includes_dir . 'managers/controls.php' );
        require_once( $this->includes_dir . 'managers/schemes.php' );
        require_once( $this->includes_dir . 'managers/skins.php' );
        require_once( $this->includes_dir . 'managers/shapes.php' );
        require_once( $this->includes_dir . 'managers/posts-css.php' );
        require_once( $this->includes_dir . 'managers/elements.php' );
        require_once( $this->includes_dir . 'managers/widgets.php' );
        require_once( $this->includes_dir . 'managers/templates.php' );
        require_once( $this->includes_dir . 'managers/page-template.php' );
        require_once( $this->includes_dir . 'managers/custom-css.php' );
        require_once( $this->includes_dir . 'managers/extensions.php' );
        require_once( $this->includes_dir . 'managers/revisions.php' );

        // extensions
        require_once( $this->includes_dir . 'extensions/extensions-base.php');

        /** Hooks *************************************************************/
        require_once $this->includes_dir.'core/actions.php';
        require_once $this->includes_dir.'core/filters.php';

        if ( is_admin() ) {

            require_once( $this->includes_dir . 'admin/admin.php' );
            require_once( $this->includes_dir . 'admin/functions.php' );
            require_once( $this->includes_dir . 'admin/actions.php' );

            if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
                require_once( $this->includes_dir . 'managers/image.php' );
            }
        }
    }

    /**
     * Loads the plugin classes.
     *
     * @since 1.0.0
     */
    public function init_classes() {

        $this->db                   = new DB();
        $this->icons_manager        = new Icons_Manager();
        $this->controls_manager     = new Controls_Manager();
        $this->schemes_manager      = new Schemes_Manager();
        $this->elements_manager     = new Elements_Manager();

        $this->widget_loader        = new Loader( $this->widget_locations );
        $this->widgets_manager      = new Widgets_Manager();

	    $this->skins_manager 	    = new Skins_Manager();
	    $this->posts_css_manager    = new Posts_CSS_Manager();
        $this->customcss            = new Custom_Css();
        $this->revisions_manager    = new Revisions_Manager();

        $this->editor               = new Editor();
        $this->preview              = new Preview();
        $this->widgets              = '';

        $this->frontend             = new Frontend();
        $this->heartbeat            = new Heartbeat();

        $this->templates_manager    = new Template_Manager();

        $this->extensions_loader    = new Loader( $this->extensions_locations );
        $this->extensions           = new Extensions\Manager();
        $this->cron                 = new Cron;

    }

    /** Public Methods ********************************************************/

    /**
     * Registers a plugin activation hook to make sure the current WordPress
     * version is suitable ( >= 3.3.1 ) for use.
     *
     * @since 1.0.0
     *
     * @global int $wp_version The current version of this particular WP instance
     */
    public function activation() {
        global $wp_version;

        if ( version_compare( $wp_version, '3.0.0', '<' ) ) {
            deactivate_plugins( plugin_basename( __FILE__ ) );
            wp_die( printf( __( 'Sorry, but your version of WordPress, <strong>%s</strong>, does not meet the Builder\'s required version of <strong>3.3.1</strong> to run properly. The plugin has been deactivated. <a href="%s">Click here to return to the Dashboard</a>', 'builder'), $wp_version, admin_url() ) );
        }
    }

    /**
     * Load the translation file for current language. Checks the languages
     * folder inside the Builder plugin first, and then the default WordPress
     * languages folder.
     *
     * Note that custom translation files inside the Builder plugin folder
     * will be removed on Builder updates. If you're creating custom
     * translation files, please use the global language folder.
     *
     * @since 1.0.0
     *
     * @uses apply_filters() Calls 'plugin_locale' with {@link get_locale()} value
     * @uses load_textdomain() To load the textdomain
     */
    public function load_textdomain() {

        // Traditional WordPress plugin locale filter
        $locale = apply_filters( 'plugin_locale', get_locale(), $this->domain );
        $mofile = $locale . '.mo';

        // Setup paths to current locale file
        $mofile_local = $this->lang_dir.$mofile;
        $mofile_global = WP_LANG_DIR . '/plugins/builder/' . $mofile;

        // Look in global /wp-content/languages/builder folder
        load_textdomain( $this->domain, $mofile_global );

        // Look in local /wp-content/plugins/builder/ folder
        load_textdomain( $this->domain, $mofile_local );

        // Look in global /wp-content/languages/plugins/
        load_plugin_textdomain( $this->domain );
    }

    /**
     * Getter method for retrieving the url.
     *
     * @since 1.0.0
     */
    public static function get_url() {
        return plugins_url( '', __FILE__ );
    }

    /**
     * Getter method for retrieving the url.
     *
     * @since 1.0.0
     */
    public static function get_dir() {
        return plugin_dir_path( __FILE__ );
    }

    /**
     * Getter method for retrieving the main plugin filepath.
     *
     * @since 1.0.0
     */
    public static function get_file() {
        return self::$file;
    }

    /**
     * @return string
     */
    public function get_version() {
        return $this->version;
    }
}