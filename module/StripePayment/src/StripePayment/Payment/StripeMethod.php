<?php
namespace StripePayment\Payment;

class StripeMethod {

    public $payment;

    /**
     * Stripe api key assigned
     */
    public function __construct() {
        //  Secret key set
        \Stripe::setApiKey(SEC_KEY);
    }

    /**
     * Function createToken defined to create the Stripe Token 
     * 
     * @param type $ccData
     * @return stripeToken object
     */
    public function createToken($ccData) {
        $stripeToken = \Stripe_Token::create(array("card" => array("number" => $ccData['card_number'], 
                                                                   "exp_month" => $ccData['expMonth'], 
                                                                   "exp_year" => $ccData['expYear'], 
                                                                   "cvc" => $ccData['cvv'])));
        return $stripeToken;
    }
    
    /**
     * Method createCustomer Defined to create customer on Stripe
     * 
     * @param type $emailId
     * @param type $userId
     * @return type
     */
    public function createCustomer($customerCreateParams) {
        $customerData = \Stripe_Customer::create($customerCreateParams);
        return $customerData;
    }

    /**
     * Method chargeCustomer Defined
     * 
     * @param type $customerProfileId
     * @param type $data
     * @return type
     */
    public function chargeCustomer($customerId, $amount) {
        $amountToCharge = 100 * $amount;
        
        $chargedObj = \Stripe_Charge::create(array(
                    "amount"   => $amountToCharge, 
                    "currency" => "usd", 
                    "customer" => $customerId)
        );
        return $chargedObj;
    }
    
    /**
     * 
     * @param type $custId
     * @return type
     */
    public function deleteCustomer($custId) {
        $cu = \Stripe_Customer::retrieve($custId);
        $cu->delete();
        return;
    }
    
    /**
     * 
     * @param type $accData
     * @return type
     */
    public function createRecipientsBankAcc($accData) {
        $acc = \Stripe_Recipient::create(array(
                                            "name" => $accData['name_on_bank'], 
                                            "type" => $accData['account_type'], 
                                            "bank_account" => array("country" => "US", 
                                                "routing_number" => $accData['routing_number'], 
                                                "account_number" => $accData['account_number']),
                                            )
                                        );
        return $acc;
    }
    
    /**
     * 
     * @param type $tokenId
     * @param type $data
     * @return boolean
     */
    public function updateRecipientBankAcc($tokenId, $data) {
        $rp = \Stripe_Recipient::retrieve($tokenId);
        $rp->name = $data['contact_fname'] . ' ' . $data['contact_lname'];
        $rp->save();
        return true;
    }

    /**
     * 
     * @param type $rpId
     * @return type
     */
    public function deleteRecipientAcc($rpId) {
        $rp = \Stripe_Recipient::retrieve($rpId);
        $rp->delete();
        return;
    }
    
    /**
     * 
     * @return type
     */
    public function createTransfer($amount, $rpId) {
        $transfer = \Stripe_Transfer::create(array(
            "amount" => $amount,
            "currency" => "usd",
            "recipient" => $rpId)
        );
        return $transfer;
    }
    
    
}
