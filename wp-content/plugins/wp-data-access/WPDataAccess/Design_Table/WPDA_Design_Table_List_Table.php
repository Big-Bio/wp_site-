<?php

/**
 * Suppress "error - 0 - No summary was found for this file" on phpdoc generation
 *
 * @package WPDataAccess\Design_Table
 */

namespace WPDataAccess\Design_Table {

	use WPDataAccess\List_Table\WPDA_List_Table;

	/**
	 * Class WPDA_Design_Table_List_Table
	 *
	 * @author  Peter Schulz
	 * @since   1.1.0
	 */
	class WPDA_Design_Table_List_Table extends WPDA_List_Table {

		/**
		 * WPDA_Design_Table_List_Table constructor
		 *
		 * @param array $args See {@see WPDA_List_Table::__construct()}.
		 *
		 * @since   1.1.0
		 *
		 * @see WPDA_List_Table
		 */
		public function __construct( $args = [] ) {

			// Add column labels.
			$args['column_headers'] = [
				'wpda_table_name'   => __( 'Table name', 'wp-data-access' ),
				'wpda_table_design' => __( 'Table structure', 'wp-data-access' ),
				'wpda_date_created' => __( 'Creation date', 'wp-data-access' ),
				'wpda_last_updated' => __( 'Last updated', 'wp-data-access' ),
			];

			$args['title'] = __( 'Data Designer', 'wp-data-access' );

			parent::__construct( $args );

		}

		/**
		 * Hide column wpda_table_design
		 *
		 * @return array
		 */
		public function get_hidden_columns() {

			return [ 'wpda_table_design' ];

		}

		/**
		 * Add buttons new and import (overwritten)
		 *
		 * @param string $add_param
		 */
		protected function add_header_button( $add_param = '' ) {

			?>

			<form
					method="post"
					action="?page=<?php echo esc_attr( $this->page ); ?>&table_name=<?php echo esc_attr( $this->table_name ); ?>"
					style="display: inline-block; vertical-align: unset;"
			>
				<div>
					<input type="hidden" name="action" value="edit">
					<input type="submit" value="<?php echo __( 'Design new table', 'wp-data-access' ); ?>"
						   class="page-title-action">

					<?php

					// Add import button to title.
					if ( null !== $this->wpda_import ) {
						$this->wpda_import->add_button();
					}

					?>

				</div>
			</form>

			<?php

		}

	}

}
