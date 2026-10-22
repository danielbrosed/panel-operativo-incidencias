<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\IncidentLogAction;
use App\Enums\IncidentPriority;
use App\Enums\IncidentStatus;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Breaks the demo data on purpose, the way it happens in real life: writes that bypass the application. It uses
 * the query builder instead of the models precisely so that none of the application's guards apply.
 * `php artisan incidents:diagnose` must report these four cases, each one in its own check (see README).
 */
class InconsistenciesSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            // A · Support closed it with a hand-written UPDATE after the technician phoned in, so the
            // in_review → resolved step never reached the trail. Check 1: reconciled automatically.
            DB::table('incidents')
                ->where('id', $this->incidentId('Fuga de agua en aseo de clientes'))
                ->update(['status' => IncidentStatus::Resolved->value]);

            // B · Someone reopened it straight in the database, without the reason the panel requires for a
            // reopen. Check 2: flagged for manual review.
            DB::table('incidents')
                ->where('id', $this->incidentId('Persiana metálica de acceso no sube'))
                ->update(['status' => IncidentStatus::Open->value]);

            // C · Double click on "Bloquear" before the row lock existed: the same request was processed twice,
            // one second apart. Check 3 (duplicate): the copy is voided automatically.
            $blocking = DB::table('incident_logs')
                ->where('incident_id', $this->incidentId('Puerta automática no abre'))
                ->where('action', IncidentLogAction::StatusChanged->value)
                ->where('new_value', IncidentStatus::Blocked->value)
                ->orderByDesc('created_at')
                ->orderByDesc('id')
                ->first() ?? throw new RuntimeException('InconsistenciesSeeder: "Puerta automática no abre" has no blocking log.');

            DB::table('incident_logs')->insert([
                'incident_id' => $blocking->incident_id,
                'action' => $blocking->action,
                'old_value' => $blocking->old_value,
                'new_value' => $blocking->new_value,
                'user_name' => $blocking->user_name,
                'metadata' => $blocking->metadata,
                'created_at' => CarbonImmutable::parse($blocking->created_at)->addSecond(),
            ]);

            // D · Imported from the previous maintenance system, which lost the open → in_review step: the trail
            // jumps from "open" to "in_review → blocked". Check 3 (broken chain): flagged for manual review.
            $openedAt = CarbonImmutable::now()->startOfMinute()->subDays(6);
            $blockedAt = $openedAt->addDay();

            $importedId = DB::table('incidents')->insertGetId([
                'title' => 'Cuadro eléctrico salta de forma intermitente',
                'description' => 'El diferencial general salta varias veces al día y la tienda se queda sin luz unos minutos.',
                'status' => IncidentStatus::Blocked->value,
                'priority' => IncidentPriority::Medium->value,
                'requester_name' => 'Javier Ortega (encargado)',
                'assigned_to' => 'Andrés Molina',
                'store' => 'Urbana Sport · Tienda 0230 · C. Mayor 8, Zaragoza',
                'due_at' => $openedAt->addHours(IncidentPriority::Medium->slaHours()),
                'created_at' => $openedAt,
                'updated_at' => $blockedAt,
            ]);

            DB::table('incident_logs')->insert([
                [
                    'incident_id' => $importedId,
                    'action' => IncidentLogAction::Created->value,
                    'old_value' => null,
                    'new_value' => IncidentStatus::Open->value,
                    'user_name' => 'importacion-legacy',
                    'metadata' => json_encode(['priority' => IncidentPriority::Medium->value], JSON_THROW_ON_ERROR),
                    'created_at' => $openedAt,
                ],
                [
                    'incident_id' => $importedId,
                    'action' => IncidentLogAction::StatusChanged->value,
                    'old_value' => IncidentStatus::InReview->value,
                    'new_value' => IncidentStatus::Blocked->value,
                    'user_name' => 'importacion-legacy',
                    'metadata' => json_encode(
                        ['reason' => 'Hay que revisar la acometida y la propiedad del local aún no lo ha autorizado.'],
                        JSON_THROW_ON_ERROR,
                    ),
                    'created_at' => $blockedAt,
                ],
            ]);
        });
    }

    private function incidentId(string $title): int
    {
        $id = DB::table('incidents')->where('title', $title)->value('id');

        if ($id === null) {
            throw new RuntimeException(
                "InconsistenciesSeeder: incident \"{$title}\" not found. It breaks the demo data, so it must run after DemoIncidentsSeeder."
            );
        }

        return (int) $id;
    }
}
