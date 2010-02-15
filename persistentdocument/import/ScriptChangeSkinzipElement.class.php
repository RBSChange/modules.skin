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
		$zipName = basename($zipPath);
		$skinFolderId = $this->getSkinFolderId();
		$mediaFolder = $this->getComputedAttribute('mediaFolder');
		try 
		{
			skin_SkinService::getInstance()->importSkinZip($zipPath, $zipName, $skinFolderId, $mediaFolder);
		}
		catch (Exception $e)
		{
			Framework::exception($e);
		}
	}
		
	/**
	 * @return Integer
	 */
	private function getSkinFolderId()
	{
		return $this->getParent()->getObject()->getId();
	}
}