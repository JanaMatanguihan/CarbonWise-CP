<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Alert;

class AlertController extends Controller
{
    public function index()
    {
        $totalAlerts = Alert::count();

        $unreadAlerts = Alert::where('is_read', false)->count();

        $criticalAlerts = Alert::where('severity', 'critical')->count();

        $readAlerts = Alert::where('is_read', true)->count();

        $recentAlerts = Alert::latest()
            ->take(5)
            ->get();

        $alerts = Alert::latest()
            ->paginate(10);

        return view(
            'admin.alerts-notifications',
            compact(
                'totalAlerts',
                'unreadAlerts',
                'criticalAlerts',
                'readAlerts',
                'recentAlerts',
                'alerts'
            )
        );
    }
}