<?php
/*******************************************************************************
 * ADOBE CONFIDENTIAL
 * ___________________
 *
 * Copyright 2022 Adobe
 * All Rights Reserved.
 *
 * NOTICE: All information contained herein is, and remains
 * the property of Adobe and its suppliers, if any. The intellectual
 * and technical concepts contained herein are proprietary to Adobe
 * and its suppliers and are protected by all applicable intellectual
 * property laws, including trade secret and copyright laws.
 * Adobe permits you to use and modify this file
 * in accordance with the terms of the Adobe license agreement
 * accompanying it (see LICENSE_ADOBE_PS.txt).
 * If you have received this file from a source other than Adobe,
 * then your use, modification, or distribution of it
 * requires the prior written permission from Adobe.
 ******************************************************************************/
declare(strict_types=1);

namespace Casio\SMC\Setup\Patch\Data;

use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\App\ResourceConnection;

class UpdateProductDefaultAttributeValue implements DataPatchInterface
{
    /**
     * Define exclude_cart_abandonment_alert attribute
     */
    public const PRODUCT_EXCLUDE_CART_ABANDONMENT_ALERT_ATTRIBUTE = 'exclude_cart_abandonment_alert';

    /**
     * Define exclude_cart_abandonment_alert attribute value
     */
    public const PRODUCT_EXCLUDE_CART_ABANDONMENT_ALERT_ATTRIBUTE_VALUE = '0';

    /**
     * Entity id
     *
     */
    public const ENTITY_ID = 4;

    /**
     * @var ModuleDataSetupInterface
     */
    private ModuleDataSetupInterface $moduleDataSetup;

    /**
     * @var ResourceConnection
     */
    protected $resourceConnection;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        ResourceConnection $resourceConnection
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Patch for update the attribute value
     *
     * @return UpdateProductDefaultAttributeValue|void
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        $table = $this->getDbConnection()->getTableName('catalog_product_entity');
        $select = $this->getDbConnection()->select()->from($table, 'row_id');
        $productIds = $this->getDbConnection()->fetchAll($select);
        $updateRows = [];
        foreach ($productIds as $id) {
            $updateRows[] = [
                "attribute_id" => $this->getAttributeId(self::PRODUCT_EXCLUDE_CART_ABANDONMENT_ALERT_ATTRIBUTE),
                "row_id" =>(int)$id['row_id'],
                "store_id" => 0,
                "value" => self::PRODUCT_EXCLUDE_CART_ABANDONMENT_ALERT_ATTRIBUTE_VALUE
            ];
        }
        if (!empty($updateRows)) {
            $this->getDbConnection()->insertOnDuplicate("catalog_product_entity_int", $updateRows, ["value"]);
        }
        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * @inheritdoc
     */
    public function getAliases(): array
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public static function getDependencies(): array
    {
        return [];
    }

    /**
     * Get connection
     *
     * @return \Magento\Framework\DB\Adapter\AdapterInterface
     */
    public function getDbConnection()
    {
        return $this->resourceConnection->getConnection();
    }

    /**
     * Get attribute id
     *
     * @param string $value
     * @return string
     */
    private function getAttributeId($value)
    {
        $table = $this->getDbConnection()->getTableName('eav_attribute');
        $select = $this->getDbConnection()->select()->from($table, 'attribute_id')->
        where("attribute_code = '" . $value . "' and entity_type_id= '" . self::ENTITY_ID . "'");
        return $this->getDbConnection()->fetchOne($select);
    }
}
