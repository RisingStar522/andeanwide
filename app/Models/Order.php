<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'recipient_id',
        'pair_id',
        'priority_id',
        'sended_amount',
        'received_amount',
        'usd_amount',
        'rate',
        'payment_amount',
        'payment_method',
        'transaction_cost',
        'priority_cost',
        'tax',
        'tax_pct',
        'total_cost',
        'payment_code',
        'remitter_id',
        'purpose'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function recipient()
    {
        return $this->belongsTo(Recipient::class);
    }

    public function pair()
    {
        return $this->belongsTo(Pair::class);
    }

    public function priority()
    {
        return $this->belongsTo(Priority::class);
    }

    public function getIsFilledAttribute()
    {
        return isset($this->filled_at);
    }

    public function getIsPaymentRejectedAttribute()
    {
        return isset($this->payment) && isset($this->payment->rejected_at);
    }

    public function getIsPaymentVerifiedAttribute()
    {
        return isset($this->payment) && isset($this->payment->verified_at);
    }

    public function getIsVerifiedAttribute()
    {
        return isset($this->verified_at);
    }

    public function getIsRejectedAttribute()
    {
        return isset($this->rejected_at);
    }

    public function getIsExpiredAttribute()
    {
        return isset($this->expired_at);
    }

    public function getIsCompletedAttribute()
    {
        return isset($this->completed_at);
    }

    public function getIsCompliancedAttribute()
    {
        return isset($this->complianced_at);
    }

    public function getIsPayoutAttribute()
    {
        return isset($this->payed_at);
    }

    public function getIsPayoutReceivedAttribute()
    {
        return $this->isPayout && $this->payout_status === 'Received';
    }

    public function getIsPayoutCompletedAttribute()
    {
        return $this->isPayout && $this->payout_status === 'Completed';
    }

    public function getIsPayoutCancelledAttribute()
    {
        return $this->isPayout && $this->payout_status === 'Cancelled';
    }

    public function getIsPayoutRejectedAttribute()
    {
        return $this->isPayout && $this->payout_status === 'Rejected';
    }

    public function getIsPayoutDeliveredAttribute()
    {
        return $this->isPayout && $this->payout_status === 'Delivered';
    }

    public function getIsPayoutOnHoldAttribute()
    {
        return $this->isPayout && $this->payout_status === 'On Hold';
    }

    public function getStatusAttribute()
    {
        if ($this->isExpired) {
            return 'EXPIRED';
        } else if ($this->isRejected) {
            if ($this->isPayoutCancelled) {
                return 'PAYOUT_CANCELLED';
            } else if ($this->isPaymentRejected) {
                return 'PAYMENT_REJECTED';
            }
            return 'ORDER_REJECTED';
        } else if ($this->isCompleted) {
            return 'COMPLETED';
        } else if ($this->isPayoutReceived) {
            return 'PAYOUT_RECEIVED';
        } else if ($this->isPayoutCompleted) {
            return 'PAYOUT_COMPLETED';
        } else if ($this->isPayoutCancelled) {
            return 'PAYOUT_CANCELLED';
        } else if ($this->isPayoutRejected) {
            return 'PAYOUT_REJECTED';
        } else if ($this->isPayoutDelivered) {
            return 'PAYOUT_DELIVERED';
        } else if ($this->isPayoutOnHold) {
            return 'PAYOUT_ONHOLD';
        } else if ($this->isVerified) {
            return 'ORDER_VERIFIED';
        } else if ($this->isPaymentVerified) {
            return 'PAYMENT_VERIFIED';
        } else if($this->isFilled) {
            return 'FILLED';
        }
        return 'INCOMPLETED';
    }

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    public function remitter()
    {
        return $this->belongsTo(Remitter::class);
    }

    public function getCanValidatePaymentAttribute()
    {
        return $this->payment && is_null($this->rejected_at) && is_null($this->expired_at) && is_null($this->payment->rejected_at) && is_null($this->payment->verified_at);
    }

    public function getCanValidateOrderAttribute()
    {
        return is_null($this->rejected_at) && is_null($this->expired_at) && is_null($this->completed_at) && isset($this->payment->verified_at);
    }

    public function getCanPayoutOrderAttribute()
    {
        return is_null($this->rejected_at) && is_null($this->expired_at) && is_null($this->completed_at) && isset($this->payment->verified_at) && $this->verified_at;
    }

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }
}
