<?php

namespace App\Model\Master;

use App\Model\Inventory\Inventory;
use App\Model\MasterModel;

class Warehouse extends MasterModel
{
    protected $connection = 'tenant';

    protected $appends = ['label'];

    protected $fillable = [
        'branch_id',
        'code',
        'name',
        'address',
        'phone',
    ];

    public function getLabelAttribute()
    {
        $label = $this->code ? '[' . $this->code . '] ' : '';

        return $label . $this->name;
    }

    /**
     * The users that belong to the warehouse.
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_warehouse')->withPivot(['is_default']);;
    }

    public function inventories()
    {
        return $this->hasMany(Inventory::class, 'warehouse_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
