<?php
/**
 * Copyright © 2026 Studio Raz. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace SR\CatalogRuleApi\Api\Data;

/**
 * @api
 */
interface RuleSearchResultInterface extends \Magento\Framework\Api\SearchResultsInterface
{
    /**
     * Get rules.
     *
     * @return \SR\CatalogRuleApi\Api\Data\RuleInterface[]
     */
    public function getItems();

    /**
     * Set rules.
     *
     * @param \SR\CatalogRuleApi\Api\Data\RuleInterface[] $items
     * @return $this
     */
    public function setItems(?array $items = null);
}
