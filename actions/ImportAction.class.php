<?php
/**
 * skin_ImportAction
 * @package modules.skin.actions
 */
class skin_ImportAction extends change_JSONAction
{
	/**
	 * @param change_Context $context
	 * @param change_Request $request
	 * @return null
	 */
	public function _execute($context, $request)
	{
		if (!count($_FILES))
		{
			return $this->sendJSONError(LocaleService::getInstance()->trans('m.skin.bo.general.import-file', array('ucf')));
		}
		
		if ($_FILES['filename']['error'] != UPLOAD_ERR_OK || substr($_FILES['filename']['name'], - strlen('.skindata.zip')) != '.skindata.zip')
		{
			return $this->sendJSONError(LocaleService::getInstance()->trans('m.skin.bo.general.import-error', array('ucf')));
		}
		
		$zipPath = $_FILES['filename']['tmp_name'];
		$skinFolderId = $request->getParameter('folderId');
		$tmpFileDir = TMP_PATH . DIRECTORY_SEPARATOR . 'skin_import_' . $skinFolderId;
		f_util_FileUtils::rmdir($tmpFileDir);
		f_util_FileUtils::mkdir($tmpFileDir);
		$tmpFileDir = realpath($tmpFileDir);
		try 
		{
			$archive = new ZipArchive();
			if ($archive->open($zipPath))
			{
				$archive->extractTo($tmpFileDir);
				$archive->close();
				$result = skin_SkinService::getInstance()->importSkinZip($tmpFileDir, $skinFolderId);
			}
		}
		catch (Exception $e)
		{
			Framework::exception($e);
			f_util_FileUtils::rmdir($tmpFileDir);
			return $this->sendJSONError(LocaleService::getInstance()->trans('m.skin.bo.general.import-error;', array('ucf')));
		}
		
		f_util_FileUtils::rmdir($tmpFileDir);	
		$warnings = array();
		foreach ($result['warnings'] as $warning)
		{
			$warnings[] = LocaleService::getInstance()->trans($warning, array('ucf')); 
		}		
		return $this->sendJSON(array('warnings' => $result['warnings']));
	}
	
	/**
	 * @return boolean
	 */
	public function isSecure()
	{
		return true;
	}
}