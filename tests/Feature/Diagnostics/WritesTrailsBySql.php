<?php

declare(strict_types=1);

namespace Tests\Feature\Diagnostics;

use App\Enums\IncidentLogAction;
use App\Enums\IncidentPriority;
use App\Enums\IncidentStatus;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

trait WritesTrailsBySql
{
    /**
     * An incident and its trail written straight to the database, the way a manual UPDATE or an import leaves them.
     * Each step is [action, old_value, new_value, seconds after the opening or the exact date]. Values are strings on
     * purpose, so a test can store one the enums do not know.
     *
     * @param  list<array{IncidentLogAction, string|null, string|null, int|CarbonInterface}>  $trail
     */
    private function writtenBySql(string $title, IncidentStatus $status, array $trail): int
    {
        $openedAt = now()->subDay()->startOfSecond();
        $id = DB::table('incidents')->insertGetId([
            'title' => $title,
            'description' => 'Escrita por SQL para la prueba.',
            'status' => $status->value,
            'priority' => IncidentPriority::Low->value,
            'requester_name' => 'Nerea Gil',
            'due_at' => $openedAt->addHours(IncidentPriority::Low->slaHours()),
            'created_at' => $openedAt,
            'updated_at' => $openedAt,
        ]);

        foreach ($trail as [$action, $old, $new, $at]) {
            DB::table('incident_logs')->insert([
                'incident_id' => $id,
                'action' => $action->value,
                'old_value' => $old,
                'new_value' => $new,
                'user_name' => 'Marta Ruiz',
                'metadata' => null,
                'created_at' => $at instanceof CarbonInterface ? $at : $openedAt->addSeconds($at),
            ]);
        }

        return $id;
    }

    /**
     * Every column of every row of both tables, in id order.
     *
     * @return array<string, array<int, array<mixed>>>
     */
    private function snapshot(): array
    {
        return collect(['incidents', 'incident_logs'])->mapWithKeys(fn (string $table): array => [
            $table => DB::table($table)->orderBy('id')->get()->map(fn (object $row): array => (array) $row)->all(),
        ])->all();
    }
}
