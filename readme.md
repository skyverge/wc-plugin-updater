# WooCommerce Plugin Updater

The SkyVerge WooCommerce Plugin Updater library is a tool that helps us serve updates for WooCommerce plugins from SkyVerge.com, using the [Easy Digital Downloads "Software Licensing" plugin](https://easydigitaldownloads.com/downloads/software-licensing/?ref=3324&campaign=sv_github).

This set of classes is not intended for plug-and-play usage, as some of the code here is specific to our site, though it can be adapted for your needs.

Based off the [EDD Software Licensing documentation](http://docs.easydigitaldownloads.com/article/383-automatic-upgrades-for-wordpress-plugins) and [Easy Digital Downloads License Handler](https://github.com/easydigitaldownloads/easy-digital-downloads/blob/master/includes/class-edd-license-handler.php).

### Requirements

 - Plugins implementing this library must use PHP 5.3+
 - Library should be bundled at `lib/skyverge/updater`
   - Recommend adding a custom install path via composer:
   ```
   "require-dev": {
       "mnsami/composer-custom-directory-installer": "1.1.*"
   },
   "extra": {
      "installer-paths": {
        "plugin/lib/skyverge/updater": ["skyverge/wc-plugin-updater"]
      }
    }
   ```

### Usage

Once included in the plugin build, usage is straight-forward, as all settings additions and updater checks are handled by this library.

Ensure the methods below exist (see below for copy/paste implementation if required for your main plugin class), then instantiate the license class in your main plugin file:

```
require_once( $this->get_plugin_path() . '/lib/skyverge/updater/class-skyverge-plugin-license.php');

// item ID is from skyverge.com download WP_Post ID
$this->license = new \SkyVerge\WooCommerce\PluginUpdater\License( $this->get_plugin_file(), $this->get_plugin_path(), $this->get_plugin_url(), $this->get_plugin_name(), $this->get_version(), $this->get_download_id() );
```

That's it! This will handle adding settings for the license keys, validating license keys, and checking for plugin updates.

#### Required methods

These methods must exist in the main plugin class when instantiating the plugin license. Be sure to use the **main plugin file** as the first param passed into the License constructor, which is most important when your plugin has an additional loader class for PHP 5.2 and other sanity checks, so the main plugin class instance's file may not be the same as the plugin file.

```
/**
 * Helper to get the plugin file.
 *
 * @since 1.0.0
 *
 * @return string
 */
public function get_file() {
	return __FILE__;
}


/**
 * Gets the main plugin file.
 *
 * @since 1.0.0
 *
 * @return string
 */
public function get_plugin_file() {

	$slug = dirname( plugin_basename( $this->get_file() ) );
	return trailingslashit( $slug ) . $slug . '.php';
}


/**
 * Helper to get the plugin path.
 *
 * @since 1.0.0
 *
 * @return string the plugin path
 */
public function get_plugin_path() {
	return untrailingslashit( plugin_dir_path( $this->get_file() ) );
}


/**
 * Helper to get the plugin URL.
 *
 * @since 1.0.0
 *
 * @return string the plugin URL
 */
public function get_plugin_url() {
	return untrailingslashit( plugins_url( '/', $this->get_file() ) );
}


/**
 * Helper to return the plugin name.
 *
 * @since 1.0.0
 *
 * @return string plugin name
 */
public function get_plugin_name() {
	return __( 'WooCommerce Plugin Name', 'textdomain' );
}



/**
 * Helper to get the plugin version.
 * ! Be sure the VERSION class constant is set !
 *
 * @since 1.0.0
 *
 * @return string the plugin version
 */
public function get_version() {
	return self::VERSION;
}


/**
 * Helper to get the plugin download ID.
 *
 * @since 1.0.0
 *
 * @return int the skyverge.com download ID
 */
protected function get_download_id() {
	return 1234;
}
```
