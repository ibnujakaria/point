<?php

namespace App\Model\HumanResource\Employee;

use App\Model\MasterModel;

class EmployeeSalaryHistory extends MasterModel
{
    protected $connection = 'tenant';

    public static $alias = 'employee_salary_history';

    /**
     * Get the employee that owns the salary history.
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id');
    }
}
