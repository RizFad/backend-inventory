<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Inventory extends Model
{
    protected $fillable = [
        'inventory_code',
        'member_id',
        'name',
        'type',
        'serial_number',
        'specification',
        'status',
        'department',
    ];

    protected $keyType = 'string';
    public $incrementing = false;

    protected static function booted()
    {
        static::creating(function ($model) {
            if (!$model->id) {
                $model->id = (string) Str::uuid();
            }
            if (!$model->inventory_code) {
                $last = Inventory::orderBy('created_at', 'desc')->first();

                $number = $last
                    ? intval(substr($last->inventory_code, 4)) + 1
                    : 1;

                $model->inventory_code = 'INV-' . str_pad($number, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function member()
    {
        return $this->belongsTo(Member::class);
    }
}
