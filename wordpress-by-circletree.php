<?php
/*
Plugin Name: WordPress by Circle Tree
Plugin URI: http://mycircletree.com/
Description: Secure Login Screen for Circle Tree powered websites
Author: Circle Tree, LLC
Version: 1.9.1
Author URI: http://mycircletree.com/
*/ 
//Start a session for login tracking if not already set
if (!isset($_SESSION))
	session_start();

//Remove WordPress/version # from Head
remove_action('wp_head', 'wp_generator');

//Remove WordPress Admin Bar Pointer
add_filter( 'show_wp_pointer_admin_bar', '__return_false' );

function byct_admin_bar () {
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

	$wp_admin_bar->add_menu(array(
			'id'=>'ct-account-logo',
			'parent'=>'wp-logo',
			'title'=>"My Circle Tree Account",
			'href'=>'https://mycircletree.com/client-area/',
			'meta'=>array('target'=>'_blank')
	));
	$wp_admin_bar->add_menu(array(
			'id'=>'ct-account-user',
			'parent'=>'user-actions',
			'title'=>"My Circle Tree Account",
			'href'=>'https://mycircletree.com/client-area/',
			'meta'=>array('target'=>'_blank')
	));
}
add_action('admin_bar_menu', 'byct_admin_bar',50);
/**
 *  returns plugin path with NO trailing slash
 *  TODO add mu support
 */
function byct_get_plugin_path () {
	return plugins_url('',__FILE__); 
}
function byct_stylesheet () {
	// full plugin path
	$pluginUrl = byct_get_plugin_path().'/circletree-login.css';
	return '<link rel="stylesheet" type="text/css" href="' . $pluginUrl . '" />';
}
function byct_theme_css () {
	$head = "";
	$head .= byct_stylesheet().PHP_EOL;
	//if not set, do not return CSS, so default Circle Tree logo is applied from the CSS
	$logo = (trim(get_option('byct_header')) == "" ? 'background-image: url('.byct_get_plugin_path().'/header.png);' : 'background-image: url('.byct_get_plugin_path().'/'.get_option('byct_header').');');
	$head .= '<style>
	BODY,HTML {
	background-color: '.get_option('byct_background_color').' !important;
	}
	#login h1 a {
		'.($logo ? $logo : '').'
		width: '.get_option('byct_header_width').'px;
		height: '.get_option('byct_header_height').'px;
	}
	#login {
		width: '.get_option('byct_header_width').'px;
	}
	</style>';
	return $head;
}
// display custom login styles
function byct_custom_login() {
if (!isset($_SESSION['byct_failed_logins'])) {
	
	$_SESSION['byct_failed_logins'] = 0;
}
	echo byct_theme_css();
}
add_action('login_head', 'byct_custom_login');
function byct_custom_admin () {
	echo byct_stylesheet();
}
add_action('admin_head', 'byct_custom_admin');



function byct_admin_footer () {
	echo '<a href="http://mycircletree.com/client-area/knowledgebase.php?action=displaycat&catid=2" target="_blank">WordPress Video Tutorials</a>';
	echo ' | <a href="https://mycircletree.com/client-area/submitticket.php" target="_blank">Contact Circle Tree Support</a>';
	echo ' | <a target="_blank" style="text-decoration:none;font-size:10px;color:#666" href="http://mycircletree.com">Site design &amp; hosting by Circle Tree <img style="vertical-align:middle;opacity:0.3;" width="30" height="30" alt="Website by Circle Tree" src="https://s3.amazonaws.com/myct2/footer-logo-30px.png"/></a>';
	echo byct_adminbar_logo_css();
}
function byct_adminbar_logo_css () {
	return '<style>
		#wp-admin-bar-wp-logo > .ab-item .ab-icon, 	#wpadminbar.nojs #wp-admin-bar-wp-logo:hover > .ab-item .ab-icon, #wpadminbar #wp-admin-bar-wp-logo.hover > .ab-item .ab-icon {
				background-image: url("https://s3.amazonaws.com/myct2/footer-logo-16px.png");
				background-position:center center;
			}
		</style>';
}
function byct_adminbar_logo_frontend () {
	echo byct_adminbar_logo_css();
}
add_action('wp_footer', 'byct_adminbar_logo_frontend');
add_action('in_admin_footer', 'byct_admin_footer');
function byct_remove_admin_footer () {
	return false;
}
add_filter('admin_footer_text', 'byct_remove_admin_footer');
function byct_login_poweredby($title) {
    return 'Go to ' . get_option('blogname');
}
add_filter('login_headertitle', 'byct_login_poweredby');
function byct_login_url($url) {
	return get_bloginfo('url');
}
add_filter('login_headerurl', 'byct_login_url');

function byct_menu () {
	$page = add_options_page('Custom WordPress Website by Circle Tree','Circle Tree Login','manage_options','circle-tree-login','byct_page');
 	add_action('admin_print_styles-' . $page, 'byct_scripts');
}
function byct_scripts () {
	wp_enqueue_script('farbtastic');
	wp_enqueue_style('farbtastic');
}
add_action('init','byct_save_options');
function byct_save_options () {
	if (isset($_POST)&&isset($_POST['post'])) {
		if (!wp_verify_nonce($_REQUEST['byct_nonce'],'save_byct')) return;
		update_option('byct_header',$_POST['filename']);
		update_option('byct_email',$_POST['email']);
		update_option('byct_email_address',$_POST['email_address']);
		update_option('byct_lockdown',$_POST['lockdown']);
		update_option('byct_lockdown_count',$_POST['lockdown_count']);
		update_option('byct_header_width',$_POST['header_width']);
		update_option('byct_header_height',$_POST['header_height']);
		update_option('byct_background_color',$_POST['color']);
		header("Location: ".$_SERVER['REQUEST_URI'].'&updated');
	}
}
function byct_page () {
	if (!current_user_can('manage_options'))  {
		wp_die( __('You do not have sufficient permissions to access this page.') );
	}
	$header_img = 'byct_header';
	$header = get_option($header_img);
	$email_address = get_option('byct_email_address');
	$email_enabled = (get_option('byct_email')==true ? ' checked' : '');
	$lockdown_enabled = (get_option('byct_lockdown')==true ? ' checked' : '');
	echo '
	<div class="wrap">
	<h2>Circle Tree Login Page Options</h2>
		<form method="POST" action="">';
	if ( function_exists('wp_nonce_field') )
		wp_nonce_field('save_byct','byct_nonce');
	echo '
		<input type="hidden" name="post" value="true" />
	<table width="100%" border="0" cellspacing="0" cellpadding="0">
		<tr>
			<th width="300"><label for="filename">Header Filename for login screen (in plugin directory)</label></th>
			<td><input name="filename" value="'.$header.'" type="text" size="40"/></td>
		</tr>
		<tr>
			<th>Header Size</th>
			<td><input name="header_width" value="'.get_option('byct_header_width').'" type="text" size="10"/>px * <input name="header_height" value="'.get_option('byct_header_height').'" type="text" size="10"/>px</td>
		</tr>
		<tr>
			<th>Email on Failed Login</th>
			<td>
		<input type="checkbox" name="email"'.$email_enabled.' value="true"/>
		<input type="text" size="40" name="email_address" value="'.$email_address.'"/></td>
		</tr>
		<tr>
			<th>Lockdown on number of failed attempts per session</th>
			<td>
				<input type="checkbox" name="lockdown"'.$lockdown_enabled.' value="true"/>
				<input type="text" size="4" name="lockdown_count" value="'.get_option('byct_lockdown_count').'"/></td>
			</td>
		</tr>
		<tr>
			<th>Login Page Background Color</th>
			<td>
				<input type="text" id="color" name="color" value="'.(get_option('byct_background_color') == "" ? '#FFFFFF' : get_option('byct_background_color')).'" />
				<div id="colorpicker"></div>
			</td>
		</tr>
	</table>
	
		<input type="submit" value="Save" />
		</form>
		<script type="text/javascript">
		
		jQuery(document).ready(function($){
	 $("#colorpicker").farbtastic("#color");
})
		</script>
	</div>
	';
	
}

function byct_failed_login () {
	$_SESSION['byct_failed_logins'] += 1; 
	if (get_option('byct_email')) {
		$message = 'This is a message from your website: '.$_SERVER['PHP_SELF'].PHP_EOL.PHP_EOL;
		$message .= 'Someone used the following to try to log into your website'.PHP_EOL.' Username:'.$_POST['log'].PHP_EOL.' Password: '.$_POST['pwd'].PHP_EOL.PHP_EOL;
		$message .= 'From IP Address: <a target="_blank" href="http://www.ipchecking.com/?ip='.$_SERVER['REMOTE_ADDR'].'&check=Lookup">'.$_SERVER['REMOTE_ADDR'].'</a>';
		$subject = "Someone is trying to log into your website.";
		wp_mail(get_option('byct_email_address'), $subject, $message);
	}
}
add_action('wp_login_failed','byct_failed_login');

add_action('admin_menu','byct_menu');
function byct_custom_form () {
	echo  '<h2 style="text-align:center"><img src="'.byct_get_plugin_path().'/lock.gif" height="" width="" alt="Lock Icon" /> Secure Login <a target="_blank" style="text-decoration:none;color:#000" href="http://mycircletree.com">by Circle Tree <img style="vertical-align:middle;opacity:0.3;" width="30" height="30" alt="Website by Circle Tree" src="https://s3.amazonaws.com/myct2/footer-logo-30px.png"/></a></h2>';
	echo  '<h3 style="text-align:center">IP Logged '.$_SERVER['REMOTE_ADDR'].'</h3>';
}
add_action('login_form', 'byct_custom_form');

function byct_login_errors ($error) {
	return '<h1>WARNING: Your IP Address has been logged, and a '.get_option('blogname').' administrator has been notified of this failed login attempt</h1>';
}
add_filter('login_errors', 'byct_login_errors');
function byct_login_success () {
	unset($_SESSION['byct_failed_logins']);
}
add_action('wp_login', 'byct_login_success');
function byct_login_lockdown () {
	$numbers1 = array (
					'4'=>'four',
					'5'=>'five',
					'6'=>'six',
					'7'=>'seven',
					'8'=>'eight',
					'9'=>'nine',
					'10'=>'ten',
					'11'=>'eleven'
					);
	$numbers2 = array(
					'3'=>'three',
					'2'=>'two',
					'4'=>'four',
					'5'=>'five'
				);
	$operators = array('*','+','-');
	$operator_labels = array('times','plus','minus'	);
	if (get_option('byct_lockdown')) {
		if (isset($_POST['captcha_answer']) && ($_POST['captcha_answer'] == $_SESSION['byct_captcha'])) {
			unset($_SESSION['byct_failed_logins']);
			return;
		} else if ((int)$_SESSION['byct_failed_logins'] > (int)get_option('byct_lockdown_count')) {
			//FIXME use wp_die instead of this
			$str = <<<EOL
<link rel="stylesheet" href="wp-admin/css/login.css" />
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.6.2/jquery.min.js" language="javascript"></script>
<script type="text/javascript">
jQuery(document).ready(function($){
	$("form").submit( function  () {
		setTimeout( function  () {
			$("input").attr('disabled',true)
			$("input[type=text]").val("Please wait..."); 
		},100)
	})
})
</script>
			<style>#login_error{text-color:red;}</style>
EOL;
$str .= byct_theme_css();
$str .= <<<EOL
<div id="login">
<form method="POST" action="">
EOL;
			$str .= '<h1>Too many login attempts</h1>';
			if (isset($_POST['captcha_answer']) && ($_POST['captcha_answer'] !== $_SESSION['byct_captcha'])) {
				sleep(5);
				$str .= '<div id="login_error"><h1>Incorrect, please try again</h1></div>';
			}
			$str .= '<p>Please verify your humanity (this is to protect against brute force attacks)</p>';
			$operator = mt_rand(0, 2);
			$number1 = mt_rand(4,11);
			$number2 = mt_rand(3, 5);
			switch ($operators[$operator]) {
				case '*':
					$_SESSION['byct_captcha'] = $number1*$number2;
					break;
				case '+':
					$_SESSION['byct_captcha'] = $number1+$number2;
					break;
				case '-':
					$_SESSION['byct_captcha'] = $number1-$number2;
					break;
			}
			$str .= '<br/><br/><h2>'.$numbers1[$number1].' '.$operator_labels[$operator].' '.$numbers2[$number2].' equals </h2>
				<input type="text" name="captcha_answer" size="10" /><input type="submit" class="button" name="" /></form>';
			$str .= <<<EOL
			</div>
EOL;
			wp_die($str,'RESTRICTED');
		}
	}
}
add_action('wp_authenticate','byct_login_lockdown');

function byct_support_widget() {
	echo ' <script type="text/javascript">
jQuery(document).ready(function($){
var $news = $("#byct_news_content"),
	$refresh = $("#refreshCTNews"),
	loadingString = "<li><h3>Loading Circle Tree News</h3></li>";
 	
	$refresh.bind("click", function  () {
		$news.append(loadingString);
		$.ajax({
		    url: document.location.protocol + "//ajax.googleapis.com/ajax/services/feed/load?v=1.0&num=4&callback=?&q=" + encodeURIComponent("http://mycircletree.com/feed/"),
		    dataType: "json",
		    success: function(data) {
		      	$news.empty();
		      	var str = "";
				$.each(data.responseData.feed.entries, function (k,entry) {
					str += "<li><h4><a target=\"_blank\" href=\""+entry.link+"\" title=\"View "+entry.title+" on our Website\">"+entry.title+"</a></h4><p>"+entry.contentSnippet+"<a style=\"float:right;\" target=\"_blank\" href=\""+entry.link+"\">Read more...</a></p></li>";
				});
				str +=	"<h3><a href=\"http://mycircletree.com/\" target=\"_blank\">Read more on the Circle Tree Blog</a></h3>";
				$(str).appendTo($news); 
		    }
		});
	  return false;
	}).trigger("click");
})
	</script>';
	echo '<ul id="byct_news_content"></ul><a href="#" id="refreshCTNews" class="button">Refresh</a>';
} 
function byct_dashboard_widgets() {
	global $wp_meta_boxes;
	wp_add_dashboard_widget('byct_news', '<img style="vertical-align:middle;opacity:0.3;" width="30" height="30" alt="Website by Circle Tree" src="https://s3.amazonaws.com/myct2/footer-logo-30px.png"/> Circle Tree News', 'byct_support_widget');	
	remove_meta_box( 'dashboard_secondary', 'dashboard', 'side' );
	remove_meta_box( 'dashboard_primary', 'dashboard', 'side' );
	remove_meta_box( 'dashboard_plugins', 'dashboard', 'normal' );
	remove_meta_box( 'dashboard_incoming_links', 'dashboard', 'normal' );
	remove_meta_box( 'w3tc_latest', 'dashboard', 'normal' );
	remove_meta_box( 'w3tc_pagespeed', 'dashboard', 'normal' );
} 

// Hoook into the 'wp_dashboard_setup' action to register our function

add_action('wp_dashboard_setup', 'byct_dashboard_widgets' );
?>