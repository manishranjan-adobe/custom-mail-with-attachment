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

use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Entity\Attribute\Source\Boolean;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Setup\EavSetup;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;

class ExcludeCartAbandonmentAlertAttribute implements DataPatchInterface
{
    /**
     * Constance enable exclude cart abandonment alert
     */
    public const ENABLE = 0;

    /**
     * @var ModuleDataSetupInterface
     */
    private ModuleDataSetupInterface $moduleDataSetup;

    /**
     * Define exclude cart abandonment alert attribute
     */
    public const PRODUCT_SMC_EXCLUDE_CART_ABANDONMENT_ALERT_ATTRIBUTE = 'exclude_cart_abandonment_alert';

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param EavSetupFactory $eavSetupFactory
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        EavSetupFactory $eavSetupFactory
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->eavSetupFactory = $eavSetupFactory;
    }

    /**
     * Apply method for attribute
     *
     * @return ExcludeCartAbandonmentAlertAttribute|void
     */
    public function apply()
    {
        $this->moduleDataSetup->getConnection()->startSetup();
        /** @var EavSetup $eavSetup */
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $this->addExcludeCartAbandonmentAttribute($eavSetup);
        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * Attribute details method
     *
     * @param EavSetup $eavSetup
     * @return void
     */
    public function addExcludeCartAbandonmentAttribute($eavSetup)
    {
        $eavSetup->addAttribute(
            Product::ENTITY,
            self::PRODUCT_SMC_EXCLUDE_CART_ABANDONMENT_ALERT_ATTRIBUTE,
            [
                'type' => 'int',
                'label' => 'Exclude Cart Abandonment Alert',
                'frontend_class' => '',
                'backend' => \Magento\Catalog\Model\Product\Attribute\Backend\Boolean::class,
                'input' => 'boolean',
                'sort_order' => '150',
                'source' => Boolean::class,
                'global' => ScopedAttributeInterface::SCOPE_WEBSITE,
                'default' => self::ENABLE,
                'visible' => true,
                'user_defined' => true,
                'required' => false,
                'searchable' => false,
                'filterable' => false,
                'comparable' => false,
                'visible_on_front' => false,
                'unique' => false,
                'apply_to' => '',
                'group' => 'General',
                'used_in_product_listing' => true,
                'is_used_in_grid' => false,
                'is_visible_in_grid' => false,
                'is_filterable_in_grid' => false
            ]
        );
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
}
