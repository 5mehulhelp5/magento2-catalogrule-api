<?php
/**
 * Copyright © 2026 Studio Raz. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace SR\CatalogRuleApi\Test\Unit\Model;

use Magento\CatalogRule\Model\Flag;
use Magento\CatalogRule\Model\FlagFactory;
use Magento\CatalogRule\Model\ResourceModel\Rule\Collection;
use Magento\CatalogRule\Model\ResourceModel\Rule\CollectionFactory;
use Magento\CatalogRule\Model\Rule as CatalogRule;
use Magento\CatalogRule\Model\RuleFactory;
use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SR\CatalogRuleApi\Api\Data\RuleInterface;
use SR\CatalogRuleApi\Api\Data\RuleSearchResultInterface;
use SR\CatalogRuleApi\Api\Data\RuleSearchResultInterfaceFactory;
use SR\CatalogRuleApi\Model\Converter\ToDataModel;
use SR\CatalogRuleApi\Model\Converter\ToModel;
use SR\CatalogRuleApi\Model\Data\Rule as RuleDataModel;
use SR\CatalogRuleApi\Model\RuleRepository;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class RuleRepositoryTest extends TestCase
{
    /**
     * @var RuleFactory|MockObject
     */
    private $ruleFactory;

    /**
     * @var ToDataModel|MockObject
     */
    private $toDataModelConverter;

    /**
     * @var ToModel|MockObject
     */
    private $toModelConverter;

    /**
     * @var RuleSearchResultInterfaceFactory|MockObject
     */
    private $searchResultFactory;

    /**
     * @var JoinProcessorInterface|MockObject
     */
    private $extensionAttributesJoinProcessor;

    /**
     * @var CollectionFactory|MockObject
     */
    private $ruleCollectionFactory;

    /**
     * @var FlagFactory|MockObject
     */
    private $flagFactory;

    /**
     * @var CollectionProcessorInterface|MockObject
     */
    private $collectionProcessor;

    /**
     * @var RuleRepository
     */
    private $repository;

    protected function setUp(): void
    {
        $this->ruleFactory = $this->getMockBuilder(RuleFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();

        $this->toDataModelConverter = $this->createMock(ToDataModel::class);
        $this->toModelConverter = $this->createMock(ToModel::class);

        $this->searchResultFactory = $this->getMockBuilder(RuleSearchResultInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();

        $this->extensionAttributesJoinProcessor = $this->createMock(JoinProcessorInterface::class);

        $this->ruleCollectionFactory = $this->getMockBuilder(CollectionFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();

        $this->flagFactory = $this->getMockBuilder(FlagFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();

        $this->collectionProcessor = $this->createMock(CollectionProcessorInterface::class);

        $objectManager = new ObjectManager($this);
        $this->repository = $objectManager->getObject(
            RuleRepository::class,
            [
                'ruleFactory' => $this->ruleFactory,
                'toDataModelConverter' => $this->toDataModelConverter,
                'toModelConverter' => $this->toModelConverter,
                'searchResultFactory' => $this->searchResultFactory,
                'extensionAttributesJoinProcessor' => $this->extensionAttributesJoinProcessor,
                'ruleCollectionFactory' => $this->ruleCollectionFactory,
                'flagFactory' => $this->flagFactory,
                'collectionProcessor' => $this->collectionProcessor,
            ]
        );
    }

    public function testSaveConvertsSavesReloadsMarksDirtyAndReturnsDataModel(): void
    {
        $rule = $this->createMock(RuleDataModel::class);
        $model = $this->createMock(CatalogRule::class);
        $dataModel = $this->createMock(RuleDataModel::class);

        $this->toModelConverter->expects(self::once())->method('toModel')->with($rule)->willReturn($model);
        $model->expects(self::once())->method('save');
        $model->method('getId')->willReturn(5);
        $model->expects(self::once())->method('load')->with(5);

        $flag = $this->mockFlag();

        $this->toDataModelConverter->expects(self::once())
            ->method('toDataModel')
            ->with($model)
            ->willReturn($dataModel);

        $result = $this->repository->save($rule);

        self::assertSame($dataModel, $result);
        self::assertSame(1, $flag->getState());
    }

    public function testGetByIdReturnsDataModelWhenFound(): void
    {
        $model = $this->createMock(CatalogRule::class);
        $dataModel = $this->createMock(RuleDataModel::class);

        $this->ruleFactory->expects(self::once())->method('create')->willReturn($model);
        $model->expects(self::once())->method('load')->with(7)->willReturnSelf();
        $model->method('getId')->willReturn(7);

        $this->toDataModelConverter->expects(self::once())
            ->method('toDataModel')
            ->with($model)
            ->willReturn($dataModel);

        $result = $this->repository->getById(7);

        self::assertSame($dataModel, $result);
    }

    public function testGetByIdThrowsNoSuchEntityExceptionWhenNotFound(): void
    {
        $model = $this->createMock(CatalogRule::class);
        $this->ruleFactory->method('create')->willReturn($model);
        $model->method('load')->willReturnSelf();
        $model->method('getId')->willReturn(null);

        $this->expectException(NoSuchEntityException::class);

        $this->repository->getById(999);
    }

    public function testDeleteByIdDeletesMarksDirtyAndReturnsTrue(): void
    {
        $model = $this->createMock(CatalogRule::class);
        $this->ruleFactory->method('create')->willReturn($model);
        $model->expects(self::once())->method('load')->with(3)->willReturnSelf();
        $model->method('getId')->willReturn(3);
        $model->expects(self::once())->method('delete');

        $flag = $this->mockFlag();

        $result = $this->repository->deleteById(3);

        self::assertTrue($result);
        self::assertSame(1, $flag->getState());
    }

    public function testDeleteByIdThrowsNoSuchEntityExceptionWhenNotFound(): void
    {
        $model = $this->createMock(CatalogRule::class);
        $this->ruleFactory->method('create')->willReturn($model);
        $model->method('load')->willReturnSelf();
        $model->method('getId')->willReturn(null);

        $this->expectException(NoSuchEntityException::class);

        $this->repository->deleteById(999);
    }

    public function testGetListProcessesCollectionAndBuildsSearchResults(): void
    {
        $searchCriteria = $this->createMock(SearchCriteriaInterface::class);
        $collection = $this->createMock(Collection::class);

        $item1 = $this->createMock(CatalogRule::class);
        $item1->method('getId')->willReturn(1);
        $dataModel1 = $this->createMock(RuleDataModel::class);

        $item2 = $this->createMock(CatalogRule::class);
        $item2->method('getId')->willReturn(2);
        $dataModel2 = $this->createMock(RuleDataModel::class);

        $this->ruleCollectionFactory->expects(self::once())->method('create')->willReturn($collection);

        $this->extensionAttributesJoinProcessor->expects(self::once())
            ->method('process')
            ->with($collection, RuleInterface::class);

        $this->collectionProcessor->expects(self::once())
            ->method('process')
            ->with($searchCriteria, $collection);

        $collection->method('getItems')->willReturn([$item1, $item2]);
        $collection->method('getSize')->willReturn(2);

        $this->toDataModelConverter->method('toDataModel')->willReturnMap([
            [$item1, $dataModel1],
            [$item2, $dataModel2],
        ]);

        $searchResults = $this->createMock(RuleSearchResultInterface::class);
        $searchResults->expects(self::once())->method('setSearchCriteria')->with($searchCriteria);
        $searchResults->expects(self::once())->method('setItems')->with([$dataModel1, $dataModel2]);
        $searchResults->expects(self::once())->method('setTotalCount')->with(2);

        $this->searchResultFactory->expects(self::once())->method('create')->willReturn($searchResults);

        $result = $this->repository->getList($searchCriteria);

        self::assertSame($searchResults, $result);
    }

    /**
     * setState()/getState() only exist via DataObject::__call(), so only the real "loadSelf"/"save"
     * methods are stubbed (to avoid touching the DB); the magic accessors keep their real behaviour, so
     * the flag's state after the call under test can be asserted with a real getState().
     *
     * @return Flag|MockObject
     */
    private function mockFlag()
    {
        $flag = $this->getMockBuilder(Flag::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['loadSelf', 'save'])
            ->getMock();
        $flag->method('loadSelf')->willReturnSelf();
        $flag->expects(self::once())->method('save')->willReturnSelf();
        $this->flagFactory->expects(self::once())->method('create')->willReturn($flag);
        return $flag;
    }
}
