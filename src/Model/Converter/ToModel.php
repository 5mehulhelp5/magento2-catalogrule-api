<?php
/**
 * Copyright © 2026 Studio Raz. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace SR\CatalogRuleApi\Model\Converter;

use Magento\CatalogRule\Model\Rule as CatalogRule;
use Magento\CatalogRule\Model\RuleFactory;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Reflection\DataObjectProcessor;
use SR\CatalogRuleApi\Api\Data\RuleInterface;
use SR\CatalogRuleApi\Model\Data\Rule as RuleDataModel;
use SR\CatalogRuleApi\Model\Data\Validator;

/**
 * Converts the API DTO (Model\Data\Rule) into the core ORM model (Magento\CatalogRule\Model\Rule).
 *
 * Mirrors \Magento\SalesRule\Model\Converter\ToModel: the merge is additive (existing model data is kept,
 * DTO fields override it) except for the condition tree, which is always fully replaced so that a PUT
 * behaves as a real replace rather than a partial update.
 */
class ToModel
{
    /**
     * @param RuleFactory $ruleFactory
     * @param DataObjectProcessor $dataObjectProcessor
     * @param Validator|null $validator
     */
    public function __construct(
        private readonly RuleFactory $ruleFactory,
        private readonly DataObjectProcessor $dataObjectProcessor,
        private ?Validator $validator = null
    ) {
        $this->validator = $validator ?? ObjectManager::getInstance()->get(Validator::class);
    }

    /**
     * Convert the DTO to a loaded/new core catalog rule model, ready to be saved
     *
     * @param RuleDataModel $dataModel
     * @return CatalogRule
     * @throws NoSuchEntityException
     * @throws InputException
     */
    public function toModel(RuleDataModel $dataModel): CatalogRule
    {
        $ruleId = $dataModel->getRuleId();

        if ($ruleId) {
            $ruleModel = $this->ruleFactory->create()->load($ruleId);
            if (!$ruleModel->getId()) {
                throw new NoSuchEntityException(
                    __('The catalog rule with the "%1" ID wasn\'t found. Verify the ID and try again.', $ruleId)
                );
            }
        } else {
            $ruleModel = $this->ruleFactory->create();
        }

        $modelData = $ruleModel->getData();

        $data = $this->dataObjectProcessor->buildOutputDataArray($dataModel, RuleInterface::class);
        $data = array_filter($data, static fn ($value) => $value !== null);
        $mergedData = array_merge($modelData, $data);

        $validateResult = $ruleModel->validateData(new DataObject($mergedData));
        $validationErrors = is_array($validateResult) ? $validateResult : [];

        $ruleModel->setData($mergedData);

        $this->mapConditions($ruleModel, $dataModel);

        if (!$this->validator->isValid($dataModel)) {
            $validationErrors = array_merge($validationErrors, $this->validator->getMessages());
        }

        if ($validationErrors) {
            $exception = new InputException();
            array_walk($validationErrors, $exception->addError(...));
            throw $exception;
        }

        return $ruleModel;
    }

    /**
     * Replace the rule's condition tree with the one from the DTO, when one was submitted
     *
     * The core \Magento\CatalogRule\Model\Rule::setRuleCondition() already does
     * getConditions()->setConditions([])->loadArray(...), so it discards whatever condition tree was
     * previously loaded on the model - this is what makes a PUT a real replace instead of a merge.
     *
     * @param CatalogRule $ruleModel
     * @param RuleDataModel $dataModel
     * @return void
     */
    private function mapConditions(CatalogRule $ruleModel, RuleDataModel $dataModel): void
    {
        $condition = $dataModel->getCondition();
        if ($condition) {
            $ruleModel->setRuleCondition($condition);
        }
    }
}
