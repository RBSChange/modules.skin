<?php
/**
 * skin_ExportAction
 * @package modules.skin.actions
 */
class skin_ExportAction extends f_action_BaseAction
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
		$filePath = tempnam(null, "skinzip");
		$zip = new ZipArchive();
		$zipRes = $zip->open($filePath, ZipArchive::CREATE | ZIPARCHIVE::OVERWRITE);
		if ($zipRes !== true)
		{
			switch ($zipRes)
			{
				case ZIPARCHIVE::ER_EXISTS : $resString = "ER_EXISTS"; break;
				case ZIPARCHIVE::ER_INCONS : $resString = "ER_INCONS"; break;
				case ZIPARCHIVE::ER_INVAL : $resString = "ER_INVAL"; break;
				case ZIPARCHIVE::ER_MEMORY : $resString = "ER_MEMORY"; break;
				case ZIPARCHIVE::ER_NOENT : $resString = "ER_NOENT"; break;
				case ZIPARCHIVE::ER_NOZIP : $resString = "ER_NOZIP"; break;
				case ZIPARCHIVE::ER_OPEN : $resString = "ER_OPEN"; break;
				case ZIPARCHIVE::ER_READ : $resString = "ER_READ"; break;
				case ZIPARCHIVE::ER_SEEK : $resString = "ER_SEEK"; break;
				default: $resString = "Unknown error : ".var_export($zipRes, true);
			}
			throw new Exception("Unable to create zip archive $filePath: ".$resString);	
		}
		if (!$zip->addFromString($filename . ".skin", $skinDefFile))
		{
			throw new Exception("Unable to add skin def file to zip archive"); 
		}
		foreach ($skinFiles as $id => $path)
		{
			if (!$zip->addFile($path, $id . f_util_FileUtils::getFileExtension($path, true)))
			{
				throw new Exception("Unable to add $path to zip archive");
			}
		}
		if (!$zip->close())
		{
			throw new Exception("Could not close zip archive");
		}
		
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
