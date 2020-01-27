<?php

namespace RemotiiModels\Model;

use Zend\Db\Adapter\Adapter;
use Zend\Db\TableGateway\AbstractTableGateway;
use Zend\Crypt\Password\Bcrypt;

class Client extends AbstractTableGateway {
	public $adapter;
	public $platform;
	
	/**
	 * Service Provider Id from service_provider table
	 */
	public $companyId;
	/**
	 *
	 * @param \Zend\Db\Adapter\Adapter $db        	
	 */
	public function __construct(Adapter $db) {
		$this->adapter = $db;
		$this->platform = $this->adapter->getPlatform ();
	}
	
	/**
	 *
	 * @param type $macId        	
	 * @return type
	 */
	public function getRemotiiId($macId) {
		$customArr = array ();
		$macId = trim ( $macId );
		$query = "SELECT remotii_id, service_provider_id FROM remotii WHERE mac_address = " . $this->platform->quoteValue ( $macId );
		
		$dataPro = $this->adapter->query ( $query );
		$results = $dataPro->execute ()->getResource ()->fetchAll ( \PDO::FETCH_OBJ );
		$customArr ['remotiiId'] = $results [0]->remotii_id;
		$customArr ['spId'] = $results [0]->service_provider_id;
		
		return $customArr;
	}
	
	/**
	 *
	 * @param type $remotiiName        	
	 * @param type $remotiiId        	
	 * @param type $loggedInUserId        	
	 * @param type $settingType        	
	 * @return boolean
	 */
	public function saveUserRemotiiData($remotiiName, $remotiiId, $loggedInUserId, $settingType) {
		$time = time ();
		$queryAdd = "INSERT INTO user_remotii SET 
            user_id = " . $loggedInUserId . ", 
            remotii_id = " . $remotiiId . ", 
            remotii_name = " . $this->platform->quoteValue ( $remotiiName ) . ", 
            is_default_cofig 	 = " . $settingType . ",
           	is_default = ( select IF ( count( * ) = 0, 1, 0 ) from user_remotii t2 where t2.user_id = '$loggedInUserId' ),
            updated_by = " . $loggedInUserId . ", 
            updated_on = " . $time;
		
		$query = $this->adapter->query ( $queryAdd );
		$query->execute ();
		$lastInsertedId = $this->adapter->getDriver ()->getLastGeneratedValue ();
		return $lastInsertedId;
	}
	public function updateRemotiiName($userRemotiiId, $remotiiName) {
		$time = time ();
		$queryAdd = "UPDATE user_remotii SET
            remotii_name = " . $this->platform->quoteValue ( $remotiiName ) . ",
            updated_on = " . $time . "
    		where user_remotii_id = '$userRemotiiId'";
		
		$query = $this->adapter->query ( $queryAdd );
		$query->execute ();
		return true;
	}
	public function saveInputConfig($userRemotiiId, $spInputConfig, $lid) {
		$query = "
    			delete from user_remotii_input_config where user_remotii_id = '$userRemotiiId';
    			insert into 
    				user_remotii_input_config(
    					user_remotii_id, 
    					pin_number,
    					name,
		    			active_label_text,
		    			active_label_color,
		    			inactive_label_text,
		    			inactive_label_color,
		    			is_enabled,
		    			enable_notification,
		    			notification_trigger,
		    			notification_email,
		    			updated_by,
		    			updated_on
    				) VALUES ";
		
		foreach ( $spInputConfig as $spic ) {
			
			if (empty ( $spic ))
				continue;
			
			$subQryArr [] = "( 
    					$userRemotiiId, 
    					'" . $spic ['pin_number'] . "',
    					'" . $spic ['name'] . "',
    					'" . $spic ['active_label_text'] . "',
    					'" . $spic ['active_label_color'] . "',
    					'" . $spic ['inactive_label_text'] . "',
    					'" . $spic ['inactive_label_color'] . "',
    					1,
    					'" . $spic ['enable_notification'] . "',
    					'" . $spic ['notification_trigger'] . "',
    					'" . $spic ['notification_email'] . "',
    					$lid,		
    					'" . time () . "'
    					)";
		}
		
		if (! empty ( $subQryArr )) {
			
			$subQry = implode ( ', ', $subQryArr );
			$query = $query . $subQry;
			
			$stmt = $this->adapter->query ( $query );
			$stmt->execute ();
			$lastInsertedId = $this->adapter->getDriver ()->getLastGeneratedValue ();
			return $lastInsertedId;
		}
	}
	public function saveOutputConfig($userRemotiiId, $spOutputConfig, $lid) {
		$query = "
    			delete from user_remotii_output_config where user_remotii_id = '$userRemotiiId';
    			insert into 
    				user_remotii_output_config(
    					user_remotii_id,
    					pin_number,
    					name,
		    			active_label_text,
		    			active_label_color,
		    			inactive_label_text,
		    			inactive_label_color,
		    			is_enabled,
    					is_output_momentary,
    					output_initial_state,
		    			enable_notification,
		    			notification_trigger,
                                        notification_email,
		    			updated_by,
		    			updated_on
    				) VALUES ";
		
		foreach ( $spOutputConfig as $spoc ) {
			
			if (empty ( $spoc ))
				continue;
			
			$subQryArr [] = "( 
    					$userRemotiiId, 
    					'" . $spoc ['pin_number'] . "',
    					'" . $spoc ['name'] . "',
    					'" . $spoc ['active_label_text'] . "',
    					'" . $spoc ['active_label_color'] . "',
    					'" . $spoc ['inactive_label_text'] . "',
    					'" . $spoc ['inactive_label_color'] . "',
    					1,
    					'" . $spoc ['is_output_momentary'] . "',
    					'" . $spoc ['output_initial_state'] . "',
    					'" . $spoc ['enable_notification'] . "',
    					'" . $spoc ['notification_trigger'] . "',
    					'" . $spoc ['notification_email'] . "',
    					$lid,		
    					'" . time () . "'
    					)";
		}
		
		if (! empty ( $subQryArr )) {
			$subQry = implode ( ', ', $subQryArr );
			$query = $query . $subQry;
			
			$stmt = $this->adapter->query ( $query );
			$stmt->execute ();
			$lastInsertedId = $this->adapter->getDriver ()->getLastGeneratedValue ();
			return $lastInsertedId;
		}
	}
	
	/**
	 *
	 * @param type $spId        	
	 * @param type $userRemotiiId        	
	 * @return type
	 */
	public function saveDefaultIOconfig($spId, $userRemotiiId, $lid, $loggedInUserEmail) {
		$querySPIC = "SELECT * FROM service_provider_input_config WHERE service_provider_id = '" . $spId . "'";
		$data = $this->adapter->query ( $querySPIC );
		$resultsSPIC = $data->execute ()->getResource ()->fetchAll ( \PDO::FETCH_OBJ );
		
		$query = "insert into 
    				user_remotii_input_config(
    					user_remotii_id, 
    					name,
		    			active_label_text,
		    			active_label_color,
		    			inactive_label_text,
		    			inactive_label_color,
		    			is_enabled,
		    			enable_notification,
		    			notification_trigger,
		    			notification_email,
		    			updated_by,
		    			updated_on
    				) VALUES ";
		
		foreach ( $resultsSPIC as $spic ) {
			
			if (empty ( $spic ))
				continue;
			
			$subQryArr [] = "( 
    					$userRemotiiId, 
    					'" . $spic->name . "',
    					'" . $spic->active_label_text . "',
    					'" . $spic->active_label_color . "',
    					'" . $spic->inactive_label_text . "',
    					'" . $spic->inactive_label_color . "',
    					1,
    					'" . $spic->enable_notification . "',
    					'" . $spic->notification_trigger . "',
    					'" . $loggedInUserEmail . "',
    					$lid,		
    					'" . time () . "'
                                )";
		}
		
		if (! empty ( $subQryArr )) {
			
			$subQry = implode ( ', ', $subQryArr );
			$query = $query . $subQry;
			
			$stmt = $this->adapter->query ( $query );
			$stmt->execute ();
			$lastInsertedId = $this->adapter->getDriver ()->getLastGeneratedValue ();
		}
		
		$querySPOC = "SELECT * FROM service_provider_output_config WHERE service_provider_id = '" . $spId . "'";
		$dataSPOC = $this->adapter->query ( $querySPOC );
		$resultsSPOC = $dataSPOC->execute ()->getResource ()->fetchAll ( \PDO::FETCH_OBJ );
		$query = "insert into 
    				user_remotii_output_config(
    					user_remotii_id, 
    					name,
		    			active_label_text,
		    			active_label_color,
		    			inactive_label_text,
		    			inactive_label_color,
		    			is_enabled,
    					is_output_momentary,
    					output_initial_state,
		    			enable_notification,
		    			notification_trigger,
                                        notification_email,
		    			updated_by,
		    			updated_on
    				) VALUES ";
		
		foreach ( $resultsSPOC as $spoc ) {
			
			if (empty ( $spoc ))
				continue;
			
			$subQryArr2 [] = "(
    					$userRemotiiId, 
    					'" . $spoc->name . "',
    					'" . $spoc->active_label_text . "',
    					'" . $spoc->active_label_color . "',
    					'" . $spoc->inactive_label_text . "',
    					'" . $spoc->inactive_label_color . "',
    					1,
    					'" . $spoc->is_output_momentary . "',
    					'" . $spoc->output_initial_state . "',
    					'" . $spoc->enable_notification . "',
    					'" . $spoc->notification_trigger . "',
    					'" . $loggedInUserEmail . "',
    					$lid,		
    					'" . time () . "'
    				)";
		}
		
		if (! empty ( $subQryArr2 )) {
			$subQry = implode ( ', ', $subQryArr2 );
			print $query = $query . $subQry;
			
			$stmt = $this->adapter->query ( $query );
			$stmt->execute ();
			$lastInsertedId = $this->adapter->getDriver ()->getLastGeneratedValue ();
		}
		return;
	}
	
	/**
	 *
	 * @param type $macId        	
	 * @return type
	 */
	public function remotiiConfigchkValidateChk($macId) {
		$qry = "SELECT remotii_id FROM remotii WHERE mac_address = '" . trim ( $macId ) . "'";
		$dataPro = $this->adapter->query ( $qry );
		$results = $dataPro->execute ()->getResource ()->fetchAll ( \PDO::FETCH_OBJ );
		if ($results [0]->remotii_id > 0) {
			$qry = "SELECT ur.remotii_id FROM 
		                remotii r INNER JOIN user_remotii ur ON (r.remotii_id = ur.remotii_id) 
		                WHERE r.mac_address = '" . trim ( $macId ) . "'";
			$dataPro = $this->adapter->query ( $qry );
			$results = $dataPro->execute ()->getResource ()->fetchAll ( \PDO::FETCH_OBJ );
			if ($results [0]->remotii_id > 0) {
				return 1;
			} else {
				return 0;
			}
		} else {
			return 2;
		}
	}
	
	/**
	 *
	 * @param type $uid        	
	 * @return type
	 */
	public function getClientRemotii($uid) {
		$query = "SELECT *, r.remotii_status FROM user_remotii ur LEFT JOIN remotii r ON(ur.remotii_id = r.remotii_id) 
        			WHERE ur.user_id = '" . $uid . "' ORDER BY ur.user_remotii_id DESC";
		$queryExec = $this->adapter->query ( $query );
		$results = $queryExec->execute ()->getResource ()->fetchAll ( \PDO::FETCH_OBJ );
		return $results;
	}
	
	/**
	 *
	 * @param type $spid        	
	 * @return type
	 */
	public function getInputConfig($uid, $remotiiId) {
		$q = "SELECT * FROM 
	            user_remotii ur INNER JOIN user_remotii_input_config ic ON (ur.user_remotii_id = ic.user_remotii_id) 
	            WHERE ur.user_id = '" . $uid . "' AND ur.remotii_id = '" . $remotiiId . "'";
		$stmt = $this->adapter->query ( $q );
		$results = $stmt->execute ()->getResource ()->fetchAll ( \PDO::FETCH_ASSOC );
		return $results;
	}
	
	/**
	 *
	 * @param type $spid        	
	 * @return type
	 */
	public function getOutputConfig($uid, $remotiiId) {
		$q = "SELECT * FROM 
	            user_remotii ur INNER JOIN user_remotii_output_config oc ON (ur.user_remotii_id = oc.user_remotii_id) 
	            WHERE ur.user_id = '" . $uid . "' AND ur.remotii_id = '" . $remotiiId . "'";
		$stmt = $this->adapter->query ( $q );
		$results = $stmt->execute ()->getResource ()->fetchAll ( \PDO::FETCH_ASSOC );
		return $results;
	}
	
	/**
	 *
	 * @param type $userId        	
	 * @param type $remotiiId        	
	 * @return type
	 */
	public function getRemotiiNameMacId($userId, $remotiiId) {
		$q = "SELECT * FROM 
	            remotii r INNER JOIN user_remotii ur ON (r.remotii_id = ur.remotii_id) 
	            WHERE ur.user_id = '" . $userId . "' AND ur.remotii_id = '" . $remotiiId . "'";
		$result = $this->adapter->query ( $q );
		$data = $result->execute ()->getResource ()->fetchAll ( \PDO::FETCH_ASSOC );
		return $data;
	}
	
	/**
	 *
	 * @param type $remotiiId        	
	 * @return type
	 */
	public function getIdFromUserRemotii($remotiiId) {
		$q = "SELECT user_remotii_id FROM 
            user_remotii 
            WHERE remotii_id = '" . $remotiiId . "'";
		$result = $this->adapter->query ( $q );
		$data = $result->execute ()->getResource ()->fetchAll ( \PDO::FETCH_ASSOC );
		return $data [0] ['user_remotii_id'];
	}
	public function getUserRemotiiIDByMacID($macID) {
		$q = "SELECT user_remotii_id FROM
	            user_remotii ur INNER JOIN remotii r ON(ur.remotii_id = r.remotii_id)
	            WHERE r.mac_address = '" . $macID . "'";
		$result = $this->adapter->query ( $q );
		$data = $result->execute ()->getResource ()->fetchColumn ();
		return $data;
	}
	
	/**
	 *
	 * @return boolean
	 */
	public function createUser($data) {
		$time = time ();
		$role = 3; // 1 indicate admin user role
		           
		// Encode the password using
		$bcrypt = new Bcrypt ();
		$bcrypt->setCost ();
		$password = $bcrypt->create ( $data ['password'] );
		
		$queryAdd = "INSERT INTO user SET 
            username = " . $this->platform->quoteValue ( $data ['userName'] ) . ", 
            fname = " . $this->platform->quoteValue ( $data ['fName'] ) . ", 
            lname = " . $this->platform->quoteValue ( $data ['lName'] ) . ", 
            phone = " . $this->platform->quoteValue ( $data ['phoneNumber'] ) . ", 
            email = " . $this->platform->quoteValue ( $data ['emailId'] ) . ", 
            password = " . $this->platform->quoteValue ( $password ) . ", 
            user_role_id = " . $this->platform->quoteValue ( $role ) . ", 
            street = " . $this->platform->quoteValue ( $data ['street'] ) . ", 
            city = " . $this->platform->quoteValue ( $data ['city'] ) . ", 
            state = " . $this->platform->quoteValue ( $data ['state'] ) . ", 
            zip_code = " . $this->platform->quoteValue ( $data ['zip_code'] ) . ", 
            created_on = " . $time . "";
		
		$queryExeA = $this->adapter->query ( $queryAdd );
		$queryExeA->execute ();
		$lastInsertedId = $this->adapter->getDriver ()->getLastGeneratedValue ();
		return $lastInsertedId;
	}
	
	/**
	 *
	 * @return boolean
	 */
	public function updateUser($data, $id) {
		if (! $id)
			return;
		
		if (! empty ( $data ['password'] )) {
			// Encode the password using
			$bcrypt = new Bcrypt ();
			$bcrypt->setCost ();
			$password = $bcrypt->create ( $data ['password'] );
			$passwordSubQry = "password = " . $this->platform->quoteValue ( $password ) . ",";
		}
		
		$queryAdd = "UPDATE user SET
            username = " . $this->platform->quoteValue ( $data ['userName'] ) . ",
            fname = " . $this->platform->quoteValue ( $data ['fName'] ) . ",
            lname = " . $this->platform->quoteValue ( $data ['lName'] ) . ",
            phone = " . $this->platform->quoteValue ( $data ['phoneNumber'] ) . ",
            email = " . $this->platform->quoteValue ( $data ['emailId'] ) . ",
            $passwordSubQry
            street = " . $this->platform->quoteValue ( $data ['street'] ) . ",
            city = " . $this->platform->quoteValue ( $data ['city'] ) . ",
            state = " . $this->platform->quoteValue ( $data ['state'] ) . ",
            zip_code = " . $this->platform->quoteValue ( $data ['zip_code'] ) . " where user_id= " . $this->platform->quoteValue ( $id ) . "";
		
		$queryExeA = $this->adapter->query ( $queryAdd );
		$queryExeA->execute ();
		// $lastInsertedId = $this->adapter->getDriver()->getLastGeneratedValue();
		return $id;
	}
	
	/**
	 *
	 * @param type $createdUid        	
	 * @param type $profileId        	
	 * @param type $paymentProfileId        	
	 * @param type $shippingAddrsId        	
	 * @return type
	 */
	public function saveUserPaymentProfile($createdUid, $profileId, $paymentProfileId, $shippingAddrsId, $card_holder) {
		$queryAdd = "INSERT INTO end_user_payment_profile SET 
            user_id = " . $createdUid . ", 
            authorizenet_profile_id = " . $this->platform->quoteValue ( $profileId ) . ", 
            payment_profile_id = " . $this->platform->quoteValue ( $paymentProfileId ) . ", 
            shipping_profile_id = " . $this->platform->quoteValue ( $shippingAddrsId ) . ", 
            card_holder = " . $this->platform->quoteValue ( $card_holder );
		
		$queryExeA = $this->adapter->query ( $queryAdd );
		$queryExeA->execute ();
		return;
	}
	
	/**
	 *
	 * @param type $userId        	
	 * @return type
	 */
	public function getUserRemotiiIOconfig($userId, $remotiiId = "") {
		if ($remotiiId) {
			$q = "SELECT * FROM
                remotii r INNER JOIN user_remotii ur ON (r.remotii_id = ur.remotii_id)
                WHERE
                ur.user_id = '" . $userId . "' AND
                r.remotii_status <> '" . SUSPENDED . "' AND r.remotii_status <> '" . SUSPENDED_BY_ADMIN . "' AND
                ur.remotii_id = '$remotiiId'
                ORDER BY ur.user_remotii_id DESC";
		} else {
			$q = "SELECT * FROM
                remotii r INNER JOIN user_remotii ur ON ( r.remotii_id = ur.remotii_id )
                WHERE
                ur.user_id = '" . $userId . "' AND
                r.remotii_status <> '" . SUSPENDED . "' AND r.remotii_status <> '" . SUSPENDED_BY_ADMIN . "' AND
                ur.is_default = 1
                ORDER BY ur.user_remotii_id DESC
                ";
		}
		
		$stmt = $this->adapter->query ( $q );
		$results = $stmt->execute ()->getResource ()->fetchAll ( \PDO::FETCH_ASSOC );
		
		foreach ( $results as $custData ) {
			// Query to get the input config
			$in = "SELECT * FROM user_remotii_input_config WHERE user_remotii_id = '" . $custData ['user_remotii_id'] . "'";
			$inR = $this->adapter->query ( $in );
			$resultsIn = $inR->execute ()->getResource ()->fetchAll ( \PDO::FETCH_ASSOC );
			
			// Query to get the output config
			$out = "SELECT * FROM user_remotii_output_config WHERE user_remotii_id = '" . $custData ['user_remotii_id'] . "'";
			$outR = $this->adapter->query ( $out );
			$resultsOut = $outR->execute ()->getResource ()->fetchAll ( \PDO::FETCH_ASSOC );
		}
		
		// generating the custom array to diaplay
		$responseConfig = array ();
		$responseConfig ['baseRec'] = $results;
		$responseConfig ['inConfig'] = $resultsIn;
		$responseConfig ['outConfig'] = $resultsOut;
		return $responseConfig;
	}
	
	/**
	 * Changes remotii status to ACTIVE or INACTIVE
	 *
	 * @author emp24
	 */
	public function assignDefaultRemotii($userId, $remotiiId) {
		if ($userId && $remotiiId) {
			$query = "
	    			UPDATE user_remotii SET is_default = '" . INACTIVE . "' WHERE user_id= '$userId'; 
	    			UPDATE user_remotii SET is_default = '" . ACTIVE . "' WHERE remotii_id = '$remotiiId' ";
			$queryExeA = $this->adapter->query ( $query );
			$queryExeA->execute ();
		} else {
			return array (
					'status' => 'FAIL',
					'message' => 'Please provide both user id and remotii id' 
			);
		}
		return array (
				'status' => 'OK' 
		);
	}
	
	/**
	 *
	 * @param type $configId        	
	 * @param type $status        	
	 * @return type
	 */
	function changeRemotiiOutConfigStatus($configId, $status) {
		$qry = "UPDATE user_remotii_output_config SET output_initial_state = '" . $status . "' WHERE config_id = '" . $configId . "'";
		$queryExeA = $this->adapter->query ( $qry );
		$queryExeA->execute ();
		
		// Query to get the output config
		$out = "SELECT * FROM user_remotii_output_config WHERE config_id = '" . $configId . "'";
		$outR = $this->adapter->query ( $out );
		$resultsOut = $outR->execute ()->getResource ()->fetchAll ( \PDO::FETCH_ASSOC );
		$returnData = $resultsOut [0];
		return $returnData;
	}
	function changeOBRemotiiPin($params) {
		$macAddress = $params ['mac_address'];
		$remotiiId = $params ['remotii_id'];
		$doutSet = $params ['dout_set'];
		$doutClr = $params ['dout_clr'];
		
		$qry = "INSERT INTO outbound SET 
    				remotii_id = '" . $remotiiId . "',
    				mac_address = '" . $macAddress . "',
    				dout_set = '" . $doutSet . "', 
    				dout_clr = '$doutClr'";
		
		$queryExeA = $this->adapter->query ( $qry );
		$queryExeA->execute ();
	}
	
	/**
	 *
	 * @param type $rid        	
	 * @return type
	 */
	public function getClientById($uid) {
		$query = "SELECT * FROM user u WHERE u.user_id = '" . $uid . "'";
		$dataPro = $this->adapter->query ( $query );
		$results = $dataPro->execute ()->getResource ()->fetchAll ( \PDO::FETCH_OBJ );
		return $results;
	}
	public function getInboundData($remotiiId, $last_message_id = '0', $lessThenMessage = false, $limit = '100') {
		$limit = ( int ) $limit;
		
		if ($lessThenMessage) {
			$co = '<';
		} else {
			$co = '>';
		}
		
		$query = "SELECT
	    				message_id,
	    				DATE_FORMAT(receive_time, '%c/%e/%Y %l:%i:%s %p') as receive_time,
    			        din,
	    				dout
	    		  FROM
	    				inbound ib 
	    		  WHERE 
	    				ib.mac_address = ( select mac_address from remotii 
	    		  						WHERE remotii_id = " . $this->platform->quoteValue ( $remotiiId ) . " 
    							)
    						AND  message_id $co " . $this->platform->quoteValue ( $last_message_id ) . " 	
    			  ORDER BY  ib.receive_time DESC LIMIT $limit";
		
		$dataPro = $this->adapter->query ( $query );
		$results = $dataPro->execute ()->getResource ()->fetchAll ( \PDO::FETCH_OBJ );
		return $results;
	}
	public function remotiiNameExists($remotiiName) {
		$query = "select remotii_name from user_remotii where remotii_name=" . $this->platform->quoteValue ( $remotiiName );
		$stmt = $this->adapter->query ( $query );
		$result = $stmt->execute ()->getResource ()->fetchColumn ();
		return $result;
	}
	public function getClientInputConfig($urid) {
		$q = "select * from user_remotii_input_config where user_remotii_id = '$urid'";
		$stmt = $this->adapter->query ( $q );
		$results = $stmt->execute ()->getResource ()->fetchAll ( \PDO::FETCH_ASSOC );
		return $results;
	}
	public function getClientOutputConfig($urid) {
		$q = "select * from user_remotii_output_config where user_remotii_id = '$urid'";
		$stmt = $this->adapter->query ( $q );
		$results = $stmt->execute ()->getResource ()->fetchAll ( \PDO::FETCH_ASSOC );
		return $results;
	}
	
	/**
	 * Check if end user has remtoii
	 *
	 * @param array $variables
	 *        	<pre>
	 *        	An associative array containing:
	 *        	- userId: The user Id
	 *        	- remotiiId: Remotii Id
	 *        	- remotiiMacAddress: Remotii mac address
	 *        	One of remotiiId or remotiiMacAddress is required
	 *        	</pre>
	 *        	
	 * @author emp24
	 * @return boolean
	 */
	public function endUserHasRemotii($variables) {
		$remotiiId = $variables ['remotiiId'];
		$remotiiMacAddress = $variables ['remotiiMacAddress'];
		$userId = $variables ['userId'];
		
		if (empty ( $userId )) {
			return array (
					'status' => 'FAIL',
					'message' => 'User Id is required' 
			);
		}
		
		if (empty ( $remotiiId ) && empty ( $remotiiMacAddress )) {
			return array (
					'status' => 'FAIL',
					'message' => 'One is required either remotii id or remotii mac address' 
			);
		}
		
		if ($remotiiId) {
			$q = "select count(*) from user_remotii where user_id = " . $this->platform->quoteValue ( $userId ) . " and remotii_id =" . $this->platform->quoteValue ( $remotiiId );
		} else if ($remotiiMacAddress) {
			$q = "select count(*) from user_remotii where remotii_id =" . $this->platform->quoteValue ( $remotiiId );
		}
		
		$stmt = $this->adapter->query ( $q );
		$count = $stmt->execute ()->getResource ()->fetchColumn ();
		return array (
				'status' => ($count ? 'OK' : 'FAIL') 
		);
	}
	public function getEUByMac( $mac ) {
		$q = "select u.* from user u 
						where user_id = ( select user_id from remotii r inner join user_remotii ur 
											on(r.remotii_id = ur.remotii_id) 
											where r.mac_address='$mac')";
		$stmt = $this->adapter->query ( $q );
		return $result = $stmt->execute ()->getResource ()->fetch (\PDO::FETCH_ASSOC);
	}
}