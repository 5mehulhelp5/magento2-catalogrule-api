<?php
/**
 * Copyright © 2026 Studio Raz. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace SR\CatalogRuleApi\Model;

use Magento\CatalogRule\Model\FlagFactory;
use Magento\CatalogRule\Model\ResourceModel\Rule\CollectionFactory;
use Magento\CatalogRule\Model\RuleFactory;
use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\NoSuchEntityException;
use SR\CatalogRuleApi\Api\Data\RuleInterface;
use SR\CatalogRuleApi\Api\Data\RuleSearchResultInterface;
use SR\CatalogRuleApi\Api\Data\RuleSearchResultInterfaceFactory;
use SR\CatalogRuleApi\Api\RuleRepositoryInterface;
use SR\CatalogRuleApi\Model\Converter\ToDataModel;
use SR\CatalogRuleApi\Model\Converter\ToModel;

/**
 * Catalog price rule CRUD class.
 *
 * Mirrors \Magento\SalesRule\Model\RuleRepository. Persistence is delegated entirely to the core
 * \Magento\CatalogRule\Model\Rule ORM model (via the ToModel/ToDataModel converters), so save/delete side
 * effects - reindex-on-commit, cache invalidation - are identical to saving/deleting in admin.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class RuleRepository implements RuleRepositoryInterface
{
    /**
     * @param RuleFactory $ruleFactory
     * @param ToDataModel $toDataModelConverter
     * @param ToModel $toModelConverter
     * @param RuleSearchResultInterfaceFactory $searchResultFactory
     * @param JoinProcessorInterface $extensionAttributesJoinProcessor
     * @param CollectionFactory $ruleCollectionFactory
     * @param FlagFactory $flagFactory
     * @param CollectionProcessorInterface|null $collectionProcessor
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        private readonly RuleFactory $ruleFactory,
        private readonly ToDataModel $toDataModelConverter,
        private readonly ToModel $toModelConverter,
        private readonly RuleSearchResultInterfaceFactory $searchResultFactory,
        private readonly JoinProcessorInterface $extensionAttributesJoinProcessor,
        private readonly CollectionFactory $ruleCollectionFactory,
        private readonly FlagFactory $flagFactory,
        private ?CollectionProcessorInterface $collectionProcessor = null
    ) {
        $this->collectionProcessor = $collectionProcessor
            ?: ObjectManager::getInstance()->get(CollectionProcessorInterface::class);
    }

    /**
     * @inheritdoc
     */
    public function save(RuleInterface $rule)
    {
        $model = $this->toModelConverter->toModel($rule);
        $model->save();
        $model->load($model->getId());
        $this->markDirty();
        return $this->toDataModelConverter->toDataModel($model);
    }

    /**
     * @inheritdoc
     */
    public function getById($ruleId)
    {
        $model = $this->ruleFactory->create()->load($ruleId);

        if (!$model->getId()) {
            throw new NoSuchEntityException(
                __('The catalog rule with the "%1" ID wasn\'t found. Verify the ID and try again.', $ruleId)
            );
        }

        return $this->toDataModelConverter->toDataModel($model);
    }

    /**
     * @inheritdoc
     */
    public function getList(SearchCriteriaInterface $searchCriteria)
    {
        /** @var \Magento\CatalogRule\Model\ResourceModel\Rule\Collection $collection */
        $collection = $this->ruleCollectionFactory->create();
        $this->extensionAttributesJoinProcessor->process($collection, RuleInterface::class);

        $this->collectionProcessor->process($searchCriteria, $collection);

        $rules = [];
        /** @var \Magento\CatalogRule\Model\Rule $ruleModel */
        foreach ($collection->getItems() as $ruleModel) {
            $ruleModel->load($ruleModel->getId());
            $rules[] = $this->toDataModelConverter->toDataModel($ruleModel);
        }

        /** @var RuleSearchResultInterface $searchResults */
        $searchResults = $this->searchResultFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setItems($rules);
        $searchResults->setTotalCount($collection->getSize());
        return $searchResults;
    }

    /**
     * @inheritdoc
     */
    public function deleteById($ruleId)
    {
        $model = $this->ruleFactory->create()->load($ruleId);

        if (!$model->getId()) {
            throw new NoSuchEntityException(
                __('The catalog rule with the "%1" ID wasn\'t found. Verify the ID and try again.', $ruleId)
            );
        }

        $model->delete();
        $this->markDirty();
        return true;
    }

    /**
     * Mark catalog rules as dirty, matching the admin Save/Delete controllers.
     *
     * Shows the "Catalog price rules were changed. Rules must be applied in order for the changes to
     * take effect" admin notice; cleared by RuleManagement::applyAll().
     *
     * @return void
     */
    private function markDirty(): void
    {
        $this->flagFactory->create()->loadSelf()->setState(1)->save();
    }
}
