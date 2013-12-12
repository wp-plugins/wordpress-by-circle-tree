<?php
/**
 * Login Lockdown Class
 * @author robertgregor
 */
final class wp_login_lockdown {
    /**
     * Transient to store failed logins
     * @var string
     */
    const TRANSIENT_NAME = 'byct_failed_logins';
    const TRANSIENT_NAME_LOG = 'byct_login_log';
    /**
     * Option name to block IPS
     * @var string
     */
    const BLOCKED_IP_NAME = 'byct_blocked_ips';
    /**
     * Option name for whitelisted IPS
     * @var string 
     */
    const WHITELISTED_IP_NAME = 'byct_whitelisted_ips';
    /**
     * How long to cache failed logins
     * @var int 24 hours
     */
    const TRANSIENT_TIMEOUT = 86400;
    /**
     * Settings Key name in wp_options table
     * @var string
     */
    const SETTINGS_KEY = 'byct_settings';
    private $recaptcha_keys = array(
            'public'=>'6LfQidUSAAAAAK7jn1CmndZdjiHOtcNDFWBCBaaN',
            'private'=>'6LfQidUSAAAAANudouhBvNSEHphlJzBPlKNo9PZq'
    );
    public static $remote_ip;
    private $message = false;
    private $settings_page_id;
    private $log_page_id;
    /**
     * Blacklisted usernames
     * @var array
     */
    private $admin_usernames = array(
            'admin',
            'administrator',
            'root'
    );
    private function get_blacklisted_usernames ()
    {
        $defaults = $this->admin_usernames;
        $usernames_string = $this->get_setting('blacklisted_admin_usernames');
        $usernames_array = array();
        if ($usernames_string != '') {
            $usernames_array = explode(',', $usernames_string);
        }
        return array_merge($usernames_array, $defaults);
    }
    /**
     * Registered Settings
     * @var array
    */
    private $settings;
    function __construct() {
        $this->get_remote_ip();
        
        //Actions
        add_action('login_form', array(&$this, 'login_form_secure'));
        add_action('login_init', array(&$this, 'login_lockdown'));
        add_action('admin_init', array(&$this, 'admin_init'));
        add_action('admin_menu', array(&$this, 'admin_menu'));
        add_action('right_now_table_end', array($this, 'admin_home_failed_logins'));
        add_action('wp_ajax_by_ct_action', array($this, 'admin_init'));
        
        //Filters
        add_filter('wp_login_failed',array(&$this, 'login_failed'));
        add_filter('login_errors',array(&$this, 'login_error_message'));
        add_filter('wp_login',array(&$this, 'login_success'));
        add_filter('validate_username', array($this, 'validate_username'), 10, 2);
    }
    /**
     * Check if a restricted admin name is trying to be used
     * @param bool $valid
     * @param string $username
     * @return boolean
     */
    public function validate_username ($valid, $username)
    {
        if ( $this->get_setting('blacklist_admin')) {
            if (in_array($username, $this->admin_usernames)) {
                $this->log('New account creation disabled with this username', $username, self::$remote_ip);
                $valid = false;
            }
        }
        return $valid;
    }
    public function admin_home_failed_logins  ()
    {
        $url = admin_url('index.php?page=circle_tree_login_log');
        echo '<tr>';
        echo    '<td class="b"><a href="'.$url.'">'.$this->get_total_failed_logins().'</a></td>';
        echo    '<td class="t"><a href="'.$url.'">Failed Logins</a></td>';
        echo '</tr>';
    }
    /**
     * Response codes that indicate an error
     * @var array
     */
    public $error_codes = array(
    	    3,
            4,
            5,
            9,
            10
    );
    public function admin_init() {
        $this->register_settings();
        if (! isset($_SESSION) ) {
            session_start();
        }
        $admin_action_pages = array(
                'circle_tree_login_settings',
                'circle_tree_login_log',
        );
        
        if (isset($_REQUEST['action']) && isset($_REQUEST['page']) && in_array($_REQUEST['page'], $admin_action_pages)) {
            $redirect = 'index.php?page=circle_tree_login_log';
            if (! current_user_can('manage_options')) {
                $_SESSION['msg'] = 5;
                wp_redirect($redirect);
                return;
            }
            //Log Page Actions
            if ('circle_tree_login_log' == $_REQUEST['page']) {
                if (isset($_REQUEST['new']) && isset($_REQUEST['ip'])) {
                    //Skip ip based nonce validation for new (manually entered) IP's
                    $temp_ip = $_REQUEST['ip'];
                    unset($_REQUEST['ip']);
                }
                if (wp_verify_nonce($_REQUEST['nonce'], 'wp_login_lockdown'.(isset($_REQUEST['ip']) ? $_REQUEST['ip'] : ''))) {
                    //Reset IP
                    if (isset($_REQUEST['new'])) {
                        $_REQUEST['ip'] = $temp_ip;
                        unset($temp_ip);
                    }
                    $action_field = defined('DOING_AJAX') ? $_REQUEST['ajax_action'] : $_REQUEST['action'];
                    switch ($action_field) {
                    	case 'block':
                    	    if (filter_var($_REQUEST['ip'], FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE)) {
                    	        $message_code = $this->block_ip($_REQUEST['ip']) ?  1 : 9;
                    	    } else {
                    	        $message_code = 4;
                    	    }
                    	    break;
                    	case 'unblock':
                    	    $success = $this->unblock_ip($_REQUEST['ip']);
                    	    if ($success) {
                    	        $message_code = 2;
                    	    } else {
                    	        $message_code = 3;
                    	    }
                    	    break;
                    	case 'clear_log':
                    	    if (current_user_can('activate_plugins')) {
                    	        $this->clear_log();
                    	        $message_code = 6;
                    	    } else {
                    	        $message_code = 5;
                    	    }
                    	    break;
                    	case 'whitelist':
                    	    if (filter_var($_REQUEST['ip'], FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE)) {
                        	    $message_code = $this->whitelist_ip($_REQUEST['ip']) ? 7 : 9;
                    	    } else {
                    	        $message_code = 4;
                    	    }
                    	    break;
                    	case 'unwhitelist':
                    	    $success = $this->unwhitelist_ip($_REQUEST['ip']);
                    	    if ($success) {
                    	        $message_code = 8;
                    	    } else {
                    	        $message_code = 3;
                    	    }
                    	    break;
                    	default:
                    	   $message_code = 12;
                	   break;    
                    }
                    if (defined('DOING_AJAX')) {
                        $contents = '';
                        if (! in_array($message_code, $this->error_codes)) {
                            ob_start();
                            $this->log_page();
                            $contents = ob_get_contents();
                            ob_end_clean();
                        }
                        header('Content-Type: application/json');
                        echo json_encode(array('code' => $message_code, 'html' => $contents));
                    } else {
                        $_SESSION['msg'] = $message_code;
                        wp_redirect($redirect);
                    }
                    die;
                } else {
                    //Invalid NONCE
                    if (defined('DOING_AJAX')) {
                        echo 10;
                    } else {
                        $_SESSION['msg'] = 10;
                        wp_redirect($redirect);
                    }
                    die;
                }//End nonce check
            }//End log page check
            //Settings Page actions
            //Save Settings
            if (
            wp_verify_nonce($_REQUEST['nonce'], 'save_circle_tree_login_settings')
            &&
            $_REQUEST['action'] == 'save_circle_tree_secure_login_settings'
                    ) {
                $settings_to_save = array();
                foreach ( $this->settings as $setting ) {
                    $key = $setting['name'];
                    if (isset($_REQUEST['settings'][$key]) && ! empty($_REQUEST['settings'][$key])) {
                        //Save
                        $settings_to_save[$key] = $_REQUEST['settings'][$key];
                    }
                }
                $_SESSION['msg'] = array();
                if (count( $this->get_admin_conflicts() ) > 0 && isset($settings_to_save['blacklist_admin'])) {
                    unset($settings_to_save['blacklist_admin']);
                    $_SESSION['msg'][] = 3;
                }
                update_option(self::SETTINGS_KEY, $settings_to_save);
                $_SESSION['msg'][] = 1;
                wp_redirect('options-general.php?page=circle_tree_login_settings');
            }
            //Reset Settings
            if (
            wp_verify_nonce($_REQUEST['nonce'], 'reset_circle_tree_login_settings')
            &&
            $_REQUEST['action'] == 'reset_circle_tree_secure_login_settings'
                    ) {
                $defaults = array();
                delete_option(self::SETTINGS_KEY);
                 
                foreach ( $this->settings as $setting) {
                    if (isset($setting['default'])) {
                        $defaults[$setting['name']] = $setting['default'];
                    }
                }
                $_SESSION['msg'] = array();
                if (count($this->get_admin_conflicts()) > 0) {
                    unset($defaults['blacklist_admin']);
                    $_SESSION['msg'][] = 3;
                }
                update_option(self::SETTINGS_KEY, $defaults);
                $_SESSION['msg'][] = 2;
                wp_redirect('options-general.php?page=circle_tree_login_settings');
            }
        }
    }
    public function get_admin_conflicts () {
        //Check for existing admin usernames
        $admin = array();
        foreach ( $this->admin_usernames as $username) {
            if (is_object($user = get_user_by('login', $username))) {
                $admin[] = $user->get('user_login');
            }
        }
        return $admin;
    }
    /**
     * Get setting value
     * @param string $name
     * @return mixed value on success, false on failure / not set
     */
    public function get_setting($name)
    {
        $options = get_option(self::SETTINGS_KEY);
        if (isset($options[$name])) {
            $value = $options[$name];
            if ($value == 'on') {
                return true;
            }
            return $value;
        }
        return false;
    }
    private function register_settings() {
        $this->settings = array(
                array(
                        'name'    =>    'admin_emails', //HTML safe option key
                        'type'    =>    'checkbox',
                        'label'   =>    'Send an email notification on failed login',
                        'default' =>     false
                ),
                array(
                        'name'    =>    'admin_email',
                        'type'    =>    'text',
                        'size'    =>    40,
                        'label'   =>    'Send Email Notifications to',
                        'default' =>    get_bloginfo('admin_email'),
                ),
                array(
                        'name'    =>    'blacklist_admin', //HTML safe option key
                        'type'    =>    'checkbox',
                        'label'   =>    'Blacklist IPs using the Admin User Account',
                        'tooltip' => 	'Automatically block IP addresses that attempt to login ' . 
                        //Make sure you concat long strings, or tooltip will break!
                        'using ('.rtrim(implode(', ', $this->get_blacklisted_usernames()), ', ').') usernames',
                        'default' =>     true
                ),
                array(
                        'name'    =>     'blacklisted_admin_usernames',
                        'type'    =>    'text',
                        'size'    =>    80,
                        'label'   =>    'Additional Blacklisted Usernames (CSV)',
                        'tooltip' =>    'Comma Separated Usernames to automatically blacklist', 
                ),
                array(
                        'name'    =>     'login_lockdown_attempts',
                        'type'    =>    'select',
                        'tooltip' =>    'Number of attempts allowed before displaying a CAPTCHA',
                        'label'   =>    'Allow Failed Login',
                        'default' =>    3,
                        'options' =>
                        array(
                                2    =>    2,
                                3    =>   '3 (default)',
                                4    =>    4,
                                5    =>    5,
                                6    =>    6,
                                7	 =>    7,
                                8    =>    8,
                                -1   =>    'Disabled (NOT recommended)',
                        ),
                ),
                array(
                        'name'    =>    'log_level',
                        'type'    =>    'select',
                        'tooltip' =>    'How verbose to make the Dashboard Security Log',
                        'label'   =>    'Log Level',
                        'default' =>    '2',
                        'options' =>
                        array(
                                1    =>    'Off',
                                2    =>   'Log Failures Only (default)',
                                3    =>    'Verbose',
                        ),
                ),
        );
    }
    public function  get_settings()
    {
        return $this->settings;
    }
    /**
     * Gets all failed logins across IP's 
     * @return number
     */
    private function get_total_failed_logins ()
    {
        $total = 0;
        foreach ( $this->get_log() as $log_item ) {
            //Count log entries that have an associated IP
            //IE - a failed login, not a general log message
            if (isset($log_item['ip'])) {
                $total++;
            }
        }
        return $total;
    }
    public function admin_menu () {
        $total = $this->get_total_failed_logins();
        $this->log_page_id = add_dashboard_page(
                'Secure Login Log',
                'Security Log ' . ($total > 0 ? '
	            <span class="update-plugins" title="'.$total.' Failed Logins">
	                <span class="update-count">'.$total.'</span>
	            </span> ' : '' ),
                'edit_others_posts',
                'circle_tree_login_log',
                array(
                        $this, 'log_page'
                )
        );

        $this->settings_page_id = add_options_page(
                'Secure Login Settings',
                'Security',
                'manage_options',
                'circle_tree_login_settings',
                array(
                        $this, 'settings_page'
                )
        );
        add_action("load-{$this->settings_page_id}", array($this, 'load_settings_page'));
        add_action("admin_print_scripts-{$this->settings_page_id}", array(&$this, 'admin_scripts'));
        add_action("admin_print_scripts-{$this->log_page_id}", array(&$this, 'admin_scripts'));
        add_action("admin_print_scripts-{$this->log_page_id}", array(&$this, 'admin_scripts_log'));
    }
    public function load_settings_page ()
    {
        $screen = get_current_screen();
        $log_link = admin_url('index.php?page=circle_tree_login_log');
        $screen->add_help_tab(array(
                'id' => 'by_ct_settings_help',
                'title'=>'Login Settings',
                'content' => '<p><b>Configure security settings</b>.<br/><br/>' .
                    'Enable automatic emails when there is a series of failed logins.<br/>' .
                    'You can also specify that any attempts using <code>' . 
                        rtrim(implode(', ', $this->admin_usernames), ', ') . '</code>' .
                    ' are automatically blocked by their IP address. <br/> You can also change the number of allowed login attempts' .
                    ' before displaying a CAPTCHA. ' .   
                    '</p>' . 
                '<p>'.
                     'You can configure the log level to change how verbose the '.
                    '<a href="'.$log_link.'">Security Log</a> is.' . 
                '</p>'
        ));
        $screen->set_help_sidebar('<p><a href="'.$log_link.'">Security Log</a></p>');
    }
    public function admin_scripts () {
        wp_enqueue_script('jquery');
        wp_register_style('byct_css', wp_by_ct::get_url() . '/css/circletree-login'.(WP_DEBUG ? '' : '.min').'.css');
        wp_enqueue_style('byct_css');
        wp_register_script('byct_js', wp_by_ct::get_url() . '/js/jquery.by_ct.js');
        wp_enqueue_script('byct_js');
    }
    public function admin_scripts_log ()
    {
        wp_register_script('datatable', wp_by_ct::get_url() . '/js/jquery.dataTables'.(WP_DEBUG ? '' : '.min').'.js');
        wp_enqueue_script('datatable');
        
        wp_register_style('datatable', wp_by_ct::get_url() . '/css/jquery.dataTables.css', array('byct_css'));
        wp_enqueue_style('datatable');

    }
    public function log_page()
    {
        require_once wp_by_ct::get_path() . 'includes' . DS . 'pages'. DS. 'log.php';
    }
    public function  settings_page ()
    {
        require_once wp_by_ct::get_path() . 'includes' . DS . 'pages'. DS. 'settings.php';
    }
    private function display_capcha_form() {
        ob_start();
        require_once wp_by_ct::get_path() . 'includes' . DS . 'pages'. DS. 'captcha.php';
        $str = ob_get_contents();
        ob_end_clean();
        wp_die($str,'ERROR | TOO MANY LOGIN ATTEMPTS', array('response'=>503));
    }
	public function login_form_secure () { ?>
		<h2 class="byct_lockdown" >
			<img style="vertical-align:middle;" src="<?php echo wp_by_ct::get_url(); ?>/lock.png" alt="Lock Icon" />
			Secure Login 
			<a target="_blank" style="text-decoration:none;color:#000" href="http://mycircletree.com">
				by Circle Tree
			</a>
		</h2>
		<div class="byct_lockdown" id="ip_logged_notice">
			<div class="two_cols">
				<span class="ip_logged">IP Address Logged <?php echo self::$remote_ip ?></span>
				<span class="notice">
				You will be locked out and an administrator will be notified after 
				<?php echo LOGIN_LOCKDOWN_ATTEMPTS?> failed login 
				<?php echo _n('attempt', 'attempts', LOGIN_LOCKDOWN_ATTEMPTS)?></span>
			</div>
		</div>
	<?php 
	}
	public function login_failed ($username) {
        if ( $this->get_setting('blacklist_admin') ) {
            if (in_array(trim(strtolower($username)), $this->get_blacklisted_usernames())) {
                $this->block_ip(self::$remote_ip);
                $this->log('Blacklisted Username', $username, self::$remote_ip);
                wp_redirect(get_bloginfo('url'));
                die;
            }
        }
		$this->log('Failed Login', $username, self::$remote_ip);
		$this->set_failed_login();
		status_header(401);
	}
	public function login_error_message ($error) {
        if ( $this->is_IP_whitelisted(self::$remote_ip)) {
            return '<h2 class="login_error" >' . $error . '</h2>';
        }
        if (isset($_GET['action']) && 'lostpassword' == $_GET['action']) {
            //WordPress Doesn't call the login failed action on invalid username / password
            if (strstr(strtolower($error), 'invalid')) {
                //We check manually for invalid / username / email
                $this->set_failed_login();
                $this->log('Password recovery failure', $_POST['user_login'], self::$remote_ip);
            } 
        }
		$message = '<h2 class="login_error" >'.$error;
		//Make sure this is an error that triggers the wp_login_failed filter
		if (! strstr($error, 'empty') ) {
			$message .= $this->get_lockdown_message().'<br/>';
		}
		return $message . '</h2>';
	}
	public function login_success () {
		$this->reset_failed_logins();
	}
	public function login_lockdown () {
		require_once wp_by_ct::get_path() . 'includes'.DS.'recaptchalib.php';
		if (! LOGIN_LOCKDOWN) {
            return;
        }
        //Check whitelist
		if ( $this->is_IP_whitelisted()) {
            return;
        }
		//Redirect blocked IP'S to homepage
		if ( $this->is_ip_blocked() ) {
			wp_redirect(get_bloginfo('url'));
			die;
		}
		//Validate captcha
		if ($this->valid_captcha()) {
			$this->reset_failed_logins();
			wp_redirect('wp-login.php');
			die;			
		}
		
		//Check number of resets (full CAPTCHA cycles)
		if ( $this->get_resets() >= LOGIN_LOCKDOWN_RESETS) {
			//Flood protection, max 1 email every 5 min per ip
			$flood_key = 'byct_lockdown_emails'.self::$remote_ip;
			if (
                ! get_transient($flood_key)  
                    && 
                self::get_setting('admin_emails')
            ) {
				$this->send_email();
				$this->log('Sending email');
				set_transient( $flood_key, true, 300 );
			} else {
                if (self::get_setting('admin_emails')) {
                    $message =  'Not Sending Mail, flood protection';
                } else {
                    $message = 'Admin Emails Disabled';
                }
                $this->log($message);
            } 
		}
		//Check if number of failed logins
		if ( ($this->get_failed_logins() + 1 ) >= $this->get_total_failures_allowed() ) {
			$this->display_capcha_form();
        }
	}
	private function send_email() {
        ob_start();
		require_once wp_by_ct::get_path() . 'includes' . DS . 'email.php';
        $message = ob_get_contents();
        ob_end_clean();
        $subject = 'Failed Logins on ' . get_bloginfo('name');
        $email = self::get_setting('admin_email');
        if (empty($email)) {
            $email = get_option('admin_email');
        }
		return wp_mail($email, $subject, $message);
	}
	/**
	 * Block IP
	 * @param string $ip
	 * @uses options
	 */
	private function block_ip ($ip) {
		$current = $this->get_blocked_ips();
		if ( $this->is_IP_whitelisted($ip)) {
            return false;
        }
		if (false !== $current && in_array($ip, $current)) {
			return false;
		} else {
			$current[] = $ip;
		}
		update_option(self::BLOCKED_IP_NAME, $current);
		return true;
	}
	/**
	 * @param string $ip
	 * @return boolean true if found and unblocked
	 * @uses options
	 */
	private function unblock_ip ($ip) {
		$current = $this->get_blocked_ips();
		if (in_array($ip, $current)) {
			$key = array_search($ip, $current);
			unset($current[ $key ]);
			update_option(self::BLOCKED_IP_NAME, $current);
			return true;
		} else {
			return false;
		}
	}
	/**
	 * Check if an IP is whitelisted
	 * @param string $ip
	 * @return boolean
	 */
	public function  is_IP_whitelisted ($ip = null)
	{
	    if (is_null($ip)) {
            $ip = self::$remote_ip;
        }
	    $ips = $this->get_whitelisted_ips();
	    if (empty($ips)) {
	        return false;
        }
	    return in_array($ip, $ips);
	}
	/**
	 * Whitelist an IP
	 * @param string $ip
	 * @return bool true on success, false if already whitelisted
	 */
	private function whitelist_ip ($ip) {
        $current = $this->get_whitelisted_ips();
        if (false !== $current && in_array($ip, $current)) {
            return false;
        }
        if (in_array($ip, $this->get_blocked_ips())) {
            return false;
        }
        if (is_array($current)) {
            $current[] = $ip;
        } else {
            $current = array($ip);
        }
        update_option(self::WHITELISTED_IP_NAME, $current);
        return true;
    }
    /**
     * Removes a whitelisted IP
     * @param string $ip
     * @return boolean true on success, false on IP not whitelisted
     */
    private function unwhitelist_ip ($ip)
    {
        $current = $this->get_whitelisted_ips();
        if (in_array($ip, $current)) {
            unset($current[array_search($ip, $current)]);
            update_option(self::WHITELISTED_IP_NAME, $current);
            return true;
        } else {
            return false;
        }
    }
    private function get_whitelisted_ips() {
        return get_option(self::WHITELISTED_IP_NAME, array());
    }
	private function get_blocked_ips () {
		return get_option(self::BLOCKED_IP_NAME, array());
	}
	private function is_ip_blocked () {
		$ips = $this->get_blocked_ips();
		if (FALSE == $ips) return false; //No Ips Blocked
		return in_array(self::$remote_ip, $ips);
	}
	private function valid_captcha() {
		if (! isset($_POST["recaptcha_challenge_field"]) || ! isset($_POST["recaptcha_response_field"])) {
            return false;
        }
		$resp = recaptcha_check_answer(
                $this->recaptcha_keys['private'],
				$_SERVER["REMOTE_ADDR"],
				$_POST["recaptcha_challenge_field"],
				$_POST["recaptcha_response_field"]
            );
		if (! $resp->is_valid ) {
            $this->log('Failed CAPTCHA', '', self::$remote_ip);
			sleep(2);
			$this->message = $resp->error;
			return false;
		} else {
			return true;
		}
	}
	private function get_lockdown_message() {
		return 'You have '. $this->get_remaining_attempts() . ' login 
	         '._n('attempt', 'attempts', $this->get_remaining_attempts()).' remaining';
	}
	private function get_remaining_attempts() {
		return $this->get_total_failures_allowed() - $this->get_failed_logins();
	}
	/**
	 * Gets failed logins for this IP
	 * @return number failed logins
	 */
	private function get_failed_logins() {
		$logins = $this->get_transient();
		if (! $logins || ! isset($logins[ self::$remote_ip ]))
			return 0;
		else return $logins[ self::$remote_ip ];
	}
	/**
	 * gets total number of CAPTCHAs entered
	 * @return int $resets number of resets for current IP
	 */
	private function get_resets() {
		$logins = $this->get_transient();
		if (! $logins || ! isset($logins['reset'][ self::$remote_ip ]))
			return 0;
		else return $logins['reset'][ self::$remote_ip ];
	}
	private function get_transient() {
		return get_transient(self::TRANSIENT_NAME);
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
	/**
	 * Sets a failed login for the IP in the the transient array
	 */
	private function set_failed_login() {
		$current = $this->get_transient();
		if (isset($current[ self::$remote_ip ])) {
			$current[ self::$remote_ip ] += 1;
		} else {
			$current[ self::$remote_ip ] = 1;
		}
		$this->save_transient($current);
	}
	/**
	 * Adds to log transient
	 * @param string $msg
	 * @param string $username (optional) username tried
	 * @param string $ip (optional) ip address of remote request, use self::$remote_ip 
	 */
	private function log ($msg, $username = null, $ip = null) {
		$current = self::get_log();
        if (
            //Logging off
            $this->get_setting('log_level') == 1
                ||
            //Not a failure && log level is not verbose
            ( 
                $this->get_setting('log_level') == 2
                &&
                is_null($ip)
            ) 
		) {
            return;
        }
        
 		$message = array(
				'msg' => $msg,
				'username' => $username,
				'ip' => $ip,
				'time' => current_time('timestamp')
			);
		$current[] = $message;
		set_transient(self::TRANSIENT_NAME_LOG, $current, DAY_IN_SECONDS);
	}
 	/**
     * Gets human readable relative time
     * @param int $timestamp
     * @param number $granularity 
     * @param string $format fallback
     * @return string
     */
    public static function time_ago ($timestamp, $granularity=1, $format='Y-m-d H:i:s'){
        $difference = current_time('timestamp') - $timestamp;
        if($difference < 5) return 'just now';
        elseif($difference < (31556926 * 1 )) { //1 years
            $periods = array(
                    'week' => 604800,
                    'day' => 86400,
                    'hour' => 3600,
                    'minute' => 60,
                    'second' => 1
            );
            $output = '';
            foreach($periods as $label => $value){
                if($difference >= $value){
                    $time = round($difference / $value);
                    $difference %= $value;
                    $output .= ($output ? ' ' : '').$time.' ';
                    $output .= (($time > 1 ) ? $label.'s' : $label);
                    $granularity--;
                }
                if($granularity == 0) break;
            }
            return $output . ' ago';
        }
        else return date($format, $timestamp);
    }
	/**
	 * 
	 */
	private function clear_log ()
	{
		delete_transient(self::TRANSIENT_NAME_LOG);
	}
	/**
	 * 
	 * @return Array log with oldest first
	 */
	private function get_log() {
        $log = get_transient(self::TRANSIENT_NAME_LOG);
        //Remove deprecated log string
        if (! is_array(get_transient(self::TRANSIENT_NAME_LOG))) {
            delete_transient(self::TRANSIENT_NAME_LOG);
            return array();
        }
		return $log;
	}
	private function save_transient($value) {
		set_transient(self::TRANSIENT_NAME, $value, self::TRANSIENT_TIMEOUT);
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
	/**
	 * Used to check if the current request is being done via XHR / AJAX
	 * @return boolean
	 */
	public static function is_ajax()
	{
		return  isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH']  == 'XMLHttpRequest';
	}
}