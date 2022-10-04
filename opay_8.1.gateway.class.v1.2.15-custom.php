<?php

class OpayCustom {

    private $charIdSymbolsPool = 'WERTYUPASDFGHJKLZXCVBNM2345679';
    private $charIdLength = 7;
    private $channelName;

    public function matchPublicCharId(&$string, $prefix, $pool = '')
    {

        if ($pool == '')
        {
            $pool = preg_quote($this->charIdSymbolsPool);
        }
        else
        {
            $pool = preg_quote($pool);
        }

        if (is_array($prefix))
        {
            $str = '';
            foreach($prefix as $val)
            {
                $str .= preg_quote($val).'|';
            }
            $prefix = substr($str, 0, -1);
        }
        else
        {
            $prefix = preg_quote($prefix);
        }

        if (preg_match('/(8('.$prefix.')(['.$pool.']{3})8(['.$pool.']{'.($this->charIdLength-3).'}))/i', $string, $matches) === 1)
        {
            $string = $matches[1];
            return $matches[3].$matches[4];
        }
        else
        {
            return false;
        }
    }

    public function convertTransactionCharIdToId(&$charId)
    {
        if (!isset($this->convertTransactionCharIdToId[$charId]))
        {
            // $charIdDecoded - charID clean databse format
            if (($charIdDecoded = $this->matchPublicCharId($charId, 'T')) !== false)
            {
                print_r('SELECT transactions_id FROM identifiers WHERE char_id = :charId AND transactions_id > 0');
                /*Loader::loadClassFile('Db');
                $db = Db::getReadDb();
                $transactionId = (int)$db->getCellSql('SELECT transactions_id FROM identifiers WHERE char_id = :charId AND transactions_id > 0', array('charId' => $charIdDecoded));*/
                $transactionId=216;

                if ($transactionId > 0)
                {
                    $this->convertTransactionCharIdToId[$charId] = $transactionId;
                }
                else
                {
                    $this->convertTransactionCharIdToId[$charId] = false;
                }
            }
            else
            {
                $this->convertTransactionCharIdToId[$charId] = false;
            }
        }

        return $this->convertTransactionCharIdToId[$charId];
    }

    public function convertPaymentCharIdToTransactionsAndCustomersInputsId(&$charId)
    {
        if (!isset($this->convertPaymentCharIdToTransactionsAndCustomersInputsId[$charId]))
        {
            // $charIdDecoded - charID clean databse format
            if (($charIdDecoded = $this->matchPublicCharId($charId, 'P')) !== false)
            {
                Loader::loadClassFile('Db');
                $db = Db::getReadDb();
//                $arr = $db->getRow('identifiers', array('payment_transactions_id' => 'transactions_id', 'payment_customers_inputs_id' => 'customers_inputs_id'), array('char_id' => $charIdDecoded));
                $arr = $db->getRowSql('SELECT payment_transactions_id AS transactions_id, payment_customers_inputs_id AS customers_inputs_id FROM identifiers WHERE char_id = :charId AND payment_transactions_id > 0 AND payment_customers_inputs_id > 0', array('charId' => $charIdDecoded));

                if (!empty($arr))
                {
                    $this->convertPaymentCharIdToTransactionsAndCustomersInputsId[$charId] = $arr;
                }
                else
                {
                    $this->convertPaymentCharIdToTransactionsAndCustomersInputsId[$charId] = false;
                }
            }
            else
            {
                $this->convertPaymentCharIdToTransactionsAndCustomersInputsId[$charId] = false;
            }
        }

        return $this->convertPaymentCharIdToTransactionsAndCustomersInputsId[$charId];
    }

    public function checkSignatureNeoFinance($token, $transactionId, $channelName = '')
    {
//        if (GlobalVars::getPisInsertionFromWorkerSenderVerified() == true)
//        {
//            return true;
//        }

        // cia toks rakto tikrinimas gaunasi issaugant pirma patikinimo rezultata, kadangi NEO FInance siuncia batch kruvoj transakcijas.

        $md5 = md5($token);
        if (!isset($this->checkSignatureNeoFinance[$md5]))
        {
            // Paimtas paasikinimas is ten kur sita funkcija naudojama (pis.controller.php):
            // normaliai, mes turetume tikrinti parasa dar pries cikla, bet pries cikla mes neturim transakcijos, pagal kuria gaunam koks raktas turi buti naudojamas
            // checkSignatureNeoFinance pasidarys token'o hasha ir jeigu cia tas pats tokenas, tai grazins jau issaugota rezultata, nesvarbu kad bus kiti transakcijos ID,
            // kadangi NEO Finance skirtingiems merchantu accountams darytu atskirus API requestus, tai mes suprantam, kad siame requeste esantys yrasai bus to paties rakto

            if ($channelName == '')
            {
                // when payment is initiated to our bank account we set the $channelName, otherwise this is default $this->channelName
                $channelName = $this->channelName;
            }

            $keyArr = $this->getKeyByTransactionid('our_key', $channelName, $transactionId); // moq ne sertifikatas, o public key čia guli
            if (!empty($keyArr))
            {
                $jwt = Loader::getInstance('Jwt', '', array($keyArr['key'], 'HS256', 31536000, 20)); // 31536000 yra kiek laiko galioja sitas pranesimas, praejus sitam laikui, gausim invalid signature. jeigu 31536000 yra sekundes, tai yra 365 dienos

                try{
                    $array = $jwt->decode($token);
                    $msg = '';
                }
                catch (Exception $e)
                {
                    $array = array();
                    $msg = $e->getMessage();
                }


                if (!empty($array))
                {
                    $this->checkSignatureNeoFinance[$md5] = true;
                }
                else
                {

                    $error = &Loader::getInstance('Error');
                    $error->set(__CLASS__, __FUNCTION__, 'pis-3', 'Signature Error when checking PIS NEO Finance payment callback. '.$msg);
                    $error->critical();

                    $this->checkSignatureNeoFinance[$md5] = false;
                }
            }
            else
            {
                $error = &Loader::getInstance('Error');
                $error->set(__CLASS__, __FUNCTION__, 'pis-2', 'Signature Error when checking PIS NEO Finance payment callback. Key not found IN DB');
                $error->critical();
                $this->checkSignatureNeoFinance[$md5] = false;
            }
        }

        return $this->checkSignatureNeoFinance[$md5];
    }

    public function getKeyByTransactionid($keyName, $channelName, $transactionId)
    {
        $readDb = &Db::getReadDb();
        $sql = "SELECT w.keys_group_identifier FROM transactions t INNER JOIN websites w ON (t.websites_id = w.id) WHERE t.id = :transactionId";
        $readDb->setQueryName(__CLASS__.'/'.__FUNCTION__);
        $keysGroupIdentifier = $readDb->getCellSql($sql, array('transactionId' => $transactionId));
        if (!empty($keysGroupIdentifier))
        {
            return $this->getKey($keyName, $channelName, $keysGroupIdentifier);
        }
        else
        {
            return false;
        }
    }
}