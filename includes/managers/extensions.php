<?php
namespace Qazana\Extensions;

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

use Qazana\Admin\Settings\Panel;
use Qazana\Loader;

use Qazana\Admin\Settings\Extensions as Options;

final class Manager {

    /**
     * Extension loader
     *
     * @var array
     */
	public $loader;

    /**
     * Extension data
     *
     * @var array
     */
	private $path = array();

	/**
	 * @var \ReflectionClass
	 */
	private $reflection;

    /**
     * Extension instances are stored here
     *
     * @var array
     */
	private $extensions = array();

    /**
     * __construct
     */
	public function __construct() {
        $this->loader = new Loader();
        $this->reflection = new \ReflectionClass( $this );
        $this->options = get_option('qazana_' . Options::EXTENSIONS_MANAGER_OPTION_NAME, []);

        do_action( 'qazana/extensions/before/loaded', $this );

        add_action( 'qazana_init_classes', [ $this, 'register_all_extensions' ] );
        add_action( 'qazana_init_classes', [ $this, 'load_extension_dependencies' ] );

        add_action( 'qazana/widgets/widgets_registered', [ $this, 'load_widgets' ] );
    
        do_action( 'qazana/extensions/after/loaded', $this );
    }

    public function register_all_extensions() {

        do_action( 'qazana/extensions/loader/before', $this->loader );

        $this->loader->add_stack( qazana()->theme_paths_child, qazana()->theme_extensions_locations );
        $this->loader->add_stack( qazana()->theme_paths, qazana()->theme_extensions_locations );

        do_action( 'qazana/extensions/loader', $this->loader );  // plugins can intercept the stack here. Themes will always take precedence

        $this->loader->add_stack( array( 'path' => qazana()->plugin_dir, 'uri' => qazana()->plugin_url ), qazana()->plugin_extensions_locations );

        do_action( 'qazana/extensions/loader/after', $this->loader );

        foreach ( $this->loader->merge_files_stack_locations() as $folder ) {
            $this->register_extensions( $folder );
        }
    }

    /**
     * Register Extensions for use
     *
     * @since       1.0.0
     * @access      public
     * @return      void
     */
    public function register_extensions( $path ) {

        if ( ! is_dir( $path ) ) {
            return false;
        }

        $folders = scandir( $path, 1 );

        /**
         * Action 'qazana/extensions/before'
         *
         * @param object $this Qazana
         */
        do_action( 'qazana/extensions/register/before', $this );

        foreach ( $folders as $folder ) {
            $this->initialize_extension( $folder, $path );
        }

        /**
         * Action 'qazana/extensions'
         *
         * @param object $this Qazana
         */
        do_action( 'qazana/extensions/register/after', $this );

    }

     /**
      * Register Extension for use
      *
      * @since       1.0.0
      * @access      public
      * @return      void
      */
    public function initialize_extension( $folder, $path ) {

        $path = trailingslashit( $path );

        if ( $folder === '.' || $folder === '..' || ! is_dir( $path . $folder ) || substr( $folder, 0, 1 ) === '.' || substr( $folder, 0, 1 ) === '@' || substr( $folder, 0, 4 ) === '_vti' ) {
            return;
        }

        $extension_class = 'Qazana\Extensions\\' . ucfirst(str_replace('-', '_', $folder));

        /**
         * Filter 'qazana/extension/{folder}'
         *
         * @param string                    extension class file path
         * @param string $extension_class   extension class name
         */
        $class_file = "$path$folder/extension_{$folder}.php";

        if ( $file = $this->loader->locate_widget("$folder/extension_{$folder}.php", false ) && file_exists( $class_file ) && empty( $this->extensions[ $folder ] ) ) {
            
            if ( class_exists( $extension_class ) ) {
                return;
            }

            require_once $class_file;

            if ( ! class_exists( $extension_class ) ) {
                return new \WP_Error( __CLASS__ . '::' . $extension_class, 'Extension class not found in `' . $class_file );
            }
          
            if ( class_exists( $extension_class ) ) {
                $this->extensions[ $folder ] = new $extension_class( $this );
            }

        }

        /**
         * Action 'qazana/extensions/before'
         *
         * @param object $this Qazana
         */
        do_action( "qazana/extensions/registered/{$folder}", $folder, $this );

    }

    /**
     * Load Extensions for use
     *
     * @since       1.0.0
     * @return      void
     */
    public function load_extension_dependencies() {

        $extensions = $this->get_extensions();
        if ( empty( $extensions ) ) {
            return;
        }

        foreach ( $extensions as $extension_id => $extension_instance ) {
            if ( ! empty( $extension_instance->get_config()['dependencies'] ) ) {
                $dependencies = $extension_instance->get_config()['dependencies'];
                foreach ( $dependencies as $extension_id ) {
                    $this->load_extension( $extension_id );
                }
            }
        }
    }

    /**
     * Load a single Extension for use - bypasses activation settings
     *
     * @since       1.3.0
     * @return      void
     */
    public function load_extension( $extension_id ) {
        if ( ! is_object( $this->get_extension( $extension_id ) ) ) {
            foreach ( $this->loader->merge_files_stack_locations() as $folder ) {
                $this->initialize_extension( $extension_id, $folder );
            }
        }
    }

    /**
     * Register Extensions for use
     *
     * @since       1.0.0
     * @return      void
     */
    private function register_widgets( $extension, $widgets ) {

        /**
         * action 'qazana/extensions/before'
         *
         * @param object $this Qazana
         */
        do_action( "qazana/extensions/widgets/before", $this );

        if ( empty( $widgets ) ) {
            return;
        }

        foreach ( $widgets as $widget ) {

            $filename = strtolower(
    			preg_replace(
    				[ '/^' . __NAMESPACE__ . '\\\/', '/([a-z])([A-Z])/', '/_/', '/\\\/' ],
    				[ '', '$1-$2', '-', DIRECTORY_SEPARATOR ],
    				$widget
    			)
    		);

            if ( $file = $this->loader->locate_widget( "{$extension}/widgets/{$filename}.php", false ) ) {
                require_once $file;
            }
        }

        /**
         * action 'qazana/extensions'
         *
         * @param object $this Qazana
         */
        do_action( "qazana/extensions/widgets", $this );

    }

    /**
     * Register Extensions for use
     *
     * @since       1.0.0
     * @return      void
     */
    private function load_skins( $extension, $skins ) {

        /**
         * action 'qazana/extensions/before'
         *
         * @param object $this Qazana
         */
        do_action( "qazana/extensions/skins/before", $this );

        if ( empty( $skins ) ) {
            return;
        }

        foreach ( $skins as $skin ) {

            $filename = strtolower(
    			preg_replace(
    				[ '/^' . __NAMESPACE__ . '\\\/', '/([a-z])([A-Z])/', '/_/', '/\\\/' ],
    				[ '', '$1-$2', '-', DIRECTORY_SEPARATOR ],
    				$skin
    			)
    		);

            $class_name = $this->reflection->getNamespaceName() . '\Widgets\\' . ucfirst( $extension ) . '\Skins\Skin_' . ucfirst( $skin );

            if ( class_exists( $class_name ) ) {
                continue;
            }

            if ( $file = $this->loader->locate_widget( "{$extension}/skins/{$filename}.php", false ) ) {
                require_once $file;
            }
        }

        /**
         * action 'qazana/extensions'
         *
         * @param object $this Qazana
         */
        do_action( "qazana/extensions/skins", $this );

    }

	public function load_widgets() {

        $extensions = $this->get_extensions();

        if ( empty( $extensions ) ) {
            return;
        }
    
        foreach ( $extensions as $extension_id => $extension_object ) {

            if ( ! $this->is_extension_active( $extension_id ) ) {
                continue;
            }

            $this->load_extension_widgets( $extension_id );
        }
    }

    /**
     * Load a single Extension's widgets
     *
     * @since       1.3.0
     * @return      void
     */
    public function load_extension_widgets( $extension_id ) {

        $widget_manager = qazana()->widgets_manager;

        if ( ! empty( $this->get_skins( $extension_id ) ) ) {
            $this->load_skins( $extension_id, $this->get_skins( $extension_id ) );
        }

        if ( ! empty( $this->get_widgets( $extension_id ) ) ) {
            $this->register_widgets( $extension_id, $this->get_widgets( $extension_id ) );

            foreach ( $this->get_widgets( $extension_id ) as $widget ) {

                $class_name = $this->reflection->getNamespaceName() . '\Widgets\\' . $widget;

                if ( ! class_exists( $class_name ) ) {
                    return new \WP_Error( __CLASS__ . '::' . $class_name, 'Widget class not found in `' . $this->get_name( $extension_id ) );
                }

                $widget_manager->register_widget_type( new $class_name() );
            }
        }
    }

    public function get_name( $extension_id ) {

        $extension_data = $this->get_extension_data( $extension_id );

        if ( $extension_data['name'] ) {
			return $extension_data['name'];
		}

		return false;
	}

	public function get_widgets( $extension_id ) {

        $extension_data = $this->get_extension_data( $extension_id );

        if ( ! empty( $extension_data['widgets'] ) ) {
			return $extension_data['widgets'];
		}

		return false;
	}

    public function get_skins( $extension_id ) {

        $extension_data = $this->get_extension_data( $extension_id );

        if ( ! empty( $extension_data['skins'] ) ) {
			return $extension_data['skins'];
		}

		return false;
	}

    public function get_extension( $extension_id ) {
        $extensions = $this->get_extensions();
        return isset($extensions[ $extension_id ] ) ? $extensions[ $extension_id ] : false;
	}

	public function get_extension_data( $extension_id ) {
        $extensions = $this->get_extensions();
		return isset($extensions[ $extension_id ] ) ? $extensions[ $extension_id ]->get_config() : false;
    }
    
    public function get_extensions( $extension_id = null ) {
        if ( $extension_id ) {
            //TODO _deprecated_function( __METHOD__, '2.0.0', '$this->get_extension()' );
            return $this->get_extension( $extension_id );
        }

        return apply_filters( 'qazana/extensions', $this->extensions );
    }

    public function is_extension_active( $extension_id ) {

        $extension_data = $this->get_extension_data( $extension_id );

        if ( $extension_data['required'] === true ) {
            return true;
        }

        if ( ! empty( $this->options[$extension_id] ) && in_array( 'active', $this->options[$extension_id] ) ) {
            return true;
        }

        return false;
    }
}
