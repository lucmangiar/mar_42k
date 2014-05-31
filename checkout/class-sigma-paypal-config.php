<?php

class Paypal_Utilities {

    public static function get_paypal_url() {
        return (static::$using_sandbox? "https://www.sandbox.paypal.com/webscr" : "https://www.paypal.com/cgi-bin/webscr");
    }

    public static function get_paypal_endpoint() {
        return 'post_paypal_ipn';
    }

}

?>