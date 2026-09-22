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
use SR\CatalogRuleApi\Api\Data\RuleInterface;
use SR\CatalogRuleApi\Model\Data\Rule;
use SR\CatalogRuleApi\Model\Data\Validator\RequiredFields;

class RequiredFieldsTest extends TestCase
{
    /**
     * @var RequiredFields
     */
    private $validator;

    /**
     * @var ObjectManager
     */
    private $objectManagerHelper;

    protected function setUp(): void
    {
        $this->validator = new RequiredFields();
        $this->objectManagerHelper = new ObjectManager($this);
    }

    /**
     * @param array $data
     * @return void
     */
    #[DataProvider('invalidDataProvider')]
    public function testIsValidReturnsFalseForIncompleteData(array $data): void
    {
        $rule = $this->buildRule($data);

        self::assertFalse($this->validator->isValid($rule));
        self::assertNotEmpty($this->validator->getMessages());
    }

    /**
     * @return array<string, array{0: array}>
     */
    public static function invalidDataProvider(): array
    {
        $valid = [
            'name' => 'Summer sale',
            'website_ids' => [1],
            'customer_group_ids' => [0, 1],
            'simple_action' => RuleInterface::ACTION_BY_PERCENT,
        ];

        return [
            'missing name' => [array_merge($valid, ['name' => ''])],
            'null name' => [array_merge($valid, ['name' => null])],
            'empty website_ids' => [array_merge($valid, ['website_ids' => []])],
            'empty customer_group_ids' => [array_merge($valid, ['customer_group_ids' => []])],
            'non numeric website_ids' => [array_merge($valid, ['website_ids' => ['not-a-number']])],
            'unknown simple_action' => [array_merge($valid, ['simple_action' => 'unknown_action'])],
        ];
    }

    public function testIsValidReturnsTrueForCompleteData(): void
    {
        $rule = $this->buildRule([
            'name' => 'Summer sale',
            'website_ids' => [1],
            'customer_group_ids' => [0, 1],
            'simple_action' => RuleInterface::ACTION_BY_PERCENT,
        ]);

        self::assertTrue($this->validator->isValid($rule));
        self::assertEmpty($this->validator->getMessages());
    }

    public function testIsValidThrowsForNonRuleInput(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->validator->isValid(new \stdClass());
    }

    /**
     * @param array $data
     * @return Rule
     */
    private function buildRule(array $data): Rule
    {
        /** @var Rule $rule */
        $rule = $this->objectManagerHelper->getObject(Rule::class);
        $rule->setName($data['name']);
        $rule->setWebsiteIds($data['website_ids']);
        $rule->setCustomerGroupIds($data['customer_group_ids']);
        $rule->setSimpleAction($data['simple_action']);
        return $rule;
    }
}
