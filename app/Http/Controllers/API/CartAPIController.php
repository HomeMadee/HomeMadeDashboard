<?php

namespace App\Http\Controllers\API;


use App\Http\Requests\CreateCartRequest;
use App\Http\Requests\CreateFavoriteRequest;
use App\Models\Cart;
use App\Repositories\CartRepository;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Repositories\FoodRepository;
use App\Repositories\OrderDateRepository;
use App\Repositories\OrderRepository;
use Illuminate\Support\Facades\Log;
use InfyOm\Generator\Criteria\LimitOffsetCriteria;
use Prettus\Repository\Criteria\RequestCriteria;
use Illuminate\Support\Facades\Response;
use Prettus\Repository\Exceptions\RepositoryException;
use Flash;
use Prettus\Validator\Exceptions\ValidatorException;

/**
 * Class CartController
 * @package App\Http\Controllers\API
 */

class CartAPIController extends Controller
{
    /** @var  CartRepository */
    private $cartRepository;

    /** @var  FoodRepository */
    private $foodRepository;

    /** @var  OrderDateRepository */
    private $orderDateRepository;


    /** @var  OrderRepository */
    private $orderRepository;

    public function __construct(
        CartRepository $cartRepo,
        FoodRepository $foodRepo,
        OrderDateRepository $orderDateRepo,
        OrderRepository $orderRepo
    ) {
        $this->cartRepository = $cartRepo;
        $this->foodRepository = $foodRepo;
        $this->orderDateRepository = $orderDateRepo;
        $this->orderRepository = $orderRepo;
    }

    /**
     * Display a listing of the Cart.
     * GET|HEAD /carts
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $this->cartRepository->pushCriteria(new RequestCriteria($request));
            $this->cartRepository->pushCriteria(new LimitOffsetCriteria($request));
        } catch (RepositoryException $e) {
            return $this->sendError($e->getMessage());
        }
        $carts = $this->cartRepository->all();

        return $this->sendResponse($carts->toArray(), 'Carts retrieved successfully');
    }

    /**
     * Display a listing of the Cart.
     * GET|HEAD /carts
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function count(Request $request)
    {
        try {
            $this->cartRepository->pushCriteria(new RequestCriteria($request));
            $this->cartRepository->pushCriteria(new LimitOffsetCriteria($request));
        } catch (RepositoryException $e) {
            return $this->sendError($e->getMessage());
        }
        $count = $this->cartRepository->count();

        return $this->sendResponse($count, 'Count retrieved successfully');
    }

    /**
     * Display the specified Cart.
     * GET|HEAD /carts/{id}
     *
     * @param  int $id
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        /** @var Cart $cart */
        if (!empty($this->cartRepository)) {
            $cart = $this->cartRepository->findWithoutFail($id);
        }

        if (empty($cart)) {
            return $this->sendError('Cart not found');
        }

        return $this->sendResponse($cart->toArray(), 'Cart retrieved successfully');
    }
    /**
     * Store a newly created Cart in storage.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $input = $request->all();
        // dd($input);
        try {
            if (isset($input['reset']) && $input['reset'] == '1') {
                // delete all items in the cart of current user
                $this->cartRepository->deleteWhere(['user_id' => $input['user_id']]);
            }
            $cart = $this->cartRepository->create($input);
        } catch (ValidatorException $e) {
            return $this->sendError($e->getMessage());
        }

        return $this->sendResponse($cart->toArray(), __('lang.saved_successfully', ['operator' => __('lang.cart')]));
    }

    /**
     * Update the specified Cart in storage.
     *
     * @param int $id
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function update($id, Request $request)
    {
        $cart = $this->cartRepository->findWithoutFail($id);

        if (empty($cart)) {
            return $this->sendError('Cart not found');
        }
        $input = $request->all();

        try {
            //            $input['extras'] = isset($input['extras']) ? $input['extras'] : [];
            $cart = $this->cartRepository->update($input, $id);
        } catch (ValidatorException $e) {
            return $this->sendError($e->getMessage());
        }

        return $this->sendResponse($cart->toArray(), __('lang.saved_successfully', ['operator' => __('lang.cart')]));
    }

    /**
     * Remove the specified Favorite from storage.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $cart = $this->cartRepository->findWithoutFail($id);

        if (empty($cart)) {
            return $this->sendError('Cart not found');
        }

        $cart = $this->cartRepository->delete($id);

        return $this->sendResponse($cart, __('lang.deleted_successfully', ['operator' => __('lang.cart')]));
    }

    public function checkFood($restaurantId, Request $request)
    {
        $input = $request->all();
        $this->orderDateRepository->pushCriteria(new RequestCriteria($request));
        $orderDate = $this->orderDateRepository->findWhere(
            [
                'restaurant_id' => $restaurantId,
                'order_date' => $input['order_date']
            ]
        );
        $orders = $orderDate->toArray();
        if (empty($orders)) {
            return $this->sendResponse(true, 'This date the restaurant will be free');
        }

        $remaining = 0;

        $avaialbe = TRUE;

        foreach ($orders as $order) {
            $foodOrders = $order['order']['food_orders'];
            $restaurant = $order['restaurant'];
            $workingHours = intval($restaurant['custom_fields']['working_hours']['value']);
            // dd(($foodOrders));
            foreach ($foodOrders as $food) {
                if ($food['food']['custom_fields']['producible'] === '0') {
                    $remaining = intval($food['food']['custom_fields']['daily_orders']);
                } else {
                    $dailyProducts =  $workingHours / intval($food['food']['custom_fields']['prepare_time']['value']);
                    $remaining = $dailyProducts;
                    // dd($food['food']['custom_fields']['prepare_time']['value']);
                }
                // $food['quantity']

                /*  $prepareTime = intval($food['food']['custom_fields']['prepare_time']);
                if(intval($food['food']['custom_fields']['producible'])) {

                }
                $qty = $food['quantity'];
                dd($food); */
            }
        }
    }
}
