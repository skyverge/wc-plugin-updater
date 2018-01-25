<?php
/**
 * Skyverge Plugin License Settings
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
 * @package   SkyVerge/WooCommerce/PluginUpdater
 * @author    SkyVerge
 * @copyright Copyright (c) 2017-2018, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

namespace SkyVerge\WooCommerce\PluginUpdater;

defined( 'ABSPATH' ) or exit;

if ( ! class_exists( '\\SkyVerge\\WooCommerce\\PluginUpdater\\License_Settings' ) ) :

/**
 * Provides a general license settings page for plugins to add license key inputs.
 *
 * @since 1.0.0
 */
class License_Settings {


	/** @var string $plugin_url the URL for the plugin file */
	protected $plugin_url;


	/**
	 * \SkyVerge\WooCommerce\PluginUpdater\License_Settings constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param string $plugin_url the plugin file URL
	 */
	public function __construct( $plugin_url ) {

		$this->plugin_url = $plugin_url;

		// add the license settings
		add_action( 'current_screen', array( $this, 'current_screen' ) );

		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}


	/** Output settings ***************************************** */


	/**
	 * Checks the current screen to output our tab and settings content.
	 *
	 * @since 1.0.0
	 *
	 * @param \WP_Screen $screen the current screen
	 */
	public function current_screen( $screen ) {

		$wc_screen_id = sanitize_title( __( 'WooCommerce', 'woocommerce' ) );

		if ( "{$wc_screen_id}_page_wc-addons" === $screen->id ) {

			$this->add_settings_tab();

			if ( isset( $_GET['section'] ) && 'skyverge-helper' === $_GET['section'] ) {

				$this->output();
				$this->move_output();
			}
		}
	}


	/**
	 * Outputs settings for all license sections.
	 *
	 * @since 1.0.0
	 */
	public function output() {

		$this->change_active_tab();

		add_filter( 'woocommerce_addons_sections', '__return_empty_array' );

		$license_sections = $this->get_license_settings();

		$output  = '<div class="start skyverge-helper container">';
		$output .= '<form method="post" id="mainform" action="options.php" enctype="multipart/form-data">';
		$output .= '<div class="wrap wrap-licenses">';
		$output .= '<table class="form-table">';

		// shouldn't happen given if this class is loaded, a plugin is using it
		if ( empty( $license_sections ) ) {

			$message  = esc_html__( 'There are no active SkyVerge plugins that require license keys.', 'skyverge-plugin-updater' );
			$message .= '<a href="https://www.skyverge.com/products/woocommerce-extensions/" style="margin-left:10px;" class="button button-primary">' . esc_html__( 'View Plugins', 'skyverge-plugin-updater' ) . ' &rarr;</a>';

			$output .= '<div class="notice-info notice inline"><p>' . wp_kses_post( $message ) . '</p></div>';

		} else {

			$output .= '<h2>' .  __( 'SkyVerge Plugin License Keys', 'skyverge-plugin-updater' ) . '</h2>';
			$output .= '<p>' . __( 'Enter your plugin license keys here to receive automatic updates.', 'skyverge-plugin-updater') . '</p>';

			ob_start();

			settings_fields( 'skyverge_plugin_license_settings' );

			do_settings_sections( 'skyverge_plugin_license_settings_section' );

			submit_button();

			$output .= ob_get_clean();

		}

		$output .= '</table></div></form>';

		if ( ! $this->is_jilt_active() ) {
			$output .= $this->get_jilt_banner();
		}

		$output .= '</div>'; // end wrapper container

		echo $output;
	}


	/**
	 * Returns HTML for the Jilt banner if not active.
	 *
	 * @since 1.0.0
	 */
	protected function get_jilt_banner() {

		ob_start();
		?>

		<div class="jilt-notice">
			<div class="logo">
				<a href="<?php echo esc_url( $this->get_jilt_url() ); ?>">
					<img src="<?php echo trailingslashit( esc_html( $this->plugin_url  ) ); ?>lib/skyverge/updater/assets/img/jilt-logo-landscape-white.svg" width="150" />
				</a>
			</div>
			<div class="text">
				<h3><?php esc_html_e( 'Increase sales by 15% in 10 minutes or less!', 'skyverge-plugin-updater' ); ?></h3>
				<p><?php esc_html_e( 'Jilt helps you recover lost sales with automated emails.', 'skyverge-plugin-updater' ); ?></p>
			</div>
			<div class="link">
				<a class="btn" href="<?php echo esc_url( $this->get_jilt_url() ); ?>">
					<?php esc_html_e( 'Get Jilt', 'skyverge-plugin-updater' ); ?> &rarr;
				</a>
			</div>
		</div>

		<?php
		return ob_get_clean();
	}


	/**
	 * Registers settings using the WP settings API.
	 *
	 * @since 1.0.0
	 */
	public function register_settings() {

		$settings = $this->get_license_settings();

		add_settings_section(
			'skyverge_plugin_license_settings_section',
			__return_null(),
			'__return_false',
			'skyverge_plugin_license_settings_section'
		);

		foreach ( $settings as $setting ) {

			$setting = wp_parse_args( $setting, array(
				'section'       => 'wc-addons',
				'id'            => null,
				'desc'          => '',
				'name'          => '',
				'size'          => null,
				'options'       => '',
				'std'           => '',
				'min'           => null,
				'max'           => null,
				'step'          => null,
				'chosen'        => null,
				'multiple'      => null,
				'placeholder'   => null,
				'allow_blank'   => true,
				'readonly'      => false,
				'faux'          => false,
				'tooltip_title' => false,
				'tooltip_desc'  => false,
				'field_class'   => '',
			) );

			add_settings_field(
				$setting['id'],
				str_replace( 'WooCommerce ', '' , $setting['name'] ),
				array( $this, 'license_key_callback' ),
				'skyverge_plugin_license_settings_section',
				'skyverge_plugin_license_settings_section',
				$setting
			);

			register_setting( 'skyverge_plugin_license_settings', $setting['id'], array( $this, 'sv_settings_sanitize' ) );
		}
	}


	/**
	 * Registers the license field callback for Software Licensing.
	 *
	 * @since 1.0.0
	 *
	 * @param string[] $args arguments passed by the setting
	 */
	public function license_key_callback( $args ) {

		$sv_option = get_option( $args['id'] );
		$messages  = array();
		$license   = get_option( $args['options']['is_valid_license_option'] );

		if ( $sv_option ) {
			$value = $sv_option;
		} else {
			$value = isset( $args['std'] ) ? $args['std'] : '';
		}

		if( ! empty( $license ) && is_object( $license ) ) {

			// activate_license 'invalid' on anything other than valid, so if there was an error capture it
			if ( false === $license->success ) {

				switch( $license->error ) {

					case 'expired' :
						$class      = 'expired';
						$messages[] = sprintf(
							__( 'Your license key expired on %1$s. Please %2$srenew your license key%3$s.', 'skyverge-plugin-updater' ),
							date_i18n( get_option( 'date_format' ), strtotime( $license->expires, current_time( 'timestamp' ) ) ),
							'<a href="https://skyverge.com/checkout/?edd_license_key=' . $value . '&utm_campaign=admin&utm_source=licenses&utm_medium=expired" target="_blank">',
							'</a>'
						);
						$license_status = 'license-' . $class . '-notice';
					break;

					case 'revoked' :
						$class      = 'error';
						$messages[] = sprintf(
							__( 'Your license key has been disabled. Please %1$scontact support%2$s for more information.', 'skyverge-plugin-updater' ),
							'<a href="https://skyverge.com/contact?utm_campaign=admin&utm_source=licenses&utm_medium=revoked" target="_blank">',
							'</a>'
						);
						$license_status = 'license-' . $class . '-notice';
					break;

					case 'missing' :
						$class      = 'error';
						$messages[] = sprintf(
							__( 'Invalid license. Please %1$svisit your account page%2$s and verify it.', 'skyverge-plugin-updater' ),
							'<a href="https://skyverge.com/checkout/purchase-history/?utm_campaign=admin&utm_source=licenses&utm_medium=missing" target="_blank">',
							'</a>'
						);
						$license_status = 'license-' . $class . '-notice';
					break;

					case 'invalid' :
					case 'site_inactive' :
						$class = 'error';
						$messages[] = sprintf(
							__( 'Your %1$s is not active for this URL. Please %2$svisit your account page%3$s to manage your license key URLs.', 'skyverge-plugin-updater' ),
							$args['name'],
							'<a href="https://skyverge.com/checkout/purchase-history/?utm_campaign=admin&utm_source=licenses&utm_medium=invalid" target="_blank">',
							'</a>'
						);
						$license_status = 'license-' . $class . '-notice';
					break;

					case 'item_name_mismatch' :
						$class      = 'error';
						$messages[] = sprintf( __( 'This appears to be an invalid license key for %s.', 'skyverge-plugin-updater' ), $args['name'] );
						$license_status = 'license-' . $class . '-notice';
					break;

					case 'no_activations_left':
						$class = 'error';
						$messages[] = sprintf( __( 'Your license key has reached its activation limit. %1$sView possible upgrades%2$s now.', 'skyverge-plugin-updater' ), '<a href="https://skyverge.com/checkout/purchase-history/">', '</a>' );
						$license_status = 'license-' . $class . '-notice';
					break;

					case 'license_not_activable':
						$class = 'error';
						$messages[] = __( 'The key you entered belongs to a bundle, please use the product specific license key.', 'skyverge-plugin-updater' );
						$license_status = 'license-' . $class . '-notice';
					break;

					default :
						$class = 'error';
						$error = ! empty(  $license->error ) ?  $license->error : __( 'unknown_error', 'skyverge-plugin-updater' );
						$messages[] = sprintf( __( 'There was an error with this license key: %1%s. Please %2$scontact our support team%3$s.', 'skyverge-plugin-updater' ), $error, '<a href="https://skyverge.com/contact/">', '</a>'  );
						$license_status = 'license-' . $class . '-notice';
					break;
				}

			} else {

				switch( $license->license ) {

					case 'valid' :
					default:
						$class = 'valid';
						$now        = current_time( 'timestamp' );
						$expiration = strtotime( $license->expires, current_time( 'timestamp' ) );

						if ( 'lifetime' === $license->expires ) {

							$messages[] = __( 'License key never expires.', 'skyverge-plugin-updater' );
							$license_status = 'license-lifetime-notice';

						} elseif( $expiration > $now && $expiration - $now < ( DAY_IN_SECONDS * 30 ) ) {

							$messages[] = sprintf(
								__( 'Your license key expires soon! It expires on %1$s. %2$sRenew your license key%3$s.', 'skyverge-plugin-updater' ),
								date_i18n( get_option( 'date_format' ), strtotime( $license->expires, current_time( 'timestamp' ) ) ),
								'<a href="https://skyverge.com/checkout/?edd_license_key=' . $value . '&utm_campaign=admin&utm_source=licenses&utm_medium=renew" target="_blank">',
								'</a>'
							);
							$license_status = 'license-expires-soon-notice';

						} else {

							$messages[] = sprintf(
								__( 'Your license key expires on %s.', 'skyverge-plugin-updater' ),
								date_i18n( get_option( 'date_format' ), strtotime( $license->expires, current_time( 'timestamp' ) ) )
							);
							$license_status = 'license-expiration-date-notice';
						}
					break;
				}
			}

		} else {

			$class = 'empty';
			$messages[] = sprintf( __( 'To receive updates, please enter your valid %s license key.', 'skyverge-plugin-updater' ), $args['name'] );
			$license_status = null;
		}

		$class .= ' ' . sanitize_html_class( $args['field_class'] );

		$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
		$html = '<input type="text" class="' . sanitize_html_class( $size ) . '-text" id="' . sanitize_text_field( $args['id'] ) . '" name="' . sanitize_text_field( $args['id'] ) . '" value="' . esc_attr( $value ) . '"/>';

		if ( ( is_object( $license ) && 'valid' == $license->license ) || 'valid' == $license ) {
			$html .= '<input type="submit" class="button-secondary" name="' . $args['id'] . '_deactivate" value="' . __( 'Deactivate License',  'skyverge-plugin-updater' ) . '"/>';
		}

		$html .= '<label for="' . sanitize_text_field( $args['id'] ) . '"> '  . wp_kses_post( $args['desc'] ) . '</label>';

		if ( ! empty( $messages ) ) {
			foreach( $messages as $message ) {
				$html .= '<div class="sv-license-data sv-license-' . $class . ' ' . $license_status . '">';
				$html .= '<p>' . $message . '</p></div>';
			}
		}

		wp_nonce_field( sanitize_text_field( $args['id'] ) . '-nonce', sanitize_text_field( $args['id'] ) . '-nonce' );
		echo $html;
	}


	/**
	 * Adds a settings tab via javascript.
	 *
	 * @since 1.0.0
	 */
	public function add_settings_tab() {

		$url = $this->get_license_settings_url();

		wc_enqueue_js("
			$( 'nav.nav-tab-wrapper' ).append('<a href=\"" . esc_url( $url ) . "\" class=\"nav-tab skyverge-helper\">" . __( 'SkyVerge Licenses', 'skyverge-plugin-updater' ) . " </a>');
		");
	}


	/**
	 * Changes the active tab to our own tab.
	 *
	 * @since 1.0.0
	 */
	protected function change_active_tab() {

		wc_enqueue_js("
			jQuery(document).ready(function($) {
				$( 'nav.nav-tab-wrapper a' ).each( function() {
					$( this ).removeClass( 'nav-tab-active' );
				});
				$( 'a.skyverge-helper' ).addClass( 'nav-tab-active' );
				// remove the sections WC tries to add
				$( 'div.wrap.wc_addons_wrap p' ).first().remove();
				$( 'div.wrap.wc_addons_wrap br.clear' ).first().remove();
			});
		");
	}


	/**
	 * Moves our licenses output in the DOM so it appears in the right place.
	 *
	 * @since 1.0.0
	 */
	protected function move_output() {

		wc_enqueue_js("
		jQuery(document).ready(function($) {
			$( 'div.wrap.wc_addons_wrap' ).append( $('div.skyverge-helper.container' ) );
		});
	");
	}


	/** Helper functions ***************************************** */


	/**
	 * Settings sanitization.
	 *
	 * Adds a settings error (for the updated message).
	 *
	 * @since 1.0.0
	 *
	 * @param array $input the value inputted in the field
	 * @return string $input sanitized value
	 */
	function settings_sanitize( $input = array() ) {

		$setting_types = array(
			// we may add more types in the future
			'text',
		);

		foreach ( $setting_types as $type ) {

			switch( $type ) {

				case 'text':
					$input = sanitize_text_field( $input );
				break;
			}
		}

		add_settings_error( 'skyverge-license-settings', '', __( 'Settings updated.', 'skyverge-plugin-updater' ), 'updated' );

		return $input;
	}


	/**
	 * Gets license sections to output.
	 *
	 * @since 1.0.0
	 *
	 * @return string[] array of license settings
	 */
	protected function get_license_settings() {

		// this will allow the license class to add new settings here as it's loaded
		return apply_filters( 'skyverge_plugin_license_settings', array() );
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
	 * Checks if a plugin is active.
	 *
	 * @since 1.0.0
	 *
	 * @return bool true if Jilt for WC is installed and active
	 */
	protected function is_jilt_active() {

		$active_plugins = (array) get_option( 'active_plugins', array() );

		if ( is_multisite() ) {
			$active_plugins = array_merge( $active_plugins, array_keys( get_site_option( 'active_sitewide_plugins', array() ) ) );
		}

		$plugin_filenames = array();

		foreach ( $active_plugins as $plugin ) {

			if ( false !== strpos( $plugin, '/' ) ) {

				// normal plugin name (plugin-dir/plugin-filename.php)
				list( , $filename ) = explode( '/', $plugin );

			} else {

				// no directory, just plugin file
				$filename = $plugin;
			}

			$plugin_filenames[] = $filename;
		}

		return in_array( 'jilt-for-woocommerce.php', $plugin_filenames );
	}


	/**
	 * Returns a URL for Jilt upsells or banners.
	 *
	 * @since 1.0.0
	 *
	 * @return string Jilt URL
	 */
	protected function get_jilt_url() {
		return 'https://jilt.com/?partner=1&campaign=SkyVerge+Free+Plugin+Banner&utm_medium=display&utm_source=SkyVerge+Plugin&utm_campaign=SkyVerge+Free+Plugin+Banner';
	}


}

endif;
