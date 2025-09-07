<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('users', function (Blueprint $t) {
            $t->text('dropbox_refresh_token')->nullable();
            $t->string('dropbox_access_token', 2048)->nullable();
            $t->timestamp('dropbox_token_expires_at')->nullable();
        });
    }
    public function down(): void {
        Schema::table('users', function (Blueprint $t) {
            $t->dropColumn([
                'dropbox_refresh_token',
                'dropbox_access_token',
                'dropbox_token_expires_at',
            ]);
        });
    }
};
