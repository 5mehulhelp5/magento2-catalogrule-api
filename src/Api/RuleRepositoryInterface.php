<?php
/**
 * Copyright © 2026 Studio Raz. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace SR\CatalogRuleApi\Api;

/**
 * Catalog price rule CRUD interface
 *
 * @api
 */
interface RuleRepositoryInterface
{
    /**
     * Save catalog price rule.
     *
     * @param \SR\CatalogRuleApi\Api\Data\RuleInterface $rule
     * @return \SR\CatalogRuleApi\Api\Data\RuleInterface
     * @throws \Magento\Framework\Exception\InputException If there is a problem with the input
     * @throws \Magento\Framework\Exception\NoSuchEntityException If a rule ID is sent but the rule does not exist
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(\SR\CatalogRuleApi\Api\Data\RuleInterface $rule);

    /**
     * Get rule by ID.
     *
     * @param int $ruleId
     * @return \SR\CatalogRuleApi\Api\Data\RuleInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException If $ruleId is not found
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getById($ruleId);

    /**
     * Retrieve catalog price rules that match the specified criteria.
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \SR\CatalogRuleApi\Api\Data\RuleSearchResultInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(\Magento\Framework\Api\SearchCriteriaInterface $searchCriteria);

    /**
     * Delete rule by ID.
     *
     * @param int $ruleId
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($ruleId);
}
