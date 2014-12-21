<?php

class Mypages_Model_MyPages extends XenForo_Model
{
	public function getThreads()
    {

        $viewableNodes = array(0);
		$nodeModel = XenForo_Model::create('XenForo_Model_Node');
		
		$nodePermissions = $nodeModel->getNodePermissionsForPermissionCombination();
		foreach ($nodePermissions as $nodeId => $node) {
			if (XenForo_Permission::hasContentPermission($node, 'view') && XenForo_Permission::hasContentPermission($node, 'viewOthers')) $viewableNodes[] = $nodeId;
		}

        return $this->_getDb()->fetchAll('
            SELECT thread.last_post_id as post_id,
       thread.user_id,
       thread.username as username ,
       thread.post_date,
       thread.title as threadtitle,
       thread.thread_id as thread_id, 
       thread.reply_count, 
       thread.view_count,
       thread.prefix_id,
       user.avatar_date, 
       user.display_style_group_id,
       forum.title as node_title, 
       forum.node_id as node_id
       FROM xf_thread as thread
       INNER JOIN xf_user AS user ON (thread.user_id = user.user_id)
       INNER JOIN xf_node AS forum ON (thread.node_id = forum.node_id)
       INNER JOIN xf_thread_prefix AS prefix ON (thread.prefix_id = prefix.prefix_id)
       WHERE (thread.node_id IS NULL OR thread.node_id IN (' . implode(',', $viewableNodes) . '))
       AND discussion_open = ? AND discussion_state = ?
       ORDER BY last_post_date DESC
       LIMIT 7;', array(1, 'visible')); 
    }

	public function getMyPageById($id)
	{
		return $this->_getDb()->fetchRow("
			SELECT *
			FROM xf_my_pages
			WHERE page_id = ?
		", $id);
	}

	public function getMyPageByName($name)
	{
		return $this->_getDb()->fetchRow("
			SELECT *
			FROM xf_my_pages
			WHERE page_name = ?
		", $name);
	}

	public function getMyPages()
	{
		return $this->fetchAllKeyed("
			SELECT *
			FROM xf_my_pages
			WHERE active = 1
			ORDER BY display_order
		", 'page_id');
	}

	public function getAllPages()
	{
		return $this->getPages();
	}

	public function getActivePages()
	{
		return $this->getPages(array(
			'active' => true
		));
	}

	/**
	 * Gets Pages matching the specified conditions
	 *
	 * @param array $conditions
	 * @param array $fetchOptions
	 *
	 * @return array [site id] => info
	 */
	
	public function getPages(array $conditions = array(), array $fetchOptions = array())
	{
		$whereClause = $this->preparePageConditions($conditions, $fetchOptions);

		$orderClause = $this->preparePageOrderOptions($fetchOptions, 'my_page.display_order');
		$limitOptions = $this->prepareLimitFetchOptions($fetchOptions);

		return $this->fetchAllKeyed($this->limitQueryResults('
			SELECT my_page.*
			FROM xf_my_pages AS my_page
			WHERE ' . $whereClause . '
			' . $orderClause . '
		', $limitOptions['limit'], $limitOptions['offset']
		), 'page_id');
	}

	/**
	 * Prepares an SQL 'WHERE' clause for use in getPages()
	 *
	 * @param array $conditions
	 * @param array $fetchOptions
	 *
	 * @return string
	 */
	public function preparePageConditions(array $conditions = array(), array $fetchOptions = array())
	{
		$db = $this->_getDb();
		$sqlConditions = array();

		if (!empty($conditions['all']))
		{
			$sqlConditions[] = '1=1';
		}

		if (!empty($conditions['page_id']))
		{
			if (is_array($conditions['page_id']))
			{
				$sqlConditions[] = 'my_page.page_id IN (' . $db->quote($conditions['page_id']) . ')';
			}
			else
			{
				$sqlConditions[] = 'my_page.page_id = ' . $db->quote($conditions['page_id']);
			}
		}
		if (isset($conditions['active']))
		{
			$sqlConditions[] = 'my_page.active = ' . ($conditions['active'] ? 1 : 0);
		}

		return $this->getConditionsForClause($sqlConditions);
	}

	/**
	 * Prepares an SQL 'ORDER' clause for use in getPages()
	 *
	 * @param array $fetchOptions
	 *
	 * @return string
	 */
	public function preparePageOrderOptions(array $fetchOptions = array(), $defaultOrderSql = '')
	{
		$choices = array(
			'display_order' => 'my_page.display_order'
		);
		return $this->getOrderByClause($choices, $fetchOptions, $defaultOrderSql);
	}

	public function preparePage(array $page)
	{
		$page['title'] = new XenForo_Phrase($this->getMyPageTitlePhraseName($page['page_id']));
		$page['description'] = new XenForo_Phrase($this->getMyPageDescriptionPhraseName($page['page_id']));

		return $page;
	}

	public function preparePages(array $pages)
	{
		foreach ($pages AS &$page)
		{
			$page = $this->preparePage($page);
		}

		return $pages;
	}

	/**
	 * Gets the my page's title phrase name.
	 *
	 * @param integer $pageId
	 *
	 * @return string
	 */
	public function getMyPageTitlePhraseName($pageId)
	{
		return 'my_page_' . $pageId . '_title';
	}

	/**
	 * Gets the my page's description phrase name.
	 *
	 * @param integer $pageId
	 *
	 * @return string
	 */
	public function getMyPageDescriptionPhraseName($pageId)
	{
		return 'my_page_' . $pageId . '_desc';
	}

	/**
	 * Gets the my page's master title phrase text.
	 *
	 * @param integer $pageId
	 *
	 * @return string
	 */
	public function getMyPageMasterTitlePhraseValue($pageId)
	{
		$phraseName = $this->getMyPageTitlePhraseName($pageId);
		return $this->_getPhraseModel()->getMasterPhraseValue($phraseName);
	}

	/**
	 * Gets the my page's master description phrase text.
	 *
	 * @param integer $pageId
	 *
	 * @return string
	 */
	public function getMyPageMasterDescriptionPhraseValue($pageId)
	{
		$phraseName = $this->getMyPageDescriptionPhraseName($pageId);
		return $this->_getPhraseModel()->getMasterPhraseValue($phraseName);
	}

	/**
	 * Gets the my page's template name.
	 *
	 * @param integer $pageId
	 *
	 * @return string
	 */
	public function getMyPageTemplateName($pageId)
	{
		return '_my_page_' . $pageId;
	}

	public function getMyPageTemplate($pageId)
	{
		$templateName = $this->getMyPageTemplateName($pageId);

		return $this->_getDb()->fetchRow("
			SELECT *
			FROM xf_template
			WHERE title = ?
				AND style_id = 0
		", $templateName);
	}

	public function getPageMasterTitlePhraseValue($pageId)
	{
		$phraseName = $this->getMyPageTitlePhraseName($pageId);
		return $this->_getPhraseModel()->getMasterPhraseValue($phraseName);
	}

	
	public function getPageMasterDescriptionPhraseValue($pageId)
	{
		$phraseName = $this->getMyPageDescriptionPhraseName($pageId);
		return $this->_getPhraseModel()->getMasterPhraseValue($phraseName);
	}

	/**
	 * Gets the phrase model object.
	 *
	 * @return XenForo_Model_Phrase
	 */
	protected function _getPhraseModel()
	{
		return $this->getModelFromCache('XenForo_Model_Phrase');
	}
}