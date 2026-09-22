<?php
/**
 * Copyright © 2026 Studio Raz. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace SR\CatalogRuleApi\Api\Data;

/**
 * Catalog price rule data interface.
 *
 * Superset of the native \Magento\CatalogRule\Api\Data\RuleInterface: it additionally exposes
 * from_date/to_date, website_ids and customer_group_ids so the rule can be fully managed over REST.
 *
 * @api
 */
interface RuleInterface extends \Magento\Framework\Api\ExtensibleDataInterface
{
    /**#@+
     * Constants defined for keys of data array
     */
    public const RULE_ID = 'rule_id';
    public const NAME = 'name';
    public const DESCRIPTION = 'description';
    public const IS_ACTIVE = 'is_active';
    public const WEBSITE_IDS = 'website_ids';
    public const CUSTOMER_GROUP_IDS = 'customer_group_ids';
    public const FROM_DATE = 'from_date';
    public const TO_DATE = 'to_date';
    public const CONDITION = 'condition';
    public const STOP_RULES_PROCESSING = 'stop_rules_processing';
    public const SORT_ORDER = 'sort_order';
    public const SIMPLE_ACTION = 'simple_action';
    public const DISCOUNT_AMOUNT = 'discount_amount';
    /**#@-*/

    /**#@+
     * Simple action types, mirroring the literals accepted by
     * \Magento\CatalogRule\Model\Rule::validateDiscount()
     */
    public const ACTION_BY_PERCENT = 'by_percent';
    public const ACTION_BY_FIXED = 'by_fixed';
    public const ACTION_TO_PERCENT = 'to_percent';
    public const ACTION_TO_FIXED = 'to_fixed';
    /**#@-*/

    /**
     * Get rule id
     *
     * @return int|null
     */
    public function getRuleId();

    /**
     * Set rule id
     *
     * @param int|null $ruleId
     * @return $this
     */
    public function setRuleId($ruleId);

    /**
     * Get rule name
     *
     * @return string
     */
    public function getName();

    /**
     * Set rule name
     *
     * @param string $name
     * @return $this
     */
    public function setName($name);

    /**
     * Get rule description
     *
     * @return string|null
     */
    public function getDescription();

    /**
     * Set rule description
     *
     * @param string|null $description
     * @return $this
     */
    public function setDescription($description);

    /**
     * Whether the rule is active
     *
     * @return bool
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getIsActive();

    /**
     * Set whether the rule is active
     *
     * @param bool $isActive
     * @return $this
     */
    public function setIsActive($isActive);

    /**
     * Get the website ids the rule applies to
     *
     * @return int[]
     */
    public function getWebsiteIds();

    /**
     * Set the website ids the rule applies to
     *
     * @param int[] $websiteIds
     * @return $this
     */
    public function setWebsiteIds(array $websiteIds);

    /**
     * Get the customer group ids the rule applies to
     *
     * @return int[]
     */
    public function getCustomerGroupIds();

    /**
     * Set the customer group ids the rule applies to
     *
     * @param int[] $customerGroupIds
     * @return $this
     */
    public function setCustomerGroupIds(array $customerGroupIds);

    /**
     * Get the date the rule becomes active, in Y-m-d format
     *
     * @return string|null
     */
    public function getFromDate();

    /**
     * Set the date the rule becomes active, in Y-m-d format
     *
     * @param string|null $fromDate
     * @return $this
     */
    public function setFromDate($fromDate);

    /**
     * Get the date the rule stops being active, in Y-m-d format
     *
     * @return string|null
     */
    public function getToDate();

    /**
     * Set the date the rule stops being active, in Y-m-d format
     *
     * @param string|null $toDate
     * @return $this
     */
    public function setToDate($toDate);

    /**
     * Get the rule's condition tree
     *
     * @return \Magento\CatalogRule\Api\Data\ConditionInterface|null
     */
    public function getCondition();

    /**
     * Set the rule's condition tree. Replaces the entire existing tree.
     *
     * @param \Magento\CatalogRule\Api\Data\ConditionInterface|null $condition
     * @return $this
     */
    public function setCondition($condition);

    /**
     * Whether to stop further rule processing once this rule applies
     *
     * @return bool
     * @SuppressWarnings(PHPMD.BooleanGetMethodName)
     */
    public function getStopRulesProcessing();

    /**
     * Set whether to stop further rule processing once this rule applies
     *
     * @param bool $stopRulesProcessing
     * @return $this
     */
    public function setStopRulesProcessing($stopRulesProcessing);

    /**
     * Get sort order
     *
     * @return int|null
     */
    public function getSortOrder();

    /**
     * Set sort order
     *
     * @param int $sortOrder
     * @return $this
     */
    public function setSortOrder($sortOrder);

    /**
     * Get simple action, one of the ACTION_* constants
     *
     * @return string|null
     */
    public function getSimpleAction();

    /**
     * Set simple action, one of the ACTION_* constants
     *
     * @param string $simpleAction
     * @return $this
     */
    public function setSimpleAction($simpleAction);

    /**
     * Get discount amount
     *
     * @return float|null
     */
    public function getDiscountAmount();

    /**
     * Set discount amount
     *
     * @param float $discountAmount
     * @return $this
     */
    public function setDiscountAmount($discountAmount);

    /**
     * Retrieve existing extension attributes object or create a new one.
     *
     * @return \SR\CatalogRuleApi\Api\Data\RuleExtensionInterface|null
     */
    public function getExtensionAttributes();

    /**
     * Set an extension attributes object.
     *
     * @param \SR\CatalogRuleApi\Api\Data\RuleExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(\SR\CatalogRuleApi\Api\Data\RuleExtensionInterface $extensionAttributes);
}
