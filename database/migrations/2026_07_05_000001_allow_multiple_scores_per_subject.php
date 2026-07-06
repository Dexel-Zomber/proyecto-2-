<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scores', function (Blueprint $table) {
            // Antes solo se permitía UNA nota por estudiante/materia para siempre.
            // Esto impedía registrar parciales y hacía inútil el cálculo de tendencia.
            $table->dropUnique(['student_id', 'subject_id']);

            if (! Schema::hasColumn('scores', 'label')) {
                $table->string('label')->nullable()->after('subject_id');
            }
        });

        // Etiqueta por defecto para las notas ya existentes.
        \DB::table('scores')->whereNull('label')->update(['label' => 'General']);

        Schema::table('scores', function (Blueprint $table) {
            $table->string('label')->default('General')->nullable(false)->change();
            $table->unique(['student_id', 'subject_id', 'label']);
        });
    }

    public function down(): void
    {
        Schema::table('scores', function (Blueprint $table) {
            $table->dropUnique(['student_id', 'subject_id', 'label']);
            $table->dropColumn('label');
            $table->unique(['student_id', 'subject_id']);
        });
    }
};
