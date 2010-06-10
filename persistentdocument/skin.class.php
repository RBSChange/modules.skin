<?php
/**
 * skin_persistentdocument_skin
 * @package skin
 */
class skin_persistentdocument_skin extends skin_persistentdocument_skinbase  implements f_web_CSSVariables
{
	
	/**
	 * @see f_persistentdocument_PersistentDocumentImpl::addTreeAttributes()
	 *
	 * @param string $moduleName
	 * @param string $treeType
	 * @param array $nodeAttributes
	 */
	protected function addTreeAttributes($moduleName, $treeType, &$nodeAttributes)
	{
		
		$nodeAttributes['subskinidof'] = $this->getSubskinidof();
		$nodeAttributes['currentsubskinid'] = $this->getCurrentsubskinid();
		
		if ($treeType == 'wlist')
		{
			$lang = RequestContext::getInstance()->getUILang();
			$nodeAttributes['startpublicationdate'] = date_DateFormat::format($this->getUIStartpublicationdate(), null, $lang);
			$nodeAttributes['endpublicationdate'] = date_DateFormat::format($this->getUIEndpublicationdate(), null, $lang);
		}
	}
	
	/**
	 * @param string $actionType
	 * @param array $formProperties
	 */
	public function addFormProperties($propertiesNames, &$formProperties)
	{
		if (in_array('variablesJSON', $propertiesNames))
		{
			$string = $this->getS18s();
			if (f_util_StringUtils::isEmpty($string))
			{
				$data = array();
			}
			else
			{
				$data = unserialize($string);
			}
			$formProperties["variablesJSON"] = $data; 
		}		
	}
	
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
			if ($type === 'imagecss' && is_numeric($value))
			{
				try 
				{
					$media = DocumentHelper::getDocumentInstance($value, "modules_media/media");
					$value = 'url('.$media->getDocumentService()->generateUrl($media).')';
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
		else 
		{
			Framework::warn(__METHOD__ . ' undefined var:' . $name);
		}
		return $value === null ? $defaultValue : $value;
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