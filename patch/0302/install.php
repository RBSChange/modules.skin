<?php
/**
 * skin_patch_0302
 * @package modules.skin
 */
class skin_patch_0302 extends patch_BasePatch
{
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		$this->executeLocalXmlScript('fixlist.xml');
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
		return '0302';
	}
}