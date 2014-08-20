<?php

class Mypages_ControllerAdmin_MyPages extends XenForo_ControllerAdmin_Abstract
{
	protected function _preDispatch($action)
	{
		$this->assertAdminPermission('mypages');
	}

	public function actionIndex()
	{
		$helpModel = $this->_getMyPagesModel();
		$pages = $helpModel->getMyPages();

		$viewParams = array(
			'pages' => $helpModel->preparePages($pages)
		);

		return $this->responseView('XenForo_ViewAdmin_MyPage_List', 'my_page_list', $viewParams);
	}

	protected function _getMyPageAddEditResponse(array $page)
	{
		$helpModel = $this->_getMyPagesModel();

		if (!empty($page['page_id']))
		{
			$title = $helpModel->getMyPageMasterTitlePhraseValue($page['page_id']);
			$description = $helpModel->getMyPageMasterDescriptionPhraseValue($page['page_id']);
			$template = $helpModel->getMyPageTemplate($page['page_id']);
		}
		else
		{
			$title = '';
			$description = '';
			$template = null;
		}

		$viewParams = array(
			'page' => $page,
			'title' => $title,
			'description' => $description,
			'template' => $template,
		);

		return $this->responseView('XenForo_ViewAdmin_MyPage_Edit', 'my_page_edit', $viewParams);
	}

	public function actionAdd()
	{
		return $this->_getMyPageAddEditResponse(array(
			'display_order' => 1
		));
	}

	public function actionEdit()
	{
		$page = $this->_getMyPageOrError();

		return $this->_getMyPageAddEditResponse($page);
	}

	public function actionSave()
	{
		$this->_assertPostOnly();

		$pageName = $this->_input->filterSingle('page_name', XenForo_Input::STRING);
		if ($pageName !== '')
		{
			$page = $this->_getMyPageOrError($pageName);
		}
		else
		{
			$page = null;
		}

		$dwInput = $this->_input->filter(array(
			'display_order' => XenForo_Input::UINT,
			'callback_class' => XenForo_Input::STRING,
			'callback_method' => XenForo_Input::STRING,
		));
		$extraInput = $this->_input->filter(array(
			'new_page_name' => XenForo_Input::STRING,
			'title' => XenForo_Input::STRING,
			'description' => XenForo_Input::STRING,
			'content' => XenForo_Input::STRING
		));

		$dw = XenForo_DataWriter::create('Mypages_DataWriter_MyPages');
		if ($page)
		{
			$dw->setExistingData($page, true);
		}
		$dw->bulkSet($dwInput);
		$dw->set('page_name', $extraInput['new_page_name']);
		$dw->setExtraData(Mypages_DataWriter_MyPages::DATA_TITLE, $extraInput['title']);
		$dw->setExtraData(Mypages_DataWriter_MyPages::DATA_DESCRIPTION, $extraInput['description']);
		$dw->setExtraData(Mypages_DataWriter_MyPages::DATA_CONTENT, $extraInput['content']);
		$dw->save();

		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			XenForo_Link::buildAdminLink('my-pages') . $this->getLastHash($dw->get('page_id'))
		);
	}

	public function actionDelete()
	{
		$page = $this->_getMyPageOrError();

		$dw = XenForo_DataWriter::create('Mypages_DataWriter_MyPages');
		$dw->setExistingData($page, true);

		if ($this->isConfirmedPost()) // delete add-on
		{
			$dw->delete();

			return $this->responseRedirect(
				XenForo_ControllerResponse_Redirect::SUCCESS,
				XenForo_Link::buildAdminLink('my-pages')
			);
		}
		else // show delete confirmation prompt
		{
			$viewParams = array(
				'page' => $page
			);

			return $this->responseView('XenForo_ViewAdmin_MyPage_Delete', 'my_page_delete', $viewParams);
		}
	}

	protected function _getMyPageOrError($pageName = null)
	{
		if ($pageName === null)
		{
			$pageName = $this->_input->filterSingle('page_name', XenForo_Input::STRING);
		}

		$info = $this->_getMyPagesModel()->getMyPageByName($pageName);
		if (!$info)
		{
			throw $this->responseException($this->responseError(new XenForo_Phrase('requested_my_page_not_found'), 404));
		}

		return $this->_getMyPagesModel()->preparePage($info);
	}

	/**
	 * @return Mypages_Model_MyPages
	 */
	protected function _getMyPagesModel()
	{
		return $this->getModelFromCache('Mypages_Model_MyPages');
	}
}