<?php

namespace codingninjastest;

use \Exception, \codingninjas;

class MetaboxForTask {

	/**
	 * Hook into the appropriate actions when the class is constructed.
	 */
	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		add_action( 'save_post_' . codingninjas\Task::POST_TYPE, array( $this, 'save' ) );
	}

	/**
	 * Adds the meta box container.
	 */
	public function add_meta_box( $post_type ) {
		// Limit meta box to certain post types.
		$post_types = array( codingninjas\Task::POST_TYPE );

		if ( in_array( $post_type, $post_types ) ) {
			add_meta_box(
				'freelancer_for_' . codingninjas\Task::POST_TYPE,
				__( 'Freelancer', 'cnt' ),
				array( $this, 'render_meta_box_content' ),
				$post_type,
				'side',
				'default'
			);
		}
	}

	/**
	 * Save the meta when the post is saved.
	 *
	 * @param int $post_id The ID of the post being saved.
	 */
	public function save( $post_id ) {

		/*
		 * We need to verify this came from the our screen and with proper authorization,
		 * because save_post can be triggered at other times.
		 */

		// Check if our nonce is set.
		if ( ! isset( $_POST['metabox_for_task_nonce'] ) ) {
			return $post_id;
		}

		$nonce = $_POST['metabox_for_task_nonce'];

		// Verify that the nonce is valid.
		if ( ! wp_verify_nonce( $nonce, 'metabox_for_task' ) ) {
			return $post_id;
		}

		/*
		 * If this is an autosave, our form has not been submitted,
		 * so we don't want to do anything.
		 */
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}

		// Sanitize the user input.
		if ( isset( $_POST[ 'freelancer_for_' . codingninjas\Task::POST_TYPE ] ) ) {

			$freelancer = (int) $_POST[ 'freelancer_for_' . codingninjas\Task::POST_TYPE ];

			// Update the meta field.
			update_post_meta( $post_id, 'freelancer_for_' . codingninjas\Task::POST_TYPE, $freelancer );
		}

	}


	/**
	 * Render Meta Box content.
	 *
	 * @param WP_Post $post The post object.
	 */
	public function render_meta_box_content( $post ) {

		// Add an nonce field so we can check for it later.
		wp_nonce_field( 'metabox_for_task', 'metabox_for_task_nonce' );

		// Use get_post_meta to retrieve an existing value from the database.
		$freelancer_for_task = get_post_meta( $post->ID, 'freelancer_for_' . codingninjas\Task::POST_TYPE, true );

		$freelancers = get_posts( array(
			'numberposts' => - 1,
			'post_status' => 'publish',
			'post_type'   => Freelancer::POST_TYPE
		) );

		if ( ! empty( $freelancers ) ) {
			$output = '<select name="freelancer_for_' . codingninjas\Task::POST_TYPE . '">';
			$output .= '<option value="">Select Freelancer</option>';
			foreach ( $freelancers as $freelancer ) {
				$output .= '<option value="' . $freelancer->ID . '" ' . selected( $freelancer_for_task, $freelancer->ID, false ) . '>' . $freelancer->post_title . '</option>';
			}
			$output .= '</select>';
		} else {
			$output = '<a href="'. admin_url( 'post-new.php?post_type=freelancer' ) .'" class="button button-primary button-large">Add Freelancer</a>';
		}


		wp_reset_postdata();


		// Display the form, using the current value.
		echo $output;
	}
}

