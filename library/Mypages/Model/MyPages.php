<?php

class Mypages_Model_MyPages extends XenForo_Model
{
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
			ORDER BY display_order
		", 'page_id');
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
	 * Gets the help page's title phrase name.
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
	 * Gets the help page's description phrase name.
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
	 * Gets the help page's master title phrase text.
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
	 * Gets the help page's master description phrase text.
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
	 * Gets the help page's template name.
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

	public function getThreads()
    {

        $viewableNodes = array(0);
		$nodeModel = XenForo_Model::create('XenForo_Model_Node');
		
		$nodePermissions = $nodeModel->getNodePermissionsForPermissionCombination();
		foreach ($nodePermissions as $nodeId => $node) {
			if (XenForo_Permission::hasContentPermission($node, 'view') && XenForo_Permission::hasContentPermission($node, 'viewOthers')) $viewableNodes[] = $nodeId;
		}

        return $this->_getDb()->fetchAll("
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
       LEFT JOIN xf_user AS user ON (thread.user_id = user.user_id)
       LEFT JOIN xf_node AS forum ON (thread.node_id = forum.node_id)
       LEFT JOIN xf_thread_prefix AS prefix ON (thread.prefix_id = prefix.prefix_id)
       WHERE (thread.node_id IS NULL OR thread.node_id IN ('" . implode("','", $viewableNodes) . "'))
       AND discussion_open = 1 AND discussion_state ='visible'
       ORDER BY last_post_date DESC
       LIMIT 7;"); 
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