<?php

interface OpayGatewayWebServiceException
{
    const COMMUNICATION_WITH_SERVER_ERROR       = '21101';
    const JSON_DECODING_ERROR                   = '21102';
    const WRONG_JSON_FORMAT                     = '21103';
     
}

interface OpayGatewayWebServiceInterface
{
    
/////
//  Funkcijos, naudojamos gauti iš OPAY galimas naudoti reikšmes
// 
    
    /**
    * Funkcija kreipiasi į $url nurodytą web servisą ir grąžina rezultatus,
    * 
    * @param string  $url             - Pilnas HTTPS adresas kartu su nurodytu protokolu. Pvz.: https://gateway.opay.lt/pay/listchannels/
    * @param array   $parametersArray - Masyvas parametrų, kurie bus siunčiami. Masyvo asociatyvus indeksas atspindi parametro pavadinimą, o reikšmė - parametro reikšmę.
    * @param boolean $sendEncoded     - Jei nurodyta reikšmė TRUE, tada visi parametrai bus suspausti ir siunčiami kaip vienas parametras pavadinimu "encoded". 
    *
    * @return array                   - Metodas grąžina masyvą. Kurio struktūra tokia:
    * 
    *                                 array(
    *                                       'response' => array( 
    *                                                       'language' => <kalbos kodas, kokia grąžinamas atsakykmas. pvz "LTL">
    *                                                       'result'   => <rezultatas priklauso nuo to, į kokį web servisą kreipiamasi>
    *                                                       'errors'   => <tuščias masyvas jei klaidų neįvyko> ARBA array(
    *                                                                                                                       '0' => array(
    *                                                                                                                                 'code'      => <klaidos kodas>,
    *                                                                                                                                 'message'   => <klaidos tekstas>,
    *                                                                                                                                 'solutions' => <tuscias masyvas> ARBA masyvas tekstų         
    *                                                                                                                                   )   
    *                                                                                                                    )  
    *                                       )
    *                                 )
    * 
    * Grąžinamo rezultato pavyzdys:
    * 
    *  Array
    *(
    *    [response] => Array
    *    (
    *        [language] => LIT
    *        [result] => Array
    *        (
    *            [banklink] => Array
    *            (
    *                [group_title] => Mokėjimas per internetinę bankininkystę
    *                [channels] => Array
    *                (
    *                    [banklink_swedbank] => Array
    *                    (
    *                        [channel_name] => banklink_swedbank
    *                        [title] => Swedbank bankas
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
    *                [message] => Nežinoma (-os) reikšmė(-ės) dfdgfsg pateikta(-os) parametre [ show_channels ]. 
    *                [solutions] => Array
    *                (
    *                    [0] => Detalesnę informaciją apie [ show_channels ] ir [ hide_channels ] parametrus galite rasti OPAY mokėjimų sistemos specifikacijoje
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