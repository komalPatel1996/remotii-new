<?php
namespace RemotiiModels\Model;
use Zend\Db\Adapter\Adapter;
use Zend\Db\TableGateway\AbstractTableGateway;
use Zend\Crypt\Password\Bcrypt;
use Zend\Db\Sql\Sql;
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
        $this->platform = $this->adapter->getPlatform();
    }
    /**
     *
     * @param type $macId        	
     * @return type
     */
    public function getRemotiiId($macId) {
        $customArr = array();
        $macId = trim($macId);
        $query = "SELECT remotii_id, service_provider_id FROM remotii WHERE mac_address = " . $this->platform->quoteValue($macId);
        $dataPro = $this->adapter->query($query);
        $results = $dataPro->execute()->getResource()->fetchAll(\PDO::FETCH_OBJ);
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
    public function saveUserRemotiiData($remotiiName, $remotiiId, $loggedInUserId, $settingType, $paymentStatus, $selGmt, $day_saving, $offset) {
        $time = time();
        if ($data = $this->ifRemotiiExist($remotiiId)) {
            $queryAdd = "UPDATE user_remotii SET 
                                user_id = " . $loggedInUserId . ", 
                                remotii_name = " . $this->platform->quoteValue($remotiiName) . ", 
                                is_default_cofig 	 = " . $settingType . ",
                                updated_by = " . $loggedInUserId . ", 
                                updated_on = " . $time . ",
                                zone_id = '" . $selGmt . "',
                                offset = '" . $offset . "',
                                day_light_saving='" . $day_saving . "',
                                payment_status = '" . $paymentStatus . "'
                        where   remotii_id = " . $remotiiId . "";
            $query = $this->adapter->query($queryAdd);
            $query->execute();
        } else {
            $queryAdd = "INSERT INTO user_remotii SET 
                                user_id = " . $loggedInUserId . ", 
                                remotii_id = " . $remotiiId . ", 
                                remotii_name = " . $this->platform->quoteValue($remotiiName) . ", 
                                is_default_cofig 	 = " . $settingType . ",
                                is_default = ( select IF ( count( * ) = 0, 1, 0 ) from user_remotii t2 where t2.user_id = '$loggedInUserId' ),
                                updated_by = " . $loggedInUserId . ", 
                                updated_on = " . $time . ",
                                zone_id = '" . $selGmt . "',
                                offset = '" . $offset . "',
                                day_light_saving='" . $day_saving . "',
                                payment_status = '" . $paymentStatus . "'";
            $query = $this->adapter->query($queryAdd);
            $query->execute();
        }
        $lastInsertedId = $this->adapter->getDriver()->getLastGeneratedValue();
        return $lastInsertedId;
    }
    public function ifRemotiiExist($remotiiId) {
        $q = "SELECT remotii_id from user_remotii where remotii_id=$remotiiId";
        $result = $this->adapter->query($q);
        $data = $result->execute()->getResource()->fetch(\PDO::FETCH_COLUMN);
        return $data;
    }
    public function updateRemotiiName($userRemotiiId, $remotiiName, $selGmt, $day_saving, $offset) {
        $time = time();
        $queryAdd = "UPDATE user_remotii SET
            remotii_name = " . $this->platform->quoteValue($remotiiName) . ",
            updated_on = " . $time . ",
            zone_id=" . $this->platform->quoteValue($selGmt) . ",
             offset=" . $this->platform->quoteValue($offset) . ",
            day_light_saving=" . $this->platform->quoteValue($day_saving) . "
    		where user_remotii_id = '$userRemotiiId'";
        $query = $this->adapter->query($queryAdd);
        $query->execute();
        return true;
    }
    public function updateRemotiiNotificationSettings($remotii_id, $enable_end_user_offline_notifications = 0, $enable_rsp_offline_notifications = 0, $offline_notification_timeout_hours = 1, $notification_email) {
        $time = time();
        $queryAdd = "UPDATE remotii SET
          
            updated_on = " . $time . ",
            enable_end_user_offline_notifications = '" . $enable_end_user_offline_notifications . "',
            enable_rsp_offline_notifications = '" . $enable_rsp_offline_notifications . "',
            offline_notification_timeout_hours = '" . $offline_notification_timeout_hours . "',
            notification_email = '" . $notification_email . "'
           
    		where remotii_id = '$remotii_id'";
        $query = $this->adapter->query($queryAdd);
        $query->execute();
        return true;
    }
    public function getGmt($selGmt) {
        $q = "SELECT offset_min FROM 
	            zone_gmt where zone_id=$selGmt";
        $result = $this->adapter->query($q);
        $data = $result->execute()->getResource()->fetchAll(\PDO::FETCH_ASSOC);
        return $data;
    }
    public function saveInputConfig($userRemotiiId, $spInputConfig, $lid,$remotiiId = null,$userType='user') {
        $queryInsert = "
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
                                        SoundFlag,
		    			updated_by,
		    			updated_on
    				) VALUES ";
        $configIds = array();
        foreach ($spInputConfig as $spic) {
            $mergeEmail = implode(",", $spic['email']);
            if (empty($spic))
                continue;
            if ($spic['config_id'] == '' || $userType == 'sp') {
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
    					'" . $mergeEmail . "',
                                        '" . (!empty($spic['play_sound']) ? $spic['play_sound'] : $spic['notification_sound']) . "',
    					$lid,		
    					'" . time() . "'
    					)";
            } else {
                $updateQry = "update user_remotii_input_config SET 
                                        user_remotii_id= $userRemotiiId, 
    					pin_number= '" . $spic ['pin_number'] . "',
    					name= '" . $spic ['name'] . "',
		    			active_label_text = '" . $spic ['active_label_text'] . "',
		    			active_label_color = '" . $spic ['active_label_color'] . "',
		    			inactive_label_text = '" . $spic ['inactive_label_text'] . "',
		    			inactive_label_color = '" . $spic ['inactive_label_color'] . "',
		    			is_enabled = 1,
		    			enable_notification = '" . $spic ['enable_notification'] . "',
		    			notification_trigger = '" . $spic ['notification_trigger'] . "',
		    			notification_email = '" . $mergeEmail . "',
                                        SoundFlag = '" . (!empty($spic['play_sound']) ? $spic['play_sound'] : $spic['notification_sound']) . "',
		    			updated_by = $lid,
		    			updated_on = '" . time() . "' where config_id=" . $spic['config_id'];
                $upstmt = $this->adapter->query($updateQry);
                $upstmt->execute();
                $configIds[] = $spic['config_id'];
            }
        }
        if (count($configIds) > 0) {
            $ids = implode(',', $configIds);
            $pinQry = "select * from user_remotii_input_config where config_id NOT IN (" . $ids . ") AND user_remotii_id = '$userRemotiiId'";
            $cmd = $this->adapter->query($pinQry);
            $result = $cmd->execute();
            if (count($result) > 0) {
                $dltQry = "delete from user_remotii_input_config where config_id NOT IN (" . $ids . ") AND user_remotii_id = '$userRemotiiId'";
                $dstmt = $this->adapter->query($dltQry);
                $res = $dstmt->execute();
                $disable = $this->disableChainedEvent($remotiiId);
            }
        }
//        if (count($configIds) > 0) {
//            $ids = implode(',', $configIds);
//            $dltQry = "delete from user_remotii_input_config where config_id NOT IN (" . $ids . ") AND user_remotii_id = '$userRemotiiId'";
//            $dstmt = $this->adapter->query($dltQry);
//            $dstmt->execute();
//        }
        if (!empty($subQryArr)) {
            if (count($configIds) <= 0) {
                $query = "
    			delete from user_remotii_input_config where user_remotii_id = '$userRemotiiId';";
            } else {
                $query = '';
            }
            $subQry = implode(', ', $subQryArr);
            $queryInsert = $queryInsert . $subQry;
            $query = $query . $queryInsert;
            $stmt = $this->adapter->query($query);
            $stmt->execute();
        }
        $lastInsertedId = $this->adapter->getDriver()->getLastGeneratedValue();
        return $lastInsertedId;
    }
    public function saveOutputConfig($userRemotiiId, $spOutputConfig, $lid, $remotiiId = null,$userType='user') {
        //find out config ids for this table
        $queryOldReferenceConfig = "select pin_number, config_id from user_remotii_output_config where user_remotii_id = '$userRemotiiId' and config_id in (select output_config_id from event_schedular_pins);";
        $stmt = $this->adapter->query($queryOldReferenceConfig);
        $queryOldReferenceConfig = $stmt->execute();
        $configIds = array();
        $queryInsert = "
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
                                        SoundFlag,
                                        pulse_width,
		    			updated_by,
		    			updated_on
    				) VALUES ";
        $myfile = fopen("test/testfile.txt", "w");
        foreach ($spOutputConfig as $spoc) {
//            print_r($spoc['email']);exit();
            $mergeEmail = implode(",", $spoc['email']);
            if (empty($spoc))
                continue;
            
            if ($spoc['config_id'] == '' || $userType == 'sp') {
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
    					'" . $mergeEmail . "',
                                        '" . (!empty($spoc['play_sound']) ? $spoc['play_sound'] : $spoc['notification_sound']) . "',    
                                        '" . (!empty($spoc['ro_pulse_time']) ? $spoc['ro_pulse_time'] : $spoc['pulse_time']) . "',
    					$lid,		
    					'" . time() . "'
    					)";
            } else {
                $updateQry = "update user_remotii_output_config set 
                                        user_remotii_id = $userRemotiiId,
    					pin_number = '" . $spoc ['pin_number'] . "',
    					name = '" . $spoc ['name'] . "',
		    			active_label_text = '" . $spoc ['active_label_text'] . "',
		    			active_label_color = '" . $spoc ['active_label_color'] . "',
		    			inactive_label_text = '" . $spoc ['inactive_label_text'] . "',
		    			inactive_label_color = '" . $spoc ['inactive_label_color'] . "',
		    			is_enabled = 1,
    					is_output_momentary = '" . $spoc ['is_output_momentary'] . "',
    					output_initial_state = '" . $spoc ['output_initial_state'] . "',
		    			enable_notification = '" . $spoc ['enable_notification'] . "',
		    			notification_trigger = '" . $spoc ['notification_trigger'] . "',
                                        notification_email = '" . $mergeEmail . "',
                                        SoundFlag = '" . (!empty($spoc['play_sound']) ? $spoc['play_sound'] : $spoc['notification_sound']) . "',
                                        pulse_width = '" . (!empty($spoc['ro_pulse_time']) ? $spoc['ro_pulse_time'] : $spoc['pulse_time']) . "',
		    			updated_by = $lid,
		    			updated_on = '" . time() . "' where config_id=" . $spoc['config_id'];
                $upstmt = $this->adapter->query($updateQry);
                $upstmt->execute();
                $configIds[] = $spoc['config_id'];
            }
        }
        if (count($configIds) > 0) {
            $ids = implode(',', $configIds);
            $pinQry = "select * from user_remotii_output_config where config_id NOT IN (" . $ids . ") AND user_remotii_id = '$userRemotiiId'";
            $cmd = $this->adapter->query($pinQry);
            $result = $cmd->execute();
            if (count($result) > 0) {
                $dltQry = "delete from user_remotii_output_config where config_id NOT IN (" . $ids . ") AND user_remotii_id = '$userRemotiiId'";
                $dstmt = $this->adapter->query($dltQry);
                $res = $dstmt->execute();
                $disable = $this->disableChainedEvent($remotiiId);
            }
        }
        if (count($subQryArr) > 0) {
            if (count($configIds) <= 0) {
                $queryToDelete = "
    			delete from user_remotii_output_config where user_remotii_id = '$userRemotiiId';";
                $stmt = $this->adapter->query($queryToDelete);
                $queryToDelete = $stmt->execute();
            }
            $subQry = implode(', ', $subQryArr);
            $queryInsert = $queryInsert . $subQry;
            $query = $query . $queryInsert;
        }
        if(!$query){
            $query ='no quer';
        }
         fwrite($myfile, $query);
         fclose($myfile);
//        _pre($query);
        $stmt = $this->adapter->query($query);
        $stmt->execute();
        $lastInsertedId = $this->adapter->getDriver()->getLastGeneratedValue();
        if (count($queryOldReferenceConfig) > 0) {
            _pre('$queryNewReferenceConfig');
            _pre($queryNewReferenceConfig);
            $queryNewReferenceConfig = "select pin_number, config_id from user_remotii_output_config where user_remotii_id = '$userRemotiiId';";
            $stmt = $this->adapter->query($queryNewReferenceConfig);
            $queryNewReferenceConfig = $stmt->execute();
             _pre('$queryOldReferenceConfig');
            _pre($queryOldReferenceConfig);
            foreach ($queryOldReferenceConfig as $old) {
                foreach ($queryNewReferenceConfig as $new) {
                    if ($old["pin_number"] == $new["pin_number"]) {
                        $updatequery = "update event_schedular_pins set output_config_id = " . $new["config_id"] . " where output_config_id = " . $old["config_id"] . " ;";
                        $stmt = $this->adapter->query($updatequery);
                        $stmt->execute();
                        break;
                    }
                }
            }
        }
        return $lastInsertedId;
    }
    /**
     *
     * @param type $spId        	
     * @param type $userRemotiiId        	
     * @return type
     */
    public function saveDefaultIOconfig($spId, $userRemotiiId, $lid, $loggedInUserEmail) {
        $querySPIC = "SELECT * FROM service_provider_input_config WHERE service_provider_id = '" . $spId . "'";
        $data = $this->adapter->query($querySPIC);
        $resultsSPIC = $data->execute()->getResource()->fetchAll(\PDO::FETCH_OBJ);
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
        foreach ($resultsSPIC as $spic) {
            if (empty($spic))
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
    					'" . time() . "'
                                )";
        }
        if (!empty($subQryArr)) {
            $subQry = implode(', ', $subQryArr);
            $query = $query . $subQry;
            $stmt = $this->adapter->query($query);
            $stmt->execute();
            $lastInsertedId = $this->adapter->getDriver()->getLastGeneratedValue();
        }
        $querySPOC = "SELECT * FROM service_provider_output_config WHERE service_provider_id = '" . $spId . "'";
        $dataSPOC = $this->adapter->query($querySPOC);
        $resultsSPOC = $dataSPOC->execute()->getResource()->fetchAll(\PDO::FETCH_OBJ);
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
        foreach ($resultsSPOC as $spoc) {
            if (empty($spoc))
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
    					'" . time() . "'
    				)";
        }
        if (!empty($subQryArr2)) {
            $subQry = implode(', ', $subQryArr2);
            print $query = $query . $subQry;
            $stmt = $this->adapter->query($query);
            $stmt->execute();
            $lastInsertedId = $this->adapter->getDriver()->getLastGeneratedValue();
        }
        return;
    }
    /**
     *
     * @param type $macId        	
     * @return type
     */
    public function checkRole($loggedInUserId) {
        $qry = "SELECT user_role_id FROM user WHERE user_id = '" . $loggedInUserId . "'";
        $dataPro = $this->adapter->query($qry);
        $role = $dataPro->execute()->getResource()->fetch(\PDO::FETCH_COLUMN);
        return $role;
    }
    public function remotiiConfigchkValidateChk($macId) {
        $qry = "SELECT remotii_id FROM remotii WHERE mac_address = '" . trim($macId) . "'  AND remotii_status = '" . ACTIVE . "'";
        $dataPro = $this->adapter->query($qry);
        $results = $dataPro->execute()->getResource()->fetchAll(\PDO::FETCH_OBJ);
        if ($results [0]->remotii_id > 0) {
            $qry = "SELECT ur.remotii_id FROM 
		                remotii r INNER JOIN user_remotii ur ON (r.remotii_id = ur.remotii_id) 
		                WHERE r.mac_address = '" . trim($macId) . "'";
            $dataPro = $this->adapter->query($qry);
            $results = $dataPro->execute()->getResource()->fetchAll(\PDO::FETCH_OBJ);
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
     * Dev K
     * @param type $macId
     * @return type
     */
    public function remotiiUserPaymentInfo($macId) {
        $qry = "SELECT r.mac_address, r.service_provider_id, sp.end_user_price, sp.allow_end_user_billing, 
                    sp.company_name 
                    FROM remotii r 
                    INNER JOIN service_provider sp ON (r.service_provider_id = sp.service_provider_id) 
                    WHERE r.mac_address = '" . trim($macId) . "'";
        $dataPro = $this->adapter->query($qry);
        $results = $dataPro->execute()->getResource()->fetchAll(\PDO::FETCH_OBJ);
        return $results[0];
    }
    /**
     *
     * @param type $uid        	
     * @return type
     */
    public function getClientRemotii($uid, $remotiiId = null) {
//        $where = ($remotiiId) ? ' ur.remotii_id!=' . $remotiiId . ' AND' : '';
        $where = '';
        $query = "SELECT *, r.remotii_status,ur.is_default as ur_default,sr.is_default as sr_is_default
                    FROM user_remotii ur 
                    INNER JOIN remotii r ON(ur.remotii_id = r.remotii_id)
                    LEFT JOIN shared_remotii sr ON(ur.user_remotii_id = sr.user_remotii_id AND sr.shared_user_id ='" . $uid . "')
                    WHERE " . $where . " (ur.user_id = '" . $uid . "') OR (sr.shared_user_id ='" . $uid . "') ORDER BY ur.user_remotii_id DESC";
        $queryExec = $this->adapter->query($query);
        $results = $queryExec->execute()->getResource()->fetchAll(\PDO::FETCH_OBJ);
        return $results;
    }
    public function getSpRemotii($uid) {
        $query = "SELECT *, r.remotii_status,ur.is_default as ur_default
                    FROM user_remotii ur 
                    INNER JOIN remotii r ON(ur.remotii_id = r.remotii_id)
                    WHERE (ur.user_id = '" . $uid . "') ORDER BY ur.user_remotii_id DESC";
        $queryExec = $this->adapter->query($query);
        $results = $queryExec->execute()->getResource()->fetchAll(\PDO::FETCH_OBJ);
        return $results;
    }
    public function insertCheckInReq($mac_address) {
        $query = "Insert INTO outbound(mac_address,tx_type) VALUES('$mac_address', 'CHECK_IN_REQ')";
        $stmt = $this->adapter->query($query);
        $stmt->execute();
    }
    /**
     *
     * @param type $spid        	
     * @return type
     */
    public function getInputConfig($uid, $remotiiId) {
        $q = "SELECT * FROM 
	            user_remotii ur INNER JOIN user_remotii_input_config ic ON (ur.user_remotii_id = ic.user_remotii_id) 
	            WHERE ur.remotii_id = '" . $remotiiId . "'";
        $stmt = $this->adapter->query($q);
        $results = $stmt->execute()->getResource()->fetchAll(\PDO::FETCH_ASSOC);
        return $results;
    }
    /**
     *
     * @param type $spid        	
     * @return type
     */
    public function getOutputConfig($uid = null, $remotiiId) {
        $q = "SELECT * FROM 
	            user_remotii ur INNER JOIN user_remotii_output_config oc ON (ur.user_remotii_id = oc.user_remotii_id) 
	            WHERE ur.remotii_id = '" . $remotiiId . "'";
        $stmt = $this->adapter->query($q);
        $results = $stmt->execute()->getResource()->fetchAll(\PDO::FETCH_ASSOC);
        return $results;
    }
    /**
     *
     * @param type $userId        	
     * @param type $remotiiId        	
     * @return type
     */
    public function getRemotiiNameMacId($userId, $remotiiId) {
        $q = "SELECT 
               sr.*, r.*, ur.*, CASE WHEN (sr.shared_user_id) IS NOT NULL THEN CONCAT(ur.remotii_name,' ','(shared)') 
               Else ur.remotii_name End as remotii_name FROM
                remotii r  INNER JOIN user_remotii ur ON (r.remotii_id = ur.remotii_id)
                LEFT JOIN shared_remotii sr ON (ur.user_remotii_id = sr.user_remotii_id AND sr.shared_user_id =$userId)
                WHERE
                (ur.user_id = $userId OR  sr.shared_user_id = $userId) AND  ur.remotii_id =$remotiiId";
        $result = $this->adapter->query($q);
        $data = $result->execute()->getResource()->fetchAll(\PDO::FETCH_ASSOC);
        return $data;
    }
    public function getoffset() {
        $q = "SELECT * FROM 
	            zone_gmt
                    ";
        $result = $this->adapter->query($q);
        $data = $result->execute()->getResource()->fetchAll(\PDO::FETCH_ASSOC);
        return $data;
    }
    public function getRemotiiName($loggedInUserId) {
        $q = "SELECT sr.*, r.*, ur.* FROM
                remotii r 
                INNER JOIN user_remotii ur ON (r.remotii_id = ur.remotii_id)
                LEFT JOIN shared_remotii sr ON (ur.user_remotii_id = sr.user_remotii_id AND sr.shared_user_id =$loggedInUserId)
                WHERE
                (ur.user_id = $loggedInUserId OR  sr.shared_user_id = $loggedInUserId)  
               ";
        $result = $this->adapter->query($q);
        $data = $result->execute()->getResource()->fetchAll(\PDO::FETCH_ASSOC);
        return $data;
    }
    public function getIdFromUserRemotii($remotiiId) {
        $q = "SELECT user_remotii_id FROM 
            user_remotii 
            WHERE remotii_id = '" . $remotiiId . "'";
        $result = $this->adapter->query($q);
        $data = $result->execute()->getResource()->fetchAll(\PDO::FETCH_ASSOC);
        return $data [0] ['user_remotii_id'];
    }
    public function getUserRemotiiIDByMacID($macID) {
        $q = "SELECT user_remotii_id FROM
	            user_remotii ur INNER JOIN remotii r ON(ur.remotii_id = r.remotii_id)
	            WHERE r.mac_address = '" . $macID . "'";
        $result = $this->adapter->query($q);
        $data = $result->execute()->getResource()->fetchColumn();
        return $data;
    }
    /**
     *
     * @return boolean
     */
    public function createUser($data) {
        $time = time();
        $role = 3; // 1 indicate admin user role
        // Encode the password using
        $bcrypt = new Bcrypt ();
        // $bcrypt->setCost();
        $password = $bcrypt->create($data ['password']);
        $queryAdd = "INSERT INTO user SET 
            username = " . $this->platform->quoteValue($data ['userName']) . ", 
            fname = " . $this->platform->quoteValue($data ['fName']) . ", 
            lname = " . $this->platform->quoteValue($data ['lName']) . ", 
            phone = " . $this->platform->quoteValue($data ['phoneNumber']) . ", 
            email = " . $this->platform->quoteValue($data ['emailId']) . ", 
            password = " . $this->platform->quoteValue($password) . ", 
            user_role_id = " . $this->platform->quoteValue($role) . ", 
            street = " . $this->platform->quoteValue($data ['street']) . ", 
            city = " . $this->platform->quoteValue($data ['city']) . ", 
            state = " . $this->platform->quoteValue($data ['state']) . ", 
            zip_code = " . $this->platform->quoteValue($data ['zip_code']) . ", 
            country = " . $this->platform->quoteValue($data ['country']) . ", 
            created_on = " . $time . "";
        $queryExeA = $this->adapter->query($queryAdd);
        $queryExeA->execute();
        $lastInsertedId = $this->adapter->getDriver()->getLastGeneratedValue();
        return $lastInsertedId;
    }
    public function selGmtInsert($sel, $userRemotiiId) {
        $query = "UPDATE user_remotii SET offset=$sel
      where user_remotii_id='$userRemotiiId'";
        $queryExeA = $this->adapter->query($query);
        $queryExeA->execute();
        return true;
    }
    /**
     *
     * @return boolean
     */
    public function updateUser($data, $id) {
        if (!$id)
            return;
        if (!empty($data ['password'])) {
            // Encode the password using
            $bcrypt = new Bcrypt ();
            // $bcrypt->setCost(12);
            $password = $bcrypt->create($data ['password']);
            $passwordSubQry = "password = " . $this->platform->quoteValue($password) . ",";
        }
        $queryAdd = "UPDATE user SET
            username = " . $this->platform->quoteValue($data ['userName']) . ",
            fname = " . $this->platform->quoteValue($data ['fName']) . ",
            lname = " . $this->platform->quoteValue($data ['lName']) . ",
            phone = " . $this->platform->quoteValue($data ['phoneNumber']) . ",
            email = " . $this->platform->quoteValue($data ['emailId']) . ",
            $passwordSubQry
            street = " . $this->platform->quoteValue($data ['street']) . ",
            city = " . $this->platform->quoteValue($data ['city']) . ",
            state = " . $this->platform->quoteValue($data ['state']) . ",
            zip_code = " . $this->platform->quoteValue($data ['zip_code']) . ",
            country = " . $this->platform->quoteValue($data ['country']) . "                
            where user_id= " . $this->platform->quoteValue($id) . "";
        $queryExeA = $this->adapter->query($queryAdd);
        $queryExeA->execute();
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
            authorizenet_profile_id = " . $this->platform->quoteValue($profileId) . ", 
            payment_profile_id = " . $this->platform->quoteValue($paymentProfileId) . ", 
            shipping_profile_id = " . $this->platform->quoteValue($shippingAddrsId) . ", 
            card_holder = " . $this->platform->quoteValue($card_holder);
        $queryExeA = $this->adapter->query($queryAdd);
        $queryExeA->execute();
        return;
    }
    public function saveEvent($post, $mac_address, $rid, $userId) {
        $i = 0;
        $DecOutputs = 0;
        $DecInputs = 0;
        $d_Set = 0;
        $d_Clr = 0;
        $input_cond = 0;
        $input_bits = $post['radio1'];
        $remotii_id = $post['remotii_id'];
        $mac_address = $post['mac_address'];
        $arrayMomentary = $post['IsMomentary'];
        $arrayState = $post['radioState'];
        $outputNameArray = $post['outputname'];
        $inputNameArray = $post['inputname'];
        $arrayOutputPin = $post['pin_number'];
        $arrayInputPin = $post['pin_number1'];
        $d_tgl = 0;
        $tx_type = '';
        foreach ($outputNameArray as $outputName) {
            $pinNumber = $post['pin_output_config_id'][$outputName];
            $AddValue = pow(2, ($pinNumber - 1));
            $DecOutputs += $AddValue;
            if ($arrayMomentary[$outputName] == 1) {
                $d_Set = $d_Set + $AddValue;
                $d_Clr = $d_Clr + $AddValue;
            } else {
                if ($arrayState[$outputName] == 1) {
                    $d_Set = $d_Set + $AddValue;
                } else {
                    $d_Clr = $d_Clr + $AddValue;
                }
            }
        }
        foreach ($inputNameArray as $inputName) {
            $pinNumber = $post['pin_input_config_id'][$inputName];
            $AddValue = pow(2, ($pinNumber - 1));
            $DecInputs += $AddValue;
            if (!empty($post['radio1'][$inputName])) {
                $input_cond+=$AddValue;
            }
        }
        if ($post['occurence_type'] == 0) {
            $occurence_date = date('d', strtotime($post['occurence_date']));
            $occurence_month = date('m', strtotime($post['occurence_date']));
            $occurence_year = date('Y', strtotime($post['occurence_date']));
        } elseif ($post['occurence_type'] == 2) {
            $array = $post['days'];
            $days = implode(",", $array);
            $occurence_days = $days;
        } elseif ($post['occurence_type'] == 3) {
            $occurence_date = $post['date1'];
        } elseif ($post['occurence_type'] == 4) {
            $occurence_date = $post['date'];
            $occurence_month = $post['month'];
        }
        $occurence_hours = str_pad($post['hours'], 2, "0", STR_PAD_LEFT);
        $occurence_min = str_pad($post['min'], 2, "0", STR_PAD_LEFT);
        $occurence_am = $post['am'];
        $occurence_time = $occurence_hours . ":" . $occurence_min . " " . $occurence_am;
        $array1 = $post['radio1'];
//        $input_cond = implode("", $array1);
//        $input_cond = bindec($input_cond);
        // check whether any input is checked
        if (empty($DecInputs) || $DecInputs < 1) {
            $post['condition_type'] = -1;
        }
        $queryAdd = "INSERT INTO `event_scheduler`
            (`remotii_id`,
            `mac_address`,
            `event_name`,
            `output_bits`,
            `dout_set`,
            `dout_clr`,
            `occurence_time`,
            `occurence_type`, 
            `occurence_date`,
            `occurence_days`,
            `condition_type`,
            `occurence_month`,
            `occurence_year`,
            `input_bits`,
            `input_cond`,
            `event_status`,
            `created_by`,
            `updated_by`,
            `updated_on`
            ) 
            VALUES 
            (
            '$remotii_id ',
            '$mac_address',
            '$post[event_name]',
            '$DecOutputs',
            '$d_Set',
            '$d_Clr',
            '$occurence_time',
            '$post[occurence_type]',
            '$occurence_date',
            '$occurence_days',
            '$post[condition_type]',
            '$occurence_month',
            '$occurence_year',
            '$DecInputs',
            '$input_cond',
             '1',
             '$userId',
             '$userId',
             '" . time() . "'
             )";
        $queryExeA = $this->adapter->query($queryAdd);
        $queryExeA->execute();
        $event_id = $this->adapter->getDriver()->getLastGeneratedValue();
        foreach ($outputNameArray as $outputName) {
            $d_Set = 0;
            $d_Clr = 0;
            $d_tgl = 0;
            $pinNumber = $post['pin_output_config_id'][$outputName];
            $AddValue = pow(2, ($pinNumber - 1));
            if ($arrayMomentary[$outputName] == 1) {
                $d_tgl = $AddValue;
                $tx_type = 'GPIO_TGL_REQ';
            } else {
                if ($arrayState[$outputName] == 1) {
                    $d_Set = $AddValue;
                } else {
                    $d_Clr = $AddValue;
                }
                $tx_type = 'GPIO_SET_REQ';
            }
            $queryPins = "insert into event_schedular_pins
                         (event_id, 
                          output_config_id, 
                          tx_type, dout_set, 
                          dout_clr, 
                          dout_tgl) 
                          values 
                          ('$event_id',
                           '$outputName',
                           '$tx_type',
                           '$d_Set',
                           '$d_Clr',
                           '$d_tgl')";
            $queryPinsresult = $this->adapter->query($queryPins);
            $queryPinsresult->execute();
        }
        return;
    }
    public function updateEvent($post, $mac_address, $eventId, $userId) {
        $i = 0;
        $DecOutputs = 0;
        $DecInputs = 0;
        $d_Set = 0;
        $d_Clr = 0;
        $input_cond = 0;
        $input_bits = $post['radio1'];
        $remotii_id = $post['remotii_id'];
        $mac_address = $post['mac_address'];
        $arrayMomentary = $post['IsMomentary'];
        $arrayState = $post['radioState'];
        $outputNameArray = $post['outputname'];
        $inputNameArray = $post['inputname'];
        $arrayOutputPin = $post['pin_number'];
        $arrayInputPin = $post['pin_number1'];
        $d_tgl = 0;
        $tx_type = '';
        // _pre($arrayInputPin);
        $querydelete = "delete from event_schedular_pins where event_id=$eventId";
        $querydeleteRes = $this->adapter->query($querydelete);
        $querydeleteRes->execute();
        foreach ($outputNameArray as $outputName) {
            $pinNumber = $post['pin_output_config_id'][$outputName];
            $AddValue = pow(2, ($pinNumber - 1));
            $DecOutputs += $AddValue;
            if ($arrayMomentary[$outputName] == 1) {
                $d_Set = $d_Set + $AddValue;
                $d_Clr = $d_Clr + $AddValue;
            } else {
                if ($arrayState[$outputName] == 1) {
                    $d_Set = $d_Set + $AddValue;
                } else {
                    $d_Clr = $d_Clr + $AddValue;
                }
            }
        }
        foreach ($inputNameArray as $inputName) {
            $pinNumber = $post['pin_input_config_id'][$inputName];
            $AddValue = pow(2, ($pinNumber - 1));
            $DecInputs += $AddValue;
            if (!empty($post['radio1'][$inputName])) {
                $input_cond+=$AddValue;
            }
        }
        if ($post['occurence_type'] == 0) {
            $occurence_date = date('d', strtotime($post['occurence_date']));
            $occurence_month = date('m', strtotime($post['occurence_date']));
            $occurence_year = date('Y', strtotime($post['occurence_date']));
        } elseif ($post['occurence_type'] == 2) {
            $array = $post['days'];
            $days = implode(",", $array);
            $occurence_days = $days;
        } elseif ($post['occurence_type'] == 3) {
            $occurence_date = $post['date1'];
        } elseif ($post['occurence_type'] == 4) {
            $occurence_date = $post['date'];
            $occurence_month = $post['month'];
        }
        $occurence_hours = str_pad($post['hours'], 2, "0", STR_PAD_LEFT);
        $occurence_min = str_pad($post['min'], 2, "0", STR_PAD_LEFT);
        $occurence_am = $post['am'];
        $occurence_time = $occurence_hours . ":" . $occurence_min . " " . $occurence_am;
        $array1 = $post['radio1'];
//        $input_cond = implode("", $array1);
//        $input_cond = bindec($input_cond);
        // check whether any input is checked
        if (empty($DecInputs) || $DecInputs < 1) {
            $post['condition_type'] = -1;
        }
        $queryAdd = "UPDATE `event_scheduler`
            SET 
           `event_name`='$post[event_name]',
            `output_bits`       ='$DecOutputs',
            `dout_set`          ='$d_Set',
            `dout_clr`          ='$d_Clr',
            `occurence_time`    ='$occurence_time',
            `occurence_type`    ='$post[occurence_type]',
            `occurence_date`    ='$occurence_date',
            `occurence_days`    ='$occurence_days',
            `condition_type`    ='$post[condition_type]',
            `occurence_month`   ='$occurence_month',
            `occurence_year`    ='$occurence_year',
            `input_bits`        ='$DecInputs',
            `input_cond`        ='$input_cond',
            `updated_by`        = '$userId',
            `updated_on`        ='" . time() . "'
            WHERE id='" . $eventId . "'
            ";
        $queryExeA = $this->adapter->query($queryAdd);
        $queryExeA->execute();
        foreach ($outputNameArray as $outputName) {
            $d_Set = 0;
            $d_Clr = 0;
            $d_tgl = 0;
            $pinNumber = $post['pin_output_config_id'][$outputName];
            $AddValue = pow(2, ($pinNumber - 1));
            if ($arrayMomentary[$outputName] == 1) {
                $d_tgl = $AddValue;
                $tx_type = 'GPIO_TGL_REQ';
            } else {
                if ($arrayState[$outputName] == 1) {
                    $d_Set = $AddValue;
                } else {
                    $d_Clr = $AddValue;
                }
                $tx_type = 'GPIO_SET_REQ';
            }
            $queryPins = "insert into event_schedular_pins
                         (event_id, 
                          output_config_id, 
                          tx_type, dout_set, 
                          dout_clr, 
                          dout_tgl) 
                          values 
                          ('$eventId',
                           '$outputName',
                           '$tx_type',
                           '$d_Set',
                           '$d_Clr',
                           '$d_tgl')";
            $queryPinsresult = $this->adapter->query($queryPins);
            $queryPinsresult->execute();
        }
        return;
    }
    public function updateChainedEvent($post, $rid, $eventId, $userId) {
        $destDecOutputs = 0;
        $d_Set = 0;
        $d_Clr = 0;
        $arrayDestMomentary = $post['dest_IsMomentary'];
        $arrayDestState = $post['dest_radioState'];
        $destOutputNameArray = $post['dest_output'];
        $arrayDestOutputPin = $post['dest_pin_number'];
        $d_tgl = 0;
        $tx_type = '';
        $querydelete = "delete from chained_event_pins where chained_event_id=$eventId";
        $querydeleteRes = $this->adapter->query($querydelete);
        $querydeleteRes->execute();
        //destination remotii
        foreach ($destOutputNameArray as $desrOutputName) {
            $destPinNumber = $post['dest_pin_output_config_id'][$desrOutputName];
            $destAddValue = pow(2, ($destPinNumber - 1));
            $destDecOutputs += $destAddValue;
            if ($arrayDestMomentary[$desrOutputName] == 1) {
                $d_Set = $d_Set + $destAddValue;
                $d_Clr = $d_Clr + $destAddValue;
            } else {
                if ($arrayDestState[$desrOutputName] == 1) {
                    $d_Set = $d_Set + $destAddValue;
                } else {
                    $d_Clr = $d_Clr + $destAddValue;
                }
            }
        }
        ///src remotii output
        $srcDecOutputs = 0;
        $scr_d_Set = 0;
        $src_d_Clr = 0;
        $arraySrcMomentary = $post['IsMomentary'];
        $arraySrcState = $post['radioState'];
        $srcOutputNameArray = $post['outputname'];
        $arraySrcOutputPin = $post['pin_number'];
        foreach ($srcOutputNameArray as $srcOutputName) {
            $srcPinNumber = $post['pin_output_config_id'][$srcOutputName];
            $srcAddValue = pow(2, ($srcPinNumber - 1));
            $srcDecOutputs += $srcAddValue;
            if ($arraySrcMomentary[$srcOutputName] == 1) {
                $scr_d_Set = $scr_d_Set + $srcAddValue;
                $src_d_Clr = $src_d_Clr + $srcAddValue;
            } else {
                if ($arraySrcState[$srcOutputName] == 1) {
                    $scr_d_Set = $scr_d_Set + $srcAddValue;
                } else {
                    $src_d_Clr = $src_d_Clr + $srcAddValue;
                }
            }
        }
        //source remotii input
        $DecInputs = 0;
        $input_bits = $post['radio1'];
        $inputNameArray = $post['inputname'];
        $arrayInputPin = $post['pin_number1'];
        foreach ($inputNameArray as $inputName) {
            $pinNumber = $post['pin_input_config_id'][$inputName];
            $AddValue = pow(2, ($pinNumber - 1));
            $DecInputs += $AddValue;
            if (!empty($post['radio1'][$inputName])) {
                $input_cond+=$AddValue;
            }
        }
        $chainedEevent['event_name'] = $post['event_name'];
        $chainedEevent['source_remotii'] = $rid;
        $chainedEevent['source_output_bits'] = $srcDecOutputs;
        $chainedEevent['source_input_bits'] = $DecInputs;
        $chainedEevent['source_input_condition'] = $input_cond;
        $chainedEevent['source_output_condition'] = $scr_d_Set;
        $chainedEevent['destination_remotii'] = $post['triggering_remotii'];
        $chainedEevent['dest_output_bits'] = $destDecOutputs;
        $chainedEevent['trigger_condition'] = $d_Set;
        $chainedEevent['event_status'] = 1;
        $chainedEevent['condition_type'] = $post['condition'];
        $chainedEevent['config_change'] = 0;
        $sql = new Sql($this->adapter);
        $update = $sql->update('chained_event');
        $update->set($chainedEevent);
        $update->where(array('ce_id' => $eventId));
        $statement = $sql->prepareStatementForSqlObject($update);
        $results = $statement->execute();
        foreach ($destOutputNameArray as $desrOutputName) {
            $d_Set = 0;
            $d_Clr = 0;
            $d_tgl = 0;
            $pinNumber = $post['dest_pin_output_config_id'][$desrOutputName];
            $AddValue = pow(2, ($pinNumber - 1));
            if ($arrayDestMomentary[$desrOutputName] == 1) {
                $d_tgl = $AddValue;
                $tx_type = 'GPIO_TGL_REQ';
            } else {
                if ($arrayDestState[$desrOutputName] == 1) {
                    $d_Set = $AddValue;
                } else {
                    $d_Clr = $AddValue;
                }
                $tx_type = 'GPIO_SET_REQ';
            }
            $queryPins = "insert into chained_event_pins
                         (chained_event_id, 
                          output_config_id, 
                          tx_type, dout_set, 
                          dout_clr, 
                          dout_tgl) 
                          values 
                          ('$eventId',
                           '$desrOutputName',
                           '$tx_type',
                           '$d_Set',
                           '$d_Clr',
                           '$d_tgl')";
            $queryPinsresult = $this->adapter->query($queryPins);
            $queryPinsresult->execute();
        }
        return true;
    }
    public function selectquery($rid) {
        $queryAdd = "select * from event_scheduler where remotii_id='$rid' ORDER BY `id` DESC";
        $stmt = $this->adapter->query($queryAdd);
        $results = $stmt->execute()->getResource()->fetchAll(\PDO::FETCH_ASSOC);
        return $results;
    }
    public function getEventScheduleData($eventId) {
        $queryAdd = "select * from event_scheduler where id ='" . $eventId . "'";
        $stmt = $this->adapter->query($queryAdd);
        $results = $stmt->execute()->getResource()->fetch(\PDO::FETCH_ASSOC);
        return $results;
    }
    public function getChainedEventData($eventId) {
        $queryAdd = "select ce.* ,ur.remotii_name from chained_event ce  "
                . "left join user_remotii ur on ce.destination_remotii=ur.remotii_id where ce.ce_id ='" . $eventId . "'";
        $stmt = $this->adapter->query($queryAdd);
        $results = $stmt->execute()->getResource()->fetch(\PDO::FETCH_ASSOC);
        return $results;
    }
    public function deleteremotii($id) {
        $queryAdd = "DELETE FROM event_scheduler WHERE id=$id";
        $stmt = $this->adapter->query($queryAdd);
        $results = $stmt->execute();
        return true;
    }
    public function deleteChainedEvent($id) {
        $queryAdd = "DELETE FROM chained_event WHERE ce_id=$id";
        $stmt = $this->adapter->query($queryAdd);
        $results = $stmt->execute();
        $queryAdd = "DELETE FROM chained_event_pins WHERE chained_event_id=$id";
        $stmt = $this->adapter->query($queryAdd);
        $results = $stmt->execute();
        return true;
    }
    public function active($value, $id) {
        $queryAdd = "Update event_scheduler set event_status='$value' where id=$id";
        $stmt = $this->adapter->query($queryAdd);
        $stmt->execute();
        return true;
    }
    public function changeChainedEventStatus($value, $id) {
        $qry = "select config_change from chained_event where ce_id=$id";
        $sm = $this->adapter->query($qry);
        $results = $sm->execute()->getResource()->fetch(\PDO::FETCH_ASSOC);
//        stop();
        if ($results['config_change'] == 0) {
            if ($value == 0) {
                $queryAdd = "Update chained_event set event_status='$value',event_delay='0',email_sent= '0',last_executed_time=NULL where ce_id=$id";
            } else {
                $queryAdd = "Update chained_event set event_status='$value' where ce_id=$id";
            }
        } else {
            return false;
        }
        $stmt = $this->adapter->query($queryAdd);
        $stmt->execute();
        return true;
    }
    /**
     *
     * @param type $userId        	
     * @return type
     */
    public function getUserRemotiiIOconfig($userId, $remotiiId = "") {
        $q = "SELECT sr.*, r.*, ur.* ,ur.user_remotii_id as ur_remotii_id FROM
                remotii r 
                INNER JOIN user_remotii ur ON (r.remotii_id = ur.remotii_id)
                LEFT JOIN shared_remotii sr ON (ur.user_remotii_id = sr.user_remotii_id AND sr.shared_user_id ='" . $userId . "')
                WHERE
                (ur.user_id = '" . $userId . "' OR  sr.shared_user_id = '" . $userId . "') AND
                (r.remotii_status <> '" . SUSPENDED . "' AND r.remotii_status <> '" . SUSPENDED_BY_ADMIN . "' AND
                ur.remotii_id = '$remotiiId') 
                ORDER BY ur.user_remotii_id DESC";
        $stmt = $this->adapter->query($q);
        $results = $stmt->execute()->getResource()->fetchAll(\PDO::FETCH_ASSOC);
        $user_remotii_id = $results[0]['ur_remotii_id'];
        $query = "SELECT us.username
                    FROM shared_remotii sr
                    INNER JOIN user us ON (sr.shared_user_id = us.user_id)
                    where user_remotii_id='$user_remotii_id' ";
        $queryExec = $this->adapter->query($query);
        $shared_remotii_user = $queryExec->execute()->getResource()->fetchAll(\PDO::FETCH_ASSOC);
        $results[0]['shared_user'] = $shared_remotii_user;
        foreach ($results as $custData) {
            // Query to get the input config
            $in = "SELECT * FROM user_remotii_input_config WHERE user_remotii_id = '" . $custData ['user_remotii_id'] . "'";
            $inR = $this->adapter->query($in);
            $resultsIn = $inR->execute()->getResource()->fetchAll(\PDO::FETCH_ASSOC);
            // Query to get the output config
            $out = "SELECT * FROM user_remotii_output_config WHERE user_remotii_id = '" . $custData ['user_remotii_id'] . "'";
            $outR = $this->adapter->query($out);
            $resultsOut = $outR->execute()->getResource()->fetchAll(\PDO::FETCH_ASSOC);
        }
        $responseConfig = array();
        $responseConfig ['baseRec'] = $results;
        $responseConfig ['inConfig'] = $resultsIn;
        $responseConfig ['outConfig'] = $resultsOut;
        return $responseConfig;
    }
    public function getSpRemotiiIOconfig($userId, $remotiiId = "") {
        $q = "SELECT r.*, ur.* ,ur.user_remotii_id as ur_remotii_id FROM
                remotii r 
                INNER JOIN user_remotii ur ON (r.remotii_id = ur.remotii_id)
               
                WHERE
                (ur.user_id = '" . $userId . "') AND
                (r.remotii_status <> '" . SUSPENDED . "' AND r.remotii_status <> '" . SUSPENDED_BY_ADMIN . "' AND
                ur.remotii_id = '$remotiiId') 
                ORDER BY ur.user_remotii_id DESC";
        $stmt = $this->adapter->query($q);
        $results = $stmt->execute()->getResource()->fetchAll(\PDO::FETCH_ASSOC);
//        $user_remotii_id = $results[0]['ur_remotii_id'];
//        $query = "SELECT us.username
//                    FROM shared_remotii sr
//                    INNER JOIN user us ON (sr.shared_user_id = us.user_id)
//                    where user_remotii_id='$user_remotii_id' ";
//        $queryExec = $this->adapter->query($query);
//        $shared_remotii_user = $queryExec->execute()->getResource()->fetchAll(\PDO::FETCH_ASSOC);
//        $results[0]['shared_user'] = $shared_remotii_user;
        foreach ($results as $custData) {
            // Query to get the input config
            $in = "SELECT * FROM user_remotii_input_config WHERE user_remotii_id = '" . $custData ['user_remotii_id'] . "'";
            $inR = $this->adapter->query($in);
            $resultsIn = $inR->execute()->getResource()->fetchAll(\PDO::FETCH_ASSOC);
            // Query to get the output config
            $out = "SELECT * FROM user_remotii_output_config WHERE user_remotii_id = '" . $custData ['user_remotii_id'] . "'";
            $outR = $this->adapter->query($out);
            $resultsOut = $outR->execute()->getResource()->fetchAll(\PDO::FETCH_ASSOC);
        }
        $responseConfig = array();
        $responseConfig ['baseRec'] = $results;
        $responseConfig ['inConfig'] = $resultsIn;
        $responseConfig ['outConfig'] = $resultsOut;
        return $responseConfig;
    }
    public function getUserNameOfSharedRemotii($share_person_userId) {
        $q = "SELECT username FROM user
                WHERE
                user_id='" . $share_person_userId . "'
                ";
        $stmt = $this->adapter->query($q);
        $results = $stmt->execute()->getResource()->fetch(\PDO::FETCH_ASSOC);
        return $results['username'];
    }
    public function getUserRemotiiIOconf($userId) {
        $q = "SELECT sr.view_status as srv,ur.view_status as urv , sr.*, r.*, ur.* ,ur.is_default as ur_default,sr.is_default as sr_is_default FROM
                remotii r INNER JOIN user_remotii ur ON ( r.remotii_id = ur.remotii_id )
                 LEFT JOIN shared_remotii sr ON (ur.user_remotii_id = sr.user_remotii_id AND sr.shared_user_id =$userId)
                WHERE
                (ur.user_id ='" . $userId . "' OR  sr.shared_user_id = '" . $userId . "')
                ORDER BY ur.user_remotii_id DESC
                ";
        $stmt = $this->adapter->query($q);
        $results = $stmt->execute()->getResource()->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($results as $custData) {
            // Query to get the input config
            $in = "SELECT * FROM user_remotii_input_config WHERE user_remotii_id = '" . $custData ['user_remotii_id'] . "'";
            $inR = $this->adapter->query($in);
            $resultsIn[] = $inR->execute()->getResource()->fetchAll(\PDO::FETCH_ASSOC);
            // Query to get the output config
            $out = "SELECT * FROM user_remotii_output_config WHERE user_remotii_id = '" . $custData ['user_remotii_id'] . "'";
            $outR = $this->adapter->query($out);
            $resultsOut[] = $outR->execute()->getResource()->fetchAll(\PDO::FETCH_ASSOC);
        }
        // generating the custom array to diaplay
        $responseConfig = array();
        $responseConfig ['baseRec'] = $results;
        $responseConfig ['inConfig'] = $resultsIn;
        $responseConfig ['outConfig'] = $resultsOut;
        return $responseConfig;
    }
    public function getSpRemotiiIOconf($userId) {
        $q = "SELECT ur.view_status as urv , r.*, ur.* ,ur.is_default as ur_default FROM
                remotii r INNER JOIN user_remotii ur ON ( r.remotii_id = ur.remotii_id )
                
                WHERE
                (ur.user_id ='" . $userId . "')
                ORDER BY ur.user_remotii_id DESC
                ";
        $stmt = $this->adapter->query($q);
        $results = $stmt->execute()->getResource()->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($results as $custData) {
            // Query to get the input config
            $in = "SELECT * FROM user_remotii_input_config WHERE user_remotii_id = '" . $custData ['user_remotii_id'] . "'";
            $inR = $this->adapter->query($in);
            $resultsIn[] = $inR->execute()->getResource()->fetchAll(\PDO::FETCH_ASSOC);
            // Query to get the output config
            $out = "SELECT * FROM user_remotii_output_config WHERE user_remotii_id = '" . $custData ['user_remotii_id'] . "'";
            $outR = $this->adapter->query($out);
            $resultsOut[] = $outR->execute()->getResource()->fetchAll(\PDO::FETCH_ASSOC);
        }
        // generating the custom array to diaplay
        $responseConfig = array();
        $responseConfig ['baseRec'] = $results;
        $responseConfig ['inConfig'] = $resultsIn;
        $responseConfig ['outConfig'] = $resultsOut;
        return $responseConfig;
    }
    public function getPeriodicallyUserRemotiiIOconf($userId, $time) {
        $q = "SELECT sr.view_status as srv,ur.view_status as urv , sr.*, r.*, ur.* FROM
                remotii r INNER JOIN user_remotii ur ON ( r.remotii_id = ur.remotii_id )
                 LEFT JOIN shared_remotii sr ON (ur.user_remotii_id = sr.user_remotii_id AND sr.shared_user_id =$userId)
                WHERE
                (ur.user_id ='" . $userId . "' OR  sr.shared_user_id = '" . $userId . "') 
                AND (ur.updated_on >= $time OR r.remotii_last_received_time >= $time)
                ORDER BY ur.user_remotii_id DESC
                ";
        $stmt = $this->adapter->query($q);
        $results = $stmt->execute()->getResource()->fetchAll(\PDO::FETCH_ASSOC);
        $remotiiData = array();
        $i = 0;
        foreach ($results as $custData) {
            // Query to get the input config
            $in = "SELECT * FROM user_remotii_input_config WHERE user_remotii_id = '" . $custData ['user_remotii_id'] . "'";
            $inR = $this->adapter->query($in);
            $resultsIn = $inR->execute()->getResource()->fetchAll(\PDO::FETCH_ASSOC);
            // Query to get the output config
            $out = "SELECT * FROM user_remotii_output_config WHERE user_remotii_id = '" . $custData ['user_remotii_id'] . "'";
            $outR = $this->adapter->query($out);
            $resultsOut = $outR->execute()->getResource()->fetchAll(\PDO::FETCH_ASSOC);
            $remotiiData[$i] = $custData;
            $remotiiData[$i]['inConfig'] = $resultsIn;
            $remotiiData[$i]['outConfig'] = $resultsOut;
            $i++;
        }
        // generating the custom array to diaplay
        $responseConfig = array();
        $responseConfig ['baseRec'] = $remotiiData;
        return $responseConfig;
    }
    /**
     * Changes remotii status to ACTIVE or INACTIVE
     *
     * @author emp24
     */
    public function assignDefaultRemotii($userId, $remotiiId, $userRemotiiId, $isShared) {
        if ($userId && $remotiiId) {
            if ($isShared) {
                $query = "
	    			UPDATE user_remotii SET is_default = '" . INACTIVE . "' WHERE user_id= '$userId'; 
	    			UPDATE shared_remotii SET is_default = '" . INACTIVE . "' WHERE shared_user_id= '$userId'; 
	    			UPDATE shared_remotii SET is_default = '" . ACTIVE . "' WHERE user_remotii_id = '$userRemotiiId' AND shared_user_id= '$userId' ";
            } else {
                $query = "
	    			UPDATE user_remotii SET is_default = '" . INACTIVE . "' WHERE user_id= '$userId'; 
                                UPDATE shared_remotii SET is_default = '" . INACTIVE . "' WHERE shared_user_id= '$userId'; 
	    			UPDATE user_remotii SET is_default = '" . ACTIVE . "' WHERE remotii_id = '$remotiiId'; 
                        ";
            }
            $queryExeA = $this->adapter->query($query);
            $queryExeA->execute();
        } elseif ($userId) {
            $query = "
	    		    UPDATE user_remotii SET is_default = '" . INACTIVE . "' WHERE user_id= '$userId';
                            UPDATE shared_remotii SET is_default = '" . INACTIVE . "' WHERE shared_user_id= '$userId'; 
	    			";
            $queryExeA = $this->adapter->query($query);
            $queryExeA->execute();
        } else {
            return array(
                'status' => 'FAIL',
                'message' => 'Please provide both user id and remotii id'
            );
        }
        return array(
            'status' => 'OK',
            'remotii_id' => $remotiiId
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
        $queryExeA = $this->adapter->query($qry);
        $queryExeA->execute();
        // Query to get the output config
        $out = "SELECT * FROM user_remotii_output_config WHERE config_id = '" . $configId . "'";
        $outR = $this->adapter->query($out);
        $resultsOut = $outR->execute()->getResource()->fetchAll(\PDO::FETCH_ASSOC);
        $returnData = $resultsOut [0];
        return $returnData;
    }
    function changeOBRemotiiPin($params) {
        $macAddress = $params ['mac_address'];
        $remotiiId = $params ['remotii_id'];
        $tx_type = $params ['tx_type'];
        if ($tx_type == "GPIO_TGL_REQ") {
            $pulse_time = $params['pulse_time'];
            $qry = "INSERT INTO outbound SET 
    				remotii_id = '" . $remotiiId . "',
    				mac_address = '" . $macAddress . "',
                                tx_type='$tx_type',
                                dout_tgl='" . $params['dout_tgl'] . "',
                                dout_tgl_dly_ms	='" . $pulse_time . "'";
        } elseif ($tx_type == "GPIO_SET_REQ") {
            $qry = "INSERT INTO outbound SET 
    				remotii_id = '" . $remotiiId . "',
    				mac_address = '" . $macAddress . "',
                                tx_type='$tx_type',
                                dout_set = '" . $params['dout_set'] . "',
                                dout_clr = '" . $params['dout_clr'] . "'";
        }
        $queryExeA = $this->adapter->query($qry);
        $queryExeA->execute();
    }
    /**
     *
     * @param type $rid        	
     * @return type
     */
    public function getClientById($uid) {
        $query = "SELECT * FROM user u WHERE u.user_id = '" . $uid . "'";
        $dataPro = $this->adapter->query($query);
        $results = $dataPro->execute()->getResource()->fetchAll(\PDO::FETCH_OBJ);
        return $results;
    }
    public function getInboundData($remotiiId, $last_message_id = '0', $lessThenMessage = false, $limit = '100') {
        $limit = (int) $limit;
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
	    		  						WHERE remotii_id = " . $this->platform->quoteValue($remotiiId) . " 
    							)
    						AND  message_id $co " . $this->platform->quoteValue($last_message_id) . " GROUP by receive_time,din,dout	
    			  ORDER BY  message_id DESC LIMIT $limit";
        $dataPro = $this->adapter->query($query);
        $results = $dataPro->execute()->getResource()->fetchAll(\PDO::FETCH_OBJ);
        return $results;
    }
    public function remotiiNameExists($remotiiName, $remotiiId, $userId) {
        $query = "select remotii_name from user_remotii where remotii_name=" . $this->platform->quoteValue($remotiiName) . " AND remotii_id != " . $this->platform->quoteValue($remotiiId) . " AND user_id= " . $this->platform->quoteValue($userId) . "";
        $stmt = $this->adapter->query($query);
        $result = $stmt->execute()->getResource()->fetchColumn();
        return $result;
    }
    public function getClientInputConfig($urid) {
        $q = "select * from user_remotii_input_config where user_remotii_id = '$urid'";
        $stmt = $this->adapter->query($q);
        $results = $stmt->execute()->getResource()->fetchAll(\PDO::FETCH_ASSOC);
        return $results;
    }
    public function getClientOutputConfig($urid) {
        $q = "select * from user_remotii_output_config where user_remotii_id = '$urid'";
        $stmt = $this->adapter->query($q);
        $results = $stmt->execute()->getResource()->fetchAll(\PDO::FETCH_ASSOC);
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
        if (empty($userId)) {
            return array(
                'status' => 'FAIL',
                'message' => 'User Id is required'
            );
        }
        if (empty($remotiiId) && empty($remotiiMacAddress)) {
            $q = "select count(*) from user_remotii where user_id =" . $this->platform->quoteValue($userId);
            $stmt = $this->adapter->query($q);
            $count = $stmt->execute()->getResource()->fetchColumn();
            if ($count > 0) {
                return array(
                    'status' => 'Success',
                );
            } else {
                return array(
                    'status' => ($count ? 'OK' : 'FAIL')
                );
            }
        }
        if ($remotiiId) {
            $q = "select count(*) from user_remotii where remotii_id =" . $this->platform->quoteValue($remotiiId);
        } else if ($remotiiMacAddress) {
            $q = "select count(*) from user_remotii where remotii_id =" . $this->platform->quoteValue($remotiiId);
        }
        $stmt = $this->adapter->query($q);
        $count = $stmt->execute()->getResource()->fetchColumn();
        return array(
            'status' => ($count ? 'OK' : 'FAIL')
        );
    }
    public function getEUByMac($mac) {
        $q = "select u.* from user u 
						where user_id = ( select user_id from remotii r inner join user_remotii ur 
											on(r.remotii_id = ur.remotii_id) 
											where r.mac_address='$mac')";
        $stmt = $this->adapter->query($q);
        return $result = $stmt->execute()->getResource()->fetch(\PDO::FETCH_ASSOC);
    }
    /**
     * 
     * @return type
     */
    public function getLoggedInuserInfo($uid) {
        $qry = "SELECT * FROM user WHERE user_id = '" . $uid . "'";
        $dataPro = $this->adapter->query($qry);
        $results = $dataPro->execute()->getResource()->fetchAll(\PDO::FETCH_OBJ);
        return $results[0];
    }
    /**
     * 
     * @param type $remotiiId
     */
    public function getUserRemotiiLastPaymentStatus($remotiiId) {
        $qry = "SELECT ur.payment_status, sp.allow_end_user_billing
                        FROM remotii r 
                        INNER JOIN user_remotii ur ON (r.remotii_id = ur.remotii_id) 
                        INNER JOIN service_provider sp ON ( sp.service_provider_id = r.service_provider_id)
                    WHERE r.remotii_id = '" . $remotiiId . "' 
                    ";
        $dataPro = $this->adapter->query($qry);
        $details = $dataPro->execute()->getResource()->fetchAll(\PDO::FETCH_OBJ);
        return $details[0];
    }
    /**
     * 
     * @return type
     */
    public function chkSPconfigSetting($macId) {
        $qry = "SELECT count(r.remotii_id) as totalCount
                        FROM remotii r 
                        INNER JOIN  service_provider_input_config spin ON (r.service_provider_id = spin.service_provider_id) 
                        INNER JOIN service_provider_output_config spout ON ( r.service_provider_id = spout.service_provider_id) 
                    WHERE r.mac_address = '" . $macId . "' 
                    ";
        $dataPro = $this->adapter->query($qry);
        $details = $dataPro->execute()->getResource()->fetchAll(\PDO::FETCH_OBJ);
        return $details[0]->totalCount;
    }
    public function updateViewStatusInSharedRemotii($status, $user_remotii_id, $shared_user_id) {
        $queryAdd = "UPDATE shared_remotii SET view_status=$status 
        where user_remotii_id='$user_remotii_id' AND shared_user_id='$shared_user_id'";
        $queryExeA = $this->adapter->query($queryAdd);
        $queryExeA->execute();
        return true;
    }
    public function updateViewStatusInUserRemotii($status, $user_remotii_id) {
        $queryAdd = "UPDATE user_remotii SET view_status=$status 
      where user_remotii_id='$user_remotii_id'";
        $queryExeA = $this->adapter->query($queryAdd);
        $queryExeA->execute();
        return true;
    }
    public function deleteEventData($outputresult, $remotiiId, $inputresult) {
        $query = "select id,input_bits,output_bits from event_scheduler where remotii_id='$remotiiId'";
        $dataPro = $this->adapter->query($query);
        $details = $dataPro->execute()->getResource()->fetchAll(\PDO::FETCH_ASSOC);
        // _pre($details);
        $i = 0;
        // $event_id = array();
        $input_bits = array();
        $output_bits = array();
        $input_pin_num = array();
        $output_pin_num = array();
        foreach ($details as $data) {
            $event_id = $data['id'];
            $input_bits[$i] = $data['input_bits'];
            $output_bits[$i] = $data['output_bits'];
            $j = 0;
            $k = 0;
            if ($input_bits[$i] & 1) {
                $input_pin_num[$j] = 1;
                $j++;
            }
            if ($input_bits[$i] & 2) {
                $input_pin_num[$j] = 2;
                $j++;
            }
            if ($input_bits[$i] & 4) {
                $input_pin_num[$j] = 3;
                $j++;
            }
            if ($input_bits[$i] & 8) {
                $input_pin_num[$j] = 4;
                $j++;
            }
            if ($output_bits[$i] & 1) {
                $output_pin_num[$k] = 1;
                $k++;
            }
            if ($output_bits[$i] & 2) {
                $output_pin_num[$k] = 2;
                $k++;
            }
            if ($output_bits[$i] & 4) {
                $output_pin_num[$k] = 3;
                $k++;
            } //$id = implode(",", $event_id);
            if (count(array_intersect($inputresult, $input_pin_num)) > 0) {
                $query = "delete from event_scheduler where id =$event_id";
                $stmt = $this->adapter->query($query);
                $results = $stmt->execute();
            }
            if (count(array_intersect($outputresult, $output_pin_num)) > 0) {
                $query = "delete from event_scheduler where id=$event_id";
                $stmt = $this->adapter->query($query);
                $results = $stmt->execute();
            }
            $i++;
        }
    }
    public function checkdeleteData($outputresult, $remotiiId, $inputresult) {
        $query = "select id,input_bits,output_bits from event_scheduler where remotii_id='$remotiiId'";
        $dataPro = $this->adapter->query($query);
        $details = $dataPro->execute()->getResource()->fetchAll(\PDO::FETCH_ASSOC);
        $i = 0;
        $input_bits = array();
        $output_bits = array();
        $input_pin_num = array();
        $output_pin_num = array();
        foreach ($details as $data) {
            $input_bits[$i] = $data['input_bits'];
            $output_bits[$i] = $data['output_bits'];
            $event_id = $data['id'];
            $j = 0;
            $k = 0;
            if ($input_bits[$i] & 1) {
                $input_pin_num[$j] = 1;
                $j++;
            }
            if ($input_bits[$i] & 2) {
                $input_pin_num[$j] = 2;
                $j++;
            }
            if ($input_bits[$i] & 4) {
                $input_pin_num[$j] = 3;
                $j++;
            }
            if ($input_bits[$i] & 8) {
                $input_pin_num[$j] = 4;
                $j++;
            }
            if ($output_bits[$i] & 1) {
                $output_pin_num[$k] = 1;
                $k++;
            }
            if ($output_bits[$i] & 2) {
                $output_pin_num[$k] = 2;
                $k++;
            }
            if ($output_bits[$i] & 4) {
                $output_pin_num[$k] = 3;
                $k++;
            }
            if (count(array_intersect($inputresult, $input_pin_num)) > 0) {
                return true;
            }
            if (count(array_intersect($outputresult, $output_pin_num)) > 0) {
                return true;
            }
            $i++;
        }
        return false;
    }
    public function getIsDefaultRemotii($userId) {
        echo $query = "SELECT ur.remotii_id
                    FROM user_remotii ur 
                    LEFT JOIN shared_remotii sr ON(ur.user_remotii_id = sr.user_remotii_id AND sr.shared_user_id =$userId AND sr.is_default=1)
                    WHERE ((ur.user_id = $userId) OR (sr.shared_user_id =$userId)) AND (ur.is_default=1 OR sr.is_default=1)	
";
        $dataPro = $this->adapter->query($query);
        $details = $dataPro->execute()->getResource()->fetch(\PDO::FETCH_ASSOC);
        return $details;
    }
    public function getIsDefaultSpRemotii($userId) {
        $query = "SELECT ur.remotii_id
                    FROM user_remotii ur 
                    WHERE (ur.user_id = $userId) AND (ur.is_default=1)	
";
        $dataPro = $this->adapter->query($query);
        $details = $dataPro->execute()->getResource()->fetch(\PDO::FETCH_ASSOC);
        return $details;
    }
    public function getRemotiiHeartBeatTime($remotiiId) {
        $query = "SELECT remotii_last_heartbeat_received_time from remotii where remotii_id='$remotiiId'
";
        $dataPro = $this->adapter->query($query);
        $details = $dataPro->execute()->getResource()->fetch(\PDO::FETCH_ASSOC);
        return $details;
    }
//    public function updateFlagInNoitificationEmail($params) {
//
//        $query = "UPDATE notification_email SET SoundFlag= 1
//      where mac_address='$params[mac_address]' ";
//        $queryExeA = $this->adapter->query($query);
//        $queryExeA->execute();
//
//        return true;
//    }
    public function getUserRemotiiId($remotiiId) {
        $query = "SELECT user_remotii_id from user_remotii where remotii_id='$remotiiId'
";
        $dataPro = $this->adapter->query($query);
        $details = $dataPro->execute()->getResource()->fetch(\PDO::FETCH_COLUMN);
        return $details;
    }
    public function getSPInputConfig($userRemotiiId) {
        $q = "select * from user_remotii_input_config where user_remotii_id = '$userRemotiiId'";
        $stmt = $this->adapter->query($q);
        $results = $stmt->execute()->getResource()->fetchAll(\PDO::FETCH_ASSOC);
        return $results;
    }
    public function getSPOutputConfig($userRemotiiId) {
        $q = "select * from user_remotii_output_config where user_remotii_id = '$userRemotiiId'";
        $stmt = $this->adapter->query($q);
        $results = $stmt->execute()->getResource()->fetchAll(\PDO::FETCH_ASSOC);
        return $results;
    }
    public function getUserId($macId) {
        $q = "SELECT user_id FROM
	            user_remotii ur INNER JOIN remotii r ON(ur.remotii_id = r.remotii_id)
	            WHERE r.mac_address = '" . $macId . "'";
        $stmt = $this->adapter->query($q);
        $results = $stmt->execute()->getResource()->fetch(\PDO::FETCH_COLUMN);
        return $results;
    }
    public function getNumbersList() {
        $q = "SELECT name,value FROM numbers_table";
        $stmt = $this->adapter->query($q);
        $results = $stmt->execute()->getResource()->fetchAll(\PDO::FETCH_ASSOC);
        return $results;
    }
    public function getNumbers() {
        $q = "SELECT value FROM numbers_table";
        $stmt = $this->adapter->query($q);
        $results = $stmt->execute()->getResource()->fetchAll(\PDO::FETCH_COLUMN);
        return $results;
    }
    public function getSPIDByRemotiiMacAddress($macAddress) {
        $query = "select service_provider_id from remotii where mac_address = " . $this->platform->quoteValue($macAddress) . " LIMIT 1";
        $stmt = $this->adapter->query($query);
        $spid = $stmt->execute()->getResource()->fetchColumn();
        return $spid;
    }
    public function getSPInputConfigNew($spid) {
        $q = "select * from service_provider_input_config where service_provider_id = '$spid'";
        $stmt = $this->adapter->query($q);
        $results = $stmt->execute()->getResource()->fetchAll(\PDO::FETCH_ASSOC);
        return $results;
    }
    public function getSPOutputConfigNew($spid) {
        $q = "select * from service_provider_output_config where service_provider_id = '$spid'";
        $stmt = $this->adapter->query($q);
        $results = $stmt->execute()->getResource()->fetchAll(\PDO::FETCH_ASSOC);
        return $results;
    }
    /**
     * Implemented for deleting sp row in user_remotii
     */
    function deleteUserRemotiiOfSp($remotiiId) {
        $tempDestOutput = implode('', $post['dest_radioState']);
        $tempDestOutputDecimal = bindec($tempDestOutput);
        $tempSrcOutput = implode('', $post['radioState']);
        $tempSrcOutputDecimal = bindec($tempDestOutput);
        $chainedEevent['event_name'] = $post['event_name'];
        $chainedEevent['source_remotii'] = $rid;
        $chainedEevent['destination_remotii'] = $post['triggering_remotii'];
        $chainedEevent['trigger_condition'] = $tempDestOutputDecimal;
        $chainedEevent['input_condition'] = $tempSrcOutputDecimal;
        $query = "delete from user_remotii where remotii_id=" . $this->platform->quoteValue($remotiiId);
        $stmt = $this->adapter->query($query);
        $results = $stmt->execute();
    }
    public function insertChainedEvent($post, $rid) {
        $destDecOutputs = 0;
        $d_Set = 0;
        $d_Clr = 0;
        $arrayDestMomentary = $post['dest_IsMomentary'];
        $arrayDestState = $post['dest_radioState'];
        $destOutputNameArray = $post['dest_output'];
        $arrayDestOutputPin = $post['dest_pin_number'];
        $d_tgl = 0;
        $tx_type = '';
        //destination remotii
        foreach ($destOutputNameArray as $desrOutputName) {
            $destPinNumber = $post['dest_pin_output_config_id'][$desrOutputName];
            $destAddValue = pow(2, ($destPinNumber - 1));
            $destDecOutputs += $destAddValue;
            if ($arrayDestMomentary[$desrOutputName] == 1) {
                $d_Set = $d_Set + $destAddValue;
                $d_Clr = $d_Clr + $destAddValue;
            } else {
                if ($arrayDestState[$desrOutputName] == 1) {
                    $d_Set = $d_Set + $destAddValue;
                } else {
                    $d_Clr = $d_Clr + $destAddValue;
                }
            }
        }
        //src remotii output
        $srcDecOutputs = 0;
        $scr_d_Set = 0;
        $src_d_Clr = 0;
        $arraySrcMomentary = $post['IsMomentary'];
        $arraySrcState = $post['radioState'];
        $srcOutputNameArray = $post['outputname'];
        $arraySrcOutputPin = $post['pin_number'];
        foreach ($srcOutputNameArray as $srcOutputName) {
            $srcPinNumber = $post['pin_output_config_id'][$srcOutputName];
            $srcAddValue = pow(2, ($srcPinNumber - 1));
            $srcDecOutputs += $srcAddValue;
            if ($arraySrcMomentary[$srcOutputName] == 1) {
                $scr_d_Set = $scr_d_Set + $srcAddValue;
                $src_d_Clr = $src_d_Clr + $srcAddValue;
            } else {
                if ($arraySrcState[$srcOutputName] == 1) {
                    $scr_d_Set = $scr_d_Set + $srcAddValue;
                } else {
                    $src_d_Clr = $src_d_Clr + $srcAddValue;
                }
            }
        }
        //src remotii input
        $DecInputs = 0;
        $input_cond = 0;
        $input_bits = $post['radio1'];
        $inputNameArray = $post['inputname'];
        $arrayInputPin = $post['pin_number1'];
        foreach ($inputNameArray as $inputName) {
            $pinNumber = $post['pin_input_config_id'][$inputName];
            $AddValue = pow(2, ($pinNumber - 1));
            $DecInputs += $AddValue;
            if (!empty($post['radio1'][$inputName])) {
                $input_cond+=$AddValue;
            }
        }
        $chainedEevent['event_name'] = $post['event_name'];
        $chainedEevent['source_remotii'] = $rid;
        $chainedEevent['source_output_bits'] = $srcDecOutputs;
        $chainedEevent['source_input_bits'] = $DecInputs;
        $chainedEevent['source_input_condition'] = $input_cond;
        $chainedEevent['source_output_condition'] = $scr_d_Set;
        $chainedEevent['destination_remotii'] = $post['triggering_remotii'];
        $chainedEevent['dest_output_bits'] = $destDecOutputs;
        $chainedEevent['trigger_condition'] = $d_Set;
        $chainedEevent['event_status'] = 1;
        $chainedEevent['condition_type'] = $post['condition'];
        $chainedEevent['config_change'] = 0;
        $sql = new Sql($this->adapter);
        $insert = $sql->insert('chained_event');
        $insert->values($chainedEevent);
        $selectString = $sql->getSqlStringForSqlObject($insert);
        $results = $this->adapter->query($selectString, Adapter::QUERY_MODE_EXECUTE);
        $event_id = $this->adapter->getDriver()->getLastGeneratedValue();
//        $arrayDestMomentary = $post['dest_IsMomentary'];
//        $arrayDestState = $post['dest_radioState'];
//        $destOutputNameArray = $post['dest_output'];
//        $arrayDestOutputPin = $post['dest_pin_number'];
        foreach ($destOutputNameArray as $desrOutputName) {
            $d_Set = 0;
            $d_Clr = 0;
            $d_tgl = 0;
            $pinNumber = $post['dest_pin_output_config_id'][$desrOutputName];
            $AddValue = pow(2, ($pinNumber - 1));
            if ($arrayDestMomentary[$desrOutputName] == 1) {
                $d_tgl = $AddValue;
                $tx_type = 'GPIO_TGL_REQ';
            } else {
                if ($arrayDestState[$desrOutputName] == 1) {
                    $d_Set = $AddValue;
                } else {
                    $d_Clr = $AddValue;
                }
                $tx_type = 'GPIO_SET_REQ';
            }
            $queryPins = "insert into chained_event_pins
                         (chained_event_id, 
                          output_config_id, 
                          tx_type, dout_set, 
                          dout_clr, 
                          dout_tgl) 
                          values 
                          ('$event_id',
                           '$desrOutputName',
                           '$tx_type',
                           '$d_Set',
                           '$d_Clr',
                           '$d_tgl')";
            $queryPinsresult = $this->adapter->query($queryPins);
            $queryPinsresult->execute();
        }
        return true;
    }
    public function getChainedEvents($rid) {
        $q = "select ce.*,r_src.remotii_name as src_remotii_name,r_dest.remotii_name as dest_remotii_name from chained_event ce
            left join user_remotii r_src on r_src.remotii_id=ce.source_remotii 
            left join user_remotii r_dest on r_dest.remotii_id=ce.destination_remotii where ce.source_remotii ='$rid' ORDER BY ce.ce_id DESC";
        $stmt = $this->adapter->query($q);
        $results = $stmt->execute()->getResource()->fetchAll(\PDO::FETCH_ASSOC);
        return $results;
    }
    public function getAllChainedEvents() {
        $sql = new Sql($this->adapter);
        $select = $sql->select('*');
        $select->from('chained_event');
        $statement = $sql->getSqlStringForSqlObject($select);
        $results = $statement->execute();
        return $results;
    }
    public function disableChainedEvent($remotiiId) {
        $sql = 'select ce.ce_id from chained_event ce '
                . 'left join chained_event_pins cp on cp.chained_event_id=ce.ce_id'
                . ' where (ce.source_remotii =' . $remotiiId . ' OR ce.destination_remotii=' . $remotiiId . ')';
        $stmt = $this->adapter->query($sql);
        $results = $stmt->execute()->getResource()->fetchAll(\PDO::FETCH_ASSOC);
        if (count($results) > 0) {
            foreach ($results as $ce_Id) {
                $ce_Ids[] = $ce_Id['ce_id'];
            }
            $ceIds = implode(',', $ce_Ids);
            $qry = "update chained_event set event_status=0 , config_change=1 where ce_id IN($ceIds)";
            $queryresult = $this->adapter->query($qry);
            $queryresult->execute();
        }
        return $results;
    }
}
