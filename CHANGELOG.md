# Changelog

All notable changes to `filament-notifications` will be documented in this file.

## v0.1.0 - 2026-02-15

### Added
- Initial release
- Admin notification creation via Filament UI
- Multi-user recipient selection
- Customizable notification appearance (icons, colors, types)
- Draft and send workflow
- Read status tracking
- Recipients relation manager with read/unread filtering
- Filament Shield permission integration
- Soft delete support for audit trail
- Read-only enforcement for sent notifications
- Comprehensive documentation and README

### Features
- 13+ heroicon options for notification icons
- 4 notification types (info, success, warning, danger)
- 6 icon color options
- Persistent notifications in Filament notification bell
- Real-time read tracking
- Analytics (read count, unread count, total recipients)
- Filter by type, status, and creator
- Search functionality

### Database
- `admin_notifications` table for notification storage
- `notification_recipients` pivot table for recipient tracking
- Proper indexing for performance
- Soft deletes enabled

### Models
- `AdminNotification` model with relationships and helper methods
- User model relationships via trait or manual implementation
- Policy-based authorization

### UI
- Create/Edit/View/List pages via Filament Resource
- Recipients Relation Manager for detailed tracking
- Inline send action on table and view page
- Status badges and icons
- Read/unread indicators

### Notifications
- `AdminBroadcastNotification` class
- Synchronous delivery for immediate notification
- Filament database channel integration
- Persistent notifications with "Mark as Read" action
