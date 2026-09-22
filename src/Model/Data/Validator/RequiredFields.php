<?php
/**
 * Copyright © 2026 Studio Raz. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace SR\CatalogRuleApi\Model\Data\Validator;

use InvalidArgumentException;
use Magento\Framework\Validator\AbstractValidator;
use SR\CatalogRuleApi\Api\Data\RuleInterface;
use SR\CatalogRuleApi\Model\Data\Rule;

/**
 * Validates that the fields the core Magento\CatalogRule\Model\Rule save path always needs are present.
 */
class RequiredFields extends AbstractValidator
{
    private const ALLOWED_SIMPLE_ACTIONS = [
        RuleInterface::ACTION_BY_PERCENT,
        RuleInterface::ACTION_BY_FIXED,
        RuleInterface::ACTION_TO_PERCENT,
        RuleInterface::ACTION_TO_FIXED,
    ];

    /**
     * @inheritDoc
     */
    public function isValid($value)
    {
        $this->_clearMessages();
        if (!$value instanceof Rule) {
            throw new InvalidArgumentException('Expected instance of ' . Rule::class);
        }

        if ($value->getName() === null || trim((string)$value->getName()) === '') {
            $this->_addMessages([__('"%fieldName" is required. Enter and try again.', ['fieldName' => 'name'])]);
        }

        if (!$this->isNonEmptyIntArray($value->getWebsiteIds())) {
            $this->_addMessages(
                [__('"%fieldName" is required. Enter and try again.', ['fieldName' => 'website_ids'])]
            );
        }

        if (!$this->isNonEmptyIntArray($value->getCustomerGroupIds())) {
            $this->_addMessages(
                [__('"%fieldName" is required. Enter and try again.', ['fieldName' => 'customer_group_ids'])]
            );
        }

        $simpleAction = $value->getSimpleAction();
        if ($simpleAction !== null && !in_array($simpleAction, self::ALLOWED_SIMPLE_ACTIONS, true)) {
            $this->_addMessages([
                __(
                    'Invalid value of "%value" provided for the %fieldName field.',
                    ['fieldName' => 'simple_action', 'value' => $simpleAction]
                ),
            ]);
        }

        return empty($this->getMessages());
    }

    /**
     * Whether the given value is a non-empty array of (numeric) ids
     *
     * @param mixed $value
     * @return bool
     */
    private function isNonEmptyIntArray($value): bool
    {
        if (!is_array($value) || count($value) === 0) {
            return false;
        }
        foreach ($value as $id) {
            if (!is_numeric($id)) {
                return false;
            }
        }
        return true;
    }
}
