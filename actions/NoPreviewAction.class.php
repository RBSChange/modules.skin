<?php
/**
 * skin_NoPreviewAction
 * @package modules.skin.actions
 */
class skin_NoPreviewAction extends change_Action
{
	/**
	 * @param change_Context $context
	 * @param change_Request $request
	 */
	public function _execute($context, $request)
	{
		return change_View::SUCCESS;
	}
	
	public function isSecure()
	{
		return true;
	}
}