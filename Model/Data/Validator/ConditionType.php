<?php
/**
 * Copyright © 2026 Studio Raz. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace SR\CatalogRuleApi\Model\Data\Validator;

use InvalidArgumentException;
use Magento\CatalogRule\Api\Data\ConditionInterface;
use Magento\Framework\Validator\AbstractValidator;
use SR\CatalogRuleApi\Model\Data\Rule;

/**
 * Validates that every "type" in a submitted condition tree is one of a known-safe set of condition
 * classes. Defence-in-depth on top of \Magento\Rule\Model\ConditionFactory::create(), which already
 * refuses to instantiate anything that doesn't implement Rule\Condition\ConditionInterface: the admin
 * form only ever submits Combine/Product nodes, but REST is a wider surface, so the class list accepted
 * here is restricted the same way (injected via di.xml so it can be extended without touching this class).
 */
class ConditionType extends AbstractValidator
{
    /**
     * @param string[] $allowedTypes
     */
    public function __construct(
        private readonly array $allowedTypes = [
            \Magento\CatalogRule\Model\Rule\Condition\Combine::class,
            \Magento\CatalogRule\Model\Rule\Condition\Product::class,
        ]
    ) {
    }

    /**
     * @inheritDoc
     */
    public function isValid($value)
    {
        $this->_clearMessages();
        if (!$value instanceof Rule) {
            throw new InvalidArgumentException('Expected instance of ' . Rule::class);
        }

        if ($value->getCondition()) {
            $this->validate($value->getCondition());
        }

        return empty($this->getMessages());
    }

    /**
     * Recursively validate a condition node and its children
     *
     * @param ConditionInterface $condition
     * @return void
     */
    private function validate(ConditionInterface $condition): void
    {
        $type = $condition->getType();
        if ($type && !in_array($type, $this->allowedTypes, true)) {
            $this->_addMessages([
                __(
                    'Invalid value of "%value" provided for the %fieldName field.',
                    ['fieldName' => 'condition.type', 'value' => $type]
                ),
            ]);
        }

        foreach ((array)$condition->getConditions() as $subCondition) {
            $this->validate($subCondition);
        }
    }
}
