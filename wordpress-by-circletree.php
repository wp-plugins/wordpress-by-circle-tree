<?php
/*
Plugin Name: WordPress by Circle Tree
Plugin URI: http://mycircletree.com/
Description: Secure Login Screen for Circle Tree powered websites
Author: Circle Tree, LLC
Version: 2.0.2
Author URI: http://mycircletree.com/
*/
defined('LOGIN_LOCKDOWN') OR define('LOGIN_LOCKDOWN', TRUE); 
/**
 * Number of password attempts before displaying a CAPTCHA
 */
defined('LOGIN_LOCKDOWN_ATTEMPTS') OR define('LOGIN_LOCKDOWN_ATTEMPTS', 3);

/**
 * Number of CAPTCHA cycles before sending an admin email
 */
defined('LOGIN_LOCKDOWN_RESETS') OR define('LOGIN_LOCKDOWN_RESETS', 2);


final class wp_by_ct {
	const PLUGIN_DIR_NAME = 'wordpress-by-circle-tree'; 
	/**
	 * @var string css to apply custom icon over the WordPress one
	 */
	const CIRCLETREE_ADMINBAR_ICON_STYLE = '<style>
		#wp-admin-bar-wp-logo > .ab-item .ab-icon,
		#wpadminbar.nojs #wp-admin-bar-wp-logo:hover > .ab-item .ab-icon,
		#wpadminbar #wp-admin-bar-wp-logo.hover > .ab-item .ab-icon {
				background-image: url("https://s3.amazonaws.com/myct2/footer-logo-16px.png");
				background-position:center center;
			}
		</style>';
	/**
	 * @access private
	 */
	private static $plugin_url = null;
	
	/**
	 * stores application instance 
	 */
	public function __construct() {
		/**
		 * actions
		 */
		add_action('admin_bar_menu', array($this, 'admin_bar'), 50);
		add_action('wp_dashboard_setup', array(&$this, 'tweak_dashboard') );
		add_action('in_admin_footer', array(&$this , 'admin_footer_links'));
		add_action('login_head', array(&$this, 'echo_stylesheet_link'));
		add_action('wp_footer', array(&$this, 'admin_bar_icon'));
		
		//Remove WordPress/version # from Head for security purposes
		remove_action('wp_head', 'wp_generator');
		/**
		 * filters
		 */
		add_filter('login_headertitle', array(&$this, 'login_header_title'));
		add_filter('login_headerurl', array(&$this, 'login_header_url'));
		add_filter('admin_footer_text', '__return_false');
	}
	public static function echo_stylesheet_link () {
		echo '<link rel="stylesheet" type="text/css" href="' . wp_by_ct::get_url() . '/circletree-login.css" />';
	}
	/**
	 * get the url for the plugin directory with a trailing slash
	 */
	public static function get_url() {
		if (is_null(self::$plugin_url)) {
			if (is_multisite())
				self::$plugin_url = WPMU_PLUGIN_URL.'/'.wp_by_ct::PLUGIN_DIR_NAME.'/';
			else
				self::$plugin_url = WP_PLUGIN_URL.'/'.wp_by_ct::PLUGIN_DIR_NAME.'/';
		}
		return self::$plugin_url;
	}
	/**
	 * Gets link to client area
	 * @param string $id menu node ID
	 * @param string $parent ID of parent menu node to add to
	 */
	private function get_my_account_menu_item ($id, $parent) {
		return 	 
			array(
					'id'=>$id,
					'parent'=>$parent,
					'title'=>"My Circle Tree Account",
					'href'=>'https://mycircletree.com/client-area/',
					'meta'=>array('target'=>'_blank')
			);
	}
	public function admin_bar () {
		global $wp_admin_bar;
		$wp_admin_bar->remove_menu('wporg');
		$wp_admin_bar->remove_menu('about');
		$wp_admin_bar->add_menu(array(
				'id'=>'ct-tutorials',
				'parent'=>'wp-logo',
				'title'=>"WordPress Video Tutorials",
				'href'=>'http://mycircletree.com/client-area/knowledgebase.php?action=displaycat&catid=2',
				'meta'=>array('target'=>'_blank')
		));
		
		$wp_admin_bar->add_menu(
				$this->get_my_account_menu_item('ct-account-logo', 'wp-logo')
			);
		$wp_admin_bar->add_menu(
				$this->get_my_account_menu_item('ct-account-user', 'user-actions')
			);
	}
	public function login_header_title($title) {
		return 'Go to ' . get_option('blogname');
	}
	public function login_header_url($url) {
		return get_bloginfo('url');
	}
	public function tweak_dashboard () {
		wp_add_dashboard_widget('byct_news', '<img style="vertical-align:middle;opacity:0.3;" width="30" height="30" alt="Website by Circle Tree" src="https://s3.amazonaws.com/myct2/footer-logo-30px.png"/> Circle Tree News', array(&$this, 'news_widget_content'));
		wp_enqueue_script('wp_by_ct', wp_by_ct::get_url().'jquery.custom.wp_by_ct.js');
		remove_meta_box( 'dashboard_secondary', 'dashboard', 'side' );
		remove_meta_box( 'dashboard_primary', 'dashboard', 'side' );
		remove_meta_box( 'dashboard_plugins', 'dashboard', 'normal' );
		remove_meta_box( 'dashboard_incoming_links', 'dashboard', 'normal' );
		remove_meta_box( 'w3tc_latest', 'dashboard', 'normal' );
		remove_meta_box( 'w3tc_pagespeed', 'dashboard', 'normal' );
	}
	public function admin_footer_links() {
			echo '<a href="http://mycircletree.com/client-area/knowledgebase.php?action=displaycat&catid=2" target="_blank">WordPress Video Tutorials</a>';
			echo ' | <a href="https://mycircletree.com/client-area/submitticket.php" target="_blank">Contact Circle Tree Support</a>';
			echo ' | <a target="_blank" style="text-decoration:none;font-size:10px;color:#666" href="http://mycircletree.com">Site design &amp; hosting by Circle Tree <img style="vertical-align:middle;opacity:0.3;" width="30" height="30" alt="Website by Circle Tree" src="https://s3.amazonaws.com/myct2/footer-logo-30px.png"/></a>';
			$this->admin_bar_icon();
	}
	public function news_widget_content() {
		echo '<ul id="byct_news_content"></ul><a href="#" id="refreshCTNews" class="button">Refresh</a>';
	}
	public function admin_bar_icon () {
		if (is_user_logged_in() && is_admin_bar_showing())
			echo wp_by_ct::CIRCLETREE_ADMINBAR_ICON_STYLE;
	}
}
new wp_by_ct;

/**
 * Login Lockdown Class
 * @author robertgregor
 */
final class wp_login_lockdown {
	const TRANSIENT_NAME = 'byct_failed_logins';
	const BLOCKED_IP_NAME = 'byct_blocked_ips';
	//24 hours
	const TRANSIENT_TIMEOUT = 86400;
	private $recaptcha_keys = array(
			'public'=>'6LfQidUSAAAAAK7jn1CmndZdjiHOtcNDFWBCBaaN',
			'private'=>'6LfQidUSAAAAANudouhBvNSEHphlJzBPlKNo9PZq'
		);
	public static $remote_ip;
	private $message;
	private $page_id;
	function __construct() {
		$this->get_remote_ip();
		add_action('login_form', array(&$this, 'login_form_secure'));
		add_filter('wp_login_failed',array(&$this, 'login_failed'));
		add_filter('login_errors',array(&$this, 'login_error_message'));
		add_filter('wp_login',array(&$this, 'login_success'));
		add_action('login_init', array(&$this, 'login_lockdown'));
		add_action('admin_init', array(&$this, 'admin_init'));
		add_action('admin_menu', array(&$this, 'admin_menu'));
		add_filter('contextual_help', array(&$this, 'help'), 10, 3);
	}
	public function admin_init() {
		if (isset($_REQUEST['action']) && isset($_REQUEST['page']) && $_REQUEST['page'] == 'circle_tree_login') {
			if (! wp_verify_nonce($_GET['nonce'], 'wp_login_lockdown') ) 
				return;
			switch ($_REQUEST['action']) {
				case 'block':
					if (filter_var($_REQUEST['ip'], FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE)) {
						$this->block_ip($_REQUEST['ip']);
						wp_redirect('options-general.php?page=circle_tree_login&msg=1');
					} else {
						wp_redirect('options-general.php?page=circle_tree_login&msg=4');
					}
				break;
				case 'unblock':
					$success = $this->unblock_ip($_REQUEST['ip']);
					if ($success)
						wp_redirect('options-general.php?page=circle_tree_login&msg=2');
					else 
						wp_redirect('options-general.php?page=circle_tree_login&msg=3');
				break;
			}
		}
	}
	public function admin_menu () {
		$this->page_id = add_options_page('Custom WordPress Website by Circle Tree','Circle Tree Secure Login','manage_options','circle_tree_login',array($this, 'settings_page'));
		add_action("admin_print_scripts-{$this->page_id}", array(&$this, 'admin_scripts'));
	}
	public function admin_scripts () {
		wp_enqueue_script('jquery');
		wp_register_style('byct_css', wp_by_ct::get_url() . '/circletree-login.css');
		wp_enqueue_style('byct_css');
	}
	public function settings_page() { ?>
	<div class="wrap">
	<?php screen_icon();?>
	<h2>Circle Tree Secure Login</h2>
		<?php if (isset($_REQUEST['msg'])) : ?>
		<div class="updated inline">
			<?php if ($_REQUEST['msg'] == 1) :?>
				<p>That IP address has been blocked</p>
			<?php endif;?>
			<?php if ($_REQUEST['msg'] == 2) :?>
				<p>That IP has been unblocked</p>
			<?php endif;?>
			<?php if ($_REQUEST['msg'] == 3) :?>
				<p class="error" >There was an error processing that request. Please reload the page and try again.</p>
			<?php endif;?>
			<?php if ($_REQUEST['msg'] == 4) :?>
				<p class="error" >Invalid IP.</p>
			<?php endif;?>
		</div>
		<script>
		jQuery(function($) {
			setTimeout(function  () {
				$(".updated.inline").slideUp(500); 		  
			}, 2000);
		});
		</script>
		<?php endif; 
		$log = $this->get_transient();
		if ($log) :
			if (isset($log['reset'])) : ?>
			<h3>Invalid passwords requiring a CAPTCHA:</h3>
			<ul id="reset_log">
				<?php 
				foreach ($log['reset'] as $ip => $count ) {
					$class = ($ip == self::$remote_ip) ? ' class="warning"' : ''; 
					echo '<li'.$class.'>IP: '.$ip.'. CAPTCHAS: '.$count.' &rarr;';
						echo '<a class="button-primary" href="'.admin_url('options-general.php?page=circle_tree_login&action=block&ip='.$ip.'&nonce='.wp_create_nonce('wp_login_lockdown')).'">Block</a>';
					echo '</li>';
				} ?>
			</ul>
			<?php endif; ?>
		<?php else:?>
			<p>Log is empty!</p>
		<?php endif;?>
		<h3>Manually Block an IP:</h3>
		<form method="GET" action="<?php echo admin_url('options-general.php')?>">
			<input type="hidden" name="page" value="circle_tree_login" />
			<input type="hidden" name="action" value="block"/>
			<input type="hidden" name="nonce" value="<?php echo wp_create_nonce('wp_login_lockdown'); ?>"/>
			<input type="text" name="ip" size="10"/>
			<?php submit_button('Block');?>
		</form>
		<?php  if ($this->get_blocked_ips()) :?>
			<h3>Blocked IPS</h3>
			<ul>
			<?php foreach ($this->get_blocked_ips() as $ip) :?>
				<li><?php echo $ip?>
					<?php echo '<a class="button" href="'.admin_url('options-general.php?page=circle_tree_login&action=unblock&ip='.$ip.'&nonce='.wp_create_nonce('wp_login_lockdown')).'">Unblock</a>';?>
				</li>
			<?php endforeach; ?>
			</ul>
		<?php endif; ?>
		<?php if ( $this->get_log() ) :?>
		<h3>Failed logins over the past 24 hours</h3>
			<ul>
				<?php $log_array = explode(PHP_EOL, $this->get_log());
				foreach ($log_array as $item) {
					echo '<li>' . $item . '</li>';
				}
				?>
			</ul>
		<?php endif; ?>
	</div>
		<?php 
	}
	public function login_form_secure () {
		echo  '<h2 style="text-align:center">';
		echo '	<img style="vertical-align:middle;" src="'.wp_by_ct::get_url().'/lock.png" height="" width="" alt="Lock Icon" />';
		echo '	Secure Login <a target="_blank" style="text-decoration:none;color:#000" href="http://mycircletree.com">by Circle Tree ';
		echo '	<img style="vertical-align:middle;opacity:0.5;" width="30" height="30" alt="Website by Circle Tree" src="https://s3.amazonaws.com/myct2/footer-logo-30px.png"/></a></h2>';
		echo  '<h3 style="text-align:center">IP Logged '.self::$remote_ip.'</h3>';
	}
	public function login_failed ($username) {
		$this->log('Failed login from IP: '.self::$remote_ip.'. Username: '.$username);
		$this->set_failed_login();
	}
	public function login_error_message ($error) {
		$message = '<h2 class="login_error" >'.$error;
		//Make sure this is an error that triggers the wp_login_failed filter
		if (! strstr($error, 'empty') ) {
			$message .= $this->get_lockdown_message().'<br/>';
		}
		return $message;
	}
	public function help ($text, $screen_id, $screen) {
		if ($screen_id = $this->page_id) {
			$text = ' <h1>Overview</h1>';
			$text .= '<p>Every failed login will set a transient, and if it is greater than a defined threshhold, it will trigger a reCAPTCHA';
			$text .= ' challenge. If that threshold is broken it will send an administrator notification of the failed attempts. All failed logins are';
			$text .= ' logged below.<br/> <b>You can also block IP addresses of malicious users from accessing the login page; It will redirect them to the homepage.</b></p>';
		}
		return $text;
	}
	public function login_success () {
		$this->reset_failed_logins();
	}
	public function login_lockdown () {
		require_once 'recaptchalib.php';
		if (! LOGIN_LOCKDOWN) return;
		if ($this->valid_captcha()) {
			$this->reset_failed_logins();
			wp_redirect('wp-login.php');			
		}
		//Redirect blocked IPS to homepage
		if ( $this->is_ip_blocked() )
			wp_redirect(get_bloginfo('url'));
		
		//Check number of resets (full CAPTCHA cycles)
		if ( $this->get_resets() >= LOGIN_LOCKDOWN_RESETS) {
			//Flood protection, max 1 email every 5 min per ip
			$flood_key = 'byct_lockdown_emails'.self::$remote_ip;
			if ( ! get_transient($flood_key) ) {
				$this->send_email();
				set_transient( $flood_key, true, 300 );
			} 
		}
		//Check if number of failed logins
		if ( ($this->get_failed_logins() + 1 ) >= $this->get_total_failures_allowed() )
			$this->display_capcha_form();
	}
	private function send_email() {
		$subject = 'Invalid Login on '.get_bloginfo('name');
		$message = 'There have been a number of failed login attempts on your website: '.get_bloginfo('name').PHP_EOL;
		$message .= 'If you have forgotten your password, please go to: '.PHP_EOL.get_bloginfo('wpurl').'/wp-login.php?action=lostpassword'.PHP_EOL.PHP_EOL;
		$message .= 'If this is unauthorized activity, please block the remote IP by going here: '.PHP_EOL;
		$message .= get_bloginfo('wpurl').'/wp-admin/options-general.php?page=circle_tree_login&action=block&ip=' . self::$remote_ip;
		wp_mail(get_bloginfo('admin_email'), $subject, $message);
	}
	private function block_ip ($ip) {
		$current = $this->get_blocked_ips();
		if (in_array($ip, $current)) return;
		else $current[] = $ip;
		update_option($this::BLOCKED_IP_NAME, $current);
	}
	/**
	 * @param string $ip
	 * @return boolean true if found and unblocked
	 */
	private function unblock_ip ($ip) {
		$current = $this->get_blocked_ips();
		if (in_array($ip, $current)) {
			$key = array_search($ip, $current);
			unset($current[ $key ]);
			update_option($this::BLOCKED_IP_NAME, $current);
			return true;
		} else {
			return false;
		}
	}
	private function get_blocked_ips () {
		return get_option($this::BLOCKED_IP_NAME);
	}
	private function is_ip_blocked () {
		$ips = $this->get_blocked_ips();
		return in_array(self::$remote_ip, $ips);
	}
	private function display_capcha_form() {
		ob_start();
		ob_implicit_flush(false);
		wp_by_ct::echo_stylesheet_link();?>
		<script src="https://ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js" language="javascript"></script>
		<script type="text/javascript">
		jQuery(function($) {
			$("form").on('submit', function  () {
				setTimeout( function  () {
					$("input").attr('disabled',true)
					$("input[type=text]").val("Please wait..."); 
				},100);
			});
		});
		</script>
		<div id="lockdown">
			<form method="POST" action="">
				<h1>Too many login attempts</h1>
				<p>Please verify your humanity (this is to protect against brute force attacks)</p>
				<?php echo recaptcha_get_html($this->recaptcha_keys['public'], $this->message);?>
				<input type="submit" value="Verify" />
			</form>
		</div>
		<?php 
		$str = ob_get_clean();
		wp_die($str,'ERROR | TOO MANY LOGIN ATTEMPTS', array('response'=>503));
	}
	private function valid_captcha() {
		if (! isset($_POST["recaptcha_challenge_field"]) || ! isset($_POST["recaptcha_response_field"])) return;
		$resp = recaptcha_check_answer ($this->recaptcha_keys['private'],
				$_SERVER["REMOTE_ADDR"],
				$_POST["recaptcha_challenge_field"],
				$_POST["recaptcha_response_field"]);
		if (! $resp->is_valid ) {
			sleep(2);
			$this->message = $resp->error;
			return false;
		} else {
			return true;
		}
	}
	private function get_lockdown_message() {
		return 'You have '. $this->get_remaining_attempts() . ' login '._n('attempt', 'attempts', $this->get_remaining_attempts()).' remaining';
	}
	private function get_remaining_attempts() {
		return $this->get_total_failures_allowed() - $this->get_failed_logins();
	}
	private function get_failed_logins() {
		$logins = $this->get_transient();
		if (! $logins || ! isset($logins[ self::$remote_ip ]))
			return 0;
		else return $logins[ self::$remote_ip ];
	}
	/**
	 * gets total number of CAPTCHAs entered
	 * @return int $resets number of resets
	 */
	private function get_resets() {
		$logins = $this->get_transient();
		if (! $logins || ! isset($logins['reset'][ self::$remote_ip ]))
			return 0;
		else return $logins['reset'][ self::$remote_ip ];
	}
	private function get_transient() {
		return get_transient($this::TRANSIENT_NAME);
	}
	private function reset_failed_logins() {
		$current = $this->get_transient();
		unset($current[ self::$remote_ip ]);
		if (! isset($current['reset'])) $current['reset'] = array();
		if (isset($current['reset'][ self::$remote_ip ])) {
			$resets = $current['reset'][ self::$remote_ip ];
			$current['reset'][ self::$remote_ip ] = $resets +1;
		} else {
			$current['reset'][ self::$remote_ip ] = 1;
		}
		$this->save_transient($current);
	}
	private function set_failed_login() {
		$current = $this->get_transient();
		if (isset($current[ self::$remote_ip ])) {
			$current[ self::$remote_ip ] += 1;
		} else {
			$current[ self::$remote_ip ] = 1;
		}
		$this->save_transient($current);
	}
	private function log ($msg) {
		$current = get_transient('byct_login_log');
		if ($current) $current .= $msg . PHP_EOL;
		else $current = $msg . PHP_EOL;
		set_transient('byct_login_log', $current, 86400);
	}
	private function get_log() {
		return get_transient('byct_login_log');
	}
	private function save_transient($value) {
		set_transient($this::TRANSIENT_NAME, $value, $this::TRANSIENT_TIMEOUT);
	}
	private function get_total_failures_allowed() {
		return LOGIN_LOCKDOWN_ATTEMPTS;
	}
	private function get_remote_ip () {
		if (isset($_SERVER["HTTP_X_FORWARDED"])) {
			self::$remote_ip =  $_SERVER["HTTP_X_FORWARDED"];
		} elseif (isset($_SERVER["HTTP_FORWARDED_FOR"])) {
			self::$remote_ip =  $_SERVER["HTTP_FORWARDED_FOR"];
		} elseif (isset($_SERVER["HTTP_FORWARDED"])) {
			self::$remote_ip =  $_SERVER["HTTP_FORWARDED"];
		} elseif (isset($_SERVER["HTTP_X_FORWARDED"])) {
			self::$remote_ip =  $_SERVER["HTTP_X_FORWARDED"];
		} elseif (isset($_SERVER["HTTP_X_FORWARDED_FOR"])) {
			self::$remote_ip =  $_SERVER["HTTP_X_FORWARDED_FOR"];
		} else {
			self::$remote_ip =  $_SERVER["REMOTE_ADDR"];
		}
	}
}
new wp_login_lockdown;