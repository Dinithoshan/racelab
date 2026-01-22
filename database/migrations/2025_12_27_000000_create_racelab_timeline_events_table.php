<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection($this->connectionName())->create($this->tableName(), function (Blueprint $table): void {
            $table->id();
            $table->string('type', 32);
            $table->uuid('request_id')->nullable(); //Groups events from the same request/job/command'
            $table->unsignedInteger('sequence')->nullable();//Order of events within a request
            $table->unsignedBigInteger('process_id')->nullable();
            $table->double('occurred_at')->nullable();//Absolute timestamp when event occurred
            $table->double('elapsed_time')->nullable();//Time elapsed since request start (ms)
            $table->text('file')->nullable();
            $table->unsignedInteger('line')->nullable();
            $table->string('class')->nullable();
            $table->string('function')->nullable();
            $table->boolean('is_vendor')->default(false);
            $table->text('payload')->nullable();//JSON payload specific to event type
            $table->timestamps();
            $table->index(['request_id', 'sequence']);
            $table->index(['type', 'occurred_at']);
            $table->index('request_id');
        });
    }

    public function down(): void
    {
        Schema::connection($this->connectionName())->dropIfExists($this->tableName());
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
