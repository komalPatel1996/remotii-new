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
        // die('hi');
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

        $post_array = $this->getRequest()->getPost()->toArray();

        if (!empty($post_array)) {
            // Form validation function called
            $statusData = $this->validateServiceProviderForm($post_array, $id, 2);
            if ($statusData) {
                return $this->redirect()->toUrl(BASE_URL . "/admin/index/addserviceprovider");
            }

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
                        $err = $body ['error'];
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
                $post_array ['name_on_bank'] = $post_array ['nob_firstname'] . ' ' . $post_array ['nob_lastname'];
                // Creating Service Provider Bank Account Info on Stripe START
                if ($post_array ['allow_end_user_billing'] == 1 && $post_array ['routing_number'] != '' && $post_array ['account_type'] != '' && $post_array ['account_number'] != '' && $post_array ['name_on_bank'] != '') {
                    $spBankInfoStripe = $this->stripeMethod->createRecipientsBankAcc($post_array);
                }
                // Creating Service Provider Bank Account Info on Stripe END
            } catch (\Exception $e) {
                if ($e instanceof \Stripe_CardError or $e instanceof \Stripe_InvalidRequestError or $e instanceof \Stripe_AuthenticationError or $e instanceof \Stripe_ApiConnectionError or $e instanceof \Stripe_Error) {
                    // Error occured for
                    $body = $e->getJsonBody();
                    $err = $body ['error'];
                    // Since it's a decline, Stripe_CardError will be caught
                    $this->flashMessenger()->addMessage(array(
                        'errorBillingDetails' => 3
                    ));
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
                    'acc_status' => $postData ['acc_status'],
                    'routing_number' => $postData ['routing_number'],
                    'account_type' => $postData ['account_type'],
                    'account_number' => $postData ['account_number'],
                    'nob_firstname' => $postData ['nob_firstname'],
                    'nob_lastname' => $postData ['nob_lastname']
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
                    'routing_number' => $postData ['routing_number'],
                    'account_type' => $postData ['account_type'],
                    'account_number' => $postData ['account_number'],
                    'nob_firstname' => $postData ['nob_firstname'],
                    'nob_lastname' => $postData ['nob_lastname']
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
                    if ($userData [0]->authorizenet_profile_id && !is_null($userData [0]->authorizenet_profile_id && !empty($userData [0]->authorizenet_profile_id))) {
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
                die('hii');
                if ($userData [0]->authorizenet_profile_id && !is_null($userData [0]->authorizenet_profile_id && !empty($userData [0]->authorizenet_profile_id))) {
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
        $post_array ['name_on_bank'] = $post_array ['nob_firstname'] . ' ' . $post_array ['nob_lastname'];
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
                'status' => 'successmsg',
                'message' => 'Record deleted successfully.'
            );
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

        $authPaymentProfile = $this->_modelUsers->getAuthPaymentProfileDetails($id);
        $profileId = $authPaymentProfile->authorizenet_profile_id;
        // $paymentProfileId = $authPaymentProfile->payment_profile_id;
        // $shippingProfileId = $authPaymentProfile->shipping_profile_id;
        $status = 'FAILED';

        if ($profileId != '') {

            // $accumulatedAmount = $this->_modelUsers->getSPAccumulatedAmount($id);
            // $amount = $amount + $accumulatedAmount;
            // $transData = $this->cimMethod->capturePayment($profileId, $paymentProfileId, $shippingProfileId, $amount); //OLD CIM
            try {
                $transData = $this->stripeMethod->chargeCustomer($profileId, $amount);
            } catch (\Exception $e) {
                $errmsg = $e->getMessage();
            }

            // Save transaction data
            $payment_source = 'CC';
            $payment_flag = 'CR';
            $lastInsertedId = $this->_modelUsers->saveTransactionData($transData, $id, $amount, $payment_source, $payment_flag);
            // Change service provider account status
            if ($transData->paid == 1) {
                $status = 'OK';
                $this->_modelUsers->updateAccStatus($id, $lastInsertedId);
                $this->_modelUsers->clearSPAccumulatedAmount($id);
            } else {
                $this->_modelUsers->updateSPLastPaymentStatId($id, $lastInsertedId);
            }
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

        if ($profileId != '') {

            try {
                $transData = $this->stripeMethod->chargeCustomer($profileId, $amount);
            } catch (\Exception $e) {
                $errmsg = $e->getMessage();
            }

            // Save transaction data
            $payment_source = 'CC';
            $payment_flag = 'CR';
            $lastInsertedId = $this->_modelUsers->saveTransactionData($transData, $id, $amount, $payment_source, $payment_flag);
            // Change service provider account status
            if ($transData->paid == 1) {
                $status = 'OK';
                $this->_modelUsers->activeEndUserAccStatus($id);
                $this->_modelUsers->clearEndUserAccumulatedAmount($id);
            } else {
                $this->_modelUsers->updateEULastPaymentStatId($id, $lastInsertedId);
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

    public function cronBillServiceProvidersAction() {
        if (BILLING_DAY_OF_MONTH == date('d', time())) {
            $amountDetails = $this->_modelUsers->getSpsAmountDetails();
            $transData = "";
            foreach ($amountDetails as $amountDetail) {
                $transData = "";
                $id = $amountDetail->service_provider_id;
                $amount = $amountDetail->contracted_price_total; // + $amountDetail->servie_fee;

                $authPaymentProfile = $this->_modelUsers->getAuthPaymentProfileDetails($id);
                $profileId = $authPaymentProfile->authorizenet_profile_id;
                // $paymentProfileId = $authPaymentProfile->payment_profile_id;
                // $shippingProfileId = $authPaymentProfile->shipping_profile_id;

                if ($profileId != '') {
                    try {
                        // $transData = $this->cimMethod->capturePayment($profileId, $paymentProfileId, $shippingProfileId, $amount);
                        $accumulatedAmount = $this->_modelUsers->getSPAccumulatedAmount($id);
                        $totalAmountToCharge = $accumulatedAmount + $amount;
                        if ($totalAmountToCharge) {
                            $transData = $this->stripeMethod->chargeCustomer($profileId, $totalAmountToCharge);
                        } else {
                            continue;
                        }
                        // Change service provider account status
                    } catch (\Stripe_CardError $e) {
                        // Error occured for
                        $body = $e->getJsonBody();
                        $err = $body ['error'];
                        error_log(json_encode(array(
                            'spId' => $id,
                            'error' => $err ['message']
                                )), 3, '/var/www/remotii/public/cronBillServiceProvidersAction.fn.log');
                    }

                    // Save transaction data
                    $payment_source = 'CC';
                    $payment_flag = 'CR';
                    $lastInsertedId = $this->_modelUsers->saveTransactionData($transData, $id, $totalAmountToCharge, $payment_source, $payment_flag);

                    if ($transData->paid == 1) {
                        $this->_modelUsers->updateAccStatus($id, $lastInsertedId);
                        $this->_modelUsers->clearSPAccumulatedAmount($id);
                    } else {
                        $this->_modelUsers->updateSPLastPaymentStatId($id, $lastInsertedId);
                        $this->_modelUsers->updateAccStatusToDelinquent($id);
                        $this->_modelUsers->addSPAccumulatedAmount($id, $amount);
                    }
                }
                error_log(json_encode($transData), 3, '/var/www/remotii/public/cronBillServiceProvidersAction.fn.log');
            }

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

                    $endUserAccumulatedAmount = $this->_modelUsers->getEndUserAccumulatedAmount($userId);
                    $customerNeedToPay = $endInfo->amount * $endInfo->spCount;
                    $totalEndUserAmountToCharge = $endUserAccumulatedAmount + $customerNeedToPay;

                    $transData = $this->stripeMethod->chargeCustomer($endInfo->pid, $totalEndUserAmountToCharge);
                } catch (\Exception $e) {
                    if ($e instanceof \Stripe_CardError or $e instanceof \Stripe_InvalidRequestError or $e instanceof \Stripe_AuthenticationError or $e instanceof \Stripe_ApiConnectionError or $e instanceof \Stripe_Error) {
                        // Error occured for
                        $body = $e->getJsonBody();
                        $err = $body ['error'];
                        error_log(json_encode(array(
                            'userId' => $userId,
                            'error' => $err ['message']
                                )), 3, '/var/www/remotii/public/cronBillServiceProvidersAction.fn.log');
                    }
                }

                // Save transaction data
                $payment_source = 'CC';
                $payment_flag = 'CR';
                $lastInsertedId = $this->_modelUsers->saveTransactionData($transData, $userId, $totalEndUserAmountToCharge, $payment_source, $payment_flag);
                // Change service provider account status


                error_log(json_encode($transData), 3, '/var/www/remotii/public/cronBillServiceProvidersAction.fn.log');

                if ($transData->paid == 1) {
                    // $customSpArr[$i][$endInfo->service_provider_id] = $endInfo->amount_to_pay;
                    // $i++;
                    $this->_modelUsers->updateAccStatus($userId, $lastInsertedId);
                    $this->_modelUsers->clearEndUserAccumulatedAmount($userId);
                } else {
                    $this->_modelUsers->updateEULastPaymentStatId($id, $lastInsertedId);
                    $this->_modelUsers->updateUserAccStatusToDelinquent($userId);
                    $this->_modelUsers->addEndUserAccumulatedAmount($userId, $customerNeedToPay);
                }

                $totalAmountToPayToSP [$endInfo->service_provider_id] ['stripe_acc_id'] = $endInfo->stripe_acc_id;
                $totalAmountToPayToSP [$endInfo->service_provider_id] ['totalAmountToPay'] = $endUserAccumulatedAmount + $totalAmountToPayToSP [$endInfo->service_provider_id] ['totalAmountToPay'] + $endInfo->spCount * ($endInfo->amount - ($endInfo->amount * $endInfo->service_fee / 100));
            }

            foreach ($totalAmountToPayToSP as $spid => $sp) {
                try {
                    // Pay to SP
                    $payToSPAmount = ($sp ['totalAmountToPay'] * 100); // $endInfo->spCount*($endInfo->amount - ($endInfo->amount*$endInfo->service_fee/100));
                    $transData2 = $this->stripeMethod->createTransfer($payToSPAmount, $sp ['stripe_acc_id']);
                } catch (\Exception $e) {
                    if ($e instanceof \Stripe_CardError or $e instanceof \Stripe_InvalidRequestError or $e instanceof \Stripe_AuthenticationError or $e instanceof \Stripe_ApiConnectionError or $e instanceof \Stripe_Error) {
                        // Error occured for
                        $body = $e->getJsonBody();
                        $err = $body ['error'];
                        error_log(json_encode(array(
                            'spid' => $spid,
                            'error' => $err ['message']
                                )), 3, '/var/www/remotii/public/cronBillServiceProvidersAction.fn.log');
                    }
                }

                $payment_source = 'Stripe';
                $payment_flag = 'DR';
                $lastInsertedId = $this->_modelUsers->saveTransactionData($transData2, $spid, - $sp ['totalAmountToPay'], $payment_source, $payment_flag);
                error_log(json_encode($transData2), 3, '/var/www/remotii/public/cronBillServiceProvidersAction.fn.log');
            }

            /*
             * // Calculating the Total amount of the service provider to pay by admin foreach($customSpArr as $val) { foreach($val as $k => $v) { if(array_key_exists($k, $val)) { $cust[$k] = $cust[$k] + $v; } } } // // // $sreviceProviderToPay = array_keys($cust); $strSreviceProviderToPay = implode(',', $sreviceProviderToPay); // Function defined to pay SP by Admin Using Stripe $amountSpDetails = $this->_modelUsers->getAmountPaidToSP($strSreviceProviderToPay); foreach($amountSpDetails as $dataAmount) { $transData = $this->stripeMethod->createTransfer($cust[$dataAmount->service_provider_id], $dataAmount->stripe_acc_id); $payment_source = 'Stripe'; $payment_flag = 'DR'; $lastInsertedId = $this->_modelUsers->saveTransactionData($transData, $transData->service_provider_id, -$cust[$dataAmount->service_provider_id], $payment_source, $payment_flag); error_log(json_encode($transData),3,'/var/www/remotii/public/cronBillServiceProvidersAction.fn.log'); }
             */

            $view = new ViewModel(array(
                'data' => $spData
            ));
            $view->setTerminal(true);
            return $view;
        }
    }

    /**
     *
     * @return \Zend\View\Model\ViewModel
     */
    public function cronNotificationMailAction() {
        $details = $this->_modelUsers->getMailNotificationData();
        $dataCust = array();
        $dataCust[] = 0;
        foreach ($details as $data) {
            $uinfo = $this->_modelClient->getEUByMac($data->mac_address);
            $bitInfo = $this->_modelUsers->getPinLabelInfo($data->mac_address, $data->state);

            $inputConfig = $this->_modelUsers->getInputConfig($data->mac_address);
            $outputConfig = $this->_modelUsers->getOutputConfig($data->mac_address);

            //  Input
            $inactive = 0;
            if ($data->state & 1) {
                $pinNumStatus = 1;
            } else {
                $inactive = 1;
            }
            if ($data->state & 2) {
                $pinNumStatus = 2;
            } else {
                $inactive = 1;
            }
            if ($data->state & 4) {
                $pinNumStatus = 3;
            } else {
                $inactive = 1;
            }
            if ($data->state & 8) {
                $pinNumStatus = 4;
            } else {
                $inactive = 1;
            }

            //  Output
            if ($data->outstate & 1) {
                $pinOutStatus = 1;
            }
            if ($data->outstate & 2) {
                $pinOutStatus = 2;
            }
            if ($data->outstate & 4) {
                $pinOutStatus = 3;
            }

            if (!empty($data->email)) {
                $viewTemplate = 'tpl/email/spmail';
                $values = array(
                    'message' => 'Pin status has been changed',
                    'uinfo' => $uinfo,
                    'bitInfo' => $bitInfo[0],
                    'inactive' => $inactive,
                    'pinNumStatus' => $pinNumStatus,
                    'inputConfig' => $inputConfig,
                    'outputConfig' => $outputConfig,
                    'inState' => $data->state,
                    'outState' => $data->out,
                    'pinOutStatus' => $pinOutStatus,
                    'outState' => $data->outstate
                );
                $subject = 'No Reply. Remotii Notification - ' . $bitInfo[0]['remotii_name'];
                $mailService = $this->getServiceLocator()->get('goaliomailservice_message');
                $message = $mailService->createHtmlMessage(ADMIN_EMAIL, "$data->email", $subject, $viewTemplate, $values);
                //$mailService->send ( $message );
                $dataCust[] = $data->nid;

                try {
                    $mailService->send($message);
                } catch (\Exception $e) {
                    $msg = $e->getMessage();
                    error_log(json_encode(array(
                        'macId' => $data->mac_address,
                        'message' => 'Mail not sent',
                        'email' => $data->email,
                        'exception' => $msg
                            )), 3, '/var/www/remotii/public/cronNotification.fn.log');
                }
            }
        }
        $detailsM = $this->_modelUsers->changeMailNotificationStatus($dataCust);

        $view = new ViewModel ();
        $view->setTerminal(true);
        return $view;
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

        $remVal = $_REQUEST ['postdata'];
        $data = $this->_modelUsers->saveRemotiiMac($remVal, $id, $loggedInUserId);
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

        $flashMessenger = $this->flashMessenger();
        if ($flashMessenger->hasMessages()) {
            $response = $flashMessenger->getMessages();
        }
        return new ViewModel(array(
            'response' => $response [0],
            'data' => $remotiiData
        ));
    }

    /**
     *
     * @return type
     */
    public function deleteremotiilistAction() {
        $id = $this->params()->fromRoute('id', 0);
        if (!$id) {
            return $this->redirect()->toUrl(BASE_URL . "/admin/index/listremotii");
        }

        // Model function to delete the remotii and its associated data
        $delUser = $this->_modelUsers->deleteRemotii($id);
        if ($delUser) {
            $response = array(
                'status' => 'success-msg',
                'message' => 'Record deleted successfully.'
            );
            $this->flashMessenger()->addMessage($response);
            return $this->redirect()->toUrl(BASE_URL . "/admin/index/listremotii");
        }
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

}
