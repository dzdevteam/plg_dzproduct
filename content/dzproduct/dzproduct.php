<?php
/**
* @version     1.0.0
* @package     Joomla.Plugin
* @subpackage  Content.DZProduct
* @copyright   Copyright (C) 2013. All rights reserved.
* @license     GNU General Public License version 2 or later; see LICENSE.txt
* @author      DZ Team <dev@dezign.vn> - dezign.vn
*/

defined('_JEXEC') or die;

class PlgContentDZProduct extends JPlugin
{
    protected $_type='content';
    protected $_name='dzproduct';
    
    function __construct(&$subject, $config = array()) {
        parent::__construct(&$subject, $config = array());
        $this->loadLanguage();
        
        // Include the relation model and table
        JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR.'/components/com_dzproduct/models');
        JForm::addFormPath(JPATH_ADMINISTRATOR.'/components/com_dzproduct/models/forms');
        JTable::addIncludePath(JPATH_ADMINISTRATOR.'/components/com_dzproduct/tables');
    }
    
    public function onContentPrepareForm($form, $data)
    {
        if (!($form instanceof JForm))
        {
            $this->_subject->setError('JERROR_NOT_A_FORM');
            return false;
        }
        
        // Only inject our form when using categories with our extension
        if ($form->getName() != 'com_categories.categorycom_dzproduct.items.catid')
            return true;
        
        // Add the extra fields to the form.
        JForm::addFormPath(dirname(__FILE__) . '/group');
        $form->loadFile('group', false);
        return true;
    }
    
    public function onContentAfterSave($context, &$article, $isNew)
    {
        if ($context != 'com_categories.category' && JRequest::getVar('extension') != 'com_dzproduct.items.catid')
            return true;
        $catid = $article->id;
        
        $registry = new JRegistry();
        $registry->loadString($article->params);
        $params = $registry->toArray();
        $groupid = $params['groupid'];
        
        $relation_table = JTable::getInstance('Relation', 'DZProductTable');
        if ($relation_table->load(array('catid' => $article->id)))
            $id = $relation_table->id;
        else
            $id = 0;
        
        $data = array('id' => $id, 'catid' => $catid, 'groupid' => $groupid);
        $relation_model = JModelLegacy::getInstance('Relation', 'DZProductModel');
        
        // Get the form
        $form = $relation_model->getForm($data, false);
        if (!$form)
            return false;
        
        // Test whether the data is valid.
        $validData = $relation_model->validate($form, $data);
        if ($validData)
            $relation_model->save($validData);
        else
            return false;
        
        return true;
    }
}
