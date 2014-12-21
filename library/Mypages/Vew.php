<?php
class Mypages_Vew extends XenForo_ViewPublic_Base
{
    public function renderHtml()
    {
        /* Get the post we need to parse the content */
        $post = $this->_params['post'];

        /* Create the parser to parse the bbcodes */
        $bbCodeParser = new XenForo_BbCode_Parser(XenForo_BbCode_Formatter_Base::create('Base', array('view' => $this)));

        /* Parse options */
        $bbCodeOptions = array(
            'states' => array(
                'viewAttachments' => true //If you don't want to show attachments, set this to false
            )
        );

        /* parse the post message and merge into the post data */
        $this->_params['post']['messageParsed'] = XenForo_ViewPublic_Helper_Message::getBbCodeWrapper($post, $bbCodeParser, $bbCodeOptions);
    }
}