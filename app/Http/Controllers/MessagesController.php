<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\ContactMessageReceived;
use App\Mail\ContactMessageConfirmation;
use App\Models\Message;

class MessagesController extends Controller
{
    public function index()
    {
        return response()->json(Message::latest()->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'firstName' => 'required|string|max:255',
            'lastName' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'message' => 'required|string',
        ]);

        $msg = Message::create([
            'first_name' => $validated['firstName'],
            'last_name' => $validated['lastName'],
            'email' => $validated['email'],
            'message' => $validated['message'],
        ]);

        try {
            Mail::to(config('mail.from.address'))->send(new ContactMessageReceived($msg));
            Mail::to($msg->email)->send(new ContactMessageConfirmation($msg));
        } catch (\Exception $e) {
            Log::error('Contact email failed: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => "Thank you! We'll be in touch.",
        ]);
    }

    public function show(string $id)
    {
        return response()->json(Message::findOrFail($id));
    }

    public function destroy(string $id)
    {
        Message::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Message deleted.']);
    }
}
