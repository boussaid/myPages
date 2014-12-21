<?php
class Mypages_ViewPublic_Pages extends XFCP_Mypages_ViewPublic_Pages
{
	public function renderHtml()
	{
		parent::renderHtml();
		$content = $this->_params['templateHtml']->render();
		$bbCodeMode = false;

		if(XenForo_Application::get('options')->get('useParseBbcode'))
		{
			$bbCodeMode = true;
			$formatterOptions = array(
				'view' => $this,
				'states' => array('viewAttachments' => true),
				'smilies' => array()
			);
			
			$bbCodeParser = XenForo_BbCode_Parser::create(XenForo_BbCode_Formatter_Base::create('Base', $formatterOptions));

			$content = Mypages_Helper::BbCodeOutputBefore($content);
			$content = $bbCodeParser->render($content);
			$content = Mypages_Helper::BbCodeOutputAfter($content);

			$content = htmlspecialchars_decode($content);
		}

		$content = Mypages_Helper::finalOutput($content, $bbCodeMode);
		$this->_params['templateHtml'] = $content;
	}
}