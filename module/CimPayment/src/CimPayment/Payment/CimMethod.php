<?php

namespace CimPayment\Payment;

use CimPayment\Payment\Payment as Payment;

class CimMethod {

    public $payment;

    /**
     * Payment class object created
     */
    public function __construct() {
        $this->payment = new Payment();
    }

    /**
     * Methdo profileCreate Defined
     * 
     * @param type $emailId
     * @param type $userId
     * @return type
     */
    public function profileCreate($emailId, $userId = null) {
        //build xml to post
        $content =
                "<?xml version=\"1.0\" encoding=\"utf-8\"?>" .
                "<createCustomerProfileRequest xmlns=\"AnetApi/xml/v1/schema/AnetApiSchema.xsd\">" .
                $this->payment->MerchantAuthenticationBlock() .
                "<profile>" .
                "<merchantCustomerId>" . time() . "</merchantCustomerId>" . // Your own identifier for the customer.
                "<description></description>" .
                "<email>" . $emailId . "</email>" .
                "</profile>" .
                "</createCustomerProfileRequest>";
        $response = $this->payment->send_xml_request($content);
        $parsedresponse = $this->payment->parse_api_response($response);

        if ("Ok" == $parsedresponse->messages->resultCode) {
            $response = htmlspecialchars($parsedresponse->customerProfileId);
        } else {
            $response = '';
        }
        return $response;
    }

    /**
     * Methdo paymentProfileCreate Defined
     * 
     * @param type $customerProfileId
     * @param type $data
     * @return type
     */
    public function paymentProfileCreate($customerProfileId, $userData) {
        //build xml to post
        $content =
                "<?xml version=\"1.0\" encoding=\"utf-8\"?>" .
                "<createCustomerPaymentProfileRequest xmlns=\"AnetApi/xml/v1/schema/AnetApiSchema.xsd\">" .
                $this->payment->MerchantAuthenticationBlock() .
                "<customerProfileId>" . $customerProfileId . "</customerProfileId>" .
                "<paymentProfile>" .
                "<billTo>" .
                "<firstName>" . $userData['fName'] . "</firstName>" .
                "<lastName>" . $userData['lName'] . "</lastName>" .
                "<phoneNumber>" . $userData['phoneNumber'] . "</phoneNumber>" .
                "</billTo>" .
                "<payment>" .
                "<creditCard>" .
                "<cardNumber>" . $userData['card_number'] . "</cardNumber>" .
                "<expirationDate>" . $userData['expYear'] . "-" . $userData['expMonth'] . "</expirationDate>" . // required format for API is YYYY-MM
                "</creditCard>" .
                "</payment>" .
                "</paymentProfile>" .
                "<validationMode>" . VALIDATIONMODE . "</validationMode>" .
                "</createCustomerPaymentProfileRequest>";

        $response = $this->payment->send_xml_request($content);
        $parsedresponse = $this->payment->parse_api_response($response);
        if ("Ok" == $parsedresponse->messages->resultCode) {
            $response = htmlspecialchars($parsedresponse->customerPaymentProfileId);
        } else {
            $response = '';
        }
        return $response;
    }

    /**
     * Method shippingProfileCreate Defined
     * 
     * @param type $customerProfileId
     * @param type $data
     * @return type
     */
    public function shippingProfileCreate($customerProfileId, $userData) {
        //build xml to post
        $content =
                "<?xml version=\"1.0\" encoding=\"utf-8\"?>" .
                "<createCustomerShippingAddressRequest xmlns=\"AnetApi/xml/v1/schema/AnetApiSchema.xsd\">" .
                $this->payment->MerchantAuthenticationBlock() .
                "<customerProfileId>" . $customerProfileId . "</customerProfileId>" .
                "<address>" .
                "<firstName>" . $userData['fName'] . "</firstName>" .
                "<lastName>" . $userData['lName'] . "</lastName>" .
                "<phoneNumber>" . $userData['phoneNumber'] . "</phoneNumber>" .
                "</address>" .
                "</createCustomerShippingAddressRequest>";

        $response = $this->payment->send_xml_request($content);
        $parsedresponse = $this->payment->parse_api_response($response);
        if ("Ok" == $parsedresponse->messages->resultCode) {
            $response = htmlspecialchars($parsedresponse->customerAddressId);
        } else {
            $response = '';
        }
        return $response;
    }

    /**
     * Method capturePayment Defined
     * 
     * @param type $profileId
     * @param type $paymentProfileId
     * @param type $shippingProfileId
     * @param type $data
     */
    public function capturePayment($profileId, $paymentProfileId, $shippingProfileId, $amount) {
        $time = time();
        //build xml to post
        $content =
                "<?xml version=\"1.0\" encoding=\"utf-8\"?>" .
                "<createCustomerProfileTransactionRequest xmlns=\"AnetApi/xml/v1/schema/AnetApiSchema.xsd\">" .
                $this->payment->MerchantAuthenticationBlock() .
                "<transaction>" .
                "<profileTransAuthOnly>" .
                "<amount>" . $amount . "</amount>" . // should include tax, shipping, and everything.
                "<shipping>" .
                "<amount>0.00</amount>" .
                "<name>Free Shipping</name>" .
                "<description>Free UPS Ground shipping. Ships in 5-10 days.</description>" .
                "</shipping>" .
                "<customerProfileId>" . $profileId . "</customerProfileId>" .
                "<customerPaymentProfileId>" . $paymentProfileId . "</customerPaymentProfileId>" .
                "<customerShippingAddressId>" . $shippingProfileId . "</customerShippingAddressId>" .
                "<order>" .
                "<invoiceNumber>INV".$time."</invoiceNumber>" .
                "</order>" .
                "</profileTransAuthOnly>" .
                "</transaction>" .
                "</createCustomerProfileTransactionRequest>";

        $response = $this->payment->send_xml_request($content);
        $parsedresponse = $this->payment->parse_api_response($response);
        

        $transResponse = array();
        if (isset($parsedresponse->directResponse)) {
            $transactionData = htmlspecialchars($parsedresponse->directResponse);

            $directResponseFields = explode(",", $parsedresponse->directResponse);
            $responseCode = $directResponseFields[0]; // 1 = Approved 2 = Declined 3 = Error
            $responseReasonCode = $directResponseFields[2]; // See http://www.authorize.net/support/AIM_guide.pdf
            $responseReasonText = $directResponseFields[3];
            $approvalCode = $directResponseFields[4]; // Authorization code
            $transId = $directResponseFields[6];

            if ("1" == $responseCode) {
                $transResponse['transData'] = $transactionData;
                $transResponse['transId'] = $transId;
                $transResponse['transStatus'] = 1;
            }
        }
        
        if ("Ok" != $parsedresponse->messages->resultCode) {
            foreach ($parsedresponse->messages->message as $msg) {
                $errorData .= "[" . htmlspecialchars($msg->code) . "] " . htmlspecialchars($msg->text);
            }
            $transResponse['transData'] = $errorData;
            $transResponse['transStatus'] = 0;
        }
        
        
        return $transResponse;
    }

    /**
     * Methdo profileDelete Defined
     * 
     * @param type $emailId
     * @param type $userId
     * @return type
     */
    public function profileDelete($profileId) {
        //build xml to post
        $content =
            "<?xml version=\"1.0\" encoding=\"utf-8\"?>" .
            "<deleteCustomerProfileRequest xmlns=\"AnetApi/xml/v1/schema/AnetApiSchema.xsd\">" .
            $this->payment->MerchantAuthenticationBlock() .
            "<customerProfileId>" . $profileId . "</customerProfileId>".
            "</deleteCustomerProfileRequest>";

        $response = $this->payment->send_xml_request($content);
        $parsedresponse = $this->payment->parse_api_response($response);
        if ("Ok" == $parsedresponse->messages->resultCode) {
            return true;
        }
        return false;
    }

}
