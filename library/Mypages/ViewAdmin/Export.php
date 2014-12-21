<?php

class Mypages_ViewAdmin_Export extends XenForo_ViewAdmin_Base
{
	/**
	 * Render the exported date to XML.
	 *
	 * @return string
	 */
	public function renderXml()
	{
		$this->setDownloadFileName('my_pages.xml');
		return $this->_params['xml']->saveXml();
	}
}