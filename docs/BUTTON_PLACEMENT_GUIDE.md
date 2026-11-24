DASHBOARD HEADER LAYOUT
=======================

[Dashboard Title]                                    [Welcome Message]

╔═══════════════════════════════════════════════════════════════════════════╗
║ Card Header: My Applications                                              ║
╠═══════════════════════════════════════════════════════════════════════════╣
║                                                                           ║
║  Display Mode Selector          Lock Button    Groups  Save Order        ║
║  ┌─────────────────┐          ┌──────────┐    Button   Button           ║
║  │ ▭ │ ▬ │ ☰ │   │          │ 🔓/🔒   │   │Groups│  │Save Order│      ║
║  └─────────────────┘          │ Unlocked │   └──────┘  └─────────┘      ║
║   Normal Tile View             │ Locked   │                             ║
║   Small Tile View              └──────────┘                             ║
║   Text Only View                                                        ║
║                                                                           ║
╠═══════════════════════════════════════════════════════════════════════════╣
║                                                                           ║
║   [Application Tile 1]  [Application Tile 2]  [Application Tile 3]      ║
║   [Application Tile 4]  [Application Tile 5]  [Application Tile 6]      ║
║                                                                           ║
║   When UNLOCKED (🔓):                                                   ║
║   • User can drag tiles to reorder                                      ║
║   • Save Order button appears after drag                                ║
║   • Button text shows "Unlocked"                                        ║
║                                                                           ║
║   When LOCKED (🔒):                                                     ║
║   • Dragging is disabled                                                ║
║   • Tiles show reduced opacity                                          ║
║   • Button text shows "Locked"                                          ║
║   • Button has red background color                                     ║
║                                                                           ║
╚═══════════════════════════════════════════════════════════════════════════╝


KEY BUTTON FEATURES
===================

Default State (Unlocked):
  Icon:     🔓 (unlock icon)
  Label:    "Unlocked"
  Color:    Secondary (gray)
  Position: 4th button in header row
  Tooltip:  "Lock dashboard to prevent accidental changes"
  Action:   Click to lock

Locked State:
  Icon:     🔒 (lock icon)  
  Label:    "Locked"
  Color:    Danger (red #dc3545)
  Position: 4th button in header row
  Tooltip:  "Unlock dashboard to edit"
  Action:   Click to unlock


BUTTON INTERACTIONS
===================

User Clicks Button:
  1. Button becomes disabled (prevents double-click)
  2. AJAX request sent to server
  3. Database updated
  4. Response received with new state
  5. Button UI updates:
     - Icon changes (unlock ↔ lock)
     - Text changes (Unlocked ↔ Locked)
     - Color may change
     - Tooltip updates
  6. Button re-enabled
  7. Page ready for next action


DRAG BEHAVIOR
=============

When UNLOCKED:
  • Mouse over application: Shows hover effect
  • Click and drag: Application follows cursor
  • Release: Application drops into new position
  • Button appears: "Save Order" button shows
  • Click Save: Order persists to database

When LOCKED:
  • Mouse over application: No special effect
  • Click and drag: Nothing happens (prevented by JavaScript)
  • No drag visual feedback
  • "Save Order" button never appears
  • Database unchanged


USER EXPERIENCE FLOW
====================

Session 1:
  1. User logs in
  2. Dashboard loads - button shows "🔓 Unlocked"
  3. User drags applications
  4. User clicks "🔓 Unlocked" button
  5. Button updates to "🔒 Locked"
  6. User tries to drag - nothing happens
  7. User logs out

Session 2:
  1. User logs in
  2. Dashboard loads - button shows "🔒 Locked" (state persisted!)
  3. User tries to drag - nothing happens
  4. User clicks "🔒 Locked" button
  5. Button updates to "🔓 Unlocked"
  6. User can now drag applications
  7. Preference is saved again


RESPONSIVE DESIGN
=================

Desktop (> 768px):
  • Button appears in header with text
  • Full tooltip visible on hover
  • Normal click behavior

Tablet (480-768px):
  • Button appears in header
  • Icon and abbreviated text visible
  • Tooltip on hover

Mobile (< 480px):
  • Button appears in header
  • Icon visible, text may wrap
  • Tooltip visible on tap
