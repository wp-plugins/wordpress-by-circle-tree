<?php
final class wp_by_ct {
    const PLUGIN_DIR_NAME = 'wordpress-by-circle-tree';
    /**
     * @var string css to apply custom icon over the WordPress one
     */
    const CIRCLETREE_ADMINBAR_ICON_STYLE = '<style>
		#wp-admin-bar-wp-logo > .ab-item .ab-icon,
        #wpadminbar>#wp-toolbar>#wp-admin-bar-root-default #wp-admin-bar-wp-logo .ab-icon,
		#wpadminbar.nojs #wp-admin-bar-wp-logo:hover > .ab-item .ab-icon,
		#wpadminbar #wp-admin-bar-wp-logo.hover > .ab-item .ab-icon {
				background-image: url("https://myct2.s3.amazonaws.com/footer-logo2-16px.png") !important;
                background-repeat: no-repeat;
				background-size: inherit !important;
                font: none;
				background-position:center center;
			}
            #wpadminbar #wp-admin-bar-wp-logo>.ab-item .ab-icon:before {
                content: none !important;
            }
	        @media screen and (min-resolution: 120dpi),
            (-webkit-min-device-pixel-ratio: 1.5),
            (min--moz-device-pixel-ratio: 1.5),
            (-o-min-device-pixel-ratio: 15/10),
            (min-device-pixel-ratio: 1.5),
            (min-resolution: 1.5dppx) {
	            #wp-admin-bar-wp-logo > .ab-item .ab-icon,
		        #wpadminbar.nojs #wp-admin-bar-wp-logo:hover > .ab-item .ab-icon,
                #wpadminbar>#wp-toolbar>#wp-admin-bar-root-default #wp-admin-bar-wp-logo:hover .ab-icon,
                #wpadminbar>#wp-toolbar>#wp-admin-bar-root-default #wp-admin-bar-wp-logo .ab-icon,
		        #wpadminbar #wp-admin-bar-wp-logo.hover > .ab-item .ab-icon {
	                background-image: url("https://myct2.s3.amazonaws.com/footer-logo2-32px.png") !important;
	                background-size: 16px 16px !important;
	            }
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
        add_action('admin_head', array($this, 'admin_bar_icon'), 20);
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
        echo '<link rel="stylesheet" type="text/css" href="' . wp_by_ct::get_url() . 'css/circletree-login.css" />';
    }
    /**
     * get the url for the plugin directory with a trailing slash
     */
    public static function get_url() {
        if (is_null(self::$plugin_url)) {
            if (is_multisite() && file_exists(WPMU_PLUGIN_URL.'/'.wp_by_ct::PLUGIN_DIR_NAME.'/'))
                self::$plugin_url = WPMU_PLUGIN_URL.'/'.wp_by_ct::PLUGIN_DIR_NAME.'/';
            else
                self::$plugin_url = WP_PLUGIN_URL.'/'.wp_by_ct::PLUGIN_DIR_NAME.'/';
        }
        return self::$plugin_url;
    }
    /**
     * Gets path to this plugins directory, trailing slashed
     * @return string /wp-content/plugins/wordpress-by-circletree/
     */
    public static function  get_path ()
    {
        return WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . wp_by_ct::PLUGIN_DIR_NAME . DIRECTORY_SEPARATOR;
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
        $wp_admin_bar->add_menu(array(
                'id'=>'gravatar',
                'parent'=>'user-actions',
                'title'=>"Customize Gravatar",
                'href'=>'http://gravatar.com',
                'meta'=>array('target'=>'_blank')
        ));
    }
    public function login_header_title($title) {
        return 'Go to ' . get_option('blogname');
    }
    public function login_header_url($url) {
        return get_bloginfo('url');
    }
    public function tweak_dashboard () {
        wp_add_dashboard_widget(
            'byct_news',
            '<img style="vertical-align:middle;" width="32" height="32" 
                    alt="Website by Circle Tree" src="https://s3.amazonaws.com/myct2/footer-logo2-32px.png"/>
                    Circle Tree<sup>&reg;</sup> News',
            array(
                $this, 'news_widget_content')
            );
        remove_meta_box( 'dashboard_secondary', 'dashboard', 'side' );
        remove_meta_box( 'dashboard_primary', 'dashboard', 'side' );
        remove_meta_box( 'dashboard_plugins', 'dashboard', 'normal' );
        remove_meta_box( 'dashboard_incoming_links', 'dashboard', 'normal' );
        remove_meta_box( 'w3tc_latest', 'dashboard', 'normal' );
        remove_meta_box( 'w3tc_pagespeed', 'dashboard', 'normal' );
    }
    public function news_widget_content() {
        echo '<ul id="byct_news_content"></ul><a href="#" id="refreshCTNews" class="button">Refresh</a>';
        $string = file_get_contents(self::get_path() . 'includes/news.php');
        echo self::minify($string);
    }
    public function admin_footer_links() {
        echo '<a href="http://mycircletree.com/client-area/knowledgebase.php?action=displaycat&catid=2" target="_blank">Video Tutorials</a>';
        echo ' | <a href="https://mycircletree.com/client-area/submitticket.php" target="_blank">Contact Circle Tree<sup>&reg;</sup> Support</a>';
        echo ' | <a target="_blank" style="text-decoration:none;font-size:10px;color:#666" href="http://mycircletree.com">Site design &amp; hosting by Circle Tree <img style="vertical-align:middle;opacity:0.3;" width="30" height="30" alt="Website by Circle Tree" src="https://s3.amazonaws.com/myct2/footer-logo-30px.png"/></a>';
    }
    public function admin_bar_icon () {
        if (is_user_logged_in() && is_admin_bar_showing())
            echo self::CIRCLETREE_ADMINBAR_ICON_STYLE;
    }
    /**
     * Minifies a html / js string
     * @param string $string
     * @return string minified string
     */
    public static function minify ($string)
    {
        return str_replace(array("\t","\r\n", "\n", "\r","\t"), '', $string);
    }
}