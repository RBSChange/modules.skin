<?php
/**
 * skin_persistentdocument_skin
 * @package skin
 */
class skin_persistentdocument_skin extends skin_persistentdocument_skinbase  implements f_web_CSSVariables
{
	/**
	 * Return a identifier for the set of variable
	 * @return string
	 */
	public function getIdentifier()
	{
		return $this->getId();
	}
	
	/**
	 * @var array
	 */
	private $varsInfos;
	
	/**
	 * @return array
	 */
	private function getVarsInfos()
	{
		if ($this->varsInfos === null)
		{
			if ($this->getTheme())
			{
				$variablesPath = f_util_FileUtils::buildChangeBuildPath('themes', $this->getTheme(), 'variables.ser');
				if (!is_readable($variablesPath))
				{
					throw new Exception('theme no compiled properly: ' . $this->getTheme());
				}
				$this->varsInfos = unserialize(file_get_contents($variablesPath));
			}
			else
			{
				$this->varsInfos = array();
			}
		}
		return $this->varsInfos;
	}
	
	/**
	 * @return array
	 */
	public function getExportInfos()
	{
		$result = array('__theme' => $this->getTheme(), '__label' => $this->getLabel(), 
						'__description' => $this->getDescription());
		
		$vars = $this->getVarsInfos();
		foreach ($vars as $name => $infos) 
		{
			$value = $this->getS18sProperty($name);
			if ($value !== null)
			{
				if ($infos['type'] === 'imagecss' && is_numeric($value))
				{
					$media = $this->getMediaById($value);
					if ($media === null)
					{
						continue;
					}
					$value = array('id' => $media->getId(), 'label' => $media->getLabel(), 'title' => $media->getTitle(), 
						'description' => $media->getDescription(), 'credit' => $media->getCredit(), 
						'mediatype' => $media->getMediatype(), 'filename' => $media->getFilename());
				}
				$result[$name] = $value;
			}
		}
		return $result;
	}
	
	/**
	 * @param array $vars
	 */
	public function setImportInfos($vars)
	{
		foreach ($vars as $name => $value)
		{
			if ($value === null)
			{
				continue;
			}
			
			switch ($name) 
			{
				case '__theme':
					$this->setTheme($value);
					break;
				case '__label':
					$this->setLabel($value);
					break;	
				case '__description':
					$this->setDescription($value);
					break;				
				default:
					$this->setS18sProperty($name, $value);
					break;
			}
		}
	}
	
	/**
	 * @param integer $id
	 * @return media_persistentdocument_media
	 */
	private function getMediaById($id)
	{
		try 
		{
			return DocumentHelper::getDocumentInstance($id, 'modules_media/media');
		}
		catch (Exception $e)
		{
			Framework::exception($e);
		}
		return null;
	}
	
	/**
	 * @param string $name
	 * @return boolean
	 */
	private function isDefinedVar($name)
	{
		$vars = $this->getVarsInfos();
		return isset($vars[$name]);
	}
	
	private function getVarType($name)
	{
		$vars = $this->getVarsInfos();
		if (isset($vars[$name]))
		{
			return $vars[$name]['type'];
		}
		return 'text';
	}
	
	private function getVarDefaultValue($name)
	{
		$vars = $this->getVarsInfos();
		if (isset($vars[$name]))
		{
			return $vars[$name]['ini'];
		}
		return '';
	}	

	/**
	 * @param string $name
	 * @param string $defaultValue
	 * @return string | null
	 */
	public function getCSSValue($name, $defaultValue = '')
	{
		$value = $this->getS18sProperty($name);
		if ($this->isDefinedVar($name))
		{
			$type = $this->getVarType($name);
			if ($value === null)
			{
				return $this->getVarDefaultValue($name);
			}
			if (is_numeric($value) && ($type === 'document' || $type === 'imagecss'))
			{
				try 
				{
					$document = DocumentHelper::getDocumentInstance($value);
					$value = 'url('.LinkHelper::getDocumentUrl($document).')';
				}
				catch (Exception $e)
				{
					Framework::exception($e);
					$value = 'none';
				}
			}
			else if (preg_match('/\|#[a-f0-9]{6}/i', $value))
			{
				$value = explode("|", $value);
				$value = $value[1];
			}
			return $value;
		}
		elseif (Framework::isInfoEnabled())
		{
			Framework::info(__METHOD__ . ' undefined var:' . $name);
		}
		return $value === null ? $defaultValue : $value;
	}
	
	/**
	 * @return media_persistentdocument_file[]
	 */
	public function getMediaDocuments()
	{
		$result = array();
		foreach ($this->getVarsInfos() as $name => $array)
		{
			if ($array['type'] === 'imagecss' || $array['type'] === 'document')
			{
				$value = $this->getS18sProperty($name);
				if (is_numeric($value))
				{
					$modelName =f_persistentdocument_PersistentProvider::getInstance()->getDocumentModelName($value);
					if ($modelName !== false)
					{
						$object = f_persistentdocument_PersistentProvider::getInstance()->getDocumentInstance($value, $modelName);
						if ($object instanceof media_persistentdocument_file) 
						{
							$result[] = $object;
						}
					}
				}
			}
		}
		return $result;		
	}
	
	/**
	 * @param string $name
	 * @param string $value
	 */
	function setCSSValue($name, $value)
	{
		if ($this->isDefinedVar($name))
		{
			$this->setS18sProperty($name, $value);
		}
		else
		{
			Framework::warn(__METHOD__ . ' undefined var:' . $name);
			$this->setS18sProperty($name, $value);
		}
	}
	
	/**
	 * @param array $data
	 */
	function setVariablesJSON($data)
	{		
		if (is_array($data))
		{
			foreach ($data as $name => $value) 
			{
				if ($this->isDefinedVar($name))
				{
					$this->setS18sProperty($name, $value);
				}
				else
				{
					Framework::warn(__METHOD__ . ' undefined var:' . $name);
				}
			}
		}
		else
		{
			Framework::warn(__METHOD__ . ' invalid data:' . var_export($data, true));
		}
	}
	
	/**
	 * @return string
	 */
	function getThemeid()
	{
		if ($this->getTheme())
		{
			$theme = theme_ThemeService::getInstance()->getByCodeName($this->getTheme());
			return ($theme) ? $theme->getId() : null;
		}
		return null;
	}
	
	/**
	 * @param string $themeId
	 */
	function setThemeid($themeId)
	{
		if (intval($themeId))
		{
			$theme = DocumentHelper::getDocumentInstance(intval($themeId), "modules_theme/theme");
			$this->setTheme($theme->getCodename());
		}
		else
		{
			$this->setTheme(null);
		}
	}
}