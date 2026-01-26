<?php

declare(strict_types=1);

namespace Valsis\RoCompanyLookup\Batch;

use DateTimeInterface;
use Valsis\RoCompanyLookup\RoCompanyLookupManager;

class BatchLookup
{
    /**
     * @var array<int, int|string>
     */
    protected array $cuis;

    protected ?DateTimeInterface $date;

    /**
     * @param  array<int, int|string>  $cuis
     */
    public function __construct(
        protected RoCompanyLookupManager $manager,
        array $cuis,
        ?DateTimeInterface $date = null
    ) {
        $this->cuis = $cuis;
        $this->date = $date;
    }

    public function date(DateTimeInterface $date): self
    {
        $this->date = $date;

        return $this;
    }

    /**
     * @return array<int, \Valsis\RoCompanyLookup\Data\CompanySimpleData>
     */
    public function get(): array
    {
        return $this->manager->batchNow($this->cuis, $this->date);
    }
}
