<?php

namespace App\Http\Controllers;

use App\Models\Business;
use App\Models\Publication;
use App\Models\Request as CustomerRequest;
use App\Models\User;

class CustomerController extends Controller
{
    public function dashboard()
    {
        $user = auth()->user();

        $requests = CustomerRequest::with('business')
            ->where('customer_id', $user->id)
            ->latest()
            ->take(5)
            ->get();

        $stats = [
            'requests' => CustomerRequest::where('customer_id', $user->id)->count(),
            'pending' => CustomerRequest::where('customer_id', $user->id)->whereIn('status', ['enviada', 'vista', 'aceptada'])->count(),
            'completed' => CustomerRequest::where('customer_id', $user->id)->where('status', 'completada')->count(),
        ];

        return view('customer.dashboard', compact('user', 'requests', 'stats'));
    }
}