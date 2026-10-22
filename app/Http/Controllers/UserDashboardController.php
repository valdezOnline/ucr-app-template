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
                $category = $shortcut['category'] ?? null;
                $shortcut['category'] = $this->normalizeCategory($category);
                $shortcut['category_key'] = filled($shortcut['category']) ? mb_strtolower($shortcut['category']) : null;

                return $shortcut;
            })
            ->values();

        $shortcutCategories = $shortcuts
            ->pluck('category')
            ->filter(fn($category) => filled($category))
            ->map(fn($category) => trim((string) $category))
            ->unique(fn($category) => mb_strtolower($category))
            ->sort(fn($left, $right) => mb_strtolower($left) <=> mb_strtolower($right))
            ->values();

        $shortcutsByCategory = $shortcuts
            ->sortBy([
                fn(array $shortcut) => empty($shortcut['category_key']) ? 'zzzzzzzz' : $shortcut['category_key'],
                fn(array $shortcut) => mb_strtolower($shortcut['name']),
            ])
            ->groupBy(function (array $shortcut) {
                return $shortcut['category_key'] ?? 'uncategorized';
            })
            ->mapWithKeys(function ($items, $key) {
                if ($key === 'uncategorized') {
                    return ['Uncategorized' => $items];
                }

                $firstCategory = trim((string) ($items->first()['category'] ?? ''));
                $displayCategory = $firstCategory !== '' ? $firstCategory : 'Uncategorized';

                return [$displayCategory => $items];
            });

        return view('user-dashboard', compact('userApplications', 'shortcutsByCategory', 'shortcutCategories'));
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

        $normalizedShortcut = $this->normalizeShortcutPayload($validated);
        $user = $request->user();
        $shortcuts = collect($user->dashboard_shortcuts ?? []);
        $shortcuts->push([
            'id' => (string) Str::uuid(),
            'name' => $normalizedShortcut['name'],
            'type' => $normalizedShortcut['type'],
            'url' => $normalizedShortcut['url'],
            'category' => $normalizedShortcut['category'],
        ]);

        $user->dashboard_shortcuts = $shortcuts->values()->all();
        $user->save();

        return back()->with('success', 'Shortcut added to My Shortcuts.');
    }

    /**
     * Update a dashboard shortcut for the authenticated user.
     */
    public function updateShortcut(Request $request, string $shortcutId): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', Rule::in(['internal', 'external'])],
            'url' => ['required', 'string', 'max:2048'],
            'category' => ['nullable', 'string', 'max:80'],
        ]);

        $normalizedShortcut = $this->normalizeShortcutPayload($validated);
        $user = $request->user();
        $shortcuts = collect($user->dashboard_shortcuts ?? []);

        $shortcuts = $shortcuts->map(function (array $shortcut) use ($shortcutId, $normalizedShortcut) {
            if (($shortcut['id'] ?? null) !== $shortcutId) {
                return $shortcut;
            }

            $shortcut['name'] = $normalizedShortcut['name'];
            $shortcut['type'] = $normalizedShortcut['type'];
            $shortcut['url'] = $normalizedShortcut['url'];
            $shortcut['category'] = $normalizedShortcut['category'];

            return $shortcut;
        });

        $user->dashboard_shortcuts = $shortcuts->values()->all();
        $user->save();

        return back()->with('success', 'Shortcut updated.');
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
     * Normalize and validate shortcut payload before saving.
     *
     * @param  array{name: string, type: string, url: string, category?: string|null}  $payload
     * @return array{name: string, type: string, url: string, category: string|null}
     */
    private function normalizeShortcutPayload(array $payload): array
    {
        $normalizedUrl = trim((string) $payload['url']);
        $shortcutType = $payload['type'];

        if ($shortcutType === 'internal') {
            if (!Str::startsWith($normalizedUrl, '/')) {
                $normalizedUrl = '/' . ltrim($normalizedUrl, '/');
            }

            if (filter_var($normalizedUrl, FILTER_VALIDATE_URL)) {
                throw new \InvalidArgumentException('Internal shortcuts must use a path such as /dashboard or /profile.');
            }
        }

        if ($shortcutType === 'external' && !filter_var($normalizedUrl, FILTER_VALIDATE_URL)) {
            throw new \InvalidArgumentException('External shortcuts must be valid URLs including http:// or https://.');
        }

        return [
            'name' => trim((string) $payload['name']),
            'type' => $shortcutType,
            'url' => $normalizedUrl,
            'category' => $this->normalizeCategory($payload['category'] ?? null),
        ];
    }

    /**
     * Normalize category labels for consistent display and grouping.
     */
    private function normalizeCategory(?string $category): ?string
    {
        $trimmedCategory = trim((string) ($category ?? ''));

        if ($trimmedCategory === '') {
            return null;
        }

        return ucwords(strtolower($trimmedCategory));
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