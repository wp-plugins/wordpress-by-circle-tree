<?php
/*
Plugin Name: WordPress by Circle Tree
Plugin URI: http://mycircletree.com/
Description: Secure Login Screen for Circle Tree powered websites
Author: Circle Tree, LLC
Version: 3.0.7
Author URI: http://mycircletree.com/
*/
/**
 * Disable login lockdown completely 
 */
defined('LOGIN_LOCKDOWN') OR define('LOGIN_LOCKDOWN', TRUE);
/**
 * Shorthand utility
 */ 
defined('DS') OR define('DS', DIRECTORY_SEPARATOR); 
require_once WP_PLUGIN_DIR . DS . 'wordpress-by-circle-tree' . DS . 'includes' . DS . 'class.wp_login_lockdown.php';
/**
 * Number of password attempts before displaying a CAPTCHA
 */
$lockdown = new wp_login_lockdown;
if (! defined('LOGIN_LOCKDOWN_ATTEMPTS'))  {
	$setting = $lockdown->get_setting('login_lockdown_attempts');
	//Default override
	if (false === $setting) {
		$setting = 3;
	}
	define('LOGIN_LOCKDOWN_ATTEMPTS', $setting);
}

/**
 * Number of CAPTCHA cycles before sending an admin email
 */
defined('LOGIN_LOCKDOWN_RESETS') OR define('LOGIN_LOCKDOWN_RESETS', 2);
/**
 * Back compat with WordPress 3.4
 */
defined('DAY_IN_SECONDS') OR define('DAY_IN_SECONDS', 86400);


require_once WP_PLUGIN_DIR . DS . 'wordpress-by-circle-tree' . DS . 'includes' . DS . 'class.wp_by_ct.php';

new wp_by_ct;

