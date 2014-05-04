<?php

class Paypal_Functions {
    public static function set_express_checkout_dg( $paymentAmount, $currencyCodeType, $paymentType, $returnURL, $cancelURL, $items) {
        //------------------------------------------------------------------------------------------------------------------------------------
        // Construct the parameter string that describes the SetExpressCheckout API call in the shortcut implementation

        $nvpstr = "&PAYMENTREQUEST_0_AMT=". $paymentAmount;
        $nvpstr .= "&PAYMENTREQUEST_0_PAYMENTACTION=" . $paymentType;
        $nvpstr .= "&RETURNURL=" . $returnURL;
        $nvpstr .= "&CANCELURL=" . $cancelURL;
        $nvpstr .= "&PAYMENTREQUEST_0_CURRENCYCODE=" . $currencyCodeType;
        $nvpstr .= "&REQCONFIRMSHIPPING=0";
        $nvpstr .= "&NOSHIPPING=1";

        foreach($items as $index => $item) {
            $nvpstr .= "&L_PAYMENTREQUEST_0_NAME" . $index . "=" . urlencode($item["name"]);
            $nvpstr .= "&L_PAYMENTREQUEST_0_AMT" . $index . "=" . urlencode($item["amt"]);
            $nvpstr .= "&L_PAYMENTREQUEST_0_QTY" . $index . "=" . urlencode($item["qty"]);
            $nvpstr .= "&L_PAYMENTREQUEST_0_ITEMCATEGORY" . $index . "=Digital";
        }


        //'---------------------------------------------------------------------------------------------------------------
        //' Make the API call to PayPal
        //' If the API call succeded, then redirect the buyer to PayPal to begin to authorize payment.
        //' If an error occured, show the resulting errors
        //'---------------------------------------------------------------------------------------------------------------
        $resArray = static::hash_call("set_express_checkout_dg", $nvpstr);
        $ack = strtoupper($resArray["ACK"]);
        if($ack == "SUCCESS" || $ack == "SUCCESSWITHWARNING")
        {
            $token = urldecode($resArray["TOKEN"]);
            $_SESSION['TOKEN'] = $token;
        }

        return $resArray;
    }

    /*
    '-------------------------------------------------------------------------------------------
    ' Purpose: 	Prepares the parameters for the GetExpressCheckoutDetails API Call.
    '
    ' Inputs:
    '		None
    ' Returns:
    '		The NVP Collection object of the GetExpressCheckoutDetails Call Response.
    '-------------------------------------------------------------------------------------------
    */
    public static function get_express_checkout_details($token) {
        //'--------------------------------------------------------------
        //' At this point, the buyer has completed authorizing the payment
        //' at PayPal.  The function will call PayPal to obtain the details
        //' of the authorization, incuding any shipping information of the
        //' buyer.  Remember, the authorization is not a completed transaction
        //' at this state - the buyer still needs an additional step to finalize
        //' the transaction
        //'--------------------------------------------------------------

        //'---------------------------------------------------------------------------
        //' Build a second API request to PayPal, using the token as the
        //'  ID to get the details on the payment authorization
        //'---------------------------------------------------------------------------
        $nvpstr = "&TOKEN=" . $token;

        //'---------------------------------------------------------------------------
        //' Make the API call and store the results in an array.
        //'	If the call was a success, show the authorization details, and provide
        //' 	an action to complete the payment.
        //'	If failed, show the error
        //'---------------------------------------------------------------------------
        $resArray = static::hash_call("get_express_checkout_details",$nvpstr);
        $ack = strtoupper($resArray["ACK"]);
        if($ack == "SUCCESS" || $ack=="SUCCESSWITHWARNING") {
            return $resArray;
        }
        else return false;

    }

    /*
    '-------------------------------------------------------------------------------------------------------------------------------------------
    ' Purpose: 	Prepares the parameters for the GetExpressCheckoutDetails API Call.
    '
    ' Inputs:
    '		sBNCode:	The BN code used by PayPal to track the transactions from a given shopping cart.
    ' Returns:
    '		The NVP Collection object of the GetExpressCheckoutDetails Call Response.
    '--------------------------------------------------------------------------------------------------------------------------------------------
    */
    public static function confirm_payment( $token, $paymentType, $currencyCodeType, $payerID, $FinalPaymentAmt, $items ) {
        /* Gather the information to make the final call to
           finalize the PayPal payment.  The variable nvpstr
           holds the name value pairs
           */
        $token 				= urlencode($token);
        $paymentType 		= urlencode($paymentType);
        $currencyCodeType 	= urlencode($currencyCodeType);
        $payerID 			= urlencode($payerID);
        $serverName 		= urlencode($_SERVER['SERVER_NAME']);

        $nvpstr  = '&TOKEN=' . $token . '&PAYERID=' . $payerID . '&PAYMENTREQUEST_0_PAYMENTACTION=' . $paymentType . '&PAYMENTREQUEST_0_AMT=' . $FinalPaymentAmt;
        $nvpstr .= '&PAYMENTREQUEST_0_CURRENCYCODE=' . $currencyCodeType . '&IPADDRESS=' . $serverName;

        foreach($items as $index => $item) {
            $nvpstr .= "&L_PAYMENTREQUEST_0_NAME" . $index . "=" . urlencode($item["name"]);
            $nvpstr .= "&L_PAYMENTREQUEST_0_AMT" . $index . "=" . urlencode($item["amt"]);
            $nvpstr .= "&L_PAYMENTREQUEST_0_QTY" . $index . "=" . urlencode($item["qty"]);
            $nvpstr .= "&L_PAYMENTREQUEST_0_ITEMCATEGORY" . $index . "=Digital";
        }
        /* Make the call to PayPal to finalize payment
           If an error occured, show the resulting errors
           */
        $resArray = static::hash_call("DoExpressCheckoutPayment",$nvpstr);

        /* Display the API response back to the browser.
           If the response from PayPal was a success, display the response parameters'
           If the response was an error, display the errors received using APIError.php.
           */
        $ack = strtoupper($resArray["ACK"]);

        return $resArray;
    }
    /**
    '-------------------------------------------------------------------------------------------------------------------------------------------
     * hash_call: Function to perform the API call to PayPal using API signature
     * @methodName is name of API  method.
     * @nvpStr is nvp string.
     * returns an associtive array containing the response from the server.
    '-------------------------------------------------------------------------------------------------------------------------------------------
     */
    private static function hash_call($methodName,$nvpStr) {
        //declaring of global variables
        global $API_Endpoint, $version, $API_UserName, $API_Password, $API_Signature;
        global $USE_PROXY, $PROXY_HOST, $PROXY_PORT;
        global $gv_ApiErrorURL;
        global $sBNCode;

        //setting the curl parameters.
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$API_Endpoint);
        curl_setopt($ch, CURLOPT_VERBOSE, 1);

        //turning off the server and peer verification(TrustManager Concept).
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_POST, 1);

        //if USE_PROXY constant set to TRUE in Constants.php, then only proxy will be enabled.
        //Set proxy name to PROXY_HOST and port number to PROXY_PORT in constants.php
        if($USE_PROXY) {
            curl_setopt ($ch, CURLOPT_PROXY, $PROXY_HOST. ":" . $PROXY_PORT);
        }
        //NVPRequest for submitting to server
        $nvpreq="METHOD=" . urlencode($methodName) . "&VERSION=" . urlencode($version) . "&PWD=" . urlencode($API_Password) . "&USER=" . urlencode($API_UserName) . "&SIGNATURE=" . urlencode($API_Signature) . $nvpStr . "&BUTTONSOURCE=" . urlencode($sBNCode);

        //setting the nvpreq as POST FIELD to curl
        curl_setopt($ch, CURLOPT_POSTFIELDS, $nvpreq);

        //getting response from server
        $response = curl_exec($ch);

        //convrting NVPResponse to an Associative Array
        $nvpResArray=deformatNVP($response);
        $nvpReqArray=deformatNVP($nvpreq);
        $_SESSION['nvpReqArray']=$nvpReqArray;

        if (curl_errno($ch)) {
            // moving to display page to display curl errors
            $_SESSION['curl_error_no']=curl_errno($ch) ;
            $_SESSION['curl_error_msg']=curl_error($ch);

            //Execute the Error handling module to display errors.
        } else {
            //closing the curl
            curl_close($ch);
        }

        return $nvpResArray;
    }

    /*'----------------------------------------------------------------------------------
     Purpose: Redirects to PayPal.com site.
     Inputs:  NVP string.
     Returns:
    ----------------------------------------------------------------------------------
    */
    // TODO Lucho Revisar esta garompa porque las variables que se marcan como global las tengo en class-sigma-paypal
    public static function RedirectToPayPal ($token) {
        global $PAYPAL_URL;

        // Redirect to paypal.com here
        $payPalURL = $PAYPAL_URL . $token;
        header("Location: ".$payPalURL);
        exit;
    }

    public static function RedirectToPayPalDG ($token) {
        global $PAYPAL_DG_URL;

        // Redirect to paypal.com here
        $payPalURL = $PAYPAL_DG_URL . $token;
        header("Location: ".$payPalURL);
        exit;
    }



    /*'----------------------------------------------------------------------------------
     * This function will take NVPString and convert it to an Associative Array and it will decode the response.
      * It is usefull to search for a particular key and displaying arrays.
      * @nvpstr is NVPString.
      * @nvpArray is Associative Array.
       ----------------------------------------------------------------------------------
      */
    public static function deformat_nvp($nvpstr) {
        $intial=0;
        $nvpArray = array();

        while(strlen($nvpstr))
        {
            //postion of Key
            $keypos= strpos($nvpstr,'=');
            //position of value
            $valuepos = strpos($nvpstr,'&') ? strpos($nvpstr,'&'): strlen($nvpstr);

            /*getting the Key and Value values and storing in a Associative Array*/
            $keyval=substr($nvpstr,$intial,$keypos);
            $valval=substr($nvpstr,$keypos+1,$valuepos-$keypos-1);
            //decoding the respose
            $nvpArray[urldecode($keyval)] =urldecode( $valval);
            $nvpstr=substr($nvpstr,$valuepos+1,strlen($nvpstr));
        }
        return $nvpArray;
    }
}

?>