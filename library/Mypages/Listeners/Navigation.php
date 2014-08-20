<?php

class Mypages_Listeners_Navigation
{
    public static function getTabs(&$extraTabs, $selectedTabId)
		{
			$options = XenForo_Application::get('options');
			
			$extraTabs['mypages'] = array(
				'title' 		=> $options->tabTitle,
				'href' 			=> XenForo_Link::buildPublicLink('full:mypages'),
				//'linksTemplate' => 'mypageLinks',
				//'selected' => ($selectedTabId == 'mypages'),
				'position'      => $options->tabPosition
			);
		}
}