<?php

/**
 * sfGoogleLogin actions.
 *
 * @package    sfGoogleLoginPlugin
 * @subpackage actions
 * @author     Sebastian Herbermann <sebastian.herbermann@googlemail.com>
 */
class sfGoogleLoginActions extends sfActions {

	public function executeLogin(sfWebRequest $request) {
        if ( $this->getUser()->isAuthenticated() ) {
            $this->redirect('homepage');
        }
        
        $user = $this->getUser();
        
        $googleOpenID = new GoogleOpenID( sfContext::getInstance()->getRequest()->getUriPrefix() );
        
        $user->setAttribute('sfGoogleLogin_returnTo', $request->getUri() );
        $returnTo = $this->generateUrl('sfGoogleLogin_verify', array(), true);
        
        $this->loginUrl = $googleOpenID->getLoginUrl( $returnTo );
        $this->loginJs = $googleOpenID->getLoginJs( $returnTo );
	}
	
	public function executeVerify(sfWebRequest $request) {
		$this->success = $request->getParameter('openid_mode') != 'cancel';
        
        $user = $this->getUser();
        $googleOpenID = new GoogleOpenID( 'http://'.$_SERVER['SERVER_NAME'] );
        
        if ( $googleOpenID->verifyLogin() && $googleUserToken = $googleOpenID->getUser() ) {
            if ( !$googleAccount = Doctrine::getTable('GoogleAccount')->findOneByUserToken( $googleUserToken ) ) {
                $googleAccount = new GoogleAccount();
                $googleAccount->setUserToken( $googleUserToken );
            }
            $googleAccount->setLastLogin( date('Y-m-d H:i:s') );
            $googleAccount->save();
            
            $user->setAuthenticated( true );
            $user->setAttribute( 'sfGoogleLogin_account', $googleAccount );
        } else {
        	$this->success = false;
        }
        
        $this->setLayout(false);
	}
	
    public function executeLogout(sfWebRequest $request) {
        $this->getUser()->setAuthenticated( false );
        $this->getUser()->setAttribute( 'sfGoogleLogin_account', null );
        $this->getUser()->setAttribute( 'sfGoogleLogin_returnTo', null );
        $this->redirect( 'homepage' );
    }
}
