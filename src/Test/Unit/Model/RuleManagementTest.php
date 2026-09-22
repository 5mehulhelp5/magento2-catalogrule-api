<?php
/**
 * Copyright © 2026 Studio Raz. All rights reserved.
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace SR\CatalogRuleApi\Test\Unit\Model;

use Magento\CatalogRule\Model\Flag;
use Magento\CatalogRule\Model\FlagFactory;
use Magento\CatalogRule\Model\Rule\Job;
use Magento\Framework\Exception\LocalizedException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SR\CatalogRuleApi\Model\RuleManagement;

class RuleManagementTest extends TestCase
{
    /**
     * @var Job|MockObject
     */
    private $ruleJob;

    /**
     * @var FlagFactory|MockObject
     */
    private $flagFactory;

    /**
     * @var RuleManagement
     */
    private $management;

    protected function setUp(): void
    {
        // Job/DataObject's has*/get*/set* accessors (hasError, getError, setError, ...) only exist via
        // DataObject::__call(), so only the real "applyAll" method is stubbed - everything else keeps its
        // real magic-accessor behaviour, letting the test seed hasError()/getError() with setError().
        $this->ruleJob = $this->getMockBuilder(Job::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['applyAll'])
            ->getMock();

        $this->flagFactory = $this->getMockBuilder(FlagFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();

        $this->management = new RuleManagement($this->ruleJob, $this->flagFactory);
    }

    public function testApplyAllInvalidatesIndexerClearsFlagAndReturnsTrue(): void
    {
        $this->ruleJob->expects(self::once())->method('applyAll')->willReturnSelf();

        // setState()/getState() only exist via DataObject::__call(), so only the real "loadSelf"/"save"
        // methods are stubbed (to avoid touching the DB); the magic accessors keep their real behaviour,
        // so the flag's state after the call can be asserted with a real getState().
        $flag = $this->getMockBuilder(Flag::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['loadSelf', 'save'])
            ->getMock();
        $flag->method('loadSelf')->willReturnSelf();
        $flag->expects(self::once())->method('save')->willReturnSelf();
        $this->flagFactory->expects(self::once())->method('create')->willReturn($flag);

        $result = $this->management->applyAll();

        self::assertTrue($result);
        self::assertSame(0, $flag->getState());
    }

    public function testApplyAllThrowsLocalizedExceptionWithJobErrorMessage(): void
    {
        $this->ruleJob->setError('Indexer table is locked.');
        $this->ruleJob->expects(self::once())->method('applyAll')->willReturnSelf();

        $this->flagFactory->expects(self::never())->method('create');

        try {
            $this->management->applyAll();
            self::fail('Expected LocalizedException was not thrown.');
        } catch (LocalizedException $e) {
            self::assertStringContainsString('Indexer table is locked.', $e->getMessage());
        }
    }
}
