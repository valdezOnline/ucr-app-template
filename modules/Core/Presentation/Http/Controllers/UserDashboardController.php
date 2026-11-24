<?php

namespace Modules\Core\Presentation\Http\Controllers;

use Modules\Core\Models\Application;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Modules\Core\Models\User;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

        // Group applications if user has groups set up
        $groupedApplications = $this->groupApplicationsByUserGroups($userApplications, $user);

        $displayMode = $user->dashboard_display_mode ?? 'normal';
        $userGroups = $user->dashboard_groups ?? [];
        $groupDisplayMode = $user->dashboard_group_display_mode ?? 'accordion';
        $shortcutGroupDisplayMode = $user->dashboard_shortcut_group_display_mode ?? 'list';
        $dashboardFullWidth = (bool) ($user->dashboard_full_width ?? false);

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

        return view('user-dashboard', compact(
            'userApplications',
            'groupedApplications',
            'displayMode',
            'userGroups',
            'groupDisplayMode',
            'shortcutGroupDisplayMode',
            'dashboardFullWidth',
            'shortcutsByCategory',
            'shortcutCategories'
        ));
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
     * Export the authenticated user's dashboard personalization settings.
     */
    public function exportPersonalization(Request $request): StreamedResponse
    {
        $payload = $this->buildDashboardPersonalizationExportPayload($request->user());
        $encodedPayload = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if ($encodedPayload === false) {
            abort(500, 'Unable to export dashboard personalization.');
        }

        $filename = 'dashboard-personalization-' . now()->format('Ymd-His') . '.json';

        return response()->streamDownload(function () use ($encodedPayload): void {
            echo $encodedPayload;
        }, $filename, [
            'Content-Type' => 'application/json',
        ]);
    }

    /**
     * Import dashboard personalization settings for the authenticated user.
     */
    public function importPersonalization(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'personalization_file' => ['required', 'file', 'max:512', 'mimes:json,txt'],
        ]);

        $fileContent = file_get_contents($validated['personalization_file']->getRealPath());
        if ($fileContent === false) {
            return back()->withErrors([
                'personalization_file' => 'Unable to read the selected personalization file.',
            ]);
        }

        $decodedPayload = json_decode($fileContent, true);
        if (!is_array($decodedPayload)) {
            return back()->withErrors([
                'personalization_file' => 'The selected file is not valid JSON.',
            ]);
        }

        try {
            $importedPreferences = $this->parseDashboardPersonalizationImportPayload($decodedPayload, $request->user());
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors([
                'personalization_file' => $e->getMessage(),
            ]);
        }

        $user = $request->user();
        $user->dashboard_display_mode = $importedPreferences['display_mode'];
        $user->dashboard_full_width = $importedPreferences['full_width'];
        $user->dashboard_application_order = $importedPreferences['application_order'];
        $user->dashboard_groups = $importedPreferences['groups'];
        $user->dashboard_group_display_mode = $importedPreferences['group_display_mode'];
        $user->dashboard_shortcuts = $importedPreferences['shortcuts'];
        $user->dashboard_shortcut_group_display_mode = $importedPreferences['shortcut_group_display_mode'];
        $user->save();

        return back()->with('success', 'Dashboard personalization imported successfully.');
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
     * Toggle the dashboard lock state.
     */
    public function toggleDashboardLock(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->dashboard_locked = !$user->dashboard_locked;
        $user->save();

        return response()->json([
            'message' => $user->dashboard_locked ? 'Dashboard locked.' : 'Dashboard unlocked.',
            'locked' => $user->dashboard_locked,
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
            'shortcut_group_display_mode' => ['nullable', Rule::in(['list', 'nav-pills'])],
        ]);

        try {
            $normalizedShortcut = $this->normalizeShortcutPayload($validated);
        } catch (\InvalidArgumentException $e) {
            return back()
                ->withInput()
                ->withErrors(['url' => $e->getMessage()]);
        }

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
        $user->dashboard_shortcut_group_display_mode = $validated['shortcut_group_display_mode']
            ?? ($user->dashboard_shortcut_group_display_mode ?? 'list');
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
            'shortcut_group_display_mode' => ['nullable', Rule::in(['list', 'nav-pills'])],
        ]);

        try {
            $normalizedShortcut = $this->normalizeShortcutPayload($validated);
        } catch (\InvalidArgumentException $e) {
            return back()
                ->withInput()
                ->withErrors(['url' => $e->getMessage()]);
        }

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
        $user->dashboard_shortcut_group_display_mode = $validated['shortcut_group_display_mode']
            ?? ($user->dashboard_shortcut_group_display_mode ?? 'list');
        $user->save();

        return back()->with('success', 'Shortcut updated.');
    }

    /**
     * Remove a dashboard shortcut for the authenticated user.
     */
    public function destroyShortcut(Request $request, string $shortcutId): RedirectResponse
    {
        $user = $request->user();
        $shortcuts = collect($user->dashboard_shortcuts ?? [])
            ->reject(fn(array $shortcut) => ($shortcut['id'] ?? null) === $shortcutId);

        $user->dashboard_shortcuts = $shortcuts->values()->all();
        $user->save();

        return back()->with('success', 'Shortcut removed.');
    }

    /**
     * Update the user's dashboard display mode preference.
     */
    public function updateDisplayMode(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'display_mode' => ['required', Rule::in(['normal', 'small', 'text'])],
        ]);

        $user = $request->user();
        $user->dashboard_display_mode = $validated['display_mode'];
        $user->save();

        return response()->json([
            'message' => 'Display mode updated successfully.',
            'display_mode' => $validated['display_mode'],
        ]);
    }

    /**
     * Update the user's application layout width preference.
     */
    public function updateFullWidth(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'full_width' => ['required', 'boolean'],
        ]);

        $user = $request->user();
        $user->dashboard_full_width = $validated['full_width'];
        $user->save();

        return response()->json([
            'message' => 'Application width updated successfully.',
            'full_width' => (bool) $validated['full_width'],
        ]);
    }

    /**
     * Create or update application groups for the user.
     */
    public function saveApplicationGroups(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'groups' => ['present', 'array'],
            'groups.*.id' => ['required', 'string', 'distinct'],
            'groups.*.name' => ['required', 'string', 'max:100'],
            'groups.*.application_ids' => ['present', 'array'],
            'groups.*.application_ids.*' => ['integer'],
            'group_display_mode' => ['nullable', Rule::in(['tabs', 'accordion'])],
        ]);

        $user = $request->user();
        $visibleApplicationIds = Application::visibleToUser($user)
            ->active()
            ->pluck('id')
            ->all();

        // Validate all application IDs belong to the user
        $allGroupApplicationIds = collect($validated['groups'])
            ->pluck('application_ids')
            ->flatten()
            ->unique()
            ->all();

        $invalidIds = array_diff($allGroupApplicationIds, $visibleApplicationIds);
        if (!empty($invalidIds)) {
            return response()->json([
                'message' => 'One or more applications are not available for your account.',
            ], 422);
        }

        // Store groups configuration
        $user->dashboard_groups = $validated['groups'];
        $user->dashboard_group_display_mode = $validated['group_display_mode'] ?? ($user->dashboard_group_display_mode ?? 'accordion');
        $user->save();

        return response()->json([
            'message' => 'Application groups saved successfully.',
        ]);
    }

    /**
     * Delete an application group.
     */
    public function deleteApplicationGroup(Request $request, string $groupId): JsonResponse
    {
        $user = $request->user();
        $groups = collect($user->dashboard_groups ?? []);

        $groups = $groups->reject(fn(array $group) => ($group['id'] ?? null) === $groupId);

        $user->dashboard_groups = $groups->values()->all();
        $user->save();

        return response()->json([
            'message' => 'Group deleted successfully.',
        ]);
    }

    /**
     * Normalize and validate shortcut payload before saving.
     *
     * @param  array{name: string, type: string, url: string, category?: string|null}  $payload
     * @return array{name: string, type: string, url: string, category: string|null}
     */
    private function groupApplicationsByUserGroups(Collection $applications, User $user): array
    {
        $userGroups = $user->dashboard_groups ?? [];

        if (empty($userGroups)) {
            return ['ungrouped' => $applications];
        }

        $grouped = [];
        $groupedAppIds = [];

        foreach ($userGroups as $group) {
            $groupId = $group['id'] ?? null;
            $groupName = $group['name'] ?? 'Unnamed Group';
            $applicationIds = $group['application_ids'] ?? [];

            $groupedAppIds = array_merge($groupedAppIds, $applicationIds);
            $grouped[$groupId] = [
                'name' => $groupName,
                'applications' => $applications->filter(
                    fn($app) => in_array($app->id, $applicationIds)
                )->values(),
            ];
        }

        $ungroupedApps = $applications->filter(
            fn($app) => !in_array($app->id, $groupedAppIds)
        )->values();

        if ($ungroupedApps->isNotEmpty()) {
            $grouped['ungrouped'] = [
                'name' => 'Ungrouped',
                'applications' => $ungroupedApps,
            ];
        }

        return $grouped;
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

    /**
     * Build the dashboard personalization export payload.
     *
     * @return array{
     *     type: string,
     *     version: int,
     *     exported_at: string,
     *     preferences: array{
     *         display_mode: string,
     *         full_width: bool,
     *         application_order: array<int, int>,
     *         groups: array<int, array{id: string, name: string, application_ids: array<int, int>}>,
     *         group_display_mode: string,
     *         shortcuts: array<int, array{id: string, name: string, type: string, url: string, category: string|null}>,
     *         shortcut_group_display_mode: string
     *     }
     * }
     */
    private function buildDashboardPersonalizationExportPayload(User $user): array
    {
        $groups = collect($user->dashboard_groups ?? [])
            ->filter(fn($group) => is_array($group))
            ->map(function (array $group) {
                $applicationIds = collect($group['application_ids'] ?? [])
                    ->map(fn($applicationId) => (int) $applicationId)
                    ->filter(fn(int $applicationId) => $applicationId > 0)
                    ->unique()
                    ->values()
                    ->all();

                return [
                    'id' => (string) ($group['id'] ?? ''),
                    'name' => (string) ($group['name'] ?? ''),
                    'application_ids' => $applicationIds,
                ];
            })
            ->values()
            ->all();

        $shortcuts = collect($user->dashboard_shortcuts ?? [])
            ->filter(fn($shortcut) => is_array($shortcut))
            ->map(function (array $shortcut) {
                return [
                    'id' => (string) ($shortcut['id'] ?? ''),
                    'name' => (string) ($shortcut['name'] ?? ''),
                    'type' => (string) ($shortcut['type'] ?? ''),
                    'url' => (string) ($shortcut['url'] ?? ''),
                    'category' => $this->normalizeCategory(
                        isset($shortcut['category']) ? (string) $shortcut['category'] : null
                    ),
                ];
            })
            ->values()
            ->all();

        return [
            'type' => 'ucr-dashboard-personalization',
            'version' => 1,
            'exported_at' => now()->toIso8601String(),
            'preferences' => [
                'display_mode' => $user->dashboard_display_mode ?? 'normal',
                'full_width' => (bool) ($user->dashboard_full_width ?? false),
                'application_order' => collect($user->dashboard_application_order ?? [])
                    ->map(fn($applicationId) => (int) $applicationId)
                    ->filter(fn(int $applicationId) => $applicationId > 0)
                    ->unique()
                    ->values()
                    ->all(),
                'groups' => $groups,
                'group_display_mode' => $user->dashboard_group_display_mode ?? 'accordion',
                'shortcuts' => $shortcuts,
                'shortcut_group_display_mode' => $user->dashboard_shortcut_group_display_mode ?? 'list',
            ],
        ];
    }

    /**
     * Parse, normalize, and validate imported dashboard personalization payload.
     *
     * @param  array<string, mixed>  $payload
     * @return array{
     *     display_mode: string,
     *     full_width: bool,
     *     application_order: array<int, int>,
     *     groups: array<int, array{id: string, name: string, application_ids: array<int, int>}>,
     *     group_display_mode: string,
     *     shortcuts: array<int, array{id: string, name: string, type: string, url: string, category: string|null}>,
     *     shortcut_group_display_mode: string
     * }
     */
    private function parseDashboardPersonalizationImportPayload(array $payload, User $user): array
    {
        if (($payload['type'] ?? null) !== 'ucr-dashboard-personalization') {
            throw new \InvalidArgumentException('Unsupported dashboard personalization file format.');
        }

        if (($payload['version'] ?? null) !== 1) {
            throw new \InvalidArgumentException('Unsupported dashboard personalization file version.');
        }

        $preferences = $payload['preferences'] ?? null;
        if (!is_array($preferences)) {
            throw new \InvalidArgumentException('Dashboard personalization file is missing preferences.');
        }

        $displayMode = $preferences['display_mode'] ?? null;
        if (!in_array($displayMode, ['normal', 'small', 'text'], true)) {
            throw new \InvalidArgumentException('Display mode in personalization file is invalid.');
        }

        $groupDisplayMode = $preferences['group_display_mode'] ?? null;
        if (!in_array($groupDisplayMode, ['tabs', 'accordion'], true)) {
            throw new \InvalidArgumentException('Group display mode in personalization file is invalid.');
        }

        $shortcutGroupDisplayMode = $preferences['shortcut_group_display_mode'] ?? null;
        if (!in_array($shortcutGroupDisplayMode, ['list', 'nav-pills'], true)) {
            throw new \InvalidArgumentException('Shortcut display mode in personalization file is invalid.');
        }

        $fullWidth = $this->parseBooleanPreference($preferences['full_width'] ?? null, 'full_width');
        $visibleApplicationIds = Application::visibleToUser($user)
            ->active()
            ->pluck('id')
            ->all();

        $applicationOrderRaw = $preferences['application_order'] ?? null;
        if (!is_array($applicationOrderRaw)) {
            throw new \InvalidArgumentException('Application order in personalization file is invalid.');
        }

        $applicationOrder = $this->normalizeIntegerList($applicationOrderRaw, 'application_order');
        $invalidOrderIds = array_diff($applicationOrder, $visibleApplicationIds);
        if (!empty($invalidOrderIds)) {
            throw new \InvalidArgumentException('Application order includes applications that are not available for your account.');
        }
        $remainingIds = array_values(array_diff($visibleApplicationIds, $applicationOrder));
        $finalOrder = array_values(array_merge($applicationOrder, $remainingIds));

        $groupsRaw = $preferences['groups'] ?? null;
        if (!is_array($groupsRaw)) {
            throw new \InvalidArgumentException('Groups in personalization file are invalid.');
        }

        $groupIds = [];
        $groups = collect($groupsRaw)->map(function ($group, int $index) use ($visibleApplicationIds, &$groupIds) {
            if (!is_array($group)) {
                throw new \InvalidArgumentException('Groups in personalization file are invalid.');
            }

            $groupId = trim((string) ($group['id'] ?? ''));
            $groupName = trim((string) ($group['name'] ?? ''));
            if ($groupId === '' || mb_strlen($groupId) > 120) {
                throw new \InvalidArgumentException("Group #" . ($index + 1) . ' has an invalid id.');
            }
            if (in_array($groupId, $groupIds, true)) {
                throw new \InvalidArgumentException('Group ids in personalization file must be unique.');
            }

            $groupIds[] = $groupId;

            if ($groupName === '' || mb_strlen($groupName) > 100) {
                throw new \InvalidArgumentException("Group #" . ($index + 1) . ' has an invalid name.');
            }

            $groupApplicationIdsRaw = $group['application_ids'] ?? null;
            if (!is_array($groupApplicationIdsRaw)) {
                throw new \InvalidArgumentException("Group '{$groupName}' has an invalid application list.");
            }

            $groupApplicationIds = $this->normalizeIntegerList($groupApplicationIdsRaw, "groups.{$index}.application_ids");
            $invalidGroupIds = array_diff($groupApplicationIds, $visibleApplicationIds);
            if (!empty($invalidGroupIds)) {
                throw new \InvalidArgumentException("Group '{$groupName}' contains applications that are not available for your account.");
            }

            return [
                'id' => $groupId,
                'name' => $groupName,
                'application_ids' => $groupApplicationIds,
            ];
        })->values()->all();

        $shortcutsRaw = $preferences['shortcuts'] ?? null;
        if (!is_array($shortcutsRaw)) {
            throw new \InvalidArgumentException('Shortcuts in personalization file are invalid.');
        }

        $shortcutIds = [];
        $shortcuts = collect($shortcutsRaw)->map(function ($shortcut) use (&$shortcutIds) {
            if (!is_array($shortcut)) {
                throw new \InvalidArgumentException('Shortcuts in personalization file are invalid.');
            }

            $normalizedShortcut = $this->normalizeShortcutPayload([
                'name' => (string) ($shortcut['name'] ?? ''),
                'type' => (string) ($shortcut['type'] ?? ''),
                'url' => (string) ($shortcut['url'] ?? ''),
                'category' => isset($shortcut['category']) ? (string) $shortcut['category'] : null,
            ]);

            $shortcutId = trim((string) ($shortcut['id'] ?? ''));
            if ($shortcutId === '') {
                $shortcutId = (string) Str::uuid();
            }
            if (mb_strlen($shortcutId) > 120) {
                throw new \InvalidArgumentException('A shortcut id in personalization file is invalid.');
            }
            if (in_array($shortcutId, $shortcutIds, true)) {
                throw new \InvalidArgumentException('Shortcut ids in personalization file must be unique.');
            }
            $shortcutIds[] = $shortcutId;

            return [
                'id' => $shortcutId,
                'name' => $normalizedShortcut['name'],
                'type' => $normalizedShortcut['type'],
                'url' => $normalizedShortcut['url'],
                'category' => $normalizedShortcut['category'],
            ];
        })->values()->all();

        return [
            'display_mode' => $displayMode,
            'full_width' => $fullWidth,
            'application_order' => $finalOrder,
            'groups' => $groups,
            'group_display_mode' => $groupDisplayMode,
            'shortcuts' => $shortcuts,
            'shortcut_group_display_mode' => $shortcutGroupDisplayMode,
        ];
    }

    /**
     * @param  array<int, mixed>  $values
     * @return array<int, int>
     */
    private function normalizeIntegerList(array $values, string $fieldName): array
    {
        $normalizedValues = [];
        foreach ($values as $value) {
            $normalizedValue = filter_var($value, FILTER_VALIDATE_INT);
            if ($normalizedValue === false || $normalizedValue <= 0) {
                throw new \InvalidArgumentException("{$fieldName} contains an invalid id.");
            }

            $normalizedValues[] = (int) $normalizedValue;
        }

        return array_values(array_unique($normalizedValues));
    }

    /**
     * @param  mixed  $value
     */
    private function parseBooleanPreference(mixed $value, string $fieldName): bool
    {
        $booleanValue = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($booleanValue === null) {
            throw new \InvalidArgumentException("{$fieldName} must be a boolean value.");
        }

        return $booleanValue;
    }
}