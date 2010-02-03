<?php
/**
 * skin_CreateSubSkinAction
 * @package modules.skin.actions
 */
class skin_CreateSubSkinAction extends f_action_BaseJSONAction
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		$skin = $this->getCurrentSkin($request);		
		$subSkin = $skin->getDocumentService()->createNewSubSkinFromSkin($skin);			
		return $this->sendJSON(array('id' => $subSkin->getId(), 'label' => $subSkin->getLabel()));
	}
	
	/**
	 * @param Request $request
	 * @return skin_persistentdocument_skin
	 */
	private function getCurrentSkin($request)
	{
		$skin = $this->getDocumentInstanceFromRequest($request);
		if (!$skin instanceof skin_persistentdocument_skin) 
		{
			throw new BaseException('Invalid-Skin', 'modules.skin.errors.Invalid-Skin');
		}		
		return $skin;
	}
}