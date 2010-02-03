<?php

class skin_ImportAction extends skin_Action
{
	private $mediaFolderId = array();
	
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		if (! count($_FILES))
		{
			return View::INPUT;
		}
		
		if ($_FILES['skinfile']['error'] != UPLOAD_ERR_OK || substr($_FILES['skinfile']['name'], - strlen('.skin.zip')) != '.skin.zip')
		{
			return View::ERROR;
		}
		
		$file = $_FILES['skinfile']['tmp_name'];
		
		$zip = new ZipArchive();
		if ($zip->open($file) !== true)
		{
			return View::ERROR;
		}
		$zipName = $_FILES['skinfile']['name'];
		$ext = f_util_FileUtils::getFileExtension($zipName, true);
		$zipContent = $zip->getFromName(substr($zipName, 0, - strlen($ext)));
		if ($zipContent === false)
		{
			return View::ERROR;
		}
		$skinContent = unserialize(gzuncompress($zipContent));
		
		if (! is_array($skinContent))
		{
			return View::ERROR;
		}
		
		$tmpDir = TMP_PATH . DIRECTORY_SEPARATOR . 'skin-' . mt_rand();
		f_util_FileUtils::mkdir($tmpDir);
		$zip->extractTo($tmpDir);
		$allMedias = array();
		try
		{
			$propertyInfo = null;
			
			$skin = skin_SkinService::getInstance()->getNewDocumentInstance();
			$model = $skin->getPersistentModel();
			foreach ($skinContent as $name => $value)
			{
				$propertyInfo = $model->getEditableProperty($name);
				if ($propertyInfo !== null)
				{
					$method = 'set' . ucfirst($name);
					if (is_array($value))
					{
						if (! isset($allMedias[$value['id']]))
						{
							$mediaPath = $tmpDir . DIRECTORY_SEPARATOR . $value['id'] . f_util_FileUtils::getFileExtension($value['filename'], true);
							if (file_exists($mediaPath))
							{
								$media = media_MediaService::getInstance()->getNewDocumentInstance();
								foreach ($value as $mediaName => $mediaValue)
								{
									if ($mediaName != 'id')
									{
										$mediaMethod = 'set' . ucfirst($mediaName);
										$media->$mediaMethod($mediaValue);
									}
								}
								$media->setNewFileName($mediaPath);
								$media->save($this->getMediaFolderId($skinContent['label']));
								$allMedias[$value['id']] = $media;
								$skin->$method($media);
							}
							else
							{
								$request->setParameter('warning', true);
							}
						}
						else
						{
							$skin->$method($allMedias[$value['id']]);
						}
					}
					else if (is_string($value))
					{
						if (preg_match('/\|#[a-f0-9]{6}/i', $value))
						{
							$value = explode("|", $value);
							$value = $value[1];
						}						
						$skin->$method($value);
					}
					else
					{
						$skin->$method($value);
					}
				}
				else
				{
					$request->setParameter('warning2', true);
				}
			}
			$folderId = f_util_ArrayUtils::firstElement($request->getParameter(K::COMPONENT_ID_ACCESSOR));
			if (! is_numeric($folderId))
			{
				$folderId = ModuleService::getInstance()->getRootFolderId('skin');
			}
			$skin->save($folderId);
			f_util_FileUtils::rmdir($tmpDir);
		}
		catch (Exception $e)
		{
			Framework::exception($e);
			f_util_FileUtils::rmdir($tmpDir);
			return View::ERROR;
		}
		
		return View::SUCCESS;
	}
	
	private function getMediaFolderId($skinName)
	{
		if (isset($this->mediaFolderId[$skinName]))
		{
			return $this->mediaFolderId[$skinName];
		}
		
		$sysId = ModuleService::getInstance()->getSystemFolderId('media', 'skin');
		$pp = f_persistentdocument_PersistentProvider::getInstance();
		$folder = $pp->createQuery('modules_generic/folder')
			->add(Restrictions::childOf($sysId))
			->add(Restrictions::eq('label', $skinName))->findUnique();
		if ($folder === null)
		{
			$folder = generic_FolderService::getInstance()->getNewDocumentInstance();
			$folder->setLabel($skinName);
			$folder->save($sysId);
		}
		return $folder->getId();
	}
	
	public function isSecure()
	{
		return true;
	}
}
