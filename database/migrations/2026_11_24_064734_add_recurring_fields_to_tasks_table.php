<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignId('recurring_template_id')->nullable()->after('category_id')
                ->constrained('tasks')->nullOnDelete();
            $table->timestamp('occurrence_at')->nullable()->after('deadline_at');
            $table->unique(['recurring_template_id', 'occurrence_at'], 'tasks_occurrence_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropUnique('tasks_occurrence_unique');
            $table->dropConstrainedForeignId('recurring_template_id');
            $table->dropColumn('occurrence_at');
        });
    }
};
