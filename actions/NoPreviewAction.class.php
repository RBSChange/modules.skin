<?php
/**
 * skin_NoPreviewAction
 * @package modules.skin.actions
 */
class skin_NoPreviewAction extends f_action_BaseAction
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		return View::SUCCESS;
	}
	
	public function isSecure()
	{
		return true;
	}
}