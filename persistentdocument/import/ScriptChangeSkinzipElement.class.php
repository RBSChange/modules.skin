<?php
class skin_ScriptChangeSkinzipElement extends import_ScriptObjectElement
{
	private $document = null;

	/**
	 * @return Object
	 */
	public function getObject()
	{
		return $this->document;
	}
	
	public function endProcess()
	{
		$zipPath = f_util_FileUtils::buildWebeditPath($this->getComputedAttribute('zipPath'));
		$zip = new PclZip($zipPath);
		$tmpFileDir = TMP_PATH . '/skin_import';
		f_util_FileUtils::rmdir($tmpFileDir);
		$zip->extract(PCLZIP_OPT_PATH, $tmpFileDir);
		
		$skinFolderId = $this->getSkinFolderId();
		$mediaFolder = $this->getComputedAttribute('mediaFolder');
		try 
		{
			$result = skin_SkinService::getInstance()->importSkinZip($tmpFileDir, $skinFolderId, $mediaFolder);
			$this->document = $result['skin'];
		}
		catch (Exception $e)
		{
			Framework::exception($e);
		}
	}
		
	/**
	 * @return integer
	 */
	private function getSkinFolderId()
	{
		return $this->getParent()->getObject()->getId();
	}
}