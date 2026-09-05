<?php

namespace Tests\Feature;

use App\Models\News;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class NewsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_news_index_and_show_pages_render(): void
    {
        $this->withoutVite();

        $article = News::create([
            'title' => '露天風呂の点検のお知らせ',
            'content' => "明日は午前中、露天風呂の点検を行います。\nご不便をおかけします。",
            'published_at' => now()->toDateString(),
        ]);

        $this->get(route('news'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('News/Index')
                ->has('news', 1)
            );

        $this->get(route('news.show', $article))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('News/Show')
                ->where('article.id', $article->id)
                ->where('article.title', '露天風呂の点検のお知らせ')
                ->where('article.content', "明日は午前中、露天風呂の点検を行います。\nご不便をおかけします。")
            );
    }
}
