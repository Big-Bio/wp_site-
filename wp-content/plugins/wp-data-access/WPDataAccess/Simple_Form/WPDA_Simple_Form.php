<?php

/**
 * Suppress "error - 0 - No summary was found for this file" on phpdoc generation
 *
 * @package WPDataAccess\Simple_Form
 */

namespace WPDataAccess\Simple_Form {

	use WPDataAccess\Data_Dictionary\WPDA_List_Columns;
	use WPDataAccess\Utilities\WPDA_Message_Box;
	use WPDataAccess\WPDA;

	/**
	 * Class WPDA_Simple_Form
	 *
	 * Generates a simple dynamic data entry form on request. Data entry form consists of the following components:
	 * + WPDA_Simple_Form (layout management)
	 * + WPDA_Simple_Form_Data (data management)
	 * + WPDA_Simple_Form_Item (data item management)
	 * + WPDA_Simple_Form_Type_Icon (data type icon)
	 *
	 * Simple forms can be generated for tables only. It is not possible to generate simple forms for views.
	 *
	 * Simple forms can be generated for tables with a primary key only. For tables that do not have a primary key it
	 * is not possibble to generate a simple form as records in the table cannot be recognized uniquely.
	 *
	 * Primary key fields are disable in update mode. This is to prevent inconsistency. Use method
	 * {@see WPDA_Simple_Form::set_update_keys()} to allow updating primary key items. Make sure you understand to
	 * consequences!!!
	 *
	 * @author  Peter Schulz
	 * @since   1.0.0
	 */
	class WPDA_Simple_Form {

		/**
		 * Static id to create unique form names on the page
		 *
		 * @var int
		 */
		static protected $form_id = 0;

		/**
		 * Warning icon
		 */
		const WARNING_ICON = '<span class="dashicons dashicons-warning"></span> ';

		/**
		 * Form id
		 *
		 * @var  int
		 */
		protected $current_form_id;

		/**
		 * Page title
		 *
		 * @var string
		 */
		protected $title = null;

		/**
		 * Add action to page title?
		 *
		 * @var string TRUE = add action
		 */
		protected $add_action_to_title;

		/**
		 * Page subtitle
		 *
		 * @var string
		 */
		protected $subtitle = '';

		/**
		 * Hides warning update text
		 *
		 * @var string TRUE = hide warning (overwrites plugin settings)
		 */
		protected $no_warning_update_text = 'FALSE';

		/**
		 * Database schema name
		 *
		 * @var string
		 */
		protected $schema_name;

		/**
		 * Database table name
		 *
		 * @var string
		 */
		protected $table_name;

		/**
		 * Handle to data management component
		 *
		 * @var WPDA_Simple_Form_Data
		 */
		protected $row_data;

		/**
		 * Handle to database record
		 *
		 * @var object
		 */
		protected $row;

		/**
		 * Handle to WPDA_List_Columns
		 *
		 * @var WPDA_List_Columns
		 */
		protected $wpda_list_columns;

		/**
		 * Access column names
		 *
		 * @var array
		 */
		protected $table_columns;

		/**
		 * Access column headers
		 *
		 * @var array
		 */
		protected $table_column_headers;

		/**
		 * Display column headers
		 *
		 * @var array
		 */
		protected $table_column_header;

		/**
		 * Column headers (labels, arguments)
		 *
		 * @var array
		 */
		protected $column_headers;

		/**
		 * Form items
		 *
		 * @var array
		 */
		protected $form_items = [];

		/**
		 * Form items named array
		 *
		 * Use this array to quickly find an item in the form
		 *
		 * @var array
		 */
		protected $form_items_named = [];

		/**
		 * Form items old values (if applicable)
		 *
		 * @var array
		 */
		protected $form_items_old_values = [];

		/**
		 * Form items new values (if applicable)
		 *
		 * @var array
		 */
		protected $form_items_new_values = [];

		/**
		 * Menu slug current page
		 *
		 * @var string
		 */
		protected $page;

		/**
		 * Primary page action
		 *
		 * @var string
		 */
		protected $action;

		/**
		 * Secondary page action
		 *
		 * @var string
		 */
		protected $action2 = '';

		/**
		 * Auto increment value
		 *
		 * @var int
		 */
		protected $auto_increment_value = - 1;

		/**
		 * Defines it keys are updatable
		 *
		 * @var bool
		 */
		protected $update_keys_allowed = false;

		/**
		 * Error message number
		 *
		 * @var int
		 */
		protected $wpda_err = 0;

		/**
		 * Error message text
		 *
		 * @var string
		 */
		protected $wpda_msg = '';

		/**
		 * Indicates whether the title should be displayed or not
		 *
		 * @var bool
		 */
		protected $show_title = true;

		/**
		 * Indicates whether the back button should be displayed or not
		 *
		 * @var bool
		 */
		protected $show_back_button = true;

		/**
		 * Indicates whether table type should be checked to display subtitle.
		 *
		 * @var bool
		 */
		protected $check_table_type = true;

		/**
		 * Page number item name (default 'page_number')
		 *
		 * The name can be changed for pages on multiple levels. This is needed to get back to the right page in
		 * parent-child page.
		 *
		 * @var string
		 */
		protected $page_number_item_name = 'page_number';

		/**
		 * Real page number
		 *
		 * @var null
		 */
		protected $page_number_link = '';

		/**
		 * Page number text item
		 *
		 * @var null
		 */
		protected $page_number_item = '';

		/**
		 * WPDA_Simple_Form constructor
		 *
		 * Performs the following steps:
		 * + Check if requested action is allowed
		 * + Check if table is provided
		 * + Save OLD and NEW request values
		 * + Create WPDA_Simple_Form_Data object
		 *
		 * @param string            $schema_name Database schema name.
		 * @param string            $table_name Database table name.
		 * @param WPDA_List_Columns $wpda_list_columns Reference to column array.
		 * @param array             $args Messages (named array).
		 *
		 * @since   1.0.0
		 *
		 * @see WPDA_Simple_Form_Data
		 *
		 */
		public function __construct(
			$schema_name,
			$table_name,
			&$wpda_list_columns,
			$args = []
		) {
			// Get page and handling arguments.
			if ( isset( $_REQUEST['page'] ) ) {
				$this->page = sanitize_text_field( wp_unslash( $_REQUEST['page'] ) ); // input var okay.
			} else {
				wp_die( __( 'ERROR: Wrong arguments [missing page argument]', 'wp-data-access' ) );
			}
			if ( isset( $_REQUEST['action'] ) ) {
				// Possible values: "new", "edit" and "view".
				$this->action = sanitize_text_field( wp_unslash( $_REQUEST['action'] ) ); // input var okay.
			} else {
				wp_die( __( 'ERROR: Wrong arguments [missing action argument]', 'wp-data-access' ) );
			}
			if ( isset( $_REQUEST['action2'] ) ) {
				$this->action2 = sanitize_text_field( wp_unslash( $_REQUEST['action2'] ) ); // input var okay.
			}

			$this->schema_name = $schema_name;
			$this->table_name  = $table_name;
			if ( '' === $this->table_name ) {
				// Without a table name it makes no sense to continue.
				wp_die( __( 'ERROR: Wrong arguments [missing table_name argument]' ) );
			}

			if ( ! WPDA::is_wpda_table( $this->table_name ) ) {
				// Check access rights for tables that do not belong to the plugin.
				if ( 'on' !== WPDA::get_option( WPDA::OPTION_BE_ALLOW_INSERT ) && 'new' === $this->action ) {
					// Insert not allowed.
					wp_die( __( 'ERROR: Not authorized', 'wp-data-access' ) );
				}
				if ( 'on' !== WPDA::get_option( WPDA::OPTION_BE_VIEW_LINK ) && 'view' === $this->action ) {
					// Viewing not allowed.
					wp_die( __( 'ERROR: Not authorized', 'wp-data-access' ) );
				}
				if ( 'on' !== WPDA::get_option( WPDA::OPTION_BE_ALLOW_UPDATE ) && 'edit' === $this->action ) {
					// Update not allowed.
					wp_die( __( 'ERROR: Not authorized', 'wp-data-access' ) );
				}
			}

			// Get columns information.
			$this->wpda_list_columns    = $wpda_list_columns;
			$this->table_columns        = $this->wpda_list_columns->get_table_columns();
			$this->table_column_headers = $this->wpda_list_columns->get_table_column_headers();

			// Set page title.
			if ( isset( $args['title'] ) ) {
				$this->title               = $args['title'];
				$this->add_action_to_title = true;
				if ( isset( $args['add_action_to_title'] ) && 'FALSE' === $args['add_action_to_title'] ) {
					$this->add_action_to_title = false;
				}
			}

			// Set page subtitle.
			if ( isset( $args['subtitle'] ) ) {
				$this->subtitle = $args['subtitle'];
			} else {
				if ( $this->check_table_type ) {
					global $wpdb;
					$wp_tables = $wpdb->tables( 'all', true );

					if ( isset( $wp_tables[ substr( $this->table_name, strlen( $wpdb->prefix ) ) ] ) ) {
						$this->subtitle = self::WARNING_ICON . WPDA::get_table_type_text( WPDA::TABLE_TYPE_WP );
					} elseif ( WPDA::is_wpda_table( $this->table_name ) ) {
						$this->subtitle = self::WARNING_ICON . WPDA::get_table_type_text( WPDA::TABLE_TYPE_WPDA );
					}
				}
			}

			if ( isset( $args['no_warning_update_text'] ) ) {
				$this->no_warning_update_text = $args['no_warning_update_text'];
			}

			// Add customizable success and failure messages to support i18n.
			// This allows to overwrite message in classes extending WPDA_Simple_Form.
			$args = wp_parse_args(
				$args, [
					'wpda_success_msg' => __( 'Succesfully saved changes to database', 'wp-data-access' ),
					'wpda_failure_msg' => __( 'Saving changes to database failed', 'wp-data-access' ),
					'show_title'       => true,
					'show_back_button' => true,
				]
			);

			// Get arguments from URL (old and new values).
			$this->get_url_arguments();

			// Add data object for DML.
			$this->row_data = new WPDA_Simple_Form_Data(
				$this->schema_name,
				$this->table_name,
				$this->wpda_list_columns,
				$this,
				$args['wpda_success_msg'],
				$args['wpda_failure_msg']
			);

			$this->current_form_id = 'wpda_simple_form_' . self::$form_id ++;

			if ( isset( $args['show_title'] ) ) {
				$this->show_title = $args['show_title'];
			}

			if ( isset( $args['show_back_button'] ) ) {
				$this->show_back_button = $args['show_back_button'];
			}

			// Overwrite column header text if column headers were provided.
			$this->column_headers = isset( $args['column_headers'] ) ? $args['column_headers'] : '';

			// Get current page number of list table
			if ( 'page_number' !== $this->page_number_item_name ) {
				if ( isset( $_REQUEST['page_number'] ) ) {
					$requested_page_number  = sanitize_text_field( wp_unslash( $_REQUEST['page_number'] ) ); // input var okay.
					$this->page_number_link = '&page_number=' . $requested_page_number;
					$this->page_number_item = "<input type='hidden' name='page_number' value='$requested_page_number'/>";
				}
			}
			if ( isset( $_REQUEST[ $this->page_number_item_name ] ) ) {
				$requested_page_number  = sanitize_text_field( wp_unslash( $_REQUEST[ $this->page_number_item_name ] ) ); // input var okay.
				$this->page_number_link .= '&paged=' . $requested_page_number;
				$this->page_number_item .= "<input type='hidden' name='" . $this->page_number_item_name . "' value='$requested_page_number'/>";
			}
		}

		/**
		 * Get URL arguments.
		 *
		 * @since   1.5.0
		 */
		protected function get_url_arguments() {

			// Get OLD and NEW values for all items.
			foreach ( $this->wpda_list_columns->get_table_columns() as $column ) {
				if ( isset( $_REQUEST[ $column['column_name'] . '_old' ] ) ) {
					$this->form_items_old_values[ $column['column_name'] ] = wp_unslash( $_REQUEST[ $column['column_name'] . '_old' ] ); // input var okay.
				}
				if ( isset( $_REQUEST[ $column['column_name'] ] ) ) {
					if ( is_array( $_REQUEST[ $column['column_name'] ] ) ) {
						$column_array = '';
						foreach ( $_REQUEST[ $column['column_name'] ] as $column_value ) {
							$column_array .= $column_value . ',';
						}
						if ( '' !== $column_array ) {
							$this->form_items_new_values[ $column['column_name'] ] = substr( $column_array, 0, strlen( $column_array ) - 1 );
						}
					} else {
						$this->form_items_new_values[ $column['column_name'] ] = wp_unslash( $_REQUEST[ $column['column_name'] ] ); // input var okay.
					}
				}
			}

		}

		protected function prepare_form( $allow_save = true ) {

			$set_back_form_values = false;

			if ( $allow_save && ( 'new' === $this->action || 'edit' === $this->action ) && 'save' === $this->action2 ) {

				// Security check (is action allowed?).
				$wp_nonce = isset( $_REQUEST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ) : ''; // input var okay.
				if ( ! wp_verify_nonce( $wp_nonce, $this->get_nonce_action() ) ) {
					wp_die( __( 'ERROR: Not authorized', 'wp-data-access' ) );
				}

				if ( 'new' === $this->action ) {
					// Save new record.
					if ( ! $this->validate() ) {

						// Validation failed: return error.
						if ( '' === $this->wpda_msg ) {
							$this->wpda_msg = __( 'Validation failed', 'wp-data-access' ); // Default error message.
						}

						$msg = new WPDA_Message_Box(
							[
								'message_text'           => $this->wpda_msg,
								'message_type'           => 'error',
								'message_is_dismissible' => false,
							]
						);
						$msg->box();

						$set_back_form_values = true;
					} else {
						// Insert record.
						$add_row_result = $this->row_data->add_row();
						if ( $add_row_result ) {
							// Insert succeeded:
							// (1) save auto_increment value (if applicable).
							// (2) change action to edit.
							if ( is_numeric( $add_row_result ) ) {
								// save auto_increment value (1).
								$this->auto_increment_value = $add_row_result;
							}
							// change action to edit (2).
							$this->action = 'edit';
						}

						$this->prepare_row();
					}
				} else {
					// Update existing record.
					if ( ! $this->validate() ) {
						// Validation failed: return error.
						if ( '' === $this->wpda_msg ) {
							$this->wpda_msg = __( 'Validation failed', 'wp-data-access' );
						}

						$msg = new WPDA_Message_Box(
							[
								'message_text'           => $this->wpda_msg,
								'message_type'           => 'error',
								'message_is_dismissible' => false,
							]
						);
						$msg->box();
					} else {
						// Update record.
						$this->row_data->set_row();
					}

					$this->prepare_row();

					$set_back_form_values = true;
				}
			} else {
				$this->prepare_row();
			}

			$this->prepare_items( $set_back_form_values );

			if ( '1' === $this->wpda_err &&
			     $this->update_keys_allowed &&
			     'edit' === $this->action &&
			     'save' === $this->action2
			) {
				$keys_have_changed = false;

				// There was an error! If key updates are allowed, we might have needed to set back the keys to their
				// original values. We'll inform the user with a message box.
				foreach ( $this->wpda_list_columns->get_table_primary_key() as $pk_column ) {
					if ( $this->get_old_value( $pk_column ) !== $this->get_new_value( $pk_column ) ) {
						$keys_have_changed = true;
						break;
					}
				}

				if ( $keys_have_changed ) {
					$msg = new WPDA_Message_Box(
						[
							'message_text' => __( 'Key columns have been reversed to their original values', 'wp-data-access' ),
						]
					);
					$msg->box();
				}
			}

			if ( null === $this->title ) {
				if ( 'new' === $this->action ) {
					$action_in_title = __( 'Add new row to', 'wp-data-access' );
				} else {
					$action_in_title = ucfirst( $this->action );
				}
				$this->title = $action_in_title . ' ' . __( 'table', 'wp-data-access' ) . ' ' . strtoupper( $this->table_name );
			} else {
				if ( 'new' === $this->action ) {
					$title_action = 'Add New ';
				} else {
					$title_action = ucfirst( $this->action );
				}
				if ( $this->add_action_to_title ) {
					$this->title = $title_action . ' ' . $this->title;
				}
			}

		}

		/**
		 * Show simple form
		 *
		 * Performs the following steps:
		 * + Checks for posted data
		 * + Saves changes if applicable (displays success or failure message)
		 * + Prepares simple form
		 * + Shows simple form for requested table (HTML ande Javascript)
		 *
		 * @param boolean $allow_save Allow to save data
		 * @param string  $add_param Parameter to be added to form action.
		 *
		 * @since   1.0.0
		 *
		 */
		public function show( $allow_save = true, $add_param = '' ) {

			$this->prepare_form( $allow_save );

			?>

			<div class="wrap">
				<?php if ( $this->show_title ) { ?>
					<h1>
						<a
								href="javascript:void(0)"
								onclick="javascript:location.href='?page=<?php echo esc_attr( $this->page ); ?><?php echo '' === $this->schema_name ? '' : '&schema_name=' . esc_attr( $this->schema_name ); ?>&table_name=<?php echo esc_attr( $this->table_name ); ?><?php echo esc_attr( $add_param ); ?><?php echo $this->page_number_link; ?>'"
								style="display: inline-block; vertical-align: unset;"
								class="dashicons dashicons-arrow-left-alt"
								title="<?php echo __( 'Back To List', 'wp-data-access' ); ?>"
						></a>&nbsp;
						<?php echo esc_attr( $this->title ); ?>
					</h1>
					<div><strong><?php echo wp_kses( $this->subtitle, [ 'span' => [ 'class' => [] ] ] ); ?></strong>
					</div>
					<p></p>
				<?php } ?>
				<form id="<?php echo esc_attr( $this->current_form_id ); ?>"
					  method="post" enctype="multipart/form-data"
					  action="?page=<?php echo esc_attr( $this->page ); ?><?php echo '' === $this->schema_name ? '' : '&schema_name=' . esc_attr( $this->schema_name ); ?>&table_name=<?php echo esc_attr( $this->table_name ); ?><?php echo esc_attr( $add_param ); ?>">
					<div class="wpda_simple_form_border">
						<table class="wpda_simple_table">
							<?php
							$js_code = ''; // All column JS code will be stored here and added to the end of the form.
							foreach ( $this->form_items as $item ) {
								$item->show( $this->action, $this->update_keys_allowed );
								$js_code .= $item->get_item_js(); // Add column specific JS code.
							}
							?>
						</table>
						<div><?php $this->add_form_logic(); ?></div>
					</div>
					<p><?php if ( 'view' !== $this->action ) {
							echo __( 'All * marked items must be entered', 'wp-data-access' );
						} ?></p>
					<input type="hidden" name="action" value="<?php echo esc_attr( $this->action ); ?>"/>
					<input type="hidden" name="action2" value="save"/>
					<?php echo $this->page_number_item; ?>
					<?php $this->add_parent_args(); ?>
					<?php wp_nonce_field( $this->get_nonce_action( false ), '_wpnonce', false ); ?>
					<?php if ( 'view' !== $this->action ) { ?>
						<input type="submit"
							   value="<?php echo __( 'Save Changes To Database', 'wp-data-access' ); ?>"
							   class="button button-primary"
							   name="submit_button"
							   onclick="return submit_form();"
						/>
						<input type="button" value="<?php echo __( 'Reset Form', 'wp-data-access' ); ?>"
							   class="button"
							   name="reset_button"
							   onclick="jQuery('#<?php echo esc_attr( $this->current_form_id ); ?>').trigger('reset')"
						/>
						<?php if ( $this->show_back_button ) { ?>
							<a
									href="javascript:void(0)"
									onclick="javascript:location.href='?page=<?php echo esc_attr( $this->page ); ?><?php echo '' === $this->schema_name ? '' : '&schema_name=' . esc_attr( $this->schema_name ); ?>&table_name=<?php echo esc_attr( $this->table_name ); ?><?php echo esc_attr( $this->add_parent_args_to_back_button() ); ?><?php echo esc_attr( $add_param ); ?><?php echo $this->page_number_link; ?>'"
									class="button button-secondary"
							>
								<?php echo __( 'Back To List', 'wp-data-access' ); ?>
							</a>
						<?php } ?>
						<span style="float:right;"><?php $this->add_buttons(); ?></span>
					<?php } ?>
				</form>
			</div>

			<script language="JavaScript">
				function submit_form() {
					var failed = false;
					jQuery('.wpda_not_null').each(function (i, obj) {
						if (jQuery(obj).val() === '') {
							alert(<?php echo __('\'Item\'', 'wp-data-access'); ?> + ' ' + jQuery(obj).attr('name') + ' ' + <?php echo __('\'must be entered\'', 'wp-data-access'); ?>);
							failed = true;
						}
					});
					jQuery('.wpda_input_error').each(function (i, obj) {
						alert(<?php echo __('\'Column\'', 'wp-data-access'); ?> + ' ' + jQuery(obj).attr('name') + <?php echo __('\': max size exceeded\'', 'wp-data-access'); ?>);
						failed = true;
					});
					return !failed;
				}

				jQuery(document).ready(function () {
					<?php if ( 'view' === $this->action ) { ?>
					jQuery("#<?php echo esc_attr( $this->current_form_id ); ?> input").prop("readonly", true);
					jQuery("#<?php echo esc_attr( $this->current_form_id ); ?> select").prop("disabled", true);
					<?php } ?>
					<?php if ( ! $this->update_keys_allowed && 'new' !== $this->action ) { ?>
					jQuery("#<?php echo esc_attr( $this->current_form_id ); ?> input.wpda_primary_key").prop("readonly", true);
					jQuery("#<?php echo esc_attr( $this->current_form_id ); ?> select.wpda_primary_key").prop("disabled", true);
					<?php } ?>
					<?php if ( 'new' === $this->action ) { ?>
					jQuery("#<?php echo esc_attr( $this->current_form_id ); ?> input.auto_increment").prop("readonly", true);
					<?php } ?>
					jQuery("#<?php echo esc_attr( $this->current_form_id ); ?> input.wpda_readonly").prop("readonly", true);
					jQuery('.wpda_data_type_number').bind('keyup paste', function () {
						this.value = this.value.replace(/[^\d\.\,]/g, ''); // Allow only 0-9 . ,
						if (isNaN(this.value)) {
							jQuery(this).addClass('wpda_input_error');
						} else {
							jQuery(this).removeClass('wpda_input_error');
						}
					});
				});
				<?php echo $js_code; ?>
			</script>

			<?php

		}

		/**
		 * Overwrite to add logic to form
		 *
		 * @since 2.0.15
		 */
		public function add_form_logic() { }

		/**
		 * Overwrite to add buttons to right bottom
		 *
		 * @since 2.0.15
		 */
		public function add_buttons() { }

		/**
		 * Creates a wpnonce action
		 *
		 * Set wp_nonce action for security check:
		 * prefix + table name + primary key values
		 *
		 * @param boolean $use_old_value TRUE = use old key values, FALSE = use new key values.
		 *
		 * @return string wp_nonce action holding: prefix + table name + primary key values.
		 * @since   1.0.0
		 *
		 */
		protected function get_nonce_action( $use_old_value = true ) {

			// Add prefix + table name to wp_nonce action.
			$wp_nonce_action = "wpda-simple-form-{$this->table_name}";

			foreach ( $this->wpda_list_columns->get_table_primary_key() as $pk_column ) {
				// Add primary key value to wp_nonce action.
				if ( 'new' === $this->action ) {
					// New records have no key values on form startup.
					$wp_nonce_action .= '-?';
				} else {
					// Add primary key value to wp_nonce action.
					if ( 'save' === $this->action2 && $use_old_value ) {
						// Use old value (in cases where primary key update is allowed).
						$wp_nonce_action .= isset( $_REQUEST[ $pk_column . '_old' ] ) ? '-' . sanitize_text_field( wp_unslash( $_REQUEST[ $pk_column . '_old' ] ) ) : '-?'; // input var okay.
					} else {
						if ( isset( $_REQUEST[ $pk_column ] ) && '' !== sanitize_text_field( wp_unslash( $_REQUEST[ $pk_column ] ) ) ) { // input var okay.
							// Use new value.
							$wp_nonce_action .= '-' . sanitize_text_field( wp_unslash( $_REQUEST[ $pk_column ] ) ); // input var okay.
						} elseif ( - 1 !== $this->auto_increment_value ) {
							// Use auto increment value (updated wp_nonce action after insert).
							$wp_nonce_action .= '-' . $this->auto_increment_value;
						} else {
							// No value found, let security check handle wrong wp_nonce action.
							$wp_nonce_action .= '-?';
						}
					}
				}
			}

			return str_replace( ' ', '_', $wp_nonce_action );

		}

		/**
		 * Perform validation check
		 *
		 * Called to perform default validation before insert and update.
		 *
		 * Extend class WPDA_Simple_Form and override this method if you need validation on insert and/or update or
		 * if you prefer to change error messages. If you want to handle inserts and updates differently, the
		 * following information might be helpful:
		 * + on insert: $this->action = 'new'
		 * + on update: $this->action = 'edit'
		 *
		 * Use set_message to show messages (info as well as error).
		 *
		 * @return boolean TRUE = validation succeeded, FALSE = validation failed.
		 * @since   1.0.0
		 *
		 */
		protected function validate() {

			return $this->row_data->is_valid();

		}

		/**
		 * Get current from database table
		 *
		 * Handle insert, update and save diffently.
		 *
		 * @since   1.0.0
		 */
		protected function prepare_row() {

			if ( 'edit' === $this->action || 'view' === $this->action || 'save' === $this->action2 ) {
				// Get record by primary key, use auto_increment for new records.
				$this->row = $this->row_data->get_row( $this->auto_increment_value, $this->wpda_err );
			} else {
				// There's no record yet.
				$this->row = $this->row_data->new_row();
			}

		}

		/**
		 * Set item attributes
		 *
		 * If you want to change the layout of your simple form(s), consider to extend class WPDA_Simple_Form and
		 * override this method.
		 *
		 * @param boolean $set_back_form_values TRUE = set back user entered value, FALSE = set to database value.
		 *
		 * @since   1.0.0
		 *
		 */
		protected function prepare_items( $set_back_form_values = false ) {

			$count_cols = count( $this->table_columns );
			for ( $i = 0; $i < $count_cols; $i ++ ) {
				$column_name = $this->table_columns[ $i ]['column_name'];
				$item_enum   = '';
				if ( 'enum' === $this->table_columns[ $i ]['data_type'] || 'set' === $this->table_columns[ $i ]['data_type'] ) {
					$item_enum = $this->table_columns[ $i ]['column_type'];
				}
				if ( $set_back_form_values ) {
					// Set value back to what user entered.
					$item_value = $this->get_new_value( $column_name );
				} else {
					// Get value from database.
					$item_value = isset( $this->row ) ? $this->row[0][ $column_name ] : '';
				}
				$item = new WPDA_Simple_Form_Item(
					[
						'item_name'          => $column_name,
						'data_type'          => $this->table_columns[ $i ]['data_type'],
						'item_label'         => isset( $this->column_headers[ $column_name ] ) ? $this->column_headers[ $column_name ] : $this->table_column_headers[ $column_name ],
						'item_value'         => $item_value,
						'item_default_value' => $this->table_columns[ $i ]['column_default'],
						'item_extra'         => $this->table_columns[ $i ]['extra'],
						'item_enum'          => $item_enum,
						'column_type'        => $this->table_columns[ $i ]['column_type'],
						'is_nullable'        => $this->table_columns[ $i ]['is_nullable'],
					]
				);
				if ( 'enum' === $this->table_columns[ $i ]['data_type'] || 'set' === $this->table_columns[ $i ]['data_type'] ) {
					$item->set_item_hide_icon( true );
				}
				$item->set_is_key_column( $this->is_key_column( $column_name ) );
				$this->add_form_item( $i, $item );
			}

		}

		/**
		 * Get new value for item
		 *
		 * Or empty if no new value available.
		 *
		 * @param string $column_name Column name.
		 *
		 * @return mixed|string New value for column or empty.
		 * @since   1.0.0
		 *
		 */
		public function get_new_value( $column_name ) {

			return isset( $this->form_items_new_values[ $column_name ] ) ? $this->form_items_new_values[ $column_name ] : '';

		}

		/**
		 * Add item to form
		 *
		 * @param int                   $index Item sequence number.
		 * @param WPDA_Simple_Form_Item $item Reference to simple form item.
		 *
		 * @since   1.0.0
		 *
		 * @see WPDA_Simple_Form_Item
		 *
		 */
		protected function add_form_item( $index, $item ) {

			// Add item to form
			$this->form_items[ $index ] = $item;

			// Add position to named array for quick access of single items
			$item_name                            = $item->get_item_name();
			$this->form_items_named[ $item_name ] = $index;

		}

		/**
		 * Get item position
		 *
		 * @param string $item_name Form item name
		 *
		 * @return bool|int Position item or false if not found
		 *
		 * @since 2.0.15
		 */
		protected function get_item_position( $item_name ) {
			if ( isset( $this->form_items_named[ $item_name ] ) ) {
				return $this->form_items_named[ $item_name ];
			} else {
				return false;
			}
		}

		/**
		 * Get old value form item
		 *
		 * Or empty if no old value available.
		 *
		 * @param string $column_name Column name.
		 *
		 * @return mixed|string Old value for column or empty.
		 * @since   1.0.0
		 *
		 */
		public function get_old_value( $column_name ) {

			return isset( $this->form_items_old_values[ $column_name ] ) ? $this->form_items_old_values[ $column_name ] : '';

		}

		/**
		 * Is column part of primary key?
		 *
		 * @param string $column_name Column name.
		 *
		 * @return bool TRUE = column is part of primary key, FALSE = column is not part of primary key.
		 * @since   1.0.0
		 *
		 */
		protected function is_key_column( $column_name ) {

			foreach ( $this->wpda_list_columns->get_table_primary_key() as $pk_column ) {
				if ( $column_name === $pk_column ) {
					return true;
				}
			}

			return false;

		}

		/**
		 * Get primary form action
		 *
		 * @return string Primary page action
		 * @since   1.0.0
		 *
		 */
		public function get_form_action() {

			return $this->action;

		}

		/**
		 * Get secondary form action
		 *
		 * @return string Secondary page action
		 * @since   1.0.0
		 *
		 */
		public function get_form_action2() {

			return $this->action2;

		}

		/**
		 * Defines whether primary key item can be updated
		 *
		 * @param boolean $update_keys_allowed Allow keys to be updated.
		 *
		 * @since   1.0.0
		 *
		 */
		protected function set_update_keys( $update_keys_allowed ) {

			$this->update_keys_allowed = $update_keys_allowed;

		}

		/**
		 * Reorder columns
		 *
		 * Reorders the column array in the order as defined in argument $columns_ordered.
		 *
		 * @param array $columns_ordered Ordered column names.
		 *
		 * @since   1.0.0
		 *
		 */
		protected function order_and_filter_columns( $columns_ordered ) {

			$column_array_ordered = [];
			$i                    = 0;

			foreach ( $columns_ordered as $key => $value ) {
				$column_array_ordered[ $i ++ ] =
					$this->table_columns[ $this->get_column_position( $this->table_columns, $value ) ];
			}

			$this->table_columns = $column_array_ordered;

		}

		/**
		 * Get column position in column array
		 *
		 * @param array  $column_array Column array.
		 * @param string $column_name Column name.
		 *
		 * @return int Position of $column_name in $column_array
		 * @since   1.0.0
		 *
		 */
		protected function get_column_position( $column_array = [], $column_name ) {

			$count_cols = count( $column_array );
			for ( $i = 0; $i < $count_cols; $i ++ ) {
				if ( $column_array[ $i ]['column_name'] === $column_name ) {
					return $i;
				}
			}

			return - 1;

		}

		/**
		 * Add dummy column to form
		 *
		 * @param string $column_name Column name.
		 *
		 * @since   1.0.0
		 *
		 */
		protected function add_dummy_column( $column_name ) {

			$this->table_columns[] = [
				'column_name' => $column_name,
				'data_type'   => 'varchar',
				'extra'       => '',
				'column_type' => '',
			];

		}

		/**
		 * Set message number and text
		 *
		 * Assigns values to $this->wpda_err and $this->wpda_msg.
		 *
		 * @param string $wpda_err '0' = INFO, '1' = ERROR.
		 * @param string $wpda_msg Message to be displayed.
		 *
		 * @since   1.0.0
		 *
		 */
		protected function set_message( $wpda_err, $wpda_msg ) {

			// Wrong values will be handled by message box class.
			$this->wpda_err = $wpda_err;
			$this->wpda_msg = $wpda_msg;

		}

		/**
		 * Use this method to build parent child relationships.
		 *
		 * Overwrite this function if you want to use the form as a child form related to some parent
		 * form. You can add parent arguments to calls to make sure you get back to the right parent.
		 *
		 * @since   1.5.0
		 */
		protected function add_parent_args() { }

		/**
		 * Use this method to build parent child relationships.
		 *
		 * Overwrite this function if you want to use the form as a child form related to some parent
		 * form. You can add parent arguments to calls to make sure you get back to the right parent.
		 *
		 * @since   1.6.9
		 */
		protected function add_parent_args_to_back_button() { }

		/**
		 * Set item labels
		 *
		 * @param array $item_labels Named array containing item name/label pairs
		 *
		 * @since 2.0.15
		 */
		public function set_labels( $item_labels ) {
			if ( is_array( $item_labels ) ) {
				foreach ( $item_labels as $key => $label ) {
					foreach ( $this->form_items as $form_item ) {
						if ( $form_item->get_item_name() === $key ) {
							$form_item->set_label( $label );
						}
					}
				}
			}
		}

		/**
		 * Hide items
		 *
		 * @param array $items_to_hide Arrays contains item names of items that should be defined as hidden
		 *
		 * @since 2.0.15
		 */
		public function hide_items( $items_to_hide ) {
			if ( is_array( $items_to_hide ) ) {
				foreach ( $items_to_hide as $column_name ) {
					foreach ( $this->form_items as $form_item ) {
						if ( $form_item->get_item_name() === $column_name ) {
							$form_item->set_hide_item( true );
						}
					}
				}
			}
		}

		/**
		 * Number of items
		 *
		 * @return int
		 *
		 * @since 2.0.15
		 */
		public function count() {
			return count( $this->form_items );
		}

	}

}
