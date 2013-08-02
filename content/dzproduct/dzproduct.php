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
    
    public function onContentAfterSave($context, $table, $isNew)
    {
        if ($context == 'com_categories.category' && JRequest::getVar('extension') == 'com_dzproduct.items.catid') {
            $catid = $table->id;
            
            $registry = new JRegistry();
            $registry->loadString($table->params);
            $params = $registry->toArray();
            $groupid = $params['groupid'];
            
            $relation_table = JTable::getInstance('Relation', 'DZProductTable');
            if ($relation_table->load(array('catid' => $table->id)))
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
        }
        
        if ($context == 'com_dzproduct.item') {
            $input = JFactory::getApplication()->input->getArray(array("jform" => array("fields" => 'array')));
            $fields = $input['jform']['fields'];
            $itemid = $table->id;
            foreach ($fields as $fieldid => $value) {
                $fielddata_table = JTable::getInstance('FieldData', 'DZProductTable');
                if ($fielddata_table->load(array('fieldid' => $fieldid, 'itemid' => $itemid)))
                    $id = $fielddata_table->id;
                else 
                    $id = 0;
                
                $fielddata_model = JModelLegacy::getInstance('FieldData', 'DZProductModel');
                $data = array('id' => $id, 'itemid' => $itemid, 'fieldid' => $fieldid, 'value' => $value);
                // Get the form
                $form = $fielddata_model->getForm($data, false);
                if (!$form)
                    continue;
                
                // Test whether the data is valid.
                $validData = $fielddata_model->validate($form, $data);
                if ($validData)
                    $fielddata_model->save($validData);
                else
                    continue;
            }
        }
        return true;
    }
    
    public function onContentAfterDelete($context, $table)
    {
        // Delete all relations associated to a category if it is deleted
        if ($context == 'com_categories.category' && JRequest::getVar('extension') == 'com_dzproduct.items.catid') {
            $catid = $table->id;
            
            $relation_table = JTable::getInstance('Relation', 'DZProductTable');
            if ($relation_table->load(array('catid' => $catid))) {
                $id = $relation_table->id;
                $relation_model = JModelLegacy::getInstance('Relation', 'DZProductModel');
                $relation_model->delete($id);
            }
        }
        
        // Delete all field data associated to an item if it is deleted
        if ($context == 'com_dzproduct.item') {
            $itemid = $table->id;
            
            $fielddata_table = JTable::getInstance('FieldData', 'DZProductTable');
            while ($fielddata_table->load(array('itemid' => $itemid))) {
                $id = $fielddata_table->id;
                $fielddata_model = JModelLegacy::getInstance('FieldData', 'DZProductModel');
                $fielddata_model->delete($id);
            }
        }
        return true;
    }
}
