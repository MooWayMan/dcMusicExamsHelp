<?php

// app/Services/SchoolLinks.php

namespace App\Services;

use App\Models\ExamContact;
use App\Models\Instrument;
use App\Models\School;

/**
 * Who works at a school and what it teaches, as Paul records them on the
 * school's Edit page. Built 27 Sep 2026: the school page used to list as
 * "teachers" anyone whose pupils had an exam entry under the school's name,
 * and that name is Trinity's exam VENUE, so teachers whose pupils only sat
 * an exam at Learn Music Ltd were shown as working there.
 *
 * Teachers are the contact_school link, with `former` for people who have
 * left. Instruments are the school_instrument link. The importer still adds
 * to both when a school admin books (see TrinityCsvImporter); this is where
 * they are read and corrected by hand.
 */
final class SchoolLinks
{
    /** @return list<array{id: int, name: string, former: bool}> Current first, then by name. */
    public function teachers(School $school): array
    {
        return $school->contacts()
            ->orderBy('contact_school.former')
            ->orderBy('exam_contacts.name')
            ->get(['exam_contacts.id', 'exam_contacts.name'])
            ->map(fn (ExamContact $c) => [
                'id' => $c->id,
                'name' => $c->name,
                'former' => (bool) $c->pivot->former,
            ])
            ->values()
            ->all();
    }

    /** @return list<array{id: int, name: string}> Everyone who can be added: teachers and school admins. */
    public function teacherOptions(): array
    {
        return ExamContact::query()
            ->withType(['teacher', 'school_admin'])
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (ExamContact $c) => ['id' => $c->id, 'name' => $c->name])
            ->all();
    }

    /** @param list<array{id: int|string, former?: bool|int|string}> $teachers The whole list; anyone left out is unlinked. */
    public function saveTeachers(School $school, array $teachers): void
    {
        $school->contacts()->sync(
            collect($teachers)->mapWithKeys(fn (array $t) => [
                (int) $t['id'] => ['former' => filter_var($t['former'] ?? false, FILTER_VALIDATE_BOOLEAN)],
            ])->all(),
        );
    }

    /** @return list<int> */
    public function instrumentIds(School $school): array
    {
        return $school->instruments()->pluck('instruments.id')->map(fn ($id) => (int) $id)->all();
    }

    /** @return list<array{id: int, name: string}> */
    public function instrumentOptions(): array
    {
        return Instrument::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Instrument $i) => ['id' => $i->id, 'name' => $i->name])
            ->all();
    }

    /** @param list<int|string> $ids The whole list; anything left out is removed. */
    public function saveInstruments(School $school, array $ids): void
    {
        $school->instruments()->sync(array_map('intval', $ids));
    }
}
