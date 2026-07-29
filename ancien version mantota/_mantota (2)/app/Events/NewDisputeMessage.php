<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewDisputeMessage implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $orderId,
        public string $senderType,
        public ?int $userId,
        public string $message,
        public string $senderName,
        public string $createdAt,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("dispute.{$this->orderId}"),
        ];
    }

    public function broadcastWith(): array
    {
        return [
            'order_id'    => $this->orderId,
            'sender_type' => $this->senderType,
            'user_id'     => $this->userId,
            'sender_name' => $this->senderName,
            'message'     => $this->message,
            'created_at'  => $this->createdAt,
        ];
    }
}
