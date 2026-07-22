<?php

return [

    // Default abilities applied to any module that uses standard CRUD.
    'default_abilities' => ['view', 'create', 'edit', 'delete'],

    // Every admin module derived from routes/admin.php, mapped to its allowed abilities.
    'modules' => [
        // Global / Core App permissions (not tied to standard CRUD)
        'system' => [
            'access_dashboard',
            'view_system_logs',
        ],

        // ─── CMS ───────────────────────────────────────────────────────────────
        'legal_documents' => ['view', 'edit'],
        'contact_messages' => ['view', 'delete', 'mark_reviewed'],

        // ─── Campaigns ───────────────────────────────────────────────────────
        'campaign_expenses' => ['view', 'create', 'edit'],
        'campaign_beneficiary_reports' => ['view', 'export'],

        // ─── Meetings ────────────────────────────────────────────────────────
        'meetings' => ['view', 'create', 'edit', 'delete', 'print'],
        'meeting_minutes' => ['create', 'edit', 'approve'],
        'meeting_decisions' => ['create', 'edit', 'delete', 'reorder', 'update_status'],
        'meeting_attachments' => ['create', 'delete', 'download'],

        // ─── Beneficiaries ───────────────────────────────────────────────────
        'beneficiaries' => ['view', 'create', 'edit', 'delete', 'update_status', 'view_sensitive_details'],
        'beneficiary_assessments' => ['create', 'edit', 'review', 'approve'],
        'beneficiary_supports' => ['view', 'create'],
        'beneficiary_support_reports' => ['view', 'export'],
        'aid_items' => ['view', 'create', 'edit'],

        // ─── Financial ───────────────────────────────────────────────────────
        'donations' => ['view', 'export'],
        'transactions' => ['view', 'create', 'edit', 'export', 'reverse'],
        'transfers' => ['view', 'create'],
    ],
];
