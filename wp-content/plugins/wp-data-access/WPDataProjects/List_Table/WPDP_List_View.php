<?php

/**
 * Suppress "error - 0 - No summary was found for this file" on phpdoc generation
 *
 * @package WPDataProjects\List_Table
 */

namespace WPDataProjects\List_Table {

	use WPDataAccess\List_Table\WPDA_List_View;
	use WPDataAccess\Utilities\WPDA_Repository;
	use WPDataProjects\Project\WPDP_Project;
	use WPDataProjects\Data_Dictionary\WPDP_List_Columns_Cache;

	/**
	 * Class WPDP_List_View extends WPDA_List_View
	 *
	 * Data Projects uses WPDP_List_View instead of WPDA_List_View to handle column labels correctly. If the where
	 * clause contains the $$USER$$ variable insert, delete and import are disabled.
	 *
	 * @see WPDA_List_View
	 *
	 * @author  Peter Schulz
	 * @since   2.0.0
	 */
	class WPDP_List_View extends WPDA_List_View {

		/**
		 * Project ID
		 *
		 * @var null|string
		 */
		protected $project_id = null;

		/**
		 * Page ID
		 *
		 * @var null
		 */
		protected $page_id = null;

		/**
		 * Page title
		 *
		 * @var null
		 */
		protected $title;

		/**
		 * Page subtitle
		 *
		 * @var null
		 */
		protected $subtitle;

		/**
		 * Possible values for mode are: view and edit
		 *
		 * @var
		 */
		protected $mode;

		/**
		 * SQL where clause
		 *
		 * @var null
		 */
		protected $where_clause = null;

		/**
		 * Possible values for label type are: listtable and tableform
		 * @var string
		 */
		protected $label_type = 'listtable';

		/**
		 * Allow insert?
		 *
		 * @var string|null
		 */
		protected $allow_insert = null;

		/**
		 * Allow delete?
		 *
		 * @var string|null
		 */
		protected $allow_delete = null;

		/**
		 * Allow import?
		 *
		 * @var string|null
		 */
		protected $allow_import = null;

		/**
		 * Overwrite constructor
		 *
		 * @param array $args
		 */
		public function __construct( array $args = [] ) {
			if ( isset( $args['project_id'] ) ) {
				$this->project_id = sanitize_text_field( wp_unslash( $args['project_id'] ) );
			} elseif ( isset( $_REQUEST['tab'] ) && 'tables' === $_REQUEST['tab'] ) {
				$this->project_id = 'wpda_sys_tables';
			}
			if ( isset( $args['page_id'] ) ) {
				$this->page_id = sanitize_text_field( wp_unslash( $args['page_id'] ) );
			}

			$this->project  = new WPDP_Project( $this->project_id, $this->page_id );
			$this->title    = $this->project->get_title();
			$this->subtitle = $this->project->get_subtitle();
			$this->mode     = $this->project->get_mode();

			$args['title']    = ( null === $this->title || '' === $this->title ) ? null : $this->title;
			$args['subtitle'] = $this->subtitle;

			parent::__construct( $args );

			if (
				'edit' === $this->action ||
				'new' === $this->action ||
				'view' === $this->action
			) {
				$this->label_type = 'tableform';
			}

			// Overwrite column header text.
			$this->column_headers = isset( $args['column_headers'] ) ? $args['column_headers'] : '';

			if ( isset( $args['where_clause'] ) && '' !== $args['where_clause'] ) {
				$this->where_clause = $args['where_clause'];
			}

			if ( isset( $args['allow_insert'] ) ) {
				$this->allow_insert = sanitize_text_field( wp_unslash( $args['allow_insert'] ) );
			}
			if ( isset( $args['allow_delete'] ) ) {
				$this->allow_delete = sanitize_text_field( wp_unslash( $args['allow_delete'] ) );
			}
			if ( isset( $args['allow_import'] ) ) {
				$this->allow_import = sanitize_text_field( wp_unslash( $args['allow_import'] ) );
			}
		}

		/**
		 * Overwrite show method
		 *
		 * @see WPDA_List_View::show()
		 */
		public function show() {
			// Prepare columns for list table. Needed in get_column_headers() and handed over to list table to prevent
			// processing the same queries multiple times.
			if ( null === $this->wpda_list_columns ) {
				$this->wpda_list_columns = WPDP_List_Columns_Cache::get_list_columns( $this->schema_name, $this->table_name, $this->label_type );
			}

			$wpda_repository = new WPDA_Repository();
			$wpda_repository->inform_user();

			switch ( $this->action ) {

				case 'new':  // Show edit form in editing mode to create new records.
				case 'edit': // Show edit form in editing mode to update records.
				case 'view': // Show edit form in view mode to view records.
					$this->display_edit_form();
					break;

				case 'create_table': // Show form to create new table.
					$this->display_design_menu();
					break;

				default: // Show list (default).
					$this->display_list_table();

			}
		}

		/**
		 * Overwrite display_list_table method
		 *
		 * @see WPDA_List_View::display_list_table()
		 */
		protected function display_list_table() {
			$args = [
				'schema_name'       => $this->schema_name,
				'table_name'        => $this->table_name,
				'wpda_list_columns' => $this->wpda_list_columns,
				'column_headers'    => $this->column_headers,
				'title'             => $this->title,
				'subtitle'          => $this->subtitle,
				'mode'              => $this->mode,
				'where_clause'      => $this->where_clause,
			];
			if ( null !== $this->allow_insert ) {
				$args['allow_insert'] = $this->allow_insert;
			}
			if ( null !== $this->allow_delete ) {
				$args['allow_delete'] = $this->allow_delete;
			}
			if ( null !== $this->allow_import ) {
				$args['allow_import'] = $this->allow_import;
			}
			$this->wpda_list_table = new WPDP_List_Table( $args );

			$this->wpda_list_table->show();
		}

		/**
		 * Overwrite get_column_headers method
		 *
		 * @see WPDA_List_View::get_column_headers()
		 */
		public function get_column_headers() {
			if ( null === $this->wpda_list_columns ) {
				$this->wpda_list_columns = WPDP_List_Columns_Cache::get_list_columns( $this->schema_name, $this->table_name, $this->label_type );
			}

			return $this->wpda_list_columns->get_table_column_headers();
		}

	}

}