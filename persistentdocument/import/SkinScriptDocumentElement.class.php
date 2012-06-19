<?php
class skin_SkinScriptDocumentElement extends import_ScriptDocumentElement
{
	/**
	 * @return skin_persistentdocument_skin
	 */
	protected function initPersistentDocument()
	{
		return skin_SkinService::getInstance()->getNewDocumentInstance();
	}
}