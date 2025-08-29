<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use CustomFacades\Repositories\DeviceRepo;
use CustomFacades\Repositories\UserRepo;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Input;
use Tobuli\Entities\Chat;
use Tobuli\Entities\ChatMessage;
use Tobuli\Entities\Device;
use Tobuli\Entities\TraccarDevice;
use Tobuli\Entities\User;

use App\Exceptions\ResourseNotFoundException;
use App\Exceptions\PermissionException;
use Tobuli\Exceptions\ValidationException;

class ChatController extends Controller {

    public function index()
    {
        $this->checkException('chats', 'view');

        $chattableObjects = $this->user->devices()
            ->search(Input::get('search_phrase'))
            ->protocol('osmand')
            ->paginate(10);

        return view('front::Chat.index')->with(compact('chattableObjects'));
    }

    public function searchParticipant()
    {
        $this->checkException('chats', 'view');

        $chattableObjects = $this->user->devices()
            ->search(Input::get('search_phrase'))
            ->protocol('osmand')
            ->paginate(10);

        return view('front::Chat.partials.table')->with(compact('chattableObjects'));
    }

    public function getChat($chatId)
    {
        $chat = Chat::with(['participants'])->find($chatId);

        $this->checkException('chats', 'show', $chat);

        return view('front::Chat.partials.conversation')
            ->with([
                'chat' => $chat,
                'messages' => $chat->getLastMessages(),
            ]);
    }

    public function getMessages($chatId)
    {
        $chat = Chat::find($chatId);

        $this->checkException('chats', 'show', $chat);

        $messages = $chat->getLastMessages();

        return response()->json(array_merge(['status' => 1], $messages->toArray()));
    }

    public function initChat($chatableId, $type = 'device')
    {
        $this->checkException('chats', 'store');

        switch ($type) {
            case 'device':
                $device = Device::find($chatableId);

                if ( ! $device)
                    throw new ModelNotFoundException('Device not found');

                $chat = Chat::getRoomByDevice($device);
                $chat->addParticipant($this->user);

                break;
            case 'user':
                $participants = new Collection();

                $user = User::find($chatableId);
                if ( ! $user)
                    throw new ModelNotFoundException('User not found');

                $participants->push($user);
                $participants->push($this->user);

                $chat = Chat::getRoom($participants);

                break;
            default:
                throw new \Exception("Type '$type' not supported");
        }

        return view('front::Chat.partials.conversation')->with([
                'chat' => $chat,
                'messages' => $chat->getLastMessages()->setPath(route('chat.messages', $chat->id))
            ]);
    }

    public function createMessage($chatId) {
        if (empty($this->data['message'])) {
            throw new ValidationException(['message' => trans('validation.attributes.message')]);
        }

        $chat = Chat::find($chatId);

        $this->checkException('chats', 'update', $chat);

        $message = new ChatMessage();
        $message
            ->setTo(null, $chat)
            ->setFrom($this->user)
            ->setContent($this->data['message'])->send();

        return response()->json(['status' => 1]);
    }
}
