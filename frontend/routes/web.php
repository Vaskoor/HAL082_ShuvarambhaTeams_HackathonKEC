<?php

use App\Http\Controllers\ChatbotController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\FileManagerController;
use App\Http\Controllers\FileTypeController;
use App\Http\Controllers\FolderController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserManagementController;
use App\Models\Chatbot;
use Illuminate\Support\Facades\Route;

// Public routes
Route::get('/', fn() => view('welcome'));
Route::get('/widget/load/{chatbot}', [ChatbotController::class, 'loadWidget']);
Route::post('/chat/send', [ChatbotController::class, 'sendMessage'])->name('chat.send');
Route::get('/chat/load/{conversation}', [ChatbotController::class, 'loadMessages']);



// Delete user route (outside auth group for now, adjust if needed)
Route::delete('/users/delete/{id}', [UserManagementController::class, 'destroy'])->name('users.destroy');

// Authenticated routes
Route::middleware('auth')->group(function () {
    Route::post('/websites/{website}/generate-token', [ChatbotController::class, 'generateToken']);
    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Dashboard
    Route::get('/dashboard', fn() => view('dashboard'))->name('dashboard');

    // File Manager
    Route::get('/file-manager', [FileManagerController::class, 'index'])->name('file-manager.index');

    // User data JSON
    Route::get('/userdata', function () {
        $perPage = 10;
        $users = \App\Models\User::orderBy('id', 'desc')->paginate($perPage);
        return response()->json($users, 200, [], JSON_PRETTY_PRINT);
    });

    // Chatbot data JSON
    Route::get('/chatbotdata', function () {
        $perPage = 10;
        $chatbots = Chatbot::orderBy('id', 'desc')->paginate($perPage);
        return response()->json($chatbots, 200, [], JSON_PRETTY_PRINT);
    });


    // User management
    Route::get('/users', [UserManagementController::class, 'index'])->name('users.index');
    Route::get('/users/search', [UserManagementController::class, 'search'])->name('users.search');
    Route::post('/users/update', [UserManagementController::class, 'update'])->name('users.update');

    // Folder routes
    Route::prefix('folders')->group(function () {
        Route::get('/', [FolderController::class, 'index'])->name('folders.index');
        Route::post('/', [FolderController::class, 'store'])->name('folders.store');
        Route::post('/{folder}/rename', [FolderController::class, 'rename'])->name('folders.rename');
        Route::delete('/{folder}', [FolderController::class, 'destroy'])->name('folders.destroy');

        // File routes inside folder
        Route::post('/{folder}/files', [FileController::class, 'store'])->name('files.store');
    });

    // File routes
    Route::post('/files/{file}/rename', [FileController::class, 'rename'])->name('files.rename');
    Route::delete('/files/{file}', [FileController::class, 'destroy'])->name('files.destroy');

    // Chatbot routes
    Route::prefix('chatbots')->group(function () {
        Route::get('/', [ChatbotController::class, 'index'])->name('chatbots.index');
        Route::post('/', [ChatbotController::class, 'store'])->name('chatbots.store');
        Route::get('/{chatbot}/edit', [ChatbotController::class, 'edit'])->name('chatbot.edit');
        Route::post('/{chatbot}/edit', [ChatbotController::class, 'update'])->name('chatbot.update');
        Route::post('/{chatbot}/fewshots', [ChatbotController::class, 'addFewShot'])->name('chatbot.fewshots.add');
        Route::delete('/{chatbot}/fewshots/{index}', [ChatbotController::class, 'deleteFewShot'])->name('chatbot.fewshots.delete');
        Route::delete('/{chatbot}', [ChatbotController::class, 'destroy'])->name('chatbots.destroy');

        // Chatbot interface & conversations
        Route::prefix('{chatbot}')->group(function () {
            Route::get('/interface', fn(Chatbot $chatbot) => view('chatbot_interface.index', compact('chatbot')))->name('chatbots.interface');

            Route::get('/conversations', [ConversationController::class, 'index']);
            Route::post('/conversations', [ConversationController::class, 'store']);
            Route::delete('/conversations/{conversation}', [ConversationController::class, 'destroy']);

            Route::get('/conversations/{conversation}/messages', [MessageController::class, 'index']);
            Route::post('/conversations/{conversation}/messages', [MessageController::class, 'store']);
        });
    });

    // FileType routes
    Route::prefix('filetypes')->group(function () {
        Route::get('/', [FileTypeController::class, 'index'])->name('filetypes.index');
        Route::get('/create', [FileTypeController::class, 'create'])->name('filetypes.create'); // Add modal
        Route::post('/', [FileTypeController::class, 'store'])->name('filetypes.store');
        Route::get('/{id}/show', [FileTypeController::class, 'show'])->name('filetypes.show');
        Route::get('/{id}/edit', [FileTypeController::class, 'edit'])->name('filetypes.edit');
        Route::put('/{id}', [FileTypeController::class, 'update'])->name('filetypes.update');
        Route::delete('/{id}', [FileTypeController::class, 'destroy'])->name('filetypes.destroy');
    });
});

require __DIR__.'/auth.php';