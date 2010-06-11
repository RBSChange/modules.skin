<?php
if (!class_exists('PclZip', false))
{
	require_once WEBEDIT_HOME . '/modules/skin/tools/pclzip.lib.php';
}

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
		
		if ($_FILES['filename']['error'] != UPLOAD_ERR_OK || substr($_FILES['filename']['name'], - strlen('.skindata.zip')) != '.skindata.zip')
		{
			return $this->sendJSONError(f_Locale::translateUI('&modules.skin.bo.general.Import-error;', true));
		}
		
		$zipPath = $_FILES['filename']['tmp_name'];
		$skinFolderId = $request->getParameter('folderId');
		$zip = new PclZip($zipPath);
		$tmpFileDir = TMP_PATH . '/skin_import';
		f_util_FileUtils::rmdir($tmpFileDir);
		$zip->extract(PCLZIP_OPT_PATH, $tmpFileDir);
		
		try 
		{
			$result = skin_SkinService::getInstance()->importSkinZip($tmpFileDir, $skinFolderId);
		}
		catch (Exception $e)
		{
			Framework::exception($e);
			f_util_FileUtils::rmdir($tmpFileDir);
			return $this->sendJSONError(f_Locale::translateUI('&modules.skin.bo.general.Import-error;', true));
		}
		
		f_util_FileUtils::rmdir($tmpFileDir);	
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
