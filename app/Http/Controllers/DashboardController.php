<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Document;
use App\Models\Media;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function stats()
    {
        $now        = Carbon::now();
        $startOfThisMonth = $now->copy()->startOfMonth();
        $startOfLastMonth = $now->copy()->subMonth()->startOfMonth();
        $endOfLastMonth   = $now->copy()->subMonth()->endOfMonth();

        $stats = [
            'users'     => $this->getStats(User::class, $startOfThisMonth, $startOfLastMonth, $endOfLastMonth),
            'documents' => $this->getStats(Document::class, $startOfThisMonth, $startOfLastMonth, $endOfLastMonth),
            'media'     => $this->getStats(Media::class, $startOfThisMonth, $startOfLastMonth, $endOfLastMonth),
        ];

        return response()->json($stats);
    }

    private function getStats(string $model, $startOfThisMonth, $startOfLastMonth, $endOfLastMonth): array
    {
        $total         = $model::count();
        $thisMonth     = $model::where('created_at', '>=', $startOfThisMonth)->count();
        $lastMonth     = $model::whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])->count();

        $percentage = 0;
        if ($lastMonth > 0) {
            $percentage = round((($thisMonth - $lastMonth) / $lastMonth) * 100, 2);
        } elseif ($thisMonth > 0) {
            $percentage = 100;
        }

        return [
            'total'       => $total,
            'this_month'  => $thisMonth,
            'last_month'  => $lastMonth,
            'percentage'  => $percentage,        // e.g. +25.5 or -10.0
            'trend'       => $percentage >= 0 ? 'up' : 'down',
        ];
    }
}
