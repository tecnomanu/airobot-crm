# Project Handover & Status - Google Sheets Integration

## Google Sheets CRM Integration - Walkthrough

This document details the implementation of the Google Sheets integration for the CRM, allowing automated export of "Sales Ready" leads.

### Changes Overview

#### Backend Architecture
- **Database**:
    - Created `google_integrations` table to store OAuth tokens securely.
    - Added `google_integration_id`, `google_spreadsheet_id`, and `google_sheet_name` columns to `campaigns` table.
- **Authentication**:
    - Implemented `GoogleController` for OAuth 2.0 flow using `laravel/socialite`.
    - Tokens are encrypted at rest.
- **Services**:
    - Created `GoogleSheetsService` to handle API interactions (creating sheets, appending rows).
- **Automation**:
    - Added `ExportLeadToGoogleSheetJob` queue job.
    - Registered `ExportLeadToGoogleSheet` listener on `LeadUpdated` event.
    - Logic triggers when Lead status is "Sales Ready" (Intention Finalized).
    - **Note:** Currently only supports "Interested" lead export.

#### Frontend (User Interface)
- **Navigation**:
    - Added "Integrations" to the sidebar menu.
    - Renamed "Configuración" profile menu item to "Perfil".
- **Settings Page**:
    - Created `resources/js/Pages/Settings/Integrations.jsx`.
    - Allows users to connect/disconnect their Google account.
- **Campaign Configuration (Refactored)**:
    - Renamed "Webhooks" tab to **"Acciones Post-Intención"**.
    - **Lead Interesado**:
        - Toggle switch in header.
        - **Action Type Choice**: Select between **Webhook** or **Google Sheet**.
        - If Google Sheet selected: Configuration UI appears here (Spreadsheet ID, Sheet Name).
        - Includes "Create New Sheet" helper button.
    - **Lead No Interesado**:
        - Toggle switch in header.
        - Currently supports Webhook only.
    - Removed Google Sheets configuration from "Información General" (BasicInfoTab).

### Verification Results

#### Automated Build
The frontend build passed successfully after resolving dependency (`react-icons`, `radio-group`) and import path issues.

#### Manual Verification Steps
1. **Connect Google Account**:
    - Go to **Integrations** in the sidebar.
    - Click "Conectar Google".
    - Complete OAuth flow.
2. **Configure Campaign**:
    - Create/Edit a campaign.
    - Go to **Acciones Post-Intención** tab.
    - Enable **Lead Interesado**.
    - Select **Exportar a Google Sheet**.
    - Choose/Enter Spreadsheet ID.
3. **Test Export**:
    - Move a lead in that campaign to "Sales Ready".
    - Verify row appears in Google Sheet.

### Configuration Requirements
Ensure the following `.env` variables are set:
```env
GOOGLE_CLIENT_ID=your-client-id
GOOGLE_CLIENT_SECRET=your-client-secret
GOOGLE_REDIRECT_URL=https://your-domain.com/auth/google/callback
```

---

## Pending Items & Next Steps

### 1. "Lead No Interesado" Support
- **Current State**: The UI allows enabling a webhook for "Not Interested" leads, but the Google Sheet export option is visually mocked or not fully implemented/selectable for this case yet.
- **Next Step**: Implement the option to export "Not Interested" leads to a separate Google Sheet (or same sheet with status column). Requires backend job adaptation to handle this trigger.

### 2. Trigger Logic Refinement
- **Current State**: The `ExportLeadToGoogleSheet` listener triggers strictly on `LeadUpdated` when status becomes `sales_ready`.
- **Next Step**: Ensure the logic covers all "Interested" intent cases. If the system uses other statuses for "Interested", updates are needed in `app/Listeners/ExportLeadToGoogleSheet.php`.

### 3. Production Testing
- **Current State**: Verified locally.
- **Next Step**: Verify Google OAuth consent screen configuration in Google Cloud Console for production (publishing status, scopes).

### 4. Code Cleanup
- Check for any residual unused files in `resources/js/Pages/Campaigns/Partials/` if they weren't fully deleted (git status shows delete, but verify).
