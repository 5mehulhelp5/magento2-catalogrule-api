<?php
/**
 * Copyright © 2026 Studio Raz. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace SR\CatalogRuleApi\Test\Unit\Model\Converter;

use Magento\CatalogRule\Api\Data\ConditionInterface;
use Magento\CatalogRule\Model\Rule as CatalogRule;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SR\CatalogRuleApi\Api\Data\RuleExtensionFactory;
use SR\CatalogRuleApi\Api\Data\RuleExtensionInterface;
use SR\CatalogRuleApi\Api\Data\RuleInterfaceFactory;
use SR\CatalogRuleApi\Model\Converter\ToDataModel;
use SR\CatalogRuleApi\Model\Data\Rule as RuleDataModel;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ToDataModelTest extends TestCase
{
    /**
     * @var RuleInterfaceFactory|MockObject
     */
    private $ruleDataFactory;

    /**
     * @var RuleExtensionFactory|MockObject
     */
    private $extensionFactory;

    /**
     * @var ToDataModel
     */
    private $model;

    /**
     * @var ObjectManager
     */
    private $objectManagerHelper;

    protected function setUp(): void
    {
        $this->ruleDataFactory = $this->getMockBuilder(RuleInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();

        $this->extensionFactory = $this->getMockBuilder(RuleExtensionFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();

        $this->objectManagerHelper = new ObjectManager($this);
        $this->model = $this->objectManagerHelper->getObject(
            ToDataModel::class,
            [
                'ruleDataFactory' => $this->ruleDataFactory,
                'extensionFactory' => $this->extensionFactory,
            ]
        );
    }

    /**
     * Build a real Model\Data\Rule instance (its own constructor dependencies are auto-mocked)
     *
     * @return RuleDataModel
     */
    private function newRuleDataModel(): RuleDataModel
    {
        return $this->objectManagerHelper->getObject(RuleDataModel::class);
    }

    /**
     * @return array
     */
    private function getArrayData(): array
    {
        return [
            'rule_id' => 1,
            'name' => 'Test rule',
            'is_active' => 1,
            'website_ids' => [1],
            'customer_group_ids' => [0, 1],
            'conditions_serialized' => json_encode([
                'type' => \Magento\CatalogRule\Model\Rule\Condition\Combine::class,
                'aggregator' => 'all',
                'value' => '1',
                'conditions' => [
                    [
                        'type' => \Magento\CatalogRule\Model\Rule\Condition\Product::class,
                        'attribute' => 'sku',
                        'operator' => '==',
                        'value' => 'ABC',
                    ],
                ],
            ]),
            'extension_attributes' => [
                'some_extension_attribute' => 123,
            ],
        ];
    }

    public function testToDataModelCallsLazyAssociationGettersBeforeGetData(): void
    {
        $array = $this->getArrayData();

        $ruleModel = $this->createMock(CatalogRule::class);

        $callOrder = [];
        $ruleModel->method('getWebsiteIds')->willReturnCallback(function () use (&$callOrder) {
            $callOrder[] = 'getWebsiteIds';
            return [1];
        });
        $ruleModel->method('getCustomerGroupIds')->willReturnCallback(function () use (&$callOrder) {
            $callOrder[] = 'getCustomerGroupIds';
            return [0, 1];
        });
        $ruleModel->method('getData')->willReturnCallback(function ($key = '') use (&$callOrder, $array) {
            $callOrder[] = 'getData';
            return $key === '' ? $array : ($array[$key] ?? null);
        });
        $ruleModel->method('getRuleCondition')->willReturn($this->createMock(ConditionInterface::class));

        $arrayAttributes = $array;
        $attributesMock = $this->createMock(RuleExtensionInterface::class);
        $arrayAttributes['extension_attributes'] = $attributesMock;

        $this->extensionFactory->method('create')
            ->with(['data' => $array['extension_attributes']])
            ->willReturn($attributesMock);

        $dataModel = $this->newRuleDataModel();
        $this->ruleDataFactory->method('create')
            ->with(['data' => $arrayAttributes])
            ->willReturn($dataModel);

        $return = $this->model->toDataModel($ruleModel);

        self::assertSame($dataModel, $return);
        // getWebsiteIds()/getCustomerGroupIds() must run before the first getData() call, so that the
        // lazily-loaded associations are present in the array getData() returns.
        self::assertSame(['getWebsiteIds', 'getCustomerGroupIds'], array_slice($callOrder, 0, 2));
    }

    public function testToDataModelMapsSerializedConditionsViaCoreConverter(): void
    {
        $array = $this->getArrayData();
        $condition = $this->createMock(ConditionInterface::class);

        $ruleModel = $this->createMock(CatalogRule::class);
        $ruleModel->method('getWebsiteIds')->willReturn([1]);
        $ruleModel->method('getCustomerGroupIds')->willReturn([0, 1]);
        $ruleModel->method('getData')->willReturnCallback(
            static fn ($key = '') => $key === '' ? $array : ($array[$key] ?? null)
        );
        $ruleModel->expects(self::once())->method('getRuleCondition')->willReturn($condition);

        $this->extensionFactory->method('create')->willReturn($this->createMock(RuleExtensionInterface::class));

        $dataModel = $this->newRuleDataModel();
        $this->ruleDataFactory->method('create')->willReturn($dataModel);

        $this->model->toDataModel($ruleModel);

        self::assertSame($condition, $dataModel->getCondition());
    }

    public function testToDataModelSetsConditionNullWhenConditionsSerializedIsEmpty(): void
    {
        $array = $this->getArrayData();
        $array['conditions_serialized'] = '';

        $ruleModel = $this->createMock(CatalogRule::class);
        $ruleModel->method('getWebsiteIds')->willReturn([1]);
        $ruleModel->method('getCustomerGroupIds')->willReturn([0, 1]);
        $ruleModel->method('getData')->willReturnCallback(
            static fn ($key = '') => $key === '' ? $array : ($array[$key] ?? null)
        );
        $ruleModel->expects(self::never())->method('getRuleCondition');

        $this->extensionFactory->method('create')->willReturn($this->createMock(RuleExtensionInterface::class));

        $dataModel = $this->newRuleDataModel();
        $this->ruleDataFactory->method('create')->willReturn($dataModel);

        $this->model->toDataModel($ruleModel);

        self::assertNull($dataModel->getCondition());
    }

    public function testToDataModelConvertsExtensionAttributesArrayToObject(): void
    {
        $array = $this->getArrayData();
        $attributesMock = $this->createMock(RuleExtensionInterface::class);

        $ruleModel = $this->createMock(CatalogRule::class);
        $ruleModel->method('getWebsiteIds')->willReturn([1]);
        $ruleModel->method('getCustomerGroupIds')->willReturn([0, 1]);
        $ruleModel->method('getData')->willReturnCallback(
            static fn ($key = '') => $key === '' ? $array : ($array[$key] ?? null)
        );
        $ruleModel->method('getRuleCondition')->willReturn($this->createMock(ConditionInterface::class));

        $this->extensionFactory->expects(self::once())
            ->method('create')
            ->with(['data' => $array['extension_attributes']])
            ->willReturn($attributesMock);

        $dataModel = $this->newRuleDataModel();
        $this->ruleDataFactory->expects(self::once())
            ->method('create')
            ->with(self::callback(
                static fn (array $args) => $args['data']['extension_attributes'] === $attributesMock
            ))
            ->willReturn($dataModel);

        $this->model->toDataModel($ruleModel);
    }
}
