<?php
/**
 * skin_ExportAction
 * @package modules.skin.actions
 */
class skin_ExportAction extends change_Action
{
	/**
	 * @param change_Request $request
	 * @return skin_persistentdocument_skin
	 */
	private function getSkinFromRequest($request)
	{
		return $this->getDocumentInstanceFromRequest($request);
	}
	
	/**
	 * @param change_Context $context
	 * @param change_Request $request
	 */
	public function _execute($context, $request)
	{
		$document = $this->getSkinFromRequest($request);
		$tmpFileDir = TMP_PATH . DIRECTORY_SEPARATOR . 'skin_' . $document->getId();
		f_util_FileUtils::mkdir($tmpFileDir);
		$tmpFileDir = realpath($tmpFileDir);

		$skinDefContent = $document->getExportInfos();
		foreach ($skinDefContent as $name => $value) 
		{
			if (is_array($value) && isset($value['id']))
			{
				$media = DocumentHelper::getDocumentInstance($value['id'], 'modules_media/media');
				$mediaPath = $media->getDocumentService()->getOriginalPath($media);
				$skinDefContent[$name]['zippath'] = '/medias/' . basename($mediaPath);
				f_util_FileUtils::cp($mediaPath, $tmpFileDir . '/medias/' . basename($mediaPath), f_util_FileUtils::OVERRIDE);
			}
		}
		f_util_FileUtils::writeAndCreateContainer($tmpFileDir .'/skindata.ser', serialize($skinDefContent), f_util_FileUtils::OVERRIDE);
		
		$zipFile = f_util_FileUtils::getTmpFile('zipskin');
		
		$archive = new ZipArchive();
		$archive->open($zipFile, ZipArchive::CREATE);
		$basePathLength = strlen($tmpFileDir) + 1;
		foreach (new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($tmpFileDir, RecursiveDirectoryIterator::KEY_AS_PATHNAME), RecursiveIteratorIterator::SELF_FIRST) 
				as $file => $info)
		{
			if ($info->isFile())
			{
				$newPath = substr($file, $basePathLength);
				$archive->addFile($file, $newPath);
			}
			
		}
		$archive->close();
		
		$headers[] = 'Cache-Control: public, must-revalidate';
		$headers[] = 'Pragma: hack';
		$headers[] = 'Content-type: application/zip';
		$headers[] = 'Content-Disposition: attachment; filename="skin_' . $document->getId() . '.skindata.zip"';
		foreach ($headers as $header)
		{
			header($header);
		}
		readfile($zipFile);
		@unlink($zipFile);
		f_util_FileUtils::rmdir($tmpFileDir);
		return change_View::NONE;
	}
	
	public function isSecure()
	{
		return true;
	}
}
