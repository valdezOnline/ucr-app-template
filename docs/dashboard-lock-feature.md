# Dashboard Lock Feature

## Overview

The dashboard lock feature prevents users from accidentally dragging and reordering their applications when the dashboard is locked. When locked, users cannot modify the application order, though they can still view the dashboard and access their shortcuts.

## Features

- **Lock/Unlock Toggle**: A button on the dashboard header allows users to toggle between locked and unlocked states
- **Persistent State**: The lock state is saved per user and persists across sessions
- **Visual Feedback**: 
  - Lock icon appears when dashboard is locked
  - Unlock icon appears when dashboard is unlocked
  - Tooltip text updates based on lock state
- **Drag Prevention**: When locked, drag-and-drop is disabled for application tiles
- **Save Button Hidden**: The "Save Order" button is hidden when dragging is prevented

## User Interface

### Lock Button Location
The lock/unlock button is located in the dashboard header next to the display mode buttons and groups button.

### Button States
- **Unlocked**: Shows an unlock icon (`fa-unlock`) and displays "Unlocked" text
- **Locked**: Shows a lock icon (`fa-lock`) and displays "Locked" text
- Clicking the button toggles between states and immediately saves the preference

### Visual Indicators
- Tooltips provide context for the button's current state
- The button is styled consistently with other dashboard controls
- When locked, a notification is displayed (optional toast/alert can be added)

## Implementation Details

### Database Schema
A new boolean column `dashboard_locked` is added to the `users` table:
- Default value: `false` (dashboard is unlocked by default)
- Stored per user, allowing different users to have different preferences

### Routes
- **POST** `/dashboard/lock` → `toggleDashboardLock()` - Toggles the lock state

### Controller Method
The `UserDashboardController::toggleDashboardLock()` method:
- Retrieves the authenticated user
- Toggles the `dashboard_locked` boolean
- Saves the user record
- Returns a JSON response with the new lock state

### Frontend Logic
JavaScript functionality:
1. Reads the current lock state from the user model when the page loads
2. Updates the button UI based on the lock state
3. Prevents drag events when locked
4. Prevents dragover events when locked
5. Sends AJAX request when the lock button is clicked
6. Updates the UI to reflect the new state

## Usage

### For Users
1. Click the lock/unlock button in the dashboard header
2. The button will update immediately with new icon and text
3. If locked, dragging applications is prevented
4. If unlocked, users can drag and reorder applications

### For Developers
To check if a dashboard is locked in code:
```php
$isLocked = auth()->user()->dashboard_locked;
```

To set the locked state:
```php
auth()->user()->update(['dashboard_locked' => true]);
```

## Security Considerations
- The lock state is tied to the authenticated user
- The toggle endpoint is protected by authentication middleware
- Only the authenticated user can modify their own lock state
- The lock state is enforced on both the frontend (UX) and backend (API)

## Future Enhancements
Potential improvements for future versions:
- Add a toast notification when lock state changes
- Add keyboard shortcut to quickly lock/unlock the dashboard
- Add optional PIN/password protection for locked state
- Add dashboard-wide settings modal with lock preference
