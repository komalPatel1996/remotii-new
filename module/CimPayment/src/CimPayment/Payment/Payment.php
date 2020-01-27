<?php

namespace CimPayment\Payment;

class Payment {
    
    /**
     * 
     * @param \Zend\Db\Adapter\Adapter $db
     */
    public function __construct() {
        //  
    }

    //function to send xml request to Api.
    //There is more than one way to send https requests in PHP.
    public function send_xml_request($content) {
        return $this->send_request_via_fsockopen(API_HOST, API_PATH, $content);
    }

    //function to send xml request via fsockopen
    //It is a good idea to check the http status code.
    public function send_request_via_fsockopen($host, $path, $content) {
        $posturl = "ssl://" . $host;
        $header = "Host: $host\r\n";
        $header .= "User-Agent: PHP Script\r\n";
        $header .= "Content-Type: text/xml\r\n";
        $header .= "Content-Length: " . strlen($content) . "\r\n";
        $header .= "Connection: close\r\n\r\n";
        $fp = fsockopen($posturl, 443, $errno, $errstr, 30);
        if (!$fp) {
            $body = false;
        } else {
            error_reporting(E_ERROR);
            fputs($fp, "POST $path  HTTP/1.1\r\n");
            fputs($fp, $header . $content);
            fwrite($fp, $out);
            $response = "";
            while (!feof($fp)) {
                $response = $response . fgets($fp, 128);
            }
            fclose($fp);
            error_reporting(E_ALL ^ E_NOTICE);

            $len = strlen($response);
            $bodypos = strpos($response, "\r\n\r\n");
            if ($bodypos <= 0) {
                $bodypos = strpos($response, "\n\n");
            }
            while ($bodypos < $len && $response[$bodypos] != '<') {
                $bodypos++;
            }
            $body = substr($response, $bodypos);
        }
        return $body;
    }

    //function to send xml request via curl
    public function send_request_via_curl($host, $path, $content) {
        $posturl = "https://" . $host . $path;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $posturl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, Array("Content-Type: text/xml"));
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $content);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $response = curl_exec($ch);
        return $response;
    }

    //function to parse the api response
    //The code uses SimpleXML. http://us.php.net/manual/en/book.simplexml.php 
    //There are also other ways to parse xml in PHP depending on the version and what is installed.
    public function parse_api_response($content) {
        $parsedresponse = simplexml_load_string($content, "SimpleXMLElement", LIBXML_NOWARNING);
        return $parsedresponse;
        
        if ("Ok" != $parsedresponse->messages->resultCode) {
            echo "The operation failed with the following errors:<br>";
            foreach ($parsedresponse->messages->message as $msg) {
                echo "[" . htmlspecialchars($msg->code) . "] " . htmlspecialchars($msg->text) . "<br>";
            }
            echo "<br>";
        }
        return $parsedresponse;
    }

    /**
     * 
     * @return type
     */
    public function MerchantAuthenticationBlock() {
        return
                "<merchantAuthentication>" .
                "<name>" . API_LOGINNAME . "</name>" .
                "<transactionKey>" . API_TRANSACTIONKEY . "</transactionKey>" .
                "</merchantAuthentication>";
    }

}
