<?php wp_by_ct::echo_stylesheet_link();?>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js"></script>
<script type="text/javascript">
jQuery(function($) {
	$("form").on('submit', function  () {
		setTimeout( function  () {
			$("input").attr('disabled',true);
		    $("html").addClass('disabled'); 
			$("input[type=submit]").val("Please wait...").attr('disabled', true); 
		},100);
	});
});
 var RecaptchaOptions = {
    theme : 'white',
    tabindex: 1
 };
</script>
<div id="lockdown">
	<form method="POST" action="">
		<h1>Too many login attempts</h1>
		<p>Please verify your humanity (this is to protect against brute force attacks)</p>
		<?php if ( $this->message == 'incorrect-captcha-sol') : ?>
		  <div class="error">
		      <h3>Incorrect. Please try again</h3>
		  </div>
		<?php endif; ?>
		<?php echo recaptcha_get_html($this->recaptcha_keys['public'], $this->message, is_ssl());?>
		<input tabindex="1" class="button button-large" type="submit" value="Verify" />
	</form>
</div>