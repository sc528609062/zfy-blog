<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Content;
use App\Models\Order;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $stats = [
            'users'      => User::count(),
            'contents'   => Content::count(),
            'published'  => Content::published()->count(),
            'pending'    => Content::where('status', Content::STATUS_PENDING)->count(),
            'comments'   => Comment::count(),
            'orders'     => Order::count(),
            'paid_orders'=> Order::where('status', 'paid')->count(),
        ];

        $recentContents = Content::query()
            ->with('author')
            ->latest()
            ->take(8)
            ->get();

        $recentUsers = User::query()->latest()->take(8)->get();

        return view('admin.dashboard', compact('stats', 'recentContents', 'recentUsers'));
    }
}
