<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Alert;

class AlertController extends Controller
{
    public function index()
    {
        /*
        |--------------------------------------------------------------------------
        | Summary Cards
        |--------------------------------------------------------------------------
        */

        $totalAlerts = Alert::count();

        $unreadAlerts = Alert::where('is_read', false)
            ->count();

        $criticalAlerts = Alert::where('severity', 'critical')
            ->count();

        $resolvedAlerts = Alert::where('is_read', true)
            ->count();


        /*
        |--------------------------------------------------------------------------
        | Recent Alerts
        |--------------------------------------------------------------------------
        */

        $search = request('search');
        $severity = request('severity');
        $status = request('status');

        $recentAlerts = Alert::query();

        if ($search) {

            $recentAlerts->where(function ($query) use ($search) {

                $query->where('title', 'like', "%{$search}%")
                      ->orWhere('message', 'like', "%{$search}%");

            });

        }

        if ($severity) {

            $recentAlerts->where('severity', $severity);

        }

        if ($status == 'read') {

            $recentAlerts->where('is_read', true);

        }

        if ($status == 'unread') {

            $recentAlerts->where('is_read', false);

        }

        $recentAlerts = $recentAlerts
            ->latest()
            ->paginate(10)
            ->withQueryString();


                return view(
                'admin.alerts-notifications',
                compact(
                    'totalAlerts',
                    'unreadAlerts',
                    'criticalAlerts',
                    'resolvedAlerts',
                    'recentAlerts',
                    'search',
                    'severity',
                    'status'
                )
            );
        }

        /**
         * Mark an alert as read.
         */
        public function markAsRead(Alert $alert)
        {
            $alert->update([
                'is_read' => true
            ]);

            return redirect()
                ->route('admin.alerts')
                ->with('success', 'Alert marked as read.');
        }

        /**
         * Delete an alert.
         */
        public function destroy(Alert $alert)
        {
            $alert->delete();

            return redirect()
                ->route('admin.alerts')
                ->with('success', 'Alert deleted successfully.');
        }
    }