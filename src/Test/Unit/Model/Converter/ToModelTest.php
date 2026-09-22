<?php
/**
 * Copyright © 2026 Studio Raz. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace SR\CatalogRuleApi\Test\Unit\Model\Converter;

use Magento\CatalogRule\Api\Data\ConditionInterface;
use Magento\CatalogRule\Model\Rule as CatalogRule;
use Magento\CatalogRule\Model\RuleFactory;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SR\CatalogRuleApi\Api\Data\RuleInterface;
use SR\CatalogRuleApi\Model\Converter\ToModel;
use SR\CatalogRuleApi\Model\Data\Rule as RuleDataModel;
use SR\CatalogRuleApi\Model\Data\Validator;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ToModelTest extends TestCase
{
    /**
     * @var RuleFactory|MockObject
     */
    private $ruleFactory;

    /**
     * @var DataObjectProcessor|MockObject
     */
    private $dataObjectProcessor;

    /**
     * @var Validator|MockObject
     */
    private $validator;

    /**
     * @var ToModel
     */
    private $model;

    protected function setUp(): void
    {
        $this->ruleFactory = $this->getMockBuilder(RuleFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();

        $this->dataObjectProcessor = $this->getMockBuilder(DataObjectProcessor::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['buildOutputDataArray'])
            ->getMock();

        $this->validator = $this->createMock(Validator::class);
        $this->validator->method('isValid')->willReturn(true);

        $this->model = new ToModel($this->ruleFactory, $this->dataObjectProcessor, $this->validator);
    }

    public function testToModelCreatesNewRuleWhenNoRuleId(): void
    {
        $dataModel = $this->createMock(RuleDataModel::class);
        $dataModel->method('getRuleId')->willReturn(null);
        $dataModel->method('getCondition')->willReturn(null);

        $ruleModel = $this->createMock(CatalogRule::class);
        $ruleModel->expects(self::never())->method('load');
        $ruleModel->method('getData')->willReturn(['name' => 'existing']);
        $ruleModel->method('validateData')->willReturn(true);

        $this->ruleFactory->expects(self::once())->method('create')->willReturn($ruleModel);

        $this->dataObjectProcessor->method('buildOutputDataArray')
            ->with($dataModel, RuleInterface::class)
            ->willReturn(['name' => 'New rule', 'website_ids' => [1]]);

        $ruleModel->expects(self::once())
            ->method('setData')
            ->with(['name' => 'New rule', 'website_ids' => [1]]);

        $result = $this->model->toModel($dataModel);

        self::assertSame($ruleModel, $result);
    }

    public function testToModelLoadsExistingRuleWhenRuleIdPresent(): void
    {
        $dataModel = $this->createMock(RuleDataModel::class);
        $dataModel->method('getRuleId')->willReturn(10);
        $dataModel->method('getCondition')->willReturn(null);

        $ruleModel = $this->createMock(CatalogRule::class);
        $ruleModel->expects(self::once())->method('load')->with(10)->willReturnSelf();
        $ruleModel->method('getId')->willReturn(10);
        $ruleModel->method('getData')->willReturn([]);
        $ruleModel->method('validateData')->willReturn(true);

        $this->ruleFactory->expects(self::once())->method('create')->willReturn($ruleModel);
        $this->dataObjectProcessor->method('buildOutputDataArray')->willReturn([]);

        $result = $this->model->toModel($dataModel);

        self::assertSame($ruleModel, $result);
    }

    public function testToModelThrowsNoSuchEntityExceptionWhenRuleIdNotFound(): void
    {
        $dataModel = $this->createMock(RuleDataModel::class);
        $dataModel->method('getRuleId')->willReturn(999);

        $ruleModel = $this->createMock(CatalogRule::class);
        $ruleModel->expects(self::once())->method('load')->with(999)->willReturnSelf();
        $ruleModel->method('getId')->willReturn(null);

        $this->ruleFactory->expects(self::once())->method('create')->willReturn($ruleModel);

        $this->expectException(NoSuchEntityException::class);

        $this->model->toModel($dataModel);
    }

    public function testToModelReplacesConditionTreeWhenConditionSubmitted(): void
    {
        $condition = $this->createMock(ConditionInterface::class);

        $dataModel = $this->createMock(RuleDataModel::class);
        $dataModel->method('getRuleId')->willReturn(null);
        $dataModel->method('getCondition')->willReturn($condition);

        $ruleModel = $this->createMock(CatalogRule::class);
        $ruleModel->method('getData')->willReturn([]);
        $ruleModel->method('validateData')->willReturn(true);
        $ruleModel->expects(self::once())->method('setRuleCondition')->with($condition);

        $this->ruleFactory->method('create')->willReturn($ruleModel);
        $this->dataObjectProcessor->method('buildOutputDataArray')->willReturn([]);

        $this->model->toModel($dataModel);
    }

    public function testToModelSkipsConditionMappingWhenNoConditionSubmitted(): void
    {
        $dataModel = $this->createMock(RuleDataModel::class);
        $dataModel->method('getRuleId')->willReturn(null);
        $dataModel->method('getCondition')->willReturn(null);

        $ruleModel = $this->createMock(CatalogRule::class);
        $ruleModel->method('getData')->willReturn([]);
        $ruleModel->method('validateData')->willReturn(true);
        $ruleModel->expects(self::never())->method('setRuleCondition');

        $this->ruleFactory->method('create')->willReturn($ruleModel);
        $this->dataObjectProcessor->method('buildOutputDataArray')->willReturn([]);

        $this->model->toModel($dataModel);
    }

    public function testToModelAggregatesCoreValidationErrorsIntoInputException(): void
    {
        $dataModel = $this->createMock(RuleDataModel::class);
        $dataModel->method('getRuleId')->willReturn(null);
        $dataModel->method('getCondition')->willReturn(null);

        $ruleModel = $this->createMock(CatalogRule::class);
        $ruleModel->method('getData')->willReturn([]);
        $ruleModel->method('validateData')->willReturn([__('Percentage discount should be between 0 and 100.')]);

        $this->ruleFactory->method('create')->willReturn($ruleModel);
        $this->dataObjectProcessor->method('buildOutputDataArray')->willReturn([]);

        $this->expectException(InputException::class);

        $this->model->toModel($dataModel);
    }

    public function testToModelAggregatesValidatorErrorsIntoInputException(): void
    {
        $dataModel = $this->createMock(RuleDataModel::class);
        $dataModel->method('getRuleId')->willReturn(null);
        $dataModel->method('getCondition')->willReturn(null);

        $ruleModel = $this->createMock(CatalogRule::class);
        $ruleModel->method('getData')->willReturn([]);
        $ruleModel->method('validateData')->willReturn(true);

        $this->ruleFactory->method('create')->willReturn($ruleModel);
        $this->dataObjectProcessor->method('buildOutputDataArray')->willReturn([]);

        $this->validator = $this->createMock(Validator::class);
        $this->validator->method('isValid')->willReturn(false);
        $this->validator->method('getMessages')
            ->willReturn([__('"website_ids" is required. Enter and try again.')]);
        $this->model = new ToModel($this->ruleFactory, $this->dataObjectProcessor, $this->validator);

        $this->expectException(InputException::class);

        $this->model->toModel($dataModel);
    }

    public function testToModelMergesCoreAndCustomValidationMessagesInSingleException(): void
    {
        $dataModel = $this->createMock(RuleDataModel::class);
        $dataModel->method('getRuleId')->willReturn(null);
        $dataModel->method('getCondition')->willReturn(null);

        $ruleModel = $this->createMock(CatalogRule::class);
        $ruleModel->method('getData')->willReturn([]);
        $ruleModel->method('validateData')->willReturn([__('Unknown action.')]);

        $this->ruleFactory->method('create')->willReturn($ruleModel);
        $this->dataObjectProcessor->method('buildOutputDataArray')->willReturn([]);

        $this->validator = $this->createMock(Validator::class);
        $this->validator->method('isValid')->willReturn(false);
        $this->validator->method('getMessages')
            ->willReturn([__('"name" is required. Enter and try again.')]);
        $this->model = new ToModel($this->ruleFactory, $this->dataObjectProcessor, $this->validator);

        try {
            $this->model->toModel($dataModel);
            self::fail('Expected InputException was not thrown.');
        } catch (InputException $e) {
            $messages = array_map(static fn ($error) => (string)$error->getMessage(), $e->getErrors());
            self::assertCount(2, $messages);
        }
    }
}
