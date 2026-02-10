    <?php

    use Illuminate\Database\Migrations\Migration;
    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Support\Facades\Schema;

    return new class extends Migration
    {
        public function up(): void
        {
            Schema::create('patient_history', function (Blueprint $table) {
                $table->id('gl_no');
                $table->unsignedBigInteger('patient_id');
                $table->string('category');
                $table->string('partner');
                $table->decimal('hospital_bill', 15, 2)->nullable();
                $table->decimal('issued_amount', 15, 2);
                $table->string('issued_by');
                $table->date('date_issued');
                $table->timestamps();

                $table->foreign('patient_id')->references('patient_id')->on('patient_list')->onDelete('cascade');
            });
        }

        public function down(): void
        {
            Schema::dropIfExists('patient_history');
        }
    };

