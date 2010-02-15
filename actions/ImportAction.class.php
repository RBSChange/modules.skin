<?php
/**
 * skin_ImportAction
 * @package modules.skin.actions
 */
class skin_ImportAction extends f_action_BaseJSONAction
{
	private $mediaFolderId = array();
	
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		if (!count($_FILES))
		{
			return $this->sendJSONError(f_Locale::translateUI('&modules.skin.bo.general.Import-file;', true));
		}
		
		if ($_FILES['filename']['error'] != UPLOAD_ERR_OK || substr($_FILES['filename']['name'], - strlen('.skin.zip')) != '.skin.zip')
		{
			return $this->sendJSONError(f_Locale::translateUI('&modules.skin.bo.general.Import-error;', true));
		}
		
		$zipPath = $_FILES['filename']['tmp_name'];
		$zipName = $_FILES['filename']['name'];
		$skinFolderId = $request->getParameter('folderId');
		try 
		{
			$result = skin_SkinService::getInstance()->importSkinZip($zipPath, $zipName, $skinFolderId);
		}
		catch (Exception $e)
		{
			Framework::exception($e);
			return $this->sendJSONError(f_Locale::translateUI('&modules.skin.bo.general.Import-error;', true));
		}
		
		$warnings = array();
		foreach ($result['warnings'] as $warning)
		{
			$warnings[] = f_locale::translateUI('&'.$warning.';'); 
		}		
		return $this->sendJSON(array('warnings' => $result['warnings']));
	}
	
	public function isSecure()
	{
		return true;
	}
}
