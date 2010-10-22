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

<?php
    if ( $sf_user->isAuthenticated() )
        echo link_to ( 'Logout', $logoutUrl );
    else 
        echo link_to ( 'Login', $loginUrl, array ('class' => 'googleLogin' ) );
?>