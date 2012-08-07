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
		$tmpFileDir = TMP_PATH . '/skin_import';
		f_util_FileUtils::rmdir($tmpFileDir);
		
		$skinFolderId = $this->getSkinFolderId();
		$mediaFolder = $this->getComputedAttribute('mediaFolder');
		$archive = new ZipArchive();
		if ($archive->open($zipPath))
		{
			$archive->extractTo($tmpFileDir);
			$archive->close();
			$result = skin_SkinService::getInstance()->importSkinZip($tmpFileDir, $skinFolderId, $mediaFolder);
			$this->document = $result['skin'];
		}
		else 
		{
			throw new Exception('Cannot open ' . $zipPath);
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