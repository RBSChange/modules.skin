<?php
/**
 * skin_PreviewAction
 * @package modules.skin.actions
 */
class skin_PreviewAction extends f_action_BaseAction
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		$session = $context->getUser()->getAttribute('skinPreview');
		$skinParams = $session[$request->getParameter('md5')];
		$pageId = $request->getParameter('pageid');		
		$page = DocumentHelper::getDocumentInstance($pageId);
			
		$skin = DocumentHelper::getDocumentInstance($skinParams['cmpref']);	
		$tmpskin = skin_SkinService::getInstance()->getNewDocumentInstance();
		$skin->copyTo($tmpskin);
		$tmpskin->setVariablesJSON($skinParams['variablesJSON']);
		$page->setSkin($tmpskin);
		website_PageService::getInstance()->render($page);
	}
	
	public function isSecure()
	{
		return true;
	}
}