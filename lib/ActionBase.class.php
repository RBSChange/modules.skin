<?php
/**
 * @package modules.skin.lib
 */
class skin_ActionBase extends f_action_BaseAction
{

	/**
	 * Returns the skin_SkinService to handle documents of type "modules_skin/skin".
	 *
	 * @return skin_SkinService
	 */
	public function getSkinService()
	{
		return skin_SkinService::getInstance();
	}
}