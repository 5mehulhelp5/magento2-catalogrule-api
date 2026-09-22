<?php
/**
 * Copyright © 2026 Studio Raz. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace SR\CatalogRuleApi\Test\Unit\Model\Data\Validator;

use InvalidArgumentException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SR\CatalogRuleApi\Model\Data\Rule;
use SR\CatalogRuleApi\Model\Data\Validator\DateRange;

class DateRangeTest extends TestCase
{
    /**
     * @var DateRange
     */
    private $validator;

    /**
     * @var ObjectManager
     */
    private $objectManagerHelper;

    protected function setUp(): void
    {
        $this->validator = new DateRange();
        $this->objectManagerHelper = new ObjectManager($this);
    }

    /**
     * @param string|null $fromDate
     * @param string|null $toDate
     */
    #[DataProvider('validDatesProvider')]
    public function testIsValidReturnsTrue(?string $fromDate, ?string $toDate): void
    {
        $rule = $this->buildRule($fromDate, $toDate);

        self::assertTrue($this->validator->isValid($rule));
        self::assertEmpty($this->validator->getMessages());
    }

    /**
     * @return array<string, array{0: string|null, 1: string|null}>
     */
    public static function validDatesProvider(): array
    {
        return [
            'both null' => [null, null],
            'from before to' => ['2026-06-01', '2026-08-31'],
            'from equal to' => ['2026-06-01', '2026-06-01'],
            'only from set' => ['2026-06-01', null],
            'only to set' => [null, '2026-08-31'],
        ];
    }

    public function testIsValidReturnsFalseWhenToDateBeforeFromDate(): void
    {
        $rule = $this->buildRule('2026-08-31', '2026-06-01');

        self::assertFalse($this->validator->isValid($rule));
        self::assertNotEmpty($this->validator->getMessages());
    }

    public function testIsValidReturnsFalseForUnparsableDate(): void
    {
        $rule = $this->buildRule('not-a-date', null);

        self::assertFalse($this->validator->isValid($rule));
        self::assertNotEmpty($this->validator->getMessages());
    }

    public function testIsValidThrowsForNonRuleInput(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->validator->isValid(new \stdClass());
    }

    /**
     * @param string|null $fromDate
     * @param string|null $toDate
     * @return Rule
     */
    private function buildRule(?string $fromDate, ?string $toDate): Rule
    {
        /** @var Rule $rule */
        $rule = $this->objectManagerHelper->getObject(Rule::class);
        $rule->setFromDate($fromDate);
        $rule->setToDate($toDate);
        return $rule;
    }
}
