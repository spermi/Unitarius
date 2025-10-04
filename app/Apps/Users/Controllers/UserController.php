<?php
declare(strict_types=1);

namespace App\Apps\Users\Controllers;

use Core\DB;
use Core\View;

/**
 * UserController
 *
 * Handles user listing and (later) management.
 * Accessible only to users with the "users.view" permission.
 */
final class UserController
{
    /**
     * GET /users
     *
     * Displays all users in a simple table (ID, name, email, status, last login, avatar).
     * Uses AdminLTE styling and referrerpolicy="no-referrer" for Google avatars.
     */
    public function index(): string
    {
        $pdo = DB::pdo();

        // Fetch all users (limit for safety)
        $stmt = $pdo->query(
            'SELECT id, name, email, status, avatar, last_login_at 
             FROM users 
             ORDER BY id ASC 
             LIMIT 200'
        );
        $users = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        return View::render('users/list', [
            'title' => 'Felhasználók listája',
            'users' => $users,
        ]);
    }
}
