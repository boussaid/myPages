<?php
class Mypages_Helper
{
	public static function BbCodeOutputBefore($content)
	{
		$content = str_replace('<br />', '<br_source />', $content);
		return $content;	
	}

	public static function BbCodeOutputAfter($content)
	{
		//This hook is just before the htmlspecialchars_decode function
		$content = str_replace(
				array(
					"&gt;<br />",
					"&gt;\n<br />"
				),
				"&gt;",
		$content);

		return $content;	
	}

	public static function finalOutput($content, $bbCodeMode = false)
	{
		if($bbCodeMode)
		{
			//This hook is just after the htmlspecialchars_decode function
			$content = str_replace('<br_source />', '<br />', $content);
		}
/*
		if(XenForo_Application::get('options')->get('sedo_pck_basecss'))
		{
			$content = "<div class='pke_base'>$content</div>";
		}
*/
		return $content;
	}

	
}