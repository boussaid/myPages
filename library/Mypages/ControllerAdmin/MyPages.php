<?php

class Mypages_ControllerAdmin_MyPages extends XenForo_ControllerAdmin_Abstract
{
	protected function _preDispatch($action)
	{
		$this->assertAdminPermission('mypages');
	}

	public function actionIndex()
	{
		$myPageModel = $this->_getMyPagesModel();
		$pages = $myPageModel->getAllPages();

		$viewParams = array(
			'pages' => $myPageModel->preparePages($pages)
		);

		return $this->responseView('Mypages_ViewAdmin_MyPage_List', 'my_page_list', $viewParams);
	}

	protected function _getMyPageAddEditResponse(array $page)
	{
		$myPageModel = $this->_getMyPagesModel();

		if (!empty($page['page_id']))
		{
			$title = $myPageModel->getMyPageMasterTitlePhraseValue($page['page_id']);
			$description = $myPageModel->getMyPageMasterDescriptionPhraseValue($page['page_id']);
			$template = $myPageModel->getMyPageTemplate($page['page_id']);
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

		return $this->responseView('Mypages_ViewAdmin_MyPages_Edit', 'my_page_edit', $viewParams);
	}

	public function actionAdd()
	{
		return $this->_getMyPageAddEditResponse(array(
			'display_order' => 1,
			'active' => 1
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
			'active' => XenForo_Input::BOOLEAN,
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

	protected function _switchPageActiveStateAndGetResponse($pagId, $activeState)
	{
		$dw = XenForo_DataWriter::create('Mypages_DataWriter_MyPages');
		$dw->setExistingData($pagId);
		$dw->set('active', $activeState);
		$dw->save();

		return $this->responseRedirect(
			XenForo_ControllerResponse_Redirect::SUCCESS,
			XenForo_Link::buildAdminLink('my-pages') //. $this->getLastHash($dw->get('page_id'))
		);
	}

	/**
	 * Enables the specified page.
	 *
	 * @return XenForo_ControllerResponse_Abstract
	 */
	public function actionEnable()
	{
		// can be requested over GET, so check for the token manually
		$this->_checkCsrfFromToken($this->_input->filterSingle('_xfToken', XenForo_Input::STRING));

		$pagId = $this->_input->filterSingle('page_id', XenForo_Input::STRING);
		return $this->_switchPageActiveStateAndGetResponse($pagId, 1);
	}

	/**
	 * Disables the specified page.
	 *
	 * @return XenForo_ControllerResponse_Abstract
	 */
	public function actionDisable()
	{
		// can be requested over GET, so check for the token manually
		$this->_checkCsrfFromToken($this->_input->filterSingle('_xfToken', XenForo_Input::STRING));

		$pagId = $this->_input->filterSingle('page_id', XenForo_Input::STRING);
		return $this->_switchPageActiveStateAndGetResponse($pagId, 0);
	}

	public function actionToggle()
	{
		return $this->_getToggleResponse(
			$this->_getMyPagesModel()->getAllPages(),
			'Mypages_DataWriter_MyPages',
			'my-pages',
			'active'
		);
	}
/*
	public function actionExport()
	{
		$this->_assertPostOnly();

		$export = $this->_input->filterSingle('export', array(XenForo_Input::STRING, 'array' => true));
		$pages = $this->_getMyPagesModel()->getPagesByIds($export);
		//$myPageModel = $this->_getMyPagesModel();

		$this->_routeMatch->setResponseType('xml');

		$viewParams = array(
			'xml' => $this->_getMyPagesModel()->getPageExportXml($pages)
		);

		return $this->responseView('Mypages_ViewAdmin_Export', '', $viewParams);
	}

	public function actionImport()
	{
		if ($this->isConfirmedPost())
		{
			$upload = XenForo_Upload::getUploadedFile('upload');
			if (!$upload)
			{
				return $this->responseError(new XenForo_Phrase('please_provide_valid_bb_code_xml_file'));
			}

			$document = $this->getHelper('Xml')->getXmlFromFile($upload);
			$this->_getBbCodeModel()->importCustomBbCodeXml($document);

			return $this->responseRedirect(
					XenForo_ControllerResponse_Redirect::SUCCESS,
					XenForo_Link::buildAdminLink('bb-codes')
				);
		}
		else
		{
			return $this->responseView('XenForo_ViewAdmin_BbCode_Import', 'bb_code_import');
		}
	}

	
	protected function _getPageOrError($id = null)
	{
		if ($id === null)
		{
			$id = $this->_input->filterSingle('page_id', XenForo_Input::STRING);
		}

		$info = $this->_getBbCodeModel()->getBbCodeById($id);
		if (!$info)
		{
			throw $this->responseException($this->responseError(new XenForo_Phrase('requested_bb_code_not_found'), 404));
		}

		return $this->_getBbCodeModel()->prepareBbCode($info);
	}
*/
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