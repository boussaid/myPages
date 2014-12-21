<?php

class Mypages_ViewAdmin_MyPage extends XFCP_Mypages_ViewAdmin_MyPage
{
	public function renderHtml()
	{
		if(class_exists('Mypages_ViewAdmin_MyPages_Edit') && method_exists('Mypages_ViewAdmin_MyPages_Edit', 'renderHtml'))
		{
			parent::renderHtml();
		}

			$editorName = 'content';
			$html = $this->_params['template']['template'];
			$editorOptions = array(
				'extraClass' => 'html_mode'
			);			
		
			$this->_params['myPageEditor'] = XenForo_ViewPublic_Helper_Editor::getEditorTemplate(
				$this, $editorName, $html, $editorOptions
			);

			/*The getEditorTemplate doesn't have an option to bypass the Wysiwyg formatter, let's trick it*/
			$raw_message = $this->_params['myPageEditor']->getParam('message');
			$this->_params['myPageEditor']->setParam('messageHtml', $raw_message);
			
			$raw_name = $this->_params['myPageEditor']->getParam('formCtrlName');			
			$this->_params['myPageEditor']->setParam('formCtrlNameHtml', $raw_name);
	}
}