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
            $table->string('status', 20);
            $table->string('priority', 10);
            $table->string('requester_name', 120);
            // A name from config('incidents.operators'); with real users this would be a foreign key to users.
            $table->string('assigned_to', 120)->nullable();
            $table->string('store', 150)->nullable();
            // Response time committed when the incident was opened (priority SLA). Stored, not recomputed,
            // so a later change of SLA policy does not move existing commitments.
            $table->timestamp('due_at');
            $table->timestamps();

            // The list filters by status or priority and always sorts by date.
            $table->index(['status', 'created_at']);
            $table->index(['priority', 'created_at']);
            $table->index('created_at');
        });

        // Defence in depth for writes that bypass the application (manual SQL, imports): exactly the cases the
        // diagnostic exists for. Values are written out on purpose: a migration is a snapshot of the schema at this
        // point in history, so adding a status means a new migration (see .claude/skills/incident-domain).
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE incidents ADD CONSTRAINT incidents_status_check CHECK (status IN ('open', 'in_review', 'blocked', 'resolved'))");
            DB::statement("ALTER TABLE incidents ADD CONSTRAINT incidents_priority_check CHECK (priority IN ('low', 'medium', 'high'))");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('incidents');
    }
};
