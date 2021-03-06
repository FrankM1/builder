<?php
namespace Qazana\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Qazana tracker.
 *
 * Qazana tracker handler class is responsible for sending anonymous plugin
 * data to Qazana servers for users that actively allowed data tracking.
 *
 * @since 1.0.0
 */
class Tracker {

	/**
	 * API URL.
	 *
	 * Holds the URL of the Tracker API.
	 *
	 * @since 1.0.0
	 * @access private
	 *
	 * @var string API URL.
	 */

    private static $_api_url = 'https://api.qazana.net/api/v1/qazana/stats/';

	/**
	 * Constructor.
	 *
	 * Initialize Qazana tracker.
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public function __construct() {
		add_action( 'qazana/tracker/send_event', [ __CLASS__, 'send_tracking_data' ] );
		add_action( 'admin_init', [ __CLASS__, 'handle_tracker_actions' ] );
		add_action( 'admin_notices', [ __CLASS__, 'admin_notices' ] );
	}

	/**
	 * Check for settings opt-in.
	 *
	 * Checks whether the site admin has opted-in for data tracking, or not.
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 *
	 * @param string $new_value Allowed tracking value.
	 *
	 * @return string Return `yes` if tracking allowed, `no` otherwise.
	 */
	public static function check_for_settings_optin( $new_value ) {
		$old_value = get_option( 'qazana_allow_tracking', 'no' );
		if ( $old_value !== $new_value && 'yes' === $new_value ) {
			self::send_tracking_data( true );
		}

		if ( empty( $new_value ) ) {
			$new_value = 'no';
		}
		return $new_value;
	}

	/**
	 * Send tracking data.
	 *
	 * Decide whether to send tracking data, or not.
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 *
	 * @param bool $override
	 */
	public static function send_tracking_data( $override = false ) {
		// Don't trigger this on AJAX Requests.
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			return;
		}

		if ( ! self::is_allow_track() ) {
			return;
		}

		$last_send = self::get_last_send_time();

		/**
		 * Tracker override send.
		 *
		 * Filters whether to override sending tracking data or not.
		 *
		 * @since 1.0.0
		 *
		 * @param bool $override Whether to override default setting or not.
		 */
		$override = apply_filters( 'qazana/tracker/send_override', $override );

		if ( ! $override ) {
			$last_send_interval = strtotime( '-1 week' );

			/**
			 * Tracker last send interval.
			 *
			 * Filters the interval of between two tracking requests.
			 *
			 * @since 1.0.0
			 *
			 * @param int $last_send_interval A date/time string. Default is `strtotime( '-1 week' )`.
			 */
			$last_send_interval = apply_filters( 'qazana/tracker/last_send_interval', $last_send_interval );

			// Send a maximum of once per week by default.
			if ( $last_send && $last_send > $last_send_interval ) {
				return;
			}
		} else {
			// Make sure there is at least a 1 hour delay between override sends, we dont want duplicate calls due to double clicking links.
			if ( $last_send && $last_send > strtotime( '-1 hours' ) ) {
				return;
			}
		}

		// Update time first before sending to ensure it is set.
		update_option( 'qazana_tracker_last_send', time() );

		// Send here..
		$params = [
			'system' => self::get_system_reports_data(),
			'site_lang' => get_bloginfo( 'language' ),
			'email' => get_option( 'admin_email' ),
			'usages' => [
				'posts' => self::get_posts_usage(),
				'library' => self::get_library_usage(),
			],
			'is_first_time' => empty( $last_send ),
		];

		/**
		 * Tracker send tracking data params.
		 *
		 * Filters the data parameters when sending tracking request.
		 *
		 * @since 1.0.0
		 *
		 * @param array $params Variable to encode as JSON.
		 */
		$params = apply_filters( 'qazana/tracker/send_tracking_data_params', $params );

		add_filter( 'https_ssl_verify', '__return_false' );

		wp_safe_remote_post(
			self::$_api_url,
			[
				'timeout' => 25,
				'blocking' => false,
				// 'sslverify' => false,
				'body' => [
					'data' => wp_json_encode( $params ),
				],
			]
		);
	}

	/**
	 * Is allow track.
	 *
	 * Checks whether the site admin has opted-in for data tracking, or not.
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function is_allow_track() {
		return 'yes' === get_option( 'qazana_allow_tracking', 'no' );
	}

	/**
	 * Handle tracker actions.
	 *
	 * Check if the user opted-in or opted-out and update the database.
	 *
	 * Fired by `admin_init` action.
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function handle_tracker_actions() {
		if ( ! isset( $_GET['qazana_tracker'] ) ) {
			return;
		}

		if ( 'opt_into' === $_GET['qazana_tracker'] ) {
			check_admin_referer( 'opt_into' );

			update_option( 'qazana_allow_tracking', 'yes' );
			self::send_tracking_data( true );
		}

		if ( 'opt_out' === $_GET['qazana_tracker'] ) {
			check_admin_referer( 'opt_out' );

			update_option( 'qazana_allow_tracking', 'no' );
			update_option( 'qazana_tracker_notice', '1' );
		}

		wp_redirect( remove_query_arg( 'qazana_tracker' ) );
		exit;
	}

	/**
	 * Admin notices.
	 *
	 * Add Qazana notices to WordPress admin screen to show tracker notice.
	 *
	 * Fired by `admin_notices` action.
	 *
	 * @since 1.0.0
	 * @access public
	 * @static
	 */
	public static function admin_notices() {
		// Show tracker notice after 24 hours from installed time.
		if ( self::get_installed_time() > strtotime( '-24 hours' ) ) {
			return;
		}

		if ( '1' === get_option( 'qazana_tracker_notice' ) ) {
			return;
		}

		if ( self::is_allow_track() ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$qazana_pages = new \WP_Query( [
			'post_type' => 'any',
			'post_status' => 'publish',
			'fields' => 'ids',
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
			'meta_key' => '_qazana_edit_mode',
			'meta_value' => 'builder',
		] );

		if ( 2 > $qazana_pages->post_count ) {
			return;
		}

		self::$notice_shown = true;

		// TODO: Skip for development env.
		$optin_url = wp_nonce_url( add_query_arg( 'qazana_tracker', 'opt_into' ), 'opt_into' );
		$optout_url = wp_nonce_url( add_query_arg( 'qazana_tracker', 'opt_out' ), 'opt_out' );

		$tracker_description_text = __( 'Love using Qazana? Become a super contributor by opting in to our anonymous plugin data collection and to our updates. We guarantee no sensitive data is collected.', 'qazana' );

		/**
		 * Tracker admin description text.
		 *
		 * Filters the admin notice text for anonymous data collection.
		 *
		 * @since 1.0.0
		 *
		 * @param string $tracker_description_text Description text displayed in admin notice.
		 */
		$tracker_description_text = apply_filters( 'qazana/tracker/admin_description_text', $tracker_description_text );
		?>
		<div class="notice updated qazana-message">
			<div class="qazana-message-inner">
				<div class="qazana-message-icon">
					<div class="e-logo-wrapper">
						<i class="eicon-qazana" aria-hidden="true"></i>
					</div>
				</div>
				<div class="qazana-message-content">
					<p><?php echo esc_html( $tracker_description_text ); ?> <a href="https://qazana.net/plugins/qazana/qazana-usage-data/" target="_blank"><?php _e( 'Learn more.', 'qazana' ); ?></a></p>
					<p class="qazana-message-actions"><a href="<?php echo $optin_url; ?>" class="button button-primary"><?php _e( 'Sure! I\'d love to help', 'qazana' ); ?></a>&nbsp;<a href="<?php echo $optout_url; ?>" class="button-secondary"><?php _e( 'No thanks', 'qazana' ); ?></a></p>
				</div>
			</div>
		</div>
	<?php
	}

	public static function is_notice_shown() {
		return self::$notice_shown;
	}

	/**
	 * Get installed time.
	 *
	 * Retrieve the time when Qazana was installed.
	 *
	 * @since 2.0.0
	 * @access private
	 * @static
	 *
	 * @return int Unix timestamp when Qazana was installed.
	 */
	private static function get_installed_time() {
		$installed_time = get_option( '_qazana_installed_time' );
		if ( ! $installed_time ) {
			$installed_time = time();
			update_option( '_qazana_installed_time', $installed_time );
		}
		return $installed_time;
	}

	/**
	 * Get system reports data.
	 *
	 * Retrieve the data from system reports.
	 *
	 * @since 2.0.0
	 * @access private
	 * @static
	 *
	 * @return array The data from system reports.
	 */
	private static function get_system_reports_data() {
		$reports = qazana()->admin->system_info->load_reports( System\Info\Main::get_allowed_reports() );

		$system_reports = [];
		foreach ( $reports as $report_key => $report_details ) {
			$system_reports[ $report_key ] = [];
			foreach ( $report_details['report'] as $sub_report_key => $sub_report_details ) {
				$system_reports[ $report_key ][ $sub_report_key ] = $sub_report_details['value'];
			}
		}
		return $system_reports;
	}

	/**
	 * Get last send time.
	 *
	 * Retrieve the last time tracking data was sent.
	 *
	 * @since 2.0.0
	 * @access private
	 * @static
	 *
	 * @return int|false The last time tracking data was sent, or false if
	 *                   tracking data never sent.
	 */
	private static function get_last_send_time() {
		$last_send_time = get_option( 'qazana_tracker_last_send', false );

		/**
		 * Tracker last send time.
		 *
		 * Filters the last time tracking data was sent.
		 *
		 * @since 1.0.0
		 *
		 * @param int|false $last_send_time The last time tracking data was sent,
		 *                                  or false if tracking data never sent.
		 */
		$last_send_time = apply_filters( 'qazana/tracker/last_send_time', $last_send_time );

		return $last_send_time;
	}

	/**
	 * Get posts usage.
	 *
	 * Retrieve the number of posts using Qazana.
	 *
	 * @since 2.0.0
	 * @access private
	 * @static
	 *
	 * @return array The number of posts using Qazana grouped by post types
	 *               and post status.
	 */
	private static function get_posts_usage() {
		global $wpdb;

		$usage = [];

		$results = $wpdb->get_results(
			"SELECT `post_type`, `post_status`, COUNT(`ID`) `hits`
				FROM {$wpdb->posts} `p`
				LEFT JOIN {$wpdb->postmeta} `pm` ON(`p`.`ID` = `pm`.`post_id`)
				WHERE `post_type` != 'qazana_library'
					AND `meta_key` = '_qazana_edit_mode' AND `meta_value` = 'qazana'
				GROUP BY `post_type`, `post_status`;"
		);

		if ( $results ) {
			foreach ( $results as $result ) {
				$usage[ $result->post_type ][ $result->post_status ] = $result->hits;
			}
		}

		return $usage;

	}

	/**
	 * Get library usage.
	 *
	 * Retrieve the number of Qazana library items saved.
	 *
	 * @since 2.0.0
	 * @access private
	 * @static
	 *
	 * @return array The number of Qazana library items grouped by post types
	 *               and meta value.
	 */
	private static function get_library_usage() {
		global $wpdb;

		$usage = [];

		$results = $wpdb->get_results(
			"SELECT `meta_value`, COUNT(`ID`) `hits`
				FROM {$wpdb->posts} `p`
				LEFT JOIN {$wpdb->postmeta} `pm` ON(`p`.`ID` = `pm`.`post_id`)
				WHERE `post_type` = 'qazana_library'
					AND `meta_key` = '_qazana_template_type'
				GROUP BY `post_type`, `meta_value`;"
		);

		if ( $results ) {
			foreach ( $results as $result ) {
				$usage[ $result->meta_value ] = $result->hits;
			}
		}

		return $usage;

	}
}
