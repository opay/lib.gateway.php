<?php

require_once dirname(__FILE__).'/opay_8.1.gateway.core.interface.php';
require_once dirname(__FILE__).'/opay_8.1.gateway.webservice.interface.php';

class OpayGatewayException extends Exception implements OpayGatewayCoreException, OpayGatewayWebServiceException{}

class OpayGateway implements OpayGatewayCoreInterface, OpayGatewayWebServiceInterface 
{

    protected $signaturePassword;
    protected $merchantRsaPrivateKey;
    protected $opayCertificate;
    
    public function setMerchantRsaPrivateKey($merchantRsaPrivateKey)
    {
        $this->merchantRsaPrivateKey = trim($merchantRsaPrivateKey);    
    }
    
    public function setOpayCertificate($opayCertificate)
    {
        $this->opayCertificate = trim($opayCertificate);    
    } 
    
    public function setSignaturePassword($password)
    {
        $this->signaturePassword = trim($password);    
    }
    
    public function getTypeOfSignatureIsUsed()
    {
        if (!empty($this->merchantRsaPrivateKey) && !empty($this->opayCertificate))
        {
            if (function_exists('openssl_pkey_get_public'))
            {
                return self::SIGNATURE_TYPE_RSA;
            }
            else
            {
                if (!empty($this->signaturePassword))
                {
                    return self::SIGNATURE_TYPE_PASSWORD;
                }
                else
                {
                    throw new OpayGatewayException('OpenSSL is not available in your server. To use RSA signature type (which is set when using setMerchantRsaPrivateKey() and setOpayCertificate()) install the OpenSSL PHP module. Otherwise use password signature (which is set when using setSignaturePassword()).', OpayGatewayException::SIGNATURE_OPEN_SSL_NOT_FOUND);
                }    
            }
        }
        else if (!empty($this->signaturePassword))
        {
            return self::SIGNATURE_TYPE_PASSWORD;
        }
        else
        {
            throw new OpayGatewayException('Signature parameters are not set. Use functions setMerchantRsaPrivateKey() and setOpayCertificate() to set parameters for RSA signature type, or setSignaturePassword() for password signature type.', OpayGatewayException::SIGNATURE_PARAMETERS_ARE_NOT_SET);
        }
    }
    
    public function signArrayOfParameters($parametersArray)
    {
        // cleaning signature parameters if someone tries to sign already signed array
        if (isset($parametersArray['rsa_signature']))
        {
            unset($parametersArray['rsa_signature']);   
        }
        
        if (isset($parametersArray['password_signature']))
        {
            unset($parametersArray['password_signature']);   
        }
        
        $signatureType = $this->getTypeOfSignatureIsUsed();
        
        $stringToBeSigned = '';
        foreach ($parametersArray as $key => $val)
        {
            // http_build_query strips parameters which have null values, so we do the same here (normally you shouldn't pass parameters with null values here)
            if (!is_null($val))
            {
                // converting boolen to integer (normally you shouldn't pass parameters with boolean values here)
                if (is_bool($val))
                {
                    $val = (int)$val;
                } 
                $stringToBeSigned .= $key.$val;
            }
        }
         
        if ($signatureType == self::SIGNATURE_TYPE_RSA)
        {
            $parametersArray['rsa_signature'] = $this->signStringUsingPrivateKey($stringToBeSigned, $this->merchantRsaPrivateKey);
        }
        else
        {
            $parametersArray['password_signature'] = $this->signStringUsingPassword($stringToBeSigned, $this->signaturePassword);    
        }
    
        return $parametersArray;
    }
    
    public function signStringUsingPrivateKey($stringToBeSigned, $privateKey, $toBase64Encode = true)
    {
        // -- creating private key resource                          
        $pkeyid = openssl_get_privatekey($privateKey); 
        if ($pkeyid !== false)
        {  
            // -- signing the $stringToBeSigned
            if (openssl_sign($stringToBeSigned, $signature, $pkeyid) === true)
            { 
                if ($toBase64Encode == true)
                {
                    // -- encoding to base64
                    if (($signature = base64_encode($signature)) !== false)
                    { 
                        // stripping new lines
                        $signature = preg_replace("/[\r\n\t]*/", "", $signature); 
                        // -- freeing the memory
                        openssl_free_key($pkeyid);
                        return $signature;
                    }
                    else
                    {
                        throw new OpayGatewayException('Could not encode to base64 after signing using a private key.', OpayGatewayException::SIGNING_USING_PRIVATE_KEY_BASE_64_ERROR);
                    }
                }
                else
                {
                    return $signature;    
                }
            }
            else
            {
                throw new OpayGatewayException('Error occurred when signing using private key', OpayGatewayException::SIGNING_USING_PRIVATE_KEY_ERROR);
            }
        }
        else
        {
            throw new OpayGatewayException('Error reading private key', OpayGatewayException::SIGNING_USING_PRIVATE_KEY_READING_ERROR);
        }
    }
    
    public function signStringUsingPassword($stringToBeSigned, $password)
    {
        return md5($stringToBeSigned . $password);
    }
    
    // backward compatibility for mistyped function name
    public function generatetAutoSubmitForm($url, $parametersArray, $sendEncoded = true)
    {
        return $this->generateAutoSubmitForm($url, $parametersArray, $sendEncoded);
    }
    
    public function generateAutoSubmitForm($url, $parametersArray, $sendEncoded = true)
    {
        $language = (!empty($parametersArray['language'])) ? $this->iso369_3ToIso369_1($parametersArray['language']) : 'en';
        $redirectingText = $this->redirectingTextTranslation($language).'...';
        
        $str = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">';
        $str .= '<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="'.$language.'" lang="'.$language.'">';
        $str .= '<head>';
        $str .= '    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />';
        $str .= '    <title>'.$redirectingText.'</title>';
        $str .= "
        <style type=\"text/css\">

            html, body, form, p { margin: 0px; padding: 0px; }
            html, body { height: 100%; background: rgb(242, 242, 242); }
            h1 { font-size: 24px; font-weight: normal; color: rgb(68, 68, 68); margin:0; line-height: 100%; letter-spacing: normal; padding-bottom:24px;}
            p { margin: 0px 0px 15px; }

            .clear { clear: both; display: block; height: 1px; overflow: hidden; margin: -1px 0px 0px; }
            body, div, p{ font-family: Arial, Helvetica, sans-serif; font-size: 14px; line-height: 150%; color: rgb(0, 0, 0); }

            #inprogress{padding: 50px 20px 0 20px;}

            .root { min-height: 100%; position: relative; text-align: center; background: rgb(242, 242, 242); }
            #window { box-shadow: rgba(0, 0, 0, 0.2) 0px 1px 5px; -webkit-box-shadow: rgba(0, 0, 0, 0.2) 0px 1px 5px; width: 600px; margin: 70px auto 0px; }
            .content { position: relative; background: rgb(255, 255, 255); }
            .posrel { position: relative; }

            .progess-bar{
                width:176px;
                height:30px;
                margin:0 auto 30px auto;

            } 
            .pbar{
                width:30px;
                height:30px;
                float:left;
                margin:0 14px 0 0;
                
            }
            #a1{background-color: #eeeadf;}
            #a2{background-color: #d4e0de;}
            #a3{background-color: #eadcdb;}
            #a4{background-color: #d3e0e5;}
            #a5{background-color: #eee6e3;}

            #legal { position: relative; border-top-width: 1px; border-top-style: solid; border-top-color: rgb(234, 234, 234); padding: 30px 40px 15px; }
            #legal p, #legal strong { font-size: 12px; color: rgb(98, 98, 98); }
            #legal p a { font-size: 12px; color: rgb(98, 98, 98); text-decoration: none; border-bottom-width: 1px; border-bottom-style: solid; border-bottom-color: rgb(176, 176, 176); }
            #legal p a:hover { border-bottom-width: 0px; border-bottom-style: solid; border-bottom-color: rgb(176, 176, 176); }

            .cert {overflow:hidden; width:1px; height:1px;}
            
            #legal button{
                background-color: #c3b38b;
                border: 0;
                height:30px;
                color:#fff;
                cursor:pointer; 
                font-weight: bold;
                padding:0 20px 0 20px;
            }
            #legal button span{
                font-size: 12px;
                font-weight:normal;
                color: #5b8b9c;
            }
            
            @media (max-width:599px) {
                .window{
                    width: 320px !important;
                }    
            }  
        </style>        

        ";
        $str .= "
        <script type=\"text/javascript\">
        //<![CDATA[
        
        function fullBar(){
            sq('a1', 'eeeadf', 'c3b38b', 0);         
            sq('a2', 'd4e0de', '618e85', 200);         
            sq('a3', 'eadcdb', 'b37e7a', 400);         
            sq('a4', 'd3e0e5', '5b8b9c', 600);         
        }
        
        function sq(id, passiveColor, activeColor, startAfter) {
            var o = document.getElementById(id).style;
            setTimeout(function(){
                o.backgroundColor = '#'+activeColor;
                setTimeout(function(){
                    o.backgroundColor  = '#'+passiveColor;
                }, 200);
            }, startAfter);    
        }
        
        function getViewportWidth(){
            var w = window, d = document, e = d.documentElement, g = d.getElementsByTagName('body')[0], x = w.innerWidth || e.clientWidth || g.clientWidth;
            return x;    
        }
        
        function autoAlign(){    
            var w = document.getElementById('window');
            var wStyle = w.style;
            
            if (getViewportWidth() <= 599) {
                w.style.width = '320px';    
            } else {
                w.style.width = '600px';
            }

            wStyle.position = 'absolute';
            wStyle.top = '50%'
            wStyle.left = '50%';
            wStyle.marginLeft = (w.offsetWidth/2*-1)+'px';
            wStyle.marginTop = (w.offsetHeight/2*-1)+'px';
        }
        
        
        window.onload = function(){
            autoAlign();
            fullBar();
            setInterval(function(){
                fullBar();    
            }, 900);    
            
            document.redirectForm.submit();
        };
        
        window.onresize = function(){
            autoAlign();
        };
        
        //]]>
        </script>
        ";
        $str .= '</head>';
        $str .= '<body>';

        $str .= '
            <div class="root">
                <div class="clear">&nbsp;</div>
                <div id="window">
                    <div class="posrel">
                        <div class="content">
                            <div id="inprogress">
                                <h1>'.$redirectingText.'</h1>
                                <div class="progess-bar"> 
                                    <div id="a1" class="pbar">&nbsp;</div>
                                    <div id="a2" class="pbar">&nbsp;</div>
                                    <div id="a3" class="pbar">&nbsp;</div>
                                    <div id="a4" class="pbar">&nbsp;</div>
                                </div>
                                <div class="clear">&nbsp;</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        ';
        
        $str .= '<form action="'.htmlspecialchars($url, ENT_COMPAT, 'UTF-8').'" method="post" accept-charset="UTF-8" name="redirectForm">';
        if ($sendEncoded == true)
        {
            $encoded = $this->convertArrayOfParametersToEncodedString($parametersArray);
            $str .= '<input type="hidden" name="encoded" value="'.$encoded.'" />';
        }
        else
        {
            foreach ($parametersArray as $key => $val)
            {
                // http_build_query strips parameters which have null values, so we do the same here (normally you shouldn't pass parameters with null values here)
                if (!is_null($val))
                {
                    // converting boolen to integer (normally you shouldn't pass parameters with boolean values here)
                    if (is_bool($val))
                    {
                        $val = (int)$val;
                    }
                
                    $str .= '<input type="hidden"  name="'.htmlspecialchars($key, ENT_COMPAT, 'UTF-8').'" value="'.htmlspecialchars($val, ENT_COMPAT, 'UTF-8').'" />';
                }
            }
        }

        $str .= '
            </form>
            </body>
            </html>
        ';

        return $str;    
    }
    
    public function convertArrayOfParametersToEncodedString($parametersArray)
    {
        return strtr(base64_encode(http_build_query($parametersArray, '', '&', PHP_QUERY_RFC1738)), array('+' => '-', '/' => '_', '=' => ','));    
    }
    
    public function convertEncodedStringToArrayOfParameters($encodedString)
    {
        $data = strtr($encodedString, array('-' => '+', '_' => '/', ',' => '='));
        
        if (($data = base64_decode($data)) !== false)
        {
            $params = array();
            parse_str($data, $params);
            return $params;
        }
        else
        {
            throw new OpayGatewayException('Base64 decoding error when converting encoded request from gateway.', OpayGatewayException::GATEWAY_REQUEST_BASE64_DECODE_ERROR);
        }    
    }
    
    public function verifySignature($parametersArray)
    {
        $rsaSignature      = '';
        $passwordSignature = '';
        
        if (isset($parametersArray['rsa_signature']))
        {
            $rsaSignature = $parametersArray['rsa_signature']; 
            unset($parametersArray['rsa_signature']);   
        }
        
        if (isset($parametersArray['password_signature']))
        {
            $passwordSignature = $parametersArray['password_signature']; 
            unset($parametersArray['password_signature']);   
        }
        
        $stringToBeVerified = '';
        foreach ($parametersArray as $key => $val)
        {
            $stringToBeVerified .= $key.$val;
        }
    
        if (!empty($rsaSignature) && !empty($this->opayCertificate))
        {
            return $this->verifySignatureUsingCertificate($stringToBeVerified, $rsaSignature, $this->opayCertificate);    
        }
        else if (!empty($passwordSignature) && !empty($this->signaturePassword))
        {
            return $this->verifySignatureUsingPassword($stringToBeVerified, $passwordSignature, $this->signaturePassword);    
        }
        else
        {
            throw new OpayGatewayException('Could not verify a signature. Signature parameters are not set properly. Use functions setMerchantRsaPrivateKey() and setOpayCertificate() to set parameters for RSA signature type, or setSignaturePassword() for password signature type. ', OpayGatewayException::SIGNATURE_PARAMETERS_ARE_NOT_SET);        
        }
    }
    
    public function verifySignatureUsingCertificate($string, $signature, $certificate)
    {  
        // -- extractig the public key 
        $pubkeyid = openssl_pkey_get_public($certificate); 
        if ($pubkeyid !== false)
        {
            // -- verifying the signature 
            $ok = openssl_verify($string, base64_decode($signature), $pubkeyid); 
            openssl_free_key($pubkeyid);
            if ($ok === 1)
            {   
                return true;
            }
            else if ($ok === 0)
            {    
                return false;
            }
            else
            {
                throw new OpayGatewayException('Error reading certificate or extracting a public key from it', OpayGatewayException::SIGNATURE_VERIFICATION_USING_CERTIFICATE_ERROR);
            } 
        }
        else
        {
            throw new OpayGatewayException('Error reading certificate or extracting a public key from it', OpayGatewayException::SIGNATURE_VERIFICATION_USING_CERTIFICATE_READING_ERROR);
        }
    }
    
    public function verifySignatureUsingPassword($string, $signature, $password)
    {
        return ($this->signStringUsingPassword($string, $password) == $signature);     
    }
    
    
    public function webServiceRequest($url, $parametersArray, $sendEncoded = true, $decodeJson = true)
    {
        if ($sendEncoded == true)
        {        
            $encoded = $this->convertArrayOfParametersToEncodedString($parametersArray);
            unset($parametersArray);
            $parametersArray['encoded'] = &$encoded;
        }
        
        $data = $this->sendRequest($url, 'POST', $parametersArray, false, "Content-Type: application/x-www-form-urlencoded\r\n");
        
        if ($data !== false)
        {
            $data = trim($data);
            if ($decodeJson)
            {
                if (version_compare(PHP_VERSION, '5.2.0', '<')) 
                {
                    throw new OpayGatewayException('Cannot decode JSON. Your PHP version ('.PHP_VERSION.') does not have json_decode function. This function is included starting from PHP version 5.2.0. You may pass FALSE to $decodeJson parameter when calling OpayGateway::webServiceRequest method and decode JSON by yourself.', OpayGatewayException::JSON_DECODING_ERROR);
                }
                $data = json_decode($data, true);
                
                if (version_compare(PHP_VERSION, '5.3.0', '>=')) 
                {
                    $jsonLastError = json_last_error();
                    if ($jsonLastError != JSON_ERROR_NONE) 
                    {
                        throw new OpayGatewayException('Could not decode JSON. json_decode() error code is '.$jsonLastError, OpayGatewayException::JSON_DECODING_ERROR);    
                    }
                }
                else if ($data === NULL) 
                {
                    throw new OpayGatewayException('Wrong JSON format or the encoded data is deeper than the recursion limit.', OpayGatewayException::JSON_DECODING_ERROR);
                }
                
                if (!is_array($data))
                {
                    throw new OpayGatewayException('Didn\'t get an array after decoding the JSON returned by the web service', OpayGatewayException::WRONG_JSON_FORMAT);    
                }
            }
            return $data;
        }
        else
        { 
            throw new OpayGatewayException('Could not connect to server.', OpayGatewayException::COMMUNICATION_WITH_SERVER_ERROR);        
        }
 
    }
    
    public function sendRequest($url, $httpMethod, $parametersArray, $keepAlive = false, $optionalHeaders = null, $timeout = 3)
    {
        $httpMethod = strtoupper($httpMethod);
        $content = http_build_query($parametersArray, '', '&', PHP_QUERY_RFC1738);
        $parsedUrlArr = parse_url($url);
        if (!array_key_exists('scheme', $parsedUrlArr))
        {
            throw new OpayGatewayException('URL must contain a name of a protocol e.g. http:// or https://', OpayGatewayException::COMMUNICATION_WITH_SERVER_ERROR);    
        }
        
        if (strtolower($parsedUrlArr['scheme']) == 'https')
        { 
            $w = stream_get_wrappers();
            if (!extension_loaded('openssl') || !in_array('https', $w))
            {
                throw new OpayGatewayException('php_openssl extension have to be enabled and allow_url_fopen must be set On', OpayGatewayException::COMMUNICATION_WITH_SERVER_ERROR);    
            } 
        }
        
        $headers = "User-Agent: OPAY Client\r\n"
                  ."Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8\r\n"
                  ."Accept-Language: en-us,en;q=0.5\r\n"
                  ."Accept-Encoding: identity\r\n" // this client does not support a compression
                  ."Accept-Charset: utf-8;q=0.7,*;q=0.7\r\n";
        
        if ($httpMethod == 'GET')
        {
            $url = strtok($url, '?');
            if (isset($parsedUrlArr['query']))
            {
                parse_str($parsedUrlArr['query'], $queryParamsArr);
                if (!empty($queryParamsArr))
                {
                    $parametersArray = array_merge($queryParamsArr, $parametersArray);    
                }
            }
            $url .= '?'.http_build_query($parametersArray, '', '&', PHP_QUERY_RFC1738);    
        }
        else
        {
            $headers .= "Content-Length: ".strlen($content)."\r\n";
        }

        if (!$keepAlive)
        {
            $headers .= "Connection: Close\r\n";
        }
        else
        {
            $headers .= "Connection: keep-alive\r\n";
        }
           
        if ($optionalHeaders !== null) {
            $headers .= $optionalHeaders;
        }
        
        // removing the last \r\n for possible incompatibilities in some conjunctions of PHP + OpenSSL when file_get_contents returns === false
        $headers = rtrim($headers);
        
        if (ini_get('allow_url_fopen')) {
            $params = array(
                'http' => array(
                    'method'  => $httpMethod == 'GET' ? 'GET' : 'POST',
                    'timeout' => $timeout,
                    'header'  => $headers
                )
            );
            if ($httpMethod != 'GET') {
                $params['http']['content'] = $content;
            }
            $fp = @file_get_contents(
                $url, 
                false, 
                stream_context_create($params)
            );

            return $fp;
        }
        elseif (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            if ($httpMethod != 'GET') {
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
            }
            curl_setopt($ch, CURLOPT_HTTPHEADER, explode("\r\n", $headers));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
            $data = curl_exec($ch);
            curl_close($ch);

            return $data;
        }
        else
        { 
            throw new OpayGatewayException('allow_url_fopen must be set On.', OpayGatewayException::COMMUNICATION_WITH_SERVER_ERROR);        
        }    
    }
    
    protected function iso369_3ToIso369_1($languageCode)
    {
        $arr = array(
            'aar'=>'aa','abk'=>'ab','ave'=>'ae','afr'=>'af','aka'=>'ak','amh'=>'am','arg'=>'an',
            'ara'=>'ar','asm'=>'as','ava'=>'av','aym'=>'ay','aze'=>'az','bak'=>'ba','bel'=>'be',
            'bul'=>'bg','bis'=>'bi','bam'=>'bm','ben'=>'bn','bod'=>'bo','bod'=>'bo','bre'=>'br',
            'bos'=>'bs','cat'=>'ca','che'=>'ce','cha'=>'ch','cos'=>'co','cre'=>'cr','ces'=>'cs',
            'ces'=>'cs','chu'=>'cu','chv'=>'cv','cym'=>'cy','cym'=>'cy','dan'=>'da','deu'=>'de',
            'deu'=>'de','div'=>'dv','dzo'=>'dz','ewe'=>'ee','ell'=>'el','ell'=>'el','eng'=>'en',
            'epo'=>'eo','spa'=>'es','est'=>'et','eus'=>'eu','eus'=>'eu','fas'=>'fa','fas'=>'fa',
            'ful'=>'ff','fin'=>'fi','fij'=>'fj','fao'=>'fo','fra'=>'fr','fra'=>'fr','fry'=>'fy',
            'gle'=>'ga','gla'=>'gd','glg'=>'gl','grn'=>'gn','guj'=>'gu','glv'=>'gv','hau'=>'ha',
            'heb'=>'he','hin'=>'hi','hmo'=>'ho','hrv'=>'hr','hat'=>'ht','hun'=>'hu','hye'=>'hy',
            'hye'=>'hy','her'=>'hz','ina'=>'ia','ind'=>'id','ile'=>'ie','ibo'=>'ig','iii'=>'ii',
            'ipk'=>'ik','ido'=>'io','isl'=>'is','isl'=>'is','ita'=>'it','iku'=>'iu','jpn'=>'ja',
            'jav'=>'jv','kat'=>'ka','kat'=>'ka','kon'=>'kg','kik'=>'ki','kua'=>'kj','kaz'=>'kk',
            'kal'=>'kl','khm'=>'km','kan'=>'kn','kor'=>'ko','kau'=>'kr','kas'=>'ks','kur'=>'ku',
            'kom'=>'kv','cor'=>'kw','kir'=>'ky','lat'=>'la','ltz'=>'lb','lug'=>'lg','lim'=>'li',
            'lin'=>'ln','lao'=>'lo','lit'=>'lt','lub'=>'lu','lav'=>'lv','mlg'=>'mg','mah'=>'mh',
            'mri'=>'mi','mri'=>'mi','mkd'=>'mk','mkd'=>'mk','mal'=>'ml','mon'=>'mn','mar'=>'mr',
            'msa'=>'ms','msa'=>'ms','mlt'=>'mt','mya'=>'my','mya'=>'my','nau'=>'na','nob'=>'nb',
            'nde'=>'nd','nep'=>'ne','ndo'=>'ng','nld'=>'nl','nld'=>'nl','nno'=>'nn','nor'=>'no',
            'nbl'=>'nr','nav'=>'nv','nya'=>'ny','oci'=>'oc','oji'=>'oj','orm'=>'om','ori'=>'or',
            'oss'=>'os','pan'=>'pa','pli'=>'pi','pol'=>'pl','pus'=>'ps','por'=>'pt','que'=>'qu',
            'roh'=>'rm','run'=>'rn','ron'=>'ro','ron'=>'ro','rus'=>'ru','kin'=>'rw','san'=>'sa',
            'srd'=>'sc','snd'=>'sd','sme'=>'se','sag'=>'sg','sin'=>'si','slk'=>'sk','slk'=>'sk',
            'slv'=>'sl','smo'=>'sm','sna'=>'sn','som'=>'so','sqi'=>'sq','sqi'=>'sq','srp'=>'sr',
            'ssw'=>'ss','sot'=>'st','sun'=>'su','swe'=>'sv','swa'=>'sw','tam'=>'ta','tel'=>'te',
            'tgk'=>'tg','tha'=>'th','tir'=>'ti','tuk'=>'tk','tgl'=>'tl','tsn'=>'tn','ton'=>'to',
            'tur'=>'tr','tso'=>'ts','tat'=>'tt','twi'=>'tw','tah'=>'ty','uig'=>'ug','ukr'=>'uk',
            'urd'=>'ur','uzb'=>'uz','ven'=>'ve','vie'=>'vi','vol'=>'vo','wln'=>'wa','wol'=>'wo',
            'xho'=>'xh','yid'=>'yi','yor'=>'yo','zha'=>'za','zho'=>'zh','zho'=>'zh','zul'=>'zu'
        );
        $languageCode = strtolower($languageCode);
        return (isset($arr[$languageCode])) ? $arr[$languageCode] : $languageCode;
    }
    
    protected function redirectingTextTranslation($languageCode)
    {
        $languageCode = $this->iso369_3ToIso369_1($languageCode);
        $arr = array(
            'lt' => 'Vyksta nukreipimas, palaukite',
            'lv' => 'Notiek novirzīšana, lūdzu uzgaidiet',
            'ee' => 'Palun oodake, teid suunatakse edasi',
            'pl' => 'Przekierowanie, proszę czekać',
            'ru' => 'Перенаправляем, подождите, пожалуйста',
            'de' => 'Umleiten, bitte warten Sie',
            'fr' => 'Redirection, S\'il vous plaît attendre',
            'en' => 'Redirecting, please wait'
        ); 
        return (isset($arr[$languageCode])) ? $arr[$languageCode] : $arr['en'];       
    }
}

