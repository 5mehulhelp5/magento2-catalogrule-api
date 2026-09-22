<?php
/**
 * Copyright © 2026 Studio Raz. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace SR\CatalogRuleApi\Model\Data;

use Magento\Framework\Validator\AbstractValidator;
use Magento\Framework\Validator\ValidatorInterface;

/**
 * Composite validator for the catalog rule DTO. Aggregates the validators injected via di.xml.
 */
class Validator extends AbstractValidator
{
    /**
     * @param array $validators
     */
    public function __construct(
        private readonly array $validators = []
    ) {
        array_map(fn (ValidatorInterface $validator) => $validator, $validators);
    }

    /**
     * @inheritDoc
     */
    public function isValid($value)
    {
        $this->_clearMessages();
        foreach ($this->validators as $validator) {
            if (!$validator->isValid($value)) {
                $this->_addMessages($validator->getMessages());
            }
        }
        return empty($this->getMessages());
    }
}
