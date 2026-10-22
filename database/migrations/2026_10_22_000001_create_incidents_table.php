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
        Schema::create('incidents', function (Blueprint $table) {
            $table->id();
            $table->string('title', 160);
            $table->text('description');
            // Binary, NO PAD collation: enum values compare exactly, so the CHECKs below reject 'OPEN', 'open ' or
            // 'résolved', which the case- and accent-insensitive default (utf8mb4_unicode_ci) would let through.
            $table->string('status', 20)->collation('utf8mb4_0900_bin');
            $table->string('priority', 10)->collation('utf8mb4_0900_bin');
            $table->string('requester_name', 120);
            // A name from config('incidents.operators'); with real users this would be a foreign key to users.
            $table->string('assigned_to', 120)->nullable();
            $table->string('store', 150)->nullable();
            // Response time committed when the incident was opened (priority SLA). Stored, not recomputed,
            // so a later change of SLA policy does not move existing commitments.
            $table->timestamp('due_at');
            // NOT NULL with database defaults (unlike timestamps()): rows written outside the app still get dates.
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            // The list filters by status or priority and always sorts by date.
            $table->index(['status', 'created_at']);
            $table->index(['priority', 'created_at']);
            $table->index('created_at');
        });

        // Defence in depth for writes that bypass the application (manual SQL, imports): exactly the cases the
        // diagnostic exists for. The values are written out because a migration is a snapshot of the schema: adding
        // a status or a priority means adding a migration that drops and re-creates this CHECK.
        DB::statement("ALTER TABLE incidents ADD CONSTRAINT incidents_status_check CHECK (status IN ('open', 'in_review', 'blocked', 'resolved'))");
        DB::statement("ALTER TABLE incidents ADD CONSTRAINT incidents_priority_check CHECK (priority IN ('low', 'medium', 'high'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('incidents');
    }
};
