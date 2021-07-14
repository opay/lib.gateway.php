<?php

namespace Opay\Gateway;

require_once __DIR__ . '/Exception/OpayGatewayWebServiceException.php';

interface OpayGatewayWebServiceInterface
{
    
/////
//  Functions used to obtain available values from OPAY
// 
    
    /**
    * Function calls the web service specified in the $url and returns the result.
    * 
    * @param string  $url             - The full https address along with the specified protocol. Ex: https://gateway.opay.lt/pay/listchannels/
    * @param array   $parametersArray - An associative array of parameters to be sent. The key of the array represents the parameter name and the value represents the parameter value.
    * @param boolean $sendEncoded     - If the value is TRUE, then all parameters will be compressed and sent as a single parameter named "encoded".
    *
    * @return array                   - The method returns an array. The structure of which is as follows: 
    * 
    *                                 array(
    *                                       'response' => array( 
    *                                                       'language' => <language code that is used in the response. Ex: "ENG">
    *                                                       'result'   => <the result depends on which web service is called>
    *                                                       'errors'   => <empty array if the are no errors> OR array(
    *                                                                                                                       '0' => array(
    *                                                                                                                                 'code'      => <error code>,
    *                                                                                                                                 'message'   => <error message>,
    *                                                                                                                                 'solutions' => <empty array> OR array of strings     
    *                                                                                                                                   )   
    *                                                                                                                    )  
    *                                       )
    *                                 )
    * 
    * Response example:
    * 
    *  Array
    *(
    *    [response] => Array
    *    (
    *        [language] => ENG
    *        [result] => Array
    *        (
    *            [banklink] => Array
    *            (
    *                [group_title] => Internet banking
    *                [channels] => Array
    *                (
    *                    [banklink_swedbank] => Array
    *                    (
    *                        [channel_name] => banklink_swedbank
    *                        [title] => Swedbank
    *                        [logo_urls] => Array
    *                            (
    *                                [color_33px] => https://widgets.opay.lt/img/banklink_swedbank_color_0x33.png
    *                                [color_49px] => https://widgets.opay.lt/img/banklink_swedbank_color_0x49.png
    *                            )
    *                    )
    *                )
    *            )
    *        )
    *        [errors] => Array
    *        (
    *            [0] => Array
    *            (
    *                [code] => UNKNOWN_SHOW_CHANNEL_NAMES
    *                [message] => Unknown value dfdgfsg specified in parameter [show_channels]
    *                [solutions] => Array
    *                (
    *                    [0] => More detailed information on [show_channels] and [hide_channels] parameters can be found in the OPAY payment system specification
    *                )
    *            )
    *        )
    *    )
    *)
    * 
    */
    
    public function webServiceRequest($url, $parametersArray, $sendEncoded = true);
   
}


?>