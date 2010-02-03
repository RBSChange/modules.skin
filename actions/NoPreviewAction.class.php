<?php
class skin_NoPreviewAction extends skin_Action
{

	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
  {
		return View::SUCCESS ;
	}

  public function isSecure()
	{
		return true;
	}
}