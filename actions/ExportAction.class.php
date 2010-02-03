<?php

class skin_ExportAction extends skin_Action
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		$document = $this->getDocumentInstanceFromRequest($request);
		
		$skinDefContent = array();
		$skinFiles = array();
		$genericProperties = array('label', 'creationdate', 'startpublicationdate', 'endpublicationdate', 'description');
		foreach ($genericProperties as $propertyName)
		{
			$component = $document->{'get' . ucfirst($propertyName)}();
			$skinDefContent[$propertyName] = $component;
		}
		$dm = $document->getPersistentModel();		
		$properties = $dm->getSerializedPropertiesInfos();
		foreach ($properties as $property)
		{
			$propertyName = $property->getName();
			$component = $document->{'get' . ucfirst($propertyName)}();
			if ($component instanceof media_persistentdocument_media)
			{
				$skinFiles[$component->getId()] = $component->getDocumentService()->getOriginalPath($component);
				$skinDefContent[$propertyName] = array('id' => $component->getId(), 'label' => $component->getLabel(), 'title' => $component->getTitle(), 'description' => $component->getDescription(), 'credit' => $component->getCredit(), 'mediatype' => $component->getMediatype(), 'filename' => $component->getFilename());
				
			}
			else if ($component instanceof f_persistentdocument_PersistentDocument)
			{
				$skinDefContent[$propertyName] = null;
			}
			else
			{
				$skinDefContent[$propertyName] = $component;
			}
		}
		$skinDefFile = gzcompress(serialize($skinDefContent));
		$filename = str_replace('°', '_', f_util_FileUtils::normalizeFilename($document->getLabel()));
		f_util_FileUtils::mkdir(TMP_PATH);
		$filePath = TMP_PATH . DIRECTORY_SEPARATOR . "skin-" . mt_rand() . $filename . ".zip";
		
		$zip = new ZipArchive();
		$zip->open($filePath, ZipArchive::CREATE);
		$zip->addFromString($filename . ".skin", $skinDefFile);
		foreach ($skinFiles as $id => $path)
		{
			$zip->addFile($path, $id . f_util_FileUtils::getFileExtension($path, true));
		}
		$res = $zip->close();
		Framework::fatal("-->" . file_get_contents($filePath));
		
		$headers[] = 'Cache-Control: public, must-revalidate';
		$headers[] = 'Pragma: hack';
		$headers[] = 'Content-type: application/zip';
		$headers[] = 'Content-Disposition: attachment; filename="' . $filename . '.skin.zip"';
		foreach ($headers as $header)
		{
			header($header);
		}
		readfile($filePath);
		@unlink($filePath);
		
		return View::NONE;
	}
	
	public function isSecure()
	{
		return true;
	}
}
