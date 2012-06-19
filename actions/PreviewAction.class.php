<?php
/**
 * skin_PreviewAction
 * @package modules.skin.actions
 */
class skin_PreviewAction extends change_Action
{
	/**
	 * @param change_Context $context
	 * @param change_Request $request
	 */
	public function _execute($context, $request)
	{
		$context->getUser()->setUserNamespace(change_User::BACKEND_NAMESPACE);
		$skinParams = change_Controller::getInstance()->getStorage()->readForUser($request->getParameter('md5'));
		$pageId = $request->getParameter('pageid');		
		$page = DocumentHelper::getDocumentInstance($pageId);
			
		$skin = DocumentHelper::getDocumentInstance($skinParams['cmpref']);	
		$tmpskin = skin_SkinService::getInstance()->getNewDocumentInstance();
		$skin->copyPropertiesTo($tmpskin);
		$tmpskin->setVariablesJSON($skinParams['variablesJSON']);
		$tmpskin->setPublicationstatus(f_persistentdocument_PersistentDocument::STATUS_PUBLISHED);
		$page->setSkin($tmpskin);
		website_PageService::getInstance()->render($page);
	}
	
	/**
	 * @return boolean
	 */
	public function isSecure()
	{
		return true;
	}
}