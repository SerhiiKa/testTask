<?php

namespace codingninjastest;

use \Exception, \codingninjas, \WP_Post;

class Freelancer {
	/**
	 * post instance
	 * @var WP_Post
	 */
	protected $post;

	/**
	 * post type name
	 */
	const POST_TYPE = 'freelancer';

	/**
	 * Freelancer constructor.
	 *
	 * @param WP_Post $post
	 */
	public function __construct( WP_Post $post ) {
		$this->post = $post;
	}


}