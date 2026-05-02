<?php

namespace App\Actions\Fortify;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Models\ExamContact;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules, ProfileValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * In addition to the standard Fortify create flow we:
     *   1. Validate `role` against User::ROLES so a user can't sign themselves
     *      up as `admin` (admins are seeded by Paul, not self-registered).
     *   2. After the user is saved, attempt to find a matching `exam_contacts`
     *      row by email. If we find one we mirror a few CRM-ish flags onto
     *      the user so first-login dashboards have something to show. We
     *      intentionally do NOT modify the contact row — it remains the
     *      single source of truth on the people side.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        // Self-registration disallows the `admin` role outright. Admins are
        // created/promoted by Paul directly via tinker / TablePlus.
        $assignableRoles = array_values(array_filter(User::ROLES, fn ($r) => $r !== 'admin'));

        Validator::make($input, [
            ...$this->profileRules(),
            'role' => ['required', 'string', 'in:'.implode(',', $assignableRoles)],
            'password' => $this->passwordRules(),
        ])->validate();

        $user = User::create([
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => $input['password'],
            'role' => $input['role'],
        ]);

        // Best-effort link to the wider exam_contacts people system. If no
        // match exists the user simply lands on the holding-message dashboard;
        // an admin can stitch them up manually later.
        $contact = ExamContact::query()
            ->where('email', $input['email'])
            ->orWhereHas('emails', fn ($q) => $q->where('email', $input['email']))
            ->first();

        if ($contact && $user->hubspot_contact_id === null && $contact->hubspot_contact_id) {
            $user->forceFill(['hubspot_contact_id' => $contact->hubspot_contact_id])->save();
        }

        return $user;
    }
}
