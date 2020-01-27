<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2013 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */
namespace RemotiiServiceProvider\Controller;
use Zend\Mvc\Controller\AbstractActionController;
use RemotiiModels\Model\Feed as MFeed;
use RemotiiModels\Model\ManageUsers as mUsers;
use RemotiiModels\Model\Client as MClient;
use RemotiiModels\Model\ServiceProvider;
use Zend\View\Model\ViewModel;
use Zend\Validator\EmailAddress as emailIdValidation;
// Payment module used
use CimPayment\Payment\CimMethod as cimMethod;
use StripePayment\Payment\StripeMethod as stripeMethod;
class IndexController extends AbstractActionController {
    private $_modelUsers;
    private $_modelSP;
    private $_clientModel;
    public $stripeMethod;
    /**
     * Listeners defined attachDefaultListeners() for
     * calling predispatch and postdispatch
     */
    protected function attachDefaultListeners() {
        parent::attachDefaultListeners();
        $events = $this->getEventManager();
        $this->events->attach('dispatch', array($this, 'preDispatch'), 100);
        //$this->events->attach('dispatch', array($this, 'postDispatch'), -100);
    }
    /**
     * Function preDispatch() defined to get 
     * 
     */
    public function preDispatch() {
        $db = $this->getServiceLocator()->get('db');
        $this->_modelUsers = new mUsers($db);
        $this->_modelSP = new ServiceProvider($db);
        $this->_clientModel = new MClient($db);
        // Payment object initialised
        $this->cimMethod = new cimMethod ();
        // Stripe Payment object initialised
        $this->stripeMethod = new stripeMethod ();
    }
    public function indexAction() {
        $loggedInUserId = $this->getLoggedInUserId();
        $spid = $this->_modelSP->getServiceProviderCompId($loggedInUserId);
        $summery = $this->_modelSP->getServiceProviderSummary($spid);
        $delinquentClients = $this->_modelUsers->getSPDelinquentClients($spid);
        $service_provider_credit = $this->_modelUsers->getSPCredits($spid);
        return new ViewModel(array(
            'summery' => $summery,
            'delinquentClients' => $delinquentClients,
            'sp_credit' => $service_provider_credit
                )
        );
    }
    public function listEndUsersAction() {
        $loggedInUserId = $this->getLoggedInUserId();
        $spid = $this->_modelSP->getServiceProviderCompId($loggedInUserId);
        $spUsers = $this->_modelUsers->getSPClients($spid);
        $flashMessenger = $this->flashMessenger();
        if ($flashMessenger->hasMessages()) {
            $response = $flashMessenger->getMessages();
        }
        return new ViewModel(array(
            'response' => $response,
            'spUsers' => $spUsers
                )
        );
    }
    /**
     * 
     * @return \Zend\View\Model\ViewModel
     */
    public function remotiisAction() {
        //  catch the set messages
        $flashMessenger = $this->flashMessenger();
        if ($flashMessenger->hasMessages()) {
            $response = $flashMessenger->getMessages();
        }
        $loggedInUserId = $this->getLoggedInUserId();
        //  Model function call to get the Service Provider Admins Company ID (Service Provider ID)
        $id = $this->_modelSP->getServiceProviderCompId($loggedInUserId);
        $searchRemotii = $this->getRequest()->getQuery('search');
        $post_array = $this->getRequest()->getPost()->toArray();
        if (isset($post_array['filterRemotii'])) {
            if ($post_array['remotiiId'] <> '') {
                return $this->redirect()->toUrl(BASE_URL . "/sp/remotiis?search=" . $post_array['remotiiId']);
            }
        }
        if ($searchRemotii <> '') {
            $spRemotiis = $this->_modelUsers->searchSPRemotiis($id, $searchRemotii);
        } else {
            $spRemotiis = $this->_modelUsers->getSPRemotiis($id);
        }
        return new ViewModel(array(
            'spRemotii' => $spRemotiis,
            'searchRemotii' => $searchRemotii,
            'response' => $response[0]
                )
        );
    }
    /**
     *
     * @return \Zend\View\Model\ViewModel
     */
    public function listofflineremotiiAction() {
        $loggedInUserId = $this->getLoggedInUserId();
        //  Model function call to get the Service Provider Admins Company ID (Service Provider ID)
        $id = $this->_modelSP->getServiceProviderCompId($loggedInUserId);
        $spRemotiis = $this->_modelUsers->getOfflineSPRemotiis($id);
        return new ViewModel(array(
            'spRemotii' => $spRemotiis,
            'response' => $response[0]
                )
        );
    }
    /**
     * 
     * @return \Zend\View\Model\ViewModel
     */
    public function remotichkAction() {
        $remVal = $_REQUEST['postdata'];
        $data = $this->_modelUsers->remotiValidateChk($remVal);
        // your code here ...
        $view = new ViewModel(array('data' => $data));
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
        //  Model function call to get the Service Provider Admins Company ID (Service Provider ID)
        $spId = $this->_modelSP->getServiceProviderCompId($loggedInUserId);
        $spInputConfig = $this->_modelSP->getSPInputConfig($spId);  
        $spOutputConfig = $this->_modelSP->getSPOutputConfig($spId);
        $remVal = $_REQUEST['postdata'];
        $count = count($remVal);
        for ($i = 0; $i < $count; $i++) {
            $remId = $this->_modelUsers->saveRemotiiMacForRSP($remVal[$i], $spId, $loggedInUserId);
            $userRemotiiId = $this->_modelUsers->saveUserRemotiiData($remVal[$i], $remId, $loggedInUserId);
            $this->_clientModel->saveInputConfig($userRemotiiId, $spInputConfig, $loggedInUserId,null,'sp');
            $this->_clientModel->saveOutputConfig($userRemotiiId, $spOutputConfig, $loggedInUserId,null,'sp');
        }
        $response = array('status' => 'success-msg', 'message' => 'Record added successfully.');
        $this->flashMessenger()->addMessage($response);
        // your code here ...
        $view = new ViewModel();
        $view->setTerminal(true);
        return $view;
    }
    /**
     * 
     * @return type
     */
    public function deleteremotiiAction() {
        $id = $this->params()->fromRoute('id', 0);
        if (!$id) {
            return $this->redirect()->toUrl(BASE_URL . "/sp/remotiis");
        }
        $SharePersonInfo = $this->_modelUsers->getSharedInfo($id);
        $chainedEvents = $this->_modelUsers->getRemotiiChainedEvent($id);
        $remotiiData = $this->_modelUsers->getRemotiiData($id);
        //  Model function to delete the remotii and its associated data
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
//
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
                                'macId' => $data->mac_address,
                                'message' => 'Mail not sent',
                                'email' => $mail,
                                'exception' => $msg
                                    )), 3, '/var/www/remotii/public/cronNotification.fn.log');
                        }
                    }
                }
            }
            $response = array('status' => 'success-msg', 'message' => 'Record deleted successfully.');
            $this->flashMessenger()->addMessage($response);
            return $this->redirect()->toUrl(BASE_URL . "/sp/remotiis");
        }
    }
    public function activeDeactiveRemotiiAction($params) {
        $request = $this->getRequest();
        $loggedInUserId = $this->getLoggedInUserId();
        $spid = $this->_modelSP->getServiceProviderCompId($loggedInUserId);
        $uid = $this->params('id');
        if ($request->isPost()) {
            $shutOffAllRemotiis = $request->getPost('shutOffAllRemotiis');
//			$allRemotiiIdsOfSPsUser = $this->_modelSP->getUserRemotiis(
//					array(	'user_id'=>$uid,
//							'service_provider_id'=>$spid,
//							'fields' => 'ur.remotii_id',
//							'fetch'=> 'column'
//					));
            $remotiiIdsToTurnOn = $request->getPost('remotiis');
            ///_pre($remotiiIdsToTurnOn);
            if (!empty($remotiiIdsToTurnOn)) {
                //$this->_modelSP->shutOnOffRemotiis( $allRemotiiIdsOfSPsUser, ACTIVE  );
                $result = $this->_modelSP->shutOnOffRemotiis($remotiiIdsToTurnOn, SUSPENDED);
                /* if($result['status'] == 'FAIL')
                  {
                  $message[] = 'Choose atleast a single remotii';
                  } */
            } else {
                $message[] = 'Choose atleast a single remotii';
            }
        }
        $spUserDetailsArr = $this->_modelUsers->getUserById($uid);
        $spUserDetails = $spUserDetailsArr[0];
        $userRemotiis = $this->_modelSP->getUserRemotiis(array('user_id' => $uid, 'service_provider_id' => $spid));
        return new ViewModel(array(
            'userRemotiis' => $userRemotiis,
            'spUserDetails' => $spUserDetails
                )
        );
    }
    public function defaultRemotiiSetupAction() {
        $loggedInUserId = $this->getLoggedInUserId();
        $spid = $this->_modelUsers->getServiceProviderIdFromUser($loggedInUserId);
        $request = $this->getRequest();
        if ($loggedInUserId) {
            $SPInputConfig = $this->_modelSP->getSPInputConfig($spid);
            if (!empty($SPInputConfig)) {
                foreach ($SPInputConfig as $k => $v) {
                    $SPInputConfigTmp[$v['pin_number']] = $v;
                }
                $SPInputConfig = $SPInputConfigTmp;
            }
            $SPOutputConfig = $this->_modelSP->getSPOutputConfig($spid);
            if (!empty($SPOutputConfig)) {
                foreach ($SPOutputConfig as $k => $v) {
                    $SPOutputConfigTmp[$v['pin_number']] = $v;
                }
                $SPOutputConfig = $SPOutputConfigTmp;
            }
        }
        if ($request->isXmlHttpRequest()) {
            if ($request->getPost('xDefaultRemotiiDataSubmission')) {
                $defaultRSData = array();
                $defaultRSDataInputConf = array();
                $defaultRSDataOutputConf = array();
                for ($i = 1; $i <= 4; $i++) {
                    $ri_enabled_ = $request->getPost('ri_enabled_' . $i);
                    if (!empty($ri_enabled_)) {
                        $defaultRSDataInputConf[$i]['name'] = $request->getPost('ri_name_' . $i);
                        $defaultRSDataInputConf[$i]['active_label_text'] = $request->getPost('ri_asl_' . $i);
                        $defaultRSDataInputConf[$i]['active_label_color'] = $request->getPost('ri_active_color_' . $i);
                        $defaultRSDataInputConf[$i]['inactive_label_text'] = $request->getPost('ri_iasl_' . $i);
                        $defaultRSDataInputConf[$i]['inactive_label_color'] = $request->getPost('ri_inactive_color_' . $i);
                        $defaultRSDataInputConf[$i]['pin_number'] = $request->getPost('ri_pin_number_' . $i);
                        $defaultRSDataInputConf[$i]['enable_notification'] = $request->getPost('ri_enable_ntfn_' . $i) ? 1 : 0;
                        $defaultRSDataInputConf[$i]['notification_trigger'] = $request->getPost('ri_ntfn_trigger_' . $i);
                        $defaultRSDataInputConf[$i]['notification_sound'] = $request->getPost('ri_ntfn_sound_' . $i);
                    }
                }
                for ($i = 1; $i <= 3; $i++) {
                    $ro_enabled_ = $request->getPost('ro_enabled_' . $i);
                    if (!empty($ro_enabled_)) {
                        $defaultRSDataOutputConf[$i]['name'] = $request->getPost('ro_name_' . $i);
                        $defaultRSDataOutputConf[$i]['active_label_text'] = $request->getPost('ro_asl_' . $i);
                        $defaultRSDataOutputConf[$i]['active_label_color'] = $request->getPost('ro_active_color_' . $i);
                        $defaultRSDataOutputConf[$i]['inactive_label_text'] = $request->getPost('ro_iasl_' . $i);
                        $defaultRSDataOutputConf[$i]['inactive_label_color'] = $request->getPost('ro_inactive_color_' . $i);
                        $defaultRSDataOutputConf[$i]['pin_number'] = $request->getPost('ro_pin_number_' . $i);
                        $defaultRSDataOutputConf[$i]['is_output_momentary'] = $request->getPost('ro_momentary_' . $i) ? 1 : 0;
                        $defaultRSDataOutputConf[$i]['output_initial_state'] = $request->getPost('ro_initialState_' . $i) ? 1 : 0;
                        $defaultRSDataOutputConf[$i]['pulse_time'] = $request->getPost('ro_pulse_time_' . $i);
                        //$defaultRSDataOutputConf[$i]['enable_notification'] = $request->getPost('ro_enable_ntfn_'.$i) ? 1 : 0;
                        //$defaultRSDataOutputConf[$i]['notification_trigger'] = $request->getPost('ro_ntfn_trigger_'.$i) ? 1 : 0;
                    }
                }
                //_pr($defaultRSDataInputConf);
                $this->_modelSP->saveSPInputConfig($defaultRSDataInputConf, $spid);
                $this->_modelSP->saveSPOutputConfig($defaultRSDataOutputConf, $spid);
            }
            die('done');
        }
//_pre($SPInputConfig);
        $view = new ViewModel(array(
            'SPInputConfig' => $SPInputConfig,
            'SPOutputConfig' => $SPOutputConfig
        ));
        $view->setTerminal(true);
        return $view;
    }
    protected function getLoggedInUserId() {
        if ($this->zfcUserAuthentication()->hasIdentity()) {
            return $this->zfcUserAuthentication()->getIdentity()->getId();
        } else {
            return false;
        }
    }
    protected function getLoggedInUserRole() {
        if ($this->zfcUserAuthentication()->hasIdentity()) {
            return $this->zfcUserAuthentication()->getIdentity()->getUserRoleId();
        } else {
            return false;
        }
    }
    public function paymentsAction() {
        $loggedInUserId = $this->getLoggedInUserId();
        if ($this->getRequest()->isPost()) {
            $fromDate = strtotime($this->getRequest()->getPost('fromDate'));
            $toDate = strtotime($this->getRequest()->getPost('toDate'));
            $payments = $this->_modelSP->getSPPaymentsInfo(array('toDate' => $toDate, 'fromDate' => $fromDate), $loggedInUserId);
        } else {
            $fromDate = strtotime('-1 month');
            $toDate = strtotime(date('m/d/Y'));
            $payments = $this->_modelSP->getSPPaymentsInfo(array('toDate' => $toDate, 'fromDate' => $fromDate), $loggedInUserId);
        }
        return new ViewModel(array(
            'fromDate' => $fromDate ? date('m/d/Y', $fromDate) : "",
            'toDate' => $toDate ? date('m/d/Y', $toDate) : "",
            'payments' => $payments,
        ));
    }
    /**
     * 
     * @return \Zend\View\Model\ViewModel
     */
    public function previousBillingCycleAction() {
        $spPreviousBillingCycle = $this->_modelSP->getPreviousBillingCycle();
        return new ViewModel(array(
            'spPreviousBillingCycle' => $spPreviousBillingCycle
                )
        );
    }
    /**
     * Show service provider admin profile
     * 
     */
    public function profileAction() {
        $flashMessenger = $this->flashMessenger();
        if ($flashMessenger->hasMessages()) {
            $responseData = $flashMessenger->getMessages();
        }
        $post_array = $this->getRequest()->getPost()->toArray();
        $uid = $this->getLoggedInUserId();
        if (isset($post_array['submit_user'])) {
            if ($uid) {
                if (!empty($post_array)) {
                    try {
                        $loggedInUserId = $this->getLoggedInUserId();
                        $this->_modelUsers->updateSPUser($post_array, $loggedInUserId, $uid);
                        $errorResponse['1'] = (object) array('status' => 'success-msg', 'message' => 'User updated successfully.');
                        $this->flashMessenger()->clearCurrentMessages();
                        $this->flashMessenger()->addMessage($errorResponse);
                        return $this->redirect()->toUrl(BASE_URL . "/sp/profile");
                    } catch (\Exception $e) {
                        $errorResponse['1'] = (object) array('status' => 'error-msg', 'message' => 'Error in update operation.');
                        $this->flashMessenger()->clearCurrentMessages();
                        $this->flashMessenger()->addMessage($errorResponse);
                    }
                }
                $userData = $this->_modelUsers->getUserById($id);
            }
            $spUserDetails = $userData;
        } else {
            if ($uid) {
                $spUserDetails = $this->_modelUsers->getUserById($uid);
                if ($spUserDetails) {
                    $spUserDetails = $spUserDetails[0];
                }
                //_pre($spUserDetails);
            }
        }
        $isPost = $this->getRequest()->isPost();
        return new ViewModel(array(
            'uid' => $this->getRequest()->getQuery('uid'),
            'isPost' => $isPost,
            'responseData' => $responseData[0],
            'spUserDetails' => $spUserDetails,
        ));
    }
    public function billingDetailsAction() {
        $uid = $this->getLoggedInUserId();
        $id = $this->_modelUsers->getServiceProviderIdFromUser($uid);
        // catch the set messages
        $flashMessenger = $this->flashMessenger();
        if ($flashMessenger->hasMessages()) {
            $flashMessages = $flashMessenger->getMessages();
        }
        // Function call to get the service provider information from DB
        $userData = $this->_modelUsers->getServiceProviderInfo($id);
        $post_array = $this->getRequest()->getPost()->toArray();
        if (isset($post_array ['submit_user']) && $post_array ['submit_user'] == 'Submit') {
            // Update service provider info
            $this->updateServiceProviderInfo($post_array, $id, $userData);
            if (isset($post_array ['billAccount']) && $post_array ['billAccount'] == 'Bill Account') {
                // Capture service provider payment and save payment stat
                $this->captureUserPayment($post_array, $id);
            }
        }
        if (empty($flashMessages[1]['stripeErrors'])) {
            $sprpinfo = $this->_modelUsers->getServiceProviderReceivningPaymentInfo($id);
        } else {
            $sprpinfo = $flashMessages[0];
        }
        // _pre($spUserDetails);
        return new ViewModel(array(
            'uid' => $this->getLoggedInUserId(),
            'responseData' => $flashMessages [0],
            'data' => $userData,
            'errors' => $errors,
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
            return $this->redirect()->toUrl(BASE_URL . "/sp/billing-details");
        }
        $loggedInUserId = $this->getLoggedInUserId();
        if ($postData['shbillingDetails'] == '1') {
            if ($postData ['card_number'] != '' && $postData ['expMonth'] != '' && $postData ['expYear'] != '') {
                try {
                    $stripeToken = $this->stripeMethod->createToken($postData);
                } catch (\Exception $e) {
                    $message = $e->getMessage();
                    $this->flashMessenger()->addMessage(array('stripeErrors' => array('status' => 'error', 'message' => $message)));
                    return $this->redirect()->toUrl(BASE_URL . "/sp/billing-details");
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
                        return $this->redirect()->toUrl(BASE_URL . "/sp/billing-details");
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
                return $this->redirect()->toUrl(BASE_URL . "/sp/billing-details");
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
        $this->_modelUsers->updateServiceProviderBillingDetails($postData, $loggedInUserId, $id);
        $errorResponse ['1'] = (object) array(
                    'status' => 'success-msg',
                    'message' => 'Service provider info updated successfully.'
        );
        $this->flashMessenger()->clearCurrentMessages();
        $this->flashMessenger()->addMessage($errorResponse);
        return $this->redirect()->toUrl(BASE_URL . "/sp/billing-details");
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
        return $this->redirect()->toUrl(BASE_URL . "/sp/billing-details");
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
        if ($postData ['contracted_price'] == '') {
            $errorResponse ['err_contracted_price'] = 1;
            $status = true;
        }
        if ($postData ['end_user_price'] == '') {
            $errorResponse ['err_end_user_price'] = 1;
            $status = true;
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
        $this->flashMessenger()->addMessage($errorResponse);
        return $status;
    }
    public function allRemotiiAction() {
        $userId = $this->getLoggedInUserId();
        $userRemotiiId = $this->_clientModel->getSpRemotii($userId);
        $time = time();
        $userRemotiiConf = $this->_clientModel->getSpRemotiiIOconf($userId);
        return new ViewModel(array(
            'userRemotii' => $userRemotiiId,
            'userId' => $userId,
            'userRemotiiConf' => $userRemotiiConf,
        ));
    }
    public function viewRemotiiAction() {
        $userId = $this->getLoggedInUserId();
        $remotiiId = $this->params('id', '0');
        if ($remotiiId) {
            $userRemotiiIdData = $this->_clientModel->getRemotiiNameMacId($userId, $remotiiId);
            $mac_address = $userRemotiiIdData[0]['mac_address'];
            $this->_clientModel->insertCheckInReq($mac_address);
        }
        $spid = $this->_modelUsers->getServiceProviderIdFromUser($userId);
        $userRemotiiId = $this->_clientModel->getSpRemotii($userId);
        $userRemotiiConfig = $this->_clientModel->getUserRemotiiIOconfig($userId, $remotiiId);
        $remotii_data = $this->_modelUsers->getRemotiiOffsetDayLightSaving($remotiiId);
        $offset = $remotii_data[0]->offset;
        $day_light_saving = $remotii_data[0]->day_light_saving;
        if (empty($userRemotiiConfig['baseRec'][0]['remotii_id'])) {
            return $this->redirect()->toUrl(BASE_URL . '/sp/all-remotii');
        }
        $access_level = $userRemotiiConfig['baseRec'][0]['access_level'];
        $remotiiId = $userRemotiiConfig['baseRec'][0]['remotii_id'];
//        $share_person_userId = $userRemotiiConfig['baseRec'][0]['user_id'];
//        $shared_user_id = $userRemotiiConfig['baseRec'][0]['shared_user_id'];
//        $share_person_name = $this->_clientModel->getUserNameOfSharedRemotii($share_person_userId);
        $inboundData = $this->_clientModel->getInboundData($remotiiId);
        $view = new ViewModel(array(
            'userRemotii' => $userRemotiiId,
            'userId' => $userId,
            'userRemotiiConfig' => $userRemotiiConfig,
            'inboundData' => $inboundData,
            'remotiiId' => $remotiiId,
            'access_level' => $access_level,
            'time' => $time
        ));
        //$view->setTerminal(true);
        return $view;
    }
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
        $userRemotiiConfig = $this->_clientModel->getSpRemotiiIOconfig($userId, $remotiiId);
        $permission = ($userRemotiiConfig['baseRec'][0]['user_id'] == $userId) ? 1 : (int) $userRemotiiConfig['baseRec'][0]['access_level'];
        if ($permission != 1 && $permission != 2) {
            die('0');
        }
        $status = $request->getQuery('status');
        $loggedInUserId = $this->getLoggedInUserId();
        $result = $this->_clientModel->endUserHasRemotii(
                array('userId' => $loggedInUserId,
                    'remotiiId' => $remotiiId));
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
            }if ($status == 'tgl') {
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
    public function remotiiSetupAction() {
        $loggedInUserId = $this->getLoggedInUserId();
        $id = $this->_modelSP->getServiceProviderCompId($loggedInUserId);
        $remotiiData = $this->_clientModel->getRemotiiName($loggedInUserId);
        $numbersList = $this->_clientModel->getNumbersList();
        $request = $this->getRequest();
        $modifyRemotiiId = $this->params('id', '0');
//  Added on 17/12/2013
        $remotiiPaymentStatusVal = $this->_clientModel->getUserRemotiiLastPaymentStatus($modifyRemotiiId);
        $remotiiPaymentStatus = $remotiiPaymentStatusVal->payment_status;
        $remotiiAlwEndBill = $remotiiPaymentStatusVal->allow_end_user_billing;
        $offsetArray = $this->_clientModel->getoffset();
        if (!$modifyRemotiiId) {
            $modifyRemotiiId = $request->getPost('modifyRemotiiId');
        }
        if ($modifyRemotiiId) {
            $paymentStripeStatusCustom = 1;
            $result = $this->_modelSP->spHasRemotii(
                    array('userId' => $id,
                        'remotiiId' => $modifyRemotiiId));
            if ($result['status'] == 'FAIL') {
                die("This remotii does not exists to your account");
            }
            $remotiiMacData = $this->_modelSP->getRemotiiNameMacId($id, $modifyRemotiiId);
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
                $configSetting = $request->getPost('configSetting');
//  get the remotii Id using remotii Mac Addrs
                $rmData = $this->_clientModel->getRemotiiId($macAddress);
                $remotiiId = $rmData['remotiiId'];
                $spId = $rmData['spId'];
                $settingType = DEFAULTS;
                $configSetting = 'custom';
//                if ($paymentStripeStatusCustom == 1 || $alwEndBll != 1) {
                if (TRUE) {
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
                                if (!empty($remotiiName)) {
                                    $this->_clientModel->updateRemotiiName($userRemotiiId, $remotiiName, $selGmt, $day_saving, $offset_time);
                                }
                            }
                        } else {
                            $userRemotiiId = $this->_clientModel->saveUserRemotiiData($remotiiName, $remotiiId, $loggedInUserId, $settingType, $paymentStripeStatusCustom, $selGmt, $day_saving, $offset_time);
                        }
                        if ($modifyRemotiiId) {
                            $i = 0;
                            $InputConfig = $this->_clientModel->getInputConfig($loggedInUserId, $remotiiId);
                            foreach ($InputConfig as $ic) {
                                $inputArray[$i] = $ic['pin_number'];
                                $i++;
                            }//_pr($inputArray);
                            $OutputConfig = $this->_clientModel->getOutputConfig($loggedInUserId, $remotiiId);
                            foreach ($OutputConfig as $oc) {
                                $outputArray[$i] = $oc[pin_number];
                                $i++;
                            }
                        }
                        //  Save data into config tables
                        for ($i = 1; $i <= 4; $i++) {
                            $ri_enabled_ = $request->getPost('ri_enabled_' . $i);
                            if (!empty($ri_enabled_)) {
                                $defaultRSDataInputConf[$i]['name'] = $request->getPost('ri_name_' . $i);
                                $defaultRSDataInputConf[$i]['pin_number'] = $request->getPost('ri_pin_number_' . $i);
                                $defaultRSDataInputConf[$i]['active_label_text'] = $request->getPost('ri_asl_' . $i);
                                $defaultRSDataInputConf[$i]['active_label_color'] = $request->getPost('ri_active_color_' . $i);
                                $defaultRSDataInputConf[$i]['inactive_label_text'] = $request->getPost('ri_iasl_' . $i);
                                $defaultRSDataInputConf[$i]['inactive_label_color'] = $request->getPost('ri_inactive_color_' . $i);
                                $defaultRSDataInputConf[$i]['enable_notification'] = $request->getPost('ri_enable_ntfn_' . $i) ? 1 : 0;
                                $defaultRSDataInputConf[$i]['notification_trigger'] = $request->getPost('ri_ntfn_trigger_' . $i);
                                $defaultRSDataInputConf[$i]['email'] = $_POST['email'][$i];
                                $defaultRSDataInputConf[$i]['play_sound'] = $request->getPost('ri_ntfn_sound_' . $i);
                            }
                        }$i = 0;
                        foreach ($defaultRSDataInputConf as $defaultInput) {
                            $dInput[$i] = $defaultInput['pin_number'];
                            $i++;
                        }//_pr($defaultRSDataInputConf);
                        $inputresult = array_diff($inputArray, $dInput);
                        for ($i = 1; $i <= 3; $i++) {
                            $ro_enabled_ = $request->getPost('ro_enabled_' . $i);
                            if (!empty($ro_enabled_)) {
                                $defaultRSDataOutputConf[$i]['name'] = $request->getPost('ro_name_' . $i);
                                $pin = $defaultRSDataOutputConf[$i]['pin_number'] = $request->getPost('ro_pin_number_' . $i);
                                $defaultRSDataOutputConf[$i]['active_label_text'] = $request->getPost('ro_asl_' . $i);
                                $defaultRSDataOutputConf[$i]['active_label_color'] = $request->getPost('ro_active_color_' . $i);
                                $defaultRSDataOutputConf[$i]['inactive_label_text'] = $request->getPost('ro_iasl_' . $i);
                                $defaultRSDataOutputConf[$i]['inactive_label_color'] = $request->getPost('ro_inactive_color_' . $i);
                                $ism = $defaultRSDataOutputConf[$i]['is_output_momentary'] = $request->getPost('ro_momentary_' . $i) ? 1 : 0;
                                $ois = $defaultRSDataOutputConf[$i]['output_initial_state'] = $request->getPost('ro_initialState_' . $i) ? 1 : 0;
                                $defaultRSDataOutputConf[$i]['enable_notification'] = $request->getPost('ro_enable_ntfn_' . $i) ? 1 : 0;
                                $defaultRSDataOutputConf[$i]['notification_trigger'] = $request->getPost('ro_ntfn_trigger_' . $i) ? 1 : 0;
                                $defaultRSDataOutputConf[$i]['notification_email'] = $request->getPost('ro_ntfn_mail_' . $i);
                                $defaultRSDataOutputConf[$i]['ro_pulse_time'] = $request->getPost('ro_pulse_time_' . $i);
                                $pulse_time = round($defaultRSDataOutputConf[$i]['ro_pulse_time'] * 1000);
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
                                        'tx_type' => $tx_type,
                                    );
                                    $result = $this->_clientModel->changeOBRemotiiPin($params);
                                }
//}
//}
                            }
                        }$i = 0;
                        foreach ($defaultRSDataOutputConf as $defaultOutput) {
                            $dOutput[$i] = $defaultOutput['pin_number'];
                            $i++;
                        }
                        $outputresult = array_diff($outputArray, $dOutput);
                        $count = $this->_clientModel->deleteEventData($outputresult, $remotiiId, $inputresult);
                        $this->_clientModel->saveInputConfig($userRemotiiId, $defaultRSDataInputConf, $loggedInUserId);
                        $this->_clientModel->saveOutputConfig($userRemotiiId, $defaultRSDataOutputConf, $loggedInUserId);
                    }
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
                $view = new ViewModel(array('data' => $data));
                $view->setTerminal(true);
                return $view;
            }
        }
        $view = new ViewModel(array('data' => $data));
        $view->setTerminal(true);
        return $view;
    }
    public function copyRemotii($params) {
        $loggedInUserId = $this->getLoggedInUserId();
        $remotiiData = $this->_clientModel->getRemotiiName($loggedInUserId);
        $message = "Success";
        return $data = array(
            'remotiiData' => $remotiiData,
            'message' => $message,
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
                $settingType = DEFAULTS;
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
                        if (!empty($remotiiName)) {
                            $this->_clientModel->updateRemotiiName($userRemotiiId, $remotiiName);
                        }
                    }
//  Save data into config tables
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
            'remotiiId' => $remotiiId,
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
        $data = $this->_clientModel->remotiiConfigchkValidateChk($macId);
// your code here ...
        $view = new ViewModel(array('data' => $data));
        $view->setTerminal(true);
        return $view;
    }
    public function remotiiNameExistsAction() {
        $remotiiName = $this->getRequest()->getQuery('remotiiName');
        $rne = $this->_clientModel->remotiiNameExists($remotiiName);
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
        $oldLastInputStatus = $request->getQuery('oldDin');
        $InboundData = array_reverse($inboundData, true);
        // $XorResult = (int) $recentLastInputStatus ^ (int) $oldLastInputStatus;
        $playSound = 0;
        $AlarmStatus = FALSE;
        if ($ajxCount !== '0') {
            foreach ($InboundData as $recentLastInputStatus) {
                $XorResult = (int) $recentLastInputStatus->din ^ (int) $oldLastInputStatus;
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
                $oldLastInputStatus = $recentLastInputStatus->din;
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
            'recentLastInputStatus' => $recentStatus
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
            'userRemotiiConfig' => $userRemotiiConfig,
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
            'remotii_id' => $remotiiId,
        ));
        return $view;
    }
    public function checkspdefaultconfigAction() {
        $postData = $_REQUEST;
        $settingStatus = $this->_clientModel->chkSPconfigSetting($postData['macAddrs']);
// your code here ...
        $view = new ViewModel(array('data' => $settingStatus));
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
                $str.="<a href='" . BASE_URL . "/sp/view-remotii/" . $remotii_id . "'>" . $userRemotiiConfig['remotii_name'] . "</a>";
            }
            if (!empty($userRemotiiConfig['shared_user_id'])) {
                $str.="<a href='" . BASE_URL . "/sp/view-remotii/" . $remotii_id . "'>" . $userRemotiiConfig['remotii_name'] . " " . "(shared)" . "</a>";
            }
            $str.='</p>';
            $i = 0;
            $rmLastStatus = $userRemotiiConfig['remotii_last_input_status'];
            $str.="<div class='remotii-wrap'";
            if ($userRemotiiConfig['view_status'] == 0) {
                $str.="style='display:none'";
            }
            $str.="><span> &nbsp;&nbsp;Input Controls</span>";
            $str.="<div class='input-color-wrap'>";
            foreach ($userRemotiiConfig['inConfig'] as $inConfig) {
//                _pr($inConfig);
                $i = $inConfig['pin_number'];
                if ($i == 1) {
                    if ($rmLastStatus & 1) {
                        $tooltip = 'Pin: ' . $inConfig['pin_number'] . '&#10;' . 'State: ' . $inConfig['active_label_text'] . ' ( Energized )';
                        $str.= '<div class="input-color">';
                        $str.='<span title="' . $tooltip . '" class="ic_out_color' . $i . '" style="background: ' . $inConfig['active_label_color'] . ';"></span>
                                    ' . $inConfig['name'] . '
                                </div>';
                    } else {
                        $tooltip = 'Pin: ' . $inConfig['pin_number'] . '&#10;' . 'State: ' . $inConfig['inactive_label_text'] . ' ( Not Energized )';
                        $str.= '<div class="input-color">';
                        $str.='<span title="' . $tooltip . '" class="ic_out_color' . $i . '" style="background: ' . $inConfig['inactive_label_color'] . ';"></span>
                                    ' . $inConfig['name'] . '
                                </div>';
                    }
                }
                if ($i == 2) {
                    if ($rmLastStatus & 2) {
                        $tooltip = 'Pin: ' . $inConfig['pin_number'] . '&#10;' . 'State: ' . $inConfig['active_label_text'] . ' ( Energized )';
                        $str.= '<div class="input-color">';
                        $str.='<span title="' . $tooltip . '" class="ic_out_color' . $i . '" style="background: ' . $inConfig['active_label_color'] . ';"></span>
                                    ' . $inConfig['name'] . '
                                </div>';
                    } else {
                        $tooltip = 'Pin: ' . $inConfig['pin_number'] . '&#10;' . 'State: ' . $inConfig['inactive_label_text'] . ' ( Not Energized )';
                        $str.= '<div class="input-color">';
                        $str.='<span title="' . $tooltip . '" class="ic_out_color' . $i . '" style="background: ' . $inConfig['inactive_label_color'] . ';"></span>
                                    ' . $inConfig['name'] . '
                                </div>';
                    }
                }
                if ($i == 3) {
                    if ($rmLastStatus & 4) {
                        $tooltip = 'Pin: ' . $inConfig['pin_number'] . '&#10;' . 'State: ' . $inConfig['active_label_text'] . ' ( Energized )';
                        $str.='<div class="input-color">';
                        $str.='<span title="' . $tooltip . '" class="ic_out_color' . $i . '" style="background: ' . $inConfig['active_label_color'] . ';"></span>
                                    ' . $inConfig['name'] . '
                                </div>';
                    } else {
                        $tooltip = 'Pin: ' . $inConfig['pin_number'] . '&#10;' . 'State: ' . $inConfig['inactive_label_text'] . ' ( Not Energized )';
                        $str.= '<div class="input-color">';
                        $str.='<span title="' . $tooltip . '" class="ic_out_color' . $i . '" style="background: ' . $inConfig['inactive_label_color'] . ';"></span>
                                    ' . $inConfig['name'] . '
                                </div>';
                    }
                }
                if ($i == 4) {
                    if ($rmLastStatus & 8) {
                        $tooltip = 'Pin: ' . $inConfig['pin_number'] . '&#10;' . 'State: ' . $inConfig['active_label_text'] . ' ( Energized )';
                        $str.='<div class="input-color">';
                        $str.='<span title="' . $tooltip . '" class="ic_out_color' . $i . '" style="background: ' . $inConfig['active_label_color'] . ';"></span>
                                    ' . $inConfig['name'] . '
                                </div>';
                    } else {
                        $tooltip = 'Pin: ' . $inConfig['pin_number'] . '&#10;' . 'State: ' . $inConfig['inactive_label_text'] . ' ( Not Energized )';
                        $str.='<div class="input-color">';
                        $str.='<span title="' . $tooltip . '" class="ic_out_color' . $i . '" style="background: ' . $inConfig['inactive_label_color'] . ';"></span>
                                    ' . $inConfig['name'] . '
                                </div>';
                    }
                }
            }
            $str.="</div>";
            $str.="<div>";
            $str.="<span> &nbsp;&nbsp; Output Controls </span>";
            $str.="<div class='input-color-wrap brdr'>";
            $j = 0;
            $rmLastStatus = $userRemotiiConfig['remotii_last_output_status'];
            foreach ($userRemotiiConfig['outConfig'] as $outConfig) {
//                _pr($outConfig);
                $i = $outConfig['pin_number'];
                if ($i == 1) {
                    if ($rmLastStatus & 1) {
                        $tooltip = 'Pin: ' . $outConfig['pin_number'] . '&#10;' . 'State: ' . $outConfig['active_label_text'] . ' ( Energized )';
                        $str.= '<div class="input-color">';
                        $str.='<span title="' . $tooltip . '" class="ic_out_color' . $i . '" style="background: ' . $outConfig['active_label_color'] . ';"></span>
                                    ' . $outConfig['name'] . '
                                </div>';
                    } else {
                        $tooltip = 'Pin: ' . $outConfig['pin_number'] . '&#10;' . 'State: ' . $outConfig['inactive_label_text'] . ' ( Not Energized )';
                        $str.= '<div class="input-color">';
                        $str.='<span title="' . $tooltip . '" class="ic_out_color' . $i . '" style="background: ' . $outConfig['inactive_label_color'] . ';"></span>
                                    ' . $outConfig['name'] . '
                                </div>';
                    }
                }
                if ($i == 2) {
                    if ($rmLastStatus & 2) {
                        $tooltip = 'Pin: ' . $outConfig['pin_number'] . '&#10;' . 'State: ' . $outConfig['active_label_text'] . ' ( Energized )';
                        $str.= '<div class="input-color">';
                        $str.='<span title="' . $tooltip . '" class="ic_out_color' . $i . '" style="background: ' . $outConfig['active_label_color'] . ';"></span>
                                    ' . $outConfig['name'] . '
                                </div>';
                    } else {
                        $tooltip = 'Pin: ' . $outConfig['pin_number'] . '&#10;' . 'State: ' . $outConfig['inactive_label_text'] . ' ( Not Energized )';
                        $str.= '<div class="input-color">';
                        $str.='<span title="' . $tooltip . '" class="ic_out_color' . $i . '" style="background: ' . $outConfig['inactive_label_color'] . ';"></span>
                                    ' . $outConfig['name'] . '
                                </div>';
                    }
                }
                if ($i == 3) {
                    if ($rmLastStatus & 4) {
                        $tooltip = 'Pin: ' . $outConfig['pin_number'] . '&#10;' . 'State: ' . $outConfig['active_label_text'] . ' ( Energized )';
                        $str.='<div class="input-color">';
                        $str.='<span title="' . $tooltip . '" class="ic_out_color' . $i . '" style="background: ' . $outConfig['active_label_color'] . ';"></span>
                                    ' . $outConfig['name'] . '
                                </div>';
                    } else {
                        $tooltip = 'Pin: ' . $outConfig['pin_number'] . '&#10;' . 'State: ' . $outConfig['inactive_label_text'] . ' ( Not Energized )';
                        $str.= '<div class="input-color">';
                        $str.='<span title="' . $tooltip . '" class="ic_out_color' . $i . '" style="background: ' . $outConfig['inactive_label_color'] . ';"></span>
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
            $str.=" </div>
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
            'count' => count($returnArray),
        );
    }
    /**
     * 
     * @return \Zend\View\Model\ViewModel
     */
    public function remotiiuserpinfoAction() {
        $macId = $_REQUEST['postdata'];
        $data = $this->_clientModel->remotiiUserPaymentInfo($macId);
// your code here ...
        $view = new ViewModel(array('data' => $data));
        $view->setTerminal(true);
        return $view;
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
	    $result = $this->$action($params);

        } else {
            $result = array('status' => 'FAIL', 'result' => 'Action not exists');
        }
        return $response->setContent(\Zend\Json\Json::encode($result));
    }
    public function addNoteToSP($params) {
        $spRemotiis = $this->_modelUsers->addNoteFromSPToRemotti(array('ur_id' => $params['ur_id'], 'note' => $params['note']));
        return $data = array(
            'status' => 'success',
        );
    }
    public function setDefaultRemotiiAction() {
        $request = $this->getRequest();
        if ($request->isXmlHttpRequest()) {
            $loggedInUserId = $this->getLoggedInUserId();
            $id = $this->_modelSP->getServiceProviderCompId($loggedInUserId);
            $remotiiId = $request->getPost('remotiiId');
            $result = $this->_modelSP->spHasRemotii(
                    array('userId' => $id,
                        'remotiiId' => $remotiiId));
            if ($result['status'] == 'FAIL') {
                die("This remotii does not exists to your account");
            }
            $userRemotiiIdData = $this->_modelSP->getRemotiiNameMacId($id, $remotiiId);
            $userRemotiiId = $userRemotiiIdData[0]['user_remotii_id'];
            $result = $this->_clientModel->assignDefaultRemotii($loggedInUserId, $remotiiId, $userRemotiiId);
            echo json_encode($result);
            die();
        }
        die('Sorry, Invalid Request....  :) ');
        return;
    }
    public function getSpDefaultConfigAction() {
        $remotiiId = $this->params('id');
        $spid = $this->_modelSP->getSPIDByRemotiiMacAddress($remotiiId);
        $SPInputConfig = $this->_modelSP->getSPInputConfig($spid);
        if (!empty($SPInputConfig)) {
            foreach ($SPInputConfig as $k => $v) {
                $SPInputConfigTmp[$v['pin_number']] = $v;
            }
            $SPInputConfig = $SPInputConfigTmp;
        }
        $SPOutputConfig = $this->_modelSP->getSPOutputConfig($spid);
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
        if (!empty($clientOutputConfig)) {
            foreach ($clientOutputConfig as $k => $v) {
                $clientOutputConfigTmp[$v['pin_number']] = $v;
            }
            $clientOutputConfig = $clientOutputConfigTmp;
        }
        $urIOConfig['spInputConfig'] = $clientInputConfig; //$this->_clientModel->getClientInputConfig($urid);
        $urIOConfig['spOutputConfig'] = $clientOutputConfig;
//$this->_clientModel->getClientOutputConfig($urid);
        echo json_encode($urIOConfig);
        die();
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
    public function redirectRemotiiAction() {
        $userId = $this->getLoggedInUserId();
        $defaultRemotiiId = $this->_clientModel->getIsDefaultSpRemotii($userId);
        $remotiiId = $defaultRemotiiId['remotii_id'];
        if (!empty($defaultRemotiiId)) {
            return $this->redirect()->toUrl(BASE_URL . '/sp/view-remotii/' . $remotiiId);
            // return $this->redirect()->toUrl(BASE_URL . '/client/all-remotii');
        } else {
            return $this->redirect()->toUrl(BASE_URL . '/sp/all-remotii');
        }
    }
}
