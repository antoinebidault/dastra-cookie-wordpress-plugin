<?php
/**
 * @package Dastra
 * @version 0.1.6
 * Plugin Name: Dastra
 * Plugin URI: http://wordpress.org/plugins/dastra/
 * Description: Dastra is a cookie consent management platform
 * Author: Dastra
 * Version: 0.1.6
 * Author URI: https://dastra.eu
 *
 * Text Domain: dastra
 * Domain Path: /languages/
*/

add_action('admin_menu', 'dastra_create_menu');

function dastra_create_menu() {
  add_menu_page(__('Dastra Settings', 'dastra'), __('Dastra Settings', 'dastra'), 'administrator', __FILE__, 'dastra_plugin_settings_page' , 'https://www.dastra.eu/favicon.ico');
}

function dastra_plugin_settings_page() {
  if (isset($_GET["workspaceId"]) && !empty($_GET["workspaceId"])) {
    update_option("workspace_id", sanitize_text_field($_GET["workspaceId"]));
  }

  if (isset($_GET["widgetId"]) && !empty($_GET["widgetId"])) {
    update_option("widget_id", sanitize_text_field($_GET["widgetId"]));
  }

  if (isset($_GET["publicKey"]) && !empty($_GET["publicKey"])) {
    update_option("public_key", sanitize_text_field($_GET["publicKey"]));
  }

  if (isset($_GET["trackUser"]) && !empty($_GET["trackUser"])) {
    update_option("track_user", $_GET["trackUser"] == 'true');
  }

  $widget_id = get_option("widget_id");
  $workspace_id = get_option("workspace_id");
  $is_dastra_working = isset($widget_id) && !empty($widget_id);

  // Check the callback nonce
  $is_nonce_valid = true;
  if ($is_dastra_working && isset($_GET["_wpnonce"])) {
    $is_nonce_valid = wp_verify_nonce($_GET["_wpnonce"], "dastra-connect");
  }

  $http_callback = "http" . (($_SERVER['SERVER_PORT'] == 443) ? "s://" : "://") . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
  $add_to_dastra_link = wp_nonce_url("https://app.dastra.eu/connect/cookie-widget?returnUrl=$http_callback", "dastra-connect");


  if (!$is_nonce_valid){
    ?>
    <div class="wrap dastra-wrap">
      <div class="dastra-modal">
        <h2 class="dastra-title"><?php _e('Error during installation', 'dastra'); ?></h2>
        <p>
          <?php _e('The url provided is not valid', 'dastra'); ?>
        </p>
      </div>
    </div>
  <?php
  }
  else if ($is_dastra_working) {
  ?>
  <div class="wrap dastra-wrap">
    <div class="dastra-modal">
      <h2 class="dastra-title"><?php _e('The Dastra CMP is installed', 'dastra'); ?></h2>
      <p class="dastra-subtitle"><?php _e('The Dastra CMP will be visible on your pages.', 'dastra'); ?></p>
      <a class="dastra-button dastra" href="https://app.dastra.eu/workspace/<?php echo $workspace_id ?>/cookie-widget/integration/<?php echo $widget_id ?>/edit"><?php _e('Go to my Dastra settings', 'dastra'); ?></a>
      <a class="dastra-button dastra" href="https://app.dastra.eu/workspace/<?php echo $workspace_id ?>/cookie-widget/analytics?widgetId=<?php echo $widget_id ?>"><?php _e('Analytics', 'dastra'); ?></a>
      <a class="dastra-button dastra" href="<?php echo $add_to_dastra_link; ?>"><?php _e('Reconfigure', 'dastra'); ?></a>
    </div>
    <p class="dastra-notice"><?php _e('Loving Dastra <b style="color:red">♥</b> ? Rate us on the <a target="_blank" href="https://wordpress.org/support/plugin/dastra/reviews/?filter=5">Wordpress Plugin Directory</a>', 'dastra'); ?></p>
  </div>
  <?php
  } else {
  ?>
  <div class="wrap dastra-wrap">
    <div class="dastra-modal">
      <h2 class="dastra-title"><?php _e('Connect with your Dastra CMP', 'dastra'); ?></h2>
      <p class="dastra-subtitle"><?php _e('.This link will redirect you to Dastra and configure your Wordpress. Magic', 'dastra'); ?></p>
      <a class="dastra-button dastra" href="<?php echo $add_to_dastra_link; ?>"><?php _e('Connect with Dastra', 'dastra'); ?></a>
    </div>
  </div>
  <?php
  }
}

add_action('wp_footer', 'dastra_hook_footer', 1);
add_action('admin_enqueue_scripts', 'dastra_enqueue_stylesheet');

function dastra_enqueue_stylesheet() {
  wp_enqueue_style( 'style_dastra' , plugins_url('assets/style.css' , __FILE__ ), array());
}

// Push the Dastra SDK async script
add_action( 'wp_enqueue_scripts', 'dastra_enqueue_sdk_js');

function dastra_enqueue_sdk_js(){
  $public_key = get_option('public_key');
	if (isset($public_key) && !empty($public_key)){
		wp_enqueue_script( 'script_dastra', 'https://cdn.dastra.eu/sdk/dastra.js?key='. $public_key .'#asyncload', array(), null, true);
	}
}

function dastra_sync_wordpress_user() {
  $output = "";

  if (is_user_logged_in()) {
    $current_user = wp_get_current_user();
  }

  if (!isset($current_user)) {
    return "";
  }

  $email = $current_user->user_email;
  $nickname = $current_user->display_name;

  $output .= 'window.dastra = window.dastra || [];';
  if (!empty($email)) {
    $output .= 'dastra.push(["set", "cookie:userId", "' . $email . '"]);';
  } else if (!empty($nickname)) {
    $output .= 'dastra.push(["set", "cookie:userId", "' . $nickname . '"]);';
  }

  return $output;
}


function dastra_hook_footer() {
  $widget_id = get_option('widget_id');
  $public_key = get_option('public_key');
  $track_user = get_option('track_user');
  $locale = str_replace("_", "-", strtolower(get_locale()));

  if (!isset($widget_id) || empty($widget_id)) {
    return;
  }
  
  $output = "<div id='dastra-cookie-consent' data-widgetid='". $widget_id ."' data-lang='". $locale ."'></div>";

  if ($track_user) {
    $output .= "<script>";
    $output .=    dastra_sync_wordpress_user();
    $output .= "</script>";
  }
  
  echo $output;
}
