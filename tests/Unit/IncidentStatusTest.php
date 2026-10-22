<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\IncidentLogAction;
use App\Enums\IncidentStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class IncidentStatusTest extends TestCase
{
    /** Every pair (from, to). Anything not listed as allowed must be rejected. */
    private const ALLOWED = [
        'open→in_review',
        'in_review→blocked',
        'in_review→resolved',
        'blocked→in_review',
        'resolved→open',
    ];

    /** @return iterable<string, array{IncidentStatus, IncidentStatus}> */
    public static function allPairs(): iterable
    {
        foreach (IncidentStatus::cases() as $from) {
            foreach (IncidentStatus::cases() as $to) {
                yield "{$from->value}→{$to->value}" => [$from, $to];
            }
        }
    }

    #[DataProvider('allPairs')]
    public function test_transition_matrix(IncidentStatus $from, IncidentStatus $to): void
    {
        $expected = in_array("{$from->value}→{$to->value}", self::ALLOWED, true);

        $this->assertSame($expected, $from->canTransitionTo($to));
    }

    public function test_blocking_and_reopening_require_a_reason(): void
    {
        $this->assertTrue(IncidentStatus::InReview->requiresReason(IncidentStatus::Blocked));
        $this->assertTrue(IncidentStatus::Resolved->requiresReason(IncidentStatus::Open));

        $this->assertFalse(IncidentStatus::Open->requiresReason(IncidentStatus::InReview));
        $this->assertFalse(IncidentStatus::InReview->requiresReason(IncidentStatus::Resolved));
        $this->assertFalse(IncidentStatus::Blocked->requiresReason(IncidentStatus::InReview));
    }

    public function test_resolving_blocking_and_reopening_ask_for_confirmation(): void
    {
        $this->assertTrue(IncidentStatus::InReview->requiresConfirmation(IncidentStatus::Resolved));
        $this->assertTrue(IncidentStatus::InReview->requiresConfirmation(IncidentStatus::Blocked));
        $this->assertTrue(IncidentStatus::Resolved->requiresConfirmation(IncidentStatus::Open));

        $this->assertFalse(IncidentStatus::Open->requiresConfirmation(IncidentStatus::InReview));
        $this->assertFalse(IncidentStatus::Blocked->requiresConfirmation(IncidentStatus::InReview));
    }

    public function test_a_reopen_is_logged_with_its_own_action(): void
    {
        $this->assertSame(IncidentLogAction::Reopened, IncidentStatus::Resolved->logActionFor(IncidentStatus::Open));
        $this->assertSame(IncidentLogAction::StatusChanged, IncidentStatus::InReview->logActionFor(IncidentStatus::Resolved));
    }

    public function test_action_labels(): void
    {
        $this->assertSame('Pasar a revisión', IncidentStatus::Open->actionLabel(IncidentStatus::InReview));
        $this->assertSame('Bloquear', IncidentStatus::InReview->actionLabel(IncidentStatus::Blocked));
        $this->assertSame('Resolver', IncidentStatus::InReview->actionLabel(IncidentStatus::Resolved));
        $this->assertSame('Desbloquear', IncidentStatus::Blocked->actionLabel(IncidentStatus::InReview));
        $this->assertSame('Reabrir', IncidentStatus::Resolved->actionLabel(IncidentStatus::Open));
    }
}
