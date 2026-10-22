<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
            $table->string('action', 40);
            $table->string('old_value', 40)->nullable();
            $table->string('new_value', 40)->nullable();
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
    }

    public function down(): void
    {
        Schema::dropIfExists('incident_logs');
    }
};
