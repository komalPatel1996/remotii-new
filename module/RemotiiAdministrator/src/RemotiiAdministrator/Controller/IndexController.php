<?php

/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2013 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace RemotiiAdministrator\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use RemotiiModels\Model\ManageUsers as mUsers;
use RemotiiModels\Model\ServiceProvider as ModelSP;
use RemotiiModels\Model\Client;
use Zend\View\Model\ViewModel;
use Zend\Validator\EmailAddress as emailIdValidation;
// Payment module used
use CimPayment\Payment\CimMethod as cimMethod;
use StripePayment\Payment\StripeMethod as stripeMethod;

// Payment method called
class IndexController extends AbstractActionController {

    private $_modelUsers;
    private $_modelClient;
    private $_modelSP;
    public $cimMethod;
    public $stripeMethod;

    /**
     * Listeners defined attachDefaultListeners() for
     * calling predispatch and postdispatch
     */
    protected function attachDefaultListeners() {
        parent::attachDefaultListeners();
        $events = $this->getEventManager();
        $this->events->attach('dispatch', array(
            $this,
            'preDispatch'
                ), 100);
        // $this->events->attach('dispatch', array($this, 'postDispatch'), -100);
    }

    /**
     * Function preDispatch() defined to get
     */
    public function preDispatch() {
        $db = $this->getServiceLocator()->get('db');
        $this->_modelUsers = new mUsers($db);
        $this->_modelClient = new Client($db);
        $this->_modelSP = new ModelSP($db);

        // Payment object initialised
        $this->cimMethod = new cimMethod ();

        // Stripe Payment object initialised
        $this->stripeMethod = new stripeMethod ();
    }

    public function indexAction() {



        $summery = $this->_modelUsers->getAdminSiteSummary();
        $sps = $this->_modelUsers->getServiceProviderStatistics();
        return new ViewModel(array(
            'userData' => $data,
            'summery' => $summery,
            'spStatistics' => $sps
        ));
    }

    public function savespuser() {
        $custVar = 0;
        $id = $this->params()->fromRoute('id', 0);
        $uid = $this->getRequest()->getQuery('uid');
        $flashMessenger = $this->flashMessenger();
        if ($flashMessenger->hasMessages()) {
            $responseData = $flashMessenger->getMessages();
        }

        $post_array = $this->getRequest()->getPost()->toArray();
        if (!empty($post_array)) {
            // Form validation function called
            $statusData = $this->validateServiceProviderAdminForm($post_array, $uid, 2);
            if ($statusData) {
                return $this->redirect()->toUrl(BASE_URL . "/admin/index/serviceproviderdetail/" . $id . '?ct=users&sval=1&uid=' . $uid);
            }
        }

       
        if (!$uid) {
            // Add case
            if (!empty($post_array)) {

                try {
                    $loggedInUserId = $this->getLoggedInUserId();
                    $this->_modelUsers->createSPUser($post_array, $loggedInUserId, $id);
                    $errorResponse ['1'] = (object) array(
                                'status' => 'success-msg',
                                'message' => 'User created successfully.'
                    );
                    $this->flashMessenger()->clearCurrentMessages();
                    $this->flashMessenger()->addMessage($errorResponse);
                    return $this->redirect()->toUrl(BASE_URL . "/admin/index/serviceproviderdetail/$id?ct=users");
                } catch (\Exception $e) {
                    $errorResponse ['1'] = (object) array(
                                'status' => 'error-msg',
                                'message' => 'Error in add operation.'
                    );
                    $this->flashMessenger()->clearCurrentMessages();
                    $this->flashMessenger()->addMessage($errorResponse);
                }
            }
            $custVar = 1;
        } else {
            // Edit case
            if (!empty($post_array)) {
                try {
                    $loggedInUserId = $this->getLoggedInUserId();
                    $this->_modelUsers->updateSPUser($post_array, $loggedInUserId, $uid);
                    $errorResponse ['1'] = (object) array(
                                'status' => 'success-msg',
                                'message' => 'User updated successfully.'
                    );
                    $this->flashMessenger()->clearCurrentMessages();
                    $this->flashMessenger()->addMessage($errorResponse);
                    return $this->redirect()->toUrl(BASE_URL . "/admin/index/serviceproviderdetail/$id?ct=users");
                } catch (\Exception $e) {
                    $errorResponse ['1'] = (object) array(
                                'status' => 'error-msg',
                                'message' => 'Error in update operation.'
                    );
                    $this->flashMessenger()->clearCurrentMessages();
                    $this->flashMessenger()->addMessage($errorResponse);
                }
            }
            // get user data to edit
            $userData = $this->_modelUsers->getUserById($id);
            $data = $userData;
            $custVar = 1;
        }

        if ($custVar == 0) {
            return $responseData;
        } else {
            return $data;
        }
    }

    /**
     *
     * @param type $postData
     * @return boolean
     */
    public function validateServiceProviderAdminForm($postData, $id, $rid) {
        $status = false;
        $validator = new emailIdValidation ();

        $errorResponse ['0'] = (object) array(
                    'username' => $postData ['userName'],
                    'display_name' => $postData ['displayName'],
                    'fname' => $postData ['fName'],
                    'lname' => $postData ['lName'],
                    'phone' => $postData ['phoneNumber'],
                    'email' => $postData ['emailId'],
                    'street' => $postData ['street'],
                    'city' => $postData ['city'],
                    'state' => $postData ['state'],
                    'country' => $postData ['country'],
                    'zip_code' => $postData ['zip'],
                    'acc_status' => $postData ['acc_status'],
                    'password' => $postData ['password'],
                    'cnfrmPassword' => $postData ['cnfrmPassword']
        );

        if ($postData ['userName'] == '') {
            $errorResponse ['erruserName'] = 1;
            $status = true;
        } else {
            // USer name Duplicasy chk
            $uid = $this->_modelUsers->userNameDuplicasyChk($postData ['userName'], $id);
            if ($uid > 0) {
                $errorResponse ['errUsernameDuplicasy'] = 1;
                $status = true;
            }
        }
        if ($postData ['fName'] == '') {
            $errorResponse ['errfName'] = 1;
            $status = true;
        }
        if ($postData ['lName'] == '') {
            $errorResponse ['errlName'] = 1;
            $status = true;
        }
        if ($postData ['phoneNumber'] == '') {
            $errorResponse ['errphoneNumber'] = 1;
            $status = true;
        }
        if (!$validator->isValid($postData ['emailId'])) {
            $errorResponse ['erremailId'] = 1;
            $status = true;
        } else {
            // Email Duplicasy chk
            $uid = $this->_modelUsers->emailDuplicasyChk($postData ['emailId'], $id, $rid);
            if ($uid > 0) {
                $errorResponse ['errEmailDuplicasy'] = 1;
                $status = true;
            }
        }

        if (!$id || $postData ['password'] != '') {

            $uppercase = preg_match('@[A-Z]@', $postData ['password']);
            // $lowercase = preg_match('@[a-z]@', $postData['password']);
            // $number = preg_match('@[0-9]@', $postData['password']);

            if (!$uppercase || strlen($postData ['password']) < 8) {
                // tell the user something went wrong
                $errorResponse ['errpassword'] = 1;
                $status = true;
            }
            if ($postData ['password'] == '' || strlen($postData ['password']) < 8) {
                $errorResponse ['errpassword'] = 1;
                $status = true;
            }
            if ($postData ['password'] != $postData ['cnfrmPassword']) {
                $errorResponse ['errcnfrmPassword'] = 1;
                $status = true;
            }
        }
        $this->flashMessenger()->addMessage($errorResponse);
        return $status;
    }

    /**
     *
     * @return \Zend\View\Model\ViewModel
     */
    public function manageadminAction() {
        $id = $this->params()->fromRoute('id', 0);
        $flashMessenger = $this->flashMessenger();
        if ($flashMessenger->hasMessages()) {
            $responseData = $flashMessenger->getMessages();
        }

        $post_array = $this->getRequest()->getPost()->toArray();
        if (!empty($post_array)) {
            // Form validation function called
            $statusData = $this->validateManageAdminForm($post_array, $id, 1);
            if ($statusData) {
                if ($id != '') {
                    $redirectId = '/' . $id;
                } else {
                    $redirectId = '';
                }
                return $this->redirect()->toUrl(BASE_URL . "/admin/index/manageadmin" . $redirectId);
            }
        }

        if (!$id) {
            // Add case
            if (!empty($post_array)) {
                try {
                    $loggedInUserId = $this->getLoggedInUserId();
                    $this->_modelUsers->createAdminUser($post_array, $loggedInUserId);
                    $errorResponse ['1'] = (object) array(
                                'status' => 'success-msg',
                                'message' => 'User created successfully.'
                    );
                    $this->flashMessenger()->clearCurrentMessages();
                    $this->flashMessenger()->addMessage($errorResponse);
                    return $this->redirect()->toUrl(BASE_URL . "/admin/index/listadminuser");
                } catch (\Exception $e) {
                    $errorResponse ['1'] = (object) array(
                                'status' => 'error-msg',
                                'message' => 'Error in add operation.'
                    );
                    $this->flashMessenger()->clearCurrentMessages();
                    $this->flashMessenger()->addMessage($errorResponse);
                }
            }
        } else {
            // Edit case
            if (!empty($post_array)) {
                try {
                    $loggedInUserId = $this->getLoggedInUserId();
                    $this->_modelUsers->updateAdminUser($post_array, $loggedInUserId, $id);
                    $errorResponse ['1'] = (object) array(
                                'status' => 'success-msg',
                                'message' => 'User updated successfully.'
                    );
                    $this->flashMessenger()->clearCurrentMessages();
                    $this->flashMessenger()->addMessage($errorResponse);
                    return $this->redirect()->toUrl(BASE_URL . "/admin/index/listadminuser");
                } catch (\Exception $e) {
                    $errorResponse ['1'] = (object) array(
                                'status' => 'error-msg',
                                'message' => 'Error in update operation.'
                    );
                    $this->flashMessenger()->clearCurrentMessages();
                    $this->flashMessenger()->addMessage($errorResponse);
                }
            }
            // get user data to edit
            $userData = $this->_modelUsers->getUserById($id);
            $data = $userData;
        }
        return new ViewModel(array(
            'data' => $data,
            'responseData' => $responseData [0]
        ));
    }

    /**
     *
     * @param type $postData
     * @return boolean
     */
    public function validateManageAdminForm($postData, $id, $rid) {
        $status = false;
        $validator = new emailIdValidation ();

        $errorResponse ['0'] = (object) array(
                    'username' => $postData ['userName'],
                    'display_name' => $postData ['displayName'],
                    'fname' => $postData ['fName'],
                    'lname' => $postData ['lName'],
                    'phone' => $postData ['phoneNumber'],
                    'email' => $postData ['emailId'],
                    'street' => $postData ['street'],
                    'city' => $postData ['city'],
                    'state' => $postData ['state'],
                    'country' => $postData ['country'],
                    'zip_code' => $postData ['zip'],
                    'acc_status' => $postData ['acc_status']
        );

        if ($postData ['userName'] == '') {
            $errorResponse ['erruserName'] = 1;
            $status = true;
        } else {
            // USer name Duplicasy chk
            $uid = $this->_modelUsers->userNameDuplicasyChk($postData ['userName'], $id);
            if ($uid > 0) {
                $errorResponse ['errUsernameDuplicasy'] = 1;
                $status = true;
            }
        }
        if ($postData ['fName'] == '') {
            $errorResponse ['errfName'] = 1;
            $status = true;
        }
        if ($postData ['lName'] == '') {
            $errorResponse ['errlName'] = 1;
            $status = true;
        }
        if ($postData ['phoneNumber'] == '') {
            $errorResponse ['errphoneNumber'] = 1;
            $status = true;
        }
        if (!$validator->isValid($postData ['emailId'])) {
            $errorResponse ['erremailId'] = 1;
            $status = true;
        } else {
            // Email Duplicasy chk
            $uid = $this->_modelUsers->emailDuplicasyChk($postData ['emailId'], $id, $rid);
            if ($uid > 0) {
                $errorResponse ['errEmailDuplicasy'] = 1;
                $status = true;
            }
        }

        if (!$id || $postData ['password'] != '') {

            $uppercase = preg_match('@[A-Z]@', $postData ['password']);
            // $lowercase = preg_match('@[a-z]@', $postData['password']);
            // $number = preg_match('@[0-9]@', $postData['password']);

            if (!$uppercase || strlen($postData ['password']) < 8) {
                // tell the user something went wrong
                $errorResponse ['errpassword'] = 1;
                $status = true;
            }

            if ($postData ['password'] == '' || strlen($postData ['password']) < 8) {
                $errorResponse ['errpassword'] = 1;
                $status = true;
            }
            if ($postData ['password'] != $postData ['cnfrmPassword']) {
                $errorResponse ['errcnfrmPassword'] = 1;
                $status = true;
            }
        }
        $this->flashMessenger()->addMessage($errorResponse);
        return $status;
    }

    /**
     *
     * @return \Zend\View\Model\ViewModel
     */
    public function listadminuserAction() {
        $roleId = 1; // get admin user list
        $userData = $this->_modelUsers->getUserList($roleId);

        $flashMessenger = $this->flashMessenger();
        if ($flashMessenger->hasMessages()) {
            $response = $flashMessenger->getMessages();
        }
        return new ViewModel(array(
            'response' => $response [0],
            'data' => $userData
        ));
    }

    /**
     *
     * @return type
     */
    public function deleteadminuserAction() {
        $id = $this->params()->fromRoute('id', 0);
        if (!$id) {
            return $this->redirect()->toUrl(BASE_URL . "/admin/index/listadminuser");
        }
        $delUser = $this->_modelUsers->deleteUser($id);
        if ($delUser) {
            $response = array(
                'status' => 'success-msg',
                'message' => 'Record deleted successfully.'
            );
            $this->flashMessenger()->addMessage($response);
            return $this->redirect()->toUrl(BASE_URL . "/admin/index/listadminuser");
        }
    }

    /**
     *
     * @return \Zend\View\Model\ViewModel
     */
    public function addserviceproviderAction() {

        $flashMessenger = $this->flashMessenger();
        if ($flashMessenger->hasMessages()) {
            $responseData = $flashMessenger->getMessages();
        }
        $flag = FALSE;
        $post_array = $this->getRequest()->getPost()->toArray();

        if (!empty($post_array)) {
            // Form validation function called
            $statusData = $this->validateServiceProviderForm($post_array, $id, 2);
            if ($statusData) {
                return $this->redirect()->toUrl(BASE_URL . "/admin/index/addserviceprovider");
            }
        }
        if (!empty($post_array)) {
            // Form validation function called
//            $statusData = $this->validateServiceProviderForm($post_array, $id, 2);
//            if ($statusData) {
//                return $this->redirect()->toUrl(BASE_URL . "/admin/index/addserviceprovider");
//            }

            if ($post_array ['shbillingDetails']) {
                // Secret key set
                try {

                    $stripeToken = $this->stripeMethod->createToken($_POST);

                    if ($stripeToken->id != '') {
                        $customerCreateParams = array(
                            'card' => $stripeToken->id,
                            'description' => $_POST ['fName'] . ' ' . $_POST ['lName'],
                            'email' => $_POST ['emailId']
                        );
                        $customerData = $this->stripeMethod->createCustomer($customerCreateParams);
                    }
                } catch (\Exception $e) {
                    
                    if ($e instanceof \Stripe_CardError or $e instanceof \Stripe_InvalidRequestError or $e instanceof \Stripe_AuthenticationError or $e instanceof \Stripe_ApiConnectionError or $e instanceof \Stripe_Error) {
                        // Error occured for
                        $body = $e->getJsonBody();

                        $errorResponse = $body ['error'];
                        // Since it's a decline, Stripe_CardError will be caught
                        $errorResponse ['errorBillingDetails'] = 3;

                        $this->flashMessenger()->addMessage($errorResponse);
                        return $this->redirect()->toUrl(BASE_URL . "/admin/index/addserviceprovider");
                    }
                }

                /*
                 * // Create payment profile $profileId = $this->cimMethod->profileCreate($post_array['emailId']); if($profileId <> '') { $paymentProfileId = $this->cimMethod->paymentProfileCreate($profileId, $post_array); if($paymentProfileId <> '' ) { $shippingAddrsId = $this->cimMethod->shippingProfileCreate($profileId, $post_array); if($shippingAddrsId == ''){ $this->flashMessenger()->addMessage(array('errorBillingDetails' => 3)); return $this->redirect()->toUrl(BASE_URL . "/admin/index/addserviceprovider"); } }else{ $this->flashMessenger()->addMessage(array('errorBillingDetails' => 3)); return $this->redirect()->toUrl(BASE_URL . "/admin/index/addserviceprovider"); } } else { $this->flashMessenger()->addMessage(array('errorBillingDetails' => 3)); return $this->redirect()->toUrl(BASE_URL . "/admin/index/addserviceprovider"); }
                 */
            }

            try {
                //$post_array ['name_on_bank'] = $post_array ['nob_firstname'] . ' ' . $post_array ['nob_lastname'];
                // Creating Service Provider Bank Account Info on Stripe START
                if ($post_array ['allow_end_user_billing'] == 1 && $post_array ['routing_number'] != '' && $post_array ['account_type'] != '' && $post_array ['account_number'] != '' && $post_array ['name_on_bank'] != '') {
                    $spBankInfoStripe = $this->stripeMethod->createRecipientsBankAcc($post_array);
                }
                // Creating Service Provider Bank Account Info on Stripe END
            } catch (\Exception $e) {
                if ($e instanceof \Stripe_CardError or $e instanceof \Stripe_InvalidRequestError or $e instanceof \Stripe_AuthenticationError or $e instanceof \Stripe_ApiConnectionError or $e instanceof \Stripe_Error) {
                    // Error occured for
                    $body = $e->getJsonBody();
                    $errorResponse = $body ['error'];
                    // Since it's a decline, Stripe_CardError will be caught
                    $errorResponse ['errorBillingDetails'] = 3;
                    $this->flashMessenger()->addMessage($errorResponse);

                    return $this->redirect()->toUrl(BASE_URL . "/admin/index/addserviceprovider");
                }
            }
        }

        // Add case
        if (!empty($post_array)) {
            // try {
            $loggedInUserId = $this->getLoggedInUserId();
            $spId = $this->_modelUsers->createServiceProviderUser($post_array, $loggedInUserId);

            // Save Service Provider recevining payment info
            $pId = $this->_modelUsers->saveServiceProviderReceivningPaymentInfo($post_array, $spId, $spBankInfoStripe->id);

            $companyId = $this->_modelUsers->companyId;

            // if( $profileId <> '' && $paymentProfileId <> '' && $shippingAddrsId <> '' ) {
            // 		$this->_modelUsers->savePaymentProfileData($spId, $profileId, $paymentProfileId, $shippingAddrsId);
            // }

            if ($customerData->id != '') {
                $this->_modelUsers->savePaymentProfileData($spId, $customerData->id, $paymentProfileId = null, $shippingAddrsId = null);
            }

            $errorResponse ['1'] = (object) array(
                        'status' => 'success-msg',
                        'message' => 'User created successfully.'
            );
            $this->flashMessenger()->clearCurrentMessages();
            $this->flashMessenger()->addMessage($errorResponse);
            return $this->redirect()->toUrl(BASE_URL . "/admin/index/listserviceprovider");
        }

        return new ViewModel(array(
            'data' => $data,
            'responseData' => $responseData [0],
            'errRes' => $responseData
        ));
    }

    /**
     *
     * @param type $postData
     * @return boolean
     */
    public function validateServiceProviderCompany($postData, $id) {
        $status = false;
        $validator = new emailIdValidation ();
        $errorResponse ['0'] = (object) array(
                    'contact_fname' => $postData ['contact_fname'],
                    'contact_lname' => $postData ['contact_lname'],
                    'contact_phone' => $postData ['contact_phone'],
                    'contact_email' => $postData ['contact_email'],
                    'company_name' => $postData ['company_name'],
                    'contracted_price' => $postData ['contracted_price'],
                    'allow_end_user_billing' => $postData ['allow_end_user_billing'],
                    'service_fee' => $postData ['service_fee'],
                    'end_user_price' => $postData ['end_user_price'],
                    'service_provider_credit' => $postData ['service_provider_credit'],
                    'acc_status' => $postData ['acc_status'],
                    'routing_number' => $postData ['routing_number'],
                    'account_type' => $postData ['account_type'],
                    'account_number' => $postData ['account_number'],
                    'nob_firstname' => $postData ['nob_firstname'],
                    'nob_lastname' => $postData ['nobcredit_lastname'],
                    'old_card_number' => $postData ['old_card_number'],
                    'card_holder' => $postData ['card_holder'],
                    'card_number' => $postData ['shbillingDetails'] == 0 ? $postData ['old_card_number'] : $postData ['card_number'],
                    'expMonth' => $postData ['expMonth'],
                    'expYear' => $postData ['expYear'],
                    'name_on_bank' => $postData ['name_on_bank'],
                    'cardType' => $postData ['cardType'],
                    'account_type' => $postData ['account_type'],
                    'shbillingDetails' => $postData ['shbillingDetails'],
        );

        if ($postData ['company_name'] == '') {
            $errorResponse ['errCompany'] = 1;
            $status = true;
        } else {
            $cData = $this->_modelUsers->companyNameExistsChk($postData ['company_name'], $id);
            if ($cData > 0) {
                $errorResponse ['errCompanyDuplicasy'] = 1;
                $status = true;
            }
        }
        if ($postData ['contact_fname'] == '') {
            $errorResponse ['errfName'] = 1;
            $status = true;
        }
        if ($postData ['contact_lname'] == '') {
            $errorResponse ['errlName'] = 1;
            $status = true;
        }
        if ($postData ['contact_phone'] == '') {
            $errorResponse ['errphoneNumber'] = 1;
            $status = true;
        }
        if ($postData ['contracted_price'] == '') {
            $errorResponse ['err_contracted_price'] = 1;
            $status = true;
        }
        if ($postData ['end_user_price'] == '') {
            $errorResponse ['err_end_user_price'] = 1;
            $status = true;
        }
        if ($postData ['service_provider_credit'] == '') {
            $errorResponse ['err_service_provider_credit'] = 1;
            $status = true;
        }
        if (!$validator->isValid($postData ['contact_email'])) {
            $errorResponse ['erremailId'] = 1;
            $status = true;
        } else {
            // Email Duplicasy chk
            $uid = $this->_modelUsers->emailDuplicasyChkSP($postData ['contact_email'], $id, 2);
            if ($uid > 0) {
                $errorResponse ['errEmailDuplicasy'] = 1;
                $status = true;
            }
        }
        if ($postData ['shbillingDetails']) {
            if (empty($postData ['cardType']) && empty($postData ['card_holder']) && empty($postData ['card_number']) && empty($postData ['cvv']) && empty($postData ['expMonth']) && empty($postData ['expYear'])) {
                $status = false;
            } else {
                if ($postData ['card_holder'] == '') {
                    $errorResponse ['err_card_holder'] = 1;
                    $status = true;
                }
                if ($postData ['card_number'] != '') {
                    if (!$this->checkCreditCard($postData ['card_number'], $postData ['cardType'], $errornumber, $errortext)) {
                        $errorResponse ['errcnCC'] = 1;
                        $status = true;
                    }
                }
            }
        }
        //      validations by aditya
        $postData['contracted_price'] = ($postData['contracted_price'] == "" ? 0 : $postData['contracted_price']);
        if ($postData['contracted_price'] >= 0) {
            if (!is_numeric($postData['contracted_price'])) {
                $errorResponse ['contracted_price'] = 1;
                $status = true;
            }
        } else {
            $errorResponse ['contracted_price'] = 2;
            $status = true;
        }

        $postData['end_user_price'] = ($postData['end_user_price'] == "" ? 0 : $postData['end_user_price']);
        if ($postData['end_user_price'] >= 0) {
            if (!is_numeric($postData['end_user_price'])) {
                $errorResponse ['end_user_price'] = 1;
                $status = true;
            }
        } else {
            $errorResponse ['end_user_price'] = 2;
            $status = true;
        }
//      service_provider_credit

        $postData['service_provider_credit'] = ($postData['service_provider_credit'] == "" ? 0 : $postData['service_provider_credit']);
        if ($postData['service_provider_credit'] >= 0) {
            if (!is_numeric($postData['service_provider_credit'])) {
                $errorResponse ['service_provider_credit'] = 1;
                $status = true;
            }
        } else {
            $errorResponse ['service_provider_credit'] = 2;
            $status = true;
        }


        $this->flashMessenger()->addMessage($errorResponse);

        return $status;
    }

    /**
     *
     * @param type $postData
     * @return boolean
     */
    public function validateServiceProviderForm($postData, $id, $rid) {
        $status = false;
        $validator = new emailIdValidation ();

        $errorResponse ['0'] = (object) array(
                    'fname' => $postData ['fName'],
                    'lname' => $postData ['lName'],
                    'phone' => $postData ['phoneNumber'],
                    'email' => $postData ['emailId'],
                    'street' => $postData ['street'],
                    'city' => $postData ['city'],
                    'state' => $postData ['state'],
                    'country' => $postData ['country'],
                    'zip_code' => $postData ['zip'],
                    'company' => $postData ['company'],
                    'shbillingDetails' => $postData ['shbillingDetails'],
                    'contracted_price' => $postData ['contracted_price'],
                    'allow_end_user_billing' => $postData ['allow_end_user_billing'],
                    'service_fee' => $postData ['service_fee'],
                    'end_user_price' => $postData ['end_user_price'],
                    'service_provider_credit' => $postData ['service_provider_credit'],
                    'routing_number' => $postData ['routing_number'],
                    'account_type' => $postData ['account_type'],
                    'account_number' => $postData ['account_number'],
                    'nob_firstname' => $postData ['nob_firstname'],
                    'nob_lastname' => $postData ['nob_lastname'],
                    'card_holder' => $postData ['card_holder'],
                    'card_number' => $postData ['card_number'],
                    'expMonth' => $postData ['expMonth'],
                    'expYear' => $postData ['expYear'],
                    'name_on_bank' => $postData ['name_on_bank'],
                    'cardType' => $postData ['cardType'],
                    'account_type' => $postData ['account_type'],
        );

        if ($postData ['contracted_price'] == '') {
            $errorResponse ['err_contracted_price'] = 1;
            $status = true;
        }

        if ($postData ['end_user_price'] == '') {
            $errorResponse ['err_end_user_price'] = 1;
            $status = true;
        }

        if ($postData ['company'] == '') {
            $errorResponse ['company'] = 1;
            $status = true;
        } else {
            if ($this->_modelUsers->companyNameExists($postData ['company'])) {
                $errorResponse ['company'] = 2;
                $status = true;
            }
        }

        if ($postData ['fName'] == '') {
            $errorResponse ['errfName'] = 1;
            $status = true;
        }

        if ($postData ['lName'] == '') {
            $errorResponse ['errlName'] = 1;
            $status = true;
        }
        if ($postData ['phoneNumber'] == '') {
            $errorResponse ['errphoneNumber'] = 1;
            $status = true;
        }
        if (!$validator->isValid($postData ['emailId'])) {
            $errorResponse ['erremailId'] = 1;
            $status = true;
        } else {
            // Email Duplicasy chk
            $uid = $this->_modelUsers->emailDuplicasyChkSP($postData ['emailId'], $id, $rid);
            if ($uid > 0) {
                $errorResponse ['errEmailDuplicasy'] = 1;
                $status = true;
            }
        }

        if ($postData ['shbillingDetails']) {
            if ($postData ['card_holder'] == '') {
                $errorResponse ['err_card_holder'] = 1;
                $status = true;
            }

            if ($postData ['card_number'] != '') {
                if (!$this->checkCreditCard($postData ['card_number'], $postData ['cardType'], $errornumber, $errortext)) {
                    $errorResponse ['errcnCC'] = 1;
                    $status = true;
                }
            }
        }

        if ($postData ['card_number'] != '') {
            if (!$this->checkCreditCard($postData ['card_number'], $postData ['cardType'], $errornumber, $errortext)) {
                $errorResponse ['errcnCC'] = 1;
                $status = true;
            }
        }

//       validations by aditya
        $postData['contracted_price'] = ($postData['contracted_price'] == "" ? 0 : $postData['contracted_price']);
        if ($postData['contracted_price'] >= 0) {
            if (!is_numeric($postData['contracted_price'])) {
                $errorResponse ['contracted_price'] = 1;
                $status = true;
            }
        } else {
            $errorResponse ['contracted_price'] = 2;
            $status = true;
        }

        $postData['end_user_price'] = ($postData['end_user_price'] == "" ? 0 : $postData['end_user_price']);
        if ($postData['end_user_price'] >= 0) {
            if (!is_numeric($postData['end_user_price'])) {
                $errorResponse ['end_user_price'] = 1;
                $status = true;
            }
        } else {
            $errorResponse ['end_user_price'] = 2;
            $status = true;
        }
//      service_provider_credit

        $postData['service_provider_credit'] = ($postData['service_provider_credit'] == "" ? 0 : $postData['service_provider_credit']);
        if ($postData['service_provider_credit'] >= 0) {
            if (!is_numeric($postData['service_provider_credit'])) {
                $errorResponse ['service_provider_credit'] = 1;
                $status = true;
            }
        } else {
            $errorResponse ['service_provider_credit'] = 2;
            $status = true;
        }


        $this->flashMessenger()->addMessage($errorResponse);
        return $status;
    }

    /**
     *
     * @return \Zend\View\Model\ViewModel
     */
    public function listserviceproviderAction() {
        $sf = $this->getRequest()->getQuery('sf');
        $userData = $this->_modelUsers->getServiceProviderList($sf);
//        echo "<pre>";
//        print_r($userData);
//        echo "<pre>";
//        die();
        $flashMessenger = $this->flashMessenger();
        if ($flashMessenger->hasMessages()) {
            $response = $flashMessenger->getMessages();
        }

        return new ViewModel(array(
            'response' => $response [0],
            'data' => $userData
        ));
    }

    /**
     *
     * @return \Zend\View\Model\ViewModel
     */
    public function serviceproviderdetailAction() {
        $id = $this->params()->fromRoute('id', 0);
        $uid = $this->getRequest()->getQuery('uid');
        $service_provider_credit = $this->_modelUsers->getSPCredits($id);
        // catch the set messages
        $flashMessenger = $this->flashMessenger();
        if ($flashMessenger->hasMessages()) {
            $flashMessages = $flashMessenger->getMessages();
            // _pre($flashMessages);
        }

        // Function call to get the service provider information from DB
        $userData = $this->_modelUsers->getServiceProviderInfo($id);
        $spUsers = $this->_modelUsers->getSPUsers($id);

        $searchRemotii = $this->getRequest()->getQuery('search');
        if ($searchRemotii != '') {
            $spRemotiis = $this->_modelUsers->searchSPRemotiis($id, $searchRemotii);
        } else {
            $spRemotiis = $this->_modelUsers->getSPRemotiis($id);
        }

        // Get service provider payment
        $spPayment = $this->_modelUsers->getSPPayment($id);
        $post_array = $this->getRequest()->getPost()->toArray();

        if (isset($post_array ['filterRemotii'])) {
            if ($post_array ['remotiiId'] != '') {
                return $this->redirect()->toUrl(BASE_URL . "/admin/index/serviceproviderdetail/" . $id . "?ct=remotii&search=" . $post_array ['remotiiId']);
            }
        }

        if (isset($post_array ['submit_user'])) {
            $spUserDetails = $this->savespuser();
        } else {
            if ($uid && empty($flashMessages)) {
                $spUserDetails = $this->_modelUsers->getUserById($uid);
                if ($spUserDetails) {
                    $spUserDetails = $spUserDetails [0];
                }
                // _pre($spUserDetails);
            }
        }

        if (isset($post_array ['submit']) && $post_array ['submit'] == 'Update') {
            // Update service provider info
            $this->updateServiceProviderInfo($post_array, $id, $userData);

            if (isset($post_array ['billAccount']) && $post_array ['billAccount'] == 'Bill Account') {
                // Capture service provider payment and save payment stat
                $this->captureUserPayment($post_array, $id);
            }
        }

        if (isset($post_array ['addRemotii'])) {
            // die('RemotiiFrm SUBMIT');
            // Capture service provider payment and save payment stat
            $this->addServiceProviderRemotii($post_array, $id);
        }

        $summery = $this->_modelUsers->getServiceProviderSummary($id);
        $delinquentClients = $this->_modelUsers->getSPDelinquentClients($id);

        if (empty($flashMessages[1]['stripeErrors'])) {
            $sprpinfo = $this->_modelUsers->getServiceProviderReceivningPaymentInfo($id);
        } else {
            $sprpinfo = $flashMessages[0];
        }

        $isPost = $this->getRequest()->isPost();
        $ct = $this->getRequest()->getQuery('ct');
        // _pre($spUserDetails);
        return new ViewModel(array(
            'sval' => $this->getRequest()->getQuery('sval'),
            'uid' => $this->getRequest()->getQuery('uid'),
            'isPost' => $isPost,
            'currentTab' => $ct,
            'responseData' => $flashMessages [0],
            'data' => $userData,
            'accountStatistics' => array(
                'summery' => $summery,
                'delinquentClients' => $delinquentClients
            ),
            'spUsers' => $spUsers,
            'spRemotii' => $spRemotiis,
            'spUserDetails' => $spUserDetails,
            'spPayment' => $spPayment,
            'searchRemotii' => $searchRemotii,
            'errors' => $errors,
            'sprpinfo' => $sprpinfo[0],
            'credit' => $service_provider_credit,
            'flashMessages' => $flashMessages
        ));
    }

    /**
     *
     * @param type $postData
     */
    public function updateServiceProviderInfo($postData, $id, $userData) {
        $statusData = $this->validateServiceProviderCompany($postData, $id);
        if ($statusData) {
            return $this->redirect()->toUrl(BASE_URL . "/admin/index/serviceproviderdetail/" . $id . '?ct=accinfo');
        }

        $loggedInUserId = $this->getLoggedInUserId();

        if ($postData['shbillingDetails'] == '1') {
            if ($postData ['card_number'] != '' && $postData ['expMonth'] != '' && $postData ['expYear'] != '') {

                try {
                    $stripeToken = $this->stripeMethod->createToken($postData);
                } catch (\Exception $e) {
                    $message = $e->getMessage();
                    $this->flashMessenger()->addMessage(array('stripeErrors' => array('status' => 'error', 'message' => $message)));
                    return $this->redirect()->toUrl(BASE_URL . "/admin/index/serviceproviderdetail/" . $id . '?ct=accinfo');
                    $message = $e->getMessage();
                }

                if ($stripeToken->id != '') {
                    if ($userData [0]->authorizenet_profile_id && !is_null($userData [0]->authorizenet_profile_id) && !empty($userData [0]->authorizenet_profile_id)) {

                        try {
                            $updateStripeCustomer = $this->stripeMethod->deleteCustomer($userData [0]->authorizenet_profile_id);
                        } catch (\Exception $e) {
                            $message = $e->getMessage();
                        }
                    }

                    $customerCreateParams = array(
                        'card' => $stripeToken->id,
                        'description' => $postData ['contact_fname'] . ' ' . $postData ['contact_lname'],
                        'email' => $postData ['contact_email']
                    );

                    try {
                        $customerData = $this->stripeMethod->createCustomer($customerCreateParams);
                    } catch (\Exception $e) {
                        $message = $e->getMessage();
                        $this->flashMessenger()->addMessage(array('stripeErrors' => array('status' => 'error', 'message' => $message)));
                        return $this->redirect()->toUrl(BASE_URL . "/admin/index/serviceproviderdetail/" . $id . '?ct=accinfo');
                        $message = $e->getMessage();
                    }
                }
                $this->_modelUsers->savePaymentProfileData($id, $customerData->id, $paymentProfileId = null, $shippingAddrsId = null);

                /*
                 * $authPaymentProfile = $this->_modelUsers->getAuthPaymentProfileDetails($id); $delProfileId = $authPaymentProfile->authorizenet_profile_id; if ($delProfileId > 0) { // First Delete the profile in case user need to update the CC info $delStatus = $this->cimMethod->profileDelete($delProfileId); } // Create payment profile $profileId = $this->cimMethod->profileCreate($postData['contact_email'], $id); if ($profileId <> '') { $paymentProfileData = array( 'fName' => $postData['contact_fname'], 'lName' => $postData['contact_lname'], 'phoneNumber' => $postData['contact_phone'], 'card_number' => $postData['card_number'], 'expYear' => $postData['expYear'], 'expMonth' => $postData['expMonth']); $paymentProfileId = $this->cimMethod->paymentProfileCreate($profileId, $paymentProfileData); /// $paymentProfileShippingData = array('fName' => $postData['contact_fname'], 'lName' => $postData['contact_lname'], 'phoneNumber' => $postData['contact_phone']); $shippingAddrsId = $this->cimMethod->shippingProfileCreate($profileId, $paymentProfileShippingData); } if ($profileId <> '' && $paymentProfileId <> '' && $shippingAddrsId <> '') { $this->_modelUsers->savePaymentProfileData($id, $profileId, $paymentProfileId, $shippingAddrsId); }
                 */
            } else {

                if ($userData [0]->authorizenet_profile_id && !is_null($userData [0]->authorizenet_profile_id) && !empty($userData [0]->authorizenet_profile_id)) {
                    try {
                        $updateStripeCustomer = $this->stripeMethod->deleteCustomer($userData [0]->authorizenet_profile_id);
                    } catch (\Exception $e) {
                        $message = $e->getMessage();
                    }
                }
            }
        }


        // Update Service Provider Bank Details on Stripe START
        $tokStripeUpdate = $this->_modelUsers->getServiceProviderReceivningPaymentInfo($id);

        /*
         * Creating recipent account for service provider
         */
        //$post_array ['name_on_bank'] = $post_array ['nob_firstname'] . ' ' . $post_array ['nob_lastname'];
        if ($postData ['allow_end_user_billing'] == 1 && $postData ['routing_number'] != '' && $postData ['account_type'] != '' && $postData ['account_number'] != '' && $postData ['name_on_bank'] != '') {

            if ($tokStripeUpdate [0]->stripe_acc_id != '') {

                try {
                    $this->stripeMethod->deleteRecipientAcc($tokStripeUpdate [0]->stripe_acc_id);
                } catch (\Exception $e) {

                }
            }

            try {
                $spBankInfoStripe = $this->stripeMethod->createRecipientsBankAcc($postData);
            } catch (\Exception $e) {
                $message = $e->getMessage();
                //$this->flashMessenger ()->clearCurrentMessages ();
                $this->flashMessenger()->addMessage(array('stripeErrors' => array('status' => 'error', 'message' => $message)));
                return $this->redirect()->toUrl(BASE_URL . "/admin/index/serviceproviderdetail/" . $id . "?ct=accinfo");
            }
        }

        // Creating Service Provider Bank Account Info on Stripe END
        // $this->stripeMethod->updateRecipientBankAcc($tokStripeUpdate[0]->stripe_acc_id, $postData);
        // Update Service Provider Bank Details on Stripe END

        if ($tokStripeUpdate [0]->stripe_acc_id != '') {
            // Upadte Service Provider Receivning Payment Info
            $this->_modelUsers->updateServiceProviderReceivningPaymentInfo($postData, $id, $spBankInfoStripe->id);
        } else {
            $this->_modelUsers->saveServiceProviderReceivningPaymentInfo($postData, $id, $spBankInfoStripe->id);
        }

        $this->_modelUsers->updateServiceProviderCompany($postData, $loggedInUserId, $id);

        $errorResponse ['1'] = (object) array(
                    'status' => 'success-msg',
                    'message' => 'Service provider info updated successfully.'
        );
        $this->flashMessenger()->clearCurrentMessages();
        $this->flashMessenger()->addMessage($errorResponse);
        return $this->redirect()->toUrl(BASE_URL . "/admin/index/serviceproviderdetail/" . $id . '?ct=accinfo');
    }

    /**
     *
     * @return \Zend\View\Model\ViewModel
     */
    public function listclientAction() {
        $sf = $this->getRequest()->getQuery('sf');
        $userData = $this->_modelUsers->getEndUsers($sf);

        $flashMessenger = $this->flashMessenger();
        if ($flashMessenger->hasMessages()) {
            $response = $flashMessenger->getMessages();
        }
        return new ViewModel(array(
            'response' => $response [0],
            'data' => $userData
        ));
    }

    /**
     *
     * @return type
     */
    public function deleteclientAction() {
        $id = $this->params()->fromRoute('id', 0);
        if (!$id) {
            return $this->redirect()->toUrl(BASE_URL . "/admin/index/listclient");
        }
        $delUser = $this->_modelUsers->deleteUser($id);
        if ($delUser) {
            $response = array(
                'status' => 'successmsg',
                'message' => 'Record deleted successfully.'
            );
            $this->flashMessenger()->addMessage($response);
            return $this->redirect()->toUrl(BASE_URL . "/admin/index/listclient");
        }
    }

    public function deletespuserAction() {
        $id = $this->params()->fromRoute('id', 0);
        $spId = $this->params()->fromRoute('id2', 0);
        $delUser = $this->_modelUsers->deleteSPUser($id);
        if ($delUser) {
            $response = array(
                'status' => 'success-msg',
                'message' => 'Record deleted successfully.'
            );
            $this->flashMessenger()->clearCurrentMessages();
            $this->flashMessenger()->addMessage($response);
            return $this->redirect()->toUrl(BASE_URL . "/admin/index/serviceproviderdetail/$spId");
        }
    }

    /**
     *
     * @return \Zend\View\Model\ViewModel
     */
    public function clientinfoAction() {
        $id = $this->params()->fromRoute('id', 0);

        $flashMessenger = $this->flashMessenger();
        if ($flashMessenger->hasMessages()) {
            $responseData = $flashMessenger->getMessages();
        }

        $post_array = $this->getRequest()->getPost()->toArray();
        if (!empty($post_array)) {
            // Form validation function called
            $statusData = $this->validateManageAdminForm($post_array, $id, 3);
            if ($statusData) {
                if ($id != '') {
                    $redirectId = '/' . $id;
                } else {
                    $redirectId = '';
                }
                return $this->redirect()->toUrl(BASE_URL . "/admin/index/clientinfo" . $redirectId);
            }
        }

        // Edit case
        if (!empty($post_array)) {
            try {
                $loggedInUserId = $this->getLoggedInUserId();
                $this->_modelUsers->updateEndUser($post_array, $loggedInUserId, $id);
                $errorResponse ['1'] = (object) array(
                            'status' => 'success-msg',
                            'message' => 'User updated successfully.'
                );
                $this->flashMessenger()->clearCurrentMessages();
                $this->flashMessenger()->addMessage($errorResponse);
                return $this->redirect()->toUrl(BASE_URL . "/admin/index/clientinfo/" . $id);
            } catch (\Exception $e) {
                $errorResponse ['1'] = (object) array(
                            'status' => 'error-msg',
                            'message' => 'Error in update operation.'
                );
                $this->flashMessenger()->clearCurrentMessages();
                $this->flashMessenger()->addMessage($errorResponse);
            }
        }
        // get user data to edit
        $userData = $this->_modelUsers->getUserById($id);
        $data = $userData;

        // get user Remotii to list
        $userRemotiiData = $this->_modelUsers->getEndUserRemotii($id);

        // $remotiisPrice = $this->_modelUsers->getClientRemtiisTotalPrice($id);
        // _pr($userRemotiiData);
        // Generating array as required
        $tmpData = array();
        foreach ($userRemotiiData as $urData) {
            if (in_array($urData ['service_provider_id'], $urData)) {
                $tmpData [$urData ['cname']] [0] = $urData ['enduPrice'];
                $tmpData [$urData ['cname']] [1] = $urData ['acc_status'];
                $tmpData [$urData ['cname']] [] = $urData;
            }
        }

        return new ViewModel(array(
            'data' => $data,
            'remotiisPrice' => $remotiisPrice,
            'responseData' => $responseData [0],
            'userRemotiiData' => $tmpData
        ));
    }

    /**
     * Credit Card validation function defined
     *
     * @param type $cardnumber
     * @param type $cardname
     * @param type $errornumber
     * @param type $errortext
     * @return boolean
     */
    public function checkCreditCard($cardnumber, $cardname, &$errornumber, &$errortext) {

        // Define the cards we support. You may add additional card types.
        // Name: As in the selection box of the form - must be same as user's
        // Length: List of possible valid lengths of the card number for the card
        // prefixes: List of possible prefixes for the card
        // checkdigit Boolean to say whether there is a check digit
        // Don't forget - all but the last array definition needs a comma separator!
        $cards = array(
            array(
                'name' => 'American Express',
                'length' => '15',
                'prefixes' => '34,37',
                'checkdigit' => true
            ),
            array(
                'name' => 'Diners Club Carte Blanche',
                'length' => '14',
                'prefixes' => '300,301,302,303,304,305',
                'checkdigit' => true
            ),
            array(
                'name' => 'Diners Club',
                'length' => '14,16',
                'prefixes' => '36,38,54,55',
                'checkdigit' => true
            ),
            array(
                'name' => 'Discover',
                'length' => '16',
                'prefixes' => '6011,622,64,65',
                'checkdigit' => true
            ),
            array(
                'name' => 'Diners Club Enroute',
                'length' => '15',
                'prefixes' => '2014,2149',
                'checkdigit' => true
            ),
            array(
                'name' => 'JCB',
                'length' => '16',
                'prefixes' => '35',
                'checkdigit' => true
            ),
            array(
                'name' => 'Maestro',
                'length' => '12,13,14,15,16,18,19',
                'prefixes' => '5018,5020,5038,6304,6759,6761,6762,6763',
                'checkdigit' => true
            ),
            array(
                'name' => 'MasterCard',
                'length' => '16',
                'prefixes' => '51,52,53,54,55',
                'checkdigit' => true
            ),
            array(
                'name' => 'Solo',
                'length' => '16,18,19',
                'prefixes' => '6334,6767',
                'checkdigit' => true
            ),
            array(
                'name' => 'Switch',
                'length' => '16,18,19',
                'prefixes' => '4903,4905,4911,4936,564182,633110,6333,6759',
                'checkdigit' => true
            ),
            array(
                'name' => 'VISA',
                'length' => '16',
                'prefixes' => '4',
                'checkdigit' => true
            ),
            array(
                'name' => 'VISA Electron',
                'length' => '16',
                'prefixes' => '417500,4917,4913,4508,4844',
                'checkdigit' => true
            ),
            array(
                'name' => 'LaserCard',
                'length' => '16,17,18,19',
                'prefixes' => '6304,6706,6771,6709',
                'checkdigit' => true
            )
        );

        $ccErrorNo = 0;

        $ccErrors [0] = "Unknown card type";
        $ccErrors [1] = "No card number provided";
        $ccErrors [2] = "Credit card number has invalid format";
        $ccErrors [3] = "Credit card number is invalid";
        $ccErrors [4] = "Credit card number is wrong length";

        // Establish card type
        $cardType = - 1;
        for ($i = 0; $i < sizeof($cards); $i++) {

            // See if it is this card (ignoring the case of the string)
            if (strtolower($cardname) == strtolower($cards [$i] ['name'])) {
                $cardType = $i;
                break;
            }
        }

        // If card type not found, report an error
        if ($cardType == - 1) {
            $errornumber = 0;
            $errortext = $ccErrors [$errornumber];
            return false;
        }

        // Ensure that the user has provided a credit card number
        if (strlen($cardnumber) == 0) {
            $errornumber = 1;
            $errortext = $ccErrors [$errornumber];
            return false;
        }

        // Remove any spaces from the credit card number
        $cardNo = str_replace(' ', '', $cardnumber);

        // Check that the number is numeric and of the right sort of length.
        if (!preg_match("/^[0-9]{13,19}$/", $cardNo)) {
            $errornumber = 2;
            $errortext = $ccErrors [$errornumber];
            return false;
        }

        // Now check the modulus 10 check digit - if required
        if ($cards [$cardType] ['checkdigit']) {
            $checksum = 0; // running checksum total
            $mychar = ""; // next char to process
            $j = 1; // takes value of 1 or 2
            // Process each digit one by one starting at the right
            for ($i = strlen($cardNo) - 1; $i >= 0; $i--) {

                // Extract the next digit and multiply by 1 or 2 on alternative digits.
                $calc = $cardNo {$i} * $j;

                // If the result is in two digits add 1 to the checksum total
                if ($calc > 9) {
                    $checksum = $checksum + 1;
                    $calc = $calc - 10;
                }

                // Add the units element to the checksum total
                $checksum = $checksum + $calc;

                // Switch the value of j
                if ($j == 1) {
                    $j = 2;
                } else {
                    $j = 1;
                }
                ;
            }

            // All done - if checksum is divisible by 10, it is a valid modulus 10.
            // If not, report an error.
            if ($checksum % 10 != 0) {
                $errornumber = 3;
                $errortext = $ccErrors [$errornumber];
                return false;
            }
        }

        // The following are the card-specific checks we undertake.
        // Load an array with the valid prefixes for this card
        $prefix = explode(',', $cards [$cardType] ['prefixes']);

        // Now see if any of them match what we have in the card number
        $PrefixValid = false;
        for ($i = 0; $i < sizeof($prefix); $i++) {
            $exp = '/^' . $prefix [$i] . '/';
            if (preg_match($exp, $cardNo)) {
                $PrefixValid = true;
                break;
            }
        }

        // If it isn't a valid prefix there's no point at looking at the length
        if (!$PrefixValid) {
            $errornumber = 3;
            $errortext = $ccErrors [$errornumber];
            return false;
        }

        // See if the length is valid for this card
        $LengthValid = false;
        $lengths = explode(',', $cards [$cardType] ['length']);
        for ($j = 0; $j < sizeof($lengths); $j++) {
            if (strlen($cardNo) == $lengths [$j]) {
                $LengthValid = true;
                break;
            }
        }

        // See if all is OK by seeing if the length was valid.
        if (!$LengthValid) {
            $errornumber = 4;
            $errortext = $ccErrors [$errornumber];
            return false;
        }
        ;

        // The credit card is in the required format.
        return true;
    }

    /**
     *
     * @param type $postData
     * @param type $id
     * @return type
     */
    public function captureUserPayment($data, $id) {
        $contracted_price = $data ['contracted_price'];
        $allow_end_user_billing = $data ['allow_end_user_billing'];
        if ($allow_end_user_billing == 1) {
            $service_fee = $data ['service_fee'];
            $end_user_price = $data ['end_user_price'];
            $sfee = ($end_user_price * $service_fee / 100);
        }
        $accumulatedAmount = $this->_modelUsers->getSPAccumulatedAmount($id);
        $totalAmount = $accumulatedAmount + $contracted_price + $sfee;

        // get user payment profile details
        $authPaymentProfile = $this->_modelUsers->getAuthPaymentProfileDetails($id);
        $profileId = $authPaymentProfile->authorizenet_profile_id;
        $paymentProfileId = $authPaymentProfile->payment_profile_id;
        $shippingProfileId = $authPaymentProfile->shipping_profile_id;

        if ($profileId != '' && $paymentProfileId != '' && $shippingProfileId != '') {
            $transData = $this->cimMethod->capturePayment($profileId, $paymentProfileId, $shippingProfileId, $totalAmount);
            if ($transData ['transStatus'] == 1) {
                // Save transaction data
                $payment_source = 'CC';
                $payment_flag = 'CR';
                $this->_modelUsers->saveTransactionData($transData, $id, $totalAmount, $payment_source, $payment_flag);
                // Change service provider account status
                $this->_modelUsers->updateAccStatus($id);
                $this->_modelUsers->clearSPAccumulatedAmount($id);
                $errorResponse ['1'] = (object) array(
                            'status' => 'success-msg',
                            'message' => 'Payment captured successfully.'
                );
            } else {
                // Error in transaction
                $errorResponse ['1'] = (object) array(
                            'status' => 'error-msg',
                            'message' => 'Payment capture failed.'
                );
            }
        }

        $this->flashMessenger()->clearCurrentMessages();
        $this->flashMessenger()->addMessage($errorResponse);
        return $this->redirect()->toUrl(BASE_URL . "/admin/index/serviceproviderdetail/" . $id);
    }

    /**
     *
     * @return \Zend\View\Model\ViewModel
     */
    public function changeaccstatusAction() {
        $id = $this->params()->fromRoute('id', 0);
        $accType = $_REQUEST ['postdata'];
        $data = $this->_modelUsers->changeSPaccStatus($id, $accType);

        // Get the updated value
        $spData = $this->_modelUsers->getServiceProviderListById($id);

        // your code here ...
        $view = new ViewModel(array(
            'data' => $spData
        ));
        $view->setTerminal(true);
        return $view;
    }

    /**
     *
     * @return \Zend\View\Model\ViewModel
     */
    public function billaccspAction() {
        $id = $this->params()->fromRoute('id', 0);
        $amount = $_REQUEST ['postdata'];
        $service_provider_credit = $this->_modelUsers->getSPCredits($id);

        $authPaymentProfile = $this->_modelUsers->getAuthPaymentProfileDetails($id);
        $profileId = $authPaymentProfile->authorizenet_profile_id;
        // $paymentProfileId = $authPaymentProfile->payment_profile_id;
        // $shippingProfileId = $authPaymentProfile->shipping_profile_id;
        $status = 'FAILED';

        if ($profileId != '' && !empty($amount)) {


            if ($service_provider_credit) {
                $credit_flag = 1;
                if ($service_provider_credit <= $amount) {
                    $credit_charge = $service_provider_credit;
                    $remaining_credit = 0;
                    $amount = $amount - $service_provider_credit;
                } else {
                    $credit_charge = $amount;
                    $remaining_credit = $service_provider_credit - $amount;
                    $amount = 0;
                }
            }


            //die();
            // $accumulatedAmount = $this->_modelUsers->getSPAccumulatedAmount($id);
            // $amount = $amount + $accumulatedAmount;
            // $transData = $this->cimMethod->capturePayment($profileId, $paymentProfileId, $shippingProfileId, $amount); //OLD CIM
            try {
                if ($amount != 0) {
                    $transData = $this->stripeMethod->chargeCustomer($profileId, $amount);
                } elseif ($amount == 0 && $credit_flag) {
                    $transData = new \stdClass();
                    $transData->id = uniqid() . "TEMP";
                    $transData->paid = 1;
                    $transData->status = 'paid';
                }


                //$transData = $this->stripeMethod->chargeCustomer($profileId, $amount);
            } catch (\Exception $e) {
                $errmsg = $e->getMessage();
            }

            // Save transaction data
            $payment_source = 'CC';
            $payment_flag = 'CR';
            if ($credit_flag)
                $payment_source = "CC/Credits";



            $lastInsertedId = $this->_modelUsers->saveTransactionData($transData, $id, $amount, $payment_source, $payment_flag, '', '', 1, $credit_charge);

            $toDay = date('Y-m-d');
            //  Calculating SP next billing date
            $spNextBillingDate = date('Y-m-d', strtotime($toDay . SP_NEXT_BILLING));

            //  Mail to End User that he has charged successfully
            $viewTemplate = 'tpl/email/sppaymentmail';

            // Change service provider account status
            if ($transData->paid == 1) {
                $status = 'OK';
                // $this->_modelUsers->updateAccStatus($id, $lastInsertedId);
                $this->_modelUsers->updateAccStatus($id, $lastInsertedId, $remaining_credit);
                $this->_modelUsers->clearSPAccumulatedAmount($id);

                ////////////////////////////////////////
                $values = array(
                    'name' => 'Your RSP account, <b>' . $authPaymentProfile->company_name . '</b>, has been successfully billed for <b>$' . $amount . '</b>.',
                    'nxtBillDate' => 'Your next billing date is <b>' . $spNextBillingDate . '</b>.',
                    'msg' => ''
                );
                $subject = 'Remotii RSP Account Payment Successful.';
                ////////////////////////////////////////
            } else {
                $this->_modelUsers->updateSPLastPaymentStatId($id, $lastInsertedId);

                ////////////////////////////////////////
                $values = array(
                    'name' => 'An attempt to bill your RSP account, <b>' . $authPaymentProfile->company_name . '</b>, for <b>$' . $amount . '</b> has been unsuccessful.',
                    'nxtBillDate' => 'Please log in to your account and update your billing settings if necessary.',
                    'msg' => 'We will try again shortly.',
                );
                $subject = 'Remotii RSP Account Payment Failed.';
                ////////////////////////////////////////
            }
            ////////////////////////////////////////
            //  Email Code Start
            $mailService = $this->getServiceLocator()->get('goaliomailservice_message');
            $message = $mailService->createHtmlMessage(ADMIN_EMAIL, "$authPaymentProfile->contact_email", $subject, $viewTemplate, $values);
            try {
                $mailService->send($message);
            } catch (\Exception $e) {
                $msg = $e->getMessage();
                error_log(json_encode(array(
                 'date' => date('Y-m-d H:i:s'),
                 'method' => 'billaccspAction',
                    'message' => 'Mail not sent',
                    'email' => $authPaymentProfile->contact_email,
                    'exception' => $msg
                ))."\n", 3, '/var/www/remotii/public/cronNotification.fn.log');
            }
            //  Email Code Start
            ////////////////////////////////////////
        } else {
            // Error in transaction
        }

        // Get the updated value
        $spData = $this->_modelUsers->getServiceProviderListById($id);
        $jdata = $data [0];
        print json_encode(array(
            'data' => $spData [0],
            'status' => $status,
            'message' => $errmsg
        ));
        // your code here ...
        die();
    }

    /**
     *
     * @return \Zend\View\Model\ViewModel
     */
    public function billacceuAction() {
        $id = $this->params()->fromRoute('id', 0);
        $amount = $_REQUEST ['postdata'];

        $authPaymentProfile = $this->_modelUsers->getEUPaymentProfileDetails($id);
        $profileId = $authPaymentProfile->authorizenet_profile_id;
        $amount = $authPaymentProfile->accumulated_amount;
        // $paymentProfileId = $authPaymentProfile->payment_profile_id;
        // $shippingProfileId = $authPaymentProfile->shipping_profile_id;
        $status = 'FAILED';

        if ($profileId != '' && !empty($amount)) {
            //  Calculating SP next billing date
            $euNextBillingDate = date('Y-m-d', strtotime($authPaymentProfile->next_billing_date . ENDUSER_NEXT_BILLING));


            try {
                $transData = $this->stripeMethod->chargeCustomer($profileId, $amount);
            } catch (\Exception $e) {
                $errmsg = $e->getMessage();
            }

            // Save transaction data
            $payment_source = 'CC';
            $payment_flag = 'CR';
            $lastInsertedId = $this->_modelUsers->saveTransactionData($transData, $id, $amount, $payment_source, $payment_flag, Null, Null, 2);

            $viewTemplate = 'tpl/email/eupaymentmail';
            if ($transData->paid == 1) {
                $status = 'OK';
                $this->_modelUsers->activeEndUserAccStatus($id);
                $this->_modelUsers->clearEndUserAccumulatedAmount($id);

                ////////////////////////////////////////
                $values = array(
                    'name' => 'Your Remotii account has been successfully billed for <b>$' . $amount . '</b>.',
                    'nxtBillDate' => 'Your next billing date is <b>' . $euNextBillingDate . '</b>.',
                    'msg' => '',
                    'rsp' => 'The Remotii Team'
                );
                $subject = 'Remotii Payment Successful.';
                ////////////////////////////////////////
            } else {
                $this->_modelUsers->updateEULastPaymentStatId($id, $lastInsertedId);

                ////////////////////////////////////////
                $values = array(
                    //'name' => $authPaymentProfile->contact_fname,
                    'name' => 'An attempt to bill your Remotii account for <b>$' . $amount . '</b> has been unsuccessful.',
                    'nxtBillDate' => 'Please log in to your account and update your billing settings if necessary.',
                    'msg' => 'We will try again shortly.',
                    'rsp' => 'The Remotii Team'
                );
                $subject = 'Remotii Payment Failed.';
                ////////////////////////////////////////
            }
            ////////////////////////////////////////
            //  Email Code Start
            $mailService = $this->getServiceLocator()->get('goaliomailservice_message');
            $message = $mailService->createHtmlMessage(ADMIN_EMAIL, "$authPaymentProfile->contact_email", $subject, $viewTemplate, $values);
            try {
                $mailService->send($message);
            } catch (\Exception $e) {
                $msg = $e->getMessage();
                error_log(json_encode(array(
                 'date' => date('Y-m-d H:i:s'),
                 'method' => 'billacceuAction',
                    'message' => 'Mail not sent',
                    'email' => $authPaymentProfile->contact_email,
                    'exception' => $msg
                ))."\n", 3, '/var/www/remotii/public/cronNotification.fn.log');
            }
        } else {
            // Error in transaction
        }

        // Get the updated value
        $spData = $this->_modelUsers->getUserById($id);
        $jdata = $data [0];
        print json_encode(array(
            'data' => $spData [0],
            'status' => $status,
            'message' => $errmsg
        ));
        // your code here ...
        die();
    }

    /**
     * Function implemented Cron Billing
     *
     * @return \Zend\View\Model\ViewModel
     */
    public function cronBillServiceProvidersAction() {
        $client_ip = $this->get_client_ip();
//         var_dump($_SERVER['SERVER_ADDR'])."<br>";

        if ($client_ip == IP || IP == '') {
            $messageCron = '';
            $toDay = date('Y-m-d');
            $cron_start = date('Y-m-d H:i:s');
            //  Save data in the cron table
            $cronId = $this->_modelUsers->saveCronData($toDay, $cron_start);

            $messageCron = 'Cron Start Time - ' . $cron_start;
            //if (BILLING_DAY_OF_MONTH == date('d', time())) {
            if (true) {
                $amountDetails = $this->_modelUsers->getSpsAmountDetails();
                $transData = "";

                //  die();
                foreach ($amountDetails as $amountDetail) {

                    $credit_flag = 0;
                    $credit_charge = "";
                    $remaining_credit = "";
                    $totalAmountToCharge = "";
                    $service_provider_credit = "";
                    $transData = "";
                    $id = $amountDetail->service_provider_id;
                    $amount = $amountDetail->contracted_price_total; // + $amountDetail->servie_fee;
                    $numberOfTimes = $amountDetail->try_count;

                    $authPaymentProfile = $this->_modelUsers->getAuthPaymentProfileDetails($id);
                    $profileId = $authPaymentProfile->authorizenet_profile_id;
                    // $paymentProfileId = $authPaymentProfile->payment_profile_id;
                    // $shippingProfileId = $authPaymentProfile->shipping_profile_id;

                    if ($profileId != '') {
                        try {
                            // $transData = $this->cimMethod->capturePayment($profileId, $paymentProfileId, $shippingProfileId, $amount);
                            //$accumulatedAmount = $this->_modelUsers->getSPAccumulatedAmount ( $id );    //change
                            $accumulatedAmount = $amountDetail->accumulated_amount;

                            if ($numberOfTimes == 1) {
                                $totalAmountToCharge = $accumulatedAmount + $amount;
                            } else {
                                $totalAmountToCharge = $accumulatedAmount;
                            }



                            $service_provider_credit = $amountDetail->service_provider_credit;


                            // Charge From Credits
                            if ($service_provider_credit) {

                                $credit_flag = 1;
                                if ($service_provider_credit <= $totalAmountToCharge) {
                                    $credit_charge = $service_provider_credit;
                                    $remaining_credit = 0;
                                    $totalAmountToCharge = $totalAmountToCharge - $service_provider_credit;
                                } else {
                                    $credit_charge = $totalAmountToCharge;
                                    $remaining_credit = $service_provider_credit - $totalAmountToCharge;
                                    $totalAmountToCharge = 0;
                                }
                            }


                            //                        echo "<pre>";
                            //                        print_r("credit_charge=".$credit_charge);
                            //                        echo "<pre>";
                            //                        print_r("remain_credit=".$remaining_credit);
                            //                        echo "<pre>";
                            //                        print_r("amount=".$totalAmountToCharge);
                            //                        echo "<pre>";
                            //
                            //                        die("asd");
                            //if (!empty($totalAmountToCharge))

                            if ($totalAmountToCharge >= 0) {
                                if ($totalAmountToCharge != 0) {
                                    $transData = $this->stripeMethod->chargeCustomer($profileId, $totalAmountToCharge);
                                } elseif ($totalAmountToCharge == 0 && $credit_flag) {
                                    $transData = new \stdClass();
                                    $transData->id = uniqid() . "TEMP";
                                    $transData->paid = 1;
                                    $transData->status = 'paid';
                                }
                            } else {
                                continue;
                            }
                            // Change service provider account status
                        } catch (\Exception $e) {
                            if ($e instanceof \Stripe_CardError or $e instanceof \Stripe_InvalidRequestError or $e instanceof \Stripe_AuthenticationError or $e instanceof \Stripe_ApiConnectionError or $e instanceof \Stripe_Error) {
                                // Error occured for
                                $body = $e->getJsonBody();
                                $err = $body ['error'];
                                //                        error_log(json_encode(array(
                                //                            'spId' => $id,
                                //                            'error' => $err ['message']
                                //                                )), 3, '/var/www/remotii/public/cronBillServiceProvidersAction.fn.log');
                                $messageCron .= 'Charge to SP failed. Error ' . $err ['message'];
                                $transData = $err ['message'];
                            }
                        }

                        // Save transaction data
                        $payment_source = 'CC';
                        $payment_flag = 'CR';
                        if ($credit_flag)
                            $payment_source = "CC/Credits";



                        $lastInsertedId = $this->_modelUsers->saveTransactionData($transData, $id, $totalAmountToCharge, $payment_source, $payment_flag, '', '', 1, $credit_charge);




                        //  Calculating SP next billing date
                        $spNextBillingDate = date('Y-m-d', strtotime($toDay . SP_NEXT_BILLING));
                        if ($toDay == trim($amountDetail->next_billing_date) || $amountDetail->next_billing_date == NULL) {
                            $this->_modelUsers->updateSPnextBillDate($id, $spNextBillingDate);
                        }
                        //  Mail to End User that he has charged successfully
                        $viewTemplate = 'tpl/email/sppaymentmail';

                        if ($transData->paid == 1) {
                            $messageCron .= 'Charge to SP success. Transaction Id' . $transData->id;
                            $lastPaymentStatus = 1;
                            $this->_modelUsers->updateAccStatus($id, $lastInsertedId, $remaining_credit);
                            $this->_modelUsers->clearSPAccumulatedAmount($id);

                            //  Mail to service provider that he has charged successfully
                            ////////////////////////////////////////
                            $values = array(
                                //                            'name' => $amountDetail->contact_fname,
                                //                            'amntMsg' => 'Your RSP account, ' . $amountDetail->company_name . ', has been successfully billed for ' . $totalAmountToCharge. '.',
                                //                            'nxtBillDate' => 'Your next billing date is ' . $spNextBillingDate. '.',
                                //                            //'numberOfTimes' => 'Number of tries ' . $numberOfTimes
                                //                            'numberOfTimes' => 'Thank you for your business,'
                                'name' => 'Your RSP account, <b>' . $authPaymentProfile->company_name . '</b>, has been successfully billed for <b>$' . $totalAmountToCharge . '</b>.',
                                'nxtBillDate' => 'Your next billing date is <b>' . $spNextBillingDate . '</b>.',
                                'msg' => ''
                            );
                            $subject = 'Remotii RSP Account Payment Successful.';
                            ////////////////////////////////////////
                        } else {
                            $messageCron .= 'Charge to SP failed. Payment Stat Id' . $lastInsertedId;
                            $lastPaymentStatus = 0;
                            $this->_modelUsers->updateSPLastPaymentStatId($id, $lastInsertedId);
                            $this->_modelUsers->updateAccStatusToDelinquent($id);
                            if ($numberOfTimes == 1) {
                                $this->_modelUsers->addSPAccumulatedAmount($id, $amount);
                            }

                            ////////////////////////////////////////
                            $values = array(
                                'name' => 'An attempt to bill your RSP account, <b>' . $authPaymentProfile->company_name . '</b>, for <b>$' . $totalAmountToCharge . '</b> has been unsuccessful.',
                                'nxtBillDate' => 'Please log in to your account and update your billing settings if necessary.',
                                'msg' => 'We will try again shortly.',
                            );
                            $subject = 'Remotii RSP Account Payment Failed.';
                            ////////////////////////////////////////
                        }
                        $this->_modelUsers->updateSPlastPaymentStatus($id, $lastPaymentStatus);
                        ////////////////////////////////////////
                        //  Email Code Start
                        $mailService = $this->getServiceLocator()->get('goaliomailservice_message');
                        $message = $mailService->createHtmlMessage(ADMIN_EMAIL, "$amountDetail->contact_email", $subject, $viewTemplate, $values);
                        //                    $message = $mailService->createHtmlMessage(ADMIN_EMAIL, "DEV_EMAIL", $subject, $viewTemplate, $values);

                        try {
                            $mailService->send($message);
                        } catch (\Exception $e) {
                            $msg = $e->getMessage();
                            //                        error_log(json_encode(array(
                            //                            'message' => 'Mail not sent',
                            //                            'email' => $amountDetail->contact_email,
                            //                            'exception' => $msg
                            //                                )), 3, '/var/www/remotii/public/cronNotification.fn.log');
                        }
                        //  Email Code Start
                        ////////////////////////////////////////
                    }
                    //error_log(json_encode($transData), 3, '/var/www/remotii/public/cronBillServiceProvidersAction.fn.log');
                }

                //  SP_BILLING_NEXT_MONTH
                //  ENDUSER_BILLING_NEXT_MONTH
                // Function call to charge End user and deposit amount into Admin stripe account
                $endCharge = $this->_modelUsers->chargeToEndUser();
                // _pre($endCharge);
                // $customSpArr = array();
                // $i = 0;
                $transData = "";
                foreach ($endCharge as $endInfo) {
                    $transData = "";
                    try {
                        $userId = $endInfo->user_id;
                        $userRemotiiId = $endInfo->userRemotiiId;
                        $numberOfTimesEU = $endInfo->try_count;

                        $endUserAccumulatedAmount = $this->_modelUsers->getEndUserAccumulatedAmount($userId);
                        //$customerNeedToPay = $endInfo->amount * $endInfo->spCount;
                        $customerNeedToPay = $endInfo->amount * ENDUSER_BILL_MONTHS;
                        if ($numberOfTimesEU == 1) {
                            $totalEndUserAmountToCharge = $endUserAccumulatedAmount + $customerNeedToPay;
                        } else {
                            $totalEndUserAmountToCharge = $endUserAccumulatedAmount;
                        }

                        if (!empty($totalEndUserAmountToCharge)) {
                            $transData = $this->stripeMethod->chargeCustomer($endInfo->pid, $totalEndUserAmountToCharge);
                        } else {
                            continue;
                        }
                    } catch (\Exception $e) {
                        if ($e instanceof \Stripe_CardError or $e instanceof \Stripe_InvalidRequestError or $e instanceof \Stripe_AuthenticationError or $e instanceof \Stripe_ApiConnectionError or $e instanceof \Stripe_Error) {
                            // Error occured for
                            $body = $e->getJsonBody();
                            $err = $body ['error'];
                            error_log(json_encode(array(
                                'userId' => $userId,
                                'error' => $err ['message']
                                    )), 3, '/var/www/remotii/public/cronBillServiceProvidersAction.fn.log');
                            $messageCron .= 'Charge to End User failed. Error ' . $err['message'];
                            $transData = $err['message'];
                        }
                    }

                    // Save transaction data
                    $payment_source = 'CC';
                    $payment_flag = 'CR';
                    $lastInsertedId = $this->_modelUsers->saveTransactionData($transData, $userId, $totalEndUserAmountToCharge, $payment_source, $payment_flag, $endInfo->userRemotiiId, Null, 2);
                    // Change service provider account status


                    error_log(json_encode($transData), 3, '/var/www/remotii/public/cronBillServiceProvidersAction.fn.log');

                    //  Mail to End User that he has charged successfully
                    $viewTemplate = 'tpl/email/eupaymentmail';

                    //  Calculating SP next billing date
                    $euNextBillingDate = date('Y-m-d', strtotime($toDay . ENDUSER_NEXT_BILLING));

                    if ($transData->paid == 1) {
                        $messageCron .= 'Charge to End User success. Transaction Id ' . $transData->id;
                        // $customSpArr[$i][$endInfo->service_provider_id] = $endInfo->amount_to_pay;
                        // $i++;
                        //  CHK This
                        //$this->_modelUsers->updateAccStatus ( $userId, $lastInsertedId );
                        //  CHK This
                        $this->_modelUsers->updateAccStatusEU($userRemotiiId, $lastInsertedId, $euNextBillingDate);

                        $this->_modelUsers->clearEndUserAccumulatedAmount($userRemotiiId);

                        //  Save Date into table for paying to SP that is payback amount START
                        $totalAmountToPayToSP = $totalEndUserAmountToCharge - ($totalEndUserAmountToCharge * $endInfo->service_fee / 100);
                        if ($totalAmountToPayToSP > 0) {
                            $nextPaybackDate = date('Y-m-d', strtotime($toDay . SP_NEXT_GET_PAYMENT));
                            $this->_modelUsers->saveSPpaybackInfo($endInfo->service_provider_id, $totalAmountToPayToSP, $nextPaybackDate);
                            //  Save Date into table for paying to SP that is payback amount END
                            ////////////////////////////////////////
                        }
                        $values = array(
                            //                        'name' => $endInfo->fname,
                            //                        'amntMsg' => 'Your Remotii, ' . $endInfo->remotiiName . ', has been successfully billed for ' . $totalEndUserAmountToCharge,
                            //                        'nxtBillDate' => 'Your next billing date is ' . $euNextBillingDate,
                            //                        'numberOfTimesEU' => 'Thank you for your business, <br /><br /> ' . $endInfo->spFname . ' ' . $endInfo->spLname
                            'name' => 'Your Remotii account has been successfully billed for <b>$' . $totalEndUserAmountToCharge . '</b>.',
                            'nxtBillDate' => 'Your next billing date is <b>' . $euNextBillingDate . '</b>.',
                            'msg' => '',
                            'rsp' => 'The Remotii Team'
                        );
                        $subject = 'Remotii Payment Successful.';
                        ////////////////////////////////////////
                    } else {
                        $messageCron .= 'Charge to End User failed. Payment Stat Id ' . $lastInsertedId;
                        ////////////////////////////////////////
                        $values = array(
                            //                        'name' => $endInfo->fname,
                            //                        'amntMsg' => 'An attempt to bill your Remotii, ' . $endInfo->remotiiName . ', for ' . $totalEndUserAmountToCharge . ' has been unsuccessful.',
                            //                        'nxtBillDate' => 'Please log in to your account and update your billing settings if necessary.',
                            //                        'numberOfTimesEU' => 'Thank you for your business, <br /><br /> ' . $endInfo->spFname . ' ' . $endInfo->spLname
                            'name' => 'An attempt to bill your Remotii account for <b>$' . $totalEndUserAmountToCharge . '</b> has been unsuccessful.',
                            'nxtBillDate' => 'Please log in to your account and update your billing settings if necessary.',
                            'msg' => 'We will try again shortly.',
                            'rsp' => 'The Remotii Team'
                        );
                        $subject = 'Remotii Payment Failed.';
                        ////////////////////////////////////////

                        $this->_modelUsers->updateEULastPaymentStatIdByRemotiiId($userRemotiiId, $lastInsertedId);
                        $this->_modelUsers->updateUserAccStatusToDelinquent($userId);
                        if ($numberOfTimesEU == 1) {
                            $this->_modelUsers->addEndUserAccumulatedAmount($userRemotiiId, $customerNeedToPay);
                        }
                    }

                    ////////////////////////////////////////
                    //  Email Code Start
                    $mailService = $this->getServiceLocator()->get('goaliomailservice_message');
                    $message = $mailService->createHtmlMessage(ADMIN_EMAIL, "$endInfo->email", $subject, $viewTemplate, $values);
                    try {
                        $mailService->send($message);
                    } catch (\Exception $e) {
                        $msg = $e->getMessage();
                        error_log(json_encode(array(
                         'date' => date('Y-m-d H:i:s'),
                         'method' => 'cronBillServiceProvidersAction-1',
                            'message' => 'Mail not sent',
                            'email' => $endInfo->email,
                            'exception' => $msg
                        ))."\n", 3, '/var/www/remotii/public/cronNotification.fn.log');
                    }
                    //  Email Code Start
                    ////////////////////////////////////////
                    //$totalAmountToPayToSP [$endInfo->service_provider_id] ['stripe_acc_id'] = $endInfo->stripe_acc_id;
                    //  OLD
                    //$totalAmountToPayToSP [$endInfo->service_provider_id] ['totalAmountToPay'] = $endUserAccumulatedAmount + $totalAmountToPayToSP [$endInfo->service_provider_id] ['totalAmountToPay'] + $endInfo->spCount * ($endInfo->amount - ($endInfo->amount * $endInfo->service_fee / 100));

                    /*
                      Commented on 03-01-2013 by k

                      $totalAmountToPayToSP = $endUserAccumulatedAmount + $totalAmountToPayToSP [$endInfo->service_provider_id] ['totalAmountToPay'] + ($endInfo->amount - ($endInfo->amount * $endInfo->service_fee / 100));
                      //  Calculating SP next Get Payment date
                      $nextPaybackDate = date('Y-m-d', strtotime($toDay . SP_NEXT_GET_PAYMENT));
                      //  Add the amount to pay to SP in DB
                      //$this->_modelUsers->updateAmountToPayToSP($endInfo->service_provider_id, $totalAmountToPayToSP, $nextPaybackDate);
                      //  Save SP Payback data
                      $this->_modelUsers->saveSPpaybackInfo($endInfo->service_provider_id, $totalAmountToPayToSP, $nextPaybackDate);
                     */
                }

                //  Mail to SP to credit amount in his account
                $viewTemplate = 'tpl/email/sppaybackmail';

                $spPaybackSuccessId = array();
                $spPaybackSuccessId[] = 0;
                //  Get the data to pay to SP
                $payToSP = $this->_modelUsers->payToSP();
                foreach ($payToSP as $spInfo) {
                    try {
                        $customAmntMail = $spInfo->payback_amount;
                        // Pay to SP
                        $payToSPAmount = ($spInfo->payback_amount * 100);
                        // $endInfo->spCount*($endInfo->amount - ($endInfo->amount*$endInfo->service_fee/100));
                        //  stripe bank id   replace with
                        if (!empty($payToSPAmount)) {
                            $transData2 = $this->stripeMethod->createTransfer($payToSPAmount, $spInfo->stripe_acc_id);
                        } else {
                            continue;
                        }
                    } catch (\Exception $e) {
                        if ($e instanceof \Stripe_CardError or $e instanceof \Stripe_InvalidRequestError or $e instanceof \Stripe_AuthenticationError or $e instanceof \Stripe_ApiConnectionError or $e instanceof \Stripe_Error) {
                            // Error occured for
                            $body = $e->getJsonBody();
                            $err = $body ['error'];
                            error_log(json_encode(array(
                                'spid' => $spInfo->service_provider_id,
                                'error' => $err['message']
                                    )), 3, '/var/www/remotii/public/cronBillServiceProvidersAction.fn.log');
                            $messageCron .= 'Payback to SP failed. Error ' . $err['message'];
                            $transDataMsg = $err['message'];
                        }
                    }


                    if ($transData2->status == 'paid' || $transData2->status == 'pending') {
                        $messageCron .= 'Payback to SP success. Payment Stat Id ' . $transData2->id;
                        //  IN CASE of success empty the amount
                        //  $this->_modelUsers->clearSPAmountToPay($spInfo->service_provider_id);
                        // Change status to paid
                        $spPaybackSuccessId[] = $spInfo->payback_id;

                        //  Mail to Service provider that account has been credited to his account
                        ////////////////////////////////////////
                        $values = array(
                            //'name' => $spInfo->contact_fname,
                            'name' => 'We have successfully deposited <b>$' . $customAmntMail . '</b> in to your  <b>' . $spInfo->company_name . '</b> RSP account.',
                            'nxtBillDate' => ''
                        );
                        $subject = 'Remotii RSP Account Deposit Successful.';
                        ////////////////////////////////////////
                    } else {
                        $messageCron .= 'Payback to SP failed.';
                        //  IN CASE of success empty the amount
                        //  Calculating SP next Get Payment date
                        //  $spNextGetPaymentDate = date('Y-m-d', strtotime($toDay . SP_NEXT_GET_PAYMENT));
                        //  $this->_modelUsers->updateSPAmountToPayDate($spInfo->service_provider_id, $spNextGetPaymentDate);
                        ////////////////////////////////////////
                        $values = array(
                            //'name' => $spInfo->contact_fname,
                            'name' => 'Unfortunately, an error has occurred while depositing <b>$' . $customAmntMail . '</b> in to your <b>' . $spInfo->company_name . '</b> RSP account.',
                            'nxtBillDate' => 'Please log in to your account and update your account information if necessary.'
                        );
                        $subject = 'Remotii RSP Account Deposit Failed.';
                        ////////////////////////////////////////
                    }

                    ////////////////////////////////////////
                    //  Email Code Start
                    $mailService = $this->getServiceLocator()->get('goaliomailservice_message');
                    $message = $mailService->createHtmlMessage(ADMIN_EMAIL, "$spInfo->contact_email", $subject, $viewTemplate, $values);
                    try {
                        $mailService->send($message);
                    } catch (\Exception $e) {
                        $msg = $e->getMessage();
                        error_log(json_encode(array(
                         'date' => date('Y-m-d H:i:s'),
                         'method' => 'cronBillServiceProvidersAction-2',
                            'message' => 'Mail not sent',
                            'email' => $spInfo->contact_email,
                            'exception' => $msg
                        ))."\n", 3, '/var/www/remotii/public/cronNotification.fn.log');
                    }
                    //  Email Code Start
                    ////////////////////////////////////////
                    //                $payment_source = 'Stripe';
                    $payment_source = 'Stripe Transfer';
                    $payment_flag = 'DR';
                    $lastInsertedId = $this->_modelUsers->saveTransactionData($transData2, $spInfo->service_provider_id, - $spInfo->payback_amount, $payment_source, $payment_flag, $transDataMsg);
                    error_log(json_encode($transData2), 3, '/var/www/remotii/public/cronBillServiceProvidersAction.fn.log');
                }

                //  Change the payback payment status to paid
                $spPaybackSuccessIdStr = implode(',', $spPaybackSuccessId);
                $this->_modelUsers->changePaybackPaymentStatusPaid($spPaybackSuccessIdStr);

                /*
                 * // Calculating the Total amount of the service provider to pay by admin foreach($customSpArr as $val) { foreach($val as $k => $v) { if(array_key_exists($k, $val)) { $cust[$k] = $cust[$k] + $v; } } } // // // $sreviceProviderToPay = array_keys($cust); $strSreviceProviderToPay = implode(',', $sreviceProviderToPay); // Function defined to pay SP by Admin Using Stripe $amountSpDetails = $this->_modelUsers->getAmountPaidToSP($strSreviceProviderToPay); foreach($amountSpDetails as $dataAmount) { $transData = $this->stripeMethod->createTransfer($cust[$dataAmount->service_provider_id], $dataAmount->stripe_acc_id); $payment_source = 'Stripe'; $payment_flag = 'DR'; $lastInsertedId = $this->_modelUsers->saveTransactionData($transData, $transData->service_provider_id, -$cust[$dataAmount->service_provider_id], $payment_source, $payment_flag); error_log(json_encode($transData),3,'/var/www/remotii/public/cronBillServiceProvidersAction.fn.log'); }
                 */

                $cron_end = date('Y-m-d H:i:s');
                $messageCron .= ' Cron End Time - ' . $cron_end;
                //  Save data in the cron table
                $cronUpdate = $this->_modelUsers->updateCronData($cronId, $cron_end, $messageCron);

                $view = new ViewModel(array(
                    'data' => $spData
                ));
                $view->setTerminal(true);
                return $view;
            }
        } else {
            die('Unauthorized Access');
        }
    }

    /**
     *
     * @return \Zend\View\Model\ViewModel
     */
    public function cronNotificationMailAction() {
        $details = $this->_modelUsers->getMailNotificationData();
        $numbersList = $this->_modelClient->getNumbers();
        $dataCust = array();
        $dataCustO = array();
        $dataCust[] = 0;
        $dataCustO[] = 0;
        foreach ($details as $data) {
            $email = explode(",", $data->email);
            //user data with given mac id
            $uinfo = $this->_modelClient->getEUByMac($data->mac_address);
            //user remotii data
            $bitInfo = $this->_modelUsers->getRemotiiName($data->mac_address);
            if ($data->email_type == 0) {
                //input and otput config for the mac
                $inputConfig = $this->_modelUsers->getInputConfig($data->mac_address);
                $outputConfig = $this->_modelUsers->getOutputConfig($data->mac_address);
                //time of notification_mail insert
                $timestamp = strtotime($data->time);
                //get remotii id
                $remotii_id = $this->_modelUsers->getRemotiiId($data->mac_address);
                $r_id = $remotii_id[0]->remotii_id;
                //get offset daylight remotii value
                $remotii_data = $this->_modelUsers->getRemotiiOffsetDayLightSaving($r_id);
                $offset = $remotii_data[0]->offset;
                $day_light_saving = $remotii_data[0]->day_light_saving;
                $time = 0;
                //
                if ($day_light_saving == 0) {
                    $time = $offset;
                }
                if ($day_light_saving == 1) {
                    $time = $offset;
                    $time = $time - 60;
                }
                $hour = intval($time / 60);
                $minutes = $time % 60;
                $Time = '+00:00';
                if ($time < 0) {
                    $hour = -$hour;
                    $minutes = -$minutes;
                    if ($hour < 10) {
                        if ($minutes < 10) {
                            $Time = "-0$hour:0$minutes";
                        }
                        if ($minutes >= 10) {
                            $Time = "-0$hour:$minutes";
                        }
                    }

                    if ($hour >= 10) {
                        if ($minutes < 10) {
                            $Time = "-$hour:0$minutes";
                        }
                        if ($minutes >= 10) {
                            $Time = "-$hour:$minutes";
                        }
                    }
                }

                if ($time > 0) {
                    if ($hour < 10) {
                        if ($minutes < 10) {
                            $Time = "+0$hour:0$minutes";
                        }
                        if ($minutes >= 10) {
                            $Time = "+0$hour:$minutes";
                        }
                    }

                    if ($hour >= 10) {
                        if ($minutes < 10) {
                            $Time = "+$hour:0$minutes";
                        }
                        if ($minutes >= 10) {
                            $Time = "+$hour:$minutes";
                        }
                    }
                }

                $newTime = $timestamp + $offset * 60;
                if ($newTime > 0 && $timestamp > 0) {
                    $date = date('m/d/Y h:i:s A', $newTime);
                    $time_with_space = '';
                    for ($i = 0; $i < strlen($date); $i++) {
                        if ($date{$i} == ' ' && $i <= 15) {
                            $time_with_space = $time_with_space . ' ' . $date{$i};
                        } else {
                            $time_with_space = $time_with_space . $date{$i};
                        }
                    }
                    $actual_time = $time_with_space; //. " GMT(" . $Time . ")";
                } else {
                    $actual_time = $timestamp;
                }
                //
                foreach ($email as $mail) {
                    if (!empty($mail)) {
                        $domain_name = substr(strrchr($mail, "@"), 1);
                        if (in_array($domain_name, $numbersList)) {
                            $viewTemplate = 'tpl/email/spmail-text';
                        } else {
                            $viewTemplate = 'tpl/email/spmail';
                        }
                        $values = array(
                            'message' => 'Pin status has been changed',
                            'uinfo' => $uinfo,
                            'bitInfo' => $bitInfo[0],
                            'inputConfig' => $inputConfig,
                            'outputConfig' => $outputConfig,
                            'trigger_pin' => $data->trigger_pin,
                            'inState' => $data->instate,
                            'outState' => $data->outstate,
                            'outTime' => $actual_time,
                            'in_out_flag' => $data->in_out_flag
                        );

                        $subject = 'Remotii Notification';
                        $mailService = $this->getServiceLocator()->get('goaliomailservice_message');
                        if (in_array($domain_name, $numbersList)) {
                            $message = $mailService->createTextMessage(ADMIN_EMAIL, "$mail", $subject, $viewTemplate, $values);
                        } else {
                            $message = $mailService->createHtmlMessage(ADMIN_EMAIL, "$mail", $subject, $viewTemplate, $values);
                        }
                        //$mailService->send ( $message );
                        $dataCust[] = $data->nid;

                        try {
                            $mailService->send($message);
                        } catch (\Exception $e) {
                            $msg = $e->getMessage();
                            error_log(json_encode(array(
                             'date' => date('Y-m-d H:i:s'),
                             'method' => 'cronNotificationMailAction-1',
                                'macId' => $data->mac_address,
                                'message' => 'Mail not sent',
                                'email' => $mail,
                                'exception' => $msg
                            ))."\n", 3, '/var/www/remotii/public/cronNotification.fn.log');
                        }
                    }
                }
            } else {
                foreach ($email as $mail) {
                    if (!empty($mail)) {
                        $domain_name = substr(strrchr($mail, "@"), 1);
                        if (in_array($domain_name, $numbersList)) {
                            $viewTemplate = 'tpl/email/closed-loop-text';
                        } else {
                            $viewTemplate = 'tpl/email/closed-loop-mail';
                        }
                        $chained_event = $this->_modelClient->getChainedEventData($data->event_id);
                        $values = array(
                            'message' => 'Closed Loop',
                            'event_name' => $chained_event['event_name']
                        );

                        $subject = '[Remotii] Closed Loop';
                        $mailService = $this->getServiceLocator()->get('goaliomailservice_message');
                        if (in_array($domain_name, $numbersList)) {
                            $message = $mailService->createTextMessage(ADMIN_EMAIL, "$mail", $subject, $viewTemplate, $values);
                        } else {
                            $message = $mailService->createHtmlMessage(ADMIN_EMAIL, "$mail", $subject, $viewTemplate, $values);
                        }
                        //$mailService->send ( $message );
                        $dataCust[] = $data->nid;

                        try {
                            $mailService->send($message);
                        } catch (\Exception $e) {
                            $msg = $e->getMessage();
                            error_log(json_encode(array(
                             'date' => date('Y-m-d H:i:s'),
                             'method' => 'cronNotificationMailAction-2',
                                'macId' => $data->mac_address,
                                'message' => 'Mail not sent',
                                'email' => $mail,
                                'exception' => $msg
                            ))."\n", 3, '/var/www/remotii/public/cronNotification.fn.log');
                        }
                    }
                }
            }
        }
        $detailsM = $this->_modelUsers->changeMailNotificationStatus($dataCust);

        $offlineNotificationDetails = $this->_modelUsers->getOfflineMailNotificationData();
        foreach ($offlineNotificationDetails as $mail) {
            if (!empty($mail)) {
                $domain_name = substr(strrchr($mail['email'], "@"), 1);
                if (in_array($domain_name, $numbersList)) {
                    $viewTemplate = 'tpl/email/offline-notification-text';
                } else {
                    $viewTemplate = 'tpl/email/offline-notification-mail';
                }

                //$viewTemplate = 'tpl/email/offline-notification-mail';

                $values = array(
                    'name' => $mail['name'],
                    'remotii_name' => $mail['remotii_name'],
                    'remotii_offline_hours' => $mail['remotii_offline_hours'],
                    'remotii_last_communication_time' => $mail['remotii_last_communication_time'],
                );

                $subject = 'Remotii Offline Notification';
                $mailService = $this->getServiceLocator()->get('goaliomailservice_message');

                $message = $mailService->createHtmlMessage(ADMIN_EMAIL, $mail['email'], $subject, $viewTemplate, $values);

                $dataCustO[] = $mail['id'];

                try {
                    $mailService->send($message);
                } catch (\Exception $e) {
                    $msg = $e->getMessage();
                    error_log(json_encode(array(
                     'date' => date('Y-m-d H:i:s'),
                     'method' => 'cronNotificationMailAction-3',
                        'remotii' => $mail['remotii_name'],
                        'message' => 'Mail not sent',
                        'email' => $mail,
                        'exception' => $msg
                    ))."\n", 3, '/var/www/remotii/public/cronNotification.fn.log');
                }
            }
        }

        $detailsO = $this->_modelUsers->changeOfflineMailNotificationStatus($dataCustO);

        $view = new ViewModel ();
        $view->setTerminal(true);
        return $view;
    }

    /*
     * Remove Inbound Data
     */

    public function cronOfflineNotificationAction() {
        $current_timestamp = time();
        $adapter = $this->_modelUsers->adapter;
        $stmt = $adapter->query("Select r.*,ur.offset,z.abbr FROM remotii r LEFT JOIN user_remotii as ur on (r.remotii_id = ur.remotii_id )
                LEFT JOIN zone_gmt z on ur.zone_id = z.zone_id WHERE r.offline_notification_timeout_hours > 0 "
                . "AND ((" . $current_timestamp . "-r.remotii_last_heartbeat_received_time)/3600) >= (r.offline_notification_timeout_hours + 0.083) "
                . "AND r.last_offline_detection_timestamp = 0 AND (r.enable_end_user_offline_notifications=1 OR r.enable_rsp_offline_notifications=1)");
        
        $notifications = $stmt->execute()->getResource()->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($notifications as $notification) {
            $email = '';
            $sent = 0;
            $MaxTime = $notification['remotii_last_heartbeat_received_time'] + (int) $notification['offset'] * 60;
            if ($MaxTime > 0 && $notification['remotii_last_heartbeat_received_time'] > 0) {
                $FormattedTime = date('m/d/Y H:i:s', $MaxTime);
                $time_with_space = '';
                for ($i = 0; $i < strlen($FormattedTime); $i++) {
                    if ($FormattedTime{$i} == ' ' && $i <= 15) {
                        $time_with_space = $time_with_space . ' ' . $FormattedTime{$i};
                    } else {
                        $time_with_space = $time_with_space . $FormattedTime{$i};
                    }
                }

                $lastNotificationTime = $time_with_space . " (" . $notification['abbr'] . ")";
            } else {
                $lastNotificationTime = $notification['remotii_last_heartbeat_received_time'];
            }

            if ($notification['enable_end_user_offline_notifications'] == 1) {
                if (!empty($notification['notification_email'])) {
                    $email = array();
                    $email = explode(",", $notification['notification_email']);
                }

                $userDetailsSql = $adapter->query("Select u.email,u.fname,u.lname,ur.remotii_name FROM user_remotii ur INNER JOIN user u ON(u.user_id=ur.user_id) WHERE ur.remotii_id=" . $notification['remotii_id']);
                $userDetails = $userDetailsSql->execute()->getResource()->fetch(\PDO::FETCH_ASSOC);
//                $email[] = $userDetails['email'];
                $name = $userDetails['fname'] . " " . $userDetails['lname'];
                $remotii_name = $userDetails['remotii_name'];
                
                foreach ($email as $mail) {
                    $sendMail = "INSERT INTO offline_notification_email(email,time,name,remotii_name,remotii_offline_hours,remotii_last_communication_time)"
                            . " VALUES('" . $mail . "','" . $current_timestamp . "','" . $name . "','" . $remotii_name . "','" . $notification['offline_notification_timeout_hours'] . "','" . $lastNotificationTime . "')";
                    $queryExeA = $adapter->query($sendMail);
                    $queryExeA->execute();
                    $sent = 1;
                }
            }
            if ($notification['enable_rsp_offline_notifications'] == 1) {
                $RspDetailsSql = $adapter->query("Select r.mac_address,sp.contact_email,sp.contact_fname,sp.contact_lname FROM remotii r INNER JOIN service_provider sp ON(r.service_provider_id=sp.service_provider_id) WHERE r.remotii_id=" . $notification['remotii_id']);
                $RspDetails = $RspDetailsSql->execute()->getResource()->fetch(\PDO::FETCH_ASSOC);
                $email = $RspDetails['contact_email'];
                $name = $RspDetails['contact_fname'] . " " . $RspDetails['contact_lname'];
                $remotii_name = $RspDetails['mac_address'];
                $sendMail = "INSERT INTO offline_notification_email(email,time,name,remotii_name,remotii_offline_hours,remotii_last_communication_time)"
                        . " VALUES('" . $email . "','" . $current_timestamp . "','" . $name . "','" . $remotii_name . "','" . $notification['offline_notification_timeout_hours'] . "','" . $lastNotificationTime . "')";
                $queryExeA = $adapter->query($sendMail);
                $queryExeA->execute();
                $sent = 1;
            }
            if ($sent) {
                $updateRemotiiAfterMailSuccess = "UPDATE remotii SET last_offline_detection_timestamp='$current_timestamp'"
                        . "where remotii_id=" . $notification['remotii_id'];
                $queryExeA = $adapter->query($updateRemotiiAfterMailSuccess);
                $queryExeA->execute();
            }
        }

        //update remotii whose heartbeat timestamp is greater than our notification timestamp
        $stmt2 = $adapter->query("UPDATE remotii SET last_offline_detection_timestamp=0 WHERE last_offline_detection_timestamp > 0 AND remotii_last_heartbeat_received_time >= last_offline_detection_timestamp");
        $resetUpdatedRemotiis = $stmt2->execute();

        die();
    }

    /*
     * Remove Inbound Data
     */

    public function cronRmibDataAction() {
        $adapter = $this->_modelUsers->adapter;
        $stmt = $adapter->query("DELETE FROM inbound WHERE receive_time < NOW() - INTERVAL 2 WEEK");
        $stmt->execute();
        die();
    }

    /*
     * Remove Outbound data
     */

    public function cronRmobDataAction() {
        $adapter = $this->_modelUsers->adapter;
        $stmt = $adapter->query("DELETE FROM outbound WHERE xmit_time IS NULL and insert_time < NOW() - INTERVAL 5 MINUTE");
        $stmt->execute();
        die();
    }

    /*
     * Remove Outbound data
     */

    public function cronRmEventCronDataAction() {
        $adapter = $this->_modelUsers->adapter;
        $stmt = $adapter->query("DELETE FROM `cron_event_scheduler`
            WHERE FROM_UNIXTIME(created_on,'%Y-%m-%d %H:%i:%s') < NOW() - INTERVAL 24 HOUR");
        $stmt->execute();
        die();
    }

    /**
     *
     * @return \Zend\View\Model\ViewModel
     */
    public function remotichkAction() {
        $remVal = $_REQUEST ['postdata'];
        $data = $this->_modelUsers->remotiValidateChk($remVal);

        // your code here ...
        $view = new ViewModel(array(
            'data' => $data
        ));
        $view->setTerminal(true);
        return $view;
    }

    /**
     *
     * @return \Zend\View\Model\ViewModel
     */
    public function remotiisaveAction() {
        $id = $this->params()->fromRoute('id', 0);
        $loggedInUserId = $this->getLoggedInUserId();
        $SPUsers = $this->_modelUsers->getSPUsers($id);
        $RSPFirstUser = $SPUsers[0]->user_id;

        $remVal = $_REQUEST ['postdata'];
        $count = count($remVal);
        for ($i = 0; $i < $count; $i++) {
            $remId = $this->_modelUsers->saveRemotiiMacForRSP($remVal[$i], $id, $loggedInUserId);
            $userRemotiiId = $this->_modelUsers->saveUserRemotiiData($remVal[$i], $remId, $RSPFirstUser);
            $this->_clientModel->saveInputConfig($userRemotiiId, $spInputConfig, $RSPFirstUser);
            $this->_clientModel->saveOutputConfig($userRemotiiId, $spOutputConfig, $RSPFirstUser);
        }

        //$response = array('status' => 'success-msg', 'message' => 'Record added successfully.');
        //$this->flashMessenger()->addMessage($response);
        // your code here ...
        $view = new ViewModel ();
        $view->setTerminal(true);
        return $view;
    }

    /**
     *
     * @return type
     */
    public function deleteremotiiAction() {
        $id = $this->params()->fromRoute('id', 0);
        $deleteId = $this->params()->fromRoute('id2', 0);
        if (!$id) {
            return $this->redirect()->toUrl(BASE_URL . "/admin/index/serviceproviderdetail/" . $id);
        }

        // Model function to delete the remotii and its associated data
        $delUser = $this->_modelUsers->deleteRemotii($deleteId);
        if ($delUser) {
            $response = array(
                'status' => 'success-msg',
                'message' => 'Record deleted successfully.'
            );
            $this->flashMessenger()->addMessage($response);
            return $this->redirect()->toUrl(BASE_URL . "/admin/index/serviceproviderdetail/" . $id);
        }
    }

    /**
     *
     * @return \Zend\View\Model\ViewModel
     */
    public function listremotiiAction() {
        $remotiiData = $this->_modelUsers->getRemotiisList();

        $ServiceProviderData = $this->_modelSP->fetchAll();
        $flashMessenger = $this->flashMessenger();
        if ($flashMessenger->hasMessages()) {
            $response = $flashMessenger->getMessages();
        }
        return new ViewModel(array(
            'response' => $response [0],
            'data' => $remotiiData,
            'SpData' => $ServiceProviderData
        ));
    }

    /**
     *
     * @return \Zend\View\Model\ViewModel
     */
    public function listofflineremotiiAction() {
        $remotiiData = $this->_modelUsers->getOfflineRemotiisList();

        $ServiceProviderData = $this->_modelSP->fetchAll();
        $flashMessenger = $this->flashMessenger();
        if ($flashMessenger->hasMessages()) {
            $response = $flashMessenger->getMessages();
        }
        return new ViewModel(array(
            'response' => $response [0],
            'data' => $remotiiData,
            'SpData' => $ServiceProviderData
        ));
    }

    public function updateremotiiAction() {
        $id = $this->params()->fromRoute('id', 0);
        if (!$id) {
            return $this->redirect()->toUrl(BASE_URL . "/admin/index/listremotii/" . $id);
        }
        //  _pre($id);
        $updRemo = $this->_modelUsers->updateRemotii($id);
    }

    /**
     *
     * @return type
     */
    public function deleteremotiilistAction() {
        $loggedInUserId = $this->getLoggedInUserId();
        $id = $this->params()->fromRoute('id', 0);
        if (!$id) {
            return $this->redirect()->toUrl(BASE_URL . "/admin/index/listremotii");
        }
        $SharePersonInfo = $this->_modelUsers->getSharedInfo($id);
        $chainedEvents = $this->_modelUsers->getRemotiiChainedEvent($id);
        $remotiiData = $this->_modelUsers->getRemotiiData($id);
        // Model function to delete the remotii and its associated data
        $delUser = $this->_modelUsers->deleteRemotii($id);
        if ($delUser) {
            if (count($chainedEvents) > 0) {

                $email = explode($remotiiData['notification_email'], ',');
                $email = explode(',', $remotiiData['notification_email']);

                if (count($SharePersonInfo) > 0) {
                    $s = count($email);
                    foreach ($SharePersonInfo as $share => $sharVal) {
                        $email[$s] = $sharVal->email;
                        $s++;
                    }
                }

//                //email notification if chained evnt is set
                foreach ($email as $mail) {
                    if (!empty($mail)) {
                        $domain_name = substr(strrchr($mail, "@"), 1);
                        if (in_array($domain_name, $numbersList)) {
                            $viewTemplate = 'tpl/email/remotii-delete-text';
                        } else {
                            $viewTemplate = 'tpl/email/remotii-delete-mail';
                        }
                        $values = array(
                            'message' => $remotiiData['remotii_name'] . ' has been removed',
                            'remotii_name' => $remotiiData['remotii_name'],
                            'chainedEvents' => $chainedEvents
                        );
//
                        $subject = '[Remotii] ' . $remotiiData['remotii_name'] . ' Removed';
                        $mailService = $this->getServiceLocator()->get('goaliomailservice_message');
                        if (in_array($domain_name, $numbersList)) {
                            $message = $mailService->createTextMessage(ADMIN_EMAIL, "$mail", $subject, $viewTemplate, $values);
                        } else {
                            $message = $mailService->createHtmlMessage(ADMIN_EMAIL, "$mail", $subject, $viewTemplate, $values);
                        }

//
                        try {
                            $mailService->send($message);
                        } catch (\Exception $e) {
                            $msg = $e->getMessage();
                            error_log(json_encode(array(
                             'date' => date('Y-m-d H:i:s'),
                             'method' => 'deleteremotiilistAction',
                                'macId' => $data->mac_address,
                                'message' => 'Mail not sent',
                                'email' => $mail,
                                'exception' => $msg
                            ))."\n", 3, '/var/www/remotii/public/cronNotification.fn.log');
                        }
                    }
                }
            }
            $response = array(
                'status' => 'success-msg',
                'message' => 'Record deleted successfully.'
            );
            $this->flashMessenger()->addMessage($response);
            return $this->redirect()->toUrl(BASE_URL . "/admin/index/listremotii");
        }
    }

    public function clearremotiitokenAction() {
        $id = $this->params()->fromRoute('id', 0);
        $url = $this->params()->fromQuery('des');
        if ($url == listserviceprovider) {
            $clearToken = $this->_modelUsers->clearRemotiiToken($id);
            if ($clearToken) {
                $response = array(
                    'status' => 'success-msg',
                    'message' => 'Token cleared successfully.'
                );
                $this->flashMessenger()->addMessage($response);
                return $this->redirect()->toUrl(BASE_URL . "/admin/index/listserviceprovider");
            }
        }
        if ($url == listremotii) {
            $clearToken = $this->_modelUsers->clearRemotiiToken($id);
            if ($clearToken) {
                $response = array(
                    'status' => 'success-msg',
                    'message' => 'Token cleared successfully.'
                );
                $this->flashMessenger()->addMessage($response);
                return $this->redirect()->toUrl(BASE_URL . "/admin/index/listremotii");
            }
        }
    }

    public function suspendremotiiAction() {
        $id = $this->params()->fromRoute('id', 0);
        if (!$id) {
            return $this->redirect()->toUrl(BASE_URL . "/admin/index/listremotii");
        }

        // Model function to delete the remotii and its associated data
        $delUser = $this->_modelUsers->suspendRemotii($id);
        if ($delUser) {
            $response = array(
                'status' => 'success-msg',
                'message' => 'Record updated successfully.'
            );
            $this->flashMessenger()->addMessage($response);
            return $this->redirect()->toUrl(BASE_URL . "/admin/index/listremotii");
        }
    }

    public function activateremotiiAction() {
        $id = $this->params()->fromRoute('id', 0);
        if (!$id) {
            return $this->redirect()->toUrl(BASE_URL . "/admin/index/listremotii");
        }

        // Model function to delete the remotii and its associated data
        $delUser = $this->_modelUsers->activateRemotii($id);
        if ($delUser) {
            $response = array(
                'status' => 'success-msg',
                'message' => 'Record updated successfully.'
            );
            $this->flashMessenger()->addMessage($response);
            return $this->redirect()->toUrl(BASE_URL . "/admin/index/listremotii");
        }
    }

    public function iajaxAction() {
        $viewmodel = new ViewModel();
        $request = $this->getRequest();
        $response = $this->getResponse();

        $post = array_merge_recursive(
                $this->getRequest()->getPost()->toArray(), $this->getRequest()->getFiles()->toArray()
        );

        $action = $post['action'];
        $params = $post['params'];
        if (method_exists($this, $action)) {
            $result = @call_user_func_array(array($this,$action), $params);
        } else {
            $result = array('status' => 'FAIL', 'result' => 'Action not exists');
        }

        return $response->setContent(\Zend\Json\Json::encode($result));
    }

    public function addNoteToAdmin($params,$p1) {
        // var_dump($params.$p1);exit;
        $status = $this->_modelUsers->addNoteFromAdminToRemotti(array('r_id' => $params, 'note' => $p1));

        return $data = array(
            'status' => 'success',
        );
    }

    public function addNoteToSP($params) {
        $status = $this->_modelSP->addNoteFromAdminToSP(array('r_id' => $params['rsp_id'], 'note' => $params['note']));

        return $data = array(
            'status' => 'success',
        );
    }

    protected function changeServiceProvider($params) {
        $status = "error";
        $remotiiId = $params['remotii_id'];
        $serviceProviderId = $params['service_provider_id'];
        if ($this->_modelSP->updateServiceProvider($remotiiId, $serviceProviderId)) {
            $status = "success";
            $message = "service provider updated successfully";
        } else {
            $message = "Error in updating service provider";
        }
        return $data = array(
            'status' => $status,
            'result' => $message,
            'params' => $params
        );
    }

    // //////////////////////////////////////////////////////////////////////////
    protected function isUserLoggedIn() {
        return $this->zfcUserAuthentication()->hasIdentity();
    }

    protected function getLoggedInUserId() {
        if ($this->zfcUserAuthentication()->hasIdentity()) {
            return $this->zfcUserAuthentication()->getIdentity()->getId();
        } else {
            return false;
        }
    }

    public function paymentsAction() {
        if ($this->getRequest()->isPost()) {
            $fromDate = strtotime($this->getRequest()->getPost('fromDate'));
            $toDate = strtotime($this->getRequest()->getPost('toDate'));

            $payments = $this->_modelUsers->getPayments(array(
                'toDate' => $toDate,
                'fromDate' => $fromDate
            ));
        } else {
            $fromDate = strtotime('-1 month');
            $toDate = strtotime(date('m/d/Y'));

            $payments = $this->_modelUsers->getPayments(array(
                'toDate' => $toDate,
                'fromDate' => $fromDate
            ));
        }
        return new ViewModel(array(
            'fromDate' => $fromDate ? date('m/d/Y', $fromDate) : "",
            'toDate' => $toDate ? date('m/d/Y', $toDate) : "",
            'payments' => $payments
        ));
    }

    /**
     *
     * @return \Zend\View\Model\ViewModel
     */
    public function settingsAction() {

        $flashMessenger = $this->flashMessenger();
        if ($flashMessenger->hasMessages()) {
            $responseData = $flashMessenger->getMessages();
        }

        $post_array = $this->getRequest()->getPost()->toArray();

        if (!empty($post_array)) {

            $status = false;
            $post_array['contracted_price'] = ($post_array['contracted_price'] == "" ? 0 : $post_array['contracted_price']);
            if ($post_array['contracted_price'] >= 0) {
                if (!is_numeric($post_array['contracted_price'])) {
                    $errorResponse ['contracted_price'] = 1;
                    $status = true;
                }
            } else {
                $errorResponse ['contracted_price'] = 2;
                $status = true;
            }

            if ($status) {
                $errorResponse ['0'] = (object) array(
                            'contracted_price' => $post_array ['contracted_price'],
                );

                $this->flashMessenger()->addMessage($errorResponse);
                return $this->redirect()->toUrl(BASE_URL . "/admin/index/settings");
            }

            try {


                $this->_modelUsers->updateAdminSettings($post_array);
                $errorResponse ['1'] = (object) array(
                            'status' => 'success-msg',
                            'message' => 'Settings updated successfully.'
                );
                $this->flashMessenger()->clearCurrentMessages();
                $this->flashMessenger()->addMessage($errorResponse);
                return $this->redirect()->toUrl(BASE_URL . "/admin/index/settings");
            } catch (\Exception $e) {
                $errorResponse ['1'] = (object) array(
                            'status' => 'error-msg',
                            'message' => 'Error in update operation.'
                );
                $this->flashMessenger()->clearCurrentMessages();
                $this->flashMessenger()->addMessage($errorResponse);
            }
        } else {
            $data = $this->_modelUsers->getAdminSettings();
        }
        return new ViewModel(array(
            'data' => $data,
            'responseData' => $responseData [0]
        ));
    }

    /**
     *
     * @return \Zend\View\Model\ViewModel
     */
    public function changecpriceAction() {
        $id = $this->params()->fromRoute('id', 0);

        $amount = $_REQUEST ['postdata'];
        $data = $this->_modelUsers->changeContractedPrice($id, $amount);
        // your code here ...
        $view = new ViewModel ();
        $view->setTerminal(true);
        return $view;
    }

    /**
     *
     * @return \Zend\View\Model\ViewModel
     */
    public function changeremotiistatusAction() {
        $status = $this->params()->fromRoute('id', 0);

        if ($status) {
            $status = SUSPENDED_BY_ADMIN;
        } else {
            $status = ACTIVE;
        }
        $postdata = $_REQUEST ['postdata'];
        $rIdsArr = explode(',', $postdata);
        $data = $this->_modelUsers->changeRemotiiStatus($status, $postdata);
        $userIds = $this->_modelSP->getUserIdsByRemotiiIds($rIdsArr);
        foreach ($userIds as $userId) {
            $this->_modelSP->setDefaultRemotiiRandomly($userId['user_id']);
        }
        die('done..');
    }

    public function updateFirmware($params) {
        $text = $params['text'];
        $mac_add = $params['mac_add'];
        if ($this->_modelSP->updateFirmware($text, $mac_add)) {
            $status = "success";
            $message = "Firmware updated successfully";
        } else {
            $message = "Error in updating Firmware";
        }
        return $data = array(
            'status' => $status,
            'result' => $message,
            'params' => $params
        );
    }

    public function checkRemotiiFirmwareStatus($params) {
        $status = "error";
        $text = $params['text'];
        $mac_add = $params['mac_add'];
        if ($newFirmware = $this->_modelSP->checkFirmware($text, $mac_add)) {
            $status = "success";
            $message = $newFirmware;
        } else {
            $message = "Firmware not updated yet.";
        }
        return $data = array(
            'status' => $status,
            'result' => $message,
            'params' => $params
        );
    }

    public function cronRemotiiEventAction() {
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '-1');
        $mainStartTime = time();
        $serverTime = date('Y-m-d H:i:00', time());
        $currentTime = strtotime(date('Y-m-d H:i:00', time()));

        $cronLastExecutionTime = $this->_modelUsers->getLastEventSchedulerCronLogTime();
        if (empty($cronLastExecutionTime)) {
            $cronLastExecutionTime = $currentTime - 60;
        }
        $minutes = ceil(($currentTime - $cronLastExecutionTime) / 60);
        if ($minutes > 60) {
            $minutes = 60;
            //$mainStartTime = $mainStartTime - 3600;
        }

        $cronLogId = $this->_modelUsers->insertEventSchedulerCronLog($mainStartTime, $mainStartTime);
        $minutes = 1;
        $eventProcessed = array();
        $q = "";
        for ($i = 1; $i <= $minutes; $i++) {
            $cronTimeToBeExecuted = strtotime(date('Y-m-d H:i:00', $cronLastExecutionTime)) + ($i * 60);
            if ($cronTimeToBeExecuted > $currentTime) {
                break;
            }
            $results = $this->_modelUsers->getCronEventSchedulerData($cronTimeToBeExecuted);

            $fullDate = date('Y-m-d H:i:00', $cronTimeToBeExecuted);
//            echo $q.="
//
//SELECT
//            es.id,
//            CONV(es.input_bits, 10, 2) as input_bits,
//            ep.dout_set,
//            ep.dout_clr,
//            es.mac_address,
//            es.remotii_id,
//            CONV(r.remotii_last_input_status, 10, 2) as remotii_last_input_status,
//            ep.dout_tgl,
//            ep.tx_type,
//            uoc.pulse_width
//            from event_schedular_pins ep
//            INNER JOIN event_scheduler es ON (es.id = ep.event_id)
//            INNER JOIN user_remotii_output_config uoc ON ep.output_config_id = uoc.config_id
//            INNER JOIN remotii as r ON(es.remotii_id = r.remotii_id)
//            INNER JOIN user_remotii as ur ON(es.remotii_id = ur.remotii_id)
//            WHERE
//            (
//                (
//                    occurence_type=0 AND occurence_date = DAY('" . $fullDate . "' + INTERVAL (ur.offset) MINUTE)
//                    AND
//                    occurence_month=MONTH('" . $fullDate . "' + INTERVAL (ur.offset) MINUTE)
//                    AND
//                    occurence_year=YEAR('" . $fullDate . "' + INTERVAL (ur.offset) MINUTE)
//                )
//                OR
//                (
//                    occurence_type=1
//                )
//                OR
//                (
//                    occurence_type=2
//                    AND
//                    occurence_days LIKE CONCAT('%',DATE_FORMAT('" . $fullDate . "' + INTERVAL (ur.offset) MINUTE,'%a'),'%')
//                )
//                OR
//                (
//                    occurence_type=3
//                    AND
//                    occurence_date=DAY('" . $fullDate . "' + INTERVAL (ur.offset) MINUTE)
//                )
//                OR
//                (
//                    occurence_type=4
//                    AND
//                    occurence_date=DAY('" . $fullDate . "' + INTERVAL (ur.offset) MINUTE)
//                    AND
//                    occurence_month=MONTH('" . $fullDate . "' + INTERVAL (ur.offset) MINUTE)
//                )
//            )
//            AND
//            (
//                (
//                    condition_type= '-1'
//                )
//                OR
//                (
//                    condition_type=0
//                    AND
//
//                    CASE WHEN
//                    ((es.input_bits & r.remotii_last_input_status) ^ (es.input_bits & es.input_cond))  > 13
//                    THEN
//                    (
//                        (
//                            (
//                                (es.input_bits & r.remotii_last_input_status) ^ (es.input_bits & es.input_cond)
//                            )
//                            ^ (1)
//                         )
//                         &
//                         es.input_bits
//                      ) != 0
//                    WHEN
//                    ((es.input_bits & r.remotii_last_input_status) ^ (es.input_bits & es.input_cond) ) > 11
//                    THEN
//                    (((((es.input_bits & r.remotii_last_input_status) ^ (es.input_bits & es.input_cond) ) ^ (3)) & es.input_bits) != 0 )
//                    WHEN
//                    ((es.input_bits & r.remotii_last_input_status) ^ (es.input_bits & es.input_cond) ) > 7
//                    THEN
//                    (((((es.input_bits & r.remotii_last_input_status) ^ (es.input_bits & es.input_cond) ) ^ (7)) & es.input_bits) != 0 )
//                   ELSE
//                    (((((es.input_bits & r.remotii_last_input_status) ^ (es.input_bits & es.input_cond) ) ^ (15)) & es.input_bits) != 0 )
//                    END
//                )
//                OR
//                (
//                    condition_type=1
//                    AND
//                    (
//                        (es.input_bits & r.remotii_last_input_status) = (es.input_bits & es.input_cond)
//                    )
//                )
//
//            )
//            AND
//            event_status = 1
//            AND
//            occurence_time = DATE_FORMAT('" . $fullDate . "' + INTERVAL (ur.offset) MINUTE,'%h:%i %p') ";
            foreach ($results as $result) {
                $eventProcessed[] = $result->id;
                $this->_modelUsers->insertCronEventDataToOutbound($result->dout_set, $result->dout_clr, $result->remotii_id, $result->mac_address, $result->dout_tgl, $result->tx_type, $result->pulse_width * 1000);
            }
        }
        $totalExecutionTime = time() - $mainStartTime;
        $this->_modelUsers->updateEventSchedulerCronLog($cronLogId, "CRON --$cronLogId (SERVER TIME : " . $serverTime . ") EXECUTED IN " . $totalExecutionTime . " seconds. Events processed : " . implode(",", $eventProcessed) . "



");
        die("CRON --$cronLogId EXECUTED IN " . $totalExecutionTime . " seconds. Events processed : " . implode(",", $eventProcessed));
    }

    public function errorsAction() {
        $errorsArray = $this->_modelUsers->fetchAllErrors();
        return new ViewModel(array(
            'errorsArray' => $errorsArray,
        ));
    }

    public function clearErrorsAction() {
        $this->_modelUsers->clearErrors();
        return $this->redirect()->toUrl(BASE_URL . "/admin/index/errors");
    }

    public function get_client_ip() {

        $ipaddress = '';
        if ($_SERVER['HTTP_CLIENT_IP'])
            $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
        else if ($_SERVER['HTTP_X_FORWARDED_FOR'])
            $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
        else if ($_SERVER['HTTP_X_FORWARDED'])
            $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
        else if ($_SERVER['HTTP_FORWARDED_FOR'])
            $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
        else if ($_SERVER['HTTP_FORWARDED'])
            $ipaddress = $_SERVER['HTTP_FORWARDED'];
        else if ($_SERVER['REMOTE_ADDR'])
            $ipaddress = $_SERVER['REMOTE_ADDR'];
        else
            $ipaddress = 'UNKNOWN';
        return $ipaddress;
    }

}
