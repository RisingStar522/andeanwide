<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Order;
use App\Models\Comment;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class CommentTest extends TestCase
{
    use DatabaseMigrations, RefreshDatabase;

    /** @test */
    public function create_comment()
    {
        $comment = Comment::create([
            'comments' => 'lorem ipsum',
            'cashout_id' => 1,
            'date' => '2021/01/01',
            'bank_reference_id' => 'abcd',
            'commentable_id' => 1,
            'commentable_type' => 'lorem'
        ]);

        $this->assertNotNull($comment);
        $this->assertNotNull($comment->id);
        $this->assertDatabaseHas('comments', [
            'comments' => 'lorem ipsum',
            'cashout_id' => 1,
            'date' => '2021/01/01',
            'bank_reference_id' => 'abcd',
            'commentable_id' => 1,
            'commentable_type' => 'lorem'
        ]);
    }

    /** @test */
    public function a_comment_belongs_to_an_order()
    {
        $order = Order::factory()->create();
        $comment = Comment::factory()->create([
            'commentable_id' => $order->id,
            'commentable_type' => 'App\Models\Order',
        ]);

        $this->assertNotNull($comment->commentable);
        $this->assertInstanceOf(Order::class, $comment->commentable);
        $this->assertInstanceOf(Collection::class, $order->comments);
        $this->assertInstanceOf(Comment::class, $order->comments[0]);
    }
}
