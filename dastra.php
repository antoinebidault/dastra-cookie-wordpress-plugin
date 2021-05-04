<?php
/**
 * @package Dastra
 * @version 0.1
 * Plugin Name: Dastra
 * Plugin URI: http://wordpress.org/plugins/dastra/
 * Description: Dastra is a cookie consent management platform
 * Author: Dastra SaS
 * Version: 0.1
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
    update_option("workspace_id", $_GET["workspaceId"]);
  }

  if (isset($_GET["widgetId"]) && !empty($_GET["widgetId"])) {
    update_option("widget_id", $_GET["widgetId"]);
  }

  if (isset($_GET["publicKey"]) && !empty($_GET["publicKey"])) {
    update_option("public_key", $_GET["publicKey"]);
  }

  if (isset($_GET["trackUser"]) && !empty($_GET["trackUser"])) {
    update_option("track_user", $_GET["trackUser"]);
  }

  $widget_id = get_option('widget_id');
  $is_dastra_working = isset($widget_id) && !empty($widget_id);
  $http_callback = "http" . (($_SERVER['SERVER_PORT'] == 443) ? "s://" : "://") . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
  $add_to_dastra_link = "https://app.dastra.eu/connect/cookie-widget?returnUrl=$http_callback";
?>

<link rel="stylesheet" href="<?php echo plugins_url("assets/style.css", __FILE__ );?>">
  <?php
  if ($is_dastra_working) {
  ?>
  <div class="wrap dastra-wrap">
    <div class="dastra-modal">
      <h2 class="dastra-title"><?php _e('Connected with Dastra.', 'dastra'); ?></h2>
      <p class="dastra-subtitle"><?php _e('You can now use Dastra from your homepage.', 'dastra'); ?></p>
      <a class="dastra-button dastra" href="https://app.dastra.eu/workspace/<?php echo $workspace_id ?>/0/cookie-widget/integration/<?php echo $widget_id ?>/edit"><?php _e('Go to my Dastra settings', 'dastra'); ?></a>
      <a class="dastra-button dastra" href="https://app.dastra.eu/workspace/<?php echo $workspace_id ?>/0/cookie-widget/analytics?widgetId=<?php echo $widget_id ?>"><?php _e('Analytics', 'dastra'); ?></a>
      <a class="dastra-button dastra" href="<?php echo $add_to_dastra_link; ?>"><?php _e('Reconfigure', 'dastra'); ?></a>
    </div>
    <p class="dastra-notice"><?php _e('Loving Dastra <b style="color:red">♥</b> ? Rate us on the <a target="_blank" href="https://wordpress.org/support/plugin/dastra/reviews/?filter=5">Wordpress Plugin Directory</a>', 'dastra'); ?></p>
  </div>

  <?php
  } else {
  ?>
  <div class="wrap dastra-wrap">
    <div class="dastra-modal">
      <h2 class="dastra-title"><?php _e('Connect with your Dastra app', 'dastra'); ?></h2>
      <p class="dastra-subtitle"><?php _e('This link will redirect you to Dastra and configure your Wordpress. Magic', 'dastra'); ?></p>
      <a class="dastra-button dastra" href="<?php echo $add_to_dastra_link; ?>"><?php _e('Connect with Dastra', 'dastra'); ?></a>
    </div>
  </div>
  <?php
  }
}

add_action('wp_head', 'dastra_hook_head', 1);

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

  if (!empty($email)) {
    $output .= 'dastra.push(["set", "cookie:userId", "' . $email . '"]);';
  } else if (!empty($nickname)) {
    $output .= 'dastra.push(["set", "cookie:userId", "' . $nickname . '"]);';
  }

  return $output;
}

function dastra_hook_head() {
  $widget_id = get_option('widget_id');
  $public_key = get_option('public_key');
  $track_user = get_option('track_user');
  $locale = str_replace("_", "-", strtolower(get_locale()));

  if (!isset($widget_id) || empty($widget_id)) {
    return;
  }

  $output ="<script src='https://cdn.dastra.eu/dist/dastra.js?key=$public_key' async ></script>";
  $output .= "<div id='cookie-consent' data-widgetid='$widget_id' data-lang='$locale'></div>";

  
  if (!isset($widget_id) || empty($widget_id)) {
    $output .= "<script>";
    $output .= dastra_sync_wordpress_user();
    $output .= "</script>";
  }
  
  echo $output;
}
