<?php
/**
 * Copyright © 2026 Studio Raz. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace SR\CatalogRuleApi\Test\Unit\Model\Data\Validator;

use InvalidArgumentException;
use Magento\CatalogRule\Api\Data\ConditionInterface;
use Magento\CatalogRule\Model\Rule\Condition\Combine;
use Magento\CatalogRule\Model\Rule\Condition\Product;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SR\CatalogRuleApi\Model\Data\Rule;
use SR\CatalogRuleApi\Model\Data\Validator\ConditionType;

class ConditionTypeTest extends TestCase
{
    /**
     * @var ConditionType
     */
    private $validator;

    /**
     * @var ObjectManager
     */
    private $objectManagerHelper;

    protected function setUp(): void
    {
        $this->validator = new ConditionType([Combine::class, Product::class]);
        $this->objectManagerHelper = new ObjectManager($this);
    }

    public function testIsValidReturnsTrueForNoCondition(): void
    {
        $rule = $this->buildRule(null);

        self::assertTrue($this->validator->isValid($rule));
    }

    public function testIsValidReturnsTrueForWhitelistedTypes(): void
    {
        $product = $this->mockCondition(Product::class, []);
        $combine = $this->mockCondition(Combine::class, [$product]);

        $rule = $this->buildRule($combine);

        self::assertTrue($this->validator->isValid($rule));
        self::assertEmpty($this->validator->getMessages());
    }

    public function testIsValidReturnsFalseForNonWhitelistedNestedType(): void
    {
        $malicious = $this->mockCondition('Some\\Arbitrary\\Class', []);
        $combine = $this->mockCondition(Combine::class, [$malicious]);

        $rule = $this->buildRule($combine);

        self::assertFalse($this->validator->isValid($rule));
        self::assertNotEmpty($this->validator->getMessages());
    }

    public function testIsValidReturnsFalseForNonWhitelistedRootType(): void
    {
        $root = $this->mockCondition('Some\\Arbitrary\\Class', []);

        $rule = $this->buildRule($root);

        self::assertFalse($this->validator->isValid($rule));
    }

    public function testIsValidThrowsForNonRuleInput(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->validator->isValid(new \stdClass());
    }

    /**
     * @param string $type
     * @param ConditionInterface[] $conditions
     * @return ConditionInterface|MockObject
     */
    private function mockCondition(string $type, array $conditions)
    {
        $condition = $this->createMock(ConditionInterface::class);
        $condition->method('getType')->willReturn($type);
        $condition->method('getConditions')->willReturn($conditions);
        return $condition;
    }

    /**
     * @param ConditionInterface|null $condition
     * @return Rule
     */
    private function buildRule(?ConditionInterface $condition): Rule
    {
        /** @var Rule $rule */
        $rule = $this->objectManagerHelper->getObject(Rule::class);
        $rule->setCondition($condition);
        return $rule;
    }
}
