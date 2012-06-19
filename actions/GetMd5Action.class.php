<?php
/**
 * skin_GetMd5Action
 * @package modules.skin.actions
 */
class skin_GetMd5Action extends change_JSONAction
{
	/**
	 * @param change_Context $context
	 * @param change_Request $request
	 */
	public function _execute($context, $request)
	{	
		$finalParams = $request->getParameters();
		$md5 = md5(serialize($finalParams));
		$context->getUser()->setUserNamespace(change_User::BACKEND_NAMESPACE);
		$sp = array($md5 => $finalParams);
		$skinParams = change_Controller::getInstance()->getStorage()->writeForUser('skinPreview', $sp);
		return $this->sendJSON(array('md5' => $md5));
	}
	
	public function isSecure()
	{
		return true;
	}
}