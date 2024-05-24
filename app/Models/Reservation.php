<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Reservation extends Model
{

    use SoftDeletes;

    protected $fillable = [
        'name', 
        'phoneNumber', 
        'date', 
        'adults', 
        'children', 
        'cabin', 
        'nights', 
        'amountUSD', 
        'amountCRC', 
        'agency', 
        'commission', 
        'paidToUlisesUSD', 
        'paidToDeyaniraUSD', 
        'paidToUlisesCRC', 
        'paidToDeyaniraCRC', 
        'invoiceNeeded', 
        'paidToDeyanira', 
        'pendingToPay', 
        'pendingAmountUSD', 
        'pendingAmountCRC', 
        'note',
        'google_event_id',
        'created_by',
        'status', 
        'amountCRCToUSD', 
        'amountUSDToCRC', 
        'CHANGE_DOLLAR_TO_COLON', 
        'CHANGE_COLON_TO_DOLLAR'
    ];
    
    use HasFactory;
}
