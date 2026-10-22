<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Actions\Incidents\AddIncidentComment;
use App\Actions\Incidents\ChangeIncidentStatus;
use App\Actions\Incidents\CreateIncident;
use App\Data\Actor;
use App\Data\NewIncidentData;
use App\Enums\IncidentPriority;
use App\Enums\IncidentStatus;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

/**
 * Builds a realistic history through the same actions the panel uses, travelling in time for each step.
 * Every incident, log and comment is therefore consistent by construction: on this data alone,
 * `incidents:diagnose` finds nothing.
 */
class DemoIncidentsSeeder extends Seeder
{
    public function run(CreateIncident $createIncident, ChangeIncidentStatus $changeStatus, AddIncidentComment $addComment): void
    {
        $now = CarbonImmutable::now()->startOfMinute();

        try {
            foreach ($this->dataset() as $row) {
                $openedAt = $now->subMinutes((int) round($row['opened_hours_ago'] * 60));

                $this->travelTo($openedAt);
                $incident = $createIncident->handle(new NewIncidentData(
                    title: $row['title'],
                    description: $row['description'],
                    priority: IncidentPriority::from($row['priority']),
                    requesterName: $row['requester'],
                    assignedTo: $row['assigned_to'],
                    store: $row['store'],
                ), new Actor($row['opened_by']));

                foreach ($this->eventsInOrder($row) as [$hours, $event]) {
                    $this->travelTo($openedAt->addMinutes((int) round($hours * 60)));

                    if ($event['type'] === 'step') {
                        $incident = $changeStatus->handle(
                            $incident,
                            IncidentStatus::from($event['to']),
                            $incident->status,
                            new Actor($event['actor']),
                            $event['reason'],
                        );
                    } else {
                        $addComment->handle($incident, $event['body'], new Actor($event['actor']));
                    }
                }
            }
        } finally {
            Carbon::setTestNow();
            CarbonImmutable::setTestNow();
        }
    }

    /**
     * @return list<array{title: string, description: string, priority: string, requester: string, store: string,
     *     assigned_to: string|null, opened_by: string, opened_hours_ago: int|float,
     *     steps: list<array{0: int|float, 1: string, 2: string, 3: string|null}>,
     *     comments: list<array{0: int|float, 1: string, 2: string}>}>
     */
    private function dataset(): array
    {
        return require __DIR__.'/data/demo-incidents.php';
    }

    /**
     * Steps and comments of one incident, merged and sorted by time.
     *
     * @param  array{steps: list<array{0: int|float, 1: string, 2: string, 3: string|null}>, comments: list<array{0: int|float, 1: string, 2: string}>}  $row
     * @return list<array{0: int|float, 1: array{type: 'step', to: string, actor: string, reason: string|null}|array{type: 'comment', actor: string, body: string}}>
     */
    private function eventsInOrder(array $row): array
    {
        $events = [];
        foreach ($row['steps'] as [$hours, $to, $actor, $reason]) {
            $events[] = [$hours, ['type' => 'step', 'to' => $to, 'actor' => $actor, 'reason' => $reason]];
        }
        foreach ($row['comments'] as [$hours, $actor, $body]) {
            $events[] = [$hours, ['type' => 'comment', 'actor' => $actor, 'body' => $body]];
        }

        usort($events, fn (array $a, array $b): int => $a[0] <=> $b[0]);

        return $events;
    }

    private function travelTo(CarbonImmutable $moment): void
    {
        Carbon::setTestNow($moment);
        CarbonImmutable::setTestNow($moment);
    }
}
