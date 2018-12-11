<?php

namespace codingninjastest;

use \codingninjas;

class AppTest extends codingninjas\App {
	/**
	 * Instance of AppTest
	 * @var null
	 */
	public static $instance = null;

	/**
	 * Plugin main file
	 * @var
	 */
	public static $main_file;

	/**
	 * Path to app folder
	 * @var string
	 */
	public static $app_path;

	/**
	 * Url to app folder
	 * @var string
	 */
	public static $app_url;

	/**
	 * Current route
	 * @var
	 */
	public static $route;

	/**
	 * AppTest constructor.
	 *
	 * @param $main_file
	 */
	public function __construct( $main_file ) {

		self::$main_file = $main_file;
		self::$app_path  = dirname( $main_file ) . '/app';
		self::$app_url   = plugin_dir_url( $main_file ) . 'app';
		self::$route     = codingninjas\App::$route;
		spl_autoload_register( array( &$this, 'autoloader' ) );

		$this->initActions();
		$this->initFilters();
	}

	/**
	 * Run AppTest
	 *
	 * @param $main_file
	 *
	 * @return AppTest|null
	 */
	public static function run( $main_file ) {
		if ( ! self::$instance ) {
			self::$instance = new self( $main_file );
		}

		return self::$instance;
	}

	/**
	 * Classes autoloader
	 *
	 * @param $class
	 *
	 * @return mixed
	 */
	public function autoloader( $class ) {
		$folders = [
			'decorators',
			'metabox',
			'shortcodes'
		];

		$parts = explode( '\\', $class );
		array_shift( $parts );
		$class_name = array_shift( $parts );

		foreach ( $folders as $folder ) {
			$file = self::$app_path . '/' . $folder . '/' . $class_name . '.php';
			if ( ! file_exists( $file ) ) {
				continue;
			}

			return require_once $file;

			if ( ! class_exists( $class ) ) {
				continue;
			}
		}
	}

	/**
	 * Init wp actions
	 */
	private function initActions() {
		add_action( 'init', array( $this, 'onInitPostTypes' ) );
		add_action( 'add_meta_boxes', array( $this, 'onInitMetaBoxForFreelancer' ) );
		add_action( 'post_edit_form_tag', array( $this, 'addMultipartFormForFreelancer' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'showAssets' ) );

		add_action( 'save_post_' . Freelancer::POST_TYPE, array( $this, 'saveFreelancer' ) );

		if ( is_admin() ) {
			add_action( 'load-post.php', array( $this, 'callMetaboxForTask' ) );
			add_action( 'load-post-new.php', array( $this, 'callMetaboxForTask' ) );
		}

		if ( 'tasks' === $this->getCurrentRouteCallback()[1] ) {
			add_action( 'wp_enqueue_scripts', array( $this, 'onInitScriptsTask' ), 21 );
			add_action( 'wp_enqueue_scripts', array( $this, 'onInitStylesTask' ), 21 );

			add_action( 'wp_footer', array( $this, 'addMenuItemToTaskPageAction' ) );
		}
		add_action( 'wp_ajax_add_task', array( $this, 'addItemToTask' ) );
		add_action( 'wp_ajax_nopriv_add_task', array( $this, 'addItemToTask' ) );

		/*
		  shortcode handler
		  possibly to pass a parameter(ms) that determines how often the shortcode will be updated
		  default 5000ms
		 */
		ShortcodeDashboard::run();
	}

	/**
	 * Init wp filters
	 */
	private function initFilters() {
		add_filter( 'wp_insert_post_data', array( $this, 'modifyPostTitleForFreelancer' ), 10, 2 );
		add_filter( 'pre_get_document_title', array( $this, 'changeTitleNinjaPost' ) );

		add_filter( 'cn_tasks_thead_cols', array( $this, 'addTableHeadCol' ) );
		add_filter( 'cn_tasks_tbody_row_cols', array( $this, 'addTableBodyRowCol' ), 10, 2 );


		if ( 'tasks' === $this->getCurrentRouteCallback()[1] ) {
			add_filter( 'cn_page_menu_html', array( $this, 'addMenuItemToTaskPageFilter' ), 10, 2 );
		}
	}

	/**
	 * Init js scripts
	 */
	public function onInitScriptsTask() {
		wp_enqueue_script( 'jquery' );

		wp_enqueue_script(
			'DataTables',
			self::$app_url . '/vendor/DataTables/datatables.min.js',
			[ 'jquery' ],
			'1.10.18',
			true
		);
		wp_enqueue_script(
			'tables',
			self::$app_url . '/assets/js/table.js',
			[ 'jquery', 'DataTables' ],
			'1.0.0',
			true
		);

		wp_localize_script( 'tables', 'ajaxData',
			array(
				'url'   => admin_url( 'admin-ajax.php' ),
				'nonce' => wp_create_nonce( 'add_task-nonce' )
			)
		);
	}

	/**
	 * Init styles
	 */
	public function onInitStylesTask() {
		wp_enqueue_style(
			'DataTables',
			self::$app_url . '/vendor/DataTables/datatables.min.css'
		);
	}


	/**
	 * Init post type freelancer
	 */
	public function onInitPostTypes() {
		$labels = array(
			'name'               => __( 'Freelancers', 'cnt' ),
			'singular_name'      => __( 'Freelancer', 'cnt' ),
			'menu_name'          => __( 'Freelancers', 'cnt' ),
			'name_admin_bar'     => __( 'Freelancer', 'cnt' ),
			'add_new'            => __( 'Add New', 'cnt' ),
			'add_new_item'       => __( 'Add New Freelancer', 'cnt' ),
			'new_item'           => __( 'New Freelancer', 'cnt' ),
			'edit_item'          => __( 'Edit Freelancer', 'cnt' ),
			'view_item'          => __( 'View Freelancer', 'cnt' ),
			'all_items'          => __( 'All Freelancers', 'cnt' ),
			'search_items'       => __( 'Search Freelancers', 'cnt' ),
			'parent_item_colon'  => __( 'Parent Freelancers:', 'cnt' ),
			'not_found'          => __( 'No tasks found.', 'cnt' ),
			'not_found_in_trash' => __( 'No tasks found in Trash.', 'cnt' )
		);

		$args = array(
			'labels'             => $labels,
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'freelancer' ),
			'menu_icon'          => 'dashicons-admin-users',
			'capability_type'    => 'post',
			'has_archive'        => true,
			'hierarchical'       => false,
			'menu_position'      => null,
			'supports'           => false
		);

		register_post_type( Freelancer::POST_TYPE, $args );
	}

	/**
	 * Add custom fields for post type freelancer
	 */
	public function onInitMetaBoxForFreelancer() {
		add_meta_box( Freelancer::POST_TYPE . '_fields', 'Freelancer', array(
			$this,
			'render_metabox_for_freelancer'
		), Freelancer::POST_TYPE, 'normal', 'high' );
	}

	/**
	 * Render Meta Box for Freelancer
	 */
	public function render_metabox_for_freelancer( $post ) {

		// Add an nonce field so we can check for it later.
		wp_nonce_field( 'render_metabox_for_freelancer', 'render_metabox_for_freelancer_nonce' );
		$default_img = $this::$app_url . '/assets/images/no-image.png';

		$field_for_freelancer = get_post_meta( $post->ID, Freelancer::POST_TYPE, true );

		$field_name = isset( $field_for_freelancer['freelancer_name'] ) && '' !== $field_for_freelancer['freelancer_name'] ? $field_for_freelancer['freelancer_name'] : $post->post_title;

		$field_avatar = isset( $field_for_freelancer['freelancer_avatar'] ) && '' !== $field_for_freelancer['freelancer_avatar'] ? $field_for_freelancer['freelancer_avatar'] : $default_img;

		$w = 115;
		if ( is_numeric( $field_avatar ) ) {
			$image_attributes = wp_get_attachment_image_src( $field_avatar );
			$src_img          = $image_attributes[0];
		} else {
			$src_img = $default_img;
		}
		?>
		<table class="form-table company-info">

			<tr>
				<th>
					Freelancer Info
				</th>
				<td>
					<div>
						<label>Name:
							<input
								name="<?php echo Freelancer::POST_TYPE; ?>[freelancer_name]"
								value="<?php echo $field_name; ?>"
							>
							</input>
						</label>
					</div>

					<hr>
					<div>
						<img data-src="<?php echo $default_img; ?>"
						     src="<?php echo $src_img; ?>"
						     width="<?php echo $w; ?>px"
						     height="auto"/>
						<div>
							<input type="hidden"
							       name="<?php echo Freelancer::POST_TYPE; ?>[freelancer_avatar]"
							       id="<?php echo Freelancer::POST_TYPE; ?>[freelancer_avatar]"
							       value="<?php echo $field_avatar ?>"/>
							<button type="submit" class="upload_image_button button">Upload</button>
							<button type="submit" class="remove_image_button button">&times;</button>
						</div>
					</div>
				</td>
			</tr>

		</table>
		<?php
	}

	/**
	 * Save Freelancer
	 */
	public function saveFreelancer( $post_id ) {
		// Check if it's not an autosave.
		if ( wp_is_post_autosave( $post_id ) ) {
			return;
		}


		if ( isset( $_POST[ Freelancer::POST_TYPE ] ) && is_array( $_POST[ Freelancer::POST_TYPE ] ) ) {
			$field_for_freelancer = $_POST[ Freelancer::POST_TYPE ];

			$field_for_freelancer = array_map( 'sanitize_text_field', $field_for_freelancer );;

			update_post_meta( $post_id, Freelancer::POST_TYPE, $field_for_freelancer );

		}
	}

	/**
	 * Modify Post Title if the input field for Name empty
	 * @param $data
	 * @param $postarr
	 *
	 * @return mixed
	 */
	public function modifyPostTitleForFreelancer( $data, $postarr ) {
		if ( $data['post_type'] == Freelancer::POST_TYPE ) {
			if ( isset( $postarr[ Freelancer::POST_TYPE ]['freelancer_name'] ) && '' !== $postarr[ Freelancer::POST_TYPE ]['freelancer_name'] ) {
				$data['post_title'] = $postarr[ Freelancer::POST_TYPE ]['freelancer_name'];
				$data['post_name']  = $postarr[ Freelancer::POST_TYPE ]['freelancer_name'];
			} else {
				$data['post_title'] = 'Freelancer #' . $postarr['ID'];
				$data['post_name']  = 'Freelancer #' . $postarr['ID'];
			}
		}

		return $data;
	}

	/**
	 * Add enctype="multipart/form-data" for upload image for freelancer
	 * @param $post
	 */
	public function addMultipartFormForFreelancer( $post ) {
		if ( Freelancer::POST_TYPE === $post->post_type ) {
			echo ' enctype="multipart/form-data"';
		}
	}

	/**
	 * Include needed  for upload image for the freelancer
	 */
	public function showAssets() {
		if ( is_admin() && get_current_screen()->id == Freelancer::POST_TYPE ) {
			//add script for upload image
			if ( ! did_action( 'wp_enqueue_media' ) ) {
				wp_enqueue_media();
			}
			global $post;
			if ( is_object( $post ) ) {
				wp_enqueue_media( array( 'post' => $post->ID ) );
			}

			wp_enqueue_script( 'admin_scripts', $this::$app_url . '/assets/js/admin.js', array( 'jquery' ), null, true );
		}
	}

	/**
	 * Init Meta Box For Tasks
	 */
	public function callMetaboxForTask() {
		new MetaboxForTask();
	}

	/**
	 * Change Page title
	 *
	 * @return string
	 */
	public function changeTitleNinjaPost() {
		$routes = $this->getCurrentRouteCallback();

		if ( $routes ) {
			return ucfirst( strtolower( $routes[1] ) );
		}
	}

	/**
	 * Add 'Freelancer' to the table header
	 *
	 * @param $cols
	 *
	 * @return mixed
	 */
	public function addTableHeadCol( $cols ) {
		$inserted = __( 'Freelancer', 'cnt' );
		array_splice( $cols, 2, 0, $inserted );

		return $cols;
	}

	/**
	 * Add 'Freelancer' column to the table
	 *
	 * @param $cols
	 * @param $task
	 *
	 * @return mixed
	 */
	public function addTableBodyRowCol( $cols, $task ) {
		$id = (int) str_replace( '#', '', $task->id() );

		$name = $this->getNameFreelancerByIDTask( $id );

		array_splice( $cols, 2, 0, $name );

		return $cols;
	}

	/**
	 * Get Name's Freelancer by ID Task's
	 *
	 * @param $id
	 *
	 * @return string
	 */
	public function getNameFreelancerByIDTask( $id ) {

		$name = 'Not Selected';

		$freelancer_id = get_post_meta( $id, 'freelancer_for_' . codingninjas\Task::POST_TYPE, true );

		$freelancers = get_post( $freelancer_id );

		if ( $freelancers ) {
			$name = $freelancers->post_title;
		}

		return $name;
	}

	/**
	 * Add Menu Item To Task's Page
	 */
	public function addMenuItemToTaskPageFilter( $menu, $item ) {
		$li   = '<li><a  data-toggle="modal" data-target="#modalForAddNewTask" href="" id="add_new_task"><i class="fa fa-plus-circle fa-fw"></i> Add New Tasks</a></li>
';
		$menu = str_replace( "</ul>", $li . '</ul>', $menu );

		return $menu;
	}

	/**
	 * Add Modal Window To Task's Page
	 */
	public function addMenuItemToTaskPageAction() {

		$freelancers_raw = get_posts( array(
			'numberposts' => - 1,
			'post_status' => 'publish',
			'post_type'   => Freelancer::POST_TYPE
		) );

		wp_reset_postdata();

		$freelancers = array();

		//in the list does not show freelancers who have more than 2 tasks
		if ( ! empty( $freelancers_raw ) ) {
			foreach ($freelancers_raw as $freelancer){
				$tasks = get_posts( array(
					'numberposts' => - 1,
					'post_status' => 'publish',
					'post_type'   => codingninjas\Task::POST_TYPE,
					'meta_query' => array(
						array(
							'key' => 'freelancer_for_' . codingninjas\Task::POST_TYPE,
							'value' => $freelancer->ID
						)
					)
				) );
				wp_reset_postdata();
				if (count($tasks) <= 2){
					$freelancers[] = $freelancer;
				}
			}
		}

		if ( ! empty( $freelancers ) ) {
			$output = '<select class="form-control" id="freelancer_for_task_select" name="freelancer_name">';
			$output .= '<option value="">Select Freelancer</option>';
			foreach ( $freelancers as $freelancer ) {
				$output .= '<option value="' . $freelancer->ID . '">' . $freelancer->post_title . '</option>';
			}
			$output .= '</select>';
		} else {
			$output = '<input type="text" disabled class="form-control" placeholder="All Freelancers are Busy">';
		}
		?>
		<div class="modal fade" id="modalForAddNewTask" tabindex="-1" role="dialog"
		     aria-labelledby="modalForAddNewTaskLabel">
			<div class="modal-dialog" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span
								aria-hidden="true">&times;</span></button>
						<h4 class="modal-title" id="modalForAddNewTaskLabel">Add New Tsak</h4>
					</div>
					<div class="modal-body">
						<div class="container-fluid">
							<div class="row">
								<div class="col-xs-8 col-xs-offset-1">
									<form class="form-horizontal" id="formForAddTask" role="form">
										<div class="form-group">
											<label for="inputTaskTitle"
											       class="col-sm-4 control-label">Task title</label>
											<div class="col-sm-8">
												<input name="task_title"
												       type="text"
												       class="form-control"
												       id="inputTaskTitle"
												       placeholder="Task">
											</div>
										</div>
										<div class="form-group">
											<label for="freelancer_for_task_select"
											       class="col-sm-4 control-label">Freelancer</label>
											<div class="col-sm-8">
												<?php echo $output; ?>
											</div>
										</div>
										<div class="form-group">
											<div class="col-sm-offset-4 col-sm-8">
												<button type="button" id="addTaskModal" class="btn btn-primary">Add
												</button>
											</div>
										</div>
									</form>
								</div>
							</div>
						</div>

					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Ajax Handler for posting task from frontend
	 */
	public function addItemToTask() {
		$response = array(
			'status' => true,
			'msg'    => ''
		);
		if ( ! check_ajax_referer( 'add_task-nonce', 'nonce_code', false ) ) {
			$response['msg']    = 'Something wrong';
			$response['status'] = false;
			wp_send_json( $response );
		}

		if ( ! isset( $_POST['data']['task_title'] ) || '' == $_POST['data']['task_title'] ) {
			$response['msg']    .= 'Task title missing. ';
			$response['status'] = false;
		}
		if ( ! isset( $_POST['data']['freelancer_id'] ) || '' == $_POST['data']['freelancer_id'] ) {
			$response['msg']    .= ' Freelancer name missing. ';
			$response['status'] = false;
		}

		if ( ! $response['status'] ) {
			wp_send_json( $response );
		}

		$cur_user_id = get_current_user_id();
		$post_data = array(
			'post_type'    => codingninjas\Task::POST_TYPE,
			'post_title'   => wp_strip_all_tags( $_POST['data']['task_title'] ),
			'post_content' => '',
			'post_status'  => 'publish',
			'post_author'  => $cur_user_id
		);

		$post_id = wp_insert_post( $post_data );

		if ( is_wp_error( $post_id ) ) {
			$response['msg']    = $post_id->get_error_message();
			$response['status'] = false;
			wp_send_json( $response );
		}

		$freelancer_id = (int) $_POST['data']['freelancer_id'];

		// Update the meta field.
		update_post_meta( $post_id, 'freelancer_for_' . codingninjas\Task::POST_TYPE, $freelancer_id );


		$response['status'] = true;
		$response['msg']    = 'Success!';

		wp_send_json( $response );
	}

}

