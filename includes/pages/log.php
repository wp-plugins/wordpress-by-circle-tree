<?php
function refresh_form ()
{ ?>
<form method="GET" class="ajax refresh_form" action="<?php echo admin_url('index.php')?>" style="display:inline-block" >
	<input type="hidden" name="page" value="circle_tree_login_log" />
	<input type="hidden" name="action" value=""/>
	<input type="hidden" name="new" value="true" />
	<input type="hidden" name="nonce" value="<?php echo wp_create_nonce('wp_login_lockdown'); ?>"/>
	<?php submit_button('Refresh', 'mini', '', false);?>
</form>
<?php 
}
if (! isset($_SESSION['msg'])) {
    $_SESSION['msg'] = -1;
} 
/**
 * @var wp_login_lockdown $this 
 */
?>
<div class="wrap">
	<?php screen_icon('byct_log');?>
	<h2>Circle Tree<sup>&reg;</sup> Secure Login <?php refresh_form();?></h2>
	<noscript>
    	<div class="error">
        	<p>
        	   Enable JavaScript for an enhanced experience.
        	   <a href="http://www.activatejavascript.org/" target="_blank" >Learn More</a>
        	</p>
    	</div> 
	</noscript>
		<div class="byct_messages code_0 error hidden">
		    <p>Unknown AJAX Action</p>
	    </div>
		<div class="byct_messages code_1 updated <?php if ($_SESSION['msg'] != 1) :?>hidden<?php endif;?>">
		    <p>IP blocked</p>
	    </div>
		<div class="byct_messages code_2 updated <?php if ($_SESSION['msg'] != 2) :?>hidden<?php endif;?>">
		    <p>IP unblocked</p>
	    </div>
		<div class="byct_messages code_3 error <?php if ($_SESSION['msg'] != 3) :?>hidden<?php endif;?>">
		    <p>There was an error processing that request. 
		    Please try again.</p>
	    </div>
		<div class="byct_messages code_4 error <?php if ($_SESSION['msg'] != 4) :?>hidden<?php endif;?>">
		    <p>Invalid IP</p>
	    </div>
		<div class="byct_messages code_5 error <?php if ($_SESSION['msg'] != 5) :?>hidden<?php endif;?>">
		    <p>You are not authorized to perform that action.</p>
	    </div>
		<div class="byct_messages code_6 updated <?php if ($_SESSION['msg'] != 6) :?>hidden<?php endif;?>">
		    <p>Log Cleared</p>
	    </div>
		<div  class="byct_messages code_7 updated <?php if ($_SESSION['msg'] != 7) :?>hidden<?php endif;?>">
		    <p>IP address whitelisted</p>
	    </div>
		<div class="byct_messages code_8 updated <?php if ($_SESSION['msg'] != 8) :?>hidden<?php endif;?>">
		    <p>IP address removed from whitelist</p>
	    </div>
		<div class="byct_messages code_9 error <?php if ($_SESSION['msg'] != 9) :?>hidden<?php endif;?>">
		    <p>IP address already listed</p>
	    </div>
	    <div class="byct_messages code_10 error <?php if ($_SESSION['msg'] != 10) :?>hidden<?php endif;?>">
		    <p>Please refresh the page and try again.</p>
	    </div>
	    <div class="byct_messages code_11 error <?php if ($_SESSION['msg'] != 11) :?>hidden<?php endif;?>">
		    <p>Private IP Addresses are not allowed.</p>
	    </div>
	    <div class="byct_messages code_12 updated <?php if ($_SESSION['msg'] != 12) :?>hidden<?php endif;?>">
		    <p>Refreshed.</p>
	    </div>
		<?php if (isset($_SESSION['msg'])) : ?>
		<script>
		jQuery(function($) {
			setTimeout(function  () {
				$(".byct_messages").not('.hidden').stop().fadeTo(500,0).addClass('hidden'); 		  
			}, 2000);
		});
		</script>
		<?php unset($_SESSION['msg']);?>
		<?php endif; ?>
		<div class="block-box" id="blacklist">
			<h3>Block an IP:</h3>
			<form method="GET" class="ajax" action="<?php echo admin_url('index.php')?>">
				<input type="hidden" name="page" value="circle_tree_login_log" />
				<input type="hidden" name="action" value="block"/>
				<input type="hidden" name="new" value="true" />
				<input type="hidden" name="nonce" value="<?php echo wp_create_nonce('wp_login_lockdown'); ?>"/>
				<input type="text" name="ip" size="12"/>
				<?php submit_button('Block', 'primary', null, false);?>
			</form>
		</div> 
		<div class="block-box" id="whitelist">
			<h3>Allow an IP:</h3>
			<form method="GET" class="ajax" action="<?php echo admin_url('index.php')?>">
				<input type="hidden" name="page" value="circle_tree_login_log" />
				<input type="hidden" name="action" value="whitelist"/>
				<input type="hidden" name="new" value="true" />
				<input type="hidden" name="nonce" value="<?php echo wp_create_nonce('wp_login_lockdown'); ?>"/>
				<input type="text" name="ip" size="12"/>
				<?php submit_button('Allow', 'primary', null, false);?>
			</form>
		</div> 
		<?php  
		$log = $this->get_transient();
		if ($log) :
			if (isset($log['reset'])) : ?>
			<h3>Invalid passwords requiring a <abbr class="byct_tooltip" title="Lockdown page with hard to read text">CAPTCHA</abbr>:</h3>
			<table class="widefat" style="visibility: hidden;">
				<thead>
					<tr>
						<th class="manage-column column-title ip">
							IP
						</th>
						<th># of CAPTCHA Cycles</th>
						<th class="manage-column column-title actions">
							Actions
						</th>
					</tr>
				</thead>
				<tbody id="resets">
					<?php foreach ($log['reset'] as $ip => $count ) : ?>
					<?php $current_user = ($ip == self::$remote_ip);?>
						<?php $class = $current_user ? ' warning' : ''; ?>
						<?php $title = $current_user ? 'Warning: This is your current IP Address' : 'This is the number of CAPTCHAS for this IP'; ?>
						<tr class="<?php echo $class?>">
							<td class="ip"><?php echo $ip?></td>
							<td><?php echo $count; ?></td>
							<td>
		        				<form method="GET" class="ajax" action="<?php echo admin_url('index.php')?>" style="display:inline-block;">
		            		    	<input type="hidden" name="page" value="circle_tree_login_log" />
		            			    <input type="hidden" name="action" value="block"/>
		            			    <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('wp_login_lockdown'.$ip); ?>"/>
		            			    <input type="hidden" name="ip" value="<?php echo esc_attr($ip)?>" />
		            			    <div class="byct_tooltip" title="<?php echo $title;?>">
			            			    <?php submit_button('Block', '', null, false);?>
		            			    </div>
		        		        </form>
		        				<form method="GET" class="ajax" action="<?php echo admin_url('index.php')?>" style="display:inline-block;">
		            		    	<input type="hidden" name="page" value="circle_tree_login_log" />
		            			    <input type="hidden" name="action" value="whitelist"/>
		            			    <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('wp_login_lockdown'.$ip); ?>"/>
		            			    <input type="hidden" name="ip" value="<?php echo esc_attr($ip)?>" />
		            			    <?php $title = 'Add an exception, and allow this user to proceed without any CAPTCHA\'s';?>
		            			    <div class="byct_tooltip" title="<?php echo $title;?>">
			            			    <?php submit_button('Whitelist', '', null, false);?>
		            			    </div>
		        		        </form>
	        		        </td>
					<?php endforeach;?>
				</tbody>
			</table>
			<?php endif; ?>
		<?php else:?>
		<div class="updated">
			<p>Log is empty!</p>
		</div>
		<?php endif; //end CAPTCHA view?>
		<?php if ( $this->get_whitelisted_ips()) :?>
		  <div class="col_wrapper" id="whitelisted">
    		  <h3>Allowed IP's</h3>
    		  <table class="widefat" style="visibility: hidden;">
                	<thead>
                		<tr>
                			<th class="manage-column column-title ip">
                				IP
                			</th>
                			<th class="actions">Un-Whitelist</th>
                		</tr>
                	</thead>
                	<tbody id="whitelisted-ips">
            			<?php foreach ($this->get_whitelisted_ips() as $ip) :?>
                			<tr>
                				<td class="ip"><?php echo $ip?></td>
                				<td>
                				    <form method="GET" class="ajax" action="<?php echo admin_url('index.php')?>">
                				    <input type="hidden" name="page" value="circle_tree_login_log" />
                				    <input type="hidden" name="action" value="unwhitelist"/>
                				    <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('wp_login_lockdown'.$ip); ?>"/>
                				    <input type="hidden" name="ip" value="<?php echo esc_attr($ip)?>" />
                				    <?php submit_button('Remove', '', null, false);?>
                			    </form>
                				</td>
                			</tr>
            			<?php endforeach; ?>
                	</tbody>
                </table>
            </div>
		<?php endif; //End Allowed List?>
		<?php  if ($this->get_blocked_ips()) :?>
    		<div class="col_wrapper" id="blocked">
    			<h3>Blocked IP's</h3>
    			<table class="widefat" style="visibility: hidden;">
                	<thead>
                		<tr>
                		 	<th class="manage-column column-title ip">
                				IP
                			</th>
                			<th class="actions">Unblock</th>
                		</tr>
                	</thead>
                	<tbody id="blocked-ips">
            			<?php foreach ($this->get_blocked_ips() as $ip) :?>
                			<tr>
                				<td class="ip"><?php echo $ip?></td>
                				<td>
                				    <form method="GET" class="ajax" action="<?php echo admin_url('index.php')?>">
                    				    <input type="hidden" name="page" value="circle_tree_login_log" />
                    				    <input type="hidden" name="action" value="unblock"/>
                    				    <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('wp_login_lockdown'.$ip); ?>"/>
                    				    <input type="hidden" name="ip" value="<?php echo esc_attr($ip)?>" />
                    				    <?php submit_button('Unblock', '', null, false);?>
                    			    </form>
                				</td>
                			</tr>
            			<?php endforeach; ?>
                	</tbody>
                </table>
            </div>
		<?php endif; //End blocked list?>
		
		<?php if ( $this->get_log() ) :?>
		<h3>Activity Log <?php if (current_user_can('activate_plugins')) :?>
		      <?php $url = 'index.php?page=circle_tree_login_log&action=clear_log&nonce='.wp_create_nonce('wp_login_lockdown');
		      $random_ip = mt_rand(1, 255) . '.' .mt_rand(1, 255) . '.' .mt_rand(1, 255) . '.' .mt_rand(1, 255); ?>
		      <a class="button ajax"
		          data-action="clear_log"
		          data-nonce="<?php echo wp_create_nonce('wp_login_lockdown' . $random_ip)?>" 
		          data-ip="<?php echo $random_ip;?>"
	              href="<?php echo admin_url($url)?>">Clear</a>
	              &nbsp;&#124;&nbsp;
    		<?php endif;//End clear log only for admins ?>
    		Filter:
            <a class="button <?php if (isset($_GET['show']) && 'blocked' == $_GET['show']) :?>disabled<?php endif;?>"
                href="<?php echo admin_url('index.php?page=circle_tree_login_log&show=blocked#the-log')?>">
                Show Blocked
            </a>
    		<a class="button <?php if (isset($_GET['show']) && 'unblocked' == $_GET['show']) :?>disabled<?php endif;?>" 
    		      href="<?php echo admin_url('index.php?page=circle_tree_login_log&show=unblocked#the-log')?>">
    		      Show Unblocked</a>
    		<?php if (isset($_GET['show'])):?>
    		  <a class="button" href="<?php echo admin_url('index.php?page=circle_tree_login_log#the-log')?>">Show All</a>
    		<?php endif;?>
		</h3>
		<table class="wp-list-table widefat fixed">
			<thead>
				<tr>
					<th class="manage-column column-title">
						Message
					</th>
					<th class="manage-column column-title">
						Username
					</th>
					<th class="manage-column column-title">
						Time
					</th>
					<th class="manage-column column-title">
						When
					</th>
					<th class="manage-column column-title ip">
						IP
					</th>
					<th class="manage-column column-title actions">
						Actions
					</th>
				</tr>
			</thead>
			<tbody id="the-log">
				<?php $log_array = array_reverse( $this->get_log(), true );
				foreach ($log_array as $key => $item) {
                    if (isset($_GET['show'])) {
                        if ('unblocked' == $_GET['show'] && in_array($item['ip'], $this->get_blocked_ips())) {
                            continue;
                        }
                        if ('blocked' == $_GET['show'] && ! in_array($item['ip'], $this->get_blocked_ips())) {
                            continue;
                        }
                    }
					echo '<tr '.($key % 2 ? 'class="alternate"' : '').'>';
					echo 	'<td>' . $item['msg'] . '</td>';
					echo 	isset($item['username']) ? '<td>' . $item['username'] . '</td>' : '<td></td>';
					echo 	isset($item['time']) ? '<td>' . date_i18n('m/d/y h:i:s', $item['time']) . '</td>' : '<td></td>';
					echo 	isset($item['time']) ? '<td>' . wp_login_lockdown::time_ago($item['time']) . '</td>' : '<td></td>';
					if (isset($item['ip'])) {
                        $ip = $item['ip'];
                        $this_nonce = wp_create_nonce('wp_login_lockdown'.$ip);
					   $url = 'index.php?page=circle_tree_login_log' . 
					       '&ip=' . $ip . '&' . 
					       '&nonce='.$this_nonce;  
				       echo '<td class="ip">' . $ip . '</td>';
				       echo '<td>';
                        if (! in_array($ip, $this->get_blocked_ips())) {
                            echo     '<a class="button button-mini button-primary ajax"' .
                                    ' data-ip="'.$ip.'" data-action="block"'.
                                    ' data-nonce="'.$this_nonce.'"'.
                                    ' href="' . $url . '&action=block">Block</a>';
                        } else {
                            echo     '<a class="button button-mini ajax"'.
                                    ' data-ip="'.$item["ip"].'" data-action="unblock"'.
                                    ' data-nonce="'.$this_nonce.'"'.
                                    ' href="' . $url . '&action=unblock">Unblock</a>';
                        }
				       echo '</td>';
					} else {
                        echo '<td></td><td></td>';
                    }
					echo  '</tr>';
				}
				?>
			</tbody>
		</table>
		<?php endif; //End log view?>
		
		<?php if (current_user_can('manage_options')) :?>
		  <p class="alignright">
		      <a href="<?php echo admin_url('options-general.php?page=circle_tree_login_settings')?>">Change Secure Login Settings</a>  
		  </p>
		<?php endif;?>
		<?php refresh_form();?>
	<noscript>
    	<style>.widefat {
    	   visibility: visible !important;
    	}
    	</style>
	</noscript>
</div><?php //end .wrap?>