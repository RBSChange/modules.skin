<?php
class skin_SkinService extends f_persistentdocument_DocumentService
{
	/**
	 * @var skin_SkinService
	 */
	private static $instance;
	

	/**
	 * @return skin_SkinService
	 */
	public static function getInstance()
	{
		if (self::$instance === null)
		{
			self::$instance = self::getServiceClassInstance(get_class());
		}
		return self::$instance;
	}
	
	/**
	 * @return skin_persistentdocument_skin
	 */
	public function getNewDocumentInstance()
	{
		return $this->getNewDocumentInstanceByModelName('modules_skin/skin');
	}
	
	/**
	 * Create a query based on 'modules_skin/skin' model
	 * @return f_persistentdocument_criteria_Query
	 */
	public function createQuery()
	{
		return $this->pp->createQuery('modules_skin/skin');
	}
	
	/**
	 * @param skin_persistentdocument_skin $document
	 * @param Integer $parentNodeId
	 */
	protected function postSave($document, $parentNodeId = null)
	{		
		if ($document->getSubskinidof() !== null)
		{
			//Version de skin
			$masterSkin = $this->getMasterSkin($document);
			if ($masterSkin->getCurrentsubskinid() == $document->getId())
			{
				$this->transfertToMaster($document);
			}
		}
		CacheService::getInstance()->clearCssCache();
	}
		
	/**
	 * @param skin_persistentdocument_skin $skin
	 * @return skin_persistentdocument_skin
	 */
	public function createNewSubSkinFromSkin($skin)
	{
		try 
		{
			$this->tm->beginTransaction();
			$masterSkin = $this->getMasterSkin($skin);
			$currentSkinId = $masterSkin->getCurrentsubskinid();
			if ($currentSkinId === null)
			{
				$this->createInitialSubSkin($masterSkin);
			}
			$subSkin = $this->createSubSkin($skin, $masterSkin->getId());
			$subSkin->save($masterSkin->getId());
			$this->tm->commit();
		}
		catch (Exception $e)
		{
			$this->tm->rollBack($e);
		}
		return $subSkin;
	}
	
	
	/**
	 * @param skin_persistentdocument_skin $skin
	 * @return skin_persistentdocument_skin
	 */		
	private function getMasterSkin($skin)
	{
		if ($skin->getSubskinidof() != null)
		{
			return DocumentHelper::getDocumentInstance($skin->getSubskinidof());
		}	
		return $skin;
	}
	
	/**
	 * @param skin_persistentdocument_skin $masterSkin
	 * @return skin_persistentdocument_skin
	 */
	private function createInitialSubSkin($masterSkin)
	{
		$initialSubSkin = $this->getNewDocumentInstance();
		$rc = RequestContext::getInstance();
		try 
		{
			$rc->beginI18nWork($masterSkin->getLang());
			$masterSkin->copyTo($initialSubSkin, true);
			$initialSubSkin->setSubskinidof($masterSkin->getId());			
			$initialSubSkin->setStartpublicationdate(null);
			$initialSubSkin->setEndpublicationdate(null);
			$initialSubSkin->setPublicationstatus('ACTIVE');
			
			$this->save($initialSubSkin, $masterSkin->getId());
			$masterSkin->setCurrentsubskinid($initialSubSkin->getId());
			
			$this->pp->updateDocument($masterSkin);
			$rc->endI18nWork();
		}
		catch (Exception $e)
		{
			$rc->endI18nWork($e);
			return null;
		}
		
		return $initialSubSkin;
	}
	
	/**
	 * @param skin_persistentdocument_skin $masterSkin
	 * @return skin_persistentdocument_skin
	 */
	private function createSubSkin($fromSkin, $masterSkinId)
	{
		$subSkin = $this->getNewDocumentInstance();
		$rc = RequestContext::getInstance();
		try 
		{
			$rc->beginI18nWork($fromSkin->getLang());
			$fromSkin->copyTo($subSkin, true);
			$subSkin->setSubskinidof($masterSkinId);
			$subSkin->setCurrentsubskinid(null);
			
			$subSkin->setPublicationstatus('DRAFT');
			$subSkin->setStartpublicationdate(null);
			$subSkin->setEndpublicationdate(null);
			$countSubSkin = $this->countSubSkin($masterSkinId) + 1;
			$subSkin->setLabel(f_Locale::translate('&modules.skin.document.skin.Default-version-label;', 
				array('count' => $countSubSkin, 'label' => $fromSkin->getLabel())));			
			$rc->endI18nWork();
		}
		catch (Exception $e)
		{
			$rc->endI18nWork($e);
			return null;
		}
		return $subSkin;
	}
	
	private function countSubSkin($masterSkinId)
	{
		$result = $this->createQuery()->add(Restrictions::eq('subskinidof', $masterSkinId))
			->setProjection(Projections::rowCount('countSubSkin'))->find();
		return intval($result[0]['countSubSkin']);
	}
		
	/**
	 * @param skin_persistentdocument_skin $subSkin
	 * @return skin_persistentdocument_skin
	 */
	private function transfertToMaster($subSkin)
	{
		$masterSkin = $this->getMasterSkin($subSkin);
		if (Framework::isDebugEnabled())
		{
			Framework::debug(__METHOD__ . ' ' . $subSkin->getId() . ' -> ' . $masterSkin->getId());
		}
				
		$rc = RequestContext::getInstance();
		try 
		{
			$rc->beginI18nWork($subSkin->getLang());
			$subSkin->copyTo($masterSkin, true);
			$masterSkin->setSubskinidof(null);
			$masterSkin->setCurrentsubskinid($subSkin->getId());			
			$masterSkin->setPublicationstatus('ACTIVE');
			$masterSkin->setStartpublicationdate(null);
			$masterSkin->setEndpublicationdate(null);
			$this->save($masterSkin);								
			$rc->endI18nWork();
		}
		catch (Exception $e)
		{
			$rc->endI18nWork($e);
			return null;
		}
		return $masterSkin;
	}
	
	/**
	 * @see f_persistentdocument_DocumentService::postDelete()
	 *
	 * @param f_persistentdocument_PersistentDocument $document
	 */
	protected function postDelete($document)
	{
		//Version de skin
		if ($document->getSubskinidof() !== null)
		{
			$masterSkin = $this->getMasterSkin($document);
			$firstSubSkin = $this->retrieveFirstSubSkin($masterSkin->getId());
			if ($firstSubSkin === null)
			{
				//Plus de version de skin le skin redevient standard
				$masterSkin->setCurrentsubskinid(null);
				if ($masterSkin->isModified())
				{
					$this->pp->updateDocument($masterSkin);
				}
			} 
			else if ($masterSkin->getCurrentsubskinid() == $document->getId())
			{
				//On affecte le skin principal à la premiere version de skin trouvé
				//si la version courante est la version supprimé (plus de version publié)
				$this->transfertToMaster($firstSubSkin);						
			}
		}
	}
	
	/**
	 * @see f_persistentdocument_DocumentService::publicationStatusChanged()
	 *
	 * @param skin_persistentdocument_skin $document
	 * @param String $oldPublicationStatus
	 * @param array $params
	 */
	protected function publicationStatusChanged($document, $oldPublicationStatus, $params)
	{
		//Version de skin
		if ($document->getSubskinidof() !== null)
		{
			if ($oldPublicationStatus == 'PUBLICATED' || $document->isPublished())
			{
				try 
				{
					$this->tm->beginTransaction();
					$masterSkin = $this->getMasterSkin($document);
					$subSkin = $this->retrieveCurrentSubSkin($masterSkin->getId());
					if ($subSkin !== null)
					{
						if ($masterSkin->getCurrentsubskinid() != $subSkin->getId())
						{
							$this->transfertToMaster($subSkin);
						}			
					}
					$this->tm->commit();
				}
				catch (Exception $e)
				{
					$this->tm->rollBack($e);
				}
			}
		}
	}
	
	/**
	 * @param Integer $masterSkinId
	 * @return skin_persistentdocument_skin
	 */
	private function retrieveFirstSubSkin($masterSkinId)
	{
		$result = $this->createQuery()->add(Restrictions::eq('subskinidof', $masterSkinId))->find();
		return f_util_ArrayUtils::firstElement($result);
	}
	
	/**
	 * @param Integer $masterSkinId
	 * @return skin_persistentdocument_skin
	 */
	private function retrieveCurrentSubSkin($masterSkinId)
	{
		$result = $this->createQuery()
			->add(Restrictions::published())
			->add(Restrictions::eq('subskinidof', $masterSkinId))
			->addOrder(Order::desc('document_startpublicationdate'))
			->addOrder(Order::desc('document_modificationdate'))
			->find();
			
		return f_util_ArrayUtils::firstElement($result);
	}
	
	// Importation.
	
	/**
	 * @param String $zipPath
	 * @param String $zipName
	 * @param Integer $skinFolderId
	 * @param generic_persistentdocument_folder $mediaFolder
	 * @return skin_persistentdocument_skin
	 */
	public function importSkinZip($zipPath, $zipName, $skinFolderId, $mediaFolder = null)
	{		
		$warnings = array();
		$zip = new ZipArchive();
		if ($zip->open($zipPath) !== true)
		{
			throw new BaseException('Bad archive: '.$zipPath);
		}
		$ext = f_util_FileUtils::getFileExtension($zipName, true);
		$zipContent = $zip->getFromName(substr($zipName, 0, - strlen($ext)));
		if ($zipContent === false)
		{
			throw new BaseException('No content for skin: '.$zipName);
		}
		$skinContent = unserialize(gzuncompress($zipContent));
		
		if (! is_array($skinContent))
		{
			throw new BaseException('Bad content for skin: '.$zipName);
		}
		
		$tmpDir = f_util_FileUtils::buildChangeCachePath('tmp', 'skin-'.mt_rand());
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
						if (!isset($allMedias[$value['id']]))
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
								$media->save($this->getMediaFolderId($skinContent['label'], $mediaFolder));
								$allMedias[$value['id']] = $media;
								$skin->$method($media);
							}
							else
							{
								if (Framework::isDebugEnabled())
								{
									Framework::debug(__METHOD__ . ' invalid media path: "' . $mediaPath . '"');
								}
								if (!in_array('modules.skin.bo.general.Import-warning'))
								{
									$warnings[] = 'modules.skin.bo.general.Import-warning';
								}
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
					if (Framework::isDebugEnabled())
					{
						Framework::debug(__METHOD__ . ' unknown property: "' . $name . '"');
					}
					if (!in_array('modules.skin.bo.general.Import-warning2'))
					{
						$warnings[] = 'modules.skin.bo.general.Import-warning2';
					}
				}
			}
			$skin->save($skinFolderId);
			f_util_FileUtils::rmdir($tmpDir);
		}
		catch (Exception $e)
		{
			Framework::warn(__METHOD__ . ' failed to import zipped skin: "' . $zipPath . '"');
			Framework::exception($e);
			f_util_FileUtils::rmdir($tmpDir);
			throw new BaseException('An error occured during import: '.$e->getMessage());
		}
		return array('skin' => $skin, 'warnings' => $warnings);
	}
	
	/**
	 * @var Array
	 */
	private $mediaFolderId = array();
	
	/**
	 * @param String $skinName
	 * @return Integer
	 */
	private function getMediaFolderId($skinName, $mediaFolder)
	{
		if (!isset($this->mediaFolderId[$skinName]))
		{
			if ($mediaFolder !== null)
			{
				$mediaFolderId = $mediaFolder->getId();
			}
			else
			{
				$mediaFolderId = ModuleService::getInstance()->getSystemFolderId('media', 'skin');
			}
			$folder = generic_FolderService::getInstance()->createQuery()
				->add(Restrictions::childOf($mediaFolderId))
				->add(Restrictions::eq('label', $skinName))->findUnique();
			if ($folder === null)
			{
				$folder = generic_FolderService::getInstance()->getNewDocumentInstance();
				$folder->setLabel($skinName);
				$folder->save($mediaFolderId);
			}
			$this->mediaFolderId[$skinName] = $folder->getId();
		}
		return $this->mediaFolderId[$skinName];
	}
}