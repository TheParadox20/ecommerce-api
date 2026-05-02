<?php

namespace App\Http\Controllers;

use App\Models\PageView;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class PageViewController extends Controller
{
    /**
     * Store a new page view event.
     */
    public function store(Request $request)
    {
        $userAgent = $request->header('User-Agent');
        $ip = $request->ip();
        
        // Basic Bot Filtering
        if ($this->isBot($userAgent)) {
            return response()->json(['message' => 'Bot ignored'], 202);
        }

        $deviceType = $this->detectDevice($userAgent);
        $browser = $this->detectBrowser($userAgent);

        // Fetch Location from IP (Free API)
        $location = ['country' => 'Unknown', 'countryCode' => '??', 'city' => 'Unknown'];
        try {
            // We use the full IP for lookup, but then we mask it before saving
            $response = Http::timeout(2)->get("http://ip-api.com/json/{$ip}?fields=status,country,countryCode,city");
            if ($response->successful() && $response->json('status') === 'success') {
                $location = $response->json();
            }
        } catch (\Exception $e) {
            // Silent fail
        }

        PageView::create([
            'path' => $request->input('path'),
            'referrer' => $request->input('referrer'),
            'session_id' => $request->input('session_id'),
            'user_agent' => $userAgent,
            'device_type' => $deviceType,
            'browser' => $browser,
            'user_id' => auth('sanctum')->id(),
            'country' => $location['country'],
            'country_code' => $location['countryCode'],
            'city' => $location['city'],
        ]);

        return response()->json(['success' => true], 201);
    }

    /**
     * Get analytics stats.
     */
    public function stats(Request $request)
    {
        $days = (int) $request->query('days', 7);
        $startDate = Carbon::now()->subDays($days);

        // 1. Total views & Unique visitors
        $summary = PageView::where('created_at', '>=', $startDate)
            ->select([
                DB::raw('COUNT(*) as total_views'),
                DB::raw('COUNT(DISTINCT session_id) as unique_visitors')
            ])->first();

        // 2. Daily Trends
        $dailyTrends = PageView::where('created_at', '>=', $startDate)
            ->select([
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as views'),
                DB::raw('COUNT(DISTINCT session_id) as visitors')
            ])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // 3. Top Pages
        $topPages = PageView::where('created_at', '>=', $startDate)
            ->select('path', DB::raw('COUNT(*) as views'), DB::raw('COUNT(DISTINCT session_id) as visitors'))
            ->groupBy('path')
            ->orderByDesc('views')
            ->limit(10)
            ->get();

        // 4. Device Breakdown
        $deviceBreakdown = PageView::where('created_at', '>=', $startDate)
            ->select('device_type', DB::raw('COUNT(*) as count'))
            ->groupBy('device_type')
            ->get();

        // 5. Browser Breakdown
        $browserBreakdown = PageView::where('created_at', '>=', $startDate)
            ->select('browser', DB::raw('COUNT(*) as count'))
            ->groupBy('browser')
            ->orderByDesc('count')
            ->get();

        // 6. Top Referrers
        $topReferrers = PageView::where('created_at', '>=', $startDate)
            ->whereNotNull('referrer')
            ->where('referrer', '!=', '')
            ->select('referrer', DB::raw('COUNT(*) as count'))
            ->groupBy('referrer')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        // 8. Top Countries
        $topCountries = PageView::where('created_at', '>=', $startDate)
            ->whereNotNull('country')
            ->select('country', DB::raw('COUNT(*) as count'))
            ->groupBy('country')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        // 9. Recent Activity (Last 50 hits)
        $recentActivity = PageView::orderByDesc('created_at')
            ->limit(50)
            ->get();

        return response()->json([
            'summary' => $summary,
            'daily_trends' => $dailyTrends,
            'top_pages' => $topPages,
            'device_breakdown' => $deviceBreakdown,
            'browser_breakdown' => $browserBreakdown,
            'top_referrers' => $topReferrers,
            'top_countries' => $topCountries,
            'recent_activity' => $recentActivity,
            'period_days' => $days
        ]);
    }

    private function isBot($ua)
    {
        if (empty($ua)) return false;
        $bots = ['bot', 'crawler', 'spider', 'slurp', 'googlebot', 'bingbot', 'yandexbot', 'baiduspider', 'facebookexternalhit', 'twitterbot', 'rogerbot', 'linkedinbot', 'embedly', 'quora link preview', 'showyoubot', 'outbrain', 'pinterest', 'slackbot', 'vkShare', 'W3C_Validator'];
        return (bool) preg_match('/' . implode('|', $bots) . '/i', $ua);
    }

    private function detectDevice($ua)
    {
        if (preg_match('/tablet|ipad|playbook|silk/i', $ua)) return 'tablet';
        if (preg_match('/mobile|phone|iphone|ipod|android|blackberry|opera mini|iemobile/i', $ua)) return 'mobile';
        return 'desktop';
    }

    private function detectBrowser($ua)
    {
        if (preg_match('/MSIE/i', $ua) && !preg_match('/Opera/i', $ua)) return 'Internet Explorer';
        if (preg_match('/Firefox/i', $ua)) return 'Firefox';
        if (preg_match('/Chrome/i', $ua)) return 'Chrome';
        if (preg_match('/Safari/i', $ua)) return 'Safari';
        if (preg_match('/Opera/i', $ua)) return 'Opera';
        if (preg_match('/Netscape/i', $ua)) return 'Netscape';
        return 'Unknown';
    }
}
