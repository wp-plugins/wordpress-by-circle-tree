<div class="wrap">
	<?php screen_icon('byct_settings');?>
	<h2>Circle Tree<sup>&reg;</sup> Secure Login Settings</h2>
	<?php if (isset($_SESSION['msg'])): ?>
	    <div class="updated inline">
	        <?php if (in_array(1, $_SESSION['msg'])):?>
	            <p>Saved Settings</p>
	        <?php endif;?>
	        <?php if (in_array('2', $_SESSION['msg'])):?>
	            <p>Settings Reset to Defaults</p>
	        <?php endif;?>
	        <?php if (in_array('3', $_SESSION['msg'])):?>
	            <p class="error">
    	            <?php $conflicts = $this->get_admin_conflicts();?>
    	            <?php if (count($conflicts) > 1) :?>
    	               There are already existing users with a restricted name. Users: <?php echo implode(' ,', $conflicts);?>
    	            <?php else:?>
    	               There is an existing username using a restricted name: <?php echo implode(' ,', $conflicts);?>
    	            <?php endif;?>
    	            <br/>
	               Please create a new, more secure user account. Then delete the old account. When you delete the old account,
	               WordPress will give you the option to attribute posts to the new user.
	            </p>
	        <?php endif;?>
	    </div>
	    <?php unset($_SESSION['msg']); ?>
	<?php endif;?>
	<form method="post" action="<?php echo $_SERVER['PHP_SELF']?>" style="display:inline-block" >
	    <input type="hidden" name="action" value="save_circle_tree_secure_login_settings"/>
	    <input type="hidden" name="page" value="circle_tree_login_settings" />
	    <?php echo wp_nonce_field('save_circle_tree_login_settings','nonce');?>
        <table class="form-table" >
            <?php foreach ($this->get_settings() as $setting) :?>
                <tr>
                    <td class="label" >
                    	<?php $tooltip = isset($setting['tooltip']); ?>
                        <label 
                        	for="<?php echo $setting['name']?>"
                        	class="<?php echo $tooltip ? 'byct_tooltip label' : 'label'?>"
                        	<?php echo $tooltip ? 'title="' . htmlentities($setting['tooltip']) . '"' : '';?>
                        >
                            <?php echo $setting['label']?>
                        </label>
                    </td>
                    <td>
                        <?php switch ($setting['type']) { 
                        case 'text':?>
                            <input type="text" name="settings[<?php echo $setting['name']?>]" 
                            id="<?php echo $setting['name']; ?>" value="<?php echo self::get_setting($setting['name']);?>"/>
                        <?php break;?>
                        <?php case 'checkbox': ?>
                            <input type="checkbox" name="settings[<?php echo $setting['name']?>]"
                                id="<?php echo $setting['name']; ?>"
                                <?php checked(self::get_setting($setting['name']));?>/>
                        <?php break;?>
                        <?php case 'select': ?>
                            <select name="settings[<?php echo $setting['name']?>]" 
                                id="<?php echo $setting['name']; ?>">
                                <?php foreach ($setting['options'] as $value => $label) :?>
                                    <option <?php selected(self::get_setting($setting['name']), $value); ?>
                                        value="<?php echo $value?>"><?php echo $label?></option>
                                <?php endforeach;?>
                            </select>
                        <?php break;?>
                        <?php case 'hidden':?>
                        <?php //these are hard coded into the register_settings method?>
                        <?php break;?>
                        <?php default: ?>
                            Unknown $type = <?php echo $setting['type'];?>
                        <?php break; 
                        } ?>
                    </td>
                </tr>
            <?php endforeach;?>
        </table>	    
	    <?php echo submit_button('Save Changes')?>
	</form>
	<form method="post" action="<?php echo $_SERVER['PHP_SELF']?>" style="display: inline-block;" >
	    <input type="hidden" name="action" value="reset_circle_tree_secure_login_settings"/>
	    <input type="hidden" name="page" value="circle_tree_login_settings" />
	    <?php echo wp_nonce_field('reset_circle_tree_login_settings','nonce');?>
	    <?php echo submit_button('Reset','secondary')?>
    </form>
    <p>
        <a href="<?php echo admin_url('index.php?page=circle_tree_login_log')?>">View Security Log</a>
    </p>
</div>