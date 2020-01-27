<?php

namespace RemotiiModels\Model;

use Zend\Db\Adapter\Adapter;
use Zend\Db\TableGateway\AbstractTableGateway;
use Zend\Crypt\Password\Bcrypt;

class ServiceProvider extends AbstractTableGateway {

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

    public function fetchAll() {
        $q = "select * from service_provider";
        $stmt = $this->adapter->query($q);
        $results = $stmt->execute()->getResource()->fetchAll(\PDO::FETCH_ASSOC);
        return $results;
    }

    public function shutOnOffRemotiis($remotiiIds, $status = ACTIVE, $suspendedBy = SUSPENDED_BY_ADMIN) {
        if (empty($remotiiIds)) {
            return array('status' => 'FAIL', 'message' => 'No remotii provided');
        } else {
            $remotiiIdsStr = implode(',', $remotiiIds);
            //$query = "update remotii set remotii_status='".$status."' where remotii_status <> '".$suspendedBy."' and remotii_id IN( $remotiiIdsStr )";


            $query = "update remotii set remotii_status= case when remotii_status ='" . ACTIVE . "' then '" . SUSPENDED . "' when remotii_status ='" . SUSPENDED . "' then '" . ACTIVE . "' end  where remotii_id IN( $remotiiIdsStr )";
            $queryExe = $this->adapter->query($query);
            $queryExe->execute();

//                $query = "update remotii set remotii_status='".SUSPENDED."' where remotii_status = '".ACTIVE."' and remotii_id IN( $remotiiIdsStr )";
//    		$queryExe = $this->adapter->query( $query );
//    		$queryExe->execute();
//    		if($status <> ACTIVE) {
            $userId = $this->getUserIdByRemotiiId($remotiiIds[0]);
            $this->setDefaultRemotiiRandomly($userId);
//    		}
        }
        return true;
    }

    public function getUserIdByRemotiiId($remotiiId) {
        $q = "select user_id from user_remotii where remotii_id = '$remotiiId'";
        $stmt = $this->adapter->query($q)->execute();
        return $userId = $stmt->getResource()->fetchColumn();
    }

    public function getUserIdsByRemotiiIds($remotiiIds) {
        $remotiiIdsStr = trim(implode(',', $remotiiIds), ',');
        $q = "select user_id from user_remotii where remotii_id in($remotiiIdsStr) GROUP BY user_id";
        $stmt = $this->adapter->query($q)->execute();
        return $userIds = $stmt->getResource()->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Set enduser remotii status to default randomly
     * 
     * @param $enduserUserId int
     * @return bool
     */
    public function setDefaultRemotiiRandomly($enduserUserId) {
        $q1 = "select count(*) from remotii r inner join user_remotii ur on(r.remotii_id=ur.remotii_id) 
    				where r.remotii_status=" . ACTIVE . " and ur.user_id='$enduserUserId' and ur.is_default=1";
        $stmt = $this->adapter->query($q1);
        $isDefaultRemotiiExists = $stmt->execute()->getResource()->fetchColumn();
        if ($isDefaultRemotiiExists == 0) {
            $q2 = "
    				update user_remotii set is_default=0 where user_id='$enduserUserId';
    				set @remotii_id := (
	    					select ur.remotii_id from user_remotii ur inner join remotii r on(ur.remotii_id=r.remotii_id)
	    					where ur.user_id='$enduserUserId' and r.remotii_status=" . ACTIVE . " limit 1
    			   		);
    			   update user_remotii set is_default=1 where remotii_id=@remotii_id";
            $stmt = $this->adapter->query($q2);
            $stmt->execute();
        }
    }

    public function getUserRemotiis($params) {
        $uid = $params['user_id'];
        $spid = $params['service_provider_id'];
        $fields = $params['fields'] ? $params['fields'] : "*";
        $fetch = $params['fetch'];
        $where = $params['where'];

        $query = "select $fields from
    	user_remotii ur INNER JOIN remotii r ON(ur.remotii_id = r.remotii_id)
    	where user_id='$uid'";


        if ($spid) {
            $query = $query . " and r.service_provider_id='$spid'";
        }

        $query = $query . $where;

        $dataPro = $this->adapter->query($query);
        if ($fetch == 'column') {
            $results = $dataPro->execute()->getResource()->fetchAll(\PDO::FETCH_COLUMN);
        } else {
            $results = $dataPro->execute()->getResource()->fetchAll(\PDO::FETCH_ASSOC);
        }
        return $results;
    }

    public function getServiceProviderSummary($spid) {
        $billingStartDate = date('Y-m-d', strtotime(date('Y-m', strtotime('-1 month'))));
        $billingEndDate = date('Y-m-d', strtotime(date('Y-m')));

        $query = "SELECT COUNT(*) FROM user_remotii ur LEFT JOIN remotii r  
    				ON( ur.remotii_id = r.remotii_id ) WHERE r.service_provider_id = '$spid'";
        $dataPro = $this->adapter->query($query);
        $return['totalClient'] = $dataPro->execute()->getResource()->fetchColumn();

        $query = "SELECT COUNT(*) FROM remotii r WHERE r.service_provider_id = '$spid' AND r.remotii_status = '" . ACTIVE . "'";
        $dataPro = $this->adapter->query($query);
        $return['activeRemotiis'] = $dataPro->execute()->getResource()->fetchColumn();

        $query = "SELECT COUNT(*) FROM remotii r 
    				WHERE r.service_provider_id = '$spid' 
    				AND ( r.remotii_status = '" . SUSPENDED . "' OR r.remotii_status = '" . SUSPENDED_BY_ADMIN . "')";
        $dataPro = $this->adapter->query($query);
        $return['inActiveRemotiis'] = $dataPro->execute()->getResource()->fetchColumn();



        /*
         * Count Suspended Users of Service Provider ( $DU )
         */
        $query = "SELECT
				  	TRUNCATE(SUM( sp.contracted_price ),2) AS contracted_price_total,
				  	TRUNCATE(SUM( sp.end_user_price * sp.service_fee / 100 ), 2) AS servie_fee
				FROM
					service_provider sp
					INNER JOIN remotii r ON ( r.service_provider_id = sp.service_provider_id )
					INNER JOIN user_remotii ur ON( ur.remotii_id = r.remotii_id )
				WHERE
					sp.acc_status = " . SUSPENDED . " AND
					r.remotii_status = " . SUSPENDED . " OR r.remotii_status = " . SUSPENDED_BY_ADMIN . " ) AND
    				sp.service_provider_id='$spid'";

        $query = "select 
    				IFNULL(sp.end_user_price, 0) AS end_user_price, 
    				sp.service_fee, 
    				sp.contracted_price,
    				sp.allow_end_user_billing,
		    		( select count(*) from 
			    		user_remotii ur INNER JOIN remotii r ON(ur.remotii_id=r.remotii_id and r.service_provider_id='$spid')
		    		    where r.remotii_status <> " . SUSPENDED . " AND r.remotii_status <> " . SUSPENDED_BY_ADMIN . "
                    ) AS total_user_remotiis,
		    		( select count(*) from 
			    		remotii r where r.service_provider_id='$spid'
		    		) AS total_remotiis 
	    			from service_provider sp
    				where sp.service_provider_id='$spid'";

        $dataPro = $this->adapter->query($query);
        $serviceProviderInfo = $dataPro->execute()->getResource()->fetch();

        $contractedPrice = $serviceProviderInfo['contracted_price'];
        $endUserPrice = $serviceProviderInfo['end_user_price'];
        $totalUserRemotiis = $serviceProviderInfo['total_user_remotiis'];
        $totalRemotiis = $serviceProviderInfo['total_remotiis'];
        $serviceFee = $serviceProviderInfo['service_fee'];

        if ($serviceProviderInfo['allow_end_user_billing']) {
            $query = "SELECT TRUNCATE(IFNULL(SUM( amount ),0),0) from payment_stats where payment_id in
                    ( select last_payment_stat_id FROM service_provider sp where sp.service_provider_id = '$spid')";

            $dataPro = $this->adapter->query($query);
            $netIncomeInPBC = '$' . $dataPro->execute()->getResource()->fetchColumn();
        } else {
            $netIncomeInPBC = 'N/A';
        }

        //echo "EUP:".$endUserPrice.'<br>TUR:'.$totalUserRemotiis.'<br>SF:'.$serviceFee.'<br>CP:'.$contractedPrice.'<br>TR:'.$totalRemotiis;exit;

        $expectedIncomeInNBCSF =
                ( $endUserPrice * $totalUserRemotiis * $serviceFee ) / 100;

        if ($endUserPrice * $totalUserRemotiis - $expectedIncomeInNBCSF) {
            $expectedIncomeInNBC = $endUserPrice * $totalUserRemotiis - $expectedIncomeInNBCSF;
        } else {
            $expectedIncomeInNBC = 0.00;
        }

        if ($return['inActiveRemotiis']) {
            $inActiveIncome = $return['inActiveRemotiis'] * $endUserPrice;
        } else {
            $inActiveIncome = 0.00;
        }



        if ($serviceProviderInfo['allow_end_user_billing']) {
            $expectedAmountInNBC = ( $contractedPrice * $totalRemotiis ) - ( $contractedPrice * $totalRemotiis * $serviceFee / 100 );
        } else {
            $expectedAmountInNBC = ( $contractedPrice * $totalRemotiis );
        }

        $return['expectedAmountInNBC'] = $expectedAmountInNBC ? $expectedAmountInNBC : 'N/A';
        $return['expectedIncomeInNBCSF'] = $expectedIncomeInNBCSF;
        $return['netIncomeInPBC'] = $netIncomeInPBC;
        $return['expectedIncomeInNBC'] = $expectedIncomeInNBC;
        $return['inActiveIncome'] = $inActiveIncome;

        return $return;
    }

    /**
     * 
     * @param type $params
     * @return type
     */
    public function getSPPaymentsInfo($params = "", $spId) {
        if (!empty($params)) {
            $fromDate = $params['fromDate'];
            $toDate = $params['toDate'];
            $query = "select ps.*, CONCAT_WS(' ', u.fname, u.lname) as completeName, sp.acc_status from payment_stats ps
    				INNER JOIN service_provider sp ON ( ps.user_id = sp.service_provider_id )
                                INNER JOIN service_provider_admins spa ON (spa.service_provider_id = sp.service_provider_id)
                                INNER JOIN user u ON (u.user_id = spa.user_id)
    				where ps.executed_on >= '$fromDate' AND ps.payment_cycle <= '$toDate'
                                    AND spa.user_id = '" . $spId . "'";

            $dataPro = $this->adapter->query($query);
            $results = $dataPro->execute()->getResource()->fetchAll(\PDO::FETCH_OBJ);
        }
        return $results;
    }

    /**
     * 
     * @param type $loggedInUserId
     */
    public function getServiceProviderCompId($uId) {
        $qry = "SELECT sp.service_provider_id as spId FROM 
                        service_provider sp INNER JOIN service_provider_admins spa ON (sp.service_provider_id = spa.service_provider_id) 
                        WHERE spa.user_id = '" . $uId . "'";
        $exeQry = $this->adapter->query($qry);
        $results = $exeQry->execute()->getResource()->fetchAll(\PDO::FETCH_OBJ);
        return $results[0]->spId;
    }

    public function saveSPInputConfig($spInputConfig, $spid) {
        $query = "
    			delete from service_provider_input_config where service_provider_id = '$spid';";
        $queryInsert = "insert into 
    				service_provider_input_config(
    					service_provider_id, 
    					name,
		    			active_label_text,
		    			active_label_color,
		    			inactive_label_text,
		    			inactive_label_color,
		    			is_enabled,
		    			pin_number,
		    			enable_notification,
		    			notification_trigger,
		    			notification_sound,
		    			updated_by,
		    			updated_on
    				) VALUES ";

        foreach ($spInputConfig as $spic) {

            if (empty($spic))
                continue;

            $subQryArr[] = "( 
    					$spid, 
    					'" . $spic['name'] . "',
    					'" . $spic['active_label_text'] . "',
    					'" . $spic['active_label_color'] . "',
    					'" . $spic['inactive_label_text'] . "',
    					'" . $spic['inactive_label_color'] . "',
    					1,
    					'" . $spic['pin_number'] . "',
    					'" . $spic['enable_notification'] . "',
    					'" . $spic['notification_trigger'] . "',
    					'" . $spic['notification_sound'] . "',
    					$spid,		
    					'" . time() . "'
    					)";
        }

        if (!empty($subQryArr)) {
            $subQry = implode(', ', $subQryArr);
            $queryInsert = $queryInsert . $subQry;
            $query = $query . $queryInsert;
        }
        $stmt = $this->adapter->query($query);
        $stmt->execute();
        $lastInsertedId = $this->adapter->getDriver()->getLastGeneratedValue();
        return $lastInsertedId;
    }

    public function saveSPOutputConfig($spOutputConfig, $spid) {
        $query = "
    			delete from service_provider_output_config where service_provider_id = '$spid';";
        $queryInsert = "insert into 
    				service_provider_output_config(
    					service_provider_id, 
    					name,
		    			active_label_text,
		    			active_label_color,
		    			inactive_label_text,
		    			inactive_label_color,
		    			is_enabled,
		    			pin_number,
    					is_output_momentary,
    					output_initial_state,
		    			enable_notification,
		    			notification_trigger,
		    			pulse_time,
		    			updated_by,
		    			updated_on
    				) VALUES ";

        foreach ($spOutputConfig as $spoc) {

            if (empty($spoc))
                continue;

            $subQryArr[] = "( 
    					$spid, 
    					'" . $spoc['name'] . "',
    					'" . $spoc['active_label_text'] . "',
    					'" . $spoc['active_label_color'] . "',
    					'" . $spoc['inactive_label_text'] . "',
    					'" . $spoc['inactive_label_color'] . "',
    					1,
    					'" . $spoc['pin_number'] . "',
    					'" . $spoc['is_output_momentary'] . "',
    					'" . $spoc['output_initial_state'] . "',
    					'" . $spoc['enable_notification'] . "',
    					'" . $spoc['notification_trigger'] . "',
    					'" . $spoc['pulse_time'] . "',
    					$spid,		
    					'" . time() . "'
    					)";
        }

        if (!empty($subQryArr)) {
            $subQry = implode(', ', $subQryArr);
            $queryInsert = $queryInsert . $subQry;
            $query = $query . $queryInsert;
        }
        $stmt = $this->adapter->query($query);
        $stmt->execute();
        $lastInsertedId = $this->adapter->getDriver()->getLastGeneratedValue();
        return $lastInsertedId;
    }

    public function getSPInputConfig($spid) {
        $q = "select * from service_provider_input_config where service_provider_id = '$spid'";
        $stmt = $this->adapter->query($q);
        $results = $stmt->execute()->getResource()->fetchAll(\PDO::FETCH_ASSOC);
        return $results;
    }

    public function getSPOutputConfig($spid) {
        $q = "select * from service_provider_output_config where service_provider_id = '$spid'";
        $stmt = $this->adapter->query($q);
        $results = $stmt->execute()->getResource()->fetchAll(\PDO::FETCH_ASSOC);
        return $results;
    }

    public function deleteSPConfig($spid) {
        $query = "	delete from service_provider_input_config where service_provider_id = '$spid';
    			delete from service_provider_output_config where service_provider_id = '$spid';
    		 ";
        $stmt = $this->adapter->query($query);
        $stmt->execute();
    }

    public function getPreviousBillingCycle($uid) {
        $query = "select * from payment_stats where user_id = '$uid'";
    }

    public function getSPIDByRemotiiId($remotiiId) {
        $query = "select service_provider_id from remotii where remotii_id = " . $this->platform->quoteValue($remotiiId) . " LIMIT 1";
        $stmt = $this->adapter->query($query);
        $spid = $stmt->execute()->getResource()->fetchColumn();
        return $spid;
    }

    public function getSPIDByRemotiiMacAddress($macAddress) {
        $query = "select service_provider_id from remotii where mac_address = " . $this->platform->quoteValue($macAddress) . " LIMIT 1";
        $stmt = $this->adapter->query($query);
        $spid = $stmt->execute()->getResource()->fetchColumn();
        return $spid;
    }

    public function updateServiceProvider($remotiiId, $serviceProviderId) {
        if (!empty($remotiiId) && !empty($serviceProviderId)) {

            $query = "update remotii set service_provider_id= '" . $serviceProviderId . "' where remotii_id ='" . $remotiiId . "'";
            $queryExe = $this->adapter->query($query);
            $queryExe->execute();
            return TRUE;
        }
        return false;
    }

    public function updateFirmware($text, $mac_add) {
        $query = "INSERT INTO `outbound`(`mac_address`, `next_fw_version`, `tx_type`)
        VALUES ('$mac_add','$text','FW_UPDATE_REQ')";
        $queryExeA = $this->adapter->query($query);
        $queryExeA->execute();
        return true;
    }

    public function checkFirmware($text, $mac_add) {
        $query = "SELECT hwfwver from `remotii` WHERE mac_address='" . $mac_add . "' AND hwfwver !='" . $text . "'";
        $stmt = $this->adapter->query($query);
        $hwfwver = $stmt->execute()->getResource()->fetchColumn();
        return $hwfwver;
    }

    public function spHasRemotii($variables) {
        $remotiiId = $variables ['remotiiId'];
        $userId = $variables ['userId'];
        if (empty($userId)) {
            return array(
                'status' => 'FAIL',
                'message' => 'User Id is required'
            );
        }
        if ($remotiiId) {
            $q = "select remotii_id from remotii where service_provider_id =" . $userId;
        }
        $stmt = $this->adapter->query($q);
        $count = $stmt->execute()->getResource()->fetchColumn();
        return array(
            'status' => ($count ? 'OK' : 'FAIL')
        );
    }

    public function getRemotiiNameMacId($id, $modifyRemotiiId) {
        $q = "Select r.*,ur.* from remotii as r INNER JOIN   user_remotii as ur
            ON(r.remotii_id=ur.remotii_id) where r.service_provider_id=$id AND ur.remotii_id=$modifyRemotiiId";
        $result = $this->adapter->query($q);
        $data = $result->execute()->getResource()->fetchAll(\PDO::FETCH_ASSOC);
        return $data;
    }
    
    public function addNoteFromAdminToSP($data) {
        $query = "update service_provider set admin_notes='" . $data['note'] . "' where service_provider_id =" . $data['r_id'] . " ";
        $queryExe = $this->adapter->query($query);
        $status = $queryExe->execute();
        return $status;
    }
}

