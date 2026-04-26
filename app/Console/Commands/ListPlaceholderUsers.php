<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * List users in the `users` table who appear to be placeholders rather
 * than real login accounts — created during early imports to satisfy
 * orders.user_id / students.user_id FKs back when teachers were stored
 * as Users. The unified contacts model has fully replaced that.
 *
 * "Placeholder" heuristic: no login activity (last_login_at is NULL or
 * was never used). Excludes admins by role.
 *
 * Read-only — does not delete anything. Review the output, then decide
 * which (if any) to remove via a separate destructive command after
 * taking a TablePlus backup.
 */
class ListPlaceholderUsers extends Command
{
    protected $signature = 'users:list-placeholders
                            {--with-orders : Include count of legacy orders.user_id rows (only useful before that column is dropped)}';

    protected $description = 'List users who look like import placeholders rather than real accounts';

    public function handle(): int
    {
        $hasOrdersUserId = DB::getSchemaBuilder()->hasColumn('orders', 'user_id');
        $hasStudentsUserId = DB::getSchemaBuilder()->hasColumn('students', 'user_id');
        $hasLastLoginAt = DB::getSchemaBuilder()->hasColumn('users', 'last_login_at');

        $query = User::query()
            ->where(function ($q) {
                $q->where('role', '!=', 'admin')
                    ->orWhereNull('role');
            });

        if ($hasLastLoginAt) {
            $query->whereNull('last_login_at');
        }

        $users = $query->orderBy('id')->get(['id', 'name', 'email', 'role', 'created_at']);

        if ($users->isEmpty()) {
            $this->info('No placeholder users found.');
            return Command::SUCCESS;
        }

        $this->info("Found {$users->count()} placeholder user(s):");
        $this->newLine();

        $rows = $users->map(function (User $u) use ($hasOrdersUserId, $hasStudentsUserId) {
            $row = [
                'id'         => $u->id,
                'name'       => $u->name ?? '—',
                'email'      => $u->email,
                'role'       => $u->role ?? '—',
                'created'    => $u->created_at?->format('Y-m-d') ?? '—',
            ];

            if ($hasStudentsUserId) {
                $row['students'] = DB::table('students')->where('user_id', $u->id)->count();
            }
            if ($hasOrdersUserId && $this->option('with-orders')) {
                $row['orders'] = DB::table('orders')->where('user_id', $u->id)->count();
            }

            return $row;
        })->all();

        $this->table(array_keys($rows[0]), $rows);
        $this->newLine();
        $this->comment('This command is READ-ONLY. Review the list, then decide what to remove.');

        return Command::SUCCESS;
    }
}
