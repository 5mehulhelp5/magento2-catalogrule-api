<?php
/**
 * Copyright © 2026 Studio Raz. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace SR\CatalogRuleApi\Model\Data;

use Magento\Framework\Api\AbstractExtensibleObject;
use SR\CatalogRuleApi\Api\Data\RuleExtensionInterface;
use SR\CatalogRuleApi\Api\Data\RuleInterface;

/**
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @codeCoverageIgnore
 */
class Rule extends AbstractExtensibleObject implements RuleInterface
{
    /**
     * @inheritdoc
     */
    public function getRuleId()
    {
        return $this->_get(self::RULE_ID);
    }

    /**
     * @inheritdoc
     */
    public function setRuleId($ruleId)
    {
        return $this->setData(self::RULE_ID, $ruleId);
    }

    /**
     * @inheritdoc
     */
    public function getName()
    {
        return $this->_get(self::NAME);
    }

    /**
     * @inheritdoc
     */
    public function setName($name)
    {
        return $this->setData(self::NAME, $name);
    }

    /**
     * @inheritdoc
     */
    public function getDescription()
    {
        return $this->_get(self::DESCRIPTION);
    }

    /**
     * @inheritdoc
     */
    public function setDescription($description)
    {
        return $this->setData(self::DESCRIPTION, $description);
    }

    /**
     * @inheritdoc
     */
    public function getIsActive()
    {
        return $this->_get(self::IS_ACTIVE);
    }

    /**
     * @inheritdoc
     */
    public function setIsActive($isActive)
    {
        return $this->setData(self::IS_ACTIVE, $isActive);
    }

    /**
     * @inheritdoc
     */
    public function getWebsiteIds()
    {
        return $this->_get(self::WEBSITE_IDS);
    }

    /**
     * @inheritdoc
     */
    public function setWebsiteIds(array $websiteIds)
    {
        return $this->setData(self::WEBSITE_IDS, $websiteIds);
    }

    /**
     * @inheritdoc
     */
    public function getCustomerGroupIds()
    {
        return $this->_get(self::CUSTOMER_GROUP_IDS);
    }

    /**
     * @inheritdoc
     */
    public function setCustomerGroupIds(array $customerGroupIds)
    {
        return $this->setData(self::CUSTOMER_GROUP_IDS, $customerGroupIds);
    }

    /**
     * @inheritdoc
     */
    public function getFromDate()
    {
        return $this->_get(self::FROM_DATE);
    }

    /**
     * @inheritdoc
     */
    public function setFromDate($fromDate)
    {
        return $this->setData(self::FROM_DATE, $fromDate);
    }

    /**
     * @inheritdoc
     */
    public function getToDate()
    {
        return $this->_get(self::TO_DATE);
    }

    /**
     * @inheritdoc
     */
    public function setToDate($toDate)
    {
        return $this->setData(self::TO_DATE, $toDate);
    }

    /**
     * @inheritdoc
     */
    public function getCondition()
    {
        return $this->_get(self::CONDITION);
    }

    /**
     * @inheritdoc
     */
    public function setCondition($condition)
    {
        return $this->setData(self::CONDITION, $condition);
    }

    /**
     * @inheritdoc
     */
    public function getStopRulesProcessing()
    {
        return $this->_get(self::STOP_RULES_PROCESSING);
    }

    /**
     * @inheritdoc
     */
    public function setStopRulesProcessing($stopRulesProcessing)
    {
        return $this->setData(self::STOP_RULES_PROCESSING, $stopRulesProcessing);
    }

    /**
     * @inheritdoc
     */
    public function getSortOrder()
    {
        return $this->_get(self::SORT_ORDER);
    }

    /**
     * @inheritdoc
     */
    public function setSortOrder($sortOrder)
    {
        return $this->setData(self::SORT_ORDER, $sortOrder);
    }

    /**
     * @inheritdoc
     */
    public function getSimpleAction()
    {
        return $this->_get(self::SIMPLE_ACTION);
    }

    /**
     * @inheritdoc
     */
    public function setSimpleAction($simpleAction)
    {
        return $this->setData(self::SIMPLE_ACTION, $simpleAction);
    }

    /**
     * @inheritdoc
     */
    public function getDiscountAmount()
    {
        return $this->_get(self::DISCOUNT_AMOUNT);
    }

    /**
     * @inheritdoc
     */
    public function setDiscountAmount($discountAmount)
    {
        return $this->setData(self::DISCOUNT_AMOUNT, $discountAmount);
    }

    /**
     * @inheritdoc
     *
     * @return RuleExtensionInterface|null
     */
    public function getExtensionAttributes()
    {
        return $this->_getExtensionAttributes();
    }

    /**
     * @inheritdoc
     *
     * @param RuleExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(RuleExtensionInterface $extensionAttributes)
    {
        return $this->_setExtensionAttributes($extensionAttributes);
    }
}
