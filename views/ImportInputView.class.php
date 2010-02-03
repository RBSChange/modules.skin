<?php

class skin_ImportInputView extends f_view_BaseView
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		$this->setTemplateName('Skin-Import-Input', K::HTML);
	}
}
