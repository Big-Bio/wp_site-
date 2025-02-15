<?php

/**
 * Suppress "error - 0 - No summary was found for this file" on phpdoc generation
 *
 * @package plugin\admin
 */

use WPDataAccess\Design_Table\WPDA_Design_Table_Model;
use WPDataAccess\List_Table\WPDA_List_View;
use WPDataAccess\Settings\WPDA_Settings;
use WPDataAccess\User_Menu\WPDA_User_Menu_Model;
use WPDataAccess\WPDA;
use WPDataAccess\Documentation\WPDA_Documentation;
use WPDataAccess\Backup\WPDA_Data_Export;
use WPDataAccess\Data_Publisher\WPDA_Publisher_Model;

/**
 * Class WP_Data_Access_Admin
 *
 * Defines admin specific functionality for plugin WP Data Access.
 *
 * @author  Peter Schulz
 * @since   1.0.0
 */
class WP_Data_Access_Admin {

	/**
	 * Menu slug for main page
	 */
	const PAGE_MAIN = 'wpda';

	/**
	 * Menu slug for setting page
	 */
	const PAGE_SETTINGS = 'wpda_settings';

	/**
	 * Menu slug for export import page
	 */
	const PAGE_BACKUP = 'wpda_backup';

	/**
	 * Menu slug for menus page
	 */
	const PAGE_MENUS = 'wpda_menus';

	/**
	 * Menu slug for explorer page
	 */
	const PAGE_EXPLORER = 'wpda_explorer';

	/**
	 * Menu slug for main page
	 */
	const PAGE_PUBLISHER = 'wpda_publisher';

	/**
	 * Menu slug for designer page
	 */
	const PAGE_DESIGNER = 'wpda_designer';

	/**
	 * Menu slug for my tables page
	 */
	const PAGE_MY_TABLES = 'wpda_my_tables';

	/**
	 * Menu slug for help page
	 */
	const PAGE_HELP = 'wpda_help';

	/**
	 * Page hook suffix to Data Explorer page or false
	 *
	 * @var string|false
	 */
	protected $wpda_data_explorer_menu;

	/**
	 * Page hook suffix to Data Designer page or false
	 *
	 * @var string|false
	 */
	protected $wpda_data_designer_menu;

	/**
	 * Page hook suffix to Data Publisher page or false
	 *
	 * @var string|false
	 */
	protected $wpda_data_publisher_menu;

	/**
	 * Page hook suffix to Manage Menus page or false
	 *
	 * @var string|false
	 */
	protected $wpda_manage_menus_menu;

	/**
	 * Reference to list view for Data Explorer page
	 *
	 * @var WPDA_List_View
	 */
	protected $wpda_data_explorer_view;

	/**
	 * Reference to list view for Data Designer page
	 *
	 * @var WPDA_List_View
	 */
	protected $wpda_data_designer_view;

	/**
	 * Reference to list view for Data Publisher page
	 *
	 * @var WPDA_List_View
	 */
	protected $wpda_data_publisher_view;

	/**
	 * Reference to list view for Manage Menus page
	 *
	 * @var WPDA_List_View
	 */
	protected $wpda_manage_menus_view;

	/**
	 * Data Publisher sub menu title (dynamically set to support translations)
	 *
	 * @var string
	 */
	protected $title_submenu_publisher;

	/**
	 * Array of page hook suffixes to user defined sub menus
	 *
	 * @var array
	 */
	protected $wpda_my_table_list_menu = [];

	/**
	 * Array of list view for user defined sub menus
	 *
	 * @var array
	 */
	protected $wpda_my_table_list_view = [];

	/**
	 * Page hook suffix help page or false
	 *
	 * @var string|false
	 */
	protected $wpda_help;

	/**
	 * Main menu title (dynamically set to support translations)
	 *
	 * @var string
	 */
	protected $title_menu_menu;

	/**
	 * Data explorer sub menu title (dynamically set to support translations)
	 *
	 * @var string
	 */
	protected $title_submenu_explorer;

	/**
	 * Data designer sub menu title (dynamically set to support translations)
	 *
	 * @var string
	 */
	protected $title_submenu_designer;

	/**
	 * Settings page sub menu title (dynamically set to support translations)
	 *
	 * @var string
	 */
	protected $title_submenu_settings;

	/**
	 * Data backup page sub menu title (dynamically set to support translations)
	 *
	 * @var string
	 */
	protected $title_submenu_backup;

	/**
	 * Menus page sub menu title (dynamically set to support translations)
	 *
	 * @var string
	 */
	protected $title_submenu_menus;

	/**
	 * Menu slug or null
	 *
	 * @var null
	 */
	protected $page = null;

	/**
	 * Help sub menu title (dynamically set to support translations)
	 *
	 * @var string
	 */
	protected $title_submenu_help;

	/**
	 * WP_Data_Access_Admin constructor
	 *
	 * @since   1.0.0
	 */
	public function __construct() {
		if ( isset( $_REQUEST['page'] ) ) {
			$this->page = sanitize_text_field( wp_unslash( $_REQUEST['page'] ) ); // input var okay.
		}
	}

	/**
	 * Handle plugin cookies
	 *
	 * Cookies are use to remember values during navigation: (max 1 hour)
	 * 1) SCHEMA NAME
	 * The schema name is saved as a cookie when changed in the Data Explorer. As long as the user stays within the
	 * page the saved schema is used. When the user moves to another page the value is destroyed. The user gets the
	 * default value on the next visit.
	 * 2) FAVOURITE SELECTION
	 * The favourite selection is saved as cookie when changed in the Data Explorer. As long as the user stays within
	 * the page the saved selection is used. When the user moves to another page the value is destroyed. The user gets
	 * the default value on the next visit.
	 * 3) SEARCH ARGUMENT
	 * Search arguments are saved as cookies per table. As long as the user stays within the same page the saved
	 * search value is used. When the user moves to another page the value is destroyed. This allows users to navigate
	 * between pages without losing the search value.
	 *
	 * @since   1.6.0
	 */
	public function handle_plugin_cookies() {
		if ( $this->page === self::PAGE_MAIN ) {
			// Handle Data Explorer cookies (search cookie is handled in next section).
			// Handle cookie to remember active schema.
			$cookie_name = self::PAGE_MAIN . '_schema_name';
			if ( isset( $_REQUEST['wpda_main_db_schema'] ) && '' !== $_REQUEST['wpda_main_db_schema'] ) {
				$requested_db_schema = sanitize_text_field( wp_unslash( $_REQUEST['wpda_main_db_schema'] ) ); // input var okay.
				setcookie( $cookie_name, $requested_db_schema, time() + 3600 );
			} else {
				// Check referer: clear cookie on new page request.
				$url = parse_url( wp_get_referer() );
				if ( isset( $url['query'] ) ) {
					parse_str( $url['query'], $path );
					if ( isset( $path['page'] ) ) {
						$page = $path['page'];
						if ( $this->page !== $page ) {
							// New page request: reset cookie.
							setcookie( $cookie_name, '', time() - 3600 );
							unset( $_COOKIE[ $cookie_name ] );
						}
					}
				}
			}

			// Handle cookie to remember favourite selection.
			$cookie_name = $this->page . '_favourites';
			if ( isset( $_REQUEST['wpda_main_favourites'] ) ) {
				$favourites = sanitize_text_field( wp_unslash( $_REQUEST['wpda_main_favourites'] ) ); // input var okay.
				setcookie( $cookie_name, $favourites, time() + 3600 );
			} else {
				// Check referer: clear cookie on new page request.
				$url = parse_url( wp_get_referer() );
				if ( isset( $url['query'] ) ) {
					parse_str( $url['query'], $path );
					if ( isset( $path['page'] ) ) {
						$page = $path['page'];
						if ( $this->page !== $page ) {
							// New page request: reset cookie.
							setcookie( $cookie_name, '', time() - 3600 );
							unset( $_COOKIE[ $cookie_name ] );
						}
					}
				}
			}
		}

		// Handle cookie for search value.
		$table_name  =
			isset( $_REQUEST['table_name'] ) ?
				sanitize_text_field( wp_unslash( $_REQUEST['table_name'] ) ) :
				\WPDataAccess\List_Table\WPDA_List_Table::LIST_BASE_TABLE; // input var okay.
		$cookie_name = $this->page . '_search_' . str_replace( '.', '_', $table_name );
		if ( isset( $_REQUEST['s'] ) ) { // input var okay.
			$search_argument = sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ); // input var okay.
			if ( '' !== $search_argument ) {
				setcookie( $cookie_name, $search_argument, time() + 3600 );
			} else {
				setcookie( $cookie_name, '', time() - 3600 );
				unset( $_COOKIE[ $cookie_name ] );
			}
		} else {
			// Check referer: clear cookie on new page request.
			$url = parse_url( wp_get_referer() );
			if ( isset( $url['query'] ) ) {
				parse_str( $url['query'], $path );
				if ( isset( $path['page'] ) ) {
					$page = $path['page'];
					if ( $this->page !== $page ) {
						// New page request: reset cookie and all cookies for subpages.
						foreach ( $_COOKIE as $key => $value ) {
							if ( $this->page . '_search_' === substr( $key, 0, strlen( $this->page . '_search_' ) ) ) {
								setcookie( $key, '', time() - 3600 );
								unset( $_COOKIE[ $key ] );
							}
						}
					}
				}
			}
		}
	}

	/**
	 * Add stylesheets to back-end
	 *
	 * The following stylesheets are added:
	 * + Plugin stylesheet
	 * + Visual editor stylesheet
	 *
	 * The plugin stylesheet is used to style the setting forms {@see WPDA_Settings}, simple forms
	 * {@see \WPDataAccess\Simple_Form\WPDA_Simple_Form} and the user menu edit form
	 * {@see \WPDataAccess\User_Menu\WPDA_User_Menu_Form}. The visual editor stysheets is used to style
	 * the plugin button in the visual editor {@see WP_Data_Access_Public}.
	 *
	 * @since   1.0.0
	 *
	 * @see WPDA_Settings
	 * @see \WPDataAccess\Simple_Form\WPDA_Simple_Form
	 * @see \WPDataAccess\User_Menu\WPDA_User_Menu_Form
	 * @see WP_Data_Access_Public
	 */
	public function enqueue_styles() {
		wp_enqueue_style( 'wp-jquery-ui-core' );
		wp_enqueue_style( 'wp-jquery-ui-dialog' );
		wp_enqueue_style( 'wp-jquery-ui-tabs' );

		// WPDataAccess CSS.
		wp_register_style(
			'wpdataaccess',
			plugins_url( '../assets/css/wpda_style.css', __FILE__ ),
			[],
			WPDA::get_option( WPDA::OPTION_WPDA_VERSION )
		);
		wp_enqueue_style( 'wpdataaccess' );

		// Add WP Data Projects stylesheet.
		wp_register_style(
			'wpdataprojects',
			plugins_url( '../WPDataProjects/assets/css/wpdp_style.css', __FILE__ ),
			[],
			WPDA::get_option( WPDA::OPTION_WPDA_VERSION )
		);
		wp_enqueue_style( 'wpdataprojects' );

		if ( WPDA::get_option( WPDA::OPTION_BE_LOAD_DATATABLES ) === 'on' ) {
			// Load JQuery DataTables to test publication in the dashboard
			wp_register_style(
				'jquery_datatables', '//cdn.datatables.net/' .
				                     WPDA::get_option( WPDA::OPTION_WPDA_DATATABLES_VERSION ) .
				                     '/css/jquery.dataTables.min.css',
				[],
				WPDA::get_option( WPDA::OPTION_WPDA_VERSION )
			);
			wp_enqueue_style( 'jquery_datatables' );
		}

		if ( WPDA::get_option( WPDA::OPTION_BE_LOAD_DATATABLES_RESPONSE ) === 'on' ) {
			// Load JQuery DataTables Responsive to test publication in the dashboard
			wp_register_style(
				'jquery_datatables_responsive',
				'//cdn.datatables.net/responsive/' .
				WPDA::get_option( WPDA::OPTION_WPDA_DATATABLES_RESPONSIVE_VERSION ) .
				'/css/responsive.dataTables.min.css',
				[],
				WPDA::get_option( WPDA::OPTION_WPDA_VERSION )
			);
			wp_enqueue_style( 'jquery_datatables_responsive' );
		}

		if ( WPDA::get_option( WPDA::OPTION_BE_LOAD_BOOTSTRAP ) === 'on' ) {
			// Load Bootstrap to test publication in the dashboard
			wp_register_style(
				'prefix_bootstrap',
				'//maxcdn.bootstrapcdn.com/bootstrap/' .
				WPDA::get_option( WPDA::OPTION_WPDA_BOOTSTRAP_VERSION ) .
				'/css/bootstrap.min.css',
				[],
				WPDA::get_option( WPDA::OPTION_WPDA_VERSION )
			);
			wp_enqueue_style( 'prefix_bootstrap' );
		}
	}

	/**
	 * Add scripts to back-end
	 *
	 * @since   1.0.0
	 *
	 * @see WP_Data_Access_Public
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( 'jquery-ui-core' );
		wp_enqueue_script( 'jquery-ui-dialog' );
		wp_enqueue_script( 'jquery-ui-tabs' );

		// Register wpda admin functions.
		wp_register_script(
			'wpda_admin_scripts',
			plugins_url( '../assets/js/wpda_admin.js', __FILE__ ),
			[],
			WPDA::get_option( WPDA::OPTION_WPDA_VERSION )
		);
		wp_enqueue_script( 'wpda_admin_scripts' );

		// Add WP Data Projects JS functions.
		wp_register_script(
			'wpdataprojects',
			plugins_url( '../WPDataProjects/assets/js/wpdp_admin.js', __FILE__ ),
			[],
			WPDA::get_option( WPDA::OPTION_WPDA_VERSION )
		);
		wp_enqueue_script( 'wpdataprojects' );

		// Ajax call to WPDA datables implementation to test publication in the dashboard
		$details      = __( 'Row details', 'wp-data-access' ); // Set title of modal window here to support i18n.
		$query_string = str_replace( ' ', '+', "?details=$details" );
		wp_register_script(
			'wpda_datatables',
			plugins_url( '../assets/js/wpda_datatables.js' . $query_string, __FILE__ ), [ 'jquery' ],
			[],
			WPDA::get_option( WPDA::OPTION_WPDA_VERSION )
		);
		wp_localize_script( 'wpda_datatables', 'wpda_ajax', [ 'wpda_ajaxurl' => admin_url( 'admin-ajax.php' ) ] );
		wp_enqueue_script( 'wpda_datatables' );

		// Register purl external library.
		wp_register_script( 'purl', plugins_url( '../assets/js/purl.js', __FILE__ ), [ 'jquery' ] );
		wp_enqueue_script( 'purl' );

		// Register clipboard.js external library.
		wp_register_script(
			'clipboard',
			plugins_url( '../assets/js/clipboard.min.js', __FILE__ ),
			[],
			WPDA::get_option( WPDA::OPTION_WPDA_VERSION )
		);
		wp_enqueue_script( 'clipboard' );

		// Load JQuery DataTables to test publication in the dashboard
		if ( WPDA::get_option( WPDA::OPTION_BE_LOAD_DATATABLES ) === 'on' ) {
			wp_register_script(
				'jquery_datatables',
				'//cdn.datatables.net/' .
				WPDA::get_option( WPDA::OPTION_WPDA_DATATABLES_VERSION ) .
				'/js/jquery.dataTables.min.js',
				[],
				WPDA::get_option( WPDA::OPTION_WPDA_VERSION )
			);
			wp_enqueue_script( 'jquery_datatables' );
		}

		// Load JQuery DataTables Responsive to test publication in the dashboard
		if ( WPDA::get_option( WPDA::OPTION_BE_LOAD_DATATABLES_RESPONSE ) === 'on' ) {
			wp_register_script(
				'jquery_datatables_responsive',
				'//cdn.datatables.net/responsive/' .
				WPDA::get_option( WPDA::OPTION_WPDA_DATATABLES_RESPONSIVE_VERSION ) .
				'/js/dataTables.responsive.min.js',
				[],
				WPDA::get_option( WPDA::OPTION_WPDA_VERSION )
			);
			wp_enqueue_script( 'jquery_datatables_responsive' );
		}

		if ( WPDA::get_option( WPDA::OPTION_BE_LOAD_BOOTSTRAP ) === 'on' ) {
			// Load Bootstrap to test publication in the dashboard
			wp_register_script(
				'prefix_bootstrap',
				'//maxcdn.bootstrapcdn.com/bootstrap/' .
				WPDA::get_option( WPDA::OPTION_WPDA_BOOTSTRAP_VERSION ) .
				'/js/bootstrap.min.js'
			);
			wp_enqueue_script( 'prefix_bootstrap' );
		}
	}

	/**
	 * Add plugin menu and sub menus
	 *
	 * Adds the following menu and sub menus to the back-end menu:
	 * + WP Data Access
	 *   + Data Explorer
	 *   + Data Designer
	 *   + Data Projects
	 *   + Manage Plugin
	 *   + Plugin Help
	 *
	 * Menu titles are dynamically set in {@see WP_Data_Access_Admin::set_menu_titles()} to support translations.
	 *
	 * @since   1.0.0
	 *
	 * @see WP_Data_Access_Admin::set_menu_titles()
	 */
	public function add_menu_items() {
		global $wpdb;

		// Dynamically set menu titles.
		$this->set_menu_titles();

		if ( current_user_can( 'manage_options' ) ) {
			// Specific list tables (and forms) can be made available for specific capabilities:
			// managed in method add_menu_my_tables.
			// Main menu and items are only available to admin users (set capability to 'manage_options').
			add_menu_page(
				$this->title_menu_menu,
				$this->title_menu_menu,
				'manage_options',
				self::PAGE_MAIN,
				null,
				'dashicons-editor-table',
				999999999
			);

			// Add data explorer to WPDA menu.
			$this->wpda_data_explorer_menu = add_submenu_page(
				self::PAGE_MAIN,
				$this->title_menu_menu,
				$this->title_submenu_explorer,
				'manage_options',
				self::PAGE_MAIN,
				[ $this, 'data_explorer_page' ]
			);
			$this->wpda_data_explorer_view = new WPDA_List_View(
				[
					'page_hook_suffix' => $this->wpda_data_explorer_menu,
				]
			);

			if ( WPDA_Design_Table_Model::table_exists() ) {
				// Add data designer to WPDA menu.
				$this->wpda_data_designer_menu = add_submenu_page(
					self::PAGE_MAIN,
					$this->title_menu_menu,
					$this->title_submenu_designer,
					'manage_options',
					self::PAGE_DESIGNER,
					[ $this, 'data_designer_page' ]
				);
				$this->wpda_data_designer_view = new WPDA_List_View(
					[
						'page_hook_suffix' => $this->wpda_data_designer_menu,
						'table_name'       => $wpdb->prefix . WPDA::get_option( WPDA::OPTION_WPDA_PREFIX ) . 'table_design',
						'list_table_class' => 'WPDataAccess\\Design_Table\\WPDA_Design_Table_List_Table',
						'edit_form_class'  => 'WPDataAccess\\Design_Table\\WPDA_Design_Table_Form',
						'subtitle'         => '',
					]
				);
			}

			// Add Data Projects menu.
			$wpdp = new \WPDataProjects\WPDP();
			$wpdp->add_menu_items();

			if ( WPDA_Publisher_Model::table_exists() ) {
				// Add submenu for Data Publisher
				$this->wpda_data_publisher_menu = add_submenu_page(
					self::PAGE_MAIN,
					$this->title_menu_menu,
					$this->title_submenu_publisher,
					'manage_options',
					self::PAGE_PUBLISHER,
					[ $this, 'data_publisher_page' ]
				);
				$this->wpda_data_publisher_view = new WPDA_List_View(
					[
						'page_hook_suffix' => $this->wpda_data_publisher_menu,
						'table_name'       => $wpdb->prefix . WPDA::get_option( WPDA::OPTION_WPDA_PREFIX ) . 'publisher',
						'list_table_class' => 'WPDataAccess\\Data_Publisher\\WPDA_Publisher_List_Table',
						'edit_form_class'  => 'WPDataAccess\\Data_Publisher\\WPDA_Publisher_Form',
					]
				);
			}

			if ( WPDA_User_Menu_Model::table_exists() ) {
				// Add manage menus page to WPDA menu.
				$this->wpda_manage_menus_menu = add_submenu_page(
					self::PAGE_MAIN,
					$this->title_menu_menu,
					$this->title_submenu_menus,
					'manage_options',
					self::PAGE_MENUS,
					[ $this, 'menus_page' ]
				);
				$this->wpda_manage_menus_view = new WPDA_List_View(
					[
						'page_hook_suffix' => $this->wpda_manage_menus_menu,
						'table_name'       => $wpdb->prefix . WPDA::get_option( WPDA::OPTION_WPDA_PREFIX ) . 'menu_items',
						'title'            => $this->title_submenu_menus,
						'list_table_class' => 'WPDataAccess\\User_Menu\\WPDA_User_Menu_List_Table',
						'edit_form_class'  => 'WPDataAccess\\User_Menu\\WPDA_User_Menu_Form',
						'column_headers'   => [
							'menu_name'       => __( 'Menu name', 'wp-data-access' ),
							'menu_table_name' => __( 'Table name', 'wp-data-access' ),
							'capability'      => __( 'Capability', 'wp-data-access' ),
							'menu_slug'       => __( 'Menu slug', 'wp-data-access' ),
						],
					]
				);
			}

			// Add plugin export import page to WPDA menu.
			add_submenu_page(
				self::PAGE_MAIN,
				$this->title_menu_menu,
				$this->title_submenu_backup,
				'manage_options',
				self::PAGE_BACKUP,
				[ $this, 'backup_page' ]
			);

			// Add plugin settings page to WPDA menu.
			add_submenu_page(
				self::PAGE_MAIN,
				$this->title_menu_menu,
				$this->title_submenu_settings,
				'manage_options',
				self::PAGE_SETTINGS,
				[ $this, 'settings_page' ]
			);

			// Add plugin help page to WPDA menu.
			add_submenu_page(
				self::PAGE_MAIN,
				$this->title_menu_menu,
				$this->title_submenu_help,
				'manage_options',
				self::PAGE_HELP,
				[ $this, 'help_page' ]
			);
		} else {
			$this->grant_access_to_data_publications();
		}

		// Add Data Projects menus.
		$wpdp = new \WPDataProjects\WPDP();
		$wpdp->add_projects();
	}

	protected function grant_access_to_data_publications() {
		// Check user role
		$user_roles = WPDA::get_current_user_roles();
		if ( false === $user_roles || ! is_array( $user_roles ) ) {
			// Cannot determine the user roles (not able to show menus)
			return;
		}

		$publication_roles = WPDA::get_option( WPDA::OPTION_DP_PUBLICATION_ROLES );
		if ( '' === $publication_roles || 'administrator' === $publication_roles ) {
			// No access
			return;
		}

		$user_has_role = false;
		foreach ( $user_roles as $user_role ) {
			if ( false !== strpos( $publication_roles, $user_role ) ) {
				$user_has_role = true;
			}
		}
		if ( ! $user_has_role ) {
			// No access
			return;
		}

		// Grant access to main menu
		add_menu_page(
			$this->title_menu_menu,
			$this->title_menu_menu,
			WPDA::get_current_user_capability(),
			self::PAGE_PUBLISHER,
			null,
			'dashicons-editor-table',
			999999999
		);

		if ( WPDA_Publisher_Model::table_exists() ) {
			global $wpdb;

			// Add submenu for Data Publisher
			$this->wpda_data_publisher_menu = add_submenu_page(
				self::PAGE_PUBLISHER,
				$this->title_menu_menu,
				$this->title_submenu_publisher,
				WPDA::get_current_user_capability(),
				self::PAGE_PUBLISHER,
				[ $this, 'data_publisher_page' ]
			);
			$this->wpda_data_publisher_view = new WPDA_List_View(
				[
					'page_hook_suffix' => $this->wpda_data_publisher_menu,
					'table_name'       => $wpdb->prefix . WPDA::get_option( WPDA::OPTION_WPDA_PREFIX ) . 'publisher',
					'list_table_class' => 'WPDataAccess\\Data_Publisher\\WPDA_Publisher_List_Table',
					'edit_form_class'  => 'WPDataAccess\\Data_Publisher\\WPDA_Publisher_Form',
				]
			);
		}
	}

	/**
	 * Dynamically set menu titles
	 *
	 * Dynamically set menu titles to support translations.
	 *
	 * @since   1.0.0
	 */
	protected function set_menu_titles() {
		$this->title_menu_menu         = 'WP Data Access';
		$this->title_submenu_explorer  = __( 'Data Explorer', 'wp-data-access' );
		$this->title_submenu_designer  = __( 'Data Designer', 'wp-data-access' );
		$this->title_submenu_publisher = __( 'Data Publisher', 'wp-data-access' );
		$this->title_submenu_menus     = __( 'Data Menus', 'wp-data-access' );
		$this->title_submenu_backup    = __( 'Data Backup', 'wp-data-access' );
		$this->title_submenu_settings  = __( 'Manage Plugin', 'wp-data-access' );
		$this->title_submenu_help      = __( 'Plugin Help', 'wp-data-access' );
	}

	/**
	 * Show data explorer main page
	 *
	 * Initialization of $this->wpda_data_explorer_view is done earlier in
	 * {@see WP_Data_Access_Admin::add_menu_items()} to support screen options. This method just shows the page
	 * containing the list table.
	 *
	 * @since   1.0.0
	 *
	 * @see WP_Data_Access_Admin::add_menu_items()
	 */
	public function data_explorer_page() {
		$this->wpda_data_explorer_view->show();
	}

	/**
	 * Show data designer main page
	 *
	 * Initialization of $this->wpda_data_designer_view is done earlier in
	 * {@see WP_Data_Access_Admin::add_menu_items()} to support screen options. This method just shows the page
	 * containing the list table.
	 *
	 * @since   1.0.0
	 *
	 * @see WP_Data_Access_Admin::add_menu_items()
	 */
	public function data_designer_page() {
		$this->wpda_data_designer_view->show();
	}

	/**
	 * Show data publisher main page
	 */
	public function data_publisher_page() {
		$this->wpda_data_publisher_view->show();
	}

	/**
	 * Show manage menus page
	 *
	 * Shows a list table containing all user defined menu items. From the list table the user menu form
	 * {\WPDataAccess\User_Menu\WPDA_User_Menu_Form} can be called to edit menu items.
	 *
	 * Initialization of $this->wpda_manage_menus_view is done earlier in
	 * {@see WP_Data_Access_Admin::add_menu_items()} to support screen options. This method just shows the page
	 * containing the list table.
	 *
	 * @see WP_Data_Access_Admin::add_menu_items()
	 * @see \WPDataAccess\User_Menu\WPDA_User_Menu_Form
	 */
	public function menus_page() {
		$this->wpda_manage_menus_view->show();
	}

	/**
	 * Show data backup main page
	 *
	 * Calls a page to create automatic backups (in fact data exports) and offers possibilities to restore (in fact
	 * data imports).
	 *
	 * @since   2.0.6
	 *
	 * @see WPDA_Data_Export::show_wp_cron()
	 */
	public function backup_page() {
		$wpda_backup = new WPDA_Data_Export();
		if ( isset( $_REQUEST['action'] ) ) {
			$action = sanitize_text_field( wp_unslash( $_REQUEST['action'] ) ); // input var okay.
			if ( 'new' === $action ) {
				$wpda_backup->create_export( 'add' );
			} elseif ( 'add' === $action ) {
				$wpda_backup->wpda_add_cron_job();
			} elseif ( 'remove' === $action ) {
				$wpda_backup->wpda_remove_cron_job();
			} elseif ( 'edit' === $action ) {
				$wpda_backup->create_export( 'update' );
			} elseif ( 'update' === $action ) {
				$wpda_backup->wpda_update_cron_job();
			}
		} else {
			$wpda_backup->show_wp_cron();
		}
	}

	/**
	 * Show setting pages
	 *
	 * @since   1.0.0
	 *
	 * @see WPDA_Settings::show()
	 */
	public function settings_page() {
		$wpda_settings = new WPDA_Settings();
		$wpda_settings->show();
	}

	/**
	 * Show help page
	 *
	 * @since   2.0.0
	 */
	public function help_page() {
		$wpda_documentation = new WPDA_Documentation();
		$wpda_documentation->show();
	}

	/**
	 * Add user defined sub menu
	 *
	 * WPDA allows users to create sub menu for table lists and simple forms. Sub menus can be added to the WPDA
	 * menu or any other (external) menu. A sub menu is added to an external menu via the menu slug. Sub menus are
	 * taken from {@see WPDA_User_Menu_Model}.
	 *
	 * This method is called from the admin_menu action with a lower priority to make sure other menus are available.
	 * User defined menu items are added to avalable menus in this method. These can be WPDA menus or external menus
	 * as mentioned in the according list table and edit form. WPDA menus are added to menu WP Data Tables. External
	 * menus are added to the menu having the menu slug defined by the user.
	 *
	 * This method does not actually show the list tables! It just creates the menu items. When the user clicks on such
	 * a dynamiccally defined menu item, method {@see WP_Data_Access_Admin::my_tables_page()} is called, which takes
	 * care of showing the list table.
	 *
	 * @since   1.0.0
	 *
	 * @see WP_Data_Access_Admin::my_tables_page()
	 * @see WPDA_User_Menu_Model
	 */
	public function add_menu_my_tables() {
		// Dynamically set menu titles.
		$this->set_menu_titles();

		$menus_shown_to_current_user = [];

		// Add list tables to external menus.
		foreach ( WPDA_User_Menu_Model::list_external_menus() as $menu ) {
			if ( current_user_can( $menu->menu_capability ) ) {
				if ( ! isset( $menus_shown_to_current_user[ $menu->menu_slug . '/' . $menu->menu_name . '/' . $menu->menu_table_name ] ) ) {
					$menu_slug = self::PAGE_EXPLORER . '_' . $menu->menu_table_name;

					$this->wpda_my_table_list_menu[ $menu->menu_table_name ] =
						add_submenu_page(
							$menu->menu_slug,
							$this->title_menu_menu . ' : ' . strtoupper( $menu->menu_table_name ),
							$menu->menu_name,
							$menu->menu_capability,
							$menu_slug,
							[ $this, 'my_tables_page' ]
						);

					$this->wpda_my_table_list_view[ $menu->menu_table_name ] =
						new WPDA_List_View(
							[
								'page_hook_suffix' => $this->wpda_my_table_list_menu[ $menu->menu_table_name ],
								'table_name'       => $menu->menu_table_name,
							]
						);

					$menus_shown_to_current_user[ $menu->menu_slug . '/' . $menu->menu_name . '/' . $menu->menu_table_name ] = true;
				}
			}
		}
	}

	/**
	 * Show user defined menus
	 *
	 * A user defined menu that are added to the plugin menu in {@see WP_Data_Access_Admin::add_menu_my_tables()} is
	 * shown here. This method is called when the user clicks on the menu item generated in
	 * {@see WP_Data_Access_Admin::add_menu_my_tables()}.
	 *
	 * @since   1.0.0
	 *
	 * @see WP_Data_Access_Admin::add_menu_my_tables()
	 */
	public function my_tables_page() {
		// Grab table name from menu slug.
		if ( null !== $this->page ) {
			if ( strpos( $this->page, self::PAGE_EXPLORER ) !== false ) {
				$table = substr( $this->page, strlen( self::PAGE_EXPLORER . '_' ) );
			} else {
				$table = substr( $this->page, strlen( self::PAGE_MY_TABLES . '_' ) );
			}
			// Show list table.
			$this->wpda_my_table_list_view[ $table ]->show();
		}
	}

}
