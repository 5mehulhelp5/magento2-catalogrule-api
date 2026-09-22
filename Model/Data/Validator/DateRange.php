<?php
/**
 * Copyright © 2026 Studio Raz. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace SR\CatalogRuleApi\Model\Data\Validator;

use DateTime;
use Exception;
use InvalidArgumentException;
use Magento\Framework\Validator\AbstractValidator;
use SR\CatalogRuleApi\Model\Data\Rule;

/**
 * Validates from_date / to_date: both must parse and from_date must not be after to_date.
 */
class DateRange extends AbstractValidator
{
    /**
     * @inheritDoc
     */
    public function isValid($value)
    {
        $this->_clearMessages();
        if (!$value instanceof Rule) {
            throw new InvalidArgumentException('Expected instance of ' . Rule::class);
        }

        $fromDate = $this->parseDate($value->getFromDate(), 'from_date');
        $toDate = $this->parseDate($value->getToDate(), 'to_date');

        if ($fromDate !== null && $toDate !== null && $fromDate > $toDate) {
            $this->_addMessages([__('"from_date" must not be later than "to_date".')]);
        }

        return empty($this->getMessages());
    }

    /**
     * Parse a date field, recording a validation message if it is set but unparsable
     *
     * @param string|null $date
     * @param string $fieldName
     * @return DateTime|null
     */
    private function parseDate(?string $date, string $fieldName): ?DateTime
    {
        if ($date === null || $date === '') {
            return null;
        }
        try {
            return new DateTime($date);
        } catch (Exception $e) {
            $this->_addMessages([
                __(
                    'Invalid value of "%value" provided for the %fieldName field.',
                    ['fieldName' => $fieldName, 'value' => $date]
                ),
            ]);
            return null;
        }
    }
}
