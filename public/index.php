<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/bootstrap.php';

use App\Controllers\AuthController;
use App\Controllers\BotController;
use App\Controllers\BroadcastController;
use App\Controllers\DashboardController;
use App\Controllers\FieldController;
use App\Controllers\KeywordController;
use App\Controllers\NodeController;
use App\Controllers\SequenceController;
use App\Controllers\SubscriberController;
use App\Controllers\TagController;
use App\Core\Request;
use App\Core\Router;

$sessionConfig = config()['session'];
session_name($sessionConfig['name']);
session_start();

$router = new Router();

// Auth
$router->get('/login', [AuthController::class, 'showLogin']);
$router->post('/login', [AuthController::class, 'login']);
$router->get('/register', [AuthController::class, 'showRegister']);
$router->post('/register', [AuthController::class, 'register']);
$router->post('/logout', [AuthController::class, 'logout']);

// Dashboard
$router->get('/', [DashboardController::class, 'index'], true);

// Bots
$router->get('/bots', [BotController::class, 'index'], true);
$router->get('/bots/create', [BotController::class, 'create'], true);
$router->post('/bots', [BotController::class, 'store'], true);
$router->get('/bots/{id}/edit', [BotController::class, 'edit'], true);
$router->post('/bots/{id}', [BotController::class, 'update'], true);
$router->post('/bots/{id}/delete', [BotController::class, 'delete'], true);

// Nodes
$router->get('/bots/{botId}/nodes', [NodeController::class, 'index'], true);
$router->get('/bots/{botId}/nodes/create', [NodeController::class, 'create'], true);
$router->post('/bots/{botId}/nodes', [NodeController::class, 'store'], true);
$router->get('/bots/{botId}/nodes/{id}/edit', [NodeController::class, 'edit'], true);
$router->post('/bots/{botId}/nodes/{id}', [NodeController::class, 'update'], true);
$router->post('/bots/{botId}/nodes/{id}/delete', [NodeController::class, 'delete'], true);
$router->post('/bots/{botId}/nodes/{id}/buttons', [NodeController::class, 'storeButton'], true);
$router->post('/bots/{botId}/nodes/{id}/buttons/{buttonId}/delete', [NodeController::class, 'deleteButton'], true);

// Subscribers
$router->get('/bots/{botId}/subscribers', [SubscriberController::class, 'index'], true);
$router->get('/bots/{botId}/subscribers/{id}', [SubscriberController::class, 'show'], true);
$router->post('/bots/{botId}/subscribers/{id}/tags', [SubscriberController::class, 'addTag'], true);
$router->post('/bots/{botId}/subscribers/{id}/tags/{tagId}/delete', [SubscriberController::class, 'removeTag'], true);

// Keywords
$router->get('/bots/{botId}/keywords', [KeywordController::class, 'index'], true);
$router->post('/bots/{botId}/keywords', [KeywordController::class, 'store'], true);
$router->post('/bots/{botId}/keywords/{id}/delete', [KeywordController::class, 'delete'], true);

// Fields
$router->get('/bots/{botId}/fields', [FieldController::class, 'index'], true);

// Tags
$router->get('/bots/{botId}/tags', [TagController::class, 'index'], true);
$router->post('/bots/{botId}/tags', [TagController::class, 'store'], true);
$router->post('/bots/{botId}/tags/{id}/delete', [TagController::class, 'delete'], true);

// Broadcasts (Mərhələ 4)
$router->get('/bots/{botId}/broadcasts', [BroadcastController::class, 'index'], true);
$router->get('/bots/{botId}/broadcasts/create', [BroadcastController::class, 'create'], true);
$router->post('/bots/{botId}/broadcasts', [BroadcastController::class, 'store'], true);
$router->post('/bots/{botId}/broadcasts/{id}/send', [BroadcastController::class, 'send'], true);

// Sequences (Mərhələ 4)
$router->get('/bots/{botId}/sequences', [SequenceController::class, 'index'], true);
$router->post('/bots/{botId}/sequences', [SequenceController::class, 'store'], true);
$router->post('/bots/{botId}/sequences/{id}/messages', [SequenceController::class, 'storeMessage'], true);
$router->post('/bots/{botId}/sequences/{id}/messages/{messageId}/delete', [SequenceController::class, 'deleteMessage'], true);

$router->dispatch(new Request());
