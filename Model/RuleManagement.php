<?php
/**
 * Copyright © 2026 Studio Raz. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace SR\CatalogRuleApi\Model;

use Magento\CatalogRule\Model\FlagFactory;
use Magento\CatalogRule\Model\Rule\Job;
use Magento\Framework\Exception\LocalizedException;
use SR\CatalogRuleApi\Api\RuleManagementInterface;

/**
 * Mirrors \Magento\CatalogRule\Controller\Adminhtml\Promo\Catalog\ApplyRules::execute(): invalidates the
 * catalogrule_rule indexer via the core Job model, then clears the "rules need to be applied" flag.
 */
class RuleManagement implements RuleManagementInterface
{
    /**
     * @param Job $ruleJob
     * @param FlagFactory $flagFactory
     */
    public function __construct(
        private readonly Job $ruleJob,
        private readonly FlagFactory $flagFactory
    ) {
    }

    /**
     * @inheritdoc
     */
    public function applyAll()
    {
        $this->ruleJob->applyAll();

        if ($this->ruleJob->hasError()) {
            throw new LocalizedException(__('We can\'t apply the rules. %1', $this->ruleJob->getError()));
        }

        $this->flagFactory->create()->loadSelf()->setState(0)->save();

        return true;
    }
}
