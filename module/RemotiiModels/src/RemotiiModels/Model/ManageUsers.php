<?php

namespace RemotiiModels\Model;

use Zend\Db\Adapter\Adapter;
use Zend\Db\TableGateway\AbstractTableGateway;
use Zend\Crypt\Password\Bcrypt;

class ManageUsers extends AbstractTableGateway {

    public $adapter;
    public $platform;

    /**
     * Service Provider Id from service_provider table 
     */
    public $companyId;

    /**
     * @param \Zend\Db\Adapter\Adapter $db
     */
    public function __construct(Adapter $db) {
        $this->adapter = $db;
        $this->platform = $this->adapter->getPlatform();
    }

    public function checkUser($loggedInUserId, $email, $remotiiId) {

        $query1 = "SELECT * FROM user WHERE email='$email' AND user_id <> '$loggedInUserId' LIMIT 1";
        $result1 = $this->adapter->query($query1);
        $results = $result1->execute()->getResource()->fetchAll(\PDO::FETCH_OBJ);
        $userid = $results[0]->user_id;
        if (!empty($userid)) {
            $query2 = "SELECT COUNT(shr.id) as shared_count FROM shared_remotii shr
                INNER JOIN user_remotii ur ON (shr.user_remotii_id=ur.user_remotii_id)
                WHERE shr.shared_user_id='$userid' AND ur.remotii_id='$remotiiId'  LIMIT 1";
            $result2 = $this->adapter->query($query2);
            $results2 = $result2->execute()->getResource()->fetch(\PDO::FETCH_ASSOC);
            if (empty($results2['shared_count'])) {
                return TRUE;
            }
        }
        return false;
    }

    public function deleteShareRemotii($userRemotiiID, $sharedUsersId) {
        $query1 = "delete  FROM shared_remotii WHERE user_remotii_id='$userRemotiiID'AND shared_user_id='$sharedUsersId'";
        $result1 = $this->adapter->query($query1);
        $results = $result1->execute();
        return true;
    }

    public function getUserId($email) {
        $query1 = "SELECT * FROM user WHERE email='$email'";
        $result1 = $this->adapter->query($query1);
        $results = $result1->execute()->getResource()->fetchAll(\PDO::FETCH_OBJ);
        return $results;
    }

    public function updateAccess($userRemotiiID, $sharedUsersId, $access_level) {
        $queryAdd = "UPDATE shared_remotii SET access_level=$access_level 
      where user_remotii_id='$userRemotiiID' AND shared_user_id='$sharedUsersId'";
        $queryExeA = $this->adapter->query($queryAdd);
        $queryExeA->execute();
        return true;
    }

    public function insertService($userRemotiiID, $sharedUsersId, $AccessId, $servicePro) {
//        $updated_on = $servicePro[0]->updated_on;
//        $created_on = $servicePro[0]->created_on;
        $updated_on = time();
        $created_on = time();
        $query = "INSERT INTO shared_remotii (`shared_user_id`, `user_remotii_id`, `updated_on`, `created_on`, `access_level`)
        VALUES ($sharedUsersId,$userRemotiiID,$updated_on,$created_on,$AccessId)";
        $queryExeA = $this->adapter->query($query);
        $queryExeA->execute();
        return true;
    }

    public function getservice($id) {

        $query1 = "SELECT * FROM  shared_remotii WHERE user_remotii_id 	='$id'";
        $result1 = $this->adapter->query($query1);
        $results = $result1->execute()->getResource()->fetchAll(\PDO::FETCH_OBJ);

        return $results;
    }

    public function userInfo($id) {
        $query1 = "SELECT * FROM  user_remotii WHERE remotii_id='$id'";
        $result1 = $this->adapter->query($query1);
        $results = $result1->execute()->getResource()->fetchAll(\PDO::FETCH_OBJ);

        return $results;
    }

    public function getuserRemotiiId($remotii_id, $loggedInUserId) {
        $query1 = "SELECT user_remotii_id FROM  user_remotii WHERE remotii_id='$remotii_id' AND user_id='$loggedInUserId'";
        $result1 = $this->adapter->query($query1);
        $results = $result1->execute()->getResource()->fetchAll(\PDO::FETCH_OBJ);

        return $results;
    }

    public function SharePersonInf($userId, $remotiiId) {
        $query1 = "SELECT user.username,user.email,user.user_id,
                    shared_remotii.created_on,shared_remotii.updated_on,shared_remotii.access_level 
                    FROM shared_remotii 
                    INNER JOIN user_remotii 
                    ON user_remotii.user_remotii_id=shared_remotii.user_remotii_id
                    INNER JOIN user
                    ON user.user_id=shared_remotii.shared_user_id 
                    where user_remotii.remotii_id='$remotiiId' AND user_remotii.user_id='$userId'
                    ";
        $result1 = $this->adapter->query($query1);
        $results = $result1->execute()->getResource()->fetchAll(\PDO::FETCH_OBJ);
        return $results;
    }

    public function getUserList($rid, $sf="null") {

        if (in_array($sf, array(DELINQUENT, SUSPENDED))) {
            $sq = " AND acc_status = '$sf'";
        }

        $query = "SELECT * FROM user WHERE user_role_id = '" . $rid . "' $sq ORDER BY user_id DESC";
        $dataPro = $this->adapter->query($query);
        $results = $dataPro->execute()->getResource()->fetchAll(\PDO::FETCH_OBJ);
        return $results;
    }

    public function getEndUsers($sf) {

        if (in_array($sf, array(DELINQUENT, SUSPENDED))) {
            $sq = " AND acc_status = '$sf'";
        }

        $query = "SELECT u.*  FROM user u
    				WHERE u.user_role_id = 3 $sq ORDER BY u.user_id DESC";
        $dataPro = $this->adapter->query($query);
        $results = $dataPro->execute()->getResource()->fetchAll(\PDO::FETCH_OBJ);
        return $results;
    }

    /**
     * 
     * @param type $rid
     * @return type
     */
    public function getUserById($uid) {
        $query = "SELECT * FROM user WHERE user_id = '" . $uid . "'";
        $dataPro = $this->adapter->query($query);
        $results = $dataPro->execute()->getResource()->fetchAll(\PDO::FETCH_OBJ);
        return $results;
    }

    /**
     * 
     * @param type $uid
     * @return boolean
     */
    public function deleteUser($uid) {
//  Get mac address of the user
        $q = "SELECT r.mac_address, ur.user_remotii_id 
                FROM 
                    user_remotii ur INNER JOIN remotii r ON (ur.remotii_id = r.remotii_id) 
                WHERE ur.user_id = '" . $uid . "'";

        $dataPro = $this->adapter->query($q);
        $resultsMac = $dataPro->execute()->getResource()->fetchAll(\PDO::FETCH_ASSOC);

        $macAddrsArr = array();
        $macAddrsArr[] = 0;
        $userRmId = array();
        $userRmId[] = 0;
        foreach ($resultsMac as $mac) {
            $macAddrsArr[] = "'" . $mac['mac_address'] . "'";
            $userRmId[] = $mac['user_remotii_id'];
        }
        $joinMacAddrs = implode(',', $macAddrsArr);
        $joinUserRmId = implode(',', $userRmId);

        $this->adapter->getDriver()->getConnection()->beginTransaction();
        try {
//  Delete the config of this remotii
            $q = "DELETE FROM user_remotii_input_config WHERE user_remotii_id IN (" . $joinUserRmId . ")";
            $queryE = $this->adapter->query($q);
            $queryE->execute();

//  Delete the config of this remotii
            $q = "DELETE FROM user_remotii_output_config WHERE user_remotii_id IN (" . $joinUserRmId . ")";
            $queryE = $this->adapter->query($q);
            $queryE->execute();

//  Delete Remotii from inbound
            $qI = "DELETE FROM inbound WHERE mac_address = '" . $joinMacAddrs . "'";
            $queryI = $this->adapter->query($qI);
            $queryI->execute();

//  Delete Remotii from outbound
            $qO = "DELETE FROM outbound WHERE mac_address = '" . $joinMacAddrs . "'";
            $queryO = $this->adapter->query($qO);
            $queryO->execute();

//  Delete from user remotii table
            $delQry = "DELETE FROM user_remotii WHERE user_id = '" . $uid . "'";
            $queryE = $this->adapter->query($delQry);
            $queryE->execute();

//  Delete from user remotii table
            $delQry = "DELETE FROM end_user_payment_profile WHERE user_id = '" . $uid . "'";
            $queryE = $this->adapter->query($delQry);
            $queryE->execute();

//  Delete from user table
            $delQry = "DELETE FROM user WHERE user_id = '" . $uid . "'";
            $queryE = $this->adapter->query($delQry);
            $queryE->execute();

            $this->adapter->getDriver()->getConnection()->commit();
        } catch (Exception $e) {
            $this->adapter->getDriver()->getConnection()->rollback();
        }

        return true;
    }

    /**
     * 
     * @param type $email
     * @param type $uid
     * @return type
     */
    public function emailDuplicasyChk($email, $uid, $rid) {
        $query = "SELECT user_id FROM user WHERE email = " . $this->platform->quoteValue($email) . " AND user_role_id = '" . $rid . "' ";
        if ($uid <> '') {
            $query .= " AND user_id != '" . $uid . "'";
        }
        $dataPro = $this->adapter->query($query);
        $results = $dataPro->execute()->getResource()->fetchAll(\PDO::FETCH_OBJ);
        return $results[0]->user_id;
    }

    /**
     * 
     * @param type $email
     * @param type $uid
     * @return type
     */
    public function emailDuplicasyChkSP($email, $uid, $rid) {
        $query = "SELECT service_provider_id FROM service_provider WHERE contact_email = " . $this->platform->quoteValue($email);
        if ($uid <> '') {
            $query .= " AND service_provider_id != '" . $uid . "'";
        }
        $dataPro = $this->adapter->query($query);
        $results = $dataPro->execute()->getResource()->fetchAll(\PDO::FETCH_OBJ);
        return $results[0]->user_id;
    }

    /**
     * 
     * @param type $userName
     * @param type $uid
     * @return type
     */
    public function userNameDuplicasyChk($userName, $uid) {
        $query = "SELECT user_id FROM user WHERE username = " . $this->platform->quoteValue($userName) . "";
        if ($uid <> '') {
            $query .= " AND user_id != '" . $uid . "'";
        }
        $dataPro = $this->adapter->query($query);
        $results = $dataPro->execute()->getResource()->fetchAll(\PDO::FETCH_OBJ);
        return $results[0]->user_id;
    }

    /**
     * Function createAdminUser() defined to save admin user
     * 
     * @param type $data
     * @return boolean
     */
    public function createAdminUser($data, $loggedInUserId) {
        $time = time();
        $adminRole = 1; //  1 indicate admin user role
//  Encode the password using 
        $bcrypt = new Bcrypt();
        // $bcrypt->setCost();
        $password = $bcrypt->create($data['password']);

        $queryAdd = "INSERT INTO user SET username = " . $this->platform->quoteValue($data['userName']) . ", fname = " . $this->platform->quoteValue($data['fName']) . ", 
            lname = " . $this->platform->quoteValue($data['lName']) . ", phone = " . $this->platform->quoteValue($data['phoneNumber']) . ", email = " . $this->platform->quoteValue($data['emailId']) . ", 
            password = " . $this->platform->quoteValue($password) . ", user_role_id = " . $this->platform->quoteValue($adminRole) . ", updated_by = " . $this->platform->quoteValue($loggedInUserId) . ", 
            updated_on = " . $time . ", created_on = " . $time . "";

        $queryExeA = $this->adapter->query($queryAdd);
        $queryExeA->execute();
        return true;
    }

    public function createSPUser($data, $loggedInUserId, $spid) {
        $time = time();

//  Encode the password using
        $bcrypt = new Bcrypt();
        // $bcrypt->setCost();
        $password = $bcrypt->create($data['password']);

        $queryAdd = "INSERT INTO user SET username = " . $this->platform->quoteValue($data['userName']) . ", fname = " . $this->platform->quoteValue($data['fName']) . ",
            lname = " . $this->platform->quoteValue($data['lName']) . ", phone = " . $this->platform->quoteValue($data['phoneNumber']) . ", email = " . $this->platform->quoteValue($data['emailId']) . ",
            password = " . $this->platform->quoteValue($password) . ", user_role_id = 2, updated_by = " . $this->platform->quoteValue($loggedInUserId) . ",
            updated_on = " . $time . ", created_on = " . $time . "";

        $queryExeA = $this->adapter->query($queryAdd);
        $queryExeA->execute();
        $lastInsertedId = $this->adapter->getDriver()->getLastGeneratedValue();

        $query = "insert into service_provider_admins set service_provider_id='$spid', user_id='$lastInsertedId'";
        $queryExeA = $this->adapter->query($query);
        $queryExeA->execute();

        return true;
    }

    public function getSPUsers($spid) {
        $q = "SELECT * FROM service_provider_admins spa INNER JOIN user u ON( spa.user_id = u.user_id )
	            WHERE spa.service_provider_id = '$spid'";

        $dataPro = $this->adapter->query($q);
        $results = $dataPro->execute()->getResource()->fetchAll(\PDO::FETCH_OBJ);
        return $results;
    }

    public function deleteSPUser($uid) {
        $q = "DELETE FROM service_provider_admins WHERE user_id = '" . $uid . "'";
        $queryE = $this->adapter->query($q);
        $queryE->execute();

        $delQry = "DELETE FROM user WHERE user_id = '" . $uid . "'";
        $queryE = $this->adapter->query($delQry);
        $queryE->execute();

        return true;
    }

    /**
     * Function createAdminUser() defined to save admin user
     * 
     * @param type $data
     * @return boolean
     */
    public function updateAdminUser($data, $loggedInUserId, $id) {
        $password = '';
        $time = time();
        $adminRole = 1; //  1 indicate admin user role
//  Encode the password using
        if ($data['password'] <> '') {
            $bcrypt = new Bcrypt();
            // $bcrypt->setCost();
            $password = $bcrypt->create($data['password']);
        }

        $queryAdd = "UPDATE user SET username = " . $this->platform->quoteValue($data['userName']) . ", fname = " . $this->platform->quoteValue($data['fName']) . ", 
            lname = " . $this->platform->quoteValue($data['lName']) . ", phone = " . $this->platform->quoteValue($data['phoneNumber']) . ", email = " . $this->platform->quoteValue($data['emailId']) . ", ";
        if ($data['password'] <> '') {
            $queryAdd .= "password = " . $this->platform->quoteValue($password) . ", ";
        }
        $queryAdd .= "user_role_id = " . $this->platform->quoteValue($adminRole) . ", updated_by = " . $this->platform->quoteValue($loggedInUserId) . ", 
        updated_on = " . $time . ", display_name = " . $this->platform->quoteValue($data['displayName']) . " WHERE user_id = '" . $id . "'";

        $queryExeA = $this->adapter->query($queryAdd);
        $queryExeA->execute();
        return true;
    }

    public function updateSPUser($data, $loggedInUserId, $id) {
        $password = '';
        $time = time();
        $role = 2; //  1 indicate admin user role
//  Encode the password using
        if ($data['password'] <> '') {
            $bcrypt = new Bcrypt();
            // $bcrypt->setCost();
            $password = $bcrypt->create($data['password']);
        }

        $queryAdd = "UPDATE user SET username = " . $this->platform->quoteValue($data['userName']) . ", fname = " . $this->platform->quoteValue($data['fName']) . ",
            lname = " . $this->platform->quoteValue($data['lName']) . ", phone = " . $this->platform->quoteValue($data['phoneNumber']) . ", email = " . $this->platform->quoteValue($data['emailId']) . ", ";
        if ($data['password'] <> '') {
            $queryAdd .= "password = " . $this->platform->quoteValue($password) . ", ";
        }
        $queryAdd .= "user_role_id = " . $this->platform->quoteValue($role) . ", updated_by = " . $this->platform->quoteValue($loggedInUserId) . ",
        updated_on = " . $time . ", display_name = " . $this->platform->quoteValue($data['displayName']) . " WHERE user_id = '" . $id . "'";

        $queryExeA = $this->adapter->query($queryAdd);
        $queryExeA->execute();
        return true;
    }

    /**
     * 
     * @param type $data
     * @param type $loggedInUserId
     * @return boolean
     */
    public function createServiceProviderUser($data, $loggedInUserId) {
        $time = time();
        $newstring = substr(trim($data['card_number']), -4);
        $cardNumber = 'xxxxxxxxxxxx' . $newstring;
        $newSPid = getBigRandom(10);

        if ($data['card_holder'] &&
                $data['card_number']) {
            $subQ = "card_holder = " . $this->platform->quoteValue($data['card_holder']) . ",
                     card_number = " . $this->platform->quoteValue($cardNumber) . ",";
        }
        if(!$data['allow_end_user_billing']){
            $data['allow_end_user_billing'] = '0';
        }
        if(!$data['service_fee']){
            $data['service_fee'] = '0.00';
        }
        $queryAdd = "INSERT INTO service_provider SET 
            service_provider_id = " . $newSPid . ", 
        	acc_created_on = " . time() . ",
        	acc_status = " . ACTIVE . ",
        	$subQ
            contracted_price = " . $this->platform->quoteValue($data['contracted_price']) . ", 
            end_user_price = " . $this->platform->quoteValue($data['end_user_price']) . ",
            service_provider_credit = " . $this->platform->quoteValue($data['service_provider_credit']) . ",    
            allow_end_user_billing = " . $this->platform->quoteValue($data['allow_end_user_billing']) . ",
            service_fee = " . $this->platform->quoteValue($data['service_fee']) . ", 
            updated_by = " . $this->platform->quoteValue($loggedInUserId) . ",
            company_name = " . $this->platform->quoteValue($data['company']) . ", 
            updated_on = '" . $time . "',
            contact_fname = " . $this->platform->quoteValue($data['fName']) . ",
			contact_lname = " . $this->platform->quoteValue($data['lName']) . ",
			contact_phone = " . $this->platform->quoteValue($data['phoneNumber']) . ",
			contact_email = " . $this->platform->quoteValue($data['emailId']) . "";
        $queryExeA = $this->adapter->query($queryAdd);
        $queryExeA->execute();
//$lastInsertedId = $this->adapter->getDriver()->getLastGeneratedValue();

        return $newSPid;
    }

    /**
     * Function call to save Contracted price and service fee charges
     * 
     * @param type $spId
     * @param type $data
     * @return boolean
     */
    /*  public function saveServiceProviderOtherInfo($spId, $data, $loggedInUserId) {
      $time = time();
      $newstring = substr(trim($data['card_number']), -4);
      $cardNumber = 'xxxxxxxxxxxx' . $newstring;
      $newSPid = getBigRandom(10);

      $queryAdd = "INSERT INTO service_provider SET card_number = ".$this->platform->quoteValue($cardNumber).",
      service_provider_id = ".$newSPid.",
      acc_created_on = ".time().",
      acc_status = ".ACTIVE.",
      card_holder = ".$this->platform->quoteValue($data['card_holder']).",
      contracted_price = " . $this->platform->quoteValue($data['contracted_price']) . ",
      end_user_price = ".$this->platform->quoteValue($data['end_user_price']) .",
      allow_end_user_billing = " . $this->platform->quoteValue($data['allow_end_user_billing']) . ",
      service_fee = " . $this->platform->quoteValue($data['service_fee']) . ",
      updated_by = " . $this->platform->quoteValue($loggedInUserId) . ",
      company_name = " . $this->platform->quoteValue($data['company']) . ", updated_on = '". $time ."'
      ";
      $queryExeA = $this->adapter->query($queryAdd);
      $queryExeA->execute();
      $lastInsertedId = $this->adapter->getDriver()->getLastGeneratedValue();

      $this->companyId = $lastInsertedId;

      $queryAdd = "INSERT INTO service_provider_admins SET service_provider_id = '$lastInsertedId', user_id='$spId'";
      $queryExeA = $this->adapter->query($queryAdd);
      $queryExeA->execute();

      return true;
      } */

    /**
     * 
     * @param type $postData
     * @param type $spId
     */
    public function saveServiceProviderReceivningPaymentInfo($postData, $spId, $stripeAccId) {
        if ($postData['allow_end_user_billing'] == 1 &&
                $postData['routing_number'] <> '' &&
                $postData['account_type'] <> '' &&
                $postData['account_number'] <> '' &&
                $postData['name_on_bank'] <> '') {
            $queryAdd = "INSERT INTO service_provider_receiving_payment_details SET 
                            service_provider_id = " . $spId . ", 
                            routing_number = " . $this->platform->quoteValue($postData['routing_number']) . ", 
                            account_type = " . $this->platform->quoteValue($postData['account_type']) . ", 
                            account_number = " . $this->platform->quoteValue($postData['account_number']) . ", 
                            name_on_bank = " . $this->platform->quoteValue($postData['name_on_bank']) . ", 
                            stripe_acc_id = " . $this->platform->quoteValue($stripeAccId);

            $queryExeA = $this->adapter->query($queryAdd);
            $queryExeA->execute();
        }
    }

    /**
     * 
     * @param type $postData
     * @param type $spId
     */
    public function updateServiceProviderReceivningPaymentInfo($postData, $spId, $rpID) {
        if ($postData['allow_end_user_billing'] == 1 &&
                $postData['routing_number'] <> '' &&
                $postData['account_type'] <> '' &&
                $postData['account_number'] <> '' &&
                $postData['name_on_bank'] <> '') {
            $queryAdd = "UPDATE service_provider_receiving_payment_details SET 
                            routing_number = " . $this->platform->quoteValue($postData['routing_number']) . ", 
                            account_type = " . $this->platform->quoteValue($postData['account_type']) . ", 
                            account_number = " . $this->platform->quoteValue($postData['account_number']) . ", 
                            stripe_acc_id = " . $this->platform->quoteValue($rpID) . ", 
                            name_on_bank = " . $this->platform->quoteValue($postData['name_on_bank']) . " 
                            WHERE service_provider_id = " . $spId;

            $queryExeA = $this->adapter->query($queryAdd);
            $queryExeA->execute();
        }
    }

    /**
     * 
     * @param type $spId
     */
    public function getServiceProviderReceivningPaymentInfo($spId) {
        $query = "SELECT * FROM service_provider_receiving_payment_details WHERE service_provider_id = '$spId'";
        $dataPro = $this->adapter->query($query);
        $results = $dataPro->execute()->getResource()->fetchAll(\PDO::FETCH_OBJ);
        return $results;
    }

    /**
     * 
     * @return type
     */
    public function getServiceProviderList($sf) {

        if (in_array($sf, array(DELINQUENT, SUSPENDED))) {
            $sq = " WHERE sp.acc_status = '$sf'";
        }

        $query = "SELECT
                    @spid := sp.service_provider_id AS spid,
                    sp.company_name,
                    sp.contracted_price,
                    sp.end_user_price,
                    sp.allow_end_user_billing,
                    sp.service_fee,
                    sp.acc_created_on,
                    sp.admin_notes,
                    ( SELECT COUNT(*)
                         FROM 
                              remotii r WHERE (r.service_provider_id = @spid AND r.remotii_status = " . ACTIVE . ")
                    ) AS total_active_remotiis,
                    @inactive_remotiis := ( SELECT COUNT(*)
                         FROM 
                            remotii r WHERE (r.service_provider_id = @spid AND (r.remotii_status = " . SUSPENDED . " OR r.remotii_status = " . SUSPENDED_BY_ADMIN . "))
                    ) AS total_inactive_remotiis,
                    IFNULL(ps.payment_status,'N/A') as paymentstatus,
                    IFNULL(ps.executed_on,'N/A') as executedon,
                    ps.amount,
                    ps.credit,
                    sp.acc_status
                  FROM
                    service_provider sp left outer join payment_stats ps on (sp.last_payment_stat_id = ps.payment_id)
                  $sq
        			";

        $dataPro = $this->adapter->query($query);
        $results = $dataPro->execute()->getResource()->fetchAll(\PDO::FETCH_OBJ);
        return $results;
    }

    /**
     * 
     * @return type
     */
    public function getServiceProviderInfo($sid) {
        $query = "SELECT * FROM service_provider sp WHERE service_provider_id='$sid'";
        $dataPro = $this->adapter->query($query);
        $results = $dataPro->execute()->getResource()->fetchAll(\PDO::FETCH_OBJ);
        return $results;
    }

    public function getServiceProviderInfoByRemotiiId($remotiiId) {
        $query = "SELECT sp.* FROM service_provider sp INNER JOIN remotii r 
    				ON ( sp.service_provider_id = r.service_provider_id ) 
    				WHERE r.remotii_id='$remotiiId' GROUP BY sp.service_provider_id";

        $dataPro = $this->adapter->query($query);
        $results = $dataPro->execute()->getResource()->fetchAll(\PDO::FETCH_OBJ);
        return $results;
    }

    /**
     * 
     * @param type $data
     * @param type $loggedInUserId
     * @return boolean
     */
    public function updateServiceProviderUser($data, $loggedInUserId, $sid) {
        $time = time();
        $adminRole = 2; //  1 indicate admin user role
//  Encode the password using 
        $bcrypt = new Bcrypt();
        $bcrypt->setCost();
        $password = $bcrypt->create($data['password']);

        $queryAdd = "UPDATE user SET username = " . $this->platform->quoteValue($data['userName']) . ", fname = " . $this->platform->quoteValue($data['fName']) . ", 
            lname = " . $this->platform->quoteValue($data['lName']) . ", phone = " . $this->platform->quoteValue($data['phoneNumber']) . ", email = " . $this->platform->quoteValue($data['emailId']) . ", 
            password = " . $this->platform->quoteValue($password) . ", user_role_id = " . $this->platform->quoteValue($adminRole) . ", updated_by = " . $this->platform->quoteValue($loggedInUserId) . ", 
            updated_on = " . $time . ", display_name = " . $this->platform->quoteValue($data['displayName']) . ", 
            street = " . $this->platform->quoteValue($data['street']) . ", city = " . $this->platform->quoteValue($data['city']) . ", company = " . $this->platform->quoteValue($data['company']) . ", 
            state = " . $this->platform->quoteValue($data['state']) . ", country = " . $this->platform->quoteValue($data['country']) . ", zip_code = " . $this->platform->quoteValue($data['zip']);

        if ($data['acc_status'] <> '') {
            $queryAdd .= " , acc_status = " . $this->platform->quoteValue($data['acc_status']);
        }

        $queryAdd .= " WHERE user_id = '" . $sid . "' ";

        $queryExeA = $this->adapter->query($queryAdd);
        $queryExeA->execute();

//  Function call to save remoti and service fee charges
        $this->updateServiceProviderOtherInfo($sid, $data, $loggedInUserId);
        return true;
    }

    /**
     * 
     * @param type $data
     * @param type $loggedInUserId
     * @return boolean
     */
    public function updateServiceProviderCompany($data, $loggedInUserId, $sid) {
        $time = time();
        $newstring = substr(trim($data['card_number']), -4);
        $cardNumber = 'xxxxxxxxxxxx' . $newstring;

        $accountStatus = ($data['acc_status'] == "2" ? SUSPENDED : ACTIVE);

        if ($data['card_holder'] &&
                $data['card_number']) {
            $subQ = "card_holder = " . $this->platform->quoteValue($data['card_holder']) . ", 
                     card_number = " . $this->platform->quoteValue($cardNumber) . ",";
        }
        if(!$data['allow_end_user_billing']){
            $data['allow_end_user_billing'] = '0';
        }
        if(!$data['service_fee']){
            $data['service_fee'] = '0.00';
        }
        $queryAdd = "UPDATE service_provider SET 
                    company_name = " . $this->platform->quoteValue($data['company_name']) . ", 
                    contact_fname = " . $this->platform->quoteValue($data['contact_fname']) . ", 
                    contact_lname = " . $this->platform->quoteValue($data['contact_lname']) . ", 
                    contact_phone = " . $this->platform->quoteValue($data['contact_phone']) . ", 
                    contact_email = " . $this->platform->quoteValue($data['contact_email']) . ", 
                    $subQ
                    contracted_price = " . $this->platform->quoteValue($data['contracted_price']) . ", 
                    allow_end_user_billing = " . $this->platform->quoteValue($data['allow_end_user_billing']) . ", 
                    service_fee = " . $this->platform->quoteValue($data['service_fee']) . ", 
                    end_user_price = " . $this->platform->quoteValue($data['end_user_price']) . ",
                    service_provider_credit = " . $this->platform->quoteValue($data['service_provider_credit']) . ",    
                    acc_status = " . $accountStatus . ",
                    updated_by = " . $this->platform->quoteValue($loggedInUserId) . ", 
                    updated_on = " . $time . "
                   	WHERE service_provider_id = '" . $sid . "'";

        $queryExeA = $this->adapter->query($queryAdd);
        $queryExeA->execute();

        $this->changeSPaccStatus($sid, $accountStatus);

        return true;
    }

    /**
     * 
     * @param type $data
     * @param type $loggedInUserId
     * @return boolean
     */
    public function updateServiceProviderBillingDetails($data, $loggedInUserId, $sid) {
        $time = time();
        $newstring = substr(trim($data['card_number']), -4);
        $cardNumber = 'xxxxxxxxxxxx' . $newstring;

        $accountStatus = ($data['acc_status'] == "2" ? SUSPENDED : ACTIVE);

        if ($data['card_holder'] &&
                $data['card_number']) {
            $subQ = "card_holder = " . $this->platform->quoteValue($data['card_holder']) . ", 
                     card_number = " . $this->platform->quoteValue($cardNumber) . ",";
        }

        $queryAdd = "UPDATE service_provider SET 
                    $subQ
                    contracted_price = " . $this->platform->quoteValue($data['contracted_price']) . ", 
                    allow_end_user_billing = " . $this->platform->quoteValue($data['allow_end_user_billing']) . ", 
                    service_fee = " . $this->platform->quoteValue($data['service_fee']) . ", 
                    end_user_price = " . $this->platform->quoteValue($data['end_user_price']) . ", 
                    acc_status = " . $accountStatus . ",
                    updated_by = " . $this->platform->quoteValue($loggedInUserId) . ", 
                    updated_on = " . $time . "
                   	WHERE service_provider_id = '" . $sid . "'";

        $queryExeA = $this->adapter->query($queryAdd);
        $queryExeA->execute();

        $this->changeSPaccStatus($sid, $accountStatus);

        return true;
    }

    /**
     * 
     * @param type $spId
     * @param type $data
     * @param type $loggedInUserId
     * @return boolean
     */
    public function updateServiceProviderOtherInfo($spId, $data, $loggedInUserId) {
        $time = time();
        $newstring = substr(trim($data['card_number']), -4);
        $cardNumber = 'xxxx' . $newstring;

        $queryAdd = "UPDATE service_provider SET card_number = " . $this->platform->quoteValue($cardNumber) . ", 
            contracted_price = " . $this->platform->quoteValue($data['contracted_price']) . ", allow_end_user_billing = " . $this->platform->quoteValue($data['allow_end_user_billing']) . ",
            service_fee = " . $this->platform->quoteValue($data['service_fee']) . ", end_user_price = " . $this->platform->quoteValue($data['end_user_price']) . ", 
            updated_by = " . $this->platform->quoteValue($loggedInUserId) . ", updated_on = " . $time . " WHERE user_id = '" . $spId . "'
        ";
        $queryExeA = $this->adapter->query($queryAdd);
        $queryExeA->execute();
        return true;
    }

    /**
     * 
     * @param type $data
     * @param type $loggedInUserId
     * @param type $id
     * @return boolean
     */
    public function updateEndUser($data, $loggedInUserId, $id) {
        $password = '';
        $time = time();

        $accStatus = $data['acc_status'] == SUSPENDED ? $data['acc_status'] : ACTIVE;
        $accStatus = $this->platform->quoteValue($accStatus);
//  Encode the password using
        if ($data['password'] <> '') {
            $bcrypt = new Bcrypt();
            $bcrypt->setCost();
            $password = $bcrypt->create($data['password']);
        }

        $queryAdd = "UPDATE user SET username = " . $this->platform->quoteValue($data['userName']) . ", fname = " . $this->platform->quoteValue($data['fName']) . ", 
            lname = " . $this->platform->quoteValue($data['lName']) . ", phone = " . $this->platform->quoteValue($data['phoneNumber']) . ", email = " . $this->platform->quoteValue($data['emailId']) . ", 
            street = " . $this->platform->quoteValue($data['street']) . ", city = " . $this->platform->quoteValue($data['city']) . ", state = " . $this->platform->quoteValue($data['state']) . ", 
            country = " . $this->platform->quoteValue($data['country']) . ", zip_code = " . $this->platform->quoteValue($data['zip']) . ", acc_status = " . $accStatus . ", 
        ";

        if ($data['password'] <> '') {
            $queryAdd .= "password = " . $this->platform->quoteValue($password) . ", ";
        }
        $queryAdd .= " updated_by = " . $this->platform->quoteValue($loggedInUserId) . ", updated_on = '" . $time . "', display_name = " . $this->platform->quoteValue($data['displayName']) . " WHERE user_id = '" . $id . "'";

        $queryExeA = $this->adapter->query($queryAdd);
        $queryExeA->execute();
        return true;
    }

    public function getServiceProviderIdFromUser($uid) {
        $query = "SELECT service_provider_id FROM service_provider_admins WHERE user_id = '$uid'";
        $dataPro = $this->adapter->query($query);
        return $dataPro->execute()->getResource()->fetchColumn();
    }

    public function getServiceProviderSummary($spid) {

        $billingStartDate = date('Y-m-d', strtotime(date('Y-m', strtotime('-1 month'))));
        $billingEndDate = date('Y-m-d', strtotime(date('Y-m')));

        $query = "SELECT COUNT(*) FROM remotii r INNER JOIN user_remotii ur 
    				ON( ur.remotii_id = r.remotii_id ) WHERE r.service_provider_id = '$spid'";
        $dataPro = $this->adapter->query($query);
        $return['totalClient'] = $dataPro->execute()->getResource()->fetchColumn();

        $query = "SELECT COUNT(*) FROM remotii r
    				 WHERE r.service_provider_id = '$spid' AND r.remotii_status = '" . ACTIVE . "'";
        $dataPro = $this->adapter->query($query);
        $return['activeRemotiis'] = $dataPro->execute()->getResource()->fetchColumn();

        $query = "SELECT COUNT(*) FROM remotii r 
    				WHERE r.service_provider_id = '$spid' AND ( r.remotii_status = '" . SUSPENDED . "' OR r.remotii_status = '" . SUSPENDED_BY_ADMIN . "' ) ";
        $dataPro = $this->adapter->query($query);
        $return['inActiveRemotiis'] = $dataPro->execute()->getResource()->fetchColumn();

        $query = "SELECT TRUNCATE(IFNULL(SUM( amount ),0),0) from payment_stats where payment_id in 
    	          ( select last_payment_stat_id FROM service_provider sp where sp.service_provider_id = '$spid')";

        $dataPro = $this->adapter->query($query);
        $netIncomeInPBC = $dataPro->execute()->getResource()->fetchColumn();

        /*
         * Count Delinquent and Active Remotii Users of Service Provider ( $DAU )
         */

        $query = "SELECT
					TRUNCATE(SUM(case when ( ur.user_id is null) OR sp.acc_status = " . SUSPENDED . " OR r.remotii_status in(" . SUSPENDED . "," . SUSPENDED_BY_ADMIN . ") then 0 else end_user_price end),2) AS end_user_price,
					TRUNCATE(SUM(case when ( ur.user_id is null) OR sp.acc_status = " . SUSPENDED . " OR r.remotii_status in(" . SUSPENDED . "," . SUSPENDED_BY_ADMIN . ") then 0 else (sp.end_user_price * sp.service_fee / 100 ) end),2) AS servie_fee, 
					TRUNCATE((SUM(case when r.remotii_status in(" . SUSPENDED . "," . SUSPENDED_BY_ADMIN . ") then sp.end_user_price else 0 end)),2) AS inactive_income,
					TRUNCATE((SUM(case when r.remotii_status in(" . SUSPENDED . "," . SUSPENDED_BY_ADMIN . ") then ( sp.end_user_price * sp.service_fee / 100 ) else 0 end)),2) AS sf_inactive_income
				  FROM
						service_provider sp
						INNER JOIN remotii r ON ( r.service_provider_id = sp.service_provider_id )
						LEFT OUTER JOIN user_remotii ur ON(ur.remotii_id = r.remotii_id)  
					WHERE
						sp.service_provider_id='$spid'";

        $dataPro = $this->adapter->query($query);
        $data = $dataPro->execute()->getResource()->fetch(\PDO::FETCH_OBJ);
        $enduserPriceTotal = $data->end_user_price;
        $SF_Expected = $data->servie_fee;
        $inactive_income = $data->inactive_income;
        $SF_Inactive = $data->sf_inactive_income;

        $expectedIncomeInNBC = $enduserPriceTotal - $SF_Expected;

        $return['NIserviceFee'] = $SF_Expected;
        $return['INACTserviceFee'] = $SF_Inactive;
        $return['netIncomeInPBC'] = $netIncomeInPBC;
        $return['expectedIncomeInNBC'] = $expectedIncomeInNBC;
        $return['inActiveIncome'] = $inactive_income - $SF_Inactive; //$inActiveIncome; 

        return $return;
    }

    public function getSPDelinquentClients($spid) {
        $query = "SELECT u.user_id, u.username, u.fname, u.lname FROM remotii r INNER JOIN user_remotii ur
    	ON( ur.remotii_id = r.remotii_id ) INNER JOIN user u ON( u.user_id = ur.user_id AND u.acc_status = '" . DELINQUENT . "' ) WHERE r.service_provider_id = '$spid'";
//exit;
        $dataPro = $this->adapter->query($query);

        return $return['delinquentClient'] = $dataPro->execute()->getResource()->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function getAdminSiteSummary() {
        $lastBillingCycle = strtotime(date('Y-m', strtotime('-1 month')));

        $query = "SELECT COUNT(*) FROM user u WHERE u.user_role_id = '3'";
        $dataPro = $this->adapter->query($query);
        $return['totalClient'] = $dataPro->execute()->getResource()->fetchColumn();

        $query = "SELECT COUNT(*) FROM service_provider";
        $dataPro = $this->adapter->query($query);
        $return['totalServiceProviders'] = $dataPro->execute()->getResource()->fetchColumn();


        $query = "SELECT TRUNCATE(IFNULL(SUM( ps.amount ),0),0) FROM
    				  payment_stats ps
	    		  WHERE
    				  ps.executed_on >= '" . $lastBillingCycle . "' AND
    				  ps.payment_status = " . SUCCESS;
        $dataPro = $this->adapter->query($query);
        $netIncomeInPBC = $dataPro->execute()->getResource()->fetchColumn();


        $query = "SELECT 
			  	SUM( sp.contracted_price ) AS contracted_price_total, 
				TRUNCATE(SUM( case when sp.allow_end_user_billing = 0 then 0 else (case when ur.user_id is null then 0 else ( sp.end_user_price * sp.service_fee / 100 ) end ) end), 2) AS servie_fee
			FROM
				service_provider sp
				INNER JOIN remotii r ON ( r.service_provider_id = sp.service_provider_id )
				LEFT OUTER JOIN user_remotii ur ON (r.remotii_id = ur.remotii_id)
			WHERE
				sp.acc_status <> " . SUSPENDED . " AND
				r.remotii_status <> " . SUSPENDED . " AND r.remotii_status <> '" . SUSPENDED_BY_ADMIN . "'";

        $dataPro = $this->adapter->query($query);
        $data = $dataPro->execute()->getResource()->fetch(\PDO::FETCH_OBJ);
        $contractedPriceTotal = $data->contracted_price_total;
        $SFNI = $data->servie_fee;


        $query = "SELECT 
					  	TRUNCATE(SUM( sp.contracted_price ),2) AS contracted_price_total, 
					  	TRUNCATE(SUM( case when sp.allow_end_user_billing = 0 then 0 else (case when ur.user_id is null then 0 else ( sp.end_user_price * sp.service_fee / 100 ) end ) end), 2) AS servie_fee
					FROM
						service_provider sp
						INNER JOIN remotii r ON ( r.service_provider_id = sp.service_provider_id )
    					LEFT OUTER JOIN user_remotii ur ON (r.remotii_id = ur.remotii_id)
					WHERE
						sp.acc_status = " . SUSPENDED . " AND
						( r.remotii_status = " . SUSPENDED . " OR r.remotii_status = '" . SUSPENDED_BY_ADMIN . "') ";

        $dataPro = $this->adapter->query($query);
        $data = $dataPro->execute()->getResource()->fetch(\PDO::FETCH_OBJ);
        $inactiveIncomeContractedPriceTotal = $data->contracted_price_total;
        $SFIA = $data->service_fee ? $data->service_fee : '0.00';
        $expectedIncomeInNBC = $contractedPriceTotal + $SFNI;

        if ($inactiveIncomeContractedPriceTotal) {
            $inActiveIncome = $inactiveIncomeContractedPriceTotal + $SFIA;
        } else {
            $inActiveIncome = 0.00;
        }

        $return['NIserviceFee'] = $SFNI;
        $return['INACTserviceFee'] = $SFIA;
        $return['netIncomeInPBC'] = $netIncomeInPBC;
        $return['expectedIncomeInNBC'] = $expectedIncomeInNBC;
        $return['inActiveIncome'] = $inActiveIncome;

        return $return;
    }

    public function getServiceProviderStatistics() {
        $query = "SELECT
	   				@spid := sp.service_provider_id AS spid, 
					sp.company_name, 
					sp.contracted_price,
					( SELECT COUNT(*)
						 FROM remotii r WHERE r.service_provider_id = @spid AND r.remotii_status = " . ACTIVE . "
					) AS total_active_remotiis,
					@inactive_remotiis := ( SELECT COUNT(*)
						 FROM remotii r WHERE r.service_provider_id = @spid AND ( r.remotii_status = " . SUSPENDED . " OR r.remotii_status = '" . SUSPENDED_BY_ADMIN . "' )
					) AS total_inactive_remotiis,
    				TRUNCATE(( sp.contracted_price *  @inactive_remotiis ), 2) AS inactive_income,
    				TRUNCATE(((sp.contracted_price * ( SELECT count( * ) FROM remotii r	WHERE r.service_provider_id = @spid AND r.remotii_status <> " . SUSPENDED . " AND r.remotii_status <> '" . SUSPENDED_BY_ADMIN . "')) 
						 	 + (IFNULL(sp.end_user_price, 0) * (select count(*) from remotii r INNER JOIN user_remotii ur ON (ur.remotii_id = r.remotii_id) 
					   			                                   WHERE r.service_provider_id= @spid AND r.remotii_status <> " . SUSPENDED . " AND r.remotii_status <> '" . SUSPENDED_BY_ADMIN . "'
								                                  ) * sp.service_fee * 1.0 / 100)
					),2) AS projected_amount
				  FROM 
					service_provider sp";
        $dataPro = $this->adapter->query($query);
        $sps = $dataPro->execute()->getResource()->fetchAll(\PDO::FETCH_ASSOC);

        return $sps;
    }

    /**
     * Method savePaymentProfileData defined
     * 
     * @param type $spId
     * @param type $profileId
     * @param type $paymentProfileId
     * @param type $shippingAddrsId
     * @return boolean
     */
    public function savePaymentProfileData($spId, $profileId, $paymentProfileId, $shippingAddrsId) {
        $queryAdd = "UPDATE service_provider SET authorizenet_profile_id = " . $this->platform->quoteValue($profileId) . ", 
            payment_profile_id = " . $this->platform->quoteValue($paymentProfileId) . ", 
            shipping_profile_id = " . $this->platform->quoteValue($shippingAddrsId) . " WHERE service_provider_id = '" . $spId . "'";
        $queryExeA = $this->adapter->query($queryAdd);
        $queryExeA->execute();
        return true;
    }

    /**
     * 
     * @param type $id
     * @return type
     */
    public function getAuthPaymentProfileDetails($id) {
        $query = "SELECT contact_fname, contact_email, authorizenet_profile_id, payment_profile_id, shipping_profile_id, company_name FROM service_provider WHERE service_provider_id = '" . $id . "'";
        $dataPro = $this->adapter->query($query);
        $results = $dataPro->execute()->getResource()->fetchAll(\PDO::FETCH_OBJ);
        return $results[0];
    }

    /**
     *
     * @param type $id
     * @return type
     */
    public function getEUPaymentProfileDetails($id) {
        $query = "SELECT eupp.authorizenet_profile_id, eupp.payment_profile_id, eupp.shipping_profile_id,
            eupp.accumulated_amount, eupp.next_billing_date,
            u.email as contact_email, u.fname as contact_fname FROM end_user_payment_profile eupp
            INNER JOIN user u ON (eupp.user_id = u.user_id)
            WHERE eupp.user_id = '" . $id . "'";
        $dataPro = $this->adapter->query($query);
        $result = $dataPro->execute()->getResource()->fetch(\PDO::FETCH_OBJ);
        return $result;
    }

    /**
     * 
     * @param type $transData
     * @param type $id
     * @param type $totalAmount
     * @return boolean
     */
    public function saveTransactionData($transData, $uid, $amount, $payment_source, $payment_flag, $userRemotiiId = null, $transDataMsg = null, $userType = 1, $credit_charge = null) {

        $payment_cycle = date('Ym');

        if ($transData->paid == 1) {
            $payment_status = 1;
        } else {
            $payment_status = 0;
        }

        if ($transData->status == 'paid' || $transData->status == 'pending') {
            $payment_status = 1;
        }
        $transclass = get_class($transData);

        if (get_class($transData) == "stdClass") {
            $tempid = $transData->id;
            $transData = json_encode($transData);
        }


        $Response = $transData;
        if ($transData == '') {
            $Response = $transDataMsg;
        }

        $executed_on = time();

        $query = "INSERT INTO payment_stats SET payment_cycle = " . $payment_cycle . ", user_id = " . $uid . ", 
            amount = " . $amount . ", payment_source = " . $this->platform->quoteValue($payment_source) . ", 
            payment_status = $payment_status, 
            executed_on = " . $executed_on . ", 
            payment_response = " . $this->platform->quoteValue($Response) . ", 
            payment_flag = " . $this->platform->quoteValue($payment_flag) . ", 
            user_remotii_id = " . $this->platform->quoteValue($userRemotiiId) . ", 
            trans_id = " . ($transclass == "stdClass" ? $this->platform->quoteValue($tempid) : $this->platform->quoteValue($transData->id) ) . ",
            user_type = " . $this->platform->quoteValue($userType) . " 
           " . ($credit_charge !== NULL ? " ,credit = '" . $credit_charge . "'  " : "");



        $queryExeA = $this->adapter->query($query);
        $queryExeA->execute();
        $lastInsertedId = $this->adapter->getDriver()->getLastGeneratedValue();
        return $lastInsertedId;
    }

    /**
     * 
     * @param type $transData
     * @param type $id
     * @param type $totalAmount
     * @return boolean
     */
    public function updateTransactionData($transData, $uid, $amount, $payment_source, $payment_flag, $modifyRemotiiId) {
//  get the transaction Id
        $queryTid = "SELECT ";

        $payment_cycle = date('Ym');
        if ($transData->paid == 1) {
            $payment_status = 1;
        } else {
            $payment_status = 0;
        }
        $executed_on = time();
        $query = "INSERT INTO payment_stats SET payment_cycle = " . $payment_cycle . ", user_id = " . $uid . ", 
            amount = " . $amount . ", payment_source = " . $this->platform->quoteValue($payment_source) . ", 
            payment_status = $payment_status, 
            executed_on = " . $executed_on . ", 
            payment_response = " . $this->platform->quoteValue($transData) . ", 
            payment_flag = " . $this->platform->quoteValue($payment_flag) . ", 
            trans_id = " . $this->platform->quoteValue($transData->id);

        $queryExeA = $this->adapter->query($query);
        $queryExeA->execute();
        $lastInsertedId = $this->adapter->getDriver()->getLastGeneratedValue();
        return $lastInsertedId;
    }

    /**
     * 
     * @param type $uid
     * @return boolean
     */
    public function updateAccStatus($uid, $payment_stat_id, $remaining_credit = NULL) {



        $queryAdd = "UPDATE service_provider SET acc_status = 1, 
                     last_payment_stat_id = '" . $payment_stat_id . "'
                    " . ($remaining_credit !== NULL ? " ,service_provider_credit = '" . $remaining_credit . "'  " : "") . "
                      WHERE service_provider_id = '" . $uid . "'";

        $queryExeA = $this->adapter->query($queryAdd);
        $queryExeA->execute();
        return true;
    }

    /**
     * 
     * @param type $uid
     * @return boolean
     */
    public function updateSPnextBillDate($uid, $nxtPaymentDate) {
        $queryAdd = "UPDATE service_provider SET 
                     next_billing_date = '" . $nxtPaymentDate . "' 
                      WHERE service_provider_id = '" . $uid . "'";
        $queryExeA = $this->adapter->query($queryAdd);
        $queryExeA->execute();
        return true;
    }

    /**
     * 
     * @param type $uid
     * @return boolean
     */
    public function updateSPlastPaymentStatus($uid, $payment_status) {
        $queryAdd = "UPDATE service_provider SET last_payment_status  = '" . $payment_status . "' 
                     WHERE service_provider_id = '" . $uid . "'";
        $queryExeA = $this->adapter->query($queryAdd);
        $queryExeA->execute();
        return true;
    }

    /**
     * 
     * @param type $uid
     * @return boolean
     */
    public function updateAmountToPayToSP($uid, $totalAmountToPayToSP, $spNxtPayDate) {
        $queryAdd = "UPDATE service_provider SET 
                     amount_payto_sp = amount_payto_sp + '" . $totalAmountToPayToSP . "', 
                     next_get_payment_date = '" . $spNxtPayDate . "' WHERE service_provider_id = '" . $uid . "'";
        $queryExeA = $this->adapter->query($queryAdd);
        $queryExeA->execute();
        return true;
    }

    /**
     * 
     * @param type $uid
     * @return boolean
     */
    public function saveSPpaybackInfo($spId, $paybackAmount, $nextPaybackDate) {
        $queryAdd = "INSERT INTO service_provider_payback SET service_provider_id = '" . $spId . "', 
                     payback_amount = '" . $paybackAmount . "', next_payback_date = '" . $nextPaybackDate . "', status = 0";
        $queryExeA = $this->adapter->query($queryAdd);
        $queryExeA->execute();
        return true;
    }

    /**
     * 
     * @param type $spid
     * @return boolean
     */
    public function clearSPAmountToPay($spid) {
        $queryAdd = "UPDATE service_provider SET amount_payto_sp = '' WHERE service_provider_id = '" . $spid . "'";
        $queryExeA = $this->adapter->query($queryAdd);
        $queryExeA->execute();
        return true;
    }

    /**
     * 
     * @param type $spid
     * @return boolean
     */
    public function updateSPAmountToPayDate($spid, $nxtDate) {
        $queryAdd = "UPDATE service_provider SET next_get_payment_date = '" . $nxtDate . "' WHERE service_provider_id = '" . $spid . "'";
        $queryExeA = $this->adapter->query($queryAdd);
        $queryExeA->execute();
        return true;
    }

    /**
     * 
     * @param type $spid
     * @return boolean
     */
    public function changePaybackPaymentStatusPaid($spPaybackSuccessIdStr) {
        $today = date('Y-m-d');
        $queryAdd = "UPDATE service_provider_payback SET status = '1', payment_date = '" . $today . "' WHERE payback_id IN (" . $spPaybackSuccessIdStr . ")";
        $queryExeA = $this->adapter->query($queryAdd);
        $queryExeA->execute();
        return true;
    }

    /**
     * 
     * @return type
     */
    public function OLD_payToSP_OLD() {
        $query = "SELECT * FROM service_provider sp
		  WHERE
			sp.acc_status <> " . SUSPENDED;
        $dataPro = $this->adapter->query($query);
        $amountDetails = $dataPro->execute()->getResource()->fetchAll(\PDO::FETCH_OBJ);
        return $amountDetails;
    }

    /**
     * 
     * @return type
     */
    public function payToSP() {
        $today = date('Y-m-d');
        $query = "SELECT sp.service_provider_id, sprp.stripe_acc_id, spb.payback_amount, 
                      spb.next_payback_date, spb.status, spb.payback_id, sp.contact_fname, sp.contact_email,sp.company_name
                  FROM service_provider sp 
                      INNER JOIN service_provider_receiving_payment_details sprp ON (sp.service_provider_id = sprp.service_provider_id) 
                      INNER JOIN service_provider_payback spb ON (spb.service_provider_id = sprp.service_provider_id) 
                  WHERE 
                    sp.acc_status <> " . SUSPENDED . " AND spb.status = 0 
                    AND spb.next_payback_date <= '" . $today . "'
                  ";
        $dataPro = $this->adapter->query($query);
        $amountDetails = $dataPro->execute()->getResource()->fetchAll(\PDO::FETCH_OBJ);
        return $amountDetails;
    }

    /**
     * 
     * @param type $uid
     * @return boolean
     */
    public function updateAccStatusEU($userRemotiiId, $payment_stat_id, $euNextBillingDate) {
        $queryAdd = "UPDATE  end_user_payment_profile SET last_payment_stat_id = '" . $payment_stat_id . "', 
                     next_billing_date = '" . $euNextBillingDate . "' WHERE user_remotii_id = '" . $userRemotiiId . "'";
        $queryExeA = $this->adapter->query($queryAdd);
        $queryExeA->execute();
        return true;
    }

    public function updateAccStatusEUWithoutNxtBillingDate($userRemotiiId, $payment_stat_id) {
        $queryAdd = "UPDATE  end_user_payment_profile SET last_payment_stat_id = '" . $payment_stat_id . "' 
                     WHERE user_remotii_id = '" . $userRemotiiId . "'";
        $queryExeA = $this->adapter->query($queryAdd);
        $queryExeA->execute();
        return true;
    }

    /**
     * 
     * @param type $uid
     * @return boolean
     */
    public function updateUserRemotiiPaymentStatus($userRemotiiId, $payment_status) {
        $queryAdd = "UPDATE  user_remotii SET payment_status = '" . $payment_status . "' WHERE user_remotii_id = '" . $userRemotiiId . "'";
        $queryExeA = $this->adapter->query($queryAdd);
        $queryExeA->execute();
        return true;
    }

    /**
     * 
     * @param type $uid
     * @return boolean
     */
    public function updatePaymentStateAddEUremotiiId($userRemotiiId, $paymentId) {
        $queryAdd = "UPDATE  payment_stats SET user_remotii_id = '" . $userRemotiiId . "' WHERE payment_id = '" . $paymentId . "'";
        $queryExeA = $this->adapter->query($queryAdd);
        $queryExeA->execute();
        return true;
    }

    /**
     *
     * @param type $uid
     * @return boolean
     */
    public function activeEndUserAccStatus($uid) {
        $queryAdd = "UPDATE user SET acc_status = 1 WHERE user_id = '" . $uid . "'";
        $queryExeA = $this->adapter->query($queryAdd);
        $queryExeA->execute();
        return true;
    }

    public function updateSPLastPaymentStatId($spid, $payment_stat_id) {
        $queryAdd = "UPDATE service_provider SET last_payment_stat_id = '" . $payment_stat_id . "' WHERE service_provider_id = '" . $spid . "'";
        $queryExeA = $this->adapter->query($queryAdd);
        $queryExeA->execute();
        return true;
    }

    public function updateEULastPaymentStatId($uid, $payment_stat_id) {
        $queryAdd = "UPDATE end_user_payment_profile SET last_payment_stat_id = '" . $payment_stat_id . "' WHERE user_id = '" . $uid . "'";
        $queryExeA = $this->adapter->query($queryAdd);
        $queryExeA->execute();
        return true;
    }

    public function updateEULastPaymentStatIdByRemotiiId($userRemotiiId, $payment_stat_id) {
        $queryAdd = "UPDATE end_user_payment_profile SET last_payment_stat_id = '" . $payment_stat_id . "' WHERE user_remotii_id = '" . $userRemotiiId . "'";
        $queryExeA = $this->adapter->query($queryAdd);
        $queryExeA->execute();
        return true;
    }

    public function getSpsAmountDetails() {
        $toDay = date('Y-m-d');
        $query = "SELECT
	    		sp.service_provider_id,
			  	SUM( sp.contracted_price ) AS contracted_price_total,service_provider_credit,
                                sp.next_billing_date, sp.contact_fname, sp.contact_email, accumulated_amount, 
                                DATEDIFF('" . $toDay . "', ifnull(sp.next_billing_date,'" . $toDay . "')) + 1 as try_count,
                                sp.company_name
			FROM 
				service_provider sp 
				INNER JOIN remotii r ON ( r.service_provider_id = sp.service_provider_id ) 
			WHERE 
				sp.acc_status <> " . SUSPENDED . " AND 
				r.remotii_status <> " . SUSPENDED . " AND r.remotii_status <> '" . SUSPENDED_BY_ADMIN . "' 
                                AND ( sp.next_billing_date = '" . $toDay . "' OR sp.last_payment_status = 0 OR sp.last_payment_status IS NULL ) 
                                AND ( DATEDIFF('" . $toDay . "', ifnull(sp.next_billing_date,'" . $toDay . "')) < " . NUMBER_OF_TRIAL . " ) 
                        GROUP BY 
                                sp.service_provider_id, sp.next_billing_date, 
                                sp.contact_fname, sp.contact_email, sp.accumulated_amount, sp.company_name
                        ORDER BY sp.service_provider_id DESC
                         ";

        $dataPro = $this->adapter->query($query);
        $amountDetails = $dataPro->execute()->getResource()->fetchAll(\PDO::FETCH_OBJ);
        return $amountDetails;
    }

    /**
     * 
     * @return type
     */
    public function getAmountPaidToSP($spIds) {
        $query = "SELECT 
                    sp.service_provider_id, sprp.stripe_acc_id 
			FROM 
                            service_provider sp 
                            INNER JOIN remotii r ON ( r.service_provider_id = sp.service_provider_id ) 
                            INNER JOIN service_provider_receiving_payment_details sprp ON (sp.service_provider_id = sprp.service_provider_id) 
			WHERE 
                            sp.acc_status <> " . SUSPENDED . " AND 
                            r.remotii_status <> " . SUSPENDED . " AND r.remotii_status <> '" . SUSPENDED_BY_ADMIN . "' AND 
                            sprp.stripe_acc_id IS NOT NULL AND 
                            sp.service_provider_id IN ($spIds)
                            ";

        $dataPro = $this->adapter->query($query);
        $amountDetails = $dataPro->execute()->getResource()->fetchAll(\PDO::FETCH_OBJ);
        return $amountDetails;
    }

    /**
     * 
     * @return type
     */
    public function chargeToEndUser() {
        $toDay = date('Y-m-d');

        $qry = "SELECT 
                    sp.end_user_price as amount, 
                    eup.authorizenet_profile_id as pid, 
                    eup.next_billing_date as nxtBillingDate, 
                    u.user_id, 
                    u.email, 
                    u.fname, 
                    sp.service_fee, 
                    sp.service_provider_id, 
                    ur.user_remotii_id as userRemotiiId, 
                    ur.remotii_name as remotiiName,
                    sp.contact_fname as spFname,
                    sp.contact_lname as spLname,
                    DATEDIFF('" . $toDay . "', ifnull(eup.next_billing_date, '" . $toDay . "')) + 1 as try_count 
                    FROM end_user_payment_profile eup 
                    INNER JOIN user_remotii ur ON ( ur.user_remotii_id = eup.user_remotii_id ) 
                    INNER JOIN user u ON (u.user_id = ur.user_id) 
                    
                    INNER JOIN remotii r ON ( r.remotii_id = ur.remotii_id ) 
                    INNER JOIN service_provider sp ON ( sp.service_provider_id = r.service_provider_id ) 
                WHERE u.user_role_id = 3 AND 
                      r.remotii_status <> " . SUSPENDED . " AND 
                      r.remotii_status <> '" . SUSPENDED_BY_ADMIN . "' AND 
                      sp.allow_end_user_billing = 1 AND 
                      u.acc_status <> " . SUSPENDED . " 
                      AND ( eup.next_billing_date = '" . $toDay . "' OR ur.payment_status = 0 ) 
                      AND ( DATEDIFF('" . $toDay . "', ifnull(eup.next_billing_date, '" . $toDay . "')) < " . NUMBER_OF_TRIAL . " ) 
                ";

        /*
          $qry = "SELECT sp.end_user_price as amount,
          eup.authorizenet_profile_id as pid,
          u.user_id,
          TRUNCATE (CASE WHEN sp.allow_end_user_billing = 1
          THEN (sp.end_user_price - ( sp.end_user_price * sp.service_fee /100 ) )
          END, 2) AS amount_to_pay,
          sp.service_provider_id
          FROM user u
          INNER JOIN user_remotii ur ON ( u.user_id = ur.user_id )
          INNER JOIN end_user_payment_profile eup ON ( eup.user_id = u.user_id )
          INNER JOIN remotii r ON ( r.remotii_id = ur.remotii_id )
          INNER JOIN service_provider sp ON ( sp.service_provider_id = r.service_provider_id )
          WHERE u.user_role_id = 3 AND
          r.remotii_status <> ".SUSPENDED." AND r.remotii_status <> '".SUSPENDED_BY_ADMIN."' AND
          u.acc_status <> ".SUSPENDED;
         */

        /*
          $qry = "SELECT TRUNCATE( SUM( sp.end_user_price ) , 2 ) AS amount, eup.authorizenet_profile_id AS pid, u.user_id,
          TRUNCATE( SUM( CASE WHEN sp.allow_end_user_billing = 0
          THEN (sp.end_user_price)
          ELSE (sp.end_user_price - ( sp.end_user_price * sp.service_fee /100 ) ) END ) , 2) AS amount_to_pay,
          sp.service_provider_id
          FROM user u
          INNER JOIN user_remotii ur ON ( u.user_id = ur.user_id )
          INNER JOIN end_user_payment_profile eup ON ( eup.user_id = u.user_id )
          INNER JOIN remotii r ON ( r.remotii_id = ur.remotii_id )
          INNER JOIN service_provider sp ON ( sp.service_provider_id = r.service_provider_id )
          WHERE u.user_role_id = 3
          AND r.remotii_status <>  ".SUSPENDED."
          AND u.acc_status <>  ".SUSPENDED."
          GROUP BY u.user_id";
         */


        $dataPro = $this->adapter->query($qry);
        $amountDetails = $dataPro->execute()->getResource()->fetchAll(\PDO::FETCH_OBJ);
        return $amountDetails;
    }

    public function updateAccStatusToDelinquent($spid) {
        $queryAdd = "UPDATE service_provider SET acc_status = '" . DELINQUENT . "' WHERE service_provider_id = '" . $spid . "'";
        $queryExeA = $this->adapter->query($queryAdd);
        $queryExeA->execute();
        return true;
    }

    public function addSPAccumulatedAmount($spid, $amount) {
        $amount = $this->platform->quoteValue($amount);
        $queryAdd = "UPDATE service_provider SET accumulated_amount = accumulated_amount + $amount WHERE service_provider_id = '" . $spid . "'";
        $queryExeA = $this->adapter->query($queryAdd);
        $queryExeA->execute();
        return true;
    }

    public function getSPAccumulatedAmount($spid) {
        $queryAdd = "select accumulated_amount from service_provider WHERE service_provider_id = '" . $spid . "'";
        $accumulatedAmount = $this->adapter->query($queryAdd)->execute()->getResource()->fetchColumn();
        return $accumulatedAmount;
    }

    public function clearSPAccumulatedAmount($spid) {
        $queryAdd = "UPDATE service_provider SET accumulated_amount = '' WHERE service_provider_id = '" . $spid . "'";
        $queryExeA = $this->adapter->query($queryAdd);
        $queryExeA->execute();
        return true;
    }

    public function addEndUserAccumulatedAmount($userRemotiiId, $amount) {
        $amount = $this->platform->quoteValue($amount);
        $queryAdd = "UPDATE end_user_payment_profile SET accumulated_amount = accumulated_amount + $amount WHERE user_remotii_id = '" . $userRemotiiId . "'";
        $queryExeA = $this->adapter->query($queryAdd);
        $queryExeA->execute();
        return true;
    }

    public function getEndUserAccumulatedAmount($userId) {
        $queryAdd = "select accumulated_amount from end_user_payment_profile WHERE user_id = '" . $userId . "'";
        $accumulatedAmount = $this->adapter->query($queryAdd)->execute()->getResource()->fetchColumn();
        return $accumulatedAmount;
    }

    /**
     * 
     * @param type $userId
     * @return type
     */
    public function getEndUserAccountInfo($userId) {
        $queryGet = "SELECT email, fname from user WHERE user_id = '" . $userId . "'";
        $dataPro = $this->adapter->query($queryGet);
        $accDetails = $dataPro->execute()->getResource()->fetchAll(\PDO::FETCH_OBJ);
        return $accDetails[0];
    }

    public function clearEndUserAccumulatedAmount($userRmId) {
        $queryAdd = "UPDATE end_user_payment_profile SET accumulated_amount = '' WHERE  user_remotii_id = '" . $userRmId . "'";
        $queryExeA = $this->adapter->query($queryAdd);
        $queryExeA->execute();
        return true;
    }

    /**
     * 
     * @param type $user_id
     * @return boolean
     */
    public function updateUserAccStatusToDelinquent($user_id) {
        $queryAdd = "UPDATE user SET acc_status = '" . DELINQUENT . "' WHERE user_id = '" . $user_id . "'";
        $queryExeA = $this->adapter->query($queryAdd);
        $queryExeA->execute();
        return true;
    }

    /**
     * 
     * @param type $id
     * @param type $postData
     * @return boolean
     */
    public function changeSPaccStatus($id, $accountStatus) {
        $queryAdd = "UPDATE service_provider SET acc_status = '" . $accountStatus . "' WHERE service_provider_id = '" . $id . "'";
        $queryExe = $this->adapter->query($queryAdd);
        $queryExe->execute();

        $queryAdd = "UPDATE user SET acc_status = '" . $accountStatus . "' WHERE user_id IN(
        				select user_id from service_provider_admins where service_provider_id='$id')";
        $queryExe = $this->adapter->query($queryAdd);
        $queryExe->execute();

        $remotiiStatus = $accountStatus == SUSPENDED ? SUSPENDED_BY_ADMIN : ACTIVE;
        $queryAdd = "UPDATE remotii SET remotii_status = '" . $remotiiStatus . "' WHERE service_provider_id = '" . $id . "'";
        $queryExeA = $this->adapter->query($queryAdd);
        $queryExeA->execute();
        return true;
    }

    public function companyNameExists($companyName) {
        $companyName = $this->platform->quoteValue($companyName);
        $res = $this->adapter->query("select count(*) from service_provider where company_name=$companyName");
        $count = $res->execute()->getResource()->fetchColumn();
        return $count ? true : false;
    }

    /**
     * 
     * @param type $companyName
     * @param type $id
     * @return type
     */
    public function companyNameExistsChk($companyName, $id) {
        $query = "SELECT service_provider_id FROM service_provider WHERE company_name = " . $this->platform->quoteValue($companyName) . "";
        if ($id <> '') {
            $query .= " AND service_provider_id != '" . $id . "'";
        }
        $dataPro = $this->adapter->query($query);
        $results = $dataPro->execute()->getResource()->fetchAll(\PDO::FETCH_OBJ);
        return $results[0]->service_provider_id;
    }

    /**
     * 
     * @return type
     */
    public function getServiceProviderListById($spid) {
        $query = "SELECT
                       @spid := sp.service_provider_id AS spid,
                    sp.company_name,
                    sp.contracted_price,
                    sp.end_user_price,
                    sp.allow_end_user_billing,
                    sp.service_fee,
                    sp.acc_created_on,
                    ( SELECT COUNT(*)
                         FROM 
                              remotii r WHERE (r.service_provider_id = @spid AND r.remotii_status = " . ACTIVE . ")
                    ) AS total_active_remotiis,
                    @inactive_remotiis := ( SELECT COUNT(*)
                         FROM 
                              remotii r WHERE (r.service_provider_id = @spid AND ( r.remotii_status = " . SUSPENDED . " OR r.remotii_status = '" . SUSPENDED_BY_ADMIN . "') )
                    ) AS total_inactive_remotiis,
                    ps.payment_status as paymentstatus,
                    IFNULL(ps.executed_on,'N/A') as executedon,
                    ps.amount,
                    sp.acc_status
                  FROM
                    service_provider sp left outer join payment_stats ps on (sp.last_payment_stat_id = ps.payment_id)
                    WHERE sp.service_provider_id = '" . $spid . "'
        ";
        $dataPro = $this->adapter->query($query);
        $results = $dataPro->execute()->getResource()->fetchAll(\PDO::FETCH_OBJ);
        return $results;
    }

    public function remotiValidateChk($macId) {
        $qry = "SELECT remotii_id FROM remotii WHERE mac_address = '" . trim($macId) . "'";
        $dataPro = $this->adapter->query($qry);
        $results = $dataPro->execute()->getResource()->fetchAll(\PDO::FETCH_OBJ);
        return $results[0]->remotii_id;
    }

    /**
     * 
     * @param type $macID
     */
    public function saveRemotiiMac($macID, $spid, $loggedInId) {
        $updatedOn = time();
        $count = count($macID);
        for ($i = 0; $i < $count; $i++) {
            $qry = "INSERT INTO remotii SET mac_address = '" . strtoupper($macID[$i]) . "', service_provider_id = '" . $spid . "', 
                remotii_status = '1', updated_by = '" . $loggedInId . "', updated_on = '" . $updatedOn . "'";

            $queryExeA = $this->adapter->query($qry);
            $queryExeA->execute();
            $lastInsertedId = $this->adapter->getDriver()->getLastGeneratedValue();
//return $lastInsertedId;
        }
        return 1;
    }

    public function saveRemotiiMacForRSP($macID, $spid, $loggedInId) {
        $updatedOn = time();

        $qry = "INSERT INTO remotii SET mac_address = '" . strtoupper($macID) . "', service_provider_id = '" . $spid . "', 
                remotii_status = '1', updated_by = '" . $loggedInId . "', updated_on = '" . $updatedOn . "',remotii_last_input_status = '0',remotii_last_output_status = '0', remotii_last_received_time = '".$updatedOn."',notification_email = '' ";
        $queryExeA = $this->adapter->query($qry);
        $queryExeA->execute();
        $lastInsertedId = $this->adapter->getDriver()->getLastGeneratedValue();
        return $lastInsertedId;
    }

    /**


     * 
     * @param type $spid
     * @return type
     */
    public function getSPRemotiis($spid) {
        // $qry = "SELECT r.mac_address,r.remotii_id,r.remotii_status, ur.*, ifnull((select username from user where user_id = ur.user_id),'') as uname ,
        //     GREATEST(IFNULL(r.remotii_last_heartbeat_received_time,0),r.remotii_last_received_time) as remotii_last_received_time,
        //     u.user_role_id as user_role
        //     from remotii r left outer join user_remotii ur on r.remotii_id = ur.remotii_id
        //     left outer join user u on u.user_id = ur.user_id
        //     where r.service_provider_id ='" . $spid . "'";
        $qry = "SELECT r.mac_address,r.remotii_id,r.remotii_status,ur.remotii_name, ifnull((select username from user where user_id = ur.user_id),'') as uname ,
            GREATEST(IFNULL(r.remotii_last_heartbeat_received_time,0),r.remotii_last_received_time) as remotii_last_received_time,
            u.user_role_id as user_role
            from remotii r left outer join user_remotii ur on r.remotii_id = ur.remotii_id
            left outer join user u on u.user_id = ur.user_id
            where r.service_provider_id ='" . $spid . "'";
        $dataPro = $this->adapter->query($qry);
        $results = $dataPro->execute()->getResource()->fetchAll(\PDO::FETCH_OBJ);
        return $results;
    }

    /**
     * @param type $spid
     * @return type
     */
    public function getOfflineSPRemotiis($spid) {
        $qry = "SELECT z.abbr,r.mac_address,r.remotii_id,r.remotii_status, ur.*, ifnull((select username from user where user_id = ur.user_id),'') as uname ,
            GREATEST(IFNULL(r.remotii_last_heartbeat_received_time,0),r.remotii_last_received_time) as remotii_last_received_time,r.local_ip_address,r.last_offline_detection_timestamp,
            u.user_role_id as user_role
            from remotii r left outer join user_remotii ur on r.remotii_id = ur.remotii_id
            left outer join user u on u.user_id = ur.user_id
            left join user_remotii usr on usr.remotii_id = r.remotii_id
            left join zone_gmt z on usr.zone_id = z.zone_id
            where r.service_provider_id ='" . $spid . "' AND r.last_offline_detection_timestamp > 0 AND enable_rsp_offline_notifications=1";
        $dataPro = $this->adapter->query($qry);
        $results = $dataPro->execute()->getResource()->fetchAll(\PDO::FETCH_ASSOC);
        return $results;
    }

    /**
     * 
     * @param type $spid
     * @return type
     */
    public function searchSPRemotiis($spid, $searchItem) {
        $qry = "SELECT r.mac_address,r.remotii_id, ur.*, ifnull((select username from user where user_id = ur.user_id),'') as uname 
            from remotii r left outer join user_remotii ur on r.remotii_id = ur.remotii_id
            left join user u on u.user_id = ur.user_id 
            where r.service_provider_id ='" . $spid . "' AND ( r.mac_address LIKE '%" . $searchItem . "%' OR u.username LIKE '%" . $searchItem . "%' ) ";
        $dataPro = $this->adapter->query($qry);
        $results = $dataPro->execute()->getResource()->fetchAll(\PDO::FETCH_OBJ);
        return $results;
    }

    public function getPayments($params = "") {
        if (!empty($params)) {
            $fromDate = $params['fromDate'];
            $toDate = $params['toDate'];
//    		$query = "select * from payment_stats ps
//    				INNER JOIN user u ON ( ps.user_id = u.user_id AND u.user_role_id = 2 )
//    				where ps.executed_on >= '$fromDate' AND ps.payment_cycle <= '$toDate'";
            $query = "select ps.*,sp.company_name as user_name from payment_stats ps
    				INNER JOIN service_provider sp ON ( ps.user_id = sp.service_provider_id  AND ps.user_type = 1)
                                
    				where ps.executed_on >= '$fromDate' AND ps.payment_cycle <= '$toDate'
                    UNION 
                    select ps.*,us.username as user_name from payment_stats ps
    				INNER JOIN user us ON ( ps.user_id = us.user_id  AND ps.user_type = 2 AND us.user_role_id = 3)
                                
    				where ps.executed_on >= '$fromDate' AND ps.payment_cycle <= '$toDate'
                    
                    ";

            $dataPro = $this->adapter->query($query);
            $results = $dataPro->execute()->getResource()->fetchAll(\PDO::FETCH_OBJ);
        }

        return $results;
    }

    /**
     * 
     * @param type $spId
     * @return type
     */
    public function getSPPayment($spId) {
        $query = "SELECT * FROM payment_stats ps 
                        INNER JOIN service_provider sp ON ( ps.user_id = sp.service_provider_id) 
                        WHERE ps.user_id = '" . $spId . "' 
                        ORDER BY ps.payment_id DESC";

        $dataPro = $this->adapter->query($query);
        $results = $dataPro->execute()->getResource()->fetchAll(\PDO::FETCH_OBJ);
        return $results;
    }

    public function getRemotiiChainedEvent($rid) {
        $q = "select ce.*,sr.remotii_name as source,dst.remotii_name as destination from chained_event ce "
                . "left join user_remotii sr on sr.remotii_id=ce.source_remotii "
                . "left join user_remotii dst on dst.remotii_id=ce.destination_remotii "
                . "where ce.source_remotii ='$rid' OR ce.destination_remotii='$rid' ORDER BY ce.ce_id DESC";
        $stmt = $this->adapter->query($q);
        $results = $stmt->execute()->getResource()->fetchAll(\PDO::FETCH_ASSOC);
        return $results;
    }

    public function getRemotiiData($rid) {
        $q = "select r.mac_address,r.notification_email,ur.remotii_name from remotii r "
                . "left join user_remotii ur on r.remotii_id=ur.remotii_id "
                . "where r.remotii_id='$rid'";
        $stmt = $this->adapter->query($q);
        $results = $stmt->execute()->getResource()->fetch();
        return $results;
    }

    /**
     * 
     * @return boolean
     */
    public function deleteRemotii($delId) {
        $query = "SELECT user_remotii_id FROM user_remotii WHERE remotii_id = '" . $delId . "'";
        $dataPro = $this->adapter->query($query);
        $idURI = $dataPro->execute()->getResource()->fetchColumn();

        $this->adapter->getDriver()->getConnection()->beginTransaction();
        try {
//  Delete the event of this remotii
            $q = "DELETE FROM event_scheduler WHERE remotii_id = '" . $delId . "'";
            $queryE = $this->adapter->query($q);
            $queryE->execute();

//  Delete the config of this remotii
            $q = "DELETE FROM user_remotii_input_config WHERE user_remotii_id = '" . $idURI . "'";
            $queryE = $this->adapter->query($q);
            $queryE->execute();

//  Delete the config of this remotii
            $q = "DELETE FROM user_remotii_output_config WHERE user_remotii_id = '" . $idURI . "'";
            $queryE = $this->adapter->query($q);
            $queryE->execute();

//  Delete the user remotii
            $q = "DELETE FROM user_remotii WHERE remotii_id = '" . $delId . "'";
            $queryE = $this->adapter->query($q);
            $queryE->execute();

//  Get the remotii macAddress  Date:27-12-2013
            $query = "SELECT mac_address FROM remotii WHERE remotii_id = '" . $delId . "'";
            $dataRmac = $this->adapter->query($query);
            $rMacAddrs = $dataRmac->execute()->getResource()->fetchColumn();

//  Delete Remotii from inbound
            $qI = "DELETE FROM inbound WHERE mac_address = '" . $rMacAddrs . "'";
            $queryI = $this->adapter->query($qI);
            $queryI->execute();

//  Delete Remotii from outbound
            $qO = "DELETE FROM outbound WHERE mac_address = '" . $rMacAddrs . "'";
            $queryO = $this->adapter->query($qO);
            $queryO->execute();

//  Delete the notification email of this remotii
            $q = "DELETE FROM notification_email WHERE mac_address = '" . $rMacAddrs . "'";
            $queryE = $this->adapter->query($q);
            $queryE->execute();

//  Delete the notification email of this remotii
            $q = "DELETE FROM shared_remotii WHERE user_remotii_id = '" . $idURI . "'";
            $queryE = $this->adapter->query($q);
            $queryE->execute();

//  Delete the remotii
            $q = "DELETE FROM remotii WHERE remotii_id = '" . $delId . "'";
            $queryE = $this->adapter->query($q);
            $queryE->execute();

//  Delete chained event
            $q = "DELETE FROM chained_event WHERE source_remotii = '" . $delId . "' OR destination_remotii='" . $delId . "'";
            $queryE = $this->adapter->query($q);
            $queryE->execute();


            $this->adapter->getDriver()->getConnection()->commit();
        } catch (Exception $e) {
            $this->adapter->getDriver()->getConnection()->rollback();
        }

        return true;
    }

    public function clearRemotiiToken($id) {
        $queryAdd = "UPDATE remotii SET token =  0 WHERE remotii_id = '" . $id . "'";
        $queryExeA = $this->adapter->query($queryAdd);
        $queryExeA->execute();
        return true;
    }

    public function suspendRemotii($remotiiId) {
//        $query = "SELECT user_remotii_id FROM user_remotii WHERE remotii_id = '" . $delId . "'";
//        $dataPro = $this->adapter->query($query);
//        $idURI = $dataPro->execute()->getResource()->fetchColumn();

        $this->adapter->getDriver()->getConnection()->beginTransaction();
        try {

//  Delete the remotii
            $q = "update remotii set remotii_status =" . SUSPENDED_BY_ADMIN . " WHERE remotii_id = '" . $remotiiId . "'";
            $queryE = $this->adapter->query($q);
            $queryE->execute();

            $this->adapter->getDriver()->getConnection()->commit();
        } catch (Exception $e) {
            $this->adapter->getDriver()->getConnection()->rollback();
        }

        return true;
    }

    public function activateRemotii($remotiiId) {
//        $query = "SELECT user_remotii_id FROM user_remotii WHERE remotii_id = '" . $delId . "'";
//        $dataPro = $this->adapter->query($query);
//        $idURI = $dataPro->execute()->getResource()->fetchColumn();

        $this->adapter->getDriver()->getConnection()->beginTransaction();
        try {

//  Delete the remotii
            $q = "update remotii set remotii_status =" . ACTIVE . " WHERE remotii_id = '" . $remotiiId . "'";
            $queryE = $this->adapter->query($q);
            $queryE->execute();

            $this->adapter->getDriver()->getConnection()->commit();
        } catch (Exception $e) {
            $this->adapter->getDriver()->getConnection()->rollback();
        }

        return true;
    }

    /**
     * 
     * @return type
     */
    public function getRemotiisList() {
//  ifnull((select max(receive_time) from inbound group by mac_address  where mac_address=r.mac_address),'') as time
//        $qry = "SELECT r.mac_address,r.hwfwver, r.remotii_id, ur.user_id, r.service_provider_id, r.remotii_status,
//             ifnull((select username from user where user_id = ur.user_id),'') as uname, 
//             ifnull((select company_name from service_provider where service_provider_id = r.service_provider_id),'') as cname
//            
//             from remotii r left outer join user_remotii ur on r.remotii_id = ur.remotii_id 
//             where 1";
        $qry = "SELECT r.mac_address,r.hwfwver,r.remotii_id,ur.user_id,r.service_provider_id,r.remotii_status,us.username,sp.company_name,ur.offset,ur.day_light_saving,ur.remotii_name,GREATEST(IFNULL(r.remotii_last_heartbeat_received_time,0),r.remotii_last_received_time) as max_recieve_time
                ,r.admin_notes,
                us.user_role_id as user_role
                from remotii as r
                LEFT JOIN service_provider as sp on (sp.service_provider_id = r.service_provider_id)
		        LEFT JOIN user_remotii as ur on (r.remotii_id = ur.remotii_id )
                LEFT JOIN user as us on (us.user_id = ur.user_id)
		";
        $dataPro = $this->adapter->query($qry);
        $results = $dataPro->execute()->getResource()->fetchAll(\PDO::FETCH_ASSOC);
        return $results;
    }

    /**
     * 
     * @return type
     */
    public function getOfflineRemotiisList() {
        $qry = "SELECT z.abbr,r.mac_address,r.hwfwver,r.remotii_id,ur.user_id,r.service_provider_id,r.remotii_status,us.username,sp.company_name,ur.offset,ur.day_light_saving,ur.remotii_name,ur.user_remotii_id,GREATEST(IFNULL(r.remotii_last_heartbeat_received_time,0),r.remotii_last_received_time) as max_recieve_time
                ,r.admin_notes,r.last_offline_detection_timestamp,r.local_ip_address,
                us.user_role_id as user_role
                from remotii as r
                LEFT JOIN service_provider as sp on (sp.service_provider_id = r.service_provider_id)
		LEFT JOIN user_remotii as ur on (r.remotii_id = ur.remotii_id )
                LEFT JOIN zone_gmt z on ur.zone_id = z.zone_id
                LEFT JOIN user as us on (us.user_id = ur.user_id)
                WHERE r.last_offline_detection_timestamp > 0";
        $dataPro = $this->adapter->query($qry);
        $results = $dataPro->execute()->getResource()->fetchAll(\PDO::FETCH_ASSOC);
        return $results;
    }

    /**
     * 
     * @param type $endUid
     * @return string
     */
    public function getEndUserRemotii($endUid) {
        $qry = "SELECT r.mac_address, r.remotii_id, ur.user_id, r.service_provider_id, r.remotii_status, 
             ifnull((select company_name from service_provider where service_provider_id = r.service_provider_id),'') as cname, 
             ifnull((select end_user_price from service_provider where service_provider_id = r.service_provider_id),'') as enduPrice, 
             ifnull((select acc_status from service_provider where service_provider_id = r.service_provider_id),'') as acc_status  
             from remotii r left outer join user_remotii ur on r.remotii_id = ur.remotii_id 
             where ur.user_id = '" . $endUid . "'";

        $dataPro = $this->adapter->query($qry);
        $results = $dataPro->execute()->getResource()->fetchAll(\PDO::FETCH_ASSOC);
        return $results;
    }

    public function getClientRemtiisTotalPrice($clientId) {
        $qry = "SELECT SUM( sp.end_user_price ) 
					FROM
					user_remotii ur INNER JOIN remotii r ON ( r.remotii_id = ur.remotii_id )
					  LEFT JOIN service_provider sp ON ( sp.service_provider_id = r.service_provider_id )
					WHERE ur.user_id = '$clientId'";

        $dataPro = $this->adapter->query($qry);
        $result = $dataPro->execute()->getResource()->fetchColumn();
        return $result;
    }

    /**
     * 
     * @param type $spId
     * @param type $amount
     * @return type
     */
    public function changeContractedPrice($spId, $amount) {
        $queryAdd = "UPDATE service_provider SET 
                        contracted_price = " . $this->platform->quoteValue($amount);
        $queryAdd .= " WHERE service_provider_id = '" . $spId . "' ";

        $queryExeA = $this->adapter->query($queryAdd);
        $queryExeA->execute();
        return;
    }

    /**
     * 
     * @param type $status
     * @param type $rmIds
     * @return type
     */
    public function changeRemotiiStatus($status, $rmIds) {
        $nStr = rtrim($rmIds, ",");
        $queryAdd = "UPDATE remotii SET 
                        remotii_status = " . $this->platform->quoteValue($status);
        $queryAdd .= " WHERE remotii_id IN (" . $nStr . ") ";

        $queryExeA = $this->adapter->query($queryAdd);
        $queryExeA->execute();
        return;
    }

    public function getSPClients($spid) {
        $q = "SELECT * FROM remotii r INNER JOIN user_remotii ur ON( r.remotii_id = ur.remotii_id ) 
    			INNER JOIN user u ON( u.user_id = ur.user_id )
    			WHERE r.service_provider_id = '$spid' GROUP BY u.user_id";

        $dataPro = $this->adapter->query($q);
        $results = $dataPro->execute()->getResource()->fetchAll(\PDO::FETCH_OBJ);
        return $results;
    }

    public function getMailNotificationData() {
        $q = "SELECT * FROM notification_email WHERE flag = 0 AND ( email IS NOT NULL AND email != '' ) ORDER BY nid ASC";
        $dataPro = $this->adapter->query($q);
        $results = $dataPro->execute()->getResource()->fetchAll(\PDO::FETCH_OBJ);
        return $results;
    }

    public function getOfflineMailNotificationData() {
        $q = "SELECT * FROM offline_notification_email WHERE status = 0 AND ( email IS NOT NULL AND email != '' ) ORDER BY id ASC";
        $dataPro = $this->adapter->query($q);
        $results = $dataPro->execute()->getResource()->fetchAll(\PDO::FETCH_ASSOC);
        return $results;
    }

    public function getEventSchedulerData() {
        $day = date('D');
        $date = date("m/d/Y");
        $date1 = date('d', strtotime($date));
        $month = date('m', strtotime($date));
        $year = date('Y', strtotime($date));
        $time = date('h:i A');

        $q = ("SELECT CONV(es.input_bits, 10, 2),es.dout_set,es.dout_clr,es.mac_address,es.remotii_id,CONV(r.remotii_last_input_status, 10, 2)from event_scheduler as es INNER JOIN remotii as r
        ON(es.remotii_id=r.remotii_id)
        where
        ((condition_type=0 AND (es.input_bits ^ r.remotii_last_input_status)) OR (condition_type=1 AND (es.input_bits && r.remotii_last_input_status)) AND occurence_type=0 AND occurence_time='$time' AND occurence_date='$date1' AND occurence_month='$month' AND occurence_year='$year' AND event_status=1)
            OR
        ((condition_type=0 AND (es.input_bits ^ r.remotii_last_input_status)) OR (condition_type=1 AND (es.input_bits && r.remotii_last_input_status)) AND occurence_type=1 AND occurence_time='$time' AND event_status=1)
            OR
        ((condition_type=0 AND (es.input_bits ^ r.remotii_last_input_status)) OR (condition_type=1 AND (es.input_bits && r.remotii_last_input_status)) AND occurence_type=2 AND occurence_time='$time' AND occurence_days='$day' AND event_status=1)
            OR
        ((condition_type=0 AND (es.input_bits ^ r.remotii_last_input_status)) OR (condition_type=1 AND (es.input_bits && r.remotii_last_input_status)) AND occurence_type=3 AND occurence_time='$time' AND occurence_date='$date1' AND event_status=1)
            OR
        ((condition_type=0 AND (es.input_bits ^ r.remotii_last_input_status)) OR (condition_type=1 AND (es.input_bits && r.remotii_last_input_status)) AND occurence_type=4 AND occurence_time=$time AND occurence_date='$date1'AND occurence_month='$month' AND event_status=1)
           ");

        $dataPro = $this->adapter->query($q);
        $results = $dataPro->execute()->getResource()->fetch(\PDO::FETCH_OBJ);
        return $results;
    }

    public function getRemotii_last_input_status($id) {
        $q = "select remotii_last_input_status from remotii 
        where remotii_id='$id'";
        $dataPro = $this->adapter->query($q);
        $results = $dataPro->execute()->getResource()->fetchAll(\PDO::FETCH_OBJ);
        return $results;
    }

    /**
     * Function getPinLabelInfo;
     */
    public function getPinLabelInfo($mac, $pinNum) {
//  Get the pinnumber to be changed
        if ($pinNum & 1) {
            $pinNumStatus = 1;
        }
        if ($pinNum & 2) {
            $pinNumStatus = 2;
        }
        if ($pinNum & 4) {
            $pinNumStatus = 3;
        }
        if ($pinNum & 8) {
            $pinNumStatus = 4;
        }

        $q = "SELECT * FROM 
                remotii r INNER JOIN user_remotii ur ON (r.remotii_id = ur.remotii_id)
                INNER JOIN user_remotii_input_config rc ON (rc.user_remotii_id = ur.user_remotii_id)
                WHERE r.mac_address = '" . $mac . "'
                    AND rc.pin_number = '" . $pinNumStatus . "'
                ";
        $dataPro = $this->adapter->query($q);
        $results = $dataPro->execute()->getResource()->fetchAll(\PDO::FETCH_ASSOC);
        return $results;
    }

    /**
     * Function getPinLabelInfo;
     */
    public function getRemotiiName($mac) {

        $q = "SELECT remotii_name FROM 
                remotii r INNER JOIN user_remotii ur ON (r.remotii_id = ur.remotii_id)
                WHERE r.mac_address = '" . $mac . "'
                ";
        $dataPro = $this->adapter->query($q);
        $results = $dataPro->execute()->getResource()->fetchAll(\PDO::FETCH_ASSOC);
        return $results;
    }

    public function getInputConfig($mac) {
        $q = "SELECT * FROM 
                remotii r INNER JOIN user_remotii ur ON (r.remotii_id = ur.remotii_id) 
                INNER JOIN user_remotii_input_config rc ON (rc.user_remotii_id = ur.user_remotii_id) 
                WHERE r.mac_address = '" . $mac . "' ORDER BY rc.pin_number ASC
                ";
        $dataPro = $this->adapter->query($q);
        $results = $dataPro->execute()->getResource()->fetchAll(\PDO::FETCH_ASSOC);
        return $results;
    }

    /**
     * Function getPinLabelInfo;
     */
    public function getOutputConfig($mac) {
        $q = "SELECT * FROM 
                remotii r INNER JOIN user_remotii ur ON (r.remotii_id = ur.remotii_id) 
                INNER JOIN user_remotii_output_config rc ON (rc.user_remotii_id = ur.user_remotii_id) 
                WHERE r.mac_address = '" . $mac . "' ORDER BY rc.pin_number ASC
                ";
        $dataPro = $this->adapter->query($q);
        $results = $dataPro->execute()->getResource()->fetchAll(\PDO::FETCH_ASSOC);
        return $results;
    }

    public function changeMailNotificationStatus($data) {
        $cData = implode(',', $data);

        $update = "UPDATE notification_email SET flag = 1 WHERE nid IN (" . $cData . ")";
        echo $update; exit;
        $queryExeA = $this->adapter->query($update);
        $queryExeA->execute();
        return;
    }

    public function changeOfflineMailNotificationStatus($data) {
        $cData = implode(',', $data);

        $update = "UPDATE offline_notification_email SET status = 1 WHERE id IN (" . $cData . ")";
        $queryExeA = $this->adapter->query($update);
        $queryExeA->execute();
        return;
    }

    public function checkSPAccountStatus($username) {
        $q = "select acc_status from user where 
    			username=" . $this->platform->quoteValue($username);

        $stmt = $this->adapter->query($q);
        $accountStatus = $stmt->execute()->getResource()->fetchColumn();

        return $accountStatus;
    }

    /**
     * 
     * @return type
     */
    public function chargeEndUserByPerRemotii($uid) {
        $uQry = "SELECT count(user_id) as totalCount FROM user u WHERE u.user_id = '" . $uid . "' AND u.acc_status <> '" . SUSPENDED . "'";
        $dataU = $this->adapter->query($uQry);
        $uDetails = $dataU->execute()->getResource()->fetchAll(\PDO::FETCH_OBJ);

        if ($uDetails[0]->totalCount >= 1) {
            $qry = "SELECT count(sp.service_provider_id) as spCount , sp.end_user_price as amount, 
                            sp.service_fee, 
                            sp.service_provider_id 
                          FROM remotii r INNER JOIN service_provider sp ON ( sp.service_provider_id = r.service_provider_id ) 
                          LEFT JOIN service_provider_receiving_payment_details sprp ON (sp.service_provider_id = sprp.service_provider_id) 
                          WHERE r.remotii_status <> " . SUSPENDED . " AND 
                            r.remotii_status <> '" . SUSPENDED_BY_ADMIN . "'
                          "
            ;

            $dataPro = $this->adapter->query($qry);
            $amountDetails = $dataPro->execute()->getResource()->fetchAll(\PDO::FETCH_OBJ);
            return $amountDetails;
        }
    }

    /**
     * 
     * @return type
     */
    public function chargeToEndUserByPerRemotii($remotiiId) {
        $qry = "SELECT count(sp.service_provider_id) as spCount , sp.end_user_price as amount, 
                    sp.service_fee, 
                    sp.service_provider_id, 
                    sprp.stripe_acc_id 
                    FROM remotii r 
                    INNER JOIN service_provider sp ON ( sp.service_provider_id = r.service_provider_id ) 
                    LEFT JOIN service_provider_receiving_payment_details sprp ON (sp.service_provider_id = sprp.service_provider_id) 
                WHERE r.remotii_status <> " . SUSPENDED . " AND 
                      r.remotii_status <> '" . SUSPENDED_BY_ADMIN . "' AND 
                      sp.allow_end_user_billing = 1 AND 
                      r.remotii_id = '" . $remotiiId . "'
                "
        ;

        $dataPro = $this->adapter->query($qry);
        $amountDetails = $dataPro->execute()->getResource()->fetchAll(\PDO::FETCH_OBJ);
        return $amountDetails;
    }

    /**
     * 
     * @param type $userId
     * @param type $userRemotiiId
     * @param type $profileId
     * @param type $cardHandlerName
     */
    public function saveEndUserPaymentProfile($userId, $userRemotiiId, $profileId, $cardHandlerName) {
        $query = "INSERT INTO end_user_payment_profile set user_id = '" . $userId . "', user_remotii_id = '" . $userRemotiiId . "', 
                  authorizenet_profile_id = '" . $profileId . "', card_holder = '" . $cardHandlerName . "'";
        $queryExeA = $this->adapter->query($query);
        $queryExeA->execute();
    }

    /**
     * 
     * @param type $userId
     * @param type $userRemotiiId
     * @param type $profileId
     * @param type $cardHandlerName
     */
    public function updateEndUserPaymentProfile($userId, $userRemotiiId, $profileId, $cardHandlerName) {
        $query = "UPDATE end_user_payment_profile set user_id = '" . $userId . "', 
                  authorizenet_profile_id = '" . $profileId . "', card_holder = '" . $cardHandlerName . "' WHERE user_remotii_id = '" . $userRemotiiId . "'";
        $queryExeA = $this->adapter->query($query);
        $queryExeA->execute();
    }

    /**
     * 
     * @param type $toDay
     * @param type $cron_start
     * @return type
     */
    public function saveCronData($toDay, $cron_start) {
        $queryAdd = "INSERT INTO  cron_automated_billing SET 
            execution_date = '" . $toDay . "', 
            start_time = '" . $cron_start . "'";
        echo $queryAdd; exit;
        $queryExeA = $this->adapter->query($queryAdd);
        $queryExeA->execute();
        $lastInsertedId = $this->adapter->getDriver()->getLastGeneratedValue();
        return $lastInsertedId;
    }

    /**
     * 
     * @param type $cid
     * @param type $cron_start
     * @param type $message
     * @return boolean
     */
    public function updateCronData($cid, $cron_end, $message) {
        $queryAdd = "UPDATE cron_automated_billing SET cron_message = " . $this->platform->quoteValue($message) . ", 
            end_time = '" . $cron_end . "' WHERE cid = '" . $cid . "'";
        $queryExeA = $this->adapter->query($queryAdd);
        $queryExeA->execute();
        return true;
    }

    public function updateRemotii($id) {
        $queryAdd = "UPDATE remotii SET cname= " . $this->platform->quoteValue($message) . ", 
            end_time = '" . $cron_end . "' WHERE cid = '" . $cid . "'";
        $queryExeA = $this->adapter->query($queryAdd);
        $queryExeA->execute();
        return true;
    }

    public function inserOffset($minutes, $remotii_id) {
        $queryAdd = "UPDATE user_remotii SET offset=$minutes where remotii_id='$remotii_id'";
        $queryExeA = $this->adapter->query($queryAdd);
        $queryExeA->execute();
        return true;
    }

    public function getCronEventSchedulerData($time) {
        $fullDate = date('Y-m-d H:i:00', $time);
        $q = "
            SELECT 
            es.id,
            CONV(es.input_bits, 10, 2) as input_bits,
            ep.dout_set,
            ep.dout_clr,
            es.mac_address,
            es.remotii_id,
            CONV(r.remotii_last_input_status, 10, 2) as remotii_last_input_status,
            ep.dout_tgl,
            ep.tx_type,
            uoc.pulse_width            
            from event_schedular_pins ep
            INNER JOIN event_scheduler es ON (es.id = ep.event_id)
            INNER JOIN user_remotii_output_config uoc ON ep.output_config_id = uoc.config_id
            INNER JOIN remotii as r ON(es.remotii_id = r.remotii_id)
            INNER JOIN user_remotii as ur ON(es.remotii_id = ur.remotii_id)
            WHERE
            (
                (
                    occurence_type=0 AND occurence_date = DAY('" . $fullDate . "' + INTERVAL (ur.offset) MINUTE)
                    AND
                    occurence_month=MONTH('" . $fullDate . "' + INTERVAL (ur.offset) MINUTE)
                    AND
                    occurence_year=YEAR('" . $fullDate . "' + INTERVAL (ur.offset) MINUTE)
                )
                OR
                (
                    occurence_type=1 
                )
                OR
                (
                    occurence_type=2
                    AND
                    occurence_days LIKE CONCAT('%',DATE_FORMAT('" . $fullDate . "' + INTERVAL (ur.offset) MINUTE,'%a'),'%')
                )
                OR
                (
                    occurence_type=3
                    AND
                    occurence_date=DAY('" . $fullDate . "' + INTERVAL (ur.offset) MINUTE)
                )
                OR
                (
                    occurence_type=4
                    AND
                    occurence_date=DAY('" . $fullDate . "' + INTERVAL (ur.offset) MINUTE)
                    AND
                    occurence_month=MONTH('" . $fullDate . "' + INTERVAL (ur.offset) MINUTE)
                )
            )
            AND 
            (
                (
                    condition_type= '-1'
                )
                OR
                (
                    condition_type=0
                    AND
                    
                    CASE WHEN 
                    ((es.input_bits & r.remotii_last_input_status) ^ (es.input_bits & es.input_cond))  > 13
                    THEN 
                    (
                        (
                            (
                                (es.input_bits & r.remotii_last_input_status) ^ (es.input_bits & es.input_cond)
                            )
                            ^ (1)
                         )
                         &
                         es.input_bits
                      ) != 0
                    WHEN 
                    ((es.input_bits & r.remotii_last_input_status) ^ (es.input_bits & es.input_cond) ) > 11
                    THEN 
                    (((((es.input_bits & r.remotii_last_input_status) ^ (es.input_bits & es.input_cond) ) ^ (3)) & es.input_bits) != 0 )
                    WHEN 
                    ((es.input_bits & r.remotii_last_input_status) ^ (es.input_bits & es.input_cond) ) > 7
                    THEN 
                    (((((es.input_bits & r.remotii_last_input_status) ^ (es.input_bits & es.input_cond) ) ^ (7)) & es.input_bits) != 0 )
                   ELSE
                    (((((es.input_bits & r.remotii_last_input_status) ^ (es.input_bits & es.input_cond) ) ^ (15)) & es.input_bits) != 0 )
                    END
                )
                OR
                (
                    condition_type=1
                    AND
                    (
                        (es.input_bits & r.remotii_last_input_status) = (es.input_bits & es.input_cond)
                    )
                )
                
            )
            AND
            event_status = 1
            AND
            occurence_time = DATE_FORMAT('" . $fullDate . "' + INTERVAL (ur.offset) MINUTE,'%h:%i %p')
           ";
        $dataPro = $this->adapter->query($q);
        return $results = $dataPro->execute()->getResource()->fetchAll(\PDO::FETCH_OBJ);
    }

    public function insertEventSchedulerCronLog($time, $serverTime) {
        /*$queryAdd = "INSERT INTO  cron_event_scheduler SET 
            log_text = 'CRON STARTS BUT NOT FINISHED (SERVER TIME : '" . $serverTime . "')', 
            created_on = '" . $time . "'";*/
        
        $queryAdd = "INSERT INTO  cron_event_scheduler SET 
            log_text = 'CRON STARTS BUT NOT FINISHED (SERVER TIME : " . $serverTime . ")', 
            created_on = '" . $time . "'";

        $queryExeA = $this->adapter->query($queryAdd);

        $queryExeA->execute();
        $lastInsertedId = $this->adapter->getDriver()->getLastGeneratedValue();
        return $lastInsertedId;
    }

    public function updateEventSchedulerCronLog($cronLogId, $message) {
        $queryAdd = "UPDATE  cron_event_scheduler SET 
            log_text = '" . addcslashes($message, "'") . "', 
            status = '1'
            WHERE id='" . $cronLogId . "'";

        $queryExeA = $this->adapter->query($queryAdd);
        $queryExeA->execute();
        return $cronLogId;
    }

    public function getLastEventSchedulerCronLogTime() {
        $q = "SELECT  MAX(created_on) as last_execution_time FROM cron_event_scheduler 
            WHERE
            status = 1";

        $dataPro = $this->adapter->query($q);
        return $results = $dataPro->execute()->getResource()->fetchColumn();
    }

    public function insertCronEventDataToOutbound($dout_set, $dout_clr, $remotii_id, $mac_address, $dout_tgl, $tx_type, $pulse_width) {
        $queryAdd = ("Insert INTO outbound(dout_set,dout_clr,remotii_id,mac_address, dout_tgl, dout_tgl_dly_ms, tx_type) 
                  VALUES ('" . $dout_set . "','" . $dout_clr . "','" . $remotii_id . "','" . $mac_address . "','" . $dout_tgl . "','" . $pulse_width . "','" . $tx_type . "')");
        $queryExeA = $this->adapter->query($queryAdd);
        $queryExeA->execute();
    }

    public function getRemotiiId($mac_address) {
        $query = "SELECT remotii_id FROM remotii WHERE mac_address = '$mac_address'";
        $dataPro = $this->adapter->query($query);
        $results = $dataPro->execute()->getResource()->fetchAll(\PDO::FETCH_OBJ);
        return $results;
    }

    public function getRemotiiOffsetDayLightSaving($remotii_id) {
        $query = "SELECT offset,day_light_saving FROM user_remotii WHERE remotii_id = '$remotii_id'";
        $dataPro = $this->adapter->query($query);
        $results = $dataPro->execute()->getResource()->fetchAll(\PDO::FETCH_OBJ);
        $dataCust = array();
        return $results;
    }

    public function fetchAllErrors() {
        $query = "SELECT * from error ORDER BY error_id DESC limit 10000";
        $dataPro = $this->adapter->query($query);
        $results = $dataPro->execute()->getResource()->fetchAll(\PDO::FETCH_ASSOC);
        return $results;
    }

    public function getMacAddressByRemotiiId($remotiiId) {
        $query = "SELECT mac_address from remotii where remotii_id= '$remotiiId'";
        $dataPro = $this->adapter->query($query);
        $results = $dataPro->execute()->getResource()->fetch(\PDO::FETCH_COLUMN);
        return $results;
    }

    public function getServiceProviderRemotiis($id) {
        $query = "SELECT r.remotii_id,r.* from remotii as r where (r.remotii_id NOT IN(select ur.remotii_id from user_remotii as ur)) AND r.service_provider_id= '$id'";
        $dataPro = $this->adapter->query($query);
        $results = $dataPro->execute()->getResource()->fetchAll(\PDO::FETCH_OBJ);
        return $results;
    }

    public function saveUserRemotiiData($remVal, $remId, $loggedInUserId) {
        $time = time();
        $queryAdd = "INSERT INTO user_remotii SET 
                                user_id = " . $loggedInUserId . ", 
                                remotii_id = " . $remId . ", 
                                remotii_name = '" . $remVal . "', 
                                is_default_cofig 	 = " . 0 . ",
                                is_default = " . 0 . ",
                                updated_by = " . $loggedInUserId . ", 
                                updated_on = " . $time . ",
                                zone_id = '0',
                                offset = '0',
                                day_light_saving='0',
                                payment_status = '0'";


        $query = $this->adapter->query($queryAdd);
        $query->execute();
        $lastInsertedId = $this->adapter->getDriver()->getLastGeneratedValue();
        return $lastInsertedId;
    }

    public function clearErrors() {
        $queryAdd = "TRUNCATE TABLE error";
        $query = $this->adapter->query($queryAdd);
        $query->execute();
    }

    public function findUserByEmailAndUsername($email, $username) {
        $query1 = "SELECT user_id FROM user WHERE email=" . $this->platform->quoteValue($email) . " AND username = " . $this->platform->quoteValue($username) . " LIMIT 1";
        $result1 = $this->adapter->query($query1);
        return $result1->execute()->getResource()->fetch(\PDO::FETCH_COLUMN);
    }

    public function getSPCredits($id) {
        $query1 = "SELECT service_provider_credit FROM service_provider WHERE service_provider_id=$id ";
        $result1 = $this->adapter->query($query1);
        return $result1->execute()->getResource()->fetch(\PDO::FETCH_COLUMN);
    }

    public function addNoteFromAdminToRemotti($data) {
        $query = "update remotii set admin_notes='" . $data['note'] . "' where remotii_id =" . $data['r_id'] . " ";
        $queryExe = $this->adapter->query($query);
        $status = $queryExe->execute();
        return $status;
    }

    public function addNoteFromSPToRemotti($data) {
        $query = "update user_remotii set rsp_notes='" . $data['note'] . "' where user_remotii_id =" . $data['ur_id'] . " ";
        $queryExe = $this->adapter->query($query);
        $status = $queryExe->execute();

        return $status;
    }

    /**
     * Function updateAdminSettings() defined to save admin settings
     * 
     * @param type $data
     * @return boolean
     */
    public function updateAdminSettings($data) {

        $queryUpdate = "UPDATE admin_settings SET contracted_price = " . $this->platform->quoteValue($data['contracted_price']) . " WHERE id=1";
        $queryExeA = $this->adapter->query($queryUpdate);
        $queryExeA->execute();
        return true;
    }

    public function getAdminSettings() {
        $query1 = "SELECT * FROM admin_settings";
        $result1 = $this->adapter->query($query1);
        $results = $result1->execute()->getResource()->fetch(\PDO::FETCH_ASSOC);
        return $results;
    }
     public function getSharedInfo($remotiiId) {
        $query1 = "SELECT user.username,user.email,user.user_id,
                    shared_remotii.created_on,shared_remotii.updated_on,shared_remotii.access_level 
                    FROM shared_remotii 
                    INNER JOIN user_remotii 
                    ON user_remotii.user_remotii_id=shared_remotii.user_remotii_id
                    INNER JOIN user
                    ON user.user_id=shared_remotii.shared_user_id 
                    where user_remotii.remotii_id='$remotiiId'";
        $result1 = $this->adapter->query($query1);
        $results = $result1->execute()->getResource()->fetchAll(\PDO::FETCH_OBJ);
        return $results;
    }

}
