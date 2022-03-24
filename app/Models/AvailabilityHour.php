<?php
/*
 * File name: AvailabilityHour.php
 * Last modified: 2021.04.12 at 09:20:07
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2021
 */

namespace App\Models;

use Eloquent as Model;

/**
 * Class AvailabilityHour
 * @package App\Models
 * @version January 16, 2021, 4:08 pm UTC
 *
 * @property EProvider eProvider
 * @property string id
 * @property string day
 * @property string start_at
 * @property string end_at
 * @property string data
 * @property integer restaurant_id
 */
class AvailabilityHour extends Model
{


    /**
     * Validation rules
     *
     * @var array
     */
    public static $rules = [
        'day' => 'required|max:16',
        'start_at' => 'required|date_format:H\:i',
        'end_at' => 'required|date_format:H\:i|after:start_at',
        'data' => 'max:255',
        'restaurant_id' => 'required|exists:restaurants,id',
    ];
    public $translatable = [
        'data',
    ];
    public $timestamps = false;
    public $table = 'availability_hours';
    public $fillable = [
        'day',
        'start_at',
        'end_at',
        'data',
        'restaurant_id',
    ];
    /**
     * The attributes that should be casted to native types.
     *
     * @var array
     */
    protected $casts = [
        'day' => 'string',
        'start_at' => 'string',
        'end_at' => 'string',
        'data' => 'string',
        'restaurant_id' => 'integer',
    ];
    /**
     * New Attributes
     *
     * @var array
     */
    protected $appends = [
        'custom_fields',
		'restaurants',

    ];
    protected $hidden = [
        "created_at",
        "updated_at",
    ];

    /**
     * @return array
     */
    public function getCustomFieldsAttribute(): array
    {
        $hasCustomField = in_array(static::class, setting('custom_field_models', []));
        if (!$hasCustomField) {
            return [];
        }
        $array = $this->customFieldsValues()
            ->join('custom_fields', 'custom_fields.id', '=', 'custom_field_values.custom_field_id')
            ->where('custom_fields.in_table', '=', true)
            ->get()->toArray();

        return convertToAssoc($array, 'name');
    }

    /**
     * @return MorphMany
     */
    public function customFieldsValues(): MorphMany
    {
        return $this->morphMany('App\Models\CustomFieldValue', 'customizable');
    }

       /**
     * get restaurant attribute
     * @return \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Relations\BelongsTo|object|null
     */
    public function getRestaurantAttribute()
    {
        return $this->restaurant()->first(['id', 'name', 'delivery_fee', 'address', 'phone','default_tax','available_for_delivery']);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     **/
    public function restaurant()
    {
        return $this->belongsTo(\App\Models\Restaurant::class, 'restaurant_id', 'id');
    }

}
