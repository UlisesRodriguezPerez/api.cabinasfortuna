<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{

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
        'status'
    ];
    
    use HasFactory;
}
