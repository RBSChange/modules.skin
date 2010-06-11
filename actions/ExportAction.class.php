<?php
if (!class_exists('PclZip', false))
{
	require_once WEBEDIT_HOME . '/modules/skin/tools/pclzip.lib.php';
}

/**
 * skin_ExportAction
 * @package modules.skin.actions
 */
class skin_ExportAction extends f_action_BaseAction
{
	/**
	 * @param Request $request
	 * @return skin_persistentdocument_skin
	 */
	private function getSkinFromRequest($request)
	{
		return $this->getDocumentInstanceFromRequest($request);
	}
	
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		$document = $this->getSkinFromRequest($request);
		$tmpFileDir = TMP_PATH . '/skin_' . $document->getId();
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
		$zip = new PclZip($zipFile);
		$zip->add($tmpFileDir, PCLZIP_OPT_REMOVE_PATH, $tmpFileDir);
		
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
		return View::NONE;
	}
	
	public function isSecure()
	{
		return true;
	}
}
