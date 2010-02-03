<?php

class skin_NoPreviewSuccessView extends f_view_BaseView
{
	/**
	 * @param Context $context
	 * @param Request $request
	 */
	public function _execute($context, $request)
	{
		$this->setTemplateName('Skin-NoPreview-Success', K::HTML);
	}
}
