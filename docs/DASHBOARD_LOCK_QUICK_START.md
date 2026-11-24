# Dashboard Lock Feature - Quick Start Guide

## For Users

### How to Lock Your Dashboard

1. Navigate to your Dashboard
2. Look for the **Lock/Unlock button** in the header (next to the display mode selector)
3. Click the button to toggle between locked and unlocked states:
   - **Unlocked** (![unlock icon](/resources/images/unlock.svg)): Applications can be dragged to reorder
   - **Locked** (![lock icon](/resources/images/lock.svg)): Applications cannot be dragged

### Why Lock Your Dashboard?

- **Prevent Accidental Changes**: Avoid accidentally dragging applications out of order
- **Keep Your Layout Stable**: Lock ensures your preferred layout stays consistent
- **Better for Touch Devices**: Prevents unintended touches from moving tiles

### Tips

- Your lock preference is **saved automatically** and persists across sessions
- When locked, you can still:
  - View all your applications
  - Click to open applications
  - Manage shortcuts
  - Change display modes
- When unlocked, you can:
  - Drag and drop to reorder applications
  - Save your custom order

---

## For Administrators/Developers

### Database Schema

```sql
ALTER TABLE users ADD COLUMN dashboard_locked BOOLEAN DEFAULT false;
```

### API Endpoint

**POST** `/dashboard/lock`

Request:
```json
{
  "X-CSRF-TOKEN": "your-csrf-token"
}
```

Response:
```json
{
  "message": "Dashboard locked.",
  "locked": true
}
```

### Checking Lock State in Code

```php
// In a controller
$isLocked = auth()->user()->dashboard_locked;

// In a Blade template
@if(auth()->user()->dashboard_locked)
    <p>Dashboard is currently locked</p>
@endif
```

### Setting Lock State Programmatically

```php
// Lock the dashboard
auth()->user()->update(['dashboard_locked' => true]);

// Unlock the dashboard
auth()->user()->update(['dashboard_locked' => false]);
```

---

## Technical Details

### Files Changed

| File | Changes |
|------|---------|
| `database/migrations/2026_11_24_000000_add_dashboard_locked_to_users_table.php` | Added `dashboard_locked` column |
| `modules/Core/Models/User.php` | Added field to `$fillable` and `casts()` |
| `modules/Core/Presentation/Http/Controllers/UserDashboardController.php` | Added `toggleDashboardLock()` method |
| `routes/web.php` | Added `/dashboard/lock` route |
| `resources/views/user-dashboard.blade.php` | Added lock button UI and JavaScript |
| `resources/css/custom.css` | Added styling for lock button |
| `tests/Feature/UserDashboardPersonalizationTest.php` | Added 2 new tests |

### Security

- ✅ CSRF protected (requires valid CSRF token)
- ✅ Authentication required (protects with `auth` middleware)
- ✅ User-specific (each user can only change their own lock state)
- ✅ No role restrictions (all authenticated users can lock/unlock)

### Browser Support

- ✅ Chrome/Edge 90+
- ✅ Firefox 88+
- ✅ Safari 14+
- ✅ All modern browsers

---

## Troubleshooting

### Lock button not appearing?

1. Clear browser cache
2. Ensure you're on the latest dashboard page
3. Check browser console for JavaScript errors

### Lock state not persisting?

1. Verify you're logged in
2. Check that the POST request succeeds (look in browser Network tab)
3. Clear cookies and log in again

### Can still drag when locked?

1. Refresh the page to reload the lock state
2. Check browser console for JavaScript errors
3. Ensure JavaScript is enabled

---

## Support

For issues or feature requests, please contact:
- Technical Support: [contact info]
- Documentation: See `/docs/dashboard-lock-feature.md`
