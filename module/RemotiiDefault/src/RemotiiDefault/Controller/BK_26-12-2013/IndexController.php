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

class IndexController extends AbstractActionController {
    private $_clientModel;
    private $_modelUsers;
    public $stripeMethod;
    private $_modelSP;
    
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
     * 
     * Function preDispatch() defined to get 
     */
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
            'userData' => '',
                )
        );
    }

    /**
     * 
     * @return \Zend\View\Model\ViewModel
     */
    public function profileAction() {
    	
       	$uid = $this->getLoggedInUserId();
        $post_array = $this->getRequest()->getPost()->toArray();
        
       
		//if(empty($responseData)){
			
			if ( !empty($post_array) ) {
				//  Form validation function called
				$statusData = $this->validateManageAdminForm( $post_array, $uid, 3 );
				if ($statusData) {
					return $this->redirect()->toUrl(BASE_URL . "/client/profile");
				}
			}
			
			$flashMessenger = $this->flashMessenger();
			if ($flashMessenger->hasMessages()) {
				$responseData = $flashMessenger->getMessages();
			}
			
			
			if( !$responseData[0]['errorStatus'] ){
			
	       	 //  Add case
		        if (!empty($post_array)) {
		            try {
		                //  Save user into DB
		                $createdUid = $this->_clientModel->updateUser($post_array, $uid);
		
		                if( $post_array['card_holder'] 
		                	&& $post_array['card_number'] 
		                	&& $post_array['cvv']
		                	&& $post_array['expMonth']
		                	&& $post_array['expYear']
        					) 
		                {
                                    ////
                                    $stripeToken = $this->stripeMethod->createToken($post_array);
                                    if($stripeToken->id <> '') {
                                        $customerCreateParams = array('card' => $stripeToken->id, 
                                                'description' => $post_array['fName'] . ' ' . $post_array['lName'], 
                                                'email' => $post_array['emailId']);
                                        $customerData = $this->stripeMethod->createCustomer($customerCreateParams);
                                    }
                                    ////
                                    if( $createdUid <> '' ) {
                                        //  Save customer payment profile into DB
                                        $this->_clientModel->saveUserPaymentProfile($createdUid, $customerData->id, $paymentProfileId = null, $shippingAddrsId = null, $post_array['card_holder']);
                                    }
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
		                }
			            
		                $errorResponse[1] = (object) array('status' => 'success-msg', 'message' => 'Profile updated successfully.');
		                $this->flashMessenger()->clearCurrentMessages();
		                $this->flashMessenger()->addMessage($errorResponse);
		                return $this->redirect()->toUrl(BASE_URL . "/client/profile");
		            } catch (\Exception $e) {
		                $errorResponse[1] = (object) array('status' => 'error-msg', 'message' => 'Error while updating.');
		                $this->flashMessenger()->clearCurrentMessages();
		                $this->flashMessenger()->addMessage($errorResponse);
		            }
		        } else {
		        	//_pre($responseData);
		        	 if($responseData[1]['errorBillingDetails']){
		        	 	$pResponse = $responseData[1];
		        	 }
		        	 
		        	 if(!empty($responseData[0][1]->status)){
		        	 	$message = $responseData[0];
		        	 }
		        	 $responseData[0] = $this->_clientModel->getClientById($uid);
		        }
			} 
		//}
		
		
        return new ViewModel(array(
            'responseData' => $responseData[0],
            'errdata' => $message,
            'pResponse' => array($pResponse),
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
            
            //  CAPTCHA CODE ADDED
            $request = $this->getRequest();  
            // Get out from the $_POST array the captcha part...  
            $captcha = $request->getPost('captcha');  
            // Actually it's an array, so both the ID and the submitted word  
            // is in it with the corresponding keys  
            // So here's the ID...  
            $captchaId = $captcha['id'];  
            // And here's the user submitted word...  
            $captchaInput = $captcha['input'];  
           // _pre($captcha);
            // We are accessing the session with the corresponding namespace  
            // Try overwriting this, hah!  
            $captchaSession = new Container('Zend_Form_Captcha_'.$captchaId);
            
            // To access what's inside the session, we need the Iterator  
            // So we get one...
            $captchaIterator = $captchaSession->getIterator();
            // And here's the correct word which is on the image...
            $captchaWord = $captchaIterator['word'];
            //_pre($captchaIterator);
            //$custData = strrev($captchaWord);
            
            // Now just compare them...  
            if( $captchaInput == $captchaWord ) {
                //  OK
            } else {
                $errorResponse['errCaptcha'] = 1;
                $this->flashMessenger()->addMessage($errorResponse);
                return $this->redirect()->toUrl(BASE_URL . "/register");
            }
        }

        //  Add case
        if (!empty($post_array)) {

        	try {
                $stripeToken = $this->stripeMethod->createToken($post_array);
                if($stripeToken->id <> '') {
                    $customerCreateParams = array('card' => $stripeToken->id, 
                            'description' => $post_array['fName'] . ' ' . $post_array['lName'], 
                            'email' => $post_array['emailId']);
                    $customerData = $this->stripeMethod->createCustomer( $customerCreateParams );
                }
                //  Save user into DB
                //$createdUid = $this->_clientModel->createUser($post_array);

                if($customerData->id <> '') {
                    //  Save user into DB   //  Tmp Commented
                    try {
                		$createdUid = $this->_clientModel->createUser($post_array);
                    } catch( \Exception $e ) {
                    	$exFlag = true;
                    }
                    //  Save customer payment profile into DB
                    $this->_clientModel->saveUserPaymentProfile($createdUid, $customerData->id, $paymentProfileId = null, $shippingAddrsId = null, $post_array['card_holder']);
                }
                
                if( $exFlag <> true ) {
                	$post['identity'] = $post_array['userName'];
                	$post['credential'] = $post_array['password'];
                	$request->setPost(new \Zend\Stdlib\Parameters($post));
                	return $this->forward()->dispatch('zfcuser', array('action' => 'authenticate'));
                }
                
               	/* $errorResponse['1'] = (object) array('status' => 'success-msg', 'message' => 'Registration Successfull. Please login.');
                $this->flashMessenger()->clearCurrentMessages();
                $this->flashMessenger()->addMessage($errorResponse); */
                return $this->redirect()->toUrl( BASE_URL . "/register" );
            } catch (\Exception $e) {
                if($e instanceof \Stripe_CardError OR 
                   $e instanceof \Stripe_InvalidRequestError OR 
                   $e instanceof \Stripe_AuthenticationError OR 
                   $e instanceof \Stripe_ApiConnectionError OR 
                   $e instanceof \Stripe_Error) {
                        //  Error occured for
                        $body = $e->getJsonBody();
                        $err  = $body['error'];
                        $errorResponse['1'] = (object) array('status' => 'error-msg', 'message' => $err['message']);
                        //$this->flashMessenger()->clearCurrentMessages();
                        $this->flashMessenger()->addMessage( $errorResponse );
                        return $this->redirect()->toUrl(BASE_URL . "/register");
                }

//                $body = $e->getJsonBody();
//                $err  = $body['error'];
//                $errorResponse['1'] = (object) array('status' => 'error-msg', 'message' => $err['message']);
//                $this->flashMessenger()->clearCurrentMessages();
//                $this->flashMessenger()->addMessage($errorResponse);
//                return $this->redirect()->toUrl(BASE_URL . "/register");

            }
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
    public function myRemotiiAction() {
        $userId = $this->getLoggedInUserId();
        $remotiiId = $this->params('id', '0');

        //   Get client remotii
        $userRemotiiId = $this->_clientModel->getClientRemotii($userId);
        
        //  Get user remotii IO configuration
        $userRemotiiConfig = $this->_clientModel->getUserRemotiiIOconfig($userId, $remotiiId);
        
        $remotiiId = $userRemotiiConfig['baseRec'][0]['remotii_id'];
        
        $inboundData = $this->_clientModel->getInboundData($remotiiId);
        
        //_pre($userRemotiiConfig);
        return new ViewModel(array(
            'userRemotii' => $userRemotiiId,
            'userId' => $userId,
            'userRemotiiConfig' => $userRemotiiConfig,
        	'inboundData' => $inboundData,
        	'remotiiId' => $remotiiId,	
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
        $status = $request->getQuery('status');
        
        $loggedInUserId = $this->getLoggedInUserId();
        
        $result = $this->_clientModel->endUserHasRemotii(
        		array(  'userId' => $loggedInUserId,
        				'remotiiId' => $remotiiId ));
        
        if( $result['status'] == 'FAIL' ) {
        	die("This remotii does not exists to your account");
        }
        
        if( $macAddress && $doutSC ) {
        	if( $status == 'set' ){
        		$doutSet = $doutSC;
        		$doutClr = '0';
        	} else if( $status == 'clr' ) {
        		$doutClr = $doutSC;
        		$doutSet = '0';
        	} else if( $status == 'tgl' ) {
        		$doutSet = $doutClr = $doutSC;
        	}
        	
        	$params = array(
        			'mac_address'=> $macAddress,
        			'remotii_id'=> $remotiiId, 
        			'dout_set'=> $doutSet,
        			'dout_clr'=> $doutClr
        	);
        	
        	$result = $this->_clientModel->changeOBRemotiiPin( $params );
        }
        echo '1';
        die('');
    }
    
    /**
     * 
     * @return \Zend\View\Model\ViewModel
     */
    public function remotiiSetupAction() {
        
    	$loggedInUserId = $this->getLoggedInUserId();
        $loggedInUserEmail = $this->getLoggedInUserEmail();
        
        $request = $this->getRequest();

        $modifyRemotiiId = $this->params('id','0');
        
        if(!$modifyRemotiiId)
        {
        	$modifyRemotiiId = $request->getPost('modifyRemotiiId');
        }
        	
        if( $modifyRemotiiId )
        {
        	$result = $this->_clientModel->endUserHasRemotii(
        			array(  'userId' => $loggedInUserId,
        					'remotiiId' => $modifyRemotiiId ));
        	
        	if( $result['status'] == 'FAIL' ) {
        		die("This remotii does not exists to your account");
        	}
        	
        	$remotiiMacData = $this->_clientModel->getRemotiiNameMacId($loggedInUserId, $modifyRemotiiId);
        }
        
        if ($request->isXmlHttpRequest()) {
            if ($request->getPost('xDefaultRemotiiDataSubmission')) {
                $defaultRSData = array();
                $defaultRSDataInputConf = array();
                $defaultRSDataOutputConf = array();

                $macAddress = $request->getPost('macAddress');
                $remotiiName = $request->getPost('remotiiName');
                $configSetting = $request->getPost('configSetting');

                //  get the remotii Id using remotii Mac Addrs
                $rmData = $this->_clientModel->getRemotiiId( $macAddress );
                $remotiiId = $rmData['remotiiId'];
                $spId = $rmData['spId'];
                $settingType = DEFAULTS;
                $configSetting = 'custom';

                if( $configSetting == 'custom' ) { 
                	
                	if( $modifyRemotiiId )
                	{
	                	$userRemotiiId = $this->_clientModel->getIdFromUserRemotii($modifyRemotiiId);
	                	if( $userRemotiiId ){
	                		if(!empty($remotiiName)){
	                			$this->_clientModel->updateRemotiiName( $userRemotiiId, $remotiiName );
	                		}
	                	}
                	}else{
	                		$userRemotiiId = $this->_clientModel->saveUserRemotiiData($remotiiName, $remotiiId, $loggedInUserId, $settingType);
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
                            $defaultRSDataInputConf[$i]['notification_trigger'] = $request->getPost('ri_ntfn_trigger_' . $i) ? 1 : 0;

                            $defaultRSDataInputConf[$i]['notification_email'] = $request->getPost('ri_ntfn_mail_' . $i);
                        }
                    }

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
                            
                            if( !$modifyRemotiiId && $macAddress ) {
                            	$doutSet = "";
                            	$doutClr = "";
                            	if(!$ism) {
	                            	if( $pin == 1 ) {
	                            		if( $ois == 1 ) {
	                            			$doutSet = '1';
	                            			$doutClr = '0';
	                            		} else {
	                            			$doutSet = '0';
	                            			$doutClr = '1';
	                            		}
	                            	} else if( $pin == 2) {
	                            		if( $ois == 1 ) {
	                            			$doutSet = '2';
	                            			$doutClr = '0';
	                            		} else {
	                            			$doutSet = '0';
	                            			$doutClr = '2';
	                            		}
	                            	} else if( $pin == 3 ) {
	                            		if( $ois == 1 ) {
	                            			$doutSet = '4';
	                            			$doutClr = '0';
	                            		} else {
	                            			$doutSet = '0';
	                            			$doutClr = '4';
	                            		}
	                            	}
	                            	
                            	} else {
                            		$doutSet = $doutClr = 1;
                            	}
                            	
                            	$params = array(
                            			'mac_address'=> $macAddress,
                            			'remotii_id'=> $remotiiId,
                            			'dout_set'=> $doutSet,
                            			'dout_clr'=> $doutClr
                            	);
                            	 
                            	if( $doutSet != "" && $doutClr !="" ) {
                            		$result = $this->_clientModel->changeOBRemotiiPin( $params );
                            	}
                            }
                        }
                    }

                    $this->_clientModel->saveInputConfig($userRemotiiId, $defaultRSDataInputConf, $loggedInUserId);
                    $this->_clientModel->saveOutputConfig($userRemotiiId, $defaultRSDataOutputConf, $loggedInUserId);
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
        ));
        $view->setTerminal(true);
        return $view;
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
        if($loggedInUserId) {
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

                if($configSetting == 'default') {
                    //  Save data into user remotii
                    //$userRemotiiId = $this->_clientModel->saveUserRemotiiData($remotiiName, $remotiiId, $loggedInUserId, $settingType);

                    //  save SP config to user config
                    //$this->_clientModel->saveDefaultIOconfig($spId , $userRemotiiId, $loggedInUserId, $loggedInUserEmail);
                }
                //if($configSetting == 'custom') {

                if(true) {
                    //  $settingType = CUSTOM;
                    $userRemotiiId = $this->_clientModel->getIdFromUserRemotii($remotiiId);
                    
                    if( !$userRemotiiId ){
                    	//  Save data into user remotii
                    	$userRemotiiId = $this->_clientModel->saveUserRemotiiData($remotiiName, $remotiiId, $loggedInUserId, $settingType);
                    }else{
                    	if(!empty($remotiiName))
                    		$this->_clientModel->updateRemotiiName($userRemotiiId, $remotiiName);
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
                            $defaultRSDataInputConf[$i]['notification_trigger'] = $request->getPost('ri_ntfn_trigger_' . $i) ? 1 : 0;

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
        $data = $this->_clientModel->remotiiConfigchkValidateChk($macId);

        // your code here ...
        $view = new ViewModel(array('data' => $data));
        $view->setTerminal(true);
        return $view;
    }
    
    public function serviceProviderInfoAction()
    {
    	$remotiiId = $this->getRequest()->getQuery('remotii_id');
    	$spInfo = $this->_modelUsers->getServiceProviderInfoByRemotiiId($remotiiId);
    	if(!empty($spInfo)){
    		$spInfo = $spInfo[0];
    	}
    	
    	$view = new ViewModel( array('spInfo' => $spInfo) );
    	$view->setTerminal(true);
    	return $view;
    }
    
    
    /**
     *  Send client query to service provider email address
     *  
     *  @author emp24
     *  @return void
     */
    public function sendEmailToServiceProviderAction()
    {
    	
    	$request = $this->getRequest();
    	if( $request->isXmlHttpRequest() )
    	{
    		$spid = $request->getQuery('spid');
    		$message = $request->getPost('message');
    		
    		$spInfo = $this->_modelUsers->getServiceProviderInfo($spid);
    		
    		$uid = $this->getLoggedInUserId();
    		$uinfo = $this->_clientModel->getClientById($uid);
    		
    		if(!empty($uinfo)){
    		    $uinfo = (array) $uinfo[0];
    		}
    		
    		if(!empty($spInfo)){
    			$spInfo = $spInfo[0];
    		}

    		$toEmail = $spInfo->contact_email;
    		
    		if(!empty($toEmail))
    		{
	    		$viewTemplate = 'tpl/email/sp_contact';
	    		$values = array('spInfo' => $spInfo,'uinfo'=>$uinfo, 'message'=> $message);
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
    public function setDefaultRemotiiAction()
    {
    	$request = $this->getRequest();
    	if( $request->isXmlHttpRequest() )
    	{
    		$loggedInUserId = $this->getLoggedInUserId();
    		$remotiiId = $request->getPost('remotiiId');
    		
    		$result = $this->_clientModel->endUserHasRemotii(
    					array(  'userId' => $loggedInUserId,
    							'remotiiId' => $remotiiId ));
    		
    		if( $result['status'] == 'FAIL' ) {
    			die("This remotii does not exists to your account");
    		}
    		
    		$result = $this->_clientModel->assignDefaultRemotii($loggedInUserId, $remotiiId);
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

        $errorResponse['0'] = (object) array('username' => $postData['userName'], 'display_name' => $postData['displayName'], 
            'fname' => $postData['fName'], 'lname' => $postData['lName'], 'phone' => $postData['phoneNumber'], 
            'email' => $postData['emailId'], 'street' => $postData['street'], 'city' => $postData['city'], 
            'state' => $postData['state'], 'country' => $postData['country'], 'zip_code' => $postData['zip_code'], 
            'password' => $postData['password'], 'cnfrmPassword' => $postData['cnfrmPassword'], 'cardType' => $postData['cardType'],
        	'card_holder' => $postData['card_holder'], 'card_number' => $postData['card_number'], 'expMonth' => $postData['expMonth'],
        	'expYear' => $postData['expYear'],'cvv' => $postData['cvv'] 		
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

        if(!$id || $postData['password'] <> '') {
            $uppercase = preg_match('@[A-Z]@', $postData['password']);
            //$lowercase = preg_match('@[a-z]@', $postData['password']);
            //$number    = preg_match('@[0-9]@', $postData['password']);
			//$specialCharacters = preg_match('/[!@#$%^&*-]/', $postData['password']);

            if(!$uppercase || strlen($postData['password']) < 8) {
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
    
    public function getSpDefaultConfigAction()
    {
    	$remotiiId = $this->params('id');
    	$spid = $this->_modelSP->getSPIDByRemotiiMacAddress($remotiiId);
    	
    	$SPInputConfig = $this->_modelSP->getSPInputConfig($spid);
    		
    	if( !empty($SPInputConfig) ) {
    		foreach( $SPInputConfig as $k=>$v ) {
    			$SPInputConfigTmp[$v['pin_number']] = $v;
    		}
    		$SPInputConfig = $SPInputConfigTmp;
    	}
    		
    	$SPOutputConfig = $this->_modelSP->getSPOutputConfig($spid);
    		
    	if( !empty($SPOutputConfig) ) {
    		foreach( $SPOutputConfig as $k=>$v ) {
    			$SPOutputConfigTmp[$v['pin_number']] = $v;
    		}
    		$SPOutputConfig = $SPOutputConfigTmp;
    	}
    	
    	$spIOConfig['spInputConfig'] = $SPInputConfig;//$this->_modelSP->getSPInputConfig($spid);
    	$spIOConfig['spOutputConfig'] = $SPOutputConfig;//$this->_modelSP->getSPOutputConfig($spid);
    	
    	echo json_encode($spIOConfig);
    	die();
    }
    
    public function getClientDefaultConfigAction()
    {
    	$macID = $this->params('id');
    	$urid = $this->_clientModel->getUserRemotiiIDByMacID($macID);
    	//die($urid);
    	
    	$clientInputConfig = $this->_clientModel->getClientInputConfig($urid);
    	
    	if( !empty($clientInputConfig) ) {
    		foreach( $clientInputConfig as $k=>$v ) {
    			$clientInputConfigTmp[$v['pin_number']] = $v;
    		}
    		$clientInputConfig = $clientInputConfigTmp;
    	}
    	
    	$clientOutputConfig = $this->_clientModel->getClientOutputConfig($urid);
    	
    	if( !empty($clientOutputConfig) ) {
    		foreach( $clientOutputConfig as $k=>$v ) {
    			$clientOutputConfigTmp[$v['pin_number']] = $v;
    		}
    		$clientOutputConfig = $clientOutputConfigTmp;
    	}
    	
    	$urIOConfig['spInputConfig'] = $clientInputConfig;//$this->_clientModel->getClientInputConfig($urid);
    	$urIOConfig['spOutputConfig'] = $clientOutputConfig;//$this->_clientModel->getClientOutputConfig($urid);
    	 
    	echo json_encode($urIOConfig);
    	die();
    }
    
    
    public function remotiiNameExistsAction()
    {
    	$remotiiName = $this->getRequest()->getQuery('remotiiName');
    	$rne = $this->_clientModel->remotiiNameExists( $remotiiName );
    	if( true == $rne  )
    	{
    		echo '1';
    	}else{
    		echo '0';
    	}
    	die('');
    }
    
    public function ajxGetInboundDataAction()
    {
    	$request = $this->getRequest();
    	
    	$remotiiId = $request->getQuery('remotii_id');
    	$lastMessageId = $request->getQuery('last_message_id');
    	$lessThenMessageId = $request->getQuery('ltmsgid') ? true : false;
    	$limit = $request->getQuery('limit');
    	$limit = $limit ? $limit : '100';
    	
    	$userId = $this->getLoggedInUserId();
    	
    	//  Get user remotii IO configuration
    	$userRemotiiConfig = $this->_clientModel->getUserRemotiiIOconfig( $userId, $remotiiId );
    	//_pre($userRemotiiConfig);
    	$remotiiId = $userRemotiiConfig['baseRec'][0]['remotii_id'];
    	
    	$inboundData = $this->_clientModel->getInboundData( $remotiiId, $lastMessageId, $lessThenMessageId, $limit );
    	//_pre($inboundData);
    	//_pre($userRemotiiConfig);
    	$view = new ViewModel(array(
    			'userRemotiiConfig' => $userRemotiiConfig,
    			'inboundData' => $inboundData,
    			'lastMessageId' => $lastMessageId,
    	));
		
    	$view->setTerminal(true);
    	return $view;
    } 
    
    public function ajxGetIoControlAction()
    {
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
    protected function getLoggedInUserEmail() {
    	if ($this->zfcUserAuthentication()->hasIdentity()) {
    		return $this->zfcUserAuthentication()->getIdentity()->getEmail();
    	} else {
    		return false;
    	}
    }
    
    public function aboutRemotiiAction()
    {
    	return new ViewModel();
    }
    
    public function aboutAction()
    {
    	return new ViewModel();
    }
    
    public function careersAction()
    {
    	return new ViewModel();
    }
    
    public function featuresAction()
    {
    	return new ViewModel();
    }
    
    public function contactAction()
    {
		$request = $this->getRequest();
		if( $request->isPost() ) {
			$yourName = $request->getPost('yourName');
			$yourEmail = $request->getPost('yourEmail');
			$message = $request->getPost('message');
			
			$viewTemplate = 'tpl/email/contact_us_mail';
			$values = array (
					'yourName' => $yourName,
					'yourEmail' => $yourEmail,
					'message' => $message,
			);
			$subject = 'Remotii visitor message';
			$mailService = $this->getServiceLocator ()->get ( 'goaliomailservice_message' );
			$message = $mailService->createTextMessage ( ADMIN_EMAIL, 'shivam@finoit.com', $subject, $viewTemplate, $values );
			try{
				$mailService->send ( $message );
				$flag = true;
			} catch(\Exception $e) {
				$msg = $e->getMessage();
				$flag = false;
			}
			if( $flag ) {
				$this->flashMessenger()->addMessage(array('status'=>'success','message'=>'Message sent successfully'));
				return $this->redirect()->toUrl(BASE_URL.'/contact');
			} else {
				$this->flashMessenger()->addMessage(array('status'=>'fail','message'=>$msg));
			}
		}    	
		
    	return new ViewModel(array('flashMessages'=>$this->flashMessenger()->getCurrentMessages(), 'contact'=>$request->getPost()->toArray()));
    }
    
    public function whereToBuyAction()
    {
    	return new ViewModel();
    }
    
    public function wiringDiagramsAction() {
    	return new ViewModel();
    }
    
    public function remotiiUsesAction() {
    	return new ViewModel();
    }
    
    public function termsAndConditionsAction() {
    	return new ViewModel();
    }
}
