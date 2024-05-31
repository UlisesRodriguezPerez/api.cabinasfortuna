<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;

class CabinExpense extends Model
{
    use HasFactory;
    use SoftDeletes;
    use LogsActivity;

    protected static $logAttributes = [
        'cabin_name', 
        'cleaning_cost', 
        'electricity_cost',
        'internet_cost',
        'extra_house_light_cost',
        'other_expenses',
        'month_year',
    ];
    
    protected static $logName = 'CabinExpenses';
    protected static $logOnlyDirty = true;
    protected static $submitEmptyLogs = false;

    protected $fillable = [
        'cabin_name', 
        'cleaning_cost',
        'electricity_cost', 
        'internet_cost', 
        'extra_house_light_cost', 
        'other_expenses', 
        'month_year'
    ];

    protected $dates = ['month_year'];
}
