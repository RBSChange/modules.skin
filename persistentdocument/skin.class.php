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
	 * @param string $name
	 * @param string $defaultValue
	 * @return string | null
	 */
	function getCSSValue($name, $defaultValue = '')
	{
		$property = $this->getPersistentModel()->getEditableProperty($name);
		if ($property !== null)
		{
			$value = $this->{'get' . ucfirst($name)}();
			if ($value instanceof media_persistentdocument_media) 
			{
				return 'url('.$value->getDocumentService()->generateUrl($value).')';
			} 
			else if ($value !== null && $value !== '')
			{
				if (preg_match('/\|#[a-f0-9]{6}/i', $value))
				{
					$value = explode("|", $value);
					$value = $value[1];
				}
				return $value;
			}
		}
		return $defaultValue;
	}
	
	/**
	 * @param string $name
	 * @param string $value
	 */
	function setCSSValue($name, $value)
	{
		$property = $this->getPersistentModel()->getEditableProperty($name);
		if ($property !== null)
		{
			if ($property->getType() === "modules_media/media")
			{
				$value = intval($value);
				if ($value > 0)
				{
					$value = DocumentHelper::getDocumentInstance($value);
				}
				else
				{
					$value = null;
				}
			}
			
			$this->{'set' . ucfirst($name)}($value);
		}
	}
	
	/**
	 * Return a identifier for the set of variable
	 * @return string
	 */
	function getIdentifier()
	{
		return $this->getId();
	}
}