<?php

interface OpayGatewayCoreException
{
    const SIGNATURE_PARAMETERS_ARE_NOT_SET                          = '11101';
    const SIGNATURE_OPEN_SSL_NOT_FOUND                              = '11102';
    const SIGNATURE_VERIFICATION_USING_CERTIFICATE_ERROR            = '11103';
    const SIGNATURE_VERIFICATION_USING_CERTIFICATE_READING_ERROR    = '11104';
    const SIGNING_USING_PRIVATE_KEY_BASE_64_ERROR                   = '11105'; 
    const SIGNING_USING_PRIVATE_KEY_ERROR                           = '11106'; 
    const SIGNING_USING_PRIVATE_KEY_READING_ERROR                   = '11107';
    const GATEWAY_REQUEST_BASE64_DECODE_ERROR                       = '11108'; 
     
}

interface OpayGatewayCoreInterface
{

    const SIGNATURE_TYPE_PASSWORD = 'signature_type_password';
    const SIGNATURE_TYPE_RSA      = 'signature_type_rsa';
    
/////
// Funkcijos, skirtos pasirašymo būdui nustatyti siunčiant ir gaunant duomenis iš/į OPAY.
// Pagal atliktus nustatymus bus automatišktai parinktas pasirašymo algoritmas. 
// Visų pirma yra tikrinama ar užtenka parametrų RSA parašo algoritmui, o tik tada slaptažodžio parašui.
//
    
    /**
    * Funkcija, skirta prekybininko privačiam raktui nustatyti. 
    * Reikalinga norint naudotis RSA parašo algoritmu.
    * 
    * @param string $merchantRsaPrivateKey
    */
    public function setMerchantRsaPrivateKey($merchantRsaPrivateKey);

    /**
    * Funkcija, skirta OPAY sertifikatui nustatyti.
    * Reikalinga norint naudotis RSA parašo algoritmu.
    * 
    * @param string $opayCertificate
    */
    public function setOpayCertificate($opayCertificate);
   
    
    /**
    * Funkcija, skirta OPAY išduotam pasirašymo slaptažodžiui nustatyti.
    * Reikalinga norint naudotis parašo slaptažodžiu algoritmu.
    * 
    * @param string $password
    */
    public function setSignaturePassword($password);

    
    /** 
    * Funkcija grąžina koks parašo algoritmas bus naudojamas siunčiant duomenis į OPAY
    * 
    * @return string slef::SIGNATURE_TYPE_PASSWORD or self::SIGNATURE_TYPE_RSA
    */
    public function getTypeOfSignatureIsUsed();
    

    
/////
// Funkcijos, naudojamos siunčiant užklausą apmokėjimui
//
    
    
    /**
    * Prie asociatyvaus parametrų masyvo $parametersArray prideda dar vieną masyvo narį indekso pavadinimu "rsa_signature" arba "password_signature" (priklausomai nuo jūsų pasirinkto pasirašymo būdo),
    * kurio reikšmė yra šio masyvo struktūrą užtvirtinantis skaitmeninis parašas.  
    * Metodas naudoja RSA parašo algoritmą. Kai RSA algoritmu sugeneruojami du tarpusavy susiję raktai. Vienas (privatus) naudojamas pasirašymui,
    * o kitas pateikiamas Opay, kad Opay sistema, galėtų patikrinti ar tikrai duomenis pasirašė privataus rakto savininkas ir ar tie duomenys nepakeisti.
    * Viešasis raktas Opay administracijai pateikiamas sertifikato failo pavidalu, PEM formatu. Šiame faile yra pateikta informacija apie viešojo rakto 
    * savininką (prekybininką) ir pats viešasis raktas.  
    * 
    * @param array  $parametersArray - Masyvas parametrų, kurie bus siunčiami. Masyvo asociatyvus indeksas atspindi parametro pavadinimą, o reikšmė - parametro reikšmę.
    * 
    * @return array                  - Metodas grąžina parametrų masyvą $parametersArray su pridėtu papildomu masyvo nariu, indekso pavadinimu "rsa_signature"
    */
    public function signArrayOfParameters($parametersArray);
    
    
    /**
    * Funkcija, skirta HTML dokumentui išvesti, su jame esančia forma (<form>), kuri sudaryta iš parametrų, 
    * kuriuos ruošiamasi siųsti POST metodu į funkcijai pateiktą https adresą ($url). Ši forma, tik papuolusi į naršyklę,
    * iškarto automatiškai yra patvirtinama (angl. submit). Taip įvyksta vartotojo nukreipimas į pateiktą adresą kartu su duomenimis, siunčiamais POST metodu. 
    * 
    * @param string  $url             - Pilnas HTTPS adresas kartu su nurodytu protokolu. Pvz.: https://gateway.opay.lt/pay/
    * @param array   $parametersArray - Masyvas parametrų, kurie bus siunčiami. Masyvo asociatyvus indeksas atspindi parametro pavadinimą, o reikšmė - parametro reikšmę.
    * @param boolean $sendEncoded     - Jei nurodyta reikšmė TRUE, tada visi parametrai bus suspausti ir siunčiami kaip vienas parametras pavadinimu "encoded". 
    *                                 Plačiau apie tai galite skaityti Opay integravimo specifikacijoje, skiltyje "Duomenų kodavimas, siekiant išvengti galimų iškraipymų".
    * 
    * @return string                  - Metodas grąžina paruošto HTML dokumento tekstą (eilutę), kurį reikės išvesti (print) į naršyklę, tokioje PHP scenarijaus vietoje, kad prieš tai nebūtų išvesta nieko kito.
    */
    public function generateAutoSubmitForm($url, $parametersArray, $sendEncoded = true); 
                                    
    
    /**
    * Funkcija konvertuoja parametrų masyvą į užkoduotą eilutę.
    * Plačiau apie tai galite skaityti Opay integravimo specifikacijoje, skiltyje "Duomenų kodavimas, siekiant išvengti galimų iškraipymų". 
    * Jeigu naudojate funkciją generateAutoSubmitForm su parametro $sendEncoded reikšme 'true', tai funkcijos convertArrayOfParametersToEncodedString papildomai naudoti nebereikia. 
    * 
    * @param array $parametersArray  - Masyvas parametrų, kurie bus siunčiami. Masyvo asociatyvus indeksas atspindi parametro pavadinimą, o reikšmė - parametro reikšmę.
    * 
    * @return string                 - Metodas grąžina sugeneruotą eilutę.
    */
    public function convertArrayOfParametersToEncodedString($parametersArray);
    
    
    
    
/////
// Funkcijos, naudojamos priimant iš OPAY užklausą (pranešimą) apie apmokėjimą
//
    
    
    
    /**
    * Funkcija konvertuoja užkoduotą eilutę į parametrų masyvą.
    * Plačiau apie tai galite skaityti Opay integravimo specifikacijoje, skiltyje "Duomenų kodavimas, siekiant išvengti galimų iškraipymų".
    * 
    * @param string $encodedString   - Užkoduota eilutė.
    * 
    * @return array                  - Metodas grąžina asociatyvų parametrų masyvą.
    */
    public function convertEncodedStringToArrayOfParameters($encodedString);
    
    
    /**
    * Funkcija patikrina ar pasirašyto $parametersArray masyvo informacija yra teisinga ir ar panaudotas teisingas raktas tai informacijai pasirašyti.
    * $parametersArray masyvas laikomas pasirašytu kai turi narį, indekso pavadinimu "rsa_signature" arba "password_signature", priklausomai nuo pasirašymo būdo, kurį nustatėte 
    * su funkcija useRsaSignature() arba usePasswordSignature()
    * 
    * @param array $parametersArray - Masyvas parametrų, gautas HTTP metodu iš Opay. Pvz.: $_POST. Bet kadangi Opay siunčia visus parametrus užkuoduotus vienoje eilutėje,
    *                                 tai teisingas pavyzdys būtų: 
    *                                       $opayGateway = new OpayGateway();
    *                                       $parametersArray = $opayGateway->convertEncodedStringToArrayOfParameters($_POST['encoded']); 
    * 
    * @return boolean               - Metodas grąžina TRUE jei informacija ir panaudotas raktas informacijai pasirašyti yra teisingi
    */  
    public function verifySignature($parametersArray);
    
    

    
}

