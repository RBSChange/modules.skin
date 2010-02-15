<?php
/**
 * skin_GetMd5Action
 * @package modules.skin.actions
 */
class skin_GetMd5Action extends f_action_BaseJSONAction
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{	
		$finalParams = $request->getParameters();
		$md5 = md5(serialize($finalParams));
		$context->getUser()->removeAttribute('skinPreview');
		$context->getUser()->setAttribute('skinPreview', array($md5 => $finalParams));
		return $this->sendJSON(array('md5' => $md5));
	}
	
	public function isSecure()
	{
		return true;
	}
}