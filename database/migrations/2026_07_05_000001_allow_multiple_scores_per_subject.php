<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL usa el índice único (student_id, subject_id) para respaldar la
        // llave foránea scores_student_id_foreign. Si lo borramos sin darle un
        // reemplazo primero, MySQL rechaza el DROP con el error 1553.
        // Por eso creamos antes un índice simple sobre student_id.
        Schema::table('scores', function (Blueprint $table) {
            $table->index('student_id', 'scores_student_id_index');
        });

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
            $table->dropIndex('scores_student_id_index');
            $table->unique(['student_id', 'subject_id']);
        });
    }
};
