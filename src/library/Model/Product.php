<?php
/**
 * Copyright 2022-2023 FOSSBilling
 * Copyright 2011-2021 BoxBilling, Inc.
 * SPDX-License-Identifier: Apache-2.0
 *
 * @copyright FOSSBilling (https://www.fossbilling.org)
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 */

class Model_Product extends \RedBeanPHP\SimpleModel implements \FOSSBilling\InjectionAwareInterface
{
    public const STATUS_ENABLED    = 'enabled';
    public const STATUS_DISABLED   = 'disabled';

    public const CUSTOM            = 'custom';
    public const LICENSE           = 'license';
    public const ADDON             = 'addon';
    public const DOMAIN            = 'domain';
    public const DOWNLOADABLE      = 'downloadable';
    public const HOSTING           = 'hosting';
    public const MEMBERSHIP        = 'membership';
    public const VPS               = 'vps';

    public const SETUP_AFTER_ORDER     = 'after_order';
    public const SETUP_AFTER_PAYMENT   = 'after_payment';
    public const SETUP_MANUAL          = 'manual';

    protected ?\Pimple\Container $di = null;

    public function setDi(\Pimple\Container $di): void
    {
        $this->di = $di;
    }

    public function getDi(): ?\Pimple\Container
    {
        return $this->di;
    }

    public function getTable()
    {
        $tableName = 'Model_Product'. ucfirst($this->type). 'Table';
        if(!class_exists($tableName)) {
            $tableName = 'Model_ProductTable';
        }
        $productTable = new $tableName;
        $productTable->setDi($this->di);
        return $productTable;
    }

    public function getService()
    {
        return $this->di['mod_service']('service'.$this->type);
    }
}
