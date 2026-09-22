<?php
/**
 * Copyright © 2026 Studio Raz. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace SR\CatalogRuleApi\Api;

/**
 * Catalog price rule management interface
 *
 * @api
 */
interface RuleManagementInterface
{
    /**
     * Apply all active catalog price rules.
     *
     * Equivalent to the "Apply Rules" button in Marketing > Catalog Price Rules.
     *
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function applyAll();
}
