<?php
/**
 * Skyverge Plugin License class
 *
 * This source file is subject to the GNU General Public License v3.0
 * that is bundled with this package in the file license.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.html
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@skyverge.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade to newer
 * versions in the future. If you wish to customize this plugin for your
 * needs please refer to http://skyverge.com/product/tba/ for more information.
 *
 * This file is based on the `EDD_License` class from the Easy Digital Downloads team.
 *
 * @package   SkyVerge/WooCommerce/PluginUpdater
 * @author    SkyVerge
 * @copyright Copyright (c) 2012-2017, Easy Digital Downloads
 * @copyright Copyright (c) 2017-2018, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

namespace SkyVerge\WooCommerce\PluginUpdater;

defined( 'ABSPATH' ) or exit;

if ( ! class_exists( '\\SkyVerge\\WooCommerce\\PluginUpdater\\License' ) ) :

	/**
	 * Provides a general license settings page for plugins to add license key inputs.
	 *
	 * @since 1.0.0
	 */
	class License {


		/** @var string $api_url plugin update URL */
		protected $api_url;

		/** @var string $file plugin file */
		protected $file;

		/** @var string $plugin_url plugin url */
		protected $plugin_url;

		/** @var string $item_name plugin name on our site */
		protected $item_name;

		/** @var int $item_id plugin post ID on our site */
		protected $item_id;

		/** @var string $version plugin version */
		protected $version;

		/** @var string $author plugin author */
		protected $author;

		/** @var string $item_shortname slugified plugin name */
		private $item_shortname;

		/** @var string $license license key */
		private $license;

		/** @var string $updater_url the URL for plugin updates */
		protected $updater_url = 'https://skyverge.com/';

		/** @var \SkyVerge\WooCommerce\PluginUpdater\License_Settings $settings the settings instance */
		protected $settings;


		/**
		 * SkyVerge\WooCommerce\PluginUpdater\License constructor.
		 *
		 * @since 1.0.0
		 *
		 * @param string $_file
		 * @param string $_path
		 * @param string $_plugin_url
		 * @param string $_item_name
		 * @param string $_version
		 * @param int $_item_id
		 * @param string $_author
		 */
		public function __construct( $_file, $_path, $_plugin_url, $_item_name, $_version, $_item_id = null, $_author = 'SkyVerge' ) {

			if ( is_numeric( $_item_id ) ) {
				$this->item_id = absint( $_item_id );
			}

			$this->api_url        = 'https://skyverge.com/';
			$this->file           = $_file;
			$this->path           = $_path;
			$this->plugin_url     = $_plugin_url;
			$this->item_name      = $_item_name;
			$this->version        = $_version;
			$this->author         = $_author;
			$this->license        = trim( get_option( "{$this->item_shortname}_license_key", '' ) );
			$this->item_shortname = preg_replace( '/[^a-zA-Z0-9_\s]/', '', str_replace( 'woocommerce', 'wc', str_replace( ' ', '_', strtolower( $this->item_name ) ) ) );

			$this->includes();
			$this->add_hooks();
		}


		/**
		 * Includes required files.
		 *
		 * @since 1.0.0
		 */
		public function includes() {

			// load settings if not available already
			if ( ! class_exists( '\\SkyVerge\\WooCommerce\\PluginUpdater\\License_Settings' ) ) {
				require_once( $this->path . '/lib/skyverge/updater/class-skyverge-plugin-license-settings.php' );
				$this->settings = new License_Settings( $this->plugin_url );
			}

			if ( ! class_exists( '\\SkyVerge\\WooCommerce\\PluginUpdater\\Updater' ) ) {
				require_once( $this->path . '/lib/skyverge/updater/class-skyverge-plugin-updater.php' );
			}
		}


		/**
		 * Setup plugin hooks.
		 *
		 * @since 1.0.0
		 */
		private function add_hooks() {

			// load plugin updater
			add_action( 'admin_init', array( $this, 'auto_updater' ), 0 );

			// add scheduled event for license update check
			add_filter( 'cron_schedules', array( $this, 'add_cron_schedule' ) );
			add_action( 'wp', array( $this, 'schedule_events' ) );

			// register settings
			add_filter( 'skyverge_plugin_license_settings', array( $this, 'add_settings' ), 1 );

			// add styles for settings
			add_action( 'admin_enqueue_scripts', array( $this, 'add_styles' ) );

			// activate license key on settings save
			add_action( 'admin_init', array( $this, 'activate_license' ) );

			// deactivate license key
			add_action( 'admin_init', array( $this, 'deactivate_license' ) );

			// check that license is valid once per week
			add_action( 'skyverge_weekly_scheduled_events', array( $this, 'weekly_license_check' ) );

			// for testing license notices, uncomment this line to force checks on every page load
			//add_action( 'admin_init', array( $this, 'weekly_license_check' ) );

			// Display notices to admins
			add_action( 'admin_notices', array( $this, 'notices' ) );

			add_action( 'in_plugin_update_message-' . plugin_basename( $this->file ), array( $this, 'plugin_row_license_missing' ), 10, 2 );
		}



		/**
		 * Load the auto updater.
		 *
		 * @since 1.0.0
		 */
		public function auto_updater() {

			$data = array(
				'version' => $this->version,
				'license' => $this->license,
				'author'  => $this->author,
			);

			if ( ! empty( $this->item_id ) ) {
				$data['item_id'] = $this->item_id;
			} else {
				$data['item_name'] = $this->item_name;
			}

			// Setup the updater
			$plugin_updater = new Updater( $this->file, $data );
		}


		/**
		 * Registers new cron schedule.
		 *
		 * @since 1.0.0
		 *
		 * @param string[] $schedules existing schedules
		 * @return string[] update schedules
		 */
		public function add_cron_schedule( $schedules = array() ) {

			// adds a weekly schedule to available cron schedules
			$schedules['weekly'] = array(
				'interval' => 604800,
				'display'  => __( 'Once per week', 'skyverge-plugin-updater' ),
			);

			return $schedules;
		}


		/**
		 * Schedule weekly events.
		 *
		 * @since 1.0.0
		 */
		public function schedule_events() {

			if ( ! wp_next_scheduled( 'skyverge_weekly_scheduled_events' ) ) {
				wp_schedule_event( current_time( 'timestamp', true ), 'weekly', 'skyverge_weekly_scheduled_events' );
			}
		}


		/**
		 * Get license settings page URL.
		 *
		 * @since 1.0.0
		 *
		 * @param string[] $settings license settings
		 * @return string[] updated settings
		 */
		public function add_settings( $settings ) {

			$plugin_license_settings = array(
				array(
					'id'      => "{$this->item_shortname}_license_key",
					'name'    => sprintf( __( '%1$s', 'skyverge-plugin-updater' ), $this->item_name ),
					'desc'    => '',
					'type'    => 'license_key',
					'options' => array( 'is_valid_license_option' => "{$this->item_shortname}_license_active" ),
					'size'    => 'regular',
				)
			);

			return array_merge( $settings, $plugin_license_settings );
		}


		/**
		 * Adds updater page stylesheet.
		 *
		 * @since 1.0.0
		 */
		public function add_styles() {

			if ( isset( $_GET['section'] ) && 'skyverge-helper' === $_GET['section'] ) {
				wp_enqueue_style( 'skyverge-plugin-license-settings', $this->plugin_url . '/lib/skyverge/updater/assets/css/skyverge-updater-styles.css', array(), $this->version );
			}
		}


		/**
		 * Activate the license key
		 *
		 * @since 1.0.0
		 */
		public function activate_license() {

			if ( ! isset( $_REQUEST["{$this->item_shortname}_license_key-nonce"] ) || ! wp_verify_nonce( $_REQUEST["{$this->item_shortname}_license_key-nonce"], "{$this->item_shortname}_license_key-nonce" ) || ! current_user_can( 'manage_woocommerce' ) ) {
				return;
			}

			if ( empty( $_POST["{$this->item_shortname}_license_key"] ) ) {
				delete_option( "{$this->item_shortname}_license_active" );
				return;
			}

			// don't activate a key when deactivating a different key
			foreach ( $_POST as $key => $value ) {
				if ( false !== strpos( $key, 'license_key_deactivate' ) ) {
					return;
				}
			}

			$details = get_option( "{$this->item_shortname}_license_active" );

			if ( is_object( $details ) && 'valid' === $details->license ) {
				return;
			}

			$license = sanitize_text_field( $_POST["{$this->item_shortname}_license_key"] );

			if ( empty( $license ) ) {
				return;
			}

			// data to send to the API
			$api_params = array(
				'edd_action' => 'activate_license',
				'license'    => $license,
				'item_name'  => urlencode( $this->item_name ),
				'url'        => home_url(),
			);

			$response = wp_remote_post(
				$this->api_url,
				array(
					'timeout' => 15,
					'body'    => $api_params,
				)
			);

			// make sure there are no errors
			if ( is_wp_error( $response ) ) {
				return;
			}

			// tell WP to look for updates
			set_site_transient( 'update_plugins', null );

			// decode license data
			$license_data = json_decode( wp_remote_retrieve_body( $response ) );

			update_option( "{$this->item_shortname}_license_active", $license_data );
		}


		/**
		 * Deactivate the license key
		 *
		 * @since 1.0.0
		 */
		public function deactivate_license() {

			if ( ! isset( $_POST["{$this->item_shortname}_license_key"] ) || ! current_user_can( 'manage_woocommerce' ) ) {
				return;
			}

			if ( ! wp_verify_nonce( $_REQUEST["{$this->item_shortname}_license_key-nonce"], "{$this->item_shortname}_license_key-nonce" ) ) {
				wp_die( __( 'Nonce verification failed', 'skyverge-plugin-updater' ), __( 'Error', 'skyverge-plugin-updater' ), array( 'response' => 403 ) );
			}

			// run on deactivate button press
			if ( isset( $_POST["{$this->item_shortname}_license_key_deactivate"] ) ) {

				// data to send to the API
				$api_params = array(
					'edd_action' => 'deactivate_license',
					'license'    => $this->license,
					'item_name'  => urlencode( $this->item_name ),
					'url'        => home_url(),
				);

				$response = wp_remote_post(
					$this->api_url,
					array(
						'timeout' => 15,
						'body'    => $api_params,
					)
				);

				// Make sure there are no errors
				if ( is_wp_error( $response ) ) {
					return;
				}

				// Decode the license data
				$license_data = json_decode( wp_remote_retrieve_body( $response ) );

				delete_option( "{$this->item_shortname}_license_active" );
			}
		}


		/**
		 * Check for a valid license on this plugin.
		 *
		 * @since 1.0.0
		 *
		 * @return bool true if valid
		 */
		public function is_license_valid() {

			$details = get_option( "{$this->item_shortname}_license_active" );

			return is_object( $details ) && 'valid' === $details->license;
		}


		/**
		 * Check if license key is valid once per week
		 *
		 * @since 1.0.0
		 *
		 * @return bool
		 */
		public function weekly_license_check() {

			if ( ! empty( $_POST["{$this->item_shortname}_license_key"] ) ) {
				return false; // don't fire when saving settings
			}

			if ( empty( $this->license ) ) {
				return false;
			}

			// data to send in our API request
			$api_params = array(
				'edd_action' => 'check_license',
				'license'    => $this->license,
				'item_name'  => urlencode( $this->item_name ),
				'url'        => home_url(),
			);

			$response = wp_remote_post(
				$this->api_url,
				array(
					'timeout' => 15,
					'body'    => $api_params,
				)
			);

			// make sure the response came back okay
			if ( is_wp_error( $response ) ) {
				return false;
			}

			$license_data = json_decode( wp_remote_retrieve_body( $response ) );

			update_option( "{$this->item_shortname}_license_active", $license_data );
		}


		/**
		 * Add admin notices to WooCommerce pages for errors.
		 *
		 * @since 1.0.0
		 */
		public function notices() {
			global $current_screen;

			static $showed_invalid_message = false;

			$prefix  = sanitize_title( __( 'WooCommerce', 'woocommerce' ) );
			$screens = array( "{$prefix}_page_wc-addons", "{$prefix}_page_wc-settings", 'plugins' );
			$key     = trim( get_option( "{$this->item_shortname}_license_key", '' ) );

			if ( empty( $key ) || ! current_user_can( 'manage_woocommerce' ) ) {
				return;
			}

			$messages = array();
			$license  = get_option( "{$this->item_shortname}_license_active" );

			if ( in_array( $current_screen->id, $screens, true ) && is_object( $license ) && 'valid' !== $license->license && ! $showed_invalid_message ) {

				// only show this notice on settings / Extensions screens
				if ( ! isset( $_GET['section'] ) || 'skyverge-helper' !== $_GET['section'] ) {

					$messages[] = sprintf(
						/* translators: Placeholder: %1$s - <a>, %2$s - </a> */
						__( 'You have invalid or expired license keys for SkyVerge Plugins. Please go to the %1$sLicenses page%2$s to correct this issue.', 'skyverge-plugin-updater' ),
						'<a href="' . $this->get_license_settings_url() . '">',
						'</a>'
					);

					$showed_invalid_message = true;
				}
			}

			if ( ! empty( $messages ) ) {

				foreach( $messages as $message ) {
					echo '<div class="error"><p>' . $message . '</p></div>';
				}
			}
		}


		/**
		 * Displays message inline on plugin row that the license key is missing
		 *
		 * @since 1.0.0
		 */
		public function plugin_row_license_missing( $plugin_data, $version_info ) {

			static $showed_missing_key_message = array();

			$license = get_option( "{$this->item_shortname}_license_active" );

			if ( ( ! is_object( $license ) || 'valid' !== $license->license ) && empty( $showed_missing_key_message[ $this->item_shortname ] ) ) {

				echo '&nbsp;<strong><a href="' . esc_url( $this->get_license_settings_url() ) . '">' . __( 'Enter valid license key for automatic updates.', 'skyverge-plugin-updater' ) . '</a></strong>';
				$showed_missing_key_message[ $this->item_shortname ] = true;
			}
		}


		/**
		 * Get license settings page URL.
		 *
		 * @since 1.0.0
		 */
		public function get_license_settings_url() {
			return admin_url( 'admin.php?page=wc-addons&section=skyverge-helper' );
		}


		/**
		 * Gets the license settings instance.
		 */
		public function get_license_settings_instance() {
			return $this->settings;
		}


	}

endif;
