<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$request1 = Illuminate\Http\Request::create('/api/test-idempotency', 'POST');
$request1->headers->set('Idempotency-Key', 'test-key-123');

$response1 = $kernel->handle($request1);
echo "Response 1: " . $response1->getContent() . "\n";
sleep(2);

$request2 = Illuminate\Http\Request::create('/api/test-idempotency', 'POST');
$request2->headers->set('Idempotency-Key', 'test-key-123');
$response2 = $kernel->handle($request2);
echo "Response 2: " . $response2->getContent() . "\n";
