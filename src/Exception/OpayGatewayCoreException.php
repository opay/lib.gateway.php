<?php

namespace Opay\Gateway\Exception;

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