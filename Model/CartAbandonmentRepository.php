<?php
/**
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
 */
declare(strict_types=1);

namespace Casio\SMC\Model;

use Casio\SMC\Model\CartAbandonmentFactory;
use Casio\SMC\Model\ResourceModel\CartAbandonment;
use Magento\Framework\Exception\AlreadyExistsException;

class CartAbandonmentRepository
{
    /**
     * @var CartAbandonmentFactory
     */
    private CartAbandonmentFactory $cartAbandonmentFactory;
    /**
     * @var CartAbandonment
     */
    private CartAbandonment $cartAbandonment;

    /**
     * CartAbandonmentRepository constructor
     *
     * @param \Casio\SMC\Model\CartAbandonmentFactory $cartAbandonmentFactory
     * @param CartAbandonment $cartAbandonment
     */
    public function __construct(
        CartAbandonmentFactory $cartAbandonmentFactory,
        CartAbandonment        $cartAbandonment
    ) {

        $this->cartAbandonmentFactory = $cartAbandonmentFactory;
        $this->cartAbandonment = $cartAbandonment;
    }

    /**
     * Save cart abandonment
     *
     * @param array $data
     * @throws AlreadyExistsException
     */
    public function createEventTrigger($data)
    {
        $cartAbandonment = $this->cartAbandonmentFactory->create();
        $cartAbandonment->setData($data);
        $this->cartAbandonment->save($cartAbandonment);
    }
}
