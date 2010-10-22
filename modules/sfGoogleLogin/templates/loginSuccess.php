<?php use_javascript('/sfGoogleLoginPlugin/js/sfGoogleLogin_jQuery'); ?>
<?php use_javascript('/sfGoogleLoginPlugin/js/sfGoogleLogin'); ?>

<?php if ( !sfConfig::get('sfGoogleLogin_jsLoaded') ) : ?>
<script type="text/javascript">
<!--
$('document').ready(function(e){
	$('a.googleLogin').click(function(e){
	    e.preventDefault();
	    var gLogin = new googleLogin();
	    gLogin.googleLoginPopup();
	});
});
//-->
</script>
<?php endif; sfConfig::set('sfGoogleLogin_jsLoaded', true) ?>

<h2>Login</h2>

<p>Login using your <?php echo link_to ( 'Google Account', $loginUrl, array ('class' => 'googleLogin' ) ); ?>.</p>