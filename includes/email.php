There have been a number of failed login attempts on your website: <?php echo get_bloginfo('name'); ?>

If you have forgotten your password, please go to:
<?php echo get_bloginfo('wpurl').'/wp-login.php?action=lostpassword'?>

If this is unauthorized activity, you may block the remote IP by going
here: 
<?php echo get_bloginfo('wpurl').'/wp-admin/options-general.php?page=circle_tree_login_settings&action=block&ip='
. self::$remote_ip; ?>