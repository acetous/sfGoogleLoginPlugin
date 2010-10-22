<?php

/**
 * Realizes a login for Google accounts via OpenID 2.0.
 * Uses the user session to store data. Session has to be startet before use.
 *
 * @author Sebastian Herbermann <sebastian.herbermann@googlemail.com>
 * @copyright Sebastian Herbermann, 2009
 * @license http://creativecommons.org/licenses/by-sa/3.0/de/
 *
 */
class GoogleOpenID
{
    // Config
    /**
     * Provider URL.
     *
     * @var String
     */
    private $provider = 'https://www.google.com/accounts/o8/id';
    /**
     * Enable or disable debug output.
     *
     * @var boolean
     */
    private $debug = false;
    
    // Private values
    /**
     * Given realm.
     *
     * @var String
     */
    private $realm;
    /**
     * User information.
     *
     * @var String
     */
    private $user, $email;
    
    
    /**
     * Constructs the GoogleOpenID Object and checks the association handle.
     * Requests a new handle if not valid.
     *
     * @param String $realm Realm for the OpenID login.
     */
    public function __construct( $realm )
    {
        $this->realm = $realm;
        $this->checkHandle();
    }
    
    /**
     * Constructs the login URL for the user.
     *
     * @param String $returnTo URL to return to after login.
     * @return String URL
     */
    public function getLoginUrl( $returnTo )
    {
        $params = array(
            'openid.ns' => 'http://specs.openid.net/auth/2.0',
            'openid.mode' => 'checkid_setup',
            'openid.return_to' => $returnTo,
            'openid.claimed_id' => 'http://specs.openid.net/auth/2.0/identifier_select',
            'openid.identity' => 'http://specs.openid.net/auth/2.0/identifier_select',
            'openid.realm' => $this->realm,
            'openid.trust_root' => $this->realm,
            'openid.assoc_handle' => $this->getHandle(),
            'openid.ns.ext1' => 'http://openid.net/srv/ax/1.0',
            'openid.ext1.mode' => 'fetch_request',
            'openid.ext1.type.email' => 'http://axschema.org/contact/email',
            'openid.ext1.if_available' => 'email',
            'openid.ns.sreg' => 'http://openid.net/sreg/1.0',
            'openid.sreg.optional' => 'email',
        );
        $url = $this->buildReqeustUrl( $params );
        return $url;
    }
    
    /**
     * Checks GET and POST data for a valid OpenID response.
     *
     * @return boolean True, if a valid user identifier was found.
     */
    public function verifyLogin()
    {
        // read response
        $params = array();
        foreach ( $_REQUEST as $key => $value )
            if ( substr($key, 0, 6) == 'openid' )
                $params[ $key ] = $value;
                
        if ( !array_key_exists( 'openid_assoc_handle', $params ) )
        {
            return false;
        }

        // verify assoc_handle
        if ( $params['openid_assoc_handle'] == $this->getHandle() )
        {
            if ( $this->debug ) echo 'handle verified<br>';
            
            // compute signed string
            $signed = '';
            foreach( explode(',', $params['openid_signed']) as $field )
                $signed .= ''. $field .':'. $params['openid_'.str_replace('.','_',$field)] . "\n";

            // calculate signature and verify
            $signature = base64_encode( hash_hmac( 'sha1', $signed, $this->getMac(), true) );
            if( $params['openid_sig'] == $signature )
            {
                if ( $this->debug ) echo 'mac-key verified<br>';
                
                $this->user = $params['openid_claimed_id'];
                /**
                 * @todo user's email address
                 */
                
                return true;
            }
            if ( $this->debug ) echo 'signatures did not match<br>';
            return false;
        }
        if ( $this->debug ) echo 'openid_assoc_handle did not match<br>';
        return false;
    }
    
    /**
     * Returns the user identifier after a successful login sequence.
     *
     * @return String The user identifier, null if none present.
     */
    public function getUser()
    {
        return is_null( $this->user ) ? null : $this->user;
    }
    
    /**
     * Returns the user email after a successful login sequence.
     * @todo Read email from response.
     *
     * @return String The user email, null if none present.
     */
    public function getEmail()
    {
        return is_null( $this->email ) ? null : $this->email;
    }
    
    /**
     * Requests the OpenID endpoint from the provider.
     *
     * @return String Endpoint URL
     */
    private function getEndpoint()
    {
        if ( false == ($endpoint = $this->cacheGet('endpoint')) )
        {
            $response = file_get_contents( $this->provider );
            preg_match( '$<URI>(.*)</URI>$', $response, $matches );
            $endpoint = $matches[1];
            $this->cachePut( 'endpoint', $endpoint );
        }
        return $endpoint;
    }
    
    /**
     * Checks if the association handle present and valid.
     *
     */
    private function checkHandle()
    {
        if ( false == ($handle = $this->cacheGet('handle')) )
        {
            $this->renewHandle();
        }
        if ( false == ($handleExpires = $this->cacheGet('handle-expires')) )
        {
            $this->renewHandle();
        }
        if ( $handleExpires < time() )
        {
            $this->renewHandle();
        }
    }
    
    /**
     * Obtains a new association handle and mac-key from the provider.
     *
     */
    private function renewHandle()
    {
        $params = array(
            'openid.ns' => 'http://specs.openid.net/auth/2.0',
            'openid.mode' => 'associate',
            'openid.assoc_type' => 'HMAC-SHA1',
            'openid.session_type' => 'no-encryption'
        );
        $response = $this->getResponse( $params );
        $params = array();
        preg_match_all( '$([a-z0-9\_]+):(.+)$i', $response, $matches, PREG_SET_ORDER);
        foreach($matches as $match)
            $params[$match[1]] = $match[2];
            
        $this->cachePut( 'handle', $params['assoc_handle'] );
        $this->cachePut( 'handle-expires', time() + $params['expires_in'] );
        $this->cachePut( 'mac', base64_decode($params['mac_key']) );
    }
    
    /**
     * Returns the association handle
     *
     * @return String Handle
     */
    private function getHandle()
    {
        return $this->cacheGet('handle');
    }
    
    /**
     * Returns the mac-key.
     *
     * @return String Mac
     */
    private function getMac()
    {
        return $this->cacheGet('mac');
    }
    
    /**
     * Fetchs the response from the server giving through the OpenID endpoint
     * while passing the given parameters.
     *
     * @param array $params Associative array for parameters to pass
     * @return String Response from the OpenID provider
     */
    private function getResponse( array $params )
    {
        $url = $this->buildReqeustUrl( $params );
        
        return file_get_contents( $url );
    }
    
    /**
     * Build the request arguments for an GET request.
     *
     * @param array $params Associative array for parameters to pass
     * @return String Arguments for an GET request
     */
    private function buildReqeustUrl( array $params )
    {
        $paramsDefault = array(
            'openid.realm' => $this->realm
        );
        
        $params = array_merge( $paramsDefault, $params );
        
        $paramsEncoded = '';
        $firstParam = true;
        foreach ( $params as $key => $value )
        {
            if ( $firstParam ) {
                $paramsEncoded .= '?'.$key.'='.urlencode($value);
                $firstParam = false;
            } else {
                $paramsEncoded .= '&'.$key.'='.urlencode($value);
            }
        }
        
        return $this->getEndpoint() . $paramsEncoded;
    }
    
    /**
     * Returns a value from the cache.
     *
     * @param String $key
     * @return String
     */
    private function cacheGet( $key )
    {
        $value = array_key_exists( 'GoogleLogin-'.$key, $_SESSION ) ? $_SESSION[ 'GoogleLogin-'.$key ] : null;
        if ( $this->debug ) echo "cache call: $key => $value<br>";
        return $value;
    }
    
    /**
     * Stores a value in the cache.
     *
     * @param String $key
     * @param String $value
     */
    private function cachePut( $key, $value )
    {
        if ( $this->debug ) echo "cache write $key => $value<br>";
        $_SESSION[ 'GoogleLogin-'.$key ] = $value;
    }
    
    public function getLoginJs( $returnTo )
    {
    	$js = <<<SCRIPT
<script type="text/javascript">
<!--
var openidParams = { realm : '$this->realm', returnToUrl : '$returnTo', opEndpoint : 'https://www.google.com/accounts/o8/ud', onCloseHandler : googleOpenIdLogin, shouldEncodeUrls : 'true' };
var googleOpener = popupManager.createPopupOpener(openidParams);
/*googleOpener.popup(500, 500);*/
//-->
</script>
SCRIPT;
    	return $js;
    }
}

?>
