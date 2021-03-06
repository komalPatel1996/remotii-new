<?php

/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2013 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace RemotiiDefault\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use RemotiiModels\Model\Client as MClient;
use RemotiiModels\Model\ManageUsers as mUsers;
use RemotiiModels\Model\ServiceProvider as modelSP;
use Zend\View\Model\ViewModel;
use Zend\Validator\EmailAddress as emailIdValidation;
//  Payment module used
use CimPayment\Payment\CimMethod as cimMethod;
use StripePayment\Payment\StripeMethod as stripeMethod;
use Zend\Session\Container;
use RemotiiDefault\Form\EventSchedulerForm;
use RemotiiDefault\Form\ChainedEventSchedulerForm;
use Zend\Form\Form;
use Zend\Stdlib\ResponseInterface as Response;
use Zend\Stdlib\Parameters;
use GoalioForgotPassword\Service\Password as PasswordService;
use GoalioForgotPassword\Options\ForgotControllerOptionsInterface;

class IndexController extends AbstractActionController {

    private $_clientModel;
    private $_modelUsers;
    public $stripeMethod;
    private $_modelSP;
    private $_helper;

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
        //$this->events->attach('dispatch', array($this, 'postDispatch'), -100);
    }

    public function preDispatch() {
        $db = $this->getServiceLocator()->get('db');
        $this->_clientModel = new MClient($db);
        $this->_modelUsers = new mUsers($db);
        $this->_modelSP = new modelSP($db);

        //  Payment object initialised
        $this->cimMethod = new cimMethod();

        //  Stripe Payment object initialised
        $this->stripeMethod = new stripeMethod();
    }

    public function indexAction() {
        return new ViewModel(array(
            'userData' => ''
        ));
    }

    /**
     * 
     * @return \Zend\View\Model\ViewModel
     */
    public function profileAction() {

        $uid = $this->getLoggedInUserId();
        $post_array = $this->getRequest()->getPost()->toArray();


        //if(empty($responseData)){

        if (!empty($post_array)) {
            //  Form validation function called
            $statusData = $this->validateManageAdminForm($post_array, $uid, 3);
            if ($statusData) {
                return $this->redirect()->toUrl(BASE_URL . "/client/profile");
            }
        }

        $flashMessenger = $this->flashMessenger();
        if ($flashMessenger->hasMessages()) {
            $responseData = $flashMessenger->getMessages();
        }


        if (!$responseData[0]['errorStatus']) {

            //  Add case
            if (!empty($post_array)) {
                try {
                    //  Save user into DB
                    $createdUid = $this->_clientModel->updateUser($post_array, $uid);

                    //                    if ($post_array['card_holder'] && $post_array['card_number'] && $post_array['cvv'] && $post_array['expMonth'] && $post_array['expYear']
                    //                    ) {
                    //                        ////
                    //                        $stripeToken = $this->stripeMethod->createToken($post_array);
                    //                        if ($stripeToken->id <> '') {
                    //                            $customerCreateParams = array('card' => $stripeToken->id,
                    //                                'description' => $post_array['fName'] . ' ' . $post_array['lName'],
                    //                                'email' => $post_array['emailId']);
                    //                            $customerData = $this->stripeMethod->createCustomer($customerCreateParams);
                    //                        }
                    //                        ////
                    //                        if ($createdUid <> '') {
                    //                            //  Save customer payment profile into DB
                    //                            $this->_clientModel->saveUserPaymentProfile($createdUid, $customerData->id, $paymentProfileId = null, $shippingAddrsId = null, $post_array['card_holder']);
                    //                        }
                    /*
                      $authPaymentProfile = $this->_modelUsers->getAuthPaymentProfileDetails($uid);
                      $delProfileId = $authPaymentProfile->authorizenet_profile_id;

                      if ($delProfileId > 0) {
                      //  First Delete the profile in case user need to update the CC info
                      $delStatus = $this->cimMethod->profileDelete( $delProfileId );
                      }

                      //  Create user Payment Profile
                      $profileId = $this->cimMethod->profileCreate();

                      if($profileId <> '') {

                      $paymentProfileId = $this->cimMethod->paymentProfileCreate( $profileId, $post_array );
                      if($paymentProfileId <> '' ) {
                      $shippingAddrsId = $this->cimMethod->shippingProfileCreate( $profileId, $post_array );
                      if($shippingAddrsId == '') {
                      $this->flashMessenger()->addMessage( array('errorBillingDetails' => 3, 'errorStatus' => 1) );
                      return $this->redirect()->toUrl( BASE_URL . "/client/profile" );
                      }
                      if( $createdUid <> '' ) {
                      //  Save customer payment profile into DB
                      $this->_clientModel->saveUserPaymentProfile($createdUid, $profileId, $paymentProfileId, $shippingAddrsId, $post_array['card_holder']);

                      }
                      }else{
                      $this->flashMessenger()->addMessage( array('errorBillingDetails' => 3, 'errorStatus' => 1) );
                      return $this->redirect()->toUrl( BASE_URL . "/client/profile" );
                      }
                      } else {
                      $this->flashMessenger()->addMessage( array('errorBillingDetails' => 3, 'errorStatus' => 1) );
                      return $this->redirect()->toUrl( BASE_URL . "/client/profile" );
                      }
                     */
                    //}

                    $errorResponse[1] = (object) array(
                                'status' => 'success-msg',
                                'message' => 'Profile updated successfully.'
                    );
                    $this->flashMessenger()->clearCurrentMessages();
                    $this->flashMessenger()->addMessage($errorResponse);
                    return $this->redirect()->toUrl(BASE_URL . "/client/profile");
                } catch (\Exception $e) {
                    $errorResponse[1] = (object) array(
                                'status' => 'error-msg',
                                'message' => 'Error while updating.'
                    );
                    $this->flashMessenger()->clearCurrentMessages();
                    $this->flashMessenger()->addMessage($errorResponse);
                }
            } else {
                //_pre($responseData);
                if ($responseData[1]['errorBillingDetails']) {
                    $pResponse = $responseData[1];
                }

                if (!empty($responseData[0][1]->status)) {
                    $message = $responseData[0];
                }
                $responseData[0] = $this->_clientModel->getClientById($uid);
            }
        }
        //}


        return new ViewModel(array(
            'responseData' => $responseData[0],
            'errdata' => $message,
            'pResponse' => array(
                $pResponse
            )
        ));
    }

    /**
     * 
     * @return \Zend\View\Model\ViewModel
     */
    public function registerAction() {
        $flashMessenger = $this->flashMessenger();
        if ($flashMessenger->hasMessages()) {
            $responseData = $flashMessenger->getMessages();
        }

        $post_array = $this->getRequest()->getPost()->toArray();
        if (!empty($post_array)) {
            //  Form validation function called
            $statusData = $this->validateManageAdminForm($post_array, $id, 3);
            if ($statusData) {
                return $this->redirect()->toUrl(BASE_URL . "/register");
            }
            $recaptcha = $post_array['g-recaptcha-response'];

            if (!empty($recaptcha)) {
                $google_url = "https://www.google.com/recaptcha/api/siteverify";
                $secret = GOOGLE_CAPTCHA_VERIFICATION_SECRET;
                $ip = $_SERVER['REMOTE_ADDR'];
                $url = $google_url . "?secret=" . $secret . "&response=" . $recaptcha . "&remoteip=" . $ip;
                $res = $this->getCurlData($url);
                $res = json_decode($res, true);

                //reCaptcha success check 
                if ($res['success']) {
                    
                } else {
                    $errorResponse['errCaptcha'] = 1;
                    $this->flashMessenger()->addMessage($errorResponse);
                    return $this->redirect()->toUrl(BASE_URL . "/register");
                }
            } else {
                $errorResponse['errCaptcha'] = 1;
                $this->flashMessenger()->addMessage($errorResponse);
                return $this->redirect()->toUrl(BASE_URL . "/register");
            }

            //  CAPTCHA CODE ADDED
            //            $request = $this->getRequest();
            //// Get out from the $_POST array the captcha part...  
            //            $captcha = $request->getPost('captcha');
            //// Actually it's an array, so both the ID and the submitted word  
            //// is in it with the corresponding keys  
            //// So here's the ID...  
            //            $captchaId = $captcha['id'];
            //// And here's the user submitted word...  
            //            $captchaInput = $captcha['input'];
            //// _pre($captcha);
            //// We are accessing the session with the corresponding namespace  
            //// Try overwriting this, hah!  
            //            $captchaSession = new Container('Zend_Form_Captcha_' . $captchaId);
            //
            //// To access what's inside the session, we need the Iterator  
            //// So we get one...
            //            $captchaIterator = $captchaSession->getIterator();
            //// And here's the correct word which is on the image...
            //            $captchaWord = $captchaIterator['word'];
            ////_pre($captchaIterator);
            ////$custData = strrev($captchaWord);
            //// Now just compare them...  
            //            if ($captchaInput == $captchaWord) {
            ////  OK
            //            } else {
            //                $errorResponse['errCaptcha'] = 1;
            //                $this->flashMessenger()->addMessage($errorResponse);
            //                return $this->redirect()->toUrl(BASE_URL . "/register");
            //            }
        }
        if (!empty($post_array)) {
            $createdUid = $this->_clientModel->createUser($post_array);
            $this->getServiceLocator()->get('zfcuser_user_service')->getAuthService()->getStorage()->write($createdUid);
            //  redirect after success full registration
            $_SESSION['SUCC_REG_MSG'] = 'Registration successfull.';
            return $this->redirect()->toUrl(BASE_URL . "/login");
        }

        return new ViewModel(array(
            'responseData' => $responseData[0],
            'errdata' => $responseData[0],
            'pResponse' => $responseData
        ));
    }

    /**
     * 
     * @return \Zend\View\Model\ViewModel
     */
    public function registerRspAction() {
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
                return $this->redirect()->toUrl(BASE_URL . "/register-rsp");
            }
        }
        if (!empty($post_array)) {
            // Form validation function called
            //            $statusData = $this->validateServiceProviderForm($post_array, $id, 2);
            //            if ($statusData) {
            //                return $this->redirect()->toUrl(BASE_URL . "/admin/index/addserviceprovider");
            //            }

            if ($post_array['shbillingDetails']) {
                // Secret key set
                try {
                    $stripeToken = $this->stripeMethod->createToken($_POST);
                    if ($stripeToken->id != '') {
                        $customerCreateParams = array(
                            'card' => $stripeToken->id,
                            'description' => $_POST['fName'] . ' ' . $_POST['lName'],
                            'email' => $_POST['emailId']
                        );
                        $customerData = $this->stripeMethod->createCustomer($customerCreateParams);
                    }
                } catch (\Exception $e) {
                    if ($e instanceof \Stripe_CardError or $e instanceof \Stripe_InvalidRequestError or $e instanceof \Stripe_AuthenticationError or $e instanceof \Stripe_ApiConnectionError or $e instanceof \Stripe_Error) {
                        // Error occured for
                        $body = $e->getJsonBody();

                        $errorResponse = $body['error'];
                        // Since it's a decline, Stripe_CardError will be caught
                        $errorResponse['errorBillingDetails'] = 3;

                        $this->flashMessenger()->addMessage($errorResponse);
                        return $this->redirect()->toUrl(BASE_URL . "/register-rsp");
                    }
                }

                /*
                 * // Create payment profile $profileId = $this->cimMethod->profileCreate($post_array['emailId']); if($profileId <> '') { $paymentProfileId = $this->cimMethod->paymentProfileCreate($profileId, $post_array); if($paymentProfileId <> '' ) { $shippingAddrsId = $this->cimMethod->shippingProfileCreate($profileId, $post_array); if($shippingAddrsId == ''){ $this->flashMessenger()->addMessage(array('errorBillingDetails' => 3)); return $this->redirect()->toUrl(BASE_URL . "/admin/index/addserviceprovider"); } }else{ $this->flashMessenger()->addMessage(array('errorBillingDetails' => 3)); return $this->redirect()->toUrl(BASE_URL . "/admin/index/addserviceprovider"); } } else { $this->flashMessenger()->addMessage(array('errorBillingDetails' => 3)); return $this->redirect()->toUrl(BASE_URL . "/admin/index/addserviceprovider"); }
                 */
            }

            try {
                //$post_array ['name_on_bank'] = $post_array ['nob_firstname'] . ' ' . $post_array ['nob_lastname'];
                // Creating Service Provider Bank Account Info on Stripe START
                if ($post_array['allow_end_user_billing'] == 1 && $post_array['routing_number'] != '' && $post_array['account_type'] != '' && $post_array['account_number'] != '' && $post_array['name_on_bank'] != '') {
                    $spBankInfoStripe = $this->stripeMethod->createRecipientsBankAcc($post_array);
                }
                // Creating Service Provider Bank Account Info on Stripe END
            } catch (\Exception $e) {
                if ($e instanceof \Stripe_CardError or $e instanceof \Stripe_InvalidRequestError or $e instanceof \Stripe_AuthenticationError or $e instanceof \Stripe_ApiConnectionError or $e instanceof \Stripe_Error) {
                    // Error occured for
                    $body = $e->getJsonBody();
                    $errorResponse = $body['error'];
                    // Since it's a decline, Stripe_CardError will be caught
                    $errorResponse['errorBillingDetails'] = 3;
                    $this->flashMessenger()->addMessage($errorResponse);

                    return $this->redirect()->toUrl(BASE_URL . "/register-rsp");
                }
            }
        }

        // Add case
        if (!empty($post_array)) {
            // try {
            $loggedInUserId = ($this->getLoggedInUserId()) ? $this->getLoggedInUserId() : 0;
            $settingsData = $this->_modelUsers->getAdminSettings();
            $post_array['contracted_price'] = $settingsData['contracted_price'];

            $spId = $this->_modelUsers->createServiceProviderUser($post_array, $loggedInUserId);
            if ($spId) {
                $user_post_array = array(
                    'userName' => $post_array['user_username'],
                    'fName' => $post_array['user_fname'],
                    'lName' => $post_array['user_lname'],
                    'phoneNumber' => $post_array['user_phone'],
                    'emailId' => $post_array['user_email'],
                    'password' => $post_array['password']
                );

                $this->_modelUsers->createSPUser($user_post_array, $loggedInUserId, $spId);
            }
            // Save Service Provider recevining payment info
            $pId = $this->_modelUsers->saveServiceProviderReceivningPaymentInfo($post_array, $spId, $spBankInfoStripe->id);

            $companyId = $this->_modelUsers->companyId;

            // if( $profileId <> '' && $paymentProfileId <> '' && $shippingAddrsId <> '' ) {
            // 		$this->_modelUsers->savePaymentProfileData($spId, $profileId, $paymentProfileId, $shippingAddrsId);
            // }

            if ($customerData->id != '') {
                $this->_modelUsers->savePaymentProfileData($spId, $customerData->id, $paymentProfileId = null, $shippingAddrsId = null);
            }

            $errorResponse['1'] = (object) array(
                        'status' => 'success-msg',
                        'message' => 'User created successfully.'
            );
            $this->flashMessenger()->clearCurrentMessages();
            $this->flashMessenger()->addMessage($errorResponse);
            $_SESSION['SUCC_REG_MSG'] = 'Registration successfull.';
            return $this->redirect()->toUrl(BASE_URL . "/login");
        }

        return new ViewModel(array(
            'data' => $data,
            'responseData' => $responseData[0],
            'errRes' => $responseData
        ));
    }

    /**
     * 
     * @return \Zend\View\Model\ViewModel
     */
    public function myRemotiiAction() {
        $userId = $this->getLoggedInUserId();
        $remotiiId = $this->params('id', '0');
        if ($remotiiId) {
            $userRemotiiIdData = $this->_clientModel->getRemotiiNameMacId($userId, $remotiiId);
            $mac_address = $userRemotiiIdData[0]['mac_address'];
            $this->_clientModel->insertCheckInReq($mac_address);
        }
        $userRemotiiId = $this->_clientModel->getClientRemotii($userId);
        $userRemotiiConfig = $this->_clientModel->getUserRemotiiIOconfig($userId, $remotiiId);
        // _pre($userRemotiiConfig);
        $remotii_data = $this->_modelUsers->getRemotiiOffsetDayLightSaving($remotiiId);
        $offset = $remotii_data[0]->offset;
        $day_light_saving = $remotii_data[0]->day_light_saving;
        if (empty($userRemotiiConfig['baseRec'][0]['remotii_id'])) {
            return $this->redirect()->toUrl(BASE_URL . '/client/all-remotii');
        }
        $access_level = $userRemotiiConfig['baseRec'][0]['access_level'];
        $remotiiId = $userRemotiiConfig['baseRec'][0]['remotii_id'];
        $share_person_userId = $userRemotiiConfig['baseRec'][0]['user_id'];
        $shared_user_id = $userRemotiiConfig['baseRec'][0]['shared_user_id'];
        $share_person_name = $this->_clientModel->getUserNameOfSharedRemotii($share_person_userId);
        $inboundData = $this->_clientModel->getInboundData($remotiiId);
        // _pre($inboundData);
        return new ViewModel(array(
            'userRemotii' => $userRemotiiId,
            'userId' => $userId,
            'userRemotiiConfig' => $userRemotiiConfig,
            'inboundData' => $inboundData,
            'remotiiId' => $remotiiId,
            'share_person_name' => $share_person_name,
            'shared_user_id' => $shared_user_id,
            'access_level' => $access_level,
            'time' => $time
        ));
    }

    public function allRemotiiAction() {
        $userId = $this->getLoggedInUserId();
        $userRemotiiId = $this->_clientModel->getClientRemotii($userId);
        $time = time();
        $userRemotiiConf = $this->_clientModel->getUserRemotiiIOconf($userId);
        return new ViewModel(array(
            'userRemotii' => $userRemotiiId,
            'userId' => $userId,
            'userRemotiiConf' => $userRemotiiConf
        ));
    }

    /**
     * Ajax request action
     * 
     * @return type
     */
    public function changeConfigStatusAction() {
        $request = $this->getRequest();
        $macAddress = $request->getQuery('remotii_mac_address');
        $remotiiId = $request->getQuery('remotii_id');
        $doutSC = $request->getQuery('dout_sc');
        $pin_number = $request->getQuery('pin_number');
        $pulse_width = $request->getQuery('pulse_width');
        $pulse_width = round($pulse_width * 1000);
        //$access_level = $request->getQuery('access_level');
        //        $shared_user_id = $request->getQuery('shared_user_id');
        //        $access_level = $this->_clientModel->periodicallyCheckAccessControl($remotiiId, $shared_user_id);

        $userId = $this->getLoggedInUserId();
        $userRemotiiConfig = $this->_clientModel->getUserRemotiiIOconfig($userId, $remotiiId);
        $permission = ($userRemotiiConfig['baseRec'][0]['user_id'] == $userId) ? 1 : (int) $userRemotiiConfig['baseRec'][0]['access_level'];

        if ($permission != 1 && $permission != 2) {
            die('0');
        }
        $status = $request->getQuery('status');

        $loggedInUserId = $this->getLoggedInUserId();

        $result = $this->_clientModel->endUserHasRemotii(array(
            'userId' => $loggedInUserId,
            'remotiiId' => $remotiiId
        ));

        if ($result['status'] == 'FAIL') {
            die("This remotii does not exists to your account");
        }

        if ($macAddress && $doutSC) {
            if ($status == 'set') {
                $doutSet = $doutSC;
                $doutClr = '0';
            } else if ($status == 'clr') {
                $doutClr = $doutSC;
                $doutSet = '0';
            }
            if ($status == 'tgl') {
                $tx_type = 'GPIO_TGL_REQ';
            } else {
                $tx_type = 'GPIO_SET_REQ';
            }
            $params = array(
                'mac_address' => $macAddress,
                'remotii_id' => $remotiiId,
                'dout_set' => $doutSet,
                'dout_clr' => $doutClr,
                'pulse_time' => $pulse_width,
                'pin_number' => $pin_number,
                'tx_type' => $tx_type,
                'dout_tgl' => $doutSC
            );

            $result = $this->_clientModel->changeOBRemotiiPin($params);
        }
        echo '1';
        die('');
    }

    /**
     * 
     * @return \Zend\View\Model\ViewModel
     */
    public function remotiiSetupAction() {
        //logged in user id and email
        $loggedInUserId = $this->getLoggedInUserId();
        $loggedInUserEmail = $this->getLoggedInUserEmail();

        //get the remotii data for logged in user
        $remotiiData = $this->_clientModel->getRemotiiName($loggedInUserId);

        $numbersList = $this->_clientModel->getNumbersList();

        $request = $this->getRequest();

        $modifyRemotiiId = $this->params('id', '0');

        //  Added on 17/12/2013
        $remotiiPaymentStatusVal = $this->_clientModel->getUserRemotiiLastPaymentStatus($modifyRemotiiId);
        $remotiiPaymentStatus = $remotiiPaymentStatusVal->payment_status;
        $remotiiAlwEndBill = $remotiiPaymentStatusVal->allow_end_user_billing;
        $offsetArray = $this->_clientModel->getoffset();

        //get input and output config
        $InputConfig = $this->_clientModel->getInputConfig($loggedInUserId, $modifyRemotiiId);
        $OutputConfig = $this->_clientModel->getOutputConfig($loggedInUserId, $modifyRemotiiId);
        if (!$modifyRemotiiId) {
            $modifyRemotiiId = $request->getPost('modifyRemotiiId');
        }
        if ($modifyRemotiiId) {
            $paymentStripeStatusCustom = 1;
            $result = $this->_clientModel->endUserHasRemotii(array(
                'userId' => $loggedInUserId,
                'remotiiId' => $modifyRemotiiId
            ));

            if ($result['status'] == 'FAIL') {
                die("This remotii does not exists to your account");
            }
            $remotiiMacData = $this->_clientModel->getRemotiiNameMacId($loggedInUserId, $modifyRemotiiId);
        }
        if ($request->isXmlHttpRequest()) {
            if ($request->getPost('xDefaultRemotiiDataSubmission')) {
                $mailStatus = '';
                $toDay = date('Y-m-d');

                $defaultRSData = array();
                $defaultRSDataInputConf = array();
                $defaultRSDataOutputConf = array();
                $inputArray = array();
                $outputArray = array();
                $dInput = array();
                $dOutput = array();
                $inputresult = array();
                $outputresult = array();

                $userRemotiiId = null;
                if ($modifyRemotiiId) {
                    $macAddress = $request->getPost('macAddress1');
                    $sel = $request->getPost('offset');
                    $day_light_saving = $request->getPost('day_saving');
                    $userRemotiiId = $this->_clientModel->getIdFromUserRemotii($modifyRemotiiId);

                    //                    $gmtInsert= $this->_clientModel->selGmtInsert($sel,$userRemotiiId);
                } else {
                    $macAddress = $request->getPost('macAddress');
                }

                $remotiiName = $request->getPost('remotiiName');
                if( $request->getPost('enable_end_user_offline_notifications') != ''){
                    $enable_end_user_offline_notifications = $request->getPost('enable_end_user_offline_notifications');
                }else{
                    $enable_end_user_offline_notifications = 1;
                }
                if( $request->getPost('enable_rsp_offline_notifications') != ''){
                    $enable_rsp_offline_notifications = $request->getPost('enable_rsp_offline_notifications');
                }else{
                    $enable_rsp_offline_notifications = 0;
                }
                
                $enable_rsp_offline_notifications = $request->getPost('enable_rsp_offline_notifications');
                $offline_notification_timeout_hours = $request->getPost('offline_notification_timeout_hours');
                $notification_email = implode(",", current($request->getPost('offline')));

                $configSetting = $request->getPost('configSetting');

                //  get the remotii Id using remotii Mac Addrs
                $rmData = $this->_clientModel->getRemotiiId($macAddress);
                $remotiiId = $rmData['remotiiId'];
                $spId = $rmData['spId'];

                $cusomerIdStripe = $request->getPost('stripeCustProfile');
                $cardHandlerName = $request->getPost('card_holder');
                $alwEndBll = $request->getPost('alwEndBll');

                if ($cusomerIdStripe <> '') {
                    //  Charge to customer START
                    // Function call to charge End user and deposit amount into Admin stripe account
                    $endCharge = $this->_modelUsers->chargeToEndUserByPerRemotii($remotiiId);
                    // _pre($endCharge);
                    // $customSpArr = array();
                    // $i = 0;
                    $transData = "";
                    foreach ($endCharge as $endInfo) {
                        $transData = "";
                        try {
                            $userId = $loggedInUserId;

                            $endUserAccumulatedAmount = $this->_modelUsers->getEndUserAccumulatedAmount($userId);
                            //$endUserAccumulatedAmount = 0;
                            $customerNeedToPay = $endInfo->amount * $endInfo->spCount * ENDUSER_BILL_MONTHS;
                            $totalEndUserAmountToCharge = $endUserAccumulatedAmount + $customerNeedToPay;
                            //$totalEndUserAmountToCharge = $customerNeedToPay;
                            if (!empty($totalEndUserAmountToCharge)) {
                                $transData = $this->stripeMethod->chargeCustomer($cusomerIdStripe, $totalEndUserAmountToCharge);
                            } else {
                                continue;
                            }
                        } catch (\Exception $e) {
                            if ($e instanceof \Stripe_CardError or $e instanceof \Stripe_InvalidRequestError or $e instanceof \Stripe_AuthenticationError or $e instanceof \Stripe_ApiConnectionError or $e instanceof \Stripe_Error) {
                                $uInfo = $this->_modelUsers->getEndUserAccountInfo($loggedInUserId);
                                $this->sendPaymentFailureMail($uInfo->email, "Remotii Payment Failed", array(
                                    'name' => 'An attempt to bill your Remotii, <b>' . $remotiiName . '</b>, for <b>$' . $totalEndUserAmountToCharge . '</b> has been unsuccessful.',
                                    'nxtBillDate' => 'Please log in to your account and update your billing settings if necessary.'
                                ));
                                // Error occured for
                                //$body = $e->getJsonBody ();
                                //$err = $body ['error'];
                                //						error_log ( json_encode ( array (
                                //								'userId' => $userId,
                                //								'error' => $err ['message'] 
                                //						) ), 3, '');

                                print 'failed';
                                die();
                            }
                        }
                        //      /var/www/remotii/public/cronBillServiceProvidersAction.fn.log
                        // Save transaction data
                        $payment_source = 'CC';
                        $payment_flag = 'CR';
                        $lastInsertedId = $this->_modelUsers->saveTransactionData($transData, $userId, $totalEndUserAmountToCharge, $payment_source, $payment_flag, $userRemotiiId, Null, 2);
                        // Change service provider account status
                        //error_log(json_encode($transData), 3, '/var/www/remotii/public/cronBillServiceProvidersAction.fn.log');
                        $AmtToBeAddedtoAcc = 0;
                        $UserRemotiiPaymentStatus = 0;

                        if ($transData->paid == 1) {
                            $mailStatus = "SUCCESS";
                            $paymentStripeStatusCustom = 1;
                            $UserRemotiiPaymentStatus = 1;
                            $AmtToBeAddedtoAcc = 0 - $endUserAccumulatedAmount; // for clearing up accumulated amount
                            //$this->_modelUsers->saveEndUserPaymentProfile($userId, $lastInsertedId);
                            //$this->_modelUsers->updateAccStatus ( $userId, $lastInsertedId );
                            //$this->_modelUsers->clearEndUserAccumulatedAmount ( $userId );
                            //  Save Date into table for paying to SP that is payback amount START
                            $totalAmountToPayToSP = $totalEndUserAmountToCharge - ($totalEndUserAmountToCharge * $endInfo->service_fee / 100);
                            if ($totalAmountToPayToSP > 0) {
                                $nextPaybackDate = date('Y-m-d', strtotime($toDay . SP_NEXT_GET_PAYMENT));
                                $this->_modelUsers->saveSPpaybackInfo($endInfo->service_provider_id, $totalAmountToPayToSP, $nextPaybackDate);
                                //  Save Date into              table for paying to SP that is payback amount END
                            }
                        } else {
                            $mailStatus = "FAILURE";
                            $paymentStripeStatusCustom = 0;
                            $AmtToBeAddedtoAcc = $customerNeedToPay;
                            //$this->_modelUsers->updateEULastPaymentStatId($id, $lastInsertedId);
                            //$this->_modelUsers->updateUserAccStatusToDelinquent ( $userId );
                            //$this->_modelUsers->addEndUserAccumulatedAmount ( $userId, $customerNeedToPay );
                        }
                    }
                    //  Charge to customer END
                }

                $settingType = DEFAULTS;
                $configSetting = 'custom';
                if ($paymentStripeStatusCustom == 1 || $alwEndBll != 1) {
                    //if(true) {
                    if ($configSetting == 'custom') {

                        $selGmt = $request->getPost('offset');
                        $offset = $this->_clientModel->getGmt($selGmt);
                        $offset_time = $offset[0]['offset_min'];
                        $day_saving = $request->getPost('day_saving');
                        if ($day_saving == 1) {
                            $offset_time = $offset_time + 60;
                        }
                        if (empty($day_saving)) {
                            $day_saving = 0;
                        }
                        if ($modifyRemotiiId) {
                            if ($userRemotiiId) {
                                //update remotii name in user_remotii
                                if (!empty($remotiiName)) {
                                    $this->_clientModel->updateRemotiiName($userRemotiiId, $remotiiName, $selGmt, $day_saving, $offset_time);
                                }
                            }
                        } else {
                            //delete sp row
                            $this->_clientModel->deleteUserRemotiiOfSp($remotiiId);
                            $userRemotiiId = $this->_clientModel->saveUserRemotiiData($remotiiName, $remotiiId, $loggedInUserId, $settingType, $paymentStripeStatusCustom, $selGmt, $day_saving, $offset_time);
                        }

                        $this->_clientModel->updateRemotiiNotificationSettings($remotiiId, $enable_end_user_offline_notifications, $enable_rsp_offline_notifications, $offline_notification_timeout_hours, $notification_email);

                        if ($modifyRemotiiId) {
                            $i = 0;

                            foreach ($InputConfig as $ic) {
                                $inputArray[$i] = $ic['pin_number'];
                                $i++;
                            } //_pr($inputArray);

                            foreach ($OutputConfig as $oc) {
                                $outputArray[$i] = $oc[pin_number];
                                $i++;
                            }
                        }
                        //                        foreach($_POST['email'] as $email){
                        //                            $Email=$email;
                        //                            $Email1=array_reverse($Email);
                        //                        }
                        //  Save data into config tables
                        for ($i = 1; $i <= 4; $i++) {
                            $ri_enabled_ = $request->getPost('ri_enabled_' . $i);

                            if (!empty($ri_enabled_)) {
                                $defaultRSDataInputConf[$i]['name'] = $request->getPost('ri_name_' . $i);
                                $defaultRSDataInputConf[$i]['pin_number'] = $request->getPost('ri_pin_number_' . $i);
                                $defaultRSDataInputConf[$i]['config_id'] = $request->getPost('ri_confId_' . $i);
                                $defaultRSDataInputConf[$i]['active_label_text'] = $request->getPost('ri_asl_' . $i);
                                $defaultRSDataInputConf[$i]['active_label_color'] = $request->getPost('ri_active_color_' . $i);
                                $defaultRSDataInputConf[$i]['inactive_label_text'] = $request->getPost('ri_iasl_' . $i);
                                $defaultRSDataInputConf[$i]['inactive_label_color'] = $request->getPost('ri_inactive_color_' . $i);

                                $defaultRSDataInputConf[$i]['enable_notification'] = $request->getPost('ri_enable_ntfn_' . $i) ? 1 : 0;
                                //                                $defaultRSDataInputConf[$i]['notification_trigger'] = $request->getPost('ri_ntfn_trigger_' . $i);
                                $defaultRSDataInputConf[$i]['email'] = $_POST['email'][$i];
                                //                                $defaultRSDataInputConf[$i]['play_sound'] = $request->getPost('ri_ntfn_sound_' . $i);
                                $defaultRSDataInputConf[$i]['notification_trigger'] = $request->getPost('ri_ntfn_trigger_hidden_' . $i);
                                $defaultRSDataInputConf[$i]['play_sound'] = $request->getPost('ri_sound_hidden_' . $i);
                            }
                        }
                        $i = 0;
                        foreach ($defaultRSDataInputConf as $defaultInput) {
                            $dInput[$i] = $defaultInput['pin_number'];
                            $i++;
                        } //_pr($defaultRSDataInputConf);

                        $inputresult = array_diff($inputArray, $dInput);
//                        print_r($_REQUEST);exit();
                        for ($i = 1; $i <= 3; $i++) {
                            $ro_enabled_ = $request->getPost('ro_enabled_' . $i);
                            if (!empty($ro_enabled_)) {

                                $defaultRSDataOutputConf[$i]['name'] = $request->getPost('ro_name_' . $i);
                                $pin = $defaultRSDataOutputConf[$i]['pin_number'] = $request->getPost('ro_pin_number_' . $i);
                                $defaultRSDataOutputConf[$i]['config_id'] = $request->getPost('ro_confId_' . $i);
                                $defaultRSDataOutputConf[$i]['active_label_text'] = $request->getPost('ro_asl_' . $i);
                                $defaultRSDataOutputConf[$i]['active_label_color'] = $request->getPost('ro_active_color_' . $i);
                                $defaultRSDataOutputConf[$i]['inactive_label_text'] = $request->getPost('ro_iasl_' . $i);
                                $defaultRSDataOutputConf[$i]['inactive_label_color'] = $request->getPost('ro_inactive_color_' . $i);

                                $ism = $defaultRSDataOutputConf[$i]['is_output_momentary'] = $request->getPost('ro_momentary_' . $i) ? 1 : 0;
                                $ois = $defaultRSDataOutputConf[$i]['output_initial_state'] = $request->getPost('ro_initialState_' . $i) ? 1 : 0;
                                $defaultRSDataOutputConf[$i]['enable_notification'] = $request->getPost('ro_enable_ntfn_' . $i) ? 1 : 0;
                                $defaultRSDataOutputConf[$i]['notification_trigger'] = $request->getPost('ro_ntfn_trigger_hidden_' . $i);

                                $defaultRSDataOutputConf[$i]['email'] = $_REQUEST['ro_email'][$i];
                                $defaultRSDataOutputConf[$i]['ro_pulse_time'] = $request->getPost('ro_pulse_time_' . $i);
                                $pulse_time = round($defaultRSDataOutputConf[$i]['ro_pulse_time'] * 1000);
                                $defaultRSDataOutputConf[$i]['play_sound'] = $request->getPost('ro_sound_hidden_' . $i);
                                if ($ism) {

                                    $pinHexValue = 0;
                                    if ($pin == 1) {
                                        $pinHexValue = 1;
                                    } elseif ($pin == 2) {
                                        $pinHexValue = 2;
                                    } elseif ($pin == 3) {
                                        $pinHexValue = 4;
                                    }

                                    if ($ois == 1) {
                                        $dout_set = $pinHexValue;
                                    } elseif ($ois == 0) {
                                        $dout_clr = $pinHexValue;
                                    }

                                    $tx_type = 'GPIO_SET_REQ';
                                    $params = array(
                                        'mac_address' => $macAddress,
                                        'remotii_id' => $remotiiId,
                                        'pulse_time' => $pulse_time,
                                        'pin_number' => $pin,
                                        'dout_set' => $dout_set,
                                        'dout_clr' => $dout_clr,
                                        'tx_type' => $tx_type
                                    );

                                    $result = $this->_clientModel->changeOBRemotiiPin($params);
                                }
                                //}
                            }
                        }
                        $i = 0;
                        foreach ($defaultRSDataOutputConf as $defaultOutput) {
                            $dOutput[$i] = $defaultOutput['pin_number'];
                            $i++;
                        }
                        $outputresult = array_diff($outputArray, $dOutput);
                        $count = $this->_clientModel->deleteEventData($outputresult, $remotiiId, $inputresult);
                        try {

                            $this->_clientModel->saveInputConfig($userRemotiiId, $defaultRSDataInputConf, $loggedInUserId,$remotiiId);
                            $this->_clientModel->saveOutputConfig($userRemotiiId, $defaultRSDataOutputConf, $loggedInUserId,$remotiiId);
                        } catch (\Exception $e) {
                            
                        }
                    }

                    if ($alwEndBll == 1) {
                        if ($modifyRemotiiId == '') {
                            $this->_modelUsers->saveEndUserPaymentProfile($userId, $userRemotiiId, $cusomerIdStripe, $cardHandlerName);
                            //  Calculating EU next billing date
                            $euNextBillingDate = date('Y-m-d', strtotime($toDay . ENDUSER_NEXT_BILLING));
                            $this->_modelUsers->updateAccStatusEU($userRemotiiId, $lastInsertedId, $euNextBillingDate);
                        } else {
                            $this->_modelUsers->updateEndUserPaymentProfile($userId, $userRemotiiId, $cusomerIdStripe, $cardHandlerName);
                            $this->_modelUsers->updateAccStatusEUWithoutNxtBillingDate($userRemotiiId, $lastInsertedId);
                            $this->_modelUsers->updateUserRemotiiPaymentStatus($userRemotiiId, $UserRemotiiPaymentStatus);
                        }

                        //  Update payment stat table to add user remotii id
                        //$this->_modelUsers->updatePaymentStateAddEUremotiiId($userRemotiiId, $lastInsertedId);
                        //  Add the Accumulated amount for next billing
                        $this->_modelUsers->addEndUserAccumulatedAmount($userRemotiiId, $AmtToBeAddedtoAcc);
                    }

                    //  Email to the End User
                    if ($mailStatus <> '') {
                        $uInfo = $this->_modelUsers->getEndUserAccountInfo($loggedInUserId);
                        //  Mail to End User that he has charged successfully
                        $viewTemplate = 'tpl/email/eupaymentmail';
                        if ($mailStatus == "SUCCESS") {
                            ////////////////////////////////////////
                            $values = array(
                                'name' => 'Your Remotii account has been successfully billed for <b>$' . $totalEndUserAmountToCharge . '</b>.',
                                'nxtBillDate' => 'Your next billing date is <b>' . $euNextBillingDate . '</b>.',
                                'msg' => '',
                                'rsp' => 'The Remotii Team'
                            );
                            $subject = 'Remotii Payment Successful.';
                            ////////////////////////////////////////
                        } elseif ($mailStatus == "FAILURE") {
                            ////////////////////////////////////////
                            $values = array(
                                'name' => 'An attempt to bill your Remotii account for <b>$' . $totalEndUserAmountToCharge . '</b> has been unsuccessful.',
                                'nxtBillDate' => 'Please log in to your account and update your billing settings if necessary.',
                                'msg' => '',
                                'rsp' => 'The Remotii Team'
                            );
                            $subject = 'Remotii Payment Failed.';
                            ////////////////////////////////////////
                        }
                        ////////////////////////////////////////
                        //  Email Code Start
                        $mailService = $this->getServiceLocator()->get('goaliomailservice_message');
                        $message = $mailService->createHtmlMessage(ADMIN_EMAIL, "$uInfo->email", $subject, $viewTemplate, $values);
                        try {
                            $mailService->send($message);
                        } catch (\Exception $e) {
                            $msg = $e->getMessage();
                            error_log(json_encode(array(
                                'message' => 'Mail not sent',
                                'email' => $uInfo->email,
                                'exception' => $msg
                                    )), 3, '/var/www/remotii/public/cronNotification.fn.log');
                        }
                        //  Email Code Start
                        ////////////////////////////////////////
                    }
                }

                if ($paymentStripeStatusCustom == 0 && $alwEndBll == 1) {
                    print 'failed';
                    die();
                }


                echo $modifyRemotiiId ? $modifyRemotiiId : $remotiiId;


                //_pr($defaultRSDataOutputConf);
                die();
                //_pre($defaultRSData);
                //die($formData);
            }
        }

        $view = new ViewModel(array(
            'remotiiMacData' => $remotiiMacData,
            'modifyRemotiiId' => $modifyRemotiiId,
            'remotiiPaymentStatus' => $remotiiPaymentStatus,
            'remotiiAlwEndBill' => $remotiiAlwEndBill,
            'remotiiData' => $remotiiData,
            'inputConfig' => $InputConfig,
            'outputConfig' => $OutputConfig,
            'offsetArray' => $offsetArray,
            'numbersList' => $numbersList
        ));
        $view->setTerminal(true);
        return $view;
    }

    public function checkremotiisetupAction() {

        $loggedInUserId = $this->getLoggedInUserId();
        $modifyRemotiiId = $_POST['modifyRemotiiId'];
        $macAddress = $_POST['macAddress1'];
        $rmData = $this->_clientModel->getRemotiiId($macAddress);
        $remotiiId = $rmData['remotiiId'];
        $InputConfig = $this->_clientModel->getInputConfig($loggedInUserId, $remotiiId);
        $OutputConfig = $this->_clientModel->getOutputConfig($loggedInUserId, $remotiiId);
        $ri_pin_number = array();
        $ro_pin_number = array();
        $input_count = array();
        $output_count = array();
        foreach ($InputConfig as $inconfig) {
            $inputArray[] = $inconfig['pin_number'];
        }
        foreach ($OutputConfig as $outconfig) {
            $outputArray[] = $outconfig['pin_number'];
        }
        for ($i = 1; $i <= 4; $i++) {

            if ($_POST['ri_enabled_' . $i]) {

                $ri_pin_number[] = $_POST['ri_pin_number_' . $i];
            }
        }
        for ($i = 1; $i <= 3; $i++) {
            if ($_POST['ro_enabled_' . $i]) {
                $ro_pin_number[] = $_POST['ro_pin_number_' . $i];
            }
        }
        $input_count = array_diff($inputArray, $ri_pin_number);
        $output_count = array_diff($outputArray, $ro_pin_number);
        $data = $this->_clientModel->checkdeleteData($output_count, $remotiiId, $input_count);
        if (!empty($input_count) || !empty($output_count)) {
            if ($data == 1) {

                $view = new ViewModel(array(
                    'data' => $data
                ));
                $view->setTerminal(true);
                return $view;
            }
        }
        $view = new ViewModel(array(
            'data' => $data
        ));
        $view->setTerminal(true);
        return $view;
    }

    public function copyRemotii($params) {
        $loggedInUserId = $this->getLoggedInUserId();
        $remotiiData = $this->_clientModel->getRemotiiName($loggedInUserId);
        $message = "Success";
        return $data = array(
            'remotiiData' => $remotiiData,
            'message' => $message
        );
    }

    /**
     * 
     * @return \Zend\View\Model\ViewModel
     */
    public function customRemotiiSetupAction() {
        $remotiiId = $this->params()->fromRoute('id', 0);
        $loggedInUserId = $this->getLoggedInUserId();

        //  Logged in user email Id
        $loggedInUserEmail = $this->getLoggedInUserEmail();
        if ($loggedInUserId) {
            $remotiiMacData = $this->_clientModel->getRemotiiNameMacId($loggedInUserId, $remotiiId);
            $InputConfig = $this->_clientModel->getInputConfig($loggedInUserId, $remotiiId);
            $OutputConfig = $this->_clientModel->getOutputConfig($loggedInUserId, $remotiiId);
        }

        $request = $this->getRequest();

        if ($request->isXmlHttpRequest()) {
            if ($request->getPost('xDefaultRemotiiDataSubmission')) {
                $remotiiId = $this->params()->fromRoute('id', 0);

                $defaultRSData = array();
                $defaultRSDataInputConf = array();
                $defaultRSDataOutputConf = array();

                $macAddress = $request->getPost('macAddress');
                $remotiiName = $request->getPost('remotiiName');
                //$configSetting = $request->getPost('configSetting');
                //  get the remotii Id using remotii Mac Addrs
                //  $rmData = $this->_clientModel->getRemotiiId($macAddress);
                //$remotiiId = $rmData['remotiiId'];
                //  $spId = $rmData['spId'];
                $settingType = DEFAULTS; //->not clear

                if ($configSetting == 'default') {
                    //  Save data into user remotii
                    //$userRemotiiId = $this->_clientModel->saveUserRemotiiData($remotiiName, $remotiiId, $loggedInUserId, $settingType);
                    //  save SP config to user config
                    //$this->_clientModel->saveDefaultIOconfig($spId , $userRemotiiId, $loggedInUserId, $loggedInUserEmail);
                }
                //if($configSetting == 'custom') {
                // die("h");
                if (true) {
                    //   die("hi");
                    $userRemotiiId = $this->_clientModel->getIdFromUserRemotii($remotiiId);

                    if (!$userRemotiiId) {
                        //  Save data into user remotii
                        $userRemotiiId = $this->_clientModel->saveUserRemotiiData($remotiiName, $remotiiId, $loggedInUserId, $settingType);
                    } else {
                        //update remotii name in user_remotii
                        if (!empty($remotiiName)) {
                            $this->_clientModel->updateRemotiiName($userRemotiiId, $remotiiName);
                        }
                    }

                    //  Save data into config tables
                    //input config
                    for ($i = 1; $i <= 4; $i++) {
                        $ri_enabled_ = $request->getPost('ri_enabled_' . $i);
                        if (!empty($ri_enabled_)) {
                            $defaultRSDataInputConf[$i]['name'] = $request->getPost('ri_name_' . $i);
                            $defaultRSDataInputConf[$i]['active_label_text'] = $request->getPost('ri_asl_' . $i);
                            $defaultRSDataInputConf[$i]['active_label_color'] = $request->getPost('ri_active_color_' . $i);
                            $defaultRSDataInputConf[$i]['inactive_label_text'] = $request->getPost('ri_iasl_' . $i);
                            $defaultRSDataInputConf[$i]['inactive_label_color'] = $request->getPost('ri_inactive_color_' . $i);

                            $defaultRSDataInputConf[$i]['enable_notification'] = $request->getPost('ri_enable_ntfn_' . $i) ? 1 : 0;
                            $defaultRSDataInputConf[$i]['notification_trigger'] = $request->getPost('ri_ntfn_trigger_' . $i);

                            $defaultRSDataInputConf[$i]['notification_email'] = $request->getPost('ri_ntfn_mail_' . $i);
                        }
                    }
                    //output config
                    for ($i = 1; $i <= 3; $i++) {
                        $ro_enabled_ = $request->getPost('ro_enabled_' . $i);
                        if (!empty($ro_enabled_)) {
                            $defaultRSDataOutputConf[$i]['name'] = $request->getPost('ro_name_' . $i);
                            $defaultRSDataOutputConf[$i]['active_label_text'] = $request->getPost('ro_asl_' . $i);
                            $defaultRSDataOutputConf[$i]['active_label_color'] = $request->getPost('ro_active_color_' . $i);
                            $defaultRSDataOutputConf[$i]['inactive_label_text'] = $request->getPost('ro_iasl_' . $i);
                            $defaultRSDataOutputConf[$i]['inactive_label_color'] = $request->getPost('ro_inactive_color_' . $i);

                            $defaultRSDataOutputConf[$i]['is_output_momentary'] = $request->getPost('ro_momentary_' . $i) ? 1 : 0;
                            $defaultRSDataOutputConf[$i]['output_initial_state'] = $request->getPost('ro_active_initialState_' . $i) ? 1 : 0;
                            $defaultRSDataOutputConf[$i]['enable_notification'] = $request->getPost('ro_enable_ntfn_' . $i) ? 1 : 0;
                            $defaultRSDataOutputConf[$i]['notification_trigger'] = $request->getPost('ro_ntfn_trigger_' . $i) ? 1 : 0;

                            $defaultRSDataOutputConf[$i]['notification_email'] = $request->getPost('ro_ntfn_mail_' . $i);
                        }
                    }

                    $this->_clientModel->saveInputConfig($userRemotiiId, $defaultRSDataInputConf, $loggedInUserId);
                    $this->_clientModel->saveOutputConfig($userRemotiiId, $defaultRSDataOutputConf, $loggedInUserId);
                }
                //_pre($defaultRSData);
                //die($formData);
            }
        }

        $view = new ViewModel(array(
            'remotiiMacData' => $remotiiMacData,
            'InputConfig' => $InputConfig,
            'OutputConfig' => $OutputConfig,
            'remotiiId' => $remotiiId
        ));
        $view->setTerminal(true);
        return $view;
    }

    /**
     * 
     * @return \Zend\View\Model\ViewModel
     */
    public function remotiiconfigchkAction() {
        $macId = $_REQUEST['postdata'];
        $userId = $this->_clientModel->getUserId($macId);
        $checkRole = $this->_clientModel->checkRole($userId);
        $data = $this->_clientModel->remotiiConfigchkValidateChk($macId);
        if ($checkRole == 2) {
            $data = 3;
        }

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
    public function remotiiuserpinfoAction() {
        $macId = $_REQUEST['postdata'];
        $data = $this->_clientModel->remotiiUserPaymentInfo($macId);

        // your code here ...
        $view = new ViewModel(array(
            'data' => $data
        ));
        $view->setTerminal(true);
        return $view;
    }

    public function serviceProviderInfoAction() {
        $remotiiId = $this->getRequest()->getQuery('remotii_id');
        $spInfo = $this->_modelUsers->getServiceProviderInfoByRemotiiId($remotiiId);
        if (!empty($spInfo)) {
            $spInfo = $spInfo[0];
        }

        $view = new ViewModel(array(
            'spInfo' => $spInfo
        ));
        $view->setTerminal(true);
        return $view;
    }

    /**
     *  Send client query to service provider email address
     *  
     *  @author emp24
     *  @return void
     */
    public function sendEmailToServiceProviderAction() {

        $request = $this->getRequest();
        if ($request->isXmlHttpRequest()) {
            $spid = $request->getQuery('spid');
            $message = $request->getPost('message');

            $spInfo = $this->_modelUsers->getServiceProviderInfo($spid);

            $uid = $this->getLoggedInUserId();
            $uinfo = $this->_clientModel->getClientById($uid);

            if (!empty($uinfo)) {
                $uinfo = (array) $uinfo[0];
            }

            if (!empty($spInfo)) {
                $spInfo = $spInfo[0];
            }

            $toEmail = $spInfo->contact_email;

            if (!empty($toEmail)) {
                $viewTemplate = 'tpl/email/sp_contact';
                $values = array(
                    'spInfo' => $spInfo,
                    'uinfo' => $uinfo,
                    'message' => $message
                );
                $subject = 'End User Query';
                $mailService = $this->getServiceLocator()->get('goaliomailservice_message');
                $message = $mailService->createTextMessage(ADMIN_EMAIL, "$toEmail", $subject, $viewTemplate, $values);
                $mailService->send($message);
                die('Message sent successfully.');
            }
        }
        die('Sorry, Invalid Request....  :) ');
    }

    /**
     * Assign default remotii to client
     * 
     * @author emp24
     * @return void
     */
    public function setDefaultRemotiiAction() {
        $request = $this->getRequest();
        if ($request->isXmlHttpRequest()) {
            $loggedInUserId = $this->getLoggedInUserId();
            $remotiiId = $request->getPost('remotiiId');

            $result = $this->_clientModel->endUserHasRemotii(array(
                'userId' => $loggedInUserId,
                'remotiiId' => $remotiiId
            ));

            if ($result['status'] == 'FAIL') {
                die("This remotii does not exists to your account");
            }
            $userRemotiiIdData = $this->_clientModel->getRemotiiNameMacId($loggedInUserId, $remotiiId);
            $userRemotiiId = $userRemotiiIdData[0]['user_remotii_id'];
            $isShared = !empty($userRemotiiIdData[0]['shared_user_id']) ? TRUE : FALSE;
            $result = $this->_clientModel->assignDefaultRemotii($loggedInUserId, $remotiiId, $userRemotiiId, $isShared);

            echo json_encode($result);
            die();
        }
        die('Sorry, Invalid Request....  :) ');
        return;
    }

    /**
     * 
     * @return boolean
     */
    public function redirectRemotiiAction() {

        $userId = $this->getLoggedInUserId();
        $defaultRemotiiId = $this->_clientModel->getIsDefaultRemotii($userId);
        $remotiiId = $defaultRemotiiId['remotii_id'];
        if (!empty($defaultRemotiiId)) {

            return $this->redirect()->toUrl(BASE_URL . '/client/my-remotii/' . $remotiiId);
            // return $this->redirect()->toUrl(BASE_URL . '/client/all-remotii');
        } else {

            return $this->redirect()->toUrl(BASE_URL . '/client/all-remotii');
        }
    }

    //    public function redirectShareRemotiiAction() {
    //             return $this->redirect()->toUrl(BASE_URL . '/client/all-remotii');
    //    }

    protected function getLoggedInUserId() {
        if ($this->zfcUserAuthentication()->hasIdentity()) {
            return $this->zfcUserAuthentication()->getIdentity()->getId();
        } else {
            return false;
        }
    }

    /**
     * 
     * @param type $postData
     * @return boolean
     */
    public function validateManageAdminForm($postData, $id, $rid) {
        $status = false;
        $validator = new emailIdValidation();

        $errorResponse['0'] = (object) array(
                    'username' => $postData['userName'],
                    'display_name' => $postData['displayName'],
                    'fname' => $postData['fName'],
                    'lname' => $postData['lName'],
                    'phone' => $postData['phoneNumber'],
                    'email' => $postData['emailId'],
                    'street' => $postData['street'],
                    'city' => $postData['city'],
                    'state' => $postData['state'],
                    'country' => $postData['country'],
                    'zip_code' => $postData['zip_code'],
                    'password' => $postData['password'],
                    'cnfrmPassword' => $postData['cnfrmPassword'],
                    'cardType' => $postData['cardType'],
                    'card_holder' => $postData['card_holder'],
                    'card_number' => $postData['card_number'],
                    'expMonth' => $postData['expMonth'],
                    'expYear' => $postData['expYear'],
                    'cvv' => $postData['cvv']
        );

        if ($postData['userName'] == '') {
            $errorResponse['erruserName'] = 1;
            $status = true;
        } else {
            //  USer name Duplicasy chk
            $uid = $this->_modelUsers->userNameDuplicasyChk($postData['userName'], $id);
            if ($uid > 0) {
                $errorResponse['errUsernameDuplicasy'] = 1;
                $status = true;
            }
        }
        if ($postData['fName'] == '') {
            $errorResponse['errfName'] = 1;
            $status = true;
        }
        if ($postData['lName'] == '') {
            $errorResponse['errlName'] = 1;
            $status = true;
        }
        if ($postData['phoneNumber'] == '') {
            $errorResponse['errphoneNumber'] = 1;
            $status = true;
        }
        if (!$validator->isValid($postData['emailId'])) {
            $errorResponse['erremailId'] = 1;
            $status = true;
        } else {
            //  Email Duplicasy chk
            $uid = $this->_modelUsers->emailDuplicasyChk($postData['emailId'], $id, $rid);
            if ($uid > 0) {
                $errorResponse['errEmailDuplicasy'] = 1;
                $status = true;
            }
        }

        if (!$id || $postData['password'] <> '') {
            $uppercase = preg_match('@[A-Z]@', $postData['password']);
            //$lowercase = preg_match('@[a-z]@', $postData['password']);
            //$number    = preg_match('@[0-9]@', $postData['password']);
            //$specialCharacters = preg_match('/[!@#$%^&*-]/', $postData['password']);

            if (!$uppercase || strlen($postData['password']) < 8) {
                // tell the user something went wrong
                $errorResponse['errpassword'] = 1;
                $status = true;
            }

            if ($postData['password'] == '' || strlen($postData['password']) < 8) {
                $errorResponse['errpassword'] = 1;
                $status = true;
            }

            if ($postData['password'] != $postData['cnfrmPassword']) {
                $errorResponse['errcnfrmPassword'] = 1;
                $status = true;
            }
        }
        $errorResponse['errorStatus'] = $status;
        $this->flashMessenger()->addMessage($errorResponse);
        return $status;
    }

    public function getSpDefaultConfigAction() {
        $remotiiId = $this->params('id');
        $urid = $this->_clientModel->getUserRemotiiIDByMacID($remotiiId);
        $SPInputConfig = $this->_clientModel->getSPInputConfig($urid);

        if (!empty($SPInputConfig)) {
            foreach ($SPInputConfig as $k => $v) {
                $SPInputConfigTmp[$v['pin_number']] = $v;
            }
            $SPInputConfig = $SPInputConfigTmp;
        }

        $SPOutputConfig = $this->_clientModel->getSPOutputConfig($urid);

        if (!empty($SPOutputConfig)) {
            foreach ($SPOutputConfig as $k => $v) {
                $SPOutputConfigTmp[$v['pin_number']] = $v;
            }
            $SPOutputConfig = $SPOutputConfigTmp;
        }

        $spIOConfig['spInputConfig'] = $SPInputConfig; //$this->_modelSP->getSPInputConfig($spid);
        $spIOConfig['spOutputConfig'] = $SPOutputConfig; //$this->_modelSP->getSPOutputConfig($spid);

        echo json_encode($spIOConfig);
        die();
    }

    public function getSpDefaultConfigNewAction() {
        $remotiiId = $this->params('id');
        $spid = $this->_clientModel->getSPIDByRemotiiMacAddress($remotiiId);

        $SPInputConfig = $this->_clientModel->getSPInputConfigNew($spid);

        if (!empty($SPInputConfig)) {
            foreach ($SPInputConfig as $k => $v) {
                $SPInputConfigTmp[$v['pin_number']] = $v;
            }
            $SPInputConfig = $SPInputConfigTmp;
        }

        $SPOutputConfig = $this->_clientModel->getSPOutputConfigNew($spid);

        if (!empty($SPOutputConfig)) {
            foreach ($SPOutputConfig as $k => $v) {
                $SPOutputConfigTmp[$v['pin_number']] = $v;
            }
            $SPOutputConfig = $SPOutputConfigTmp;
        }

        $spIOConfig['spInputConfig'] = $SPInputConfig; //$this->_modelSP->getSPInputConfig($spid);
        $spIOConfig['spOutputConfig'] = $SPOutputConfig; //$this->_modelSP->getSPOutputConfig($spid);

        echo json_encode($spIOConfig);
        die();
    }

    public function getClientDefaultConfigAction() {
        $macID = $this->params('id');
        $urid = $this->_clientModel->getUserRemotiiIDByMacID($macID);
        $clientInputConfig = $this->_clientModel->getClientInputConfig($urid);
        foreach ($clientInputConfig as $key => $inputConfig) {
            $modifiedEmail = $inputConfig['notification_email'];
            $clientInputConfig[$key]['notification_email'] = explode(",", $modifiedEmail);
        }

        if (!empty($clientInputConfig)) {
            foreach ($clientInputConfig as $k => $v) {
                $clientInputConfigTmp[$v['pin_number']] = $v;
            }
            $clientInputConfig = $clientInputConfigTmp;
        }

        $clientOutputConfig = $this->_clientModel->getClientOutputConfig($urid);
        foreach ($clientOutputConfig as $key2 => $outputConfig) {
            $modifiedOpEmail = $outputConfig['notification_email'];
            $clientOutputConfig[$key2]['notification_email'] = explode(",", $modifiedOpEmail);
        }
        if (!empty($clientOutputConfig)) {
            foreach ($clientOutputConfig as $k => $v) {
                $clientOutputConfigTmp[$v['pin_number']] = $v;
            }
            $clientOutputConfig = $clientOutputConfigTmp;
        }
//        print_r($clientOutputConfig);exit();
        $urIOConfig['spInputConfig'] = $clientInputConfig; //$this->_clientModel->getClientInputConfig($urid);
        $urIOConfig['spOutputConfig'] = $clientOutputConfig;

        //$this->_clientModel->getClientOutputConfig($urid);

        echo json_encode($urIOConfig);
        die();
    }

    public function remotiiNameExistsAction() {
        $remotiiName = $this->getRequest()->getQuery('remotiiName');
        $remotiiId = $this->getRequest()->getQuery('remotiiId', 0);

        $rne = $this->_clientModel->remotiiNameExists($remotiiName, $remotiiId, $this->getLoggedInUserId());
        if (true == $rne) {
            echo '1';
        } else {
            echo '0';
        }
        die('');
    }

    public function ajxGetInboundDataAction() {
        $request = $this->getRequest();
        $remotiiId = $request->getQuery('remotii_id');
        $lastMessageId = $request->getQuery('last_message_id');
        $ajxCount = $request->getQuery('ajax_count', 0);
        $lessThenMessageId = $request->getQuery('ltmsgid') ? true : false;
        $limit = $request->getQuery('limit');
        $limit = $limit ? $limit : '100';
        $userId = $this->getLoggedInUserId();
        $userRemotiiConfig = $this->_clientModel->getUserRemotiiIOconfig($userId, $remotiiId);

        $offset = $userRemotiiConfig['baseRec'][0]['offset'];
        $access_level = $userRemotiiConfig['baseRec'][0]['access_level'];
        $remotiiId = $userRemotiiConfig['baseRec'][0]['remotii_id'];
        $inboundData = $this->_clientModel->getInboundData($remotiiId, $lastMessageId, $lessThenMessageId, $limit);
        $permission = ($userRemotiiConfig['baseRec'][0]['user_id'] == $userId) ? 1 : (int) $userRemotiiConfig['baseRec'][0]['access_level'];
        $remotiiLastRecievedTime = $this->_clientModel->getRemotiiHeartBeatTime($remotiiId);
        $recentStatus = $userRemotiiConfig['baseRec'][0]['remotii_last_input_status'];
        $recentOutputStatus = $userRemotiiConfig['baseRec'][0]['remotii_last_output_status'];
        $oldLastInputStatus = $request->getQuery('oldDin');
        $oldLastOutputStatus = $request->getQuery('oldDout');
        $InboundData = array_reverse($inboundData, true);
        // $XorResult = (int) $recentLastInputStatus ^ (int) $oldLastInputStatus;

        $playSound = 0;
        $AlarmStatus = FALSE;
        if ($ajxCount !== '0') {
            foreach ($InboundData as $recentLastInputStatus) {
                $XorResult = (int) $recentLastInputStatus->din ^ (int) $oldLastInputStatus;
                $XorOutputResult = (int) $recentLastInputStatus->dout ^ (int) $oldLastOutputStatus;
                if ($XorResult != 0) {

                    foreach ($userRemotiiConfig['inConfig'] as $inConfig) {
                        // Pin 1 calculation
                        if (($XorResult & 1) && ($inConfig['pin_number'] == 1)) {

                            $playSound = $inConfig['SoundFlag'];
                            $enable_notification = $inConfig['enable_notification'];
                            $NotificationTrigger = $inConfig['notification_trigger'];
                            if ($enable_notification == 1 && $playSound == 1 && ($NotificationTrigger == 2 || ($NotificationTrigger == ($recentLastInputStatus->din & 1)))) {
                                $AlarmStatus = TRUE;
                                break;
                            }
                        }

                        // Pin 2 calculation
                        if (($XorResult & 2) && ($inConfig['pin_number'] == 2)) {
                            $playSound = $inConfig['SoundFlag'];
                            $enable_notification = $inConfig['enable_notification'];
                            $NotificationTrigger = $inConfig['notification_trigger'];
                            if ($enable_notification == 1 && ($playSound == 1) && (($NotificationTrigger == 2) || ($NotificationTrigger == ($recentLastInputStatus->din & 2)))) {
                                $AlarmStatus = TRUE;
                                break;
                            }
                        }
                        // Pin 3 calculation
                        if (($XorResult & 4) && ($inConfig['pin_number'] == 3)) {
                            $playSound = $inConfig['SoundFlag'];
                            $enable_notification = $inConfig['enable_notification'];
                            $NotificationTrigger = $inConfig['notification_trigger'];
                            if ($enable_notification == 1 && $playSound == 1 && ($NotificationTrigger == 2 || ($NotificationTrigger == ($recentLastInputStatus->din & 4)))) {
                                $AlarmStatus = TRUE;
                                break;
                            }
                        }
                        // Pin 4 calculation
                        if (($XorResult & 8) && ($inConfig['pin_number'] == 4)) {
                            $playSound = $inConfig['SoundFlag'];
                            $enable_notification = $inConfig['enable_notification'];
                            $NotificationTrigger = $inConfig['notification_trigger'];
                            if ($enable_notification == 1 && $playSound == 1 && ($NotificationTrigger == 2 || ($NotificationTrigger == ($recentLastInputStatus->din & 8)))) {
                                $AlarmStatus = TRUE;
                                break;
                            }
                        }
                    }
                }

                if ($XorOutputResult != 0) {
                    foreach ($userRemotiiConfig['outConfig'] as $outConfig) {

                        // Pin 1 calculation
                        if (($XorOutputResult & 1) && ($outConfig['pin_number'] == 1)) {

                            $playSound = $outConfig['SoundFlag'];
                            $enable_notification = $outConfig['enable_notification'];
                            $NotificationTrigger = $outConfig['notification_trigger'];
                            if ($enable_notification == 1 && $playSound == 1 && ($NotificationTrigger == 2 || ($NotificationTrigger == ($recentLastInputStatus->dout & 1)))) {
                                $AlarmStatus = TRUE;
                                break;
                            }
                        }

                        // Pin 2 calculation
                        if (($XorOutputResult & 2) && ($outConfig['pin_number'] == 2)) {
                            $playSound = $outConfig['SoundFlag'];
                            $enable_notification = $outConfig['enable_notification'];
                            $NotificationTrigger = $outConfig['notification_trigger'];
                            if ($enable_notification == 1 && ($playSound == 1) && (($NotificationTrigger == 2) || ($NotificationTrigger == ($recentLastInputStatus->dout & 2)))) {
                                $AlarmStatus = TRUE;
                                break;
                            }
                        }
                        // Pin 3 calculation
                        if (($XorOutputResult & 4) && ($outConfig['pin_number'] == 3)) {
                            $playSound = $outConfig['SoundFlag'];
                            $enable_notification = $outConfig['enable_notification'];
                            $NotificationTrigger = $outConfig['notification_trigger'];
                            if ($enable_notification == 1 && $playSound == 1 && ($NotificationTrigger == 2 || ($NotificationTrigger == ($recentLastInputStatus->dout & 4)))) {
                                $AlarmStatus = TRUE;
                                break;
                            }
                        }
                    }
                }
                $oldLastInputStatus = $recentLastInputStatus->din;
                $oldLastOutputStatus = $recentLastInputStatus->dout;
            }
        }

        foreach ($remotiiLastRecievedTime as $time) {
            $timestamp = $time;
        }
        if ($timestamp == NULL) {
            $latesttime = 0;
        } else {
            $newTime = $timestamp + ((int) $offset) * 60;
            $outTime = date('m/d/Y h:i:s A', $newTime);
            $latesttime = '';
            for ($i = 0; $i < strlen($outTime); $i++) {
                if ($outTime{$i} == ' ' && $i <= 15) {
                    $latesttime = $latesttime . '   ' . $outTime{$i};
                } else {
                    $latesttime = $latesttime . $outTime{$i};
                }
            }
        }
//echo $AlarmStatus;exit();
        $view = new ViewModel(array(
            'userRemotiiConfig' => $userRemotiiConfig,
            'inboundData' => $inboundData,
            'lastMessageId' => $lastMessageId,
            'offset' => $offset,
            'access_level' => $access_level,
            'permission' => $permission,
            'remotii_id' => $remotiiId,
            'latesttime' => $latesttime,
            'AlarmStatus' => $AlarmStatus,
            'recentLastInputStatus' => $recentStatus,
            'recentLastOutputStatus' => $recentOutputStatus
        ));

        $view->setTerminal(true);
        return $view;
    }

    public function ajxGetIoControlAction() {
        $request = $this->getRequest();

        $remotiiId = $request->getQuery('remotii_id');
        $userId = $this->getLoggedInUserId();

        $userRemotiiConfig = $this->_clientModel->getUserRemotiiIOconfig($userId, $remotiiId);

        $view = new ViewModel(array(
            'remotii_id' => $remotiiId,
            'userRemotiiConfig' => $userRemotiiConfig
        ));

        $view->setTerminal(true);
        return $view;
    }

    /**
     * 
     * @return boolean
     */
    protected function getLastUpdatedTimeAction() {
        $request = $this->getRequest();
        $remotiiId = $request->getQuery('remotii_id');
        $lastMessageId = $request->getQuery('last_message_id');
        $lessThenMessageId = $request->getQuery('ltmsgid') ? true : false;
        $limit = $request->getQuery('limit');
        $limit = $limit ? $limit : '100';
        $userId = $this->getLoggedInUserId();
        $userRemotiiConfig = $this->_clientModel->getUserRemotiiIOconfig($userId, $remotiiId);
        $offset = $userRemotiiConfig['baseRec'][0]['offset'];
        $access_level = $userRemotiiConfig['baseRec'][0]['access_level'];
        $remotiiId = $userRemotiiConfig['baseRec'][0]['remotii_id'];
        $inboundData = $this->_clientModel->getInboundData($remotiiId, $lastMessageId, $lessThenMessageId, $limit);
        $permission = ($userRemotiiConfig['baseRec'][0]['user_id'] == $userId) ? 1 : (int) $userRemotiiConfig['baseRec'][0]['access_level'];
        $view = new ViewModel(array(
            'userRemotiiConfig' => $userRemotiiConfig,
            'inboundData' => $inboundData,
            'lastMessageId' => $lastMessageId,
            'offset' => $offset,
            'access_level' => $access_level,
            'permission' => $permission,
            'remotii_id' => $remotiiId
        ));


        return $view;
    }

    protected function getLoggedInUserEmail() {
        if ($this->zfcUserAuthentication()->hasIdentity()) {
            return $this->zfcUserAuthentication()->getIdentity()->getEmail();
        } else {
            return false;
        }
    }

    public function aboutRemotiiAction() {
        return new ViewModel();
    }

    public function aboutAction() {
        return new ViewModel();
    }

    public function infoRspAction() {
        return new ViewModel();
    }

    public function careersAction() {
        return new ViewModel();
    }

    public function featuresAction() {
        return new ViewModel();
    }

    public function contactAction() {
        $request = $this->getRequest();
        if ($request->isPost()) {
            $yourName = $request->getPost('yourName');
            $yourEmail = $request->getPost('yourEmail');
            $message = $request->getPost('message');

            $viewTemplate = 'tpl/email/contact_us_mail';
            $values = array(
                'yourName' => $yourName,
                'yourEmail' => $yourEmail,
                'message' => $message
            );
            $subject = 'Remotii visitor message';
            $mailService = $this->getServiceLocator()->get('goaliomailservice_message');
            $message = $mailService->createTextMessage(ADMIN_EMAIL, $yourEmail, $subject, $viewTemplate, $values);
            try {
                $mailService->send($message);
                $flag = true;
            } catch (\Exception $e) {
                $msg = $e->getMessage();
                $flag = false;
            }
            if ($flag) {
                $this->flashMessenger()->addMessage(array(
                    'status' => 'success',
                    'message' => 'Message sent successfully'
                ));
                return $this->redirect()->toUrl(BASE_URL . '/contact');
            } else {
                $this->flashMessenger()->addMessage(array(
                    'status' => 'fail',
                    'message' => $msg
                ));
            }
        }

        return new ViewModel(array(
            'flashMessages' => $this->flashMessenger()->getCurrentMessages(),
            'contact' => $request->getPost()->toArray()
        ));
    }

    public function whereToBuyAction() {
        return new ViewModel();
    }

    public function gettingStartedAction() {
        return new ViewModel();
    }

    public function remotiiUsesAction() {
        return new ViewModel();
    }

    public function termsAndConditionsAction() {
        return new ViewModel();
    }

    /**
     * Function defined to create stripe profile of the customer
     * 
     */
    public function stripeprofilecreateAction() {
        $postData = $_REQUEST;
        $stripeData = array();
        try {
            $stripeToken = $this->stripeMethod->createToken($postData);
            if ($stripeToken->id <> '') {
                $uid = $this->getLoggedInUserId();

                //  function call to get the login user information
                $userInfo = $this->_clientModel->getLoggedInuserInfo($uid);

                $customerCreateParams = array(
                    'card' => $stripeToken->id,
                    'description' => $userInfo->fname . ' ' . $userInfo->lname,
                    'email' => $userInfo->email
                );

                $customerData = $this->stripeMethod->createCustomer($customerCreateParams);
            }
            $stripeData['stripeId'] = $customerData->id;
            $stripeData['message'] = 'success';
        } catch (\Exception $e) {
            if ($e instanceof \Stripe_CardError OR $e instanceof \Stripe_InvalidRequestError OR $e instanceof \Stripe_AuthenticationError OR $e instanceof \Stripe_ApiConnectionError OR $e instanceof \Stripe_Error) {
                //  Error occured for
                $body = $e->getJsonBody();
                $err = $body['error'];
                $stripeData['stripeId'] = '';
                $stripeData['message'] = $err['message'];
            }
        }

        // your code here ...
        $view = new ViewModel(array(
            'data' => $stripeData
        ));
        $view->setTerminal(true);
        return $view;
    }

    /**
     * Function defined to create stripe profile of the customer
     * 
     */
    public function checkspdefaultconfigAction() {
        $postData = $_REQUEST;

        $settingStatus = $this->_clientModel->chkSPconfigSetting($postData['macAddrs']);

        // your code here ...
        $view = new ViewModel(array(
            'data' => $settingStatus
        ));
        $view->setTerminal(true);
        return $view;
    }

    public function getremotiiperiodically($params) {
        $returnArray = array();
        $time = $params['time'];
        $loggedInUserId = $this->getLoggedInUserId();
        $userRemotiiConf = $this->_clientModel->getPeriodicallyUserRemotiiIOconf($loggedInUserId, $time);
        //        _pre($userRemotiiConf);
        //        $arr1 = array();
        //        $k = 0;
        //        foreach ($userRemotiiConf['baseRec'] as $userRemotiiConfig) {
        //            $arr1[$userRemotiiConfig['remotii_id']] = $userRemotiiConfig;
        //        }
        //        $remotiiArr['baseRec'] = $arr1;
        //        foreach ($userRemotiiConf['baseRec'] as $userRemotiiConfig) {
        //            foreach ($userRemotiiConf['inConfig'][$k] as $inConfig) {
        //                $arr1[$userRemotiiConfig['remotii_id']] = $inConfig;
        //            }
        //            $k++;
        //        }
        //        $remotiiArr['inConfig'] = $arr1;
        //        foreach ($userRemotiiConf['baseRec'] as $userRemotiiConfig) {
        //            foreach ($userRemotiiConf['outConfig'][$k] as $outConfig) {
        //                $arr1[$userRemotiiConfig['remotii_id']] = $outConfig;
        //            }
        //            $k++;
        //        }
        //        $remotiiArr['outConfig'] = $arr1;
        //        _pre($remotiiArr);
        $i = 0;
        $counter = 0;
        $k = 0;
        foreach ($userRemotiiConf['baseRec'] as $userRemotiiConfig) {
            //            _pr($userRemotiiConfig);
            $remotii_id = $userRemotiiConfig['remotii_id'];
            $str = "";

            $str = '<p><a href="#" class="fr remotii-action" status=' . $userRemotiiConfig['urv'] . ' user_remotii_id=' . $userRemotiiConfig['user_remotii_id'] . ' shared_user_id=' . $userRemotiiConfig['srv'] . '></a>';
            if (empty($userRemotiiConfig['shared_user_id'])) {
                $str .= "<a href='" . BASE_URL . "/client/my-remotii/" . $remotii_id . "'>" . $userRemotiiConfig['remotii_name'] . "</a>";
            }

            if (!empty($userRemotiiConfig['shared_user_id'])) {
                $str .= "<a href='" . BASE_URL . "/client/my-remotii/" . $remotii_id . "'>" . $userRemotiiConfig['remotii_name'] . " " . "(shared)" . "</a>";
            }
            $str .= '</p>';
            $i = 0;
            $rmLastStatus = $userRemotiiConfig['remotii_last_input_status'];
            $str .= "<div class='remotii-wrap'";
            if ($userRemotiiConfig['view_status'] == 0) {
                $str .= "style='display:none'";
            }
            $str .= "><span> &nbsp;&nbsp;Input Controls</span>";
            $str .= "<div class='input-color-wrap'>";
            foreach ($userRemotiiConfig['inConfig'] as $inConfig) {
                //                _pr($inConfig);
                $i = $inConfig['pin_number'];
                if ($i == 1) {
                    if ($rmLastStatus & 1) {
                        $tooltip = 'Pin: ' . $inConfig['pin_number'] . '&#10;' . 'State: ' . $inConfig['active_label_text'] . ' ( Energized )';
                        $str .= '<div class="input-color">';
                        $str .= '<span title="' . $tooltip . '" class="ic_out_color' . $i . '" style="background: ' . $inConfig['active_label_color'] . ';"></span>
                                    ' . $inConfig['name'] . '
                                </div>';
                    } else {
                        $tooltip = 'Pin: ' . $inConfig['pin_number'] . '&#10;' . 'State: ' . $inConfig['inactive_label_text'] . ' ( Not Energized )';
                        $str .= '<div class="input-color">';
                        $str .= '<span title="' . $tooltip . '" class="ic_out_color' . $i . '" style="background: ' . $inConfig['inactive_label_color'] . ';"></span>
                                    ' . $inConfig['name'] . '
                                </div>';
                    }
                }
                if ($i == 2) {
                    if ($rmLastStatus & 2) {
                        $tooltip = 'Pin: ' . $inConfig['pin_number'] . '&#10;' . 'State: ' . $inConfig['active_label_text'] . ' ( Energized )';
                        $str .= '<div class="input-color">';
                        $str .= '<span title="' . $tooltip . '" class="ic_out_color' . $i . '" style="background: ' . $inConfig['active_label_color'] . ';"></span>
                                    ' . $inConfig['name'] . '
                                </div>';
                    } else {
                        $tooltip = 'Pin: ' . $inConfig['pin_number'] . '&#10;' . 'State: ' . $inConfig['inactive_label_text'] . ' ( Not Energized )';
                        $str .= '<div class="input-color">';
                        $str .= '<span title="' . $tooltip . '" class="ic_out_color' . $i . '" style="background: ' . $inConfig['inactive_label_color'] . ';"></span>
                                    ' . $inConfig['name'] . '
                                </div>';
                    }
                }
                if ($i == 3) {
                    if ($rmLastStatus & 4) {
                        $tooltip = 'Pin: ' . $inConfig['pin_number'] . '&#10;' . 'State: ' . $inConfig['active_label_text'] . ' ( Energized )';
                        $str .= '<div class="input-color">';
                        $str .= '<span title="' . $tooltip . '" class="ic_out_color' . $i . '" style="background: ' . $inConfig['active_label_color'] . ';"></span>
                                    ' . $inConfig['name'] . '
                                </div>';
                    } else {
                        $tooltip = 'Pin: ' . $inConfig['pin_number'] . '&#10;' . 'State: ' . $inConfig['inactive_label_text'] . ' ( Not Energized )';
                        $str .= '<div class="input-color">';
                        $str .= '<span title="' . $tooltip . '" class="ic_out_color' . $i . '" style="background: ' . $inConfig['inactive_label_color'] . ';"></span>
                                    ' . $inConfig['name'] . '
                                </div>';
                    }
                }
                if ($i == 4) {
                    if ($rmLastStatus & 8) {
                        $tooltip = 'Pin: ' . $inConfig['pin_number'] . '&#10;' . 'State: ' . $inConfig['active_label_text'] . ' ( Energized )';
                        $str .= '<div class="input-color">';
                        $str .= '<span title="' . $tooltip . '" class="ic_out_color' . $i . '" style="background: ' . $inConfig['active_label_color'] . ';"></span>
                                    ' . $inConfig['name'] . '
                                </div>';
                    } else {
                        $tooltip = 'Pin: ' . $inConfig['pin_number'] . '&#10;' . 'State: ' . $inConfig['inactive_label_text'] . ' ( Not Energized )';
                        $str .= '<div class="input-color">';
                        $str .= '<span title="' . $tooltip . '" class="ic_out_color' . $i . '" style="background: ' . $inConfig['inactive_label_color'] . ';"></span>
                                    ' . $inConfig['name'] . '
                                </div>';
                    }
                }
            }

            $str .= "</div>";
            $str .= "<div>";
            $str .= "<span> &nbsp;&nbsp; Output Controls </span>";

            $str .= "<div class='input-color-wrap brdr'>";
            $j = 0;
            $rmLastStatus = $userRemotiiConfig['remotii_last_output_status'];
            foreach ($userRemotiiConfig['outConfig'] as $outConfig) {
                //                _pr($outConfig);
                $i = $outConfig['pin_number'];
                if ($i == 1) {
                    if ($rmLastStatus & 1) {
                        $tooltip = 'Pin: ' . $outConfig['pin_number'] . '&#10;' . 'State: ' . $outConfig['active_label_text'] . ' ( Energized )';
                        $str .= '<div class="input-color">';
                        $str .= '<span title="' . $tooltip . '" class="ic_out_color' . $i . '" style="background: ' . $outConfig['active_label_color'] . ';"></span>
                                    ' . $outConfig['name'] . '
                                </div>';
                    } else {
                        $tooltip = 'Pin: ' . $outConfig['pin_number'] . '&#10;' . 'State: ' . $outConfig['inactive_label_text'] . ' ( Not Energized )';
                        $str .= '<div class="input-color">';
                        $str .= '<span title="' . $tooltip . '" class="ic_out_color' . $i . '" style="background: ' . $outConfig['inactive_label_color'] . ';"></span>
                                    ' . $outConfig['name'] . '
                                </div>';
                    }
                }
                if ($i == 2) {
                    if ($rmLastStatus & 2) {
                        $tooltip = 'Pin: ' . $outConfig['pin_number'] . '&#10;' . 'State: ' . $outConfig['active_label_text'] . ' ( Energized )';
                        $str .= '<div class="input-color">';
                        $str .= '<span title="' . $tooltip . '" class="ic_out_color' . $i . '" style="background: ' . $outConfig['active_label_color'] . ';"></span>
                                    ' . $outConfig['name'] . '
                                </div>';
                    } else {
                        $tooltip = 'Pin: ' . $outConfig['pin_number'] . '&#10;' . 'State: ' . $outConfig['inactive_label_text'] . ' ( Not Energized )';
                        $str .= '<div class="input-color">';
                        $str .= '<span title="' . $tooltip . '" class="ic_out_color' . $i . '" style="background: ' . $outConfig['inactive_label_color'] . ';"></span>
                                    ' . $outConfig['name'] . '
                                </div>';
                    }
                }
                if ($i == 3) {
                    if ($rmLastStatus & 4) {
                        $tooltip = 'Pin: ' . $outConfig['pin_number'] . '&#10;' . 'State: ' . $outConfig['active_label_text'] . ' ( Energized )';
                        $str .= '<div class="input-color">';
                        $str .= '<span title="' . $tooltip . '" class="ic_out_color' . $i . '" style="background: ' . $outConfig['active_label_color'] . ';"></span>
                                    ' . $outConfig['name'] . '
                                </div>';
                    } else {
                        $tooltip = 'Pin: ' . $outConfig['pin_number'] . '&#10;' . 'State: ' . $outConfig['inactive_label_text'] . ' ( Not Energized )';
                        $str .= '<div class="input-color">';
                        $str .= '<span title="' . $tooltip . '" class="ic_out_color' . $i . '" style="background: ' . $outConfig['inactive_label_color'] . ';"></span>
                                    ' . $outConfig['name'] . '
                                </div>';
                    }
                }
            }
            //            foreach ($userRemotiiConfig['outConfig'] as $outConfig) {
            ////$i++;
            ////                $j = $outConfig['pin_number'];
            ////                $tooltip = 'Pin: ' . $outConfig['pin_number'] . '&#10;' . 'State: ' . $outConfig['active_label_text'] . ' ( Energized )';
            ////                $str.= "<div class='input-color'>";
            ////
            ////                $str.='<span title=' . $tooltip . ' class="color-output ic_op_color' . $j . '" id="statusClr' . $outConfig['config_id'] . '" style="background:';
            ////                if ($outConfig['output_initial_state'] == 1) {
            ////                    $outConfig['active_label_color'];
            ////                } else {
            ////                    $outConfig['inactive_label_color'];
            ////                }
            ////                $str.=">
            ////                                        </span>";
            ////                $str.='<label id="statusTxt' . $outConfig['config_id'] . '" class="ic_op_status' . $j . '">';
            ////
            ////                if ($outConfig['output_initial_state'] == 1) {
            ////                    $outConfig['active_label_text'];
            ////                } else {
            ////                    $outConfig['inactive_label_text'];
            ////                }
            ////                $str.="</div>";
            //
            //
            //                $j = $outConfig['pin_number'];
            //                $tooltip = 'Pin: ' . $outConfig['pin_number'] . '&#10;' . 'State: ' . $outConfig['active_label_text'] . ' ( Energized )';
            //
            //                $str.='<div class="input-color">
            //                    <span title="' . $tooltip . '" class="color-output ic_op_color' . $j . ' " id="statusClr ' . $outConfig['config_id'] . ' " style="background: 
            //                    ' . (($outConfig['output_initial_state'] == 1) ? $outConfig['active_label_color'] : $outConfig['inactive_label_color']) . '">
            //                    </span>
            //                    <label id="statusTxt ' . $outConfig['config_id'] . ' " class="ic_op_status' . $j . ' ">
            //
            //                        ' . (($outConfig['output_initial_state'] == 1) ? $outConfig['active_label_text'] : $outConfig['inactive_label_text']) . '
            //
            //                    </label>
            //                </div>';
            //            }
            $str .= " </div>
                        </div>
                    </div>
                </div>";
            $k = $k + 1;
            $returnArray[$counter]['remotii_id'] = $userRemotiiConfig['remotii_id'];
            $returnArray[$counter]['remotii_status'] = $userRemotiiConfig['view_status'];
            $returnArray[$counter]['remotii_html'] = $str;
            $counter++;
            $i++;
        }
        return $data = array(
            'userRemotiiConf' => $returnArray,
            'params' => $params,
            'time' => time(),
            'count' => count($returnArray)
        );
    }

    public function eventSchedulerAction() {

        $loggedInUserId = $this->getLoggedInUserId();
        $loggedInUserEmail = $this->getLoggedInUserEmail();
        if ($loggedInUserId) {
            $form = new EventSchedulerForm();
            $request = $this->getRequest();
            $post = $request->getPost()->toArray();
            $remotiiId = (int) $this->params()->fromRoute('id', 0);
            $rid = $remotiiId;
            $remotiiMacData = $this->_clientModel->getRemotiiNameMacId($loggedInUserId, $remotiiId);
            //            _pre($remotiiMacData);
            $InputConfig = $this->_clientModel->getInputConfig($loggedInUserId, $remotiiId);
            $OutputConfig = $this->_clientModel->getOutputConfig($loggedInUserId, $remotiiId);
            $mac_address = $remotiiMacData[0]['mac_address'];
            $form->setData($post);
            if ($request->isPost()) {

                $this->_clientModel->saveEvent($post, $mac_address, $rid, $loggedInUserId);
                return $this->redirect()->toRoute('remotiifrontend', array(
                            'action' => 'event-scheduler',
                            'id' => $remotiiId
                ));
            }

            $data = $this->_clientModel->selectquery($rid);
            $viewModel = new ViewModel(array(
                'form' => $form,
                'data' => $data,
                'OutputConfig' => $OutputConfig,
                'InputConfig' => $InputConfig,
                'rid' => $rid,
                'remotiiMacData' => $remotiiMacData,
                'mac_address' => $mac_address
            ));
            $viewModel->setTerminal(true);
            return $viewModel;
        }

        //return new ViewModel(array('data' => $post));
    }

    public function chainedEventSchedulerAction() {
        $loggedInUserId = $this->getLoggedInUserId();
        $loggedInUserEmail = $this->getLoggedInUserEmail();
        if ($loggedInUserId) {

            $form = new ChainedEventSchedulerForm();
            $request = $this->getRequest();
            $post = $request->getPost()->toArray();
            $remotiiId = (int) $this->params()->fromRoute('id', 0);
            $rid = $remotiiId;
            $remotiiMacData = $this->_clientModel->getRemotiiNameMacId($loggedInUserId, $remotiiId);
            $OutputConfig = $this->_clientModel->getOutputConfig($loggedInUserId, $remotiiId);
            $InputConfig = $this->_clientModel->getInputConfig($loggedInUserId, $remotiiId);
            $userRemotii = $this->_clientModel->getClientRemotii($loggedInUserId, $remotiiId);
            $chainedEevent = array();
            $form->setData($post);
            if ($request->isPost()) {
                $remotiiId = (int) $this->params()->fromRoute('id', 0);
                $this->_clientModel->insertChainedEvent($post, $rid);
                return $this->redirect()->toRoute('remotiifrontend', array(
                            'action' => 'chained-event-scheduler',
                            'id' => $remotiiId
                ));
            }
            $data = $this->_clientModel->getChainedEvents($rid);
            $viewModel = new ViewModel(array(
                'form' => $form,
                'data' => $data,
                'remotiiMacData' => $remotiiMacData,
                'userRemotii' => $userRemotii,
                'OutputConfig' => $OutputConfig,
                'rid' => $rid,
                'InputConfig' => $InputConfig
            ));
            $viewModel->setTerminal(true);
            return $viewModel;
        }
    }

    public function getRemotiiOutputConfigAction() {
        $loggedInUserId = $this->getLoggedInUserId();
        $request = $this->getRequest();
        $response = $this->getResponse();
        $remotiiId = $request->getPost('remotiiId');
        $OutputConfig = $this->_clientModel->getOutputConfig($loggedInUserId, $remotiiId);
        $form = new ChainedEventSchedulerForm();
        foreach ($OutputConfig as $outputconfig) {
            $configName = ($outputconfig[is_output_momentary] == 1) ? $outputconfig['name'] . ' ' . '(Momentary)' : $outputconfig['name'];

            $result.= '<input type="hidden" name="dest_pin_output_config_id[' . $outputconfig['config_id'] . ']" value="' . $outputconfig[pin_number] . '">
                   <input type="hidden" name="dest_pin_number[]" value="' . $outputconfig[pin_number] . '">
                   <input type="hidden" name="dest_IsMomentary[' . $outputconfig['config_id'] . ']" value="' . $outputconfig[is_output_momentary] . '">
                    <tr> 
                    
                   <input type="hidden" name="dest_pin_output_config_id[' . $outputconfig['config_id'] . ']" value="' . $outputconfig[pin_number] . '">
                   <input type="hidden" name="dest_pin_number[]" value="' . $outputconfig[pin_number] . '">
                   <input type="hidden" name="dest_IsMomentary[' . $outputconfig['config_id'] . ']" value="' . $outputconfig[is_output_momentary] . '">
                    
                
                 
                 <td class="column">
                 <input type="checkbox" name="dest_output[]" class="check dest_output" id="check2" value="' . $outputconfig['config_id'] . '">
                 <span>' . $configName . '</span>
                 </td>';
            $result .= '<td class="column"><div class="colorbox" style="background:' . $outputconfig['active_label_color'] . '" ></div>';
            if ($outputconfig[is_output_momentary] == 0) {
                $result.= '<input type="radio" class ="radio dest_state r1"  name="dest_radioState[' . $outputconfig['config_id'] . ']" value="1"  disabled  >';
                $result .= '<span>' . $outputconfig['active_label_text'] . '</span></td>';
            } else {
                $result .= '<span style="margin-left:14px;">' . $outputconfig['active_label_text'] . '</span></td>';
            }
            $result .= ' <td class="column">
                         <div class="colorbox " style="background:' . $outputconfig['inactive_label_color'] . '" ></div>';
            if ($outputconfig[is_output_momentary] == 0) {
                $result .= '<input type="radio" class ="radio" name="dest_radioState[' . $outputconfig['config_id'] . ']" value="0" disabled> ';
                $result .= '<span>' . $outputconfig['inactive_label_text'] . '</span></td></tr>';
            } else {
                $result .= '<span style="margin-left:14px;">' . $outputconfig['inactive_label_text'] . '</span></td></tr>';
            }
        }
        return $response->setContent($result);
    }

    public function eventSchedulerEditAction() {

        $loggedInUserId = $this->getLoggedInUserId();
        $remotiiId = (int) $this->params()->fromRoute('id', 0);
        $rid = $remotiiId;
        //  Logged in user email Id
        $loggedInUserEmail = $this->getLoggedInUserEmail();
        if ($loggedInUserId) {
            $form = new EventSchedulerForm();
            $request = $this->getRequest();
            $post = $request->getPost()->toArray();
            $eventId = (int) $this->params()->fromRoute('id', 0);
            $eventData = $this->_clientModel->getEventScheduleData($eventId);
            $eventData['occurence_days'] = $this->array_map_recursive("trim", (array) explode(",", $eventData['occurence_days']));
            $remotiiId = (int) $eventData['remotii_id'];
            $remotiiMacData = $this->_clientModel->getRemotiiNameMacId($loggedInUserId, $remotiiId);
            $InputConfig = $this->_clientModel->getInputConfig($loggedInUserId, $remotiiId);
            $OutputConfig = $this->_clientModel->getOutputConfig($loggedInUserId, $remotiiId);


            $eventData['output_bits_flags'] = array_reverse(array_pad(str_split(decbin($eventData['output_bits'])), -3, 0));

            $eventData['dout_set_flags'] = array_reverse(array_pad(str_split(decbin($eventData['dout_set'])), -3, 0));
            $eventData['dout_clr_flags'] = array_reverse(array_pad(str_split(decbin($eventData['dout_clr'])), -3, 0));
            $eventData['input_bits_flags'] = array_reverse(array_pad(str_split(decbin($eventData['input_bits'])), -4, 0));
            $eventData['input_cond_flags'] = array_reverse(array_pad(str_split(decbin($eventData['input_cond'])), -4, 0));
            //            _pr($eventData);
            //            _pre($InputConfig);

            $mac_address = $remotiiMacData[0]['mac_address'];
            $form->setData($post);
            if ($request->isPost()) {
                //                _pre($post);

                $this->_clientModel->updateEvent($post, $mac_address, $eventId, $loggedInUserId);
                return $this->redirect()->toRoute('remotiifrontend', array(
                            'action' => 'event-scheduler',
                            'id' => $remotiiId
                ));
            }

            $data = $this->_clientModel->selectquery($remotiiId);

            $viewModel = new ViewModel(array(
                'form' => $form,
                'eventData' => $eventData,
                'data' => $data,
                'OutputConfig' => $OutputConfig,
                'InputConfig' => $InputConfig,
                'rid' => $rid,
                'eventId' => $eventId,
                'remotiiMacData' => $remotiiMacData,
                'mac_address' => $mac_address
            ));
            $viewModel->setTerminal(true);

            return $viewModel;
        }

        //return new ViewModel(array('data' => $post));
    }

    public function chainedEventSchedulerEditAction() {

        $loggedInUserId = $this->getLoggedInUserId();
        $remotiiId = (int) $this->params()->fromRoute('id', 0);
        $rid = $remotiiId;
        //  Logged in user email Id
        $loggedInUserEmail = $this->getLoggedInUserEmail();
        if ($loggedInUserId) {

            $form = new ChainedEventSchedulerForm();
            $request = $this->getRequest();
            $post = $request->getPost()->toArray();
            $eventId = (int) $this->params()->fromRoute('id', 0);

            $eventData = $this->_clientModel->getChainedEventData($eventId);
            $userRemotii = $this->_clientModel->getClientRemotii($loggedInUserId, $eventData['source_remotii']);
            $remotiiId = (int) $eventData['remotii_id'];
            $remotiiMacData = $this->_clientModel->getRemotiiNameMacId($loggedInUserId, $eventData['source_remotii']);

            //destination remotii
            $OutputConfig = $this->_clientModel->getOutputConfig($loggedInUserId, $eventData['destination_remotii']);

            $eventData['dest_output_bits_flags'] = array_reverse(array_pad(str_split(decbin($eventData['dest_output_bits'])), -3, 0));
            $eventData['dest_dout_set_flags'] = array_reverse(array_pad(str_split(decbin($eventData['trigger_condition'])), -3, 0));
            //source remotii Output
            $srcOutputConfig = $this->_clientModel->getOutputConfig($loggedInUserId, $eventData['source_remotii']);
            $eventData['src_output_bits_flags'] = array_reverse(array_pad(str_split(decbin($eventData['source_output_bits'])), -3, 0));
            $eventData['src_dout_set_flags'] = array_reverse(array_pad(str_split(decbin($eventData['source_output_condition'])), -3, 0));
            //source remotii input
            $srcInputConfig = $this->_clientModel->getInputConfig($loggedInUserId, $eventData['source_remotii']);
            $eventData['src_input_bits_flags'] = array_reverse(array_pad(str_split(decbin($eventData['source_input_bits'])), -4, 0));
            $eventData['src_din_set_flags'] = array_reverse(array_pad(str_split(decbin($eventData['source_input_condition'])), -4, 0));
            $mac_address = $remotiiMacData[0]['mac_address'];
            $form->setData($post);
            if ($request->isPost()) {
                $this->_clientModel->updateChainedEvent($post, $eventData['source_remotii'], $eventId, $loggedInUserId);
                return $this->redirect()->toRoute('remotiifrontend', array(
                            'action' => 'chained-event-scheduler',
                            'id' => $eventData['source_remotii']
                ));
            }

            $data = $this->_clientModel->selectquery($remotiiId);
            $viewModel = new ViewModel(array(
                'form' => $form,
                'eventData' => $eventData,
                'data' => $data,
                'OutputConfig' => $OutputConfig,
                'srcoutputConfig' => $srcOutputConfig,
                'InputConfig' => $srcInputConfig,
                'rid' => $rid,
                'eventId' => $eventId,
                'remotiiMacData' => $remotiiMacData,
                'mac_address' => $mac_address,
                'userRemotii' => $userRemotii,
            ));
            $viewModel->setTerminal(true);

            return $viewModel;
        }

        //return new ViewModel(array('data' => $post));
    }

    public function delete($params) {
	
	    $Id = $params['iddel'];
        if (!$params['table']) {
            $this->_clientModel->deleteremotii($Id);
        } else {
            //deleteChainedEvent
            $this->_clientModel->deleteChainedEvent($Id);
        }
        return true;
    }

    public function active($params) {
        $id = $params['id'];
        $value = $params['value'];
        $table = ($params['table'] == '') ? 'event_scheduler' : $params['table'];
        if ($table == 'event_scheduler') {
            $result = $this->_clientModel->active($value, $id);
        } else {
            $result = $this->_clientModel->changeChainedEventStatus($value, $id);
        }

        if ($result) {
            $status = "success";
            $message = "Share person inserted successfully";
        } else {
            $status = "Failed";
            $message = ($table == 'event_scheduler')?"Error in inserting share person":"One or more of the specified input conditions or resulting action pins have been disabled. Edit and re-save the chained event to continue. ";
        }

        return $data = array(
            'status' => $status,
            'message' => $message,
            'params' => $params
        );
    }

    public function deleteAction() {
        $remotiiId = $this->params('id', '0');
        //        _pre($remotiiId);

        $this->_clientModel->deleteremotii($remotiiId);
        return $this->redirect()->toRoute(NULL, array(
                    'controller' => 'index',
                    'action' => 'delete'
        ));
    }

    public function enduserAction() {
        $viewModel = new ViewModel();
        return $viewModel;
    }

    public function remotiiSharingAction() {
        $loggedInUserId = $this->getLoggedInUserId();
        $remotiiId = $this->params('id');
        $user = $this->_modelUsers->userInfo($remotiiId);
        $remotiiname = $user[0]->remotii_name;
        $SharePersonInfo = $this->_modelUsers->SharePersonInf($loggedInUserId, $remotiiId);
        if (empty($SharePersonInfo)) {

            $message = "This Remotii is not currently shared with any users.";
        }

        $viewModel = new ViewModel(array(
            'SharePersons' => $SharePersonInfo,
            'remotiiname' => $remotiiname,
            'id' => $remotiiId,
            'message' => $message
        ));
        $viewModel->setTerminal(true);
        return $viewModel;
    }

    public function checkemail($params) {
        $loggedInUserId = $this->getLoggedInUserId();
        $email = $params['email'];
        $status = "error";

        $remotiiId = $params['remotii_id'];
        $userid = $this->_modelUsers->getUserId($email);
        $user = $userid[0]->user_id;
        if (empty($userid)) {
            $regex = '/^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})$/';
            if (preg_match($regex, $email)) {
                $errmess = "No user available with this email.";
            } else {
                $errmess = "Invalid email.";
            }
        } else {
            if ($userid[0]->user_id == $loggedInUserId) {
                $errmess = "Remotii cannot be shared to the remotii owner.";
            } elseif ($this->_modelUsers->checkUser($loggedInUserId, $email, $remotiiId)) {
                $status = "success";
            } else {
                $errmess = "Remotii already shared with " . $email;
            }
        }
        return $data = array(
            'status' => $status,
            'errmess' => $errmess,
            'params' => $params
        );
    }

    public function InsertSharePerson($params) {
        $checkEmailData = $this->checkemail($params);
        if ($checkEmailData['status'] == 'success') {
            $email = $params['email'];
            $AccessId = $params['AccessId'];
            $user = $this->_modelUsers->getUserId($email);
            $loggedInUserId = $this->getLoggedInUserId();
            $remotii_id = $params['remotii_id'];

            if ($this->_modelUsers->checkUser($loggedInUserId, $email, $remotii_id)) {
                $username = $user[0]->username;
                $sharedUsersId = $user[0]->user_id;
                $userRemotiiId = $this->_modelUsers->getuserRemotiiId($remotii_id, $loggedInUserId);
                $userRemotiiID = $userRemotiiId[0]->user_remotii_id;
                $servicePro = $this->_modelUsers->getservice($userRemotiiID);
                if ($this->_modelUsers->insertService($userRemotiiID, $sharedUsersId, $AccessId, $servicePro)) {
                    $status = "success";
                    $message = "share person inserted successfully";
                } else {
                    $message = "Error in inserting share person";
                }
            } else {
                $message = "This is already a shared person";
            }
        } else {
            $message = $checkEmailData['errmess'];
        }

        return $data = array(
            'status' => $status,
            'result' => $message,
            'params' => $params,
            'email' => $email,
            'username' => $username
        );
    }

    public function iajaxAction() {
        $viewmodel = new ViewModel();
        $request = $this->getRequest();
        $response = $this->getResponse();

        $post = array_merge_recursive($this->getRequest()->getPost()->toArray(), $this->getRequest()->getFiles()->toArray());
	
        $action = $post['action'];
	$params = $post['params'];


	if (method_exists($this, $action)){
		
	    $result = $this->$action($params);
        } else {
            $result = array(
                'status' => 'FAIL',
                'result' => 'Action not exists'
            );
        }

        return $response->setContent(\Zend\Json\Json::encode($result));
    }

    /**
     * Function array_map_recursive() Implemented for 
     * getting mapped array with callback function
     *  
     * @param type $param 
     * 
     * @return array 
     */
    public function array_map_recursive($callback, $array) {
        foreach ($array as $key => $value) {
            if (is_array($array[$key])) {
                $array[$key] = $this->array_map_recursive($callback, $array[$key]);
            } else {
                $array[$key] = call_user_func($callback, $array[$key]);
            }
        }
        return $array;
    }

    public function editAccess($params) {
        $access_level = $params['access_level'];
        $email = $params['email'];
        $remotii_id = $params['remotii_id'];
        $loggedInUserId = $this->getLoggedInUserId();
        $userRemotiiId = $this->_modelUsers->getuserRemotiiId($remotii_id, $loggedInUserId);
        $userRemotiiID = $userRemotiiId[0]->user_remotii_id;
        $user = $this->_modelUsers->getUserId($email);
        $sharedUsersId = $user[0]->user_id;
        if ($this->_modelUsers->updateAccess($userRemotiiID, $sharedUsersId, $access_level)) {
            $status = "success";
            $message = "Access level updated successfully";
        } else {
            $message = "Error in updating Access level";
        }
        return $data = array(
            'status' => $status,
            'result' => $message,
            'params' => $params
        );
    }

    public function deleteShare($params) {
        $email = $params['email'];
        $remotii_id = $params['remotii_id'];
        $loggedInUserId = $this->getLoggedInUserId();
        $userRemotiiId = $this->_modelUsers->getuserRemotiiId($remotii_id, $loggedInUserId);
        $userRemotiiID = $userRemotiiId[0]->user_remotii_id;
        $user = $this->_modelUsers->getUserId($email);
        $sharedUsersId = $user[0]->user_id;
        if ($this->_modelUsers->deleteShareRemotii($userRemotiiID, $sharedUsersId)) {
            $status = "success";
            $message = "shared remotii deleted successfully";
        } else {
            $message = "Error in deleting shared remotii";
        }
        return $data = array(
            'status' => $status,
            'result' => $message,
            'params' => $params
        );
    }

    public function changeViewStatus($params) {
        $status = $params['status'];
        // _pr($status);
        if ($status == 1) {

            $status = 0;
        } else {

            $status = 1;
        }
        //_pre($status);
        $user_remotii_id = $params['user_remotii_id'];
        $shared_user_id = $params['shared_user_id'];
        if (empty($shared_user_id)) {
            if ($this->_clientModel->updateViewStatusInUserRemotii($status, $user_remotii_id)) {
                $stat = "success";
                $message = "View status updated successfully";
            } else {
                $message = "Error in updating view status";
            }
        } elseif (!empty($shared_user_id)) {
            // die("hi");
            if ($this->_clientModel->updateViewStatusInSharedRemotii($status, $user_remotii_id, $shared_user_id)) {
                $stat = "success";
                $message = "View status updated successfully";
            } else {
                $message = "Error in updating view status";
            }
        }
        return $data = array(
            'stat' => $stat,
            'status' => $status,
            'result' => $message,
            'params' => $params
        );
    }

    public function sendPaymentFailureMail($to, $subject, $params = array()) {
        $mailService = $this->getServiceLocator()->get('goaliomailservice_message');
        $message = $mailService->createHtmlMessage(ADMIN_EMAIL, $to, $subject, 'tpl/email/stripefailedmail', $params);
        try {
            $mailService->send($message);
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            error_log(json_encode(array(
                'message' => 'Mail not sent',
                'email' => $to,
                'exception' => $msg
                    )), 3, '/var/www/remotii/public/cronNotification.fn.log');
        }
    }

    public function forgotAction() {
        $service = $this->getPasswordService();
        $service->cleanExpiredForgotRequests();
        $error = '';
        $request = $this->getRequest();
        $form = $this->getForgotForm();

        if ($this->getRequest()->isPost()) {
            $form->setData($this->getRequest()->getPost());
            if ($form->isValid()) {
                //                $userService = $this->getUserService();

                $email = $this->getRequest()->getPost()->get('email', '');
                $username = $this->getRequest()->getPost()->get('username', '');

                $userId = $this->_modelUsers->findUserByEmailAndUsername($email, $username);


                //                $user = $userService->getUserMapper()->findByEmail($email);
                //only send request when email is found
                if ($userId) {
                    $service->sendProcessForgotRequest($userId, $email);
                    $vm = new ViewModel(array(
                        'email' => $email
                    ));
                    $vm->setTemplate('goalio-forgot-password/forgot/sent');
                    return $vm;
                } else {
                    $error = "Invalid combination of username and email-id.";
                }
            }
        }


        // Render the form
        $vm = new ViewModel(array(
            'forgotForm' => $form,
            'error' => $error
        ));
        $vm->setTemplate('goalio-forgot-password/forgot/forgot');
        return $vm;
    }

    /**
     * Getters/setters for DI stuff
     */
    public function getUserService() {
        if (!$this->userService) {
            $this->userService = $this->getServiceLocator()->get('zfcuser_user_service');
        }
        return $this->userService;
    }

    public function setUserService(UserService $userService) {
        $this->userService = $userService;
        return $this;
    }

    public function getPasswordService() {
        if (!$this->passwordService) {
            $this->passwordService = $this->getServiceLocator()->get('goalioforgotpassword_password_service');
        }
        return $this->passwordService;
    }

    public function setPasswordService(PasswordService $passwordService) {
        $this->passwordService = $passwordService;
        return $this;
    }

    public function getForgotForm() {
        if (!$this->forgotForm) {
            $this->setForgotForm($this->getServiceLocator()->get('goalioforgotpassword_forgot_form'));
        }
        return $this->forgotForm;
    }

    public function setForgotForm(Form $forgotForm) {
        $this->forgotForm = $forgotForm;
    }

    public function getResetForm() {
        if (!$this->resetForm) {
            $this->setResetForm($this->getServiceLocator()->get('goalioforgotpassword_reset_form'));
        }
        return $this->resetForm;
    }

    public function setResetForm(Form $resetForm) {
        $this->resetForm = $resetForm;
    }

    /**
     * set options
     *
     * @param ForgotControllerOptionsInterface $options
     * @return ForgotController
     */
    public function setOptions(ForgotControllerOptionsInterface $options) {
        $this->options = $options;
        return $this;
    }

    /**
     * get options
     *
     * @return ForgotControllerOptionsInterface
     */
    public function getOptions() {
        if (!$this->options instanceof ForgotControllerOptionsInterface) {
            $this->setOptions($this->getServiceLocator()->get('goalioforgotpassword_module_options'));
        }
        return $this->options;
    }

    public function getZfcUserOptions() {
        if (!$this->zfcUserOptions instanceof PasswordOptionsInterface) {
            $this->zfcUserOptions = $this->getServiceLocator()->get('zfcuser_module_options');
        }
        return $this->zfcUserOptions;
    }

    /**
     *
     * @param type $postData        	
     * @return boolean
     */
    public function validateServiceProviderForm($postData, $id, $rid) {
        $status = false;
        $validator = new emailIdValidation();

        $errorResponse['0'] = (object) array(
                    'fname' => $postData['fName'],
                    'lname' => $postData['lName'],
                    'phone' => $postData['phoneNumber'],
                    'email' => $postData['emailId'],
                    'street' => $postData['street'],
                    'city' => $postData['city'],
                    'state' => $postData['state'],
                    'country' => $postData['country'],
                    'zip_code' => $postData['zip'],
                    'company' => $postData['company'],
                    'shbillingDetails' => $postData['shbillingDetails'],
                    'contracted_price' => $postData['contracted_price'],
                    'allow_end_user_billing' => $postData['allow_end_user_billing'],
                    'service_fee' => $postData['service_fee'],
                    'end_user_price' => $postData['end_user_price'],
                    'service_provider_credit' => $postData['service_provider_credit'],
                    'routing_number' => $postData['routing_number'],
                    'account_type' => $postData['account_type'],
                    'account_number' => $postData['account_number'],
                    'nob_firstname' => $postData['nob_firstname'],
                    'nob_lastname' => $postData['nob_lastname'],
                    'card_holder' => $postData['card_holder'],
                    'card_number' => $postData['card_number'],
                    'expMonth' => $postData['expMonth'],
                    'expYear' => $postData['expYear'],
                    'name_on_bank' => $postData['name_on_bank'],
                    'cardType' => $postData['cardType'],
                    'account_type' => $postData['account_type'],
                    'user_username' => $postData['user_username'],
                    'user_fname' => $postData['user_fname'],
                    'user_lname' => $postData['user_lname'],
                    'user_phone' => $postData['user_phone'],
                    'user_email' => $postData['user_email'],
                    'password' => $postData['password'],
                    'cnfrmPassword' => $postData['cnfrmPassword'],
                    'captcha' => $postData['captcha']
        );

        //        if ($postData ['contracted_price'] == '') {
        //            $errorResponse ['err_contracted_price'] = 1;
        //            $status = true;
        //        }

        $recaptcha = $postData['g-recaptcha-response'];
        if (!empty($recaptcha)) {
            $google_url = "https://www.google.com/recaptcha/api/siteverify";
            $secret = GOOGLE_CAPTCHA_VERIFICATION_SECRET;
            $ip = $_SERVER['REMOTE_ADDR'];
            $url = $google_url . "?secret=" . $secret . "&response=" . $recaptcha . "&remoteip=" . $ip;
            $res = $this->getCurlData($url);
            $res = json_decode($res, true);

            //reCaptcha success check 
            if ($res['success']) {
                
            } else {
                $errorResponse['err_captcha'] = 1;
                $status = true;
            }
        } else {
            $errorResponse['err_captcha'] = 1;
            $status = true;
        }

        if ($postData['end_user_price'] == '') {
            $errorResponse['err_end_user_price'] = 1;
            $status = true;
        }

        if ($postData['company'] == '') {
            $errorResponse['company'] = 1;
            $status = true;
        } else {
            if ($this->_modelUsers->companyNameExists($postData['company'])) {
                $errorResponse['company'] = 2;
                $status = true;
            }
        }

        if ($postData['fName'] == '') {
            $errorResponse['errfName'] = 1;
            $status = true;
        }

        if ($postData['lName'] == '') {
            $errorResponse['errlName'] = 1;
            $status = true;
        }
        if ($postData['phoneNumber'] == '') {
            $errorResponse['errphoneNumber'] = 1;
            $status = true;
        }
        if (!$validator->isValid($postData['emailId'])) {
            $errorResponse['erremailId'] = 1;
            $status = true;
        } else {
            // Email Duplicasy chk
            $uid = $this->_modelUsers->emailDuplicasyChkSP($postData['emailId'], $id, $rid);
            if ($uid > 0) {
                $errorResponse['errEmailDuplicasy'] = 1;
                $status = true;
            }
        }

        if ($postData['shbillingDetails']) {
            if ($postData['card_holder'] == '') {
                $errorResponse['err_card_holder'] = 1;
                $status = true;
            }

            if ($postData['card_number'] != '') {
                if (!$this->checkCreditCard($postData['card_number'], $postData['cardType'], $errornumber, $errortext)) {
                    $errorResponse['errcnCC'] = 1;
                    $status = true;
                }
            }
        }

        if ($postData['card_number'] != '') {
            if (!$this->checkCreditCard($postData['card_number'], $postData['cardType'], $errornumber, $errortext)) {
                $errorResponse['errcnCC'] = 1;
                $status = true;
            }
        }

        //       validations by aditya
        //        $postData['contracted_price'] = ($postData['contracted_price'] == "" ? 0 : $postData['contracted_price']);
        //        if ($postData['contracted_price'] >= 0) {
        //            if (!is_numeric($postData['contracted_price'])) {
        //                $errorResponse ['contracted_price'] = 1;
        //                $status = true;
        //            }
        //        } else {
        //            $errorResponse ['contracted_price'] = 2;
        //            $status = true;
        //        }

        $postData['end_user_price'] = ($postData['end_user_price'] == "" ? 0 : $postData['end_user_price']);
        if ($postData['end_user_price'] >= 0) {
            if (!is_numeric($postData['end_user_price'])) {
                $errorResponse['end_user_price'] = 1;
                $status = true;
            }
        } else {
            $errorResponse['end_user_price'] = 2;
            $status = true;
        }
        //      service_provider_credit
        //        $postData['service_provider_credit'] = ($postData['service_provider_credit'] == "" ? 0 : $postData['service_provider_credit']);
        //        if ($postData['service_provider_credit'] >= 0) {
        //            if (!is_numeric($postData['service_provider_credit'])) {
        //                $errorResponse ['service_provider_credit'] = 1;
        //                $status = true;
        //            }
        //        } else {
        //            $errorResponse ['service_provider_credit'] = 2;
        //            $status = true;
        //        }

        if ($postData['user_username'] == '') {
            $errorResponse['errUserUserName'] = 1;
            $status = true;
        } else {
            // USer name Duplicasy chk
            $uid = $this->_modelUsers->userNameDuplicasyChk($postData['user_username'], $id = 0);
            if ($uid > 0) {
                $errorResponse['errUserUsernameDuplicasy'] = 1;
                $status = true;
            }
        }


        if ($postData['user_fname'] == '') {
            $errorResponse['errUserfName'] = 1;
            $status = true;
        }
        if ($postData['user_lname'] == '') {
            $errorResponse['errUserlName'] = 1;
            $status = true;
        }
        if ($postData['user_phone'] == '') {
            $errorResponse['errUserphoneNumber'] = 1;
            $status = true;
        }
        if (!$validator->isValid($postData['user_email'])) {
            $errorResponse['errUseremailId'] = 1;
            $status = true;
        } else {
            // Email Duplicasy chk
            $uid = $this->_modelUsers->emailDuplicasyChk($postData['user_email'], $id, $rid);
            if ($uid > 0) {
                $errorResponse['errUserEmailDuplicasy'] = 1;
                $status = true;
            }
        }

        if ($postData['password'] != '') {

            $uppercase = preg_match('@[A-Z]@', $postData['password']);
            // $lowercase = preg_match('@[a-z]@', $postData['password']);
            // $number = preg_match('@[0-9]@', $postData['password']);

            if (!$uppercase || strlen($postData['password']) < 8) {
                // tell the user something went wrong
                $errorResponse['errpassword'] = 1;
                $status = true;
            }
            if ($postData['password'] == '' || strlen($postData['password']) < 8) {
                $errorResponse['errpassword'] = 1;
                $status = true;
            }
            if ($postData['password'] != $postData['cnfrmPassword']) {
                $errorResponse['errcnfrmPassword'] = 1;
                $status = true;
            }
        }


        $this->flashMessenger()->addMessage($errorResponse);
        return $status;
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

        $ccErrors[0] = "Unknown card type";
        $ccErrors[1] = "No card number provided";
        $ccErrors[2] = "Credit card number has invalid format";
        $ccErrors[3] = "Credit card number is invalid";
        $ccErrors[4] = "Credit card number is wrong length";

        // Establish card type
        $cardType = -1;
        for ($i = 0; $i < sizeof($cards); $i++) {

            // See if it is this card (ignoring the case of the string)
            if (strtolower($cardname) == strtolower($cards[$i]['name'])) {
                $cardType = $i;
                break;
            }
        }

        // If card type not found, report an error
        if ($cardType == -1) {
            $errornumber = 0;
            $errortext = $ccErrors[$errornumber];
            return false;
        }

        // Ensure that the user has provided a credit card number
        if (strlen($cardnumber) == 0) {
            $errornumber = 1;
            $errortext = $ccErrors[$errornumber];
            return false;
        }

        // Remove any spaces from the credit card number
        $cardNo = str_replace(' ', '', $cardnumber);

        // Check that the number is numeric and of the right sort of length.
        if (!preg_match("/^[0-9]{13,19}$/", $cardNo)) {
            $errornumber = 2;
            $errortext = $ccErrors[$errornumber];
            return false;
        }

        // Now check the modulus 10 check digit - if required
        if ($cards[$cardType]['checkdigit']) {
            $checksum = 0; // running checksum total
            $mychar = ""; // next char to process
            $j = 1; // takes value of 1 or 2
            // Process each digit one by one starting at the right
            for ($i = strlen($cardNo) - 1; $i >= 0; $i--) {

                // Extract the next digit and multiply by 1 or 2 on alternative digits.
                $calc = $cardNo{$i} * $j;

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
                $errortext = $ccErrors[$errornumber];
                return false;
            }
        }

        // The following are the card-specific checks we undertake.
        // Load an array with the valid prefixes for this card
        $prefix = explode(',', $cards[$cardType]['prefixes']);

        // Now see if any of them match what we have in the card number
        $PrefixValid = false;
        for ($i = 0; $i < sizeof($prefix); $i++) {
            $exp = '/^' . $prefix[$i] . '/';
            if (preg_match($exp, $cardNo)) {
                $PrefixValid = true;
                break;
            }
        }

        // If it isn't a valid prefix there's no point at looking at the length
        if (!$PrefixValid) {
            $errornumber = 3;
            $errortext = $ccErrors[$errornumber];
            return false;
        }

        // See if the length is valid for this card
        $LengthValid = false;
        $lengths = explode(',', $cards[$cardType]['length']);
        for ($j = 0; $j < sizeof($lengths); $j++) {
            if (strlen($cardNo) == $lengths[$j]) {
                $LengthValid = true;
                break;
            }
        }

        // See if all is OK by seeing if the length was valid.
        if (!$LengthValid) {
            $errornumber = 4;
            $errortext = $ccErrors[$errornumber];
            return false;
        }
        ;

        // The credit card is in the required format.
        return true;
    }

    function getCurlData($url) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);
        curl_setopt($curl, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US; rv:1.9.2.16) Gecko/20110319 Firefox/3.6.16");
        $curlData = curl_exec($curl);
        curl_close($curl);
        return $curlData;
    }

    //    public function updateSoundFlag($params) {
    //        $status = $this->_clientModel->updateFlagInNoitificationEmail($params);
    //        return $status;
    //    }
}

