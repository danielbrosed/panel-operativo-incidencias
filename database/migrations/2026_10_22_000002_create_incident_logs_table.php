<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Append-only audit trail. Rows are never updated or deleted by the application; the only allowed
        // change is voiding a row once, with a reason (see App\Models\IncidentLog).
        Schema::create('incident_logs', function (Blueprint $table) {
            $table->id();
            // RESTRICT: the trail must outlive any attempt to delete the incident.
            $table->foreignId('incident_id')->constrained()->restrictOnDelete();
            // Exact (binary, NO PAD) comparisons, as in incidents.status: the CHECK on action and the diagnostic's
            // joins between old_value, new_value and incidents.status must not treat 'Created' or 'open ' as equal.
            $table->string('action', 40)->collation('utf8mb4_0900_bin');
            $table->string('old_value', 40)->nullable()->collation('utf8mb4_0900_bin');
            $table->string('new_value', 40)->nullable()->collation('utf8mb4_0900_bin');
            // Name snapshot at the time of the action, not a reference: the trail must not change if a person is renamed.
            $table->string('user_name', 120);
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('voided_at')->nullable();
            $table->string('voided_reason', 255)->nullable();

            // History of one incident in order; also serves the diagnostic's window functions
            // (PARTITION BY incident_id ORDER BY created_at, id).
            $table->index(['incident_id', 'created_at', 'id']);
        });

        // Same defence in depth as incidents.status: the model casts `action` to an enum, so an unknown value written
        // by SQL would break every screen that reads the trail. A new action means a new migration replacing this.
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE incident_logs ADD CONSTRAINT incident_logs_action_check CHECK (action IN ('created', 'status_changed', 'reopened', 'status_reconciled', 'flagged_for_review'))");
            // A voided row always says why, and a reason never sits on an active row.
            DB::statement('ALTER TABLE incident_logs ADD CONSTRAINT incident_logs_void_check CHECK ((voided_at IS NULL) = (voided_reason IS NULL))');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_logs');
    }
};
