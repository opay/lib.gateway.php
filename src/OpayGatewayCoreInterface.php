<?php

namespace Opay\Gateway;

require_once __DIR__ . '/Exception/OpayGatewayCoreException.php';

interface OpayGatewayCoreInterface
{

    const SIGNATURE_TYPE_PASSWORD = 'signature_type_password';
    const SIGNATURE_TYPE_RSA      = 'signature_type_rsa';
    
/////
// Functions for setting the signing method when sending and receiving data from / to OPAY.
// The signing algorithm will be selected automatically according to the settings made. 
// It is first checked whether the parameters are sufficient for the RSA signature algorithm, and then for the signature password.
//
    
    /**
    * Function for setting merchant's private key. 
    * Required to use the RSA signature algorithm.
    * 
    * @param string $merchantRsaPrivateKey
    */
    public function setMerchantRsaPrivateKey($merchantRsaPrivateKey);

    /**
    * Function for setting the OPAY certificate.
    * Required to use the RSA signature algorithm.
    * 
    * @param string $opayCertificate
    */
    public function setOpayCertificate($opayCertificate);
   
    
    /**
    * Function for setting the signature password issued by OPAY.
    * Required to use the signature password algorithm.
    * 
    * @param string $password
    */
    public function setSignaturePassword($password);

    
    /** 
    * The function returns what signature algorithm will be used when sending data to OPAY.
    * 
    * @return string slef::SIGNATURE_TYPE_PASSWORD or self::SIGNATURE_TYPE_RSA
    */
    public function getTypeOfSignatureIsUsed();
    

    
/////
// Functions used to send a payment request
//
    
    
    /**
    * Adds another element to associative parameter array with a key of "rsa_signature" or "password_signature" (depending on the signing method that you choose),
    * the value of which is the digital signature that secures the structure of this array. 
    * If you are using RSA signature algorithm. The RSA algorithm is used to generate two interrelated keys. One (private) used for signing,
    * and the other is provided to OPAY so that the OPAY system can verify that the data was indeed signed by the owner of the private key and that the data has not been altered.
    * The public key is provided to the OPAY administration in the form of a certificate file, in PEM format. This file contains information about the public key
    * the owner (merchant) and the public key itself.
    * 
    * @param array  $parametersArray - An associative array of parameters to be sent. The key of the array represents the parameter name and the value represents the parameter value.
    * 
    * @return array                  - The method returns a associative parameter array $parametersArray with an additional array element added, with a key of "rsa_signature" or "password_signature"
    */
    public function signArrayOfParameters($parametersArray);
    
    
    /**
    * A function for outputting an HTML document with a form (<form> element), 
    * which consists of parameters which are going to be sent by a POST method to the https address ($url) that is provided to the function.
    * This form is submitted automatically immediately. This redirects the user to the provided address along with the data sent by the POST method.
    * 
    * @param string  $url             - The full https address along with the specified protocol. Ex: https://gateway.opay.lt/pay/
    * @param array   $parametersArray - An associative array of parameters to be sent. The key of the array represents the parameter name and the value represents the parameter value.
    * @param boolean $sendEncoded     - If the value is TRUE, then all parameters will be compressed and sent as a single parameter named "encoded". 
    *                                 You can read more about this in the OPAY integration specification.
    * 
    * @return string                  - The method returns the text (string) of the prepared HTML document, which will need to be printed to the browser, in such a a place in the PHP script that nothing else is output before it. 
    */
    public function generateAutoSubmitForm($url, $parametersArray, $sendEncoded = true); 
                                    
    
    /**
    * Function converts an array of parameters to an encoded string.
    * You can read more about this in the OPAY integration specification.
    * If you are using the generateAutoSubmitForm function with the value of the $sendEncoded set to TRUE, you no longer need to use the convertArrayOfParametersToEncodedString function.
    * 
    * @param array $parametersArray  - An associative array of parameters to be sent. The key of the array represents the parameter name and the value represents the parameter value.
    * 
    * @return string                 - The method returns the generated string.
    */
    public function convertArrayOfParametersToEncodedString($parametersArray);
    
    
    
    
/////
// Functions used to receive a request about the payment from OPAY.
//
    
    
    
    /**
    * The function converts the encoded string to an array of parameters.
    * You can read more about this in the OPAY integration specification.
    * 
    * @param string $encodedString   - Encoded string.
    * 
    * @return array                  - The method returns an associative array of parameters.
    */
    public function convertEncodedStringToArrayOfParameters($encodedString);
    
    
    /**
    * The function verifies that the information in the signed $parametersArray array is correct and that the correct key way used to sign that information.
    * The $parametersArray array is considered signed when it has a member with a key of "rsa_signature" or "password_signature",
    * depending on the signing method that you use.
    * 
    * @param array $parametersArray - An array of parameters obtained by a HTTP method from OPAY. Ex: $_POST,
    *                                 but since OPAY sends all parameters encoded in one line, the correct example would be: 
    *                                       $opayGateway = new OpayGateway();
    *                                       $parametersArray = $opayGateway->convertEncodedStringToArrayOfParameters($_POST['encoded']); 
    * 
    * @return boolean               - The method returns TRUE if the information and the key used to sign the information are correct
    */  
    public function verifySignature($parametersArray);
    
    

    
}

