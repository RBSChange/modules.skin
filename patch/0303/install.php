<?php
/**
 * skin_patch_0303
 * @package modules.skin
 */
class skin_patch_0303 extends patch_BasePatch
{ 
	
	private $themecodename = "projecttheme";
	
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		if (!PatchService::getInstance()->isInstalled('theme', '0300'))
		{
			throw new Exception('Execute: [change.php apply-patch theme 0300] before this patch');
		}		
		$destSkinPath = f_util_FileUtils::buildWebeditPath('themes', $this->themecodename, 'skin', 'skin.xml');
		$destSkinLocalPath = f_util_FileUtils::buildWebeditPath('themes', $this->themecodename, 'locale', 'skin.xml');
		
		if (!file_exists($destSkinPath))
		{
			$overrideSkin = f_util_FileUtils::buildOverridePath('modules', 'skin', 'forms', 'editor', 'skin', 'properties.xml');
			if (file_exists($overrideSkin))
			{
				$this->migrateSkinVariable($overrideSkin);
			}
			else
			{
				$srcPath = f_util_FileUtils::buildWebeditPath('modules', 'skin', 'patch', '0303', 'skinedt.xml');
				f_util_FileUtils::mkdir(dirname($destSkinPath));
				f_util_FileUtils::cp($srcPath, $destSkinPath); 
				
				if (!file_exists($destSkinLocalPath))
				{
					$srcPath = f_util_FileUtils::buildWebeditPath('modules', 'skin', 'patch', '0303', 'skini18n.xml');
					f_util_FileUtils::mkdir(dirname($destSkinLocalPath));
					f_util_FileUtils::cp($srcPath, $destSkinLocalPath);
				}	
			}
		}
				
		try 
		{
			$newPath = f_util_FileUtils::buildWebeditPath('modules/skin/persistentdocument/skin.xml');
			$newModel = generator_PersistentModel::loadModelFromString(f_util_FileUtils::read($newPath), 'skin', 'skin');
			$newProp = $newModel->getPropertyByName('theme');
			f_persistentdocument_PersistentProvider::getInstance()->addProperty('skin', 'skin', $newProp);
		} 
		catch (BaseException $e)
		{
			if ($e->getAttribute('sqlstate') != '42S21' || $e->getAttribute('errorcode') != '1060')
			{
				throw $e;
			}
		}
		
		$this->executeSQLQuery("UPDATE m_skin_doc_skin SET theme='". $this->themecodename . "' WHERE theme IS NULL");
		
		$stmt = $this->executeSQLSelect("SELECT document_id, description FROM m_skin_doc_skin WHERE description IS NOT NULL");
		$rows = array();
		foreach ($stmt as $row) 
		{
			$rows[$row['document_id']] = $row['description'];
		}
		
		foreach ($rows as $id => $desc) 
		{
			$skin = DocumentHelper::getDocumentInstance($id, "modules_skin/skin");
			$skin->setDescription($desc);
			$skin->save();
		}
	}

	private function migrateSkinVariable($overrideSkin)
	{
		$srcDoc = f_util_DOMUtils::fromPath($overrideSkin);
		$skinDoc = f_util_DOMUtils::fromString('<?xml version="1.0" encoding="UTF-8"?><sections/>');

		$sections = $srcDoc->find('/panel/section');

		foreach ($sections as $section) 
		{
			$fields = $srcDoc->find('./field', $section);
			foreach ($fields as $field) 
			{
				switch ($field->getAttribute('name'))
				{
					case 'label':
					case 'description':
					case 'startpublicationdate':
					case 'endpublicationdate':
						$field->parentNode->removeChild($field);
					break;
				}
			}
			if ($section->firstChild)
			{
				$skinDoc->documentElement->appendChild($skinDoc->importNode($section, true));	
			}
		}
		$oldLocalDoc = f_util_DOMUtils::fromPath(f_util_FileUtils::buildWebeditPath('modules', 'skin', 'patch', '0303', 'skini18n.xml'));
		$skinLocalDoc = f_util_DOMUtils::fromString('<?xml version="1.0" encoding="utf-8"?><localization/>');
		
		foreach ($skinDoc->find('//section[@label]') as $section)
		{
			$key = $section->getAttribute('label');
			if (f_Locale::isLocaleKey($key))
			{
				$data = explode('.', str_replace(';', '', $key));
				$varName = strtolower(end($data));
				$section->setAttribute('name', $varName);
				$section->removeAttribute('label');
				$localNode = $skinLocalDoc->documentElement->appendChild($skinLocalDoc->createElement('entity'));
				$localNode->setAttribute('id', strtolower($varName));
				foreach (array('fr', 'en') as $lang) 
				{
					$text = f_Locale::translate($key, null, $lang, false);
					if ($text)
					{
						$langNode = $localNode->appendChild($skinLocalDoc->createElement('locale'));
						$langNode->setAttribute('lang', $lang);
						$langNode->appendChild($skinLocalDoc->createTextNode($text));
					}
					else 
					{
						$xpath = '//entity[@id="'.$varName.'"]/locale[@lang="'.$lang.'"]';
						echo $xpath . "\n";
						$langNode =  $oldLocalDoc->findUnique($xpath);
						if ($langNode)
						{
							$localNode->appendChild($skinLocalDoc->importNode($langNode, true));
						}
					}
				}				
			}
		}
		
		foreach ($skinDoc->find('//field[@name]') as $field) 
		{
			$varName = strtolower($field->getAttribute('name'));
			$localNode = $skinLocalDoc->documentElement->appendChild($skinLocalDoc->createElement('entity'));
			$localNode->setAttribute('id', $varName);
			foreach (array('fr', 'en') as $lang) 
			{
				$text = f_Locale::translate('&modules.skin.document.skin.' . $varName .';', null, $lang, false);
				if ($text)
				{
					$langNode = $localNode->appendChild($skinLocalDoc->createElement('locale'));
					$langNode->setAttribute('lang', $lang);
					$langNode->appendChild($skinLocalDoc->createTextNode($text));
				}
				else 
				{
					
					$langNode =  $oldLocalDoc->findUnique('//entity[@id="'.$varName.'"]/locale[@lang="'.$lang.'"]');
					if ($langNode)
					{
						$localNode->appendChild($skinLocalDoc->importNode($langNode, true));
					}
				}
			}
			
			if ($field->hasAttribute('allowfile'))
			{
				$field->setAttribute('type', 'imagecss');
				$field->setAttribute('moduleselector', 'media');
				$field->setAttribute('allow', 'modules_media_media');
				$field->setAttribute('mediafoldername', 'Inbox_' . $this->themecodename);
			}
			
			if (!$field->hasAttribute('type'))
			{
				$field->setAttribute('type', 'text');
			}
		}
		$path = f_util_FileUtils::buildWebeditPath('themes', $this->themecodename, 'skin', 'skin.xml');
		if (!file_exists($path))
		{
			f_util_FileUtils::mkdir(dirname($path));
			$skinDoc->save($path);
		}
		
		$path = f_util_FileUtils::buildWebeditPath('themes', $this->themecodename, 'locale', 'skin.xml');
		if (!file_exists($path))
		{
			f_util_FileUtils::mkdir(dirname($path));
			$skinLocalDoc->save($path);
		}
		
		$this->logWarning('Remove folder:' . f_util_FileUtils::buildOverridePath('modules', 'skin', 'forms', 'editor', 'skin'));
		$this->logWarning('Remove Skin injection document');
	}
	/**
	 * @return String
	 */
	protected final function getModuleName()
	{
		return 'skin';
	}

	/**
	 * @return String
	 */
	protected final function getNumber()
	{
		return '0303';
	}
}