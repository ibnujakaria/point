<?php

namespace App\Model\HumanResource\Kpi;

use App\Model\MasterModel;

class KpiResult extends MasterModel
{
    protected $connection = 'tenant';

    public static $alias = 'kpi_result';
}
