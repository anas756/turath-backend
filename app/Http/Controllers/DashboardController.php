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
        $now              = Carbon::now();
        $startOfThisMonth = $now->copy()->startOfMonth();
        $startOfLastMonth = $now->copy()->subMonth()->startOfMonth();
        $endOfLastMonth   = $now->copy()->subMonth()->endOfMonth();

        $stats = [
            'users'     => $this->getStats(User::class, $startOfThisMonth, $startOfLastMonth, $endOfLastMonth),
            'documents' => $this->getStats(Document::class, $startOfThisMonth, $startOfLastMonth, $endOfLastMonth),
            'media'     => $this->getStats(Media::class, $startOfThisMonth, $startOfLastMonth, $endOfLastMonth),
            'recent'    => $this->getRecent(),
        ];

        return response()->json($stats);
    }

    private function getStats(string $model, $startOfThisMonth, $startOfLastMonth, $endOfLastMonth): array
    {
        $total     = $model::count();
        $thisMonth = $model::where('created_at', '>=', $startOfThisMonth)->count();
        $lastMonth = $model::whereBetween('created_at', [$startOfLastMonth, $endOfLastMonth])->count();

        $percentage = 0;
        if ($lastMonth > 0) {
            $percentage = round((($thisMonth - $lastMonth) / $lastMonth) * 100, 2);
        } elseif ($thisMonth > 0) {
            $percentage = 100;
        }

        return [
            'total'      => $total,
            'this_month' => $thisMonth,
            'last_month' => $lastMonth,
            'percentage' => $percentage,
            'trend'      => $percentage >= 0 ? 'up' : 'down',
        ];
    }

    private function getRecent(): array
    {
        $users = User::latest()->limit(5)->get()->map(fn($u) => [
            'type'       => 'user',
            'title'      => $u->name,
            'subtitle'   => $u->email,
            'role'       => $u->role,
            'created_at' => $u->created_at->toDateTimeString(),
        ]);

        $documents = Document::with('categorie')->latest()->limit(5)->get()->map(fn($d) => [
            'type'       => 'document',
            'title'      => $d->title,
            'subtitle'   => $d->categorie->name ?? null,
            'authors'    => $d->authors,
            'cover'      => $d->cover,
            'source'     => $d->source,
            'created_at' => $d->created_at->toDateTimeString(),
        ]);

        $media = Media::latest()->limit(5)->get()->map(fn($m) => [
            'type'       => 'media',
            'title'      => $m->title,
            'subtitle'   => $m->type,
            'format'     => $m->format,
            'size'       => $m->size,
            'status'     => $m->status,
            'curator'    => $m->curator,
            'created_at' => $m->created_at->toDateTimeString(),
        ]);

        return collect()
            ->merge($users)
            ->merge($documents)
            ->merge($media)
            ->sortByDesc('created_at')
            ->take(5)
            ->values()
            ->toArray();
    }
}
