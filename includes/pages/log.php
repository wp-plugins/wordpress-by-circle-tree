<?php 
/**
 * @var wp_login_lockdown $this 
 */
?>
<div class="wrap">
	<?php screen_icon('byct_log');?>
	<h2>Circle Tree<sup>&reg;</sup> Secure Login</h2>
		<?php if (isset($_SESSION['msg'])) : ?>
		<?php $errors = array(3, 4, 5, 9);?>
	       	<div class="<?php if (in_array($_SESSION['msg'], $errors)) :?>error<?php else:?>updated<?php endif;?> byct_messages">
			<?php if ($_SESSION['msg'] == 1) :?>
				<p>IP blocked</p>
			<?php endif;?>
			<?php if ($_SESSION['msg'] == 2) :?>
				<p>IP unblocked</p>
			<?php endif;?>
			<?php if ($_SESSION['msg'] == 3) :?>
				<p>There was an error processing that request. 
				Please try again.</p>
			<?php endif;?>
			<?php if ($_SESSION['msg'] == 4) :?>
				<p>Invalid IP</p>
			<?php endif;?>
			<?php if ($_SESSION['msg'] == 5) :?>
				<p>You are not authorized to perform that action.</p>
			<?php endif;?>
			<?php if ($_SESSION['msg'] == 6) :?>
				<p>Log Cleared</p>
			<?php endif;?>
			<?php if ($_SESSION['msg'] == 7) :?>
				<p>IP address whitelisted</p>
			<?php endif;?>
			<?php if ($_SESSION['msg'] == 8) :?>
				<p>IP address removed from whitelist</p>
			<?php endif;?>
			<?php if ($_SESSION['msg'] == 9) :?>
				<p>IP address already listed</p>
			<?php endif;?>
		</div>
		<script>
		jQuery(function($) {
			setTimeout(function  () {
				$(".byct_messages").fadeOut(500); 		  
			}, 2000);
		});
		</script>
		<?php unset($_SESSION['msg']);?>
		<?php endif; ?>
		<div class="block-box" id="blacklist">
			<h3>Block an IP:</h3>
			<form method="GET" action="<?php echo admin_url('index.php')?>">
				<input type="hidden" name="page" value="circle_tree_login_log" />
				<input type="hidden" name="action" value="block"/>
				<input type="hidden" name="nonce" value="<?php echo wp_create_nonce('wp_login_lockdown'); ?>"/>
				<input type="text" name="ip" size="12"/>
				<?php submit_button('Block', 'primary', null, false);?>
			</form>
		</div> 
		<div class="block-box" id="whitelist">
			<h3>Allow an IP:</h3>
			<form method="GET" action="<?php echo admin_url('index.php')?>">
				<input type="hidden" name="page" value="circle_tree_login_log" />
				<input type="hidden" name="action" value="whitelist"/>
				<input type="hidden" name="nonce" value="<?php echo wp_create_nonce('wp_login_lockdown'); ?>"/>
				<input type="text" name="ip" size="12"/>
				<?php submit_button('Block', 'primary', null, false);?>
			</form>
		</div> 
		<?php  
		$log = $this->get_transient();
		if ($log) :
			if (isset($log['reset'])) : ?>
			<h3>Invalid passwords requiring a <abbr title="Lockdown page with hard to read text">CAPTCHA</abbr>:</h3>
			<table class="widefat">
				<thead>
					<tr>
						<th class="manage-column column-title ip">
							IP
						</th>
						<th># of CAPTCHA Cycles</th>
						<th class="manage-column column-title">
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
		        				<form method="GET" action="<?php echo admin_url('index.php')?>" style="display:inline-block;">
		            		    	<input type="hidden" name="page" value="circle_tree_login_log" />
		            			    <input type="hidden" name="action" value="block"/>
		            			    <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('wp_login_lockdown'); ?>"/>
		            			    <input type="hidden" name="ip" value="<?php echo esc_attr($ip)?>" />
		            			    <div class="byct_tooltip" title="<?php echo $title;?>">
			            			    <?php submit_button('Block', '', null, false);?>
		            			    </div>
		        		        </form>
		        				<form method="GET" action="<?php echo admin_url('index.php')?>" style="display:inline-block;">
		            		    	<input type="hidden" name="page" value="circle_tree_login_log" />
		            			    <input type="hidden" name="action" value="whitelist"/>
		            			    <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('wp_login_lockdown'); ?>"/>
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
		<?php endif;?>
		<?php if ( $this->get_whitelisted_ips()) :?>
		  <h3>Allowed IP's</h3>
		  <table class="widefat">
            	<thead>
            		<tr>
            			<th class="manage-column column-title ip">
            				IP
            			</th>
            			<th>Un-Whitelist</th>
            		</tr>
            	</thead>
            	<tbody id="whitelisted-ips">
        			<?php foreach ($this->get_whitelisted_ips() as $ip) :?>
            			<tr>
            				<td class="ip"><?php echo $ip?></td>
            				<td>
            				    <form method="GET" action="<?php echo admin_url('index.php')?>">
            				    <input type="hidden" name="page" value="circle_tree_login_log" />
            				    <input type="hidden" name="action" value="unwhitelist"/>
            				    <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('wp_login_lockdown'); ?>"/>
            				    <input type="hidden" name="ip" value="<?php echo esc_attr($ip)?>" />
            				    <?php submit_button('Remove', '', null, false);?>
            			    </form>
            				</td>
            			</tr>
        			<?php endforeach; ?>
            	</tbody>
            </table>
		<?php endif;?>
		<?php  if ($this->get_blocked_ips()) :?>
			<h3>Blocked IP's</h3>
			<table class="widefat">
            	<thead>
            		<tr>
            		 	<th class="manage-column column-title ip">
            				IP
            			</th>
            			<th>Unblock</th>
            		</tr>
            	</thead>
            	<tbody id="blocked-ips">
        			<?php foreach ($this->get_blocked_ips() as $ip) :?>
            			<tr>
            				<td class="ip"><?php echo $ip?></td>
            				<td>
            				    <form method="GET" action="<?php echo admin_url('index.php')?>">
            				    <input type="hidden" name="page" value="circle_tree_login_log" />
            				    <input type="hidden" name="action" value="unblock"/>
            				    <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('wp_login_lockdown'); ?>"/>
            				    <input type="hidden" name="ip" value="<?php echo esc_attr($ip)?>" />
            				    <?php submit_button('Unblock', '', null, false);?>
            			    </form>
            				</td>
            			</tr>
        			<?php endforeach; ?>
            	</tbody>
            </table>
		<?php endif; ?>
		<?php if ( $this->get_log() ) :?>
		<h3>Activity Log <?php if (current_user_can('activate_plugins')) :?>
		      <?php $url = 'index.php?page=circle_tree_login_log&action=clear_log&nonce='.wp_create_nonce('wp_login_lockdown');?>
		      <a class="button" href="<?php echo $url?>">Clear</a>
    		<?php endif;//End clear log only for admins ?></h3>
		<table class="wp-list-table widefat fixed">
			<thead>
				<tr>
					<th class="manage-column column-title">
						Message
					</th>
					<th class="manage-column column-title">
						Username
					</th>
					<th class="manage-column column-title ip">
						IP
					</th>
					<th class="manage-column column-title">
						When
					</th>
				</tr>
			</thead>
			<tbody id="the-log">
				<?php $log_array = array_reverse( $this->get_log(), true );
				foreach ($log_array as $key => $item) {
					$colspan = 1;
					if (! isset($item['username'])) $colspan += 1;
					if (! isset($item['ip'])) $colspan += 1;
					if (! isset($item['time'])) $colspan += 1;
					echo '<tr '.($key % 2 ? 'class="alternate"' : '').'>';
					echo 	'<td colspan="' . $colspan . '">' . $item['msg'] . '</td>';
					echo 	isset($item['username']) ? '<td>' . $item['username'] . '</td>' : '';
					if (isset($item['ip'])) {
					   $url = 'index.php?page=circle_tree_login_log&action=block&nonce='.wp_create_nonce('wp_login_lockdown') . 
					       '&ip=' . $item['ip'];
				       echo 	'<td class="ip">' . $item['ip'] . ' <a class="button button-mini" href="' . $url . '">Block</a></td>';
					}
					echo 	isset($item['time']) ? '<td>' . wp_login_lockdown::time_ago($item['time']) . '</td>' : '';
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
	</div>