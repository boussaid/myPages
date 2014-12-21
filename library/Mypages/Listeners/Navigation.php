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

	public static function extendViewPublicMyPage($class, array &$extend)
	{
		if($class == 'Mypages_ViewPublic_MyPages')
		{
			$extend[] = 'Mypages_ViewPublic_Pages';
		}	
	}

	public static function extendViewAdminMyPage($class, array &$extend)
	{
		if($class == 'Mypages_ViewAdmin_MyPages_Edit')
		{
			$extend[] = 'Mypages_ViewAdmin_MyPage';
		}	
	}
}