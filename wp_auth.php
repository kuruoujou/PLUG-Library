<?php
require('../wp-blog-header.php');

class TMRSAuthentication
{
    public function do_basic_authentication()
    {
        if (!isset($_SERVER['PHP_AUTH_USER'])) {
            header('WWW-Authenticate: Basic realm="Login to the PLUG Library Database"');
            header('HTTP/1.0 401 Unauthorized');
            # this message is displayed if the user cancels login
            $this->show_failed_login();
            exit;
        } else {
            $username = $_SERVER['PHP_AUTH_USER'];
            $password = $_SERVER['PHP_AUTH_PW'];
            if( $this->_authenticate_wp($username, $password) ) {
                return true;
            } else {
                return false;
            }
        }
    }

    private function _authenticate_wp($username, $password)
    {
        global $wp_error;
        if ( empty($wp_error) ) {
            $wp_error = new WP_Error();
        }
        $user = wp_authenticate($username, $password);
        if(is_wp_error($user)) {
            return false;
        } else {
            return true;
        }
    }

    public function show_failed_login()
    {
        echo "Error: Login failed. Login to the PLUG Library with your Wordpress username and password.";
    }

}
?>
