<?php

class Mypages_ControllerPublic_MyPages extends XenForo_ControllerPublic_Abstract
{
	protected $_pagesCache = null;

	public function actionIndex()
	{
		$pageName = $this->_input->filterSingle('page_name', XenForo_Input::STRING);
		if ($pageName !== '')
		{
			$actionName = str_replace(array('-', '/'), ' ', strtolower($pageName));
			$actionName = str_replace(' ', '', ucwords($actionName));
			if (strtolower($actionName) != 'index')
			{
				if (method_exists($this, "action$actionName"))
				{
					return $this->responseReroute(__CLASS__, $actionName);
				}

				$pageName = trim($pageName, '/ ');

				return $this->_handleMyPage($pageName);
			}
		}

		$myPagesModel = $this->_getMyPagesModel();
		$pages = $myPagesModel->getMyPages();
		$this->_pagesCache = $myPagesModel->preparePages($pages);
		

		$viewParams = array(
			'pages' => $this->_pagesCache
		);

		return $this->_getWrapper('',
			$this->responseView('Mypages_ViewPublic_Index', 'mypages_index', $viewParams)
		);
	}

	protected function _handleMyPage($pageName)
	{
		$page = $this->_getMyPagesModel()->getMyPageByName($pageName);
		if (!$page)
		{
			return $this->responseError(new XenForo_Phrase('requested_page_not_found'), 404);
		}

		$this->canonicalizeRequestUrl(XenForo_Link::buildPublicLink('mypages', $page));

		$myPagesModel = $this->_getMyPagesModel();

		$viewParams = array(
			'page' => $myPagesModel->preparePage($page),
			'templateName' => $myPagesModel->getMyPageTemplateName($page['page_id'])
		);
		$response = $this->responseView('Mypages_ViewPublic_MyPages', 'my_page', $viewParams);

		if (!empty($page['callback_class']) && !empty($page['callback_method']))
		{
			call_user_func_array(array($page['callback_class'], $page['callback_method']), array($this, &$response));
		}

		return $this->_getWrapper($pageName, $response);
	}

	

	/**
	 * Session activity details.
	 * @see XenForo_Controller::getSessionActivityDetailsForList()
	 */
	public static function getSessionActivityDetailsForList(array $activities)
	{
		return XenForo_Application::get('options')->tabTitle;
	}

	protected function _getWrapper($selected, XenForo_ControllerResponse_View $subView)
	{
		if ($this->_pagesCache !== null)
		{
			$pages = $this->_pagesCache;
		}
		else
		{
			$myPagesModel = $this->_getMyPagesModel();
			$pages = $myPagesModel->preparePages($myPagesModel->getMyPages());
		}
		$options = XenForo_Application::get('options');
		
		if ($options->selectTemplate == 'mypages_Hwrapper')
		{
		$selected = $this->_input->filterSingle('selected', XenForo_Input::STRING);
		if (!$selected)
		{
		$selected = 'mypages';
		}
		}
		if ($options->showSide)
		{
		$myPagesModel = $this->_getMyPagesModel();
		$threads= $myPagesModel->getThreads();

		$userModel = XenForo_Model::create('XenForo_Model_User');
		$criteria = array(
                //'user_state' => 'valid',
                'is_banned' => 0
                );
		$activeUsers = $userModel->getMostActiveUsers($criteria, array('limit' => 5));

		$viewParams = array(
			'selected' => $selected,
			'threads' => $threads,
			'activeUsers' => $activeUsers,
			'pages' => $pages
		);
		}
		
		else
		{
		$viewParams = array(
			'selected' => $selected,
			'pages' => $pages
		);
		}

		$wrapper = $this->responseView('Mypages_ViewPublic_Wrapper', $options->selectTemplate, $viewParams);
		$wrapper->subView = $subView;

		return $wrapper;
	}

	protected function _assertViewingPermissions($action)
	{
	}

	/**
	 * @return Mypages_Model_MyPages
	 */
	protected function _getMyPagesModel()
	{
		return $this->getModelFromCache('Mypages_Model_MyPages');
	}
}