<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageAttachment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ChatController extends Controller
{
    /** Elenco conversazioni dell'utente con anteprima ultimo messaggio e non letti. */
    public function conversations(Request $request)
    {
        $userId = $request->user()->id;

        $conversations = Conversation::query()
            ->forUser($userId)
            ->with(['users:id,name,email', 'latestMessage'])
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->get();

        $unread = $this->unreadByConversation($userId, $conversations->pluck('id'));

        return response()->json(
            $conversations->map(fn ($c) => $this->presentConversation($c, $userId, $unread[$c->id] ?? 0))
        );
    }

    /** Utenti con cui avviare una chat (tutti tranne se stesso). */
    /** public function users(Request $request)
    * {
    *    return response()->json(
    *        User::where('id', '!=', $request->user()->id)
    *            ->orderBy('name')
    *            ->get(['id', 'name', 'email'])
    *    );
    * } */

public function users(Request $request)
{
    return response()->json(
        User::where('id', '!=', $request->user()->id)
            ->where('email', 'like', '%@isomaxporte.com')
            ->orderBy('name')
            ->get(['id', 'name', 'email'])
    );
}


    /** Trova o crea una conversazione diretta 1-a-1. */
    public function direct(Request $request)
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $me    = $request->user()->id;
        $other = (int) $data['user_id'];
        abort_if($other === $me, 422, 'Non puoi chattare con te stesso.');

        $conversation = Conversation::query()
            ->where('type', 'direct')
            ->whereHas('users', fn ($q) => $q->where('users.id', $me))
            ->whereHas('users', fn ($q) => $q->where('users.id', $other))
            ->first();

        if (! $conversation) {
            $conversation = Conversation::create([
                'type'       => 'direct',
                'created_by' => $me,
            ]);
            $conversation->users()->attach([$me, $other]);
        }

        return response()->json(['id' => $conversation->id]);
    }

    /** Messaggi di una conversazione. Con ?after=ID restituisce solo i nuovi (polling). */
    public function messages(Conversation $conversation, Request $request)
    {
        $this->ensureMember($conversation, $request->user()->id);

        if ($after = $request->integer('after')) {
            $messages = $conversation->messages()
                ->with(['user:id,name', 'attachments'])
                ->where('id', '>', $after)
                ->orderBy('id')
                ->limit(100)
                ->get();
        } else {
            $messages = $conversation->messages()
                ->with(['user:id,name', 'attachments'])
                ->orderByDesc('id')
                ->limit(50)
                ->get()
                ->reverse()
                ->values();
        }

        return response()->json(
            $messages->map(fn ($m) => $this->presentMessage($m, $request->user()->id))
        );
    }

    /** Invia un messaggio, con eventuali allegati (immagini o documenti). */
    public function store(Conversation $conversation, Request $request)
    {
        $this->ensureMember($conversation, $request->user()->id);

        $data = $request->validate([
            'body'          => ['nullable', 'required_without:attachments', 'string', 'max:5000'],
            'attachments'   => ['nullable', 'array', 'max:10'],
            'attachments.*' => ['file', 'max:20480'],   // max 20 MB a file
        ]);

        $message = $conversation->messages()->create([
            'user_id' => $request->user()->id,
            'body'    => $data['body'] ?? '',
        ]);

        foreach ($request->file('attachments', []) as $file) {
            $path = $file->store("chat/{$conversation->id}");   // disco privato (storage/app)
            $message->attachments()->create([
                'path'          => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type'     => $file->getClientMimeType(),
                'size'          => $file->getSize(),
            ]);
        }

        $conversation->update(['last_message_at' => $message->created_at]);
        $conversation->users()->updateExistingPivot($request->user()->id, [
            'last_read_at' => now(),
        ]);

        // 🔌 REALTIME (upgrade futuro a Reverb):
        // broadcast(new \App\Events\MessageSent($message))->toOthers();

        $message->load('user:id,name', 'attachments');

        return response()->json($this->presentMessage($message, $request->user()->id), 201);
    }

    /** Scarica/visualizza un allegato (solo membri della conversazione). */
    public function attachment(MessageAttachment $attachment, Request $request)
    {
        $conversation = $attachment->message->conversation;
        $this->ensureMember($conversation, $request->user()->id);

        abort_unless(Storage::exists($attachment->path), 404);

        // le immagini inline (per <img>), gli altri file come download
        return $attachment->isImage()
            ? Storage::response($attachment->path, $attachment->original_name)
            : Storage::download($attachment->path, $attachment->original_name);
    }

    /** Cancella un messaggio (solo l'autore). I file vengono rimossi dal model. */
    public function destroy(Message $message, Request $request)
    {
        abort_unless($message->user_id === $request->user()->id, 403);

        $conversation = $message->conversation;
        $message->delete();   // l'evento "deleting" sul model cancella anche i file

        $last = $conversation->messages()->latest('id')->first();
        $conversation->update(['last_message_at' => $last?->created_at]);

        return response()->json(['ok' => true]);
    }

    /** Segna la conversazione come letta. */
    public function read(Conversation $conversation, Request $request)
    {
        $this->ensureMember($conversation, $request->user()->id);

        $conversation->users()->updateExistingPivot($request->user()->id, [
            'last_read_at' => now(),
        ]);

        return response()->json(['ok' => true]);
    }

    /** Totale messaggi non letti (per il badge). */
    public function unreadCount(Request $request)
    {
        $userId = $request->user()->id;
        $ids    = Conversation::forUser($userId)->pluck('id');

        return response()->json([
            'count' => array_sum($this->unreadByConversation($userId, $ids)),
        ]);
    }

    /* -------------------- helper privati -------------------- */

    private function unreadByConversation(int $userId, $conversationIds): array
    {
        if ($conversationIds->isEmpty()) {
            return [];
        }

        return DB::table('messages as m')
            ->join('conversation_user as cu', function ($join) use ($userId) {
                $join->on('cu.conversation_id', '=', 'm.conversation_id')
                     ->where('cu.user_id', '=', $userId);
            })
            ->whereIn('m.conversation_id', $conversationIds)
            ->where('m.user_id', '!=', $userId)
            ->where(function ($q) {
                $q->whereNull('cu.last_read_at')
                  ->orWhereColumn('m.created_at', '>', 'cu.last_read_at');
            })
            ->groupBy('m.conversation_id')
            ->selectRaw('m.conversation_id, COUNT(*) as cnt')
            ->pluck('cnt', 'm.conversation_id')
            ->toArray();
    }
/** Elimina un'intera conversazione (messaggi + allegati). Solo per i membri. */
public function destroyConversation(Conversation $conversation, Request $request)
{
    $this->ensureMember($conversation, $request->user()->id);

    // rimuove i file fisici degli allegati di questa conversazione
    Storage::deleteDirectory("chat/{$conversation->id}");

    // cancella la conversazione: il cascade elimina pivot, messaggi e allegati
    $conversation->delete();

    return response()->json(['ok' => true]);
}
    private function presentConversation(Conversation $c, int $userId, int $unread): array
    {
        $others = $c->users->where('id', '!=', $userId)->values();

        $title = $c->type === 'group'
            ? ($c->title ?: 'Gruppo')
            : ($others->first()->name ?? 'Utente');

        return [
            'id'              => $c->id,
            'type'            => $c->type,
            'title'           => $title,
            'participants'    => $c->users->map(fn ($u) => ['id' => $u->id, 'name' => $u->name])->values(),
            'last_message'    => $c->latestMessage ? [
                'body'       => Str::limit($c->latestMessage->body ?: '📎 Allegato', 80),
                'user_id'    => $c->latestMessage->user_id,
                'created_at' => $c->latestMessage->created_at,
            ] : null,
            'last_message_at' => $c->last_message_at,
            'unread'          => $unread,
        ];
    }

    private function presentMessage($m, int $userId): array
    {
        return [
            'id'          => $m->id,
            'body'        => $m->body,
            'user_id'     => $m->user_id,
            'user_name'   => $m->user->name ?? '',
            'created_at'  => $m->created_at,
            'mine'        => $m->user_id === $userId,
            'attachments' => $m->attachments->map(fn ($a) => [
                'id'       => $a->id,
                'name'     => $a->original_name,
                'mime'     => $a->mime_type,
                'size'     => $a->size,
                'is_image' => $a->isImage(),
                'url'      => route('chat.attachment', $a->id),
            ])->values(),
        ];
    }

    private function ensureMember(Conversation $conversation, int $userId): void
    {
        abort_unless($conversation->hasUser($userId), 403);
    }
}
