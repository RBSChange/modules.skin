<?php
class skin_patch_0300 extends patch_BasePatch
{ 
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		parent::execute();

		$ignoredProperties = array('document_id','document_model','document_version','document_metas');
		$objectProperties = array(	'bodybgimage','contentbgimage',
									'bannerbgimage','logoimage',
									'footerbgimage','menumaintopbgimage',
									'menumaintopimage','menumaintopcurrentimage',
									'menumaintophoverimage','menumainleftbgimage',
									'menumainleftoneimage','menumainleftonecurrentimage',
									'menumainleftonehoverimage','menumainlefttwoimage',
									'menumainlefttwocurrentimage','menumainlefttwohoverimage',
									'menucontextualtopbgimage','menucontextualtopimage',
									'menucontextualtopcurrentimage','menucontextualtophoverimage',
									'menucontextualleftbgimage','menucontextualleftoneimage',
									'menucontextualleftonecurrentimage','menucontextualleftonehoverimage',
									'menucontextuallefttwoimage','menucontextuallefttwocurrentimage',
									'menucontextuallefttwohoverimage','headingonebgimage',
									'headingtwobgimage','headingthreebgimage',
									'headingfourbgimage','headingfivebgimage',
									'headingsixbgimage','ulbullet',
									'olbullet','buttonsbgimage',
									'linkbuttonbgimage');
		
		$skinRootFolderId = ModuleService::getInstance()->getRootFolderId('skin');
		
		$pp = $this->getPersistentProvider();
		
		$statement = $pp->executeSQLSelect('SELECT * FROM `m_skin_doc_skin`;');
		$statement->execute();				
		$skinData = $statement->fetchAll(PDO::FETCH_ASSOC);

		$this->executeSQLQuery("DROP TABLE m_skin_doc_skin;");				
		$this->executeSQLQuery("CREATE TABLE IF NOT EXISTS `m_skin_doc_skin` (
	`document_id` int(11) NOT NULL default '0',
	`document_model` varchar(50) NOT NULL default '',
	  `document_label` varchar(255),
	  `document_author` varchar(50),
	  `document_authorid` int(11),
	  `document_creationdate` datetime,
	  `document_modificationdate` datetime,
	  `document_publicationstatus` ENUM('DRAFT', 'CORRECTION', 'ACTIVE', 'PUBLICATED', 'DEACTIVATED', 'FILED', 'DEPRECATED', 'TRASH', 'WORKFLOW') NULL DEFAULT NULL,
	  `document_lang` varchar(2),
	  `document_modelversion` varchar(20),
	  `document_version` int(11),
	  `document_startpublicationdate` datetime,
	  `document_endpublicationdate` datetime,
	  `document_metas` text,
	  `subskinidof` int(11),
	  `currentsubskinid` int(11),
	  `description` text,
	  `document_s18s` mediumtext,
PRIMARY KEY  (`document_id`)
) TYPE=InnoDB CHARACTER SET utf8 COLLATE utf8_bin;");
		
		foreach ($skinData as $row)
		{
			$newSkin = skin_SkinService::getInstance()->getNewDocumentInstance();
			
			$skinModel = $newSkin->getPersistentModel();
			
			$properties = array_keys($row);			
			foreach ($properties as $property)
			{
				$value = $row[$property];
				
				if(in_array($property, $ignoredProperties) || $value === null)
				{
					continue;
				}
				
				if(ereg('^document_',$property))
				{					
					$property = str_replace('document_','',$property);
				}
				
				$method = "set$property";
				
				if(in_array($property, $objectProperties))
				{
					$value = DocumentHelper::getDocumentInstance($value);
				}
				
				if (is_string($value) && preg_match("/\|#([a-f0-9]{6})/i", $value, $matches))
				{
					$value = '#' . $matches[1];
				}
				
				$newSkin->$method($value);
			}
			
			$newSkin->save($skinRootFolderId);
			
			$oldDocumentId = $row['document_id'];
			$newDocumentId = $newSkin->getId();
			
			$this->executeSQLQuery("UPDATE f_relation SET relation_id2 = '$newDocumentId' WHERE relation_name = 'skin' AND relation_id2  = '$oldDocumentId';");
			$this->executeSQLQuery("UPDATE m_website_doc_website SET skin = '$newDocumentId' WHERE skin = '$oldDocumentId';");
			$this->executeSQLQuery("UPDATE m_website_doc_topic SET skin = '$newDocumentId' WHERE skin = '$oldDocumentId';");
			$this->executeSQLQuery("UPDATE m_website_doc_page SET skin = '$newDocumentId' WHERE skin = '$oldDocumentId';");
			$this->executeSQLQuery("DELETE FROM f_document WHERE document_id = '$oldDocumentId';");			
		}
	}

	/**
	 * Returns the name of the module the patch belongs to.
	 *
	 * @return String
	 */
	protected final function getModuleName()
	{
		return 'skin';
	}

	/**
	 * Returns the number of the current patch.
	 * @return String
	 */
	protected final function getNumber()
	{
		return '0300';
	}

}