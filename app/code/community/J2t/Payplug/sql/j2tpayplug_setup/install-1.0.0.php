<?php

/** @var $installer Mage_Paypal_Model_Resource_Setup */
$installer = $this;

/**
 * Prepare database for install
 */
$installer->startSetup();


/**
 * Create table 'paypal/cert'
 */
$table = $installer->getConnection()
    ->newTable($installer->getTable('j2tpayplug/cert'))
    ->addColumn('cert_id', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'identity'  => true,
        'unsigned'  => true,
        'nullable'  => false,
        'primary'   => true,
        ), 'Cert Id')
    ->addColumn('website_id', Varien_Db_Ddl_Table::TYPE_SMALLINT, null, array(
        'unsigned'  => true,
        'nullable'  => false,
        'default'   => '0'
        ), 'Website Id')
    ->addColumn('content', Varien_Db_Ddl_Table::TYPE_TEXT, '64K', array(
        ), 'Content')
    ->addColumn('updated_at', Varien_Db_Ddl_Table::TYPE_TIMESTAMP, null, array(
        ), 'Updated At')
    ->addIndex($installer->getIdxName('j2tpayplug/cert', array('website_id')),
        array('website_id'))
    ->addForeignKey($installer->getFkName('j2tpayplug/cert', 'website_id', 'core/website', 'website_id'),
        'website_id', $installer->getTable('core/website'), 'website_id',
        Varien_Db_Ddl_Table::ACTION_CASCADE, Varien_Db_Ddl_Table::ACTION_CASCADE)
    ->setComment('Payplug Certificate Table');
$installer->getConnection()->createTable($table);


/**
 * Prepare database after install
 */
$installer->endSetup();

