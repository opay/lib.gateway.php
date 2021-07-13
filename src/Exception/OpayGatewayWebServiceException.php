<?php

namespace Opay\Gateway\Exception;

interface OpayGatewayWebServiceException
{
    const COMMUNICATION_WITH_SERVER_ERROR       = '21101';
    const JSON_DECODING_ERROR                   = '21102';
    const WRONG_JSON_FORMAT                     = '21103';
     
}