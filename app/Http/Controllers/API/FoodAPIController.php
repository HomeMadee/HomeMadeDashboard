<?php

/**
 * File name: FoodAPIController.php
 * Last modified: 2020.05.04 at 09:04:19
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2020
 *
 */

namespace App\Http\Controllers\API;


use App\Criteria\Foods\NearCriteria;
use App\Criteria\Foods\FoodsOfCategoriesCriteria;
use App\Criteria\Foods\FoodsOfCuisinesCriteria;
use App\Criteria\Foods\TrendingWeekCriteria;
use App\Http\Controllers\Controller;
use App\Models\Extra;
use App\Models\Food;
use App\Repositories\CustomFieldRepository;
use App\Repositories\ExtraRepository;
use App\Repositories\FoodRepository;
use App\Repositories\UploadRepository;
use Flash;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use InfyOm\Generator\Criteria\LimitOffsetCriteria;
use Prettus\Repository\Criteria\RequestCriteria;
use Prettus\Repository\Exceptions\RepositoryException;
use Prettus\Validator\Exceptions\ValidatorException;

/**
 * Class FoodController
 * @package App\Http\Controllers\API
 */
class FoodAPIController extends Controller
{
    /** @var  FoodRepository */
    private $foodRepository;
    /**
     * @var CustomFieldRepository
     */
    private $customFieldRepository;
    /**
     * @var UploadRepository
     */
    private $uploadRepository;

    /**
     * @var ExtraRepository
     */
    private $extraRepository;


    public function __construct(FoodRepository $foodRepo, CustomFieldRepository $customFieldRepo, UploadRepository $uploadRepo, ExtraRepository $extraRepo)
    {
        parent::__construct();
        $this->foodRepository = $foodRepo;
        $this->customFieldRepository = $customFieldRepo;
        $this->uploadRepository = $uploadRepo;
        $this->extraRepository = $extraRepo;
    }

    /**
     * Display a listing of the Food.
     * GET|HEAD /foods
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $this->foodRepository->pushCriteria(new RequestCriteria($request));
            $this->foodRepository->pushCriteria(new LimitOffsetCriteria($request));
            $this->foodRepository->pushCriteria(new FoodsOfCuisinesCriteria($request));
            if ($request->get('trending', null) == 'week') {
                $this->foodRepository->pushCriteria(new TrendingWeekCriteria($request));
            } else {
                $this->foodRepository->pushCriteria(new NearCriteria($request));
            }

            //            $this->foodRepository->orderBy('closed');
            //            $this->foodRepository->orderBy('area');
            $foods = $this->foodRepository->all();
        } catch (RepositoryException $e) {
            return $this->sendError($e->getMessage());
        }

        return $this->sendResponse($foods->toArray(), 'Foods retrieved successfully');
    }

    /**
     * Display a listing of the Food.
     * GET|HEAD /foods/categories
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function categories(Request $request)
    {
        try {
            $this->foodRepository->pushCriteria(new RequestCriteria($request));
            $this->foodRepository->pushCriteria(new LimitOffsetCriteria($request));
            $this->foodRepository->pushCriteria(new FoodsOfCuisinesCriteria($request));
            $this->foodRepository->pushCriteria(new FoodsOfCategoriesCriteria($request));

            $foods = $this->foodRepository->all();
        } catch (RepositoryException $e) {
            return $this->sendError($e->getMessage());
        }

        return $this->sendResponse($foods->toArray(), 'Foods retrieved successfully');
    }

    /**
     * Display the specified Food.
     * GET|HEAD /foods/{id}
     *
     * @param  int $id
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        /** @var Food $food */
        if (!empty($this->foodRepository)) {

            $food = $this->foodRepository->findWithoutFail($id);
        }

        if (empty($food)) {
            return $this->sendError('Food not found show');
        }

        $extras = Extra::where('food_id', ($id))->with('extraGroup')->get();
        $food->food_extras = $extras;

        return $this->sendResponse($food->toArray(), 'Food retrieved successfully');
    }

    /**
     * Store a newly created Food in storage.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $input = $request->all();
        $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->foodRepository->model());
        try {
            // if ($input['producible'] === '0') {
            //     $input['remaining'] = $input['daily_orders'];
            // } else {
            //     // $hrsPerDay = $input['working_hours'] / count($input['working_days']);
            //     $dailyProducts =  $input['working_hours'] / $input['prepare_time'];
            //     $input['remaining'] = $dailyProducts;
            // }
            $food = $this->foodRepository->create($input);
            $food->customFieldsValues()->createMany(getCustomFieldsValues($customFields, $request));
            if (isset($input['image']) && $input['image']) {
                $cacheUpload = $this->uploadRepository->getByUuid($input['image']);
                $mediaItem = $cacheUpload->getMedia('image')->first();
                $mediaItem->copy($food, 'image');
            }
        } catch (ValidatorException $e) {
            return $this->sendError($e->getMessage());
        }

        $extras = $input['extrasData'];
        if (!empty($extras)) {
            foreach ($extras as $extra) {
                $extra['food_id'] = $food->id;
                $newExtra = $this->extraRepository->create($extra);
                if (isset($extra['image']) && $extra['image']) {
                    $cacheUpload = $this->uploadRepository->getByUuid($extra['image']);
                    $mediaItem = $cacheUpload->getMedia('image')->first();
                    $mediaItem->copy($newExtra, 'image');
                }
            }
        }

        return $this->sendResponse($food->toArray(), __('lang.saved_successfully', ['operator' => __('lang.food')]));
    }

    /**
     * Update the specified Food in storage.
     *
     * @param int $id
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function update($id, Request $request)
    {
        $food = $this->foodRepository->findWithoutFail($id);

        if (empty($food)) {
            return $this->sendError('Food not found to update');
        }
        $input = $request->all();
        $customFields = $this->customFieldRepository->findByField('custom_field_model', $this->foodRepository->model());
        try {
            $food = $this->foodRepository->update($input, $id);

            if (isset($input['image']) && $input['image']) {
                $cacheUpload = $this->uploadRepository->getByUuid($input['image']);
                $mediaItem = $cacheUpload->getMedia('image')->first();
                $mediaItem->copy($food, 'image');
            }
            foreach (getCustomFieldsValues($customFields, $request) as $value) {
                $food->customFieldsValues()
                    ->updateOrCreate(['custom_field_id' => $value['custom_field_id']], $value);
            }
        } catch (ValidatorException $e) {
            return $this->sendError($e->getMessage());
        }

        return $this->sendResponse($food->toArray(), __('lang.updated_successfully', ['operator' => __('lang.food')]));
    }

    /**
     * Remove the specified Food from storage.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $food = $this->foodRepository->findWithoutFail($id);

        if (empty($food)) {
            return $this->sendError('Food not found to destroy');
        }

        $food = $this->foodRepository->delete($id);

        return $this->sendResponse($food, __('lang.deleted_successfully', ['operator' => __('lang.food')]));
    }

    public function activate($id, Request $request)
    {
        $input = $request->all();
        try {
            $food = $this->foodRepository->findWithoutFail($id);
            if (empty($food)) {
                return $this->sendError('Food not found to destroy');
            }
            $active = $input['active'];

            $food = $this->foodRepository->update(['active' => $active], $id);
            return $this->sendResponse($food, __('lang.deleted_successfully', ['operator' => __('lang.food')]));
        } catch (\Throwable $th) {
        }
    }
}
