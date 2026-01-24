<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection($this->connectionName())->table($this->tableName(), function (Blueprint $table): void {
            // Link stack trace events to their parent query event
            $table->unsignedBigInteger('parent_event_id')->nullable()->after('id');
            
            // Store the analyzed trace summary (the most relevant frame)
            $table->text('trace_summary')->nullable()->after('payload');
            
            // Add index for querying related events
            $table->index('parent_event_id');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connectionName())->table($this->tableName(), function (Blueprint $table): void {
            $table->dropIndex(['parent_event_id']);
            $table->dropColumn(['parent_event_id', 'trace_summary']);
        });
    }

    protected function connectionName(): string
    {
        return (string) config('racelab.database.connection', config('database.default'));
    }

    protected function tableName(): string
    {
        return (string) config('racelab.database.table', 'racelab_timeline_events');
    }
};
