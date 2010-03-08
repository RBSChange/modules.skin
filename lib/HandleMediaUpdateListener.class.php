<?php
class skin_HandleMediaUpdateListener
{
	public function onPersistentDocumentUpdated($sender, $params)
	{
		if ($params['document'] instanceof media_persistentdocument_media)
		{
			$skinIds = $this->getSkinIdArray();
			$allUsages = $params['document']->getAllUsages();
			foreach ($allUsages as $usage)
			{
				$usageDocumentId = $usage[2];
				if (in_array($usageDocumentId, $skinIds))
				{
					$cs = CacheService::getInstance();
					$cs->clearCssCache();
					return;
				}
			}
		}
	}
	
	
	private function getSkinIdArray()
	{
		return skin_SkinService::getInstance()->createQuery()->setProjection(Projections::property('id', 'id'))->findColumn('id');
	}
}