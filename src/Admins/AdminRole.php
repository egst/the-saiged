<?php declare(strict_types = 1);

namespace TheSaiged\Admins;

/**
 * `Admin` may edit-and-manage-other-admins; `Editor` may use the admin
 * panel but not see or touch the Users screen (AdminGuard::admin gates
 * that both server- and client-side).
 */
enum AdminRole: string {

    case Editor = 'editor';
    case Admin  = 'admin';

}
