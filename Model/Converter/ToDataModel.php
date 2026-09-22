<?php
/**
 * Copyright © 2026 Studio Raz. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace SR\CatalogRuleApi\Model\Converter;

use Magento\CatalogRule\Model\Rule as CatalogRule;
use Magento\Framework\App\ObjectManager;
use SR\CatalogRuleApi\Api\Data\RuleExtensionFactory;
use SR\CatalogRuleApi\Api\Data\RuleExtensionInterface;
use SR\CatalogRuleApi\Api\Data\RuleInterfaceFactory;
use SR\CatalogRuleApi\Model\Data\Rule as RuleDataModel;

/**
 * Converts the core ORM model (Magento\CatalogRule\Model\Rule) into the API DTO (Model\Data\Rule).
 *
 * Mirrors \Magento\SalesRule\Model\Converter\ToDataModel.
 */
class ToDataModel
{
    /**
     * @param RuleInterfaceFactory $ruleDataFactory
     * @param RuleExtensionFactory|null $extensionFactory
     */
    public function __construct(
        private readonly RuleInterfaceFactory $ruleDataFactory,
        private ?RuleExtensionFactory $extensionFactory = null
    ) {
        $this->extensionFactory = $extensionFactory ?: ObjectManager::getInstance()->get(RuleExtensionFactory::class);
    }

    /**
     * Convert a loaded core catalog rule model to the API DTO
     *
     * @param CatalogRule $ruleModel
     * @return RuleDataModel
     */
    public function toDataModel(CatalogRule $ruleModel): RuleDataModel
    {
        // Force the lazily-loaded website / customer group associations to populate before getData().
        $ruleModel->getWebsiteIds();
        $ruleModel->getCustomerGroupIds();

        $modelData = $ruleModel->getData();
        $modelData = $this->convertExtensionAttributesToObject($modelData);

        /** @var RuleDataModel $dataModel */
        $dataModel = $this->ruleDataFactory->create(['data' => $modelData]);

        $this->mapConditions($dataModel, $ruleModel);

        return $dataModel;
    }

    /**
     * Convert the serialized condition tree into the DTO's condition object, or null if there is none
     *
     * @param RuleDataModel $dataModel
     * @param CatalogRule $ruleModel
     * @return void
     */
    private function mapConditions(RuleDataModel $dataModel, CatalogRule $ruleModel): void
    {
        // Read the raw column before anything (e.g. getConditions()) lazily unserializes/unsets it.
        $conditionsSerialized = $ruleModel->getData('conditions_serialized');
        if (empty($conditionsSerialized)) {
            $dataModel->setCondition(null);
            return;
        }

        $dataModel->setCondition($ruleModel->getRuleCondition());
    }

    /**
     * Convert extension attributes of model to object if it is an array
     *
     * @param array $data
     * @return array
     */
    private function convertExtensionAttributesToObject(array $data): array
    {
        if (isset($data['extension_attributes']) && is_array($data['extension_attributes'])) {
            /** @var RuleExtensionInterface $attributes */
            $data['extension_attributes'] = $this->extensionFactory->create(['data' => $data['extension_attributes']]);
        }
        return $data;
    }
}
