<?php

class skin_PreviewAction extends skin_Action
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
		foreach ($skinParams as $name => $value) 
		{
			$tmpskin->setCSSValue($name, $value);
		}
		$page->setSkin($tmpskin);
		website_PageService::getInstance()->render($page);
	}
	
	public function isSecure()
	{
		return true;
	}
}