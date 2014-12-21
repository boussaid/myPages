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
		$pages = $myPagesModel->getActivePages();
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
		/** Profiles Psts */
		$visitor = XenForo_Visitor::getInstance();
		$profilePostLimit = XenForo_Application::getOptions()->forumListNewProfilePosts;

		if ($profilePostLimit && $visitor->canViewProfilePosts())
		{
			/** @var XenForo_Model_ProfilePost $profilePostModel */
			$profilePostModel = $this->getModelFromCache('XenForo_Model_ProfilePost');
			$profilePosts = $profilePostModel->getLatestProfilePosts(
				array(
					'deleted' => false,
					'moderated' => false
				), array(
					'limit' => max($profilePostLimit * 2, 10),
					'join' =>
						XenForo_Model_ProfilePost::FETCH_USER_POSTER |
						XenForo_Model_ProfilePost::FETCH_USER_RECEIVER |
						XenForo_Model_ProfilePost::FETCH_USER_RECEIVER_PRIVACY,
					'permissionCombinationId' => $visitor->permission_combination_id
				)
			);
			foreach ($profilePosts AS $id => &$profilePost)
			{
				$receivingUser = $profilePostModel->getProfileUserFromProfilePost($profilePost);
				if (!$profilePostModel->canViewProfilePostAndContainer($profilePost, $receivingUser))
				{
					unset($profilePosts[$id]);
				}

				$profilePost = $profilePostModel->prepareProfilePost($profilePost, $receivingUser);
				if (!empty($profilePost['isIgnored']))
				{
					unset($profilePosts[$id]);
				}
			}
			$profilePosts = array_slice($profilePosts, 0, $profilePostLimit, true);
		}
		else
		{
			$profilePosts = array();
		}

		$viewParams = array(
			'selected' => $selected,
			'threads' => $threads,
			'activeUsers' => $activeUsers,
			'onlineUsers' => $this->_getSessionActivityList(),
			'canViewMemberList' => $this->getModelFromCache('XenForo_Model_User')->canViewMemberList(),
			'profilePosts' => $profilePosts,
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

	protected function _getSessionActivityList()
	{
		$visitor = XenForo_Visitor::getInstance();

		/** @var $sessionModel XenForo_Model_Session */
		$sessionModel = $this->getModelFromCache('XenForo_Model_Session');

		return $sessionModel->getSessionActivityQuickList(
			$visitor->toArray(),
			array('cutOff' => array('>', $sessionModel->getOnlineStatusTimeout())),
			($visitor['user_id'] ? $visitor->toArray() : null)
		);

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