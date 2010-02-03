<?php

class skin_ImportSuccessView extends f_view_BaseView
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		$this->setTemplateName('Skin-Import-Success', K::HTML);
		$this->setAttributes($request->getParameters());
	}
}
