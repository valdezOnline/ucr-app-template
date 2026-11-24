# Dashboard Display and Grouping Features

## Overview

The dashboard now supports three display modes for application tiles and allows users to organize applications into custom groups.

## Features

### 1. Display Modes

Users can choose from three display modes for application tiles:

#### Normal Tile
- Shows application icon/logo, name, description, and launch button
- Default view that displays full application information
- Best for discovering new applications

#### Small Tile
- Displays application icon, name, and a launch button
- Takes up 20% of the width (5 tiles per row on large screens)
- Best for users who already know their applications

#### Text Only
- Shows only the application name
- Full width layout (one per row)
- Best for quick scanning and minimal visual clutter

### 2. Application Groups

Users can organize applications into custom groups:
- Create custom groups (e.g., "HR Tools", "Development", "Finance")
- Drag and drop applications into groups
- Each group displays with its name as a section header
- Ungrouped applications appear in an "Ungrouped" section

## Implementation Details

### Database Schema

Migration: `database/migrations/2026_11_19_000000_add_dashboard_display_mode_to_users_table.php`

Columns added to the `users` table:

```sql
ALTER TABLE users ADD COLUMN dashboard_display_mode ENUM('normal', 'small', 'text') DEFAULT 'normal';
ALTER TABLE users ADD COLUMN dashboard_groups JSON NULL;
ALTER TABLE users ADD COLUMN dashboard_application_groups JSON NULL;
```

Columns from the earlier migration `2026_10_21_202500_add_dashboard_preferences_to_users_table.php`:
`dashboard_application_order` and `dashboard_shortcuts`.

> **Known gap:** `dashboard_application_groups` is declared in the model and migration but is not read or written by any controller or view. Groups are stored in `dashboard_groups`. Remove the column in a future migration once confirmed unused.

### Shortcuts and Application Order

Shortcuts are stored in `dashboard_shortcuts` and managed through these routes (all `auth` + `auth.type`):

| Method | Route | Action |
|---|---|---|
| POST | `/dashboard/shortcuts` | `storeShortcut` |
| PUT | `/dashboard/shortcuts/{shortcutId}` | `updateShortcut` |
| DELETE | `/dashboard/shortcuts/{shortcutId}` | `destroyShortcut` |
| POST | `/dashboard/applications/order` | `saveApplicationOrder` (only visible applications accepted) |

Internal shortcuts must be paths (for example `/profile`). External shortcuts must be full URLs.

### Data Storage Format

#### Display Mode
Stored as a string: `'normal'`, `'small'`, or `'text'`

#### Groups Structure
```json
[
    {
        "id": "group-1234567890",
        "name": "HR Tools",
        "application_ids": [1, 2, 3]
    },
    {
        "id": "group-1234567891",
        "name": "Development",
        "application_ids": [4, 5]
    }
]
```

### API Routes

#### Update Display Mode
```
POST /dashboard/display-mode
Content-Type: application/json

{
    "display_mode": "normal|small|text"
}
```

Response:
```json
{
    "message": "Display mode updated successfully.",
    "display_mode": "normal"
}
```

#### Save Application Groups
```
POST /dashboard/applications/groups
Content-Type: application/json

{
    "groups": [
        {
            "id": "group-id",
            "name": "Group Name",
            "application_ids": [1, 2, 3]
        }
    ]
}
```

Response:
```json
{
    "message": "Application groups saved successfully."
}
```

#### Delete Application Group
```
DELETE /dashboard/applications/groups/{groupId}
```

Response:
```json
{
    "message": "Group deleted successfully."
}
```

## UI Components

### Display Mode Toggle
Located in the application card header, features radio buttons for:
- Normal (grid icon)
- Small (smaller grid icon)
- Text (list icon)

### Groups Management Modal
Accessible via the "Groups" button in the application card header (`#groupsModal`):
- Lists all current groups
- Group names are edited inline in each group card (`.group-name-input`)
- Each group card has a trash button to delete the group
- "Add New Group" button (`#add-group-btn`) creates a group named "New Group"
- "Save Groups" button (`#save-groups-btn`) persists changes

### Shortcuts Panel
The "My Shortcuts" card on the dashboard lists shortcuts grouped by category.
- "Add Shortcut" opens `#shortcutModal` to create a shortcut (name, link type, URL or path, optional category)
- Each shortcut has edit and remove actions. Remove submits a `DELETE` form to `/dashboard/shortcuts/{id}`
- Categories are suggested from existing shortcuts via a datalist

## User Flow

1. User navigates to dashboard
2. Sees applications in their current display mode and groups
3. Can switch display modes using the toggle buttons (changes persist immediately)
4. Can click "Groups" button to open group management modal
5. In the modal, can:
   - Create new groups by clicking "Add New Group"
   - Edit group names
   - Check/uncheck applications to assign them to groups
   - Delete groups by clicking the trash icon
6. Click "Save Groups" to persist all group changes
7. Dashboard reloads to show newly organized applications

## Default Behavior

- **Display Mode**: Defaults to "normal" if not set
- **Groups**: Empty by default; applications are "ungrouped" until organized
- **Drag and Drop**: Still works for reordering within groups or when ungrouped

## Migration Path

If users had applications but no groups defined:
- Display mode defaults to "normal"
- All applications appear in a single ungrouped section
- Users can optionally create groups at any time

## Responsive Behavior

### Desktop (> 1200px)
- Normal: 3 columns
- Small: 5 columns
- Text: 1 column

### Tablet (768px - 1200px)
- Normal: 2 columns
- Small: 3 columns
- Text: 1 column

### Mobile (< 768px)
- Normal: 2 columns
- Small: 2 columns
- Text: 1 column

## User Preferences Persistence

All settings are stored in the authenticated user's profile:
- Stored in JSON format in the `users` table
- Automatically loaded on dashboard view
- Updated via AJAX requests (no page reload required)

## Migration and Rollback

Apply with `php artisan migrate`. Roll back with `php artisan migrate:rollback`. The migration drops only the columns it adds (`dashboard_display_mode`, `dashboard_groups`, `dashboard_application_groups`). Existing user data in other columns is preserved.

## Tests

Covered by `tests/Feature/UserDashboardPersonalizationTest.php`:

- Application order saved for visible apps only
- Shortcut add, update, and remove
- Display mode update
- Group save, delete-all, delete-one, and inaccessible-app rejection
- Dashboard view receives display mode and groups

Run with: `docker compose exec apptemplate php artisan test --filter=UserDashboardPersonalizationTest`

## Historical Notes

- The display mode toggle initially updated only a CSS class, which did not change tile markup. The toggle now reloads the page after a successful save so the server re-renders the tiles with the new mode.
