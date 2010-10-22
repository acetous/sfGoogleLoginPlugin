<?php if ( $success ) : ?>
    <script type="text/javascript">
	<!--
	window.close();
	//-->
	</script>
    <h3>Du bist nun eingeloggt.</h3>
<?php else : ?>
    <h3>Fehler beim Login!</h3>
<?php endif; ?>

<p>Schließe dieses Fenster, um fortzufahren oder gehe zu deine <?php echo link_to('deiner letzten Seite', $sf_user->getAttribute('sfGoogleLogin_returnTo') );?>.</p>
