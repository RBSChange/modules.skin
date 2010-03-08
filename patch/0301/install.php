<?php
/**
 * skin_patch_0301
 * @package modules.skin
 */
class skin_patch_0301 extends patch_BasePatch
{
//  by default, isCodePatch() returns false.
//  decomment the following if your patch modify code instead of the database structure or content.
    /**
     * Returns true if the patch modify code that is versionned.
     * If your patch modify code that is versionned AND database structure or content,
     * you must split it into two different patches.
     * @return Boolean true if the patch modify code that is versionned.
     */
//	public function isCodePatch()
//	{
//		return true;
//	}
 
	/**
	 * Entry point of the patch execution.
	 */
	public function execute()
	{
		// Implement your patch here.
		exec('change.php compile-listeners');
		foreach (skin_SkinService::getInstance()->createQuery()->find() as $skin)
		{
			$skin->setModificationdate(null);
			$skin->save();
		}
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
		return '0301';
	}
}