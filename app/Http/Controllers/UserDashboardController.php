<?php

namespace App\Http\Controllers;

use App\Models\Application;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class UserDashboardController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the user dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $user = auth()->user();

        // Get applications user has access to through their roles
        $userApplications = Application::visibleToUser($user)
            ->active()
            ->orderBy('name')
            ->get();

        $userApplications = $this->applySavedApplicationOrder(
            $userApplications,
            $user->dashboard_application_order ?? []
        );

        $shortcuts = collect($user->dashboard_shortcuts ?? [])
            ->filter(fn(array $shortcut) => isset($shortcut['id'], $shortcut['name'], $shortcut['url'], $shortcut['type']))
            ->map(function (array $shortcut) {
                $shortcut['category'] = $shortcut['category'] ?? null;
                return $shortcut;
            })
            ->values();

        $shortcutsByCategory = $shortcuts
            ->sortBy([
                fn(array $shortcut) => empty($shortcut['category']) ? 'zzzzzzzz' : mb_strtolower($shortcut['category']),
                fn(array $shortcut) => mb_strtolower($shortcut['name']),
            ])
            ->groupBy(fn(array $shortcut) => empty($shortcut['category']) ? 'Uncategorized' : $shortcut['category']);

        return view('user-dashboard', compact('userApplications', 'shortcutsByCategory'));
    }

    /**
     * Show the user's profile (read-only view).
     */
    public function profile()
    {
        $user = auth()->user();

        // Load user with roles for detailed view
        $user->load('roles');

        return view('users.profile', compact('user'));
    }

    /**
     * Persist user-specific dashboard tile order.
     */
    public function saveApplicationOrder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'application_ids' => ['required', 'array'],
            'application_ids.*' => ['integer', 'distinct'],
        ]);

        $user = $request->user();
        $visibleApplicationIds = Application::visibleToUser($user)
            ->active()
            ->pluck('id')
            ->all();

        $submittedIds = $validated['application_ids'];
        $invalidIds = array_diff($submittedIds, $visibleApplicationIds);

        if (!empty($invalidIds)) {
            return response()->json([
                'message' => 'One or more applications are not available for your account.',
            ], 422);
        }

        $remainingIds = array_values(array_diff($visibleApplicationIds, $submittedIds));
        $finalOrder = array_values(array_merge($submittedIds, $remainingIds));

        $user->dashboard_application_order = $finalOrder;
        $user->save();

        return response()->json([
            'message' => 'Application order saved.',
        ]);
    }

    /**
     * Save a dashboard shortcut for the authenticated user.
     */
    public function storeShortcut(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', Rule::in(['internal', 'external'])],
            'url' => ['required', 'string', 'max:2048'],
            'category' => ['nullable', 'string', 'max:80'],
        ]);

        $normalizedUrl = trim($validated['url']);
        $shortcutType = $validated['type'];

        if ($shortcutType === 'internal') {
            if (!Str::startsWith($normalizedUrl, '/')) {
                $normalizedUrl = '/' . ltrim($normalizedUrl, '/');
            }

            if (filter_var($normalizedUrl, FILTER_VALIDATE_URL)) {
                return back()->withInput()->withErrors([
                    'url' => 'Internal shortcuts must use a path such as /dashboard or /profile.',
                ]);
            }
        }

        if ($shortcutType === 'external' && !filter_var($normalizedUrl, FILTER_VALIDATE_URL)) {
            return back()->withInput()->withErrors([
                'url' => 'External shortcuts must be valid URLs including http:// or https://.',
            ]);
        }

        $user = $request->user();
        $shortcuts = collect($user->dashboard_shortcuts ?? []);
        $shortcuts->push([
            'id' => (string) Str::uuid(),
            'name' => trim($validated['name']),
            'type' => $shortcutType,
            'url' => $normalizedUrl,
            'category' => filled($validated['category'] ?? null) ? trim($validated['category']) : null,
        ]);

        $user->dashboard_shortcuts = $shortcuts->values()->all();
        $user->save();

        return back()->with('success', 'Shortcut added to My Shortcuts.');
    }

    /**
     * Remove a dashboard shortcut for the authenticated user.
     */
    public function destroyShortcut(Request $request, string $shortcutId): RedirectResponse
    {
        $user = $request->user();
        $shortcuts = collect($user->dashboard_shortcuts ?? []);

        $shortcutExists = $shortcuts->contains(
            fn(array $shortcut) => ($shortcut['id'] ?? null) === $shortcutId
        );

        if (!$shortcutExists) {
            return back()->withErrors([
                'shortcut' => 'Shortcut not found.',
            ]);
        }

        $user->dashboard_shortcuts = $shortcuts
            ->reject(fn(array $shortcut) => ($shortcut['id'] ?? null) === $shortcutId)
            ->values()
            ->all();
        $user->save();

        return back()->with('success', 'Shortcut removed from My Shortcuts.');
    }

    /**
     * Apply a saved application order while retaining all visible apps.
     *
     * @param  array<int, int|string>  $savedOrder
     */
    private function applySavedApplicationOrder(Collection $applications, array $savedOrder): Collection
    {
        if (empty($savedOrder)) {
            return $applications;
        }

        $orderedById = $applications->keyBy('id');
        $savedOrderedApplications = collect($savedOrder)
            ->map(fn($applicationId) => (int) $applicationId)
            ->unique()
            ->map(fn(int $applicationId) => $orderedById->get($applicationId))
            ->filter();

        $remainingApplications = $applications->reject(function ($application) use ($savedOrderedApplications) {
            return $savedOrderedApplications->contains('id', $application->id);
        });

        return $savedOrderedApplications->concat($remainingApplications)->values();
    }
}