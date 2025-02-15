<?php

/**
 * Suppress "error - 0 - No summary was found for this file" on phpdoc generation
 *
 * @package WPDataProjects\Parent_Child
 */

namespace WPDataProjects\Parent_Child {

	use WPDataProjects\List_Table\WPDP_List_Table_Lookup;
	use WPDataAccess\Data_Dictionary\WPDA_Dictionary_Exist;

	/**
	 * Class WPDP_Parent_List_Table extends WPDP_List_Table_Lookup
	 *
	 * @see WPDP_List_Table_Lookup
	 *
	 * @author  Peter Schulz
	 * @since   2.0.0
	 */
	class WPDP_Parent_List_Table extends WPDP_List_Table_Lookup {

		/**
		 * Project info
		 *
		 * @var array
		 */
		protected $project;

		/**
		 * User message
		 *
		 * @var string
		 */
		protected $message_confirm_delete;

		/**
		 * WPDP_Parent_List_Table constructor
		 *
		 * @param array $args
		 */
		public function __construct( $args = [] ) {
			if ( isset( $args['project'] ) ) {
				$this->project = $args['project'];
			} else {
				wp_die( __( 'ERROR: Wrong arguments', 'wp-data-access' ) );
			}

			$args['allow_import'] = 'off';

			global $wpdb;
			$args['schema_name'] = $wpdb->dbname;

			parent::__construct( $args );

			$this->message_confirm_delete =
				__( "Delete current item and all its relationships?\\nThis action cannot be undone.\\n\'Cancel\' to stop, \'OK\' to delete.", 'wp-data-access' );

			if ( isset( $args['where_clause'] ) && '' !== $args['where_clause'] ) {
				$this->where = $args['where_clause'];
			}
		}

		/**
		 * Overwrites method bind_action_buttons to set message_confirm_delete
		 */
		protected function bind_action_buttons() {
			?>
			<script language="JavaScript">
				jQuery(document).ready(function () {
					jQuery("#doaction").click(function () {
						return wpdp_action_button("<?php echo $this->message_confirm_delete; ?>");
					});
					jQuery("#doaction2").click(function () {
						return wpdp_action_button("<?php echo $this->message_confirm_delete; ?>");
					});
				});
			</script>
			<?php
		}

		/**
		 * Overwrites method column_default_add_action to set mode
		 *
		 * @param array  $item
		 * @param string $column_name
		 * @param array  $actions
		 */
		protected function column_default_add_action( $item, $column_name, &$actions ) {
			if ( 'view' === $this->project->get_mode() ) {
				unset( $actions['edit'] );
				unset( $actions['delete'] );
			} else {
				?>
				<script language="JavaScript">
					jQuery("#view_form_" + <?php echo( self::$list_number - 1 ) ?>).append('<input type="hidden" name="mode" value="view">');
					jQuery("#edit_form_" + <?php echo( self::$list_number - 1 ) ?>).append('<input type="hidden" name="mode" value="edit">');
					jQuery("#delete_form_" + <?php echo( self::$list_number - 1 ) ?>).append('<input type="hidden" name="mode" value="edit">');
				</script>
				<?php
				$actions['delete'] = sprintf(
					'
					    <a  href="javascript:void(0)"
					        onclick="if (confirm(\'%s\')) jQuery(\'%s\').submit()"
					        >
					        Delete
                        </a>
                    ',
					$this->message_confirm_delete,
					'#delete_form_' . strval( self::$list_number - 1 )
				);
			}
		}

		/**
		 * Overwrite method get_bulk_actions to add action 'Delete Permanently'
		 *
		 * @return array
		 */
		public function get_bulk_actions() {
			if ( 'view' === $this->project->get_mode() ) {
				return '';
			}

			if ( ! $this->bulk_actions_enabled ) {
				// Bulk actions disabled.
				return '';
			}

			if ( empty( $this->wpda_list_columns->get_table_primary_key() ) ) {
				// Tables has no primary key: no bulk actions allowed!
				// Primary key is neccesary to ensure uniqueness.
				return '';
			}

			$actions = [
				'bulk-delete' => __( 'Delete Permanently', 'wp-data-access' ),
			];

			return $actions;
		}

		/**
		 * Overwrites method delete_row to delete child rows on parent delete
		 *
		 * @param string $where
		 *
		 * @return mixed
		 */
		public function delete_row( $where ) {
			foreach ( $this->project->get_children() as $child ) {
				if ( isset( $child['relation_nm'] ) ) {
					$table_name        = $child['relation_nm']['child_table'];
					$row_to_be_deleted = [];
					$i                 = 0;
					foreach ( $where as $key ) {
						$row_to_be_deleted[ $child['relation_nm']['child_table_where'][ $i ] ] = $key;
						$i ++;
					}
					$this->delete_row_relationship( $table_name, $row_to_be_deleted );
				} elseif ( isset( $child['relation_1n'] ) ) {
					$table_name        = $child['table_name'];
					$row_to_be_deleted = [];
					$i                 = 0;
					foreach ( $where as $key ) {
						$row_to_be_deleted[ $child['relation_1n']['child_key'][ $i ] ] = $key;
						$i ++;
					}
					$this->delete_row_relationship( $table_name, $row_to_be_deleted );
				}
			}

			return parent::delete_row( $where );
		}

		/**
		 * Delete relationship
		 *
		 * @param string $table_name Database table name
		 * @param string $where SQL where clause
		 *
		 * @return mixed Return value of SQL delete
		 */
		public function delete_row_relationship( $table_name, $where ) {
			global $wpdb;

			$cannot_delete_from_view = new WPDA_Dictionary_Exist( '', $table_name );
			if ( $cannot_delete_from_view->is_view() ) {
				// Cannot delete rows from view
				return;
			}

			return $wpdb->delete( $table_name, $where ); // db call ok; no-cache ok.
		}

		/**
		 * Overwrites method add_header_button to add arguments
		 *
		 * @param string $add_param
		 */
		protected function add_header_button( $add_param = '' ) {
			?>
			<form
					method="post"
					action="?page=<?php echo esc_attr( $this->page ); ?>"
					style="display: inline-block; vertical-align: unset;"
			>
				<div>
					<input type="hidden" name="action" value="new">
					<input type="hidden" name="mode" value="edit">
					<input type="hidden" name="table_name" value="<?php echo esc_attr( $this->table_name ); ?>">
					<input type="submit" value="Add New" class="page-title-action">
				</div>
			</form>
			<?php
			if ( null !== $this->wpda_import ) {
				$this->wpda_import->add_button( __( 'Import', 'wp-data-access' ) );
			}
		}

	}

}
