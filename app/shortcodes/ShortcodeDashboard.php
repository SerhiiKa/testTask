<?php

namespace codingninjastest;

use \Exception, \codingninjas;


class ShortcodeDashboard {

	/**
	 * Instance of ShortcodeDashboard
	 * @var null
	 */
	public static $instance = null;

	/**
	 * @var, parameter that determines how often the shortcode will be updated
	 */
	private static $timeout;

	/**
	 * ShortcodeDashboard constructor.
	 *
	 * @param $timeout
	 */
	public function __construct( $timeout ) {
		self::$timeout = $timeout;

		add_shortcode( 'cn_dashboard', array( $this, 'showShortcode' ) );

		add_action( 'wp_ajax_shortcode_dashboard', array( $this, 'ajaxShortCodeDashboard' ) );
		add_action( 'wp_ajax_nopriv_shortcode_dashboard', array( $this, 'ajaxShortCodeDashboard' ) );

		add_action( 'wp_enqueue_scripts', array( $this, 'addAJAXScript' ), 21 );
	}

	/**
	 * Run ShortcodeDashboard
	 *
	 * @param $timeout, parameter that determines how often the shortcode will be updated
	 *
	 * @return ShortcodeDashboard|null
	 */
	public static function run( $timeout = 5000 ) {
		if ( ! self::$instance ) {
			self::$instance = new self( $timeout );
		}

		return self::$instance;
	}


	/**
	 * Shortcode handler
	 *
	 * @return string
	 */
	public function showShortcode() {

		$number_tasks      = $this->getTaskAmount();
		$number_freelancer = $this->getFreelancerAmount();
		ob_start();
		?>
		<div class="row">
			<div class="col-lg-3 col-md-6">
				<div class="panel panel-primary freelancer">
					<div class="panel-heading">
						<div class="row">
							<div class="col-xs-3">
								<i class="fa fa-users fa-5x"></i>
							</div>
							<div class="col-xs-9 text-right">
								<div id="number_freelancer" class="huge"><?php echo $number_freelancer; ?></div>
								<div>Freelancers</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="col-lg-3 col-md-6">
				<div class="panel panel-green tasks">
					<div class="panel-heading">
						<div class="row">
							<div class="col-xs-3">
								<i class="fa fa-tasks fa-5x"></i>
							</div>
							<div class="col-xs-9 text-right">
								<div id="number_tasks" class="huge"><?php echo $number_tasks; ?></div>
								<div>Tasks</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>

		<?php

		$content = ob_get_clean();

		return $content;
	}

	/**
	 * Get the amount of Tasks
	 *
	 * @return mixed
	 */
	private function getTaskAmount() {
		$count_posts = wp_count_posts( codingninjas\Task::POST_TYPE );

		return $count_posts->publish;
	}

	/**
	 * Get the amount of Freelancers
	 *
	 * @return mixed
	 */
	private function getFreelancerAmount() {
		$count_posts = wp_count_posts( Freelancer::POST_TYPE );

		return $count_posts->publish;
	}

	/**
	 *  Ajax Handler for Dashboard Shortcode
	 */
	public function ajaxShortCodeDashboard() {

		$number_tasks      = $this->getTaskAmount();
		$number_freelancer = $this->getFreelancerAmount();
		$response          = array(
			'status'            => true,
			'msg'               => 'OK',
			'number_tasks'      => $number_tasks,
			'number_freelancer' => $number_freelancer,
		);

		wp_send_json( $response );
	}

	/**
	 * enqueue ajax script
	 */
	public function addAJAXScript() {
		wp_enqueue_script( 'jquery' );

		wp_enqueue_script(
			'dashboard-ajax',
			AppTest::$app_url . '/assets/js/dashboard-ajax.js',
			[ 'jquery' ],
			'1.0.0',
			true
		);

		wp_localize_script( 'dashboard-ajax', 'ajaxDashboardData',
			array(
				'url'          => admin_url( 'admin-ajax.php' ),
				'timeout_ajax' => self::$timeout
			)
		);
	}
}
