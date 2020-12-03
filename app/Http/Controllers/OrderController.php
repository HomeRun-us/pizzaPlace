<?php

namespace App\Http\Controllers;

use App\Models\Pizza;
use App\Models\Drink;
use App\Models\User;
use App\Models\Order;
use App\Models\OrderPizzaPrice;
use App\Models\DrinkPriceOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $user   = Auth::user();
        $search = $request->get('search');
        
        $orders = (isset($search)) 
                ? Order::join('users as u1', 'u1.id', '=', 'orders.customer_user_id')
                    ->join('users as u2', 'u2.id', '=', 'orders.seller_user_id')
                    ->when($user->hasRole('customer'), function($query) use ($user) {
                        return $query->where('orders.customer_user_id', $user->id);
                    })
                    ->where('u1.username', 'like', "%$search%")
                    ->orWhere('u2.username', 'like', "%$search%")
                    ->orWhere('orders.address', 'like', "%$search%")
                    ->orWhere('orders.time', 'like', "%$search%")
                    ->get()
                : Order::when($user->hasRole('customer'), function($query) use ($user) {
                            return $query->where('orders.customer_user_id', $user->id);
                        })
                        ->get();

        $order_pizzas = OrderPizzaPrice::all();
        $order_drinks = DrinkPriceOrder::all();

        return view(($user->hasRole('cashier')) 
            ? "order.index" 
            : "order.customer", compact('orders', 'order_pizzas', 'order_drinks'));

        return redirect()->route('orders.create');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $pizzas = Pizza::orderBy('name', 'desc')->get();
        $drinks = Drink::all();

        return view('order.create', compact('pizzas','drinks'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $input = $request->except(['pizza_quantity', 'drink_quantity']);
        $seller = User::role('cashier')
                       ->select([DB::raw("COUNT('orders.seller_user_id') as times"), DB::raw('users.id')])            
                       ->leftJoin('orders', 'orders.seller_user_id', 'users.id')
                       ->groupBy('users.id')
                       ->orderBy('times', 'asc')
                       ->first();
        
        $input['customer_user_id'] = Auth::id();
        $input['seller_user_id']   = $seller->id;
        $order = Order::create($input);
        $input['order_id'] = $order->id;

        $order_pizza_price = ($request->get('pizza_price_id') > '0') 
                           ? OrderPizzaPrice::create(array_merge($input, $request->only('pizza_quantity'))) 
                           : '';
        $order_drink_price = ($request->get('drink_price_id') > '0')
                           ? DrinkPriceOrder::create(array_merge($input, $request->only('drink_quantity')))
                           : '';

        Session::flash('success', 'Your order has been placed');
        return redirect()->route('orders.create');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $order = Order::find($id);

        if (!empty($order)) {
            $sent = $order->sent;
            $order->update(['sent' => (int)!$sent]);
        }
            
        return redirect()->route('orders.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $order = Order::find($id);

        if (!empty($order))
            Order::destroy($id);
        
        return redirect()->route('orders.index');
    }
}
