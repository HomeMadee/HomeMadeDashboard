<?php

namespace App\Repositories;

use App\Models\OrderDate;
use InfyOm\Generator\Common\BaseRepository;

/**
 * Class OrderDateRepository
 * @package App\Repositories
 * @version August 29, 2019, 9:38 pm UTC
 *
 * @method OrderDate findWithoutFail($id, $columns = ['*'])
 * @method OrderDate find($id, $columns = ['*'])
 * @method OrderDate first($columns = ['*'])
 */
class OrderDateRepository extends BaseRepository
{
    /**
     * @var array
     */
    protected $fieldSearchable = [
        'order_date',
        'order_id'
    ];

    /**
     * Configure the Model
     **/
    public function model()
    {
        return OrderDate::class;
    }
}
