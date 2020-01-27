<?php
namespace Common\Service;

use Zend\ServiceManager\ServiceManagerAwareInterface;
use Zend\ServiceManager\ServiceManager;

class Session implements ServiceManagerAwareInterface
{
	/**
	 * @var ServiceManager
	 */
	protected $serviceManager;
	
	protected $closeSessionOnBrowserClose = true;
	
	protected $rememberMeCookieName = 'remember_me';
	
	/**
	 * Get session timeout
	 *
	 * @return string
	 */
	public function getTimeout()
	{
		return ini_get('session.cookie_lifetime');
	}
	
	/**
	 * Set session timeout
	 *
	 * @param int $seconds
	 * @return string
	 */
	public function setTimeout($seconds)
	{
		if(isset($_COOKIE["remember_me"])){
			return;
		}
		
		return ini_set('session.cookie_lifetime',$seconds);
	}
	
	public function getName()
	{
		return ini_get('session.name');
	}
	
	/**
	 * Retrieve service manager instance
	 *
	 * @return ServiceManager
	 */
	public function getServiceManager()
	{
		return $this->serviceManager;
	}
	
	/**
	 * Set service manager instance
	 *
	 * @param ServiceManager $serviceManager
	 * @return User
	 */
	public function setServiceManager(ServiceManager $serviceManager)
	{
		$this->serviceManager = $serviceManager;
		return $this;
	}
}