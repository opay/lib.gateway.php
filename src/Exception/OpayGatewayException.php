<?php

namespace Opay\Gateway\Exception;

class OpayGatewayException extends \Exception implements OpayGatewayCoreException, OpayGatewayWebServiceException {}